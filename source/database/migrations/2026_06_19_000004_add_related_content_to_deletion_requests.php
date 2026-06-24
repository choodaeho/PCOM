<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deletion_requests', function (Blueprint $table) {
            $table->foreignId('related_post_id')
                ->nullable()->after('target_url')
                ->constrained('posts')->nullOnDelete();
            $table->foreignId('related_comment_id')
                ->nullable()->after('related_post_id')
                ->constrained('comments')->nullOnDelete();
            $table->string('blinded_type', 10)->nullable()->after('related_comment_id')
                ->comment('post | comment');
        });
    }

    public function down(): void
    {
        Schema::table('deletion_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('related_post_id');
            $table->dropConstrainedForeignId('related_comment_id');
            $table->dropColumn('blinded_type');
        });
    }
};
