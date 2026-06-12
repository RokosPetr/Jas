<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Orm\Phones;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Expresion\DbColumn;
use App\Modules\SystemModule\Orm\Users\User;

/**
 * @property int                       $id             {primary}
 * @property string                    $number
 * @property string                    $description
 * @property User                      $user           {m:1 User::$phones}
 *
 * @property-read string               $username       {virtual}
 */
class Phone extends BaseEntity
{
    public function getterUsername(): string
    {
        return $this->user->name;
    }

    public function getSqlUsername(): DbColumn
    {
        return new DbColumn('user->name');
    }
}
