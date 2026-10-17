# ADR 0009 : lockfile npm régénéré avec npm 11 (assets Symfony UX)

- Statut : Acceptée
- Date : 2026-10-17
- Contexte lié : ADR 0005 (mise à jour des dépendances), ADR 0008

## Contexte
`package.json` référence deux assets Symfony UX par des liens `file:` :
`@symfony/ux-autocomplete` et `@symfony/ux-toggle-password`
(`file:vendor/symfony/ux-*/assets`). La mise à jour Composer de l'ADR 0005 a fait passer
`ux-autocomplete` à 2.36.2, dont le `package.json` déclare 9 `devDependencies` d'outillage
(vitest 4, jsdom, TypeScript...) au lieu de 3. Avec npm 10.8.2 (livré avec Node 20, version de
la CI et du devcontainer), `npm ci` et même `npm install` plantent :
`Cannot read properties of null (reading 'edgesOut')` (bogue d'Arborist, `#loadPeerSet`), y
compris sans lockfile. Le lockfile décrivait en plus les anciens assets (`version: 1.0.0`).

## Décision
Régénérer `package-lock.json` avec **npm 11** (`npx npm@11 install --package-lock-only`) à partir
du lockfile existant. Le lockfile complet est ensuite lu sans erreur par npm 10.8.2 : `npm ci` et
`npm run build` réussissent avec la version de la CI. Aucun changement de `package.json`, aucune
montée de version de la CI.

## Conséquences
- Le lockfile gagne environ 100 paquets de **développement** (outillage des assets UX liés) et en
  perd 67 ; 27 versions changent. Ils n'ont aucun effet sur le code de l'application.
- Cause structurelle : npm traite un lien `file:` comme un workspace et installe les
  `devDependencies` de sa cible. Toute montée des paquets `symfony/ux-*` peut changer le lockfile.
- **À retenir** : après une mise à jour Composer des paquets `symfony/ux-*`, vérifier
  `npm ci` ; si npm 10.8 plante, régénérer le lockfile avec `npx npm@11 install --package-lock-only`.
  Sans cela, la CI échoue à l'étape `npm ci`.
