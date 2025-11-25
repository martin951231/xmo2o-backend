<?php

/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      海康云眸内部应用对应社区信息表
 */

namespace app\community\model\db\Device;
use think\Model;

class DeviceHikCloudCommunities extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * @param array       $where 查询条件
     * @param bool|string $field 查询具体字段
     * @param int         $page  分页 查询第几页就传对应页数
     * @param int         $limit 分页每页获取条数
     * @param string      $order 查询排序
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($where, $field = true, $page = 1, $limit = 20, $order = 'hik_cloud_id DESC')
    {
        $list = $this->field($field)->where($where)->order($order);
        if ($page > 0) {
            $list->page($page, $limit);
        }
        $list = $list->select();
        return $list;
    }


    public function getColumn($where,$field, string $key = '')
    {
        $info = $this->where($where)->column($field, $key);
        return $info;
    }
}