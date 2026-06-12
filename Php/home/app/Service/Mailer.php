<?php
declare(strict_types=1);

namespace App\Service;

use App\Modules\DeliveryModule\Orm\CustomerComplaints\CustomerComplaint;
use App\Modules\DeliveryModule\Orm\DeliveryNotes\DeliveryNote;
use App\Modules\SystemModule\Orm\Mails\Mail;
use App\Modules\SystemModule\Orm\Mails\MailRepository;
use App\Modules\SystemModule\Orm\Stores\Store;
use App\Modules\SystemModule\Orm\Users\User;
use App\Modules\TransportModule\Orm\Transports\StoreTransport;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\Template;
use Nette\Application\UI\TemplateFactory;
use Nette\Mail\Message;
use Nette\Mail\SendmailMailer;

class Mailer
{
    public const MAIL_TEMPLATES_DIR = __DIR__ . '/mailTemplates';
    public const ADMIN_MAIL = 'rokos@koupelny-jas.cz';
    public const SUPER_ADMIN_MAIL = 'rokos@koupelny-jas.cz';

    private LinkGenerator $linkGenerator;
    private TemplateFactory $templateFactory;
    private MailRepository $mailRepository;
    private string $emailFrom;

    public function __construct(LinkGenerator $linkGenerator, TemplateFactory $templateFactory, OrmModel $orm)
    {
        $this->linkGenerator = $linkGenerator;
        $this->templateFactory = $templateFactory;
        $this->mailRepository = $orm->mails;
        $this->emailFrom = $orm->users->getMainAdmin()->email;
    }

    private function createTemplate(): Template
    {
        $template = $this->templateFactory->createTemplate();
        $template->getLatte()->addProvider('uiControl', $this->linkGenerator);
        return $template;
    }

    public function createEmail(string $templateFile, $params): Message
    {
        $template = $this->createTemplate();
        $html = $template->renderToString($templateFile, $params);
        return (new Message())->setHtmlBody($html)->setFrom($this->emailFrom);
    }

    public function sendEmail(Message $email): void
    {
        (new SendmailMailer())->send($email);
    }

    /** Pozvankovy mail posilany novemu uzivateli systemu */
    public function sendInviteMail(User $user): void
    {
        $params = [
            'username' => $user->username,
            'internalLogin' => $user->internalLogin,
            'token' => $user->createToken()
        ];
        $mail = $this->createEmail(self::MAIL_TEMPLATES_DIR . '/inviteMail.latte', $params)
            ->addTo($user->email)
            ->setSubject('Koupelny JaS - Pozvánka do interní aplikace');
        $this->sendEmail($mail);
        $this->logMail($user, $mail);
    }

    /** Mail s odkazem na reset uzivatelskeho hesla */
    public function sendResetPasswordMail(User $user): void
    {
        $params = [
            'token' => $user->createToken(),
            'username' => $user->name
        ];
        $mail = $this->createEmail(self::MAIL_TEMPLATES_DIR . '/resetPasswordMail.latte', $params)
            ->addTo($user->email)
            ->setSubject('Koupelny JaS - Obnovení hesla');
        $this->sendEmail($mail);
        $this->logMail($user, $mail);
    }

    /** Notifikacni mail na upozorneni vyrizeni reklamace */
    public function sendCustomerComplaintNotification(CustomerComplaint $complaint): void
    {
        $params = [
            'complaint' => $complaint
        ];
        $mail = $this->createEmail(self::MAIL_TEMPLATES_DIR . '/customerComplaintNotification.latte', $params)
            ->addTo($complaint->createdBy->email)
            ->addCc($complaint->store->manager ? $complaint->store->manager->email : $complaint->store->email)
            ->addBcc(self::SUPER_ADMIN_MAIL)
            ->setSubject("Koupelny JaS - Nevyřešená reklamace $complaint->number");
        $this->sendEmail($mail);
        $this->logMail($complaint->createdBy, $mail);
    }

    public function sendUpdateSalesDataNotification(User $user): void
    {
        $mail = $this->createEmail(self::MAIL_TEMPLATES_DIR . '/salesDataUpdateNotification.latte', [])
            ->addTo($user->email)
            ->setSubject('Koupelny JaS - Analýza prodeje - aktualizace');
        $this->sendEmail($mail);
        $this->logMail($user, $mail);
    }

    /** Notifikacni mail na upozorneni duplicitniho dokladu */
    public function sendDeliveryNoteDuplicity(array $storeDuplicities): void
    {
        $params = ['duplicities' => $storeDuplicities];
        $mail = $this->createEmail(self::MAIL_TEMPLATES_DIR . '/deliveryNoteDuplicityNotification.latte', $params)
            ->addTo(self::ADMIN_MAIL)
            ->setSubject('Koupelny JaS - Duplicita dokladu');
        $this->sendEmail($mail);
    }

    public function sendEmptyStockItemData(array $regNumbers): void
    {
        $params = ['regNumbers' => $regNumbers];
        $mail = $this->createEmail(self::MAIL_TEMPLATES_DIR . '/emptyStockItemDataNotification.latte', $params)
            ->addTo(self::ADMIN_MAIL)
            ->setSubject('Koupelny JaS - Prázdná karta položky sortimentu');
        $this->sendEmail($mail);
    }

    /** @var DeliveryNote[] $notes */
    public function sendEmptyDeliveryNotes(array $notes): void
    {
        $params = ['notes' => $notes];
        $mail = $this->createEmail(self::MAIL_TEMPLATES_DIR . '/emptyDeliveryNotesNotification.latte', $params)
            ->addTo(self::ADMIN_MAIL)
            ->setSubject('Koupelny JaS - Prázdné doklady');
        $this->sendEmail($mail);
    }

    /**
     * Notifikace pri validacni chybe v rozvozech maloobchodu
     * @param StoreTransport[] $transports
     */
    public function sendInvalidStoreTransport(Store $store, array $transports): void
    {
        $storeMail = $store->email;
        $managerMail = $store->manager->email ?? false;

        if (!$transports || (!$storeMail && !$managerMail)) {
            // Pobocka nema email a nema managera nebo manager nema email
            return;
        }

        $date = reset($transports)->date;
        $params = ['date' => $date, 'transports' => $transports];
        $mail = $this->createEmail(self::MAIL_TEMPLATES_DIR . '/invalidStoreTransportNotification.latte', $params)
            ->addTo($storeMail ?: $managerMail)
            ->setSubject('Koupelny JaS - Chyby v rozvozech ' . $date->format('d.m.Y'));

        if ($storeMail && $managerMail && $storeMail !== $managerMail) {
            $mail->addCc($managerMail);
        }

        $this->sendEmail($mail);

        if ($managerMail) {
            $this->logMail($store->manager, $mail);
        }
    }

    /** Ulozeni odeslaneho mailu */
    public function logMail(User $user, Message $message): void
    {
        $sentMail = new Mail();
        $sentMail->user = $user;
        $sentMail->subject = $message->getSubject();
        $sentMail->body = $message->getHtmlBody();
        $this->mailRepository->persistAndFlush($sentMail);
    }
}
