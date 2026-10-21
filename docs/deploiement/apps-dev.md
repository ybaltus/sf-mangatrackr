# Déploiement de MangaTrackr sur la VM `apps-dev` (homelab)

- Mise à jour : 2026-10-19 (chantier `deploiement-homelab`, décision : [ADR 0015](../decisions/0015-deploiement-compose-scripte-apps-dev.md))
- Remplace, pour l'exploitation, la doc locale `ressources/HomelabDocumentationApps.md` (non versionnée)
  et la procédure d'import [`homelab.md`](../chantiers/migration-postgresql/homelab.md) (historique).
- Fichiers concernés : [`compose.prod.yaml`](../../compose.prod.yaml),
  [`docker/Dockerfile.prod`](../../docker/Dockerfile.prod) (+ `Dockerfile.prod.dockerignore`),
  [`deploy/`](../../deploy/) (scripts et modèle de secrets
  [`mangatrackr.prod.env.dist`](../../deploy/mangatrackr.prod.env.dist)).
- **Aucun secret dans ce document** : les valeurs réelles ne vivent que dans
  `/opt/apps/secrets/mangatrackr.prod.env` sur la VM.

Sommaire : [1. Fiche de référence](#1-fiche-de-référence) · [2. Architecture](#2-architecture) ·
[3. Règles impératives](#3-règles-impératives) · [4. Scripts](#4-scripts-deploy) ·
[5. Réinstallation complète](#5-réinstallation-complète-vm-vierge) ·
[6. Migration de l'installation actuelle](#6-migration-de-linstallation-actuelle-une-fois) ·
[7. Procédures courantes](#7-procédures-courantes) · [8. Contrôles fonctionnels](#8-contrôles-fonctionnels) ·
[9. Dépannage](#9-dépannage) · [10. Sécurité](#10-sécurité) · [11. Reporté](#11-reporté--reverse-proxy-https-et-google-oauth)

## 1. Fiche de référence

| Paramètre | Valeur | Variable des scripts (`deploy/lib.sh`) |
|---|---|---|
| VM | `apps-dev`, Debian 13, `192.168.1.29`, utilisateur `ybaltus` | |
| Racine | `/opt/apps` | `APPS_ROOT` |
| Projet (clone git) | `/opt/apps/projects/sf-mangatrackr` | `PROJECT_DIR` |
| Dépôt | `https://github.com/ybaltus/sf-mangatrackr` | `REPO_URL` (`REPO_BRANCH` au clonage) |
| Branche déployée | `main` (stable, PR fusionnées depuis `dev`) ; basculer avec `deploy/deploy.sh --ref main` (section 7.1) | `--ref` |
| Compose de production | `compose.prod.yaml` (toujours `-f` explicite) | `COMPOSE_FILE` |
| Projet Compose | `sf-mangatrackr` (`name:` de `compose.prod.yaml`, label `com.docker.compose.project`) | `COMPOSE_PROJECT_NAME` (priorité sur `name:`) |
| Secrets (600, répertoire 700) | `/opt/apps/secrets/mangatrackr.prod.env` | `ENV_FILE` (`SECRETS_DIR`) |
| Sauvegardes (700, fichiers 600) | `/opt/apps/backups/backup_mangatrackr_AAAAMMJJ-HHMMSS.dump` | `BACKUP_DIR`, `KEEP` (7) |
| Réseaux Docker externes | `database` (PostgreSQL), `proxy` (réseau prévu pour un reverse proxy local ; le proxy actuel est la VM `proxy-caddy`) | `NETWORKS` |
| PostgreSQL 18 mutualisé | conteneur `postgres`, port 5432 non publié | `PG_CONTAINER`, `PG_SUPERUSER` |
| Base / rôle applicatif | `mangatrackr` / `mangatrackr` | `DB_NAME`, `DB_ROLE` |
| Image | `mangatrackr:prod` (retour arrière : `mangatrackr:prod-previous`) | `IMAGE`, `IMAGE_PREVIOUS` |
| Conteneurs | `mangatrackr_app_prod` (Apache), `mangatrackr_worker_prod` (Messenger `async`) | `APP_CONTAINER`, `WORKER_CONTAINER` |
| Accès actuel | `http://192.168.1.29:8080/` (LAN, HTTP) | `MANGATRACKR_BIND`, `APP_URL` |
| Reverse proxy HTTPS, Google OAuth | section 11 (ADR 0016) | |

Toutes les variables sont surchargeables à l'appel, par exemple `APPS_ROOT=/srv/apps deploy/check.sh`.

## 2. Architecture

```text
LAN ── 192.168.1.29:8080 ──> mangatrackr_app_prod (Apache + Symfony, réseaux database + proxy)
                                     │
        mangatrackr_worker_prod ─────┤  réseau Docker externe « database »
        (messenger:consume async)    │
                                     └──> postgres (PostgreSQL 18 mutualisé) ── base mangatrackr
Internet ──> VM proxy-caddy (192.168.1.14, Caddy, HTTPS) ──> 192.168.1.29:8080 (ADR 0016)
```

- Autres machines du homelab : hôte Proxmox `192.168.1.25`, OpenMediaVault `192.168.1.26`,
  Jellyfin (LXC) `192.168.1.27`.
- `app` et `worker` utilisent la même image `mangatrackr:prod`, **construite sur la VM** à partir
  de `docker/Dockerfile.prod` (dépendances Composer sans dev, assets compilés pendant la
  construction). L'image ne lance ni migration, ni fixtures, ni création d'utilisateur.
- Le worker consomme le transport `async` (limites : 3 600 s et 128 Mo par exécution, puis
  redémarrage par `restart: unless-stopped`). Il n'a pas de healthcheck ; `app` en a un (HTTP local).
- `compose.prod.yaml` ne définit **pas** PostgreSQL : le conteneur `postgres` est partagé entre les
  applications de la VM et administré à part (section 5.3). Chaque application a sa base et son rôle ;
  aucune n'utilise le superutilisateur.
- Arborescence :

```text
/opt/apps/
├── postgres/      service PostgreSQL mutualisé (hors de ce dépôt, documentation séparée)
├── proxy/         (réservé ; le reverse proxy est sur la VM proxy-caddy)
├── projects/sf-mangatrackr/   clone git : compose.prod.yaml, docker/Dockerfile.prod, deploy/...
├── secrets/       700 : mangatrackr.prod.env (600)
└── backups/       700 : backup_mangatrackr_*.dump (600), rotation par deploy/backup.sh
```

## 3. Règles impératives

- **Jamais `docker compose down`** sur la production (les scripts le refusent). Pour arrêter :
  `stop`, pour appliquer : `up -d`.
- **Toujours `-f compose.prod.yaml`**, depuis `/opt/apps/projects/sf-mangatrackr`. Le
  `docker-compose.yml` du dépôt est celui du **développement** (il définit sa propre base).
- **Jamais `docker/docker.sh` ni `docker/Dockerfile` en production** : ils sont réservés au dev et
  à la démo (`make first-install`, `app:init-datas`, utilisateur `demo@demo.fr`), et
  modifieraient la base de production.
- **Jamais `docker compose ... config` sans `--quiet`** : sans cette option, Compose affiche les
  valeurs du fichier de secrets.
- **Jamais `bash -x` ni `set -x`** sur les scripts `deploy/` (ils manipulent des secrets ; ils
  refusent de tourner dans ce mode).
- **Ne pas toucher au conteneur `postgres`** (ni recréer, ni arrêter, ni mettre à jour) lors d'un
  déploiement applicatif. Toute opération sur PostgreSQL : sauvegarde vérifiée d'abord, et
  impact sur les autres applications évalué.
- **Migrations** : seulement par `deploy/deploy.sh --migrate`, qui fait une sauvegarde `pg_dump`
  juste avant.
- **Secrets** : jamais dans git, un ticket, un journal partagé ou un terminal partagé ; ne jamais
  afficher `DATABASE_URL` en entier.

## 4. Scripts `deploy/`

Tous ont `--help` (en-tête du script) et `--dry-run` (contrôles en lecture seule exécutés, actions
seulement affichées), sont idempotents et ne font jamais `down`.

| Script | Rôle | Codes de sortie |
|---|---|---|
| `install-vm.sh` | Vérifie Debian / Docker / Compose / git (n'installe rien) ; crée ce qui manque : `/opt/apps/{projects,secrets,backups}`, réseaux `database` et `proxy`, clone, fichier de secrets copié du `.dist` **s'il est absent** ; installe (avec `sudo`) `wait-ip.sh` et le drop-in `docker` (section 9.1), sans redémarrer Docker | 0 ok, 2 prérequis, 3 conflit |
| `wait-ip.sh [adresse] [délai] [interface]` | Copié dans `/usr/local/sbin` par `install-vm.sh` ; appelé par systemd avant Docker : attend que l'IPv4 de publication soit posée, sort toujours en 0 | 0 |
| `init-database.sh [--restore <dump>]` | Crée le rôle et la base `mangatrackr` (ICU `fr`, `template0`) **s'ils manquent** ; `--restore` : restaure un dump dans une base **vide** (exige `CONFIRM_TARGET=postgres/mangatrackr`, vérifié **avant** toute création : un `--restore` refusé ne crée rien) | 0, 2 config, 3 PostgreSQL, 5 cible refusée |
| `backup.sh` | `pg_dump -Fc` vers `/opt/apps/backups/backup_mangatrackr_<date>.dump` (contrôle `pg_restore --list`, 600, jamais d'écrasement), rotation `KEEP=7` limitée à ses propres fichiers : la sauvegarde qui vient d'être écrite n'est jamais supprimée, les autres dumps du répertoire ne sont ni comptés ni supprimés | 0, 2 usage, 3 échec du dump ou nom déjà pris |
| `deploy.sh [--ref <ref>] [--migrate]` | Préflight (projet Compose des conteneurs existants ; refus si `postgres` ou un autre conteneur que app, worker et les jetables `*-run-*` porte le label du projet), `git fetch` + checkout (arbre propre exigé), image en service **épinglée par nom** (`mangatrackr:prod-service`) avant `compose build`, contrôle des migrations (seul « Out-of-date! » avec le code 1 compte comme migration en attente, tout autre échec arrête le script), image épinglée retaguée `prod-previous` (par nom) **juste avant** `up -d --wait` (sauf si l'image construite est identique : retour arrière conservé ; si l'image en service est déjà introuvable : avertissement, aucun retour arrière pour ce cycle, sans échec), `check.sh`. Pas de `--remove-orphans`. En `--dry-run`, une ref absente en local est cherchée sur `origin` (`git ls-remote`) | 0, 2 préflight, 3 build/base/migration, 4 contrôles KO |
| `deploy.sh --rollback` | Remet `prod-previous` en `prod`, `up -d`, `check.sh` (ni schéma ni code git ramenés) | idem |
| `check.sh` | Lecture seule : conteneurs et santé, secrets en 600, pilote `pgsql`, `SELECT current_database(), current_user`, migrations à jour, HTTP 200/301/302, aucun secret dans les 100 dernières lignes de journaux ; `bin/console` lancé en `www-data` | 0 tout OK, 1 au moins un KO, 2 usage |

Raccourcis Makefile (si `make` est installé sur la VM) : `make prod-deploy ARGS="--dry-run"`,
`make prod-check`, `make prod-backup`. Sinon, appeler les scripts directement (`deploy/<script>.sh`).

## 5. Réinstallation complète (VM vierge)

Ordre : VM → Docker → `install-vm.sh` → secrets → PostgreSQL mutualisé → `init-database.sh
--restore` → `deploy.sh` → `check.sh` → contrôles fonctionnels.

### 5.1. VM Debian 13
Dans Proxmox : VM Debian 13 (installation minimale + serveur SSH), nom `apps-dev`, adresse fixe
`192.168.1.29`, utilisateur d'administration `ybaltus` avec `sudo`. Puis :

```bash
sudo apt-get update && sudo apt-get upgrade -y
sudo apt-get install -y git curl make openssl
```

Interface réseau de la VM : `net0` avec `firewall=1` (VMID actuel `200`, MAC `BC:24:11:93:CA:6E` : la
réservation DHCP de la Livebox pour `192.168.1.29` y est liée, donc la reprendre à la recréation).

### 5.1 bis. Pare-feu Proxmox de la VM (ADR 0016)
Pare-feu **Proxmox** (pas de `ufw` dans la VM : Docker le contournerait), politique entrante `DROP` :
le 8080 n'est ouvert qu'au reverse proxy `192.168.1.14` et à l'IP du poste d'administration
(secours), le SSH au LAN, le ping au LAN ; pas de 80/443, PostgreSQL jamais publié.
**La source de vérité est le dépôt `proxy-setup`** (la VM `proxy-caddy` et tout ce qui en découle) :
`proxmox/apps-dev-firewall.sh` et `APPS-DEV.md` §7 (règles, commande de recréation, activation,
contrôles, retour arrière). Ne pas dupliquer les règles ici.

### 5.2. Docker
Docker Engine et le plugin Compose depuis le dépôt officiel Docker pour Debian (Docker Desktop
inutile ; versions relevées en 2026-10 : Engine 29.8.1, Compose v5.5.1) :

```bash
sudo apt-get install -y ca-certificates curl
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/debian/gpg -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc
sudo tee /etc/apt/sources.list.d/docker.sources >/dev/null <<EOF
Types: deb
URIs: https://download.docker.com/linux/debian
Suites: $(. /etc/os-release && echo "$VERSION_CODENAME")
Components: stable
Signed-By: /etc/apt/keyrings/docker.asc
EOF
sudo apt-get update
sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
sudo usermod -aG docker "$USER"     # puis se déconnecter / reconnecter
docker --version && docker compose version && docker info >/dev/null && echo OK
```

Le groupe `docker` donne l'équivalent des droits root : le réserver à l'administrateur.

### 5.2 bis. Démarrage fiable après reboot
Sans ce réglage, la VM neuve reproduit la panne du 2026-10-20 (section 9.1) : Docker relance `app`
avant que l'IPv4 DHCP soit posée et le bind de `192.168.1.29:8080` échoue. Deux parties :

1. **À la main, avant le premier redémarrage** : l'installateur Debian met en général
   `allow-hotplug ens18`, que `network-online.target` n'attend pas. Passer en `auto` (le changement
   ne s'applique qu'au démarrage suivant ; ne pas relancer `networking` en SSH) :
   ```bash
   sudo cp /etc/network/interfaces /etc/network/interfaces.bak-$(date +%F)
   sudo sed -i 's/^allow-hotplug ens18/auto ens18/' /etc/network/interfaces
   grep -n ens18 /etc/network/interfaces
   ```
2. **Par `deploy/install-vm.sh`** (étape 5.4) : installe `deploy/wait-ip.sh` dans
   `/usr/local/sbin` et le drop-in `docker.service.d/wait-ip.conf`. `install-vm.sh` signale
   (avertissement) un `allow-hotplug` encore présent.

### 5.3. Prérequis : PostgreSQL 18 mutualisé
Le conteneur `postgres` n'est **ni créé ni géré par les scripts** de ce dépôt ; il relève de la
documentation séparée du service mutualisé (`/opt/apps/postgres/`, hors dépôt). Avant
`init-database.sh`, il doit :

- s'appeler `postgres`, tourner en PostgreSQL **18** (image officielle : ICU et extension
  `unaccent` disponibles) et de préférence avoir un healthcheck ;
- être rattaché au réseau Docker `database`, sans port publié sur la VM ;
- accepter la connexion locale (socket) de son superutilisateur sans mot de passe (défaut de
  l'image officielle) ; sinon, indiquer le superutilisateur avec `PG_SUPERUSER=<nom>`.

Les réseaux `database` et `proxy` sont créés par `install-vm.sh` s'ils manquent : si le service
PostgreSQL est installé avant, il doit se rattacher au réseau `database` existant.

```bash
docker inspect --format '{{.State.Status}} / {{if .State.Health}}{{.State.Health.Status}}{{else}}sans healthcheck{{end}}' postgres
docker inspect --format '{{range $k, $v := .NetworkSettings.Networks}}{{$k}} {{end}}' postgres
```

### 5.4. Clone et `install-vm.sh`
Le script est dans le dépôt : cloner d'abord (branche `main`), puis le lancer.

```bash
sudo install -d -o "$USER" -g "$USER" /opt/apps
mkdir -p /opt/apps/projects
git clone https://github.com/ybaltus/sf-mangatrackr /opt/apps/projects/sf-mangatrackr
cd /opt/apps/projects/sf-mangatrackr
deploy/install-vm.sh --dry-run
deploy/install-vm.sh
deploy/install-vm.sh            # deuxième passage : uniquement « déjà en place »
```

L'étape d'attente de l'IP (5.2 bis) demande `sudo` (mot de passe) ; elle n'a d'effet qu'au prochain
démarrage de la VM. Après le premier redémarrage : contrôle de la section 9.1.

### 5.5. Fichier de secrets
`install-vm.sh` a copié le modèle en `/opt/apps/secrets/mangatrackr.prod.env` (600). Remplacer
**chaque** valeur `<...>` dans un éditeur, sur un terminal non partagé :

```bash
nano /opt/apps/secrets/mangatrackr.prod.env
```

- `APP_SECRET` : valeur propre à la production, générée par `openssl rand -hex 32`.
- `DATABASE_URL` : `postgresql://mangatrackr:<mot-de-passe>@postgres:5432/mangatrackr?serverVersion=18&charset=utf8`.
  En réinstallation, reprendre le mot de passe du rôle existant si la base est conservée ;
  sinon en choisir un nouveau, de préférence hexadécimal (`openssl rand -hex 24`) : rien à encoder.
  Sinon : percent-encoder les caractères spéciaux (`@` → `%40`), **puis doubler chaque `%`**
  (`%%40`), car la valeur passe par `%env(resolve:)%` de Symfony.
- Une valeur contenant un `$` s'entoure d'apostrophes simples (interpolation de Compose).
- `MAILER_DSN=null://null` tant qu'aucun relais SMTP n'est prévu ; OAuth : section 11.

Contrôles (aucune valeur affichée) :

```bash
stat -c '%a %U:%G %n' /opt/apps/secrets /opt/apps/secrets/mangatrackr.prod.env   # 700 puis 600
grep -cE '^[A-Z0-9_]+=.*<[^>]+>' /opt/apps/secrets/mangatrackr.prod.env          # doit afficher 0
diff <(grep -oE '^[A-Z0-9_]+=' deploy/mangatrackr.prod.env.dist | sort) \
     <(grep -oE '^[A-Z0-9_]+=' /opt/apps/secrets/mangatrackr.prod.env | sort) && echo "mêmes clés"
```

Garder une copie de ce fichier dans un coffre chiffré hors de la VM (section 10).

### 5.6. Base : `init-database.sh` puis `--restore`
Créer le rôle et la base (seulement s'ils manquent ; un rôle existant n'est pas modifié) :

```bash
deploy/init-database.sh --dry-run
deploy/init-database.sh
```

Restaurer les données à partir d'un dump `pg_dump -Fc` : `make pg-export` sur la machine de dev
(`ressources/mangatrackr_<base>_<date>.dump`) ou une sauvegarde `backup.sh` (copiée hors de
l'ancienne VM). Transfert par SSH, de préférence dans un sous-répertoire `import/` pour ne pas
mélanger les dumps importés et les sauvegardes. La rotation de `backup.sh` ne porte que sur ses
propres fichiers (`backup_mangatrackr_AAAAMMJJ-HHMMSS.dump`) : un dump posé à côté n'est ni compté
ni supprimé.

```bash
# Sur la VM
mkdir -p -m 700 /opt/apps/backups/import
# Sur la machine qui détient le dump
scp ressources/mangatrackr_<base>_<date>.dump ybaltus@192.168.1.29:/opt/apps/backups/import/
# Sur la VM
chmod 600 /opt/apps/backups/import/*.dump
cd /opt/apps/projects/sf-mangatrackr
CONFIRM_TARGET=postgres/mangatrackr deploy/init-database.sh --restore /opt/apps/backups/import/<fichier>.dump --dry-run
CONFIRM_TARGET=postgres/mangatrackr deploy/init-database.sh --restore /opt/apps/backups/import/<fichier>.dump
```

- Restauration en une seule transaction ; refusée si la base contient déjà des tables (aucun
  remplacement automatique : voir 7.5 pour remplacer une base).
- Ne **pas** lancer de migration avant : la baseline est dans le dump.
- Le dump contient des données personnelles : le supprimer des machines intermédiaires après
  usage (`rm`), ne jamais le commiter ni l'afficher.
- Installation sans données (base neuve) : sauter `--restore`, puis le premier `deploy.sh` doit
  être lancé avec `--migrate` (la baseline crée le schéma). Ensuite, **base neuve uniquement** :

  ```bash
  cd /opt/apps/projects/sf-mangatrackr
  # Référentiels (types, statuts, fantrads, statuts de suivi) créés s'ils manquent, puis environ
  # 50 mangas récupérés par l'API Tenrai (accès Internet requis ; échec si l'API ne répond pas).
  docker compose -f compose.prod.yaml run --rm --no-deps app php bin/console app:init-datas --env=prod --no-debug
  # Compte administrateur : mode interactif (sans arguments, pour que le mot de passe ne figure ni
  # dans l'historique du shell ni dans `ps`). Répondre « yes » à la question administrateur.
  docker compose -f compose.prod.yaml run --rm --no-deps app php bin/console app:create-user --env=prod --no-debug
  ```

  `app:init-datas` ne supprime rien et ne touche ni aux comptes ni au suivi de lecture, mais sur
  une base existante il **met à jour** les fiches de mangas déjà présentes avec les données de
  Tenrai : ne pas le relancer sur la production restaurée. `app:create-user` refuse un email déjà
  utilisé. Le mot de passe saisi s'affiche en clair à l'écran (question non masquée) : le faire sur
  un terminal non partagé. Ne jamais utiliser `docker/docker.sh`, les fixtures ni `demo@demo.fr`.

### 5.7. Premier déploiement et contrôle

```bash
cd /opt/apps/projects/sf-mangatrackr
deploy/deploy.sh --dry-run
deploy/deploy.sh                 # « aucune migration en attente » attendu après une restauration
deploy/check.sh                  # « Résumé : OK »
```

Le premier déploiement n'a pas d'image `prod-previous` : pas de retour arrière possible (le script
le signale). Terminer par les contrôles fonctionnels (section 8).

## 6. Migration de l'installation actuelle (une fois)

Prérequis : le code à déployer est **sur GitHub** et la VM le récupère par `git fetch`. La branche
déployée est `main` (la PR du chantier est fusionnée). Cette migration a été faite une fois, le
2026-10-19, depuis `dev` alors que la PR n'était pas encore fusionnée ; à refaire, utiliser `main`.

Sur la VM actuelle, `compose.prod.yaml`, `docker/Dockerfile.prod` et
`docker/Dockerfile.prod.dockerignore` ont été posés à la main, hors git. `git pull` refuserait de
les écraser et `deploy.sh` refuse un arbre non propre. Les conteneurs en service ne sont pas
touchés par ces étapes.

```bash
cd /opt/apps/projects/sf-mangatrackr
git status --porcelain                       # repérer tous les fichiers modifiés ou non suivis
SAUV=/opt/apps/backups/fichiers-manuels-$(date +%Y%m%d)
mkdir -p -m 700 "$SAUV"
mv compose.prod.yaml docker/Dockerfile.prod docker/Dockerfile.prod.dockerignore "$SAUV"/
git status --porcelain                       # doit être vide (traiter de même tout autre fichier)
git fetch origin && git checkout main && git pull --ff-only
diff -u "$SAUV/compose.prod.yaml" compose.prod.yaml
diff -u "$SAUV/Dockerfile.prod" docker/Dockerfile.prod
diff -u "$SAUV/Dockerfile.prod.dockerignore" docker/Dockerfile.prod.dockerignore
```

Écarts attendus dans les `diff` : en-têtes de commentaires ; `name: sf-mangatrackr` ; port et
chemin des secrets paramétrables (mêmes valeurs par défaut) ; healthcheck HTTP de `app` ;
`pull_policy: never` du worker ; exclusions du contexte de construction (`/ressources/`, `*.dump`,
`*.sql`, `*.zip`, `*.prod.env`, `.claude/`, `.idea/`, `.devcontainer/`, `.github/`, `docs/`,
`tests/`, `migrations-archive-mariadb/`). Tout autre écart (réglage fait sur la VM) est à reporter
dans le dépôt avant de continuer.

Relever ensuite le projet Compose des conteneurs actuels **et du PostgreSQL mutualisé** (lecture
seule), puis la liste des conteneurs de ce projet :

```bash
docker inspect -f '{{index .Config.Labels "com.docker.compose.project"}}' mangatrackr_app_prod
docker inspect -f '{{index .Config.Labels "com.docker.compose.project"}}' mangatrackr_worker_prod
docker inspect -f '{{index .Config.Labels "com.docker.compose.project"}}' postgres
docker ps -a --filter label=com.docker.compose.project=sf-mangatrackr --format '{{.Names}}'
```

- `sf-mangatrackr` (attendu : projet lancé depuis `/opt/apps/projects/sf-mangatrackr`) : rien à
  faire, c'est la valeur de `name:` dans `compose.prod.yaml`.
- Autre valeur : `up` entrerait en conflit sur `container_name`, et `deploy.sh` s'arrête en
  préflight (code 2, « appartient au projet Compose ... »). Préfixer alors **toutes** les commandes,
  scripts comme `docker compose` manuels, par `COMPOSE_PROJECT_NAME=<valeur relevée>` (il a
  priorité sur `name:`). Signaler la valeur pour qu'elle soit reportée dans le dépôt.
- `postgres` porte le **même** label que MangaTrackr, ou la liste contient un autre conteneur que
  `mangatrackr_app_prod`, `mangatrackr_worker_prod` (et d'éventuels jetables `*-run-*`) :
  **arrêt**. `deploy.sh` refuse ce cas (code 2, « appartient au projet Compose » ou « conteneur(s)
  étranger(s) »), car Compose traiterait ces conteneurs comme les siens. Ne pas contourner : la
  séparation des projets se décide avec la doc du PostgreSQL mutualisé (dette technique).

Vérifier aussi si l'image **actuellement en service** (construite avant l'exclusion de
`ressources/`) embarque ce répertoire :

```bash
docker exec mangatrackr_app_prod ls -A /var/www/ressources   # « No such file or directory » attendu
```

S'il existe, l'ancienne image contient peut-être des secrets ou un dump. Après le premier
déploiement scripté, c'est elle qui devient `prod-previous` (seul retour arrière possible) : ne la
supprimer qu'après le **deuxième** déploiement scripté réussi, quand `prod-previous` désigne une
image saine (`docker image ls`, puis `docker image rm <id>` de l'ancienne image). Elle n'est que locale à
la VM, où ces secrets sont déjà présents : renouvellement nécessaire seulement si l'image a été
copiée ailleurs (`docker save`, registre).

Sur la VM (magasin containerd), la première tentative a déplacé `mangatrackr:prod` sans pouvoir
retaguer l'ancienne image, qui n'est plus retrouvable : le prochain `deploy.sh` annonce « aucun retour
arrière possible pour ce cycle » et déploie quand même ; `prod-previous` n'existera qu'après le
déploiement suivant.

```bash
deploy/install-vm.sh --dry-run   # affiche seulement ce qui serait corrigé (rien n'est créé)
deploy/install-vm.sh             # corrige si besoin : crée /opt/apps/backups, droits 700/600 ; n'écrase rien
deploy/check.sh                  # « sans healthcheck » attendu : conteneurs créés par l'ancien fichier
deploy/deploy.sh --dry-run
deploy/backup.sh                 # sauvegarde avant le premier déploiement scripté
deploy/deploy.sh                 # recrée app et worker avec le fichier versionné
deploy/check.sh
# L'image construite par le dépôt ne doit pas contenir ressources/ (la commande doit échouer) :
docker run --rm --entrypoint ls mangatrackr:prod /var/www/ressources
```

## 7. Procédures courantes

Toujours depuis `/opt/apps/projects/sf-mangatrackr`.

### 7.1. Redéploiement (nouvelle version du code)

```bash
deploy/deploy.sh --dry-run
deploy/deploy.sh                       # branche courante ; ou --ref <branche|tag|commit>
```

La VM déploie `main`. Si le clone est sur une autre branche (par exemple `dev` après la migration
de la section 6), basculer **par le script**, jamais par un `git checkout` à la main :

```bash
deploy/deploy.sh --ref main --dry-run
deploy/deploy.sh --ref main            # fetch, checkout main, pull --ff-only, puis déploiement
```

Ensuite `deploy/deploy.sh` suit la branche courante (`main`). Ne déployer `dev` ou un tag que par
un `--ref` volontaire ; un tag est un checkout détaché : repasser par `--ref main` après.

Si une migration est en attente, le script s'arrête **avant** de redémarrer quoi que ce soit (la
version en service continue de tourner) : passer à 7.3.

### 7.2. Changement de variables (fichier de secrets)
Les variables sont lues à la création des conteneurs : les recréer sans reconstruire.

```bash
nano /opt/apps/secrets/mangatrackr.prod.env
docker compose -f compose.prod.yaml up -d --no-deps --no-build app      # variable utilisée par l'application
docker compose -f compose.prod.yaml up -d --no-deps --no-build worker   # si le worker est concerné
deploy/check.sh
```

Une modification du code ou du Dockerfile passe par 7.1.

### 7.3. Migration de schéma

```bash
deploy/deploy.sh --migrate --dry-run
deploy/deploy.sh --migrate
```

Ordre : construction de l'image, `backup.sh` (sauvegarde horodatée dans `/opt/apps/backups/`),
`doctrine:migrations:migrate --all-or-nothing` dans un conteneur jetable (une transaction : en cas
d'échec, schéma inchangé et version en service conservée), puis `up -d`. Entre la migration et le
redémarrage, l'ancien code tourne quelques secondes sur le nouveau schéma : acceptable pour une
migration additive ; sinon, arrêter d'abord les services (`docker compose -f compose.prod.yaml stop app worker`).

### 7.4. Sauvegarde

```bash
deploy/backup.sh                       # ou make prod-backup ; KEEP=14 deploy/backup.sh pour en garder 14
ls -l /opt/apps/backups/backup_mangatrackr_*.dump
```

Le nom est horodaté à la seconde ; deux sauvegardes dans la même seconde ne s'écrasent pas (le
script attend la seconde suivante, puis refuse avec le code 3). Seuls les fichiers
`backup_mangatrackr_*.dump` entrent dans la rotation : une copie manuelle à conserver doit porter un
autre nom (par exemple `avant-migration-<date>.dump`).

Les sauvegardes restent sur la VM : les copier régulièrement hors de la VM par un canal chiffré
(par exemple `scp` vers le NAS dans un répertoire à accès restreint). Elles contiennent des
données personnelles.

### 7.5. Restauration
`init-database.sh --restore` ne restaure que dans une base vide. Pour remplacer la base de
production (par exemple après une migration ratée), mettre l'ancienne de côté sans la détruire :

```bash
deploy/backup.sh                                            # état actuel, au cas où
docker compose -f compose.prod.yaml stop app worker         # plus aucune connexion (pas « down »)
docker exec -i postgres sh -c 'psql -X -v ON_ERROR_STOP=1 -U "${POSTGRES_USER:-postgres}" -d postgres' <<'SQL'
ALTER DATABASE mangatrackr RENAME TO mangatrackr_avant_restauration;
SQL
deploy/init-database.sh                                     # recrée une base vide (le rôle existe déjà)
CONFIRM_TARGET=postgres/mangatrackr deploy/init-database.sh --restore /opt/apps/backups/<fichier>.dump
docker compose -f compose.prod.yaml up -d --no-build        # ou deploy/deploy.sh --rollback (7.6)
deploy/check.sh
```

Après validation (section 8), supprimer l'ancienne base, en vérifiant le nom deux fois :
`DROP DATABASE mangatrackr_avant_restauration;` (même commande `docker exec ... psql`).
`ALTER DATABASE ... RENAME` échoue tant qu'une session est ouverte sur la base : vérifier que `app`
et `worker` sont bien arrêtés.

### 7.6. Test de restauration (à planifier, au moins après chaque changement de procédure)
Une sauvegarde n'est fiable qu'une fois restaurée. Contrôle rapide de l'archive :

```bash
docker exec -i postgres pg_restore --list < /opt/apps/backups/<fichier>.dump | head -n 5
```

Test complet dans une base jetable du serveur mutualisé (jamais la base de production), puis
suppression :

```bash
F=/opt/apps/backups/<fichier>.dump
SU='psql -X -v ON_ERROR_STOP=1 -U "${POSTGRES_USER:-postgres}"'
docker exec -i postgres sh -c "$SU -d postgres" <<'SQL'
CREATE DATABASE mangatrackr_test_restauration OWNER mangatrackr TEMPLATE template0
  ENCODING 'UTF8' LOCALE_PROVIDER icu ICU_LOCALE 'fr';
SQL
docker cp "$F" postgres:/tmp/test-restauration.dump
docker exec postgres sh -c 'pg_restore -U "${POSTGRES_USER:-postgres}" --dbname=mangatrackr_test_restauration --role=mangatrackr --no-owner --no-privileges --exit-on-error --single-transaction /tmp/test-restauration.dump'
docker exec -i postgres sh -c "$SU -At -d mangatrackr_test_restauration" <<'SQL'
SELECT count(*) FROM pg_tables WHERE schemaname = 'public';
SQL
docker exec postgres rm -f /tmp/test-restauration.dump
docker exec -i postgres sh -c "$SU -d postgres" <<'SQL'
DROP DATABASE mangatrackr_test_restauration;
SQL
```

Le nombre de tables doit être celui de la production
(`SELECT count(*) FROM pg_tables WHERE schemaname = 'public';` sur `mangatrackr`).

### 7.7. Retour arrière

```bash
deploy/deploy.sh --rollback --dry-run
deploy/deploy.sh --rollback
```

- Remet l'image précédente (`mangatrackr:prod-previous`, celle qui était en service avant le
  dernier `deploy.sh` **arrivé jusqu'au `up` avec une image différente** ; un redéploiement qui
  redonne la même image, message « image inchangée », ne la modifie pas) et relance `up -d` puis `check.sh`. Un seul niveau
  de retour. Une tentative interrompue avant `up` (migration en attente, échec du build, de la
  sauvegarde ou de la migration) ne modifie pas `prod-previous`.
- Magasin d'images containerd (Docker 29, `io.containerd.snapshotter.v1`) : dès que le dernier
  tag d'une image est déplacé, elle disparaît de `docker image ls -a` et son ID n'est plus
  utilisable (`No such image: sha256:...`), même si des conteneurs tournent dessus. `deploy.sh`
  épingle donc l'image en service sous le tag `mangatrackr:prod-service` **avant** `compose build`
  et ne retague jamais par ID. Si, au lancement, l'image du conteneur est déjà introuvable (tag
  `prod` déjà déplacé par un build ou un déploiement interrompu), le script le dit
  (« aucun retour arrière possible pour ce cycle »), ne touche pas `prod-previous` et déploie quand
  même : le cycle suivant retrouvera un retour arrière. Le tag `prod-service` reste en place après le
  déploiement (il épingle l'image du déploiement précédent, jusqu'au suivant).
- Si `check.sh` échoue après un retour arrière, le script ne reconseille pas `--rollback` :
  passer au dépannage (section 9) ou à la restauration (7.5).
- **Limite : le schéma n'est pas ramené en arrière.** Si le déploiement fautif a migré la base et
  que l'ancien code ne fonctionne pas sur le nouveau schéma, restaurer la sauvegarde prise par
  `--migrate` (7.5), puis `--rollback`.
- Le code git reste sur la nouvelle version : au prochain déploiement, préciser
  `--ref <commit ou tag sain>` tant que le correctif n'est pas publié.

## 8. Contrôles fonctionnels
Après une installation, un déploiement ou une restauration (`check.sh` couvre les points 1, 4, 5
et 9 en partie ; un code HTTP 200 ne prouve pas que tout fonctionne) :

1. Conteneurs `app` (sain) et `worker` en marche.
2. Apache démarré sans erreur (`docker compose -f compose.prod.yaml logs --tail=100 app`).
3. Worker qui consomme `async` (`... logs --tail=100 worker`).
4. PostgreSQL répond en lecture (base `mangatrackr`, rôle `mangatrackr`).
5. L'application répond en HTTP (`http://192.168.1.29:8080/`).
6. Pages principales avec leurs styles et images (catalogue, recherche, calendrier).
7. Lecture et écriture attendues : connexion par formulaire, scanthèque (modifier un chapitre),
   export CSV, administration EasyAdmin.
8. Authentification testée séparément (Google OAuth : à valider sur `https://mangasync.labytech.fr`,
   section 11).
9. Aucun secret dans les journaux ni dans git.

## 9. Dépannage

| Symptôme | Vérification |
|---|---|
| L'application ne répond plus | `deploy/check.sh` ; `docker compose -f compose.prod.yaml ps` ; `... logs --tail=100 app` |
| `deploy.sh` : « arbre git non propre » | `git status --porcelain` ; déplacer ou remiser les fichiers (cas de la section 6) |
| `deploy.sh` : « migration(s) en attente » | Normal : relancer avec `--migrate` (7.3) ; rien n'a été redémarré. Le clone git est alors en avance sur l'image en service : c'est attendu |
| `deploy.sh` : « appartient au projet Compose ... » (code 2) | Conteneurs créés sous un autre nom de projet : relancer avec `COMPOSE_PROJECT_NAME=<nom indiqué>` (section 6). Si c'est `postgres` qui est cité : ne pas contourner (section 6) |
| `deploy.sh` : « conteneur(s) étranger(s) dans le projet Compose » (code 2) | Un autre conteneur porte le label du projet : identifier son origine (`docker inspect`) ; ne pas contourner |
| `deploy.sh` : « image inchangée » | Normal : la construction a redonné l'image en service (aucun changement du contexte) ; `prod-previous` est conservé |
| `deploy.sh` : « aucun retour arrière possible pour ce cycle » | L'image du conteneur en service n'existe plus (magasin containerd, tag déplacé). Le déploiement continue ; `prod-previous` n'est pas modifié. Après ce déploiement, le retour arrière redevient possible (voir 7.7) |
| `deploy.sh` : « up-to-date en erreur » (code 3) | La console a échoué (exception, configuration) : lire la sortie affichée ; rien n'a été migré ni redémarré |
| `deploy.sh --dry-run` : « ref inconnue » | La ref n'existe ni en local ni sur `origin` (`git ls-remote origin`) ; un commit non récupéré ne peut pas être vérifié en dry-run |
| `backup.sh` : « existe déjà » (code 3) | Deux sauvegardes dans la même seconde : relancer |
| Erreur de connexion PostgreSQL | `docker ps --filter name=postgres` ; `docker network inspect database` ; `DATABASE_URL` : schéma `postgresql`, hôte `postgres`, base `mangatrackr`, `%` doublés (ne jamais l'afficher en entier) |
| `could not find driver` | `docker run --rm mangatrackr:prod php -r 'var_export(PDO::getAvailableDrivers()); echo PHP_EOL;'` : `pgsql` attendu |
| `init-database.sh` : « requête impossible » | Superutilisateur du conteneur : relancer avec `PG_SUPERUSER=<nom>` |
| `app` reste en `starting` / `unhealthy` | `docker inspect --format '{{json .State.Health}}' mangatrackr_app_prod` ; journaux `app` ; `deploy.sh --rollback` si une version vient d'être déployée |
| « un autre déploiement est en cours » | Un `deploy.sh` tourne déjà (verrou `.git/mangatrackr-deploy.lock`) : attendre sa fin |
| Dépréciation `symfony/ux-toggle-password` 2.29 | Connue, sans effet sur le démarrage ; à traiter dans un chantier à part (dette technique) |
| Après un redémarrage de la VM : `app` absent, ou 500 `could not translate host name "postgres"` | Voir 9.1 |
| Google OAuth refuse `http://192.168.1.29:8080/oauth/callback/google` | Attendu : Google exige un domaine valide ; se connecter par `https://mangasync.labytech.fr` (section 11). Ne pas modifier les URI au hasard |

Commande de lecture utile (base et rôle vus par l'application) :

```bash
docker exec -u www-data mangatrackr_app_prod php bin/console dbal:run-sql 'SELECT current_database(), current_user' --env=prod --no-debug
```

### 9.1. Redémarrage de la VM : `app` ne démarre pas ou ne voit pas `postgres`
Incident du 2026-10-20. Cause : `dhcpcd` (via `ifupdown`) pose l'IPv6 puis fait l'ARP probing de
l'IPv4 (environ 3 s) alors que `network-online.target` est déjà atteint. Docker redémarre `app`
avant que `192.168.1.29` existe : le bind du port 8080 échoue (`Exited (128)`, `cannot assign
requested address`) et le conteneur reste rattaché au seul réseau `proxy` (sans `database`), d'où
`could not translate host name "postgres"` et des 500, même après un simple `docker start`.

Correctif (versionné, installé par `deploy/install-vm.sh`, voir 5.2 bis) :
1. `/etc/network/interfaces` : `auto ens18` à la place de `allow-hotplug ens18` (**à la main**,
   sauvegarde `interfaces.bak-<date>`). Effet au prochain démarrage seulement (ne pas relancer
   `networking` en SSH).
2. `/usr/local/sbin/wait-ip.sh` (755, copie de `deploy/wait-ip.sh`) : boucle jusqu'à ce que
   `ip -4 -o addr show dev ens18` contienne l'adresse (défaut `192.168.1.29`, 60 s), journalise
   `wait-ip: ... present after Ns`, sort toujours en 0.
3. `/etc/systemd/system/docker.service.d/wait-ip.conf`, puis `systemctl daemon-reload` :
   ```ini
   [Service]
   ExecStartPre=/usr/local/sbin/wait-ip.sh 192.168.1.29 60 ens18
   TimeoutStartSec=90
   ```
   Un `sh -c` imbriqué avec guillemets dans `ExecStartPre` n'a pas fonctionné (retour immédiat) :
   garder un script. L'adresse, l'interface et le délai viennent de `MANGATRACKR_BIND`,
   `WAIT_IFACE` et `WAIT_TIMEOUT` (`deploy/lib.sh`). Relancer `deploy/install-vm.sh` met à jour
   le script et le drop-in s'ils diffèrent.

Contrôle après redémarrage : `sudo journalctl -b -u docker | grep wait-ip`, `app` `healthy`, HTTP 200.

Reprise manuelle si `app` est resté sans le réseau `database` (vérifier avec `docker inspect`) :
```bash
docker compose -f compose.prod.yaml up -d --no-deps --force-recreate app
```
Un `up -d --no-deps app` seul ne recrée pas un conteneur existant : il faut `--force-recreate`.
Ne jamais utiliser `down`, ne pas toucher à `postgres`.

## 10. Sécurité
- Secrets : `/opt/apps/secrets` en 700, `mangatrackr.prod.env` en 600 ; jamais versionnés
  (`/ressources/` est ignoré par git et exclu du contexte de construction des images, de prod
  par `docker/Dockerfile.prod.dockerignore` et, sans BuildKit ou pour l'image de dev, par le
  `.dockerignore` racine) ; copie de
  secours dans un coffre chiffré ; tout secret exposé est renouvelé immédiatement.
- Réseau : l'application écoute sur `192.168.1.29:8080` (HTTP, LAN). Vérifier qu'aucune
  redirection NAT ne l'expose à Internet. PostgreSQL ne publie aucun port.
- Conteneurs `no-new-privileges` ; le conteneur `postgres` n'est jamais manipulé par les scripts,
  sauf `docker exec` (psql, pg_dump, pg_restore) et un fichier temporaire supprimé après
  restauration.
- Sauvegardes et dumps : données personnelles ; 700/600 sur la VM, transfert uniquement par SSH.

## 11. Reverse proxy HTTPS et Google OAuth
**En ligne depuis le 2026-10-21.** Reverse proxy : VM `proxy-caddy` (192.168.1.14, domaine
`mangasync.labytech.fr`, certificat Let's Encrypt), recréable depuis le dépôt GitHub privé
`Homelab-AOOSTAR/proxy-setup` (`REBUILD.md`, avec la fiche des réglages Livebox, Hostinger et Google).
Côté MangaTrackr (ADR 0016) :
- Fichier de secrets : `TRUSTED_PROXIES=192.168.1.14` et `DEFAULT_URI=https://mangasync.labytech.fr`
  (modèle `deploy/mangatrackr.prod.env.dist`). Redéployer :
  `deploy/deploy.sh --ref main` (ou `make prod-deploy` si `make` est installé).
- Console Google : ajouter l'URI de redirection `https://mangasync.labytech.fr/oauth/callback/google`.
- Contrôle depuis `proxy-caddy` : `curl -skI --resolve mangasync.labytech.fr:443:127.0.0.1
  https://mangasync.labytech.fr/login` : `Location` en `https://`, `Set-Cookie` avec `Secure`.
- Pare-feu Proxmox de `apps-dev` en dernier (règles et activation : §5.1 bis) : 8080 limité à
  192.168.1.14 et à l'IP d'admin.
- Bascule DNS (Hostinger) et redirection Livebox : faite le 2026-10-21 (ancien site Hostinger abandonné). Procédure et réglages dans `proxy-setup` (`REBUILD.md` §5, `PROXY-VM.md` §5.7).
- `DEFAULT_URI` est aussi une variable factice du build de l'image (`docker/Dockerfile.prod`) : `assets:install` la lit ; sans elle la construction échoue.
