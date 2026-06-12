<?php
declare(strict_types = 1);

namespace App\Core\Component\Datagrid;

use App\Core\Utils\ConditionParser\ConditionParser;
use App\Core\Utils\ConditionParser\OperatorInterface;
use Nette\SmartObject;

class GridLegend
{
    use SmartObject;

    public string $label;

    /** Legend class (for color mostly) */
    public string $class = '';

    /** Legend condition */
    public string $condition = '';

    /** Parsed expression form of the condition */
    public OperatorInterface $expresion;

    public function __construct(string $label = "", string $class = '', string $condition = '')
    {
        $this->label = $label;
        $this->class = $class;
        $this->setCondition($condition);
    }

    public function setName(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function setClass(string $class): self
    {
        $this->class = $class;
        return $this;
    }

    public function setCondition(string $condition): self
    {
        $this->condition = $condition;
        $this->expresion = ConditionParser::parse($condition);
        return $this;
    }
}
