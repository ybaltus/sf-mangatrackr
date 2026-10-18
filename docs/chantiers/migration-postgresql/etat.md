# État — migration-postgresql

Dernière mise à jour : 2026-10-18 (chef de projet, T8 retrait de MariaDB)

**Phase courante** : T0-T7 terminées (dépôt sur PostgreSQL 18, export homelab prêt) ; T8 (retrait de MariaDB) terminé, non commité.

## Tâches
| ID | Tâche | Agent | Dépend de | Statut | Rapport |
|---|---|---|---|---|---|
| T0 | Organisation : skills, agents, suivi, ADR 0011 | chef | — | terminé | — |
| T1 | Analyse hors ligne du dump (dry-run) | chef | — | terminé | `rapports/001-attendu.json` |
| T2 | Import dans `sf_mangatrackr_app_import` + vérifications | migration-importeur | T1, base joignable | terminé | `rapports/003-migration-importeur-import-dump.md` |
| T3 | Audit compatibilité MariaDB → PG 18 | migration-auditeur | — (lecture seule) | terminé | `rapports/002-migration-auditeur-audit-compatibilite.md` |
| T4 | Schéma et infra PG 18 (Docker, `pdo_pgsql`, env, migrations, CI) | migration-schema | T3 | terminé (T4.1-T4.8, rapports 004-016) ; réserve : 1er run CI réel sur GitHub non fait | `010-migration-schema-baseline-postgresql.md` |
| T5 | Transfert des données + parité | migration-donnees | T2, T4 | terminé (parité 22 tables ; doctrine_migration_versions volontairement non transférée) | `017-migration-donnees-transfert.md` |
| T6 | Recette QA + revue | testeur, relecteur | T5 | terminé (rapports 018-025) | `023-relecteur-revue-corrections.md` |
| T7 | Plan de bascule homelab + ADR + dette technique | chef, relecteur | T6 | terminé (procédure simplifiée `homelab.md`, export `make pg-export` ; plan-bascule.md non retenu) | `034-migration-donnees-export-homelab.md` |
| T8 | Retrait de MariaDB (infra, config, docs vivantes) ; outils de migration et historique conservés | migration-schema, relecteur, testeur | T7 | terminé (relecteur : validé avec réserves, mineurs 1/3/4 corrigés ; testeur : nouvelle image sans `pdo_mysql`, 226/580 verts) ; non commité | `035-…`, `036-…`, `037-testeur-validation-retrait-mariadb.md` |

## Blocages
- Aucun côté dépôt (propriétaire root des fichiers corrigé par l'utilisateur, 2026-10-18).

## Décisions à prendre
- Aucune en attente.

## Points de vigilance
- CI PostgreSQL : verte sur la PR #17 (fusionnée dans `main` le 2026-10-18) ; PR T8 brouillon #18 ouverte (`dev` 366f19a).
- Test instable isolé (1 erreur, non reproduite) ; DBAL 4 : surveiller l'index d'expression `LOWER(email)`.
- Après T8 (2026-10-18) : PR #18 fusionnée ; devcontainer reconstruit, `mangatrackr_db` et volume `db-data` retirés ;
  nouveau conteneur vérifié (`pdo_pgsql` sans `pdo_mysql`, 226 tests / 580 verts).
- Le devcontainer tourne en root : les fichiers qu'il crée appartiennent à root sur l'hôte
  (`remoteUser`/UID 1000 à envisager).

## Décisions prises
- 2026-10-18 : cible PostgreSQL 18 ; production future sur le homelab ; dump désindexé et
  `ressources/*.sql` ignoré par git ; agents au modèle de la session ; organisation ADR 0011.
- 2026-10-18 : les 2 écarts d'index hérités corrigés dans la baseline PostgreSQL (T4).
- 2026-10-18 (audit T3, rapport 002) : D1 baseline PG unique + archivage des 15 migrations MySQL ;
  **D2 table `user` renommée `app_user`** ; D3 IDENTITY ; **D4 recherche insensible à la casse ET
  aux accents** (`LOWER` + `unaccent`) ; D5 collation ICU `fr` ; D6 coexistence MariaDB/PG jusqu'à T5 ;
  D7 `json` ; D8 `disable_type_comments: true` ; D9 transfert par script PHP/DBAL.
- 2026-10-18 : index unique sur `LOWER(email)` (table `app_user`) dans la baseline PG (T4.4).
- 2026-10-18 : bascule simplifiée, faite par l'utilisateur sur le homelab (PG 18) avec `make pg-import`.
- 2026-10-18 : ADR 0014 (bascule simplifiée, retrait de MariaDB) remplace l'ADR 0013.
- 2026-10-18 (T8) : MariaDB retiré de l'infra ; outils de migration MariaDB conservés comme archive ;
  historique (ADR, rapports, journal, `migrations-archive-mariadb/`) non modifié.

## Prochaine action
1. (Utilisateur) Supprimer le secret GitHub `DB_ROOT_PASSWORD` ; import sur le homelab (`homelab.md`).
