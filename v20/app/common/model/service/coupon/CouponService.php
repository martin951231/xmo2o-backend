<?php
namespace app\common\model\service\coupon;
use app\appoint\model\db\Appoint;
use app\common\model\db\CardNewCoupon;
use app\common\model\db\CardNewCouponHadpull;
use app\common\model\db\MerchantStore;
use app\common\model\db\SystemCoupon;
use app\common\model\db\SystemCouponHadpull;
use app\common\model\db\User;
use app\common\model\service\user\UserService;
use app\mall\model\service\MallStaffCartService;
use SystemSendCouponActivityModel;

class CouponService
{
    /**
     * 弹窗优惠券
     * @author 张涛
     * @date 2020/08/04
     */
    public function sendCoupon()
    {
        $uid =request()->log_uid;
        $page = $this->request->param("page", "", "trim");//页面标志
        $nowActivity = (new SystemSendCouponActivityModel)->getNowActivity($uid,$page);
        if (empty($nowActivity)) {
            return api_output_error(1003, L_('没有活动'));
        } else {
            return api_output(0, $nowActivity);
        }
    }


    public function getNowActivity($uid, $page = '')
    {
        if ($uid > 0) {
            $paidOrderCount =(new UserService())->check_new($uid, 'all');
            $isNew = $paidOrderCount ? 1 : 2;
        } else {
            $isNew = 1;
        }

        $today = date('Y-m-d');
        $where = [
            'send_type' => $isNew,
            'is_del' => 0,
            'status' => 1,
            'start_date' => ['elt', $today],
            'end_date' => ['egt', $today],
        ];
        if ($page == 'plat_index') {
            $where['_string'] = 'show_page&1 = 1';
        } else if ($page == 'shop_index') {
            $where['_string'] = 'show_page&2 = 2';
        }else if ($page == 'mall_index') {//商城券
            $where['_string'] = 'show_page&3 = 3';
        } else if ($page == 'all') {
            //$where['_string'] = 'show_page&3 = 3';
        } else {
            return [];
        }
        $activity = $this->where($where)->order('id DESC')->find();
        if (empty($activity)) {
            return [];
        }
        $fields = "s.*";
        $fields .= ",plat.img AS plat_img,plat.is_discount AS plat_is_discount,plat.coupon_id as plat_coupon_id,plat.name AS plat_name,plat.order_money AS plat_order_money,plat.discount_value AS plat_discount_value,plat.discount AS plat_discount,plat.start_time AS plat_start_time,plat.end_time AS plat_end_time,plat.platform AS plat_platform,plat.cate_name AS plat_cate_name,plat.is_privileged AS plat_is_privileged,plat.is_hide AS plat_is_hide,plat.allow_sign AS plat_allow_sign,plat.allow_gift AS plat_allow_gift,plat.allow_im_get AS plat_allow_im_get,plat.allow_new AS plat_allow_new,plat.is_vip_level AS plat_is_vip_level,plat.status AS plat_status,plat.num AS plt_num,plat.`limit` AS plat_limit,plat.use_type AS plat_use_type";
        $fields .= ",mer.mer_id,mer.store_id,mer.img AS mer_img,mer.is_discount AS mer_is_discount,mer.coupon_id as mer_coupon_id,mer.name AS mer_name,mer.order_money AS mer_order_money,mer.discount_value AS mer_discount_value,mer.discount AS mer_discount,mer.start_time AS mer_start_time,mer.end_time AS mer_end_time,mer.platform AS mer_platform,mer.cate_name AS mer_cate_name,mer.allow_new AS mer_allow_new,mer.allow_im_get AS mer_allow_im_get,mer.is_live AS mer_is_live,mer.status AS mer_status,mer.num AS mer_num,mer.`limit` AS mer_limit";

        $setting = M('System_send_coupon_activity_setting')->alias('s')
            ->join(C('DB_PREFIX') . 'system_coupon AS plat ON s.coupon_id = plat.coupon_id AND s.coupon_type="plat"')
            ->join(C('DB_PREFIX') . 'card_new_coupon AS mer ON s.coupon_id = mer.coupon_id AND s.coupon_type="mer"')
            ->field($fields)
            ->where(['s.aid' => $activity['id']])
            ->select();
        $coupon = [];
        $logMod = M('System_send_coupon_activity_log');
        $getCouponIndex = [];
        if ($uid > 0) {
            $logs = $logMod->where(['uid' => $uid, 'aid' => $activity['id']])->field('concat(`coupon_type`,"_",`coupon_id`) AS couponIndex')->select();
            $getCouponIndex = array_column($logs, 'couponIndex');
        }

        $isAlert = false;
        foreach ($setting as $v) {
            //判断用户领取是否达到上线，是否还有剩余
            if ($v[$v['coupon_type'] . '_status'] == 3) {
                continue;
            }

            $canGetNum = $v[$v['coupon_type'] . '_limit'];
            if ($uid > 0) {
                $maxNum = $v[$v['coupon_type'] . '_limit'];
                if ($v['coupon_type'] == 'mer') {
                    $count = M('Card_new_coupon_hadpull')->where(['coupon_id' => $v['coupon_id'], 'uid' => $uid])->count();
                } else {
                    $count = M('System_coupon_hadpull')->where(['coupon_id' => $v['coupon_id'], 'uid' => $uid])->count();
                }
                if ($count >= $maxNum) {
                    continue;
                }else{
                    $canGetNum = $maxNum - $count;
                }
            }

            $data = [
                'id' => $v['id'],
                'type' => $v['coupon_type'],
                'mer_id' => intval($v['mer_id']),
                'coupon_id' => $v['coupon_id'],
                'coupon_name' => $v[$v['coupon_type'] . '_name'],
                'sub_title' => sprintf('满%s可用', getFormatNumber($v[$v['coupon_type'] . '_order_money'])),
                'coupon_type' => $v[$v['coupon_type'] . '_is_discount'] == 1 ? 2 : 1,
                'discount' => $v[$v['coupon_type'] . '_is_discount'] == 1 ? getFormatNumber($v[$v['coupon_type'] . '_discount_value']) : getFormatNumber($v[$v['coupon_type'] . '_discount']),
                'store_ids' => is_null($v['store_id']) ? '' : $v['store_id'],
                'pic' => replace_file_domain($v[$v['coupon_type'] . '_img']),
            ];
            $storeIds = explode(',', $data['store_ids']);
            if (count($storeIds) > 1) {
                $data['is_multi_store'] = true;
            } else {
                $data['is_multi_store'] = false;
            }

            if ($uid == 0) {
                //未登陆
                $data['is_get'] = false;
                $data['btn_txt'] = '登录领取';
                $isAlert = true;
            } else {
                $data['btn_txt'] = '去使用';
                if (in_array($v['coupon_type'] . '_' . $v['coupon_id'], $getCouponIndex)) {
                    $data['is_get'] = true;
                    continue;
                } else {
                    $isAlert = true;
                    $data['is_get'] = false;
                }
            }

            if ($v['coupon_type'] == 'mer') {
                $data['goto'] = C('config.site_url') . '/packapp/plat/pages/store/storeList?coupon_id=' . $v['coupon_id'];
            } else {
                if($v['plat_cate_name'] == 'village_group') { //跳转 社区团购首页
                    $data['goto'] = C('config.site_url') . '/wap.php?g=Wap&c=Village_group&a=index';
                } else {
                    if($v['plat_use_type'] == 1) {
                        $store_ids =  M('System_coupon_store')->field('store_id')->where(['coupon_id'=>$v['coupon_id']])->select();
                        if(count($store_ids) == 1) { //单店铺
                            $data['goto'] = C('config.site_url') . '/packapp/plat/pages/store/homePage?store_id='.$store_ids[0]['store_id'];
                        } else { //多店铺
                            $data['goto'] = C('config.site_url') . '/packapp/plat/pages/store/storeList?coupon_id='.$v['coupon_id'].'&coupon_type=system';
                        }
                    } else {
                        $data['goto'] = C('config.site_url') . '/packapp/plat/pages/store/storeList?coupon_id='.$v['coupon_id'].'&coupon_type=system';
                    }
                }
            }
            for ($i = 0; $i < $canGetNum; $i++) {
                $coupon[] = $data;
            }

        }

        empty($activity['color']) && $activity['color'] = '#FFFFFF';
        empty($activity['background_color']) && $activity['background_color'] = '#E13924';
        empty($activity['background_pic']) && $activity['background_pic'] = C('config.site_url') . '/static/default_background_send_coupon.png';

        $rs = [
            'is_alert'=>$isAlert,
            'activity_id' => $activity['id'],
            'title' => $activity['title'],
            'color' => stripos($activity['color'], 'rgb') !== false ? RGBToHex($activity['color']) : $activity['color'],
            'background_pic' => replace_file_domain($activity['background_pic']),
            'background_color' => stripos($activity['background_color'], 'rgb') !== false ? RGBToHex($activity['background_color']) : $activity['background_color'],
            'coupon_lists' => $coupon
        ];
        return $rs;
    }

