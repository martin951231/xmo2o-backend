<?php
/**
 * 订单接口
 * Created by subline.
 * Author: lumin
 * Date Time: 2020/9/09 10:46
 */

namespace app\mall\controller\api;
use app\mall\model\db\MallOrderRefund;
use app\mall\model\db\MerchantStore;
use app\mall\model\service\MallGoodsService as MallGoodsService;
use app\mall\model\service\MallGoodsSkuService as MallGoodsSkuService;
use app\mall\model\service\MallOrderService;
use app\mall\model\service\MallOrderCombineService;
use app\common\model\service\UserAdressService;
use app\mall\model\service\MerchantService;
use app\mall\model\service\third_order_deliver\DeliverApiService;
use app\merchant\model\service\MerchantStoreService;
use app\common\model\service\SingleFaceService;
use app\mall\model\service\activity\MallActivityService;
use app\common\model\service\AreaService;
use app\mall\model\service\ExpressTemplateService;
use app\mall\model\service\MallOrderRefundService;
use app\mall\model\service\CartService;
use app\mall\model\service\MallRiderService;
use app\mall\model\service\MallOrderRemindService;
use app\mall\model\service\activity\MallPrepareActService;
use app\mall\model\service\MerchantStoreMallService;
use app\mall\model\db\MallNewPeriodicPurchaseOrder;
use app\pay\model\service\PayService;
use Carbon\Carbon;
use think\helper\Arr;
use tools\Sign;
use think\facade\Db;

