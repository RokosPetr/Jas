<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\StockSectors;

use App\Core\Orm\BaseEntity;
use App\Modules\StockModule\Orm\StockItems\StockVariant;
use App\Modules\SystemModule\Orm\Stores\Store;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * @property int                          $id               {primary}
 * @property Store                        $store            {m:1 Store, oneSided=true}
 * @property string                       $name
 * @property OneHasMany|StockVariant[]    $variants         {1:m StockVariant::$sector}
 */
class StockSector extends BaseEntity
{
}
