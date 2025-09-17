<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2020/4/26 09:44
 */
namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseVillagePileMonthCard extends Model{

    /**
     * 获取月卡列表
     * @author:zhubaodi
     * @date_time: 2021/4/26 19:52
     */
    public function getList($where,$field=true,$page=1,$limit=20,$order='id DESC',$type=0) {
        $list = $this->field($field)->where($where);
        if($type)
            $list = $list->order($order)->page($page,$limit)->select();
        else
            $list = $list->select();
        return $list;
    }

    /**
     * 获取单个数据信息
     * @author: zhubaodi
     * @date_time: 2021/4/27 19:52
     * @param array $where 查询条件
     * @param bool|string $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function getOne($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }
}
