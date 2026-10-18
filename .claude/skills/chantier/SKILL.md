---
name: chantier
description: Piloter un chantier de développement en chef de projet (MangaTrackr) — créer, reprendre ou faire avancer un chantier en déléguant aux sous-agents et en tenant à jour son état sur disque. À utiliser quand l'utilisateur dit « chantier », « reprendre le chantier », « où en est la migration », ou lance un travail en plusieurs étapes qui doit survivre à la fermeture de la session.
argument-hint: "[reprendre|nouveau|statut] <nom-du-chantier>"
---

# Piloter un chantier (rôle : chef de projet)

La session principale joue le **chef de projet**. Elle ne fait pas le travail technique :
elle lit l'état, choisit la tâche suivante, la délègue à un sous-agent, contrôle le
résultat et met l'état à jour. Les sous-agents ne peuvent pas lancer d'autres sous-agents :
toute délégation passe par le chef.

## Où vit la mémoire du chantier
Tout est dans `docs/chantiers/<nom>/` (versionné, donc rien n'est perdu si la session se ferme) :

| Fichier | Rôle | Qui écrit |
|---|---|---|
| `README.md` | Objectif, périmètre, critères de fin, phases, contraintes | Chef (au démarrage) |
| `etat.md` | **Tableau de bord** : phase courante, tâches et statuts, blocages, prochaine action | Chef uniquement |
| `journal.md` | Chronologie en ajout seul, une ligne par événement | Chef et agents |
| `rapports/NNN-<agent>-<sujet>.md` | Rapport détaillé de chaque intervention | L'agent concerné |

L'index de tous les chantiers est `docs/chantiers/README.md`.

## Reprendre un chantier (début de session)
1. Lire `docs/chantiers/<nom>/etat.md` **et rien d'autre** au départ (pas les rapports, pas le code).
2. Lire les 10 dernières lignes de `journal.md` pour vérifier qu'aucune tâche n'est restée
   `en cours` sans rapport (session coupée) : si c'est le cas, la repasser en `à reprendre`.
3. Résumer à l'utilisateur en 5 lignes max : phase, dernière tâche terminée, blocages, prochaine action proposée.
4. Attendre son accord avant toute action visible par d'autres ou irréversible (voir CLAUDE.md).

## Boucle de pilotage (une tâche à la fois, ou plusieurs si indépendantes)
1. **Choisir** la tâche dans `etat.md` (dépendances satisfaites).
2. **Marquer** `en cours` dans `etat.md` et ajouter une ligne au journal (`chef | lance T<n> → <agent>`).
3. **Déléguer** avec l'outil Agent, `subagent_type` = l'agent du tableau ci-dessous. Le prompt contient :
   - le nom du chantier et l'identifiant de tâche (`T<n>`) ;
   - le numéro de rapport à utiliser (`NNN` = prochain numéro libre dans `rapports/`) ;
   - l'objectif, les entrées (fichiers, rapports précédents **par leur chemin**, pas leur contenu) et le critère de fin ;
   - les interdits propres à la tâche.
   Lancer en parallèle (un seul message) les tâches indépendantes.
4. **Contrôler** le résumé retourné (format imposé par le skill `rapport-agent`) : statut,
   critère de fin atteint ou non, risques. Ne pas ouvrir le rapport complet sauf si le résumé
   est ambigu, en échec, ou si une décision en dépend.
5. **Mettre à jour** `etat.md` (statut, lien vers le rapport, prochaine action) et le journal.
6. **Décider** : tâche suivante, correction (renvoyer au même agent avec `SendMessage` si son
   contexte est utile), ou escalade à l'utilisateur (choix structurant → proposer un ADR).

Ne jamais déclarer une phase terminée sans un passage du `relecteur` et du `testeur`
(`make qa-php-stan` et `make test-only` verts), conformément à CLAUDE.md.

## Équipe disponible (`.claude/agents/`)
| Agent | Portée | Rôle |
|---|---|---|
| `developpeur` | projet | Implémente une tâche de code bien délimitée |
| `testeur` | projet | Écrit/lance les tests et la QA, rapporte les échecs sans corriger le code métier |
| `relecteur` | projet | Revue en lecture seule d'un diff ou d'un livrable |
| `migration-importeur` | migration | Importe un dump dans une base locale dédiée et vérifie l'extraction |
| `migration-auditeur` | migration | Audit lecture seule de la compatibilité MariaDB → PostgreSQL |
| `migration-schema` | migration | Config Doctrine/Docker/env et migrations pour PostgreSQL |
| `migration-donnees` | migration | Transfert des données vers PostgreSQL et contrôle de parité |

Un nouvel agent ou un nouveau skill se crée dans `.claude/agents/` ou `.claude/skills/` et
s'ajoute à ce tableau.

## Créer un chantier
1. `docs/chantiers/<nom>/` avec `README.md`, `etat.md`, `journal.md`, `rapports/` en copiant
   la structure de `docs/chantiers/migration-postgresql/`.
2. Ajouter une ligne dans `docs/chantiers/README.md`.
3. Découper en phases et tâches numérotées `T1, T2…` avec dépendances et agent pressenti.

## Format de `etat.md`
Garder ce fichier **court** (< 80 lignes) : c'est le seul que le chef lit en entier.
Statuts : `à faire`, `en cours`, `bloqué`, `à reprendre`, `terminé`, `abandonné`.
Toujours dater (AAAA-MM-JJ) la ligne « Dernière mise à jour ».

## Clôture
Mettre à jour `docs/claude/etat-et-dette-technique.md`, l'index des ADR, passer le chantier
en `terminé` dans `docs/chantiers/README.md`. Commits uniquement sur demande explicite.
