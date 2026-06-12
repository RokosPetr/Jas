<?php
declare(strict_types=1);

namespace App\Modules\CliModule\Service\Entity;

use App\Modules\StockModule\Orm\StockGroups\StockGroup;
use App\Modules\StockModule\Orm\StockItems\StockVariant;

class StoreStockImport
{
    public string $regNumber;
    public string $name;
    public ?string $catalog;
    public string $supplement;
    public ?string $remark;
    public int $producer;
    public int $group;
    public float $quantity;
    public float $paletteQuantity;
    public string $unit;
    public int $weight;
    public float $price;
    public ?string $sector;

    public function getOutletType(): ?int
    {
        if ($this->group === StockGroup::TILES_OUTLET) {
            return StockVariant::OUTLET_TILES;
        }
        return in_array($this->group, StockGroup::SANITARY_OUTLET)
            ? StockVariant::OUTLET_SANITARY
            : null;
    }
}
