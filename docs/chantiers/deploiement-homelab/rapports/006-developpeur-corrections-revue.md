# 006 — developpeur — corrections des réserves de la revue

- Tâche : T5.1
- Date : 2026-10-18
- Statut : terminé

## Objectif
Corriger les 3 majeurs et les mineurs sûrs du rapport 005, rejouer le banc avec les cas « image
inchangée » et « postgres dans le même projet », puis passer `bash -n`, shellcheck,
`make qa-php-stan` et `make test-only`.

## Ce qui a été fait

### Majeurs
| # | Fichier | Correction |
|---|---|---|
| M1 | `deploy/deploy.sh` | Nouveau `compare_built_image`, appelé après `compose build`. Si `docker image inspect -f '{{.Id}}' mangatrackr:prod` est égal à `SERVICE_ID`, alors `SAME_IMAGE=1` et un avertissement est affiché. Dans ce cas, `promote_previous` ne touche pas `prod-previous` (message « image inchangée ... retour arrière inchangé »). Pas de comparaison en dry-run, puisque rien n'est construit. En-tête mis à jour |
| M2 | `deploy/deploy.sh` | `--remove-orphans` retiré du `up`. `check_compose_project` est étendu au préflight, donc aussi avant `--rollback`. Il refuse avec le code 2 : si le label `com.docker.compose.project` de `$PG_CONTAINER` est le projet de MangaTrackr ; si `docker ps -a --filter label=com.docker.compose.project=<projet>` liste un conteneur autre que app, worker ou les jetables `<projet>-*-run-*` (les noms sont affichés). Il échoue aussi si la liste est impossible à obtenir |
| M2 | `docs/deploiement/apps-dev.md` §6 | Relevé du label pour app, worker **et** `postgres`, plus la liste `docker ps -a --filter`. Conduite à tenir : arrêt, sans contournement, si `postgres` ou un conteneur étranger partage le projet |
| M3 | `.dockerignore` (racine) | Ajouts : `/ressources/`, `**/*.dump`, `**/*.sql`, `**/*.zip`, `**/*.prod.env` (`.env.local` y était déjà). Vérifié avec l'outil du testeur : aucun fichier sensible du poste n'est inclus |
| M3 | doc §6, §10 ; dette | En fin de §6 : `docker run --rm --entrypoint ls mangatrackr:prod /var/www/ressources` doit échouer. §10 corrigé (exclusion par les deux `.dockerignore`). Ligne de dette « images de dev construites avant 2026-10-18 » à supprimer |

