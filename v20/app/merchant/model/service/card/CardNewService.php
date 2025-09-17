<?php
/**
 * 商家会员卡service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/09 14:09
 */

namespace app\merchant\model\service\card;
use app\common\model\db\CardNew;
use app\common\model\db\CardUserlist;
use app\common\model\db\OrderPayInfo;
use app\common\model\db\OrderPayRefundInfo;
use app\http\exceptions\CustomException;
use app\merchant\model\db\CardNew as CardNewModel;
use app\common\model\service\UserService as UserService;
use app\common\model\service\coupon\MerchantCouponService;
use app\common\model\service\weixin\AccessTokenExpiresService;
use app\merchant\model\service\MerchantUserRelationService;
use net\Http;
use think\facade\Db;

class CardNewService {
    public $cardNewModel = null;
    public function __construct()
    {
        $this->cardNewModel = new CardNewModel();
    }
    

    /**
     * 自动派券领卡
     * @param $merId int 商家id
     * @return array
     */
    public function merchantCard($merId,$orderInfo){
        $card = $this->getCardByMerId($merId);
        if(!$card){
            return false;
        }

        $uid = $orderInfo['uid'] ?? 0;
        if(!$uid){
            return false;
        }

        $cardInfo = $this->getCardByUidAndMerId($uid,$merId);

        if(empty($cardInfo) && $card['auto_get_buy']){
            // 非会员自动领卡(商家开启自动领卡功能)
            $res = $this->autoGet($uid,$merId);
        }

        $fan = [];
        if (empty($cardInfo)) { //粉丝购买自动派券功能 自动领卡
            $nowUser = (new UserService())->getUser($uid);
            if($nowUser['openid']){
                $where = [
                    'openid' => $nowUser['openid']
                ];
                $fan = (new MerchantUserRelationService())->getOne($where);
            }
        }

        if( $cardInfo || $fan ){ // 会员和粉丝 购买自动派券功能
            // 商家会员卡信息
            $merCardInfo = $card;
            $payType = $orderInfo['pay_type'] ?? '';
            $paymentMoney = $orderInfo['payment_money'] ?? '0';
            if ($merCardInfo['weixin_send'] && $payType =='weixin' && $paymentMoney >= $merCardInfo['weixin_send_money']) {
                //购买自动派券功能（派发的都是用户可以领取的功能）
                $coupon_list = explode(',',$merCardInfo['weixin_send_couponlist']);

                $merchantCouponService = new MerchantCouponService();
                foreach ($coupon_list as $item) {
                    try {
                        $tmp = $merchantCouponService->receiveCoupon($uid, $item);
                    } catch (\Exception $e) {
                    }
                }

                // 购买自动派券 粉丝自动领卡
                if ($fan) {
                    $res = $this->autoGet($uid,$merId,'',true);
                }
            }
        }

        if(!empty($cardInfo)){
            $dataScore['card_id'] = $cardInfo['id'] ;
            $dataScore['type'] = 1;
            if($orderInfo['order_type']=='group' && !$orderInfo['verify_all'] ){
                $dataScore['score_add'] = round($cardInfo['support_score']*($orderInfo['total_money']+$orderInfo['balance_pay']+$orderInfo['merchant_balance']+$orderInfo['card_give_money']));
            }else{
                $dataScore['score_add'] = round($cardInfo['support_score']*($orderInfo['payment_money']+$orderInfo['balance_pay']+$orderInfo['merchant_balance']+$orderInfo['card_give_money']));
            }
            if( $dataScore['score_add']>0){
                $dataScore['desc'] = L_('消费获得会员卡积分');
                $res = $this->addUserMoney($merId,$uid,0,0,$dataScore['score_add'],'',$dataScore['desc']);
            }
        }

    }
    
