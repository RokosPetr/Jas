<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Orm\StoreSettings;

use App\Core\Orm\BaseEntity;
use App\Modules\SystemModule\Orm\Stores\Store;

/**
 * @property int                       $id             {primary}
 * @property Store|null                $store          {m:1 Store, oneSided=true}
 * @property string                    $name
 * @property string|null               $value
 */
class StoreSetting extends BaseEntity
{
    public const CURRENT_SEASON = 'current_season';
}
