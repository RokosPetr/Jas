<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\Stands;

use App\Core\Orm\BaseRepository;

class StandNoteRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [StandNote::class];
    }
}
