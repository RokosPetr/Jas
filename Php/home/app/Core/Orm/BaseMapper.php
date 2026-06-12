<?php
declare(strict_types=1);

namespace App\Core\Orm;

use Nextras\Dbal\QueryBuilder\QueryBuilder;
use Nextras\Orm\Collection\Helpers\DbalQueryBuilderHelper;
use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Mapper\Mapper;
use Nextras\Orm\Mapper\Dbal\DbalMapper;
use Nextras\Orm\Relationships\HasMany;
use Nextras\Orm\StorageReflection\StringHelper;
use Nextras\Orm\Entity\Reflection\PropertyMetadata;
use App\Core\Utils\DateTime;
use Nette\Utils\ArrayHash;
use Nextras\Dbal\IConnection;

abstract class BaseMapper extends Mapper
{
    public const DATA_STRING_SEPARATOR = '<!>';

    /** Inserts bulk records */
    public function insertItems(array $items): void
    {
        $this->getConnection()->query("INSERT INTO [$this->tableName] %values[]", $items);
    }

    public function updateItems(array $items, array $columns): void
    {
        $updates = implode(', ', array_map(fn(string $column): string => "`$column`=VALUES(`$column`)", $columns));
        $sql = "INSERT INTO [$this->tableName] %values[] ON DUPLICATE KEY UPDATE $updates";
        $this->getConnection()->query($sql, $items);
    }

    public function truncateTable(): void
    {
        $this->getConnection()->query("TRUNCATE [$this->tableName]");
    }

    /** Redefinition conventions to our use */
    protected function createConventions(): BaseConventions
    {
        return new BaseConventions(
            $this->createInflector(),
            $this->connection,
            $this->getTableName(),
            $this->getRepository()->getEntityMetadata(),
            $this->cache
        );
    }

    /** Redefinition getManyHasManyParameters */
    public function getManyHasManyParameters(PropertyMetadata $sourceProperty, DbalMapper $targetMapper): array
    {
        return [
            $this->getConventions()->getCoreManyHasManyStorageName(
                $targetMapper,
                $sourceProperty,
                $this->getRepository()
            ),
            $this->getConventions()->getCoreManyHasManyStoragePrimaryKeys(
                $targetMapper,
                $sourceProperty,
                $this->getRepository()
            )
        ];
    }

    /** Help function sending connection to query you own SQL */
    public function getConnection(): IConnection
    {
        return $this->connection;
    }

    /** Helper for searching by LIKE in repository for datagrid AJAX function */
    public function findByLikeForDatagrid(array $filter, array $order = []): QueryBuilder
    {
        $builder = $this->builder();
        $doContinue = false;
        $repo = $this->getRepository();
        $localHelper = new QueryBuilderHelper($repo->getModel(), $repo, $this);
        $dbalHelper = new DbalQueryBuilderHelper($repo->getModel(), $repo, $this);
        $entityClass = $repo->getEntityClassName([]);
        $entity = new $entityClass();
        $metadata = $entity->getMetadata();

        foreach ($filter as $k => $v) {
            //I will only extract the alpha value of the string so that I can compare
            // if there is an entity filter in the entity for it
            $alphaKey = preg_replace("/[^a-zA-Z_]+/", "", $k);

            if (is_string($v)) {
                $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $v);

                // check for d.m.Y format from datepicker
                if (!$dateTime && preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])\.(0[1-9]|1[0-2])\.[0-9]{4}$/", $v)) {
                    $dateTime = DateTime::createFromFormat('d.m.Y', $v)->setTime(0, 0, 0);
                }

