<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\PreventivoPaziente;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use OpenAI;

class ProcessPreventivo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $preventivo;
    public $tries = 3;
    public $backoff = [120, 300];
    public $timeout = 300;

    public function __construct(PreventivoPaziente $preventivo)
    {
        $this->preventivo = $preventivo;
    }

    public function handle(): void
    {
        Log::info("ProcessPreventivo Job #{$this->preventivo->id}: Avviato.");
        $this->preventivo->update(['stato_elaborazione' => 'in_elaborazione']);

        $text = null;
        $filePath = Storage::disk('public')->path($this->preventivo->file_path);
        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);

        try {
            if (strtolower($fileExtension) === 'pdf') {
                Log::info("ProcessPreventivo Job #{$this->preventivo->id}: Inizio parsing PDF.");
                $parser = new Parser();
                $pdf = $parser->parseFile($filePath);
                $extractedText = $pdf->getText();
                Log::info("ProcessPreventivo Job #{$this->preventivo->id}: Parsing PDF completato.");

                if (strlen($extractedText) > 50) {
                    $text = $extractedText;
                } else {
                    Log::warning("PDF #{$this->preventivo->id} è probabilmente un file scansionato o vuoto.");
                    throw new \Exception('Il file PDF è un\'immagine scansionata e non può essere processato.');
                }
            } else {
                Log::info("ProcessPreventivo Job #{$this->preventivo->id}: Inizio chiamata a OCR.space.");
                $response = Http::timeout(120)
                    ->withHeaders(['apikey' => env('OCR_SPACE_API_KEY')])
                    ->attach('file', file_get_contents($filePath), basename($filePath))
                    ->post('https://api.ocr.space/parse/image', [
                        'language' => 'ita',
                        'isOverlayRequired' => 'false',
                        'detectOrientation' => 'true',
                        'ocrengine' => 2,
                    ]);
                Log::info("ProcessPreventivo Job #{$this->preventivo->id}: Chiamata a OCR.space completata.");

                if ($response->successful() && !$response->json('IsErroredOnProcessing')) {
                    $text = $response->json('ParsedResults.0.ParsedText');
                } else {
                    throw new \Exception('Errore API OCR.space: ' . ($response->json('ErrorMessage.0') ?? $response->body()));
                }
            }

            if (!$text) {
                throw new \Exception('Estrazione del testo fallita, il risultato è vuoto.');
            }
            
            $additionalInstruction = '';
            if (strtolower($fileExtension) === 'pdf') {
                $additionalInstruction = "\n\nNOTA: Questo testo proviene da un PDF e la struttura a colonne potrebbe essere andata persa. Fai del tuo meglio per associare correttamente le prestazioni con le rispettive quantità e prezzi.";
            }

            Log::info("ProcessPreventivo Job #{$this->preventivo->id}: Inizio chiamata a OpenAI.");
            $client = OpenAI::client(env('OPENAI_API_KEY'));
            $response = $client->chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Sei un assistente IA esperto nell\'analisi di documenti finanziari, specializzato in preventivi medici e dentistici. Il tuo compito è estrarre le informazioni con la massima precisione e strutturarle in un formato JSON specifico.

                        ## STRATEGIA DI ANALISI
                        Prima di creare il JSON, segui mentalmente questi passi:
                        1.  **Scansiona il documento per identificare la struttura tabellare.** Cerca le intestazioni di colonna come "Prestazione", "Quantità", "Prezzo Singolo", "Prezzo Totale".
                        2.  **Analizza ogni riga della tabella una per una**, estraendo le tre informazioni chiave richieste.
                        3.  **Infine, cerca nel fondo del documento il totale generale definitivo.**
                                            
                        ## FORMATO JSON DI OUTPUT OBBLigatorio
                        Il JSON deve contenere due chiavi principali:
                        1.  `voci_preventivo`: un array di oggetti.
                        2.  `totale_preventivo`: un numero (float o integer).
                                            
                        Ogni oggetto all\'interno di `voci_preventivo` deve avere esattamente tre chiavi:
                        -   `prestazione`: (stringa) Il nome del servizio. Rimuovi codici o numeri tra parentesi (es. "(27,28)").
                        -   `quantità`: (numero, integer) La quantità del servizio.
                        -   `prezzo`: (numero, float o integer) Il costo **TOTALE** per quella voce di preventivo.
                                            
                        ## REGOLE FONDAMENTALI PER L\'ESTRAZIONE
                                            
                        ### 1. Analisi delle Voci (`voci_preventivo`):
                        -   **Prestazione**: Deve essere solo il testo descrittivo del trattamento.
                        -   **Quantità**:
                            - Cerca attivamente una colonna con intestazioni come: **`Quantità`**, **`Q.tà`**, **`Qtà`**, **`Qt.`**. Usa il valore da quella colonna.
                            - Se non esiste una colonna, cerca la quantità nella descrizione (es. "x2").
                            - Se non trovi nessuna indicazione, la quantità di default è `1`.
                        -   **Prezzo (per singola voce)**:
                            - **REGOLA CRITICA**: Questo campo deve essere il **costo totale della riga**.
                            - Se vedi colonne sia per "Prezzo Singolo" (o "Unitario") sia per "Prezzo Totale" (o "Importo"), **devi obbligatoriamente usare il valore dalla colonna "Prezzo Totale"**.
                            - Se c\'è solo una quantità e un prezzo unitario, calcola tu il totale (`quantità` * `prezzo_unitario`).
                            - Se c\'è un solo prezzo, assumi che sia già il totale.
                                            
                        ### 2. Calcolo del Totale (`totale_preventivo`):
                        -   **PRIORITÀ ASSOLUTA**: Il totale deve essere quello **esplicitamente scritto sul documento**.
                        -   Cerca con la massima priorità etichette come: **`Totale da pagare`**, **`Totale`**, **`Corrispettivo`**, **`Totale IVA Inclusa`**.
                        -   Il valore da prendere è la cifra finale, quella che include ogni costo aggiuntivo (es. "quota associativa"). Se vedi "Corrispettivo: 1000" e "Totale con quota: 1050", il valore corretto è `1050`.
                        -   **Fallback (DA USARE CON CAUTELA)**: Calcola tu la somma dei prezzi delle voci **SOLO E SOLTANTO SE** non esiste un totale generale esplicito nel documento.
                                            
                        ### 3. Regole Aggiuntive e Formattazione:
                        -   **Numeri**: Tutti i valori devono essere numeri puri, senza simboli di valuta (€) o separatori delle migliaia (usa `1334.00` e non `1.334,00`). Usa il punto `.` come separatore decimale.
                        -   **Dati da Ignorare**: Ignora intestazioni, dati del paziente, indirizzi, e qualsiasi testo non pertinente.
                                            
                        ## ESEMPIO PRATICO (Basato su un caso reale)
                        Testo: "Prestazione: Ricostruzione dente (27,28), Qtà: 2, Prezzo singolo: 116,00, Prezzo totale: 232,00. [...] In fondo: Corrispettivo con quota associativa 89€: 1.334,00 €"
                                            
                        Output JSON corretto per questo caso:
                        ```json
                        {
                          "voci_preventivo": [
                            {
                              "prestazione": "Ricostruzione dente",
                              "quantità": 2,
                              "prezzo": 232.00
                            }
                          ],
                          "totale_preventivo": 1334.00
                        }
                        ```'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Analizza, struttura e calcola il totale del seguente testo estratto da un documento: \n\n" . $text . $additionalInstruction
                    ]
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

            Log::info("ProcessPreventivo Job #{$this->preventivo->id}: Chiamata a OpenAI completata.");

            $structuredJsonString = $response->choices[0]->message->content;
            $decodedResponse = json_decode($structuredJsonString, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Il JSON ricevuto da OpenAI non è valido. Risposta: ' . $structuredJsonString);
            }

            // *** MODIFICA CHIAVE: SALVIAMO L'INTERO OGGETTO JSON ***
            $this->preventivo->update([
                'json_preventivo'    => json_encode($decodedResponse), // Salva l'intero oggetto
                'stato_elaborazione' => 'completato'
            ]);
            
            Log::info("ProcessPreventivo Job #{$this->preventivo->id}: Completato con successo.");

        } catch (\Exception $e) {
            $this->preventivo->update(['stato_elaborazione' => 'errore', 'messaggio_errore' => $e->getMessage()]);
            Log::error("Errore durante l'elaborazione del preventivo #{$this->preventivo->id}: " . $e->getMessage());
            $this->fail($e);
        }
    }
}