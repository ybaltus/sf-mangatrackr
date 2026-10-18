#!/usr/bin/env bash
# Déploiement (ou retour arrière) de MangaTrackr en production sur la VM apps-dev.
#
# Usage : deploy/deploy.sh [--ref <branche|tag|commit>] [--migrate] [--dry-run]
#         deploy/deploy.sh --rollback [--dry-run]
#         (ou make prod-deploy ARGS="--dry-run")
#
# Étapes :
#  1. Préflight : Docker, PostgreSQL ($PG_CONTAINER) en marche/sain, réseaux $NETWORKS, $ENV_FILE
#     présent en 600 et sans valeur <...> du modèle, conteneurs existants dans le projet Compose
#     $COMPOSE_PROJECT_NAME (défaut sf-mangatrackr), aucun conteneur étranger (dont $PG_CONTAINER)
#     dans ce projet, `compose config --quiet`.
#  2. Code : arbre git propre exigé ; `git fetch`, puis checkout + `pull --ff-only` de la branche
#     (défaut : branche courante) ou checkout détaché d'un tag/commit.
#  3. Image : l'image EN SERVICE est relevée, puis `compose build`. Elle ne devient
#     $IMAGE_PREVIOUS qu'au moment du `up` (une tentative interrompue garde l'ancien retour arrière) ;
#     si la construction redonne l'image en service (cache intégral), $IMAGE_PREVIOUS n'est pas touché.
#  4. Migrations (conteneur jetable `compose run --rm --no-deps`) : si une migration est en attente,
#     arrêt AVANT `up` sans --migrate (le tag $IMAGE est remis sur l'image en service) ; avec
#     --migrate : deploy/backup.sh puis doctrine:migrations:migrate --all-or-nothing (une seule
#     transaction : en cas d'échec, schéma inchangé).
#  5. `compose up -d --no-build --wait` (sans --remove-orphans : un conteneur étranger rattaché par
#     erreur au projet ne doit jamais être supprimé), puis deploy/check.sh.
#     En cas d'échec : deploy/deploy.sh --rollback.
#
# --rollback : retague $IMAGE_PREVIOUS en $IMAGE, `compose up -d`, check.sh. Le SCHÉMA n'est PAS
#   ramené en arrière (une migration appliquée reste appliquée : restaurer la sauvegarde si besoin)
#   et le code git n'est pas modifié.
# --dry-run : contrôles en lecture seule exécutés ; actions (fetch, checkout, tag, build,
#   migrations, up) seulement affichées.
# Jamais de `docker compose down` ; le conteneur PostgreSQL n'est jamais modifié.
#
# Variables : voir deploy/lib.sh (PROJECT_DIR, ENV_FILE, COMPOSE_FILE...) ; WAIT_TIMEOUT (180 s).
# Code de sortie : 0 ok ; 2 usage/préflight ; 3 échec (build, base, migration en attente ou
#   échouée) ; 4 contrôles post-déploiement KO.
set -euo pipefail
# shellcheck source=deploy/lib.sh
. "$(dirname "$0")/lib.sh"

REF=''
MIGRATE=0
ROLLBACK=0
# Identifiant de l'image du conteneur app en service, relevé avant la construction.
SERVICE_ID=''
# 1 si la construction a produit exactement l'image en service (cache intégral) : prod-previous gardé.
SAME_IMAGE=0
: "${WAIT_TIMEOUT:=180}"

git_p() { git -C "$PROJECT_DIR" "$@"; }

# console_run <commande console...> : bin/console dans un conteneur jetable de la nouvelle image.
console_run() {
  compose run --rm --no-deps -T app php bin/console "$@" --env=prod --no-debug
}

acquire_lock() {
  command -v flock >/dev/null 2>&1 || { warn "flock absent : pas de verrou contre deux déploiements simultanés"; return 0; }
  exec 9>"$PROJECT_DIR/.git/mangatrackr-deploy.lock"
  flock -n 9 || die "un autre déploiement est en cours (verrou $PROJECT_DIR/.git/mangatrackr-deploy.lock)" 2
}

preflight() {
  log "Préflight"
  require_docker
  require_cmd git
  [ -d "$PROJECT_DIR/.git" ] || die "$PROJECT_DIR n'est pas un clone git (deploy/install-vm.sh)" 2
  require_pg_running
  local n
  for n in $NETWORKS; do
    docker network inspect "$n" >/dev/null 2>&1 || die "réseau Docker $n absent (deploy/install-vm.sh)" 2
  done
  ok "réseaux : $NETWORKS"
  [ -f "$ENV_FILE" ] || die "fichier de secrets absent : $ENV_FILE (deploy/install-vm.sh)" 2
  [ "$(file_mode "$ENV_FILE")" = 600 ] || die "$ENV_FILE en $(file_mode "$ENV_FILE") : chmod 600 attendu" 2
  n=$(env_placeholders)
  [ "$n" = 0 ] || die "$n valeur(s) encore au format <...> dans $ENV_FILE" 2
  ok "fichier de secrets : $ENV_FILE (600)"
  check_compose_project
  check_compose_config
}

