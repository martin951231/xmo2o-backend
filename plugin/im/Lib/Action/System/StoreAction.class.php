<?php

class StoreAction extends BaseAction
{
    public $couponCat = [
        'new_user' => '新人券',
        'group' => '团购',
        'meal' => '餐饮',
        'appoint' => '预约',
        'shop' => '外卖',
        'store' => '优惠买单',
        'mall' => '商城',
        'all' => '全品类通用',
        'wxapp' => '微信营销',
        'village_group' => '社区拼团'
    ];

    public function coupons()
    {
        $page = isset($_GET['page']) ? intval($_GET['page']) : 0;
        if ($page > 0) {
            $storeId = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
            $limit = isset($_GET['limit']) && $_GET['limit'] > 0 ? intval($_GET['limit']) : $this->pageSize;
            if ($storeId < 1) {
                layerError('店铺参数有误');
            } else {
                $coupons = PD('Card_new_coupon')->getImCouponByStore($storeId, $page, $limit);

                $coupon_list = array_values($coupons['coupon_list']);
                foreach ($coupon_list as &$coupon) {
                    if ($coupon['img']) {
                        $coupon['img'] = replace_file_domain($coupon['img']);
                    }
                    $coupon['cate_name'] = $this->couponCat[$coupon['cate_name']];
                }
                layerOk($coupons['coupon_count'], $coupon_list);
            }
        } else {
            $storeId = isset($_GET['store_id']) ? $_GET['store_id'] : 0;
            $this->assign('storeId', $storeId);
            $this->display();
        }
    }


    public function systemCoupon()
    {
        $page = isset($_GET['page']) ? intval($_GET['page']) : 0;
        if ($page > 0) {
            $limit = isset($_GET['limit']) && $_GET['limit'] > 0 ? intval($_GET['limit']) : $this->pageSize;
            $mod = M('System_coupon');
            $where = [
                'end_time' => ['gt', time()],
                'status' => 1,
                'start_time' => ['lt', time()],
                'allow_im_get' => 1
            ];
            $field = "coupon_id, name, img, platform, cate_id, cate_name, num, had_pull,`num`-`had_pull` AS left_num,discount,order_money,start_time,end_time";
            $limit = ($page - 1) * $limit . ',' . $limit;
            $count = $mod->where($where)->count();
            $coupons = $mod->where($where)->field($field)->limit($limit)->select();
            foreach ($coupons as &$coupon) {
                if ($coupon['img']) {
                    $coupon['img'] = replace_file_domain($coupon['img']);
                }
                $coupon['cate_name'] = $this->couponCat[$coupon['cate_name']];
            }
            layerOk($count, $coupons);
        } else {
            $storeId = isset($_GET['store_id']) ? $_GET['store_id'] : 0;
            $this->assign('storeId', $storeId);
            $this->display();
        }
    }
}