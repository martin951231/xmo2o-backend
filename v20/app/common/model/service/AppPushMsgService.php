<?php
//
namespace app\common\model\service;

use app\common\model\db\AppPushMsg;
use app\common\model\service\config\AppapiAppConfigService;
use app\merchant\model\service\storestaff\MerchantStoreStaffService;
use think\Exception;
use think\facade\Db;
use token\Token;

/**
 * app推送索引服务类
 * @author: 张涛
 * @date: 2020/9/16
 */
class AppPushMsgService
{
    public $appPushMsgMod;

    public function __construct()
    {
        $this->appPushMsgMod = new AppPushMsg();
    }
    

    /**
     * app轮询获取消息
     * @param $deviceId
     * @param $appType
     * @author: 张涛
     * @date: 2020/9/16
     */
    public function getAppPushMessage($deviceId, $appType, $businessType = '')
    {
        $where = [
            ['device_id', '=', $deviceId],
            ['platform', '=', $appType],
            ['status', '=', 0]
        ];
        if ($businessType) {
            if ($businessType == 'new_order') {
                $where[] = ['business_type', 'IN', ['new_order', 'cancel_order']];
            } else {
                $where[] = ['business_type', '=', $businessType];
            }
        }
        $message = $this->appPushMsgMod
            ->where($where)
            ->field('*')
            ->order('id', 'DESC')
            ->select()
            ->toArray();
        if (empty($message)) {
            return [];
        }
        $rs = ['is_new_order' => false, 'extras' => []];
        $isCancelOrder = false;
        foreach ($message as $v) {
            $isNewOrder = (isset($v['business_type']) && $v['business_type'] == 'new_order') ? true : false;
            if (!$rs['is_new_order']) {
                $rs['is_new_order'] = $isNewOrder;
            }

            if (!$isCancelOrder) {
                $isCancelOrder = (isset($v['business_type']) && $v['business_type'] == 'cancel_order') ? true : false;
                $data = unserialize($v['data']);
                $content = unserialize($data['content']);
                if (isset($content[0]['message']['extras']) && !empty($content[0]['message']['extras'])) {
                    if (empty($rs['extras'])) {
                        $rs['extras'] = $content[0]['message']['extras'];
                    } else {
                        if ($rs['is_new_order'] && !$isNewOrder) {
                            $rs['extras'] = $content[0]['message']['extras'];
                        }
                    }
                }
            }
        }

        //判断推送的mp3链接是否能访问，防止app闪崩
        if (isset($rs['extras']['voice_mp3']) && !check_remote_file_exists($rs['extras']['voice_mp3'])) {
            $rs = ['is_new_order' => false, 'extras' => []];
        }

        //全部改成已发送
        $this->appPushMsgMod->where($where)->update(['status' => 1, 'send_type' => 'http', 'send_time' => time()]);
        return $rs;
    }

    /**
     * app轮询获取消息多条
     * @param $deviceId
     * @param $appType
     * @author: 汪晨
     * @date: 2020/2/4
     */
    public function getAppPushRewardMessage($deviceId, $appType, $businessType = '')
    {
        $where = [
            'device_id'      => $deviceId,
            'platform'      => $appType,
            'business_type' => $businessType,
            'status'        => 0
        ];

        $message = $this->appPushMsgMod
            ->where($where)
            ->field('*')
            ->order('id', 'DESC')
            ->select()
            ->toArray();
        if (empty($message)) {
            return [];
        }

        $rs = ['is_new_order' => false, 'extras' => []];

        foreach ($message as $k=>$v) {
            $data = unserialize($v['data']);
            $content = unserialize($data['content']);
            $content[0]['message']['extras']['msg_content'] = $content[0]['message']['msg_content'];
            $content[0]['message']['extras']['content_type'] = $content[0]['message']['content_type'];
            $content[0]['message']['extras']['title'] = $content[0]['message']['title'];
            $content[0]['message']['extras']['from'] = $content[0]['from'];

            if (isset($content[0]['message']['extras']) && !empty($content[0]['message']['extras'])) {
                if (empty($rs['extras'][$k])) {
                    $rs['extras'][$k] = $content[0]['message']['extras'];
                }
            }
        }

        //判断推送的mp3链接是否能访问，防止app闪崩
        if (isset($rs['extras']['voice_mp3']) && !check_remote_file_exists($rs['extras']['voice_mp3'])) {
            $rs = ['is_new_order' => false, 'extras' => []];
        }

        //全部改成已发送
        $this->appPushMsgMod->where($where)->update(['status' => 1, 'send_type' => 'http', 'send_time' => time()]);
        return $rs;
    }

