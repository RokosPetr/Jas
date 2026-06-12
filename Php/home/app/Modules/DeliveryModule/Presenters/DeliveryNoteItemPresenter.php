<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\FilterContainer;
use App\Modules\Presenters\SecurePresenter;

/** Presenter pro spravu pohybu pobocek */
final class DeliveryNoteItemPresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'Položky pohybů',
    ];

    protected function createComponentDeliveryNoteItems(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->deliveryNoteItems);
        $grid->addCellsTemplate(__DIR__ . '/../templates/DeliveryNoteItem/grid.cells.latte');
        $grid->settings->setFulltextColumns(['name']);

        $grid->addColumn('name', 'Položka dokladu');
        $grid->addColumn('amount', 'Množství')->enableSort();
        $grid->addColumn('unit', 'Jednotka');
        $grid->addColumn('note', 'Doklad');
        $grid->addColumn('store', 'Pobočka');
        $grid->addColumn('movementNumber', 'Pohyb');
        $grid->addColumn('date', 'Datum')->dateFormat(DATE)->enableSort(BaseDatagrid::ORDER_DESC);

        $grid->setFilterFormFactory(function (): FilterContainer {
            $types = [
                '' => 'Vše',
                1 => 'Prodej',
                2 => 'Příjem',
                3 => 'Storno',
                4 => 'Převodky z poboček',
                5 => 'Převodky na pobočky'
            ];

            $form = new FilterContainer();
            $form->addContainer('date');
            $form->addDateFrom('date', 'Od');
            $form->addDateTo('date', 'Do');
            $form->addSelect('movementType', 'Typ pohybu', $types);
            return $form;
        });

        return $grid;
    }
}
