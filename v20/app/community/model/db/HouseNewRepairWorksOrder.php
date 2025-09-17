<?php


namespace app\community\model\db;

use think\Model;

class HouseNewRepairWorksOrder extends Model
{
    /**
     * 工单列表
     * @author lijie
     * @date_time 2021/08/12
     * @param array $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     */
    public function getList($where=[],$field=true,$page=1,$limit=10,$order='o.order_id DESC',$pigcms_id=0,$roomIds=[])
    {
        $data = $this->alias('o')
            ->leftJoin('House_new_repair_subject s','o.type_id = s.id')
            ->where($where);
        if(!empty($pigcms_id) && !empty($roomIds)){
            $condition_or = [
                ['o.bind_id','=',$pigcms_id],
                ['o.room_id','in',$roomIds]
            ];
            $data->where(function ($query) use ($condition_or) {
                $query->whereOr($condition_or);
            });
        }elseif (!empty($pigcms_id) && empty($roomIds)){
            $data->where([['o.bind_id','=',$pigcms_id]]);
        }
        $data->field($field)
            ->order($order);
        if($page)
            $data = $data->page($page,$limit);
        $datas = $data->select();
        //echo $data->getLastSql();
        return $datas;
    }
    
    public function getAllList($where=[],$field=true,$order='update_time ASC,order_id ASC')
    {
        $dataObj = $this->alias('o')->where($where)->field($field)->order($order)->select();
        return $dataObj;
    }
    
    public function getListByEvaluate($where = [], $field = true, $page = 1, $limit = 10, $order = 'o.order_id DESC')
    {
        $data = $this->alias('o')
            ->leftJoin('House_new_repair_works_order_log l', 'o.order_id = l.order_id')
            ->where($where)->field($field)->order($order);
        if ($page) {
            $data = $data->page($page, $limit);
        }
        $datas = $data->group('l.order_id')->select();
        return $datas;
    }

    public function getCountByEvaluate($where=[])
    {
        $countArr = $this->alias('o')->leftJoin('House_new_repair_works_order_log l', 'o.order_id = l.order_id')->where($where)->group('l.order_id')->count();
        return $countArr ? count($countArr):0;
    }
    /**
     * Notes:
     * @param array $where
     * @param string $whereRaw
     * @param bool|string $field
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2021/8/12 14:48
     */
    public function getWorkHandleList($where=[],$whereRaw='',$field=true,$page=1,$page_size=10,$order='a.order_id DESC',$group='a.order_id')
    {
        $sql = $this->alias('a')
        ->leftJoin('house_new_repair_works_order_log b','b.order_id = a.order_id')->field($field)->group($group);
        if ($whereRaw) {
            $sql->whereRaw($whereRaw);
        } else {
            $sql->where($where);
        }
        if ($page && $page_size) {
            $sql->page($page,$page_size);
        }
        $data = $sql->order($order)->select();

        return $data;
    }
    public function getWorkHandleCount($where=[],$whereRaw='',$group='a.order_id')
    {
        $sql = $this->alias('a')
            ->leftJoin('house_new_repair_works_order_log b','b.order_id = a.order_id')->group($group);
        if ($whereRaw) {
            $sql->whereRaw($whereRaw);
        } else {
            $sql->where($where);
        }
        $data = $sql->count();
        fdump($this->getLastSql(),'getWorkHandleCount');
        return $data;
    }

    /**
     * 添加工单
     * @author lijie
     * @date_time 2021/08/13
     * @param array $data
     * @return int|string
     */
    public function addOne($data=[])
    {
        $id = $this->insertGetId($data);
        return $id;
    }

    /**
     * Notes: 获取工单
     * @param array $where
     * @param bool|string $field
     * @return array|Model|null
     * @author: wanzy
     * @date_time: 2021/8/17 13:06
     */
    public function getOne($where=[],$field=true)
    {
        $data = $this->where($where)->field($field)->find();
        return $data;
    }

    /**
     * 修改工单
     * @author lijie
     * @date_time 2021/08/17
     * @param array $where
     * @param array $data
     * @return bool
     */
    public function saveOne($where=[],$data=[])
    {
        $res = $this->where($where)->save($data);
        return $res;
    }

    /**
     * 工单数量
     * @author lijie
     * @date_time 2021/08/18
     * @param array $where
     * @return int
     */
    public function getCount($where=[])
    {
        $count = $this->alias('o')->where($where)->count();
        return $count;
    }

    public function getWhereRawCount($whereRaw='')
    {
        $count = $this->whereRaw($whereRaw)->count();
        return $count;
    }


    /**
     * 获取工单分类
     * @author: liukezhu
     * @date : 2022/4/18
     * @param $where
     * @param bool $field
     * @return mixed
     */
    public function getOrderCate($where,$field=true){
        $data = $this->alias('o')
            ->leftJoin('house_new_repair_cate c1','c1.id = o.cat_fid')
            ->leftJoin('house_new_repair_cate c2','c2.id = o.cat_id')
            ->where($where)->field($field)->find();
        return $data;
    }

    public function getBindCateOrder($where,$field=true,$order='r.id asc')
    {
        $where_str='';
        if(isset($where['_string']) && $where['_string']='isNotTime'){
            $where_str='o.add_time >= w.create_time';
            unset($where['_string']);
        }
        $data = $this->alias('o')
            ->leftJoin('house_new_repair_cate c1','c1.id = o.cat_fid')
            ->leftJoin('house_new_repair_cate c2','c2.id = o.cat_id')
            ->leftJoin('house_new_repair_cate_group_relation r','r.cate_id = o.cat_fid')
            ->leftJoin('house_worker w','w.department_id = r.group_id')
//            ->leftJoin('house_new_repair_graborder_notice_record g','g.wid=w.wid and g.order_id = o.order_id')
            ->where($where)->where($where_str)
            ->field($field)
            ->order($order)
            ->select();
        return $data;
    }


    public function getBindWorksOrderList($where,$whereRaw='',$field=true,$order='o.order_id desc',$page=0,$limit=20)
    {
        $sql = $this->alias('o')->where($where)->field($field)->order($order);
        if($whereRaw){
            $sql->whereRaw($whereRaw);
        }
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }

    public function getBindWorksOrderCount($where=[],$whereRaw='')
    {
        $sql = $this->alias('o')
            ->where($where);
        if($whereRaw){
            $sql->whereRaw($whereRaw);
        }
        $list = $sql->count();
        return $list;
    }


    public function getColumn($where,$field, string $key = '')
    {
        $info = $this->where($where)->column($field, $key);
        return $info;
    }
    
    /**
     * 获取工单完成率列表
     */
    public function getRepairRateList($where,$field,$order,$group,$limit)
    {
        $list = $this->where($where)
            ->field($field)
            ->order($order)->group($group)
            ->paginate($limit)
            ->toArray();
        return $list;
    }

}