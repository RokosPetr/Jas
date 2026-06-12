<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Orm\UserSettings;

use App\Core\Orm\BaseEntity;
use App\Modules\SystemModule\Orm\Users\User;

/**
 * @property int                       $id             {primary}
 * @property User                      $user           {m:1 User, oneSided=true}
 * @property string                    $component
 * @property array                     $setting
 */
class UserSetting extends BaseEntity
{
}
