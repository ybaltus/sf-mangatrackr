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
            self::MANGA, 'manga' => 'Manga',
            self::NOVEL, 'novel' => 'Novel',
            self::LIGHTNOVEL, 'Light novel', 'light_novel', 'lighnovel', 'light-novel' => 'Light Novel',
            self::ONESHOT, 'One-Shot', 'one-shot', 'one_shot','oneshot' => 'One Shot',
            self::DOUJINSHI, 'doujinshi', 'doujin' => 'Doujinshi',
            self::MANHWA, 'manhwa' => 'Manhwa',
            self::MANHUA, 'manhua' => 'Manhua',
            default => 'Unknown',
        };
    }
}