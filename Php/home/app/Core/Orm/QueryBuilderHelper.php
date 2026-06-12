<?php
declare(strict_types=1);

namespace App\Core\Orm;

use Nextras\Dbal\QueryBuilder\QueryBuilder;
use Nextras\Orm\Collection\Functions\CompareEqualsFunction;
use Nextras\Orm\Collection\Helpers\DbalQueryBuilderHelper;
use Nextras\Orm\Entity\IEntity;
use Nextras\Orm\Model\IModel;
use Nextras\Orm\Mapper\Dbal\DbalMapper;
use Nextras\Orm\Repository\IRepository;
use Traversable;

/**
 * QueryBuilderHelper for Nextras\Dbal.
 */
class QueryBuilderHelper
{
    private IRepository $repository;
    public DbalQueryBuilderHelper $queryHelper;

    /**
     * QueryBuilderHelper constructor.
     */
    public function __construct(IModel $model, IRepository $repository, DbalMapper $mapper)
    {
        $this->repository = $repository;
        $this->queryHelper = new DbalQueryBuilderHelper($model, $repository, $mapper);
    }

    /**
     * Transforms orm condition and adds it to QueryBuilder.
     * @param  string       $expression
     * @param  mixed        $value
     * @param  QueryBuilder $builder
     */
    public function processWhereExpression(
        string $expression,
        $value,
        QueryBuilder $builder
    ): void {
        [$operatorClassString, $expr2] = $this->repository->getConditionParser()->parsePropertyOperator($expression);

        if ($value instanceof Traversable) {
            $value = iterator_to_array($value);
        } elseif ($value instanceof IEntity) {
            $value = $value->getValue('id');
        }

        if (is_array($value) && count($value) === 0) {
            $builder->andWhere(($operatorClassString === CompareEqualsFunction::class ? '1=0' : '1=1'));
            return;
        }

        $operatorClass = new $operatorClassString();
        $expression = $operatorClass->processQueryBuilderExpression($this->queryHelper, $builder, [$expr2, $value]);

        $builder->andWhere("%ex", $expression->args);
    }
}