class OrderController extends ApiBaseController
{
    //对外接口默认秘钥
    const DEFAULT_SECRET = 'b897afbec6f584415ba515aa171ac72f';
	//订单详情接口
	public function detail(){
		if(empty($this->request->log_uid)){
			return api_output_error(1002, '当前接口需要登录');
		}
		$order_id = $this->request->param("order_id", 0, "intval");
		if(empty($order_id)){
        	return api_output_error(1001, 'not found order_id!!!');	
        }

		$MallOrderService = new MallOrderService;

		$output = [
			'confirm_self_delivery' => false,
			'status' => [
				'val' => 0,
				'goods_activity_type' => '',
				'btn_refund' => 1,
				'btn_invoice' => 0,
				'btn_comment' => 1,
				'title' => '',
				'has_refund' => 0,//是否有退款历史
				'count_down' => 0,
			],
			'log' => [
				'last_data' => [
					'status' => 0,
					'status_txt' => '',
					'title' => '',
					'date' => ''
				],
				'rider' => [
					'name' => '',
					'phone' => '',
					'status' => 0,
					'status_txt' => '',
					'date' => ''
				]
			],
			'address' => [
				'id' => 0,
				'name' => '',
				'phone' => '',
				'detail' => ''
			],
			'take_address' => [
				'name' => '',
				'phone' => '',
				'detail' => '',
				'qrcode' => ''
			],
			'order' => [
				'order_no' => '',
				'order_time' => '',
				'pay_time' => '',
				'send_time' => '',
				'express_style' => '',
				'remark' => '',
				'mer_id' => '',
				'store_id' => '',
				'store_name' => '',
				'goods_price' => '',
				'freight_price' => '',
				'express_code' => '',
				'express_num' => '',
				'express_name' => '',
				'discount' => '',
				'merchant_card' => '',
				'real_pay' => '',
				'refund_status' => 0,//0=不存在退款 1=退款申请中 2=退款申请审核完毕
				'is_invoice' => 0,
				'goods' => [],
                'clerk_remark' => ''
			],
			'activity_info' => [
				'periodic' => [
					'all_stage' => 0,
					'cur_stage' => 0,
					'data' => []
				],
				'prepare' => [
					'status' => 0,
					'count_down' => 0,
					'is_pay_end' => 0,
					'send_date' => '',
					'step' => [
						'first' => [
							'paid' => 0,
							'money' => 0
						],
						'second' => [
							'paid' => 0,
							'money' => 0
						],
					]
				]
			],
		];
		$order = $MallOrderService->getOrderDetail($order_id);
		if($this->request->log_uid != $order['order']['uid']){
			return api_output_error(1003, '您无权查看当前订单！');	
		}

		//获取支付方式
        $payService = new PayService();
        $output['order']['pay_type_txt'] = $payService->getPayTypeText($order['order']['online_pay_type'], $order['order']);

        $output['order']['clerk_remark'] = $order['order']['clerk_remark']??'';
		//判断是否允许客户自提
        $platMallConfirm = cfg('mall_confirm_self_delivery');
        $thisMer = (new MerchantService())->getByMerId($order['order']['mer_id'],'mall_confirm_self_delivery');
        $output['confirm_self_delivery'] = ($platMallConfirm == 1 && $thisMer['mall_confirm_self_delivery'] == 1);

		//组装数据
		//状态组装
		$output['status']['val'] = $order['order']['status'];
		$output['status']['goods_activity_type'] = $order['order']['goods_activity_type'];
        $re_order=(new MallOrderRefund())->getSome(['order_id'=>$order['order']['order_id'],'status'=>1],'order_no')->toArray();
		if($order['order']['status'] >= 0 && $order['order']['status'] <= 9){
			$output['status']['title'] = '等待买家付款';
			$output['status']['count_down'] = (cfg('mall_order_obligations_time')*60+$order['order']['create_time']) - time();
			if($output['status']['count_down'] <= 1 && $order['order']['goods_activity_type'] != 'prepare'){//超时取消订单
				$MallOrderService->autoCancelOrder($order_id, 51, '超时取消订单（详情页）');
				return api_output_error(1007, '请前端再次请求本接口！');
			}
		}
		elseif($order['order']['status'] >= 10 && $order['order']['status'] <= 19 && empty($re_order)){
			$output['status']['title'] = '等待卖家发货';
			if($order['order']['status'] == 11){
				$output['status']['title'] = '备货中';
			}
			if($order['order']['express_style'] == 3){
				$output['status']['title'] = '待自提';
			}
			$output['status']['count_down'] = (MallOrderService::SEND_TIME*60+$order['order']['pay_time']) - time();
		}
		elseif($order['order']['status'] >= 20 && $order['order']['status'] <= 29){
			$output['status']['title'] = '卖家已发货';
			$output['status']['count_down'] = (MallOrderService::CONFIRM_TIME*60+$order['order']['send_time']) - time();
		}
		elseif($order['order']['status'] >= 30 && $order['order']['status'] <=49){
			$output['status']['title'] = '已完成';
		}
		elseif($order['order']['status'] >= 50 && $order['order']['status'] <=59){
			$output['status']['title'] = '订单已取消';
		}
		elseif($order['order']['status'] >= 60 && $order['order']['status'] <=69){
			$output['status']['title'] = '申请售后中';
		}
		elseif(($order['order']['status'] >= 70 && $order['order']['status'] <=79) || !empty($re_order)){
           // $re_order=(new MallOrderRefund())->getSome(['order_id'=>$order['order']['order_id'],'status'=>1],'order_no')->toArray();
            if(!empty($re_order)){
                if(count($re_order)==1){
                    $order['order']['order_no']=$re_order[0]['order_no'];
                }else{
                    $bind=array();
                    foreach ($re_order as $k=>$v){
                        $bind[]=$v['order_no'];
                    }
                    $order['order']['order_no']=implode(',',$bind);
                }
            }
			$output['status']['title'] = '订单已退款';
		}
		$MerchantStoreMallService = new MerchantStoreMallService;
		$merchant_store_mall = $MerchantStoreMallService->getStoremallInfo($order['order']['store_id'], true);
		if($merchant_store_mall['e_invoice_status'] == '1' && $order['order']['money_real'] >= $merchant_store_mall['invoice_money']){
			$output['status']['btn_invoice'] = 1;//可展示开票按钮
		}
		if(!empty($order['order']['complete_time']) && cfg('mall_order_service_comment_time')*24*60*60 + $order['order']['complete_time'] <= time()){
			$output['status']['btn_comment'] = 0;//不能评论
			$output['status']['btn_refund'] = 0;//不能申请售后
		}
		if ($order['order']['status'] >= 20 && $order['order']['status'] < 30) {
			$output['status']['btn_refund'] = 0;
		}

		//轨迹组装
        $output['delivery'] = [];
		if($order['order']['express_style'] == 2&&cfg('kd100_appkey')){
			if($order['order']['express_num']){
			    if($order['order']['goods_activity_type'] != 'periodic'){
                    $output['delivery'] = (new MallOrderService())->orderDeliveryList($order_id);
                }
                $last_data = (new SingleFaceService)->getSynQuery($order['order']['express_num'], $order['order']['express_code'], $order['order']['phone']);
				if($last_data['code'] == '0'){
					$output['log']['last_data']['status'] = $last_data['data']['state'];
					$output['log']['last_data']['status_txt'] = $last_data['data']['stateMessage'];
					if(count($last_data['data']['data']) > 0){
						$output['log']['last_data']['title'] = $last_data['data']['data'][0]['context'];
						$output['log']['last_data']['date'] = $last_data['data']['data'][0]['time'];
					}
				}
				else{
					//return api_output_error(1003, $last_data['msg']);	
				}
			}
		}
		elseif($order['order']['express_style'] == 1){
			$rider = (new MallRiderService)->getRecord('order', $order_id);
			if(!empty($rider)){
			    if($order['order']['is_third_deliver']&&$rider[0]['status']>0&&$rider[0]['status']<4){
			        //第三方配送走接口获取配送员位置
                    $deliver_lnglat = (new DeliverApiService())->realTimeLocation($order['order']);
                    $rider[0]['lng'] = $deliver_lnglat['lng']?:$rider[0]['lng'];
                    $rider[0]['lat'] = $deliver_lnglat['lat']?:$rider[0]['lat'];
                }
				$output['log']['rider'] = [
					'name' => $rider[0]['rider_name'],
					'phone' => $rider[0]['rider_phone'],
					'status' => $rider[0]['status'],
					'lng' => $rider[0]['lng'],
					'lat' => $rider[0]['lat'],
                    'distance' => 0,
					'status_txt' => (new MallRiderService)->rider_status[$rider[0]['status']],
					'date' => date('Y-m-d H:i:s', $rider[0]['addtime']),
				];
				if(cfg('mall_order_deliver_phone_hide')){
				    if((time()-$order['complete_time'])>(cfg('mall_order_deliver_phone_hide')*3600*24)){
                        $output['log']['rider']['phone'] = '';
                    }
                }
			}
		}

		//收货地址
		$output['address']['id'] = $order['order']['address_id'];
		$output['address']['name'] = $order['order']['username'];
		$output['address']['phone'] = $order['order']['phone'];
		$output['address']['detail'] = $order['order']['address'];
		$output['address']['address_lng'] = $order['order']['address_lng'];
		$output['address']['address_lat'] = $order['order']['address_lat'];
        
        if(!empty($output['log']['rider']['lat']) && !empty($output['log']['rider']['lng'])){
            $output['log']['rider']['distance'] = getDistance($output['log']['rider']['lat'], $output['log']['rider']['lng'], $order['order']['address_lat'], $order['order']['address_lng']);
            if($output['log']['rider']['distance'] > 1000){
                $output['log']['rider']['distance'] = ($output['log']['rider']['distance']/1000).'km';
            }else{
                $output['log']['rider']['distance'] .= 'm';
            }
        }

		//基本信息组装
		$output['order']['order_no'] = $order['order']['order_no'];
		$output['order']['order_time'] = date('Y-m-d H:i:s',$order['order']['create_time']);
		$output['order']['pay_time'] = $order['order']['pay_time'] > 0 ? date('Y-m-d H:i:s',$order['order']['pay_time']) : '';
		$output['order']['send_time'] = $order['order']['send_time'] > 0 ? date('Y-m-d H:i:s',$order['order']['send_time']) : '';
		$output['order']['use_get_time'] = '';
		if($order['order']['express_send_time'] != ''){
			$express_send_time = explode(',', $order['order']['express_send_time']);
			$output['order']['use_get_time'] = date('Y-m-d', $express_send_time[0]).' '.date('H:i', $express_send_time[0]).'-'.date('H:i', $express_send_time[1]);
		}
		$express_style = [
            '1' => cfg('delivery_alias_name') ?? '骑手配送',
			'2' => '普通快递',
			'3' => '到店自提',
		];
		$store_info = (new MerchantStoreService)->getOne(['store_id' => $order['order']['store_id']]);
		$output['order']['express_style'] = $order['order']['express_style'];
        $output['order']['express_style_text'] = $express_style[$order['order']['express_style']] ?? '';
        $output['order']['express_overtime'] = 0;
        if($order['order']['express_style'] == 1 && $order['order']['express_send_time']){//骑手配送
            $expressSendTime = explode(',', $order['order']['express_send_time']);
            $express_send_time = date('Y-m-d',$expressSendTime[0]).' '.date('H:i',$expressSendTime[0]).'~'.date('H:i',$expressSendTime[1]);
            $express_send_time_time = date('H:i',$expressSendTime[0]).'~'.date('H:i',$expressSendTime[1]);
            $expressSendTime = date('Y-m-d H:i',end($expressSendTime));
            $output['order']['express_send_time'] = $expressSendTime;
            $time = Carbon::parse($expressSendTime);
            if(strtotime($expressSendTime)<time()){
                $output['order']['express_overtime'] = 1;
                $output['order']['express_send_time'] = $express_send_time;
            }if($time->isToday()){
                $output['order']['express_send_time'] = '今日：'. $express_send_time_time;
            }
            if($time->isTomorrow()){
                $output['order']['express_send_time'] = '明日：'. $express_send_time_time;
            }
            if($time->subDays(1)->isTomorrow()){
                $output['order']['express_send_time'] = '后天：'. $express_send_time_time;
            }
        }
		$output['order']['remark'] = $order['order']['remark'];
		$output['order']['mer_id'] = $order['order']['mer_id'];
		$output['order']['store_id'] = $order['order']['store_id'];
		$output['order']['store_name'] = $store_info['name'] ?? '';
		$output['order']['store_logo'] = replace_file_domain($store_info['logo']);
		$output['order']['long'] = $store_info['long'];
		$output['order']['lat'] = $store_info['lat'];
		$output['order']['goods_price'] = $order['order']['money_goods'];
		$output['order']['freight_price'] = get_format_number($order['order']['money_freight']);
		$output['order']['express_num'] = $order['order']['express_num'];
		$output['order']['express_code'] = $order['order']['express_code'];
		$output['order']['express_name'] = $order['order']['express_name'];
		$output['order']['discount'] = get_format_number($order['order']['discount_system_coupon'] + $order['order']['discount_merchant_coupon'] + $order['order']['discount_merchant_card'] + $order['order']['discount_system_level']);
		$output['order']['merchant_card'] = get_format_number($order['order']['money_merchant_balance'] + $order['order']['money_merchant_give_balance']);
		$output['order']['real_pay'] = $order['order']['money_real'];
		$output['order']['is_invoice'] = $order['order']['is_invoice'];
		$MallOrderRefundService = new MallOrderRefundService;
		$now_refund = $MallOrderRefundService->getRefundByOrderId($order_id);
		$now_refund_detail = [];
		if(isset($now_refund['status'])){
			switch ($now_refund['status']) {
				case '0':
					$output['order']['refund_status'] = 1;
					$now_refund_detail = array_column($MallOrderRefundService->getRefundDetailList($now_refund['refund_id']), 'num', 'detail_id');
					break;
				case '1':
					$output['order']['refund_status'] = 2;
					break;
				case '2':
					$output['order']['refund_status'] = 3;
					break;
			}
			/*if($now_refund['type'] == '1' && $output['status']['val'] >= 30){
				$output['order']['refund_status'] = 0;
			}//如果订单核销，而退款记录是待发货之前申请的，那么当前的退款记录就不展示了（就当它没有申请过退款）*/
		}

		//自提订单的信息
		if($order['order']['express_style'] == '3'){
            $phones = explode(" ", $store_info['phone']);
			$output['take_address']['name'] = $store_info['name'] ?? '';
			$output['take_address']['phone'] = $phones[0] ?? '';
			$output['take_address']['lng'] = $store_info['long'] ?? '';
			$output['take_address']['lat'] = $store_info['lat'] ?? '';
			$output['take_address']['detail'] = $store_info['adress'] ?? '';
			if($order['order']['status']>=10&&!in_array($order['order']['status'],[50,51,52])){
                $output['take_address']['qrcode'] = cfg('site_url').$MallOrderService->createQrcode($order_id, $order['order']['order_no']);
            }
		}

		//成功的退款
		$success_refund = $MallOrderRefundService->getSuccessRefundDetail(array_column($MallOrderRefundService->getSuccessRefund($order_id), 'refund_id'));

		//组装订单详细信息
		foreach ($order['detail'] as $key => $value) {
			$temp = [
				'order_detail_id' => $value['id'],
				'sku_id' => $value['sku_id'],
				'goods_id' => $value['goods_id'],
				'goods_name' => $value['name'],
				'image' => replace_file_domain($value['image']),
				'sku_str' => $value['sku_info'],
				'price' => $value['price'],
				'num' => $value['num'],
				'refund' => [
					'success' => $success_refund[$value['id']] ?? 0,
					'audit' => $now_refund_detail[$value['id']] ?? 0,
				],
				'is_gift' => $value['is_gift'],
				'more_info' => $value['forms'] ? json_decode($value['forms'], true) : []
			];
			if(empty($value['image'])){
                $goods_image=(new MallGoodsService())->getOne($value['goods_id']);
                if(!empty($goods_image)){
                    $temp['image']=replace_file_domain($goods_image['image']);
                }
            }
			if($output['status']['has_refund'] == 0 && isset($success_refund[$value['id']]) && $success_refund[$value['id']] > 0){
				$output['status']['has_refund'] = 1;
			}
			foreach ($temp['more_info'] as $k => $v) {
				if($v['type'] == 'image' && !empty($v['val'])){
					$temp['more_info'][$k]['val'] = replace_file_domain($v['val']);
				}
			}
			$output['order']['goods'][] = $temp;
		}

		//活动订单信息
		switch ($order['order']['goods_activity_type']) {
			case 'group':
				if($order['order']['status'] == 13){
					$group_endtime = (new MallActivityService)->getGroupOrderAndTime($order_id);
					$output['status']['title'] = '待成团';
					$output['status']['count_down'] = 0;
					if($group_endtime > 0 && $group_endtime > time()){
						$output['status']['count_down'] = $group_endtime - time();
					}
				}
				break;
			case 'periodic':
				$periodic = (new MallActivityService)->getPeriodicOrderDetail($order_id);
				if(isset($periodic['list'])){
					$output['activity_info']['periodic'] = [
						'all_stage' => $periodic['all_nums'],
						'cur_stage' => $periodic['nums'],
						'data' => []
					];
					if(!empty($periodic['send_time'])){
						$output['order']['send_time'] = date('Y-m-d H:i:s', $periodic['send_time']);
					}
					$no_send = 0;
					foreach ($periodic['list'] as $k => $per) {
						if(in_array($per['deliver_status'], [0,1])){
							$no_send++;
						}
                        $delivery = (new MallOrderService())->orderDeliveryList($order_id,$per['purchase_order_id']);
						$output['activity_info']['periodic']['data'][] = [
							'purchase_order_id' => $per['purchase_order_id'],
							'status' => $per['deliver_status'],
							'send_time' => $per['period_date'],
							'complete_time' => $per['arrive_time'] ?? '',
							'express_num' => $per['express_num'],
							'express_code' => $per['express_code'],
							'express_name' => $per['express_name'],
                            'delivery' => $delivery
						];
						if(in_array($per['deliver_status'], [3,4])){
							$output['order']['express_code'] = $per['express_code'];
							$output['order']['express_num'] = $per['express_num'];
							$output['order']['express_name'] = $per['express_name'];							
						}
					}
					if($no_send == 0){
						$output['status']['btn_refund'] = 0;//周期购订单，如果都发货了，那就不显示“申请售后”按钮
					}
				}
				break;
			case 'prepare':
				$prepare = (new MallActivityService)->getPrepareOrderStatus($order['detail'][0]['goods_id'], $this->request->log_uid, $order['detail'][0]['activity_id'], $order_id);
				$output['activity_info']['prepare'] = [
					'status' => 1,
					'count_down' => 0,
					'is_pay_end' => 0,
					'send_date' => '',
					'step' => [
						'first' => [
							'paid' => 0,
							'money' => 0
						],
						'second' => [
							'paid' => 0,
							'money' => 0
						]
					],
				];
				if($prepare){
					// $output['activity_info']['prepare']['count_down'] = $prepare['act_end_time'];
					$output['activity_info']['prepare']['send_date'] = date('Y-m-d H:i:s', $prepare['send_goods_date']);
					if(strpos($output['activity_info']['prepare']['send_date'], '00:00:00') !== false){
						$output['activity_info']['prepare']['send_date'] = str_replace('00:00:00', '', $output['activity_info']['prepare']['send_date']);
					}
					if($prepare['pay_status'] == 1){
						$output['activity_info']['prepare']['step']['first']['paid'] = 1;
						$output['activity_info']['prepare']['status'] = 2;		
						$nowtime = time();				
						if($prepare['start_time'] > 0){
							if($nowtime < $prepare['start_time']){
		                        $output['activity_info']['prepare']['count_down'] = $prepare['start_time'] - $nowtime;
		                    }
		                    elseif($nowtime >= $prepare['start_time'] && $nowtime < $prepare['end_time']){
		                        $output['activity_info']['prepare']['count_down'] = $prepare['end_time'] - $nowtime;
		                        $output['activity_info']['prepare']['is_pay_end'] = 1;
		                    }
		                    else{
		                    	//超时取消订单
		                    	(new MallActivityService)->getPrepareOrderRefund($order_id);
		                    	return api_output_error(1007, '请前端再次请求本接口！');
		                    }
						}
					}
					if($prepare['pay_status'] == 2){
						$output['activity_info']['prepare']['step']['first']['paid'] = 1;
						$output['activity_info']['prepare']['step']['second']['paid'] = 1;
						$output['activity_info']['prepare']['status'] = 3;						
					}
					if($prepare['pay_status'] == 3){
						if(!empty($order['order']['pre_pay_orderno'])){
							$output['activity_info']['prepare']['step']['first']['paid'] = 1;
							$output['activity_info']['prepare']['status'] = 2;		
						}
					}
					$output['activity_info']['prepare']['step']['first']['money'] = get_format_number($prepare['bargain_price']);
					$output['activity_info']['prepare']['step']['second']['money'] = get_format_number($prepare['rest_price']);
				}
				break;
			default:
				# code...
				break;
		}

		//处理小数点
		$output['order']['goods_price'] = get_format_number($output['order']['goods_price']);
		$output['order']['real_pay'] = get_format_number($output['order']['real_pay']);
		foreach ($output['order']['goods'] as $key => $value) {
			$output['order']['goods'][$key]['price'] = get_format_number($value['price']);
		}
		if($output['status']['btn_refund'] == 1 && $output['order']['express_style'] != 3){
			$output['status']['btn_invoice'] = 0;//如果有退款存在，就不能开具发票
		}
		return api_output(0, $output);
	}

