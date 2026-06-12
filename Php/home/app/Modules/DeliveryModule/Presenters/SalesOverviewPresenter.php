<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Core\Utils\DateTime;
use App\Modules\CliModule\Service\SalesDataImporter;
use App\Modules\DeliveryModule\Component\SalesOverview;
use App\Modules\DeliveryModule\Component\StoreOverview;
use App\Modules\DeliveryModule\Component\StoreOverviewFilter;
use App\Modules\DeliveryModule\Orm\DeliveryNotes\DeliveryNote;
use App\Modules\DeliveryModule\Orm\SalesData\SalesDataAccess;
use App\Modules\DeliveryModule\Orm\SalesData\SalesDataRepository;
use App\Modules\DeliveryModule\Presenters\Traits\OverviewExportTrait;
use App\Modules\Presenters\SecurePresenter;
use App\Modules\SystemModule\Orm\Stores\Store;
use Nette\Http\FileUpload;
use Nette\Utils\Paginator;
use Nextras\Dbal\Result\Row;
use Nextras\Dbal\Utils\DateTimeImmutable;
use Nextras\Orm\Collection\ICollection;
use XSuchy09\Application\Responses\CsvResponse;

/** Presenter pro prezentaci dat prodeje prodejen */
final class SalesOverviewPresenter extends SecurePresenter
{
    use OverviewExportTrait;

    /** @inject */
    public SalesDataImporter $salesImporter;

    public array $titles = [
        'default' => 'Analýza prodeje',
        'import' => 'Analýza prodeje - Import',
        'dataAccess' => 'Analýza - Viditelnost',
        'overview' => 'Analýza odběrů',
        'overviewGrid' => 'Analýza odběrů'
    ];

    /** Nastaveni pristupu uzivatelu k datum analyzy */
    public function actionDataAccess(): void
    {
        $defaults = [];

        foreach (array_keys(SalesOverview::SALE_GROUPS) as $storeId) {
            $defaults[$storeId] = $this->orm->salesDataAccess->findBy(['store' => $storeId])->fetchPairs(null, 'user->id');
        }

        $defaults[SalesDataAccess::DATA_UPDATE_NOTIFICATION] = $this->orm->salesDataAccess->findBy(
            ['store' => SalesDataAccess::DATA_UPDATE_NOTIFICATION]
        )->fetchPairs(null, 'user->id');

        $this['dataAccessForm']->setDefaults($defaults);
    }

    /** Vypis prodejnich dokladu prodeju ve zvolenem mesici */
    public function actionSalesSumCheck(int $store, int $year = null, int $month = null): void
    {
        $year ??= (int) date('Y');
        $month ??= (int) date('n');

        $this->template->year = $year;
        $this->template->month = DateTime::CZ_MONTHS[$month];
        $this->template->store = SalesOverview::SALE_GROUPS[$store];
        $this->template->notes = $this->loadSalesNotes($store, $year, $month);
    }

    /** Odesle notifikace o aktualizaci dat o prodejich */
    public function actionSendUpdateNotification(): void
    {
        foreach ($this->orm->salesDataAccess->findBy(['store' => SalesDataAccess::DATA_UPDATE_NOTIFICATION]) as $access) {
            $this->mailer->sendUpdateSalesDataNotification($access->user);
        }

        $this->flashMessage('Notifikace o aktualizaci dat byly odeslány');
        $this->redirect('default');
    }

    /** Export vypisu prodejnich dokladu prodeju ve zvolenem mesici */
    public function handleExportSumCheck(int $store, int $year, int $month): void
    {
        $notes = $this->loadSalesNotes($store, $year, $month);
        $export = [];

        /** @var DeliveryNote $note */
        foreach ($notes as $note) {
            $line = [];
            $line['Pobočka'] = $note->store->name;
            $line['Pohyb'] = $note->movementNumber;
            $line['Číslo dokladu'] = $note->number;
            $line['Suma'] = str_replace('.', ',', (string) $note->sellSum);
            $export[] = $line;
        }

        $storeTitle = str_replace('.', '', SalesOverview::SALE_GROUPS[$store]);
        $response = new CsvResponse($export, "Prodej-$storeTitle-$month-$year.csv");
        $response->setDelimiter(';');
        $this->sendResponse($response);
    }

    /** Analyaza odberu pobocky */
    protected function createComponentStoreOverview(): StoreOverview
    {
        return new StoreOverview($this->orm);
    }

    /** Analyaza odberu pobocky - filtr */
    protected function createComponentStoreOverviewFilter(): StoreOverviewFilter
    {
        return new StoreOverviewFilter($this->orm);
    }

