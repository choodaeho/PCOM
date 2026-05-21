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
     * 진영별 연간 점수 통계 테이블.
     * factions_monthly_stats를 매년 1월 1일 00:15에 집계하여 저장.
     */
    public function up(): void
    {
        Schema::create('factions_yearly_stats', function (Blueprint $table) {
            $table->id();

            $table->string('faction_type', 20)->comment('conservative | moderate | progressive');
            $table->unsignedSmallInteger('stat_year')->comment('집계 연도 (ex: 2026)');

            // 연간 합계
            $table->unsignedBigInteger('post_count')->default(0);
            $table->unsignedBigInteger('comment_count')->default(0);
            $table->unsignedBigInteger('vote_up_count')->default(0);
            $table->unsignedBigInteger('vote_down_count')->default(0);
            $table->unsignedBigInteger('report_count')->default(0);

            // 연간 평균 활성 사용자 수 (월간 평균의 평균)
            $table->decimal('avg_active_user_count', 10, 2)->default(0);
            $table->unsignedInteger('peak_active_user_count')->default(0)->comment('연중 최대 활성 사용자 수');

            // 점수
            $table->decimal('total_raw_score', 16, 4)->default(0)->comment('연간 원시 점수 합계');
            $table->decimal('avg_normalized_score', 10, 6)->default(0)->comment('연간 월별 정규화 점수 평균');

            // 연간 챔피언 통계
            $table->unsignedTinyInteger('months_as_top_faction')->default(0)->comment('연중 1위였던 월수 (0~12)');

            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->unique(['faction_type', 'stat_year']);
        });

        DB::statement('CREATE INDEX idx_yearly_stats_year ON factions_yearly_stats (stat_year DESC)');
        DB::statement('CREATE INDEX idx_yearly_stats_faction ON factions_yearly_stats (faction_type, stat_year DESC)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factions_yearly_stats');
    }
};
