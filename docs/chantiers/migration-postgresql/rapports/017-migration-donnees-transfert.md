# 017 — migration-donnees — Transfert MariaDB (_import) vers PostgreSQL dev

- Tâche : T5 (T5.0 à T5.5)
- Date : 2026-10-18
- Statut : terminé

## Objectif
Transférer les 23 tables de `sf_mangatrackr_app_import` (MariaDB) vers la base de dev PostgreSQL
`sf_mangatrackr_app` (schéma = baseline `Version20261018145659`), recaler les séquences, prouver la
parité table par table, fournir une procédure rejouable pour la bascule.

## Ce qui a été fait
- **T5.0** : inventaire de `_import` relancé (utilisateur `mangatrackr`, SELECT seul) puis
  `--compare` avec `003-inventaire-mariadb-import.json` : **parité complète** (23 tables, lignes et
  empreintes identiques). Cible dev avant transfert : toutes les tables vides, sauf
  `doctrine_migration_versions` (1 ligne, la baseline).
- **T5.1** : `.claude/skills/verif-donnees/scripts/transfert-mariadb-pgsql.php` (PDO, deux
  connexions). Choisi plutôt qu'une commande Symfony : hors `src/`, donc hors PHPStan/CS, sans
  dépendance au kernel, et réutilisable pour la bascule. Mot de passe source par variable
  `MARIADB_PASSWORD` (jamais écrit sur disque), cible déduite de `DATABASE_URL`.
