<?php
declare(strict_types = 1);

namespace App\Core\Component\Datagrid;

use Nette\MemberAccessException;
use Nette\Utils\Callback;
use Nextras\Datagrid\Datagrid;

/**
 * Custom datagrid setting
 * - property anotation is set because PhpStorm notification
 *
 * @property-read $datagrid
 * @property-read $dataSourceFilter
 * @property-read $addFormCallback
 * @property-read $addFormFactory
 * @property-read $shownCheckboxes
 * @property-read $checkboxesCondition
 * @property-read $onCheckCallback
 * @property-read $showExports
 * @property-read $showSettings
 * @property-read $showFulltextTooltip
 * @property-read $exportType
 * @property-read $templateFile
 * @property-read $itemsPerPage
 * @property-read $fulltextColumns
 * @property-read $defaultTranslates
 * @property-read $suffixDateTime
 * @property-read $suffixDate
 * @property-read $suffixTime
 * @property-read $suffixIntervalTexts
 * @property-read $suffixIntervalIntegers
 * @property-read $formDateTime
 * @property-read $formDate
 * @property-read $formTime
 * @property-read $formIntervalTexts
 * @property-read $formIntervalIntegers
 * @property-read $instanceParams
 * @property-read $showPaginator
 * @property-read $floatedTable
 * @property-read $exportFilename
 * @property-read $exportHandle
 * @property-read $forceOrder
 */
class DatagridSetting
{
    protected Datagrid $datagrid;

    protected array $dataSourceFilter = [];

    /** Callback function to Add button in datagrid inline adding */
    protected string $addFormCallback;
    protected string $addFormFactory;

    /** Condition for dynamic hiding of checkboxes for mome rows */
    protected ?string $checkboxesCondition;

    protected bool $showExports = false;
    protected bool $showFulltextTooltip = true;
    protected ?string $exportHandle = null;
    protected string $exportType = 'xls';
    protected string $exportFilename = 'export';
    protected string $templateFile = __DIR__ . '/templates/Datagrid.latte';

    /** @var string $defaultOrderColumn */
    public string $defaultOrderColumn = 'id';

    /** @var string $defaultOrderType */
    public string $defaultOrderType = Datagrid::ORDER_ASC;

    /** Allowed presets for page size */
    protected array $itemsPerPage = [
        10 => 10,
        20 => 20,
        50 => 50,
        100 => 100
    ];

    /**
     * Default set of translation keys, can be overriden or set from constructor
     * @var array $defaultTranslates
     */
    protected array $defaultTranslates = [
        'empty_set' => 'Žádné záznamy zde nebyly doposud vytvořeny.',
        'choose' => 'Vybrat',
        'records_selected' => 'Počet vybraných řádků',
        'empty_filter_set' => 'Zadaným filtrům nevyhovuje žádný záznam',
        'exportGrid' => 'Exportovat',
        'clear_selected_checkboxs' => 'Zrušit výběr',
        'info_button' => 'Rychlé vyhledávání nad',
        'search' => 'Vyhledat',
        'legend' => 'Legenda',
        'recordsCountShort' => 'Zobrazených záznamů',
        'of' => 'z',
        'delete' => 'Smazat',
        'cancel' => 'Zrušit',
        'filtering' => 'Filtrování',
        'filter' => 'Rozšířený filtr',
    ];

    /** Set of fulltext columns */
    protected array $fulltextColumns = [];

    /** Checked suffixes for date time filters */
    protected array $suffixDateTime = ["_DateTimeFrom", "_DateTimeTo"];

    /** Checked suffixes for date filters */
    protected array $suffixDate = ["_DateFrom", "_DateTo"];

    /** Checked suffixes for time filters */
    protected array $suffixTime = ["_TimeFrom", "_TimeTo"];

    /** Checked suffix for interval texts filters */
    protected array $suffixIntervalTexts = ["_TextFrom", "_TextTo"];

    /** Checked suffix for interval integers filters */
    protected array $suffixIntervalIntegers = ["_IntegerFrom", "_IntegerTo"];

    /** date time columns filters */
    protected array $formDateTime = [];

    /** date columns filters */
    protected array $formDate = [];

    /** time columns filters */
    protected array $formTime = [];

    /** interval text filters */
    protected array $formIntervalTexts = [];

