<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Utils\DateTime;
use App\Modules\DeliveryModule\Component\OutletOverview;
use App\Modules\DeliveryModule\Component\SalesOverview;
use App\Modules\DeliveryModule\Orm\DeliveryNotes\DeliveryNote;
use App\Modules\DeliveryModule\Service\OverviewExporter;
use App\Modules\Presenters\SecurePresenter;
use App\Modules\StockModule\Orm\StockItems\StockVariant;
use App\Modules\SystemModule\Orm\Stores\Store;
use Nextras\Dbal\Utils\DateTimeImmutable;

/** Presenter pro prezentaci dat vyprodeju */
final class OutletOverviewPresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'Statistika prodeje výprodejů',
        'sumCheck' => 'Výprodeje - doklady'
    ];

    /** @inject */
    public OverviewExporter $overviewExporter;

    public function actionSumCheck(int $store, int $outlet, int $year, int $month): void
    {
        $dateFrom = (new DateTimeImmutable())->setDate($year, $month, 1)->setTime(0, 0);
        $dateTo = $dateFrom->modify('+1 month');
        $filter = [
            'note->store->id' => Store::MAIN_STORAGES,
            'note->date>=' => $dateFrom,
            'note->date<' => $dateTo,
            'outletType' => $outlet
        ];

        if ($store === Store::MAIN_STORAGE) {
            $filter['note->movementType'] = [DeliveryNote::TYPE_SALE, DeliveryNote::TYPE_CANCEL];
        } else {
            $filter['note->movementType'] = [DeliveryNote::TYPE_TRANSFER_IN, DeliveryNote::TYPE_TRANSFER_OUT];
            $filter['note->depot->voj'] = $store;
        }

        $this->template->noteItems = $this->orm->deliveryNoteItems->findBy($filter)
            ->orderBy('note->id')->orderBy('note->number')->fetchAll();
        $this->template->year = $year;
        $this->template->month = DateTime::CZ_MONTHS[$month];
        $this->template->store = SalesOverview::SALE_GROUPS[$store];
        $this->template->outlet = StockVariant::OUTLETS_TYPES[$outlet];
    }

    protected function createComponentOutletOverview(): OutletOverview
    {
        return new OutletOverview($this->orm, $this->overviewExporter);
    }

    protected function createComponentOutletItems(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->stockVariants);
        $grid->settings->setFulltextColumns(['regNumber', 'name', 'catalogTitle'])
            ->setDataSourceFilter([
            'outletType!=' => null,
            'store->id' => $this->selectedStore
        ]);
        $grid->addCellsTemplate(__DIR__ . '/../templates/OutletOverview/grid.cells.latte');
        $grid->setMultiWordSearch();

        $grid->addColumn('regNumber', 'Registrační číslo')->enableSort(BaseDatagrid::ORDER_ASC);
        $grid->addColumn('producer', 'Výrobce')->enableSort();
        $grid->addColumn('series', 'Série');
        $grid->addColumn('name', 'Název')->enableSort();
        $grid->addColumn('catalogTitle', 'Katalogové číslo')->enableSort();
        $grid->addColumn('remark', 'Varianta');
        $grid->addColumn('outletType', 'Výprodej');

        return $grid;
    }
}
