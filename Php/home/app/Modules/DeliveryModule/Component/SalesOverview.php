<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Component;

use App\Core\Utils\DateTime;
use App\Modules\DeliveryModule\Component\Entity\MonthSalesEntity;
use App\Modules\DeliveryModule\Orm\SalesData\SalesData;
use App\Service\OrmModel;
use Nette\Application\UI\Control;
use Nette\Application\UI\Presenter;
use Nette\Http\SessionSection;

class SalesOverview extends Control
{
    public const TABLE_VIEW = 1;
    public const GRAPH_VIEW = 2;
    public const SUM_VIEW = 3;
    public const CURRENT_DATE_VIEW = 4;
    public const ABSOLUTE_SUM_VIEW = 5;

    public const PLAN_COMPARISON = 1;
    public const LAST_YEAR_COMPARISON = 2;

    public const SALE_GROUPS = [
        90 => 'Velkoobchod',
        91 => 'Velkoobchod sk.1',
        92 => 'Velkoobchod sk.2',
        1 => 'Prodejna Šumperk',
        101 => 'Šumperk OZ.1 (33,88)',
        102 => 'Šumperk OZ.2 (55)',
        103 => 'Šumperk stavební firmy (22)',
        104 => 'Šumperk drobný prodej',
        2 => 'Prodejna Olomouc',
        201 => 'Olomouc OZ.1 (33,88)',
        202 => 'Olomouc OZ.2 (55)',
        203 => 'Olomouc stavební firmy (22)',
        204 => 'Olomouc drobný prodej',
        3 => 'Prodejna Otrokovice',
        301 => 'Otrokovice OZ.1 (33,88)',
        302 => 'Otrokovice OZ.2 (55)',
        303 => 'Otrokovice stavební firmy (22)',
        304 => 'Otrokovice drobný prodej',
        4 => 'Prodejna Ostrava',
        401 => 'Ostrava OZ.1 (33,88)',
        402 => 'Ostrava OZ.2 (55)',
        403 => 'Ostrava stavební firmy (22)',
        404 => 'Ostrava drobný prodej',
        5 => 'Prodejna Prostějov',
        501 => 'Prostějov OZ.1 (33,88)',
        502 => 'Prostějov OZ.2 (55)',
        503 => 'Prostějov stavební firmy (22)',
        504 => 'Prostějov drobný prodej',
        6 => 'Prodejna Teplice',
        601 => 'Teplice OZ.1 (33,88)',
        602 => 'Teplice OZ.2 (55)',
        603 => 'Teplice stavební firmy (22)',
        604 => 'Teplice drobný prodej',
        7 => 'Prodejna Valašské Meziříčí',
        701 => 'Valašské Meziříčí OZ.1 (33,88)',
        702 => 'Valašské Meziříčí OZ.2 (55)',
        703 => 'Valašské Meziříčí stavební firmy (22)',
        704 => 'Valašské Meziříčí drobný prodej',
        8 => 'Prodejna Hradec Králové',
        801 => 'Hradec Králové OZ.1 (33,88)',
        802 => 'Hradec Králové OZ.2 (55)',
        803 => 'Hradec Králové stavební firmy (22)',
        804 => 'Hradec Králové drobný prodej',
        99 => 'Eshop',
        910 => 'Koupelnové vybavení'
    ];

    private SessionSection $session;
    private OrmModel $orm;

    public array $selectedMonths;
    public int $selectedYear;
    public int $viewMode;
    public bool $selectAllMonths = false;
    public bool $showVO;
    public bool $showProd;
    public bool $showOzGroups;
    public bool $showSF;
    public bool $showDP;
    public bool $showALL;
    public \DateTimeImmutable $selectedDate;
    public array $selectedStores;
    public array $storeAccess;
    public array $selectedComparisons;

    public function __construct(OrmModel $orm, array $storeAccess)
    {
        $this->orm = $orm;
        $this->storeAccess = $storeAccess;

        $this->monitor(Presenter::class, function (): void {
            $this->session = $this->getPresenter()->getSession('salesOverview');
        });
    }

