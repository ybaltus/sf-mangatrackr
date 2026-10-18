# 003 — migration-importeur — Import du dump de prod dans MariaDB locale

- Tâche : T2
- Date : 2026-10-18
- Statut : terminé

## Objectif
Importer `ressources/u556516513_20261018_mangasync.sql` (dump phpMyAdmin, prod MariaDB
11.8.9) dans la base locale `sf_mangatrackr_app_import` (MariaDB 10.11.5) et dire si
l'extraction est correcte.

## Ce qui a été fait
1. SHA-256 du dump recalculé : `967d0750…e1465286`, **identique** à `001-attendu.json`
   → dry-run non refait, `001-attendu.json` sert de référence `--expected` (pas de
   `003-attendu.json`, conformément à la consigne du chef).
2. Recherche préalable des syntaxes 11.x dans le dump : aucune collation `uca1400`,
   uniquement `utf8mb4_unicode_ci` (22 tables), `utf8mb4_bin` (7 colonnes JSON) et
   `utf8mb3_unicode_ci` (1 table). Seuls commentaires versionnés : 7 × `/*!40101`.
   Aucune transformation du dump nécessaire.
3. Connectivité PDO vers `database:3306` (root) : OK, serveur 10.11.5-MariaDB.
4. Import : `import-dump.php … --db=sf_mangatrackr_app_import --create-db` → 442
   instructions exécutées sans erreur (le script s'arrête en code 4 à la première erreur ;
   il est allé jusqu'au rapport final).
5. Inventaire : `inventaire.php --dsn=…sf_mangatrackr_app_import --out=rapports/003-inventaire-mariadb-import.json`
   puis `--expected=rapports/001-attendu.json` → code de sortie 0.
6. Migrations : `doctrine_migration_versions` de la base importée comparée à `ls migrations/`.
7. `doctrine:schema:validate` avec `DATABASE_URL` surchargée en variable d'environnement
   (`serverVersion=10.11.5-MariaDB`), puis `doctrine:schema:update --dump-sql` (lecture
   seule, sans `--force`) pour identifier l'écart.
8. Script d'intégrité dans le scratchpad (`integrite.php`, hors dépôt) : orphelins sur
   chaque clé étrangère déclarée dans `information_schema`, dates à zéro, validité UTF-8,
   motifs de double encodage, caractères de contrôle, JSON valide, lignes `id = 0`.

Fichiers créés : `rapports/003-inventaire-mariadb-import.json`, ce rapport, une ligne dans
`journal.md`. Aucune modification de `src/`, `config/`, `migrations/`, Docker, ni des bases
`sf_mangatrackr_app` / `sf_mangatrackr_app_test`.

## Résultats

### Dump ↔ base (23 tables, 71 029 lignes)
| table | dump | base | résultat |
|---|---:|---:|---|
| doctrine_migration_versions | 15 | 15 | OK |
| editor | 550 | 550 | OK |
| fantrad | 562 | 562 | OK |
| manga | 7 412 | 7 412 | OK |
| manga_editor | 4 795 | 4 795 | OK |
| manga_fantrad | 1 726 | 1 726 | OK |
| manga_manga_type | 17 684 | 17 684 | OK |
| manga_manga_updates_api | 2 038 | 2 038 | OK |
| manga_release_config | 0 | 0 | OK |
| manga_statistic | 7 415 | 7 415 | OK |
| manga_status | 7 | 7 | OK |
| manga_tenrai_api | 5 679 | 5 679 | OK |
| manga_type | 82 | 82 | OK |
| manga_user_track | 68 | 68 | OK |
| messenger_messages | 0 | 0 | OK |
| release_manga_updates_api | 22 966 | 22 966 | OK |
| status_track | 6 | 6 | OK |
| text_content_page | 1 | 1 | OK |
| user | 6 | 6 | OK |
| user_invitation_code | 5 | 5 | OK |
| user_news | 6 | 6 | OK |
| user_reset_password | 0 | 0 | OK |
| user_track_list | 6 | 6 | OK |

### Migrations
15 versions dans le dump, de `Version20231120150811` à `Version20260925000000` :
**identiques** aux 15 fichiers de `migrations/` (diff vide). La prod est à jour, y compris
le renommage `manga_jikan_api` → `manga_tenrai_api`.

### doctrine:schema:validate
- Mapping : **OK**.
- Base : **non synchronisée**, 5 instructions proposées, toutes héritées des migrations
  (donc présentes aussi sur toute base construite par `make sf-dmm`, pas une dérive de prod) :
  - `manga_tenrai_api` : index unique `uniq_a2733e8c7b6461` à renommer en
    `UNIQ_8A6250027B6461` — la migration `Version20260925000000` a renommé la table sans
    renommer l'index (nom haché dérivé de l'ancien nom de table).
  - `messenger_messages` : 3 index simples (`queue_name`, `available_at`, `delivered_at`)
    créés par `Version20231120150811` à remplacer par un index composite
    `(queue_name, available_at, delivered_at, id)` — évolution du schéma Messenger récent.
  Rien n'a été corrigé.

### Intégrité
- Clés étrangères réelles : 19 ; **0 ligne orpheline** sur chacune.
- Dates : 41 colonnes date/datetime/timestamp ; **0 valeur `0000-00-00`** ni jour/mois à zéro.
- Encodage : 58 colonnes texte ; **0 valeur UTF-8 invalide, 0 motif de double encodage
  (mojibake), 0 caractère de contrôle / U+FFFD**.
- JSON : 7 colonnes `longtext utf8mb4_bin` (alias JSON MariaDB, 7 contraintes CHECK
  `json_valid` importées) ; 0 valeur JSON invalide.
- Lignes `id = 0` (risque lié à `NO_AUTO_VALUE_ON_ZERO`) : aucune.

## Écarts, risques, points non vérifiés
- `doctrine_migration_versions` est en `utf8mb3_unicode_ci` (seule table hors utf8mb4) :
  sans incidence sur les données (identifiants ASCII), à ignorer ou à normaliser lors de la
  baseline PostgreSQL.
- Écart de version 11.8.9 → 10.11.5 : aucune incompatibilité rencontrée ; le dump n'utilise
  pas de syntaxe propre à 11.x. Pour la suite, `schema:validate` a été fait avec
  `serverVersion=10.11.5-MariaDB` (version réelle locale), pas 11.8.9.
- Le contrôle de contenu porte sur les comptages et l'intégrité ; l'empreinte normalisée par
  table est dans `003-inventaire-mariadb-import.json` mais n'a pas d'équivalent côté dump
  (le dry-run ne compte que les lignes) : la fidélité valeur par valeur n'est garantie que
  par l'exécution sans erreur des INSERT.
- Les deux écarts de schéma Doctrine existeront aussi dans le schéma PostgreSQL si on le
  génère depuis le mapping : ils disparaîtront naturellement avec une nouvelle baseline.

## Verdict
**Extraction correcte** : import sans erreur, 23/23 tables à parité (71 029 lignes),
migrations du dump = `migrations/`, aucun orphelin, aucune date nulle, encodage UTF-8 sain.

## Recommandation pour la suite
- Utiliser `sf_mangatrackr_app_import` et `003-inventaire-mariadb-import.json` comme
  source de référence pour la migration PostgreSQL (`inventaire.php --compare=…`).
- Décider (chef / auditeur) si la baseline PostgreSQL corrige l'index `manga_tenrai_api` et
  les index `messenger_messages`, ou si une migration MariaDB dédiée est d'abord créée.
