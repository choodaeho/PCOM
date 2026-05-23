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
     * 진영별 월간 점수 통계 테이블.
     * factions_daily_stats를 매월 1일 00:10에 집계하여 저장.
     * DATE_TRUNC('month', stat_date) 기준으로 롤업.
     */
    public function up(): void
    {
        Schema::create('factions_monthly_stats', function (Blueprint $table) {
            $table->id();

            $table->string('faction_type', 20)->comment('conservative | moderate | progressive');

            /**
             * stat_year_month: 'YYYY-MM' 형식 문자열.
             * DATE 타입 대신 문자열로 저장하여 월 단위 집계를 명확하게 표현.
             * ex) '2026-05'
             */
            $table->char('stat_year_month', 7)->comment('집계 월 (YYYY-MM)');

            // 월간 합계
            $table->unsignedInteger('post_count')->default(0);
            $table->unsignedInteger('comment_count')->default(0);
            $table->unsignedInteger('vote_up_count')->default(0);
            $table->unsignedInteger('vote_down_count')->default(0);
            $table->unsignedInteger('report_count')->default(0);

            // 월간 평균 활성 사용자 수 (일간 평균)
            $table->decimal('avg_active_user_count', 8, 2)->default(0);
            $table->unsignedInteger('peak_active_user_count')->default(0)->comment('월중 최대 활성 사용자 수');

            // 점수 (일간 정규화 점수의 월 합산 및 평균)
            $table->decimal('total_raw_score', 14, 4)->default(0)->comment('월간 원시 점수 합계');
            $table->decimal('avg_normalized_score', 10, 6)->default(0)->comment('월간 일별 정규화 점수 평균');

            $table->unsignedTinyInteger('best_rank_in_month')->nullable()->comment('월중 1위를 차지한 일수로 산출한 최고 순위');

            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->unique(['faction_type', 'stat_year_month']);
        });

        DB::statement('CREATE INDEX idx_monthly_stats_year_month ON factions_monthly_stats (stat_year_month DESC)');
        DB::statement('CREATE INDEX idx_monthly_stats_faction ON factions_monthly_stats (faction_type, stat_year_month DESC)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factions_monthly_stats');
    }
};
