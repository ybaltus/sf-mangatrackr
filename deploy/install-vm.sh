#!/usr/bin/env bash
# Préparation idempotente de la VM apps-dev (Debian 13) pour MangaTrackr.
#
# Usage : deploy/install-vm.sh [--dry-run] [--help]
#
# Vérifie : Debian, Docker Engine, plugin Compose v2, git (n'installe RIEN : en cas d'absence,
# s'arrête en renvoyant à docs/deploiement/apps-dev.md, section Docker).
# Crée seulement ce qui manque :
#   - $APPS_ROOT/{projects,secrets,backups} (secrets et backups en 700) ;
#   - les réseaux Docker externes $NETWORKS (database, proxy) ;
#   - le clone du dépôt $REPO_URL dans $PROJECT_DIR (branche REPO_BRANCH si définie) ;
#   - le fichier de secrets $ENV_FILE (600), copié depuis deploy/mangatrackr.prod.env.dist
#     UNIQUEMENT s'il est absent (jamais écrasé) : il faut ensuite remplir ses valeurs.
# Ne crée, ne modifie ni ne redémarre jamais le conteneur PostgreSQL ($PG_CONTAINER).
# Une deuxième exécution n'affiche que « déjà en place ».
#
# Prérequis : $APPS_ROOT inscriptible par l'utilisateur courant, par exemple :
#   sudo install -d -o "$USER" -g "$USER" /opt/apps
#
# Code de sortie : 0 ok ; 2 prérequis manquant ou usage ; 3 conflit (répertoire non vide...).
set -euo pipefail
# shellcheck source=deploy/lib.sh
. "$(dirname "$0")/lib.sh"

# ensure_dir <chemin> [mode] : crée le répertoire s'il manque, corrige ses droits si besoin.
ensure_dir() {
  local d=$1 mode=${2:-}
  if [ -d "$d" ]; then
    if [ -n "$mode" ] && [ "$(file_mode "$d")" != "$mode" ]; then
      log "$d : droits $(file_mode "$d") -> $mode"
      run chmod "$mode" "$d"
    else
      ok "$d déjà en place${mode:+ ($mode)}"
    fi
  else
    log "création de $d${mode:+ ($mode)}"
    run mkdir -p "$d"
    [ -z "$mode" ] || run chmod "$mode" "$d"
  fi
}

check_system() {
  log "Système"
  local id='' version=''
  if [ -r /etc/os-release ]; then
    # shellcheck disable=SC1091
    id=$(. /etc/os-release && echo "${ID:-}")
    # shellcheck disable=SC1091
    version=$(. /etc/os-release && echo "${VERSION_ID:-}")
  fi
  if [ "$id" = debian ]; then
    ok "Debian ${version:-?}"
    [ "$version" = 13 ] || warn "VM de référence en Debian 13 (trouvé : ${version:-inconnu})"
  else
    warn "système non Debian (${id:-inconnu}) : procédure prévue pour Debian 13"
  fi
  require_docker
  ok "$(docker --version)"
  ok "$(docker compose version)"
  require_cmd git
  ok "$(git --version)"
}

check_dirs() {
  log "Répertoires sous $APPS_ROOT"
  if [ ! -d "$APPS_ROOT" ] && [ ! -w "$(dirname "$APPS_ROOT")" ]; then
    die "$APPS_ROOT absent et non créable : sudo install -d -o \"\$USER\" -g \"\$USER\" $APPS_ROOT" 2
  fi
  if [ -d "$APPS_ROOT" ] && [ ! -w "$APPS_ROOT" ]; then
    die "$APPS_ROOT non inscriptible par $(id -un) : sudo chown \"\$USER\": $APPS_ROOT" 2
  fi
  ensure_dir "$APPS_ROOT"
  ensure_dir "$APPS_ROOT/projects"
  ensure_dir "$SECRETS_DIR" 700
  ensure_dir "$BACKUP_DIR" 700
}

check_networks() {
  log "Réseaux Docker externes"
  local n
  for n in $NETWORKS; do
    if docker network inspect "$n" >/dev/null 2>&1; then
      ok "réseau $n déjà en place"
    else
      log "création du réseau $n"
      run docker network create "$n"
    fi
  done
  # Lecture seule : le conteneur PostgreSQL doit être rattaché au réseau database (non modifié ici).
  local st nets
  st=$(container_state "$PG_CONTAINER")
  if [ "$st" = absent ]; then
    warn "conteneur $PG_CONTAINER absent : PostgreSQL mutualisé à installer séparément (voir la doc), puis deploy/init-database.sh"
  else
    nets=$(docker inspect --format '{{range $k, $v := .NetworkSettings.Networks}}{{$k}} {{end}}' "$PG_CONTAINER")
    case " $nets " in
      *" database "*) ok "conteneur $PG_CONTAINER ($st) rattaché au réseau database" ;;
      *) warn "conteneur $PG_CONTAINER ($st) non rattaché au réseau database (réseaux : ${nets:-aucun})" ;;
    esac
  fi
}

check_clone() {
  log "Dépôt dans $PROJECT_DIR"
  if [ -d "$PROJECT_DIR/.git" ]; then
    ok "clone déjà en place ($(git -C "$PROJECT_DIR" rev-parse --abbrev-ref HEAD 2>/dev/null || echo '?'), origine : $(git -C "$PROJECT_DIR" remote get-url origin 2>/dev/null || echo '?'))"
  elif [ -e "$PROJECT_DIR" ] && [ -n "$(ls -A "$PROJECT_DIR" 2>/dev/null)" ]; then
    die "$PROJECT_DIR existe, n'est pas un clone git et n'est pas vide : rien n'est écrasé." 3
  else
    log "clonage de $REPO_URL"
    if [ -n "${REPO_BRANCH:-}" ]; then
      run git clone --branch "$REPO_BRANCH" "$REPO_URL" "$PROJECT_DIR"
    else
      run git clone "$REPO_URL" "$PROJECT_DIR"
    fi
  fi
}

check_env_file() {
  log "Fichier de secrets $ENV_FILE"
  local dist="$DEPLOY_DIR/mangatrackr.prod.env.dist" n
  if [ -f "$ENV_FILE" ]; then
    if [ "$(file_mode "$ENV_FILE")" != 600 ]; then
      log "$ENV_FILE : droits $(file_mode "$ENV_FILE") -> 600"
      run chmod 600 "$ENV_FILE"
    else
      ok "$ENV_FILE déjà en place (600)"
    fi
    n=$(env_placeholders)
    [ "$n" = 0 ] || warn "$n valeur(s) encore au format <...> dans $ENV_FILE : à remplir avant deploy.sh"
  else
    [ -f "$dist" ] || die "modèle introuvable : $dist" 2
    log "copie du modèle $dist (600) : REMPLIR ensuite chaque valeur <...>"
    run install -m 600 "$dist" "$ENV_FILE"
  fi
}

main() {
  while [ $# -gt 0 ]; do
    case "$1" in
      --dry-run) DRY_RUN=1 ;;
      -h | --help) usage; exit 0 ;;
      *) die "option inconnue : $1" 2 ;;
    esac
    shift
  done
  [ "$DRY_RUN" = 0 ] || log "MODE --dry-run : aucune modification"
  # Pas de umask 077 global : le clone doit rester lisible (COPY conserve les droits dans l'image).
  check_system
  check_dirs
  check_networks
  check_clone
  check_env_file
  log "Terminé. Suite : remplir $ENV_FILE, deploy/init-database.sh, puis deploy/deploy.sh."
}

main "$@"
