#!/usr/bin/env bash
# Création idempotente du rôle et de la base de MangaTrackr dans le PostgreSQL 18 mutualisé,
# avec restauration facultative d'un dump (réinstallation de la VM).
#
# Usage : deploy/init-database.sh [--dry-run] [--help]
#         CONFIRM_TARGET=<conteneur>/<base> deploy/init-database.sh --restore <fichier.dump> [--dry-run]
#
# - Le conteneur $PG_CONTAINER doit tourner : il n'est jamais démarré ni modifié ici.
# - Rôle $DB_ROLE (LOGIN) et base $DB_NAME créés UNIQUEMENT s'ils manquent :
#     CREATE DATABASE ... OWNER <rôle> TEMPLATE template0 ENCODING 'UTF8'
#       LOCALE_PROVIDER icu ICU_LOCALE 'fr';
#   Un rôle existant n'est pas modifié (mot de passe compris).
# - Le mot de passe du rôle est lu dans le DATABASE_URL de $ENV_FILE (« %% » puis %XX décodés)
#   et transmis à psql par l'entrée standard : jamais en argument, jamais affiché, et la
#   journalisation des requêtes est coupée pour la session.
# - Connexion en superutilisateur du conteneur par le socket local (PG_SUPERUSER, défaut :
#   $POSTGRES_USER du conteneur, sinon postgres).
# - --restore : exige CONFIRM_TARGET=$PG_CONTAINER/$DB_NAME (vérifié avant toute création),
#   refuse une base contenant déjà des tables (même règle que bin/pg-import.sh ; aucun
#   remplacement possible ici). Le dump (pg_dump -Fc,
#   par exemple de bin/pg-export.sh ou deploy/backup.sh) est copié dans un fichier temporaire du
#   conteneur (supprimé ensuite) puis restauré par pg_restore 18 en une seule transaction
#   (--single-transaction --exit-on-error), objets attribués au rôle applicatif (--role).
#   Ne pas lancer doctrine:migrations:migrate avant : la baseline est dans le dump.
#
# Code de sortie : 0 ok ; 2 usage/configuration ; 3 échec PostgreSQL ; 5 cible refusée.
set -euo pipefail
# shellcheck source=deploy/lib.sh
. "$(dirname "$0")/lib.sh"

DUMP=''
TMP_IN_CONTAINER=''

cleanup() {
  if [ -n "$TMP_IN_CONTAINER" ]; then
    docker exec "$PG_CONTAINER" rm -f "$TMP_IN_CONTAINER" >/dev/null 2>&1 \
      || warn "fichier temporaire non supprimé dans $PG_CONTAINER : $TMP_IN_CONTAINER"
  fi
}

# sql_literal <texte> : littéral SQL ('...' avec apostrophes doublées ; standard_conforming_strings).
sql_literal() { printf "'%s'" "${1//\'/\'\'}"; }

check_config() {
  [ -f "$ENV_FILE" ] || die "fichier de secrets absent : $ENV_FILE (deploy/install-vm.sh)" 2
  parse_database_url
  [ "$DBURL_USER" = "$DB_ROLE" ] || die "DATABASE_URL utilise le rôle « $DBURL_USER », attendu « $DB_ROLE » (DB_ROLE)" 2
  [ "$DBURL_NAME" = "$DB_NAME" ] || die "DATABASE_URL désigne la base « $DBURL_NAME », attendue « $DB_NAME » (DB_NAME)" 2
  [ "$DBURL_HOST" = "$PG_CONTAINER" ] \
    || warn "DATABASE_URL vise l'hôte « $DBURL_HOST » et non le conteneur « $PG_CONTAINER » : vérifier la cible"
  ok "DATABASE_URL : rôle $DBURL_USER, base $DBURL_NAME, hôte $DBURL_HOST:$DBURL_PORT"
}

ensure_role() {
  local n
  n=$(pg_scalar postgres "SELECT count(*) FROM pg_roles WHERE rolname = '$DB_ROLE'") \
    || die "requête impossible sur $PG_CONTAINER (superutilisateur ? PG_SUPERUSER)" 3
  if [ "$n" = 1 ]; then
    ok "rôle $DB_ROLE déjà en place (non modifié)"
    return 0
  fi
  log "création du rôle $DB_ROLE (LOGIN, mot de passe de DATABASE_URL)"
  if [ "$DRY_RUN" = 1 ]; then
    echo "[dry-run] psql (conteneur $PG_CONTAINER) : CREATE ROLE $DB_ROLE LOGIN PASSWORD '***'"
    return 0
  fi
  # SQL sur stdin : le mot de passe n'apparaît ni dans ps, ni dans les journaux du serveur.
  {
    echo "SET log_statement = 'none';"
    echo "SET log_min_duration_statement = -1;"
    echo "SET log_min_error_statement = 'panic';"
    printf 'CREATE ROLE %s LOGIN PASSWORD %s;\n' "$DB_ROLE" "$(sql_literal "$DBURL_PASS")"
  } | pg_psql -d postgres || die "création du rôle $DB_ROLE impossible" 3
  ok "rôle $DB_ROLE créé"
}

