<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Service;

use App\Core\Exporter\SpreadsheetResponse;
use App\Modules\DeliveryModule\Orm\Companies\Depot;
use App\Modules\SystemModule\Orm\Stores\Store;
use App\Service\OrmModel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class DiscountExporter
{
    private OrmModel $orm;

    public function __construct(OrmModel $orm)
    {
        $this->orm = $orm;
    }

    public function discountsToExcel(Depot $depot, array $producers): SpreadsheetResponse
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

        // Slevy na skupiny
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->setTitle('Skupiny');
        $worksheet->setCellValue('A1', 'Slevy na produktové skupiny')->getStyle('A1')->getFont()
            ->setBold(true)->setSize(14);
        $worksheet->mergeCells('A1:C1');
        $worksheet->setCellValue('A3', 'Výrobce')->getStyle('A3')->getFont()->setBold(true);
        $worksheet->setCellValue('B3', 'Druh zboží')->getStyle('B3')->getFont()->setBold(true);
        $worksheet->setCellValue('C3', 'Sleva')->getStyle('C3')->getFont()->setBold(true);
        $worksheet->fromArray($this->orm->discountStockGroups->loadExportData($depot, $producers), null, 'A4');

        foreach ($worksheet->getColumnIterator() as $col => $column) {
            if ($column) {
                $worksheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        // Slevy na produkty
        $worksheet = $spreadsheet->createSheet();
        $worksheet->setTitle('Produkty');
        $worksheet->setCellValue('A1', 'Slevy na produkty')->getStyle('A1')->getFont()
            ->setBold(true)->setSize(14);
        $worksheet->mergeCells('A1:C1');
        $worksheet->setCellValue('A3', 'Výrobce')->getStyle('A3')->getFont()->setBold(true);
        $worksheet->setCellValue('B3', 'Produkt')->getStyle('B3')->getFont()->setBold(true);
        $worksheet->setCellValue('C3', 'Sleva')->getStyle('C3')->getFont()->setBold(true);
        $worksheet->fromArray($this->orm->discountStockItems->loadExportData($depot, $producers), null, 'A4');

        foreach ($worksheet->getColumnIterator() as $col => $column) {
            if ($column) {
                $worksheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        $spreadsheet->setActiveSheetIndex(0);
        return new SpreadsheetResponse($spreadsheet, "Slevy - $depot->title", true);
    }

    public function contactsToExcel(array $depots): SpreadsheetResponse
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
        $worksheet = $spreadsheet->getActiveSheet();
        $sheetColumns = ['IČO', 'Společnost', 'Pobočka', 'Adresa provozovny', 'Jméno', 'Pozice', 'Telefon', 'Email', 'WWW stránka', 'Poznámka'];
        $activeRow = 1;

        foreach ($sheetColumns as $index => $sheetColumn) {
            $worksheet->getCellByColumnAndRow($index + 1, $activeRow)->setValue($sheetColumn)
                ->getStyle()->getFont()->setBold(true);
        }

        $depotCollection = $depots
            ? $this->orm->companyDepots->findByIds($depots)
            : $this->orm->companyDepots->findBy([
                'store->id' => Store::OSTRAVA_MAIN_STORAGE,
                'dealers->id!=' => null
            ]);
        $depotCollection->orderBy('company->name');

        foreach ($depotCollection as $depot) {
            $contactCollection = $depot->contacts->toCollection()->findBy(['deleted' => false])->orderBy('order');

            foreach ($contactCollection as $contact) {
                $activeRow++;
                $worksheet->getCellByColumnAndRow(1, $activeRow)->setValueExplicit($depot->companyIcoString, DataType::TYPE_STRING);
                $worksheet->getCellByColumnAndRow(2, $activeRow)->setValue($depot->companyName);
                $worksheet->getCellByColumnAndRow(3, $activeRow)->setValue($depot->title);
                $worksheet->getCellByColumnAndRow(4, $activeRow)->setValue($depot->contactAddress->title ?? null);
                $worksheet->getCellByColumnAndRow(5, $activeRow)->setValue($contact->name);
                $worksheet->getCellByColumnAndRow(6, $activeRow)->setValue($contact->position);
                $worksheet->getCellByColumnAndRow(7, $activeRow)->setValueExplicit($contact->phone, DataType::TYPE_STRING);
                $worksheet->getCellByColumnAndRow(8, $activeRow)->setValue($contact->email);
                $worksheet->getCellByColumnAndRow(9, $activeRow)->setValue($contact->url);
                $worksheet->getCellByColumnAndRow(10, $activeRow)->setValue($contact->remark);
            }
        }

        foreach ($worksheet->getColumnIterator() as $col => $column) {
            if ($column) {
                $worksheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        return new SpreadsheetResponse($spreadsheet, 'Kontakty partnerů', true);
    }
}
