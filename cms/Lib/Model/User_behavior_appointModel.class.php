<?php
/**
 * Created by PigCMS.
 * User: JJC.
 * Date: 2019/11/29.
 * Time: 11:19.
 */

/**
 * Class User_behavior_appointModel用户行为分析（预约服务）
 */
class User_behavior_appointModel extends Model{

    public $from_type_config = array(
        '0'=>'wap端',
        '1'=>'安卓',
        '2'=>'ios',
        '3'=>'小程序',
        '4'=>'pc端',
        '5'=>'其他',
        '6'=>'移动端'
    );

    /**
     * @desc 记录用户行为（预约服务）记录下已成功支付的预约商品购买记录
     * @param $cat_id：分类id
     * @param $goods_id：预约商品id(没有就传空)
     * @param $uid:用户id
     * @param $name:商品名称(如果没有预约商品就传分类名称)
     * @param $num：商品数量
     * @param $appoint_time：预约时间
     * @param $from_type：0wap端，1安卓,2ios，3小程序，4pc端，5其他
     * @param $order_id：订单id
     * @param string $add_ip:用户ip
     * @return bool
     */
    public function add_record($cat_id,$goods_id,$uid,$name,$num,$appoint_time,$from_type,$order_id,$add_ip=''){
        $data = [
            'cat_id' => $cat_id,
            'goods_id' => $goods_id,
            'uid' => $uid,
            'name' => $name,
            'num' => $num,
            'appoint_time' => $appoint_time,
            'from_type' => $from_type,
            'order_id' => $order_id,
            'add_ip' => $add_ip?ip2long($add_ip): ip2long($_SERVER["REMOTE_ADDR"]),
            'add_time' => time(),
        ];
        $r = $this->data($data)->add();
        fdump(M()->getLastSql(),'User_behavior_appoint_log',1);//记录下插入日志
        return  $r? true : false;
    }
}

?>