### Mineurs retenus
| # | Correction |
|---|---|
| m1 | `check.sh` : `docker exec -u www-data` pour `bin/console`, avec un commentaire. Même chose pour la commande de lecture de la doc §9 |
| m2 | `deploy.sh` : seul le code 1 **avec** « Out-of-date! » dans la sortie compte comme migration en attente. Tout autre cas (exception de la console, qui renvoie aussi 1, configuration, code 2) déclenche `restore_tag` puis un arrêt en code 3 : ni sauvegarde ni `migrate`. Vérifié dans `UpToDateCommand` 3.9.8 (0 = à jour, 1 = en attente, 2 = migrations non enregistrées avec `--fail-on-unregistered`) |
| m3 | doc §6 : `install-vm.sh --dry-run` « affiche seulement », puis `install-vm.sh` réel (corrige 700/600, n'écrase rien) |
| m4 | dette : « Doc du PostgreSQL mutualisé absente » |
| m5 | doc §5.6, base neuve : `compose run --rm --no-deps app php bin/console app:init-datas` puis `app:create-user` en mode interactif. Le code a été lu d'abord. `InitDataService::initAllDatas` ne crée les référentiels que s'ils manquent. `saveMangaDatasInDb` **met à jour** les mangas déjà présents (titres, description...) à partir de Tenrai, sans rien supprimer et sans toucher aux comptes ni au suivi : la doc le limite à une base neuve. `CreateUserCommand` refuse un email existant. Le mode interactif évite de passer le mot de passe en argument, mais la question n'est pas masquée : c'est signalé |
| m6 | doc §6 : prérequis « branche `dev` poussée sur GitHub », et `git checkout dev` (puis `main` après la fusion) au lieu de `main` |
| m7 | doc §6 : `docker exec mangatrackr_app_prod ls -A /var/www/ressources` sur l'image actuelle ; si le répertoire existe, supprimer l'ancienne image une fois `prod-previous` remplacé. Pas de renouvellement des secrets, sauf si l'image a été copiée hors de la VM |
| m8 | ADR 0015 : rapports 001 à 006, et nouvelle puce « Nom de projet Compose fixé » (label vérifié, conteneurs étrangers refusés, pas de `--remove-orphans`) |
| m9 | dette : journaux Apache dans des fichiers du conteneur |
| m10 | dette : piste 1 mise à jour (import fait, essai des scripts sur la VM) ; doc §4 : code 2 (usage) de `check.sh` |

Doc §4, §7.7 et §9 : tableau de `deploy.sh` (préflight élargi, codes de `up-to-date`, image
inchangée, pas de `--remove-orphans`), sens précis de `prod-previous`, et 4 nouvelles lignes de
dépannage.

### Mineurs non retenus
- **Annexe d'exemple du PostgreSQL mutualisé (m4, seconde partie)** : écrire une configuration de
  référence (image, volume, healthcheck) sans connaître celle qui tourne sur la VM risquerait de
  documenter un service différent du réel. Seule la ligne de dette est ajoutée.
- **Correction des journaux Apache (m9)** : changer le vhost modifie l'image. C'est hors
  périmètre des corrections de revue, donc dette seulement.
- **Ordre T6/T7 (m6, côté pilotage)** : le push de `dev` relève du chef, avec l'accord de
  l'utilisateur. La doc le pose seulement en prérequis.
- **Nuances `pg_restore --list` et `--all-or-nothing`** : aucune correction n'était demandée.

### Banc
- `scratchpad/t4/fakebin/docker` :
  - `exec -u/--user` ;
  - sous-commande `ps` (`FAKE_PROJECT_CONTAINERS`) ;
  - label propre à `postgres` (`FAKE_PG_PROJECT_LABEL`, défaut `postgres`) ;
  - build qui ne change pas l'image (`FAKE_BUILD_SAME`) ;
  - `up-to-date` en exception (`FAKE_UPTODATE_EXC`) ou en code 2 (`FAKE_UPTODATE_RC2`).
- `scratchpad/t4/replay-t41.sh` : 19 vérifications ajoutées (sections « T5.1 »). Résultat dans
  `scratchpad/t4/replay-t51.result`.

## Résultats
| Contrôle | Résultat |
|---|---|
| `bash -n` (6 scripts) | OK |
| shellcheck 0.10.0 `-x` | 0 remarque |
| `compose config --quiet` (modèle `.dist`) | OK |
| Rejeu | **83 PASS, 0 FAIL**, deux passages identiques (64 vérifications d'avant, toujours vertes, + 19) |
| Image inchangée | après v1 → v2 (prev = v1), un redéploiement à image identique donne rc 0, le message « image inchangée », aucun tag vers `prod-previous`, et prev reste v1. `--rollback` revient bien à v1 |
| postgres dans le projet | rc 2 avec un message explicite, sans fetch, build, tag ni up ; `--rollback` également refusé ; `COMPOSE_PROJECT_NAME` égal au projet de `postgres` : refusé |
| Conteneur étranger dans le projet | rc 2, le conteneur est nommé ; un jetable `sf-mangatrackr-app-run-<id>` est toléré |
| `up` | plus de `--remove-orphans` dans les journaux d'appels |
| `up-to-date` en exception ou en code 2 avec `--migrate` | rc 3, ni `pg_dump` ni `migrate`, tag `prod` restauré |
| `check.sh` | tous les `bin/console` passent par `-u www-data` |
| `.dockerignore` racine (outil du testeur) | aucun `.sql`, `.zip`, `.dump`, `*.prod.env`, `.env.local` ni `ressources/` inclus |
| Valeurs de prod dans les fichiers modifiés (test booléen) | absentes |
| `make qa-php-stan` | `[OK] No errors` |
| `make test-only` | `OK (226 tests, 580 assertions)` |

## Écarts, risques, points non vérifiés
- Toujours rien d'exécuté contre un vrai Docker. Deux points restent supposés : l'égalité d'ID
  d'image sur un cache intégral (comportement standard de BuildKit) et le format de
  `docker ps --format '{{.Names}}'`. À voir en T6.
- Le motif `<projet>-*-run-*` suppose le nommage des conteneurs jetables de Compose v2
  (`<projet>-<service>-run-<id>`, lu dans le source en 003). Si une autre version les nomme
  autrement, `deploy.sh` les refuse pendant qu'un `compose run` tourne. Ce refus va dans le sens
  de la sûreté.
- Sans `--remove-orphans`, un service retiré un jour de `compose.prod.yaml` laisserait son
  conteneur en place : il faudrait le supprimer à la main. C'est voulu.
- `ADR 0015` (acceptée) : deux retouches, la référence des rapports et la puce « nom de projet ».
  Elles décrivent une décision déjà prise en T4.1.

## Recommandation pour la suite
- Le chef pousse `dev` (sur accord) avant T6.
- T6, dans l'ordre de la doc §6 :
  1. relever les labels de app, worker et `postgres`, et lancer `docker ps -a --filter` ;
  2. lancer `ls -A /var/www/ressources` sur l'image actuelle ;
  3. lancer les scripts en `--dry-run` ;
  4. faire une vraie sauvegarde (`backup.sh`) ;
  5. après le premier déploiement, `docker run --rm --entrypoint ls mangatrackr:prod /var/www/ressources` doit échouer.
- Pour rejouer :
  `bash /tmp/claude-0/-var-www/f578a951-56ed-48f3-8d75-06a131c21ff4/scratchpad/t4/replay-t41.sh`.
