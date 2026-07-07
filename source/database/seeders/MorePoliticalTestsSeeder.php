<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PoliticalTest;
use Illuminate\Database\Seeder;

/**
 * 성향 테스트 추가 문항 시드 (30문항).
 *
 * 기존 PoliticalTestsSeeder의 10문항에 더해 총 40문항을 구성한다.
 * 매 테스트 시 DB에서 10개를 무작위 선택하므로 문항 수가 많을수록 다양성 증가.
 *
 * 점수 규칙:
 *   +2/+1 : 보수 성향 (conservative)
 *   -2/-1 : 진보 성향 (progressive)
 *   0     : 중립
 */
class MorePoliticalTestsSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [
            // ── 경제 ──────────────────────────────────
            [
                'question'   => '최저임금을 대폭 인상하면 오히려 고용이 줄어든다.',
                'options'    => [
                    ['value' =>  2, 'label' => '매우 동의'],
                    ['value' =>  1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.3,
                'sort_order' => 11,
                'is_active'  => true,
            ],
            [
                'question'   => '법인세 인하가 기업 투자와 일자리 창출에 도움이 된다.',
                'options'    => [
                    ['value' =>  2, 'label' => '매우 동의'],
                    ['value' =>  1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.4,
                'sort_order' => 12,
                'is_active'  => true,
            ],
            [
                'question'   => '소득 재분배보다 경제 성장이 빈곤 문제 해결에 더 효과적이다.',
                'options'    => [
                    ['value' =>  2, 'label' => '매우 동의'],
                    ['value' =>  1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.2,
                'sort_order' => 13,
                'is_active'  => true,
            ],
            [
                'question'   => '기본소득 도입은 근로 의욕을 저하시킬 수 있다.',
                'options'    => [
                    ['value' =>  2, 'label' => '매우 동의'],
                    ['value' =>  1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.1,
                'sort_order' => 14,
                'is_active'  => true,
            ],
            // ── 복지 ──────────────────────────────────
            [
                'question'   => '보편적 무상 의료 서비스 확대가 필요하다.',
                'options'    => [
                    ['value' => -2, 'label' => '매우 동의'],
                    ['value' => -1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' =>  1, 'label' => '반대'],
                    ['value' =>  2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.3,
                'sort_order' => 15,
                'is_active'  => true,
            ],
            [
                'question'   => '노인 복지보다 청년층 지원 예산을 더 늘려야 한다.',
                'options'    => [
                    ['value' => -2, 'label' => '매우 동의'],
                    ['value' => -1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' =>  1, 'label' => '반대'],
                    ['value' =>  2, 'label' => '매우 반대'],
                ],
                'weight'     => 0.9,
                'sort_order' => 16,
                'is_active'  => true,
            ],
            [
                'question'   => '복지는 선별적으로 제공하는 것이 보편적 지급보다 효율적이다.',
                'options'    => [
                    ['value' =>  2, 'label' => '매우 동의'],
                    ['value' =>  1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.2,
                'sort_order' => 17,
                'is_active'  => true,
            ],
            // ── 환경·에너지 ────────────────────────────
            [
                'question'   => '원자력 발전은 탄소중립 달성을 위해 필요한 에너지원이다.',
                'options'    => [
                    ['value' =>  2, 'label' => '매우 동의'],
                    ['value' =>  1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.3,
                'sort_order' => 18,
                'is_active'  => true,
            ],
            [
                'question'   => '탄소세 도입은 산업 경쟁력을 약화시키므로 신중해야 한다.',
                'options'    => [
                    ['value' =>  2, 'label' => '매우 동의'],
                    ['value' =>  1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.1,
                'sort_order' => 19,
                'is_active'  => true,
            ],
            [
                'question'   => '2050 탄소중립 목표 달성을 위해 경제적 부담도 감수해야 한다.',
                'options'    => [
                    ['value' => -2, 'label' => '매우 동의'],
                    ['value' => -1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' =>  1, 'label' => '반대'],
                    ['value' =>  2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.2,
                'sort_order' => 20,
                'is_active'  => true,
            ],
            // ── 안보·외교 ──────────────────────────────
            [
                'question'   => '사드(THAAD) 배치는 한국 안보를 위해 필요한 결정이었다.',
                'options'    => [
                    ['value' =>  2, 'label' => '매우 동의'],
                    ['value' =>  1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.3,
                'sort_order' => 21,
                'is_active'  => true,
            ],
            [
                'question'   => '자국 방위를 위해 독자적인 핵 억지력 보유를 검토해야 한다.',
                'options'    => [
                    ['value' =>  2, 'label' => '매우 동의'],
                    ['value' =>  1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.4,
                'sort_order' => 22,
                'is_active'  => true,
            ],
            [
                'question'   => '남북관계 개선을 위해 대북 인도적 지원을 확대해야 한다.',
                'options'    => [
                    ['value' => -2, 'label' => '매우 동의'],
                    ['value' => -1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' =>  1, 'label' => '반대'],
                    ['value' =>  2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.3,
                'sort_order' => 23,
                'is_active'  => true,
            ],
            // ── 교육 ──────────────────────────────────
            [
                'question'   => '자사고·외국어고 등 특목고를 유지하고 확대해야 한다.',
                'options'    => [
                    ['value' =>  2, 'label' => '매우 동의'],
                    ['value' =>  1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.2,
                'sort_order' => 24,
                'is_active'  => true,
            ],
            [
                'question'   => '대학 서열 해소를 위해 입시 제도를 근본적으로 개혁해야 한다.',
                'options'    => [
                    ['value' => -2, 'label' => '매우 동의'],
                    ['value' => -1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' =>  1, 'label' => '반대'],
                    ['value' =>  2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.1,
                'sort_order' => 25,
                'is_active'  => true,
            ],
            [
                'question'   => '교육에서 경쟁은 개인의 역량 개발에 긍정적인 역할을 한다.',
                'options'    => [
                    ['value' =>  2, 'label' => '매우 동의'],
                    ['value' =>  1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.0,
                'sort_order' => 26,
                'is_active'  => true,
            ],
            // ── 사법·검찰 ──────────────────────────────
            [
                'question'   => '검찰 권력을 분산하고 고위공직자범죄수사처(공수처)를 강화해야 한다.',
                'options'    => [
                    ['value' => -2, 'label' => '매우 동의'],
                    ['value' => -1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' =>  1, 'label' => '반대'],
                    ['value' =>  2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.4,
                'sort_order' => 27,
                'is_active'  => true,
            ],
            [
                'question'   => '사형제도는 흉악 범죄 억제를 위해 유지 또는 집행되어야 한다.',
                'options'    => [
                    ['value' =>  2, 'label' => '매우 동의'],
                    ['value' =>  1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.1,
                'sort_order' => 28,
                'is_active'  => true,
            ],
            // ── 노동 ──────────────────────────────────
            [
                'question'   => '주 4일제 도입이 노동자 삶의 질 향상에 도움이 된다.',
                'options'    => [
                    ['value' => -2, 'label' => '매우 동의'],
                    ['value' => -1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' =>  1, 'label' => '반대'],
                    ['value' =>  2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.0,
                'sort_order' => 29,
                'is_active'  => true,
            ],
            [
                'question'   => '노동 유연성 확대가 일자리 창출과 기업 경쟁력에 도움이 된다.',
                'options'    => [
                    ['value' =>  2, 'label' => '매우 동의'],
                    ['value' =>  1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.2,
                'sort_order' => 30,
                'is_active'  => true,
            ],
            [
                'question'   => '강성 노동조합의 권한을 제한하는 입법이 필요하다.',
                'options'    => [
                    ['value' =>  2, 'label' => '매우 동의'],
                    ['value' =>  1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.1,
                'sort_order' => 31,
                'is_active'  => true,
            ],
            // ── 부동산·주거 ────────────────────────────
            [
                'question'   => '다주택자에 대한 세금(종부세)을 현행 수준 이상으로 유지해야 한다.',
                'options'    => [
                    ['value' => -2, 'label' => '매우 동의'],
                    ['value' => -1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' =>  1, 'label' => '반대'],
                    ['value' =>  2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.3,
                'sort_order' => 32,
                'is_active'  => true,
            ],
            [
                'question'   => '재건축·재개발 규제를 완화하면 주택 공급이 늘어 집값 안정에 도움이 된다.',
                'options'    => [
                    ['value' =>  2, 'label' => '매우 동의'],
                    ['value' =>  1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.2,
                'sort_order' => 33,
                'is_active'  => true,
            ],
            // ── 사회·문화 ──────────────────────────────
            [
                'question'   => '이민 확대는 저출산·인구 감소 문제 해결에 도움이 된다.',
                'options'    => [
                    ['value' => -2, 'label' => '매우 동의'],
                    ['value' => -1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' =>  1, 'label' => '반대'],
                    ['value' =>  2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.1,
                'sort_order' => 34,
                'is_active'  => true,
            ],
            [
                'question'   => '여성 할당제나 적극적 우대 조치는 역차별을 유발할 수 있다.',
                'options'    => [
                    ['value' =>  2, 'label' => '매우 동의'],
                    ['value' =>  1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.0,
                'sort_order' => 35,
                'is_active'  => true,
            ],
            [
                'question'   => '동성결혼 법제화를 지지한다.',
                'options'    => [
                    ['value' => -2, 'label' => '매우 동의'],
                    ['value' => -1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' =>  1, 'label' => '반대'],
                    ['value' =>  2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.0,
                'sort_order' => 36,
                'is_active'  => true,
            ],
            // ── 언론·정보 ──────────────────────────────
            [
                'question'   => '공영방송의 독립성 강화를 위한 법적 장치가 필요하다.',
                'options'    => [
                    ['value' => -2, 'label' => '매우 동의'],
                    ['value' => -1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' =>  1, 'label' => '반대'],
                    ['value' =>  2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.1,
                'sort_order' => 37,
                'is_active'  => true,
            ],
            [
                'question'   => '온라인 허위정보 규제를 위한 법률 강화가 필요하다.',
                'options'    => [
                    ['value' => -2, 'label' => '매우 동의'],
                    ['value' => -1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' =>  1, 'label' => '반대'],
                    ['value' =>  2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.0,
                'sort_order' => 38,
                'is_active'  => true,
            ],
            // ── 정부 역할 ──────────────────────────────
            [
                'question'   => '정부의 시장 개입은 최소화하고 민간의 자율에 맡겨야 한다.',
                'options'    => [
                    ['value' =>  2, 'label' => '매우 동의'],
                    ['value' =>  1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.4,
                'sort_order' => 39,
                'is_active'  => true,
            ],
            [
                'question'   => '공공의료 확충을 위해 의대 정원을 대폭 늘려야 한다.',
                'options'    => [
                    ['value' => -2, 'label' => '매우 동의'],
                    ['value' => -1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' =>  1, 'label' => '반대'],
                    ['value' =>  2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.1,
                'sort_order' => 40,
                'is_active'  => true,
            ],
        ];

        foreach ($questions as $q) {
            PoliticalTest::firstOrCreate(
                ['question' => $q['question']],
                [
                    'options'    => $q['options'],
                    'weight'     => $q['weight'],
                    'sort_order' => $q['sort_order'],
                    'is_active'  => $q['is_active'],
                ]
            );
        }

        $this->command->info('MorePoliticalTests seeded: '.count($questions).' additional questions');
    }
}
