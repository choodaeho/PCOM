<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScoreWeightsSeeder extends Seeder
{
    /**
     * 진영 점수 가중치 초기 데이터 시드.
     *
     * 정규화 점수 공식:
     *   raw_score = Σ(activity_count × weight)
     *   normalized_score = raw_score ÷ max(active_user_count, 1)
     *
     * 관리자 페이지에서 weight 값을 조정하면 점수 체계가 실시간 변경됨.
     */
    public function run(): void
    {
        $now = now();

        $weights = [
            [
                'action_type' => 'post',
                'weight'      => 3.00,
                'description' => '게시물 작성 (양질의 콘텐츠 생산에 높은 가중치)',
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'action_type' => 'comment',
                'weight'      => 1.00,
                'description' => '댓글 작성 (토론 참여 기여)',
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'action_type' => 'vote_up',
                'weight'      => 2.00,
                'description' => '추천 받음 (커뮤니티 긍정 평가)',
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'action_type' => 'vote_down',
                'weight'      => -0.50,
                'description' => '비추천 받음 (소폭 감점, 과도한 패널티 방지)',
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'action_type' => 'report',
                'weight'      => -5.00,
                'description' => '신고 누적 (혐오 표현·규정 위반에 강한 패널티)',
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ];

        // upsert: 이미 존재하면 weight/description만 업데이트
        DB::table('score_weights')->upsert(
            $weights,
            uniqueBy: ['action_type'],
            update: ['weight', 'description', 'updated_at'],
        );

        $this->command->info('✅ ScoreWeightsSeeder: 점수 가중치 5개 시드 완료');
        $this->command->table(
            ['action_type', 'weight', 'description'],
            collect($weights)->map(fn ($w) => [
                $w['action_type'],
                $w['weight'],
                $w['description'],
            ])->toArray()
        );
    }
}
