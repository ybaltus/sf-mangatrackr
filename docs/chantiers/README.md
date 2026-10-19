# Chantiers

Un chantier est un travail en plusieurs étapes, piloté par la session principale (chef de
projet) qui délègue à des sous-agents (`.claude/agents/`). Son état vit dans son dossier pour
pouvoir être repris après une coupure de session : voir le skill `chantier`
(`.claude/skills/chantier/SKILL.md`) et l'ADR 0011.

Reprendre : `/chantier reprendre <nom>`.

| Chantier | Objectif | Statut | Démarré |
|---|---|---|---|
| [migration-postgresql](migration-postgresql/etat.md) | Passer de MariaDB à PostgreSQL 18 | terminé (import homelab fait) | 2026-10-18 |
| [deploiement-homelab](deploiement-homelab/etat.md) | Déploiement scripté et réinstallation de la VM `apps-dev` | terminé (PR #20 fusionnée, déployé sur la VM) | 2026-10-18 |
