<?php
declare(strict_types=1);

namespace App\Modules\CliModule\Orm\Imports;

use App\Core\Orm\BaseEntity;
use Nextras\Dbal\Utils\DateTimeImmutable;

/**
 * @property int                       $id             {primary}
 * @property string                    $name
 * @property DateTimeImmutable         $date
 */
class Import extends BaseEntity
{
}
