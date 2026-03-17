<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class UserAdminController extends Controller
{
    public function index(Request $request)
    {
        $q = User::query()->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }

        if ($email = $request->query('email')) {
            $q->where('email', 'like', '%'.mb_strtolower($email).'%');
        }

        return response()->json($q->paginate((int) $request->query('per_page', 20)));
    }

    public function updateStatus(Request $request, string $id, AuditService $audit)
    {
        $data = $request->validate([
            'status' => ['required', 'in:ACTIVE,SUSPENDED,PENDING,DELETED'],
        ]);

        $user = User::query()->findOrFail($id);
        $old = $user->status;
        $user->status = $data['status'];
        $user->save();

        $audit->record($request->user(), 'users.status.update', 'User', $user->getKey(), [
            'from' => $old,
            'to' => $user->status,
        ], $request);

        return response()->json($user);
    }

    public function assignRoles(Request $request, string $id, AuditService $audit)
    {
        $data = $request->validate([
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['required', 'string'],
        ]);

        $user = User::query()->findOrFail($id);

        $roleIds = Role::query()->whereIn('id', $data['role_ids'])->pluck('id')->all();
        $now = Carbon::now();

        $pivotData = [];
        foreach ($roleIds as $roleId) {
            $pivotData[$roleId] = [
                'assigned_at' => $now,
                'assigned_by' => $request->user()?->getKey(),
            ];
        }

        $user->roles()->syncWithoutDetaching($pivotData);

        $audit->record($request->user(), 'users.roles.assign', 'User', $user->getKey(), [
            'role_ids' => $roleIds,
        ], $request);

        return response()->json([
            'user_id' => $user->getKey(),
            'role_ids' => $user->roles()->pluck('roles.id')->all(),
        ]);
    }
}