    /**
     * 增加会员卡余额
     * @param $merId int 商家id
     * @param $uid int 用户id
     * @param $money float 金额
     * @param $giveMoney float 赠送金额
     * @param $giveScore float 赠送积分
     * @param $desc string 描述
     * @param $giveDesc string 赠送描述
     * @return boolean
     */
    public function addUserMoney($merId, $uid, $money, $giveMoney, $giveScore, $desc, $giveDesc=''){
        $cardUserlistService = new CardUserlistService();

        // 用户会员卡信息
        $nowUserlist = $cardUserlistService->getCardByUidAndMerId($merId,$uid);

        $data_userlist['card_money'] = $nowUserlist['card_money'] + $money;
        $data_userlist['card_money_give'] = $nowUserlist['card_money_give'] + $giveMoney;
        $data_userlist['card_score'] = $nowUserlist['card_score'] + $giveScore;

        $where = [
            'id' => $nowUserlist['id']
        ];

        // 保存更新数据
        if($cardUserlistService->updateByCondition($where, $data_userlist)){
            $param =[
                'card_id' => $nowUserlist['id'],
                'type' => 1
            ];
            // 充值金额
            if($money>0){
                $param['money_add'] = $money;
                $param['desc'] = $desc;
            }

            if($giveMoney>0){
                $param['money_add'] = $giveMoney;
                $param['desc'] = $giveDesc;
            }

            if($giveScore>0){
                $param['score_add'] = $giveScore;
                $param['desc'] = $giveDesc;
            }
            // 添加记录
            if(isset($param) && $param){
                (new CardNewRecordService())->addRow($param);
            }

            if ($nowUserlist['wx_card_code'] != ''){
                if($money+$giveMoney!=0){
                    $this->updateWxCard($nowUserlist['wx_card_code'],($money+$giveMoney),0,$desc);
                }
                if($giveScore!=0){
                    $this->updateWxCard($nowUserlist['wx_card_code'],0,$giveScore,'',$giveDesc);
                }

            }
            return true;
        }else{
            return false;
        }
    }

    public function miaomaiUseCardMoney($uid, $money, $type, $orderNo, $merchantStore)
    {
        // 用户会员卡信息
        $cardUser = (new CardUserlistService())->getCardByUidAndMerId($merchantStore['mer_id'], $uid);
        if (empty($cardUser)) {
            throw_exception('用户会员卡信息不存在');
        }
        if (empty((float)$cardUser['card_money']) && empty((float)$cardUser['card_money_give'])) {
            throw_exception('用户会员卡余额不足');
        }
        return Db::transaction(function () use ($uid, $money, $type, $orderNo, $cardUser, $merchantStore) {
            $discountGiveMoney = $discountCardMoney = 0;
            if ($cardUser['card_money_give'] > 0) {
                if ($cardUser['card_money_give'] < $money) {
                    $discountGiveMoney = $cardUser['card_money_give'];
                    $money = $money - $discountGiveMoney;
                } else {
                    $discountGiveMoney = $money;
                    $money = 0;
                }
                (new CardNewService)->useGiveMoney($merchantStore['mer_id'], $uid, $discountGiveMoney, "淼迈操作减少会员卡赠送金额");
                OrderPayInfo::saveInfo([//记录订单支付信息
                    'uid' => $uid,
                    'order_no' => $orderNo,
                    'order_type' => 'miaomai',
                    'pay_type' => 'merchant_card_give_money',
                    'mer_id' => $merchantStore['mer_id'],
                    'store_id' => $merchantStore['store_id'],
                    'pay_money' => $discountGiveMoney,
                ]);
            }

            if ($cardUser['card_money'] > 0 && $money) {
                if ($cardUser['card_money'] < $money) {
                    $discountCardMoney = $cardUser['card_money'];
                } else {
                    $discountCardMoney = $money;
                }
                (new CardNewService)->useMoney($merchantStore['mer_id'], $uid, $discountCardMoney, "淼迈操作减少会员卡金额");
                OrderPayInfo::saveInfo([//记录订单支付信息
                    'uid' => $uid,
                    'order_no' => $orderNo,
                    'order_type' => 'miaomai',
                    'pay_type' => 'merchant_card_money',
                    'mer_id' => $merchantStore['mer_id'],
                    'store_id' => $merchantStore['store_id'],
                    'pay_money' => $discountCardMoney,
                ]);
            }
            return bcadd($discountGiveMoney, $discountCardMoney, 2);
        });
    }

