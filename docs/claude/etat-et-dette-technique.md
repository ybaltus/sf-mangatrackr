# État du projet et dette technique

Document **daté** : à relire et à mettre à jour de temps en temps.
Dernière mise à jour : 2026-10-19 (PR #20 fusionnée dans `main`, chantier `deploiement-homelab` clos).

## État actuel
- 226 tests, 580 assertions, PHPStan niveau 6 sans erreur, `composer audit` sans avis.
- Toutes les dépendances Composer mises à jour dans leurs contraintes (voir l'ADR 0005).
- `declare(strict_types=1)` dans tous les fichiers PHP de `src/` et `tests/` (ADR 0001).
- GrumPHP actif (ADR 0006).
- PHP 8.2 minimum depuis l'ADR 0008 (le lock issu de la mise à jour n'était pas installable
  sous PHP 8.1, ce que la CI a révélé).
- Lockfile npm régénéré avec npm 11 (ADR 0009).
- `npm audit fix` sans version majeure : 58 vulnérabilités ramenées à 27, plus aucune critique ; `webpack` borné à `~5.111.1` (ADR 0010).
- PR #16 fusionnée dans `main` ; la CI de `main` est verte (PHP 8.2 : Composer, `npm ci`,
  build, cs-fixer, PHPStan, migrations, fixtures, PHPUnit). `dev` est à jour avec `main`.

## Après la migration PostgreSQL (chantier `migration-postgresql`, 2026-10-18)
- PostgreSQL 18 en développement, test et CI (ADR 0012) ; schéma = une baseline PG unique
  (les 15 migrations MariaDB sont archivées) ; table `user` renommée `app_user`.
- 226 tests (référence du dernier passage vert de `make test-only` côté chef : 226 tests / 580 assertions),
  PHPStan niveau 6 sans erreur ; `doctrine:schema:validate` et `migrations:diff` sans écart (rapport 022).
- Parité des données prouvée MariaDB vers PostgreSQL : 22 tables identiques (empreintes tolérante
  et stricte), 19 séquences recalées, 71 014 lignes ; `doctrine_migration_versions` volontairement
  non transférée (rapports 017, 022).
- Recherche insensible à la casse et aux accents (`LOWER` + `unaccent`, fonction DQL `UNACCENT`).
- **Production cible : le homelab sous PostgreSQL 18** (Hostinger abandonné) : export prêt
  (`make pg-export`), import fait par l'utilisateur (`docs/chantiers/migration-postgresql/homelab.md`).
- MariaDB retiré de l'infra, de la config et des docs vivantes (T8, rapport 035) : plus de service
  `database`, de volume `db-data` ni de `pdo_mysql`. Les outils de migration MariaDB sont conservés
  comme archive (skills `import-dump-mariadb`, `verif-donnees`, agents `migration-*`).
- **La CI PostgreSQL n'a jamais tourné pour de vrai** : `dev` est poussé, mais aucune PR n'est ouverte ;
  le workflow (service `postgres:18`, rôle créé par `psql`) n'est vérifié que par lecture et par
  exécution partielle en local (rapports 011, 016).

## Vérifications manuelles
Faites à la main le 2026-10-17 (par le mainteneur), avec les versions mises à jour :
- interface d'administration EasyAdmin (4.29) : OK ;
- connexion Google OAuth (Guzzle 8) : OK ;
- scanthèque, dont la modification des chapitres (ADR 0001) : OK.

La couverture **automatique** de ces zones reste faible (voir la dette technique).

## Dette technique connue
| Sujet | Détail |
|---|---|
| Versions majeures en attente | PHPStan 1.x vers 2.x, PHPUnit 9 vers 11, Doctrine ORM 2 vers 3 et DBAL 3 vers 4 (contraintes `^2` / `^3` dans `composer.json`) |
| Paquets abandonnés | `doctrine/cache`, `symfony/ux-toggle-password` (aucun remplaçant indiqué) |
| Dépendances JavaScript | 27 vulnérabilités npm restantes (11 hautes, 16 modérées), toutes dans l'outillage de build (surtout le serveur de développement Webpack, non utilisé). Elles exigent `@symfony/webpack-encore` 5, `tailwindcss` 4 et `webpack-notifier`. Borne `webpack ~5.111.1` à retirer avec Encore 5 (ADR 0010). Autres majeures en retard : `flowbite` 4, `htmx.org` 2, `postcss-loader` 8, `webpack-cli` 7 |
| Base de test | Créée par `docker/postgres/init/01-create-test-database.sh` à la première création du volume `pg-data` (ou par `make test`, le rôle ayant `CREATEDB`) |
| Lockfile npm | Les liens `file:vendor/symfony/ux-*/assets` font planter npm 10.8 (Node 20) quand les assets UX changent ; lockfile régénéré avec npm 11 (ADR 0009). Envisager Node 22/24 ou npm 11 dans le devcontainer et la CI |
| Environnement | `APP_ENV=dev` exporté par le shell du devcontainer, contourné dans le Makefile |
| Tests réseau | Plusieurs tests de commandes (`ApiTenraiCommandTest`, `InitDataCommandTest`, `ApiMangaUpdatesCommandTest`) appellent les vraies API : réseau requis, résultats non déterministes |
| Admin | Presque pas de tests sur EasyAdmin (un test de redirection du dashboard) |
| `strict_types` | Les tableaux `mixed` venant du front, de la ligne de commande ou des API échappent à PHPStan : un `int` ou une chaîne mal typé peut lever une `TypeError` à l'exécution. Audit non exhaustif |
| Routes | `Symfony\Component\Routing\Annotation\Route` (déprécié en 6.4) à remplacer par `Symfony\Component\Routing\Attribute\Route` |
| Fichiers parasites | `tests/Command/ApiTenraiTestCommandTest.php.save` |
| Symfony 6.4 | Version LTS ; la suivante (7.4 LTS) demandera une migration |

