<?php
/**
 * 发起团购的列表
 * Author: 衡婷妹
 * Date Time: 2020/11/27 16:18
 */

namespace app\group\model\service\order;

use app\group\model\db\GroupBuyerList;

class GroupBuyerListService
{
    public $groupBuyerListModel = null;

    public function __construct()
    {
        $this->groupBuyerListModel = new GroupBuyerList();
    }
   

    /**
     * 获取拼团小组成员 包括机器人
     * @param $data array 数据
     * @return array
     */
    public function getBuyererByFid($fid)
    {
        $field = 'b.*,o.is_head,o.pay_time,o.status,o.group_pass,o.real_orderid,u.phone,u.nickname,u.avatar,u.openid,o.mer_id,o.store_id,o.order_name';

        $where = [
            'b.fid'=> $fid
        ];

        $order = [
            'is_head' => 'DESC'
        ];

        $res = $this->getBuyerListByJoin($where, $field, $order);

        return $res;
    }

    /**
     * 获取列表
     * @return array
     */
    public function getBuyerListByJoin($where, $field=true, $order=[])
    {
        $result = $this->groupBuyerListModel->getBuyerListByJoin($where, $field, $order);
        if(empty($result)){
            return [];
        }

        return $result->toArray();
    }

    /**
     * 记录参团用户信息
     * @param int $fid  
     * @param int $uid  
     * @param int $orderId  
     * @return array
     */
    public function addBuyerList($fid,$uid,$orderId){
        $buyerInfo['fid'] = $fid;
        $buyerInfo['order_id'] = $orderId;
        $buyerInfo['uid'] = $uid;
        return $this->add($buyerInfo);
    }
    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $id = $this->groupBuyerListModel->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     *批量插入数据
     * @param $data array
     * @return array
     */
    public function addAll($data){
        if(empty($data)){
            return false;
        }

        try {
            // insertAll方法添加数据成功返回添加成功的条数
            $result = $this->groupBuyerListModel->insertAll($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     * 更新数据
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data){
        if(empty($data) || empty($where)){
            return false;
        }

        try {
            $result = $this->groupBuyerListModel->where($where)->update($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     *获取一条数据
     * @param $where array
     * @return array
     */
    public function getOne($where, $order = []){
        if(empty($where)){
            return false;
        }

        $result = $this->groupBuyerListModel->getOne($where, $order);
        if(empty($result)){
            return [];
        }

        return $result->toArray();
    }

    /**
     *获取多条数据
     * @param $where array
     * @return array
     */
    public function getSome($where, $field = true,$order=true,$page=0,$limit=0){
        try {
            $result = $this->groupBuyerListModel->getSome($where,$field ,$order,$page,$limit);
        } catch (\Exception $e) {
            return [];
        }

        return $result->toArray();
    }
}