<?php


namespace app\community\model\db;

use think\Model;

class HouseNewRepairWorksOrderLog extends Model
{
    /**
     * 添加工单操作记录
     * @author lijie
     * @@date_time 2021/08/13
     * @param array $data
     * @return int|string
     */
    public function addOne($data=[])
    {
        $id = $this->insertGetId($data);
        return $id;
    }

    /**
     *工单操作日志
     * @author lijie
     * @date_time 2021/08/13
     * @param array $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($where=[],$field=true,$page=0,$limit=10,$order='log_id DESC')
    {
        if($page){
            $data = $this->where($where)->field($field)->page($page,$limit)->order($order)->select();
        } else{
            $data = $this->where($where)->field($field)->order($order)->select();
        }
        return $data;
    }

    public function getLists($where=[],$field=true,$group='o.worker_id',$sort='l.log_id desc')
    {
        $data = $this->alias('l')
            ->leftJoin('house_new_repair_works_order o','o.order_id = l.order_id')
            ->where($where)
            ->field($field)
            ->group($group)
            ->select();
        return $data;
    }

    /**
     * Notes: 获取单个日志 可按照排序获取
     * 获取一条工单日志
     * @author lijie
     * @date_time 2021/08/17
     */
    public function getOne($where=[],$field=true,$order='log_id DESC')
    {
        $data = $this->where($where)->field($field)->order($order)->find();
        return $data;
    }

    /**
     * 日志数量
     * @author lijie
     * @date_time 2021/08/18
     * @param array $where
     * @return int
     */
    public function getCount($where=[],$groupBy='')
    {
        $sql = $this->where($where);
        if(!empty($groupBy)){
            $sql= $sql->group($groupBy);
        }
        $count= $sql->count();
        return $count;
    }
    public function getGroupByList($where=[],$field=true,$page=0,$limit=10,$groupBy='',$order='log_id DESC'){

        if($page){
            $data = $this->where($where)->field($field)->page($page,$limit)->group($groupBy)->order($order)->select();
        } else{
            $data = $this->where($where)->field($field)->group($groupBy)->order($order)->select();
        }
       // echo $this->getLastSql();
        return $data;
    }
    public function getCountGroupBy($where=[],$groupby='')
    {
        $countArr = $this->where($where)->group($groupby)->count();
        return !empty($countArr) ? count($countArr):0;
    }

    /**
     * 计算平均分
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @param string $group
     * @return mixed
     */
    public function getAvg($where,$field=true,$page=0,$limit=10,$order='avg_evaluate DESC',$group='o.worker_id')
    {
        $data = $this->alias('l')
            ->leftJoin('house_new_repair_works_order o','o.order_id = l.order_id')
            ->leftJoin('house_worker w','w.wid = o.worker_id')
            ->where($where)
            ->field($field)
            ->order($order)
            ->group($group)
            ->select();
        return $data;
    }

    public function saveOne($where,$data){
        $res = $this->where($where)->save($data);
        return $res;
    }
    public function getSum($where=[],$field='get_integral')
    {
        $sumTo = $this->where($where)->sum($field);
        return $sumTo;
    }
    
    public function getColumn($where,$field,$key = '')
    {
        $info = $this->where($where)->column($field, $key);
        return $info;
    }
}