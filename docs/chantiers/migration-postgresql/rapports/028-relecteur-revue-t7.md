# 028 — relecteur — Revue de la tâche T7 (plan de bascule, ADR 0013, dette)

- Tâche : T7 (relecture)
- Date : 2026-10-18
- Statut : terminé
- Verdict : **validé avec réserves** (aucun bloquant ; 4 points importants à corriger avant la répétition)

## Objectif
Relire `plan-bascule.md`, ADR 0013, index des ADR, `etat-et-dette-technique.md` et `contexte.md`
(non commités) contre le dépôt : scripts, baseline, dump, rapports 017/019/022/023/024/026/027, ADR 0012.

## Ce qui a été fait
Lecture seule. Relu `rejouer-transfert.sh`, `transfert-mariadb-pgsql.php`, `inventaire.php` (options),
`import-dump.php` (options), baseline, `docker/postgres/init/*`, `docker-compose.yml`, `.gitignore`,
JSON d'inventaire 022, en-tête du dump (`SET time_zone`, `AUTO_INCREMENT=`). Aucune requête sur une base,
aucun test relancé (226/580 confirmés seulement par les rapports 024/025 et le journal).

## Résultats

### Bloquant
Aucun. Les erreurs de commande trouvées échouent « fermées » (code 2), sans toucher à une base.

### Important
- **I1. Simulation 9.3 : commande invalide** (`plan-bascule.md:291-293`). Le `php transfert-mariadb-pgsql.php --yes --dry-run`
  omet `--replace-existing`, exigé aussi en dry-run (`transfert-mariadb-pgsql.php:125-127`) : sortie code 2,
  rien d'exécuté. Correction : ajouter `--replace-existing` (et noter que `CONFIRM_TARGET` reste exigé, déjà dit).
- **I2. Requête ICU fausse sur la cible PostgreSQL 18** (`plan-bascule.md:213`, `:217-218`). La requête principale
  utilise `daticulocale`, colonne renommée `datlocale` en PostgreSQL 17+ : elle échoue sur 18, la version visée.
  Correction : requête principale `SELECT datname, datlocprovider, datlocale FROM pg_database …` (attendu `i`, `fr`),
  `daticulocale` seulement en note pour PG ≤ 16. Ajouter la vérification de l'extension : `SELECT extname FROM pg_extension WHERE extname='unaccent'`
  après la baseline (G5), et un test fonctionnel `SELECT unaccent('é')`.
- **I3. Requête « emails non normalisés » sans effet** (`plan-bascule.md:134`). Sur `_import` (collation `utf8mb4_unicode_ci`,
  insensible à la casse et PAD SPACE), `email <> LOWER(email)` est faux même pour `Foo@x`, et les espaces finaux sont ignorés :
  le comptage sera toujours ~0. Correction : comparer en binaire (`email COLLATE utf8mb4_bin <> LOWER(email)`, `BINARY email <> TRIM(email)`).
  Idem remarque sur les deux requêtes de doublons : `GROUP BY` en collation `_ci` regroupe aussi les accents (faux positifs
  possibles, donc prudents, mais à signaler) ; elles ne prouvent pas ce qu'une `LOWER(email)` PostgreSQL rejettera. Le contrôle
  fiable est la dry-run du transfert (code 3) en répétition, à citer comme second filet.
