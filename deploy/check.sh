#!/usr/bin/env bash
# Contrôle de la production MangaTrackr, en LECTURE SEULE (aucun conteneur créé ni modifié).
#
# Usage : deploy/check.sh [--dry-run] [--help]          (ou make prod-check)
#   --dry-run : liste les contrôles sans les exécuter.
#
# Contrôles : Docker joignable ; `compose ps` ; conteneur PostgreSQL en marche ; conteneur app en
# marche et sain (healthcheck) ; worker en marche ; droits 600 du fichier de secrets ; pilote PDO
# pgsql ; requête `SELECT current_database(), current_user` (base $DB_NAME, rôle $DB_ROLE) ;
# aucune migration en attente ; code HTTP 200/301/302 sur $APP_URL ; aucun motif de secret dans les
# 100 dernières lignes de journaux de app et worker (seul un compteur est affiché, jamais les lignes).
#
# Code de sortie : 0 si tous les contrôles sont OK ; 1 si au moins un est KO ; 2 usage.

# Les fonctions c_* sont appelées indirectement par check() :
# shellcheck disable=SC2317
set -euo pipefail
# shellcheck source=deploy/lib.sh
. "$(dirname "$0")/lib.sh"

NB_OK=0
KO_LIST=()

# check <libellé> <fonction> : exécute un contrôle et comptabilise OK/KO.
check() {
  local label=$1
  shift
  if [ "$DRY_RUN" = 1 ]; then
    echo "[dry-run] contrôle : $label"
    return 0
  fi
  if "$@"; then
    printf '    OK : %s\n' "$label"
    NB_OK=$((NB_OK + 1))
  else
    printf '    KO : %s\n' "$label"
    KO_LIST+=("$label")
  fi
}

# En www-data, comme Apache : un bin/console lancé en root créerait dans var/cache des fichiers
# qu'Apache ne pourrait plus écrire.
console() { docker exec -u www-data "$APP_CONTAINER" php bin/console "$@" --env=prod --no-debug; }

has_compose_file() {
  [ -f "$PROJECT_DIR/$COMPOSE_FILE" ] || { echo "      fichier Compose absent : $PROJECT_DIR/$COMPOSE_FILE"; return 1; }
}

c_compose_ps() { has_compose_file && compose ps; }

c_pg() {
  local st
  st=$(container_state "$PG_CONTAINER")
  echo "      $PG_CONTAINER : $st"
  case "$st" in running/healthy | running/sans-healthcheck) return 0 ;; *) return 1 ;; esac
}

c_app() {
  local st
  st=$(container_state "$APP_CONTAINER")
  echo "      $APP_CONTAINER : $st"
  case "$st" in
    running/healthy) return 0 ;;
    running/sans-healthcheck)
      warn "$APP_CONTAINER sans healthcheck : conteneur créé avant compose.prod.yaml versionné (redéployer)"
      return 0
      ;;
    running/starting) warn "$APP_CONTAINER en démarrage : relancer le contrôle dans 30 s" ; return 1 ;;
    *) return 1 ;;
  esac
}

