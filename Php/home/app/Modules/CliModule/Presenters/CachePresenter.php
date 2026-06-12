<?php
declare(strict_types=1);

namespace App\Modules\CliModule\Presenters;

use App\Core\Component\Form\BaseForm;
use App\Core\Utils\DateTime;
use App\Modules\CliModule\Service\SalesDataImporter;
use App\Modules\DeliveryModule\Component\SalesOverview;
use App\Modules\DeliveryModule\Orm\TakingsOverviewCache\TakingsOverviewCache;
use App\Modules\DeliveryModule\Service\TakingsOverviewCacheService;
use App\Modules\Presenters\BasePresenter;
use App\Modules\SystemModule\Orm\Stores\Store;
use App\Modules\DeliveryModule\Orm\SalesData\SalesData;
use App\Modules\CliModule\Orm\Imports\Import;

/** Presenter pro tvorbu cache dat */
final class CachePresenter extends BasePresenter
{
    public array $titles = [
        'default' => 'Tvorba Cache'
    ];

    /** @inject */
    public TakingsOverviewCacheService $takingsOverviewCacheService;
    /** @inject */
    public SalesDataImporter $salesDataImporter;

    public function renderDefault(): void
    {
        $this->template->createCache = [
            'yearTakingsOverview' => 'Analýza nákupů - roční cache',
            'monthTakingsOverview' => 'Analýza nákupů - pravidelná měsíční cache',
            'yearStoreSellsOverview' => 'Analýza odběrů - roční cache pro pobočky',
            'monthStoreSellsOverview' => 'Analýza odběrů - pravidelná měsíční cache pro pobočky',
            'customSalesData' => 'Analýza prodeje - tvorba uzávěrek poboček',
            'regularSalesData' => 'Analýza prodeje - pravidelná uzávěrka poboček'
        ];
    }

    /** Ulozi do cache data pro analyzu nakupu za minuly mesic */
    public function actionMonthTakingsOverview(): void
    {
        $currentYear = (int) date('Y');
        $currentMonth = ((int) date('n'));
        $lastMonth = $currentMonth - 1;
        $lastMonthYear = $currentYear;

        if ($lastMonth === 0) {
            $lastMonth = 12;
            $lastMonthYear--;
        }

        $this->takingsOverviewCacheService->setMonthCache($currentYear, $currentMonth);
        $this->takingsOverviewCacheService->setMonthCache($lastMonthYear, $lastMonth);
        $this->flashMessage('Analýza nákupů - měsíční cache vytvořena');
        $this->redirect('default');
    }

    /** Ulozi do cache data pro analyzu odberu pobocek za minuly mesic */
    //Analýza odběrů poboček
    public function actionMonthStoreSellsOverview(): void
    {
        $currentYear = (int) date('Y');
        $currentMonth = ((int) date('n'));
        $lastMonth = $currentMonth - 1;
        $lastMonthYear = $currentYear;

        if ($lastMonth === 0) {
            $lastMonth = 12;
            $lastMonthYear--;
        }

        $this->takingsOverviewCacheService->setStoreSellsMonthCache($currentYear, $currentMonth);
        $this->takingsOverviewCacheService->setStoreSellsMonthCache($lastMonthYear, $lastMonth);
        $this->flashMessage('Analýza odběrů - měsíční cache vytvořena');
        $this->redirect('default');
    }

