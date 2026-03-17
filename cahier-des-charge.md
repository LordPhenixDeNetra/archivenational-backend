# Cahier des charges – Backend Laravel (API) – Portail “Archives Nationales du Sénégal”

## 0) Contexte & Objectifs
- Fournir une API sécurisée pour alimenter:
  - le site public (présentation, fonds, catalogue documents, bibliothèque numérique)
  - la visionneuse de documents (PDF / images + métadonnées)
  - les services en ligne (demandes: consultation, copies, authentification, recherche)
  - l’espace administration (gestion utilisateurs, documents, fonds, demandes, statistiques)
  - la librairie (produits, commandes, paiements, livraisons)


## 1) Stack Backend (Imposé)

- **Laravel 10/11**
- **MySQL 8+** (InnoDB, utf8mb4)
- **JWT**:
  - Recommandé: `tymon/jwt-auth` (ou équivalent)
  - Access token court (ex: 15 min) + refresh token long (ex: 7–30 jours)
- Cache/Queue (recommandé): Redis + Horizon (optionnel)
- Stockage fichiers: S3/MinIO (prod) + local (dev)

***

## 2) Auth JWT (exigences strictes)

### 2.1 Tokens

- **Access Token (JWT)**:
  - TTL court (10–20 min)
  - Contenu minimal: `sub` (userId), `iat`, `exp`, `jti`
  - **Ne jamais stocker** l’access token en localStorage (préférer mémoire) si possible.
- **Refresh Token**:
  - Stocké côté serveur dans table `refresh_tokens`
  - **Stocker hash** (SHA-256) du refresh token (jamais le token en clair)
  - Rotation obligatoire:
    - chaque refresh invalide l’ancien refresh token et en émet un nouveau
- Révocation:
  - Logout révoque le refresh token courant (ou tous si “logout all devices”).

### 2.2 Sécurité login

- Rate limit login (IP + email).
- Compteur échecs + lock temporaire.
- Messages d’erreur neutres.

### 2.3 RBAC

- Middleware: `auth:jwt` + `permission:<code>`
- Policies Laravel pour contrôler accès par ressource (Document, Request, Order…).

***

## 3) Modèle de Données (MySQL) – Tables & Index (MVP)

### 3.1 users

- `users`
  - `id` BIGINT UNSIGNED PK AI
  - `email` VARCHAR(191) UNIQUE
  - `phone` VARCHAR(30) NULL
  - `first_name`, `last_name` VARCHAR(80)
  - `status` ENUM('ACTIVE','SUSPENDED','PENDING','DELETED') default 'ACTIVE'
  - `last_login_at` DATETIME NULL
  - `created_at`, `updated_at`

**Index**: `UNIQUE(email)`, `INDEX(status)`

### 3.2 credentials

- `password_credentials`
  - `id` BIGINT PK
  - `user_id` BIGINT UNIQUE FK users(id)
  - `password_hash` VARCHAR(255)
  - `failed_login_count` INT default 0
  - `locked_until` DATETIME NULL
  - `password_changed_at` DATETIME NULL

### 3.3 refresh tokens

- `refresh_tokens`
  - `id` BIGINT PK
  - `user_id` BIGINT INDEX
  - `token_hash` CHAR(64) UNIQUE
  - `expires_at` DATETIME INDEX
  - `revoked_at` DATETIME NULL INDEX
  - `user_agent` VARCHAR(255) NULL
  - `ip` VARCHAR(45) NULL
  - `created_at`

### 3.4 roles/permissions

- `roles` (`id`, `name` UNIQUE, `description`)
- `permissions` (`id`, `code` UNIQUE, `description`)
- `role_user` (PK composite `role_id`,`user_id`, indexes)
- `permission_role` (PK composite `permission_id`,`role_id`, indexes)

### 3.5 Catalogue archives

- `fonds_archives`
  - `id` BIGINT PK
  - `code` VARCHAR(32) UNIQUE
  - `name` VARCHAR(255)
  - `description` TEXT
  - `period_label` VARCHAR(80) NULL
  - `unesco` TINYINT(1)
  - `estimated_documents_count` INT NULL
  - timestamps
    **Index**: `UNIQUE(code)`, `INDEX(unesco)`
