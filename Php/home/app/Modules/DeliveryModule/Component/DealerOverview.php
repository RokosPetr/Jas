<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Component;

class DealerOverview extends BaseOverview
{
    protected function createComponentOverviewFilter(): DealerOverviewFilter
    {
        return new DealerOverviewFilter($this->orm);
    }

    public function loadCellValue(int $producer, int $year, int $month): int
    {
        return $this->orm->salesData->loadFilterSalesSum(
            $this->filter,
            $this->filter->getStockGroupFilter($producer),
            $year,
            $month
        );
    }

    public function loadCellTotalValue(int $stockGroup, int $year, int $month): int
    {
        return $this->orm->salesData->loadFilterSalesSum(
            $this->filter,
            $this->getStockGroups($stockGroup),
            $year,
            $month
        );
    }
}
