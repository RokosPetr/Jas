<?php
declare(strict_types=1);

namespace App\Core\Exporter;

use Nette\Application\Response;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\SmartObject;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class SpreadsheetResponse implements Response
{
    use SmartObject;

    public Spreadsheet $spreadsheet;
    public string $filename;
    public bool $forceDownload = false;

    public function __construct(Spreadsheet $spreadsheet, string $filename = null, bool $forceDownload = false)
    {
        $this->spreadsheet = $spreadsheet;
        $this->filename = $filename ?: 'newfile';
        $this->forceDownload = $forceDownload;
    }

    public function send(IRequest $httpRequest, IResponse $httpResponse): void
    {
        $httpResponse->setContentType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $httpResponse->setHeader(
            'Content-Disposition',
            ($this->forceDownload ? 'attachment; ' : '')
            . 'filename="' . $this->filename . '.xlsx"'
        );
        $httpResponse->setHeader('Content-Transfer-Encoding', 'binary');
        $objWriter = IOFactory::createWriter($this->spreadsheet, 'Xlsx');
        $objWriter->save('php://output');
    }
}
