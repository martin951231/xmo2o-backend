<?php
namespace app\community\model\service;

use think\facade\Db;

/** Uses the existing resident cashier, with identity derived only from the agent session. */
class CommunityAgentBillService
{
    public static function normalize(array $form)
    {
        $ids=$form['order_ids'] ?? [];
        if (!is_array($ids) || count($ids)>1000) { throw new \InvalidArgumentException('账单选择格式错误'); }
        foreach ($ids as $id) {
            if ((!is_int($id) && !is_string($id)) || !ctype_digit((string)$id) || $id<1 || strlen((string)$id)>10) {
                throw new \InvalidArgumentException('账单编号格式错误');
            }
        }
        $ids=array_values(array_unique(array_map('intval',$ids))); sort($ids);
        return ['order_ids'=>$ids,'quote'=>is_string($form['quote'] ?? '') ? ($form['quote'] ?? '') : ''];
    }

    private static function context(array $session)
    {
        $bind=Db::name('house_village_user_bind')->where(['pigcms_id'=>$session['bind_id'],'village_id'=>$session['village_id'],'uid'=>$session['uid'],'status'=>1])->whereIn('type',[0,1,2,3])->find();
        if (!$bind || !$bind['vacancy_id']) { throw new \InvalidArgumentException('无有效住户身份'); }
        return ['village_id'=>(int)$session['village_id'],'pigcms_id'=>(int)$session['bind_id'], 'uid'=>(int)$session['uid'],
            'vacancy_id'=>(int)$bind['vacancy_id'],'summary_id'=>0,'is_electric'=>0,'subject_id'=>[]];
    }

    public static function metadata(array $session)
    {
        $context=self::context($session); $cashier=new HouseNewCashierService();
        $header=$cashier->getOrderChargeTypeList($session['bind_id'],$context); $items=[];
        foreach (($header['charge_type'] ?? []) as $category) {
            $data=array_merge($context,['charge_type'=>$category['key'],'subject_id'=>$category['subject_id'] ?? 0]);
            foreach ($cashier->getOrderProjectList($data) as $project) {
                $data['project_id']=$project['key'];
                foreach ($cashier->getOrderRuleList($data) as $rule) {
                    $data['rule_id']=$rule['key'];
                    foreach ($cashier->getChargeOrderList($data) as $order) {
                        // Recheck ownership even if the legacy list changes its filtering later.
                        if ((int)$order['room_id']!==$context['vacancy_id']) { continue; }
                        $items[$order['order_id']]=['id'=>(int)$order['order_id'],'name'=>$project['value'],
                            'period'=>$order['title'],'amount'=>number_format((float)$order['modify_money'],2,'.',''),
                            'project_id'=>(int)$project['key'],'rule_id'=>(int)$rule['key'],
                            'is_cycle'=>(int)($order['is_cycle'] ?? 0),'unify_group'=>(string)($order['order_unify_flage'] ?? ''),
                            'order_type'=>$order['order_type']];
                    }
                }
            }
        }
        return ['bills'=>array_values($items),'bill_address'=>$header['address'] ?? '', 'bill_combine'=>false,
            'missing_fields'=>[],'blocking_reason'=>'','can_submit'=>false];
    }

    private static function validateSelection(array $session,array $form)
    {
        $meta=self::metadata($session); $ids=$form['order_ids'];
        $available=array_column($meta['bills'],'id');
        if (!$ids || array_diff($ids,$available)) { throw new \InvalidArgumentException('账单已变化，请刷新后重新选择'); }
        foreach ($meta['bills'] as $selected) {
            if (!in_array($selected['id'],$ids,true)) { continue; }
            foreach ($meta['bills'] as $other) {
                $sameGroup=$selected['unify_group']!=='' && $selected['unify_group']===$other['unify_group'];
                $earlier=$selected['is_cycle']===2 && $selected['project_id']===$other['project_id'] &&
                    ($other['period']<$selected['period'] || ($other['period']===$selected['period'] && $other['id']<$selected['id']));
                if (($sameGroup || $earlier) && !in_array($other['id'],$ids,true)) { throw new \InvalidArgumentException('请一并选择该项目的前期欠费及关联账单'); }
            }
        }
        return $meta;
    }