- `documents`
  - `id` BIGINT PK
  - `fonds_id` BIGINT INDEX FK
  - `title` VARCHAR(255) INDEX
  - `reference_code` VARCHAR(80) NULL INDEX
  - `summary` TEXT NULL
  - `type` ENUM(...) INDEX
  - `visibility` ENUM('PUBLIC','REGISTERED','RESTRICTED','ADMIN\_ONLY') INDEX
  - `start_date` DATE NULL INDEX
  - `end_date` DATE NULL INDEX
  - `language` VARCHAR(30) NULL
  - `page_count` INT NULL
  - `published_at` DATETIME NULL INDEX
  - timestamps
    **Index**: `(fonds_id, visibility, published_at)`, `(type, visibility)`
- `document_files`
  - `id` BIGINT PK
  - `document_id` BIGINT INDEX
  - `kind` ENUM('PDF','IMAGE\_JPEG','IMAGE\_PNG','THUMBNAIL','OCR\_TEXT','OTHER')
  - `storage_key` VARCHAR(512) UNIQUE
  - `content_type` VARCHAR(120)
  - `size_bytes` BIGINT
  - `sha256` CHAR(64) INDEX
  - `version` INT
  - `uploaded_by` BIGINT NULL
  - `uploaded_at` DATETIME
    **Index**: `(document_id, kind)`, `(document_id, version)`
- `tags` (`id`, `name` UNIQUE, `slug` UNIQUE)
- `document_tag` (PK composite `document_id`,`tag_id`)
- `access_policies`
  - `id` BIGINT PK
  - `document_id` BIGINT INDEX
  - `rule` ENUM('ALLOW','DENY','REQUIRE\_MFA','REQUIRE\_APPROVAL')
  - `conditions_json` JSON NULL
- `document_view_events`
  - `id` BIGINT PK
  - `document_id` BIGINT INDEX
  - `user_id` BIGINT NULL INDEX
  - `viewed_at` DATETIME INDEX
  - `ip` VARCHAR(45) NULL
  - `user_agent` VARCHAR(255) NULL

### 3.6 Demandes services

- `service_requests`
  - `id` BIGINT PK
  - `requester_user_id` BIGINT NULL INDEX
  - `requester_full_name` VARCHAR(160)
  - `requester_email` VARCHAR(191) INDEX
  - `requester_phone` VARCHAR(30) NULL
  - `type` ENUM('CONSULTATION','COPY\_CERTIFIED','AUTHENTICATION','RESEARCH') INDEX
  - `status` ENUM('DRAFT','SUBMITTED','IN\_REVIEW','NEEDS\_INFO','APPROVED','REJECTED','IN\_PROGRESS','COMPLETED','CANCELLED') INDEX
  - `priority` ENUM('LOW','NORMAL','HIGH','URGENT') INDEX
  - `subject` VARCHAR(255)
  - `description` TEXT
  - `closed_at` DATETIME NULL INDEX
  - timestamps
    **Index**: `(status, created_at)`, `(type, status)`
- `request_attachments`
  - `id` BIGINT PK
  - `request_id` BIGINT INDEX
  - `storage_key` VARCHAR(512) UNIQUE
  - `content_type` VARCHAR(120)
  - `size_bytes` BIGINT
  - `sha256` CHAR(64) INDEX
  - `uploaded_at` DATETIME
- `request_status_histories`
  - `id` BIGINT PK
  - `request_id` BIGINT INDEX
  - `from_status`, `to_status` ENUM(...)
  - `changed_at` DATETIME INDEX
  - `changed_by` BIGINT NULL
  - `note` TEXT NULL

### 3.7 Librairie (optionnel si vous la gardez)

- `products`, `inventory_items`, `orders`, `order_items`, `payments`, `shipments`, `addresses`
- Index principaux: `orders(status, created_at)`, `payments(order_id,status)`, `products(active, sku unique)`

### 3.8 Audit

