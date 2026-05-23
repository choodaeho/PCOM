<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use App\Models\ScoreWeight;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScoreWeightController extends Controller
{
    /**
     * GET /api/v1/admin/score-weights
     */
    public function index(): JsonResponse
    {
        return response()->json(['weights' => ScoreWeight::all()]);
    }

    /**
     * PUT /api/v1/admin/score-weights/{scoreWeight}
     *
     * 가중치 수정 후 Redis 캐시 무효화.
     */
    public function update(Request $request, ScoreWeight $scoreWeight): JsonResponse
    {
        $validated = $request->validate([
            'weight'      => ['required', 'numeric', 'between:-100,100'],
            'description' => ['nullable', 'string', 'max:200'],
            'is_active'   => ['boolean'],
        ]);

        $before = $scoreWeight->only(['weight', 'description', 'is_active']);
        $scoreWeight->update($validated);

        // Redis 가중치 캐시 무효화
        ScoreWeight::invalidateCache();

        AdminActionLog::record(
            $request->user()->id,
            'score_weight.update',
            $scoreWeight,
            ['before' => $before, 'after' => $validated]
        );

        return response()->json([
            'message' => '점수 가중치가 업데이트되었습니다. 다음 집계부터 반영됩니다.',
            'weight'  => $scoreWeight->fresh(),
        ]);
    }
}
