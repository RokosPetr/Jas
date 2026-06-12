<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Modules\Presenters\SecurePresenter;
use Nette\Http\IResponse;
use Nette\Neon\Neon;
use Nextras\Orm\Entity\ToArrayConverter;

/** Presenter pro spravu uzivatelskych roli v systemu */
final class RolePresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'Seznam rolí',
        'preview' => 'Náhled',
        'add' => 'Přidat roli',
        'edit' => 'Upravit roli'
    ];

    /** Nahled uzivatelsko role */
    public function actionPreview(int $id): void
    {
        $role = $this->orm->roles->getById($id);
        if (!$role) {
            $this->error('Položka nenalezena');
        }
        $this->template->role = $role;
        $this->sideDialogAjaxHandler();
    }

    /** Pridani nove uzivatelske role */
    public function actionAdd(): void
    {
        $this->template->resourceConfig = Neon::decode(file_get_contents(CONFIG_DIR . '/resources.neon'));
        $this->template->linkResourceMap = $this->orm->resources->findAll()->fetchPairs('link', 'id');
    }

    /** Uprava uzivatelske role */
    public function actionEdit(int $id): void
    {
        $role = $this->orm->roles->getById($id);
        if (!$role) {
            $this->error('Položka nenalezena');
        }
        if ($role->id === 1) {
            $this->error('Položku nelze upravovat', IResponse::S405_METHOD_NOT_ALLOWED);
        }
        $defaults = $role->toArray(ToArrayConverter::RELATIONSHIP_AS_ID);
        $defaults['resources'] = array_map(fn(): bool => true, array_flip($defaults['resources']));
        $this['roleForm']->setDefaults($defaults);
        $this->template->resourceConfig = Neon::decode(file_get_contents(CONFIG_DIR . '/resources.neon'));
        $this->template->linkResourceMap = $this->orm->resources->findAll()->fetchPairs('link', 'id');
    }

    /** Odstraneni uzivatelske role */
    public function actionDelete(int $id): void
    {
        $role = $this->orm->roles->getById($id);
        if (!$role) {
            $this->error('Položka nenalezena');
        }
        if ($role->id < 3) {
            $this->error('Položku nelze upravovat', IResponse::S405_METHOD_NOT_ALLOWED);
        }
        $this->orm->roles->removeAndFlush($role);
        $this->flashMessage('Role byla smazána');
        $this->redirect('default');
    }

    /** Datagrid s uzivatelskymi rolemi */
    protected function createComponentRoles(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->roles);
        $grid->addColumn('name', 'Název')->enableSort();
        $grid->addColumn('description', 'Popis');

        $grid->addTopAction('add', 'Přidat');
        $grid->addTopAction('default', 'Zdroje')->setLink('System', 'Resource');
        $grid->addRowAction('preview', 'Náhled role')->setSideDialog();
        $grid->addRowAction('edit', 'Upravit')->setCondition("\$id != 1");
        $grid->addRowAction('delete', 'Smazat')->setCondition("\$id > 2");

        return $grid;
    }

    /** Formular pro upravu uzivatelske role */
    protected function createComponentRoleForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addText('name', 'Název', null, 250)->setRequired();
        $form->addTextArea('description', 'Popis')
            ->setRequired()
            ->addRule(BaseForm::MAX_LENGTH, null, 2000);
        $resourceContainer = $form->addContainer('resources');

        foreach ($this->orm->resources->findAll() as $resource) {
            $linkSplit = explode(':', ltrim($resource->link, ':'));
            $resourceContainer->addCheckbox((string) $resource->id, $resource->description)
                ->getControlPrototype()
                ->setAttribute('data-module', $linkSplit[0])
                ->setAttribute('data-presenter', $linkSplit[1]);
        }

        $form->addSubmit($this->action, $this->action === 'add' ? 'Přidat' : 'Upravit');

        $form->onValidate[] = function (BaseForm $form): void {
            $nameTextInput = $form['name'];
            $role = $this->orm->roles->getBy(['name' => $nameTextInput->getValue()]);
            if (!$role || ($this->action === 'edit' && $role->id == $this->getParameter('id'))) {
                return;
            }
            $nameTextInput->addError('Role s tímto názvem již existuje!');
        };

        $form->onSuccess[] = function (array $values): void {
            $values['resources'] = array_keys(array_filter($values['resources']));
            $this->action === 'add'
                ? $this->orm->roles->insertEntity(null, $values)
                : $this->orm->roles->updateEntity($this->getParameter('id'), null, $values);
            $this->flashMessage('Role byla ' . ($this->action === 'add' ? 'přidána': 'upravena'));
            $this->redirect('default');
        };

        return $form;
    }
}
