<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PoliticalTest;
use Illuminate\Database\Seeder;

class PoliticalTestsSeeder extends Seeder
{
    /**
     * 성향 테스트 기본 문항 시드.
     *
     * 점수 기준: 보수 성향 → 양수(+), 진보 성향 → 음수(-)
     * 범위: -100 ~ +100 → +25 이상 보수, -25 이하 진보, 그 사이 중도
     */
    public function run(): void
    {
        $questions = [
            [
                'question' => '국가 안보와 군사력 강화를 위해 국방 예산을 늘려야 한다.',
                'options' => [
                    ['value' => 2,  'label' => '매우 동의'],
                    ['value' => 1,  'label' => '동의'],
                    ['value' => 0,  'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight' => 1.2,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'question' => '복지 확대보다 경제 성장이 국민 삶의 질 향상에 더 효과적이다.',
                'options' => [
                    ['value' => 2,  'label' => '매우 동의'],
                    ['value' => 1,  'label' => '동의'],
                    ['value' => 0,  'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight' => 1.5,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'question' => '대기업 규제를 완화하면 경제 전반에 긍정적인 효과가 있다.',
                'options' => [
                    ['value' => 2,  'label' => '매우 동의'],
                    ['value' => 1,  'label' => '동의'],
                    ['value' => 0,  'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight' => 1.3,
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'question' => '사회적 약자를 위한 정부의 적극적인 지원 정책이 필요하다.',
                'options' => [
                    ['value' => -2, 'label' => '매우 동의'],
                    ['value' => -1, 'label' => '동의'],
                    ['value' => 0,  'label' => '중립'],
                    ['value' => 1,  'label' => '반대'],
                    ['value' => 2,  'label' => '매우 반대'],
                ],
                'weight' => 1.4,
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'question' => '전통적 가족 가치관과 문화를 보전하는 것이 중요하다.',
                'options' => [
                    ['value' => 2,  'label' => '매우 동의'],
                    ['value' => 1,  'label' => '동의'],
                    ['value' => 0,  'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight' => 1.0,
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'question' => '소득 불평등 해소를 위해 고소득자와 대기업에 더 높은 세금을 부과해야 한다.',
                'options' => [
                    ['value' => -2, 'label' => '매우 동의'],
                    ['value' => -1, 'label' => '동의'],
                    ['value' => 0,  'label' => '중립'],
                    ['value' => 1,  'label' => '반대'],
                    ['value' => 2,  'label' => '매우 반대'],
                ],
                'weight' => 1.5,
                'sort_order' => 6,
                'is_active' => true,
            ],
            [
                'question' => '대북 정책은 강경한 압박보다 대화와 협력을 통해 풀어야 한다.',
                'options' => [
                    ['value' => -2, 'label' => '매우 동의'],
                    ['value' => -1, 'label' => '동의'],
                    ['value' => 0,  'label' => '중립'],
                    ['value' => 1,  'label' => '반대'],
                    ['value' => 2,  'label' => '매우 반대'],
                ],
                'weight' => 1.3,
                'sort_order' => 7,
                'is_active' => true,
            ],
            [
                'question' => '환경 보호보다 경제 발전을 우선시해야 하는 경우도 있다.',
                'options' => [
                    ['value' => 2,  'label' => '매우 동의'],
                    ['value' => 1,  'label' => '동의'],
                    ['value' => 0,  'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight' => 1.0,
                'sort_order' => 8,
                'is_active' => true,
            ],
            [
                'question' => '미한 동맹을 강화하는 것이 한국의 안보에 도움이 된다.',
                'options' => [
                    ['value' => 2,  'label' => '매우 동의'],
                    ['value' => 1,  'label' => '동의'],
                    ['value' => 0,  'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight' => 1.2,
                'sort_order' => 9,
                'is_active' => true,
            ],
            [
                'question' => '노동자의 권리 보호를 위한 강력한 노동법이 필요하다.',
                'options' => [
                    ['value' => -2, 'label' => '매우 동의'],
                    ['value' => -1, 'label' => '동의'],
                    ['value' => 0,  'label' => '중립'],
                    ['value' => 1,  'label' => '반대'],
                    ['value' => 2,  'label' => '매우 반대'],
                ],
                'weight' => 1.1,
                'sort_order' => 10,
                'is_active' => true,
            ],
        ];

        foreach ($questions as $q) {
            PoliticalTest::firstOrCreate(
                ['question' => $q['question']],
                [
                    'options'   => $q['options'],
                    'weight'    => $q['weight'],
                    'sort_order'     => $q['sort_order'],
                    'is_active' => $q['is_active'],
                ]
            );
        }

        $this->command->info('PoliticalTests seeded: '.count($questions).' questions');
    }
}
