<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\DepotStandChecks;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Traits\CreatableTrait;
use App\Modules\DeliveryModule\Orm\Companies\Depot;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * @property int                            $id               {primary}
 * @property Depot                          $depot            {m:1 Depot::$standChecks}
 * @property string|null                    $remark
 * @property OneHasMany|MissingDepotStand[] $missingStands    {1:m MissingDepotStand::$standCheck}
 */
class DepotStandCheck extends BaseEntity
{
    use CreatableTrait;
}
