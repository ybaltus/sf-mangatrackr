<?php

namespace App\Services\Trait;

use App\Entity\Enum\LangEnum;

trait SampleDataTrait
{
    /**
     * @return array<string>
     */
    public function getMangaTypes(): array
    {
        return [
            'Action',
            'Adventure',
            'Avant Garde',
            'Award Winning',
            'Boys Love',
            'Comedy',
            'Drama',
            'Fantasy',
            'Girls Love',
            'Gourmet',
            'Horror',
            'Mystery',
            'Romance',
            'Sci-Fi',
            'Slice of Life',
            'Sports',
            'Supernatural',
            'Suspense',
            'Ecchi',
            'Erotica',
            'Hentai',
            'Adult Cast',
            'Anthropomorphic',
            'CGDCT',
            'Childcare',
            'Combat Sports',
            'Crossdressing',
            'Delinquents',
            'Detective',
            'Educational',
            'Gag Humor',
            'Gore',
            'Harem',
            'High Stakes Game',
            'Historical',
            'Idols (Female)',
            'Idols (Male)',
            'Isekai',
            'Iyashikei',
            'Love Polygon',
            'Magical Sex Shift',
            'Mahou Shoujo',
            'Martial Arts',
            'Mecha',
            'Medical',
            'Memoir',
            'Military',
            'Music',
            'Mythology',
            'Organized Crime',
            'Otaku Culture',
            'Parody',
            'Performing Arts',
            'Pets',
            'Psychological',
            'Racing',
            'Reincarnation',
            'Reverse Harem',
            'Romantic Subtext',
            'Samurai',
            'School',
            'Showbiz',
            'Space',
            'Strategy Game',
            'Super Power',
            'Survival',
            'Team Sports',
            'Time Travel',
            'Vampire',
            'Video Game',
            'Villainess',
            'Visual Arts',
            'Workplace',
            'Josei',
            'Kids',
            'Seinen',
            'Shoujo',
            'Shounen',
        ];
    }

    /**
     * @return array<string>
     */
    public function getMangaStatus(): array
    {
        return [
            'Publishing',
            'Complete',
            'On Hiatus',
            'Discontinued',
            'Upcoming',
            'Finished',
            'Not yet published',
        ];
    }

    /**
     * @return array<mixed>
     */
    public function getFantrad(): array
    {
        return [
            ['FMTeam', 'https://www.fmteam.fr/', LangEnum::FR],
            ['Anime Sama', 'https://anime-sama.fr/', LangEnum::FR],
            ['Webtoons', 'https://www.webtoons.com/fr/', LangEnum::FR],
            ['Manga-Scantrad-io', 'https://manga-scantrad.io/', LangEnum::FR],
            ['Astral-Manga', 'https://astral-manga.fr/', LangEnum::FR],
            ['Bento-Manga', 'https://bentomanga.com/', LangEnum::EN],
            ['Scantrad-VF', 'https://scantrad-vf.me/', LangEnum::FR],
            ['Scan-VF', 'https://www.scan-vf.net/', LangEnum::FR],
            ['Manga-Scantrad-fr', 'https://www.mangascantrad.fr/', LangEnum::FR],
            ['Legacy-Scans', 'https://legacy-scans.com/', LangEnum::FR],
            ['Scan-Manga', 'https://www.scan-manga.com/?home', LangEnum::FR],
        ];
    }

    /**
     * @return array<string>
     */
    public function getStatusTrack(): array
    {
        return [
            'Play',
            'Pause',
            'Completed',
            'Abandoned',
            'Archived',
            'Not Started',
        ];
    }
}
