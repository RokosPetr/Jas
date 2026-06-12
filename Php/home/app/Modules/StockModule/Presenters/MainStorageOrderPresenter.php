<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Core\Component\Form\FilterContainer;
use App\Core\Utils\DateTime;
use App\Modules\DeliveryModule\Orm\DeliveryNotes\DeliveryNote;
use App\Modules\Presenters\SecurePresenter;
use App\Modules\StockModule\Orm\MainStorageOrders\MainStorageOrder;
use App\Modules\StockModule\Orm\MainStorageOrders\MainStorageOrderItem;
use App\Modules\StockModule\Orm\StockItems\StockItem;
use App\Modules\StockModule\Service\MainStorageOrderService;
use Nextras\Dbal\Utils\DateTimeImmutable;
use Nextras\Orm\Collection\ICollection;
use Nextras\Orm\Entity\ToArrayConverter;

/** Presenter pro objednavky sortimentu velkoskladu */
final class MainStorageOrderPresenter extends SecurePresenter
{
    /** @inject */
    public MainStorageOrderService $orderService;

    private ICollection $selectedItemsToOrder;

    public array $titles = [
        'default' => 'Objednavky velkoskladu',
        'preview' => 'Položky objednavky',
        'smartOrderStart' => 'Nastavení objednavky',
        'smartOrderFinish' => 'Dokončení objednavky',
        'manualOrderStart' => 'Nastavení objednavky',
        'manualOrderFinish' => 'Dokončení objednavky'
    ];

    /** Nahled objednavky - seznam polozek s moznosti editace */
    public function actionPreview(int $id): void
    {
        $order = $this->orm->mainStorageOrders->getById($id);
        if (!$order) {
            $this->error('Položka nenalezena');
        }
        $this->template->order = $order;
    }

    /** Smazani objednavky */
    public function actionDelete(int $id): void
    {
        $order = $this->orm->mainStorageOrders->getById($id);
        if (!$order) {
            $this->error('Položka nenalezena');
        }
        $this->orm->mainStorageOrders->removeAndFlush($order);
        $this->redirect('default');
    }

    /** Pridani polozky na objednavku */
    public function actionAddItem(int $id): void
    {
        $order = $this->orm->mainStorageOrders->getById($id);
        if (!$order) {
            $this->error('Položka nenalezena');
        }
        $this->setView('addEditItem');
        $this->sideDialogAjaxHandler();
    }

    /** Uprava polozky na objednavce */
    public function actionEditItem(int $id): void
    {
        $orderItem = $this->orm->mainStorageOrderItems->getById($id);
        if (!$orderItem) {
            $this->error('Položka nenalezena');
        }
        $defaults = $orderItem->toArray(ToArrayConverter::RELATIONSHIP_AS_ID);
        $defaults['item'] = $orderItem->item->regNumber . ' - ' . $orderItem->item->name;
        $this['addEditMainStorageOrderItemForm']->setDefaults($defaults);
        $this->setView('addEditItem');
        $this->sideDialogAjaxHandler();
    }

    /** Smazani polozky na objednavce */
    public function actionDeleteItem(int $id): void
    {
        $orderItem = $this->orm->mainStorageOrderItems->getById($id);
        if (!$orderItem) {
            $this->error('Položka nenalezena');
        }
        $orderId = $orderItem->order->id;
        $this->orm->mainStorageOrderItems->removeAndFlush($orderItem);
        $this->redirect('preview', ['id' => $orderId]);
    }

    /** Prvni krok objednavky polozek - nastaveni */
    public function actionSmartOrderStart(): void
    {
        $defaults = $this->getOrderSetting();
        if ($defaults) {
            $this['mainStorageSmartOrderSettingForm']->setDefaults($defaults);
        }
    }

    /** Objednavka polozek do velkoskladu */
    public function actionSmartOrderFinish(): void
    {
        $this->setView('orderFinish');
        $orderSetting = $this->getOrderSetting();
        if (empty($orderSetting)) {
            $this->flashMessage('Nejsou nastaveny parametry objednávky', self::MSG_ERROR);
            $this->redirect('default');
        }
        $this->template->stockItemRepo = $this->orm->stockItems;
        $this->template->producer = $this->orm->producers->getById($orderSetting['producer'])->name;
    }

