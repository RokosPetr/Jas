<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\SalesData;

use App\Core\Orm\BaseEntity;

/**
 * @property int                          $id               {primary}
 * @property int                          $store
 * @property int                          $month
 * @property int                          $year
 * @property int                          $lastSale
 * @property int                          $salePlan
 * @property int                          $realSale
 * @property int                          $salePlanDifference
 * @property int                          $lastSaleDifference
 * @property int                          $lastProfit
 * @property int                          $profitPlan
 * @property int                          $realProfit
 * @property int                          $profitPlanDifference
 * @property int                          $lastProfitDifference
 * @property int                          $costsPlan
 */
class SalesData extends BaseEntity
{
    public const RAW_SALES_DATA = 1;
    public const RAW_PROFIT_DATA = 2;
    public const OPEN_SALES_DATA = 3;
    public const OPEN_PROFIT_DATA = 4;
}
