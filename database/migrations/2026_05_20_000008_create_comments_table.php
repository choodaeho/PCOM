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
     */
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('post_id')
                ->constrained('posts')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            /**
             * parent_id: 대댓글 지원 (1-depth).
             * NULL이면 최상위 댓글, 값이 있으면 해당 댓글의 답글.
             */
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('comments')
                ->cascadeOnDelete()
                ->comment('대댓글 parent (null=최상위)');

            /**
             * faction: 작성 당시 진영 스냅샷 (posts와 동일 정책).
             */
            $table->string('faction', 20)->comment('작성 당시 진영 스냅샷: conservative | moderate | progressive');

            $table->text('content')->comment('댓글 본문');
            $table->boolean('is_anonymous')->default(false);

            $table->string('status', 20)->default('published')->comment('published | hidden | deleted_by_admin');

            // 비정규화 카운터
            $table->unsignedInteger('vote_up_count')->default(0);
            $table->unsignedInteger('vote_down_count')->default(0);
            $table->unsignedInteger('report_count')->default(0);
            $table->unsignedInteger('reply_count')->default(0)->comment('대댓글 수');

            $table->softDeletes();
            $table->timestamps();
        });

        DB::statement('CREATE INDEX idx_comments_post_id ON comments (post_id, created_at ASC) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX idx_comments_user_id ON comments (user_id) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX idx_comments_parent_id ON comments (parent_id) WHERE parent_id IS NOT NULL AND deleted_at IS NULL');
        DB::statement('CREATE INDEX idx_comments_faction ON comments (faction, created_at DESC) WHERE deleted_at IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