	//获取骑手轨迹
	public function getRiderRecord(){
		if(empty($this->request->log_uid)){
			return api_output_error(1002, '当前接口需要登录');
		}
		$type = $this->request->param("type", 'order');
		$data_id = $this->request->param("data_id");
		if(empty($data_id)){
        	return api_output_error(1001, '参数缺失!!!');	
        }
        if($type == 'order'){
        	$order_id = $data_id;
        }
        else{
        	$perorder = (new MallNewPeriodicPurchaseOrder)->getPeriodicOrder([['id', '=', $data_id]]);
        	$order_id = $perorder['order_id'];
        }
        $MallOrderService = new MallOrderService;

		$output = [
			'last_status' => 0,
			'last_status_txt' => '',
			'arrive_time' => '',
			'rider_name' => '',
			'rider_phone' => '',
			'list' => [],
		];
		$order = $MallOrderService->getOrderDetail($order_id);
        $MallOrderService->checkOrderAuth($order,$this->request->log_uid);
		$MallRiderService = new MallRiderService;
		$rider = $MallRiderService->getRecord($type, $data_id, 'id desc');
		if(!empty($rider)){
			$output = [
				'last_status' => $rider[0]['status'],
				'last_status_txt' => $MallRiderService->rider_status[$rider[0]['status']],
				'arrive_time' => '',
				'rider_name' => $rider[0]['rider_name'],
				'rider_phone' => $rider[0]['rider_phone'],
				'lat' => Arr::first($rider)['lat'],
				'lng' => Arr::first($rider)['lng'],
			];
		}
		$arrive_time = explode(',', $order['order']['express_send_time']);
		foreach ($rider as $key => $value) {
			if($key === 0){
				$output['last_status'] = $value['status'];
				$output['last_status_txt'] = $MallRiderService->rider_status[$value['status']];
				$output['arrive_time'] = date('Y-m-d H:i', $arrive_time[0]). ' - ' . date('Y-m-d H:i', $arrive_time[1]);
				if($type != 'order'){//周期购子订单
					$output['arrive_time'] = $perorder['periodic_date'] > 0 ? date('Y-m-d H:i', $perorder['periodic_date']) : '';
				}
				$output['rider_name'] = $value['rider_name'];
				$output['rider_phone'] = $value['rider_phone'];
			}
			$output['list'][] = [
				'time' => date('H:i', $value['addtime']),
				'context' => $value['note']
			];
		}
		return api_output(0, $output);
	}

