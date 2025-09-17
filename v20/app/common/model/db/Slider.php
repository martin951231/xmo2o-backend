<?php
/**
 * 系统后台导航表
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/21 11:37
 */

namespace app\common\model\db;
use think\Model;
class Slider extends Model {
    /**
     * 根据条件获取导航列表
     * @param $where
     * @param $order 排序
     * @param $limit 查询记录条数限制
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSliderListByCondition($where, $order='', $limit = 0) {
       if(empty($where)) {
            return false;
        }
        $this->name = _view($this->name);
        $result = $this->where($where)
                        ->order($order)
                        ->limit($limit)
                        ->select();
        return $result;
    }
}