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
                'order'           => 1,
                'is_active'       => true,
            ],
            [
                'name'            => '중도 아지트',
                'slug'            => 'moderate-azit',
                'description'     => '중도 진영만의 자유로운 토론 공간',
                'board_type'      => BoardType::Azit,
                'allowed_faction' => 'moderate',
                'order'           => 2,
                'is_active'       => true,
            ],
            [
                'name'            => '진보 아지트',
                'slug'            => 'progressive-azit',
                'description'     => '진보 진영만의 자유로운 토론 공간',
                'board_type'      => BoardType::Azit,
                'allowed_faction' => 'progressive',
                'order'           => 3,
                'is_active'       => true,
            ],

            // ── 전쟁터 (모든 진영) ─────────────────────────────
            [
                'name'            => '정치 전쟁터',
                'slug'            => 'battle-politics',
                'description'     => '진영을 초월한 정치 토론 전쟁터',
                'board_type'      => BoardType::Battle,
                'allowed_faction' => 'all',
                'order'           => 10,
                'is_active'       => true,
            ],
            [
                'name'            => '경제 전쟁터',
                'slug'            => 'battle-economy',
                'description'     => '경제 정책을 둘러싼 진영 간 논쟁',
                'board_type'      => BoardType::Battle,
                'allowed_faction' => 'all',
                'order'           => 11,
                'is_active'       => true,
            ],
            [
                'name'            => '사회 전쟁터',
                'slug'            => 'battle-society',
                'description'     => '사회 이슈에 대한 다양한 시각',
                'board_type'      => BoardType::Battle,
                'allowed_faction' => 'all',
                'order'           => 12,
                'is_active'       => true,
            ],

            // ── 공지사항 ─────────────────────────────────────
            [
                'name'            => '공지사항',
                'slug'            => 'notice',
                'description'     => '폴릿 운영 공지 및 업데이트 안내',
                'board_type'      => BoardType::Notice,
                'allowed_faction' => 'all',
                'order'           => 0,
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
