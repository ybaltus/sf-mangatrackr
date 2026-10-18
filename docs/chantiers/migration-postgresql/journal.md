# Journal — migration-postgresql

Ajout seul, une ligne par événement : `date | acteur | tâche | statut | résumé → rapport`.

- 2026-10-18 | chef | T0 | terminé | Skills (chantier, rapport-agent, qa-symfony, import-dump-mariadb, verif-donnees), 7 agents, suivi du chantier, ADR 0011
- 2026-10-18 | chef | T0 | terminé | Dump désindexé de git, `ressources/*.sql` ajouté au .gitignore
- 2026-10-18 | chef | T1 | terminé | Dry-run du dump : 442 instructions, 23 tables, 71 029 lignes (recoupé par comptage ligne à ligne) → rapports/001-attendu.json
- 2026-10-18 | chef | T2 | bloqué | MariaDB injoignable depuis le conteneur app (hôte `database` inconnu)
- 2026-10-18 | chef | T3 | en cours | lance T3 → migration-auditeur (rapport 002) ; T2 toujours bloqué (base injoignable)
- 2026-10-18 | chef | T2 | en cours | base relancée (MariaDB locale 10.11.5) ; lance T2 → migration-importeur (rapport 003)
- 2026-10-18 | migration-importeur | T2 | terminé | Dump importé dans sf_mangatrackr_app_import : 23/23 tables à parité (71 029 lignes), migrations à jour, 0 orphelin, UTF-8 sain → extraction correcte → rapports/003-migration-importeur-import-dump.md
- 2026-10-18 | chef | T2 | terminé | Contrôlé par le chef (--expected : 23\/23 OK, aucun email dans les rapports). Phase 1 terminée, extraction correcte
- 2026-10-18 | utilisateur | T4 | décision | Écarts d'index corrigés dans la baseline PostgreSQL, pas de migration MariaDB dédiée
- 2026-10-18 | migration-auditeur | T3 | terminé | Audit MariaDB → PG 18 : 30 constats (8 bloquants : table user, 15 migrations MySQL, env, Docker, pdo_pgsql, init test, CI), tris NULL et LIKE à adapter, baseline PG + IDENTITY recommandées, 9 décisions → rapports/002-migration-auditeur-audit-compatibilite.md
- 2026-10-18 | chef | T3 | terminé | 30 constats dont 8 bloquants, 9 décisions D1-D9 à soumettre à l'utilisateur
- 2026-10-18 | utilisateur | T3 | décision | D1, D3, D6-D9 selon l'audit ; D2 `user` → `app_user` ; D4 casse + accents ; D5 ICU fr
- 2026-10-18 | chef | T4 | en cours | lance T4.1 → migration-schema (rapport 004) et T4.7 → developpeur (rapport 005) ; ajout T4.8 (accents, PG seulement)
- 2026-10-18 | migration-schema | T4.1 | terminé | ADR 0012 « Migration vers PostgreSQL 18 » (D1-D9, exception unique sur les migrations, index hérités) + index des ADR → rapports/004-migration-schema-adr-postgresql.md
- 2026-10-18 | chef | T4.1 | terminé | ADR 0012 contrôlé ; unaccent placé dans la baseline (T4.4) ; rebuild PG requis avant T4.3
- 2026-10-18 | developpeur | T4.7 | terminé | Tris NULL portables (CASE HIDDEN), LOWER() sur recherche et dédoublonnage, emails normalisés à l'écriture et lus sans casse, OrderBy collections ; 16 tests, PHPStan OK, 185 tests/525 assertions → rapports/005-developpeur-corrections-portables.md
- 2026-10-18 | chef | T4.7 | terminé | Contrôlé par le chef : PHPStan OK, 185 tests / 525 assertions verts ; relecture à faire (auth modifiée)
- 2026-10-18 | utilisateur | T4.4 | décision | Index unique sur LOWER(email) ajouté au périmètre de la baseline PG
- 2026-10-18 | chef | T4 | en cours | lance relecture T4.7 → relecteur (rapport 006) et T4.2 Docker → migration-schema (rapport 007)
- 2026-10-18 | relecteur | T4.7 | terminé | Revue T4.7 : validé avec réserves (0 bloquant, 0 majeur, 4 mineurs : trim vs throttling, départage des tris, test date fragile, index LOWER) ; PHPStan OK, 185 tests verts → rapports/006-relecteur-revue-t4-7.md
- 2026-10-18 | migration-schema | T4.2 | terminé | Service postgres:18 (volume pg-data sur /var/lib/postgresql, ICU fr, healthcheck), scripts d'init rôle non superutilisateur + bases dev/test, pdo_pgsql dans les 2 Dockerfile, psql 18 PGDG en dev ; non construit (pas de Docker) → rapports/007-migration-schema-docker-postgresql.md
- 2026-10-18 | chef | T4.7 | relu | Relecture 006 : validé avec réserves (4 mineurs) ; R1 (throttling/trim), R2 (départage des tris), R3 (test fragile) renvoyés au developpeur → rapport 008
- 2026-10-18 | chef | T4.2 | terminé | Fichiers Docker PG 18 écrits (non testés, pas de Docker dans app) ; rebuild + 9 vérifications demandés à l'utilisateur
- 2026-10-18 | developpeur | T4.7-bis | terminé | Réserves 006 corrigées : email normalisé avant UserBadge/throttling (getPayload typé), oauthTestEmails sans casse, départage m.id, test latest isolé ; 192 tests/541 assertions, PHPStan OK → rapports/008-developpeur-corrections-relecture-t4-7.md
- 2026-10-18 | chef | T4.7-bis | terminé | Contrôlé par le chef : PHPStan OK, 192 tests / 541 assertions ; throttling testé en unitaire seulement
- 2026-10-18 | chef | T4.3 | à faire | Session interrompue pour le rebuild du devcontainer ; mémoire Claude sauvegardée dans var/claude-memory-backup/
