<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\CatalogNumbers;

use App\Core\Orm\BaseRepository;

/**
 * @method array loadCatalogNumbers()
 */
class CatalogNumberRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [CatalogNumber::class];
    }
}
