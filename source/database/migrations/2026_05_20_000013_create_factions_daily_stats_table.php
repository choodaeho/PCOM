<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 진영별 일간 점수 통계 테이블.
     * Laravel Task Scheduling으로 매일 00:05에 전일 데이터 집계.
     *
     * 정규화 점수 공식:
     *   raw_score = post_count×3 + comment_count×1 + vote_up_count×2
     *             - vote_down_count×0.5 - report_count×5
     *   normalized_score = raw_score / max(active_user_count, 1)
     */
    public function up(): void
    {
        Schema::create('factions_daily_stats', function (Blueprint $table) {
            $table->id();

            $table->string('faction_type', 20)->comment('conservative | moderate | progressive');
            $table->date('stat_date')->comment('집계 기준일 (KST)');

            // 원시 집계 지표
            $table->unsignedInteger('post_count')->default(0)->comment('당일 게시물 수');
            $table->unsignedInteger('comment_count')->default(0)->comment('당일 댓글 수');
            $table->unsignedInteger('vote_up_count')->default(0)->comment('당일 받은 추천 수');
            $table->unsignedInteger('vote_down_count')->default(0)->comment('당일 받은 비추천 수');
            $table->unsignedInteger('report_count')->default(0)->comment('당일 신고 누적 수');
            $table->unsignedInteger('active_user_count')->default(0)->comment('당일 활동 사용자 수 (1회 이상 활동)');
            $table->unsignedInteger('new_user_count')->default(0)->comment('당일 신규 가입자 수');

            // 점수
            $table->decimal('raw_score', 12, 4)->default(0)->comment('가중합 원시 점수');
            $table->decimal('normalized_score', 10, 6)->default(0)->comment('활성 사용자 수로 나눈 정규화 점수');

            // 순위 (집계 시 산출)
            $table->unsignedTinyInteger('rank')->nullable()->comment('당일 진영 순위 (1~3)');

            $table->timestamp('calculated_at')->nullable()->comment('집계 완료 시각');
            $table->timestamps();

            // 진영+날짜 유니크 (하루에 진영당 1건)
            $table->unique(['faction_type', 'stat_date']);
        });

        DB::statement('CREATE INDEX idx_daily_stats_date ON factions_daily_stats (stat_date DESC)');
        DB::statement('CREATE INDEX idx_daily_stats_faction_date ON factions_daily_stats (faction_type, stat_date DESC)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factions_daily_stats');
    }
};
