<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\MainStorageOrders;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Expresion\DbColumn;
use App\Core\Orm\Expresion\DbFunction;
use App\Core\Orm\Expresion\DbString;
use App\Core\Orm\Traits\CreatableTrait;
use App\Core\Orm\Traits\UpdatableTrait;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * @property int                                $id             {primary}
 * @property int                                $year
 * @property int                                $month
 * @property int                                $number
 * @property int                                $state          {enum self::STATE_*} {default self::STATE_NEW}
 * @property OneHasMany|MainStorageOrderItem[]  $items          {1:m MainStorageOrderItem::$order, cascade=[persist, remove]}
 *
 * @property-read string                        $numberLabel    {virtual}
 * @property-read string                        $producer       {virtual}
 */
class MainStorageOrder extends BaseEntity
{
    use CreatableTrait;
    use UpdatableTrait;

    public const STATE_NEW = 1;
    public const STATE_PARTLY_STOCKED = 2;
    public const STATE_COMPLETELY_STOCKED = 3;
    public const STATE_NOT_STOCKED = 12;

    public function getterNumberLabel(): string
    {
        return str_pad((string) $this->number, 2, '0' , STR_PAD_LEFT)
            . '-'
            . str_pad((string) $this->month, 2, '0' , STR_PAD_LEFT)
            . '-'
            . $this->year;
    }

    public function getSqlNumberLabel(): DbFunction
    {
        return new DbFunction(
            'CONCAT',
            new DbColumn('year'),
            new DbFunction('LPAD', new DbColumn('month'), new DbString('2'), new DbString('0')),
            new DbFunction('LPAD', new DbColumn('number'), new DbString('2'), new DbString('0'))
        );
    }

    public function getterProducer(): string
    {
        $producers = [];

        foreach ($this->items as $item) {
            if (!isset($producers[$item->producer])) {
                $producers[$item->producer] = $item->producer;
            }
        }

        return $producers ? implode(', ', $producers) : '-';
    }
}
