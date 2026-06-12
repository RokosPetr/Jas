<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\StockItems;

use App\Core\Orm\BaseRepository;
use Nextras\Orm\Collection\ICollection;

/**
 * @method array loadStoreVariants(int $storeId)
 * @method array loadStoreOutlets(int $storeId)
 */
class StockVariantRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [StockVariant::class];
    }

    public function findByItemPerStore(int $itemId): array
    {
        $variants = [];
        $collection = $this->findBy(['item->id' => $itemId, 'deleted' => false])
            ->orderBy('quantity', ICollection::DESC);

        foreach ($collection as $variant) {
            if (!$variant->quantity) {
                continue;
            }

            if (!isset($variants[$variant->store->id])) {
                $variants[$variant->store->id] = [];
            }

            $variants[$variant->store->id][] = $variant;
        }

        return $variants;
    }
}
