<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GeneraControproposte;
use App\Jobs\ProcessPreventivo;
use App\Models\PreventivoPaziente;
use App\Models\ContropropostaMedico; 
use Illuminate\Http\Request;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Auth; 
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PreventivoController extends Controller
{
    // ... (il metodo store rimane invariato) ...
    public function store(Request $request)
    {
        if ($request->hasFile('preventivo')) {
            $sizeInKb = round($request->file('preventivo')->getSize() / 1024, 2);
            Log::info("Nuovo preventivo ricevuto. Dimensione file: {$sizeInKb} KB");
        }

        $anagraficaExists = $request->user()->anagraficaPaziente()->exists();

        $rules = [
            'preventivo' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ];

        if (!$anagraficaExists) {
            $rules += [
                'cellulare' => 'required|string|min:9',
                'indirizzo' => 'required|string|max:255',
                'citta'     => 'required|string|max:255',
                'cap'       => 'required|string|size:5',
                'provincia' => 'required|string|size:2',
            ];
        }

        $validatedData = $request->validate($rules);

        if (!$anagraficaExists) {
            $anagrafica = $request->user()->anagraficaPaziente()->create([
                'cellulare' => $validatedData['cellulare'],
                'indirizzo' => $validatedData['indirizzo'],
                'citta'     => $validatedData['citta'],
                'cap'       => $validatedData['cap'],
                'provincia' => $validatedData['provincia'],
                'lat'       => 45.4642000,
                'lng'       => 9.1900000,
            ]);
        } else {
            $anagrafica = $request->user()->anagraficaPaziente;
        }

        $file = $request->file('preventivo');
        $maxSizeInBytes = 1024 * 1024; // 1MB
        $filePath = null;

        if (Str::startsWith($file->getMimeType(), 'image/') && $file->getSize() > $maxSizeInBytes) {
            $sourcePath = $file->getRealPath();
            list($originalWidth, $originalHeight, $imageType) = getimagesize($sourcePath);
            $sourceImage = null;
            switch ($imageType) {
                case IMAGETYPE_JPEG:
                    $sourceImage = imagecreatefromjpeg($sourcePath);
                    break;
                case IMAGETYPE_PNG:
                    $sourceImage = imagecreatefrompng($sourcePath);
                    break;
            }
            if ($sourceImage) {
                $maxWidth = 1200;
                $ratio = $originalWidth / $originalHeight;
                $newWidth = $maxWidth;
                $newHeight = $maxWidth / $ratio;
                $destImage = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($destImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
                $tempPath = tempnam(sys_get_temp_dir(), 'resized-') . '.' . $file->getClientOriginalExtension();
                switch ($imageType) {
                    case IMAGETYPE_JPEG:
                        imagejpeg($destImage, $tempPath, 85);
                        break;
                    case IMAGETYPE_PNG:
                        imagepng($destImage, $tempPath, 6);
                        break;
                }
                imagedestroy($sourceImage);
                imagedestroy($destImage);
                $fileName = Str::slug($request->user()->name) . '_' . $anagrafica->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $finalDirectory = 'preventivi/' . $anagrafica->id;
                Storage::disk('public')->putFileAs($finalDirectory, new File($tempPath), $fileName);
                $filePath = $finalDirectory . '/' . $fileName;
                unlink($tempPath);
            }
        }

        if (is_null($filePath)) {
            $fileName = Str::slug($request->user()->name) . '_' . $anagrafica->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('preventivi/' . $anagrafica->id, $fileName, 'public');
        }

        $preventivo = $anagrafica->preventivi()->create([
            'file_path'           => $filePath,
            'file_name_originale' => $file->getClientOriginalName(),
            'stato_elaborazione'  => 'caricato',
        ]);

        ProcessPreventivo::dispatch($preventivo);

        return response()->json([
            'success' => true,
            'message' => 'Preventivo caricato. Inizio elaborazione.',
            'preventivo_id' => $preventivo->id
        ], 201);
    }

    public function stato(PreventivoPaziente $preventivoPaziente)
    {
        if ($preventivoPaziente->anagraficaPaziente->user_id !== Auth::id()) {
            return response()->json(['error' => 'Non autorizzato'], 403);
        }

        // *** MODIFICA CHIAVE: Estrae solo le voci per il frontend ***
        $voci = null;
        if ($preventivoPaziente->stato_elaborazione === 'completato') {
            $datiPreventivo = json_decode($preventivoPaziente->json_preventivo);
            // Controlla se la proprietà 'voci_preventivo' esiste prima di accedervi
            $voci = $datiPreventivo->voci_preventivo ?? [];
        }

        return response()->json([
            'stato_elaborazione' => $preventivoPaziente->stato_elaborazione,
            'voci_preventivo' => $voci,
        ]);
    }

    /**
     * *** METODO CON LA LOGICA CORRETTA ***
     * Riceve le voci modificate, ricalcola il totale e salva l'intero oggetto JSON.
     */
    public function conferma(Request $request, PreventivoPaziente $preventivoPaziente)
    {
        if ($preventivoPaziente->anagraficaPaziente->user_id !== Auth::id()) {
            return response()->json(['error' => 'Non autorizzato'], 403);
        }
        
        if ($preventivoPaziente->stato_elaborazione !== 'completato') {
            return response()->json(['error' => 'Il preventivo non è ancora stato elaborato.'], 422);
        }

        $validated = $request->validate([
            'voci' => 'required|array',
            'voci.*.prestazione' => 'required|string',
            'voci.*.quantità' => 'required|integer|min:1',
            'voci.*.prezzo' => 'required|numeric|min:0',
        ]);

        // 1. Recupera le nuove voci validate
        $nuoveVoci = $validated['voci'];

        // 2. Ricalcola il totale basandosi sulle nuove voci
        $nuovoTotale = 0;
        foreach ($nuoveVoci as $voce) {
            $nuovoTotale += $voce['prezzo'];
        }

        // 3. Crea la nuova struttura JSON completa
        $datiAggiornati = [
            'voci_preventivo' => $nuoveVoci,
            'totale_preventivo' => $nuovoTotale
        ];

        // 4. Salva il nuovo oggetto JSON completo nel database
        // Laravel si occuperà di codificarlo correttamente nel campo 'json_preventivo'
        $preventivoPaziente->json_preventivo = $datiAggiornati;
        $preventivoPaziente->save();

        // 5. Avvia il job per generare le controproposte
        GeneraControproposte::dispatch($preventivoPaziente);

        return response()->json(['message' => 'Preventivo confermato. Stiamo generando le controproposte.']);
    }

    /**
     * *** NUOVO METODO ***
     * Controlla se sono state generate delle proposte per un dato preventivo.
     */
    public function proposteStato(PreventivoPaziente $preventivoPaziente)
    {
        // Policy di sicurezza
        if ($preventivoPaziente->anagraficaPaziente->user_id !== Auth::id()) {
            return response()->json(['error' => 'Non autorizzato'], 403);
        }

        // Controlla se esiste almeno una controproposta per questo preventivo
        $proposteEsistono = ContropropostaMedico::where('preventivo_paziente_id', $preventivoPaziente->id)->exists();

        return response()->json([
            'proposte_pronte' => $proposteEsistono,
        ]);
    }
}