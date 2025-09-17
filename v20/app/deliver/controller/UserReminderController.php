<?php
/*
 * @Descripttion: 用户催单
 * @Author: wangchen
 * @Date: 2021-02-07 10:45:56
 * @LastEditors: wangchen
 * @LastEditTime: 2021-02-08 16:16:51
 */

namespace app\deliver\controller;

use app\common\model\service\AppPushMsgService;
use app\shop\model\service\order\ShopOrderService;
use app\deliver\model\service\DeliverUserService;
use app\deliver\model\service\DeliverSupplyService;
use app\common\model\service\plan\PlanMsgService;
use think\Request;
use message\Jpush;
use net\Http;

class UserReminderController extends ApiBaseController
{

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
     * 用户催单
     * @author: 汪晨
     * @date: 2021/2/7
     */
    // 用户催单
    public function user_reminder()
    {
        // 获取订单信息
        $order_id = $this->request->param('order_id', '', 'trim');
        $order = (new ShopOrderService())->getOneOrder($order_id);
        if (empty($order)) {
            return api_output(1001, [], '订单信息不正确');
        }
        // 用户是否可以催单 平台配送才会显示
        if($order['is_pick_in_store']==0 && in_array($order['order_status'], [2,3,4]) && $order['expect_use_time']+900 < time()){

            if ($order['user_reminder']>=3) {
                return api_output(1001, [], '本单催单次数已达上限，不能催单了');
            }

            if (time()-$order['reminder_time']<120) {
                return api_output(1001, [], '您已催过单了，配送员已经在快马加鞭的为您配送了');
            }

            $data['user_reminder'] = $order['user_reminder'] + 1;
            $data['reminder_status'] = 0;
            $data['reminder_time'] = $_SERVER['REQUEST_TIME'];

            if((new ShopOrderService())->saveOneOrder($order['order_id'], $data)){

                $supply = (new DeliverSupplyService())->getOneOrder(array('order_id' => $order_id));
                $deliver_user = (new DeliverUserService())->getOneUser([['uid', '=', $supply['uid']], ['last_time', '<>', 0]]);
                if(!$deliver_user){
                    return api_output(1001, [], '催单失败，请稍后重试！');
                }
                $jpush = new Jpush();

                $href = "miniapp://__UNI__8799035&pages/reminder/index/index";
                $title = '订单提醒';
                $msgl = '用户催单已超时未送达订单，等待您的处理';

                $mp3_label = 'new_reminder_order_yonghucuidan_'.substr(md5('new_reminder_order_chaoshitixing'),0,16);
                
                $client = $deliver_user['client'];
                if ($client == 1) {
                    $device_id = str_replace('-', '', $deliver_user['device_id']);
                    $audience = array('tag' => array($device_id));
                } else {
                    $audience = array('tag' => array($deliver_user['device_id']));
                }

                $notification = $message = '';

                //$voice_return = json_decode($this->voicBaidu(), true);
                //$voice_access_token = $voice_return['access_token'];
                $sys_mp3 = $this->config['deliver_reminder_mp3'];
                if($sys_mp3){
                    $voice_mp3 = $this->config['site_url'].$this->config['deliver_reminder_mp3'];
                }else{
                    //$voice_mp3 = 'http://tsn.baidu.com/text2audio?tex=' . $msgl . '&lan=zh&tok=' . $voice_access_token . '&ctp=1&cuid=9B9A62EE-3EE8-45E0-9C06-2E3245AE3FF5';
                    $voice_mp3 = text2audio($msgl);
                }

                $js_url = $href;

                $js_url = str_replace('http://','https://',$js_url);

                $look_url = $this->config['site_url'].'/packapp/deliver/detail.html?supply_id='.$supply['supply_id'];
                
                $voice_second = 1;  // 语音播报次数
                
                $extra = array(
                    'pigcms_tag' => 'reminder_order',
                    'tag_desc' => $order['real_orderid'],
                    'supply_id' => $supply['supply_id'],
                    'orderStatus' => 'pick',
                    'orderType' =>'merchant_order',
                    'look_url' => $look_url,
                    'voice_mp3' => $voice_mp3,
                    'voice_second' => $voice_second,
                    'url' => $js_url,
                    'js_url' => $js_url,
                    'mp3_label' => $mp3_label ? $mp3_label : 'new_reminder_order_yonghucuidan_'.substr(md5('new_reminder_order_chaoshitixing'),0,16)
                );
                
                $notification = $jpush->createBody($client, $title, $msgl, $extra, 'sound.caf');
                $message = $jpush->createMsg($title, $msgl, $extra);
                
                $columns = array();
                $columns['platform'] = $client == 1 ? array('ios') : array('android');
                $columns['audience'] = $audience;
                $columns['notification'] = $notification;
                $columns['message'] = $message;
                $columns['from'] = 'deliverreminder';
                $columns['business_type'] = 'reminder_order';

                $msg = [
                    'type' => '4', 'content' => array($columns)
                ];

                (new PlanMsgService())->addRewardTask($msg);

                return api_output(1001, [], '系统已帮您催单配送员尽快送达');
            }else{
                return api_output(1001, [], '催单失败');
            }
        }else{
            return api_output(1001, [], '不能催单');
        }
    }
}
