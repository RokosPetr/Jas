<?php
declare(strict_types=1);

namespace App\Core\Orm;

use Nextras\Orm\Mapper\Dbal\Conventions\Conventions;
use Nextras\Orm\Entity\Reflection\PropertyMetadata;
use Nextras\Orm\Mapper\Dbal\DbalMapper;
use Nextras\Orm\Repository\IRepository;

class BaseConventions extends Conventions
{
    /**
     * Name generator pattern %s_x_%s
     * @var string $manyHasManyStorageNamePattern
     */
    public $manyHasManyStorageNamePattern = "%s_%s";

    /**
     * Overwrite join table for many has many table name
     * - first from target mapper property
     * - then try source mapper BUT with table name & property name
     *   (when using perhaps more manyHasMany to same table)
     * - then try from source mapper property (joined m:m oneSided)
     * - default behaviour
     */
    public function getCoreManyHasManyStorageName(
        DbalMapper $targetMapper,
        PropertyMetadata $sourceProperty,
        IRepository $sourceRepository
    ): string {
        $table = null;
        $primary = $this->storageTable->name;
        $secondary = $targetMapper->getConventions()->getStorageTable()->name;

        //target mapper with table names
        $propertyName = "table_" . $primary . "_" . $secondary;
        if (isset($targetMapper->getRepository()->getMapper()->{$propertyName})) {
            $table = $targetMapper->getRepository()->getMapper()->{$propertyName};
        }

        //source mapper with property name
        $sourcePropertyName = "table_" . $primary . "_" . $sourceProperty->name;
        if (isset($sourceRepository->getMapper()->{$sourcePropertyName})) {
            $table = $sourceRepository->getMapper()->{$sourcePropertyName};
        }

        //source mapper with table names
        if (isset($sourceRepository->getMapper()->{$propertyName})) {
            $table = $sourceRepository->getMapper()->{$propertyName};
        }

        if (empty($table)) {
            $table = sprintf($this->manyHasManyStorageNamePattern, $primary, $secondary);
        }
        if ($this->storageNameWithSchema) {
            $schema = $this->storageTable->schema;
            return "$schema.$table";
        } else {
            return $table;
        }
    }

    /**
     * Return array with storage primary keys (from upper function)
     */
    public function getCoreManyHasManyStoragePrimaryKeys(
        DbalMapper $targetMapper,
        PropertyMetadata $sourceProperty,
        IRepository $sourceRepository
    ): array {
        $targetConvenctions = $targetMapper->getConventions();

        $one = $this->getStoragePrimaryKey()[0];
        $two = $targetConvenctions->getStoragePrimaryKey()[0];
        if ($one !== $two) {
            return [$one, $two];
        }

        return $this->findManyHasManyPrimaryColumns(
            $this->getCoreManyHasManyStorageName($targetMapper, $sourceProperty, $sourceRepository),
            $targetConvenctions->getStorageTable()
        );
    }
}
