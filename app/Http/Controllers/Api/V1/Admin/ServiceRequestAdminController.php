<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\RequestStatusHistory;
use App\Models\ServiceRequest;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ServiceRequestAdminController extends Controller
{
    public function index(Request $request)
    {
        $q = ServiceRequest::query()->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }

        if ($type = $request->query('type')) {
            $q->where('type', $type);
        }

        return response()->json($q->paginate((int) $request->query('per_page', 20)));
    }

    public function updateStatus(Request $request, string $id, AuditService $audit)
    {
        $data = $request->validate([
            'status' => ['required', 'in:DRAFT,SUBMITTED,IN_REVIEW,NEEDS_INFO,APPROVED,REJECTED,IN_PROGRESS,COMPLETED,CANCELLED'],
            'note' => ['nullable', 'string'],
        ]);

        $sr = ServiceRequest::query()->findOrFail($id);
        $from = $sr->status;
        $to = $data['status'];

        if ($from === $to) {
            return response()->json($sr);
        }

        $sr->status = $to;
        if (in_array($to, ['COMPLETED', 'CANCELLED', 'REJECTED'], true)) {
            $sr->closed_at = Carbon::now();
        }
        $sr->save();

        RequestStatusHistory::query()->create([
            'request_id' => $sr->getKey(),
            'from_status' => $from,
            'to_status' => $to,
            'changed_at' => Carbon::now(),
            'changed_by' => $request->user()?->getKey(),
            'note' => $data['note'] ?? null,
        ]);

        $audit->record($request->user(), 'requests.status.update', 'ServiceRequest', $sr->getKey(), [
            'from' => $from,
            'to' => $to,
        ], $request);

        return response()->json($sr);
    }
}

