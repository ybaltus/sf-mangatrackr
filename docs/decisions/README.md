# Décisions d'architecture (ADR)

Un fichier par décision structurante : le **contexte**, la **décision** et ses
**conséquences**. Une décision n'est pas réécrite quand elle évolue : on ajoute un nouvel ADR
qui la remplace et on passe l'ancien en « Remplacée par ADR xxxx ».

| N° | Décision | Statut |
|---|---|---|
| [0001](0001-strict-types-partout.md) | `declare(strict_types=1)` partout et conversion aux frontières | Acceptée |
| [0002](0002-logique-metier-dans-des-services.md) | Contrôleurs minces, logique dans `Services/`, horloge PSR-20 | Acceptée |
| [0003](0003-journalisation-psr-3.md) | Journaliser les erreurs, ne pas exposer les exceptions | Acceptée |
| [0004](0004-codes-expires-410-gone.md) | Code expiré ou déjà utilisé : 410 Gone | Acceptée |
| [0005](0005-mise-a-jour-des-dependances.md) | Mise à jour des dépendances (92 avis de sécurité) | Acceptée |
| [0006](0006-qualite-automatisee-et-commits.md) | Qualité automatisée : GrumPHP actif, règles de commit | Acceptée |
| [0007](0007-conventions-de-tests.md) | Conventions de tests (rejouables, doubles, horloge figée) | Acceptée |
| [0008](0008-php-8-2-minimum.md) | PHP 8.2 minimum (lock incompatible avec 8.1) | Acceptée |
| [0009](0009-lockfile-npm-et-assets-ux.md) | Lockfile npm régénéré avec npm 11 (assets Symfony UX) | Acceptée |
| [0010](0010-audit-npm-et-borne-webpack.md) | Audit npm sans version majeure et borne sur webpack | Acceptée |
| [0011](0011-chantiers-pilotes-par-sous-agents.md) | Chantiers pilotés par un chef de projet et des sous-agents | Acceptée |
| [0012](0012-migration-postgresql-18.md) | Migration vers PostgreSQL 18 (baseline unique, `app_user`, IDENTITY, ICU `fr`) | Acceptée |
| [0013](0013-bascule-production-postgresql.md) | Bascule de la production vers PostgreSQL sur le homelab (maintenance, répétition, retour arrière) | Remplacée par ADR 0014 |
| [0014](0014-bascule-homelab-simplifiee-et-retrait-mariadb.md) | Bascule simplifiée sur le homelab (`pg_dump`) et retrait de MariaDB | Acceptée |

## Modèle
```markdown
# ADR nnnn : titre

- Statut : Proposée | Acceptée | Remplacée par ADR xxxx
- Date : AAAA-MM-JJ

## Contexte
## Décision
## Conséquences
```
