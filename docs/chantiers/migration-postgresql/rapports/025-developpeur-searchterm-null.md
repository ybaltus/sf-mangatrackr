# 025 — développeur — searchTerm absent/non scalaire, validateur, joker `_`

- Tâche : T6 (I-B de 023)
- Date : 2026-10-18
- Statut : terminé

## Objectif
Supprimer le 500 (`TypeError`) de `POST /manga/search` sans `searchTerm` ou avec un tableau (ADR 0001) ;
durcir `SearchTermValidator` (réserve M-A) ; évaluer le joker LIKE `_`.

## Ce qui a été fait
- `src/Controller/MangaController.php` : `searchTerm` lu via `$request->request->all()['searchTerm'] ?? ''`,
  converti en chaîne si scalaire, sinon `''` (un tableau ne lève plus la `BadRequestException` 400). Le terme vide
  échoue à la validation existante : `hasError = true`, réponse 200.
- `src/Services/Common/SearchTermValidator.php` : `\s` remplacé par l'espace normal U+0020 ; doc corrigée
  (tab, \n, \r, espaces Unicode, contrôles rejetés). Choix : pas d'espaces Unicode (NBSP rejeté), plus simple et sûr.
- `src/Repository/MangaRepository.php` (`searchManga`) : `addcslashes($term, '\\%_')` avant ajout des `%`.
  Tranché : simple et sûr (échappement par défaut `\` de PostgreSQL, paramètre lié). `_` reste autorisé par le
  validateur (ex. `dr-stone_2`) et devient littéral.
- Tests : `MangaControllerTest` (champ absent, vide, tableau, saut de ligne → 200, pas de 500),
  `SearchTermValidatorTest` (\n, \t, \r, NBSP rejetés), `MangaRepositoryTest::testSearchMangaTreatsUnderscoreAsLiteral`
  (données créées puis purgées par le tearDown existant).

## Résultats
- `make qa-php-cs-fixer` : 0 fichier corrigé ; `make qa-php-stan` : No errors ;
  `make test-only` : OK (226 tests, 580 assertions).

## Écarts, risques, points non vérifiés
- Le template `mangas_list.html.twig` affiche « Aucun résultats » aussi quand le terme est invalide non vide
  (préexistant, non modifié) ; d'où l'absence d'assertion sur ce message dans le test.
- Le test du joker `_` s'appuie sur l'échappement `\` par défaut de PostgreSQL ; vérifié en base de test seulement.
- Base de dev `sf_mangatrackr_app` et `.claude/skills/verif-donnees/` non touchées.

## Recommandation pour la suite
- Optionnel : ne pas afficher « Aucun résultats » quand `hasError` (template).