    /**
     * 设置推送消息为已发送
     * @author: 张涛
     * @date: 2020/12/14
     */
    public function setIsSendByDeviceId($deviceId)
    {
        $this->appPushMsgMod->where(['device_id' => $deviceId, 'status' => 0])->update(['status' => 1, 'send_type' => 'http', 'send_time' => time()]);
        return true;
    }

    /**
     * 店员app轮询获取消息
     * @param $deviceId
     * @param $appType
     * @author: 衡婷妹
     * @date: 2020/12/10
     */
    public function getstaffNewOrderMessage($deviceId, $appType, $staffUser)
    {
        // 返回数据
        $returnArr = [
            'list' => []
        ];

        $rmDeviceId = str_replace('-', '', $deviceId);
        // 查询未读数据
        $where = [
            ['device_id', 'IN', [$deviceId,$rmDeviceId]],
            ['platform', '=', $appType],
            ['status', '=', 0]
        ];

        $where[] = ['business_type', 'IN', ['new_order_mall','new_order_shop', 'new_order_group', 'new_order_appoint', 'new_order_foodshop', 'new_order_dining', 'new_order_store_arrival','new_order_cash']];

        $message = $this->appPushMsgMod
            ->where($where)
            ->field('*')
            ->order('id', 'DESC')
            ->select()
            ->toArray();

        if (empty($message)) {
            return $returnArr;
        }

        $rs = ['is_new_order' => false, 'extras' => []];

        $list = [
            'shop' => [
                'new_order_count' => 0,
                'business_type' => 'shop',
                'name' => '外卖',
            ],
            'mall' => [
                'new_order_count' => 0,
                'business_type' => 'mall',
                'name' => '商城',
            ],
            'group' => [
                'new_order_count' => 0,
                'business_type' => 'group',
                'name' => '团购',
            ],
            'appoint' => [
                'new_order_count' => 0,
                'business_type' => 'appoint',
                'name' => '预约',
            ],
            'foodshop' => [
                'new_order_count' => 0,
                'business_type' => 'foodshop',
                'name' => '餐饮',
            ],
            'dining' => [
                'new_order_count' => 0,
                'business_type' => 'dining',
                'name' => '新版餐饮',
            ],
            'store_arrival' => [
                'new_order_count' => 0,
                'business_type' => 'store_arrival',
                'name' => '店内收银',
            ],
            'cash' => [
                'new_order_count' => 0,
                'business_type' => 'cash',
                'name' => '快速买单',
            ],
        ];
        foreach ($message as $v) {
            switch ($v['business_type']){
                case 'new_order_shop':
                    $list['shop']['new_order_count']++;
                    break;
                case 'new_order_mall':
                    $list['mall']['new_order_count']++;
                    break;
                case 'new_order_group':
                    $list['group']['new_order_count']++;
                    break;
                case 'new_order_appoint':
                    $list['appoint']['new_order_count']++;
                    break;
                case 'new_order_foodshop':
                    $list['foodshop']['new_order_count']++;
                    break;
                case 'new_order_dining':
                    $list['dining']['new_order_count']++;
                    break;
                case 'new_order_store_arrival':
                    $list['store_arrival']['new_order_count']++;
                    break;
                case 'new_order_cash':
                    $list['cash']['new_order_count']++;
                    break;
            }
        }
        $returnArr['list'] = array_values($list);

        // 获得语音数据  语音提醒优先级：外卖提醒>商城提醒>快速买单提醒>餐饮提醒>团购提醒>预约提醒
        // 业务别称+新订单提醒与业务别称+新订单提醒，请及时查看
        $businessType = '';
        $businessName = '';
        $urlType = '';
        $MP3 = '';
        if($list['shop']['new_order_count']){
            $businessType = 'shop';
            $businessName = cfg('shop_alias_name');
            $urlType = 'url';
        }elseif ($list['mall']['new_order_count']){
            $businessType = 'mall';
            $businessName = cfg('mall_alias_name');
            $urlType = 'url';
        }elseif ($list['store_arrival']['new_order_count']){
            $businessType = 'store_arrival';
            $businessName = '店员收银';
            $urlType = 'url';
        }elseif ($list['cash']['new_order_count']){
            $businessType = 'cash';
            $businessName = cfg('cash_alias_name');
            $urlType = 'url';
        }elseif ($list['foodshop']['new_order_count']){
            $businessType = 'foodshop';
            $businessName = cfg('meal_alias_name');
            $urlType = 'url';
        }elseif ($list['dining']['new_order_count']){
            $businessType = 'dining';
            $businessName = cfg('meal_alias_name');
            $urlType = 'miniapp';
        }elseif ($list['group']['new_order_count']){
            $businessType = 'group';
            $businessName = cfg('group_alias_name');
            $urlType = 'url';
        }elseif ($list['appoint']['new_order_count']){
            $businessType = 'appoint';
            $businessName = cfg('appoint_alias_name');
            $urlType = 'url';
        }
        if($businessType){
            $title = L_('X1新订单提醒',L_($businessName));
            $sTitle = L_('X1新订单提醒，请及时查看',L_($businessName));
            $returnArr['business_type'] = $businessType;
            $returnArr['url_type'] = $urlType;
            $returnArr['voice_time'] = (new AppapiAppConfigService())->get('storestaff_app_voice_time') ?: 6;

            $url = [];
            foreach ($message as $v) {
                if('new_order_'.$businessType == $v['business_type']){
                    $data = unserialize($v['data']);
                    $content = unserialize($data['content']);
                    $url = $content[0]['notification'][$appType]['extras']['js_url'] ?? '';
                    $mp3Label = $content[0]['notification'][$appType]['extras']['mp3_label'] ?? '';
                    $voiceUrl= $content[0]['notification'][$appType]['extras']['voice_mp3'] ?? '';
                    $voiceTime= $content[0]['notification'][$appType]['extras']['voice_second'] ?? '';
                    $title= $content[0]['message']['title'] ?? $title;
                    $sTitle= $content[0]['message']['msg_content'] ?? $sTitle;
                    if($v['business_type'] == 'new_order_mall' && !$voiceUrl){
                        $voiceUrl = (new MerchantStoreStaffService())->getstaffNewOrderVoice(L_('您有x1个新订单需要处理',1));
                    }
                    break;
                }
            }
            $returnArr['title'] = $title;
            $returnArr['s_title'] = $sTitle;
            $returnArr['url'] = $url;
            $returnArr['mp3_label'] = $mp3Label;
            $returnArr['voice_url'] = $voiceUrl;
            $returnArr['voice_time'] = $voiceTime;
                fdump([$url,$returnArr],'miniapp',1);
            if(stripos($url, 'miniapp') !== false){
//            miniapp://__UNI__8799035&/pages/foodshop/index/index
                $returnArr['url_type'] = 'miniapp';
                $strArr = explode('//',$url)[1];
                $strArr = explode('&',$strArr);

                // 获得店员app ticket
                $ticket = Token::createToken($staffUser['id']);
                $params = [
                    'device_id' => $deviceId,
                    'ticket' => $ticket,
                    'domain' => cfg('site_url'),
                ];
                $returnArr['ticket'] = $ticket;
                $returnArr['app_data'] = [
                    'appid' => $strArr[0],
                    'path' => $strArr[1],
                    'arguments' => json_encode($params),
                ];
            }

        }

        fdump($returnArr,'miniapp',1);
        //全部改成已发送
        $this->appPushMsgMod->where($where)->update(['status' => 1, 'send_type' => 'http', 'send_time' => time()]);
        return $returnArr;
    }

    /**
     *插入数据
     * @param $data array
     * @return array
     */
    public function add($data){
        if(empty($data)){
            return false;
        }

        $id = $this->appPushMsgMod->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     * 批量插入数据
     * @param $data array
     * @return array
     */
    public function addAll($data){
        if(empty($data)){
            return false;
        }

        $id = $this->appPushMsgMod->addAll($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

}