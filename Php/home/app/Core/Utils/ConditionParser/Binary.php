<?php
declare(strict_types=1);

namespace App\Core\Utils\ConditionParser;

/** Binary expression tree */
class Binary implements OperatorInterface
{
    protected OperatorInterface $lhs;
    protected OperatorInterface $rhs;

    /** @var string|int - PHP token operator */
    protected $operator;

    /**
     * @param string|int $operator
     * @param OperatorInterface $lhs
     * @param OperatorInterface $rhs
     */
    public function __construct($operator, OperatorInterface $lhs, OperatorInterface $rhs)
    {
        $this->operator = $operator;
        $this->lhs = $lhs;
        $this->rhs = $rhs;
    }

    /** Evaluate expression tree recursively */
    public function evaluate(\ArrayAccess $variables)
    {
        switch ($this->operator) {
            case T_BOOLEAN_AND:
                return $this->lhs->evaluate($variables) && $this->rhs->evaluate($variables);
            case T_BOOLEAN_OR:
                return $this->lhs->evaluate($variables) || $this->rhs->evaluate($variables);
            case '<':
                return $this->lhs->evaluate($variables) < $this->rhs->evaluate($variables);
            case '>':
                return $this->lhs->evaluate($variables) > $this->rhs->evaluate($variables);
            case T_IS_SMALLER_OR_EQUAL:
                return $this->lhs->evaluate($variables) <= $this->rhs->evaluate($variables);
            case T_IS_GREATER_OR_EQUAL:
                return $this->lhs->evaluate($variables) >= $this->rhs->evaluate($variables);
            case T_IS_EQUAL:
                return $this->lhs->evaluate($variables) == $this->rhs->evaluate($variables);
            case T_IS_NOT_EQUAL:
                return $this->lhs->evaluate($variables) != $this->rhs->evaluate($variables);
            case T_OBJECT_OPERATOR:
                return $this->rhs->evaluate($this->lhs->evaluate($variables));
            default:
                throw new \Exception("Operator $this->operator not implemented");
        }
    }

    /** Generate infix notation from expression tree */
    public function __toString(): string
    {
        switch ($this->operator) {
            case T_BOOLEAN_AND:
                return "($this->lhs && $this->rhs)";
            case T_BOOLEAN_OR:
                return "($this->lhs || $this->rhs)";
            case T_IS_SMALLER_OR_EQUAL:
                return "($this->lhs <= $this->rhs)";
            case T_IS_GREATER_OR_EQUAL:
                return "($this->lhs >= $this->rhs)";
            case T_IS_EQUAL:
                return "($this->lhs == $this->rhs)";
            case T_IS_NOT_EQUAL:
                return "($this->lhs != $this->rhs)";
            case T_OBJECT_OPERATOR:
                return "($this->lhs->$this->rhs)";
            default:
                // toString() is not allowed to throw exceptions
                return "($this->lhs $this->operator $this->rhs)";
        }
    }
}
