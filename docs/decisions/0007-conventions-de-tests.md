# ADR 0007 : conventions de tests

- Statut : Acceptée
- Date : 2026-10-17
- Commits : `2d3152c`, `d846763`

## Décision
- **Unitaire** (`TestCase`) pour les services : doubles PHPUnit pour `EntityManagerInterface`,
  `MailerInterface`, hasher, etc. ; `MockHttpClient` pour les API ; `MockClock` pour le temps.
  Pour une méthode magique de repository (`findOneByTitleSlug`, `findByUser`), utiliser
  `getMockBuilder(...)->addMethods([...])`.
- **Fonctionnel** (`WebTestCase`) pour les routes. Les données partagées des fixtures ne sont
  pas modifiées : un test qui a besoin de créer ou de supprimer crée **son propre utilisateur**
  (identifiant unique) et nettoie en fin de test.
- Les tests doivent être **rejouables** avec `make test-only` (sans réinitialiser la base) :
  pas de donnée résiduelle, pas d'e-mail ou de nom d'utilisateur fixe qui entrerait en conflit
  à la deuxième exécution.
- Exécution : `make test` (réinitialise la base) ou `make test-only`. Hors Makefile, préfixer
  par `APP_ENV=test`.
- Les valeurs qui viennent du front sont testées **sous forme de chaînes** (voir l'ADR 0001).

## Conséquences
- Plusieurs tests de commandes appellent encore les vraies API (réseau requis) ; voir la dette
  technique.
