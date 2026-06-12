<?php
declare(strict_types=1);

namespace App\Modules\MtzModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Modules\Presenters\SecurePresenter;

/** Presenter pro spravu polozek MTZ */
final class MtzOrderPresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'MTZ objednávky'
    ];

    /** Nahled objednavky */
    public function actionPreview(int $id): void
    {
        $mtzOrder = $this->orm->mtzOrders->getById($id);
        if (!$mtzOrder) {
            $this->error('Položka nenalezena');
        }
        $this->template->mtzOrder = $mtzOrder;
        $this->sideDialogAjaxHandler();
    }

    /** Grid MTZ objednavek z shopu */
    protected function createComponentMtzOrders(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->mtzOrders);
        $grid->addCellsTemplate(__DIR__ . '/../templates/MtzOrder/grid.cells.latte');
        $grid->settings->setFulltextColumns(['createdBy']);

        $grid->addColumn('id', 'ID')->enableSort($grid::ORDER_DESC);
        $grid->addColumn('createdAt', 'Datum')->dateFormat();
        $grid->addColumn('createdBy', 'Vytvořil');
        $grid->addColumn('remark', 'Poznámka');

        $grid->addRowAction('preview', 'Náhled')->setSideDialog();

        return $grid;
    }
}
