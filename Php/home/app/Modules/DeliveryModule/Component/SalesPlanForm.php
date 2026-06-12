<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Component;

use App\Core\Component\Form\BaseForm;
use App\Modules\DeliveryModule\Orm\SalesData\SalesData;
use App\Modules\SystemModule\Orm\Stores\Store;
use App\Service\OrmModel;
use Nette\Application\UI\Control;
use Nette\Application\UI\Presenter;
use Nette\Http\SessionSection;

class SalesPlanForm extends Control
{
    private OrmModel $orm;
    private SessionSection $session;

    public int $year;
    public int $store;

    public function __construct(OrmModel $orm)
    {
        $this->orm = $orm;
        $this->monitor(Presenter::class, function (): void {
            $this->session = $this->getPresenter()->getSession('salesPlanForm');
        });
    }

    public function loadState(array $params): void
    {
        parent::loadState($params);
        $this->year = $this->session->year ?? (int)date('Y');
        $this->store = $this->session->store ?? Store::OSTRAVA;
    }

    public function handleSetYear(): void
    {
        $this->year = $this->session->year = (int) $this->getPresenter()->getParameter('year');
        $this->redrawControl('salesPlanForm');
    }

    public function handleSetStore(): void
    {
        $this->store = $this->session->store = (int) $this->getPresenter()->getParameter('store');
        $this->redrawControl('salesPlanForm');
    }

    public function render(): void
    {
        $this->template->stores = SalesOverview::SALE_GROUPS;
        $this->template->lastSalesData = $this->orm->salesData->findBy(['store' => $this->store, 'year' => $this->year - 1])
            ->fetchPairs('month');
        $this->template->futureSalesData = $this->orm->salesData->findBy(['store' => $this->store, 'year' => $this->year + 1])
            ->fetchPairs('month');
        $this->template->setFile(__DIR__ . '/templates/salesPlanForm.latte');
        $this->template->render();
    }

    protected function createComponentEditSalePlanForm(): BaseForm
    {
        $form = new BaseForm();
        $saleContainer = $form->addContainer('sale');
        $profitContainer = $form->addContainer('profit');

        for ($month = 1; $month <= 12; $month++) {
            $salesData = $this->orm->salesData->getBy(['store' => $this->store, 'year' => $this->year, 'month' => $month]);
            $saleContainer->addInteger("$month")
                ->setRequired()
                ->setDefaultValue($salesData->salePlan ?? 0);
            $profitContainer->addInteger("$month")
                ->setRequired()
                ->setDefaultValue($salesData->profitPlan ?? 0);
        }

        $form->addSubmit('submit', 'Uložit');

        $form->onSuccess[] = function (array $values): void {
            for ($month = 1; $month <= 12; $month++) {
                $salesData = $this->orm->salesData->getBy(['store' => $this->store, 'year' => $this->year, 'month' => $month]);

                if ($salesData) {
                    $salesData->salePlan = $values['sale']["$month"];
                    $salesData->profitPlan = $values['profit']["$month"];
                    $salesData->salePlanDifference = $salesData->realSale - $salesData->salePlan;
                    $salesData->profitPlanDifference = $salesData->realProfit - $salesData->profitPlan;
                } else {
                    $salesData = new SalesData();
                    $salesData->store = $this->store;
                    $salesData->year = $this->year;
                    $salesData->month = $month;
                    $salesData->realSale = 0;
                    $salesData->lastSale = 0;
                    $salesData->lastSaleDifference = 0;
                    $salesData->salePlanDifference = 0;
                    $salesData->realProfit = 0;
                    $salesData->lastProfit = 0;
                    $salesData->lastProfitDifference = 0;
                    $salesData->profitPlanDifference = 0;
                    $salesData->salePlan = $values['sale']["$month"];
                    $salesData->profitPlan = $values['profit']["$month"];
                }

                $this->orm->salesData->persist($salesData);
            }

            $this->orm->flush();
            $this->presenter->flashMessage('Plány byly uloženy');
            $this->redirect('this');
        };

        return $form;
    }
}