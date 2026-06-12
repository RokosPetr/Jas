<?php
declare(strict_types=1);

namespace App\Modules\TransportModule\Orm\Drivers;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Traits\CreatableTrait;
use App\Core\Orm\Traits\DeletableTrait;
use App\Core\Orm\Traits\UpdatableTrait;
use App\Modules\SystemModule\Orm\Users\User;
use App\Modules\TransportModule\Orm\Cars\StoreCar;
use App\Modules\TransportModule\Orm\Transports\StoreTransport;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * @property int                          $id               {primary}
 * @property string                       $name
 * @property string|null                  $phone
 * @property StoreCar|null                $car              {1:1 StoreCar::$driver}
 * @property User|null                    $user             {m:1 User, oneSided=true}
 * @property OneHasMany|StoreTransport[]  $storeTransports  {1:m StoreTransport::$driver}
 */
class StoreDriver extends BaseEntity
{
    use CreatableTrait;
    use UpdatableTrait;
    use DeletableTrait;
}
