<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\Province;
use App\Models\City;

class GeoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        City::truncate();
        Province::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('Tabelle province e comuni svuotate.');

        $this->command->info('Chiamata API per le province...');
        $provincesResponse = Http::get('https://axqvoqvbfjpaamphztgd.functions.supabase.co/province');

        if (!$provincesResponse->successful()) {
            $this->command->error('Errore nel recuperare le province dall\'API.');
            return;
        }

        $provincesData = $provincesResponse->json();
        $provincesMap = [];

        $this->command->getOutput()->progressStart(count($provincesData));
        foreach ($provincesData as $provinceItem) {
            $province = Province::create([
                'name' => $provinceItem['nome'],
                'code' => $provinceItem['codice'],
                'initials' => $provinceItem['sigla'],
                'region_name' => $provinceItem['regione'],
            ]);
            $provincesMap[$province->initials] = $province->id;
            $this->command->getOutput()->progressAdvance();
        }
        $this->command->getOutput()->progressFinish();
        $this->command->info('Province importate con successo!');

        $this->command->info('Chiamata API per i comuni (potrebbe richiedere un po\' di tempo)...');
        $citiesResponse = Http::get('https://axqvoqvbfjpaamphztgd.functions.supabase.co/comuni');

        if (!$citiesResponse->successful()) {
            $this->command->error('Errore nel recuperare i comuni dall\'API.');
            return;
        }

        $citiesData = $citiesResponse->json();
        $citiesToInsert = [];

        $this->command->getOutput()->progressStart(count($citiesData));
        foreach ($citiesData as $cityItem) {
            $provinceSigla = $cityItem['provincia']['sigla'];

            if (isset($provincesMap[$provinceSigla])) {
                $citiesToInsert[] = [
                    'province_id' => $provincesMap[$provinceSigla],
                    'name' => $cityItem['nome'],
                    'cap' => $cityItem['cap'],
                    'prefix' => $cityItem['prefisso'] ?? null,
                    'code' => $cityItem['codice'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            $this->command->getOutput()->progressAdvance();
        }

        City::insert($citiesToInsert);

        $this->command->getOutput()->progressFinish();
        $this->command->info('Comuni importati con successo!');
    }
}
