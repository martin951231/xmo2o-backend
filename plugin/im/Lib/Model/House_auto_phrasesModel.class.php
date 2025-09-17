<?php


class House_auto_phrasesModel extends Model
{
    /**
     * 匹配关键字回复
     * @author lijie
     * @date_time 2021/10/20
     * @param string $keyword
     * @param int $village_id
     * @return array|bool|mixed|null
     */
    public function getKeyReply($keyword='',$village_id=0)
    {
        if(empty($keyword) || !$village_id)
            return [];
        $where['village_id'] = $village_id;
        $where['key'] = $keyword;
        return $this->where($where)->order('id DESC')->find();
    }
}