- **I4. Plan sur Q19 / séquences : écart D3 vs script non tranché alors que la réponse est claire.**
  Lequel est correct : **l'ADR 0012 D3** (accepté) — il garantit « jamais réutiliser un ID supprimé » ; `MAX+1`
  (`transfert-mariadb-pgsql.php:356-360`, `inventaire.php:128` qui valide aussi `MAX+1`) ne le garantit pas.
  Le dump contient `AUTO_INCREMENT=` sur 16 tables (vérifié), donc la donnée existe, et l'import `_import` la conserve
  (`information_schema.tables.auto_increment`). Mais attention : `AUTO_INCREMENT=` n'est dans le dump que si l'option
  d'export phpMyAdmin est cochée : à ajouter à 6.3 et à vérifier à 6.4. À corriger : (a) soit faire évoluer le script
  (lire `information_schema.TABLES.AUTO_INCREMENT` de la source, `setval(GREATEST(MAX, AI-1))`) **et** `inventaire.php`
  (attendu), puis rejouer les 2 passes ; (b) soit amender l'ADR 0012 D3 par l'ADR 0013 (décision explicite que `MAX+1` suffit).
  Dans les deux cas retirer le « recaler à la main » de `plan-bascule.md:320-321` (non reproductible : aucune commande donnée,
  et un recalage manuel contredirait le critère « 19 séquences sans écart » de la parité). Proposer la requête de comparaison :
  `SELECT table_name, auto_increment FROM information_schema.tables WHERE table_schema='<…_import>'`.
  Impact réel faible aujourd'hui (6 comptes) mais réel pour `manga_user_track` (ID dans le `localStorage`).

### Mineur / risques oubliés
- **M1. Table `app_user` sur Hostinger** (`plan-bascule.md:167`) : sur MariaDB elle s'appelle `user`. Le contrôle de gel échouerait.
  Vérifier aussi que le `MAX(updated_at)` de `manga_statistic` bouge bien quand `nb_view` s'incrémente (colonne `updated_at`, nullable).
- **M2. Fuseaux horaires non traités.** Colonnes `TIMESTAMP WITHOUT TIME ZONE` copiées en chaîne (017) ; le dump pose
  `SET time_zone = "+00:00"`. Ajouter : fuseau PHP/`date.timezone` et fuseau du serveur PostgreSQL du homelab, et un contrôle de
  l'affichage des dates (calendrier de sorties) en recette R11.
- **M3. Charset** : le dump mêle `utf8mb3_unicode_ci` et `utf8mb4_unicode_ci` ; le pré-contrôle M4 du script couvre l'encodage
  cible, mais le plan ne le mentionne pas. Ajouter une ligne au critère 6.4.
- **M4. Sessions et cookies** : `framework.yaml` n'a pas de `handler_id` (sessions fichiers natives). À la bascule toutes les
  sessions sont perdues (reconnexion) ; si `APP_SECRET` change, remember-me et liens signés sont invalidés. À annoncer dans
  la communication (Q16) et à lister dans Q21.
- **M5. Messenger** : transport `use_notify: true` (rapport 002, constat 18). R12 vérifie la consommation, mais le plan ne demande
  pas de vérifier que la fonction/trigger NOTIFY existe dans la base de production (baseline) ni de purger/vider la file avant le gel.
- **M6. `.gitignore`** : `plan-bascule.md:78` dit « `ressources/` est ignoré par git » ; seul `/ressources/*.sql` l'est. Un dump
  `.sql.gz`/`.zip` (export Hostinger courant) ou un JSON/CSV déposé là ne l'est pas. Corriger la phrase ou élargir `.gitignore`.
- **M7. Codes de sortie (9.3)** : exacts pour 0-5, mais la ligne « 3 : cible inchangée » n'est pas vraie si l'échec vient des
  `inventaire.php` après COMMIT (leur code est propagé par `set -e`). Le code 0 mène déjà à la section 10 (parité faite dans le
  `.sh`) : reformuler « continuer vers les contrôles complémentaires ». Les options `--yes`/`--replace-existing` sont bien ajoutées
  par le `.sh`, `CONFIRM_TARGET` compare `hôte/base` sans le port (le plan est cohérent). `TRANSFER_EXPECTED_*` est hérité de l'environnement : OK.
- **M8. Source « dump du 15/10 »** (`:118`, `:315`) : date introuvable dans les rapports (le fichier présent est
  `u556516513_20261018_mangasync.sql`). Sourcer ou écrire « dump de référence ». « 71 029 lignes, 23 tables » = 71 014 + 15 versions
  de migration, cohérent avec le JSON 022 (23 tables, 71 015 lignes côté PG avec la baseline).
