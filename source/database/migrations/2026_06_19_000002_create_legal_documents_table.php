<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_documents', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->comment('terms | privacy');
            $table->string('version', 20)->comment('v1.0, v1.1 등');
            $table->string('title', 200);
            $table->text('content');
            $table->date('effective_date')->comment('시행일');
            $table->boolean('is_current')->default(false)->comment('현재 적용 버전');
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'is_current'], 'idx_legal_type_current');
            $table->index(['type', 'effective_date'], 'idx_legal_type_effective');
            $table->unique(['type', 'version'], 'uniq_legal_type_version');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_documents');
    }
};
