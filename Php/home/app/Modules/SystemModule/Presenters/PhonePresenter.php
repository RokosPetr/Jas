<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Modules\Presenters\SecurePresenter;
use Nextras\Orm\Entity\ToArrayConverter;

/** Presenter pro spravu tel. cisel uzivatelu */
final class PhonePresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'Seznam telefonů',
        'add' => 'Přidat telefon',
        'edit' => 'Upravit telefon'
    ];

    /** Uprava tel. cisla */
    public function actionEdit(int $id): void
    {
        $phone = $this->orm->phones->getById($id);
        if (!$phone) {
            $this->error('Položka nenalezena');
        }
        $this['phoneForm']->setDefaults($phone->toArray(ToArrayConverter::RELATIONSHIP_AS_ID));
    }

    /** Smazani tel. cisla */
    public function actionDelete(int $id): void
    {
        $phone = $this->orm->phones->getById($id);
        if (!$phone) {
            $this->error('Položka nenalezena');
        }
        $this->orm->phones->removeAndFlush($phone);
        $this->flashMessage('Telefon byl smazán');
        $this->redirect('default');
    }

    /** Datagrid s tel. cisly */
    protected function createComponentPhones(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->phones);
        $grid->settings->setFulltextColumns(['username']);
        $grid->addColumn('username', 'Uživatel')->enableSort(BaseDatagrid::ORDER_ASC);
        $grid->addColumn('number', 'Číslo');
        $grid->addColumn('description', 'Popis');

        $grid->addTopAction('add', 'Přidat');
        $grid->addRowAction('edit', 'Upravit');
        $grid->addRowAction('delete', 'Smazat');

        return $grid;
    }

    /** Formular pro upravu tel. cisla */
    protected function createComponentPhoneForm(): BaseForm
    {
        $form = new BaseForm();
        $users = ['' => '-- Vyberte --'] + $this->orm->users->findBy(['deleted' => false])->fetchPairs('id', 'name');
        $form->addSelect('user', 'Uživatel', $users)->setRequired();
        $form->addText('number', 'Číslo', null, 20)->setRequired();
        $form->addText('description', 'Popis', null, 200)->setRequired();
        $form->addSubmit($this->action, $this->action === 'add' ? 'Přidat' : 'Upravit');

        $form->onSuccess[] = function (BaseForm $form): void {
            $this->action === 'add'
                ? $this->orm->phones->insertEntity($form)
                : $this->orm->phones->updateEntity($this->getParameter('id'), $form);
            $this->flashMessage('Telefon byl ' . ($this->action === 'add' ? 'přidán': 'upraven'));
            $this->redirect('default');
        };
        return $form;
    }
}
