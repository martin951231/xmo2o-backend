<?php
/**
 * 店员优惠券service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/08/29 17:35
 */

namespace app\storestaff\model\service;

use app\common\model\db\CardNewCoupon;
use app\common\model\db\CardNewCouponHadpull;
use app\common\model\db\Merchant;
use app\common\model\db\SystemCoupon;
use app\common\model\db\SystemCouponHadpull;
use app\common\model\db\SystemCouponStore;
use app\common\model\service\coupon\MerchantCouponService;
use app\common\model\service\coupon\SystemCouponService;
use app\merchant\model\db\MerchantMoneyList;
use app\merchant\model\service\MerchantStoreService;
use app\storestaff\model\db\CouponVerifyRecord;
use think\facade\Db;

class CouponService {
    public $store = null;
    public function __construct($staff)
    {
        $this->store = (new MerchantStoreService())->getStoreByStoreId($staff['store_id']);
    }

    /**
     * 发送短信验证吗
     * @param $param array 登录信息
     * @return array
     */
    public function getCouponList($param){

        if(empty($param['uid']) || empty($param['type'])  || empty($param['business'])){
            throw new \think\Exception(L_("缺少参数!"), 1001);
        }
        $couponList = [];
        $orderInfo = [
            'can_coupon_money' => $param['money'],
            'business' => $param['business'],
            'store_id' => $this->store['store_id'],
        ];
        if($param['type'] == 'system'){
            $couponList = (new SystemCouponService())->getAvailableCoupon( $param['uid'], $orderInfo);
        }elseif($param['type'] == 'merchant'){ // 会员卡于优惠不同享
            if($param['has_merchant_discount'] >0){
                $orderInfo['merchant_card'] = true;
            }
            $couponList = (new MerchantCouponService())->getAvailableCoupon($param['uid'], $this->store['mer_id'],$orderInfo);
        }

        $returnArr = [];
        $couponList = (new SystemCouponService())->formatDiscount($couponList);
        $returnArr = [];
        foreach ($couponList as $_coupon){
            $temp = [];
            $temp['had_id'] = $_coupon['id'];
            $temp['discount'] = $_coupon['discount'];
            $temp['order_money'] = $_coupon['order_money'];
            $temp['end_time'] = date('Y-m-d',$_coupon['end_time']);
            $temp['discount_title'] = $_coupon['discount_title'];
            $temp['discount_des'] = $_coupon['discount_des'];
            $temp['is_discount'] = $_coupon['is_discount'];
            $returnArr[] = $temp;
        }
        return $returnArr;
    }

    /**
     * 获取扫描的核销码/条形码信息
     * @param $params
     * @return array
     * @throws \think\Exception
     */
    public function getCodeInfo($params){
        $code = $params['code'];
        $staff_user = $params['staff_user'];
        if(!$code){
            throw new \think\Exception(L_('参数错误！'));
        }
        $type = 'system';     
	//查询是否为平台券
	$had_coupon = (new SystemCouponHadpull())->where(['code'=>$code])->findOrEmpty()->toArray();
	if(!$had_coupon){
		$had_coupon = (new CardNewCouponHadpull())->where(['code'=>$code])->findOrEmpty()->toArray();
		$type = 'mer';
	}
  
        
        if(!$had_coupon){
            throw new \think\Exception(L_('序列号不存在！'));
        }
        if($type=='system'){
            $coupon_mode = new SystemCoupon();
        }else{
            $coupon_mode = new CardNewCoupon();
        }
        $coupon = $coupon_mode->where(['coupon_id'=>$had_coupon['coupon_id']])->findOrEmpty()->toArray();
        if(!$coupon){
            throw new \think\Exception(L_('优惠券不存在！'));
        }
        if((strlen($code)!=18&&!$coupon['store_direct_verification'])||(strlen($code)==18&&!$coupon['is_paper'])){
            throw new \think\Exception(L_('该券不支持核销！'));
        }
        $this->checkCoupon($type,$coupon,$staff_user['store_id']);
        $status = 0;
        $status_txt = '核销';
        if($coupon['end_time'] < time()){
            $status_txt = '已过期';
            $status = 2;
        }
        if($had_coupon['is_use']){
            $status_txt = '已核销';
            $status = 1;
        }
        if($coupon['is_discount']){
            $coupon_price_txt = '打'.$coupon['discount_value'].'折';
        }else{
            $coupon_price_txt = '立减'.$coupon['discount'].'元'.($coupon['order_money']?'(满'.$coupon['order_money'].'可用)':'');
        }
        $data = [
            'status' => $status,
            'is_discount' => $coupon['is_discount'],
            'discount' => $coupon['is_discount'] ? $coupon['discount_value'] : $coupon['discount'],
            'status_txt' => $status_txt,
            'date_txt' => date('Y年m月d日', (strlen($code)==18?$coupon['start_time']:$had_coupon['receive_time'])) . '-' . date('Y年m月d日', $coupon['end_time']),
            'coupon_name' => $coupon['name'],
            'coupon_price_txt' => $coupon_price_txt,
            'code' => $code,
            'img' => $coupon['img']?replace_file_domain($coupon['img']):'',
            'type' => $type,
        ];
        return $data;
    }