	//获取快递轨迹
	public function getExpress(){
		if(empty($this->request->log_uid)){
			return api_output_error(1002, '当前接口需要登录');
		}
		$express_code = $this->request->param("express_code");
		$express_num = $this->request->param("express_num");
		$phone = $this->request->param("phone");
		if(empty($phone) || empty($express_num) || empty($express_code)){
        	return api_output_error(1001, '参数缺失!!!');	
        }

		$output = [
			'express_name' => '',
			'express_num' => $express_num,
			'last_state' => '',
			'list' => []
		];
		$last_data = (new SingleFaceService)->getSynQuery($express_num, $express_code, $phone);
		if($last_data['code'] == '0'){
			$output['last_state'] = $last_data['data']['stateMessage'];
			$output['list'] = $last_data['data']['data'];
		}

		return api_output(0, $output);
	}

	//订单详情--去付款
	public function goPay(){
		if(empty($this->request->log_uid)){
			return api_output_error(1002, '当前接口需要登录');
		}
		$order_id = $this->request->param("order_id", "", "intval");
		$is_prepare_end = $this->request->param("is_prepare_end", 0, "intval");
        if(empty($order_id)){
        	return api_output_error(1001, 'order_id必传!');
        }

        //1、获取订单信息
        $MallOrderService = new MallOrderService;
        $order = $MallOrderService->getOrderDetail($order_id);
        $MallOrderService->checkOrderAuth($order,$this->request->log_uid);
        
        //查询店铺状态
        $merchantStoreInfo = (new MerchantStore())->where(['store_id'=>$order['order']['store_id']])->field('status,have_mall')->find();
        if(!$merchantStoreInfo){
            return api_output_error(1001, '未查询到店铺信息!');
        }
        if($merchantStoreInfo['status'] == 0){
            return api_output_error(1001, '店铺已关闭!');
        }
        if($merchantStoreInfo['have_mall'] == 0){
            return api_output_error(1001, '店铺（商城）已关闭!');
        }

        //2、创建合单信息，生成合单id
        $combine_id = (new MallOrderCombineService)->addData($this->request->log_uid, [$order_id], $order['order']['mer_id'], $order['order']['store_id'], 0, $is_prepare_end);

        if($is_prepare_end == 1){//如果是支付尾款，那需要先锁住这笔订单在15分钟内不能被计划任务取消了（修复：尾款邻近取消时间去支付，订单被计划任务取消的bug）
        	(new MallPrepareActService)->secondPayUpdateOrder($order_id);
        }

        //3、返回合单id
        return api_output(0, ['order_type' => 'mall', 'order_id' => $combine_id, 'use_merchant_balance'=>0, 'use_qiye_balance'=>0, 'use_system_balance'=>1, 'use_user_score'=>1]);
	}

