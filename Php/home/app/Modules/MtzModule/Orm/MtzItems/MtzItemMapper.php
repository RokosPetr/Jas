<?php
declare(strict_types=1);

namespace App\Modules\MtzModule\Orm\MtzItems;

use App\Core\Orm\BaseMapper;

class MtzItemMapper extends BaseMapper
{
    /** @var string */
    protected $tableName = 'mtz_items';

    public function loadMtzTree(): array
    {
        $sql = "WITH RECURSIVE tmp (id, parent, sort, level, title, reg_number) AS
            (
              SELECT id, 0, CAST(LPAD(cast(`order` as varchar(5)), 5, '0') AS VARCHAR(100)), 0, name, 0
                FROM mtz_groups
                WHERE parent IS NULL
              UNION ALL
              SELECT l.id, l.parent, CONCAT(sort, LPAD(cast(`order` as varchar(100)), 5, '0')), level + 1, name, 0
                FROM tmp AS p JOIN mtz_groups AS l
                  ON p.id = l.parent
                WHERE level = 0
              UNION ALL
              SELECT l.id, l.`group`, CONCAT(sort, LPAD(cast(l.id as varchar(100)), 10, '0')), level + 1, name, l.reg_number
                FROM tmp AS p JOIN mtz_items AS l
                  ON p.id = l.`group`
                WHERE level = 1
            )
            SELECT * FROM tmp
            ORDER BY sort
        ";

        function tree($item, int $parent){
            if($item->parent == $parent){
                return $item;
            }
        }

        $mtzItems =  $this->getConnection()->query($sql)->fetchAll();
        $root = [];
        foreach ($mtzItems as $key => $level0) {
            if (tree($level0, 0)) {
                //$array_filter[$key] = $level0;
                $chidren = [];
                foreach ($mtzItems as $key1 => $level1) {
                    if (tree($level1, $level0->id)) {
                        $chidren2 = [];
                        foreach ($mtzItems as $key2 => $level2) {
                            if (tree($level2, $level1->id)) {
                                $chidren2[$key2] = ['id'=>$level2->id, 'title'=>$level2->title];
                            }
                        }
                        $chidren[$key1] = ['title' => $level1->title, 'children' => $chidren2];
                    }
                }
                $root[$key] = ['title' => $level0->title, 'children' => $chidren];
            }
        }

        return $root;
    }

}
