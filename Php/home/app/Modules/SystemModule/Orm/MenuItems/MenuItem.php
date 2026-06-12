<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Orm\MenuItems;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Traits\CreatableTrait;
use App\Core\Orm\Traits\UpdatableTrait;

/**
 * @property int                       $id             {primary}
 * @property string                    $name
 * @property string                    $module
 * @property string                    $presenter
 * @property string                    $action
 * @property int                       $order
 * @property string|null               $icon
 * @property bool                      $active
 *
 * @property-read string               $link           {virtual}
 */
class MenuItem extends BaseEntity
{
    use CreatableTrait;
    use UpdatableTrait;

    public function getterLink(): string
    {
        return ":$this->module:$this->presenter:$this->action";
    }
}
