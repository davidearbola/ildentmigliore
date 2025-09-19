<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Route::get('/create-symlink-now', function () {
//     // Il percorso di destinazione (dove sono i file reali)
//     $targetFolder = storage_path('app/public');

//     // Il nome del link (dove il web server cerca i file)
//     $linkFolder = public_path('storage');

//     // Controlla se il link esiste già
//     if (file_exists($linkFolder)) {
//         return 'Il link simbolico "public/storage" esiste già.';
//     }

//     // Prova a creare il link
//     try {
//         symlink($targetFolder, $linkFolder);
//         return 'Link simbolico creato con successo!';
//     } catch (\Exception $e) {
//         return 'Errore durante la creazione del link: ' . $e->getMessage();
//     }
// });
