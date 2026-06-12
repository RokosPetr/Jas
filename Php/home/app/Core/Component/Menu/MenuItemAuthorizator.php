<?php
declare(strict_types = 1);

namespace App\Core\Component\Menu;

use App\Core\Security\User;
use Contributte\MenuControl\IMenuItem;
use Contributte\MenuControl\Security\IAuthorizator;

final class MenuItemAuthorizator implements IAuthorizator
{
    private User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function isMenuItemAllowed(IMenuItem $item) : bool
    {
        $resource = $item->getAction();

        if (!is_null($resource)) {
            return $this->user->isAllowed(":$resource");
        }

        foreach ($item->getItems() as $childItem) {
            if ($childItem->isAllowed()) {
                return true;
            }
        }

        return false;
    }
}
