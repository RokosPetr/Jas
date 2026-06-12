<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\StockSeries;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Expresion\DbColumn;
use App\Core\Orm\Expresion\DbCondition;
use App\Core\Orm\Expresion\DbFunction;
use App\Core\Orm\Expresion\DbString;
use App\Modules\StockModule\Orm\StockItems\StockItem;
use Nextras\Orm\Relationships\ManyHasMany;

/**
 * @property int                          $id             {primary}
 * @property string                       $name
 * @property string                       $key
 * @property ManyHasMany|StockItem[]      $items          {m:m StockItem::$series, isMain=true}
 *
 * @property-read bool                    $hasItems       {virtual}
 */
class StockSeries extends BaseEntity
{
    public const GLOBAL_SERIES = [
        'jaserie' => StockItem::GLOBAL_PRODUCER_JAS,
        'naradi' => StockItem::GLOBAL_PRODUCER_TOOLS
    ];

    public const GLOBAL_SERIES_LABELS = [
        'jaserie' => 'JA|SÉRIE',
        'naradi' => 'Nářadí'
    ];

    public function getterHasItems(): bool
    {
        return $this->items->count() > 0;
    }

    public function getSqlHasItems(): DbFunction
    {
        return new DbFunction(
            'IF',
            new DbCondition(new DbColumn('items->id'), new DbString('IS NULL')),
            new DbString('0'),
            new DbString('1')
        );
    }
}
