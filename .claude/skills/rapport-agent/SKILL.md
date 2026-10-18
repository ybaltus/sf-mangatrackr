---
name: rapport-agent
description: Format obligatoire du rapport d'un sous-agent travaillant sur un chantier MangaTrackr (fichier détaillé dans docs/chantiers/<nom>/rapports/, ligne de journal, résumé court au chef de projet). À appliquer à la fin de toute tâche déléguée.
user-invocable: false
---

# Rapport d'un sous-agent

Le chef de projet ne voit que ton **résumé final**. Tout le détail doit donc être écrit sur
disque, pour lui, pour les autres agents et pour une session future.

## 1. Rapport détaillé
Écrire `docs/chantiers/<chantier>/rapports/<NNN>-<agent>-<sujet-court>.md` (le numéro `NNN`
est donné par le chef ; s'il manque, prendre le prochain libre sur 3 chiffres) :

```markdown
# <NNN> — <agent> — <sujet>

- Tâche : T<n>
- Date : AAAA-MM-JJ
- Statut : terminé | partiel | échec | bloqué

## Objectif
## Ce qui a été fait
(commandes lancées, fichiers créés ou modifiés avec leur chemin)
## Résultats
(chiffres, tableaux, extraits de sortie utiles — pas de sortie brute de plus de 30 lignes)
## Écarts, risques, points non vérifiés
## Recommandation pour la suite
```

Ne jamais y recopier de secret (mot de passe, jeton, contenu de `.env.local`) ni de donnée
personnelle issue d'un dump (emails, hash de mot de passe) : seulement des comptages ou des
identifiants techniques.

## 2. Journal
Ajouter **une** ligne à la fin de `docs/chantiers/<chantier>/journal.md` :

```
- AAAA-MM-JJ | <agent> | T<n> | <statut> | <une phrase> → rapports/<NNN>-....md
```

Ne pas modifier `etat.md` : c'est le rôle du chef.

## 3. Résumé retourné au chef (dernier message, 15 lignes max)
```
Statut : terminé | partiel | échec | bloqué
Critère de fin : atteint | non atteint (pourquoi)
Faits marquants : 2 à 5 puces
Fichiers modifiés : liste courte (ou « aucun »)
Risques / décisions à prendre : …
Rapport : docs/chantiers/<chantier>/rapports/<NNN>-....md
```

Être franc : un échec ou une vérification impossible se dit explicitement.
