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
        Schema::table('anagrafica_medici', function (Blueprint $table) {
            $table->enum('tipo_registrazione', ['idm', 'vivi'])->default('idm')->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('anagrafica_medici', function (Blueprint $table) {
            $table->dropColumn('tipo_registrazione');
        });
    }
};
