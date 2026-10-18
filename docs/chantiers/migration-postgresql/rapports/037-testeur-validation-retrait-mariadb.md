# 037 — testeur — validation QA du retrait de MariaDB

- Tâche : T8
- Date : 2026-10-18
- Statut : terminé

## Objectif
Valider le diff non commité de T8 (retrait de MariaDB : service compose `database`, `pdo_mysql`,
`docker/mariadb/`, variables `.env`, commentaires et docs), y compris l'image du devcontainer
reconstruite, que le rapport 035 n'avait pas pu tester.

## Ce qui a été fait
- État trouvé : `mangatrackr_pg` et `mangatrackr_db` arrêtés (« Exited »), pas de `mangatrackr_app`.
- `docker compose config -q` / `--services` / `--volumes`, avec et sans
  `-f .devcontainer/docker-compose.devcontainer.yml`, puis recherche de
  `database|db-data|mysql|mariadb` dans la configuration résolue.
- `docker build -f .devcontainer/Dockerfile.dev -t mangatrackr-t8-check .` (1 min 51 s, rc 0).
- `php -m | grep -i pdo` sur la nouvelle image et, pour comparaison, sur `sf-mangatrackr-app:latest`.
- `docker start mangatrackr_pg` (`pg_isready` OK), puis QA dans un conteneur jetable sur la
  **nouvelle image** : `docker run --rm -u 1000:1000 --entrypoint '' --network
  sf-mangatrackr_mangatrackr_network -v <dépôt>:/var/www -w /var/www -e HOME=/tmp
  -e DB_HOST=postgres -e DB_PORT=5432 mangatrackr-t8-check <commande>`.
- Remise en état : `docker stop mangatrackr_pg`, `docker rmi mangatrackr-t8-check`.
  `mangatrackr_db` et les volumes non touchés ; pas de `compose down/up`.
- Fichiers créés : ce rapport et une ligne dans `journal.md`. Aucun fichier de code, de config
  ou de test modifié ; aucun fichier appartenant à root (vérifié par `find -user root`).

## Résultats
Image utilisée pour la QA : **`mangatrackr-t8-check`** (construite depuis le
`Dockerfile.dev` modifié, PHP 8.2.34), puis supprimée.

| Contrôle | Résultat | Sortie résumée |
|---|---|---|
| `docker compose config -q` | OK | rc 0 ; services `postgres`, `app` ; volume `pg-data` |
| idem avec `.devcontainer/docker-compose.devcontainer.yml` | OK | rc 0 ; services `postgres`, `app` ; volumes `pg-data`, `claude-config` |
| Pas de service `database` ni volume `db-data` | OK | seule correspondance : `DATABASE_URL` en `postgresql://…@postgres:5432` |
| Build `Dockerfile.dev` | OK | rc 0, 1 min 51 s |
| Extensions PDO (nouvelle image) | OK | `PDO`, `pdo_pgsql`, `pdo_sqlite` ; **`pdo_mysql` absent** (l'ancienne image l'avait) |
| `make qa-php-cs-fixer` | OK | `Fixed 0 of 183 files` |
| `make qa-php-stan` | OK | `[OK] No errors` |
| `make test-only` | OK | `OK (226 tests, 580 assertions)` = référence ; 0 skipped, 0 échec |
| `doctrine:schema:validate` (dev) | OK | mapping correct, base synchronisée |
| `doctrine:migrations:status` (test) | OK | courant = dernier = `Version20261018145659`, 1 exécutée, 0 nouvelle |

Les tests réseau (`ApiTenraiCommandTest`, `InitDataCommandTest`, `ApiMangaUpdatesCommandTest`)
sont passés. Dépréciations inchangées (dont `symfony/ux-toggle-password` 2.29, déjà connue).

## Écarts, risques, points non vérifiés
- `docker/Dockerfile` (image de production) **n'a pas été construit** : seul le
  `Dockerfile.dev` était demandé. Son diff est d'une ligne, analogue.
- Le devcontainer complet (VS Code, `postCreateCommand`, etc.) n'a pas été relancé ; seule
  l'image a été testée. Le rebuild réel reste à faire par l'utilisateur.
- Les couches de build restent dans le cache BuildKit de l'hôte (image supprimée) ;
  `docker builder prune` les libérerait si besoin.
- `mangatrackr_pg` est rendu arrêté, comme trouvé.

## Recommandation pour la suite
T8 validable côté QA. Reste le rebuild du devcontainer par l'utilisateur et, au besoin, un
build de `docker/Dockerfile` avant commit.
