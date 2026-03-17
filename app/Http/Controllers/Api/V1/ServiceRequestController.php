<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RequestStatusHistory;
use App\Models\ServiceRequest;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ServiceRequestController extends Controller
{
    public function store(Request $request, AuditService $audit)
    {
        $data = $request->validate([
            'requester_full_name' => ['required', 'string', 'max:160'],
            'requester_email' => ['required', 'email', 'max:191'],
            'requester_phone' => ['nullable', 'string', 'max:30'],
            'type' => ['required', 'in:CONSULTATION,COPY_CERTIFIED,AUTHENTICATION,RESEARCH'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ]);

        $user = $request->user();

        $sr = ServiceRequest::query()->create([
            'requester_user_id' => $user?->getKey(),
            'requester_full_name' => $data['requester_full_name'],
            'requester_email' => mb_strtolower($data['requester_email']),
            'requester_phone' => $data['requester_phone'] ?? null,
            'type' => $data['type'],
            'status' => 'SUBMITTED',
            'priority' => 'NORMAL',
            'subject' => $data['subject'],
            'description' => $data['description'],
            'closed_at' => null,
        ]);

        RequestStatusHistory::query()->create([
            'request_id' => $sr->getKey(),
            'from_status' => 'DRAFT',
            'to_status' => 'SUBMITTED',
            'changed_at' => Carbon::now(),
            'changed_by' => $user?->getKey(),
            'note' => null,
        ]);

        $audit->record($user, 'requests.create', 'ServiceRequest', $sr->getKey(), ['type' => $sr->type], $request);

        return response()->json($sr, 201);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $q = ServiceRequest::query()->where('requester_user_id', $user->getKey())->orderByDesc('created_at');

        return response()->json($q->paginate((int) $request->query('per_page', 20)));
    }

    public function show(Request $request, string $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $sr = ServiceRequest::query()->findOrFail($id);
        if ($sr->requester_user_id !== $user->getKey()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($sr);
    }
}

