<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auto_content_configs', function (Blueprint $table) {
            $table->string('pixabay_api_key', 255)->default('')->after('gemini_api_key');
            $table->boolean('include_images')->default(true)->after('end_hour');
            $table->boolean('include_news_links')->default(true)->after('include_images');
            $table->boolean('include_youtube')->default(true)->after('include_news_links');
            $table->boolean('use_grounding')->default(true)->after('include_youtube');
            // use_grounding: true → Gemini Google Search 그라운딩 사용 (실시간 뉴스 반영)
        });
    }

    public function down(): void
    {
        Schema::table('auto_content_configs', function (Blueprint $table) {
            $table->dropColumn(['pixabay_api_key', 'include_images', 'include_news_links', 'include_youtube', 'use_grounding']);
        });
    }
};
