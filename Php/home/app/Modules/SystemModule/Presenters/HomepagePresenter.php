<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Presenters;

use App\Modules\Presenters\SecurePresenter;
use Contributte\MenuControl\UI\MenuComponent;
use App\Core\Utils\DateTime;


/** Vychozi presenter aplikace */
final class HomepagePresenter extends SecurePresenter
{
    /** Hlavni menu aplikace */
    protected function createComponentHomepageMenu(): MenuComponent
    {
        return $this->menuFactory->create('homepageMenu');
    }
}
