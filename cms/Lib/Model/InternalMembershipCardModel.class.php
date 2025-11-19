<?php
class InternalMembershipCardModel extends Model
{
    public function businessType(): array
    {
        return [
            'shop' => C('config.shop_alias_name') ?: '外卖',
            'shop_offline' => L_('线下零售'),
            'offline' => L_('线下支付'),
            'group' => C('config.group_alias_name') ?: '团购',
            'dining' => C('config.dining_alias_name') ?: '餐饮',
            'mall' => C('config.mall_alias_name') ?: '商城',
            'appoint' => C('config.appoint_alias_name') ?: '预约',
            'store' => L_('优惠买单'),
            'cash' => L_('店内收银'),
        ];
    }
    
    //查询用户的一卡通信息
    public function getInternalCard($storeInfo, $keyWords, $password = '', $params = []): array
    {
        if ($storeInfo['is_internal_card_pay'] != 1) {
            throw_exception(L_('店铺未开启一卡通支付'));
        }
        $cardNames = D('Internal_membership_names')
            ->where([
                'status' => 1
            ])
            ->where("(use_area_ids = '' AND FIND_IN_SET(".$storeInfo['store_id'].", use_store_ids)) 
                    OR (use_store_ids = '' AND FIND_IN_SET(".$storeInfo['area_id'].", use_area_ids)) 
                    OR (FIND_IN_SET(".$storeInfo['area_id'].", use_area_ids) AND FIND_IN_SET(".$storeInfo['store_id'].", use_store_ids))")
            ->field('card_name')
            ->select();
        if(empty($cardNames)) {
            throw_exception(L_('没有一卡通信息'));
        }
        if(preg_match('/^1[3-9]\d{9}$/', $keyWords)){
            $where = "(phone='{$keyWords}' OR card_id IN ('{$keyWords}'))";
        }else{
            $where = "card_id IN ('{$keyWords}')";
        }
        
        $InternalCardList = $this
            ->field('uid,card_id, phone, card_name, balance_money, password')
            ->where(['status' => 1, 'card_name' => ['IN', array_column($cardNames,'card_name')]])
            ->where($where)
            ->select();
        if(empty($InternalCardList)){
            throw_exception(L_('未查询到一卡通信息'));
        }
        if(empty($params['no_check_password'])) {
            $delPassword = function (&$InternalCardList, $checkPassword = false)use($password){
                $isCheck = false;
                foreach($InternalCardList as &$item){
                    if($checkPassword && $item['password'] == md5($password)){
                        $isCheck = true;
                    }
                    unset($item['password']);
                    if ($item['balance_money'] <= 0) {
                        $item['balance_money'] = 0;
                    }
                }
                return $isCheck;
            };
            if (!$password) {
                $intersection = array_intersect(array_column($InternalCardList, 'phone'),  C('sys_conf.special_internal_card_id'));
                if (empty($intersection)) {
                    return [
                        'err_code' => false,
                        'data' => [
                            'need_password' => true
                        ],
                        'need_password' => true
                    ];
                }
                $delPassword($InternalCardList);
            }else
            if(!$delPassword($InternalCardList, true)){
                throw_exception(L_('一卡通密码错误'));
            }
        }

        //获取uid
        $uids = array_filter(array_column($InternalCardList, 'uid'));
        
        return [
            'err_code' => false,
            'data' => [
                'uid'=>$uids[0]??0,
                'internal_card_ids' => $InternalCardList,
                'ecard_total_money' => bcadd(array_sum(array_column($InternalCardList, 'balance_money')),0,2),
            ],
        ];
    }
    
    public function getInternalCardPay(array $internalCardIds, $payMoney, int $storeId, int $uid): array
    {
        $storeInfo = D('Merchant_store')->where(['store_id' => $storeId])->find();
        $internalCardIds = $this->getInternalCard($storeInfo, implode(',', $internalCardIds), '', ['no_check_password' => true]);
        $cardIds = array_column($internalCardIds['data']['internal_card_ids'], 'card_id');
        $internalCardPay = [];
        foreach($cardIds as $card_id){
            $payMoney = round($payMoney * 100) / 100;
            $cardInfo = D('Internal_membership_card')
                ->where(['card_id' => $card_id, 'uid' => $uid])
                ->find();
            if (!$cardInfo) {
                throw_exception(L_('会员卡'. $card_id .'不存在'));
            }
            $cardBalance = round($cardInfo['balance_money'] * 100) / 100;
            if ($payMoney > 0){
                $tmp = $payMoney;
                $payMoney -= $cardBalance;
                if ($payMoney <= 0){
                    $payMoney = 0;
                    $internalCardPay[$card_id] = $tmp;
                }else{
                    $internalCardPay[$card_id] = round($cardBalance * 100) / 100;
                }
            }
        }
        return [
            'pay_money' => $payMoney,
            'internal_card_pay' => json_encode($internalCardPay)
        ];
    }
    
    public function getBusinessTypeText($businessType)
    {
        $orderType = $this->businessType();
        
        return $orderType[$businessType] ?? $businessType;
    }
    
    public function getDayIncome($merId, $timestamp): array
    {
        $cardPay = D('Internal_membership_card_log')->where([
                'mer_id' => $merId,
                'create_time' => ['between', [$timestamp - 24*3600, $timestamp]],
            ])
            ->field('IFNULL(SUM(CASE WHEN operation_type = 2 THEN money ELSE 0 END), 0) pay_money, 
            IFNULL(SUM(CASE WHEN operation_type = 3 THEN money ELSE 0 END), 0) refund_money,
            IFNULL(SUM(service_fee), 0) service_fee
            ')
            ->find();
        
        return $cardPay;
    }

    public function getDayOrderCount($merId, $timestamp)
    {
        $cardOrderCount = D('Internal_membership_card_log')->where([
                'mer_id' => $merId,
                'create_time' => ['between', [$timestamp - 24*3600, $timestamp]],
            ])
            ->where(['operation_type' => 2])
            ->group('order_id')
            ->count();

        return $cardOrderCount ?: 0;
    }
}