- **M9. Ambiguïté** : `<app_user>` désigne tantôt le rôle, tantôt la table `app_user` (section 7) ; renommer le placeholder en `<role_app>`.
- **M10. OAuth / mails / cache** : R5, R13 et 12.1 couvrent l'URI de redirection, le SMTP et `cache:clear`. Manque seulement :
  le SPF/DKIM est en Q10 (OK) ; l'envoi d'emails réels pendant la recette sur données de production peut écrire à de vrais
  utilisateurs (R6 : « email envoyé ») — prescrire un compte/une adresse de test et `MAILER_DSN` neutre pour la recette sur la base
  chargée (invitations/réinitialisations ne doivent pas viser les 6 comptes réels).
- **M11. Dette (027)** : le rapport et le journal annoncent « 16 lignes » ; le tableau « Dette issue de la migration PostgreSQL »
  en contient **15** (la 16e est la ligne « Base de test » réécrite dans l'autre tableau). Compter correctement dans le rapport/journal.
  Chaque ligne renvoie bien à un rapport existant. Le bloc « État actuel » (haut de `etat-et-dette-technique.md`) affiche encore
  « 169 tests, 329 assertions » alors que la nouvelle section dit 226/580 : incohérence interne à corriger. 226/580 : confirmé par
  les rapports 024 et 025 (non rejoué ici). 19 séquences, 22 tables de données et 71 014 lignes : confirmés par le JSON 022.
- **M12. ADR 0013 / 0012** : cohérent (B1-B8 prolongent D1-D9 ; renvoi bien fait vers la T7 prévue en fin d'ADR 0012). 0013 ne
  tranche pas Q19 alors qu'il le liste « à trancher » : cf. I4. Le statut « Proposée » est correct tant que les 22 questions sont ouvertes.
  CLAUDE.md continue d'annoncer MariaDB (stack) alors que `contexte.md` annonce PostgreSQL : divergence assumée par 027 (lignes à ajuster
  listées), à ne pas oublier.
- **Complétude retour arrière / go-no-go** : bon (principe, deux phases, conservation, seuil Q17). Manque : G10 reste un blanc ; le
  retour arrière « après ouverture » ne décrit pas la copie des écritures `nb_view` (négligeable) ; critère de volumétrie : non
  traité (71 k lignes, transfert 1,3 s mesuré en dev, donc sans objet pour le code, mais le plan renvoie à juste titre à la mesure
  en répétition). Aucune donnée personnelle ni secret dans les 5 fichiers (placeholders uniquement). Aucune invention sur le homelab :
  tout est en `[À COMPLÉTER]` / Q1-Q22.
- **Liens** : tous les chemins cités existent (scripts `.claude/skills/*`, `docker/postgres/init/*`, `ci.yml`, ADR 0012, rapports 017-027,
  `Version20261018145659.php`). Options vérifiées valides : `import-dump.php --dry-run --report --db --create-db --recreate --password`,
  `inventaire.php --expected --inventory`, variables `INVENTAIRE_DB_PASSWORD`/`IMPORT_DB_PASSWORD`/`MARIADB_PASSWORD`.

## Écarts, risques, points non vérifiés
- Rien exécuté : requêtes SQL du plan non rejouées (aucune base écrite ni lue). Les remarques I2/I3 sont issues de la sémantique
  PostgreSQL 17+ et des collations `_ci` MariaDB, non d'un essai.
- 226 tests / 580 assertions non relancés (confiance dans 024/025).
- Comportement réel de l'export phpMyAdmin Hostinger (`AUTO_INCREMENT`, compression) non vérifiable.

## Recommandation pour la suite
T7 peut être close une fois : (1) I1-I4 corrigés (I4 = décision de l'utilisateur sur Q19, ma recommandation : appliquer D3 dans le script),
(2) mineurs M1, M6, M11 corrigés (faux factuels simples), (3) questions Q1-Q22 traitées par l'utilisateur avant la répétition ; l'ADR 0013
reste « Proposée » jusqu'à la répétition. Aucun fichier modifié par cette revue en dehors du rapport et du journal.