    /**
     * 获取相应优惠券列表
     * @param $params
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCouponList($params){
        $uid = $params['uid'];
        $mer_id = $params['mer_id'];
        $goods_id = $params['goods_id'];
        $type = $params['type'];
        $coupon_type = $params['coupon_type'];
        $money = 0;
        if($type=='appoint'){
            $appoint_info = (new Appoint())->where(['appoint_id'=>$goods_id])->findOrEmpty()->toArray();
            if(!$appoint_info){
                return [];
            }
            if($appoint_info['is_appoint_price']){
                $money = $appoint_info['appoint_price'];
            }else{
                //多规格读取规格最小价
                $money = (new \app\appoint\model\db\AppointProduct())->where('appoint_id',$goods_id)->min('price');
            }
        }
        $store_id = [];
        if($mer_id){
            if($type=='appoint'){
                $store_id = (new MerchantStore())->alias('s')->join('appoint_store a','s.store_id = a.store_id')->where(['s.mer_id'=>$mer_id,'s.status'=>1,'a.appoint_id'=>$goods_id])->column('s.store_id');
            }else{
                $store_id = (new MerchantStore())->where(['mer_id'=>$mer_id,'status'=>1])->column('store_id');
            }
        }
        $coupon_list = [];
        if($coupon_type=='system'){
            $system_coupon = (new SystemCouponService)->getSystemCouponList($type, '', $uid, true, true);
            $couponStoreMod = new \app\common\model\db\SystemCouponStore();
            foreach ($system_coupon as $value) {
                //指定店铺
                if ($value['use_type'] == 1&&$store_id) {
                    $findStore = $couponStoreMod->where('coupon_id', $value['coupon_id'])->where([['store_id','in', $store_id]])->find();
                    if (empty($findStore)) {
                        continue;
                    }
                }
                if($value['is_use'] == '0' && $value['status'] == '1'){
                    $temp = [
                        'title' => $value['name'],
                        'rule' => $value['discount_des'],
                        'limit_date' => $value['limit_date'],
                        'money' => $value['discount'],
                        'get' => $value['is_get'],
                        'type' => 'system',
                        'coupon_id' => $value['coupon_id'],
                        'limit_num' => $value['limit'],
                        'limit_day' => '距离到期还有'.intval(($value['end_time']-time())/24/3600).'天'
                    ];
                    $coupon_list[] = $temp;
                }
            }
        }else{
            //商家优惠券
            $system_coupon = (new MerchantCouponService)->getMerchantCouponList($mer_id, $goods_id, $type, '', $uid, true);
            $filterCoupon = function($allCoupon)use($system_coupon, $money, $store_id, $coupon_list){
                foreach ($system_coupon as $value) {
                    if(empty($allCoupon) && $value['order_money']>$money){
                        continue;
                    }
                    $c_store_ids = $value['store_id']?explode(',',$value['store_id']):[];
                    if($store_id&&empty(array_intersect($store_id,$c_store_ids))){
                        continue;
                    }
                    if($value['is_use'] == '0' && $value['status'] == '1'){
                        $temp = [
                            'title' => $value['name'],
                            'rule' => $value['discount_des'],
                            'limit_date' => $value['limit_date'],
                            'money' => $value['discount'],
                            'get' => $value['is_get'],
                            'type' => 'merchant',
                            'coupon_id' => $value['coupon_id'],
                            'limit_num' => $value['limit'],
                            'limit_day' => '距离到期还有'.intval(($value['end_time']-time())/24/3600).'天'
                        ];
                        $coupon_list[] = $temp;
                    }
                }
                return $coupon_list;
            };
            if($params['all_coupon']){
               return [
                   'can_use_coupon' => $filterCoupon(0),
                   'all_coupon' => $filterCoupon(1),
               ];
            }
            return $filterCoupon(0);
        }
        return $coupon_list;
    }

    /**
     * 领取优惠券
     * @param $type
     * @param $uid
     * @param $coupon_id
     * @return int[]|\think\response\Json
     * @throws \Exception
     */
    public function receive($type,$uid='',$coupon_id){
        if(empty($type) || empty($coupon_id)){
            return api_output_error(1001, '必填参数未传递!');
        }
        if(!is_array($coupon_id)){
            $coupon_id = array($coupon_id);
        }
        try {
            if($type == 'system'){
                foreach ($coupon_id as $item){
                    $receive = (new SystemCouponService)->receiveCoupon($uid, $item);
                    fdump_api($receive,'coupon/appoint_coupon',1);
                }

            } elseif($type == 'merchant'){
                foreach ($coupon_id as $item){
                    $receive = (new MerchantCouponService)->receiveCoupon($uid, $item);
                    fdump_api($receive,'coupon/appoint_coupon',1);
                }
            }
            return true;
        }catch (\Exception $e){
            throw new \think\Exception(L_($e->getMessage()));
        }
    }

