<?php
/**
 * 商家会员卡service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/09 14:09
 */

namespace app\merchant\model\service\card;
use app\common\model\service\UserService;
use app\common\model\service\weixin\TemplateNewsService;
use app\merchant\model\db\CardNewRecord as CardNewRecordModel;
use app\merchant\model\service\MerchantService;
use Http;
class CardNewRecordService {
    public $cardNewRecordModel = null;
    public function __construct()
    {
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
        $cardInfo = (new CardUserlistService())->getOne(['id'=>$parm['card_id']]);
        $nowUser = (new UserService())->getUser($cardInfo['uid']);

        // 商家信息
        $nowMerchant = (new MerchantService())->getMerchantByMerId($cardInfo['mer_id']);

        // 发送模板消息
        if($nowUser['openid'] && ($addData['money_add']>0 || $addData['money_use']>0)) {
            $href = cfg('site_url') . '/wap.php?c=My_card&a=merchant_card&mer_id='.$cardInfo['mer_id'];
            $money = $addData['money_add']>0?$addData['money_add']:$addData['money_use'];
            if ($addData['money_add'] > 0) {
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
            $dataArr = [
                'href' => $href,
                'wecha_id' => $nowUser['openid'],
                'first' => L_('尊敬的x1,您的【x2】会员卡赠送余额账户发生变动',array('x1'=>$nowUser['nickname'],'x2'=>$nowMerchant['name'])),
                'keyword1' => date('Y-m-d H:i'),
                'keyword2' =>$parm['desc'],
                'keyword3' => ($addData['money_add']>0 ? '+' : '-').$money,
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