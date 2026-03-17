<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentViewEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $allowedVisibilities = ['PUBLIC'];
        if ($user) {
            $allowedVisibilities[] = 'REGISTERED';
            if ($user->hasPermission('documents.restricted.read')) {
                $allowedVisibilities[] = 'RESTRICTED';
            }
            if ($user->hasPermission('admin.access')) {
                $allowedVisibilities[] = 'ADMIN_ONLY';
            }
        }

        $q = Document::query()->whereIn('visibility', $allowedVisibilities);

        if ($fondsId = $request->query('fonds_id')) {
            $q->where('fonds_id', $fondsId);
        }

        if ($type = $request->query('type')) {
            $q->where('type', $type);
        }

        if ($search = $request->query('q')) {
            $q->where(function ($sub) use ($search) {
                $sub->where('title', 'like', '%'.$search.'%')
                    ->orWhere('reference_code', 'like', '%'.$search.'%');
            });
        }

        $q->orderByDesc('published_at')->orderByDesc('created_at');

        return response()->json($q->paginate((int) $request->query('per_page', 20)));
    }

    public function show(Request $request, string $id)
    {
        $document = Document::query()->findOrFail($id);
        $this->authorize('view', $document);

        return response()->json($document);
    }

    public function files(Request $request, string $id)
    {
        $document = Document::query()->findOrFail($id);
        $this->authorize('view', $document);

        $files = $document->files()->orderByDesc('uploaded_at')->get()->map(function ($file) {
            return [
                'id' => $file->getKey(),
                'kind' => $file->kind,
                'content_type' => $file->content_type,
                'size_bytes' => $file->size_bytes,
                'sha256' => $file->sha256,
                'version' => $file->version,
                'uploaded_at' => $file->uploaded_at,
            ];
        });

        return response()->json(['data' => $files]);
    }

    public function view(Request $request, string $id)
    {
        $document = Document::query()->findOrFail($id);
        $this->authorize('view', $document);

        DocumentViewEvent::query()->create([
            'document_id' => $document->getKey(),
            'user_id' => $request->user()?->getKey(),
            'viewed_at' => Carbon::now(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referrer' => $request->headers->get('referer'),
        ]);

        return response()->noContent();
    }
}

