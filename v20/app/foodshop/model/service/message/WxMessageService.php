<?php
/**
 * 餐饮订单商品详情service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/30 17:46
 */

namespace app\foodshop\model\service\message;
use app\common\model\service\weixin\TemplateNewsService;
use app\common\model\service\weixin\WxappTemplateService;
use app\foodshop\model\service\order\DiningOrderService;
use app\common\model\service\UserService;
use app\merchant\model\service\MerchantStoreService;

class WxMessageService {

    public function __construct()
    {

    }

    /**
     * 发送小程序订阅消息
     * @param $param int
     * @return array
     */
    public function getNormalWxappTemplate($param){

        $type = $param['type'] ?? '';

        $returnArr = [];
        switch ($type){
            case 'fetch_number'://取餐号通知
                $where = [
                    'template_id' => ['1820']
                ];
                break;
            case 'queue_number'://排号通知
                $where = [
                    'template_id' => ['341','5699','5870']
                ];
                break;
        }
        $template = (new WxappTemplateService())->getNormalWxappTemplate($where);
        $returnArr['template_list'] = $template['list'];
        return $returnArr;
    }

    /**
     * 发送小程序订阅消息
     * @param $param array [
     *                          'type'=> '',//必传，发送类型，shop_pay_success-外卖支付成功
     *                          'wxapp_openid'=> '',//必传，用户公众号模板id
     *                          'page'=> '',//点击跳转页面，例子：pages/foodshop/order/orderDetail?order_id=1
     *                      ]
     * @return array
     */
    public function sendWxappMessage($param){
        $templateKey = $param['template_key'] ?? '';
        $type = $param['type'] ?? 0;

        $returnArr = [];
        // 获得模板id 并初步验证
        switch ($type) {
            case 'fetch_number'://取餐号通知
                $templateId = ['1820'];
                break;
            case 'queue_success'://取号成功通知
                $templateId = ['5870'];
                break;
            case 'queue_notice'://排队提醒通知
                $templateId = ['341'];
                break;
            case 'queue_complete'://到号提醒通知
                $templateId = ['5699'];
                break;
        }

        // 验证模板是否可用
        $where = [
            'template_id' => $templateId
        ];
        $template = (new WxappTemplateService())->getNormalWxappTemplate($where);
        if (empty($template['list'])) {
            $returnArr['msg'] = L_('模板不存在，请添加模板');
        }

        switch ($type){
            case 'fetch_number'://取餐号通知
                $orderId = $param['order_id'] ?? 0;
                $nowOrder = (new DiningOrderService())->getOrderByOrderId($orderId);
                if (empty($nowOrder)){
                    throw new \think\Exception(L_("订单不存在"), 1003);
                }

                if(empty($nowOrder['fetch_number']) || empty($nowOrder['self_take_time'])){
                    $returnArr['msg'] = L_('非自取订单不能发送');
                }

                // 小程序openid
                $wxappOpenid = '';

                // 微信公众号openid
                $openid = '';

                // 获得用户的openID
                if($nowOrder['uid']){
                    $nowUser = (new UserService())->getUser($nowOrder['uid']);
                    $wxappOpenid = $nowUser['wxapp_openid'];
                    $openid = $nowUser['openid'];
                }elseif ($nowOrder['user_type'] == 'wxapp_openid'){
                    $wxappOpenid = $nowOrder['user_id'];

                }elseif ($nowOrder['user_type'] == 'openid'){
                    $openid = $nowOrder['user_id'];
                }

                $nowStore = (new MerchantStoreService())->getStoreByStoreId($nowOrder['store_id']);

                $msgData = [
                    'page' => 'pages/foodshop/order/orderDetail?order_id='.$nowOrder['order_id'],
                    'wecha_id' => $wxappOpenid,
                    'thing2' => $nowStore['name'],//门店名称
                    'character_string4' => $nowOrder['fetch_number'],//取餐号码
                    'time6' => date("Y年m月d日 H:i", $nowOrder['self_take_time']) .'~'. date("H:i", $nowOrder['self_take_time']+1200),//取餐时间
                    'thing11' => L_('为避免影响您的用餐口感，请准时取餐'),//温馨提示

                ];

                /*您已下单成功，取餐时间为：立即取餐，取餐号为：10002；为避免影响您的用餐口感，请准时取餐。
                    项目名称：取餐号提醒
                    最新状态：下单成功
                    时间：2015-56  12:20
                    点击查看您在【某某店铺】的餐饮订单*/
                $msgDataWx = [
                    'href' => cfg('site_url').'pages/foodshop/order/orderDetail?order_id='.$nowOrder['order_id'],
                    'wecha_id' => $openid,
                    'first' => '您已下单成功，取餐时间为：'.date("Y-m-d H:i", $nowOrder['self_take_time']) .'~'. date("H:i", $nowOrder['self_take_time']+1200).'，取餐号为：'.$nowOrder['fetch_number'].'；为避免影响您的用餐口感，请准时取餐。',//门店名称
                    'keyword1' => L_('取餐号提醒'),
                    'keyword2' => L_('下单成功'),
                    'keyword3' => date("Y-m-d H:i"),
                    'remark' => L_('点击查看您在【X1】的餐饮订单',$nowStore['name']),

                ];
                break;
            case 'queue_success'://取号成功通知
                // 小程序openid
                $wxappOpenid = '';

                // 微信公众号openid
                $openid = '';

                // 获得用户的openID
                $nowUser = (new UserService())->getUser($param['uid']);
                if ($nowUser) {
                    $wxappOpenid = $nowUser['wxapp_openid'];
                    $openid = $nowUser['openid'];
                }

                $nowStore = (new MerchantStoreService())->getStoreByStoreId($param['store_id']);

                $msgData = [
                    'page' => $param['page'],
                    'wecha_id' => $wxappOpenid,
                    'character_string1' => $param['number'],//排队号码
                    'thing3' => $nowStore['name'],//门店名称
                    'thing2' => L_('前面还有【X1】人在等待', $param['count']),
                    'date5' => date('Y年m月d日 H:i', $param['create_time']),//取号时间
                ];

                /*您已取号成功，排号码为：A2；请您准时到店就餐。
                项目名称：排号成功提醒
                最新状态：取号成功
                时间：2015-56  12:20
                点击查看您在【某某店铺】的排号详情*/
                $msgDataWx = [
                    'href' => $param['page'],
                    'wecha_id' => $openid,
                    'first' => '您已取号成功，排号码为：' . $param['number'] . '；请您准时到店就餐。',
                    'keyword1' => L_('排号成功提醒'),
                    'keyword2' => L_('取号成功'),
                    'keyword3' => date("Y-m-d H:i"),
                    'remark' => L_('点击查看您在【X1】的排号详情', $nowStore['name']),
                ];
                break;
            case 'queue_notice'://排队提醒通知
                // 小程序openid
                $wxappOpenid = '';

                // 微信公众号openid
                $openid = '';

                // 获得用户的openID
                $nowUser = (new UserService())->getUser($param['uid']);
                if ($nowUser) {
                    $wxappOpenid = $nowUser['wxapp_openid'];
                    $openid = $nowUser['openid'];
                }

                $nowStore = (new MerchantStoreService())->getStoreByStoreId($param['store_id']);

                $msgData = [
                    'page' => $param['page'],
                    'wecha_id' => $wxappOpenid,
                    'thing2' => $param['number'],//排队号码
                    'thing1' => $nowStore['name'],//商家名称
                    'time8' => date('Y年m月d日 H:i:', $param['create_time']),//取号时间
                    'thing5' => $param['remark'],//备注
                ];

                /*您的排号码为：A2；您当前还有2桌，请您做好就餐准备。
                项目名称：排队提醒通知
                最新状态：当前需等待2桌
                时间：2015-56  12:20
                点击查看您在【某某店铺】的排号详情*/
                $msgDataWx = [
                    'href' => $param['page'],
                    'wecha_id' => $openid,
                    'first' => '您的排号码为：' . $param['number'] . '；您当前还有2桌，请您做好就餐准备。',
                    'keyword1' => L_('排队提醒通知'),
                    'keyword2' => L_('当前需等待2桌'),
                    'keyword3' => date("Y-m-d H:i"),
                    'remark' => L_('点击查看您在【X1】的排号详情', $nowStore['name']),
                ];
                break;
            case 'queue_complete'://到号提醒通知
                // 小程序openid
                $wxappOpenid = '';

                // 微信公众号openid
                $openid = '';

                // 获得用户的openID
                $nowUser = (new UserService())->getUser($param['uid']);
                if ($nowUser) {
                    $wxappOpenid = $nowUser['wxapp_openid'];
                    $openid = $nowUser['openid'];
                }

                $nowStore = (new MerchantStoreService())->getStoreByStoreId($param['store_id']);

                $msgData = [
                    'page' => $param['page'],
                    'wecha_id' => $wxappOpenid,
                    'character_string2' => $param['number'],//排队号码
                    'thing4' => $nowStore['name'],//商家名称
                    'thing3' => $param['remark'],//备注
                ];

                /*您的排号码为：A2；您已到号，请您到店就餐。
                项目名称：到号提醒通知
                最新状态：已到号
                时间：2015-56  12:20
                点击查看您在【某某店铺】的排号详情*/
                $msgDataWx = [
                    'href' => $param['page'],
                    'wecha_id' => $openid,
                    'first' => '您的排号码为：' . $param['number'] . '；您已到号，请您到店就餐。',
                    'keyword1' => L_('到号提醒通知'),
                    'keyword2' => L_('已到号'),
                    'keyword3' => date("Y-m-d H:i"),
                    'remark' => L_('点击查看您在【X1】的排号详情', $nowStore['name']),
                ];
                break;
        }
        // 发送订阅消息
        $res = false;
        if ($wxappOpenid) {
            $res = (new WxappTemplateService())->sendTempMsg($template['list'][0]['template_key'], $msgData);
        }

        // 订阅消息发送失败后， 尝试发送公众号模板消息
        if (!$res && $openid) {
            $res = (new TemplateNewsService())->sendTempMsg('OPENTM400166399', $msgDataWx);
        }
        return $returnArr;
    }

}