<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * 데이터베이스 시드 실행.
     *
     * 실행 순서:
     *   1. ScoreWeightsSeeder  - 진영 점수 가중치 기본값
     *   2. (추가 예정) PoliticalTestsSeeder - 성향 테스트 문항
     *   3. (추가 예정) BoardsSeeder         - 기본 게시판 구성
     *   4. (추가 예정) AdminUserSeeder      - 최초 슈퍼관리자 계정
     */
    public function run(): void
    {
        $this->call([
            ScoreWeightsSeeder::class,
            PoliticalTestsSeeder::class,
            BoardsSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
