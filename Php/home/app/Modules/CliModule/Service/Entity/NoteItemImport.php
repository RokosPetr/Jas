<?php
declare(strict_types=1);

namespace App\Modules\CliModule\Service\Entity;

class NoteItemImport
{
    public int $movementNumber;
    public int $noteNumber;
    public string $itemRegNumber;
    public string $supplement;
    public float $amount;
    public float $sellPrice;
    public float $buyPrice;
    public \DateTime $date;
    public float $discount;
    public bool $isService;
    public int $tax;
}
