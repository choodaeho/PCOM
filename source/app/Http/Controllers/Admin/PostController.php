<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use App\Models\Board;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Post::withTrashed()->with(['user:id,nickname', 'board:id,name,slug'])->withCount('reports')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('faction')) {
            $query->where('faction', $request->faction);
        }

        if ($request->filled('search')) {
            $query->where('title', 'ilike', '%' . $request->search . '%');
        }

        return Inertia::render('Admin/Posts/Index', [
            'posts'   => $query->paginate(20)->withQueryString(),
            'filters' => $request->only(['status', 'faction', 'search']),
            'boards'  => Board::orderBy('sort_order')->get(['id', 'name']),
        ]);
    }

    public function hide(Request $request, Post $post): RedirectResponse
    {
        $post->update(['status' => 'deleted_by_admin']);
        AdminActionLog::record($request->user()->id, 'post_hide', $post);

        return back()->with('success', '게시글을 숨겼습니다.');
    }

    public function restore(Request $request, Post $post): RedirectResponse
    {
        $post->restore();
        $post->update(['status' => 'published']);

        return back()->with('success', '게시글을 복구했습니다.');
    }

    public function destroy(Request $request, Post $post): RedirectResponse
    {
        AdminActionLog::record($request->user()->id, 'post_force_delete', $post, ['title' => $post->title]);
        $post->forceDelete();

        return back()->with('success', '게시글을 영구 삭제했습니다.');
    }
}