    /** Nahled prodeju v objednavce velkoskladu */
    public function actionSalePreview(int $id): void
    {
        $stockItem = $this->orm->stockItems->getById($id);

        if (!$stockItem) {
            $this->error('Položka nenalezena');
        }

        $seasonFrom = $this->getSeasonFromSetting();
        $seasonTo = $this->getSeasonToSetting();

        $this->template->stockItem = $stockItem;
        $this->template->salesAverage = $this->orderService->getSalesAverage(
            $this->orm->stockItems->loadSales($id, $seasonFrom, $seasonTo),
            $this->orm->stockItems->loadCancels($id, $seasonFrom, $seasonTo),
            $this->orderService->getMonthDiff($seasonFrom, $seasonTo)
        );

        $this->sideDialogAjaxHandler();
    }

    /** Odstraneni polozky z objednavkoveho formu chytre objednavky */
    public function actionRemoveSmartOrderItem(int $id): void
    {
        $skippedItems = $this->getSkippedOrderItems();
        $skippedItems[] = $id;
        $this->setSkippedOrderItems($skippedItems);
        $this->sendJson(['status' => 200, 'text' => 'OK']);
    }

    /** Objednavka polozek do velkoskladu */
    public function actionManualOrderFinish(): void
    {
        $this->setView('orderFinish');
        $selectedItems = $this->getSession('mainStorageItemsToOrder_datagrid')->selectedRows ?? [];
        if (!$selectedItems) {
            $this->flashMessage('Nejsou vybrané žádné položky');
            $this->redirect('manualOrderStart');
        }
        $this->selectedItemsToOrder = $this->orm->stockItems->findBy(['id' => $selectedItems]);
        $this->template->selectedItemsToOrder = $this->selectedItemsToOrder->fetchPairs('id');
    }

    /** Odstraneni polozky z objednavkoveho formu chytre objednavky */
    public function actionRemoveManualOrderItem(int $id): void
    {
        $gridSession = $this->getSession('mainStorageItemsToOrder_datagrid');
        $selectedItems = $gridSession->selectedRows ?? [];
        unset($selectedItems[$id]);
        $gridSession->selectedRows = $selectedItems;
        $this->sendJson(['status' => 200, 'text' => 'OK']);
    }

    /** AJAX odpoved - paletove mnozstvi polozky sortimentu */
    public function handleGetStockItemPaletteCount(): void
    {
        $stockItem = $this->orm->stockItems->getById($this->getRequest()->getPost('id'));
        $result = [
            'palette' => (!$stockItem || !$stockItem->palette) ? 0 : $stockItem->palette,
            'minOrder' => (!$stockItem || !$stockItem->minOrder) ? 0 : $stockItem->minOrder,
        ];
        $this->sendJson(['result' => $result]);
    }

    /** Nahledovy grid prodeju zvolene polozky */
    protected function createComponentStockItemSales(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->deliveryNoteItems);
        $grid->settings->setDataSourceFilter([
            'item->item->id' => $this->getParameter('id'),
            'movementType' => [DeliveryNote::TYPE_SALE, DeliveryNote::TYPE_CANCEL],
            'note->date>=' => $this->getSeasonFromSetting(),
            'note->date<=' => $this->getSeasonToSetting()
        ])->disableFloatedTable();

        $grid->addColumn('noteNumber', 'DL');
        $grid->addColumn('store', 'Pobočka')->enableSort();
        $grid->addColumn('amount', 'Množství')->enableSort();
        $grid->addColumn('date', 'Datum')->dateFormat(DATE)->enableSort(BaseDatagrid::ORDER_DESC);

        $grid->addLegend('Storno', 'legend_red', "\$movementType == 3");

