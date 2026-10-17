<?php

declare(strict_types=1);

namespace App\Tests\Services\Common;

use App\Services\Common\SpreadSheetService;
use PHPUnit\Framework\TestCase;

final class SpreadSheetServiceTest extends TestCase
{
    public function testCreateSimpleSpreadSheetPutsTheColumnNamesFirst(): void
    {
        $spreadSheet = (new SpreadSheetService())->createSimpleSpreadSheet(
            ['Title', 'Chapter'],
            [['Berserk', 364], ['Monster', 162]]
        );

        $this->assertSame([
            ['Title', 'Chapter'],
            ['Berserk', 364],
            ['Monster', 162],
        ], $spreadSheet->getActiveSheet()->toArray(null, true, false));
    }

    public function testCreateCsvWriterUsesTheGivenFormat(): void
    {
        $service = new SpreadSheetService();
        $spreadSheet = $service->createSimpleSpreadSheet(['Title', 'Chapter'], [['Berserk', 364]]);

        $file = tempnam(sys_get_temp_dir(), 'csv');
        $service->createCsvWriter($spreadSheet, ',', "'", "\r\n")->save($file);
        $content = (string) file_get_contents($file);
        unlink($file);

        $this->assertSame("'Title','Chapter'\r\n'Berserk','364'\r\n", $content);
    }
}
