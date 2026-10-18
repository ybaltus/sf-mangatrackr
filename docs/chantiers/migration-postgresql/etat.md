# État — migration-postgresql

Dernière mise à jour : 2026-10-18 (chef de projet)

**Phase courante** : 1. Validation de l'extraction — **terminée** (extraction correcte) ; 2. Audit **terminé** (T3) ; décisions D1-D9 prises ; 3. Schéma et infra en cours.

## Tâches
| ID | Tâche | Agent | Dépend de | Statut | Rapport |
|---|---|---|---|---|---|
| T0 | Organisation : skills, agents, suivi, ADR 0011 | chef | — | terminé | — |
| T1 | Analyse hors ligne du dump (dry-run) | chef | — | terminé | `rapports/001-attendu.json` |
| T2 | Import dans `sf_mangatrackr_app_import` + vérifications | migration-importeur | T1, base joignable | terminé | `rapports/003-migration-importeur-import-dump.md` |
| T3 | Audit compatibilité MariaDB → PG 18 | migration-auditeur | — (lecture seule) | terminé | `rapports/002-migration-auditeur-audit-compatibilite.md` |
| T4 | Schéma et infra PG 18 (Docker, `pdo_pgsql`, env, migrations, CI) | migration-schema | T3, ADR (inclut correction des 2 index, cf. décisions) | en cours (T4.1, T4.2, T4.7 terminés ; T4.3 en attente du rebuild) | `rapports/004-…`, `rapports/005-developpeur-corrections-portables.md` |
| T5 | Transfert des données + parité | migration-donnees | T2, T4 | à faire | — |
| T6 | Recette QA + revue | testeur, relecteur | T5 | à faire | — |
| T7 | Plan de bascule homelab + ADR + dette technique | chef, relecteur | T6 | à faire | — |

## Sous-tâches T4 / T5 (proposées par l'audit, rapport 002 § Recommandation)
- T4.1 ADR migration PG 18 (D1-D5, D7, D8) · T4.2 Docker `postgres:18` + `pdo_pgsql` (rebuild hôte)
  · T4.3 config `.env`/`doctrine.yaml`/mapping `User` · T4.4 baseline PG (archive des 15 migrations,
  corrige les 2 index) · T4.5 vérifs (migrate, diff vide, schema:validate) · T4.6 CI
  · T4.7 `developpeur` : corrections portables (NULL en tête, `LOWER`, emails, OrderBy) + tests
  · T4.8 `developpeur` : recherche insensible aux accents (`unaccent`, fonction DQL), PG seulement, après T4.4.
- T5.1 outil PHP/DBAL · T5.2 transfert ordonné · T5.3 recalage des 16 séquences
  · T5.4 parité `--compare` · T5.5 procédure rejouable pour la bascule.

## Blocages
- **Attente utilisateur** : rebuild des conteneurs + vérifications 1 à 9 de `rapports/007-…` § « Commandes à lancer depuis l'hôte » (T4.2 écrit, non testé). Bloque T4.3.

## Décisions à prendre
- Aucune en attente.

## Points de vigilance (rapport 004)
- Dès T4.3/T4.4, la QA ne tourne plus sur MariaDB (baseline PG seule, `app_user`) : le rebuild
  PG 18 + `pdo_pgsql` par l'utilisateur doit être fait **avant** T4.3.
- `CREATE EXTENSION unaccent` : décision du chef → dans la baseline (T4.4) ; extension « trusted »
  depuis PG 13, droit du propriétaire de la base à confirmer sur un vrai serveur.
- T5.4 : `inventaire.php --compare` doit gérer la correspondance `user` → `app_user`.
- T4.7 modifie l'authentification (`security.yaml` : `property: email` retiré, `UserLoaderInterface`) :
  relu (rapport 006) : **validé avec réserves**, 0 bloquant ; R1-R3 corrigés en T4.7-bis (rapport 008, 192 tests / 541 assertions).
- T4.3 : harmoniser `DB_PASSWORD` (compose) / `DB_USER_PASSWORD` (`.env`) ; port 5432 de l'hôte possiblement occupé ;
  rôle `mangatrackr` avec `CREATEDB` en dev seulement (pas en prod homelab).
- Corrigés en T4.7-bis : `oauthTestEmails` comparé avec la casse ; valeur non typée passée à `UserBadge` dans `AppAuthenticator`.

## Décisions prises
- 2026-10-18 : cible PostgreSQL 18 ; production future sur le homelab ; dump désindexé et
  `ressources/*.sql` ignoré par git ; agents au modèle de la session ; organisation ADR 0011.
- 2026-10-18 : les 2 écarts d'index hérités (index de `manga_tenrai_api` à renommer, index
  composite de `messenger_messages`) seront corrigés **dans la baseline PostgreSQL** (T4),
  sans migration MariaDB dédiée. À intégrer au périmètre de T4.
- 2026-10-18 (audit T3, rapport 002) : D1 baseline PG unique + archivage des 15 migrations MySQL ;
  **D2 table `user` renommée `app_user`** (le transfert T5 doit faire la correspondance) ;
  D3 IDENTITY ; **D4 recherche insensible à la casse ET aux accents** (`LOWER` + `unaccent`) ;
  D5 collation ICU `fr` ; D6 coexistence MariaDB/PG jusqu'à T5, schéma Hostinger gelé ;
  D7 `json` ; D8 `disable_type_comments: true` ; D9 transfert par script PHP/DBAL.
- 2026-10-18 : index unique sur `LOWER(email)` (table `app_user`) dans la baseline PG (T4.4).

## Prochaine action
1. (Utilisateur) Rebuild du devcontainer, puis vérifications 1 à 9 de `rapports/007-…` § « Commandes à lancer depuis l'hôte ».
2. (Chef, nouvelle session) Vérifier depuis `app` : `php -m | grep pdo_pgsql`, connexion PDO à `postgres:5432`,
   MariaDB toujours joignable (`sf_mangatrackr_app_import` présente), `make test-only` vert (192 tests).
3. Lancer T4.3 (`migration-schema`, rapport 009) : `.env`/`.env.test` vers PG 18, harmoniser `DB_PASSWORD`,
   `doctrine.yaml` (IDENTITY, `disable_type_comments`), mapping `User` → `app_user`. Puis T4.4 (baseline, rapport 010).
