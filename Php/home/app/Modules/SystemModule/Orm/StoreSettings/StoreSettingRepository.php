<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Orm\StoreSettings;

use App\Core\Orm\BaseRepository;

class StoreSettingRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [StoreSetting::class];
    }

    public function createSetting(string $name, ?string $value, int $storeId = null): StoreSetting
    {
        return $this->insertEntity(null, [
            'name' => $name,
            'value' => $value,
            'store' => $storeId
        ]);
    }
}
