<?php
/**
 * App推送
 * Author: hengtingmei
 * Date Time: 2021/05/24
 */
namespace app\group\model\service\message;

use app\common\model\service\plan\PlanMsgService;
use app\common\model\service\user\AppapiAppLoginLogService;
use app\common\model\service\weixin\TemplateNewsService;
use app\merchant\model\service\storestaff\MerchantStoreStaffService;
use app\merchant\model\service\VoiceBoxService;
use message\Jpush;

class AppPushService
{
	public function send($order)
	{
		$loginLog = (new AppapiAppLoginLogService())->getOne(['uid' => $order['uid']], true, 'create_time DESC');
       
		if (empty($loginLog)) return false;

		$client = $loginLog['client'];
		if ($client == 1) {
			$device_id = str_replace('-', '', $loginLog['device_id']);
			$audience = array('tag' => array($device_id));
		} else {
			$audience = array('tag' => array($loginLog['device_id']));
		}
		$notification = $message = '';
		$jpush = new Jpush();
	
        $status = array(
            0 => '下单成功',
            1 => '支付成功', 
            2 => '完成评论',
            3 => '拼团成功',
            4 => '已完成退款', 
            5 => '已取消订单',
            6 => '店员已发货' , 
            7 => '店员验证消费',
            8 => '开团成功',
            9 => '参团成功'
        );

        $extra = array(
            'pigcms_tag' => 'group_order', 
            'tag_desc' => $order['real_orderid'], 
            'url' => cfg('site_url') . '/wap.php?c=My&a=group_order&order_id=' . $order['order_id'] // TODO
        );

        $title = cfg('group_alias_name') . L_('订单状态变更提醒');
        $msg = cfg('group_alias_name') . L_('订单状态修改成【' . $status[$order['status']] . '】，更多信息请查看详情！');
        $notification = $jpush->createBody(3, $title, $msg, $extra);
        $message = $jpush->createMsg($title, $msg, $extra);
          

		$columns = array();
		$columns['platform'] = $client == 1 ? array('ios') : array('android');
		$columns['audience'] = $audience;
		$columns['notification'] = $notification;
		$columns['message'] = $message;
		$columns['from'] = '';
		(new PlanMsgService)->addTask(array('type' => '4', 'content' => array($columns)));
		return true;
	}

	/**
	 * 发送消息给店员app
	 */
	public function sendMsgGroupOrder($nowOrder){
		$where = [
			['store_id' , '=', $nowOrder['store_id']],
			['is_notice' , '=', 0],
			['last_time' , '<>', 0],
		];
		$staffs = (new MerchantStoreStaffService())->getStaffListByCondition($where);

		$jpush = new Jpush();
		$href = cfg('config_site_url') . '/packapp/storestaff/index.html?gopage=group_list';
		$model = new TemplateNewsService();

		(new VoiceBoxService())->sendMsgToVoiceBox($nowOrder['store_id'], cfg('group_alias_name') . L_('新订单提醒，请及时查看！'));

		foreach ($staffs as $staff) {
			if($staff['client'] == 0 && $staff['openid']){
			    // 团购新订单-提醒店员
				$model->sendTempMsg('TM00017',
                    array('href' => $href,
                        'wecha_id' => $staff['openid'],
                        'first' => L_("X1您好！X2新订单提醒！",array("X1" => $staff['name'],"X2" => cfg('group_alias_name'))), 
                        'OrderSn' => $nowOrder['real_orderid'],
                        'OrderStatus' => L_('待接单'),
                        'remark' => L_('请您及时处理！')),
                    $nowOrder['mer_id']);
			}else if($staff['client'] > 0){
				if (! cfg('staff_jpush_appkey') && ($staff['client']!=1 || !cfg('pushkit_cert'))) continue;
				if ($staff['device_id']) {
					$client = $staff['client'];
					$device_id = str_replace('-', '', $staff['device_id']);
					$audience = array('tag' => array($device_id));
					
					$notification = $message = '';
					
					$title = L_('订单提醒');
					$msg = cfg('group_alias_name') . L_('新订单提醒，请及时查看！');
					
					//$voice_return = json_decode((new MerchantStoreStaffService())->voicBaidu(), true);

					//$voice_access_token = $voice_return['access_token'];
					//$voice_mp3 = 'http://tsn.baidu.com/text2audio?tex=' . $msg . '&lan=zh&tok=' . $voice_access_token . '&ctp=1&cuid=9B9A62EE-3EE8-45E0-9C06-2E3245AE3FF5';
                    $voice_mp3 = text2audio($msg);
				
					$url = cfg('site_url') . '/packapp/storestaff/index.html?gopage=group_list';
					$js_url = cfg('site_url') . '/packapp/storestaff/group_list.html';
					
					$url = str_replace('http://','https://',$url);
					$js_url = str_replace('http://','https://',$js_url);
					
					$voice_second = cfg('storestaff_app_voice_time');
					if(empty($voice_second)){
						$voice_second = 6;
					}
					
					$extra = array(
						'pigcms_tag' => 'group_order',
                        'tag_desc' => $nowOrder['real_orderid'],
						'voice_mp3' => $voice_mp3,
						'voice_second' => $voice_second, 
						'url' => $url,
						'js_url' => $js_url,
						'mp3_label' => 'new_group_order'.substr(md5(cfg('site_url')),0,16)
					);
					
					$notification = $jpush->createBody($client, $title, $msg, $extra, 'group_sound.caf');
					$message = $jpush->createMsg($title, $msg, $extra);
					
					$columns = array();
					$columns['platform'] = $client == 1 ? array('ios') : array('android');
					$columns['audience'] = $audience;
					$columns['notification'] = $notification;
					$columns['message'] = $message;
					$columns['from'] = 'storestaff';
                    $staff['is_app_native'] && $columns['business_type'] = 'new_order_group';
					(new PlanMsgService())->addTask(array('type' => '4', 'content' => array($columns)));
				}
			}
		}
	}
}