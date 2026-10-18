#!/bin/sh
# Procédure rejouable MariaDB (_import) -> PostgreSQL : transfert, inventaires, parité.
#
# Usage : MARIADB_PASSWORD=... CONFIRM_TARGET=<hôte>/<base> ./rejouer-transfert.sh <dossier-de-sortie> [options du transfert...]
#   La cible est celle de DATABASE_URL (schéma PostgreSQL, jamais une base _test).
#   Ce script DÉTRUIT le contenu de la cible. Il ne se confirme pas lui-même : l'appelant doit fournir
#   CONFIRM_TARGET=<hôte>/<base>, identique caractère pour caractère à la cible réelle de DATABASE_URL
#   (dev : CONFIRM_TARGET=postgres/sf_mangatrackr_app). Sinon refus, code 5, rien n'est touché.
#   Hôte et base attendus : TRANSFER_EXPECTED_HOST / TRANSFER_EXPECTED_DB (défaut postgres /
#   sf_mangatrackr_app) ; toute autre cible (production, répétition) exige de les définir EXPLICITEMENT.
#   Les options après le dossier sont transmises à transfert-mariadb-pgsql.php.
#   Variables facultatives : SOURCE_DSN, MARIADB_USER (défaut : base _import, utilisateur mangatrackr).
#
# Code de sortie :
#   0  transfert validé ET parité complète des tables de données (doctrine_migration_versions
#      est exclue de la comparaison mais le script vérifie que la baseline Version20261018145659
#      est présente côté PostgreSQL), séquences = MAX(id)+1
#   1  écart réel de parité (contenu, lignes, colonnes, séquences, baseline absente)
#   2  erreur d'usage (variable manquante, DATABASE_URL non PostgreSQL)
#   3  transfert ou connexion en échec, exception inattendue (ROLLBACK : cible inchangée)
#   4  COMMIT fait, séquences à recaler
#   5  cible refusée par le garde-fou (hôte, base, _test, confirmation absente ou différente)
set -eu
DIR=$(dirname "$0")
BASELINE=Version20261018145659
[ $# -ge 1 ] || { echo "ERREUR : dossier de sortie (inventaires JSON) requis" >&2; exit 2; }
OUT=$1
shift
[ -n "${MARIADB_PASSWORD:-}" ] || { echo "ERREUR : MARIADB_PASSWORD requis" >&2; exit 2; }
[ -n "${DATABASE_URL:-}" ] || { echo "ERREUR : DATABASE_URL requis" >&2; exit 2; }
mkdir -p "$OUT"

SOURCE_DSN=${SOURCE_DSN:-mysql:host=database;port=3306;dbname=sf_mangatrackr_app_import}
MARIADB_USER=${MARIADB_USER:-mangatrackr}

# Une seule lecture de DATABASE_URL (le mot de passe ne passe jamais par un argument de commande)
PG=$(php -r '
$u = parse_url((string) getenv("DATABASE_URL"));
if (!is_array($u) || !in_array($u["scheme"] ?? "", ["postgresql", "postgres", "pgsql"], true) || !isset($u["host"], $u["path"])) { exit(1); }
echo $u["host"], "\n", $u["port"] ?? 5432, "\n", ltrim($u["path"], "/"), "\n", rawurldecode($u["user"] ?? ""), "\n", rawurldecode($u["pass"] ?? ""), "\n";
') || { echo "ERREUR : DATABASE_URL ne désigne pas une base PostgreSQL" >&2; exit 2; }
PGHOST=$(echo "$PG" | sed -n 1p)
PGPORT=$(echo "$PG" | sed -n 2p)
PGDB=$(echo "$PG" | sed -n 3p)
PGUSER=$(echo "$PG" | sed -n 4p)
PGPASS=$(echo "$PG" | sed -n 5p)

# Cible affichée et confirmation exigée AVANT toute connexion ; le PHP revérifie hôte et base attendus.
echo "Cible réelle : $PGHOST:$PGPORT/$PGDB (sera vidée puis rechargée)"
if [ "${CONFIRM_TARGET:-}" != "$PGHOST/$PGDB" ]; then
  echo "ERREUR : confirmation absente ou différente. Relancer avec CONFIRM_TARGET=$PGHOST/$PGDB si cette cible est bien la bonne." >&2
  exit 5
fi
export CONFIRM_TARGET

# Transfert (échec : code 2/3/4/5 propagé par set -e). --yes/--replace-existing ne valent qu'après la confirmation ci-dessus.
php "$DIR/transfert-mariadb-pgsql.php" --yes --replace-existing --source-dsn="$SOURCE_DSN" --source-user="$MARIADB_USER" "$@"

INVENTAIRE_DB_PASSWORD="$MARIADB_PASSWORD" php "$DIR/inventaire.php" \
  --dsn="$SOURCE_DSN" --user="$MARIADB_USER" --out="$OUT/inventaire-mariadb.json" >/dev/null
INVENTAIRE_DB_PASSWORD="$PGPASS" php "$DIR/inventaire.php" \
  --dsn="pgsql:host=$PGHOST;port=$PGPORT;dbname=$PGDB" --user="$PGUSER" \
  --out="$OUT/inventaire-pgsql.json" >/dev/null

# Parité des 22 tables de données ; doctrine_migration_versions exclue mais baseline contrôlée.
# Deux passes : tolérante (--compare) puis stricte (valeurs brutes, 3 tables de jointure incluses).
RC=0
php "$DIR/inventaire.php" --compare="$OUT/inventaire-mariadb.json,$OUT/inventaire-pgsql.json" \
  --rename=user:app_user --ignore=doctrine_migration_versions --require-baseline="$BASELINE" || RC=$?
[ "$RC" -le 1 ] || exit "$RC"

echo
echo "--- Passe stricte (valeurs brutes) ---"
INVENTAIRE_DB_PASSWORD="$MARIADB_PASSWORD" php "$DIR/inventaire.php" --strict \
  --dsn="$SOURCE_DSN" --user="$MARIADB_USER" --out="$OUT/inventaire-mariadb-strict.json" >/dev/null
INVENTAIRE_DB_PASSWORD="$PGPASS" php "$DIR/inventaire.php" --strict \
  --dsn="pgsql:host=$PGHOST;port=$PGPORT;dbname=$PGDB" --user="$PGUSER" \
  --out="$OUT/inventaire-pgsql-strict.json" >/dev/null
RC2=0
php "$DIR/inventaire.php" --compare="$OUT/inventaire-mariadb-strict.json,$OUT/inventaire-pgsql-strict.json" \
  --rename=user:app_user --ignore=doctrine_migration_versions --require-baseline="$BASELINE" || RC2=$?
[ "$RC2" -le 1 ] || exit "$RC2"
[ "$RC" -eq 0 ] && [ "$RC2" -eq 0 ] && exit 0
exit 1
