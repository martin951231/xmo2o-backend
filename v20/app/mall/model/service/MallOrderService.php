<?php
/**
 * MallOrderService.php
 * 订单
 * Create on 2020/9/10 17:36
 * Created by zhumengqun
 */

namespace app\mall\model\service;

use app\common\model\db\DeliveryLogistics;
use app\common\model\db\MiaomaiOrderRefundUpload;
use app\common\model\db\MiaomaiOrderUpload;
use app\common\model\db\SystemOrder;
use app\common\model\db\User;
use app\common\model\service\AreaService;
use app\common\model\service\export\ExportService as BaseExportService;
use app\common\model\service\live\LiveGoodService;
use app\common\model\service\order\SystemOrderService;
use app\common\model\service\SingleFaceService;
use app\common\model\service\user\UserNoticeService;
use app\common\model\service\UserService;
use app\deliver\model\service\DeliverSupplyService;
use app\mall\model\db\Area;
use app\mall\model\db\Express;
use app\mall\model\db\MallFullGiveGiftSku;
use app\mall\model\db\MallGoods;
use app\mall\model\db\MallNewGroupOrder;
use app\mall\model\db\MallNewPeriodicDeliver;
use app\mall\model\db\MallNewPeriodicPurchase;
use app\mall\model\db\MallNewPeriodicPurchaseOrder;
use app\mall\model\db\MallOrder;
use app\mall\model\db\MallOrderDelivery;
use app\mall\model\db\MallOrderDetail;
use app\mall\model\db\MallOrderLog;
use app\mall\model\db\MallOrderRefund;
use app\mall\model\db\MallOrderRefundDetail;
use app\mall\model\db\MallOrderRemind;
use app\mall\model\db\MallPrepareOrder;
use app\mall\model\db\MerchantStore;
use app\mall\model\db\MerchantStoreMall;
use app\mall\model\db\PayOrderInfo;
use app\mall\model\db\ShopGoodsBatchFailLog;
use app\mall\model\db\ShopGoodsBatchLog;
use app\mall\model\service\activity\MallActivityService;
use app\mall\model\service\activity\MallFullGiveGiftSkuService;
use app\mall\model\service\activity\MallNewPeriodicPurchaseOrderService;
use app\mall\model\service\order_print\PrintHaddleService;
use app\mall\model\service\third_order_deliver\DeliverApiService;
use app\mall\validate\OrderDelivery;
use app\merchant\model\db\Merchant;
use app\merchant\model\db\MerchantMoneyList;
use app\merchant\model\service\MerchantMoneyListService;
use app\pay\model\service\PayService;
use app\shop\model\db\ShopGoods;
use miaomai\Miaomaiyun;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\facade\Db;
use Tools\Spread;

require_once '../extend/phpqrcode/phpqrcode.php';

class MallOrderService
{
    const SEND_TIME = 24 * 60;//强制发货时间，如果未发货，则取消订单（单位：分钟）
    const CONFIRM_TIME = 7 * 24 * 60;//强制确认收货时间，如果未收货，则自动确认（单位：分钟）
    
    const EXPRESS_TYPE_1 = 1;//电子面单发货
    const EXPRESS_SEND_TYPE_1 = 1;//请求类型 1发货
    
    public static $order_status = [
        '0' => '待支付',
        '1' => '待支付',//尾款待支付
        '10' => '待发货',
        '11' => '备货中',
        '12' => '已顺延',
        '13' => '待成团',
        '14' => '备货完成待配送',
        '15' => '分配订单给骑手',
        '20' => '已发货',
        '21' => '等待骑手接单',
        '22' => '骑手接单',
        '23' => '骑手到店',
        '24' => '骑手取货',
        '30' => '已收货',//用户端的已收货对应的平台 商家  店铺的已完成
        '31' => '已自提',//用户端确认自提
        '32' => '已核销',//店员确认核销
        '33' => '骑手已送达',//骑手确认送达
        '40' => '已完成',
        '50' => '已取消',
        '51' => '超时取消',
        '52' => '用户取消',
        '60' => '申请售后',
        '70' => '已退款',
    ];

    public function __construct()
    {
        $this->MallOderModel = new MallOrder();
        $this->MallOderDatail = new MallOrderDetail();
        $this->MallOrderLog = new MallOrderLog();
        $this->mallActivityService = new MallActivityService();
        $this->mallOrderRefundService = new MallOrderRefundService();
        $this->MallOrderRemind = new MallOrderRemind();
    }

    /**
     * 通过id获取订单信息
     * @param $id
     * @return array
     */
    public function getOne($id)
    {
        $one = $this->MallOderModel->getOne($id);
        return $one;
    }

    public function area()
    {

    }

    /**
     * 获取所有店铺
     * @return array|string
     */
    public function getStores()
    {
        $storeService = new MerchantStoreService();
        $arr = $storeService->getStoreList1([]);
        if (!empty($arr)) {
            return $arr;
        } else {
            return '';
        }
    }

    /**
     * 获取某个商家的所有店铺
     * @return array|string
     */
    public function getStores1($mer_id)
    {
        if (empty($mer_id)) {
            throw new \think\Exception('缺少mer_id参数');
        }
        $storeService = new MerchantStoreService();
        $arr = $storeService->getStoreList1(['mer_id' => $mer_id]);
        if (!empty($arr)) {
            return $arr;
        } else {
            return '';
        }
    }

    /**
     * 获取所有商家
     * @return array|string
     */
    public function getMers()
    {
        $merService = new MerchantService();
        $arr = $merService->getMerList1([]);
        if (!empty($arr)) {
            return $arr;
        } else {
            return '';
        }

    }

    /**
     * 按条件获取列表
     * @param $param
     * @return array
     */
    public function searchOrders($param, $type = 0)
    {
        $style = 0; //列表调用
        $now_status = $param['status'];
        if(($now_status==1 || $now_status==8) && $param['search_time_type']=='refund_time' && !empty($param['begin_time']) && !empty($param['end_time'])){
            $now_status=8;
            $param['status']=8;
        }
        $where_all = $this->getWhereCopy($param, $style);

        $param['provinceId'] = $param['cityId'] = $param['areaId'] = $param['streetId'] = 0;

        $field = 'o.order_id,o.order_no,o.mer_id,o.store_id,o.create_time,o.money_online_pay,o.online_pay_type,o.address,o.express_send_time,o.money_freight,o.username,o.phone,o.express_style,o.send_time,o.status,o.clerk_remark,o.money_total,o.money_real,o.discount_system_coupon,o.discount_merchant_coupon,o.discount_merchant_card,o.discount_system_level,o.discount_clerk_money,o.discount_act_money,o.money_score,o.money_system_balance,o.money_merchant_balance,o.money_merchant_give_balance,o.money_qiye_balance,o.goods_activity_type,o.activity_type,o.activity_id,o.pay_orderno,o.pre_pay_orderno,o.province_id,o.city_id,s.name as store_name,m.name as mer_name,u.nickname,u.phone as wx_phone,o.verify_status,o.employee_score_pay,o.employee_balance_pay,o.money_refund';
        if ($param['re_type'] == 'platform') {

            //系统后台区域管理员只能查看区域内店铺的订单
                //获取管理员是否是区域管理员以及管理的区域
            if(isset($param['userInfo']['open_admin_area']) && $param['userInfo']['open_admin_area'] == 1 && isset($param['userInfo']['area_id']) && $param['userInfo']['area_id']){
                $areaIdInfo = $param['userInfo']['area_id'];
                $streetId = $param['userInfo']['street_id']??0;
                //查询区域下所有区域id
                $now_area = (new AreaService())->getAreaAndOneCircle($areaIdInfo,'area_type,area_pid');
                if ($now_area['area_type'] == 3) {
                    $param['areaId'] = $areaIdInfo;
                    $param['cityId'] = $now_area['area_pid'];
                    $tempArea = (new AreaService())->getAreaAndOneCircle($param['cityId'],'area_pid');
                    $param['provinceId'] = $tempArea['area_pid'];
                } elseif($now_area['area_type'] == 2) {
                    $param['cityId'] = $areaIdInfo;
                    $param['provinceId'] = $now_area['area_pid'];
                } elseif ($now_area['area_type'] == 1) {
                    $param['provinceId'] = $areaIdInfo;
                }
                if ($param['provinceId']) {
                    $where_all[] = ['s.province_id','=',$param['provinceId']];
                }
                if ($param['cityId']) {
                    $where_all[] = ['s.city_id','=',$param['cityId']];
                }
                if ($param['areaId']) {
                    $where_all[] = ['s.area_id','=',$param['areaId']];
                }
                if($streetId){
                    $param['streetId'] = $streetId;
                    $where_all[] = ['s.street_id','=',$streetId];
                }
            }
        } elseif ($param['re_type'] == 'merchant') {
        } elseif ($param['re_type'] == 'storestaff') {
        } else {
            throw new \think\Exception('re_type参数错误');
        }
        $field .= ',o.remark';
        $refundDetailService = new MallOrderRefundDetail();
        $payService = new PayService();
        $prepreOrder = new MallPrepareOrder();
        $arr = [];
        if (!in_array($now_status,[7,8])){
            $arr = $this->MallOderModel->getListInfo($field, $where_all, $param['page'], $param['pageSize']);
        }
        //获取各种和
        $style = 4;//获取各种统计 不选状态，不包含周期购，退款，售后，取消

        $collect_where = $this->getWhere($param, $style);
        if ($param['provinceId']) {
            $collect_where[] = ['s.province_id','=',$param['provinceId']];
        }
        if ($param['cityId']) {
            $collect_where[] = ['s.city_id','=',$param['cityId']];
        }
        if ($param['areaId']) {
            $collect_where[] = ['s.area_id','=',$param['areaId']];
        }
        if($param['streetId']){
            $collect_where[] = ['s.street_id','=',$param['streetId']];
        }
        $collect_num = $this->getCollect($collect_where, $param);

        $list_refud=(new MallOrder())->getRudTradeNew($collect_where);
        $ref_order=[];
        if(!empty($list_refud)){
            $refund_money=0;
            foreach ($list_refud as $k=>$v){
                $ref_order[]=$v['order_no'];
                $refund_money+=$v['refund_money'];
            }
            //$where_ref=[['order_id','in',$ref_order]];
            if(isset($collect_num['tk_money'])){
                $collect_num['tk_money']=get_number_format($refund_money);
            }
        }
        $arrs = array();
        if (!empty($arr)) {
            foreach ($arr as $k => $item) {
                $arr[$k]['username'] = $item['username']?:$item['nickname'];
                $arr[$k]['phone'] = $item['phone']?:$item['wx_phone'];
                // 周期购的处理 期数
                if ($item['goods_activity_type'] == 'periodic') {
                    $arrs[] = $arr[$k];
                    //根据订单id获取所有的子订单信息
                    $periodic = $this->mallActivityService->returnNowPeriodicOrderAndActBefore($item['order_id'], $now_status);//邓远辉提供的接口
                    if (!empty($periodic)) {
                        foreach ($periodic as $kk => $vv) {
                            $periodic[$kk]['button'] = [
                                'take_btn' => 0,
                                'hoseman_btn' => 0,
                                'clerk_btn' => 0,
                                'express_btn' => 0,
                                'logistics_btn' => 0,
                                'postpone_btn' => 0,
                                'trajectory_btn' => 0,
                                'agree_refund_btn' => 0,
                                'refuse_refund_btn' => 0
                            ];
                            if ($vv['is_complete'] == 10) {
                                if ($param['re_type'] == 'storestaff') {
                                    $periodic[$kk]['button']['take_btn'] = 1;
                                    $periodic[$kk]['button']['postpone_btn'] = 1;
                                }
                            } elseif ($vv['is_complete'] == 11) {
                                if ($item['express_style'] == 1) {
                                    $periodic[$kk]['button']['hoseman_btn'] = 1;
                                } elseif ($item['express_style'] == 3) {
                                    $periodic[$kk]['button']['clerk_btn'] = 1;
                                } elseif ($item['express_style'] == 2) {
                                    $periodic[$kk]['button']['express_btn'] = 1;
                                } else {
                                    throw new \think\Exception('发货方式错误');
                                }
                            } elseif ($vv['is_complete'] == 20) {
                                if ($item['express_style'] == 2) {
                                    $periodic[$kk]['button']['logistics_btn'] = 1;
                                } elseif ($item['express_style'] == 1) {
                                    $periodic[$kk]['button']['trajectory_btn'] = 1;
                                }
                            } elseif ($vv['is_complete'] == 30) {
                                if ($item['express_style'] == 2) {
                                    $periodic[$kk]['button']['logistics_btn'] = 1;
                                } elseif ($item['express_style'] == 1) {
                                    $periodic[$kk]['button']['trajectory_btn'] = 1;
                                }
                            }
                            $periodic[$kk]['periodic_date'] = $vv['periodic_date'] ? date('Y-m-d H:i', $vv['periodic_date']) : '';
                            $periodic[$kk]['delivery'] = $this->orderDeliveryList($vv['order_id'], $vv['purchase_order_id']);
                        }
                        $arr[$k]['periodic_info'] = $periodic;
                        $arr[$k]['periodic_order_id'] = $periodic[$kk]['purchase_order_id'];
                    }
                    $pWhere = [['is_complete', '<>', 2], ['order_id', '=', $item['order_id']]];
                    $arr[$k]['periodic_num'] = (new MallNewPeriodicPurchaseOrder())->getPeriodicOrderNum($pWhere);
                }else{
                    $arr[$k]['delivery'] = $this->orderDeliveryList($item['order_id']);
                }
                $arr[$k]['verify_text'] = ($item['express_style']==3&&($arr[$k]['status'] >= 30 && $arr[$k]['status'] <= 40)) ? $this->MallOderModel->getVerifyText($arr[$k]['verify_status']) : '';
            }
        }
        if ($now_status == 7 || $now_status == 8) {
            $arr = $this->getTkInfo($now_status, $param,0);
        }
        if (!empty($arr)) {
            foreach ($arr as $key => $val) {
                $info = [];
                if ($now_status == 7 || $now_status == 8) {
                    $field = 'r.*,d.id,d.goods_id,d.name as goods_name,d.image,d.sku_info,d.num,d.price,d.status as goods_status,d.forms,d.notes,mg.image as goods_image';
                    $info = $refundDetailService->getJoinData(['refund_id' => $val['refund_id']], $field)->toArray();
                }
                if(empty($info)){
                    $field = 'd.id,d.goods_id,d.name as goods_name,d.image,d.sku_info,d.num,d.price,d.status as goods_status,d.forms,d.notes,g.image as goods_image';
                    $info = (new MallOrderDetail())->getGoodsJoinData(['d.order_id'=>$val['order_id']],$field)->toArray();
                }
                
                $total_num = 0;
                if (!empty($info)) {
                    foreach ($info as $vv) {
                        //处理图片
                        $vv['image'] = $vv['image'] ? replace_file_domain($vv['image']) : replace_file_domain($vv['goods_image']);
                        $vv['forms'] = json_decode($vv['forms'], true) ?: [];
                        $vv['notes'] = json_decode($vv['notes'], true) ?: [];
                        if (!empty($val['refund_id'])) {
                            $vv['num'] = $vv['refund_nums'] ?? 0;
                        }
                        $total_num += $vv['num'];
                        //如果该笔订单的商品存在退款则要展示出
                        $where_tk = ['d.order_detail_id' => $vv['id']];
                        $vv['refund_desc'] = [];
                        $refund_nums1 = 0;
                        $refund_nums2 = 0;
                        $refund_nums3 = 0;
                        $refund_details = $refundDetailService->getRefundByDetaiId($where_tk);
                        if (!empty($refund_details) && $now_status != 7 && $now_status != 8 && ($val['status']<50 || $val['status'] >=60)) {
                            foreach ($refund_details as $refund_detail) {
                                if ($refund_detail['status'] == 0 && $refund_detail['type'] == 1 && $val['status'] != 60 && $val['status'] != 70) {
                                    $refund_nums1 += $refund_detail['refund_nums'];
                                } elseif ($refund_detail['status'] == 0 && $refund_detail['type'] == 2 && $val['status'] != 60 && $val['status'] != 70) {
                                    $refund_nums2 += $refund_detail['refund_nums'];
                                } elseif ($refund_detail['status'] == 1 && $val['status'] != 60 && $val['status'] != 70) {
                                    $refund_nums3 += $refund_detail['refund_nums'];
                                }
                            }
                            $str_refund = '';
                            if (!empty($refund_nums1)) {
                                $vv['refund_desc'][] = '退款中x' . $refund_nums1;
                                $str_refund = '退款中:'.$refund_nums1;
                            }
                            if (!empty($refund_nums2)) {
                                $vv['refund_desc'][] = '售后中x' . $refund_nums2;
                                $str_refund = $str_refund ? $str_refund.',' : '';
                                $str_refund = $str_refund.'售后中:'.$refund_nums2;
                            }
                            if (!empty($refund_nums3)) {
                                $vv['refund_desc'][] = '已退款x' . $refund_nums3;
                                $str_refund = $str_refund ? $str_refund.',' : '';
                                $str_refund = $str_refund.'已退:'.$refund_nums3;
                            }
                            $str_refund = $str_refund ? '('.$str_refund.')' : '';
                            $vv['num_new'] = $vv['num'].$str_refund;
                        }
                        $vv['is_gift'] = 0;
                        //如果该订单的商品是赠品则要展示出
                        if (stripos($val['activity_type'], 'give') !== false) {
                            if ((new MallFullGiveGiftSku())->getGiftInfo('g.id', [['g.goods_id', '=', $vv['goods_id']], ['a.start_time', '<', time()], ['a.end_time', '>', time()], ['a.status', '<>', 2]])) {
                                $vv['is_gift'] = 1;
                            }
                        }
                        $arr[$key]['children'][] = $vv;
                        $msg=(new MallGoods())->getGoodsByShopGoods(['s.goods_id'=>$vv['goods_id']],'a.supply_price,a.goods_code,a.cost_price,a.number');
                        if(!empty($msg)){
                            $arr[$key]['supply_price']=$msg['supply_price'];
                            $arr[$key]['goods_code']=$msg['goods_code'];
                            $arr[$key]['cost_price']=$msg['cost_price'];
                            $arr[$key]['number']=$msg['number'];
                        }else{
                            $arr[$key]['supply_price']=0;
                            $arr[$key]['goods_code']="";
                            $arr[$key]['cost_price']=0;
                            $arr[$key]['number']="";
                        }
                    }
                } else {
//                    throw new \think\Exception('未查询到与该订单有关的商品信息');
                }
                $arr[$key]['pay_type'] = $payService->getPayTypeText($val['online_pay_type'], $val);
                //该笔订单总数
                $arr[$key]['total_num'] = $total_num;
                //店员优惠取最后一次
                $clerk_discount = explode(',', $val['discount_clerk_money']);
                $arr[$key]['discount_clerk_money'] = $clerk_discount ? (float)$clerk_discount[0] : 0.00;
                //总优惠
                $arr[$key]['discount_total'] = 0.00;
                $discount = $this->getDiscount($val['order_id']);
                if (!empty($discount)) {
                    foreach ($discount as $v) {
                        $arr[$key]['discount_total'] += (float)$v;
                    }
                }
                $arr[$key]['discount_total'] = number_format($arr[$key]['discount_total'], 2);
                //判断是否能改价格
                if (empty($arr[$key]['goods_activity_type']) && empty($arr[$key]['activity_type'])) {
                    $arr[$key]['clerk_modify'] = 1;
                } else {
                    $arr[$key]['clerk_modify'] = 0;
                }
                //周期购退款信息
                if (isset($arr[$key]['refund_id']) && $arr[$key]['goods_activity_type'] == 'periodic') {
                    $arr[$key]['refund_money_periodic'] = $arr[$key]['refund_money'];  //周期购退款金额
                    $arr[$key]['refund_count_periodic'] = 0;  //周期购退款期数
                }
                //预售的处理 需付款+已付定金+已付尾款
                if (stripos($val['goods_activity_type'], 'prepare') !== false) {
                    $prefield = 'po.bargain_price,po.rest_price,p.discount_price,po.act_id,po.goods_id,po.uid';
                    $prepare = $prepreOrder->getPrepare($prefield, $val['order_id']);
                    if (!empty($prepare)) {
                        $arr[$key]['bargain_price'] = $prepare['bargain_price'];
                        $arr[$key]['real_bargain_price'] = $prepare['bargain_price'] - $arr[$key]['discount_total'];
                        $arr[$key]['rest_price'] = $prepare['rest_price'];
                        $arr[$key]['deduct_price'] = $prepare['bargain_price'] + $prepare['discount_price'] * $total_num;
                        $arr[$key]['discount_price'] = $prepare['discount_price'];
                        $arr[$key]['send_time'] = date('Y-m-d H:i:s', ($this->mallActivityService->getPrepareOrderStatus($prepare['goods_id'], $prepare['uid'], $prepare['act_id'], $val['order_id']))['send_goods_date']);
                        $arr[$key]['children'][0]['price'] = ($prepare['rest_price'] + $prepare['bargain_price']) / $total_num;
                        $arr[$key]['money_total'] = $prepare['rest_price'] + $prepare['bargain_price'];
                        if ($val['status'] >= 0 && $val['status'] < 10) {
                            $arr[$key]['money_real'] = $prepare['rest_price'];
                        } elseif ($val['status'] >= 50 && $val['status'] < 60) {
                            $arr[$key]['money_real'] = $prepare['bargain_price'] - $arr[$key]['discount_total'];
                        } else {
                            $arr[$key]['money_real'] = $prepare['rest_price'] + $prepare['bargain_price'] - $arr[$key]['discount_total'];
                        }
                    } else {
                        fdump($val,"noPrepare20211019",1);
                        //throw new \think\Exception('没有查到该订单的预售信息');
                    }
                }
                //处理退款相关的状态转换
                if ($now_status == 7) {
                    $arr[$key]['status'] = 60;
                    $val['status'] = 60;
                } elseif ($now_status == 8) {
                    $arr[$key]['status'] = 70;
                    $val['status'] = 70;
                } elseif ($now_status == 1 && isset($val['refund_id'])) {
                    if ($val['status'] === 0) {
                        $arr[$key]['status'] = 60;
                        $val['status'] = 60;
                    } elseif ($val['status'] === 1) {
                        //部分退款原订单状态不变，返回记录的退款信息
                        $arr[$key]['status'] = 70;
                        $val['status'] = 70;
                    }
                }
                //格式化时间
                $arr[$key]['create_time'] = date('Y-m-d H:i:s', $val['create_time']);
                $arr[$key]['button'] = [
                    'take_btn' => 0,
                    'hoseman_btn' => 0,
                    'clerk_btn' => 0,
                    'express_btn' => 0,
                    'logistics_btn' => 0,
                    'postpone_btn' => 0,
                    'trajectory_btn' => 0,
                    'agree_refund_btn' => 0,
                    'refuse_refund_btn' => 0
                ];
                if ($val['status'] >= 0 && $val['status'] < 10) {
                    $arr[$key]['status_txt'] = '待付款';
                    if ($param['re_type'] == 'storestaff') {
                        $arr[$key]['operator'] = '';
                    }
                } elseif ($val['status'] == 10) {
                    $arr[$key]['status_txt'] = '待发货';
                    if ($param['re_type'] == 'storestaff') {
                        $arr[$key]['button']['take_btn'] = 1;
                        if (stripos($val['goods_activity_type'], 'periodic') !== false) {
                            $arr[$key]['button']['postpone_btn'] = 1;
                        }
                    }
                } elseif ($val['status'] == 11) {
                    $arr[$key]['status_txt'] = '备货中';
                    if ($param['re_type'] == 'storestaff') {
                        if ($val['express_style'] == 1) {
                            $arr[$key]['button']['hoseman_btn'] = 1;
                        } elseif ($val['express_style'] == 3) {
                            $arr[$key]['button']['clerk_btn'] = 1;
                        } elseif ($val['express_style'] == 2) {
                            $arr[$key]['button']['express_btn'] = 1;
                        } else {
                           // throw new \think\Exception('发货方式错误');
                        }
                    }
                } elseif ($val['status'] == 13) {//商品未成团不需任何操作
                    $arr[$key]['status_txt'] = '待成团';
                } elseif ($val['status'] >= 20 && $val['status'] < 30) {
                    $arr[$key]['status_txt'] = '已发货';
                    if ($val['express_style'] == 2) {
                        $arr[$key]['button']['logistics_btn'] = 1;
                    } elseif ($val['express_style'] == 1) {
                        $arr[$key]['button']['trajectory_btn'] = 1;
                    }
                } elseif ($val['status'] >= 30 && $val['status'] < 50) {//用户端的已收货所对应的店员端的已完成
                    $arr[$key]['status_txt'] = '已完成';
                    if ($val['express_style'] == 2) {
                        $arr[$key]['button']['logistics_btn'] = 1;
                    } elseif ($val['express_style'] == 1) {
                        $arr[$key]['button']['trajectory_btn'] = 1;
                    }
                } elseif ($val['status'] >= 50 && $val['status'] < 60) {
                    $arr[$key]['status_txt'] = '已取消';
                } elseif ($val['status'] >= 60 && $val['status'] < 70) {
                    if(isset($val['type'])){
                        if ($val['type'] == 1) {
                            $arr[$key]['status_txt'] = '退款中';
                        } elseif ($val['type'] == 2) {
                            $arr[$key]['status_txt'] = '售后中';
                        } else {
                            throw new \think\Exception('退款参数有误');
                        }
                    }else{
                        $arr[$key]['status_txt'] = '';
                    }
                    if (isset($val['is_all']) && $val['is_all'] == 0 && $now_status == 1 && $arr[$key]['children']) {
                        foreach ($arr[$key]['children'] as $k => $child) {
                            if (!empty($child['refund_desc'])) {
                                $cInfo[] = $arr[$key]['children'][$k];
                            }
                        }
                        if (!empty($cInfo)) {
                            $arr[$key]['children'] = $cInfo;
                        }
                    }
                    if ($param['re_type'] == 'storestaff') {
                        $arr[$key]['button']['agree_refund_btn'] = 1;
                        $arr[$key]['button']['refuse_refund_btn'] = 1;
                    }
                    if (isset($val['type']) &&  $val['type'] == 2 && $val['express_style'] != 3) {
                        $arr[$key]['button']['logistics_btn'] = 1;
                        if ($val['express_style'] == 2) {
                            $arr[$key]['button']['logistics_btn'] = 1;
                        } elseif ($val['express_style'] == 1) {
                            $arr[$key]['button']['trajectory_btn'] = 1;
                        }
                    }
                } elseif ($val['status'] >= 70) {
                    $arr[$key]['status_txt'] = '已退款';
                    $arr[$key]['button']['logistics_btn'] = 1;
                    if (isset($val['is_all']) && $val['is_all'] == 0 && $now_status == 1 && $arr[$key]['children']) {
                        foreach ($arr[$key]['children'] as $k => $child) {
                            if (!empty($child['refund_desc'])) {
                                $cInfo[] = $arr[$key]['children'][$k];
                            }
                        }
                        if (!empty($cInfo)) {
                            $arr[$key]['children'] = $cInfo;
                        }
                    }
                }
                //将数字转化为对应的文字
                switch ($val['express_style']) {
                    case 1:
                        $arr[$key]['express_style_txt'] = '骑手配送';
                        $time = explode(',', $val['express_send_time']);
                        if (!empty($time[0]) && !empty($time[1])) {
                            $arr[$key]['current_time'] = date('Y-m-d', $time[0]) . ' ' . date('H:i', $time[0]) . '-' . date('H:i', $time[1]);
                            $arr[$key]['express_current_time'] = date('Y-m-d', $time[0]) . ' ' . date('H:i', $time[0]) . '-' . date('H:i', $time[1]);
                        }
                        break;
                    case 3:
                        $arr[$key]['express_style_txt'] = '自提';
                        break;
                    case 2:
                        $arr[$key]['express_style_txt'] = '快递配送';
                        break;
                }
                if (stripos($val['goods_activity_type'], 'bargain') !== false) {
                    $arr[$key]['order_type_txt'] = '砍价订单';
                    $arr[$key]['order_type'] = 'bargain';
                } elseif (stripos($val['goods_activity_type'], 'group') !== false) {
                    $arr[$key]['order_type_txt'] = '拼团订单';
                    $arr[$key]['order_type'] = 'group';
                } elseif (stripos($val['goods_activity_type'], 'limited') !== false) {
                    $arr[$key]['order_type_txt'] = '秒杀订单';
                    $arr[$key]['order_type'] = 'limited';
                } elseif (stripos($val['goods_activity_type'], 'prepare') !== false) {
                    $arr[$key]['order_type_txt'] = '预售订单';
                    $arr[$key]['order_type'] = 'prepare';
                } elseif (stripos($val['goods_activity_type'], 'periodic') !== false) {
                    $arr[$key]['order_type_txt'] = '周期购订单';
                    $arr[$key]['order_type'] = 'periodic';
                } else {
                    $arr[$key]['order_type_txt'] = '普通订单';
                    $arr[$key]['order_type'] = '';
                }
                $arr[$key]['money_total'] = get_format_number($arr[$key]['money_total'] - $arr[$key]['money_freight']);
            }
        }

        $list['collect_num'] = $collect_num;//必须放在最后不影响循环
        if ($param['use_type'] == 1) {
            $list['list'] = $arr;
            $list['status_num'] = $this->getNumberCopy($param);
            if(($now_status==1 || $now_status==8) && $param['search_time_type']=='refund_time' && !empty($param['begin_time']) && !empty($param['end_time'])){
                $list['status_num'][0]['num']=$list['status_num'][7]['num'];
                $list['status_num'][1]['num']=0;
                $list['status_num'][2]['num']=0;
                $list['status_num'][3]['num']=0;
                $list['status_num'][4]['num']=0;
                $list['status_num'][5]['num']=0;
                $list['status_num'][6]['num']=0;
            }
            $list['count'] = $list['status_num'][$now_status - 1]['num'];
        } else {
            $arr = $this->formatExportChildren(array_values($arr));
            $list = $arr;
        }
        if($now_status!=1 && $now_status!=8 && $param['search_time_type']=='refund_time' && !empty($param['begin_time']) && !empty($param['end_time'])){
            //$list['collect_num'] = [];//必须放在最后不影响循环
            $list['list'] = [];
            $list['status_num'] = 0;
            $list['count'] = 0;
           // return $list;
        }
        return $list;
    }


    /**
     * 按条件获取列表
     * @param $param
     * @return array
     */
    public function searchOrdersDownExcel($param, $type = 0)
    {
        $style = 0; //列表调用
        $now_status = $param['status'];
        $where_all = $this->getWhere($param, $style);
        if ($param['re_type'] == 'platform') {
            $field = 'md.goods_id,md.name as goods_name,md.sku_info,md.num,md.price,md.money_total as goods_total_money,
            o.order_id,o.order_no,o.mer_id,o.store_id,o.address,o.create_time,o.money_online_pay,o.online_pay_type,
            o.express_send_time,o.money_freight,o.username,o.phone,o.express_style,o.send_time,o.status,o.clerk_remark,
            o.remark,o.money_total,o.money_real,o.discount_system_coupon,o.discount_merchant_coupon,o.discount_merchant_card,
            o.discount_system_level,o.discount_clerk_money,o.discount_act_money,o.money_score,o.money_system_balance,
            o.money_merchant_balance,o.money_merchant_give_balance,o.money_qiye_balance,o.goods_activity_type,o.activity_type,
            o.pay_orderno,o.pre_pay_orderno,o.activity_id,s.name as store_name,m.name as mer_name,o.complete_time,o.staff_name';
        } elseif ($param['re_type'] == 'merchant') {
            $field = 'md.goods_id,md.name as goods_name,md.sku_info,md.num,md.price,md.money_total as goods_total_money,
            o.order_id,o.order_no,o.mer_id,o.store_id,o.create_time,o.money_online_pay,o.online_pay_type,o.address,
            o.express_send_time,o.money_freight,o.username,o.phone,o.express_style,o.send_time,o.status,o.clerk_remark,
            o.remark,o.money_total,o.money_real,o.discount_system_coupon,o.discount_merchant_coupon,o.discount_merchant_card,
            o.discount_system_level,o.discount_clerk_money,o.discount_act_money,o.money_score,o.money_system_balance,
            o.money_merchant_balance,o.money_merchant_give_balance,o.money_qiye_balance,o.goods_activity_type,o.activity_type,
            o.activity_id,o.pay_orderno,o.pre_pay_orderno,s.name as store_name,m.name as mer_name,o.complete_time,o.staff_name';
        } elseif ($param['re_type'] == 'storestaff') {
            $field = 'md.goods_id,md.name as goods_name,md.sku_info,md.num,md.price,md.money_total as goods_total_money,
            o.order_id,o.order_no,o.mer_id,o.store_id,o.create_time,o.money_online_pay,o.online_pay_type,o.address,
            o.express_send_time,o.money_freight,o.username,o.phone,o.express_style,o.send_time,o.status,o.clerk_remark,
            o.remark,o.money_total,o.money_real,o.discount_system_coupon,o.discount_merchant_coupon,o.discount_merchant_card,
            o.discount_system_level,o.discount_clerk_money,o.discount_act_money,o.money_score,o.money_system_balance,
            o.money_merchant_balance,o.money_merchant_give_balance,o.money_qiye_balance,o.goods_activity_type,o.activity_type,
            o.activity_id,o.pay_orderno,o.pre_pay_orderno,s.name as store_name,m.name as mer_name,o.complete_time,o.staff_name';
        } else {
            throw new \think\Exception('re_type参数错误');
        }
        $refundDetailService = new MallOrderRefundDetail();
        $orderDetailService = new MallOrderDetailService();
        $payService = new PayService();
        $prepreOrder = new MallPrepareOrder();

        $style = 4; //选择周期购时不过滤状态和周期购条件
        $where_periodic_all = $this->getWhere($param, $style);
        array_push($where_periodic_all, ['o.goods_activity_type', '=', 'periodic']);
        $arr = $this->MallOderModel->getSearchDown($field, $where_all, $where_periodic_all);
        $arrs = array();
        if (!empty($arr)) {
            foreach ($arrs as $k1 => $v1) {
                foreach ($arr as $k2 => $v2) {
                    if ($v1['order_id'] == $v2['order_id'] && !isset($v2['periodic_order_id'])) {
                        unset($arr[$k2]);
                    }
                    if ($now_status == 2) {
                        if (!($v2['status'] >= 0 && $v2['status'] < 10)) {
                            unset($arr[$k2]);
                        }
                    } elseif ($now_status == 3) {
                        if (!($v2['status'] >= 10 && $v2['status'] < 20)) {
                            unset($arr[$k2]);
                        }
                    } elseif ($now_status == 4) {
                        if (!($v2['status'] >= 20 && $v2['status'] < 30)) {
                            unset($arr[$k2]);
                        }
                    } elseif ($now_status == 5) {
                        if (!($v2['status'] >= 30 && $v2['status'] < 50)) {
                            unset($arr[$k2]);
                        }
                    } elseif ($now_status == 6) {
                        if (!($v2['status'] >= 50 && $v2['status'] < 60)) {
                            unset($arr[$k2]);
                        }
                    } elseif ($now_status == 7) {
                        if (!($v2['status'] >= 60 && $v2['status'] < 70)) {
                            unset($arr[$k2]);
                        }
                    }
                }
            }
            $arr = array_values($arr);
        }
        $unsetPageParam = $param;
        if (isset($unsetPageParam['page'])) {
            $unsetPageParam['page']=0;
        }
        if (isset($unsetPageParam['pageSize'])) {
            $unsetPageParam['pageSize']=0;
        }
        if ($now_status == 7 || $now_status == 8) {
            $arr = $this->getTkInfoByOrderDetail($now_status, $unsetPageParam);
        } elseif ($now_status == 1) {
            $arr_tk = $this->getTkInfoByOrderDetail($now_status, $unsetPageParam);
            $arr = array_merge($arr, $arr_tk);
        }
        $refund_info = $this->mallOrderRefundService->getAllRefund([['r.status', '=', 1], ['r.is_all', '=', 1]], 'o.*,r.*,mr.name as mer_name,ms.name as store_name', '', '');
        if (!empty($refund_info)) {
            $refundOrderIds = array_column($refund_info, 'order_id');
            $refundOrderIds = array_unique($refundOrderIds);

            foreach ($arr as $key => $val) {
                if (in_array($val['order_id'], $refundOrderIds) && !isset($val['refund_id'])) {
                    unset($arr[$key]);
                }
            }
        }
        $countArr = array_values($arr);
        if ($type == 0) {
            $arr = $this->pageDeal($countArr, $param['page'], $param['pageSize']);
        }
        if (!empty($arr)) {
            foreach ($arr as $key => $val) {
                $arr[$key]['complete_time']=$val['complete_time']?date('Y-m-d H:i:s',$val['complete_time']):'';
                if ($now_status != 7 && $now_status != 8) {
                    $field = 'id,goods_id,name as goods_name,image,sku_info,num,price,status as goods_status,forms,notes';
                    $info = $orderDetailService->getByOrderId($field, $val['order_id']);
                } else {
                    $field = 'r.*,d.id,d.goods_id,d.name as goods_name,d.image,d.sku_info,d.num,d.price,d.status as goods_status,d.forms,d.notes';
                    $info = $refundDetailService->getJoinData(['refund_id' => $val['refund_id']], $field);
                }

                $total_num = 0;

                $msg=(new MallGoods())->getGoodsByShopGoods(['s.goods_id'=>$val['goods_id']],'s.common_goods_id,a.spec_value,a.supply_price,a.goods_code,a.cost_price,a.number');
                if(!empty($msg)){
                    $number = $msg['number'];
                    $costPrice = $msg['cost_price'];
                    if ($val['sku_info']) {
                        //多规格条码和成本价更新
                        $skuStr = $val['sku_info'];
                        $specArr = (new \app\shop\model\service\goods\ShopGoodsService())->formatSpecValue($msg['spec_value'], $msg['common_goods_id']);
                        foreach ($specArr['list'] as $sv) {
                            if (isset($sv['spec_str']) && $sv['spec_str'] == $skuStr) {
                                $costPrice = $sv['cost_price'];
                                $number = $sv['number'];
                            }
                        }
                    }
                    $arr[$key]['supply_price']=$msg['supply_price'];
                    $arr[$key]['goods_code']=$msg['goods_code'];
                    $arr[$key]['cost_price']=$costPrice;
                    $arr[$key]['number']=$number;
                    if(isset($val['price'])){
                        $arr[$key]['sku_all_price']=$val['num']*$val['price'];
                    }else{
                        $arr[$key]['sku_all_price']=0;
                    }
                }else{
                    $arr[$key]['supply_price']=0;
                    $arr[$key]['goods_code']="";
                    $arr[$key]['cost_price']=0;
                    $arr[$key]['number']="";
                    $arr[$key]['sku_all_price']=0;
                }
                //平台结算给商家的钱
                $arr[$key]['settlement_merchant_money'] = MerchantMoneyList::where([
                    'order_id' => $val['order_no'],
                    'type'     => 'mall',
                    'income'   => 1
                ])->value('money');
                
                if (!empty($info)) {
                    foreach ($info as $vv) {
                        //处理图片
                        if ($now_status == 7 && $now_status== 8) {
                            $arr[$key]['num']=$vv['num'];
                            $arr[$key]['sku_all_price']=$vv['num']*$vv['price'];
                        }
                    }
                }
                /*else {
                    throw new \think\Exception('未查询到与该订单有关的商品信息');
                }*/
                $arr[$key]['pay_type'] = $payService->getPayTypeText($val['online_pay_type'], $val);
                //总优惠
                $arr[$key]['discount_total'] = 0.00;
                $discount = $this->getDiscount($val['order_id']);
                if (!empty($discount)) {
                    foreach ($discount as $v) {
                        $arr[$key]['discount_total'] += (float)$v;
                    }
                }
                $arr[$key]['discount_total'] = number_format($arr[$key]['discount_total'], 2);
                //预售的处理 需付款+已付定金+已付尾款
                if (stripos($val['goods_activity_type'], 'prepare') !== false) {
                    $prefield = 'po.bargain_price,po.rest_price,p.discount_price,po.act_id,po.goods_id,po.uid';
                    $prepare = $prepreOrder->getPrepare($prefield, $val['order_id']);
                    if (!empty($prepare)) {
                        $arr[$key]['bargain_price'] = $prepare['bargain_price'];
                        $arr[$key]['real_bargain_price'] = $prepare['bargain_price'] - $arr[$key]['discount_total'];
                        $arr[$key]['rest_price'] = $prepare['rest_price'];
                        $arr[$key]['deduct_price'] = $prepare['bargain_price'] + $prepare['discount_price'] * $total_num;
                        $arr[$key]['discount_price'] = $prepare['discount_price'];
                        $arr[$key]['send_time'] = date('Y-m-d H:i:s', ($this->mallActivityService->getPrepareOrderStatus($prepare['goods_id'], $prepare['uid'], $prepare['act_id'], $val['order_id']))['send_goods_date']);
                        //$arr[$key]['children'][0]['price'] = ($prepare['rest_price'] + $prepare['bargain_price']) / $total_num;
                        $arr[$key]['money_total'] = $prepare['rest_price'] + $prepare['bargain_price'];
                        if ($val['status'] >= 0 && $val['status'] < 10) {
                            $arr[$key]['money_real'] = $prepare['rest_price'];
                        } elseif ($val['status'] >= 50 && $val['status'] < 60) {
                            $arr[$key]['money_real'] = $prepare['bargain_price'] - $arr[$key]['discount_total'];
                        } else {
                            $arr[$key]['money_real'] = $prepare['rest_price'] + $prepare['bargain_price'] - $arr[$key]['discount_total'];
                        }

                    } else {
                       // throw new \think\Exception('没有查到该订单的预售信息');
                    }
                }
                //处理退款相关的状态转换
                if ($now_status == 7) {
                    $arr[$key]['status'] = 60;
                    $val['status'] = 60;
                } elseif ($now_status == 8) {
                    $arr[$key]['status'] = 70;
                    $val['status'] = 70;
                } elseif ($now_status == 1 && isset($val['refund_id'])) {
                    if ($val['status'] === 0) {
                        $arr[$key]['status'] = 60;
                        $val['status'] = 60;
                    } elseif ($val['status'] === 1) {
                        //部分退款原订单状态不变，返回记录的退款信息
                        $arr[$key]['status'] = 70;
                        $val['status'] = 70;
                    }
                }               //格式化时间
                $arr[$key]['create_time'] = date('Y-m-d H:i:s', $val['create_time']);
                if ($val['status'] >= 0 && $val['status'] < 10) {
                    $arr[$key]['status_txt'] = '待付款';
                    if ($param['re_type'] == 'storestaff') {
                        $arr[$key]['operator'] = '';
                    }
                } elseif ($val['status'] == 10) {
                    $arr[$key]['status_txt'] = '待发货';
                    if ($param['re_type'] == 'storestaff') {
                        $arr[$key]['button']['take_btn'] = 1;
                        if (stripos($val['goods_activity_type'], 'periodic') !== false) {
                            $arr[$key]['button']['postpone_btn'] = 1;
                        }
                    }
                } elseif ($val['status'] == 11) {
                    $arr[$key]['status_txt'] = '备货中';
                } elseif ($val['status'] == 13) {//商品未成团不需任何操作
                    $arr[$key]['status_txt'] = '待成团';
                } elseif ($val['status'] >= 20 && $val['status'] < 30) {
                    $arr[$key]['status_txt'] = '已发货';
                } elseif ($val['status'] >= 30 && $val['status'] < 50) {//用户端的已收货所对应的店员端的已完成
                    $arr[$key]['status_txt'] = '已完成';

                } elseif ($val['status'] >= 50 && $val['status'] < 60) {
                    $arr[$key]['status_txt'] = '已取消';
                } elseif ($val['status'] >= 60 && $val['status'] < 70) {
                    if(isset($val['type'])){
                        if ($val['type'] == 1) {
                            $arr[$key]['status_txt'] = '退款中';
                        } elseif ($val['type'] == 2) {
                            $arr[$key]['status_txt'] = '售后中';
                        } else {
                            throw new \think\Exception('退款参数有误');
                        }
                    }
                } elseif ($val['status'] >= 70) {
                    $arr[$key]['status_txt'] = '已退款';
                }


                if (stripos($val['goods_activity_type'], 'bargain') !== false) {
                    $arr[$key]['order_type_txt'] = '砍价订单';
                    $arr[$key]['order_type'] = 'bargain';
                } elseif (stripos($val['goods_activity_type'], 'group') !== false) {
                    $arr[$key]['order_type_txt'] = '拼团订单';
                    $arr[$key]['order_type'] = 'group';
                } elseif (stripos($val['goods_activity_type'], 'limited') !== false) {
                    $arr[$key]['order_type_txt'] = '秒杀订单';
                    $arr[$key]['order_type'] = 'limited';
                } elseif (stripos($val['goods_activity_type'], 'prepare') !== false) {
                    $arr[$key]['order_type_txt'] = '预售订单';
                    $arr[$key]['order_type'] = 'prepare';
                } elseif (stripos($val['goods_activity_type'], 'periodic') !== false) {
                    $arr[$key]['order_type_txt'] = '周期购订单';
                    $arr[$key]['order_type'] = 'periodic';
                } else {
                    $arr[$key]['order_type_txt'] = '普通订单';
                    $arr[$key]['order_type'] = '';
                }
            }
        }
        if ($param['use_type'] == 1) {
            $list = $arr;
        }
        else {
            $list = array_values($arr);
        }
        return $list;
    }


    /**
     * @param $param
     * @return array
     * 优化订单列表查询
     */
    public function searchOrdersCopy($param)
    {
        $style = 0; //列表调用
        $now_status = $param['status'];
        $where_all = $this->getWhereCopy($param, $style);
        $param['provinceId'] = $param['cityId'] = $param['areaId'] = $param['streetId'] = 0;
        if ($param['re_type'] == 'platform') {
            $field = 'o.order_id,o.order_no,o.mer_id,o.store_id,o.address,o.create_time,o.money_online_pay,o.online_pay_type,o.express_send_time,o.money_freight,o.username,o.phone,o.express_style,o.send_time,o.status,o.remark,o.clerk_remark,o.money_total,o.money_real,o.discount_system_coupon,o.discount_merchant_coupon,o.discount_merchant_card,o.discount_system_level,o.discount_clerk_money,o.discount_act_money,o.money_score,o.money_system_balance,o.money_merchant_balance,o.money_merchant_give_balance,o.money_qiye_balance,o.goods_activity_type,o.activity_type,o.pay_orderno,o.pre_pay_orderno,o.activity_id,s.name as store_name,m.name as mer_name,o.verify_status,o.employee_score_pay,o.employee_balance_pay,o.is_third_deliver';
        } elseif ($param['re_type'] == 'merchant') {
            $field = 'o.order_id,o.order_no,o.mer_id,o.store_id,o.create_time,o.money_online_pay,o.online_pay_type,o.address,o.express_send_time,o.money_freight,o.username,o.phone,o.express_style,o.send_time,o.status,o.remark,o.clerk_remark,o.money_total,o.money_real,o.discount_system_coupon,o.discount_merchant_coupon,o.discount_merchant_card,o.discount_system_level,o.discount_clerk_money,o.discount_act_money,o.money_score,o.money_system_balance,o.money_merchant_balance,o.money_merchant_give_balance,o.money_qiye_balance,o.goods_activity_type,o.activity_type,o.activity_id,o.pay_orderno,o.pre_pay_orderno,s.name as store_name,m.name as mer_name,o.verify_status,o.employee_score_pay,o.employee_balance_pay,o.is_third_deliver';
        } elseif ($param['re_type'] == 'storestaff') {
            $field = 'o.order_id,o.order_no,o.mer_id,o.store_id,o.create_time,o.money_online_pay,o.online_pay_type,o.address,o.express_send_time,o.money_freight,o.username,o.phone,o.express_style,o.send_time,o.status,o.remark,o.clerk_remark,o.money_total,o.money_real,o.discount_system_coupon,o.discount_merchant_coupon,o.discount_merchant_card,o.discount_system_level,o.discount_clerk_money,o.discount_act_money,o.money_score,o.money_system_balance,o.money_merchant_balance,o.money_merchant_give_balance,o.money_qiye_balance,o.goods_activity_type,o.activity_type,o.activity_id,o.pay_orderno,o.pre_pay_orderno,s.name as store_name,m.name as mer_name,o.verify_status,o.employee_score_pay,o.employee_balance_pay,o.is_third_deliver';
        } else {
            throw new \think\Exception('re_type参数错误');
        }
        $refundDetailService = new MallOrderRefundDetail();
        $orderDetailService = new MallOrderDetailService();
        $payService = new PayService();
        $prepreOrder = new MallPrepareOrder();
        $arr = $this->MallOderModel->getList($field, $where_all, $param['page'], $param['pageSize']);
        //获取各种和
        $style = 4;//获取各种统计 不选状态，不包含周期购，退款，售后，取消
        $collect_where = $this->getWhere($param, $style);
        $collect_num = $this->getCollect($collect_where, $param);

        $list_refud=(new MallOrder())->getRudTradeNew($collect_where);
        if(!empty($list_refud)){
            $list_refud_array=array_column($list_refud,'refund_money');
            $collect_num['tk_money']=get_number_format(array_sum($list_refud_array));
        }
        $arrs = array();
        if (!empty($arr)) {
            foreach ($arr as $k => $item) {
                // 周期购的处理 期数
                if ($item['goods_activity_type'] == 'periodic') {
                    $arrs[] = $arr[$k];
                    //根据订单id获取所有的子订单信息
                    $periodic = $this->mallActivityService->returnNowPeriodicOrderAndActBefore($item['order_id'], 0);
                    if (!empty($periodic)) {
                        foreach ($periodic as $kk => $vv) {
                            $periodic[$kk]['button'] = [
                                'take_btn' => 0,
                                'hoseman_btn' => 0,
                                'clerk_btn' => 0,
                                'express_btn' => 0,
                                'logistics_btn' => 0,
                                'postpone_btn' => 0,
                                'trajectory_btn' => 0,
                                'agree_refund_btn' => 0,
                                'refuse_refund_btn' => 0
                            ];
                            if ($vv['is_complete'] == 10) {
                                if ($param['re_type'] == 'storestaff') {
                                    $periodic[$kk]['button']['take_btn'] = 1;
                                    $periodic[$kk]['button']['postpone_btn'] = 1;
                                }
                            } elseif ($vv['is_complete'] == 11) {
                                if ($item['express_style'] == 1) {
                                    $periodic[$kk]['button']['hoseman_btn'] = 1;
                                } elseif ($item['express_style'] == 3) {
                                    $periodic[$kk]['button']['clerk_btn'] = 1;
                                } elseif ($item['express_style'] == 2) {
                                    $periodic[$kk]['button']['express_btn'] = 1;
                                } else {
                                    //throw new \think\Exception('发货方式错误');
                                }
                            } elseif ($vv['is_complete'] == 20) {
                                if ($item['express_style'] == 2) {
                                    $periodic[$kk]['button']['logistics_btn'] = 1;
                                } elseif ($item['express_style'] == 1) {
                                    $periodic[$kk]['button']['trajectory_btn'] = 1;
                                }
                            } elseif ($vv['is_complete'] == 30) {
                                if ($item['express_style'] == 2) {
                                    $periodic[$kk]['button']['logistics_btn'] = 1;
                                } elseif ($item['express_style'] == 1) {
                                    $periodic[$kk]['button']['trajectory_btn'] = 1;
                                }
                            }
                            $periodic[$kk]['periodic_date'] = $vv['periodic_date'] ? date('Y-m-d H:i', $vv['periodic_date']) : '';
                            $periodic[$kk]['delivery'] = $this->orderDeliveryList($vv['order_id'], $vv['purchase_order_id']);
                            $arr[$k]['periodic_order_id'] =$periodic[0]['purchase_order_id'];
                            if($item['is_third_deliver']){
                                $periodic[$kk]['button']['take_btn'] = 0;
                                $periodic[$kk]['button']['hoseman_btn'] = 0;
                                $periodic[$kk]['button']['postpone_btn'] = 0;
                            }
                        }
                        $arr[$k]['periodic_info'] = $periodic;
                    }else{
                        $arr[$k]['periodic_info']=[];
                        $arr[$k]['periodic_order_id']=0;
                        $arr[$k]['button']['hoseman_btn']=0;
                    }
                    $pWhere = [['is_complete', '<>', 2], ['order_id', '=', $item['order_id']]];
                    $arr[$k]['periodic_num'] = (new MallNewPeriodicPurchaseOrder())->getPeriodicOrderNum($pWhere);
                }else{
                    $this->orderDeliveryList($item['order_id']);
                }
                $arr[$k]['verify_text'] = ($arr[$k]['status'] >= 30 && $arr[$k]['status'] <= 40) ? $this->MallOderModel->getVerifyText($arr[$k]['verify_status']) : '';
            }
        }
        if ($now_status == 7 || $now_status == 8) {
            $arr = $this->getTkInfo($now_status, $param);
        }
        if (!empty($arr)) {
            foreach ($arr as $key => $val) {
                if ($now_status != 7 && $now_status != 8) {
                    $field = 'd.id,d.goods_id,d.name as goods_name,d.image,d.sku_info,d.num,d.price,d.status as goods_status,d.forms,d.notes,g.image as goods_image';
                    $info = (new MallOrderDetail())->getGoodsJoinData(['d.order_id'=>$val['order_id']], $field);
                } else {
                    $field = 'r.*,d.id,d.goods_id,d.name as goods_name,d.image,d.sku_info,d.num,d.price,d.status as goods_status,d.forms,d.notes,mg.image as goods_image';
                    $info = $refundDetailService->getJoinData(['refund_id' => $val['refund_id']], $field);
                }
                $total_num = 0;
                $info && $info = $info->toArray();
                if (!empty($info)) {
                    foreach ($info as $vv) {
                        //处理图片
                        $vv['image'] = $vv['image'] ? replace_file_domain($vv['image']) :replace_file_domain($vv['goods_image']);
                        $vv['forms'] = json_decode($vv['forms'], true) ?: [];

                        if (!empty($val['refund_id'])) {
                            $vv['num'] = $vv['refund_nums'];
                        }
                        if(!empty($vv['notes'])){
                            $_notes = json_decode($vv['notes'], true);
                            $cart_v=array();
                            foreach ($_notes as $n) {
                                $cart_v[] = implode(',', $n['property_val']);
                            }
                            if(empty($vv['sku_info'])){
                                $vv['sku_info']=implode(',',$cart_v);
                            }else{
                                $vv['sku_info']=$vv['sku_info'].",".implode(',',$cart_v);
                            }
                        }
                        $vv['notes'] = json_decode($vv['notes'], true) ?: [];
                        $total_num += $vv['num'];
                        //如果该笔订单的商品存在退款则要展示出
                        $where_tk = ['d.order_detail_id' => $vv['id']];
                        $vv['refund_desc'] = [];
                        $refund_nums1 = 0;
                        $refund_nums2 = 0;
                        $refund_nums3 = 0;
                        $refund_details = $refundDetailService->getRefundByDetaiId($where_tk);
                        if (!empty($refund_details) && $now_status != 7 && $now_status != 8) {
                            foreach ($refund_details as $refund_detail) {
                                if ($refund_detail['status'] == 0 && $refund_detail['type'] == 1 && $val['status'] != 60 && $val['status'] != 70) {
                                    $refund_nums1 += $refund_detail['refund_nums'];
                                } elseif ($refund_detail['status'] == 0 && $refund_detail['type'] == 2 && $val['status'] != 60 && $val['status'] != 70) {
                                    $refund_nums2 += $refund_detail['refund_nums'];
                                } elseif ($refund_detail['status'] == 1 && $val['status'] != 60 && $val['status'] != 70) {
                                    $refund_nums3 += $refund_detail['refund_nums'];
                                }
                            }
                            if (!empty($refund_nums1)) {
                                $vv['refund_desc'][] = '退款中x' . $refund_nums1;
                            }
                            if (!empty($refund_nums2)) {
                                $vv['refund_desc'][] = '售后中x' . $refund_nums2;
                            }
                            if (!empty($refund_nums3)) {
                                $vv['refund_desc'][] = '已退款x' . $refund_nums3;
                            }
                        }
                        $vv['is_gift'] = 0;
                        //如果该订单的商品是赠品则要展示出
                        if (stripos($val['activity_type'], 'give') !== false) {
                            if ((new MallFullGiveGiftSku())->getGiftInfo('g.id', [['g.goods_id', '=', $vv['goods_id']], ['a.start_time', '<', time()], ['a.end_time', '>', time()], ['a.status', '<>', 2]])) {
                                $vv['is_gift'] = 1;
                            }
                        }
                        $arr[$key]['children'][] = $vv;
                    }
                } else {
                    fdump_sql(['order'=>$val],"noFoundMallOrder");
                    //throw new \think\Exception('未查询到与该订单有关的商品信息');
                }
                $arr[$key]['pay_type'] = $payService->getPayTypeText($val['online_pay_type'], $val);
                //该笔订单总数
                $arr[$key]['total_num'] = $total_num;
                //店员优惠取最后一次
                $clerk_discount = explode(',', $val['discount_clerk_money']);
                $arr[$key]['discount_clerk_money'] = $clerk_discount ? (float)$clerk_discount[0] : 0.00;
                //总优惠
                $arr[$key]['discount_total'] = 0.00;
                $discount = $this->getDiscount($val['order_id']);
                if (!empty($discount)) {
                    foreach ($discount as $v) {
                        $arr[$key]['discount_total'] += (float)$v;
                    }
                }
                $arr[$key]['discount_total'] = number_format($arr[$key]['discount_total'], 2);
                //判断是否能改价格
                if (empty($arr[$key]['goods_activity_type']) && empty($arr[$key]['activity_type'])) {
                    $arr[$key]['clerk_modify'] = 1;
                } else {
                    $arr[$key]['clerk_modify'] = 0;
                }
                //周期购退款信息
                if (isset($arr[$key]['refund_id']) && $arr[$key]['goods_activity_type'] == 'periodic') {
                    $arr[$key]['refund_money_periodic'] = $arr[$key]['refund_money'];  //周期购退款金额
                    $arr[$key]['refund_count_periodic'] = 0;  //周期购退款期数
                }
                //预售的处理 需付款+已付定金+已付尾款
                if (stripos($val['goods_activity_type'], 'prepare') !== false) {
                    $prefield = 'po.bargain_price,po.rest_price,p.discount_price,po.act_id,po.goods_id,po.uid';
                    $prepare = $prepreOrder->getPrepare($prefield, $val['order_id']);
                    if (!empty($prepare)) {
                        $arr[$key]['bargain_price'] = $prepare['bargain_price'];
                        $arr[$key]['real_bargain_price'] = $prepare['bargain_price'] - $arr[$key]['discount_total'];
                        $arr[$key]['rest_price'] = $prepare['rest_price'];
                        $arr[$key]['deduct_price'] = $prepare['bargain_price'] + $prepare['discount_price'] * $total_num;
                        $arr[$key]['discount_price'] = $prepare['discount_price'];
                        $arr[$key]['send_time'] = date('Y-m-d H:i:s', ($this->mallActivityService->getPrepareOrderStatus($prepare['goods_id'], $prepare['uid'], $prepare['act_id'], $val['order_id']))['send_goods_date']);
                        $arr[$key]['children'][0]['price'] = ($prepare['rest_price'] + $prepare['bargain_price']) / $total_num;
                        $arr[$key]['money_total'] = $prepare['rest_price'] + $prepare['bargain_price'];
                        if ($val['status'] >= 0 && $val['status'] < 10) {
                            $arr[$key]['money_real'] = $prepare['rest_price'];
                        } elseif ($val['status'] >= 50 && $val['status'] < 60) {
                            $arr[$key]['money_real'] = $prepare['bargain_price'] - $arr[$key]['discount_total'];
                        } else {
                            $arr[$key]['money_real'] = $prepare['rest_price'] + $prepare['bargain_price'] - $arr[$key]['discount_total'];
                        }
                    } else {
                        //throw new \think\Exception('没有查到该订单的预售信息');
                    }
                }
                //处理退款相关的状态转换
                if ($now_status == 7) {
                    $arr[$key]['status'] = 60;
                    $val['status'] = 60;
                } elseif ($now_status == 8) {
                    $arr[$key]['status'] = 70;
                    $val['status'] = 70;
                } elseif ($now_status == 1 && isset($val['refund_id'])) {
                    if ($val['status'] === 0) {
                        $arr[$key]['status'] = 60;
                        $val['status'] = 60;
                    } elseif ($val['status'] === 1) {
                        //部分退款原订单状态不变，返回记录的退款信息
                        $arr[$key]['status'] = 70;
                        $val['status'] = 70;
                    }
                }
                //格式化时间
                $arr[$key]['create_time'] = date('Y-m-d H:i:s', $val['create_time']);
                $arr[$key]['button'] = [
                    'take_btn' => 0,
                    'hoseman_btn' => 0,
                    'clerk_btn' => 0,
                    'express_btn' => 0,
                    'logistics_btn' => 0,
                    'postpone_btn' => 0,
                    'trajectory_btn' => 0,
                    'agree_refund_btn' => 0,
                    'refuse_refund_btn' => 0,
                    'choose_delivery_method' => 0
                ];
                if(cfg('open_mall_staff_change_deliver')&&$val['status'] == MallOrder::$orderStatus['wait_send']){//选择配送方式
                    $arr[$key]['button']['choose_delivery_method'] = 1;
                }
                if($val['status'] >= 10 && $val['status'] <= 20){
                    $applyRefundRecord = (new MallOrderRefund())->where('order_id',$val['order_id'])->where('status',0)->findOrEmpty()->toArray();
                    if ($param['re_type'] == 'storestaff' && $applyRefundRecord) {
                        $arr[$key]['button']['agree_refund_btn'] = 1;
                        $arr[$key]['button']['refuse_refund_btn'] = 1;
                    }
                }

                if ($val['status'] >= 0 && $val['status'] < 10) {
                    $arr[$key]['status_txt'] = '待付款';
                    if ($param['re_type'] == 'storestaff') {
                        $arr[$key]['operator'] = '';
                    }
                } elseif ($val['status'] == 10) {
                    $arr[$key]['status_txt'] = '待发货';
                    if ($param['re_type'] == 'storestaff') {
                        $arr[$key]['button']['take_btn'] = 1;
                        if (stripos($val['goods_activity_type'], 'periodic') !== false) {
                            $arr[$key]['button']['postpone_btn'] = 1;
                        }
                    }
                } elseif ($val['status'] == 11) {
                    $arr[$key]['status_txt'] = '备货中';
                    if ($param['re_type'] == 'storestaff') {
                        if ($val['express_style'] == 1) {
                            $arr[$key]['button']['hoseman_btn'] = 1;
                        } elseif ($val['express_style'] == 3) {
                            $arr[$key]['button']['clerk_btn'] = 1;
                        } elseif ($val['express_style'] == 2) {
                            $arr[$key]['button']['express_btn'] = 1;
                        } else {
                            throw new \think\Exception('发货方式错误');
                        }
                    }
                } elseif ($val['status'] == 13) {//商品未成团不需任何操作
                    $arr[$key]['status_txt'] = '待成团';
                } elseif ($val['status'] >= 20 && $val['status'] < 30) {
                    $arr[$key]['status_txt'] = '已发货';
                    if ($val['express_style'] == 2) {
                        $arr[$key]['button']['logistics_btn'] = 1;
                    } elseif ($val['express_style'] == 1) {
                        $arr[$key]['button']['trajectory_btn'] = 1;
                    }
                } elseif ($val['status'] >= 30 && $val['status'] < 50) {//用户端的已收货所对应的店员端的已完成
                    $arr[$key]['status_txt'] = '已完成';
                    if ($val['express_style'] == 2) {
                        $arr[$key]['button']['logistics_btn'] = 1;
                    } elseif ($val['express_style'] == 1) {
                        $arr[$key]['button']['trajectory_btn'] = 1;
                    }
                } elseif ($val['status'] >= 50 && $val['status'] < 60) {
                    $arr[$key]['status_txt'] = '已取消';
                } elseif ($val['status'] >= 60 && $val['status'] < 70) {
                    if(isset($val['type'])){
                        if ($val['type'] == 1) {
                            $arr[$key]['status_txt'] = '退款中';
                        } elseif ($val['type'] == 2) {
                            $arr[$key]['status_txt'] = '售后中';
                        } else {
                            $arr[$key]['status_txt'] = '售后中';
                           // throw new \think\Exception('退款参数有误');
                        }
                    }else{
                        $arr[$key]['status_txt'] = '售后中';
                        fdump_sql(['val'=>$val,"msg"=>"退款参数有误"],"mall_return");
                        //throw new \think\Exception('退款参数有误');
                    }
                    if ($param['re_type'] == 'storestaff') {
                        $arr[$key]['button']['agree_refund_btn'] = 1;
                        $arr[$key]['button']['refuse_refund_btn'] = 1;
                    }
                    if (isset($val['type']) && $val['type'] == 2 && $val['express_style'] != 3) {
                        if ($val['express_style'] == 2) {
                            $arr[$key]['button']['logistics_btn'] = 1;
                        } elseif ($val['express_style'] == 1) {
                            $arr[$key]['button']['trajectory_btn'] = 1;
                        }
                    }
                } elseif ($val['status'] >= 70) {
                    $arr[$key]['status_txt'] = '已退款';
                    $arr[$key]['button']['logistics_btn'] = 1;
                }

                if (isset($arr[$key]['goods_activity_type']) && $arr[$key]['goods_activity_type'] == 'periodic' && empty($arr[$key]['periodic_info'])) {//不到发货时间的不显示发货按钮
                    $arr[$key]['button']['express_btn'] =0;
                }
                if(isset($arr[$key]['periodic_order_id']) && $arr[$key]['periodic_order_id']==0){
                    $arr[$key]['button']['postpone_btn'] = 0;
                }

                if (stripos($val['goods_activity_type'], 'periodic') !== false) {
                    $periodic = $this->mallActivityService->returnNowPeriodicOrderAndActBefore($val['order_id'], $now_status);//邓远辉提供的接口
                    if (empty($periodic)) {
                        $arr[$key]['button']['hoseman_btn'] = 0;
                    }
                }
                if($val['is_third_deliver']){
                    $arr[$key]['button']['take_btn'] = 0;
                    $arr[$key]['button']['hoseman_btn'] = 0;
                    $arr[$key]['button']['postpone_btn'] = 0;
                }
                //将数字转化为对应的文字
                switch ($val['express_style']) {
                    case 1:
                        $arr[$key]['express_style_txt'] = '骑手配送';
                        $time = explode(',', $val['express_send_time']);
                        if (!empty($time[0]) && !empty($time[1])) {
                            $arr[$key]['current_time'] = date('Y-m-d', $time[0]) . ' ' . date('H:i', $time[0]) . '-' . date('H:i', $time[1]);
                        }
                        break;
                    case 3:
                        $arr[$key]['express_style_txt'] = '自提';
                        break;
                    case 2:
                        $arr[$key]['express_style_txt'] = '快递配送';
                        break;
                }
                if (stripos($val['goods_activity_type'], 'bargain') !== false) {
                    $arr[$key]['order_type_txt'] = '砍价订单';
                    $arr[$key]['order_type'] = 'bargain';
                } elseif (stripos($val['goods_activity_type'], 'group') !== false) {
                    $arr[$key]['order_type_txt'] = '拼团订单';
                    $arr[$key]['order_type'] = 'group';
                } elseif (stripos($val['goods_activity_type'], 'limited') !== false) {
                    $arr[$key]['order_type_txt'] = '秒杀订单';
                    $arr[$key]['order_type'] = 'limited';
                } elseif (stripos($val['goods_activity_type'], 'prepare') !== false) {
                    $arr[$key]['order_type_txt'] = '预售订单';
                    $arr[$key]['order_type'] = 'prepare';
                } elseif (stripos($val['goods_activity_type'], 'periodic') !== false) {
                    $arr[$key]['order_type_txt'] = '周期购订单';
                    $arr[$key]['order_type'] = 'periodic';
                } else {
                    $arr[$key]['order_type_txt'] = '普通订单';
                    $arr[$key]['order_type'] = '';
                }
                 if($arr[$key]['send_time']>0 && is_numeric($arr[$key]['send_time'])){
                     $arr[$key]['send_time'] =date("Y-m-d H:i:s",$arr[$key]['send_time']);
                 }
                 $arr[$key]['money_total'] = get_format_number($arr[$key]['money_total'] - $arr[$key]['money_freight']);
                 if($arr[$key]['discount_clerk_money']){
                    $arr[$key]['discount_total'] = bcsub($arr[$key]['money_total'], $arr[$key]['money_real'], 2);
                 }
                 if(!empty($param['miaomaiyun_erp_storeid'])){
                     $arr[$key]['button'] = [];
                 }
            }
        }
        $list['collect_num'] = $collect_num;//必须放在最后不影响循环
        if ($param['use_type'] == 1) {
            $list['list'] = $arr;
            $list['status_num'] = $this->getNumberCopy($param);
            $list['count'] = $list['status_num'][$now_status - 1]['num'];
        } else {
            $arr = $this->formatExportChildren(array_values($arr));
            $list = $arr;
        }
        return $list;
    }

    /**
     * @param $list
     * @param $page
     * @param $pageSize
     * @return array
     * 分页处理
     */
    public function pageDeal($list, $page, $pageSize)
    {
        return array_slice($list, ($page - 1) * $pageSize, $pageSize);
    }

    /**
     * @param $arr
     * @param $param
     * 支付信息获取
     */
    public function getPayInfo($arr, $param)
    {
        //支付方式搜索
        if (!empty($param['pay'])) {
            switch ($param['pay']) {
                case 'wechat':
                    $tmp = ['pay_type', '=', 'wechat'];
                    break;
                case 'alipay':
                    $tmp = ['pay_type', '=', 'alipay'];
                    break;
                default:
                    $tmp = [];
            }
        }
        if ($param['search_type'] == 2) {
            $tmp1 = ['paid_extra', '=', $param['content']];
        }
        foreach ($arr as $key => $val) {
            if (stripos($val['goods_activity_type'], 'prepare') !== false) {
                $where = [['orderid', '=', $val['pre_pay_orderno']]];
            } else {
                $where = [['orderid', '=', $val['pay_orderno']]];
            }
            if (!empty($tmp)) {
                array_push($where, $tmp);
            }
            if (!empty($tmp1)) {
                array_push($where, $tmp1);
            }
            $payInfo = (new PayOrderInfo())->getOne($where);
            if (!empty($payInfo)) {
                $arr[$key]['pay_type'] = $payInfo['pay_type'];
                $arr[$key]['current_score_deducte'] = $payInfo['current_score_deducte'];
                $arr[$key]['current_system_balance'] = $payInfo['current_system_balance'];
                $arr[$key]['current_merchant_give_balance'] = $payInfo['current_merchant_give_balance'];
                $arr[$key]['current_merchant_balance'] = $payInfo['current_merchant_balance'];
                $arr[$key]['current_qiye_balance'] = $payInfo['current_qiye_balance'];
            } else {
                $arr[$key]['pay_type'] = '';
                $arr[$key]['current_score_deducte'] = '';
                $arr[$key]['current_system_balance'] = '';
                $arr[$key]['current_merchant_give_balance'] = '';
                $arr[$key]['current_merchant_balance'] = '';
                $arr[$key]['current_qiye_balance'] = '';
            }
        }
        return $arr;
    }

    /**
     * @param $arr
     * @param $now_status
     * @return mixed
     * 处理退款信息
     */
    public function getTkInfo($now_status, $param, $is_all = 0)
    {
        $style = 10;//10标识退款的where条件
        $where_all = $this->getWhere($param, $style);
        if(isset($param['provinceId']) && $param['provinceId']){
            $where_all[] = ['s.province_id','=',$param['provinceId']];
        }
        if(isset($param['cityId']) && $param['cityId']){
            $where_all[] = ['s.city_id','=',$param['cityId']];
        }
        if(isset($param['areaId']) && $param['areaId']){
            $where_all[] = ['s.area_id','=',$param['areaId']];
        }
        if(isset($param['streetId']) && $param['streetId']){
            $where_all[] = ['s.street_id','=',$param['streetId']];
        }
        if ($now_status == 7) {
            //售后中的退款信息
            $where1 = [['r.status', '=', 0]];
            if (!empty($where_all)) {
                array_push($where1, $where_all);
            }
            $refund_info = $this->mallOrderRefundService->getAllRefund($where1, 'o.*,r.refund_id,r.create_time as refund_create_time,r.order_no,r.type,r.status,r.reason,r.error_reason,r.audit_time,r.voucher,r.refund_money,r.refund_nums,r.is_all,mr.name as mer_name,s.name as store_name', $param['page'], $param['pageSize']);
        } else if ($now_status == 8) {
                //时间搜索
            $where1 = [['r.status', '=', 1]];
            if (!empty($param['begin_time']) && !empty($param['end_time'])) {
                if ($param['search_time_type'] == 'refund_time') {//退款时间
                    $where1 = [['r.status', '=', 1],['r.create_time', '<=', strtotime($param['end_time'] . ' 23:59:59')], ['r.create_time', '>=', strtotime($param['begin_time'] . ' 00:00:00')]];
                }
            }

            if (!empty($where_all)) {
                array_push($where1, $where_all);
            }
            $refund_info = $this->mallOrderRefundService->getAllRefund($where1, 'o.*,o.order_no as real_order_no,r.refund_id,r.create_time as refund_create_time,r.order_no,r.type,r.status,r.reason,r.error_reason,r.audit_time,r.voucher,r.refund_money,r.refund_nums,r.is_all,mr.name as mer_name,s.name as store_name', $param['page'], $param['pageSize']);
        } else {
            //所有的退款信息
            if (!empty($param['begin_time']) && !empty($param['end_time'])) {
                if ($param['search_time_type'] == 'refund_time') {//退款时间
                    $where1 = [['r.status', '=', 1],['r.create_time', '<=', strtotime($param['end_time'] . ' 23:59:59')], ['r.create_time', '>=', strtotime($param['begin_time'] . ' 00:00:00')]];
                }
            }else{
                $where1 = [['r.status', '<=', 1]];
                if ($is_all == 1) {
                    $where1 = [['r.status', '<=', 1], ['r.is_all', '=', 1]];
                }
            }

            if (!empty($where_all)) {
                array_push($where1, $where_all);
            }
            $refund_info = $this->mallOrderRefundService->getAllRefund($where1, 'o.*,r.refund_id,r.create_time as refund_create_time,r.order_no,r.type,r.status,r.reason,r.error_reason,r.audit_time,r.voucher,r.refund_money,r.refund_nums,r.is_all,mr.name as mer_name,s.name as store_name');
        }
        return $refund_info;
    }

    /**
     * @param $arr
     * @param $now_status
     * @return mixed
     * 处理退款信息
     */
    public function getTkInfoByOrderDetail($now_status, $param, $is_all = 0)
    {
        $style = 10;//10标识退款的where条件
        $where_all = $this->getWhere($param, $style);
        foreach ($where_all as $key=>$item){
            if($item[0]=='s.name'){
                $where_all[$key][0] = 'ms.name';
            }
        }
        if ($now_status == 7) {
            //售后中的退款信息
            $where1 = [['r.status', '=', 0]];
            if (!empty($where_all)) {
                array_push($where1, $where_all);
            }
            $refund_info = $this->mallOrderRefundService->getAllRefundByOrderDetail($where1, 'md.goods_id,md.name as goods_name,md.sku_info,md.price,o.*,r.refund_id,r.create_time as refund_create_time,r.order_no,r.type,r.status,r.reason,r.error_reason,r.audit_time,r.voucher,r.refund_money,r.refund_nums as num,r.is_all,mr.name as mer_name,ms.name as store_name', $param['page'], $param['pageSize']);
        } else if ($now_status == 8) {
            $where1 = [['r.status', '=', 1]];
            if (!empty($where_all)) {
                array_push($where1, $where_all);
            }
            $refund_info = $this->mallOrderRefundService->getAllRefundByOrderDetail($where1, 'md.goods_id,md.name as goods_name,md.sku_info,o.*,o.order_no as real_order_no,r.refund_id,r.create_time as refund_create_time,r.order_no,r.type,r.status,r.reason,r.error_reason,r.audit_time,r.voucher,r.refund_money,r.refund_nums as num,r.is_all,mr.name as mer_name,ms.name as store_name', $param['page'], $param['pageSize']);
        } else {
            //所有的退款信息
            $where1 = [['r.status', '<=', 1]];
            if ($is_all == 1) {
                $where1 = [['r.status', '<=', 1], ['r.is_all', '=', 1]];
            }
            if (!empty($where_all)) {
                array_push($where1, $where_all);
            }
            $refund_info = $this->mallOrderRefundService->getAllRefundByOrderDetail($where1, 'md.goods_id,md.name as goods_name,md.sku_info,o.*,r.refund_id,r.create_time as refund_create_time,r.order_no,r.type,r.status,r.reason,r.error_reason,r.audit_time,r.voucher,r.refund_money,r.refund_nums as num,r.is_all,mr.name as mer_name,ms.name as store_name');
        }
        return $refund_info;
    }

    public function getWhere($param, $style = 0)
    {
        if ($style == 4 || $style == 10 || $style == 7) {
            $where = [['o.order_id', '<>', '']];
        } else {
            $where = [['o.order_id', '<>', ''], ['o.goods_activity_type', '<>', 'periodic']];
        }
        if ($param['re_type'] == 'platform') {
            //平台后台的搜索参数
            if (!empty($param['mer_id'])) {
                $tmp = ['o.mer_id', '=', $param['mer_id']];
                array_push($where, $tmp);
            }
            if (!empty($param['store_id'])) {
                $tmp = ['o.store_id', '=', $param['store_id']];
                array_push($where, $tmp);
            }
            if (!empty($param['province_id'])) {
                $tmp = ['o.province_id', '=', $param['province_id']];
                array_push($where, $tmp);
            }
            if (!empty($param['city_id'])) {
                $tmp = ['o.city_id', '=', $param['city_id']];
                array_push($where, $tmp);
            }
            if (!empty($param['area_id'])) {
                $tmp = ['o.area_id', '=', $param['area_id']];
                array_push($where, $tmp);
            }
            if (!empty($param['store_name'])) {
                $tmp = ['s.name', '=', $param['store_name']];
                array_push($where, $tmp);
            }
        } elseif ($param['re_type'] == 'merchant') {
            //商家后台搜索的参数
            if (empty($param['store_id'])) {
                throw new \think\Exception('缺少store_id参数');
            }
            if (empty($param['mer_id'])) {
                throw new \think\Exception('缺少mer_id参数');
            }
            array_push($where, ['o.mer_id', '=', $param['mer_id']], ['o.store_id', '=', $param['store_id']]);
        } elseif ($param['re_type'] == 'storestaff') {
            if (empty($param['store_id'])) {
                throw new \think\Exception('缺少store_id参数');
            }
            array_push($where, ['o.store_id', '=', $param['store_id']]);
        } else {
            throw new \think\Exception('参数错误');
        }
        //发货方式
        if (!empty($param['express_type'])) {
            
            $where = array_merge($where, [['o.express_style', '=', $param['express_type']]]);
        }
        //内容搜索
        if (!empty($param['content'])) {
            switch ($param['search_type']) {
                case '1':
                    if ($param['type'] == 'pc') {
                        if ($style == 5 || $style == 6) {
                            $tmp = [];
                        } elseif ($style == 10 || $style == 7) {
                            $tmp = ['r.order_no', '=', $param['content']];
                        } else {
                            $get_order=(new MallOrderRefund())->getOne(['order_no'=>$param['content'],'status'=>1]);
                            if(empty($get_order)){
                                $tmp = ['o.order_no', '=', $param['content']];
                            }else{
                                $get_order=$get_order->toArray();
                                $tmp = ['o.order_id', '=', $get_order['order_id']];
                            }

                        }
                    } else {
                        if ($style == 5 || $style == 6) {
                            $tmp = [];
                        } elseif ($style == 10 || $style == 7) {
                            $tmp = ['r.order_no', '=', $param['content']];
                        } else {
                            $tmp = ['o.order_no', 'exp', Db::raw('like ' . '"%' . $param['content'] . '%"' . ' OR o.phone like ' . '"%' . $param['content'] . '%" ' . 'OR o.username like ' . '"%' . $param['content'] . '%"')];
                        }
                    }
                    break;
                case '2':
                    $pay_order_info = (new PayOrderInfo())->where(['paid_extra'=>$param['content']])->value('orderid');
                    $tmp = ['o.pay_orderno', '=', ($pay_order_info?:0)];//第三方支付号？？？
                    break;
                case '3':
                    $tmp = ['o.username', 'like', '%' . $param['content'] . '%'];
                    break;
                case '4':
                    $tmp = ['o.phone', 'like', '%' . $param['content'] . '%'];
                    break;
				case '5':
					//商品名称模糊搜索先获得订单号，然后再用in查询订单号
					$orderIds = $this->MallOderDatail->geOrderMsg([['name', 'like', '%' . $param['content'] . '%']], 'order_id');
                    $tmp = ['o.order_id', 'in', array_column($orderIds, 'order_id')];
                    break;
                default:
                    $tmp = [];
            }
            if (!empty($tmp)) {
                array_push($where, $tmp);
            }
        }
        if ($param['type'] == 'pc') {
            //时间搜索
            if (!empty($param['begin_time']) && !empty($param['end_time'])) {
                if ($param['search_time_type']=='refund_time'){//退款时间
                    $tmp = [['r.create_time', '<=', strtotime($param['end_time'] . ' 23:59:59')], ['r.create_time', '>=', strtotime($param['begin_time'] . ' 00:00:00')]];
                }elseif($param['search_time_type']){
                    $tmp = [['o.'.$param['search_time_type'], '<=', strtotime($param['end_time'] . ' 23:59:59')], ['o.'.$param['search_time_type'], '>=', strtotime($param['begin_time'] . ' 00:00:00')]];
                }else{
                    $tmp = [['o.create_time', '<=', strtotime($param['end_time'] . ' 23:59:59')], ['o.create_time', '>=', strtotime($param['begin_time'] . ' 00:00:00')]];
                }
                $where = array_merge($where, $tmp);
            }
            //活动搜索
            if (!empty($param['act'])) {
                switch ($param['act']) {
                    //砍价：bargain；拼团：group；限时：limited；预售：prepare；周期购：periodic；N元N价：reached；
                    //满包邮 :shipping;满赠 ：give；满减：minus；满折：discount
                    case 'bargain':
                        $tmp = ['o.goods_activity_type', '=', 'bargain'];
                        break;
                    case 'group':
                        $tmp = ['o.goods_activity_type', '=', 'group'];
                        break;
                    case 'limited':
                        $tmp = ['o.goods_activity_type', '=', 'limited'];
                        break;
                    case 'prepare':
                        $tmp = ['o.goods_activity_type', '=', 'prepare'];
                        break;
                    case 'periodic':
                        $tmp = ['o.goods_activity_type', '=', 'periodic'];
                        break;
                    case 'ordinary':
                        $tmp = ['o.goods_activity_type', 'not in', ['periodic', 'prepare', 'limited', 'group', 'bargain']];
                        break;
                    default:
                        $tmp = [];
                }
                if (!empty($tmp)) {
                    array_push($where, $tmp);
                }
            }
            //支付方式搜索
            if (!empty($param['pay'])) {
                switch ($param['pay']) {
                    case 'all':
                        $tmp = [];
                        break;
                    case 'wechat':
                        $where[] = ['o.online_pay_type', '=', 'wechat'];
                        break;
                    case 'alipay':
                        $where[] = ['o.online_pay_type', '=', 'alipay'];
                        break;
                    case 'platform_balance':
                        $where[] = ['o.money_system_balance', '<>', '0.00'];
                        break;
                    case 'merchant_balance':
                        $where[] = ['o.money_merchant_balance', 'exp', Db::raw('<> 0.00 OR o.money_merchant_give_balance <> 0.00')];
                        break;
                    case 'employee_money_pay':
                        $where[] = ['o.employee_balance_pay', '<>', '0.00'];
                        break;
                    case 'employee_score_pay':
                        $where[] = ['o.employee_score_pay', '<>', '0.00'];
                        break;
                    default:
                        $where[] = ['o.online_pay_type', '=', 'undefind'];//未开发的支付方式标识
                }
                if (!empty($tmp)) {
                    array_push($where, $tmp);
                }
            }
            //订单来源搜索
            if (!empty($param['source'])) {
                switch ($param['source']) {
                    case 'androidapp':
                        $tmp = ['o.source', '=', 'androidapp'];
                        break;
                    case 'iosapp':
                        $tmp = ['o.source', '=', 'iosapp'];
                        break;
                    case 'wechat_mini':
                        $tmp = ['o.source', '=', 'wechat_mini'];
                        break;
                    case 'wechat_h5':
                        $tmp = ['o.source', '=', 'wechat_h5'];
                        break;
                    case 'h5':
                        $tmp = ['o.source', '=', 'h5'];
                        break;
                    default:
                        $tmp = [];
                }
                if (!empty($tmp)) {
                    array_push($where, $tmp);
                }
            }
        }

        //订单状态搜索
        if ($style != 2 && $style != 4 && $style != 6 && $style != 7) {
            if (!empty($param['status'])) {
                switch ($param['status']) {
                    case '1':
                        $tmp = [];
                        break;
                    case '2':
                        $tmp = [['o.status', '>=', 0], ['o.status', '<', 10]];
                        break;
                    case '3':
                        $tmp = [['o.status', '>=', 10], ['o.status', '<', 20]];
                        break;
                    case '4':
                        $tmp = [['o.status', '>=', 20], ['o.status', '<', 30]];
                        break;
                    case '5':
                        $tmp = [['o.status', '>=', 30], ['o.status', '<', 50]];
                        break;
                    case '6':
                        $tmp = [['o.status', '>=', 50], ['o.status', '<', 60]];
                        break;
                    case '7':
                        $tmp = [];
                        break;
                    case '8':
                        $tmp = [];
                        break;
                    default:
                        $tmp = [];
                }
                if (!empty($tmp)) {
                    $where = array_merge($where, $tmp);
                }
            }
        }
        //核销方式搜索
        if (isset($param['verify']) && !empty($param['verify'])) {
            $tmp = [];
            if($param['verify'] != 'all'){
                $verifyStatusAry = explode(',',$param['verify']);
                $tmp = count($verifyStatusAry) >1 ? [['o.verify_status', 'IN', $verifyStatusAry]] : [['o.verify_status', '=', $param['verify']]];
            }
            if (!empty($tmp)) {
                $where = array_merge($where, $tmp);
            }
        }
        return $where;
    }

    public function getWhereCopy($param, $style = 0)
    {
        if (($param['status'] == 1 ||  $param['status'] == 2 || $param['status'] == 3 || $param['status'] == 5)) {
            $where = [['o.status', '>=', 0]];//修改为查询全部
//            $where = [['o.status', '>=', 0], ['o.status', '<', 50]];
        } else{
            if($param['type']=='wap'){
                $where = [['o.refund_status', '=', 0]];
            }else{
                if($param['act']!='periodic' && $param['act']!='all'){
                    $where = [['o.refund_status', '=', 0], ['o.goods_activity_type', '<>', 'periodic']];
                }else{
                    $where = [['o.refund_status', '=', 0]];
                }
            }
        }
        if(!empty($param['content'])){
            $where = [['o.status', '>=', 0]];
        }
        
        if ($param['re_type'] == 'platform') {
            //平台后台的搜索参数
            if (!empty($param['mer_id'])) {
                $tmp = ['o.mer_id', '=', $param['mer_id']];
                array_push($where, $tmp);
            }
            if (!empty($param['store_id'])) {
                $tmp = ['o.store_id', '=', $param['store_id']];
                array_push($where, $tmp);
            }
            if (!empty($param['province_id'])) {
                $tmp = ['o.province_id', '=', $param['province_id']];
                array_push($where, $tmp);
            }
            if (!empty($param['city_id'])) {
                $tmp = ['o.city_id', '=', $param['city_id']];
                array_push($where, $tmp);
            }
            if (!empty($param['area_id'])) {
                $tmp = ['o.area_id', '=', $param['area_id']];
                array_push($where, $tmp);
            }
            if (!empty($param['store_name'])) {
                $tmp = ['s.name', '=', $param['store_name']];
                array_push($where, $tmp);
            }
        } elseif ($param['re_type'] == 'merchant') {
            //商家后台搜索的参数
            if (empty($param['store_id'])) {
                throw new \think\Exception('缺少store_id参数');
            }
            if (empty($param['mer_id'])) {
                throw new \think\Exception('缺少mer_id参数');
            }
            array_push($where, ['o.mer_id', '=', $param['mer_id']], ['o.store_id', '=', $param['store_id']]);
        } elseif ($param['re_type'] == 'storestaff') {
            if (empty($param['store_id'])) {
                throw new \think\Exception('缺少store_id参数');
            }
            array_push($where, ['o.store_id', '=', $param['store_id']]);
        } else {
            throw new \think\Exception('参数错误');
        }
        //发货方式
        if (!empty($param['express_type'])) {
            $where = array_merge($where, [['o.express_style', '=', $param['express_type']]]);
        }
        //内容搜索
        if (!empty($param['content'])) {
            switch ($param['search_type']) {
                case '1':
                    if ($param['type'] == 'pc') {
                        if ($style == 5 || $style == 6) {
                            $tmp = [];
                        } elseif ($style == 10 || $style == 7) {
                            $tmp = ['r.order_no', '=', $param['content']];
                        } else {
                            $tmp = ['o.order_no', '=', $param['content']];
                        }
                    } else {
                        if ($style == 5 || $style == 6) {
                            $tmp = [];
                        } elseif ($style == 10 || $style == 7) {
                            $tmp = ['r.order_no', '=', $param['content']];
                        } else {
                            $tmp = ['o.order_no', 'exp', Db::raw('like ' . '"%' . $param['content'] . '%"' . ' OR o.phone = ' . '"' . $param['content'] . '" ' . 'OR o.username like ' . '"%' . $param['content'] . '%"')];
                        }
                    }
                    break;
                case '2':
                    $pay_order_info = (new PayOrderInfo())->where(['paid_extra'=>$param['content']])->value('orderid');
                    $tmp = ['o.pay_orderno', '=', ($pay_order_info?:0)];//第三方支付号？？？
                    break;
                case '3':
                    $tmp = ['o.username', 'like', '%' . $param['content'] . '%'];
                    break;
                case '4':
                    $tmp = ['o.phone', 'like', '%' . $param['content'] . '%'];
                    break;
                case '5':
                    //商品名称模糊搜索先获得订单号，然后再用in查询订单号
                    $orderIds = $this->MallOderDatail->geOrderMsg([['name', 'like', '%' . $param['content'] . '%']], 'order_id');
                    $tmp = ['o.order_id', 'in', array_column($orderIds, 'order_id')];
                    break;
                default:
                    $tmp = [];
            }
            if (!empty($tmp)) {
                array_push($where, $tmp);
            }
        }
        if ($param['type'] == 'pc') {
            //时间搜索
            if (!empty($param['begin_time']) && !empty($param['end_time'])) {
                if ($param['search_time_type']=='refund_time'){//退款时间
                    $tmp=[];
                }elseif($param['search_time_type']){
                    $tmp = [['o.'.$param['search_time_type'], '<=', strtotime($param['end_time'] . ' 23:59:59')], ['o.'.$param['search_time_type'], '>=', strtotime($param['begin_time'] . ' 00:00:00')]];
                }else{
                    $tmp = [['o.create_time', '<=', strtotime($param['end_time'] . ' 23:59:59')], ['o.create_time', '>=', strtotime($param['begin_time'] . ' 00:00:00')]];
                }
                $where = array_merge($where, $tmp);
            }
            //活动搜索
            if (!empty($param['act'])) {
                switch ($param['act']) {
                    //砍价：bargain；拼团：group；限时：limited；预售：prepare；周期购：periodic；N元N价：reached；
                    //满包邮 :shipping;满赠 ：give；满减：minus；满折：discount
                    case 'bargain':
                        $tmp = ['o.goods_activity_type', '=', 'bargain'];
                        break;
                    case 'group':
                        $tmp = ['o.goods_activity_type', '=', 'group'];
                        break;
                    case 'limited':
                        $tmp = ['o.goods_activity_type', '=', 'limited'];
                        break;
                    case 'prepare':
                        $tmp = ['o.goods_activity_type', '=', 'prepare'];
                        break;
                    case 'periodic':
                        $tmp = ['o.goods_activity_type', '=', 'periodic'];
                        break;
                    case 'ordinary':
                        $tmp = ['o.goods_activity_type', 'not in', ['periodic', 'prepare', 'limited', 'group', 'bargain']];
                        break;
                    default:
                        $tmp = [];
                }
                if (!empty($tmp)) {
                    array_push($where, $tmp);
                }
            }
            //支付方式搜索
            if (!empty($param['pay'])) {
                switch ($param['pay']) {
                    case 'all':
                        $tmp = [];
                        break;
                    case 'wechat':
                        $where[] = ['o.online_pay_type', '=', 'wechat'];
                        break;
                    case 'alipay':
                        $where[] = ['o.online_pay_type', '=', 'alipay'];
                        break;
                    case 'platform_balance':
                        $where[] = ['o.money_system_balance', '<>', '0.00'];
                        break;
                    case 'money_merchant_balance':
                        $where[] = ['o.money_merchant_balance', '<>', '0.00'];
                        break;
                    case 'employee_money_pay':
                        $where[] = ['o.employee_balance_pay', '<>', '0.00'];
                        break;
                    case 'employee_score_pay':
                        $where[] = ['o.employee_score_pay', '<>', '0.00'];
                        break;
		    case 'card':
                        $where[] = ['o.internal_card_pay', 'not null'];
                        $where[] = ['o.internal_card_pay', '<>', '[]'];
                        break;
                    default:
                        $where[] = ['o.online_pay_type', '=', 'undefind'];//未开发的支付方式标识
                }
                if (!empty($tmp)) {
                    array_push($where, $tmp);
                }
            }
            //订单来源搜索
            if (!empty($param['source'])) {
                switch ($param['source']) {
                    case 'androidapp':
                        $tmp = ['o.source', '=', 'androidapp'];
                        break;
                    case 'iosapp':
                        $tmp = ['o.source', '=', 'iosapp'];
                        break;
                    case 'wechat_mini':
                        $tmp = ['o.source', '=', 'wechat_mini'];
                        break;
                    case 'wechat_h5':
                        $tmp = ['o.source', '=', 'wechat_h5'];
                        break;
                    case 'h5':
                        $tmp = ['o.source', '=', 'h5'];
                        break;
                    default:
                        $tmp = [];
                }
                if (!empty($tmp)) {
                    array_push($where, $tmp);
                }
            }
        }

        //订单状态搜索
        if ($style != 2 && $style != 4 && $style != 6 && $style != 7) {
            if (!empty($param['status'])) {
                switch ($param['status']) {
                    case '1':
                        $tmp = [];
                        break;
                    case '2':
                        $tmp = [['o.status', '>=', 0], ['o.status', '<', 10]];
                        break;
                    case '3':
                        $tmp = [['o.status', '>=', 10], ['o.status', '<', 20]];
                        break;
                    case '4':
                        $tmp = [['o.status', '>=', 20], ['o.status', '<', 30]];
                        break;
                    case '5':
                        $tmp = [['o.status', '>=', 30], ['o.status', '<', 50]];
                        break;
                    case '6':
                        $tmp = [['o.status', '>=', 50], ['o.status', '<', 60]];
                        break;
                    case '7':
                        $tmp = [];
                        break;
                    case '8':
                        $tmp = [];
                        break;
                    default:
                        $tmp = [];
                }
                if (!empty($tmp)) {
                    $where = array_merge($where, $tmp);
                }
            }
        }
        //核销方式搜索
        if (isset($param['verify']) && !empty($param['verify'])) {
            if($param['verify'] != 'all'){
                $verifyStatusAry = explode(',',$param['verify']);
                $tmp = count($verifyStatusAry) >1 ? [['o.verify_status', 'IN', $verifyStatusAry]] : [['o.verify_status', '=', $param['verify']]];
            }
            if (!empty($tmp)) {
                $where = array_merge($where, $tmp);
            }
        }
        //查询是否有最新订单id的限制
        if(isset($param['first_order_id']) && $param['first_order_id']){
            $where = array_merge($where, [['o.order_id','<=',$param['first_order_id']]]);
        }
        return $where;
    }

    /**
     * 获取订单详情
     * @param $order_id
     * @return array
     * @throws \think\Exception
     */
    public function getOrderDetails($order_id, $periodic_order_id = '', $re_type = '', $refund_id = '')
    {
        if (empty($order_id)) {
            throw new \think\Exception('缺少order_id参数');
        }
        $orderDetailService = new MallOrderDetailService();
        $field = 'o.freight_distance,o.address_lng,o.address_lat,o.address_id,o.online_pay_type,o.order_id,o.goods_activity_type,o.mer_id,o.store_id,o.money_freight,o.order_no,o.uid,o.create_time,o.send_time,o.complete_time,o.address,o.username,o.phone as sh_phone,u.phone as zc_phone,o.express_send_time,o.express_style,o.money_total,o.discount_system_coupon,o.discount_merchant_coupon,o.discount_merchant_card,o.discount_system_level,o.discount_clerk_money,o.discount_act_money,o.money_real,o.money_score,o.money_system_balance,o.money_merchant_balance,o.money_merchant_give_balance,o.money_qiye_balance,o.money_online_pay,o.status as order_status,o.pay_orderno,o.pre_pay_orderno,o.remark,o.clerk_remark,o.express_name,o.express_num,o.express_id,o.activity_type,o.pay_time,o.staff_name,o.employee_score_pay,o.employee_balance_pay,o.money_refund';
        $where = ['o.order_id' => $order_id];
        $arr = $this->MallOderModel->getDetail($where, $field);
        if (!empty($arr)) {
            //支付信息
            if ($arr['goods_activity_type'] == 'prepare' && empty($arr['pay_orderno'])) {
                $where = ['business' => 'mall', 'orderid' => $arr['pre_pay_orderno']];
            } else {
                $where = ['business' => 'mall', 'orderid' => $arr['pay_orderno']];
            }
            $payInfo = (new PayOrderInfo())->getOne($where);
            $arr = array_merge($arr, $payInfo);
            //总优惠金额
            $clerkDiscount = $arr['discount_clerk_money'] ? explode(',', $arr['discount_clerk_money']) : '';
            if (!empty($clerkDiscount)) {
                $arr['discount_clerk_money'] = $clerkDiscount[0];
            } else {
                $arr['discount_clerk_money'] = 0.00;
            }
            $arr['store_name']=(new MerchantStore())->getOne(['store_id'=>$arr['store_id']])['name'];
            $arr['discount_total'] = (float)$arr['discount_system_coupon'] + (float)$arr['discount_merchant_coupon'] + (float)$arr['discount_merchant_card'] + (float)$arr['discount_system_level'] + (float)$arr['discount_clerk_money'] + (float)$arr['discount_act_money'];
            $arr['discount_total'] = number_format($arr['discount_total'], 2);
            //处理时间
            $arr['create_time'] = date('Y-m-d H:i:s', $arr['create_time']);
            $arr['send_time'] = $arr['send_time'] ? date('Y-m-d H:i:s', $arr['send_time']) : '';
            $arr['paid_time'] = $arr['pay_time'] ? date('Y-m-d H:i:s', $arr['pay_time']) : '';
            $arr['complete_time'] = $arr['complete_time'] ? date('Y-m-d H:i:s', $arr['complete_time']) : '';
            //处理店员优惠前后的金额展示
            $discount_list = array();
            $this_money = $arr['money_real'];
            if (!empty($clerkDiscount)) {
                foreach ($clerkDiscount as $key => $val) {
                    if ($val) {
                        $discount_list[] = [
                            'money_before' => number_format((float)$this_money + (float)$val, 2),
                            'money_after' => number_format((float)$this_money, 2)
                        ];
                        $this_money = number_format((float)$this_money + (float)$val, 2);
                    }
                }
                $discount_list = array_reverse($discount_list);
                $arr['clert_discount_process'] = $discount_list;
            }
            $field = 'id,goods_id,name as goods_name,image,sku_info,num,price,status as goods_status,forms,notes';
            $info = $orderDetailService->getByOrderId($field, $arr['order_id']);
            $arr['delivery'] = [];
            //周期购和预售特殊字段的处理
            //周期购的处理 期数
            if (stripos($arr['goods_activity_type'], 'periodic') !== false) {
                if (!empty($periodic_order_id)) {
                    $periodic = $this->mallActivityService->returnNowPeriodicOrderDetail($periodic_order_id);//邓远辉提供的接口
                    if (!empty($periodic)) {
                        $arr['periodic_order_id'] = $periodic_order_id;
                        $arr['periodic_count'] = $periodic['periodic_count'];
                        $arr['current_periodic'] = $periodic['current_periodic'];
                        $arr['current_time'] = date('Y-m-d H:i:s', $periodic['periodic_date']);//周期购订单期望到达时间
                        $arr['order_status'] = $periodic['is_complete'];
                        if ($periodic['periodic_count'] === 0) {
                            throw new \think\Exception('周期购的期数不能为0');
                        }
                        if (!empty($periodic['deliver'])) {
                            $arr['express_name'] = $periodic['deliver']['express_name'];
                        }
                    }
                }
                $periodic = $this->mallActivityService->returnNowPeriodicOrderAndActBefore($order_id, 0);
                $col = 0;
                if (!empty($periodic)) {//copy from getOrderDetailsCopy
                    foreach ($periodic as $kk => $vv) {
                        $periodic[$kk]['button'] = [
                            'take_btn' => 0,
                            'hoseman_btn' => 0,
                            'clerk_btn' => 0,
                            'express_btn' => 0,
                            'logistics_btn' => 0,
                            'postpone_btn' => 0,
                            'trajectory_btn' => 0,
                            'agree_refund_btn' => 0,
                            'refuse_refund_btn' => 0
                        ];
                        if ($vv['is_complete'] == 10) {
                            if ($re_type == 'storestaff') {
                                $periodic[$kk]['button']['take_btn'] = 1;
                                $periodic[$kk]['button']['postpone_btn'] = 1;
                            }
                        } elseif ($vv['is_complete'] == 11) {
                            if ($arr['express_style'] == 1) {
                                $periodic[$kk]['button']['hoseman_btn'] = 1;
                            } elseif ($arr['express_style'] == 3) {
                                $periodic[$kk]['button']['clerk_btn'] = 1;
                            } elseif ($arr['express_style'] == 2) {
                                $periodic[$kk]['button']['express_btn'] = 1;
                            } else {
                                throw new \think\Exception('发货方式错误');
                            }
                        } elseif ($vv['is_complete'] == 20) {
                            if ($arr['express_style'] == 2) {
                                $periodic[$kk]['button']['logistics_btn'] = 1;
                            } elseif ($arr['express_style'] == 1) {
                                $periodic[$kk]['button']['trajectory_btn'] = 1;
                            }
                        } elseif ($vv['is_complete'] == 30) {
                            if ($arr['express_style'] == 2) {
                                $periodic[$kk]['button']['logistics_btn'] = 1;
                            } elseif ($arr['express_style'] == 1) {
                                $periodic[$kk]['button']['trajectory_btn'] = 1;
                            }
                        }
                        $periodic[$kk]['delivery'] = (new MallOrderService())->orderDeliveryList($order_id, $periodic[$kk]['purchase_order_id']);
                        $periodic[$kk]['index'] = $col++;
                        $periodic[$kk]['periodic_date'] = $vv['periodic_date'] ? date('Y-m-d H:i', $vv['periodic_date']) : '';
                        $info[0]['image'] = replace_file_domain($info[0]['image']);
                        $periodic[$kk] = array_merge($periodic[$kk], $info[0]);
                    }
                    $arr['periodic_info'] = $periodic;
                }
            }else{
                $arr['delivery'] = $this->orderDeliveryList($order_id);
            }

            //退款
            $arr['refund_money'] = $arr['money_refund']?:0.00;
            $arr['refund_reason'] = '';
            $arr['refund_images'] = [];
            $arr['refund_money_periodic'] = 0.00;
            $arr['refund_count_periodic'] = 0;
            $arr['all_refund'] = false;
            $arr['is_refund'] = false;
            $arr['part_refund_money'] = $arr['money_refund']?:0.00;
            if($refund_id > 0){
                $refund_info = (new MallOrderRefund)->getOne([['refund_id', '=', $refund_id], ['status', '<=', 1]], true);
            }else{
                $refund_info = (new MallOrderRefund)->getOne([['order_id', '=', $order_id], ['status', '<=', 1]], true,'refund_id desc');
            }
            $arr['real_order_no'] = '';
            if (!empty($refund_info)) {
                $refund_info = $refund_info->toArray();
                $arr['refund_id'] = $refund_info['refund_id'];
                $arr['is_all'] = $refund_info['is_all'];
                $arr['type'] = $refund_info['type'];
                $arr['refund_reason'] = $refund_info['reason'];
                $arr['refund_images'] = $refund_info['voucher'] ? explode(',', $refund_info['voucher']) : [];
                if (!empty($arr['refund_images'])) {
                    foreach ($arr['refund_images'] as $ik => $im) {
                        $arr['refund_images'][$ik] = replace_file_domain($im);
                    }
                }
                $arr['refund_time'] = $refund_info['create_time'] ? date('Y-m-d H:i:s', intval($refund_info['create_time'])) : '';
                $arr['refund_money_periodic'] = 0.00;  //周期购退款金额
                $arr['refund_count_periodic'] = 0;  //周期购退款期数
                $arr['all_refund'] = $refund_info['is_all']&&$refund_info['status']==1?1:0;//是全退
                $arr['is_refund'] = true; //存在退款
                if ($refund_id>0) {//部分退款
                    $arr['part_refund_money'] = $refund_info['refund_money'];
                }
                if($arr['order_status']>=50 && $arr['order_status']<60){//已取消的订单不能展示已退款未退款
                    
                } elseif ($refund_info['status'] == 0) {
                    $arr['order_status'] = 60;
                    $arr['real_order_no']=$arr['order_no'];
                    $arr['order_no']=$refund_info['order_no'];
                } elseif ($refund_info['status'] == 1) {
                    $arr['order_status'] = 70;
                    $arr['real_order_no']=$arr['order_no'];
                    $arr['order_no']=$refund_info['order_no'];
                }
            }
            $refundDetailService = new MallOrderRefundDetail();
            //sku的状态展示（sku暂不进行单独操作 以后有需求可以在此扩展）
            if (!empty($refund_id)) {
                $field = 'd.id,d.goods_id,d.name as goods_name,d.image,d.sku_info,num,d.price,d.status as goods_status,d.forms,d.notes,r.refund_nums';
                $where = ['refund_id' => $refund_id];
                $info = $refundDetailService->getJoinData($where, $field);
            } 
            if(empty($info) || (is_object($info) && $info->isEmpty())){
                $field = 'id,goods_id,name as goods_name,image,sku_info,num,price,status as goods_status,forms,notes';
                $orderDetailService = new MallOrderDetailService();
                $info = $orderDetailService->getByOrderId($field, $arr['order_id']);
            }
            $arr['total_num'] = 0;
            if (!empty($info)) {
                foreach ($info as $kk => $vv) {
                    if (!empty($refund_id)) {
                        $vv['goods_status'] = $arr['order_status'];
                        $vv['num'] = $vv['refund_nums'];
                    }
                    if ($arr['goods_activity_type'] == 'periodic') {
                        $vv['goods_status'] = $arr['order_status'];
                    }
                    //如果该笔订单的商品存在退款则要展示出
                    $where_tk = ['d.order_detail_id' => $vv['id']];
                    $vv['refund_desc'] = [];
                    $refund_nums1 = 0;
                    $refund_nums2 = 0;
                    $refund_nums3 = 0;
                    $refund_details = $refundDetailService->getRefundByDetaiId($where_tk);
                    if (!empty($refund_details) && ($arr['order_status']<50 || $arr['order_status']>=60)) {
                        foreach ($refund_details as $refund_detail) {
                            if ($refund_detail['status'] == 0 && $refund_detail['type'] == 1 && $arr['order_status'] != 60 && $arr['order_status'] != 70) {
                                $refund_nums1 += $refund_detail['refund_nums'];
                            } elseif ($refund_detail['status'] == 0 && $refund_detail['type'] == 2 && $arr['order_status'] != 60 && $arr['order_status'] != 70) {
                                $refund_nums2 += $refund_detail['refund_nums'];
                            } elseif ($refund_detail['status'] == 1 && $arr['order_status'] != 60 && $arr['order_status'] != 70) {
                                $refund_nums3 += $refund_detail['refund_nums'];
                            }
                        }
                        if (!empty($refund_nums1)) {
                            $vv['refund_desc'][] = '退款中x' . $refund_nums1;
                        }
                        if (!empty($refund_nums2)) {
                            $vv['refund_desc'][] = '售后中x' . $refund_nums2;
                        }
                        if (!empty($refund_nums3)) {
                            $vv['refund_desc'][] = '已退款x' . $refund_nums3;
                        }
                    }
                    $vv['is_gift'] = 0;
                    //如果该订单的商品是赠品则要展示出
                    if (stripos($arr['activity_type'], 'give') !== false) {
                        if ((new MallFullGiveGiftSku())->getGiftInfo('g.id', [['g.goods_id', '=', $vv['goods_id']], ['a.start_time', '<', time()], ['a.end_time', '>', time()], ['a.status', '<>', 2]])) {
                            $vv['is_gift'] = 1;
                        }
                    }
                    if ($vv['goods_status'] >= 0 && $vv['goods_status'] < 10) {
                        $vv['goods_status_txt'] = '待付款';
                    } elseif ($vv['goods_status'] == 10) {
                        $vv['goods_status_txt'] = '待发货';
                    } elseif ($vv['goods_status'] == 11) {
                        $vv['goods_status_txt'] = '备货中';
                    } elseif ($vv['goods_status'] == 13) {
                        $vv['goods_status_txt'] = '待成团';
                    } elseif ($vv['goods_status'] >= 20 && $vv['goods_status'] < 30) {
                        $vv['goods_status_txt'] = '已发货';
                    } elseif ($vv['goods_status'] >= 30 && $vv['goods_status'] < 50) {//用户端的已收货所对应的店员端的已完成
                        $vv['goods_status_txt'] = '已完成';
                    } elseif ($vv['goods_status'] >= 50 && $vv['goods_status'] < 60) {
                        $vv['goods_status_txt'] = '取消';
                    } elseif ($vv['goods_status'] >= 60 && $vv['goods_status'] < 70) {
                        $vv['goods_status_txt'] = '退款中';
                        if ($refund_info['type'] == 2) {
                            $vv['goods_status_txt'] = '售后中';
                        }
                    } elseif ($vv['goods_status'] >= 70) {
                        $vv['goods_status_txt'] = '已退款';
                    }
                    $vv['image'] = $vv['image'] ? replace_file_domain($vv['image']) : '';
                    $arr['total_num'] += $vv['num'];
                    $arr['children'][] = $vv;
                    $arr['forms'] = json_decode($vv['forms'], true) ?: [];
                    if (!empty($arr['forms'])) {
                        foreach ($arr['forms'] as $k => $v) {
                            if ($v['type'] == 'image' && !empty($v['val'])) {
                                $arr['forms'][$k]['val'] = replace_file_domain($v['val']);
                            }
                        }
                    }
                    $arr['notes'] = json_decode($vv['notes'], true) ?: [];
                }
            } else {
               // throw new \think\Exception('未查询到与该订单有关的商品信息');
            }
            //预售的处理 需付款+已付定金+已付尾款
            if (stripos($arr['goods_activity_type'], 'prepare') !== false) {
                $prefield = 'po.bargain_price,po.rest_price,p.discount_price,po.act_id,po.goods_id,po.uid,po.pay_time';
                $prepare = (new MallPrepareOrder())->getPrepare($prefield, $order_id);
                if (!empty($prepare)) {
                    $arr['bargain_price'] = $prepare['bargain_price'];
                    $arr['rest_price'] = $prepare['rest_price'];
                    $arr['total_num'] = $arr['total_num'] != 0 ? $arr['total_num'] : 1;
                    $arr['deduct_price'] = $prepare['bargain_price'] + $prepare['discount_price'] * $arr['total_num'];
                    $arr['money_total'] = $prepare['rest_price'] + $prepare['bargain_price'];
                    $arr['children'][0]['price'] = ($prepare['rest_price'] + $prepare['bargain_price']) / $arr['total_num'];
                    if ($arr['order_status'] >= 0 && $arr['order_status'] < 10) {
                        $arr['money_real'] = $prepare['rest_price'];
                    } elseif ($arr['order_status'] >= 50 && $arr['order_status'] < 60) {
                        $arr['money_real'] = $prepare['bargain_price'] - $arr['discount_total'];
                    } else {
                        $arr['money_real'] = $prepare['rest_price'] + $prepare['bargain_price'] - $arr['discount_total'];
                    }
                    $arr['discount_price'] = $prepare['discount_price'];
                    $arr['paid_time'] = $prepare['pay_time'] ? date('Y-m-d H:i:s', $prepare['pay_time']) : '';
                    $arr['send_time'] = date('Y-m-d H:i:s', ($this->mallActivityService->getPrepareOrderStatus($prepare['goods_id'], $prepare['uid'], $prepare['act_id'], $order_id))['send_goods_date']);
                } else {
                    //throw new \think\Exception('没有查到该订单的预售信息');
                }
            }
            $arr['pay_type'] = isset($arr['pay_type']) ?? '';
	    
            $payService = new PayService();
            $arr['pay_type_txt'] = $payService->getPayTypeText($arr['online_pay_type'], $arr);

            switch ($arr['online_pay_type']) {
                case 'wechat':
                    $arr['pay_type_txt'] = '微信支付';
                    break;
                case 'alipay':
                    $arr['pay_type_txt'] = '支付宝支付';
                    break;
                case 'douyin':
                    $arr['pay_type_txt'] = '抖音支付';
                    break;
                default:
                    $arr['pay_type_txt'] = '';
            }
            if (!empty($arr['current_score_deducte'])) {
                if (!empty($arr['pay_type_txt'])) {
                    $arr['pay_type_txt'] .= ' + ' . '积分抵扣';
                } else {
                    $arr['pay_type_txt'] = '积分抵扣';
                }
            }
            if (!empty($arr['current_system_balance']) || !empty($arr['current_qiye_balance'])) {
                if (!empty($arr['pay_type_txt'])) {
                    $arr['pay_type_txt'] .= ' + ' . '平台支付';
                } else {
                    $arr['pay_type_txt'] = '平台支付';
                }
            }
            if (!empty($arr['current_merchant_give_balance']) || !empty($arr['current_merchant_balance'])) {
                if (!empty($arr['pay_type_txt'])) {
                    $arr['pay_type_txt'] .= ' + ' . '商家余额支付';
                } else {
                    $arr['pay_type_txt'] = '商家余额支付';
                }
            }

            //订单类型
            if (stripos($arr['goods_activity_type'], 'bargain') !== false) {
                $arr['order_type_txt'] = '砍价订单';
                $arr['order_type'] = 'bargain';
            } elseif (stripos($arr['goods_activity_type'], 'group') !== false) {
                $arr['order_type_txt'] = '拼团订单';
                $arr['order_type'] = 'group';
            } elseif (stripos($arr['goods_activity_type'], 'limited') !== false) {
                $arr['order_type_txt'] = '秒杀订单';
                $arr['order_type'] = 'limited';
            } elseif (stripos($arr['goods_activity_type'], 'prepare') !== false) {
                $arr['order_type_txt'] = '预售订单';
                $arr['order_type'] = 'prepare';
            } elseif (stripos($arr['goods_activity_type'], 'periodic') !== false) {
                $arr['order_type_txt'] = '周期购订单';
                $arr['order_type'] = 'periodic';
            } else {
                $arr['order_type_txt'] = '普通订单';
                $arr['order_type'] = '';
            }

            $arr['button'] = [
                'take_btn' => 0,
                'hoseman_btn' => 0,
                'clerk_btn' => 0,
                'express_btn' => 0,
                'logistics_btn' => 0,
                'postpone_btn' => 0,
                'trajectory_btn' => 0,
                'agree_refund_btn' => 0,
                'refuse_refund_btn' => 0,
            ];
            //店员端的操作
            if ($arr['order_status'] >= 0 && $arr['order_status'] < 10) {
                $arr['order_status_txt'] = '待付款';
                $arr['step_create_time'] = $arr['create_time'];
            } elseif ($arr['order_status'] == 10) {
                $arr['order_status_txt'] = '待发货';
                if ($re_type == 'storestaff') {
                    $arr['button']['take_btn'] = 1;
                }
                $arr['step_create_time'] = $arr['create_time'];
                $arr['step_send_time'] = $arr['send_time'];
                $arr['step_paid_time'] = $arr['paid_time'];
            } elseif ($arr['order_status'] == 11) {
                $arr['order_status_txt'] = '备货中';
                if ($re_type == 'storestaff') {
                    if ($arr['express_style'] == 1) {
                        $arr['button']['hoseman_btn'] = 1;
                    } elseif ($arr['express_style'] == 3) {
                        $arr['button']['clerk_btn'] = 1;
                    } elseif ($arr['express_style'] == 2) {
                        $arr['button']['express_btn'] = 1;
                    } else {
                        throw new \think\Exception('发货方式错误');
                    }
                }
                $arr['step_create_time'] = $arr['create_time'];
                $arr['step_send_time'] = $arr['send_time'];
                $arr['step_paid_time'] = $arr['paid_time'];
            } elseif ($arr['order_status'] == 13) {
                $arr['order_status_txt'] = '待成团';
                $arr['step_create_time'] = $arr['create_time'];
                $arr['step_send_time'] = $arr['send_time'];
                $arr['step_paid_time'] = $arr['paid_time'];
            } elseif ($arr['order_status'] >= 20 && $arr['order_status'] < 30) {
                $arr['order_status_txt'] = '已发货';
                if ($arr['express_style'] == 2) {
                    $arr['button']['logistics_btn'] = 1;
                } elseif ($arr['express_style'] == 1) {
                    $arr['button']['trajectory_btn'] = 1;
                }
                $arr['step_create_time'] = $arr['create_time'];
                $arr['step_send_time'] = $arr['send_time'];
                $arr['step_paid_time'] = $arr['paid_time'];
            } elseif ($arr['order_status'] >= 30 && $arr['order_status'] < 50) {//用户端的已收货所对应的店员端的已完成
                $arr['order_status_txt'] = '已完成';
                if ($arr['express_style'] != 3) {
                    $arr['button']['logistics_btn'] = 1;
                }
                $arr['step_create_time'] = $arr['create_time'];
                $arr['step_send_time'] = $arr['send_time'];
                $arr['step_paid_time'] = $arr['paid_time'];
                $arr['step_complete_time'] = $arr['complete_time'];
            } elseif ($arr['order_status'] >= 50 && $arr['order_status'] < 60) {
                $arr['order_status_txt'] = '取消';
            } elseif ($arr['order_status'] >= 60 && $arr['order_status'] < 70) {
                if ($arr['type'] == 1) {
                    $arr['order_status_txt'] = '退款中';
                } elseif ($arr['type'] == 2) {
                    $arr['order_status_txt'] = '售后中';
                } else {
                    fdump_sql(['val'=>$arr,"msg"=>"退款参数有误"],"mall_return");
                   // throw new \think\Exception('退款参数有误');
                }
                if ($re_type == 'storestaff') {
                    $second = $refund_info['create_time'] + cfg('mall_order_refund_time') * 24 * 60 * 60 - time();
                    $arr['rest_time'] = $second;
                    $arr['button']['agree_refund_btn'] = 1;
                    $arr['button']['refuse_refund_btn'] = 1;
                }
                if ($arr['type'] == 2 && $arr['express_style'] != 3) {
                    $arr['button']['logistics_btn'] = 1;
                }
            } elseif ($arr['order_status'] >= 70) {
                $arr['order_status_txt'] = '已退款';
                $arr['button']['logistics_btn'] = 1;
            }
            //将数字转化为对应的文字
            switch ($arr['express_style']) {
                case 1:
                    $arr['express_style_txt'] = '骑手配送';
                    $time = explode(',', $arr['express_send_time']);
                    $arr['express_send_time'] = date('Y-m-d', $time[0]) . ' ' . date('H:i', $time[0]) . '-' . date('H:i', $time[1]);
                    break;
                case 3:
                    $arr['express_style_txt'] = '自提';
                    break;
                case 2:
                    $arr['express_style_txt'] = '快递配送';
                    break;
            }
            //获取自提信息
            $arr['take_address'] = '';
            if($arr['express_style']==3){
                $arr['take_address'] = (new MerchantStore())->getOne(['store_id'=>$arr['store_id']])['adress']??'';
            }
            //完善收货经纬度
            if ($arr['address_id'] > 0 && empty(intval($arr['address_lng'])) && empty(intval($arr['address_lat']))) {
                $addressInfo = (new \app\common\model\service\UserAdressService())->getOne(['adress_id' => $arr['address_id']]);
                $arr['address_lng'] = $addressInfo['longitude'] ?? 0;
                $arr['address_lat'] = $addressInfo['latitude'] ?? 0;
            }
            
            return $arr;
        } else {
            throw new \think\Exception('没有该订单相关的详情,请确认后重试');
        }
    }

    /**
     * 获取订单详情优化
     * @param $order_id
     * @return array
     * @throws \think\Exception
     */
    public function getOrderDetailsCopy($order_id, $periodic_order_id = '', $re_type = '', $refund_id = '', $now_status = 1, $staffUser=[])
    {
        try{
        if (empty($order_id)) {
            throw new \think\Exception('缺少order_id参数');
        }
        $orderDetailService = new MallOrderDetailService();
        $field = 'o.order_id,o.goods_activity_type,o.mer_id,o.store_id,o.money_freight,o.order_no,o.uid,o.create_time,o.send_time,o.complete_time,o.address,o.username,o.phone as sh_phone,u.phone as zc_phone,o.express_send_time,o.express_style,o.express_num,o.money_total,o.discount_system_coupon,o.discount_merchant_coupon,o.discount_merchant_card,o.discount_system_level,o.discount_clerk_money,o.discount_act_money,o.money_real,o.money_score,o.money_system_balance,o.money_merchant_balance,o.money_merchant_give_balance,o.money_qiye_balance,o.money_online_pay,o.status as order_status,o.pay_orderno,o.pre_pay_orderno,o.remark,o.clerk_remark,o.express_name,o.express_id,o.activity_type,o.pay_time,o.staff_name,o.employee_score_pay,o.employee_balance_pay,o.online_pay_type,o.fetch_number,o.money_refund,o.internal_card_pay,o.is_third_deliver';

		if(strlen($order_id) >= 24){
			$where = ['o.order_no' => $order_id];
		}else{
			$where = ['o.order_id' => $order_id];
		}

        $arr = $this->MallOderModel->getDetail($where, $field);
        if (!empty($arr)) {
            if($staffUser&&$staffUser['store_id']!=$arr['store_id']){
                throw new \think\Exception('订单不存在');
            }
            //支付信息
            if ($arr['goods_activity_type'] == 'prepare' && empty($arr['pay_orderno'])) {
                $where = ['business' => 'mall', 'orderid' => $arr['pre_pay_orderno']];
            } else {
                $where = ['business' => 'mall', 'orderid' => $arr['pay_orderno']];
            }
            $payInfo = (new PayOrderInfo())->getOne($where);
            $arr = array_merge($arr, $payInfo);
            //总优惠金额
            $clerkDiscount = $arr['discount_clerk_money'] ? explode(',', $arr['discount_clerk_money']) : '';
            if (!empty($clerkDiscount)) {
                $arr['discount_clerk_money'] = $clerkDiscount[0];
            } else {
                $arr['discount_clerk_money'] = 0.00;
            }
            $arr['discount_total'] = (float)$arr['discount_system_coupon'] + (float)$arr['discount_merchant_coupon'] + (float)$arr['discount_merchant_card'] + (float)$arr['discount_system_level'] + (float)$arr['discount_clerk_money'] + (float)$arr['discount_act_money'];
            $arr['discount_total'] = number_format($arr['discount_total'], 2);
            //处理时间
            $arr['create_time'] = date('Y-m-d H:i:s', $arr['create_time']);
            $arr['send_time'] = $arr['send_time'] ? date('Y-m-d H:i:s', $arr['send_time']) : '';
            $arr['paid_time'] = $arr['pay_time'] ? date('Y-m-d H:i:s', $arr['pay_time']) : '';
            $arr['complete_time'] = $arr['complete_time'] ? date('Y-m-d H:i:s', $arr['complete_time']) : '';
            //处理店员优惠前后的金额展示
            $discount_list = array();
            $this_money = $arr['money_real'];
            if (!empty($clerkDiscount)) {
                foreach ($clerkDiscount as $key => $val) {
                    if ($val) {
                        $discount_list[] = [
                            'money_before' => number_format((float)$this_money + (float)$val, 2),
                            'money_after' => number_format((float)$this_money, 2)
                        ];
                        $this_money = number_format((float)$this_money + (float)$val, 2);
                    }
                }
                $discount_list = array_reverse($discount_list);
                $arr['clert_discount_process'] = $discount_list;
            }
            //判断是否能改价格
            if (empty($arr['goods_activity_type']) && empty($arr['activity_type'])) {
                $arr['clerk_modify'] = 1;
            } else {
                $arr['clerk_modify'] = 0;
            }
            //退款
            $arr['refund_money'] = 0.00;
            $arr['refund_reason'] = '';
            $arr['refund_images'] = [];
            $arr['refund_money_periodic'] = 0.00;
            $arr['refund_count_periodic'] = 0;
            $arr['all_refund'] = false;
            $arr['is_refund'] = false;
            $refund_info = (new MallOrderRefund)->getOne([['refund_id', '=', $refund_id], ['status', '<=', 1]], true);
            $arr['real_order_no'] = '';
            if (!empty($refund_info)) {
                $refund_info = $refund_info->toArray();
                $arr['refund_id'] = $refund_info['refund_id'];
                $arr['is_all'] = $refund_info['is_all'];
                $arr['type'] = $refund_info['type'];
                $arr['refund_money'] = $refund_info['refund_money'];
                $arr['refund_reason'] = $refund_info['reason'];
                $arr['refund_images'] = $refund_info['voucher'] ? explode(',', $refund_info['voucher']) : [];
                if (!empty($arr['refund_images'])) {
                    foreach ($arr['refund_images'] as $ik => $im) {
                        $arr['refund_images'][$ik] = replace_file_domain($im);
                    }
                }
                $arr['refund_time'] = $refund_info['create_time'] ? date('Y-m-d H:i:s', intval($refund_info['create_time'])) : '';
                $arr['refund_money_periodic'] = 0.00;  //周期购退款金额
                $arr['refund_count_periodic'] = 0;  //周期购退款期数
                $arr['all_refund'] = $refund_info['is_all'];//是全退
                $arr['is_refund'] = true; //存在退款
                if ($refund_info['is_all'] == 0) {//部分退款
                    $arr['part_refund_money'] = $refund_info['refund_money'];
                }
                if ($refund_info['status'] == 0) {
                    $arr['order_status'] = 60;
                    $arr['real_order_no']=$arr['order_no'];
                    $arr['order_no']=$refund_info['order_no'];
                } elseif ($refund_info['status'] == 1) {
                    $arr['order_status'] = 70;
                    $arr['real_order_no']=$arr['order_no'];
                    $arr['order_no']=$refund_info['order_no'];
                }
            }
            $refundDetailService = new MallOrderRefundDetail();
            //sku的状态展示（sku暂不进行单独操作 以后有需求可以在此扩展）
            if (!empty($refund_id)) {
                $field = 'd.id,d.goods_id,d.name as goods_name,d.image,d.sku_info,num,d.price,d.status as goods_status,d.forms,d.notes,r.refund_nums';
                $where = ['refund_id' => $refund_id];
                $info = $refundDetailService->getJoinData($where, $field);
                if (!empty($info)) {
                    $info = $info->toArray();
                }
            } else {
                $field = 'id,goods_id,name as goods_name,image,sku_info,num,price,status as goods_status,forms,notes';
                $info = $orderDetailService->getByOrderId($field, $arr['order_id']);
            }
            $arr['total_num'] = 0;
            if (!empty($info)) {
                foreach ($info as $kk => $vv) {
                    if (!empty($refund_id)) {
                        $vv['goods_status'] = $arr['order_status'];
                        $vv['num'] = $vv['refund_nums'];
                    }
                    if ($arr['goods_activity_type'] == 'periodic') {
                        $vv['goods_status'] = $arr['order_status'];
                    }
                    if(!empty($vv['notes'])){
                        $_notes = json_decode($vv['notes'], true);
                        $cart_v=array();
                        foreach ($_notes as $n) {
                            $cart_v[] = implode(',', $n['property_val']);
                        }
                        if(empty($vv['sku_info'])){
                            $vv['sku_info']=implode(',',$cart_v);
                        }else{
                            $vv['sku_info']=$vv['sku_info'].",".implode(',',$cart_v);
                        }
                    }
                    //如果该笔订单的商品存在退款则要展示出
                    $where_tk = ['d.order_detail_id' => $vv['id']];
                    $vv['refund_desc'] = [];
                    $refund_nums1 = 0;
                    $refund_nums2 = 0;
                    $refund_nums3 = 0;
                    $refund_details = $refundDetailService->getRefundByDetaiId($where_tk);
                    if (!empty($refund_details)) {
                        foreach ($refund_details as $refund_detail) {
                            if ($refund_detail['status'] == 0 && $refund_detail['type'] == 1 && $arr['order_status'] != 60 && $arr['order_status'] != 70) {
                                $refund_nums1 += $refund_detail['refund_nums'];
                            } elseif ($refund_detail['status'] == 0 && $refund_detail['type'] == 2 && $arr['order_status'] != 60 && $arr['order_status'] != 70) {
                                $refund_nums2 += $refund_detail['refund_nums'];
                            } elseif ($refund_detail['status'] == 1 && $arr['order_status'] != 60 && $arr['order_status'] != 70) {
                                $refund_nums3 += $refund_detail['refund_nums'];
                            }
                        }
                        if (!empty($refund_nums1)) {
                            $vv['refund_desc'][] = '退款中x' . $refund_nums1;
                        }
                        if (!empty($refund_nums2)) {
                            $vv['refund_desc'][] = '售后中x' . $refund_nums2;
                        }
                        if (!empty($refund_nums3)) {
                            $vv['refund_desc'][] = '已退款x' . $refund_nums3;
                        }
                    }
                    $vv['is_gift'] = 0;
                    //如果该订单的商品是赠品则要展示出
                    if (stripos($arr['activity_type'], 'give') !== false) {
                        if ((new MallFullGiveGiftSku())->getGiftInfo('g.id', [['g.goods_id', '=', $vv['goods_id']], ['a.start_time', '<', time()], ['a.end_time', '>', time()], ['a.status', '<>', 2]])) {
                            $vv['is_gift'] = 1;
                        }
                    }
                    if ($vv['goods_status'] >= 0 && $vv['goods_status'] < 10) {
                        $vv['goods_status_txt'] = '待付款';
                    } elseif ($vv['goods_status'] == 10) {
                        $vv['goods_status_txt'] = '待发货';
                    } elseif ($vv['goods_status'] == 11) {
                        $vv['goods_status_txt'] = '备货中';
                    } elseif ($vv['goods_status'] == 13) {
                        $vv['goods_status_txt'] = '待成团';
                    } elseif ($vv['goods_status'] >= 20 && $vv['goods_status'] < 30) {
                        $vv['goods_status_txt'] = '已发货';
                    } elseif ($vv['goods_status'] >= 30 && $vv['goods_status'] < 50) {//用户端的已收货所对应的店员端的已完成
                        $vv['goods_status_txt'] = '已完成';
                    } elseif ($vv['goods_status'] >= 50 && $vv['goods_status'] < 60) {
                        $vv['goods_status_txt'] = '取消';
                    } elseif ($vv['goods_status'] >= 60 && $vv['goods_status'] < 70) {
                        if ($refund_info['type'] == 1) {
                            $vv['goods_status_txt'] = '退款中';
                        } elseif ($refund_info['type'] == 2) {
                            $vv['goods_status_txt'] = '售后中';
                        }
                    } elseif ($vv['goods_status'] >= 70) {
                        $vv['goods_status_txt'] = '已退款';
                    }
                    $vv['image'] = $vv['image'] ? replace_file_domain($vv['image']) : '';
                    $arr['total_num'] += $vv['num'];
                    $arr['children'][] = $vv;
                    $arr['forms'] = json_decode($vv['forms'], true) ?: [];
                    if (!empty($arr['forms'])) {
                        foreach ($arr['forms'] as $k => $v) {
                            if ($v['type'] == 'image' && !empty($v['val'])) {
                                $arr['forms'][$k]['val'] = replace_file_domain($v['val']);
                            }
                        }
                    }
                    $arr['notes'] = json_decode($vv['notes'], true) ?: [];
                }
            } else {
//                throw new \think\Exception('未查询到与该订单有关的商品信息');
            }
            $arr['button'] = [
                'take_btn' => 0,
                'hoseman_btn' => 0,
                'clerk_btn' => 0,
                'express_btn' => 0,
                'logistics_btn' => 0,
                'postpone_btn' => 0,
                'trajectory_btn' => 0,
                'agree_refund_btn' => 0,
                'refuse_refund_btn' => 0,
            ];
            //周期购的处理 期数
            if (stripos($arr['goods_activity_type'], 'periodic') !== false) {
                if (!empty($periodic_order_id)) {
                    $periodic = $this->mallActivityService->returnNowPeriodicOrderDetail($periodic_order_id);//邓远辉提供的接口
                    if (!empty($periodic)) {
                        $arr['periodic_order_id'] = $periodic_order_id;
                        $arr['periodic_count'] = $periodic['periodic_count'];
                        $arr['current_periodic'] = $periodic['current_periodic'];
                        $arr['current_time'] = date('Y-m-d H:i', $periodic['periodic_date']);//周期购订单期望到达时间
                        if ($periodic['periodic_count'] === 0) {
                            throw new \think\Exception('周期购的期数不能为0');
                        }
                        if (!empty($periodic['deliver'])) {
                            $arr['express_name'] = $periodic['deliver']['express_name'];
                        }
                    }
                    $periodic = $this->mallActivityService->returnNowPeriodicOrderAndActBefore($order_id, $now_status,'staff');
                    $col = 0;
                    if (!empty($periodic)) {
                        foreach ($periodic as $kk => $vv) {
                            $periodic[$kk]['button'] = [
                                'take_btn' => 0,
                                'hoseman_btn' => 0,
                                'clerk_btn' => 0,
                                'express_btn' => 0,
                                'logistics_btn' => 0,
                                'postpone_btn' => 0,
                                'trajectory_btn' => 0,
                                'agree_refund_btn' => 0,
                                'refuse_refund_btn' => 0
                            ];
                            if ($vv['is_complete'] == 10) {
                                if ($re_type == 'storestaff') {
                                    $periodic[$kk]['button']['take_btn'] = 1;
                                    $periodic[$kk]['button']['postpone_btn'] = 1;
                                }
                            } elseif ($vv['is_complete'] == 11) {
                                if ($arr['express_style'] == 1) {
                                    $periodic[$kk]['button']['hoseman_btn'] = 1;
                                } elseif ($arr['express_style'] == 3) {
                                    $periodic[$kk]['button']['clerk_btn'] = 1;
                                } elseif ($arr['express_style'] == 2) {
                                    $periodic[$kk]['button']['express_btn'] = 1;
                                } else {
                                    throw new \think\Exception('发货方式错误');
                                }
                            } elseif ($vv['is_complete'] == 20) {
                                if ($arr['express_style'] == 2) {
                                    $periodic[$kk]['button']['logistics_btn'] = 1;
                                } elseif ($arr['express_style'] == 1) {
                                    $periodic[$kk]['button']['trajectory_btn'] = 1;
                                }
                            } elseif ($vv['is_complete'] == 30) {
                                if ($arr['express_style'] == 2) {
                                    $periodic[$kk]['button']['logistics_btn'] = 1;
                                } elseif ($arr['express_style'] == 1) {
                                    $periodic[$kk]['button']['trajectory_btn'] = 1;
                                }
                            }
                            $periodic[$kk]['delivery'] = (new MallOrderService())->orderDeliveryList($order_id, $periodic[$kk]['purchase_order_id']);
                            $periodic[$kk]['index'] = $col++;
                            $periodic[$kk]['periodic_date'] = $vv['periodic_date'] ? date('Y-m-d H:i', $vv['periodic_date']) : '';
                            $info[0]['image'] = replace_file_domain($info[0]['image']);
                            $periodic[$kk] = array_merge($periodic[$kk], $info[0]);
                        }
                        $arr['periodic_info'] = $periodic;
                        $arr['periodic_order_id'] = $periodic[$kk]['purchase_order_id'];
                    }
                }
            }else{
                $arr['delivery'] = (new MallOrderService())->orderDeliveryList($order_id);
            }
            //预售的处理 需付款+已付定金+已付尾款
            if (stripos($arr['goods_activity_type'], 'prepare') !== false) {
                $prefield = 'po.bargain_price,po.rest_price,p.discount_price,po.act_id,po.goods_id,po.uid,po.pay_time';
                $prepare = (new MallPrepareOrder())->getPrepare($prefield, $order_id);
                if (!empty($prepare)) {
                    $arr['bargain_price'] = $prepare['bargain_price'];
                    $arr['rest_price'] = $prepare['rest_price'];
                    $arr['total_num'] = $arr['total_num'] != 0 ? $arr['total_num'] : 1;
                    $arr['deduct_price'] = $prepare['bargain_price'] + $prepare['discount_price'] * $arr['total_num'];
                    $arr['money_total'] = $prepare['rest_price'] + $prepare['bargain_price'];
                    $arr['children'][0]['price'] = ($prepare['rest_price'] + $prepare['bargain_price']) / $arr['total_num'];
                    if ($arr['order_status'] >= 0 && $arr['order_status'] < 10) {
                        $arr['money_real'] = $prepare['bargain_price']- $arr['discount_total'];
                    } elseif ($arr['order_status'] >= 50 && $arr['order_status'] < 60) {
                        $arr['money_real'] = $prepare['bargain_price'] - $arr['discount_total'];
                    } else {
                        $arr['money_real'] = $prepare['rest_price'] + $prepare['bargain_price'] - $arr['discount_total'];
                    }
                    $arr['discount_price'] = $prepare['discount_price'];
                    $arr['paid_time'] = $prepare['pay_time'] ? date('Y-m-d H:i:s', $prepare['pay_time']) : '';
                    $arr['send_time'] = date('Y-m-d H:i:s', ($this->mallActivityService->getPrepareOrderStatus($prepare['goods_id'], $prepare['uid'], $prepare['act_id'], $order_id))['send_goods_date']);
                } else {
                    //throw new \think\Exception('没有查到该订单的预售信息');
                }
            }
            $arr['pay_type'] = $arr['pay_type'] ?? '';
            $payService = new PayService();
            $arr['pay_type_txt'] = $payService->getPayTypeText($arr['online_pay_type'], $arr);
            //订单类型
            if (stripos($arr['goods_activity_type'], 'bargain') !== false) {
                $arr['order_type_txt'] = '砍价订单';
                $arr['order_type'] = 'bargain';
            } elseif (stripos($arr['goods_activity_type'], 'group') !== false) {
                $arr['order_type_txt'] = '拼团订单';
                $arr['order_type'] = 'group';
            } elseif (stripos($arr['goods_activity_type'], 'limited') !== false) {
                $arr['order_type_txt'] = '秒杀订单';
                $arr['order_type'] = 'limited';
            } elseif (stripos($arr['goods_activity_type'], 'prepare') !== false) {
                $arr['order_type_txt'] = '预售订单';
                $arr['order_type'] = 'prepare';
            } elseif (stripos($arr['goods_activity_type'], 'periodic') !== false) {
                $arr['order_type_txt'] = '周期购订单';
                $arr['order_type'] = 'periodic';
            } else {
                $arr['order_type_txt'] = '普通订单';
                $arr['order_type'] = '';
            }

            //店员端的操作
            if ($arr['order_status'] >= 0 && $arr['order_status'] < 10) {
                $arr['order_status_txt'] = '待付款';
                $arr['step_create_time'] = $arr['create_time'];
            } elseif ($arr['order_status'] == 10) {
                $arr['order_status_txt'] = '待发货';
                if ($re_type == 'storestaff') {
                    $arr['button']['take_btn'] = 1;
                }
                $arr['step_create_time'] = $arr['create_time'];
                $arr['step_send_time'] = $arr['send_time'];
                $arr['step_paid_time'] = $arr['paid_time'];
            } elseif ($arr['order_status'] == 11) {
                $arr['order_status_txt'] = '备货中';
                if ($re_type == 'storestaff') {
                    if ($arr['express_style'] == 1) {
                        $arr['button']['hoseman_btn'] = 1;
                    } elseif ($arr['express_style'] == 3) {
                        $arr['button']['clerk_btn'] = 1;
                    } elseif ($arr['express_style'] == 2) {
                        $arr['button']['express_btn'] = 1;
                    } else {
                        throw new \think\Exception('发货方式错误');
                    }
                }
                $arr['step_create_time'] = $arr['create_time'];
                $arr['step_send_time'] = $arr['send_time'];
                $arr['step_paid_time'] = $arr['paid_time'];
                if (stripos($arr['goods_activity_type'], 'periodic') !== false) {
                    if (empty($periodic_order_id)) {
                        $arr['button']['express_btn'] = 0;
                    }
                }
            } elseif ($arr['order_status'] == 13) {
                $arr['order_status_txt'] = '待成团';
                $arr['step_create_time'] = $arr['create_time'];
                $arr['step_send_time'] = $arr['send_time'];
                $arr['step_paid_time'] = $arr['paid_time'];
            } elseif ($arr['order_status'] >= 20 && $arr['order_status'] < 30) {
                $arr['order_status_txt'] = '已发货';
                if ($arr['express_style'] == 2) {
                    $arr['button']['logistics_btn'] = 1;
                } elseif ($arr['express_style'] == 1) {
                    $arr['button']['trajectory_btn'] = 1;
                }
                $arr['step_create_time'] = $arr['create_time'];
                $arr['step_send_time'] = $arr['send_time'];
                $arr['step_paid_time'] = $arr['paid_time'];
            } elseif ($arr['order_status'] >= 30 && $arr['order_status'] < 50) {//用户端的已收货所对应的店员端的已完成
                $arr['order_status_txt'] = '已完成';
                if ($arr['express_style'] == 2) {
                    $arr['button']['logistics_btn'] = 1;
                } elseif ($arr['express_style'] == 1) {
                    $arr['button']['trajectory_btn'] = 1;
                }
                $arr['step_create_time'] = $arr['create_time'];
                $arr['step_send_time'] = $arr['send_time'];
                $arr['step_paid_time'] = $arr['paid_time'];
                $arr['step_complete_time'] = $arr['complete_time'];
            } elseif ($arr['order_status'] >= 50 && $arr['order_status'] < 60) {
                $arr['order_status_txt'] = '取消';
            } elseif ($arr['order_status'] >= 60 && $arr['order_status'] < 70) {
                if (isset($arr['type']) && $arr['type'] == 1) {
                    $arr['order_status_txt'] = '退款中';
                } elseif (isset($arr['type']) &&$arr['type'] == 2) {
                    $arr['order_status_txt'] = '售后中';
                } else {
                    $arr['order_status_txt'] = '售后中';
                    fdump_sql(['val'=>$arr,"msg"=>"退款参数有误"],"mall_return");
                    //throw new \think\Exception('退款参数有误');
                }
                if ($re_type == 'storestaff') {
                    $second = $refund_info['create_time'] + cfg('mall_order_refund_time') * 24 * 60 * 60 - time();
                    $arr['rest_time'] = $second;
                    $arr['button']['agree_refund_btn'] = 1;
                    $arr['button']['refuse_refund_btn'] = 1;
                }
                if (isset($arr['type']) && $arr['type'] == 2 && $arr['express_style'] != 3) {
                    if ($arr['express_style'] == 2) {
                        $arr['button']['logistics_btn'] = 1;
                    } elseif ($arr['express_style'] == 1) {
                        $arr['button']['trajectory_btn'] = 1;
                    }
                }
            } elseif ($arr['order_status'] >= 70) {
                $arr['order_status_txt'] = '已退款';
                $arr['button']['logistics_btn'] = 1;
            }
            if (stripos($arr['goods_activity_type'], 'periodic') !== false) {//没到发货时间不显示发货按钮
                $periodic = $this->mallActivityService->returnNowPeriodicOrderAndActBefore($order_id, $now_status);
                if(empty($periodic)){
                    $arr['button']['hoseman_btn'] = 0;
                }
            }
            if($arr['is_third_deliver']){
                $arr['button']['take_btn'] = 0;
                $arr['button']['hoseman_btn'] = 0;
                $arr['button']['postpone_btn'] = 0;
            }
            //将数字转化为对应的文字
            switch ($arr['express_style']) {
                case 1:
                    $arr['express_style_txt'] = '骑手配送';
                    $time = explode(',', $arr['express_send_time']);
                    $arr['express_send_time'] = date('Y-m-d', $time[0]) . ' ' . date('H:i', $time[0]) . '-' . date('H:i', $time[1]);
                    break;
                case 3:
                    $arr['express_style_txt'] = '自提';
                    break;
                case 2:
                    $arr['express_style_txt'] = '快递配送';
                    break;
            }
            //获取自提信息
            $arr['take_address'] = '';
            if($arr['express_style']==3){
                $arr['take_address'] = (new MerchantStore())->getOne(['store_id'=>$arr['store_id']])['adress']??'';
            }
            if($arr['discount_clerk_money']){
                $arr['discount_total']=$arr['money_total']-$arr['money_real'];
            }
            if(customization('internal_membership_card') && isset($staffUser['store_id'])) {
                $miaomaiyunErpStoreid = MerchantStore::where(['store_id' => $staffUser['store_id']])->value('miaomaiyun_erp_storeid');
                if ($miaomaiyunErpStoreid) {
                    $arr['button'] = [];
                }
            }
            return $arr;
        } else {
            throw new \think\Exception('没有该订单相关的详情,请确认后重试');
        }
        }catch (\Exception $e){
            throw new \think\Exception($e->getMessage());
        }

    }

    /**
     * 获取日志
     * @param $order_id
     * @return array
     */
    public function getOrderLog($order_id)
    {
        if (empty($order_id)) {
            throw new \think\Exception('缺少order_id参数');
        }
        $orderLog = new MallOrderLog();
        $arr = $orderLog->getLogByOrderId($order_id);
        if (!empty($arr)) {
            foreach ($arr as $key => $val) {
                $arr[$key]['addtime'] = date('Y-m-d H:i:s', $val['addtime']);
            }
        }
        if (!empty($arr)) {
            return $arr;
        } else {
            return [];
        }
    }

    /**
     * 获取各种和
     * @return mixed
     */
    public function getCollect($where, $param)
    {
        $arr['sh_money'] = $this->getMerchantMoney($param);
        if(!empty($where)){
            foreach ($where as $k=>$v){
                if($v[0]=='r.create_time'){
                    unset($where[$k]);
                }
            }
            $where=array_values($where);
        }
        $arr['zf_money'] = $this->MallOderModel->getMoneySum(array_merge($where, [['o.status', 'IN', [1,10,11,12,13,14,15,20,21,22,23,24,30,31,32,33,40,60,61,70]]]), 'sum(money_real-money_refund)');
        $arr['zf_money'] = getFormatNumber($arr['zf_money']);
        $arr['jianshu'] = $this->MallOderModel->getCount(array_merge($where, [['o.status', '>=', 0]]));
        $style = 7;
        $where = $this->getWhere($param, $style);
        if(isset($param['provinceId']) && $param['provinceId']){
            $where[] = ['s.province_id','=',$param['provinceId']];
        }
        if(isset($param['cityId']) && $param['cityId']){
            $where[] = ['s.city_id','=',$param['cityId']];
        }
        if(isset($param['areaId']) && $param['areaId']){
            $where[] = ['s.area_id','=',$param['areaId']];
        }
        if(isset($param['streetId']) && $param['streetId']){
            $where[] = ['s.street_id','=',$param['streetId']];
        }
        $refundMoney = (new MallOrderRefund())->getInfoByStore(array_merge($where, [['r.status', '=', '1']]), 'r.refund_money', '', '');
        if (!empty($refundMoney)) {
            $arr['tk_money'] = number_format((float)array_sum(array_column($refundMoney, 'refund_money')), 2);
        }elseif(isset($param['search_type']) && isset($param['content']) && $param['search_type']==1 && $param['content']){
            foreach ($where as &$vv){
                if($vv[0] == 'r.order_no'){
                    $vv[0] = 'o.order_no';
                }
            }
            $refundMoneyByOrderNo = (new MallOrder())->getInfo(array_merge($where, [['r.status', '=', '1']]), 'r.refund_money');
            if($refundMoneyByOrderNo){
                $arr['tk_money'] = number_format((float)array_sum(array_column($refundMoneyByOrderNo, 'refund_money')), 2);
            }
        }
        return $arr;
    }

    /**
     * 获取商家余额
     */
    public function getMerchantMoney($param)
    {
        $style = 2; //计数时不统计状态
        $where = $this->getWhere($param, $style);
        if ($param['provinceId']) {
            $where[] = ['s.province_id','=',$param['provinceId']];
        }
        if ($param['cityId']) {
            $where[] = ['s.city_id','=',$param['cityId']];
        }
        if ($param['areaId']) {
            $where[] = ['s.area_id','=',$param['areaId']];
        }
        if($param['streetId']){
            $where[] = ['s.street_id','=',$param['streetId']];
        }
        if(!empty($where)){
            foreach ($where as $k=>$v){
                if($v[0]=='r.create_time'){
                    unset($where[$k]);
                }
            }
            $where=array_values($where);
        }
        $field = 'o.pay_orderno,o.goods_activity_type,o.order_no';
        $style = 4;
        $where_periodic = $this->getWhere($param, $style);

        if(!empty($where_periodic)){
            foreach ($where_periodic as $k1=>$v1){
                if($v1[0]=='r.create_time'){
                    unset($where_periodic[$k1]);
                }
            }
            $where_periodic=array_values($where_periodic);
        }
        array_push($where_periodic, ['o.goods_activity_type', 'like', '%' . 'periodic' . '%']);

        $sum1 = $this->MallOderModel->getMerchantSum($where,$where_periodic,[['mml.income','=',1]]);
        $sum2 = $this->MallOderModel->getMerchantSum($where,$where_periodic,[['mml.income','=',2]]);
        $sum = bcsub($sum1,$sum2,2);
        return $sum;
//        $arr = $this->MallOderModel->getSearch($field, $where, $where_periodic);
//        $order_no = array_column($arr, 'order_no');
//        if (!empty($order_no)) {
//            $where = [['order_id', 'in', $order_no],['income','=',1]];
//            $sum1=(new MerchantMoneyList())->getSum($where);
//            $where = [['order_id', 'in', $order_no],['income','=',2]];
//            $sum2=(new MerchantMoneyList())->getSum($where);
//            $sum=get_format_number($sum1-$sum2);
//            if(!$sum){
//                $sum=0;
//            }
//            return $sum;
//        } else {
//            return 0;
//        }
    }

    /**
     * 获取各种优惠
     * @return mixed
     */
    public function getDiscount($order_id)
    {
        if (empty($order_id)) {
            throw new \think\Exception('缺少order_id参数');
        }
        $where = ['order_id' => $order_id];
        $field = 'discount_system_coupon,discount_merchant_coupon,discount_merchant_card,discount_system_level,discount_clerk_money,discount_act_money';
        $arr = $this->MallOderModel->getDiscount($where, $field);
        if (!empty($arr)) {
            //店员优惠取最后一次
            $clerk_discount = explode(',', $arr['discount_clerk_money']);
            $arr['discount_clerk_money'] = $clerk_discount[0];
            return $arr;
        } else {
            return [];
        }
    }

    /**
     * 获取订单的各种信息用于导出
     * @param $param
     * @return array
     */
    public function formatExportChildren($arr)
    {
        foreach ($arr as $key => $val) {
            $goods_names = [];
            $prices = [];
            $num = [];
            $num_new = [];
            foreach ($val['children'] as $kk => $vv) {
                $goods_names[] = $vv['goods_name'].'  '.$vv['sku_info'];
                $prices[] = '¥' . $vv['price'];
                $num[] = '*' . $vv['num'];
                $num_new[] = '*' . ($vv['num_new']??$vv['num']);
            }
            $arr[$key]['goods_name'] = implode('/', $goods_names);
            $arr[$key]['price'] = implode('/', $prices);
            $arr[$key]['num'] = implode('/', $num);
	    $arr[$key]['num_new'] = implode('/', $num_new);
            $arr[$key]['supply_price'] =$val['supply_price'];
            $arr[$key]['goods_code'] =$val['goods_code'];
        }
        return $arr;

    }

    /**
     * 添加导出计划任务
     * @param $param
     * @param array $systemUser
     * @param array $merchantUser
     * @return array
     * @throws \think\Exception
     */
    public function addOrderExportOld($param, $systemUser = [], $merchantUser = [])
    {
        $title = cfg('mall_alias_name') . '订单导出';
        $param['type'] = 'pc';
        $param['service_path'] = '\app\mall\model\service\MallOrderService';
        $param['service_name'] = 'orderExportPhpSpreadsheet';
        $param['rand_number'] = time();
        $param['system_user']['area_id'] = $systemUser ? $systemUser['area_id'] : 0;
        $param['merchant_user']['mer_id'] = $merchantUser ? $merchantUser['mer_id'] : 0;
        $result = (new BaseExportService())->addExport($title, $param, 'xlsx');
        return $result;

    }
    /**
     * 订单导出
     */
    public function addOrderExport($params, $systemUser = [], $merchantUser = [])
    {
        $params['type'] = 'pc';
        $params['service_path'] = '\app\mall\model\service\MallOrderService';
        $params['service_name'] = 'orderExportPhpSpreadsheet';
        $params['rand_number'] = time();
        $params['system_user']['area_id'] = $systemUser ? $systemUser['area_id'] : 0;
        $params['merchant_user']['mer_id'] = $merchantUser ? $merchantUser['mer_id'] : 0;
        $params['page'] = 0;
        $params['pageSize'] = 0;

        $params['search_time_type'] = $params['search_time_type'] ?? false;
        $downFileUrl = $this->orderExportPhpSpreadsheet($params);
        $downFileUrl = cfg('site_url') . '/v20/runtime/' . $downFileUrl;
        return ['file_url' => $downFileUrl, 'file_name' => $downFileUrl];



        $csvHead = array(
            L_('订单编号'),
            L_('收货人姓名'),
            L_('收货人手机号'),
            L_('收货地址'),
            L_('商家名称'),
            L_('店铺名称'),
            L_('商品名称'),
            L_('商品单价'),
            L_('商品数量'),
            L_('商品总价'),
            L_('优惠总金额'),
            L_('实际付款'),
            L_('订单状态'),
            L_('订单时间'),
            L_('支付方式'),
            L_('第三方支付流水号'),
            L_('商家备注'),
        );
        $data = $this->searchOrders($params, $type = 1);

        $csvData = [];

        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $csvData[$key] = [
                    $value['order_no'] . "\t",
                    $value['username'],
                    $value['phone'],
                    $value['address'],
                    $value['mer_name'],
                    $value['store_name'],
                    $value['goods_name'],
                    $value['price'] . "\t",
                    $value['num'],
                    '¥' .$value['money_total'],
                    '¥' .$value['discount_total'],
                    '¥' .$value['money_real'],
                    $value['status_txt'] . "\t",
                    $value['create_time'],
                    $value['pay_type'],
                    $value['pay_orderno'],
                    $value['clerk_remark'],
                ];
            }
        }
        $filename = date("Y-m-d", time()) . '-' . $params['rand_number'] . '.csv';
        (new BaseExportService())->putCsv($filename, $csvData, $csvHead);

        $downFileUrl = cfg('site_url').'/v20/runtime/' . $filename;
        return ['file_url' => $downFileUrl,'file_name' => $downFileUrl];
    }

    /**
     * 导出订单(Spreadsheet方法)
     * @param $param
     */
    public function orderExportPhpSpreadsheet($param)
    {
        ini_set('memory_limit', '2048M');
        $orderList = $this->searchOrdersDownExcel($param, $type = 1);

        //为空则导出所有列
        $columnSetting = $this->exportColumns();
        $allColumns = [];
        foreach ($columnSetting as $e) {
            $allColumns = array_merge($allColumns, $e['columns']);
        }
        $columns = (isset($param['columns']) && $param['columns']) ? explode(',', $param['columns']) : array_column($allColumns, 'value');
        $columnIndex = array_column($allColumns, 'name', 'value');
        $xlsCell = [];
        foreach ($columns as $c) {
            $xlsCell[] = [$c, $columnIndex[$c]];
        }

        $lastOrderNo = '';
        $express = ['1' => '骑手配送', '2' => '快递配送', '3' => '自提'];
        $mergeColumn = ['goods_name', 'price', 'num', 'sku_all_price', 'sku_info', 'supply_price', 'goods_code', 'cost_price'];
        foreach ($orderList as &$o) {
            if ($o['order_no'] == $lastOrderNo) {
                if (empty(array_intersect($mergeColumn, $columns))) {
                    $o = [];
                } else {
                    $new = [
                        'goods_name' => $o['goods_name'],
                        'price' => $o['price'] ?? 0,
                        'num' => $o['num'],
                        'sku_all_price' => $o['sku_all_price'],
                        'sku_info' => $o['sku_info'] ?? '',
                        'supply_price' => $o['supply_price'],
                        'goods_code' => $o['goods_code'],
                        'cost_price' => $o['cost_price'],
                    ];
                    $o = $new;
                }
            } else {
                $o['express_name'] = $express[$o['express_style']] ?? '';
                $times = [];
                if ($o['express_style'] == 1 && $o['express_send_time']) {
                    $times = explode(',', $o['express_send_time']);
                }
                $o['express_start_time'] = isset($times[0]) ? date('Y-m-d H:i:s', $times[0]) : '';
                $o['express_end_time'] = isset($times[1]) ? date('Y-m-d H:i:s', $times[1]) : '';
                $lastOrderNo = $o['order_no'];
            }
       
        }
        $orderList = array_values(array_filter($orderList));
        $fileName = date("Y-m-d", time()) . '-' . $param['rand_number'];
        $exportResult = export_excel('商城订单', $xlsCell, $orderList, $fileName);
        return $exportResult['filename'];

        $orderList = $this->searchOrdersDownExcel($param, $type = 1);
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        //设置单元格内容
        $worksheet->setCellValueByColumnAndRow(1, 1, '订单编号');
        $worksheet->setCellValueByColumnAndRow(2, 1, '收货人姓名');
        $worksheet->setCellValueByColumnAndRow(3, 1, '收货人手机号');
        $worksheet->setCellValueByColumnAndRow(4, 1, '收货地址');
        $worksheet->setCellValueByColumnAndRow(5, 1, '商家名称');
        $worksheet->setCellValueByColumnAndRow(6, 1, '店铺名称');

        $worksheet->setCellValueByColumnAndRow(7, 1, '商品名称');
        $worksheet->setCellValueByColumnAndRow(8, 1, '商品单价');
        $worksheet->setCellValueByColumnAndRow(9, 1, '商品数量');
        $worksheet->setCellValueByColumnAndRow(10, 1, '商品总价');

        $worksheet->setCellValueByColumnAndRow(11, 1, '商品规格');
        $worksheet->setCellValueByColumnAndRow(12, 1, '供货价');
        $worksheet->setCellValueByColumnAndRow(13, 1, '商品编码');
        $worksheet->setCellValueByColumnAndRow(14, 1, '进货价');

        $worksheet->setCellValueByColumnAndRow(15, 1, '商品总价');
        $worksheet->setCellValueByColumnAndRow(16, 1, '优惠总金额');
        $worksheet->setCellValueByColumnAndRow(17, 1, '实际付款');
        $worksheet->setCellValueByColumnAndRow(18, 1, '订单状态');
        $worksheet->setCellValueByColumnAndRow(19, 1, '订单时间');
        $worksheet->setCellValueByColumnAndRow(20, 1, '支付方式');
	    $worksheet->setCellValueByColumnAndRow(21, 1, '第三方支付流水号');
        $worksheet->setCellValueByColumnAndRow(22, 1, '在线支付金额');
        $worksheet->setCellValueByColumnAndRow(23, 1, '平台余额支付金额');
        $worksheet->setCellValueByColumnAndRow(24, 1, '买家备注');
        $worksheet->setCellValueByColumnAndRow(25, 1, '店员备注');
        $worksheet->setCellValueByColumnAndRow(26, 1, '平台结算给商家的金额');
        if($param['status']==8){
            $worksheet->setCellValueByColumnAndRow(26, 1, '原始订单编号');
            $worksheet->setCellValueByColumnAndRow(27, 1, '部分退款金额');
            $worksheet->setCellValueByColumnAndRow(28, 1, '平台结算给商家的金额');
        }
        $worksheet->setCellValueByColumnAndRow(29, 1, '完成时间');

        //设置单元格样式
        $worksheet->getStyle('A1:AC1')->getFont()->setName('黑体')->setSize(12);
        $spreadsheet->getActiveSheet()->getStyle('A:AC')->getAlignment()->setWrapText(true);
        $spreadsheet->getActiveSheet()->getStyle('A1:AC1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EEEEEE');

        $spreadsheet->getActiveSheet()->getRowDimension('1')->setRowHeight(26);
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(26);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(13);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(42);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(30);

        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(50);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(35);
        $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(14);
        $spreadsheet->getActiveSheet()->getColumnDimension('L')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('M')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('N')->setWidth(16);

        $spreadsheet->getActiveSheet()->getColumnDimension('O')->setWidth(15);

        $spreadsheet->getActiveSheet()->getColumnDimension('P')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('Q')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('R')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('S')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('T')->setWidth(15);

        $spreadsheet->getActiveSheet()->getColumnDimension('U')->setWidth(26);
        $spreadsheet->getActiveSheet()->getColumnDimension('V')->setWidth(26);
        $spreadsheet->getActiveSheet()->getColumnDimension('W')->setWidth(26);
        $spreadsheet->getActiveSheet()->getColumnDimension('X')->setWidth(50);
        $spreadsheet->getActiveSheet()->getColumnDimension('Y')->setWidth(50);
        if($param['status']==8){
            $spreadsheet->getActiveSheet()->getColumnDimension('Z')->setWidth(26);
            $spreadsheet->getActiveSheet()->getColumnDimension('AA')->setWidth(26);
        }
        $spreadsheet->getActiveSheet()->getColumnDimension('AC')->setWidth(26);

        $len = count($orderList);
        $j = 0;
        $row = 0;
        $i = 0;
        foreach ($orderList as $key => $val) {
            if ($i < $len) {
                $j = $i + 2; //从表格第2行开始
                    $worksheet->setCellValueByColumnAndRow(1, $j, $orderList[$key]['order_no']);
                    $worksheet->setCellValueByColumnAndRow(2, $j, $orderList[$key]['username']);
                    $worksheet->setCellValueByColumnAndRow(3, $j, $orderList[$key]['phone']);
                    $worksheet->setCellValueByColumnAndRow(4, $j, $orderList[$key]['address']);
                    $worksheet->setCellValueByColumnAndRow(5, $j, $orderList[$key]['mer_name']);
                    $worksheet->setCellValueByColumnAndRow(6, $j, $orderList[$key]['store_name']);

                    $worksheet->setCellValueByColumnAndRow(7, $j, $orderList[$key]['goods_name']);
                    if(isset($orderList[$key]['price'])){
                        $worksheet->setCellValueByColumnAndRow(8, $j, $orderList[$key]['price']);
                    }else{
                        $worksheet->setCellValueByColumnAndRow(8, $j, 0);
                    }
                    $worksheet->setCellValueByColumnAndRow(9, $j, $orderList[$key]['num']);
                    $worksheet->setCellValueByColumnAndRow(10, $j, $orderList[$key]['sku_all_price']);
                    $worksheet->setCellValueByColumnAndRow(11, $j, empty($orderList[$key]['sku_info'])?'无':$orderList[$key]['sku_info']);
                    $worksheet->setCellValueByColumnAndRow(12, $j, '¥' . $orderList[$key]['supply_price']);
                    $worksheet->setCellValueByColumnAndRow(13, $j, $orderList[$key]['goods_code']);
                    $worksheet->setCellValueByColumnAndRow(14, $j, '¥' . $orderList[$key]['cost_price']);

                    $worksheet->setCellValueByColumnAndRow(15, $j, '¥' . $orderList[$key]['money_total']);
                    $worksheet->setCellValueByColumnAndRow(16, $j, '¥' . $orderList[$key]['discount_total']);
                    $worksheet->setCellValueByColumnAndRow(17, $j, '¥' . $orderList[$key]['money_real']);
                    if(isset($orderList[$key]['status_txt'])){
                        $worksheet->setCellValueByColumnAndRow(18, $j, $orderList[$key]['status_txt']);
                    }else{
                        $worksheet->setCellValueByColumnAndRow(18, $j, "");
                    }
                    $worksheet->setCellValueByColumnAndRow(19, $j, $orderList[$key]['create_time']);
                    $worksheet->setCellValueByColumnAndRow(20, $j, $orderList[$key]['pay_type']);
		            $worksheet->setCellValueByColumnAndRow(21, $j, $orderList[$key]['pay_orderno']);
                    $worksheet->setCellValueByColumnAndRow(22, $j, '¥' . $orderList[$key]['money_online_pay']);
                    $worksheet->setCellValueByColumnAndRow(23, $j, '¥' . $orderList[$key]['money_system_balance']);
                    $worksheet->setCellValueByColumnAndRow(24, $j, $orderList[$key]['remark']);
                    $worksheet->setCellValueByColumnAndRow(25, $j, $orderList[$key]['clerk_remark']);
                    $worksheet->setCellValueByColumnAndRow(26, $j, $orderList[$key]['settlement_merchant_money']);
                    if($param['status']==8){
                        $worksheet->setCellValueByColumnAndRow(26, $j, $orderList[$key]['real_order_no']??'');
                        $worksheet->setCellValueByColumnAndRow(27, $j, '¥' . $orderList[$key]['refund_money']??0);
                        $worksheet->setCellValueByColumnAndRow(28, $j, $orderList[$key]['settlement_merchant_money']);
                    }
                    $worksheet->setCellValueByColumnAndRow(29, $j, $orderList[$key]['complete_time']);
                $i++;

                if($key>=1 && ($orderList[$key]['order_no'] == $orderList[$key - 1]['order_no'])){
                    $worksheet->mergeCells('A' . ($j-1) . ':' . 'A' . $j); //合并单元格
                    $worksheet->mergeCells('B' . ($j-1) . ':' . 'B' . $j); //合并单元格
                    $worksheet->mergeCells('C' . ($j-1) . ':' . 'C' . $j); //合并单元格
                    $worksheet->mergeCells('D' . ($j-1) . ':' . 'D' . $j); //合并单元格
                    $worksheet->mergeCells('E' . ($j-1) . ':' . 'E' . $j); //合并单元格
                    $worksheet->mergeCells('F' . ($j-1) . ':' . 'F' . $j); //合并单元格



                    $worksheet->mergeCells('O' . ($j-1) . ':' . 'O' . $j); //合并单元格
                    $worksheet->mergeCells('P' . ($j-1) . ':' . 'P' . $j); //合并单元格
                    $worksheet->mergeCells('Q' . ($j-1) . ':' . 'Q' . $j); //合并单元格
                    $worksheet->mergeCells('R' . ($j-1) . ':' . 'R' . $j); //合并单元格
                    $worksheet->mergeCells('S' . ($j-1) . ':' . 'S' . $j); //合并单元格
                    $worksheet->mergeCells('T' . ($j-1) . ':' . 'T' . $j); //合并单元格
                    $worksheet->mergeCells('U' . ($j-1) . ':' . 'U' . $j); //合并单元格
                    $worksheet->mergeCells('V' . ($j-1) . ':' . 'V' . $j); //合并单元格
                    $worksheet->mergeCells('W' . ($j-1) . ':' . 'W' . $j); //合并单元格
                }
            }

            $row++;
        }
        $styleArrayBody = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
        ];
        $total_rows = $row + 1;
        //添加所有边框/居中
        $worksheet->getStyle('A1:AC' . $total_rows)->applyFromArray($styleArrayBody);
        //下载
        $filename = date("Y-m-d", time()) . '-' . $param['rand_number'] . '.xlsx';
        (new BaseExportService())->phpSpreadsheet($filename, $spreadsheet);
        return $filename;

    }

    /**
     * 店员设置价格
     * @param $order_id
     * @param $before_money
     * @param $after_money
     * @return MallOrder
     * @throws Exception
     */
    public function editMoney($order_id, $before_money, $after_money)
    {
        if (empty($order_id) || empty($before_money) || empty($after_money)) {
            throw new \think\Exception('参数缺失');
        }
        $where = ['order_id' => $order_id];
        //在原来的基础上向前累加店员优惠金额
        $field = 'discount_clerk_money';
        $clerkDiscount_before = $this->MallOderModel->getDiscount($where, $field)['discount_clerk_money'];
        $clerkDiscount_now = (float)($before_money - $after_money) . ',' . $clerkDiscount_before;
        $data = ['money_real' => $after_money, 'discount_clerk_money' => $clerkDiscount_now];
        $result = $this->MallOderModel->updateClerkMoney($where, $data);
        if ($result === false) {
            throw new  \think\Exception('操作失败，请重试');
        }
        return $result;
    }

    /**
     * 获取订单状态
     * @param $log_uid
     * @return array
     */
    public function getOrderStatus($log_uid)
    {
        $where = ['uid' => $log_uid];
        $orderStatus = $this->MallOderModel->getStatus($where);
        if (!empty($orderStatus)) {
            $Status['toBeDelivered'] = 0;
            $Status['delivered'] = 0;
            $Status['toBeReceived'] = 0;
            $Status['toBeEvaluated'] = 0;
            $Status['afterSales'] = 0;
            foreach ($orderStatus as $key => $val) {
                if ($val['status'] >= 0 && $val['status'] < 10) {
                    $Status['toBeDelivered'] = $val['count(status)'];
                } elseif ($val['status'] >= 10 && $val['status'] < 20) {
                    $Status['delivered'] = $val['count(status)'];
                } elseif ($val['status'] >= 20 && $val['status'] < 30) {
                    $Status['toBeReceived'] = $val['count(status)'];
                } elseif ($val['status'] >= 30 && $val['status'] < 50) {
                    $Status['toBeEvaluated'] = $val['count(status)'];
                } elseif ($val['status'] >= 60 && $val['status'] < 80) {
                    $Status['afterSales'] = $val['count(status)'];
                }
            }
            return $Status;
        } else {
            return [];
        }
    }

    /**
     * 订单按照状态展示
     * @param $log_uid
     * @param $type
     * @return array
     */
    public function getOrderByStatus($log_uid, $type)
    {
        $where = [['uid', '=', $log_uid]];
        switch ($type) {
            case 0:
                $tmp = []; //全部
                break;
            case 1:
                $tmp = [['o.status', '>=', 0], ['o.status', '<', 10]];
                break;
            case 2:
                $tmp = [['o.status', '>=', 10], ['o.status', '<', 20]];
                break;
            case 3:
                $tmp = [['o.status', '>=', 20], ['o.status', '<', 30]];
                break;
            case 4:
                $tmp = [['o.status', '>=', 30], ['o.status', '<', 50]];
                break;
            case 5:
                $tmp = [['o.status', '>=', 60], ['o.status', '<', 80]];
                break;
            default:
                $tmp = [];
        }
        $field = 'o.order_id,s.name as store_name';
        $where = array_merge($where, $tmp);
        $storeName = $this->MallOderModel->getOrderByStatus($field, $where);
        $dfield = 'name as goods_name,price,num,image,sku_info';
        $orderDetailService = new MallOrderDetailService();
        $num_all = 0;
        $arr = array();
        foreach ($storeName as $val) {
            $arr['store_name'] = $val['store_name'];
            $info = $orderDetailService->getByOrderId($dfield, $val['order_id']);
            foreach ($info as $kk => $vv) {
                $arr[] = [
                    'goods_name' => $vv['goods_name'],
                    'image' => $vv['image'],
                    'sku_info' => $vv['sku_info'],
                    'price' => '¥' . $vv['price'],
                    'num' => 'x' . $vv['num']];
                $num_all += $vv['num'];
            }
            $arr['num_all'] = $num_all;
        }
        if (!empty($arr)) {
            return $arr;
        } else {
            return [];
        }

    }

    /**
     * 保存订单，生成订单ID
     * @param  [type] $confirm_data 由CartService中的confirmCartData()方法返回的数据
     * @return [int]               合单表的主键ID
     */
    public function saveOrder($uid, $confirm_data, $store_param, $current_liveshow_id = 0, $current_livevideo_id = 0,$activity_type='normal')
    {
        if (empty($confirm_data) || empty($uid)) return 0;
        $store_param = json_decode($store_param, true);
        $PayService = new PayService();
        $success_orderids = [];
        $mer_ids = [];

        $business_order_sns = [];
        foreach ($confirm_data['order'] as $pre_order) {
            if (empty($pre_order['express']['style'])) {
                throw new \think\Exception("请选择配送方式");
            }

            if($pre_order['express']['style']==1 && empty($pre_order['express']['delivery_time_list'])){
                throw new \think\Exception("暂无骑手配送,请选择其他配送方式");
            }

            $goods_activity_type = array_filter(array_unique(array_column($pre_order['goods_list'], 'activity_type')));
            if (isset($pre_order['activity']['type']) && $pre_order['activity']['type'] == 'shipping') {
                $pre_order['express']['money'] = 0;//满包邮
            }
            $order = [
                'order_no' => $PayService->createOrderNo(),
                'mer_id' => $pre_order['mer_id'],
                'store_id' => $pre_order['store_id'],
                'uid' => $uid,
                'province_id' => $confirm_data['address']['province_id'],
                'city_id' => $confirm_data['address']['city_id'],
                'area_id' => $confirm_data['address']['area_id'],
                'address' => $confirm_data['address']['pca'] . $confirm_data['address']['detail'],
                'phone' => $confirm_data['address']['phone'],
                'username' => $confirm_data['address']['name'],
                'address_id' => $confirm_data['address']['id'],
                'status' => 0,//待付款
                'express_style' => $pre_order['express']['style'],
                'express_send_time' => $pre_order['express']['express_time'],
                'is_invoice' => $store_param[$pre_order['store_id']]['invoice_id'] ?? 0,
                'money_total' => $pre_order['total']['total_money'],
                'money_goods' => $pre_order['total']['goods_money_total'],
                'money_freight' => $pre_order['express']['money'],
                'freight_distance' => $pre_order['express']['distance'],
                'money_real' => get_format_number($pre_order['total']['goods_money'] + $pre_order['express']['money']),
                'money_merchant_balance' => $pre_order['merchant_card']['is_checked'] == 1 ? ($pre_order['merchant_card']['merchant_balance'] ?? 0) : 0,
                'money_merchant_give_balance' => $pre_order['merchant_card']['is_checked'] == 1 ? ($pre_order['merchant_card']['merchant_give_balance'] ?? 0) : 0,
                'goods_activity_type' => $goods_activity_type ? implode(',', $goods_activity_type) : '',
                'goods_activity_id' => $goods_activity_type ? $pre_order['goods_list'][0]['activity_id'] : 0,
                'activity_type' => $pre_order['activity']['type'] ?? '',
                'activity_id' => $pre_order['activity']['id'] ?? '',
//                'discount_act_money' => $pre_order['activity']['discount'] ?? 0,
                'discount_act_money' => $pre_order['activity']['money'] ?? 0,
                'system_coupon_id' => $pre_order['total']['sys_coupon_id'],
                'merchant_coupon_id' => $pre_order['coupon']['id'] ?? 0,
                'merchant_card_id' => $pre_order['merchant_card']['card_id'] ?? 0,
                'discount_system_coupon' => $pre_order['total']['sys_coupon_money'],
                'discount_merchant_coupon' => $pre_order['coupon']['money'] ?? 0,
                'discount_merchant_card' => $pre_order['merchant_card']['discount_money'] ?? 0,
                'create_time' => time(),
                'remark' => $pre_order['remark'] ?? '',
                'source' => request()->agent,
                'freight_free' => $pre_order['freight_free'] ?? 0,
                'discount_system_coupon_merchant' => $pre_order['total']['sys_coupon_money_merchant'],
                'address_lng' => $confirm_data['address']['lng'],
                'address_lat' => $confirm_data['address']['lat'],
                'is_third_deliver' => $pre_order['express']['is_third_deliver'],
            ];
            $business_order_sns[] = $order['order_no'];
            if ($current_liveshow_id > 0 && cfg('live_open') > 0) {
                $live_goods = (new LiveGoodService)->getGoodsPercentByLive($current_liveshow_id, 0, 'mall3');
                $is_liveshow = 0;
                if (isset($live_goods['goodids'])) {
                    foreach ($pre_order['goods_list'] as $livek => $liveg) {
                        if (in_array($liveg['goods_id'], $live_goods['goodids'])) {
                            $is_liveshow = 1;
                            $pre_order['goods_list'][$livek]['is_liveshow'] = 1;
                            $pre_order['goods_list'][$livek]['liveshow_id'] = $current_liveshow_id;
                            $pre_order['goods_list'][$livek]['liveshow_percent'] = $live_goods['percent'][$liveg['goods_id']];
                        }
                    }
                }
                if ($is_liveshow == 1) {
                    $order['is_liveshow'] = 1;
                    $order['liveshow_id'] = $current_liveshow_id;
                }
            } elseif ($current_livevideo_id > 0 && cfg('live_open') > 0) {
                $live_goods = (new LiveGoodService)->getGoodsPercentByLive(0, $current_livevideo_id, 'mall3');
                $is_livevideo = 0;
                if (isset($live_goods['goodids'])) {
                    foreach ($pre_order['goods_list'] as $livek => $liveg) {
                        if (in_array($liveg['goods_id'], $live_goods['goodids'])) {
                            $is_livevideo = 1;
                            $pre_order['goods_list'][$livek]['is_livevideo'] = 1;
                            $pre_order['goods_list'][$livek]['livevideo_id'] = $current_livevideo_id;
                            $pre_order['goods_list'][$livek]['liveshow_percent'] = $live_goods['percent'][$liveg['goods_id']];
                        }
                    }
                }
                if ($is_livevideo == 1) {
                    $order['is_livevideo'] = 1;
                    $order['livevideo_id'] = $current_livevideo_id;
                }
            }
            if ($order_id = $this->MallOderModel->add($order)) {
                if (!in_array($pre_order['mer_id'], $mer_ids)) {
                    $mer_ids[] = $pre_order['mer_id'];
                }

                $success_orderids[] = $order_id;
                $pre_order['activity']['type'] = $pre_order['activity']['type'] ?? '';
                $minus_discount_goods_total_money = 0;
                if($pre_order['activity']['type'] == 'minus_discount'){
                    foreach ($pre_order['goods_list'] as $i_goods){
                        if(in_array($i_goods['sku_id'], $pre_order['activity']['skuids'])){
                            $minus_discount_goods_total_money += $i_goods['num'] * $i_goods['price'];
                        }
                    }
                }
                foreach ($pre_order['goods_list'] as $goods) {
                    //算单品参与优惠后的实际总价money_total
                    $money_total = $goods['num'] * $goods['price'];
                    if($pre_order['activity']['type'] == 'minus_discount' && in_array($goods['sku_id'], $pre_order['activity']['skuids'])){
                        $bili_minus_discount = $minus_discount_goods_total_money > 0 ? $money_total / $minus_discount_goods_total_money : 1;
                    }
                    $bili = $pre_order['total']['goods_money_total'] > 0 ? $money_total / $pre_order['total']['goods_money_total'] : 1;//算在总额中的占比
                    //计算各个优惠，该单品应该享受多少优惠价
                    //1、店铺活动
                    if (isset($pre_order['activity']['skuids']) && $pre_order['activity']['type'] != 'shipping' && in_array($goods['sku_id'], $pre_order['activity']['skuids']) && $pre_order['activity']['money'] > 0) {
                        if($pre_order['activity']['type'] == 'minus_discount'){
                            $money_total -= $pre_order['activity']['money'] * $bili_minus_discount;
                        }else {
                            $money_total -= $pre_order['activity']['money'] * $bili;
                        }
                    }
                    //2、店铺优惠券
                    if ($order['discount_merchant_coupon'] > 0) {
                        $money_total -= $order['discount_merchant_coupon'] * $bili;
                    }
                    //3、商家会员卡
                    if ($order['discount_merchant_card'] > 0) {
                        $money_total -= $order['discount_merchant_card'] * $bili;
                    }
                    //4、平台优惠券
                    if ($order['discount_system_coupon'] > 0) {
                        $money_total -= $order['discount_system_coupon'] * $bili;
                    }
                    $detail = [
                        'order_id' => $order_id,
                        'uid' => $uid,
                        'goods_id' => $goods['goods_id'],
                        'sku_id' => $goods['sku_id'],
                        'name' => $goods['goods_name'],
                        'image' => $goods['image_original'],
                        'sku_info' => $goods['sku_str'],
                        'num' => $goods['num'],
                        'price' => $goods['price'] + ($goods['end_price'] ?? 0),
                        'money_total' => get_format_number($money_total),
                        'activity_type' => $goods['activity_type'],
                        'activity_id' => $goods['activity_id'],
                        'is_gift' => 0,
                        'notes' => $goods['notes'] ?: '',
                        'forms' => $goods['forms'] ?: '',
                        'is_liveshow' => $goods['is_liveshow'] ?? 0,
                        'is_livevideo' => $goods['is_livevideo'] ?? 0,
                        'liveshow_id' => $goods['liveshow_id'] ?? 0,
                        'livevideo_id' => $goods['livevideo_id'] ?? 0,
                        'liveshow_percent' => $goods['liveshow_percent'] ?? 0
                    ];
                    if (!$this->MallOderDatail->add($detail)) {
                        throw new \think\Exception("保存订单详情失败，请联系管理员");
                    }

                    //增加销量
                    $MallGoodsModel = new MallGoods;
                    $MallGoodsModel->where('goods_id', $goods['goods_id'])->inc('sale_num', $goods['num'])->update();

                    switch ($goods['activity_type']) {
                        case 'limited':
                            $ret=(new MallActivityService)->saveOrderLimitAct($goods['activity_id'], $goods['sku_id'], $goods['num']);
                            if(!$ret){
                                throw new \think\Exception("库存不足");
                            }
                            break;
                        case 'group':
                            (new MallActivityService)->saveOrderUpdateGroupTeam($goods['activity_id'], $uid, $order_id, $goods['activity_field'] ?: 0);
                            break;
                        case 'bargain':
                            (new MallActivityService)->saveOrderUpdateBargainTeamStatus($goods['activity_field'], $order_id);
                            break;
                        case 'periodic':
                            (new MallActivityService)->createPeriodic($goods['activity_id'], $order_id, $goods['activity_field']);
                            break;
                        case 'prepare':
                            (new MallActivityService)->saveOrderUpdatePrepareMsg($order_id, $goods['activity_id'], $goods['goods_id'], $goods['sku_id'], $goods['price'] * $goods['num'], $goods['end_price'] * $goods['num'], $uid);
                            break;
                    }
                }
                $give_sku_relation = [];
                foreach ($pre_order['give_goods'] as $give) {
                    $give_detail = [
                        'order_id' => $order_id,
                        'goods_id' => $give['goods_id'],
                        'sku_id' => $give['sku_id'],
                        'name' => $give['goods_name'],
                        'image' => $give['image_original'],
                        'sku_info' => $give['sku_str'],
                        'num' => $give['num'],
                        'price' => 0,
                        'money_total' => 0,
                        'is_gift' => 1,
                    ];
                    if (!$this->MallOderDatail->add($give_detail)) {
                        throw new \think\Exception("保存订单详情失败，请联系管理员");
                    }
                    $give_sku_relation[] = ['act_id' => $pre_order['activity']['id'], 'sku_id' => $give['sku_id'], 'nums' => $give['num']];
                }
                if (!empty($give_sku_relation)) {
                    (new MallFullGiveGiftSkuService)->paySuccessMinusStock($give_sku_relation);
                }
                $this->changeOrderStatus($order_id, 0, '订单下单成功',[],0,'',$activity_type);
                (new MallOrderRemindService)->insertRemind($pre_order['store_id'], 10, $order_id);
            } else {
                throw new \think\Exception("保存订单失败，请联系管理员");
            }
        }
        if ($success_orderids) {
            $mer_id = count($mer_ids) == 1 ? $mer_ids[0] : 0;
            $store_id = count($success_orderids) == 1 ? $confirm_data['order'][0]['store_id'] : 0;
            $business_order_sn = implode(',', $business_order_sns);
            return (new MallOrderCombineService)->addData($uid, $success_orderids, $mer_id, $store_id, 0, 0, $business_order_sn);
        }
    }

    //更新订单信息
    public function updateMallOrder($where, $data)
    {
        return $this->MallOderModel->updateThis($where, $data);
    }

    /**
     * @param $where
     * @return mixed
     * 获取今日销量
     */
    public function getTodaySaleNum($where)
    {
        $number = $this->MallOderModel->getTodaySaleNum($where);
        return $number;
    }
    /**
     * @param $where
     * @return mixed
     * 获取今日退款数量
     */
    public function getTodayRefundNum($where)
    {
        $number = (new MallOrderRefund())->getTodayRefundNum($where);
        return $number;
    }

    /**
     * 统一的修改订单状态（所有修改订单状态的地方必须调用这个方法）
     * 方便统一处理
     * add by lumin
     */
    public function changeOrderStatus($order_id, $status, $note, $detail_where = [], $detail_status = 0, $order_no = '',$activity_type='normal',$verifyInfo=[],$is_throw_err=1)
    {
        if (empty($order_id) && empty($order_no)) return false;
        if ($order_no) {
            $now_order_info = $this->MallOderModel->getOrder([['order_no', '=', $order_no]], true, true);
            $order_id = $now_order_info['order_id'];
        }
        Db::startTrans();
        try {
            $order_info = $this->MallOderModel->getOne($order_id);
            $old_status = $order_info['status'];
            //判断状态是否可以更改
//            $this->checkOrderStatus($status,$order_info['status']);
            $where = [];
            if (is_array($order_id)) {
                $where[] = ['order_id', 'in', $order_id];
            } else {
                $where[] = ['order_id', '=', $order_id];
            }
            $update = [
                'status' => $status,
                'last_uptime' => time()
            ];
            $updateOrder = $update;
            //如果是确认收货，录入完成时间
            if ($status >= 30 && $status < 40) {
                $updateOrder = [
                    'status' => $status,
                    'last_uptime' => time(),
                    'complete_time' => time()
                ];
            }
            if($verifyInfo){
                $update['verify_status'] = $verifyInfo['status'];
                $update['staff_id'] = $verifyInfo['staff_id'];
                $update['staff_name'] = $verifyInfo['staff_name'];
                $updateOrder['verify_status'] = $verifyInfo['status'];
                $updateOrder['staff_id'] = $verifyInfo['staff_id'];
                $updateOrder['staff_name'] = $verifyInfo['staff_name'];
            }
            $res = $this->MallOderModel->updateThis($where, $updateOrder);
            if ($res) {
                if (!empty($detail_where)) {//用作部分订单详情的状态修改
                    $where = $detail_where;
                    $update['status'] = $detail_status;
                }

                $this->MallOderDatail->updateThis($where, $update);
                $this->mallOrderlog($order_id, $status, $note);
                foreach ($order_info as $k=>$v){
                    $order_info[$k] = $updateOrder[$k]??$v;
                }
                //逻辑业务处理
                switch ($status) {
                    case 0://下单
                        $order_detail = $this->MallOderDatail->getSome([['order_id', '=', $order_id]])->toArray();
                        $storeInfo = (new MerchantStore())->getOne(['store_id' => $order_info['store_id']]);
                        $gnames = $storeInfo['name'] . implode('', array_column($order_detail, 'name'));
                        (new SystemOrderService)->saveOrder('mall3', $order_id, $order_info['uid'], [
                            'mer_id' => $order_info['mer_id'], 
                            'store_id' => $order_info['store_id'],
                            'price' => $order_info['money_real'],
                            'total_price' => $order_info['money_total'],
                            'real_orderid' => $order_info['order_no'],
                            'keywords' => $gnames,
                        ]);
                        //扣库存
                        $detail_info = $this->MallOderDatail->getByOrderId('goods_id,sku_id,num', $order_id);
                        foreach ($detail_info as $odetail) {
                            (new MallGoodsSkuService)->changeStock($odetail['sku_id'], $odetail['goods_id'], -1, $odetail['num'],$activity_type);
                        }
                        break;
                    case 10:
                        // 通知店员
                        (new SendTemplateMsgService())->staffPushMessage($order_info);
                        (new UserNoticeService())->addNotice([
                            'type'=>0,
                            'business'=>'mall',
                            'order_id'=>$order_id,
                            'mer_id'=>$order_info['mer_id'],
                            'store_id'=>$order_info['store_id'],
                            'uid'=>$order_info['uid'],
                            'title'=>'商城商品下单成功',
                            'content'=>'下单成功，商品将尽快为你送达',
                            'is_throw_err'=>$is_throw_err
                        ]);
                        (new SystemOrderService)->paidOrder('mall3', $order_id);
                        //生成配送单号（仅骑手配送订单）
                        if($order_info['express_style']==1){
                            $fetchNumber = $this->getFetchNumber();
                            $this->MallOderModel->updateThis(['order_id'=>$order_id], ['fetch_number'=>$fetchNumber]);
                        }
                        break;
                    case 11:
                        // 接单
                        (new UserNoticeService())->addNotice([
                            'type'=>0,
                            'business'=>'mall',
                            'order_id'=>$order_id,
                            'mer_id'=>$order_info['mer_id'],
                            'store_id'=>$order_info['store_id'],
                            'uid'=>$order_info['uid'],
                            'title'=>'商家已接单',
                            'content'=>'商家已接单，商品将尽快为你送达'
                        ]);
                        break;
                    case 13:
                        (new SystemOrderService)->paidOrder('mall3', $order_id);
                        break;
                    case 20:
                        (new SystemOrderService)->sendOrder('mall3', $order_id);
                        //已发货推送消息
                        (new SendTemplateMsgService())->sendWxappMessage(['type' => 'order_status_change_notice', 'order_id' => $order_id, 'status' => $status]);
                        (new UserNoticeService())->addNotice([
                            'type'=>0,
                            'business'=>'mall',
                            'order_id'=>$order_id,
                            'mer_id'=>$order_info['mer_id'],
                            'store_id'=>$order_info['store_id'],
                            'uid'=>$order_info['uid'],
                            'title'=>'商品已发货',
                            'content'=>'您的商品已经发货，请进入订单详情查看快递进度'
                        ]);
                        break;
                    case 30:
                        $this->userScoreGet($order_info);
                        (new SystemOrderService)->completeOrder('mall3', $order_id);
                        $result = $this->merchantAddMoney($order_id);
                        if(!empty($result['error_msg'])){
                            throw_exception($result['error_msg']);
                        }
                        //已送达推送消息
                        (new SendTemplateMsgService())->sendWxappMessage(['type' => 'order_status_change_notice', 'order_id' => $order_id, 'status' => $status]);
                        (new UserNoticeService())->addNotice([
                            'type'=>0,
                            'business'=>'mall',
                            'order_id'=>$order_id,
                            'mer_id'=>$order_info['mer_id'],
                            'store_id'=>$order_info['store_id'],
                            'uid'=>$order_info['uid'],
                            'title'=>'商品已经送达',
                            'content'=>'您的商城商品已经送达，点击查看订单详情'
                        ]);
                        break;
                    case 31://用户确认自提
                        $this->userScoreGet($order_info);
                        (new SystemOrderService)->completeOrder('mall3', $order_id);
                        $this->merchantAddMoney($order_id);
                        (new UserNoticeService())->addNotice([
                            'type'=>0,
                            'business'=>'mall',
                            'order_id'=>$order_id,
                            'mer_id'=>$order_info['mer_id'],
                            'store_id'=>$order_info['store_id'],
                            'uid'=>$order_info['uid'],
                            'title'=>'核销成功，已经领取商品',
                            'content'=>'您已经领取店铺自提商品，请核对订单'
                        ]);
                        break;
                    case 32://店员确认核销
                        $this->userScoreGet($order_info);
                        (new SystemOrderService)->completeOrder('mall3', $order_id);
                        $this->merchantAddMoney($order_id);
                        (new UserNoticeService())->addNotice([
                            'type'=>0,
                            'business'=>'mall',
                            'order_id'=>$order_id,
                            'mer_id'=>$order_info['mer_id'],
                            'store_id'=>$order_info['store_id'],
                            'uid'=>$order_info['uid'],
                            'title'=>'核销成功，已经领取商品',
                            'content'=>'您已经领取店铺自提商品，请核对订单'
                        ]);
                        break;
                    case 33: //骑手确认送达
                        if ($order_info['status'] != 70) {  //已全部退款订单，骑手送货完成也不在更新订单状态
                            $this->userScoreGet($order_info);
                            (new SystemOrderService)->completeOrder('mall3', $order_id);
                            $this->merchantAddMoney($order_id);
                            fdump_api($order_id,'checkMaLLOrderDeliver',1);
                            //已送达推送消息
                            (new SendTemplateMsgService())->sendWxappMessage(['type' => 'order_status_change_notice', 'order_id' => $order_id, 'status' => $status]);
                        }
                        break;
                    case 50:
                    case 51://订单超时未支付取消订单
                    case 52://用户取消订单
                    case 53://管理员取消订单
                        (new SystemOrderService)->cancelOrder('mall3', $order_id);
                        $order_info = $this->MallOderModel->getOne($order_id);
                        if ($order_info['goods_activity_type'] == 'group') {//拼团失败通知
                            $this->mallActivityService->saveOrderUserUpdate($order_id);
                        }
                        if ($order_info['goods_activity_type'] == 'bargain') {//砍价订单超时通知
                            $this->mallActivityService->fifteenMiniuteRefundBargainOrder($order_id);
                        }
                        $is_refund = false;
                        if(!in_array($old_status,[0,50,51,52,53,70])){
                            $is_refund = true;
                        }
                        $this->cancelOrder($order_id,$is_refund);
                        //加库存
                        $detail_info = $this->MallOderDatail->getByOrderId('goods_id,sku_id,num,activity_type,activity_id', $order_id);
                        foreach ($detail_info as $odetail) {
                            if ($odetail['activity_type'] == 'limited') {//限时优惠增加库存
                                $this->mallActivityService->refundOrderLimitAct($odetail['activity_id'], $odetail['sku_id'], $odetail['num']);
                                (new MallGoodsSkuService)->changeStock($odetail['sku_id'], $odetail['goods_id'], 1, $odetail['num']);
                            }else{
                                (new MallGoodsSkuService)->changeStock($odetail['sku_id'], $odetail['goods_id'], 1, $odetail['num']);
                            }
                        }

                        //同步第三方配送状态
                        if($order_info['is_third_deliver']&&$order_info['pay_time']>0){
                            (new DeliverApiService())->cancel($order_info,$status);
                        }

                        if(in_array($status,[51,52,53])){
                            $noticeContent = '支付超时，订单取消';
                            if($status == 51){
                                $noticeContent = '支付超时，订单取消';
                            }
                            if($status == 53){
                                $noticeContent = '系统管理员取消订单';
                            }
                            (new UserNoticeService())->addNotice([
                                'type'=>0,
                                'business'=>'mall',
                                'order_id'=>$order_id,
                                'title'=>'订单取消提醒',
                                'content'=>$noticeContent
                            ]);
                        }
                        break;
                    case 60://用户申请售后
                        (new MallOrder())->updateThis(['order_id'=>$order_id], ['status'=>60]);
                        (new SystemOrder())->where(['type'=>'mall3','order_id'=>$order_id])->save(['system_status'=>7]);
                        break;
                    case 70:
                        (new MallOrder())->updateThis(['order_id'=>$order_id], ['status'=>70]);
                        (new SystemOrderService)->refundOrder('mall3', $order_id);
                        //同步第三方配送状态
                        if($order_info['is_third_deliver']){
                            (new DeliverApiService())->cancel($order_info,$status);
                        }

                        break;
                    default:
                        # code...
                        break;
                }
            }
            Db::commit();
            if ($status != 70) {
                invoke_cms_model('System_order/sendAppPushMessage', ['orderId' => $order_id, 'type' => 'mall3']);
            }

            //订单取消发送短信 (用户取消、已退款)
            if ($status == 52 || $status == 70) {
                $now_user = (new UserService())->getUser($order_info['uid']);
                $merchant = (new MerchantService())->getByMerId($order_info['mer_id'],'*');
                if (in_array(cfg('sms_cancel_order'), [2, 3]) && $merchant['phone']) {
                    $sms_data['uid'] = 0;
                    $sms_data['mobile'] = $merchant['phone'];
                    $sms_data['sendto'] = 'merchant';
                    $sms_data['content'] = L_('顾客购买的X1的订单(订单号：X2)在X3时已被客户取消并退款！', array('X1' => cfg('mall_alias_name_new'), 'X2' => $order_info['order_no'], 'X3' => date('Y-m-d H:i:s')));
                    (new \app\common\model\service\send_message\SmsService())->sendSms($sms_data);
                }
                if (in_array(cfg('sms_cancel_order'), [1, 3]) && $now_user['phone']) {
                    $sms_data['uid'] = $now_user['uid'];
                    $sms_data['mobile'] = $now_user['phone'];
                    $sms_data['sendto'] = 'user';
                    $sms_data['content'] = L_('您在X1店中下的订单（订单号：X2），在X3已被取消并退款，欢迎再次光临！', array('X1' => cfg('mall_alias_name_new'), 'X2' => $order_info['order_no'], 'X3' => date('Y-m-d H:i:s')));
                    (new \app\common\model\service\send_message\SmsService())->sendSms($sms_data);
                }
            }
        } catch (\Exception $e) {
            Db::rollback();
            fdump_api([$order_id,$e->getMessage()],'mall_staff_error',1);
            throw new \think\Exception($e->getMessage());

        }
        return $res;
    }
    /**
     * 查询新版骑手配送商城取单号
     */
    public function getFetchNumber()
    {
        $fetchNumber = 1;
        $startTime = strtotime(date('Y-m-d',time()));
        $endTime = time();
        $where = [
            ['fetch_number','>',0],
            ['pay_time','between',[$startTime,$endTime]],
        ];
        $lastNum = $this->MallOderModel->where($where)->order('fetch_number desc')->value('fetch_number');
        if($lastNum && $lastNum>0){
            $fetchNumber = $lastNum+1;
        }
        return $fetchNumber;
    }
    /**
     * 获取积分：商品单独积分<商家编辑设置积分<系统后台设置积分
     * @param $order_info
     */
    public function userScoreGet($order_info){
        $score_round_two = cfg('score_round_two') ? 2 : 1;//积分保留几位小数
        //获取订单商品详情
        $goods_detail = $this->MallOderDatail->getRealOrderGoods([['d.order_id','=',$order_info['order_id']],['g.score_percent','<>',''],['g.score_percent','<>','0']],'d.money_total,d.name,d.num,g.goods_id,g.score_percent,g.score_percent_type,g.score_max');
        $goods_score = [];
        //商品单独积分
        foreach ($goods_detail as $item){
            $score = 0;
            if($item['score_percent']&&$item['score_percent']!=0){
                if(strpos($item['score_percent'], "%")!=false){
                    $percent = explode('%',$item['score_percent']);
                    $score = $item['money_total']*$percent[0]/100;
                }else{
                    $score = $item['money_total']*$item['score_percent'];
                }
                if($item['score_percent_type']==1&&($score>$item['score_max'])){
                    $score = $item['score_max'];
                }
                $order_info['money_real'] -= $item['money_total'];
            }
            if($score){
                $goods_score[] = [
                    'name' => $item['name'].'*'.$item['num'],
                    'score' => $score
                ];
            }
        }
        foreach ($goods_score as $val){
            $info = [
                'mer_id' => $order_info['mer_id'],
                'store_id' => $order_info['store_id'],
                'order_id' => $order_info['order_id'],
                'order_type' => 'mall',
            ];
            $ret=(new UserService())->addScore($order_info['uid'],$val['score'],'商城购买'.$val['name'].'获得积分',0,$info);
            if($ret['error_code']){
                fdump_sql(['order'=>$order_info,'msg'=>$ret['msg']],'addScoreError');
            }
        }

        //商家编辑设置积分计算
        //查询商家信息
        $merchantScorePercent = (new Merchant())->where('mer_id',$order_info['mer_id'])->value('score_get_percent');
        if($merchantScorePercent && $merchantScorePercent>0){
            $score = bcdiv(bcmul($order_info['money_real'],$merchantScorePercent,0),100,$score_round_two);
            $info = [
                'mer_id' => $order_info['mer_id'],
                'store_id' => $order_info['store_id'],
                'order_id' => $order_info['order_id'],
                'order_type' => 'mall',
            ];
            $ret=(new UserService())->addScore($order_info['uid'],$score,'商城消费获得积分',0,$info);
            if($ret['error_code']){
                fdump_sql(['order'=>$order_info,'msg'=>$ret['msg']],'addScoreError');
            }
            $order_info['money_real'] = 0;//直接修改为0，防止继续走系统后台积分计算
        }

        //系统后台设置积分计算
        $order_info['money_real'] = $order_info['money_real']>0?$order_info['money_real']:0;
        if(cfg('user_score_get')>0 && cfg('add_score_by_system_commission')==0){//积分设置--消费1元获得积分数
            if(cfg('open_score_get_percent')){//百分比
                $score = bcdiv(bcmul($order_info['money_real'],cfg('user_score_get'),0),100,$score_round_two);
            }else{//积分
                $score = bcmul($order_info['money_real'],cfg('user_score_get'),$score_round_two);
            }
            $info = [
                'mer_id' => $order_info['mer_id'],
                'store_id' => $order_info['store_id'],
                'order_id' => $order_info['order_id'],
                'order_type' => 'mall',
            ];
            $ret=(new UserService())->addScore($order_info['uid'],$score,'商城消费获得积分',0,$info);
            if($ret['error_code']){
                fdump_sql(['order'=>$order_info,'msg'=>$ret['msg']],'addScoreError');
            }
        }
    }

    /**
     * 获取订单信息
     * @param  [type] $where [description]
     * @return [type]        [description]
     */
    public function getOrders($where)
    {
        $return = (new MallOrder())->getSome($field = "*", $where);

        return $return;
    }

    /**
     * @param $order_id
     * 手动接单
     */
    public function orderTaking($order_id, $periodic_order_id, $current_periodic, $periodic_count, $note = '')
    {
        if (empty($order_id)) {
            throw new \think\Exception('缺少order_id参数');
        }
        Db::startTrans();
        try {
            $order_info = (new MallOrder())->getOne($order_id);
            if($order_info['status'] > 10){
                throw new \think\Exception('订单当前状态无法接单，请刷新页面');
            }
            if (!empty($periodic_order_id)) {
                //周期购订单状态更新
                $note = $note ? $note : '第' . $current_periodic . '期已接单，当前处于该笔订单正在备货中';
                $this->mallActivityService->updatePeriodicOrder($periodic_order_id, 1);
                $this->mallOrderlog($order_id, 11, $note);
                if (($current_periodic == $periodic_count) && !empty($current_periodic) && !empty($periodic_count)) {
                    //所有的周期购子记录都完成后更新主表的订单状态为已发货
                    //把状态置成11  记录日志
                    $note = '周期购最后一期完成接单';
                    $this->changeOrderStatus($order_id, 11, $note);
                }
                if ($order_info['express_style'] == 1) {
                    //骑手纪录店员接单
                    (new MallRiderService())->addRecord('periodic', $periodic_order_id, 0, '店员已接单', '', '', []);
                }
            } else {
                //把状态置成11  记录日志
                $note = '已接单，当前该笔订单正在备货中';
                $this->changeOrderStatus($order_id, 11, $note);
                //骑手纪录店员接单
                if ($order_info['express_style'] == 1) {
                    //骑手纪录店员接单
                    (new MallRiderService())->addRecord('order', $order_id, 0, '店员已接单', '', '', []);
                }
            }

            // // 接单成功后打印小票
            $param = [
                'order_id' => $order_id,
                'print_type' => 'bill_account'
            ];
            $res = (new PrintHaddleService)->printOrder($param, 1);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * @param $order_id *订单id
     * @param $note *日志描述/内容
     * 订单流转记录日志(所有订单日志记录走此方法)
     */
    public function mallOrderlog($order_id, $status, $note = '')
    {
        if (empty($order_id)) {
            throw new \think\Exception('缺少order_id参数');
        }
        $data = ['order_id' => $order_id, 'note' => $note, 'status' => $status, 'addtime' => time()];
        $res = $this->MallOrderLog->addOne($data);
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        }
        return true;
    }

    /**
     * @param $order_id
     * @return bool
     * 店员核销
     */
    public function staffVerify($order_id,$type='',$staffUser=[])
    {
        if (empty($order_id)) {
            throw new \think\Exception('缺少order_id参数');
        }
        $orderInfo = $this->getOne($order_id);
        if($orderInfo['status'] >= 30 && $orderInfo['status']<= 40){
            return true;
        }
        if($orderInfo['express_style'] != 3){
            throw new \think\Exception('非自提订单，无法核销');
        }
        if($orderInfo['status'] == 10){
            throw new \think\Exception('订单未接单，无法核销');
        }
        if($orderInfo['status'] < 10){
            throw new \think\Exception('订单未支付，无法核销');
        }
        if($orderInfo['status'] < 60 && $orderInfo['status'] >= 50){
            throw new \think\Exception('订单已取消，无法核销');
        }
        if($orderInfo['status'] == 70){
            throw new \think\Exception('订单已退款，无法核销');
        }
        if ($staffUser['store_id'] != $orderInfo['store_id']) {
            throw new \think\Exception('订单不存在，无法核销');
        }
        $note = '店员核销,该笔订单已完成';
        $verifyInfo = [
            'status'=>$type ? 1 : 2,//1-移动端店员核销，2-pc端店员核销，3-扫码核销
        ];
        $verifyInfo['staff_id'] = $staffUser ? $staffUser['id'] : 0;
        $verifyInfo['staff_name'] = $staffUser ? $staffUser['username'] : 0;
        $res = $this->changeOrderStatus($order_id, 32, $note, [], 0, '','normal',$verifyInfo);
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        }
        return true;
    }

    public function orderDeliveryList($orderId, $periodicOrderId = 0)
    {
        if(!empty($periodicOrderId)){
            $order = MallNewPeriodicDeliver::where([
                    'order_id'          => $orderId,
                    'purchase_order_id' => $periodicOrderId,
                ])
                ->field('express_id, express_num, express_code, express_name')
                ->find();

            $order && $order['extra_delivery'] = MallOrderDelivery::where([
                    'order_id'          => $orderId,
                    'periodic_order_id' => $periodicOrderId,
                    'is_del' => 0,
                ])
                ->field('express_id, express_num, express_code, express_name')
                ->select()
                ->toArray();
        }else{
            $order = MallOrder::where('order_id', $orderId)
                ->with(['extra_delivery' => function ($query) {
                    $query->where('is_del', 0)
                        ->where('periodic_order_id', 0)
                        ->field('order_id, order_detail_ids, express_id, express_code, express_num, express_name, express_time');
                }])
                ->field('order_id, express_id, express_code, express_num,express_name, order_detail_ids, phone')
                ->find()
                ->toArray();
        }
        if(empty($order) || empty($order['express_num'])){
            return [];
        }
        $delivery = [[
            'express_id'       => $order['express_id'],
            'express_code'     => $order['express_code'],
            'express_num'      => $order['express_num'],
            'express_name'     => $order['express_name'],
            'order_detail_ids' => $order['order_detail_ids'] ?? ''
        ]];
        if (!empty($order['extra_delivery'])) {
            $delivery = array_merge($delivery, $order['extra_delivery']);
        }
        foreach ($delivery as &$v) {
            $v['logistics'] = DeliveryLogistics::getLogistics($v['express_code'], $v['express_num'], $order['phone']);
            if(!empty($v['order_detail_ids'])){
                $v['goods_detail'] = MallOrderDetail::where('id', 'IN', explode(',', $v['order_detail_ids']))
                    ->field('name, image')
                    ->select()
                    ->each(function ($item) {
                        $item->image = replace_file_domain($item->image);
                    });
            }else{
                $v['goods_detail'] = MallOrderDetail::where('order_id', $orderId)
                    ->field('name, image')
                    ->select()
                    ->each(function ($item) {
                        $item->image = replace_file_domain($item->image);
                    });
            }
        }

        return $delivery;
    }
    
    public function orderExtraDelivery(array $params)
    {
        $params['express_time'] = time();
        MallOrderDelivery::where('order_id', $params['order_id'])
            ->save([
                'is_del'      => MallOrderDelivery::IS_DEL_1,
                'delete_time' => date('Y-m-d H:i:s')
            ]);
        foreach ($params['extra_delivery'] as $v){
            $expressInfo = (new Express())->getOne(['id' => $v['express_id']]);
            empty($expressInfo) && throw_exception(L_('快递公司编码参数缺失'));
            
            $v = array_merge($v,[
                'express_name' => $expressInfo['name'],
                'express_code' => $expressInfo['code'],
                'is_del'       => MallOrderDelivery::IS_DEL_0,
                'delete_time'  => NULL
            ]);
            
            $params['express_num'] = $v['express_no'];
            $express = array_merge($params, $v);
            
            if(!empty($v['id'])){
               $orderDelivery = MallOrderDelivery::where([
                    'id' => $v['id'],
                    'order_id' => $params['order_id'],
                ])->find();
                empty($orderDelivery) && throw_exception(L_('快递数据异常'));
            }else{
                $orderDelivery = new MallOrderDelivery();
            }
            unset($express['Device-Id']);
            $orderDelivery->setAttrs($express);
            $orderDelivery->save();
        }
        return true;
    }
    /**
     * @param $order_id
     * 快递配送
     */
    public function deliverGoodsByExpress($param)
    {
        if (empty($param['order_id'])) {
            throw new \think\Exception('缺少order_id参数');
        }
        if (empty($param['store_id'])) {
            throw new \think\Exception('缺少store_id参数');
        }
        if (empty($param['express_id'])) {
            throw new \think\Exception('缺少express_id参数');
        }
        if (empty($param['express_name'])) {
            $express=(new Express())->getOne(['id'=>$param['express_id']]);
            if(empty($express)){
                throw new \think\Exception('快递信息错误');
            }else{
                $param['express_name']=$express['name'];
            }
        }
        //电子面单发货
        if ($param['express_type'] == 1) {
            $faceInfo = (new MerchantStoreMallService())->getStoremallInfo($param['store_id'], 'single_face_info');
            if (!empty($faceInfo)) {
                $faceInfo = json_decode($faceInfo['single_face_info'], true);
                foreach ($faceInfo as $val) {
                    if ($val['name'] == $param['express_name']) {
                        $express = $val;
                    }
                }
                if (empty($express)) {
                    throw new \think\Exception('店铺没有配置' . $param['express_name'] . '的电子面单发货');
                }
                //获取快递信息
                $singleFaceService = new SingleFaceService();
                if ($param['activity_type'] == 'periodic') {
                    $result = $singleFaceService->getSingleFace($param['order_id'], $express, $param['periodic_order_id']);
                } else {
                    $result = $singleFaceService->getSingleFace($param['order_id'], $express, 0);
                }
                if ($result['code'] === 0) {
                    $express['express_num'] = $result['data']['express_num'] ?: '';
                    $express['express_code'] = $result['data']['express_code'] ?: '';
                    if (empty($express['express_num']) || empty($express['express_code'])) {
                        throw new \think\Exception('电子面单返回参数有误');
                    }
                    $express_info = (new Express())->getOne(['code' => $express['express_code']]);
                    $express['name'] = $express_info['name'];
                    $express['id'] = $express_info['id'];
                    $this->changeExpereeInfo($param, $express);
                } else {
                    throw new \think\Exception($result['msg']);
                }
            } else {
                throw new \think\Exception('该店铺没有配置快递');
            }
        }
        elseif ($param['express_type'] == 2) {//普通发货
            if (empty($param['express_no'])) {
                throw new \think\Exception('请输入快递单号');
            }
            //获取快递code
            $expressInfo = (new Express())->getOne(['id' => $param['express_id']]);
            if (!empty($expressInfo)) {
                $code = $expressInfo['code'];
            } else {
                throw new \think\Exception('快递公司编码参数缺失');
            }
            Db::StartTrans();
            try {
                if ($param['fh_type'] == 1) {
                    if ($param['activity_type'] == 'periodic') {
                        $where_periodic=[['is_complete','in',[0,1]],['order_id','=',$param['order_id']]];
                        if (empty($param['periodic_order_id'])) {
                            throw new \think\Exception('periodic_order_id参数缺失');
                        }
                        //入库周期购子表
                        $this->mallActivityService->addPeriodicDeliver($param['order_id'], $param['periodic_order_id'], 0, $param['express_no'], $param['current_periodic'], 0, 0, $param['express_name'], $param['express_id'], $code);//邓远辉提供
                        //周期购订单状态更新
                        $this->mallActivityService->updatePeriodicOrder($param['periodic_order_id'], 3);
                        $note = '快递发货，第' . $param['current_periodic'] . '期普通发货成功';
                        $this->mallOrderlog($param['order_id'], 20, $note);
                        $list=(new MallNewPeriodicPurchaseOrder())->getPeriodicOrderList($where_periodic);
                        if ((($param['current_periodic'] == $param['periodic_count']) && !empty($param['current_periodic']) && !empty($param['periodic_count'])) || empty($list)) {
                            $this->checkOrderStatus(20,0,$param['order_id']);
                            //所有的周期购子记录都完成后更新主表的订单状态为已发货
                            $note1 = '周期购最后一期完成发货';
                            $this->changeOrderStatus($param['order_id'], 20, $note1);
                        }
                    } else {
                        $this->checkOrderStatus(20,0,$param['order_id']);
                        //入库
                        $this->MallOderModel->updateThis(['order_id' => $param['order_id']],
                            [
                                'express_num'  => $param['express_no'],
                                'express_name' => $param['express_name'],
                                'express_id'   => $param['express_id'],
                                'send_time'    => time(),
                                'express_code' => $code,
                                'order_detail_ids' => $param['order_detail_ids'] ?? '',
                                'express_time' => time()
                            ]);
                        $note = '快递发货，普通发货成功';
                        $this->changeOrderStatus($param['order_id'], 20, $note);
                    }
                    //只在第一次发货的时候更新发货时间（修改快递时不更新）
                    $this->MallOderModel->updateThis(['order_id' => $param['order_id']], ['send_time' => time()]);
                } elseif ($param['fh_type'] == 2) {
                    if ($param['activity_type'] == 'periodic') {
                        if (empty($param['periodic_order_id'])) {
                            throw new \think\Exception('periodic_order_id参数缺失');
                        }
                        //更新周期购子表
                        $periodic_data = ['express_num' => $param['express_no'], 'express_name' => $param['express_name'], 'express_id' => $param['express_id'], 'express_code' => $code];
                        $periodic_where = ['purchase_order_id' => $param['periodic_order_id']];
                        $this->mallActivityService->updatePeriodicDeliver($periodic_where, $periodic_data);//邓远辉提供
                        $note = '第' . $param['current_periodic'] . '期修改快递发货，修改快递为' . $param['express_name'] . '成功';
                        $this->mallOrderlog($param['order_id'], 20, $note);
                    } else {
                        $this->checkOrderStatus(20,0,$param['order_id']);
                        //更新入库
                        $this->MallOderModel->updateThis(['order_id' => $param['order_id']],
                            [
                                'express_num'      => $param['express_no'],
                                'express_name'     => $param['express_name'],
                                'express_id'       => $param['express_id'],
                                'send_time'        => time(),
                                'express_code'     => $code,
                                'order_detail_ids' => $param['order_detail_ids'] ?? '',
                                'express_time'     => time()
                            ]);
                        $note = '修改快递发货，修改快递为' . $param['express_name'] . '成功';
                        $this->changeOrderStatus($param['order_id'], 20, $note);
                    }
                } else {
                    throw new \think\Exception('fh_type参数错误');
                }
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                throw new \think\Exception($e->getMessage());
            }
        } else {
            throw new \think\Exception('express_type 参数错误');
        }

        return true;

    }

    /**
     *电子面单发货信息快递状态修改
     */
    public function changeExpereeInfo($param, $express)
    {
        //获取快递信息
        if (empty($express)) {
            throw new \think\Exception('回调快递信息返回为空');
        }
        if (!empty($param['periodic_order_id'])) {
            //入库周期购子表
            $this->mallActivityService->addPeriodicDeliver($param['order_id'], $param['periodic_order_id'], 0, $express['express_num'], $param['current_periodic'], 0, 0, $express['name'], $express['id'], $express['code']);//邓远辉提供
            //周期购订单状态更新
            $this->mallActivityService->updatePeriodicOrder($param['periodic_order_id'], 3);
            $note = '第' . $param['current_periodic'] . '期快递发货，电子面单打印成功';
            $this->mallOrderlog($param['order_id'], 20, $note);
            if (($param['current_periodic'] == $param['periodic_count']) && !empty($param['current_periodic']) && !empty($param['periodic_count'])) {
                $this->checkOrderStatus(20,0,$param['order_id']);
                //所有的周期购子记录都完成后更新主表的订单状态为已发货
                $note1 = '周期购最后一期完成发货';
                $this->changeOrderStatus($param['order_id'], 20, $note1);
            }
        } else {
            $this->checkOrderStatus(20,0,$param['order_id']);
            //更新总表快递信息
            $this->MallOderModel->updateThis(['order_id' => $param['order_id']], ['express_num' => $express['express_num'], 'express_name' => $express['name'], 'express_id' => $express['id'], 'send_time' => time(), 'express_code' => $express['code']]);
            $note = '快递发货，电子面单打印成功';
            $this->changeOrderStatus($param['order_id'], 20, $note);
        }
    }

    /**
     * @param $order_id
     * 骑手配送
     */
    public function deliverGoodsByHouseman($order_id, $periodic_order_id, $current_periodic = 0,$merId = null)
    {
        if (empty($order_id)) {
            throw new \think\Exception('缺少order_id参数');
        }
        $now_order_id = $order_id;
        $orderInfo = $this->MallOderModel->getOne($order_id);
        $payInfo = (new PayOrderInfo())->getOne(['business' => 'mall', 'orderid' => $orderInfo['pay_orderno']]);
        $storeInfo = (new MerchantStore())->getOne(['store_id' => $orderInfo['store_id']]);
        $storeMallInfo = (new MerchantStoreMall())->getStoreConfigList(['store_id' => $orderInfo['store_id']], true);
        $jwdInfo = (new AreaService())->addressInfo($orderInfo['address']);
        $lng = '';
        $lat = '';
        if ($jwdInfo && $jwdInfo['status'] === 0) {
            $lng = $jwdInfo['result']['location']['lng'];
            $lat = $jwdInfo['result']['location']['lat'];
        }
        //杨宁宁说周期购的备货时间也是在完善店铺那里设置的
        $order_out_time = explode(';', $storeMallInfo['stockup_time']);
        if (!empty($order_out_time) && $order_out_time[0]) {
            $order_out_time = $order_out_time[0] * 24 * 60 * 60 + $order_out_time[1] * 60 * 60 + $order_out_time[2] * 60;
        } else {
            $order_out_time = 1800;
        }
        $order_out_time = $order_out_time + time();
        if (!empty($periodic_order_id)) {
            $periodicInfo = (new MallNewPeriodicPurchaseOrderService())->retCountByOrderid($periodic_order_id);
            if (!empty($periodicInfo)) {
                $expect_time = $periodicInfo['periodic_date'];
                $orderInfo['money_freight'] = number_format((float)($orderInfo['money_freight'] / ($periodicInfo['periodic_count'] ?? 1)), 2);
                $orderInfo['money_real'] = number_format((float)($orderInfo['money_real'] / ($periodicInfo['periodic_count'] ?? 1)), 2);
            }
            $now_order_id = $periodic_order_id;
            $item = 5;//周期购
        } else {
            $time = explode(',', $orderInfo['express_send_time']);
            // $expect_time = date('Y-m-d', $time[0]).' '.date('H:i', $time[0]).'-'.date('H:i', $time[1]);
            $expect_time = $time[0];
            $item = 4;//普通
        }
        $param = [
            'order_from' => 8, //新版商城固定传8
            'order_id' => $now_order_id, //商城订单主键id
            //'periodic_order_id' => $periodic_order_id, //周期购订单id(周期购暂未对接)
            'paid' => 1, //是否支付  1：已支付  0：未支付
            'pay_time' => $orderInfo['create_time'] ?? time(), //支付时间
            'real_orderid' => $orderInfo['order_no'], //商场订单长订单号
            'pay_type' => $payInfo['pay_type'] ?: "", //支付方式
            'money' => $orderInfo['money_real'],//应收金额
            'deliver_cash' => 0, //配送员应收现金
            'store_id' => $orderInfo['store_id'], //店铺ID
            'store_name' => $storeInfo['name'],//店铺名称
            'mer_id' => $orderInfo['mer_id'],//商家ID
            'from_site' => $storeInfo['adress'],//店铺地址
            'from_lnt' => $storeInfo['long'],//来源地经度
            'from_lat' => $storeInfo['lat'],//来源地纬度
            'province_id' => $storeInfo['province_id'],//店铺所在省份id
            'city_id' => $storeInfo['city_id'],//店铺所在城市id
            'area_id' => $storeInfo['area_id'],//店铺所在区域id
            'aim_site' => $orderInfo['address'],//收货地址
            'aim_lnt' => $lng,//收货地经度
            'aim_lat' => $lat,//收货地纬度
            'name' => $orderInfo['username'],//收货人名称
            'phone' => $orderInfo['phone'],//收货人手机号
            'fetch_number' => 0, //取单号
            'type' => $storeMallInfo['horseman_type'],//配送方式   0 系统配送 1商家配送
            'item' => $item,
            'order_out_time' => $order_out_time, //预计出单时间
            'appoint_time' => $expect_time, //期望送达时间
            'note' => $orderInfo['remark'], //备注
            'is_right_now' => 0, //是否立即送达
            'order_time' => $orderInfo['create_time'], //订单下单时间
            'freight_charge' => $orderInfo['money_freight'], //配送费
            'distance' => '', //距离，单位：km
            'virtual_phone' => '', //虚拟手机号
            'virtual_phone_overtime' => '', //虚拟手机号过期时间
        ];
        Db::startTrans();
        try {
            if($merId){
                //判断是否开源骑手配送
                $distribution = $this->getDistributionInfo($orderInfo,$merId,$lng,$lat);
            }
            if((isset($distribution) && !$distribution) || !$merId){
                $res = (new DeliverSupplyService())->saveOrder($param);
            }
            if (!empty($periodic_order_id)) {
                if (empty($periodicInfo)) {
                    throw new \think\Exception('周期购期数信息缺失');
                }
                if ($periodicInfo['periodic_count'] == $periodicInfo['now_count']) {
                    $this->checkOrderStatus(20,$orderInfo['status']);
                    $note = '周期购最后一期已发货';
                    $this->changeOrderStatus($order_id, 20, $note);
                }
                $this->mallOrderlog($order_id, 20, '周期购第' . $periodicInfo['now_count'] . '期已发货');
                $this->mallActivityService->updatePeriodicOrder($periodic_order_id, 3);//邓远辉提供
                //入库周期购子表
//                $this->mallActivityService->addPeriodicDeliver($order_id, $periodic_order_id, 0, 0, $current_periodic, 0, 0, 0, 0, 0);//邓远辉提供
            } else {
                $this->checkOrderStatus(20,$orderInfo['status']);
                //入库发货时间
                $this->MallOderModel->updateThis(['order_id' => $param['order_id']], ['send_time' => time()]);
                $note = '店员已发货，发货方式：骑手配送';
                $this->changeOrderStatus($order_id, 20, $note);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            fdump_sql(['error'=>$e],'DeliverError2022');
            fdump($e,"errorMallOrderDeliverGoodsByHouseman111",1);
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 河山泉接口
     */
    public function getDistributionInfo($orderInfo,$merId,$lng,$lat)
    {
        if($orderInfo && $orderInfo['express_style'] == 1){
            //查询当前商户是否开源骑手配送
            $merAry = cfg('mall_distribution_mer') ? explode(',',cfg('mall_distribution_mer')) : [];
            if(in_array($merId,$merAry)){
                //查询购买的商品信息
                $fields = 'g.name,d.num';
                $goodsInfo = (new MallOrderDetail())->getGoodsJoinData([['d.order_id','=',$orderInfo['order_id']]],$fields)->toArray();
                //修改订单同步第三方状态为已同步
                $sync = $this->MallOderModel->updateThis(['order_id' => $orderInfo['order_id']], ['sync_status' => 1]);
                if(!$sync){
                    throw new Exception('修改订单同步状态失败');
                }
                $areaAry = [];
                if($orderInfo['province_id']){
                    $areaAry[] = $orderInfo['province_id'];
                }
                if($orderInfo['city_id']){
                    $areaAry[] = $orderInfo['city_id'];
                }
                if($orderInfo['area_id']){
                    $areaAry[] = $orderInfo['area_id'];
                }
                if($areaAry){
                    $areaAry = (new AreaService())->getNameByIds($areaAry);
                }
                //获取省市区
                $areaStrLeft = '';
                if($areaAry && $orderInfo['province_id']){
                    $areaStrLeft .= $areaAry[$orderInfo['province_id']];
                }
                if($areaAry && $orderInfo['city_id']){
                    $areaStrLeft .= $areaAry[$orderInfo['city_id']];
                }
                if($areaAry && $orderInfo['area_id']){
                    $areaStrLeft .= $areaAry[$orderInfo['area_id']];
                }
                //获取详细地址
                $areaStrRightInfo = explode($areaStrLeft,$orderInfo['address']);
                $areaStrRight = count($areaStrRightInfo) > 1 && $areaStrRightInfo[0]=='' ? md_trim($orderInfo['address'],$areaStrLeft,true,false) : $orderInfo['address'];
                //拦截骑手配送订单,请求第三方接口
                $api = 'http://hsqapi.rzghjt.com/wx/order/createOrder';
                $paramData = [
                    'user_tel'=>$orderInfo['phone'],
                    'order_no'=>$orderInfo['order_no'],
                    'pay_time'=>date('Y-m-d H:i:s',$orderInfo['pay_time']),
                    'online_pay_type'=>2,
                    'money_real'=>$orderInfo['money_real'],
                    'remark'=>$orderInfo['remark'],
                    'address_info'=>[
//                        'address'=>$orderInfo['address'],
                        'district'=>$areaStrLeft,
                        'detail'=>$areaStrRight,
                        'longitude'=>$lng,
                        'latitude'=>$lat,
                        'name'=>$orderInfo['username'],
                        'phone'=>$orderInfo['phone'],
                    ],
                    'product_info'=>$goodsInfo
                ];
                fdump($paramData,'hsq_3');
                $result = \net\Http::curlPostOwn($api, json_encode($paramData), 30,'json');
                fdump($result,'hsq_2');
                $result = json_decode($result, true);
                fdump($result,'hsq_1');
                if (isset($result['errno']) && $result['errno'] == 0) {
                    return true;
                } else {
                    throw new Exception($result['errmsg'] ?? '同步订单失败');
                }
            }
        }
        return false;
    }
    /**
     * 骑手配送详情
     * type 0=普通 1=周期购
     */
    public function getDeliverDetail($order_id, $type = 0)
    {
        if ($type == 1) {
            $order_id = (new MallNewPeriodicPurchaseOrder())->getNowPeriodicOrder(['id' => $order_id]) ? (new MallNewPeriodicPurchaseOrder())->getNowPeriodicOrder(['id' => $order_id])['order_id'] : 0;
        }
        if (empty($order_id)) {
            throw new \think\Exception('order_id参数缺失');
        }
        $orderInfo = $this->MallOderModel->getOne($order_id);
        $storeInfo = (new MerchantStore())->getOne(['store_id' => $orderInfo['store_id']]);
        $lng = '';
        $lat = '';
        if ($orderInfo['address_id'] > 0) {
            $addressInfo = (new \app\common\model\service\UserAdressService())->getOne(['adress_id' => $orderInfo['address_id']]);
        }
        if ($addressInfo) {
            $lng = $addressInfo['longitude'];
            $lat = $addressInfo['latitude'];
        } else {
            $jwdInfo = (new AreaService())->addressInfo($orderInfo['address']);
            if ($jwdInfo && $jwdInfo['status'] === 0) {
                $lng = $jwdInfo['result']['location']['lng'];
                $lat = $jwdInfo['result']['location']['lat'];
            }
        }
        
        $time = explode(',', $orderInfo['express_send_time']);

        $arr = [
            'user_address' => [
                "title" => $orderInfo['username'],
                "sub_title" => $orderInfo['address'],
                "lng" => $lng,
                "lat" => $lat,
                "tag" => L_("送货"),
                "miles" => number_format(($orderInfo['freight_distance'] / 1000), 2) . 'km'
            ],
            'pick_address' => [
                "title" => $storeInfo['name'],
                "sub_title" => $storeInfo['adress'],
                "lng" => $storeInfo['long'],
                "lat" => $storeInfo['lat'],
                "tag" => L_("取货"),
                "miles" => '0km'
            ],
            'phone_lists' => [
                [
                    "name" => $orderInfo['username'],
                    "type" => 1,
                    "txt" => L_("收货人"),
                    "phone" => $orderInfo['phone'],
                    "im_url" => ''
                ],
                [
                    "name" => L_("商家"),
                    "type" => 2,
                    "txt" => L_("发货人"),
                    "phone" => $storeInfo['phone'],
                    "im_url" => ''
                ]

            ],
            'labels' => [
                [
                    "txt" => L_("商城"),
                    "background" => "#A057F5",
                    "font_color" => "#FFFFFF",
                    "with_border" => false
                ]
            ],
        ];
        $arr['order_id'] = $orderInfo['order_id'];
        $arr['real_orderid'] = $orderInfo['order_no'];
        $arr['fetch_number'] = $orderInfo['fetch_number'] ?: 0;
        $arr['note'] = $orderInfo['remark'] ?: '';
        $arr['desc'] = '';
        $arr['expect_use_time'] = $time[0] ?? 0;
        $arr['order'] = $orderInfo;
        $arr['username'] = $orderInfo['username'];
        $arr['phone'] = $orderInfo['phone'];
        return $arr;
    }

    /**
     * @param $type   类型 order|periodic
     * @param $status 状态 1=接单 2=到店 3=取货 4=送达 5=失败
     * @param $order_id  订单id或周期购订单id 和type 保持一致  必传
     * @param string $lng 经度
     * @param string $lat 纬度
     * @param array $rider_info [id,//配送员id
     *                          name,//配送员姓名
     *                          phone//配送员电话
     *                          ]  接单时传回
     * @return bool
     * @throws Exception
     * 骑手日志
     */
    public function housemanOrderLog($type, $status, $id, $lng = '', $lat = '', $rider_info = [])
    {
        if (empty($id)) {
            throw new \think\Exception('缺少id参数');
        }
        if (!in_array($type, ['order', 'periodic'])) {
            throw new \think\Exception('type参数错误');
        }
        switch ($status) {
            case 1:
                $note = $rider_info['msg'] ?? '骑手已接单，正在赶往商家';
                break;
            case 2:
                $note = '骑手已到店，正在等待取货';
                break;
            case 3:
                $note = '骑手已取货，正在配送中';
                break;
            case 4:
                $note = '骑手已送达';
                break;
            case 5:
                $note = '配送失败';
                break;
        }
        Db::startTrans();
        try {
            if ($status == 1 && empty($rider_info)) {
                throw new \think\Exception('缺少快递员信息');
            }
            (new MallRiderService())->addRecord($type, $id, $status, $note, $lng, $lat, $rider_info);
            if ($status == 4 && $type == 'order') {
                $this->changeOrderStatus($id, 33, $note);
            } elseif ($status == 4 && $type == 'periodic') {
                $periodicInfo = (new MallNewPeriodicPurchaseOrderService())->retCountByOrderid($id);
                if (empty($periodicInfo)) {
                    throw new \think\Exception('周期购期数信息缺失');
                }
                if ($periodicInfo['periodic_count'] == $periodicInfo['now_count']) {
                    $note = '周期购最后一期完成';
                    $this->changeOrderStatus($periodicInfo['order_id'], 33, $note);
                }
                $this->mallActivityService->updatePeriodicOrder($id, 4);//邓远辉提供

                $wh = [['purchase_order_id', '=', $id]];
                $da['status'] = 1;
                $this->mallActivityService->updatePeriodicDeliver($wh, $da);

                $this->mallOrderlog($periodicInfo['order_id'], 33, '周期购第' . $periodicInfo['periodic_count'] . '期骑手已送达');
            }
            fdump_api($id,'checkMaLLOrderDeliver',1);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * @param $order_id
     * @return bool
     * 顺延发货
     */
    public function postponeDelivery($periodic_order_id, $order_id)
    {
        if (empty($order_id) || empty($periodic_order_id)) {
            throw new \think\Exception('缺少参数');
        }
        $res = $this->mallActivityService->periodicDelay($periodic_order_id, $order_id, 1);//邓远辉提供
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        }
        //记录日志
        $this->mallOrderlog($order_id, 12, $res['periodic_count'] . '顺延发货成功');
        //等待邓远辉接口完成
        return true;
    }

    /**
     * @param $order_id
     * 同意退款
     */
    public function AgreeRefund($order_id, $refund_id, $is_all, $source = 1)
    {
        if (empty($order_id)) {
            throw new \think\Exception('缺少order_id参数');
        }
        if (empty($refund_id)) {
            $refund_record = (new MallOrderRefund())->where('order_id', $order_id)->where('status', 0)->findOrEmpty()->toArray();
            if ($refund_record) {
                $refund_id = $refund_record['refund_id'];
                $is_all = $refund_record['is_all'];
            }
        }else{
            $refund_record = (new MallOrderRefund())->where('refund_id', $refund_id)->where('status', 0)->findOrEmpty()->toArray();
            if ($refund_record) {
                $is_all = $refund_record['is_all'];
            }
        }
        if($source==1&&!$refund_record){
            fdump_api([$order_id,$refund_record],'mall/refundError',true);
            throw new \think\Exception('订单已处理');
        }
        // 启动事务
        Db::startTrans();
        try {
            $msg = (new MallOrder())->getOne($order_id);
            
            $note = '店员同意退款申请';
            if ($is_all == 1) {
                //是全部退款就更新订单总表并记日志
                $this->changeOrderStatus($order_id, 70, $note);
            } else {
                //部分退款只更新日志
                if ($source == 1) {//店员同意
                    $note = '店员同意退款申请';
                } elseif ($source == 2) {//超时自动同意
                    $note = '已退款，店员超时未处理，系统自动同意退款';
                }
                //退款部分积分
                $refundInfo = (new MallOrderRefund())->where('refund_id', $refund_id)->field('refund_money')->find();
                if($refundInfo && $refundInfo['refund_money']){
                    (new SystemOrderService())->backScore('mall3',$order_id,$refundInfo['refund_money']);
                }
//                $this->mallOrderlog($order_id, 70, $note);
                //部分退款修改为退到上一步
                $last_status = 10;
                $log_list=(new MallOrderLog())->getSome(['order_id'=>$order_id],'status','status desc')->toArray();
                if(!empty($log_list)){
                    foreach ($log_list as $k=>$v){
                        if(((in_array($msg['status'],[60,61]) && !in_array($v['status'],[60,61,70])) || !in_array($msg['status'],[60,61]))){
                            $last_status=$v['status'];
                            break;
                        }
                    }
                }
                $this->mallOrderlog($order_id, $last_status, $note);

                $res = $this->MallOderModel->updateThis(['order_id'=>$order_id], ['status'=>$last_status]);
                fdump_api([
                    'msg' => '新版商城退款订单状态',
                    'order_id'    => $order_id,
                    'refund_id'   => $refund_id,
                    'last_status' => $last_status,
                ], 'mall_order/refuseRefund', true);
                if($res){
                    if($last_status==10){
                        (new SystemOrder())->updateThis(['order_id'=>$order_id,'type'=>'mall3'],['system_status'=>0]);
                    }elseif($last_status>10 && $last_status<30){
                        (new SystemOrder())->updateThis(['order_id'=>$order_id,'type'=>'mall3'],['system_status'=>1]);
                    }elseif ($last_status>=30 && $last_status<40){
                        (new SystemOrder())->updateThis(['order_id'=>$order_id,'type'=>'mall3'],['system_status'=>2]);
                    }
                }
            }
          

            if (!empty($msg) && $msg['goods_activity_type'] == 'periodic') {
                (new MallActivityService())->cancelUpdatePeriodic($order_id);
                $status = (new MallActivityService())->getPeriodicOrderStatus($order_id);
                $this->mallOrderlog($order_id, $status, '周期购同意退款，主订单状态修改');
            }
            if (!empty($msg) && $msg['goods_activity_type'] == 'group') {
                (new MallActivityService())->saveOrderUserUpdate($order_id);
            }
            (new UserNoticeService())->addNotice([
                'type'=>0,
                'business'=>'mall',
                'order_id'=>$order_id,
                'mer_id'=>$msg['mer_id'],
                'store_id'=>$msg['store_id'],
                'uid'=>$msg['uid'],
                'title'=>'商家已经同意退款',
                'content'=>'您申请的退款商家已经同意，请查看订单详情'
            ]);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            fdump_api(['order_id'=>$order_id,'error'=>$e->getMessage()],'mall/refundError',true);
            throw new \think\Exception($e->getMessage());
        }

        //在线退款挪到最后
        $this->mallOrderRefundService->auditRefund($order_id, $refund_id, 1,'',$is_all);
        //更新退款表
        $data = ['status' => 1, 'audit_time' => time()];
        $this->mallOrderRefundService->updateRefund($refund_id, $data);


        //退款成功推送消息
        (new SendTemplateMsgService())->sendWxappMessage(['type' => 'refund_success', 'order_id' => $order_id, 'status' => 70, 'refund_id' => $refund_id]);

        invoke_cms_model('System_order/sendAppPushMessage', ['orderId' => $order_id, 'type' => 'mall3']);
        return true;
    }

    /**
     * @param $order_id
     * 拒绝退款
     */
    public function RefuseRefund($order_id, $refund_id, $reason, $status)
    {
        if (empty($order_id)) {
            throw new \think\Exception('缺少order_id参数');
        }
        $msg = (new MallOrder())->getOne($order_id);
        if(!$msg){
            throw new \think\Exception('订单不存在！');
        }
        // 启动事务
        Db::startTrans();
        try {
            $last_status=10;

            //查询退款记录
            if($refund_id){
                $refundInfo = (new MallOrderRefund())->where('refund_id',$refund_id)->field('type')->find();
                if(!$refundInfo){
                    throw new \think\Exception('未查询到售后信息');
                }
                if($refundInfo['type'] == 2){//售后
                    $log_list=(new MallOrderLog())->getSome(['order_id'=>$order_id],'status','status desc')->toArray();
                    if(!empty($log_list)){
                        foreach ($log_list as $k=>$v){
                            if(((in_array($msg['status'],[60,61]) && !in_array($v['status'],[60,61,70])) || !in_array($msg['status'],[60,61]))){
                                $last_status=$v['status'];
                                break;
                            }
                        }
                    }
                }
            }
            $note = '店员拒绝退款，原因：' . $reason;
            $this->mallOrderlog($order_id, $last_status, $note);
//            $this->mallOrderlog($order_id, $status, $note);
            //更新退款表
            $data = ['status' => 2, 'error_reason' => $reason, 'audit_time' => time()];

            if(empty($refund_id)){
                $refund_record = (new MallOrderRefund())->where('order_id',$order_id)->where('status',0)->findOrEmpty()->toArray();
                $refund_record && $refund_id = $refund_record['refund_id'];
            }
            $this->mallOrderRefundService->updateRefund($refund_id, $data);

            $res = $this->MallOderModel->updateThis(['order_id'=>$order_id], ['status'=>$last_status]);
            if ($res) {
                $this->MallOderDatail->updateThis(['order_id'=>$order_id], ['status'=>$last_status]);
                //'0 待支付'', ''1 待支付'',''10 待发货'', ''11 备货中'', ''12 已顺延'',''13 待成团'',''20 已发货'', ''30 已收货'',''31 已自提'',
                // ''32 已核销'',''33 骑手已送达'',''40 已完成'',''50 已取消'',''51 超时取消'',''52 用户取消'',
                //''60 申请售后'',''70 已退款'',（具体看MallOrderService）
                //'系统状态，1-已发货，2-已完成，3-已评价，4-已退款，5-已取消,6-已完成（不用评价）,7-退款申请中'
                if($last_status==10){
                    (new SystemOrder())->updateThis(['order_id'=>$order_id,'type'=>'mall3'],['system_status'=>0]);
                }elseif($last_status>10 && $last_status<30){
                    (new SystemOrder())->updateThis(['order_id'=>$order_id,'type'=>'mall3'],['system_status'=>1]);
                }elseif ($last_status>=30 && $last_status<40){
                    (new SystemOrder())->updateThis(['order_id'=>$order_id,'type'=>'mall3'],['system_status'=>2]);
                }
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * @param $order_id
     * @throws Exception
     * 查看物流
     */
    public function viewLogistics($order_id, $periodic_order_id, $order_type, $express_style)
    {
        if (empty($order_id)) {
            throw new \think\Exception('缺少order_id参数');
        }
        if ($express_style == 2) {//快递配送
            //根据订单id查出总表储存的快递信息
            $arr = $this->MallOderModel->getOne($order_id);
            if (!empty($arr)) {
                return (new MallOrderService())->orderDeliveryList($order_id, $periodic_order_id);
                if ($order_type == 'periodic') {
                    //周期购从子表查出快递信息
                    if (empty($periodic_order_id)) {
                         //throw new \think\Exception('缺少periodic_order_id参数');
                    }
                    $sub_info = (new MallNewPeriodicDeliver())->getPeriodicDeliver(['purchase_order_id' => $periodic_order_id]);
                    $express_num = $sub_info['express_num'];
                    $express_code = $sub_info['express_num'];
                    $express_name = $sub_info['express_name'];
                } else {
                    $express_num = $arr['express_num'];
                    $express_code = $arr['express_code'];
                    $express_name = $arr['express_name'];
                }
                $singleFaceService = new SingleFaceService();
                $result = $singleFaceService->getSynQuery($express_num, $express_code, $arr['phone']);
                if ($result['code'] === 0) {
                    $list['express_name'] = $express_name;
                    $list['express_num'] = $express_num;
                    $list['last_state'] = $result['data']['data'] ? $result['data']['data'][0] : '';
                    $list['list'] = $result['data']['data'] ?? $result['data'];
                    $list['errCode'] = 0;
                    return $list;
                } else {
                    return ['errCode' => 1, 'errMsg' => $result['msg']];
                }
            } else {
                throw new \think\Exception('没查到该笔订单的快递信息，请检查快递单号和快递公司编码是否正确');
            }
        } elseif ($express_style == 1) {
            if ($order_type == 'periodic') {
                $list['expect_time'] = (new MallNewPeriodicPurchaseOrderService())->retCountByOrderid($periodic_order_id) ? date('Y-m-d H:i:s', (new MallNewPeriodicPurchaseOrderService())->retCountByOrderid($periodic_order_id)['periodic_date']) : '';
                $arr = (new MallRiderService())->getRecord('periodic', $periodic_order_id, 'addtime ASC');
            } else {
                if (!empty($this->MallOderModel->getOne($order_id))) {
                    $time = $this->MallOderModel->getOne($order_id)['express_send_time'];
                    if ($time) {
                        $list['expect_time'] = date('Y-m-d H:i:s', explode(',', $time)[0]);
                    } else {
                        $list['expect_time'] = '';
                    }
                }
                $arr = (new MallRiderService())->getRecord('order', $order_id);
            }
            if (!empty($arr)) {
                foreach ($arr as $key => $val) {
                    $arr[$key]['context'] = $val['note'];
                    $arr[$key]['time'] = date('Y.m.d H:i:s', $val['addtime']);//杨宁宁说骑手配送时间格式用点分割
                }
                $list['list'] = $arr;
                $list['last_state'] = $arr[0]['note'];
                $list['rider_name'] = $arr[0]['rider_name'];
                $list['rider_phone'] = $arr[0]['rider_phone'];
                return $list;
            } else {
                return [];
            }
        } else {
            throw new \think\Exception('配送方式错误');
        }
    }

    /**
     * 获取订单详情
     * @param  [type] $order_id [description]
     * @return [type]           [description]
     */
    public function getOrderDetail($order_id)
    {
        if (empty($order_id)) return [];
        $now_order = $this->getOne($order_id);
        $now_order_detail = $this->MallOderDatail->getByOrderId(true, $order_id);
        return ['order' => $now_order, 'detail' => $now_order_detail];
    }

    /**
     * @param $order_id
     * @param $notes
     * @throws Exception
     * 店员备注
     */
    public function clerkNotes($order_id, $notes, $status, $staff)
    {
        if (empty($order_id)) {
            throw new \think\Exception('缺少order_id参数');
        }
        $data = ['clerk_remark' => $notes];
        $where = ['order_id' => $order_id];
        $res = $this->MallOderModel->updateThis($where, $data);
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        }
        //记日志
        $this->mallOrderlog($order_id, $status, '店员' . $staff . '修改备注为' . $notes);
        return true;
    }

    /**
     *获取各种状态数(暂时注释)
     */
    public function getNumber($arr_order,$unsetPageParam)
    {
        $status_num[0]['num'] = count($arr_order);
        $status_num[0]['status'] = 1;
        $status_num[1]['num'] = 0;
        $status_num[1]['status'] = 2;
        $status_num[2]['num'] = 0;
        $status_num[2]['status'] = 3;
        $status_num[3]['num'] =0;
        $status_num[3]['status'] =4;
        $status_num[4]['num'] =0;
        $status_num[4]['status'] =5;
        $status_num[5]['num'] = 0;
        $status_num[5]['status'] = 6;
        $status_num[6]['num'] = 0;
        $status_num[6]['status'] = 7;
        $status_num[7]['num'] = 0;
        $status_num[7]['status'] = 8;

        $refund_info = $this->mallOrderRefundService->getAllRefund([['r.status', '=', 1], ['r.is_all', '=', 1]], 'o.*,r.*,mr.name as mer_name,s.name as store_name', '', '');
        $refundOrderIds = array_column($refund_info, 'order_id');
        $refundOrderIds = array_unique($refundOrderIds);


        foreach ($arr_order as $k => $item) {
            if(!empty($refund_info)){
                if (in_array($item['order_id'], $refundOrderIds) && !isset($val['refund_id'])) {
                    unset($arr_order[$k]);
                }
            }
        }

        $arr_order=array_values($arr_order);

        $arrs = array();
        if (!empty($arr_order)) {
            foreach ($arr_order as $k => $item) {
                // 周期购的处理 期数
                if ($item['goods_activity_type'] == 'periodic') {
                    $arrs[] = $arr_order[$k];
                    //根据订单id获取所有的子订单信息
                    $periodic = $this->mallActivityService->returnNowPeriodicOrderAndActBefore($item['order_id'], 1);//邓远辉提供的接口
                    if (!empty($periodic)) {
                        $periodic_val = [];
                        foreach ($periodic as $kk => $vv) {
                            $tmp['periodic_count'] = $vv['periodic_count'];
                            $tmp['current_periodic'] = $vv['current_periodic'];
                            $tmp['express_current_time'] = $vv['periodic_date'] ? date('Y-m-d H:i:s', $vv['periodic_date']) : '';
                            $tmp['status'] = $vv['is_complete'];
                            $tmp['periodic_order_id'] = $vv['purchase_order_id'];
                            if ($vv['periodic_count'] === 0) {
                                throw new \think\Exception('周期购的期数不能为0');
                            }
                            $periodic_val[] = array_merge($item, $tmp);
                        }
                        $arr_order = array_merge($arr_order, $periodic_val);
                    }
                }
            }
            foreach ($arrs as $k1 => $v1) {
                foreach ($arr_order as $k2 => $v2) {
                    if ($v1['order_id'] == $v2['order_id'] && !isset($v2['periodic_order_id'])) {
                        unset($arr_order[$k2]);
                    }
                }
            }
            $arr_order = array_values($arr_order);
        }

        $unsetPageParam['status']=7;
        $status_num[6]['num']=$this->refunOrdersNum(7,$unsetPageParam);
        $unsetPageParam['status']=8;
        $status_num[7]['num']=$this->refunOrdersNum(8,$unsetPageParam);

        $unsetPageParam['status']=1;
        $arr_tk = $this->getTkInfo(1, $unsetPageParam);
        $status_num[0]['num']=$status_num[0]['num']+count($arr_tk);
        foreach ($arr_order as $k=>$v) {
            if ($v['status'] < 10 && $v['goods_activity_type']!="periodic") {//待付款
                $status_num[1]['num']++;
            } elseif ($v['status'] >= 10 && $v['status'] < 20) {//待发货
                $status_num[2]['num']++;
            } elseif ($v['status'] >= 20 && $v['status'] < 30) {//已发货
                $status_num[3]['num']++;
            } elseif ($v['status'] >= 30 && $v['status'] < 50) {//已完成
                $status_num[4]['num']++;
            } elseif ($v['status'] >= 50 && $v['status'] < 60) {//已取消
                $status_num[5]['num']++;
            }
        }
        return $status_num;
    }

    /**
     * @param $status
     * @param $unsetPageParam
     * @return int
     * 计算退款数量
     */
    public function refunOrdersNum($status,$unsetPageParam){
        $arr = $this->getTkInfo($status, $unsetPageParam);
        $refund_info = $this->mallOrderRefundService->getAllRefund([['r.status', '=', 1], ['r.is_all', '=', 1]], 'o.*,r.*,mr.name as mer_name,s.name as store_name', '', '');
        if (!empty($refund_info)) {
            $refundOrderIds = array_column($refund_info, 'order_id');
            $refundOrderIds = array_unique($refundOrderIds);

            foreach ($arr as $key => $val) {
                if (in_array($val['order_id'], $refundOrderIds) && !isset($val['refund_id'])) {
                    unset($arr[$key]);
                }
            }
        }
        $countArr = array_values($arr);
        return count($countArr);
    }
    /**
     * @param $param
     * @return int
     * @throws Exception
     * 计算各种数量
     */
    public function getStatusCount($param)
    {
        return 0;
        $style = 0; //列表调用
        $now_status = $param['status'];
        $where_all = $this->getWhere($param, $style);
        if ($param['re_type'] == 'platform') {
            $field = 'o.order_id,o.order_no,o.mer_id,o.store_id,o.address,o.create_time,o.money_online_pay,o.online_pay_type,o.express_send_time,o.money_freight,o.username,o.phone,o.express_style,o.send_time,o.status,o.clerk_remark,o.remark,o.money_total,o.money_real,o.discount_system_coupon,o.discount_merchant_coupon,o.discount_merchant_card,o.discount_system_level,o.discount_clerk_money,o.discount_act_money,o.money_score,o.money_system_balance,o.money_merchant_balance,o.money_merchant_give_balance,o.money_qiye_balance,o.goods_activity_type,o.activity_type,o.pay_orderno,o.pre_pay_orderno,o.activity_id,s.name as store_name,m.name as mer_name';
        } elseif ($param['re_type'] == 'merchant') {
            $field = 'o.order_id,o.order_no,o.mer_id,o.store_id,o.create_time,o.money_online_pay,o.online_pay_type,o.address,o.express_send_time,o.money_freight,o.username,o.phone,o.express_style,o.send_time,o.status,o.clerk_remark,o.remark,o.money_total,o.money_real,o.discount_system_coupon,o.discount_merchant_coupon,o.discount_merchant_card,o.discount_system_level,o.discount_clerk_money,o.discount_act_money,o.money_score,o.money_system_balance,o.money_merchant_balance,o.money_merchant_give_balance,o.money_qiye_balance,o.goods_activity_type,o.activity_type,o.activity_id,o.pay_orderno,o.pre_pay_orderno,s.name as store_name,m.name as mer_name';
        } elseif ($param['re_type'] == 'storestaff') {
            $field = 'o.order_id,o.order_no,o.mer_id,o.store_id,o.create_time,o.money_online_pay,o.online_pay_type,o.address,o.express_send_time,o.money_freight,o.username,o.phone,o.express_style,o.send_time,o.status,o.clerk_remark,o.remark,o.money_total,o.money_real,o.discount_system_coupon,o.discount_merchant_coupon,o.discount_merchant_card,o.discount_system_level,o.discount_clerk_money,o.discount_act_money,o.money_score,o.money_system_balance,o.money_merchant_balance,o.money_merchant_give_balance,o.money_qiye_balance,o.goods_activity_type,o.activity_type,o.activity_id,o.pay_orderno,o.pre_pay_orderno,s.name as store_name,m.name as mer_name';
        } else {
            throw new \think\Exception('re_type参数错误');
        }

        $style = 4; //选择周期购时不过滤状态和周期购条件
        $where_periodic_all = $this->getWhere($param, $style);
        array_push($where_periodic_all, ['o.goods_activity_type', '=', 'periodic']);
        $arr = $this->MallOderModel->getSearch($field, $where_all, $where_periodic_all);
        //获取各种和
        $arrs = array();
        if (!empty($arr)) {
            foreach ($arr as $k => $item) {
                // 周期购的处理 期数
                if ($item['goods_activity_type'] == 'periodic' && $now_status != 6) {
                    $arrs[] = $arr[$k];
                    //根据订单id获取所有的子订单信息
                    $periodic = $this->mallActivityService->returnNowPeriodicOrderAndActBefore($item['order_id'], $now_status);//邓远辉提供的接口
                    if (!empty($periodic)) {
                        $periodic_val = [];
                        foreach ($periodic as $kk => $vv) {
                            $tmp['periodic_count'] = $vv['periodic_count'];
                            $tmp['current_periodic'] = $vv['current_periodic'];
                            $tmp['express_current_time'] = $vv['periodic_date'] ? date('Y-m-d H:i:s', $vv['periodic_date']) : '';
                            $tmp['status'] = $vv['is_complete'];
                            $tmp['periodic_order_id'] = $vv['purchase_order_id'];
                            if ($vv['periodic_count'] === 0) {
                                throw new \think\Exception('周期购的期数不能为0');
                            }
                            $periodic_val[] = array_merge($item, $tmp);
                        }
                        $arr = array_merge($arr, $periodic_val);
                    }
                }
            }
            foreach ($arrs as $k1 => $v1) {
                foreach ($arr as $k2 => $v2) {
                    if ($v1['order_id'] == $v2['order_id'] && !isset($v2['periodic_order_id'])) {
                        unset($arr[$k2]);
                    }
                    if ($now_status == 2) {
                        if (!($v2['status'] >= 0 && $v2['status'] < 10)) {
                            unset($arr[$k2]);
                        }
                    } elseif ($now_status == 3) {
                        if (!($v2['status'] >= 10 && $v2['status'] < 20)) {
                            unset($arr[$k2]);
                        }
                    } elseif ($now_status == 4) {
                        if (!($v2['status'] >= 20 && $v2['status'] < 30)) {
                            unset($arr[$k2]);
                        }
                    } elseif ($now_status == 5) {
                        if (!($v2['status'] >= 30 && $v2['status'] < 50)) {
                            unset($arr[$k2]);
                        }
                    } elseif ($now_status == 6) {
                        if (!($v2['status'] >= 50 && $v2['status'] < 60)) {
                            unset($arr[$k2]);
                        }
                    } elseif ($now_status == 7) {
                        if (!($v2['status'] >= 60 && $v2['status'] < 70)) {
                            unset($arr[$k2]);
                        }
                    }
                }
            }
            $arr = array_values($arr);
        }
        $unsetPageParam = $param;
        if (isset($unsetPageParam['page'])) {
            $unsetPageParam['page'] = 0;
        }
        if (isset($unsetPageParam['pageSize'])) {
            $unsetPageParam['pageSize'] = 0;
        }
        if ($now_status == 7 || $now_status == 8) {
            $arr = $this->getTkInfo($now_status, $unsetPageParam);
        } elseif ($now_status == 1) {
            $arr_tk = $this->getTkInfo($now_status, $unsetPageParam);
            $arr = array_merge($arr, $arr_tk);
        }
        $refund_info = $this->mallOrderRefundService->getAllRefund([['r.status', '=', 1], ['r.is_all', '=', 1]], 'o.*,r.*,mr.name as mer_name,s.name as store_name', '', '');
        if (!empty($refund_info)) {
            $refundOrderIds = array_column($refund_info, 'order_id');
            $refundOrderIds = array_unique($refundOrderIds);

            foreach ($arr as $key => $val) {
                if (in_array($val['order_id'], $refundOrderIds) && !isset($val['refund_id'])) {
                    unset($arr[$key]);
                }
            }
        }
        $countArr = array_values($arr);
        return count($countArr);
    }
    /**
     *获取各种状态数
     */
    public function getNumberCopy($param)
    {
        //普通订单
        $status_arr = [1, 2, 3, 4, 5, 6];
        foreach ($status_arr as $k => $v) {
            $param['status'] = $v;
            unset($param['first_order_id']);
            $num_where = $this->getWhereCopy($param, 1);
            if(isset($param['provinceId']) && $param['provinceId']){
                $num_where[] = ['s.province_id','=',$param['provinceId']];
            }
            if(isset($param['cityId']) && $param['cityId']){
                $num_where[] = ['s.city_id','=',$param['cityId']];
            }
            if(isset($param['areaId']) && $param['areaId']){
                $num_where[] = ['s.area_id','=',$param['areaId']];
            }
            if(isset($param['streetId']) && $param['streetId']){
                $num_where[] = ['s.street_id','=',$param['streetId']];
            }
            $status_num[$k]['num'] = $this->MallOderModel->getCount($num_where);
            $status_num[$k]['status'] = $v;
        }
        //退款订单
        $param['page'] = '';
        $param['pageSize'] = '';
        $param['status'] = 7;
        $shz_num = count($this->getTkInfo(7, $param));
        $param['status'] = 8;
        $ytk_num = count($this->getTkInfo(8, $param));
        $status_num[] = ['num' => $shz_num, 'status' => 7];
        $status_num[] = ['num' => $ytk_num, 'status' => 8];
//        $status_num[0]['num'] -= count($this->getTkInfo(1, $param, 1));
        return $status_num;
    }

    /**
     * @param $order_id
     * 店员修改价格
     */
    public function clerkDiscount($order_id, $before_money, $after_money, $staff)
    {
        if (empty($order_id)) {
            throw new \think\Exception('缺少order_id参数');
        }
        if ($before_money === '') {
            throw new \think\Exception('缺少before_money参数');
        }
        if ($after_money === '') {
            throw new \think\Exception('缺少after_money参数');
        }
        $money = $before_money - $after_money;
        $clerk_discount_money = $this->MallOderModel->getOne($order_id);
        if (!empty($clerk_discount_money)) {
            $res = $this->changeOrderPrice($order_id, $after_money);
            if ($res) {
                $clerk_money = $money . ',' . $clerk_discount_money['discount_clerk_money'];
                $res = $this->MallOderModel->updateThis(['order_id' => $order_id], ['discount_clerk_money' => $clerk_money, 'money_real' => $after_money]);
                if ($res === false) {
                    throw new \think\Exception('操作失败，请重试');
                }
                //纪录日志
                $this->mallOrderlog($order_id, 0, '店员' . $staff . '修改价格为' . $after_money);
                return true;
            }

        }
    }

    /**
     * @param $order_id
     * 各种提醒
     */
    public function mallNotice($store_id, $intval)
    {
        if (empty($store_id)) {
            throw new \think\Exception('缺少store_id参数');
        }
        $list = [];
        //待发货
        $dfh_where = $where = [['store_id', '=', $store_id], ['last_uptime', '<', time()], ['last_uptime', '>', time() - 5], ['status', '=', '10'], ['status_up', '=', '0']];
        $count = $this->MallOrderRemind->getSomeCount($dfh_where) ?: 0;
        if ($count != 0) {
            //各种提醒
            $list['dfh']['num'] = $count;
            $list['dfh']['has_new'] = 1;
            $list['dfh']['mp3'] = cfg('mall_send_goods_mp3') ? replace_file_domain(cfg('mall_send_goods_mp3')) : '';
        } else {
            $list['dfh']['num'] = 0;
            $list['dfh']['has_new'] = 0;
            $list['dfh']['mp3'] = '';
        }
        //售后中
        $shz_where = $where = [['store_id', '=', $store_id], ['last_uptime', '<', time()], ['last_uptime', '>', time() - $intval], ['status', '=', '30'], ['status_up', '=', '0']];
        $count = $this->MallOrderRemind->getSomeCount($shz_where);
        if ($count != 0) {
            //各种提醒
            $list['shz']['num'] = $count;
            $list['shz']['mp3'] = cfg('mall_refund_mp3') ? replace_file_domain(cfg('mall_refund_mp3')) : '';
            $list['shz']['has_new'] = 1;
        } else {
            $list['shz']['num'] = 0;
            $list['shz']['has_new'] = 0;
            $list['shz']['mp3'] = '';
        }
        //催单
        $cd_where = $where = [['store_id', '=', $store_id], ['last_uptime', '<', time()], ['last_uptime', '>', time() - $intval], ['status', '=', '20'], ['status_up', '=', '0']];
        $count = $this->MallOrderRemind->getSomeCount($cd_where);
        if ($count != 0) {
            //各种提醒
            $list['shz']['num'] = $count;
            $list['shz']['mp3'] = cfg('mall_remind_mp3') ? replace_file_domain(cfg('mall_remind_mp3')) : '';
            $list['shz']['has_new'] = 1;
        } else {
            $list['shz']['num'] = 0;
            $list['shz']['has_new'] = 0;
            $list['shz']['mp3'] = '';
        }
        if (!empty($list)) {
            return $list;
        } else {
            return [];
        }
    }

    public function getExpress($store_id)
    {
        $expressService = new ExpressService();
        $all = $expressService->getExpress();
        $faceInfo = (new MerchantStoreMallService())->getStoremallInfo($store_id, 'single_face_info');
        $names = array();
        if (!empty($faceInfo['single_face_info'])) {
            $faceInfo = json_decode($faceInfo['single_face_info'], true);
            $names = array_column($faceInfo, 'name');
        }
        if (!empty($all)) {
            foreach ($all as $key => $val) {
                $all[$key]['is_singface'] = 0;
                if (in_array($val['name'], $names)) {
                    $all[$key]['is_singface'] = 1;
                }
            }
        } else {
            throw new \think\Exception('没有快递公司信息');
        }
        return $all;
    }

    //取消整单
    public function cancelOrder($order_id,$is_refund=true)
    {
        if ((new MallOrderRefundService)->refund($order_id, '', true,$is_refund)) {
            # 这里可能还需要增加其他逻辑
            $order = $this->getOrderDetail($order_id);
            if($is_refund){
                $update = [
                    'money_refund' => $order['order']['money_real']
                ];

                $this->MallOderModel->updateThis(['order_id' => $order_id], $update);

                $merchant = Merchant::where(['mer_id' => $order['order']['mer_id']])->find();
                if($merchant['miaomaiyun_switch']){
                    fdump_api(['order_id' => $order_id,'msg' => '商城取消订单'], 'miaomai/mall_cancel_order', 1);
                    $miaomaiyun = new Miaomaiyun();
                    $result = $miaomaiyun
                        ->setConfig($merchant['miaomaiyun_erp_appid'], $merchant['miaomaiyun_erp_appsecret'], $merchant['miaomaiyun_url'])
                        ->setParams([
                            'order_code' => $order['order']['miaomai_order_code']
                        ])
                        ->run(Miaomaiyun::CANCEL_ORDER);
                    fdump_api(['order_id' => $order_id,'result' => $result], 'miaomai/mall_cancel_order', 1);
                }         
                // 支退款成功后打印小票
                if (isset($order['order']['pay_time']) && $order['order']['pay_time'] > 0) {
                    $param = [
                        'order_id' => $order_id,
                        'print_type' => 'refund'
                    ];
                    (new PrintHaddleService)->printOrder($param, 3);
                }
            }
            return true;
        }
        return false;
    }

    /**
     * 生成以订单编号为内容的二维码，供自提扫码
     * @param  [type] $order_id 订单ID
     * @param  [type] $order_no 订单编号
     * @return [type]           [description]
     */
    public function createQrcode($order_id, $order_no)
    {
        if (empty($order_id) || empty($order_no)) return '';
        $dir = '/runtime/qrcode/mall/' . substr($order_no, 0, 8);
        $path = '../..' . $dir;
        $filename = $order_no . '.png';
        if (file_exists($path . '/' . $filename)) {
            return $dir . '/' . $filename;
        }
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $qrCon = $order_no;
        $qrcode = new \QRcode();
        $qrcode->png($qrCon, $path . '/' . $filename, 'L', '9');
        return $dir . '/' . $filename;
    }

    /**商城订单完成增加商家余额
     * @param $orderId
     * @param int $periodic_count 周期购订单确认收货的期数，说明：周期购订单运费按期结算
     * @param int $prepare_type 预售订单，1：未付尾款 2:已付尾款
     * @throws Exception
     */
    public function merchantAddMoney($orderId, $periodic_count = 0, $prepare_type = 2)
    {
        try {
            $nowOrder = $this->getOne($orderId);

            if (!$nowOrder) {
                throw new \Exception('订单不存在');
            }

            if ($nowOrder['status'] >= 40) {
                throw new \Exception('订单已完成');
            }


            //订单详情信息
            $order_detail = $this->getOrderDetails($orderId);
            $goods = isset($order_detail['children']) ? $order_detail['children'] : [];
            $num = 0;
            foreach ($goods as $value) {
                $num += $value['num'];
            }

            // 店铺service
            $merchantStoreService = new MerchantStoreService();
            $nowStore = $merchantStoreService->getOne($nowOrder['store_id']);

            if ($nowOrder['goods_activity_type'] == 'prepare') {//预售订单
                $prepare_order_info = (new MallPrepareOrder())->getOne($where = ['order_id' => $orderId]);
                if ($prepare_type == 2) {
                    $money_real = $prepare_order_info['bargain_price'] + $prepare_order_info['rest_price'];
                } else {
                    $money_real = $prepare_order_info['bargain_price'];
                }
            } else {
                $money_real = $nowOrder['money_real'];
            }
            $discount_system_coupon_platform = $nowOrder['discount_system_coupon'] > 0 ? getFormatNumber(bcsub($nowOrder['discount_system_coupon'],$nowOrder['discount_system_coupon_merchant'],3)) : 0;
	        $internal_card_pay = 0;
            if ($nowOrder['internal_card_pay'] && ($internal_card_pay_arr = @json_decode($nowOrder['internal_card_pay'], true))) {
                $internal_card_pay = array_sum($internal_card_pay_arr);
            }
            //当前商家应该入账的金额 = 实际金额（除去优惠的金额） - 积分的钱 - 商家会员卡赠送余额付了多少钱 - 平台余额付了多少钱 - 商家会员卡余额付了多少钱 - 运费 - 已成功退款金额
            //new 当前商家应该入账的金额 = 实际金额（除去优惠的金额） - 积分的钱 - 商家会员卡赠送余额付了多少钱 - 商家会员卡余额付了多少钱 - 运费 - 已成功退款金额 + 平台优惠券金额（平台补贴金额,不参与抽成）
            $bill_money = $money_real - $nowOrder['money_score'] - $nowOrder['money_merchant_give_balance'] - $nowOrder['money_merchant_balance'] - $nowOrder['money_freight'] - $nowOrder['money_refund'];
            $bill_money = $bill_money - $internal_card_pay; 
            
            $MerchantStoreMallService = new MerchantStoreMallService();
            $nowStoreMall = $MerchantStoreMallService->getStoremallInfo($nowOrder['store_id'], 'horseman_type');
            if($nowOrder['express_style'] == '1' && $nowStoreMall['horseman_type'] == 1){
                $bill_money = $bill_money +  $nowOrder['money_freight'];
            }

            $money_system_take = $bill_money + $nowOrder['money_merchant_give_balance'] + $nowOrder['money_merchant_balance'] + $nowOrder['discount_merchant_card'];//平台抽成基数
            $bill_money += $discount_system_coupon_platform;

            if ($nowOrder['goods_activity_type'] == 'periodic') {//周期购订单
                $mallNewPeriodicPurchaseOrderService = new MallNewPeriodicPurchaseOrderService();
                $where = [];
                $where['order_id'] = $orderId;
                $where['periodic_count'] = $periodic_count;//周期购订单当前期数
                $periodicOrder = $mallNewPeriodicPurchaseOrderService->getNowPeriodicOrder($where);
                if (empty($periodic_count)) {
                    throw new \Exception('周期购订单期数参数不能为空');
                }
                if (empty($periodicOrder)) {
                    throw new \Exception('周期购订单不存在');
                }

                if ($periodicOrder['is_complete'] == 4) {
                    throw new \Exception('周期购订单当前期数已完成');
                }

                $periodic_info = (new MallNewPeriodicPurchase())->getInfo($where = ['id' => $periodicOrder['act_id']], $fields = 's.periodic_count');
                $periodic_count = $periodic_info['periodic_count'];//周期购订单总期数
                $bill_money = get_format_number($bill_money / $periodic_count);

            }
            $orderInfo = [];
            $orderInfo['pay_order_id'] = [$nowOrder['pay_orderno'], $nowOrder['pre_pay_orderno']];//支付单号
            $orderInfo['total_money'] = $nowOrder['money_total'];//当前订单总金额
            $orderInfo['bill_money'] = $bill_money;
            $orderInfo['balance_pay'] = $nowOrder['money_system_balance'];//平台余额支付金额
            $orderInfo['merchant_balance'] = $nowOrder['money_merchant_balance'];//商家会员卡支付金额
            $orderInfo['card_give_money'] = $nowOrder['money_merchant_give_balance'];//商家会员卡赠送支付金额
            $orderInfo['payment_money'] = $nowOrder['money_online_pay'];//在线支付金额（不包含自有支付）
            $orderInfo['score_deducte'] = $nowOrder['money_score'];//积分支付金额
            $orderInfo['order_from'] = 1;
            $orderInfo['order_type'] = 'mall';
            $orderInfo['num'] = $num;//数量
            $orderInfo['store_id'] = $nowOrder['store_id'];
            $orderInfo['mer_id'] = $nowOrder['mer_id'];
            $orderInfo['order_id'] = $nowOrder['order_id'];
            $orderInfo['group_id'] = '0';
            $orderInfo['real_orderid'] = $nowOrder['order_no'];//订单编号
            $orderInfo['union_mer_id'] = '0';//商家联盟id
            $orderInfo['uid'] = $nowOrder['uid'];//用户ID
            $orderInfo['desc'] = '用户在 ' . $nowStore['name'] . ' 中消费' . $nowOrder['money_total'] . '元记入收入';
            $orderInfo['is_own'] = '0';//自有支付类型
            $orderInfo['own_pay_money'] = '0';//自有支付在线支付金额
            
            if ($nowOrder['express_style'] == 1) {
                //骑手配送区分平台还是商家，如果是商家配送，运费结算给商家
                $orderInfo['pay_for_store'] = '0';
                $supply = (new \app\deliver\model\db\DeliverSupply())->where('order_id', '=', $orderId)->where('item', 'in', '4,5')->find();
                if ($supply && $supply->type == 0) {
                    if ($nowOrder['goods_activity_type'] == 'periodic') {
                        $orderInfo['pay_for_system'] = $nowOrder['money_freight'] / $periodic_count;
                    } else {
                        $orderInfo['pay_for_system'] = $nowOrder['money_freight'];
                    }
                }
            } elseif ($nowOrder['express_style'] == 2) {
                if ($nowOrder['goods_activity_type'] == 'periodic') {
                    $orderInfo['pay_for_store'] = $nowOrder['money_freight'] / $periodic_count;
                } else {
                    $orderInfo['pay_for_store'] = $nowOrder['money_freight'];
                }
            } elseif ($nowOrder['express_style'] == 3) {
                $orderInfo['pay_for_store'] = '0';
            }
            $orderInfo['score_used_count'] = '';
            $orderInfo['score_discount_type'] = '';
            $orderInfo['money_system_take'] = $money_system_take;
            $orderInfo['discount_detail'] = '';
            $orderInfo['system_coupon_plat_money'] = '';
            $orderInfo['system_coupon_merchant_money'] = '';
            $orderInfo['is_liveshow'] = $nowOrder['is_liveshow'] ?? 0;
            $orderInfo['liveshow_id'] = $nowOrder['liveshow_id'] ?? 0;
            $orderInfo['is_livevideo'] = $nowOrder['is_livevideo'] ?? 0;
            $orderInfo['livevideo_id'] = $nowOrder['livevideo_id'] ?? 0;
            $orderInfo['live_merchant_money'] = $nowOrder['live_merchant_money'] ?? 0;
            $orderInfo['live_author_money'] = $nowOrder['live_author_money'] ?? 0;

            //自有支付需要重置is_own\own_pay_money\bill_money
            $payOrderids = array_filter($orderInfo['pay_order_id']);
            $payOrders = (new PayService)->getPayOrders($payOrderids, [['business', '=', $orderInfo['order_type']], ['paid_money', '>', 0]]);
            if ($payOrders) {
                $orderInfo['is_own'] = $payOrders[0]['is_own'] ?? 0;
                if ($orderInfo['is_own']) {
                    $orderInfo['own_pay_money'] = $orderInfo['bill_money'];
                    $orderInfo['bill_money'] = 0;
                }
            }

            $merchantMoneyListService = new MerchantMoneyListService();
            $merchantMoneyListService->addMoney($orderInfo);
            $result = ['status' => 0];

        } catch (\Exception $e) {
            $result = ['status' => 1003, 'error_msg' => $e->getMessage()];
        }
        return $result;
    }

    /**
     * 获取用户已经参与过的店铺级活动次数
     * @param  [type] $activity_type [description]
     * @param  [type] $activity_id   [description]
     * @param  [type] $uid           [description]
     * @return [type]                [description]
     */
    public function getStoreActivityJoinNums($activity_type, $activity_id, $uid)
    {
        if (empty($activity_id) || empty($activity_type) || empty($uid)) {
            return 0;
        }
        $where = [
            ['uid', '=', $uid],
            ['activity_type', '=', $activity_type],
            ['activity_id', '=', $activity_id],
        ];
        $join_nums = (new MallOrder)->getCount($where);
        return $join_nums;
    }

    /**
     * 自动取消订单
     * @param  [type] $order_id [description]
     * @param  [type] $status   [description]
     * @param  [type] $note     [description]
     * @return [type]           [description]
     */
    public function autoCancelOrder($order_id, $status, $note = '')
    {
        if (empty($order_id) || empty($status)) return false;
        $this->changeOrderStatus($order_id, $status, $note);
        $order_detail = $this->getOrderDetail($order_id);
        if ($order_detail['order']['goods_activity_type'] == 'bargain') {
            $this->mallActivityService->fifteenMiniuteRefundBargainOrder($order_id);
        }
        return true;
    }


    /**
     * 导出订单
     * @param $param array 数据
     * $param = [
     *         'type',//导出业务唯一标识
     * ]
     * @return array
     */
    public function orderExport($param)
    {
        $store_id = isset($param['store_id']) ? $param['store_id'] : 0;//店铺id
        $status = isset($param['status']) ? $param['status'] : [];//订单状态
        $where = array();
        if ($store_id != 0) {//店铺id
            $where[] = ['store_id', '=', $store_id];
        }

        if (!empty($status)) {//状态status
            $where[] = ['status', 'in', $status];
        }

        $orderList = $this->getOrders($where);
        $csvHead = array(
            L_('订单编号'),
            L_('*物流公司'),
            L_('*物流单号')
        );

        $csvData = [];
        if (!empty($orderList)) {
            foreach ($orderList as $orderKey => $value) {
                $csvData[$orderKey] = [
                    $value['order_no'] . ' ',
                    $value['express_name'],
                    $value['express_num'],
                ];
            }
        }
        $filename = date("Y-m-d", time()) . '-' . "导出模板订单" . '.csv';
        (new BaseExportService())->putCsv($filename, $csvData, $csvHead);
    }


    /**
     * 导出Excel订单(Spreadsheet方法)
     * @param $param
     * 不是筑梦群做的（批量发货）
     */
    public function orderExportExcel($param)
    {
        $store_id = isset($param['store_id']) ? $param['store_id'] : 0;//店铺id
        $status = isset($param['status']) ? $param['status'] : [];//订单状态
        $where = array();
        if ($store_id != 0) {//店铺id
            $where[] = ['store_id', '=', $store_id];
        }
        if (!empty($status)) {//状态status
            $where[] = ['status', '>=', 10];
            $where[] = ['status', '<', 20];
            $where[] = ['goods_activity_type', '<>', 'periodic'];
            $where[] = ['express_style', '=', 2];
        }
        $orderList = $this->getOrders($where);
        if (!empty($orderList)) {
            foreach ($orderList as $key => $val) {
                $wh = [['is_all', '=', 1], ['order_id', '=', $val['order_id']]];
                $arr = (new MallOrderRefundService())->getAllRefundByOrderId($wh);
                $arrs = (new MallNewGroupOrder())->getOneByWh($val['order_id'], 0);
                if (!empty($arr) || !empty($arrs)) {
                    unset($orderList[$key]);
                    continue;
                }
            }
        }
        array_values($orderList);
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        //设置单元格内容
        $worksheet->setCellValueByColumnAndRow(1, 1, '订单编号');
        $worksheet->setCellValueByColumnAndRow(2, 1, '*物流公司');
        $worksheet->setCellValueByColumnAndRow(3, 1, '*物流单号');
        //设置单元格样式
        $worksheet->getStyle('A1:O1')->getFont()->setName('黑体')->setSize(12);
        $spreadsheet->getActiveSheet()->getStyle('A:O')->getAlignment()->setWrapText(true);
        $spreadsheet->getActiveSheet()->getStyle('A1:O1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EEEEEE');
        $spreadsheet->getActiveSheet()->getRowDimension('1')->setRowHeight(26);
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(26);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(26);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(26);
        $len = count($orderList);
        $j = 0;
        $row = 0;
        $i = 0;
        foreach ($orderList as $key => $val) {
            if ($i < $len) {
                $j = $i + 2; //从表格第2行开始
                $worksheet->setCellValueByColumnAndRow(1, $j, $val['order_no']);
                $worksheet->setCellValueByColumnAndRow(2, $j, $val['express_name']);
                $worksheet->setCellValueByColumnAndRow(3, $j, $val['express_num']);
                $i++;
            }
            $row++;
        }
        $styleArrayBody = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
        ];
        $total_rows = $row + 1;
        //添加所有边框/居中
        $worksheet->getStyle('A1:O' . $total_rows)->applyFromArray($styleArrayBody);
        //下载
        $filename = date("Y-m-d", time()) . '-' . "导出订单模板" . '.xlsx';
        (new BaseExportService())->phpSpreadsheet($filename, $spreadsheet);
        return $this->downloadExportFile($filename);
    }


    /**
     * 导出Excel订单(Spreadsheet方法)
     * @param $param
     */
    public function failLogExportExcel($param)
    {
        $log_id = isset($param['log_id']) ? $param['log_id'] : 0;//店铺id
        $where = array();
        if ($log_id == 0) {//店铺id
            throw new \think\Exception("参数缺失");
        } else {
            $where = [['log_id', '=', $log_id]];
        }
        $orderList = (new ShopGoodsBatchFailLog())->getList($where, "pigcms_id asc");
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        //设置单元格内容
        $worksheet->setCellValueByColumnAndRow(1, 1, '订单编号');
        $worksheet->setCellValueByColumnAndRow(2, 1, '*物流公司');
        $worksheet->setCellValueByColumnAndRow(3, 1, '*物流单号');
        $worksheet->setCellValueByColumnAndRow(4, 1, '失败原因(再次上传请删除此列)');
        //设置单元格样式
        $worksheet->getStyle('A1:O1')->getFont()->setName('黑体')->setSize(12);
        $spreadsheet->getActiveSheet()->getStyle('A:O')->getAlignment()->setWrapText(true);
        $spreadsheet->getActiveSheet()->getStyle('A1:O1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EEEEEE');
        $spreadsheet->getActiveSheet()->getRowDimension('1')->setRowHeight(26);
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(26);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(26);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(26);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(26);
        $len = count($orderList);
        $j = 0;
        $row = 0;
        $i = 0;
        if ($len > 0) {
            foreach ($orderList as $key => $val) {
                if ($i < $len) {
                    $j = $i + 2; //从表格第2行开始
                    $worksheet->setCellValueByColumnAndRow(1, $j, $val['goods_name']);
                    $res = json_decode($val['data'], true);
                    $worksheet->setCellValueByColumnAndRow(2, $j, $res['B']);
                    $worksheet->setCellValueByColumnAndRow(3, $j, $res['C']);
                    $worksheet->setCellValueByColumnAndRow(4, $j, $val['error_msg']);
                    $i++;
                }
                $row++;
            }
        }

        $styleArrayBody = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
        ];
        $total_rows = $row + 1;
        //添加所有边框/居中
        $worksheet->getStyle('A1:O' . $total_rows)->applyFromArray($styleArrayBody);
        //下载
        $filename = date("Y-m-d", time()) . '-' . "导出下载失败" . '.xlsx';
        (new BaseExportService())->phpSpreadsheet($filename, $spreadsheet);
        return $this->downloadExportFile($filename);
    }

    /**
     * 下载表格
     */
    public function downloadExportFile($param)
    {
        // TODO 验证身份
        // if(empty($_SESSION['staff'])&&empty($_SESSION['merchant'])&&empty($_SESSION['system'])){
        $returnArr = [];
        // var_dump($export);
        if (!file_exists(request()->server('DOCUMENT_ROOT') . '/v20/runtime/' . $param)) {
            $returnArr['error'] = 1;
            return $returnArr;
        }

        if (request()->isAjax()) {
            $returnArr['error'] = 1;
            return $returnArr;
        }

        $filename = $param;

        $ua = request()->server('HTTP_USER_AGENT');
        $ua = strtolower($ua);
        if (preg_match('/msie/', $ua) || preg_match('/edge/', $ua) || preg_match('/trident/', $ua)) { //判断是否为IE或Edge浏览器
            $filename = str_replace('+', '%20', urlencode($filename)); //使用urlencode对文件名进行重新编码
        }
        $returnArr['url'] = cfg('site_url') . '/v20/runtime/' . $param;
        $returnArr['error'] = 0;
        return $returnArr;
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * 导入excel更新订单表
     */
    public function uploadData($param, $fileExtendName)
    {
        $storeId = isset($param['store_id']) ? intval($param['store_id']) : 0;
        $name = $param['name'];
        $type = $param['type'];//导入新增，，导入修改
        if ($storeId != 0) {
            $result = invoke_cms_model('Merchant_store/getStoreById', [$storeId]);
            $result = $result['retval'];
        } else {
            throw new \think\Exception("店铺id参数缺失");
        }

        if ($fileExtendName == 'xlsx') {
            $objReader = IOFactory::createReader('Xlsx');
        } else {
            $objReader = IOFactory::createReader('Xls');
        }

        $objPHPExcel = $objReader->load(request()->server('DOCUMENT_ROOT') . $param['file_url']);  //$filename可以是上传的表格，或者是指定的表格
        $objPHPExcel = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
        $header = $objPHPExcel[1];//标题头
        unset($objPHPExcel[1]);
        if (empty($objPHPExcel)) {
            return 4;
            // throw new \think\Exception("表格没数据");
        }
        $success = 0;//成功数量
        $fail = 0;//失败数量
        $log = [
            'action' => 6,
            'log_date' => date('Y-m-d H:i:s'),
            'file' => '',
            'store_id' => $storeId,
            'name' => $name,
            'mer_id' => $result['mer_id'],
            'header' => json_encode([$header['A'], $header['B'], $header['C']], JSON_UNESCAPED_UNICODE)
        ];
        /*$log['action']=6;
        $log['log_date']=date('Y-m-d H:i:s');
        $log['file']='';
        $log['store_id']=$storeId;
        $log['name']=$name;
        $log['mer_id']=$result['mer_id'];
        $log['header']=json_encode([$header['A'], $header['B'], $header['C']], JSON_UNESCAPED_UNICODE);*/
        $logId = (new ShopGoodsBatchLog())->addOne($log);

        foreach ($objPHPExcel as $k => $v) {
            try {
                $msg = "";
                $name = $v['A'];
                //判断必填项
                if (empty($v['A'])) {
                    $msg .= '订单号未填写  ';
                }
                if (empty($v['B'])) {
                    $msg .= '物流公司未填写  ';
                }
                if (empty($v['C'])) {
                    $msg .= '物流单号未填写  ';
                }
                if (empty($v['A']) || empty($v['B']) || empty($v['C'])) {
                    throw new \Exception(L_('错误'));
                }
                $where = [['name', '=', $v['B']]];
                $res = (new Express())->getOne($where);
                if (empty($res)) {
                    $msg .= '物流公司错误  ';
                    throw new \Exception(L_('错误'));
                }

                if ($type == 'add') {//批量发货新增物流信息
                    $where = [['order_no', '=', $v['A']]];
                    $arr['express_name'] = $v['B'];
                    $arr['express_num'] = $v['C'];
                    $arr['express_id'] = $res['id'];
                    $arr['send_time'] = time();
                    $arr['express_time'] = time();
                    $arr['express_code'] = $res['code'];
                    $msg1 = (new MallOrder())->getDiscount($where, $field = "order_id,status");
                    if (!empty($msg1)) {
                        if ($msg1['status'] == 20) {
                            $msg .= '订单已发货  ';
                            throw new \Exception(L_('错误'));
                        } else {
                            (new MallOrder())->updateClerkMoney($where, $arr);//更新物流信息
                            $this->changeOrderStatus($msg1['order_id'], 20, '店员批量发货');//更新发货状态
                            $success++;
                        }
                    } else {
                        $msg .= '订单号不存在  ';
                        throw new \Exception(L_('错误'));
                    }
                } else {//批量修改更新物流信息
                    $where = [['order_no', '=', $v['A']], ['express_num', '<>', ''], ['express_id', '>', 0]];
                    $arr['express_name'] = $v['B'];
                    $arr['express_num'] = $v['C'];
                    $arr['express_id'] = $res['id'];
                    $arr['send_time'] = time();
                    $arr['express_time'] = time();
                    $arr['express_code'] = $res['code'];
                    $msg1 = (new MallOrder())->getDiscount($where, $field = "order_id,status,pay_time");
                    if (!empty($msg1)) {
                        if ((time() - $msg1['pay_time']) > 86400) {
                            $msg .= '该订单发货已超过24小时，不可修改物流信息  ';
                            throw new \Exception(L_('错误'));
                        } else {
                            (new MallOrder())->updateClerkMoney($where, $arr);//更新物流信息
                            $success++;
                        }
                    } else {
                        $msg .= '订单号不存在  ';
                        throw new \Exception(L_('错误'));
                    }
                }
            } catch (\Exception $t) {
                $data = ['A' => $v['A'], 'B' => $v['B'], 'C' => $v['C']];
                if (empty($v['A']) && empty($v['B']) && empty($v['C'])) {
                    continue;
                }
                $failLog = [
                    'log_id' => $logId,
                    'goods_name' => $name ?? '',
                    'data' => $data ? json_encode($data, JSON_UNESCAPED_UNICODE) : '{}',
                    'error_msg' => $msg
                ];
                (new ShopGoodsBatchFailLog())->addOne($failLog);
                $fail++;
            }
        }

        $where = [['pigcms_id', '=', $logId]];
        $datas = ['status' => 2, 'success_num' => $success, 'error_num' => $fail];
        (new ShopGoodsBatchLog())->updateOne($datas, $where);
        if ($fail == 0) {
            //$this->success(L_('全部导入成功'));
            return 1;
        } else if ($success > 0) {
            return 2;
            //$this->success(L_('导入完成！其中部分商品导入失败，您可在操作记录列表中下载导入失败记录，做相应修改后继续导入'));
        } else {
            return 3;
            //$this->success(L_('全部导入失败！您可在操作记录列表中下载导入失败记录，做相应修改后继续导入'));
        }

    }

    /**
     * 判断限购是否满足
     * @param  [type] $goods_id 商品ID
     * @param  [type] $type     限购规则 0=终身限购 1=每天限购 2=每周限购 3=每月限购
     * @param  [type] $nums     限购数量
     * @param  [type] $buy      即将购买多少个
     * @return bool
     */
    public function checkLimit($uid, $goods_id, $type = 0, $nums = 0, $buy = 1)
    {
        if ($nums == 0) return true;
        if (empty($uid) || empty($goods_id)) {
            throw new \think\Exception("判断限购方法参数传递异常");
        }
        $where = [
            ['d.goods_id', '=', $goods_id],
            ['o.uid', '=', $uid],
            ['d.is_gift', '=', 0],
            ['o.status', 'between', [0, 49]]
        ];
        switch ($type) {
            case '0':
                break;
            case '1':
                $s = strtotime(date('Y-m-d 00:00:00'));
                $e = $s + 86400;
                $where[] = ['o.create_time', 'between', [$s, $e]];
                break;
            case '2':
                $t = date('w') > 0 ? date('w') : 7;
                $s = strtotime(date('Y-m-d 00:00:00', strtotime('-' . ($t - 1) . ' days')));
                $e = $s + 7 * 86400;
                $where[] = ['o.create_time', 'between', [$s, $e]];
                break;
            case '3':
                $s = strtotime(date('Y-m-01 00:00:00'));
                $e = strtotime(date('Y-m-01 00:00:00', strtotime("+1 month")));
                $where[] = ['o.create_time', 'between', [$s, $e]];
                break;
            default:
                throw new \think\Exception("判断限购方法参数传递异常");
                break;
        }
        $data = $this->MallOderDatail->getOrderGoods($where, 'd.num');
        if (empty($data)) {
            return true;
        }
        $data = $data->toArray();
        $count = array_sum(array_column($data, 'num'));
        if (($count + $buy) > $nums) {
            return false;
        }
        return true;
    }


    /**
     * 获取商城商品信息
     * @return mixed
     * @author: 张涛
     * @date: 2021/03/03
     */
    public function getGoodsByOrderId($orderId, $fields = '*', $isPeriodic = false)
    {
        if ($isPeriodic) {
            $periodicItem = (new MallNewPeriodicPurchaseOrder())->getNowPeriodicOrder(['id' => $orderId]);
            $orderId = $periodicItem['order_id'] ?? 0;
        }
        return $this->MallOderDatail->where(['order_id' => $orderId])->field($fields)->select()->toArray();
    }

    /**
     * 修改订单价格
     * @param  [type] $order_id  订单ID
     * @param  [type] $new_price 修改之后的价格
     * @return bool            true=修改成功 false=修改失败
     */
    public function changeOrderPrice($order_id, $new_price)
    {
        $order = $this->getOrderDetail($order_id);
        if ($new_price == $order['order']['money_real']) return true;
        $real_money_total_detail = get_format_number(array_sum(array_column($order['detail'], 'money_total')));
        $temp = 0;
        foreach ($order['detail'] as $key => $value) {//找到每个详情价格在总价中的占比进行分配
            $where = [
                ['id', '=', $value['id']]
            ];
            $data = [];
            if ($key == count($order['detail']) - 1) {//最后一个
                $data['money_total'] = $new_price - $temp;
            } else {
                $data['money_total'] = get_format_number(($value['money_total'] / $real_money_total_detail) * $new_price);
                $temp += $data['money_total'];
            }
            $this->MallOderDatail->updateThis($where, $data);
        }
        $this->MallOderModel->updateThis([['order_id', '=', $order_id]], ['money_real' => $new_price]);
        return true;
    }

    /**
     * 获取订单及购买者信息
     * User: zhanghan
     * Date: 2022/1/18
     * Time: 15:25
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getOrderUserInfoList($where,$field = true,$page = 0,$limit = 10){
        if(empty($where)){
            return [];
        }
        $list = $this->MallOderModel->getOrderUserInfoList($where,$field,$page,$limit);
        if(!empty($list)){
            foreach ($list as &$value){
                if(isset($value['avatar']) && empty($value['avatar'])){
                    $value['avatar'] = cfg('site_url').'/static/images/user_avatar.jpg';
                }
                if(isset($value['nickname'])){
                    $value['title'] = '附近的游客**下单了';
                    if(!empty($value['nickname'])){
                        $nickname = substr($value['nickname'],0,3).'**';
                        $value['title'] = '附近的'.$nickname.'下单了';
                    }
                }
            }
        }
        return $list;
    }


    public function getOrderList($param)
    {
        $where[] = ['o.store_id','=',$param['store_id']];
        if($param['start_time']){
            $where[] = ['o.pay_time','>=',strtotime($param['start_time'].' 00:00:00')];
        }
        if($param['end_time']){
            $where[] = ['o.pay_time','<=',strtotime($param['end_time'].' 23:59:59')];
        }
        if($param['status'] > 1){
            switch ($param['status']) {
                case '2':
                    $where[] = ['o.status', '>=', 0];
                    $where[] = ['o.status', '<', 10];
                    break;
                case '3':
                    $where[] = ['o.status', '>=', 10];
                    $where[] = ['o.status', '<', 20];
                    break;
                case '4':
                    $where[] = ['o.status', '>=', 20];
                    $where[] = ['o.status', '<', 30];
                    break;
                case '5':
                    $where[] = ['o.status', '>=', 30];
                    $where[] = ['o.status', '<', 50];
                    break;
                case '6':
                    $where[] = ['o.status', '>=', 50];
                    $where[] = ['o.status', '<', 60];
                    break;
                case '7':
                    $where[] = ['o.status', '=', 60];
                    break;
                case '8':
                    $where[] = ['o.status', '=', 70];
                    break;
                default:
                    break;
            }
        }
        $field = 'o.order_no,o.pay_time,o.online_pay_type,o.money_real,o.remark,o.address,adr.longitude,adr.latitude,o.username,o.phone,o.order_id';
        $data = $this->MallOderModel->getOrderList($field, $where, $param['page'], $param['pageSize']);
        $orderIdAry = [];
        $productInfo = [];
        foreach ($data['data'] as &$v){
            $jwdInfo = (new AreaService())->addressInfo($v['address']);
            $lng = '';
            $lat = '';
            if ($jwdInfo && $jwdInfo['status'] === 0) {
                $lng = $jwdInfo['result']['location']['lng'];
                $lat = $jwdInfo['result']['location']['lat'];
            }

            $v['pay_time'] = $v['pay_time'] ? date('Y-m-d H:i:s',$v['pay_time']) : '';
            $v['online_pay_type'] = 2;
//            $v['online_pay_type'] = $v['online_pay_type'] ? 2 : 3;
            //收货地址
            $v['address_info'] = [
                'address'=>$v['address'],
                'longitude'=>$lng,
                'latitude'=>$lat,
//                'longitude'=>$v['longitude'],
//                'latitude'=>$v['latitude'],
                'name'=>$v['username'],
                'phone'=>$v['phone'],
            ];
            unset($v['address'],$v['longitude'],$v['latitude'],$v['username'],$v['phone']);
            $orderIdAry[] = $v['order_id'];
        }
        if($orderIdAry){
            //查询订单中的商品信息
            $fields = 'd.goods_id,g.name,d.num,d.order_id';
            $info = (new MallOrderDetail())->getGoodsJoinData([['d.order_id','IN',$orderIdAry]],$fields)->toArray();
            foreach ($info as $val){
                $productInfo[$val['order_id']][] = [
                    'goods_id'=>$val['goods_id'],
                    'name'=>$val['name'],
                    'num'=>$val['num']
                ];
            }
        }
        if($productInfo){
            foreach ($data['data'] as &$v){
                $v['product_info'] = $productInfo[$v['order_id']]??[];
                unset($v['order_id']);
            }
        }
        //查询
        return $data;
    }

    /**
     * 修改商城订单
     * @author Nd
     * @date 2022/5/6
     */
    public function editOrderStatus($param)
    {
        $thirdDistributionInfo = [
            'worker_name'=>$param['worker_name'],
            'worker_phone'=>$param['worker_phone'],
            'order_status'=>$param['order_status'],//订单状态：201已接单 401已完成
            'update_time'=>$param['update_time'],
        ];
        //查询订单信息
        $orderInfo = $this->MallOderModel->getOrder(['order_no'=>$param['order_no']],true,'');
        if(!$orderInfo){
            throw new \think\Exception("未查询到订单信息");
        }
        $orderInfo['third_distribution_info'] = $orderInfo['third_distribution_info'] ? json_decode($orderInfo['third_distribution_info'],true) : [];
        $orderInfo['third_distribution_info'][] = $thirdDistributionInfo;
        $updateData['third_distribution_info'] = json_encode($orderInfo['third_distribution_info']);
        if($param['order_status'] == 201){
            //已接单
            $status = 1;
        }
        if($param['order_status'] == 401){
            //订单已完成
            $status = 4;
        }
        //修改订单状态
        $this->housemanOrderLog($orderInfo['goods_activity_type'] == 'periodic' ? 'periodic' : 'order', $status, $orderInfo['order_id'], '', '', [
            'id'=>0,
            'name'=>$param['worker_name'],
            'phone'=>$param['worker_phone'],
        ]);
        //修改订单信息
        $update = $this->updateMallOrder(['order_id'=>$orderInfo['order_id']],$updateData);
        if(!$update){
            throw new \think\Exception("修改订单信息失败");
        }
    }


    /**
     * @param $order_id
     * 手动退款
     */
    public function refundOrderManual($order_id, $refund_id, $is_all, $source = 1,$price_back=0)
    {
        if (empty($order_id)) {
            throw new \think\Exception('缺少order_id参数');
        }
        if (empty($refund_id)) {
            $refund_record = (new MallOrderRefund())->where('order_id', $order_id)->where('status', 0)->findOrEmpty()->toArray();
            if ($refund_record) {
                $refund_id = $refund_record['refund_id'];
                $is_all = $refund_record['is_all'];
            }
        }
        // 启动事务
        Db::startTrans();
        try {
            $msg = (new MallOrder())->getOne($order_id);
            $this->mallOrderRefundService->auditRefundManual($order_id, $refund_id, 1,'',$is_all,$price_back);
            if ($is_all == 1) {
                //是全部退款就更新订单总表并记日志
                $note = '店员同意退款申请';
                $this->changeOrderStatus($order_id, 70, $note);
            } else {
                //部分退款只更新日志
                if ($source == 1) {//店员同意
                    $note = '店员同意退款申请';
                } elseif ($source == 2) {//超时自动同意
                    $note = '已退款，店员超时未处理，系统自动同意退款';
                }
                $this->mallOrderlog($order_id, 70, $note);
            }
            //更新退款表
            $data = ['status' => 1, 'audit_time' => time()];
            if($refund_id){
                $this->mallOrderRefundService->updateRefund($refund_id, $data);
            }

            if (!empty($msg) && $msg['goods_activity_type'] == 'periodic') {
                (new MallActivityService())->cancelUpdatePeriodic($order_id);
                $status = (new MallActivityService())->getPeriodicOrderStatus($order_id);
                $this->mallOrderlog($order_id, $status, '周期购同意退款，主订单状态修改');
            }
            if (!empty($msg) && $msg['goods_activity_type'] == 'group') {
                (new MallActivityService())->saveOrderUserUpdate($order_id);
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        if($refund_id){
            //退款成功推送消息
            (new SendTemplateMsgService())->sendWxappMessage(['type' => 'refund_success', 'order_id' => $order_id, 'status' => 70, 'refund_id' => $refund_id]);
        }

        invoke_cms_model('System_order/sendAppPushMessage', ['orderId' => $order_id, 'type' => 'mall3']);
        return true;
    }
    
    public function checkOrderStatus($status,$orderStatus,$orderId=0)
    {
        if($orderId){
            $order_info = $this->MallOderModel->getOne($orderId);
            if(!$order_info){
                throw new \think\Exception('订单不存在');
            }
            $orderStatus = $order_info['status'];
        }
        if($status == 11 && $orderStatus >= 11 && !in_array($orderStatus,[60,61])){
            throw new \think\Exception('订单当前状态无法接单，请刷新页面后操作');
        }
        if($status == 15 && $orderStatus == 15){
            throw new \think\Exception('订单已分配给骑手，请勿重复操作');
        }
        if($status == 20 && $orderStatus > 20 && !in_array($orderStatus,[60,61])){
            throw new \think\Exception('订单当前状态无法发货，请刷新页面后操作');
        }
        if(in_array($status,[30,31,32,33]) && ($orderStatus<10 || $orderStatus>=30) && !in_array($orderStatus,[60,61])){
            throw new \think\Exception('订单当前状态无法完成，请刷新页面后操作'); 
        }
        if(in_array($status,[50,51,52,53]) && $orderStatus>50){
            throw new \think\Exception('订单当前状态无法取消，请刷新页面后操作');
        }
        if($status == 60 && ($orderStatus>=60 || in_array($orderStatus,[20]))){
            throw new \think\Exception('订单当前状态无法申请退款，请刷新页面后操作'); 
        }
        if($status == 70 && $orderStatus>=70){
            throw new \think\Exception('订单当前状态无法退款，请刷新页面后操作');
        }
    }
    
    public function orderDelivery(array $params)
    {
        validate(OrderDelivery::class)->scene('check_order_delivery')->check($params);
        foreach ($params['extra_delivery'] as $v) {
            validate(OrderDelivery::class)->scene('check_extra_delivery')->check($v);
            if(!empty($v['order_detail_ids'])){
                $orderDetailIds = explode(',', $v['order_detail_ids']);
                $goodsCount = MallOrderDetail::where([
                        'order_id' => $params['order_id']
                    ])
                    ->whereIn('id', $orderDetailIds)
                    ->count();
                if($goodsCount != count($orderDetailIds)){
                    throw_exception(L_('订单发货部分商品不存在'));
                }
            }
        }
        if(count($params['extra_delivery']) > 1 && $params['express_type'] == MallOrderService::EXPRESS_TYPE_1){
            throw_exception(L_('多包裹发货暂不支持面单发货'));
        }
        return Db::transaction(function ()use($params){
            $express = array_shift($params['extra_delivery']);
            $express = array_merge($params, $express);
            $data = $this->deliverGoodsByExpress($express);

            if(!empty($params['extra_delivery'])){
                $data = $this->orderExtraDelivery($params);
            }
            return $data;
        });
    }

    /**
     * 店员修改配送方式
     * @throws ModelNotFoundException
     * @throws DbException
     * @throws DataNotFoundException
     */
    public function changeDeliveryType(int $orderId, int $deliveryType, array $staffUser): bool
    {
        if(!in_array($deliveryType, [1, 2])){//配送方式1：骑手配送，2：普通快递 3：到店自提'
            throw_exception(L_('配送方式不存在'));
        }
        $order = MallOrder::where([
            'order_id' => $orderId
        ])->find();
        if(empty($order)){
            throw_exception(L_('当前订单不存在'));
        }
        if($order['status'] != MallOrder::$orderStatus['wait_send']){
            throw_exception(L_('当前订单不可更改配送方式'));
        }

        $order->express_style = $deliveryType;

        $data = [
            'order_id' => $orderId,
            'note' => '店员修改配送方式',
            'status' => $order->status,
            'old_status' => $order->status,
            'operator' => 'storestaff',
            'operator_id' => $staffUser['id'],
            'operator_name' => $staffUser['name'],
            'operator_phone' => $staffUser['tel'],
            'addtime' => time()
        ];
        (new MallOrderLog())->addOne($data);
        
        return $order->save();
    }

    /**
     * 商城导出列配置
     *
     * @date: 2024/03/04
     */
    public function exportColumns()
    {
        return [
            [
                'title' => L_('基础信息'),
                'columns' => [
                    ['name' => L_('订单编号'), 'value' => 'order_no'],
                    ['name' => L_('商家名称'), 'value' => 'mer_name'],
                    ['name' => L_('店铺名称'), 'value' => 'store_name'],
                    ['name' => L_('商品名称'), 'value' => 'goods_name'],
                    ['name' => L_('商品单价'), 'value' => 'price'],
                    ['name' => L_('商品数量'), 'value' => 'num'],
                    ['name' => L_('商品规格'), 'value' => 'sku_info'],
                    ['name' => L_('供货价'), 'value' => 'supply_price'],
                    ['name' => L_('商品编码'), 'value' => 'goods_code'],
                    ['name' => L_('进货价'), 'value' => 'cost_price'],
                    ['name' => L_('商品总价'), 'value' => 'sku_all_price'],
                    ['name' => L_('配送方式'), 'value' => 'express_name'],
                    ['name' => L_('收货人'), 'value' => 'username'],
                    ['name' => L_('收货电话'), 'value' => 'phone'],
                    ['name' => L_('收货地址'), 'value' => 'address'],
                    ['name' => L_('买家备注'), 'value' => 'remark'],
                    ['name' => L_('订单状态'), 'value' => 'status_txt'],
                    ['name' => L_('下单时间'), 'value' => 'create_time'],
                ]
            ],
            [
                'title' => L_('支付信息'),
                'columns' => [
                    ['name' => L_('优惠总金额'), 'value' => 'discount_total'],
                    ['name' => L_('在线支付金额'), 'value' => 'money_online_pay'],
                    ['name' => L_('平台余额支付金额'), 'value' => 'money_system_balance'],
                    ['name' => L_('平台优惠券抵扣金额'), 'value' => 'discount_system_coupon'],
                    ['name' => L_('商家优惠券抵扣金额'), 'value' => 'discount_merchant_coupon'],
                    ['name' => L_('实际付款'), 'value' => 'money_real'],
                    ['name' => L_('支付方式'), 'value' => 'pay_type'],
                    ['name' => L_('第三方支付流水号'), 'value' => 'pay_orderno'],
                ]
            ],
            [
                'title' => L_('其他信息'),
                'columns' => [
                    ['name' => L_('店员备注'), 'value' => 'clerk_remark'],
                    ['name' => L_('核销店员'), 'value' => 'staff_name'],
                    ['name' => L_('商品留言'), 'value' => 'forms_txt'],
                    ['name' => L_('平台结算给商家的金额'), 'value' => 'settlement_merchant_money'],
                    ['name' => L_('原始订单编号'), 'value' => 'real_order_no'],
                    ['name' => L_('部分退款金额'), 'value' => 'refund_money'],
                    ['name' => L_('配送开始时间'), 'value' => 'express_start_time'],
                    ['name' => L_('配送结束时间'), 'value' => 'express_end_time'],
                    ['name' => L_('完成时间'), 'value' => 'complete_time'],
                ]
            ]
        ];
    }
    
    public function uploadMiaomaiOrder($orderId): bool
    {
        $order = MallOrder::where(['order_id' => $orderId])->find();
        $details = (new MallOrderDetailService)->getByOrderId(true, $order['order_id']);

        $merchant = Merchant::where(['mer_id' => $order['mer_id']])->find();
        if(empty($merchant['miaomaiyun_switch'])){
            return false;
        }
        $merchantStore = MerchantStore::where(['store_id' => $order['store_id']])->find();
        if(empty($merchantStore['miaomaiyun_erp_storeid'])){
            return false;
        }
        $province = Area::where(['area_id' => $order['province_id']])->value('area_name');
        $city = Area::where(['area_id' => $order['city_id']])->value('area_name');
        $area = Area::where(['area_id' => $order['area_id']])->value('area_name');
        $startTime = $endTime = 0;
        if(!empty($order['express_send_time'])){
           $expressTime = explode(',', $order['express_send_time']);
           $startTime = date('Y-m-d H:i:s', $expressTime[0]);
           $endTime = date('Y-m-d H:i:s', $expressTime[1]);
        }
        $payMethod = [
            'wechat' => ['pay_method' => '2', 'name' => '微信支付'],
            'alipay' => ['pay_method' => '3', 'name' => '支付宝支付'],
            'card' => ['pay_method' => '5', 'name' => '会员卡支付'],
            ['pay_method' => '11', 'name' => '挂账'],
            'score' => ['pay_method' => '2050', 'name' => '积分兑换'],
        ];
        $origin = [
            'androidapp' => ['origin' => 1, 'origin_name' => '安卓app'],
            'iosapp' => ['origin' => 2, 'origin_name' => '苹果app'],
            'wechat_mini' => ['origin' => 3, 'origin_name' => '小程序'],
            'h5' => ['origin' => 4, 'origin_name' => '网页'],
            'wechat_h5' => ['origin' => 4, 'origin_name' => '网页'],
            ['origin' => 5, 'origin_name' => '收银端'],
            ['origin' => 6, 'origin_name' => '扫码支付'],
            ['origin' => 99, 'origin_name' => '其他'],
        ];
        $mode = [
           1 => ['mode' => 1, 'mode_name' => '配送'],
           2 => ['mode' => 1, 'mode_name' => '配送'],
           3 => ['mode' => 2, 'mode_name' => '自提'],
        ];
        $orderFrom = 99;
        if(isset($origin[$order['source']])){
            $orderFrom = $origin[$order['source']]['origin'];
        }
        $orderPayMethod = 5;
        if(isset($payMethod[$order['online_pay_type']])){
            $orderPayMethod = $payMethod[$order['online_pay_type']]['pay_method'];
        }
        if(empty($order['address'])){
            $order['address'] = $merchantStore['adress'];
        }
        $data = [
            'appid' => $merchant['miaomaiyun_erp_appid'],
            'stores_id' => $merchantStore['miaomaiyun_erp_storeid'],
            'member_openid' => $order['phone'],
            'freight' => $order['money_freight'],
            'pay_time' => date('Y-m-d H:i:s', $order['pay_time']),
            'pay_method' => $orderPayMethod,
            'origin' => $orderFrom,
            'mode' => $mode[$order['express_style']]['mode'],
            'address' => $order['address'],
            'pcac' => "$province/$city/$area",
            'otype' => 1,
            'member_ver' => 2,
            'districts' => $order['address'],
            'start_time' => $startTime,
            'end_time' => $endTime,
            'name' => $order['username'],
            'tel' => $order['phone'],
            'out_code' => $order['order_no'],
            'remark' => $order['remark'],
        ];
        
        foreach($details as $detail){
            $miaomaiBarcodeId = ShopGoods::where([
                'goods_id' => MallGoods::where(['goods_id' => $detail['goods_id']])->value('common_goods_id')
            ])->value('miaomaiyun_barcode_id');
            $data['goods'][] = [
                'barcode_id' => $miaomaiBarcodeId,
                'num' => $detail['num'],
            ];
        }
        $data['goods'] = json_encode($data['goods'], JSON_UNESCAPED_UNICODE);
        $miaomaiyun = new Miaomaiyun();
        $result = $miaomaiyun
            ->setConfig($merchant['miaomaiyun_erp_appid'], $merchant['miaomaiyun_erp_appsecret'], $merchant['miaomaiyun_url'])
            ->setParams($data)
            ->run(Miaomaiyun::ORDER_UPD);
        fdump_api(['data' => $data,'result' => $result], 'miaomai/uploadMiaomaiMallOrder', 1);
        $upload = [
            'order_no' => $order['order_no'],
            'order_type' => 'mall',
            'data' => $data,
            'result' => $result,
            'status' => $result['status'] == 'y' ? 1 : 2,
        ];
        if($result['status'] == 'y'){
            $order->miaomai_order_code = $result['data']['order_code'];
            $order->save();
            $upload['miaomaiyun_order_code'] = $result['data']['order_code'];
        }
        MiaomaiOrderUpload::saveInfo($upload);
        
        return true;
    }
    
    
    public function miaomaiMallOrderRefund($refundId)
    {
        $orderRefund = MallOrderRefund::where('refund_id',$refundId)->find();
        $refundGoods = MallOrderRefundDetail::where('refund_id',$refundId)->select();
        $mallOrder = MallOrder::where('order_id',$orderRefund['order_id'])->find();
        $merchant = Merchant::where(['mer_id' => $mallOrder['mer_id']])->find();
        if(empty($merchant['miaomaiyun_switch'])){
            fdump_api(['refund_id' => $refundId, 'error' => '淼迈云开关未打开'], 'miaomai/miaomaiMallOrderRefund', 1);
            return false;
        }
        $merchantStore = MerchantStore::where(['store_id' => $mallOrder['store_id']])->find();
        if(empty($merchantStore['miaomaiyun_erp_storeid'])){
            fdump_api(['refund_id' => $refundId, 'error' => '店铺淼迈云店铺未设置'], 'miaomai/miaomaiMallOrderRefund', 1);
            return false;
        }
        $goods = [];
        foreach ($refundGoods as $value) {
            $mallGoods = MallGoods::alias('m')
                ->join('shop_goods s','m.common_goods_id = s.goods_id')
                ->join('shop_order_detail d','d.goods_id = m.goods_id')
                ->where('d.id',$value['order_detail_id'])
                ->field('m.name goods_name,m.goods_id,m.common_goods_id,s.miaomaiyun_barcode_id')
                ->find();
            $goods[] = [
                'barcode_id' => $mallGoods['miaomaiyun_barcode_id'],
                'name' => $mallGoods['goods_name'],
                'num' => $value['refund_nums'],
                'reason' => $orderRefund['reason'],
            ];
        }
        $miaomaiOrderCode = $mallOrder['miaomai_order_code'];
        $userPhone = User::where(['uid' => $mallOrder['uid']])->value('phone');
        $refundInfo = [
            'order_code' => $miaomaiOrderCode,
            'member_openid' => $userPhone,
            'reason' => 7,//其他
            'goods' => json_encode($goods,JSON_UNESCAPED_UNICODE)
        ];

        $result = (new Miaomaiyun)
            ->setConfig($merchant['miaomaiyun_erp_appid'], $merchant['miaomaiyun_erp_appsecret'], $merchant['miaomaiyun_url'])
            ->setParams($refundInfo)
            ->run(Miaomaiyun::ORDER_REFUND_APPLY);

        fdump_api(['refund_info' => $refundInfo, 'result' => $result],'miaomai/miaomaiyun_refund_apply',1);

        $upload = [
            'refund_id' => $refundId,
            'order_type' => 'mall',
            'data' => $refundInfo,
            'result' => $result,
            'status' => $result['status'] == 'y' ? 1 : 2,
        ];
        if($result['status'] == 'y'){
            $orderRefund->miaomai_order_code = $result['data']['refund_code'];
            $orderRefund->save();
            $upload['miaomaiyun_order_code'] = $result['data']['refund_code'];
        }
        MiaomaiOrderRefundUpload::saveInfo($upload);

        return true;
    }
    public function miaomaiChangeOrderStatus($order, $status, $staff)
    {
        $mallOrder = MallOrder::where(['order_id' => $order['order_id']])->find();
        $orderId = $mallOrder['order_id'];
        switch ($status){
            case 10://发货
                (new MallOrderService())->orderTaking($orderId, 0, 0, 0);
                switch ($mallOrder['express_style']){
                    case 1://骑手配送
                        (new MallOrderService())->deliverGoodsByHouseman($orderId, 0);
                        break;
                    case 2://普通快递
                        break;
                }
                break;
            case 20://完成
                switch ($mallOrder['express_style']){
                    case 1://骑手配送
                        (new MallOrderService())->changeOrderStatus($orderId, 33, '骑手已送达');
                        break;
                    case 2://普通快递
                        (new MallOrderService())->changeOrderStatus($orderId, 30, '用户确认收货');
                        break;
                    case 3://店员核销
                        (new MallOrderService())->staffVerify($orderId,'',$staff);
                        break;
                    default:
                        throw_exception('配送状态错误');
                }
                break;
            case 30://取消
                (new MallOrderService())->changeOrderStatus($orderId, 53, '收银员取消订单');
                break;
            case 40://退货
                if($mallOrder['status'] == 70){
                   throw_exception('订单已退款，请勿重复操作!');
                }
                if(!empty($order['refund_code']) && !empty($order['refund_status'])){
                    $refund = MallOrderRefund::where(['miaomai_refund_code' => $order['refund_code']])->find();
                    switch ($order['refund_status']){
                        case 1://退款
                            (new MallOrderRemindService)->insertRemind($mallOrder['store_id'], 30, $orderId);
                            (new MallOrderService())->AgreeRefund($orderId, $refund['refund_id'], 0);
                            break;
                        case 2://拒绝退款
                            (new MallOrderService())->refuseRefund($orderId, $refund['refund_id'], '淼迈拒绝退款');
                            break;
                        default:
                            throw_exception('退款状态异常');
                    }
                }else{
                    throw_exception('退款失败');
                }
                break;
            default:
                throw_exception('订单状态异常');
        }
        
        return true;
    }
}