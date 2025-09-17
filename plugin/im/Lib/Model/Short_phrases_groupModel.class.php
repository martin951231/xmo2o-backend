<?php


class Short_phrases_groupModel extends Model
{
    const TYPE_TEAM = 1;

    const TYPE_PERSONAL = 2;

    public function checkType($type)
    {
        return in_array($type, array(self::TYPE_PERSONAL, self::TYPE_TEAM));
    }

    public function getPhrases($storeId,$belong='')
    {
        $phrases = array();
        if ($storeId >= 0) {
            $lists = $this->alias('g')->join(C('DB_PREFIX') . 'short_phrases AS p ON g.id = p.group_id')
                ->field('g.id AS group_id,g.name AS group_name,p.id AS phrase_id,p.context,g.sort AS g_sort,p.sort AS p_sort')
                ->where(array('g.store_id' => $storeId, 'g.belong' => $belong))
                ->order('g.sort DESC,p.sort DESC')
                ->select();
            foreach ($lists as $l) {
                if (!isset($phrases[$l['group_id']])) {
                    $phrases[$l['group_id']] = array(
                        'id' => $l['group_id'],
                        'text' => $l['group_name'],
                        'sort' => $l['g_sort'],
                        'children' => array()
                    );
                }
                if($l['phrase_id'] > 0){
                    $phrases[$l['group_id']]['children'][] = array(
                        'id' => $l['phrase_id'],
                        'text' => $l['context'],
                        'sort' => $l['p_sort']
                    );
                }
            }
            unset($lists);
        }
        return array_values($phrases);
    }
}