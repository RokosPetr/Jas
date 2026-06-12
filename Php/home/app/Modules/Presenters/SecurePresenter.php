<?php
declare(strict_types=1);

namespace App\Modules\Presenters;

use App\Modules\StockModule\Orm\StockItems\StockItem;
use App\Modules\SystemModule\Component\StoreSelect;
use App\Modules\SystemModule\Orm\Stores\Store;
use App\Modules\SystemModule\Orm\Users\User;
use App\Modules\SystemModule\Orm\UserSettings\UserSetting;
use Nextras\Orm\Collection\Expression\LikeExpression;
use Nextras\Orm\Collection\ICollection;

abstract class SecurePresenter extends BasePresenter
{
    protected int $selectedStore;

    /** Seznam zdroju dostupnym vsem prihlasenym uzivatelum */
    private array $allowedResources = [
        ':System:Homepage:default',
        ':System:User:logout',
        ':System:User:changePassword',
        ':Stock:Warehouseman:tableToPdf'
    ];

    /**
     * Start up metoda
     * - Autorizace pristupu uzivatele na stranku
     * - Nastaveni vybrane pobocky
     */
    protected function startup(): void
    {
        parent::startup();

        if ($this->getName() === 'System:User' && $this->action === 'externallogin'){
            return;
        }

        if (!$this->getUser()->isLoggedIn()) {
            $backlink = $this->getName() === 'System:Homepage' ? [] : ['backlink' => $this->storeRequest()];
            $this->redirect(':System:Login:', $backlink);
        }

        if (!$this->getUser()->isAllowed($this->resource) && !in_array($this->resource, $this->allowedResources)) {
            $this->flashMessage('Na tuto stránku nemáte povolen přístup');
            $this->redirect(':System:Homepage:');
        }

        if (in_array($this->action, ['add', 'edit'])) {
            $this->setView('addEdit');
        }

        $sysUser = $this->getSysUser();
        $this->orm->setSysUser($sysUser);
        $this->setStoreAccess($sysUser);
    }

    /** AJAX odpoved na select pro vyber polozky sortimentu vazane k povinnemu sortimentu */
    public function handleUpdateProductSelect(): void
    {
        $search = trim($this->getParameter('search'));
        $result = [];

        if (!$search) {
            $this->sendJson($result);
        }

        $filter = [
            'query' => explode(' ', $search),
            'fulltextColumns' => ['regNumber', 'name', 'catalog'],
            'status' => 'all'
        ];

        /** @var StockItem $item */
        foreach ($this->orm->stockItems->getDataForDatagrid($filter, []) as $item) {
            $result[] = [
                'id' => $item->id,
                'text' => $item->title
            ];
        }

        $this->sendJson(['results' => $result]);
    }

    /** AJAX odpoved na select pro vyber partnerske pobocky velkoobchodu */
    public function handleUpdatePartnerVOSelect(): void
    {
        $search = trim($this->getParameter('search'));
        $result = [];

        if (!$search) {
            $this->sendJson($result);
        }

        $depots = $this->orm->companyDepots->findBy([
            ICollection::AND,
            [
                ICollection::AND,
                'store->id' => Store::OSTRAVA_MAIN_STORAGE,
                'dealers->id!=' => null
            ],
            [
                ICollection::OR,
                'company->ico~' => LikeExpression::contains($search),
                'company->name~' => LikeExpression::contains($search),
                'title~' => LikeExpression::contains($search)
            ]
        ])->orderBy('company->name');

        foreach ($depots as $depot) {
            $result[] = [
                'id' => $depot->id,
                'text' => $depot->depotName
            ];
        }

        $this->sendJson(['results' => $result]);
    }

    /** AJAX odpoved na select pro vyber serie */
    public function handleUpdateSeriesSelect(): void
    {
        $search = trim($this->getParameter('search'));
        $result = [];

        if (!$search) {
            $this->sendJson($result);
        }

        $series = $this->orm->stockSeries->findBy(['name~' => LikeExpression::contains($search)])->orderBy('name');

        foreach ($series as $seriesItem) {
            $result[] = [
                'id' => $seriesItem->id,
                'text' => $seriesItem->name
            ];
        }

        $this->sendJson(['results' => $result]);
    }

    /** Najde uzivatelske nastaveni prihlaseneho uzivatele pro komponentu */
    public function getUserSetting(string $component): array
    {
        return $this->orm->userSetting->getBy(['user->id' => $this->user->id, 'component' => $component])->setting ?? [];
    }

    /** Ulozi uzivatelske nastaveni prihlaseneho uzivatele pro komponentu */
    public function setUserSetting(string $component, array $setting): void
    {
        $userSetting = $this->orm->userSetting->getBy(['user->id' => $this->user->id, 'component' => $component]);
        if ($userSetting) {
            $userSetting->setting = $setting;
        } else {
            $userSetting = new UserSetting();
            $userSetting->user = $this->getSysUser();
            $userSetting->component = $component;
            $userSetting->setting = $setting;
        }
        $this->orm->userSetting->persistAndFlush($userSetting);
    }

    /** Vraci id pobocky prirazenou uzivateli */
    public function getUserStore(): int
    {
        return $this->selectedStore;
    }

    /** Komponenta selectu s pobockama */
    protected function createComponentStoreSelect(): StoreSelect
    {
        return new StoreSelect($this->orm->stores, $this->getSession('system'));
    }

    /** Vraci entitu prihlaseneho uzivatele */
    public function getSysUser(): User
    {
        return $this->orm->users->getById($this->user->id);
    }

    /**
     * Nastaveni pobocky
     *  - Pokud uzivatel si nemuze vybirat pobocky, nastavi se jeho pridelena pobocka
     *  - Jinak se nastavi posledne vybrana polozka, uzivatelova pobocka nebo Ostravska pobocka
     */
    private function setStoreAccess(User $sysUser): void
    {
        $session = $this->getSession('system');

        if (!$this->getUser()->isSuperAdmin()) {
            if (!$sysUser->store) {
                $this->flashMessage('Na tuto stránku nemáte povolen přístup');
                $this->redirect(':System:Homepage:');
            }

            $this->selectedStore = $session->selectedStore = $sysUser->store->id;
        } else {
            if (isset($session->selectedStore)) {
                $this->selectedStore = $session->selectedStore;
            } elseif ($sysUser->store) {
                $this->selectedStore = $session->selectedStore = $sysUser->store->id;
            } else {
                $this->selectedStore = $session->selectedStore = Store::OSTRAVA_MAIN_STORAGE;
            }
        }
    }
}