## Dette issue de la migration PostgreSQL
| Sujet | Détail | Rapport |
|---|---|---|
| `%` dans `DATABASE_URL` | Les `%` du mot de passe doivent être doublés (`resolve` du conteneur), en CI comme dans un `.env.local` écrit à la main ; un outil externe lisant l'URL brute verrait un mot de passe doublé. Mot de passe local alphanumérique conseillé | 016 |
| PHP Insights | `make qa-all` retourne 2 à cause de `qa-php-insights` (message sur l'accès réseau pour l'audit de sécurité) ; cause exacte non isolée, état avant migration non comparé. cs-fixer, PHPStan et security:check sont verts | 015 |
| Dépréciations Doctrine | Nombreuses sous PostgreSQL (colonnes identity émulées par séquence, ORM 3.0) : fixer la stratégie SEQUENCE/IDENTITY avant ORM 3. Revérifier aussi avec DBAL 4 que le diff ne propose pas de supprimer l'index `LOWER(email)` | 015, etat.md |
| Recherche | Pas d'index pour `unaccent(lower(title))` ni `pg_trgm` (volume faible, non mesuré) ; `pg_trgm` à envisager si la recherche ralentit (fonction IMMUTABLE requise) | 012, 014 |
| Sensibilité aux accents | `searchByTitles` (dédoublonnage MangaUpdates) et la recherche EasyAdmin restent sensibles aux accents ; décision fonctionnelle à prendre pour `searchByTitles` | 012 |
| Jokers `LIKE` | `_` saisi par l'utilisateur reste un joker (comportement antérieur) ; `%` et `\` sont rejetés par `SearchTermValidator` | 012, 021, 025 |
| Libellé de recherche | `mangas_list.html.twig` affiche « Aucun résultats » aussi pour un terme invalide ; `hasError` n'est pas affiché | 021, 025 |
| Tests rejouables | Purge via `PurgeTestDataTrait` ; `dama/doctrine-test-bundle` (rollback transactionnel global) à envisager. Un test interrompu avant `tearDown` laisse des lignes (`make test` les efface) | 013 |
| Doublons d'emails | Test du dump final avec des emails identiques à la casse près (index unique sur `LOWER(email)`) non fait | 020 |
| Recette (rapport 019) | Non vérifiés ni couverts : `smut_datas`/`umut_datas`/`dmut_datas` avec données valides, inscription, changement de mot de passe, suppression de compte, recherche avancée (API externes), connexion réelle, écritures EasyAdmin. Test automatisé de l'export CSV et de `searchManga` avec accents non écrit | 019 |
| Outil de transfert | M-F : `--inject-failure` présent dans le script de production ; `--require-baseline` n'accepte pas « baseline seule version ». Le garde-fou (hôte + base du DSN, `CONFIRM_TARGET`) ne détecte pas un alias DNS ou un tunnel nommé comme la cible attendue : en production, fixer `TRANSFER_EXPECTED_HOST/DB` et saisir `CONFIRM_TARGET` à la main | 023, 024 |
| Accès source | Un agent a lu `_import` avec l'utilisateur root MariaDB (lecture seule) ; préférer l'utilisateur applicatif limité à `SELECT` | 024 |

