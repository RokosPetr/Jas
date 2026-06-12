<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Core\Component\Form\FilterContainer;
use App\Core\Utils\DateTime;
use App\Modules\DeliveryModule\Component\SalesOverview;
use App\Modules\DeliveryModule\Component\SalesPlanForm;
use App\Modules\DeliveryModule\Service\SalesDataExporter;
use App\Modules\Presenters\SecurePresenter;
use Nextras\Orm\Collection\ICollection;
use Nextras\Orm\Entity\ToArrayConverter;

/** Presenter pro spravu sluzeb pobocek */
final class SalesDataPresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'Data prodejů',
        'edit' => 'Upravit data prodejů',
        'plan' => 'Plány prodejů',
        'export' => 'Export prodejů'
    ];

    public function actionEdit(int $id): void
    {
        $salesData = $this->orm->salesData->getById($id);
        if (!$salesData) {
            $this->error('Položka nenalezena');
        }
        $this['salesDataForm']->setDefaults($salesData->toArray(ToArrayConverter::RELATIONSHIP_AS_ID));
        $this->template->heading = ucfirst(DateTime::CZ_MONTHS[$salesData->month] ?? '???') . " $salesData->year - "
            . (SalesOverview::SALE_GROUPS[$salesData->store] ?? '???');
    }

    /** Grid s daty prodeju */
    protected function createComponentSalesDataGrid(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->salesData);
        $grid->addCellsTemplate(__DIR__ . '/../templates/SalesData/grid.cells.latte');
        $grid->settings->setForceOrder(['month' => ICollection::ASC, 'year' => ICollection::DESC]);

        $grid->addColumn('store', 'Pobočka')->enableSort(BaseDatagrid::ORDER_ASC);
        $grid->addColumn('month','Měsic')->enableSort()->alignRight();
        $grid->addColumn('year', 'Rok')->enableSort()->alignRight();
        $grid->addColumn('realSale', 'Prodej')->enableSort()->alignRight()->numberFormat();
        $grid->addColumn('lastSale', 'Prodej loni')->enableSort()->alignRight()->numberFormat();
        $grid->addColumn('salePlan', 'Plán prodeje')->enableSort()->alignRight()->numberFormat();
        $grid->addColumn('lastSaleDifference', 'Rozdíl prodeje')->enableSort()->alignRight()->numberFormat();
        $grid->addColumn('salePlanDifference', 'Rozdíl plánu prodeje')->enableSort()->alignRight()->numberFormat();
        $grid->addColumn('realProfit', 'Zisk')->enableSort()->alignRight()->numberFormat();
        $grid->addColumn('lastProfit', 'Zisk loni')->enableSort()->alignRight()->numberFormat();
        $grid->addColumn('profitPlan', 'Plán zisku')->enableSort()->alignRight()->numberFormat();
        $grid->addColumn('lastProfitDifference', 'Rozdíl zisku')->enableSort()->alignRight()->numberFormat();
        $grid->addColumn('profitPlanDifference', 'Rozdíl plánu zisku')->enableSort()->alignRight()->numberFormat();

        $grid->addRowAction('edit', 'Upravit');

        $grid->setFilterFormFactory(function (): FilterContainer {
            $stores = SalesOverview::SALE_GROUPS;
            $years = range(intval(date('Y')), 2014);
            ksort($stores);
            $form = new FilterContainer();
            $form->addSelect('store', null, ['' => 'Vše'] + $stores);
            $form->addSelect('month', null, ['' => 'Vše'] + DateTime::CZ_MONTHS);
            $form->addSelect('year', null, ['' => 'Vše'] + array_combine($years, $years));
            return $form;
        });

        return $grid;
    }

    /** Form na upravu dat prodeju */
    protected function createComponentSalesDataForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addInteger('realSale', 'Prodej')
            ->setRequired()
            ->addRule(BaseForm::MIN, null, 0);
        $form->addInteger('lastSale', 'Prodej loni')
            ->setRequired()
            ->addRule(BaseForm::MIN, null, 0);
        $form->addInteger('salePlan', 'Plán prodeje')
            ->setRequired()
            ->addRule(BaseForm::MIN, null, 0);
        $form->addInteger('realProfit', 'Zisk')
            ->setRequired();
        $form->addInteger('lastProfit', 'Zisk loni')
            ->setRequired();
        $form->addInteger('profitPlan', 'Plán zisku')
            ->setRequired();

        $form->onSuccess[] = function (array $values): void {
            $salesData = $this->orm->salesData->getById($this->getParameter('id'));
            $salesData->realSale = $values['realSale'];
            $salesData->lastSale = $values['lastSale'];
            $salesData->salePlan = $values['salePlan'];
            $salesData->lastSaleDifference = $salesData->realSale - $salesData->lastSale;
            $salesData->salePlanDifference = $salesData->realSale - $salesData->salePlan;

            $salesData->realProfit = $values['realProfit'];
            $salesData->lastProfit = $values['lastProfit'];
            $salesData->profitPlan = $values['profitPlan'];
            $salesData->lastProfitDifference = $salesData->realProfit - $salesData->lastProfit;
            $salesData->profitPlanDifference = $salesData->realProfit - $salesData->profitPlan;
            $this->orm->salesData->persistAndFlush($salesData);
            $this->redirect('default');
        };

        $form->addSubmit('submit', 'Uložit');
        return $form;
    }

    /** Formular na export dat prodeju */
    protected function createComponentSalesDataExportForm(): BaseForm
    {
        $years = [];
        for ($year = date('Y'); $year >= 2014 ; $year--) {
            $years[$year] = $year;
        }
        $form = new BaseForm();
        $form->addSelect('year', 'Rok', $years)->setRequired();
        $form->addSubmit('export', 'Exportovat');

        $form->onSuccess[] = function (array $values): void {
            $response = (new SalesDataExporter($this->orm))->exportToExcel((int) $values['year']);
            $this->sendResponse($response);
        };

        return $form;
    }

    /** Form pro nastaveni planu prodeje */
    protected function createComponentSalesPlanForm(): SalesPlanForm
    {
        return new SalesPlanForm($this->orm);
    }
}
