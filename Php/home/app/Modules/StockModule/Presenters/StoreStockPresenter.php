<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Core\Component\Form\FilterContainer;
use App\Core\Exporter\Exporter;
use App\Modules\CliModule\Service\WarehouseImporter;
use App\Modules\Presenters\SecurePresenter;
use App\Modules\StockModule\Orm\ObligatoryItems\ObligatoryItem;
use App\Modules\StockModule\Orm\StockItems\Unit;
use Nextras\Orm\Collection\ICollection;

/** Presenter pro praci se sortimentem vybrane pobocky */
final class StoreStockPresenter extends SecurePresenter
{
    private ICollection $obligatoryItemsToOrder;

    public array $titles = [
        'default' => 'Skladové zásoby pobočky',
        'obligatoryStock' => 'Povinný sortiment pobočky',
        'orderItems' => 'Objednat povinný sortiment',
        'missingObligatorySectors' => 'Chybějící sektory',
        'obligatoryItemSectors' => 'Sektory sortimentu'
    ];

    /** Aktualni pocty jednotlivych variant vsech polozek sortimentu na zvolene pobocce */
    public function renderDefault(int $id): void
    {
        $store = $this->orm->stores->getById($id);
        if (!$store) {
            $this->error('Položka nenalezena');
        }
        $this->template->store = $store;
    }

    /** Vypis mnozstvi povinneho sortimentu na zvolene pobocce */
    public function actionObligatoryStock(): void
    {
        $store = $this->orm->stores->getById($this->selectedStore);
        if (!$store) {
            $this->error('Položka nenalezena');
        }
        $this->orm->obligatoryItems->setStore($this->selectedStore);

        $import = $this->orm->imports->getImportByName("Skladové položky - $store->name");

        if ($import) {
            $this->template->importAt = $import->date;
            $this->template->nextImportAt = WarehouseImporter::getNextStockItemsImport($this->selectedStore, $import->date);
        }
    }

    /** Objednavka zvolenych polozek povinneho sortimentu */
    public function actionOrderItems(): void
    {
        $sysUser = $this->getSysUser();

        if (!$sysUser->internalLogin) {
            // Pro odeslani objednavky je potreba Jas Login
            $this->flashMessage('Pouze uživatel s nastaveným JaS loginem může objednávat', self::MSG_ERROR);
            $this->redirect('obligatoryStock');
        }

        if ($sysUser->store->id !== $this->selectedStore) {
            $this->flashMessage('Nelze objednavat zbozi z jine pobocky', self::MSG_ERROR);
            $this->redirect('obligatoryStock');
        }

        $selectedItems = $this->getSession('storeObligatoryItems_datagrid')->selectedRows ?? [];

        if (!$selectedItems) {
            $this->flashMessage('Nejsou vybrané žádné položky');
            $this->redirect('obligatoryStock');
        }

        $this->orm->obligatoryItems->setStore($this->selectedStore);
        $this->obligatoryItemsToOrder = $this->orm->obligatoryItems->findBy(['id' => $selectedItems]);
        $this->template->selectedItemsToOrder = $this->obligatoryItemsToOrder;
    }

    /** Odstraneni polozky povinneho sortimentu z objednavaciho formulare */
    public function handleRemoveFormItem(int $id): void
    {
        $datagridSession = $this->getSession('storeObligatoryItems_datagrid');
        $selectedItems = $datagridSession->selectedRows;
        unset($selectedItems[$id]);
        $datagridSession->selectedRows = $selectedItems;
        $this->sendJson(['status' => 200, 'text' => 'OK']);
    }

    /** Seznam variant zbozi povinneho sortimentu bez zadaneho sektoru */
    public function actionMissingObligatorySectorPreview(int $id): void
    {
        $item = $this->orm->stockItems->getById($id);
        if (!$item) {
            $this->error('Položka nenalezena');
        }
        $this->template->stockItem = $item;
        $this->template->stockVariants = $item->variants->toCollection()->findBy([
            'deleted' => false,
            'sample' => false,
            'store->id' => $this->selectedStore,
            'sector->id' => null
        ]);
        $this->sideDialogAjaxHandler();
    }

