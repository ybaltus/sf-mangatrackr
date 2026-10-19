# Chantier : déploiement scripté sur le homelab (VM `apps-dev`)

## Objectif
Automatiser le déploiement de MangaTrackr sur la VM `apps-dev` (Debian 13, Docker Compose,
PostgreSQL 18 mutualisé dans le conteneur `postgres`) et documenter une procédure de
réinstallation complète de la VM. Suite du chantier `migration-postgresql` (import fait).

## Périmètre
- Fichiers de production versionnés : `docker/Dockerfile.prod`, sa `.dockerignore`, `compose.prod.yaml`,
  modèle de secrets `deploy/mangatrackr.prod.env.dist`.
- Scripts idempotents `deploy/` (installation de la VM, base, sauvegarde, déploiement, contrôle, retour arrière).
- Documentation `docs/deploiement/apps-dev.md` et ADR 0015.
- **Hors périmètre** : reverse proxy HTTPS, domaine et Google OAuth (reportés) ; administration du
  conteneur `postgres` mutualisé (jamais recréé ni modifié par les scripts).

## Phases et critères de fin
| Phase | Contenu | Critère de fin |
|---|---|---|
| 1. Sécurité | `.gitignore`, historique git, modèle de secrets | Aucun secret suivi ni dans l'historique ; `ressources/` ignoré |
| 2. Scripts | Fichiers prod + `deploy/*.sh` | Scripts idempotents, `--dry-run` partout, jamais `down` |
| 3. Documentation | Réinstallation complète, ADR 0015 | Procédure de VM vierge à application en service |
| 4. Tests | Simulation locale (faux `docker`), QA | shellcheck propre, idempotence prouvée, `make qa-php-stan` / `make test-only` verts |
| 5. Livraison | Revue, essai `--dry-run` sur la VM (utilisateur), commits, PR brouillon | Relecteur sans bloquant, accord de l'utilisateur |

## Contraintes
- Pas de Docker ni d'accès à la VM depuis le devcontainer : tests par simulation, essai réel par l'utilisateur.
- Aucune modification de la base PostgreSQL existante pendant les tests.
- Migrations appliquées par `deploy.sh` uniquement avec `--migrate`, après sauvegarde `pg_dump`.
- Jamais de secret dans un fichier versionné, un rapport ou le journal.
- Pas de commit / push sans demande explicite.

## Entrées
- `ressources/HomelabDocumentationApps.md`, `ressources/Dockerfile.prod`, `ressources/Dockerfile.prod.dockerignore`,
  `ressources/compose.prod.yaml` (non versionnés) ; `ressources/mangatrackr.prod.env` : **secrets, ne pas lire**
  (seulement la liste des clés).
- `docs/chantiers/migration-postgresql/homelab.md`, `bin/pg-*.sh`.
