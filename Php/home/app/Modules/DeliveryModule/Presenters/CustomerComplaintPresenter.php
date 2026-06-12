<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Core\Component\Form\FilterContainer;
use App\Modules\DeliveryModule\Orm\CustomerComplaints\CustomerComplaint;
use App\Modules\Presenters\SecurePresenter;

/** Presenter pro spravu reklamaci zakazniku */
final class CustomerComplaintPresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'Reklamace zákazníků',
        'preview' => 'Reklamační formulář',
        'edit' => 'Opravit reklamaci',
        'add' => 'Nová reklamace',
        'respond' => 'Vyjádření k reklamaci'
    ];

    public function actionPreview(int $id): void
    {
        $complaint = $this->orm->customerComplaints->getById($id);
        if (!$complaint) {
            $this->error('Položka nenalezena');
        }
        $this->template->complaint = $complaint;
    }

    /** Oprava reklamace */
    public function actionEdit(int $id): void
    {
        $complaint = $this->orm->customerComplaints->getById($id);
        if (!$complaint || $complaint->state === CustomerComplaint::STATE_RESPONDED) {
            $this->error('Položka nenalezena');
        }

        if ($complaint->createdBy->id !== $this->getUser()->getId() && !$this->getUser()->isAdmin()) {
            $this->flashMessage('Nemáte práva na úpravu dokumentu', self::MSG_ERROR);
            $this->redirect('default');
        }

        $defaults = $complaint->description;
        $defaults['name'] = $complaint->name;
        $defaults['company'] = $complaint->company;
        $defaults['item'] = $complaint->item->id;
        $form = $this['customerComplaintForm'];
        $form['item']->setItems([$complaint->item->id => $complaint->item->name]);
        $form->setDefaults($defaults);
    }

    /** Vyjadreni k reklamaci */
    public function actionRespond(int $id): void
    {
        $complaint = $this->orm->customerComplaints->getById($id);
        if (!$complaint || $complaint->state === CustomerComplaint::STATE_RESPONDED) {
            $this->error('Položka nenalezena');
        }
        $this->template->complaint = $complaint;
    }

    /** Datagrid reklamaci zakazniku */
    protected function createComponentCustomerComplaints(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->customerComplaints);

        if (!$this->getUser()->isSuperAdmin()) {
            $grid->settings->setDataSourceFilter(['store' => $this->selectedStore]);
        }

        $grid->settings->setFulltextColumns(['name', 'company', 'item']);
        $grid->addCellsTemplate(__DIR__ . '/../templates/CustomerComplaint/grid.cells.latte');

        if ($this->getUser()->isSuperAdmin()) {
            $grid->addColumn('store', 'Pobočka')->enableSort();
        }

        $grid->addColumn('id', 'Číslo')->enableSort(BaseDatagrid::ORDER_DESC);
        $grid->addColumn('createdAt', 'Datum')->dateFormat(DATE);
        $grid->addColumn('createdBy', 'Zpracoval');
        $grid->addColumn('name', 'Zákazník')->enableSort();
        $grid->addColumn('company', 'Společnost')->enableSort();
        $grid->addColumn('item', 'Zboží');
        $grid->addColumn('daysLeft', 'Zbývá dní');

        $grid->addTopAction('add', 'Nová reklamace');
        $grid->addRowAction('preview', 'Náhled');

        $editActionCondition = "\$state != " . CustomerComplaint::STATE_RESPONDED;

        if (!$this->getUser()->isAdmin()) {
            $editActionCondition .= " && \$createdBy->id == " . $this->getUser()->getId();
        }

        $grid->addRowAction('edit', 'Opravit')->setCondition($editActionCondition);
        $grid->addRowAction('respond', 'Napsat vyjádření', 'comment-o')
            ->setCondition("\$state != " . CustomerComplaint::STATE_RESPONDED);

        $grid->addLegend('Odesláno upozornění', 'legend_orange', "\$state == " . CustomerComplaint::STATE_NOTIFIED);
        $grid->addLegend('Vyřízeno', 'legend_red', "\$state == " . CustomerComplaint::STATE_RESPONDED);

        $grid->setFilterFormFactory(function (): FilterContainer {
            $form = new FilterContainer();
            $form->addContainer('createdAt');
            $form->addDateFrom('createdAt', 'Od');
            $form->addDateTo('createdAt', 'Do');

            if ($this->getUser()->isSuperAdmin()) {
                $form->addMultiSelect('store', 'Pobočka', $this->orm->stores->findAll()->fetchPairs('id', 'name'));
            }

            $form->addSelect('state', 'Stav', [
                12 => 'Čeká na vyřízení',
                3 => 'Vyřízeno',
                '' => 'Vše'
            ])->setDefaultValue(12);
            return $form;
        });

        return $grid;
    }

    /** Formular pro upravu reklamace */
    protected function createComponentCustomerComplaintForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addGroup('Zákazník');
        $form->addText('name', 'Jméno', null, 255)->setRequired();
        $form->addText('company', 'Firma', null, 255);
        $form->addText('street', 'Ulice', null, 255)->setRequired();
        $form->addText('zipCode', 'PSČ', null, 15)->setRequired();
        $form->addText('city', 'Obec', null, 255)->setRequired();
        $form->addText('phone', 'Telefon', null, 15);
        $form->addEmail('email', 'Email');

        $form->addGroup('Produkt');
        $form->addText('deliveryItem', 'Dodací list / Účtenka', null, 30)->setRequired();
        $form->addText('bill', 'Faktura', null, 30);
        $form->addSelect('item', 'Produkt')
            ->getControlPrototype()->addClass('select2-ignore');
        $form->addInteger('amount', 'Množství')
            ->setRequired()
            ->addRule(BaseForm::MIN, null, 1);
        $form->addInteger('price', 'Cena')->setRequired()
            ->setRequired()
            ->addRule(BaseForm::MIN, null, 1);
        $form->addDate('buyDate', 'Zakoupeno')->setRequired();
        $form->addTextArea('description', 'Popis závady', null, 4)->setRequired();
        $form->addTextArea('request', 'Požadavek zákazníka', null, 4)->setRequired();
        $form->addSubmit($this->action, $this->action === 'add' ? 'Vytvořit' : 'Opravit');

        $form->onValidate[] = function (BaseForm $form) {
            $stockItemId = $this->getHttpRequest()->getPost('item');

            if (!$stockItemId) {
                $form['item']->addError('Toto pole je povinné.');
                return;
            }

            $stockItem = $this->orm->stockItems->getById($stockItemId);

            if (!$stockItem) {
                $form['item']->addError('Produkt nenalezen.');
            }
        };

        $form->onSuccess[] = [$this, 'customerComplainFormSuccess'];
        return $form;
    }

    /** Success callback formulare s reklamaci */
    public function customerComplainFormSuccess(array $formValues): void
    {
        $formValues['item'] = $this->getHttpRequest()->getPost('item');
        $baseValues = ['item', 'name', 'company'];
        $values = [];

        foreach ($formValues as $key => $value) {
            if (in_array($key, $baseValues)) {
                $values[$key] = $value;
            } else {
                $values['description'][$key] = $value;
            }
        }

        if ($this->action === 'add') {
            $values['store'] = $this->selectedStore;
            $this->orm->customerComplaints->insertEntity(null, $values);
        } else {
            $this->orm->customerComplaints->updateEntity($this->getParameter('id'), null, $values);
        }

        $this->flashMessage('Reklamace byla ' . ($this->action === 'add' ? 'vytvořena' : 'opravena'));
        $this->redirect('default');
    }

    /** Formular pro zadani vyjadreni k reklamaci zakaznika */
    public function createComponentComplaintRespondForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addTextArea('response', 'Vyjádření k reklamaci', null, 5)->setRequired();
        $form->addSubmit('save', 'Odeslat');
        $form->onSuccess[] = function (array $values): void {
            $complaint = $this->orm->customerComplaints->getById($this->getParameter('id'));
            $complaint->response = $values['response'];
            $complaint->state = CustomerComplaint::STATE_RESPONDED;
            $complaint->getRepository()->persistAndFlush($complaint);
            $this->flashMessage('Reklamace vyřízena');
            $this->redirect('default');
        };
        return $form;
    }
}