	//更换收货地址
	public function changeAddress(){
		if(empty($this->request->log_uid)){
			return api_output_error(1002, '当前接口需要登录');
		}
		$order_id = $this->request->param("order_id", "", "intval");
        if(empty($order_id)){
        	return api_output_error(1001, 'order_id必传!');
        }
        $address_id = $this->request->param("address_id", "", "intval");
        if(empty($address_id)){
        	return api_output_error(1001, 'address_id必传!');
        }
        //1、获取订单信息
        $MallOrderService = new MallOrderService;
        $order = $MallOrderService->getOrderDetail($order_id);
        $MallOrderService->checkOrderAuth($order,$this->request->log_uid);

        if($order['order']['address_id'] == $address_id){
        	return api_output_error(1001, '未检测到变更地址!');	
        }

        $address = (new UserAdressService)->getAdressByAdressid($address_id);
        $areas = (new AreaService)->getNameByIds([$address['province'], $address['city'], $address['area']]);
        $store_info = (new MerchantStoreService)->getOne([['store_id','=',$order['order']['store_id']]]);

        $update = [
        	'province_id' => $address['province'],
        	'city_id' => $address['city'],
        	'area_id' => $address['area'],
        	'address' => ($areas[$address['province']] ?? ''.$areas[$address['city']] ?? ''.$areas[$address['area']] ?? '').$address['adress'],
        	'phone' => $address['phone'],
        	'username' => $address['name'],
        	'address_id' => $address_id,
        	'last_uptime' => time(),
        ];
        if($order['order']['express_style'] == '1'){//张涛提供service
        	$rider = (new MallRiderService)->getTimeList($order['order']['store_id'], $store_info['long'], $store_info['lat'], $address['longitude'], $address['latitude']);
        	if(!$rider){
        		return api_output_error(1003, '当前收货地址不支持骑手配送，请重新选择!');	
        	}
        }
        if($order['order']['activity_type'] != 'shipping' && $order['order']['freight_free'] != '1'){
	        if($order['order']['express_style'] == '1'){//张涛提供service
	        	$mall_rider_info = (new MallRiderService)->computeFee($store_info['long'], $store_info['lat'], $address['longitude'], $address['latitude'], $order['order']['express_send_time']);
	        	$update['money_freight'] = $mall_rider_info['money'];
	        	$update['freight_distance'] = $mall_rider_info['distance'];
	        }
	        elseif($order['order']['express_style'] == '2'){
	        	$ExpressTemplateService = new ExpressTemplateService;
	        	$MallGoodsService = new MallGoodsService;
	        	$goodids = [];
	        	$goodnums = [];
	        	foreach ($order['detail'] as $detail) {
	        		if($detail['is_gift'] == '0'){
		        		$goodids[] = $detail['goods_id'];
		        		$goodnums[$detail['goods_id']] = $detail['num'];
		        	}
	        	}
	        	$where = [
	        		['goods_id', 'in', $goodids]
	        	];
	        	$goods = $MallGoodsService->getSome($where, 'goods_id, fright_id, other_area_fright');
	        	$param = [];
	        	foreach ($goods as $key => $value) {
	        		$param[] = [
	        			'fright_id' => $value['fright_id'],
	        			'num' => $goodnums[$value['goods_id']],
	        			'other_area_fright' => $value['other_area_fright'],
	        		];
	        	}
	        	$update['money_freight'] = $ExpressTemplateService->computeFee($order['order']['store_id'], $param, $address['province']);
	        }
			$minx = $update['money_freight'] - $order['order']['money_freight'];//运费差价
			if($minx != '0'){
				$update['money_total'] = $order['order']['money_total'] + $minx;
				$update['money_real'] = $order['order']['money_real'] + $minx;
				$update['money_total'] < 0 && $update['money_total'] = 0;
				$update['money_real'] < 0 && $update['money_real'] = 0;
			}
	    }
	    if($MallOrderService->updateMallOrder(['order_id'=>$order_id], $update)){
	    	$MallOrderService->mallOrderlog($order_id, $order['order']['status'], '变更了收货地址！');
	    	return api_output(0, ['res'=>1]);
	    }
	    else{
	    	return api_output_error(1005, '变更失败!');
	    }
	}