    /**
     * 获取核销券二维码，条形码
     * @param $params
     * @return array|null
     * @throws \think\Exception
     */
    public function getCodeInfo($params)
    {
        $type = $params["type"];
        $coupon_id = $params["coupon_id"];
        $user_had_id = $params["user_had_id"];
        $uid = $params["uid"];
        if ($type == 'offline_enjoy_coupon') {
            $had_coupon = (new EnjoyUserCoupon())->where(['id' => $user_had_id, 'coupon_id' => 0])->findOrEmpty()->toArray();
            if (!$had_coupon) {
                throw new \think\Exception(L_('优惠券不存在'));
            }
            $code = $had_coupon['str_mid'];
            //生成二维码
            $qrCode = (new MallStaffCartService())->getQrCode($code, $code, 'offline_enjoy_coupon');
            $coupon_price_txt = '立减' . $had_coupon['dec_value'] . '元';
            $data = [
                'qrCode' => $qrCode,
                'barCode' => '',
                'date_txt' => date('Y年m月d日', strtotime($had_coupon['start_date'])) . '-' . date('Y年m月d日', strtotime($had_coupon['end_date'])),
                'coupon_name' => $had_coupon['coupon_name'],
                'coupon_price_txt' => $coupon_price_txt,
                'code' => $code,
                'has_code' => 1,
            ];
            return $data;
        }
        if ($type == 'system') {
            $had_mode = new SystemCouponHadpull();
            $coupon_mode = new SystemCoupon();
        } else {
            $had_mode = new CardNewCouponHadpull();
            $coupon_mode = new CardNewCoupon();
        }
        $coupon = $coupon_mode->where(['coupon_id' => $coupon_id])->findOrEmpty()->toArray();
        $had_coupon = $had_mode->where(['coupon_id' => $coupon_id, 'id' => $user_had_id, 'uid' => $uid])->findOrEmpty()->toArray();
        if (!$coupon) {
            throw new \think\Exception(L_('优惠券不存在'));
        }
        if (!$had_coupon || $coupon['store_direct_verification'] != 1) {
            return ['has_code' => 0];
        }
        $data = null;
        if ($coupon['store_direct_verification']) {
            $code = $had_coupon['code'];
            if (!$code) {
                //生成序列号
                $code = ($type == 'system' ? 'S' : 'M') . $had_coupon['id'] . date('ymd') . substr(microtime(), 2, 9 - strlen($had_coupon['id']));
                $had_mode->where(['id' => $user_had_id])->save(['code' => $code]);
            }
            //生成二维码
            $qrCode = (new MallStaffCartService())->getQrCode($code, $code, 'coupon');
            //生成条形码
            $barCode = $this->getBarCode($code, $code);
            if ($coupon['is_discount']) {
                $coupon_price_txt = '打' . $coupon['discount_value'] . '折';
            } else {
                $coupon_price_txt = '立减' . $coupon['discount'] . '元' . ($coupon['order_money'] ? '(满' . $coupon['order_money'] . '可用)' : '');
            }
            $data = [
                'qrCode' => $qrCode,
                'barCode' => $barCode,
                'date_txt' => date('Y年m月d日', $had_coupon['receive_time']) . '-' . date('Y年m月d日', $coupon['end_time']),
                'coupon_name' => $coupon['name'],
                'coupon_price_txt' => $coupon_price_txt,
                'code' => $code,
                'has_code' => 1,
            ];
        }
        return $data;
    }

    /**
     * 获取条形码
     */
    private function getBarCode($code, $text, $folder = 'coupon')
    {
        $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
        $base64Image = base64_encode($generator->getBarcode($text, $generator::TYPE_CODE_128, 1, 100));
        $path = request()->server('DOCUMENT_ROOT') . '/runtime/' . $folder . '/barcode/';
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        $base64Image = 'data:image/png;base64,' . $base64Image;
        base64_to_img($path, $code, $base64Image, 'png');
        return cfg('site_url') . '/runtime/' . $folder . '/barcode/' . $code . '.png';
    }
}