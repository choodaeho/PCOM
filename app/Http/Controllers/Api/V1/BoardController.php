<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Board;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoardController extends Controller
{
    /**
     * GET /api/v1/boards
     *
     * 현재 사용자가 접근 가능한 게시판 목록.
     * 아지트: 본인 진영만, 전쟁터: 전체 노출.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $boards = Board::active()
            ->when(
                $user?->hasCompletedPoliticalTest(),
                fn ($q) => $q->accessibleByFaction($user->political_type),
                fn ($q) => $q->where('board_type', '!=', 'azit') // 테스트 미완료 → 아지트 숨김
            )
            ->get(['id', 'slug', 'name', 'description', 'icon', 'board_type',
                   'allowed_faction', 'post_count', 'sort_order']);

        // 아지트 / 전쟁터 / 공지 그룹핑
        $grouped = $boards->groupBy('board_type');

        return response()->json([
            'azit'   => $grouped->get('azit', collect()),
            'battle' => $grouped->get('battle', collect()),
            'notice' => $grouped->get('notice', collect()),
        ]);
    }

    /**
     * GET /api/v1/boards/{board:slug}
     *
     * 게시판 상세 (메타 정보).
     */
    public function show(Board $board): JsonResponse
    {
        return response()->json(['board' => $board]);
    }
}
