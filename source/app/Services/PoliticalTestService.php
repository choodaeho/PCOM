<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FactionType;
use App\Models\PoliticalTest;
use App\Models\PoliticalTestSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * 성향 테스트 처리 서비스.
 *
 * 책임:
 *   - 활성 문항 조회
 *   - 응답값으로 총점 산출
 *   - 총점으로 진영(FactionType) 결정
 *   - 결과를 DB에 저장하고 users 테이블 갱신
 */
class PoliticalTestService
{
    /**
     * 활성화된 문항 목록 반환 (출제 순서 정렬).
     *
     * @return \Illuminate\Database\Eloquent\Collection<PoliticalTest>
     */
    public function getActiveQuestions(): \Illuminate\Database\Eloquent\Collection
    {
        return PoliticalTest::active()->get();
    }

    /**
     * 활성 문항에서 $count개를 무작위로 선택하여 반환.
     *
     * @param  int  $count 출제할 문항 수 (기본 10)
     * @return \Illuminate\Database\Eloquent\Collection<PoliticalTest>
     */
    public function getRandomQuestions(int $count = 10): \Illuminate\Database\Eloquent\Collection
    {
        // scopeActive 내 orderBy('sort_order')를 reorder()로 초기화한 뒤
        // inRandomOrder()를 적용해야 진짜 ORDER BY RANDOM() 쿼리가 실행된다.
        return PoliticalTest::active()->reorder()->inRandomOrder()->limit($count)->get();
    }

    /**
     * 응답으로 성향을 계산만 하고 DB에 저장하지 않는다 (비로그인 테스트용).
     *
     * @param  array<int, int>   $answers     {question_id => selected_value} 맵
     * @param  array<int>        $questionIds 출제된 문항 ID 목록 (빈 배열이면 전체 활성 문항 사용)
     * @return array{political_type: string, faction_label: string, faction_emoji: string, faction_color: string, score: int, description: null}
     */
    public function computeResult(array $answers, array $questionIds = []): array
    {
        $questions  = ! empty($questionIds)
            ? PoliticalTest::whereIn('id', $questionIds)->get()
            : $this->getActiveQuestions();
        $totalScore = $this->calculateScore($questions, $answers);
        $faction    = FactionType::fromScore($totalScore);

        return [
            'political_type' => $faction->value,
            'faction_label'  => $faction->label(),
            'faction_emoji'  => $faction->emoji(),
            'faction_color'  => $faction->color(),
            'score'          => $totalScore,
            'description'    => null,
        ];
    }

    /**
     * 제출된 응답으로 성향 점수를 계산하고 결과를 저장한다.
     *
     * @param  User              $user        테스트 제출 사용자
     * @param  array<int, int>   $answers     {question_id => selected_value} 맵
     * @param  array<int>        $questionIds 출제된 문항 ID 목록 (세션에서 전달)
     * @return PoliticalTestSession           저장된 세션 레코드
     *
     * @throws \InvalidArgumentException 응답이 부족한 경우
     */
    public function submitAndSave(User $user, array $answers, array $questionIds = []): PoliticalTestSession
    {
        $questions = ! empty($questionIds)
            ? PoliticalTest::whereIn('id', $questionIds)->get()
            : $this->getActiveQuestions();

        if (count($answers) < $questions->count()) {
            throw new \InvalidArgumentException('모든 문항에 응답해야 합니다.');
        }

        $totalScore = $this->calculateScore($questions, $answers);
        $faction    = FactionType::fromScore($totalScore);

        return DB::transaction(function () use ($user, $answers, $totalScore, $faction) {
            // 기존 최종 결과 해제
            PoliticalTestSession::where('user_id', $user->id)
                ->where('is_final', true)
                ->update(['is_final' => false]);

            // 새 세션 저장
            $session = PoliticalTestSession::create([
                'user_id'      => $user->id,
                'answers'      => $answers,
                'total_score'  => $totalScore,
                'result_type'  => $faction->value,
                'is_final'     => true,
                'completed_at' => now(),
            ]);

            // 사용자 진영 정보 갱신
            $user->update([
                'political_type'    => $faction->value,
                'test_score'        => $totalScore,
                'test_completed_at' => now(),
            ]);

            return $session;
        });
    }

    // -------------------------------------------------------------------------
    // 내부 헬퍼
    // -------------------------------------------------------------------------

    /**
     * 응답값과 문항 가중치를 곱한 합산 점수를 100점 척도로 정규화.
     *
     * 공식: sum(selected_value × weight) → 스케일링 → int(-100 ~ +100)
     *
     * @param  \Illuminate\Database\Eloquent\Collection $questions
     * @param  array<int, int>                          $answers
     */
    private function calculateScore(
        \Illuminate\Database\Eloquent\Collection $questions,
        array $answers
    ): int {
        $rawScore = 0.0;
        $maxScore = 0.0; // 이론적 최대 점수 (모든 문항에 최대값 응답)

        foreach ($questions as $question) {
            $id           = (int) $question->id;
            $selectedValue = $answers[$id] ?? 0;

            // 선택지에서 최대/최소 value 추출
            $optionValues = array_column($question->options, 'value');
            $maxValue     = max($optionValues);
            $minValue     = min($optionValues);

            $rawScore += $selectedValue * $question->weight;

            // 가중치 방향에 따라 이론적 최대를 누적
            $maxScore += abs($question->weight) * max(abs($maxValue), abs($minValue));
        }

        if ($maxScore === 0.0) {
            return 0;
        }

        // -100 ~ +100 정규화
        return (int) round(($rawScore / $maxScore) * 100);
    }
}
