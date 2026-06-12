<?php
declare(strict_types = 1);

namespace App\Core\Utils\ConditionParser;

/**
 * Unary expression tree
 * only NOT and EMPTY operators implemented
 */
class Unary implements OperatorInterface
{
    protected OperatorInterface $subExpr;

    /** @var string|int - PHP token operator */
    protected $operator;

    /**
     * @param string|int $operator
     * @param OperatorInterface $subExpr
     */
    public function __construct($operator, OperatorInterface $subExpr)
    {
        $this->operator = $operator;
        $this->subExpr = $subExpr;
    }

    /** Evaluate expression recursively */
    public function evaluate(\ArrayAccess $variables)
    {
        switch ($this->operator) {
            case T_EMPTY:
                return ! count($this->subExpr->evaluate($variables));
            case '!':
                return ! $this->subExpr->evaluate($variables);
            default:
                throw new \Exception("Unary operator $this->operator not implemented");
        }
    }

    public function __toString(): string
    {
        switch ($this->operator) {
            case T_EMPTY:
                return "(empty($this->subExpr))";
            case '!':
                return "(! $this->subExpr)";
            default:
                // toString() is not allowed to throw exceptions
                return "($this->operator($this->subExpr))";
        }
    }
}
