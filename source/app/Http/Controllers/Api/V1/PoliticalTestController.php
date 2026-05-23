<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PoliticalTestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PoliticalTestController extends Controller
{
    public function __construct(private readonly PoliticalTestService $testService) {}

    /**
     * GET /api/v1/political-test/questions
     *
     * 활성화된 성향 테스트 문항 목록 반환.
     */
    public function questions(): JsonResponse
    {
        $questions = $this->testService->getActiveQuestions()
            ->map(fn ($q) => [
                'id'       => $q->id,
                'question' => $q->question,
                'options'  => $q->options,
                'category' => $q->category,
            ]);

        return response()->json([
            'questions' => $questions,
            'total'     => $questions->count(),
        ]);
    }

    /**
     * POST /api/v1/political-test/submit
     *
     * 성향 테스트 응답 제출 및 진영 결정.
     * Request body: { "answers": { "1": 2, "2": -1, ... } }
     */
    public function submit(Request $request): JsonResponse
    {
        $request->validate([
            'answers'   => ['required', 'array'],
            'answers.*' => ['required', 'integer', 'min:-2', 'max:2'],
        ]);

        $session = $this->testService->submitAndSave(
            $request->user(),
            $request->input('answers')
        );

        return response()->json([
            'message'      => '성향 테스트가 완료되었습니다.',
            'total_score'  => $session->total_score,
            'result_type'  => $session->result_type->value,
            'faction_label'=> $session->result_type->label(),
            'faction_color'=> $session->result_type->color(),
            'faction_emoji'=> $session->result_type->emoji(),
        ]);
    }

    /**
     * GET /api/v1/political-test/result
     *
     * 가장 최근 성향 테스트 결과 반환.
     */
    public function result(Request $request): JsonResponse
    {
        $session = $request->user()->latestTestSession;

        if ($session === null) {
            return response()->json(['message' => '성향 테스트를 아직 완료하지 않았습니다.'], 404);
        }

        return response()->json([
            'total_score'   => $session->total_score,
            'result_type'   => $session->result_type->value,
            'faction_label' => $session->result_type->label(),
            'faction_color' => $session->result_type->color(),
            'faction_emoji' => $session->result_type->emoji(),
            'completed_at'  => $session->completed_at->toIso8601String(),
        ]);
    }
}
