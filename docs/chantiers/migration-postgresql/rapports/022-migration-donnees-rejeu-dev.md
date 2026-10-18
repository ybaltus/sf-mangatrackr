# 022 — migration-donnees — Rejeu complet sur la base de dev (phase 2)

- Tâche : T6 phase 2
- Date : 2026-10-18
- Statut : terminé

## Objectif
Rejouer le transfert MariaDB (`_import`) vers la base de dev PostgreSQL `sf_mangatrackr_app`
(écrasement autorisé), prouver la parité (22 tables + baseline + séquences, strict inclus) et
l'identité de deux rejeux, effacer l'écriture involontaire de la recette 019, mettre à jour la doc.

## Ce qui a été fait
- `rejouer-transfert.sh` lancé 3 fois (mot de passe MariaDB lu dans `docker-compose.yml`, passé par
  variable d'environnement, jamais écrit). Les 3 rejeux : code de sortie **0**.
- JSON régénérés rangés : `rapports/022-inventaire-{mariadb,pgsql,mariadb-strict,pgsql-strict}.json`
  (sorties du rejeu 2). Les `017-inventaire-*.json` (ancien format, non suivis par git) sont supprimés ;
  références mises à jour dans 017 et 018. `003-inventaire-mariadb-import.json` conservé (autre usage).
- `SKILL.md` : options (`--strict`, `--rename`, `--ignore`, `--require-baseline`,
  `INVENTAIRE_DB_PASSWORD`), codes de sortie 0/1/2 de l'inventaire, 0/2/3/4 du transfert, 0-4 du `.sh`,
  garde-fous `--yes`/`--replace-existing`/`--allow-target`.
- Rapport 017 : section « Code de sortie » corrigée (0 attendu, plus « 1 attendu »).
- **M4 traité** dans `transfert-mariadb-pgsql.php` : avant tout TRUNCATE, `SHOW server_encoding` doit
  valoir UTF8, et chaque colonne doit appartenir à une combinaison de types source/cible prévue
  (tinyint(1)→boolean, int→integer, bigint, varchar, longtext→text/json, double→double precision,
  datetime→timestamp), sinon code 3 « rien n'a été modifié ». Preuve négative sur une copie du script
  avec la table de types volontairement faussée : refus listant les colonnes, sans modification.
  Cible réelle : UTF8 ; les 8 combinaisons présentes sont toutes autorisées.

## Résultats
| Preuve | Résultat |
|---|---|
| Parité tolérante et stricte (rejeu 1, 2, 3) | 22 tables OK + `doctrine_migration_versions` IGNORÉE (explicite), baseline `Version20261018145659` présente, 19 séquences sans écart |
| Volumes | 71 014 lignes transférées (dont `manga` 7 412, `manga_statistic` 7 415, `release_manga_updates_api` 22 966) |
| Identité des rejeux | les 4 JSON (hors `generated_at`) sont identiques octet pour octet entre rejeu 2 et rejeu 3 (rejeu 1 = rejeu 2 pour les 4 aussi, empreintes comparées) |
| Parité tables en strict | comparaison indépendante des empreintes par table MariaDB/PostgreSQL : 0 écart |
| `manga_statistic` id 332 | source : rating NULL, nb_track 0, nb_view 8, updated_at 2026-09-23 00:48:30 ; cible : ligne identique (TRUNCATE + rechargement a effacé l'écriture de 019) |
| `make qa-php-stan` | OK, 0 erreur |
| `make test-only` | OK, 217 tests, 570 assertions (le test rouge de 020 est corrigé) |
| `doctrine:schema:validate` (dev) | mapping OK, schéma en phase |
| `doctrine:migrations:diff` (dev) | « No changes detected » (aucun fichier créé) |

## Écarts, risques, points non vérifiés
- `doctrine_migration_versions` : 15 lignes à gauche, 1 (baseline) à droite — attendu, exclue de la parité
  avec contrôle de la baseline.
- Le rejeu a détruit toutes les écritures faites en dev depuis le dernier transfert (autorisé).
- Pas de test du dump final avec doublons d'emails à la casse près (reste de 020).
- Le garde `EXPECTED_TARGET_DB` est une constante : adapter pour la production (`--allow-target`).
- Le test négatif M4 a utilisé une copie du script (supprimée) ; pas de test dédié versionné.
- Aucun contrôle sur un `server_encoding` non UTF8 (non reproductible sans base dédiée).

## Recommandation pour la suite
Passer à la phase suivante du chantier (T4.5/T6 clôture) ; pour la bascule finale, réimporter le
dump final dans `_import`, refaire T5.0 puis lancer `rejouer-transfert.sh`.
