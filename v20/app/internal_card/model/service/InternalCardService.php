<?php

namespace app\internal_card\model\service;

use app\common\model\db\Merchant;
use app\common\model\db\MerchantStore;
use app\common\model\service\weixin\TemplateNewsService;
use app\internal_card\model\db\InternalMembershipCard;
use app\internal_card\model\db\InternalMembershipCardLog;
use app\internal_card\model\db\InternalMembershipNames;
use think\facade\Db;

class InternalCardService
{
    public function sendWxMsg($card_id, $money, $msg = '一卡通余额变动'): bool
    {
        $card = Db::table('pigcms_internal_membership_card')->where('card_id', $card_id)->find();
        if (!$card) {
            return false;
        }
        $now_user = Db::table('pigcms_user')->where('uid', $card['uid'])->find();
        if (!$now_user) {
            return false;
        }
        if ($money != 0) {
            $dataArr = [
                'href' => cfg('site_url') . '/packapp/plat/pages/my/internalCard/myCardLog?card_id=' . $card_id.'&store_id='.$card['store_id'],
                'wecha_id' => $now_user['openid'],
                'first' => L_('一卡通余额变动消息提醒'),
                'keyword1' => date('Y-m-d H:i'),
                'keyword2' => '一卡通' . $card_id . $msg,
                'keyword3' => $money,
                'keyword4' => $card['balance_money'],
                'remark' => $card['card_name'] . ': ' . $card_id
            ];
            (new TemplateNewsService())->sendTempMsg('OPENTM401833445', $dataArr, 0);
            return true;
        }
        return false;
    }

