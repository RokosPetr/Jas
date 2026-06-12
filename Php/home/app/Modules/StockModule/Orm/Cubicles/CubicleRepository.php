<?php
declare(strict_types=1);

namespace App\Modules\StockModule\Orm\Cubicles;

use App\Core\Orm\BaseRepository;
use Nextras\Orm\Collection\ICollection;

class CubicleRepository extends BaseRepository
{
    public const IMAGE_DIR = '/www/upload/cubicles';

    static function getEntityClassNames(): array
    {
        return [Cubicle::class];
    }

    public function loadNewCodeNumber(int $code): int
    {
        $lastNumber = $this->findBy(['codeFirstPart' => $code])
                ->orderBy('codeSecondPart', ICollection::DESC)
                ->fetch()->codeSecondPart ?? 0;
        return $lastNumber + 1;
    }
}
