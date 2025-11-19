<?php
/**
 * 系统后台配置group model
 * Author: chenxiang
 * Date Time: 2020/5/18 10:10
 */

namespace app\common\model\db;

use think\Model;

class ConfigGroup extends Model
{

    /**
     * 获取配置分组
     * @author: chenxiang
     * @date: 2020/5/19 9:29
     * @param bool $field
     * @param array $where
     * @param string $sort
     * @return \think\Collection
     */
    public function getConfigGroupList($field = true, $where = [], $sort = '') {
        $result = $this->field($field)->where($where)->order($sort)->select();
        return $result;
    }

    /**
     * 获取一条数据
     * @param array $where
     * @return array|Model|null
     */
    public function getDataOne($where = []) {
        $result = $this->where($where)->find();
        return $result;
    }
}
