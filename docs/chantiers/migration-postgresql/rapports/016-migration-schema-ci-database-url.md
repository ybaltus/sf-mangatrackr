# 016 — migration-schema — DATABASE_URL de la CI robuste aux caractères spéciaux

- Tâche : T4.6-bis
- Date : 2026-10-18
- Statut : terminé (exécution GitHub non vérifiable)

## Objectif
Point « Important 1 » du rapport 014 : un `DB_PASSWORD` contenant `@ : / # ? % $` cassait la `DATABASE_URL` composée dans `.env.test`.

## Ce qui a été fait
- `.github/workflows/ci.yml` : nouvelle étape « Build DATABASE_URL with encoded password » (après la copie de `.env.test.local`). Elle calcule l'URL en PHP (déjà installé par setup-php), l'écrit dans `$GITHUB_ENV` ; une variable d'environnement réelle l'emporte sur `.env`/`.env.test(.local)` (Dotenv n'écrase pas). Le mot de passe est lu via `getenv` (jamais en argument de commande), masqué par `::add-mask::`, l'étape échoue si le mot de passe est vide ou multiligne.
- Étape psql de création du rôle inchangée (mot de passe passé par `-v` puis `format(... %L)`, correctement quoté). Base de test toujours `${DB_NAME}_test`, cohérente avec `dbname_suffix` de doctrine.yaml. `DB_ROOT_PASSWORD` : aucune référence restante (vérifié par grep dans `.github` et `.env.test`).
- Aucun autre fichier modifié (`.env.test` et doctrine.yaml intacts).

## Résultats
Point découvert en testant : un simple `rawurlencode` ne suffit PAS. `doctrine.yaml` lit `%env(resolve:DATABASE_URL)%` ; le processeur `resolve` interprète `%40s%3A...%` comme un paramètre du conteneur et lève `ParameterNotFoundException: non-existent parameter "40s"`. Il faut donc doubler chaque `%` après l'encodage (`%%` redevient `%` à la résolution).

Test local (mot de passe `p@s:s/w#o?r%d$x%40y%3A&+ z`, étape YAML extraite avec le Yaml de Symfony puis exécutée avec un faux `GITHUB_ENV`) :
- YAML valide (parse Symfony OK).
- URL produite avec `%%xx`, injectée en `DATABASE_URL`, noyau Symfony démarré en env test : mot de passe décodé identique à l'original (`match=true`), user, host et base `sf_mangatrackr_app_test` corrects (via les paramètres réels de la connexion DBAL, donc le parseur d'URL de Doctrine).
- Sans doublement des `%` : échec reproduit (ParameterNotFoundException).
- Mot de passe vide : étape en échec (rc=1).
- `make qa-php-stan` : No errors. `make test-only` : OK (193 tests, 546 assertions).

## Écarts, risques, points non vérifiés
- L'exécution réelle sur GitHub Actions n'est pas vérifiable ici (pas de runner). Non testés : lecture de `$GITHUB_ENV` par les étapes suivantes, comportement du masquage, connexion réelle à PostgreSQL avec ce mot de passe (le rôle étant créé par psql avec `%L`, aucun risque identifié). Le quotage des guillemets de `php -r '...'` a été validé en exécutant l'étape extraite du YAML sous bash, pas sous le shell du runner (bash aussi par défaut sur ubuntu).
- L'URL en variable d'environnement contient des `%%` : elle n'est valable que via `resolve` (cas de l'application) ; un outil externe lisant `DATABASE_URL` brute verrait un mot de passe doublé.
- Mot de passe avec guillemets/backslash : supporté (lu via getenv). Mot de passe multiligne : refusé volontairement.
- Le mot de passe local (`.env.local`) a le même piège : tout `DATABASE_URL` écrit à la main avec `%` dans `.env.local` doit doubler les `%` ; non traité (hors périmètre).

## Recommandation pour la suite
Premier run via PR brouillon pour valider la CI. Éventuellement noter dans la doc que `.env` compose `DATABASE_URL` sans encodage (mot de passe local alphanumérique conseillé, ou encoder et doubler les `%`).
