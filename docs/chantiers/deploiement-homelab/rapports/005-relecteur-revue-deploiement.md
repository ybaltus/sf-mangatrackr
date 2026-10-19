# 005 — relecteur — revue du déploiement scripté (T1 à T4.1)

- Tâche : T5
- Date : 2026-10-18
- Statut : terminé
- Verdict : **validé avec réserves** (0 bloquant, 3 majeurs, 10 mineurs)

## Objectif
Relire en lecture seule tout le diff non commité du chantier : `.gitignore`, `deploy/*`,
`compose.prod.yaml`, `docker/Dockerfile.prod` (+ `.dockerignore`), en-têtes de `docker/Dockerfile`
et `docker/docker.sh`, cibles Makefile `prod-*`, `docs/deploiement/apps-dev.md`, ADR 0015 et index,
CLAUDE.md, `contexte.md`, dette technique, docs des chantiers. Priorités : secrets, sûreté des
données, exactitude de l'image et des commandes, doc exécutable, conventions.

## Ce qui a été fait
- `git status`, `git diff` (12 fichiers modifiés), lecture intégrale des fichiers nouveaux, du plan
  `/root/.claude/plans/graceful-wondering-newt.md` et des rapports 001 à 004.
- `bash -n deploy/*.sh` : OK (shellcheck absent de l'image, non relancé ; propre selon 003/004).
- Comparaison des fichiers versionnés avec les originaux posés à la main
  (`ressources/compose.prod.yaml`, `ressources/Dockerfile.prod*`) : écarts limités à ceux annoncés
  en doc §6.
- Recherche de secrets : `git log --all -- ressources/ '*prod.env*'` vide, `git grep` (GOCSPX, URL
  avec mot de passe) sans résultat réel, `git check-ignore` : `ressources/*` ignoré par
  `.gitignore:56`. `ressources/mangatrackr.prod.env` **non lu**.
- Vérifications de code tiers : `UpToDateCommand` de doctrine/migrations 3.9.8 (codes de retour),
  option `--all-or-nothing` présente ; `monolog` prod sur `php://stderr` ; clés `%env()%` de
  `config/` comparées au `.dist` (toutes présentes ; `VAR_DUMPER_SERVER` est `when@dev`,
  `*_DSN` de traduction commentés).
- Aucun fichier PHP modifié : `make qa-php-stan` / `make test-only` non relancés (verts en 004).
- Fichiers écrits : ce rapport et une ligne dans `journal.md`. Aucune autre modification.

## Résultats

### Majeurs

**M1 — `prod-previous` écrasée par l'image en service quand l'image reconstruite est identique**
(`deploy/deploy.sh:364-368`, appel `:476`).
`promote_previous` retague toujours `SERVICE_ID` en `prod-previous`. Si le build produit la même
image (cache intégral : commit ne touchant que `docs/`, `tests/`, `.github/`, tous exclus du
contexte ; ou simple relance de `deploy.sh` après un `check.sh` KO transitoire), alors
`SERVICE_ID` = nouvelle image et `prod-previous` devient l'image courante : la vraie version
précédente est perdue et `--rollback` ne fait plus rien.
Scénario : déployer v2 (previous = v1), relancer `deploy.sh` sans changement de code → previous = v2 ;
v2 s'avère fautive → `--rollback` reste sur v2.
Correction : après `compose build`, comparer `docker image inspect -f '{{.Id}}' "$IMAGE"` à
`SERVICE_ID` ; si égal, ne pas toucher `prod-previous` (message « image inchangée, retour arrière
conservé »). Ajouter le cas au rejeu du testeur.

**M2 — `up --remove-orphans` sans garde sur le projet Compose du conteneur `postgres`**
(`deploy/deploy.sh:417`, contrôle `:299-308` ; doc `apps-dev.md:294-297`).
`check_compose_project` ne vérifie que les conteneurs app/worker. La doc §6 demande de reprendre
`COMPOSE_PROJECT_NAME=<valeur relevée>` sur les conteneurs actuels : si l'installation manuelle a
lancé MangaTrackr dans le même projet Compose que le PostgreSQL mutualisé (ou que d'autres
applications), `up --remove-orphans` supprimerait le conteneur `postgres` (et les autres) comme
orphelins — exactement l'invariant « jamais modifié » de l'ADR 0015. Le label réel n'est connu
qu'en T6.
Correction (préflight, lecture seule) : refuser si `com.docker.compose.project` de `$PG_CONTAINER`
vaut `$COMPOSE_PROJECT_NAME`, et plus généralement lister
`docker ps -a --filter label=com.docker.compose.project=$COMPOSE_PROJECT_NAME` et refuser tout
conteneur autre que app, worker et `*-run-*`. À défaut, retirer `--remove-orphans`.

**M3 — Le `.dockerignore` racine n'exclut pas `ressources/` : l'image de dev embarque les secrets de
prod et le dump** (`.dockerignore` racine, non modifié ; `docker/Dockerfile:31` `COPY . /var/www/` ;
`docker-compose.yml:39`).
Le service `app` du compose de dev (devcontainer) construit `docker/Dockerfile` avec un contexte qui
contient `ressources/mangatrackr.prod.env` (vrais secrets) et `ressources/*.dump` (données
personnelles) : ils sont figés dans une couche d'image locale à chaque reconstruction. Même
chose pour l'image de prod si BuildKit est désactivé (`DOCKER_BUILDKIT=0` : seul le
`.dockerignore` racine est lu, `Dockerfile.prod.dockerignore` ignoré). L'affirmation de la doc §10
(« `/ressources/` exclu du contexte de construction de l'image ») n'est vraie que pour la prod sous
BuildKit.
Correction : ajouter au `.dockerignore` racine `/ressources/`, `**/*.dump`, `**/*.sql`,
`**/*.prod.env`, `.env.local` (déjà présent) ; signaler à l'utilisateur de supprimer les images de
dev déjà construites (`docker image ls`, puis reconstruction).

### Mineurs

| # | Fichier:ligne | Constat / scénario | Correction |
|---|---|---|---|
| m1 | `deploy/check.sh:151`, `apps-dev.md:471` | `docker exec` sans `-u` : `bin/console` tourne en root dans le conteneur `app`, où Apache écrit en `www-data` ; des sous-répertoires de `var/cache/prod/pools` créés par root deviennent non inscriptibles pour Apache (écritures de cache perdues ; pas de fichier de log, monolog est sur stderr) | `docker exec -u www-data ...` dans `check.sh` et la doc |
| m2 | `deploy/deploy.sh:394-404` | Tout code ≠ 0 de `migrations:up-to-date` est lu comme « migration en attente » ; une erreur d'un autre type (exception, configuration) déclencherait avec `--migrate` une sauvegarde puis un `migrate` | Ne traiter que `rc=1` comme « en attente », sinon `die ... 3` avec la sortie |
| m3 | `apps-dev.md:300` | « `install-vm.sh --dry-run` # crée seulement /opt/apps/backups » : en dry-run rien n'est créé ; si le fichier de secrets actuel n'est pas en 600, `deploy.sh` s'arrêtera | Indiquer de lancer ensuite `install-vm.sh` sans `--dry-run` (corrige 700/600, n'écrase rien) |
| m4 | `apps-dev.md:150-162` | Réinstallation « VM vierge » non exécutable de bout en bout : le PostgreSQL mutualisé renvoie à une doc séparée qui n'existe pas (la doc locale homelab dit « à documenter séparément ») et qui vivrait sur la VM perdue | Ajouter la ligne en dette (`etat-et-dette-technique.md`) et, a minima, un exemple de référence (image `postgres:18`, réseau `database`, volume, healthcheck) en annexe |
| m5 | `apps-dev.md:243-244` | Base neuve : après `--migrate`, rien sur `app:init-datas` (« Initialize the datas for the production environment ») ni sur la création d'un compte admin : application vide/inutilisable | Ajouter `docker compose -f compose.prod.yaml run --rm --no-deps app php bin/console app:init-datas --env=prod` et la création d'un admin (sans fixtures ni `demo@demo.fr`) |
| m6 | `etat.md` (T6 avant T7), plan §T6 | T6 prévoit un `git pull` de `dev` sur la VM, mais rien n'est poussé avant T7 | Pousser `dev` (sur accord) avant T6, ou réordonner T6/T7 |
| m7 | `ressources/Dockerfile.prod.dockerignore` (original) | L'image en service a été construite sans exclusion de `/ressources/` : si le clone de la VM contenait `ressources/`, l'image actuelle l'embarque | En T6 : `docker exec mangatrackr_app_prod ls -A /var/www/ressources` ; si présent, supprimer l'ancienne image après le premier déploiement scripté |
| m8 | `docs/decisions/0015-...md:6,19-39` | ADR : « rapports 001 à 003 » alors que 004 a introduit `name: sf-mangatrackr` et le contrôle du label, décision absente de la section Décision | Citer 004 et ajouter une puce « nom de projet Compose fixé » |
| m9 | `docker/vhosts/mangatracker.conf` (ErrorLog/CustomLog) | Journaux Apache dans des fichiers du conteneur : absents de `compose logs`, non couverts par le contrôle de secrets de `check.sh`, croissance dans la couche inscriptible (préexistant) | Dette : rediriger vers `/proc/self/fd/1` et `/2` dans un vhost de prod |
| m10 | `docs/claude/etat-et-dette-technique.md` (« Pistes », point 1) ; `apps-dev.md:107` | Piste 1 obsolète (« importer la base sur le homelab ») ; table des codes de `check.sh` sans le code 2 (usage) | Mettre à jour la piste ; ajouter « 2 usage » |

Nuances, sans correction exigée : `pg_restore --list` (`backup.sh:99`) ne détecte pas une archive
tronquée (TOC en tête) ; la vraie garantie est le code de retour de `pg_dump`, qui est contrôlé.
`--all-or-nothing` garantit un « schéma inchangé » sur PostgreSQL tant qu'aucune migration
n'utilise d'instruction non transactionnelle (`CREATE INDEX CONCURRENTLY`...).

### Points vérifiés conformes
- **Secrets** : refus de `set -x` (`lib.sh:19-24`) ; mot de passe du rôle par stdin avec
  `log_statement` coupé (`init-database.sh:357-362`) ; `compose config --quiet` partout ; contrôle
  des journaux par compteurs, valeurs comparées via `grep -F -f <(...)` (pas en argument) ;
  Dockerfile.prod : variables factices sur la seule ligne `RUN`, aucun `ENV` ni `ARG` secret ;
  `.dist` sans vraie valeur ; `.gitignore` `/ressources/` (aucun fichier suivi dessous) ; droits
  700/600 posés et contrôlés ; `umask 077` pour les sauvegardes.
- **Sûreté des données** : aucun `down`, `DROP`, `rm` de base dans les scripts ; migration
  seulement avec `--migrate`, après `backup.sh` réussi, arrêt avant `up` sinon ; `--restore` exige
  `CONFIRM_TARGET`, vérifié avant toute création, refuse une base non vide et `*_test` ;
  `--single-transaction --exit-on-error` ; rotation limitée au motif strict
  `backup_<base>_AAAAMMJJ-HHMMSS.dump`, sauvegarde courante protégée, `.part` renommé après succès.
- **§7.5** : sûre ; en cas de session restante, le `RENAME` échoue, `init-database.sh` voit la base
  existante et `--restore` refuse (tables présentes) : enchaînement collé d'un bloc sans dégât.
  **§7.6** : exacte (quoting `$SU` développé dans le conteneur), base jetable distincte,
  `DROP` uniquement sur `mangatrackr_test_restauration`.
- **Image** : `migrations/`, `config/`, `templates/`, `translations/`, `assets/`, `bin/`, `public/`
  (hors `build/` et `bundles/`, regénérés) présents ; exclusions `docs/`, `tests/`, `.github/`
  sans usage à l'exécution (autoload prod = `src/` seul) ; aucun `.sql`/`.zip` suivi ; pas
  d'upload persistant à perdre lors d'une recréation.
- **Commandes** : `compose run --rm --no-deps -T` (ni `container_name` ni ports appliqués),
  `up -d --no-build --remove-orphans --wait --wait-timeout`, `migrations:up-to-date` (0 si une
  migration exécutée est inconnue du code : pas de faux KO après `--rollback`), `--all-or-nothing`,
  `--allow-no-migration` : valides. Idempotence d'`install-vm.sh` et `init-database.sh` réelle.
- **Doc ↔ scripts** : noms, options, codes de sortie (hors m10), chemins et variables cohérents ;
  la procédure §6 (déplacement des fichiers posés à la main, `diff`, label Compose) est claire.
- **Conventions** : ADR au modèle, index à jour, CLAUDE.md et `contexte.md` cohérents, en-têtes
  « dev/démo uniquement » sur `docker/Dockerfile` et `docker/docker.sh`.

## Écarts, risques, points non vérifiés
- Aucune exécution contre un vrai Docker/PostgreSQL (pas de Docker dans le devcontainer) :
  comportement réel de `--wait`, de `pg_restore --role` sur un dump `--no-owner` (`COMMENT ON
  EXTENSION`, `CREATE EXTENSION unaccent` par le rôle propriétaire) et du cache de build
  (M1 suppose un ID d'image identique sur cache intégral, comportement standard de BuildKit)
  restent à confirmer sur la VM.
- Label `com.docker.compose.project` des conteneurs et de `postgres` sur la VM inconnu (M2).
- shellcheck non relancé (binaire absent) ; `make qa-php-stan` / `make test-only` non relancés
  (aucun PHP modifié).

## Recommandation pour la suite
1. T5.1 (developpeur) : M1, M2, M3, puis m1, m2, m3, m5, m8, m10 ; rejouer le banc du testeur avec
   un cas « image inchangée » (M1) et « postgres dans le même projet » (M2).
2. Consigner m4 et m9 dans la dette technique.
3. Avant T6 : régler m6 (push), et ajouter à la liste T6 le relevé des labels Compose de
   `mangatrackr_app_prod`, `mangatrackr_worker_prod` **et** `postgres`, ainsi que m7.
