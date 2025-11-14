<?php

/**
 * 系统后台店铺店员model
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/5/26 15:11
 */

namespace app\foodshop\model\service\store;

use app\common\model\service\plan\PlanMsgService;
use app\common\model\service\weixin\TemplateNewsService;
use app\foodshop\model\service\order\DiningOrderPayService;
use app\merchant\model\service\storestaff\MerchantStoreStaffService as MerchantStoreStaffObj;
use app\foodshop\model\service\store\FoodshopQueueService;
use app\foodshop\model\service\store\MerchantStoreFoodshopService;
use app\common\model\service\send_message\WebPushMsgService;
use think\Exception;
use app\merchant\model\service\VoiceBoxService;
use message\Jpush;

class MerchantStoreStaffService
{
    public function __construct()
    {
    }


    /**
     * 餐饮通知店员
     * @param $order
     * @param $payParam array 支付后的支付数据
     * @return array
     */
    public function sendMsgFoodShop($order)
    {
        $merchantStoreStaffObj = new MerchantStoreStaffObj();
        $staffs = array();
        if ($order['table_id']) {
            // 桌台信息
            $table = (new FoodshopTableService())->geTableById($order['table_id']);
            if ($table && $table['staff_id']) { //桌台绑定店员
                $staffs[] = $merchantStoreStaffObj->getStaffById($table['staff_id']);
            }
        }

        // 桌台没有绑定店员
        if (empty($staffs)) {
            $where[] = ['store_id', '=', $order['store_id']];
            $where[] = ['is_notice', '=', 0];
            $where[] = ['last_time', '>', 0];
            $staffs = $merchantStoreStaffObj->getStaffListByCondition($where);
        }

        if(empty($staffs)){// 没有店员
            return true;
        }

        $newOrder = false;
        $jpush = new Jpush();
        $mp3 = '';
        if ($order['status'] == 100) {
            //            $href = cfg('config_site_url') . '/packapp/storestaff/foodshop_order_list.html?status=1&from=message';
            $title = L_('客人呼叫服务');
//            $msg = L_("【X1】的客人呼叫服务，请您及时提供服务",array("X1" => $table['name']));
//			$mp3_label = 'new_foodshop_order_hujiaofuwu_'.substr(md5($table['name']),0,16).substr(md5(cfg('config_site_url')),0,16);
        } elseif ($order['status'] == 1) {      
        	if ($order['order_from'] == 2) { //店员下单不提示
	    		return false;
	    	}      
            $newOrder = true;

            $href = "miniapp://".cfg('staff_uni_appid')."&pages/foodshop/index/index";
            $hrefH5 = cfg('site_url') . "/packapp/staff/#/pages/foodshop/index/index";
            $title =  L_('X1新订单提醒', cfg('meal_alias_name'));
            $msg = L_('X1新订单提醒，请及时查看！', cfg('meal_alias_name'));
            $mp3 = replace_file_domain(cfg('storestaff_foodshop_new_order'));
            $mp3_label = 'new_foodshop_order_neworder_' . substr(md5(cfg('config_site_url'). $mp3), 0, 16);
            (new VoiceBoxService())->sendMsgToVoiceBox($order['store_id'], $msg);
        } elseif ($order['status'] == 30) {
        	if ($order['order_from'] == 2) { //店员下单不提示
	    		return false;
	    	}
            $href = "miniapp://".cfg('staff_uni_appid')."&pages/foodshop/index/index";
            $hrefH5 = cfg('site_url') . "/packapp/staff/#/pages/foodshop/index/index";
	    	
            $title = L_('X1订单支付成功',cfg('meal_alias_name'));
            $msg = L_('X1订单支付成功，请及时处理！',cfg('meal_alias_name'));
            $mp3 = replace_file_domain(cfg('storestaff_foodshop_new_order'));
            $mp3_label = 'new_foodshop_order_payorder_'.substr(md5(cfg('config_site_url').$mp3),0,16);

            if (in_array($order['order_from'], [1, 4]) && $order['settle_accounts_type'] == 2) { //扫码点餐和直接选菜先付后吃第一次支付新订单云音响提醒
                $where = [
                    'paid' => 1,
                    'order_id' => $order['order_id']
                ];
                $count = (new DiningOrderPayService)->getCount($where);
                fdump($count,'sendMsgFoodShop',1);
                if ($count == 1) {
                    $newOrder = true;
                    $title =  L_('X1新订单提醒', cfg('meal_alias_name'));
                    $msg = L_('X1新订单提醒，请及时查看！', cfg('meal_alias_name'));
                    (new VoiceBoxService())->sendMsgToVoiceBox($order['store_id'], $msg);
                }
            }
        }else {
            $href = "miniapp://".cfg('staff_uni_appid')."&pages/foodshop/index/index";
            $hrefH5 = cfg('site_url') . "/packapp/staff/#/pages/foodshop/index/index";

            if (!isset($order['status']) || !in_array($order['status'], [1, 4])) {
                $href = "miniapp://".cfg('staff_uni_appid')."&pages/foodshop/index/index?orderType=2";
                $hrefH5 = cfg('site_url') . "/packapp/staff/#/pages/foodshop/index/index?orderType=2";
            }
            $title =  L_('X1上菜提醒', cfg('meal_alias_name'));
            $msg = L_('X1上菜提醒，请及时确认菜品！', cfg('meal_alias_name'));
            $mp3_label = 'new_foodshop_order_shangcaitixing_' . substr(md5(cfg('config_site_url')), 0, 16);
        }

        if($newOrder){// PC店员通知
            $data['business_type'] = 'dining';
            $data['operate_type'] = 'new_order';
            $data['platform'] = 'pc';
            $data['store_id'] = $order['store_id'];
            if($order['is_self_take'] == 1){// 自取订单 跳转全部订单列表
                $data['url'] = cfg('site_url') . '/v20/public/platform/#/storestaff/storestaff.cashier/cashier/query';
            }
            (new WebPushMsgService())->add($data);
        }
        
        $msgl = $msg;
        $titlel = $title;

        foreach ($staffs as $staff) {
            if (empty($staff)) {
                continue;
            }
            $titlel = $title;
            $hello = L_('您好！');
            $msgl = $msg;
        	if(isset($staff['use_lang']) && $staff['use_lang']){
        		cfg('tmp_system_lang', $staff['use_lang']);
        		$titlel = L_($title);
        		$hello = L_('您好！');
        		$msgl = L_($msg);
        	}
            if ($staff['client'] == 0 && $staff['openid']) {
                $msgData = [
                    'href' => $hrefH5,
                    'wecha_id' => $staff['openid'],
                    'first' => $staff['name'] . $hello . $titlel,
                    'OrderSn' => $order['real_orderid'],
                    'OrderStatus' => L_('待处理'),
                    'remark' => L_('请您及时处理！')
                ];
                (new TemplateNewsService())->sendTempMsg('TM00017', $msgData, $order['mer_id']);
            } elseif ($staff['client'] > 0) {
                if (!cfg('staff_jpush_appkey') && ($staff['client'] != 1 || !cfg('pushkit_cert'))) continue;
                if ($staff['device_id']) {
                    $client = $staff['client'];
                    if ($client == 1) {
                        $device_id = str_replace('-', '', $staff['device_id']);
                        $audience = array('tag' => array($device_id));
                    } else {
                        $audience = array('tag' => array($staff['device_id']));
                    }
                    $notification = $message = '';


                    //$voice_return = json_decode($merchantStoreStaffObj->voicBaidu(), true);
                    //$voice_access_token = $voice_return['access_token'];
                    //$voice_mp3 = 'http://tsn.baidu.com/text2audio?tex=' . $msgl . '&lan=zh&tok=' . $voice_access_token . '&ctp=1&cuid=9B9A62EE-3EE8-45E0-9C06-2E3245AE3FF5';
                    $voice_mp3 = $mp3 ? $mp3 : text2audio($msgl);

                    //                    $url = cfg('config_site_url') . '/packapp/storestaff/index.html?gopage=foodshop';
                    //					  $url = str_replace('http://','https://',$url);

                    $js_url = $href;

                    $js_url = str_replace('http://', 'https://', $js_url);

                    $voice_second = cfg('storestaff_app_voice_time');
                    if (empty($voice_second)) {
                        $voice_second = 6;
                    }

                    $extra = array(
                        'pigcms_tag' => 'foodshop_order',
                        'tag_desc' => $order['real_orderid'],
                        'voice_mp3' => $voice_mp3,
                        'voice_second' => $voice_second,
                        'url' => $js_url,
                        'js_url' => $js_url,
                        'mp3_label' => $mp3_label ? $mp3_label : 'new_foodshop_order_new' . substr(md5(cfg('config_site_url')), 0, 16)
                    );

                    $notification = $jpush->createBody($client, $titlel, $msgl, $extra, 'sound.caf');
                    $message = $jpush->createMsg($titlel, $msgl, $extra);

                    $columns = array();
                    $columns['platform'] = $client == 1 ? array('ios') : array('android');
                    $columns['audience'] = $audience;
                    $columns['notification'] = $notification;
                    $columns['message'] = $message;
                    $columns['from'] = 'storestaff';               
                    ($staff['is_app_native']) && $columns['business_type'] = 'new_order_dining';

                    $msgArr = [
                        'type' => '4', 'content' => array($columns)
                    ];
                    (new PlanMsgService())->addTask($msgArr);
                }
            }
        }

        cfg('tmp_system_lang', '');
    }

