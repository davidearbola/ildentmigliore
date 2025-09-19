<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FotoStudio;
use App\Models\StaffMedico;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfiloMedicoController extends Controller
{
    /**
     * Ritorna tutti i dati del profilo del medico.
     */
    public function index()
    {
        $medico = Auth::user();
        $medico->load(['anagraficaMedico', 'fotoStudi', 'staff']);

        return response()->json($medico);
    }

    /**
     * Ritorna il profilo pubblico di un medico, con controllo di autorizzazione.
     */
    public function showPublicProfile($medicoId)
    {
        $user = Auth::user();
        $medico = User::with(['anagraficaMedico', 'fotoStudi', 'staff'])->findOrFail($medicoId);

        // Controllo #1: il medico ha completato tutti gli step del profilo?
        $anagrafica = $medico->anagraficaMedico;
        if (!$anagrafica || !$anagrafica->step_listino_completed_at || !$anagrafica->step_profilo_completed_at || !$anagrafica->step_staff_completed_at) {
            abort(403, 'Il profilo di questo medico non è ancora completo.');
        }

        // Controllo #2: l'utente è il medico stesso?
        if ($user->id == $medicoId) {
            return response()->json($medico);
        }

        // Controllo #3: l'utente è un paziente che ha una proposta da questo medico?
        if ($user->role === 'paziente') {
            $hasProposta = $user->anagraficaPaziente
                ->preventivi()
                ->whereHas('controproposte', function ($query) use ($medicoId) {
                    $query->where('medico_user_id', $medicoId);
                })
                ->exists();

            if ($hasProposta) {
                return response()->json($medico);
            }
        }

        // Se nessuno dei controlli ha avuto successo, l'accesso è negato.
        abort(403, 'Non hai il permesso di visualizzare questo profilo.');
    }

    /**
     * Aggiorna la descrizione dello studio.
     */
    public function updateDescrizione(Request $request)
    {
        $validated = $request->validate([
            'descrizione' => 'required|string|min:50',
        ]);

        $medico = Auth::user();
        $medico->anagraficaMedico()->update($validated);
        $this->checkProfiloCompletion($medico);

        return response()->json(['success' => true, 'message' => 'Descrizione aggiornata con successo.']);
    }

    /**
     * Carica una nuova foto per lo studio.
     */
    public function uploadFotoStudio(Request $request)
    {
        $validated = $request->validate([
            'foto' => 'required|image|mimes:jpg,jpeg,png|max:2048', // max 2MB
        ]);

        $medico = Auth::user();
        $file = $validated['foto'];

        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs('profilo_studi/' . $medico->id . '/foto_studio', $fileName, 'public');

        $foto = $medico->fotoStudi()->create(['file_path' => $filePath]);

        $this->checkProfiloCompletion($medico);

        return response()->json([
            'success' => true,
            'message' => 'Foto caricata con successo.',
            'foto' => $foto
        ], 201);
    }

    /**
     * Elimina una foto dello studio.
     */
    public function destroyFotoStudio(FotoStudio $foto)
    {
        if ($foto->medico_user_id !== Auth::id()) {
            abort(403, 'Azione non autorizzata.');
        }

        // Elimina il file dallo storage
        Storage::disk('public')->delete($foto->file_path);
        // Elimina il record dal database
        $foto->delete();

        $this->checkProfiloCompletion(Auth::user());

        return response()->json(['success' => true, 'message' => 'Foto eliminata.']);
    }

    /**
     * Crea un nuovo membro dello staff.
     */
    public function storeStaff(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'ruolo' => 'required|string|max:255',
            'specializzazione' => 'nullable|string',
            'esperienza' => 'nullable|string',
            'foto' => 'required|image|mimes:jpg,jpeg,png|max:1024', // max 1MB
        ]);

        $medico = Auth::user();
        $file = $validated['foto'];

        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs('profilo_studi/' . $medico->id . '/foto_staff', $fileName, 'public');

        $staff = $medico->staff()->create([
            'nome' => $validated['nome'],
            'ruolo' => $validated['ruolo'],
            'specializzazione' => $validated['specializzazione'],
            'esperienza' => $validated['esperienza'],
            'foto_path' => $filePath,
        ]);

        $this->checkStaffCompletion($medico);

        return response()->json([
            'success' => true,
            'message' => 'Membro dello staff aggiunto.',
            'staff' => $staff,
        ], 201);
    }

    /**
     * Aggiorna un membro dello staff 
     */
    public function updateStaff(Request $request, StaffMedico $staff)
    {
        if ($staff->medico_user_id !== Auth::id()) {
            abort(403, 'Azione non autorizzata.');
        }

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'ruolo' => 'required|string|max:255',
            'specializzazione' => 'nullable|string',
            'esperienza' => 'nullable|string',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png|max:1024', // La foto è facoltativa in modifica
        ]);

        // Gestione della foto, se ne è stata caricata una nuova
        if ($request->hasFile('foto')) {
            // 1. Elimina la vecchia foto dallo storage
            if ($staff->foto_path) {
                Storage::disk('public')->delete($staff->foto_path);
            }

            // 2. Salva la nuova foto
            $file = $request->file('foto');
            $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('profilo_studi/' . Auth::id() . '/foto_staff', $fileName, 'public');

            // 3. Aggiunge il nuovo percorso ai dati da salvare
            $validated['foto_path'] = $filePath;
        }

        // 4. Aggiorna il record nel database
        $staff->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Membro dello staff aggiornato.',
            'staff' => $staff->fresh() // Ritorna i dati aggiornati
        ]);
    }

    /**
     * Elimina un membro dello staff.
     */
    public function destroyStaff(StaffMedico $staff)
    {
        if ($staff->medico_user_id !== Auth::id()) {
            abort(403, 'Azione non autorizzata.');
        }
        Storage::disk('public')->delete($staff->foto_path);
        $staff->delete();

        $this->checkStaffCompletion(Auth::user());

        return response()->json(['success' => true, 'message' => 'Membro dello staff eliminato.']);
    }

    private function checkProfiloCompletion($medico)
    {
        $anagrafica = $medico->anagraficaMedico;
        $isComplete = $anagrafica->descrizione && $medico->fotoStudi()->count() >= 3;

        // Se i requisiti sono soddisfatti E lo step non era completo, lo segno come completato.
        if ($isComplete && !$anagrafica->step_profilo_completed_at) {
            $anagrafica->update(['step_profilo_completed_at' => Carbon::now()]);
        }
        // Altrimenti, se i requisiti NON sono soddisfatti E lo step ERA completo, lo resetto.
        elseif (!$isComplete && $anagrafica->step_profilo_completed_at) {
            $anagrafica->update(['step_profilo_completed_at' => null]);
        }
    }

    private function checkStaffCompletion($medico)
    {
        $anagrafica = $medico->anagraficaMedico;
        $isComplete = $medico->staff()->count() >= 1;

        if ($isComplete && !$anagrafica->step_staff_completed_at) {
            $anagrafica->update(['step_staff_completed_at' => Carbon::now()]);
        } elseif (!$isComplete && $anagrafica->step_staff_completed_at) {
            $anagrafica->update(['step_staff_completed_at' => null]);
        }
    }
}
