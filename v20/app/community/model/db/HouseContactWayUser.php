<?php


namespace app\community\model\db;

use think\Model;
class HouseContactWayUser extends Model
{
    /**
     * Notes: 获取一条数据
     * @datetime: 2021/3/22 13:13
     * @param $where
     * @param bool $field
     * @param string $order
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getFind($where,$field=true,$order='customer_id desc')
    {
        $data = $this->where($where)->field($field)->order($order)->find();
        return $data;
    }

    /**
     * Notes: 获取所有
     * @datetime: 2021/4/7 14:42
     * @param $where
     * @param bool $field
     * @param string $order
     * @return \think\Collection
     */
    public function getAll($where,$field=true,$order='customer_id desc')
    {
        $data = $this->where($where)->field($field)->order($order)->select();
        return $data;
    }

    /**
     * 企业微信用户
     * @author lijie
     * @date_time 2021/03/19
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($where,$field=true,$page=1,$limit=10,$order='u.customer_id DESC')
    {
        if($page){
            $data = $this->alias('u')
                ->leftJoin('village_qywx_bind_label l','l.ExternalUserID = u.ExternalUserID')
                ->where($where)
                ->field($field)
                ->page($page,$limit)
                ->order($order)
                ->group('u.ExternalUserID')
                ->select();
        } else{
            $data = $this->alias('u')
                ->leftJoin('village_qywx_bind_label l','l.ExternalUserID = u.ExternalUserID')
                ->where($where)
                ->field($field)
                ->order($order)
                ->group('u.ExternalUserID')
                ->select();
        }
        return $data;
    }

    /**
     * 企业微信用户数量
     * @author lijie
     * @date_time 2021/03/19
     * @param $where
     * @return int
     */
    public function getCount($where)
    {
        $count = $this->alias('u')
            ->leftJoin('village_qywx_bind_label l','l.customer_id = u.customer_id')
            ->where($where)
            ->count();
        return $count;
    }

    /**
     * 外部用户信息
     * @author lijie
     * @date_time 2021/03/23
     * @param $where
     * @param bool|string $field
     * @param string $order
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne($where,$field=true, $order = 'customer_id desc')
    {
        $data = $this->where($where)->field($field)->order($order)->find();
        return $data;
    }

    /**
     * 工作人员信息
     * @author lijie
     * @date_time 2021/03/22
     * @param $where
     * @param bool $field
     * @return mixed
     */
    public function getWorkerInfo($where,$field=true)
    {
        $data = $this->alias('u')
            ->leftJoin('house_worker w','u.wid w.wid')
            ->where($where)
            ->field($field)
            ->find();
        return $data;
    }
    public function addFind($data)
    {
        $id = $this->insertGetId($data);
        return $id;
    }
    public function getLists($where,$field,$order='customer_id desc',$page=0,$limit=20)
    {
        $list = $this->where($where)->field($field)->page($page,$limit)->order($order)->select();
        return $list;
    }
    public function getColumn($where,$column)
    {
        $data = $this->where($where)->column($column);
        return $data;
    }
    public function getMemberCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * 更新数据
     * @param $where
     * @param $data
     * @return bool
     */
    public function updateWxInfo($where,$data){
        if(empty($where) || empty($data)){
            return false;
        }
        return $this->where($where)->save($data);
    }

    /**
     * 获取添加人员相关信息列表（目前用来查询某一小区下的）
     * @param $where
     * @param bool $field
     * @param string $order
     * @return array
     */
    public function getAllVillageContact($where,$field=true,$order='g.customer_id desc'){
        $data = $this->alias('g')
                ->leftjoin('house_worker w','g.UserID = w.qy_id')
                ->where($where)
                ->field($field)
                ->order($order)
                ->select();
        if($data && !$data->isEmpty()){
            return $data->toArray();
        }
        return [];
    }

    public function getWhereOrPage($where, $field = true, $page = 1, $limit = 200, $order = 'customer_id ASC')
    {
        if ($page > 0) {
            $list = $this->whereOr($where)->field($field)->page($page, $limit)->order($order)->select();
        } else {
            $list = $this->whereOr($where)->field($field)->order($order)->select();
        }
        return $list;
    }
}