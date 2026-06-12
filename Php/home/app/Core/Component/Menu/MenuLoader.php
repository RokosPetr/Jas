<?php
declare(strict_types=1);

namespace App\Core\Component\Menu;

use App\Service\OrmModel;
use Contributte\MenuControl\IMenu;
use Contributte\MenuControl\IMenuItem;
use Contributte\MenuControl\Loaders\IMenuLoader;

final class MenuLoader implements IMenuLoader
{
    private OrmModel $orm;

    public function __construct(OrmModel $orm)
    {
        $this->orm = $orm;
    }

    public function load(IMenu $menu): void
    {
        foreach ($this->orm->menuItems->findBy(['active' => true])->orderBy('order') as $menuItem) {
            $menu->addItem(
                $menuItem->name,
                $menuItem->name,
                function (IMenuItem $item) use ($menuItem): void {
                    $item->setAction(ltrim($menuItem->link, ':'));
                    $item->setData([
                        'icon' => $menuItem->icon
                    ]);
                }
            );
        }
    }
}
