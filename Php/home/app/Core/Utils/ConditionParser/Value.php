<?php
declare(strict_types = 1);

namespace App\Core\Utils\ConditionParser;

/** Constant value expression */
class Value implements OperatorInterface
{
    /** @var mixed value */
    protected $value;

    /**
     * @param mixed $value constant value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /** Return value */
    public function evaluate(\ArrayAccess $variables)
    {
        return $this->value;
    }

    public function __toString(): string
    {
        if (is_bool($this->value)) {
            return $this->value ? 'true' : 'false';
        }
        if (is_string($this->value)) {
            return "'" . $this->value . "'";
        }

        return (string) $this->value;
    }
}