    public function loadState(array $params): void
    {
        $this->viewMode = $this->session->viewMode ?? self::SUM_VIEW;
        $this->selectedMonths = $this->session->selectedMonths ?? range(1, 12);
        $this->selectedYear = $this->session->selectedYear ?? (int) date('Y');
        $this->selectedDate = !empty($this->session->selectedDate)
            ? \DateTimeImmutable::createFromFormat(DateTime::CZ_DATE, $this->session->selectedDate)
            : new \DateTimeImmutable();
        $this->selectedStores = $this->session->selectedStores ?? $this->storeAccess;
        $this->selectedComparisons = $this->session->selectedComparisons ?? [self::PLAN_COMPARISON, self::LAST_YEAR_COMPARISON];
        $this->showVO = $this->session->showVO ?? true;
        $this->showProd = $this->session->showProd ?? true;
        $this->showOzGroups = $this->session->showOzGroups ?? true;
        $this->showSF = $this->session->showSF ?? true;
        $this->showDP = $this->session->showDP ?? true;
        $this->showALL = $this->session->showALL ?? true;
    }

    public function handleSetState(): void
    {
        $stateParam = $this->getPresenter()->getParameter('stateParam');
        $value = $this->getPresenter()->getParameter('value');
        $this->$stateParam = $this->session->$stateParam = (int) $value;
        $this->redrawControl('salesOverview');
    }

    public function handleSetAllMonths(): void
    {
        $value = (bool) $this->getPresenter()->getParameter('value');
        $this->selectedMonths = $this->session->selectedMonths = $value ? range(1, 12) : [];
        $this->selectAllMonths = $value;
        $this->redrawControl('salesOverview');
    }

    public function handleSetMonths(): void
    {
        $values = $this->getPresenter()->getParameter('selectedMonths');
        $this->selectedMonths = $this->session->selectedMonths = (array) $values;
        $this->redrawControl('salesOverview');
    }

    public function handleSetDate(): void
    {
        $value = $this->getPresenter()->getParameter('value');
        $date = \DateTimeImmutable::createFromFormat(DateTime::CZ_DATE, $value);
        if ($date) {
            $this->session->selectedDate = $value;
            $this->selectedDate = $date;
        }
        $this->redrawControl('salesOverview');
    }

    public function handleSetStores(): void
    {
        $selectedStores = $this->getPresenter()->getParameter('stores') ?? [];
        $this->selectedStores = in_array(0, $selectedStores) ? $this->storeAccess : $selectedStores;
        $this->session->selectedStores = $this->selectedStores;

        if(count($this->getPresenter()->getParameter('checks')) == 1){
            $all =  filter_var($this->getPresenter()->getParameter('checks')['all'], FILTER_VALIDATE_BOOLEAN);
            $this->showALL = $this->session->showALL = $all;
            $this->showVO = $this->session->showVO = $all;
            $this->showProd = $this->session->showProd = $all;
            $this->showDP = $this->session->showDP = $all;
            $this->showOzGroups = $this->session->showOzGroups = $all;
            $this->showSF = $this->session->showSF = $all;
        }
        else{
            $this->showVO = $this->session->showVO = filter_var($this->getPresenter()->getParameter('checks')['vo'], FILTER_VALIDATE_BOOLEAN);
            $this->showProd = $this->session->showProd = filter_var($this->getPresenter()->getParameter('checks')['pr'], FILTER_VALIDATE_BOOLEAN);
            $this->showDP = $this->session->showDP = filter_var($this->getPresenter()->getParameter('checks')['dp'], FILTER_VALIDATE_BOOLEAN);
            $this->showOzGroups = $this->session->showOzGroups = filter_var($this->getPresenter()->getParameter('checks')['oz'], FILTER_VALIDATE_BOOLEAN);
            $this->showSF = $this->session->showSF = filter_var($this->getPresenter()->getParameter('checks')['sf'], FILTER_VALIDATE_BOOLEAN);
        }

        $allStores =  array_keys(self::SALE_GROUPS);

        if ($this->viewMode != self::TABLE_VIEW) {
            unset($allStores[0]);
            $this->showVO = $this->session->showVO = (count(array_diff([91,92],array_map('intval', $selectedStores))) == 0);
        }
        else
        {
            $this->showVO = $this->session->showVO = (count(array_diff([90,91,92],array_map('intval', $selectedStores))) == 0);
        }

        $this->showALL = $this->session->showALL = (count(array_diff($allStores,array_map('intval', $selectedStores))) == 0);
        $this->showProd = $this->session->showProd = (count(array_diff([1,2,3,4,5,6,7,8],array_map('intval', $selectedStores))) == 0);
        $this->showDP = $this->session->showDP = (count(array_diff([104,204,304,404,504,604,704,804],array_map('intval', $selectedStores))) == 0);
        $this->showOzGroups = $this->session->showOzGroups = (count(array_diff([101,102,201,202,301,302,401,402,501,502,601,602,701,702,801,802],array_map('intval', $selectedStores))) == 0);
        $this->showSF = $this->session->showSF = (count(array_diff([103,203,303,403,503,603,703,803],array_map('intval', $selectedStores))) == 0);

            $this->redrawControl('salesOverview');
    }

