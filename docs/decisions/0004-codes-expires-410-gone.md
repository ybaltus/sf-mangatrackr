# ADR 0004 : code expiré ou déjà utilisé : 410 Gone

- Statut : Acceptée
- Date : 2026-10-17
- Commit : `2d3152c`

## Contexte
Un code de réinitialisation de mot de passe ou d'invitation expiré (ou déjà utilisé) levait un
`\Exception` générique, donc une erreur 500, alors qu'il s'agit d'une situation normale côté
utilisateur.

## Décision
Lever `Symfony\Component\HttpKernel\Exception\GoneHttpException` (HTTP 410). Un code **inconnu**
reste en 404 (résolu par le `ParamConverter` de Doctrine).

## Conséquences
- Des tests fonctionnels couvrent le cas (`ResetPasswordControllerTest`).
- Aucune page d'erreur dédiée n'existe pour le 410 : la page d'erreur Symfony par défaut
  s'affiche. À améliorer si besoin (`templates/bundles/TwigBundle/Exception/error410.html.twig`).