    /** Datagrid poctu jednotlivych variant zbozi na zvolene prodejne */
    protected function createComponentStoreItems(): BaseDatagrid
    {
        $storeId = $this->getParameter('id');
        $grid = $this->datagridFactory->create($this->orm->stockVariants, $storeId);
        $grid->settings->setDataSourceFilter([
            'store' => $storeId,
            'quantity>' => 0
        ]);
        $grid->settings->setFulltextColumns(['regNumber', 'name', 'catalogTitle']);
        $grid->setMultiWordSearch();
        $grid->addColumn('regNumber', 'Registrační číslo')->enableSort(BaseDatagrid::ORDER_ASC);
        $grid->addColumn('producer', 'Výrobce')->enableSort();
        $grid->addColumn('series', 'Série');
        $grid->addColumn('name', 'Název')->enableSort();
        $grid->addColumn('catalogTitle', 'Katalogové číslo')->enableSort();
        $grid->addColumn('remark', 'Varianta');
        $grid->addColumn('quantity', 'Množství')->enableSort();
        $grid->addColumn('unit', 'Jednotka');
        $grid->addColumn('sectorName', 'Sektor')->enableSort();

        $grid->setFilterFormFactory(function (): FilterContainer {
            $producers = $this->orm->producers->loadProducerFilterOption();
            $series = $this->orm->stockSeries->findAll()->orderBy('name')->fetchPairs('id', 'name');
            $sectors = $this->orm->stockSectors->findBy(['store->id' => $this->getParameter('id')])
                ->orderBy('name')
                ->fetchPairs('id', 'name');

            $form = new FilterContainer();
            $form->addSelect('producerId', 'Výrobce', ['' => 'Vše'] + $producers);
            $form->addSelect('series', 'Série', ['' => 'Vše'] + $series);
            $form->addSelect('sector', 'Sektor', ['' => 'Vše'] + $sectors);
            return $form;
        });

        return $grid;
    }

    /** Datagrid poctu povinnych polozek sortimentu na zvolene pobocce */
    protected function createComponentStoreObligatoryItems(): BaseDatagrid
    {
        $canUserOrderItems = ($this->getSysUser()->store->id ?? 0) === $this->selectedStore
            && $this->getUser()->isAllowed(':Stock:StoreStock:orderItems');
        $grid = $this->datagridFactory->create($this->orm->obligatoryItems);
        $grid->settings->setFulltextColumns(['name', 'regNumber', 'catalog'])
            ->showExport();
        $grid->setMultiWordSearch();

        if ($canUserOrderItems) {
            $grid->settings->showCheckboxes();
            $grid->addTopAction('orderItems', 'Objednat vybrané');
        }

        $grid->addColumn('regNumber', 'Registrační číslo')->enableSort(BaseDatagrid::ORDER_ASC);
        $grid->addColumn('name', 'Název')->enableSort();
        $grid->addColumn('producer', 'Výrobce')->enableSort();
        $grid->addColumn('series', 'Série');
        $grid->addColumn('catalog', 'Katalogové číslo');
        $grid->addColumn('storeQuantity', 'Zásoba')->enableSort();
        $grid->addColumn('quantity', 'Minimum')->enableSort();
        $grid->addColumn('orderSum', 'Objednáno')->enableSort();
        $grid->addColumn('minOrder', 'Min. objednavka');
        $grid->addColumn('unit', 'Jednotka');

        $grid->addLegend('Podlimitní množství', 'legend_orange', "\$belowLimit == 1 && \$hasOrder == 0");
        $grid->addLegend('Objednáno', 'legend_azure', "\$hasOrder == 1");

        $grid->setFilterFormFactory(function (): FilterContainer {
            $producers = $this->orm->obligatoryItems->loadProducersForFilter();
            $series = $this->orm->obligatoryItems->loadSeriesForFilter();
            $orderStates = [
                '' => 'Vše',
                '1' => 'Pouze objednané',
                '0' => 'Pouze neobjednané'
            ];
            $form = new FilterContainer();
            $form->addMultiSelect('producerId', 'Výrobce', $producers)
                ->checkDefaultValue(false)
                ->getControlPrototype()->addClass('multiple-select2');
            $form->addMultiSelect('series', 'Série', $series)
                ->checkDefaultValue(false)
                ->getControlPrototype()->addClass('multiple-select2');
            $form->addSelect('bellowLimit', 'Stav zásob', ['' => 'Vše', '1' => 'Pouze podlimitní množství']);
            $form->addSelect('hasOrder', 'Stav objednání', $orderStates);
            return $form;
        });

        return $grid;
    }

