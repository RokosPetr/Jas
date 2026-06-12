<?php
declare(strict_types = 1);

namespace App\Core\Latte;

class FloatToHourFilter
{
    use \Nette\SmartObject;

    public function __invoke(float $number): string
    {
        return floatToHour($number);
    }
}
