<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\Discounts;

use App\Core\Orm\BaseRepository;
use App\Modules\DeliveryModule\Orm\Companies\Depot;

class DiscountStockItemRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [DiscountStockItem::class];
    }

    public function loadExportData(Depot $depot, array $producers): array
    {
        $exportData = [];
        $filter = ['discountGroup->depots->id' => $depot->id];

        if ($producers) {
            $filter['stockItem->group->producer->id'] = $producers;
        }

        $discounts = $this->findBy($filter)->orderBy('stockItem->group->producer->number')->orderBy('stockItem->regNumber');

        foreach ($discounts as $discount) {
            $exportData[] = [
                $discount->producer->title,
                $discount->stockItem->title,
                number_format($discount->value, 2, ',', ' ')
            ];
        }

        return $exportData;
    }
}
