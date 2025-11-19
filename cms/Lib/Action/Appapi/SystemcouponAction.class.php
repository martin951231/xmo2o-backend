<?php
class SystemcouponAction extends BaseAction{
	//领取平台优惠券
    public function had_pull()
    {
        $coupon_id = intval($_POST['coupon_id']);
        $uid = $this->_uid;
        $now_user = D('User')->get_user($uid);
        if (empty($now_user)) {
            $this->returnCode('20046009');
        }elseif(empty($now_user['phone'])){
            $this->returnCodeError(L_('未绑定手机号码'));
        }
        //是否一次领取最多可领取数量
        $get_all = intval($_POST['get_all']);
        $model = D('System_coupon');
        $has_get = $model->get_coupon_count_by_uid($coupon_id, $uid);

        $result = $model->had_pull($coupon_id, $uid,'',$get_all);
        if ($result['error_code'] != 0) {
            switch ($result['error_code']) {
                case '1':
                    $error_msg = L_('领取失败');
                    break;
                case '2':
                    $error_msg = L_('优惠券已过期');
                    break;
                case '3':
                    $error_msg = L_('优惠券已经领完了');
                    break;
                case '4':
                    $error_msg = L_('只允许新用户领取');
                    break;
                case '5':
                    $error_msg = L_('不能再领取了');
                case '6':
                    $error_msg = L_('您当前所在城市无法领取该优惠券');
                    break;
            }
            $this->returnCodeError($error_msg);
        }

        $reduce_num = $result['coupon']['reduce_num'];
        $reduce_num = $reduce_num>0?$reduce_num:1;
        $model->decrease_sku(0,$reduce_num,$coupon_id);//网页领取完，微信卡券库存需要同步减少
        if($result['coupon']['limit']-$result['coupon']['has_get']<=$result['coupon']['num']-$result['coupon']['had_pull']){
            $result['coupon']['can_get'] = $result['coupon']['limit']-$result['coupon']['has_get'];
        }else{
            $result['coupon']['can_get'] =$result['coupon']['num']-$result['coupon']['had_pull'];
        }
        $this->returnCode(0,['msg' => L_('领取成功'), 'coupon' => $result['coupon']]);
    }
}
