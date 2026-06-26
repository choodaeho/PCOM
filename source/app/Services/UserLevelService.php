<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BoardType;
use App\Enums\PostStatus;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Models\UserBadge;
use Illuminate\Support\Carbon;

class UserLevelService
{
    /**
     * 레벨 정의 (레벨 번호 => [xp, emoji, name])
     *
     * @var array<int, array{xp: int, emoji: string, name: string}>
     */
    /**
     * 게시판 유형별 XP 가중치.
     * 전쟁터(높음) > 아지트(중간) > 놀이터(낮음)
     *
     * @var array<string, array{post: int, comment: int}>
     */
    public const XP_RATES = [
        'battle'     => ['post' => 20, 'comment' => 5],
        'azit'       => ['post' => 10, 'comment' => 3],
        'playground' => ['post' => 5,  'comment' => 2],
        'notice'     => ['post' => 5,  'comment' => 2],
    ];

    /** 받은 추천 1개당 XP */
    public const XP_VOTE_UP = 2;

    public const LEVELS = [
        // ── 입문 (1-5) ──────────────────────────────────────────
        1  => ['xp' => 0,         'emoji' => '🌱', 'name' => '새싹'],
        2  => ['xp' => 100,       'emoji' => '🌿', 'name' => '풀잎'],
        3  => ['xp' => 250,       'emoji' => '📖', 'name' => '견습생'],
        4  => ['xp' => 500,       'emoji' => '🔍', 'name' => '탐색자'],
        5  => ['xp' => 900,       'emoji' => '📰', 'name' => '논객'],
        // ── 성장 (6-10) ─────────────────────────────────────────
        6  => ['xp' => 1_400,     'emoji' => '🎙️', 'name' => '웅변가'],
        7  => ['xp' => 2_100,     'emoji' => '⚡', 'name' => '활동가'],
        8  => ['xp' => 3_000,     'emoji' => '🔥', 'name' => '열혈당원'],
        9  => ['xp' => 4_200,     'emoji' => '📣', 'name' => '대변인'],
        10 => ['xp' => 5_800,     'emoji' => '⚔️', 'name' => '전사'],
        // ── 중견 (11-15) ────────────────────────────────────────
        11 => ['xp' => 8_000,     'emoji' => '🗡️', 'name' => '베테랑'],
        12 => ['xp' => 11_000,    'emoji' => '🛡️', 'name' => '수호자'],
        13 => ['xp' => 15_000,    'emoji' => '🦅', 'name' => '선봉대'],
        14 => ['xp' => 20_000,    'emoji' => '💎', 'name' => '정예'],
        15 => ['xp' => 27_000,    'emoji' => '🌟', 'name' => '스타'],
        // ── 고수 (16-20) ────────────────────────────────────────
        16 => ['xp' => 36_000,    'emoji' => '🔮', 'name' => '전략가'],
        17 => ['xp' => 47_500,    'emoji' => '🦁', 'name' => '진영 리더'],
        18 => ['xp' => 62_000,    'emoji' => '👁️', 'name' => '감시자'],
        19 => ['xp' => 80_000,    'emoji' => '🏆', 'name' => '챔피언'],
        20 => ['xp' => 103_000,   'emoji' => '⭐', 'name' => '슈퍼스타'],
        // ── 엘리트 (21-25) ──────────────────────────────────────
        21 => ['xp' => 132_000,   'emoji' => '🌙', 'name' => '야전대장'],
        22 => ['xp' => 170_000,   'emoji' => '☀️', 'name' => '빛의 전사'],
        23 => ['xp' => 218_000,   'emoji' => '🌊', 'name' => '파도'],
        24 => ['xp' => 280_000,   'emoji' => '🏔️', 'name' => '철옹성'],
        25 => ['xp' => 360_000,   'emoji' => '🦋', 'name' => '각성자'],
        // ── 전설 (26-30) ────────────────────────────────────────
        26 => ['xp' => 462_000,   'emoji' => '🔱', 'name' => '신화'],
        27 => ['xp' => 593_000,   'emoji' => '💫', 'name' => '초월자'],
        28 => ['xp' => 760_000,   'emoji' => '🌌', 'name' => '우주'],
        29 => ['xp' => 975_000,   'emoji' => '🌠', 'name' => '불꽃 전설'],
        30 => ['xp' => 1_250_000, 'emoji' => '👑', 'name' => '폴릿의 신'],
    ];

