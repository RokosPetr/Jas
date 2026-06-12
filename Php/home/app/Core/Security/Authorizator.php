<?php
declare(strict_types=1);

namespace App\Core\Security;

use App\Service\OrmModel;
use Nette\Security\Permission;

final class Authorizator extends Permission
{
    public function __construct(OrmModel $orm)
    {
        foreach ($orm->resources->findAll() as $resource) {
            $this->addResource($resource->link);
        }

        foreach ($orm->roles->findAll() as $role) {
            $this->addRole($role->name);

            foreach ($role->resources as $resource) {
                $this->allow($role->name, $resource->link);
            }
        }
    }
}
