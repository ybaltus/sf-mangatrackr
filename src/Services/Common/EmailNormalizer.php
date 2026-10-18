<?php

declare(strict_types=1);

namespace App\Services\Common;

/**
 * Single rule used to store and compare emails.
 *
 * PostgreSQL compares strings case-sensitively: emails are therefore stored in
 * lower case and looked up with a case-insensitive comparison.
 */
final class EmailNormalizer
{
    public static function normalize(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
