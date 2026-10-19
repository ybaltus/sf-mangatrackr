# État — deploiement-homelab

Dernière mise à jour : 2026-10-19 (chef de projet, T6 déploiement réussi sur la VM)

**Phase courante** : 5 (livraison) — essai sur la VM par l'utilisateur.

## Tâches
| ID | Tâche | Agent | Dépend de | Statut | Rapport |
|---|---|---|---|---|---|
| T1 | Sécuriser les fichiers d'environnement (`.gitignore`, historique, modèle de secrets) | chef + developpeur | — | terminé (historique propre, 423 commits) | `rapports/001-developpeur-securite-et-scripts.md` |
| T2 | Fichiers de prod + scripts idempotents `deploy/` | developpeur | T1 | terminé (bash -n, shellcheck 0, QA verte) | `rapports/001-developpeur-securite-et-scripts.md` |
| T3 | Documentation de réinstallation `docs/deploiement/apps-dev.md` + ADR 0015 | developpeur | T2 | terminé (3 incohérences T2 signalées : rotation backup.sh, en-tête .dist, make absent) | `rapports/002-developpeur-documentation.md` |
| T4 | Tests simulés des scripts + QA | testeur | T2 | terminé (0 bloquant, 2 majeurs M1/M2, 7 mineurs) ; corrigés en T4.1 (rejeu 64/64, QA verte) → `rapports/004-developpeur-corrections-t4.md` | `rapports/003-testeur-tests-scripts.md` |
| T5 | Revue | relecteur | T3, T4 | terminé — validé avec réserves (0 bloquant, 3 majeurs, 10 mineurs) ; T5.1 corrigé (rejeu 83/83, QA verte, `rapports/006-developpeur-corrections-revue.md`) ; contre-revue : prêt à commiter (r1 doc corrigé par le chef, r2 sans effet) → `rapports/007-relecteur-contre-revue.md` | `rapports/005-relecteur-revue-deploiement.md` |
| T6 | Essai `check.sh` / `--dry-run` sur la VM (doc §6) | utilisateur | push `dev` | terminé (déploiement b99e5fa, check 10/10, contrôles fonctionnels §8 validés par l’utilisateur) ; prod-previous créé au 2e déploiement | — |
| T7 | Commits + push `dev` (avant T6), puis PR brouillon → `main` après T6 | chef (sur accord explicite) | T5 | push `dev` fait (0f11c28) ; PR brouillon après T6 | — |

## Blocages
- Aucun. Réserves : pas de prod-previous avant le 2e déploiement ; `make test-only` non rejoué ici (hôte postgres non résolu) ; correctif containerd confirmé sur la VM.

## Décisions prises
- 2026-10-18 : nouveau chantier (migration-postgresql clos) ; migrations seulement avec `--migrate`
  après `pg_dump` ; fichiers prod et doc de la VM versionnés, tout `/ressources/` ignoré ;
  tests simulés ici puis essai `--dry-run` sur la VM par l'utilisateur.

## Points de vigilance
- `ressources/mangatrackr.prod.env` contient de vrais secrets : ne jamais le lire ni l'afficher.
- `docker/Dockerfile` + `docker/docker.sh` (démo : fixtures, utilisateur demo) : jamais en prod.

- Sur la VM, `compose.prod.yaml` et `docker/Dockerfile.prod*` posés à la main hors git : à retirer avant le premier `git pull`/`deploy.sh` (doc T3).
- `compose config` sans `--quiet` affiche les secrets.

## Prochaine action
1. (Chef, sur accord) commit de ce suivi, push `dev`, puis PR brouillon `dev` → `main`.
2. (Utilisateur) 2e déploiement scripté, à la prochaine version : crée `prod-previous` (l’ancienne image est déjà perdue, rien à supprimer). Clôture : dette technique + index des chantiers.
