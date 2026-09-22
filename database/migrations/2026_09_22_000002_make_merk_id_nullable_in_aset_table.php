<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aset', function (Blueprint $table) {
            $table->unsignedBigInteger('merk_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('aset', function (Blueprint $table) {
            $table->unsignedBigInteger('merk_id')->nullable(false)->change();
        });
    }
};
