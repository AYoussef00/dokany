<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->text('storefront_hero_primary')->nullable()->after('store_logo_path');
            $table->text('storefront_hero_secondary')->nullable()->after('storefront_hero_primary');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['storefront_hero_primary', 'storefront_hero_secondary']);
        });
    }
};