	//提醒发货
	public function sendRemind(){
		if(empty($this->request->log_uid)){
			return api_output_error(1002, '当前接口需要登录');
		}
		$order_id = $this->request->param("order_id", "", "intval");
        if(empty($order_id)){
        	return api_output_error(1001, 'order_id必传!');
        }
        $MallOrderService = new MallOrderService;
        $order = $MallOrderService->getOrderDetail($order_id);
        $MallOrderService->checkOrderAuth($order,$this->request->log_uid);
        if((new MallOrderRemindService)->getTodayRemind($order['order']['store_id'], 20, $order_id) > 0){
	       	return api_output_error(1003, '今天已经提醒过店家了!');
	    }
	    else{
	    	(new MallOrderRemindService)->insertRemind($order['order']['store_id'], 20, $order_id);
	    }
        
        return api_output(0, ['res'=>1]);
	}

	//确认收货
	public function confirmGet(){
		if(empty($this->request->log_uid)){
			return api_output_error(1002, '当前接口需要登录');
		}
		$order_id = $this->request->param("order_id", "", "intval");
        if(empty($order_id)){
        	return api_output_error(1001, 'order_id必传!');
        }
        $MallOrderService = new MallOrderService;
        $order = $MallOrderService->getOrderDetail($order_id);
        $MallOrderService->checkOrderAuth($order,$this->request->log_uid);
        if($order['order']['goods_activity_type'] == 'periodic'){
        	(new MallActivityService)->updateSurePeriodic($order_id);
	    }
        elseif($order['order']['status'] < 30 || $order['order']['status'] > 40){
	        $MallOrderService->changeOrderStatus($order_id, 30, '用户确认收货');
	    }

        return api_output(0, ['res'=>1]);
	}

