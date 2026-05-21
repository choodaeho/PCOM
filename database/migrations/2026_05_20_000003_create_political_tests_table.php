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
        Schema::create('political_tests', function (Blueprint $table) {
            $table->id();
            $table->string('question', 500)->comment('성향 테스트 질문 본문');

            /**
             * options JSONB 구조 예시:
             * [
             *   {"value": 2,  "label": "매우 찬성"},
             *   {"value": 1,  "label": "찬성"},
             *   {"value": 0,  "label": "중립"},
             *   {"value": -1, "label": "반대"},
             *   {"value": -2, "label": "매우 반대"}
             * ]
             */
            $table->jsonb('options')->comment('선택지 배열 (JSONB): [{value, label}]');

            /**
             * weight: 이 문항이 점수에 미치는 가중치
             *   양수(+) → 보수 성향 문항
             *   음수(-) → 진보 성향 문항
             */
            $table->decimal('weight', 5, 2)->default(1.00)->comment('문항 가중치 (양수=보수, 음수=진보)');

            $table->string('category', 50)->nullable()->comment('문항 분류 (경제/안보/사회/환경 등)');
            $table->unsignedSmallInteger('sort_order')->default(0)->comment('출제 순서');
            $table->boolean('is_active')->default(true)->comment('활성화 여부 (비활성화 시 출제 제외)');

            $table->timestamps();
        });

        // GIN 인덱스: JSONB options 필드 검색용
        DB::statement('CREATE INDEX idx_political_tests_options_gin ON political_tests USING gin (options)');
        DB::statement('CREATE INDEX idx_political_tests_sort ON political_tests (sort_order) WHERE is_active = true');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('political_tests');
    }
};
