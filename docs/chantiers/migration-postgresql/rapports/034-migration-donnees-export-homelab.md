# 034 — migration-donnees — export de la base de dev pour le homelab

- Tâche : T7
- Date : 2026-10-18
- Statut : terminé

## Objectif
Outil simple et rejouable pour exporter la base de dev PostgreSQL (`sf_mangatrackr_app`, données réelles à parité, rapport 032) et l'importer sur le homelab ; preuve de restauration ; procédure d'une page.

## Ce qui a été fait
- `bin/pg-common.sh` (lecture de `DATABASE_URL` : environnement, sinon `.env`/`.env.local` via Dotenv ; `%%` ramené à `%` car `%env(resolve:)` ; mot de passe uniquement en `PGPASSWORD`, jamais en argument).
- `bin/pg-export.sh` et `bin/pg-import.sh`, exposés par `make pg-export` et `make pg-import DUMP=… CONFIRM_TARGET=hôte/base` (Makefile).
- `.gitignore` : `/ressources/*.dump` et `*.dump.part`. L'export refuse d'écrire si le fichier n'est pas ignoré (`git check-ignore`).
- `docs/chantiers/migration-postgresql/homelab.md` (procédure), bandeau « Procédure simplifiée retenue le 2026-10-18 » en tête de `plan-bascule.md` et de l'ADR 0013 (statut inchangé).
- Choix scripts `bin/` + cibles Makefile minces : le dépôt expose déjà ses actions par `make`, et `bin/` est présent sur le homelab (le script de transfert sous `.claude/skills/` est lié à MariaDB et n'a pas à être déployé).

## Format et justification
`pg_dump -Fc --no-owner --no-privileges` : archive compressée (4,1 Mo contre 17 Mo de SQL source), restauration atomique (`--single-transaction --exit-on-error`), `--clean --if-exists` possible, TOC inspectable sans exposer les données. Sans propriétaire ni GRANT car le rôle du homelab peut différer. Contrepartie : `pg_restore` >= 18 requis ; un dump SQL serait plus filtrable pour un serveur ancien.

## Résultats
- Dump : `/var/www/ressources/mangatrackr_sf_mangatrackr_app_20261018-163009.dump`, 4 112 051 octets, droits 600, ignoré par git (`.gitignore:58`). Contenu non affiché (données personnelles).
- Contenu (TOC) : extension `unaccent` (+ COMMENT), 23 tables dont `doctrine_migration_versions`, 19 `SEQUENCE SET` (setval IDENTITY). Donc pas de conflit avec la baseline : l'extension vient du dump, la baseline n'est pas rejouée.
- Restauration dans `sf_mangatrackr_app_scratch` (ICU `fr`, comme le dev ; PG 18.6 vers 18.6) : code 0 ; sans confirmation : code 5 ; sur base non vide sans `IMPORT_REPLACE=1` : code 5 ; avec `IMPORT_REPLACE=1` : code 0 (rejouable). Scratch supprimée ensuite (0 base `%scratch%`).
- Parité dev ↔ scratch (`inventaire.php --compare`, tolérant puis `--strict`, avec `--require-baseline`) : 23 tables OK sur 23 (lignes, empreintes, colonnes), baseline présente. Vérification complémentaire sur les JSON : 19 séquences, `next` identique dev/scratch, aucune `next < MAX+1`, 23 tables identiques (hash inclus), dans les deux modes.
- Sur la base restaurée (DATABASE_URL surchargée) : `migrations:status` = 1 exécutée, 0 nouvelle, courante `Version20261018145659` ; `migrations:migrate` = « Already at the latest version » ; `schema:validate` = « in sync » ; `migrations:diff` = « No changes detected » (aucun fichier créé, `git status migrations` propre).
- `unaccent('Éléphant à côté')` = « Elephant a cote » ; requête `unaccent(lower(title)) LIKE unaccent('%é%')` : 5638 lignes. Extensions : plpgsql, unaccent 1.1. Insertion test (rollback) : l'IDENTITY a fourni l'id 7 (suite de la séquence restaurée).
- `make qa-php-stan` : No errors. `make test-only` : OK (226 tests, 580 assertions).

## Écarts, risques, points non vérifiés
- `--compare` PostgreSQL contre PostgreSQL signale 19 « écarts » de séquences (« AUTO_INCREMENT source absent ») : règle D3 propre à une source MariaDB, sans objet ici ; remplacée par la comparaison directe des `next` (0 écart). Aucune modification de `inventaire.php`.
- Version PostgreSQL du homelab inconnue : seul 18 → 18 est prouvé. 17 probable (non testé) ; <= 16 non garanti (`SET transaction_timeout` émis par pg_dump 17+) ; `pg_restore` doit être >= 18 ; ICU requis. Non testé : rôle non superutilisateur sur le homelab (création de `unaccent` par le propriétaire, « trusted » en théorie), `make` absent (alternative `sh bin/pg-import.sh` documentée).
- Le dump est un instantané de la base de dev à 16:30 ; en refaire un si la base change. Il reste dans `ressources/` avec des données personnelles : à supprimer après transfert.
- Aucun commit ; `etat.md`, `CLAUDE.md`, migrations non touchés ; base de dev lue seulement (pg_dump, inventaires) ; base de test utilisée uniquement par `make test-only`.

## Recommandation pour la suite
Relire `homelab.md`, copier le dump par SSH, tester l'import sur le homelab (premier point à confirmer : version PostgreSQL), puis supprimer le dump. Décider du commit (scripts, Makefile, .gitignore, docs) et du statut de l'ADR 0013.
