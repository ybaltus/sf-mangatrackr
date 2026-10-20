# ADR 0015 : déploiement Compose scripté sur la VM apps-dev

- Statut : Acceptée
- Date : 2026-10-18
- Contexte lié : [ADR 0014](0014-bascule-homelab-simplifiee-et-retrait-mariadb.md), chantier
  `deploiement-homelab` (rapports 001 à 006), procédure
  [`docs/deploiement/apps-dev.md`](../deploiement/apps-dev.md)

## Contexte
Après l'ADR 0014, la production tourne sur la VM `apps-dev` du homelab (Debian 13, Docker) avec un
PostgreSQL 18 mutualisé (conteneur `postgres`, réseau Docker `database`). Le déploiement était
manuel : `compose.prod.yaml` et `docker/Dockerfile.prod` posés à la main hors git, secrets de prod
dans `ressources/` (non entièrement ignoré par git), commandes tapées au cas par cas, aucune
sauvegarde systématique avant migration et aucun retour arrière outillé. Le `docker/docker.sh` du
dépôt (dev/démo) lance des migrations, des fixtures et crée un utilisateur de démonstration : il
ne doit jamais toucher la production. Il faut pouvoir réinstaller la VM de zéro de façon
reproductible.

## Décision
- **Fichiers de production versionnés** : `compose.prod.yaml` (services `app` et `worker`, sans
  PostgreSQL), `docker/Dockerfile.prod` et sa `.dockerignore`. L'image `mangatrackr:prod` est
  **construite sur la VM** à partir du clone git.
- **Scripts idempotents `deploy/`** (bash, `--dry-run` partout, jamais `docker compose down`) :
  `install-vm.sh`, `init-database.sh [--restore]`, `backup.sh`, `deploy.sh`, `check.sh`, avec les
  cibles `make prod-deploy|prod-check|prod-backup`.
- **Migrations uniquement avec `deploy.sh --migrate`**, après une sauvegarde `pg_dump -Fc`, en une
  seule transaction (`--all-or-nothing`). Sans l'option, une migration en attente arrête le
  déploiement avant tout redémarrage.
- **Retour arrière par image** : l'image en service est épinglée par nom (`mangatrackr:prod-service`)
  avant la construction (magasin containerd : l'ancienne image disparaît dès que `mangatrackr:prod`
  est déplacé, un retag par ID échoue), puis retaguée `mangatrackr:prod-previous` par nom juste
  avant le `up` d'un déploiement (une tentative interrompue avant ne la modifie pas ; image
  déjà introuvable : pas de retour arrière pour ce cycle, sans échec) ;
  `deploy.sh --rollback` la remet en service. Le schéma n'est pas ramené en
  arrière : on restaure la sauvegarde prise par `--migrate`.
- **Secrets hors dépôt** : `/opt/apps/secrets/mangatrackr.prod.env` (700/600) sur la VM, modèle
  versionné `deploy/mangatrackr.prod.env.dist` sans valeur réelle ; `/ressources/` entièrement ignoré
  par git et exclu du contexte de construction.
- **PostgreSQL mutualisé hors des scripts** : le conteneur `postgres` n'est jamais créé, recréé ni
  reconfiguré ; les scripts n'y font que `docker exec` (psql, pg_dump, pg_restore). Rôle et base
  applicatifs créés seulement s'ils manquent.
- **Nom de projet Compose fixé** : `name: sf-mangatrackr` dans `compose.prod.yaml` (surchargeable
  par `COMPOSE_PROJECT_NAME`), au lieu d'un nom déduit du répertoire. `deploy.sh` vérifie en
  préflight le label `com.docker.compose.project` des conteneurs app et worker, et refuse tout
  conteneur étranger dans ce projet, `postgres` compris ; `up` ne fait pas de `--remove-orphans`.
- `docker/Dockerfile` et `docker/docker.sh` restent réservés au dev et à la démo.

### Alternatives écartées
- **Registre d'images** (construction en CI, `docker pull` sur la VM) : un registre de plus à
  héberger ou un service tiers, pour une seule VM et un seul mainteneur. La construction locale
  suffit ; à revoir si plusieurs machines doivent déployer la même image.
- **CI/CD** (déploiement automatique depuis GitHub) : il faudrait ouvrir la VM du LAN à GitHub
  (runner auto-hébergé ou SSH entrant) et y stocker des secrets. Le déploiement reste déclenché à la
  main sur la VM.
- **Migrations automatiques au démarrage du conteneur** : une migration appliquée sans sauvegarde
  préalable, éventuellement par deux conteneurs à la fois (`app` et `worker`), et une image qui
  modifie la base à chaque redémarrage. Écarté au profit d'une étape explicite.

## Conséquences
- Réinstallation documentée de la VM vierge à l'application en service
  (`docs/deploiement/apps-dev.md`) ; l'installation actuelle doit être migrée une fois (fichiers
  posés à la main retirés avant `git pull`).
- Un seul niveau de retour arrière (image précédente) ; pas de retour de schéma automatique.
- Les sauvegardes restent sur la VM (rotation 7) : copie hors VM et test de restauration
  périodique à organiser (dette technique).
- Les scripts n'ont pas pu être exécutés contre un vrai Docker dans le devcontainer : essai
  `--dry-run` sur la VM par l'utilisateur avant le premier usage réel.
- Reportés : reverse proxy HTTPS, domaine et Google OAuth.

## Mise à jour du 2026-10-20 : démarrage après reboot
Panne constatée au redémarrage de la VM : `dhcpcd` (ifupdown) pose l'IPv4 environ 3 s après
`network-online.target`, Docker relance `app` avant, le bind de `192.168.1.29:8080` échoue et
le conteneur reste sans réseau `database`. Décision : `deploy/wait-ip.sh` (versionné) est
installé par `install-vm.sh` avec un drop-in `docker.service.d` (`ExecStartPre`), de façon
idempotente et sans redémarrer Docker ; le passage de `ens18` en `auto` reste manuel (risque en
SSH). Détail et reprise : `docs/deploiement/apps-dev.md`, sections 5.2 bis et 9.1.
