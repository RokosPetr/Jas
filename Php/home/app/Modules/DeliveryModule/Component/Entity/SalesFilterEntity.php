<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Component\Entity;

use App\Modules\StockModule\Orm\CustomGroups\CustomGroup;
use App\Modules\StockModule\Orm\Producers\Producer;
use App\Service\OrmModel;

class SalesFilterEntity
{
    /** @var int[] */
    public array $dealers;
    /** @var int[] */
    public array $years;
    public int $month;
    /** @var int[] */
    public array $producers;

    private OrmModel $orm;
    public ?CustomGroup $stockGroup;
    public int $company;
    public int $depot;
    public int $series;
    public int $item;
    public int $store;
    public int $oz = 0;

    public function __construct(
        OrmModel $orm,
        int $store,
        array $dealers,
        array $years,
        int $month,
        array $producers,
        ?CustomGroup $stockGroup,
        int $company = 0,
        int $depot = 0,
        int $series = 0,
        int $item = 0
    ) {
        $this->orm = $orm;
        $this->dealers = array_map('intval', $dealers);
        $this->years = array_map('intval', $years);
        $this->month = $month;
        $this->producers = array_map('intval', $producers);
        $this->stockGroup = $stockGroup;
        $this->company = $company;
        $this->depot = $depot;
        $this->series = $series;
        $this->item = $item;

        if ($store > 100) {
            $this->store = intval(substr((string) $store, 0, 1));
            $this->oz = intval(substr((string) $store, -1));
        } else {
            $this->store = $store;
        }
    }

    public function isValidForData(): bool
    {
        return $this->years && ($this->producers || !$this->stockGroup);
    }

    public function getStockGroupFilter(int $producerId): array
    {
        if (!$this->stockGroup) {
            return [];
        }

        if ($producerId === Producer::DC_RAVAK_ID) {
            // DC Ravak hack
            $return = $this->orm->stockGroups->findDcRavakGroups()->fetchPairs(null, 'id');
            return $return;
        }

        return $this->stockGroup->stockGroups->toCollection()->findBy([
            'producer->id' => $producerId
        ])->fetchPairs(null, 'id');
    }

    public function getStockGroups(): array
    {
        $stockGroups = [];

        if ($this->stockGroup) {
            foreach ($this->producers as $producer) {
                $stockGroups = array_merge($stockGroups, $this->getStockGroupFilter($producer));
            }
        } else {
            foreach ($this->orm->customGroups->findAll() as $customGroup) {
                $stockGroups = array_merge($stockGroups, $customGroup->stockGroups->getRawValue());
            }
            $stockGroups = array_merge(
                $stockGroups,
                $this->orm->stockGroups->findDcRavakGroups()->fetchPairs(null, 'id')
            );
        }

        return $stockGroups;
    }

    public function isSimpleStoreFilter(): bool
    {
        return !$this->company && !$this->series && !$this->item && !$this->oz;
    }

    public function getDepotVoj(): ?string
    {
        if (!$this->depot) {
            return null;
        }

        return $this->orm->companyDepots->getById($this->depot)->voj ?? null;
    }
}