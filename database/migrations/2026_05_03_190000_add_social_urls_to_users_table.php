<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('social_facebook_url', 512)->nullable()->after('storefront_hero_secondary');
            $table->string('social_instagram_url', 512)->nullable()->after('social_facebook_url');
            $table->string('social_x_url', 512)->nullable()->after('social_instagram_url');
            $table->string('social_youtube_url', 512)->nullable()->after('social_x_url');
            $table->string('social_tiktok_url', 512)->nullable()->after('social_youtube_url');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'social_facebook_url',
                'social_instagram_url',
                'social_x_url',
                'social_youtube_url',
                'social_tiktok_url',
            ]);
        });
    }
};
