<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\DepotStandChecks;

use App\Core\Orm\BaseEntity;
use App\Modules\StockModule\Orm\Stands\StandNote;

/**
 * @property int                          $id               {primary}
 * @property DepotStandCheck              $standCheck       {m:1 DepotStandCheck::$missingStands}
 * @property StandNote                    $standNote        {m:1 StandNote, oneSided=true}
 */
class MissingDepotStand extends BaseEntity
{
}
