<?php
declare(strict_types=1);

namespace App\Modules\MtzModule\Component;

use App\Core\Component\Form\BaseForm;
use App\Service\OrmModel;
use Nette\Application\UI\Control;
use Nette\Application\UI\Presenter;
use Nette\Http\SessionSection;

class MtzBasket extends Control
{
    private OrmModel $orm;
    private SessionSection $session;
    private array $basketItems;

    public function __construct(OrmModel $orm)
    {
        $this->orm = $orm;
        $this->monitor(Presenter::class, function (): void {
            $this->session = $this->getPresenter()->getSession('mtzBasket');
        });
    }

    public function loadState(array $params): void
    {
        parent::loadState($params);
        $this->basketItems = $this->session->basketItems ?? [];
    }

    public function handleAddToBasket(int $id, int $quantity): void
    {
        $this->basketItems[$id] ??= 0;
        $this->basketItems[$id] += $quantity;
        if (!$this->basketItems[$id]) {
            unset($this->basketItems[$id]);
        }
        $this->session->basketItems = $this->basketItems;
        $this->reload();
    }

    public function handleRemoveFromBasket(int $id): void
    {
        unset($this->basketItems[$id]);
        $this->session->basketItems = $this->basketItems;
        $this->reload();
    }

    public function handleEmptyBasket(): void
    {
        $this->session->basketItems = $this->basketItems = [];
        $this->reload();
    }

    public function render(): void
    {
        $this->template->basketQuantities = $this->basketItems;
        $this->template->basketItems = $this->orm->mtzItems->findBy(['id' => array_keys($this->basketItems)])
            ->orderBy('name')->fetchAll();
        $this->template->setFile(__DIR__ . '/templates/mtzBasket.latte');
        $this->template->render();
    }

    protected function createComponentMtzOrderRemarkForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addTextArea('remark', 'Poznámka k objednávce');
        $form->addSubmit('submit', 'Odeslat objednávku');
        $form->onSuccess[] = function (array $values) {
            $this->orm->mtzOrders->createOrder($this->basketItems, $values['remark']);
            $this->handleEmptyBasket();
            $this->getPresenter()->flashMessage('Objednávka byla odeslána');
            $this->getPresenter()->redirect('default');
        };
        return $form;
    }

    protected function reload(): void
    {
        $this->redrawControl('myMtzBasket');
        if (!$this->basketItems) {
            $this->redrawControl('myMtzBasketOrderForm');
            $this->redrawControl('myMtzBasketOrderHeading');
        }
    }
}