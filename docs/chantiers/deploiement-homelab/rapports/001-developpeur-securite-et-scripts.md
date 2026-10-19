# 001 — developpeur — sécurité des env (fin de T1) et fichiers prod + scripts (T2)

- Tâche : T1 (fin), T2
- Date : 2026-10-18
- Statut : terminé

## Objectif
T1 : chercher des secrets dans l'historique git, créer le modèle `deploy/mangatrackr.prod.env.dist`,
marquer `docker/Dockerfile` et `docker/docker.sh` « dev/démo uniquement ».
T2 : versionner les fichiers de prod (Dockerfile, sa `.dockerignore`, `compose.prod.yaml`) et écrire
les scripts idempotents `deploy/*.sh`, avec les cibles Makefile `prod-*`.

## Ce qui a été fait

### T1 — recherche de secrets dans l'historique (423 commits, `--all`)
Aucune valeur affichée : seulement des hashes, des noms de fichiers et des tests booléens
(`grep -aqF` sur des valeurs lues dans des variables).
- `git log --all -S GOCSPX` : aucun commit.
- Fichiers ayant existé dans l'historique (`git log --all --name-only`) : aucun `.env.local`,
  `*.prod.env`, `*.dump`, `*.sql`, `*.zip`, clé ou certificat. Seuls `.env` et `.env.test`.
- URL avec identifiants (`postgresql://`, `mysql://`, `amqp://`... ; mots de passe masqués, seule
  la longueur est affichée) : uniquement des exemples (`!ChangeMe!`, `ChangeMe`, `db_pass`,
  `${DB_PASSWORD}`, `<mot_de_passe>`, `guest` de l'exemple AMQP de Symfony) et un mot de passe
  root MariaDB de devcontainer (3 caractères) dans `.claude/skills/import-dump-mariadb/SKILL.md`
  (commit `1f93077`) : valeur de dev local, pas de prod.
- Valeurs réelles de `ressources/mangatrackr.prod.env` comparées à l'historique complet et aux
  fichiers suivis : `APP_SECRET`, `DATABASE_URL`, mot de passe de la base (forme brute et forme
  décodée), `OAUTH_GOOGLE_CLIENT_ID`, `OAUTH_GOOGLE_CLIENT_SECRET`, `MAILER_CONTACT`,
  `MAILER_NO_REPLY`, `OAUTH_TEST_EMAILS` : **absentes**. Trouvées, comme attendu : des valeurs
  publiques (`MAILER_DSN=null://null`, transport Doctrine, `APP_NAME`, URL des API).
- Note de méthode : il faut `grep -a`. Sans cette option, le premier passage sur l'arbre suivi
  donnait de faux négatifs (fichiers binaires) ; il a été refait.
- **Conclusion : aucune clé à renouveler.** Non couvert : objets git inaccessibles (non poussés),
  et forks ou clones tiers.

### T1 — fichiers
- `deploy/mangatrackr.prod.env.dist` (nouveau) : les 18 clés de l'env de prod, des placeholders
  `<...>` et des commentaires : hôte `postgres` sur le réseau `database`, `serverVersion=18`,
  percent-encoding puis `%` doublé dans `DATABASE_URL`, `MAILER_DSN=null://null`, `APP_SECRET`
  avec `openssl rand -hex 32`, `$` entre apostrophes (interpolation Compose), OAuth reporté.
- `docker/Dockerfile`, `docker/docker.sh` : en-tête de 4 lignes « DEV / DÉMO UNIQUEMENT ».
  Il nomme le danger (`first-install`, `app:init-datas`, `demo@demo.fr`) et renvoie vers les
  fichiers de prod. Le code n'est pas modifié.

### T2 — fichiers de prod
- `docker/Dockerfile.prod` : copie à l'identique de `ressources/Dockerfile.prod` (vérifié par
  `diff`), plus un en-tête de commentaires.
- `docker/Dockerfile.prod.dockerignore` : copie, plus un en-tête et 3 exclusions : `/ressources/`,
  `**/*.dump` et `**/*.prod.env`. Sur un poste qui aurait `ressources/`, les secrets et le dump
  entreraient sinon dans le contexte de construction, et donc dans l'image via `COPY . .`.
