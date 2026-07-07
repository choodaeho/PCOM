<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auto_content_configs', function (Blueprint $table) {
            $table->id();
            $table->string('gemini_api_key', 255)->default('');
            $table->boolean('is_enabled')->default(false);

            // 생성 수량
            $table->unsignedSmallInteger('posts_per_faction')->default(100); // 진영별 일일 게시글 수
            $table->unsignedSmallInteger('comments_per_post_min')->default(1);
            $table->unsignedSmallInteger('comments_per_post_max')->default(3);

            // 시간 분배 (24시간제)
            $table->unsignedTinyInteger('start_hour')->default(6);  // 06:00
            $table->unsignedTinyInteger('end_hour')->default(24);   // 24:00 (자정)

            // 진영별 타겟 게시판 슬러그 목록
            // {"conservative": ["conservative-azit", "battle-politics"], ...}
            $table->jsonb('target_boards')->nullable();

            // 진영별 주제 키워드 목록
            // {"conservative": ["경제성장", "안보"], "moderate": [...], "progressive": [...]}
            $table->jsonb('topics')->nullable();

            // 실행 이력
            $table->timestamp('last_run_at')->nullable();
            $table->jsonb('last_run_stats')->nullable();
            // {"posts_dispatched": 300, "comments_dispatched": 520, "target_date": "2026-07-06"}

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_content_configs');
    }
};