    /** Analyaza odberu pobocky - grid */
    protected function createComponentStoreOverviewGrid(): BaseDatagrid
    {
        $storeOverviewFilter = $this['storeOverviewFilter']->getDataFilter();

        if ($storeOverviewFilter->company) {
            $gridFilter = ['id' => $storeOverviewFilter->company];
        } else {
            $gridFilter = [
                'ico!=' => [0, Store::INTERNAL_ICO],
                'depots->group->number>' => 0,
                'depots->store->id' => $storeOverviewFilter->store ?: $this->orm->stores->loadSimpleStoreIds()
            ];

            if ($storeOverviewFilter->oz || $storeOverviewFilter->store === Store::OSTRAVA_MAIN_STORAGE) {
                $gridFilter['depots->group->number'] = $storeOverviewFilter->oz === 1
                    ? SalesDataRepository::STORE_OZ_1_GROUP
                    : SalesDataRepository::STORE_OZ_2_GROUP;
            }
        }

        $grid = $this->datagridFactory->create($this->orm->companies);
        $grid->settings->setDataSourceFilter($gridFilter)
            ->hideSettings()
            ->showExport('', '', 'exportOverviewGrid!');

        if ($storeOverviewFilter->years) {
            $grid->setDataSourceCallback(
                fn(array $filter, array $order, Paginator $paginator) =>
                    $this->orm->companies->getMapper()->loadStoreOverviewGridData($storeOverviewFilter, $paginator)
            );
            $grid->setColumnGetterCallback(fn(Row $row, string $column) => $this->getOverviewGridValue(
                $row, $column, end($storeOverviewFilter->years)
            ));

        }
        $grid->addColumn('companyName', 'Název');

        foreach ($storeOverviewFilter->years as $year) {
            $grid->addColumn("$year", $year);
        }

        return $grid;
    }

    /** Analyza prodeje prodejen */
    protected function createComponentSalesOverview(): SalesOverview
    {
        $storeAccess = $this->getUser()->isSuperAdmin()
            ? array_keys(SalesOverview::SALE_GROUPS)
            : $this->orm->salesDataAccess->loadStoreAccess($this->getUser()->getId());
        return new SalesOverview($this->orm, $storeAccess);
    }

    /** Formular pro vlozeni csv souboru s nastavenim povinneho sortimentu */
    protected function createComponentSalesDataImportForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addUpload('import', 'Soubor k importu')
            ->setRequired()
            ->addRule(BaseForm::PATTERN, 'Pouze csv soubor', '.*\.csv$');
        $form->addSubmit('send', 'Importovat');

