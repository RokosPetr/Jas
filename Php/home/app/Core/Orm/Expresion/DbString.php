<?php
declare(strict_types=1);

namespace App\Core\Orm\Expresion;

use Nextras\Dbal\QueryBuilder\QueryBuilder;
use Nextras\Orm\Collection\Helpers\DbalQueryBuilderHelper;

class DbString implements DbExpressionInterface
{
    private string $expression;

    public function __construct(string $expression)
    {
        $this->expression = $expression;
    }

    public function getSql(DbalQueryBuilderHelper $helper, QueryBuilder $builder): string
    {
        return $this->expression;
    }
}
