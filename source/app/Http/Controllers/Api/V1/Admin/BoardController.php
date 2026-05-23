<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use App\Models\Board;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BoardController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Board::withTrashed()->orderBy('sort_order')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug'            => ['required', 'string', 'max:100', 'unique:boards,slug', 'regex:/^[a-z0-9\-]+$/'],
            'name'            => ['required', 'string', 'max:100'],
            'description'     => ['nullable', 'string', 'max:500'],
            'icon'            => ['nullable', 'string', 'max:10'],
            'board_type'      => ['required', 'in:azit,battle,notice'],
            'allowed_faction' => ['required', 'in:all,conservative,moderate,progressive'],
            'sort_order'      => ['integer'],
            'allow_anonymous' => ['boolean'],
            'admin_memo'      => ['nullable', 'string'],
        ]);

        $board = Board::create([...$validated, 'created_by' => $request->user()->id]);
        AdminActionLog::record($request->user()->id, 'board.create', $board);

        return response()->json($board, 201);
    }

    public function show(Board $board): JsonResponse
    {
        return response()->json($board);
    }

    public function update(Request $request, Board $board): JsonResponse
    {
        $validated = $request->validate([
            'name'            => ['sometimes', 'string', 'max:100'],
            'description'     => ['nullable', 'string', 'max:500'],
            'icon'            => ['nullable', 'string', 'max:10'],
            'sort_order'      => ['integer'],
            'is_active'       => ['boolean'],
            'allow_anonymous' => ['boolean'],
            'admin_memo'      => ['nullable', 'string'],
        ]);

        $board->update($validated);
        AdminActionLog::record($request->user()->id, 'board.update', $board, $validated);

        return response()->json($board->fresh());
    }

    public function destroy(Request $request, Board $board): Response
    {
        $board->delete();
        AdminActionLog::record($request->user()->id, 'board.delete', $board);
        return response()->noContent();
    }

    public function restore(Request $request, Board $board): JsonResponse
    {
        $board->restore();
        AdminActionLog::record($request->user()->id, 'board.restore', $board);
        return response()->json(['message' => '게시판이 복구되었습니다.']);
    }
}
