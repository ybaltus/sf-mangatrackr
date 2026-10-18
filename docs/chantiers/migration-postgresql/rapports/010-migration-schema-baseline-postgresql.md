# 010 — migration-schema — Baseline PostgreSQL 18

- Tâche : T4.4
- Date : 2026-10-18
- Statut : terminé (`make test` non lancé tel quel, voir Écarts)

## Objectif
Créer une baseline PostgreSQL unique depuis le mapping (D1), archiver les 15 migrations MySQL hors
du chemin chargé, inclure `unaccent`, les 2 index hérités corrigés, l'unicité sur `LOWER(email)`
de `app_user` et un garde-fou de plateforme. Ensuite : vérifier `migrate`, `validate`, `diff` et
la QA sur PostgreSQL.

## Ce qui a été fait
Toutes les commandes ont été lancées avec une surcharge explicite, car le conteneur exporte encore
l'URL MySQL :
`export DATABASE_URL='postgresql://mangatrackr:ChangeMe@postgres:5432/sf_mangatrackr_app?serverVersion=18&charset=utf8'`
(mot de passe par défaut de `.env`, pas un secret ; suffixe `_test` ajouté par `doctrine.yaml` en
`APP_ENV=test`).

1. **Archivage** : `mv migrations/Version*.php migrations-archive-mariadb/` (simple `mv`, index git
   non touché ; 15/15 fichiers identiques octet par octet à `HEAD`, vérifié par `cmp`).
   Emplacement **hors de `migrations/`** et non `migrations/archive-mariadb/` : l'ADR 0012 (D1)
   impose « hors du répertoire `migrations/`, pour ne pas dépendre du finder ». Un sous-dossier ne
   serait ignoré que grâce au `GlobFinder` non récursif (rapport 004), et serait chargé si l'on
   passait un jour à `organize_migrations` (`RecursiveRegexFinder`). `doctrine_migrations.yaml`
   est donc inchangé ; PHPStan et cs-fixer n'analysent pas ce dossier.
2. **Génération** : `doctrine:migrations:diff` sur la base dev vide →
   `migrations/Version20261018145659.php` (schéma Messenger compris), puis compléments écrits à la main :
   - `abortIf` (dans `up()` et dans `down()`) si la plateforme n'est pas `PostgreSQLPlatform` ;
   - `CREATE EXTENSION IF NOT EXISTS unaccent` en tête de `up()` ;
   - `CREATE UNIQUE INDEX uniq_app_user_email_lower ON app_user (LOWER(email))` ;
   - `down()` : `CREATE SCHEMA public` retiré (artefact DBAL), extension conservée (commentaire :
     elle a pu exister avant la baseline) ; description et docblock explicatifs.
3. **Diff parasite `CREATE SCHEMA public`** : un 2ᵉ `migrations:diff` sur la base migrée générait
   une migration au `up()` vide et un `down()` contenant `CREATE SCHEMA public`. C'est un bogue connu
   de DBAL 3 sur PostgreSQL : le schéma `public` est vu en base mais pas dans le mapping. Corrigé
   par `src/EventListener/PostgreSQLDefaultSchemaListener.php` (`#[AsDoctrineListener(postGenerateSchema)]`,
   `final`, qui ajoute le namespace `public` au schéma généré, sur PostgreSQL seulement). La
   migration parasite (non suivie) a été supprimée.
4. **Rejeu** : base dev `migrations:execute --down` (ce qui teste aussi le `down()`) puis `migrate` ;
   base test `migrate` (la table `doctrine_migration_versions` laissée en T4.3 a été réutilisée).
5. **QA** : `make qa-php-cs-fixer`, `make qa-php-stan`, chargement des fixtures, `make test-only`
   (×4), comptages avant/après.

## Résultats
| Vérification | dev (`sf_mangatrackr_app`) | test (`sf_mangatrackr_app_test`) |
|---|---|---|
| `doctrine:migrations:migrate` réel | OK, 71 requêtes | OK, 71 requêtes |
| `doctrine:schema:validate` (mapping + synchro) | OK / OK | OK / OK |
| `doctrine:migrations:diff` | `No changes detected` | `No changes detected` |
| `schema:update --complete --dump-sql` | rien à faire | rien à faire |
| `\dx` | `unaccent 1.1` (+ `plpgsql`) | `unaccent 1.1` (+ `plpgsql`) |
| `SELECT unaccent('Évangélion')` | `Evangelion` | `Evangelion` |
| Tables `public` | 23 (22 + `doctrine_migration_versions`) | 23 |

- **`unaccent` créée par le rôle `mangatrackr`** (non superutilisateur) pendant la migration : le
  droit du propriétaire de la base suffit (extension *trusted*). La vérification 6 de T4.2 est donc
  confirmée.
- Index (`pg_indexes`, identiques dans les deux bases) : `uniq_8a6250027b6461` sur
  `manga_tenrai_api (manga_id)` (écart 1 corrigé) ; `idx_75ea56e0…` composite
  `messenger_messages (queue_name, available_at, delivered_at, id)` (écart 2 corrigé) ;
  `uniq_88bdf3e9e7927c74 (email)` et `uniq_app_user_email_lower (lower(email::text))` sur `app_user`.
