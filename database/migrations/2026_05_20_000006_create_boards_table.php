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
     * 생성형 게시판 테이블.
     * 관리자가 코드 변경 없이 DB를 통해 게시판을 동적 생성/관리.
     */
    public function up(): void
    {
        Schema::create('boards', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique()->comment('URL용 식별자 (영문+하이픈, ex: economy-debate)');
            $table->string('name', 100)->comment('게시판 표시명');
            $table->string('description', 500)->nullable()->comment('게시판 설명 (AI 생성 가능)');
            $table->string('icon', 10)->nullable()->comment('이모지 아이콘');

            /**
             * board_type:
             *   azit       : 아지트 (진영 전용 접근)
             *   battle     : 전쟁터 (전 진영 공용)
             *   notice     : 공지사항 (관리자 전용 작성)
             */
            $table->string('board_type', 20)->default('battle')->comment('azit | battle | notice');

            /**
             * allowed_faction:
             *   all          : 전 진영 접근 가능
             *   conservative : 보수 아지트
             *   moderate     : 중도 아지트
             *   progressive  : 진보 아지트
             */
            $table->string('allowed_faction', 20)->default('all')->comment('접근 가능 진영 (all | conservative | moderate | progressive)');

            $table->unsignedSmallInteger('sort_order')->default(0)->comment('사이드바/탭 표시 순서');
            $table->boolean('is_active')->default(true)->comment('비활성화 시 목록에서 숨김');
            $table->boolean('allow_anonymous')->default(false)->comment('익명 게시 허용 여부');
            $table->unsignedInteger('post_count')->default(0)->comment('게시물 수 (비정규화 카운터)');

            // 관리자 메모
            $table->text('admin_memo')->nullable()->comment('관리자 내부 메모');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('생성한 관리자 user_id');

            $table->softDeletes();
            $table->timestamps();
        });

        DB::statement('CREATE INDEX idx_boards_type_faction ON boards (board_type, allowed_faction) WHERE is_active = true AND deleted_at IS NULL');
        DB::statement('CREATE INDEX idx_boards_sort ON boards (sort_order) WHERE is_active = true AND deleted_at IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boards');
    }
};
