# 004 — developpeur — corrections des défauts T3/T4

- Tâche : T4.1
- Date : 2026-10-18
- Statut : terminé

## Objectif
Corriger les défauts M1, M2 et m1 à m7 du rapport 003, ainsi que les incohérences 1 et 2 du
rapport 002. Mettre `docs/deploiement/apps-dev.md` en cohérence, rejouer le banc avec un scénario
M1, puis passer `bash -n`, shellcheck, `make qa-php-stan` et `make test-only`.

## Ce qui a été fait

| Défaut | Fichier | Correction |
|---|---|---|
| M1 | `deploy/backup.sh` | Nouveau nom `backup_<base>_AAAAMMJJ-HHMMSS.dump` : préfixe `backup_` propre au script, plus sûr que `mangatrackr_[0-9]*`, et qu'aucun autre outil du dépôt ne produit. Glob strict sur l'horodatage. `rotate "$file"` exclut la sauvegarde qui vient d'être écrite et garde au plus `KEEP-1` autres sauvegardes : la courante n'est jamais supprimée. Les dumps `pg-export` (`mangatrackr_<base>_<date>.dump`), l'ancien motif et tout autre fichier ne sont ni comptés ni supprimés. En-tête mis à jour |
| m4 | `deploy/backup.sh` | Nom déjà pris (même seconde) : attente d'une seconde puis nouveau nom, sinon arrêt avec le code 3. Contrôle aussi juste avant `mv` : jamais d'écrasement |
| M2 | `compose.prod.yaml` | `name: sf-mangatrackr` en tête, avec un commentaire |
| M2 | `deploy/lib.sh` | `COMPOSE_PROJECT_NAME` vaut par défaut `sf-mangatrackr` et est exporté : scripts et Compose utilisent le même nom, et la surcharge passe par une seule variable |
| M2 | `deploy/deploy.sh` | Nouveau `check_compose_project` dans le préflight, donc aussi avant `--rollback`. Si le label `com.docker.compose.project` de `mangatrackr_app_prod` ou `mangatrackr_worker_prod` diffère du nom attendu : arrêt avec le code 2 et le message « relancer avec COMPOSE_PROJECT_NAME=<label> » |
| m1 | `deploy/deploy.sh`, `deploy/check.sh` | `grep -v ... \| sed` remplacé par `sed -n '/[^[:space:]]/s/^/    /p'` : une sortie vide ne fait plus échouer le pipeline |
| m2 | `deploy/deploy.sh` | `save_previous` remplacé par `record_service_image` (relève `SERVICE_ID`, sans retag), `restore_tag` (`docker tag $SERVICE_ID $IMAGE`) et `promote_previous` (`docker tag $SERVICE_ID prod-previous`, appelé juste avant `up`). Une tentative interrompue ne modifie plus `prod-previous`. La variable `HAVE_PREVIOUS` est supprimée |
| m3 | `deploy/init-database.sh` | Nouveau `check_restore_target` (`CONFIRM_TARGET`, suffixe `_test`), appelé dans `main` avant `ensure_role` : un `--restore` refusé ne crée rien |
| m5 | `deploy/deploy.sh` | En `--dry-run`, une ref absente en local est cherchée par `git ls-remote --exit-code origin refs/heads/<ref> refs/tags/<ref>` (lecture seule) ; si elle existe : avertissement, puis checkout et pull, ou checkout détaché, affichés. Un commit non récupéré reste refusé : il est invérifiable sans fetch |
| m6 | `deploy/deploy.sh` | Si `check.sh` ou `up` échoue pendant `--rollback`, le message ne reconseille plus `--rollback` (il renvoie au dépannage et à la restauration). En dry-run, la sortie se termine par « Fin du dry-run : rien n'a été modifié. » (ou « (retour arrière) ») au lieu de « Déploiement terminé » |
| m7 | `docker/Dockerfile.prod.dockerignore` | Ajouts : `**/*.sql`, `**/*.zip`, `.claude/`, `.idea/`, `.devcontainer/`, `.github/`, `docs/`, `tests/`, `migrations-archive-mariadb/`. L'image n'en a pas besoin : rien ne les lit dans `config/`, `src/` ni `templates/`, et l'autoload de prod ne couvre que `src/`. J'ai exclu tout `docs/` plutôt que `docs/chantiers/` seulement |
| 002-2 | `deploy/mangatrackr.prod.env.dist` | En-tête : après un changement de variables, `docker compose -f compose.prod.yaml up -d --no-deps --no-build app` (puis `worker`), pas `deploy.sh` |
| m8 | — | Le `.dist` compte 17 clés, et non 18 comme l'annonce le rapport 001 : erreur du rapport, pas du code |