        return $grid;
    }

    /** Grid s objednavkami velkoskladu */
    protected function createComponentMainStorageOrders(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->mainStorageOrders);

        $grid->addColumn('numberLabel', 'Číslo')->enableSort(BaseDatagrid::ORDER_DESC);
        $grid->addColumn('producer', 'Výrobce');
        $grid->addColumn('created', 'Vytvořeno');

        $grid->addOtherColumn('updated', 'Upraveno');

        $grid->addTopAction('manualOrderStart', 'Manuální objednávka');
        $grid->addTopAction('smartOrderStart', 'Objednávka dle prodejů');
        $grid->addRowAction('preview', 'Položky objednávky');
        $grid->addRowAction('delete', 'Smazat objednávku');

        $grid->addLegend('Částečně naskladněno', 'legend_orange', "\$state == 2");
        $grid->addLegend('Kompletně naskladněno', 'legend_red', "\$state == 3");

        $grid->setFilterFormFactory(function (): FilterContainer {
            $form = new FilterContainer();
            $form->addSelect('state', 'Stav', [
                '' => 'Vše',
                MainStorageOrder::STATE_NOT_STOCKED => 'Nenaskladněné',
                MainStorageOrder::STATE_NEW => 'Nové',
                MainStorageOrder::STATE_PARTLY_STOCKED => 'Částečně naskladněné',
                MainStorageOrder::STATE_COMPLETELY_STOCKED => 'Kompletně naskladněné'
            ])->setDefaultValue(MainStorageOrder::STATE_NOT_STOCKED);
            return $form;
        });

        return $grid;
    }

    /** Grid s položkami objednavky velkoskladu */
    protected function createComponentMainStorageOrderItems(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->mainStorageOrderItems);
        $grid->settings->setDataSourceFilter([
            'order' => $this->getParameter('id')
        ]);
        $grid->settings->showExport('csv');
        $grid->addColumn('regNumber', 'Reg. číslo')->enableSort(BaseDatagrid::ORDER_ASC);
        $grid->addColumn('name', 'Název');
        $grid->addColumn('catalog', 'Kat. číslo');
        $grid->addColumn('producer', 'Výrobce');
        $grid->addColumn('paletteCount', 'Počet palet');
        $grid->addColumn('quantity', 'Množství');

        $grid->addTopAction('addItem', 'Přidat položku', ['id' => $this->getParameter('id')])
            ->setSideDialog();
        $grid->addRowAction('editItem', 'Upravit položku', 'pencil')
            ->setSideDialog();
        $grid->addRowAction('deleteItem', 'Smazat položku', 'trash')
            ->setDialog('Potvrzení', 'Přejete si opravdu odstranit položku?');

        $grid->addLegend('Naskladněno', 'legend_red', "\$stocked == 1");

        return $grid;
    }

    /** Grid s polozkami pro manualni objednavku velkoskladu */
    protected function createComponentMainStorageItemsToOrder(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->stockItems);
        $grid->addCellsTemplate(__DIR__ . '/../templates/MainStorageOrder/grid.cells.latte');
        $grid->settings->setFulltextColumns(['regNumber', 'name', 'catalog'])
            ->setDataSourceFilter(['status' => StockItem::STATUS_PALETTE])
            ->showCheckboxes();
        $grid->setMultiWordSearch();

        $grid->addColumn('regNumber', 'Registrační číslo')->enableSort(BaseDatagrid::ORDER_ASC);
        $grid->addColumn('name', 'Název')->enableSort();
        $grid->addColumn('storageCatalog', 'Katalogové číslo');
        $grid->addColumn('producer', 'Výrobce')->enableSort();
        $grid->addColumn('seriesName', 'Série');
        $grid->addColumn('group', 'Druh zboží');
        $grid->addColumn('mainStorageQuantity', 'Zásoba');
        $grid->addColumn('mainStorageOrder', 'Objednano');
        $grid->addColumn('unit', 'Jednotka');

        $grid->addTopAction('manualOrderFinish', 'Objednat vybrané');

        $grid->setFilterFormFactory(function (): FilterContainer {
            $producers = $this->orm->mainStorageOrders->loadProducersForFilter();
            $series = $this->orm->mainStorageOrders->loadSeriesForFilter();
            $form = new FilterContainer();
            $form->addMultiSelect('producer', 'Výrobce', $producers)
                ->checkDefaultValue(false);
            $form->addMultiSelect('series', 'Série', $series)
                ->checkDefaultValue(false);
            return $form;
        });

        return $grid;
    }

    /** Formular pro upravu/pridani polozky objednavky */
    protected function createComponentAddEditMainStorageOrderItemForm(): BaseForm
    {
        $form = new BaseForm();

        if ($this->getAction() === 'editItem') {
            $form->addText('item', 'Položka')->setDisabled();
        } else {
            $items = $this->orm->stockItems->findBy(['status' => StockItem::STATUS_PALETTE])
                ->fetchPairs('id', 'title');
            $form->addSelect('item', 'Položka', $items)
                ->setPrompt('-- Vyberte --')
                ->setRequired();
        }

        $form->addInteger('paletteCount', 'Počet palet')
            ->setRequired()
            ->addRule(BaseForm::MIN, null, 0)
            ->setDefaultValue(1);
        $quantityInput = $form->addText('quantity', 'Množství')
            ->setDefaultValue(0)
            ->addRule(BaseForm::FLOAT);

        if ($this->getAction() === 'editItem') {
            $orderItem = $this->orm->mainStorageOrderItems->getById($this->getParameter('id'));
            if ($orderItem->item->palette && !$orderItem->item->minOrder) {
                $quantityInput->setDisabled();
                $form->addHidden('paletteQuantity', $orderItem->item->palette);
            } else {
                $description = $orderItem->item->palette
                    ? 'Množství na paletě: ' . $orderItem->item->palette
                    : 'Neznámé množství na paletě';

                if ($orderItem->item->minOrder) {
                    $description .= ', minimum: ' . $orderItem->item->minOrder;
                }

                $quantityInput->setRequired()
                    ->addRule(BaseForm::MIN, null, 0.1)
                    ->setOption('description', $description);
            }
        }

        $form->addSubmit('submit', 'Uložit');

        $form->onSuccess[] = function (array $values) {
            if ($this->getAction() === 'editItem') {
                $orderItem = $this->orm->mainStorageOrderItems->getById($this->getParameter('id'));
                $this->flashMessage('Položka byla upravena');
            } else {
                $orderItem = new MainStorageOrderItem();
                $orderItem->order = $this->orm->mainStorageOrders->getById($this->getParameter('id'));
                $orderItem->item = $this->orm->stockItems->getById($values['item']);
                $this->flashMessage('Položka byla přidána');

                if ($orderItem->order->state === MainStorageOrder::STATE_COMPLETELY_STOCKED) {
                    $orderItem->order->state = MainStorageOrder::STATE_PARTLY_STOCKED;
                }
            }

            $orderItem->paletteCount = $values['paletteCount'];
            $orderItem->quantity = ($orderItem->item->palette && !$orderItem->item->minOrder)
                ? ($values['paletteCount'] * $orderItem->item->palette)
                : $values['quantity'];
            $this->orm->mainStorageOrderItems->persistAndFlush($orderItem);
            $orderItem->order->updatedAt = new DateTimeImmutable();
            $orderItem->order->updatedBy = $this->getSysUser();
            $this->orm->mainStorageOrders->persistAndFlush($orderItem->order);
            $this->redirect('preview', ['id' => $orderItem->order->id]);
        };

        return $form;
    }

    /** Formular pro nastaveni objednavek velkoskladu */
    protected function createComponentMainStorageSmartOrderSettingForm(): BaseForm
    {
        $currentMonth = (int) date('n');
        $years = array_reverse(range(2011, (int) date('Y')));
        $years = array_combine($years, $years);

        $orderSize = [
            1 => '1 měsíc',
            2 => '2 měsíce',
            3 => '3 měsíce',
            4 => '4 měsíce',
            5 => '5 měsíců',
            6 => '6 měsíců'
        ];

        $form = new BaseForm();
        $form->addSelect('monthFrom', 'Období od', DateTime::CZ_MONTHS)->setRequired();
        $form->addSelect('yearFrom', null, $years)->setRequired();
        $form->addSelect('monthTo', 'Období do', DateTime::CZ_MONTHS)
            ->setRequired()
            ->setDefaultValue($currentMonth === 1 ? 1 : $currentMonth - 1);
        $form->addSelect('yearTo', null, $years)->setRequired();
        $form->addSelect('producer', 'Výrobce', $this->orm->producers->findAll()->orderBy('number')->fetchPairs('id', 'name'))
            ->setRequired();
        $form->addText('minStockIndex', 'Index skladové zásoby')
            ->setHtmlType('number')
            ->setHtmlAttribute('step', 0.1)
            ->setDefaultValue(0.5)
            ->setRequired()
            ->addRule(BaseForm::FLOAT)
            ->addRule(BaseForm::RANGE, null, [0.1, 2]);
        $form->addSelect('orderSize', 'Objednávané množství', $orderSize)->setRequired();

        $form->addSubmit('continue', 'Pokračovat');

        $form->onValidate[] = function (BaseForm $form, \stdClass $values): void {
            $currentYear = (int) date('Y');
            $currentMonth = (int) date('n');

            if ($currentYear === $values->yearTo && $currentMonth <= $values->monthTo) {
                $form['monthTo']->addError('Období musí být v minulosti');
                return;
            }

            $fromDate = DateTime::createFromFormat('j-n-Y', "1-$values->monthFrom-$values->yearFrom");
            $toDate = DateTime::createFromFormat('j-n-Y', "1-$values->monthTo-$values->yearTo");

            if ($fromDate >= $toDate) {
                $form['monthTo']->addError('Období není validní');
            }
        };

        $form->onSuccess[] = function (array $values): void {
            $this->setOrderSetting($values);
            $this->setSkippedOrderItems([]);
            $this->redirect('smartOrderFinish');
        };

        return $form;
    }

    /** Formular pro vytvoreni objednavky velkoskladu */
    protected function createComponentMainStorageOrderForm(): BaseForm
    {
        $form = new BaseForm();
        $itemsContainer = $form->addContainer('items');

        foreach ($this->loadItemsToOrder() as $itemId => $orderParams) {
            $itemContainer = $itemsContainer->addContainer($itemId);
            $minOrder = $orderParams['minOrder'];
            $paletteInput = $itemContainer->addInteger('palette')
                ->setRequired()
                ->addRule(BaseForm::MIN, null, 0)
                ->setDefaultValue($orderParams['palette'] ?? 1);

            if (isset($orderParams['salesAverage'])) {
                $paletteInput->setOption('salesAverage', $orderParams['salesAverage']);
            }

            if (is_null($orderParams['palette']) || $minOrder) {
                $itemContainer->addText('quantity')
                    ->setRequired()
                    ->setDefaultValue($orderParams['quantity'])
                    ->addRule(BaseForm::FLOAT)
                    ->addRule(BaseForm::MIN, null, 0.1);
            } else {
                $paletteInput->setOption('paletteQuantity', $orderParams['quantity'] / $orderParams['palette']);
            }
        }

        $form->addSubmit('create', 'Vytvořit objednávku');

        $form->onSuccess[] = [$this, 'mainStorageOrderFormSuccess'];

        return $form;
    }

    /** Success callback formulare pro vytvoreni objednavky velkoskladu */
    public function mainStorageOrderFormSuccess(array $values): void
    {
        $order = $this->orm->mainStorageOrders->createNewOrder();

        foreach ($values['items'] as $itemId => $itemParams) {
            if ($itemParams['palette'] === 0 && ($itemParams['quantity'] ?? 0.0) === 0.0) {
                continue;
            }

            $stockItem = $this->orm->stockItems->getById($itemId);
            $orderItem = new MainStorageOrderItem();
            $orderItem->order = $order;
            $orderItem->item = $stockItem;
            $orderItem->paletteCount = $itemParams['palette'];
            $orderItem->quantity = isset($itemParams['quantity'])
                ? round($itemParams['quantity'], 1)
                : $stockItem->palette * $itemParams['palette'];
            $this->orm->mainStorageOrderItems->persist($orderItem);
        }

        $this->orm->flush();

        if ($this->getAction() === 'smartOrderFinish') {
            $this->setSkippedOrderItems([]);
        } else {
            $gridSession = $this->getSession('mainStorageItemsToOrder_datagrid');
            unset($gridSession->selectedRows);
        }

        $this->flashMessage('Objednávka byla vytvořena');
        $this->redirect('default');
    }

    private function getOrderSetting(): ?array
    {
        return $this->getSession('setting')->mainStorageOrder ?? null;
    }

    private function setOrderSetting(array $values): void
    {
        $this->getSession('setting')->mainStorageOrder = $values;
    }

    private function getSkippedOrderItems(): array
    {
        return $this->getSession('setting')->skippedOrderItems ?? [];
    }

    private function setSkippedOrderItems(array $values): void
    {
        $this->getSession('setting')->skippedOrderItems = $values;
    }

    private function loadItemsToOrder(): array
    {
        if (isset($this->selectedItemsToOrder)) {
            return $this->orderService->loadSelectedItemsToOrder($this->selectedItemsToOrder);
        } else {
            $orderSetting = $this->getOrderSetting();
            return $this->orderService->loadItemsToOrder(
                $orderSetting['producer'],
                $orderSetting['minStockIndex'],
                $orderSetting['orderSize'],
                $this->getSeasonFromSetting(),
                $this->getSeasonToSetting(),
                $this->getSkippedOrderItems()
            );
        }
    }

    private function getSeasonFromSetting(): \DateTimeInterface
    {
        $orderSetting = $this->getOrderSetting();
        return \DateTime::createFromFormat(
            'j-n-Y',
            '1-'. $orderSetting['monthFrom'] . '-' . $orderSetting['yearFrom']
        )->setTime(0, 0);
    }

    private function getSeasonToSetting(): \DateTimeInterface
    {
        $orderSetting = $this->getOrderSetting();
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, (int) $orderSetting['monthTo'], (int) $orderSetting['yearTo']);
        return \DateTime::createFromFormat(
            'j-n-Y',
            $daysInMonth . '-'. $orderSetting['monthTo'] . '-' . $orderSetting['yearTo']
        )->setTime(23, 59);
    }
}
