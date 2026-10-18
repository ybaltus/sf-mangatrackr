#!/usr/bin/env bash
# Sauvegarde de la base de MangaTrackr (lecture seule pour la base) avec rotation.
#
# Usage : deploy/backup.sh [--dry-run] [--help]        (ou make prod-backup ARGS=--dry-run)
#   KEEP=<n>   nombre de sauvegardes conservées (défaut 7, les plus anciennes sont supprimées)
#
# - pg_dump -Fc --no-owner --no-privileges exécuté DANS le conteneur $PG_CONTAINER (version 18 du
#   serveur), sortie redirigée sur l'hôte vers $BACKUP_DIR/backup_<base>_AAAAMMJJ-HHMMSS.dump
#   (600, répertoire 700 ; jamais d'écrasement : un nom déjà pris dans la même seconde est refusé). Écriture dans un fichier .part renommé seulement après succès et contrôle de
#   l'archive (pg_restore --list) : jamais de sauvegarde tronquée sous le nom final.
# - Choix du rôle : superutilisateur du conteneur par le socket local (PG_SUPERUSER, défaut
#   $POSTGRES_USER du conteneur). Aucun mot de passe à manipuler, lecture garantie de tous les
#   objets ; --no-owner rend le dump restaurable sous le rôle applicatif (init-database.sh --restore).
# - Rotation : seuls les fichiers de ce script (backup_<base>_AAAAMMJJ-HHMMSS.dump, motif strict)
#   sont concernés ; la sauvegarde qui vient d'être écrite n'est jamais supprimée. Les autres
#   fichiers (dumps de make pg-export « mangatrackr_<base>_<date>.dump », copies manuelles...) ne
#   sont ni comptés ni supprimés.
# - ATTENTION : données personnelles (emails, hash de mots de passe). Ne jamais commiter, afficher
#   ni copier hors d'un canal chiffré.
#
# Code de sortie : 0 ok ; 2 usage/configuration ; 3 échec de pg_dump (aucun fichier laissé).
set -euo pipefail
# shellcheck source=deploy/lib.sh
. "$(dirname "$0")/lib.sh"

KEEP=${KEEP:-7}
PART=''
# Préfixe propre à backup.sh : aucun autre outil du dépôt ne produit ce nom.
BACKUP_PREFIX="backup_${DB_NAME}_"

cleanup() { [ -z "$PART" ] || rm -f "$PART"; }

# rotate <sauvegarde courante> : garde la courante + les KEEP-1 plus récentes de backup.sh.
rotate() {
  local current=$1 files=() f i excess
  for f in "$BACKUP_DIR/$BACKUP_PREFIX"[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]-[0-9][0-9][0-9][0-9][0-9][0-9].dump; do
    if [ -f "$f" ] && [ "$f" != "$current" ]; then
      files+=("$f")
    fi
  done
  # Noms horodatés AAAAMMJJ-HHMMSS : l'ordre lexicographique du glob est chronologique.
  # La courante (absente en --dry-run) compte toujours pour 1 dans KEEP.
  excess=$((${#files[@]} - (KEEP - 1)))
  if [ "$excess" -le 0 ]; then
    ok "rotation : $((${#files[@]} + 1)) sauvegarde(s) de backup.sh, limite $KEEP"
    return 0
  fi
  for ((i = 0; i < excess; i++)); do
    log "rotation : suppression de ${files[$i]}"
    run rm -f -- "${files[$i]}"
  done
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
  [[ $KEEP =~ ^[1-9][0-9]*$ ]] || die "KEEP doit être un entier >= 1 (reçu : $KEEP)" 2
  [ "$DRY_RUN" = 0 ] || log "MODE --dry-run : aucune modification"
  require_docker
  require_pg_running

  local file
  file="$BACKUP_DIR/$BACKUP_PREFIX$(date +%Y%m%d-%H%M%S).dump"
  if [ -e "$file" ] || [ -e "$file.part" ]; then
    # Deux sauvegardes dans la même seconde : attendre la suivante plutôt qu'écraser.
    sleep 1
    file="$BACKUP_DIR/$BACKUP_PREFIX$(date +%Y%m%d-%H%M%S).dump"
    if [ -e "$file" ] || [ -e "$file.part" ]; then
      die "$file existe déjà : sauvegarde refusée (aucun écrasement)" 3
    fi
  fi
  if [ ! -d "$BACKUP_DIR" ]; then
    run mkdir -p "$BACKUP_DIR"
    run chmod 700 "$BACKUP_DIR"
  elif [ "$(file_mode "$BACKUP_DIR")" != 700 ]; then
    warn "$BACKUP_DIR en $(file_mode "$BACKUP_DIR") : passage en 700"
    run chmod 700 "$BACKUP_DIR"
  fi

  log "Sauvegarde de $PG_CONTAINER/$DB_NAME vers $file"
  if [ "$DRY_RUN" = 1 ]; then
    echo "[dry-run] docker exec $PG_CONTAINER pg_dump -U <superutilisateur> --format=custom --no-owner --no-privileges $DB_NAME > $file.part"
    echo "[dry-run] contrôle pg_restore --list, chmod 600, mv $file.part $file"
  else
    umask 077
    PART="$file.part"
    trap cleanup EXIT
    # shellcheck disable=SC2016 # variables développées dans le conteneur
    docker exec --env PG_SU="$PG_SUPERUSER" "$PG_CONTAINER" sh -c \
      'exec pg_dump -U "${PG_SU:-${POSTGRES_USER:-postgres}}" --format=custom --no-owner --no-privileges --dbname="$1"' \
      pg_dump "$DB_NAME" >"$PART" || die "pg_dump a échoué (aucune sauvegarde écrite)" 3
    [ -s "$PART" ] || die "sauvegarde vide" 3
    docker exec -i "$PG_CONTAINER" pg_restore --list >/dev/null <"$PART" \
      || die "archive produite illisible par pg_restore" 3
    chmod 600 "$PART"
    [ ! -e "$file" ] || die "$file apparu pendant la sauvegarde : aucun écrasement" 3
    mv -- "$PART" "$file"
    PART=''
    ok "sauvegarde écrite : $file ($(wc -c <"$file") octets, 600)"
  fi
  rotate "$file"
}

main "$@"
