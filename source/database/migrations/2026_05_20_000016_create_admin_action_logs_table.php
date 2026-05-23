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
     * 관리자 액션 감사 로그 테이블.
     * 모든 관리자 행위를 기록하여 책임 추적 및 감사에 활용.
     */
    public function up(): void
    {
        Schema::create('admin_action_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('admin_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('액션을 수행한 관리자');

            /**
             * action_type 예시:
             *   user.suspend         : 사용자 정지
             *   user.ban             : 사용자 영구 차단
             *   user.activate        : 사용자 활성화
             *   post.hide            : 게시물 숨김
             *   post.delete          : 게시물 삭제
             *   comment.hide         : 댓글 숨김
             *   board.create         : 게시판 생성
             *   board.delete         : 게시판 삭제
             *   report.action        : 신고 처리
             *   score_weight.update  : 점수 가중치 변경
             *   poll.create          : 투표 생성
             *   poll.close           : 투표 종료
             */
            $table->string('action_type', 60)->comment('수행한 액션 유형 (점 표기법)');

            // 대상 엔티티 (Polymorphic-like, nullable)
            $table->string('target_type', 60)->nullable()->comment('대상 모델명 (App\\Models\\User 등)');
            $table->unsignedBigInteger('target_id')->nullable()->comment('대상 레코드 ID');

            /**
             * payload JSONB: 액션 상세 정보 및 변경 전/후 값.
             * {
             *   "before": {"status": "active"},
             *   "after":  {"status": "suspended"},
             *   "reason": "욕설 반복 사용",
             *   "duration_days": 7
             * }
             */
            $table->jsonb('payload')->nullable()->comment('액션 상세 데이터 (before/after/reason 등)');

            $table->string('ip_address', 45)->nullable()->comment('관리자 접속 IP');
            $table->string('user_agent', 500)->nullable();

            $table->timestamp('created_at')->useCurrent();

            // 로그는 수정/삭제 불가 (updated_at 없음, 소프트 삭제 없음)
        });

        DB::statement('CREATE INDEX idx_admin_logs_admin_id ON admin_action_logs (admin_id, created_at DESC)');
        DB::statement('CREATE INDEX idx_admin_logs_action_type ON admin_action_logs (action_type, created_at DESC)');
        DB::statement('CREATE INDEX idx_admin_logs_target ON admin_action_logs (target_type, target_id) WHERE target_type IS NOT NULL');
        DB::statement('CREATE INDEX idx_admin_logs_payload_gin ON admin_action_logs USING gin (payload) WHERE payload IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_action_logs');
    }
};
