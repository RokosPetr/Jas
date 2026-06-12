<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Modules\Presenters\SecurePresenter;
use Nextras\Orm\Entity\ToArrayConverter;

/** Presenter pro spravu pobocek */
final class StorePresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'Seznam poboček',
        'add' => 'Přidat pobočku',
        'edit' => 'Upravit pobočku'
    ];

    /** Nahled pobocky */
    public function actionPreview(int $id): void
    {
        $store = $this->orm->stores->getById($id);
        if (!$store) {
            $this->error('Položka nenalezena');
        }
        $this->template->store = $store;
        $this->sideDialogAjaxHandler();
    }

    /** Uprava pobocky */
    public function actionEdit(int $id): void
    {
        $store = $this->orm->stores->getById($id);
        if (!$store) {
            $this->error('Položka nenalezena');
        }
        $this['storeForm']->setDefaults($store->toArray(ToArrayConverter::RELATIONSHIP_AS_ID));
    }

    /** Datagrid s pobockami */
    protected function createComponentStores(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->stores);
        $grid->addCellsTemplate(__DIR__ . '/../templates/Store/grid.cells.latte');
        $grid->addColumn('id', 'Číslo')->enableSort();
        $grid->addColumn('name', 'Název')->enableSort();
        $grid->addColumn('color', 'Barva v grafu');
        $grid->addColumn('street', 'Ulice');
        $grid->addColumn('zipCode', 'PSČ');
        $grid->addColumn('phone', 'Telefon');
        $grid->addColumn('email', 'Email');
        $grid->addColumn('manager', 'Vedoucí');

        $grid->addTopAction('add', 'Přidat');
        $grid->addRowAction('preview', 'Náhled')->setSideDialog();
        $grid->addRowAction('edit', 'Upravit');
        $grid->addRowAction('default', 'Sklad', 'cubes')
            ->setLink('Stock', 'StoreStock');

        return $grid;
    }

    /** Formular pro upravu pobocky */
    protected function createComponentStoreForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addText('name', 'Název', null, 250)->setRequired();
        $form->addText('street', 'Ulice', null, 250)->setRequired();
        $form->addText('zipCode', 'PSČ', null, 10)->setRequired();
        $form->addText('phone', 'Telefon', null, 20)->setRequired();
        $form->addEmail('email', 'Email')->setRequired();
        $users = ['' => '-- Vyberte --'] + $this->orm->users->findBy(['deleted' => false])->fetchPairs('id', 'name');
        $form->addSelect('manager', 'Vedoucí', $users);
        $form->addColorPicker('color', 'Barva v grafu')->setRequired();
        $form->addSubmit($this->action, $this->action === 'add' ? 'Přidat' : 'Upravit');

        $form->onSuccess[] = function (BaseForm $form): void {
            $this->action === 'add'
                ? $this->orm->stores->insertEntity($form)
                : $this->orm->stores->updateEntity($this->getParameter('id'), $form);
            $this->flashMessage('Pobočka byla ' . ($this->action === 'add' ? 'přidána': 'upravena'));
            $this->redirect('default');
        };

        return $form;
    }
}
