<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('jurnal_aset', function (Blueprint $table) {
            $table->foreignId('agenda_id')->nullable()->after('aset_id')->constrained('agenda_aset')->nullOnDelete();
        });

        Schema::table('keuangan_aset', function (Blueprint $table) {
            $table->foreignId('agenda_id')->nullable()->after('aset_id')->constrained('agenda_aset')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnal_aset', function (Blueprint $table) {
            $table->dropForeign(['agenda_id']);
            $table->dropColumn(['agenda_id']);
        });

        Schema::table('keuangan_aset', function (Blueprint $table) {
            $table->dropForeign(['agenda_id']);
            $table->dropColumn(['agenda_id']);
        });
    }
};
