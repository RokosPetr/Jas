<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Presenters;

use App\Core\Utils\DateTime;
use App\Modules\DeliveryModule\Component\TakingsOverview;
use App\Modules\DeliveryModule\Service\OverviewExporter;
use App\Modules\DeliveryModule\Service\TakingsOverviewCacheService;
use App\Modules\Presenters\SecurePresenter;
use App\Modules\StockModule\Orm\Producers\Producer;
use App\Modules\SystemModule\Orm\Stores\Store;

/** Presenter pro prezentaci dat nakupu sortimentu */
final class TakingsOverviewPresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'Analýza nákupů'
    ];

    /** @inject */
    public OverviewExporter $overviewExporter;

    public function actionProducerSumByPrice(int $producer, int $month, int $year): void
    {
        $takingsOverview = $this['takingsOverview'];
        $producer = $this->orm->producers->getById($producer);
        $span = TakingsOverviewCacheService::prepareMonthSpans($year)[$month] ?? null;
        $group = $this->orm->customGroups->getById($takingsOverview->selectedGroup);

        if (!$producer || !$span || !$group) {
            $this->flashMessage('Nejsou k dispozici validní vstupy', self::MSG_ERROR);
            $this->redirect('default');
        }

        $producers = $producer->children->getRawValue();
        $producers[] = $producer->id;
        $takingsData = $this->orm->deliveryNoteItems->loadTakingsPriceDataPerProducer(
            $producers,
            $group->stockGroups->getRawValue(),
            $span['start'],
            $span['end']
        );
        $emptyPaletteData = $this->orm->deliveryNoteItems->loadEmptyPalettesTakingsDataPerProducer(
            $producers,
            $span['start'],
            $span['end']
        );
        $this->template->noteData = array_merge($takingsData, $emptyPaletteData);
        $this->template->title = "$producer->name - suma cen nákupů na dodací list";
        $this->template->unit = 'Kč';
        $this->template->year = $takingsOverview->selectedYear;
        $this->template->month = DateTime::CZ_MONTHS[$month];
        $this->setView('producerSumCheck');
    }

    public function actionProducerSumByUnit(int $producer, int $month, int $year): void
    {
        $takingsOverview = $this['takingsOverview'];
        $producer = $this->orm->producers->getById($producer);
        $span = TakingsOverviewCacheService::prepareMonthSpans($year)[$month] ?? null;
        $group = $this->orm->customGroups->getById($takingsOverview->selectedGroup);

        if (!$producer || !$span || !$group) {
            $this->flashMessage('Nejsou k dispozici validní vstupy', self::MSG_ERROR);
            $this->redirect('default');
        }

        $producers = $producer->children->getRawValue();
        $producers[] = $producer->id;
        $this->template->noteData = $this->orm->deliveryNoteItems->loadSquareMetersTakingsDataPerProducer(
            $producers,
            $group->stockGroups->getRawValue(),
            $span['start'],
            $span['end']
        );
        $this->template->unit = 'm2';
        $this->template->title = "$producer->name - suma m2 odebraného zboží na dodací list";
        $this->template->year = $takingsOverview->selectedYear;
        $this->template->month = DateTime::CZ_MONTHS[$month];
        $this->setView('producerSumCheck');
    }

    public function actionProducerSumByPricePerStore(int $producer, int $store, int $month, int $year): void
    {
        $takingsOverview = $this['takingsOverview'];
        $span = TakingsOverviewCacheService::prepareMonthSpans($year)[$month] ?? null;
        $group = $this->orm->customGroups->getById($takingsOverview->selectedGroup);

        if (!$span || !$group) {
            $this->flashMessage('Nejsou k dispozici validní vstupy', self::MSG_ERROR);
            $this->redirect('default');
        }

        if ($producer === Producer::DC_RAVAK_ID) {
            // DC Ravak hack
            $producers = [$this->orm->producers->getBy(['name' => Producer::RAVAK_NAME])->id ?? 0];
            $stockGroups = $this->orm->stockGroups->findDcRavakGroups()->fetchPairs(null, 'id');
            $producerName = 'DC Ravak';
        } else {
            $producer = $this->orm->producers->getById($producer);

            if (!$producer) {
                $this->flashMessage('Nejsou k dispozici validní vstupy', self::MSG_ERROR);
                $this->redirect('default');
            }

            $producers = $producer->children->getRawValue();
            $producers[] = $producer->id;
            $stockGroups = $group->stockGroups->getRawValue();
            $producerName = $producer->name;
        }

        $store = $this->orm->stores->getById($store);
        $this->template->noteData = $this->orm->deliveryNoteItems->loadStoreTakingsDataPerProducer(
            $producers,
            $stockGroups,
            $span['start'],
            $span['end']
        );
        $this->template->isMainStorage = in_array($store->id, Store::MAIN_STORAGES);
        $this->template->storeId = $store->id;
        $this->template->title = "$store->name - $producerName - suma cen nákupů na dodací list";
        $this->template->unit = 'Kč';
        $this->template->year = $takingsOverview->selectedYear;
        $this->template->month = DateTime::CZ_MONTHS[$month];
        $this->setView('producerSumCheckPerStore');
    }

    protected function createComponentTakingsOverview(): TakingsOverview
    {
        return new TakingsOverview($this->orm, $this->overviewExporter);
    }
}
