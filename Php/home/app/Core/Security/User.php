<?php
declare(strict_types=1);

namespace App\Core\Security;

use Nette\Security\UserStorage;

final class User extends \Nette\Security\User
{
    public const ADMINISTRATOR = 'Administrator';
    public const SUPER_ADMIN_RESOURCE = ':System:Store:select';
    public const STORE_MANAGER = 'Vedoucí prodejny';
    public const OZ_MANAGER = 'Vedoucí OZ';

    public function __construct(
        UserStorage $storage,
        Authenticator $authenticator = null,
        Authorizator $authorizator = null
    ) {
        parent::__construct(null, $authenticator, $authorizator, $storage);
    }

    public function isAllowed($resource = Authorizator::ALL, $privilege = Authorizator::ALL): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (!$this->getAuthorizator()->hasResource($resource ?? '')) {
            return false;
        }

        return parent::isAllowed($resource, $privilege);
    }

    public function isAdmin(): bool
    {
        return $this->isInRole(self::ADMINISTRATOR);
    }

    public function isManager(): bool
    {
        return $this->isInRole(self::STORE_MANAGER) or $this->isInRole(self::OZ_MANAGER);
    }

    public function isSuperAdmin(): bool
    {
        return $this->isAllowed(self::SUPER_ADMIN_RESOURCE);
    }
}
