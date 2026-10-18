# 015 — testeur — validation de la phase T4

- Tâche : T4
- Date : 2026-10-18
- Statut : partiel (tout vert sauf `make qa-all` : étape PHP Insights en erreur, voir écarts)

## Objectif
Valider T4 sur PostgreSQL 18 : `make test` (jamais vérifié), rejouabilité, absence de résidus, QA, cohérence schéma/migrations.

## Ce qui a été fait
Aucune modification de fichier du dépôt (hors ce rapport et le journal). Drop uniquement de `sf_mangatrackr_app_test`.
1. `make test` (lancé 3 fois au total : une 1re exécution dont seule la fin de sortie a été lue, puis 2 complètes).
2. `make test-only` x2, comptages avant/après via un script PHP dans le scratchpad (connexion Doctrine env test).
3. `make qa-all`.
4. `doctrine:schema:validate` et `doctrine:migrations:diff` en dev et en test.

## Résultats
| Vérification | Résultat |
|---|---|
| `make test` (drop, create, migrate, fixtures, PHPUnit) | OK : base supprimée/recréée, 1 migration (Version20261018145659, 71 requêtes), 9 fixtures, **193 tests, 546 assertions** |
| Rôle applicatif | `mangatrackr` : rolsuper = false, rolcreatedb = true, propriétaire de la base de test. Le drop/create fonctionne donc sans superutilisateur |
| `CREATE EXTENSION unaccent` | Passe (migration l.36) : extension `unaccent` présente, propriétaire `mangatrackr` (plpgsql : postgres) |
| `make test-only` n°1 et n°2 | OK, 193 tests / 546 assertions chacun |
| Comptages app_user / user_invitation_code / user_track_list / user_news | avant 3/0/3/3, après n°1 3/0/3/3, après n°2 3/0/3/3 : aucun résidu |
| cs-fixer | 0 fichier modifié sur 181 |
| PHPStan niveau 6 | `[OK] No errors` |
| `symfony security:check` | aucune vulnérabilité connue |
| PHP Insights | exit 1 (voir écarts) ; scores Code 82 %, Complexité 85,7 %, Architecture 64,7 %, Style 92,8 % |
| schema:validate dev et test | in sync |
| migrations:diff dev et test | « No changes detected » (diff vide ; la commande a affiché l'aide faute de migration à générer, aucun fichier créé) |
| 2e `make test` | OK, mêmes chiffres (193 / 546), comptages finaux 3/0/3/3 |

Référence du skill : 169 tests ; 193 aujourd'hui (aucune baisse). Tests réseau (Tenrai, MangaUpdates, InitData) passés, non instables sur ces runs.

## Écarts, risques, points non vérifiés
- `make qa-all` retourne rc=2 à cause de `qa-php-insights` (Makefile:49, Error 1). `phpinsights.php` n'a aucun seuil `min-*` actif ; la sortie signale « PHP Insights needs an internet connection to inspect security issues » et liste des issues de style existantes (non liées à T4). Cause exacte du code de sortie non isolée ; non comparé à un état avant migration. Cs-fixer, PHPStan et security:check, eux, sont verts.
- Les deprecations Doctrine (« identity columns emulated with a sequence », ORM 3.0) sont nombreuses sous PostgreSQL : dette à noter, non bloquante.
- Les comptages portent sur la base de test seule ; la base de dev n'a pas été touchée (seulement lecture par validate/diff).

## Recommandation pour la suite
T4 validable côté tests/QA. Décider si l'échec de PHP Insights (hors réseau / seuils) bloque ; sinon l'exclure de la définition de « qa-all vert » ou le documenter. Planifier le traitement des deprecations d'identity columns (stratégie SEQUENCE/IDENTITY) avant ORM 3.
