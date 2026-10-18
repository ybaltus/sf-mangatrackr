# 026 — migration-schema — Plan de bascule et ADR 0013

- Tâche : T7
- Date : 2026-10-18
- Statut : terminé (documentation seule, rien exécuté)

## Objectif
Rédiger le runbook de bascule de la production (Hostinger/MariaDB) vers le homelab (PostgreSQL 18) et
l'ADR correspondant, sans inventer les spécificités du homelab.

## Ce qui a été fait
Lecture : ADR 0012, rapports 017, 019, 022, 023, 024, SKILL.md de `verif-donnees` et
`import-dump-mariadb`, README du chantier, `rejouer-transfert.sh`, baseline
`migrations/Version20261018145659.php`, `docker/postgres/init/00-create-app-role-and-database.sh`.
Fichiers créés ou modifiés (aucune commande d'écriture, aucun transfert, aucun accès base) :
- `docs/chantiers/migration-postgresql/plan-bascule.md` (créé) : 15 sections, du pré-requis au
  retour arrière, calendrier, rôles, 22 questions ouvertes.
- `docs/decisions/0013-bascule-production-postgresql.md` (créé, Statut : Proposée).
- `docs/decisions/README.md` : ligne 0013 ajoutée à l'index.
- `docs/chantiers/migration-postgresql/journal.md` : une ligne.

## Résultats
Le plan couvre : pré-requis, répétition, doublons d'emails (requêtes SQL), sauvegarde et gel, export
final, import `_import`, base PostgreSQL (ICU `fr`, rôle sans `CREATEDB`), baseline, transfert avec
`TRANSFER_EXPECTED_HOST/DB` et `CONFIRM_TARGET` saisi à chaque commande, alias/tunnel exclus, parité
(tolérante, stricte, séquences), recette R1 à R14 (scénarios non écrits du rapport 019), bascule DNS,
critères go/no-go G1 à G11, retour arrière (30 jours de conservation proposés).

## Écarts, risques, points non vérifiés
- Aucune commande n'a été exécutée ; les commandes viennent du dépôt (scripts, SKILL.md) et de la
  baseline. Les requêtes SQL (doublons d'emails, vérifications ICU) sont nouvelles et **non testées** ;
  le nom de colonne `daticulocale` change en PostgreSQL 17+ (`datlocale`), signalé dans le plan.
- **Écart relevé** : l'ADR 0012 D3 prévoit un recalage des séquences à `GREATEST(MAX(id), AUTO_INCREMENT-1)`
  mais le script livré et SKILL.md recalent à `MAX(id)+1` (rapport 017). Un ID supprimé en queue de table
  pourrait être réutilisé. Inscrit en question Q19 et dans l'ADR 0013 ; décision/correction du script à
  prendre (hors périmètre de cette tâche, aucun script touché).
- Doublons d'emails : MariaDB `utf8mb4_unicode_ci` les empêche en principe côté source ; le contrôle est
  gardé tout de même. `user_invitation_code.email` n'a pas d'index `LOWER(email)` : à vérifier en répétition.
- Durées (calendrier) et durée de conservation sont des propositions, pas des mesures.
- Les pré-requis du homelab sont des questions ouvertes (Q1 à Q22), non des hypothèses.
- Mention « anomalie 2 corrigée par 021/023 » : déduite des rapports, pas rejouée.

## Recommandation pour la suite
- L'utilisateur répond aux questions ouvertes du plan (section 14) et valide l'ADR 0013 (passage à
  Acceptée).
- Trancher le recalage des séquences (Q19) avant la répétition ; corriger le script si `GREATEST(...)` est retenu.
- Planifier la répétition générale (J-7) puis relancer un relecteur sur le plan avec les réponses.