    /**
     * 배지 정의 (badge_key => [emoji, name, desc])
     *
     * @var array<string, array{emoji: string, name: string, desc: string}>
     */
    public const BADGES = [
        // ── 게시글 ─────────────────────────────────────────────
        'first_post'     => ['emoji' => '🌱', 'name' => '첫 걸음',       'desc' => '게시글 1개 이상 작성'],
        'writer_10'      => ['emoji' => '📝', 'name' => '활발한 작가',   'desc' => '게시글 10개 이상 작성'],
        'writer_50'      => ['emoji' => '✍️', 'name' => '다작가',        'desc' => '게시글 50개 이상 작성'],
        'writer_200'     => ['emoji' => '📚', 'name' => '대작가',        'desc' => '게시글 200개 이상 작성'],
        'writer_500'     => ['emoji' => '🏛️', 'name' => '전설적 작가',   'desc' => '게시글 500개 이상 작성'],
        // ── 댓글 ───────────────────────────────────────────────
        'first_comment'  => ['emoji' => '🗨️', 'name' => '첫 댓글',       'desc' => '댓글 1개 이상 작성'],
        'commenter_10'   => ['emoji' => '💭', 'name' => '수다 시작',     'desc' => '댓글 10개 이상 작성'],
        'commenter_100'  => ['emoji' => '💬', 'name' => '수다쟁이',      'desc' => '댓글 100개 이상 작성'],
        'commenter_500'  => ['emoji' => '🗣️', 'name' => '댓글 마스터',   'desc' => '댓글 500개 이상 작성'],
        // ── 추천 ───────────────────────────────────────────────
        'popular_100'    => ['emoji' => '👍', 'name' => '인기인',        'desc' => '받은 추천 합계 100개 이상'],
        'popular_500'    => ['emoji' => '⭐', 'name' => '스타',          'desc' => '받은 추천 합계 500개 이상'],
        'popular_1000'   => ['emoji' => '🌟', 'name' => '슈퍼스타',      'desc' => '받은 추천 합계 1,000개 이상'],
        'hot_post'       => ['emoji' => '🔥', 'name' => '화제의 글',     'desc' => '단건 게시글 추천 30개 이상'],
        'hot_post_50'    => ['emoji' => '💥', 'name' => '대박 글',       'desc' => '단건 게시글 추천 50개 이상'],
        // ── 전쟁터 ─────────────────────────────────────────────
        'warrior'        => ['emoji' => '⚔️', 'name' => '전사',          'desc' => '전쟁터 게시글 20개 이상'],
        'warrior_50'     => ['emoji' => '🗡️', 'name' => '베테랑 전사',   'desc' => '전쟁터 게시글 50개 이상'],
        'warrior_100'    => ['emoji' => '🏹', 'name' => '전쟁의 신',     'desc' => '전쟁터 게시글 100개 이상'],
        // ── 아지트 ─────────────────────────────────────────────
        'azit_10'        => ['emoji' => '🏠', 'name' => '아지트 단골',   'desc' => '아지트 게시글 10개 이상'],
        'azit_50'        => ['emoji' => '🛡️', 'name' => '아지트 수호자', 'desc' => '아지트 게시글 50개 이상'],
        // ── 놀이터 ─────────────────────────────────────────────
        'playground_10'  => ['emoji' => '🎮', 'name' => '놀이터 단골',   'desc' => '놀이터 게시글 10개 이상'],
        'playground_50'  => ['emoji' => '🎡', 'name' => '놀이터 챔피언', 'desc' => '놀이터 게시글 50개 이상'],
        // ── 매너 ───────────────────────────────────────────────
        'manner_130'     => ['emoji' => '😇', 'name' => '매너리스트',    'desc' => '매너 점수 130점 이상'],
        'manner_150'     => ['emoji' => '🕊️', 'name' => '매너왕',        'desc' => '매너 점수 150점 이상'],
        'manner_200'     => ['emoji' => '🙏', 'name' => '성인군자',      'desc' => '매너 점수 200점 이상'],
        // ── 레벨 ───────────────────────────────────────────────
        'level5'         => ['emoji' => '📰', 'name' => '논객의 길',     'desc' => '레벨 5 달성'],
        'level10'        => ['emoji' => '⚔️', 'name' => '전사의 증명',   'desc' => '레벨 10 달성'],
        'level15'        => ['emoji' => '🌟', 'name' => '스타의 반열',   'desc' => '레벨 15 달성'],
        'level20'        => ['emoji' => '⭐', 'name' => '슈퍼스타',      'desc' => '레벨 20 달성'],
        'level25'        => ['emoji' => '🦋', 'name' => '각성',          'desc' => '레벨 25 달성'],
        'level30'        => ['emoji' => '👑', 'name' => '폴릿의 신',     'desc' => '레벨 30 달성'],
        // ── 특수 ───────────────────────────────────────────────
        'view_star'      => ['emoji' => '👁️', 'name' => '조회 스타',     'desc' => '총 조회수 10,000 이상'],
        'all_rounder'    => ['emoji' => '🌈', 'name' => '올라운더',      'desc' => '아지트·전쟁터·놀이터 모두 게시글 1개 이상'],
    ];

