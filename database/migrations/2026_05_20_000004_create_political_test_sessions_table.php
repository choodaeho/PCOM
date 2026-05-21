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
        Schema::create('political_test_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            /**
             * answers JSONB 구조 예시:
             * {
             *   "1": 2,    // question_id: 1, selected value: 2
             *   "2": -1,   // question_id: 2, selected value: -1
             *   "3": 0
             * }
             */
            $table->jsonb('answers')->comment('문항별 응답값 맵: {question_id: value}');

            $table->smallInteger('total_score')->comment('합산 성향 점수 (-100 ~ +100, 양수=보수, 음수=진보)');
            $table->string('result_type', 20)->comment('conservative | moderate | progressive');

            $table->boolean('is_final')->default(false)->comment('가장 최근 유효 결과 여부');
            $table->timestamp('completed_at')->useCurrent();

            $table->timestamps();
        });

        // 인덱스
        DB::statement('CREATE INDEX idx_test_sessions_user_id ON political_test_sessions (user_id)');
        DB::statement('CREATE INDEX idx_test_sessions_final ON political_test_sessions (user_id, is_final) WHERE is_final = true');
        DB::statement('CREATE INDEX idx_test_sessions_answers_gin ON political_test_sessions USING gin (answers)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('political_test_sessions');
    }
};
