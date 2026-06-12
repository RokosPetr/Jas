<?php
declare(strict_types=1);

namespace App\Modules\BathroomModule\Orm\Bathrooms;

use App\Core\Orm\BaseEntity;
use App\Core\Orm\Expresion\DbColumn;
use App\Core\Orm\Expresion\DbFunction;
use App\Core\Orm\Traits\DeletableTrait;
use App\Modules\BathroomModule\Orm\Parameters\BathOption;
use App\Modules\SystemModule\Orm\Files\File;
use Nextras\Orm\Relationships\ManyHasMany;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * @property int                       $id             {primary}
 * @property string                    $name
 * @property int                       $priority
 * @property int                       $virtualPictureFocus
 * @property int                       $linkPicturePosition
 *
 * @property ManyHasMany|BathOption[]  $options        {m:m BathOption::$bathrooms, isMain=true}
 * @property OneHasMany|BathPicture[]  $pictures       {1:m BathPicture::$bathroom}
 * @property OneHasMany|BathRating[]   $ratings        {1:m BathRating::$bathroom}
 * @property OneHasMany|BathItemLink[] $itemLinks      {1:m BathItemLink::$bathroom}
 *
 * @property-read string               $optionList     {virtual}
 * @property-read File|null            $mainPicture    {virtual}
 * @property-read File|null            $linkPicture    {virtual}
 * @property-read File|null            $virtualPicture {virtual}
 * @property-read float                $averageRating  {virtual}
 * @property-read int                  $itemLinksCount {virtual}
 */
class Bathroom extends BaseEntity
{
    use DeletableTrait;

    public function getterOptionList(): string
    {
        return implode(
            ', ',
            $this->options->toCollection()
                ->orderBy('parameter->order')
                ->orderBy('order')
                ->fetchPairs(null, 'name')
        );
    }

    public function getSqlOptionList(): DbFunction
    {
        return new DbFunction(
            'bath_get_option_list',
            new DbColumn('id')
        );
    }

    public function getterMainPicture(): ?File
    {
        return $this->pictures->toCollection()->getBy(['position' => 1])->picture ?? null;
    }

    public function getterLinkPicture(): ?File
    {
        return $this->pictures->toCollection()->getBy(['position' => $this->linkPicturePosition])->picture ?? null;
    }

    public function getterVirtualPicture(): ?File
    {
        return $this->pictures->toCollection()->getBy(['position' => BathPictureRepository::POSITION_3D])->picture ?? null;
    }

    public function getterAverageRating(): float
    {
        $ratingCount = $this->ratings->countStored();
        return $ratingCount
            ? array_sum($this->ratings->toCollection()->fetchPairs(null, 'rating')) / $ratingCount
            : 0;
    }

    public function getSqlAverageRating(): DbFunction
    {
        return new DbFunction(
            'AVG',
            new DbColumn('ratings->rating')
        );
    }

    public function getterItemLinksCount(): int
    {
        return $this->itemLinks->countStored();
    }

    public function getSqlItemLinksCount(): DbFunction
    {
        return new DbFunction('COUNT', new DbColumn('itemLinks->id'));
    }
}