<?php
/**
 * GoodsCategory.php
 * 平台快报（用作链接库)
 * Create on 2020/11/17 15:23
 * Created by zhumengqun
 */

namespace app\common\model\db;

use \think\Model;

class SystemNewsCategory extends Model
{
    /**
     * @return array
     * 获取全部分类
     */
    public function getCategory($where,$order)
    {
        $arr = $this->field(true)->where($where)->order($order)->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * @param $where
     * @return array
     * 根据条件获取一个
     */
    public function getSonInfo($where,$order)
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('snc')->join($prefix.'system_news sn','snc.id = sn.category_id')->where($where)->order($order)->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }
}