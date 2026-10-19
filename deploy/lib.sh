#!/usr/bin/env bash
# Fonctions et constantes communes aux scripts deploy/*.sh (à SOURCER, ne pas exécuter).
#
# Constantes : valeurs de la fiche de référence de la VM apps-dev (docs/deploiement/apps-dev.md),
# toutes surchargeables par une variable d'environnement du même nom, par exemple :
#   APPS_ROOT=/srv/apps deploy/check.sh
#
# Règles communes :
#  - `run` exécute une commande qui MODIFIE quelque chose ; en --dry-run il l'affiche seulement.
#    Les contrôles en lecture seule sont exécutés directement, même en --dry-run.
#  - `compose` appelle TOUJOURS `docker compose -f "$COMPOSE_FILE"` depuis PROJECT_DIR et refuse `down`.
#  - Le conteneur PostgreSQL mutualisé n'est jamais créé, recréé, arrêté ni reconfiguré.
#  - Aucun secret n'est affiché ni passé en argument de commande (visible dans `ps`) :
#    `set -x` est donc refusé.

# Variables définies ici et utilisées par les scripts qui sourcent ce fichier :
# shellcheck disable=SC2034

case $- in
  *x*)
    echo "ERREUR : set -x / bash -x interdit (les scripts manipulent des secrets)." >&2
    exit 2
    ;;
esac

# --- Constantes surchargeables ------------------------------------------------------------
: "${APPS_ROOT:=/opt/apps}"
: "${PROJECT_DIR:=$APPS_ROOT/projects/sf-mangatrackr}"
: "${SECRETS_DIR:=$APPS_ROOT/secrets}"
: "${ENV_FILE:=$SECRETS_DIR/mangatrackr.prod.env}"
: "${BACKUP_DIR:=$APPS_ROOT/backups}"
: "${PG_CONTAINER:=postgres}"
: "${DB_NAME:=mangatrackr}"
: "${DB_ROLE:=mangatrackr}"
: "${COMPOSE_FILE:=compose.prod.yaml}"
: "${MANGATRACKR_BIND:=192.168.1.29:8080}"
: "${APP_URL:=http://$MANGATRACKR_BIND/}"
: "${APP_CONTAINER:=mangatrackr_app_prod}"
: "${WORKER_CONTAINER:=mangatrackr_worker_prod}"
: "${IMAGE:=mangatrackr:prod}"
: "${IMAGE_PREVIOUS:=mangatrackr:prod-previous}"
: "${REPO_URL:=https://github.com/ybaltus/sf-mangatrackr}"
: "${NETWORKS:=database proxy}"
# Superutilisateur du conteneur PostgreSQL (vide = $POSTGRES_USER du conteneur, sinon postgres).
: "${PG_SUPERUSER:=}"

# compose.prod.yaml lit ces deux variables : les scripts et Compose désignent ainsi le même fichier.
export MANGATRACKR_ENV_FILE="$ENV_FILE"
export MANGATRACKR_BIND
# Nom du projet Compose : même valeur que `name:` de compose.prod.yaml. COMPOSE_PROJECT_NAME,
# lu nativement par Compose, a priorité sur `name:` : le surcharger si les conteneurs existants
# portent un autre label com.docker.compose.project (deploy.sh le vérifie).
: "${COMPOSE_PROJECT_NAME:=sf-mangatrackr}"
export COMPOSE_PROJECT_NAME

DEPLOY_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
DRY_RUN=${DRY_RUN:-0}

# Les noms de base et de rôle sont insérés dans du SQL : identifiants simples uniquement.
for _ident in "$DB_NAME" "$DB_ROLE"; do
  if ! [[ $_ident =~ ^[a-z_][a-z0-9_]{0,62}$ ]]; then
    echo "ERREUR : identifiant PostgreSQL refusé : '$_ident' (attendu : [a-z_][a-z0-9_]*)" >&2
    exit 2
  fi
done
unset _ident

# usage : affiche le bloc de commentaires d'en-tête du script appelant (option --help).
usage() { awk 'NR > 1 && /^#/ { sub(/^# ?/, ""); print; next } NR > 1 { exit }' "$0"; }

# --- Journalisation -----------------------------------------------------------------------
log() { printf '==> %s\n' "$*"; }
ok() { printf '    OK : %s\n' "$*"; }
warn() { printf 'ATTENTION : %s\n' "$*" >&2; }
# die <message> [code de sortie, défaut 1]
die() {
  printf 'ERREUR : %s\n' "$1" >&2
  exit "${2:-1}"
}

# --- Exécution ----------------------------------------------------------------------------
# run <commande...> : exécute, ou affiche seulement en --dry-run.
run() {
  if [ "$DRY_RUN" = 1 ]; then
    if [ "$1" = compose ]; then
      shift
      printf '[dry-run] (cd %q) docker compose -f %q' "$PROJECT_DIR" "$COMPOSE_FILE"
    else
      printf '[dry-run]'
    fi
    printf ' %q' "$@"
    printf '\n'
    return 0
  fi
  "$@"
}

# compose <sous-commande> [args...] : Compose de production, fichier toujours explicite.
compose() {
  case "${1:-}" in
    down) die "« docker compose down » est interdit par ces scripts (voir docs/deploiement/apps-dev.md)." 2 ;;
    '') die "compose : sous-commande manquante" 2 ;;
  esac
  [ -f "$PROJECT_DIR/$COMPOSE_FILE" ] || die "fichier Compose introuvable : $PROJECT_DIR/$COMPOSE_FILE" 2
  (cd "$PROJECT_DIR" && docker compose -f "$COMPOSE_FILE" "$@")
}

