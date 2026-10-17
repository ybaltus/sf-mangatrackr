<?php

declare(strict_types=1);

namespace App\Tests\Services\User;

use App\Services\User\ScanthequeExportService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class ScanthequeExportServiceTest extends TestCase
{
    private ScanthequeExportService $service;

    protected function setUp(): void
    {
        $this->service = new ScanthequeExportService(new MockClock('2030-01-10 10:00:00'));
    }

    public function testFilenameContainsTheCurrentDate(): void
    {
        $this->assertSame('mangasync-scantheque-20300110.csv', $this->service->getFilename());
    }

    public function testBuildRowsFlattensTheMangasByStatus(): void
    {
        $json = json_encode([
            'reading' => [
                ['title' => 'Berserk', 'nbChaptersTrack' => 10, 'nbChapters' => 364, 'statusTrack' => 'reading'],
            ],
            'completed' => [
                ['title' => 'Monster', 'nbChaptersTrack' => 162, 'nbChapters' => 162, 'statusTrack' => 'completed'],
                ['title' => 'Pluto', 'nbChaptersTrack' => 65, 'nbChapters' => 65, 'statusTrack' => 'completed'],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->assertSame([
            ['Berserk', 10, 364, 'reading'],
            ['Monster', 162, 162, 'completed'],
            ['Pluto', 65, 65, 'completed'],
        ], $this->service->buildRows($json));
    }

    public function testBuildRowsWithoutDatas(): void
    {
        $this->assertSame([], $this->service->buildRows(null));
        $this->assertSame([], $this->service->buildRows(''));
        $this->assertSame([], $this->service->buildRows('[]'));
        $this->assertSame([], $this->service->buildRows('null'));
    }

    public function testBuildRowsWithAnInvalidJson(): void
    {
        $this->expectException(\JsonException::class);

        $this->service->buildRows('{not a json');
    }
}
