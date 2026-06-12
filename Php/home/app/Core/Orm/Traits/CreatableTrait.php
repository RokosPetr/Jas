<?php
declare(strict_types=1);

namespace App\Core\Orm\Traits;

use App\Core\Orm\Expresion\DbColumn;
use App\Core\Utils\DateTime;
use App\Modules\SystemModule\Orm\Users\User;
use Nextras\Dbal\Utils\DateTimeImmutable;

/**
 * @property DateTimeImmutable           $createdAt
 * @property User                        $createdBy     {m:1 User, oneSided=true}
 *
 * @property-read string                 $created       {virtual}
 */
trait CreatableTrait
{
    public function getterCreated(): string
    {
        return $this->createdAt->format(DateTime::CZ_DATETIME) . ' (' . $this->createdBy->name . ')';
    }

    public function getSqlCreated(): DbColumn
    {
        return new DbColumn('createdAt');
    }
}
