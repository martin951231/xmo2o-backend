<?php
/**
 *  业主每月的欠费详细表
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/27 9:20
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseVillageUserPaylist extends Model{

    /**
     * 条件查询业主每月的欠费详细表
     * @author: wanziyang
     * @date_time: 2020/4/27 9:48
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @param string $order 排序
     * @return array|null|Model
     */
    public function get_list($where,$field=true,$order='pigcms_id DESC') {
        $list = $this->field($field)->where($where)->order($order)->select();
        return $list;
    }

    /**
     * 修改欠费
     * @author lijie
     * @date_time 2020/09/30
     * @param $where
     * @param $data
     * @return int|string
     */
    public function saveOne($where,$data)
    {
        $res = $this->where($where)->save($data);
        return $res;
    }

    /**
     * 添加记录
     * @author lijie
     * @date_time 2020/11/02
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        $res = $this->insert($data);
        return $res;
    }

}