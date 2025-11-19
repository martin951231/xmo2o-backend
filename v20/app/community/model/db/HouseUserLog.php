<?php
/**
 * 门禁开门记录
 * @author weili
 * @datetime 2020/8/3
 */

namespace app\community\model\db;
use think\Model;
use think\Db;
class HouseUserLog extends Model
{
    public function getList($where,$field,$page=0,$limit=10,$order='l.log_id desc',$type=0)
    {
        $list = $this->alias('l')->leftJoin('house_village_user_bind ub','l.log_bind_id=ub.pigcms_id')
            ->leftJoin('house_face_device f','l.device_id=f.device_id')
            ->field($field)
            ->where($where);
        if($type){
            $list = $list
                ->order($order)
                ->select();
        }else{
            $list = $list
                ->page($page,$limit)
                ->order($order)
                ->select();
        }
        return $list;
    }

    public function getLists($where,$field=true,$page=0,$limit=10,$order='log_id desc',$type=0)
    {
        $list = $this->field($field)->where($where);
        if($type){
            $list = $list
                ->order($order)
                ->select();
        }else{
            $list = $list
                ->page($page,$limit)
                ->order($order)
                ->select();
        }
        return $list;
    }

    public function getVillageFloorCount($where)
    {
        $count = $this->alias('l')
            ->leftJoin('house_face_device f','l.device_id=f.device_id')
            ->where($where)
            ->count();
        return $count;
    }

    /**
     * Notes: 获取一条记录
     * @param $where
     * @param bool $field
     * @return array|Model|null
     * @author: weili
     * @datetime: 2020/8/3 13:30
     */
    public function getFind($where,$field=true)
    {
        $info = $this->where($where)->field($field)->find();
        return $info;
    }

    /**
     * Notes: 获取数量
     * @param $where
     * @return int
     * @author: weili
     * @datetime: 2020/8/3 13:31
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * @param array $where
     * @param bool|string $field
     * @param int $page
     * @param int $pageSize
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getTableList($where = [], $field = true, $page = 1, $pageSize = 15, $order = 'log_id DESC')
    {
        $sql = $this->where($where)->field($field)->order($order);
        if ($page) {
            $list = $sql->page($page, $pageSize)->select();
        } else {
            $list = $sql->select();
        }
        return $list;
    }

    /**
     * Notes: 获取开门经纬度列表
     * @param $where
     * @param $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     * @author: weili
     * @datetime: 2020/8/4 21:00
     */
    public function getAddressList($where,$field,$page=0,$limit=10,$order='l.log_id desc')
    {
        
            $list = $this->alias('l')->field($field)
            ->where($where)
            ->limit($page,$limit)
            ->order($order)
            ->select();
        return $list;
    }



    //todo 统计开门和小区的数据
    public function getDayOpenDoorList($where,$group,$field='*',$order='g.log_id desc',$page=0,$limit=10,$today=1){
        $where2=[];
        if(isset($where['_string']) && !empty($where['_string'])){
            $where2=$where['_string'];
            unset($where['_string']);
        }
        $list = $this->alias('g')
            ->leftJoin('house_village v','v.village_id = g.log_business_id')
            ->where($where)
            ->where($where2)
            ->field($field)
            ->group($group)
            ->order($order);
        if($today){
            $list->whereTime('g.log_time', 'today');
        }
        if($page)
        {
            $list->page($page,$limit);
        }
        $list = $list->select();
        return $list;
    }

    //todo 统计开门和小区的数量
    public function getDayOpenDoorCount($where,$group,$today=1){
        $where2=[];
        if(isset($where['_string']) && !empty($where['_string'])){
            $where2=$where['_string'];
            unset($where['_string']);
        }
        $list = $this->alias('g')
            ->leftJoin('house_village v','v.village_id = g.log_business_id')
            ->where($where)
            ->where($where2)
            ->group($group);
        if($today){
            $list->whereTime('g.log_time', 'today');
        }
        return $list->count();
    }

    //todo 查询开门总数
    public function getOpenDoorCount($where,$today=1){
        $list=$this->alias('g')->where($where);
        if($today){
            $list->whereTime('log_time', 'today');
        }
//        $cacheKey = md5(\json_encode($where). $today);
//        return $list->cache($cacheKey, rand(60,300))->count();
        return $list->count();
    }

    //todo 查询人脸门禁和蓝牙记录
    public function getFaceOpenLog($where,$field,$order='g.log_id desc',$page=0,$limit=10)
    {
        $list = $this->alias('g')
            ->leftJoin('house_village v','v.village_id=g.log_business_id')
            ->leftJoin('house_village_user_bind ub','g.log_bind_id=ub.pigcms_id and g.log_bind_id > 0')
            ->leftJoin('house_face_device d','d.device_id=g.device_id and g.log_from <> 21')
            ->leftJoin('user u','u.uid=g.uid')
            ->field($field)
            ->where($where)->order($order);
        if($page)
        {
            $list->page($page,$limit);
        }
        return $list->select();
    }

    /**
     * 插入一条数据
     * @param $data
     * @return int|string]
     */
    public function addData($data){
        if(!isset($data['add_time']) || $data['add_time']<1100000000){
            $data['add_time']=time();
        }
        return $this->insertGetId($data);
    }

    public function addAll($data) {
        return $this->insertAll($data);
    }

    public function getMax($where,$column)
    {
        $data = $this->where($where)->max($column);
        return $data;
    }

    public function updateThis($where, $data){
        $result = $this->where($where)->update($data);
        return $result;
    }
}