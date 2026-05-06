<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table): void {
            $table->id();
            $table->dateTime('started_at')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('session_hash', 64)->index();
            $table->string('path', 512)->index();
            $table->unsignedInteger('duration_seconds')->default(0)->index();
            $table->string('referrer', 512)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('ip', 64)->nullable()->index();

            $table->index(['started_at', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};

