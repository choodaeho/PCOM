<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * 프로필 페이지 (최근 게시글 + 댓글 + 활동 통계).
     */
    public function index(Request $request): Response
    {
        $user = $request->user()->loadCount(['posts', 'comments']);

        $recentPosts = $user->posts()
            ->with('board:id,name,slug')
            ->latest()
            ->limit(10)
            ->get(['id', 'title', 'vote_up_count', 'comment_count', 'created_at', 'board_id']);

        $recentComments = $user->comments()
            ->with('post:id,title')
            ->latest()
            ->limit(10)
            ->get(['id', 'content', 'created_at', 'post_id']);

        return Inertia::render('Profile/Index', [
            'recentPosts'    => $recentPosts,
            'recentComments' => $recentComments,
            'stats'          => [
                'post_count'    => $user->posts_count,
                'comment_count' => $user->comments_count,
                'vote_up_count' => $user->posts()->sum('vote_up_count'),
            ],
        ]);
    }

    /**
     * 프로필 수정 페이지.
     */
    public function edit(): Response
    {
        return Inertia::render('Profile/Edit');
    }

    /**
     * 프로필 업데이트 처리.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $rules = [
            'nickname' => [
                'required',
                'string',
                'min:2',
                'max:20',
                'regex:/^[가-힣a-zA-Z0-9_]+$/u',
                'unique:users,nickname,' . $user->id,
            ],
        ];

        // 비밀번호 변경 요청이 있을 때만 검증
        if ($request->filled('password')) {
            $rules['current_password'] = ['required', 'current_password'];
            $rules['password'] = ['required', Password::min(8)->letters()->numbers(), 'confirmed'];
        }

        $validated = $request->validate($rules);

        $user->nickname = $validated['nickname'];

        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('profile.index')
            ->with('success', '프로필이 업데이트되었습니다.');
    }
}
