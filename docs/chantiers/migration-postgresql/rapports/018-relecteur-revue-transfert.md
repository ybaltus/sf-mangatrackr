# 018 — relecteur — Revue de l'outil de transfert MariaDB → PostgreSQL

- Tâche : T6 (revue)
- Date : 2026-10-18
- Statut : terminé

## Objectif
Relire `transfert-mariadb-pgsql.php`, `rejouer-transfert.sh`, l'option `--rename` de `inventaire.php`
et les JSON `017-inventaire-*.json` (depuis remplacés par `022-inventaire-*.json`) (rapport d'appui 017, décisions 002 D1-D9). Lecture seule : aucune
connexion aux bases, aucun correctif.

## Ce qui a été fait
Lecture complète des 3 scripts, du diff `inventaire.php`, de la baseline
`migrations/Version20261018145659.php` (types, FK, séquences), des JSON ; `php -l` et `sh -n` OK ;
`php -i` pour `zend.exception_ignore_args`.

## Résultats

Verdict : **validé avec réserves** (0 bloquant, 4 importants à corriger avant la bascule, 6 mineurs).

### Importants

**I1 — Mot de passe potentiellement affiché en cas d'échec de connexion**
`transfert-mariadb-pgsql.php:129` et `:139` (hors `try`). Si MariaDB ou PostgreSQL est injoignable,
le `PDOException` n'est pas rattrapé : PHP affiche la trace avec les arguments de `PDO->__construct(dsn,
user, password)`. `zend.exception_ignore_args` vaut `Off` dans cet environnement ; les chaînes sont
tronquées à 15 caractères, donc un mot de passe court (ex. celui par défaut du compose) apparaît en entier
sur stderr, d'où il peut finir dans un rapport ou un log. Correction : entourer chaque `new PDO` d'un
`try/catch (PDOException)` qui appelle `fail()` avec un message neutre (sans `getMessage()` brut non plus),
et/ou `ini_set('zend.exception_ignore_args', '1')` en tête de script. Même remarque pour
`inventaire.php` (connexion `--dsn`).

**I2 — La cible est vidée sans contrôle de son contenu (robustesse cible non vide)**
`:188`. Seul `--yes` protège le `TRUNCATE` de 22 tables. Le garde-fou ne refuse que `_test` et
« même nom que la source » ; une `DATABASE_URL` pointant sur la base de production PG après la bascule
serait effacée, y compris des données saisies depuis. Rien n'est affiché sur ce qui sera détruit.
Correction : lire avant le `TRUNCATE` le nombre de lignes de la cible ; si non vide, l'afficher et exiger
un second drapeau explicite (`--replace-existing`) ; afficher le nom de base et l'hôte cibles avant
exécution ; idéalement un drapeau d'environnement (`ALLOW_TRUNCATE_DB=<nom>` égal au nom de la base).
Le rejeu (cible déjà chargée) reste possible avec ce drapeau.

**I3 — `setval` n'est pas transactionnel : un échec tardif laisse les séquences incohérentes**
`:230-249`. Les `setval` sont exécutés avant le contrôle des comptages (`:241`). Si ce contrôle (ou le
`commit`) échoue, le `ROLLBACK` restaure les tables mais pas les séquences : elles pointent alors sur
`MAX(id)` des données annulées. Si la cible contenait des données plus récentes (rejeu après usage),
une séquence abaissée provoque des `duplicate key` aux insertions suivantes. Contredit
« en cas d'erreur rien n'est modifié » (en-tête, `:8`). Correction : faire le contrôle des comptages avant
les `setval`, et exécuter les `setval` après le `commit` (échec = message explicite « relancer le recalage »),
ou à défaut calculer les valeurs dans la transaction et les appliquer après.

**I4 — Le code de sortie 1 de `rejouer-transfert.sh` rend la bascule illisible**
`rejouer-transfert.sh:25` ; `inventaire.php:128,132`. `doctrine_migration_versions` (15 ↔ 1 ligne)
est toujours en écart, donc le script sort en 1 à chaque exécution réussie, alors que l'en-tête (`:4`)
annonce « 0 = parité complète » (contradiction avec 017). Une vraie divergence (`CONTENU DIFFÉRENT`)
est indiscernable de l'écart attendu : un humain ou un enchaînement `&&` ne peut pas s'y fier.
Correction proposée :
1. `inventaire.php --compare` : option `--ignore=doctrine_migration_versions` ; ces tables sont retirées
   de `$names` avant la boucle, affichées « IGNORÉE » et non comptées dans `$differences`.
2. `.sh` : ajouter `--ignore=doctrine_migration_versions` à la ligne 25. Le code redevient 0 = parité
   des 22 tables, 1 = écart réel, 2 = erreur d'usage/IO.
3. Ajouter dans le `.sh` un contrôle explicite que PG contient exactement la baseline attendue
   (`SELECT count(*) FROM doctrine_migration_versions` = 1 et la version `Version20261018145659`), pour que
   « ignorée » ne veuille pas « non vérifiée ».
Corriger aussi l'en-tête (`:4`) et la section « Code de sortie » de 017.

### Mineurs

- **M1 — Fuite de données personnelles dans les erreurs** (`:256`). Le message PDOException de PG inclut
  `DETAIL: Key (email)=(...) already exists` ou `Failing row contains (...)` : emails et hash de mot de passe
  vers stderr. Correction : ne journaliser que `SQLSTATE`, le nom de table et de contrainte
  (`$e->errorInfo`), pas `getMessage()`.
- **M2 — `fail()` (`exit`) dans le `try`** (`:70,77,83,195,247`) : `exit` ne déclenche pas le `catch`, le
  `rollBack` explicite et le message « ROLLBACK » sont sautés ; l'annulation n'est que l'effet implicite de la
  fermeture de connexion. Correct en pratique, mais fragile. Correction : `convert()` et les contrôles lancent
  `RuntimeException` ; seul le `catch` appelle `fail()`.
- **M3 — Dépendances FK** (`:155-158`) : les auto-références sont ignorées (`$child !== $parent`), aucune
  aujourd'hui (vérifié dans la baseline : 18 FK, aucune auto-référence), mais une future table hiérarchique
  casserait l'insertion sans message clair. `$child` n'est pas passé par `trim('"')` alors que `$parent` l'est :
  un nom cité (majuscules, mot réservé, ex. une future table `user`) serait silencieusement exclu de `$deps`.
  Correction : normaliser les deux, ou utiliser `pg_class.relname` plutôt que `regclass::text`, et échouer si
  une FK référence une table non répertoriée.
- **M4 — Pas de contrôle des types source** : la conversion est pilotée par le type **cible** seul. Une colonne
  source `TIMESTAMP` (sensible au fuseau de session) ou `DECIMAL` passerait sans alerte. Aujourd'hui seules
  des colonnes `datetime` existent. Correction : lire `data_type` côté source et échouer sur une combinaison
  non prévue (tinyint→boolean, datetime→timestamp, longtext→json/text, double→double precision, int→int).
  Ajouter `SHOW server_encoding` = UTF8 en pré-contrôle (un `SQL_ASCII` ne convertirait rien).
- **M5 — Incohérences du `.sh`** : port PostgreSQL codé en dur 5432 (`:22`) alors que le PHP lit celui de
  `DATABASE_URL` ; DSN source dupliqué (`:19`) ; `$u["pass"]` absent donne un warning ; quatre `php -r` pour
  parser la même URL. Aucun test que `DATABASE_URL` désigne bien PostgreSQL avant d'appeler le transfert
  (le shell exporte encore une URL MySQL selon `etat.md` : l'échec est sûr mais le message est confus).
- **M6 — `--rename`** (`inventaire.php:105-113`) : une paire sans `:` produit un warning puis une clé vide ;
  si la table cible existe déjà à gauche elle est écrasée sans alerte ; le renommage ne s'applique qu'au côté
  gauche (l'ordre des fichiers compte, non documenté). `SKILL.md` n'est mis à jour ni pour `--rename`, ni pour
  les deux nouveaux scripts. Conventions : scripts hors `src/`, donc hors PHPStan et cs-fixer (choix assumé en
  017) ; `strict_types` présent, `php -l` OK ; fonctions et constantes globales sans espace de noms
  (acceptable pour un script).

### Points vérifiés et jugés corrects
- **Booléens** : 0/1 strict, toute autre valeur = erreur ; ok avec `EMULATE_PREPARES=false` (`'0'`/`'1'`
  acceptés par PG pour un paramètre `boolean`).
- **Dates** : `datetime` → `timestamp(0) without time zone`, chaîne conservée, aucune conversion de fuseau
  (aucune colonne source `TIMESTAMP`) ; date à zéro refusée ; dates invalides type `2020-00-00` rejetées par PG
  (rollback).
- **JSON** : colonnes `JSON` (pas `jsonb`, D7), texte conservé tel quel ; la validation PHP est redondante avec
  PG mais donne un meilleur message. Chaîne vide dans une colonne JSON = erreur (comportement correct).
- **NULL / chaîne vide** : jamais confondus (`null === $value` testé avant le cast).
- **double** : lus en texte (`STRINGIFY_FETCHES`), pas de passage par un float PHP ; correct.
- **Encodage** : `SET NAMES utf8mb4` côté source ; octets invalides rejetés par PG (échec franc, pas de corruption
  silencieuse), sous réserve de M4.
- **Ordre FK** : tri topologique sur `pg_constraint` correct pour les 18 FK actuelles ; `TRUNCATE` en une seule
  instruction listant toutes les tables (évite l'erreur FK) ; limite 10 000 paramètres par lot (< 65 535).
- **Séquences** : les 19 colonnes `SERIAL`/`BIGSERIAL` sont trouvées par `pg_get_serial_sequence` (le script
  fonctionne aussi pour des colonnes `IDENTITY`, pas de `SERIAL` codé en dur) ; `setval(max, true)` ou
  `(1, false)` pour une table vide : correct. `RESTART IDENTITY` est transactionnel. Pas d'assertion sur le
  nombre attendu (19) : voir « non prouvé ».
- **Atomicité / rejouabilité** : une seule transaction PG (TRUNCATE + INSERT + comptages), source en lecture
  seule avec snapshot cohérent ; `--dry-run` ne touche pas aux séquences ; rejeu ×2 identique (017). Sous
  réserve de I3.
- **`doctrine_migration_versions`** : exclue volontairement (SKIPPED), jamais tronquée ni écrite : décision
  cohérente avec D1 (baseline PG). Le script le dit en sortie.
- **Secrets** : aucun mot de passe écrit dans les scripts, le rapport 017 ou les JSON (`MARIADB_PASSWORD`,
  `PGSQL_PASSWORD` ou `DATABASE_URL` par l'environnement ; transmis aux sous-processus par variable, pas
  par argument). Réserve : I1.
- **JSON versionnés `017-inventaire-*.json` (depuis remplacés par `022-inventaire-*.json`)** : uniquement noms de tables/colonnes, nombre de lignes, sha256
  global par table, date. Aucun email, hash de mot de passe, valeur de ligne (0 occurrence de `@`).
  Sans risque ; le sha256 de `app_user` (6 lignes) est non salé mais agrégé, non exploitable.
- **Migrations** : aucune migration modifiée (git status).

### Parité : ce qui n'est pas prouvé
1. L'empreinte de `inventaire.php` est **tolérante** (`normalize`) : JSON ré-encodé, et toute chaîne qui
   ressemble à un décimal (`^-?\d+\.\d+$`) voit ses zéros finaux supprimés, y compris dans des colonnes texte
   (`chapter_val`, `volume_val`). Une altération de ce genre ne serait pas détectée. Le contrôle strict qui
   compense a été exécuté hors dépôt (scratchpad) : **non rejouable, non relu**, et il ne couvre pas
   `manga_editor`, `manga_fantrad`, `manga_manga_type` (sans `id`). Recommandation : intégrer un mode
   `--strict` à `inventaire.php` (valeurs brutes, bool 0/1, sans réencodage), ordre déterministe sur la clé
   composite.
2. Les **valeurs des séquences** ne sont pas comparées : elles sont seulement affichées par le transfert. Aucune
   assertion « 19 séquences » ni « `nextval` > MAX(id) » dans la procédure.
3. **Schéma** : index, contraintes, valeurs par défaut, extension `unaccent`, ne sont pas vérifiés par cette
   procédure (relève de `doctrine:schema:validate`, T4.5).
4. **Intégrité applicative** : le transfert garantit données identiques, pas que l'ORM les relise à l'identique
   (booléens, JSON, dates via Doctrine) ; à couvrir par la recette 019.
5. `double` : identité stricte observée sur les données actuelles seulement (déjà signalé en 017).
6. **Unicité insensible à la casse** : l'index `LOWER(email)` fait échouer le transfert (rollback) si deux emails ne
   diffèrent que par la casse ; c'est le comportement voulu mais non testé sur un dump différent. À anticiper
   pour le dump final.
7. Bascule réelle : la procédure suppose `_import` figé et l'application arrêtée ; rien dans l'outil ne le
   vérifie (source = copie, pas de verrou côté prod).

## Écarts, risques, points non vérifiés
Rien n'a été exécuté contre les bases (consigne). Les constats I3, M3 et I1 reposent sur la lecture du code et
sur la configuration PHP (`exception_ignore_args=Off`), non reproduits par un essai.

## Recommandation pour la suite
- Corriger I1-I4 (changements courts, faisables par `developpeur` ou `migration-donnees`) avant la répétition de
  bascule en T7 ; M1-M6 au fil de l'eau.
- Ajouter le mode strict reproductible à `inventaire.php` et documenter `--rename`, `--ignore` et la procédure
  dans `SKILL.md`.
- Après correction, rejouer la procédure deux fois et vérifier : code de sortie 0, séquences inchangées après
  un échec provoqué (dry-run avec comptage faussé), aucun mot de passe dans stderr en cas de base arrêtée.