                if ($dateTime !== false) {
                    $localHelper->processWhereExpression($k, $dateTime, $builder);
                    continue;
                }
            }

            $operator = '=';
            if (preg_match('#^(.+?)(!=|<=|>=|=|>|<)?$#', $k, $matches) && count($matches) > 2) {
                $operator = $matches[2];
            }

            $sqlMethodName = "getSql" . ucfirst($alphaKey);

            // process additional conditions for virtual columns
            if (method_exists($entity, $sqlMethodName)) {
                $sql = $this->getColumnName($entity, $alphaKey, $dbalHelper, $builder);

                if (is_array($v) || $v instanceof ArrayHash) {
                    $v = (array)$v;

                    if (array_key_exists("_TextFrom", $v) && !empty($v["_TextFrom"])) {
                        $builder->andWhere("$sql >= '" . $v["_TextFrom"] . "'");
                    }

                    if (array_key_exists("_TextTo", $v) && !empty($v["_TextTo"])) {
                        $builder->andWhere("$sql <= '" . $v["_TextTo"] . "'");
                    }

                    if (array_key_exists("_IntegerFrom", $v) && is_numeric($v["_IntegerFrom"])) {
                        $builder->andWhere("$sql >= " . $v["_IntegerFrom"] . "");
                    }

                    if (array_key_exists("_IntegerTo", $v) && is_numeric($v["_IntegerTo"])) {
                        $builder->andWhere("$sql <= " . $v["_IntegerTo"] . "");
                    }

                    if (is_array($v) && array_key_exists("_DateFrom", $v) && !empty($v["_DateFrom"])) {
                        $date = self::toDate($v["_DateFrom"], DateTime::DB_DATE);
                        $builder->andWhere("$sql >= '" . $date . "'");
                    }

                    if (is_array($v) && array_key_exists("_DateTo", $v) && !empty($v["_DateTo"])) {
                        $date = self::toDate($v["_DateTo"], DateTime::DB_DATE);
                        $builder->andWhere("$sql <= '" . $date . "'");
                    }

                    if (
                        is_array($v)
                        && array_key_exists("_DateTimeFrom", $v)
                        && !empty($v["_DateTimeFrom"])
                    ) {
                        $date = self::toDate($v["_DateTimeFrom"], DateTime::DB_DATETIME);
                        $builder->andWhere("$sql >= '" . $date . "'");
                    }

                    if (is_array($v) && array_key_exists("_DateTimeTo", $v) && !empty($v["_DateTimeTo"])) {
                        $date = self::toDate($v["_DateTimeTo"], DateTime::DB_DATETIME);
                        $builder->andWhere("$sql <= '" . $date . "'");
                    }

                    if (is_array($v) && array_key_exists("_RegExp", $v) && !empty($v["_RegExp"])) {
                        $builder->andWhere("$sql REGEXP('" . $v["_RegExp"] . "')");
                    }

                    //for example multiselect - here virtual
                    if (array_key_exists(0, $v)) {
                        $values = implode("','", $v);
                        $includeOrExclude = $operator === '!=' ? 'NOT IN' : 'IN';
                        $builder->andWhere("$sql $includeOrExclude ('" . $values . "')");
                    }
                } elseif (is_string($v)) {
                    $builder->andWhere("$sql LIKE %_like_", $v);
                } elseif (is_null($v)) {
                    $operator === '!='
                        ? $builder->andWhere("$sql IS NOT NULL")
                        : $builder->andWhere("$sql IS NULL");
                } else {
                    $builder->andWhere("$sql $operator %any", $v);
                }

                continue;
            }

            // check when use xx->name
            preg_match('/(.*?)->/', $k, $relationKey);
            $entityProperties = $metadata->getProperties();
            if (
                (isset($relationKey[1]) && !array_key_exists($relationKey[1], $entityProperties)) ||
                (!isset($relationKey[1]) && !array_key_exists($alphaKey, $entityProperties))
            ) {
                continue;
            }

            if (
                array_key_exists($k, $entityProperties)
                && !empty($entityProperties[$k]->wrapper)
                && ($entityProperties[$k]->getWrapperPrototype() instanceof HasMany)
            ) {
                // cover (multi)select filters on 1:m and m:m by automatically adding ->id reference to property name
                $targetPk = $entityProperties[$k]->relationship->entityMetadata->getPrimaryKey();
                if (count($targetPk) > 1) {
                    throw new \InvalidArgumentException(
                        'A target with composite key cannot be used in the Datagrid filter'
                    );
                }
                $k .= '->' . current($targetPk);
            }

            $kDb = StringHelper::underscore($k);

            if (is_a($v, ArrayHash::class)) {
                $v = (array) $v;
            }

            //for example multiselect - here normal usage
            if (is_array($v) && array_key_exists(0, $v)) {
                $localHelper->processWhereExpression($k, $v, $builder);
                $doContinue = true;
            }

            if (is_array($v) && array_key_exists("_DateFrom", $v)) {
                if (!empty($v["_DateFrom"])) {
                    $date = self::toDate($v["_DateFrom"], DateTime::DB_DATE);
                    $builder->andWhere('[' . $kDb . "]>= %s", $date);
                }
                $doContinue = true;
            }

            if (is_array($v) && array_key_exists("_DateTo", $v)) {
                if (!empty($v["_DateTo"])) {
                    $date = self::toDate($v["_DateTo"], DateTime::DB_DATE);
                    $builder->andWhere('[' . $kDb . "]<= %s", $date);
                }
                $doContinue = true;
            }

            if (is_array($v) && array_key_exists("_DateTimeFrom", $v)) {
                if (!empty($v["_DateTimeFrom"])) {
                    $date = self::toDate($v["_DateTimeFrom"], DateTime::DB_DATETIME);
                    $builder->andWhere('[' . $kDb . "]>= %s", $date);
                }
                $doContinue = true;
            }

            if (is_array($v) && array_key_exists("_DateTimeTo", $v)) {
                if (!empty($v["_DateTimeTo"])) {
                    $date = self::toDate($v["_DateTimeTo"], DateTime::DB_DATETIME);
                    $builder->andWhere('[' . $kDb . "]<= %s", $date);
                }
                $doContinue = true;
            }

            if (is_array($v) && array_key_exists("_TextFrom", $v)) {
                if (!empty($v["_TextFrom"])) {
                    $builder->andWhere('[' . $kDb . "]>= %s", $v["_TextFrom"]);
                }
                $doContinue = true;
            }

            if (is_array($v) && array_key_exists("_TextTo", $v)) {
                if (!empty($v["_TextTo"])) {
                    $builder->andWhere('[' . $kDb . "]<= %s", $v["_TextTo"]);
                }
                $doContinue = true;
            }

            if (is_array($v) && array_key_exists("_IntegerFrom", $v)) {
                if (is_numeric($v["_IntegerFrom"])) {
                    $builder->andWhere('[' . $kDb . "]>= %i", (int) $v["_IntegerFrom"]);
                }
                $doContinue = true;
            }

            if (is_array($v) && array_key_exists("_IntegerTo", $v)) {
                if (is_numeric($v["_IntegerTo"])) {
                    $builder->andWhere('[' . $kDb . "]<= %i", (int) $v["_IntegerTo"]);
                }
                $doContinue = true;
            }

            if (is_array($v) && array_key_exists("_RegExp", $v)) {
                if (!empty($v["_RegExp"])) {
                    $builder->andWhere('[' . $kDb . "] REGEXP(%s)", $v["_RegExp"]);
                }
                $doContinue = true;
            }

            if ($doContinue) {
                $doContinue = false;
                continue;
            }

            if (is_array($v)) {
                $v = current($v);
            }

            $kDb = $k;

            if (substr($kDb, -2) == "!=") {
                $localHelper->processWhereExpression($kDb, $v, $builder);
            } elseif (in_array(substr($kDb, -1), ["<", ">", "="])) {
                $localHelper->processWhereExpression($kDb, $v, $builder);
            } elseif ($kDb == 'id') {
                $localHelper->processWhereExpression($kDb, $v, $builder);
            } elseif (is_numeric($v)) {
                $localHelper->processWhereExpression($kDb, $v, $builder);
            } elseif (is_bool($v)) {
                $kDb = StringHelper::underscore($k);
                $builder->andWhere('[' . $builder->getFromAlias() . '.' . $kDb . '] = %b', $v);
            } elseif (!empty($v)) {
                $kDb = StringHelper::underscore($k);
                $builder->andWhere('[' . $builder->getFromAlias() . '.' . $kDb . '] LIKE %_like_', $v);
            }
        }

        if (isset($filter['query'], $filter['fulltextColumns'])) {
            $wheres = [];
            $queryStrings = is_array($filter['query']) ? $filter['query'] : [$filter['query']];
            $stringCount = count($queryStrings);

            foreach ($filter['fulltextColumns'] as $column) {
                $kDb = $this->getColumnName($entity, $column, $dbalHelper, $builder, true);
                $sql = "$kDb LIKE %_like_";
                $wheres['sql'][] = implode(" AND ", array_fill(0, $stringCount, $sql));

                foreach ($queryStrings as $queryString) {
                    $wheres['value'][] = $queryString;
                }
            }

            $builder->andWhere(implode(" OR ", $wheres['sql']), ...$wheres['value']);
        }

        foreach ($order as $col => $direction) {
            $orderColumn = $this->getColumnName($entity, $col, $dbalHelper, $builder, true);
            $builder->addOrderBy(sprintf("%s %s", $orderColumn, $direction));
        }

        return $builder;
    }

    /** Helper to get full column name of foreign columns */
    public function getColumnName(
        Entity $entity,
        string $column,
        DbalQueryBuilderHelper $parser,
        QueryBuilder $builder,
        bool $underscore = false
    ): string {

        // process additional conditions for virtual columns
        $sqlMethodName = "getSql" . ucfirst($column);

        if (method_exists($entity, $sqlMethodName)) {
            $sqlColumn = $entity->$sqlMethodName();
            $sql = $sqlColumn->getSql($parser, $builder);
        } else {
            if ($underscore) {
                $sql = StringHelper::underscore($column);
            } else {
                $sql = $column;
            }

            $sql = "[" . $builder->getFromAlias() . '.' . $sql . "]";
        }

        return $sql;
    }

    /** Format datetime to date string */
    private static function toDate(string $dateString = null, string $format = null): string
    {
        $date = is_null($dateString) ? new DateTime() : new DateTime($dateString);
        return $date->format($format);
    }
}
