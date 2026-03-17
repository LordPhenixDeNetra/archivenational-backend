<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\AuditService;
use Illuminate\Http\Request;

class DocumentAdminController extends Controller
{
    public function store(Request $request, AuditService $audit)
    {
        $data = $request->validate([
            'fonds_id' => ['required', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'reference_code' => ['nullable', 'string', 'max:80'],
            'summary' => ['nullable', 'string'],
            'type' => ['required', 'in:MANUSCRIPT,NEWSPAPER,MAP,PHOTO,REGISTER,REPORT,LETTER,AUDIO,VIDEO,OTHER'],
            'visibility' => ['required', 'in:PUBLIC,REGISTERED,RESTRICTED,ADMIN_ONLY'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'language' => ['nullable', 'string', 'max:30'],
            'page_count' => ['nullable', 'integer', 'min:0'],
            'published_at' => ['nullable', 'date'],
        ]);

        $doc = Document::query()->create($data);
        $audit->record($request->user(), 'documents.create', 'Document', $doc->getKey(), ['visibility' => $doc->visibility], $request);

        return response()->json($doc, 201);
    }

    public function update(Request $request, string $id, AuditService $audit)
    {
        $data = $request->validate([
            'fonds_id' => ['sometimes', 'string'],
            'title' => ['sometimes', 'string', 'max:255'],
            'reference_code' => ['nullable', 'string', 'max:80'],
            'summary' => ['nullable', 'string'],
            'type' => ['sometimes', 'in:MANUSCRIPT,NEWSPAPER,MAP,PHOTO,REGISTER,REPORT,LETTER,AUDIO,VIDEO,OTHER'],
            'visibility' => ['sometimes', 'in:PUBLIC,REGISTERED,RESTRICTED,ADMIN_ONLY'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'language' => ['nullable', 'string', 'max:30'],
            'page_count' => ['nullable', 'integer', 'min:0'],
            'published_at' => ['nullable', 'date'],
        ]);

        $doc = Document::query()->findOrFail($id);
        $oldVisibility = $doc->visibility;

        $doc->fill($data);
        $doc->save();

        if (array_key_exists('visibility', $data) && $oldVisibility !== $doc->visibility) {
            $audit->record($request->user(), 'documents.visibility.update', 'Document', $doc->getKey(), [
                'from' => $oldVisibility,
                'to' => $doc->visibility,
            ], $request);
        } else {
            $audit->record($request->user(), 'documents.update', 'Document', $doc->getKey(), [], $request);
        }

        return response()->json($doc);
    }

    public function destroy(Request $request, string $id, AuditService $audit)
    {
        $doc = Document::query()->findOrFail($id);
        $doc->delete();

        $audit->record($request->user(), 'documents.delete', 'Document', $id, [], $request);

        return response()->noContent();
    }
}

