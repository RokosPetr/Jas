<?php
declare(strict_types = 1);

namespace App\Core\Exporter;

use Nette\Application\Response;
use Nette\InvalidArgumentException;
use Nextras\Orm\Entity\IEntity;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use XSuchy09\Application\Responses\CsvResponse;

/**
 * Service for generating exports from templates
 */
class Exporter
{
    const TYPE_CSV = 'csv';
    const TYPE_XLS = 'xls';

    public function arrayToExcelSheets(
        array $data,
        string $filename = 'export',
        bool $autosize = true,
        bool $forDownload = true
    ): SpreadsheetResponse {

        $spreadsheet = new Spreadsheet();
        $spreadsheet->disconnectWorksheets();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

        foreach ($data as $sheetLabel => $sheetData) {
            if (!is_array($sheetData)) {
                throw new \PhpOffice\PhpSpreadsheet\Exception(
                    'Invalid input data format, expected array, got ' . gettype($sheetData)
                );
            }
            $objWorkSheet = $spreadsheet->createSheet();
            $objWorkSheet->fromArray($sheetData, null, 'A1', true);

            if ($autosize === true) {
                foreach ($objWorkSheet->getColumnIterator() as $col => $column) {
                    if ($column) {
                        $objWorkSheet->getColumnDimension($col)->setAutoSize(true);
                    }
                }
            }

            $objWorkSheet->setTitle($sheetLabel);
        }

        $spreadsheet->setActiveSheetIndex(0);

        return new SpreadsheetResponse($spreadsheet, $filename, $forDownload);
    }

    public function arrayToCsv($data, string $filename = 'export.csv', string $glue = ';'): CsvResponse
    {
        $response = new CsvResponse($data, $filename);
        $response->setDelimiter($glue);
        $response->setOutputCharset('windows-1250');
        return $response;
    }

    public function exportFromDatagrid(array $gridItems, array $columns, string $type, string $filename): Response
    {
        if ($type === self::TYPE_XLS) {
            $result = [];
            $header = [];

            foreach ($columns as $column) {
                $header[] = $column->label;
            }

            $result[] = $header;

            foreach ($gridItems as $gridItem) {
                $r = [];

                foreach ($columns as $column) {
                    $r[] = $this->getDataFromGridItem($gridItem, $column->name);
                }

                $result[] = $r;
            }

            return $this->arrayToExcelSheets(['List 1' => $result], $filename);
        }

        if ($type === self::TYPE_CSV) {
            $result = [];

            foreach ($gridItems as $gridItem) {
                $line = [];

                foreach ($columns as $column) {
                    $line[$column->label] = $this->getDataFromGridItem($gridItem, $column->name);
                }

                $result[] = $line;
            }

            return $this->arrayToCsv($result, "$filename.csv");
        }

        throw new InvalidArgumentException("Invalid type for export $type");
    }

    private function getDataFromGridItem($gridItem, string $columnName)
    {
        $value = null;
        $sqlMethodName = 'getExport' . ucfirst($columnName);

        // process additional conditions for virtual columns
        if (method_exists($gridItem, $sqlMethodName)) {
            return $gridItem->$sqlMethodName();
        }

        $gridColumn = $gridItem->{$columnName};

        if (is_object($gridColumn)) {
            $className = get_class($gridColumn);

            if ($gridColumn instanceof \DateTimeInterface) {
                $value = $gridColumn->format('d.m.Y H:i');
            } elseif ($gridColumn instanceof IEntity) {
                if (isset($gridColumn->name)) {
                    $value = $gridColumn->name;
                } elseif (method_exists($gridColumn, '__toString')) {
                    $value = $gridColumn->__toString();
                } else {
                    throw new \Exception(
                        'Class (' . $className . ') has not name property or __toString function for grid column.'
                    );
                }
            } else {
                throw new \Exception('Invalid data type (' . $className . ') for grid column.');
            }
        } else {
            $value = $gridColumn;
        }

        return $value;
    }
}
