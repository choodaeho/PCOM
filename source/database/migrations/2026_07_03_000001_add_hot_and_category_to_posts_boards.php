<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 인기글(is_hot) 시스템 + 카테고리(말머리) 시스템 추가
 *
 * posts:
 *   - is_hot      boolean — 인기글 등재 여부 (추천 threshold 초과 시 자동 설정)
 *   - category    varchar(50) — 말머리/카테고리
 *
 * boards:
 *   - categories  jsonb — 게시판 고유 카테고리 목록 (예: ["뉴스","정책","기타"])
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── posts 컬럼 추가 ────────────────────────────────────────────
        Schema::table('posts', function (Blueprint $table): void {
            $table->boolean('is_hot')->default(false)->after('is_notice');
            $table->string('category', 50)->nullable()->after('is_hot');

            // 인기글 목록 + 카테고리 필터 쿼리 최적화
            $table->index(['is_hot', 'created_at'],    'idx_posts_is_hot');
            $table->index(['board_id', 'category'],    'idx_posts_board_category');
        });

        // ── boards 컬럼 추가 ───────────────────────────────────────────
        Schema::table('boards', function (Blueprint $table): void {
            $table->jsonb('categories')->nullable()->after('description');
        });

        // ── 기존 게시글 is_hot 소급 적용 ──────────────────────────────
        // 전쟁터(battle) : 추천 10개 이상
        DB::statement("
            UPDATE posts
               SET is_hot = true
             WHERE status = 'published'
               AND board_id IN (SELECT id FROM boards WHERE board_type = 'battle')
               AND vote_up_count >= 10
        ");
        // 아지트(azit) / 놀이터(playground) / 공지(notice) : 추천 5개 이상
        DB::statement("
            UPDATE posts
               SET is_hot = true
             WHERE status = 'published'
               AND board_id IN (SELECT id FROM boards WHERE board_type != 'battle')
               AND vote_up_count >= 5
        ");

        // ── 게시판별 기본 카테고리 시드 ────────────────────────────────
        $categoryMap = [
            // 전쟁터
            'battle-politics'     => ['정치일반', '대선/총선', '정당', '정책', '외교', '기타'],
            'battle-economy'      => ['경제정책', '부동산', '노동', '세금', '산업', '기타'],
            'battle-society'      => ['사회현안', '교육', '복지', '인권', '환경', '기타'],
            // 아지트
            'conservative-azit'   => ['토론', '뉴스공유', '정보', '잡담', '기타'],
            'moderate-azit'       => ['토론', '뉴스공유', '정보', '잡담', '기타'],
            'progressive-azit'    => ['토론', '뉴스공유', '정보', '잡담', '기타'],
            // 놀이터
            'play-humor'          => ['짤방', '유머', '영상', '기타'],
            'play-game'           => ['PC게임', '모바일', '콘솔', '기타'],
            'play-sports'         => ['축구', '야구', '농구', '기타'],
            'play-entertainment'  => ['드라마', '영화', '아이돌', '기타'],
            'play-stock'          => ['주식', '코인', '부동산', '기타'],
            'play-it'             => ['뉴스', '리뷰', '질문', '기타'],
            'play-food'           => ['식당', '레시피', '먹방', '기타'],
            'play-free'           => ['자유', '질문', '정보', '기타'],
            // notice: 카테고리 없음
        ];

        foreach ($categoryMap as $slug => $cats) {
            DB::table('boards')
                ->where('slug', $slug)
                ->update(['categories' => json_encode($cats, JSON_UNESCAPED_UNICODE)]);
        }
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropIndex('idx_posts_is_hot');
            $table->dropIndex('idx_posts_board_category');
            $table->dropColumn(['is_hot', 'category']);
        });

        Schema::table('boards', function (Blueprint $table): void {
            $table->dropColumn('categories');
        });
    }
};
