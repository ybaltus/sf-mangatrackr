<?php

declare(strict_types=1);

namespace App\Services\Common;

/**
 * Single rule used to store and compare emails.
 *
 * MariaDB compares emails case-insensitively (utf8mb4_unicode_ci) while
 * PostgreSQL does not: emails are therefore stored in lower case and looked up
 * with a case-insensitive comparison, the same way on both engines.
 */
final class EmailNormalizer
{
    public static function normalize(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
