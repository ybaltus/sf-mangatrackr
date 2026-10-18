<?php

declare(strict_types=1);

namespace App\Services\Common;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Valide le terme saisi dans la recherche de manga.
 *
 * Autorisé : lettres Unicode (accentuées incluses), marques combinantes,
 * chiffres, espace normal (U+0020) uniquement, « - _ : . , ! ? ( ) ». Rejeté : tout le reste
 * (tabulation, retours à la ligne, autres espaces Unicode, caractères de contrôle,
 * « % \ ' " < > & / ; » etc.), longueur hors 3..50.
 */
final class SearchTermValidator
{
    public const PATTERN = '/^[\p{L}\p{M}\p{N} \-_:.,!?()]*$/u';

    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function validate(string $searchTerm): ConstraintViolationListInterface
    {
        return $this->validator->validate($searchTerm, [
            new Assert\Length(['min' => 3, 'max' => 50]),
            new Assert\NotBlank(),
            new Assert\Regex(self::PATTERN),
            new Assert\Regex('/\S/u'),
        ]);
    }
}