    public function handleSetComparison(): void
    {
        $comparisons = (array) $this->getPresenter()->getParameter('comparisons');
        $this->selectedComparisons = $this->session->selectedComparisons = $comparisons;
        $this->redrawControl('salesOverview');
    }

    public function handleSetShowOzGroups(): void
    {
        $showOzGroups = (bool) $this->getPresenter()->getParameter('showOzGroups');
        $this->showOzGroups = $this->session->showOzGroups = $showOzGroups;
        $this->redrawControl('salesOverview');
    }

    public function handleSetShowSF(): void
    {
        $showSF = (bool) $this->getPresenter()->getParameter('showSF');
        $this->showSF = $this->session->showSF = $showSF;
        $this->redrawControl('salesOverview');
    }

    public function handleSetShowDP(): void
    {
        $showDP = (bool) $this->getPresenter()->getParameter('showDP');
        $this->showDP = $this->session->showDP = $showDP;
        $this->redrawControl('salesOverview');
    }

    public function render(): void
    {
        if ($this->viewMode === self::TABLE_VIEW) {
            $this->template->yearSalesData = $this->loadYearSalesData($this->showOzGroups, $this->showSF, $this->showDP);
            $this->template->fulfillment = $this->loadFulfillment();
        }

        if ($this->viewMode === self::SUM_VIEW) {
            $this->template->fulfillment = $this->loadFulfillment(true);
        }

        if ($this->viewMode === self::ABSOLUTE_SUM_VIEW) {
            $this->template->fulfillment = $this->loadAbsoluteFulfillment();
        }

        if ($this->viewMode === self::CURRENT_DATE_VIEW) {
            $year = (int) $this->selectedDate->format('Y');
            $month = (int) $this->selectedDate->format('n');
            $this->template->currentDataView = $year >= intval(date('Y')) && $month >= intval(date('n'));
            $this->template->fulfillment = $this->loadDateFulfillment();
            $this->template->selectedMonth = DateTime::CZ_MONTHS[(int) $this->selectedDate->format('n')];
        }

        $this->template->season = $this->getSelectedSeason();
        $this->template->setFile(__DIR__ . '/templates/salesOverview.latte');
        $this->template->render();
    }