    /** Ulozi data prodeju pobocek za minuly mesic */
    public function actionRegularSalesData(int $year = null, int $month = null, bool $cron = true): void
    {

        if($year == null and $month == null){
            $year = intval(date('Y'));
            $month = intval(date('n')) - 1;

            if (!$month) {
                $month = 12;
                $year--;
            }
        }
        elseif ($year == null or $month == null){
            $this->flashMessage("Import neproběhl. Nebyl zadán rok nebo měsíc.");
            $this->redirect('default');
        }

        $zipName = 'intranet.zip';
        $zip = new \ZipArchive();

        $arrOstrava = array_filter(array_keys(SalesOverview::SALE_GROUPS), function ($var){
            return ($var >=90 and $var < 100) or $var == 910 ;
        });

        $iOstrava = new Import();
        if($cron) {
            $iOstrava = $this->orm->imports->getImportByName("Analýza - Velkoobchod");
            $db_month =  intval($iOstrava->date->format("n"));
            $db_year =  intval($iOstrava->date->format("Y"));
            if(!(($db_month == $month and $db_year == $year) or $db_year < $year)){
                $this->flashMessage("Prodejní data $month/$year aktualizována");
                $this->redirect('default');
            }
        }

        $finished = 0;
        $updated = 0;

        $stores = $this->orm->stores->findBy(['id<' => 9]);

        foreach ($stores as $store) {
            $sumSalesData = new SalesData();
            $db_month = $month;
            $db_year = $year;
            $import = new Import();
            if($cron){
                $import = $this->orm->imports->getImportByName("Analýza - $store->name");
                $db_month =  intval($import->date->format("n"));
                $db_year =  intval($import->date->format("Y"));
            }
            if($db_month == $month and $db_year == $year){
                $auo = [$month+1, $year];
                if($cron){
                    if ($zip->open(DATA_DIR . "/$store->id/$zipName") !== true) {
                        $this->flashMessage("Import nepoběhl. Nelze načíst zdrojový soubor '$zipName'");
                        $this->redirect('default');
                    }
                    $fileName = "aktualni_ucetni_obdobi.csv";
                    $fileStats = $zip->statName($fileName);

                    if (!$fileStats) {
                        $zip->close();
                        $this->flashMessage("Import neproběhl. Nelze načíst zdrojový soubor '$fileName' ");
                        $this->redirect('default');
                    }

                    $currentTaxSeason = $zip->getFromName($fileName);
                    $data = str_getcsv($currentTaxSeason, ';');
                    $auo = [intval($data[0]), intval($data[1])];
                }
                if($auo[0] > $month and $auo[1] == $year){
                    $sumSalesData = $this->salesDataImporter->updateSalesDataByStoreId($year, $month, $store->id, $sumSalesData);
                    $sumSalesData = $this->salesDataImporter->updateSalesDataByStoreId($year, $month, $store->id * 100 + 1, $sumSalesData);
                    $sumSalesData = $this->salesDataImporter->updateSalesDataByStoreId($year, $month, $store->id * 100 + 2, $sumSalesData);
                    $sumSalesData = $this->salesDataImporter->updateSalesDataByStoreId($year, $month, $store->id * 100 + 3, $sumSalesData);
                    $sumSalesData = $this->salesDataImporter->updateSalesDataByStoreId($year, $month, $store->id * 100 + 4, $sumSalesData);
                    if($cron){
                        $import->date = date('Y-m-d');
                        $this->orm->imports->persist($import);
                        $this->orm->imports->flush();
                    }
                    $updated++;
                }

            }
            else{
                $finished++;
            }
        }

        if($finished + $updated > 0){
            $sumSalesData = new SalesData();
            foreach ($arrOstrava as $storeId) {
                $sumSalesData = $this->salesDataImporter->updateSalesDataByStoreId($year, $month, $storeId, $sumSalesData);
            }
            if($finished + $updated == 8){
                $db_month = $month;
                $db_year = $year;
                if($cron){
                    $db_month =  intval($iOstrava->date->format("n"));
                    $db_year =  intval($iOstrava->date->format("Y"));
                }
                $stores = $this->orm->stores->findBy(['id' => array(9,10)]);
                $iVelkoobchod = false;
                foreach ($stores as $store) {
                    if($db_month == $month and $db_year == $year){
                        $auo = [$month+1, $year];
                        if($cron){
                            if ($zip->open(DATA_DIR . "/$store->id/$zipName") !== true) {
                                $this->flashMessage("Import nepoběhl. Nelze načíst zdrojový soubor '$zipName'");
                                $this->redirect('default');
                            }
                            $fileName = "aktualni_ucetni_obdobi.csv";
                            $fileStats = $zip->statName($fileName);

                            if (!$fileStats) {
                                $zip->close();
                                $this->flashMessage("Import neproběhl. Nelze načíst zdrojový soubor '$fileName' '$store->id'");
                                $this->redirect('default');
                            }

                            $currentTaxSeason = $zip->getFromName($fileName);
                            $data = str_getcsv($currentTaxSeason, ';');
                            $auo = [intval($data[0]), intval($data[1])];
                        }

                        if($auo[0] > $month and $auo[1] == $year){
                            if(!$iVelkoobchod){
                                $iVelkoobchod = true;
                            }
                            elseif($cron){
                                    $iOstrava->date = date('Y-m-d');
                                    $this->orm->imports->persist($iOstrava);
                                    $this->orm->imports->flush();
                            }
                        }

                    }
                }
            }
        }

        $this->flashMessage("Prodejní data $month/$year aktualizována");
        $this->redirect('default');
    }

    protected function createComponentSetTakingsOverviewCacheForm(): BaseForm
    {
        $years = range(2011, intval(date('Y')));
        $form = new BaseForm();
        $form->addSelect('year', 'Rok', array_combine($years, $years))->setRequired();
        $form->addSubmit('create', 'Vytvořit');

        $form->onSuccess[] = function (array $values): void {
            $this->takingsOverviewCacheService->setYearCache($values['year']);
            $this->flashMessage('Cache analýzy dat vytvořena');
            $this->redirect('this');
        };

        return $form;
    }

    protected function createComponentSetStoreSellsOverviewCacheForm(): BaseForm
    {
        $years = range(2011, intval(date('Y')));
        $stores = [0 => 'Vše'] + $this->orm->stores->loadSimpleStores();
        $stores[Store::OSTRAVA_MAIN_STORAGE] = 'Michálkovice';
        $form = new BaseForm();
        $form->addSelect('year', 'Rok', array_combine($years, $years))->setRequired();
        $form->addSelect('store', 'Pobočka', $stores)->setRequired();
        $form->addSubmit('create', 'Vytvořit');

        $form->onSuccess[] = function (array $values): void {
            set_time_limit(300);
            $this->takingsOverviewCacheService->setYearCache(
                $values['year'],
                TakingsOverviewCache::TYPE_STORE_SELLS,
                $values['store']
            );
            $this->flashMessage('Cache analýzy dat vytvořena pro rok ' . $values['year']);
            $this->redirect('this');
        };

        return $form;
    }

    protected function createComponentSalesDataCacheForm(): BaseForm
    {
        $years = range(intval(date('Y')), 2014);
        $form = new BaseForm();
        $form->addSelect('year', 'Rok', array_combine($years, $years))->setRequired();
        $form->addSelect('month', 'Měsíc', DateTime::CZ_MONTHS)->setRequired();
        $form->addSubmit('submit', 'Vytvořit');

        $form->onValidate[] = function (BaseForm $form, array $values): void {
            $currYear = intval(date('Y'));
            $currMonth = intval(date('n'));

            if ($values['year'] === $currYear && $values['month'] >= $currMonth) {
                $form['month']->addError('Období musí být v minulosti');
            }
        };

        $form->onSuccess[] = function (array $values): void {
            $this->salesDataImporter->updateSalesData($values['year'], $values['month']);
            $season = $values['month'] . '/' . $values['year'];
            $this->flashMessage("Prodejní data $season aktualizována");
            $this->redirect('this');
        };

        return $form;
    }
}
