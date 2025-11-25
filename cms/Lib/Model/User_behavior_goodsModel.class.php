<?php
/**
 * Created by PigCMS.
 * User: JJC.
 * Date: 2019/11/28.
 * Time: 17:11.
 */

/**
 * Class User_behavior_goodsModel用户行为分析（购买商品）
 */
class User_behavior_goodsModel extends Model{

    public $from_type_config = array(
        '0'=>'wap端',
        '1'=>'安卓',
        '2'=>'ios',
        '3'=>'小程序',
        '4'=>'pc端',
        '5'=>'其他',
        '6'=>'移动端'
    );
    public $business_type_config = array(
        '0'=>'老版商城',
        '3'=>'新版商城',
        '1'=>'快店',
        '2'=>'团购',
        '4'=>'社区拼团'
    );
    /**
     * @desc 记录用户行为（购买商品）
     * @param $goods_id:商品id
     * @param $name:商品名称
     * @param $uid:用户id
     * @param $business_type:业务类型：0商城，1快店，2团购,3新版商城，4社区团购
     * @param $from_type:来源：0wap端，1安卓,2ios，3小程序，4pc端，5其他
     * @param $order_id：订单id
     * @param int $num：购买数量，默认1
     * @param $add_time:购买时间，一般是支付时间，默认当前时间
     * @param $add_ip:用户ip
     * @return bool
     */
    public function add_record($goods_id,$name,$uid,$business_type,$from_type,$order_id,$num=1,$add_time='',$add_ip=''){
        $data = [
            'goods_id' =>$goods_id,
            'name' =>$name,
            'uid' =>$uid,
            'business_type' =>$business_type,
            'from_type' =>$from_type,
            'order_id' =>$order_id,
            'num' =>$num,
            'add_time' =>$add_time?$add_time:time(),
            'add_ip' =>$add_ip?ip2long($add_ip): ip2long($_SERVER["REMOTE_ADDR"]),
        ];
        $r = $this->data($data)->add();
        fdump(M()->getLastSql(),'User_behavior_goods_log',1);//记录下插入日志
        return  $r? true : false;
    }
}

?>