- `compose.prod.yaml` (racine) : `env_file` `${MANGATRACKR_ENV_FILE:-/opt/apps/secrets/mangatrackr.prod.env}`,
  port `${MANGATRACKR_BIND:-192.168.1.29:8080}:80`, healthcheck `app`
  (`curl -fsS -o /dev/null http://127.0.0.1/`, 30 s / 5 s / 3 essais / start 30 s),
  `pull_policy: never` sur `worker` (image locale construite par `app`), en-tête de commentaires.
- `Makefile` : section « Production », cibles `prod-deploy`, `prod-check`, `prod-backup`
  (`bash deploy/<script>.sh $(ARGS)`) avec `##`.

### T2 — scripts `deploy/` (bash, `set -euo pipefail`, `main "$@"`, exécutables, `--help` / `--dry-run`)
- `lib.sh` (sourcé) : constantes surchargeables (APPS_ROOT, PROJECT_DIR, SECRETS_DIR, ENV_FILE,
  BACKUP_DIR, PG_CONTAINER, DB_NAME, DB_ROLE, COMPOSE_FILE, MANGATRACKR_BIND, APP_URL,
  APP_CONTAINER, WORKER_CONTAINER, IMAGE, IMAGE_PREVIOUS, REPO_URL, NETWORKS, PG_SUPERUSER).
  Il contient aussi `log`, `ok`, `warn`, `die`, `usage` (en-tête du script), `run`
  (affichage seul en dry-run) et `compose` (toujours `-f`, dans PROJECT_DIR, refuse `down`).
  Contrôles : `require_docker`, `require_pg_running`, `container_state`. Lecture des secrets :
  `env_get`, `env_placeholders`, `parse_database_url` (`%%` puis `%XX` décodés en bash pur, sans
  PHP sur l'hôte) et `urldecode`. PostgreSQL : `pg_psql` / `pg_scalar` (psql du conteneur,
  superutilisateur, socket local).
- `install-vm.sh`, `init-database.sh`, `backup.sh`, `deploy.sh`, `check.sh` : conformes à la
  spécification. Les écarts et compléments sont listés ci-dessous.

### Décisions prises (à valider par le chef / relecteur)
1. **Mot de passe du rôle** : il est transmis à psql par l'entrée standard, dans un littéral SQL
   aux apostrophes doublées. Il n'apparaît ni en argument, ni en variable `-e` de `docker exec`.
   Avant `CREATE ROLE`, la session fait `SET log_statement='none'`,
   `log_min_duration_statement=-1` et `log_min_error_statement='panic'` : rien dans les journaux
   du serveur, même en cas d'erreur. Le dry-run affiche `PASSWORD '***'`.
2. **`set -x` refusé** par `lib.sh` (sortie 2) : un `bash -x` afficherait les secrets.
3. **Sauvegarde** : elle se fait en superutilisateur du conteneur par le socket local (aucun mot
   de passe à manipuler, lecture de tous les objets), avec `--no-owner --no-privileges`. Écriture
   dans un `.part`, contrôle `pg_restore --list`, puis `chmod 600` et `mv`. Rotation `KEEP=7`
   sur `<base>_*.dump`.
4. **Restauration** : `docker cp` vers `/tmp/mangatrackr-restore-<pid>.dump` dans le conteneur
   (supprimé par `trap EXIT`), contrôle `pg_restore --list`, puis `pg_restore --role=<rôle>
   --no-owner --no-privileges --exit-on-error --single-transaction`. Les objets appartiennent au
   rôle applicatif, comme lors de l'import fait par `bin/pg-import.sh`. **Pas d'option de
   remplacement** d'une base non vide (contrairement à `IMPORT_REPLACE=1` de `bin/pg-import.sh`) :
   c'est voulu pour la prod.
5. **Image `prod-previous`** : c'est l'image **du conteneur app en service** (`docker inspect
   .Image`) qui est retaguée, et non le tag `mangatrackr:prod`. Le tag peut déjà désigner une
   construction non déployée après un arrêt.
