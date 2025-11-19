<?php

/**
 * @Author: jjc
 * @Date:   2020-06-16 15:34:25
 * @Last Modified by:   jjc
 * @Last Modified time: 2020-06-18 10:40:55
 */
namespace app\mall\model\service;
use app\mall\model\db\MallCartNew as  MallCartNewModel;

class MallCartNewService{
	public $MallCartNewModel = null;

    public function __construct()
    {
        $this->MallCartNewModel = new MallCartNewModel();
    }

    /**
     * [addCart 加入购物车]
     * @Author   JJC
     * @DateTime 2020-06-17T14:17:45+0800
     * @param    [type]                   $skuInfo [description]
     * @param    [type]                   $uid     [description]
     * @param    integer                  $num     [description]
     */
    public function addCart($skuInfo,$uid,$num=1){
    	if(empty($skuInfo)){
            throw new \think\Exception("商品信息缺失！");
        }
        if(empty($uid)){
            throw new \think\Exception("用户信息缺失！");
        }
        $exist = $this->MallCartNewModel->getOne($skuInfo['sku_id'],$uid);
        /*var_dump($exist);*/
        $data = [];
        if($exist){
        	$data['id']          = $exist['id'];
        	$data['num']         =  intval($exist['num'])+intval($num);
        	$data['join_price']  =  $skuInfo['price'];
        }else{
            $data['id']="";
        	$data['store_id']    =  $skuInfo['store_id'];
        	$data['uid']         =  $uid;
        	$data['goods_id']    =  $skuInfo['goods_id'];
        	$data['sku_id']      =  $skuInfo['sku_id'];
        	$data['sku_info']    =  $skuInfo['sku_str'];
        	$data['num']         =  $num;
        	$data['join_price']  =  $skuInfo['price'];
        	$data['create_time'] =  time();
        }
        return $this->MallCartNewModel->insertDate($data);
    }

    public function delCart($ids){
    	$where = [
    		['id','in',$ids],
    	];
    	return $this->MallCartNewModel->delCart($where);
    }

    public function cartList($uid){

    	if(empty($uid)){
            throw new \think\Exception("用户信息缺失！");
        }
        //获取库存充足的商品
        $enough_where = [
        	['c.uid','=',$uid],
        	['c.is_del','=',0],
        	['s.stock_num','<>',0],
        ];
        $enough_list = $this->MallCartNewModel->getAll($enough_where);

        //获取库存不充足的购物车商品
        $not_enough_where = [
        	['c.uid','=',$uid],
        	['c.is_del','=',0],
        	['s.stock_num','=',0],
        ];
        $not_enough_list = $this->MallCartNewModel->getAll($not_enough_where);

        $return = [
        	'normal_list' => $enough_list,
        	'no_effective_list' => $not_enough_list,
        ];
        return $return;
    }
}