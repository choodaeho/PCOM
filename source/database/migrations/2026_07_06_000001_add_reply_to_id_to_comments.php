<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 답글의 답글 대상 추적 컬럼.
     *
     * parent_id  → 항상 최상위 댓글 ID (depth = 1 고정)
     * reply_to_id → 실제로 답글한 대상 댓글 ID (답글에 대한 답글일 때 설정)
     *
     * 예) 최상위 댓글 A
     *       답글 B (parent_id=A, reply_to_id=null)
     *       답글 C (parent_id=A, reply_to_id=B)  ← B에 달린 답글, A 스레드 소속
     */
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->foreignId('reply_to_id')
                ->nullable()
                ->after('parent_id')
                ->constrained('comments')
                ->nullOnDelete()
                ->comment('답글 대상 댓글 ID (답글의 답글일 때 설정)');
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->dropForeign(['reply_to_id']);
            $table->dropColumn('reply_to_id');
        });
    }
};
