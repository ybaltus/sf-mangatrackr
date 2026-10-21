# ADR 0018 : version de patch PHP épinglée dans les images Docker

- Statut : Acceptée
- Date : 2026-10-21
- Contexte lié : ADR 0008 (PHP 8.2 minimum), ADR 0015 (`deploy/deploy.sh`, `deploy/check.sh`)

## Contexte
PHP 8.2.35 corrige une faille de sécurité (publiée le 2026-10-21). Les trois Dockerfile partaient de
l'étiquette flottante `php:8.2-apache`, mais `deploy/deploy.sh` lance `compose build` sans `--pull` :
sur la VM `apps-dev`, l'image de base en cache était réutilisée et un redéploiement ne montait pas de
version. Le devcontainer était en 8.2.34. La version réellement en service n'était ni écrite ni
contrôlée.

## Décision
- **Patch épinglé** dans `docker/Dockerfile.prod`, `.devcontainer/Dockerfile.dev` et
  `docker/Dockerfile` : `FROM php:8.2.35-apache`. Une nouvelle étiquette n'est pas en cache : le
  `compose build` suivant la télécharge et reconstruit toute l'image.
- **Contrôle en production** : `deploy/check.sh` compare `PHP_VERSION` du conteneur
  `app` au patch lu dans le `FROM` de `docker/Dockerfile.prod` ; KO en cas d'écart.
- La CI (`setup-php`, `'8.2'`) installe déjà le dernier patch : inchangée. `composer.json` garde
  `"php": ">=8.2"`.

## Conséquences
- À chaque correctif de sécurité PHP : modifier le `FROM` des trois Dockerfile, puis PR, puis
  `deploy/deploy.sh` puis `deploy/check.sh` sur la VM, où `make` n'est pas installé
  (docs/deploiement/apps-dev.md §7.8). Rien n'est automatique : surveiller
  les annonces sur php.net.
- Les mises à jour de la distribution de base (Debian) arrivent avec les nouvelles étiquettes PHP, et
  pas entre deux correctifs PHP.
