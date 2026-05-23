<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 진영 점수 계산 가중치 테이블.
     * 공식: (post×3 + comment×1 + vote_up×2 - vote_down×0.5 - report×5) ÷ active_user_count
     */
    public function up(): void
    {
        Schema::create('score_weights', function (Blueprint $table) {
            $table->id();

            /**
             * action_type 종류:
             *   post       : 게시물 작성
             *   comment    : 댓글 작성
             *   vote_up    : 추천 받음
             *   vote_down  : 비추천 받음
             *   report     : 신고 누적 (감점)
             */
            $table->string('action_type', 30)->unique()->comment('점수 액션 유형');
            $table->decimal('weight', 6, 2)->comment('가중치 (음수=감점)');
            $table->string('description', 200)->nullable()->comment('관리자용 설명');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('score_weights');
    }
};
