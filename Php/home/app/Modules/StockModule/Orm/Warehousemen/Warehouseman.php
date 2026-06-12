<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\Warehousemen;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Traits\CreatableTrait;
use App\Core\Orm\Traits\DeletableTrait;
use App\Core\Orm\Traits\UpdatableTrait;

/**
 * @property int                       $id             {primary}
 * @property string                    $name
 * @property int                       $webId
 */
class Warehouseman extends BaseEntity
{
    use CreatableTrait;
    use UpdatableTrait;
    use DeletableTrait;
}
