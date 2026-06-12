<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Component;

use App\Modules\DeliveryModule\Component\Entity\SalesFilterEntity;
use App\Modules\StockModule\Orm\Producers\Producer;
use App\Service\OrmModel;
use Nette\Application\UI\Control;

abstract class BaseOverview extends Control
{
    protected OrmModel $orm;
    protected SalesFilterEntity $filter;
    protected array $stockGroupsCache = [];

    public function __construct(OrmModel $orm)
    {
        $this->orm = $orm;
    }

    abstract protected function createComponentOverviewFilter(): BaseOverviewFilter;
    abstract public function loadCellValue(int $producer, int $year, int $month): int;
    abstract public function loadCellTotalValue(int $stockGroup, int $year, int $month): int;

    public function render(): void
    {
        $this->filter = $this['overviewFilter']->getDataFilter();
        if ($this->filter->stockGroup) {
            $producers = $this->orm->producers->findBy(['id' => $this->filter->producers])->fetchPairs('id');
            $this->template->producers = [];
            $this->template->producerColors = [];

            foreach ($this->filter->producers as $producerId) {
                if ($producerId === Producer::DC_RAVAK_ID) {
                    $this->template->producers[$producerId] = 'DC Ravak';
                    $this->template->producerColors[] = '#d3041b';
                } else {
                    $producer = $producers[$producerId];
                    $this->template->producers[$producerId] = $producer->name;
                    $this->template->producerColors[] = $producer->color;
                }
            }

            $this->template->setFile(__DIR__ . '/templates/baseOverview.latte');
        } else {
            $this->template->stockGroups = $this->orm->customGroups->findAll()->fetchPairs('id', 'name');
            $this->template->setFile(__DIR__ . '/templates/baseTotalOverview.latte');
        }

        $this->template->years = $this->filter->years;
        $this->template->hasCurrentYear = in_array(date('Y'), $this->filter->years);
        $this->template->render();
    }

    public function loadColumns(): array
    {
        $filter = $this->getDataFilter();
        
        if (!$filter->stockGroup) {
            return $this->orm->customGroups->findAll()->fetchPairs('id', 'name');
        }

        $producers = $this->orm->producers->findBy(['id' => $this->filter->producers])->fetchPairs('id');
        $columns = [];

        foreach ($filter->producers as $producerId) {
            if ($producerId === Producer::DC_RAVAK_ID) {
                $columns[$producerId] = 'DC Ravak';
            } else {
                $columns[$producerId] = $producers[$producerId]->name;
            }
        }

        return $columns;
    }

    public function isTotalValue(): bool
    {
        return !$this->getDataFilter()->stockGroup;
    }

    public function getDataFilter(): SalesFilterEntity
    {
        return $this->filter ??= $this['overviewFilter']->getDataFilter();
    }

    public function getYearCompareSums(array $producerSales): array
    {
        krsort($producerSales);

        if (!in_array((int) date('Y'), $this->filter->years)) {
            // porovnavaji se sumy za cele roky
            return array_map(fn(array $producerYearData) => array_sum($producerYearData), $producerSales, []);
        }

        $currentMonth = (int) date('n');

        return array_map(
            fn(array $producerYearData) => array_sum(
                array_filter(
                    $producerYearData,
                    fn(int $month) => $month < $currentMonth,
                    ARRAY_FILTER_USE_KEY
                )
            ),
            $producerSales,
            []
        );
    }

    protected function getStockGroups($groupId): array
    {
        if (!isset($this->stockGroupsCache[$groupId])) {
            $group = $this->orm->customGroups->getById($groupId);
            $ravakId = $this->orm->producers->getBy(['name' => Producer::RAVAK_NAME])->id ?? 0;
            $stockGroups = $group->stockGroups->getRawValue();

            if (in_array($ravakId, $stockGroups)) {
                // DC Ravak hack
                $dcRavakGroups = $this->orm->stockGroups->findDcRavakGroups()->fetchPairs(null, 'id');
                $stockGroups = array_merge($stockGroups, $dcRavakGroups);
            }

            $this->stockGroupsCache[$groupId] = $stockGroups;
        }

        return $this->stockGroupsCache[$groupId];
    }

    public function getPartSumValue(array $yearData, int $month, int $maxCompareMonth): int
    {
        $value = 0;
        if ($month <= $maxCompareMonth) {
            $value += $yearData[$month];
        }
        if ($month - 1 <= $maxCompareMonth) {
            $value += $yearData[$month - 1];
        }
        if ($month - 2 <= $maxCompareMonth) {
            $value += $yearData[$month - 2];
        }
        return $value;
    }
}