    /**
     * 核销优惠券
     */
    public function verifyCoupon($params){
        $code = $params['code'];
        $type = $params['type'];
        $staff_user = $params['staff_user'];
        $had_mode = $type=='system'?(new SystemCouponHadpull()):(new CardNewCouponHadpull());
        $had_coupon = $had_mode->where(['code'=>$code])->findOrEmpty()->toArray();
        if(!$had_coupon){
            throw new \think\Exception(L_('优惠券不存在！'));
        }
        $coupon_mode = $type=='system'?(new SystemCoupon()):(new CardNewCoupon());
        $coupon = $coupon_mode->where(['coupon_id'=>$had_coupon['coupon_id']])->findOrEmpty()->toArray();
        if(!$coupon){
            throw new \think\Exception(L_('优惠券不存在！'));
        }
        if((strlen($code)!=18&&!$coupon['store_direct_verification'])||(strlen($code)==18&&!$coupon['is_paper'])){
            throw new \think\Exception(L_('该券不支持核销！'));
        }
        if($had_coupon['is_use']){
            throw new \think\Exception(L_('该券已核销！'));
        }
        if($coupon['end_time'] < time()){
            throw new \think\Exception(L_('该券已过期！'));
        }
        $this->checkCoupon($type,$coupon,$staff_user['store_id']);
        $sys_money = $mer_money = 0;
        $coupon['discount'] = (float)$coupon['discount'];
        if($type=='system'){
            $coupon['plat_money'] = (float)$coupon['plat_money'];
            $coupon['merchant_money'] = (float)$coupon['merchant_money'];
            if((float)$coupon['plat_money']||(float)$coupon['merchant_money']){
                $sys_money = $coupon['plat_money'];
                $mer_money = $coupon['merchant_money'];
            }else{
                $sys_money = $coupon['discount'];
            }
        }else{
            $mer_money = $coupon['discount'];
        }
        if($coupon['is_discount']){
            $mer_money = 0;
            $sys_money = 0;
        }
        $time = time();
        Db::startTrans();
        try {
            $saveData = [
                'coupon_id' => $coupon['coupon_id'],
                'had_id' => $had_coupon['id'],
                'code' => $code,
                'name' => $coupon['name'],
                'order_money' => $coupon['order_money'],
                'discount' => $coupon['discount'],
                'type' => $type,
                'sys_money' => $sys_money,
                'mer_money' => $mer_money,
                'staff_user_name' => $staff_user['name'],
                'verify_time' => $time,
                'staff_uid' => $staff_user['id'],
                'store_id' => $staff_user['store_id'],
                'mer_id' => $staff_user['mer_id'],
                'is_discount' => $coupon['is_discount']?1:0,
                'discount_value' => $coupon['is_discount']?$coupon['discount_value']:0,
                'uid' => $had_coupon['uid']??0,
                'is_paper' => $coupon['is_paper'],
            ];
            //添加店员核销记录
            (new CouponVerifyRecord())->save($saveData);
            $had_save_data = ['is_use'=>1,'use_time'=>time()];
            if(strlen($code)==18){
                $had_save_data['staff_user_name'] = $staff_user['name'];
                $had_save_data['staff_uid'] = $staff_user['id'];
            }
            $had_mode->where(['code'=>$code])->save($had_save_data);
            //给商家增加金额
            if($sys_money>0 && empty($params['use_coupon'])){
                $money_list_data = [
                    'type' => 'system_coupon_verify_coupon',
                    'mer_id' => $staff_user['mer_id'],
                    'store_id' => $staff_user['store_id'],
                    'order_id' => $code,
                    'num' => 1,
                    'money' => $sys_money,
                    'system_take' => 0,
                    'percent' => 0,
                    'total_money' => $sys_money,
                    'income' => 1,
                    'use_time' => $time,
                    'desc' => '店铺直接核销平台优惠券商家获得金额'
                ];
                (new Merchant())->where(['mer_id'=>$staff_user['mer_id']])->inc('money',$sys_money)->update();
                $money_list_data['now_mer_money'] = (new Merchant())->where(['mer_id'=>$staff_user['mer_id']])->value('money');
                (new MerchantMoneyList())->save($money_list_data);
            }
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            throw new \think\Exception(L_($e->getMessage()));
        }
        return true;
    }