    /**
     * 사용자의 XP를 재계산하고 레벨 및 배지를 동기화합니다.
     */
    public function syncUser(User $user): void
    {
        $xp    = $this->calculateXp($user);
        $level = $this->levelFromXp($xp);

        $user->experience_points = $xp;
        $user->level             = $level;
        $user->save();

        // 배지 체크용 단순 카운트
        $postCount    = $this->publishedPostCount($user);
        $commentCount = (int) $user->comments()->count();
        $totalVotes   = $this->totalReceivedVotes($user);

        $this->checkAndAwardBadges($user, $postCount, $commentCount, $totalVotes, $level);
    }

    /**
     * 게시판 유형별 가중치를 적용해 XP를 계산합니다.
     * 전쟁터 게시글 +20 / 아지트 +10 / 놀이터 +5
     * 전쟁터 댓글  +5  / 아지트  +3 / 놀이터 +2
     * 받은 추천 +2 (게시판 무관)
     */
    public function calculateXp(User $user): int
    {
        $status = PostStatus::Published->value;

        // 게시판 유형별 게시글 수
        $postsByType = Post::where('posts.user_id', $user->id)
            ->where('posts.status', $status)
            ->join('boards', 'posts.board_id', '=', 'boards.id')
            ->selectRaw('boards.board_type, COUNT(*) as cnt')
            ->groupBy('boards.board_type')
            ->pluck('cnt', 'board_type')
            ->toArray();

        // 게시판 유형별 댓글 수
        $commentsByType = Comment::where('comments.user_id', $user->id)
            ->join('posts', 'comments.post_id', '=', 'posts.id')
            ->join('boards', 'posts.board_id', '=', 'boards.id')
            ->selectRaw('boards.board_type, COUNT(*) as cnt')
            ->groupBy('boards.board_type')
            ->pluck('cnt', 'board_type')
            ->toArray();

        $xp = 0;
        foreach (self::XP_RATES as $type => $rates) {
            $xp += ($postsByType[$type]    ?? 0) * $rates['post'];
            $xp += ($commentsByType[$type] ?? 0) * $rates['comment'];
        }
        $xp += $this->totalReceivedVotes($user) * self::XP_VOTE_UP;

        return $xp;
    }

    /**
     * XP로 레벨을 반환합니다.
     */
    public function levelFromXp(int $xp): int
    {
        $level = 1;
        foreach (self::LEVELS as $lvl => $info) {
            if ($xp >= $info['xp']) {
                $level = $lvl;
            }
        }
        return $level;
    }

