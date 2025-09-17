<?php
/**
 * 商家推广佣金service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/09/18 14:22
 */

namespace app\merchant\model\service\spread;
use app\merchant\model\db\MerchantSpread as MerchantSpread;
use app\merchant\model\service\MerchantService;
use app\merchant\model\service\SystemBillService;

class MerchantSpreadService {
    public $merchantSpreadModel = null;
    public function __construct()
    {
        $this->merchantSpreadModel = new MerchantSpread();
    }
    
    //增加推广佣金记录
    public function addSpreadList($orderInfo, $buyer, $type, $des, $systemTake=0){
        $spreadUser = $this->getOne(['openid'=>$buyer['openid']]);
        if (empty($spreadUser)) {
            return true;
        }
        $spreadRate = (new MerchantPercentRateService())->getMerchantrate($spreadUser['mer_id'],$type);
        $date['mer_id'] = $spreadUser['mer_id'];
        if(empty($spreadUser)||$spreadRate==0||($spreadUser['mer_id']==$orderInfo['mer_id']&&cfg('spread_money_get_type')!=1)||empty($buyer)){
            return true;
        }

        if($orderInfo['is_own']){
            $orderInfo['payment_money']=0;
        }

        $date['uid'] = $orderInfo['uid'];
        $date['openid'] = $buyer['openid'];
        if (cfg('system_take_spread_percent_mer')>0) { // 开启商家推广佣金占抽成比例
            if ($systemTake) {
                $date['money'] = round($systemTake*$spreadRate*cfg('system_take_spread_percent_mer')/10000,2);
            }else{
                return true;
            }
        }else{
            if(cfg('platform_get_merchant_rate_type')==1){//商家获得推广佣金计算方式1根据平台抽成计算
                $date['money'] = round(($systemTake*$spreadRate)/100,2);
            }else{
                $date['money'] = round((($orderInfo['balance_pay']+$orderInfo['payment_money'])*$spreadRate)/100,2);
            }
        }

        $date['order_type'] = $type;
        switch($type){
            case 'group':
            case 'shop':
            case 'mall':
            case 'meal':
            case 'appoint':
                $type_name = cfg($type.'_alias_name');
                break;
            case 'dining':
                $type_name = cfg('meal_alias_name');
                break;
            case 'store':
                $type_name = cfg('store_alias_name').L_('买单');
                break;
            case 'cash':
                $type_name=cfg('cash_alias_name').L_('支付');
                break;
            case 'wxapp':
                $type_name = L_('微信营销');
                break;
            case 'weidian':
                $type_name = L_('微店');
                break;
            case 'coupon':
                $type_name  = L_('平台活动');
                break;
            case 'yydb':
                $type_name  = L_('平台活动');
                break;
            case 'waimai':
                $type_name  = L_('外卖');
                break;
        }
        $date['order_id'] = $orderInfo['order_id'];
        $date['verify_mer_id'] = $orderInfo['mer_id'];
        $date['add_time'] = time();
        $date['des'] = $des;
        // $date['status'] = 0;
        if(cfg('merchant_replace_money')>0 && $date['money']>=cfg('merchant_replace_money') && $orderInfo['mer_id']){
            $this->updateThis(['openid'=>$buyer['openid']], ['mer_id'=>$orderInfo['mer_id']]);
        }

        if($id = (new MerchantSpreadListService())->add($date) && $date['money']>0){  //增加商家余额
            $nowOrder['bill_money'] = $date['money'];
            $nowOrder['total_money'] = $date['money'];
            $nowOrder['order_type'] = 'spread';
            $nowOrder['order_id'] = $id;
            $nowOrder['store_id'] = 0;
            $nowOrder['uid'] = '0';
            $nowOrder['score_discount_type'] = '1';
            $nowOrder['mer_id']  = $spreadUser['mer_id'];

            $nowOrder['desc']=L_("购买X1商品获得佣金,订单号：".$id,array("X1" => $type_name));

            $merchant = (new MerchantService())->getMerchantByMerId($nowOrder['mer_id']);
            $merchant && (new SystemBillService())->billMethod(0,$nowOrder);
        }

        $spreadRate = (new MerchantPercentRateService())->getMerchantrate($spreadUser['mer_id'],$type);
        if(cfg('open_user_award')==1){
            $award_money = round(($orderInfo['balance_pay']+$orderInfo['payment_money'])*$spreadRate/100,2);

            $nowOrder['bill_money'] = $award_money;
            $nowOrder['order_type'] = 'award';
            $nowOrder['order_id'] = 1;
            $nowOrder['mer_id']  = $spreadUser['mer_id'];
            $nowOrder['desc'] = L_('推广用户消费获得奖励金');
            (new SystemBillService())->billMethod(0,$nowOrder);
        }
    }

    /**
     * 获取多条记录
     * @author: 衡婷妹
     * @date: 2020/09/18
     */
    public function getSome($where)
    {
        $row = $this->merchantSpreadModel->getSome($where);
        return $row ? $row->toArray() : [];
    }
    /**
     * 获取记录
     * @author: 衡婷妹
     * @date: 2020/09/18
     */
    public function getOne($where)
    {
        $row = $this->merchantSpreadModel->where($where)->find();
        return $row ? $row->toArray() : [];
    }

    /**
     * 添加记录
     * @author: 衡婷妹
     * @date: 2020/09/18
     */
    public function add($data)
    {
        $id = $this->merchantSpreadModel->insertGetId($data);
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

        $result = $this->merchantSpreadModel->updateThis($where, $data);
        if($result === false) {
            return false;
        }

        return $result;
    }

}