<?php


class House_village_e_recordModel extends Model
{
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

    public function insertData($data)
    {
        $id = $this->data($data)->add();
        return $id;
    }
}