<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use App\Models\Board;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BoardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Boards/Index', [
            'boards' => Board::withTrashed()->withCount('posts')->orderBy('sort_order')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Boards/Form', ['board' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:50'],
            'slug'            => ['required', 'regex:/^[a-z0-9\-]+$/', 'unique:boards'],
            'description'     => ['nullable', 'string', 'max:200'],
            'board_type'      => ['required', 'in:azit,battle,playground,notice'],
            'allowed_faction' => ['required', 'in:all,conservative,moderate,progressive'],
            'sort_order'      => ['integer', 'min:0'],
            'is_active'       => ['boolean'],
        ]);

        Board::create($validated);

        return redirect()->route('admin.boards.index')->with('success', '게시판을 생성했습니다.');
    }

    public function edit(Board $board): Response
    {
        return Inertia::render('Admin/Boards/Form', ['board' => $board]);
    }

    public function update(Request $request, Board $board): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:200'],
            'board_type'  => ['sometimes', 'in:azit,battle,playground,notice'],
            'sort_order'  => ['integer', 'min:0'],
            'is_active'   => ['boolean'],
        ]);

        $board->update($validated);

        return back()->with('success', '게시판을 수정했습니다.');
    }

    public function destroy(Request $request, Board $board): RedirectResponse
    {
        $board->delete();
        AdminActionLog::record($request->user()->id, 'board_delete', $board);

        return back()->with('success', '게시판을 삭제했습니다.');
    }

    public function restore(Request $request, Board $board): RedirectResponse
    {
        $board->restore();

        return back()->with('success', '게시판을 복구했습니다.');
    }
}