# Les conteneurs existants doivent appartenir au projet Compose attendu, sinon `up` échouerait sur
# le conflit de container_name (nom fixé par `name:` dans compose.prod.yaml, ou COMPOSE_PROJECT_NAME).
check_compose_project() {
  local c label
  for c in "$APP_CONTAINER" "$WORKER_CONTAINER"; do
    label=$(docker inspect --format '{{index .Config.Labels "com.docker.compose.project"}}' "$c" 2>/dev/null) || continue
    if [ "$label" != "$COMPOSE_PROJECT_NAME" ]; then
      die "$c appartient au projet Compose « ${label:-aucun} », attendu « $COMPOSE_PROJECT_NAME » : relancer avec COMPOSE_PROJECT_NAME=${label:-<nom>} (voir docs/deploiement/apps-dev.md)" 2
    fi
  done
  # Le PostgreSQL mutualisé (ou toute autre application) ne doit pas partager ce projet Compose :
  # Compose le considérerait comme un des siens (orphelin à supprimer, conteneur à arrêter...).
  label=$(docker inspect --format '{{index .Config.Labels "com.docker.compose.project"}}' "$PG_CONTAINER" 2>/dev/null || true)
  if [ -n "$label" ] && [ "$label" = "$COMPOSE_PROJECT_NAME" ]; then
    die "le conteneur $PG_CONTAINER appartient au projet Compose « $label » de MangaTrackr : déploiement refusé (voir docs/deploiement/apps-dev.md, section 6)" 2
  fi
  local names others=()
  names=$(docker ps -a --filter "label=com.docker.compose.project=$COMPOSE_PROJECT_NAME" --format '{{.Names}}') \
    || die "impossible de lister les conteneurs du projet Compose $COMPOSE_PROJECT_NAME" 2
  while IFS= read -r c; do
    case "$c" in
      '' | "$APP_CONTAINER" | "$WORKER_CONTAINER" | "$COMPOSE_PROJECT_NAME"-*-run-*) ;;
      *) others+=("$c") ;;
    esac
  done <<<"$names"
  if [ "${#others[@]}" -gt 0 ]; then
    die "conteneur(s) étranger(s) dans le projet Compose $COMPOSE_PROJECT_NAME : ${others[*]} (attendus : $APP_CONTAINER, $WORKER_CONTAINER). Déploiement refusé : voir docs/deploiement/apps-dev.md, section 6" 2
  fi
  ok "projet Compose : $COMPOSE_PROJECT_NAME (aucun conteneur étranger, $PG_CONTAINER hors projet)"
}

# Validation seule : `config` SANS --quiet afficherait les valeurs de l'env_file.
check_compose_config() {
  compose config --quiet || die "compose config invalide ($COMPOSE_FILE)" 2
  ok "compose config ($COMPOSE_FILE)"
}

