<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\SalesData;

use App\Core\Orm\BaseRepository;
use App\Modules\DeliveryModule\Component\Entity\SalesFilterEntity;

/**
 * @method array loadToDateInMonth(int $storeId, \DateTimeInterface $date, bool $withOpenSums = false)
 * @method array loadK2real(int $storeId, \DateTimeInterface $date)
 * @method int loadFilterSalesSum(SalesFilterEntity $filter, array $groups, int $year, int $month)
 * @method int loadStoreFilterSalesSum(SalesFilterEntity $filter, array $groups, int $year, int $month)
 * @method int loadStoreFilterSalesSumK2(SalesFilterEntity $filter, int $year, int $month, int $producer = null, int $stockGroup)
 */
class SalesDataRepository extends BaseRepository
{
    public const MAIN_STORAGE_1_GROUPS = [1, 31, 32];
    public const MAIN_STORAGE_2_GROUPS = [2];
    public const ESHOP_GROUPS = [66];
    public const OSTRAVA_GROUPS = [33, 55, 88];
    public const STORE_OZ_1_GROUP = [33, 88];
    public const STORE_OZ_2_GROUP = [55];
    public const STORE_BUILD_GROUP = [22];
    public const NO_COMPANY_MOVEMENT_NUMBERS = [404, 504, 506];
    public const COMPANY_MOVEMENT_NUMBERS = [401, 402, 501, 502];
    public const END_USER_GROUPS = [88, 99];

    static function getEntityClassNames(): array
    {
        return [SalesData::class];
    }

    public function loadYearSum(int $storeId, int $year, array $selectedMonths = []): \stdClass
    {
        $filter = ['store' => $storeId, 'year' => $year];

        if ($selectedMonths) {
            $filter['month'] = $selectedMonths;
        }

        $result = new \stdClass();
        $saleDataCollection = $this->findBy($filter);
        $salesProps = [
            'lastSale', 'salePlan', 'realSale', 'salePlanDifference', 'lastSaleDifference',
            'lastProfit', 'profitPlan', 'realProfit', 'profitPlanDifference', 'lastProfitDifference'
        ];

        foreach ($salesProps as $salesProp) {
            $result->$salesProp = array_sum($saleDataCollection->fetchPairs(null, $salesProp));
        }

        return $result;
    }

    public function loadStoreFulfillment(int $storeId, int $year, array $selectedMonths = []): \stdClass
    {
        $saleFilter = ['store' => $storeId, 'year' => $year, 'realSale!=' => 0];
        $profitFilter = ['store' => $storeId, 'year' => $year, 'realProfit!=' => 0];

        if ($selectedMonths) {
            $saleFilter['month'] = $selectedMonths;
            $profitFilter['month'] = $selectedMonths;
        }

        $saleDataCollection = $this->findBy($saleFilter);
        $profitDataCollection = $this->findBy($profitFilter);

        $lastSaleSum = array_sum($saleDataCollection->fetchPairs(null, 'lastSale'));
        $salePlanSum = array_sum($saleDataCollection->fetchPairs(null, 'salePlan'));
        $realSaleSum = array_sum($saleDataCollection->fetchPairs(null, 'realSale'));

        $lastProfitSum = array_sum($profitDataCollection->fetchPairs(null, 'lastProfit'));
        $profitPlanSum = array_sum($profitDataCollection->fetchPairs(null, 'profitPlan'));
        $realProfitSum = array_sum($profitDataCollection->fetchPairs(null, 'realProfit'));

        $result = new \stdClass();
        $result->lastSaleResult = $this->getFulfillment($realSaleSum, $lastSaleSum);
        $result->salePlanResult = $this->getFulfillment($realSaleSum, $salePlanSum);
        $result->lastProfitResult = $this->getFulfillment($realProfitSum, $lastProfitSum);
        $result->profitPlanResult = $this->getFulfillment($realProfitSum, $profitPlanSum);
        return $result;
    }

    private function getFulfillment(float $sum1, float $sum2): ?float
    {
        if ($sum2 == 0) {
            return null;
        }

        $portion = $sum1 / $sum2;

        if ($portion >= 1) {
            return round(($portion * 100) - 100, 1);
        }

        return - round((1 - $portion) * 100, 1);
    }
}
