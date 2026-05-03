<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('storefront_order_id')->unique()->constrained('storefront_orders')->cascadeOnDelete();
            $table->string('buyer_name');
            $table->string('buyer_phone', 64);
            $table->text('buyer_address');
            $table->string('buyer_maps_url', 2048)->nullable();
            $table->json('lines');
            $table->decimal('subtotal', 12, 2);
            $table->string('currency_label_ar', 32);
            $table->string('currency_label_en', 32);
            $table->timestamps();
        });

        Schema::create('merchant_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('storefront_order_id')->constrained('storefront_orders')->cascadeOnDelete();
            $table->foreignId('merchant_invoice_id')->unique()->constrained('merchant_invoices')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency_label_ar', 32);
            $table->string('currency_label_en', 32);
            $table->string('payment_method', 64)->default('instapay');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_payments');
        Schema::dropIfExists('merchant_invoices');
    }
};