c_worker() {
  local st
  st=$(container_state "$WORKER_CONTAINER")
  echo "      $WORKER_CONTAINER : $st"
  case "$st" in running/*) return 0 ;; *) return 1 ;; esac
}

c_env_file() {
  [ -f "$ENV_FILE" ] || { echo "      absent : $ENV_FILE"; return 1; }
  local m
  m=$(file_mode "$ENV_FILE")
  echo "      $ENV_FILE : $m"
  [ "$m" = 600 ]
}

c_pdo() {
  docker exec "$APP_CONTAINER" php -r 'exit(in_array("pgsql", PDO::getAvailableDrivers(), true) ? 0 : 1);'
}

c_sql() {
  local out
  out=$(console dbal:run-sql 'SELECT current_database(), current_user' 2>&1) || { echo "$out" | tail -n 5; return 1; }
  printf '%s\n' "$out" | sed -n '/[^[:space:]]/s/^/      /p'
  grep -qE "(^|[[:space:]])${DB_NAME}[[:space:]]+${DB_ROLE}([[:space:]]|$)" <<<"$out"
}

c_migrations() {
  local out rc=0
  out=$(console doctrine:migrations:up-to-date 2>&1) || rc=$?
  printf '%s\n' "$out" | sed -n '/[^[:space:]]/s/^/      /p' | tail -n 3
  return "$rc"
}

c_http() {
  local code
  if command -v curl >/dev/null 2>&1; then
    code=$(curl -sS -o /dev/null -w '%{http_code}' --max-time 15 "$APP_URL" 2>/dev/null) || code=000
    echo "      $APP_URL -> $code"
  else
    code=$(docker exec "$APP_CONTAINER" curl -sS -o /dev/null -w '%{http_code}' --max-time 15 http://127.0.0.1/ 2>/dev/null) || code=000
    echo "      curl absent de l'hôte : test depuis le conteneur (http://127.0.0.1/) -> $code"
  fi
  case "$code" in 200 | 301 | 302) return 0 ;; *) return 1 ;; esac
}

c_logs() {
  local logs n_generic n_values=0 vals=() v
  has_compose_file || return 1
  logs=$(compose logs --no-color --tail=100 app worker 2>&1) || return 1
  # Motifs génériques : secret client Google, URL avec identifiants, affectations de secrets.
  n_generic=$(grep -cE \
    -e 'GOCSPX' \
    -e '(postgres(ql)?|mysql|smtps?)://[^:/@[:space:]]+:[^@[:space:]]+@' \
    -e '(APP_SECRET|CLIENT_SECRET|PASSWORD)=' <<<"$logs" || true)
  # Valeurs réelles du fichier de secrets (comparaison sans affichage).
  if [ -r "$ENV_FILE" ]; then
    for v in "$(env_get APP_SECRET || true)" "$(env_get OAUTH_GOOGLE_CLIENT_SECRET || true)"; do
      [ "${#v}" -ge 8 ] && vals+=("$v")
    done
    if (parse_database_url) >/dev/null 2>&1; then
      parse_database_url
      [ "${#DBURL_PASS}" -ge 6 ] && vals+=("$DBURL_PASS")
      DBURL_PASS=''
    fi
    if [ "${#vals[@]}" -gt 0 ]; then
      n_values=$(grep -cF -f <(printf '%s\n' "${vals[@]}") <<<"$logs" || true)
    fi
    vals=()
  fi
  logs=''
  echo "      lignes suspectes : motifs génériques $n_generic, valeurs du fichier de secrets $n_values"
  [ "$n_generic" = 0 ] && [ "$n_values" = 0 ]
}

main() {
  while [ $# -gt 0 ]; do
    case "$1" in
      --dry-run) DRY_RUN=1 ;;
      -h | --help) usage; exit 0 ;;
      *) die "option inconnue : $1 (voir --help)" 2 ;;
    esac
    shift
  done
  [ "$DRY_RUN" = 0 ] || log "MODE --dry-run : contrôles listés, non exécutés"
  [ "$DRY_RUN" = 1 ] || require_docker
  log "Contrôle de la production ($PROJECT_DIR, $COMPOSE_FILE)"
  check "compose ps" c_compose_ps
  check "PostgreSQL ($PG_CONTAINER) en marche" c_pg
  check "conteneur app en marche et sain" c_app
  check "conteneur worker en marche" c_worker
  check "fichier de secrets en 600" c_env_file
  check "pilote PDO pgsql" c_pdo
  check "SELECT current_database(), current_user = $DB_NAME / $DB_ROLE" c_sql
  check "aucune migration en attente" c_migrations
  check "HTTP 200/301/302 sur $APP_URL" c_http
  check "aucun secret dans les 100 dernières lignes de journaux" c_logs
  [ "$DRY_RUN" = 0 ] || exit 0

  echo
  if [ "${#KO_LIST[@]}" -eq 0 ]; then
    log "Résumé : OK ($NB_OK contrôles)"
    exit 0
  fi
  log "Résumé : KO (${#KO_LIST[@]} en échec, $NB_OK OK)"
  printf '    - %s\n' "${KO_LIST[@]}"
  exit 1
}

main "$@"
