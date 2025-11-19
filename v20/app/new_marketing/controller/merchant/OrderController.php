<?php
/**
 * 商家后台订单
 * Author: hengtingmei
 * Date Time: 2021/08/31 13:48
 */

namespace app\new_marketing\controller\merchant;
use app\common\model\service\ConfigService;
use app\merchant\controller\merchant\AuthBaseController;
use app\merchant\model\service\MerchantService;
use app\new_marketing\model\db\MerchantCategory;
use app\new_marketing\model\service\ClassPriceService;
use app\new_marketing\model\service\MarketingOrderService;
use app\new_marketing\model\service\MarketingPackageRegionService;
use app\new_marketing\model\service\MarketingStoreService;

class OrderController extends AuthBaseController
{
    /**
     * 获得订单列表
     */
    public function getOrderList()
    {
        $param['mer_id'] = $this->merchantUser['mer_id'];
        $param['page'] = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $list = (new MarketingOrderService())->getOrderList($param);
        return api_output(0, $list);
    }

    /**
     * 获得订单详情
     */
    public function getOrderDetail()
    {
        $param['mer_id'] = $this->merchantUser['mer_id'];
        $param['order_id'] = $this->request->param('order_id', 0, 'intval');
        $list = (new MarketingOrderService())->getOrderDetail($param);
        return api_output(0, $list);
    }

    /**
     * 获取购买套餐/分类店铺支付方式
     */
    public function pay_check()
    {
        $data = [
            [
                'label' => '商家余额',
                'value' => 'balance'
            ],
            [
                'label' => '微信支付',
                'value' => 'weixin'
            ],
            [
                'label' => '支付宝支付',
                'value' => 'alipay'
            ]
        ];
        return api_output(0, $data, 'success');
    }

