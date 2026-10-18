# ADR 0012 : migration vers PostgreSQL 18

- Statut : Acceptée
- Date : 2026-10-18
- Contexte lié : chantier `migration-postgresql` (rapports 002 et 003), ADR 0011

## Contexte
La production doit quitter Hostinger (MariaDB 11.8) pour le homelab de l'utilisateur, sur
**PostgreSQL 18**. L'audit (rapport 002) relève 30 constats, dont 8 bloquants. Les principaux :
- les 15 migrations Doctrine sont du SQL MySQL brut (`ENGINE = InnoDB`, backticks, `CHANGE`,
  `RENAME TABLE`), non rejouables sur PostgreSQL sans les réécrire ;
- la table `user` est un mot réservé PostgreSQL : DBAL la cite dans le DDL, mais pas l'ORM dans
  le DQL et les persisters (`SELECT ... FROM user` échoue) ;
- sans configuration, ORM 2.20 choisit la stratégie SEQUENCE sur PostgreSQL (avec une
  dépréciation) ; les ID de prod ont des trous (lignes supprimées) ;
- MariaDB (`utf8mb4_unicode_ci`) compare sans tenir compte de la casse ni des accents ;
  PostgreSQL tient compte des deux (recherche du catalogue, dédoublonnage MangaUpdates, emails),
  et le tri des titres dépend de la collation de la base.

L'import du dump (rapport 003) a aussi montré deux écarts d'index hérités entre la prod et le
mapping : l'index unique de `manga_tenrai_api` jamais renommé après le renommage de la table
(`Version20260925000000`), et trois index simples sur `messenger_messages` au lieu de l'index
composite `(queue_name, available_at, delivered_at, id)` du schéma Messenger actuel.

## Décision
**Cible et coexistence**
- PostgreSQL 18, production future sur le homelab. Hostinger reste en MariaDB jusqu'à la
  bascule.
- **D6** : MariaDB et PostgreSQL coexistent (services Docker, `pdo_mysql` et `pdo_pgsql`)
  jusqu'à la fin du transfert des données (T5). Le schéma de la prod Hostinger est **gelé** et
  la branche PostgreSQL n'y est pas déployée avant la bascule.

**Schéma et migrations**
- **D1** : une **baseline PostgreSQL unique**, générée sur une base PostgreSQL vide (listener de
  schéma Messenger compris) et gardée par un `abortIf` si la plateforme n'est pas PostgreSQL. Les
  15 migrations MySQL sont **archivées hors de `migrations_paths`** (hors du répertoire
  `migrations/`, pour ne pas dépendre du finder de Doctrine Migrations) ; elles restent dans
  l'historique git. `doctrine_migration_versions` n'est pas transférée.
- **Exception unique à la règle « ne jamais modifier une migration commitée »** (`CLAUDE.md`).
  Justification : ces migrations ne peuvent pas tourner sur PostgreSQL sans être réécrites, ce
  que la règle interdit précisément ; les dédoubler par plateforme reviendrait aussi à les
  modifier, sans bénéfice puisque la base PostgreSQL est créée par la baseline puis remplie par
  import, jamais par rejeu de l'historique. Le contenu des fichiers n'est pas modifié, ils sont
  seulement déplacés. Après la baseline, la règle s'applique de nouveau sans exception.
- Les **deux écarts d'index hérités** sont corrigés par la baseline PostgreSQL (noms et index
  conformes au mapping) ; **aucune migration MariaDB** n'est créée pour eux.
- **D2** : la table `user` est **renommée `app_user`** (`#[ORM\Table(name: 'app_user')]` sur
  l'entité `User`). Le transfert des données fait la correspondance `user` → `app_user`.
- **D3** : identifiants **IDENTITY**, configurés explicitement
  (`identity_generation_preferences` pour `PostgreSQLPlatform` dans `doctrine.yaml` ; `SERIAL` en
  DBAL 3). Après le transfert, chaque séquence est recalée sur
  `GREATEST(MAX(id), AUTO_INCREMENT - 1)` (valeur `AUTO_INCREMENT` du dump), pour ne jamais
  réutiliser un ID supprimé ; tables vides : séquence laissée à 1.
- **D7** : type `json` (pas `jsonb`) : texte conservé tel quel, aucune requête sur ces colonnes.
- **D8** : `disable_type_comments: true` dès la baseline (prépare DBAL 4 sans migration de
  commentaires).

**Comparaisons et tri**
- **D4** : recherches **insensibles à la casse et aux accents**. `LOWER()` en DQL, portable
  MariaDB/PostgreSQL (T4.7) ; extension `unaccent` et fonction DQL dédiée côté PostgreSQL (T4.8).
  Les emails sont **normalisés en minuscules** à la frontière (setters, authentification, reset).
- **D5** : collation **ICU `fr`**, fixée à la création de la base (dev, test, prod).

**Transfert**
- **D9** : transfert par **script PHP/DBAL** à deux connexions (source MariaDB importée, cible
  PostgreSQL), pas `pgloader`. Le schéma est créé par la baseline Doctrine, jamais par l'outil.

## Conséquences
- Dès la fusion de la baseline, `migrations/` ne s'applique plus à MariaDB : la base MariaDB de
  dev/test ne suit plus le mapping (`app_user`, index), et les tests tournent sur PostgreSQL.
  MariaDB ne sert plus que de source au transfert (base `sf_mangatrackr_app_import`) et de
  retour arrière jusqu'à la bascule.
- Aucune évolution de schéma ne peut partir en prod d'ici la bascule (gel D6). Un correctif
  urgent sur Hostinger se ferait depuis `main` sans migration, ou attendrait la bascule.
- Le renommage `app_user` doit être pris en compte partout où le nom de table apparaît en SQL :
  script de transfert, recalage des séquences, `inventaire.php --compare` (correspondance de
  noms), requêtes manuelles.
- Les ID insérés explicitement et le recalage des séquences préservent les ID référencés par le
  `localStorage` de la scanthèque (`manga_user_track`).
- La recherche par accents dépend de l'extension `unaccent` : elle doit être créée par la
  baseline (ou une migration PostgreSQL) et disponible en dev, test, CI et prod.
- La collation ICU impose une création de base explicite (script d'init Docker, CI, homelab) :
  une base créée avec la locale par défaut trierait les titres différemment.
- Infra à adapter (T4.2 à T4.6) : service `postgres:18` (volume neuf sur `/var/lib/postgresql`),
  `pdo_pgsql` dans les images, `DATABASE_URL` PostgreSQL (`serverVersion=18`), script de création
  de la base de test, CI.
- Documentation : `CLAUDE.md` et `docs/claude/contexte.md` décrivent encore MariaDB ; à mettre à
  jour à la clôture du chantier.
- La bascule elle-même (export final, gel des écritures, retour arrière) fera l'objet d'un ADR
  distinct (T7).
