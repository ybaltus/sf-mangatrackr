<?php

declare(strict_types=1);

namespace App\Services\User;

use Psr\Clock\ClockInterface;

final class ScanthequeExportService
{
    public const COLUMN_NAMES = ['Title', 'LastChapter', 'MaxChapter', 'Status'];

    public function __construct(
        private readonly ClockInterface $clock,
    ) {
    }

    public function getFilename(): string
    {
        return sprintf('mangasync-scantheque-%s.csv', $this->clock->now()->format('Ymd'));
    }

    /**
     * Convert the scantheque datas (json grouped by status) into rows for the csv file.
     *
     * @return array<int, array<int, mixed>>
     *
     * @throws \JsonException if the datas are not a valid json
     */
    public function buildRows(?string $scanthequeDatasJson): array
    {
        // A user without any manga has no datas yet
        if (null === $scanthequeDatasJson || '' === trim($scanthequeDatasJson)) {
            return [];
        }

        /** @var array<mixed> $mangasByStatusList */
        $mangasByStatusList = json_decode($scanthequeDatasJson, true, 512, JSON_THROW_ON_ERROR) ?? [];

        $rows = [];
        foreach ($mangasByStatusList as $mangasByStatus) {
            foreach ($mangasByStatus as $manga) {
                $rows[] = [
                    $manga['title'],
                    $manga['nbChaptersTrack'],
                    $manga['nbChapters'],
                    $manga['statusTrack'],
                ];
            }
        }

        return $rows;
    }
}
