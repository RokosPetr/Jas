<?php
declare(strict_types = 1);

namespace App\Core\Component\Datagrid;

use Nextras\Datagrid\Column;
use Nextras\Datagrid\Datagrid;

/**
 * Column for datagrid
 */
class BaseColumn extends Column
{
    /** @var BaseDatagrid */
    protected $grid;

    /** Column align */
    public string $align = 'left';

    /** Column number format */
    public array $numberFormat = [];

    /** String for date format */
    public ?string $dateFormat = null;

    /** Should this column be included in export */
    public bool $export = true;

    /** Allow show tooltip in TH cell */
    public ?string $tooltipText = null;

    /** Set column align right */
    public function alignRight(): self
    {
        $this->align = 'right';
        return $this;
    }

    public function numberFormat(int $decimals = 0, string $decimalSeparator = ',', string $thousandsSeparator = ' '): self
    {
        $this->numberFormat = [$decimals, $decimalSeparator, $thousandsSeparator];
        return $this;
    }

    /** Set date format */
    public function dateFormat(string $type = FULL): self
    {
        $this->dateFormat = $type;
        return $this;
    }

    /** Return date format */
    public function getDateFormat(): string
    {
        return dateFormatter('cs_CZ', $this->dateFormat);
    }

    /** Check if is date format set */
    public function isSetDateFormat(): bool
    {
        return (bool) $this->dateFormat;
    }

    /** Exclude column from export */
    public function disableExport(): self
    {
        $this->export = false;
        return $this;
    }

    /** Allow show tooltip in TH cell */
    public function showHeadTooltip(string $tooltipText): self
    {
        $this->tooltipText = $tooltipText;
        return $this;
    }

    public function isAsc(): bool
    {
        return $this->hasOrder($this->name, Datagrid::ORDER_ASC);
    }

    public function isDesc(): bool
    {
        return $this->hasOrder($this->name, Datagrid::ORDER_DESC);
    }

    /** Return bool if is desc or asc */
    public function hasOrder(string $columnName, string $orderType): bool
    {
        return ($this->grid->orderColumn === $columnName && $this->grid->orderType === $orderType);
    }

    /** Enable column sorting ability */
    public function enableSort($defaultOrderType = null): self
    {
        if ($defaultOrderType !== null) {
            $this->grid->settings->defaultOrderColumn = $this->name;
            $this->grid->settings->defaultOrderType = $defaultOrderType;
        }
        return parent::enableSort($defaultOrderType);
    }

    /** Get new order state */
    public function getNewState(): string
    {
        if (
            $this->isDesc()
            && $this->grid->settings->defaultOrderColumn === $this->name
            && $this->grid->settings->defaultOrderType === Datagrid::ORDER_DESC
        ) {
            return Datagrid::ORDER_ASC;
        }
        return parent::getNewState();
    }
}
