<?php

declare(strict_types=1);

namespace App\Services\FillBin;

use PhpOffice\PhpSpreadsheet\IOFactory;

final class XlsBinProcessorService implements BinFromFileProcessorInterface
{
    public function getCardNumbers(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $cardNumbers = [];

        foreach ($sheet->getColumnIterator('B') as $column) {
            $columnIndex = $column->getColumnIndex(); // 'B'

            foreach ($sheet->getRowIterator() as $row) {
                $cell = $sheet->getCell($columnIndex . $row->getRowIndex());
                $value = $cell->getValue();

                $cardNumber = $value;
                $bin = substr(
                    str_replace(' ', '', $value),
                    0,
                    6
                );

                $cardNumbers[] = [
                    'bin' => $bin,
                    'cardNumber' => $cardNumber,
                ];
            }
        }

        return $cardNumbers;
    }

    public function setBins(string $filePath, array $cardToBinMap): void
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($sheet->getRowIterator() as $row) {
            // search for a card number in row
            if (isset($cardToBinMap[$row->getRowIndex()])) {
                // set bin here
            }
        }
    }
}
