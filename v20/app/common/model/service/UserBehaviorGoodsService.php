<?php
/**
 * 用户行为分析（购买商品）service
 * Created by vscode.
 * Author: 钱大双
 * Date Time: 2020年12月3日10:09:05
 */

namespace app\common\model\service;

use app\common\model\db\UserBehaviorGoods as UserBehaviorGoodsModel;

class UserBehaviorGoodsService
{
    public $type;

    public function __construct()
    {
        $this->type = [
            'meal' => 1,
            'group' => 2,
            'mall' => 3
        ];
    }

    /**
     * 添加用户行为分析（购买商品）
     * @param $data [
     *                          goods_id:1,//商品id
     *                          uid:12,//用户id
     *                          name:'小炒肉',//商品名称
     *                          num:'2',//商品数量
     *                          business_type:'mall',//业务类型：快店：meal，团购：group， 新版商城：mall
     *                          from_type:1,//来源：来源：0wap端，1安卓,2ios，3小程序，4pc端，5其他,6移动端
     *                          order_id:'2',//所属订单id
     * ]
     * @return bool
     */

    public function addUserBehaviorGoods($data)
    {
        $data['add_time'] = time();
        $data['add_ip'] = get_client_ip(1);
        $data['business_type'] = $this->type[$data['business_type']];
        $userBehaviorGoodsModel = new UserBehaviorGoodsModel();
        return $userBehaviorGoodsModel->add($data);
    }
    
    /**
     * 批量添加用户行为分析（购买商品）
     */
    public function addUserBehaviorGoodsAll($data)
    {
        if(!$data){
            return false;
        }
        foreach ($data as $k=>$v){
            $data[$k]['add_time'] = time();
            $data[$k]['add_ip'] = get_client_ip(1);
            $data[$k]['business_type'] = $this->type[$data[$k]['business_type']];
        }
        $userBehaviorGoodsModel = new UserBehaviorGoodsModel();
        $userBehaviorGoodsModel->addAll($data);
        return true;
    }
}