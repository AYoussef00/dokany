<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storefront_orders', function (Blueprint $table) {
            $table->foreignId('payment_link_id')
                ->nullable()
                ->after('user_id')
                ->constrained('payment_links')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('storefront_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_link_id');
        });
    }
};
