<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\DeliveryNotes;

use App\Core\Orm\BaseRepository;

/**
 * @method array loadTakingsPriceData(array $stockGroups, \DateTimeInterface $from, \DateTimeInterface $till)
 * @method array loadTakingsPriceDataPerProducer(array $producers, array $stockGroups, \DateTimeInterface $from, \DateTimeInterface $till)
 * @method array loadStoreTakingsData(array $stockGroups, \DateTimeInterface $from, \DateTimeInterface $till)
 * @method array loadStoreTakingsDataPerProducer(array $producers, array $stockGroups, \DateTimeInterface $from, \DateTimeInterface $till)
 * @method array loadSquareMetersTakingsData(array $stockGroups, \DateTimeInterface $from, \DateTimeInterface $till)
 * @method array loadSquareMetersTakingsDataPerProducer(array $producers, array $stockGroups, \DateTimeInterface $from, \DateTimeInterface $till)
 * @method array loadEmptyPalettesTakingsData(\DateTimeInterface $from, \DateTimeInterface $till)
 * @method array loadEmptyPalettesTakingsDataPerProducer(array $producers, \DateTimeInterface $from, \DateTimeInterface $till)
 */
class NoteItemRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [NoteItem::class];
    }
}
