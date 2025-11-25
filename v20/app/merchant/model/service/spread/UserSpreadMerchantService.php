<?php
/**
 * 用户推广的商家service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/09/18 11:11
 */

namespace app\merchant\model\service\spread;
use app\common\model\service\UserService as UserService;
use app\common\model\service\UserSpreadListService;
use app\common\model\service\UserSpreadService;
use app\common\model\service\weixin\TemplateNewsService;
use app\merchant\model\db\DistributorAgent as DistributorAgentModel;
use app\merchant\model\service\MerchantService;
class UserSpreadMerchantService {
    public $distributorAgentModel = null;
    public function __construct()
    {
        $this->distributorAgentModel = new DistributorAgentModel();
    }
    
    /*
     * 用户获得推广佣金
     * */
    public function userAddMoney($nowOrder,$money){
        //商家信息
        $nowMerchant = (new MerchantService())->getMerchantByMerId($nowOrder['mer_id']);
        
        if($nowMerchant['spread_code']!=''){
            $spread_code = $nowMerchant['spread_code'];
            $nowUser = $order_user = (new UserService())->getUser($spread_code,'spread_code');
        }else{
            return false;
        }
        
        $arr['first_percent'] = cfg('c2b_first_rate');
        $arr['second_percent'] = cfg('c2b_second_rate');
        $arr['third_percent'] = cfg('c2b_third_rate');

        $spreadUsers[] = $nowUser['uid'];
        $spreadWhere['uid'] = $nowUser['uid'];
        $nowUserSpread = (new UserSpreadService())->getOneRow($spreadWhere);
        if(empty($nowUserSpread)){
            return  false;
        }

        $href = cfg('site_url') . '/wap.php?g=Wap&c=My&a=index';
        $first_user = [];
        $second_user = [];
        $spread_uid = 0;
        foreach ($arr as $key=>$percent) {
            if($key=='first_percent'){
                $spreadUser = $first_user = $nowUser;
            }else{
                $spreadUser = (new UserService())->getUser($nowUserSpread['spread_uid']);
            }
            if($key=='second_percent'){
                $second_user = $spreadUser;
                $spread_uid = $first_user['uid'];
            }
            if($key=='third_percent'){
                $spread_uid = $second_user['uid'];
            }
            $spreadMoney  = round(($money) * $percent / 100, 2);


            $spreadData =array(
                'uid' => $spreadUser['uid'],
                'spread_uid' => $spread_uid,
                'get_uid' => $first_user['uid'],
                'money' => $spreadMoney,
                'order_type' => 'user_s_mer',
                'order_id' => $nowOrder['order_id'],
                'third_id' => 0,
                'status' => 1,
                'add_time' => $_SERVER['REQUEST_TIME'],
                'spread_mer_id' => $nowOrder['mer_id']
            );

            if($spreadUser['spread_change_uid']!=0){
                $spreadData['change_uid'] = 	$spreadUser['spread_change_uid'];
            }

            if($key=='first_percent') {
                $spread_relation_txt = L_('您推广的商家').$nowMerchant['name'];
            }else if($key=='second_percent'){
                $spread_relation_txt =L_("X1的子用户推广的商家X2",array("X1" => $spreadUser['nickname'],"X2" => $nowMerchant['name']));
            }else{
                $spread_relation_txt = L_("X1的子用户的子用户推广的商家X2",array("X1" => $spreadUser['nickname'],"X2" => $nowMerchant['name'])) ;
            }
            $spreadData['desc'] = L_("X1产生交易，您获得佣金。",array("X1" => $spread_relation_txt));
            // 处理长订单id
            if (isset($nowOrder['real_orderid'])) {
                $spreadData['real_orderid'] = $nowOrder['real_orderid'];
            }
            if (empty($spreadData['real_orderid']) && isset($nowOrder['orderid'])) {
                $spreadData['real_orderid'] = $nowOrder['orderid'];
            }
            
            // 添加推广佣金记录
            (new UserSpreadListService())->getOne($spreadData);
            if($spreadMoney>0) {
                (new UserService())->addMoney($spreadUser['uid'],$spreadMoney,L_("X1产生交易，您获得佣金。",array("X1" => $spread_relation_txt)));
                $msgData = [
                    'href' => $href,
                    'wecha_id' => $spreadUser['openid'],
                    'first' =>L_("X1产生交易，您获得佣金。",array("X1" => $spread_relation_txt)),
                    'keyword1' => $spreadMoney,
                    'keyword2' => date("Y年m月d日 H:i"),
                    'remark' => L_('点击查看详情！')
                ];
                (new TemplateNewsService())->sendTempMsg('OPENTM201812627', $msgData);
            }

            if($key=='third_percent'){
                break;
            }

            $nowUser = $spreadUser;
            $spreadWhere['uid'] = $spreadUser['uid'];
            $nowUserSpread = (new UserSpreadService())->getOneRow($spreadWhere);
        }
        return true;
    }
    //获取代理商/分销员的有效性
    public function getEffective($uid, $type=1){
        $where['uid'] = $uid;
        $where['type'] = $type;
        $distributorAgent = $this->getOne($where);
        if(!$distributorAgent){
            return false;
        }
        if( $distributorAgent['start_time']< time() && $distributorAgent['end_time']>time()){
            return true;
        }else{
            return false;
        }
    }

}