6. **Arrêt avant `up`** (migration en attente sans `--migrate`, échec du build, de la sauvegarde
   ou de la migration) : `mangatrackr:prod` est remis sur l'image en service. Un `compose up`
   ultérieur ne déploie donc pas par surprise du code non migré.
7. **`--all-or-nothing`** ajouté à `doctrine:migrations:migrate` (option disponible dans
   doctrine/migrations 3.9.8). Sous PostgreSQL, le DDL est transactionnel : un échec laisse le
   schéma inchangé. Avant la migration, `dbal:run-sql 'SELECT 1'` sépare l'erreur « base
   injoignable » d'une vraie migration en attente : `migrations:up-to-date` renvoie 1 dans les
   deux cas.
8. **`compose up -d --no-build --remove-orphans --wait --wait-timeout ${WAIT_TIMEOUT:-180}`** :
   `--no-build` empêche une reconstruction pendant un rollback, `--wait` attend le healthcheck.
9. **Verrou `flock`** sur `.git/mangatrackr-deploy.lock` contre deux déploiements simultanés.
   L'arbre doit être propre, fichiers non suivis compris.
10. **Dry-run** : les contrôles en lecture seule sont exécutés (préflight, existence du rôle et de
    la base, comptage des tables). `git fetch`, checkout, tag, build, `compose run`, migrations,
    `up` et `check.sh` sont seulement affichés. `check.sh --dry-run` liste les contrôles.
11. **`check.sh`** : un conteneur app `running/sans-healthcheck` (créé avant ce
    `compose.prod.yaml`) compte comme OK, avec un avertissement. `running/starting` compte comme
    KO. Le test HTTP se fait depuis le conteneur si `curl` manque sur l'hôte. Les secrets dans les
    journaux sont cherchés de deux façons : motifs génériques (`GOCSPX`, URL avec identifiants,
    `*_SECRET=` / `PASSWORD=`) et valeurs réelles d'`APP_SECRET`, du secret OAuth et du mot de
    passe de la base, passées à `grep -F -f <(...)`. Seuls deux compteurs sont affichés.
12. **`compose config` toujours avec `--quiet`** : sans cette option, Compose affiche les valeurs
    de l'`env_file` (vérifié avec le modèle `.dist`).
13. Pas de `umask 077` global dans `install-vm.sh` : le clone doit rester lisible, car `COPY`
    conserve les droits dans l'image et Apache tourne en `www-data`. Les droits 700/600 sont posés
    explicitement.

## Résultats
| Contrôle | Résultat |
|---|---|
| `bash -n` sur les 6 scripts | OK |
| shellcheck 0.10.0 (`-x`, binaire du scratchpad) | 0 remarque. Désactivations commentées : SC2034 dans `lib.sh` (variables pour les appelants), SC2317 dans `check.sh` (fonctions appelées par `check`), SC2016 (variables développées dans le conteneur) |
| `--help` des 5 scripts | OK (en-tête affiché) |
| Compose v2.29.7 autonome (`docker-compose config`, sans démon) | Interpolation acceptée dans `env_file` : défaut `/opt/apps/...` (erreur « not found » attendue ici), surcharge `MANGATRACKR_ENV_FILE` prise en compte ; port par défaut `192.168.1.29:8080`, surcharge `127.0.0.1:18080` OK ; guillemets du `.dist` retirés et `%%` conservé |
| `.dockerignore` racine / `docker/Dockerfile.prod.dockerignore` | Pas de conflit : BuildKit (défaut de Compose v2) prend `<Dockerfile>.dockerignore` en priorité, avec des chemins relatifs à la racine du contexte. Le fichier racine reste utilisé par le Dockerfile de dev |
| Fonctions de `lib.sh` sur des valeurs factices | Mot de passe `p%%40ss%%27w%%2Fo%%25rd` décodé en `p@ss'w/o%rd` ; `sql_literal` double les apostrophes ; 9 placeholders détectés dans le `.dist`, et `parse_database_url` le refuse (sortie 2) ; `bash -x` refusé (sortie 2) |
| Essai rapide avec un faux `docker` (clone de test, `APPS_ROOT` dans le scratchpad, supprimé ensuite) | `deploy.sh --dry-run --migrate` déroule toutes les étapes ; `--rollback --dry-run` OK ; arbre sale → refus ; `--ref` inconnue → refus ; `install-vm.sh --dry-run` et `init-database.sh --dry-run` → « déjà en place » ; `backup.sh --dry-run` OK. **Aucun appel modifiant** dans le journal du faux docker (ni `tag`, `build`, `up`, `run`, `down`, `network create`, `cp`) |
| Valeurs de prod dans les fichiers créés (test booléen) | absentes |
| `make qa-php-stan` | `[OK] No errors` |
| `make test-only` | `OK (226 tests, 580 assertions)` (référence identique) |

