<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\FactionType;
use App\Jobs\GenerateAIPostJob;
use App\Models\AutoContentConfig;
use App\Models\AutoContentRun;
use App\Models\Board;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * AI 일일 콘텐츠 자동 생성 커맨드
 *
 * 스케줄: 매일 05:50 (routes/console.php)
 * 수동 실행: php artisan polit:generate-daily-content
 *
 * 동작:
 *  - 진영별 test 계정으로 오전 6시~자정 사이 게시글/댓글 Job 디스패치
 *  - Gemini free tier: 15 RPM / 1M tokens/day 범위 내
 *  - 300 posts + 500 comments = 800 Jobs / 1080분 = 0.74 Jobs/min (safe)
 *  - AutoContentRun 레코드로 실행 이력 추적
 */
class GenerateDailyContent extends Command
{
    protected $signature = 'polit:generate-daily-content
                            {--date=         : 대상일 YYYY-MM-DD (기본: 오늘)}
                            {--dry-run       : Job 디스패치 없이 계획만 출력}
                            {--force         : is_enabled=false 여도 강제 실행}
                            {--run-type=     : scheduled|manual (기본: scheduled)}
                            {--triggered-by= : 수동 실행 시 관리자 user_id}';

    protected $description = 'AI(Gemini)로 일일 게시글·댓글을 생성하고 큐 Job으로 분산 디스패치합니다.';