    private function loadYearSalesData(bool $includeOzGroups = true, bool $includeSF = true, bool $includeDP = true): array
    {
        $tablesData = [];

        foreach (self::SALE_GROUPS as $storeId => $storeName) {
            if (!in_array($storeId, $this->selectedStores)) {
                continue;
            }

            $storeSaleData = $this->orm->salesData->findBy([
                'store' => $storeId,
                'year' => $this->selectedYear,
                'month' => $this->selectedMonths
            ])->fetchPairs('month');

            $lastSale = 0;
            $salePlan = 0;
            $lastProfit = 0;
            $profitPlan = 0;
            foreach ($storeSaleData as $item) {
                $item->salePlanDifference = $item->realSale - $item->salePlan;
                $item->lastSaleDifference = $item->realSale - $item->lastSale;
                if($item->realSale == 0){
                    $item->lastSaleDifference = 0;
                    $item->salePlanDifference = 0;
                    $lastSale = $lastSale + $item->lastSale;
                    $salePlan = $salePlan + $item->salePlan;
                }
                if($item->realProfit == 0){
                    $item->lastProfitDifference = 0;
                    $item->profitPlanDifference = 0;
                    $lastProfit = $lastProfit + $item->lastProfit;
                    $profitPlan = $profitPlan + $item->profitPlan;
                }
                /*else{
                    $item->lastSaleDifference = $item->realSale - $item->lastSale;
                }*/
            }

            $storeSaleData['selectedSum'] = $this->selectedMonths
                ? $this->orm->salesData->loadYearSum($storeId, $this->selectedYear, $this->selectedMonths)
                : null;
            if ($storeSaleData['selectedSum']){
                $storeSaleData['selectedSum']->lastSale = $storeSaleData['selectedSum']->lastSale - $lastSale;
                $storeSaleData['selectedSum']->salePlan = $storeSaleData['selectedSum']->lastSale - $salePlan;
                $storeSaleData['selectedSum']->lastProfit = $storeSaleData['selectedSum']->lastProfit - $lastProfit;
                $storeSaleData['selectedSum']->profitPlan = $storeSaleData['selectedSum']->profitPlan - $profitPlan;
                $storeSaleData['yearSum'] = $this->orm->salesData->loadYearSum($storeId, $this->selectedYear);
                $tablesData[$storeName] = $storeSaleData;
            }
        }

        return $tablesData;
    }

    private function loadFulfillment(bool $allStores = false): array
    {
        $result = [];

        foreach (self::SALE_GROUPS as $storeId => $storeName) {

            if ($allStores){
                if (in_array($storeId, [90, 99])) {
                    // Velkoobchod a Eshop se do sumare nezadavaji
                    continue;
                }
                if ($storeId > 100 && !($this->getPresenter()->getUser()->isSuperAdmin() or $this->getPresenter()->getUser()->isManager())){
                    // OZ v sumari pouze pro adminy a bosse
                    continue;
                }
                if ($storeId > 100 && !$this->getPresenter()->getUser()->isSuperAdmin() && $this->getPresenter()->getUser()->isManager() && !in_array($storeId, $this->storeAccess)){
                    // OZ v sumari pouze pro adminy a bosse
                    continue;
                }
                if ($this->getPresenter()->getUser()->isSuperAdmin() && !in_array($storeId, $this->selectedStores)){
                    // pro adminy a bosse použiju filtr
                    continue;
                }
            }
            else if (!in_array($storeId, $this->selectedStores) && !in_array($storeId, $this->storeAccess)){
                // kromě sumáře se >= OZ zobrazují podle filtru a a přihlášeného uživatele
                continue;
            }

            $result[$storeName] = $this->orm->salesData->loadStoreFulfillment($storeId, $this->selectedYear, $this->selectedMonths);
        }

        return $result;
    }

