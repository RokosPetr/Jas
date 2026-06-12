<?php
declare(strict_types=1);

namespace App\Modules\CliModule\Service;

use App\Modules\DeliveryModule\Component\SalesOverview;
use App\Modules\DeliveryModule\Orm\SalesData\SalesData;
use App\Modules\DeliveryModule\Orm\SalesData\SalesDataRepository;
use App\Service\OrmModel;

/**
 * Sluzba pro import dat k analyze prodeju pobocek
 */
class SalesDataImporter
{
    private OrmModel $orm;

    public function __construct(OrmModel $orm)
    {
        $this->orm = $orm;
    }

    /** Import dat k analyze prodeju pobocek z csv souboru */
    public function importSalesData(?string $fileContent): string
    {
        if (!$fileContent) {
            return 'Soubor neobsahuje žádná data';
        }

        $imports = [];
        $updateCols = [
            'last_sale', 'sale_plan', 'real_sale',
            'last_profit', 'profit_plan', 'real_profit', 'costs_plan',
            'sale_plan_difference','last_sale_difference','profit_plan_difference','last_profit_difference'
        ];
        $separator = "\r\n";
        $line = strtok($fileContent, $separator);

        while ($line !== false) {
            $csvData = str_getcsv($line, ';');

            if (!is_numeric($csvData[0])) {
                $line = strtok($separator);
                continue;
            }

            $imports[] = [
                'store' => (int) $csvData[0],
                'last_sale' => (int) $csvData[1],
                'sale_plan' => (int) $csvData[2],
                'real_sale' => (int) $csvData[3],
                'last_profit' => (int) $csvData[4],
                'profit_plan' => (int) $csvData[5],
                'costs_plan' => (int) $csvData[6],
                'real_profit' => (int) $csvData[7],
                'month' => (int) $csvData[8],
                'year' => (int) $csvData[9],
                'sale_plan_difference' => 0,
                'last_sale_difference' => 0,
                'profit_plan_difference' => 0,
                'last_profit_difference' => 0
            ];

            $line = strtok($separator);
        }

        $this->orm->salesData->updateItems($imports, $updateCols);

        return '';
    }

    public function updateSalesData(int $year, int $month): void
    {
        $dayInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $date = \DateTime::createFromFormat('j.n.Y', "$dayInMonth.$month.$year");
        $lastYear = $year - 1;

        foreach (array_keys(SalesOverview::SALE_GROUPS) as $storeId) {
            $result = $this->orm->salesData->loadToDateInMonth($storeId, $date);
            $sale = intval(round($result[SalesData::RAW_SALES_DATA]));
            $profit = intval(round($result[SalesData::RAW_PROFIT_DATA]));
            $salesData = $this->orm->salesData->getBy(['store' => $storeId, 'year' => $year, 'month' => $month]);
            $lastSalesData = $this->orm->salesData->getBy(['store' => $storeId, 'year' => $lastYear, 'month' => $month]);

            if (!$salesData) {
                $salesData = new SalesData();
                $salesData->store = $storeId;
                $salesData->year = $year;
                $salesData->month = $month;
                $salesData->salePlan = 0;
                $salesData->profitPlan = 0;
            }

            if ($lastSalesData) {
                $lastSale = $lastSalesData->realSale;
                $lastProfit = $lastSalesData->realProfit;
            } else {
                $lastDayInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $lastYear);
                $lastDate = \DateTime::createFromFormat('j.n.Y', "$lastDayInMonth.$month.$lastYear");
                $lastResult = $this->orm->salesData->loadToDateInMonth($storeId, $lastDate);
                $lastSale = intval(round($lastResult[SalesData::RAW_SALES_DATA]));
                $lastProfit = intval(round($lastResult[SalesData::RAW_PROFIT_DATA]));
            }

            $salesData->realSale = $sale;
            $salesData->lastSale = $lastSale;
            $salesData->lastSaleDifference = $sale - $lastSale;
            $salesData->salePlanDifference = $sale - $salesData->salePlan;

            $salesData->realProfit = $profit;
            $salesData->lastProfit = $lastProfit;
            $salesData->lastProfitDifference = $profit - $lastProfit;
            $salesData->profitPlanDifference = $profit - $salesData->profitPlan;

            $this->orm->salesData->persist($salesData);
        }

