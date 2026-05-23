<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * GET /api/v1/auth/me
     *
     * 현재 로그인한 사용자 정보 반환.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return response()->json([
            'user' => $this->formatUser($user),
        ]);
    }

    /**
     * GET /api/v1/users/me/profile
     *
     * 내 프로필 상세 조회.
     */
    public function profile(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return response()->json([
            'user' => array_merge($this->formatUser($user), [
                'manner_score'   => $user->manner_score,
                'created_at'     => $user->created_at?->toIso8601String(),
                'post_count'     => $user->posts()->where('status', 'published')->count(),
                'comment_count'  => $user->comments()->count(),
            ]),
        ]);
    }

    /**
     * PUT /api/v1/users/me/profile
     *
     * 닉네임 변경.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $validated = $request->validate([
            'nickname' => [
                'required', 'string', 'min:2', 'max:50',
                'unique:users,nickname,' . $user->id,
                'regex:/^[가-힣a-zA-Z0-9_]+$/',
            ],
        ]);

        $user->update(['nickname' => $validated['nickname']]);

        return response()->json([
            'message' => '닉네임이 변경되었습니다.',
            'user'    => $this->formatUser($user->fresh()),
        ]);
    }

    /**
     * PUT /api/v1/users/me/profile/password
     *
     * 비밀번호 변경.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['현재 비밀번호가 올바르지 않습니다.'],
            ]);
        }

        $user->update(['password' => Hash::make($request->password)]);

        // 현재 토큰 외 모두 폐기 (다른 기기 로그아웃)
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        return response()->json(['message' => '비밀번호가 변경되었습니다.']);
    }

    /**
     * DELETE /api/v1/users/me/profile
     *
     * 회원 탈퇴 (soft delete).
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['비밀번호가 올바르지 않습니다.'],
            ]);
        }

        // 전체 토큰 폐기 후 soft delete
        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => '회원 탈퇴가 완료되었습니다.']);
    }

    /**
     * GET /api/v1/users/me/activity/posts
     *
     * 내가 작성한 게시글 목록.
     */
    public function myPosts(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $posts = $user->posts()
            ->where('status', 'published')
            ->with('board:id,name,slug')
            ->latest()
            ->paginate(20);

        return response()->json($posts);
    }

    /**
     * GET /api/v1/users/me/activity/comments
     *
     * 내가 작성한 댓글 목록.
     */
    public function myComments(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $comments = $user->comments()
            ->with('post:id,title,board_id', 'post.board:id,name,slug')
            ->latest()
            ->paginate(20);

        return response()->json($comments);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * 공통 사용자 정보 포맷.
     *
     * @param \App\Models\User $user
     * @return array<string, mixed>
     */
    private function formatUser(\App\Models\User $user): array
    {
        return [
            'id'             => $user->id,
            'email'          => $user->email,
            'nickname'       => $user->nickname,
            'political_type' => $user->political_type?->value,
            'faction_emoji'  => $user->factionEmoji(),
            'is_admin'       => $user->is_admin,
            'test_completed' => $user->hasCompletedPoliticalTest(),
            'email_verified' => $user->email_verified_at !== null,
            'status'         => $user->status?->value,
        ];
    }
}
