#!/bin/sh
# Crée la base de test (<base>_test, suffixe ajouté par doctrine.yaml en environnement "test")
# et donne à l'utilisateur applicatif les droits dessus.
#
# Exécuté par l'image MariaDB uniquement à la PREMIÈRE création du volume de données.
# Pour un volume existant : voir docs/claude/contexte.md (section Environnement).
set -eu

TEST_DATABASE="${MYSQL_DATABASE}_test"

mariadb -uroot -p"${MARIADB_ROOT_PASSWORD}" <<SQL
CREATE DATABASE IF NOT EXISTS \`${TEST_DATABASE}\`;
GRANT ALL PRIVILEGES ON \`${TEST_DATABASE}\`.* TO '${MYSQL_USER}'@'%';
FLUSH PRIVILEGES;
SQL

echo "Base de test ${TEST_DATABASE} prête pour l'utilisateur ${MYSQL_USER}."
