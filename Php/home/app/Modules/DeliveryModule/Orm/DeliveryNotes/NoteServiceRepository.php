<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\DeliveryNotes;

use App\Core\Orm\BaseRepository;

class NoteServiceRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [NoteService::class];
    }
}
