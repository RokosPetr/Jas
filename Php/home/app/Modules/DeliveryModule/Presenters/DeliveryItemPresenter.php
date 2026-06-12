<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Core\Component\Form\FilterContainer;
use App\Modules\DeliveryModule\Orm\DeliveryItems\DeliveryItem;
use App\Modules\DeliveryModule\Orm\DeliveryNotes\DeliveryNote;
use App\Modules\Presenters\SecurePresenter;
use App\Modules\SystemModule\Orm\Stores\Store;
use Nextras\Dbal\Utils\DateTimeImmutable;
use Nextras\Orm\Entity\ToArrayConverter;

/** Presenter pro spravu dodacích listu */
final class DeliveryItemPresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'Expedované doklady',
        'edit' => 'Opravit doklad',
        'add' => 'Přidat doklad'
    ];

    /**
     * start up metoda
     * - omezeni akci pro vekoprodejny (Michalkovice, Hlucin), kde se polozky importuji a nevkladaji rucne
     */
    public function startup(): void
    {
        parent::startup();
        if (in_array($this->selectedStore, Store::MAIN_STORAGES) && $this->action !== 'default') {
            $this->flashMessage('Tato akce není povolena', self::MSG_ERROR);
            $this->redirect('default');
        }
    }

    /** Pridani dodaciho listu nebo dalsiho odberu k dodacimu listu */
    public function actionAdd(): void
    {
        $this->template->storeName = $this->orm->stores->getById($this->selectedStore)->name;
    }

    /** Oprava dodaciho listu */
    public function actionEdit(int $id): void
    {
        $deliveryItem = $this->orm->deliveryItems->getById($id);

        if (!$deliveryItem) {
            $this->error('Položka nenalezena');
        }

        $defaults = $deliveryItem->toArray(ToArrayConverter::RELATIONSHIP_AS_ID);
        $this['deliveryItemForm']->setDefaults($defaults);
        $this->template->storeName = $this->orm->stores->getById($this->selectedStore)->name;
    }

    /** Odstraneni dodaciho listu */
    public function actionDelete(int $id): void
    {
        $deliveryItem = $this->orm->deliveryItems->getById($id);

        if (!$deliveryItem) {
            $this->error('Položka nenalezena');
        }

        if ($deliveryItem->issueYear !== intval(date('Y'))) {
            // Manualni nastaveni stavu u DL v minulosti
            $note = $this->loadDeliveryNote($deliveryItem);

            if ($note) {
                switch ($note->stateChar) {
                    case 'P':
                        $note->state = DeliveryNote::STATE_PREPARATION;
                        break;
                    case 'X':
                        $note->state = DeliveryNote::STATE_LOADING;
                        break;
                    default:
                        $note->state = DeliveryNote::STATE_RESERVATION;
                }
                $this->orm->deliveryNotes->persistAndFlush($note);
            }
        }

        $deliveryItem->getRepository()->removeAndFlush($deliveryItem);
        $this->flashMessage('Dodací list byl smazán');
        $this->redirect('default');
    }

    /** AJAX odpoved pro nalezeni dod. listu prodejny podle cisla a roku vystaveni */
    public function handleGetDeliveryItem(): void
    {
        $response = ['result' => false];
        $deliveryItem = $this->orm->deliveryItems->getBy([
            'store->id' => $this->selectedStore,
            'number' => $this->getParameter('number'),
            'issueYear' => $this->getParameter('issueYear')
        ]);

        if ($deliveryItem) {
            $response = [
                'result' => true,
                'state' => $deliveryItem->state,
                'remark' => $deliveryItem->remark
            ];
        }

        $this->sendJson($response);
    }

    /** Datagrid s dodacimi listy */
    protected function createComponentDeliveryItems(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->deliveryItems);
        $grid->settings->setDataSourceFilter(['store' => $this->selectedStore]);
        $grid->settings->setFulltextColumns(['number', 'remark']);
        $grid->settings->showExport();

        $grid->addColumn('number', 'Dodací list')->enableSort();
        $grid->addColumn('issueYear', 'Rok vystavení');
        $grid->addColumn('dispatchStart', 'První expedice')->dateFormat(DATE)
            ->enableSort();
        $grid->addColumn('dispatchEnd', 'Kompletní expedice')->dateFormat(DATE);
        $grid->addColumn('remark', 'Poznámka');

        $grid->addOtherColumn('id', 'ID')->enableSort(BaseDatagrid::ORDER_DESC);
        $grid->addOtherColumn('created', 'Vytvořeno');
        $grid->addOtherColumn('updated', 'Změněno');

        $grid->addLegend('Předchystáno', 'legend_azure', "\$state == " . DeliveryItem::STATE_PREPARED);
        $grid->addLegend('Částečně vyvezeno', 'legend_orange', "\$state == " . DeliveryItem::STATE_OPEN_DISPATCH);
        $grid->addLegend('Kompletně vyvezeno', 'legend_black', "\$state == " . DeliveryItem::STATE_LOADED);

        if (!in_array($this->selectedStore, Store::MAIN_STORAGES)) {
            $grid->addTopAction('add', 'Přidat doklad');
            $grid->addRowAction('edit', 'Opravit DL');
            $grid->addRowAction('delete', 'Smazat DL');
        }

        $grid->setFilterFormFactory(function (): FilterContainer {
            $form = new FilterContainer();
            $form->addContainer('dispatchStart');
            $form->addDateFrom('dispatchStart', 'od');
            $form->addDateTo('dispatchStart', 'do');
            $dispatchStates = [
                '' => 'Vše',
                DeliveryItem::STATE_PREPARED => 'Předchystáno',
                DeliveryItem::STATE_OPEN_DISPATCH => 'Částečně vyvezeno',
                DeliveryItem::STATE_LOADED => 'Kompletně vyvezeno'
            ];
            $form->addSelect('state', 'Stav', $dispatchStates);
            return $form;
        });

        return $grid;
    }

    /** Formular na upravu dodaciho listu */
    protected function createComponentDeliveryItemForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addInteger('number', 'Dodací list')
            ->addRule(BaseForm::RANGE, null, [1000, 99999])
            ->setRequired();

        $currentYear = intval(date('Y'));
        $yearOptions = range(2020, $currentYear);
        $form->addSelect('issueYear', 'Rok vystavení', array_combine($yearOptions, $yearOptions))
            ->setRequired()
            ->setDefaultValue($currentYear)
            ->getControlPrototype()->addClass('select2-ignore');

        $form->addRadioList('state', 'Stav', [
            DeliveryItem::STATE_PREPARED => 'Předchystáno',
            DeliveryItem::STATE_OPEN_DISPATCH => 'Částečně vyvezeno',
            DeliveryItem::STATE_LOADED => 'Kompletně vyvezeno'
        ])->setRequired()->setOption('class', 'state-radio-wrapper');

        $form->addTextArea('remark', 'Poznámka', null, 5)
            ->addRule(BaseForm::MAX_LENGTH, null, 2000);

        $form->addSubmit($this->action, $this->action === 'add' ? 'Uložit' : 'Opravit');

        if ($this->action === 'add') {
            $form->addSubmit('continue', 'Uložit a pokračovat');
        }

        $form->onValidate[] = function (BaseForm $form, array $values): void {
            $deliveryItem = $this->orm->deliveryItems->getBy([
                'store->id' => $this->selectedStore,
                'number' => $values['number'],
                'issueYear' => $values['issueYear']
            ]);

            if (!$deliveryItem) {
                return;
            }

            if ($this->action === 'add' && $deliveryItem->state === DeliveryItem::STATE_LOADED) {
                $form['number']->addError("Dodací list $deliveryItem->number byl již kompletně vyvezený");
            }

            if ($this->action === 'edit' && $deliveryItem->id !== intval($this->getParameter('id'))) {
                $form['number']->addError("Dodací list $deliveryItem->number je již evidovaný.");
            }
        };

        $form->onSuccess[] = [$this, 'deliveryItemFormSuccess'];

        return $form;
    }

    /** Success callback formulare dodaciho listu */
    public function deliveryItemFormSuccess(BaseForm $form, array $values): void
    {
        $redirect = $this->action === 'edit' || $form['add']->isSubmittedBy() ? 'default' : 'add';
        $state = $values['state'];
        $now = new DateTimeImmutable();

        if ($this->action === 'edit') {
            $deliveryItem = $this->orm->deliveryItems->getById($this->getParameter('id'));
            $deliveryItem->number = $values['number'];
            $deliveryItem->issueYear = $values['issueYear'];
            $message = 'Dodací list byl opraven';
        } else {
            $deliveryItem = $this->orm->deliveryItems->getBy([
                'store->id' => $this->selectedStore,
                'number' => $values['number'],
                'issueYear' => $values['issueYear']
            ]);

            if ($deliveryItem) {
                $message = 'Dodací list byl upraven';
            } else {
                $deliveryItem = new DeliveryItem();
                $deliveryItem->store = $this->orm->stores->getById($this->selectedStore);
                $deliveryItem->number = $values['number'];
                $deliveryItem->issueYear = $values['issueYear'];
                $message = 'Dodací list byl vytvořen';
            }
        }

        $deliveryItem->remark = $values['remark'] ?: null;

        if ($state === DeliveryItem::STATE_PREPARED) {
            $deliveryItem->dispatchStart = null;
            $deliveryItem->dispatchEnd = null;
        }

        if ($state === DeliveryItem::STATE_OPEN_DISPATCH) {
            $deliveryItem->dispatchStart ??= $now;
            $deliveryItem->dispatchEnd = null;
        }

        if ($state === DeliveryItem::STATE_LOADED) {
            $deliveryItem->dispatchStart ??= $now;
            $deliveryItem->dispatchEnd ??= $now;
        }

        $this->orm->deliveryItems->persistAndFlush($deliveryItem);

        if ($deliveryItem->issueYear !== intval(date('Y'))) {
            // Manualni nastaveni stavu u DL v minulosti
            $note = $this->loadDeliveryNote($deliveryItem);

            if ($note) {
                switch ($deliveryItem->state) {
                    case DeliveryItem::STATE_PREPARED:
                        $note->state = DeliveryNote::STATE_PREPARED;
                        break;
                    case DeliveryItem::STATE_OPEN_DISPATCH:
                        $note->state = DeliveryNote::STATE_DISPATCHING;
                        break;
                    case DeliveryItem::STATE_LOADED:
                        $note->state = DeliveryNote::STATE_DONE;
                }
                $this->orm->deliveryNotes->persistAndFlush($note);
            }
        }

        $this->flashMessage($message);
        $this->redirect($redirect);
    }

    private function loadDeliveryNote(DeliveryItem $deliveryItem): ?DeliveryNote
    {
        return $this->orm->deliveryNotes->getBy([
            'number' => $deliveryItem->number,
            'store->id' => $deliveryItem->store->id,
            'movementType' => [DeliveryNote::TYPE_SALE, DeliveryNote::TYPE_TRANSFER_OUT],
            'date>=' => "$deliveryItem->issueYear-01-01",
            'date<=' => "$deliveryItem->issueYear-12-31"
        ]);
    }
}