	//取消订单
	public function cancel(){
		if(empty($this->request->log_uid)){
			return api_output_error(1002, '当前接口需要登录');
		}
		$order_id = $this->request->param("order_id", "", "intval");
        if(empty($order_id)){
        	return api_output_error(1001, 'order_id必传!');
        }
        $MallOrderService = new MallOrderService;
        $order = $MallOrderService->getOrderDetail($order_id);
        $MallOrderService->checkOrderAuth($order,$this->request->log_uid);

        $MallOrderService->checkOrderStatus(52,0,$order_id);
        $MallOrderService->changeOrderStatus($order_id, 52, '用户取消订单');

        return api_output(0, ['res'=>1]);
	}

	//申请退款
	public function applyRefund(){
		if(empty($this->request->log_uid)){
			return api_output_error(1002, '当前接口需要登录');
		}
		$order_id = $this->request->param("order_id", 0, "intval");
		$is_again = $this->request->param("is_again", 0, "intval");
		$option = $this->request->param("option", '');
        if(empty($order_id)){
        	return api_output_error(1001, 'order_id必传!');
        }

        $MallOrderRefundService = new MallOrderRefundService;
        $MallOrderService = new MallOrderService;
        $order = $MallOrderService->getOrderDetail($order_id);
        $MallOrderService->checkOrderAuth($order,$this->request->log_uid);
        $type = 1;//这里判断订单状态来确定是什么退款
        if($order['order']['status'] < 20){
        	$type = 1;
        }
        else{
        	$type = 2;
        }
        $compute = $MallOrderRefundService->compute($order_id, $is_again, $option, $type);
		if ($compute['money_real'] < $compute['refund_money']) {
			$compute['refund_money'] = $compute['money_real'];
		}
        return api_output(0, $compute);
	}