    /**
     * 线下语音播报通知顾客
     * @param $param
     * @return array
     */
    public function sendMsgQueue($param)
    {
        $foodshopQueueService = new FoodshopQueueService();
        $where = [];
        $where['id'] = $param['id'];
        //排号基本信息
        $info = $foodshopQueueService->getInfo($where);
        if (empty($info)) {
            throw new Exception('排号信息不存在');
        }

        if ($info['status'] != 0) {
            throw new Exception('不在排队中');
        }

        if ($info['queue_from'] == 0) { //如果是线上排号，叫号发送到号提醒通知
            //桌台类型信息
            $foodshopTableTypeService = new FoodshopTableTypeService();
            $tableTypeInfo = $foodshopTableTypeService->getTableTypeInfoByCondition($where = ['id' => $info['table_type']]);
            $info['count'] = 0;
            $info['people_num'] = L_('X1-X2人', ['X1' => $tableTypeInfo['min_people'], 'X2' => $tableTypeInfo['max_people']]) . '桌';
            (new FoodshopQueueService())->sendMessage(2, $info);
        }

        //店铺基本信息
        $merchantStoreFoodshopService = new MerchantStoreFoodshopService();
        $store = $merchantStoreFoodshopService->getStoreByStoreId($info['store_id']);
        if (!empty($store['queue_content'])) {
            $msg = str_replace('{$a}', $info['number'], $store['queue_content']);
        } else {
            $msg = '请' . $info['number'] . '号顾客准备就餐！';
        }

        $msgl = L_($msg);

        (new VoiceBoxService())->sendMsgToVoiceBox($info['store_id'], $msgl);
        
        $merchantStoreStaffObj = new MerchantStoreStaffObj();
        //$voice_return = json_decode($merchantStoreStaffObj->voicBaidu(), true);
        //$voice_access_token = $voice_return['access_token'];
        //$voice_mp3 = 'http://tsn.baidu.com/text2audio?tex=' . $msgl . '&lan=zh&tok=' . $voice_access_token . '&ctp=1&cuid=9B9A62EE-3EE8-45E0-9C06-2E3245AE3FF5';
        $voice_mp3 = text2audio($msgl);

        $file = @file_get_contents($voice_mp3);

        $filepath = "/v20/runtime/foodshop/vioce/".uniqid().".mp3";

        //写入mp3文件

        $dirName = dirname(request()->server('DOCUMENT_ROOT').$filepath);
        if(!file_exists($dirName)){
            mkdir($dirName,0777,true);
        }
        if(!file_exists(request()->server('DOCUMENT_ROOT').$filepath)){
            file_put_contents(request()->server('DOCUMENT_ROOT').$filepath,$file);

        }
        $data = ['url' => cfg('site_url').$filepath];
        return $data;
    }
}
