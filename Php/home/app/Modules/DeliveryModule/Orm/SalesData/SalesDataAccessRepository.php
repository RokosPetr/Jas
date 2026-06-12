<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\SalesData;

use App\Core\Orm\BaseRepository;

class SalesDataAccessRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [SalesDataAccess::class];
    }

    public function loadStoreAccess(int $userId): array
    {
        $return = $this->findBy(['user->id' => $userId])->fetchPairs(null, 'store');
        return $return;
    }
}
