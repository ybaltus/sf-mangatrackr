# Journal — deploiement-homelab

Ajout seul, une ligne par événement : `- AAAA-MM-JJ | acteur | tâche | statut | détail`.

- 2026-10-18 | chef | T0 | terminé | Chantier créé (suite de migration-postgresql, clos) ; décisions utilisateur : nouveau chantier, `--migrate` après pg_dump, fichiers prod + doc VM versionnés, tests simulés puis VM
- 2026-10-18 | chef | T1 | en cours | `.gitignore` : bloc ressources remplacé par `/ressources/` (secrets de prod non ignorés auparavant) ; `git check-ignore` OK sur l'env de prod et le dump
- 2026-10-18 | chef | lance T1+T2 → developpeur | rapport 001
- 2026-10-18 | developpeur | T1+T2 | terminé | Historique git sans secret de prod (aucune clé à renouveler) ; modèle env .dist, en-têtes dev, fichiers prod versionnés, 5 scripts deploy/ + lib.sh (shellcheck propre, dry-run simulé sans appel modifiant), cibles prod-* ; PHPStan OK, 226 tests OK → rapports/001-developpeur-securite-et-scripts.md
- 2026-10-18 | chef | T1+T2 | 001 reçu | terminé : historique sans secret de prod, scripts deploy/ + fichiers prod, bash -n/shellcheck OK, PHPStan OK, 226 tests/580 ; à vérifier en T4 : compose run vs container_name, superutilisateur postgres, --wait sans healthcheck worker
- 2026-10-18 | chef | lance T3 → developpeur (002) et T4 → testeur (003) en parallèle
- 2026-10-18 | developpeur | T3 | terminé | Doc docs/deploiement/apps-dev.md (réinstallation, migration de l'installation actuelle, procédures courantes), bandeau homelab.md, ADR 0015 + index, CLAUDE.md/contexte.md, dette ; liens OK, PHPStan OK, 226 tests OK ; 2 incohérences de T2 signalées (rotation backup.sh, en-tête .dist) → rapports/002-developpeur-documentation.md
- 2026-10-18 | chef | T3 | 002 reçu | doc apps-dev.md, ADR 0015, CLAUDE/contexte/dette ; à corriger après T4 : motif de rotation de backup.sh (englobe les dumps pg-export), en-tête du .dist (deploy.sh pour un changement de variables) ; §7.5/7.6 psql manuels à relire en T5
- 2026-10-18 | testeur | T4 | terminé | Banc simulé (faux docker/git/curl, 93 exécutions) : aucun down/DROP/rm ni secret dans les appels ; compose run et --wait OK (source v2.29.7) ; 2 majeurs (rotation backup.sh supprime la sauvegarde neuve, nom de projet Compose implicite) et 7 mineurs ; PHPStan OK, 226 tests/580 → rapports/003-testeur-tests-scripts.md
- 2026-10-18 | chef | T4 | 003 reçu | 93 exécutions simulées, aucun appel destructif ni secret ; M1 rotation backup.sh supprime la sauvegarde fraîche, M2 nom de projet Compose non fixé ; m1-m7 mineurs ; pg_restore --list sur stdin non vérifié
- 2026-10-18 | chef | lance T4.1 → developpeur (004) : M1, M2 (`name: sf-mangatrackr`), m1-m7, en-tête .dist, alignement doc
- 2026-10-18 | developpeur | T4.1 | terminé | M1 (rotation backup_<base>_<date> stricte, courante protégée), M2 (name: + contrôle du label, COMPOSE_PROJECT_NAME), m1-m7, en-tête .dist et doc alignés ; rejeu 64/64, shellcheck propre, PHPStan OK, 226 tests OK → rapports/004-developpeur-corrections-t4.md
- 2026-10-18 | chef | T4.1 | 004 reçu | M1 (préfixe backup_), M2 (name + contrôle du label), m1-m7, doc alignée ; rejeu 64/64, QA verte ; cas d'erreur préflight/check non rejoués un par un
- 2026-10-18 | chef | lance T5 → relecteur (005)
- 2026-10-18 | relecteur | T5 | terminé | Validé avec réserves : 0 bloquant, 3 majeurs (prod-previous écrasée si image identique, --remove-orphans sans garde sur le projet de postgres, .dockerignore racine sans ressources/), 10 mineurs → rapports/005-relecteur-revue-deploiement.md
- 2026-10-18 | chef | T5 | 005 reçu | validé avec réserves : M1 prod-previous écrasé si image inchangée, M2 --remove-orphans sans garde, M3 .dockerignore racine sans ressources/ (image dev) ; 10 mineurs
- 2026-10-18 | chef | lance T5.1 → developpeur (006) : M1, M2 (retrait de --remove-orphans + garde label), M3, mineurs
- 2026-10-18 | developpeur | T5.1 | terminé | M1 (prod-previous gardé si image inchangée), M2 (sans --remove-orphans, refus si postgres ou conteneur étranger dans le projet), M3 (.dockerignore racine), mineurs m1-m10 (4 non retenus, justifiés) ; rejeu 83/83, shellcheck propre, PHPStan OK, 226 tests OK → rapports/006-developpeur-corrections-revue.md
- 2026-10-18 | chef | T5.1 | 006 reçu | M1-M3 corrigés, mineurs retenus/écartés justifiés ; rejeu 83/83, QA verte ; à confirmer sur vrai Docker : ID d'image identique au cache, nommage *-run-*
- 2026-10-18 | chef | lance contre-revue → relecteur (007) ; utilisateur : push sur dev autorisé après contre-revue
- 2026-10-18 | relecteur | T5 | terminé | Contre-revue : M1, M2, M3 et m1-m10 corrigés sans régression, écarts justifiés ; bash -n et shellcheck OK ; aucun fichier sensible à suivre ; prêt à commiter (2 mineurs résiduels) → rapports/007-relecteur-contre-revue.md
- 2026-10-18 | chef | T5 | terminé | contre-revue 007 : prêt à commiter ; r1 (suppression de l'ancienne image seulement après le 2e déploiement) corrigé dans la doc §6 par le chef ; r2 sans effet
- 2026-10-18 | chef | T7 | commits + push dev | sur accord de l'utilisateur (« on pousse sur dev »), avant T6 ; PR brouillon après T6