- **T5.2** : ordre des tables = tri topologique des clés étrangères lues dans `pg_constraint`
  (parents d'abord ; aucune dépendance circulaire). Une seule transaction PostgreSQL :
  `TRUNCATE` des 22 tables de données, `INSERT` multi-lignes par lots (500 lignes, borné à 10 000
  paramètres), contrôle des comptages, séquences, `COMMIT`. Toute erreur = `ROLLBACK`.
  Conversions pilotées par le type cible : `tinyint(1)` → boolean (0/1 strict, sinon erreur) ;
  `datetime` → `timestamp` (chaîne conservée, date à zéro refusée) ; colonnes `json` (stockées en
  `longtext` côté MariaDB) : texte conservé, validité JSON exigée (D7) ; `double` lu en chaîne
  (`ATTR_STRINGIFY_FETCHES`, évite la perte de précision d'un cast PHP) ; chaîne vide et NULL
  jamais convertis. `user` → `app_user` (D2).
  Garde-fous : `--yes` obligatoire, cible refusée si le nom finit par `_test` ou égale la source,
  session source `READ ONLY` + `START TRANSACTION WITH CONSISTENT SNAPSHOT`, aucun DDL,
  `--dry-run` (rollback) disponible.
- **T5.3** : `setval(seq, MAX(id), true)`, ou `setval(seq, 1, false)` si table vide, pour chaque
  colonne ayant une séquence (`pg_get_serial_sequence`).
- **T5.4** : `inventaire.php` étendu avec `--rename=user:app_user` pour `--compare` (modification
  minimale, 11 lignes). Contrôle complémentaire hors dépôt (scratchpad) : empreinte **stricte**
  (valeurs brutes, sans réencodage JSON ni arrondi, booléens 0/1 seulement).
- **T5.5** : `.claude/skills/verif-donnees/scripts/rejouer-transfert.sh <dossier>` enchaîne
  transfert, deux inventaires et comparaison.
- Fichiers : les deux scripts ci-dessus (nouveaux), `inventaire.php` (modifié),
  `rapports/017-inventaire-*.json` (remplacés par `022-inventaire-*.json`, nouveau format avec séquences et mode), ce rapport, journal.

## Résultats
Transfert : 71 014 lignes (= 71 029 − 15 lignes de `doctrine_migration_versions`), 1,3 s.

| Tables | Comptage | Empreinte normalisée (`--compare`) | Empreinte stricte |
|---|---|---|---|
| 22 tables de données (dont `user` → `app_user`) | égal | **identique** | identique sur les 19 tables avec colonne `id` ; les 3 tables de jointure (`manga_editor`, `manga_fantrad`, `manga_manga_type`) n'ont pas été testées en strict (pas de colonne `id`) |
| `doctrine_migration_versions` | 15 ↔ 1 | différente (attendu) | non applicable |

- `doctrine_migration_versions` : **non transférée volontairement**. MariaDB contient les 15
  versions MySQL archivées ; la cible contient la seule baseline PostgreSQL
  (`Version20261018145659`), conservée. Écart attendu et justifié, pas un défaut.
- Colonnes : aucune `COLONNES DIFFÉRENTES` (mêmes noms partout, hors renommage de table).
- **Séquences : 19 et non 16** (19 colonnes `id` en `SERIAL`, dont `manga_release_config`,
  `messenger_messages`, `user_reset_password`, vides). Toutes recalées : `last_value` = MAX(id)
  (ex. `manga` 7 428, `release_manga_updates_api` 22 966) ; tables vides : non appelée, prochaine
  valeur 1.
- **Rejeu** : procédure lancée deux fois de suite ; les deux inventaires PostgreSQL (lignes,
  empreintes, colonnes) sont identiques, résultat de comparaison identique. L'inventaire a été
  refait après `make test-only` : parité inchangée (les tests n'écrivent que dans `_test`).
- `make qa-php-stan` : `[OK] No errors`. `make test-only` : `OK (193 tests, 546 assertions)`.

## Procédure de bascule (T5.5)
```
export MARIADB_PASSWORD=…          # lu dans docker-compose.yml (${DB_PASSWORD:-…}), non écrit sur disque
# DATABASE_URL du shell = PostgreSQL cible
php .claude/skills/verif-donnees/scripts/transfert-mariadb-pgsql.php --yes --dry-run   # simulation
.claude/skills/verif-donnees/scripts/rejouer-transfert.sh docs/chantiers/migration-postgresql/rapports/<NNN>-controle
```
Code de sortie du `.sh` (corrigé, rapport 020) : 0 = transfert validé et parité complète des deux
passes (`doctrine_migration_versions` IGNORÉE explicitement mais baseline `Version20261018145659`
exigée), 1 = écart réel, 2 = usage/garde-fou, 3 = échec avec ROLLBACK, 4 = séquences à recaler après
COMMIT. Le code 1 n'est plus « attendu ». Pour la bascule finale : réimporter le dump final dans `_import` (skill
`import-dump-mariadb`), refaire T5.0 (comparaison à l'attendu du nouveau dump), puis lancer la
procédure. Le schéma doit être migré au préalable (`doctrine:migrations:migrate`).

## Écarts, risques, points non vérifiés
- L'empreinte normalisée de `inventaire.php` est tolérante (JSON ré-encodé, flottants arrondis) ;
  c'est pourquoi le contrôle strict a été ajouté, mais sans l'intégrer au dépôt (ad hoc).
- Jointures sans `id` : seulement l'empreinte normalisée (valeurs entières, risque faible).
- Les `double` sont lus en texte MariaDB puis convertis par PostgreSQL : identité stricte vérifiée
  sur les données actuelles, pas garantie en général (cas extrêmes non testés).
- Le transfert alimente la **base de dev** ; l'application reste dépendante des données de
  prod copiées en local (données personnelles présentes dans la base dev : à garder locale).
- `make test-only` écrit dans `_test` (comportement normal, laisse 2 utilisateurs de test par
  passage, cf. rapport 010/013) ; aucune autre action sur `_test`.
- Mapping de mot de passe MariaDB : compose utilise `${DB_PASSWORD:-défaut}` ; le défaut fonctionne
  pour `mangatrackr`. Le mot de passe réel de la prod n'est pas concerné.
- Écart avec la consigne : 19 séquences, pas 16.
- Aucune migration ni la baseline modifiée ; aucun commit ; `etat.md` non touché.

## Recommandation pour la suite
- Chef : valider T5, puis T6/T7 (bascule) avec la procédure ci-dessus sur le dump final ;
  envisager de relire le script (relecteur) et d'ajouter un mode `--strict` à `inventaire.php`.
- Décider si la base dev PostgreSQL chargée (données réelles) doit rester ainsi avant les
  tests de recette.
