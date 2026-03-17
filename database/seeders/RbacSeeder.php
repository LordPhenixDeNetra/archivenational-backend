<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            ['code' => 'admin.access', 'description' => 'Accès administration'],
            ['code' => 'users.read', 'description' => 'Lire utilisateurs'],
            ['code' => 'users.write', 'description' => 'Gérer utilisateurs'],
            ['code' => 'fonds.read', 'description' => 'Lire fonds'],
            ['code' => 'fonds.write', 'description' => 'Gérer fonds'],
            ['code' => 'documents.read', 'description' => 'Lire documents'],
            ['code' => 'documents.write', 'description' => 'Gérer documents'],
            ['code' => 'documents.publish', 'description' => 'Publier documents'],
            ['code' => 'documents.restricted.read', 'description' => 'Lire documents restreints'],
            ['code' => 'requests.read', 'description' => 'Lire demandes'],
            ['code' => 'requests.manage', 'description' => 'Gérer demandes'],
            ['code' => 'stats.read', 'description' => 'Lire statistiques'],
        ];

        foreach ($permissions as $p) {
            Permission::query()->updateOrCreate(['code' => $p['code']], $p);
        }

        $admin = Role::query()->updateOrCreate(['name' => 'admin'], ['description' => 'Administrateur']);
        $archivist = Role::query()->updateOrCreate(['name' => 'archivist'], ['description' => 'Archiviste']);
        Role::query()->updateOrCreate(['name' => 'user'], ['description' => 'Utilisateur']);

        $allPermissionIds = Permission::query()->pluck('id')->all();
        $admin->permissions()->sync($allPermissionIds);

        $archivistPermissionIds = Permission::query()
            ->whereIn('code', ['fonds.read', 'documents.read', 'documents.write', 'requests.read', 'requests.manage'])
            ->pluck('id')
            ->all();
        $archivist->permissions()->sync($archivistPermissionIds);
    }
}

