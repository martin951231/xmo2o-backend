<?php


namespace app\common\model\service\merchant_card;

use app\common\model\db\CardNew;
use app\common\model\db\CardUserlist;
use app\common\model\db\Merchant;
use app\common\model\db\User;

class MemberShipCardService
{
    public function getMerchantCardDetail($param){
        $uid = $param['uid'];
        $mer_id = $param['mer_id'];
        $id = $param['id'];
        if(!$mer_id){
            throw new \think\Exception(L_('参数错误！'));
        }
        //获取商家会员卡
        $merchant = (new Merchant())->where(['mer_id'=>$mer_id])->find();
        if(empty($merchant)||!$merchant){
            throw new \think\Exception(L_('商家不存在！'));
        }
        //获取用户信息
        $user = (new User())->where(['uid'=>$uid])->find();
        //获取会员卡是否存在
        $merchant_card = (new CardNew())->where(['mer_id'=>$mer_id])->find();
        if(empty($merchant_card)||!$merchant_card){
            throw new \think\Exception(L_('商家未开通会员卡！'));
        }
        $user_card = (new CardUserlist())->where(['uid'=>$uid,'mer_id'=>$mer_id])->find();
        if(empty($user_card)||!$user_card){
            if ($id) {
                // 扫会员卡二维码进入
                $user_card = (new CardUserlist())->where(array('id'=>$_GET['id']))->find();
                $res = invoke_cms_model('Card_new/auto_reg_or_bind',[$user_card['mer_id'],$user['openid'],$user_card['id'],1]);
                if($res['error_no']==0){
                    $result = $res['retval'];
                    if (!$result['error_code']) {
                        $user_card = (new CardUserlist())->where(['uid'=>$uid,'mer_id'=>$mer_id])->find();
                    } else {
                        throw new \think\Exception($result['msg']);
                    }
                }else{
                    throw new \think\Exception($res['error_msg']);
                }
            } elseif ($merchant_card['self_get'] || $merchant_card['auto_get']) {
                $res = invoke_cms_model('Card_new/auto_get',[$uid,$mer_id]);
                if($res['error_no']==0){
                    $result = $res['retval'];
                    if (!$result['error_code']) {
                        $user_card = (new CardUserlist())->where(['uid'=>$uid,'mer_id'=>$mer_id])->find();
                    } else {
                        throw new \think\Exception($result['msg']);
                    }
                }else{
                    throw new \think\Exception($res['error_msg']);
                }
            } else {
                throw new \think\Exception('您没有会员卡，请联系商家开通！');
            }
        }elseif ($user_card['status'] == 0) {
            throw new \think\Exception(L_('您的会员卡不能使用！'));
        }
        
        $data = [
            'user_name' => $user['nickname'],
            'avatar' => $user['avatar']?replace_file_domain($user['avatar']):'',
        ];

    }
}