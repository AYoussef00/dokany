<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storefront_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('buyer_name');
            $table->string('buyer_phone', 64);
            $table->text('buyer_address');
            $table->string('buyer_maps_url', 2048)->nullable();
            $table->json('lines');
            $table->decimal('subtotal', 12, 2);
            $table->string('currency_label_ar', 32);
            $table->string('currency_label_en', 32);
            $table->string('status', 32)->default('pending_payment');
            $table->string('payment_receipt_path', 512)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_orders');
    }
};
