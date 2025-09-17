<?php
/**
 * 用户分销
 * @author: 衡婷妹
 * @date: 2020/8/25
 */
namespace app\common\model\service\user;

use app\common\model\db\DistributorSpreadList;

class DistributorSpreadListService
{   
    public $distributorSpreadListModel = null;

    public function __construct()
    {
        $this->distributorSpreadListModel = new DistributorSpreadList();
    }

    /**
     * 计算用户分销获得佣金
     * @param $store
     * @param int $price
     * @author: 张涛
     * @date: 2020/8/25
     */
    public function addSpread($nowOrder, $nowGoods)
    {

        if (cfg('open_single_spread')!=1 || empty($nowOrder['distributor_uid']) || empty($nowGoods['distributor_percent'])) {
            return false;
        }

        // 可获得的金额
        $spreadTotalMoney = $nowOrder['spread_total_money'];
        $distributorSpreadMoney = round($spreadTotalMoney*$nowGoods['distributor_percent']/100,2);

        if ($distributorSpreadMoney<=0) {
            return false;
        }

        $saveData =array(
            'spread_uid' => $nowOrder['distributor_uid'],
            'order_uid' => $nowOrder['uid'],
            'money' => $distributorSpreadMoney,
            'order_type' => $nowOrder['order_type'],
            'order_id' => $nowOrder['order_id'],
            'third_id' => $nowOrder['group_id'],
            'real_orderid' => $nowOrder['real_orderid'],
            'add_time' => time()
        );
        $res = $this->add($saveData);
        return $res;
    }


    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $id = $this->distributorSpreadListModel->insertGetId($data);
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
            $result = $this->distributorSpreadListModel->insertAll($data);
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
            $result = $this->distributorSpreadListModel->where($where)->update($data);
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

        $result = $this->distributorSpreadListModel->getOne($where, $order);
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
            $result = $this->distributorSpreadListModel->getSome($where,$field ,$order,$page,$limit);
//            var_dump($this->distributorSpreadListModel->getLastSql());
        } catch (\Exception $e) {
            return [];
        }

        return $result->toArray();
    }
}