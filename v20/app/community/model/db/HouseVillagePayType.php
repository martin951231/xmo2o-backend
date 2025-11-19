<?php
/**
 *  社区线下支付方式
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/27 15:38
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseVillagePayType extends Model{

    /**
     * 条件查询社区线下支付方式
     * @author: wanziyang
     * @date_time: 2020/4/27 15:38
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @param string $order 排序
     * @return array|null|Model
     */
    public function get_list($where,$field=true,$order='id DESC') {
        $list = $this->field($field)->where($where)->order($order)->select();
        return $list;
    }

}