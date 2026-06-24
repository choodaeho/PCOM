<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\FactionType;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestAccountsSeeder extends Seeder
{
    /**
     * 정치 성향별 테스트 계정 시드.
     *
     * 보수 30개 / 중도 30개 / 진보 30개 = 총 90개
     *
     * 공통 비밀번호 : fusion!@34
     * 이메일 패턴  : {faction}{num:02d}@test.polit.kr
     * 닉네임       : 각 진영 성향에 맞는 개성 있는 닉네임 (30개 큐레이션)
     *
     * ⚠️  이 계정들은 user_type = 'test' 로 관리되므로
     *     운영 통계에서 필터링할 수 있습니다.
     */

    // ── 보수 진영 닉네임 (30개) ────────────────────────────────────────────────
    private const CONSERVATIVE_NICKNAMES = [
        '자유대한',     '태극전사',     '나라사랑해',   '국가수호대',   '애국청년단',
        '자유파수꾼',   '보수의한방',   '전통가치론',   '강철안보론',   '자유시장파',
        '경제성장론',   '작은정부론',   '한미동맹론',   '보수논객킹',   '자유민주파',
        '대한파수꾼',   '우파의목소리', '보수혁신파',   '국익우선론',   '자유경제파',
        '성장주의자',   '보수왕도',     '전통수호자',   '우파전사단',   '보수정론직',
        '안보제일론',   '자유우파론',   '경제자유론',   '나라지킴이',   '한국보수파',
    ];

    // ── 중도 진영 닉네임 (30개) ────────────────────────────────────────────────
    private const MODERATE_NICKNAMES = [
        '균형잡는자',   '합리주의자',   '중립지대인',   '실용주의자',   '균형감각론',
        '이성주의자',   '중도의힘',     '합리선택자',   '열린사고자',   '공정시각론',
        '중도현실론',   '실용론자',     '균형발전론',   '합리개혁론',   '중도실용론',
        '팩트체크왕',   '이성시민론',   '합리사고인',   '중립적시각',   '균형정치론',
        '실용접근자',   '중도개혁론',   '합리경제론',   '균형사회론',   '현실주의자',
        '이성균형론',   '합리논객론',   '중도유권자',   '균형의소리',   '실용개혁론',
    ];

    // ── 진보 진영 닉네임 (30개) ────────────────────────────────────────────────
    private const PROGRESSIVE_NICKNAMES = [
        '평등사회론',   '노동자의꿈',   '복지국가론',   '민중의목소리', '환경지킴이',
        '사회정의론',   '평등세상론',   '진보의날개',   '노동연대론',   '복지확대론',
        '민주시민론',   '평등권리론',   '사회개혁론',   '진보전진론',   '노동권리자',
        '환경보호자',   '사회연대론',   '민중연대론',   '복지천국론',   '진보시민론',
        '노동존중론',   '평등국가론',   '민주개혁론',   '진보의길',     '사회적가치',
        '노동의벗',     '복지확장론',   '평등시민론',   '민주진보론',   '사회변혁론',
    ];

    public function run(): void
    {
        $password = Hash::make('fusion!@34');

        $factions = [
            [
                'type'       => FactionType::Conservative,
                'prefix'     => 'conservative',
                'label'      => '보수',
                'base_score' => 60,
                'nicknames'  => self::CONSERVATIVE_NICKNAMES,
            ],
            [
                'type'       => FactionType::Moderate,
                'prefix'     => 'moderate',
                'label'      => '중도',
                'base_score' => 0,
                'nicknames'  => self::MODERATE_NICKNAMES,
            ],
            [
                'type'       => FactionType::Progressive,
                'prefix'     => 'progressive',
                'label'      => '진보',
                'base_score' => -60,
                'nicknames'  => self::PROGRESSIVE_NICKNAMES,
            ],
        ];

        $totalCreated = 0;

        foreach ($factions as $faction) {
            $created = 0;

            foreach ($faction['nicknames'] as $idx => $nickname) {
                $num   = str_pad((string) ($idx + 1), 2, '0', STR_PAD_LEFT);
                $email = "{$faction['prefix']}{$num}@test.polit.kr";

                // 점수를 약간씩 분산해 다양성 부여
                $scoreOffset = match($faction['type']) {
                    FactionType::Conservative => ($idx % 5) * 3,           // 60~72
                    FactionType::Moderate     => (($idx % 5) - 2) * 5,     // -10~+10
                    FactionType::Progressive  => -(($idx % 5) * 3),        // -60~-72
                };

                User::firstOrCreate(
                    ['email' => $email],
                    [
                        'nickname'          => $nickname,
                        'password'          => $password,
                        'political_type'    => $faction['type'],
                        'test_score'        => $faction['base_score'] + $scoreOffset,
                        'status'            => UserStatus::Active,
                        'user_type'         => UserType::Test,
                        'is_admin'          => false,
                        'test_completed_at' => now(),
                        'email_verified_at' => now(),
                        'manner_score'      => 100,
                    ]
                );

                $created++;
            }

            $totalCreated += $created;
            $this->command->info("✅ {$faction['label']} 테스트 계정 {$created}개 생성 완료");
        }

        $this->command->info("📊 총 {$totalCreated}개 테스트 계정 시드 완료");
        $this->command->line("   공통 비밀번호 : fusion!@34");
        $this->command->line("   이메일 패턴  : {faction}{01~30}@test.polit.kr");
        $this->command->warn('⚠️  이 계정들은 user_type=test 로 관리됩니다. 운영 통계에서 제외 처리 필요.');
    }
}
