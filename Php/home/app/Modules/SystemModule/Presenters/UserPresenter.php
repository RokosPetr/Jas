<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Core\Component\Form\BaseForm;
use App\Core\Component\Form\FilterContainer;
use App\Modules\Presenters\SecurePresenter;
use Nette\Neon\Neon;
use Nette\Security\Passwords;
use Nextras\Dbal\Drivers\Exception\UniqueConstraintViolationException;
use Nextras\Orm\Entity\ToArrayConverter;

/** Presenter pro spravu uzivatelu */
final class UserPresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'Seznam uživatelů',
        'add' => 'Přidat uživatele',
        'edit' => 'Upravit uživatele',
        'changePassword' => 'Změna hesla'
    ];

    /** Odhlaseni uzivatele */
    public function actionLogout(): void
    {
        foreach ($this->orm->sessions->findBy(['phpsessid' => session_id()]) as $session) {
            $this->orm->sessions->removeAndFlush($session);
        }
        $this->getUser()->logout();
        $this->getSession()->destroy();
        $this->flashMessage('Byl jste odhlášen ze systému');
        $this->redirect(':System:Login:');
    }

    /** externí login */
    public function actionExternalLogin(string $phpsessid): void
    {
        foreach ($this->orm->sessions->findBy(['phpsessid' => $phpsessid]) as $session) {
            $user = $session->user;
            $result[] = [
                'name' => $user->name,
                'username' => $user->username,
                'internalLogin' => $user->internalLogin,
                'email' => $user->email,
                'store' => $user->store->id ?? null,
                'lastLogin' => date_format($user->lastLogin, 'Y-m-d H:i:s')
            ];
            $this->getPresenter()->sendJson(['phpuser' => $result]);
        }
        $this->getPresenter()->sendJson(['phpuser' => []]);
    }

    /** Nahled uzivatele */
    public function actionPreview(int $id): void
    {
        $sysUser = $this->orm->users->getById($id);
        if (!$sysUser) {
            $this->error('Položka nenalezena');
        }
        $this->template->sysUser = $sysUser;
        $this->sideDialogAjaxHandler();
    }

    /** Odeslane maily uzivateli */
    public function actionSentMails(int $id): void
    {
        $sysUser = $this->orm->users->getById($id);
        if (!$sysUser) {
            $this->error('Položka nenalezena');
        }
        $this->sideDialogAjaxHandler();
    }

    /** Uprava uzivatele */
    public function actionEdit(int $id): void
    {
        $user = $this->orm->users->getById($id);
        if (!$user || $user->deleted || $user->id === 1) {
            $this->error('Položka nenalezena');
        }
        $this['userForm']->setDefaults($user->toArray(ToArrayConverter::RELATIONSHIP_AS_ID));
    }

    /** Odstraneni (deaktivace) uzivatele */
    public function actionDelete(int $id): void
    {
        $user = $this->orm->users->getById($id);
        if (!$user || $user->deleted || $user->id === 1) {
            $this->error('Položka nenalezena');
        }
        $this->orm->users->cancelEntity($user);
        $this->flashMessage('Uživatel byl smazán');
        $this->redirect('default');
    }

    /** Obnoveni smazaneho uzivatele */
    public function actionRestore(int $id): void
    {
        $user = $this->orm->users->getById($id);
        if (!$user || !$user->deleted) {
            $this->error('Položka nenalezena');
        }
        if ($this->orm->users->getBy(['deleted' => false, 'internalLogin' => $user->internalLogin])) {
            $this->flashMessage('Uživatele nelze obnovit (JaS login je přiřazen jinému uživateli)', self::MSG_ERROR);
            $this->redirect('default');
        }
        $this->orm->users->restoreEntity($user);
        $this->flashMessage('Uživatel byl obnoven');
        $this->redirect('default');
    }

    /** Odstraneni blokace uzivatele */
    public function actionRemoveBan(int $id): void
    {
        $user = $this->orm->users->getById($id);
        if (!$user || $user->deleted || !$user->banned) {
            $this->error('Položka nenalezena');
        }
        $user->banned = false;
        $user->incorrectLogins = 0;
        $this->orm->users->persistAndFlush($user);
        $this->flashMessage('Účet uživatele byl odemčen');
        $this->redirect('default');
    }

    /** Zaslani pozvanky na mail uzivatele */
    public function actionInvitationMail(int $id): void
    {
        $user = $this->orm->users->getById($id);
        if (!$user || $user->deleted) {
            $this->error('Položka nenalezena');
        }
        $this->mailer->sendInviteMail($user);
        $this->flashMessage("Uživateli $user->name byla odeslána pozvánka do systému");
        $this->redirect('default');
    }

    /** Datagrid s uzivateli */
    protected function createComponentUsers(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->users);
        $grid->addCellsTemplate(__DIR__ . '/../templates/User/grid.cells.latte');
        $grid->settings->setFulltextColumns(['name', 'username', 'internalLogin']);

        $grid->addOtherColumn('id', 'ID')->enableSort();

        $grid->addColumn('name', 'Jméno')->enableSort(BaseDatagrid::ORDER_ASC);
        $grid->addColumn('username', 'Uživatelské jméno')->enableSort();
        $grid->addColumn('email', 'Email')->enableSort();
        $grid->addColumn('internalLogin', 'JaS login')->enableSort();
        $grid->addColumn('roles', 'Role');
        $grid->addColumn('lastLogin', 'Poslední přihlášení')->dateFormat()->enableSort();
        $grid->addColumn('loginCounter', 'Počet přihlášení')->enableSort();

        $grid->addOtherColumn('id', 'ID')->enableSort();
        $grid->addOtherColumn('created', 'Vytvořeno');
        $grid->addOtherColumn('updated', 'Změněno');
        $grid->addOtherColumn('cancelled', 'Smazáno');

        $grid->addTopAction('add', 'Přidat');
        $grid->addTopAction('default', 'Telefony')->setLink('System', 'Phone');
        $grid->addRowAction('preview', 'Náhled role')->setSideDialog();
        $grid->addRowAction('invitationMail', 'Odeslat pozvánku do systému', 'envelope')
            ->setCondition("\$deleted == 0 && \$loginCounter == 0");
        $grid->addRowAction('edit', 'Upravit')->setCondition("\$id != 1");
        $grid->addRowAction('delete', 'Smazat')
            ->setCondition("\$deleted == 0 && \$id != 1");
        $grid->addRowAction('restore', 'Obnovit', 'undo')->setCondition("\$deleted == 1");
        $grid->addRowAction('removeBan', 'Odemknout', 'ban')->setCondition("\$banned == 1");
        $grid->addRowAction('sentMails', 'Odeslané maily', 'external-link')
            ->setCondition("\$hasSentMails")
            ->setSideDialog();

        $grid->addLegend('Smazaný', 'legend_red', "\$deleted == 1");

        $grid->setFilterFormFactory(function (): FilterContainer {
            $form = new FilterContainer();
            $form->addSelect('deleted', 'Stav', [
                '' => 'Vše',
                '0' => 'Pouze nesmazaní',
                '1' => 'Pouze smazaní'
            ])->setDefaultValue('0');
            return $form;
        });

        return $grid;
    }

    /** Datagrid s odeslanymi maily zvolenemu uzivateli */
    protected function createComponentSentMails(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->mails);
        $grid->settings->setDataSourceFilter(['user=' => $this->getParameter('id')]);

        $grid->addColumn('subject', 'Předmět')->enableSort();
        $grid->addColumn('sentAt', 'Odesláno')->dateFormat()->enableSort(BaseDatagrid::ORDER_DESC);
        $grid->addRowAction('preview')->setLink('System', 'Mail')->setSideDialog();

        return $grid;
    }

    /** Formular pro upravu uzivatele */
    protected function createComponentUserForm(): BaseForm
    {
        $internalLogins = $this->getAction() === 'add'
            ? $this->orm->users->findBy(['deleted' => false])->fetchPairs(null, 'internalLogin')
            : $this->orm->users->findBy(['id!=' => $this->getParameter('id'), 'deleted' => false])->fetchPairs(null, 'internalLogin');
        $form = new BaseForm();
        $form->addText('name', 'Jméno', 0, 250)->setRequired();
        $form->addEmail('email', 'Email')
            ->setDefaultValue('@koupelny-jas.cz')
            ->setRequired();

        $form->addText('username', 'Uživatelské jméno', 0, 250)->setRequired();

        $form->addText('internalLogin', 'JaS login', 0, 255)
            ->addRule(BaseForm::IS_NOT_IN, 'Tento login je již přiřazen jinému uživateli', $internalLogins);

        $passwordInput = $form->addPassword('password', 'Heslo', 0, 255);
        $passwordInput->addCondition(BaseForm::FILLED)
            ->addRule(BaseForm::MIN_LENGTH, null, 5);

        if ($this->action === 'add') {
            $passwordInput->setRequired();
        }

        $form->addPassword('password2', 'Heslo znovu', 0, 255)
            ->addConditionOn($passwordInput, BaseForm::FILLED, true)
            ->addRule(BaseForm::FILLED)
            ->addRule(BaseForm::EQUAL, 'Hesla se neshodují', $passwordInput);

        $roles = $this->orm->roles->findAll()->fetchPairs('id', 'name');
        unset($roles[2]); // authenticated - tahle role se neprirazuje

        $form->addMultiSelect('roles', 'Role', $roles)->setRequired()
            ->getControlPrototype()->addClass('multiple-select2');

        if ($this->action === 'add') {
            $form->addCheckbox('sendInvitation', 'Poslat pozvánku')->setDefaultValue(true);
        }

        $form->addSubmit($this->action, $this->action === 'add' ? 'Přidat' : 'Upravit');

        $form->onValidate[] = function (BaseForm $form, array $values) {
            // JaS login musi zacinat ID pobocky nasledovane pismenem x - napr. 9x5
            $internalLogin = $values['internalLogin'];

            if ($internalLogin === '') {
                return;
            }

            $internalLoginSplit = explode('x', $internalLogin);

            if (count($internalLoginSplit) < 2 || !$this->orm->stores->getById($internalLoginSplit[0])) {
                $form['internalLogin']->addError('Neplatný formát');
            }
        };

        $form->onSuccess[] = [$this, 'userFormSuccess'];
        return $form;
    }

    /** Success callback formulare pro upravu uzivatele */
    public function userFormSuccess(BaseForm $form, array $values): void
    {
        unset($values['password2']);

        if (!empty($values['password'])) {
            $values['password'] = (new Passwords(PASSWORD_BCRYPT))->hash($values['password']);
        } else {
            unset($values['password']);
        }

        $values['store'] = $values['internalLogin'] ? explode('x', $values['internalLogin'])[0] : null;

        try {
            $user = $this->action === 'add'
                ? $this->orm->users->insertEntity(null, $values)
                : $this->orm->users->updateEntity($this->getParameter('id'), null, $values);
        } catch (UniqueConstraintViolationException $e) {
            $form->addError('Uživatel s daným uživatelským jménem již existuje!');
            return;
        }

        if ($values['sendInvitation'] ?? false) {
            $this->mailer->sendInviteMail($user);
        }

        $this->flashMessage('Uživatel byl ' . ($this->action === 'add' ? 'vytvořen': 'upraven'));
        $this->redirect('default');
    }

    /** Formular pro zmenu uzivatelskeho nesla */
    public function createComponentChangePasswordForm(): BaseForm
    {
        $form = new BaseForm();

        $passwordInput = $form->addPassword('password', 'Nové heslo', 0, 255)
            ->setRequired()
            ->addRule(BaseForm::MIN_LENGTH, null, 5);

        $form->addPassword('password2', 'Nové heslo znovu', 0, 255)
            ->setRequired()
            ->addRule(BaseForm::EQUAL, 'Hesla se neshodují', $passwordInput);

        $form->addSubmit('change', 'Změnit heslo');

        $form->onSuccess[] = function (array $values): void {
            $this->orm->users->updateEntity($this->user->id, null, [
                'password' => (new Passwords(PASSWORD_BCRYPT))->hash($values['password'])
            ]);
            $this->flashMessage('Vaše heslo bylo úspěšně změněno');
            $this->redirect(':System:Homepage:default');
        };

        return $form;
    }
}
