<?php
declare(strict_types=1);

namespace App\Core\Orm\Expresion;

use Nextras\Dbal\QueryBuilder\QueryBuilder;
use Nextras\Orm\Collection\Helpers\DbalQueryBuilderHelper;

class DbFunction implements DbExpressionInterface
{
    protected string $name = "";
    protected array $args = [];

    public function __construct(string $name, DbExpressionInterface ...$args)
    {
        $this->name = $name;
        $this->args = $args;
    }

    public function getSql(DbalQueryBuilderHelper $helper, QueryBuilder $builder): string
    {
        $args = [];

        foreach ($this->args as $argument) {
            $args[] = $argument->getSql($helper, $builder);
        }
        return sprintf("%s(%s)", $this->name, implode(",", $args));
    }
}
