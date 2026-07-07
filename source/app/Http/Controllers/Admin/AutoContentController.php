<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AutoContentConfig;
use App\Models\AutoContentRun;
use App\Models\AutoContentRunEntry;
use App\Models\Board;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use Inertia\Inertia;
use Inertia\Response;

class AutoContentController extends Controller
{
    /** 관리 페이지 */
    public function index(): Response
    {
        $config = AutoContentConfig::getInstance();

        // 게시판 목록 (체크박스용)
        $boards = Board::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'board_type']);

        return Inertia::render('Admin/AutoContent/Index', [
            'config' => [
                'gemini_api_key'        => $config->gemini_api_key
                    ? '***' . substr($config->gemini_api_key, -4)
                    : '',
                'gemini_api_key_set'    => ! empty($config->gemini_api_key),
                'pixabay_api_key'       => $config->pixabay_api_key
                    ? '***' . substr($config->pixabay_api_key, -4)
                    : '',
                'pixabay_api_key_set'   => ! empty($config->pixabay_api_key),
                'is_enabled'            => $config->is_enabled,
                'posts_per_faction'     => $config->posts_per_faction,
                'comments_per_post_min' => $config->comments_per_post_min,
                'comments_per_post_max' => $config->comments_per_post_max,
                'start_hour'            => $config->start_hour,
                'end_hour'              => $config->end_hour,
                'include_images'        => $config->include_images  ?? true,
                'include_news_links'    => $config->include_news_links ?? true,
                'include_youtube'       => $config->include_youtube ?? true,
                'use_grounding'         => $config->use_grounding   ?? true,
                'target_boards'         => $config->target_boards ?? AutoContentConfig::defaultTargetBoards(),
                'topics'                => $config->topics ?? AutoContentConfig::defaultTopics(),
                'last_run_at'           => $config->last_run_at?->toISOString(),
                'last_run_stats'        => $config->last_run_stats,
                'estimated_daily_posts' => $config->posts_per_faction * 3,
                'estimated_daily_comments' => $config->estimatedDailyComments(),
            ],
            'boards' => $boards,
        ]);
    }

    /** 설정 저장 */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'gemini_api_key'        => ['nullable', 'string', 'max:255'],
            'pixabay_api_key'       => ['nullable', 'string', 'max:255'],
            'is_enabled'            => ['required', 'boolean'],
            'posts_per_faction'     => ['required', 'integer', 'min:1', 'max:500'],
            'comments_per_post_min' => ['required', 'integer', 'min:0', 'max:10'],
            'comments_per_post_max' => ['required', 'integer', 'min:0', 'max:10'],
            'start_hour'            => ['required', 'integer', 'min:0', 'max:23'],
            'end_hour'              => ['required', 'integer', 'min:1', 'max:24'],
            'include_images'        => ['boolean'],
            'include_news_links'    => ['boolean'],
            'include_youtube'       => ['boolean'],
            'use_grounding'         => ['boolean'],
            'target_boards'         => ['nullable', 'array'],
            'target_boards.*'       => ['nullable', 'array'],
            'topics'                => ['nullable', 'array'],
            'topics.*'              => ['nullable', 'array'],
        ]);

        $config = AutoContentConfig::getInstance();

        $updateData = [
            'is_enabled'            => $validated['is_enabled'],
            'posts_per_faction'     => $validated['posts_per_faction'],
            'comments_per_post_min' => min($validated['comments_per_post_min'], $validated['comments_per_post_max']),
            'comments_per_post_max' => max($validated['comments_per_post_min'], $validated['comments_per_post_max']),
            'start_hour'            => $validated['start_hour'],
            'end_hour'              => $validated['end_hour'],
            'include_images'        => (bool) ($validated['include_images'] ?? true),
            'include_news_links'    => (bool) ($validated['include_news_links'] ?? true),
            'include_youtube'       => (bool) ($validated['include_youtube'] ?? true),
            'use_grounding'         => (bool) ($validated['use_grounding'] ?? true),
        ];

        // API 키: 빈 문자열이거나 마스킹된 값이면 기존 유지, 새 값이면 업데이트
        if (! empty($validated['gemini_api_key'])
            && ! str_starts_with($validated['gemini_api_key'], '***')
        ) {
            $updateData['gemini_api_key'] = $validated['gemini_api_key'];
        }

        if (! empty($validated['pixabay_api_key'])
            && ! str_starts_with($validated['pixabay_api_key'], '***')
        ) {
            $updateData['pixabay_api_key'] = $validated['pixabay_api_key'];
        }

        if (isset($validated['target_boards'])) {
            $updateData['target_boards'] = $validated['target_boards'];
        }

        if (isset($validated['topics'])) {
            $updateData['topics'] = $validated['topics'];
        }

        $config->update($updateData);

        return back()->with('success', '설정이 저장되었습니다.');
    }

    /**
     * 즉시 실행 (관리자가 버튼 클릭)
     * 실제 artisan 커맨드를 큐에서 실행
     */
    public function runNow(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'dry_run' => ['boolean'],
            'date'    => ['nullable', 'date_format:Y-m-d'],
        ]);

        $config = AutoContentConfig::getInstance();

        if (empty($config->gemini_api_key)) {
            return response()->json([
                'success' => false,
                'message' => 'Gemini API 키를 먼저 설정하세요.',
            ], 422);
        }

        $args = [
            '--force'        => true,
            '--run-type'     => 'manual',
            '--triggered-by' => (string) Auth::id(),
        ];
        if ($validated['dry_run'] ?? false) {
            $args['--dry-run'] = true;
        }
        if (! empty($validated['date'])) {
            $args['--date'] = $validated['date'];
        }

        // 비동기 큐 실행 (관리자 패널이 blocking되지 않도록)
        Artisan::queue('polit:generate-daily-content', $args);

        return response()->json([
            'success' => true,
            'message' => ($validated['dry_run'] ?? false)
                ? 'DRY RUN Job이 큐에 등록되었습니다. 로그를 확인하세요.'
                : 'AI 콘텐츠 생성 Job이 큐에 등록되었습니다. 06:00~24:00 사이 순차 발행됩니다.',
        ]);
    }

    /** 마지막 실행 통계 새로고침 (AJAX) */
    public function stats(): JsonResponse
    {
        $config = AutoContentConfig::getInstance();

        return response()->json([
            'last_run_at'    => $config->last_run_at?->toISOString(),
            'last_run_stats' => $config->last_run_stats,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // 로그 관련
    // ──────────────────────────────────────────────────────────────

    /**
     * 특정 실행 중지 (AJAX POST)
     */
    public function stopRun(AutoContentRun $run): JsonResponse
    {
        if (! $run->isStoppable()) {
            return response()->json([
                'success' => false,
                'message' => '이미 중지되었거나 중지할 수 없는 상태입니다.',
            ], 422);
        }

        $run->stop();

        // Redis 큐에 쌓인 미처리 Job 전체 삭제
        // (개별 run_id 선택 삭제 불가 → default 큐 전체 비움)
        try {
            Queue::connection('redis')->clear('default');
        } catch (\Throwable) {
            // 큐 비우기 실패해도 중지 자체는 성공으로 처리
        }

        return response()->json([
            'success' => true,
            'message' => '중지 완료. 대기 중인 Job을 모두 삭제했습니다.',
            'status'  => 'stopped',
        ]);
    }

    /**
     * AI 생성 로그 목록 페이지
     */
    public function logs(Request $request): Response
    {
        $runs = AutoContentRun::with('triggeredBy:id,nickname')
            ->orderByDesc('started_at')
            ->paginate(20)
            ->through(fn (AutoContentRun $run) => [
                'id'                  => $run->id,
                'run_date'            => $run->run_date->toDateString(),
                'run_type'            => $run->run_type,
                'status'              => $run->status,
                'triggered_by'        => $run->triggeredBy?->nickname,
                'posts_dispatched'    => $run->posts_dispatched,
                'posts_succeeded'     => $run->posts_succeeded,
                'posts_failed'        => $run->posts_failed,
                'comments_dispatched' => $run->comments_dispatched,
                'comments_succeeded'  => $run->comments_succeeded,
                'comments_failed'     => $run->comments_failed,
                'total_errors'        => $run->total_errors,
                'post_success_rate'   => $run->post_success_rate,
                'started_at'          => $run->started_at->toISOString(),
                'completed_at'        => $run->completed_at?->toISOString(),
                'last_activity_at'    => $run->last_activity_at?->toISOString(),
                'elapsed_seconds'     => $run->elapsed_seconds,
                'notes'               => $run->notes,
            ]);

        // 요약 통계
        $today = now()->toDateString();

        $todayRun = AutoContentRun::where('run_date', $today)
            ->whereIn('run_type', ['scheduled', 'manual'])
            ->orderByDesc('started_at')
            ->first();

        $summary = [
            'today_posts_succeeded'    => $todayRun?->posts_succeeded    ?? 0,
            'today_posts_failed'       => $todayRun?->posts_failed       ?? 0,
            'today_posts_dispatched'   => $todayRun?->posts_dispatched   ?? 0,
            'today_comments_succeeded' => $todayRun?->comments_succeeded ?? 0,
            'today_comments_failed'    => $todayRun?->comments_failed    ?? 0,
            'today_run_id'             => $todayRun?->id,
            'total_runs_7days'         => AutoContentRun::where('started_at', '>=', now()->subDays(7))->count(),
            'total_errors_7days'       => AutoContentRun::where('started_at', '>=', now()->subDays(7))
                ->selectRaw('COALESCE(SUM(posts_failed + comments_failed), 0) as total')
                ->value('total') ?? 0,
        ];

        return Inertia::render('Admin/AutoContent/Logs', [
            'runs'    => $runs,
            'summary' => $summary,
        ]);
    }

    /**
     * 실행 상세 페이지 (Inertia)
     */
    public function logShowPage(AutoContentRun $run): Response
    {
        $run->load('triggeredBy:id,nickname');

        // 진영별 / 상태별 집계
        $factionStats = $run->entries()
            ->selectRaw("faction, entry_type, status, COUNT(*) as cnt")
            ->groupBy('faction', 'entry_type', 'status')
            ->get();

        // 오류 로그 (최대 200개)
        $errorEntries = $run->entries()
            ->where('status', 'failed')
            ->orderBy('executed_at')
            ->limit(200)
            ->get()
            ->map(fn (AutoContentRunEntry $e) => [
                'id'            => $e->id,
                'entry_type'    => $e->entry_type,
                'faction'       => $e->faction,
                'faction_label' => $e->faction_label,
                'nickname'      => $e->nickname,
                'board_name'    => $e->board_name,
                'parent_post_id'=> $e->parent_post_id,
                'topic'         => $e->topic,
                'title'         => $e->title,
                'error_message' => $e->error_message,
                'duration_fmt'  => $e->duration_formatted,
                'executed_at'   => $e->executed_at?->toISOString(),
            ]);

        return Inertia::render('Admin/AutoContent/Logs/Show', [
            'run' => [
                'id'                  => $run->id,
                'run_date'            => $run->run_date->toDateString(),
                'run_type'            => $run->run_type,
                'status'              => $run->status,
                'is_stopped'          => $run->is_stopped,
                'stopped_at'          => $run->stopped_at?->toISOString(),
                'triggered_by'        => $run->triggeredBy?->nickname,
                'posts_dispatched'    => $run->posts_dispatched,
                'posts_succeeded'     => $run->posts_succeeded,
                'posts_failed'        => $run->posts_failed,
                'posts_skipped'       => $run->posts_skipped,
                'comments_dispatched' => $run->comments_dispatched,
                'comments_succeeded'  => $run->comments_succeeded,
                'comments_failed'     => $run->comments_failed,
                'comments_skipped'    => $run->comments_skipped,
                'total_errors'        => $run->total_errors,
                'post_success_rate'   => $run->post_success_rate,
                'comment_success_rate'=> $run->comment_success_rate,
                'elapsed_seconds'     => $run->elapsed_seconds,
                'started_at'          => $run->started_at->toISOString(),
                'completed_at'        => $run->completed_at?->toISOString(),
                'last_activity_at'    => $run->last_activity_at?->toISOString(),
                'notes'               => $run->notes,
                'is_stoppable'        => $run->isStoppable(),
            ],
            'faction_stats' => $factionStats,
            'error_entries' => $errorEntries,
        ]);
    }

    /**
     * 특정 실행의 항목 목록 (AJAX)
     */
    public function logEntries(Request $request, AutoContentRun $run): JsonResponse
    {
        $validated = $request->validate([
            'type'    => ['nullable', 'in:post,comment'],
            'faction' => ['nullable', 'in:conservative,moderate,progressive'],
            'status'  => ['nullable', 'in:success,failed,skipped'],
            'page'    => ['nullable', 'integer', 'min:1'],
        ]);

        $query = $run->entries()->orderByDesc('executed_at');

        if (! empty($validated['type'])) {
            $query->where('entry_type', $validated['type']);
        }
        if (! empty($validated['faction'])) {
            $query->where('faction', $validated['faction']);
        }
        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $entries = $query->paginate(50)->through(fn (AutoContentRunEntry $e) => [
            'id'             => $e->id,
            'entry_type'     => $e->entry_type,
            'faction'        => $e->faction,
            'faction_label'  => $e->faction_label,
            'nickname'       => $e->nickname,
            'board_slug'     => $e->board_slug,
            'board_name'     => $e->board_name,
            'post_id'        => $e->post_id ?? $e->parent_post_id,
            'topic'          => $e->topic,
            'title'          => $e->title,
            'status'         => $e->status,
            'error_message'  => $e->error_message,
            'duration_ms'    => $e->duration_ms,
            'duration_fmt'   => $e->duration_formatted,
            'scheduled_at'   => $e->scheduled_at?->toISOString(),
            'executed_at'    => $e->executed_at?->toISOString(),
        ]);

        // 진영별 / 상태별 집계
        $factionStats = $run->entries()
            ->selectRaw("faction, status, COUNT(*) as cnt")
            ->groupBy('faction', 'status')
            ->get()
            ->groupBy('faction')
            ->map(fn ($rows) => $rows->keyBy('status')->map(fn ($r) => $r->cnt));

        return response()->json([
            'run' => [
                'id'                  => $run->id,
                'run_date'            => $run->run_date->toDateString(),
                'run_type'            => $run->run_type,
                'status'              => $run->status,
                'posts_dispatched'    => $run->posts_dispatched,
                'posts_succeeded'     => $run->posts_succeeded,
                'posts_failed'        => $run->posts_failed,
                'comments_dispatched' => $run->comments_dispatched,
                'comments_succeeded'  => $run->comments_succeeded,
                'comments_failed'     => $run->comments_failed,
                'started_at'          => $run->started_at->toISOString(),
                'completed_at'        => $run->completed_at?->toISOString(),
                'last_activity_at'    => $run->last_activity_at?->toISOString(),
            ],
            'entries'       => $entries,
            'faction_stats' => $factionStats,
        ]);
    }

    /**
     * 오래된 로그 정리 (AJAX DELETE)
     *
     * days = 0 → 전체 삭제
     * days > 0 → N일 이전 로그 삭제
     */
    public function logCleanup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'days' => ['required', 'integer', 'min:0', 'max:365'],
        ]);

        $days  = (int) $validated['days'];
        $query = AutoContentRun::query();

        if ($days > 0) {
            $query->where('started_at', '<', now()->subDays($days));
        }
        // days === 0: 조건 없이 전체 삭제

        $count = $query->count();
        $query->delete(); // auto_content_run_entries는 cascade 삭제

        $label   = $days > 0 ? "{$days}일 이전" : '전체';

        return response()->json([
            'success' => true,
            'deleted' => $count,
            'message' => "{$label} 로그 {$count}건이 삭제되었습니다.",
        ]);
    }
}
