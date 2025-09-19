<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\ListinoMaster;

class ListinoMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disabilita i controlli sulle chiavi esterne per evitare problemi
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        // Svuota la tabella prima di popolarla per evitare duplicati
        ListinoMaster::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $prestazioni = [
            ['nome' => 'Visita di controllo e diagnosi', 'descrizione' => 'Esame completo del cavo orale per la valutazione dello stato di salute di denti e gengive.'],
            ['nome' => 'Igiene dentale professionale', 'descrizione' => 'Ablazione del tartaro e lucidatura dei denti per la prevenzione di carie e malattie gengivali.'],
            ['nome' => 'Otturazione semplice', 'descrizione' => 'Ricostruzione di una piccola carie su una superficie del dente.'],
            ['nome' => 'Otturazione complessa', 'descrizione' => 'Ricostruzione di una carie estesa che coinvolge più superfici del dente.'],
            ['nome' => 'Estrazione dente semplice', 'descrizione' => 'Rimozione di un dente erotto in arcata senza necessità di intervento chirurgico complesso.'],
            ['nome' => 'Sbiancamento dentale professionale', 'descrizione' => 'Trattamento estetico per migliorare il colore dei denti naturali.'],
        ];

        foreach ($prestazioni as $prestazione) {
            ListinoMaster::create($prestazione);
        }
    }
}
