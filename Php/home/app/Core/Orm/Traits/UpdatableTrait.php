<?php
declare(strict_types = 1);

namespace App\Core\Orm\Traits;

use App\Core\Utils\DateTime;
use App\Modules\SystemModule\Orm\Users\User;
use Nextras\Dbal\Utils\DateTimeImmutable;

/**
 * @property DateTimeImmutable|null      $updatedAt
 * @property User|null                   $updatedBy     {m:1 User, oneSided=true}
 *
 * @property-read string|null            $updated       {virtual}
 */
trait UpdatableTrait
{
    public function getterUpdated(): ?string
    {
        if (!$this->updatedAt || !$this->updatedBy) {
            return null;
        }
        return $this->updatedAt->format(DateTime::CZ_DATETIME) . ' (' . $this->updatedBy->name . ')';
    }
}
