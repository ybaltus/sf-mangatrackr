<?php

namespace App\Entity\Enum;

enum MangaCategoryEnum: string
{
    case MANGA = 'Manga';
    case NOVEL = 'Novel';
    case LIGHTNOVEL = 'Light Novel';
    case ONESHOT = 'One-shot';
    case DOUJINSHI = 'Doujinshi';
    case MANHWA = 'Manhwa';
    case MANHUA = 'Manhua';
    case UNKNOWN = 'Unknown';

    public function getCategory(): string
    {
        return match ($this) {
            self::MANGA => 'Manga',
            self::NOVEL => 'Novel',
            self::LIGHTNOVEL => 'Light Novel',
            self::ONESHOT => 'One Shot',
            self::DOUJINSHI => 'Doujinshi',
            self::MANHWA => 'Manhwa',
            self::MANHUA => 'Manhua',
            default => 'Unknown',
        };
    }
}
