<?php
/**
 * 餐饮发送短信service
 * Author: hengtingmei
 * Date Time: 2020/12/01 17:57
 */

namespace app\foodshop\model\service\message;
use app\common\model\service\send_message\SmsService;
use app\common\model\service\UserService;
use app\merchant\model\service\MerchantStoreService;

class SmsSendService {
    /**
     * 发送短信
     * @param $param int
     * @return array
     */
    public function sendSms($param){
        $type = $param['type'] ?? '';
        $phone = $param['phone'] ?? '';
        $order = $param['order'] ?? [];
        $nowStore = $param['store'] ?? [];

        if(empty($phone) || empty($type)){
            return false;
        }

        switch ($type){
            case 'order_taking':// 店员接单
                // 店铺信息
                if(empty($nowStore)){
                    $nowStore = (new MerchantStoreService())->getStoreByStoreId($order['store_id']);
                }

                // 短信内容
                // 【小猪o2o】您在2020-11-24 13：46：00 预约了老乡鸡（天鹅湖店） 商家已接单，订单号1111111111111，请及时到店就餐。祝您用餐愉快！
                $text = L_('您在X1预约了X2商家已接单，订单号X3，请及时到店就餐。祝您用餐愉快！',['X1'=>date('Y-m-d H:i:s',$order['create_time']),'X2'=>$nowStore['name'],'X3'=>$order['real_orderid']]);

                $smsData = [
                    'mer_id' => $order['mer_id'],
                    'store_id' => $order['store_id'],
                    'content' => $text,
                    'mobile' => $phone,
                    'uid' => $order['uid'],
                    'type' => 'user'
                ];
                $order['phone_country_type'] && $smsData['nationCode']  = $order['phone_country_type'];

                fdump('$smsData','sendSmsFoodshop',1);
                fdump($smsData,'sendSmsFoodshop',1);
                (new SmsService())->sendSms($smsData);
                break;
            case 'order_cancel':// 取消订单
                // 店铺信息
                if(empty($nowStore)){
                    $nowStore = (new MerchantStoreService())->getStoreByStoreId($order['store_id']);
                }

                // 短信内容
                // 【小猪o2o】您在2020-11-24 13：46：00   预约的老乡鸡（天鹅湖店）订单 商家已为您取消。给您带来不便，敬请谅解。祝您生活愉快！
                $text = L_('您在X1预约的X2订单，商家已为您取消。给您带来不便，敬请谅解。祝您生活愉快！',['X1'=>date('Y-m-d H:i:s',$order['create_time']),'X2'=>$nowStore['name']]);

                $smsData = [
                    'mer_id' => $order['mer_id'],
                    'store_id' => $order['store_id'],
                    'content' => $text,
                    'mobile' => $phone,
                    'uid' => $order['uid'],
                    'type' => 'user'
                ];
                $order['phone_country_type'] && $smsData['nationCode']  = $order['phone_country_type'];

                (new SmsService())->sendSms($smsData);
                break;
        }
        return true;
    }

}