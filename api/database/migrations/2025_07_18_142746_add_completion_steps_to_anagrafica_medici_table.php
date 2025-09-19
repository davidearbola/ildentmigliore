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
            $table->timestamp('step_listino_completed_at')->nullable()->after('provincia');
            $table->timestamp('step_profilo_completed_at')->nullable()->after('step_listino_completed_at');
            $table->timestamp('step_staff_completed_at')->nullable()->after('step_profilo_completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('anagrafica_medici', function (Blueprint $table) {
            $table->dropColumn([
                'step_listino_completed_at',
                'step_profilo_completed_at',
                'step_staff_completed_at',
            ]);
        });
    }
};