    private function loadDateFulfillment(): array
    {
        $year = (int) $this->selectedDate->format('Y');
        $month = (int) $this->selectedDate->format('n');
        $currentDataView = $year >= intval(date('Y')) && $month >= intval(date('n'));
        $salesPlans = $this->orm->salesData->findBy(['year' => $year, 'month' => $month])->fetchPairs('store');
        $resultLast = [];
        $saleEntityLast_ = new MonthSalesEntity();
        $result = [];
        $saleEntity_ = new MonthSalesEntity();

        foreach (self::SALE_GROUPS as $storeId => $storeName) {

            $this->loadDateFulfillmentStore($resultLast, $saleEntityLast_, $storeId, $this->selectedDate->modify('-1 year'), false, $salesPlans);

            $this->loadDateFulfillmentStore($result, $saleEntity_, $storeId, $this->selectedDate, $currentDataView, $salesPlans);


//            $saleData = $this->orm->salesData->loadToDateInMonth($storeId, $this->selectedDate, $currentDataView);
//            $lastYearSalesData = $this->orm->salesData->loadToDateInMonth(
//                $storeId,
//                $this->selectedDate->modify('-1 year')
//            );
//            $k2 = $this->orm->salesData->loadK2real($storeId, $this->selectedDate);
//
//            if ($storeId == 91){
//                $a = 1;
//            }
//            if ($storeId == 103){
//                $a = 1;
//            }
//            /** @var SalesData $salePlan */
//            $salePlan = $salesPlans[$storeId] ?? null;
//            $saleEntity = new MonthSalesEntity();
//            $saleEntity->progressSale = $saleData[SalesData::RAW_SALES_DATA];
//            $saleEntity->progressProfit = $saleData[SalesData::RAW_PROFIT_DATA];
//            $saleEntity->lastYearProgressSale = $lastYearSalesData[SalesData::RAW_SALES_DATA];
//            $saleEntity->lastYearProgressProfit = $lastYearSalesData[SalesData::RAW_PROFIT_DATA];
//            $saleEntity->realSale = $currentDataView
//                ? $saleData[SalesData::RAW_SALES_DATA] - $saleData[SalesData::OPEN_SALES_DATA]
//                : $saleData[SalesData::RAW_SALES_DATA];
//            $saleEntity->realProfit = $currentDataView
//                ? $saleData[SalesData::RAW_PROFIT_DATA] - $saleData[SalesData::OPEN_PROFIT_DATA]
//                : $saleData[SalesData::RAW_PROFIT_DATA];
//
//            if (count($k2) > 0){
//                $saleEntity->realSale = $saleEntity->realSale + $k2[0]->real_sale;
//                $saleEntity->realProfit = $saleEntity->realProfit + $k2[0]->real_profit;
//                $saleEntity->progressSale = $saleEntity->progressSale + $k2[0]->sales_sale;
//                $saleEntity->progressProfit = $saleEntity->progressProfit + $k2[0]->sales_profit;
//                if ($storeId > 100){ // #03
//                    $storeId__ = ($storeId  - 3) / 100; // 1-8
//                    $result[$storeId__]->realSale = $result[$storeId__]->realSale + $k2[0]->real_sale;
//                    $result[$storeId__]->realProfit = $result[$storeId__]->realProfit + $k2[0]->real_profit;
//                    $result[$storeId__]->progressSale = $result[$storeId__]->progressSale + $k2[0]->sales_sale;
//                    $result[$storeId__]->progressProfit = $result[$storeId__]->progressProfit + $k2[0]->sales_profit;
//                }
//            }
//
//            $result[$storeId] = $saleEntity;
//
//            $storeId_ = (int)(str_split((string) $storeId)[0]);
//
//            if($storeId < 100){
//                $saleEntity_ = clone $saleEntity;
//            }
//            else if ($storeId < $storeId_*100+4){ // $storeId < #04
//                $saleEntity_->progressSale = $saleEntity_->progressSale - $saleEntity->progressSale;
//                $saleEntity_->progressProfit = $saleEntity_->progressProfit - $saleEntity->progressProfit;
//                $saleEntity_->lastYearProgressSale = $saleEntity_->lastYearProgressSale - $saleEntity->lastYearProgressSale;
//                $saleEntity_->lastYearProgressProfit = $saleEntity_->lastYearProgressProfit - $saleEntity->lastYearProgressProfit;
//                $saleEntity_->realSale = $saleEntity_->realSale - $saleEntity->realSale;
//                $saleEntity_->realProfit = $saleEntity_->realProfit - $saleEntity->realProfit;
//
//                if (count($k2) > 0){ // #03
//                    $saleEntity_->realSale = $saleEntity_->realSale + $k2[0]->real_sale;
//                    $saleEntity_->realProfit = $saleEntity_->realProfit + $k2[0]->real_profit;
//                    $saleEntity_->progressSale = $saleEntity_->progressSale + $k2[0]->sales_sale;
//                    $saleEntity_->progressProfit = $saleEntity_->progressProfit + $k2[0]->sales_profit;
//                }
//
//            }
//            else if ($storeId == $storeId_*100+4){
//                if ($storeId == 304 || $storeId == 604){
//                    $saleEntity_->realSale = $saleEntity_->realSale + $saleEntity->realSale;
//                    $saleEntity_->realProfit  = $saleEntity_->realProfit + $saleEntity->realProfit;
//                    $saleEntity_->progressSale = $saleEntity_->progressSale + $saleEntity->progressSale;
//                    $saleEntity_->progressProfit  = $saleEntity_->progressProfit + $saleEntity->progressProfit;
//                }
//                $result[$storeId] = $saleEntity_;
//            }
//
//            $result[$storeId]->salePlan = $salePlan->salePlan ?? 0;
//            $result[$storeId]->lastYearSale = $salePlan->lastSale ?? 0;
//            $result[$storeId]->profitPlan = $salePlan->profitPlan ?? 0;
//            $result[$storeId]->lastYearProfit = $salePlan->lastProfit ?? 0;
//            $result[$storeId]->costsPlan = $salePlan->costsPlan ?? 0;

            if ($storeId == 202){
                $dsada = 1;
            }

        }

        foreach (self::SALE_GROUPS as $storeId => $storeName){
           $result[$storeId]->lastYearProgressSale = $resultLast[$storeId]->realSale;
           $result[$storeId]->lastYearProgressProfit = $resultLast[$storeId]->realProfit;
        }

        $allStores =  array_keys(self::SALE_GROUPS);
        $removeStores = array_diff($allStores, array_map('intval', $this->selectedStores));

        foreach($removeStores as $key ){
            unset($result[$key]);
        }

        return $result;
    }

