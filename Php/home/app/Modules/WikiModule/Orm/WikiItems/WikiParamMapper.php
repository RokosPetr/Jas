<?php
declare(strict_types=1);

namespace App\Modules\WikiModule\Orm\WikiItems;

use App\Core\Orm\BaseMapper;

class WikiParamMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'wiki_params';
}