    public function miaomaiAddCardMoney($uid, $money, $type, $orderNo, $merchantStore)
    {
        // 用户会员卡信息
        $cardUser = (new CardUserlistService())->getCardByUidAndMerId($merchantStore['mer_id'], $uid);
        if (empty($cardUser)) {
            throw_exception('用户会员卡信息不存在');
        }
        return Db::transaction(function () use ($uid, $money, $type, $orderNo, $cardUser, $merchantStore) {
            $cardGiveMoney = OrderPayInfo::where([//记录订单支付信息
                'uid' => $uid,
                'order_no' => $orderNo,
                'order_type' => 'miaomai',
                'pay_type' => 'merchant_card_give_money',
            ])->value('pay_money');
            if ($cardGiveMoney > 0) {
                if ($cardGiveMoney < $money) {
                    $discountGiveMoney = $cardGiveMoney;
                    $money = $money - $cardGiveMoney;
                } else {
                    $discountGiveMoney = $money;
                    $money = 0;
                }
                (new CardNewService)->addUserMoney($merchantStore['mer_id'], $uid, 0, $discountGiveMoney, 0, "淼迈操作增加赠送金额");
                OrderPayRefundInfo::saveInfo([//记录订单退款信息
                    'order_no' => $orderNo,
                    'order_type' => 'miaomai',
                    'refund_type' => 'merchant_card_give_money',
                    'refund_money' => $money,
                ]);
            }
            if ($money > 0) {
                (new CardNewService)->addUserMoney($merchantStore['mer_id'], $uid, $money, 0, 0, "淼迈操作增加金额");
                OrderPayRefundInfo::saveInfo([//记录订单退款信息
                    'order_no' => $orderNo,
                    'order_type' => 'miaomai',
                    'refund_type' => 'merchant_card_money',
                    'refund_money' => $money,
                ]);
            }
            return true;
        });
    }
    /**
     * 减少会员卡余额
     * @param $uid int 用户id
     * @param $merId int 商家id
     * @param $money float 金额
     * @param $desc string 描述
     * @return array
     */
    public function useMoney($merId,$uid,$money,$desc){

        $cardUserlistService = new CardUserlistService();

        // 用户会员卡信息
        $nowUserlist = $cardUserlistService->getCardByUidAndMerId($merId,$uid);
        
        if($nowUserlist['card_money']<$money){
            throw_exception(L_("会员卡余额不足"), CustomException::class, 1005);
        }

        $saveData['card_money'] = round($nowUserlist['card_money'] - $money,2);
        $where = [
            'uid' => $uid,
            'id' => $nowUserlist['id']
        ];
        if($cardUserlistService->updateByCondition($where, $saveData)){
            $data['card_id']   = $nowUserlist['id'];
            $data['type']      = 2;
            $data['money_use'] = $money;
            $data['desc']      = $desc;
            (new CardNewRecordService())->addRow($data);
            if ($nowUserlist['wx_card_code'] != ''){
                $this->updateWxCard($nowUserlist['wx_card_code'],$money*-1,0,$desc);
            }
            return true;
        }else{
            throw_exception(L_("扣除会员卡余额失败"), CustomException::class, 1005);
        }
    }

