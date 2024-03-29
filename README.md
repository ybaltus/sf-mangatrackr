# MangaTrakr

![Symfony](https://img.shields.io/badge/symfony-%23000000.svg?style=for-the-badge&logo=symfony&logoColor=white)
![MariaDB](https://img.shields.io/badge/MariaDB-003545?style=for-the-badge&logo=mariadb&logoColor=white)
![NodeJS](https://img.shields.io/badge/node.js-6DA55F?style=for-the-badge&logo=node.js&logoColor=white)
![Webpack](https://img.shields.io/badge/webpack-%238DD6F9.svg?style=for-the-badge&logo=webpack&logoColor=black)
![TailwindCSS](https://img.shields.io/badge/tailwindcss-%2338B2AC.svg?style=for-the-badge&logo=tailwind-css&logoColor=white)
![PhpStorm](https://img.shields.io/badge/phpstorm-143?style=for-the-badge&logo=phpstorm&logoColor=black&color=black&labelColor=darkorchid)
![Linux](https://img.shields.io/badge/Linux-FCC624?style=for-the-badge&logo=linux&logoColor=black)

Application web pour suivre la lecture des mangas. Suivre facilement sa progression pour une meilleure expérience de lecture.

![My Image](mangatrackr.png)

## Fonctionnalités
- [x] Espace d'administration
- [x] Inscription sur invitation
- [x] Authentification avec login/mdp
- [x] Gérer son compte utilisateur
- [x] Rechercher un manga dans la base de données
- [x] Rechercher un manga avec l'API
- [x] Consulter le détail d'un manga
- [x] Gérer son suivi depuis une scanthèque
- [x] Calendrier de sortie de mangas
- [x] Exporter sa scanthèque
- [x] Authentification avec Google
- [ ] Traduction Anglais et Français
- [ ] Améliorer les fonctionnalités existantes
- [ ] Inscription ouvert

## Pour commencer

### Pré-requis

- PHP >= 8.1
- Composer v2
- NodeJS
- MariaDB

### Installation

Éxécutez les commandes ci-dessous pour installer le projet.

* Avec Docker :
```
- make docker-compose-up
- App disponible sur localhost:8080 
- Utilisateur de démo :  demo@demo.fr / password
```

* Avec **Symfony CLI** +  **Commande make** :
```
- make first-install ## Pour installer les dépendences
- make npm-build ## Pour compiler les assets
- symfony console app:init-datas ## Initialiser les données nécessaires en bdd
```
* Sinon :
```
- composer install 
- npm install --force
- npm run build
- php bin/console security:check
- php bin/console doctrine:database:create --if-not-exists
- php bin/console doctrine:database:migrate --no-interaction
- php bin/console app:init-datas ## Initialiser les données nécessaires en bdd
```

## Démarrage

* Avec **Symfony CLI** + **Commande make** :
```
- make sf-start 
```
* Sinon :
```
- php bin/console server:start -d
```

## Stack technique

* [Symfony 6.4](https://symfony.com/) - Framework PHP
* Doctrine ORM
* Twig
* MariaDB
* [doctrine/doctrine-fixtures-bundle](https://symfony.com/bundles/DoctrineFixturesBundle/current/index.html)
* [symfony/webpack-encore-bundle](https://symfony.com/doc/6.4/frontend/encore/index.html) - Module Bundler
* [knpuniversity/oauth2-client-bundle](https://github.com/knpuniversity/oauth2-client-bundle?tab=readme-ov-file) - OAuth2 client
* [fakerphp/faker](https://github.com/FakerPHP/Faker) - Librairie PHP
* [easycorp/easyadmin-bundle](https://symfony.com/bundles/EasyAdminBundle/current/index.html) - Générateur d'administrateur pour Symfony
* [knplabs/knp-paginator-bundle](https://github.com/KnpLabs/KnpPaginatorBundle) - Bundle de pagination
* [phpoffice/phpspreadsheet](https://github.com/PHPOffice/PhpSpreadsheet) - Librairie PHP (Xls, Csv, Pdf, etc.)
* [symfony/stimulus-bundle](https://symfony.com/bundles/StimulusBundle/current/index.html) - Framework Javascript
* [stimulus-use](https://stimulus-use.github.io/stimulus-use/#/) - Collection de composants Stimulus
* [symfony/ux-twig-component](https://symfony.com/bundles/ux-twig-component/current/index.html)
* [HtmX](https://htmx.org/) - Framework Front-end
* [Flowbite](https://flowbite.com/) - Librairie de composants UI 
* [Tailwindcss](https://tailwindcss.com/) - Framework CSS

## Outils QA & Tests utilisés
* [friendsofphp/php-cs-fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer) - PHP Coding Standards Fixer
* [phpstan/phpstan](https://github.com/phpstan/phpstan) - PHP Static Analysis Tool
* [nunomaduro/phpinsights](https://github.com/nunomaduro/phpinsights) - Static Analysis Tool
* [phpro/grumphp](https://github.com/phpro/grumphp) - 
* [symfony/test-pack](https://symfony.com/doc/6.4/testing.html#application-tests) - PHP Unit

## Commandes consoles
* Pour générer un utilisateur :
    * **php bin/console app:create-user**
* Pour tester l'envoi d'un email avec la config SMTP :
    * **php bin/console app:test-email emailFrom emailTo**
* Pour initialiser les données fictive:
  * **php bin/console doctrine:fixtures:load**

## Ressources externes utilisées
* **API REST** :
  * [Jikan API 4.0.0](https://docs.api.jikan.moe/)
  * [MangaUpdates API 1.0.0](https://api.mangaupdates.com/)
* **Illustrations** : [Transhumans](https://www.transhumans.xyz/)
* **Avatars** : [UI Faces](https://www.uifaces.co/)
* **Google Fonts** : 
  * [Bangers](https://fonts.google.com/specimen/Bangers)
  * [Sora](https://fonts.google.com/specimen/Sora)