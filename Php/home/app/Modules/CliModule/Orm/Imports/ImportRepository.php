<?php
declare(strict_types=1);

namespace App\Modules\CliModule\Orm\Imports;

use App\Core\Orm\BaseRepository;

class ImportRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [Import::class];
    }

    public function getImportByName(string $name): ?Import
    {
        return $this->getBy(['name' => $name]);
    }
}
