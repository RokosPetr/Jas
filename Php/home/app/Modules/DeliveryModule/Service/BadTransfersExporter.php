<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Service;

use App\Core\Exporter\SpreadsheetResponse;
use App\Modules\DeliveryModule\Orm\DeliveryNotes\DeliveryNote;
use App\Service\OrmModel;
use Nextras\Orm\Collection\ICollection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BadTransfersExporter
{
    private OrmModel $orm;

    public function __construct(OrmModel $orm)
    {
        $this->orm = $orm;
    }

    public function createExcelExport(): SpreadsheetResponse
    {
        $badTransfers = [];
        $incompleteTransfersIn = [];
        $incompleteTransfersOut = [];

        foreach ($this->orm->stores->findAll()->fetchPairs(null, 'id') as $storeId) {
            $badTransfersIds = $this->orm->deliveryNotes->loadBadTransfers($storeId);

            $badTransfers[$storeId] = $this->orm->deliveryNotes->findBy(
                ['id' => $badTransfersIds, 'parent->id!=' => null, 'checked' => false]
            )->orderBy(['parent->store->id' => ICollection::ASC, 'number' => ICollection::ASC])->fetchAll();

            $incompleteTransfersIn[$storeId] = $this->orm->deliveryNotes->findBy(
                ['id' => $badTransfersIds, 'parent->id' => null, 'checked' => false]
            )->orderBy(['description' => ICollection::ASC, 'number' => ICollection::ASC])->fetchAll();

            $incompleteTransfersOut[$storeId] = $this->orm->deliveryNotes->findBy([
                'date>=' => '2024-01-01', // Kontroluji se doklady az od roku 2021
                'date<=' => '2024-12-31', // Kontroluji se doklady az od roku 2021
                'store->id' => $storeId,
                'movementType' => DeliveryNote::TYPE_TRANSFER_OUT,
                'child->id' => null,
                'depot->voj!=' => '88',
                'checked' => false
            ])->orderBy(['description' => ICollection::ASC, 'number' => ICollection::ASC])->fetchAll();
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
        $spreadsheet->getDefaultStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $worksheet = $spreadsheet->getActiveSheet();
        $mainHeading = $worksheet->getCellByColumnAndRow(1, 1)->setValue('Převodky s rozdílnými cenami');
        $mainHeading->getStyle()->getFont()->setBold(true)->setSize(14);
        $mainHeading->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $this->createColHeading(
            $worksheet,
            2,
            [
                'Pobočka', 'Příjmový doklad', 'Datum', 'Nákupní cena', 'Prodejní cena',
                'Z pobočky', 'Výdejní doklad', 'Datum', 'Nákupní cena', 'Prodejní cena', 'Poznámka'
            ]
        );
        $activeRow = 3;

        foreach ($badTransfers as $transferNotes) {
            foreach ($transferNotes as $note) {
                $worksheet->getCellByColumnAndRow(1, $activeRow)->setValue($note->store->name)
                    ->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $worksheet->getCellByColumnAndRow(2, $activeRow)->setValue($note->number);
                $worksheet->getCellByColumnAndRow(3, $activeRow)->setValue($note->date->format('d.m.Y'));
                $worksheet->getCellByColumnAndRow(4, $activeRow)->setValue(number_format(round($note->buySum, 1), 2, ',', ' '));
                $worksheet->getCellByColumnAndRow(5, $activeRow)->setValue(number_format(round($note->sellSum, 1), 2, ',', ' '));
                $worksheet->getCellByColumnAndRow(6, $activeRow)->setValue($note->parent->store->name)
                    ->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $worksheet->getCellByColumnAndRow(7, $activeRow)->setValue($note->parent->number);
                $worksheet->getCellByColumnAndRow(8, $activeRow)->setValue($note->parent->date->format('d.m.Y'));
                $worksheet->getCellByColumnAndRow(9, $activeRow)->setValue(number_format(round($note->parent->buySum, 1), 2, ',', ' '));
                $worksheet->getCellByColumnAndRow(10, $activeRow)->setValue(number_format(round($note->parent->sellSum, 1), 2, ',', ' '));
                $worksheet->getCellByColumnAndRow(11, $activeRow)->setValue($note->remark)
                    ->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $activeRow++;
            }
        }

        $mainHeading = $worksheet->getCellByColumnAndRow(1, ++$activeRow)->setValue('Příjmové doklady bez výdejního dokladu');
        $mainHeading->getStyle()->getFont()->setBold(true)->setSize(14);
        $mainHeading->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $activeRow++;
        $this->createColHeading(
            $worksheet,
            $activeRow,
            [
                'Pobočka', 'Příjmový doklad', 'Datum', 'Nákupní cena', 'Prodejní cena',
                'Z pobočky', 'Výdejní doklad', 'Datum', 'Nákupní cena', 'Prodejní cena', 'Poznámka'
            ]
        );
        $activeRow++;

        foreach ($incompleteTransfersIn as $transferNotes) {
            foreach ($transferNotes as $note) {
                $worksheet->getCellByColumnAndRow(1, $activeRow)->setValue($note->store->name)
                    ->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $worksheet->getCellByColumnAndRow(2, $activeRow)->setValue($note->number);
                $worksheet->getCellByColumnAndRow(3, $activeRow)->setValue($note->date->format('d.m.Y'));
                $worksheet->getCellByColumnAndRow(4, $activeRow)->setValue(number_format(round($note->buySum, 1), 2, ',', ' '));
                $worksheet->getCellByColumnAndRow(5, $activeRow)->setValue(number_format(round($note->sellSum, 1), 2, ',', ' '));
                $worksheet->getCellByColumnAndRow(6, $activeRow)->setValue($note->description)
                    ->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $worksheet->getCellByColumnAndRow(7, $activeRow)->setValue($note->depotNote);
                $worksheet->getCellByColumnAndRow(11, $activeRow)->setValue($note->remark)
                    ->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $activeRow++;
            }
        }

        $mainHeading = $worksheet->getCellByColumnAndRow(1, ++$activeRow)->setValue('Výdejní doklady bez příjmového dokladu');
        $mainHeading->getStyle()->getFont()->setBold(true)->setSize(14);
        $mainHeading->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $activeRow++;
        $this->createColHeading(
            $worksheet,
            $activeRow,
            [
                'Pobočka', 'Výdejní doklad', 'Datum', 'Nákupní cena', 'Prodejní cena',
                'Na pobočku', 'Příjmový doklad', 'Datum', 'Nákupní cena', 'Prodejní cena', 'Poznámka'
            ]
        );
        $activeRow++;

        foreach ($incompleteTransfersOut as $transferNotes) {
            foreach ($transferNotes as $note) {
                $worksheet->getCellByColumnAndRow(1, $activeRow)->setValue($note->store->name)
                    ->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $worksheet->getCellByColumnAndRow(2, $activeRow)->setValue($note->number);
                $worksheet->getCellByColumnAndRow(3, $activeRow)->setValue($note->date->format('d.m.Y'));
                $worksheet->getCellByColumnAndRow(4, $activeRow)->setValue(number_format(round($note->buySum, 1), 2, ',', ' '));
                $worksheet->getCellByColumnAndRow(5, $activeRow)->setValue(number_format(round($note->sellSum, 1), 2, ',', ' '));
                $worksheet->getCellByColumnAndRow(6, $activeRow)->setValue($note->description)
                    ->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $worksheet->getCellByColumnAndRow(7, $activeRow)->setValue($note->depotNote);
                $worksheet->getCellByColumnAndRow(11, $activeRow)->setValue($note->remark)
                    ->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $activeRow++;
            }
        }

        $worksheet->getColumnDimensionByColumn(1)->setWidth(22);
        $worksheet->getColumnDimensionByColumn(2)->setWidth(15);
        $worksheet->getColumnDimensionByColumn(3)->setWidth(12);
        $worksheet->getColumnDimensionByColumn(4)->setWidth(15);
        $worksheet->getColumnDimensionByColumn(5)->setWidth(15);
        $worksheet->getColumnDimensionByColumn(6)->setWidth(22);
        $worksheet->getColumnDimensionByColumn(7)->setWidth(15);
        $worksheet->getColumnDimensionByColumn(8)->setWidth(12);
        $worksheet->getColumnDimensionByColumn(9)->setWidth(15);
        $worksheet->getColumnDimensionByColumn(10)->setWidth(15);
        $worksheet->getColumnDimensionByColumn(11)->setWidth(25);

        return new SpreadsheetResponse($spreadsheet, 'Chybné převodky', true);
    }

    private function createColHeading(Worksheet $worksheet, int $row, array $headings): void
    {
        $col = 1;
        $colCount = count($headings);

        foreach ($headings as $heading) {
            $worksheet->getCellByColumnAndRow($col++, $row)->setValue($heading);
        }

        for ($i = 1; $i <= $colCount; $i++) {
            $cellStyle = $worksheet->getStyleByColumnAndRow($i, $row);
            $cellStyle->getFont()->setBold(true)->getColor()->setARGB(Color::COLOR_WHITE);
            $cellStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(Color::COLOR_RED);
            $cellStyle->getAlignment()->setHorizontal(Alignment::VERTICAL_CENTER);
        }
    }
}