- **Pourquoi `LOWER(email)` ne crée pas de diff** : l'introspection des index de DBAL 3
  (`PostgreSQLSchemaManager::_getPortableTableIndexesList`) retrouve les colonnes par
  `pg_index.indkey`. Pour un index d'expression, cette valeur vaut `0` : aucune colonne n'est
  trouvée, donc l'index n'est pas remonté et le comparateur ne propose pas de le supprimer.
  C'est vérifié par l'expérience (diff et `schema:update` vides avec l'index présent). L'index
  simple `email` (`unique: true` dans le mapping) reste en place pour que le mapping et la base
  concordent. Il est redondant avec l'index `LOWER`, ce qui est sans conséquence.
- **Garde-fou** : `DATABASE_URL='sqlite:///:memory:' doctrine:migrations:migrate --dry-run` →
  `failed during Execution … Baseline réservée à PostgreSQL (ADR 0012)`. Testé sur SQLite en
  mémoire pour ne pas toucher MariaDB.
- `make qa-php-cs-fixer` : 0 fichier corrigé sur 179. `make qa-php-stan` : `[OK] No errors`.
- **Tests sur PG** : `OK (192 tests, 541 assertions)` à chacun des 4 passages, c'est-à-dire autant
  qu'en référence MariaDB. Aucun test en échec, donc rien à transmettre au `developpeur` pour la
  portabilité SQL.

Commande exacte des tests (équivalent de `make test` sans le drop/create) :
```
export DATABASE_URL='postgresql://mangatrackr:ChangeMe@postgres:5432/sf_mangatrackr_app?serverVersion=18&charset=utf8'
APP_ENV=test php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction --env=test
make test-only
```

## Écarts, risques, points non vérifiés
- **`make test` non lancé tel quel** : sa cible `sf-rdb-test` exécute `doctrine:database:drop`,
  interdit par la consigne et refusé par `.claude/settings.json`. Je l'ai remplacé par la séquence
  ci-dessus sur une base de test neuve, ce qui revient au même. Le drop puis create de `make test`
  sur PG reste donc **non vérifié** (rôle `CREATEDB`, collation héritée de `template1`, échec si
  une session est ouverte sur la base). À lancer par l'utilisateur ou par le chef.
- **Données résiduelles des tests (préexistant, sans lien avec PG)** : à chaque passage,
  `make test-only` ajoute 2 `app_user` (`Tester`, `userInvit`) et 2 `user_invitation_code`. Les
  responsables sont `CreateUserCommandTest`, `InvitationControllerTest` et
  `EasyAdminSubscriberTest`, qui ne nettoient pas. Après fixtures (3 utilisateurs, 4 mangas), un
  passage laisse 5 utilisateurs, 54 mangas (import réseau `InitDataCommandTest`, stable ensuite)
  et 1 `user_reset_password`. Les tests restent verts en rejeu, mais l'ADR 0007 n'est pas
  respectée : à confier au `testeur` ou au `developpeur`. Non corrigé (hors périmètre).
- **Dépendance à DBAL 3** : avec DBAL 4, l'introspection des index d'expression pourrait changer.
  Il faudrait alors revérifier qu'aucun diff ne propose `DROP INDEX uniq_app_user_email_lower` ;
  `schema_filter` ne filtre pas les index. Le listener `public` sera peut-être inutile en DBAL 4.
- Le listener s'exécute pour toute génération de schéma (diff, validate, `schema:update`) ; il ne
  fait rien hors PostgreSQL.
- Le conteneur `app` n'est pas encore recréé : sans surcharge, `make test-only` et la console
  tournent toujours sur MariaDB, qui n'a ni la baseline ni le mapping `app_user`, et y sont donc
  rouges.
- Changements arrivés en parallèle et laissés tels quels : `.gitignore` modifié et
  `docs/chantiers/migration-postgresql/.php-cs-fixer.cache` supprimé de l'index.
- Les fichiers déplacés apparaissent pour git comme 15 suppressions plus un dossier non suivi ;
  `git add -A` les fera reconnaître comme des renommages au commit.
- La base `sf_mangatrackr_app_import` et MariaDB n'ont pas été touchées.

## Recommandation pour la suite
- Chef : faire recréer le conteneur `app` par l'utilisateur (T4.3), puis lancer `make test` une fois
  sans surcharge pour valider le drop/create sur PG.
- T4.5 est largement couvert ici (migrate, diff vide, validate) ; reste la contre-vérification par
  le chef ou le relecteur.
- T4.6 (CI) : `postgres:18` avec `POSTGRES_INITDB_ARGS` ICU `fr`, `DB_PASSWORD`, puis
  `migrate` + fixtures + PHPUnit.
- T4.8 peut s'appuyer sur `unaccent`, présente dans toutes les bases créées par la baseline.
- Clôture : `CLAUDE.md` (règle des migrations : renvoi à l'exception de l'ADR 0012 et à
  `migrations-archive-mariadb/`) ; tableau du rapport 004.
