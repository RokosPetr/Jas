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
final class CompanyPresenter extends SecurePresenter
{

    public function actionSetCompany(string $company): void
    {
        $this->redirect(':Bathroom:FilterView:');
    }

}
