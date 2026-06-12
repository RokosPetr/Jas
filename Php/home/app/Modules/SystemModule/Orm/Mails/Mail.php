<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Orm\Mails;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Expresion\DbColumn;
use App\Modules\SystemModule\Orm\Users\User;
use Nextras\Dbal\Utils\DateTimeImmutable;

/**
 * @property int                       $id             {primary}
 * @property User                      $user           {m:1 User::$sentMails}
 * @property string                    $subject
 * @property string                    $body
 * @property DateTimeImmutable         $sentAt         {default now}
 */
class Mail extends BaseEntity
{
    public function getSqlName(): DbColumn
    {
        return new DbColumn('user->name');
    }
}
