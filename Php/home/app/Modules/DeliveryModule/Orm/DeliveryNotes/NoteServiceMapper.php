<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\DeliveryNotes;

use App\Core\Orm\BaseMapper;

class NoteServiceMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'delivery_note_service';
}
