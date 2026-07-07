<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PoliticalTest;
use Illuminate\Database\Seeder;

/**
 * 성향 테스트 추가 문항 시드 — 3차 배치 (30문항, sort_order 41-70).
 *
 * 기존 PoliticalTestsSeeder(10) + MorePoliticalTestsSeeder(30) 에 이어
 * 총 70문항으로 확장한다.
 *
 * 점수 규칙:
 *   +2/+1 : 보수 성향 (conservative)
 *   -2/-1 : 진보 성향 (progressive)
 *   0     : 중립
 */
class ExtraPoliticalTestsSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [
            // ── 연금·재정 ───────────────────────────────
            [
                'question'   => '국민연금 수령 나이를 높이더라도 재정 안정성을 확보해야 한다.',
                'options'    => [
                    ['value' =>  2, 'label' => '매우 동의'],
                    ['value' =>  1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.2,
                'sort_order' => 41,
                'is_active'  => true,
            ],
            [
                'question'   => '국가 재정 건전성을 위해 복지 지출을 줄이는 것이 불가피하다.',
                'options'    => [
                    ['value' =>  2, 'label' => '매우 동의'],
                    ['value' =>  1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.3,
                'sort_order' => 42,
                'is_active'  => true,
            ],
            [
                'question'   => '국채 발행을 늘려서라도 적극적인 재정 확장 정책을 펼쳐야 한다.',
                'options'    => [
                    ['value' => -2, 'label' => '매우 동의'],
                    ['value' => -1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' =>  1, 'label' => '반대'],
                    ['value' =>  2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.2,
                'sort_order' => 43,
                'is_active'  => true,
            ],
            // ── 민영화·공기업 ───────────────────────────
            [
                'question'   => '공기업 민영화를 통해 경영 효율성을 높여야 한다.',
                'options'    => [
                    ['value' =>  2, 'label' => '매우 동의'],
                    ['value' =>  1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.3,
                'sort_order' => 44,
                'is_active'  => true,
            ],
            [
                'question'   => '의료와 교육은 공공재로서 국가가 직접 운영해야 한다.',
                'options'    => [
                    ['value' => -2, 'label' => '매우 동의'],
                    ['value' => -1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' =>  1, 'label' => '반대'],
                    ['value' =>  2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.2,
                'sort_order' => 45,
                'is_active'  => true,
            ],
            // ── 재벌·대기업 ─────────────────────────────
            [
                'question'   => '재벌 총수 일가의 경영권 세습을 제한하는 법이 필요하다.',
                'options'    => [
                    ['value' => -2, 'label' => '매우 동의'],
                    ['value' => -1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' =>  1, 'label' => '반대'],
                    ['value' =>  2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.2,
                'sort_order' => 46,
                'is_active'  => true,
            ],
            [
                'question'   => '대기업 위주의 수출 주도 경제 모델을 계속 유지해야 한다.',
                'options'    => [
                    ['value' =>  2, 'label' => '매우 동의'],
                    ['value' =>  1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.1,
                'sort_order' => 47,
                'is_active'  => true,
            ],
            // ── 선거·정치 제도 ──────────────────────────
            [
                'question'   => '현행 소선거구제보다 비례대표제 비중을 대폭 확대해야 한다.',
                'options'    => [
                    ['value' => -2, 'label' => '매우 동의'],
                    ['value' => -1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' =>  1, 'label' => '반대'],
                    ['value' =>  2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.1,
                'sort_order' => 48,
                'is_active'  => true,
            ],
            [
                'question'   => '대통령 4년 중임제로 개헌하는 것이 바람직하다.',
                'options'    => [
                    ['value' => -1, 'label' => '매우 동의'],
                    ['value' => -1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' =>  1, 'label' => '반대'],
                    ['value' =>  2, 'label' => '매우 반대'],
                ],
                'weight'     => 0.8,
                'sort_order' => 49,
                'is_active'  => true,
            ],
            [
                'question'   => '정치 신인이나 소수 정당이 진입하기 쉽도록 선거 제도를 바꿔야 한다.',
                'options'    => [
                    ['value' => -2, 'label' => '매우 동의'],
                    ['value' => -1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' =>  1, 'label' => '반대'],
                    ['value' =>  2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.0,
                'sort_order' => 50,
                'is_active'  => true,
            ],
            // ── 지방분권 ────────────────────────────────
            [
                'question'   => '수도권 집중 완화를 위해 행정수도 이전을 완성해야 한다.',
                'options'    => [
                    ['value' => -2, 'label' => '매우 동의'],
                    ['value' => -1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' =>  1, 'label' => '반대'],
                    ['value' =>  2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.0,
                'sort_order' => 51,
                'is_active'  => true,
            ],
            [
                'question'   => '지방자치단체에 더 많은 세금과 권한을 이양해야 한다.',
                'options'    => [
                    ['value' => -2, 'label' => '매우 동의'],
                    ['value' => -1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' =>  1, 'label' => '반대'],
                    ['value' =>  2, 'label' => '매우 반대'],
                ],
                'weight'     => 0.9,
                'sort_order' => 52,
                'is_active'  => true,
            ],
            // ── AI·디지털 ────────────────────────────────
            [
                'question'   => 'AI 기술 발전을 위해 개인정보 활용 규제를 완화해야 한다.',
                'options'    => [
                    ['value' =>  2, 'label' => '매우 동의'],
                    ['value' =>  1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.0,
                'sort_order' => 53,
                'is_active'  => true,
            ],
            [
                'question'   => '플랫폼 기업의 독점적 지위 남용을 강력히 규제해야 한다.',
                'options'    => [
                    ['value' => -2, 'label' => '매우 동의'],
                    ['value' => -1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' =>  1, 'label' => '반대'],
                    ['value' =>  2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.1,
                'sort_order' => 54,
                'is_active'  => true,
            ],
            // ── 표현의 자유 ─────────────────────────────
            [
                'question'   => '혐오 표현을 법으로 처벌하면 표현의 자유를 침해할 수 있다.',
                'options'    => [
                    ['value' =>  2, 'label' => '매우 동의'],
                    ['value' =>  1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.0,
                'sort_order' => 55,
                'is_active'  => true,
            ],
            [
                'question'   => '국가보안법은 현 안보 상황에서 여전히 필요하다.',
                'options'    => [
                    ['value' =>  2, 'label' => '매우 동의'],
                    ['value' =>  1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.3,
                'sort_order' => 56,
                'is_active'  => true,
            ],
            // ── 외교 다각화 ─────────────────────────────
            [
                'question'   => '한국은 미국·중국 사이에서 전략적 균형 외교를 추구해야 한다.',
                'options'    => [
                    ['value' => -2, 'label' => '매우 동의'],
                    ['value' => -1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' =>  1, 'label' => '반대'],
                    ['value' =>  2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.2,
                'sort_order' => 57,
                'is_active'  => true,
            ],
            [
                'question'   => '일본과의 과거사 문제보다 미래 실용 협력을 우선해야 한다.',
                'options'    => [
                    ['value' =>  2, 'label' => '매우 동의'],
                    ['value' =>  1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.2,
                'sort_order' => 58,
                'is_active'  => true,
            ],
            // ── 청년·인구 ────────────────────────────────
            [
                'question'   => '저출산 해결을 위한 출산·육아 지원에 대규모 예산을 투입해야 한다.',
                'options'    => [
                    ['value' => -2, 'label' => '매우 동의'],
                    ['value' => -1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' =>  1, 'label' => '반대'],
                    ['value' =>  2, 'label' => '매우 반대'],
                ],
                'weight'     => 0.9,
                'sort_order' => 59,
                'is_active'  => true,
            ],
            [
                'question'   => '군 복무 기간 단축보다 복무 여건 개선이 더 중요하다.',
                'options'    => [
                    ['value' =>  2, 'label' => '매우 동의'],
                    ['value' =>  1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight'     => 0.8,
                'sort_order' => 60,
                'is_active'  => true,
            ],
            // ── 종교·세속주의 ────────────────────────────
            [
                'question'   => '종교 단체도 영리 활동에 대해 세금을 납부해야 한다.',
                'options'    => [
                    ['value' => -2, 'label' => '매우 동의'],
                    ['value' => -1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' =>  1, 'label' => '반대'],
                    ['value' =>  2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.0,
                'sort_order' => 61,
                'is_active'  => true,
            ],
            [
                'question'   => '공공 정책에서 종교적 가치관이 반영되는 것은 자연스러운 일이다.',
                'options'    => [
                    ['value' =>  2, 'label' => '매우 동의'],
                    ['value' =>  1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight'     => 0.9,
                'sort_order' => 62,
                'is_active'  => true,
            ],
            // ── 주거·임대차 ─────────────────────────────
            [
                'question'   => '전월세 상한제 등 임대료 규제를 강화해야 한다.',
                'options'    => [
                    ['value' => -2, 'label' => '매우 동의'],
                    ['value' => -1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' =>  1, 'label' => '반대'],
                    ['value' =>  2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.2,
                'sort_order' => 63,
                'is_active'  => true,
            ],
            [
                'question'   => '공공임대주택 비율을 현재보다 대폭 확대해야 한다.',
                'options'    => [
                    ['value' => -2, 'label' => '매우 동의'],
                    ['value' => -1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' =>  1, 'label' => '반대'],
                    ['value' =>  2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.1,
                'sort_order' => 64,
                'is_active'  => true,
            ],
            // ── 의료·보건 ────────────────────────────────
            [
                'question'   => '건강보험의 보장 범위를 넓히기 위해 보험료 인상도 감수해야 한다.',
                'options'    => [
                    ['value' => -2, 'label' => '매우 동의'],
                    ['value' => -1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' =>  1, 'label' => '반대'],
                    ['value' =>  2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.1,
                'sort_order' => 65,
                'is_active'  => true,
            ],
            [
                'question'   => '의료 시장에 민간 자본이 더 많이 참여할 수 있도록 허용해야 한다.',
                'options'    => [
                    ['value' =>  2, 'label' => '매우 동의'],
                    ['value' =>  1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.2,
                'sort_order' => 66,
                'is_active'  => true,
            ],
            // ── 군사·방위 ────────────────────────────────
            [
                'question'   => '방위산업 육성과 무기 수출은 국익에 도움이 된다.',
                'options'    => [
                    ['value' =>  2, 'label' => '매우 동의'],
                    ['value' =>  1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.0,
                'sort_order' => 67,
                'is_active'  => true,
            ],
            [
                'question'   => '주한미군 주둔 비용의 한국 부담 증가는 불가피하다.',
                'options'    => [
                    ['value' =>  2, 'label' => '매우 동의'],
                    ['value' =>  1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.1,
                'sort_order' => 68,
                'is_active'  => true,
            ],
            // ── 역사·정체성 ─────────────────────────────
            [
                'question'   => '친일 역사 청산 문제는 현재 시점에서 더 이상 중요한 과제가 아니다.',
                'options'    => [
                    ['value' =>  2, 'label' => '매우 동의'],
                    ['value' =>  1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' => -1, 'label' => '반대'],
                    ['value' => -2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.2,
                'sort_order' => 69,
                'is_active'  => true,
            ],
            [
                'question'   => '5·18 광주민주화운동의 역사적 의미를 헌법에 명시해야 한다.',
                'options'    => [
                    ['value' => -2, 'label' => '매우 동의'],
                    ['value' => -1, 'label' => '동의'],
                    ['value' =>  0, 'label' => '중립'],
                    ['value' =>  1, 'label' => '반대'],
                    ['value' =>  2, 'label' => '매우 반대'],
                ],
                'weight'     => 1.3,
                'sort_order' => 70,
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

        $this->command->info('ExtraPoliticalTests seeded: '.count($questions).' additional questions (total ~70)');
    }
}
