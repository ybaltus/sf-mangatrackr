# Plan de bascule : production Hostinger (MariaDB) vers le homelab (PostgreSQL 18)

- Statut : **projet, à valider par l'utilisateur** (ADR 0013, Proposée jusqu'à la répétition)
- Date : 2026-10-18
- Références : ADR 0012 (décisions D1 à D9), rapports 017, 019, 022, 023, 024, 028 (relecture), 029 (séquences D3), skill `verif-donnees`
- Portée : documentation seule. Rien dans ce document n'a été exécuté sur la production ni sur le
  homelab. Les commandes marquées « vérifié » existent dans le dépôt et ont été rejouées en dev
  (rapports 022 et 024) ; les autres sont **à valider en répétition générale**.

Convention : `<…>` = valeur à fournir par l'utilisateur, jamais écrite dans le dépôt. Les champs
`[À COMPLÉTER]` renvoient à la section 14 (questions ouvertes). Aucun secret ne figure ici :
mots de passe et `DATABASE_URL` de production restent dans `.env.local` du serveur cible ou dans un
gestionnaire de secrets, et dans des variables d'environnement saisies à la main pour la durée d'une
commande.

---

## 1. Principes

1. **La source de vérité reste la production MariaDB jusqu'au go.** Rien n'est supprimé côté
   Hostinger avant la fin de la période de conservation (section 12).
2. **Le transfert détruit sa cible** (TRUNCATE des 22 tables de données). Il ne doit viser que (a) la
   base de répétition, (b) la base de production du homelab, en connaissance de cause.
