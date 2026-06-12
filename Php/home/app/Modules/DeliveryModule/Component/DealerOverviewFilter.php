<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Component;

use App\Modules\DeliveryModule\Component\Entity\SalesFilterEntity;

class DealerOverviewFilter extends BaseOverviewFilter
{
    protected string $overviewName = 'dealerOverview';
    public array $dealers;
    public int $depot;

    public function loadState(array $params): void
    {
        parent::loadState($params);
        $this->dealers = $this->session->dealers ?? $this->loadDefaultDealers();
        $this->depot = $this->session->depot ?? 0;
    }

    public function handleSetMultiselectValues(): void
    {
        parent::handleSetMultiselectValues();

        if ($this->getPresenter()->getParameter('name') === 'dealers') {
            $this->company = 0;
            $this->depot = 0;
            unset($this->session->company, $this->session->depot);
        }
    }

    public function handleSetValue(): void
    {
        parent::handleSetValue();
        if ($this->getPresenter()->getParameter('name') === 'company') {
            $this->depot = 0;
            unset($this->session->depot);
        }
    }

    public function handleCancelCompany(): void
    {
        parent::handleCancelCompany();
        $this->depot = 0;
        unset($this->session->depot);
    }

    public function render(): void
    {
        $this->template->dealers = $this->orm->users->findDealers()->fetchPairs('id', 'name');
        $this->template->depots = $this->loadDepots();
        parent::render();
    }

    public function getDataFilter(): SalesFilterEntity
    {
        return new SalesFilterEntity(
            $this->orm,
            0,
            $this->dealers,
            $this->years,
            $this->month,
            $this->producers,
            $this->orm->customGroups->getById($this->stockGroup),
            $this->company,
            $this->depot,
            $this->stockSeries,
            $this->stockItem
        );
    }

    protected function getCompanyFilter(): array
    {
        return ['depots->dealers->id' => $this->dealers];
    }

    private function loadDefaultDealers(): array
    {
        $user = $this->getPresenter()->getUser();
        $dealers = $this->orm->users->findDealers()->fetchPairs('id', 'id');
        return isset($dealers[$user->id]) ? [$user->id] : [];
    }

    private function loadDepots(): array
    {
        $company = $this->company ? $this->orm->companies->getById($this->company) : null;

        if (!$company) {
            return [];
        }

        $depots = $company->depots->toCollection()->findBy(['dealers->id' => $this->dealers])->fetchPairs('id', 'title');
        return array_unique($depots);
    }
}