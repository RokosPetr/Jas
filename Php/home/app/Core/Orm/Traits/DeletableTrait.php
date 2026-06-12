<?php
declare(strict_types = 1);

namespace App\Core\Orm\Traits;

use App\Core\Orm\Expresion\DbColumn;
use App\Core\Orm\Expresion\DbCondition;
use App\Core\Orm\Expresion\DbFunction;
use App\Core\Orm\Expresion\DbString;
use App\Core\Utils\DateTime;
use App\Modules\SystemModule\Orm\Users\User;
use Nextras\Dbal\Utils\DateTimeImmutable;

/**
 * @property bool                        $deleted       {default false}
 * @property DateTimeImmutable|null      $deletedAt
 * @property User|null                   $deletedBy     {m:1 User, oneSided=true}
 *
 * @property-read string|null            $cancelled     {virtual}
 */
trait DeletableTrait
{
    public function getterCancelled(): ?string
    {
        if (!$this->deleted) {
            return null;
        }
        return $this->deletedAt->format(DateTime::CZ_DATETIME) . ' (' . $this->deletedBy->name . ')';
    }

    public function getSqlCancelled(): DbFunction
    {
        return new DbFunction(
            'IF',
            new DbCondition(new DbColumn('deletedAt'), new DbString('IS NULL')),
            new DbFunction('NOW'),
            new DbColumn('deletedAt')
        );
    }
}
