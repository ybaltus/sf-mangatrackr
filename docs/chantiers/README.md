# Chantiers

Un chantier est un travail en plusieurs étapes, piloté par la session principale (chef de
projet) qui délègue à des sous-agents (`.claude/agents/`). Son état vit dans son dossier pour
pouvoir être repris après une coupure de session : voir le skill `chantier`
(`.claude/skills/chantier/SKILL.md`) et l'ADR 0011.

Reprendre : `/chantier reprendre <nom>`.

| Chantier | Objectif | Statut | Démarré |
|---|---|---|---|
| [migration-postgresql](migration-postgresql/etat.md) | Passer de MariaDB à PostgreSQL 18 | terminé côté dépôt (import homelab par l'utilisateur) | 2026-10-18 |
