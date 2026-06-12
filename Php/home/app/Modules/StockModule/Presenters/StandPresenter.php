<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Core\Component\Form\FilterContainer;
use App\Core\Orm\BaseMapper;
use App\Core\Utils\DateTime;
use App\Modules\CliModule\Service\WarehouseImporter;
use App\Modules\DeliveryModule\Orm\Companies\DepotRepository;
use App\Modules\Presenters\SecurePresenter;
use App\Modules\StockModule\Orm\Stands\Stand;
use App\Modules\StockModule\Orm\Stands\StandNote;
use App\Modules\StockModule\Orm\Stands\StandPlate;
use App\Modules\StockModule\Orm\Stands\StandRepository;
use App\Modules\StockModule\Service\StandExporter;
use App\Modules\SystemModule\Orm\Stores\Store;
use Contributte\PdfResponse\PdfResponse;
use Nette\Http\FileUpload;
use Nextras\Orm\Collection\Expression\LikeExpression;
use Nextras\Orm\Collection\ICollection;
use Nextras\Orm\Entity\ToArrayConverter;

/** Presenter pro spravu stojanu */
final class StandPresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'Stojany',
        'add' => 'Přidat stojan',
        'edit' => 'Upravit stojan',
        'importStandNotes' => 'Import DL stojanů',
        'standNotes' => 'DL Stojanu',
        'addStandNote' => 'Přidat DL stojanu',
        'editStandNote' => 'Upravit DL stojanu',
        'standItems' => 'Položky stojanu',
        'standPlates' => 'Plata stojanu',
        'plateItems' => 'Položky plata stojanu',
        'addPlateItem' => 'Přidat položku',
        'editPlateItem' => 'Upravit položku',
        'addPlate' => 'Přidat plato stojanu',
        'editPlate' => 'Upravit plato stojanu',
        'plateItemHistory' => 'Historie položky',
        'plateHistory' => 'Histore plata'
    ];

    /** @inject */
    public WarehouseImporter $warehouseImporter;

    protected function startup(): void
    {
        parent::startup();
        if (in_array($this->action, ['addStandNote', 'editStandNote'])) {
            $this->setView('addEditStandNote');
        }
        if (in_array($this->action, ['addPlateItem', 'editPlateItem'])) {
            $this->setView('addEditPlateItem');
        }
        if (in_array($this->action, ['addPlate', 'editPlate'])) {
            $this->setView('addEditPlate');
        }
    }

    /** Stojany */
    public function renderDefault(): void
    {
        $itemFilter = $this->orm->stockItems->getById($this->getProductFilter());
        $itemFilterStands = [];

        if ($itemFilter) {
            $itemFilterStands = $this->orm->stands->findBy([
                'plates->items->item->id' => $itemFilter->id,
                'deleted' => false
            ]);
        }

        $this->template->itemFilter = $itemFilter;
        $this->template->itemFilterStands = $itemFilterStands;
    }

    /** Nahled stojanu */
    public function actionPreview(int $id): void
    {
        $stand = $this->orm->stands->getById($id);
        if (!$stand) {
            $this->error('Položka nenalezena');
        }
        $this->template->stand = $stand;
        $this->sideDialogAjaxHandler();
    }

    /** Editace stojanu */
    public function actionEdit(int $id): void
    {
        $stand = $this->orm->stands->getById($id);
        if (!$stand || $stand->deleted) {
            $this->error('Položka nenalezena');
        }
        $this->template->standPicture = $stand->picture;
        $this->template->standSecondPicture = $stand->secondPicture;
        $this['standForm']->setDefaults($stand->toArray(ToArrayConverter::RELATIONSHIP_AS_ID));
    }

    /** Editace stojanu */
    public function actionClone(int $id): void
    {
        $stand = $this->orm->stands->getById($id);
        if (!$stand || $stand->deleted) {
            $this->error('Položka nenalezena');
        }
        $newStand = $this->orm->stands->createClone($stand);
        $this->redirect('edit', ['id' => $newStand->id]);
    }

    /** Smazani stojanu */
    public function actionDelete(int $id): void
    {
        $stand = $this->orm->stands->getById($id);
        if (!$stand || $stand->deleted) {
            $this->error('Položka nenalezena');
        }
        $this->orm->stands->cancelEntity($stand);
        $this->flashMessage('Položka byla odstraněna');
        $this->redirect('default');
    }

    /** Obnoveni stojanu */
    public function actionRestore(int $id): void
    {
        $stand = $this->orm->stands->getById($id);
        if (!$stand || !$stand->deleted) {
            $this->error('Položka nenalezena');
        }
        $this->orm->stands->restoreEntity($stand);
        $this->flashMessage('Položka byla obnovena');
        $this->redirect('default');
    }

    /** Stojan na pobockach partneru */
    public function actionStandNotes(int $id): void
    {
        $stand = $this->orm->stands->getById($id);
        if (!$stand) {
            $this->error('Položka nenalezena');
        }
        $this->template->stand = $stand;
    }

    /** Pridat DL se stojanem */
    public function actionAddStandNote(int $id): void
    {
        $stand = $this->orm->stands->getById($id);
        if (!$stand || $stand->deleted) {
            $this->error('Položka nenalezena');
        }
        $this['standNoteForm']['stand']->setValue($stand->title);
        $this->template->stand = $stand;
    }

    /** Upravit DL se stojanem */
    public function actionEditStandNote(int $id): void
    {
        $standNote = $this->orm->standNotes->getById($id);
        if (!$standNote || $standNote->stand->deleted) {
            $this->error('Položka nenalezena');
        }
        $defaults = $standNote->toArray(ToArrayConverter::RELATIONSHIP_AS_ID);
        $this['standNoteForm']['depot']->setItems([$standNote->depot->id => $standNote->depot->name]);
        $this['standNoteForm']->setDefaults($defaults);
        $this['standNoteForm']['stand']->setValue($standNote->stand->title);
        $this->template->stand = $standNote->stand;
    }

    /** Odebrat stojan od partnera */
    public function actionRemoveStandNote(int $id): void
    {
        $standNote = $this->orm->standNotes->getById($id);
        if (!$standNote || !$standNote->isActive || $standNote->stand->deleted) {
            $this->error('Položka nenalezena');
        }
        $this['removeStandNoteForm']->setDefaults(['id' => $standNote->id]);
        $this->template->standNote = $standNote;
        $this->sideDialogAjaxHandler();
    }

    /** Odstranit odebrani stojan od partnera */
    public function actionCancelRemoveStandNote(int $id): void
    {
        $standNote = $this->orm->standNotes->getById($id);
        if (!$standNote || $standNote->isActive || $standNote->stand->deleted) {
            $this->error('Položka nenalezena');
        }
        $activeNote = $this->orm->standNotes->getBy([
            'stand->id' => $standNote->stand->id,
            'depot->id' => $standNote->depot->id,
            'removeDate' => null,
            'id!=' => $standNote->id
        ]);

        if ($activeNote) {
            $this->flashMessage('DL nelze obnovit, stojan u partnera existuje!', self::MSG_ERROR);
            $this->redirect('standNotes', ['id' => $standNote->stand->id]);
        }

        $standNote->removeNote = null;
        $standNote->removeDate = null;
        $standNote->removeBy = null;
        $this->orm->standNotes->persistAndFlush($standNote);
        $this->redirect('standNotes', ['id' => $standNote->stand->id]);
    }

    /** Polozky kusoveho stojanu */
    public function actionStandItems(int $id): void
    {
        $stand = $this->orm->stands->getById($id);
        if (!$stand) {
            $this->error('Položka nenalezena');
        }

        if (!$stand->plates->count()) {
            $plate = new StandPlate();
            $plate->stand = $stand;
            $plate->order = 1;
            $plate->description = '';
            $this->orm->standPlates->persistAndFlush($plate);
        } else {
            $plate = $stand->getPlate();
        }

        $this->template->plateId = $plate->id;
        $this->template->stand = $stand;
        $this->template->plateItems = $plate->loadItems();
    }

    /** Polozky kusoveho stojanu */
    public function actionPlateItems(int $id): void
    {
        $plate = $this->orm->standPlates->getById($id);
        if (!$plate) {
            $this->error('Položka nenalezena');
        }
        $this->template->plate = $plate;
        $this->template->plateItems = $plate->loadItems();
    }

    /** Pridani polozky sortimentu na stojan/plat stojanu */
    public function actionAddPlateItem(int $id, int $order = null): void
    {
        $plate = $this->orm->standPlates->getById($id);
        if (!$plate || $plate->stand->deleted) {
            $this->error('Položka nenalezena');
        }
        $this->template->plate = $plate;
        if ($order) {
            $this['standPlateItemForm']['order']->setDefaultValue($order)->setHtmlAttribute('readonly', true);
        } else {
            $lastItem = $plate->items->toCollection()->findBy(['deleted' => false])->orderBy('order', ICollection::DESC)->fetch();
            $this['standPlateItemForm']['order']->setDefaultValue($lastItem ? ($lastItem->order + 1) : 1);
        }
    }

    /** Zmena polozky sortimentu na stojanu/platu stojanu */
    public function actionEditPlateItem(int $id, bool $defaultAction = false): void
    {
        $plateItem = $this->orm->standPlateItems->getById($id);
        if (!$plateItem || $plateItem->plate->stand->deleted) {
            $this->error('Položka nenalezena');
        }
        $this->template->plate = $plateItem->plate;
        $this->template->itemPicture = $plateItem->picture;
        $this['standPlateItemForm']['order']->setDefaultValue($plateItem->order);
        $this['standPlateItemForm']['item']->setItems([$plateItem->item->id => $plateItem->item->title])
            ->setDefaultValue($plateItem->item->id);

        if ($plateItem->plate->stand->hasPlates) {
            $this['standPlateItemForm']['photoItem']->setDefaultValue($plateItem->photoItem);
            $this['standPlateItemForm']['seriesItem']->setDefaultValue($plateItem->seriesItem);
        }
    }

    /** Odstraneni polozky sortimentu ze stojanu/platu stojanu */
    public function actionDeletePlateItem(int $id, bool $defaultAction = false): void
    {
        $plateItem = $this->orm->standPlateItems->getById($id);
        if (!$plateItem || $plateItem->plate->stand->deleted) {
            $this->error('Položka nenalezena');
        }
        $this->orm->standPlateItems->cancelEntity($plateItem);

        if ($defaultAction) {
            $this->redirect('default');
        }

        $this->redirectPlateItem($plateItem->plate, $plateItem->order);
    }

    /** Platy stojanu */
    public function actionStandPlates(int $id): void
    {
        $stand = $this->orm->stands->getById($id);
        if (!$stand) {
            $this->error('Položka nenalezena');
        }
        $this->template->stand = $stand;
        $this->template->plates = $stand->loadPlates();
    }

    /** Nahled plata stojanu */
    public function actionPlatePreview(int $id): void
    {
        $plate = $this->orm->standPlates->getById($id);
        if (!$plate) {
            $this->error('Položka nenalezena');
        }
        $this->template->plate = $plate;
        $this->sideDialogAjaxHandler();
    }

    /** Nahled polozky stojanu/plata */
    public function actionPlateItemPreview(int $id): void
    {
        $plateItem = $this->orm->standPlateItems->getById($id);
        if (!$plateItem) {
            $this->error('Položka nenalezena');
        }
        $this->template->plateItem = $plateItem;
        $this->sideDialogAjaxHandler();
    }

    /** Pridani platu ke stojanu */
    public function actionAddPlate(int $id, int $order): void
    {
        $stand = $this->orm->stands->getById($id);
        if (!$stand || $stand->deleted) {
            $this->error('Položka nenalezena');
        }
        $this->template->stand = $stand;
        $this['standPlateForm']['order']->setDefaultValue($order)->setHtmlAttribute('readonly', true);
    }

    /** Uprava platu stojanu */
    public function actionEditPlate(int $id, bool $defaultAction = false): void
    {
        $plate = $this->orm->standPlates->getById($id);
        if (!$plate || $plate->stand->deleted) {
            $this->error('Položka nenalezena');
        }
        $this->template->stand = $plate->stand;
        $this->template->platePicture = $plate->picture;
        $this['standPlateForm']->setDefaults($plate->toArray(ToArrayConverter::RELATIONSHIP_AS_ID));
    }

    /** Odstraneni platu stojanu */
    public function actionDeletePlate(int $id, bool $defaultAction = false): void
    {
        $plate = $this->orm->standPlates->getById($id);
        if (!$plate || $plate->stand->deleted) {
            $this->error('Položka nenalezena');
        }
        $this->orm->standPlates->cancelEntity($plate);

        if ($defaultAction) {
            $this->redirect('default');
        }

        $this->redirectPlate($plate->stand, $plate->order);
    }

    /** Smazani obrazku */
    public function actionRemovePicture(): void
    {
        $id = (int) $this->getRequest()->getPost('id');
        $repo = $this->getRequest()->getPost('repository');

        if ($this->removePicture($id, $repo ?? '')) {
            $this->sendSuccessJson();
        } else {
            $this->sendErrorJson(404, 'Soubor nenalezen');
        }
    }

    /** Historie polozky plata/stojanu */
    public function actionPlateItemHistory(int $id, int $order): void
    {
        $plate = $this->orm->standPlates->getById($id);
        if (!$plate || $plate->deleted || $plate->stand->deleted) {
            $this->error('Položka nenalezena');
        }
        $this->template->plate = $plate;
        $this->template->order = $order;
    }

    /** Historie plata */
    public function actionPlateHistory(int $id, int $order): void
    {
        $stand = $this->orm->stands->getById($id);
        if (!$stand || $stand->deleted) {
            $this->error('Položka nenalezena');
        }
        $this->template->stand = $stand;
        $this->template->order = $order;
    }

    /** Obnoveni polozky plata/stojanu */
    public function actionRestorePlateItem(int $id): void
    {
        $plateItem = $this->orm->standPlateItems->getById($id);
        if (!$plateItem || !$plateItem->deleted || $plateItem->plate->deleted || $plateItem->plate->stand->deleted) {
            $this->error('Položka nenalezena');
        }
        $activeItem = $this->orm->standPlateItems->getBy([
            'plate->id' => $plateItem->plate->id,
            'order' => $plateItem->order,
            'deleted' => false
        ]);
        if ($activeItem) {
            $this->orm->standPlateItems->cancelEntity($activeItem);
        }
        $this->orm->standPlateItems->restoreEntity($plateItem);
        $this->flashMessage('Položka byla obnovena');
        $this->redirect('plateItemHistory', ['id' => $plateItem->plate->id, 'order' => $plateItem->order]);
    }

    /** Obnoveni plata stojanu */
    public function actionRestorePlate(int $id): void
    {
        $plate = $this->orm->standPlates->getById($id);
        if (!$plate || !$plate->deleted || $plate->stand->deleted) {
            $this->error('Položka nenalezena');
        }
        $activePlate = $this->orm->standPlates->getBy([
            'stand->id' => $plate->stand->id,
            'order' => $plate->order,
            'deleted' => false
        ]);
        if ($activePlate) {
            $this->orm->standPlates->cancelEntity($activePlate);
        }
        $this->orm->standPlates->restoreEntity($plate);
        $this->flashMessage('Plato bylo obnoveno');
        $this->redirect('plateHistory', ['id' => $plate->stand->id, 'order' => $plate->order]);
    }

    /** Export nahledu stojanu do Excel */
    public function actionExportExcel(int $id): void
    {
        $stand = $this->orm->stands->getById($id);
        if (!$stand) {
            $this->error('Položka nenalezena');
        }
        $this->sendResponse((new StandExporter())->toExcel($stand));
    }

    /** Export nahledu stojanu do PDF */
    public function actionExportPdf(int $id, int $type, bool $showPrice = false): void
    {
        $stand = $this->orm->stands->getById($id);
        if (!$stand) {
            $this->error('Položka nenalezena');
        }
        $template = $this->createTemplate();
        $template->setFile(__DIR__ . '/../templates/Stand/pdf/stand.latte');
        $template->stand = $stand;
        $template->type = $type;
        $template->showPrice = $showPrice;

        $pdf = new PdfResponse($template);
        $pdf->setDocumentTitle($stand->name);
        $pdf->styles = file_get_contents(__DIR__ . '/../templates/Stand/pdf/style.css');
        $this->sendResponse($pdf);
    }

    /** Export nahledu plata stojanu do PDF */
    public function actionExportPlatePdf(int $id): void
    {
        $plate = $this->orm->standPlates->getById($id);
        if (!$plate) {
            $this->error('Položka nenalezena');
        }
        $template = $this->createTemplate();
        $template->setFile(__DIR__ . '/../templates/Stand/pdf/plate.latte');
        $template->plate = $plate;

        $pdf = new PdfResponse($template);
        $pdf->setDocumentTitle($plate->description);
        $pdf->styles = file_get_contents(__DIR__ . '/../templates/Stand/pdf/style.css');
        $this->sendResponse($pdf);
    }

    /** Aktualizace cen produktu z MOP */
    public function actionUpdateStockItemPrice(): void
    {
        $error = $this->warehouseImporter->updateItemPrice();
        if ($error) {
            $this->flashMessage($error, self::MSG_ERROR);
        } else {
            $this->flashMessage('Aktualizace cen proběhla úspěšně');
        }
        $this->redirect('default');
    }

    /** Handle pro option selectu pobocek partneru */
    public function handleLoadDepotOption(int $id): void
    {
        $search = trim($this->getParameter('search'));
        $result = [];

        if (!$search) {
            $this->sendJson($result);
        }

        $filter = ['store->id' => Store::OSTRAVA_MAIN_STORAGE];

        if (preg_match("/^\d+$/", $search)) {
            $filter['company->ico~'] = LikeExpression::contains(ltrim($search, '0'));
        } else {
            $filter['company->name~'] = LikeExpression::contains(trim($search));
        }

        //$depots = $this->orm->companyDepots->findBy($filter + DepotRepository::DEALER_OR_JAS)
        //    ->orderBy('company->ico');

// 1) Postav obě větve se společnými podmínkami (search/store) uvnitř:
        $dealer = $filter + DepotRepository::DEALER_FILTER;
        $jas    = $filter + DepotRepository::JAS_FILTER;

// 2) Vytáhni ID z obou dotazů a sjednoť je
        $ids1 = $this->orm->companyDepots->findBy($dealer)->fetchPairs('id', 'id');
        $ids2 = $this->orm->companyDepots->findBy($jas)->fetchPairs('id', 'id');
        $ids  = array_keys($ids1 + $ids2);

// 3) Finální kolekce z DB podle sjednocených ID + řazení
        $depots = $this->orm->companyDepots
            ->findBy(['id' => $ids])
            ->orderBy('company->ico');


        foreach ($depots as $depot) {

            $hasStand = false;

            $filter = [
                'depot->id' => $depot->id,
                'stand->id' => $id,
                'removeDate' => null
            ];

            if ($this->orm->standNotes->getBy($filter)) {
                $hasStand = true;
            }

            if ($depot->companyIcoString == Store::INTERNAL_ICO){
                $result[] = [
                    'id' => $depot->id,
                    'text' => "$depot->companyIcoString - $depot->name ($depot->city)",
                    'hasStand' => $hasStand
                ];
            }
            else{
                $result[] = [
                    'id' => $depot->id,
                    'text' => "$depot->companyIcoString - $depot->name",
                    'hasStand' => $hasStand
                ];
            }

        }

        $this->sendJson(['results' => $result]);
    }

    /** Filtr stojanu dle produktu na stojanu */
    public function handleSetProductFilter(): void
    {
        $this->setProductFilter((int) $this->getParameter('product'));
        $this->redrawControl('stand-product-filter');
        $this->redrawControl('stand-grid-control');
    }

    /** Datagrid se stojany */
    protected function createComponentStands(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->stands);
        $grid->addCellsTemplate(__DIR__ . '/../templates/Stand/grid.cells.latte');
        $grid->settings->setFulltextColumns(['code', 'name']);

        if (!$this->getUser()->isAllowed(':Stock:Stand:restore')) {
            $grid->settings->setDataSourceFilter(['deleted' => 0]);
        } else {
            $grid->addLegend('Smazaná položka', 'legend_red', "\$deleted == 1");
        }

        $grid->addColumn('code', 'ID')->enableSort();
        $grid->addColumn('name', 'Název')->enableSort();
        $grid->addColumn('year', 'Rok')->enableSort();
        $grid->addColumn('producerName', 'Výrobce');
        $grid->addColumn('type', 'Typ');
        $grid->addColumn('dimensions', 'ŠxHxV (cm)');
        $grid->addColumn('unitCount', 'Počet jednotek');
        $grid->addColumn('count', 'Na pobočkách');

        $grid->addTopAction('add', 'Přidat');
        $grid->addTopAction('importStandNotes', 'Importovat DL stojanů');
        $grid->addTopAction('updateStockItemPrice', 'Aktualizovat ceny produktů');
        $grid->addTopAction('standRelocations', 'Relokace')
            ->setLink('Delivery', 'DepotStands');

        $grid->addRowAction('preview', 'Náhled')->setSideDialog();
        $grid->addRowAction('edit', 'Upravit')->setCondition("\$deleted == 0");
        $grid->addRowAction('standPlates', 'Plata', 'list-ol')
            ->setCondition("\$hasPlates == 1");
        $grid->addRowAction('standItems', 'Položky', 'list-ol')
            ->setCondition("\$hasPlates == 0");
        $grid->addRowAction('standNotes', 'Dodáno na pobočky', 'fort-awesome');
        $grid->addRowAction('clone', 'Duplikovat', 'code-fork')
            ->setCondition("\$deleted == 0")
            ->setDialog('Potvrzení', 'Opravdu chcete vytvořit nový stojan jako kopii tohoto?');
        $grid->addRowAction('delete','Smazat')
            ->setCondition("\$deleted == 0");
        $grid->addRowAction('restore','Obnovit')
            ->setCondition("\$deleted == 1");

        $grid->setFilterFormFactory(function (): FilterContainer {
            $producers = $this->orm->stands->findBy(['producer->id!=' => null])->orderBy('producer->number')
                ->fetchPairs('producer->id', 'producer->name');
            $standTypes = [
                Stand::TYPE_PEACES => 'Kusový',
                Stand::TYPE_PLATES => 'Platový',
                Stand::TYPE_SANITARY => 'Sanita'
            ];
            $form = new FilterContainer();
            $form->addSelect('producer', 'Výrobce', ['' => 'Vše'] + $producers);
            $form->addSelect('type', 'Typ', ['' => 'Vše'] + $standTypes);

            if ($this->getUser()->isAllowed(':Stock:Stand:restore')) {
                $form->addSelect('deleted', 'Stav', [
                    '' => 'Vše',
                    '0' => 'Pouze nesmazané',
                    '1' => 'Pouze smazané'
                ])->setDefaultValue('0');
            }

            return $form;
        });

        return $grid;
    }

    /** Datagrid se stojany prirazene pobockam */
    protected function createComponentStandNotes(): BaseDatagrid
    {
        $stand = $this->orm->stands->getById($this->getParameter('id'));
        $grid = $this->datagridFactory->create($this->orm->standNotes);
        $grid->addCellsTemplate(__DIR__ . '/../templates/Stand/standNotes.grid.cells.latte');
        $grid->settings->setDataSourceFilter(['stand' => $this->getParameter('id')])
            ->showExport();

        $grid->addColumn('date', 'Datum expedice')
            ->dateFormat(DATE)
            ->enableSort(BaseDatagrid::ORDER_DESC);
        $grid->addColumn('depot', 'Partner');
        $grid->addColumn('note', 'DL');
        $grid->addColumn('invoice', 'Faktura');
        $grid->addColumn('remark', 'Poznámka');
        $grid->addColumn('removeDate', 'Odebráno');

        $grid->addOtherColumn('created', 'Vytvořil');
        $grid->addOtherColumn('updated', 'Upravil');
        $grid->addOtherColumn('removed', 'Odebral');

        $grid->addLegend('Odebráno', 'legend_orange', "\$state == " . StandNote::STATE_REMOVED);
        $grid->addLegend('Nedodáno', 'legend_green', "\$state ==" . StandNote::STATE_PREPARED);

        if (!$stand->deleted) {
            $grid->addTopAction('addStandNote', 'Přidat záznam', ['id' => $this->getParameter('id')]);
            $grid->addRowAction('editStandNote', 'Upravit', 'pencil')
                ->setCondition("\$isActive == 1");
            $grid->addRowAction('removeStandNote', 'Odebrat stojan', 'close')
                ->setCondition("\$state == " . StandNote::STATE_DELIVERED)
                ->setSideDialog();
            $grid->addRowAction('cancelRemoveStandNote', 'Zrušit odebrání stojanu', 'undo')
                ->setCondition("\$isActive == 0");
        }

        $grid->setFilterFormFactory(function () use ($grid): FilterContainer {
            $depots = $this->orm->companyDepots->findBy(['standNotes->stand->id' => $this->getParameter('id')])
                ->orderBy('company->name')
                ->fetchPairs('id', 'name');

            if (isset($grid->filter['depot']) && !isset($depots[$grid->filter['depot']])) {
                $depot = $this->orm->companyDepots->getById($grid->filter['depot']);
                if ($depot) {
                    $depots[$depot->id] = $depot->name;
                }
            }

            $form = new FilterContainer();
            $form->addSelect('depot', 'Partner', ['' => 'Vše'] + $depots)->checkDefaultValue(false);
            $form->addSelect('state', 'Stav', [
                '' => 'Vše',
                StandNote::STATE_ACTIVE => 'Aktivní',
                StandNote::STATE_PREPARED => 'Nedodáno',
                StandNote::STATE_DELIVERED => 'Na pobočkách',
                StandNote::STATE_REMOVED => 'Odebráno'
            ])->setDefaultValue(StandNote::STATE_ACTIVE);

            return $form;
        });
        return $grid;
    }

    /** Datagrid s historii polozek platove polozky */
    protected function createComponentStandHistoryPlates(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->standPlates);
        $grid->settings->setDataSourceFilter([
            'stand->id' => $this->getParameter('id'),
            'order' => $this->getParameter('order')
        ]);
        $grid->addColumn('description', 'Série/Popis');
        $grid->addColumn('cancelled', 'Smazáno')->enableSort(BaseDatagrid::ORDER_DESC);
        $grid->addRowAction('platePreview', 'Náhled', 'search')->setSideDialog();
        $grid->addRowAction('restorePlate', 'Obnovit', 'undo')
            ->setCondition("\$deleted == 1");
        return $grid;
    }

    /** Datagrid s historii polozek platove polozky */
    protected function createComponentStandHistoryPlateItems(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->standPlateItems);
        $grid->addCellsTemplate(__DIR__ . '/../templates/Stand/plateItemHistory.grid.cells.latte');
        $grid->settings->setDataSourceFilter([
            'plate->id' => $this->getParameter('id'),
            'order' => $this->getParameter('order')
        ]);
        $grid->addColumn('itemTitle', 'Položka');
        $grid->addColumn('cancelled', 'Smazáno')->enableSort(BaseDatagrid::ORDER_DESC);
        $grid->addRowAction('plateItemPreview', 'Náhled', 'search')->setSideDialog();
        $grid->addRowAction('restorePlateItem', 'Obnovit', 'undo')
            ->setCondition("\$deleted == 1");
        return $grid;
    }

    /** Formular na upravu stojanu */
    protected function createComponentStandForm(): BaseForm
    {
        $stand = $this->getAction() === 'edit' ? $this->orm->stands->getById($this->getParameter('id')) : null;
        $producers = [-1 => 'Mix'] + $this->orm->producers->findAll()->fetchPairs('id', 'name');
        $standTypes = [
            Stand::TYPE_PEACES => 'Kusový',
            Stand::TYPE_PLATES => 'Platový',
            Stand::TYPE_SANITARY => 'Sanita',
        ];
        $plateOrderTypes = [
            Stand::PLATE_ORDER_RIGHT_FIRST => 'P/L',
            Stand::PLATE_ORDER_UNIVERSAL => 'Univerzální'
        ];
        $standCodes = $stand
            ? $this->orm->stands->findBy(['id!=' => $stand->id])->fetchPairs(null, 'code')
            : $this->orm->stands->findAll()->fetchPairs(null, 'code');

        $form = new BaseForm();
        $form->addText('code', 'ID', null, 13)
            ->addRule(BaseForm::PATTERN, 'Neplatný formát', '[0-9]+/[0-9]+')
            ->addRule(BaseForm::IS_NOT_IN, 'Tento stojan již existuje', $standCodes)
            ->setRequired();
        $form->addText('name', 'Název', null, 250)->setRequired();
        $form->addInteger('year', 'Rok')
            ->setRequired()
            ->setDefaultValue(intval(date('Y')))
            ->addRule(BaseForm::RANGE, null, [1900, 2100]);
        $form->addSelect('producer', 'Výrobce', $producers)->setRequired();
        $form->addCheckbox("b2b", "Velkoobchod" )
            ->addCondition(BaseForm::EQUAL, true);
        $form->addCheckbox("b2c", "Maloobchod" )
            ->addCondition(BaseForm::EQUAL, true);
        $typeSelect = $form->addRadioList('type', 'Typ', $standTypes)
            ->setDefaultValue(Stand::TYPE_PEACES)
            ->setRequired();
        $typeSelect->addCondition(BaseForm::EQUAL, Stand::TYPE_PEACES)
            ->toggle('plate-order-type-wrapper', false);
        $form->addInteger('unitCount', 'Počet jednotek')
            ->setRequired()
            ->addRule(BaseForm::RANGE, null, [1, 1000]);
        $form->addRadioList('plateOrderType', 'Řazení plat', $plateOrderTypes)
            ->setDefaultValue(Stand::PLATE_ORDER_RIGHT_FIRST)
            ->setOption('id', 'plate-order-type-wrapper')
            ->addConditionOn($typeSelect, BaseForm::EQUAL, Stand::TYPE_PLATES)->setRequired();
        $form->addText('width', 'Šířka (cm)')
            ->setRequired()
            ->addRule(BaseForm::FLOAT)
            ->addRule(BaseForm::RANGE, null, [0, 100000]);
        $form->addText('depth', 'Hloubka (cm)')
            ->setRequired()
            ->addRule(BaseForm::FLOAT)
            ->addRule(BaseForm::RANGE, null, [0, 100000]);
        $form->addText('height', 'Výška (cm)')
            ->setRequired()
            ->addRule(BaseForm::FLOAT)
            ->addRule(BaseForm::RANGE, null, [0, 100000]);
        $form->addText('qr', 'QR kód', null, 1000);
        $pictureUpload = $form->addUpload('picture', 'Foto 1')->addRule(BaseForm::IMAGE);
        $secondPictureUpload = $form->addUpload('secondPicture', 'Foto 2')->addRule(BaseForm::IMAGE);
        $form->addCheckbox("changeEmail", "Při změně poslat email" )
            ->addCondition(BaseForm::EQUAL, true);
        $form->addCheckbox("piecePriceTag", "Tisknout kusové cenovky" )
            ->addCondition(BaseForm::EQUAL, true);
        $form->addCheckbox("platePriceTag", "Tisknout platové cenovky" )
            ->addCondition(BaseForm::EQUAL, true);

        if ($stand && $stand->picture) {
            $pictureUpload->setOption('description', $stand->picture->name);
        }

        if ($stand && $stand->secondPicture) {
            $secondPictureUpload->setOption('description', $stand->secondPicture->name);
        }

        $form->addSubmit('edit', 'Uložit');

        $form->onValidate[] = function (BaseForm $form, array $values) use ($stand): void {
            if ($stand) {
                if (
                    $values['picture']->hasFile()
                    && $stand->secondPicture
                    && $values['picture']->getSanitizedName() === $stand->secondPicture->name
                ) {
                    $form['picture']->addError('Tento soubor je již přiřazen k Foto 2');
                }

                if (
                    $values['secondPicture']->hasFile()
                    && $stand->picture
                    && $values['secondPicture']->getSanitizedName() === $stand->picture->name
                ) {
                    $form['secondPicture']->addError('Tento soubor je již přiřazen k Foto 1');
                }
            }

            if (
                $values['picture']->hasFile()
                && $values['secondPicture']->hasFile()
                && $values['picture']->getSanitizedName() === $values['secondPicture']->getSanitizedName()
            ) {
                $form['picture']->addError('Tento soubor je stejný jako soubor k Foto 2');
            }
        };

        $form->onSuccess[] = function (array $values): void {
            /** @var FileUpload $picture */
            $picture = $values['picture'];
            unset($values['picture']);
            /** @var FileUpload $picture */
            $secondPicture = $values['secondPicture'];
            unset($values['secondPicture']);

            if ($values['type'] === Stand::TYPE_PEACES) {
                $values['plateOrderType'] = '';
            }

            if ($values['producer'] === -1) {
                $values['producer'] = '';
            }

            $codeParts = explode('/', $values['code']);
            $values['codeFirstPart'] = $codeParts[0];
            $values['codeSecondPart'] = $codeParts[1];
            $isEdit = $this->getAction() === 'edit';
            /** @var Stand $stand */
            $stand = $isEdit
                ? $this->orm->stands->updateEntity($this->getParameter('id'), null, $values)
                : $this->orm->stands->insertEntity(null, $values);

            if ($picture->hasFile()) {
                if ($stand->picture) {
                    $this->orm->files->updateFile($stand->picture, $picture);
                } else {
                    $stand->picture = $this->orm->files->createFile($picture, StandRepository::IMAGE_DIR . "/$stand->id");
                    $this->orm->stands->persistAndFlush($stand);
                }
            }

            if ($secondPicture->hasFile()) {
                if ($stand->secondPicture) {
                    $this->orm->files->updateFile($stand->secondPicture, $secondPicture);
                } else {
                    $stand->secondPicture = $this->orm->files->createFile($secondPicture, StandRepository::IMAGE_DIR . "/$stand->id");
                    $this->orm->stands->persistAndFlush($stand);
                }
            }

            $this->flashMessage('Stojan byl ' . ($isEdit ? 'upraven' : 'vytvořen'));
            $this->redirect('default');
        };
        return $form;
    }

    /** Fromular na upravu platu stojanu */
    public function createComponentStandPlateForm(): BaseForm
    {
        $action = $this->getAction();
        $id = $this->getParameter('id');
        $plate = null;

        if ($action === 'addPlate') {
            $stand = $this->orm->stands->getById($id);
        } else {
            $plate = $this->orm->standPlates->getById($id);
            $stand = $plate->stand;
        }

        $form = new BaseForm();
        $orderInput = $form->addInteger('order', 'Pořadí')
            ->setRequired()
            ->addRule(BaseForm::RANGE, null, [1, 100]);

        if ($this->getAction() === 'addPlate') {
            $excludeOrders = $stand->plates->toCollection()->findBy(['deleted' => false])->fetchPairs(null, 'order');
            $orderInput->addRule(BaseForm::IS_NOT_IN, null, $excludeOrders);
        }

        $form->addText('description', 'Série/Popis', null, 50)->setRequired();
        $form->addText('dimension', 'Rozměr', null, 50);
        $form->addText('qr', 'QR kód', null, 1000);
        $pictureUpload = $form->addUpload('picture', 'Foto')->addRule(BaseForm::IMAGE);

        if ($plate && $plate->picture) {
            $pictureUpload->setOption('description', $plate->picture->name);
        }

        $form->addSubmit('submit', 'Uložit');

        $form->onSuccess[] = function (array $values) use ($stand): void {
            /** @var FileUpload $picture */
            $picture = $values['picture'];
            unset($values['picture']);

            if ($this->getAction() === 'addPlate') {
                $values['stand'] = $stand->id;
                $plate = $this->orm->standPlates->insertEntity(null, $values);
            } else {
                $plate = $this->orm->standPlates->getById($this->getParameter('id'));

                if ($plate->order !== $values['order']) {
                    $this->orm->standPlates->changeOrder($plate, $values['order']);
                }

                $plate->description = $values['description'];
                $plate->dimension = $values['dimension'];
                $plate->qr = $values['qr'];
                $this->orm->standPlates->persist($plate);
                $this->orm->standPlates->flush();
            }

            if ($picture->hasFile()) {
                if ($plate->picture) {
                    $this->orm->files->updateFile($plate->picture, $picture);
                } else {
                    $plate->picture = $this->orm->files->createFile($picture, StandRepository::IMAGE_DIR . "/$stand->id/plates/$plate->id");
                    $this->orm->standPlates->persistAndFlush($plate);
                }
            }

            $this->flashMessage('Položka byla uložena');

            if ($this->getParameter('defaultAction')) {
                $this->redirect('default');
            }

            $this->redirectPlate($stand, $plate->order);
        };

        return $form;
    }

    /** Formular na upravu polozek na stojanu/platu */
    public function createComponentStandPlateItemForm(): BaseForm
    {
        $action = $this->getAction();
        $id = $this->getParameter('id');
        $item = null;

        if ($action === 'addPlateItem') {
            $plate = $this->orm->standPlates->getById($id);
        } else {
            $item = $this->orm->standPlateItems->getById($id);
            $plate = $item->plate;
        }

        $maxOrder = $plate->stand->hasPlates ? 100 : $plate->stand->unitCount;
        $form = new BaseForm();
        $orderInput = $form->addInteger('order', 'Pořadí')
            ->setRequired()
            ->addRule(BaseForm::RANGE, null, [1, $maxOrder]);

        if ($action === 'addPlateItem') {
            $excludeOrders = $plate->items->toCollection()->findBy(['deleted' => false])->fetchPairs(null, 'order');
            $orderInput->addRule(BaseForm::IS_NOT_IN, null, $excludeOrders);
        }

        $form->addSelect('item', 'Položka')
            ->setPrompt('-- Vyhledat položku --')
            ->getControlPrototype()->addClass('select2-ignore');

        if ($plate->stand->hasPlates) {
            $form->addCheckbox('photoItem', 'Malý vzorek');
            $form->addCheckbox('seriesItem', 'Položka série');
        }

        $pictureUpload = $form->addUpload('picture', 'Foto')->addRule(BaseForm::IMAGE);

        if ($item && $item->picture) {
            $pictureUpload->setOption('description', $item->picture->name);
        }

        $form->addSubmit('submit', 'Uložit');

        $form->onValidate[] = function (BaseForm $form): void {
            $itemId = $this->getRequest()->getPost('item');
            if (!$itemId) {
                $form['item']->addError('Položka je povinná.');
                return;
            }
            $item = $this->orm->stockItems->getById($itemId);
            if (!$item) {
                $form['item']->addError('Položka nebyla v systému nalezena.');
            } else {
                $form['item']->setItems([$item->id => $item->title])
                    ->setValue($item->id);
            }
        };

        $form->onSuccess[] = function (array $values) use ($plate): void {
            if (isset($values['picture'])) {
                /** @var FileUpload $picture */
                $picture = $values['picture'];
                unset($values['picture']);
            }

            if ($this->getAction() === 'addPlateItem') {
                $values['plate'] = $plate->id;
                $plateItem = $this->orm->standPlateItems->insertEntity(null, $values);
            } else {
                $plateItem = $this->orm->standPlateItems->getById($this->getParameter('id'));

                if ($plateItem->item->id !== $values['item']) {
                    $plateItem = $this->orm->standPlateItems->createClone($plateItem);
                }

                if ($plateItem->order !== $values['order']) {
                    $this->orm->standPlateItems->changeOrder($plateItem, $values['order']);
                }

                $plateItem->item = $this->orm->stockItems->getById($values['item']);
                $plateItem->photoItem = $values['photoItem'] ?? false;
                $plateItem->seriesItem = $values['seriesItem'] ?? false;

                if ($plateItem->seriesItem) {
                    // Vynutit jedinečnost seriesItem = true v rámci stejného StandPlate
                    $existingItems = $this->orm->standPlateItems->findBy([
                        'plate' => $plateItem->plate, // stejné plate
                        'seriesItem' => true,
                    ]);

                    foreach ($existingItems as $existingItem) {
                        if ($existingItem !== $plateItem) {
                            $existingItem->seriesItem = false;
                            $this->orm->standPlateItems->persist($existingItem);
                        }
                    }
                }
                $this->orm->standPlateItems->persist($plateItem);
                $this->orm->standPlateItems->flush();
            }

            if (isset($picture) && $picture->hasFile()) {
                if ($plateItem->picture) {
                    $this->orm->files->updateFile($plateItem->picture, $picture);
                } else {
                    $stand = $plateItem->plate->stand;
                    $imageSubDir = $stand->hasPlates
                        ? "/$stand->id/plates/$plate->id/items/$plateItem->id"
                        : "/$stand->id/items/$plateItem->id";
                    $plateItem->picture = $this->orm->files->createFile($picture, StandRepository::IMAGE_DIR . $imageSubDir);
                    $this->orm->standPlateItems->persistAndFlush($plateItem);
                }
            }

            $this->flashMessage('Položka byla uložena');

            if ($this->getParameter('defaultAction')) {
                $this->redirect('default');
            }

            $this->redirectPlateItem($plate, $plateItem->order);
        };

        return $form;
    }

    /** Formular na DL stojanu */
    public function createComponentStandNoteForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addText('stand', 'Stojan')->setDisabled();
        $form->addSelect('depot', 'Partner')->setPrompt('--Vyberte--')
            ->getControlPrototype()->addClass('select2-ignore');
        $form->addDate('date', 'Datum expedice')
            ->setRequired()
            ->setDefaultValue(date(DateTime::CZ_DATE));
        $form->addInteger('invoice', 'Faktura')
            ->addRule(BaseForm::MAX, null, 99999999)
            ->setHtmlAttribute('autocomplete', 'off');
        $noteInput = $form->addInteger('note', 'DL')
            ->addRule(BaseForm::RANGE, null, [10000, 99999])
            ->setHtmlAttribute('autocomplete', 'off');
        $noteDateInput = $form->addDate('noteDate', 'Datum DL');
        $noteDateInput->addConditionOn($noteInput, BaseForm::FILLED)
            ->setRequired();
        $noteInput->addConditionOn($noteDateInput, BaseForm::FILLED)
            ->setRequired();
        $form->addTextArea('remark', 'Poznámka');

        $form->addSubmit('submit', 'Uložit');

        $form->onValidate[] = function (BaseForm $form) {
            $depotId = $this->getHttpRequest()->getPost('depot');
            $isAddAction = $this->getAction() === 'addStandNote';

            if (!$depotId) {
                $form['depot']->addError('Toto pole je povinné.');
                return;
            }

            $depot = $this->orm->companyDepots->getById($depotId);

            if (!$depot) {
                $form['depot']->addError('Partnerská pobočka nenalezena.');
            }

            $form['depot']->setItems([$depot->id => $depot->title])->setValue($depot->id);

            $standId = $isAddAction
                ? $this->getParameter('id')
                : $this->orm->standNotes->getById($this->getParameter('id'))->stand->id;
            $filter = [
                'depot->id' => $depot->id,
                'stand->id' => $standId,
                'removeDate' => null
            ];

            if (!$isAddAction) {
                $filter['id!='] = $this->getParameter('id');
            }

            if ($this->orm->standNotes->getBy($filter)) {
                $form['depot']->addError('Partnerská pobočka již tento stojan má');
            }
        };

        $form->onSuccess[] = function (array $values) {
            $isAddAction = $this->getAction() === 'addStandNote';

            if ($isAddAction) {
                $values['stand'] = $this->getParameter('id');
                $entity = $this->orm->standNotes->insertEntity(null, $values);
            } else {
                $entity = $this->orm->standNotes->updateEntity($this->getParameter('id'), null, $values);
            }

            $this->flashMessage('Položka byla ' . ($isAddAction ? 'přidána' : 'upravena'));
            $this->redirect('standNotes', ['id' => $entity->stand->id]);
        };

        return $form;
    }

    /** Form na odebrani stojanu od partnera */
    public function createComponentRemoveStandNoteForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addHidden('id')->setRequired();
        $form->addDate('removeDate', 'Datum odstranění')
            ->setRequired()
            ->setDefaultValue(date(DateTime::CZ_DATE));
        $form->addInteger('removeNote', 'DL')
            ->addRule(BaseForm::RANGE, null, [10000, 99999])
            ->setHtmlAttribute('autocomplete', 'off');
        $form->addSubmit('submit', 'Uložit');

        $form->onValidate[] = function (array $values): void {
            $standNote = $this->orm->standNotes->getById($values['id']);
            if (!$standNote) {
                $this->error('Položka nenalezena');
            }
        };

        $form->onSuccess[] = function (array $values): void {
            $standNote = $this->orm->standNotes->getById($values['id']);
            unset($values['id']);
            $values['removeBy'] = $this->getUser()->getId();
            $this->orm->standNotes->updateEntity($standNote->id, null, $values);
            $this->flashMessage('Stojan byl odebrán');
            $this->redirect('standNotes', ['id' => $standNote->stand->id]);
        };

        return $form;
    }

    /** Form na import stojanu pomoci CSV souboru */
    public function createComponentStandImportForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addUpload('stands', 'CSV soubor')
            ->setRequired()
            ->addRule(BaseForm::PATTERN, 'Pouze csv soubor', '.*\.csv$');
        $form->addSubmit('import', 'Importovat');

        $form->onSuccess[] = function (array $values): void {
            $action = $this->getAction();
            $error = $this->$action($values['stands']->contents);

            if ($error) {
                $this->flashMessage($error, self::MSG_ERROR);
                $this->redirect('this');
            }

            $this->flashMessage('Import proběhl úspěšně');
            $this->redirect('default');
        };

        return $form;
    }

    /** Zpracovani CSV souboru s DL stojanu */
    private function importStandNotes(string $fileContent): string
    {
        if (!$fileContent) {
            return 'Soubor neobsahuje žádná data';
        }

        $stands = $this->orm->stands->findAll()->fetchPairs('code');
        $depots = $this->orm->companyDepots->loadStoreDepots(Store::OSTRAVA_MAIN_STORAGE);
        $separator = "\r\n";
        $line = strtok($fileContent, $separator);

        while ($line !== false) {
            $csvData = str_getcsv($line, ';');

            if (count($csvData) < 7) {
                return 'Neplatný počet sloupců na řádku: ' . implode(';', $csvData);
            }

            $ico = intval(trim($csvData[0]));
            $voj = trim($csvData[1]);
            $standCode = trim($csvData[2]);
            $note = intval(trim($csvData[3]));
            $noteDate = DateTime::createFromFormat(DateTime::CZ_DATE, trim($csvData[4]));
            $invoice = intval(trim($csvData[5]));
            $date = DateTime::createFromFormat(DateTime::CZ_DATE, trim($csvData[6]));
            $remark = trim($csvData[7] ?? '');

            if (!$ico || !$standCode || !$date || ($note && !$noteDate)) {
                return 'Nevalidní data na řádku: ' . implode(';', $csvData);
            }

            if ($note && ($note < 10000 || $note > 99999)) {
                return "Nevalidní číslo DL $note na řádku " . implode(';', $csvData);
            }

            $depotKey = $ico . BaseMapper::DATA_STRING_SEPARATOR . $voj;

            /** @var Stand $stand */
            $stand = $stands[$standCode] ?? null;
            $depotId = $depots[$depotKey] ?? null;

            if (!$stand) {
                return "Neplatné ID stojanu $standCode na řádku " . implode(';', $csvData);
            }

            if (!$depotId) {
                return "Nenalezena partnerská pobočka ICO $ico (voj '$voj') na řádku " . implode(';', $csvData);
            }

            $standNote = $this->orm->standNotes->getBy([
                'depot->id' => $depotId,
                'stand->id' => $stand->id,
                'removeDate' => null
            ]);

            if ($standNote) {
                $line = strtok($separator);
                continue;
            }

            $standNote = new StandNote();
            $standNote->depot = $this->orm->companyDepots->getById($depotId);
            $standNote->stand = $stand;
            $standNote->date = $date;

            if ($note) {
                $standNote->note = $note;
                $standNote->noteDate = $noteDate;
            }

            if ($invoice) {
                $standNote->invoice = $invoice;
            }

            if ($remark) {
                if (!mb_check_encoding($remark, 'UTF-8')) {
                   $remark = iconv('WINDOWS-1250', 'UTF-8', $remark);
                }
                $standNote->remark = $remark;
            }

            $this->orm->standNotes->persist($standNote);
            $line = strtok($separator);
        }

        $this->orm->standNotes->flush();
        return '';
    }

    public function hasPlateHistoryItems(int $plate, int $order): bool
    {
        return $this->orm->standPlateItems->findBy(['plate->id' => $plate, 'order' => $order, 'deleted' => true])
                ->countStored() > 0;
    }

    public function hasStandHistoryPlates(int $stand, int $order): bool
    {
        return $this->orm->standPlates->findBy(['stand->id' => $stand, 'order' => $order, 'deleted' => true])
                ->countStored() > 0;
    }

    private function redirectPlateItem(StandPlate $plate, int $order = 0): void
    {
        if ($plate->stand->hasPlates) {
            $this->redirect('plateItems', ['id' => $plate->id]);
        }

        if ($order < 10) {
            $this->redirect('standItems', ['id' => $plate->stand->id]);
        }

        $anchorId = intval($order / 10) * 10;
        $this->redirect("standItems#item-id-$anchorId", ['id' => $plate->stand->id]);
    }

    private function redirectPlate(Stand $stand, int $order = null): void
    {
        if ($order < 10) {
            $this->redirect('standPlates', ['id' => $stand->id]);
        }

        $anchorId = intval($order / 10) * 10;
        $this->redirect("standPlates#plate-id-$anchorId", ['id' => $stand->id]);
    }

    private function setProductFilter(int $product): void
    {
        $this->getSession('standProductFilter')->product = $product;
    }

    private function getProductFilter(): int
    {
        return $this->getSession('standProductFilter')->product ?? 0;
    }

    private function removePicture(int $fileId, string $repository): bool
    {
        if ($repository === 'stands') {
            $stand = $this->orm->stands->getBy([
                ICollection::OR,
                'picture->id' => $fileId,
                'secondPicture->id' => $fileId
            ]);

            if (!$stand) {
                return false;
            }

            if ($stand->picture && $stand->picture->id === $fileId) {
                $picture = $stand->picture;
                $stand->picture = null;
            } else {
                $picture = $stand->secondPicture;
                $stand->secondPicture = null;
            }

            $this->orm->stands->persistAndFlush($stand);
            $this->orm->files->removeFile($picture);
            return true;
        }

        if ($repository === 'standPlates' || $repository === 'standPlateItems') {
            $entity = $this->orm->{$repository}->getBy(['picture->id' => $fileId]);

            if (!$entity) {
                return false;
            }

            $picture = $entity->picture;
            $entity->picture = null;
            $this->orm->{$repository}->persistAndFlush($entity);
            $this->orm->files->removeFile($picture);
            return true;
        }

        return false;
    }
}
