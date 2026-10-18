# 011 — migration-schema — CI GitHub sur PostgreSQL 18

- Tâche : T4.6
- Date : 2026-10-18
- Statut : terminé (workflow écrit et validé syntaxiquement ; exécution réelle sur GitHub non vérifiée)

## Objectif
Adapter `.github/workflows/ci.yml` (PHP 8.2, push/PR vers `main`) à PostgreSQL 18, en cohérence avec
`docker-compose.yml`, `.env`/`.env.test` (rapport 009) et la baseline unique avec `unaccent` (010).

## Ce qui a été fait
Seul fichier modifié : `.github/workflows/ci.yml`.
- Service `mariadb` remplacé par `postgres:18` (port 5432, healthcheck `pg_isready`),
  `POSTGRES_INITDB_ARGS="--encoding=UTF8 --locale-provider=icu --icu-locale=fr"` (identique au compose).
- Extension PHP `pdo_mysql` -> `pdo_pgsql`.
- `env` du job : `DB_USER=mangatrackr`, `DB_NAME=sf_mangatrackr_app`, `DB_HOST=127.0.0.1`, `DB_PORT=5432`,
  `DB_PASSWORD=${{ secrets.DB_PASSWORD || 'ChangeMe' }}` ; `DATABASE_URL` vient donc de `.env.test`
  (composée à partir de ces variables, `serverVersion=18`) ; le suffixe `_test` est ajouté par `doctrine.yaml`.
  `secrets.DB_ROOT_PASSWORD` n'est plus utilisé (le secret peut être supprimé côté GitHub).
- Nouvelle étape « Create PostgreSQL role and test database » (psql, superutilisateur du service) :
  rôle `mangatrackr` `LOGIN CREATEDB` non superutilisateur (comme en local, scripts `docker/postgres/init`)
  et base `sf_mangatrackr_app_test` en ICU `fr`, propriétaire le rôle. `unaccent` n'est pas créée ici :
  c'est la migration baseline.
- Ordre conservé : migrations (`--env=test`, baseline unique) -> fixtures -> PHPUnit. PHPStan inchangé.
- cs-fixer : `fix src` (qui réécrivait sans jamais échouer) remplacé par `fix src tests --dry-run --diff`
  (échoue si non conforme, couvre `tests` comme `make qa-php-cs-fixer`).
- Le fichier entier était indenté de 2 espaces (YAML accepté par GitHub mais rejeté par le parseur
  strict de Symfony) : dédenté, ce qui gonfle le diff sans changement de sens.

## Résultats
| Vérification | Résultat |
|---|---|
| Parse YAML (Symfony Yaml), 17 steps, `env` et script psql extraits | OK |
| `make qa-php-cs-fixer-dry-run-verbose` | 0 fichier à corriger / 180 (le nouveau dry-run passerait) |
| `make qa-php-stan` | OK, aucune erreur |
| `make test-only` (PG) | OK, 193 tests, 546 assertions |

## Écarts, risques, points non vérifiés
- **La CI n'a pas pu tourner** (ni GitHub Actions, ni `act`, ni Docker). Non testés : image `postgres:18`
  et healthcheck dans `services`, `psql` présent sur `ubuntu-latest` (normalement oui), heredoc `<<'SQL'`
  dans le bloc `run` (terminateur en colonne 0 après dédentation YAML), `\gexec`, création du rôle (la
  syntaxe est celle des scripts locaux, eux vérifiés par 007).
- Le script SQL n'est pas idempotent (`CREATE ROLE` sans garde) : acceptable, le service est neuf à chaque run.
- `secrets.DB_PASSWORD` non défini (ex. PR depuis un fork) : repli sur `ChangeMe`, base éphémère.
  Un mot de passe avec caractères spéciaux (`@`, `:`, `/`) casserait l'URL `DATABASE_URL` non encodée.
- `npm`/`node` 20 et `setup-node` inchangés ; ADR 0009/0010 (npm 10.8.2) non revus ici.
- Le passage cs-fixer en `--dry-run` peut faire échouer la CI si du code non conforme existe sur `main`
  (conforme sur `dev` à ce jour).
- L'étape `cp .env.test .env.test.local` est conservée mais redondante.

## Recommandation pour la suite
Ouvrir la PR vers `main` (brouillon) pour obtenir le premier run réel ; si le rôle/psql pose problème, repli :
`POSTGRES_USER=mangatrackr` + `POSTGRES_DB` sur le service (mais superutilisateur, moins fidèle).
Supprimer le secret `DB_ROOT_PASSWORD` à la bascule.
