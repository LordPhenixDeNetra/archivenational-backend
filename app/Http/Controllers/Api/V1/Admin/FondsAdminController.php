<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\FondsArchive;
use App\Services\AuditService;
use Illuminate\Http\Request;

class FondsAdminController extends Controller
{
    public function store(Request $request, AuditService $audit)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'period_label' => ['nullable', 'string', 'max:80'],
            'unesco' => ['required', 'boolean'],
            'estimated_documents_count' => ['nullable', 'integer', 'min:0'],
        ]);

        $fonds = FondsArchive::query()->create($data);
        $audit->record($request->user(), 'fonds.create', 'FondsArchive', $fonds->getKey(), [], $request);

        return response()->json($fonds, 201);
    }

    public function update(Request $request, string $id, AuditService $audit)
    {
        $data = $request->validate([
            'code' => ['sometimes', 'string', 'max:32'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'period_label' => ['nullable', 'string', 'max:80'],
            'unesco' => ['sometimes', 'boolean'],
            'estimated_documents_count' => ['nullable', 'integer', 'min:0'],
        ]);

        $fonds = FondsArchive::query()->findOrFail($id);
        $fonds->fill($data);
        $fonds->save();

        $audit->record($request->user(), 'fonds.update', 'FondsArchive', $fonds->getKey(), [], $request);

        return response()->json($fonds);
    }

    public function destroy(Request $request, string $id, AuditService $audit)
    {
        $fonds = FondsArchive::query()->findOrFail($id);
        $fonds->delete();

        $audit->record($request->user(), 'fonds.delete', 'FondsArchive', $id, [], $request);

        return response()->noContent();
    }
}

