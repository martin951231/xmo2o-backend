<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2020/4/26 09:44
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseVillagePileEquipmentAttribute extends Model{

    /**
     * 获取单个设备属性数据信息
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     * @author: zhubaodi
     * @date_time: 2021/4/26 19:52
     */
    public function getOne($where, $field = true)
    {
        $info = $this->field($field)->where($where)->find();
        return $info;
    }
}
