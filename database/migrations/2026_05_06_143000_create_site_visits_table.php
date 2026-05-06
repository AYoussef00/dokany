<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_visits', function (Blueprint $table): void {
            $table->id();
            $table->dateTime('visited_at')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('session_hash', 64)->index();
            $table->string('ip', 64)->nullable()->index();
            $table->string('country_code', 8)->nullable()->index();
            $table->string('country_name', 128)->nullable();
            $table->string('path', 512)->index();
            $table->string('user_agent', 512)->nullable();

            $table->index(['visited_at', 'country_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_visits');
    }
};

