<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PostController extends Controller
{
    /**
     * GET /api/v1/boards/{board:slug}/posts
     *
     * 게시판 게시글 목록 (페이지네이션).
     * Query params: ?page=1&per_page=20&sort=latest|popular&faction=conservative
     */
    public function index(Request $request, Board $board): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 20), 50);
        $sort    = $request->query('sort', 'latest');
        $faction = $request->query('faction');

        $query = $board->posts()
            ->published()
            ->with(['user:id,nickname,political_type', 'board:id,name,slug']);

        // 진영 필터
        if ($faction) {
            $query->where('faction', $faction);
        }

        // 정렬
        match($sort) {
            'popular' => $query->orderByDesc('vote_up_count')->orderByDesc('created_at'),
            'views'   => $query->orderByDesc('view_count'),
            default   => $query->orderByDesc('is_notice')->orderByDesc('created_at'),
        };

        $posts = $query->paginate($perPage);

        return response()->json([
            'data'       => $posts->items(),
            'meta'       => [
                'current_page' => $posts->currentPage(),
                'last_page'    => $posts->lastPage(),
                'total'        => $posts->total(),
                'per_page'     => $posts->perPage(),
            ],
        ]);
    }

    /**
     * POST /api/v1/boards/{board:slug}/posts
     *
     * 게시글 작성.
     */
    public function store(Request $request, Board $board): JsonResponse
    {
        $validated = $request->validate([
            'title'        => ['required', 'string', 'min:2', 'max:300'],
            'content'      => ['required', 'string', 'min:10', 'max:50000'],
            'is_anonymous' => ['boolean'],
            'attachments'  => ['nullable', 'array', 'max:5'],
            'attachments.*.url'  => ['required', 'url'],
            'attachments.*.name' => ['required', 'string', 'max:200'],
            'attachments.*.type' => ['required', 'in:image,file'],
            'attachments.*.size' => ['required', 'integer'],
        ]);

        $user = $request->user();

        $post = $board->posts()->create([
            ...$validated,
            'user_id'      => $user->id,
            'faction'      => $user->political_type->value, // 작성 당시 진영 스냅샷
            'status'       => 'published',
            'is_anonymous' => $validated['is_anonymous'] ?? false,
        ]);

        return response()->json([
            'message' => '게시글이 등록되었습니다.',
            'post'    => $post->load('user:id,nickname'),
        ], 201);
    }

    /**
     * GET /api/v1/posts/{post}
     *
     * 게시글 단건 조회 (조회수 증가 포함).
     */
    public function show(Request $request, Post $post): JsonResponse
    {
        abort_if($post->status->value !== 'published', 404);

        // 조회수 증가 (본인 조회 제외)
        if ($request->user()?->id !== $post->user_id) {
            $post->incrementViewCount();
        }

        $post->load([
            'user:id,nickname,political_type,avatar_url',
            'board:id,name,slug',
        ]);

        $myVote = $request->user()
            ? $post->votes()->where('user_id', $request->user()->id)->first()
            : null;

        return response()->json([
            'post'    => $post,
            'my_vote' => $myVote?->vote_type?->value,
        ]);
    }

    /**
     * PUT /api/v1/posts/{post}
     *
     * 게시글 수정 (본인만 가능).
     */
    public function update(Request $request, Post $post): JsonResponse
    {
        abort_if($post->user_id !== $request->user()->id, 403);
        abort_if($post->status->value !== 'published', 403);

        $validated = $request->validate([
            'title'       => ['sometimes', 'string', 'min:2', 'max:300'],
            'content'     => ['sometimes', 'string', 'min:10', 'max:50000'],
            'attachments' => ['nullable', 'array', 'max:5'],
        ]);

        $post->update($validated);

        return response()->json([
            'message' => '게시글이 수정되었습니다.',
            'post'    => $post->fresh(),
        ]);
    }

    /**
     * DELETE /api/v1/posts/{post}
     *
     * 게시글 삭제 (본인 or 관리자).
     */
    public function destroy(Request $request, Post $post): Response
    {
        $user = $request->user();
        abort_if($post->user_id !== $user->id && ! $user->isAdmin(), 403);

        $post->delete();

        return response()->noContent();
    }
}
