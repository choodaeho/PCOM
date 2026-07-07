<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auto_content_runs', function (Blueprint $table) {
            $table->boolean('is_stopped')->default(false)->after('status')
                ->comment('관리자 중지 요청 플래그 — Job이 이 값을 확인하고 건너뜀');
            $table->timestamp('stopped_at')->nullable()->after('is_stopped')
                ->comment('중지 요청 시각');
            $table->unsignedInteger('posts_skipped')->default(0)->after('posts_failed')
                ->comment('중지로 인해 건너뛴 게시글 수');
            $table->unsignedInteger('comments_skipped')->default(0)->after('comments_failed')
                ->comment('중지로 인해 건너뛴 댓글 수');
        });
    }

    public function down(): void
    {
        Schema::table('auto_content_runs', function (Blueprint $table) {
            $table->dropColumn(['is_stopped', 'stopped_at', 'posts_skipped', 'comments_skipped']);
        });
    }
};
