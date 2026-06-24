<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FactionType;
use App\Models\FactionDailyStat;
use App\Models\ScoreWeight;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * 진영 점수 집계 서비스.
 *
 * 역할:
 *   1. 실시간 점수 (Redis HSET): 1분 간격 갱신
 *   2. 일간 점수 집계 (factions_daily_stats): 매일 00:05 스케줄러 실행
 *
 * 정규화 점수 공식:
 *   raw_score        = post×3 + comment×1 + vote_up×2 - vote_down×0.5 - report×5
 *   normalized_score = raw_score ÷ max(active_user_count, 1)
 */
class FactionScoreService
{
    /** Redis 실시간 점수 해시 키 */
    private const REALTIME_KEY    = 'polit:realtime_scores';
    private const REALTIME_TTL    = 120; // 2분 (1분 갱신 + 여유)

    // -------------------------------------------------------------------------
    // 실시간 점수 (Redis)
    // -------------------------------------------------------------------------

    /**
     * 세 진영의 실시간 점수를 Redis에서 읽어 반환.
     * 캐시 미스 시 DB에서 당일 집계값으로 초기화.
     *
     * @return array<string, float>  ['conservative' => 12.34, ...]
     */
    public function getRealtimeScores(): array
    {
        $cached = Redis::hgetall(self::REALTIME_KEY);

        if (empty($cached)) {
            return $this->refreshRealtimeCache();
        }

        return array_map('floatval', $cached);
    }

    /**
     * 실시간 캐시를 DB에서 재계산하여 Redis에 저장.
     *
     * @return array<string, float>
     */
    public function refreshRealtimeCache(): array
    {
        $scores = $this->calculateTodayScores();

        if (! empty($scores)) {
            Redis::pipeline(function ($pipe) use ($scores) {
                foreach ($scores as $faction => $score) {
                    $pipe->hset(self::REALTIME_KEY, $faction, (string) $score);
                }
                $pipe->expire(self::REALTIME_KEY, self::REALTIME_TTL);
            });
        }

        return $scores;
    }

    // -------------------------------------------------------------------------
    // 일간 집계 (스케줄러 호출용)
    // -------------------------------------------------------------------------