## Écarts, risques, points non vérifiés
- **Rien n'a été exécuté contre un vrai Docker, PostgreSQL ou la VM** (pas de Docker dans le
  devcontainer). Points à vérifier par le testeur (T4) et par l'utilisateur (T6) :
  - `docker compose run --rm --no-deps -T app ...` en présence de `container_name` : Compose v2
    donne un nom généré aux conteneurs ponctuels. À confirmer qu'il n'y a pas de conflit avec
    `mangatrackr_app_prod`.
  - L'authentification locale sans mot de passe du superutilisateur dans le conteneur `postgres`
    (`trust` sur le socket, défaut de l'image officielle) et la valeur de `POSTGRES_USER`. Sinon :
    `PG_SUPERUSER`.
  - `pg_restore --list` lu sur l'entrée standard (`docker exec -i`), dans `backup.sh`.
  - `--wait` avec un worker sans healthcheck (attendu : « running » suffit).
  - Le nom du projet Compose : les conteneurs actuels doivent venir de
    `/opt/apps/projects/sf-mangatrackr` (projet `sf-mangatrackr`). Sinon, `up` entre en conflit
    sur `container_name`.
- **Premier déploiement sur la VM** : les fichiers `compose.prod.yaml` et `docker/Dockerfile.prod*`
  y ont été posés à la main, hors git. `deploy.sh` refusera donc l'arbre (fichiers `??`), et
  `git pull` refuserait de les écraser. Il faut les déplacer (par exemple dans
  `/opt/apps/backups/`) avant le premier `deploy.sh`. C'est à écrire dans la doc (T3).
- Le premier `check.sh` sur la VM signalera « sans healthcheck » tant que les conteneurs n'auront
  pas été recréés avec le nouveau `compose.prod.yaml`.
- Le retour arrière ne concerne que l'image : ni le schéma ni le code git ne reviennent en
  arrière. C'est signalé à l'exécution.
- Pendant `--migrate`, l'ancien code tourne quelques secondes sur le nouveau schéma, entre la
  migration et `up`. C'est acceptable pour des migrations additives, à documenter.
- Binaires shellcheck et docker-compose téléchargés depuis GitHub sans vérification de somme de
  contrôle. Ils servent seulement à l'analyse et à `config`, et restent dans le scratchpad
  (`/tmp/claude-0/-var-www/f578a951-56ed-48f3-8d75-06a131c21ff4/scratchpad/bin/`) pour le testeur.

## Recommandation pour la suite
- T3 (doc) : ordre de réinstallation `install-vm.sh` → remplir les secrets → `init-database.sh
  [--restore]` → `deploy.sh` → `check.sh`. Ajouter : le déplacement des fichiers posés à la main
  avant le premier déploiement ; les variables de `lib.sh` ; les codes de sortie ; « ne jamais
  lancer `compose config` sans `--quiet` » ; les limites du rollback.
- T4 : faux `docker` plus fidèle (réseaux de `postgres`, `datdba`, sortie de `up-to-date` avec
  code 1) pour couvrir : migration en attente sans `--migrate` (restauration du tag), échec de
  migration, échec du build, base non vide en `--restore`, `CONFIRM_TARGET` absent, rotation avec
  plus de 7 fichiers, idempotence d'`install-vm.sh` en réel (sous `APPS_ROOT` du scratchpad).
- Hors périmètre, pour la dette : healthcheck du worker ; vérification d'une restauration réelle
  de sauvegarde (procédure de remplacement d'une base non vide en prod, volontairement absente) ;
  chiffrement des sauvegardes et copie hors VM.
