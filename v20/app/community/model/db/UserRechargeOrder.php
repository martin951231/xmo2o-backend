<?php
/**
 * @author : liukezhu
 * @date : 2021/11/17
 */
namespace app\community\model\db;

use think\Model;

class UserRechargeOrder extends Model{


    /**
     * 查询列表
     * @author: liukezhu
     * @date : 2021/11/17
     * @param $where
     * @param string $field
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function getList($where,$field='*',$order='order_id desc',$page=0,$limit=20)
    {
        $sql = $this->where($where)->field($field)->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }

    /**
     * 查询总数
     * @author: liukezhu
     * @date : 2021/11/17
     * @param $where
     * @return mixed
     */
    public function getCount($where) {
        $count =$this->where($where)->count();
        return $count;
    }

    /**
     * 查询单条
     * @author: liukezhu
     * @date : 2021/11/17
     * @param array $where
     * @param bool $field
     * @return mixed
     */
    public function getOne($where=[],$field=true)
    {
        $data = $this->where($where)->field($field)->find();
        return $data;
    }
}