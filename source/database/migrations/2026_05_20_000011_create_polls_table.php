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
     * 실시간 투표(The Poll) 테이블.
     * 전쟁터 상단에 시사 이슈 투표를 배치, 진영별 표 차이를 시각화.
     */
    public function up(): void
    {
        Schema::create('polls', function (Blueprint $table) {
            $table->id();

            $table->string('title', 300)->comment('투표 질문 제목');
            $table->string('description', 1000)->nullable()->comment('투표 설명/배경');

            /**
             * options JSONB 구조:
             * [
             *   {"id": 1, "label": "찬성", "vote_count": 0},
             *   {"id": 2, "label": "반대", "vote_count": 0},
             *   {"id": 3, "label": "모르겠다", "vote_count": 0}
             * ]
             * vote_count는 poll_votes 집계 후 비정규화 업데이트.
             */
            $table->jsonb('options')->comment('투표 선택지 배열 [{id, label, vote_count}]');

            $table->boolean('is_active')->default(true)->comment('현재 활성 투표 여부');
            $table->timestamp('starts_at')->nullable()->comment('투표 시작 시각 (null=즉시)');
            $table->timestamp('ends_at')->nullable()->comment('투표 종료 시각 (null=무기한)');

            // 총 투표 수 (비정규화)
            $table->unsignedInteger('total_vote_count')->default(0);

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('생성한 관리자');

            $table->softDeletes();
            $table->timestamps();
        });

        DB::statement('CREATE INDEX idx_polls_active ON polls (is_active, ends_at) WHERE is_active = true AND deleted_at IS NULL');
        DB::statement('CREATE INDEX idx_polls_options_gin ON polls USING gin (options)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('polls');
    }
};
