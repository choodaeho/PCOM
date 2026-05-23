<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PoliticalTestService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PoliticalTestController extends Controller
{
    public function __construct(private readonly PoliticalTestService $testService) {}

    public function show(): Response
    {
        return Inertia::render('PoliticalTest/Show', [
            'questions' => $this->testService->getActiveQuestions(),
        ]);
    }

    public function submit(Request $request): mixed
    {
        $validated = $request->validate([
            'answers'               => ['required', 'array'],
            'answers.*.question_id' => ['required', 'integer', 'exists:political_tests,id'],
            'answers.*.value'       => ['required', 'integer'],
        ]);

        $session = $this->testService->submitAndSave($request->user(), $validated['answers']);

        return redirect()->route('political-test.result')->with('test_session_id', $session->id);
    }

    public function result(Request $request): Response
    {
        $user          = $request->user()->fresh();
        $latestSession = $user->latestTestSession;

        return Inertia::render('PoliticalTest/Result', [
            'user'    => [
                'nickname'       => $user->nickname,
                'political_type' => $user->political_type?->value,
                'faction_label'  => $user->political_type?->label(),
                'faction_color'  => $user->political_type?->color(),
                'faction_emoji'  => $user->political_type?->emoji(),
                'test_score'     => $user->test_score,
            ],
            'session' => $latestSession ? [
                'score'       => $latestSession->score,
                'result_type' => $latestSession->result_type?->value,
            ] : null,
        ]);
    }
}
