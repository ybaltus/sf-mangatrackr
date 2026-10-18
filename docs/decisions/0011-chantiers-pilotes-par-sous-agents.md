# ADR 0011 : chantiers pilotés par un chef de projet et des sous-agents

- Statut : Acceptée
- Date : 2026-10-18

## Contexte
Les gros travaux (migration vers PostgreSQL, montées de versions) dépassent une session
Claude : le contexte grossit, et une session fermée fait perdre l'avancement. On veut aussi
séparer les rôles (implémentation, tests, revue, import) pour garder chaque contexte petit et
spécialisé.

## Décision
- La session principale joue le **chef de projet** (skill `chantier`) : elle lit l'état,
  délègue, contrôle les résumés, met l'état à jour. Les sous-agents ne pouvant pas lancer
  d'autres sous-agents, la délégation passe toujours par elle.
- Des **sous-agents** versionnés dans `.claude/agents/` : équipe projet (`developpeur`,
  `testeur`, `relecteur`) et agents propres à un chantier (`migration-*`).
- Des **skills** versionnés dans `.claude/skills/` : protocole (`chantier`, `rapport-agent`),
  QA (`qa-symfony`), outillage de chantier (`import-dump-mariadb`, `verif-donnees`).
- La mémoire d'un chantier est sur disque, dans `docs/chantiers/<nom>/` : `README.md`
  (objectif), `etat.md` (tableau de bord, écrit par le chef seul), `journal.md` (ajout seul),
  `rapports/` (un rapport détaillé par intervention d'agent).

## Conséquences
- Une session peut reprendre un chantier en lisant uniquement `etat.md` (`/chantier reprendre <nom>`).
- Chaque intervention laisse une trace relisible ; le chef ne lit les rapports complets qu'en cas de doute.
- Coût : plusieurs agents relisent une partie du contexte ; les tâches doivent donc être bien
  délimitées et les entrées passées par chemin de fichier.
- Les rapports et le journal sont versionnés : ils ne doivent contenir ni secret ni donnée personnelle.
