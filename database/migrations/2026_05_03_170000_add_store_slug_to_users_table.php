<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('store_slug', 64)->nullable()->unique()->after('name');
        });

        $sellerIds = DB::table('users')->where('role', 'seller')->pluck('id', 'id');
        foreach ($sellerIds as $id) {
            $row = DB::table('users')->where('id', $id)->first();
            if ($row === null) {
                continue;
            }
            $base = Str::slug((string) ($row->name ?? ''));
            if ($base === '') {
                $base = 'merchant';
            }
            $slug = $base;
            $n = 0;
            while (
                DB::table('users')->where('store_slug', $slug)->where('id', '!=', $id)->exists()
            ) {
                $n++;
                $slug = $base.'-'.$n;
            }
            DB::table('users')->where('id', $id)->update(['store_slug' => $slug]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('store_slug');
        });
    }
};
