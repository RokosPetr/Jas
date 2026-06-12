<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Orm\Resources;

use App\Core\Orm\BaseEntity;
use App\Modules\SystemModule\Orm\Roles\Role;
use Nextras\Orm\Relationships\ManyHasMany;

/**
 * @property int                    $id                 {primary}
 * @property string                 $link
 * @property string                 $description
 * @property ManyHasMany|Role[]     $roles              {m:m Role::$resources}
 */
class Resource extends BaseEntity
{
}