3. **Les écritures sont gelées avant l'export final** : toute écriture après l'export est perdue (le
   retour arrière est alors un retour sur Hostinger, qui n'a pas reçu ces écritures non plus, car
   l'application y est en maintenance).
4. **Une répétition générale complète précède la bascule** : mêmes commandes, sur un dump récent, base
   PostgreSQL distincte, durées mesurées.
5. **Schéma gelé** (ADR 0012 D6) : aucune migration MariaDB ne part en production d'ici la bascule ;
   la branche PostgreSQL n'est pas déployée sur Hostinger.

## 2. Rôles

| Rôle | Tenu par | Responsabilités |
|---|---|---|
| Opérateur de bascule | l'utilisateur (humain) | lance toutes les commandes marquées « humain », saisit `CONFIRM_TARGET`, décide go/no-go |
| Assistant (Claude) | session de chantier | prépare les commandes, lit les sorties que l'utilisateur colle, rédige le compte rendu ; **n'exécute jamais** export, transfert ni déploiement de production, et ne reçoit aucun secret |
| Hébergeur source | Hostinger (phpMyAdmin / hPanel) | export du dump, bascule en lecture seule ou maintenance, conservation |
| Homelab | l'utilisateur | PostgreSQL 18, reverse proxy, TLS, DNS, sauvegardes (voir section 14) |

Une seule personne tient les rôles humains : prévoir la répétition pour que la procédure tienne
sans improvisation.

## 3. Pré-requis (à valider avant la répétition)

### 3.1 Code et schéma
- [ ] Branche PostgreSQL (baseline `migrations/Version20261018145659.php`, `app_user`, IDENTITY,
      `disable_type_comments`) fusionnée vers `main` **mais pas déployée sur Hostinger** ; l'étiquette
      (tag) ou le commit déployé sur le homelab est noté : `[commit/tag : …]`.
- [ ] Schéma de production **gelé** depuis la date `[AAAA-MM-JJ]` : vérifier que `main` ne contient
      aucune migration MariaDB postérieure au dump de référence (15 versions MySQL du rapport 003).
- [ ] CI verte sur PostgreSQL 18 (`.github/workflows/ci.yml`).
- [ ] Correctifs préalables du relecteur 023 traités : I-A (garde-fou de cible, fait, rapport 024),
      I-B (`searchTerm` absent, fait, rapport 025). Mineurs M-A à M-G : non bloquants.

### 3.2 Poste d'exécution du transfert
Le transfert est un script PHP à deux connexions (source MariaDB **importée localement** dans une
base `*_import`, cible PostgreSQL). Il faut donc un poste qui dispose de :
- PHP 8.2 avec `pdo_mysql` **et** `pdo_pgsql` ;
- d'un MariaDB local (ou accessible) pour la base `sf_mangatrackr_app_import` : le skill
  `import-dump-mariadb` refuse tout autre nom que `<base>_import` ;
- d'un accès réseau à la base PostgreSQL cible (production du homelab ou répétition).

`[À COMPLÉTER : poste retenu (conteneur `app` du devcontainer, hôte du homelab, autre) et chemin
réseau vers PostgreSQL de production]`. Si le chemin passe par un tunnel SSH ou un alias DNS, voir
la limite du garde-fou en 8.3.

### 3.3 PostgreSQL de production (homelab)
- PostgreSQL 18, encodage UTF8, **ICU `fr`** (ADR 0012 D5) : voir 7.
- Rôle applicatif **non superutilisateur**.
- Sauvegarde PostgreSQL planifiée et **testée en restauration** avant la bascule
  (`[À COMPLÉTER : outil, fréquence, destination, rétention]`).
- Espace disque : la base importée pèse l'ordre de grandeur du dump (dump de référence : 71 029 lignes, 23 tables, soit 71 014 lignes de données + 15 versions de migration) ;
  prévoir une marge confortable `[À COMPLÉTER : volume disponible]`.

### 3.4 Accès et sécurité
- Dump final : le fichier contient des données personnelles. Stockage chiffré ou à accès restreint,
  hors dépôt (`.gitignore` ignore `/ressources/*.sql`, `*.sql.gz` et `*.zip` ; tout autre format déposé dans `ressources/`, JSON ou CSV, n'est pas ignoré : le vérifier avec `git check-ignore -v <fichier>`), suppression planifiée (section 12).
- Mots de passe MariaDB et PostgreSQL : variables d'environnement saisies à la main, jamais en
  argument de commande, jamais dans le dépôt, jamais collés à l'assistant.
- Plan de communication aux utilisateurs de la maintenance `[À COMPLÉTER : canal, message]`.

## 4. Répétition générale (J-7 à J-2)

But : prouver la procédure complète, mesurer les durées, détecter les surprises sur des données
récentes. **Elle ne touche pas la production** : base PostgreSQL de répétition distincte
(ex. `<base>_repetition`) sur le homelab ou en local.

1. Exporter un dump de production à chaud (section 6.2, sans gel) et l'importer dans la base
   `*_import` (section 6.4).
2. Créer la base PostgreSQL de répétition (section 7) et lancer les migrations (section 8).
3. Lancer le transfert avec les garde-fous de répétition :
   `TRANSFER_EXPECTED_HOST=<hôte répétition>` et `TRANSFER_EXPECTED_DB=<base répétition>`
   (section 9). Le défaut `postgres` / `sf_mangatrackr_app` n'est **jamais** celui de la répétition
   homelab.
4. Contrôle de parité complet (section 10) et détection des doublons d'emails (section 5).
5. Recette complète sur la base de répétition (section 11), **avec écritures** cette fois (la recette
   019 était en lecture seule).
6. Rejouer le transfert une seconde fois : les inventaires doivent être identiques (rejeu 2 = rejeu 3
   en rapport 022).
7. Simuler le retour arrière (section 12) sur le papier, et **tester la restauration** d'une
   sauvegarde PostgreSQL.
8. Relever les durées : export, import `_import`, migration, transfert (rapport 017 : 1,3 s pour
   71 014 lignes en dev local), parité, recette. Elles dimensionnent la fenêtre de maintenance
   (section 13).
9. Compte rendu de répétition : écarts, correctifs, décision de poursuivre.

Critère de sortie : parité complète aux deux passes, recette verte, durées connues, aucun point
ouvert de la section 14 resté sans réponse. Sinon, nouvelle répétition.

## 5. Contrôle préalable : doublons d'emails à la casse près (AVANT migration)

La baseline crée `uniq_app_user_email_lower` (index unique sur `LOWER(email)`) et l'application
normalise les emails en minuscules (ADR 0012 D4). Si la source contient deux emails égaux à la casse
près, **le transfert échoue** à l'insertion (ROLLBACK, cible inchangée, code 3). À détecter avant, sur
le dump final importé dans `_import` (lecture seule). En principe l'unicité MariaDB
(`utf8mb4_unicode_ci`, insensible à la casse) empêche déjà ces doublons côté source ; le contrôle reste
obligatoire (rapport 019 : 0 doublon sur le dump de référence `[date du dump à confirmer]`, à refaire
sur le dump final). Le script de
transfert ne modifie pas les données : les emails ne sont pas passés en minuscules en base.

Requêtes (à exécuter par l'utilisateur sur `_import`, résultat = comptage seulement, ne pas coller
d'emails dans un rapport) :

```sql
-- utilisateurs
SELECT COUNT(*) AS groupes_doublons FROM (
  SELECT LOWER(TRIM(email)) AS e FROM `user` GROUP BY LOWER(TRIM(email)) HAVING COUNT(*) > 1
) t;
-- codes d'invitation (index unique sur email ; casse non normalisée par la baseline)
SELECT COUNT(*) AS groupes_doublons FROM (
  SELECT LOWER(TRIM(email)) AS e FROM user_invitation_code GROUP BY LOWER(TRIM(email)) HAVING COUNT(*) > 1
) t;
-- emails non normalisés (information : seront traités à la frontière lors des connexions)
-- comparaison BINAIRE obligatoire : en collation _ci (insensible à la casse, PAD SPACE), `email <> LOWER(email)`
-- est toujours faux et les espaces finaux sont ignorés
SELECT COUNT(*) FROM `user`
WHERE email COLLATE utf8mb4_bin <> LOWER(email) COLLATE utf8mb4_bin
   OR CAST(email AS BINARY) <> CAST(TRIM(email) AS BINARY);
```

Limites de ces requêtes (MariaDB, collation `_ci`) : le `GROUP BY` regroupe aussi les variantes accentuées
(faux positifs possibles, donc prudents) et elles ne prouvent pas ce que l'index `LOWER(email)` de
PostgreSQL rejettera (autres règles de casse). **Le filet réel est la simulation du transfert
(`--dry-run`, section 9.3) jouée en répétition** : une violation de l'index unique y sort en code 3
(ROLLBACK, cible inchangée). Ces requêtes servent à anticiper, pas à prouver.

Décision :
- 0 doublon : continuer.
- ≥ 1 doublon (utilisateurs) : **no-go** tant que la décision n'est pas prise (fusion de comptes,
  suppression du doublon vide, ou renommage) par l'utilisateur, **dans la base de répétition/`_import`
  d'abord**, puis rejouée à l'identique en production le jour J après l'export (jamais en modifiant
  Hostinger avant le gel, sauf accord explicite). Documenter la décision.

Note : `user_invitation_code.email` n'a pas d'index sur `LOWER(email)` dans la baseline ; vérifier
quand même, puis noter si l'application la traite en minuscules (`[À VÉRIFIER dans la répétition]`).

## 6. Jour J : sauvegarde, gel des écritures, export final MariaDB

Heure de début : `[AAAA-MM-JJ hh:mm]` ; fenêtre prévue : `[…]` (section 13).

### 6.1 Sauvegarde de sécurité (avant tout)
1. Sauvegarde Hostinger indépendante (outil de l'hébergeur ou export phpMyAdmin complet), stockée à
   part du dump de bascule. `[À COMPLÉTER : procédure Hostinger retenue]`
2. Noter la date, la taille et l'empreinte : `sha256sum <fichier>` → `[SHA-256 : …]`.

### 6.2 Gel des écritures et page de maintenance
Objectif : plus aucune écriture dans MariaDB entre le gel et l'export (inscriptions, scanthèque,
réinitialisations, compteurs `nb_view` écrits par `MangaStatisticSubscriber`, tâches Messenger,
commandes cron d'import API Tenrai / MangaUpdates).
1. Désactiver les tâches planifiées qui écrivent (cron Hostinger : commandes d'import Tenrai,
   MangaUpdates, worker Messenger) `[À COMPLÉTER : liste exacte des crons]`.
2. Activer la maintenance : page statique ou réponse 503 avec `Retry-After`
   `[À COMPLÉTER : mécanisme sur Hostinger — fichier `maintenance` côté .htaccess, page statique, ou
   coupure du vhost]`. Les GET qui écrivent (compteur de vues) doivent être inclus dans le gel :
   une simple « lecture seule applicative » ne suffit pas, il faut bloquer le trafic applicatif.
3. Vérifier le gel : `SHOW PROCESSLIST` / absence de connexions applicatives, et deux comptages
   (`SELECT MAX(updated_at) …` sur `manga_statistic`, `COUNT(*)` sur `user`, nom MariaDB de la table ; `app_user` n'existe que côté PostgreSQL) pris à 1 minute
   d'intervalle : identiques. Vérifier en répétition que `MAX(updated_at)` de `manga_statistic` bouge
   bien quand `nb_view` s'incrémente (colonne `updated_at` nullable : sinon, comparer `SUM(nb_view)`).
4. Messenger : le transport `async` utilise `LISTEN/NOTIFY` (`use_notify: true`, `config/packages/messenger.yaml`).
   Avant le gel, vider ou traiter la file `messenger_messages` (worker arrêté après consommation) pour ne
   perdre aucun message en attente (vérifications Messenger en répétition : section 8).
5. Les tâches planifiées (point 1) incluent tout ce qui écrit : le gel n'est valide qu'une fois les crons
   et le worker arrêtés.

### 6.3 Export final
1. Export phpMyAdmin (ou `mysqldump`) du schéma **et** des données, format SQL, avec `DROP TABLE`
   / `CREATE TABLE`, jeu de caractères `utf8mb4` : même procédure que le dump du chantier
   (`u556516513_<date>_mangasync.sql`, MariaDB 11.8.9). `[À COMPLÉTER : option exacte retenue]`.
   **Cocher l'option « Ajouter la valeur AUTO_INCREMENT »** (phpMyAdmin, ou comportement par défaut de
   `mysqldump`) : sans elle le dump n'a pas de `AUTO_INCREMENT=` et le recalage des séquences (ADR 0012
   D3, section 9.3) retombe sur `MAX(id)`. Vérifier sa présence au point 6.4 : **go/no-go**.
2. Relever la taille et le SHA-256 du dump final : `sha256sum <dump>.sql`.
3. Copier le dump vers le poste d'exécution dans `ressources/` (ignoré par git). Ne pas l'envoyer à
   l'assistant ni le coller dans un outil tiers.

### 6.4 Import du dump final dans `_import` (skill `import-dump-mariadb`, vérifié en dev)
```
php .claude/skills/import-dump-mariadb/scripts/import-dump.php ressources/<dump-final>.sql \
    --dry-run --report=docs/chantiers/migration-postgresql/rapports/<NNN>-attendu.json
php .claude/skills/import-dump-mariadb/scripts/import-dump.php ressources/<dump-final>.sql \
    --db=sf_mangatrackr_app_import --create-db --recreate --password=…
```
Puis, comme en T5.0 (rapport 017) : inventaire de `_import` et comparaison à l'attendu du nouveau dump
(`inventaire.php … --expected=<attendu>.json --inventory=<inventaire>.json`). **Critère** : toutes les
tables `OK`, les 15 versions de migration du dump = celles archivées (aucune migration postérieure),
aucun orphelin de clé étrangère, aucune date `0000-00-00`, **`AUTO_INCREMENT=` présent dans le dump pour chaque table
concernée (go/no-go renforcé)**. Un simple `grep -c "AUTO_INCREMENT=" <dump>.sql` > 0 ne suffit pas (une seule
table suffirait) et le code 6 du transfert ne rattrape pas un export sans l'option (voir 9.3). Exiger :
1. `grep -c "AUTO_INCREMENT=" <dump>.sql` = nombre attendu : **16** dans le dump de référence (relevé du
   rapport 028 : 16 tables portent l'option sur les 19 tables à séquence ; les 3 autres sont les tables vides du rapport 019,
   sans `AUTO_INCREMENT=` : déduction du relevé 028, à confirmer). Le chiffre du dump final peut différer (table qui se remplit) : il doit
   être expliqué, jamais ignoré. `[À CONFIRMER au jour J sur le dump final]`.
2. Relever sur **Hostinger, avant l'export** (gel en place), `AUTO_INCREMENT` de chaque table à séquence :
   `SELECT table_name, auto_increment FROM information_schema.tables WHERE table_schema = DATABASE() AND auto_increment IS NOT NULL ORDER BY 1;`
   (comptages seulement, à conserver dans le rapport de répétition).
3. Sur `_import`, la même requête :
   `SELECT table_name, auto_increment FROM information_schema.tables WHERE table_schema = 'sf_mangatrackr_app_import' AND auto_increment IS NOT NULL ORDER BY 1;`
   Pour chaque table, `AUTO_INCREMENT` de `_import` doit être **égal** au relevé Hostinger et **≥ `MAX(id)+1`**
   (`SELECT MAX(id)+1 FROM <table>`). Une valeur égale à `MAX(id)+1` partout alors que Hostinger donne plus
   = export sans l'option : **no-go**, refaire l'export.
Tout écart, ou un nombre d'occurrences inférieur à l'attendu : **no-go**, refaire l'export.
Contrôles de fond à noter : (a) **charset** : le dump mêle `utf8mb3_unicode_ci` et `utf8mb4_unicode_ci` ;
le pré-contrôle d'encodage du script de transfert couvre la cible UTF8, mais relever dans l'inventaire
qu'aucun texte n'est tronqué ou altéré (parité stricte) ; (b) **fuseaux horaires** : le dump pose
`SET time_zone = "+00:00"` et les colonnes cibles sont des `TIMESTAMP WITHOUT TIME ZONE` copiées telles
quelles ; relever `date.timezone` de PHP et le fuseau du serveur PostgreSQL du homelab
`[À COMPLÉTER : fuseaux]`, et contrôler l'affichage des dates (calendrier de sorties) en recette R11.

Le script d'import refuse toute base non suffixée `_import`. Préférer `INVENTAIRE_DB_PASSWORD` et
`IMPORT_DB_PASSWORD` à `--password` (visible dans la liste des processus).

## 7. Création de la base PostgreSQL de production (homelab)

Principes (ADR 0012) : encodage UTF8, **collation ICU `fr`** fixée à la création (D5), base propriété
du rôle applicatif, extension `unaccent` créée par la baseline (le rôle propriétaire suffit, extension
« trusted » depuis PostgreSQL 13).

Référence dans le dépôt : `docker/postgres/init/00-create-app-role-and-database.sh` (idempotent ;
exécuté seulement à la première création du volume). Il donne `CREATEDB` au rôle parce que `make test`
en dev recrée la base de test. **En production, ne pas donner `CREATEDB`** : le rôle n'a aucune raison
de créer des bases. Équivalent SQL exécuté par un superutilisateur (à adapter, valeurs saisies par
l'opérateur) :

```sql
CREATE ROLE <role_app> LOGIN PASSWORD '<mot_de_passe>';          -- ni SUPERUSER, ni CREATEDB
CREATE DATABASE <app_db> OWNER <role_app> TEMPLATE template0
    ENCODING 'UTF8' LOCALE_PROVIDER icu ICU_LOCALE 'fr';
```

Vérifications (humain) :
```sql
SELECT datname, datlocprovider, datlocale FROM pg_database WHERE datname = '<app_db>';     -- 'i' et 'fr'
SELECT rolsuper, rolcreatedb FROM pg_roles WHERE rolname = '<role_app>';                   -- f, f
SHOW server_encoding;                                                                       -- UTF8
```
(La colonne s'appelle `datlocale` en PostgreSQL 17+, version visée ; pour PostgreSQL 16 ou moins, elle
s'appelle `daticulocale`.) Après la baseline (section 8), vérifier l'extension `unaccent`, dont dépend la
recherche :
```sql
SELECT extname FROM pg_extension WHERE extname = 'unaccent';   -- 1 ligne
SELECT unaccent('é');                                            -- e
```

Si l'hébergement homelab ne permet pas `LOCALE_PROVIDER icu` (image ou paquet sans ICU) : **no-go**
(le tri des titres diffère, ADR 0012). `[À COMPLÉTER : image / paquet PostgreSQL 18 du homelab]`

Pas de service nommé `postgres` sur le homelab : adapter l'hôte (section 9).

## 8. Schéma : migrations (baseline)

Sur le homelab (ou depuis le poste d'exécution), avec `DATABASE_URL` de la **base cible**, exportée
pour la durée de la session de migration uniquement :

```
export DATABASE_URL='postgresql://<role_app>:<mot_de_passe>@<hôte>:<port>/<app_db>?serverVersion=18&charset=utf8'
php bin/console doctrine:migrations:migrate --dry-run --no-interaction     # à vérifier d'abord
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate
php bin/console doctrine:migrations:status
unset DATABASE_URL
```
- Résultat attendu : une seule version, `Version20261018145659` (baseline, gardée par un `abortIf` hors
  PostgreSQL) ; `doctrine:schema:validate` : mapping et schéma synchronisés (vérifié en dev, rapports
  019 et 022).
- Cette étape crée l'extension `unaccent` et les index (dont `uniq_app_user_email_lower`).
- Messenger : aucun déclencheur ni fonction NOTIFY n'existe ni n'est nécessaire. Le transport
  `symfony/doctrine-messenger` (6.4.48, `composer.lock`) appelle `pg_notify` directement à l'envoi et fait
  `LISTEN` à la lecture. `MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0` (`.env`) : pas de
  création automatique de table ; `messenger_messages` (et ses index) est créée par la baseline
  (`Version20261018145659`). `[À VÉRIFIER en répétition : (a) la table et ses index existent après la
  baseline ; (b) `php bin/console messenger:consume async -vv` démarre sans erreur ; (c) un message de
  test routé en `async` (seules routes : `ChatMessage`, `SmsMessage`) est consommé]`.
- Les migrations MySQL archivées ne sont **jamais** rejouées ; `doctrine_migration_versions` MariaDB
  n'est pas transférée (ADR 0012 D1).
- Ne pas lancer `make sf-mm` ni `doctrine:schema:update --force` en production.

Variante d'exécution : si l'application est déjà déployée sur le homelab, lancer ces commandes dans
son conteneur / son répertoire `[À COMPLÉTER : où et comment]`.

## 9. Transfert (script `rejouer-transfert.sh`)

Le transfert vide la cible puis la recharge dans une transaction (ROLLBACK en cas d'erreur), recale
les séquences après COMMIT, puis produit 4 inventaires et deux comparaisons.

### 9.1 Garde-fous à fixer pour la production
Par défaut le garde-fou n'accepte que `postgres` / `sf_mangatrackr_app` (dev). Pour la répétition et
pour la production, les valeurs attendues sont **définies explicitement** à chaque commande :

| Variable | Valeur | Rôle |
|---|---|---|
| `TRANSFER_EXPECTED_HOST` | `<hôte PostgreSQL attendu>` (celui du DSN, ex. `[À COMPLÉTER]`) | l'hôte du DSN doit lui être égal, sinon code 5 |
| `TRANSFER_EXPECTED_DB` | `<base attendue>` | idem pour la base |
| `CONFIRM_TARGET` | `<hôte>/<base>` | **saisi à chaque commande**, identique à la cible réelle, sinon code 5 |
| `MARIADB_PASSWORD` | mot de passe de lecture de `_import` | jamais écrit sur disque |
| `DATABASE_URL` | URL PostgreSQL de la cible | lue par le script |

Règles non négociables :
- **`CONFIRM_TARGET` n'est jamais exporté en permanence** (`export` ou fichier `.env`, profil du shell,
  script de démarrage) : il est préfixé sur la ligne de commande, une fois, en relisant l'hôte et la
  base affichés (« Cible réelle : … »). Un `CONFIRM_TARGET` exporté neutralise le garde-fou (rapport 024).
- Les valeurs de la **répétition** et de la **production** diffèrent : relire la cible avant de valider.
  Idéalement des noms de base distincts (`<base>_repetition` / `<base>`) pour que la répétition ne
  puisse jamais viser la production par inadvertance.
- Une base finissant par `_test` ou de même nom que la source est toujours refusée.

### 9.2 Alias DNS et tunnel : à exclure
Le garde-fou lit l'hôte et la base **écrits dans le DSN**, pas l'identité du serveur : un alias DNS ou
un tunnel nommé comme la cible de répétition mais pointant vers la production (ou l'inverse) passerait
(limite documentée, rapports 023/024, SKILL.md). Donc :
- pas de tunnel SSH local réutilisé d'une session à l'autre (`localhost:5432` ne doit jamais désigner
  tantôt la répétition, tantôt la production) ; si un tunnel est indispensable, un port et un hôte
  distincts par cible et un tunnel fermé entre deux ;
- pas d'entrée `/etc/hosts` ni d'alias DNS réutilisé : la production est joignable sous **son nom
  réel** `[À COMPLÉTER]` ;
- avant la commande, vérifier l'identité du serveur à la main, par une requête simple
  (`SELECT inet_server_addr(), current_database(), current_setting('cluster_name', true);`) comparée à
  l'attendu ; le noter dans le compte rendu.

### 9.3 Simulation, puis transfert (humain)
Simulation (ROLLBACK ; le TRUNCATE verrouille la cible pendant l'essai, donc hors production active).
`--yes` **et** `--replace-existing` sont exigés même en `--dry-run` (sinon code 2, rien n'est exécuté) ;
`CONFIRM_TARGET` (ou `--confirm-target=<hôte>/<base>`) l'est aussi. Le `.sh` ajoute `--yes` et
`--replace-existing` lui-même :
```
MARIADB_PASSWORD=… DATABASE_URL='<url cible>' \
TRANSFER_EXPECTED_HOST=<hôte> TRANSFER_EXPECTED_DB=<base> CONFIRM_TARGET=<hôte>/<base> \
php .claude/skills/verif-donnees/scripts/transfert-mariadb-pgsql.php --yes --replace-existing --dry-run
```
Transfert réel (vérifié en dev avec les valeurs de dev, rapport 024 sur base scratch) :
```
MARIADB_PASSWORD=… DATABASE_URL='<url cible>' \
TRANSFER_EXPECTED_HOST=<hôte> TRANSFER_EXPECTED_DB=<base> CONFIRM_TARGET=<hôte>/<base> \
sh .claude/skills/verif-donnees/scripts/rejouer-transfert.sh \
   docs/chantiers/migration-postgresql/rapports/<NNN>-controle
```
(`MARIADB_USER` et `SOURCE_DSN` ont pour défauts `mangatrackr` et `mysql:host=database;port=3306;dbname=sf_mangatrackr_app_import` ; les adapter au poste d'exécution.)

Codes de sortie à lire (jamais passer outre) :

| Code | Sens | Action |
|---|---|---|
| 0 | transfert + parité tolérante et stricte + baseline présente (parité déjà faite par le `.sh`) | continuer vers les contrôles complémentaires de la section 10 |
| 1 | écart réel de parité | no-go, ne pas ouvrir au public ; analyser l'écart |
| 2 | usage | corriger la commande, rien n'a été modifié |
| 3 | échec avec ROLLBACK, connexion ou exception | cible inchangée ; lire le message, corriger, rejouer. Exception : si l'échec vient des `inventaire.php` lancés après le COMMIT, leur code est propagé et le transfert est déjà commité |
| 4 | COMMIT fait, séquences non recalées | rejouer (le rejeu recale) avant toute écriture |
| 5 | cible refusée par le garde-fou | relire hôte/base/confirmation ; **ne pas contourner** |
| 6 | `AUTO_INCREMENT` source absent, NULL ou `< 1` pour une table à séquence ; refus **avant tout `TRUNCATE`**, rien n'a été modifié (`rejouer-transfert.sh` propage le code) | réimporter le dump avec l'option « Ajouter la valeur AUTO_INCREMENT » cochée (6.3, 6.4), puis rejouer. **Attention** : le code 6 ne détecte pas un export fait sans l'option (voir ci-dessous) |

Le code 6 ne couvre pas le cas d'un dump exporté **sans** l'option : après import InnoDB, `AUTO_INCREMENT`
vaut alors `MAX(id)+1` (ou 1 si la table est vide), jamais NULL ; le script retombe en silence sur
`MAX(id)+1` et D3 n'est plus respectée sans aucun signal. Le seul garde-fou est le contrôle go/no-go
de la section 6.4.

Une fois le transfert terminé : les 22 tables de données sont alignées (71 014 lignes sur le dump de
référence ; `doctrine_migration_versions` : 1 ligne, la baseline). **Séquences (Q19 tranchée)** :
l'utilisateur a décidé d'appliquer la décision D3 de l'ADR 0012, soit `setval(GREATEST(MAX(id),
AUTO_INCREMENT - 1))` : un ID supprimé en queue de table n'est jamais réutilisé (les ID du
`localStorage` de la scanthèque référencent `manga_user_track`). Le script de transfert et
`inventaire.php` sont alignés sur D3 par la tâche migration-donnees (rapport 029) ; **le plan ne prévoit
plus aucun recalage manuel** (non reproductible, et contraire au critère « 19 séquences sans écart »).
Prérequis : `AUTO_INCREMENT=` présent dans le dump et égal au relevé Hostinger (6.3 et 6.4, go/no-go renforcé). Comparaison à faire en
répétition, avant/après transfert :
```sql
-- MariaDB, base _import (comptages seulement)
SELECT table_name, auto_increment FROM information_schema.tables WHERE table_schema = 'sf_mangatrackr_app_import';
-- PostgreSQL, cible : last_value attendu = GREATEST(MAX(id), AUTO_INCREMENT - 1) par table
SELECT sequencename, last_value FROM pg_sequences WHERE schemaname = 'public' ORDER BY 1;
```
`pg_sequences.last_value` est **NULL** tant que la séquence n'a jamais été appelée (`is_called` faux : table
vide avec `AUTO_INCREMENT=1`, prochaine valeur 1) : ce n'est pas une alerte. La formule
`last_value = GREATEST(MAX(id), AUTO_INCREMENT - 1)` ne vaut que si `is_called` est vrai ; sinon la prochaine
valeur est `last_value` (ici 1). Ne pas se fier à `last_value` seul pour une table vide.
`[À VÉRIFIER en répétition : comportement validé par le rapport 029, valeurs de manga_user_track et
user_track_list]`.

## 10. Contrôle de parité

Le `.sh` exécute : (a) comparaison **tolérante** (`--compare`, empreinte normalisée, `--rename=user:app_user`,
`--ignore=doctrine_migration_versions`, `--require-baseline=Version20261018145659`), (b) comparaison
**stricte** (`--strict`, valeurs brutes, 3 tables de jointure incluses), (c) séquences (valeur attendue
`GREATEST(MAX(id), AUTO_INCREMENT - 1)`, ADR 0012 D3, voir 9.3).

Lecture : `LIGNES DIFFÉRENTES` = bloquant ; `CONTENU DIFFÉRENT` à lignes égales = isoler la colonne
(booléen, flottant, date, JSON, encodage) et décider ; `COLONNES DIFFÉRENTES` = expliquer ; la table
`doctrine_migration_versions` est `IGNORÉE` explicitement (15 contre 1) : attendu.

Contrôles complémentaires en production (humain), comptages seulement :
```sql
SELECT 'app_user', COUNT(*) FROM app_user UNION ALL SELECT 'manga', COUNT(*) FROM manga;       -- = inventaire
SELECT sequencename, last_value FROM pg_sequences WHERE schemaname = 'public' ORDER BY 1;     -- 19 séquences ; NULL = jamais appelée (voir 9.3) ; last_value NULL = jamais appelée (prochaine valeur 1), voir 9.3
```
Rangement : les 4 JSON (`inventaire-*.json`) vont dans `rapports/<NNN>-…` ; ils ne contiennent que
des comptages et empreintes. Vérifier l'absence de `@` (emails) avant tout commit.

Les inventaires JSON antérieurs au rapport 029 (022, 023, 024) n'ont pas de clé `auto_increment` : ne pas les
réutiliser comme « attendu » (19 écarts avec `inventaire.php` actuel). Régénérer l'attendu et l'inventaire
(rapport 032, `rejouer-transfert.sh` le fait à chaque exécution).

Critère : code 0 du `.sh`, 19 séquences sans écart, baseline présente, doublons d'emails : 0.

## 11. Recette sur le dump final

À faire sur la base de production chargée, **avant ouverture au public**, par l'application déployée
avec une configuration de préproduction (URL interne / hôte de test) `[À COMPLÉTER : comment atteindre
l'application avant le DNS]`. Contrairement à la recette 019 (lecture seule), cette recette **écrit** :
elle se fait donc sur la base de **répétition** lors de la répétition, et en production seulement avec
des écritures minimales et nettoyables, ou bien suivie d'un rejeu du transfert (qui écrase tout) tant
que l'application n'est pas publique.

**Courrier pendant la recette** : la base chargée contient les 6 comptes réels. Tant que l'application
n'est pas publique, `MAILER_DSN` doit être neutre (`null://null` ou boîte de capture type Mailpit) pour
la recette sur la base chargée, et toute invitation ou réinitialisation vise un compte/une adresse de
test, jamais l'un des 6 comptes réels (aucun email réel ne doit partir). Le vrai SMTP n'est activé qu'à
la bascule (12.1), après R13 sur adresse de test `[À COMPLÉTER : SMTP]`.

Scénarios non couverts par la recette 019 (liste du rapport 019, écart 3 et 4) :

| # | Scénario | Attendu |
|---|---|---|
| R1 | `POST /scantheque/smut_datas/{statut}` avec données valides (ajout d'un manga) | 200, ligne `manga_user_track` créée avec un ID **supérieur** à tous les existants (séquence correcte) |
| R2 | `umut_datas` (mise à jour) et `dmut_datas` (suppression) sur ses propres tracks | 200 ; sur le track d'un autre : 403 (voter) |
| R3 | `smut_datas` avec `{"mangas":[]}` | pas de 500 (anomalie 2 du rapport 019, corrigée par 021/023) |
| R4 | Connexion **par formulaire** (compte de test dédié, mot de passe connu) | succès ; email en casse différente et espaces tolérés (`UserLoader`) |
| R5 | Connexion **Google OAuth** | succès ; l'URI de redirection autorisée côté Google couvre le **nouveau domaine/schéma** `[À COMPLÉTER : domaine, TLS]` |
| R6 | Inscription sur invitation (code d'invitation, email), puis réinitialisation de mot de passe | création (`INSERT` avec ID > max), email envoyé **à une adresse de test uniquement** |
| R7 | EasyAdmin : création, édition, suppression sur les 11 CRUD (au moins Manga, User, UserInvitationCode) | écritures OK, aucune erreur SQL ; filtres/tri avec `query=é` |
| R8 | `INSERT` après recalage : insérer une ligne dans chaque table à séquence (par l'application) et vérifier `id > MAX` précédent | aucune violation de clé primaire (« duplicate key ») |
| R9 | `GET /manga/{slug}` (écrit `nb_view`) et recherche avancée (appelle Tenrai / MangaUpdates et écrit) | OK ; clés API et sortie réseau du homelab disponibles `[À COMPLÉTER]` |
| R10 | Recherche du catalogue : terme sans accent / majuscules sur titres accentués, terme absent, caractères spéciaux | titre retrouvé (rapport 019) |
| R11 | Pages publiques et catalogue (pages 1, 2, dernière, au-delà), calendrier, export CSV pour un compte avec scanthèque | 200, tri conforme (ICU `fr`) |
| R12 | Tâches : worker Messenger (table `messenger_messages`, index composite, table et index présents dans la baseline, `auto_setup=0`), commandes d'import planifiées | `messenger:consume async` démarre (`pg_notify`/`LISTEN` sans erreur) et un message de test passe ; commandes planifiées sans erreur |
| R13 | Mail (SMTP du homelab) : invitation et réinitialisation | délivrés `[À COMPLÉTER : SMTP]` |
| R14 | Journaux applicatifs et PostgreSQL pendant la recette | pas d'erreur SQL, pas de `deprecated` bloquant |

Contrôles automatiques à rejouer sur le code déployé : `make qa-php-stan`, `make test-only` (sur la CI
ou sur une base de test, **jamais sur la base de production**), `doctrine:schema:validate`.

Critère : tous les scénarios R1 à R14 verts ; toute écriture de recette est **nettoyée** ou la base est
rechargée par un nouveau transfert avant l'ouverture. Le compteur `nb_view` et les comptes de test sont
connus et listés dans le compte rendu.

## 12. Bascule DNS / déploiement, puis retour arrière

### 12.1 Bascule (après go)
1. Déployer / démarrer l'application sur le homelab avec la configuration de production
   (`.env.local` : `APP_ENV=prod`, `DATABASE_URL` PostgreSQL, secrets Google, Tenrai, SMTP…) ; cache
   vidé et réchauffé (`cache:clear` en prod, répertoire `var/cache` d'un autre environnement non
   réutilisé) `[À COMPLÉTER : procédure de déploiement]`. Le `MAILER_DSN` neutre de la recette est remplacé
   par le vrai SMTP. L'**URI de redirection OAuth Google** du nouveau domaine est autorisée dans la
   console Google avant l'ouverture (R5, Q9).
   **Sessions et cookies** : les sessions sont des fichiers natifs PHP (pas de `handler_id` dans
   `framework.yaml`) ; à la bascule toutes les sessions sont perdues et chacun doit se reconnecter. Si
   `APP_SECRET` change, les cookies « se souvenir de moi » et les liens signés sont aussi invalidés :
   à annoncer aux utilisateurs (Q16) et à décider avec Q21 (reprendre ou non `APP_SECRET`).
2. Vérifier avec un hôte de test (hosts local, URL interne) : page d'accueil, connexion, 1 scénario
   d'écriture (R1).
3. Abaisser le TTL du DNS **48 h avant** (J-2) à une valeur courte (300 s) `[À COMPLÉTER : zone DNS,
   registrar]`.
4. Basculer le DNS vers le homelab (A/AAAA/CNAME) ; certificat TLS valide pour le domaine `[À COMPLÉTER :
   ACME / reverse proxy]` ; redirection HTTP→HTTPS ; en-têtes.
5. Retirer la maintenance côté homelab (il n'y en a pas : l'application y est ouverte) ; **laisser la
   maintenance active sur Hostinger** (503, ou redirection) pour que les clients au DNS non encore
   propagé n'écrivent pas dans l'ancienne base.
6. Surveillance renforcée pendant 24 à 48 h (journaux, erreurs 5xx, taux d'échec de connexion,
   espace disque, sauvegardes) `[À COMPLÉTER : monitoring]`.
7. Réactiver les tâches planifiées **sur le homelab uniquement**, après avoir vérifié qu'elles sont
   désactivées sur Hostinger.

### 12.2 Critères de go / no-go

Go si **tous** les points suivants sont vrais ; sinon no-go (section 12.3).

| # | Critère | Mesure |
|---|---|---|
| G1 | Répétition générale réussie, durées connues, fenêtre suffisante | compte rendu |
| G2 | Dump final intègre : SHA-256 relevé, import `_import` = comptages attendus, 15 migrations connues | inventaire `--expected` |
| G3 | 0 doublon d'email à la casse près (ou décision documentée et appliquée) | section 5 |
| G4 | Base PostgreSQL : ICU `fr`, UTF8, rôle non super, sans `CREATEDB` | requêtes de la section 7 |
| G5 | Baseline seule dans `doctrine_migration_versions`, `schema:validate` OK | section 8 |
| G6 | Transfert code 0, parité tolérante et stricte, 19 séquences sans écart | section 9 et 10 |
| G7 | Recette R1 à R14 verte, dont connexion formulaire et OAuth, écritures EasyAdmin, INSERT après recalage | section 11 |
| G8 | Sauvegarde PostgreSQL planifiée **et** restauration testée ; sauvegarde Hostinger conservée | section 3.3 / 6.1 |
| G9 | TLS valide, DNS prêt (TTL bas), monitoring en place, mails fonctionnels | section 14 |
| G10 | Retour arrière décrit, testable en moins de `[À COMPLÉTER : X]` minutes | 12.3 |
| G11 | Aucun incident non expliqué dans les journaux PostgreSQL et applicatifs pendant la recette | journaux |

### 12.3 Retour arrière
**Principe** : Hostinger/MariaDB reste intact. On ne le « réactive » pas en y copiant des données
PostgreSQL : on y revient tel qu'il était au gel.
- **Avant l'ouverture du homelab (no-go)** : retirer la maintenance sur Hostinger, réactiver les
  crons ; aucune donnée perdue (les écritures de recette sont dans PostgreSQL seulement). DNS
  inchangé. Durée : quelques minutes.
- **Après l'ouverture (rollback après N heures)** : les écritures faites sur PostgreSQL depuis la
  bascule (inscriptions, scanthèque, compteurs) **ne sont pas dans MariaDB**. Deux options à décider
  par l'utilisateur en amont :
  (a) accepter la perte de ces écritures (acceptable si la fenêtre est courte, quelques heures, usage
  perso/communauté restreinte : 6 utilisateurs dans le dump de référence) ;
  (b) rejouer manuellement les écritures (export CSV de la scanthèque PostgreSQL, ajouts à la main).
  Il n'existe **pas** de réplication inverse PostgreSQL → MariaDB (hors périmètre, non prévu).
  Seuil de décision : retour arrière possible tant que `[À COMPLÉTER : par ex. 24 h]` ; au-delà, on
  corrige en avant sur PostgreSQL.
- Procédure : (1) remettre le DNS sur Hostinger, (2) retirer la maintenance Hostinger, (3) mettre le
  homelab en maintenance, (4) réactiver les crons sur Hostinger seulement, (5) vérifier la connexion
  et une écriture, (6) analyser l'incident avant de retenter.

**Conservation** (à valider) :
| Élément | Durée proposée | Remarque |
|---|---|---|
| MariaDB Hostinger (base et code), en **lecture seule** / maintenance | **30 jours** après la bascule, au moins 2 sauvegardes PostgreSQL réussies | lecture seule : compte MariaDB limité à `SELECT` si l'hébergeur le permet, sinon application en maintenance |
| Dump final et sauvegardes MariaDB | 90 jours puis suppression sécurisée | données personnelles |
| Base `sf_mangatrackr_app_import` locale | supprimée dès la clôture | données personnelles dans une base de dev |
| Dumps/inventaires JSON | JSON sans donnée personnelle conservés dans `rapports/` | |

À l'issue : demander la suppression/arrêt de l'hébergement après accord explicite de l'utilisateur ;
tenir `docs/claude/etat-et-dette-technique.md` à jour (clôture du chantier).

## 13. Calendrier type

Les durées sont à mesurer en répétition ; valeurs initiales indicatives (transfert de 71 014 lignes :
secondes ; le goulot est humain : import phpMyAdmin, recette, DNS).

| Échéance | Étape | Responsable |
|---|---|---|
| J-14 | Validation du plan (ADR 0013), réponses à la section 14, choix de la date | utilisateur |
| J-10 | Homelab prêt : PostgreSQL 18 ICU, reverse proxy, TLS, sauvegardes testées | utilisateur |
| J-7 | Répétition générale (section 4), compte rendu | utilisateur + assistant |
| J-3 | Correctifs de répétition, seconde répétition si nécessaire ; message de maintenance préparé | utilisateur |
| J-2 | TTL DNS abaissé ; vérification des sauvegardes | utilisateur |
| J-1 | Go/no-go de principe ; annonce aux utilisateurs | utilisateur |
| J, h0 | Sauvegarde Hostinger, gel des écritures, maintenance (6.1-6.2) | utilisateur |
| J, h0 + 15 min | Export final, SHA-256, import `_import` (6.3-6.4) | utilisateur |
| J, h0 + 30 min | Contrôle doublons d'emails (5), base PG et migrations (7-8) | utilisateur |
| J, h0 + 45 min | Transfert et parité (9-10) | utilisateur |
| J, h0 + 1 h 30 | Recette (11), go/no-go (12.2) | utilisateur |
| J, h0 + 2 h | Bascule DNS (12.1) | utilisateur |
| J+1 à J+2 | Surveillance renforcée ; première sauvegarde PostgreSQL de production vérifiée | utilisateur |
| J+7 | Point de situation ; décision de maintenir Hostinger | utilisateur |
| J+30 | Fin de conservation Hostinger, nettoyage des dumps, clôture du chantier | utilisateur |

Choisir une date et une plage horaire à faible trafic `[À COMPLÉTER]`.

## 14. Questions ouvertes pour l'utilisateur

Rien de ce qui suit n'est connu du dépôt ; **aucune hypothèse n'a été prise**. Compléter avant la
répétition.

| # | Sujet | Question | Réponse |
|---|---|---|---|
| Q1 | Hébergement | Matériel / hyperviseur / conteneurs (Docker, Compose, Kubernetes, bare-metal) du homelab ? PostgreSQL en conteneur ou sur l'hôte ? Version et image exacte de PostgreSQL 18 (ICU disponible) ? | `[ ]` |
| Q2 | Réseau et exposition | Comment le site est-il exposé à Internet (IP fixe, DynDNS, tunnel type Cloudflare, VPN) ? Ports ouverts ? | `[ ]` |
| Q3 | Reverse proxy | Quel reverse proxy (Traefik, Nginx, Caddy, autre) ? Qui gère PHP (Apache dans le conteneur `app`, PHP-FPM) ? | `[ ]` |
| Q4 | TLS | Émission et renouvellement des certificats (ACME/Let's Encrypt, DNS challenge) ? | `[ ]` |
| Q5 | DNS | Registrar et zone, TTL actuel, domaine de production, enregistrements à changer (A, AAAA, CNAME, MX ?) ? | `[ ]` |
| Q6 | Sauvegardes PostgreSQL | Outil (pg_dump, pgBackRest, snapshot), fréquence, destination hors machine, rétention, chiffrement, procédure de restauration testée ? | `[ ]` |
| Q7 | Déploiement | Comment le code arrive sur le homelab (git pull, image Docker, CI/CD) ? Qui lance `composer install`, `cache:clear`, `npm run build` ? Version du commit/tag à déployer ? | `[ ]` |
| Q8 | Secrets | Où vivent `.env.local`, mots de passe PostgreSQL, clés OAuth Google, clés API, SMTP (fichier, Docker secrets, gestionnaire) ? Rotation prévue ? | `[ ]` |
| Q9 | OAuth Google | L'URI de redirection autorisée sera-t-elle modifiée dans la console Google pour le nouveau domaine ? Qui y accède ? | `[ ]` |
| Q10 | Courrier | Quel SMTP sortant sur le homelab (invitation, réinitialisation) ? SPF/DKIM/DMARC du domaine ? | `[ ]` |
| Q11 | Tâches planifiées | Liste exacte des crons et du worker Messenger actuellement sur Hostinger, et leur équivalent homelab (cron, systemd, conteneur) ? | `[ ]` |
| Q12 | Monitoring | Surveillance (uptime, logs centralisés, alertes disque/PostgreSQL) ? Qui est alerté ? | `[ ]` |
| Q13 | Poste d'exécution du transfert | Où lancer `rejouer-transfert.sh` (conteneur `app`, hôte homelab) ? Y a-t-il `pdo_mysql`+`pdo_pgsql` et un MariaDB local pour `_import` ? Chemin réseau vers PostgreSQL (direct, tunnel : à éviter, 9.2) ? | `[ ]` |
| Q14 | Gel côté Hostinger | Mécanisme de maintenance disponible (page statique, `.htaccess`, coupure du vhost, 503) ? Possibilité de rendre le compte MariaDB en lecture seule ? | `[ ]` |
| Q15 | Hostinger | Durée du contrat restant ; quand peut-il être résilié (30 jours de conservation OK) ? Politique de sauvegarde de l'hébergeur ? | `[ ]` |
| Q16 | Fenêtre | Date, heure, durée de maintenance acceptable, annonce aux utilisateurs (6 comptes), dont la reconnexion obligatoire (sessions perdues) ? | `[ ]` |
| Q17 | Retour arrière | Perte des écritures post-bascule acceptable ? Seuil de décision (heures) ? | `[ ]` |
| Q18 | Doublons d'emails | Si un doublon est détecté : fusion, suppression, ou renommage ? | `[ ]` |
| Q19 | Séquences | ~~Valider `MAX+1` ou exiger D3 ?~~ **Tranchée (2026-10-18)** : D3 appliquée, recalage à `GREATEST(MAX(id), AUTO_INCREMENT-1)` ; script et `inventaire.php` alignés (rapport 029). Reste : cocher `AUTO_INCREMENT` à l'export (6.3) et le vérifier (6.4) | D3 (décidé par l'utilisateur) |
| Q20 | Rôle `CREATEDB` | Confirmer que le rôle de production n'a pas `CREATEDB` (le script d'init de dev le donne) et qui crée la base ? | `[ ]` |
| Q21 | Environnement applicatif | Variables d'environnement requises en prod (`APP_ENV=prod`, `APP_SECRET` : le conserver ou invalider sessions/remember-me/liens signés ? `TRUSTED_PROXIES`, `MAILER_DSN`, fuseau PHP `date.timezone`, etc.) ; a-t-on un `.env.prod` de référence ? | `[ ]` |
| Q22 | Données personnelles | Durée de conservation du dump final et des sauvegardes ; information RGPD des utilisateurs sur le changement d'hébergeur ? | `[ ]` |

## 15. Limites connues du plan

- La requête de doublons et celle des emails non normalisés (section 5) n'ont pas été rejouées sur une
  base ; l'`--dry-run` du transfert en répétition est le contrôle de référence.
- Rien n'a été exécuté sur la production ni sur le homelab ; l'ordonnancement a été vérifié sur la base
  de dev et la base scratch (rapports 022, 024), pas sur un dump à jour ni avec des doublons d'emails.
- La parité stricte porte sur 22 tables ; la recette fonctionnelle (section 11) n'a jamais été jouée
  en écriture sur PostgreSQL (rapport 019 : lecture seule).
- Le garde-fou ne détecte pas un alias DNS ou un tunnel (9.2).
- `--inject-failure` reste présent dans le script (inoffensif sans drapeau) ; `--require-baseline` ne
  vérifie pas que la baseline est seule (M-F, rapport 023).
- Les `double` sont lus en texte puis convertis : identité stricte prouvée sur les données du dump
  actuel, pas en général (rapport 017).
