<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\CustomerComplaints;

use App\Core\Orm\BaseRepository;

class CustomerComplaintRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [CustomerComplaint::class];
    }
}
