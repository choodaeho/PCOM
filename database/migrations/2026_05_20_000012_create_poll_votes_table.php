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
        Schema::create('poll_votes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('poll_id')
                ->constrained('polls')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('option_id')->comment('polls.options[].id 참조');

            /**
             * faction: 투표 당시 진영 스냅샷.
             * 진영별 투표 분포 시각화에 활용.
             */
            $table->string('faction', 20)->comment('투표 당시 진영: conservative | moderate | progressive');

            $table->timestamp('created_at')->useCurrent();

            // 동일 사용자 중복 투표 방지
            $table->unique(['poll_id', 'user_id']);
        });

        // 진영별 투표 현황 집계용 인덱스
        DB::statement('CREATE INDEX idx_poll_votes_poll_faction ON poll_votes (poll_id, faction, option_id)');
        DB::statement('CREATE INDEX idx_poll_votes_user ON poll_votes (user_id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poll_votes');
    }
};
