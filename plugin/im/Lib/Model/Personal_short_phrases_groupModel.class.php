<?php

class Personal_short_phrases_groupModel extends Model
{
    public function delById($id)
    {
        if ($id < 1) {
            return true;
        }
        $this->where(array('id' => $id))->delete();
        M('Personal_short_phrases')->where(array('group_id' => $id))->delete();
        return true;
    }

    public function getPhrases($kefuId)
    {
        $phrases = array();
        if ($kefuId > 0) {
            $lists = $this->alias('g')->join(C('DB_PREFIX') . 'personal_short_phrases AS p ON g.id = p.group_id')
                ->field('g.id AS group_id,g.name AS group_name,p.id AS phrase_id,p.context,p.sort,g.sort AS g_sort,p.sort AS p_sort')
                ->where(array('g.kefu_id' => $kefuId))
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