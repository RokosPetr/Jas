<?php
declare(strict_types=1);

namespace App\Core\Orm;

use App\Core\Utils\DateTime;
use App\Modules\SystemModule\Orm\Users\User;
use Nextras\Orm\Entity\Entity;
use Nextras\Dbal\Utils\DateTimeImmutable;

abstract class BaseEntity extends Entity implements \ArrayAccess
{
    protected function getSysUser(): ?User
    {
        return $this->getRepository()->getModel()->getSysUser();
    }

    /** Property setter - Can skip modification if the value is not changing */
    public function setValue(string $name, $value): Entity
    {
        if ((is_string($value) || is_int($value)) && isset($this->id)) {
            $valueToCompare = $this->{$name};

            if ($valueToCompare instanceof DateTimeImmutable) {
                $valueToCompare = $valueToCompare->format(DateTime::DB_DATE);
            }

            if ($valueToCompare == $value) {
                return $this;
            }
        }

        return parent::setValue($name, $value);
    }

    /**
     * Call beforePersist for set created columns, check if inserting first
     * --onBeforeInsert is called to late for this
     */
    public function onBeforePersist(): void
    {
        parent::onBeforePersist();

        if ($this->getMetadata()->hasProperty('createdAt') && empty($this->createdAt)) {
            $this->createdAt = new DateTimeImmutable();
        }

        if ($this->getMetadata()->hasProperty('createdBy') && empty($this->createdBy)) {
            $this->createdBy = $this->getSysUser();
        }
    }

    /** Call beforeUpdate for set updated columns */
    public function onBeforeUpdate(): void
    {
        parent::onBeforeUpdate();

        if ($this->getMetadata()->hasProperty('updatedAt')) {
            $sysUser = $this->getSysUser();

            if ($sysUser) {
                $this->updatedAt = new DateTimeImmutable();
                $this->updatedBy = $this->getSysUser();
            }
        }
    }

    public function offsetExists($offset) : bool
    {
        return $this->getMetadata()->hasProperty($offset);
    }

    public function offsetGet($offset)
    {
        return $this->getValue($offset);
    }

    public function offsetSet($offset, $value) : void
    {
        throw new \Nextras\Orm\Exception\NotImplementedException('Entity array access is read only');
    }

    public function offsetUnset($offset) : void
    {
        throw new \Nextras\Orm\Exception\NotImplementedException('Entity array access is read only');
    }
}
