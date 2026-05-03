<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('store_logo_path')->nullable()->after('password');
            $table->string('instapay_wallet')->nullable()->after('store_logo_path');
            $table->string('whatsapp_phone')->nullable()->after('instapay_wallet');
            $table->string('phone')->nullable()->after('whatsapp_phone');
            $table->text('address')->nullable()->after('phone');
            $table->string('subscription_payment_proof_path')->nullable()->after('address');
            $table->timestamp('subscription_payment_reported_at')->nullable()->after('subscription_payment_proof_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'store_logo_path',
                'instapay_wallet',
                'whatsapp_phone',
                'phone',
                'address',
                'subscription_payment_proof_path',
                'subscription_payment_reported_at',
            ]);
        });
    }
};
