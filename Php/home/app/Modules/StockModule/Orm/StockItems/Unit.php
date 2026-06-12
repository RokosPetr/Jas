<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\StockItems;

use App\Core\Orm\BaseEntity;

/**
 * @property int                          $id             {primary}
 * @property string                       $name
 */
class Unit extends BaseEntity
{
    public const SQUARE_METER_UNIT = 'm2';
}
