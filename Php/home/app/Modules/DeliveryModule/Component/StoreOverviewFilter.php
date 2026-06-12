<?php
declare(strict_types=1);

namespace App\Modules\DeliveryModule\Component;

use App\Modules\DeliveryModule\Component\Entity\SalesFilterEntity;
use App\Modules\DeliveryModule\Orm\SalesData\SalesDataRepository;
use App\Modules\SystemModule\Orm\Stores\Store;
use Nette\Utils\Strings;
use Nextras\Orm\Collection\Expression\LikeExpression;

class StoreOverviewFilter extends BaseOverviewFilter
{
    protected string $overviewName = 'storeOverview';
    public int $store;
    public const END_CUSTOMER_LABEL = 'Koncový zákazník';
    public const COMPANY_CUSTOMER_LABEL = 'Firemní zákazník';
    public const END_CUSTOMER_ID = -1;
    public const COMPANY_CUSTOMER_ID = -2;
    //public const SALE_GROUPS = [1, 2, 201, 3, 301, 302, 4, 401, 5, 501, 6, 7, 8, 9];
    public const SALE_GROUPS = [1, 2, 3, 4, 5, 6, 7, 8, 9];

    private bool $isGridView;

    public function loadState(array $params): void
    {
        parent::loadState($params);
        $this->store = $this->session->store ?? $this->loadDefaultStore();
        $this->isGridView = $this->getPresenter()->getAction() === 'overviewGrid';

        if ($this->isGridView && $this->company < 0) {
            $this->resetCompany();
        }
    }

    public function render(): void
    {
        $this->template->stores = $this->loadStores();
        parent::render();
    }

    public function handleUpdateCompanySelect(): void
    {
        $result = [['id' => 0, 'text' => 'Vše']];
        $search = trim($this->getPresenter()->getParameter('search'));

        if (!$search) {
            $this->getPresenter()->sendJson(['results' => $result]);
        }

        if($this->store > 0 ){
            $filter = $this->getCompanyFilter();
        }

        if (preg_match("/^\d+$/", $search)) {
            $filter['ico~'] = LikeExpression::contains(ltrim($search, '0'));
        } else {
            $filter['name~'] = LikeExpression::contains($search);
        }

        $companyCollection = $this->orm->companies->findBy($filter)->orderBy('ico');

        if ($companyCollection->countStored() > 100) {
            $this->getPresenter()->sendJson(['results' => $result]);
        }

        if (!$this->isGridView && Strings::contains(mb_strtolower(self::END_CUSTOMER_LABEL), mb_strtolower($search))) {
            $result[] = ['id' => self::END_CUSTOMER_ID, 'text' => self::END_CUSTOMER_LABEL];
        }

        if (!$this->isGridView && Strings::contains(mb_strtolower(self::COMPANY_CUSTOMER_LABEL), mb_strtolower($search))) {
            $result[] = ['id' => self::COMPANY_CUSTOMER_ID, 'text' => self::COMPANY_CUSTOMER_LABEL];
        }

        foreach ($companyCollection as $company) {
            $result[] = [
                'id' => $company->id,
                'text' => "$company->icoString - $company->name"
            ];
        }

        $this->getPresenter()->sendJson(['results' => $result]);
    }

    public function getDataFilter(): SalesFilterEntity
    {
        return new SalesFilterEntity(
            $this->orm,
            $this->store,
            [],
            $this->years,
            $this->month,
            $this->producers,
            $this->orm->customGroups->getById($this->stockGroup),
            $this->company,
            0,
            $this->stockSeries,
            $this->stockItem,
        );
    }

    protected function getCompanyFilter(): array
    {
        if ($this->store < 100) {
            $return = ['depots->store->id' => $this->store ?: $this->orm->stores->loadSimpleStoreIds()];
            return $return;
        }
        $storeId = intval(substr((string) $this->store, 0, 1));
        $oz = intval(substr((string) $this->store, -1));
        return [
            'depots->store->id' => $storeId,
            'depots->group->number' => $oz === 1
                ? SalesDataRepository::STORE_OZ_1_GROUP
                : SalesDataRepository::STORE_OZ_2_GROUP
        ];
    }

    private function loadStores(): array
    {
        $userStore = $this->getPresenter()->getSysUser()->store->id ?? 0;
        $canChangeStore = $this->getPresenter()->getUser()->isSuperAdmin();
        $simpleStores = $this->orm->stores->loadSimpleStores();
        $simpleStores[Store::OSTRAVA_MAIN_STORAGE] = 'Michálkovice';
        $stores = [];

        if ($canChangeStore) {
            $stores[0] = 'Všechny';
        }

        foreach (self::SALE_GROUPS as $saleGroupId) {
            if ($saleGroupId > 100) {
                $storeId = intval(substr((string) $saleGroupId, 0, 1));
                if ($canChangeStore || $storeId === $userStore) {
                    $stores[$saleGroupId] = $simpleStores[$storeId] . ' OZ.' . (substr((string) $saleGroupId, -1));
                }
                continue;
            }

            if ($canChangeStore || $saleGroupId === $userStore) {
                $stores[$saleGroupId] = $simpleStores[$saleGroupId];
            }
            elseif ($this->getPresenter()->getSysUser()->id == 94 ){
                $stores[2] = "Olomouc";
                $stores[3] = "Otrokovice";
            }
            elseif ($this->getPresenter()->getSysUser()->id == 18 ){
                $stores[2] = "Teplice";
                $stores[3] = "Hradec Králové";
            }
        }

        return $stores;
    }

    private function loadDefaultStore(): int
    {
        $userStore = $this->getPresenter()->getSysUser()->store->id ?? 0;
        if (
            (!$userStore || in_array($userStore, Store::MAIN_STORAGES))
            && $this->getPresenter()->getUser()->isSuperAdmin()
        ) {
            return Store::OSTRAVA;
        }
        return $userStore;
    }
}