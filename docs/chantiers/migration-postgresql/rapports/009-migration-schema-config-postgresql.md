# 009 — migration-schema — Configuration applicative PostgreSQL 18

- Tâche : T4.3
- Date : 2026-10-18
- Statut : terminé (fichiers modifiés et vérifiés sur PG ; le conteneur `app` doit être recréé depuis l'hôte pour que la bascule prenne effet)

## Objectif
Basculer la configuration de l'application vers PostgreSQL 18 (ADR 0012, D2, D3, D8) :
`DATABASE_URL`, nom unique pour le mot de passe, `doctrine.yaml`, table `app_user`. Sans
baseline ni archivage des migrations (T4.4).

## Ce qui a été fait
Fichiers modifiés :
- `src/Entity/User.php` : `#[ORM\Table(name: 'app_user')]` (D2).
- `config/packages/doctrine.yaml` : `dbal.disable_type_comments: true` (D8) ;
  `orm.identity_generation_preferences: { Doctrine\DBAL\Platforms\PostgreSQLPlatform: identity }` (D3) ;
  `dbname_suffix: '_test…'` conservé (bloc `when@test` inchangé).
- `.env` et `.env.test` : `DB_USER_PASSWORD` → **`DB_PASSWORD`** (nom unique, déjà utilisé par
  `docker-compose.yml` et le secret CI), `DB_PORT=5432`,
  `DATABASE_URL="postgresql://${DB_USER}:${DB_PASSWORD}@${DB_HOST}:${DB_PORT}/${DB_NAME}?serverVersion=18&charset=utf8"`.
  `MARIADB_VERSION=10.11.5` **conservé** (commenté) : `docker compose` interpole le `.env` racine
  et s'en sert pour le tag de l'image `database` (vérifié : le serveur répond `10.11.5-MariaDB`) ;
  le retirer ferait passer l'image à `mariadb:lts` sur le volume existant.
- `.devcontainer/docker-compose.devcontainer.yml` (service `app`) : `DB_HOST: postgres`,
  `DB_PORT: 5432`, `DATABASE_URL` PG (`postgres:5432`, `serverVersion=18`, `charset=utf8`) ;
  ajout de `DATABASE_URL_MARIADB` (ancienne URL vers `database:3306`, `serverVersion` corrigé de
  `mariadb-11.4.3` en `${MARIADB_VERSION:-10.11.5}-MariaDB`), non lue par l'application, pour T5.
- `docker-compose.yml` (service `app`, image de prod) : `DB_HOST: postgres`, `DB_PORT: 5432`.
  Services `database` et `postgres` inchangés.

Recherche des références à la table `user` (`src`, `tests`, `config`, `templates`, skills) :
aucun SQL natif, aucun `getConnection()`, aucune jointure explicite ni fixture citant la table ;
les 3 `ManyToMany` concernent `Manga` seulement. Seul le mapping change.
Les `DataFixtures` passent par l'ORM. Les 15 migrations citent `user` mais restent intactes (T4.4).

Commandes de vérification (dans `app`, en surchargeant l'environnement pour simuler le conteneur
recréé, car la variable réelle `DATABASE_URL` du conteneur actuel pointe encore sur MariaDB) :
`dbal:run-sql "SELECT version()…"` (dev/test, via `.env` et via l'URL du devcontainer),
`doctrine:schema:validate --skip-sync` (dev/test), `doctrine:schema:create --dump-sql`,
`doctrine:migrations:migrate --dry-run` (test), `make qa-php-cs-fixer`, `make qa-php-stan`,
`make test-only` (sur PG).

## Résultats
| Vérification | Résultat |
|---|---|
| `SELECT version()` dev | `PostgreSQL 18.6 (Debian 18.6-1.pgdg13+2)`, base `sf_mangatrackr_app`, rôle `mangatrackr` |
| `SELECT version()` `APP_ENV=test` | PG 18.6, base `sf_mangatrackr_app_test` (suffixe OK) |
| `schema:validate --skip-sync` dev et test | `[OK] The mapping files are correct.` |
| DDL `schema:create --dump-sql` | 22 `CREATE TABLE`, 19 `SERIAL`, 0 `CREATE SEQUENCE`, 0 `COMMENT ON`, 0 `"user"` ; `CREATE TABLE app_user`, `UNIQ_88BDF3E9E7927C74 ON app_user (email)`, 4 `REFERENCES app_user (id)` |
| Dépréciation `identity_generation_preferences` (orm#8893) | absente de `debug:container --deprecations` |
| `make qa-php-cs-fixer` | 0 fichier corrigé / 178 |
| `make qa-php-stan` | `[OK] No errors` |
| `make test-only` sur PG | **rouge, attendu** : 192 tests, 245 assertions, 64 erreurs, 32 échecs — `relation "manga"/"app_user"/… does not exist` (aucune table tant que T4.4 n'est pas faite) |
| `migrations:migrate --dry-run` (test, PG) | « OK, 15 migrations, 79 requêtes » **trompeur** : le dry-run n'exécute pas le SQL et aucune migration n'a d'`abortIf` de plateforme ; un vrai `migrate` échouerait sur la syntaxe MySQL (`ENGINE = InnoDB`…). Non lancé. |

Bases PG après les tests : dev 0 table ; test 1 table `doctrine_migration_versions` (0 ligne),
créée par le `--dry-run` (initialisation du stockage des métadonnées). Laissée en place : T4.4 la
réutilise ; aucune donnée résiduelle.

## Écarts, risques, points non vérifiés
- **Bascule effective seulement après recréation du conteneur `app`** : la variable réelle
  `DATABASE_URL` (override devcontainer) l'emporte sur `.env`/`.env.local` en dev **et en test**.
  Dans le conteneur actuel, `make test-only` tourne donc encore sur MariaDB, avec le mapping
  `app_user` qui n'y existe pas : rouge aussi. Depuis l'hôte (non testé par moi) :
  VS Code « Dev Containers: Rebuild Container » (ou « Reopen in Container »), ou
  `docker compose -f docker-compose.yml -f .devcontainer/docker-compose.devcontainer.yml up -d app`
  puis, dans `app` : `env | grep -E '^(DB_HOST|DATABASE_URL)=' | sed 's#:[^:@]*@#:***@#'` → `postgres`.
- **`.env.local` (non lu)** : s'il définit `DB_USER_PASSWORD`, la renommer en `DB_PASSWORD` ;
  s'il définit `DATABASE_URL` ou `DB_PORT` en MySQL, les passer en PG (sinon une exécution hors
  conteneur, sans la variable réelle, pointerait encore sur MariaDB). À faire par l'utilisateur.
- `docker compose` lit aussi `DB_PASSWORD="ChangeMe"` dans `.env` désormais (avant : défaut
  `ChangeMe` du compose) : valeur identique, aucun changement pour les volumes existants.
- **CI cassée jusqu'à T4.6** : `.env.test` pointe sur PG `127.0.0.1:5432` alors que
  `.github/workflows/ci.yml` démarre encore MariaDB (et passe `secrets.DB_PASSWORD`, désormais le
  bon nom).
- `make first-install` (postCreateCommand) lance `sf-dmm` : sur PG il échouera tant que la baseline
  (T4.4) n'existe pas, au prochain rebuild.
- Effet de bord du hook cs-fixer : il écrit `.php-cs-fixer.cache` dans le dossier courant ; le
  fichier `docs/chantiers/migration-postgresql/.php-cs-fixer.cache` est **suivi par git** depuis
  1f93077 (commit de sauvegarde). Restauré à l'identique ; à retirer de l'index et ignorer (chef).
- Avertissement : une commande `grep` lancée par erreur sur `.env.local` a été bloquée par la règle
  de refus ; le fichier n'a pas été lu.

## Recommandation pour la suite
- T4.4 (rapport 010) : baseline PG (avec `app_user`, `SERIAL`, index `LOWER(email)`, `unaccent`,
  les 2 index corrigés) + archivage des 15 migrations hors `migrations/`, puis `make test` et
  `make test-only` sur PG (après recréation du conteneur, ou en exportant `DATABASE_URL`).
- Ajouter dans la baseline un `abortIf` sur la plateforme PostgreSQL : le dry-run actuel montre que
  rien n'empêche de jouer des migrations sur le mauvais moteur.
- T4.6 : CI `postgres:18` en lisant `DB_PASSWORD`.
