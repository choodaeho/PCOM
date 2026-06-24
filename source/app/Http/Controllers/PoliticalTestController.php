<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PoliticalTestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PoliticalTestController extends Controller
{
    public function __construct(private readonly PoliticalTestService $testService) {}

    /**
     * 성향 테스트 페이지 (비로그인 허용).
     *
     * ?source=register 로 접근하면 세션에 source를 저장하여
     * 제출 이후 결과 페이지에서 "이 결과 적용" 버튼을 표시한다.
     */
    public function show(Request $request): Response
    {
        // source 파라미터를 세션에 저장 (register | null)
        if ($request->query('source')) {
            $request->session()->put('political_test_source', $request->query('source'));
        }

        return Inertia::render('PoliticalTest/Show', [
            'questions' => $this->testService->getActiveQuestions(),
            'source'    => $request->session()->get('political_test_source'),
        ]);
    }

    /**
     * 성향 테스트 제출 (비로그인 허용).
     *
     * - 로그인 상태: DB에 저장하고 result 페이지로 이동
     * - 비로그인:   결과를 세션에만 저장하고 result 페이지로 이동
     */
    public function submit(Request $request): mixed
    {
        $validated = $request->validate([
            'answers'               => ['required', 'array'],
            'answers.*.question_id' => ['required', 'integer', 'exists:political_tests,id'],
            'answers.*.value'       => ['required', 'integer'],
        ]);

        // [{question_id: X, value: Y}] → [X => Y] 맵으로 변환 (서비스 기대 형식)
        $answersMap = collect($validated['answers'])
            ->pluck('value', 'question_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        if (Auth::check()) {
            // 로그인 사용자: DB에 저장
            $this->testService->submitAndSave(Auth::user(), $answersMap);

            return redirect()->route('political-test.result');
        }

        // 비로그인: 계산만 하고 세션에 보관
        $result = $this->testService->computeResult($answersMap);
        $request->session()->put('political_test_guest_result', $result);

        return redirect()->route('political-test.result');
    }

    /**
     * 성향 테스트 결과 페이지 (비로그인 허용).
     *
     * - 로그인 사용자: users + latestTestSession 에서 읽음
     * - 비로그인:     세션에서 읽음
     */
    public function result(Request $request): mixed
    {
        $source = $request->session()->get('political_test_source');

        if (Auth::check()) {
            $user          = $request->user()->fresh();
            $latestSession = $user->latestTestSession;

            if (! $user->political_type) {
                // 아직 테스트를 완료하지 않은 로그인 사용자 → 테스트로 리다이렉트
                return redirect()->route('political-test.show');
            }

            $result = [
                'political_type' => $user->political_type?->value,
                'faction_label'  => $user->political_type?->label(),
                'faction_emoji'  => $user->political_type?->emoji(),
                'faction_color'  => $user->political_type?->color(),
                'score'          => $latestSession?->total_score ?? $user->test_score,
                'description'    => null,
            ];

            // source 소비 후 초기화
            $request->session()->forget('political_test_source');

            return Inertia::render('PoliticalTest/Result', [
                'result' => $result,
                'source' => $source,
            ]);
        }

        // 비로그인: 세션 결과 읽기
        $result = $request->session()->get('political_test_guest_result');

        if (! $result) {
            return redirect()->route('political-test.show');
        }

        return Inertia::render('PoliticalTest/Result', [
            'result' => $result,
            'source' => $source,
        ]);
    }
}
