<?php
declare(strict_types=1);

namespace App\Core\Security;

use App\Modules\SystemModule\Orm\Users\UserRepository;
use App\Service\OrmModel;
use Nette\Security\AuthenticationException;
use Nette\Security\IIdentity;
use Nette\Security\SimpleIdentity;

final class Authenticator implements \Nette\Security\Authenticator
{
    private UserRepository $userRepository;

    public function __construct(OrmModel $orm)
    {
        $this->userRepository = $orm->users;
    }

    public function authenticate(string $user, string $password): IIdentity
    {
        $sysUser = $this->userRepository->getByUsername($user);

        if (!$sysUser) {
            throw new AuthenticationException('Špatné uživatelské jméno nebo heslo', self::IDENTITY_NOT_FOUND);
        }

        if ($sysUser->banned) {
            throw new AuthenticationException('Účet je zablokován, kontaktujte administrátora', self::NOT_APPROVED);
        }

        if (!password_verify($password, $sysUser->password)) {
            $sysUser->setIncorrectLogin();
            throw new AuthenticationException('Špatné uživatelské jméno nebo heslo', self::INVALID_CREDENTIAL);
        }

        $sysUser->setLogin();
        $roles = $sysUser->roles->toCollection()->fetchPairs('id', 'name');
        $userData = [
            'name' => $sysUser->name,
            'email' => $sysUser->email,
            'username' => $sysUser->username,
            'login' => $sysUser->internalLogin
        ];

        return new SimpleIdentity($sysUser->id, $roles, $userData);
    }
}
