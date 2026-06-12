<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\DeliveryItems;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Expresion\DbColumn;
use App\Core\Orm\Expresion\DbCondition;
use App\Core\Orm\Expresion\DbFunction;
use App\Core\Orm\Expresion\DbString;
use App\Core\Orm\Traits\CreatableTrait;
use App\Core\Orm\Traits\UpdatableTrait;
use App\Core\Utils\DateTime;
use App\Modules\SystemModule\Orm\Stores\Store;
use Nextras\Dbal\Utils\DateTimeImmutable;

/**
 * @property int                          $id               {primary}
 * @property Store                        $store            {m:1 Store, oneSided=true}
 * @property int                          $number
 * @property int                          $issueYear
 * @property DateTimeImmutable|null       $dispatchStart
 * @property DateTimeImmutable|null       $dispatchEnd
 * @property string|null                  $remark
 *
 * @property-read int                     $state            {virtual}
 */
class DeliveryItem extends BaseEntity
{
    use CreatableTrait;
    use UpdatableTrait;

    public const STATE_PREPARED = 1;
    public const STATE_OPEN_DISPATCH = 2;
    public const STATE_LOADED = 3;

    public function getterState(): int
    {
        if (is_null($this->dispatchStart)) {
            return self::STATE_PREPARED;
        }
        if (is_null($this->dispatchEnd)) {
            return self::STATE_OPEN_DISPATCH;
        }
        return self::STATE_LOADED;
    }

    public function getSqlState(): DbFunction
    {
        return new DbFunction(
            'IF',
            new DbCondition(new DbColumn('dispatchStart'), new DbString('IS NULL')),
            new DbString((string) self::STATE_PREPARED),
            new DbFunction(
                'IF',
                new DbCondition(new DbColumn('dispatchEnd'), new DbString('IS NULL')),
                new DbString((string) self::STATE_OPEN_DISPATCH),
                new DbString((string) self::STATE_LOADED)
            )
        );
    }

    public function getExportDispatchStart(): string
    {
        return $this->dispatchStart ? $this->dispatchStart->format(DateTime::CZ_DATE) : '-';
    }

    public function getExportDispatchEnd(): string
    {
        return $this->dispatchEnd ? $this->dispatchEnd->format(DateTime::CZ_DATE) : '-';
    }

    public function getExportRemark(): string
    {
        return $this->remark ? str_replace(PHP_EOL, ', ', $this->remark) : '';
    }
}
