<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\Services;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Expresion\DbColumn;
use App\Modules\DeliveryModule\Orm\DeliveryNotes\NoteService;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * @property int                          $id               {primary}
 * @property int                          $regNumber
 * @property string                       $name
 * @property ServiceGroup                 $group            {m:1 ServiceGroup, oneSided=true}
 * @property OneHasMany|NoteService[]     $noteServices     {1:m NoteService::$service}
 *
 * @property-read string                  $groupName        {virtual}
 */
class Service extends BaseEntity
{
    public function getterGroupName(): string
    {
        return $this->group->name;
    }

    public function getSqlGroup(): DbColumn
    {
        return new DbColumn('group->id');
    }
}
