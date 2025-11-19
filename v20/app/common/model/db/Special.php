<?php
/**
 * Special.php
 * 专题列表（链接库）
 * Create on 2020/11/17 13:38
 * Created by zhumengqun
 */

namespace app\common\model\db;

use \think\Model;

class Special extends Model
{
    /**
     * @return array
     * 获取全部分类
     */
    public function getSpecial($where, $page, $pageSize)
    {
        $arr = $this->field(true)->where($where)->order('pigcms_id DESC')->page($page, $pageSize)->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * @param $where
     * 获取总数
     */
    public function getCount($where)
    {
        return $this->field(true)->where($where)->count();
    }
}