<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Core\Component\Form\FilterContainer;
use App\Core\Orm\BaseMapper;
use App\Core\Utils\DateTime;
use App\Modules\CliModule\Service\WarehouseImporter;
use App\Modules\DeliveryModule\Component\DealerOverview;
use App\Modules\DeliveryModule\Component\DealerOverviewFilter;
use App\Modules\DeliveryModule\Orm\Companies\DepotRepository;
use App\Modules\DeliveryModule\Orm\DeliveryNotes\DeliveryNote;
use App\Modules\DeliveryModule\Presenters\Traits\OverviewExportTrait;
use App\Modules\Presenters\SecurePresenter;
use App\Modules\SystemModule\Orm\Roles\Role;
use App\Modules\SystemModule\Orm\Stores\Store;
use App\Modules\SystemModule\Orm\Users\User;
use Nette\Utils\Paginator;
use Nextras\Dbal\Result\Row;
use Nextras\Orm\Collection\ICollection;

/** Presenter pro spravu obchodnich zastupcu */
final class DealerPresenter extends SecurePresenter
{
    use OverviewExportTrait;

    public array $titles = [
        'default' => 'Obchodní zástupci',
        'partners' => 'Partneři obchodníka',
        'overview' => 'Analýza odběrů',
        'overviewGrid' => 'Analýza odběrů'
    ];

    public const COMPANY_GRID = 1;
    public const DEPOT_GRID = 2;

    /** @inject  */
    public WarehouseImporter $warehouseImporter;

    /** Vyber obchodnich partneru pro zvoleneho zastupce */
    public function actionPartners(int $id): void
    {
        $dealer = $this->orm->users->getById($id);
        if (!$dealer || $dealer->deleted) {
            $this->error('Položka nenalezena');
        }
        if (!$this->isAjax()) {
            $dealerDepots = $dealer->depots->getRawValue();
            $this->getSession('dealerPartners_datagrid')->selectedRows = array_combine($dealerDepots, $dealerDepots);
        }
        $this->template->dealer = $dealer;
    }

    /** Export partneru obchodniho zastupce */
    public function actionExportPartners(int $id): void
    {
        $dealer = $this->orm->users->getById($id);
        if (!$dealer || $dealer->deleted) {
            $this->error('Položka nenalezena');
        }

        $data = [];
        $depotCollection = $dealer->depots->toCollection()->orderBy([
            'company->name' => ICollection::ASC,
            'voj' => ICollection::ASC,
            'store->id' => ICollection::ASC,
            'group->number' => ICollection::ASC
        ]);

        foreach ($depotCollection as $depot) {
            $data[] = [
                'MOP pobočka' => $depot->store->id,
                'ICO' => $depot->companyIcoString,
                'Název' => $depot->companyName,
                'voj' => $depot->voj,
                'Město' => $depot->city,
            ];
        }

        $this->sendResponse($this->exporter->arrayToCsv($data, "$dealer->name.csv"));
    }

    /** Import partneru obchodniho zastupce */
    public function actionImportPartners(int $id): void
    {
        $dealer = $this->orm->users->getById($id);
        if (!$dealer || $dealer->deleted) {
            $this->error('Položka nenalezena');
        }
        $this->template->dealer = $dealer;
    }

    /** Aktualizace partneru z MOP */
    public function actionUpdatePartners(): void
    {
        foreach (Store::MAIN_STORAGES as $mainStorage) {
            $this->flashMessage($this->warehouseImporter->importPartners($mainStorage));
        }
        $this->redirect('default');
    }

    public function actionSumCheck(int $producer, int $year, int $month): void
    {
        $overviewFilter = $this['dealerOverview']['overviewFilter']->getDataFilter();
        $date = \DateTime::createFromFormat('Y-n-d', "$year-$month-01");
        $filter = [
            'note->movementType' => [DeliveryNote::TYPE_SALE, DeliveryNote::TYPE_CANCEL],
            'note->date>=' => $date->format(DateTime::DB_DATE),
            'note->date<=' => $date->modify('last day of this month')->format(DateTime::DB_DATE),
            'item->item->group->id' => $overviewFilter->getStockGroupFilter($producer),
            'note->depot->dealers->id' => $overviewFilter->dealers
        ];

        if ($overviewFilter->company) {
            $filter['note->depot->company->id'] = $overviewFilter->company;
        }

        if ($overviewFilter->depot) {
            $filter['note->depot->id'] = $overviewFilter->depot;
        }

        if ($overviewFilter->series) {
            $filter['item->item->series->id'] = $overviewFilter->series;
        }

        if ($overviewFilter->item) {
            $filter['item->item->id'] = $overviewFilter->item;
        }

        $this->template->noteItems = $this->orm->deliveryNoteItems->findBy($filter)
            ->orderBy('note->number')
            ->fetchAll();
        $this->template->year = $year;
        $this->template->month = DateTime::CZ_MONTHS[$month] ?? '-';
        $this->template->producer = $this->orm->producers->getById($producer)->name ?? '???';
        $this->template->company = $overviewFilter->company ? $this->orm->companies->getById($overviewFilter->company) : null;
        $this->template->series = $overviewFilter->series ? $this->orm->stockSeries->getById($overviewFilter->series) : null;
        $this->template->item = $overviewFilter->item ? $this->orm->stockItems->getById($overviewFilter->item) : null;
    }

