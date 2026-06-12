<?php
declare(strict_types=1);

namespace App\Modules\MtzModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Modules\MtzModule\Component\MtzBasket;
use App\Modules\Presenters\SecurePresenter;

/** Presenter pro objednavky polozek MTZ */
final class MtzShopPresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'MTZ shop',
        'basket' => 'MTZ košík'
    ];

    /** MTZ shop */
    public function renderDefault(): void
    {
        $this->template->mtzGroups = [0 => 'Vše'] + $this->orm->mtzGroups->findAll()->orderBy('order')
                ->fetchPairs('id', 'title');
        $this->template->mtzItemsTree = $this->orm->mtzItems->loadMtzTree();
        $this->template->selectedMtzGroup = $this->getSelectedMtzGroup();
    }

    /** Nahled MTZ polozky */
    public function actionPictureView(int $id): void
    {
        $mtzItem = $this->orm->mtzItems->getById($id);
        if (!$mtzItem || $mtzItem->deleted) {
            $this->error('Položka nenalezena');
        }
        $this->template->mtzItem = $mtzItem;
        $this->sideDialogAjaxHandler();
    }

    /** Nahled objednavky */
    public function actionMyOrderPreview(int $id): void
    {
        $this->template->mtzItemsTree = $this->orm->mtzItems->loadMtzTree();
        $mtzOrder = $this->orm->mtzOrders->getById($id);
        if (!$mtzOrder) {
            $this->error('Položka nenalezena');
        }
        $this->template->mtzOrder = $mtzOrder;
        $this->sideDialogAjaxHandler();
    }

    /** Nastaveni MTZ skupiny */
    public function handleSetMtzGroup(int $id): void
    {
        $this->getSession('MtzShop')->mtzGroup = $id;
        $this['mtzShop']->redrawControl('rows');
    }

    /** Update polozky pro vlozeni do kosiku */
    public function handleUpdateBasketForm(): void
    {
        $mtzItem = $this->orm->mtzItems->getById($this->getParameter('dataId'));
        if ($mtzItem) {
            $orderUnit = $mtzItem->orderUnit ? $mtzItem::UNITS_LABELS[$mtzItem->orderUnit] : '';
            $quantityLabel = $orderUnit ? "Množství ($orderUnit)" : 'Množství';
            $this['mtzAddToBasketForm']['name']->setValue($mtzItem->name);
            $this['mtzAddToBasketForm']['mtzItem']->setValue($mtzItem->id);
            $this['mtzAddToBasketForm']['quantity']->setValue(1)->setCaption($quantityLabel);
        } else {
            $this['mtzAddToBasketForm']['submit']->setDisabled();
        }
        $this->redrawControl('basketForm');
    }

    /** Mtz shop grid */
    protected function createComponentMtzShop(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->mtzItems);
        $grid->addCellsTemplate(__DIR__ . '/../templates/MtzShop/grid.cells.latte');
        $filter = ['deleted' => 0];
        $mtzGroup = $this->getSelectedMtzGroup();

        if ($mtzGroup) {
            $filter['group->id'] = $mtzGroup;
        }

        $grid->settings->setFulltextColumns(['regNumber', 'name'])
            ->setDataSourceFilter($filter)
            ->hideSettings()
            ->hideFulltextTooltip();

        $grid->addColumn('regNumber', 'Registrační číslo')->enableSort(BaseDatagrid::ORDER_ASC);
        $grid->addColumn('name', 'Název')->enableSort();
        //$grid->addColumn('description', 'Popis');
        //$grid->addColumn('remark', 'Poznámka');
        $grid->addColumn('packageTitle', 'Balení');

        $grid->addRowAction('order', 'Vložit do košíku', 'cart-plus')
            ->setModalData(['targetId' => 'shopModal']);
        $grid->addRowAction('pictureView', 'Náhled', 'search')
            ->setCondition("\$hasPicture == 1")->setSideDialog();

        return $grid;
    }

    /** Moje objednavky grid */
    protected function createComponentMyMtzOrders(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->mtzOrders);
        $grid->settings->setDataSourceFilter(['createdBy->id' => $this->getUser()->getId()]);

        $grid->addColumn('id', 'ID')->enableSort($grid::ORDER_DESC);
        $grid->addColumn('createdAt', 'Datum')->dateFormat();
        $grid->addColumn('remark', 'Poznámka');

        $grid->addRowAction('myOrderPreview', 'Náhled', 'search')->setSideDialog();

        return $grid;
    }

    /** Form na vlozeni MTZ polozky do kosiku */
    protected function createComponentMtzAddToBasketForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addText('name', 'Položka')->setDefaultValue('???')->setDisabled();
        $form->addInteger('quantity', 'Množství')
            ->setRequired()
            ->setHtmlAttribute('autocomplete', 'off')
            ->addRule(BaseForm::MIN, null, 1);
        $form->addHidden('mtzItem')->setRequired();
        $form->addSubmit('submit', 'Vložit do košíku');

        $form->onSuccess[] = function (array $values) {
            $mtzItem = $this->orm->mtzItems->getById($values['mtzItem']);
            if (!$mtzItem || $mtzItem->deleted) {
                $this->flashMessage('Položku se nepodařilo vložit do košíku', self::MSG_ERROR);
                $this->redirect('default');
            }
            $this['mtzBasket']->handleAddToBasket($mtzItem->id, $values['quantity']);
            $this->flashMessage('Položka byla vložena do košíku');
            $this->redirect('default');
        };

        return $form;
    }

    /** Komponenta kosiku */
    protected function createComponentMtzBasket(): MtzBasket
    {
        return new MtzBasket($this->orm);
    }

    private function getSelectedMtzGroup(): int
    {
        return $this->getSession('MtzShop')->mtzGroup ?? 0;
    }
}
