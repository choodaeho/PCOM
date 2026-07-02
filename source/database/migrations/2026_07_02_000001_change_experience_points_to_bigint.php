<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * experience_points: integer(max 2.1B) → bigint(max 9.2 × 10^18)
 * 50레벨 시스템에서 XP가 수십억 단위에 달하므로 컬럼 타입 변경 필요.
 */
return new class extends Migration
{
    public function up(): void
    {
        // PostgreSQL은 ALTER COLUMN TYPE bigint 직접 지원
        DB::statement('ALTER TABLE users ALTER COLUMN experience_points TYPE bigint');
    }

    public function down(): void
    {
        // 되돌릴 경우 bigint → integer (데이터 손실 가능)
        DB::statement('ALTER TABLE users ALTER COLUMN experience_points TYPE integer');
    }
};
