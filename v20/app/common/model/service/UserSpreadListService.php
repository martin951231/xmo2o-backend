<?php
/**
 * 用户推广关系佣金记录
 * User: 衡婷妹
 * Date: 2020/09/18 11:30
 */

namespace app\common\model\service;

use app\common\model\db\UserSpreadList;
use app\common\model\service\percent_rate\PercentRateService;
use app\common\model\service\weixin\TemplateNewsService;
use app\common\model\db\UserSpread;
use app\foodshop\model\service\order\DiningOrderService;
use app\group\model\service\order\GroupOrderService;
use app\merchant\model\service\MerchantMoneyListService;
use app\mall\model\service\MallOrderService;
use think\facade\Db;

class UserSpreadListService
{
    public $userSpreadListObj = null;
    public function __construct()
    {
        $this->userSpreadListObj = new UserSpreadList();
    }

    /**
     * 用户分佣佣金结算
     * User: chenxiang
     * Date: 2020/5/29 14:30
     * @param $uid int 用户id
     * @param $id int 分佣id
     */
    public function spreadCheck($uid,$id){
        if(!$uid || !$id){
            return false;
        }

        if(cfg('open_extra_price')==1){
            $money_name = cfg('extra_price_alias_name');
        }else{
            $money_name = L_('佣金');
        }

        // 获取分佣记录
        $where = [];
        $where[] = ['pigcms_id', '=', $id];
        $where[] = ['uid', 'exp', Db::raw('= ' . $uid . ' OR change_uid = '.$uid)];
        $nowSpread = $this->getOne($where);
        if(empty($nowSpread)){
            return false;
        }

        //已结算过
        if($nowSpread['status'] == 1){
            return false;
        }

        
        // 更新分佣状态为已结算
        if(!$this->updateThis(['pigcms_id'=>$id], ['status'=>1])){
            return false;
        }
        switch ($nowSpread['order_type']){
            case 'dining' ://餐饮2.0
                // 订单详情
                $order = (new DiningOrderService())->getOrderByOrderId($nowSpread['order_id']);
                break;
            case 'mall'://商城
                // 订单详情
                $order = (new MallOrderService())->getOne($nowSpread['order_id']);
                break;      
        }
        if($nowSpread['change_uid']!=0){
            (new UserService())->addMoney($nowSpread['change_uid'],$nowSpread['money'],L_("推广用户购买商品获得X1（X2过户）",array("X1" => $money_name,"X2" => $money_name),$ask = 0, $ask_id = 0, $type_id = 0, $mer_id=$nowSpread['spread_mer_id']));

            // 是否开启推广佣由金商家支付
            if (cfg('open_spread_by_merchant')==1) {
                (new MerchantMoneyListService())->useMoney($order['mer_id'], $nowSpread['money'], $nowSpread['order_type'], L_("推广用户购买商品获得X1（X2过户）扣除商家余额",array("X1" => $money_name,"X2" => $money_name)), $order['order_id']);
            }
        }else{
            if(cfg('open_extra_price')==1){
                (new UserService())->addScore($nowSpread['uid'],$nowSpread['money'],L_("推广用户购买商品获得X1",array("X1" => $money_name)));
            }else{
                (new UserService())->addMoney($nowSpread['uid'],$nowSpread['money'],L_("推广用户购买商品获得X1",array("X1" => $money_name),$ask = 0, $ask_id = 0, $type_id = 0, $mer_id=$nowSpread['spread_mer_id']));

                // 是否开启推广佣由金商家支付
                if (cfg('open_spread_by_merchant')==1) {
                    (new MerchantMoneyListService())->useMoney($order['mer_id'], $nowSpread['money'], $nowSpread['order_type'], L_("推广用户购买商品获得X1扣除商家余额",array("X1" => $money_name)), $order['order_id']);
                }
                (new ScrollMsgService())->addMsg('spread',$nowSpread['uid'],L_('用户x1于x2获得推广用户的消费佣金',array('x1'=>str_replace_name($nowSpread['nickname']),'x2'=>date('Y-m-d H:i',time()))));
            }
        }
        return true;
    }
    
