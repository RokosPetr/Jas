<?php
declare(strict_types=1);

namespace App\Core\Utils;

class DateTime extends \Nette\Utils\DateTime
{
    public const DB_DATE = 'Y-m-d';
    public const DB_TIME = 'H:i:s';
    public const DB_DATETIME = 'Y-m-d H:i:s';

    public const CZ_DATE = 'd.m.Y';
    public const CZ_TIME = 'H:i';
    public const CZ_DATETIME = 'd.m.Y H:i';
    public const CZ_DATETIME_WITH_SEC = 'd.m.Y H:i:s';
    public const CZ_SHORT_DATE = 'j.n.Y';
    public const CZ_SHORT_DATETIME = 'j.n.Y H:i';
    public const CZ_SHORT_DATETIME_WITH_SEC = 'j.n.Y H:i:s';

    public const JS_DATE = 'dd.mm.yy';
    public const JS_SHORT_DATE = 'd.m.yy';
    public const JS_TIME = 'HH:mm';

    public const CZ_MONTHS = [
        1 => 'leden',
        2 => 'únor',
        3 => 'březen',
        4 => 'duben',
        5 => 'květen',
        6 => 'červen',
        7 => 'červenec',
        8 => 'srpen',
        9 => 'září',
        10 => 'říjen',
        11 => 'listopad',
        12 => 'prosinec'
    ];

    public const CZ_WEEKDAYS = [
        1 => 'pondělí',
        2 => 'úterý',
        3 => 'středa',
        4 => 'čtvrtek',
        5 => 'pátek',
        6 => 'sobota',
        7 => 'neděle'
    ];

    public const CZ_HOLIDAYS = [
        '01.01', '01.05', '08.05', '05.07', '06.07', '28.09', '28.10', '17.11', '24.12', '25.12', '26.12'
    ];

    public function isWeekend(): bool
    {
        return $this->format('N') > 5;
    }

    public function isHoliday(): bool
    {
        $dateString = $this->format('d.m');
        if (in_array($dateString, self::CZ_HOLIDAYS)) {
            return true;
        }
        $easter = (new self())->setTimestamp(easter_date((int) $this->format('Y')));
        $easterDate = $easter->modify('+1 day')->format('d.m');
        $bigFridayDate = $easter->modify('-3 days')->format('d.m');
        return $dateString === $easterDate || $dateString === $bigFridayDate;
    }

    public function isWorkingDay(): bool
    {
        return !$this->isWeekend() && !$this->isHoliday();
    }

    public static function getWorkingDays(int $year, int $month, int $dayTill = null): int
    {
        $workingDayCounter = 0;
        $day = self::createFromFormat(self::CZ_SHORT_DATE, "1.$month.$year");

        while (intval($day->format('n')) === $month) {
            if ($dayTill && intval($day->format('j')) > $dayTill) {
                break;
            }
            if ($day->isWorkingDay()) {
                $workingDayCounter++;
            }
            $day->modify('+1 day');
        }

        return $workingDayCounter;
    }
}
