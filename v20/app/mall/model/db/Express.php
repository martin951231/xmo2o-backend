<?php
/**
 * Express.php
 * 快递model
 * Create on 2020/9/19 16:28
 * Created by zhumengqun
 */

namespace app\mall\model\db;

use think\Model;

class Express extends Model
{
    /**
     * 获取可用快递列表
     * @return array
     */
    public function getExpress($field, $where)
    {
        return $this->field($field)->where($where)->select()->toArray();
    }

    /**
     * @param $where
     * 获取一个
     */
    public function getOne($where)
    {
        $arr = $this->field(true)->where($where)->find();
        if (!empty($arr)) {
           return  $arr->toArray();
        }else{
            return [];
        }
    }
}