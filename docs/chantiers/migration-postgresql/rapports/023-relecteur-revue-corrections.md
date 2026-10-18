# 023 — relecteur — Revue des corrections (transfert, validateur, subscriber)

- Tâche : T6 (relecture des corrections 020-022)
- Date : 2026-10-18
- Statut : terminé

## Objectif
Vérifier, dans le code et pas seulement dans les rapports, la correction des constats I1-I4 et M1-M6 de
018, puis relire `SearchTermValidator`, `MangaController`, `MangaStatisticSubscriber` et leurs tests.
Lecture seule : aucune connexion à une base, aucun transfert, aucun test PHPUnit lancé (ils écrivent en
base de test).

## Ce qui a été fait
Lecture complète de `transfert-mariadb-pgsql.php`, `rejouer-transfert.sh`, `inventaire.php`, `SKILL.md`
(diff), des 3 fichiers `src/`, des 2 tests, des rapports 017/018/022 et des 4 JSON 022. Commandes :
`php -l` (2 scripts) OK, `sh -n` OK, `make qa-php-stan` OK (0 erreur), `git status migrations` (aucune
migration touchée), `grep` de secrets / `@` dans JSON et rapports.

## Résultats

Verdict : **validé avec réserves** — T6 peut être close. 0 bloquant, 2 importants (non bloquants pour la
clôture, à traiter avant la bascule de production), 7 mineurs.

### Constats d'origine (018) : état réel