# --- Contrôles communs -------------------------------------------------------------------
require_cmd() {
  local c
  for c in "$@"; do
    command -v "$c" >/dev/null 2>&1 || die "commande introuvable : $c" 2
  done
}

# Docker joignable et plugin Compose v2 présent (rien n'est installé par ces scripts).
require_docker() {
  command -v docker >/dev/null 2>&1 \
    || die "docker introuvable : installer Docker Engine (docs/deploiement/apps-dev.md, section Docker)." 2
  docker info >/dev/null 2>&1 \
    || die "démon Docker injoignable (service arrêté ou utilisateur hors du groupe docker)." 2
  docker compose version >/dev/null 2>&1 \
    || die "plugin « docker compose » (v2) introuvable : voir docs/deploiement/apps-dev.md, section Docker." 2
}

# container_state <nom> : affiche « running/healthy », « running/sans-healthcheck », « absent »...
container_state() {
  docker inspect --format '{{.State.Status}}/{{if .State.Health}}{{.State.Health.Status}}{{else}}sans-healthcheck{{end}}' \
    "$1" 2>/dev/null || echo absent
}

# Le serveur PostgreSQL mutualisé tourne (et est sain s'il a un healthcheck).
require_pg_running() {
  local st
  st=$(container_state "$PG_CONTAINER")
  case "$st" in
    running/healthy) ok "conteneur $PG_CONTAINER : $st" ;;
    running/sans-healthcheck) warn "conteneur $PG_CONTAINER en marche, sans healthcheck" ;;
    *) die "conteneur $PG_CONTAINER : $st (attendu running/healthy). Ces scripts ne le démarrent pas." 3 ;;
  esac
}

# file_mode <chemin> : droits en octal (600, 700...).
file_mode() { stat -c '%a' "$1"; }

# --- Fichier de secrets -------------------------------------------------------------------
# env_get <CLE> : écrit la valeur (guillemets englobants retirés) sur la sortie standard.
# À utiliser UNIQUEMENT dans une substitution v=$(env_get CLE) : ne jamais l'afficher.
env_get() {
  local key=$1 line val='' found=1
  [ -r "$ENV_FILE" ] || return 1
  while IFS= read -r line || [ -n "$line" ]; do
    line=${line%$'\r'}
    case "$line" in
      "$key="*)
        val=${line#*=}
        found=0
        ;;
    esac
  done <"$ENV_FILE"
  [ "$found" -eq 0 ] || return 1
  case "$val" in
    \"*\" | \'*\') val=${val:1:${#val}-2} ;;
  esac
  printf '%s' "$val"
}

# env_placeholders : nombre de valeurs encore au format <a-remplacer> du modèle .dist.
env_placeholders() {
  grep -cE '^[A-Z0-9_]+=.*<[^>]+>' "$ENV_FILE" 2>/dev/null || true
}

# urldecode <chaîne> : décodage des séquences %XX (sans interpréter les antislashs).
urldecode() {
  local s=$1 out='' c
  while [ -n "$s" ]; do
    if [[ $s =~ ^%([0-9A-Fa-f]{2}) ]]; then
      printf -v c '%b' "\\x${BASH_REMATCH[1]}"
      out+=$c
      s=${s:3}
    else
      out+=${s:0:1}
      s=${s:1}
    fi
  done
  printf '%s' "$out"
}

# parse_database_url : lit DATABASE_URL dans ENV_FILE et remplit DBURL_USER, DBURL_PASS (décodé),
# DBURL_HOST, DBURL_PORT, DBURL_NAME. Les « %% » (échappement de %env(resolve:)% de Symfony) sont
# ramenés à « % » avant le décodage, comme bin/pg-common.sh. N'affiche rien.
parse_database_url() {
  local url re='^postgres(ql)?://([^:@/]+):([^@/]*)@([^:/?]+)(:([0-9]+))?/([^?]+)'
  url=$(env_get DATABASE_URL) || die "DATABASE_URL absente de $ENV_FILE" 2
  url=${url//%%/%}
  [[ $url =~ $re ]] || die "DATABASE_URL de $ENV_FILE illisible (attendu postgresql://role:motdepasse@hote:port/base?...)." 2
  DBURL_USER=$(urldecode "${BASH_REMATCH[2]}")
  DBURL_PASS=$(urldecode "${BASH_REMATCH[3]}")
  DBURL_HOST=${BASH_REMATCH[4]}
  DBURL_PORT=${BASH_REMATCH[6]:-5432}
  DBURL_NAME=${BASH_REMATCH[7]}
  url=''
  [ -n "$DBURL_PASS" ] || die "mot de passe vide dans DATABASE_URL" 2
  case "$DBURL_PASS" in
    *'<'*'>'*) die "DATABASE_URL contient encore le modèle <...> : remplir $ENV_FILE" 2 ;;
  esac
}

# --- PostgreSQL (dans le conteneur mutualisé) ---------------------------------------------
# pg_psql [args psql...] : psql du conteneur en superutilisateur, socket local ; SQL sur stdin.
pg_psql() {
  # shellcheck disable=SC2016 # $PG_SU et $POSTGRES_USER sont développés DANS le conteneur
  docker exec -i --env PG_SU="$PG_SUPERUSER" "$PG_CONTAINER" \
    sh -c 'exec psql -X -q -v ON_ERROR_STOP=1 -U "${PG_SU:-${POSTGRES_USER:-postgres}}" "$@"' psql "$@"
}

# pg_scalar <base> <requête> : une valeur, sans en-tête.
pg_scalar() {
  printf '%s\n' "$2" | pg_psql -At -d "$1"
}
