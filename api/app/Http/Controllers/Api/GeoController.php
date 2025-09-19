<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Province;
use Illuminate\Http\Request;

class GeoController extends Controller
{
    /**
     * Restituisce la lista di tutte le province dal database.
     */
    public function getProvince()
    {
        $provinces = Province::orderBy('name')->get(['name', 'initials']);
        return response()->json($provinces);
    }

    /**
     * Restituisce la lista dei comuni per una data provincia (tramite sigla).
     * La risposta include anche il CAP di ogni comune.
     */
    public function getComuni($siglaProvincia)
    {
        $province = Province::where('initials', $siglaProvincia)->first();

        if (!$province) {
            return response()->json([]);
        }

        $cities = $province->cities()->orderBy('name')->get(['name', 'cap']);

        return response()->json($cities);
    }
}
