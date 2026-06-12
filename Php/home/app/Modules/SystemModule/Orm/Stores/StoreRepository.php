<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Orm\Stores;

use App\Core\Orm\BaseRepository;
use App\Modules\StockModule\Orm\StockItems\StockItem;

class StoreRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [Store::class];
    }

    private array $simpleStores;

    /**
     * Seradi pobocky podle klice
     *  - Michalkovice a Hlucin prvni (velkosklady) dle druhu vyrobce, zbytek dle id
     */
    public function findOrderedStoreNames(int $producerNumber = 0): array
    {
        $stores = $this->findAll()->fetchPairs('id', 'name');
        $orderedStores = [];

        if (!$producerNumber || in_array($producerNumber, StockItem::OSTRAVA_MAIN_STORAGE_PRODUCERS)) {
            $orderedStores[Store::OSTRAVA_MAIN_STORAGE] = $stores[Store::OSTRAVA_MAIN_STORAGE];
            $orderedStores[Store::HLUCIN_MAIN_STORAGE] = $stores[Store::HLUCIN_MAIN_STORAGE];
        } else {
            $orderedStores[Store::HLUCIN_MAIN_STORAGE] = $stores[Store::HLUCIN_MAIN_STORAGE];
            $orderedStores[Store::OSTRAVA_MAIN_STORAGE] = $stores[Store::OSTRAVA_MAIN_STORAGE];
        }

        foreach ($stores as $id => $storeName) {
            if (!isset($orderedStores[$id])) {
                $orderedStores[$id] = $storeName;
            }
        }

        return $orderedStores;
    }

    public function loadStoresWithMainStorage(): array
    {
        return [Store::MAIN_STORAGE => 'Velkoobchod'] + $this->loadSimpleStores();
    }

    public function loadSimpleStores(): array
    {
        return $this->findBy(['id!=' => Store::MAIN_STORAGES])->fetchPairs('id', 'name');
    }

    public function loadSimpleStoreIds(): array
    {
        $return = $this->simpleStores ??= $this->findBy(['id!=' => Store::MAIN_STORAGES])->fetchPairs(null, 'id');
        return $return;
    }
}
