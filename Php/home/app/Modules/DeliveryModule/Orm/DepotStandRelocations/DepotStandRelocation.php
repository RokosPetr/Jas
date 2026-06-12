<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\DepotStandRelocations;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Expresion\DbColumn;
use App\Core\Orm\Expresion\DbCondition;
use App\Core\Orm\Expresion\DbFunction;
use App\Core\Orm\Expresion\DbMath;
use App\Core\Orm\Expresion\DbString;
use App\Core\Orm\Traits\CreatableTrait;
use App\Core\Orm\Traits\DeletableTrait;
use App\Core\Orm\Traits\UpdatableTrait;
use App\Modules\DeliveryModule\Orm\Companies\Depot;
use App\Modules\StockModule\Orm\Stands\Stand;
use App\Modules\StockModule\Orm\Stands\StandNote;

/**
 * @property int                            $id               {primary}
 * @property StandNote                      $standNote        {m:1 StandNote::$relocations}
 * @property Depot|null                     $target           {m:1 Depot, oneSided=true}
 * @property string|null                    $remark
 *
 * @property-read bool                      $hasTarget        {virtual}
 * @property-read Depot                     $depot            {virtual}
 * @property-read Stand                     $stand            {virtual}
 * @property-read int                       $standId          {virtual}
 * @property-read int                       $state            {virtual}
 */
class DepotStandRelocation extends BaseEntity
{
    use CreatableTrait;
    use UpdatableTrait;
    use DeletableTrait;

    public const STATE_ACTIVE = 1;
    public const STATE_RELOCATED = 2;
    public const STATE_DELETED = 3;

    public function getterDepot(): Depot
    {
        return $this->standNote->depot;
    }

    public function getterHasTarget(): bool
    {
        return !is_null($this->target);
    }

    public function getSqlDepot(): DbFunction
    {
        return new DbFunction(
            'CONCAT_WS',
            new DbColumn('standNote->depot->company->name'),
            new DbString("' '"),
            new DbColumn('standNote->depot->title')
        );
    }

    public function getSqlTarget(): DbFunction
    {
        return new DbFunction(
            'CONCAT_WS',
            new DbColumn('target->company->name'),
            new DbString("' '"),
            new DbColumn('target->title')
        );
    }

    public function getterStand(): Stand
    {
        return $this->standNote->stand;
    }

    public function getSqlStand(): DbFunction
    {
        return new DbFunction(
            'CONCAT_WS',
            new DbColumn('standNote->stand->name'),
            new DbString("' '"),
            new DbColumn('standNote->stand->code')
        );
    }

    public function getterStandId(): int
    {
        return $this->stand->id;
    }

    public function getterState(): int
    {
        if ($this->deleted) {
            return self::STATE_DELETED;
        }
        return $this->standNote->isActive
            ? self::STATE_ACTIVE
            : self::STATE_RELOCATED;
    }

    public function getSqlState(): DbFunction
    {
        return new DbFunction(
            'IF',
            new DbCondition(new DbMath(new DbColumn('deleted'), '=', new DbString('1'))),
            new DbString((string) self::STATE_DELETED),
            new DbFunction(
                'IF',
                new DbCondition(new DbColumn('standNote->removeDate'), new DbString('IS NULL')),
                new DbString((string) self::STATE_ACTIVE),
                new DbString((string) self::STATE_RELOCATED)
            )
        );
    }
}