Documentation :
- `docs/deploiement/apps-dev.md` :
  - fiche de référence : ligne « Projet Compose » (`COMPOSE_PROJECT_NAME`) et nouveau nom des
    sauvegardes, aussi dans l'arborescence ;
  - section 4 : tableau des scripts mis à jour (`--restore` vérifié avant toute création,
    rotation limitée aux fichiers du script, code 3 pour un nom pris, `prod-previous` posé juste
    avant `up`, `ls-remote` en dry-run) ;
  - 5.6 : le sous-répertoire `import/` reste conseillé, mais n'est plus nécessaire pour la
    rotation ;
  - section 6 (migration unique, T6) : écarts attendus complétés (`name:`, exclusions) ; relevé
    `docker inspect -f '{{index .Config.Labels "com.docker.compose.project"}}'` de app et worker
    placé **avant** les scripts, avec la conduite à tenir si le label diffère (préfixer toutes les
    commandes par `COMPOSE_PROJECT_NAME=<valeur>`, puis signaler la valeur) ; l'ancien paragraphe
    de fin est supprimé ;
  - 7.4 : `ls` sur `backup_mangatrackr_*.dump`, règle de non-écrasement, nom à donner aux copies
    manuelles ;
  - 7.7 : sens précis de `prod-previous` et message de rollback ;
  - section 9 : quatre nouvelles lignes (projet Compose, ref inconnue en dry-run, « existe déjà »,
    clone en avance après une migration en attente).
- `docs/decisions/0015-...md` (ADR acceptée) : une phrase alignée sur m2 (« retaguée juste avant
  le `up` » au lieu de « avant chaque construction »). C'est la description d'un détail de mise en
  œuvre ; la décision elle-même ne change pas.

Banc :
- `scratchpad/t4/fakebin/docker` : prise en charge du format `com.docker.compose.project`
  (variable `FAKE_PROJECT_LABEL`, défaut `sf-mangatrackr`). Seul ajout au banc du testeur.
- Nouveau `scratchpad/t4/replay-t41.sh`, rejouable et autonome. Il recrée à chaque passage un bac
  à sable `t4/r41/` (origin avec le `compose.prod.yaml` courant, `APPS_ROOT`, état du faux Docker)
  et garde sorties et journaux dans `t4/r41-out/`. L'état du banc T4 (`t4/state`, `t4/apps`)
  n'est pas modifié. Résultat dans `t4/replay-t41.result`.