    /**
     * 套餐/分类店铺下单
     */
    public function pay()
    {
        $pay_check = $this->request->param('pay_check', '', 'trim');//支付方式:balance=商家余额,weixin=微信支付,alipay=支付宝支付
        $id = $this->request->param('id', 0, 'intval');//区域套餐ID/区域分类店铺ID
        $pay_money = $this->request->param('pay_money', 0);//支付金额
        $discount_money = $this->request->param('discount_money', 0);//优惠金额
        $year = $this->request->param('year', 1, 'intval');//支付年限
        $store_num = $this->request->param('store_num', 1, 'intval');//购买套餐店铺总数量(购买套餐需要)
        $num = $this->request->param('num', 1, 'intval');//支付套餐/店铺数量
        $type = $this->request->param('type', 1, 'intval');//1=购买套餐,2=购买分类店铺
        $is_mobile = $this->request->param('is_mobile', 0, 'intval');//1=手机端
        $openid = $this->request->param('openid', 0, 'string');//1=手机端
        
        if($openid){
            $_SESSION['openid'] = $openid;
        }
        if (!$id) {
            return api_output_error(1003, '区域套餐ID不存在');
        }
//        $pay_method = (new ConfigService())->get_pay_method();
//        if(empty($pay_method)){
//            return api_output_error(1003, '系统管理员没开启任一一种支付方式');
//        }
//        if(empty($pay_method[$pay_check])){
//            return api_output_error(1003, '您选择的支付方式不存在，请更新支付方式！');
//        }
        $orderData = [
            'orderid' => time().rand(10,99).sprintf("%06d",$this->merId),
            'mer_id' => $this->merId,
            'pack_name' => '',
            'buy_num' => $num,
            'pay_years' => $year,
            'paid' => 0,
            'pay_type' => $pay_check,
            'discount_money' => $discount_money,
            'total_price' => $pay_money,
            'add_time' => time(),
        ];
        if ($type == 2) {
            $orderData['class_region_id'] = $id;
            $orderData['pack_name'] = (new ClassPriceService())->getClassName(['id' => $id]);
            $orderData['store_num'] = $num;
        } else {
            $orderData['pack_region_id'] = $id;
            $orderData['pack_name'] = (new MarketingPackageRegionService())->getWhereData(['a.id' => $id], 'b.name')['name'] ?? '';
            $orderData['store_num'] = $store_num;
        }
        try {
            $orderData['order_id'] = (new MarketingOrderService())->add($orderData);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        $orderData['order_type'] = 'newmarket';
        if ($pay_check == 'balance') {
            if ($this->merchantUser < $orderData['total_price']) {
                return api_output_error(1003, '商家余额不足');
            }
            try {
                $data = [
                    'orderData' => $orderData,
                    'id' => $id,
                    'type' => $type,
                    'num' => $num,
                ];
                (new MarketingOrderService())->after_pay($data);
                $returnArr = array(
                    'info' => '',
                    'orderid' => $orderData['orderid'],
                    'pay_ok' => 1//商家余额支付
                );
                return api_output(0, $returnArr, 'success');
            } catch (\Exception $e) {
                return api_output_error(1003, $e->getMessage());
            }
        }
        $orderData['order_id'] = $orderData['orderid'];
        $orderData['order_name'] = $orderData['pack_name'];
        $param = [
            'orderData' => $orderData,
            'pay_money' => $pay_money,
            'pay_check' => $pay_check,
            'merchantUser' => $this->merchantUser,
            'is_mobile' => $is_mobile
        ];
        $go_pay_param = invoke_cms_model('Pay/go_pay_v20', ['name' => $param]);
        $go_pay_param = $go_pay_param['retval'];
        if (empty($go_pay_param['error'])) {
            if ($pay_check == 'weixin') {
                $returnArr = array(
                    'orderid' => $orderData['order_id'],
                    'pay_ok' => 2//微信支付
                );
                if(isset($go_pay_param['qrcode']) && $go_pay_param['qrcode']){
                    $returnArr['info'] = cfg('site_url') . '/index.php?c=Recognition&a=get_own_qrcode&qrCon=' . $go_pay_param['qrcode'];
                }elseif (isset($go_pay_param['weixin_param']) && !empty($go_pay_param['weixin_param'])) {
                    if ($go_pay_param['is_own']) {
                        $returnArr['hidScript'] = $go_pay_param['hideScript'];
                    }
                    $returnArr['weixin_param'] = $go_pay_param['weixin_param'];
                }
                return api_output(0, $returnArr, 'success');
            }elseif ($pay_check == 'weixinh5') {
                $returnArr['jump'] = 'mweb_url';
                $returnArr['url'] =$go_pay_param['mweb_url'];
            }else if ($pay_check == 'alipay' && !empty($go_pay_param['pay_url'])) {
                $returnArr = array(
                    'info' => $go_pay_param['pay_url'],
                    'orderid' => $orderData['order_id'],
                    'pay_ok' => 3//支付宝支付
                );
                return api_output(0, $returnArr, 'success');
            }
        }
        return api_output_error(1003, $go_pay_param['msg'] ?? '支付有误，请重试');
    }

    /**
     * 查询订单支付状态
     */
    public function searchPayStatus() {
        $orderid = $this->request->param('orderid','');
        try {
            $orderData = (new MarketingOrderService())->getOne(['orderid' => $orderid]) ?? [];
            if (!$orderData) {
                return api_output_error(1003, '订单不存在');
            }
            return api_output(0, ['paid' => $orderData['paid']], 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    
    
    /**
     * 获得续费订单详情
     */
    public function getPayInfo()
    {
        $param['mer_id'] = $this->merchantUser['mer_id'];
        $param['store_id'] = $this->request->param('store_id', 0, 'intval');
        $param['years'] = $this->request->param('years', 1, 'intval');
        $list = (new MarketingOrderService())->getRenewalOrderPayInfo($param);
        return api_output(0, $list);
    }

    /**
     * 保存续费订单详情
     */
    public function savePayInfo()
    {
        $param['mer_id'] = $this->merchantUser['mer_id'];
        $param['store_id'] = $this->request->param('store_id', 0, 'intval');
        $param['years'] = $this->request->param('years', 0, 'intval');
        $param['pay_type'] = $this->request->param('pay_type', 0, 'intval');// 支付方式
        $list = (new MarketingOrderService())->savePayInfo($param);
        return api_output(0, $list);
    }
    
}
