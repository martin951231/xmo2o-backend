<?php
/**
 * 街道社区工单id
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/2/22 19:10
 */

namespace app\community\model\db;

use think\Model;
class AreaStreetWorkersOrder extends Model {
    use \app\common\model\db\db_trait\CommonFunc;

    public function getList($where,$field=true,$order='order_time DESC',$page=1,$page_size=10) {
        $data_sql = $this->field($field)->where($where)->order($order);
        if ($page) {
            $data_sql->page($page,$page_size);
        }
        $data = $data_sql->select();
        return $data;
    }

    /**
     * 获取工单事件数量
     * @author lijie
     * @date_time 2021/02/24
     * @param $where
     * @param $group
     * @return int
     */
    public function getCount($where,$group='')
    {
        if($group){
            $count = $this->alias('e')
                ->rightJoin('house_village_grid_range g','g.id = e.grid_range_id')
                ->leftJoin('house_village_grid_member m','m.id = g.grid_member_id')
                ->where($where)
                ->group($group)
                ->count();
        }else{
            $count = $this->alias('e')
                ->rightJoin('house_village_grid_range g','g.id = e.grid_range_id')
                ->leftJoin('house_village_grid_member m','m.id = g.grid_member_id')
                ->where($where)
                ->count();
        }
        return $count;
    }

    /**
     * 根据分组查询工单事件
     * @author lijie
     * @date_time 2021/02/24
     * @param $where
     * @param $field
     * @param string $group
     * @return mixed
     */
    public function getListByGroup($where,$field,$group='e.grid_range_id')
    {
        $data = $this->alias('e')
            ->rightJoin('house_village_grid_range g','g.id = e.grid_range_id')
            ->where($where)
            ->field($field)
            ->group($group)
            ->select();
        return $data;
    }

    /**
     * 获取工单详情
     * @author lijie
     * @date_time 2021/02/24
     * @param $where
     * @param bool $field
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne($where,$field=true)
    {
        $data = $this->alias('e')
            ->leftJoin('house_village_grid_range g','e.grid_range_id = g.id')
            ->where($where)
            ->field($field)
            ->find();
        return $data;
    }

    /**
     *工单列表
     * @author lijie
     * @date_time 2021/02/24
     * @param $where
     * @param bool $field
     * @param string $order
     * @param int $page
     * @param int $page_size
     * @return mixed
     */
    public function getDataList($where,$field=true,$order='e.order_time DESC',$page=1,$page_size=10)
    {
        $data = $this->alias('e')
            ->leftJoin('house_village_grid_range g','g.id = e.grid_range_id')
            ->leftJoin('house_village_grid_member m','m.id = g.grid_member_id')
            ->where($where)
            ->field($field)
            ->order($order)
            ->page($page,$page_size)
            ->select();
        return $data;
    }

    /**
     * Notes: 通过工作人员获取对应工单
     * @param array $where
     * @param array $whereOr
     * @param string $field
     * @param string $order
     * @param string $group
     * @param int $page
     * @param int $page_size
     * @return mixed
     * @author: wanzy
     * @date_time: 2021/3/1 13:56
     */
    public function getWorkHandleList($where=[],$whereOr = [],$whereRaw='',$field='a.*',$order='b.bind_id DESC',$group='a.order_id',$page=1,$page_size=10)
    {
        $sql = $this->alias('a')
            ->leftJoin('area_street_event_works b','b.order_id = a.order_id')->field($field)->group($group);
        if ($whereRaw) {
            $sql->whereRaw($whereRaw);
        } else if (!empty($whereOr)) {
            $sql->whereOr($whereOr);
        }
        if (!empty($where)) {
            $sql->where($where);
        }
        if ($page && $page_size) {
            $sql->page($page,$page_size);
        }
        $data = $sql->order($order)->select();
        return $data;
    }

    /**
     * Notes: 通过工作人员获取对应工单数
     * @param $where
     * @param $field
     * @param string $order
     * @param string $group
     * @return mixed
     * @author: wanzy
     * @date_time: 2021/2/27 13:34
     */
    public function getWorkHandleCount($where,$field='a.order_id',$order='b.bind_id DESC',$group='a.order_id',$whereRaw='')
    {
        $sql=$this->alias('a')
            ->leftJoin('area_street_event_works b','b.order_id = a.order_id')
            ->where($where);
        if ($whereRaw) {
            $sql->whereRaw($whereRaw);
        }
        $count_list = $sql
            ->field($field)
            ->order($order)
            ->group($group)
            ->select();
        if ($count_list) {
            $count_list = $count_list->toArray();
        } else {
            $count_list = [];
        }
        $count = !empty($count_list) ? count($count_list) : 0;
        return $count;
    }

    /**
     * 修改工单
     * @author lijie
     * @date_time 2021/03/01
     * @param $where
     * @param $data
     * @return bool
     */
    public function saveOne($where,$data)
    {
        $res = $this->where($where)->save($data);
        return $res;
    }

    public function getFindCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    public function getFind($where, $field = true)
    {
        $data = $this->alias('o')
            ->leftJoin('area_street_event_category c1','c1.cat_id = o.cat_id')
            ->leftJoin('area_street_event_category c2','c2.cat_id = o.cat_fid')
            ->where($where)
            ->field($field)
            ->find();
        return $data;
    }

    public function getOneData($where, $field = true)
    {
        $data = $this->where($where)->field($field)->find();
        return $data;
    }
}