    /**
     * 특정 날짜의 진영별 점수를 집계하여 factions_daily_stats에 저장.
     * 기본값: 어제 (스케줄러에서 매일 00:05 실행).
     */
    public function aggregateDailyStats(?Carbon $date = null): void
    {
        $date    = ($date ?? now()->subDay())->startOfDay();
        $dateStr = $date->toDateString();
        $weights = ScoreWeight::getCachedWeights();

        DB::transaction(function () use ($date, $dateStr, $weights) {
            foreach (FactionType::cases() as $faction) {
                $factionVal = $faction->value;

                // 원시 지표 집계 (놀이터 게시판 제외)
                $postCount = DB::table('posts')
                    ->join('boards', 'posts.board_id', '=', 'boards.id')
                    ->where('boards.board_type', '!=', 'playground')
                    ->where('posts.faction', $factionVal)
                    ->whereDate('posts.created_at', $dateStr)
                    ->whereNull('posts.deleted_at')
                    ->count();

                $commentCount = DB::table('comments')
                    ->join('posts', 'comments.post_id', '=', 'posts.id')
                    ->join('boards', 'posts.board_id', '=', 'boards.id')
                    ->where('boards.board_type', '!=', 'playground')
                    ->where('comments.faction', $factionVal)
                    ->whereDate('comments.created_at', $dateStr)
                    ->whereNull('comments.deleted_at')
                    ->count();

                $voteUpCount = DB::table('votes')
                    ->join('posts', function ($join) use ($factionVal) {
                        $join->on('votes.votable_id', '=', 'posts.id')
                             ->where('votes.votable_type', 'App\Models\Post')
                             ->where('posts.faction', $factionVal);
                    })
                    ->join('boards', 'posts.board_id', '=', 'boards.id')
                    ->where('boards.board_type', '!=', 'playground')
                    ->where('votes.vote_type', 'up')
                    ->whereDate('votes.created_at', $dateStr)
                    ->count('votes.id');

                $voteDownCount = DB::table('votes')
                    ->join('posts', function ($join) use ($factionVal) {
                        $join->on('votes.votable_id', '=', 'posts.id')
                             ->where('votes.votable_type', 'App\Models\Post')
                             ->where('posts.faction', $factionVal);
                    })
                    ->join('boards', 'posts.board_id', '=', 'boards.id')
                    ->where('boards.board_type', '!=', 'playground')
                    ->where('votes.vote_type', 'down')
                    ->whereDate('votes.created_at', $dateStr)
                    ->count('votes.id');

                $reportCount = DB::table('reports')
                    ->join('posts', function ($join) use ($factionVal) {
                        $join->on('reports.reportable_id', '=', 'posts.id')
                             ->where('reports.reportable_type', 'App\Models\Post')
                             ->where('posts.faction', $factionVal);
                    })
                    ->join('boards', 'posts.board_id', '=', 'boards.id')
                    ->where('boards.board_type', '!=', 'playground')
                    ->where('reports.status', 'actioned')
                    ->whereDate('reports.created_at', $dateStr)
                    ->count('reports.id');

                // 활성 사용자: 당일 게시/댓글 작성한 해당 진영 고유 사용자 수 (놀이터 제외)
                $activeUserCount = DB::table('users')
                    ->where('political_type', $factionVal)
                    ->where('status', 'active')
                    ->whereExists(function ($q) use ($dateStr) {
                        $q->selectRaw('1')
                          ->from('posts')
                          ->join('boards', 'posts.board_id', '=', 'boards.id')
                          ->where('boards.board_type', '!=', 'playground')
                          ->whereColumn('posts.user_id', 'users.id')
                          ->whereDate('posts.created_at', $dateStr)
                          ->whereNull('posts.deleted_at')
                          ->unionAll(
                              DB::table('comments')
                                ->selectRaw('1')
                                ->join('posts as cp', 'comments.post_id', '=', 'cp.id')
                                ->join('boards as cb', 'cp.board_id', '=', 'cb.id')
                                ->where('cb.board_type', '!=', 'playground')
                                ->whereColumn('comments.user_id', 'users.id')
                                ->whereDate('comments.created_at', $dateStr)
                                ->whereNull('comments.deleted_at')
                          );
                    })
                    ->count();

                $newUserCount = DB::table('users')
                    ->where('political_type', $factionVal)
                    ->whereDate('created_at', $dateStr)
                    ->count();

                // 점수 계산
                $rawScore = ($postCount    * ($weights['post']      ?? 3.00))
                          + ($commentCount  * ($weights['comment']   ?? 1.00))
                          + ($voteUpCount   * ($weights['vote_up']   ?? 2.00))
                          - ($voteDownCount * abs($weights['vote_down'] ?? 0.50))
                          - ($reportCount   * abs($weights['report']    ?? 5.00));

                $normalizedScore = $rawScore / max($activeUserCount, 1);

                // Upsert
                FactionDailyStat::updateOrCreate(
                    ['faction_type' => $factionVal, 'stat_date' => $dateStr],
                    [
                        'post_count'        => $postCount,
                        'comment_count'     => $commentCount,
                        'vote_up_count'     => $voteUpCount,
                        'vote_down_count'   => $voteDownCount,
                        'report_count'      => $reportCount,
                        'active_user_count' => $activeUserCount,
                        'new_user_count'    => $newUserCount,
                        'raw_score'         => round($rawScore, 4),
                        'normalized_score'  => round($normalizedScore, 6),
                        'calculated_at'     => now(),
                    ]
                );
            }

            // 순위 갱신
            $this->updateDailyRanks($dateStr);
        });

        // 실시간 캐시 무효화
        Redis::del(self::REALTIME_KEY);
    }

    // -------------------------------------------------------------------------
    // 내부 헬퍼
    // -------------------------------------------------------------------------

    /**
     * 특정 날짜의 진영 순위를 업데이트.
     */
    private function updateDailyRanks(string $dateStr): void
    {
        $stats = FactionDailyStat::where('stat_date', $dateStr)
            ->orderBy('normalized_score', 'desc')
            ->get();

        foreach ($stats as $rank => $stat) {
            $stat->update(['rank' => $rank + 1]);
        }
    }

    /**
     * 오늘의 실시간 점수를 DB 활동 데이터로 계산.
     *
     * @return array<string, float>
     */
    private function calculateTodayScores(): array
    {
        $weights = ScoreWeight::getCachedWeights();
        $today   = now()->toDateString();
        $scores  = [];

        foreach (FactionType::cases() as $faction) {
            $factionVal = $faction->value;

            // 놀이터 게시판 제외
            $postCount = DB::table('posts')
                ->join('boards', 'posts.board_id', '=', 'boards.id')
                ->where('boards.board_type', '!=', 'playground')
                ->where('posts.faction', $factionVal)
                ->whereDate('posts.created_at', $today)
                ->whereNull('posts.deleted_at')
                ->count();

            $commentCount = DB::table('comments')
                ->join('posts', 'comments.post_id', '=', 'posts.id')
                ->join('boards', 'posts.board_id', '=', 'boards.id')
                ->where('boards.board_type', '!=', 'playground')
                ->where('comments.faction', $factionVal)
                ->whereDate('comments.created_at', $today)
                ->whereNull('comments.deleted_at')
                ->count();

            $activeUserCount = DB::table('users')
                ->where('political_type', $factionVal)
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->count();

            $rawScore        = ($postCount   * ($weights['post']    ?? 3.00))
                             + ($commentCount * ($weights['comment'] ?? 1.00));
            $normalizedScore = $rawScore / max($activeUserCount, 1);

            $scores[$factionVal] = round($normalizedScore, 6);
        }

        return $scores;
    }
}
