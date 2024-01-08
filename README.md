# MangaTrakr

![Symfony](https://img.shields.io/badge/symfony-%23000000.svg?style=for-the-badge&logo=symfony&logoColor=white)
![MariaDB](https://img.shields.io/badge/MariaDB-003545?style=for-the-badge&logo=mariadb&logoColor=white)
![NodeJS](https://img.shields.io/badge/node.js-6DA55F?style=for-the-badge&logo=node.js&logoColor=white)
![Webpack](https://img.shields.io/badge/webpack-%238DD6F9.svg?style=for-the-badge&logo=webpack&logoColor=black)
![TailwindCSS](https://img.shields.io/badge/tailwindcss-%2338B2AC.svg?style=for-the-badge&logo=tailwind-css&logoColor=white)
![Figma](https://img.shields.io/badge/figma-%23F24E1E.svg?style=for-the-badge&logo=figma&logoColor=white)
![PhpStorm](https://img.shields.io/badge/phpstorm-143?style=for-the-badge&logo=phpstorm&logoColor=black&color=black&labelColor=darkorchid)
![Linux](https://img.shields.io/badge/Linux-FCC624?style=for-the-badge&logo=linux&logoColor=black)

Application web pour suivre la lecture des mangas. Suivez facilement sa progression pour une meilleure expérience de lecture.

En ligne sur [mangasync.labytech.fr](https://mangasync.labytech.fr/).

## Pour commencer

Le projet n'utilise actuellement pas docker, de ce fait, il est nécessaire d'avoir les pré-requis d'installer sur son environnement.

### Pré-requis

Pour lancer le projet vous aurez besoin de la configuration suivante :

- PHP >= 8.1
- Composer v2
- NodeJS
- MariaDB

### Installation

Éxécutez les commandes ci-dessous pour installer le projet.

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

* Symfony 6.4
* Doctrine ORM
* Twig
* MariaDB
* doctrine/doctrine-fixtures-bundle
* symfony/webpack-encore-bundle
* fakerphp/faker
* easycorp/easyadmin-bundle
* knplabs/knp-paginator-bundle
* symfony/stimulus-bundle
* symfony/ux-twig-component
* stimulus-use
* HtmlX
* Flowbite + Tailwindcss

## Outils QA & Tests utilisés
* friendsofphp/php-cs-fixer
* phpstan/phpstan
* nunomaduro/phpinsights
* phpro/grumphp
* phpunit/phpunit

## Commandes consoles
* Pour générer un utilisateur :
    * **php bin/console app:create-user**
* Pour tester l'envoi d'un email avec la config SMTP :
    * **php bin/console app:test-email emailFrom emailTo**
* Pour initialiser les données fictive:
  * **php bin/console doctrine:fixtures:load**

## Ressources externes utilisées
* **API REST** : [Jikan API 4.0.0](https://docs.api.jikan.moe/)
* **Illustrations** : [Transhumans](https://www.transhumans.xyz/)
* **Avatars** : [UI Faces](https://www.uifaces.co/)
* **Google Fonts** : 
  * [Bangers](https://fonts.google.com/specimen/Bangers)
  * [Sora](https://fonts.google.com/specimen/Sora)