	//申请退款(提交申请)
	public function applyRefundSubmit(){
		if(empty($this->request->log_uid)){
			return api_output_error(1002, '当前接口需要登录');
		}
		$order_id = $this->request->param("order_id", 0, "intval");
		$is_again = $this->request->param("is_again", 0, "intval");
		$refund_money = $this->request->param("refund_money");
		$image = $this->request->param("image", '');
		$reason = $this->request->param("reason", '');
		$option = $this->request->param("option", '');
        if(empty($order_id)){
        	return api_output_error(1001, 'order_id必传!');
        }
        $MallOrderRefundService = new MallOrderRefundService;
        $MallOrderService = new MallOrderService;
        $order = $MallOrderService->getOrderDetail($order_id);
        $MallOrderService->checkOrderAuth($order,$this->request->log_uid);
        $type = 1;//这里判断订单状态来确定是什么退款
        if($order['order']['status'] == 60){
            return api_output_error(1001, '已申请退款，请勿重复操作!');
        }
        if($order['order']['status'] == 70){
            return api_output_error(1001, '订单已退款，请勿重复操作!');
        }
        if($order['order']['status'] < 20){
        	$type = 1;
        }
        else{
        	$type = 2;
        }
        $compute = $MallOrderRefundService->compute($order_id, $is_again, $option, $type);

        Db::startTrans();
        try {
            if($MallOrderRefundService->addApplyRefundRcord($compute, $refund_money, $reason, $image)){
                //$MallOrderService->changeOrderStatus($order_id, 60, '用户申请售后');
                (new MallOrderRemindService)->insertRemind($order['order']['store_id'], 30, $order_id);
            }
            else{
                throw_exception('申请退款失败');
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, ['res' => 1]);
	}

	//撤销申请
	public function revokeApply(){
		if(empty($this->request->log_uid)){
			return api_output_error(1002, '当前接口需要登录');
		}
		$refund_id = $this->request->param("refund_id", 0, "intval");
		if(empty($refund_id)){
        	return api_output_error(1001, 'refund_id必传!');
        }
        $MallOrderRefundService = new MallOrderRefundService;
        if($MallOrderRefundService->updateRefund($refund_id, ['status' => 3])){
        	return api_output(0, ['res' => 1]);	
        }
        else{
        	return api_output(0, ['res' => 0]);		
        }
	}

	//再来一单
	public function onceAgain(){
		if(empty($this->request->log_uid)){
			return api_output_error(1002, '当前接口需要登录');
		}
		$order_id = $this->request->param("order_id", 0, "intval");
        if(empty($order_id)){
        	return api_output_error(1001, 'order_id必传!');
        }
        $output = [
        	'res' => 1,
        	'tips' => '',
        ];
        $MallOrderService = new MallOrderService;
        $order = $MallOrderService->getOrderDetail($order_id);
        $MallOrderService->checkOrderAuth($order,$this->request->log_uid);
        if(!empty($order['order']['goods_activity_type']) && $order['order']['goods_activity_type'] != 'limited'){
        	return api_output_error(1001, '该商品活动暂不支持加入购物车');
        }

        $CartService = new CartService;
        $MallGoodsSkuService = new MallGoodsSkuService;
        $MallGoodsService = new MallGoodsService;
        $normal = 0;
        $error = 0;
        $success = [];
        foreach ($order['detail'] as $key => $value) {
        	if($value['is_gift'] == '0'){
        		$normal++;
	        	$sku = $MallGoodsSkuService->getSkuById($value['sku_id']);//sku信息
	        	$goods = $MallGoodsService->getOne($sku['goods_id']);//商品信息        	
        		
	        	if(!$CartService->addCart($this->request->log_uid, $sku, $goods, $value['num'], $value['notes'], $value['forms'])){
	        		$error++;
	        	}
	        	else{
	        		$success[] = $value['sku_id'];
	        	}
	        }
        }
        if($error > 0 && $normal == $error){
        	$output['res'] = 2;
        	$output['tips'] = '商品已无货，暂时无法购买';
        }
        elseif($error > 0){
        	$output['res'] = 3;
        	$output['tips'] = '部分商品暂时无货，请您核对后购买';	
        }

        if(in_array($output['res'], [1, 2]) && !empty($success)){//清除选中
        	$CartService->unSelectBySkuid($this->request->log_uid, $success);
        }

        return api_output(0, $output);
	}

	//用户端确认自提
	public function confirmTake(){
		if(empty($this->request->log_uid)){
			return api_output_error(1002, '当前接口需要登录');
		}
		$order_id = $this->request->param("order_id", 0, "intval");
        if(empty($order_id)){
        	return api_output_error(1001, 'order_id必传!');
        }
        $MallOrderService = new MallOrderService;
        $order = $MallOrderService->getOrderDetail($order_id);
        $MallOrderService->checkOrderAuth($order,$this->request->log_uid);
        $MallOrderService->changeOrderStatus($order_id, 31, '用户确认自提');

        return api_output(0, ['res'=>1]);
	}

	//周期购--确认收货
	public function periodicGetGoods(){
		if(empty($this->request->log_uid)){
			return api_output_error(1002, '当前接口需要登录');
		}
		$order_id = $this->request->param("order_id", 0, "intval");
        if(empty($order_id)){
        	return api_output_error(1001, 'order_id必传!');
        }
        $MallOrderService = new MallOrderService;
        $order = $MallOrderService->getOrderDetail($order_id);
        $MallOrderService->checkOrderAuth($order,$this->request->log_uid);
        $periodic_order_id = $this->request->param("periodic_order_id", 0, "intval");
        if(empty($periodic_order_id)){
        	return api_output_error(1001, 'periodic_order_id必传!');
        }
        if((new MallActivityService)->periodicGetGoods($periodic_order_id, $order_id)){
        	return api_output(0, ['res'=>1]);
        }
        else{
        	return api_output(0, ['res'=>0]);
        }
	}

	//周期购--延期
	public function periodicDelay(){
		if(empty($this->request->log_uid)){
			return api_output_error(1002, '当前接口需要登录');
		}
		$order_id = $this->request->param("order_id", 0, "intval");
        if(empty($order_id)){
        	return api_output_error(1001, 'order_id必传!');
        }
        $MallOrderService = new MallOrderService;
        $order = $MallOrderService->getOrderDetail($order_id);
        $MallOrderService->checkOrderAuth($order,$this->request->log_uid);
        $periodic_order_id = $this->request->param("periodic_order_id", 0, "intval");
        if(empty($periodic_order_id)){
        	return api_output_error(1001, 'periodic_order_id必传!');
        }
        if((new MallActivityService)->periodicDelay($periodic_order_id, $order_id)){
        	return api_output(0, ['res'=>1]);
        }
        else{
        	return api_output(0, ['res'=>0]);
        }
	}

    /**
     * 获取商城订单（山河泉接口）
     * @return \json
     */
    public function searchOrders()
    {
        $param['store_id'] = $this->request->param('store_id', 0, 'trim');
        $param['start_time'] = $this->request->param('start_time', '', 'trim');
        $param['end_time'] = $this->request->param('end_time', '', 'trim');
        $param['status'] = $this->request->param('status', 1, 'trim');//1-全部，2-待付款，3-待发货，4-已发货，5-已完成，6-已取消，7-售后中，8-已退款
        $param['page'] = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $orderService = new MallOrderService();

        if(empty($param['store_id'])){
            return api_output_error(1002, 'store_id不能为空');
        }
        $sign = $this->request->post('sign', '', 'trim');
        $Sign = new Sign();
        $re = $Sign->check($param, $sign, self::DEFAULT_SECRET);
        if($re !== true){
            $info = $Sign->getInfo();
            fdump_sql($info, 'api_scenic_verify_sign_error');
            return api_output(2003, ['log_id' => $info['log_id']], $info['error_msg'] ?: '身份验证失败！');
        }
        try {
            $arr = $orderService->getOrderList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 修改商城订单配送状态（山河泉接口）
     * @return \json
     */
    public function editOrderStatus()
    {
        $param['order_no'] = $this->request->param('order_no', '', 'trim');
        $param['worker_name'] = $this->request->param('worker_name', '', 'trim');
        $param['worker_phone'] = $this->request->param('worker_phone', '', 'trim');
        $param['order_status'] = $this->request->param('order_status', '', 'trim');
        $param['update_time'] = $this->request->param('update_time', '', 'trim');
        $orderService = new MallOrderService();

        foreach ($param as $k=>$v){
            if(empty($param[$k])){
                return api_output_error(1002, $k.'不能为空');
            }
        }
        $sign = $this->request->post('sign', '', 'trim');
        $Sign = new Sign();
        $re = $Sign->check($param, $sign, self::DEFAULT_SECRET);
        if($re !== true){
            $info = $Sign->getInfo();
            fdump_sql($info, 'api_scenic_verify_sign_error');
            return api_output(2003, ['log_id' => $info['log_id']], $info['error_msg'] ?: '身份验证失败！');
        }
        try {
            $arr = $orderService->editOrderStatus($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
	}

	/** 
     * 手动退款
     */
    public function refundOrderManual()
    {
        $order_id = $this->request->post('order_id', 0, 'intval');//订单id
        $refund_id = $this->request->post('refund_id', 0, 'intval');//退款id
        $price_back = $this->request->post('price_back', 0, 'intval');//是否退在线支付金额,0:不退
        try {
            if(empty($order_id)){
                throw new \think\Exception('order_id不能为空');
            }
            $data = (new MallOrderService())->refundOrderManual($order_id, $refund_id, 1, 1,$price_back);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data, 'success');
    }
}