    /**
     * 减少会员卡积分
     * @param $uid int 用户id
     * @param $merId int 商家id
     * @param $money float 金额
     * @param $desc string 描述
     * @return array
     */
    public function userScore($merId, $uid, $score, $desc){
        $cardUserlistService = new CardUserlistService();

        // 用户会员卡信息
        $nowUserlist = $cardUserlistService->getCardByUidAndMerId($merId,$uid);

        if($nowUserlist['card_score']<$score){
            throw new \think\Exception(L_("会员卡积分不足"), 1005);
        }

        $saveData['card_score'] = round($nowUserlist['card_score'] - $score,2);
        $where = [
            'uid' => $uid,
            'id' => $nowUserlist['id']
        ];
        if($cardUserlistService->updateByCondition($where, $saveData)){
            $data['card_id']   = $nowUserlist['id'];
            $data['type']      = 2;
            $data['score_use'] = $score;
            $data['desc']      = $desc;
            (new CardNewRecordService())->addRow($data);

            if ($nowUserlist['wx_card_code'] != ''){
                $this->updateWxCard($nowUserlist['wx_card_code'],$score*-1,0,$desc);
            }
            return true;
        }else{
            throw new \think\Exception(L_("扣除会员卡积分失败"), 1005);
        }
    }

    /**
     * 减少会员卡赠送积分
     * @param $uid int 用户id
     * @param $merId int 商家id
     * @param $money float 金额
     * @param $desc string 描述
     * @return array
     */
    public function useGiveMoney($merId, $uid, $money, $desc){
        $cardUserlistService = new CardUserlistService();

        // 用户会员卡信息
        $nowUserlist = $cardUserlistService->getCardByUidAndMerId($merId,$uid);

        if($nowUserlist['card_money_give']<$money){
            throw new \think\Exception(L_("会员卡余额不足"), 1005);
        }

        $saveData['card_money_give'] = round($nowUserlist['card_money_give'] - $money,2);
        $where = [
            'uid' => $uid,
            'id' => $nowUserlist['id']
        ];
        if($cardUserlistService->updateByCondition($where, $saveData)){
            $data['card_id']   = $nowUserlist['id'];
            $data['type']      = 2;
            $data['money_use'] = $money;
            $data['desc']      = $desc;
            (new CardNewRecordService())->addRow($data);

            if ($nowUserlist['wx_card_code'] != ''){
                $this->updateWxCard($nowUserlist['wx_card_code'],$money*-1,0,$desc);
            }
            return true;
        }else{
            throw new \think\Exception(L_("扣除会员卡赠送余额失败"), 1005);
        }
    }

