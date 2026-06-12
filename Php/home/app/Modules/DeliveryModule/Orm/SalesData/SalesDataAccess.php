<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\SalesData;

use App\Core\Orm\BaseEntity;
use App\Modules\SystemModule\Orm\Users\User;

/**
 * @property int                          $id               {primary}
 * @property User                         $user             {m:1 User, oneSided=true}
 * @property int                          $store
 */
class SalesDataAccess extends BaseEntity
{
    public const DATA_UPDATE_NOTIFICATION = 200;
}