    /**
     * 优惠券核销记录
     * @param $params
     * @return array|mixed
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \think\Exception
     */
    public function getCouponVerifyRecord($params){
        $is_export = $params['is_export'];
        $page = $is_export?0:$params['page'];
        $pageSize = $params['pageSize'];
        $keywords = $params['keywords'];
        $type = $params['type'];
        $start_time = $params['start_time'];
        $end_time = $params['end_time'];
        $staff_user = $params['staff_user'];
        $store_id = $staff_user['store_id'];
        $mer_id = $staff_user['mer_id'];
        $where = [
            ['store_id','=',$store_id],
            ['mer_id','=',$mer_id],
        ];
        if($keywords){
            $where[] = ['name','like','%'.$keywords.'%'];
        }
        if($type){
            $where[] = ['type','=',$type];
        }
        if($start_time&&$end_time){
            $start_time = strtotime($start_time);
            $end_time = strtotime($end_time.' 23:59:59');
            $where[] = ['verify_time','between',[$start_time,$end_time]];
        }
        $list = (new CouponVerifyRecord())->getList($where,$page,$pageSize);
        $data = $page>0?$list['data']:$list;
        foreach ($data as &$item){
            $item['verify_time'] = date('Y-m-d H:i:s',$item['verify_time']);
            if($item['is_discount']){
                $coupon_price_txt = '打'.$item['discount_value'].'折';
            }else{
                $coupon_price_txt = '立减'.$item['discount'].'元';
            }
            $item['coupon_price_txt'] = $coupon_price_txt;
            $item['type_txt'] = $item['type']=='system'?'平台券':'商家券';
        }
        if($page>0){
            $list['data'] = $data;
            return $list;
        }else{
            $xlsCell = [
                ['name', '优惠券名称'],
                ['coupon_price_txt', '优惠金额'],
                ['type_txt', '类型'], 
                ['sys_money', '平台补贴金额'],
                ['mer_money', '商家补贴金额'],
                ['staff_user_name', '操作人员'],
                ['verify_time', '核销时间'],
            ];
            $fileName = date("Y-m-d", time()).'核销记录';
            $exportResult = export_excel('核销记录', $xlsCell, $data, $fileName);
            return $exportResult;
        }
    }

    /**
     * 校验店铺是否可以核销
     * @param $type //system-平台，merchant：商家
     * @param $coupon //优惠券列表
     * @param $store_id //店员所在店铺id
     */
    public function checkCoupon($type,$coupon,$store_id){
        if($type=='system'){
            if($coupon['use_type']==1){
                $store_ids = (new SystemCouponStore())->where(['coupon_id'=>$coupon['coupon_id']])->column('store_id');
                if(!in_array($store_id,$store_ids)){
                    throw new \think\Exception(L_('当前店铺不能核销！'));
                }
            }
        }else{
            $store_ids = explode(',',$coupon['store_id']);
            if(!in_array($store_id,$store_ids)){
                throw new \think\Exception(L_('当前店铺不能核销！'));
            }
        }
        return true;
    }
}