    private function loadDateFulfillmentStore(&$result, &$saleEntity_, $storeId, $date, $currentDataView, $salesPlans): void
    {
        $saleData = $this->orm->salesData->loadToDateInMonth($storeId, $date, $currentDataView);
        $k2 = $this->orm->salesData->loadK2real($storeId, $date);

        if ($storeId == 1){
            $asda = 1;
        }

        $salePlan = $salesPlans[$storeId] ?? null;
        $saleEntity = new MonthSalesEntity();
        $saleEntity->progressSale = $saleData[SalesData::RAW_SALES_DATA];
        $saleEntity->progressProfit = $saleData[SalesData::RAW_PROFIT_DATA];
        $saleEntity->lastYearProgressSale = 0;
        $saleEntity->lastYearProgressProfit = 0;
        $saleEntity->realSale = $currentDataView
            ? $saleData[SalesData::RAW_SALES_DATA] - $saleData[SalesData::OPEN_SALES_DATA]
            : $saleData[SalesData::RAW_SALES_DATA];
        $saleEntity->realProfit = $currentDataView
            ? $saleData[SalesData::RAW_PROFIT_DATA] - $saleData[SalesData::OPEN_PROFIT_DATA]
            : $saleData[SalesData::RAW_PROFIT_DATA];

        if (count($k2) > 0){
            $saleEntity->realSale = $saleEntity->realSale + $k2[0]->real_sale;
            $saleEntity->realProfit = $saleEntity->realProfit + $k2[0]->real_profit;
            $saleEntity->progressSale = $saleEntity->progressSale + $k2[0]->sales_sale;
            $saleEntity->progressProfit = $saleEntity->progressProfit + $k2[0]->sales_profit;
            if ($storeId > 100){ // #03
                $storeId__ = ($storeId  - 3) / 100; // 1-8
                $result[$storeId__]->realSale = $result[$storeId__]->realSale + $k2[0]->real_sale;
                $result[$storeId__]->realProfit = $result[$storeId__]->realProfit + $k2[0]->real_profit;
                $result[$storeId__]->progressSale = $result[$storeId__]->progressSale + $k2[0]->sales_sale;
                $result[$storeId__]->progressProfit = $result[$storeId__]->progressProfit + $k2[0]->sales_profit;
            }
        }

        $result[$storeId] = $saleEntity;

        $storeId_ = (int)(str_split((string) $storeId)[0]);

        if($storeId < 100){
            $saleEntity_ = clone $saleEntity;
        }
        else if ($storeId < $storeId_*100+4){ // $storeId < #04
            $saleEntity_->progressSale = $saleEntity_->progressSale - $saleEntity->progressSale;
            $saleEntity_->progressProfit = $saleEntity_->progressProfit - $saleEntity->progressProfit;
            $saleEntity_->lastYearProgressSale = $saleEntity_->lastYearProgressSale - $saleEntity->lastYearProgressSale;
            $saleEntity_->lastYearProgressProfit = $saleEntity_->lastYearProgressProfit - $saleEntity->lastYearProgressProfit;
            $saleEntity_->realSale = $saleEntity_->realSale - $saleEntity->realSale;
            $saleEntity_->realProfit = $saleEntity_->realProfit - $saleEntity->realProfit;

            if (count($k2) > 0){ // #03
                $saleEntity_->realSale = $saleEntity_->realSale + $k2[0]->real_sale;
                $saleEntity_->realProfit = $saleEntity_->realProfit + $k2[0]->real_profit;
                $saleEntity_->progressSale = $saleEntity_->progressSale + $k2[0]->sales_sale;
                $saleEntity_->progressProfit = $saleEntity_->progressProfit + $k2[0]->sales_profit;
            }

        }
        else if ($storeId == $storeId_*100+4){
            if ($storeId == 304 || $storeId == 604){
                $saleEntity_->realSale = $saleEntity_->realSale + $saleEntity->realSale;
                $saleEntity_->realProfit  = $saleEntity_->realProfit + $saleEntity->realProfit;
                $saleEntity_->progressSale = $saleEntity_->progressSale + $saleEntity->progressSale;
                $saleEntity_->progressProfit  = $saleEntity_->progressProfit + $saleEntity->progressProfit;
            }
            $result[$storeId] = $saleEntity_;
        }

        $result[$storeId]->salePlan = $salePlan->salePlan ?? 0;
        $result[$storeId]->lastYearSale = $salePlan->lastSale ?? 0;
        $result[$storeId]->profitPlan = $salePlan->profitPlan ?? 0;
        $result[$storeId]->lastYearProfit = $salePlan->lastProfit ?? 0;
        $result[$storeId]->costsPlan = $salePlan->costsPlan ?? 0;

        if ($storeId == 1){
            $asda = 1;
        }

    }

