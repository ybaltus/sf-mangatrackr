# ADR 0002 : contrôleurs minces, logique dans `Services/`, horloge PSR-20

- Statut : Acceptée
- Date : 2026-10-17
- Commit : `2d3152c`

## Contexte
`ResetPasswordController`, `UserController` et `InvitationController` mélangeaient HTTP,
règles métier et accès à Doctrine (`persist`, `flush`, `getRepository`), avec des
`new \DateTimeImmutable()` impossibles à tester de façon déterministe.

## Décision
- Les contrôleurs ne font que de la coordination HTTP : formulaires, flash, redirections.
- La logique passe dans `src/Services/User/` : `ResetPasswordService`, `InvitationService`,
  `AccountService`, `ScanthequeExportService`.
- Injection par constructeur et autowiring. Les paramètres se lisent avec
  `#[Autowire('%app_name%')]`, plus de `getParameter()`.
- Le temps vient de `Psr\Clock\ClockInterface` (PSR-20, autowirable avec Symfony 6.4), pas de
  `new \DateTimeImmutable()`. En test : `Symfony\Component\Clock\MockClock`.
- Les services sont `final` et leurs dépendances `readonly`.

## Conséquences
- Les règles métier se testent sans base de données ni kernel (doubles + `MockClock`).
- Un service qui désactive un code invalide (`isResetCodeValid`, `isInvitationCodeValid`) a un
  **effet de bord** assumé et documenté : il désactive le code et fait un `flush`.
- Les contrôleurs restent testés en fonctionnel (`WebTestCase`).
