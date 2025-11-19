<?php


namespace app\common\model\service\send_message;


use app\common\model\db\AppapiAppLoginLog;
use app\common\model\db\AppPushMsg;
use app\common\model\db\Config;
use app\common\model\db\Merchant;
use app\common\model\service\plan\PlanMsgService;
use app\common\model\service\weixin\TemplateNewsService;
use app\merchant\model\service\storestaff\MerchantStoreStaffService;
use message\Jpush;

class AppPushMsgService
{
    public function send($order, $type)
    {
        //计划任务发送排序
        $sendSort = 10;
        $sendStatus = 0;
//        $where=array(
//            'uid' => $order['uid'],
//            'client'=>['neq',0],
//            'device_id'=>['neq','packapp']
//        );
        $where[] = ['uid','=',$order['uid']];
        $where[] = ['client','<>',0];
        $where[] = ['device_id','<>','packapp'];
        $login_log=(new AppapiAppLoginLog())->getOne($where,true,"create_time DESC");
        if (empty($login_log)) return false;
        $login_log=$login_log->toArray();
        $client = $login_log['client'];
        if ($client == 1) {
            $device_id = str_replace('-', '', $login_log['device_id']);
            $audience = array('tag' => array($device_id));
        } else {
            $audience = array('tag' => array($login_log['device_id']));
        }
        $notification = $message = '';
        $jpush = new Jpush();
        switch ($type) {
            case 'house_notice':
                $extra = array('pigcms_tag' => 'house_notice', 'tag_desc' => $order['bind_id'], 'url' => $order['url']);
                if(isset($order['pigcms_id'])){
                    $extra['pigcms_id']=$order['pigcms_id'];
                }
                if(isset($order['village_id'])){
                    $extra['village_id']=$order['village_id'];
                }
                $title = $order['title'];
                $msg = $order['msg'];
                $notification = $jpush->createBody(3, $title, $msg, $extra);
                $message = $jpush->createMsg($title, $msg, $extra);
                break;
            case 'seckill_remind':
                $extra = array('pigcms_tag' => 'limit', 'tag_desc' => "秒杀活动即将开启", 'url' => cfg('site_url') . '/packapp/plat/pages/shopmall_third/commodity_details?goods_id=' . $order['goods_id']);
                $title =  L_('开抢提醒');
                $msg =  L_('您关注的限时秒杀即将开始，更多信息请查看详情！');
                $notification = $jpush->createBody(3, $title, $msg, $extra);
                $message = $jpush->createMsg($title, $msg, $extra);
                break;
            case 'mall3':
                $status = [
                    '0' => '待支付',
                    '1' => '待支付',//尾款待支付
                    '10' => '待发货',
                    '11' => '备货中',
                    '12' => '已顺延',
                    '13' => '待成团',
                    '20' => '已发货',
                    '30' => '已收货',//用户端的已收货对应的平台 商家  店铺的已完成
                    '31' => '已自提',//用户端确认自提
                    '32' => '已核销',//店员确认核销
                    '33' => '骑手已送达',//骑手确认送达
                    '40' => '已完成',
                    '50' => '已取消',
                    '51' => '超时取消',
                    '52' => '用户取消',
                    '60' => '申请售后',
                    '70' => '已退款',
                ];
                if (isset($status[$order['status']])) {
                    $extra = array('pigcms_tag' => 'mall3', 'tag_desc' => $order['order_no'], 'url' => get_base_url('pages/shopmall_third/orderDetails?order_id=' . $order['order_id']));
                    $title = cfg('mall_alias_name_new') . L_('订单状态变更提醒');
                    $msg = cfg('mall_alias_name_new') . L_('订单状态修改成【' . $status[$order['status']] . '】，更多信息请查看详情！');
                    $notification = $jpush->createBody(3, $title, $msg, $extra);
                    $message = $jpush->createMsg($title, $msg, $extra);
                }
                break;
        }

        $columns = array();
        $columns['platform'] = $client == 1 ? array('ios') : array('android');
        $columns['audience'] = $audience;
        $columns['notification'] = $notification;
        $columns['message'] = $message;
        $columns['from'] = $type;
        $msg_content = [
            'type' => '4',
            'content' => [$columns],
            'sort' => intval($sendSort),
            'status' => $sendStatus
        ];
        $task_id = (new PlanMsgService())->addTask($msg_content);
        return $task_id;
    }

