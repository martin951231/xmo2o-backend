<?php


class House_village_detail_recordModel extends Model
{
    public function insertData($data)
    {
        $res = $this->data($data)->add();
        return $res;
    }

    /**
     * 判断相应的订单id是否已开电子发票
     * @author lijie
     * @date_time 2020/07/06
     * @param $where
     * @return int
     */
    public function is_record($where)
    {
        $data = $this->where($where)->find();
        if($data)
            return 2;
        return 1;
    }
}