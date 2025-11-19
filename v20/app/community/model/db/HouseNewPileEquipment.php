<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * @date_time: 2022/09/09 13:46
 */
namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseNewPileEquipment extends Model{

    /**
     * 获取指定设备信息
     * @author:zhubaodi
     * @date_time: 2022/09/09 13:46
     * @param array $where
     * @param bool $field
     */
    public function getInfo($where,$field=true){
        $info = $this->where($where)->field($field)->find();
        return $info;
    }

    /**
     * 充电桩数量
     * @author:zhubaodi
     * @date_time: 2022/09/09 13:46
     * @param array $where
     * @return int
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * 获取订单列表
     * @author:zhubaodi
     * @date_time: 2022/09/09 13:46
     */
    public function getList($where,$field=true,$page=0,$limit=20,$order='id DESC') {
        $list = $this->field($field)->where($where);
        if($page)
            $list = $list->order($order)->page($page,$limit)->select();
        else
            $list = $list->select();
        return $list;
    }

    /**
     *编辑数据
     * @author:zhubaodi
     * @date_time: 2022/9/9 13:27
     * @param $where
     * @param $save
     * @return mixed
     */
    public function save_one($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }


    /**
     * 添加数据
     * @author:zhubaodi
     * @date_time: 2022/9/9 13:27
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        $res = $this->insertGetId($data);
        return $res;
    }

}
