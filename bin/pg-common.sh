#!/bin/sh
# Fonctions communes à bin/pg-export.sh et bin/pg-import.sh (à sourcer, ne pas exécuter).
# Lit DATABASE_URL : variable d'environnement si définie, sinon .env puis .env.local (Dotenv Symfony).
# Le mot de passe ne passe JAMAIS par un argument de commande : seulement PGPASSWORD dans l'environnement
# des processus pg_dump / pg_restore / psql.

ROOT=$(cd "$(dirname "$0")/.." && pwd)

pg_load_url() {
  PG=$(cd "$ROOT" && php -r '
$url = getenv("DATABASE_URL");
if (false === $url || "" === $url) {
    require "vendor/autoload.php";
    $d = new Symfony\Component\Dotenv\Dotenv();
    $d->bootEnv(".env");
    $url = $_ENV["DATABASE_URL"] ?? $_SERVER["DATABASE_URL"] ?? "";
}
// DATABASE_URL passe par %env(resolve:)% : "%%" y désigne un "%" littéral.
$url = str_replace("%%", "%", $url);
$u = parse_url($url);
if (!is_array($u) || !in_array($u["scheme"] ?? "", ["postgresql", "postgres", "pgsql"], true) || !isset($u["host"], $u["path"])) {
    exit(1);
}
echo $u["host"], "\n", $u["port"] ?? 5432, "\n", ltrim($u["path"], "/"), "\n", rawurldecode($u["user"] ?? ""), "\n", rawurldecode($u["pass"] ?? ""), "\n";
') || { echo "ERREUR : DATABASE_URL absente ou ne désigne pas une base PostgreSQL" >&2; exit 2; }
  PGHOST=$(echo "$PG" | sed -n 1p)
  PGPORT=$(echo "$PG" | sed -n 2p)
  PGDATABASE=$(echo "$PG" | sed -n 3p)
  PGUSER=$(echo "$PG" | sed -n 4p)
  PGPASSWORD=$(echo "$PG" | sed -n 5p)
  PG=
  export PGHOST PGPORT PGDATABASE PGUSER PGPASSWORD
}
