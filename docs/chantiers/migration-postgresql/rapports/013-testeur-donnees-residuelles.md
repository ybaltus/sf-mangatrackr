# 013 — testeur — données résiduelles des tests

- Tâche : T4 (suite : données résiduelles des tests)
- Date : 2026-10-18
- Statut : terminé

## Objectif
Qu'aucune donnée ne subsiste après `make test-only` (ADR 0007), sans toucher au code métier.

## Ce qui a été fait
- Comptage par table (script hors dépôt, `dbal:run-sql`, `APP_ENV=test`) avant/après un passage : résidus = `app_user`, `user_track_list`, `user_news` (+ cascade 1-1) et `user_invitation_code`, causés par `CreateUserCommandTest` (utilisateur « Tester ») et `InvitationControllerTest` (utilisateur « userInvit » + code). `EasyAdminSubscriberTest` n'écrit rien (EntityManager mocké) : non fautif, contrairement à l'hypothèse du chef.
- Nouveau trait `tests/Traits/PurgeTestDataTrait.php` : supprime par email les codes d'invitation (FK vers l'utilisateur, en premier) puis les utilisateurs (ORM, cascade sur track list / news).
- `tests/Command/CreateUserCommandTest.php` et `tests/Controller/InvitationControllerTest.php` : `tearDown()` appelant le trait ; emails désormais `bin2hex(random_bytes(6))` au lieu de `mt_rand(1, 1000)`.
- Purge unique (SQL ciblé) des anciens résidus de la base de test : 14 utilisateurs « Tester »/« userInvit » et 14 codes `@invitation.com` (+ leurs track list / news), pour partir d'une base propre.

## Résultats
Comptages identiques avant et après 3 passages successifs de `make test-only` :
`app_user=3 user_track_list=3 user_news=3 user_invitation_code=0` (autres tables inchangées : manga=54, manga_user_track=9, user_reset_password=1…).
Avant correctif : +2 `app_user`, +2 `user_invitation_code`, +2 `user_track_list`, +2 `user_news` par passage (les ids montraient des trous : l'écart réel dépend aussi des tests des autres agents).
PHPUnit : OK, 193 tests, 546 assertions (référence 192 / 541 ; +1 test ajouté par un autre agent, T4.8 probablement). PHPStan : [OK] No errors. cs-fixer : 1 fichier corrigé (ordre des imports, appliqué), puis propre.

## Écarts, risques, points non vérifiés
- Test instable probable identifié (non reproduit) : `mt_rand(1, 1000)` pour les emails de `CreateUserCommandTest` et `InvitationControllerTest`. Avec les résidus accumulés, une collision donnait « already exists » / violation d'unicité ; c'est très probablement l'erreur isolée vue sur 192 tests. Corrigé par emails aléatoires 48 bits + purge.
- Accès concurrent : d'autres agents utilisaient la base de test pendant les mesures ; les chiffres intermédiaires (17 users) en portent la trace, les comptages finaux sont stables sur 3 passages. Je n'ai pas lancé `make test`.
- Les résidus de tests lancés en parallèle par d'autres agents avant ce correctif ne sont pas distinguables ; la purge ciblait uniquement les noms « Tester »/« userInvit ».
- Un test interrompu (fatal) avant `tearDown` laisserait des lignes ; `make test` les efface.
- Aucun code métier modifié. Fichiers `src/`, `.github/` modifiés par d'autres tâches : non touchés.

## Recommandation pour la suite
Réutiliser `PurgeTestDataTrait` pour tout futur test persistant utilisateurs/invitations ; envisager `dama/doctrine-test-bundle` (rollback transactionnel global) en dette technique.
