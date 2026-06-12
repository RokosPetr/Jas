<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\WarehousemanHours;

use App\Core\Orm\BaseRepository;
use Nextras\Dbal\Utils\DateTimeImmutable;

class WarehousemanHourRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [WarehousemanHour::class];
    }

    public function loadWorkingHours(int $month, int $year): array
    {
        $workingHours = [];
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $dateFrom = (new DateTimeImmutable())->setDate($year, $month, 1)->setTime(0,0);
        $dateTo = (new DateTimeImmutable())->setDate($year, $month, $daysInMonth)->setTime(0, 0);

        foreach ($this->findBy(['date>=' => $dateFrom, 'date<=' => $dateTo])->orderBy('date') as $hours) {
            $workingHours[(int) $hours->date->format('j')] = (float) $hours->length;
        };

        return $workingHours;
    }
}
