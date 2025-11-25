<?php

/**
 * @Author: jjc
 * @Date:   2020-06-12 10:46:27
 * @Last Modified by:   jjc
 * @Last Modified time: 2020-06-16 15:44:54
 */

namespace app\mall\model\service;

use app\mall\model\db\MallFullGiveGiftSku;
use app\mall\model\db\MallGoodsSku as MallGoodsSkuModel;
use app\mall\model\db\MallGoods;
use app\mall\model\db\MallLimitedSku;
use app\mall\model\db\MallNewBargainSku;
use app\mall\model\db\MallNewGroupSku;
use app\mall\model\db\MallPrepareActSku;
use app\merchant\model\db\MerchantStore;

class MallGoodsSkuService
{

    public $MallGoodsSkuModel = null;

    public function __construct()
    {
        $this->MallGoodsSkuModel = new MallGoodsSkuModel();
    }

    /**
     * [getSkuInfo 获取sku详情]
     * @Author   JJC
     * @DateTime 2020-06-16T10:56:52+0800
     * @param    [type]                   $spec_info [description]
     * @return   [type]                              [description]
     */
    public function getSkuInfo($spec_info, $goods_id)
    {
        $where = [];
        $where[] = ['goods_id', '=', $goods_id];
        if (!empty($spec_info)) {
            $spec_arr = [];
            foreach ($spec_info as $key => $val) {
                $spec_arr[] = $key . ':' . $val;
            }
            $spec_str = implode('|', $spec_arr);

            $where[] = ['sku_info', '=', $spec_str];
        }
        return $this->MallGoodsSkuModel->getOne($where);
    }

    /**
     * [getSkuById 根据Sku获取sku详情]
     * @Author   JJC
     * @DateTime 2020-06-16T15:21:39+0800
     * @param    [type]                   $sku_id [description]
     * @return   [type]                           [description]
     */
    public function getSkuById($sku_id)
    {
        $where = [
            ['sku_id', '=', $sku_id],
            ['is_del', '=', 0],
        ];
        return $this->MallGoodsSkuModel->getOne($where);
    }

    /**
     * 获取某商品下属sku数据
     * @param  [type] $goods_id 商品ID
     * @return [type]           [description]
     */
    public function getSkuByGoodsId($goods_id)
    {
        $where = [];
        $where[] = ['goods_id', '=', $goods_id];
        $where[] = ['is_del', '=', 0];
        $data = $this->MallGoodsSkuModel->getSome($where);
        return $data->toArray();
    }

    /**
     * 获取完整的sku信息
     * @return [type] [description]
     */
    public function getFullSkuInfo($skuids)
    {
        if (empty($skuids)) return [];
        $skuids = array_unique($skuids);
        $skus = $this->MallGoodsSkuModel->getSkuGoods($skuids);
        if($skus){
            $skus = $skus->toArray();
        }
        else{
            return [];
        }
        
        $store_ids = array_unique(array_column($skus, 'store_id'));
        $stores_data = (new MerchantStore)->getStoresByIds($store_ids);
        if($stores_data){
            $stores_data = $stores_data->toArray();
        }
        else{
            return [];
        }
        $stores = [];
        foreach ($stores_data as $key => $value) {
            $stores[$value['store_id']] = $value;
        }
        $return = [];
        foreach ($skus as $key => $value) {
            $value['store_name'] = $stores[$value['store_id']]['name'] ?? '';
            $value['lng'] = $stores[$value['store_id']]['long'] ?? '';
            $value['lat'] = $stores[$value['store_id']]['lat'] ?? '';
            $value['adress'] = $stores[$value['store_id']]['adress'] ?? '';
            $value['phone'] = $stores[$value['store_id']]['phone'] ?? '';
            $value['have_mall'] = $stores[$value['store_id']]['have_mall'] ?? '';
            $return[$value['sku_id']] = $value;
        }
        return $return;
    }

    /**
     * @param $sku_field
     * @param $sku_where
     * @return array
     * 根据条件获取sku
     * @author zhumengqun
     */
    public function getSkuByCondition($sku_field, $sku_where)
    {
        $arr = $this->MallGoodsSkuModel->getSkuByCondition($sku_field, $sku_where);
        return $arr;
    }

    /**
     * @param $where
     * @param $act_name
     * @return string
     * 根据类型获取sku信息
     */
    public function getActSku($where, $act_name)
    {
        switch ($act_name) {
            case 'bargain':
                $arr = (new MallNewBargainSku())->getBySkuId($where);
                break;
            case 'give':
                $arr = (new MallFullGiveGiftSku())->getBySkuId($where);
                break;
            case 'limited':
                $arr = (new MallLimitedSku())->getBySkuId($where);
                break;
            case 'group':
                $arr = (new MallNewGroupSku())->getBySkuId($where);
                break;
            case 'prepare':
                $arr = (new MallPrepareActSku())->getBySkuId($where);
                break;
            default:
                $arr = '';
        }
        return $arr;
    }

