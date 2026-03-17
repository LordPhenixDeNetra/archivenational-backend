<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FondsArchive;
use Illuminate\Http\Request;

class FondsController extends Controller
{
    public function index(Request $request)
    {
        $fonds = FondsArchive::query()
            ->orderBy('name')
            ->paginate((int) $request->query('per_page', 20));

        return response()->json($fonds);
    }

    public function show(string $id)
    {
        $fonds = FondsArchive::query()->findOrFail($id);

        return response()->json($fonds);
    }
}

