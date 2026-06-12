<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Orm\Users;

use App\Core\Orm\BaseRepository;
use App\Modules\SystemModule\Orm\Roles\Role;
use Nextras\Orm\Collection\ICollection;

class UserRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [User::class];
    }

    public function isRestorable(): bool
    {
        return true;
    }

    public function getMainAdmin(): User
    {
        return $this->getById(1);
    }

    public function getByUsername(string $username): ?User
    {
        $user = $this->getBy(['username' => $username, 'deleted' => false]);

        if (!$user) {
            $user = $this->getBy(['internalLogin' => $username, 'deleted' => false]);
        }

        return $user;
    }

    public function getByToken(string $token): ?User
    {
        return $this->getBy(['token' => $token, 'tokenValidity>=' => new \DateTime(), 'deleted' => false]);
    }

    public function findDealers(): ICollection
    {
        return $this->findBy(['roles->name' => Role::DEALER, 'deleted' => false]);
    }
}
