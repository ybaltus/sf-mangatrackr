# 032 — migration-donnees — Rejeu dev avec D3

- Tâche : T7 (rejeu dev, règle D3)
- Date : 2026-10-18
- Statut : terminé

## Objectif
Rejouer le transfert MariaDB `_import` -> PostgreSQL dev (`postgres/sf_mangatrackr_app`) avec le script D3, régénérer les inventaires au format avec `auto_increment`, prouver la parité et l'idempotence, QA verte.

## Ce qui a été fait
- Rejeu 1 : `CONFIRM_TARGET=postgres/sf_mangatrackr_app MARIADB_PASSWORD=<env> sh rejouer-transfert.sh <scratchpad>/r1` (utilisateur MariaDB `mangatrackr`, lecture seule ; mot de passe uniquement en variable d'environnement de la commande, jamais écrit). Code 0.
- Rejeu 2 identique vers `<scratchpad>/r2`. Code 0.
- Inventaires du rejeu 1 copiés dans `rapports/032-inventaire-{mariadb,pgsql,mariadb-strict,pgsql-strict}.json` ; les `022-inventaire-*.json` sont conservés.
- QA : `make qa-php-stan`, `make test-only`, `doctrine:schema:validate`, `doctrine:migrations:diff` (dev).
- Aucun script, migration, SKILL.md, code ou etat.md modifié.

## Résultats
| Preuve | Résultat |
|---|---|
| Rejeu 1 et 2 | code 0 chacun |
| Parité tolérante et stricte | 22 tables OK (lignes et empreintes), `doctrine_migration_versions` IGNORÉE explicitement (15 vs 1 : baseline unique) ; baseline Version20261018145659 présente ; « Parité complète » x2 par rejeu |
| Séquences D3 | 19 contrôlées, 0 écart (prochaine valeur = max(MAX+1, AUTO_INCREMENT source)) ; ex. manga 7429, release_manga_updates_api 22967, tables vides à 1 |
| Identité rejeu 1 / rejeu 2 | 4 inventaires identiques hors `generated_at` (mariadb, pgsql, mariadb-strict, pgsql-strict) |
| `make qa-php-stan` | No errors |
| `make test-only` | OK (226 tests, 580 assertions) |
| `doctrine:schema:validate` (dev) | schéma en phase avec le mapping |
| `doctrine:migrations:diff` (dev) | « No changes detected » (code 1 = rien à générer), aucun fichier créé |

Comptages notables : manga 7412, manga_manga_type 17684, release_manga_updates_api 22966, manga_tenrai_api 5679, app_user 6 (comptages uniquement).

## Écarts, risques, points non vérifiés
- Sur ces données AUTO_INCREMENT = MAX+1 pour les 19 séquences : D3 donne les mêmes valeurs que MAX+1 ; les cas AI > MAX+1 restent validés uniquement par simulation (rapport 029).
- Le dev PostgreSQL a été écrasé (autorisé). `make test-only` utilise la base de test uniquement via le Makefile.
- Pas de serveur Symfony ni requête applicative sur le dev. Les JSON ne contiennent que comptages et empreintes.

## Recommandation pour la suite
T7 peut être clôturée côté données du dev. Reste, à la répétition de bascule, de cocher l'option AUTO_INCREMENT de l'export phpMyAdmin (plan 6.3) et de contrôler AUTO_INCREMENT vs MAX+1 dans `_import`.