    public function handle(): int
    {
        $config = AutoContentConfig::getInstance();

        if (! $config->is_enabled && ! $this->option('force')) {
            $this->warn('⚠️  AI 자동 생성이 비활성화 상태입니다. --force 옵션으로 강제 실행하세요.');
            return Command::FAILURE;
        }

        if (empty($config->gemini_api_key)) {
            $this->error('❌ Gemini API 키가 설정되지 않았습니다. 관리자 패널에서 설정하세요.');
            return Command::FAILURE;
        }

        $dateStr = $this->option('date');
        $date    = $dateStr ? Carbon::parse($dateStr) : Carbon::today();
        $isDry   = (bool) $this->option('dry-run');

        $runType = $this->option('run-type') ?: 'scheduled';
        if ($isDry) {
            $runType = 'dry_run';
        }

        $triggeredBy = $this->option('triggered-by')
            ? (int) $this->option('triggered-by')
            : null;

        $this->info("🤖 AI 일일 콘텐츠 생성 시작 [{$date->toDateString()}]" . ($isDry ? ' (DRY RUN)' : ''));

        // ─────────────────────────────────────────────
        // AutoContentRun 레코드 생성 (이력 추적)
        // ─────────────────────────────────────────────
        $run = null;
        if (! $isDry) {
            $run = AutoContentRun::create([
                'run_date'    => $date->toDateString(),
                'run_type'    => $runType,
                'triggered_by'=> $triggeredBy,
                'status'      => 'running',
                'started_at'  => now(),
            ]);
            $this->line("   📋 실행 이력 ID: #{$run->id}");
        }

        // ─────────────────────────────────────────────
        // 1. 진영별 테스트 계정 로드
        // ─────────────────────────────────────────────
        $factions       = FactionType::cases();
        $usersByFaction = [];

        foreach ($factions as $factionEnum) {
            $faction = $factionEnum->value;
            $users   = User::where('user_type', 'test')
                ->where('political_type', $faction)
                ->where('status', 'active')
                ->pluck('id')
                ->toArray();

            if (empty($users)) {
                $this->warn("⚠️  [{$faction}] 테스트 계정 없음 — 건너뜀");
                continue;
            }
            $usersByFaction[$faction] = $users;
            $this->line("   [{$faction}] 계정 " . count($users) . "개");
        }

        if (empty($usersByFaction)) {
            $this->error('❌ 가용 테스트 계정이 없습니다.');
            if ($run) {
                $run->update(['status' => 'failed', 'notes' => '가용 테스트 계정 없음']);
            }
            return Command::FAILURE;
        }

        // ─────────────────────────────────────────────
        // 2. 진영별 타겟 게시판 로드
        // ─────────────────────────────────────────────
        $targetBoards    = $config->target_boards ?? AutoContentConfig::defaultTargetBoards();
        $boardsByFaction = [];

        foreach ($usersByFaction as $faction => $_) {
            $slugs  = $targetBoards[$faction] ?? [];
            $boards = Board::whereIn('slug', $slugs)->where('is_active', true)->get();

            if ($boards->isEmpty()) {
                // fallback: 전쟁터 게시판
                $boards = Board::where('board_type', 'battle')->where('is_active', true)->limit(2)->get();
            }
            $boardsByFaction[$faction] = $boards;
        }

        // ─────────────────────────────────────────────
        // 3. 시간 분배 계산
        //    start_hour~end_hour (기본 6~24) = 1080분
        // ─────────────────────────────────────────────
        $startMinutes  = $config->start_hour * 60;
        $endMinutes    = $config->end_hour   * 60; // 24*60 = 1440
        $windowMinutes = $endMinutes - $startMinutes; // 1080

        $postsPerFaction = $config->posts_per_faction; // 100
        $totalPosts      = $postsPerFaction * count($usersByFaction); // 300

        // 진영 교차 배열로 고른 분포 (con→mod→prog→con→...)
        $postSlots = $this->buildPostSlots($usersByFaction, $postsPerFaction);

        $intervalMinutes    = $windowMinutes / max($totalPosts, 1); // 3.6분/글
        $apiKey             = $config->gemini_api_key;

        $postsDispatched    = 0;
        $commentsDispatched = 0;

        $this->info("📋 게시글 {$totalPosts}개, 인터벌 " . round($intervalMinutes, 1) . "분/글");
        $bar = $this->output->createProgressBar($totalPosts);
        $bar->start();

        // ─────────────────────────────────────────────
        // 4. Job 디스패치
        // ─────────────────────────────────────────────
        foreach ($postSlots as $index => $slot) {
            $faction = $slot['faction'];

            // 댓글 작성자: 다른 진영 테스트 계정 랜덤 선택
            $commentUserIds = $this->pickCommentUsers(
                $usersByFaction,
                $faction,
                random_int($config->comments_per_post_min, $config->comments_per_post_max)
            );

            // 포스트 발행 지연(분): 기준 시각 = 오늘 start_hour
            $baseDelay = (int) round($index * $intervalMinutes);
            $jitter    = random_int(-1, 1); // ±1분 랜덤
            $postDelay = max(0, $baseDelay + $jitter);

            $dispatchAt = $date->copy()->addMinutes($startMinutes + $postDelay);

            // 댓글은 포스트 발행 5~20분 후
            $commentDelayMin = 5;
            $commentDelayMax = 20;

            if (! $isDry) {
                GenerateAIPostJob::dispatch(
                    $slot['user_id'],
                    $slot['board']->id,
                    $faction,
                    $slot['topic'],
                    $apiKey,
                    $config->pixabay_api_key ?? '',
                    (bool) ($config->use_grounding    ?? true),
                    (bool) ($config->include_images   ?? true),
                    (bool) ($config->include_news_links ?? true),
                    (bool) ($config->include_youtube  ?? true),
                    count($commentUserIds),
                    $commentDelayMin,
                    $commentDelayMax,
                    $commentUserIds,
                    $run?->id,                          // run_id
                    $dispatchAt->toISOString(),         // scheduledAt
                )->delay($dispatchAt);
            }

            $postsDispatched++;
            $commentsDispatched += count($commentUserIds);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // ─────────────────────────────────────────────
        // 5. 이력 저장
        // ─────────────────────────────────────────────
        if (! $isDry) {
            $config->update([
                'last_run_at'    => now(),
                'last_run_stats' => [
                    'target_date'         => $date->toDateString(),
                    'posts_dispatched'    => $postsDispatched,
                    'comments_dispatched' => $commentsDispatched,
                    'dry_run'             => false,
                    'run_id'              => $run?->id,
                ],
            ]);

            $run?->update([
                'status'              => 'completed',
                'completed_at'        => now(),
                'posts_dispatched'    => $postsDispatched,
                'comments_dispatched' => $commentsDispatched,
            ]);
        }

        $this->info("✅ 완료: 게시글 {$postsDispatched}개, 댓글 {$commentsDispatched}개 Job 예약"
            . ($isDry ? ' (DRY RUN — 실제 디스패치 없음)' : '')
            . ($run ? " (이력 #{$run->id})" : ''));

        Log::info('[GenerateDailyContent] 완료', [
            'date'     => $date->toDateString(),
            'posts'    => $postsDispatched,
            'comments' => $commentsDispatched,
            'dry_run'  => $isDry,
            'run_id'   => $run?->id,
        ]);

        return Command::SUCCESS;
    }

    /**
     * 진영 교차 순서로 포스트 슬롯 배열 생성
     * [con, mod, pro, con, mod, pro, ...]
     *
     * @return array<int, array{faction: string, user_id: int, board: Board, topic: string}>
     */
    private function buildPostSlots(array $usersByFaction, int $postsPerFaction): array
    {
        $config       = AutoContentConfig::getInstance();
        $topicsMap    = $config->topics ?? AutoContentConfig::defaultTopics();
        $targetBoards = $config->target_boards ?? AutoContentConfig::defaultTargetBoards();

        $factions = array_keys($usersByFaction);
        $slots    = [];

        // 진영별 커서 (어떤 유저를 쓸지 순환)
        $userCursor  = array_fill_keys($factions, 0);
        $topicCursor = array_fill_keys($factions, 0);

        $maxRound = $postsPerFaction; // 각 진영 최대 postsPerFaction 번 반복
        for ($round = 0; $round < $maxRound; $round++) {
            foreach ($factions as $faction) {
                $users  = $usersByFaction[$faction];
                $topics = $topicsMap[$faction] ?? ["정치 이슈 {$round}"];
                $slugs  = $targetBoards[$faction] ?? [];
                $boards = Board::whereIn('slug', $slugs)->where('is_active', true)->get();
                if ($boards->isEmpty()) {
                    $boards = Board::where('board_type', 'battle')->where('is_active', true)->limit(2)->get();
                }

                $userId = $users[$userCursor[$faction] % count($users)];
                $topic  = $topics[$topicCursor[$faction] % count($topics)];
                $board  = $boards[$round % $boards->count()];

                $slots[] = [
                    'faction' => $faction,
                    'user_id' => $userId,
                    'board'   => $board,
                    'topic'   => $topic,
                ];

                $userCursor[$faction]++;
                $topicCursor[$faction]++;
            }
        }

        return $slots;
    }

    /**
     * 댓글 작성자 무작위 선택 (다른 진영 계정 우선)
     *
     * @return int[]
     */
    private function pickCommentUsers(array $usersByFaction, string $postFaction, int $count): array
    {
        $pool = [];
        foreach ($usersByFaction as $faction => $ids) {
            // 다른 진영 계정을 우선 2배 가중치로 추가
            $weight = ($faction !== $postFaction) ? 2 : 1;
            for ($w = 0; $w < $weight; $w++) {
                $pool = array_merge($pool, $ids);
            }
        }

        shuffle($pool);
        $pool = array_unique($pool);

        return array_slice($pool, 0, $count);
    }
}
