<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\WarehousemanHours;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Traits\CreatableTrait;
use App\Core\Orm\Traits\UpdatableTrait;
use Nextras\Dbal\Utils\DateTimeImmutable;

/**
 * @property int                       $id             {primary}
 * @property DateTimeImmutable         $date
 * @property float                     $length
 */
class WarehousemanHour extends BaseEntity
{
    use CreatableTrait;
    use UpdatableTrait;
}
