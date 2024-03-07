<?php

namespace App\Services\Common;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

final class SpreadSheetService
{
    /**
     * @param string[]     $columNames
     * @param array<mixed> $columValues
     */
    public function createSimpleSpreadSheet(array $columNames, array $columValues): Spreadsheet
    {
        $spreadSheet = new Spreadsheet();
        $workSheet = $spreadSheet->getActiveSheet();
        $csvCells = [
            $columNames,
            ...$columValues,
        ];
        $workSheet->fromArray($csvCells);

        return $spreadSheet;
    }

    public function createCsvWriter(
        Spreadsheet $spreadSheet,
        string $delimiter = ';',
        string $enclosure = '"',
        string $lineEnding = "\n",
        int $sheetIndex = 0,
    ): Csv {
        $writer = new Csv($spreadSheet);
        $writer->setDelimiter($delimiter);
        $writer->setEnclosure($enclosure);
        $writer->setLineEnding($lineEnding);
        $writer->setSheetIndex($sheetIndex);

        return $writer;
    }
}
