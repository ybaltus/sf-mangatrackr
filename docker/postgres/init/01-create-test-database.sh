#!/bin/sh
# Crée la base de test (<base>_test, suffixe ajouté par doctrine.yaml en environnement "test"),
# propriété du rôle applicatif, en collation ICU "fr" (ADR 0012, D5).
# Équivalent de docker/mariadb/init/01-create-test-database.sh.
# Le rôle applicatif est créé par 00-create-app-role-and-database.sh.
#
# Exécuté par l'image PostgreSQL uniquement à la PREMIÈRE création du volume de données.
# Pour un volume existant (script idempotent) :
#   docker compose exec postgres sh /docker-entrypoint-initdb.d/01-create-test-database.sh
#
# L'extension unaccent n'est pas créée ici : elle l'est par la migration baseline PostgreSQL
# (T4.4), ce que le propriétaire de la base peut faire (extension "trusted").
set -eu

: "${APP_DB_NAME:?APP_DB_NAME manquant}"
: "${APP_DB_USER:?APP_DB_USER manquant}"

TEST_DATABASE="${APP_DB_NAME}_test"

psql -v ON_ERROR_STOP=1 --no-psqlrc \
    --username "${POSTGRES_USER:-postgres}" --dbname postgres \
    -v test_db="${TEST_DATABASE}" \
    -v app_user="${APP_DB_USER}" <<'SQL'
SELECT format(
    'CREATE DATABASE %I OWNER %I TEMPLATE template0 ENCODING %L LOCALE_PROVIDER icu ICU_LOCALE %L',
    :'test_db', :'app_user', 'UTF8', 'fr'
)
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = :'test_db')
\gexec
SQL

echo "Base de test ${TEST_DATABASE} prête pour l'utilisateur ${APP_DB_USER}."
