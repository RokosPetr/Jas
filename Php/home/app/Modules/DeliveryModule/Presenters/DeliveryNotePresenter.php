<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Core\Component\Form\FilterContainer;
use App\Core\Utils\DateTime;
use App\Modules\CliModule\Service\MovementImporter;
use App\Modules\DeliveryModule\Orm\DeliveryNotes\DeliveryNote;
use App\Modules\DeliveryModule\Service\BadTransfersExporter;
use App\Modules\Presenters\SecurePresenter;
use App\Modules\SystemModule\Orm\Stores\Store;

/** Presenter pro spravu pohybu pobocek (dodacich listu) */
final class DeliveryNotePresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'Pohyby pobocky',
        'preview' => 'Náhled dokladu',
        'loadingNotes' => 'Nevyvezené [X] dodáky',
        'badTransfersIn' => 'Kontrola převodek',
        'badTransfersOut' => 'Kontrola převodek',
        'checkedBadTransfers' => 'Kontrola převodek'
    ];

    /** @inject  */
    public BadTransfersExporter $badTransfersExporter;

    /** Seznam dodacich listu */
    public function renderDefault(): void
    {
        $storeName = $this->orm->stores->getById($this->selectedStore)->name;
        $import = $this->orm->imports->getImportByName("Pohyby zboží - $storeName");

        if ($import) {
            $this->template->importAt = $import->date;
            $this->template->nextImportAt = MovementImporter::getNextMovementsImport($this->selectedStore, $import->date);
        }
    }

    /** Nevyvezene dodaci listy */
    public function renderLoadingNotes(): void
    {
        $storeName = $this->orm->stores->getById($this->selectedStore)->name;
        $import = $this->orm->imports->getImportByName("Pohyby zboží - $storeName");

        if ($import) {
            $this->template->importAt = $import->date;
            $this->template->nextImportAt = MovementImporter::getNextMovementsImport($this->selectedStore, $import->date);
        }
        // U velkoskladu zatim nemame data pro zobrazeni nevyvezenych dl
        $this->template->showGrid = !in_array($this->selectedStore, Store::MAIN_STORAGES);
    }

    /** Nahled dodaciho listu */
    public function actionPreview(int $id): void
    {
        $note = $this->orm->deliveryNotes->getById($id);
        if (!$note) {
            $this->error('Položka nenalezena');
        }
        $this->template->note = $note;
        $this->sideDialogAjaxHandler();
    }

    /** Nahled dodaciho listu */
    public function actionTransferPreview(int $id): void
    {
        $note = $this->orm->deliveryNotes->getById($id);
        if (!$note || $note->movementType !== DeliveryNote::TYPE_TRANSFER_IN) {
            $this->error('Položka nenalezena');
        }
        $this->template->note = $note;
        $this->sideDialogAjaxHandler();
    }

    /** Oznacit prevodku jako zkontrolovano */
    public function actionSetTransferChecked(int $id, string $redirectAction): void
    {
        $note = $this->orm->deliveryNotes->getById($id);
        if (!$note || !in_array($note->movementType, DeliveryNote::TRANSFER_TYPES)) {
            $this->error('Položka nenalezena');
        }
        $note->checked = true;
        $this->orm->deliveryNotes->persistAndFlush($note);
        $this->redirect($redirectAction);
    }

    /** Zrusit oznaceni prevodky jako zkontrolovano */
    public function actionUnsetTransferChecked(int $id, string $redirectAction): void
    {
        $note = $this->orm->deliveryNotes->getById($id);
        if (!$note || !in_array($note->movementType, DeliveryNote::TRANSFER_TYPES)) {
            $this->error('Položka nenalezena');
        }
        $note->checked = false;
        $this->orm->deliveryNotes->persistAndFlush($note);
        $this->redirect($redirectAction);
    }

    /** Pridani polozky do dokladu */
    public function actionAddItem(int $id): void
    {
        $note = $this->orm->deliveryNotes->getById($id);
        if (!$note) {
            $this->error('Položka nenalezena');
        }
        $defaults = [
            'note' => $note->number,
            'store' => $note->store->name,
            'date' => $note->date->format(DateTime::CZ_DATE),
            'movement' => '0' . $note->movementNumber
        ];
        $this['addNoteItemForm']->setDefaults($defaults);
    }

    /** Pridani poznamky k dokladu */
    public function actionAddRemark(int $id): void
    {
        $note = $this->orm->deliveryNotes->getById($id);
        if (!$note) {
            $this->error('Položka nenalezena');
        }
        $this['deliveryNoteRemarkForm']->setDefaults([
            'remark' => $note->remark,
            'redirect' => $this->getParameter('redirectAction')
        ]);
        $this->template->note = $note;
        $this->sideDialogAjaxHandler();
    }

    /** Export chybnych prevodek do excelu */
    public function actionExportBadTransfers(): void
    {
        $response = $this->badTransfersExporter->createExcelExport();
        $this->sendResponse($response);
    }

    /** Grid s dodacimi listy */
    public function createComponentDeliveryNotes(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->deliveryNotes);
        $grid->addCellsTemplate(__DIR__ . '/../templates/DeliveryNote/grid.cells.latte');
        $grid->settings->setDataSourceFilter(['store' => $this->selectedStore]);
        $grid->settings->setFulltextColumns(['number', 'description']);

        $grid->addColumn('number', 'Doklad')->enableSort();
        $grid->addColumn('movementNumber', 'Pohyb')->enableSort();
        $grid->addColumn('date', 'Datum')->dateFormat(DATE)->enableSort(BaseDatagrid::ORDER_DESC);
        $grid->addColumn('partner', 'Partner');
        $grid->addColumn('description', 'Popis');
        $grid->addColumn('netSum', 'Cena')->enableSort();
        $grid->addColumn('bill', 'Faktura');
        $grid->addColumn('depotNote', 'Dodací list');
        $grid->addColumn('weight', 'Váha');

        $grid->addRowAction('preview', 'Položky dokladu')->setSideDialog();
        $grid->addRowAction('addItem', 'Přidat položku', 'plus');

        $grid->addLegend('Rezervace', 'legend_green', "\$state == " . DeliveryNote::STATE_RESERVATION);
        $grid->addLegend(
            'Předchystáno',
            'legend_azure',
            sprintf("\$state == %s || \$state == %s", DeliveryNote::STATE_PREPARATION, DeliveryNote::STATE_PREPARED)
        );
        $grid->addLegend('Částečně vyvezeno', 'legend_orange', "\$state == " . DeliveryNote::STATE_DISPATCHING);
        $grid->addLegend('Nevyvezeno', 'legend_red', "\$state == " . DeliveryNote::STATE_LOADING);
        $grid->addLegend(
            'Vyvezeno',
            'legend_black',
            sprintf(
                "\$state == %s && (\$movementType == %s || \$movementType == %s)",
                DeliveryNote::STATE_DONE,
                DeliveryNote::TYPE_SALE,
                DeliveryNote::TYPE_TRANSFER_OUT
            )
        );

        $grid->setFilterFormFactory(function (): FilterContainer {
            $states = [
                '' => 'Vše',
                DeliveryNote::STATE_RESERVATION => 'Rezervace [ ]',
                DeliveryNote::STATE_PREPARATION => 'Předchystáno [P]',
                DeliveryNote::STATE_PREPARED => 'Předchystáno [X]',
                DeliveryNote::STATE_DISPATCHING => 'Částečně vyvezeno s [X]',
                DeliveryNote::STATE_LOADING => 'Nevyvezeno s [X]'
            ];
            $types = [
                '' => 'Vše',
                DeliveryNote::TYPE_SALE => 'Prodej',
                DeliveryNote::TYPE_TAKINGS => 'Příjem',
                DeliveryNote::TYPE_CANCEL => 'Storno',
                DeliveryNote::TYPE_TRANSFER_IN => 'Převodky z poboček',
                DeliveryNote::TYPE_TRANSFER_OUT => 'Převodky na pobočky'
            ];
            $form = new FilterContainer();
            $form->addContainer('date');
            $form->addDateFrom('date', 'Od')->setDefaultValue('01.01.' . date('Y'));
            $form->addDateTo('date', 'Do');
            $form->addSelect('state', 'Stav', $states);
            $form->addSelect('movementType', 'Typ', $types);
            return $form;
        });

        return $grid;
    }

    /** Grid s nevyvezenymi dodacimi listy */
    public function createComponentLoadingNotes(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->deliveryNotes);
        $grid->addCellsTemplate(__DIR__ . '/../templates/DeliveryNote/grid.cells.latte');
        $grid->settings->setFulltextColumns(['number'])
            ->showExport()
            ->setDataSourceFilter([
                'store' => $this->selectedStore,
                'state' => [DeliveryNote::STATE_LOADING, DeliveryNote::STATE_PREPARED, DeliveryNote::STATE_DISPATCHING],
                'hasItems' => 1
            ]);
        $grid->addColumn('number', 'Dodací list')->enableSort();
        $grid->addColumn('movementNumber', 'Pohyb');
        $grid->addColumn('date', 'Datum')->dateFormat(DATE)->enableSort(BaseDatagrid::ORDER_DESC);
        $grid->addColumn('bill', 'Faktura');
        $grid->addColumn('warehouseRemark', 'Poznámka');

        $grid->addLegend('Předchystáno', 'legend_azure', "\$state == " . DeliveryNote::STATE_PREPARED);
        $grid->addLegend('Částečně vyvezeno', 'legend_orange', "\$state == " . DeliveryNote::STATE_DISPATCHING);

        $grid->setFilterFormFactory(function (): FilterContainer {
            $states = [
                '' => 'Vše',
                DeliveryNote::STATE_PREPARED => 'Předchystáno',
                DeliveryNote::STATE_DISPATCHING => 'Částečně vyvezeno',
                DeliveryNote::STATE_LOADING => 'Nevyvezeno',
            ];
            $form = new FilterContainer();
            $form->addSelect('stateFilter', 'Stav', $states);
            return $form;
        });

        return $grid;
    }

    /** Grid se chybnyma prijmovyma prevodkama */
    protected function createComponentBadTransfersIn(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->deliveryNotes);
        $grid->addCellsTemplate(__DIR__ . '/../templates/DeliveryNote/grid.cells.latte');
        $grid->settings->setDataSourceFilter([
            'id' => $this->orm->deliveryNotes->loadBadTransfers($this->selectedStore)
        ]);

        $grid->addColumn('number', 'Příjmový doklad')->enableSort();
        $grid->addColumn('date', 'Datum')->dateFormat(DATE)->enableSort(BaseDatagrid::ORDER_DESC);
        $grid->addColumn('depot', 'Z pobočky');
        $grid->addColumn('depotNote', 'Výdejní doklad');
        $grid->addColumn('transferError', 'Chyba v dokladech');
        $grid->addColumn('remark', 'Poznámka');

        $grid->addTopAction('badTransfersOut', 'Výdejní doklady');
        $grid->addTopAction('checkedBadTransfers', 'Zkontrolované doklady');
        $grid->addTopAction('exportBadTransfers', 'Export');
        $grid->addRowAction('transferPreview', 'Náhled', 'search')->setSideDialog();

        $firstDayInMonth = (new \DateTime())->modify('first day of this month')->format('Y-m-d');
        $actionRedirect = 'badTransfersIn';

        $grid->addRowAction(
            'setTransferChecked',
            'Označit jako zkontrolováno',
            'check-square-o',
            ['id' => 'row->id', 'redirectAction' => $actionRedirect]
        )->setCondition("\$date < '$firstDayInMonth'");

        $grid->addRowAction(
            'addRemark',
            'Poznámka',
            'comment-o',
            ['id' => 'row->id', 'redirectAction' => $actionRedirect]
        )
            ->setSideDialog()
            ->setCondition("\$date < '$firstDayInMonth'");

        $grid->setFilterFormFactory(function (): FilterContainer {
            $form = new FilterContainer();
            $form->addSelect('hasParent', 'Typ chyby', [
                '' => 'Vše',
                '0' => 'Neexistující výdejní doklad',
                '1' => 'Rozdíl v cenách'
            ]);
            return $form;
        });

        return $grid;
    }

    /** Grid se chybnyma vydejovyma prevodkama */
    protected function createComponentBadTransfersOut(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->deliveryNotes);
        $grid->addCellsTemplate(__DIR__ . '/../templates/DeliveryNote/grid.cells.latte');
        $grid->settings->setDataSourceFilter([
            'date>=' => '01.01.2021', // Kontroluji se doklady az od roku 2021
            'store->id' => $this->selectedStore,
            'movementType' => DeliveryNote::TYPE_TRANSFER_OUT,
            'hasChild' => 0,
            'depot->voj!=' => '88',
            'checked' => false
        ]);

        $grid->addColumn('number', 'Výdejní doklad')->enableSort();
        $grid->addColumn('date', 'Datum')->dateFormat(DATE)->enableSort(BaseDatagrid::ORDER_ASC);
        $grid->addColumn('depot', 'Na pobočku');
        $grid->addColumn('remark', 'Poznámka');

        $grid->addTopAction('badTransfersIn', 'Příjmové doklady');
        $grid->addTopAction('checkedTransfersOut', 'Zkontrolované doklady');
        $grid->addRowAction('preview', 'Položky dokladu')->setSideDialog();

        $firstDayInMonth = (new \DateTime())->modify('first day of this month')->format('Y-m-d');
        $actionRedirect = 'badTransfersOut';

        $grid->addRowAction(
            'setTransferChecked',
            'Označit jako zkontrolováno',
            'check-square-o',
            ['id' => 'row->id', 'redirectAction' => $actionRedirect]
        )->setCondition("\$date < '$firstDayInMonth'");

        $grid->addRowAction(
            'addRemark',
            'Poznámka',
            'comment-o',
            ['id' => 'row->id', 'redirectAction' => $actionRedirect]
        )
            ->setSideDialog()
            ->setCondition("\$date < '$firstDayInMonth'");

        return $grid;
    }

    /** Grid se chybnyma prijmovyma prevodkama oznacenyma jako zkontrolovane */
    protected function createComponentCheckedBadTransfers(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->deliveryNotes);
        $grid->addCellsTemplate(__DIR__ . '/../templates/DeliveryNote/grid.cells.latte');
        $grid->settings->setDataSourceFilter([
            'store->id' => $this->selectedStore,
            'movementType' => DeliveryNote::TYPE_TRANSFER_IN,
            'checked' => true
        ]);

        $grid->addColumn('number', 'Příjmový doklad')->enableSort();
        $grid->addColumn('date', 'Datum')->dateFormat(DATE)->enableSort(BaseDatagrid::ORDER_DESC);
        $grid->addColumn('depot', 'Z pobočky');
        $grid->addColumn('depotNote', 'Výdejní doklad');
        $grid->addColumn('transferError', 'Chyba v dokladech');
        $grid->addColumn('remark', 'Poznámka');

        $actionRedirect = 'checkedBadTransfers';

        $grid->addTopAction('badTransfersIn', 'Příjmové doklady');
        $grid->addRowAction('transferPreview', 'Náhled', 'search')->setSideDialog();
        $grid->addRowAction('unsetTransferChecked',
            'Zrušit označení jako zkontrolováno',
            'remove',
            ['id' => 'row->id', 'redirectAction' => $actionRedirect]
        );

        $grid->addRowAction(
            'addRemark',
            'Poznámka',
            'comment-o',
            ['id' => 'row->id', 'redirectAction' => $actionRedirect]
        )->setSideDialog();

        $grid->setFilterFormFactory(function (): FilterContainer {
            $form = new FilterContainer();
            $form->addContainer('date');
            $form->addDateFrom('date', 'Od');
            $form->addDateTo('date', 'Do');
            return $form;
        });

        return $grid;
    }

    /** Grid se chybnyma vydejovyma prevodkama oznacenyma jako zkontrolovane */
    protected function createComponentCheckedTransfersOut(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->deliveryNotes);
        $grid->addCellsTemplate(__DIR__ . '/../templates/DeliveryNote/grid.cells.latte');
        $grid->settings->setDataSourceFilter([
            'store->id' => $this->selectedStore,
            'movementType' => DeliveryNote::TYPE_TRANSFER_OUT,
            'checked' => true
        ]);

        $grid->addColumn('number', 'Výdejní doklad')->enableSort();
        $grid->addColumn('date', 'Datum')->dateFormat(DATE)->enableSort(BaseDatagrid::ORDER_DESC);
        $grid->addColumn('depot', 'Na pobočku');
        $grid->addColumn('remark', 'Poznámka');

        $actionRedirect = 'checkedTransfersOut';

        $grid->addTopAction('badTransfersOut', 'Výdejní doklady');
        $grid->addRowAction('preview', 'Položky dokladu', 'search')->setSideDialog();
        $grid->addRowAction('unsetTransferChecked',
            'Zrušit označení jako zkontrolováno',
            'remove',
            ['id' => 'row->id', 'redirectAction' => $actionRedirect]
        );

        $grid->addRowAction(
            'addRemark',
            'Poznámka',
            'comment-o',
            ['id' => 'row->id', 'redirectAction' => $actionRedirect]
        )->setSideDialog();

        $grid->setFilterFormFactory(function (): FilterContainer {
            $form = new FilterContainer();
            $form->addContainer('date');
            $form->addDateFrom('date', 'Od');
            $form->addDateTo('date', 'Do');
            return $form;
        });

        return $grid;
    }

    /** Formular na pridani polozky do dokladu */
    protected function createComponentAddNoteItemForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addText('store', 'Pobočka')->setDisabled();
        $form->addText('movement', 'Pohyb')->setDisabled();
        $form->addText('note', 'Dodací list')->setDisabled();
        $form->addText('date', 'Datum')->setDisabled();
        $form->addInteger('regNumber', 'Registrační číslo')->setRequired();
        $form->addText('supplement', 'Doplněk', null, 1);
        $form->addText('amount', 'Množství')->setRequired()->addRule(BaseForm::FLOAT);
        $form->addText('sellPrice', 'Prodejní cena')
            ->setRequired()
            ->addRule(BaseForm::FLOAT);
        $form->addText('buyPrice', 'Nákupní cena')->setRequired()->addRule(BaseForm::FLOAT);
        $form->addText('discount', 'Sleva')->setRequired()->addRule(BaseForm::FLOAT);
        $form->addInteger('tax', 'Daň')->setRequired();
        $form->addSubmit('save', 'Uložit');

        $form->onValidate[] = function (BaseForm $form, array $values) {
            $item = $this->orm->stockVariants->getBy([
                'store->id' => $this->selectedStore,
                'item->regNumber' => $values['regNumber'],
                'supplement' => $values['supplement']
            ]);
            if (!$item) {
                $form['regNumber']->addError('Položka neexistuje');
            }
        };

        $form->onSuccess[] = function (array $values) {
            $item = $this->orm->stockVariants->getBy([
                'store->id' => $this->selectedStore,
                'item->regNumber' => $values['regNumber'],
                'supplement' => $values['supplement']
            ]);
            $values['item'] = $item->id;
            $values['note'] = $this->getParameter('id');
            unset($values['regNumber'], $values['supplement']);
            $this->orm->deliveryNoteItems->insertEntity(null, $values);
            $this->redirect('default');
        };
        return $form;
    }

    /** Form na pridani poznaky k dodacimu listu */
    protected function createComponentDeliveryNoteRemarkForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addTextArea('remark', 'Poznámka');
        $form->addHidden('redirect')->setRequired();
        $form->addSubmit('submit', 'Uložit');

        $form->onSuccess[] = function (array $values): void {
            $note = $this->orm->deliveryNotes->getById($this->getParameter('id'));
            $note->remark = $values['remark'];
            $this->orm->deliveryNotes->persistAndFlush($note);
            $this->redirect($values['redirect']);
        };

        return $form;
    }
}
