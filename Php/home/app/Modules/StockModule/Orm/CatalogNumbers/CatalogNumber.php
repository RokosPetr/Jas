<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\CatalogNumbers;

use App\Core\Orm\BaseEntity;
use App\Modules\StockModule\Orm\StockItems\StockItem;
use App\Modules\StockModule\Orm\StockItems\StockVariant;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * @property int                          $id          {primary}
 * @property string                       $name
 * @property StockItem                    $item        {m:1 StockItem::$catalogs}
 * @property OneHasMany|StockVariant[]    $variants    {1:m StockVariant::$catalog}
 */
class CatalogNumber extends BaseEntity
{
}
