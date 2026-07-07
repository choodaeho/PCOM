<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auto_content_runs', function (Blueprint $table) {
            $table->id();

            $table->date('run_date')->comment('대상 날짜');
            $table->string('run_type', 20)->default('scheduled')
                ->comment('scheduled | manual | dry_run');
            $table->foreignId('triggered_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('수동 실행 시 관리자 ID');

            // 상태
            $table->string('status', 20)->default('running')
                ->comment('running | completed | failed');

            // 게시글 카운터
            $table->unsignedInteger('posts_dispatched')->default(0)->comment('큐 등록 수');
            $table->unsignedInteger('posts_succeeded')->default(0)->comment('성공 수');
            $table->unsignedInteger('posts_failed')->default(0)->comment('실패 수');

            // 댓글 카운터
            $table->unsignedInteger('comments_dispatched')->default(0)->comment('큐 등록 수');
            $table->unsignedInteger('comments_succeeded')->default(0)->comment('성공 수');
            $table->unsignedInteger('comments_failed')->default(0)->comment('실패 수');

            $table->timestamp('started_at')->comment('디스패치 시작');
            $table->timestamp('completed_at')->nullable()->comment('디스패치 완료');
            $table->timestamp('last_activity_at')->nullable()->comment('마지막 Job 완료 시각');

            $table->text('notes')->nullable()->comment('오류 메시지 등 부가 정보');

            $table->timestamps();

            $table->index(['run_date', 'status']);
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_content_runs');
    }
};
