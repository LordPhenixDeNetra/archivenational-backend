# Roadmap — Tâches restantes

Ce document liste les fonctionnalités à implémenter pour compléter le backend au-delà du MVP actuel.

## 1) Comptes & sécurité

- Inscription/activation compte (si requis)
- Validation email
- Forgot/reset password complet (tokens en DB + expiration + usage unique)
- Changement de mot de passe (endpoints + règles de complexité)
- MFA (TOTP/Email OTP/SMS) + enrollment/revocation
- “Logout all devices” + gestion multi refresh tokens (révocation ciblée)
- Durcissement rate limiting (login/refresh/admin) + protections anti-bruteforce
- Durcissement sécurité (headers, CORS prod, blocage IP admin si nécessaire)

## 2) Fichiers documents (bibliothèque numérique)

- Upload fichiers (admin) avec validations (MIME/taille)
- Calcul/stockage `sha256` + déduplication
- Versionning fichiers (v1, v2, …)
- Download sécurisé (streaming ou URL signée S3/MinIO) + contrôle d’accès
- Génération thumbnails/preview + OCR (jobs queue)
- Suppression/archivage fichiers + audit

## 3) Politiques d’accès avancées (AccessPolicy)

- Exploitation de `access_policies.conditions_json` (règles conditionnelles)
- `REQUIRE_APPROVAL` (workflow d’accès : demande/validation/rejet/historique)
- `REQUIRE_MFA` (contrôle dans la policy documents)
- Journal d’accès (consultations/téléchargements) + export

## 4) Catalogue (améliorations)

- CRUD tags (admin) + endpoints de recherche/filtrage
- Recherche avancée (filtres, tri, éventuellement plein texte)
- Endpoints “à la une / recommandés”
- Endpoints “collections” (module bibliothèque numérique)

## 5) Demandes de services (complétions)

- Pièces jointes sur demandes (upload/download)
- Messages/notes (usager ↔ admin) + historique
- Affectation à un agent + priorités + SLA
- Notifications (email) sur changements de statut
- Exports (PDF/CSV) des demandes (admin)

## 6) Administration (back-office)

- CRUD rôles
- CRUD permissions
- UI/API pour mapping rôles↔permissions
- Gestion utilisateurs complète (création, suspension, reset admin, etc.)
- Audit avancé (filtres/recherche/export)

## 7) Statistiques

- Endpoints stats demandes (par statut/type/période)
- Stats consultation documents (top documents, tendances)
- Payloads “dashboard-ready” + optimisation index/SQL

## 8) Documentation & qualité

- Compléter OpenAPI (schémas paginés, erreurs standardisées)
- Standardiser réponses API (Resources Laravel)
- Ajouter tests Feature/Integration (admin, policies, fichiers)
- CI (tests + lint) + environnements staging/prod

## 9) Déploiement & conformité cahier des charges

- Préparer configuration prod Hostinger (env, caches, permissions dossiers)
- Scripts de déploiement (migrate/seed/cache)
- Si exigé par le cahier des charges : upgrade Laravel 10/11 (=> PHP ≥ 8.1/8.2) + revalidation complète

