<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\WarehousemanItems;

use App\Core\Orm\BaseRepository;
use Nextras\Dbal\Result\Row;

/**
 * @method int deleteByYear(int $year)
 * @method array loadItemsByDuration(\DateTimeInterface $from, \DateTimeInterface $to)
 */
class WarehousemanItemRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [WarehousemanItem::class];
    }

    public function loadMonthData(int $month, int $year): array
    {
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $from = (new \DateTime())->setDate($year, $month, 1)->setTime(0, 0);
        $to = (new \DateTime())->setDate($year, $month, $daysInMonth)->setTime(0, 0);
        $monthData = [];

        /** @var Row $record */
        foreach ($this->loadItemsByDuration($from, $to) as $record) {
            $id = $record->id;

            if (isset($monthData[$id])) {
                $person = $monthData[$id];
            } else {
                $person = new \stdClass();
                $person->webId = $record->webId;
                $person->name = $record->name;
                $person->days = [];
                $person->items = 0;
                $person->itemAverage = 0;
                $person->length = 0;
                $person->deleted = $record->deleted;
                $monthData[$id] = $person;
            }

            if (!$record->quantity) {
                continue;
            }

            $person->items += $record->quantity;

            if ($record->length) {
                $person->length += $record->length;
                $person->itemAverage += $record->quantity;
            }

            $day = (int) $record->date->format('j');
            $person->days[$day] = new \stdClass();
            $person->days[$day]->items = $record->quantity;
            $person->days[$day]->average = $record->length
                ? str_replace('.', ',', (string) round($record->quantity / $record->length, 2))
                : null;
        }

        return $monthData;
    }

    public function loadYearData(int $year, ?int $monthAverage = null): array
    {
        $from = (new \DateTime())->setDate($year, 1, 1)->setTime(0, 0);
        $to = (new \DateTime())->setDate($year, 12, 31)->setTime(0, 0);
        $yearData = [];

        foreach ($this->loadItemsByDuration($from, $to) as $record) {
            $id = $record->id;

            if (isset($yearData[$id])) {
                $person = $yearData[$id];
            } else {
                $person = new \stdClass();
                $person->webId = $record->webId;
                $person->name = $record->name;
                $person->months = [];
                $person->items = 0;
                $person->itemAverage = 0;
                $person->length = 0;
                $person->deleted = $record->deleted;
                $yearData[$id] = $person;
            }

            if (!$record->quantity) {
                continue;
            }

            $month = (int) $record->date->format('n');

            if (isset($person->months[$month])) {
                $monthData = $person->months[$month];
            } else {
                $monthData = new \stdClass();
                $monthData->items = 0;
                $monthData->itemAverage = 0;
                $monthData->length = 0;
                $person->months[$month] = $monthData;
            }

            $person->items += $record->quantity;
            $monthData->items += $record->quantity;

            if ($record->length) {
                $monthData->length += $record->length;
                $monthData->itemAverage += $record->quantity;

                if ($month <= $monthAverage) {
                    $person->itemAverage += $record->quantity;
                    $person->length += $record->length;
                }
            }
        }

        return $yearData;
    }
}