| Constat | État | Preuve |
|---|---|---|
| I1 mot de passe en trace | corrigé | `transfert:49` et `inventaire:31` `ini_set('zend.exception_ignore_args','1')` ; `connect()` `transfert:152-160` message générique ; `inventaire:247-251`. |
| I2 cible vidée sans contrôle | corrigé en partie | voir I-A ci-dessous |
| I3 setval non transactionnel | corrigé | `transfert:312-323` comptages, `:326-329` calcul des valeurs dans la transaction (lecture seule), `:332` commit, `:346-354` `setval` après COMMIT, échec = code 4 ; rollback = séquences intactes (aucun `setval` avant). `--inject-failure=count` prouve le chemin. |
| I4 code de sortie 1 | corrigé | `--ignore` + `--require-baseline` (`inventaire:177-218`), `.sh:56,68`. Écart explicite « IGNORÉE », baseline contrôlée côté PG réel (lue dans la base à l'inventaire). |
| M1 données perso dans erreurs | corrigé | `transfert:338-340` : SQLSTATE + table pour toute `PDOException`. Les `RuntimeException` ne citent que table.colonne. |
| M2 `fail()` dans le `try` | corrigé | plus aucun `fail()` dans le `try` ; `convert()` lève `RuntimeException`. |
| M3 FK | partiel | `$child` normalisé (`:189`) ; auto-référence et parent hors liste toujours ignorés ou rapportés en « circulaire » (voir M-B). |
| M4 types source | corrigé | `transfert:219-249` : encodage UTF8 et matrice de types avant tout TRUNCATE. Preuve négative faite sur copie (non versionnée, 022). |
| M5 `.sh` | corrigé en grande partie | port lu dans l'URL, schéma vérifié, 1 seul `php -r`. Reste : utilisateur non `rawurldecode` (M-C). |
| M6 `--rename` | corrigé | paire invalide et cible déjà présente → code 2 (`inventaire:162-176`) ; SKILL.md à jour. |

### Importants

**I-A — Le garde-fou « cible » est neutralisé par `rejouer-transfert.sh` et reste fondé sur le seul nom de base**
`transfert:111,143` ; `rejouer-transfert.sh:44`. Le `.sh` ajoute lui-même `--yes --replace-existing` :
l'opérateur ne confirme rien, les deux drapeaux deviennent automatiques. Les comptages de la cible sont
affichés (`:256-260`) mais le script enchaîne sans arrêt, même si la cible contient des données. Le seul
vrai verrou est `EXPECTED_TARGET_DB`. Après la bascule, la base PostgreSQL de production portera très
probablement le même nom `sf_mangatrackr_app` (ou ce sera un `--allow-target` copié-collé), et l'hôte n'est
pas contrôlé : un `DATABASE_URL` de production exporté dans le shell suffit à vider la production en une
commande `./rejouer-transfert.sh out`. Scénario : bascule réelle, rejeu de répétition lancé dans un
terminal où la variable pointe la prod.
Correction : le `.sh` ne doit pas ajouter `--replace-existing` ; en cas de cible non vide, exiger un
drapeau/variable explicite saisi par l'humain (ex. `CONFIRM_TARGET=<hôte>/<base>`), ou refuser tout hôte
hors liste (`database`/`postgres` du compose) sans `--allow-host=`. Documenter que la constante est à
adapter en production (022 le signale déjà).

**I-B — `searchTerm` absent ou non chaîne : `TypeError` (HTTP 500) — ADR 0001 non traité**
`MangaController.php:39-42`. `$request->request->get('searchTerm')` renvoie `null` si le champ manque ;
`SearchTermValidator::validate(string)` sous `strict_types` lève alors `TypeError`. Un POST `/manga/search`
sans champ (ou un tableau → `BadRequestException` 400, acceptable) donne une 500. Le comportement
préexistait (ancienne méthode privée typée `string`), mais le refactor extrait un service public et aucun
test ne couvre `null`. Correction : conversion à la frontière (`$searchTerm = (string) ($request->request->get('searchTerm') ?? '')` ou
`$request->request->getString()`), puis test fonctionnel POST sans champ → 200 avec `hasError`.

### Mineurs

- **M-A — Documentation du validateur inexacte** (`SearchTermValidator.php:14-17`). « Rejeté : caractères de
  contrôle » est faux pour `\t \n \r \v \f` (et U+0085, U+2028…) : `\s` les admet (le `/u` de PHP active
  aussi UCP). Un terme multi-lignes passe. Sans conséquence XSS/SQL (Twig échappe, DQL paramétré) mais le
  test « contrôle » n'utilise que `\x07` et `\0`. Corriger la doc, ou remplacer `\s` par `\x20` / `\p{Zs}`.
- **M-B — `_` reste un joker LIKE** (`MangaRepository.php:123-125`). `%` et `\` sont rejetés, pas `_`
  (autorisé par l'ancienne et la nouvelle regex) : « dr_stone » trouve « dr-stone ». Préexistant, bénin ;
  échapper `_` dans le repository si on veut une recherche littérale.
- **M-C — Utilisateur PG de `DATABASE_URL` non décodé** (`transfert:131`, `.sh:35`) alors que le mot de passe
  l'est : un utilisateur avec `%40` échouerait (échec franc, code 3).
- **M-D — Codes de sortie non totalement couverts** : une exception non rattrapée (requête de métadonnées
  `:179-249`, inventaire hors connexion) sort en 255, absent de la table 0-4 ; et l'échec de connexion d'un
  `inventaire.php` appelé par le `.sh` sort en 2 (usageError) alors que SKILL.md/.sh annoncent 3 pour
  « connexion ». Cohérence sinon correcte : transfert 0/2/3/4 propagé par `set -e`, inventaire 0/1/2,
  `.sh` : 1 si écart d'une des deux passes, `exit "$RC"` si ≥ 2.
- **M-E — M3 résiduel** (`transfert:188-194`) : une FK auto-référente est ignorée sans message (ordre
  d'insertion non garanti) et une FK vers une table hors liste produit un faux « dépendances circulaires ».
  Aucun cas aujourd'hui (18 FK).
- **M-F — `--require-baseline`** ne vérifie que la présence de la version, pas qu'elle est seule (018
  suggérait « = 1 »). Une migration parasite côté PG n'est pas signalée. `--inject-failure` est un hook de
  test resté dans le script de production (documenté, inoffensif sans le drapeau).
- **M-G — Sous-agent « sous réserve »** : test négatif M4 non versionné ; encodage non-UTF8 non testé ;
  doublons d'emails à la casse près sur le dump final non testés (déjà listés en 022).

### Vérifications positives
- **Validateur** : regex `^[class]*$` ancrée, une seule classe quantifiée, `/\S/u` linéaire : pas de
  ReDoS. `/u` présent sur les deux regex ; UTF-8 invalide → `LengthValidator` signale CHARSET_ERROR sans
  exception et `preg_match` renvoie `false` → violation (test « UTF-8 invalide »). Longueur en caractères
  (`mb_strlen`), 50 « é » acceptés. `% \ ' " < > & ;` rejetés, donc `htmlspecialchars` (contrôleur `:46`)
  devient un no-op et l'apostrophe n'est pas autorisée : cohérent (ce n'est pas une régression, l'ancien
  motif la rejetait aussi). `final`, `readonly`, types, `strict_types`, injection par constructeur : OK
  (autowiring via `services.yaml`). PHPStan vert.
- **Contrôleur** : mince, plus de `ValidatorInterface`/`Assert` ; retour inchangé. Test du contrôleur
  existant (`CatalogControllerTest`) non modifié ni affaibli.
- **Subscriber** : `?? null` + `is_string` + non vide gèrent liste vide, clé absente, `mangas` scalaire
  (l'accès offset avec `??` ne lève pas). Slug inconnu → retour sans flush. Les trois cas sont testés
  (data provider) et aucun test existant n'est supprimé. Effet de bord : la liste vide continue
  d'aboutir à `json(false, 200)` dans `ScanthequeController:55` (inchangé, hors périmètre). Reste le risque
  préexistant `getMangaStatistic()` null pour un manga sans statistique (7 412 mangas / 7 415 stats : pas
  de cas connu) et `toArray()` qui lève sur corps vide/non JSON (400 de HttpFoundation).
- **Secrets / données perso** : aucun mot de passe, URL avec identifiants ni email dans les scripts,
  rapports 017-022, journal ou JSON 022 (0 occurrence de `@` dans les 4 JSON ; contenu = noms de
  tables/colonnes, comptages, sha256 agrégés par table, séquences, identifiants de versions de migration).
  Seul le hash agrégé d'`app_user` (6 lignes) y figure : non exploitable. Mot de passe transmis par variable
  d'environnement, jamais par argument (hors option `--password` déconseillée dans SKILL.md).
- **Migrations** : aucune migration modifiée ou ajoutée.

## Écarts, risques, points non vérifiés
- Rien n'a été exécuté contre les bases ni PHPUnit : les rejeux et les 217 tests reposent sur le rapport 022.
- Le comportement de `STRINGIFY_FETCHES` sur les booléens PG (strict) n'est pas relu ; il est validé par
  l'observation de la parité 022, pas par lecture.
- Tri des lignes par clé primaire : si une PK de type texte apparaissait, la collation différente entre
  MariaDB et PG produirait un faux écart (direction sûre), pas un faux « OK ».

## Recommandation pour la suite
- Clore T6 avec réserves. Avant la bascule de production (T7) : traiter I-A (supprimer l'ajout automatique
  de `--replace-existing`, contrôle d'hôte) et I-B (conversion de `searchTerm` à la frontière + test).
- Mineurs M-A à M-F au fil de l'eau ; corriger la doc du validateur (M-A).
