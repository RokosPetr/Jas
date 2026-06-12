<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Modules\Presenters\SecurePresenter;
use App\Modules\StockModule\Orm\CustomGroups\CustomGroup;
use Nextras\Orm\Entity\ToArrayConverter;

/** Presenter pro spravu definovatelnych skupin zbozi */
final class CustomGroupPresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'Vlastní skupiny zboží',
        'preview' => 'Náhled skupiny zboží',
        'add' => 'Přidat vlastní skupinu',
        'edit' => 'Upravit vlastní skupinu',
        'delete' => 'Smazat vlastní skupinu'
    ];

    /** Nahled vlastni skupiny zbozi */
    public function actionPreview(int $id): void
    {
        $customGroup = $this->orm->customGroups->getById($id);

        if (!$customGroup) {
            $this->error('Položka nenalezena');
        }

        $stockGroups = [];

        foreach ($customGroup->stockGroups->toCollection()->orderBy('producer->number') as $stockGroup) {
            if (!isset($stockGroups[$stockGroup->producer->name])) {
                $stockGroups[$stockGroup->producer->name] = [];
            }

            $stockGroups[$stockGroup->producer->name][$stockGroup->number] = $stockGroup->title;
        }


        $this->template->stockGroups = $stockGroups;
        $this->sideDialogAjaxHandler();
    }

    /** Pridani vlastni skupiny zbozi */
    public function actionAdd(): void
    {
        $this->template->producers = $this->orm->producers->findAll()->orderBy('number')->fetchAll();
    }

    /** Editace vlastni skupiny zbozi */
    public function actionEdit(int $id): void
    {
        $customGroup = $this->orm->customGroups->getById($id);
        if (!$customGroup) {
            $this->error('Položka nenalezena');
        }
        $defaults = $customGroup->toArray(ToArrayConverter::RELATIONSHIP_AS_ID);
        $defaults['stockGroups'] = array_map(fn(): bool => true, array_flip($defaults['stockGroups']));
        $this['customStockGroupForm']->setDefaults($defaults);
        $this->template->producers = $this->orm->producers->findAll()->orderBy('number')->fetchAll();
    }

    /** Odstraneni vlastni skupiny zbozi */
    public function actionDelete(int $id): void
    {
        $customGroup = $this->orm->customGroups->getById($id);
        if (!$customGroup) {
            $this->error('Položka nenalezena');
        }
        $this->orm->customGroups->removeAndFlush($customGroup);
        $this->flashMessage('Vlastni skupina zboží byla smazána');
        $this->redirect('default');
    }

    /** Grid s vlastnimi skupinami zbozi */
    protected function createComponentCustomStockGroups(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->customGroups);
        $grid->addColumn('name', 'Název')->enableSort();
        $grid->addTopAction('add', 'Vytvořit');
        $grid->addRowAction('preview', 'Náhled')->setSideDialog();
        $grid->addRowAction('edit', 'Upravit');
        $grid->addRowAction('delete', 'Smazat');
        return $grid;
    }

    /** Formular na pridani/editaci vlastni skupiny zbozi */
    protected function createComponentCustomStockGroupForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addText('name', 'Název', null, 255)->setRequired();
        $form->addSelect('viewType', 'Typ zobrazení', [
            CustomGroup::VIEW_TYPE_TAKINGS_SUM => 'Celková suma nákupů',
            CustomGroup::VIEW_TYPE_STORE_TAKINGS => 'Sumy nákupů po pobočkách'
        ])->setRequired();

        $groupContainer = $form->addContainer('stockGroups');

        foreach ($this->orm->producers->findAll() as $producer) {
            foreach ($producer->stockGroups as $group) {
                $groupContainer->addCheckbox((string) $group->id, "$group->number - $group->name");
            }
        }

        $form->addSubmit($this->action, $this->action === 'add' ? 'Vytvořit' : 'Upravit');

        $form->onValidate[] = function (BaseForm $form): void {
            $nameTextInput = $form['name'];
            $customGroup = $this->orm->customGroups->getBy(['name' => $nameTextInput->getValue()]);
            if (!$customGroup || ($this->action === 'edit' && $customGroup->id == $this->getParameter('id'))) {
                return;
            }
            $nameTextInput->addError('Skupina s tímto názvem již existuje!');
        };

        $form->onSuccess[] = function (array $values): void {
            $values['stockGroups'] = array_keys(array_filter($values['stockGroups']));
            $this->action === 'add'
                ? $this->orm->customGroups->insertEntity(null, $values)
                : $this->orm->customGroups->updateEntity($this->getParameter('id'), null, $values);
            $this->flashMessage('Skupina byla ' . ($this->action === 'add' ? 'přidána': 'upravena'));
            $this->redirect('default');
        };

        return $form;
    }
}
