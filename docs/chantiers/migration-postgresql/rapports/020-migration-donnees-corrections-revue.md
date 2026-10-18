# 020 — migration-donnees — Corrections de la revue 018 (I1-I4 + mineurs)

- Tâche : T6-bis
- Date : 2026-10-18
- Statut : partiel (I1-I4 corrigés et prouvés sur base scratch ; `make test-only` rouge pour une cause étrangère, voir plus bas ; rejeu sur la base de dev en phase 2)

## Objectif
Corriger les 4 constats importants de 018 sans toucher à la base de dev (recette 019 en cours),
et traiter les mineurs/parité dans la mesure du possible.

## Ce qui a été fait
Fichiers modifiés (dans `.claude/skills/verif-donnees/scripts/`) : `transfert-mariadb-pgsql.php`,
`inventaire.php`, `rejouer-transfert.sh`. `SKILL.md` n'a PAS été mis à jour (reste à faire).
Environnement de test : base PostgreSQL `sf_mangatrackr_app_scratch` créée avec le rôle applicatif
(CREATEDB), schéma = migration baseline `Version20261018145659` (via `doctrine:migrations:migrate`
avec `DATABASE_URL` surchargée), source `_import` en lecture seule, **supprimée à la fin** (vérifié :
il ne reste que dev et test). Aucune écriture sur dev ni test ; aucun TRUNCATE hors scratch.

- **I1** : `ini_set('zend.exception_ignore_args','1')` dans les deux scripts ; connexions via une
  fonction `connect()` qui attrape `PDOException` et affiche un message générique (libellé source/cible +
  code), sans DSN, utilisateur, ni message serveur. `inventaire.php` : idem pour `--dsn`.
- **I2** : `--yes` ET `--replace-existing` exigés (code 2 sinon) ; hôte, base et comptages de la cible
  affichés avant le TRUNCATE ; base différente de `sf_mangatrackr_app` refusée sans
  `--allow-target=<nom exact>` ; `_test` et « même nom que la source » toujours refusés. Le `.sh` ajoute
  les deux drapeaux lui-même et transmet les autres options (`--allow-target=…`).
- **I3** : ordre = chargement, contrôle des comptages, calcul des valeurs de séquences (lecture),
  COMMIT, puis seulement `setval`. Échec du `setval` : code 4 et message « relancer ». Option de test
  `--inject-failure=count` (fausse un comptage après tout le chargement) documentée dans l'en-tête.
- **I4** : `inventaire.php --compare … --ignore=<table>` (répétable ou virgules ; table inconnue = erreur
  2 ; la table est affichée `IGNORÉE`), `--require-baseline=<Version>` (l'inventaire stocke la liste des
  versions ; accepte le nom nu ou le FQCN Doctrine). Codes : 0 parité, 1 écart réel, 2 usage/lecture/connexion.
  Le `.sh` les utilise ; codes propres au transfert : 3 (échec, ROLLBACK), 4 (séquences après COMMIT).
  En-tête du `.sh` réécrit.
- **Mineurs/parité faits** : M1 (erreurs PDO : SQLSTATE + table, jamais `getMessage()`), M2 (`convert()`
  et contrôles lancent des exceptions, seul le `catch` appelle `fail()`), M3 (normalisation des noms
  cités dans les FK), M5 (port lu dans `DATABASE_URL`, un seul parsing, refus d'une URL non PostgreSQL,
  `SOURCE_DSN`/`MARIADB_USER` paramétrables), M6 (`--rename` : paire invalide et collision = erreur 2),
  mode `--strict` reproductible (valeurs brutes, NULL→`\N`, booléens 1/0, composite inclus, 3 tables de
  jointure couvertes ; le `.sh` fait les deux passes), tolérance décimale limitée aux colonnes
  numériques, séquences comparées (prochaine valeur = MAX+1, par colonne) dans l'inventaire PostgreSQL.

## Résultats (tous sur scratch)
| Preuve | Résultat |
|---|---|
| I1 mauvais mot de passe source / cible / inventaire | codes 3 / 3 / 2, message générique, 0 occurrence du faux mot de passe dans la sortie |
| I2 sans `--replace-existing` ; scratch sans `--allow-target` ; `--allow-target` erroné ; `_test` avec allow | tous refusés, code 2, avant toute connexion à la cible |
| I2 rejeu sur cible chargée | affiche les 19 tables non vides, 71 014 lignes, « sera DÉTRUIT » |
| I3 : séquence avancée à la main (hash des 19 séquences ≠ état chargé), puis `--inject-failure=count` | code 3 « tables et séquences inchangées » ; hash séquences et hash comptages **identiques avant/après** ; rejeu normal ensuite : séquences revenues à MAX+1 |
| I4 `rejouer-transfert.sh` sur scratch | code **0** : 22 tables OK (tolérant et strict), 19 séquences sans écart, baseline présente, `doctrine_migration_versions` IGNORÉE affichée |
| I4 rejeu ×2 | code 0, inventaires PostgreSQL identiques (hors horodatage) |
| I4 écart réel (nom d'éditeur modifié, séquence faussée, version de migration remplacée) | code **1**, 3 écarts listés (contenu, séquence, baseline ABSENTE) ; modes mixtes / fichier absent / `--ignore` inconnu : code 2 |

Nuance sur I1 : sous PHP 8.2+, le constructeur `PDO` masque déjà le mot de passe (`SensitiveParameterValue`) ;
la fuite décrite par 018 n'a **pas pu être reproduite** (le DSN et l'utilisateur apparaissaient en revanche
dans la trace, avec l'adresse IP du client dans le message serveur). Le correctif reste utile et ne coûte rien.

`make qa-php-stan` : OK. `make test-only` : **1 échec sur 217**,
`SearchTermValidatorTest::testRejected` (chaîne blanche), sans lien avec ces scripts (hors `src/` et
`tests/`) ; l'arbre contient des modifications `src/Controller/MangaController.php`,
`src/EventSubscriber/MangaStatisticSubscriber.php` et son test, qui ne sont pas de moi. Non investigué plus.

## Écarts, risques, points non vérifiés
- Non rejoué sur la base de dev (consigne) : aucune preuve sur `sf_mangatrackr_app` elle-même, ni sur
  les données du testeur. Le dry-run prend des verrous exclusifs (TRUNCATE) : à ne pas lancer pendant une recette.
- Les inventaires 017 versionnés sont en mode antérieur (sans séquences ni mode) ; à régénérer au rejeu.
- Restent : M4 (contrôle des types source, `server_encoding`), `SKILL.md` (documenter `--strict`,
  `--ignore`, `--require-baseline`, `--rename`, scripts de transfert), correction de la section « Code de
  sortie » de 017, test du dump final avec doublons d'emails à la casse près, `doctrine:schema:validate` (T4.5).
- Le garde `EXPECTED_TARGET_DB` est une constante : à adapter si la base de production porte un autre nom
  (alors utiliser `--allow-target`).

## Recommandation pour la suite
Phase 2 (sur demande du chef, recette 019 terminée) : depuis le devcontainer,
`MARIADB_PASSWORD=… sh .claude/skills/verif-donnees/scripts/rejouer-transfert.sh docs/chantiers/migration-postgresql/rapports/020-run`
(sans option : la cible dev est la valeur attendue) ; attendre code 0 ; ranger les 4 JSON régénérés
(`020-inventaire-*.json`) ; refaire un rejeu pour vérifier l'identité. Avant : faire corriger le test
rouge `SearchTermValidatorTest` par son auteur, puis relancer `make test-only`.
