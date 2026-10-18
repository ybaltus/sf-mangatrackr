#!/bin/sh
# Crée le rôle applicatif (non superutilisateur) et la base principale, dont il est propriétaire,
# en collation ICU "fr" (ADR 0012, D5).
#
# Exécuté par l'image PostgreSQL uniquement à la PREMIÈRE création du volume de données.
# Pour un volume existant :
#   docker compose exec postgres sh /docker-entrypoint-initdb.d/00-create-app-role-and-database.sh
# (le script est idempotent).
#
# Droits du rôle applicatif :
# - propriétaire de ses bases : peut y faire CREATE EXTENSION unaccent (extension "trusted"
#   depuis PostgreSQL 13, il suffit du droit CREATE sur la base, que le propriétaire détient ;
#   depuis PostgreSQL 15 le schéma public appartient à pg_database_owner, donc au propriétaire) ;
# - CREATEDB : nécessaire en développement pour `make test` (doctrine:database:drop/create en
#   environnement test). À ne pas donner en production.
set -eu

: "${APP_DB_NAME:?APP_DB_NAME manquant}"
: "${APP_DB_USER:?APP_DB_USER manquant}"
: "${APP_DB_PASSWORD:?APP_DB_PASSWORD manquant}"

psql -v ON_ERROR_STOP=1 --no-psqlrc \
    --username "${POSTGRES_USER:-postgres}" --dbname postgres \
    -v app_db="${APP_DB_NAME}" \
    -v app_user="${APP_DB_USER}" \
    -v app_password="${APP_DB_PASSWORD}" <<'SQL'
SELECT format('CREATE ROLE %I LOGIN CREATEDB PASSWORD %L', :'app_user', :'app_password')
WHERE NOT EXISTS (SELECT FROM pg_roles WHERE rolname = :'app_user')
\gexec

SELECT format(
    'CREATE DATABASE %I OWNER %I TEMPLATE template0 ENCODING %L LOCALE_PROVIDER icu ICU_LOCALE %L',
    :'app_db', :'app_user', 'UTF8', 'fr'
)
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = :'app_db')
\gexec
SQL

echo "Rôle ${APP_DB_USER} et base ${APP_DB_NAME} prêts."
