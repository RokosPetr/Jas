<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Core\Security\User;
use App\Modules\Presenters\SecurePresenter;
use Nextras\Orm\Entity\ToArrayConverter;

/** Presenter pro spravu zabezpecenych zdroju (resources) systemu  */
final class ResourcePresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'Seznam zdrojů',
        'edit' => 'Upravit název zdroje'
    ];

    /** Nahled zabezpeceneho zdroje systemu */
    public function actionPreview(int $id): void
    {
        $resource = $this->orm->resources->getById($id);
        if (!$resource) {
            $this->error('Položka nenalezena');
        }
        $adminRole = $this->orm->roles->getBy(['name' => User::ADMINISTRATOR]);
        $roles = [$adminRole->id => $adminRole->name];
        $roleUsers = [];
        $roleUsers[$adminRole->id] = $adminRole->users->toCollection()->orderBy('name')->fetchPairs(null, 'name');

        foreach ($resource->roles->toCollection()->orderBy('name') as $role) {
            $roles[$role->id] = $role->name;
            $roleUsers[$role->id] = $role->users->toCollection()->orderBy('name')->fetchPairs(null, 'name');
        }

        $this->template->heading = $resource->description;
        $this->template->roles = $roles;
        $this->template->roleUsers = $roleUsers;
        $this->sideDialogAjaxHandler();
    }

    /** Uprava popisu zabezpeceneho zdroje systemu */
    public function actionEdit(int $id): void
    {
        $resource = $this->orm->resources->getById($id);
        if (!$resource) {
            $this->error('Položka nenalezena');
        }
        $this['resourceForm']->setDefaults($resource->toArray(ToArrayConverter::RELATIONSHIP_AS_ID));
    }

    /** Datagrid se zabezpecenymi zdroji systemu */
    protected function createComponentResources(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->resources);
        $grid->settings->setFulltextColumns(['link', 'description']);

        $grid->addColumn('id', 'ID')->enableSort();
        $grid->addColumn('link', 'Zdroj');
        $grid->addColumn('description', 'Popis');

        $grid->addRowAction('preview', 'Náhled')->setSideDialog();
        $grid->addRowAction('edit', 'Upravit');

        return $grid;
    }

    /** Formular pro upravu zabezpeceneho zdroje systemu */
    protected function createComponentResourceForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addText('link', 'Zdroj')->setDisabled();
        $form->addText('description', 'Popis', null, 2000)->setRequired();
        $form->addSubmit('edit', 'Upravit');

        $form->onSuccess[] = function (BaseForm $form): void {
            $this->orm->resources->updateEntity($this->getParameter('id'), $form);
            $this->flashMessage('Popis zdroje byl upraven');
            $this->redirect('default');
        };

        return $form;
    }
}