ensure_database() {
  local n
  n=$(pg_scalar postgres "SELECT count(*) FROM pg_database WHERE datname = '$DB_NAME'") \
    || die "requête impossible sur $PG_CONTAINER" 3
  if [ "$n" = 1 ]; then
    ok "base $DB_NAME déjà en place (propriétaire : $(pg_scalar postgres "SELECT pg_get_userbyid(datdba) FROM pg_database WHERE datname = '$DB_NAME'"))"
    return 0
  fi
  log "création de la base $DB_NAME (ICU fr, UTF8, template0, propriétaire $DB_ROLE)"
  local sql="CREATE DATABASE $DB_NAME OWNER $DB_ROLE TEMPLATE template0 ENCODING 'UTF8' LOCALE_PROVIDER icu ICU_LOCALE 'fr';"
  if [ "$DRY_RUN" = 1 ]; then
    echo "[dry-run] psql (conteneur $PG_CONTAINER) : $sql"
    return 0
  fi
  printf '%s\n' "$sql" | pg_psql -d postgres || die "création de la base $DB_NAME impossible" 3
  ok "base $DB_NAME créée"
}

# Contrôle de la cible du --restore, fait AVANT toute création de rôle ou de base.
check_restore_target() {
  local target="$PG_CONTAINER/$DB_NAME"
  case "$DB_NAME" in
    *_test) die "base de test refusée" 5 ;;
  esac
  if [ "${CONFIRM_TARGET:-}" != "$target" ]; then
    die "relancer avec CONFIRM_TARGET=$target si cette cible est la bonne (rien n'a été créé)." 5
  fi
  ok "cible confirmée : $target"
}

restore_dump() {
  local target="$PG_CONTAINER/$DB_NAME" n
  log "Restauration de $DUMP dans $target"
  if [ "$DRY_RUN" = 1 ] && [ "$(pg_scalar postgres "SELECT count(*) FROM pg_database WHERE datname = '$DB_NAME'")" != 1 ]; then
    n=0
  else
    n=$(pg_scalar "$DB_NAME" "SELECT count(*) FROM pg_tables WHERE schemaname = 'public'") \
      || die "connexion impossible à la base $DB_NAME" 3
  fi
  [ "$n" = 0 ] || die "la base $DB_NAME contient déjà $n table(s) : restauration refusée (aucun remplacement automatique)." 5
  ok "base vide : restauration autorisée"

  local tmp="/tmp/mangatrackr-restore-$$.dump"
  # Supprimé par cleanup (trap EXIT), même en cas d'échec ; rien n'est copié en --dry-run.
  [ "$DRY_RUN" = 1 ] || TMP_IN_CONTAINER=$tmp
  run docker cp "$DUMP" "$PG_CONTAINER:$tmp"
  if [ "$DRY_RUN" = 0 ]; then
    docker exec "$PG_CONTAINER" chmod 600 "$TMP_IN_CONTAINER"
    docker exec "$PG_CONTAINER" pg_restore --list "$TMP_IN_CONTAINER" >/dev/null \
      || die "archive illisible par pg_restore du conteneur : $DUMP" 3
    ok "archive lisible (serveur $(pg_scalar postgres 'SHOW server_version'))"
  fi
  # shellcheck disable=SC2016 # variables développées dans le conteneur
  run docker exec --env PG_SU="$PG_SUPERUSER" "$PG_CONTAINER" sh -c \
    'exec pg_restore -U "${PG_SU:-${POSTGRES_USER:-postgres}}" --dbname="$1" --role="$2" --no-owner --no-privileges --exit-on-error --single-transaction "$3"' \
    pg_restore "$DB_NAME" "$DB_ROLE" "$tmp" \
    || die "pg_restore a échoué (transaction annulée, base inchangée)" 3
  [ "$DRY_RUN" = 1 ] || ok "restauration terminée ($(pg_scalar "$DB_NAME" "SELECT count(*) FROM pg_tables WHERE schemaname = 'public'") tables)"
  log "Suite : deploy/deploy.sh (migrations:status doit indiquer 0 migration en attente), puis deploy/check.sh."
}

main() {
  while [ $# -gt 0 ]; do
    case "$1" in
      --dry-run) DRY_RUN=1 ;;
      --restore)
        [ $# -ge 2 ] || die "--restore exige un fichier .dump" 2
        DUMP=$2
        shift
        ;;
      -h | --help) usage; exit 0 ;;
      *) die "option inconnue : $1 (voir --help)" 2 ;;
    esac
    shift
  done
  [ "$DRY_RUN" = 0 ] || log "MODE --dry-run : aucune modification"
  if [ -n "$DUMP" ]; then
    if [ ! -f "$DUMP" ] || [ ! -r "$DUMP" ]; then
      die "dump introuvable ou illisible : $DUMP" 2
    fi
  fi
  trap cleanup EXIT
  require_docker
  log "Contrôles"
  require_pg_running
  check_config
  [ -z "$DUMP" ] || check_restore_target
  log "Rôle et base dans $PG_CONTAINER"
  ensure_role
  ensure_database
  [ -z "$DUMP" ] || restore_dump
  DBURL_PASS=''
}

main "$@"
