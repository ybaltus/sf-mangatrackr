# 003 — testeur — tests simulés des scripts de déploiement

- Tâche : T4
- Date : 2026-10-18
- Statut : terminé (avec défauts à corriger, voir plus bas)

## Objectif
Vérifier `deploy/*.sh`, `compose.prod.yaml`, `docker/Dockerfile.prod.dockerignore` et les cibles
`prod-*` sans Docker, sans vraie base et sans rien modifier dans `deploy/`, `compose.prod.yaml`
ni les Dockerfile. Les points à vérifier listés à la fin du rapport 001 sont traités ici.
Il faut aussi passer `make qa-php-stan` et `make test-only`.

## Ce qui a été fait
Tout est dans le scratchpad `/tmp/claude-0/-var-www/f578a951-56ed-48f3-8d75-06a131c21ff4/scratchpad/t4/`.
Aucun fichier du dépôt n'a été modifié, sauf ce rapport et une ligne du journal.
- `fakebin/docker` : faux Docker sans démon. Chaque appel est écrit dans `logs/<scénario>.log`,
  et l'entrée standard des `exec -i` va dans `state/stdin.log`. L'état simulé (réseaux, tags
  d'image, image du conteneur app, rôle, base, tables, migrations en attente) est dans `state/`.
  Des variables `FAKE_*` choisissent le scénario : démon arrêté, états des conteneurs, échec du
  build, de pg_dump, de la migration ou de `up`, base injoignable, secrets dans les journaux, etc.
  `compose config` est **réellement** validé par le binaire autonome docker-compose v2.29.7.
- `fakebin/git` : journalise chaque appel puis délègue au vrai git. L'URL GitHub du `clone` est
  remplacée par un dépôt nu local (`origin.git`) : commits `seed`, `v2`, `v3`, tags `v1` et `v9`.
- `fakebin/curl` : journalise et renvoie `FAKE_HTTP_CODE`. Le vrai `flock` est utilisé.
- `harness.sh` : `APPS_ROOT` pointe sur le scratchpad (donc aussi PROJECT_DIR, SECRETS_DIR,
  ENV_FILE et BACKUP_DIR), avec le PATH des faux outils.
- Faux fichier d'env `env/fake.prod.env` (600), tiré du `.dist`. Les placeholders sont remplacés,
  et APP_SECRET, le mot de passe de `DATABASE_URL` (forme `%%40`) et le secret OAuth contiennent
  un marqueur unique. Ce marqueur est ensuite cherché dans tous les journaux et toutes les sorties.
- `tools/dockerignore_check.py` : reproduction approchée de `moby/patternmatcher`, appliquée à
  des chemins types et à la liste réelle des fichiers du poste (noms seulement).
- Code source de Compose v2.29.7 (`pkg/compose/run.go`, `start.go`, `convergence.go`,
  `cmd/compose/run.go`, `up.go`) téléchargé dans `scratchpad/src-compose/`, pour les questions
  sur `run` et `--wait`.
- 93 exécutions de scripts (dry-run et réelles sur les faux outils), chacune avec sa sortie
  (`out/`) et son journal d'appels (`logs/`).

## Résultats

### Analyse statique
| Contrôle | Résultat |
|---|---|
| `bash -n` sur les 6 scripts | OK |
| shellcheck 0.10.0 `-x` | 0 remarque |
| shellcheck `-o all` (contrôles optionnels) | Uniquement du style ou de l'info : SC2250, SC2292, SC2310, SC2312 et SC2249. J'ai relu chaque SC2310 (`set -e` désactivé dans une fonction appelée dans un `||` ou un `if`) : pas de défaut, à part le cas m1 ci-dessous, qui vient d'un pipeline et non d'un SC2310 |

### Compose et contexte de construction
| Contrôle | Attendu | Obtenu |
|---|---|---|
| `config --quiet` avec `MANGATRACKR_ENV_FILE` = faux env | rc 0, rien affiché | rc 0, sortie vide, marqueur absent |
| `config --quiet` sans variable | erreur sur le chemin par défaut | « env file /opt/apps/secrets/mangatrackr.prod.env not found » |
| Port par défaut et surchargé | `192.168.1.29:8080`, puis `127.0.0.1:18080` | conforme |
| env_file interpolé | 17 clés chargées, guillemets retirés, `%%` conservé | conforme. Le `.dist` compte **17** clés et non 18 comme l'écrit le rapport 001 (même liste que les variables de build du Dockerfile) |
| healthcheck app | curl -fsS, 30 s / 5 s / 3 / 30 s | conforme ; worker sans healthcheck, `pull_policy: never` |
| Réseaux | `database` et `proxy` externes | `external: true` pour les deux ; app sur les deux, worker sur `database` |
| Nom du projet Compose | — | dérivé du répertoire (`www` ici, `sf-mangatrackr` sur la VM) : voir M2 |
| dockerignore : `ressources/*` (env, dump, sql, doc), `.env`, `.env.*`, `**/.env.local`, `*.prod.env`, `**/*.dump`, `.git`, `vendor`, `var` | exclus | exclus (approximation de patternmatcher) |
| dockerignore : fichiers réels du poste (540, hors vendor, node_modules, .git et var) | aucun secret ni dump dans le contexte | 476 inclus. Rien de sensible, sauf `.claude/settings.local.json`, `.idea/` et `*.sql` / `*.zip` hors `ressources/` qui ne sont pas exclus (m7) |

### Scénarios (faux outils)
| Scénario | Attendu | Obtenu |
|---|---|---|
| install-vm `--dry-run`, arbre vide | uniquement des lignes `[dry-run]`, rien de créé | 9 actions affichées, `APPS_ROOT` absent après coup, aucun appel modifiant |
| install-vm réel, arbre vide | répertoires, droits 700/600, 2 réseaux, clone, copie de l'env | conforme : `secrets` et `backups` en 700, env en 600 identique au `.dist`, `network create` ×2, `clone` ×1 |
| install-vm, 2e passage réel | aucune création | 0 appel modifiant, arbre identique (mtime et droits), env rempli non écrasé (sha1 identique), 2e dry-run sans aucune ligne `[dry-run]` |
| install-vm, droits dérivés (755 et 644) | remis à 700 et 600 | conforme, en dry-run (affichage seul) comme en réel |
| install-vm, placeholders restants | avertissement | « 9 valeur(s) encore au format <...> » |
| Docker absent (install, deploy, check) | arrêt propre | rc 2, « docker introuvable ... section Docker » |
| Démon arrêté | arrêt propre | rc 2, « démon Docker injoignable » |
| PROJECT_DIR non vide et non git | refus, aucun clone | rc 3, aucun `git clone` |
| init-database : rôle et base présents (réel et dry-run) | aucune création | « déjà en place », 0 `CREATE` |
| init-database : absents, `--dry-run` | CREATE affichés avec mot de passe masqué | `PASSWORD '***'`, rien d'écrit dans l'état |
| init-database : absents, réel | CREATE ROLE puis CREATE DATABASE (ICU fr, template0) | conforme ; `SET log_statement`, `log_min_duration_statement` et `log_min_error_statement` envoyés avant `CREATE ROLE` ; mot de passe décodé (`%%40` → `@`) présent **seulement** sur l'entrée standard de psql ; 3e passage = no-op |
| `--restore` sans `CONFIRM_TARGET` ou avec une mauvaise cible | refus | rc 5, aucun `cp` ni `pg_restore` ; mais rôle et base sont créés **avant** le refus (m3) |
| `--restore`, base non vide (37 tables), réel et dry-run | refus | rc 5, « contient déjà 37 table(s) », aucun `cp` ni `pg_restore` |
| `--restore`, base vide, confirmée, dry-run | `cp` et `pg_restore` affichés | conforme, 0 appel exécuté |
| `--restore`, base vide, confirmée, réel | cp → chmod 600 → `--list` → pg_restore `--single-transaction` → rm | ordre conforme, fichier temporaire supprimé par le trap |
| `--restore` : base `*_test`, dump absent | refus | rc 2 (incohérence DATABASE_URL) ; rc 2 « dump introuvable » |
| backup `--dry-run` | rien d'écrit | conforme, rotation prévue affichée |
| backup réel | `.part`, `--list` sur stdin, 600, renommage | fichier final en 600, pas de `.part` restant, `pg_restore --list` reçoit l'archive sur l'entrée standard |
| backup, rotation (10 dumps, KEEP=7) | les plus anciens supprimés | 3 supprimés (et non 4) : la sauvegarde de la même seconde a **écrasé** la précédente (m4) ; `autre_*.dump` préservé |
| backup KEEP=3, KEEP=0, KEEP=abc | 3 conservés ; refus ; refus | conforme (rc 0, 2, 2) |
| backup, échec de pg_dump | rc 3, aucun fichier | conforme, ni `.part` ni rotation |
| backup, répertoire en 755 | remis à 700 | conforme |
| backup, dump `pg-export` (`mangatrackr_sf_..._app_*.dump`) présent, KEEP=1 | la nouvelle sauvegarde est conservée | **la sauvegarde qui vient d'être écrite est supprimée**, le dump étranger reste (M1) |
| deploy `--dry-run` | contrôles exécutés, actions affichées | conforme : config `--quiet` exécuté ; fetch, pull, tag, build, run, up et check seulement affichés ; aucun tag modifié |
| deploy, arbre sale (`??` ou ` M`) | refus avant fetch | rc 2, fichiers listés, 0 fetch, tag, build ou up |
| deploy : ref inconnue, `--rollback --migrate`, `WAIT_TIMEOUT=abc` | refus | rc 2 pour les trois |
| deploy, préflight : env en 640, postgres unhealthy, réseau absent, placeholders, compose invalide | refus | rc 2, 3, 2, 2, 2 avec un message adapté |
| deploy réel, sans migration | fetch → pull → tag prev → build → run (SELECT 1, up-to-date) → up --wait → check | ordre conforme, `prev` = ancienne image en service, `prod` et app = nouvelle, check OK (10/10) |
| deploy, migration en attente, sans `--migrate` | arrêt avant `up`, tag `prod` restauré | rc 3, 0 `up` ni `migrate`, `prod` remis sur l'image en service |
| deploy `--migrate` | backup → migrate → up | ordre vérifié dans le journal (tag, build, pg_dump, migrate, up) ; dump écrit, plus de migration en attente |
| deploy : échec du build, base injoignable, échec de la migration, échec de la sauvegarde | arrêt, tag restauré, aucun `up` | rc 3 dans les 4 cas, `prod` restauré, 0 `up` ; aucun `migrate` si la sauvegarde échoue |
| deploy, `up-to-date` en échec **sans sortie** | arrêt, tag restauré | **rc 1 silencieux, tag `prod` laissé sur l'image non déployée** (m1) |
| deploy, échec de `up --wait` | rc 4, rollback conseillé | conforme |
| deploy, échec du check.sh final (HTTP 502) | rc 4, message de retour arrière | « contrôles KO après déploiement : deploy/deploy.sh --rollback ... » |
| `--rollback` dry-run, puis réel | tag prev → prod, up, check | conforme ; dry-run sans effet ; réel : `prod` = `prev`, check OK |
| `--rollback` sans `prod-previous` | refus | rc 3 |
| `--rollback` suivi d'un check KO | rc 4 | rc 4, mais le message reconseille `--rollback` (m6) |
| Verrou : verrou tenu, puis 2 déploiements réels simultanés | le second est refusé | rc 2 « un autre déploiement est en cours », un seul `build` ; le premier finit avec rc 0 |
| flock absent | avertissement, poursuite | conforme |
| `--ref v1` (tag), puis sans `--ref`, puis `--ref dev` | détaché ; refus ; retour sur la branche | conforme (rc 0, 2, 0) |
| `--dry-run --ref v9` (tag poussé après le dernier fetch) | accepté | **refusé, rc 2 « ref inconnue »** (m5) ; accepté en réel |
| check.sh, tout OK | rc 0 | rc 0, « OK (10 contrôles) » |
| check.sh : app starting, exited ou absent, worker restarting ou absent, pg exited, PDO, SQL, migration en attente, env en 644, HTTP 500/000, secrets dans les journaux, 3 KO simultanés | rc ≠ 0, KO listés | rc 1 à chaque fois, bon libellé en KO |
| check.sh, app `running/sans-healthcheck` | OK avec avertissement | conforme |
| check.sh sans curl sur l'hôte | test depuis le conteneur | conforme |
| check.sh, secrets dans les journaux | compteurs seulement | « motifs génériques 2, valeurs du fichier de secrets 2 », aucune valeur affichée |
| check.sh `--dry-run` | 10 contrôles listés, aucun appel | conforme, aucun appel docker hors `inspect`/`info` |
| `make prod-deploy/prod-check/prod-backup` | `bash deploy/<script>.sh $(ARGS)` | conforme, 3 cibles dans `make help` |

### Balayage des 93 journaux d'appels (858 appels) et des 93 sorties
| Recherche | Résultat |
|---|---|
| `down` | 0 |
| `DROP` (journaux, sorties, entrée standard de psql) | 0 |
| `docker rm`, `rmi`, `volume`, `prune`, `network rm` | 0 |
| `docker exec ... rm` | 1, la suppression du fichier temporaire du `--restore` confirmé |
| `pg_restore` hors `--list` | 1 seul journal : `rs-ok` (avec `CONFIRM_TARGET` et base vide) |
| `compose config` sans `--quiet` | 0 sur 32 |
| `docker run/start/stop/restart/update`, `network connect`, compose `stop/rm/kill` | 0 ; conteneur postgres jamais modifié |
| `network create` | 2, uniquement au 1er passage d'install-vm |
| Marqueur de secret dans les journaux d'appels (arguments) | 0 |
| Marqueur de secret dans les sorties (dont check.sh avec secrets dans les journaux) | 0 |
| Marqueur sur l'entrée standard de psql | 1 ligne, `CREATE ROLE ... PASSWORD`, attendu |

### Points à vérifier du rapport 001
| Point | Conclusion | Méthode |
|---|---|---|
| `compose run` et `container_name` | **Pas de conflit.** `applyRunOptions` remplace `service.ContainerName` par `opts.Name` (vide sans `--name`), puis `prepareRun` génère `<projet>-app-run-<id>`. Sans `--service-ports`, les ports ne sont pas publiés (`target.Ports = []`), donc pas de conflit sur 8080 | lecture du source Compose v2.29.7 (et de `main`), non exécuté |
| `up --wait` avec un worker sans healthcheck | **« running » suffit.** Condition `running_or_healthy` ; `isServiceHealthy(fallbackRunning=true)` renvoie `Status == "running"` si `Config.Healthcheck == nil`, et une erreur si le conteneur est `exited`. Cela correspond à la doc (« Wait for services to be running\|healthy »). Limite : un worker qui plante juste après avoir démarré passe `--wait` ; check.sh le détecte (`restarting` → KO) | source v2.29.7 |
| Superutilisateur du conteneur (`PG_SUPERUSER`) | Logique correcte : `docker exec` hérite de l'environnement du conteneur, donc `${PG_SU:-${POSTGRES_USER:-postgres}}` est résolu dans le conteneur, et une valeur vide passée par `--env` est bien gérée. Non vérifiable ici : l'authentification `trust` sur le socket local (défaut de l'image officielle, sauf `--auth-local` dans `POSTGRES_INITDB_ARGS`) | relecture ; à confirmer en T6 avec `init-database.sh --dry-run`, qui exécute réellement les requêtes en lecture seule |
| Nom du projet Compose | Dérivé du nom du répertoire. Pas de `name:` dans `compose.prod.yaml`. Voir M2 | `config --format json` |
| `pg_restore --list` lu sur l'entrée standard | **Non vérifié.** Je voulais lancer `pg_restore --list` (client 18.6 présent) sur le dump de `ressources/`, en n'affichant que le code de retour. Le classifieur de permissions l'a refusé (données personnelles) et je n'ai pas contourné le refus. Aucun serveur n'était disponible ici pour produire un dump de test | à vérifier en T6 (`make prod-backup` réel) |
| `doctrine:migrations:migrate --all-or-nothing` et `up-to-date` | Options présentes dans doctrine/migrations 3.9.8. `up-to-date` renvoie 0 quand des migrations exécutées sont inconnues du code (cas d'un rollback d'image sur un schéma plus récent) : check.sh reste vert après un rollback | lecture de vendor |

### QA
| Commande | Résultat | Référence |
|---|---|---|
| `make qa-php-stan` | `[OK] No errors` | identique |
| `make test-only` | `OK (226 tests, 580 assertions)`, 0 erreur ; 2 dépréciations connues (`ux-toggle-password`) | 226 / 580, identique |

## Défauts trouvés (aucun bloquant)

### Majeurs
- **M1 — `backup.sh` `rotate` (l. 28-44, glob l. 30) : le glob `${DB_NAME}_*.dump` est trop large.** Un dump
  `pg-export` (`mangatrackr_sf_mangatrackr_app_*.dump`) ou tout autre `mangatrackr_*.dump` posé
  dans `BACKUP_DIR` est compté dans KEEP. Comme il est trié après les horodatages, il n'est jamais
  supprimé. Dès que ces fichiers étrangers atteignent KEEP, **la sauvegarde qui vient d'être
  écrite est supprimée** : vu avec KEEP=1, le fichier écrit a été effacé aussitôt. Avec
  `deploy.sh --migrate`, la migration partirait alors sans sauvegarde, et sans aucune erreur.
  C'est le même sujet que la remarque du rapport 002.
  Correction proposée :
  ```bash
  for f in "$BACKUP_DIR/${DB_NAME}"_[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]-[0-9][0-9][0-9][0-9][0-9][0-9].dump; do
    [ -e "$f" ] && [ "$f" != "${CURRENT:-}" ] && files+=("$f")
  done
  ```
  Il faut aussi exclure le fichier qui vient d'être écrit (`CURRENT=$file`) et calculer l'excédent
  sur `KEEP - 1`.
- **M2 — `compose.prod.yaml` : le nom du projet Compose est implicite.** Il vient du nom du
  répertoire, ou de `COMPOSE_PROJECT_NAME` si l'environnement le définit. Si les conteneurs
  actuels de la VM portent un autre label `com.docker.compose.project`, `up` échoue sur le conflit
  de `container_name`. Ce n'est pas destructif, mais le déploiement est bloqué. Correction
  proposée : `name: sf-mangatrackr` en tête de `compose.prod.yaml`, une fois le label confirmé sur
  la VM en T6 (`docker inspect -f '{{index .Config.Labels "com.docker.compose.project"}}'
  mangatrackr_app_prod`). Ajouter aussi au préflight de `deploy.sh` une comparaison de ce label
  avec le nom attendu, et un `die` explicite en cas d'écart.

### Mineurs
- **m1 — `deploy.sh` l. 142** :
  `printf '%s\n' "$out" | grep -v '^\s*$' | sed 's/^/    /'`. Si `up-to-date` échoue sans rien
  afficher (conteneur tué, OOM...), `grep` renvoie 1. Avec `pipefail` et `set -e`, le script
  s'arrête en rc 1, sans message et **sans `restore_tag`** : `mangatrackr:prod` reste sur l'image
  non déployée, ce qui contredit la décision 6 du rapport 001. Correction : remplacer la ligne par
  `printf '%s\n' "$out" | sed -n '/[^[:space:]]/s/^/    /p'`. Même motif, sans conséquence,
  aux lignes 93 et 100 de `check.sh`.
- **m2 — `deploy.sh` `save_previous` (l. 104-115) appelé avant le build.** Chaque tentative
  interrompue (migration en attente, build ou sauvegarde en échec) remplace `prod-previous` par
  l'image en service. La cible de retour arrière précédente est alors perdue : après un v3
  défectueux en service, une tentative v4 interrompue rend `--rollback` vers v2 impossible.
  Correction : garder l'identifiant de l'image en service dans une variable (`SERVICE_ID`).
  `restore_tag` fait alors `docker tag "$SERVICE_ID" "$IMAGE"`, et `docker tag "$SERVICE_ID"
  "$IMAGE_PREVIOUS"` n'est lancé qu'au début de `start_and_check`, juste avant `up`.
- **m3 — `init-database.sh`** : `CONFIRM_TARGET` n'est vérifié que dans `restore_dump`, après
  `ensure_role` et `ensure_database`. Une commande `--restore` refusée a donc déjà créé le rôle et
  la base. Correction : dans `main`, quand `DUMP` est défini, vérifier `CONFIRM_TARGET` (et le
  suffixe `_test`) avant `ensure_role`.
- **m4 — `backup.sh` l. 86** : deux sauvegardes dans la même seconde portent le même nom, et `mv`
  écrase la première (constaté pendant les tests). Correction : `[ ! -e "$file" ] || die "...
  existe déjà" 3`, ou ajouter `$$` au nom (en gardant le motif strict de M1 compatible).
- **m5 — `deploy.sh --dry-run --ref <ref>`** : la ref n'est cherchée qu'en local, car `fetch`
  n'est qu'affiché. Une branche ou un tag poussé depuis le dernier fetch est donc refusé à tort
  (rc 2), alors que l'exécution réelle passe. Correction : en dry-run, faire un contrôle en
  lecture seule `git ls-remote --exit-code origin "refs/heads/$ref" "refs/tags/$ref"` (ou
  accepter la ref avec un avertissement).
- **m6 — Messages** : pendant `--rollback`, l'échec de check.sh reconseille `--rollback`. En
  dry-run, la dernière ligne affiche « Déploiement terminé ». Proposition : adapter le message à
  `ROLLBACK=1`, et afficher « Fin du dry-run » en dry-run.
- **m7 — `docker/Dockerfile.prod.dockerignore`** : `.claude/` (dont `settings.local.json`),
  `.idea/`, `.devcontainer/`, `**/*.sql` et `**/*.zip` hors `ressources/` ne sont pas exclus. Ils
  entrent dans l'image par `COPY . .` si elle est construite depuis un poste de dev. Sur la VM, le
  clone propre ne les contient pas. Proposition : les ajouter (et éventuellement `tests/`,
  `docs/`, `migrations-archive-mariadb/`).
- **m8 — Rapport 001** : il annonce « 18 clés » ; le `.dist` en contient 17. C'est une
  incohérence du rapport, pas du code.
- **Info** : `deploy.sh` met à jour (`git pull`) le clone qui le contient. `backup.sh` et
  `check.sh` s'exécutent donc dans leur nouvelle version, alors que `deploy.sh` et `lib.sh`
  restent dans l'ancienne pendant ce passage. Après un arrêt « migration en attente », le clone
  est en avance sur l'image en service : c'est normal, à documenter.
- **Hygiène locale (hors scripts)** : `ressources/mangatrackr.prod.env` est en 664 sur ce poste.
  Je ne l'ai vu que par `ls -l`, sans le lire. Proposition : `chmod 600`.

## Écarts, risques, points non vérifiés
- Rien n'a tourné contre un vrai Docker, un vrai PostgreSQL ou la VM. Le faux Docker reproduit
  les sorties que j'attends : les formats réels (`docker inspect`, sortie de `up --wait`, tableau
  de `dbal:run-sql`) restent à confirmer en T6.
- Non vérifié : `pg_restore --list` sur l'entrée standard (refus de permission, voir plus haut) ;
  l'authentification `trust` locale et `POSTGRES_USER` dans le conteneur `postgres` ; le label de
  projet Compose des conteneurs actuels ; le comportement réel de `docker cp` et `chmod 600`
  (le propriétaire est root et `docker exec` tourne en root : cohérent) ; la construction réelle
  de l'image avec la `.dockerignore`. L'analyse de la `.dockerignore` est une approximation en
  Python de patternmatcher, pas BuildKit.
- Les conclusions sur `compose run` et `--wait` viennent de la lecture du code source de Compose
  v2.29.7, pas d'une exécution. La version de Compose sur la VM peut être différente.
- Pendant les tests, une erreur de mon faux Docker (code de retour de `compose` toujours à 0)
  faussait le premier essai « compose invalide ». Je l'ai corrigée, puis j'ai rejoué tous les
  scénarios `deploy.sh` et `check.sh`. Les scénarios install, init-database et backup n'appellent
  pas `compose` et ne sont pas concernés.

## Recommandation pour la suite
1. Confier au `developpeur` : M1 (motif de rotation et protection du fichier qui vient d'être
   écrit), m1, m2, m3, m4, puis m5, m6 et m7 s'ils sont retenus. M2 attend le label relevé en T6.
2. Ajouter en T6 (VM, lecture seule) :
   `docker inspect -f '{{index .Config.Labels "com.docker.compose.project"}}' mangatrackr_app_prod`,
   `docker compose version`, `deploy/init-database.sh --dry-run` (valide l'accès en
   superutilisateur) et `deploy/backup.sh` réel (valide `pg_restore --list` sur l'entrée
   standard).
3. Après les corrections, rejouer le banc : `. scratchpad/t4/harness.sh`, puis
   `sc <nom> /var/www/deploy/<script>.sh ...`. Il est réutilisable par le relecteur ; le scénario
   de M1 se rejoue avec un fichier `mangatrackr_sf_*.dump` dans `apps/backups` et `KEEP=1`.