    public function handleSetOverviewGrid(int $type): void
    {
        $this->session->getSection($this->getName())->gridType = $type;
        $this->redrawControls(['overviewFilter', 'overviewTable']);
    }

    public function renderOverviewGrid(): void
    {
        $this->template->gridType = $this->getOverviewGridType();
    }

    protected function getOverviewGridType(): int
    {
        return $this->session->getSection($this->getName())->gridType ?? self::COMPANY_GRID;
    }

    /** Analyaza odberu obchodnich zastupcu */
    protected function createComponentDealerOverview(): DealerOverview
    {
        return new DealerOverview($this->orm);
    }

    /** Analyaza odberu obchodnich zastupcu - filtr */
    protected function createComponentDealerOverviewFilter(): DealerOverviewFilter
    {
        return new DealerOverviewFilter($this->orm);
    }

    /** Grid s obchodnimi zastupci */
    protected function createComponentDealers(): BaseDatagrid
    {
        $dealerRole = $this->orm->roles->getBy(['name' => Role::DEALER]);
        $grid = $this->datagridFactory->create($this->orm->users);
        $grid->addCellsTemplate(__DIR__ . '/../templates/Dealer/dealers.grid.cells.latte');
        $grid->settings->setFulltextColumns(['name', 'internalLogin'])
            ->setDataSourceFilter(['roles->id' => $dealerRole->id ?? 0]);

        $grid->addColumn('name', 'Jméno')->enableSort(BaseDatagrid::ORDER_ASC);
        $grid->addColumn('internalLogin', 'JaS login');

        $grid->addTopAction('updatePartners', 'Aktualizovat partnery z MOP');
        $grid->addRowAction('partners', 'Partneři obchodníka', 'search');
        $grid->addRowAction('exportPartners', 'Export partnerů', 'download');

        return $grid;
    }

    /** Grid s obchodnimi partnery vybraneho zastupce */
    protected function createComponentDealerPartners(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->companyDepots, $this->getParameter('id'));
        $grid->addCellsTemplate(__DIR__ . '/../templates/Dealer/grid.cells.latte');
        $grid->settings->setFulltextColumns(['companyName', 'companyIcoString', 'title'])
            ->showCheckboxes()
            ->setOnCheckCallback([$this, 'selectDealerPartner'])
            ->setForceOrder(['voj' => ICollection::ASC, 'store' => ICollection::ASC])
            ->setDataSourceFilter(DepotRepository::DEALER_FILTER);

        $grid->addColumn('companyIcoString', 'ICO')->enableSort();
        $grid->addColumn('companyName', 'Název')->enableSort(BaseDatagrid::ORDER_ASC);
        $grid->addColumn('voj', 'voj');
        $grid->addColumn('title', 'Popis');
        $grid->addColumn('city', 'Město');
        $grid->addColumn('store', 'JaS pobočka');
        $grid->addColumn('group', 'Skupina')->enableSort();

        $grid->addTopAction('importPartners', 'Import partnerů', ['id' => $this->getParameter('id')]);

        $grid->setFilterFormFactory(function (): FilterContainer {
            $storeOption = $this->orm->stores->loadStoresWithMainStorage();
            $groupOption = $this->orm->companyGroups->findAll()->orderBy('number')
                ->fetchPairs('number', 'numberString');

            $form = new FilterContainer();
            $form->addMultiSelect('store', 'JaS pobočka', $storeOption)
                ->setDefaultValue([
                    in_array($this->selectedStore, Store::MAIN_STORAGES)
                        ? Store::MAIN_STORAGE
                        : $this->selectedStore
                ]);
            $form->addMultiSelect('group', null, $groupOption);
            return $form;
        });

