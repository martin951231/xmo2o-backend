<?php

declare(strict_types=1);

namespace app\api\controller;

use app\api\model\service\building\ParkService;
use app\building\service\ParkOrderService;

//优惠券管理
class CouponController extends BaseController
{
    //回收优惠券，返还积分
    public function recycle()
    {
        $merchantId = $this->request->param('merchant_id', 0, 'intval');
        $couponId = $this->request->param('coupon_id', 0, 'intval');
        $grantIds = $this->request->param('grant_ids', '', 'trim');
        fdump_api(request()->param(), 'building/couponRecycle', true);
        return $this->apiOutput(1000, [], '');
    }
}
