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
        Schema::table('preventivi_pazienti', function (Blueprint $table) {
            // 1. Rimuovere la chiave esterna e la colonna
            // Nota: il nome della chiave esterna potrebbe variare. Laravel di default la chiama
            // nome_tabella_nome_colonna_foreign.
            $table->dropForeign(['anagrafica_paziente_id']);
            $table->dropColumn('anagrafica_paziente_id');

            // 2. Aggiungere le nuove colonne dopo 'json_preventivo'
            $table->string('mail_paziente')->after('json_preventivo');
            $table->string('cellulare_paziente')->nullable()->after('mail_paziente');
            $table->string('indirizzo_paziente')->nullable()->after('cellulare_paziente');
            $table->string('citta_paziente')->nullable()->after('indirizzo_paziente');
            $table->string('cap_paziente', 5)->nullable()->after('citta_paziente');
            $table->string('provincia_paziente', 2)->nullable()->after('cap_paziente');
            $table->string('token')->unique()->nullable()->after('provincia_paziente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('preventivi_pazienti', function (Blueprint $table) {
            // 1. Rimuovere le nuove colonne
            $table->dropColumn([
                'mail_paziente',
                'cellulare_paziente',
                'indirizzo_paziente',
                'citta_paziente',
                'cap_paziente',
                'provincia_paziente',
                'token',
            ]);

            // 2. Aggiungere di nuovo la colonna e la chiave esterna originali
            $table->foreignId('anagrafica_paziente_id')->nullable()->constrained('anagrafica_pazienti')->onDelete('cascade');
        });
    }
};
