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
     * Polymorphic 신고 테이블.
     * posts, comments 모두 동일 테이블로 처리.
     */
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('reporter_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('신고한 사용자');

            // Polymorphic 관계
            $table->morphs('reportable'); // reportable_id + reportable_type

            /**
             * reason 분류:
             *   hate_speech    : 혐오 발언
             *   misinformation : 허위 정보
             *   spam           : 스팸/광고
             *   obscene        : 음란물
             *   other          : 기타
             */
            $table->string('reason', 30)->comment('신고 사유: hate_speech | misinformation | spam | obscene | other');
            $table->string('detail', 500)->nullable()->comment('기타 사유 상세 입력');

            /**
             * status:
             *   pending   : 검토 대기
             *   reviewed  : 검토 완료 (조치 없음)
             *   actioned  : 처리 완료 (콘텐츠 숨김/사용자 제재)
             *   dismissed : 기각
             */
            $table->string('status', 20)->default('pending')->comment('pending | reviewed | actioned | dismissed');

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('검토한 관리자');

            $table->timestamp('reviewed_at')->nullable();
            $table->string('admin_note', 500)->nullable()->comment('관리자 처리 메모');

            $table->timestamps();

            // 동일 사용자 중복 신고 방지
            $table->unique(['reporter_id', 'reportable_id', 'reportable_type']);
        });

        DB::statement('CREATE INDEX idx_reports_status ON reports (status, created_at DESC)');
        DB::statement('CREATE INDEX idx_reports_reportable ON reports (reportable_type, reportable_id)');
        DB::statement('CREATE INDEX idx_reports_reporter ON reports (reporter_id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
