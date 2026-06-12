<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\ObligatoryItems;

use App\Core\Orm\BaseRepository;

/**
 * @method array loadProducersForFilter()
 * @method array loadSeriesForFilter()
 */
class ObligatoryItemRepository extends BaseRepository
{
    protected static int $store = 0;

    static function getEntityClassNames(): array
    {
        return [ObligatoryItem::class];
    }

    public static function setStore(int $storeId): void
    {
       self::$store = $storeId;
    }

    public static function getStore(): int
    {
        return self::$store;
    }
}
