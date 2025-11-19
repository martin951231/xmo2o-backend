<?php
/**
 * @author : liukezhu
 * @date : 2022/7/15
 */
namespace app\community\model\db;
use think\Model;
use think\Db;
class HouseVillageMonthParkNoticeRecord extends Model{

    public function getFind($where,$field=true,$order='id desc')
    {
        $info = $this->where($where)->field($field)->order($order)->find();
        return $info;
    }


    public function addOne($data)
    {
        $res = $this->insert($data,true);
        return $res;
    }

    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    public function addAll($data){
        $result = $this->insertAll($data);
        return $result;
    }


}