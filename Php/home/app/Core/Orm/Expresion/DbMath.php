<?php
declare(strict_types=1);

namespace App\Core\Orm\Expresion;

use Nextras\Dbal\QueryBuilder\QueryBuilder;
use Nextras\Orm\Collection\Helpers\DbalQueryBuilderHelper;

class DbMath implements DbExpressionInterface
{
    private string $operator;
    private DbExpressionInterface $left;
    private DbExpressionInterface $right;

    public function __construct(DbExpressionInterface $left, string $operator, DbExpressionInterface $right)
    {
        $this->left = $left;
        $this->operator = $operator;
        $this->right = $right;
    }

    public function getSql(DbalQueryBuilderHelper $helper, QueryBuilder $builder): string
    {
        return sprintf(
            "(%s %s %s)",
            $this->left->getSql($helper, $builder),
            $this->operator,
            $this->right->getSql($helper, $builder)
        );
    }
}
