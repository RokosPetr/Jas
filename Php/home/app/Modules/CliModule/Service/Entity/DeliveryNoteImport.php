<?php
declare(strict_types=1);

namespace App\Modules\CliModule\Service\Entity;

class DeliveryNoteImport
{
    public ?int $season;
    public int $number;
    public int $movementNumber;
    public int $movementType;
    public \DateTime $date;
    public string $description;
    public int $ico;
    public string $voj;
    public string $bill;
    public string $deliveryNote;
    public string $state;
    public ?int $cancelNote;
    public float $netSum;
    public float $grossSum;
    public float $taxSum;
}
