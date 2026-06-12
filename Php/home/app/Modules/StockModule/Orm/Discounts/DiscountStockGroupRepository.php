<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\Discounts;

use App\Core\Orm\BaseRepository;
use App\Modules\DeliveryModule\Orm\Companies\Depot;

class DiscountStockGroupRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [DiscountStockGroup::class];
    }

    public function loadExportData(Depot $depot, array $producers): array
    {
        $exportData = [];
        $filter = ['discountGroup->depots->id' => $depot->id];

        if ($producers) {
            $filter['stockGroup->producer->id'] = $producers;
        }

        $discounts = $this->findBy($filter)->orderBy('stockGroup->producer->number')->orderBy('stockGroup->number');

        foreach ($discounts as $discount) {
            $exportData[] = [
                $discount->producer->title,
                $discount->stockGroup->title,
                number_format($discount->value, 2, ',', ' ')
            ];
        }

        return $exportData;
    }
}
