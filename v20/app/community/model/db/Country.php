<?php
/**
 * 国家
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/5/8 13:27
 */

namespace app\community\model\db;


use think\Model;
use think\facade\Db;
class Country extends Model{


    /**
     * 获取国家信息
     * @author: wanziyang
     * @date_time: 2020/5/8 13:33
     * @param array $where
     * @param bool|string $field
     * @param string $order
     * @return \think\Collection
     */
    public function getList($where,$field =true,$order='sort DESC') {
        $list = $this->field($field)->where($where)->order($order)->select();
        return $list;
    }
}