        return $grid;
    }

    /** Grid s obchodnimi partnery s daty o odberech */
    protected function createComponentDealerOverviewCompanyGrid(): BaseDatagrid
    {
        $dealerOverviewFilter = $this['dealerOverviewFilter']->getDataFilter();

        if ($dealerOverviewFilter->company) {
            $gridFilter = ['id' => $dealerOverviewFilter->company];
        } else {
            $gridFilter = [
                'ico!=' => [0, Store::INTERNAL_ICO],
                'depots->group->number>' => 0,
                'depots->dealers->id' => $dealerOverviewFilter->dealers ?: [0]
            ];
        }

        $grid = $this->datagridFactory->create($this->orm->companies);
        $grid->settings->setDataSourceFilter($gridFilter)
            ->hideSettings()
            ->showExport('', '', 'exportOverviewGrid!');

        if ($dealerOverviewFilter->years) {
            $grid->setDataSourceCallback(
                fn(array $filter, array $order, Paginator $paginator) =>
                    $this->orm->companies->getMapper()->loadDealerOverviewGridData(
                        $dealerOverviewFilter,
                        $paginator,
                        self::COMPANY_GRID
                    )
            );

            $grid->setColumnGetterCallback(fn(Row $row, string $column) => $this->getOverviewGridValue(
                $row, $column, end($dealerOverviewFilter->years)
            ));
        }

        $grid->addColumn('companyName', 'Název');
        $tempFilter = $this['dealerOverviewFilter']->getDataFilter();
        $compareYear = end($dealerOverviewFilter->years);
        $compareSum = null;

        if ($compareYear) {
            $tempFilter->years = [$compareYear];
            $compareSum = array_sum($this->orm->companies->getMapper()->loadDealerOverviewGridData($tempFilter, null, self::COMPANY_GRID));
        }

        foreach ($dealerOverviewFilter->years as $year) {
            $tempFilter->years = [$year];
            $yearSum = $year === $compareYear
                ? $compareSum
                : array_sum($this->orm->companies->getMapper()->loadDealerOverviewGridData($tempFilter, null, self::COMPANY_GRID));
            $yearSumString = number_format($yearSum, 0, ',', ' ');

            if ($compareSum && $year !== $compareYear) {
                $diff = number_format((abs($compareSum - $yearSum) / $compareSum) * 100) . '%';
                $yearSumString .= ($compareSum > $yearSum ? ' -' : ' +') . $diff;
            }

            $grid->addColumn("$year", "$year ($yearSumString)");
        }

        return $grid;
    }

    /** Grid s obchodnimi partnery s daty o odberech */
    protected function createComponentDealerOverviewDepotGrid(): BaseDatagrid
    {
        $dealerOverviewFilter = $this['dealerOverviewFilter']->getDataFilter();

        if ($dealerOverviewFilter->depot) {
            $gridFilter = ['id' => $dealerOverviewFilter->depot];
        } else {
            if ($dealerOverviewFilter->company) {
                $gridFilter = [
                    'company->id' => $dealerOverviewFilter->company,
                    'group->number>' => 0
                ];
            } else {
                $gridFilter = DepotRepository::DEALER_FILTER;
            }

            $gridFilter['dealers->id'] = $dealerOverviewFilter->dealers ?: [0];
            $gridFilter['store->id'] = Store::OSTRAVA_MAIN_STORAGE;
        }

        $grid = $this->datagridFactory->create($this->orm->companyDepots);
        $grid->settings->setDataSourceFilter($gridFilter)
            ->hideSettings()
            ->showExport('', '', 'exportOverviewGrid!');

        if ($dealerOverviewFilter->years) {
            $grid->setDataSourceCallback(
                fn(array $filter, array $order, Paginator $paginator) =>
                    $this->orm->companies->getMapper()->loadDealerOverviewGridData(
                        $dealerOverviewFilter,
                        $paginator,
                        self::DEPOT_GRID
                    )
            );

            $grid->setColumnGetterCallback(fn(Row $row, string $column) => $this->getOverviewGridValue(
                $row, $column, end($dealerOverviewFilter->years)
            ));
        }

        $grid->addColumn('depotName', 'Název');
        $tempFilter = $this['dealerOverviewFilter']->getDataFilter();
        $compareYear = end($dealerOverviewFilter->years);
        $compareSum = null;

        if ($compareYear) {
            $tempFilter->years = [$compareYear];
            $compareSum = array_sum($this->orm->companies->getMapper()->loadDealerOverviewGridData($tempFilter, null, self::DEPOT_GRID));
        }

        foreach ($dealerOverviewFilter->years as $year) {
            $tempFilter->years = [$year];
            $yearSum = $year === $compareYear
                ? $compareSum
                : array_sum($this->orm->companies->getMapper()->loadDealerOverviewGridData($tempFilter, null, self::DEPOT_GRID));
            $yearSumString = number_format($yearSum, 0, ',', ' ');

            if ($compareSum && $year !== $compareYear) {
                $diff = number_format((abs($compareSum - $yearSum) / $compareSum) * 100) . '%';
                $yearSumString .= ($compareSum > $yearSum ? ' -' : ' +') . $diff;
            }

            $grid->addColumn("$year", "$year ($yearSumString)");
        }

        return $grid;
    }

    /** Form na import partneru k obchodnimu zastupci */
    public function createComponentDealerImportForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addUpload('dealerImport', 'CSV soubor s partnery')
            ->setRequired()
            ->addRule(BaseForm::PATTERN, 'Pouze csv soubor', '.*\.csv$');
        $form->addSubmit('import', 'Importovat');

        $form->onSuccess[] = function (array $values): void {
            $dealer = $this->orm->users->getById($this->getParameter('id'));
            $error = $this->importDealerPartners($dealer, $values['dealerImport']->contents);

            if ($error) {
                $this->flashMessage($error, self::MSG_ERROR);
                $this->redirect('this');
            }

            $this->flashMessage('Import proběhl úspěšně');
            $this->redirect('partners', ['id' => $this->getParameter('id')]);
        };

        return $form;
    }

    /** Callback pro vyber pobocky partnera k obchodnimu zastupci */
    public function selectDealerPartner(int $depotId, bool $selected): void
    {
        $depot = $this->orm->companyDepots->getById($depotId);
        $dealer = $this->orm->users->getById($this->getParameter('id'));

        if (!$depot || !$dealer) {
            $this->error('Položna nenalezena');
        }

        $hasDepot = !is_null($dealer->depots->toCollection()->getById($depotId));

        if ($selected && !$hasDepot) {
            $dealer->depots->add($depot);
            $this->orm->users->persistAndFlush($dealer);
        }

        if (!$selected && $hasDepot) {
            $dealer->depots->remove($depot);
            $this->orm->users->persistAndFlush($dealer);
        }
    }

    /** Zpracovani CSV souboru s partnery obchodniho zastupce */
    private function importDealerPartners(User $dealer, string $fileContent): string
    {
        if (!$fileContent) {
            return 'Soubor neobsahuje žádná data';
        }

        $depots = $this->orm->companyDepots->loadStorageDepots();
        $importDepots = [];
        $separator = "\r\n";
        $line = strtok($fileContent, $separator);

        while ($line !== false) {
            $csvData = str_getcsv($line, ';');
            $store = (int) trim($csvData[0] ?? '');
            $ico = (int) trim($csvData[1] ?? '');
            $voj = trim($csvData[2] ?? '');
            $importKeys = [];

            if ($store === Store::MAIN_STORAGE) {
                $importKeys[] = Store::OSTRAVA_MAIN_STORAGE . BaseMapper::DATA_STRING_SEPARATOR . $ico . BaseMapper::DATA_STRING_SEPARATOR . $voj;
                $importKeys[] = Store::HLUCIN_MAIN_STORAGE . BaseMapper::DATA_STRING_SEPARATOR . $ico . BaseMapper::DATA_STRING_SEPARATOR . $voj;
            } else {
                $importKeys[] = $store . BaseMapper::DATA_STRING_SEPARATOR . $ico . BaseMapper::DATA_STRING_SEPARATOR . $voj;
            }

            foreach ($importKeys as $importId) {
                if (!isset($depots[$importId])) {
                    return 'Chyba: Nenalezena partnerská pobočka dle řádku ' . implode(';', $csvData);
                }

                $importDepots[$depots[$importId]] = $depots[$importId];
            }

            $line = strtok($separator);
        }

        $dealerDepots = $dealer->depots->toCollection()->fetchPairs('id', 'id');

        foreach ($importDepots as $depotId) {
            if (!isset($dealerDepots[$depotId])) {
                $dealer->depots->add($depotId);
            } else {
                unset($dealerDepots[$depotId]);
            }
        }

        foreach ($dealerDepots as $depotId) {
            $dealer->depots->remove($depotId);
        }

        $this->orm->users->persistAndFlush($dealer);

        return '';
    }
}
