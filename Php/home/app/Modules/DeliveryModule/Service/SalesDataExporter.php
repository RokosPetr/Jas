<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Service;

use App\Core\Exporter\SpreadsheetResponse;
use App\Core\Utils\DateTime;
use App\Service\OrmModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesDataExporter
{
    private OrmModel $orm;

    public function __construct(OrmModel $orm)
    {
        $this->orm = $orm;
    }

    public function exportToExcel(int $year): SpreadsheetResponse
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
        //$this->createProfitSheet($spreadsheet->getActiveSheet(), $year);
        $this->createOverviewSheet($spreadsheet->getActiveSheet(), $year);
        $spreadsheet->setActiveSheetIndex(0);
        return new SpreadsheetResponse($spreadsheet, "Data prodejů - $year", true);
    }

    private function createProfitSheet(Worksheet $worksheet, int $year): void
    {
        $worksheet->setTitle('zisky');
        $rowIndex = 1;
        $stores = [
            4 => 'středisko Ostrava',
            90 => 'velkoobchod celkem',
            91 => 'velkoobchod Ostrava',
            92 => 'velkoobchod Hlučín'
        ];
        $headings = ['Měsíc', 'Prodej', 'Hrubý zisk', '% HZ/prod'];

        foreach ($stores as $storeIndex => $store) {
            $style = $worksheet->getCellByColumnAndRow(1, $rowIndex)->setValue("$year - $store")->getStyle();
            $style->getFont()->setBold(true)->setSize(14);
            $style->getAlignment()->setHorizontal(Alignment::VERTICAL_CENTER);
            $worksheet->mergeCells('A' . $rowIndex . ':D' . $rowIndex);
            $rowIndex++;
            $rowIndex++;
            $worksheet->getStyle('A' . $rowIndex . ':D' . ($rowIndex + 15))
                ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            foreach ($headings as $index => $heading) {
                $style = $worksheet->getCellByColumnAndRow($index + 1, $rowIndex)->setValue($heading)->getStyle();
                $style->getFont()->setBold(true);
                $style->getAlignment()->setHorizontal(Alignment::VERTICAL_CENTER);
            }

            $rowIndex++;
            $rowIndex++;
            $saleSum = 0;
            $profitSum = 0;

            for ($month = 1; $month <= 12; $month++) {
                $salesData = $this->orm->salesData->getBy(['store' => $storeIndex, 'year' => $year, 'month' => $month]);
                $worksheet->getCellByColumnAndRow(1, $rowIndex)->setValue("$month/$year");

                if ($salesData && $salesData->realSale) {
                    $worksheet->getCellByColumnAndRow(2, $rowIndex)->setValue($salesData->realSale);
                    $worksheet->getCellByColumnAndRow(3, $rowIndex)->setValue($salesData->realProfit);
                    $worksheet->getCellByColumnAndRow(4, $rowIndex)->setValue(
                        number_format(($salesData->realProfit / $salesData->realSale) * 100, 2, ',', '') . ' %'
                    )->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $saleSum += $salesData->realSale;
                    $profitSum += $salesData->realProfit;
                }

                $rowIndex++;
            }

            $rowIndex++;
            $worksheet->getCellByColumnAndRow(1, $rowIndex)->setValue('Celkem');
            $worksheet->getCellByColumnAndRow(2, $rowIndex)->setValue($saleSum);
            $worksheet->getCellByColumnAndRow(3, $rowIndex)->setValue($profitSum);

            if ($profitSum) {
                $worksheet->getCellByColumnAndRow(4, $rowIndex)->setValue(
                    number_format(($profitSum / $saleSum) * 100, 2, ',', '') . ' %'
                )->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }

            $rowIndex++;
            $rowIndex++;
            $rowIndex++;
        }

        foreach ($worksheet->getColumnIterator() as $col => $column) {
            if ($column) {
                $worksheet->getColumnDimension($col)->setAutoSize(true);
            }
        }
    }

    private function createOverviewSheet(Worksheet $worksheet, int $year): void
    {
        $worksheet->setTitle('přehled');
        $stores = [
            [90 => 'Ostrava velkoobchod', 91 => 'Skupina 01+31+32', 92 => 'Skupina 02', 910 => 'Koupelnové vybavení'],
            [1 => 'Šumperk', 101 => 'Šumperk OZ', 102 => 'Šumperk OZ 55', 103 => 'Šumperk stavební firmy', 104 => 'Šumperk drobný prodej'],
            [2 => 'Olomouc', 201 => 'Olomouc OZ', 202 => 'Olomouc OZ 55', 203 => 'Olomouc stavební firmy', 204 => 'Olomouc drobný prodej'],
            [3 => 'Otrokovice', 301 => 'Otrokovice OZ', 302 => 'Otrokovice OZ 55', 303 => 'Otrokovice stavební firmy', 304 => 'Otrokovice drobný prodej'],
            [4 => 'Ostrava', 401 => 'Ostrava OZ', 402 => 'Ostrava OZ 55', 403 => 'Ostrava stavební firmy', 404 => 'Ostrava drobný prodej'],
            [5 => 'Prostějov', 501 => 'Prostějov OZ', 502 => 'Prostějov OZ 55', 503 => 'Prostějov stavební firmy', 504 => 'Prostějov drobný prodej'],
            [6 => 'Teplice', 601 => 'Teplice OZ', 602 => 'Teplice OZ 55', 603 => 'Teplice stavební firmy', 604 => 'Teplice drobný prodej'],
            [7 => 'Valašské Meziříčí', 701 => 'Valašské Meziříčí OZ', 702 => 'Valašské Meziříčí OZ 55', 703 => 'Valašské Meziříčí stavební firmy', 704 => 'Valašské Meziříčí drobný prodej'],
            [8 => 'Hradec Králové', 801 => 'Hradec Králové OZ', 802 => 'Hradec Králové OZ 55', 803 => 'Hradec Králové stavební firmy', 804 => 'Hradec Králové drobný prodej'],
            [99 => 'E-shop']
        ];
        $headingsFirstGroup = [
            'Prodej', "Prodej plán", "Prodej skutečnost $year", 'Rozdíl proti plánu', 'Rozdíl proti ' . ($year - 1),
            'Hrubý zisk', "Náklady střediska včetně společných nákladů-plán", "Náklady samotného střediska-plán",
            "Skutečnost $year", 'Rozdíl proti plánu', 'Rozdíl proti ' . ($year - 1), 'Marže'
        ];
        $headings = [
            'Prodej', "Prodej plán", "Prodej skutečnost $year", 'Rozdíl proti plánu', 'Rozdíl proti ' . ($year - 1),
            'Hrubý zisk', "Náklady střediska včetně společných nákladů-plán",
            "Skutečnost $year", 'Rozdíl proti plánu', 'Rozdíl proti ' . ($year - 1)
        ];
        $rowIndex = 1;

        foreach ($stores as $storeGroup) {
            $columnIndex = 1;
            $isFirstGroup = true;
            $storeItem = 1;
            foreach ($storeGroup as $storeIndex => $store) {
                $activeRow = $rowIndex;
                $worksheet->getCellByColumnAndRow($columnIndex, $activeRow)->setValue("$store")
                    ->getStyle()->getFont()->setBold(true)->setSize(14);
                $worksheet->getColumnDimension('A')->setWidth(10);
                $worksheet->mergeCells('A' . $activeRow . ':M' . $activeRow);
                $worksheet->mergeCells('O' . $activeRow . ':Y' . $activeRow);
                $worksheet->mergeCells('AA' . $activeRow . ':AK' . $activeRow);
                $worksheet->mergeCells('AM' . $activeRow . ':AW' . $activeRow);
                $worksheet->mergeCells('AY' . $activeRow . ':BI' . $activeRow);
                $activeRow++;
                $worksheet->getCellByColumnAndRow($columnIndex + 1, $activeRow)->setValue('tržby')
                    ->getStyle()->getAlignment()->setHorizontal(Alignment::VERTICAL_CENTER);
                $worksheet->getCellByColumnAndRow($columnIndex + 6, $activeRow)->setValue('marže')
                    ->getStyle()->getAlignment()->setHorizontal(Alignment::VERTICAL_CENTER);
                $worksheet->mergeCells('B' . $activeRow . ':F' . $activeRow);
                $worksheet->mergeCells('G' . $activeRow . ':M' . $activeRow);
                $worksheet->mergeCells('P' . $activeRow . ':T' . $activeRow);
                $worksheet->mergeCells('U' . $activeRow . ':Y' . $activeRow);
                $worksheet->mergeCells('AB' . $activeRow . ':AF' . $activeRow);
                $worksheet->mergeCells('AG' . $activeRow . ':AK' . $activeRow);
                $worksheet->mergeCells('AN' . $activeRow . ':AR' . $activeRow);
                $worksheet->mergeCells('AS' . $activeRow . ':AW' . $activeRow);
                $worksheet->mergeCells('AZ' . $activeRow . ':BD' . $activeRow);
                $worksheet->mergeCells('BE' . $activeRow . ':BI' . $activeRow);
                $activeRow++;

                $worksheet->getCellByColumnAndRow($columnIndex + 1, $activeRow)->setValue($year - 1);
                $worksheet->getCellByColumnAndRow($columnIndex + 2, $activeRow)->setValue($year);
                $worksheet->getCellByColumnAndRow($columnIndex + 6, $activeRow)->setValue($year - 1);
                $worksheet->getCellByColumnAndRow($columnIndex + 7, $activeRow)->setValue($year);

                $activeRow++;

                $style = $worksheet->getCellByColumnAndRow($columnIndex, $activeRow)->setValue('Měsíc')->getStyle();
                $style->getFont()->setBold(true);

                if ($isFirstGroup){
                    foreach ($headingsFirstGroup as $index => $heading) {
                        $style = $worksheet->getCellByColumnAndRow($columnIndex + 1 + $index, $activeRow)->setValue($heading)->getStyle();
                        $style->getFont()->setBold(true);
                        $style->getAlignment()->setHorizontal(Alignment::VERTICAL_CENTER);
                    }
                }
                else{
                    foreach ($headings as $index => $heading) {
                        $style = $worksheet->getCellByColumnAndRow($columnIndex + 1 + $index, $activeRow)->setValue($heading)->getStyle();
                        $style->getFont()->setBold(true);
                        $style->getAlignment()->setHorizontal(Alignment::VERTICAL_CENTER);
                    }
                }

                $activeRow++;

                foreach (DateTime::CZ_MONTHS as $month => $monthLabel) {
                    $salesData = $this->orm->salesData->getBy(['store' => $storeIndex, 'year' => $year, 'month' => $month]);
                    $worksheet->getCellByColumnAndRow($columnIndex, $activeRow)->setValue($monthLabel);

                    if ($salesData && $salesData->realSale && $store > ''){
                        if ($isFirstGroup){
                            $worksheet->getCellByColumnAndRow($columnIndex + 1, $activeRow)->setValue($salesData->lastSale)->getStyle()->getNumberFormat()->setFormatCode('###,###,###');
                            $worksheet->getCellByColumnAndRow($columnIndex + 2, $activeRow)->setValue($salesData->salePlan)->getStyle()->getNumberFormat()->setFormatCode('###,###,###');
                            $worksheet->getCellByColumnAndRow($columnIndex + 3, $activeRow)->setValue($salesData->realSale)->getStyle()->getNumberFormat()->setFormatCode('###,###,###');
                            $worksheet->getCellByColumnAndRow($columnIndex + 4, $activeRow)->setValue($salesData->salePlanDifference)->getStyle()->getNumberFormat()->setFormatCode('###,###,###');
                            //->getStyle()->getFont()->setColor(new Color($salesData->salePlanDifference < 0 ? Color::COLOR_RED : Color::COLOR_BLACK));
                            $worksheet->getCellByColumnAndRow($columnIndex + 5, $activeRow)->setValue($salesData->lastSaleDifference)->getStyle()->getNumberFormat()->setFormatCode('###,###,###');
                            $worksheet->getCellByColumnAndRow($columnIndex + 6, $activeRow)->setValue($salesData->lastProfit)->getStyle()->getNumberFormat()->setFormatCode('###,###,###');
                            //$worksheet->getCellByColumnAndRow($columnIndex + 7, $activeRow)->setValue($salesData->profitPlan)->getStyle()->getNumberFormat()->setFormatCode('###,###,###')
                            $cell = $worksheet->getCellByColumnAndRow($columnIndex + 7, $activeRow);
                            $cell->setValue($salesData->profitPlan);
                            $cell->getStyle()->getNumberFormat()->setFormatCode('###,###,###');
                            $cell->getStyle()->getFont()->getColor()->setARGB('FFFF0000');
                            $cell->getStyle()->getFont()->setBold(true);
                            //$worksheet->getCellByColumnAndRow($columnIndex + 8, $activeRow)->setValue($salesData->costsPlan)->getStyle()->getNumberFormat()->setFormatCode('###,###,###');
                            $cell = $worksheet->getCellByColumnAndRow($columnIndex + 8, $activeRow);
                            $cell->setValue($salesData->costsPlan);
                            $cell->getStyle()->getNumberFormat()->setFormatCode('###,###,###');
                            $cell->getStyle()->getFont()->getColor()->setARGB('FF00B050');
                            $cell->getStyle()->getFont()->setBold(true);
                            $worksheet->getCellByColumnAndRow($columnIndex + 9, $activeRow)->setValue($salesData->realProfit)->getStyle()->getNumberFormat()->setFormatCode('###,###,###');
                            $worksheet->getCellByColumnAndRow($columnIndex + 10, $activeRow)->setValue($salesData->profitPlanDifference)->getStyle()->getNumberFormat()->setFormatCode('###,###,###');
                            $worksheet->getCellByColumnAndRow($columnIndex + 11, $activeRow)->setValue($salesData->lastProfitDifference)->getStyle()->getNumberFormat()->setFormatCode('###,###,###');
                            $worksheet->getCellByColumnAndRow($columnIndex + 12, $activeRow)->setValue($salesData->realSale && $salesData->realProfit ? (($salesData->realProfit / $salesData->realSale) * 100) : 0)->getStyle()->getNumberFormat()->setFormatCode('###,###,###');
                        }
                        else{
                            $worksheet->getCellByColumnAndRow($columnIndex + 1, $activeRow)->setValue($salesData->lastSale)->getStyle()->getNumberFormat()->setFormatCode('###,###,###');
                            $worksheet->getCellByColumnAndRow($columnIndex + 2, $activeRow)->setValue($salesData->salePlan)->getStyle()->getNumberFormat()->setFormatCode('###,###,###');
                            $worksheet->getCellByColumnAndRow($columnIndex + 3, $activeRow)->setValue($salesData->realSale)->getStyle()->getNumberFormat()->setFormatCode('###,###,###');
                            $worksheet->getCellByColumnAndRow($columnIndex + 4, $activeRow)->setValue($salesData->salePlanDifference)->getStyle()->getNumberFormat()->setFormatCode('###,###,###');
                            //->getStyle()->getFont()->setColor(new Color($salesData->salePlanDifference < 0 ? Color::COLOR_RED : Color::COLOR_BLACK));
                            $worksheet->getCellByColumnAndRow($columnIndex + 5, $activeRow)->setValue($salesData->lastSaleDifference)->getStyle()->getNumberFormat()->setFormatCode('###,###,###');
                            //->getStyle()->getFont()->setColor(new Color($salesData->lastSaleDifference < 0 ? Color::COLOR_RED : Color::COLOR_BLACK));
                            $worksheet->getCellByColumnAndRow($columnIndex + 6, $activeRow)->setValue($salesData->lastProfit)->getStyle()->getNumberFormat()->setFormatCode('###,###,###');
                            $worksheet->getCellByColumnAndRow($columnIndex + 7, $activeRow)->setValue($salesData->profitPlan)->getStyle()->getNumberFormat()->setFormatCode('###,###,###');
                            $worksheet->getCellByColumnAndRow($columnIndex + 8, $activeRow)->setValue($salesData->realProfit)->getStyle()->getNumberFormat()->setFormatCode('###,###,###');
                            $worksheet->getCellByColumnAndRow($columnIndex + 9, $activeRow)->setValue($salesData->profitPlanDifference)->getStyle()->getNumberFormat()->setFormatCode('###,###,###');
                            //->getStyle()->getFont()->setColor(new Color($salesData->profitPlanDifference < 0 ? Color::COLOR_RED : Color::COLOR_BLACK));
                            $worksheet->getCellByColumnAndRow($columnIndex + 10, $activeRow)->setValue($salesData->lastProfitDifference)->getStyle()->getNumberFormat()->setFormatCode('###,###,###');
                            //->getStyle()->getFont()->setColor(new Color($salesData->lastProfitDifference < 0 ? Color::COLOR_RED : Color::COLOR_BLACK));
                        }
                    }

                    $activeRow++;
                }

                $activeRow++;
                $worksheet->getCellByColumnAndRow($columnIndex, $activeRow)->setValue('Celkem')
                    ->getStyle()->getFont()->setBold(true);

                for ($i = 1; $i <= ($isFirstGroup ? 11 : 10); $i++) {
                    $startCell = $worksheet->getCellByColumnAndRow($columnIndex + $i, $activeRow - 13)->getCoordinate();
                    $endCell = $worksheet->getCellByColumnAndRow($columnIndex + $i, $activeRow - 2)->getCoordinate();
                    $cell = $worksheet->getCellByColumnAndRow($columnIndex + $i, $activeRow)->setValue("=SUM($startCell:$endCell)");
                    $cell->getStyle()->getFont()->setBold(true);
                    $cell->getStyle()->getNumberFormat()->setFormatCode('###,###,###');
                }

                // Styles
                $cells =$worksheet->getStyle(
                    $worksheet->getCellByColumnAndRow($columnIndex, $activeRow - 15)->getCoordinate()
                    . ':'
                    . $worksheet->getCellByColumnAndRow($columnIndex + ($isFirstGroup ? 12 : 10), $activeRow)->getCoordinate()
                );
                $cells->getBorders()->getInside()->setBorderStyle(Border::BORDER_THIN);

                $cells = $worksheet->getStyle(
                    $worksheet->getCellByColumnAndRow($columnIndex + 1, $activeRow - 15)->getCoordinate()
                    . ':'
                    . $worksheet->getCellByColumnAndRow($columnIndex + ($isFirstGroup ? 12 : 10), $activeRow)->getCoordinate()
                );
                $cells->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $cells->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);

                $cells = $worksheet->getStyle(
                    $worksheet->getCellByColumnAndRow($columnIndex, $activeRow - 16)->getCoordinate()
                    . ':'
                    . $worksheet->getCellByColumnAndRow($columnIndex + ($isFirstGroup ? 12 : 10), $activeRow)->getCoordinate()
                );
                $cells->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);

                $cells = $worksheet->getStyle(
                    $worksheet->getCellByColumnAndRow($columnIndex + 1, $activeRow - 16)->getCoordinate()
                    . ':'
                    . $worksheet->getCellByColumnAndRow($columnIndex + 5, $activeRow)->getCoordinate()
                );
                $cells->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);
                $worksheet->getStyle(
                    $worksheet->getCellByColumnAndRow($columnIndex + 2, $activeRow - 13)->getCoordinate()
                    . ':'
                    . $worksheet->getCellByColumnAndRow($columnIndex + 2, $activeRow)->getCoordinate()
                )->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('d9d9d9d9');
                $worksheet->getStyle(
                    $worksheet->getCellByColumnAndRow($columnIndex + 7, $activeRow - 13)->getCoordinate()
                    . ':'
                    . $worksheet->getCellByColumnAndRow($columnIndex + 7, $activeRow)->getCoordinate()
                )->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('d9d9d9d9');

                if ($isFirstGroup){
                    $worksheet->getStyle(
                        $worksheet->getCellByColumnAndRow($columnIndex + 8, $activeRow - 13)->getCoordinate()
                        . ':'
                        . $worksheet->getCellByColumnAndRow($columnIndex + 8, $activeRow)->getCoordinate()
                    )->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('d9d9d9d9');
                }

                $columnIndex = $columnIndex + ($isFirstGroup ? 14 : 12);
                $isFirstGroup = false;
                $storeItem++;
            }

            $rowIndex = $rowIndex + 19;
        }

        foreach ($worksheet->getColumnIterator() as $col => $column) {
            if ($column) {
                $worksheet->getColumnDimension($col)->setAutoSize(true);
            }
        }
    }
}
