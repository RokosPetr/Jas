<?php
declare(strict_types=1);

namespace App\Core\Orm\Traits;

use App\Core\Utils\DateTime;
use App\Modules\SystemModule\Orm\Users\User;
use Nextras\Dbal\Utils\DateTimeImmutable;

/**
 * @property DateTimeImmutable|null      $lockedAt
 * @property User|null                   $lockedBy      {m:1 User, oneSided=true}
 *
 * @property-read string|null            $locked        {virtual}
 * @property-read bool                   $isLocked      {virtual}
 * @property-read bool                   $isSelfLocked  {virtual}
 */
trait LockableTrait
{
    public function getterIsLocked(): bool
    {
        // Zamek ma platnost 3 minuty
        $lockValidity = 180;
        return $this->lockedBy
            && $this->lockedAt
            && $this->lockedAt->modify("+$lockValidity seconds") > new DateTimeImmutable();
    }

    public function getterIsSelfLocked(): bool
    {
        return $this->lockedBy === $this->getSysUser();
    }

    public function getterLocked(): ?string
    {
        return $this->isLocked
            ? $this->lockedAt->format(DateTime::CZ_DATETIME) . ' (' . $this->lockedBy->name . ')'
            : null;
    }

    public function createLock(): void
    {
        $this->lockedBy = $this->getSysUser();
        $this->lockedAt = new DateTimeImmutable();
    }

    public function updateLock(): void
    {
        $this->lockedAt = new DateTimeImmutable();
    }

    public function unlock(): void
    {
        $this->lockedBy = null;
        $this->lockedAt = null;
    }
}
