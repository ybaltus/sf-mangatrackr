#!/usr/bin/env bash
# Copie la sauvegarde la plus récente de la base vers le NAS (rsync sur SSH, clé dédiée).
#
# Usage : deploy/backup-offsite.sh [--dry-run] [--help]
#   OFFSITE_REMOTE   destination rsync, ex. homelab-backup@192.168.1.26:./ (obligatoire ;
#                    sans elle le script refuse de tourner, la copie reste à la charge de l'utilisateur)
#   OFFSITE_SSH_KEY  clé privée dédiée (défaut $SECRETS_DIR/backup-nas.key, 600)
#   OFFSITE_KNOWN_HOSTS  fichier known_hosts dédié (défaut $SECRETS_DIR/backup-nas.known_hosts)
#
# - Envoie uniquement le dernier backup_<base>_AAAAMMJJ-HHMMSS.dump produit par deploy/backup.sh
#   (motif strict). La clé est limitée côté NAS à un dossier en écriture seule (dépôt proxy-setup,
#   BACKUP-NAS.md) : ce script ne peut ni lister, ni lire, ni supprimer ce qui s'y trouve.
#   La rotation côté NAS se fait sur le NAS (cron).
# - Données personnelles : canal chiffré (SSH) uniquement, jamais en clair. Rien n'est affiché des
#   secrets ; le fichier de clé n'est lu que par ssh.
#
# Code de sortie : 0 ok ; 2 usage/configuration ; 3 échec du transfert.
set -euo pipefail
# shellcheck source=deploy/lib.sh
. "$(dirname "$0")/lib.sh"

: "${OFFSITE_REMOTE:=}"
: "${OFFSITE_SSH_KEY:=$SECRETS_DIR/backup-nas.key}"
: "${OFFSITE_KNOWN_HOSTS:=$SECRETS_DIR/backup-nas.known_hosts}"
BACKUP_PREFIX="backup_${DB_NAME}_"

main() {
  while [ $# -gt 0 ]; do
    case "$1" in
      --dry-run) DRY_RUN=1 ;;
      -h | --help) usage; exit 0 ;;
      *) die "option inconnue : $1 (voir --help)" 2 ;;
    esac
    shift
  done
  [ -n "$OFFSITE_REMOTE" ] || die "OFFSITE_REMOTE non défini (ex. homelab-backup@192.168.1.26:./)" 2
  require_cmd rsync ssh
  [ -r "$OFFSITE_SSH_KEY" ] || die "clé SSH introuvable ou illisible : $OFFSITE_SSH_KEY" 2
  [ "$(file_mode "$OFFSITE_SSH_KEY")" = 600 ] || die "$OFFSITE_SSH_KEY doit être en 600" 2
  [ -r "$OFFSITE_KNOWN_HOSTS" ] || die "known_hosts introuvable : $OFFSITE_KNOWN_HOSTS (voir BACKUP-NAS.md)" 2

  local latest='' f
  for f in "$BACKUP_DIR/$BACKUP_PREFIX"[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]-[0-9][0-9][0-9][0-9][0-9][0-9].dump; do
    [ -e "$f" ] && latest="$f"
  done
  [ -n "$latest" ] || die "aucune sauvegarde ${BACKUP_PREFIX}*.dump dans $BACKUP_DIR (lancer deploy/backup.sh)" 2

  [ "$DRY_RUN" = 0 ] || log "MODE --dry-run : aucune copie"
  log "Copie de $(basename "$latest") vers $OFFSITE_REMOTE"
  run rsync -t --chmod=F600 \
    -e "ssh -i $OFFSITE_SSH_KEY -o BatchMode=yes -o IdentitiesOnly=yes -o StrictHostKeyChecking=yes -o UserKnownHostsFile=$OFFSITE_KNOWN_HOSTS" \
    "$latest" "$OFFSITE_REMOTE" || die "transfert rsync échoué" 3
  [ "$DRY_RUN" = 1 ] || ok "$(basename "$latest") envoyé"
}

main "$@"
