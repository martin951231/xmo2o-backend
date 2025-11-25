<?php
/**
 * 商家会员卡记录service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/15 10:14
 */

namespace app\merchant\model\service\card;
use app\common\model\service\weixin\TemplateNewsService;
use app\merchant\model\service\MerchantService as MerchantService;
use app\merchant\model\db\CardNewRechargeOrder as CardNewRechargeOrderModel;
use app\merchant\model\db\CardNewRecord as CardNewRecordModel;
class CardNewRechargeOrderService {
    public $cardNewRechargeOrderModel = null;
	public $cardNewRecordModel = null;
    public function __construct()
    {
        $this->cardNewRechargeOrderModel = new CardNewRechargeOrderModel();
		$this->cardNewRecordModel = new CardNewRecordModel();
    }
    
     /*增加记录行数*/
    public function addRow($parm){
        $addData['card_id'] = $parm['card_id'];
        $addData['type'] = $parm['type'];
        $addData['money_add']  = empty($parm['money_add'])?0:$parm['money_add'];
        $addData['money_use']  = empty($parm['money_use'])?0:$parm['money_use'];
        $addData['score_add']  = empty($parm['score_add'])?0:$parm['score_add'];
        $addData['score_use']  = empty($parm['score_use'])?0:$parm['score_use'];
        $addData['coupon_add'] = empty($parm['coupon_add'])?0:$parm['coupon_add'];
        $addData['coupon_use'] = empty($parm['coupon_use'])?0:$parm['coupon_use'];
        $addData['desc'] = $parm['desc'];

        // 用户领取员卡信息
        $cardInfo = (new CardNewService())->getOne(['card_id'=>$parm['card_id']]);

        // 商家信息
        $nowMerchant = (new \app\merchant\model\service\MerchantService())->getMerchantByMerId($cardInfo['mer_id']);

        // 发送模板消息
        if($cardInfo['openid'] && ($parm['money_add']>0 || $parm['money_use']>0)) {
             $href = cfg('site_url') . '/wap.php?c=My_card&a=merchant_card&mer_id='.$cardInfo['mer_id'];
             $money = $parm['money_add']>0 ? $parm['money_add']: $parm['money_use'];
            if ($parm['money_add'] > 0) {
                //充值
                $newInfo = [
                    'tempKey' => '45572',
                    'thing20' => L_('【X1】商家会员卡账户', ['X1' => $nowMerchant['name']]),
                    'amount3' => $money,
                    'amount15' => $cardInfo['card_money'] + $cardInfo['card_money_give'],
                    'time2' => date('Y-m-d H:i:s')
                ];
            } else {
                //扣除
                $newInfo = [
                    'tempKey' => '55638',
                    'thing6' => L_('【X1】商家会员卡账户', ['X1' => $nowMerchant['name']]),
                    'amount2' => $money,
                    'amount4' => $cardInfo['card_money'] + $cardInfo['card_money_give'],
                    'time1' => date('Y-m-d H:i:s')
                ];
            }
             $dataArr =  [
                    'href' => $href,
                    'wecha_id' => $cardInfo['openid'],
                    'first' => L_('尊敬的x1,您的【x2】会员卡赠送余额账户发生变动',array('x1'=>$cardInfo['nickname'],'x2'=>$nowMerchant['name'])),
                    'keyword1' => date('Y-m-d H:i'),
                    'keyword2' =>$parm['desc'],
                    'keyword3' => ($parm['money_add']>0 ? '+' : '-').$money,
                    'keyword4' => $cardInfo['card_money']+$cardInfo['card_money_give'] ,
                    'remark' => L_('详情请点击此消息进入会员卡查询!'),
                    'new_info' => $newInfo
             ];
            (new TemplateNewsService())->sendTempMsg('OPENTM401833445',$dataArr ,$cardInfo['mer_id']);
        }
        $addData['time'] = $_SERVER['REQUEST_TIME'];
        if($this->add($addData)){
            return true;
        }else{
            return false;
        }
    }

    
    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        
        $data['time'] = time();
        $result = $this->cardNewRecordModel->save($data);
        if(!$result) {
            return false;
        }
        return $this->cardNewRecordModel->id;
        
    }

}