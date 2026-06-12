<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\Contacts;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Expresion\DbColumn;
use App\Core\Orm\Expresion\DbFunction;
use App\Core\Orm\Expresion\DbString;
use App\Core\Orm\Traits\DeletableTrait;
use App\Modules\DeliveryModule\Orm\Companies\Depot;

/**
 * @property int                            $id               {primary}
 * @property Depot                          $depot            {m:1 Depot::$contacts}
 * @property string                         $firstName
 * @property string                         $lastName
 * @property int                            $order
 * @property string                         $position
 * @property string                         $phone
 * @property string|null                    $email
 * @property string|null                    $url
 * @property string|null                    $remark
 *
 * @property string                         $name             {virtual}
 */
class Contact extends BaseEntity
{
    use DeletableTrait;

    public function getterName(): string
    {
        return "$this->firstName $this->lastName";
    }

    public function getSqlName(): DbFunction
    {
        return new DbFunction(
            'CONCAT',
            new DbColumn('firstName'),
            new DbString("' '"),
            new DbColumn('lastName')
        );
    }
}
