<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Core\Component\Form\FilterContainer;
use App\Modules\DeliveryModule\Service\DiscountExporter;
use App\Modules\Presenters\SecurePresenter;
use App\Modules\SystemModule\Orm\Stores\Store;
use Nextras\Orm\Collection\ICollection;
use Nextras\Orm\Entity\ToArrayConverter;

/** Presenter pro spravu pobocek partneru */
final class DepotPresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'Pobočky partnerů',
        'address' => 'Adresa provozovny',
        'discounts' => 'Slevy partnera',
        'contacts' => 'Kontakty partnera',
        'addContact' => 'Přidat kontakt',
        'editContact' => 'Upravit kontakt'
    ];

    protected function startup(): void
    {
        parent::startup();
        if (in_array($this->getAction(), ['addContact', 'editContact'])) {
            $this->setView('addEditContact');
        }
    }

    /** Pobocky partneru */
    public function renderDefault(): void
    {
        $selectedDepots = $this->getSelectedDepots();
        $this->template->selectedDepotCount = count($selectedDepots);
        if ($this->isAjax()) {
            $this->redrawControl('selectedDepots');
        }
    }

    /** Zruseni vyberu pobocek partnera */
    public function handleCancelDepotSelection(): void
    {
        unset($this->getSession('dealerDepots_datagrid')->selectedRows);
        $this->redirect('default');
    }

    /** Nahled pobocky partnera */
    public function actionPreview(int $id): void
    {
        $depot = $this->orm->companyDepots->getById($id);
        if (!$depot) {
            $this->error('Položka nenalezena');
        }
        $this->template->depot = $depot;
        $this->sideDialogAjaxHandler();
    }

    /** Kontaktni adresa pobocky partnera */
    public function actionAddress(int $id): void
    {
        $depot = $this->orm->companyDepots->getById($id);
        if (!$depot) {
            $this->error('Položka nenalezena');
        }
        if ($depot->contactAddress) {
            $this['depotAddressForm']->setDefaults($depot->contactAddress->toArray(ToArrayConverter::RELATIONSHIP_AS_ID));
        }
        $this->template->depot = $depot;
    }

    /** Slevy pobocky partnera */
    public function actionDiscounts(int $id): void
    {
        $depot = $this->orm->companyDepots->getById($id);
        if (!$depot) {
            $this->error('Položka nenalezena');
        }
        $this->template->depot = $depot;
        $this->template->producers = $this->orm->producers->findAll()->orderBy('number')->fetchPairs('id', 'name');
    }

    /** Slevy pobocky partnera */
    public function renderDiscounts(int $id): void
    {
        $this->template->selectedProducers = $this->getProducerFilter();
    }

    /** Export slev pobocky partnera do Excelu */
    public function actionExportDiscounts(int $id): void
    {
        $depot = $this->orm->companyDepots->getById($id);
        if (!$depot) {
            $this->error('Položka nenalezena');
        }
        $exporter = new DiscountExporter($this->orm);
        $this->sendResponse($exporter->discountsToExcel($depot, $this->getProducerFilter()));
    }

    /** Kontaktni osoby pobocky partnera */
    public function actionContacts(int $id): void
    {
        $depot = $this->orm->companyDepots->getById($id);
        if (!$depot) {
            $this->error('Položka nenalezena');
        }
        $this->template->depot = $depot;
    }

    /** Pridani kontaktni osoby k pobocce partnera */
    public function actionAddContact(int $id): void
    {
        $depot = $this->orm->companyDepots->getById($id);
        if (!$depot) {
            $this->error('Položka nenalezena');
        }
        $this->template->depot = $depot;
    }

    /** Uprava kontaktni osoby pobocky partnera */
    public function actionEditContact(int $id): void
    {
        $contact = $this->orm->contacts->getById($id);
        if (!$contact || $contact->deleted) {
            $this->error('Položka nenalezena');
        }
        $this->template->depot = $contact->depot;
        $this['depotContactForm']->setDefaults($contact->toArray(ToArrayConverter::RELATIONSHIP_AS_ID));
    }

    /** Smazani kontaktni osoby pobocky partnera */
    public function actionDeleteContact(int $id): void
    {
        $contact = $this->orm->contacts->getById($id);
        if (!$contact || $contact->deleted) {
            $this->error('Položka nenalezena');
        }
        $this->orm->contacts->changeOrder($contact, $this->orm->contacts->loadLastOrder($contact->depot));
        $this->orm->contacts->flush();
        $contact->order = 0;
        $this->orm->contacts->cancelEntity($contact);
        $this->flashMessage('Kontakt byl odstraněn');
        $this->redirect('contacts', ['id' => $contact->depot->id]);
    }

    /** Obnova kontaktni osoby pobocky partnera */
    public function actionRestoreContact(int $id): void
    {
        $contact = $this->orm->contacts->getById($id);
        if (!$contact || !$contact->deleted) {
            $this->error('Položka nenalezena');
        }
        $contact->order = $this->orm->contacts->loadLastOrder($contact->depot) + 1;
        $this->orm->contacts->restoreEntity($contact);
        $this->flashMessage('Kontakt byl obnoven');
        $this->redirect('contacts', ['id' => $contact->depot->id]);
    }

    /** Export kontaktu vybranych pobocek do Excelu */
    public function actionExportContacts(): void
    {
        $exporter = new DiscountExporter($this->orm);
        $this->sendResponse($exporter->contactsToExcel($this->getSelectedDepots()));
    }

    /** Nastaveni filtru vyrobce pro stranku se slevama */
    public function handleSetProducers(): void
    {
        $this->setProducerFilter((array) $this->getParameter('producers'));
        $this->redrawControl('discount-grids');
    }

    /** Zmena poradi kontaktu pobocky partnera */
    public function handleSetContactOrder(int $contact, int $order): void
    {
        $contact = $this->orm->contacts->getById($contact);
        if ($contact && !$contact->deleted) {
            $maxOrder = $this->orm->contacts->loadLastOrder($contact->depot);
            $newOrder = $order ? ($order > $maxOrder ?  1 : $order) : $maxOrder;
            $this->orm->contacts->changeOrder($contact, $newOrder);
            $this->orm->contacts->flush();
        }
        $this->redrawControl('contact-grids');
    }

    /** Grid pobocek partneru velkoobchodu */
    protected function createComponentDealerDepots(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->companyDepots);
        $grid->addCellsTemplate(__DIR__ . '/../templates/Depot/grid.cells.latte');
        $grid->settings->setDataSourceFilter([
            'store->id' => Store::OSTRAVA_MAIN_STORAGE,
            'dealers->id!=' => null
        ])->setFulltextColumns(['companyIcoString', 'companyName', 'title'])
            ->showCheckboxes()
            ->setForceOrder(['voj' => ICollection::ASC]);

        $grid->addColumn('companyIcoString', 'IČO')->enableSort();
        $grid->addColumn('voj', 'Voj');
        $grid->addColumn('companyName', 'Společnost')->enableSort(BaseDatagrid::ORDER_ASC);
        $grid->addColumn('title', 'Pobočka');
        $grid->addColumn('dealers', 'OZ');

        $grid->addTopAction('exportContacts', 'Export kontaktů');
        $grid->addRowAction('preview', 'Náhled')->setSideDialog();
        $grid->addRowAction('contacts', 'Kontakty', 'address-book-o');
        $grid->addRowAction('address', 'Adresa provozovny', 'map-signs');
        $grid->addRowAction('discounts', 'Slevy', 'money')->setCondition("\$hasDiscounts == 1");

        $grid->setFilterFormFactory(function (): FilterContainer {
            $form = new FilterContainer();
            $dealers = $this->orm->users->findDealers()->fetchPairs('id', 'name');
            $form->addSelect('dealers', 'OZ', ['' => 'Vše'] + $dealers);
            return $form;
        });

        return $grid;
    }

    /** Grid kontaktnich osob pobocky partnera */
    protected function createComponentDepotContacts(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->contacts);
        $grid->addCellsTemplate(__DIR__ . '/../templates/Depot/contact.grid.cells.latte');
        $filter = ['depot->id' => $this->getParameter('id')];

        if (!$this->getUser()->isAllowed(':Delivery:Depot:restoreContact')) {
            $filter['deleted'] = 0;
        }

        $grid->settings->setDataSourceFilter($filter)
            ->setFulltextColumns(['name']);

        $grid->addColumn('order', 'Pořadí')->enableSort(BaseDatagrid::ORDER_ASC);
        $grid->addColumn('name', 'Jméno')->enableSort();
        $grid->addColumn('position', 'Pozice');
        $grid->addColumn('phone', 'Telefon');
        $grid->addColumn('email', 'Email');
        $grid->addColumn('url', 'WWW stránka');
        $grid->addColumn('remark', 'Poznámka');

        $grid->addTopAction('addContact', 'Přidat kontakt', ['id' => $this->getParameter('id')]);
        $grid->addRowAction('editContact', 'Upravit', 'pencil')->setCondition("\$deleted == 0");
        $grid->addRowAction('deleteContact', 'Smazat', 'trash')
            ->setCondition("\$deleted == 0")
            ->setDialog('Potvrzení', 'Opravdu checte odstranit kontakt?');
        $grid->addRowAction('restoreContact', 'Obnovit', 'undo')->setCondition("\$deleted == 1");

        if ($this->getUser()->isAllowed(':Delivery:Depot:restoreContact')) {
            $grid->setFilterFormFactory(function (): FilterContainer {
                $form = new FilterContainer();
                $form->addSelect('deleted', 'Stav', [
                    '' => 'Vše',
                    '0' => 'Pouze nesmazané',
                    '1' => 'Pouze smazané'
                ])->setDefaultValue('0');
                return $form;
            });
            $grid->addLegend('Smazano', 'legend_red', "\$deleted == 1");
        }

        return $grid;
    }

    /** Form na kontaktni osobu */
    protected function createComponentDepotContactForm(): BaseForm
    {
        $isCreate = $this->getAction() === 'addContact';
        $depot = $isCreate
            ? $this->orm->companyDepots->getById($this->getParameter('id'))
            : $this->orm->contacts->getById($this->getParameter('id'))->depot;
        $lastOrder = $this->orm->contacts->loadLastOrder($depot);

        if ($isCreate) {
            $lastOrder++;
        }

        $form = new BaseForm();
        $form->addInteger('order', 'Pořadí')
            ->setRequired()
            ->addRule(BaseForm::RANGE, null, [1, $lastOrder])
            ->setDefaultValue($lastOrder);
        $form->addText('firstName', 'Jméno', null, 250)->setRequired();
        $form->addText('lastName', 'Příjmení', null, 250)->setRequired();
        $form->addText('position', 'Pozice', null, 250)->setRequired();
        $form->addText('phone', 'Telefon', null, 250)->setRequired();
        $form->addEmail('email', 'Email')
            ->addRule(BaseForm::MAX_LENGTH, null, 250);
        $form->addText('url', 'WWW stránka', null, 250)
            ->addCondition(BaseForm::FILLED)
            ->addRule(BaseForm::URL);
        $form->addTextArea('remark', 'Poznámka');
        $form->addSubmit('submit', 'Uložit');

        $form->onSuccess[] = function (array $values) use ($isCreate, $lastOrder): void {
            $order = $values['order'];
            if ($isCreate) {
                $values['depot'] = $this->getParameter('id');
                if ($order === $lastOrder) {
                    $contact = $this->orm->contacts->insertEntity(null, $values);
                } else {
                    $values['order'] = $lastOrder;
                    $contact = $this->orm->contacts->insertEntity(null, $values);
                    $this->orm->contacts->changeOrder($contact, $order);
                    $this->orm->contacts->flush();
                }
            } else {
                $contact = $this->orm->contacts->getById($this->getParameter('id'));
                if ($order !== $contact->order) {
                    unset($values['order']);
                    $this->orm->contacts->changeOrder($contact, $order);
                    $this->orm->contacts->flush();
                }
                $this->orm->contacts->updateEntity($contact->id, null, $values);
            }
            $this->flashMessage('Kontakt byl uložen');
            $this->redirect('contacts', ['id' => $contact->depot->id]);
        };

        return $form;
    }

    /** Grid se slevama na skupiny zbozi vybrane pobocky partnera */
    protected function createComponentDepotGroupDiscounts(): BaseDatagrid
    {
        $producerFilter = $this->getProducerFilter();
        $filter = ['discountGroup->depots->id' => $this->getParameter('id')];

        if ($producerFilter) {
            $filter['stockGroup->producer->id'] = $producerFilter;
        }

        $grid = $this->datagridFactory->create($this->orm->discountStockGroups);
        $grid->addCellsTemplate(__DIR__ . '/../templates/Depot/groupDiscount.grid.cells.latte');
        $grid->settings->setDataSourceFilter($filter)
            ->hideSettings()
            ->setForceOrder(['producerNumber' => ICollection::ASC, 'stockGroupNumber' => ICollection::ASC]);

        $grid->addColumn('producer', 'Výrobce');
        $grid->addColumn('stockGroup', 'Druh zboží');
        $grid->addColumn('value', 'Sleva')->enableSort();

        return $grid;
    }

    /** Grid se slevama na produkty vybrane pobocky partnera */
    protected function createComponentDepotItemDiscounts(): BaseDatagrid
    {
        $producerFilter = $this->getProducerFilter();
        $filter = ['discountGroup->depots->id' => $this->getParameter('id')];

        if ($producerFilter) {
            $filter['stockItem->group->producer->id'] = $producerFilter;
        }

        $grid = $this->datagridFactory->create($this->orm->discountStockItems);
        $grid->addCellsTemplate(__DIR__ . '/../templates/Depot/itemDiscount.grid.cells.latte');
        $grid->settings->setDataSourceFilter($filter)
            ->hideSettings()
            ->setForceOrder(['producerNumber' => ICollection::ASC, 'stockItemNumber' => ICollection::ASC]);
        $grid->addColumn('producer', 'Výrobce');
        $grid->addColumn('stockItem', 'Produkt');
        $grid->addColumn('value', 'Sleva')->enableSort();

        return $grid;
    }

    /** Form na upravu kontaktni adresy pobocky partnera */
    protected function createComponentDepotAddressForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addText('street', 'Ulice', null, 250)->setRequired();
        $form->addText('number', 'č.p.', null, 250)->setRequired();
        $form->addText('city', 'Město', null, 250)->setRequired();
        $form->addText('zip', 'PSČ', null, 250)->setRequired();
        $form->addText('district', 'Okres', null, 250)->setRequired();
        $form->addText('openHours', 'Otevírací doba', null, 250);
        $form->addEmail('billingEmail', 'Email pro fakturaci')
            ->addRule(BaseForm::MAX_LENGTH, null, 250);
        $form->addEmail('complainEmail', 'Email pro dobropisy')
            ->addRule(BaseForm::MAX_LENGTH, null, 250);
        $form->addSubmit('submit', 'Uložit');

        $form->onSuccess[] = function (array $values): void {
            $depot = $this->orm->companyDepots->getById($this->getParameter('id'));
            if ($depot->contactAddress) {
                $this->orm->depotAddresses->updateEntity($depot->contactAddress->id, null, $values);
            } else {
                $values['depot'] = $depot->id;
                $this->orm->depotAddresses->insertEntity(null, $values);
            }
            $this->flashMessage('Adresa byla uložena');
            $this->redirect('default');
        };

        return $form;
    }

    private function setProducerFilter(array $producers): void
    {
        $this->getSession($this->getParameter('id') . '-depotDiscountFilter')->producers = $producers;
    }

    private function getProducerFilter(): array
    {
        return $this->getSession($this->getParameter('id') . '-depotDiscountFilter')->producers ?? [];
    }

    private function getSelectedDepots(): array
    {
        return $this->getSession('dealerDepots_datagrid')->selectedRows ?? [];
    }
}