    /** Datagrid povinneho sortiment bez zadaneho sektoru */
    protected function createComponentStoreMissingObligatorySectors(): BaseDatagrid
    {
        $storeName = $this->orm->stores->getById($this->selectedStore)->name;
        $grid = $this->datagridFactory->create($this->orm->stockItems);
        $grid->settings->setDataSourceFilter([
            'variants->deleted=' => false,
            'variants->sample=' => false,
            'variants->store->id=' => $this->selectedStore,
            'variants->sector->id=' => null,
            'obligatoryItem->id!=' => null,
            'unit->name=' => 'm2'
        ])->setFulltextColumns(['name', 'regNumber', 'catalog'])
            ->showExport(Exporter::TYPE_XLS, "$storeName - povinný sortiment bez sektoru");
        $grid->setMultiWordSearch();

        $grid->addColumn('regNumber', 'Registrační číslo')->enableSort(BaseDatagrid::ORDER_ASC);
        $grid->addColumn('name', 'Název')->enableSort();
        $grid->addColumn('catalog', 'Katalogové číslo')->enableSort();

        $grid->addRowAction('missingObligatorySectorPreview', 'Varianty bez sektoru', 'search')
            ->setSideDialog();

        return $grid;
    }

    /** Datagrid povinneho sortiment se zadanymi sektory */
    protected function createComponentStoreObligatoryItemSectors(): BaseDatagrid
    {
        $this->orm->obligatoryItems->setStore($this->selectedStore);
        $storeName = $this->orm->stores->getById($this->selectedStore)->name;
        $grid = $this->datagridFactory->create($this->orm->stockItems);
        $grid->settings->setDataSourceFilter([
            'variants->deleted=' => false,
            'variants->sample=' => false,
            'variants->store->id=' => $this->selectedStore,
            'variants->sector->id!=' => null,
            'obligatoryItem->id!=' => null,
            'unit->name=' => 'm2'
        ])->setFulltextColumns(['name', 'regNumber', 'catalog'])
            ->showExport(Exporter::TYPE_XLS, "$storeName - povinný sortiment se sektory");
        $grid->setMultiWordSearch();

        $grid->addColumn('regNumber', 'Registrační číslo')->enableSort(BaseDatagrid::ORDER_ASC);
        $grid->addColumn('name', 'Název')->enableSort();
        $grid->addColumn('catalog', 'Katalogové číslo')->enableSort();
        $grid->addColumn('storeSectors', 'Sektor');

        return $grid;
    }

