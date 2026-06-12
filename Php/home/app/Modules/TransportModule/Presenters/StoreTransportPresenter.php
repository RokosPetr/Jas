<?php
declare(strict_types=1);

namespace App\Modules\TransportModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Core\Component\Form\Controls\DateTimeInput;
use App\Core\Component\Form\FilterContainer;
use App\Core\Component\Form\Multiplier;
use App\Core\Utils\DateTime;
use App\Modules\Presenters\SecurePresenter;
use App\Modules\TransportModule\Component\StoreCarOccupancy;
use App\Modules\TransportModule\Component\StoreDriverSelect;
use App\Modules\TransportModule\Component\StoreTransportCalendar;
use App\Modules\TransportModule\Component\StoreTransportDriverDayPlan;
use App\Modules\TransportModule\Orm\Transports\StoreTransport;
use App\Modules\TransportModule\Orm\Transports\StoreTransportItem;
use App\Modules\TransportModule\Orm\Transports\StoreTransportItemPart;
use App\Modules\TransportModule\Orm\Transports\StoreTransportTarget;
use App\Modules\TransportModule\Service\StoreTransportService;
use Nette\Forms\Container;
use Nextras\Dbal\Utils\DateTimeImmutable;
use Nextras\Orm\Entity\ToArrayConverter;

