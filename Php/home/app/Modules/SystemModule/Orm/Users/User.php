<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Orm\Users;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Traits\CreatableTrait;
use App\Core\Orm\Traits\DeletableTrait;
use App\Core\Orm\Traits\UpdatableTrait;
use App\Modules\DeliveryModule\Orm\Companies\Depot;
use App\Modules\SystemModule\Orm\Mails\Mail;
use App\Modules\SystemModule\Orm\Phones\Phone;
use App\Modules\SystemModule\Orm\Roles\Role;
use App\Modules\SystemModule\Orm\Sessions\Session;
use App\Modules\SystemModule\Orm\Stores\Store;
use Nextras\Dbal\Utils\DateTimeImmutable;
use Nextras\Orm\Relationships\ManyHasMany;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * @property int                       $id                 {primary}
 * @property string                    $name
 * @property string                    $email
 * @property string                    $username
 * @property string                    $password
 * @property string|null               $internalLogin
 * @property DateTimeImmutable|null    $lastLogin
 * @property bool                      $banned             {default false}
 * @property int                       $incorrectLogins    {default 0}
 * @property int                       $loginCounter       {default 0}
 * @property string|null               $token
 * @property DateTimeImmutable|null    $tokenValidity
 * @property Store|null                $store              {m:1 Store::$users}
 * @property ManyHasMany|Role[]        $roles              {m:m Role::$users, isMain=true}
 * @property OneHasMany|Phone[]        $phones             {1:m Phone::$user}
 * @property OneHasMany|Mail[]         $sentMails          {1:m Mail::$user}
 * @property ManyHasMany|Depot[]       $depots             {m:m Depot::$dealers}
 * @property OneHasMany|Session[]      $sessions           {1:m Session::$user}
 *
 * @property-read bool                 $hasSentMails       {virtual}
 */
class User extends BaseEntity
{
    use CreatableTrait;
    use UpdatableTrait;
    use DeletableTrait;

    public function setIncorrectLogin(): void
    {
        $this->incorrectLogins++;

        if ($this->incorrectLogins > 4) {
            $this->banned = true;
        }

        $this->getRepository()->persistAndFlush($this);
    }

    public function setLogin(): void
    {
        $this->incorrectLogins = 0;
        $this->lastLogin = new DateTimeImmutable();
        $this->loginCounter++;
        $this->getRepository()->persistAndFlush($this);
    }

    public function createToken(): string
    {
        $this->token = generateRandomString(30);
        $this->tokenValidity = (new DateTimeImmutable())->modify('+2 hours');
        $this->getRepository()->persistAndFlush($this);
        return $this->token;
    }

    public function getterHasSentMails(): bool
    {
        return $this->sentMails->count() > 0;
    }
}
