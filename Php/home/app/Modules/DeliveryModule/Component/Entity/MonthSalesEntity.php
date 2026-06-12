<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Component\Entity;

class MonthSalesEntity
{
    public float $progressSale;
    public float $realSale;
    public float $lastYearProgressSale;
    public float $progressProfit;
    public float $realProfit;
    public float $lastYearProgressProfit;
    public int $lastYearSale;
    public int $salePlan;
    public int $lastYearProfit;
    public int $profitPlan;
    public int $costsPlan;
}