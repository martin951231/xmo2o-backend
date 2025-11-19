<?php
/**
 * 小区设备权限
 */

namespace app\community\model\db;

use think\Model;

class DeviceAuth extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
    
    public function getOneColumn($where,$field = 'floor_id',$key=''){
        return $this->where($where)->column($field,$key);
    }

    /**
     * 支持分页的列表查询
     * @param array        $where 查询条件
     * @param bool|string  $field 查询字段
     * @param string|array $order 排序
     * @param int          $page 分页查询时候 带页数
     * @param int          $page_size 每页条数
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPageList($where, $field = true, $order = 'auth_id DESC', $page = 0, $page_size = 10)
    {
        $db_list = $this->field($field)->order($order);
        if ($page) {
            $db_list->page($page, $page_size);
        }
        $list = $db_list->where($where)->select();
        return $list;
    }
    
}