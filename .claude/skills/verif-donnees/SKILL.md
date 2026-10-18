---
name: verif-donnees
description: Inventorier et comparer le contenu de bases MariaDB et PostgreSQL de MangaTrackr (nombre de lignes et empreinte normalisée par table) pour prouver qu'un import ou un transfert de données est complet. À utiliser après un import de dump ou une migration MariaDB → PostgreSQL.
---

> **Archive** : nécessite un serveur MariaDB externe et `pdo_mysql`, retirés de l'infra en T8
> (rapport `docs/chantiers/migration-postgresql/rapports/035-migration-schema-retrait-mariadb.md`).

# Vérifier la parité des données

Outil : `.claude/skills/verif-donnees/scripts/inventaire.php` (PDO ; `pdo_pgsql` présent,
`pdo_mysql` requis pour MariaDB, absent de l'image depuis T8).

## Commandes
- Inventaire d'une base (JSON : lignes, SHA-256 normalisé, clé primaire, colonnes) :
  ```
  php .claude/skills/verif-donnees/scripts/inventaire.php --dsn="mysql:host=database;port=3306;dbname=sf_mangatrackr_app_import" --user=root --password=… --out=<fichier>.json
  php .claude/skills/verif-donnees/scripts/inventaire.php --dsn="pgsql:host=postgres;port=5432;dbname=sf_mangatrackr_app" --user=… --password=… --out=<fichier>.json
  ```
- Comparer une base aux comptages d'un dump (sortie du dry-run de `import-dump-mariadb`) :
  `… --expected=<attendu>.json --inventory=<inventaire>.json`
- Comparer deux bases (ex. MariaDB importée ↔ PostgreSQL migrée) :
  `… --compare=<mariadb>.json,<pgsql>.json [--rename=user:app_user] [--ignore=<table>] [--require-baseline=<Version>]`

Options de `inventaire.php` :
- `--password` est déconseillé (visible dans la liste des processus) : préférer la variable
  `INVENTAIRE_DB_PASSWORD`.
- `--strict` : empreinte sur les valeurs brutes lues en texte (NULL → `\N`, booléens 1/0, aucun
  réencodage JSON ni arrondi). Les deux inventaires comparés doivent être du même mode (sinon code 2).
- `--rename=gauche:droite` : table renommée côté cible (ex. `user:app_user`, ADR 0012 D2).
- `--ignore=<table>` (répétable ou séparé par des virgules) : exclut la table de la parité ; elle est
  affichée `IGNORÉE`, jamais en silence. Table inconnue = erreur 2.
- `--require-baseline=<Version>` : la liste des versions de `doctrine_migration_versions` du fichier de
  droite doit contenir cette version (nom nu ou FQCN). C'est le contrepoids de `--ignore=doctrine_migration_versions`.
- L'inventaire MariaDB enregistre `AUTO_INCREMENT` par table ; l'inventaire PostgreSQL compare les séquences à la règle D3 :
  prochaine valeur attendue = `GREATEST(MAX(id)+1, AUTO_INCREMENT source)` (mode strict inclus). `AUTO_INCREMENT` source
  absent pour une table à séquence = écart. Un inventaire MariaDB ancien (sans `auto_increment`) est à régénérer.

Code de sortie de `inventaire.php` : 0 = parité, 1 = écart réel (listé table par table), 2 = erreur
d'usage ou de lecture, 3 = connexion impossible ou exception inattendue (messages génériques, sans DSN
ni mot de passe).

## Transfert et rejeu complet (MariaDB `_import` → PostgreSQL)
- `scripts/transfert-mariadb-pgsql.php` : vide puis recharge la cible dans une transaction, recale les
  séquences à `GREATEST(MAX(id), AUTO_INCREMENT_source - 1)` (D3, lu dans `information_schema.tables` de la source) après le COMMIT.
  `AUTO_INCREMENT` n'existe dans `_import` que si l'option AUTO_INCREMENT de l'export phpMyAdmin était cochée. Simulation : `--dry-run` (ROLLBACK ; attention, le TRUNCATE
  verrouille la cible pendant l'essai).
- `scripts/rejouer-transfert.sh <dossier>` (variables `MARIADB_PASSWORD`, `DATABASE_URL` = cible,
  `CONFIRM_TARGET`) : transfert, 4 inventaires (tolérant et `--strict`), deux comparaisons.
  Rejeu en dev : `CONFIRM_TARGET=postgres/sf_mangatrackr_app MARIADB_PASSWORD=… sh rejouer-transfert.sh <dossier>`.

Garde-fous du transfert (cible DÉTRUITE) :
- Hôte ET base de la cible (lus dans le DSN réellement utilisé) doivent égaler les valeurs attendues :
  `--expected-host` / `--expected-db`, sinon `TRANSFER_EXPECTED_HOST` / `TRANSFER_EXPECTED_DB`, sinon
  `postgres` / `sf_mangatrackr_app` (dev). Toute autre cible (répétition, production) exige de les définir
  explicitement. Une base `_test` ou de même nom que la source est toujours refusée.
- Confirmation de l'appelant, jamais automatique : `CONFIRM_TARGET=<hôte>/<base>` (ou
  `--confirm-target=`) identique à la cible réelle, y compris pour `--dry-run`. Le `.sh` ne l'invente pas
  et ne passe `--yes --replace-existing` qu'après l'avoir vérifiée ; le PHP la revérifie.
- `--yes` ET `--replace-existing` restent obligatoires pour le PHP ; hôte, base et comptages de la
  cible sont affichés avant le TRUNCATE.
- Source en lecture seule ; `doctrine_migration_versions` jamais touchée ; aucun DDL.
- Limite : le contrôle porte sur l'hôte/la base écrits dans le DSN, pas sur l'identité du serveur
  (un alias DNS ou un tunnel vers la production sous le nom `postgres` passerait).
- Une FK auto-référente ou vers une table hors périmètre est signalée sur stderr (AVERTISSEMENT).

Codes de sortie de `transfert-mariadb-pgsql.php` : 0 succès ; 2 usage ; 3 échec avec ROLLBACK (cible
inchangée), connexion impossible ou exception inattendue ; 4 COMMIT fait mais séquences non recalées
(rejouer) ; 5 cible refusée par le garde-fou (hôte, base, `_test`, confirmation absente ou différente) ; 6 `AUTO_INCREMENT` source NULL/absent pour une table à séquence
(rien modifié : réimporter le dump avec l'option AUTO_INCREMENT cochée).
Codes de `rejouer-transfert.sh` : 0 transfert + parité des deux passes + baseline présente ;
1 écart réel ; 2 usage ; 3 échec du transfert, connexion ou exception ; 4 séquences à recaler ;
5 cible refusée ; 6 AUTO_INCREMENT source absent (le code 255 n'existe plus).

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
