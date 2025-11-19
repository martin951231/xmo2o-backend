<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/6/28 13:21
 */
namespace app\community\model\db;

use think\Model;

class HouseVillagePrintCustom extends Model
{

    /**
     * 模板列表
     * @param array $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author zhubaodi
     * @date_time 2021/06/28
     */
    public function getLists($where = [], $field = true, $page = 0, $limit = 10, $order = 'c.configure_id ASC')
    {
        if ($page)
            $data = $this->alias('c')
                ->leftJoin('house_village_print_custom_configure b', 'c.configure_id = b.configure_id')
                ->where($where)
                ->field($field)
                ->page($page, $limit)
                ->order($order)
                ->select();
        else
            $data = $this->alias('c')
                ->leftJoin('house_village_print_custom_configure b', 'c.configure_id = b.configure_id')
                ->where($where)
                ->field($field)
                ->order($order)
                ->select();
        return $data;
    }

    /**
     * 删除模板字段
     * User: zhanghan
     * Date: 2022/2/7
     * Time: 14:10
     * @param $where
     * @return bool
     * @throws \Exception
     */
    public function delTemplateCustom($where){
        if(empty($where)){
            return false;
        }
        return $this->where($where)->delete();
    }

    /**
     * 添加模板关联字段
     * User: zhanghan
     * Date: 2022/2/7
     * Time: 14:27
     * @param $data
     * @return false|int
     */
    public function addPrintTemplateCustom($data){
        if(empty($data)){
            return false;
        }
        return $this->insertAll($data);
    }
}
