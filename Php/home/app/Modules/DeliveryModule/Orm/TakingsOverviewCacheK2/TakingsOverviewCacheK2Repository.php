<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\TakingsOverviewCacheK2;

use App\Core\Orm\BaseRepository;
use App\Modules\DeliveryModule\Component\TakingsOverview;
use App\Modules\StockModule\Orm\CustomGroups\CustomGroup;

class TakingsOverviewCacheK2Repository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [TakingsOverviewCacheK2::class];
    }

    public function loadSumTakingsData(int $year): array
    {
        $takings = [];
        $sumTakingsData = $this->findBy([
            'type' => TakingsOverviewCacheK2::TYPE_STORE_TAKINGS,
            'year' => $year,
            'group' => CustomGroup::VIEW_TYPE_TAKINGS_SUM,
            'store' => [9, 10]
        ])->orderBy('producer');

        foreach ($sumTakingsData as $cacheEntity) {
            $viewType = $cacheEntity->store === 9
                ? TakingsOverview::VIEW_BY_PRICE
                : TakingsOverview::VIEW_BY_UNIT;
            $takings[$viewType][$cacheEntity->month][$cacheEntity->producer] = $cacheEntity->value;
        }

        return $takings;
    }

    public function loadTotalSumTakingsData(): array
    {
        $takings = [];

        for ($year = 2003; $year <= intval(date('Y')); $year++) {
            $yearTakingsData = $this->findBy([
                'type' => TakingsOverviewCacheK2::TYPE_STORE_TAKINGS,
                'year' => $year,
                'group' => CustomGroup::VIEW_TYPE_TAKINGS_SUM,
                'store' => [9, 10]
            ])->orderBy('producer');

            foreach ($yearTakingsData as $cacheEntity) {
                $viewType = $cacheEntity->store === 9
                    ? TakingsOverview::VIEW_BY_PRICE
                    : TakingsOverview::VIEW_BY_UNIT;

                if (!isset($takings[$viewType][$year][$cacheEntity->producer])) {
                    $takings[$viewType][$year][$cacheEntity->producer] = 0;
                }

                $takings[$viewType][$year][$cacheEntity->producer] += $cacheEntity->value;
            }
        }

        return $takings;
    }

    public function loadStoreTakingsData(int $year, int $group): array
    {
        $takings = [];
        $storeTakingsData = $this->findBy([
            'type' => TakingsOverviewCacheK2::TYPE_STORE_TAKINGS,
            'year' => $year,
            'group' => $group
        ])->orderBy('producer');

        foreach ($storeTakingsData as $cacheEntity) {
            if (!isset($takings['sum'][$cacheEntity->producer][$cacheEntity->store])) {
                $takings['sum'][$cacheEntity->producer][$cacheEntity->store] = 0;
            }

            $takings['sum'][$cacheEntity->producer][$cacheEntity->store] += $cacheEntity->value;
            $takings[$cacheEntity->month][$cacheEntity->producer][$cacheEntity->store] = $cacheEntity->value;
        }

        return $takings;
    }

    public function loadTotalStoreTakingsData(int $group): array
    {
        $takings = [];

        for ($year = 2011; $year <= intval(date('Y')); $year++) {
            $storeTakingsData = $this->findBy([
                'type' => TakingsOverviewCacheK2::TYPE_STORE_TAKINGS,
                'year' => $year,
                'group' => $group
            ])->orderBy('producer');

            foreach ($storeTakingsData as $cacheEntity) {
                if (!isset($takings[$year][$cacheEntity->producer][$cacheEntity->store])) {
                    $takings[$year][$cacheEntity->producer][$cacheEntity->store] = 0;
                }

                $takings[$year][$cacheEntity->producer][$cacheEntity->store] += $cacheEntity->value;
            }
        }

        return $takings;
    }
}