    //app店员端推送
    public function sendMsg($storeId,$order=array(),$type='malls')
    {
        $merchantStoreStaffObj = new MerchantStoreStaffService();
        $where[] = ['store_id', '=', $order['store_id']];
        $where[] = ['is_notice', '=', 0];
        $where[] = ['last_time', '>', 0];
        $staffs = $merchantStoreStaffObj->getStaffListByCondition($where);
        if($staffs){
//            if (cfg('open_multilingual')) {
//                $merchant_lang = (new Merchant())->where(['mer_id' => $staffs[0]['token']])->field('merchant_default_lang')->find();
//                $merchant_lang = $merchant_lang['merchant_default_lang'] ?? '';
//                if(isset($merchant_lang['merchant_default_lang']) && $merchant_lang != cfg('system_lang')){
//                    cfg('tmp_system_lang',$merchant_lang);
//                    $tmp_config = (new Config())->get_config($merchant_lang);
//                }
//            }
        }else{
            fdump_html(['店铺ID' => $storeId, '商家ID' => $order['mer_id'], '订单ID' => $order['order_no']??''], 'api/log/noStaffMsg', true);
        }


        $jpush = new Jpush();
        $href = cfg('config_site_url') . '/packapp/storestaff/index.html';
        if ($order['order_no']) {
            $real_orderid = $order['order_no'];
        } else {
            $real_orderid = '';
        }
        /*区分商城*/
        $alias_name =  cfg('mall_alias_name_new');
        $addData =[];
        foreach ($staffs as $staff) {
            if ($staff['client'] == 0 && $staff['openid']) {
                $msgDataWx = [
                    'href' => $href,
                    'wecha_id' => $staff['openid'],
                    'new_info' => [
                        'tempKey' => 44716,
                        'thing12' => L_("新订单提醒"),
                        'phrase2' => $alias_name,
                        'time13' => date("Y-m-d H:i:s", time()),
                        'thing11' => $real_orderid
                    ]
                ];
                (new TemplateNewsService())->sendTempMsg('OPENTM400166399', $msgDataWx);
            } elseif($staff['client'] > 0){
                if ($staff['device_id']) {
                    $client = $staff['client'];

                    if ($client == 1) {
                        $device_id = str_replace('-', '', $staff['device_id']);
                        $audience = array('tag' => array($device_id));
                    } else {
                        $audience = array('tag' => array($staff['device_id']));
                    }

                    if($staff['jpush_registrationId']){
                        $audience['registration_id'] = [$staff['jpush_registrationId']];
                    }
                    $title = L_('X1新订单提醒', ['X1' => $alias_name]);
                    $msg = L_('X1新订单提醒，请及时查看！', ['X1' => $alias_name]);
                    $voice_second = cfg('storestaff_app_voice_time');
                    if(empty($voice_second)){
                        $voice_second = 6;
                    }
                    if(cfg('mall_send_goods_mp3')){
                        $voice_mp3 = replace_file_domain(cfg('mall_send_goods_mp3'));
                    }else{
                        $voice_mp3 = (new MerchantStoreStaffService())->getstaffNewOrderVoice(L_('您有x1个新订单需要处理',1));
                    }
                    $url = cfg('config_site_url') . '/packapp/storestaff/index.html?gopage=mall' ;
//                    $js_url = cfg('config_site_url') . '/packapp/storestaff/mall.html';


                    $url = str_replace('http://','https://',$url);
//                    $js_url = str_replace('http://','https://',$js_url);
                    $js_url = "miniapp://".cfg('staff_uni_appid')."&pages/mall/index/index";
                    $extra = array(
                        'pigcms_tag' => 'mall_order',
                        'tag_desc' => $order['order_no'],
                        'voice_mp3' => $voice_mp3,
                        'voice_second' => $voice_second,
                        'url' => $url,
                        'js_url' => $js_url,
                        'mp3_label' => 'new_mall_order'.substr(md5($voice_mp3),8,16).substr(md5(cfg('config_site_url')),8,16)
                    );
                    $notification = $jpush->createBody($client, $title, $msg, $extra, 'group_sound.caf');
                    $message = $jpush->createMsg($title, $msg, $extra);
                    $columns = array();
                    $columns['platform'] = $client == 1 ? array('ios') : array('android');
                    $columns['audience'] = $audience;
                    $columns['notification'] = $notification;
                    $columns['message'] = $message;
                    $columns['from'] = 'storestaff';
                    $columns['business_type'] = 'new_order_mall';
                    $columns = serialize([$columns]);
                    $serializeParam = serialize([
                        'sort'=>0,
                        'type'=>4,
                        'label'=>'',
                        'content'=>$columns,
                        'send_time'=>time(),
                        'add_time'=>time()
                    ]);
                    $addData[] = [
                        'device_id' => $staff['device_id'],
                        'status' => 0,
                        'platform' => $client == 1 ? 'ios' : 'android',
                        'business_type' => 'new_order_mall',
                        'add_time' => time(),
                        'data' => $serializeParam
                    ];
                }
            }
        }
        if($addData){
            (new \app\common\model\service\AppPushMsgService())->addAll($addData);
        }
    }
}