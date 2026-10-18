# Chantier : migration MariaDB → PostgreSQL 18

## Objectif
Faire tourner MangaTrackr sur PostgreSQL 18, avec des données de production migrées sans
perte, puis basculer la production sur le homelab de l'utilisateur. D'ici là, la production
Hostinger reste en MariaDB.

## Source de données
- Dump de production : `ressources/u556516513_20261018_mangasync.sql` (phpMyAdmin, MariaDB
  11.8.9, 2026-10-18, SHA-256 `967d0750…5286`). **Ignoré par git** (données personnelles).
- 23 tables, 15 migrations Doctrine (identiques à `migrations/`), 71 029 lignes attendues
  (`rapports/001-attendu.json`).

## Phases et critères de fin
| Phase | Contenu | Critère de fin |
|---|---|---|
| 0. Organisation | Skills, agents, suivi | Équipe et suivi en place (fait) |
| 1. Validation de l'extraction | Import dans `sf_mangatrackr_app_import` | Comptages = dump, migrations = `migrations/`, mapping valide, aucun orphelin |
| 2. Audit | Dépendances MariaDB du code et de l'infra, compatibilité PG 18 | Rapport d'audit + découpage validé par l'utilisateur |
| 3. Schéma et infra | Service `postgres:18`, `pdo_pgsql`, `DATABASE_URL`, migrations PG, CI | `doctrine:schema:validate` OK sur PG, migrations rejouables |
| 4. Données | Transfert MariaDB importée → PG | `inventaire.php --compare` : parité complète (écarts justifiés) |
| 5. Recette | QA complète sur PG, revue | `make qa-php-stan` et `make test-only` verts sur PG, relecteur `validé` |
| 6. Bascule | Plan homelab : export final, gel des écritures, import, retour arrière | Plan validé par l'utilisateur (ADR) |

## Contraintes
- Pas de Docker, pas de client `mysql`, pas de `pdo_pgsql` dans le conteneur `app` : les
  opérations de conteneur (démarrage, rebuild) sont faites par l'utilisateur depuis l'hôte.
- Jamais d'import dans la base de dev ni de test ; jamais de donnée du dump dans un rapport.
- Ne pas modifier les migrations commitées. Pas de commit / push sans demande explicite.
- Toute décision structurante → ADR dans `docs/decisions/`.

## Outillage
- Agents : `migration-importeur`, `migration-auditeur`, `migration-schema`,
  `migration-donnees` + équipe projet `developpeur`, `testeur`, `relecteur`.
- Skills : `chantier`, `rapport-agent`, `qa-symfony`, `import-dump-mariadb`, `verif-donnees`.