## Résultats
| Contrôle | Résultat |
|---|---|
| `bash -n` (6 scripts) | OK |
| shellcheck 0.10.0 `-x` | 0 remarque (rc 0) |
| `replay-t41.sh` | **64 PASS, 0 FAIL**, deux passages consécutifs identiques |
| dont M1 (KEEP=1, dump `pg-export`, ancien motif `mangatrackr_2026...` et `autre.dump` présents) | sauvegarde écrite conservée (600) ; fichiers étrangers intacts (sha1) ; 2e passage : seule l'ancienne `backup_` est supprimée ; le dry-run n'annonce que la suppression de la `backup_` |
| Rotation KEEP=7 avec 10 anciennes sauvegardes | 7 restantes (la courante + 6), les 4 plus anciennes supprimées, dump `pg-export` conservé |
| m4 | noms pris pour 4 secondes consécutives → code 3, rien d'écrasé, pas de `.part` ; deux sauvegardes enchaînées → 2 fichiers distincts |
| m1 | `up-to-date` en échec sans sortie → code 3 avec message, tag `prod` remis, aucun `up` |
| m2 | après un déploiement réussi (`prev` = image 1), une migration en attente puis un échec du build laissent `prev` inchangé ; `--rollback` revient à l'image 1 ; avec `--migrate`, ordre build < pg_dump < migrate < tag prev < up |
| M2 | label `autre` → code 2 et message `COMPOSE_PROJECT_NAME=autre`, sans build, tag ni up (contrôle aussi avant `--rollback`) ; avec `COMPOSE_PROJECT_NAME=autre`, préflight OK ; `config` réel : `name: sf-mangatrackr`, et `COMPOSE_PROJECT_NAME` a bien priorité (vérifié avec docker-compose v2.29.7) |
| m3 | sans `CONFIRM_TARGET` ou avec une mauvaise cible : code 5, aucun `CREATE`, ni `cp` ni `pg_restore` ; cible confirmée : création du rôle et de la base puis restauration |
| m5 | tag et branche poussés après le clone : dry-run accepté (`ls-remote`, aucun fetch exécuté) ; ref inexistante → code 2 ; déploiement réel `--ref v10` → code 0 |
| m6 | rollback suivi d'un check KO → code 4 sans conseil `--rollback` ; déploiement suivi d'un check KO → conseil `--rollback` ; dry-run → « Fin du dry-run » |
| Nominal | `check.sh` code 0 ; install-vm : 1er passage 700/600, 2e passage sans aucune action |
| Balayage des journaux et sorties | 0 `down`, 0 `DROP`, `compose config` toujours `--quiet`, marqueur de secret absent des arguments et des sorties |
| Contexte de construction (outil du testeur, 540 fichiers du poste) | 280 fichiers inclus au lieu de 476 ; aucun `.sql`, `.zip`, `.dump`, `.env`, `.claude/`, `.idea/` ni `ressources/` ; seul `deploy/mangatrackr.prod.env.dist` (placeholders) ; fichiers requis par le Dockerfile présents (composer, package, vhost, `bin/console`, `public/index.php`, webpack) |
| Valeurs de prod dans les fichiers modifiés (test booléen) | absentes |
| `make qa-php-stan` | `[OK] No errors` |
| `make test-only` | `OK (226 tests, 580 assertions)` |

## Écarts, risques, points non vérifiés
- **Les 93 scénarios d'origine du testeur ne sont pas rejoués un par un** : le banc T4 n'a pas
  de script d'enchaînement (les exécutions étaient manuelles). `replay-t41.sh` couvre les
  scénarios corrigés et les parcours nominaux (déploiement, migration, rollback, check,
  install-vm), mais pas les cas d'erreur du préflight ni ceux de `check.sh` (états des
  conteneurs, HTTP, secrets dans les journaux). Ces cas passent par du code non modifié, sauf le
  `sed` de m1 dans `check.sh`.
- Pendant la mise au point, 3 échecs étaient dus au banc lui-même (compteur de migration
  simulée, échappement `%q` du journal) et non aux scripts. Ils ont été corrigés dans le banc.
- **Changement de nom des sauvegardes** : `backup_mangatrackr_...` au lieu de
  `mangatrackr_...`. `backup.sh` n'a encore jamais tourné sur la VM, donc aucun fichier existant
  n'est concerné. Une sauvegarde nommée à l'ancienne ne serait plus jamais supprimée par la
  rotation.
- M2 dépend du label réel relevé en T6. Si ce label n'est pas `sf-mangatrackr`, il faut
  utiliser `COMPOSE_PROJECT_NAME` partout (c'est documenté), puis reporter la valeur dans le dépôt.
- m5 : un SHA de commit non encore récupéré reste refusé en dry-run (`ls-remote` ne liste que
  les refs).
- Le reste n'a toujours pas été vérifié pour de vrai (Docker, PostgreSQL, VM) : voir le rapport
  003. C'est le rôle de T6.

## Recommandation pour la suite
- T5 (relecteur) : relire en priorité `deploy/deploy.sh` (m2 et M2), `deploy/backup.sh` (M1)
  et la section 6 de `apps-dev.md`. Pour rejouer :
  `bash /tmp/claude-0/-var-www/f578a951-56ed-48f3-8d75-06a131c21ff4/scratchpad/t4/replay-t41.sh`.
- T6 : relever le label du projet Compose avant tout script, puis `init-database.sh --dry-run`
  et un vrai `backup.sh`, ce qui valide aussi `pg_restore --list` sur l'entrée standard.
- Hors périmètre : `ressources/mangatrackr.prod.env` est en 664 sur le poste de dev (constat du
  testeur) ; `chmod 600` conseillé, à faire par l'utilisateur.
