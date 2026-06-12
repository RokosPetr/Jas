<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Presenters\Traits;

use App\Modules\DeliveryModule\Component\Entity\SalesFilterEntity;
use Contributte\PdfResponse\PdfResponse;
use Nette\Utils\Html;
use Nextras\Dbal\Result\Row;

trait OverviewExportTrait
{
    /** Export do PDF */
    public function actionExportOverview(): void
    {
        $controlName = $this->getName() === 'Delivery:Dealer' ? 'dealerOverview' : 'storeOverview';
        $this->setLayout(false);
        $control = $this[$controlName];
        /** @var SalesFilterEntity $filter */
        $filter = $control->getDataFilter();
        $years = $filter->years;
        $company = $filter->company ? $this->orm->companies->getById($filter->company)->companyName : null;
        $depot = $filter->depot ? $this->orm->companyDepots->getById($filter->depot)->name : null;
        $series = $filter->series ? $this->orm->stockSeries->getById($filter->series)->name : null;
        $item = $filter->item ? $this->orm->stockItems->getById($filter->item)->title : null;

        $this->template->setFile(__DIR__ . '/../../Component/templates/pdf/baseOverview.latte');
        $this->template->overviewControl = $control;
        $this->template->columns = $control->loadColumns();
        $this->template->years = $years;
        $this->template->hasCurrentYear = in_array(date('Y'), $years);
        $this->template->isTotalValue = $control->isTotalValue();
        $this->template->charts = $this->getRequest()->getPost('charts') ?? [];
        $this->template->company = $depot ?? $company;
        $this->template->stockItem = $item ?? ($series ? "Serie $series" : null);

        if ($controlName === 'storeOverview') {
            $subject = $filter->store ? $this->orm->stores->getById($filter->store)->name : 'Všechny';
            if ($filter->oz) {
                $subject .= " OZ.$filter->oz";
            }
            $this->template->subject = "Pobočka: $subject";
        } else {
            $subject = implode(', ', $this->orm->users->findBy(['id' => $filter->dealers])->fetchPairs(null, 'name'));
            $this->template->subject = "Obchodní zástupce: $subject";
        }

        $footer = "Srovnání odběrů - $subject";

        if ($this->template->company) {
            $footer .= ' - ' . $this->template->company;
        }

        if ($this->template->stockItem) {
            $footer .= ' - ' . $this->template->stockItem;
        }

        $pdf = new PdfResponse($this->template);
        $pdf->setDocumentTitle('export');
        $pdf->pageFormat = 'A4-L';
        $pdf->getMPDF()->setFooter($footer);
        $pdf->setSaveMode(PdfResponse::INLINE);
        $pdf->styles = file_get_contents(__DIR__ . '/../../Component/templates/pdf/baseOverview.css');
        $this->sendResponse($pdf);
    }

    /** Export Gridu do Excelu */
    public function handleExportOverviewGrid(): void
    {
        $controlName = $this->getName() === 'Delivery:Dealer' ? 'dealerOverview' : 'storeOverview';
        $control = $this[$controlName];
        /** @var SalesFilterEntity $filter */
        $filter = $control->getDataFilter();
        $years = $filter->years;
        $yearResult = [];
        $header = ['Název'];

        foreach ($years as $year) {
            $filter->years = [$year];
            $dbResult = $controlName === 'storeOverview'
                ? $this->orm->companies->getMapper()->loadStoreOverviewGridData($filter)
                : $this->orm->companies->getMapper()->loadDealerOverviewGridData(
                    $filter, null, $this->getOverviewGridType()
                );
            $header[] = $year;

            foreach ($dbResult as $company => $value) {
                $yearResult[$company][$year] ??= [];
                $yearResult[$company][$year] = round($value ?? 0);
            }
        }

        $data = [$header];

        foreach ($yearResult as $company => $yearData) {
            $data[] = array_merge([$company], $yearData);
        }

        $response = $this->exporter->arrayToExcelSheets(['List 1' => $data]);
        $this->sendResponse($response);
    }

    protected function getOverviewGridValue(Row $row, string $column, int $diffYear): Html
    {
        $cell = Html::el('span')->setText(is_float($row->$column)
            ? number_format($row->$column, 0, ',', ' ')
            : $row->$column
        );
        if (!is_numeric($column) || $column == $diffYear || !$row->$diffYear) {
            return $cell;
        }
        $class = ($row->$diffYear > $row->$column ? 'negative-value' : 'positive-value');
        $operator = ($row->$diffYear > $row->$column ? ' -' : ' +');
        $diff = number_format((abs($row->$diffYear - $row->$column) / $row->$diffYear) * 100, 1, ',', ' ');
        return $cell->addHtml(Html::el('span', ['class' => $class])->setText($operator . "$diff %"));
    }
}