    /** Objednavaci formular zvolenych polozek povinneho sortimentu na dane prodejne */
    public function createComponentOrderItemsForm(): BaseForm
    {
        $getDefault = static function (float $minOrder, float $step): float {
            $default = $step;
            while ($minOrder > $default) {
                $default += $step;
            }
            return $default;
        };
        $form = new BaseForm();

        /** @var ObligatoryItem $obligatoryItem */
        foreach ($this->obligatoryItemsToOrder as $obligatoryItem) {
            $paletteQuantity = $obligatoryItem->item->palette ?? $obligatoryItem->minOrder;
            $packageQuantity = $obligatoryItem->item->package ?? 1;
            $isPaletteDefault = ($obligatoryItem->item->unit->name ?? null) === Unit::SQUARE_METER_UNIT;

            $itemContainer = $form->addContainer($obligatoryItem->id);

            $paletteInput = $itemContainer->addText('palette')
                ->setHtmlType('number')
                ->setHtmlAttribute('step', $paletteQuantity)
                ->setHtmlAttribute('min', 0);

            $packageInput = $itemContainer->addText('package')
                ->setHtmlType('number')
                ->setHtmlAttribute('step', $packageQuantity)
                ->setHtmlAttribute('min', 0);

            if ($isPaletteDefault) {
                $paletteInput->setDefaultValue($getDefault($obligatoryItem->minOrder, $paletteQuantity));
                $packageInput->setDefaultValue(0);
            } else {
                $paletteInput->setDefaultValue(0);
                $packageInput->setDefaultValue($getDefault($obligatoryItem->minOrder, $packageQuantity));
            }
        }

        $form->onValidate[] = function (BaseForm $form, array $values): void {
            /** @var ObligatoryItem $obligatoryItem */
            foreach ($this->obligatoryItemsToOrder as $obligatoryItem) {
                $orderSum = ($values[$obligatoryItem->id]['palette'] ?: 0) + ($values[$obligatoryItem->id]['package'] ?: 0);
                if ($orderSum < $obligatoryItem->minOrder) {
                    $form->addError("Položka $obligatoryItem->regNumber nemá objednáno minimální množství");
                }
            }
        };

        $form->addSubmit('order', 'Objednat');
        $form->onSuccess[] = [$this, 'orderItemsFormSuccess'];
        return $form;
    }

    public function orderItemsFormSuccess(array $values): void {
        $orderData = [];
        /** @var ObligatoryItem $obligatoryItem */
        foreach ($this->obligatoryItemsToOrder as $obligatoryItem) {
            $orderSum = ($values[$obligatoryItem->id]['palette'] ?: 0) + ($values[$obligatoryItem->id]['package'] ?: 0);
            $orderData[$obligatoryItem->item->regNumber] = $orderSum;
        }

        if (!isDevelopment()) {
            $error = $this->sendItemOrder($orderData);

            if ($error) {
                $this->flashMessage($error, self::MSG_ERROR);
                return;
            }
        }

        foreach ($this->obligatoryItemsToOrder as $obligatoryItem) {
            $orderSum = ($values[$obligatoryItem->id]['palette'] ?: 0) + ($values[$obligatoryItem->id]['package'] ?: 0);
            $this->orm->obligatoryItemOrders->insertEntity(null, [
                'obligatoryItem' => $obligatoryItem->id,
                'store' => $this->selectedStore,
                'orderSum' => $orderSum,
                'preOrderQuantity' => $obligatoryItem->storeQuantity
            ]);
        }

        unset($this->getSession('storeObligatoryItems_datagrid')->selectedRows);
        $this->flashMessage('Položky byly úspěšně objednány');
        $this->redirect('obligatoryStock');
    }

    /** Odeslani objednavky povinnych polozek pobocky na Poski server */
    private function sendItemOrder(array $items): ?string
    {
        $data = [
            'pristupovy_klic' => 'PGep08324vQryHGGhHuilNkOcx7Nm4LwZjr2SM42', // transfer token
            'zakaznik' => $this->getSysUser()->internalLogin,
            'polozky' => [],
        ];

        foreach ($items as $regNumber => $amount) {

            if ($this->orm->stockItems
                    ->findBy([
                        'regNumber' => $regNumber,
                        'group->number' => 30,
                    ])
                    ->fetch() !== null
            ) {
                $regNumber .= 'oo'; // nebo: $regNumber = $regNumber . 'oo';
            }

            $data['polozky'][] = [
                'kod' => $regNumber,
                'mnozstvi' => $amount
            ];
        }

        $curl = curl_init('https://www.koupelny-jas.cz/automaticka-objednavka');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));

        $result = curl_exec($curl);
        // cdd($result);
        $curlError = curl_errno($curl);
        $curlInfo = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($curlError) {
            return 'Odeslání objednávky se nezdařilo. Chyba komunikace.';
        } else if ($curlInfo != 200) {
            return 'Odeslání objednávky se nezdařilo. V posílaných datech je chyba (buď neexistující zákazník nebo položka).';
        }

        return null;
    }
}
