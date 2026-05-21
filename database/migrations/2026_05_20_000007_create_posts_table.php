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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('board_id')
                ->constrained('boards')
                ->cascadeOnDelete();

            /**
             * faction: 작성 당시 사용자의 진영을 스냅샷으로 저장.
             * 추후 사용자 진영이 변경되더라도 게시글에는 원래 진영이 유지됨.
             */
            $table->string('faction', 20)->comment('작성 당시 진영 스냅샷: conservative | moderate | progressive');

            $table->string('title', 300)->comment('게시글 제목');
            $table->text('content')->comment('게시글 본문 (Markdown/HTML)');

            /**
             * 첨부 파일/이미지 JSONB 구조:
             * [
             *   {"type": "image", "url": "https://...", "name": "photo.jpg", "size": 102400},
             *   {"type": "file",  "url": "https://...", "name": "document.pdf", "size": 204800}
             * ]
             */
            $table->jsonb('attachments')->nullable()->comment('첨부 파일 메타 배열 (JSONB)');

            // 상태
            $table->string('status', 20)->default('published')->comment('published | hidden | deleted_by_admin');
            $table->boolean('is_notice')->default(false)->comment('공지 고정 여부');
            $table->boolean('is_anonymous')->default(false)->comment('익명 게시 여부');

            // 비정규화 카운터 (COUNT 쿼리 회피)
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('comment_count')->default(0);
            $table->unsignedInteger('vote_up_count')->default(0);
            $table->unsignedInteger('vote_down_count')->default(0);
            $table->unsignedInteger('report_count')->default(0);

            // 전문 검색(Full-Text Search)용 tsvector 컬럼
            $table->specificType('search_vector', 'tsvector')->nullable()->comment('FTS 벡터 (title + content)');

            $table->softDeletes();
            $table->timestamps();
        });

        // 기본 인덱스
        DB::statement('CREATE INDEX idx_posts_board_id ON posts (board_id, created_at DESC) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX idx_posts_user_id ON posts (user_id) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX idx_posts_faction ON posts (faction, created_at DESC) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX idx_posts_status ON posts (status) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX idx_posts_is_notice ON posts (board_id, is_notice) WHERE is_notice = true AND deleted_at IS NULL');

        // FTS GIN 인덱스
        DB::statement('CREATE INDEX idx_posts_search_vector ON posts USING gin (search_vector)');

        // FTS 자동 업데이트 트리거
        DB::statement("
            CREATE OR REPLACE FUNCTION posts_search_vector_update() RETURNS trigger AS \$\$
            BEGIN
                NEW.search_vector :=
                    setweight(to_tsvector('simple', coalesce(NEW.title, '')), 'A') ||
                    setweight(to_tsvector('simple', coalesce(NEW.content, '')), 'B');
                RETURN NEW;
            END
            \$\$ LANGUAGE plpgsql
        ");

        DB::statement("
            CREATE TRIGGER posts_search_vector_trigger
            BEFORE INSERT OR UPDATE OF title, content ON posts
            FOR EACH ROW EXECUTE FUNCTION posts_search_vector_update()
        ");

        // attachments GIN 인덱스
        DB::statement('CREATE INDEX idx_posts_attachments_gin ON posts USING gin (attachments) WHERE attachments IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS posts_search_vector_trigger ON posts');
        DB::statement('DROP FUNCTION IF EXISTS posts_search_vector_update()');
        Schema::dropIfExists('posts');
    }
};
