# 007 — migration-schema — Infrastructure Docker PostgreSQL 18

- Tâche : T4.2
- Date : 2026-10-18
- Statut : terminé (fichiers écrits ; rien n'a pu être construit ni lancé, Docker absent du conteneur `app`)

## Objectif
Préparer l'infra Docker pour PostgreSQL 18 (ADR 0012) sans toucher à la configuration
applicative (`.env`, `.env.test`, `doctrine.yaml`, mapping, migrations : T4.3/T4.4), en gardant
le service MariaDB `database` (D6, coexistence).

## Ce qui a été fait
Fichiers modifiés :
- `docker-compose.yml` : service `postgres` ajouté (le service `database` est inchangé) ;
  `app.depends_on` += `postgres` ; volume nommé neuf `pg-data`.
- `.devcontainer/docker-compose.devcontainer.yml` : port `5432:5432` exposé pour `postgres`
  (comme `3306` pour MariaDB). `DATABASE_URL` du service `app` laissé en MySQL (T4.3).
- `docker/Dockerfile` : `pdo_pgsql` ajouté à `install-php-extensions`.
- `.devcontainer/Dockerfile.dev` : `pdo_pgsql` ajouté ; client `postgresql-client-18` via le
  dépôt PGDG (`trixie-pgdg`, clé `ACCC4CF8.asc` en `signed-by`).

Fichiers créés (mode 755, montés en lecture seule sur `/docker-entrypoint-initdb.d`) :
- `docker/postgres/init/00-create-app-role-and-database.sh` : rôle applicatif `LOGIN CREATEDB`
  (non superutilisateur) + base principale dont il est propriétaire.
- `docker/postgres/init/01-create-test-database.sh` : base `<base>_test`, propriété du rôle
  applicatif (équivalent du script MariaDB).

Détails du service `postgres` :
| Élément | Valeur |
|---|---|
| Image / conteneur | `postgres:${POSTGRES_VERSION:-18}` / `mangatrackr_pg` (style `mangatrackr_db`) |
| Volume | `pg-data:/var/lib/postgresql` (et non `/var/lib/postgresql/data`, piège de l'image 18) |
| Superutilisateur | `postgres`, mot de passe `${POSTGRES_SUPERUSER_PASSWORD:-root1234}` (valeur de dev, comme `root1234` MariaDB) |
| Rôle applicatif | `APP_DB_NAME/USER/PASSWORD` = `${DB_NAME:-sf_mangatrackr_app}`, `${DB_USER:-mangatrackr}`, `${DB_PASSWORD:-ChangeMe}` (mêmes variables que `database`) |
| initdb | `POSTGRES_INITDB_ARGS="--encoding=UTF8 --locale-provider=icu --icu-locale=fr"` (syntaxe initdb PG 15+, valable en 18) |
| Healthcheck | `pg_isready -h 127.0.0.1 -p 5432 -U postgres -d postgres`, 5 s, 10 essais, `start_period` 30 s |

Choix :
- **Rôle applicatif non superutilisateur** : l'image fait de `POSTGRES_USER` un superutilisateur ;
  le garder à `postgres` et créer `mangatrackr` à part reproduit le modèle MariaDB (root +
  `MYSQL_USER`) et prépare la prod homelab.
- **Bases créées explicitement** `TEMPLATE template0 ENCODING 'UTF8' LOCALE_PROVIDER icu
  ICU_LOCALE 'fr'` (ADR : création ICU explicite) ; en plus, `template1` est en ICU `fr` grâce à
  `POSTGRES_INITDB_ARGS`, donc une base recréée par `doctrine:database:create` (`make test`)
  hérite aussi de `fr`.
- **CREATEDB** donné au rôle : indispensable à `make test` (`doctrine:database:drop/create
  --env=test`). Commenté « à ne pas donner en production ».
- **unaccent** : non créée ici (décision : baseline T4.4). Vérifié sur la doc : extension
  *trusted* depuis PG 13, il faut le droit `CREATE` sur la base, que le propriétaire détient ; le
  schéma `public` appartient à `pg_database_owner` depuis PG 15. **Aucun droit supplémentaire
  nécessaire** pour le rôle propriétaire. À confirmer par la commande 6 ci-dessous.
- **Healthcheck en TCP** : le serveur temporaire de l'init n'écoute que sur le socket Unix, le
  service ne passe donc `healthy` qu'après les scripts d'init.
- **libpq-dev** : non ajouté à la main, `install-php-extensions pdo_pgsql` installe les
  dépendances de compilation puis les retire en gardant `libpq5`.
- **Client psql 18** : ajouté au seul Dockerfile du devcontainer (outil de dev ; en prod les
  `pg_dump` se font depuis le conteneur `postgres`).
- Scripts SQL : variables passées par `psql -v` et `format(%I, %L)` + `\gexec` (citation sûre,
  idempotents grâce à `WHERE NOT EXISTS`).

## Résultats (validations possibles sans Docker)
- YAML : `Symfony\Component\Yaml\Yaml::parseFile` OK sur les deux fichiers compose ; services
  `database, postgres, app` ; volumes `db-data, pg-data`.
- Scripts : `bash -n` et `sh -n` OK.
- Dockerfiles : relus (le `${VERSION_CODENAME}` du `RUN` est évalué par le shell, pas par Docker ;
  `curl` et `ca-certificates` sont installés avant le bloc PGDG). Base `php:8.2-apache` = trixie
  (vérifié dans le conteneur actuel), `trixie-pgdg` existe chez PGDG.
- Pas de PHP modifié : QA PHP non relancée.

## Commandes à lancer depuis l'hôte (non testées)
Les noms de conteneur sont fixes, `docker exec` évite les soucis de nom de projet compose
(le devcontainer utilise un projet `<dossier>_devcontainer`).

1. Reconstruire : VS Code « Dev Containers: Rebuild Container », ou depuis la racine du projet :
   `docker compose -f docker-compose.yml -f .devcontainer/docker-compose.devcontainer.yml build app`
   puis `docker compose -f docker-compose.yml -f .devcontainer/docker-compose.devcontainer.yml up -d`
   (image de prod seule : `docker compose build app`).
2. Santé : `docker inspect -f '{{.State.Health.Status}}' mangatrackr_pg` → `healthy`
   puis `docker exec mangatrackr_pg pg_isready -h 127.0.0.1 -U postgres` → `accepting connections`.
3. Logs d'init : `docker logs mangatrackr_pg 2>&1 | grep -E "prêt|ERROR"` → deux lignes « prêt(e) ».
4. Bases, propriétaire et collation :
   `docker exec mangatrackr_pg psql -U postgres -Atc "SELECT datname, pg_get_userbyid(datdba), datlocprovider, datlocale FROM pg_database WHERE datname IN ('template1','sf_mangatrackr_app','sf_mangatrackr_app_test')"`
   → attendu `…|postgres|i|fr` pour template1, `…|mangatrackr|i|fr` pour les deux bases.
5. Rôle : `docker exec mangatrackr_pg psql -U postgres -Atc "SELECT rolname, rolsuper, rolcreatedb FROM pg_roles WHERE rolname='mangatrackr'"` → `mangatrackr|f|t`.
6. unaccent par le rôle applicatif (annulé) :
   `docker exec mangatrackr_pg psql -U mangatrackr -d sf_mangatrackr_app_test -c "BEGIN; CREATE EXTENSION unaccent; SELECT unaccent('Évangélion'); ROLLBACK;"` → `Evangelion`.
7. Extension PHP et client : `docker exec mangatrackr_app sh -c "php -m | grep -i pdo_pgsql; psql --version"`
   → `pdo_pgsql` et `psql (PostgreSQL) 18.x`.
8. Connexion PDO app → postgres:5432 (remplacer `ChangeMe` si `DB_PASSWORD` est défini côté hôte) :
   `docker exec mangatrackr_app php -r '$p = new PDO("pgsql:host=postgres;port=5432;dbname=sf_mangatrackr_app_test", "mangatrackr", "ChangeMe"); echo $p->query("SHOW server_version")->fetchColumn(), PHP_EOL;'` → `18.x`.
9. MariaDB toujours là : `docker inspect -f '{{.State.Status}}' mangatrackr_db` → `running`, et
   `make test-only` dans `app` reste vert (config encore sur MariaDB).

## Écarts, risques, points non vérifiés
- **Rien n'a été construit ni démarré** : build des images, téléchargement PGDG, init, healthcheck,
  collation et `CREATE EXTENSION` sont non testés (commandes ci-dessus).
- Incohérence préexistante : `docker-compose.yml` utilise `DB_PASSWORD` alors que `.env` définit
  `DB_USER_PASSWORD` ; sans `DB_PASSWORD` dans l'environnement de l'hôte, le mot de passe des deux
  bases est `ChangeMe`. Gardé tel quel par cohérence ; à harmoniser en T4.3.
- Le port hôte `5432` peut être déjà pris par un PostgreSQL local de l'hôte : changer alors le
  mappage dans l'override devcontainer (ex. `"5433:5432"`).
- Scripts d'init exécutés seulement à la création du volume `pg-data` (neuf ici, donc OK au premier
  `up`). Ils sont idempotents et rejouables via `docker exec mangatrackr_pg sh /docker-entrypoint-initdb.d/<script>`.
- `doctrine:database:drop` échoue si une autre session est connectée à la base de test (normal en PG).
- `docs/claude/contexte.md` (section Environnement) non mis à jour : à faire à la bascule ou par le chef.

## Recommandation pour la suite
- Faire lancer par l'utilisateur les commandes 1 à 9 et reporter les sorties (surtout 4, 6, 8).
- T4.3 : `DATABASE_URL` PG (`postgresql://…@postgres:5432/…?serverVersion=18&charset=utf8`) dans
  `.env`/`.env.test` et l'override devcontainer ; harmoniser `DB_PASSWORD`/`DB_USER_PASSWORD`.
- T4.4 : `CREATE EXTENSION IF NOT EXISTS unaccent` dans la baseline (droits suffisants, cf. ci-dessus).
- T4.6 (CI) : reprendre les mêmes `POSTGRES_INITDB_ARGS` et la création explicite ICU `fr`.
