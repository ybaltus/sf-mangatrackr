---
name: import-dump-mariadb
description: Importer un dump SQL MariaDB/MySQL (phpMyAdmin, mysqldump) de MangaTrackr dans une base locale dédiée `*_import` et vérifier que l'extraction est complète et cohérente avec les entités Doctrine. À utiliser pour valider une extraction de production ou préparer une source de migration.
---

# Importer et valider un dump MariaDB

Le conteneur `app` n'a pas de client `mysql` et le dump dépasse `max_allowed_packet` : on
passe par `scripts/import-dump.php` (PDO, découpage tenant compte des chaînes et commentaires).

## Garde-fous
- Base cible **toujours** `<DB_NAME>_import` (ex. `sf_mangatrackr_app_import`) : le script
  refuse tout autre nom. Ne jamais importer dans la base de dev ni dans la base de test.
- Les dumps sont dans `ressources/` (ignoré par git, données personnelles) : ne jamais copier
  leur contenu dans un rapport, seulement des comptages.
- Le compte `root` local (mot de passe dans `docker-compose.yml`, environnement de dev) sert
  à créer la base ; le passer via `--password=` ou `IMPORT_DB_PASSWORD`.

## Procédure
1. **Analyse hors ligne** (aucune base nécessaire) :
   `php .claude/skills/import-dump-mariadb/scripts/import-dump.php ressources/<dump>.sql --dry-run --report=docs/chantiers/<chantier>/rapports/<NNN>-attendu.json`
   → nombre d'instructions, tables créées, lignes attendues par table, empreinte SHA-256 du dump.
2. **Connectivité** : `php -r 'new PDO("mysql:host=database;port=3306","root","…");'`.
   Erreur `getaddrinfo` / `Connection refused` → statut `bloqué` : l'utilisateur doit lancer
   depuis l'hôte `docker compose up -d database` (et vérifier que `app` est sur le réseau
   `mangatrackr_network`). Ne pas tenter de contournement.
3. **Import** :
   `php .claude/skills/import-dump-mariadb/scripts/import-dump.php ressources/<dump>.sql --db=sf_mangatrackr_app_import --create-db --password=…`
   (`--recreate` pour rejouer un import : supprime puis recrée la seule base `*_import`).
4. **Vérification des comptages** avec le skill `verif-donnees` :
   inventaire de la base importée puis `--expected` contre le rapport du dry-run.
5. **Cohérence avec le code** :
   - version des migrations : comparer `doctrine_migration_versions` du dump à `ls migrations/` ;
   - `DATABASE_URL="mysql://root:…@database:3306/sf_mangatrackr_app_import?serverVersion=11.8.9-MariaDB&charset=utf8mb4" php bin/console doctrine:schema:validate`
     (la partie mapping doit être OK ; noter tout écart de schéma, sans le corriger) ;
   - intégrité : lignes orphelines sur chaque clé étrangère (`LEFT JOIN … WHERE parent.id IS NULL`),
     valeurs `0000-00-00`, colonnes texte non UTF-8 valides.

## Critère de réussite
Import sans erreur, toutes les tables `OK` en `--expected`, migrations du dump = `migrations/`,
aucun orphelin. Tout écart est documenté dans le rapport avec sa table et son ampleur.
