<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Orm\Roles;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Traits\CreatableTrait;
use App\Core\Orm\Traits\UpdatableTrait;
use App\Modules\SystemModule\Orm\Resources\Resource;
use App\Modules\SystemModule\Orm\Users\User;
use Nextras\Orm\Relationships\ManyHasMany;

/**
 * @property int                       $id             {primary}
 * @property string                    $name
 * @property string                    $description
 * @property ManyHasMany|Resource[]    $resources      {m:m Resource::$roles, isMain=true}
 * @property ManyHasMany|User[]        $users          {m:m User::$roles}
 */
class Role extends BaseEntity
{
    use CreatableTrait;
    use UpdatableTrait;

    public const DEALER = 'Obchodní zástupce';
}