/** Presenter pro spravu rozvozu maloobchodu (prodejen) */
final class StoreTransportPresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'Rozvoz maloobchodu',
        'storeTransports' => 'Rozvozy maloobchodu',
        'add' => 'Přidat rozvoz',
        'edit' => 'Upravit rozvoz',
        'occupancy' => 'Vytíženost rozvozů'
    ];

    /** @inject */
    public StoreTransportService $transportService;

    /** Odstraneni delete submitu z formu, pokud se jedna o tvorbu prepravy */
    public function renderDefault(): void
    {
        $form = $this['storeTransportForm'];
        if ($form['action']->getValue() !== 'update') {
            unset($form['delete']);
        }
        $this->setCustomers($form);
        $transport = $this->orm->storeTransports->getById($form['id']->getValue());
        $carTitle = $transport->car->licensePlate ?? '';
        if ($transport->driver->phone ?? false) {
            $carTitle .= ' (tel. ' . $transport->driver->phone . ')';
        }
        $this->template->deliveredTargets = $transport ? $this->getDeliveredTargets($transport) : [];
        $this->template->selectedDriver = $transport->driver->name ?? '';
        $this->template->carTitle = $carTitle;
        $this->template->carWeightCapacity = $transport->car->weightCapacity ?? 0;
        $this->template->foreignParts = $transport ? $transport->loadForeignParts() : [];
    }

    /** Export nevalidnich rozvozu */
    public function actionExportInvalidTransports(): void
    {
        $store = $this->getUser()->isSuperAdmin() ? null : $this->selectedStore;
        $response = $this->transportService->exportInvalidTransports($store);
        $this->sendResponse($response);
    }

    /** Handle modalu s formem na vytvoreni noveho rozvozu */
    public function handleCreateTransport(int $car, int $time): void
    {
        if (!$this->getUser()->isAllowed(':Transport:StoreTransport:editTransport')) {
            $this->sendErrorJson(405, 'Not allowed');
        }

        $store = $this->orm->stores->getById($this->selectedStore);
        $car = $this->orm->storeCars->getById($car);
        $date = (new DateTime())->setTimestamp($time);

        if (((new \DateTime())->modify('-1 day')->setTime(0, 0)) > $date) {
            $this->flashMessage('Dopravu nelze vytvořit', self::MSG_ERROR);
            $this->redirect('default');
        }

        $transport = $this->transportService->createEmptyTransport($store, $car, $date, StoreTransportCalendar::TRANSPORT_SPAN);

        if (!$transport) {
            $this->flashMessage('Dopravu nelze vytvořit', self::MSG_ERROR);
            $this->redirect('default');
        }

        $defaults = [
            'id' => $transport->id,
            'action' => 'create',
            'date' => $date->format('d.m.Y'),
            'timeFrom' => hourToFloat($date->format('H:i')),
            'timeTill' => hourToFloat($date->modify('+1 hour')->format('H:i')),
            'targets' => [0 => ['items' => [0 => ['deliveryNote' => '']]]]
        ];

        $this['storeTransportForm']->setDefaults($defaults);
        $this->redrawControl('storeTransportForm');
    }

    /** Handle modalu s formem na upraveni noveho rozvozu */
    public function handleUpdateTransport(int $id): void
    {
        if (!$this->getUser()->isAllowed(':Transport:StoreTransport:editTransport')) {
            $this->sendErrorJson(405, 'Not allowed');
        }

        $transport = $this->orm->storeTransports->getById($id);
        if (!$transport || !$transport->isEditable()) {
            $this->flashMessage('Dopravu nelze upravit', self::MSG_ERROR);
            $this->redirect('default');
        }
        $transport->createLock();
        $this->orm->storeTransports->persistAndFlush($transport);
        $defaults = $transport->toArray(ToArrayConverter::RELATIONSHIP_AS_ID);
        $defaults['action'] = 'update';
        $defaults['targets'] = [];

        foreach ($transport->targets as $target) {
            $targetDefault = $target->toArray(ToArrayConverter::RELATIONSHIP_AS_ID);
            $targetDefault['items'] = [];

            foreach ($target->items as $item) {
                $itemDefaults = $item->toArray(ToArrayConverter::RELATIONSHIP_AS_ID);
                $itemDefaults['showPartSelects'] = (int) $item->hasParts;
                $itemDefaults['weightErrorMsg'] = $item->weightError ?? '';
                $unsetPartCount = $item->loadUnsetPartCount();
                $itemDefaults['parts'] = [];

                foreach ($item->parts as $part) {
                    $itemDefaults['parts'][] = $part->toArray(ToArrayConverter::RELATIONSHIP_AS_ID);
                }

                for ($i = 0; $i < $unsetPartCount; $i++) {
                    $itemDefaults['parts'][] = ['type' => ''];
                    $itemDefaults['showUnsetParts'] = $unsetPartCount;
                }

                $targetDefault['items'][] = $itemDefaults;
            }

            $defaults['targets'][] = $targetDefault;
        }

        $this['storeTransportForm']->setDefaults($defaults);
        $this->redrawControl('storeTransportForm');
    }

    /** Export vybraneho rozvozu do excelu  */
    public function actionExportTransport(int $car, int $time): void
    {
        $car = $this->orm->storeCars->getById($car);
        $date = (new DateTime())->setTimestamp($time);
        $response = $this->transportService->exportTransport($car, $date);
        $this->sendResponse($response);
    }

    /** Oznaceni polozky jako dorucenou */
    public function actionSetDelivered(int $id): void
    {
        $item = $this->orm->storeTransportItems->getById($id);
        if ($item && !$item->delivered) {
            $item->delivered = true;
            $item->setDeliveredBy = $this->getSysUser();
            $item->setDeliveredAt = new DateTimeImmutable();
            $this->orm->storeTransportItems->persistAndFlush($item);
        }
        $this->redirect('transportItems');
    }

    /** Zrusit oznaceni polozky jako dorucenou */
    public function actionUnsetDelivered(int $id): void
    {
        $item = $this->orm->storeTransportItems->getById($id);
        if ($item && $item->delivered) {
            $item->delivered = false;
            $item->setDeliveredBy = $this->getSysUser();
            $item->setDeliveredAt = new DateTimeImmutable();
            $this->orm->storeTransportItems->persistAndFlush($item);
        }
        $this->redirect('transportItems');
    }

    /** Nahled dopravy v gridu */
    public function actionPreview(int $id): void
    {
        $transport = $this->orm->storeTransports->getById($id);
        if (!$transport || $transport->type === StoreTransport::TYPE_UNAVAILABILITY) {
            $this->error('Položka nenalezena');
        }
        $this->template->transport = $transport;
        $this->sideDialogAjaxHandler();
    }

    /** Komponenta rozpisu rozvozu */
    protected function createComponentStoreTransportCalendar(): StoreTransportCalendar
    {
        return new StoreTransportCalendar($this->orm, $this->selectedStore);
    }

    /** Komponenta rozpisu rozvozu */
    protected function createComponentStoreTransportDriverDayPlan(): StoreTransportDriverDayPlan
    {

        return new StoreTransportDriverDayPlan($this->orm, $this->getSelectedDriver());
    }

    /** Komponenta pro vyber ridice */
    protected function createComponentStoreDriverSelect(): StoreDriverSelect
    {
        return new StoreDriverSelect(
            $this->orm->storeDrivers,
            $this->getSession('storeDriverSelect'),
            $this->selectedStore
        );
    }

    /** Tabulka s prehledem vytizenosti rozvozu */
    protected function createComponentStoreCarOccupancy(): StoreCarOccupancy
    {
        return new StoreCarOccupancy($this->orm);
    }

    /** Formular na tvorbu rozvozu */
    protected function createComponentStoreTransportForm(): BaseForm
    {
        $timeOption = StoreTransportCalendar::getTimeOption();
        $typeOption = [
            StoreTransport::TYPE_TRANSPORT => 'Přeprava',
            StoreTransport::TYPE_UNAVAILABILITY => 'Omezení'
        ];

        $form = new BaseForm();
        $validationScore = [
            $form->addHidden('id'),
            $form->addHidden('action'),
            $form->addSelect('timeFrom', '', $timeOption)->setRequired(),
            $form->addSelect('timeTill', '', $timeOption)->setRequired(),
            $form->addDate('date', '')
                ->setMinDate((new DateTime())->modify('-1 day')->setTime(0, 0), 'Datum nesmí být v minulosti')
                ->setRequired()
                ->setDefaultValue(date(DateTime::CZ_DATE))
                ->addRule(
                    static function (DateTimeInput $input): bool {
                        $value = DateTime::createFromFormat(DateTime::CZ_DATE, $input->getValue());
                        return !$value || $value->format('N') <= StoreTransportCalendar::MAX_TRANSPORT_DAY;
                    },
                    'Neplatné datum'
                )
        ];
        $typeSelect = $form->addRadioList('type', 'Druh rezervace', $typeOption)
            ->setRequired()
            ->setDefaultValue(StoreTransport::TYPE_TRANSPORT);
        $typeSelect->getSeparatorPrototype()->setName('span')->setAttribute('class', 'radio-separator');
        $typeSelect->addCondition(BaseForm::EQUAL, StoreTransport::TYPE_TRANSPORT)
            ->toggle('.unavailability-type-wrapper', false);
        $typeSelect->addCondition(BaseForm::EQUAL, StoreTransport::TYPE_UNAVAILABILITY)
            ->toggle('.transport-type-wrapper', false);

        $reasonSelect = $form->addRadioList('reason', 'Druh omezení', StoreTransport::REASONS_LABELS);
        $reasonSelect->addConditionOn($typeSelect, BaseForm::EQUAL, StoreTransport::TYPE_UNAVAILABILITY)
            ->addRule(BaseForm::FILLED);

        $reasonRemark = $form->addTextArea('reasonRemark', 'Poznámka', null, 4);
        $reasonRemark->addConditionOn($reasonSelect, BaseForm::EQUAL, StoreTransport::REASON_OTHER)
            ->addRule(BaseForm::FILLED);

        $validationScore[] = $typeSelect;
        $validationScore[] = $reasonSelect;
        $validationScore[] = $reasonRemark;

        $form->addSubmit('submit', 'ULOŽIT');
        $form->addSubmit('cancel')->setValidationScope([]);
        $form->addSubmit('updateLock')->setValidationScope([]);
        $form->addSubmit('delete', 'SMAZAT')->setValidationScope([]);
        $form->addSubmit('unavailabilitySubmit', 'ULOŽIT')->setValidationScope($validationScore);

        /** @var Multiplier $targets */
        $targets = $form->addMultiplier('targets', function (Container $container) {
            $container->addHidden('id');
            $container->addText('customer', 'Zákazník')->setDisabled();
            $container->addText('name', 'Příjemce', null, 255)->setRequired();
            $container->addText('phone', 'Telefon', null, 20)->setRequired();
            $container->addTextArea('address', 'Adresa doručení', null, 4);
            $container->addSelect('tariff', 'Tarif', ['' => '-'] + StoreTransportTarget::TARIFFS_LABELS);
            $container->addSelect('payment', 'Úhrada', ['' => '-'] + StoreTransportTarget::PAYMENTS_LABELS);
            $container->addTextArea('remark', 'Poznámka', null, 4);
            /** @var Multiplier $items */
            $items = $container->addMultiplier('items', function (Container $container) {
                $container->addHidden('id');
                $storeInput = $container->addSelect('store', 'Dodací list', $this->getTransportStores())
                    ->setRequired()
                    ->checkDefaultValue(false)
                    ->setDefaultValue($this->selectedStore);
                $noteInput = $container->addInteger('deliveryNote')
                    ->setRequired()
                    ->addRule(BaseForm::RANGE, null, [1000, 99999]);
                $yearInput = $container->addInteger('year', 'rok')
                    ->setRequired()
                    ->setDefaultValue(intval(date('Y')));
                $container->addInteger('weight')
                    ->setRequired()
                    ->addRule(BaseForm::RANGE, null, [0, 10000]);
                $container->addSubmit('update', 'Naplánovat jízdy')->setValidationScope([$container]);
                $container->addSubmit('loadData')->setValidationScope([$storeInput, $noteInput, $yearInput]);
                $container->addHidden('showUnsetParts')->setDefaultValue(0);
                $container->addHidden('showPartSelects')->setDefaultValue(0);
                $container->addHidden('hideUpdateSubmit')->setDefaultValue(1);
                $container->addHidden('weightErrorMsg');
                $container->addHidden('foreignPartsData');
                $container->addMultiplier('parts', function (Container $container) {
                    $container->addHidden('id');
                    $container->addSelect('type', null, StoreTransportItemPart::TYPES_LABELS)
                        ->setDefaultValue(StoreTransportItemPart::TYPE_SELF_PART);
                });
            }, 0);
            $items->addCreateButton('PŘIDAT DL')->addOnCreateCallback(function () {
                $this->redrawControl('storeTransportForm');
            });
            $items->addRemoveButton('X');
            $container->addSubmit('loadCustomerData')->setValidationScope([]);
        });
        $targets->addCreateButton('PŘIDAT DALŠÍHO ZÁKAZNÍKA')->addOnCreateCallback(function () {
            $this->redrawControl('storeTransportForm');
        });
        $targets->addRemoveButton('X');

        $form->onValidate[] = [$this, 'validateStoreTransportForm'];
        $form->onValidate[] = function (BaseForm $form) {
            if ($form->hasErrors()) {
                $this->redrawControl('storeTransportForm');
            }
        };
        $form->onSuccess[] = [$this, 'saveStoreTransportForm'];

        return $form;
    }

    /**
     * Validace formulare rozvozu
     *  - format datumu, posloupnost casu, obsazenost terminu, rozsah terminu k dobe prepravy
     */
    public function validateStoreTransportForm(BaseForm $form, array $values): void
    {
        if (!($form['submit']->isSubmittedBy() || $form['unavailabilitySubmit']->isSubmittedBy())) {
            return;
        }

        if (\DateTime::createFromFormat('d.m.Y', $values['date']) === false) {
            $form['date']->addError('Neplatný formát');
            return;
        }

        $timeFrom = $values['timeFrom'];
        $timeTill = $values['timeTill'];

        if ($timeFrom >= $timeTill) {
            $form['timeFrom']->addError('Čas od musí být menší než čas do');
            return;
        }

        $transport = $this->orm->storeTransports->getById($values['id']);
        $filter = [
            'deleted' => false,
            'car->id' => $transport->car->id,
            'date' => DateTime::createFromFormat(DateTime::CZ_DATE, $values['date'])->format(DateTime::DB_DATE),
            'timeFrom<' => $timeTill,
            'timeTill>' => $timeFrom,
            'id!=' => $transport->id
        ];

        $valid = true;

        foreach ($this->orm->storeTransports->findBy($filter) as $transport) {
            if ($transport->isRedundant()) {
                $this->orm->storeTransports->removeAndFlush($transport);
            } else {
                $valid = false;
            }
        }

        if (!$valid) {
            $form['timeFrom']->addError('Zadaný interval není volný');
            return;
        }

        $deliveryNotes = [];

        foreach ($values['targets'] ?? [] as $index => $target) {
            foreach ($target['items'] as $itemIndex => $item) {
                $itemKey = $item['store'] . '-' . $item['deliveryNote'] . '-' . $item['year'];
                if (in_array($itemKey, $deliveryNotes)) {
                    $note = $item['deliveryNote'];
                    $form['targets'][$index]['items'][$itemIndex]['deliveryNote']->addError("Doklad $note zadán duplicitně");
                    return;
                } else {
                    $deliveryNotes[] = $itemKey;
                }
            }
        }
    }

    /** Ulozeni dat z formulare rozvozu */
    public function saveStoreTransportForm(BaseForm $form, array $values): void
    {
        $transport = $this->orm->storeTransports->getById($form['id']->getValue());

        if ($form['cancel']->isSubmittedBy()) {
            // pri cancel se smaze nove vytvoreby rozvoz nebo se odemkne upravovany
            if ($transport && $transport->type === $transport::TYPE_TRANSPORT && !$transport->targets->count()) {
                $this->orm->storeTransports->removeAndFlush($transport);
                $transport = null;
            }
            if ($transport && $transport->isLocked) {
                $transport->unlock();
                $this->orm->storeTransports->persistAndFlush($transport);
            }
            $this['storeTransportCalendar']->redrawControl('storeTransportCalendar');
            return;
        }

        if ($form['updateLock']->isSubmittedBy()) {
            // Automaticke prodluzovani zamku pri editaci zaznamu
            if ($transport && $transport->isLocked) {
                $transport->updateLock();
                $this->orm->storeTransports->persistAndFlush($transport);
            }
            $this->sendSuccessJson();
        }

        if ($form['delete']->isSubmittedBy()) {
            // Odstraneni prepravy
            $transport->unlock();
            $this->orm->storeTransports->cancelEntity($transport);
            $this->redirect('default');
        }

        foreach ($form['targets']->getContainers() as $targetContainer) {
            if ($targetContainer['loadCustomerData']->isSubmittedBy()) {
                $note = null;

                foreach ($targetContainer['items']->getContainers() as $itemContainer) {
                    // nahrani dostupnych dat
                    $store = $itemContainer['store']->getValue();
                    $deliveryNote = $itemContainer['deliveryNote']->getValue();
                    $year = $itemContainer['year']->getValue();
                    $note = $this->orm->deliveryNotes->getByTransportItem((int) $store, (int) $deliveryNote, (int) $year);
                    break;
                }

                if ($note && $note->depot) {
                    $targetContainer['name']->setValue($note->depot->title);
                    $targetContainer['address']->setValue($note->depot->address);
                }

                $this->redrawControl('storeTransportForm');
                return;
            }


            foreach ($targetContainer['items']->getContainers() as $itemContainer) {
                $update = false;

                if ($itemContainer['loadData']->isSubmittedBy()) {
                    // nahrani dostupnych dat
                    $store = $itemContainer['store']->getValue();
                    $deliveryNote = $itemContainer['deliveryNote']->getValue();
                    $year = $itemContainer['year']->getValue();
                    $note = $this->orm->deliveryNotes->getByTransportItem($store, $deliveryNote, $year);
                    $foreignItem = $this->orm->storeTransportItems->getBy([
                        'target->id!=' => $targetContainer['id']->getValue(),
                        'target->transport->deleted' => false,
                        'store->id' => $itemContainer['store']->getValue(),
                        'deliveryNote' => $itemContainer['deliveryNote']->getValue(),
                        'year' => $itemContainer['year']->getValue()
                    ]);

                    if ($note) {
                        $itemContainer['weight']->setValue($note->weight);
                    } else {
                        $targetContainer['customer']->setOption('class', 'text-red');
                    }

                    if ($foreignItem) {
                        $targetContainer['name']->setValue($foreignItem->target->name);
                        $targetContainer['phone']->setValue($foreignItem->target->phone);
                        $targetContainer['address']->setValue($foreignItem->target->address);
                        $targetContainer['payment']->setValue($foreignItem->target->payment);
                        $targetContainer['tariff']->setValue($foreignItem->target->tariff);
                        $targetContainer['remark']->setValue($foreignItem->target->remark);
                    }

                    $update = true;
                }


                if ($update || $itemContainer['update']->isSubmittedBy()) {
                    // vykresleni jizd
                    $itemContainer['hideUpdateSubmit']->setValue(1);
                    $weight = $itemContainer['weight']->getValue();

                    if ($weight > $transport->car->weightCapacity) {
                        $partCount = (int) ceil($weight / $transport->car->weightCapacity);
                        $foreignParts = [];
                        $foreignItems = $this->orm->storeTransportItems->findBy([
                            'target->id!=' => $targetContainer['id']->getValue(),
                            'target->transport->deleted' => false,
                            'store->id' => $itemContainer['store']->getValue(),
                            'deliveryNote' => $itemContainer['deliveryNote']->getValue(),
                            'year' => $itemContainer['year']->getValue()
                        ])->orderBy('target->transport->date');

                        foreach ($foreignItems as $foreignItem) {
                            foreach ($foreignItem->parts as $foreignPart) {
                                $foreignTransport = $foreignPart->item->target->transport;
                                $foreignParts[] = $foreignTransport->date->format('d.m.Y')
                                    . ' ' . floatToHour($foreignTransport->timeFrom)
                                    . ' - ' . floatToHour($foreignTransport->timeTill);
                            }
                        }

                        $unsetPartCount = $partCount - count($foreignParts) - 1;
                        $itemContainer['id']->setValue('');
                        $itemContainer['foreignPartsData']->setValue(json_encode($foreignParts));
                        $itemContainer['showPartSelects']->setValue($unsetPartCount > 0 ? 1 : 0);
                        $itemContainer['showUnsetParts']->setValue($unsetPartCount);

                        if ($unsetPartCount > 0) {
                            /** @var Multiplier $partMultiplier */
                            $partMultiplier = $itemContainer['parts'];

                            foreach ($partMultiplier->getContainers() as $container) {
                                $partMultiplier->removeComponent($container);
                            }

                            $partMultiplier->addCopy(0)['type']->setValue(StoreTransportItemPart::TYPE_SELF_PART);

                            for ($i = 0; $i < $unsetPartCount; $i++) {
                                $partMultiplier->addCopy($i + 1)['type']->setValue('');
                            }
                        }
                    }

                    $this->redrawControl('storeTransportForm');
                    return;
                }
            }
        }

        $values['store'] = $this->selectedStore;
        $values['updatedAt'] = date(DateTime::DB_DATETIME);
        $values['updatedBy'] = $this->getSysUser()->id;
        unset($values['id']);

        if ($form['unavailabilitySubmit']->isSubmittedBy()) {
            // Ulozeni omezeni pro prepravu
            $transport->removeTargets();
            unset($values['targets']);
            $this->orm->storeTransports->updateEntity($transport->id, null, $values);
        }

        if ($form['submit']->isSubmittedBy()) {
            // ulozeni prepravy
            $values['reason'] = '';
            $values['reasonRemark'] = '';
            $deliveredTargets = $this->getDeliveredTargets($transport);
            // nasetovani jiz dorucenych preprav
            foreach ($values['targets'] as $targetIndex => $targetData) {
                $targetId = $targetData['id'];

                foreach ($targetData['items'] as $itemIndex => $itemData) {
                    $itemId = $itemData['id'];

                    foreach ($itemData['parts'] as $partIndex => $partData) {
                        if (!$partData['type']) {
                            unset($values['targets'][$targetIndex]['items'][$itemIndex]['parts'][$partIndex]);
                            unset($form['targets'][$targetIndex]['items'][$itemIndex]['parts'][$partIndex]);
                        }
                    }

                    if (isset($deliveredTargets[$targetId][$itemId])) {
                        /** @var StoreTransportItem $item */
                        $item = $deliveredTargets[$targetId][$itemId];
                        unset($deliveredTargets[$targetId][$itemId]);
                        $values['targets'][$targetIndex]['items'][$itemIndex] = [
                            'id' => $item->id,
                            'store' => $item->store->id,
                            'deliveryNote' => $item->deliveryNote,
                            'weight' => $item->weight
                        ];
                    }
                }

                foreach ($deliveredTargets[$targetId] ?? [] as $item) {
                    $values['targets'][$targetIndex]['items'][] = [
                        'id' => $item->id,
                        'store' => $item->store->id,
                        'deliveryNote' => $item->deliveryNote,
                        'weight' => $item->weight
                    ];
                }
            }

            $this->orm->storeTransports->updateEntity($transport->id, $form, $values);
        }

        $transport->unlock();
        $this->orm->storeTransports->persistAndFlush($transport);
        $this->redirect('default');
    }

    /** Grid s dennim rozvozem ridice */
    protected function createComponentStoreTransportItems(): BaseDatagrid
    {
        $grid = new BaseDatagrid($this->orm->storeTransportItems);
        $grid->addCellsTemplate(__DIR__ . '/../templates/StoreTransport/transportItems.cells.latte');
        $grid->settings->setDataSourceFilter([
            'target->transport->store->id' => $this->selectedStore,
            'target->transport->deleted' => false
        ])->setFulltextColumns(['deliveryNoteLabel', 'targetName'])
            ->setForceOrder(['transportTimeFrom' => BaseDatagrid::ORDER_ASC]);

        $grid->addColumn('transportDate', 'Datum')
            ->dateFormat(DATE)
            ->enableSort(BaseDatagrid::ORDER_DESC);
        $grid->addColumn('transportTimeFrom', 'Od');
        $grid->addColumn('transportTimeTill', 'Do');
        $grid->addColumn('customer', 'Zákazník');
        $grid->addColumn('targetName', 'Příjemce');
        $grid->addColumn('deliveryNoteLabel', 'Dodací list');
        $grid->addColumn('weight', 'Váha');
        $grid->addColumn('car', 'Dodávka');
        $grid->addColumn('setDeliveredAt', 'Doručeno');

        $grid->addRowAction('setDelivered', 'Doručit', 'thumbs-o-up')
            ->setCondition("\$delivered == 0");
        $grid->addRowAction('unsetDelivered', 'Zrušit Doručení', 'times-circle')
            ->setCondition("\$delivered == 1");

        $grid->addLegend('Doručeno', 'legend_green', "\$delivered == 1");
        $grid->addLegend('Nedoručeno', 'legend_red', "\$delivered == 0");

        $grid->setFilterFormFactory(function (): FilterContainer {
            $cars = $this->orm->storeCars->findBy([
                'deleted' => false,
                'stores->id' => $this->selectedStore
            ])->fetchPairs('id', 'licensePlate');
            $form = new FilterContainer();
            $form->addSelect('car', 'Dodávka', ['' => 'Vše'] + $cars);
            $form->addDate('transportDate', 'Datum');
            $form->addSelect('delivered', 'Doručeno', ['' => 'Vše', 0 => 'ne', 1 => 'ano']);
            return $form;
        });

        return $grid;
    }

    /** Grid s rozvozy maloobchodni pobocky */
    protected function createComponentStoreTransports(): BaseDatagrid
    {
        $grid = new BaseDatagrid($this->orm->storeTransports);
        $grid->addCellsTemplate(__DIR__ . '/../templates/StoreTransport/transports.cells.latte');
        $grid->settings->setDataSourceFilter(['store->id' => $this->selectedStore])
            ->setFulltextColumns(['targets', 'deliveryNotes'])
            ->setForceOrder(['timeFrom' => BaseDatagrid::ORDER_ASC]);

        $grid->addColumn('date', 'Datum')
            ->dateFormat(DATE)
            ->enableSort(BaseDatagrid::ORDER_DESC);
        $grid->addColumn('timeFrom', 'Od');
        $grid->addColumn('timeTill', 'Do');
        $grid->addColumn('customers', 'Zákazníci');
        $grid->addColumn('targets', 'Příjemci');
        $grid->addColumn('deliveryNotes', 'Dodací listy');
        $grid->addColumn('car', 'Dodávka');
        $grid->addColumn('driver', 'Řidič');

        $grid->addOtherColumn('id', 'ID')->enableSort();
        $grid->addOtherColumn('created', 'Vytvořeno');
        $grid->addOtherColumn('updated', 'Upraveno');
        $grid->addOtherColumn('cancelled', 'Smazáno');

        $grid->addLegend('Nevalidní', 'legend_orange', "\$isValid == 0");

        $grid->addTopAction('exportInvalidTransports', 'Export nevalidních rozvozů');

        $grid->addRowAction('preview', 'Náhled')
            ->setCondition("\$type == " . StoreTransport::TYPE_TRANSPORT)
            ->setSideDialog();

        $grid->setFilterFormFactory(function (): FilterContainer {
            $form = new FilterContainer();
            $form->addDate('date', 'Datum');

            $form->addSelect('type', 'Typ', [
                '' => 'vše',
                StoreTransport::TYPE_TRANSPORT => 'Přeprava',
                StoreTransport::TYPE_UNAVAILABILITY => 'Omezení'
            ])->setDefaultValue(StoreTransport::TYPE_TRANSPORT);

            $form->addSelect('validity', 'Validita', [
                '' => 'vše',
                1 => 'pouze nevalidní'
            ]);

            if ($this->getUser()->isAdmin()) {
                $form->addSelect('deleted', 'Stav', [
                    '' => 'Vše',
                    '0' => 'Pouze nesmazané rozvozy',
                    '1' => 'Pouze smazané rozvozy'
                ])->setDefaultValue('0');
            }

            return $form;
        });

        return $grid;
    }

    /** Pobocky, pro ktere jezdi rozvoz */
    private function getTransportStores(): array
    {
        $stores = [];
        $transportCars = $this->orm->storeCars->findBy(['stores->id' => $this->selectedStore, 'deleted' => false]);

        if ($this->getUser()->isAdmin() or $this->getUser()->isAllowed(':Transport:StoreTransport:foreignDeliveryNotes')) {
            $stores[9] = "Ostrava - Michálkovice";
            $stores[10] = "Hlučín";
        }

        foreach ($transportCars as $transportCar) {
            foreach ($transportCar->stores as $store) {
                if (!isset($stores[$store->id])) {
                    $stores[$store->id] = $store->name;
                }
            }
        }

        ksort($stores);
        return $stores;
    }

    /**
     * Dorucene polozky rozvozu
     * [ $targetId => $items[] ]
     */
    private function getDeliveredTargets(StoreTransport $transport): array
    {
        $deliveredTargets = [];

        foreach ($transport->targets->toCollection()->findBy(['items->delivered' => true]) as $target) {
            $deliveredTargets[$target->id] = $target->items->toCollection()
                ->findBy(['delivered' => true])
                ->fetchPairs('id');
        }

        return $deliveredTargets;
    }

    /** ID ridice, pro ktereho se ma zobrazit denni rozvoz */
    private function getSelectedDriver(): int
    {
        if (!$this->user->isAllowed(':Transport:StoreTransport:selectDriver')) {
            return $this->orm->storeDrivers->getBy(['deleted' => false, 'user->id' => $this->getUser()->getId()])->id
                ?? 0;
        }
        $selectedDriver = $this->getSession('storeDriverSelect')->selectedDriver;
        return $selectedDriver
            ?? $this->orm->storeDrivers->getBy(['deleted' => false, 'car->stores->id' => $this->selectedStore])->id
            ?? 0;
    }

    /** Nastaveni Zakaznika do formulare z DL */
    private function setCustomers(BaseForm $form): void
    {
        foreach ($form['targets']->getContainers() as $targetContainer) {
            $note = null;

            foreach ($targetContainer['items']->getContainers() as $itemContainer) {
                $deliveryNote = $itemContainer['deliveryNote']->getValue();
                $store = $itemContainer['store']->getValue();
                $year = $itemContainer['year']->getValue();

                if ($deliveryNote && $store && $year) {
                    $note = $this->orm->deliveryNotes->getByTransportItem((int) $store, (int) $deliveryNote, (int) $year);
                }

                if ($note) {
                    break;
                }
            }

            $targetContainer['customer']->setValue(
                $note
                    ? ($note->depot ? $note->depot->companyName : $note->description)
                    : '-- DL nenalezen --'
            );
        }
    }
}
