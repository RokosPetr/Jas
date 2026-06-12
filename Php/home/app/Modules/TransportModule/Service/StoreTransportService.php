<?php
declare(strict_types=1);

namespace App\Modules\TransportModule\Service;

use App\Core\Exporter\SpreadsheetResponse;
use App\Core\Utils\DateTime;
use App\Modules\SystemModule\Orm\Stores\Store;
use App\Modules\TransportModule\Component\StoreTransportCalendar;
use App\Modules\TransportModule\Orm\Cars\StoreCar;
use App\Modules\TransportModule\Orm\Transports\StoreTransport;
use App\Modules\TransportModule\Orm\Transports\StoreTransportTarget;
use App\Service\OrmModel;
use Nextras\Orm\Collection\ICollection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class StoreTransportService
{
    private OrmModel $orm;

    public function __construct(OrmModel $orm)
    {
        $this->orm = $orm;
    }

    public function createEmptyTransport(Store $store, StoreCar $car, \DateTime $date, float $interval): ?StoreTransport
    {
        if (!$car->driver) {
            return null;
        }

        $user = $this->orm->getSysUser();
        $timeFrom = hourToFloat($date->format('H:i'));
        $timeTill = $timeFrom + $interval;
        $filter = [
            'deleted' => false,
            'store->id' => $store->id,
            'car->id' => $car->id,
            'date' => $date->format(DateTime::DB_DATE),
            'timeFrom<' => $timeTill,
            'timeTill>' => $timeFrom
        ];
        $transport = $this->orm->storeTransports->getBy($filter);

        if ($transport && $transport->isRedundant()) {
            // Jedna se o starou nepotvrzenou rezervaci casu
            $this->orm->storeTransports->removeAndFlush($transport);
            $transport = null;
        }

        if (!$transport) {
            // tvorba nove rezervace
            $transport = new StoreTransport();
            $transport->store = $store;
            $transport->car = $car;
            $transport->driver = $car->driver;
            $transport->date = $date;
            $transport->timeFrom = $timeFrom;
            $transport->timeTill = $timeTill;
            $this->orm->storeTransports->persist($transport);
            $transport->createLock();
            $this->orm->storeTransports->persistAndFlush($transport);
            return $transport;
        }

        if ($transport->isLocked && $transport->lockedBy === $user) {
            // uprava stavajici rezervace
            $transport->updateLock();
            $this->orm->storeTransports->persistAndFlush($transport);
            return $transport;
        }

        return null;
    }

    /** Export rozvozu do excelu */
    public function exportTransport(StoreCar $car, \DateTime $date): SpreadsheetResponse
    {
        $filename = "Rozvoz maloobchodu - $car->licensePlate - " . $date->format('d.m.Y');
        $driver = $car->driver->name ?? '???';
        $driverPhone = $car->driver->phone ?? '???';
        $headBgColor = 'FFBF0002';
        $columnHeads = [
            'Čas' => 13,
            'Zákazník' => 25,
            'Příjemce' => 25,
            'Telefon' => 17,
            'Adresa dodání' => 25,
            'Dodací list' => 13,
            'Váha kg' => 9,
            'Připravil' => 13,
            'Pásmo' . PHP_EOL . 'úhrada' => 10,
            'Jízda' => 10,
            'Poznámka' => 35
        ];
        $columnCount = count($columnHeads);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        $worksheet = $spreadsheet->getActiveSheet()->setTitle($car->licensePlate);
        $worksheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0);

        //First row
        $cellStyle = $worksheet->setCellValue('A1', "$driver / $car->licensePlate / tel. $driverPhone")->getStyle('A1');
        $cellStyle->getFont()->setBold(true)->setSize(22)->getColor()->setARGB(Color::COLOR_WHITE);
        $cellStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($headBgColor);
        $worksheet->mergeCells('A1:' . $worksheet->getCellByColumnAndRow($columnCount - 1, 1)->getCoordinate());
        $cellStyle = $worksheet->getCellByColumnAndRow($columnCount, 1)->setValue($date->format(DateTime::CZ_DATE))->getStyle();
        $cellStyle->getFont()->setBold(true)->setSize(22);
        $cellStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFC0C0C0');

        $worksheet->getRowDimension(1)->setRowHeight(60);
        $worksheet->getRowDimension(2)->setRowHeight(20);
        $activeRow = 3;
        $activeColumn = 1;

        // Headings
        foreach ($columnHeads as $title => $width) {
            $worksheet->getColumnDimensionByColumn($activeColumn)->setWidth($width);
            $cellStyle = $worksheet->getCellByColumnAndRow($activeColumn, $activeRow)->setValue($title)->getStyle();
            $cellStyle->getFont()->setSize(11)->setBold(true)->getColor()->setARGB(Color::COLOR_WHITE);
            $cellStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($headBgColor);
            $activeColumn++;
        }

        // Data columns
        $activeRow = 4;
        $transports = $this->orm->storeTransports->findCarDayTransports($car, $date);
        $iteratorCounter = 1;
        $transportCount = $transports->count();
        $lastEndTime = StoreTransportCalendar::START_TIME;

        if (!$transportCount) {
            $worksheet->getCellByColumnAndRow(1, $activeRow)->setValue(
                floatToHour(StoreTransportCalendar::START_TIME) . ' - ' . floatToHour(StoreTransportCalendar::END_TIME)
            );
        }

        foreach ($transports as $transport) {
            $created = $transport->updatedBy ? $transport->updatedBy->name : '???';

            if ($transport->timeFrom > $lastEndTime) {
                $worksheet->getCellByColumnAndRow(1, $activeRow)->setValue(
                    floatToHour($lastEndTime) . ' - ' . floatToHour($transport->timeFrom)
                );
                $activeRow++;
            }

            $worksheet->getCellByColumnAndRow(1, $activeRow)->setValue(
                floatToHour($transport->timeFrom) . ' - ' . floatToHour($transport->timeTill)
            );

            if ($transport->type === StoreTransport::TYPE_UNAVAILABILITY) {
                $worksheet->getCellByColumnAndRow(2, $activeRow)->setValue(
                    'Omezení' . PHP_EOL . StoreTransport::REASONS_LABELS[$transport->reason]
                )->getStyle()->getFont()->setBold(true);
                $worksheet->getCellByColumnAndRow(8, $activeRow)->setValue($created);

                if ($transport->reasonRemark) {
                    $worksheet->getCellByColumnAndRow(11, $activeRow)->setValue($transport->reasonRemark);
                }

                $activeRow++;
            } else {
                foreach ($transport->targets as $target) {
                    $itemsCollection = $target->items->toCollection();
                    $dl = implode(PHP_EOL, $itemsCollection->fetchPairs(null, 'deliveryNoteLabel'));
                    $customers = implode(PHP_EOL, $itemsCollection->fetchPairs(null, 'customer'));
                    $tariff = $target->tariff ? StoreTransportTarget::TARIFFS_SHORT_LABELS[$target->tariff] : '-';
                    $payment = $target->payment ? StoreTransportTarget::PAYMENTS_LABELS[$target->payment] : '-';
                    $partNumbersStrings = [];

                    foreach ($itemsCollection->fetchPairs(null, 'partNumbers') as $partNumbers) {
                        foreach ($partNumbers as $partNumber => $totalParts) {
                            $partNumbersStrings[] = "$partNumber z $totalParts";
                        }
                    }

                    $worksheet->getCellByColumnAndRow(2, $activeRow)->setValue($customers ?: '???');
                    $worksheet->getCellByColumnAndRow(3, $activeRow)->setValue($target->name);
                    $worksheet->getCellByColumnAndRow(4, $activeRow)->setValue($target->phone)
                        ->setDataType(DataType::TYPE_STRING);
                    $worksheet->getCellByColumnAndRow(5, $activeRow)->setValue($target->address);
                    $worksheet->getCellByColumnAndRow(6, $activeRow)->setValue($dl);
                    $worksheet->getCellByColumnAndRow(7, $activeRow)->setValue($target->itemsWeight ?: '');
                    $worksheet->getCellByColumnAndRow(8, $activeRow)->setValue($created);
                    $worksheet->getCellByColumnAndRow(9, $activeRow)->setValue($tariff . PHP_EOL . $payment);
                    $worksheet->getCellByColumnAndRow(10, $activeRow)->setValue(implode(PHP_EOL, $partNumbersStrings));
                    $worksheet->getCellByColumnAndRow(11, $activeRow)->setValue($target->remark);
                    $activeRow++;
                }
            }

            $lastEndTime = $transport->timeTill;

            if ($iteratorCounter === $transportCount && $lastEndTime < StoreTransportCalendar::END_TIME) {
                $worksheet->getCellByColumnAndRow(1, $activeRow)->setValue(
                    floatToHour($transport->timeTill) . ' - ' . floatToHour(StoreTransportCalendar::END_TIME)
                );
                $activeRow++;
            }

            $iteratorCounter++;
        }

        for ($row = 3; $row <= $activeRow; $row++) {
            if ($row !== $activeRow) {
                $worksheet->getRowDimension($row)->setRowHeight(60);
            }

            for ($column = 1; $column <= $columnCount; $column++) {
                $cellBorder = $worksheet->getCellByColumnAndRow($column, $row)->getStyle()->getBorders();
                $cellBorder->getTop()->setBorderStyle(Border::BORDER_MEDIUM);

                if ($row === $activeRow) {
                    continue;
                }

                $cellBorder->getLeft()->setBorderStyle($column === 1 ? Border::BORDER_MEDIUM : Border::BORDER_THIN);

                if ($column === $columnCount) {
                    $cellBorder->getRight()->setBorderStyle(Border::BORDER_MEDIUM);
                }
            }
        }

        $worksheet->setSelectedCell('A1');

        return new SpreadsheetResponse($spreadsheet, $filename, true);
    }

    /** Export nevalidnich rozvozu do excelu */
    public function exportInvalidTransports(int $store = null): SpreadsheetResponse
    {
        $filename = 'Nevalidní rozvozy - ' . date(DateTime::CZ_DATE);
        $stores = $store
            ? $this->orm->stores->findBy(['id' => $store])->fetchPairs('id', 'name')
            : $this->orm->stores->findAll()->fetchPairs('id', 'name');
        $columns = [
            'Datum', 'Od', 'Do', 'Příjemci', 'Dodací listy', 'Dodávka', 'Řidič', 'Chyby'
        ];
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(12);
        $worksheet = $spreadsheet->getActiveSheet();
        $activeRow = 1;

        foreach ($stores as $storeId => $storeName) {
            $invalidTransports = $this->orm->storeTransports->findInvalidTransports($storeId)
                ->orderBy('date', ICollection::DESC);

            if (!$invalidTransports->count()) {
                continue;
            }

            $worksheet->getRowDimension($activeRow)->setRowHeight(20);
            $worksheet->getCellByColumnAndRow(1, $activeRow)->setValue($storeName)
                ->getStyle()->getFont()->setSize(18)->setBold(true);
            $worksheet->mergeCells(
                $worksheet->getCellByColumnAndRow(1, $activeRow)->getCoordinate()
                . ':'
                . $worksheet->getCellByColumnAndRow(count($columns), $activeRow)->getCoordinate()
            );

            $activeRow++;

            foreach ($columns as $index => $column) {
                $worksheet->getCellByColumnAndRow($index + 1, $activeRow)->setValue($column)
                    ->getStyle()->getFont()->setBold(true);
            }

            $activeRow++;

            /** @var StoreTransport $transport */
            foreach ($invalidTransports as $transport) {
                $worksheet->getCellByColumnAndRow(1, $activeRow)->setValue($transport->date->format(DateTime::CZ_DATE));
                $worksheet->getCellByColumnAndRow(2, $activeRow)->setValue(floatToHour($transport->timeFrom));
                $worksheet->getCellByColumnAndRow(3, $activeRow)->setValue(floatToHour($transport->timeTill));
                $worksheet->getCellByColumnAndRow(4, $activeRow)->setValue(implode(', ', $transport->targets->toCollection()->fetchPairs(null, 'name')));
                $worksheet->getCellByColumnAndRow(5, $activeRow)->setValue(implode(', ', $transport->deliveryNotes));
                $worksheet->getCellByColumnAndRow(6, $activeRow)->setValue($transport->car->licensePlate);
                $worksheet->getCellByColumnAndRow(7, $activeRow)->setValue($transport->driver->name);
                $worksheet->getCellByColumnAndRow(8, $activeRow)->setValue(implode(', ', $transport->errors));
                $activeRow++;
            }

            $activeRow++;
        }

        foreach ($worksheet->getColumnIterator() as $col => $column) {
            if ($column) {
                $worksheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        return new SpreadsheetResponse($spreadsheet, $filename, true);
    }

    /** Export vyteznosti rozvozu do excelu */
    public function exportStoreCarOccupancy(int $year, array $months, array $cars): SpreadsheetResponse
    {
        $months = array_map('intval', $months);
        $filename = "Výtěžnost MO rozvozů $year";
        $firstColumnIndex = 2;
        $lastColumnIndex = 2 * count($months) + 2;
        $spreadsheet = new Spreadsheet();
        if (!$months || !$cars) {
            return new SpreadsheetResponse($spreadsheet, $filename, true);
        }
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->getStyle('A:Z')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $worksheet->getCellByColumnAndRow($firstColumnIndex, 2)->setValue($filename)
            ->getStyle()->getFont()->setSize(14)->setBold(true);
        $worksheet->mergeCells(
            $worksheet->getCellByColumnAndRow($firstColumnIndex, 2)->getCoordinate()
            . ':'
            . $worksheet->getCellByColumnAndRow($lastColumnIndex, 3)->getCoordinate()
        );
        $worksheet->getCellByColumnAndRow($firstColumnIndex, 4)->setValue('pobočka');
        $worksheet->getCellByColumnAndRow($firstColumnIndex, 5)->setValue('auto');
        sort($months);
        $activeColumn = 3;

        foreach ($months as $month) {
            $worksheet->getCellByColumnAndRow($activeColumn, 4)->setValue(DateTime::CZ_MONTHS[$month] ?? '');
            $worksheet->mergeCells(
                $worksheet->getCellByColumnAndRow($activeColumn, 4)->getCoordinate()
                . ':'
                . $worksheet->getCellByColumnAndRow($activeColumn + 1, 4)->getCoordinate()
            );
            $worksheet->getCellByColumnAndRow($activeColumn++, 5)->setValue('fond PD');
            $worksheet->getCellByColumnAndRow($activeColumn++, 5)->setValue('najeto');
        }

        $activeRow = 6;
        $carCollection = $this->orm->storeCars->findBy(['deleted' => false, 'id' => $cars]);

        foreach ($carCollection as $car) {
            $worksheet->mergeCells(
                $worksheet->getCellByColumnAndRow($firstColumnIndex, $activeRow)->getCoordinate()
                . ':'
                . $worksheet->getCellByColumnAndRow($lastColumnIndex, $activeRow)->getCoordinate()
            );
            $activeRow++;
            $worksheet->getCellByColumnAndRow($firstColumnIndex, $activeRow)->setValue($car->homeStore->name ?? '')
                ->getStyle()->getFont()->setBold(true);
            $worksheet->getCellByColumnAndRow($firstColumnIndex, $activeRow + 1)->setValue($car->licensePlate)
                ->getStyle()->getFont()->setBold(true);
            $activeColumn = 3;

            foreach ($months as $month) {
                $carOccupancy = $car->loadOccupancy($month, $year);
                $carTimeFund = $car->loadTimeFund($month, $year);
                $occupancyValue = $carTimeFund ? ($carOccupancy / $carTimeFund) : null;
                $occupancyString = is_null($occupancyValue)
                    ? '-'
                    : (number_format($occupancyValue * 100) . ' %');
                $worksheet->getCellByColumnAndRow($activeColumn, $activeRow)->setValue($carTimeFund);
                $worksheet->getCellByColumnAndRow($activeColumn + 1, $activeRow)->setValue($carOccupancy);
                $worksheet->getCellByColumnAndRow($activeColumn, $activeRow + 1)->setValue($occupancyString);
                $worksheet->mergeCells(
                    $worksheet->getCellByColumnAndRow($activeColumn, $activeRow + 1)->getCoordinate()
                    . ':'
                    . $worksheet->getCellByColumnAndRow($activeColumn + 1, $activeRow + 1)->getCoordinate()
                );
                $activeColumn++;
                $activeColumn++;
            }

            $activeRow++;
            $activeRow++;
        }

        $lastRowIndex = $activeRow - 1;
        $worksheet->getColumnDimension('A')->setWidth(4);
        $worksheet->getColumnDimension('B')->setAutoSize(true);
        $borders = $worksheet->getStyle(
            'B2:'
            . $worksheet->getCellByColumnAndRow($lastColumnIndex, $lastRowIndex)->getCoordinate()
        )->getBorders();
        $borders->getOutline()->setBorderStyle(true);
        $borders->getInside()->setBorderStyle(Border::BORDER_THIN);

        return new SpreadsheetResponse($spreadsheet, $filename, true);
    }
}