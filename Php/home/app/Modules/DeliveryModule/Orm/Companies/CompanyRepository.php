<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\Companies;

use App\Core\Orm\BaseRepository;

class CompanyRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [Company::class];
    }
}