    private function loadAbsoluteFulfillment(): array
    {
        $showOz = $this->showOzGroups && $this->getPresenter()->getUser()->isSuperAdmin();
        $_showSF = $this->showSF && $this->getPresenter()->getUser()->isSuperAdmin();
        $_showDP = $this->showDP && $this->getPresenter()->getUser()->isSuperAdmin();
        $fulfillment = [];

        foreach ($this->loadYearSalesData($showOz,$_showSF,$_showDP) as $store => $salesData) {
            if ($store === 'Velkoobchod' || $store === 'Eshop') {
                continue;
            }

            $selectedSums = $salesData['selectedSum'] ?? $salesData['yearSum'];
            $fulfillment[$store] = [
                'sale' => $selectedSums->realSale,
                'profit' => $selectedSums->realProfit
            ];

            foreach ([self::PLAN_COMPARISON, self::LAST_YEAR_COMPARISON] as $compareType) {
                $saleScore = $compareType === self::PLAN_COMPARISON
                    ? $selectedSums->salePlanDifference >= 0
                    : $selectedSums->lastSaleDifference >= 0;
                $profitScore = $compareType === self::PLAN_COMPARISON
                    ? $selectedSums->profitPlanDifference >= 0
                    : $selectedSums->lastProfitDifference >= 0;

                $fulfillment[$store][$compareType] = [
                    'saleScore' => $saleScore,
                    'profitScore' => $profitScore
                ];
            }
        }

        return $fulfillment;
    }

    private function getSelectedSeason(): string
    {
        if (!$this->selectedMonths) {
            return '-';
        }

        $previousMonth = false;
        $currIndex = 0;
        $seasons = [];

        foreach ($this->selectedMonths as $index => $month) {
            $nextMonth = $month + 1 == ($this->selectedMonths[$index + 1] ?? 0);

            if ($nextMonth && !$previousMonth) {
                $seasons[$currIndex] = "$month-";
                $previousMonth = true;
                continue;
            }

            if (!$nextMonth && $previousMonth) {
                $seasons[$currIndex] .= $month;
                $currIndex++;
                $previousMonth = false;
                continue;
            }

            if (!$nextMonth && !$previousMonth) {
                $seasons[$currIndex] = $month;
                $currIndex++;
            }
        }

        return implode(',', $seasons);
    }
}