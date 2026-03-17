# Archives Nationales du Sénégal — Backend (API Laravel)

Backend API pour le portail “Archives Nationales du Sénégal” : catalogue public, visionneuse (métadonnées), demandes de services, administration (RBAC), audit.

## Stack & prérequis

- PHP 8.0+
- Laravel 8.83
- MySQL 8+ (prod/dev) — tests en SQLite mémoire
- Authentification API : JWT (HS256) + refresh tokens en base (rotation)
- CORS : fruitcake/laravel-cors

## Démarrage rapide (dev)

1) Installer les dépendances

```bash
composer install
```

2) Configurer l’environnement

```bash
copy .env.example .env
php artisan key:generate
```

Définir un secret JWT (HMAC) :

```bash
php -r "echo bin2hex(random_bytes(32)).PHP_EOL;"
```

Puis renseigner `.env` :

- `JWT_SECRET=...`
- `JWT_TTL=15`
- `JWT_REFRESH_TTL_DAYS=30`

3) Configurer la base de données (MySQL)

Dans `.env` :

- `DB_CONNECTION=mysql`
- `DB_HOST=...`
- `DB_PORT=3306`
- `DB_DATABASE=...`
- `DB_USERNAME=...`
- `DB_PASSWORD=...`

4) Migrer & seeder

```bash
php artisan migrate
php artisan db:seed
```

Seed admin (optionnel) : définir dans `.env` puis relancer `db:seed`

- `SEED_ADMIN_EMAIL=admin@example.com`
- `SEED_ADMIN_PASSWORD=MotDePasseSolide`
- `SEED_ADMIN_FIRST_NAME=Admin`
- `SEED_ADMIN_LAST_NAME=User`

5) Lancer l’API

```bash
php artisan serve
```

## Authentification (JWT + refresh token)

Principes :

- Access token JWT court (par défaut 15 minutes)
- Refresh token long, stocké côté serveur dans `refresh_tokens` sous forme de hash SHA-256
- Rotation obligatoire : chaque refresh invalide l’ancien refresh token et en émet un nouveau

### Endpoints

- `POST /api/v1/auth/login`
- `POST /api/v1/auth/refresh`
- `POST /api/v1/auth/logout`
- `GET /api/v1/auth/me` (Bearer)

### Exemple (curl)

Login :

```bash
curl -X POST http://localhost:8000/api/v1/auth/login ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"admin@example.com\",\"password\":\"MotDePasseSolide\"}"
```

Me :

```bash
curl http://localhost:8000/api/v1/auth/me ^
  -H "Authorization: Bearer <ACCESS_TOKEN>"
```

Refresh :

```bash
curl -X POST http://localhost:8000/api/v1/auth/refresh ^
  -H "Content-Type: application/json" ^
  -d "{\"refresh_token\":\"<REFRESH_TOKEN>\"}"
```

## RBAC (rôles/permissions)

- Middleware `permission:<code>`
- Permissions seedées (exemples) :
  - `admin.access`
  - `users.read`, `users.write`
  - `fonds.read`, `fonds.write`
  - `documents.read`, `documents.write`, `documents.publish`, `documents.restricted.read`
  - `requests.read`, `requests.manage`
  - `stats.read`

## Catalogue public (MVP)

- `GET /api/v1/fonds`
- `GET /api/v1/fonds/{id}`
- `GET /api/v1/documents` (filtres: `q`, `fonds_id`, `type`, pagination)
- `GET /api/v1/documents/{id}`
- `GET /api/v1/documents/{id}/files`
- `POST /api/v1/documents/{id}/view` (tracking)

### Règles d’accès documents

Contrôle via policy :

- `PUBLIC` : accessible à tous
- `REGISTERED` : nécessite un utilisateur authentifié (Bearer)
- `RESTRICTED` : nécessite la permission `documents.restricted.read` (ou policy ALLOW basique)
- `ADMIN_ONLY` : nécessite `admin.access`

## Demandes de services (MVP)

Côté usager :

- `POST /api/v1/requests`
- `GET /api/v1/requests` (Bearer)
- `GET /api/v1/requests/{id}` (Bearer)

Côté admin :

- `GET /api/v1/admin/requests` (Bearer + permissions)
- `PATCH /api/v1/admin/requests/{id}/status` (Bearer + permissions)

## Administration (MVP)

Les routes admin sont sous `/api/v1/admin` et exigent :

- `auth:jwt`
- `permission:admin.access`
- permissions métier additionnelles selon endpoint

Exemples :

- `GET /api/v1/admin/users` (users.read)
- `PATCH /api/v1/admin/users/{id}/status` (users.write)
- `POST /api/v1/admin/users/{id}/roles` (users.write)
- CRUD fonds/documents (fonds.write / documents.write)

## Audit

Table `audit_logs` : enregistrement des actions métier sensibles (création/modif/suppression, changements de statut, etc.).

## Modèle de données (MVP)

Toutes les entités métier utilisent des UUID (`CHAR(36)` via `uuid` Laravel).

Tables principales :

- Identity & Access : `users`, `password_credentials`, `refresh_tokens`, `roles`, `permissions`, `role_user`, `permission_role`
- Catalogue : `fonds_archives`, `documents`, `document_files`, `tags`, `document_tag`, `access_policies`, `document_view_events`
- Services : `service_requests`, `request_attachments`, `request_status_histories`
- Audit : `audit_logs`

## Tests

Les tests sont exécutés en SQLite mémoire (configuration dans `phpunit.xml`).

```bash
php artisan test
```

## Qualité (formatage)

```bash
vendor/bin/pint
```

## Notes importantes

- Le projet est volontairement maintenu compatible PHP 8.0 (Laravel 8.83) pour l’environnement actuel.
- Les refresh tokens sont persistés en base (hash SHA-256), l’access token n’est pas stocké côté serveur.
- Les fonctionnalités upload/download (S3/MinIO, sha256, streaming) sont prévues mais non finalisées dans ce MVP.