update_code() {
  log "Code ($PROJECT_DIR)"
  local dirty cur ref remote
  dirty=$(git_p status --porcelain)
  if [ -n "$dirty" ]; then
    printf '%s\n' "$dirty" | head -n 20 >&2
    die "arbre git non propre : valider, remiser ou déplacer ces fichiers avant de déployer." 2
  fi
  cur=$(git_p symbolic-ref --quiet --short HEAD || true)
  ref=${REF:-$cur}
  [ -n "$ref" ] || die "HEAD détachée : préciser --ref <branche|tag|commit>" 2
  log "avant : $(git_p log -1 --format='%h %s') (${cur:-détachée})"
  run git -C "$PROJECT_DIR" fetch --prune --tags origin
  if git_p show-ref --verify --quiet "refs/remotes/origin/$ref"; then
    [ "$ref" = "$cur" ] || run git -C "$PROJECT_DIR" checkout "$ref"
    run git -C "$PROJECT_DIR" pull --ff-only origin "$ref"
  elif git_p rev-parse --verify --quiet "$ref^{commit}" >/dev/null; then
    run git -C "$PROJECT_DIR" checkout --detach "$ref"
  elif [ "$DRY_RUN" = 1 ] && remote=$(git_p ls-remote --exit-code origin "refs/heads/$ref" "refs/tags/$ref" 2>/dev/null); then
    # En --dry-run le fetch n'est qu'affiché : interroger origin en lecture seule.
    warn "$ref existe sur origin mais n'est pas encore récupérée (le fetch réel la rendra disponible)"
    case "$remote" in
      *refs/heads/*)
        run git -C "$PROJECT_DIR" checkout "$ref"
        run git -C "$PROJECT_DIR" pull --ff-only origin "$ref"
        ;;
      *) run git -C "$PROJECT_DIR" checkout --detach "$ref" ;;
    esac
  else
    die "ref inconnue : $ref (ni branche origin/$ref, ni tag, ni commit$([ "$DRY_RUN" = 0 ] || echo ', ni ref de origin'))" 2
  fi
  [ "$DRY_RUN" = 1 ] || log "après : $(git_p log -1 --format='%h %s')"
}

# Relève l'image du conteneur app EN SERVICE (à défaut : $IMAGE), sans rien retaguer :
# $IMAGE_PREVIOUS n'est remplacé qu'au moment du `up` (promote_previous). Une tentative
# interrompue (migration en attente, échec du build...) conserve donc la cible de retour arrière.
record_service_image() {
  SERVICE_ID=$(docker inspect --format '{{.Image}}' "$APP_CONTAINER" 2>/dev/null || true)
  [ -n "$SERVICE_ID" ] || SERVICE_ID=$(docker image inspect --format '{{.Id}}' "$IMAGE" 2>/dev/null || true)
  if [ -z "$SERVICE_ID" ]; then
    warn "aucune image en service : premier déploiement, pas de retour arrière possible"
  else
    log "image en service : ${SERVICE_ID:7:12}"
  fi
}

# Juste avant `up` : l'image en service devient $IMAGE_PREVIOUS.
promote_previous() {
  [ -n "$SERVICE_ID" ] || return 0
  if [ "$SAME_IMAGE" = 1 ]; then
    log "image inchangée (construction identique à l'image en service) : $IMAGE_PREVIOUS conservé, retour arrière inchangé"
    return 0
  fi
  log "image en service ${SERVICE_ID:7:12} -> $IMAGE_PREVIOUS"
  run docker tag "$SERVICE_ID" "$IMAGE_PREVIOUS"
}

# Après `compose build` : la nouvelle image est-elle celle déjà en service ?
compare_built_image() {
  if [ "$DRY_RUN" = 1 ] || [ -z "$SERVICE_ID" ]; then
    return 0
  fi
  local new_id
  new_id=$(docker image inspect --format '{{.Id}}' "$IMAGE" 2>/dev/null || true)
  if [ -n "$new_id" ] && [ "$new_id" = "$SERVICE_ID" ]; then
    SAME_IMAGE=1
    warn "l'image construite est identique à l'image en service (${SERVICE_ID:7:12}) : $IMAGE_PREVIOUS ne sera pas modifié"
  fi
}

# Remet $IMAGE sur l'image en service (arrêt avant `up`).
restore_tag() {
  if [ -n "$SERVICE_ID" ]; then
    warn "$IMAGE remis sur l'image en service (${SERVICE_ID:7:12}) ; conteneurs inchangés"
    run docker tag "$SERVICE_ID" "$IMAGE"
  fi
}

migrations() {
  log "Migrations (conteneur jetable de la nouvelle image)"
  if [ "$DRY_RUN" = 1 ]; then
    run compose run --rm --no-deps -T app php bin/console doctrine:migrations:up-to-date --env=prod --no-debug
    if [ "$MIGRATE" = 1 ]; then
      "$DEPLOY_DIR/backup.sh" --dry-run
      run compose run --rm --no-deps -T app php bin/console doctrine:migrations:migrate \
        --no-interaction --allow-no-migration --all-or-nothing --env=prod --no-debug
    else
      echo "[dry-run] si une migration est en attente : arrêt avant up (relancer avec --migrate)"
    fi
    return 0
  fi
  console_run dbal:run-sql 'SELECT 1' >/dev/null \
    || { restore_tag; die "base injoignable depuis la nouvelle image (DATABASE_URL, réseau database)" 3; }
  local out rc=0
  out=$(console_run doctrine:migrations:up-to-date 2>&1) || rc=$?
  # sed et non grep : une sortie vide ne doit pas faire échouer le pipeline (pipefail).
  printf '%s\n' "$out" | sed -n '/[^[:space:]]/s/^/    /p'
  if [ "$rc" = 0 ]; then
    ok "aucune migration en attente"
    return 0
  fi
  # up-to-date : 1 avec « Out-of-date! » = migration(s) en attente. Tout autre cas (exception de la
  # console, configuration, code 2...) est une erreur : jamais de sauvegarde ni de migrate.
  if [ "$rc" != 1 ] || ! grep -q 'Out-of-date!' <<<"$out"; then
    restore_tag
    die "doctrine:migrations:up-to-date en erreur (code $rc) : état des migrations inconnu, rien n'a été redémarré" 3
  fi
  if [ "$MIGRATE" = 0 ]; then
    restore_tag
    die "migration(s) en attente : relancer avec --migrate (sauvegarde pg_dump puis migrate). Rien n'a été redémarré." 3
  fi
  log "--migrate : sauvegarde avant migration"
  "$DEPLOY_DIR/backup.sh" || { restore_tag; die "sauvegarde échouée : migration annulée" 3; }
  console_run doctrine:migrations:migrate --no-interaction --allow-no-migration --all-or-nothing \
    || { restore_tag; die "migration échouée (une seule transaction : schéma inchangé). Voir la sauvegarde dans $BACKUP_DIR." 3; }
  ok "migrations appliquées"
}

start_and_check() {
  local hint="deploy/deploy.sh --rollback"
  # Pendant un retour arrière, reconseiller --rollback n'aurait pas de sens.
  [ "$ROLLBACK" = 0 ] || hint="retour arrière lui-même en échec : voir docs/deploiement/apps-dev.md (dépannage, restauration)"
  log "Démarrage (attente de l'état sain, ${WAIT_TIMEOUT} s max)"
  run compose up -d --no-build --wait --wait-timeout "$WAIT_TIMEOUT" \
    || die "les services ne sont pas sains : $hint (journaux : docker compose -f $COMPOSE_FILE logs --tail=100 app worker)" 4
  if [ "$DRY_RUN" = 1 ]; then
    echo "[dry-run] $DEPLOY_DIR/check.sh"
    return 0
  fi
  "$DEPLOY_DIR/check.sh" || die "contrôles KO après $([ "$ROLLBACK" = 1 ] && echo 'retour arrière' || echo 'déploiement') : $hint" 4
}

rollback() {
  log "Retour arrière vers $IMAGE_PREVIOUS"
  docker image inspect "$IMAGE_PREVIOUS" >/dev/null 2>&1 || die "aucune image $IMAGE_PREVIOUS : retour arrière impossible" 3
  warn "le schéma de la base n'est PAS ramené en arrière ; le code git n'est pas modifié"
  run docker tag "$IMAGE_PREVIOUS" "$IMAGE"
  start_and_check
  if [ "$DRY_RUN" = 1 ]; then
    log "Fin du dry-run (retour arrière) : rien n'a été modifié."
  else
    log "Retour arrière terminé. Pour rester sur cette version au prochain déploiement : --ref <commit précédent>."
  fi
}

main() {
  while [ $# -gt 0 ]; do
    case "$1" in
      --ref)
        if [ $# -lt 2 ] || [ -z "$2" ]; then
          die "--ref exige une branche, un tag ou un commit" 2
        fi
        REF=$2
        shift
        ;;
      --migrate) MIGRATE=1 ;;
      --rollback) ROLLBACK=1 ;;
      --dry-run) DRY_RUN=1 ;;
      -h | --help) usage; exit 0 ;;
      *) die "option inconnue : $1 (voir --help)" 2 ;;
    esac
    shift
  done
  if [ "$ROLLBACK" = 1 ] && { [ -n "$REF" ] || [ "$MIGRATE" = 1 ]; }; then
    die "--rollback ne se combine pas avec --ref ni --migrate" 2
  fi
  [[ $WAIT_TIMEOUT =~ ^[0-9]+$ ]] || die "WAIT_TIMEOUT doit être un nombre de secondes" 2
  [ "$DRY_RUN" = 0 ] || log "MODE --dry-run : contrôles exécutés, actions seulement affichées"

  [ -d "$PROJECT_DIR/.git" ] || die "$PROJECT_DIR n'est pas un clone git (deploy/install-vm.sh)" 2
  acquire_lock
  preflight
  if [ "$ROLLBACK" = 1 ]; then
    rollback
    exit 0
  fi
  update_code
  [ "$DRY_RUN" = 1 ] || check_compose_config
  record_service_image
  log "Construction de $IMAGE"
  run compose build app || { restore_tag; die "construction de l'image échouée (rien n'a été redémarré)" 3; }
  compare_built_image
  migrations
  promote_previous
  start_and_check
  if [ "$DRY_RUN" = 1 ]; then
    log "Fin du dry-run : rien n'a été modifié."
  else
    log "Déploiement terminé : $(git_p log -1 --format='%h %s')"
  fi
}

main "$@"