    public static function quote(array $session,array $form,$persist=false)
    {
        $meta=self::validateSelection($session,$form);
        $data=array_merge(self::context($session),['all_checked'=>false,'charge_type'=>[],'order_id'=>$form['order_ids']]);
        if ((new \app\common\model\service\config\ConfigCustomizationService())->getWSXCustomizedOpen()) {
            $electric=[];
            foreach ($meta['bills'] as $bill) {
                if (in_array($bill['id'],$form['order_ids'],true)) { $electric[]=in_array($bill['order_type'],['electric','electricity_loss'],true) ? 1 : 0; }
            }
            if (count(array_unique($electric))>1) { throw new \InvalidArgumentException('当前小区电费与其他费用需分开支付，请分开选择'); }
            $data['is_electric']=$electric[0];
        }
        // The legacy calculator writes selection records and sometimes discount data.
        // Draft quotes must roll those writes back; submit recalculates inside its transaction.
        if (!$persist) { Db::startTrans(); }
        try { $result=(new HouseNewCashierService())->CalculateOrderTotal($data); }
        finally { if (!$persist) { Db::rollback(); } }
        $actual=array_map('intval',$result['order_id'] ?? []); sort($actual);
        if ($actual!==$form['order_ids']) { throw new \InvalidArgumentException('账单范围已变化，请刷新重新选择'); }
        $amount=number_format((float)$result['pay_money'],2,'.','');
        if ((float)$amount<=0) { throw new \InvalidArgumentException('所选账单无需支付，请刷新账单'); }
        return ['bill_amount'=>$amount,'quote'=>hash('sha256',json_encode([$session['id'],$actual,$amount]))];
    }

    public static function submit(array $session,array $form)
    {
        Db::name('house_village_user_bind')->where('pigcms_id',$session['bind_id'])->lock(true)->find();
        // Lock the selected bill rows as well as the versioned session before creating a summary.
        Db::name('house_new_pay_order')->whereIn('order_id',$form['order_ids'])->order('order_id')->lock(true)->select();
        $quote=self::quote($session,$form,true);
        if (!hash_equals($quote['quote'],$form['quote'])) { throw new \InvalidArgumentException('账单金额已变化，请重新计算并确认'); }
        $result=(new NewPayService())->CashierApiGoPay($session['bind_id'],$session['village_id'],'wxapp',[
            'uid'=>$session['uid'],'summary_id'=>0,'resource_from'=>'','pay_money'=>$quote['bill_amount'],
            'deposit_money'=>0,'deposit_type'=>0,'order_type'=>'','rule_id'=>0]);
        if (empty($result['order_id'])) { throw new \RuntimeException('缴费订单创建失败'); }
        return (int)$result['order_id'];
    }

    public static function result(array $session)
    {
        $plat=Db::name('plat_order')->where(['order_id'=>$session['application_id'],'uid'=>$session['uid'],'business_type'=>'village_new_pay'])->find();
        $summary=$plat ? Db::name('house_new_pay_order_summary')->where(['summary_id'=>$plat['business_id'], 'village_id'=>$session['village_id'],'pay_uid'=>$session['uid'],'pay_bind_id'=>$session['bind_id']])->find() : null;
        if (!$summary) { throw new \InvalidArgumentException('缴费订单不存在，请联系物业核实'); }
        $paid=(int)$summary['is_paid']===1;
        $details=[];
        if ($paid) {
            $rows=Db::name('house_new_pay_order')->where(['summary_id'=>$summary['summary_id'],'village_id'=>$session['village_id'],'is_paid'=>1])->field('order_id,order_name')->order('order_id')->select()->toArray();
            foreach ($rows as $row) { $details[]=['id'=>(int)$row['order_id'],'name'=>$row['order_name'] ?: '缴费账单',
                'url'=>'/pages/houseMeter/NewCollectMoney/billDetails?order_id='.$row['order_id'].'&type=2']; }
        }
        return ['order_type'=>'village_new_pay','payment_required'=>!$paid,'payment_complete'=>$paid,
            'bill_amount'=>number_format((float)$summary['pay_money'],2,'.',''),
            'message'=>$paid ? '支付完成' : '缴费订单已创建，待支付',
            'bill_details'=>$details];
    }
}