    /**
     * 레벨 정보를 반환합니다.
     *
     * @return array{emoji: string, name: string, current_xp: int, next_xp: int|null}
     */
    public function levelInfo(int $level): array
    {
        $info    = self::LEVELS[$level] ?? self::LEVELS[1];
        $nextXp  = isset(self::LEVELS[$level + 1]) ? self::LEVELS[$level + 1]['xp'] : null;

        return [
            'emoji'      => $info['emoji'],
            'name'       => $info['name'],
            'current_xp' => $info['xp'],
            'next_xp'    => $nextXp,
        ];
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function publishedPostCount(User $user): int
    {
        return (int) $user->posts()
            ->where('status', PostStatus::Published->value)
            ->count();
    }

    private function totalReceivedVotes(User $user): int
    {
        $postVotes = (int) $user->posts()
            ->where('status', PostStatus::Published->value)
            ->sum('vote_up_count');

        $commentVotes = (int) $user->comments()->sum('vote_up_count');

        return $postVotes + $commentVotes;
    }

    /**
     * 배지 조건을 확인하고 신규 배지를 부여합니다.
     */
    private function checkAndAwardBadges(
        User $user,
        int $postCount,
        int $commentCount,
        int $totalVotes,
        int $level
    ): void {
        // 이미 보유한 배지 키 목록 조회
        $existing = UserBadge::where('user_id', $user->id)
            ->pluck('badge_key')
            ->flip()
            ->toArray();

        $toAward    = [];
        $now        = Carbon::now();
        $manner     = $user->manner_score ?? 0;
        $uid        = $user->id;
        $status     = PostStatus::Published->value;

        // 헬퍼: 배지 등록용 배열 반환
        $entry = fn (string $key): array => [
            'user_id'    => $uid,
            'badge_key'  => $key,
            'awarded_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        // 지연 계산: 전쟁터 / 아지트 / 놀이터 카운트 (필요할 때만 쿼리)
        $battleCount     = null;
        $azitCount       = null;
        $playgroundCount = null;
        $maxVote         = null;
        $totalViews      = null;

        $getBattle = function () use (&$battleCount, $uid, $status): int {
            return $battleCount ??= (int) Post::where('user_id', $uid)
                ->where('status', $status)
                ->whereHas('board', fn ($q) => $q->where('board_type', BoardType::Battle->value))
                ->count();
        };
        $getAzit = function () use (&$azitCount, $uid, $status): int {
            return $azitCount ??= (int) Post::where('user_id', $uid)
                ->where('status', $status)
                ->whereHas('board', fn ($q) => $q->where('board_type', BoardType::Azit->value))
                ->count();
        };
        $getPlayground = function () use (&$playgroundCount, $uid, $status): int {
            return $playgroundCount ??= (int) Post::where('user_id', $uid)
                ->where('status', $status)
                ->whereHas('board', fn ($q) => $q->where('board_type', BoardType::Playground->value))
                ->count();
        };
        $getMaxVote = function () use (&$maxVote, $uid, $status): int {
            return $maxVote ??= (int) Post::where('user_id', $uid)
                ->where('status', $status)
                ->max('vote_up_count');
        };
        $getTotalViews = function () use (&$totalViews, $uid, $status): int {
            return $totalViews ??= (int) Post::where('user_id', $uid)
                ->where('status', $status)
                ->sum('view_count');
        };

        // ── 게시글 ──────────────────────────────────────────────────
        if ($postCount >= 1   && ! isset($existing['first_post']))  { $toAward[] = $entry('first_post'); }
        if ($postCount >= 10  && ! isset($existing['writer_10']))   { $toAward[] = $entry('writer_10'); }
        if ($postCount >= 50  && ! isset($existing['writer_50']))   { $toAward[] = $entry('writer_50'); }
        if ($postCount >= 200 && ! isset($existing['writer_200']))  { $toAward[] = $entry('writer_200'); }
        if ($postCount >= 500 && ! isset($existing['writer_500']))  { $toAward[] = $entry('writer_500'); }

        // ── 댓글 ────────────────────────────────────────────────────
        if ($commentCount >= 1   && ! isset($existing['first_comment']))  { $toAward[] = $entry('first_comment'); }
        if ($commentCount >= 10  && ! isset($existing['commenter_10']))   { $toAward[] = $entry('commenter_10'); }
        if ($commentCount >= 100 && ! isset($existing['commenter_100']))  { $toAward[] = $entry('commenter_100'); }
        if ($commentCount >= 500 && ! isset($existing['commenter_500']))  { $toAward[] = $entry('commenter_500'); }

        // ── 추천 합계 ───────────────────────────────────────────────
        if ($totalVotes >= 100  && ! isset($existing['popular_100']))  { $toAward[] = $entry('popular_100'); }
        if ($totalVotes >= 500  && ! isset($existing['popular_500']))  { $toAward[] = $entry('popular_500'); }
        if ($totalVotes >= 1000 && ! isset($existing['popular_1000'])) { $toAward[] = $entry('popular_1000'); }

        // ── 단건 추천 ───────────────────────────────────────────────
        if (! isset($existing['hot_post']) || ! isset($existing['hot_post_50'])) {
            $mv = $getMaxVote();
            if ($mv >= 30 && ! isset($existing['hot_post']))    { $toAward[] = $entry('hot_post'); }
            if ($mv >= 50 && ! isset($existing['hot_post_50'])) { $toAward[] = $entry('hot_post_50'); }
        }

        // ── 전쟁터 ──────────────────────────────────────────────────
        if (! isset($existing['warrior']) || ! isset($existing['warrior_50']) || ! isset($existing['warrior_100'])) {
            $bc = $getBattle();
            if ($bc >= 20  && ! isset($existing['warrior']))      { $toAward[] = $entry('warrior'); }
            if ($bc >= 50  && ! isset($existing['warrior_50']))   { $toAward[] = $entry('warrior_50'); }
            if ($bc >= 100 && ! isset($existing['warrior_100']))  { $toAward[] = $entry('warrior_100'); }
        }

        // ── 아지트 ──────────────────────────────────────────────────
        if (! isset($existing['azit_10']) || ! isset($existing['azit_50'])) {
            $ac = $getAzit();
            if ($ac >= 10 && ! isset($existing['azit_10'])) { $toAward[] = $entry('azit_10'); }
            if ($ac >= 50 && ! isset($existing['azit_50'])) { $toAward[] = $entry('azit_50'); }
        }

        // ── 놀이터 ──────────────────────────────────────────────────
        if (! isset($existing['playground_10']) || ! isset($existing['playground_50'])) {
            $pc = $getPlayground();
            if ($pc >= 10 && ! isset($existing['playground_10'])) { $toAward[] = $entry('playground_10'); }
            if ($pc >= 50 && ! isset($existing['playground_50'])) { $toAward[] = $entry('playground_50'); }
        }

        // ── 매너 ────────────────────────────────────────────────────
        if ($manner >= 130 && ! isset($existing['manner_130'])) { $toAward[] = $entry('manner_130'); }
        if ($manner >= 150 && ! isset($existing['manner_150'])) { $toAward[] = $entry('manner_150'); }
        if ($manner >= 200 && ! isset($existing['manner_200'])) { $toAward[] = $entry('manner_200'); }

        // ── 레벨 ────────────────────────────────────────────────────
        if ($level >= 5  && ! isset($existing['level5']))  { $toAward[] = $entry('level5'); }
        if ($level >= 10 && ! isset($existing['level10'])) { $toAward[] = $entry('level10'); }
        if ($level >= 15 && ! isset($existing['level15'])) { $toAward[] = $entry('level15'); }
        if ($level >= 20 && ! isset($existing['level20'])) { $toAward[] = $entry('level20'); }
        if ($level >= 25 && ! isset($existing['level25'])) { $toAward[] = $entry('level25'); }
        if ($level >= 30 && ! isset($existing['level30'])) { $toAward[] = $entry('level30'); }

        // ── 특수 ────────────────────────────────────────────────────
        if (! isset($existing['view_star']) && $getTotalViews() >= 10000) {
            $toAward[] = $entry('view_star');
        }

        if (! isset($existing['all_rounder'])) {
            if ($getAzit() >= 1 && $getBattle() >= 1 && $getPlayground() >= 1) {
                $toAward[] = $entry('all_rounder');
            }
        }

        if (! empty($toAward)) {
            UserBadge::insertOrIgnore($toAward);
        }
    }
}
