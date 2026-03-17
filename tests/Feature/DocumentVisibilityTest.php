<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\FondsArchive;
use App\Models\PasswordCredential;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DocumentVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['jwt.secret' => 'test-secret']);
    }

    public function test_public_document_is_accessible_without_auth()
    {
        $fonds = FondsArchive::query()->create([
            'code' => 'F-1',
            'name' => 'Fonds 1',
            'description' => null,
            'period_label' => null,
            'unesco' => false,
            'estimated_documents_count' => null,
        ]);

        $doc = Document::query()->create([
            'fonds_id' => $fonds->getKey(),
            'title' => 'Doc 1',
            'reference_code' => null,
            'summary' => null,
            'type' => 'OTHER',
            'visibility' => 'PUBLIC',
            'start_date' => null,
            'end_date' => null,
            'language' => null,
            'page_count' => null,
            'published_at' => null,
        ]);

        $this->getJson('/api/v1/documents/'.$doc->getKey())->assertOk();
    }

    public function test_registered_document_requires_auth()
    {
        $fonds = FondsArchive::query()->create([
            'code' => 'F-2',
            'name' => 'Fonds 2',
            'description' => null,
            'period_label' => null,
            'unesco' => false,
            'estimated_documents_count' => null,
        ]);

        $doc = Document::query()->create([
            'fonds_id' => $fonds->getKey(),
            'title' => 'Doc 2',
            'reference_code' => null,
            'summary' => null,
            'type' => 'OTHER',
            'visibility' => 'REGISTERED',
            'start_date' => null,
            'end_date' => null,
            'language' => null,
            'page_count' => null,
            'published_at' => null,
        ]);

        $this->getJson('/api/v1/documents/'.$doc->getKey())->assertStatus(403);
    }

    public function test_restricted_document_requires_permission()
    {
        $fonds = FondsArchive::query()->create([
            'code' => 'F-3',
            'name' => 'Fonds 3',
            'description' => null,
            'period_label' => null,
            'unesco' => false,
            'estimated_documents_count' => null,
        ]);

        $doc = Document::query()->create([
            'fonds_id' => $fonds->getKey(),
            'title' => 'Doc 3',
            'reference_code' => null,
            'summary' => null,
            'type' => 'OTHER',
            'visibility' => 'RESTRICTED',
            'start_date' => null,
            'end_date' => null,
            'language' => null,
            'page_count' => null,
            'published_at' => null,
        ]);

        $user = User::factory()->create(['email' => 'restricted@example.com']);
        PasswordCredential::query()->create([
            'user_id' => $user->getKey(),
            'password_hash' => Hash::make('Password123!'),
            'failed_login_count' => 0,
            'locked_until' => null,
            'password_changed_at' => now(),
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'restricted@example.com',
            'password' => 'Password123!',
        ])->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$login->json('access_token'))
            ->getJson('/api/v1/documents/'.$doc->getKey())
            ->assertStatus(403);

        $permission = Permission::query()->create(['code' => 'documents.restricted.read', 'description' => null]);
        $role = Role::query()->create(['name' => 'reader', 'description' => null]);
        $role->permissions()->sync([$permission->getKey()]);
        $user->roles()->attach($role->getKey(), ['assigned_at' => now(), 'assigned_by' => $user->getKey()]);

        $this->withHeader('Authorization', 'Bearer '.$login->json('access_token'))
            ->getJson('/api/v1/documents/'.$doc->getKey())
            ->assertOk();
    }
}