    public function internalCard($storeId, $phone, $password, $params = []): array
    {
        $store = MerchantStore::where(['store_id' => $storeId])
            ->field('store_id, area_id,is_internal_card_pay')
            ->find();
        if (!$store) {
            throw_exception(L_('未找到店铺信息'));
        }
        if ($store['is_internal_card_pay'] != 1) {
            throw_exception(L_('店铺未开启一卡通支付'));
        }
        // 一卡通查询
        $cardNames = (new InternalMembershipNames())
            ->where([
                'status' => 1
            ])
            ->where(function ($query) use($store) {
                $query->whereRaw("
                    (use_area_ids = '' AND FIND_IN_SET(".$store['store_id'].", use_store_ids)) 
                    OR (use_store_ids = '' AND FIND_IN_SET(".$store['area_id'].", use_area_ids)) 
                    OR (FIND_IN_SET(".$store['area_id'].", use_area_ids) AND FIND_IN_SET(".$store['store_id'].", use_store_ids))
                ");
            })
            ->field('card_name')
            ->select()
            ->toArray();

        if (empty($cardNames)) {
            throw_exception(L_('没有一卡通信息'));
        }
        $cardList = InternalMembershipCard::where([
                'status' => 1
            ])
            ->where('card_name', 'IN', array_column($cardNames, 'card_name'))
            ->where(function ($query) use ($phone) {
                if($phone){
                    if(preg_match('/^1[3-9]\d{9}$/', $phone)){
                        $query->where('phone', $phone);
                    }
                    $query->whereOr('card_id', $phone);
                }
            })
            ->where(function ($query) use ($params) {
                !empty($params['where']) && $query->where($params['where']);
            })
            ->field('card_id, card_name, balance_money, password, phone')
            ->select()
            ->toArray();
        if (empty($cardList)) {
            throw_exception(L_('未绑定一卡通'));
        }
        if (empty($params['no_check_password'])) {
            if (empty($password)) {
                $intersection = array_intersect(array_column($cardList, 'phone'), config('const.special_internal_card_id'));
                if (empty($intersection)) {
                    return [
                        'need_password' => true
                    ];
                }
            } else {
                $passwordError = true;
                foreach ($cardList as &$val) {
                    if ($val['balance_money'] <= 0) {
                        $val['balance_money'] = 0;
                    }
                    if ($val['password'] == md5($password)) {//只要一张卡的密码正确就通过
                        $passwordError = false;
                    }
                    unset($val['password']);
                }
                $passwordError && throw_exception(L_('一卡通密码错误'));
            }
        } else {
            foreach ($cardList as &$val) {
                if ($val['balance_money'] <= 0) {
                    $val['balance_money'] = 0;
                }
                unset($val['password']);
            }
        }
        $ecardTotalMoney = bcadd(array_sum(array_column($cardList, 'balance_money')), 0, 2);

        return [
            'ecard_total_money' => $ecardTotalMoney,
            'internal_card_ids' => $cardList
        ];
    }

    public function beforePay($storeId, $phoneOrCardId, $payMoney): array
    {
        $internalCardPay = [];
        if (floatval($payMoney) <= 0) {
            return [
                'internal_card_pay' => json_encode($internalCardPay),
                'pay_money' => $payMoney,
            ];
        }
        $merchantStore = MerchantStore::where(['store_id' => $storeId])->find();
        if ($merchantStore['is_internal_card_pay'] != 1) {
            throw_exception(L_('店铺不支持一卡通支付'));
        }
        $cardNames = (new InternalMembershipNames())
            ->where([
                'status' => 1
            ])
            ->where(function ($query) use($merchantStore) {
                $query->whereRaw("
                    (use_area_ids = '' AND FIND_IN_SET(".$merchantStore['store_id'].", use_store_ids)) 
                    OR (use_store_ids = '' AND FIND_IN_SET(".$merchantStore['area_id'].", use_area_ids)) 
                    OR (FIND_IN_SET(".$merchantStore['area_id'].", use_area_ids) AND FIND_IN_SET(".$merchantStore['store_id'].", use_store_ids))
                ");
            })
            ->field('card_name')
            ->select()
            ->toArray();

        if (empty($cardNames)) {
            throw_exception(L_('没有一卡通信息'));
        }
        $cardList = InternalMembershipCard::where([
                'status' => 1
            ])
            ->where('card_name', 'IN', array_column($cardNames, 'card_name'))
            ->where(function ($query) use ($phoneOrCardId) {
                $card = @json_decode($phoneOrCardId, true);
                if (is_array($card)) {
                    $query->where('card_id', 'IN', $card);
                } else {
                    $query->where('phone', $phoneOrCardId)->whereOr('card_id', 'IN', $phoneOrCardId);
                }
            })
            ->select()
            ->toArray();
        if (empty($cardList)) {
            throw_exception(L_('没有一卡通信息'));
        }
        $totalMoney = array_sum(array_column($cardList, 'balance_money'));
        if ($totalMoney < $payMoney) {
            throw_exception(L_('一卡通余额不足'));
        }
        //计算一卡通扣钱
        foreach ($cardList as $card) {
            if ($card['balance_money'] >= $payMoney) {
                $internalCardPay[$card['card_id']] = $payMoney;
                $payMoney = 0;
            } else {
                $internalCardPay[$card['card_id']] = $card['balance_money'];
                $payMoney = bcsub($payMoney, $card['balance_money'], 2);
            }
            if ($payMoney == 0) {
                break;
            }
        }

        return [
            'internal_card_pay' => json_encode($internalCardPay)
        ];
    }

    public function cardPay($order): bool
    {
        if (empty($order['internal_card_pay'])) {
            throw_exception(L_('一卡通支付数据异常'));
        }
        $cardPayInfo = json_decode($order['internal_card_pay'], true);
        if (empty($cardPayInfo)) {
            throw_exception(L_('一卡通支付数据异常'));
        }
        try {
            Db::transaction(function () use ($cardPayInfo, $order) {
                $time = time();
                foreach ($cardPayInfo as $cardId => $payMoney) {
                    $card = InternalMembershipCard::where('card_id', $cardId)->find();
                    $cardBalance = floatval($card['balance_money']);
                    if ($cardBalance < $payMoney) {
                        throw_exception(L_('会员卡【' . $cardId . '】余额不足'));
                    }
                    $card->balance_money = bcsub($cardBalance, $payMoney, 2);
                    $card->last_time = $time;
                    $card->save();

                    $cardLog = (new InternalMembershipCardLog());
                    $cardLog->setAttrs([
                        'card_id' => $cardId,
                        'operation_type' => 2,//交易
                        'describe' => "购买商品, 扣除一卡通余额{$payMoney}元, 订单编号{$order['order_no']}",
                        'before_balance' => $cardBalance,
                        'money' => $payMoney,
                        'uid' => $card['uid'],
                        'after_balance' => $card->balance_money,
                        'business_type' => $order['business_type'],
                        'order_id' => $order['order_no'],
                        'mer_id' => $order['mer_id'],
                        'store_id' => $order['store_id'],
                        'create_time' => $time,
                    ]);
                    $cardLog->save();
                }
            });
        } catch (\Exception $e) {
            throw_exception($e->getMessage());
        }

        return true;
    }

    public function refund($order, $refundMoney): bool
    {
        if (empty($order['internal_card_pay'])) {
            throw_exception(L_('一卡通支付数据异常'));
        }
        $cardPayInfo = json_decode($order['internal_card_pay'], true);
        if (empty($cardPayInfo)) {
            throw_exception(L_('一卡通支付数据异常'));
        }
        if (empty($order['pay_time'])) {
            throw_exception(L_('订单未支付'));
        }
        try {
            Db::transaction(function () use ($cardPayInfo, $refundMoney, $order) {
                $time = time();
                foreach ($cardPayInfo as $cardId => $payMoney) {
                    $card = InternalMembershipCard::where('card_id', $cardId)->find();
                    if ($refundMoney < $payMoney) {
                        $payMoney = $refundMoney;
                    }
                    $card->balance_money = bcadd($card['balance_money'], $payMoney, 2);
                    $card->last_time = $time;
                    $card->save();

                    $cardLog = (new InternalMembershipCardLog());
                    $cardLog->setAttrs([
                        'card_id' => $cardId,
                        'operation_type' => 3,//交易退款
                        'describe' => "商品退款, 退回一卡通余额{$payMoney}元, 订单编号{$order['order_no']}",
                        'before_balance' => $card['balance_money'],
                        'money' => $payMoney,
                        'after_balance' => $card->balance_money,
                        'business_type' => $order['business_type'],
                        'order_id' => $order['order_no'],
                        'uid' => $order['uid'],
                        'mer_id' => $order['mer_id'],
                        'store_id' => $order['store_id'],
                        'create_time' => $time,
                    ]);
                    $cardLog->save();
                    $this->sendWxMsg($cardId, '+' . $payMoney, $order['business_type_text'] . '商品退款');

                    $refundMoney = bcsub($refundMoney, $payMoney, 2);
                    if ($refundMoney <= 0) {
                        break;
                    }
                }
            });
        } catch (\Exception $e) {
            throw_exception($e->getMessage());
        }

        return true;
    }

    public function orderList($merId, $params)
    {
        $where[] = ['ms.mer_id', '=', $merId];
        if (!empty($params['store_id'])) {
            $where[] = ['ms.store_id', '=', $params['store_id']];
        }
        if (!empty($params['uid'])) {
            $where[] = ['cl.uid', '=', $params['uid']];
        }
        $selectType = [
            'store_name' => 'ms.name',
            'nickname' => 'u.nickname',
            'phone' => 'u.phone',
            'order_no' => 'cl.order_id',
        ];

        if (!empty($params['keywords']) && isset($selectType[$params['search_type']])) {
            $where[] = [$selectType[$params['search_type']], 'like', "{$params['keywords']}%"];
        }
        $where = $this->getArr($params, $where);
        $refundWhere = $where;
        $where[] = ['cl.operation_type', '=', 2];//2-交易 

        $orderList = InternalMembershipCardLog::alias('cl')
            ->leftJoin('user u', 'cl.uid = u.uid')
            ->leftJoin('merchant_store ms', 'cl.store_id = ms.store_id')
            ->where($where);
        $moneySum = clone $orderList;
        $orderList = $orderList
            ->field("
                cl.id, ms.name store_name, u.nickname, u.phone, cl.business_type, cl.order_id order_no,
                SUM(cl.money) money, cl.create_time, cl.staff_name storestaff_name
            ")
            ->append(['business_type_text'])
            ->group('cl.business_type, cl.order_id')
            ->order('cl.create_time DESC');
            if(!empty($params['export'])) {
                $merchantName = Merchant::where('mer_id', $merId)->value('name');
                $orderList = $orderList->select()->toArray();
                $title = "{$merchantName}商家订单记录";
                $xlsCell = [
                    ['store_name', '店铺名称'],
                    ['nickname', '用户姓名'],
                    ['phone', '手机号'],
                    ['business_type_text', '业务类型'],
                    ['order_no', '订单号'],
                    ['money', '交易金额(元)'],
                    ['create_time', '时间'],
                ];
                $fileName = $title.date("Y-m-d", time());
                $exportResult = export_excel($title, $xlsCell, $orderList, $fileName);
                return ['file_url' => $exportResult['url']];
            }else{
                $orderList = $orderList
                    ->paginate(input('page_size', 15))
                    ->each(function ($item){
                        $refund = InternalMembershipCardLog::where([
                            'business_type' => $item['business_type'],
                            'order_id' => $item['order_no'],
                            'operation_type' => 3,//3-交易退款
                        ])->value('id');
                        if ($refund) {
                            $item['refund'] = 1;
                        } else {
                            $item['refund'] = 0;
                        }
                    })->toArray();
            }
        $refundWhere[] = ['cl.operation_type', '=', 3];//3-退款   
        $moneySum = $moneySum->field('SUM(cl.money) money')->find();
        $refund = InternalMembershipCardLog::alias('cl')
            ->leftJoin('user u', 'cl.uid = u.uid')
            ->leftJoin('merchant_store ms', 'cl.store_id = ms.store_id')
            ->where($refundWhere)
            ->field('SUM(cl.money) money')
            ->find();
        $refundMoney = $refund['money'] ?: 0;
        $orderList['money_sum'] = bcsub($moneySum['money'], $refundMoney, 2) ;
        
        return $orderList;
    }

    public function orderPayList($merId, $params)
    {
        $where = [];
        if (!empty($params['operation_type'])) {
            if(!in_array($params['operation_type'], [InternalMembershipCardLog::MONEY_OUT, InternalMembershipCardLog::MONEY_REFUND])){//2-交易 3-交易退款
                throw_exception('状态错误');
            }
            $where[] = ['cl.operation_type', '=', $params['operation_type']];
        } else {
            $where[] = ['cl.operation_type', 'IN', [InternalMembershipCardLog::MONEY_OUT, InternalMembershipCardLog::MONEY_REFUND]];
        }
        if ($merId) {
            $where[] = ['ms.mer_id', '=', $merId];
        }
        if (!empty($params['store_id'])) {
            $where[] = ['ms.store_id', '=', $params['store_id']];
        }
        if (!empty($params['uid'])) {
            $where[] = ['cl.uid', '=', $params['uid']];
        }
        if (!empty($params['card_no'])) {
            $where[] = ['cl.card_id', '=', $params['card_no']];
        }
        $selectType = [
            'card_name' => 'imc.card_name',
            'user_name' => 'imc.user_name',
            'phone' => 'imc.phone',
            'company_owner' => 'imc.company_owner',
            'store_name' => 'ms.name',
            'order_no' => 'cl.order_id',
            'card_no' => 'cl.card_id',
        ];

        if (!empty($params['keywords']) && isset($selectType[$params['search_type']])) {
            $where[] = [$selectType[$params['search_type']], 'like', "{$params['keywords']}%"];
        }
        $where = $this->getArr($params, $where);

        $payList = InternalMembershipCardLog::alias('cl')
            ->leftJoin('internal_membership_card imc', 'cl.card_id = imc.card_id')
            ->leftJoin('merchant_store ms', 'cl.store_id = ms.store_id')
            ->leftJoin('internal_membership_card ic', 'cl.card_id = ic.card_id')
            ->where($where)
            ->field(
                'cl.id, ms.name store_name,imc.card_name, imc.card_id card_no, imc.user_name, imc.phone, imc.company_owner,
                cl.business_type, cl.order_id order_no, cl.money, cl.create_time, cl.operation_type, cl.describe'
            )
            ->append(['business_type_text']);
            if(!empty($params['export'])) {
                $merchantName = Merchant::where('mer_id', $merId)->value('name');
                $payList = $payList->select()->toArray();
                $title = "{$merchantName}商家卡支付记录";
                $xlsCell = [
                    ['store_name', '店铺名称'],
                    ['card_name', '卡名称'],
                    ['card_no', '卡号'],
                    ['user_name', '用户姓名'],
                    ['phone', '手机号'],
                    ['company_owner', '归属单位'],
                    ['business_type_text', '业务类型'],
                    ['order_no', '订单号'],
                    ['money', '交易金额(元)'],
                    ['describe', '描述'],
                    ['create_time', '时间'],
                ];
                $fileName = $title.date("Y-m-d", time());
                $exportResult = export_excel($title, $xlsCell, $payList, $fileName);
                return ['file_url' => $exportResult['url']];
            }else{
                $payList = $payList->paginate(input('page_size', 15))->toArray();
            }

        return $payList;
    }

    /**
     * @param $params
     * @param array $where
     * @return array
     */
    private function getArr($params, array $where): array
    {
        if (!empty($params['order_type'])) {
            $where[] = ['cl.business_type', '=', $params['order_type']];
        }
        if (!empty($params['province_id'])) {
            $where[] = ['ms.province_id', '=', $params['province_id']];
        }
        if (!empty($params['city_id'])) {
            $where[] = ['ms.city_id', '=', $params['city_id']];
        }
        if (!empty($params['area_id'])) {
            $where[] = ['ms.area_id', '=', $params['area_id']];
        }
        if (!empty($params['order_no'])) {
            $where[] = ['cl.order_id', '=', $params['order_no']];
        }
        if (!empty($params['start_time'])) {
            $startTime = strtotime($params['start_time'] . ' 00:00:00');
            $where[] = ['cl.create_time', '>=', $startTime];
        }
        if (!empty($params['end_time'])) {
            $endTime = strtotime($params['end_time'] . ' 23:59:59');
            $where[] = ['cl.create_time', '<=', $endTime];
        }
        return $where;
    }
}