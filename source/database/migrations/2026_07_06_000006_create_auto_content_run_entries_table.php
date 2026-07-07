<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auto_content_run_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('run_id')
                ->constrained('auto_content_runs')
                ->cascadeOnDelete();

            $table->string('entry_type', 20)->comment('post | comment');

            // 작성자 정보 (스냅샷)
            $table->string('faction', 20)->nullable()->comment('conservative | moderate | progressive');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nickname', 50)->nullable();

            // 게시판 / 게시글 정보
            $table->string('board_slug', 100)->nullable();
            $table->string('board_name', 100)->nullable();
            $table->foreignId('post_id')->nullable()->constrained('posts')->nullOnDelete();
            $table->unsignedBigInteger('parent_post_id')->nullable()
                ->comment('댓글인 경우 원본 게시글 ID');

            // 내용 스냅샷
            $table->string('topic', 300)->nullable();
            $table->string('title', 300)->nullable();

            // 결과
            $table->string('status', 20)->default('success')
                ->comment('success | failed | skipped');
            $table->text('error_message')->nullable();
            $table->unsignedInteger('duration_ms')->nullable()->comment('실행 소요 시간 (ms)');

            // 스케줄 / 실행 시각
            $table->timestamp('scheduled_at')->nullable()->comment('Job 발행 예약 시각');
            $table->timestamp('executed_at')->nullable()->comment('실제 실행 시각');

            $table->timestamps();

            // 인덱스
            $table->index(['run_id', 'entry_type']);
            $table->index(['run_id', 'status']);
            $table->index(['run_id', 'faction']);
            $table->index('executed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_content_run_entries');
    }
};
