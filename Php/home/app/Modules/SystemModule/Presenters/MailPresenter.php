<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Presenters;

use App\Core\Component\Datagrid\BaseDatagrid;
use App\Modules\Presenters\SecurePresenter;

/** Presenter pro spravu odeslanych emailu uzivatelum */
final class MailPresenter extends SecurePresenter
{
    public array $titles = [
        'default' => 'Odeslané emaily',
        'preview' => 'Náhled emailu'
    ];

    /** Nahled odeslaneho mailu */
    public function actionPreview(int $id): void
    {
        $mail = $this->orm->mails->getById($id);
        if (!$mail) {
            $this->error('Položka nenalezena');
        }
        $this->template->mail = $mail;
        $this->sideDialogAjaxHandler();
    }

    /** Datagrid s odeslanymi maily */
    protected function createComponentMails(): BaseDatagrid
    {
        $grid = $this->datagridFactory->create($this->orm->mails);
        $grid->addCellsTemplate(__DIR__ . '/../templates/Mail/grid.cells.latte');
        $grid->settings->setFulltextColumns(['user', 'subject']);

        $grid->addColumn('user', 'Uživatel')->enableSort();
        $grid->addColumn('subject', 'Předmět')->enableSort();
        $grid->addColumn('body', 'Obsah');
        $grid->addColumn('sentAt', 'Odesláno')->dateFormat()
            ->enableSort(BaseDatagrid::ORDER_DESC);

        $grid->addRowAction('preview', 'Náhled mailu')->setSideDialog();

        return $grid;
    }
}
