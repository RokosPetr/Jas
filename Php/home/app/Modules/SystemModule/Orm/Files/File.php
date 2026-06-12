<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Orm\Files;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Traits\CreatableTrait;
use Nette\Utils\Image;

/**
 * @property int                       $id             {primary}
 * @property string                    $name
 * @property int                       $size
 * @property string|null               $mimeType
 * @property string                    $path
 * @property string|null               $webPath
 *
 * @property-read string|null          $src            {virtual}
 * @property-read string               $link           {virtual}
 */
class File extends BaseEntity
{
    use CreatableTrait;

    public function getterSrc(): ?string
    {
        if ($this->webPath) {
            return "$this->webPath/$this->name";
        }
        return null;
    }

    public function getterLink(): string
    {
        return ROOT_DIR . "/$this->path/$this->name";
    }

    public function thumbnail(int $width = null, int $quality = 100): ?string
    {
        if (!$this->webPath) {
            return null;
        }

        $filename = "thumbnail_$width" . "_$quality" . "_$this->name";
        $link = ROOT_DIR . "/$this->path/$filename";
        $src = "$this->webPath/$filename";

        if (!file_exists($link)) {
            $image = Image::fromFile($this->link);
            if ($width) {
                $image->resize($width, null);
            }
            $image->save($link, $quality);
            unset($image);
        }

        return $src;
    }
}
