<?php
declare(strict_types=1);

namespace App\Core\Component\Datagrid;

use App\Core\Orm\BaseRepository;

interface DatagridFactory
{
    public function create(BaseRepository $repository, string $sessionId = ''): BaseDatagrid;
}
