<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Service;

use App\Core\Exporter\SpreadsheetResponse;
use App\Modules\StockModule\Orm\Stands\Stand;
use App\Modules\StockModule\Orm\Stands\StandPlate;
use App\Modules\StockModule\Orm\Stands\StandPlateItem;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class StandExporter
{
    public function toExcel(Stand $stand): SpreadsheetResponse
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->setCellValue('B1', $stand->title)->getStyle('B1')->getFont()
            ->setBold(true)->setSize(14);
        $dimensions = "Šířka $stand->width cm/Hloubka $stand->depth cm/Výška $stand->height cm";
        $worksheet->setCellValue('B2', $dimensions);
        $rowIndex = 4;

        /** @var StandPlate $plate */
        foreach ($stand->loadPlates() as $plate) {
            if ($stand->hasPlates) {
                $worksheet->getCellByColumnAndRow(1, $rowIndex)->setValue($plate->order)
                    ->getStyle()->getFont()->setBold(true)->setSize(12);
                $worksheet->getCellByColumnAndRow(2, $rowIndex++)->setValue($plate->description)
                    ->getStyle()->getFont()->setBold(true)->setSize(12);
            }

            /** @var StandPlateItem $item */
            foreach ($plate->loadItems() as $item) {
                $worksheet->getCellByColumnAndRow(1, $rowIndex)->setValue($item->order);
                $worksheet->getCellByColumnAndRow(2, $rowIndex)->setValue($item->item->regNumber);
                $worksheet->getCellByColumnAndRow(3, $rowIndex)->setValue($item->item->name);
                $worksheet->getCellByColumnAndRow(4, $rowIndex++)->setValue($item->item->price)
                    ->getStyle()
                    ->getNumberFormat()
                    ->setFormatCode('#,##0_-"Kč"');
            }

            if ($stand->hasPlates) {
                $rowIndex++;
            }
        }

        $worksheet->getColumnDimension('C')->setWidth(50);

        return new SpreadsheetResponse($spreadsheet, "$stand->title", true);
    }
}