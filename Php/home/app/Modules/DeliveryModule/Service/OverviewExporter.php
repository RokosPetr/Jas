<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Service;

use App\Core\Exporter\SpreadsheetResponse;
use App\Core\Utils\DateTime;
use App\Modules\DeliveryModule\Component\TakingsOverview;
use App\Modules\StockModule\Orm\Producers\Producer;
use App\Modules\StockModule\Orm\StockItems\StockVariant;
use App\Modules\SystemModule\Orm\Stores\Store;
use App\Service\OrmModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class OverviewExporter
{
    private OrmModel $orm;

    public function __construct(OrmModel $orm)
    {
        $this->orm = $orm;
    }

    public function totalTakingsToExcel(string $heading, array $producers, array $takings): SpreadsheetResponse
    {
        $columnCount = count($producers) + 2;
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
        $worksheet = $spreadsheet->getActiveSheet()->setTitle(str_replace('/', '-', $heading));
        $worksheet->setCellValue('A1', $heading)->getStyle('A1')->getFont()
            ->setBold(true)->setSize(14);
        $worksheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $worksheet->mergeCells('A1:' . $worksheet->getCellByColumnAndRow($columnCount, 1)->getCoordinate());
        $cell = $worksheet->getCellByColumnAndRow(1, 2)->setValue('Rok');
        $cell->getStyle()->getFont()->setBold(true);
        $cell->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $columnIndex = 2;

        foreach ($producers as $label) {
            $cell = $worksheet->getCellByColumnAndRow($columnIndex++, 2)->setValue($label);
            $cell->getStyle()->getFont()->setBold(true);
            $cell->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $cell = $worksheet->getCellByColumnAndRow($columnCount, 2)->setValue('Celkem');
        $cell->getStyle()->getFont()->setBold(true);
        $cell->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row = 3;

        foreach ($takings as $year => $data) {
            $columnIndex = 2;
            $worksheet->getCellByColumnAndRow(1, $row)->setValue($year)
                ->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            foreach (array_keys($producers) as $producerId) {
                $worksheet->getCellByColumnAndRow($columnIndex++, $row)->setValue($data[$producerId] ?? 0);
            }

            if ($producers) {
                $col = $worksheet->getCellByColumnAndRow($columnCount - 1, $row)->getColumn();
                $sumArea = 'B' . $row . ':' . $col . $row;
                $worksheet->getCellByColumnAndRow($columnCount, $row)->setValue("=SUM($sumArea)");
            }

            $row++;
        }

        foreach ($worksheet->getColumnIterator() as $col => $column) {
            if ($column) {
                $worksheet->getColumnDimension($col)->setWidth(15);
            }
        }

        return new SpreadsheetResponse($spreadsheet, "Obklady a dlažby - $heading", true);
    }

    public function takingsToExcel(int $year, array $producers, array $takings, ?array $lastTakings): SpreadsheetResponse
    {
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('n');
        $columnCount = count($producers) + 2;

        if ($lastTakings) {
            $columnCount = 2 * $columnCount - 1;
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

        foreach ([TakingsOverview::VIEW_BY_PRICE, TakingsOverview::VIEW_BY_UNIT] as $dataType) {
            $data = $takings[$dataType];
            $worksheet = $dataType === TakingsOverview::VIEW_BY_PRICE
                ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
            $worksheet->setTitle($dataType === TakingsOverview::VIEW_BY_PRICE ? 'Kč-rok' : 'm2-rok');
            $heading = $dataType === TakingsOverview::VIEW_BY_PRICE ? "Kč / rok $year" : "m2 / rok $year";

            if ($lastTakings) {
                $heading .= ' s rozdílem oproti minulému roku';
            }

            $worksheet->setCellValue('A1', $heading)->getStyle('A1')->getFont()
                ->setBold(true)->setSize(14);
            $worksheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $worksheet->mergeCells('A1:' . $worksheet->getCellByColumnAndRow($columnCount, 1)->getCoordinate());

            $columnIndex = 2;

            foreach ($producers as $label) {
                $cell = $worksheet->getCellByColumnAndRow($columnIndex++, 2)->setValue($label);
                $cell->getStyle()->getFont()->setBold(true);
                $cell->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                if ($lastTakings) {
                    $worksheet->mergeCells($cell->getCoordinate() . ':' . $worksheet->getCellByColumnAndRow($columnIndex++, 2)->getCoordinate());
                }
            }

            $cell = $worksheet->getCellByColumnAndRow($columnIndex, 2)->setValue('Celkem');
            $cell->getStyle()->getFont()->setBold(true);
            $cell->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($lastTakings) {
                $worksheet->mergeCells($cell->getCoordinate() . ':' . $worksheet->getCellByColumnAndRow(++$columnIndex, 2)->getCoordinate());
            }

            $worksheet->setCellValue('A3', 'Rok')
                ->getStyle('A3')->getBorders()->getBottom()->setBorderStyle(true);

            for ($i = 2; $i <= $columnCount; $i++) {
                $cellValue = $lastTakings ? ($i % 2 ? ('srovnání s ' . ($year - 1)) : $year) : $year;
                $cell = $worksheet->getCellByColumnAndRow($i, 3)->setValue($cellValue);
                $cell->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $cell->getStyle()->getBorders()->getBottom()->setBorderStyle(true);
            }

            $row = 4;

            foreach (DateTime::CZ_MONTHS as $index => $month) {
                $pastMonth = $year < $currentYear || ($year === $currentYear && $index < $currentMonth);
                $columnIndex = 2;
                $worksheet->getCellByColumnAndRow(1, $row)->setValue(ucfirst($month))
                    ->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                foreach (array_keys($producers) as $producerId) {
                    $monthTakings = $data[$index][$producerId] ?? 0;
                    $worksheet->getCellByColumnAndRow($columnIndex++, $row)->setValue($monthTakings);

                    if ($lastTakings) {
                        $diffTakings = $pastMonth ? ($monthTakings - ($lastTakings[$dataType][$index][$producerId] ?? 0)) : 0;

                        if ($diffTakings) {
                            $worksheet->getCellByColumnAndRow($columnIndex, $row)->setValue($diffTakings);
                        }

                        $columnIndex++;
                    }
                }

                if ($lastTakings) {
                    $sumArea = '';
                    $diffArea = '';
                    $currentColIndex = 2;

                    foreach ($worksheet->getColumnIterator('B', $worksheet->getCellByColumnAndRow($columnIndex - 1, $row)->getColumn()) as $col => $column) {
                        $cellIndex = $col . $row;

                        if ($currentColIndex % 2) {
                            $diffArea .= ($diffArea ? ('+' . $cellIndex) : $cellIndex);
                        } else {
                            $sumArea .= ($sumArea ? ('+' . $cellIndex) : $cellIndex);
                        }

                        $currentColIndex++;
                    }

                    $worksheet->getCellByColumnAndRow($columnIndex, $row)->setValue("=$sumArea");

                    if ($pastMonth) {
                        $worksheet->getCellByColumnAndRow(++$columnIndex, $row)->setValue("=$diffArea");
                    }
                } else {
                    $col = $worksheet->getCellByColumnAndRow($columnCount - 1, $row)->getColumn();
                    $sumArea = 'B' . $row . ':' . $col . $row;
                    $worksheet->getCellByColumnAndRow($columnCount, $row)->setValue("=SUM($sumArea)");
                }

                $row++;

                if ($index % 3 === 0) {
                    $cellValue = 'Celkem ' . ($index - 2) . '-' . $index;
                    $worksheet->getCellByColumnAndRow(1, $row)->setValue($cellValue)
                        ->getStyle()->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);

                    for ($i = 2; $i <= $columnCount; $i++) {
                        $cell = $worksheet->getCellByColumnAndRow($i, $row);
                        $colIndex = $cell->getColumn();
                        $sumArea = $colIndex . ($row - 3) . ':' . $colIndex . ($row - 1);
                        $cell->setValue("=SUM($sumArea)")
                            ->getStyle()->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
                    }

                    $row++;
                    $row++;
                }
            }

            $worksheet->getCellByColumnAndRow(1, $row)->setValue('Celkem 1-12');

            for ($i = 2; $i <= $columnCount; $i++) {
                $cell = $worksheet->getCellByColumnAndRow($i, $row);
                $colIndex = $cell->getColumn();
                $sumArea = '';

                for ($j = 0; $j < 4; $j++) {
                    $startIndex = (5 * $j) + 4;

                    if (!$sumArea) {
                        $sumArea = $colIndex . $startIndex;
                    } else {
                        $sumArea .= '+' . $colIndex . $startIndex;
                    }

                    $sumArea .= '+' . $colIndex . ($startIndex + 1);
                    $sumArea .= '+' . $colIndex . ($startIndex + 2);
                }

                $cell->setValue("=$sumArea");
            }

            $totalSumCoordinate = $worksheet->getCellByColumnAndRow($lastTakings ? $columnCount - 1 : $columnCount, $row)
                ->getCoordinate();

            if ($lastTakings) {
                $lastTotalDiffCoordinate = $worksheet->getCellByColumnAndRow($columnCount, $row)->getCoordinate();
            }

            $row++;
            $worksheet->getCellByColumnAndRow(1, $row)->setValue('Tržní podíl');

            for ($i = 2; $i <= $columnCount; $i++) {
                $cellColumn = $worksheet->getCellByColumnAndRow($i, $row)->getColumn();
                $sumFunction = null;

                if (!$lastTakings || $i % 2 === 0) {
                    $sumFunction = '=' . $cellColumn . ($row - 1) . ' / ' . $totalSumCoordinate;
                } elseif ($i !== $columnCount) {
                    $sumCellCol = $worksheet->getCellByColumnAndRow($i - 1, $row - 1)->getCoordinate();
                    $shareCol = $worksheet->getCellByColumnAndRow($i - 1, $row)->getCoordinate();
                    $sumFunction = "=$shareCol-((" . $sumCellCol . '-' . $cellColumn . ($row - 1) . ')/' . "($totalSumCoordinate-$lastTotalDiffCoordinate)" . ')';
                }

                $worksheet->getCellByColumnAndRow($i, $row)->setValue($sumFunction)
                    ->getStyle()->getNumberFormat()->setFormatCode('0.0 %');
            }

            if ($dataType === TakingsOverview::VIEW_BY_UNIT) {
                $row++;
                $row++;
                $totalSumData = $this->getTotalSumData($year, $takings);

                if ($lastTakings) {
                    $lastTotalSumData = $this->getTotalSumData($year, $lastTakings);
                }

                $worksheet->getCellByColumnAndRow(1, $row)->setValue('Kč/m2');
                $columnIndex = 2;

                foreach (array_keys($producers) as $producerId) {
                    $value = $totalSumData[TakingsOverview::VIEW_BY_PRICE][$producerId] / $totalSumData[TakingsOverview::VIEW_BY_UNIT][$producerId];
                    $worksheet->getCellByColumnAndRow($columnIndex++, $row)->setValue($value)
                        ->getStyle()->getNumberFormat()->setFormatCode('0.0');

                    if ($lastTakings) {
                        $lastValue = $lastTotalSumData[TakingsOverview::VIEW_BY_PRICE][$producerId] / $lastTotalSumData[TakingsOverview::VIEW_BY_UNIT][$producerId];
                        $worksheet->getCellByColumnAndRow($columnIndex++, $row)->setValue($value - $lastValue)
                            ->getStyle()->getNumberFormat()->setFormatCode('0.0');
                    }
                }

                $value = array_sum($totalSumData[TakingsOverview::VIEW_BY_PRICE]) / array_sum($totalSumData[TakingsOverview::VIEW_BY_UNIT]);
                $worksheet->getCellByColumnAndRow($columnIndex++, $row)->setValue($value)
                    ->getStyle()->getNumberFormat()->setFormatCode('0.0');

                if ($lastTakings) {
                    $lastValue = array_sum($lastTotalSumData[TakingsOverview::VIEW_BY_PRICE]) / array_sum($lastTotalSumData[TakingsOverview::VIEW_BY_UNIT]);
                    $worksheet->getCellByColumnAndRow($columnIndex, $row)->setValue($value - $lastValue)
                        ->getStyle()->getNumberFormat()->setFormatCode('0.0');
                }
            }

            foreach ($worksheet->getColumnIterator() as $col => $column) {
                if ($column) {
                    $worksheet->getColumnDimension($col)->setWidth(15);
                }
            }
        }

        $spreadsheet->setActiveSheetIndex(0);

        return new SpreadsheetResponse($spreadsheet, "Obklady a dlažby - $year", true);
    }

    public function totalStoreTakingsToExcel(string $heading, array $producers, array $selectedStores, array $takings): SpreadsheetResponse
    {
        $producerNames = $this->orm->producers->findBy(['id' => $producers])->orderBy('number')
            ->fetchPairs('id', 'name');
        $storeNames = [9 => 'Velkoobchod'] + $this->orm->stores->findAll()->fetchPairs('id', 'name');
        unset($storeNames[Store::HLUCIN_MAIN_STORAGE]);
        $columnCount = count($producers) + 2;
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->setTitle('Suma');
        $worksheet->setCellValue('A1', 'Kč/rok')->getStyle('A1')->getFont()
            ->setBold(true)->setSize(14);
        $worksheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $worksheet->mergeCells('A1:' . $worksheet->getCellByColumnAndRow($columnCount, 1)->getCoordinate());
        $columnIndex = 2;

        foreach ($producers as $producerId) {
            $producerName = $producerNames[$producerId] ?? 'DC Ravak';
            $cell = $worksheet->getCellByColumnAndRow($columnIndex++, 2)->setValue($producerName);
            $cell->getStyle()->getFont()->setBold(true);
            $cell->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $cell = $worksheet->getCellByColumnAndRow($columnCount, 2)->setValue('Celkem');
        $cell->getStyle()->getFont()->setBold(true);
        $cell->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row = 3;

        foreach ($takings as $year => $producerTakings) {
            $columnIndex = 2;
            $worksheet->getCellByColumnAndRow(1, $row)->setValue($year)
                ->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            foreach ($producers as $producerId) {
                $value = array_sum(array_filter($producerTakings[$producerId] ?? [], fn(int $id) => in_array($id, $selectedStores), ARRAY_FILTER_USE_KEY));
                $worksheet->getCellByColumnAndRow($columnIndex++, $row)->setValue($value);
            }

            $col = $worksheet->getCellByColumnAndRow($columnCount - 1, $row)->getColumn();
            $sumArea = 'B' . $row . ':' . $col . $row;
            $worksheet->getCellByColumnAndRow($columnCount, $row)->setValue("=SUM($sumArea)");

            $row++;
        }

        foreach ($worksheet->getColumnIterator() as $col => $column) {
            if ($column) {
                $worksheet->getColumnDimension($col)->setWidth(17);
            }
        }

        if (false) {
            // docasne vypnuto - pry neni potraba, ale kdo vi...
            $columnCount = count($selectedStores) + 2;

            foreach ($producers as $producerId) {
                $producerName = $producerNames[$producerId] ?? 'DC Ravak';
                $worksheet = $spreadsheet->createSheet();
                $worksheet->setTitle($producerName);
                $worksheet->setCellValue('A1', "$producerName/Kč/rok")->getStyle('A1')->getFont()
                    ->setBold(true)->setSize(14);
                $worksheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $worksheet->mergeCells('A1:' . $worksheet->getCellByColumnAndRow($columnCount, 1)->getCoordinate());
                $cell = $worksheet->getCellByColumnAndRow(1, 2)->setValue($producerName);
                $cell->getStyle()->getFont()->setBold(true);
                $cell->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $columnIndex = 2;

                foreach ($selectedStores as $storeId) {
                    $cell = $worksheet->getCellByColumnAndRow($columnIndex++, 2)->setValue($storeNames[$storeId]);
                    $cell->getStyle()->getFont()->setBold(true);
                    $cell->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                $cell = $worksheet->getCellByColumnAndRow($columnCount, 2)->setValue('Celkem');
                $cell->getStyle()->getFont()->setBold(true);
                $cell->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $row = 3;

                foreach ($takings as $year => $data) {
                    $columnIndex = 2;
                    $worksheet->getCellByColumnAndRow(1, $row)->setValue($year)
                        ->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                    foreach ($selectedStores as $storeId) {
                        $worksheet->getCellByColumnAndRow($columnIndex++, $row)->setValue($data[$producerId][$storeId] ?? 0);
                    }

                    $col = $worksheet->getCellByColumnAndRow($columnCount - 1, $row)->getColumn();
                    $sumArea = 'B' . $row . ':' . $col . $row;
                    $worksheet->getCellByColumnAndRow($columnCount, $row)->setValue("=SUM($sumArea)");

                    $row++;
                }

                foreach ($worksheet->getColumnIterator() as $col => $column) {
                    if ($column) {
                        $worksheet->getColumnDimension($col)->setWidth(17);
                    }
                }
            }
        }

        $spreadsheet->setActiveSheetIndex(0);

        return new SpreadsheetResponse($spreadsheet, $heading, true);
    }

    public function storeTakingsToExcel(string $heading, int $year, array $producers, array $selectedStores, array $takings, ?array $lastTakings): SpreadsheetResponse
    {
        $storesProducers = $this->orm->producers->findBy(['id' => array_keys($takings['sum'])])->orderBy('number')
            ->fetchPairs('id', 'name');
        $producerNames = [];

        foreach ($storesProducers as $id => $producer) {
            $producerNames[$id] = $producer;
            // DC Ravak hack
            if ($producer === Producer::RAVAK_NAME) {
                $producerNames[Producer::DC_RAVAK_ID] = 'DC Ravak';
            }
        }

        $storeNames = [9 => 'Velkoobchod'] + $this->orm->stores->findAll()->fetchPairs('id', 'name');
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('n');
        $columnCount = count($selectedStores) + 2;
        $totalStoreData = $takings;
        unset($totalStoreData['sum']);
        $totalStoreData = $this->getTotalSumData($year, ['sum' => $totalStoreData], true);

        if ($lastTakings) {
            $lastTotalStoreData = $lastTakings;
            unset($lastTotalStoreData['sum']);
            $lastTotalStoreData = $this->getTotalSumData($year, ['sum' => $lastTotalStoreData], true);
            $columnCount = 2 * $columnCount - 1;
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->setTitle('Suma');
        $worksheet->setCellValue('A1', "Kč / rok $year" . ($lastTakings ? ' s rozdílem oproti minulému roku' : ''))
            ->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $worksheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $worksheet->mergeCells('A1:' . $worksheet->getCellByColumnAndRow($columnCount, 1)->getCoordinate());
        $columnIndex = 2;

        foreach ($selectedStores as $storeId) {
            $cell = $worksheet->getCellByColumnAndRow($columnIndex++, 2)->setValue($storeNames[$storeId]);
            $cell->getStyle()->getFont()->setBold(true);
            $cell->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($lastTakings) {
                $worksheet->mergeCells($cell->getCoordinate() . ':' . $worksheet->getCellByColumnAndRow($columnIndex++, 2)->getCoordinate());
            }
        }

        $cell = $worksheet->getCellByColumnAndRow($columnIndex++, 2)->setValue('Celkem');
        $cell->getStyle()->getFont()->setBold(true);
        $cell->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        if ($lastTakings) {
            $worksheet->mergeCells($cell->getCoordinate() . ':' . $worksheet->getCellByColumnAndRow($columnIndex, 2)->getCoordinate());
        }

        $worksheet->setCellValue('A3', 'Rok')
            ->getStyle('A3')->getBorders()->getBottom()->setBorderStyle(true);

        for ($i = 2; $i <= $columnCount; $i++) {
            $cellValue = $lastTakings ? ($i % 2 ? ('srovnání s ' . ($year - 1)) : $year) : $year;
            $cell = $worksheet->getCellByColumnAndRow($i, 3)->setValue($cellValue);
            $cell->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $cell->getStyle()->getBorders()->getBottom()->setBorderStyle(true);
        }

        $row = 4;

        foreach ($producerNames as $producerId => $label) {
            $worksheet->getCellByColumnAndRow(1, $row)->setValue($label);
            $columnIndex = 2;

            foreach ($selectedStores as $storeId) {
                $storeTakings = $takings['sum'][$producerId][$storeId] ?? 0;
                $worksheet->getCellByColumnAndRow($columnIndex++, $row)->setValue($storeTakings);

                if ($lastTakings) {
                    $diffTakings = ($totalStoreData[$producerId][$storeId] ?? 0) - ($lastTotalStoreData[$producerId][$storeId] ?? 0);

                    if ($diffTakings) {
                        $worksheet->getCellByColumnAndRow($columnIndex, $row)->setValue($diffTakings);
                    }

                    $columnIndex++;
                }
            }

            if ($lastTakings) {
                $sumArea = '';
                $diffArea = '';
                $currentColIndex = 2;

                foreach ($worksheet->getColumnIterator('B', $worksheet->getCellByColumnAndRow($columnIndex - 1, $row)->getColumn()) as $col => $column) {
                    $cellIndex = $col . $row;

                    if ($currentColIndex % 2) {
                        $diffArea .= ($diffArea ? ('+' . $cellIndex) : $cellIndex);
                    } else {
                        $sumArea .= ($sumArea ? ('+' . $cellIndex) : $cellIndex);
                    }

                    $currentColIndex++;
                }

                $worksheet->getCellByColumnAndRow($columnIndex++, $row)->setValue("=$sumArea");
                $worksheet->getCellByColumnAndRow($columnIndex, $row)->setValue("=$diffArea");
            } else {
                $col = $worksheet->getCellByColumnAndRow($columnIndex, $row)->getColumn();
                $sumArea = 'B' . $row . ':' . $col . $row;
                $worksheet->getCellByColumnAndRow($columnIndex, $row)->setValue("=SUM($sumArea)");
            }

            $row++;
        }

        $row++;
        $worksheet->getCellByColumnAndRow(1, $row)->setValue('Celkem');

        for ($i = 2; $i <= $columnCount; $i++) {
            $cell = $worksheet->getCellByColumnAndRow($i, $row);
            $colIndex = $cell->getColumn();
            $sumArea = $colIndex . '4:' . $colIndex . (count($producerNames) + 3);
            $cell->setValue("=SUM($sumArea)");
        }

        $totalSumCoordinate = $worksheet->getCellByColumnAndRow($lastTakings ? $columnCount - 1 : $columnCount, $row)
            ->getCoordinate();

        if ($lastTakings) {
            $lastTotalDiffCoordinate = $worksheet->getCellByColumnAndRow($columnCount, $row)->getCoordinate();
        }

        $row++;
        $worksheet->getCellByColumnAndRow(1, $row)->setValue('Tržní podíl');

        for ($i = 2; $i <= $columnCount; $i++) {
            $cellColumn = $worksheet->getCellByColumnAndRow($i, $row)->getColumn();
            $sumFunction = null;

            if (!$lastTakings || $i % 2 === 0) {
                $sumFunction = '=' . $cellColumn . ($row - 1) . ' / ' . $totalSumCoordinate;
            } elseif ($i !== $columnCount) {
                $sumCellCol = $worksheet->getCellByColumnAndRow($i - 1, $row - 1)->getCoordinate();
                $shareCol = $worksheet->getCellByColumnAndRow($i - 1, $row)->getCoordinate();
                $sumFunction = "=$shareCol-((" . $sumCellCol . '-' . $cellColumn . ($row - 1) . ')/' . "($totalSumCoordinate-$lastTotalDiffCoordinate)" . ')';
            }

            $worksheet->getCellByColumnAndRow($i, $row)->setValue($sumFunction)
                ->getStyle()->getNumberFormat()->setFormatCode('0.0 %');
        }

        foreach ($worksheet->getColumnIterator() as $col => $column) {
            if ($column) {
                $worksheet->getColumnDimension($col)->setWidth(15);
            }
        }

        foreach ($producers as $producerId) {
            $producerName = $producerNames[$producerId] ?? 'DC Ravak';
            $worksheet = $spreadsheet->createSheet();
            $worksheet->setTitle($producerName);
            $worksheet->setCellValue('A1', "$producerName/Kč/rok $year" . ($lastTakings ? ' s rozdílem oproti minulému roku' : ''))
                ->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $worksheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $worksheet->mergeCells('A1:' . $worksheet->getCellByColumnAndRow($columnCount, 1)->getCoordinate());
            $cell = $worksheet->getCellByColumnAndRow(1, 2)->setValue($producerName);
            $cell->getStyle()->getFont()->setBold(true);
            $cell->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $columnIndex = 2;

            foreach ($selectedStores as $storeId) {
                $cell = $worksheet->getCellByColumnAndRow($columnIndex++, 2)->setValue($storeNames[$storeId]);
                $cell->getStyle()->getFont()->setBold(true);
                $cell->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                if ($lastTakings) {
                    $worksheet->mergeCells($cell->getCoordinate() . ':' . $worksheet->getCellByColumnAndRow($columnIndex++, 2)->getCoordinate());
                }
            }

            $cell = $worksheet->getCellByColumnAndRow($columnIndex++, 2)->setValue('Celkem');
            $cell->getStyle()->getFont()->setBold(true);
            $cell->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($lastTakings) {
                $worksheet->mergeCells($cell->getCoordinate() . ':' . $worksheet->getCellByColumnAndRow($columnIndex, 2)->getCoordinate());
            }

            $worksheet->setCellValue('A3', 'Rok')
                ->getStyle('A3')->getBorders()->getBottom()->setBorderStyle(true);

            for ($i = 2; $i <= $columnCount; $i++) {
                $cellValue = $lastTakings ? ($i % 2 ? ('srovnání s ' . ($year - 1)) : $year) : $year;
                $cell = $worksheet->getCellByColumnAndRow($i, 3)->setValue($cellValue);
                $cell->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $cell->getStyle()->getBorders()->getBottom()->setBorderStyle(true);
            }

            $row = 4;

            foreach (DateTime::CZ_MONTHS as $index => $month) {
                $pastMonth = $year < $currentYear || ($year === $currentYear && $index < $currentMonth);
                $columnIndex = 2;
                $worksheet->getCellByColumnAndRow(1, $row)->setValue(ucfirst($month))
                    ->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                foreach ($selectedStores as $storeId) {
                    $monthTakings = $takings[$index][$producerId][$storeId] ?? 0;
                    $worksheet->getCellByColumnAndRow($columnIndex++, $row)->setValue($monthTakings);

                    if ($lastTakings) {
                        $diffTakings = $pastMonth ? ($monthTakings - ($lastTakings[$index][$producerId][$storeId] ?? 0)) : 0;

                        if ($diffTakings) {
                            $worksheet->getCellByColumnAndRow($columnIndex, $row)->setValue($diffTakings);
                        }

                        $columnIndex++;
                    }
                }

                if ($lastTakings) {
                    $sumArea = '';
                    $diffArea = '';
                    $currentColIndex = 2;

                    foreach ($worksheet->getColumnIterator('B', $worksheet->getCellByColumnAndRow($columnIndex - 1, $row)->getColumn()) as $col => $column) {
                        $cellIndex = $col . $row;

                        if ($currentColIndex % 2) {
                            $diffArea .= ($diffArea ? ('+' . $cellIndex) : $cellIndex);
                        } else {
                            $sumArea .= ($sumArea ? ('+' . $cellIndex) : $cellIndex);
                        }

                        $currentColIndex++;
                    }

                    $worksheet->getCellByColumnAndRow($columnIndex, $row)->setValue("=$sumArea");

                    if ($pastMonth) {
                        $worksheet->getCellByColumnAndRow(++$columnIndex, $row)->setValue("=$diffArea");
                    }
                } else {
                    $col = $worksheet->getCellByColumnAndRow($columnCount - 1, $row)->getColumn();
                    $sumArea = 'B' . $row . ':' . $col . $row;
                    $worksheet->getCellByColumnAndRow($columnCount, $row)->setValue("=SUM($sumArea)");
                }

                $row++;

                if ($index % 3 === 0) {
                    $cellValue = 'Celkem ' . ($index - 2) . '-' . $index;
                    $worksheet->getCellByColumnAndRow(1, $row)->setValue($cellValue)
                        ->getStyle()->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);

                    for ($i = 2; $i <= $columnCount; $i++) {
                        $cell = $worksheet->getCellByColumnAndRow($i, $row);
                        $colIndex = $cell->getColumn();
                        $sumArea = $colIndex . ($row - 3) . ':' . $colIndex . ($row - 1);
                        $cell->setValue("=SUM($sumArea)")
                            ->getStyle()->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
                    }

                    $row++;
                    $row++;
                }
            }

            $worksheet->getCellByColumnAndRow(1, $row)->setValue('Celkem 1-12');

            for ($i = 2; $i <= $columnCount; $i++) {
                $cell = $worksheet->getCellByColumnAndRow($i, $row);
                $colIndex = $cell->getColumn();
                $sumArea = '';

                for ($j = 0; $j < 4; $j++) {
                    $startIndex = (5 * $j) + 4;

                    if (!$sumArea) {
                        $sumArea = $colIndex . $startIndex;
                    } else {
                        $sumArea .= '+' . $colIndex . $startIndex;
                    }

                    $sumArea .= '+' . $colIndex . ($startIndex + 1);
                    $sumArea .= '+' . $colIndex . ($startIndex + 2);
                }

                $cell->setValue("=$sumArea");
            }

            $totalSumCoordinate = $worksheet->getCellByColumnAndRow($lastTakings ? $columnCount - 1 : $columnCount, $row)
                ->getCoordinate();

            if ($lastTakings) {
                $lastTotalDiffCoordinate = $worksheet->getCellByColumnAndRow($columnCount, $row)->getCoordinate();
            }

            $row++;
            $worksheet->getCellByColumnAndRow(1, $row)->setValue('Tržní podíl');

            for ($i = 2; $i <= $columnCount; $i++) {
                $cellColumn = $worksheet->getCellByColumnAndRow($i, $row)->getColumn();
                $sumFunction = null;

                if (!$lastTakings || $i % 2 === 0) {
                    $sumFunction = '=' . $cellColumn . ($row - 1) . ' / ' . $totalSumCoordinate;
                } elseif ($i !== $columnCount) {
                    $sumCellCol = $worksheet->getCellByColumnAndRow($i - 1, $row - 1)->getCoordinate();
                    $shareCol = $worksheet->getCellByColumnAndRow($i - 1, $row)->getCoordinate();
                    $sumFunction = "=$shareCol-((" . $sumCellCol . '-' . $cellColumn . ($row - 1) . ')/' . "($totalSumCoordinate-$lastTotalDiffCoordinate)" . ')';
                }

                $worksheet->getCellByColumnAndRow($i, $row)->setValue($sumFunction)
                    ->getStyle()->getNumberFormat()->setFormatCode('0.0 %');
            }

            foreach ($worksheet->getColumnIterator() as $col => $column) {
                if ($column) {
                    $worksheet->getColumnDimension($col)->setWidth(15);
                }
            }
        }

        $spreadsheet->setActiveSheetIndex(0);

        return new SpreadsheetResponse($spreadsheet, "$heading - $year", true);
    }

    public function outletsToExcel(int $year, array $outletData): SpreadsheetResponse
    {
        $stores = $this->orm->stores->loadStoresWithMainStorage();
        $columnCount = count($outletData[1][1]) + 1;
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

        foreach (StockVariant::OUTLETS_TYPES as $outletType => $outletLabel) {
            $data = $outletData[$outletType];
            $worksheet = $outletType === 1 ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
            $worksheet->setTitle($outletLabel);
            $heading = "Prodej výprodejů - $year - $outletLabel";
            $worksheet->setCellValue('A1', $heading)->getStyle('A1')->getFont()
                ->setBold(true)->setSize(14);
            $worksheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $worksheet->mergeCells('A1:' . $worksheet->getCellByColumnAndRow($columnCount, 1)->getCoordinate());

            $columnIndex = 2;

            foreach ($stores as $storeLabel) {
                $cell = $worksheet->getCellByColumnAndRow($columnIndex++, 2)->setValue($storeLabel);
                $cell->getStyle()->getFont()->setBold(true);
                $cell->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $cell->getStyle()->getBorders()->getBottom()->setBorderStyle(true);
            }

            $worksheet->setCellValue('A2', 'Prodejna')
                ->getStyle('A2')->getBorders()->getBottom()->setBorderStyle(true);


            $row = 3;

            foreach (DateTime::CZ_MONTHS as $index => $month) {
                $columnIndex = 2;
                $worksheet->getCellByColumnAndRow(1, $row)->setValue(ucfirst($month))
                    ->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                foreach (array_keys($stores) as $storeId) {
                    $value = round($data[$index][$storeId] ?? 0);
                    $worksheet->getCellByColumnAndRow($columnIndex++, $row)->setValue($value);
                }

                $row++;

                if ($index % 3 === 0) {
                    $cellValue = 'Celkem ' . ($index - 2) . '-' . $index;
                    $worksheet->getCellByColumnAndRow(1, $row)->setValue($cellValue)
                        ->getStyle()->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);

                    for ($i = 2; $i <= $columnCount; $i++) {
                        $cell = $worksheet->getCellByColumnAndRow($i, $row);
                        $colIndex = $cell->getColumn();
                        $sumArea = $colIndex . ($row - 3) . ':' . $colIndex . ($row - 1);
                        $cell->setValue("=SUM($sumArea)")
                            ->getStyle()->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
                    }

                    $row++;
                    $row++;
                }
            }

            foreach ($worksheet->getColumnIterator() as $col => $column) {
                if ($column) {
                    $worksheet->getColumnDimension($col)->setWidth(18);
                }
            }
        }

        $spreadsheet->setActiveSheetIndex(0);

        return new SpreadsheetResponse($spreadsheet, "outlet-$year", true);
    }

    private function getTotalSumData(int $year, array $data, bool $storeData = false): array
    {
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('n');
        $sumData = [];

        foreach ($data as $dataType => $takings) {
            foreach ($takings as $month => $producerTakings) {
                if ($storeData && !($year < $currentYear || ($year === $currentYear && $month < $currentMonth))) {
                    continue;
                }

                foreach ($producerTakings as $producerId => $value) {
                    if ($storeData) {
                        foreach ($value as $storeId => $storeValue) {
                            $sumData[$producerId][$storeId] ??= 0;
                            $sumData[$producerId][$storeId] += $storeValue;
                        }
                    } else {
                        $sumData[$dataType][$producerId] ??= 0;
                        $sumData[$dataType][$producerId] += $value;
                    }
                }
            }
        }

        return $sumData;
    }
}