- `audit_logs`
  - `id` BIGINT PK
  - `actor_user_id` BIGINT NULL INDEX
  - `action` VARCHAR(80) INDEX
  - `entity_type` VARCHAR(80) INDEX
  - `entity_id` BIGINT NULL INDEX
  - `metadata_json` JSON NULL
  - `ip` VARCHAR(45) NULL
  - `user_agent` VARCHAR(255) NULL
  - `created_at` DATETIME INDEX

***

## 4) API REST – Endpoints (v1)

### 4.1 Auth JWT

- `POST /api/v1/auth/login`
- `POST /api/v1/auth/refresh`
- `POST /api/v1/auth/logout`
- `GET /api/v1/auth/me`
- `POST /api/v1/auth/forgot-password`
- `POST /api/v1/auth/reset-password`

**Contraintes**

- `refresh` valide uniquement si refresh token actif + non expiré + non révoqué.
- Rotation refresh obligatoire.

### 4.2 Public catalogue

- `GET /api/v1/fonds`
- `GET /api/v1/fonds/{id}`
- `GET /api/v1/documents` (search + filters + pagination)
- `GET /api/v1/documents/{id}`
- `GET /api/v1/documents/{id}/files`
- `GET /api/v1/document-files/{id}/download` (stream + contrôle permissions)
- `POST /api/v1/documents/{id}/view` (optionnel tracking)

### 4.3 Admin (RBAC)

- `POST/PUT/DELETE /api/v1/admin/fonds`
- `POST/PUT/DELETE /api/v1/admin/documents`
- `POST /api/v1/admin/documents/{id}/files` (upload)
- `DELETE /api/v1/admin/document-files/{id}`
- `POST/DELETE /api/v1/admin/tags`
- `GET /api/v1/admin/users` (gestion utilisateurs)
- `PATCH /api/v1/admin/users/{id}/status`
- `POST /api/v1/admin/users/{id}/roles`

### 4.4 Demandes

- `POST /api/v1/requests`
- `GET /api/v1/requests` (user: ses demandes)
- `GET /api/v1/requests/{id}`
- `POST /api/v1/requests/{id}/attachments`
- Admin:
  - `GET /api/v1/admin/requests`
  - `PATCH /api/v1/admin/requests/{id}/status`
  - `POST /api/v1/admin/requests/{id}/notes`

### 4.5 Statistiques (Admin)

- `GET /api/v1/admin/stats/overview`
- `GET /api/v1/admin/stats/requests`
- `GET /api/v1/admin/stats/documents`

***

## 5) Règles de Sécurité d’Accès Document (obligatoire)

- `PUBLIC`: accessible à tous.
- `REGISTERED`: nécessite JWT valide.
- `RESTRICTED`: nécessite permission `documents.restricted.read` ou AccessPolicy ALLOW.
- `ADMIN_ONLY`: nécessite permission `admin.access`.

Implémentation Laravel:

- `DocumentPolicy@view(User $user = null, Document $doc)`
- Middleware `auth:jwt` uniquement pour les routes non publiques + `can:view,document`.

***

## 6) Spécifications Upload Fichiers

- Max size: configurable (ex: 50MB)
- Types permis: `application/pdf`, `image/jpeg`, `image/png`
- Calcul `sha256` obligatoire.
- Stockage:
  - `storage_key = documents/{documentId}/v{version}/{uuid}.pdf`
- Download:
  - endpoint contrôlé + `Storage::download()` ou URL signée si S3.

***

## 7) Observabilité & Audit

- Audit obligatoire sur:
  - création/modif/suppression documents, changement `visibility`
  - changement statut demandes
  - assignation rôles/permissions
- Log d’erreur technique séparé des audits métier.

***

## 8) Livrables attendus (Laravel)

- Migrations + seeders (rôles/permissions)
- Controllers + FormRequests + Resources (API response)
- JWT guard + refresh token rotation
- Policies + middlewares permissions
- Upload/download fichiers sécurisés
- Tests Feature (auth/permissions/documents/requests)
- Documentation API (OpenAPI ou Postman)

***

## 9) Matrice Permissions (exemple minimal)

- `admin.access`
- `users.read`, `users.write`
- `fonds.read`, `fonds.write`
- `documents.read`, `documents.write`, `documents.publish`, `documents.restricted.read`
- `requests.read`, `requests.manage`
- `stats.read`

***

