<?php
/**
 * @author : liukezhu
 * @date : 2022/1/13
 */
namespace app\community\model\db;

use think\Model;

class HouseVillageCarAccessRecord extends Model{

    /**
     * 插入数据
     * @author: liukezhu
     * @date : 2022/1/13
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        $res = $this->insertGetId($data);
        return $res;
    }

    /**
     *更新数据
     * @author: liukezhu
     * @date : 2022/1/13
     * @param $where
     * @param $data
     * @return mixed
     */
    public function saveOne($where,$data)
    {
        $data = $this->where($where)->save($data);
        return $data;
    }

    /**
     * 查询单条
     * @author: liukezhu
     * @date : 2022/1/13
     * @param $where
     * @param bool $field
     * @return mixed
     */
    public function getOne($where,$field =true,$order='record_id DESC'){
        $info = $this->field($field)->where($where)->order($order)->find();
        return $info;
    }


    /**
     * 查询总数
     * @author: liukezhu
     * @date : 2022/1/19
     * @param $where
     * @return mixed
     */
    public function getCount($where){
        $data = $this->where($where)->count();
        return $data;
    }


    /**
     * 查询列表
     * @author: liukezhu
     * @date : 2022/1/12
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     */
    public function get_list($where,$field=true,$page=0,$limit=15,$order='record_id DESC')
    {
        if ($page){
            $data = $this->where($where)->field($field)->page($page,$limit)->order($order)->select();
        }else{
            $data = $this->where($where)->field($field)->order($order)->select();
        }
        return $data;
    }
    public function get_list_group($where,$field=true,$group='car_number')
    {
        $data = $this->where($where)->field($field)->group($group)->select();
        return $data;
    }
    public function getSum($where=[],$sum='total')
    {
        $sum = $this->where($where)->sum($sum);
        //echo $this->getLastSql();
        return $sum;
    }

    public function getSums($where=[],$sum='b.total')
    {
        $_string='';
        if(isset($where['_string'])){
            $_string=$where['_string'];
            unset($where['_string']);
        }
        $sum = $this->alias('a')
            ->leftJoin('house_village_car_access_record b','a.order_id=b.order_id and b.accessType=2')
            ->where($where)->where($_string)->sum($sum);
        //echo $this->getLastSql();
        return $sum;
    }
    /**
     * 查询列表
     * @author: liukezhu
     * @date : 2022/1/12
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     */
    public function getList($where,$field=true,$page=0,$limit=15,$order='record_id DESC')
    {
        $data = $this->where($where)->field($field);
        if ($page){
            $data=$data->page($page,$limit)->order($order)->select();
        }else{
            $data = $data->order($order)->select();
        }
        return $data;
    }
    public function getOutList($where,$field=true,$page=0,$limit=15,$order='b.total DESC,b.prepayTotal DESC, b.record_id DESC')
    {
        $_string='';
        if(isset($where['_string'])){
            $_string=$where['_string'];
            unset($where['_string']);
        }
        $sql = $this->alias('a')->leftJoin('house_village_car_access_record b','a.order_id=b.order_id')->where($where)->where($_string)->field($field);
        if ($page){
            $data=$sql->page($page,$limit)->order($order)->select();
        }else{
            $data = $sql->order($order)->select();
        }
        return $data;
    }

    public function getOutSums($where=[],$sum='b.total')
    {
        $sum = $this->alias('b')
            ->leftJoin('house_village_car_access_record a','b.order_id=a.order_id and a.accessType=1')
            ->where($where)->sum($sum);
        return $sum;
    }
    
    public function getOutCounts($where)
    {
        $data = $this->alias('b')
            ->leftJoin('house_village_car_access_record a','b.order_id=a.order_id and a.accessType=1')
            ->where($where)->count();
        return $data;
    }
    /**
     * 查询列表
     * @author: liukezhu
     * @date : 2022/1/12
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     */
    public function getCounts($where)
    {
        $_string='';
        if(isset($where['_string'])){
            $_string=$where['_string'];
            unset($where['_string']);
        }
        $data = $this->alias('a')
            ->leftJoin('house_village_car_access_record b','b.order_id=a.order_id')
            ->where($where)->where($_string)->count();
        return $data;
    }

    /**
     * 查询列表
     * @author: liukezhu
     * @date : 2022/1/12
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     */
    public function getLists($where,$field=true,$page=0,$limit=15,$order='a.record_id DESC')
    {
        $data = $this->alias('a')
            ->leftJoin('house_village_car_access_record b',' b.order_id=a.order_id and b.accessType=2')
            ->where($where)->field($field);
        if ($page){
            $data=$data->page($page,$limit)->order($order)->select();
        }else{
            $data = $data->order($order)->select();
        }
        return $data;
    }

    /**
     * 统计进场数据
     * @author zhubaodi
     * @date_time 2021/4/10
     * @param $where
     * @param  $field
     * @param string $group
     * @return mixed
     */
    public function get_counts($where=[] ,$field=true,$group='')
    {
        $data = $this->where($where)->group($group)->column($field);
      //  print_r($data);die;
        return $data;
    }

    public function getColumn($where,$column, $key = '')
    {
        $data = $this->where($where)->column($column,$key);
        return $data;
    }

    public function deleteData($where)
    {
        $data = $this->where($where)->delete();
        return $data;
    }
}