    /**
     * @param $where
     * @param $data
     * 设置商品的sku
     */
    public function setSku($where, $data)
    {
        $res = $this->MallGoodsSkuModel->setSku($where, $data);
        return $res;
    }

    /**
     * @param $where
     * @return mixed
     *根据条件删除
     */
    public function delSome($where)
    {
        if (empty($where)) {
            throw new \think\Exception('缺少where条件');
        }
        return $this->MallGoodsSkuModel->delSome($where);
    }

    /**
     * @param $data
     * 添加
     */
    public function addOne($data)
    {
        return $this->MallGoodsSkuModel->addOne($data);
    }

    /**
     * 库存变动
     * @param  [type]  $sku_id   sku
     * @param  [type]  $goods_id spu
     * @param  integer $type     -1=减库存 1=加库存
     * @param  integer $nums     库存变动的数量
     * @return [type]            true or false
     */
    public function changeStock($sku_id, $goods_id, $type = -1, $nums = 0,$activity_type='normal'){
        if(empty($sku_id) || empty($goods_id)) return false;

        fdump_api(func_get_args(),'mall_goods_change_stock',true);
        if(!in_array($type, [-1, 1])) throw new \think\Exception("库存变动方法changeStock 传递参数不正确！");
        $goods_info = (new MallGoods)->getOne($goods_id);
        if($nums <= 0){
            fdump_sql(['sku_id'=>$sku_id,'goods_id'=>$goods_id,'activity_type'=>$activity_type,'nums'=>$nums,'msg'=>"库存变动方法changeStock 传递参数不正确！",'time'=>date('Y-m-d H:i:s',time())],'changeStock');
//             throw new \think\Exception("库存变动方法changeStock 传递参数不正确！");
            return false;
        }
        $sku_info = $this->MallGoodsSkuModel->getOne([['sku_id','=',$sku_id]]);
        if(!$goods_info){
            return true;
        }
        if(!$sku_info || $goods_id != $sku_info['goods_id']){
            fdump_sql(['sku_id'=>$sku_id,'goods_id'=>$goods_id,'activity_type'=>$activity_type,'nums'=>$nums,'msg'=>"扣库存时，检测到sku与spu不匹配",'time'=>date('Y-m-d H:i:s',time())],'changeStock');
            if($type == -1){
                if($goods_info['stock_num'] < $nums){
                    fdump_sql(['sku_info'=>$sku_info,'sku_id'=>$sku_id,'goods_id'=>$goods_id,'activity_type'=>$activity_type,'nums'=>$nums,'msg'=>"库存不足，扣除库存失败"],'changeStock');
                    throw new \think\Exception("库存不足，扣除库存失败");
                }
                (new MallGoods)->updateThis(['goods_id'=>$goods_id], ['stock_num'=>$goods_info['stock_num']-$nums]);
            }
            else{
                (new MallGoods)->updateThis(['goods_id'=>$goods_id], ['stock_num'=>$goods_info['stock_num']+$nums, 'sale_num'=>$goods_info['sale_num']-$nums]);
            }
            return true;
            //throw new \think\Exception("扣库存时，检测到sku与spu不匹配");
        }
        if($sku_info['stock_num'] == -1 && $type==1){
            //库存为-1时，库存不用变更，销量需要变更（只需要处理销量减少。销量增加已经被单独处理）
            (new MallGoods)->updateThis(['goods_id'=>$goods_id], ['sale_num'=>$goods_info['sale_num']-$nums]);
            return true;
        }
        if($sku_info['stock_num'] == -1){
            return true;
        }
        /*if($activity_type=='limited'){
            return true;
        }else{*/
            if($type == -1){
                if($sku_info['stock_num'] < $nums){
                    fdump_sql(['sku_info'=>$sku_info,'sku_id'=>$sku_id,'goods_id'=>$goods_id,'activity_type'=>$activity_type,'nums'=>$nums,'msg'=>"库存不足，扣除库存失败",'time'=>date('Y-m-d H:i:s',time())],'changeStock');
                    throw new \think\Exception("库存不足，扣除库存失败");
                }
                $this->MallGoodsSkuModel->updateThis(['sku_id'=>$sku_id], ['stock_num'=>$sku_info['stock_num']-$nums]);
                (new MallGoods)->updateThis(['goods_id'=>$goods_id], ['stock_num'=>$goods_info['stock_num']-$nums]);
            }
            else{
                $this->MallGoodsSkuModel->updateThis(['sku_id'=>$sku_id], ['stock_num'=>$sku_info['stock_num']+$nums]);
                (new MallGoods)->updateThis(['goods_id'=>$goods_id], ['stock_num'=>$goods_info['stock_num']+$nums, 'sale_num'=>$goods_info['sale_num']-$nums]);
            }
        //}
        return true;
    }

}