## Dette issue du déploiement sur le homelab (chantier `deploiement-homelab`, 2026-10-18)
Procédure : `docs/deploiement/apps-dev.md`, ADR 0015.
| Sujet | Détail | Rapport |
|---|---|---|
| HTTPS et Google OAuth | Pas de reverse proxy ni de domaine : application en HTTP sur `192.168.1.29:8080` (LAN) ; Google refuse l'URI de redirection sur IP privée, connexion Google non fonctionnelle en prod. Chantier à part (proxies de confiance, URL canonique, URI OAuth) | 002 |
| Redémarrage de la VM `apps-dev` | Panne du 2026-10-20 (course DHCP/Docker, `app` sans réseau `database`) corrigée et validée par un redémarrage : `auto ens18` (manuel, doc 5.2 bis) + `deploy/wait-ip.sh` et drop-in `docker` installés par `install-vm.sh` (doc 9.1, ADR 0015). Reste à rejouer `install-vm.sh` sur la VM (aligne le script et le drop-in sur la version du dépôt) ; pas de supervision ni d'alerte si `app` est arrêté | — |
| `symfony/ux-toggle-password` 2.29 | Dépréciation constatée au démarrage en prod (paquet abandonné, voir plus haut) ; à remplacer dans un chantier à part | 002 |
| Healthcheck du worker | `mangatrackr_worker_prod` n'a pas de healthcheck : `check.sh` et `up --wait` ne vérifient que « running », pas la consommation effective de `async` | 001, 002 |
| Sauvegardes | `deploy/backup.sh` écrit sur la VM seulement (rotation 7, non chiffré) : copie hors VM à organiser ; test de restauration (procédure 7.6 de la doc) à planifier et à répéter. Remplacement d'une base non vide : manuel (renommage, doc 7.5) | 001, 002 |
| Retour arrière | Un seul niveau (`mangatrackr:prod-previous`) et pas de retour de schéma : restaurer la sauvegarde prise par `--migrate` | 001 |
| Scripts éprouvés en réel une fois | Premier déploiement réussi sur la VM le 2026-10-19 (`check.sh` 10/10). Pas encore éprouvés sur la VM : `--rollback`, `--migrate`, `init-database.sh --restore`, test de restauration (7.6) | 001, 003, 008 |
| Pas de `prod-previous` sur la VM | L'ancienne image a été perdue au premier déploiement (magasin containerd : le build déplace le tag et supprime l'enregistrement, ADR 0015). `prod-previous` n'existera qu'après le 2e déploiement scripté ; d'ici là, retour arrière par sauvegarde `pg_dump` + `deploy.sh --ref` | 008 |
| Bruit de dépréciation dans `check.sh` | Les commandes `bin/console` lancées dans le conteneur impriment la dépréciation `ux-toggle-password` à chaque contrôle (sortie noyée, sans gravité) | — |
| Healthcheck de `postgres` | Le conteneur mutualisé n'a pas de healthcheck (avertissement à chaque `deploy.sh`/`backup.sh`) : à ajouter dans la doc/compose du PostgreSQL mutualisé | — |
| `make test-only` non rejoué dans le conteneur de travail | L'hôte `postgres` n'y est pas joignable lors de la session de clôture ; la CI de la PR #20 (cs-fixer, PHPStan, PHPUnit) était verte | 008 |
| Doc du PostgreSQL mutualisé absente | Le conteneur `postgres` (image, volume, réseau `database`, healthcheck, sauvegarde du cluster) n'est documenté nulle part dans le dépôt ; la doc locale de la VM dit « à documenter séparément ». Une réinstallation de VM vierge n'est donc pas exécutable de bout en bout (section 5.3 de `apps-dev.md`) | 005, 006 |
| Journaux Apache | `docker/vhosts/mangatracker.conf` écrit `ErrorLog`/`CustomLog` dans des fichiers du conteneur : absents de `compose logs`, hors du contrôle de secrets de `check.sh`, croissance dans la couche inscriptible. Vhost de prod vers `/proc/self/fd/1` et `/2` à prévoir | 005, 006 |
| Images de dev construites avant 2026-10-18 | Le `.dockerignore` racine n'excluait pas `ressources/` : une image construite par `docker/Dockerfile` (ou `Dockerfile.prod` sans BuildKit) avant la correction peut contenir l'env de prod et un dump. Les supprimer (`docker image ls`, `docker image rm`) puis reconstruire ; sur la VM, contrôle `ls /var/www/ressources` (section 6 de `apps-dev.md`) | 005, 006 |

## Pistes pour la suite
Ordre suggéré :
1. Faire un 2e déploiement scripté sur la VM (crée `prod-previous`), puis tester `--rollback` et la restauration (7.6). Reverse proxy HTTPS et Google OAuth ensuite (chantier à part). L'import de la base et le premier déploiement sont faits.
2. Montées majeures JS, une par une : d'abord `@symfony/webpack-encore` 5 (retirer la borne webpack), puis `tailwindcss` 4 (chantier à part), `htmx.org` 2, `flowbite`.
3. Tests fonctionnels de l'admin EasyAdmin et de la connexion Google (avec `MockHttpClient`).
4. Montée de PHPStan (2.x, puis niveau 7 ou 8) et de PHPUnit.
5. Migration des attributs `Route`.
