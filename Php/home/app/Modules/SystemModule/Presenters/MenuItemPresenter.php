<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Modules\Presenters\SecurePresenter;
use Nextras\Orm\Entity\ToArrayConverter;

/** Presenter pro spravu polozek v hlavnim menu */
final class MenuItemPresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'Seznam menu položek',
        'add' => 'Přidat menu položku',
        'edit' => 'Upravit menu položku'
    ];

    /** Uprava polozky hlavniho menu */
    public function actionEdit(int $id): void
    {
        $menuItem = $this->orm->menuItems->getById($id);
        if (!$menuItem) {
            $this->error('Položka nenalezena');
        }
        $this['menuItemForm']->setDefaults($menuItem->toArray(ToArrayConverter::RELATIONSHIP_AS_ID));
    }

    /** Odstraneni polozky z hlavniho menu */
    public function actionDelete(int $id): void
    {
        $menuItem = $this->orm->menuItems->getById($id);
        if (!$menuItem) {
            $this->error('Položka nenalezena');
        }
        $this->orm->menuItems->removeAndFlush($menuItem);
        $this->flashMessage('Menu položka byla smazána');
        $this->redirect('default');
    }

    /** Datagrid s polozkami hlavniho menu */
    protected function createComponentMenuItems(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->menuItems);
        $grid->addCellsTemplate(__DIR__ . '/../templates/MenuItem/grid.cells.latte');

        $grid->addColumn('name', 'Název')->enableSort();
        $grid->addColumn('link', 'Link');
        $grid->addColumn('icon', 'Ikona');
        $grid->addColumn('order', 'Pořadí')->enableSort(BaseDatagrid::ORDER_ASC);

        $grid->addTopAction('add', '+ Přidat');
        $grid->addRowAction('edit', 'Upravit');
        $grid->addRowAction('delete', 'Smazat');

        $grid->addLegend('Nezobrazované', 'legend_red', "\$active == 0");

        return $grid;
    }

    /** Formular pro upravu polozky hlavniho menu */
    protected function createComponentMenuItemForm(): BaseForm
    {
        $form = new BaseForm();

        $form->addText('name', 'Název', null, 250)->setRequired();
        $form->addText('module', 'Modul', null, 250)->setRequired();
        $form->addText('presenter', 'Presenter', null, 250)->setRequired();
        $form->addText('action', 'Akce', null, 250)
            ->setRequired()
            ->setDefaultValue('default');
        $form->addText('icon', 'Ikona', null, 50);
        $form->addInteger('order', 'Pořadí')
            ->setRequired()
            ->setDefaultValue(1)
            ->addRule(BaseForm::RANGE, null, [1, 100]);
        $form->addCheckbox('active', 'Zobrazovat')->setDefaultValue(true);
        $form->addSubmit($this->action, $this->action === 'add' ? 'Přidat' : 'Upravit');

        $form->onValidate[] = function (BaseForm $form): void {
            $values = $form->getValues();
            $menuItem = $this->orm->menuItems->getBy([
                'module' => $values->module,
                'presenter' => $values->presenter,
                'action' => $values->action
            ]);

            if (!$menuItem || ($this->action === 'edit' && $menuItem->id == $this->getParameter('id'))) {
                return;
            }

            $form->addError("Položka v menu pro odkaz $menuItem->link již existuje!");
        };

        $form->onSuccess[] = function (BaseForm $form): void {
            $this->action === 'add'
                ? $this->orm->menuItems->insertEntity($form)
                : $this->orm->menuItems->updateEntity($this->getParameter('id'), $form);
            $this->flashMessage('Menu položka byla ' . ($this->action === 'add' ? 'přidána': 'upravena'));
            $this->redirect('default');
        };

        return $form;
    }
}