        $form->onSuccess[] = function (array $values): void {
            /** @var FileUpload $fileUpload */
            $fileUpload = $values['import'];
            $error = $this->salesImporter->importSalesData($fileUpload->contents);

            if ($error) {
                $this->flashMessage($error, self::MSG_ERROR);
                $this->redirect('this');
            }

            $this->orm->salesData->getMapper()->updateDifferences((int) date("Y"));

            $this->flashMessage('Import proběhl úspěšně');
            $this->redirect('default');

        };
        return $form;
    }

    /** Formular pro nastaveni pristupu uzivatelu k datum analyzy */
    protected function createComponentDataAccessForm(): BaseForm
    {
        $users = $this->orm->users->findAll()->orderBy('name')->fetchPairs('id', 'name');
        unset($users[1]); // Admin user
        $form = new BaseForm();

        foreach (SalesOverview::SALE_GROUPS as $storeId => $name) {
            $form->addMultiSelect((string) $storeId, $name, $users);
        }

        $form->addMultiSelect((string) SalesDataAccess::DATA_UPDATE_NOTIFICATION, 'Notifikace o aktualizaci dat', $users);

        $form->addSubmit('save', 'Uložit');

        $form->onSuccess[] = function (array $values): void {
            $accessList = [];
            $this->orm->salesDataAccess->beginTransaction();
            $this->orm->salesDataAccess->truncateTable();

            foreach ($values as $storeId => $users) {
                foreach ($users as $userId) {
                    $accessList[] = [
                        'store' => $storeId,
                        'user' => $userId
                    ];
                }
            }

            if ($accessList) {
                $this->orm->salesDataAccess->insertItems($accessList);
            }

            $this->orm->salesDataAccess->commitTransaction();
            $this->flashMessage('Nastavení přístupu k datům analýzy bylo upraveno');
            $this->redirect('default');
        };

        return $form;
    }

    private function loadSalesNotes(int $store, int $year, int $month): ICollection
    {
        $date = DateTimeImmutable::createFromFormat('j.n.Y', "1.$month.$year");

        switch ($store) {
            case 90:
                // Velkoobchod - jde o soucet Velkoobchod 1 a Velkoobchod 2
                $notesCollection = $this->orm->deliveryNotes->findBy([
                    'movementType' => [DeliveryNote::TYPE_SALE, DeliveryNote::TYPE_CANCEL],
                    'date>=' => $date->format(DateTime::DB_DATE),
                    'date<' => $date->modify('+1 month')->format(DateTime::DB_DATE),
                    'store->id' => Store::MAIN_STORAGES,
                    'depot->group->number' => array_merge(SalesDataRepository::MAIN_STORAGE_1_GROUPS, SalesDataRepository::MAIN_STORAGE_2_GROUPS)
                ]);
                break;
            case 91:
                // Velkoobchod 1
                $notesCollection = $this->orm->deliveryNotes->findBy([
                    'movementType' => [DeliveryNote::TYPE_SALE, DeliveryNote::TYPE_CANCEL],
                    'date>=' => $date->format(DateTime::DB_DATE),
                    'date<' => $date->modify('+1 month')->format(DateTime::DB_DATE),
                    'store->id' => Store::MAIN_STORAGES,
                    'depot->group->number' => SalesDataRepository::MAIN_STORAGE_1_GROUPS
                ]);
                break;
            case 92:
                $notesCollection = $this->orm->deliveryNotes->findBy([
                    'movementType' => [DeliveryNote::TYPE_SALE, DeliveryNote::TYPE_CANCEL],
                    'date>=' => $date->format(DateTime::DB_DATE),
                    'date<' => $date->modify('+1 month')->format(DateTime::DB_DATE),
                    'store->id' => Store::MAIN_STORAGES,
                    'depot->group->number' => SalesDataRepository::MAIN_STORAGE_2_GROUPS
                ]);
                break;
            case 99:
                // Eshop
                $notesCollection = $this->orm->deliveryNotes->findBy([
                    'movementType' => [DeliveryNote::TYPE_SALE, DeliveryNote::TYPE_CANCEL],
                    'date>=' => $date->format(DateTime::DB_DATE),
                    'date<' => $date->modify('+1 month')->format(DateTime::DB_DATE),
                    'store->id' => Store::MAIN_STORAGES,
                    'depot->group->number' => SalesDataRepository::ESHOP_GROUPS
                ]);
                break;
            case 4:
                // Ostrava
                $notesCollection = $this->orm->deliveryNotes->findBy([
                    ICollection::OR,
                    [
                        ICollection::AND,
                        'movementType' => [DeliveryNote::TYPE_SALE, DeliveryNote::TYPE_CANCEL],
                        'date>=' => $date->format(DateTime::DB_DATE),
                        'date<' => $date->modify('+1 month')->format(DateTime::DB_DATE),
                        'store->id' => Store::MAIN_STORAGES,
                        'depot->group->number' => array_merge(SalesDataRepository::OSTRAVA_GROUPS, [intval('9' . $store)])
                    ],
                    [
                        ICollection::AND,
                        'movementType' => [DeliveryNote::TYPE_SALE, DeliveryNote::TYPE_CANCEL],
                        'date>=' => $date->format(DateTime::DB_DATE),
                        'date<' => $date->modify('+1 month')->format(DateTime::DB_DATE),
                        'store->id' => $store
                    ],
                    [
                        ICollection::AND,
                        'movementNumber' => SalesDataRepository::NO_COMPANY_MOVEMENT_NUMBERS,
                        'date>=' => $date->format(DateTime::DB_DATE),
                        'date<' => $date->modify('+1 month')->format(DateTime::DB_DATE),
                        'store->id' => Store::MAIN_STORAGES
                    ]
                ]);
                break;
            default:
                if ($store == 401) {
                    $notesCollection = $this->orm->deliveryNotes->findBy([
                        'movementType' => [DeliveryNote::TYPE_SALE, DeliveryNote::TYPE_CANCEL],
                        'date>=' => $date->format(DateTime::DB_DATE),
                        'date<' => $date->modify('+1 month')->format(DateTime::DB_DATE),
                        'store->id' => [4, 9, 10],
                        'depot->group->number' => [33, 88]
                    ]);
                }
                elseif ($store > 100) {
                        $storeIdSplit = str_split((string) $store);
                        $ozType = (int) array_pop($storeIdSplit);
                        $storeId = array_shift($storeIdSplit);
                        $storeIdSupplement = array_shift($storeIdSplit);
                        if ($storeIdSupplement) {
                            $storeId .= $storeIdSupplement;
                        }
                        $storeId = intval($storeId);
                        $groups = $ozType === 1 ? SalesDataRepository::STORE_OZ_1_GROUP : SalesDataRepository::STORE_OZ_2_GROUP;
                        $notesCollection = $this->orm->deliveryNotes->findBy([
                            'movementType' => [DeliveryNote::TYPE_SALE, DeliveryNote::TYPE_CANCEL],
                            'date>=' => $date->format(DateTime::DB_DATE),
                            'date<' => $date->modify('+1 month')->format(DateTime::DB_DATE),
                            'store->id' => $storeId,
                            'depot->group->number' => $groups
                        ]);
                } else {
                    $notesCollection = $this->orm->deliveryNotes->findBy([
                        ICollection::OR,
                        [
                            ICollection::AND,
                            'movementType' => [DeliveryNote::TYPE_SALE, DeliveryNote::TYPE_CANCEL],
                            'date>=' => $date->format(DateTime::DB_DATE),
                            'date<' => $date->modify('+1 month')->format(DateTime::DB_DATE),
                            'store->id' => Store::MAIN_STORAGES,
                            'depot->group->number' => [intval('9' . $store)]
                        ],
                        [
                            ICollection::AND,
                            'movementType' => [DeliveryNote::TYPE_SALE, DeliveryNote::TYPE_CANCEL],
                            'date>=' => $date->format(DateTime::DB_DATE),
                            'date<' => $date->modify('+1 month')->format(DateTime::DB_DATE),
                            'store->id' => $store
                        ]
                    ]);
                }

        }

        return $notesCollection->orderBy([
            'store->id' => ICollection::ASC,
            'movementType' => ICollection::ASC,
            'movementNumber' => ICollection::ASC,
            'number' => ICollection::ASC
        ]);
    }

    public function actionSumCheck(int $producer, int $year, int $month): void
    {
        $overviewFilter = $this['storeOverview']['overviewFilter']->getDataFilter();
        $date = \DateTime::createFromFormat('Y-n-d', "$year-$month-01");

        $baseFilter = [
            'note->movementType' => [DeliveryNote::TYPE_SALE, DeliveryNote::TYPE_CANCEL],
            'note->date>=' => $date->format(DateTime::DB_DATE),
            'note->date<=' => $date->modify('last day of this month')->format(DateTime::DB_DATE),
            'item->item->group->id' => $overviewFilter->getStockGroupFilter($producer)
        ];

        if ($overviewFilter->company) {
            $baseFilter['note->depot->company->id'] = $overviewFilter->company;
        }

        if ($overviewFilter->series) {
            $baseFilter['item->item->series->id'] = $overviewFilter->series;
        }

        if ($overviewFilter->item) {
            $baseFilter['item->item->id'] = $overviewFilter->item;
        }

        if ($overviewFilter->oz || $overviewFilter->store === Store::OSTRAVA_MAIN_STORAGE) {
            $storeFilter = [
                ICollection::AND,
                'note->store->id' => $overviewFilter->store,
                'note->depot->group->number' => $overviewFilter->oz === 1
                    ? SalesDataRepository::STORE_OZ_1_GROUP
                    : SalesDataRepository::STORE_OZ_2_GROUP
            ];
        } else {
            $storeGroups = [intval('9' . $overviewFilter->store)];

            if ($overviewFilter->store === Store::OSTRAVA) {
                $storeGroups = array_merge($storeGroups, SalesDataRepository::OSTRAVA_GROUPS);
            }

            $storeFilter = [
                ICollection::OR,
                [
                    ICollection::AND,
                    'note->store->id' => $overviewFilter->store
                ],
                [
                    ICollection::AND,
                    'note->store->id' => Store::MAIN_STORAGES,
                    'note->depot->group->number' => $storeGroups
                ]
            ];

            if ($overviewFilter->store === Store::OSTRAVA) {
                $storeFilter[] = [
                    ICollection::AND,
                    'note->store->id' => Store::MAIN_STORAGES,
                    'note->movementNumber' => SalesDataRepository::NO_COMPANY_MOVEMENT_NUMBERS
                ];
            }
        }

        $filter = [
            ICollection::AND,
            $baseFilter,
            $storeFilter
        ];

        $this->template->noteItems = $overviewFilter->company >= 0 && $overviewFilter->store
            ? $this->orm->deliveryNoteItems->findBy($filter)->orderBy('note->number')->fetchAll()
            : [];
        $this->template->year = $year;
        $this->template->month = DateTime::CZ_MONTHS[$month] ?? '-';
        $this->template->producer = $this->orm->producers->getById($producer)->name ?? '???';
        $this->template->company = $overviewFilter->company ? $this->orm->companies->getById($overviewFilter->company) : null;
        $this->template->series = $overviewFilter->series ? $this->orm->stockSeries->getById($overviewFilter->series) : null;
        $this->template->item = $overviewFilter->item ? $this->orm->stockItems->getById($overviewFilter->item) : null;
    }
}
