<?php
declare(strict_types = 1);

namespace App\Core\Orm;

use Contributte\FormMultiplier\Multiplier;
use Nextras\Orm\Collection\DbalCollection;
use Nextras\Orm\Entity\IEntity;
use Nextras\Orm\Entity\Reflection\PropertyMetadata;
use Nextras\Orm\Exception\InvalidStateException;
use Nextras\Orm\Repository\Repository;
use Nextras\Orm\Relationships\OneHasMany;
use Nextras\Orm\Relationships\ManyHasMany;
use Nextras\Orm\Relationships\ManyHasOne;
use Nette\Utils\Paginator;
use Nette\Forms\Controls\Checkbox;
use Nette\Forms\Container;
use Nette\Utils\ArrayHash;

abstract class BaseRepository extends Repository
{
    public function isRestorable(): bool
    {
        return false;
    }

    public function insertItems(array $items): void
    {
        $this->getMapper()->insertItems($items);
    }

    public function updateItems(array $items, array $columns): void
    {
        $this->getMapper()->updateItems($items, $columns);
    }

    public function truncateTable(): void
    {
        $this->getMapper()->truncateTable();
    }

    public function insertEntity(Container $form = null, $values = []): IEntity
    {
        $className = $this->getEntityClassName([]);
        $entity = new $className();
        $multipliers = $this->setEntityProperties($entity, $form, $values);
        $this->persist($entity);
        /** @var PropertyMetadata $property */
        foreach ($multipliers as $propertyName => $property) {
            $this->setMultiplierProperty($entity, $property, $form[$propertyName]);
        }
        $this->flush();
        return $entity;
    }

    public function updateEntity($id, Container $form = null, $values = []): IEntity
    {
        $entity = $this->getById($id);
        $multipliers = $this->setEntityProperties($entity, $form, $values);
        $this->persist($entity);
        /** @var PropertyMetadata $property */
        foreach ($multipliers as $propertyName => $property) {
            $this->setMultiplierProperty($entity, $property, $form[$propertyName]);
        }
        $this->flush();
        return $entity;
    }

    public function cancelEntity(IEntity $entity): IEntity
    {
        $entity->deleted = true;
        $entity->deletedBy = $this->getModel()->getSysUser();
        $entity->deletedAt = new \DateTimeImmutable();
        return $this->persistAndFlush($entity);
    }

    public function restoreEntity(IEntity $entity): IEntity
    {
        $entity->deleted = false;
        $entity->deletedBy = null;
        $entity->deletedAt = null;
        return $this->persistAndFlush($entity);
    }

    protected function setEntityProperties(IEntity $entity, Container $form = null, $data = []): array
    {
        if (!($data instanceof ArrayHash) && !is_array($data)) {
            throw new InvalidStateException(
                "Values sent to save doesn't match required types - use ArrayHash or array."
            );
        }

        $values = !empty($data) ? (array)$data : $form->getValues(true);
        $components = !is_null($form) ? $form->getComponents() : [];
        $properties = $entity->getMetadata()->getProperties();
        $multipliers = [];

        foreach ($properties as $propertyName => $property) {
            if (!array_key_exists($propertyName, $values)) {
                continue;
            }

            $component = $components[$propertyName] ?? null;
            $value = $values[$propertyName];

            if ($component instanceof Checkbox) {
                $entity->$propertyName = $value === 'on' || $value === true ? 1 : 0;
                continue;
            }

            if ($component instanceof Multiplier) {
                $multipliers[$propertyName] = $property;
                continue;
            }

            if (!empty($property->relationship)) {
                $this->setRelationshipProperty($entity, $property, $value);
                continue;
            }

            if (is_null($value) || $value === '') {
                $this->setEmptyProperty($entity, $property);
                continue;
            }

            $entity->$propertyName = $value;
        }

        return $multipliers;
    }

