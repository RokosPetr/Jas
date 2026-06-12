<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\StockItems;

use App\Core\Orm\BaseRepository;

/**
 * @method array loadSales(int $itemId, \DateTimeInterface $from, \DateTimeInterface $to)
 * @method array loadCancels(int $itemId, \DateTimeInterface $from, \DateTimeInterface $to)
 * @method array loadExportData(array $filter, array $order)
 * @method array loadGroupId()
 */
class StockItemRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [StockItem::class];
    }
}
