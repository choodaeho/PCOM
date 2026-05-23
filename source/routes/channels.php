<?php

declare(strict_types=1);

use App\Models\Board;
use Illuminate\Support\Facades\Broadcast;

// ═══════════════════════════════════════════════════════════════════════════════
// Laravel Reverb (WebSocket) 채널 정의
//
// 채널 종류:
//   Public  Channel : 인증 불필요 (누구나 구독)
//   Private Channel : auth:sanctum 인증 필요
//   Presence Channel: 인증 + 현재 접속자 목록 공유
// ═══════════════════════════════════════════════════════════════════════════════

// ─────────────────────────────────────────────
// [PUBLIC] 진영 점수 실시간 브로드캐스트
// 모든 페이지 상단 헤더에서 구독
// Event: FactionScoreUpdated
// ─────────────────────────────────────────────
Broadcast::channel('faction-scores', fn () => true);

// ─────────────────────────────────────────────
// [PUBLIC] 실시간 투표 현황
// 전쟁터 상단 투표 위젯에서 구독
// Event: PollVoteUpdated
// ─────────────────────────────────────────────
Broadcast::channel('polls.{pollId}', fn ($user, int $pollId) => true);

// ─────────────────────────────────────────────
// [PRIVATE] 게시글 실시간 댓글 알림
// 게시글 상세 페이지에서 구독 (로그인 필요)
// Event: CommentCreated
// ─────────────────────────────────────────────
Broadcast::channel('posts.{postId}', function ($user, int $postId) {
    // 로그인한 사용자는 모두 구독 가능
    // 아지트 게시글의 경우 진영 확인은 Controller 레이어에서 처리
    return $user !== null;
});

// ─────────────────────────────────────────────
// [PRIVATE] 게시판별 새 게시글 알림
// 게시판 목록 페이지에서 구독
// Event: PostCreated
// ─────────────────────────────────────────────
Broadcast::channel('boards.{boardSlug}', function ($user, string $boardSlug) {
    if ($user === null) {
        return false;
    }

    $board = Board::where('slug', $boardSlug)->first();
    if ($board === null) {
        return false;
    }

    // 아지트 게시판은 해당 진영만 구독 허용
    return $board->isAccessibleBy($user);
});

// ─────────────────────────────────────────────
// [PRIVATE] 개인 알림 채널
// 추천·댓글·신고 결과 알림
// Event: UserNotification
// ─────────────────────────────────────────────
Broadcast::channel('users.{userId}', function ($user, int $userId) {
    return (int) $user->id === $userId;
});

// ─────────────────────────────────────────────
// [PRESENCE] 전쟁터 게시판 접속자 현황
// 동시 접속 진영 분포 시각화용 (Phase 3)
// ─────────────────────────────────────────────
Broadcast::channel('presence.boards.{boardSlug}', function ($user, string $boardSlug) {
    if ($user === null || ! $user->hasCompletedPoliticalTest()) {
        return false;
    }

    return [
        'id'           => $user->id,
        'nickname'     => $user->nickname,
        'faction'      => $user->political_type?->value,
        'faction_emoji'=> $user->factionEmoji(),
    ];
});

// ─────────────────────────────────────────────
// [PRIVATE] 관리자 알림 채널
// 신고 접수·처리 현황 실시간 알림
// ─────────────────────────────────────────────
Broadcast::channel('admin.notifications', function ($user) {
    return $user?->isAdmin() === true;
});
