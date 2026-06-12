<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\Companies;

use App\Core\Orm\BaseEntity;
use App\Modules\SystemModule\Orm\Users\User;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * @property int                            $id               {primary}
 * @property int                            $number
 * @property User|null                      $dealer           {m:1 User, oneSided=true}
 * @property OneHasMany|Depot[]             $depots           {1:m Depot::$group}
 *
 * @property-read string                    $numberString     {virtual}
 */
class Group extends BaseEntity
{
    public function getterNumberString(): string
    {
        return str_pad((string) $this->number, 2, '0', STR_PAD_LEFT);
    }
}
