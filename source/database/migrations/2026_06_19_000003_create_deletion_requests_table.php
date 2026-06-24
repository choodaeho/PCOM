<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deletion_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_type', 30)->comment('personal_info|defamation|copyright|post|comment|other');
            $table->string('requester_name', 100);
            $table->string('requester_email', 200);
            $table->string('target_url', 1000)->nullable()->comment('삭제 요청 대상 URL');
            $table->text('description')->comment('삭제 요청 상세 사유');
            $table->string('status', 20)->default('pending')->comment('pending|processing|completed|rejected');
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'idx_deletion_status_created');
            $table->index('requester_email', 'idx_deletion_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deletion_requests');
    }
};
