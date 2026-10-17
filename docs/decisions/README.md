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

## Modèle
```markdown
# ADR nnnn : titre

- Statut : Proposée | Acceptée | Remplacée par ADR xxxx
- Date : AAAA-MM-JJ

## Contexte
## Décision
## Conséquences
```
