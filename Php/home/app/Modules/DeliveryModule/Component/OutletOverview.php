<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Component;

use App\Modules\DeliveryModule\Service\OverviewExporter;
use App\Modules\StockModule\Orm\StockItems\StockVariant;
use App\Service\OrmModel;
use Nette\Application\UI\Control;
use Nette\Application\UI\Presenter;
use Nette\Http\SessionSection;

class OutletOverview extends Control
{
    private OrmModel $orm;
    private OverviewExporter $exporter;
    private SessionSection $session;
    private array $outletData;
    public int $selectedYear;

    public function __construct(OrmModel $orm, OverviewExporter $exporter)
    {
        $this->orm = $orm;
        $this->exporter = $exporter;
        $this->monitor(Presenter::class, function (): void {
            $this->session = $this->getPresenter()->getSession('outletOverview');
        });
    }

    public function loadState(array $params): void
    {
        $this->selectedYear = $this->session->selectedYear ?? (int)date('Y');
    }

    public function handleSetYear(): void
    {
        $this->selectedYear = $this->session->selectedYear = (int) $this->getPresenter()->getParameter('year');
        $this->redrawControl('outletOverview');
    }

    public function handleExcelExport(): void
    {
        $response = $this->exporter->outletsToExcel($this->selectedYear, $this->loadData());
        $this->getPresenter()->sendResponse($response);
    }

    public function render(): void
    {
        $this->outletData = $this->loadData();
        $this->template->stores = $this->orm->stores->loadStoresWithMainStorage();
        $this->template->setFile(__DIR__ . '/templates/outletOverview.latte');
        $this->template->render();
    }

    public function loadCellValue(int $storeId, int $outletType, int $month): float
    {
        return $this->outletData[$outletType][$month][$storeId] ?? 0;
    }

    public function loadSumValue(int $storeId, int $outletType, int $monthFrom, int $monthTo): float
    {
        $sumValue = 0;
        for ($month = $monthFrom; $month <= $monthTo; $month++) {
            $sumValue += $this->outletData[$outletType][$month][$storeId] ?? 0;
        }
        return $sumValue;
    }

    private function loadData(): array
    {
        $data = [];
        foreach (array_keys(StockVariant::OUTLETS_TYPES) as $outletType) {
            for ($month = 1; $month <= 12; $month++) {
                $data[$outletType][$month] = $this->orm->salesData->getMapper()->loadOutletData($this->selectedYear, $month, $outletType);
            }
        }
        return $data;
    }
}
