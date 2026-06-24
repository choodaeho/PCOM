<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BoardType;
use App\Models\Board;
use Illuminate\Database\Seeder;

class BoardsSeeder extends Seeder
{
    /**
     * 기본 게시판 시드.
     */
    public function run(): void
    {
        $boards = [
            // ── 아지트 (진영 전용) ──────────────────────────────
            [
                'name'            => '보수 아지트',
                'slug'            => 'conservative-azit',
                'description'     => '보수 진영만의 자유로운 토론 공간',
                'board_type'      => BoardType::Azit,
                'allowed_faction' => 'conservative',
                'sort_order'      => 1,
                'is_active'       => true,
            ],
            [
                'name'            => '중도 아지트',
                'slug'            => 'moderate-azit',
                'description'     => '중도 진영만의 자유로운 토론 공간',
                'board_type'      => BoardType::Azit,
                'allowed_faction' => 'moderate',
                'sort_order'      => 2,
                'is_active'       => true,
            ],
            [
                'name'            => '진보 아지트',
                'slug'            => 'progressive-azit',
                'description'     => '진보 진영만의 자유로운 토론 공간',
                'board_type'      => BoardType::Azit,
                'allowed_faction' => 'progressive',
                'sort_order'      => 3,
                'is_active'       => true,
            ],

            // ── 전쟁터 (모든 진영) ─────────────────────────────
            [
                'name'            => '정치 전쟁터',
                'slug'            => 'battle-politics',
                'description'     => '진영을 초월한 정치 토론 전쟁터',
                'board_type'      => BoardType::Battle,
                'allowed_faction' => 'all',
                'sort_order'      => 10,
                'is_active'       => true,
            ],
            [
                'name'            => '경제 전쟁터',
                'slug'            => 'battle-economy',
                'description'     => '경제 정책을 둘러싼 진영 간 논쟁',
                'board_type'      => BoardType::Battle,
                'allowed_faction' => 'all',
                'sort_order'      => 11,
                'is_active'       => true,
            ],
            [
                'name'            => '사회 전쟁터',
                'slug'            => 'battle-society',
                'description'     => '사회 이슈에 대한 다양한 시각',
                'board_type'      => BoardType::Battle,
                'allowed_faction' => 'all',
                'sort_order'      => 12,
                'is_active'       => true,
            ],

            // ── 놀이터 (진영 무관 자유 게시판) ─────────────────
            [
                'name'            => '유머/짤방',
                'slug'            => 'play-humor',
                'description'     => '웃긴 짤과 유머로 스트레스 해소! 정치 무관 누구나 참여 가능',
                'board_type'      => BoardType::Playground,
                'allowed_faction' => 'all',
                'sort_order'      => 20,
                'is_active'       => true,
            ],
            [
                'name'            => '게임',
                'slug'            => 'play-game',
                'description'     => '게임 공략·리뷰·커뮤니티. PC/모바일/콘솔 모두 환영',
                'board_type'      => BoardType::Playground,
                'allowed_faction' => 'all',
                'sort_order'      => 21,
                'is_active'       => true,
            ],
            [
                'name'            => '스포츠',
                'slug'            => 'play-sports',
                'description'     => '야구·축구·농구·E스포츠 실시간 반응과 하이라이트',
                'board_type'      => BoardType::Playground,
                'allowed_faction' => 'all',
                'sort_order'      => 22,
                'is_active'       => true,
            ],
            [
                'name'            => '방송/연예',
                'slug'            => 'play-entertainment',
                'description'     => '드라마·예능·아이돌·영화 실시간 반응과 정보 공유',
                'board_type'      => BoardType::Playground,
                'allowed_faction' => 'all',
                'sort_order'      => 23,
                'is_active'       => true,
            ],
            [
                'name'            => '주식/코인',
                'slug'            => 'play-stock',
                'description'     => '주식·부동산·코인 정보와 투자 이야기. 수익 인증도 환영',
                'board_type'      => BoardType::Playground,
                'allowed_faction' => 'all',
                'sort_order'      => 24,
                'is_active'       => true,
            ],
            [
                'name'            => 'IT/테크',
                'slug'            => 'play-it',
                'description'     => '스마트폰·AI·개발·가젯. 최신 IT 이슈를 가장 빠르게',
                'board_type'      => BoardType::Playground,
                'allowed_faction' => 'all',
                'sort_order'      => 25,
                'is_active'       => true,
            ],
            [
                'name'            => '먹방/맛집',
                'slug'            => 'play-food',
                'description'     => '맛집 후기, 레시피, 먹방 추천. 먹는 것만큼은 하나가 되자',
                'board_type'      => BoardType::Playground,
                'allowed_faction' => 'all',
                'sort_order'      => 26,
                'is_active'       => true,
            ],
            [
                'name'            => '자유게시판',
                'slug'            => 'play-free',
                'description'     => '어떤 주제도 OK. 하고 싶은 말 다 해요',
                'board_type'      => BoardType::Playground,
                'allowed_faction' => 'all',
                'sort_order'      => 27,
                'is_active'       => true,
            ],

            // ── 공지사항 ─────────────────────────────────────
            [
                'name'            => '공지사항',
                'slug'            => 'notice',
                'description'     => '폴릿 운영 공지 및 업데이트 안내',
                'board_type'      => BoardType::Notice,
                'allowed_faction' => 'all',
                'sort_order'      => 0,
                'is_active'       => true,
            ],
        ];

        foreach ($boards as $data) {
            Board::firstOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }

        $this->command->info('Boards seeded: '.count($boards).' boards');
    }
}
