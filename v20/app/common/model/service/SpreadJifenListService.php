<?php
/**
 * 用户推广关系佣金记录
 * User: 衡婷妹
 * Date: 2020/09/18 11:30
 */

namespace app\common\model\service;

use app\common\model\db\SpreadJifenList;
use app\foodshop\model\service\order\DiningOrderService;

class SpreadJifenListService
{
    public $spreadJifenListObj = null;
    public function __construct()
    {
        $this->spreadJifenListObj = new SpreadJifenList();
    }
    
    //三级分佣积分返现
    public function spreadCheckJifen($order = []){
        $ordeId = $order['order_id'] ?? 0;
        $orderType = $order['order_type'] ?? '';
        if(empty($ordeId) || empty($orderType)){
            return false;
        }

        $where = [];
        $where['order_type'] = $orderType;
        $where['order_id'] = $ordeId;
        $list = $this->getSome($where);
        if(empty($list)){
            return false;
        }

        foreach ($list as $key => $value) {
            $id = $value['id'];
            /*信息*/
            $uid=$value['uid'];
            $jifen=$value['jifen'];
            $desc = L_('推广用户购买商品获得').cfg('score_name');

            $data=array();
            $data['id']=$id;
            $data['update_time']=time();

            switch ($orderType) {
                case 'dining':
                case 'mall':
                    (new UserService())->addScore($uid,$jifen,$desc);
                    break;
            }
        }
    }
    
    /**
     * 获取多条记录
     * @author: 衡婷妹
     * @date: 2020/09/18
     */
    public function getSome($where)
    {
        $row = $this->spreadJifenListObj->getSome($where);
        return $row ? $row->toArray() : [];
    }
    /**
     * 获取记录
     * @author: 衡婷妹
     * @date: 2020/09/18
     */
    public function getOne($where)
    {
        $row = $this->spreadJifenListObj->where($where)->find();
        return $row ? $row->toArray() : [];
    }

    /**
     * 添加记录
     * @author: 衡婷妹
     * @date: 2020/09/18
     */
    public function add($data)
    {
        $id = $this->spreadJifenListObj->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }
    /**
     * 更新数据
     * @param $where array
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data) {
        if(empty($where) || empty($data)){
            return false;
        }

        $result = $this->spreadJifenListObj->updateThis($where, $data);
        if($result === false) {
            return false;
        }

        return $result;
    }
}