#!/bin/sh
# Import d'un dump produit par bin/pg-export.sh dans la base désignée par DATABASE_URL.
#
# Usage : CONFIRM_TARGET=<hôte>/<base> sh bin/pg-import.sh <fichier.dump>
#         make pg-import DUMP=ressources/<fichier>.dump CONFIRM_TARGET=<hôte>/<base>
#   Remplacement d'une base non vide (DÉTRUIT son contenu) : ajouter IMPORT_REPLACE=1.
#
# Prérequis (voir docs/chantiers/migration-postgresql/homelab.md) :
#   - base existante, créée avec ICU fr :
#       CREATE DATABASE <base> OWNER <role> TEMPLATE template0 ENCODING 'UTF8'
#         LOCALE_PROVIDER icu ICU_LOCALE 'fr';
#     (LC_COLLATE / LC_CTYPE libc éventuels : ceux du cluster par défaut ; en dev 'en_US.utf8')
#   - rôle applicatif propriétaire de la base ; DATABASE_URL pointant dessus ;
#   - le dump contient CREATE EXTENSION unaccent (extension "trusted" depuis PG 13, le propriétaire
#     de la base suffit), les tables, les données, les séquences IDENTITY (setval) et
#     doctrine_migration_versions (baseline présente => doctrine:migrations:migrate est un no-op).
#   - client pg_restore >= version de pg_dump (18) ; serveur cible 17 ou 18 recommandé (pg_dump 17+ émet
#     SET transaction_timeout, inconnu des serveurs <= 16).
#   - ne PAS lancer doctrine:migrations:migrate avant l'import (la baseline créerait le schéma, conflit).
# Restauration atomique (--single-transaction --exit-on-error) : au moindre échec, rien n'est modifié.
#
# Code de sortie : 0 ok ; 2 usage ; 5 cible refusée (confirmation, base _test, base non vide) ; 3 échec.
set -eu
. "$(dirname "$0")/pg-common.sh"
[ $# -eq 1 ] || { echo "Usage : CONFIRM_TARGET=<hôte>/<base> sh bin/pg-import.sh <fichier.dump>" >&2; exit 2; }
DUMP=$1
[ -f "$DUMP" ] || { echo "ERREUR : fichier introuvable : $DUMP" >&2; exit 2; }
command -v pg_restore >/dev/null || { echo "ERREUR : pg_restore introuvable (paquet postgresql-client)" >&2; exit 2; }
pg_load_url

echo "Cible : $PGHOST:$PGPORT/$PGDATABASE"
case "$PGDATABASE" in
  *_test) echo "ERREUR : base de test refusée" >&2; exit 5 ;;
esac
if [ "${CONFIRM_TARGET:-}" != "$PGHOST/$PGDATABASE" ]; then
  echo "ERREUR : relancer avec CONFIRM_TARGET=$PGHOST/$PGDATABASE si cette cible est la bonne." >&2
  exit 5
fi

NB=$(psql -XAtc "SELECT count(*) FROM pg_tables WHERE schemaname = 'public'") \
  || { echo "ERREUR : connexion impossible (base créée ? rôle et mot de passe ?)" >&2; exit 3; }
OPTS="--no-owner --no-privileges --exit-on-error --single-transaction"
if [ "$NB" -gt 0 ]; then
  if [ "${IMPORT_REPLACE:-}" != "1" ]; then
    echo "ERREUR : la cible contient déjà $NB table(s). IMPORT_REPLACE=1 pour la remplacer (contenu détruit)." >&2
    exit 5
  fi
  OPTS="$OPTS --clean --if-exists"
fi

echo "Serveur cible : $(psql -XAtc 'SHOW server_version') ; restauration en cours..."
# shellcheck disable=SC2086
pg_restore $OPTS --dbname="$PGDATABASE" "$DUMP" || { echo "ERREUR : pg_restore a échoué (transaction annulée)" >&2; exit 3; }
echo "Import terminé. Suite : php bin/console doctrine:migrations:status (baseline appliquée, rien à migrer), puis cache:clear."
