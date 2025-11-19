<?php

declare(strict_types=1);

namespace app\im\controller;

use app\BaseController;
use app\im\model\ImUser;

class CouponController extends BaseController
{
    /**
     * 领取优惠券
     *
     * @return void
     * @author: zt
     * @date: 2024/07/10
     */
    public function get()
    {
        $couponId = $this->request->param('coupon_id', 0, 'intval');
        $username = $this->request->param('username', '', 'trim');
        $fromType = $this->request->param('type', '', 'trim');
        if ($couponId < 1 || empty($username) || empty($fromType)) {
            return api_output_error(1001, '参数有误');
        }

        $imUserMod = new ImUser();
        list($type, $uuid) = $imUserMod->getUidAndRole($username);
        if ($type != $imUserMod::PREFIX_USER || $uuid < 1) {
            return api_output_error(1001, '非法领取');
        }
        if ($fromType == 'store') {
            $rs =  invoke_cms_model('Card_new_coupon/had_pull', [$couponId, $uuid]);
        } else if ($fromType == 'plat') {
            $rs =  invoke_cms_model('System_coupon/had_pull', [$couponId, $uuid]);
        } else {
            return api_output_error(1001, '优惠券类型错误');
        }
        $rs = $rs['retval'] ?? [];

        if ($rs && $rs['error_code'] == 0) {
            $tip = date('H:i:s') . ' ' . L_('您已成功领取优惠券');
        } else {
            $reason = array(1 => L_('优惠券不存在'), 2 => L_('优惠券已过期'), 3 => L_('优惠券已领完'), 4 => L_('该优惠券只允许新用户领取'), 5 => L_('优惠券不能再领取了'));
            $tip = date('H:i:s') . ' ' . (isset($reason[$rs['error_code']]) ? $reason[$rs['error_code']] : L_('领取失败'));
        }
        return api_output(0, ['message' => $tip]);
    }
}
