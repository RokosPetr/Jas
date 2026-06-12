<?php
declare(strict_types = 1);

namespace App\Core\Orm\Expresion;

use Nextras\Dbal\QueryBuilder\QueryBuilder;
use Nextras\Orm\Collection\Helpers\DbalQueryBuilderHelper;

interface DbExpressionInterface
{
    public function getSql(DbalQueryBuilderHelper $helper, QueryBuilder $builder): string;
}
