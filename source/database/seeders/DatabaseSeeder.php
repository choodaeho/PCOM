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
     *   1. ScoreWeightsSeeder      — 진영 점수 가중치 기본값
     *   2. PoliticalTestsSeeder    — 성향 테스트 문항 (10개)
     *   3. MorePoliticalTestsSeeder — 성향 테스트 추가 문항 (30개, 총 40개)
     *   4. ExtraPoliticalTestsSeeder— 성향 테스트 추가 문항 (30개, 총 70개)
     *   5. BoardsSeeder             — 기본 게시판 구성 (아지트 3 + 전쟁터 3 + 놀이터 8 + 공지 1)
     *   6. AdminUserSeeder          — 슈퍼관리자 계정 (pcomDaeho)
     *   7. TestAccountsSeeder       — 테스트 계정 90개 (보수/중도/진보 각 30개)
     *   8. LegalDocumentsSeeder     — 이용약관 / 개인정보처리방침 초기 데이터
     */
    public function run(): void
    {
        $this->call([
            ScoreWeightsSeeder::class,
            PoliticalTestsSeeder::class,
            MorePoliticalTestsSeeder::class,
            ExtraPoliticalTestsSeeder::class,
            BoardsSeeder::class,
            AdminUserSeeder::class,
            TestAccountsSeeder::class,
            LegalDocumentsSeeder::class,
        ]);
    }
}
