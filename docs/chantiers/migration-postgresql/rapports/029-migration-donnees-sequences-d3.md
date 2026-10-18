# 029 — migration-donnees — Séquences IDENTITY selon D3 (Q19, option 1)

- Tâche : T7-bis Q19
- Date : 2026-10-18
- Statut : terminé

## Objectif
Appliquer l'ADR 0012 D3 : séquences recalées à `GREATEST(MAX(id), AUTO_INCREMENT_source - 1)`, tables vides comprises ;
`inventaire.php` cohérent (tolérant et strict) ; refus explicite si AUTO_INCREMENT source absent.

## Ce qui a été fait
- `transfert-mariadb-pgsql.php` : avant tout TRUNCATE, lecture de `information_schema.tables.AUTO_INCREMENT` de la source pour
  chaque table à séquence IDENTITY (cible) ; NULL/absent/<1 = **code 6** (nouveau, rien modifié, message : cocher l'option
  AUTO_INCREMENT de l'export phpMyAdmin). Dans la transaction : `max(MAX(id), AI-1)` calculé ; `setval` appliqués après le COMMIT (I3
  conservé) ; table vide : `setval(AI-1, true)` ou `(1, false)` si AI = 1.
- `inventaire.php` : l'inventaire MariaDB enregistre `auto_increment` par table ; à la comparaison, attendu =
  `max(MAX+1, AUTO_INCREMENT source)` (le renommage user:app_user est respecté) ; AUTO_INCREMENT absent à gauche = écart (un
  inventaire MariaDB ancien doit être régénéré). Mode strict identique.
- `SKILL.md` et en-têtes de `rejouer-transfert.sh` : règle D3, code 6.
- Fichiers : `/var/www/.claude/skills/verif-donnees/SKILL.md`, `.../scripts/transfert-mariadb-pgsql.php`, `.../scripts/inventaire.php`,
  `.../scripts/rejouer-transfert.sh`.

## Résultats
- AUTO_INCREMENT est **préservé par l'import** dans `_import` (valeurs non NULL pour les 19 tables à séquence) et **lisible par
  l'utilisateur applicatif `mangatrackr`** (root inutile). Les tables de jointure sans id ont NULL (normal, pas de séquence).
- Sur les données réelles, AUTO_INCREMENT = MAX+1 pour les 19 séquences (aucune suppression en fin de table dans ce dump) : le
  nouveau calcul donne les mêmes valeurs qu'avant, l'écart n'était que théorique aujourd'hui.
- Base scratch `sf_mangatrackr_app_scratch` (migrée Doctrine, supprimée ensuite : 0 base scratch restante) :

| Preuve | Résultat |
|---|---|
| `rejouer-transfert.sh` réel | code 0 ; 22 tables ; parité tolérante et stricte ; 19 séquences en écart 0 ; baseline présente |
| Rejeu n° 2 | code 0 ; inventaires PostgreSQL (tolérant, strict) et MariaDB identiques au premier (hors `generated_at`) |
| AUTO_INCREMENT NULL simulé (`editor`) | code 6, message explicite, cible inchangée (550 lignes conservées) |
| AUTO_INCREMENT > MAX+1 simulé (`editor` +10) | prochaine valeur 562 (MAX+1 = 552) |
| Table vide avec AUTO_INCREMENT=6 simulé (`manga_release_config`) | prochaine valeur 6 (au lieu de 1) |
| Comparaison avec attendu relevé (tolérant et strict) | parité complète, 0 écart |
| Même cible contre l'inventaire réel (AI non relevé), tolérant et strict | 2 écarts signalés (`editor.id` 562/552, `manga_release_config.id` 6/1) : le contrôle détecte bien |
| Inventaire MariaDB sans `auto_increment` | 19 écarts « AUTO_INCREMENT source absent » |
| `make qa-php-stan` | No errors |
| `make test-only` | OK (226 tests, 580 assertions) |

## Écarts, risques, points non vérifiés
- Les cas AUTO_INCREMENT > MAX+1, NULL et table vide à AI > 1 sont **simulés** : la source étant en lecture seule et `mangatrackr`
  n'ayant pas le droit de créer une base MariaDB (refusé), j'ai exécuté des **copies** du script (dans le scratchpad, requête
  AUTO_INCREMENT surchargée) et un inventaire gauche modifié. Le chemin de code est celui du script réel, mais pas sur une vraie
  table MariaDB à AI > MAX+1.
- Si le dump est exporté sans l'option AUTO_INCREMENT, `_import` peut donner AI = 1 (non NULL) pour certaines tables : le script
  ne le détecte alors pas (seul NULL est refusé) et retombe sur MAX+1. À vérifier à 6.3/6.4 du plan (cocher l'option) ;
  contrôle possible : comparer `information_schema.tables.AUTO_INCREMENT` à `MAX(id)+1` dans `_import`.
- Base de dev `sf_mangatrackr_app` non touchée ; pas rejoué ; le dev garde donc les séquences MAX+1 (identiques en pratique).
- Docs du plan / ADR non modifiés (autre agent) : `plan-bascule.md` (mentions MAX+1, « recaler à la main ») à vérifier par lui.

## Recommandation pour la suite
Demander à l'utilisateur l'accord pour rejouer en dev (`CONFIRM_TARGET=postgres/sf_mangatrackr_app`). Ajouter au plan 6.3 l'option
AUTO_INCREMENT de l'export et le code de sortie 6.
