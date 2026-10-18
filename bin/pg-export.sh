#!/bin/sh
# Export de la base PostgreSQL désignée par DATABASE_URL (lecture seule) vers ressources/.
#
# Usage : make pg-export   (ou sh bin/pg-export.sh)
#
# Format : pg_dump -Fc (archive compressée) avec --no-owner --no-privileges : le rôle applicatif du
# serveur cible peut différer. Fichier : ressources/mangatrackr_<base>_<AAAAMMJJ-HHMMSS>.dump, droits 600.
#
# ATTENTION : le dump contient des DONNÉES PERSONNELLES (emails, hash de mots de passe, suivi de
# lecture). Ne jamais le commiter, l'envoyer par un canal non chiffré ni l'afficher. Le script refuse
# d'écrire si le fichier n'est pas ignoré par git.
#
# Code de sortie : 0 ok ; 2 usage/configuration ; 3 échec de pg_dump (aucun fichier laissé).
set -eu
. "$(dirname "$0")/pg-common.sh"
pg_load_url
command -v pg_dump >/dev/null || { echo "ERREUR : pg_dump introuvable (paquet postgresql-client)" >&2; exit 2; }

OUTDIR="$ROOT/ressources"
mkdir -p "$OUTDIR"
FILE="$OUTDIR/mangatrackr_${PGDATABASE}_$(date +%Y%m%d-%H%M%S).dump"

if git -C "$ROOT" rev-parse --git-dir >/dev/null 2>&1 && ! git -C "$ROOT" check-ignore -q "$FILE"; then
  echo "ERREUR : $FILE n'est pas ignoré par git (ajouter /ressources/*.dump au .gitignore). Export refusé." >&2
  exit 2
fi

echo "Source : $PGHOST:$PGPORT/$PGDATABASE (lecture seule)"
umask 077
TMP="$FILE.part"
trap 'rm -f "$TMP"' EXIT
pg_dump --format=custom --no-owner --no-privileges --file="$TMP" || { echo "ERREUR : pg_dump a échoué" >&2; exit 3; }
chmod 600 "$TMP"
mv "$TMP" "$FILE"
trap - EXIT

echo "Dump écrit : $FILE ($(wc -c < "$FILE") octets, droits 600)"
echo "AVERTISSEMENT : données personnelles. Ne pas commiter, ne pas afficher, transférer par canal chiffré (scp/rsync sur SSH)."
echo "Serveur source : $(psql -XAtc 'SHOW server_version')"
