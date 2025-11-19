<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/6/28 10:02
 */
namespace app\community\model\db;

use think\Model;

class HouseVillagePrintTemplate extends Model
{
    /**
     * 查询模板列表
     * @param array $where
     * @param string $order
     * @param bool $field
     * @return mixed
     * @author zubaodi
     * @date_time 2021/06/28
     */
    public function getList($where = [], $field = true, $order = 'template_id DESC')
    {
        $data = $this->where($where)->field($field)->order($order)->select();
        return $data;
    }

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
     * @author lijie
     * @date_time 2021/06/15
     */
    public function getLists($where = [], $field = true, $page = 0, $limit = 10, $order = 'o.order_id DESC')
    {
        if ($page)
            $data = $this->alias('o')
                ->leftJoin('house_village_print_custom c', 'r.id = o.rule_id')
                ->leftJoin('house_village_print_custom_configure d', 'p.id = o.project_id')
                ->where($where)
                ->field($field)
                ->page($page, $limit)
                ->order($order)
                ->select();
        else
            $data = $this->alias('o')
                ->leftJoin('house_village_print_custom c', 'r.id = o.rule_id')
                ->leftJoin('house_village_print_custom_configure d', 'p.id = o.project_id')
                ->where($where)
                ->field($field)
                ->order($order)
                ->select();
        return $data;
    }



    /**
     * 模板详情
     * @param array $where
     * @param bool $field
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author:zhubaodi
     * @date_time: 2021/6/28 14:12
     */
    public function get_one($where = [], $field = true)
    {
        $data = $this->where($where)->field($field)->find();
        return $data;
    }

    /**
     * 获取模板列表 分页
     * User: zhanghan
     * Date: 2022/1/28
     * Time: 17:04
     * @param $where
     * @param $field
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getListByPage($where,$field,$page = 0,$limit = 10){
        $sql = $this->where($where)->field($field)->order('template_id DESC');
        $count = $sql->count();
        $list = [];
        if($page){
            $sql = $sql->page($page,$limit);
        }
        $data = $sql->select();
        if($data && !$data->isEmpty()){
            $list = $data->toArray();
        }
        $return['list'] = $list;
        $return['count'] = $count;
        $return['total_limit'] = $limit;
        return $return;
    }

    /**
     * 添加模板
     * User: zhanghan
     * Date: 2022/1/29
     * Time: 10:22
     * @param $data
     * @return int|string
     */
    public function templateAdd($data){
        return $this->insertGetId($data);
    }

    /**
     * 更新模板
     * User: zhanghan
     * Date: 2022/1/29
     * Time: 10:23
     * @param $where
     * @param $data
     * @return bool
     */
    public function templateSave($where,$data){
        if(empty($where)){
            return false;
        }
        return $this->where($where)->save($data);
    }

    /**
     * 删除模板
     * User: zhanghan
     * Date: 2022/1/29
     * Time: 11:01
     * @param $where
     * @return bool
     * @throws \Exception
     */
    public function delTemplate($where){
        if(empty($where)){
            return false;
        }
        return $this->where($where)->delete();
    }
}
