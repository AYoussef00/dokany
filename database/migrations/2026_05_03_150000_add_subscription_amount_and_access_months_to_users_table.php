<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('subscription_paid_amount', 12, 2)->nullable()->after('merchant_subscription_status');
            $table->unsignedSmallInteger('merchant_access_months')->nullable()->after('subscription_paid_amount');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['subscription_paid_amount', 'merchant_access_months']);
        });
    }
};
