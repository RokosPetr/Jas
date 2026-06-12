<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Orm\DeliveryNotes;

use App\Core\Orm\BaseRepository;

/**
 * @method array loadByCurrentSeason(int $storeId, int $season)
 * @method void deleteByCurrentSeason(int $storeId, int $season)
 * @method void deleteBySeason(int $storeId, int $season)
 * @method array loadDuplicities(int $year = null)
 * @method array loadBadTransfers(int $store)
 */
class DeliveryNoteRepository extends BaseRepository
{
    const SALES = [501, 502, 504, 506];
    const TAKINGS = [201, 202];
    const CANCELS = [401, 402, 404];
    const IN_TRANSFERS = [220, 221];
    const OUT_TRANSFERS = [520, 521];

    static function getEntityClassNames(): array
    {
        return [DeliveryNote::class];
    }

    public function getByTransportItem(int $store, int $number, int $year): ?DeliveryNote
    {
        $return = $this->getBy([
            'number' => $number,
            'store->id' => $store,
            'movementType' => [DeliveryNote::TYPE_SALE, DeliveryNote::TYPE_TRANSFER_OUT],
            'date>=' => "$year-01-01",
            'date<=' => "$year-12-31"
        ]);
        return $return;
    }

    static function getMovementType(int $movementNumber): ?int
    {
        if (in_array($movementNumber, self::SALES)) {
            return DeliveryNote::TYPE_SALE;
        }

        if (in_array($movementNumber, self::TAKINGS)) {
            return DeliveryNote::TYPE_TAKINGS;
        }

        if (in_array($movementNumber, self::CANCELS)) {
            return DeliveryNote::TYPE_CANCEL;
        }

        if (in_array($movementNumber, self::IN_TRANSFERS)) {
            return DeliveryNote::TYPE_TRANSFER_IN;
        }

        if (in_array($movementNumber, self::OUT_TRANSFERS)) {
            return DeliveryNote::TYPE_TRANSFER_OUT;
        }

        return null;
    }
}
