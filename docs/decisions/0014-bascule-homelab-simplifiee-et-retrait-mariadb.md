# ADR 0014 : bascule simplifiée sur le homelab et retrait de MariaDB

- Statut : Acceptée
- Date : 2026-10-18
- Remplace : [ADR 0013](0013-bascule-production-postgresql.md)
- Contexte lié : ADR 0012, chantier `migration-postgresql` (T7 rapport 034, T8 rapports 035-037),
  procédure [`homelab.md`](../chantiers/migration-postgresql/homelab.md)

## Contexte
L'ADR 0013 prévoyait une bascule complète de Hostinger (MariaDB) vers le homelab : fenêtre de
maintenance, export final de production, transfert par script PHP/DBAL, répétition générale et
retour arrière. Depuis, l'utilisateur a abandonné Hostinger. Les données réelles sont déjà
transférées et contrôlées dans la base PostgreSQL de dev (parité 22 tables, rapports 017 et 032),
et le homelab a PostgreSQL 18 et le dépôt git. Dans ce cadre, la fenêtre de maintenance, la
répétition et le retour arrière vers MariaDB ne servent plus à rien. Le service MariaDB de l'infra
du dépôt, gardé jusqu'ici comme source d'import, n'a plus d'usage non plus.

## Décision
- **Bascule par dump PostgreSQL** : `make pg-export` sur la machine de dev (`pg_dump -Fc --no-owner
  --no-privileges`), copie par SSH, `make pg-import` sur une base vide du homelab (garde-fou
  `CONFIRM_TARGET`). C'est l'utilisateur qui la fait, en suivant `homelab.md`. Restauration
  prouvée de PostgreSQL 18 vers PostgreSQL 18 (rapport 034).
- **Plus d'export depuis Hostinger** ; `plan-bascule.md` est conservé pour l'historique seulement.
- **MariaDB retiré de l'infra** (T8) : service compose `database` et volume `db-data`, `pdo_mysql`
  dans les images, `docker/mariadb/`, `MARIADB_VERSION` et exemples `mysql://`. PostgreSQL 18 est
  la seule base.
- **Outils de migration conservés comme archive** (skills `import-dump-mariadb` et
  `verif-donnees`, agents `migration-*`) : ils ne tournent qu'avec un serveur MariaDB externe et
  `pdo_mysql`.

## Conséquences
- Pas de retour arrière vers MariaDB : la sauvegarde de référence est le dump PostgreSQL (et, tant
  que l'utilisateur le garde, le volume Docker `db-data`).
- Réimporter un dump MariaDB demande de réinstaller `pdo_mysql` et un serveur MariaDB
  (l'historique git garde l'ancienne configuration).
- Reste à faire par l'utilisateur : rebuild du devcontainer, retrait du conteneur `mangatrackr_db`,
  suppression du secret GitHub `DB_ROOT_PASSWORD`, import sur le homelab, et premier run de la CI
  PostgreSQL via une PR vers `main`.
