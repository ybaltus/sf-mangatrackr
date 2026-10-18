# 035 — migration-schema — Retrait de MariaDB (infra, config, docs vivantes)

- Tâche : T8
- Date : 2026-10-18
- Statut : terminé

## Objectif
Retirer MariaDB de l'infra, de la config, des commentaires de code et des docs vivantes, et ne
garder que PostgreSQL 18. Outils de migration MariaDB conservés comme archive, historique non
modifié (plan `delegated-stirring-lamport.md`, section « Modifications »).

## Ce qui a été fait
**Infra**
- `docker-compose.yml` : suppression du service `database`, du `depends_on: database` et du
  volume `db-data` ; commentaires du service `postgres` reformulés (« seule base de
  l'application », « mêmes variables DB_* que .env »).
- `.devcontainer/docker-compose.devcontainer.yml` : suppression de `DATABASE_URL_MARIADB` et du
  bloc `database:` (port 3306).
- `docker/Dockerfile` et `.devcontainer/Dockerfile.dev` : `pdo_mysql` retiré.
- `git rm` : `docker/mariadb/init/01-create-test-database.sh`, `compose.yaml.save` et
  `compose.override.yaml.save`. Les deux `.save` sont des restes de la recette Flex (commit
  c504a8c, service `database` en postgres:15-alpine, mailcatcher) : Compose ne charge pas
  l'extension `.save`, et aucun fichier ne les référence (`devcontainer.json` ne charge que
  `docker-compose.yml` et l'override) ; seule la dette technique les citait comme « fichiers
  parasites ». Les suppressions sont **indexées** par `git rm` (pas de commit).

**Config** : `.env` (exemples `mysql://`, `MARIADB_VERSION`, commentaire « services postgres et
database »), `.env.test` (`MARIADB_VERSION`, même commentaire), `docker/vhosts/mangatracker.conf`
(exemple en `postgresql://…:5432/…?serverVersion=18&charset=utf8`),
`config/packages/security.yaml` (commentaire).

**Commentaires de code** (aucune logique modifiée, langue conservée) :
`src/Repository/MangaRepository.php` (NULLS LAST : « PostgreSQL sorts NULL first in DESC » ;
départage déterministe), `src/Repository/UserRepository.php`,
`src/Services/Common/EmailNormalizer.php`, `tests/Repository/UserRepositoryTest.php` (PostgreSQL
compare les chaînes en tenant compte de la casse), `docker/postgres/init/01-create-test-database.sh`
(ligne « Équivalent de docker/mariadb/… » supprimée).

**Docs vivantes** : `README.md` (badge PostgreSQL, prérequis et stack PostgreSQL 18, port 5432),
`CLAUDE.md` l. 11 et 45, `docs/claude/contexte.md` (stack : production cible homelab, Hostinger
abandonné ; environnement ; mention d'archive des outils), `docs/claude/etat-et-dette-technique.md`
(l. 27 réécrite + puce T8, l. 47 sans le script MariaDB, ligne « Restes MariaDB » soldée
(supprimée), « Fichiers parasites » sans les `.save` de compose, piste 1 réécrite).

**Outils conservés** : bandeau « **Archive** : nécessite un serveur MariaDB externe et
`pdo_mysql`, retirés de l'infra en T8 » ajouté juste après le front matter de
`.claude/skills/import-dump-mariadb/SKILL.md`, `.claude/skills/verif-donnees/SKILL.md`,
`.claude/agents/migration-importeur.md`, `.claude/agents/migration-auditeur.md`. Scripts et
`.claude/settings.json` inchangés.

## Résultats
| Vérification | Résultat |
|---|---|
| `docker compose config -q` | OK (services : `postgres`, `app`) |
| `docker compose -f docker-compose.yml -f .devcontainer/docker-compose.devcontainer.yml config -q` | OK, aucun service `database` |
| `make qa-php-cs-fixer` | 0 fichier corrigé sur 183 |
| `make qa-php-stan` | `[OK] No errors` |
| `make test-only` | `OK (226 tests, 580 assertions)` |
| `doctrine:schema:validate` (dev, PostgreSQL) | mapping correct, schéma synchronisé |
| `doctrine:migrations:migrate --dry-run` | déjà à `Version20261018145659` |

Environnement d'exécution : à mon arrivée, **tous les conteneurs étaient arrêtés** (`mangatrackr_db`
et `mangatrackr_pg` « Exited » ; plus de conteneur `mangatrackr_app`), contrairement au brief.
Le PHP de l'hôte (8.4, sans `pdo_pgsql`) ne convient pas. J'ai donc lancé `docker start mangatrackr_pg`
(conteneur existant, ni recréé ni reconstruit), puis la QA dans un conteneur jetable :
`docker run --rm -u 1000:1000 --entrypoint '' --network sf-mangatrackr_mangatrackr_network
-v <dépôt>:/var/www -w /var/www -e HOME=/tmp -e DB_HOST=postgres -e DB_PORT=5432
sf-mangatrackr-app:latest make …` (image existante, PHP 8.2 ; aucun fichier root créé). J'ai
ensuite arrêté `mangatrackr_pg` (`docker stop`) pour rendre l'état trouvé. `mangatrackr_db` n'a pas été touché.

### Occurrences restantes de `git grep -i -E 'mariadb|mysql|db-data|DATABASE_URL_MARIADB'`
| Fichiers | Justification |
|---|---|
| `docs/decisions/0011` à `0013`, `docs/chantiers/migration-postgresql/rapports/*`, anciennes lignes de `journal.md`, `plan-bascule.md`, `migrations/Version20261018145659.php` | historique (interdit de modification) |
| `.claude/skills/import-dump-mariadb/**`, `.claude/skills/verif-donnees/**`, `.claude/agents/migration-importeur.md`, `migration-auditeur.md`, `migration-donnees.md` | outils conservés (bandeaux compris) |
| `.claude/settings.json` | permission de `import-dump.php` conservée (consigne) |
| `.claude/skills/chantier/SKILL.md` l. 59 | description de l'agent `migration-auditeur` (outil conservé) |
| `composer.lock` | mots-clés du paquet doctrine/dbal (interdit) |
| `CLAUDE.md` l. 11, 45, 58 ; `docs/claude/contexte.md` l. 43, 52-54 ; `etat-et-dette-technique.md` l. 20, 23, 29-31 | mentions du retrait, des outils archivés et faits passés (migrations archivées, parité prouvée) |
| `etat-et-dette-technique.md` l. 76 « Accès source » | dette de l'outil de transfert conservé (utile s'il est réutilisé) |
| `docs/chantiers/README.md`, `docs/chantiers/migration-postgresql/README.md`, `etat.md` | documents du chantier (titre, cadrage d'origine ; `etat.md` réservé au chef) |
| `.claude/agents/migration-schema.md` l. 25 | règle « MariaDB doit rester utilisable… » : **devenue obsolète**, non modifiée (hors périmètre, à décider par le chef) |

`homelab.md` : aucune mention de MariaDB, rien à corriger.

## Écarts, risques, points non vérifiés
- Le devcontainer **n'a pas été reconstruit** : l'image sans `pdo_mysql` n'est pas testée (la QA a
  tourné sur l'ancienne image, qui contient encore `pdo_mysql` ; l'application ne l'utilise pas).
- Les outils archivés (`import-dump.php`, `transfert-mariadb-pgsql.php`, `rejouer-transfert.sh`)
  ne fonctionnent plus dans l'infra du dépôt : leurs défauts (`mysql:host=database;port=3306`)
  pointent vers un service supprimé. L'inventaire PostgreSQL seul de `verif-donnees` reste
  utilisable ; le bandeau est donc un peu plus large que nécessaire pour ce skill.
  La phrase « `pdo_mysql` présent » de `verif-donnees/SKILL.md` est désormais fausse (laissée,
  corrigée de fait par le bandeau).
- `docs/chantiers/migration-postgresql/README.md` l. 6 (« Hostinger reste en MariaDB ») et
  l. 26 contredisent la décision « Hostinger abandonné » : document de cadrage du chantier, non
  modifié.
- Le secret GitHub `DB_ROOT_PASSWORD` (dette l. 61) reste à supprimer par l'utilisateur.
- Les suppressions `git rm` sont dans l'index : `git restore --staged --worktree <fichier>` pour revenir.

## Recommandation pour la suite
Actions de l'utilisateur, depuis l'hôte, à la racine du dépôt :
1. Rebuild du devcontainer (PhpStorm/Gateway : « Rebuild container »), ou
   `docker compose -f docker-compose.yml -f .devcontainer/docker-compose.devcontainer.yml build app`.
2. `docker compose -f docker-compose.yml -f .devcontainer/docker-compose.devcontainer.yml down --remove-orphans`
   (supprime le conteneur orphelin `mangatrackr_db`, garde les volumes), puis relancer le devcontainer.
3. Optionnel et **irréversible** (la base `sf_mangatrackr_app_import` disparaît) :
   `docker volume rm sf-mangatrackr_db-data` ; éventuellement `docker image rm mariadb:10.11.5`.
4. Vérifier dans le nouveau conteneur : `php -m | grep -i pdo` (pas de `pdo_mysql`), `make test-only`.

Pour le chef : décider du sort de la règle l. 25 de `.claude/agents/migration-schema.md` et des
l. 6/26 du README du chantier ; relecture (036) puis validation (037).
