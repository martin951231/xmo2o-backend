<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      指纹器数据库表相关增删改查操作
 */

namespace app\community\model\db\Device;

use think\Model;
use think\facade\Db;

class FingerprintDevice extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * @param array $where
     * @param bool $field
     * @param int $page
     * @param int $pageSize
     * @param string $order
     * @param string $whereRaw
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($where = [], $field = true, $page = 1, $pageSize = 15, $order = 'device_id DESC', $whereRaw = '')
    {
        $sql = $this->where($where)->field($field)->order($order);
        if ($whereRaw) {
            $sql  = $sql->whereRaw($whereRaw);
        }
        if ($page) {
            $list = $sql->page($page, $pageSize)->select();
        } else {
            $list = $sql->select();
        }
        return $list;
    }

    /**
     * 查询对应上面列表的相关数量
     * @param array $where
     * @param string $whereRaw
     * @return int
     */
    public function getFingerprintDeviceCount($where = [], $whereRaw = '') {
        $sql = $this->where($where);
        if ($whereRaw) {
            $sql  = $sql->whereRaw($whereRaw);
        }
        return $sql->count();
    }


    public function getColumn($where, $column, $key = '')
    {
        $data = $this->where($where)->column($column,$key);
        return $data;
    }
}