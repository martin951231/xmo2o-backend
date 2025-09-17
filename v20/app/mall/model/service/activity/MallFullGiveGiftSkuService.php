<?php


namespace app\mall\model\service\activity;


use app\mall\model\db\MallFullGiveGiftSku;
use think\facade\Db;
class MallFullGiveGiftSkuService
{
    /** 添加数据 获取插入的数据id
     * Date: 2020-10-16 15:42:29
     * @param $data
     * @return int|string
     */
    public function addGiveGiftSku($data) {
        return (new MallFullGiveGiftSku())->addOne($data);
    }

    /** 更新数据
     * Date: 2020-10-16 15:42:29
     * @param array $data
     * @param array|mixed $where
     * @return boolean
     */
    public function updateGiveGiftSku($data,$where) {
        return (new MallFullGiveGiftSku())->updateOne($data,$where);
    }

    /** 删除数据
     * Date: 2020-10-16 15:42:29
     * @param array|mixed $where
     * @return boolean
     */
    public function delGiveGiftSku($where) {
        return (new MallFullGiveGiftSku())->delOne($where);
    }

    /**获取满赠活动赠送商品信息
     * @param $field
     * @param $where
     * @return mixed
     */
    public function getFullGiveGiftSku($fields,$where)
    {
        return (new MallFullGiveGiftSku())->getGiftInfo($fields,$where);
    }

    /**
     * @param $data
     * 赠品支付成功，减去库存
     */
    public function paySuccessMinusStock($data){
        //参数数据结构
        /*$data=[
            [
                'act_id'=>1,//活动id
                'sku_id'=>1,//赠品规格id
                'nums'=>1//赠品数量
            ]
        ];*/
        //启动事务
        Db::startTrans();
        try {
            foreach ($data as $key=>$val){
             $where=[
                 ['act_id','=',$val['act_id']],
                 ['sku_id','=',$val['sku_id']]
             ];
             $msg=(new MallFullGiveGiftSku())->getBySkuId($where);//找出对应sku表的数据
             if(!empty($msg)){
                 if($msg['act_stock_num']!=-1 && $msg['act_stock_num']>0){
                     $msg1['act_stock_num']=$msg['act_stock_num']-$val['nums'];
                     (new MallFullGiveGiftSku())->updateOne($msg1,$where);//判断不是无限库存，减库存
                 }
             }
            }
            Db::commit();//提交
        }catch (\Exception $e){
            Db::rollback();//失败回滚
        }
    }
}