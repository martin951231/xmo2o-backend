<?php
/**
 * PC店员消息通知
 * time：2021/04/01
 * author 衡婷妹
 */

namespace app\common\model\service\send_message;

use app\common\model\db\WebPushMsg;
use app\common\model\service\config\AppapiAppConfigService;
use app\merchant\model\service\storestaff\MenuService;
use app\merchant\model\service\storestaff\MerchantStoreStaffService;

class WebPushMsgService
{

    public $webPushMsg;
    public $businessNameArr;

    public function __construct()
    {
        $this->webPushMsgModel = new WebPushMsg();
        $this->businessNameArr = [
            'shop' => cfg('shop_alias_name'),
            'mall' => cfg('mall_alias_name'),
            'mall_new' => cfg('mall_alias_name'),
            'foodshop' => cfg('meal_alias_name'),
            'dining' => cfg('meal_alias_name'),
            'group' => cfg('group_alias_name'),
            'appoint' => cfg('appoint_alias_name'),
            'store_arrival' => L_('店员收银'),
        ];
    }
/**
     * PC店员轮询获取消息
     * @param $deviceId
     * @param $staffUser 店员信息
     * @param $time 时间
     * @author: 衡婷妹
     * @date: 2020/12/10
     */
    public function getstaffOrderMessage($staffUser,$time=0)
    {
        // 返回数据
        $returnArr = [
            'list' => [], 
            'staff_id' => $staffUser['id'], 
            'now_time' => time(), // 当前时间
        ];

        if (empty($time)) {// 没有时间
            return $returnArr;
        }

        // 查询新数据
        $where = [
            ['store_id', '=', $staffUser['store_id']],
            ['add_time', '>', $time]
        ];
        $where[] = ['business_type', 'IN', ['mall','shop', 'group', 'appoint', 'foodshop', 'dining', 'store_arrival','cash','mall_new']];
        $message = $this->getSome($where,true,['id'=>'DESC']);

        if (empty($message)) {// 没有数据
            return $returnArr;
        }

        $list =  [ 
            'shop' => 
                [
                    'new_order_count' => 0,
                    'business_type' => 'shop',
                    'name' => '外卖',
                ],
            'mall' => 
                [
                    'new_order_count' => 0,
                    'business_type' => 'mall',
                    'name' => '商城',
                ],
            'mall_new' => 
                [
                    'new_order_count' => 0,
                    'business_type' => 'mall_new',
                    'name' => '商城',
                ],
            'group' => 
                [
                    'new_order_count' => 0,
                    'business_type' => 'group',
                    'name' => '团购',
                ],
            'appoint' => 
                [
                    'new_order_count' => 0,
                    'business_type' => 'appoint',
                    'name' => '预约',
                ],
            'foodshop' => 
                [
                    'new_order_count' => 0,
                    'business_type' => 'foodshop',
                    'name' => '餐饮',
                ],
            'dining' => 
                [
                    'new_order_count' => 0,
                    'business_type' => 'dining',
                    'name' => '新版餐饮',
                ],
            'store_arrival' =>
                [
                    'new_order_count' => 0,
                    'business_type' => 'store_arrival',
                    'name' => '店内收银',
                ],
            'cash' => 
                [
                    'new_order_count' => 0,
                    'business_type' => 'cash',
                    'name' => '快速买单',
                ],
            ];

        $countType = [];// 有数据的业务数组

        // 统计每种业务的数量
        foreach ($message as $v) {
            $list[$v['business_type']]['new_order_count']++;

            if(!in_array($v['business_type'],$countType)){
                $countType[]  = $v['business_type'];
            }
        }

        $returnArr['list'] = array_values($list);

        // 餐饮新订单提醒
        // 您有 x1 个新订单需要处理
        // 您有多个新订单提醒
        // 您有多个新订单需要处理

        if(count($countType) > 1){// 多种业务
            $title = L_('您有多个新订单提醒');
            $sTitle = L_('您有多个新订单需要处理');
            $returnArr['business_type'] = 'many';
            $returnArr['url'] = (new MenuService()) ->getPcMenuUrl();
        }else{//一个业务
            $businessName = $this->businessNameArr[$countType[0]];
            $returnArr['business_type'] = $countType[0];
            $returnArr['business_name'] = $businessName;
            $returnArr['count'] = count($message);
            $title = L_('X1新订单提醒',$businessName);
            $sTitle = L_('您有x1个新订单需要处理',count($message));
            $returnArr['url'] =  $returnArr['count'] == 1 && $message[0]['url'] ? $message[0]['url'] : (new MenuService()) ->getPcMenuUrl($countType[0]);
        }
        $voice_url = (new MerchantStoreStaffService())->getstaffNewOrderVoice($sTitle);

        // 语音提醒次数
        $returnArr['voice_time'] = (new AppapiAppConfigService())->get('storestaff_app_voice_time') ?: 6;

        $returnArr['title'] = $title;// 主标题
        $returnArr['s_title'] = $sTitle; // 副标题
        $returnArr['voice_url'] = $voice_url; // 语音
           
        return $returnArr;
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data)
    {
        $data['add_time'] = time();
        $result = $this->webPushMsgModel->insertGetId($data);
        if (!$result) {
            return false;
        }

        return $result;
    }

    

    /**
     * 获得多条数据
     * @param $where array 条件
     * @return array
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0)
    {
        $result = $this->webPushMsgModel->getSome($where,$field,$order,$page,$limit);
        if (!$result) {
            return [];
        }

        return $result->toArray();
    }
    
    /**
     * 获得总数
     * @param $where array 条件
     * @return array
     */
    public function getCount($where = [])
    {
        $result = $this->webPushMsgModel->getCount($where);
        if (!$result) {
            return 0;
        }

        return $result;
    }
}