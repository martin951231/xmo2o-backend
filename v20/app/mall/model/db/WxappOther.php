<?php
/**
 * WxappOther.php
 * 平台小程序支持打开别的小程序列表model
 * Create on 2020/10/21 9:06
 * Created by zhumengqun
 */

namespace app\mall\model\db;

use \think\Model;

class WxappOther extends Model
{
    /**
     * 通过id获取一条记录
     * @param $field
     * @param $where
     * @return array
     */
    public function getById($field, $where)
    {
        $arr = $this->field($field)->where($where)->find();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * 获取所有小程序
     * @return array
     */
    public function getAll()
    {
        $arr = $this->field(true)->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }
}