    /**
     * 更新微信会员卡
     * @param $merId int 商家id
     * @param $wxCardCode int 用户id
     * @param $money float 金额
     * @param $score float 赠送积分
     * @param $moneyDes string 描述
     * @param $scoreDes string 赠送描述
     * @return array
     */
    public function updateWxCard($wxCardCode, $money=0, $score=0, $moneyDes='', $scoreDes=''){
        
        $cardUserlistService = new CardUserlistService();

        // 用户领取信息
        $where = [
            'wx_card_code' => $wxCardCode,
        ];
        $userInfo = $cardUserlistService->getOne($where);
        

        // 会员卡信息
        $card = $this->getCardByMerId($userInfo['mer_id']);

        $http = new Http();

        $accessTokenExpiresService = new AccessTokenExpiresService();
        $res = $accessTokenExpiresService->getAccessToken();
        $url="https://api.weixin.qq.com/card/membercard/updateuser?access_token=".$res['access_token'];

        $arr['code'] = $wxCardCode;
        $arr['card_id'] = $card['wx_cardid'];
        if($money!=0){
            $nowUser = (new UserService())->getUser($userInfo['uid']);
            // TODO
            // $href = C('config.site_url').'/wap.php?c=My_card&a=merchant_card&mer_id='.$userInfo['mer_id'];
            // $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
			// $model->sendTempMsg('OPENTM405462911', 
			// 	array(
			// 		'href' => $href, 
			// 		'wecha_id' => $nowUser['openid'], 
			// 		'first' => $moneyDes.'\n', 
			// 		'keyword1' => $card['name'].L_('会员卡余额变动'), 
			// 		'keyword2' =>L_('已发送'),
			// 		'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']), 
			// 		'keyword4' => $nowUser['nickname'], 
			// 		'remark' => '\n请点击查看详细信息！'
			// 	)
			// );
        }
        
        if($score != 0){
            $arr['bonus'] = $userInfo['card_score'];
            $arr['add_bonus'] = $score;
            $arr['record_bonus'] = $scoreDes;
        }
        $mode = new AccessTokenExpiresService();
        $res = $mode->getAccessToken();
        $url="https://api.weixin.qq.com/card/membercard/updateuser?access_token=".$res['access_token'];
        $res =   $http->curlPost($url,json_encode($arr, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 自动领卡
     * @param $merId int 商家id
     * @return array
     */
    public function autoGet($uid, $merId, $wxcardcode='', $isAuto=false){
        $card = $this->getCardByMerId($merId);
        if(!$card){//未查询到商家的会员卡
            return false;
        }

        if(!$card['status']){
            // 该商家会员卡没有启用
            return false;
        }

        if(!$card['auto_get'] && !$card['auto_get_buy'] && !$isAuto){
            // 该商家会员卡不能自动领卡
            return false;
        }

        if($uid && (new CardUserlistService())->getCardByUidAndMerId($merId, $uid)){
            // 您已经有一张会员卡了，不能再领了
            return false;
        }

        $buyGoodsList = unserialize($card['buy_goods_list']);
        $check = 0;
        if($card['auto_get_buy'] && !empty($buyGoodsList)){
            // TODO
            foreach($buyGoodsList as $key => $val){
                if($key == 'mall' && !empty($buyGoodsList[$key])){
                    // $mall = M("Shop_order_detail")->join("as d left join ".C("DB_PREFIX")."shop_order as o on o.order_id=d.order_id")->where(['o.uid'=>$uid,'o.order_from'=>1,'d.goods_id'=>['in',$buy_goods_list[$key]]])->select();
                    if(!empty($mall)){
                        $check++;
                    }
                }
                if($key == 'shop' && !empty($buyGoodsList[$key])){
                    // $shop = M("Shop_order_detail")->join("as d left join ".C("DB_PREFIX")."shop_order as o on o.order_id=d.order_id")->where(['o.uid'=>$uid,'o.order_from'=>['neq',1],'d.goods_id'=>['in',$buy_goods_list[$key]]])->select();
                    if(!empty($shop)){
                        $check++;
                    }
                }
                if($key == 'group' && !empty($buyGoodsList[$key])){
                    // $group = M("Group_order")->where(['uid'=>$uid,'group_id'=>['in',$buyGoodsList[$key]]])->select();
                    if(!empty($group)){
                        $check++;                            
                    }
                }
            }
            if($check === 0){
                // 没有购买指定团购，不能自动领卡
                return false;
            }
        }
        
       
        $data['card_id'] = $card['card_id'];
        $data['mer_id'] = $merId;
        $data['uid'] = $uid;
        $data['card_money_give'] = $card['begin_money'];
        $data['card_score'] = $card['begin_score'];
        $data['wx_card_code'] = $wxcardcode;

        if($cardId =  (new CardUserlistService())->addCard($data)){
            if($card['begin_money']>0||$card['begin_score']>0){
                $dateRow['card_id']=$cardId;
                $dateRow['type']=1;
                $dateRow['money_add']=$card['begin_money'];
                $dateRow['score_add']=$card['begin_score'];
                $dateRow['desc']=L_('开卡赠送余额积分');
                (new CardNewRecordService())->addRow($dateRow);
            }

            if($card['auto_get_coupon']){//TODO
                // PD('Card_new_coupon')->auto_get_coupon($merId,$uid);
            }

            if($now_user = (new UserService())->getUser($uid)){
                // TODO
                // $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                // $now_merchant = M('Merchant')->where(array('mer_id'=>$merId))->find();
                // $model->sendTempMsg('OPENTM200964573', array('href' => C('config.site_url').'/wap.php?g=Wap&c=My_card&a=merchant_card&mer_id='.$merId, 'wecha_id' =>$now_user['openid'], 'first' =>  L_('您成功领取了商家【X1】的会员卡',$now_merchant['name']), 'keyword1' =>$card_id, 'keyword2' => $now_user['nickname'],'keyword3'=>$now_user['phone'],'keyword4'=>date("Y年m月d日 H:i"), 'remark' => L_('感谢您的关注！')),$now_merchant['mer_id']);
            }

            return $cardId;
        }else{
            return  false;
        }
    }
    /**
     * 根据条件获取折扣金额
     * @param $merId int 商家id
     * @return array
     */
    public function getCardDiscountMoney($money,$merId, $uid) {
        try{
            $cardInfo = $this->getCardByUidAndMerId($merId, $uid);
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }

        // 折扣数
        $cardDiscount = $cardInfo['discount'] ?? 0;
        if($cardDiscount<=0 || $cardDiscount>='10'){
            $cardDiscount = 10;
        }

        $cardDiscountMoney = get_format_number($money*(10-$cardDiscount)/10);

        $returnArr['card_discount_money'] = $cardDiscountMoney;
        $returnArr['card_discount'] = $cardDiscount;
        return $returnArr;
    }

	/**
     * 根据条件获取会员卡信息
     * @param $merId int 商家id
     * @return array
     */
    public function getCardByUidAndMerId($merId, $uid) {
        if(empty($merId)){
            return [];
        }

        $merchantCard = $this->getCardByMerId($merId);
        if(!$merchantCard){
            return [];
        }
        
        if(!$merchantCard['status']){
            return [];
        }

        $userCard = (new CardUserlistService())->getCardByUidAndMerId($merId, $uid);
        if(empty($userCard) || $userCard['status']==0){
            return [];
        }

        $returnArr = [];

        // $coupon_list = D('Card_new_coupon')->get_coupon_by_uid($uid,$now_user_card['mer_id']);
        // if(empty($coupon_list)){
        //     $count = 0;
        // }else{
        //     $count = count($coupon_list);
        // }

        // $now_user = D('User')->get_user($uid);

        $returnArr = array_merge($merchantCard,$userCard);

        // $cardInfo['coupon_num'] = $count;
        return $returnArr;
    }
    
	/**
     * 根据商家id获取会员卡信息
     * @param $merId int 商家id
     * @return array
     */
    public function getCardByMerId($merId) {
        if(empty($merId)){
           return [];
        }

        $card = $this->cardNewModel->getCardByMerId($merId);
        if(!$card) {
            return [];
        }
        
        return $card->toArray(); 
    }

    /**
     *
     * 获取会员卡信息
     * @param where array
     * @return array
     */
    public function getOne($where) {
        if(empty($where)){
            return [];
        }

        $card = $this->cardNewModel->getOne($where);
        if(!$card) {
            return [];
        }

        return $card->toArray();
    }

    /**
     *
     * 获取会员卡信息
     * @param where array
     * @return array
     */
    public function getSome($where, $field = true,$order=true,$page=0,$limit=0) {
        if(empty($where)){
            return [];
        }

        $card = $this->cardNewModel->getSome($where, $field,$order,$page,$limit);
        if(!$card) {
            return [];
        }

        return $card->toArray();
    }
    
    /**
     *
     * 获取会员卡信息
     * @param where array
     * @return array
     */
    public function getUserCardList($uid, $merId)
    {
        $card = CardUserlist::where([
                'uid' => $uid,
                'mer_id' => $merId,
                'status' => 1,
            ])
            ->field('id card_receive_id, card_id, physical_id, card_money, card_money_give')
            ->find();
        
        return $card;
    }
}