    /**
     * Set Entity relationship property
     * @param IEntity $entity
     * @param PropertyMetadata $property
     * @param mixed $value
     */
    protected function setRelationshipProperty(IEntity $entity, PropertyMetadata $property, $value): void
    {
        $propertyName = $property->name;
        $relationRepository = $this->getModel()->getRepository($property->relationship->repository);

        if ($property->wrapper == ManyHasMany::class || $property->wrapper == OneHasMany::class) {
            foreach ($entity->$propertyName as $relationEntity) {
                $entity->$propertyName->remove($relationEntity);
            }

            if (empty($value)) {
                return;
            }

            foreach ($relationRepository->findBy(['id' => $value]) as $relationEntity) {
                $entity->$propertyName->add($relationEntity);
            }
            return;
        }

        if (is_numeric($value)) {
            $entity->$propertyName = $relationRepository->getById($value);
            return;
        }

        if (is_array($value)) {
            $relationEntityName = $property->relationship->entity;
            $relationEntity = $entity->$propertyName ?: new $relationEntityName();

            foreach ($value as $relationPropertyName => $relationPropertyValue) {
                if ($relationPropertyName == 'id') {
                    continue;
                }

                $relationEntity->$relationPropertyName = $relationPropertyValue;
            }

            $entity->$propertyName = $relationEntity;
            return;
        }

        if (is_object($value) && $property->wrapper == ManyHasOne::class) {
            $entity->$propertyName = $value;
            return;
        }

        $entity->$propertyName = null;
    }

    /** Multiplier entity data setter */
    protected function setMultiplierProperty(IEntity $entity, PropertyMetadata $property, Multiplier $multiplier): void
    {
        /** @var self $relationRepository */
        $relationRepository = $this->getModel()->getRepository($property->relationship->repository);
        $ids = [];
        /** @var Container $container */
        foreach ($multiplier->getContainers() as $container) {
            $containerValues = $container->getValues(true);
            $id = $containerValues['id'];
            unset($containerValues['id']);
            $containerValues[$property->relationship->property] = $entity->id;
            $multiplierEntity = $id
                ? $relationRepository->updateEntity($id, $container, $containerValues)
                : $relationRepository->insertEntity($container, $containerValues);
            $ids[] = $multiplierEntity->id;
        }

        foreach ($entity->{$property->name}->toCollection()->findBy(['id!=' => $ids]) as $deleteEntity) {
            $relationRepository->removeAndFlush($deleteEntity);
        }
    }

    /**
     * Set empty property
     * @param IEntity $entity
     * @param PropertyMetadata $property
     */
    protected function setEmptyProperty(IEntity $entity, PropertyMetadata $property): void
    {
        $propertyName = $property->name;

        if ($property->isNullable) {
            $entity->$propertyName = null;
            return;
        }

        if (array_key_exists("string", $property->types)) {
            $entity->$propertyName = '';
            return;
        }

        if (array_key_exists("array", $property->types)) {
            $entity->$propertyName = [];
            return;
        }

        $entity->$propertyName = 0;
    }

    /**
    * Get data for datagrid
    * @param array $filter
    * @param array $order
    * @param Paginator|NULL $paginator
    * @return DbalCollection
    */
    public function getDataForDatagrid(
        array $filter,
        array $order,
        Paginator $paginator = null
    ): DbalCollection {
        // remove text placeholders
        foreach (array_keys($filter) as $filterName) {
            if (preg_match('/text_/', $filterName)) {
                unset($filter[$filterName]);
            }
        }
        $dbalCollection = $this->getMapper()->toCollection($this->getMapper()
            ->findByLikeForDatagrid($filter, $order)
            ->groupBy($this->getMapper()->getTableName() . '.id'));

        if ($paginator) {
            $dbalCollection = $dbalCollection->limitBy($paginator->getItemsPerPage(), $paginator->getOffset());
        }

        return $dbalCollection;
    }

    /**
     * Get count of items by given filter
     * @param array filter
     * @return int count
     */
    public function getCount(array $filter): int
    {
        // remove text placeholders
        foreach (array_keys($filter) as $filterName) {
            if (preg_match('/text_/', $filterName)) {
                unset($filter[$filterName]);
            }
        }
        $data = $this->getMapper()->toCollection($this->getMapper()
            ->findByLikeForDatagrid($filter)
            ->groupBy($this->getMapper()->getTableName() . '.id'));

        return $data->countStored();
    }

    /**
     * Help function, shorter use beginTransaction
     */
    public function beginTransaction(): void
    {
        $this->getMapper()->getConnection()->beginTransaction();
    }

    /**
     * Help function, shorter use commitTransaction
     */
    public function commitTransaction(): void
    {
        $this->getMapper()->getConnection()->commitTransaction();
    }

    /**
     * Help function, shorter use rollbackTransaction
     */
    public function rollbackTransaction(): void
    {
        $this->getMapper()->getConnection()->rollbackTransaction();
    }
}
