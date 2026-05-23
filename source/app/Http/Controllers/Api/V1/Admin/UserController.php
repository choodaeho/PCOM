<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * GET /api/v1/admin/users
     *
     * 사용자 목록 (검색: email, nickname, faction, status).
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::withTrashed()
            ->when($request->query('search'), fn ($q, $s) =>
                $q->where('email', 'ilike', "%{$s}%")
                  ->orWhere('nickname', 'ilike', "%{$s}%"))
            ->when($request->query('faction'), fn ($q, $f) => $q->where('political_type', $f))
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('created_at')
            ->paginate(30);

        return response()->json($users);
    }

    /**
     * GET /api/v1/admin/users/{user}
     */
    public function show(User $user): JsonResponse
    {
        return response()->json(
            $user->load(['socialAccounts', 'latestTestSession'])
                 ->append(['posts_count', 'comments_count'])
        );
    }

    /**
     * POST /api/v1/admin/users/{user}/suspend
     *
     * 일시 정지.
     * Body: { "days": 7, "reason": "욕설 반복" }
     */
    public function suspend(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'days'   => ['required', 'integer', 'min:1', 'max:365'],
            'reason' => ['required', 'string', 'max:200'],
        ]);

        $until = now()->addDays($validated['days']);

        $user->update([
            'status'          => 'suspended',
            'suspended_until' => $until,
        ]);

        AdminActionLog::record(
            $request->user()->id,
            'user.suspend',
            $user,
            [
                'before'          => ['status' => 'active'],
                'after'           => ['status' => 'suspended'],
                'reason'          => $validated['reason'],
                'duration_days'   => $validated['days'],
                'suspended_until' => $until->toIso8601String(),
            ]
        );

        return response()->json([
            'message'         => "{$validated['days']}일간 정지 처리되었습니다.",
            'suspended_until' => $until->toIso8601String(),
        ]);
    }

    /**
     * POST /api/v1/admin/users/{user}/ban
     *
     * 영구 차단.
     */
    public function ban(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:200'],
        ]);

        $user->update(['status' => 'banned']);

        AdminActionLog::record(
            $request->user()->id,
            'user.ban',
            $user,
            ['reason' => $validated['reason']]
        );

        return response()->json(['message' => '영구 차단 처리되었습니다.']);
    }

    /**
     * POST /api/v1/admin/users/{user}/activate
     *
     * 계정 활성화 (정지 해제 또는 차단 해제).
     */
    public function activate(Request $request, User $user): JsonResponse
    {
        $user->update([
            'status'          => 'active',
            'suspended_until' => null,
        ]);

        AdminActionLog::record($request->user()->id, 'user.activate', $user);

        return response()->json(['message' => '계정이 활성화되었습니다.']);
    }

    /**
     * GET /api/v1/admin/users/stats/overview
     *
     * 진영별 사용자 통계.
     */
    public function statsOverview(): JsonResponse
    {
        $stats = User::where('status', 'active')
            ->selectRaw('political_type, COUNT(*) as count')
            ->groupBy('political_type')
            ->pluck('count', 'political_type');

        return response()->json([
            'total'        => User::count(),
            'active'       => User::where('status', 'active')->count(),
            'pending'      => User::where('status', 'pending')->count(),
            'suspended'    => User::where('status', 'suspended')->count(),
            'banned'       => User::where('status', 'banned')->count(),
            'by_faction'   => $stats,
        ]);
    }
}
