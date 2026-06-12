<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Orm\Sessions;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Traits\CreatableTrait;
use App\Core\Orm\Traits\UpdatableTrait;
use App\Modules\SystemModule\Orm\Resources\Resource;
use App\Modules\SystemModule\Orm\Users\User;
use Nextras\Dbal\Utils\DateTimeImmutable;
use Nextras\Orm\Relationships\ManyHasMany;

/**
 * @property int                       $id	         {primary}
 * @property string                    $phpsessid
 * @property DateTimeImmutable|null    $expiration
 * @property User                      $user         {m:1 User::$sessions}
 */
class Session extends BaseEntity
{
}
