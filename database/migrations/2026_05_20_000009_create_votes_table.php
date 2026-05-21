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
     * Polymorphic 투표 테이블.
     * posts, comments 모두 동일 테이블로 처리.
     * votable_type: 'App\Models\Post' | 'App\Models\Comment'
     */
    public function up(): void
    {
        Schema::create('votes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Polymorphic 관계
            $table->morphs('votable'); // votable_id + votable_type

            /**
             * vote_type:
             *   up   : 추천
             *   down : 비추천
             */
            $table->string('vote_type', 10)->comment('up | down');

            $table->timestamp('created_at')->useCurrent();

            // 동일 사용자가 동일 대상에 중복 투표 방지
            $table->unique(['user_id', 'votable_id', 'votable_type']);
        });

        // 조회용 인덱스 (morphs()가 기본 인덱스를 생성하지만 커버링 인덱스 추가)
        DB::statement('CREATE INDEX idx_votes_votable ON votes (votable_type, votable_id, vote_type)');
        DB::statement('CREATE INDEX idx_votes_user_id ON votes (user_id, created_at DESC)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
