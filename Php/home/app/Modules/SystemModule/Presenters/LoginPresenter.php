<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Presenters;

use App\Core\Component\Form\BaseForm;
use App\Core\Utils\DateTime;
use App\Modules\Presenters\BasePresenter;
use App\Modules\SystemModule\Orm\Sessions\Session;
use Nette\Security\AuthenticationException;
use Nette\Security\Passwords;

/** Presenter pro spravu prihlasovani uzivatelu */
final class LoginPresenter extends BasePresenter
{
    public array $titles = [
        'default' => 'Přihlášení',
        'forgottenPassword' => 'Zapomenuté heslo',
        'resetPassword' => 'Nastavení nového hesla'
    ];

    /** URL pro přesměrování po přihlášení (předána z externího systému) */
    private ?string $backUrl = null;

    /** Start up metoda - pokud se uzivatel prihlasen, dojde k presmerovani na domovskou stranku */
    protected function startup(): void
    {
        $this->getSession()->start();
        $section = $this->getSession('login');

        // Při GET requestu s backUrl ho uložíme do session (na POST requestu ho v URL nemáme)
        $backUrlParam = $this->getParameter('backUrl');
        if ($backUrlParam !== null && $backUrlParam !== '') {
            $section->backUrl = $backUrlParam;
        }
        $this->backUrl = isset($section->backUrl) ? (string)$section->backUrl : null;

        if ($this->user->isLoggedIn()) {
            if ($this->backUrl !== null && (strpos($this->backUrl, 'https://') === 0 || strpos($this->backUrl, 'http://') === 0)) {
                unset($section->backUrl);
                $separator = strpos($this->backUrl, '?') !== false ? '&' : '?';
                $this->redirectUrl($this->backUrl . $separator . 'phpsessid=' . session_id());
            }
            $this->redirect('Homepage:default');
        }
        parent::startup();
    }

    /** Obnova zamomenuteho hesla */
    public function actionResetPassword(string $id): void
    {
        $user = $this->orm->users->getByToken($id);
        if (!$user) {
            $this->error('Stránka již není dostupná');
        }
        $this->template->username = $user->name;
    }

    /** Prihlasovaci formular */
    protected function createComponentLoginForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addText('username', 'uživatelské jméno', null, 250)->setRequired();
        $form->addPassword('password', 'heslo', null, 250)->setRequired();
        $form->addSubmit('login', 'PŘIHLÁSIT');
        $form->onSuccess[] = [$this, 'loginFormSuccess'];
        return $form;
    }

    /** Předání backUrl do šablony */
    public function renderDefault(): void
    {
        $this->template->backUrl = $this->backUrl;
    }

    /** Success callback prihlasovaciho formulare */
    public function loginFormSuccess(\stdClass $values): void
    {
        $backUrl = $this->backUrl;

        try {
            $this->getUser()->setExpiration('+ 1 days')->login($values->username, $values->password);
            $user = $this->orm->users->getById($this->getUser()->getId());

            $session = new Session();
            $session->phpsessid = session_id();
            $session->user = $user;
            $session->expiration = ((new DateTime())->modify('+ 1 day'));

            $this->orm->sessions->persistAndFlush($session);

            $this->flashMessage('Byl jste úspěšně přihlášen.');

        } catch (AuthenticationException $e) {
            $this->flashMessage($e->getMessage(), self::MSG_ERROR);
            $this->redirect('default');
        }

        if ($backUrl !== null && (strpos($backUrl, 'https://') === 0 || strpos($backUrl, 'http://') === 0)) {
            unset($this->getSession('login')->backUrl);
            $separator = strpos($backUrl, '?') !== false ? '&' : '?';
            $this->redirectUrl($backUrl . $separator . 'phpsessid=' . session_id());
        }

        $this->redirect(':System:Homepage:');
    }

    /** Formular pro zadani emailu pri zapomenuti hesla */
    public function createComponentForgottenLoginForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addText('username', 'login')->setRequired();
        $form->addSubmit('send', 'odeslat');
        $form->onValidate[] = function (BaseForm $form, array $values): void {
            $user = $this->orm->users->getByUsername($values['username']);
            if (!$user) {
                $form->addError('Uživatel není v systému registrován');
            }
        };
        $form->onSuccess[] = function (array $values): void {
            $user = $this->orm->users->getByUsername($values['username']);
            $this->mailer->sendResetPasswordMail($user);
            $this->flashMessage('Na Váš email byl odeslaný odkaz na změnu hesla');
            $this->redirect('default');
        };
        return $form;
    }

    /** Formular pro zmenu zapomenuteho hesla */
    public function createComponentResetPasswordForm(): BaseForm
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
            $user = $this->orm->users->getByToken($this->getParameter('id'));
            if (!$user) {
                $this->error('Stránka již není dostupná');
            }
            $this->orm->users->updateEntity($user->id, null, [
                'password' => (new Passwords(PASSWORD_BCRYPT))->hash($values['password']),
                'token' => null,
                'tokenValidity' => null,
                'incorrectLogins' => 0,
                'banned' => false
            ]);
            $this->flashMessage('Vaše heslo bylo úspěšně změněno');
            $this->redirect('default');
        };

        return $form;
    }
}
