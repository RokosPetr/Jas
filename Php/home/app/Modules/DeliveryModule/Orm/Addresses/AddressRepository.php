<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\Addresses;

use App\Core\Orm\BaseRepository;

class AddressRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [Address::class];
    }
}