    /**
     * 用户三级分佣
     * User: 衡婷妹
     * Date: 2021/5/25 15:00
     * @param array $order 订单信息
     * @param string $orderType 订单类型 group-团购
     */
    public function setSpreadList($order,$orderType){

        $nowUser = (new UserService())->getUser($order['uid']);

        if(cfg('open_score_pay_back') && cfg('system_take_spread_percent') > 0 ){
            $spreadTotalMoney = ($order['balance_pay']+$order['payment_money']+$order['score_deducte']+$order['coupon_price']+$order['merchant_balance']);
            $percent = (new PercentRateService())->getPercentRate($order['mer_id'],$orderType,$spreadTotalMoney,$order['group_id'],$order['store_id'], true);

            $spreadTotalMoney = $spreadTotalMoney * $percent / 100;
        }else{
            $spreadTotalMoney = $order['balance_pay'] + $order['payment_money'];
        }

        if(cfg('add_spread_by_system_commission')){// 根据平台抽成获得佣金	
            switch($orderType){
                case 'group':
                    // 获取平台抽成金额
                    $update_order = $order;							
                    $update_order['order_type'] = 'group';
                    $update_order['verify_all'] = 1;
                    $spreadTotalMoney = (new GroupOrderService())->billOrder($update_order, true);
                break;
            }
        }

        $model = new TemplateNewsService();
        if ($order['share_openid']) {
            //上级分享佣金
            $spread_rate = (new PercentRateService())->getUserSpreadRate($order['mer_id'],'group',$order['group_id']);
            
            //上级分享获得积分
            $score_spread_rate =(new PercentRateService())->getUserScoreSpreadRate('group',$order['group_id']);

            // 推广者
            $spread_user = (new UserService())->getUser($order['share_openid'], 'openid');

            // 一级分佣比例
            $userSpreadRate = $spread_rate['first_rate'];
            $userScoreSpreadRate = $score_spread_rate['first_rate'];

            $open_extra_price = cfg('open_extra_price');
            $href = cfg('site_url') . '/wap.php?g=Wap&c=My&a=spread_list&status=-1';

            if($spread_user && ($userSpreadRate || $userScoreSpreadRate) &&$spread_user['uid']!=$nowUser['uid']){
                // 存在上级分佣佣金或者积分  且不是自己的分享
                if ($userSpreadRate ) {
                    $spread_money = round($spreadTotalMoney * $userSpreadRate / 100, 2);

                    $spread_data = array('uid'=>$spread_user['uid'],'spread_uid'=>0,'get_uid'=>$nowUser['uid'],'money'=>$spread_money,'order_type'=>'group','order_id'=>$order['order_id'],'third_id'=>$order['group_id'],'add_time'=>time());
                    // 处理长订单id
                    if ($order['real_orderid']) {
                        $spread_data['real_orderid'] = $order['real_orderid'];
                    }
                    (new UserSpreadListService())->add($spread_data);

                    $buy_user = $nowUser;
                    if($spread_money>0){
                        if($open_extra_price){
                            $money_name = cfg('extra_price_alias_name');
                        }else{
                            $money_name = L_('佣金');
                        }
                        $model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $spread_user['openid'], 'first' => L_("X1通过您的分享购买商品，验证消费后您将获得X2。",array("X1" => $buy_user['nickname'],"X2" => $money_name)) , 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $order['mer_id']);
                    }
                }
                if ($userScoreSpreadRate) {
                    // 一级分享积分获得
                    $spread_score = round($spreadTotalMoney * $userScoreSpreadRate / 100, 2);
                    $spread_data = array('uid'=>$spread_user['uid'],'spread_uid'=>0,'get_uid'=>$nowUser['uid'],'money'=>$spread_score,'order_type'=>'group','spread_type'=>'1','order_id'=>$order[' '],'third_id'=>$order['group_id'],'add_time'=>time());
                    // 处理长订单id
                    if ($order['real_orderid']) {
                        $spread_data['real_orderid'] = $order['real_orderid'];
                    }
                    D('User_spread_list')->data($spread_data)->add();
                }

            }
        } elseif(cfg('open_user_spread') && (!cfg('open_spread_appoint_user') || $nowUser['can_spread']==1) && (cfg('spread_money_limit')==0 || cfg('spread_money_limit')<=$spreadTotalMoney) && empty(cfg('open_single_spread'))&&empty(cfg('open_spread_rand'))){
            //上级分享佣金
            $spread_rate = (new PercentRateService())->getUserSpreadRate($order['mer_id'],'group',$order['group_id']);
        
            //上级分享获得积分
            $orderType == 'group' && $score_spread_rate =(new PercentRateService())->getUserScoreSpreadRate('group',$order['group_id']);
        

           $spreadUsers[] = $nowUser['uid'];
            $nowUserSpread = (new UserSpreadService())->getOneRow(['uid' => $nowUser['uid'],'is_del'=>0,'is_apply'=>0]);

            $href = cfg('site_url') . '/wap.php?g=Wap&c=My&a=spread_list&status=-1';
            $open_extra_price = cfg('open_extra_price');
            if($order['is_own']  && cfg('own_pay_spread')==0){
                $order['payment_money']=0;
            }
            if(!empty($nowUserSpread)){
               $spreadUser = (new UserService())->getUser($nowUserSpread['spread_uid']);
                $userSpreadRate = $spread_rate['first_rate'];
                $userScoreSpreadRate = $score_spread_rate['first_rate'] ?? 0;
                if($spreadUser && ($userSpreadRate || $userScoreSpreadRate) &&!in_array($spreadUser['uid'],$spreadUsers)){
                    if ($userSpreadRate ) {
                        $spread_money = round($spreadTotalMoney * $userSpreadRate / 100, 2);

                        $spreadData = array(
                            'uid'=>$spreadUser['uid'],
                            'spread_uid'=>0,
                            'get_uid'=>$nowUser['uid'],
                            'money'=>$spread_money,
                            'order_type'=>$orderType,
                            'order_id'=>$order['order_id'],
                            'third_id'=>$order['group_id'] ?? 0,
                            'add_time'=>time()
                        );
                        if($spreadUser['spread_change_uid']!=0){
                            $spreadData['change_uid'] =     $spreadUser['spread_change_uid'];
                        }
                        // 处理长订单id
                        if ($order['real_orderid']) {
                            $spreadData['real_orderid'] = $order['real_orderid'];
                        }

                        // 添加一级分佣记录
                        $this->add($spreadData);

                        $buy_user = (new UserService())->getUser($nowUserSpread['uid']);
                        if($spread_money>0){
                            if($open_extra_price){
                                $money_name = cfg('extra_price_alias_name');
                            }else{
                                $money_name = L_('佣金');
                            }
                            $model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' =>$spreadUser['openid'], 'first' => L_("X1通过您的分享购买商品，验证消费后您将获得X2。",array("X1" => $buy_user['nickname'],"X2" => $money_name)) , 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $order['mer_id']);
                        }
                    }

                    if ($userScoreSpreadRate) {
                        // 一级分享积分获得
                        $spread_score = round($spreadTotalMoney * $userScoreSpreadRate / 100, 2);
                        $spreadData = array(
                            'uid'=>$spreadUser['uid'],
                            'spread_uid'=>0,
                            'get_uid'=>$nowUser['uid'],
                            'money'=>$spread_score,
                            'order_type'=>$orderType,
                            'orderType'=>'1',
                            'order_id'=>$order['order_id'],
                            'third_id'=>$order['group_id'] ?? 0,
                            'add_time'=>time());
                        if($spreadUser['spread_change_uid']!=0){
                            $spreadData['change_uid'] = $spreadUser['spread_change_uid'];
                        }
                        // 处理长订单id
                        if ($order['real_orderid']) {
                            $spreadData['real_orderid'] = $order['real_orderid'];
                        }
                        // 添加一级分佣记录
                        $this->add($spreadData);
                    }

                   $spreadUsers[]=$spreadUser['uid'];
                }

                //第二级分享佣金
                $second_user_spread = (new UserSpreadService())->getOneRow(['uid' => $spreadUser['uid']]);
                if(!empty($second_user_spread)&&!$open_extra_price) {
                    
                    $second_user = (new UserService())->getUser($second_user_spread['spread_uid']);
                    
                    $sub_user_spread_rate = $spread_rate['second_rate'];
                    $sub_user_score_spread_rate = $score_spread_rate['second_rate'] ?? 0;
                    if ($second_user && ($sub_user_spread_rate || $sub_user_score_spread_rate)&&!in_array($second_user['uid'],$spreadUsers)) {
                        if ($sub_user_spread_rate) {
                            $spread_money = round($spreadTotalMoney * $sub_user_spread_rate / 100, 2);

                            $spreadData = array(
                                'uid' => $second_user['uid'], 
                                'spread_uid' =>$spreadUser['uid'], 
                                'get_uid' => $nowUser['uid'], 
                                'money' => $spread_money, 
                                'order_type' => $orderType, 
                                'order_id' => $order['order_id'], 
                                'third_id' => $order['group_id'], 
                                'add_time' => time()
                            );
                            if($spreadUser['spread_change_uid']!=0){
                                $spreadData['change_uid'] =     $second_user['spread_change_uid'];
                            }
                            // 处理长订单id
                            if ($order['real_orderid']) {
                                $spreadData['real_orderid'] = $order['real_orderid'];
                            }
                            $this->add($spreadData);
                            
                            $sec_user = (new UserService())->getUser($second_user_spread['uid']);
                            
                            if($spread_money>0) {
                                $model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $second_user['openid'], 'first' => L_("X1的子用户X2通过您的分享购买商品，验证消费后您将获得佣金。",array("X1" => $sec_user['nickname'],"X2" => $buy_user['nickname'])) , 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $order['mer_id']);
                            }
                        }

                        // 二级分享积分
                        if ($sub_user_score_spread_rate) {
                            $spread_score = round($spreadTotalMoney * $sub_user_score_spread_rate / 100, 2);

                            $spreadData = array(
                                'uid' => $second_user['uid'], 
                                'spread_uid' =>$spreadUser['uid'], 
                                'get_uid' => $nowUser['uid'], 
                                'money' => $spread_score, 
                                'order_type' => $orderType,
                                'orderType' => '1', 
                                'order_id' => $order['order_id'], 
                                'third_id' => $order['group_id'], 
                                'add_time' => time()
                            );
                            if($spreadUser['spread_change_uid']!=0){
                                $spreadData['change_uid'] =     $second_user['spread_change_uid'];
                            }
                            // 处理长订单id
                            if ($order['real_orderid']) {
                                $spreadData['real_orderid'] = $order['real_orderid'];
                            }
                            $this->add($spreadData);
                        }

                       $spreadUsers[] = $second_user['uid'];
                    }

                    
                    //顶级分享佣金
                    $first_user_spread = (new UserSpreadService())->getOneRow(['uid' => $second_user['uid']]);

                    if (!empty($first_user_spread) && cfg('user_third_level_spread')&&!$open_extra_price) {
                        
                        $first_spread_user = (new UserService())->getUser($first_user_spread['spread_uid']);
                        
                        $sub_user_spread_rate = $spread_rate['third_rate'];
                        if ($first_spread_user && $sub_user_spread_rate&&!in_array($first_spread_user['uid'],$spreadUsers)) {
                            $spread_money = round($spreadTotalMoney* $sub_user_spread_rate / 100, 2);

                            $spreadData = array(
                                'uid' => $first_spread_user['uid'], 
                                'spread_uid' => $second_user['uid'] ?? 0, 
                                'get_uid' => $nowUser['uid'], 
                                'money' => $spread_money, 
                                'order_type' => $orderType, 
                                'order_id' => $order['order_id'], 
                                'third_id' => $order['group_id'] ?? 0, 
                                'add_time' => time()
                            );
                            if($spreadUser['spread_change_uid']!=0){
                                $spreadData['change_uid'] = $first_spread_user['spread_change_uid'];
                            }
                            // 处理长订单id
                            if ($order['real_orderid']) {
                                $spreadData['real_orderid'] = $order['real_orderid'];
                            }

                            $this->add($spreadData);

                            $fir_user = (new UserService())->getUser($first_user_spread['uid']);
                           
                            if($spread_money>0) {
                                $model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $first_spread_user['openid'], 'first' =>L_("X1的子用户的子用户X2通过您的分享购买了商品，验证消费后您将获得佣金。",array("X1" => $fir_user['nickname'],"X2" => $buy_user['nickname'])) , 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => '点击查看详情！'), $order['mer_id']);
                            }
                        }

                        // 三级分享积分
                        $third_user_score_spread_rate = $score_spread_rate['third_rate'] ?? 0;
                        if ($first_spread_user && $third_user_score_spread_rate&&!in_array($first_spread_user['uid'],$spreadUsers)) {
                            $spread_score = round($spreadTotalMoney* $third_user_score_spread_rate / 100, 2);

                            $spreadData = array(
                                'uid' => $first_spread_user['uid'], 
                                'spread_uid' => $second_user['uid'], 
                                'get_uid' => $nowUser['uid'], 
                                'money' => $spread_money, 
                                'order_type' => $orderType, 
                                'orderType' => '1', 
                                'order_id' => $order['order_id'], 
                                'third_id' => $order['group_id'], 
                                'add_time' => time()
                            );

                            if($spreadUser['spread_change_uid']!=0){
                                $spreadData['change_uid'] = $first_spread_user['spread_change_uid'];
                            }
                            // 处理长订单id
                            if ($order['real_orderid']) {
                                $spreadData['real_orderid'] = $order['real_orderid'];
                            }
                            $this->add($spreadData);
                        }
                    }

                }

                /*平台三级积分分佣 2018/12/13 客户定制*/
                if($spreadTotalMoney>0 &&$spreadUser['uid']>0){
                    if(cfg('first_jifen_percent')>0 || cfg('second_jifen_percent')>0 || cfg('third_jifen_percent')>0){
                        $param=array();
                        $param['get_uid']=$nowUser['uid'];
                        $param['first_uid']=$spreadUser['uid'];
                        $param['second_uid']=$second_user['uid'];
                        $param['third_uid']=$first_spread_user['uid'];
                        $param['spreadTotalMoney']=$spreadTotalMoney;
                        $param['order_id']=$order['order_id'];
                        (new PercentRateService())->addSpreadJifenLog($param,'group',$order['store_id']);
                    }
                }
            }
        }elseif(!empty($nowUser['openid'])&&cfg('open_spread_rand')){
            /* 开启三级推广随机一级推广奖励功能，唯一次奖励 */
            //一级推广用户
            $this->spreadRandOnlyOnce($orderType, $nowUser, $order);
        }elseif(!empty($nowUser['openid'])&&cfg('open_spread_appoint_user') && $nowUser['can_spread']==1 && cfg('open_spread_only_once')&&(cfg('spread_money_limit')==0 || cfg('spread_money_limit')<=$spreadTotalMoney)){
            /* 开启指定用户享受三级推广功能且开启唯一次奖励 */
            //一级推广用户
            $this->spreadUserOnlyOnce($orderType, $spreadTotalMoney,$nowUser,$order);
        }
    }


    /**
     * 开启三级推广随机一级推广奖励功能，唯一次奖励 。a用户通过个人中心的推广海报推广下级的一级新用户b（必须为平台新用户），b用户在平台下单后（任意业务），a用户即可获得平台奖励的一次性随机佣金金额（只奖励一次性）。
     * User: hengtingmei
     * Date: 2021/5/25 15:26
     * @param bool $field
     * @param array $where
     * @return array|mixed|\think\Model|null
     */
    public function spreadRandOnlyOnce($orderType, $nowUser, $nowOrder=array()){
        if (cfg('open_spread_rand')!=1) {
            $return = array(
                'error_code' =>0,
                'msg' =>'一级推广随机奖励未开启',
            );
        }

        //一级推广用户
        $return = array(
            'error_code' =>0,
            'msg' =>'添加成功',
        );

        // 查询推广用户
        $nowUserSpread = (new UserSpreadService())->getOneRow(['uid'=>$nowUser['uid']]);

        //判断推广用户是否可以获得佣金
        if ($nowUserSpread && !((new UserService())->canSpread($nowUserSpread['spread_uid']))) {
            $nowUserSpread = [];
        }

        if(empty($nowUserSpread)){
            $return['error_code'] = 1;
            $return['msg'] = '没有推广用户';
            return $return;
        }

        // 查询推广用户已获得佣金信息
        $user_spread_list = (new UserSpreadListService())->getOne(array('uid'=>$nowUserSpread['spread_uid'],'get_uid'=>$nowUser['uid'],'is_rand'=>1,'status' => array('neq',2)));
        if ($user_spread_list) {
            // 获得过奖励 直接返回
            $return['error_code'] = 1;
            $return['msg'] = '推广用户已获得过奖励，不能再获得';
            return $return;
        }

        // 推广用户详细信息
        $spread_user =(new UserService())->getUser($nowUserSpread['spread_uid']);
        if(!$spread_user){
            // 推广用户不存在
            $return['error_code'] = 1;
            $return['msg'] = '推广用户不存在';
            return $return;
        }

        if($spread_user['uid'] == $nowUser['uid']){
            // 推广用户是自己
            $return['error_code'] = 1;
            $return['msg'] = '推广用户是自己';
            return $return;
        }

        $min = cfg('spread_rand_min');
        $max = cfg('spread_rand_max');
        if (!is_numeric($min) || !is_numeric($max)) {
            $return['error_code'] = 1;
            $return['msg'] = '随机金额最小值或最大值未不正确';
            return $return;
        }

        if ($min>$max) {
            $return['error_code'] = 1;
            $return['msg'] = '随机金额最小值不能大于最大值';
            return $return;
        }

        // 将获得的佣金
        $spread_money = random_float($min ,$max);

        $open_extra_price = cfg('open_extra_price');
        $href = cfg('site_url') . '/wap.php?g=Wap&c=My&a=spread_list&status=-1';
        // 添加数据
        $spread_data = array(
            'uid'=>$spread_user['uid'],
            'spread_uid'=>0,
            'is_rand'=>1,
            'get_uid'=>$nowUser['uid'],
            'money'=>$spread_money,
            'order_type'=>$orderType,
            'order_id'=>$nowOrder['order_id'],
            'add_time'=>time()
        );
        switch ($orderType) {
            case 'shop':
            case 'mall':
                $spread_data['third_id'] = $nowOrder['store_id'];
                // 处理长订单id
                if ($nowOrder['real_orderid']) {
                    $spread_data['real_orderid'] = $nowOrder['real_orderid'];
                }
                break;
            case 'meal':
                $spread_data['third_id'] = $nowOrder['store_id'];
                // 处理长订单id
                if ($nowOrder['real_orderid']) {
                    $spread_data['real_orderid'] = $nowOrder['real_orderid'];
                }
                break;
            case 'store':
            case 'cash':
                $spread_data['third_id'] = $nowOrder['store_id'];
                // 处理长订单id
                if ($nowOrder['orderid']) {
                    $spread_data['real_orderid'] = $nowOrder['orderid'];
                }
                break;
            case 'group':
                $spread_data['third_id'] = $nowOrder['group_id'];
                // 处理长订单id
                if ($nowOrder['real_orderid']) {
                    $spread_data['real_orderid'] = $nowOrder['real_orderid'];
                }
                break;
            case 'sub_card':
                $spread_data['third_id'] = $nowOrder['store_id'];
                // 处理长订单id
                if ($nowOrder['real_orderid']) {
                    $spread_data['real_orderid'] = $nowOrder['real_orderid'];
                }
                break;
            case 'recharge':
                $spread_data['third_id'] = $nowOrder['store_id'];
                if($spread_user['spread_change_uid']!=0){
                    $spread_data['change_uid'] =    $spread_user['spread_change_uid'];
                }
                // 处理长订单id
                if ($nowOrder['orderid']) {
                    $spread_data['real_orderid'] = $nowOrder['orderid'];
                }
                break;
            case 'classifynew':
                $spread_data['order_id'] = 0;
                $spread_data['third_id'] = 0;
                $spread_data['status'] = 1;
                break;
            case 'yuedan':
                $spread_data['third_id'] = $nowOrder['rid'];
                break;
            case 'activity':
                $spread_data['third_id'] = $nowOrder['activity_id'];
                // 处理长订单id
                if ($nowOrder['real_orderid']) {
                    $spread_data['real_orderid'] = $nowOrder['real_orderid'];
                }
                break;
        }

        if($spread_user['spread_change_uid']!=0){
            $spread_data['change_uid'] =    $spread_user['spread_change_uid'];
        }

        if ($spread_money<=0) {
            $return['error_code'] = 1;
            $return['msg'] = '推广佣金小于等于0';
            return $return;
        }
        $this->add($spread_data);

        if($nowUserSpread['uid']){
            $buy_user =(new UserService())->getUser($nowUserSpread['uid']);
        }elseif($nowUserSpread['openid']){
            $buy_user =(new UserService())->getUser($nowUserSpread['openid'],'openid');
        }
        if($open_extra_price){
            $money_name = cfg('extra_price_alias_name');
        }else{
            $money_name = L_('佣金');
        }
        if ($orderType=='activity') {
            $money_name = L_('活动佣金');
            $message =  $buy_user['nickname'] . '通过您的分享参与了活动，验证消费后您将获得'.$money_name.'。';
        }else{
            $message =  L_('x1通过您的分享购买商品，验证消费后您将获得x2。',array('x1'=>$buy_user['nickname'],'x2'=>$money_name));

        } 

        $model = new TemplateNewsService;
        $model->sendTempMsg(
            'OPENTM201812627', 
            array(
                'href' => $href, 
                'wecha_id' => $spread_user['openid'], 
                'first' => $message,
                'keyword1' => $spread_money, 
                'keyword2' => date("Y年m月d日 H:i"), 
                'remark' => L_('点击查看详情！'),
            ),
            $nowOrder['mer_id']
        );
        return $return;
    }

    /**
     * 开启指定用户享受三级推广功能且开启唯一次奖励
     * User: hengtingmei
     * Date: 2021/5/25 15:26
     * @param bool $field
     * @param array $where
     * @return array|mixed|\think\Model|null
     */
    public function spreadUserOnlyOnce($orderType,$spreadTotalMoney,$nowUser,$nowOrder=array()){
        //一级推广用户
        $return = array(
            'error_code' =>0,
            'msg' =>'添加成功',
        );

        // 查询推广用户
        $nowUserSpread = (new UserSpreadService())->getOneRow(['uid'=>$nowUser['uid']]);

        if(empty($nowUserSpread)){
            $return['error_code'] = 1;
            $return['msg'] = '没有推广用户';
            return $return;
        }

        // 查询推广用户已获得佣金信息
        $user_spread_list = $this->getOne(array('uid'=>$nowUserSpread['spread_uid'],'get_uid'=>$nowUser['uid'],'status' => array('neq',2)));
        if ($user_spread_list) {
            // 获得过奖励 直接返回
            $return['error_code'] = 1;
            $return['msg'] = '推广用户已获得过奖励，不能再获得';
            return $return;
        }

        // 推广用户详细信息
        $spread_user =(new UserService())->getUser($nowUserSpread['spread_uid']);
        if(!$spread_user){
            // 推广用户不存在
            $return['error_code'] = 1;
            $return['msg'] = '推广用户不存在';
            return $return;
        }
        if($spread_user['uid'] == $nowUser['uid']){
            // 推广用户是自己
            $return['error_code'] = 1;
            $return['msg'] = '推广用户是自己';
            return $return;
        }

        //一级分享佣金比例
        if ($orderType == 'activity') {
            $spread_rate = (new PercentRateService())->getUserSpreadRate($nowOrder['activity_id'],'activity',$nowOrder['order_id']);
        }else{
            $spread_rate = (new PercentRateService())->getUserSpreadRate($nowOrder['mer_id'],$orderType);
        }
        $user_spread_rate = $spread_rate['first_rate'];

        // 一级分享积分
        if ($orderType=='group') {
            $score_spread_rate = (new PercentRateService())->getUserScoreSpreadRate('group',$nowOrder['group_id']);
            $user_score_spread_rate = $score_spread_rate['first_rate'];
        }

        if(!$user_spread_rate||!$user_score_spread_rate){
            $return['error_code'] = 1;
            $return['msg'] = '推广佣金比例未设置';
            return $return;
        }

        if ($user_score_spread_rate ) {
            // 一级分享积分获得
            $spread_score = round($spreadTotalMoney * $user_score_spread_rate / 100, 2);
        }

        $open_extra_price = cfg('open_extra_price');
        $href = cfg('site_url') . '/wap.php?g=Wap&c=My&a=spread_list&status=-1';
        // 将获得的佣金
        $spread_money = round($spreadTotalMoney * $user_spread_rate / 100, 2);
        // 添加数据
        $spread_data = array(
            'uid'=>$spread_user['uid'],
            'spread_uid'=>0,
            'get_uid'=>$nowUser['uid'],
            'money'=>$spread_money,
            'order_type'=>$orderType,
            'order_id'=>$nowOrder['order_id'],
            'add_time'=>time()
        );
        switch ($orderType) {
            case 'shop':
            case 'mall':
                if (cfg('shop_goods_spread_edit')==1) { 
                    // 开启每个商品单独佣金
                    $spread_money = $user_spread_rate;
                }

                $spread_data['third_id'] = $nowOrder['store_id'];
                // 处理长订单id
                if ($nowOrder['real_orderid']) {
                    $spread_data['real_orderid'] = $nowOrder['real_orderid'];
                }
                break;
            case 'meal':
                $spread_data['third_id'] = $nowOrder['store_id'];
                // 处理长订单id
                if ($nowOrder['real_orderid']) {
                    $spread_data['real_orderid'] = $nowOrder['real_orderid'];
                }
                break;
            case 'store':
            case 'cash':
                $spread_data['third_id'] = $nowOrder['store_id'];
                // 处理长订单id
                if ($nowOrder['orderid']) {
                    $spread_data['real_orderid'] = $nowOrder['orderid'];
                }
                break;
            case 'group':
                $spread_data['third_id'] = $nowOrder['group_id'];
                // 处理长订单id
                if ($nowOrder['real_orderid']) {
                    $spread_data['real_orderid'] = $nowOrder['real_orderid'];
                }

                if ($user_score_spread_rate) {
                    // 一级分享积分获
                    $spread_data_score = array(
                        'uid'=>$spread_user['uid'],
                        'spread_uid'=>0,
                        'get_uid'=>$nowUser['uid'],
                        'money'=>$spread_score,
                        'order_type'=>'group',
                        'orderType'=>'1',
                        'order_id'=>$nowOrder['order_id'],
                        'third_id'=>$nowOrder['group_id'],
                        'add_time'=>time()
                    );
                    if($spread_user['spread_change_uid']!=0){
                        $spread_data_score['change_uid'] =    $spread_user['spread_change_uid'];
                    }
                    // 处理长订单id
                    if ($nowOrder['real_orderid']) {
                        $spread_data_score['real_orderid'] = $nowOrder['real_orderid'];
                    }
                    $this->add($spread_data_score);
                }
                break;
            case 'sub_card':
                $spread_data['third_id'] = $nowOrder['store_id'];
                // 处理长订单id
                if ($nowOrder['real_orderid']) {
                    $spread_data['real_orderid'] = $nowOrder['real_orderid'];
                }
                break;
            case 'recharge':
                $spread_data['third_id'] = $nowOrder['store_id'];
                if($spread_user['spread_change_uid']!=0){
                    $spread_data['change_uid'] =    $spread_user['spread_change_uid'];
                }
                // 处理长订单id
                if ($nowOrder['orderid']) {
                    $spread_data['real_orderid'] = $nowOrder['orderid'];
                }
                break;
            case 'classifynew':
                $spread_data = array(
                    'uid'=>$spread_user['uid'],
                    'spread_uid'=>0,
                    'get_uid'=>$nowUser['uid'],
                    'money'=>$spread_money,
                    'order_type'=>$orderType,
                    'order_id'=>0,
                    'third_id'=>0,
                    'status'=>1,
                    'add_time'=>time()
                );
                break;
            case 'yuedan':
                $spread_data['third_id'] = $nowOrder['rid'];
                break;
            case 'activity':
                $spread_data['third_id'] = $nowOrder['activity_id'];
                // 处理长订单id
                if ($nowOrder['real_orderid']) {
                    $spread_data['real_orderid'] = $nowOrder['real_orderid'];
                }
                break;
        }

        if($spread_user['spread_change_uid']!=0){
            $spread_data['change_uid'] =    $spread_user['spread_change_uid'];
        }

        if ($spread_money<=0) {
            $return['error_code'] = 1;
            $return['msg'] = '推广佣金小于等于0';
            return $return;
        }

        $this->add($spread_data);
        $buy_user =(new UserService())->getUser($nowUserSpread['openid'], 'openid');
        if($open_extra_price){
            $money_name = cfg('extra_price_alias_name');
        }else{
            $money_name = L_('佣金');
        }
        if ($orderType=='activity') {
            $money_name = L_('活动佣金');
            $message =  $buy_user['nickname'] . '通过您的分享参与了活动，验证消费后您将获得'.$money_name.'。';
        }else{
            $message =  L_('x1通过您的分享购买商品，验证消费后您将获得x2。',array('x1'=>$buy_user['nickname'],'x2'=>$money_name));

        }

        $model = new TemplateNewsService;
        $model->sendTempMsg(
            'OPENTM201812627', 
            array(
                'href' => $href, 
                'wecha_id' => $spread_user['openid'], 
                'first' => $message,
                'keyword1' => $spread_money, 
                'keyword2' => date("Y年m月d日 H:i"), 
                'remark' => L_('点击查看详情！'),
            ),
            $nowOrder['mer_id']
        );
        return $return;
    }

    /**
     * 添加用户分佣佣金
     * User: hengtingmwi
     * Date: 2021/8/2 14:30
     * @param array $orderInfo 订单详情 [
     *                                    'system_balance' => 0, // 平台余额       
     *                                    'pay_money' => 0, // 在线支付金额       
     *                                    'system_score_money' => 0, // 积分抵扣金额       
     *                                    'system_coupon_price' => 0, // 平台优惠券抵扣金额      
     *                                    'merchant_balance_pay' => 0, // 商家会员卡抵扣金额     
     *                                    'store_id' => 0, // 店铺id     
     *                                    'mer_id' => 0, // 商家id     
     *                                    'order_type' => 'meal', // 订单类型    
     *                                    'uid' => 0, // 用户id    
     *                                    'openid' => 0, // 用户微信openid   
     *                                    'real_orderid' => 0, // 长订单号
     *   
     *                                   ]
     * @return bool
     */
    public function addSpreadList($orderInfo){
        $spreadTotalMoney = 0;// 可参与分佣的总金额
        $spreadType = $orderInfo['order_type'] ?? '';// 订单类型

        // 用户信息
        $userMod = new UserService();
        $nowUser = $userMod->getUser($orderInfo['uid']);
        if(empty($nowUser)){
            return false;
        }

        if(cfg('open_score_pay_back') && cfg('system_take_spread_percent')>0 ){// 开启积分返现, 会员分佣占抽成比例
            $spreadTotalMoney = $orderInfo['system_balance'] + $orderInfo['pay_money']+ $orderInfo['system_score_money'] + $orderInfo['system_coupon_price'] + $orderInfo['merchant_balance_pay'];
            $percent = $percent = (new PercentRateService())->getPercentRate($orderInfo['mer_id'],$orderInfo['order_type'],$spreadTotalMoney,'',$orderInfo['store_id'], true);
            $spreadTotalMoney = $spreadTotalMoney*$percent/100;
        }else{
            $spreadTotalMoney = $orderInfo['system_balance'] +$orderInfo['pay_money'];
        }
        
        if(!empty($nowUser['openid'])&&cfg('open_user_spread') && (!cfg('open_spread_appoint_user') || ($nowUser['can_spread']==1 && !cfg('open_spread_only_once'))) && (cfg('spread_money_limit')==0 || cfg('spread_money_limit')<=$spreadTotalMoney) && !cfg('open_spread_rand')){
            // 获得分佣比例
            $spreadRate = (new PercentRateService())->getUserSpreadRate($orderInfo['mer_id'],$spreadType);

            // 推广用户uid数组
            $spreadUsers[] = $nowUser['uid']; 

            //获得一级分享佣金推广记录
            $spreadWhere = [
                    ['uid', '=', $nowUser['uid']]
                ];
            $nowUserSpread = (new UserSpreadService())->getOneRow($spreadWhere);

            //判断推广用户是否可以获得佣金
            if ($nowUserSpread && !($userMod->canSpread($nowUserSpread['spread_uid']))) {
                $nowUserSpread = [];
            }

            $href = cfg('site_url') . '/wap.php?g=Wap&c=My&a=spread_list&status=-1';
            $openExtraPrice = cfg('open_extra_price');

            if(!empty($nowUserSpread)){
                // 一级推广用户信息
                $spreadUser = $userMod->getUser($nowUserSpread['spread_uid']);
                // 一级推广佣金比例
                $userSpreadRate = $spreadRate['first_rate'];
                if($spreadUser && $userSpreadRate && !in_array($spreadUser['uid'],$spreadUsers)){

                    $spreadMoney = round($spreadTotalMoney * $userSpreadRate / 100, 2);
                    $spreadSaveData = array(
                        'uid'=>$spreadUser['uid'],
                        'spread_uid' => 0,
                        'get_uid' => $nowUser['uid'],
                        'money' => $spreadMoney,
                        'order_type' => $spreadType,
                        'order_id' => $orderInfo['order_id'],
                        'third_id' => $orderInfo['store_id'],
                        'pay_order_id' => $orderInfo['pay_order_id'] ?? 0,
                        'add_time' => time()
                    );
                    if($spreadUser['spread_change_uid'] != 0){
                        $spreadSaveData['change_uid'] = $spreadUser['spread_change_uid'];
                    }
                    // 处理长订单id
                    if ($orderInfo['real_orderid']) {
                        $spreadSaveData['real_orderid'] = $orderInfo['real_orderid'];
                    }
                    // 添加一条推广记录
                    $this->add($spreadSaveData);

                    $buyUser = $userMod->getUser($nowUserSpread['uid'],'uid');
                    if($spreadMoney>0){
                        if($openExtraPrice){
                            $money_name = cfg('extra_price_alias_name');
                        }else{
                            $money_name = L_('佣金');
                        }

                        (new TemplateNewsService())->sendTempMsg(
                            'OPENTM201812627', 
                            [
                                'href' => $href, 
                                'wecha_id' => $spreadUser['openid'], 
                                'first' => L_('x1通过您的分享购买商品，验证消费后您将获得x2。',
                                                [
                                                    'x1'=>$buyUser['nickname'],
                                                    'x2'=>$money_name
                                                ]
                                                ), 
                                'keyword1' => $spreadMoney, 
                                'keyword2' => date("Y年m月d日 H:i"), 
                                'remark' => L_('点击查看详情！')
                            ], 
                            $orderInfo['mer_id']
                        );
                    }
                    $spreadUsers[] = $spreadUser['uid'];
                }

                //第二级分享佣金
                $spreadWhere = [
                        ['uid', '=', $spreadUser['uid']]
                    ];
                $secondUserSpread = (new UserSpreadService())->getOneRow($spreadWhere);

                //判断推广用户是否可以获得佣金
                if ($secondUserSpread && !($userMod->canSpread($secondUserSpread['spread_uid']))) {
                    $secondUserSpread = [];
                }

                if(!empty($secondUserSpread)&&!$openExtraPrice) {
                    //二级推广用户信息
                    $secondUser = $userMod->getUser($secondUserSpread['spread_uid']);
                    // 二级推广佣金比例
                    $subUserSpreadRate = $spreadRate['second_rate'];

                    if ($secondUser && $subUserSpreadRate&&!in_array($secondUser['uid'],$spreadUsers)) {
                        // 二级推广佣金
                        $spreadMoney = round($spreadTotalMoney * $subUserSpreadRate / 100, 2);

                        $spreadSaveData = array(
                            'uid' => $secondUser['uid'], 
                            'spread_uid' => $spreadUser['uid'], 
                            'get_uid' => $nowUser['uid'], 
                            'money' => $spreadMoney, 
                            'order_type' => $spreadType, 
                            'order_id' => $orderInfo['order_id'], 
                            'third_id' => $orderInfo['store_id'], 
                            'pay_order_id' => $orderInfo['pay_order_id'] ?? 0,
                            'add_time' => time()
                        );
                        if($spreadUser['spread_change_uid']!=0){
                            $spreadSaveData['change_uid'] = $secondUser['spread_change_uid'];
                        }
                        // 处理长订单id
                        if ($orderInfo['real_orderid']) {
                            $spreadSaveData['real_orderid'] = $orderInfo['real_orderid'];
                        }
                        
                        // 添加一条推广记录
                        $this->add($spreadSaveData);

                        // 二级推广用户信息
                        $sec_user = $userMod->getUser($secondUserSpread['uid']);
                        if($spreadMoney>0) {
                           (new TemplateNewsService())->sendTempMsg(
                               'OPENTM201812627', 
                               array(
                                   'href' => $href, 
                                   'wecha_id' => $secondUser['openid'], 
                                   'first' => L_('x1的子用户x2通过您的分享购买商品，验证消费后您将获得佣金。',
                                                array(
                                                    'x1'=>$sec_user['nickname'],
                                                    'x2'=>$buyUser['nickname']
                                                    )
                                ), 
                                'keyword1' => $spreadMoney, 
                                'keyword2' => date("Y年m月d日 H:i"), 
                                'remark' => L_('点击查看详情！')
                                ), 
                                $orderInfo['mer_id']
                            );
                        }

                        $spreadUsers[]=$secondUser['uid'];
                    }

                    //三级分享佣金

                    $spreadWhere = [
                        ['uid', '=', $secondUser['uid']]
                    ];
                    $firstUserSpread = (new UserSpreadService())->getOneRow($spreadWhere);

                    //判断推广用户是否可以获得佣金
                    if ($firstUserSpread && !($userMod->canSpread($firstUserSpread['spread_uid']))) {
                        $firstUserSpread = [];
                    }

                    if (!empty($firstUserSpread) && cfg('user_third_level_spread')&&!$openExtraPrice) {
                        //三级推广用户信息
                        $firstSpreadUser = $userMod->getUser($firstUserSpread['spread_uid']);
                        //三级推广用户信息
                        $subUserSpreadRate = $spreadRate['third_rate'];
                        if ($firstSpreadUser && $subUserSpreadRate&&!in_array($firstSpreadUser['uid'],$spreadUsers)) {
                            //三级推广用户信息
                            $spreadMoney = round($spreadTotalMoney * $subUserSpreadRate / 100, 2);
                            
                            $spreadSaveData = array(
                                'uid' => $firstSpreadUser['uid'], 
                                'spread_uid' => $secondUser['uid'], 
                                'get_uid' => $nowUser['uid'], 
                                'money' => $spreadMoney, 
                                'order_type' => $spreadType, 
                                'order_id' => $orderInfo['order_id'], 
                                'third_id' => $orderInfo['store_id'], 
                                'pay_order_id' => $orderInfo['pay_order_id'] ?? 0,
                                'add_time' => time()
                            );
                            if($spreadUser['spread_change_uid']!=0){
                                $spreadSaveData['change_uid'] =     $firstSpreadUser['spread_change_uid'];
                            }
                            // 处理长订单id
                            if ($orderInfo['real_orderid']) {
                                $spreadSaveData['real_orderid'] = $orderInfo['real_orderid'];
                            }
                            // 添加一条推广记录
                            $this->add($spreadSaveData);
                            
                            // 二级推广用户信息
                            $firUser = $userMod->getUser($firstUserSpread['uid']);
                            if($spreadMoney>0) {
                               (new TemplateNewsService())->sendTempMsg(
                                   'OPENTM201812627',
                                    array(
                                        'href' => $href, 
                                        'wecha_id' => $firstSpreadUser['openid'], 
                                        'first' => L_('x1的子用户的子用户x2通过您的分享消购买商品，验证消费后您将获得佣金。',
                                                        array(
                                                            'x1'=>$firUser['nickname'],
                                                            'x2'=>$buyUser['nickname']
                                                        
                                                        )
                                                        ), 
                                        'keyword1' => $spreadMoney, 
                                        'keyword2' => date("Y年m月d日 H:i"), 
                                        'remark' => L_('点击查看详情！')
                                    ), 
                                    $orderInfo['mer_id']
                                );
                            }
                        }
                    }

                }
            }

            /*平台三级积分分佣 2018/12/13 客户定制*/
            if($spreadTotalMoney>0 && $spreadUser['uid']>0){
                if(cfg('first_jifen_percent')>0 || cfg('second_jifen_percent')>0 || cfg('third_jifen_percent')>0){
                    $param=array();
                    $param['get_uid']=$nowUser['uid'];
                    $param['first_uid']=$spreadUser['uid'];
                    $param['second_uid']=$secondUser['uid'];
                    $param['third_uid']=$firstSpreadUser['uid'];
                    $param['spreadTotalMoney']=$spreadTotalMoney;
                    $param['order_id']=$orderInfo['order_id'];
                    (new PercentRateService())->addSpreadJifenLog($param,$spreadType,$orderInfo['store_id']);
                }
            }
        }elseif(!empty($nowUser['openid'])&&cfg('open_spread_rand')){
            /* 开启三级推广随机一级推广奖励功能，唯一次奖励 */
            //一级推广用户
            $spread_res = $this->spreadUserOnlyOnce($spreadType,$nowUser,$orderInfo);
        }elseif(!empty($nowUser['openid'])&&cfg('open_spread_appoint_user') && $nowUser['can_spread']==1 && cfg('open_spread_only_once')&&(cfg('spread_money_limit')==0 || cfg('spread_money_limit')<=$spreadTotalMoney)){
            /* 开启指定用户享受三级推广功能且开启唯一次奖励 */
            //一级推广用户
            $spread_res = $this->spreadUserOnlyOnce($spreadType,$spreadTotalMoney,$nowUser,$orderInfo);
        }
     
        return true;
    }
    
    /**
     * 获取多条记录
     * @author: 衡婷妹
     * @date: 2020/09/18
     */
    public function getSome($where)
    {
        $row = $this->userSpreadListObj->getSome($where);
        return $row ? $row->toArray() : [];
    }
    /**
     * 获取记录
     * @author: 衡婷妹
     * @date: 2020/09/18
     */
    public function getOne($where)
    {
        $row = $this->userSpreadListObj->where($where)->find();
        return $row ? $row->toArray() : [];
    }

    /**
     * 获取记录
     * @author: 衡婷妹
     * @date: 2020/12/17
     */
    public function getCount($where)
    {
        $res = $this->userSpreadListObj->where($where)->count();
        return $res ? $res :0;
    }

    /**
     * 统计某个字段
     * @param  $where array 查询条件
     * @param  $field string 需要统计的字段
     * @author hengtingmei
     * @return string
     */
    public function getTotalByCondition($where, $field){
        $res = $this->userSpreadListObj->getTotalByCondition($where,$field);
        if(!$res){
            return 0;
        }

        return $res['total'];
    }
    /**
     * 添加记录
     * @author: 衡婷妹
     * @date: 2020/09/18
     */
    public function add($data)
    {
        $id = $this->userSpreadListObj->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }
    /**
     * 更新数据
     * @param $where array
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data) {
        if(empty($where) || empty($data)){
            return false;
        }

        $result = $this->userSpreadListObj->updateThis($where, $data);
        if($result === false) {
            return false;
        }

        return $result;
    }

    /**
     * 指定用户产生订单，上级和上上级和上上上级获得佣金（未结算）
     * @param  [type]  $uid          产生订单的用户ID
     * @param  integer $level1_money 上级获得佣金
     * @param  integer $level2_money 上上级获得佣金
     * @param  [type]  $order_type   订单类型
     * @param  [type]  $order_id     订单ID
     * @return [type]                [description]
     */
    public function userToSpread($uid, $order_type, $order_id, $real_orderid = '', $level1_money = 0, $level2_money = 0, $level3_money = 0){
        if(empty($uid)) return false;
        if($level1_money<=0) return false;
        $level1 = (new UserSpread)->getOne([['uid', '=', $uid]]);
        if(empty($level1)) return false;

        $userService = new \app\common\model\service\UserService();
        $level1 = $level1->toArray();
        $level2 = (new UserSpread)->getOne([['uid', '=', $level1['spread_uid']]]);
        if($level2&&$level2_money>0){
            $level2 = $level2->toArray();
            if (!$userService->canSpread($level2['spread_uid'])) {
                $level2 = [];
            }else{
                //给上上级加钱
                $data = [
                    'uid' => $level2['spread_uid'],
                    'spread_uid' => $level1['spread_uid'],
                    'get_uid' => $uid,
                    'money' => get_format_number($level2_money),
                    'order_type' => $order_type,
                    'order_id' => $order_id,
                    'add_time' => time(),
                    'status' => 0,
                    'desc' => '您推广的用户产生交易，您获得佣金',
                    'real_orderid' => $real_orderid,
                    'spread_type' => 0,
                ];
                $this->add($data);
            }
            
            //给上上上级加钱
            if($level3_money && cfg('user_third_level_spread')){
                $level3=(new UserSpread())->getOne([['uid', '=', $level2['spread_uid']]]);
                if($level3) {
                    $level3 = $level3->toArray();
                    if (!$userService->canSpread($level3['spread_uid'])) {
                        $level3 = [];
                    }else{
                        $data = [
                            'uid' => $level3['spread_uid'],
                            'spread_uid' => 0,
                            'get_uid' => $uid,
                            'money' => get_format_number($level3_money),
                            'order_type' => $order_type,
                            'order_id' => $order_id,
                            'add_time' => time(),
                            'status' => 0,
                            'desc' => '您推广的用户产生交易，您获得佣金',
                            'real_orderid' => $real_orderid,
                            'spread_type' => 0,
                        ];
                        $this->add($data);
                    }
                }
            }
        }
        //给上级加钱
        if ($userService->canSpread($level1['spread_uid'])) {
            $data = [
                'uid' => $level1['spread_uid'],
                'spread_uid' => 0,
                'get_uid' => $uid,
                'money' => get_format_number($level1_money),
                'order_type' => $order_type,
                'order_id' => $order_id,
                'add_time' => time(),
                'status' => 0,
                'desc' => '您推广的用户产生交易，您获得佣金',
                'real_orderid' => $real_orderid,
                'spread_type' => 0,
            ];
            $this->add($data);
        }
    
        return true;
    }

    //佣金结算进入用户账户
    public function spreadToComplete($order_type, $order_id){
        $where = [
            ['order_type', '=', $order_type],
            ['order_id', '=', $order_id],
            ['status', '=', 0],
        ];
        $data = $this->getSome($where);
        if(!empty($data)){
            $userService = new UserService();
            foreach ($data as $key => $value) {
                if($this->updateThis(['pigcms_id'=>$value['pigcms_id']], ['status'=>1])){
                    $userService->addMoney($value['uid'],  $value['money'], '推广用户购买商城商品获得佣金');
                }
            }
        }
        return ;
    }
}