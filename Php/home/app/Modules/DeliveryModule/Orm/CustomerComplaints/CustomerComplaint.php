<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\CustomerComplaints;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Expresion\DbColumn;
use App\Core\Orm\Traits\CreatableTrait;
use App\Core\Orm\Traits\UpdatableTrait;
use App\Core\Utils\DateTime;
use App\Modules\StockModule\Orm\StockItems\StockItem;
use App\Modules\SystemModule\Orm\Stores\Store;

/**
 * @property int                          $id               {primary}
 * @property Store                        $store            {m:1 Store, oneSided=true}
 * @property StockItem                    $item             {m:1 StockItem, oneSided=true}
 * @property string                       $name
 * @property string|null                  $company
 * @property array                        $description
 * @property string|null                  $response
 * @property int                          $state            {enum self::STATE_*} {default self::STATE_NEW}
 *
 * @property-read string                  $number           {virtual}
 * @property-read int|null                $daysLeft         {virtual}
 */
class CustomerComplaint extends BaseEntity
{
    use CreatableTrait;
    use UpdatableTrait;

    public const STATE_NEW = 1;
    public const STATE_NOTIFIED = 2;
    public const STATE_RESPONDED = 3;

    public function getterNumber(): string
    {
        return str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public function getterDaysLeft(): ?int
    {
        if ($this->state === self::STATE_RESPONDED) {
            return null;
        }

        return  30 - (int) (new \DateTime())->diff(new \DateTime($this->createdAt->format(DateTime::DB_DATE)))
            ->format('%a');
    }

    public function getSqlItem(): DbColumn
    {
        return new DbColumn('item->name');
    }
}
