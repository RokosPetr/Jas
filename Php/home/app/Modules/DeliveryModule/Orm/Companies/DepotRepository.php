<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\Companies;

use App\Core\Orm\BaseRepository;
use App\Modules\SystemModule\Orm\Stores\Store;

/**
 * @method array loadStoreDepots(int $storeId)
 * @method array loadStorageDepots()
 */
class DepotRepository extends BaseRepository
{
    public const DEALER_FILTER = [
        'company->ico!=' => [0, Store::INTERNAL_ICO],
        'group->number>' => 0,
    ];

    public const JAS_FILTER = [
        'company' => 1,
        'store'   => 9,
        'voj>='   => 1,
        'voj<='   => 8,
    ];

    // POZOR: použít string 'OR', ne ICollection::OR
    public const DEALER_OR_JAS = [
        'OR',
        self::DEALER_FILTER,
        self::JAS_FILTER,
    ];

    public static function getEntityClassNames(): array
    {
        return [Depot::class];
    }
}
