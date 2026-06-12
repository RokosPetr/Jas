<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Modules\DeliveryModule\Component\Entity\SalesFilterEntity;
use App\Modules\Presenters\SecurePresenter;
use Nette\Http\IResponse;
use Nette\Neon\Neon;
use Nextras\Orm\Entity\ToArrayConverter;

/** Presenter pro spravu uzivatelskych roli v systemu */
final class TestPresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'Seznam rolí',
        'preview' => 'Náhled',
        'add' => 'Přidat roli',
        'edit' => 'Upravit roli'
    ];

    /** Datagrid s uzivatelskymi rolemi */
    protected function createComponentTests(): BaseDatagrid
    {
        $storeOverviewFilter = new SalesFilterEntity($this->orm, 1, [], [2023], 2, [], null);
        $data = $this->orm->companies->getMapper()->loadStoreOverviewGridData($storeOverviewFilter, null);
        $grid = $this->datagridFactory->create($this->orm->roles);
        $grid->addColumn('name', 'Název')->enableSort();
        $grid->addColumn('description', 'Popis');

        $grid->addTopAction('add', 'Přidat');
        $grid->addTopAction('default', 'Zdroje')->setLink('System', 'Resource');
        $grid->addRowAction('preview', 'Náhled role')->setSideDialog();
        $grid->addRowAction('edit', 'Upravit')->setCondition("\$id != 1");
        $grid->addRowAction('delete', 'Smazat')->setCondition("\$id > 2");

        return $grid;
    }

}
