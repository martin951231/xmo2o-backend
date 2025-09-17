<?php
/*
 * @Descripttion: 打赏通知服务
 * @Author: wangchen
 * @Date: 2021-01-31 17:04:00
 * @LastEditors: wangchen
 * @LastEditTime: 2021-02-07 16:39:23
 */

namespace app\reward\model\service\store;

use app\common\model\service\plan\PlanMsgService;
use app\reward\model\db\DeliverRewardOrder;
use app\deliver\model\service\DeliverUserService;
use app\deliver\model\service\DeliverSupplyService;
use app\shop\model\service\order\ShopOrderService;
use message\Jpush;
use net\Http;

class RewardOrderPushService{
	
    public $DeliverRewardOrderMod = null;

    public function __construct()
    {
        $this->deliverRewardOrderMod = new DeliverRewardOrder();
    }

    public function voicBaidu()
    {
        static $return;

        if (empty($return)) {
            $voicBaidu = "https://openapi.baidu.com/oauth/2.0/token?grant_type=client_credentials&client_id=XU7nTWQzS9vn32bckmrhHbTu&client_secret=1375720f23eed9b7b787e728de5dd1c2";
            $return = Http::curlGet($voicBaidu);
        }
        return $return;
    }

    
    /**
     * 打赏通知骑手
     * @param $order 
     * @return array 
     */
	public function sendMsgReward($order){
        // 骑手信息
        if ($order['user_id']) {
			$staff = (new DeliverUserService())->getOneUser(['uid'=>$order['user_id']]);   
        }
        
        // 获取外卖订单信息
        if ($order['user_id']) {
            $shopCont = (new ShopOrderService())->getOneOrder(['order_id'=>$order['takeout_id']],'fetch_number,address,last_time');
            $deliverCont = (new DeliverSupplyService())->getOneOrder(['order_id'=>$order['takeout_id']],'store_name');
            if($deliverCont['store_name']){
                $order_address = $deliverCont['store_name'];
            }else{
                $order_address = $shopCont['address'];
            }
        }
        
        $jpush = new Jpush();

        $href = "miniapp://__UNI__8799035&pages/reward/index/index";
        $title =  '顾客答谢小费'.$order['order_money'].'元';
        $msgl = '收到【#'.$shopCont['fetch_number'].'】'.$order_address.' 订单的顾客答谢小费';
                
        // $msg = ['address'=>$shopCont['address'],'order_money'=>$order['order_money'],'last_time'=>$shopCont['last_time'],'create_time'=>$shopCont['create_time']];
        
        $mp3_label = 'new_reward_order_daxietixing_'.substr(md5('new_reward_order_daxietixing'),0,16);

        $msg = '您有一笔打赏收入，注意查收。';
        
        if ($staff['device_id']) {
            $client = $staff['client'];
            if ($client == 1) {
                $device_id = str_replace('-', '', $staff['device_id']);
                $audience = array('tag' => array($device_id));
            } else {
                $audience = array('tag' => array($staff['device_id']));
            }

            $notification = $message = '';

            //$voice_return = json_decode($this->voicBaidu(), true);
            //$voice_access_token = $voice_return['access_token'];
            //$voice_mp3 = 'http://tsn.baidu.com/text2audio?tex=' . $msg . '&lan=zh&tok=' . $voice_access_token . '&ctp=1&cuid=9B9A62EE-3EE8-45E0-9C06-2E3245AE3FF5';
            $voice_mp3 = text2audio($msg);

            $js_url = $href;

            $js_url = str_replace('http://','https://',$js_url);
            
            $voice_second = 1;  // 语音播报次数
            
            $extra = array(
                // 'sub_title'     => '收到['.$shopCont['address'].']订单的顾客答谢小费'.$order['order_money'].'元',
                'service_time'   => '送达时间：'.date('m月d日 H:i',$shopCont['last_time']),
                'thank_time'   => '答谢时间：'.date('m月d日 H:i',$order['create_time']),
                'rewar_dmoney'   => '答谢金额：'.$order['order_money'].'元',
                'remarks'   => '答谢金额将直接打入您的账户余额，继续加油送单赚取更多小费吧~',
                'pigcms_tag' => 'reward_order',
                'tag_desc' => $order['order_no'],
                'voice_mp3' => $voice_mp3,
                'voice_second' => $voice_second,
                'url' => $js_url,
                'js_url' => $js_url,
                'mp3_label' => $mp3_label ? $mp3_label : 'new_reward_order_new'.substr(md5(cfg('config_site_url')),0,16)
            );
            
            $notification = $jpush->createBody($client, $title, $msgl, $extra, 'sound.caf');
            $message = $jpush->createMsg($title, $msgl, $extra);
            
            $columns = array();
            $columns['platform'] = $client == 1 ? array('ios') : array('android');
            $columns['audience'] = $audience;
            $columns['notification'] = $notification;
            $columns['message'] = $message;
            $columns['from'] = 'deliverreward';
            $columns['business_type'] = 'reward_order';

            $msg = [
                'type' => '4', 'content' => array($columns)
            ];

            (new PlanMsgService())->addRewardTask($msg);
        }
    }

}