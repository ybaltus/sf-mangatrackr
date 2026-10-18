# Homelab : procédure simplifiée (MangaTrackr sur PostgreSQL)

> **Historique.** Procédure de l'import initial (2026-10-18). Pour l'exploitation et la réinstallation
> de la production (VM `apps-dev`, Compose prod, scripts `deploy/`), suivre
> [`docs/deploiement/apps-dev.md`](../../deploiement/apps-dev.md).

- Date : 2026-10-18 (décision de l'utilisateur ; remplace `plan-bascule.md` comme référence)
- Rapport : `rapports/034-migration-donnees-export-homelab.md`
- Hors périmètre ici : reverse proxy, serveur web (non décrits, à configurer côté homelab).

## 1. Exporter la base de dev (machine de dev, depuis `/var/www`)
```
make pg-export
```
Écrit `ressources/mangatrackr_<base>_<AAAAMMJJ-HHMMSS>.dump` (`pg_dump -Fc --no-owner --no-privileges`, droits 600,
lecture seule sur la base). **Le dump contient des données personnelles** (emails, hash de mots de passe,
suivi de lecture) : il est ignoré par git (`/ressources/*.dump`), ne jamais le commiter ni l'afficher ;
le copier par `scp`/`rsync` (SSH), puis le supprimer des machines intermédiaires. Rejouable à volonté.

## 2. Sur le homelab
1. **Code** : `git clone` ou `git pull`, puis la branche voulue (`dev` ou `main` une fois la PR fusionnée).
   `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`.
2. **`.env.local`** (non versionné) :
   `DATABASE_URL="postgresql://<role>:<mot_de_passe>@<hote>:5432/<base>?serverVersion=18&charset=utf8"`
   plus `APP_ENV=prod`, `APP_SECRET` et les autres secrets de l'application. Le mot de passe est
   percent-encodé ; **tout `%` doit être doublé** (`%%40` pour `@`) car la valeur passe par `%env(resolve:)%`.
   (`bin/pg-*.sh` gèrent ce doublement.)
3. **Rôle et base** (superutilisateur, une fois) :
   ```sql
   CREATE ROLE <role> LOGIN PASSWORD '<mot_de_passe>';
   CREATE DATABASE <base> OWNER <role> TEMPLATE template0 ENCODING 'UTF8'
       LOCALE_PROVIDER icu ICU_LOCALE 'fr';
   ```
   Créer la base **vide** : ni migration ni extension à la main. L'extension `unaccent` est dans le dump
   (« trusted » depuis PG 13 : le propriétaire de la base suffit).
4. **Import** : `make pg-import DUMP=ressources/<fichier>.dump CONFIRM_TARGET=<hote>/<base>`
   (ou `CONFIRM_TARGET=… sh bin/pg-import.sh <fichier>`). Restauration atomique ; refuse une base non
   vide (sauf `IMPORT_REPLACE=1`, qui détruit le contenu) et une base `_test`. Sans `make` : même commande
   avec `sh bin/pg-import.sh`. Il faut `pg_restore` en version >= 18.
5. **Migrations** : `php bin/console doctrine:migrations:migrate --no-interaction` doit répondre
   « Already at the latest version » (la baseline `Version20261018145659` est dans le dump). Ne pas le
   lancer avant l'import.
6. **Cache** : `APP_ENV=prod php bin/console cache:clear` (puis droits d'écriture sur `var/`).
7. **Premier contrôle** : `doctrine:migrations:status` (1 exécutée, 0 nouvelle), `doctrine:schema:validate`
   (« in sync »), connexion avec un compte existant, une recherche avec accents (ex. « ele » trouve « Élé… »),
   la scanthèque.

## Compatibilité PostgreSQL
- Serveur **18** : cas prouvé (restauration 18.6 vers 18.6, rapport 034).
- **17** : probablement compatible (non testé). **<= 16** : non garanti ; `pg_dump` 17+ écrit
  `SET transaction_timeout` que ces serveurs rejettent ; le restaurer demanderait de filtrer cette ligne
  via un dump SQL (`pg_restore -f -`). ICU est requis (`LOCALE_PROVIDER icu`).
- Les séquences IDENTITY sont restaurées avec `setval` (19 séquences vérifiées) : pas de recalage à faire.
