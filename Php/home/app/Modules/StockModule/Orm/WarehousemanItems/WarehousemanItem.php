<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\WarehousemanItems;

use App\Core\Orm\BaseEntity;
use Nextras\Dbal\Utils\DateTimeImmutable;

/**
 * @property int                       $id             {primary}
 * @property int                       $webId
 * @property int                       $quantity
 * @property DateTimeImmutable         $date
 */
class WarehousemanItem extends BaseEntity
{
}
