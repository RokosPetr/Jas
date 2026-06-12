<?php
declare(strict_types = 1);

namespace App\Core\Utils\ConditionParser;

/** Variable expression */
class Variable implements OperatorInterface
{
    protected string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /** Return variable value */
    public function evaluate(\ArrayAccess $variables)
    {
        if (!$variables->offsetExists($this->name)) {
            throw new \InvalidArgumentException("Variable '$this->name' was not defined");
        }
        return $variables[$this->name];
    }

    public function __toString(): string
    {
        return '$' . $this->name;
    }
}
