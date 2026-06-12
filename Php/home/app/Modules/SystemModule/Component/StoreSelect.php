<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Component;

use App\Modules\SystemModule\Orm\Stores\StoreRepository;
use Nette\Application\UI\Control;
use Nette\Http\SessionSection;

class StoreSelect extends Control
{
    protected StoreRepository $storeRepository;
    protected SessionSection $session;

    public function __construct(StoreRepository $storeRepository, SessionSection $session)
    {
        $this->storeRepository = $storeRepository;
        $this->session = $session;
    }

    public function handleChangeStore(int $id): void
    {
        $this->session->selectedStore = $id;
        $this->redirect('this');
    }

    public function render(): void
    {
        $this->template->stores = $this->storeRepository->findOrderedStoreNames();
        $this->template->selectedStore = $this->session->selectedStore;
        $this->template->setFile(__DIR__ . '/templates/storeSelect.latte');
        $this->template->render();
    }
}