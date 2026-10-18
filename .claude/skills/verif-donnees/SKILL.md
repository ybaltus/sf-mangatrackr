---
name: verif-donnees
description: Inventorier et comparer le contenu de bases MariaDB et PostgreSQL de MangaTrackr (nombre de lignes et empreinte normalisée par table) pour prouver qu'un import ou un transfert de données est complet. À utiliser après un import de dump ou une migration MariaDB → PostgreSQL.
---

# Vérifier la parité des données

Outil : `.claude/skills/verif-donnees/scripts/inventaire.php` (PDO ; `pdo_mysql` présent,
`pdo_pgsql` requis pour PostgreSQL).

## Commandes
- Inventaire d'une base (JSON : lignes, SHA-256 normalisé, clé primaire, colonnes) :
  ```
  php .claude/skills/verif-donnees/scripts/inventaire.php --dsn="mysql:host=database;port=3306;dbname=sf_mangatrackr_app_import" --user=root --password=… --out=<fichier>.json
  php .claude/skills/verif-donnees/scripts/inventaire.php --dsn="pgsql:host=postgres;port=5432;dbname=sf_mangatrackr_app" --user=… --password=… --out=<fichier>.json
  ```
- Comparer une base aux comptages d'un dump (sortie du dry-run de `import-dump-mariadb`) :
  `… --expected=<attendu>.json --inventory=<inventaire>.json`
- Comparer deux bases (ex. MariaDB importée ↔ PostgreSQL migrée) :
  `… --compare=<mariadb>.json,<pgsql>.json`

Code de sortie 0 = parité, 1 = écarts (listés table par table).

## Interpréter
- `LIGNES DIFFÉRENTES` : transfert incomplet ou doublons → bloquant.
- `CONTENU DIFFÉRENT` à lignes égales : souvent une conversion (booléen, flottant, date avec
  fuseau, JSON, chaîne tronquée, encodage). Isoler la colonne en comparant quelques lignes
  par clé primaire, puis documenter : écart attendu (justifié) ou défaut.
- `COLONNES DIFFÉRENTES` : le schéma cible n'a pas les mêmes noms de colonnes → à expliquer.
- `doctrine_migration_versions` et `messenger_messages` peuvent légitimement différer après
  une nouvelle baseline PostgreSQL : le dire explicitement plutôt que les ignorer en silence.

## Rangement
Les fichiers JSON produits vont dans `docs/chantiers/<chantier>/rapports/` avec le numéro du
rapport (`<NNN>-inventaire-mariadb.json`…). Ils ne contiennent que des comptages et des
empreintes, aucune donnée personnelle.
