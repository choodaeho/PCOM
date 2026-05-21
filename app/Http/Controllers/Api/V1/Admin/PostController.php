<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $posts = Post::withTrashed()
            ->with(['user:id,nickname', 'board:id,name,slug'])
            ->when($request->query('board_id'), fn ($q, $b) => $q->where('board_id', $b))
            ->when($request->query('faction'), fn ($q, $f) => $q->where('faction', $f))
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(30);

        return response()->json($posts);
    }

    public function hide(Request $request, Post $post): JsonResponse
    {
        $post->update(['status' => 'deleted_by_admin']);
        AdminActionLog::record($request->user()->id, 'post.hide', $post);
        return response()->json(['message' => '게시글이 숨김 처리되었습니다.']);
    }

    public function restore(Request $request, Post $post): JsonResponse
    {
        $post->restore();
        $post->update(['status' => 'published']);
        AdminActionLog::record($request->user()->id, 'post.restore', $post);
        return response()->json(['message' => '게시글이 복구되었습니다.']);
    }

    public function destroy(Request $request, Post $post): Response
    {
        $post->forceDelete();
        AdminActionLog::record($request->user()->id, 'post.delete', null, ['post_id' => $post->id]);
        return response()->noContent();
    }
}