        $this->orm->salesData->flush();
    }

    public function updateSalesDataByStoreId(int $year, int $month, int $storeId, SalesData $sumSalesData): SalesData
    {
        $dayInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $date = \DateTime::createFromFormat('j.n.Y', "$dayInMonth.$month.$year");
        $lastYear = $year - 1;

        $result = $this->orm->salesData->loadToDateInMonth($storeId, $date);
        $sale = intval(round($result[SalesData::RAW_SALES_DATA]));
        $profit = intval(round($result[SalesData::RAW_PROFIT_DATA]));
        $salesData = $this->orm->salesData->getBy(['store' => $storeId, 'year' => $year, 'month' => $month]);
        $lastSalesData = $this->orm->salesData->getBy(['store' => $storeId, 'year' => $lastYear, 'month' => $month]);

        if ($storeId > 100 and $storeId % 100 == 4){
            $salesData->realSale = $sumSalesData->realSale; // - $sale;
            $salesData->lastSale = $sumSalesData->lastSale;
            $salesData->lastSaleDifference = $sumSalesData->lastSaleDifference;
            $salesData->salePlanDifference = $sumSalesData->salePlanDifference;
            $salesData->realProfit = $sumSalesData->realProfit; // - $profit;
            $salesData->lastProfit = $sumSalesData->lastProfit;
            $salesData->lastProfitDifference = $sumSalesData->lastProfitDifference;
            $salesData->profitPlanDifference = $sumSalesData->profitPlanDifference;
            $this->orm->salesData->persist($salesData);
            $this->orm->salesData->flush();
            return $salesData;
        }

        if (!$salesData) {
            $salesData = new SalesData();
            $salesData->store = $storeId;
            $salesData->year = $year;
            $salesData->month = $month;
            $salesData->salePlan = 0;
            $salesData->profitPlan = 0;
        }

        if ($lastSalesData) {
            $lastSale = $lastSalesData->realSale;
            $lastProfit = $lastSalesData->realProfit;
        } else {
            $lastDayInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $lastYear);
            $lastDate = \DateTime::createFromFormat('j.n.Y', "$lastDayInMonth.$month.$lastYear");
            $lastResult = $this->orm->salesData->loadToDateInMonth($storeId, $lastDate);
            $lastSale = intval(round($lastResult[SalesData::RAW_SALES_DATA]));
            $lastProfit = intval(round($lastResult[SalesData::RAW_PROFIT_DATA]));
        }

        $salesData->realSale = $sale;
        $salesData->lastSale = $lastSale;
        $salesData->lastSaleDifference = $sale - $lastSale;
        $salesData->salePlanDifference = $sale - $salesData->salePlan;

        $salesData->realProfit = $profit;
        $salesData->lastProfit = $lastProfit;
        $salesData->lastProfitDifference = $profit - $lastProfit;
        $salesData->profitPlanDifference = $profit - $salesData->profitPlan;

        $this->orm->salesData->persist($salesData);

        $this->orm->salesData->flush();

        if($storeId == 4){
            $sumSalesData =  $salesData;
        }
        elseif($storeId < 9){
            $sumSalesData =  $salesData;
        }
        elseif ($storeId > 100 and $storeId % 100 < 4 and ($storeId < 401 or $storeId > 404)){
            $sumSalesData->realSale				=	$sumSalesData->realSale				-	 $salesData->realSale;
            $sumSalesData->lastSale				=	$sumSalesData->lastSale				-	 $salesData->lastSale;
            $sumSalesData->lastSaleDifference	=	$sumSalesData->lastSaleDifference	-	 $salesData->lastSaleDifference;
            $sumSalesData->salePlanDifference	=	$sumSalesData->salePlanDifference	-	 $salesData->salePlanDifference;

            $sumSalesData->realProfit			=	$sumSalesData->realProfit			-	 $salesData->realProfit;
            $sumSalesData->lastProfit			=	$sumSalesData->lastProfit			-	 $salesData->lastProfit;
            $sumSalesData->lastProfitDifference	=	$sumSalesData->lastProfitDifference	-	 $salesData->lastProfitDifference;
            $sumSalesData->profitPlanDifference	=	$sumSalesData->profitPlanDifference	-	 $salesData->profitPlanDifference;
        }
        return $sumSalesData;

    }
}
