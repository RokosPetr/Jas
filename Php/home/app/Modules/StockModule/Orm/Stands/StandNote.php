<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\Stands;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Expresion\DbColumn;
use App\Core\Orm\Expresion\DbCondition;
use App\Core\Orm\Expresion\DbFunction;
use App\Core\Orm\Expresion\DbMath;
use App\Core\Orm\Expresion\DbString;
use App\Core\Orm\Traits\CreatableTrait;
use App\Core\Orm\Traits\UpdatableTrait;
use App\Core\Utils\DateTime;
use App\Modules\DeliveryModule\Orm\Companies\Depot;
use App\Modules\DeliveryModule\Orm\DepotStandRelocations\DepotStandRelocation;
use App\Modules\SystemModule\Orm\Users\User;
use Nextras\Dbal\Utils\DateTimeImmutable;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * @property int                                $id             {primary}
 * @property Depot                              $depot          {m:1 Depot::$standNotes}
 * @property Stand                              $stand          {m:1 Stand::$standNotes}
 * @property DateTimeImmutable                  $date
 * @property int|null                           $note
 * @property DateTimeImmutable|null             $noteDate
 * @property int|null                           $invoice
 * @property string|null                        $remark
 * @property DateTimeImmutable|null             $removeDate
 * @property int|null                           $removeNote
 * @property User|null                          $removeBy       {m:1 User, oneSided=true}
 * @property OneHasMany|DepotStandRelocation[]  $relocations    {1:m DepotStandRelocation::$standNote}
 *
 * @property-read bool                          $isActive       {virtual}
 * @property-read int                           $state          {virtual}
 * @property-read string|null                   $removed        {virtual}
 * @property-read bool                          $isRelocating   {virtual}
 */
class StandNote extends BaseEntity
{
    use CreatableTrait;
    use UpdatableTrait;

    public const STATE_PREPARED = 1;
    public const STATE_DELIVERED = 2;
    public const STATE_REMOVED = 3;
    public const STATE_ACTIVE = 4;

    public function getterState(): int
    {
        if ($this->removeDate) {
            return self::STATE_REMOVED;
        }
        return $this->date < new DateTimeImmutable()
            ? self::STATE_DELIVERED
            : self::STATE_PREPARED;
    }

    public function getterRemoved(): ?string
    {
        if (!$this->removeDate || !$this->removeBy) {
            return null;
        }
        return $this->removeDate->format(DateTime::CZ_DATETIME) . ' (' . $this->removeBy->name . ')';
    }

    public function getterIsRelocating(): bool
    {
        return !is_null($this->getRelocation());
    }

    public function getterIsActive(): bool
    {
        return is_null($this->removeDate);
    }

    public function getSqlState(): DbFunction
    {
        return new DbFunction(
            'IF',
            new DbCondition(new DbColumn('removeDate'), new DbString('IS NOT NULL')),
            new DbString((string) self::STATE_REMOVED),
            new DbFunction(
                'IF',
                new DbCondition(
                    new DbMath(new DbColumn('date'), '<', new DbFunction('NOW'))
                ),
                new DbString((string) self::STATE_DELIVERED),
                new DbString((string) self::STATE_PREPARED)
            )
        );
    }

    public function getRelocation(): ?DepotStandRelocation
    {
        return $this->relocations->toCollection()->getBy(['deleted' => false]);
    }

    public function getExportDate(): string
    {
        return $this->date->format(DateTime::CZ_DATE);
    }

    public function getExportRemoveDate(): string
    {
        return $this->removeDate ? $this->removeDate->format(DateTime::CZ_DATE) : '';
    }
}
