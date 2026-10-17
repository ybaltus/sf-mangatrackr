# ADR 0003 : journaliser les erreurs, ne pas exposer les exceptions

- Statut : Acceptée
- Date : 2026-10-17
- Commit : `74d5650`

## Contexte
Plusieurs `catch (\Exception)` renvoyaient `false` ou le message brut de l'exception au client
sans rien journaliser (`ScanthequeService`, `ScanthequeController`, `MailService`,
`InitDataCommand`, `UserController`). Le message brut peut révéler des détails internes (SQL,
chemins).

## Décision
- `Psr\Log\LoggerInterface` (PSR-3) est injecté et chaque `catch` fait
  `$this->logger->error('Message explicite.', ['exception' => $e])`.
- Le client reçoit un message **générique** (`'error'`, `'Delete failed'`). Le front du projet
  ne lit que `result[0] === 'success'` ; le reste n'est qu'un `console.error`.
- En ligne de commande (`InitDataCommand`), le message reste affiché à l'opérateur et est aussi
  journalisé.

## Conséquences
- Les erreurs sont visibles dans `var/log` (et `make sf-log`).
- Un `catch (\Exception)` n'attrape pas les `\Error` (dont `TypeError`) : voir l'ADR 0001.
