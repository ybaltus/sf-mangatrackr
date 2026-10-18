# État — migration-postgresql

Dernière mise à jour : 2026-10-18 (chef de projet, clôture T4)

**Phase courante** : 1. Validation de l'extraction — **terminée** (extraction correcte) ; 2. Audit **terminé** (T3) ; décisions D1-D9 prises ; 3. Schéma et infra en cours.

## Tâches
| ID | Tâche | Agent | Dépend de | Statut | Rapport |
|---|---|---|---|---|---|
| T0 | Organisation : skills, agents, suivi, ADR 0011 | chef | — | terminé | — |
| T1 | Analyse hors ligne du dump (dry-run) | chef | — | terminé | `rapports/001-attendu.json` |
| T2 | Import dans `sf_mangatrackr_app_import` + vérifications | migration-importeur | T1, base joignable | terminé | `rapports/003-migration-importeur-import-dump.md` |
| T3 | Audit compatibilité MariaDB → PG 18 | migration-auditeur | — (lecture seule) | terminé | `rapports/002-migration-auditeur-audit-compatibilite.md` |
| T4 | Schéma et infra PG 18 (Docker, `pdo_pgsql`, env, migrations, CI) | migration-schema | T3, ADR (inclut correction des 2 index, cf. décisions) | terminé (T4.1, T4.2 vérifié, T4.7 terminés ; T4.3, T4.4, T4.5 terminés ; T4.8 terminé `012` ; T4.6 écrit `011`, non exécuté sur GitHub ; données résiduelles corrigées `013` ; revue `014` (validé avec réserves) et validation `015` faites ; `DATABASE_URL` CI corrigée `016`) → **T4 terminé**, sous réserve du 1er run CI réel (PR brouillon, sur accord) | `004-…`, `005-…`, `009-…`, `010-migration-schema-baseline-postgresql.md` |
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
- **Attente utilisateur** : recréer `app` depuis l'hôte (le shell exporte encore `DATABASE_URL` MySQL),
  puis lancer `make test` (drop/create PG non encore vérifié). `.env.local` : clés OAuth seules, rien à adapter.
- Rebuild fait ; vérifié par le chef depuis `app` (2026-10-18) : 4, 5, 7, 8 OK (PG 18.6,
  ICU `fr`, rôle non super avec `CREATEDB`, mot de passe par défaut `ChangeMe`) ; 6 partielle
  (`unaccent` 1.1 disponible et `trusted`, `CREATE EXTENSION` à prouver en T4.4) ; 9 OK
  (MariaDB dev/test intacts, `make test-only` vert : 192 tests / 541 assertions).
- Non vérifiable depuis `app` : présence de `sf_mangatrackr_app_import` (aucun droit du rôle
  applicatif). À contrôler avant T5 (`docker exec mangatrackr_db …`), sinon réimport du dump.

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
- Test instable : 1 erreur isolée (192 tests / 540 assertions) au 1er `make test-only` après
  rebuild, non reproduite sur 7 relances ; test non identifié (rien dans `var/log/test.log`).
- CI cassée jusqu'à T4.6. Données résiduelles des tests (+2 `app_user`, +2 `user_invitation_code` par passage :
  `CreateUserCommandTest`, `InvitationControllerTest`, `EasyAdminSubscriberTest`), préexistant, ADR 0007 non respecté.
- DBAL 4 : revérifier qu'aucun diff ne propose de supprimer l'index d'expression `LOWER(email)`.
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
1. (Utilisateur) Décider du commit de T4 (non commité : ci.yml, doctrine.yaml, MangaRepository, tests, `src/Doctrine/`, trait de purge, rapports 011-016). Découpage suggéré : `feat:` UNACCENT, `test:` purge, `fix:` CI.
2. (Chef) T5 : vérifier d'abord la présence de `sf_mangatrackr_app_import` (`docker exec mangatrackr_db …`), sinon réimport du dump, puis `migration-donnees` (T5.1-T5.5).
3. Dette à tracer en T7 : PHP Insights (qa-all rc=2, cause non isolée), deprecations identity/ORM 3, pg_trgm pour la recherche, `searchByTitles` et EasyAdmin sensibles aux accents, dama/doctrine-test-bundle, `%` à doubler dans `DATABASE_URL` (resolve), secret `DB_ROOT_PASSWORD` à supprimer sur GitHub.
