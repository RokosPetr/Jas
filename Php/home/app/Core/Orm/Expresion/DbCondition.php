<?php
declare(strict_types=1);

namespace App\Core\Orm\Expresion;

use Nextras\Dbal\QueryBuilder\QueryBuilder;
use Nextras\Orm\Collection\Helpers\DbalQueryBuilderHelper;

class DbCondition implements DbExpressionInterface
{
    protected array $args = [];

    public function __construct(DbExpressionInterface ...$args)
    {
        $this->args = $args;
    }

    public function getSql(DbalQueryBuilderHelper $helper, QueryBuilder $builder): string
    {
        $args = [];

        foreach ($this->args as $argument) {
            $args[] = $argument->getSql($helper, $builder);
        }

        return sprintf("(%s)", implode(" ", $args));
    }
}
