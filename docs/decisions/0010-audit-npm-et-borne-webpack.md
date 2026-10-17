# ADR 0010 : audit npm sans version majeure et borne sur webpack

- Statut : Acceptée
- Date : 2026-10-17
- Contexte lié : ADR 0009 (lockfile npm)

## Contexte
`npm audit` signalait 58 vulnérabilités (3 critiques, 21 hautes, 29 modérées, 5 basses) sur 889
paquets, dont 5 directs. Elles viennent de l'outillage de build (Webpack, Encore, Tailwind,
PostCSS), pas des bibliothèques livrées au navigateur (`flowbite`, `htmx.org`, `stimulus-use`
n'étaient pas signalées). Les plus graves visent le serveur de développement de Webpack
(`express`, `body-parser`, `webpack-dev-middleware`, `websocket-driver`...), que les scripts
`dev`, `watch` et `build` n'utilisent pas.

## Décision
- Appliquer `npm audit fix` **sans `--force`**, avec npm 11, sur le lockfier seul
  (`--package-lock-only`), puis valider avec npm 10.8.2 (version de la CI).
- **Borner `webpack` à `~5.111.1`** dans `package.json` (au lieu de `^5.74.0`). Le correctif
  automatique le montait à 5.112.0, qui ne contient plus
  `webpack/lib/dependencies/LoaderDependency` : Encore 4.x (`copy-files-loader`) et
  `@symfony/stimulus-bridge` 3.x le chargent, et le build échouait (6 erreurs). 5.111.1 corrige
  l'avis de sécurité de webpack (corrigé depuis 5.94).
- Les montées majeures sont traitées à part (voir ci-dessous).

## Conséquences
- 58 vulnérabilités ramenées à 27 (plus aucune critique, 11 hautes, 16 modérées). Tailwind passe
  de 3.4.1 à 3.4.19, Encore de 4.6 à 4.7.
- Les 27 restantes exigent une version majeure : `@symfony/webpack-encore` 5 (serveur de
  développement, `serialize-javascript`, cssnano), `tailwindcss` 4 (migration lourde de la
  configuration, compatibilité avec `flowbite` à vérifier) et `webpack-notifier`.
- **La borne `~5.111.1` est à retirer lors du passage à Encore 5.** D'ici là, ni `npm update` ni
  un robot de mise à jour ne doivent la contourner.
- Le CSS généré est équivalent : 4 classes disparaissent (`-mx-1`, `-my-1`, `lg:py-3`, `me-1`),
  mais ce sont des faux positifs de l'extraction de Tailwind 3.4.1 (fragments de `-mx-1.5` ou
  `lg:py-3.5`) ; les classes réellement utilisées sont présentes.
- **Procédure** après toute mise à jour npm : `npm ci` (npm 10.8.2), `npm run build`, comparer
  l'ensemble des classes CSS et le manifeste, puis `make test-only`.