    /** interval integer filters */
    protected array $formIntervalIntegers = [];

    /** Instance specific parameters (to be used in template) */
    protected array $instanceParams = [];

    protected bool $showPaginator = true;
    protected bool $shownCheckboxes = false;
    protected bool $showSettings = true;
    protected bool $floatedTable = true;
    protected $onCheckCallback = null;
    protected array $forceOrder = [];

    public function __construct(Datagrid $datagrid)
    {
        $this->datagrid = $datagrid;
    }

    /**
     * Magic getter - first look for function
     * @param string $property
     * @return DatagridSetting|mixed
     */
    public function __get(string $property)
    {
        $method = 'get' . ucfirst($property);
        if (is_callable([$this, $method])) {
            return $this->$method();
        }
        if (property_exists($this, $property)) {
            return $this->$property;
        }
        return $this;
    }

    public function __set(string $property, $value): void
    {
        throw new MemberAccessException("This property ($property) can't be set to $value - use proper method.");
    }

    public function __isset(string $property): void
    {
        throw new MemberAccessException("This property ($property) can't be checked - use proper method to set/get.");
    }

    /** Set datasource filter - need for shown filtered entities, paginator & export function */
    public function setDataSourceFilter(array $filter): DatagridSetting
    {
        $this->dataSourceFilter = $filter;
        return $this;
    }

    /** Setting callback function to Add button in datagrid inline adding */
    public function setAddFormCallback(callable $addFormCallback): DatagridSetting
    {
        Callback::check($addFormCallback);
        $this->addFormCallback = $addFormCallback;
        return $this;
    }

    /** Setting function returning Nette\Forms\Container to Add button in datagrid inline adding */
    public function setAddFormFactory(callable $addFormFactory): DatagridSetting
    {
        Callback::check($addFormFactory);
        $this->addFormFactory = $addFormFactory;
        return $this;
    }

    public function setTranslates(array $translates): self
    {
        $this->defaultTranslates = array_merge($this->defaultTranslates, $translates);
        return $this;
    }

    public function hidePaginator(): DatagridSetting
    {
        $this->showPaginator = false;
        return $this;
    }

    public function hideSettings(): DatagridSetting
    {
        $this->showSettings = false;
        return $this;
    }

    /**
     * Set instance specific component parameter
     * @param string $name
     * @param mixed $value
     * @return DatagridSetting
     */
    public function setInstanceParameter(string $name, $value): DatagridSetting
    {
        $this->instanceParams[$name] = $value;
        return $this;
    }

    /**
     * Set instance specific component parameter
     * @param string $property
     * @param mixed $value
     * @return DatagridSetting
     */
    public function setFormFiltersWithSuffix(string $property, $value): DatagridSetting
    {
        $this->$property[] = $value;
        return $this;
    }

    /**
     * Get instance specific component parameter
     * @param string $name
     * @return mixed|null
     */
    public function getInstanceParameter(string $name)
    {
        return $this->instanceParams[$name] ?? null;
    }

    public function setItemsPerPage(array $items): DatagridSetting
    {
        $this->itemsPerPage = $items;
        return $this;
    }

    public function setFulltextColumns(array $columns): DatagridSetting
    {
        $this->fulltextColumns = $columns;
        return $this;
    }

    public function showCheckboxes(string $condition = null): DatagridSetting
    {
        $this->shownCheckboxes = true;
        $this->checkboxesCondition = $condition;
        return $this;
    }

    public function showExport(string $type = 'xls', string $filename = 'export', string $handle = null): DatagridSetting
    {
        $this->showExports = true;
        $this->exportType = $type;
        $this->exportFilename = $filename;
        $this->exportHandle = $handle;
        return $this;
    }

    public function disableFloatedTable(): DatagridSetting
    {
        $this->floatedTable = false;
        return $this;
    }

    public function setTemplateFile(string $template): DatagridSetting
    {
        $this->templateFile = $template;
        return $this;
    }

    public function setOnCheckCallback(callable $callback): self
    {
        $this->onCheckCallback = $callback;
        return $this;
    }

    public function setForceOrder(array $order): DatagridSetting
    {
        $this->forceOrder = $order;
        return $this;
    }

    public function hideFulltextTooltip(): self
    {
        $this->showFulltextTooltip = false;
        return $this;
    }
}
