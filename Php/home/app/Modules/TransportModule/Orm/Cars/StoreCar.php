<?php
declare(strict_types=1);

namespace App\Modules\TransportModule\Orm\Cars;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Traits\CreatableTrait;
use App\Core\Orm\Traits\DeletableTrait;
use App\Core\Orm\Traits\UpdatableTrait;
use App\Core\Utils\DateTime;
use App\Modules\SystemModule\Orm\Stores\Store;
use App\Modules\TransportModule\Orm\Drivers\StoreDriver;
use App\Modules\TransportModule\Orm\Transports\StoreTransport;
use Nextras\Orm\Relationships\ManyHasMany;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * @property int                          $id                  {primary}
 * @property string                       $licensePlate
 * @property int                          $weightCapacity
 * @property StoreDriver|null             $driver              {1:1 StoreDriver::$car, isMain=true}
 * @property Store|null                   $homeStore           {m:1 Store, oneSided=true}
 * @property ManyHasMany|Store[]          $stores              {m:m Store::$cars, isMain=true}
 * @property OneHasMany|StoreTransport[]  $storeTransports     {1:m StoreTransport::$car}
 *
 * @property-read string                  $title            {virtual}
 */
class StoreCar extends BaseEntity
{
    use CreatableTrait;
    use UpdatableTrait;
    use DeletableTrait;

    public const DAY_TIME_FUND = 8;

    public function getterTitle(): string
    {
        return $this->homeStore
            ? $this->homeStore->name . " ($this->licensePlate)"
            : $this->licensePlate;
    }

    public function loadOccupancy(int $month, int $year): int
    {
        $currentYear = intval(date('Y'));
        $currentMonth = intval(date('n'));
        $currentDay = intval(date('j'));
        $isCurrMonth = $year === $currentYear && $month === $currentMonth;

        if (
            $year > $currentYear
            || ($year === $currentYear && $month > $currentMonth)
            || ($isCurrMonth && $currentDay === 1)
        ) {
            return 0;
        }

        $date = DateTime::createFromFormat(DateTime::CZ_SHORT_DATE, "1.$month.$year");
        $occupancy = 0;
        $transports = $this->storeTransports->toCollection()->findBy([
            'date>=' => $date->format(DateTime::DB_DATE),
            'date<=' => $isCurrMonth
                ? (new DateTime())->modify('-1 day')->format(DateTime::DB_DATE)
                : $date->modify('last day of this month')->format(DateTime::DB_DATE),
            'type' => StoreTransport::TYPE_TRANSPORT,
            'deleted' => false
        ])->fetchAll();

        foreach ($transports as $transport) {
            $occupancy += ($transport->timeTill - $transport->timeFrom);
        }

        return (int) $occupancy;
    }

    public function loadTimeFund(int $month, int $year): int
    {
        $currentYear = intval(date('Y'));
        $currentMonth = intval(date('n'));
        $currentDay = intval(date('j'));
        $isCurrMonth = $year === $currentYear && $month === $currentMonth;

        if (
            $year > $currentYear
            || ($year === $currentYear && $month > $currentMonth)
            || ($isCurrMonth && $currentDay === 1)
        ) {
            return 0;
        }

        $dayTill = $isCurrMonth ? ($currentDay - 1) : null;
        $timeFund = DateTime::getWorkingDays($year, $month, $dayTill) * self::DAY_TIME_FUND;
        $date = DateTime::createFromFormat(DateTime::CZ_SHORT_DATE, "1.$month.$year");
        $unavailableTransports = $this->storeTransports->toCollection()->findBy([
            'date>=' => $date->format(DateTime::DB_DATE),
            'date<=' => $dayTill
                ? (new DateTime())->modify('-1 day')->format(DateTime::DB_DATE)
                : $date->modify('last day of this month')->format(DateTime::DB_DATE),
            'type' => StoreTransport::TYPE_UNAVAILABILITY,
            'deleted' => false
        ])->fetchAll();

        foreach ($unavailableTransports as $transport) {
            $unavailableTime = $transport->timeTill - $transport->timeFrom;
            $timeFund -= ($unavailableTime > self::DAY_TIME_FUND) ? self::DAY_TIME_FUND : $unavailableTime;
        }

        return (int) $timeFund;
    }
}
