<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lampiran_aset', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aset_id')->constrained('aset')->cascadeOnDelete();
            $table->string('file_path', 500);
            $table->string('file_name', 255);
            $table->unsignedInteger('file_size');
            $table->string('file_type', 50);
            $table->string('label', 255)->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lampiran_aset');
    }
};
