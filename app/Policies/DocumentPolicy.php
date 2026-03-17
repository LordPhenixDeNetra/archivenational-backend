<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function view(?User $user, Document $document): bool
    {
        if ($document->visibility === 'PUBLIC') {
            return true;
        }

        if ($document->visibility === 'REGISTERED') {
            return $user !== null;
        }

        if ($document->visibility === 'ADMIN_ONLY') {
            return $user !== null && $user->hasPermission('admin.access');
        }

        if ($document->visibility !== 'RESTRICTED') {
            return false;
        }

        if ($user === null) {
            return false;
        }

        if ($user->hasPermission('documents.restricted.read')) {
            return true;
        }

        $allow = $document->accessPolicies()->where('rule', 'ALLOW')->whereNull('conditions_json')->exists();
        if ($allow) {
            return true;
        }

        $deny = $document->accessPolicies()->where('rule', 'DENY')->whereNull('conditions_json')->exists();
        if ($deny) {
            return false;
        }

        return false;
    }
}

