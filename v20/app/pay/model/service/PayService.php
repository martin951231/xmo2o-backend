<?php
/**
 * 支付中心统一中转service
 * add by lumin
 * 2020-05-25
 * 对外只暴露此类*****************************************
 */

namespace app\pay\model\service;

use app\common\model\db\Merchant;
use app\common\model\db\PaidOrderRecord;
use app\common\model\service\UserService;
use app\community\model\db\HouseVillage;
use app\community\model\db\PlatOrder;
use app\community\model\db\WeixinAppBind;
use app\community\model\service\NewPayService;
use app\community\model\service\PlatOrderService;
use app\group\model\service\GroupOrderService;
use app\internal_card\model\service\InternalCardService;
use app\maiyatian\service\MaiyatianRechargeService;
use app\mall\model\db\MallOrder;
use app\mall\model\service\MallOrderCombineService;
use app\pay\model\db\IcbcPayInfo;
use app\pay\model\db\IcbcPaySubInfo;
use app\pay\model\db\PayChannel;
use app\pay\model\db\PayChannelParam;
use app\pay\model\db\PayOrderInfo;
use app\pay\model\db\PayType;
use app\pay\model\service\channel\ChannelService;
use app\pay\model\service\channel\IcbcService;
use app\pay\model\service\env\EnvService;
use app\thirdRobot\model\service\ThirdRobotDeliveryOrderService;

//用于实例化各种环境  支付宝、小程序、app、H5等等...
//用户实例化各种支付通道  原生、superpay、paylinx等等...
class PayService{
	private $city_id;//城市自有支付
	private $mer_id;//商家自有支付
	private $store_id;//门店自有支付（堂扫业务具备，其他业务待定）
    private $village_id;// 社区id
    private $wxapp_own_village_id;// 物业自由小程序

    public $env = [
        'wechat' => '微信环境',
        'h5' => 'H5环境',
        'app' => 'APP环境',
        'wechat_mini' => '微信小程序环境',
        'alipay' => '支付宝环境',
        'alipay_mini' => '支付宝小程序环境',
        'scrcuCash' => '四川农信',
        'ebankpay' => '日照银行',
        'hqpay' => '环球汇通',
        'douyin_mini' => '抖音支付',
        'icbc' => '工行聚富通支付',
    ];

    public $agent = [
        'pc' => '电脑端',
        'h5' => '手机网页端',
        'wechat_h5' => '微信网页端',
        'alipay' => '支付宝网页端',
        'wechat_mini' => '微信小程序端',

        'iosapp' => '苹果APP端',
        'androidapp' => '安卓APP端',
        'scrcuCash' => '四川农信',
        'douyin_mini' => '抖音小程序',
        'ebankpay' => '日照银行',
    ];

    public $channel = [//支付通道
        'wechat' => '微信官方通道',
        'alipay' => '支付宝官方通道',
        'wftpay' => '平台低费率通道',
        'tianque' => '随行付服务商通道',
        'scrcuCash' => '四川农信通道',
        'scrcu' => '四川农信通道',
        'ebankpay' => '日照银行',
        'hqpay_wx' => '环球汇通微信通道',
        'hqpay_al' => '环球汇通支付宝通道',
        'farmersbankpay' => '仪征农商行通道',
        'douyin' => '抖音官方通道',
        'chinaums' => '银联',
        'lakala' => '拉卡拉通道',
	    'icbc' => '工行支付通道'
    ];

	public $channelType = [
		0 => 'text',//文本框
		1 => 'switch',//开关
		2 => 'richtext',//富文本
		3 => 'file',//上传文件
		4 => 'select',//下拉框
	];
    /**
     * business商业
     * village 社区
    **/
    public $businessTypeArr = [
        'dining'=>'business',
        'group'=>'business',
        'package_room'=>'village',
        'package'=>'village',
        'village_pay'=>'village',
        'mall'=>'business',
        'shop'=>'business',
        'house_meter'=>'village',
        'reward'=>'business',
        'pile'=>'village',
        'village_new_pay'=>'village',
        'employee_card'=>'business',
        'life_tools_competition_join'=>'business',
        'life_tools_appoint_join'=>'business',
        'lifetools'=>'business',
        'lifetoolscard'=>'business',
        'scanpay'=>'village',
    ];
	public function __construct($city_id = 0, $mer_id = 0, $store_id = 0, $village_id = 0, $wxapp_own_village_id =0){
		$this->city_id 		        = $city_id;
		$this->mer_id 		        = $mer_id;
		$this->store_id		        = $store_id;
        $this->village_id           = $village_id;
        $this->wxapp_own_village_id = $wxapp_own_village_id;
	}

	/**
	 * 获取当前环境下的系统支付方式
	 * @param int $city_id 城市ID
	 * @param int $mer_id 商家ID
	 * @param int $store_id 城市ID
	 * @return Array 返回符合条件的支付方式
	 */
	public function payTypeList(){

		$env = new EnvService($this->city_id, $this->mer_id, $this->store_id, $this->village_id, $this->wxapp_own_village_id);
		$envService = $env->getEnvService();
		if($envService !== null){
			$list = $envService->payTypeList();
		}
		else{
			$list = [];
		}

		return $this->dealPaytypes($list);
	}

	/**
	 * 支付动作
	 * @param  [string] 	$business 支付业务 shop、mall、dining...
	 * @param  [int] 		$business_order_id 支付业务对应订单ID
	 * @param  [string] 	$pay_type 支付类型 alipay、wechat、...
	 * @param  [float] 		$money    支付金额  元为单位
	 * @param  [array] 		$pay_info [
	 *                             current_score_use:积分使用数量
	 *                             current_score_deducte:积分抵扣金额
	 *                             current_system_balance:平台余额使用金额
	 *                             current_merchant_balance:商家会员卡余额使用金额
	 *                             current_qiye_balance:企业预存款使用金额
	 * ]
	 * @param  [array] 	    $extra_cfg    支付额外需要传入的信息  如：微信支付时，需要传入openid
	 * @throws \think\Exception
	 * @return [array]      [
	 *         		'type' => '',//枚举值 redirect=跳转 jssdk=微信内微信支付  sdk=原生sdk拉起(app、小程序)
	 *         		'info' => '',//返回信息，根据type不同而不同
	 *         		'orderid' => '',//支付单号（用来查询订单状态）
	 *         ]
	 * ]
	 */
	public function pay($business, $business_order_id, $pay_type = '', $money = 0, $pay_info = [], $extra_cfg = [], $rate = 1){
		if(empty($business) || empty($business_order_id)){
            fdump_api(['business' => $business,'business_order_id' => $business_order_id,'pay_type' => $pay_type,'money' => $money,'pay_info' => $pay_info,'extra_cfg' => $extra_cfg,'rate' => $rate,'msg' => '必填参数错误!'],'errPay/payServicePayLog');
			throw new \think\Exception("必填参数错误！");
		}
        $extra_cfg['pay_type']   = $pay_type;
        $extra_cfg['order_type'] = $business;
        if (($pay_type == 'hqpay_wx' || $pay_type == 'hqpay_al')) {
            $pay_type = 'hqpay';
        }
        $is_mobile_pay = 0;
        if(in_array(request()->agent,array('iosapp','androidapp'))){
            $is_mobile_pay = 2;
        }elseif(in_array(request()->agent,array('wechat_h5','h5'))){
            $is_mobile_pay = 1;
        }elseif(request()->agent=='wechat_mini'){
            $is_mobile_pay = 3;
        }
        $extra_cfg['is_mobile_pay'] = $is_mobile_pay;
		//1、生成支付订单号
        $order_no = $this->createOrderNo($pay_type,$business_order_id);
		$extra_cfg['business_order_sn'] = $extra_cfg['business_order_sn'] ?: $order_no;
		//2、获取支付通道参数
		$paid = 0;
        $s1=microtime(true);
        $orderRecordId=0;
        $paidOrderRecordDb = new PaidOrderRecord();
		if($pay_type && $money > 0){
			$env = new EnvService($this->city_id, $this->mer_id, $this->store_id, $this->village_id, $this->wxapp_own_village_id);
			$envService = $env->getEnvService();
			$channel = $envService->getChannel($pay_type,$business);

            fdump_api([
                'channel'   => $channel,
                'pay_type'  => $pay_type,
                'business'  => $business,
                'money'     => $money,
                'pay_info'  => $pay_info,
                'extra_cfg' => $extra_cfg,
                'rate'      => $rate,
                'msg'       => '支付通道配置!'
            ], 'pay/payServicePayLog');

			if(empty($channel) || empty($channel['config'])){
                fdump_api(['channel' => $channel,'pay_type' => $pay_type,'business' => $business,'money' => $money,'pay_info' => $pay_info,'extra_cfg' => $extra_cfg,'rate' => $rate,'msg' => '支付通道未配置!'],'errPay/payServicePayLog');
                throw new \think\Exception("支付通道未配置！");
			}

			//如果是商家自有小程序openid和wxapp_openid需要重置
			$uid = request()->log_uid;
			if ($pay_type == 'wechat' && $channel['is_own'] ==1 && ($channel['channel'] != 'chinaums' && $channel['channel'] != 'leshua') && $this->mer_id > 0 && $uid > 0) {
                if(request()->agent == 'wechat_h5'){
                    $bindUser = (new \app\community\model\db\WeixinBindUser())->getFind([['mer_id', '=', $this->mer_id], ['uid', '=', $uid], ['openid', '<>', '']]);
                }else{
                    $bindUser = (new \app\community\model\db\WeixinBindUser())->getFind([['mer_id', '=', $this->mer_id], ['uid', '=', $uid], ['wxapp_openid', '<>', '']]);
                }

				if (empty($bindUser)) {
                    if (request()->agent == 'wechat_h5') {
                        $nowUser = \think\facade\Db::name('user')->where('uid', $uid)->where('status', 1)->find();
                        $payUrl = get_base_url('/pages/pay/check?order_id=' . $business_order_id . '&order_type=' . $business);
                        $merchantBind = \think\facade\Db::name('weixin_bind')->where('mer_id', $this->mer_id)->where('type', 0)->find();
                        $authUrl = cfg('site_url') . '/wap.php?c=Packapp&a=merchant_authorizer&authorizer_appid=' . $merchantBind['authorizer_appid'] . '&mer_id=' . $this->mer_id . '&plat_openid=' . $nowUser['openid'] . '&refer=' . urlencode($payUrl);
                        return [
                            'type' => 'redirect',
                            'info' => $authUrl
                        ];
                    }
					throw new \think\Exception("获取用户信息失败，请个人中心重新登录重试！");
				}
				$extra_cfg['openid'] = $bindUser['openid'];
				$extra_cfg['wxapp_openid'] = $bindUser['wxapp_openid'];
			}

			if(!empty($extra_cfg)){
				$channel['config'] = array_merge($channel['config'], $extra_cfg);
			}
            $extra_cfg['sub_orders'] = [];
            $extra_cfg['pay_order_no'] = $order_no;//支付单号
            if($pay_type == 'icbc') {
                $extra_cfg['pay_icbc_merid'] = $channel['config']['pay_icbc_merid'];
                $extra_cfg['village_id'] = $this->village_id;
                $extra_cfg['sub_orders'] = $this->getSubOrders($business, $business_order_id, $money*$rate, $extra_cfg);
            }
            $pay_type != 'hqpay' && $channel['config']['pay_type'] = $pay_type;
            $s = microtime(true);
			//new 支付通道 调用第三方支付
			$channel_obj = ChannelService::getChannelService($channel['channel'], request()->agent, $channel['config']);
            fdump_api([$channel, $channel_obj],'$channel_obj');
			$return = $channel_obj->pay($order_no, get_format_number($money*$rate), $extra_cfg);
            //跳转微信小程序
            if (isset($return['redirect_mini']) && $return['redirect_mini'] == 1) {
                $return['path'] = '/pages/pay/check?order_id=' . $business_order_id . '&order_type=' . $business;
                return $return;
            }
            fdump_api($return,'$return');
            $n = microtime(true);
            fdump_api(['$channel_obj->pay花费时长',$n-$s,$s,$n],'microtime_0706',1);
		}
		else{
			//如果在线支付金额为0，那么直接调after_pay
			$extra = [
				'paid' => 1,
				'paid_money'  => 0,
				'paid_time' => 0,
				'paid_type' => '',
				'paid_orderid' => $order_no,
				'is_own' => 0,
				'current_score_use'		=> isset($pay_info['current_score_use_ed']) ? $pay_info['current_score_use_ed'] : 0,
				'current_score_deducte'	=> isset($pay_info['current_score_deducte_ed']) ? $pay_info['current_score_deducte_ed'] : 0,
				'current_system_balance'=> isset($pay_info['current_system_balance']) ? $pay_info['current_system_balance'] : 0,
                'current_village_balance'=> isset($pay_info['current_village_balance']) ? $pay_info['current_village_balance'] : 0,
                'current_village_hot_water_balance'=> isset($pay_info['current_village_hot_water_balance']) ? $pay_info['current_village_hot_water_balance'] : 0,
                'current_village_cold_water_balance'=> isset($pay_info['current_village_cold_water_balance']) ? $pay_info['current_village_cold_water_balance'] : 0,
                'current_village_electric_balance'=> isset($pay_info['current_village_electric_balance']) ? $pay_info['current_village_electric_balance'] : 0,
                'current_merchant_balance'	=> isset($pay_info['current_merchant_balance']) ? $pay_info['current_merchant_balance'] : 0,
				'current_merchant_give_balance'	=> isset($pay_info['current_merchant_give_balance']) ? $pay_info['current_merchant_give_balance'] : 0,
				'current_qiye_balance'	=> isset($pay_info['current_qiye_balance']) ? $pay_info['current_qiye_balance'] : 0,
				'current_employee_balance'	=> isset($pay_info['current_employee_balance']) ? $pay_info['current_employee_balance'] : 0,
				'current_employee_score_deducte'	=> isset($pay_info['current_employee_score_deducte']) ? $pay_info['current_employee_score_deducte'] : 0,
				'current_internal_card_balance'	=> $pay_info['current_internal_card_balance'] ?? 0,
                'current_internal_card_ids'	=> $pay_info['current_internal_card_ids'] ?? '',
                'employee_card_user_id'	=> $pay_info['employee_card_user_id'] ?? 0,
                'is_mobile_pay' => $is_mobile_pay,

			];
            $uid = request()->log_uid;
            $orderRecordArr = array('source_from' => 0);
            $business_type = isset($this->businessTypeArr[$business]) ? $this->businessTypeArr[$business]:'';
            if ($business_type == 'business') {
                $orderRecordArr['source_from'] = 2;
            } else if ($business_type == 'village') {
                $orderRecordArr['source_from'] = 1;
            }
            $orderRecordArr['business_type'] = $business;
            $orderRecordArr['uid']=$uid>0 ? $uid:0;
            $orderRecordArr['business_order_id'] = $business_order_id;
            $orderRecordArr['pay_order_no'] = $order_no;
            $orderRecordArr['pay_order_info_id'] = 0;
            $orderRecordArr['third_transaction_no'] = '';
            $orderRecordArr['is_own'] = 1;
            $orderRecordArr['pay_type'] = 'balance';
            $balance_money = 0;
            $pay_type_from_arr=array();
            if (isset($pay_info['current_system_balance']) && $pay_info['current_system_balance'] > 0) {
                $balance_money += $pay_info['current_system_balance'];
                $pay_type_from_arr[]='system_balance';
            }
            if (isset($pay_info['current_village_balance']) && $pay_info['current_village_balance'] > 0) {
                $balance_money += $pay_info['current_village_balance'];
                $pay_type_from_arr[]='village_balance';
            }
            if (isset($pay_info['current_merchant_balance']) && $pay_info['current_merchant_balance'] > 0) {
                $balance_money += $pay_info['current_merchant_balance'];
                $pay_type_from_arr[]='merchant_balance';
            }
            if (isset($pay_info['current_qiye_balance']) && $pay_info['current_qiye_balance'] > 0) {
                $balance_money += $pay_info['current_qiye_balance'];
                $pay_type_from_arr[]='qiye_balance';
            }
            if (isset($pay_info['current_employee_balance']) && $pay_info['current_employee_balance'] > 0) {
                $balance_money += $pay_info['current_employee_balance'];
                $pay_type_from_arr[]='employee_balance';
            }
            if (isset($pay_info['current_village_hot_water_balance']) && $pay_info['current_village_hot_water_balance'] > 0) {
                $balance_money += $pay_info['current_village_hot_water_balance'];
                $pay_type_from_arr[]='hot_water_balance';
            }
            if (isset($pay_info['current_village_cold_water_balance']) && $pay_info['current_village_cold_water_balance'] > 0) {
                $balance_money += $pay_info['current_village_cold_water_balance'];
                $pay_type_from_arr[]='cold_water_balance';
            }
            if (isset($pay_info['current_village_electric_balance']) && $pay_info['current_village_electric_balance'] > 0) {
                $balance_money += $pay_info['current_village_electric_balance'];
                $pay_type_from_arr[]='electric_balance';
            }
            if (isset($pay_info['current_merchant_give_balance']) && $pay_info['current_merchant_give_balance'] > 0) {
                $balance_money += $pay_info['current_merchant_give_balance'];
                $pay_type_from_arr[]='merchant_give_balance';
            }
            $score_money=$extra['current_score_deducte']+$extra['current_employee_score_deducte'];
            if($score_money>0 && $balance_money<=0){
                $orderRecordArr['pay_type'] = 'score_deducte';
            }
            $orderRecordArr['balance_money'] = $balance_money;
            if($pay_type_from_arr){
                $orderRecordArr['pay_type_from']=implode(',',$pay_type_from_arr);
            }
            $orderRecordArr['pay_env'] = request()->agent;
            $orderRecordArr['pay_channel'] = isset($channel['channel']) ? $channel['channel'] : '';
            $orderRecordArr['is_online'] = 1;
            $orderRecordArr['score_money']=$score_money;
            $orderRecordArr['pay_time'] = time();
            $orderRecordArr['extra_data']=json_encode($extra,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            $orderRecordId = $paidOrderRecordDb->addOneData($orderRecordArr);
            $extra['order_record_id']=$orderRecordId;

			$this->afterPay($business, $business_order_id, $extra);
			$return['type'] = 'pay_success';
			$redirect_url = $this->getPayResultUrl($business, $business_order_id);
			$redirect_url = is_array($redirect_url) ? $redirect_url : ['redirect_url' => $redirect_url, 'direct' => 0];
			$return['info'] = $redirect_url;
			$paid = 1;
            $return['paid'] = $paid;
		}
		if(in_array($business,array('package_room')) && ($extra_cfg['uid']>0)&& $extra['current_system_balance']>0){
            //判断帐户余额,扣除余额
            $old_order_no=isset($extra_cfg['old_order_no']) ? $extra_cfg['old_order_no']:'';
            $useResult = (new UserService())->userMoney($extra_cfg['uid'], $extra['current_system_balance'], L_("物业后台缴费，订购房间套餐扣除余额，订单编号X1", array("X1" => $old_order_no)));
            fdump_api(['uid'=>$extra_cfg['uid'],'current_system_balance'=>$extra['current_system_balance'],'extra_cfg'=>$extra_cfg,'useResult'=>$useResult],'00current_system_balance',1);
        }

        $n1=microtime(true);
        fdump_api(['$pay_type花费时长',$n1-$s1,$s1,$n1],'microtime_0706',1);
		//写入支付订单信息表 pigcms_pay_order_info
		$pay_db = new PayOrderInfo;
		$money += $pay_info['current_score_deducte_ed'] + $pay_info['current_system_balance'] + $pay_info['current_merchant_balance'] + $pay_info['current_merchant_give_balance'] + $pay_info['current_qiye_balance'] + $pay_info['current_employee_balance'] + $pay_info['current_employee_score_deducte'] + $pay_info['current_internal_card_balance'];
		if($pay_type != 'hqpay'){
            $money=$money*100;
        }
		$data = [
			'business' 				        => $business,
			'business_order_id' 	        => $business_order_id,
			'orderid' 				        => $order_no,
			'addtime' 				        => date('Y-m-d H:i:s'),
			'money' 				        => $money,
			'pay_type' 				        => $pay_type,
			'env'					        => request()->agent,
			'channel' 				        => isset($channel['channel']) ? $channel['channel'] : '',
			'pay_config' 			        => isset($channel['config']) ? $channel['config'] : [],
			'is_own'				        => isset($channel['is_own']) ? $channel['is_own'] : 0,
			'paid' 					        => $paid,
			'paid_money' 			        => 0,
			'paid_time' 			        => '',
			'paid_extra' 			=> $return['outTradeNo'] ?? '',
			'current_score_use'		        => isset($pay_info['current_score_use_ed']) ? $pay_info['current_score_use_ed'] : 0,
			'current_score_deducte'	        => isset($pay_info['current_score_deducte_ed']) ? $pay_info['current_score_deducte_ed']*100 : 0,
			'current_system_balance'        => isset($pay_info['current_system_balance']) ? $pay_info['current_system_balance']*100 : 0,
            'current_village_balance'       => isset($pay_info['current_village_balance']) ? $pay_info['current_village_balance']*100 : 0,
            'current_merchant_balance'	    => isset($pay_info['current_merchant_balance']) ? $pay_info['current_merchant_balance']*100 : 0,
			'current_merchant_give_balance'	=> isset($pay_info['current_merchant_give_balance']) ? $pay_info['current_merchant_give_balance']*100 : 0,
			'current_qiye_balance'	=> isset($pay_info['current_qiye_balance']) ? $pay_info['current_qiye_balance']*100 : 0,
			'current_employee_balance'	=> isset($pay_info['current_employee_balance']) ? $pay_info['current_employee_balance']*100 : 0,
			'current_employee_score_deducte'	=> isset($pay_info['current_employee_score_deducte']) ? $pay_info['current_employee_score_deducte']*100 : 0,
            'current_internal_card_balance'	=> isset($pay_info['current_internal_card_balance']) ? $pay_info['current_internal_card_balance']*100 : 0,
            'current_internal_card_ids'	=> $pay_info['current_internal_card_ids'] ?? '',
            'employee_card_user_id'	=> $pay_info['employee_card_user_id'] ?? 0,
			'rate' 					=> $rate,
            'chinaums_merchant_already_get' => $return['merchant_get'] ?? 0,
            'chinaums_platform_already_get' => $return['platform_get'] ?? 0,
		];
        if(isset($pay_info['current_village_hot_water_balance']) && $pay_info['current_village_hot_water_balance']>0){
            $data['current_village_hot_water_balance']=$pay_info['current_village_hot_water_balance']*100;
        }
        if(isset($pay_info['current_village_cold_water_balance']) && $pay_info['current_village_cold_water_balance']>0){
            $data['current_village_cold_water_balance']=$pay_info['current_village_cold_water_balance']*100;
        }
        if(isset($pay_info['current_village_electric_balance']) && $pay_info['current_village_electric_balance']>0){
            $data['current_village_electric_balance']=$pay_info['current_village_electric_balance']*100;
        }
		if ($this->wxapp_own_village_id > 0 && intval($data['is_own']) > 0) {
            $data['own_from']    = 'property_wxapp';
            $data['own_from_id'] = $this->wxapp_own_village_id;
        }
        if ($pay_type == 'ebankpay') {
            $data['add_time'] = time();
        }
		if(isset($return['extends'])){//针对某些特殊支付通道需要存储的个性化字段
			$data = array_merge($data, $return['extends']);
		}
		if (!empty($extra_cfg['pay_type']) && ($extra_cfg['pay_type'] == 'hqpay_wx' || $extra_cfg['pay_type'] == 'hqpay_al')) {
            $data['pay_type'] = $extra_cfg['pay_type'];
        }
		$insert = $pay_db->add($data);
        if($insert>0 && $orderRecordId>0){
            $whereArr=array('id'=>$orderRecordId);
            $updateArr=array('pay_order_info_id'=>$insert);
            if($data['paid_extra']){
                $updateArr['third_transaction_no']=$data['paid_extra'];
            }
            $paidOrderRecordDb->saveOneData($whereArr,$updateArr);
        }
		$return['orderid'] = $order_no;
		return $return;
	}

    public function getSubOrders($orderType, $orderId, $money, $extraCfg): array
    {
        if($money <= 0){
            throw_exception('在线支付金额错误');
        }
        switch ($orderType) {
            case 'dining':
                $business = new \app\foodshop\model\service\order\DiningOrderPayService;
                break;
            case 'group':
                $business = new \app\group\model\service\order\GroupOrderService;
                break;
            case 'mall':
                return (new MallOrderCombineService())->getSubOrders($orderId, $money, $extraCfg);
            case 'shop':
                $business = new \app\shop\model\service\order\ShopOrderPayService;
                break;
            case 'life_tools_appoint_join':// 门票预约报名
                $business = new \app\life_tools\model\service\appoint\LifeToolsAppointJoinOrderService();
                break;
            case 'lifetools': //体育健身
                $business = new \app\life_tools\model\service\LifeToolsOrderService();
                break;
            case 'lifetoolscard'://景区次卡
                $business = new \app\life_tools\model\service\LifeToolsCardOrderService();
                break;
            case 'village_new_pay':
                $business = new \app\community\model\service\NewPayService();
                break;
            default:
                throw_exception('当前业务（'.$orderType.'）未对接');
        }
        $order = $business->getOrderPayInfo($orderId);
        $payOrderNo = $extraCfg['pay_order_no'];

        $subOrders = [];
        //金额转成元
        $extraCfg['platform_get'] = bcdiv($extraCfg['platform_get'], 100, 2);
        $extraCfg['merchant_get'] = bcdiv($extraCfg['merchant_get'], 100, 2);
        if($extraCfg['platform_get']) {
            $subOrders[] = [
                'business_order_no' => $order['order_no'],
                'type' => 'platform',
                'order_no' => $payOrderNo.'_plat',//合作方子订单ID,确保唯一
                'sub_icbc_no' => $extraCfg['pay_icbc_merid'], //收方商户编号
                'order_title' => '平台抽成', //子商品名称
                'pay_money' => $extraCfg['platform_get'], //支付金额(元)
            ];
        }
        if($orderType == 'village_new_pay') {
            $village = HouseVillage::where('village_id', $extraCfg['village_id'])->find();
            $subOrders[] = [
                'business_order_no' => $order['order_no'],
                'type' => 'village',
                'order_no' => $payOrderNo.'_'.$village['village_id'],
                'sub_icbc_no' => $village['sub_icbc_no'],
                'mer_id' => 0,
                'order_title' => '社区收入',
                'pay_money' => $extraCfg['merchant_get'],
            ];
        }else{
            $merchant = Merchant::where('mer_id', $order['mer_id'])->find();
            $subOrders[] = [
                'business_order_no' => $order['order_no'],
                'type' => 'merchant',
                'order_no' => $payOrderNo.'_'.$merchant['mer_id'],
                'sub_icbc_no' => $merchant['sub_icbc_no'],
                'mer_id' => $merchant['mer_id'],
                'order_title' => '商家收入',
                'pay_money' => $extraCfg['merchant_get'],
            ];
        }

        return $subOrders;
    }
	/**
	 * 生成订单号（唯一）
	 * @return String 如：202005251408217989725219
	 */
	public function createOrderNo($payType = '', $orderId = ''){
        if($payType == 'farmersbankpay'){//12位订单号
            $nowtime = date("dHis");
            $orderNo = $nowtime . str_pad(substr(sprintf("%04d", $orderId),-4),4,'0',STR_PAD_LEFT);
            $info = PayOrderInfo::where('orderid', $orderNo)->field('id')->find();
            if(!empty($info)){
                $this->createOrderNo($payType, $orderId);
            }
            return $orderNo;
        }
		$order_id_main = date('YmdHis') . rand(10000000,99999999);

		$order_id_len = strlen($order_id_main);

		$order_id_sum = 0;

		for($i=0; $i<$order_id_len; $i++){
			$order_id_sum += (int)(substr($order_id_main,$i,1));
		}

		$osn = $order_id_main . str_pad((100 - $order_id_sum % 100) % 100,2,'0',STR_PAD_LEFT);
		return $osn;
	}

	/**
	 * 异步通知
	 * @param string $order_no 本系统支付单号
	 * @return [array] [
	 *         after_pay:true,//是否需要执行after_pay
	 *         business:'shop',//业务代号  shop=外卖...
	 *         business_order_id:11,//业务订单ID
	 *         extra:[//一些支付信息，给业务方记录
	 *
	 *         ]
	 * ]
	 */
	public function notice($order_no,$noticeArr=array()){
		if(empty($order_no)){
            fdump_api(['param' => $_POST,'order_no' => $order_no,'msg' => 'order_no 异常！'],'errPay/payServiceNoticeLog');
			throw new \think\Exception("order_no 异常");
		}
		//找到本系统中的支付订单
		$pay_db = new PayOrderInfo;
		$order = $pay_db->getByOrderNo($order_no);
		if(empty($order)){
            fdump_api(['param' => $_POST,'order_no' => $order_no,'order' => $order,'msg' => '没有找到支付订单'],'errPay/payServiceNoticeLog');
			throw new \think\Exception("没有找到支付订单");
		}

		if($order['paid'] == '1'){
            if ($order['business']=='village_new_pay'&&!empty($order['business_order_id'])){
                $db_plat_order = new PlatOrder();
                $plat_order = $db_plat_order->get_one(['order_id'=>$order['business_order_id'],'business_type'=>'village_new_pay'],'order_id,pay_money,pay_time,paid');
                fdump_api([$order,$plat_order],'notice_zbd_0923',1);
                if (!empty($plat_order)&&$plat_order['paid']!=1){
                    return [
                        'after_pay' => true,
                        'business' => $order['business'],
                        'business_order_id' => $order['business_order_id'],
                        'extra' => [
                            'paid' => 1,
                            'paid_money' => $order['paid_money']/100,
                            'paid_time' => $order['paid_time'],
                            'paid_type' => $order['pay_type'],
                            'paid_orderid' => $order['orderid'],
                            'is_own' => $order['is_own'],
                            'env' => $order['env'],
                            'pay_order_info_id' => $order['id'],
                            'paid_extra'=> $order['paid_extra'] ? $order['paid_extra']:'',
                        ]
                    ];
                }
            } elseif ($order['business'] == 'scanpay') {
                $db_plat_order = new PlatOrder();
                $plat_order_info = $db_plat_order->get_one(['order_id'=>$order['business_order_id'],'business_type'=>'village_new_pay'],'order_id,pay_money,pay_time,paid,business_id');
                $service_new_pay = new NewPayService();
                if($plat_order_info['pay_time']>100 && $plat_order_info['paid']==1){
                      //已支付了
                }else{
                    $service_plat_order = new PlatOrderService();
                    $plat_order = [];
                    $plat_order['pay_type'] = $order['pay_type'];
                    $plat_order['orderid'] = $order['orderid'];
                    $plat_order['third_id'] = $order['paid_extra'] ? $order['paid_extra']:'';
                    $plat_order['pay_time'] = $order['paid_time']>100 ? $order['paid_time']: time();
                    $plat_order['paid'] = 1;
                    $plat_id = $service_plat_order->savePlatOrder(['business_id'=>$plat_order_info['business_id'],'business_type '=>'village_new_pay'],$plat_order);
                }
                $res = $service_new_pay->offlineAfterPay($plat_order_info['business_id'],1,$order['id']);
                fdump_api(['msg' => '订单已经支付记录直接修改','$order' => $order, '$order_no' => $order_no, '$plat_order' => $plat_order],'pay/notice_scanpay_log',1);
            }
			return [
				'after_pay' => false,//不需要执行after_pay方法了
			];
		}
        if($noticeArr && isset($noticeArr['noticeType']) && $noticeArr['noticeType']=='constructionPay'){
            $notice=$noticeArr;
        }else {
            //实例化通道
            $channel_config = json_decode($order['pay_config'], true);
            $channel_obj = ChannelService::getChannelService($order['channel'], $order['env'], $channel_config);

            $notice = $channel_obj->notice();
            if (!$notice || (isset($notice['error']) && ($notice['error'] == 1)) || empty($notice['paid_money'])) {
                fdump_api(['param' => $_POST, 'order_no' => $order_no, 'order' => $order, 'channel_config' => $channel_config, 'notice' => $notice, 'msg' => 'notice 返回有误'], 'errPay/payServiceNoticeLog');
                $orderStat = 0;
                if ($notice && isset($notice['orderStat'])) {
                    $orderStat = '222' . $notice['orderStat'];
                }
                throw new \think\Exception("notice 返回有误", $orderStat);
            }
        }
		return $this->saveData($order, $notice);
	}
    public function notice_test($order_no){
        if(empty($order_no)){
            throw new \think\Exception("order_no 异常");
        }
        //找到本系统中的支付订单
        $pay_db = new PayOrderInfo;
        $order = $pay_db->getByOrderNo($order_no);
        if(empty($order)){

            throw new \think\Exception("没有找到支付订单");
        }
        //实例化通道
        $channel_config = json_decode($order['pay_config'], true);
        $channel_obj = ChannelService::getChannelService($order['channel'], $order['env'], $channel_config);

        $notice = $channel_obj->querytest($order_no);
        if(!$notice || empty($notice['paid_money'])){
            fdump_api(['param' => $_POST,'order_no' => $order_no,'order' => $order,'channel_config' => $channel_config,'notice' => $notice,'msg' => 'notice 返回有误'],'errPay/payServiceNoticeLog');
            throw new \think\Exception("notice 返回有误");
        }
    }
	/**
	 * 前端主动查询订单状态
	 * @param string $order_no 本系统支付单号
	 * @return [array] [
	 *         after_pay:true,//是否需要执行after_pay
	 *         business:'shop',//业务代号  shop=外卖...
	 *         business_order_id:11,//业务订单ID
	 *         extra:[//一些支付信息，给业务方记录
	 * ]
	 */
	public function query($order_no){
		if(empty($order_no)){
            fdump_api(['param' => $_POST,'order_no' => $order_no,'msg' => 'order_no 异常！'],'errPay/payServiceQueryLog');
			throw new \think\Exception("order_no 异常");
		}

		//找到本系统中的支付订单
		$pay_db = new PayOrderInfo;
		$order = $pay_db->getByOrderNo($order_no);
		if(empty($order)){
            fdump_api(['param' => $_POST,'order_no' => $order_no,'order' => $order,'msg' => '没有找到支付订单'],'errPay/payServiceQueryLog');
			throw new \think\Exception("没有找到支付订单");
		}

		if($order['paid'] == '1'){
		    if ($order['business']=='village_new_pay'&&!empty($order['business_order_id'])){
                $db_plat_order = new PlatOrder();
                $plat_order = $db_plat_order->get_one(['order_id'=>$order['business_order_id'],'business_type'=>'village_new_pay'],'order_id,pay_money,pay_time,paid');
                fdump_api([$order,$plat_order],'query_zbd_0923',1);
                if (!empty($plat_order)&&$plat_order['paid']!=1){
                    return [
                        'after_pay' => true,
                        'business' => $order['business'],
                        'business_order_id' => $order['business_order_id'],
                        'extra' => [
                            'paid' => 1,
                            'paid_money' => $order['paid_money']/100,
                            'paid_time' => $order['paid_time'],
                            'paid_type' => $order['pay_type'],
                            'paid_orderid' => $order['orderid'],
                            'is_own' => $order['is_own'],
                            'env' => $order['env'],
                            'pay_order_info_id' => $order['id'],
                            'paid_extra'=> $order['paid_extra'] ? $order['paid_extra']:'',
                        ]
                    ];
                }
            }
			return [
				'after_pay' => false,//不需要执行after_pay方法了
				'business' => $order['business'],
				'business_order_id' => $order['business_order_id'],
			];
		}

		//实例化通道
		$channel_config = json_decode($order['pay_config'], true);
		$channel_obj = ChannelService::getChannelService($order['channel'], $order['env'], $channel_config);

		$result = $channel_obj->query($order_no);
		if(!$result || empty($result['paid_money'])){
		    if ($order['channel'] == 'hqpay') {
                fdump_api(['param' => $_POST,'order_no' => $order_no,'order' => $order,'channel_config' => $channel_config,'result' => $result,'msg' => '未支付'],'errPay/payServiceNoticeLog');
                throw new \think\Exception("未支付");
            }
            fdump_api(['param' => $_POST,'order_no' => $order_no,'order' => $order,'channel_config' => $channel_config,'result' => $result,'msg' => 'query 返回有误'],'errPay/payServiceNoticeLog');
            throw new \think\Exception("query 返回有误");
		}

		return $this->saveData($order, $result);
	}


	/**
	 * 退款service
	 * @param  [string] $order_no     支付单号
	 * @param  [float] $refund_money 退款金额 不传则默认全部退款 不可以填0  (元为单位)
	 * @param  [float] $refundAry 业务退款所需参数
	 * @return [array]               [
	 *         refund_no : 退款单号
	 *         refund_money: 退款金额 （单位：元）
	 * ]
	 */
	public function refund($order_no, $refund_money = false,$refundAry=[]){
        fdump_api([
            'param' => $_POST,
            'order_no' => $order_no,
            'refund_money' => $refund_money,
            'refundAry' => $refundAry,
        ],'refund/payServiceRefundLog',1);
		if(empty($order_no)){
            fdump_api(['param' => $_POST,'order_no' => $order_no,'refund_money' => $refund_money,'msg' => 'order_no 支付单号传递异常'],'errPay/payServiceRefundLog');
			throw new \think\Exception("order_no 支付单号传递异常");
		}
		if($refund_money !== false && !$refund_money){
            fdump_api(['param' => $_POST,'order_no' => $order_no,'refund_money' => $refund_money,'msg' => 'refund_money 退款金额传递有误'],'errPay/payServiceRefundLog');
			throw new \think\Exception("refund_money 退款金额传递有误");
		}

		//找到本系统中的支付订单
		$pay_db = new PayOrderInfo;
		$order = $pay_db->getByOrderNo($order_no);
		if(empty($order)){
            fdump_api(['param' => $_POST,'order_no' => $order_no,'order' => $order,'refund_money' => $refund_money,'msg' => '没有找到支付订单'],'errPay/payServiceRefundLog');
			throw new \think\Exception("没有找到支付订单");
		}

		if($order['paid'] == '0'){
            fdump_api(['param' => $_POST,'order_no' => $order_no,'order' => $order,'refund_money' => $refund_money,'msg' => '该笔订单未支付'],'errPay/payServiceRefundLog');
			throw new \think\Exception("该笔订单未支付");
		}

		if($order['refund'] == '2'){
            fdump_api(['param' => $_POST,'order_no' => $order_no,'order' => $order,'refund_money' => $refund_money,'msg' => '该笔订单已全额退款'],'errPay/payServiceRefundLog');
			throw new \think\Exception("该笔订单已全额退款");
		}
        $refund_money = $refund_money ? ($order['channel'] == 'hqpay' ? $refund_money : intval(bcmul($refund_money,100))) : $order['paid_money'];
		if($order['refund_money'] + $refund_money > $order['paid_money']){
		    if (isset($order['paid_discount_amount']) && floatval($order['paid_discount_amount']) > 0) {
		        // 如果存在支付平台优惠 单位元 转换为分*100 需要去除优惠部分
                $buyer_pay_amount = $order['buyer_pay_amount'] * 100;
                if ($buyer_pay_amount > 0 && $refund_money > $buyer_pay_amount) {
                    // 退款金额大于去除优惠后支付金额退款实际支付金额 减去已经退款金额
                    $refund_money = $buyer_pay_amount - intval($order['refund_money']);
                    if ($refund_money <= 0) {
                        fdump_api(['param' => $_POST,'order_no' => $order_no,'order' => $order,'refund_money' => $refund_money,'msg' => '剩余退款金额不足'],'errPay/payServiceRefundLog');
                        throw new \think\Exception("剩余退款金额不足");
                    }
                } else {
                    fdump_api(['param' => $_POST,'order_no' => $order_no,'order' => $order,'refund_money' => $refund_money,'msg' => '剩余退款金额不足'],'errPay/payServiceRefundLog');
                    throw new \think\Exception("剩余退款金额不足");
                }
            } else {
                fdump_api(['param' => $_POST,'order_no' => $order_no,'order' => $order,'refund_money' => $refund_money,'msg' => '剩余退款金额不足'],'errPay/payServiceRefundLog');
                throw new \think\Exception("剩余退款金额不足");
            }
		}

		//实例化通道
		$channel_config = json_decode($order['pay_config'], true);
		if($refundAry){
            $channel_config['refund_ary'] = $refundAry;
        }

        if (isset($order['own_from']) && trim($order['own_from']) == 'property_wxapp' && isset($order['own_from_id']) && intval($order['own_from_id']) > 0) {
            $channel_pay_wxapp_appid = isset($channel_config['pay_wxapp_appid']) ? trim($channel_config['pay_wxapp_appid']) : '';
            $channel_pay_wxapp_mchid = isset($channel_config['pay_wxapp_mchid']) ? trim($channel_config['pay_wxapp_mchid']) : '';
            $channel_pay_wxapp_key = isset($channel_config['pay_wxapp_key']) ? trim($channel_config['pay_wxapp_key']) : '';
            $channel_pay_wxapp_appsecret = isset($channel_config['pay_wxapp_appsecret']) ? trim($channel_config['pay_wxapp_appsecret']) : '';
            $channel_pay_weixin_client_cert = isset($channel_config['pay_weixin_client_cert']) ? trim($channel_config['pay_weixin_client_cert']) : '';
            $channel_pay_wxapp_cert_key = isset($channel_config['pay_wxapp_cert_key']) ? trim($channel_config['pay_wxapp_cert_key']) : '';

            $db_weixin_app_bind = new WeixinAppBind();
            $whereOwnFrom = [];
            $whereOwnFrom[] = ['bind_type', '=', 1];
            $whereOwnFrom[] = ['appid', '=', $channel_pay_wxapp_appid];
            $whereOwnFrom[] = ['wxpay_merid', '=', $channel_pay_wxapp_mchid];
            $property_wxapp_info = $db_weixin_app_bind->getFind($whereOwnFrom, 'bind_type, bind_id, appid, wxpay_merid, wxpay_key, appsecret, public_key, private_key');
            $wxpay_key = isset($property_wxapp_info['wxpay_key']) ? trim($property_wxapp_info['wxpay_key']) : '';
            $appsecret = isset($property_wxapp_info['appsecret']) ? trim($property_wxapp_info['appsecret']) : '';
            $public_key = isset($property_wxapp_info['public_key']) ? trim($property_wxapp_info['public_key']) : '';
            $private_key = isset($property_wxapp_info['private_key']) ? trim($property_wxapp_info['private_key']) : '';

            if ($wxpay_key && $wxpay_key != $channel_pay_wxapp_key) {
                $channel_config['pay_wxapp_key'] = $wxpay_key;
            }
            if ($appsecret  && $appsecret != $channel_pay_wxapp_appsecret) {
                $channel_config['pay_wxapp_appsecret'] = $appsecret;
            }
            if ($public_key  && $public_key != $channel_pay_weixin_client_cert) {
                $channel_config['pay_weixin_client_cert'] = $public_key;
            }
            if ($private_key  && $private_key != $channel_pay_wxapp_cert_key) {
                $channel_config['pay_wxapp_cert_key'] = $private_key;
            }
            $channel_config['pay_wxapp_cert'] = $channel_config['pay_weixin_client_cert'];
        }
		$channel_obj = ChannelService::getChannelService($order['channel'], $order['env'], $channel_config);

        if($order['channel']=='icbc'){
            fdump_api([
                'order_no' => $order_no,
                'money' => get_format_number(($refund_money/100)*$order['rate']*100),
                'refund_money' => $refund_money,
                'channel_config'=>$channel_obj
            ], 'pay/icbc_refund', 1);
            $result = $this->icbcRefund($order, get_format_number(($refund_money/100)*$order['rate']*100));
        } else {
            fdump_sql(['order_no' => $order_no,'money' => get_format_number(($refund_money/100)*$order['rate']*100),'refund_money' => $refund_money,'channel_config'=>$channel_obj], 'payServiceRefundLog');
            $result = $channel_obj->refund($order_no, $order['paid_money'], get_format_number(($refund_money/100)*$order['rate']*100), $order['paid_extra']);
        }

        fdump_api([
            'param' => $_POST,
            'order_no' => $order_no,
            'result' => $result,
            'order' => $order,
            'money' => get_format_number(($refund_money/100)*$order['rate']*100),
            'refund_money' => $refund_money
        ],'yizheng_farmers_bank/payServiceRefundLog',1);

        if(!$result || !isset($result['refund_no'])){
            fdump_api(['param' => $_POST,'order_no' => $order_no,'result' => $result,'order' => $order,'money' => get_format_number(($refund_money/100)*$order['rate']*100),'refund_money' => $refund_money,'msg' => 'refund 返回有误'],'errPay/payServiceRefundLog',1);
            if (isset($result['msg']) && $result['msg']) {
                throw new \think\Exception($result['msg']);
            }
			throw new \think\Exception("refund 返回有误");
		}

		$save_data = [
			'refund' => $order['refund_money'] + $refund_money > $order['paid_money'] ? 2 : 1,
			'refund_money' => $order['refund_money'] + $refund_money,
			'refund_nums' => $order['refund_nums']+1,
			'refund_last_time' => date('Y-m-d H:i:s')
		];
		if($pay_db->updateById($order['id'], $save_data)){
            $paidOrderRecordDb = new PaidOrderRecord();
            $refund_money_tmp=$save_data['refund_money']/100;
            $save_record_data=array('refund_status'=>$save_data['refund'],'refund_money'=>$refund_money_tmp);
            $save_record_data['last_refund_time']=time();
            $paidOrderRecordDb->paidOrderRefundHandle(0,$order['id'],$order_no,$save_record_data);
			return [
				'refund_no' => $result['refund_no'],
				'refund_money' => $refund_money/100,
                'raw_refund_data' => $result,
			];
		}
		else{
            fdump_api(['param' => $_POST,'order_no' => $order_no,'order' => $order,'save_data' => $save_data,'msg' => '更新退款信息失败'],'errPay/payServiceRefundLog');
			throw new \think\Exception("更新退款信息失败！");
		}

	}

    /**
     * 退款service
     * @param  [string] $order_no     支付单号
     * @param  [float] $refund_money 退款金额 不传则默认全部退款 不可以填0  (元为单位)
     * @return [array]               [
     *         refund_no : 退款单号
     *         refund_money: 退款金额 （单位：元）
     * ]
     */
    public function refund_test($order_no, $refund_money = false){
        if(empty($order_no)){
            throw new \think\Exception("order_no 支付单号传递异常");
        }
        //找到本系统中的支付订单
        $pay_db = new PayOrderInfo;
        $order = $pay_db->getByOrderNo($order_no);
        if(empty($order)){
            throw new \think\Exception("没有找到支付订单");
        }

        //实例化通道
        $channel_config = json_decode($order['pay_config'], true);
        $channel_obj = ChannelService::getChannelService($order['channel'], $order['env'], $channel_config);

        $result = $channel_obj->refund($order_no, $order['paid_money'], $refund_money, $order['paid_extra']);
        if(!$result || !isset($result['refund_no'])){
            throw new \think\Exception("refund 返回有误");
        }
        return $result;
    }


	private function saveData($order, $data){
        $paid_money_tmp=$data['paid_money'];
		$data['paid_money'] = get_format_number($data['paid_money']/$order['rate']);
		if (!isset($data['paid_time'])||empty($data['paid_time'])){
            $data['paid_time']=time();
        }
        $transaction_no=isset($data['transaction_no']) && $data['transaction_no'] ? $data['transaction_no'] : '';
		$save_data = [
			'paid' => 1,
			'paid_money' => $data['paid_money'],
			'paid_time' => $data['paid_time'],
		];
        if(!empty($transaction_no)){
            $save_data['paid_extra']=$transaction_no;
        }
		$pay_db = new PayOrderInfo;
        $orderRecordId=0;
		if($pay_db->updateById($order['id'], $save_data)){
            $paidOrderRecordDb = new PaidOrderRecord();
            $whereRecordArr=array(['pay_order_info_id','=',$order['id']]);
            $paidOrderRecord = $paidOrderRecordDb->getOneData($whereRecordArr);
            if(empty($paidOrderRecord) || $paidOrderRecord->isEmpty()){
                $orderRecordArr=array('source_from'=>0);
                $business_type=$this->businessTypeArr[$order['business']];
                if($business_type=='business'){
                    $orderRecordArr['source_from']=2;
                }else if($business_type=='village'){
                    $orderRecordArr['source_from']=1;
                }
                $orderRecordArr['business_type']=$order['business'];
                $orderRecordArr['business_order_id']=$order['business_order_id'];
                $orderRecordArr['pay_order_no']=$order['orderid'];
                $orderRecordArr['pay_order_info_id']=$order['id'];
                $orderRecordArr['third_transaction_no']=$transaction_no;
                $orderRecordArr['is_own']=$order['is_own'];
                if($order['channel'] != 'hqpay'){
                    $paid_money_tmp=$paid_money_tmp/100;
                }
                $orderRecordArr['pay_money']=$paid_money_tmp;
                $orderRecordArr['pay_type']=$order['pay_type'];
                if(empty($orderRecordArr['pay_type']) && ($order['current_system_balance']>0
                        || (isset($order['current_village_balance']) && $order['current_village_balance']>0)
                        || (isset($order['current_merchant_balance']) && $order['current_merchant_balance']>0)
                        || (isset($order['current_qiye_balance']) && $order['current_qiye_balance']>0 )
                        || (isset($order['current_employee_balance']) && $order['current_employee_balance']>0)
                        || (isset($order['current_village_hot_water_balance']) && $order['current_village_hot_water_balance']>0)
                        || (isset($order['current_village_cold_water_balance']) && $order['current_village_cold_water_balance']>0)
                        || (isset($order['current_merchant_give_balance']) && $order['current_merchant_give_balance']>0)
                        || (isset($order['current_village_electric_balance']) && $order['current_village_electric_balance']>0)
                    ) ){
                    $orderRecordArr['pay_type']= 'balance';
                }
                $balance_money=0;
                $pay_type_from_arr=array();
                if(isset($order['current_system_balance']) && $order['current_system_balance']>0){
                    $balance_money+=$order['current_system_balance'];
                    $pay_type_from_arr[]='system_balance';
                }
                if(isset($order['current_village_balance']) && $order['current_village_balance']>0){
                    $balance_money+=$order['current_village_balance'];
                    $pay_type_from_arr[]='village_balance';
                }
                if(isset($order['current_merchant_balance']) && $order['current_merchant_balance']>0){
                    $balance_money+=$order['current_merchant_balance'];
                    $pay_type_from_arr[]='merchant_balance';
                }
                if(isset($order['current_qiye_balance']) && $order['current_qiye_balance']>0){
                    $balance_money+=$order['current_qiye_balance'];
                    $pay_type_from_arr[]='qiye_balance';
                }
                if(isset($order['current_employee_balance']) && $order['current_employee_balance']>0){
                    $balance_money+=$order['current_employee_balance'];
                    $pay_type_from_arr[]='employee_balance';
                }
                if(isset($order['current_village_hot_water_balance']) && $order['current_village_hot_water_balance']>0){
                    $balance_money+=$order['current_village_hot_water_balance'];
                    $pay_type_from_arr[]='hot_water_balance';
                }
                if(isset($order['current_village_cold_water_balance']) && $order['current_village_cold_water_balance']>0){
                    $balance_money+=$order['current_village_cold_water_balance'];
                    $pay_type_from_arr[]='cold_water_balance';
                }
                if(isset($order['current_village_electric_balance']) && $order['current_village_electric_balance']>0){
                    $balance_money+=$order['current_village_electric_balance'];
                    $pay_type_from_arr[]='electric_balance';
                }
                if(isset($order['current_merchant_give_balance']) && $order['current_merchant_give_balance']>0){
                    $balance_money+=$order['current_merchant_give_balance'];
                    $pay_type_from_arr[]='merchant_give_balance';
                }
                $score_money=$order['current_score_deducte']+$order['current_employee_score_deducte'];
                $balance_money = $balance_money/100;
                $score_money=$score_money/100;
                $orderRecordArr['balance_money']=$balance_money;
                if($pay_type_from_arr){
                    $orderRecordArr['pay_type_from']=implode(',',$pay_type_from_arr);
                }
                $orderRecordArr['score_money']=$score_money;
                $orderRecordArr['pay_env']=$order['env'];
                $orderRecordArr['pay_channel']=$order['channel'];
                $orderRecordArr['is_online']=1;
                $orderRecordArr['pay_time']=$data['paid_time'];
                $orderRecordId=$paidOrderRecordDb->addOneData($orderRecordArr);
            }

		    $paid_money=$data['paid_money']/100;
		    if($order['channel'] == 'hqpay'){
                $paid_money = $data['paid_money'];
            }
            $after_pay = true;
            if ($order['business'] == 'scanpay') {
                $db_plat_order = new PlatOrder();
                $plat_order_info = $db_plat_order->get_one(['order_id'=>$order['business_order_id'],'business_type'=>'village_new_pay'],'order_id,pay_money,pay_time,paid,business_id');
                $service_new_pay = new NewPayService();
                $service_plat_order = new PlatOrderService();
                $plat_order = [];
                $plat_order['pay_type'] = $order['pay_type'];
                $plat_order['orderid'] = $order['orderid'];
                $plat_order['third_id'] = $transaction_no;
                $plat_order['pay_time'] = time();
                $plat_order['paid'] = 1;
                $plat_id = $service_plat_order->savePlatOrder(['business_id'=>$plat_order_info['business_id'],'business_type '=>'village_new_pay'],$plat_order);
                $res = $service_new_pay->offlineAfterPay($plat_order_info['business_id'],1,$order['id']);
                fdump_api(['msg' => '查询订单已经支付更改相关记录','$order' => $order, '$data' => $data, '$plat_order' => $plat_order],'pay/notice_scanpay_log',1);
                $after_pay = false;
            }
			return [
				'after_pay' => $after_pay,
				'business' => $order['business'],
				'business_order_id' => $order['business_order_id'],
				'extra' => [
					'paid' => 1,
					'paid_money' => $paid_money,
					'paid_time' => $data['paid_time'],
					'paid_type' => $order['pay_type'],
					'paid_orderid' => $order['orderid'],
					'is_own' => $order['is_own'],
                    'env' => $order['env'],
                    'order_record_id'=>$orderRecordId,
                    'pay_order_info_id'=>$order['id'],
                    'paid_extra'=>$transaction_no
				]
			];
		}
		else{
            fdump_api(['param' => $_POST,'data' => $data,'order' => $order,'save_data' => $save_data,'msg' => '更新退款信息失败'],'errPay/payServiceSaveDataLog');
			throw new \think\Exception("更新订单信息失败！");
		}
	}

	/**
     * 支付成功后回调各个业务方
     * @param  [string] $order_type 业务方代号  shop=外卖 mall=商城 ....
     * @param  [int] $order_id   业务方订单ID
     * @param  [array] $extra   [
     *                          paid => 1,//1=已支付
     *                          paid_money => 1.00, //支付金额  元为单位
     *                          paid_time => 467312145,//支付时间  时间戳
     *                          paid_type => wechat,//支付方式
     *                          paid_orderid => 202006021523143182448438,//支付单号
     *                          is_own => 0,//0=非自有支付 1=商家自有 2=店铺自有 3=城市自有
     *                          current_score_use:积分使用数量
 	 *                          current_score_deducte:积分抵扣金额
 	 *                          current_system_balance:平台余额使用金额
     *                          current_merchant_balance:商家会员卡余额使用金额
     *                          current_merchant_give_balance:商家会员卡赠送余额使用金额
     *                          current_qiye_balance:企业预存款使用金额
     * ]
     */
    public function afterPay($order_type, $order_id, $extra){

        if(isset($extra['paid_orderid']) && !isset($extra['current_score_use'])){
    		$pay_db = new PayOrderInfo;
            $order_info = $pay_db->getByOrderNo($extra['paid_orderid']);
    		$extra['current_score_use'] = $order_info['current_score_use'];
    		$extra['current_score_deducte'] = $order_info['current_score_deducte']/100;
    		$extra['current_system_balance'] = $order_info['current_system_balance']/100;
            $extra['current_village_balance'] = $order_info['current_village_balance']/100;
    		$extra['current_merchant_balance'] = $order_info['current_merchant_balance']/100;
    		$extra['current_merchant_give_balance'] = $order_info['current_merchant_give_balance']/100;
    		$extra['current_qiye_balance'] = $order_info['current_qiye_balance']/100;
    		$extra['current_employee_balance'] = $order_info['current_employee_balance']/100;
    		$extra['current_employee_score_deducte'] = $order_info['current_employee_score_deducte']/100;
    		$extra['current_internal_card_balance'] = $order_info['current_internal_card_balance']/100;
    		$extra['current_internal_card_ids'] = $order_info['current_internal_card_ids'];
    		$extra['employee_card_user_id'] = $order_info['employee_card_user_id']/100;
            if(isset($order_info['current_village_hot_water_balance']) && $order_info['current_village_hot_water_balance']){
                $extra['current_village_hot_water_balance'] = $order_info['current_village_hot_water_balance']/100;
            }
            if(isset($order_info['current_village_cold_water_balance']) && $order_info['current_village_cold_water_balance']){
                $extra['current_village_cold_water_balance'] = $order_info['current_village_cold_water_balance']/100;
            }
            if(isset($order_info['current_village_electric_balance']) && $order_info['current_village_electric_balance']){
                $extra['current_village_electric_balance'] = $order_info['current_village_electric_balance']/100;
            }
            $extra['paid_extra']=$order_info['paid_extra'] ? $order_info['paid_extra']:'';
    	}

        $is_hqpay=0;
        if(isset($extra['paid_type']) && isset($extra['env'])){
            $paid_type=[
                'hqpay_wx'  =>  2,//微信
                'hqpay_al'  =>  3 //支付宝
            ];
            $env=[
                'pc'           =>  1, //电脑端
                'h5'           =>  2, //手机网页端
                'wechat_h5'    =>  3, //微信网页端
                'wechat_mini'  =>  4, //微信小程序端
                'alipay'       =>  5, //支付宝网页端
                'iosapp'       =>  6, //苹果APP端
                'androidapp'   =>  7, //安卓APP端
            ];
            if(in_array($extra['paid_type'],['hqpay_wx','hqpay_al'])){
                $is_hqpay=1;
                $extra['hqpay_type']=$extra['paid_type'];
                $extra['hqpay_env']=$extra['env'];
                $extra['hqpay_source']=($extra['paid_type'] == 'hqpay_wx') ? 'weixin' : 'alipay';
                $extra['hqpay_pay']= isset($env[$extra['env']]) ? $paid_type[$extra['paid_type']].$env[$extra['env']] : 0;
                fdump_api(['环球回调参数'.__LINE__,$order_type, $order_id, $extra],'hq_pay/afterPay',1);
            }
        }
        $extra['is_hqpay']=$is_hqpay;
        $extra['business_order_type']=$order_type;
        switch ($order_type) {
            case 'dining':
                #  这里调用餐饮的service
                $business = new \app\foodshop\model\service\order\DiningOrderPayService;
                break;
			case 'group':
				$business = new \app\group\model\service\order\GroupOrderService;
				break;
            case 'package_room': //todo 社区=>房间套餐
				$business = new \app\community\model\service\PackageRoomOrderService;
				break;
			case 'package':   //todo 社区=>功能套餐
				$business = new \app\community\model\service\PackageOrderService;
				break;
			case 'village_pay': //todo 社区=>老版支付
				$business = new \app\community\model\service\VillagePayOrderService;
				break;
			case 'mall':
				$business = new \app\mall\model\service\MallOrderCombineService;
				break;
			case 'shop':
				$business = new \app\shop\model\service\order\ShopOrderPayService;
				break;
			case 'robot':
				$business = new ThirdRobotDeliveryOrderService();
				break;
			case 'house_meter': //todo 社区=>电表
				$business = new \app\community\model\service\HouseMeterPayService;
                break;
			case 'reward':
				$business = new \app\reward\model\service\order\RewardOrderPayService;
				break;
            case 'pile':  //todo 社区=>充电桩
                $business = new \app\community\model\service\PileOrderPayService;
                break;
            case 'village_new_pay': //todo 社区=>新版物业
                $business = new \app\community\model\service\NewPayService();
                break;
			case 'employee_card':// 员工卡充值
				$business = new \app\employee\model\service\EmployeeCardOrderService();
				break;
            case 'life_tools_competition_join':// 赛事下单
                $business = new \app\life_tools\model\service\LifeToolsCompetitionJoinOrderService();
                break;
            case 'life_tools_appoint_join'://门票预约
                $business = new \app\life_tools\model\service\appoint\LifeToolsAppointJoinOrderService();
                break;
            case 'lifetools'://体育健身
                $business = new \app\life_tools\model\service\LifeToolsOrderService();
                break;
            case 'lifetoolscard'://景区次卡
                $business = new \app\life_tools\model\service\LifeToolsCardOrderService();
                break;
            case 'appoint_reward'://预约技师打赏
                $business = new \app\appoint\model\service\AppointRewardOrderService();
                break;
            case 'pile_user_charge_balance': //充电桩用户余额充值
                $business = new \app\community\model\service\PileOrderPayService();
                break;

	        case 'maiyatian_recharge': //麦芽田商家充值
		        $business = new MaiyatianRechargeService();
		        break;

            case 'building': //商场楼宇系统跳转支付中心订单
				$business = new \app\building\service\OrderService();
				break;
            case 'shopping_mall_park': //商场停车缴费
				$business = new \app\building\service\ParkOrderService();
				break;
            case 'social_activity': //活动报名
                $business = new \app\social_activity\model\service\SocialActivityOrderService();
                break;
            default:
                break;
        }
        $business->afterPay($order_id, $extra);
    }

    /**
     * 支付成功后回调各个业务方
     * @param  [string] $order_type 业务方代号  shop=外卖 mall=商城 ....
     * @param  [int] $order_id   业务方订单ID
     * @return 跳转地址
     */
    public function getPayResultUrl($order_type, $order_id, $deal=true){
        $redirect_url='';
        switch ($order_type) {
            case 'dining':
                #  这里调用餐饮的service
            	$business = new \app\foodshop\model\service\order\DiningOrderPayService;
                break;
			case 'group':
				$business = new \app\group\model\service\order\GroupOrderService;
				break;
            case 'package_room':
				$business = new \app\community\model\service\PackageRoomOrderService;
				break;
			case 'package':
				$business = new \app\community\model\service\PackageOrderService;
				break;
			case 'village_pay':
				$business = new \app\community\model\service\VillagePayOrderService;
				break;
			case 'mall':
				$business = new \app\mall\model\service\MallOrderCombineService;
				break;
			case 'shop':
				$business = new \app\shop\model\service\order\ShopOrderPayService;
				break;
			case 'robot':
				$business = new ThirdRobotDeliveryOrderService();
				break;
			case 'house_meter':
				$business = new \app\community\model\service\HouseMeterPayService;
                break;
			case 'reward':
				$business = new \app\reward\model\service\order\RewardOrderPayService;
				break;
            case 'pile':
                $business = new \app\community\model\service\PileOrderPayService;
                break;
            case 'village_new_pay':
                $business = new \app\community\model\service\NewPayService;
                break;
			case 'employee_card':// 员工卡充值
				$business = new \app\employee\model\service\EmployeeCardOrderService();
				break;
            case 'life_tools_competiti':// 赛事
            case 'life_tools_competition_join':// 赛事
                $business = new \app\life_tools\model\service\LifeToolsCompetitionJoinOrderService();
                break;
            case 'life_tools_appoint_join'://门票预约
                $business = new \app\life_tools\model\service\appoint\LifeToolsAppointJoinOrderService();
                break;
            case 'lifetools'://体育健身
                $business = new \app\life_tools\model\service\LifeToolsOrderService();
                break;
            case 'lifetoolscard'://景区次卡
                $business = new \app\life_tools\model\service\LifeToolsCardOrderService();
                break;
            case 'appoint_reward'://预约打赏技师
                $business = new \app\appoint\model\service\AppointRewardOrderService();
                break;
            case 'pile_user_charge_balance': //充电桩用户余额充值
                $business = new \app\community\model\service\PileOrderPayService();
                $redirect_url = $business->getPayResultUrl($order_id,'pile_user_charge_balance');
                break;

	        case 'maiyatian_recharge': //麦芽田商家充值
		        $business = new MaiyatianRechargeService();
		        break;
            case 'building': //商场楼宇系统跳转支付中心订单
				$business = new \app\building\service\OrderService();
				break;
            case 'shopping_mall_park': //商场停车缴费
				$business = new \app\building\service\ParkOrderService();
				break;
            case 'social_activity': //活动报名
                $business = new \app\social_activity\model\service\SocialActivityOrderService();
                break;
            default:
                break;
        }
        if(empty($redirect_url)){
            $redirect_url = $business->getPayResultUrl($order_id);
            $redirect_url = is_array($redirect_url) && $deal ? $redirect_url['redirect_url'] : $redirect_url;
        }
        return $redirect_url;
    }

    /**
     * 获取允许的在线支付方式
     * @return 二维数组
     */
    public function getAllowPaytypes($where=[]){
    	$data = (new PayType)->getSome($where,true,'id asc');
    	return $this->dealPaytypes($data->toArray());
    }

    /**
     * 自动添加商家支付配置
     * @return 二维数组
     */
    public function addPayChannelByMerId($merId){
        if(empty($merId)){
            return false;
        }

        // 添加商家支付配置
        $saveData = [
            [
                'pay_type' => 'wechat',
                'channel' => 'wechat',
                'env' => 'wechat',
                'mer_id' => $merId
            ], // 微信公众号官方通道

            [
                'pay_type' => 'wechat',
                'channel' => 'wechat',
                'env' => 'wechat_mini',
                'mer_id' => $merId
            ], // 微信小程序官方通道
        ];
        (new PayChannel())->addAll($saveData);
        return $saveData;
    }

    /**
     * 自动添加物业支付配置
     * @param $property_id
     * @return array[]|false
     */
    public function addPayChannelByPropertyId($property_id){
        if(empty($property_id)){
            return false;
        }

        // 添加商家支付配置
        $saveData = [
            [
                'pay_type'    => 'wechat',
                'channel'     => 'wechat',
                'env'         => 'wechat',
                'property_id' => $property_id,
                'switch'      => 0,
            ], // 微信公众号官方通道

            [
                'pay_type'    => 'wechat',
                'channel'     => 'wechat',
                'env'         => 'wechat_mini',
                'property_id' => $property_id,
                'switch'      => 0,
            ], // 微信小程序官方通道
        ];
        (new PayChannel())->addAll($saveData);
        return $saveData;
    }

    /**
     * 获取单个支付方式详情
     * @param string $code 支付方式标识
     */
    public function getSingleByCode($code){
    	$data = (new PayType)->getOne(['code'=>$code]);
    	$data = $this->dealPaytypes([$data]);
    	return $data ? $data[0] : [];
    }

    /**
     * 处理支付方式数据
     * @param  支付方式二维数组
     * @return 处理后的二维数组
     */
    public function dealPaytypes($data){
    	if(empty($data)) return [];
    	foreach ($data as $key => $value) {
    		$data[$key]['icon'] = cfg('site_url').$value['icon'];
    	}
    	return $data;
    }

    /**
     * 获取某个支付方式下的通道，并按环境分组返回(系统后台使用)
     * @param string $code 支付方式标识
     * @param int $mer_id 商家ID
     * @param int $store_id 门店ID
     * @param int $city_id 城市ID
     * @param array $param 额外参数
     * @return array 按环境分组返回
     */
    public function getChannelByCode($code, $mer_id = 0, $store_id = 0, $city_id = 0, $param = []){
    	if(empty($code)) return [];
    	$property_id = isset($param['property_id']) && $param['property_id'] ? $param['property_id'] : 0;
    	if ($property_id > 0) {
            $where = [
                'property_id' => $property_id,
                'pay_type'    => $code
            ];
        } else {
            $where = [
                ['mer_id'   ,'=', $mer_id],
                ['store_id','=', $store_id],
                ['city_id'  ,'=', $city_id],
                ['pay_type' ,'=', $code]
            ];
            $where[]=['property_id'  ,'<', 1];
        }
    	$all_channel = (new PayChannel)->getSome($where, true, 'id asc')->toArray();
        // 自动添加商家支付配置
    	if(empty($all_channel) && $mer_id){
    	    $this->addPayChannelByMerId($mer_id);
            $all_channel = (new PayChannel)->getSome($where, true, 'id asc')->toArray();
        }
        // 自动添加物业支付配置
        if(empty($all_channel) && $property_id){
            $this->addPayChannelByPropertyId($property_id);
            $all_channel = (new PayChannel)->getSome($where, true, 'id asc')->toArray();
        }

    	$group = [];//按环境分组
    	foreach ($all_channel as $key => $value) {
    		$value['channel_name'] = $this->getChannelName($value['pay_type'], $value['channel']);
    		if(!isset($group[$value['env']])){
    			$group[$value['env']] = [];
    		}
    		$group[$value['env']][] = $value;
    	}
    	return $group;
    }

    /**
     * 获取通道名称
     */
    public function getChannelName($pay_type, $channel){
    	if($pay_type == $channel){
    		return '官方通道';
    	}
    	if('wftpay' == $channel){
    		return '平台低费率通道';
    	}
    	if('scrcuCash' == $channel){
    		return '四川农信通道';
    	}
    	if('scrcu' == $channel){
    		return '四川农信通道';
    	}
        if('ebankpay' == $channel){
            return '日照银行通道';
        }
        if('wenzhouBank' == $channel){
            return '温州银行通道';
        }
        if('chinaums' == $channel){
            return '银联通道';
        }
        if('lakala' == $channel){
            return '拉卡拉通道';
        }
    	return $channel.'通道';
    }

    /**
     * 获取通道配置参数
     * @param  [type] $cid 通道ID
     * @return [type]      配置参数数组
     */
    public function getChannelParam($cid){
    	if(empty($cid)) return [];
    	$pay_channel_param = new PayChannelParam;
    	$data = $pay_channel_param->getList(['channel_id'=>$cid]);
        $data = $data->toArray();
    	if(empty($data)){
    	    $this->addChannelParam($cid);
    	    $data = $pay_channel_param->getList(['channel_id'=>$cid]);
        }
    	return $data;
    }

    /**
     * 自动添加通道配置参数
     * @return 二维数组
     */
    public function addChannelParam($cid){
        if(empty($cid)){
            return false;
        }
        $channel = $this->getChannelInfo($cid);
		$saveData = [];
       if($channel['pay_type'] == 'wechat' && $channel['env'] == 'wechat' && $channel['channel'] == 'wechat'){
           // 微信公众号官方通道
           $saveData = [
               [
                   'channel_id' => $cid,
                   'title' => 'APPID',
                   'name' => 'pay_weixin_appid',
                   'type' => 0,
                   'orderby' => 100,
                   'tips' => '',
                   'options' => '',
               ],
               [
                   'channel_id' => $cid,
                   'title' => '商户号',
                   'name' => 'pay_weixin_mchid',
                   'type' => 0,
                   'orderby' => 90,
                   'tips' => '',
                   'options' => '',
               ],
               [
                   'channel_id' => $cid,
                   'title' => '签名秘钥',
                   'name' => 'pay_weixin_key',
                   'type' => 0,
                   'orderby' => 80,
                   'tips' => '',
                   'options' => '',
               ],
               [
                   'channel_id' => $cid,
                   'title' => 'APPSECRET',
                   'name' => 'pay_weixin_appsecret',
                   'type' => 0,
                   'orderby' => 70,
                   'tips' => '',
                   'options' => '',
               ],
               [
                   'channel_id' => $cid,
                   'title' => '支付证书',
                   'name' => 'pay_weixin_client_cert',
                   'type' => 3,
                   'orderby' => 60,
                   'tips' => '微信支付证书，在微信商家平台中可以下载！文件名一般为apiclient_cert.pem',
                   'options' => '',
               ],
               [
                   'channel_id' => $cid,
                   'title' => '支付证书秘钥',
                   'name' => 'pay_weixin_client_key',
                   'type' => 3,
                   'orderby' => 50,
                   'tips' => '微信支付证书密钥，在微信商家平台中可以下载！文件名一般为apiclient_key.pem',
                   'options' => '',
               ],
           ];

       }elseif($channel['pay_type'] == 'wechat' && $channel['env'] == 'wechat_mini' && $channel['channel'] == 'wechat'){
           // 微信小程序官方通道
           $saveData = [
               [
                   'channel_id' => $cid,
                   'title' => 'APPID',
                   'name' => 'pay_wxapp_appid',
                   'type' => 0,
                   'orderby' => 100,
                   'tips' => '',
                   'options' => '',
               ],
               [
                   'channel_id' => $cid,
                   'title' => '商户号',
                   'name' => 'pay_wxapp_mchid',
                   'type' => 0,
                   'orderby' => 90,
                   'tips' => '',
                   'options' => '',
               ],
               [
                   'channel_id' => $cid,
                   'title' => '签名秘钥',
                   'name' => 'pay_wxapp_key',
                   'type' => 0,
                   'orderby' => 80,
                   'tips' => '',
                   'options' => '',
               ],
               [
                   'channel_id' => $cid,
                   'title' => 'APPSECRET',
                   'name' => 'pay_wxapp_appsecret',
                   'type' => 0,
                   'orderby' => 70,
                   'tips' => '',
                   'options' => '',
               ],
               [
                   'channel_id' => $cid,
                   'title' => '支付证书',
                   'name' => 'pay_weixin_client_cert',
                   'type' => 3,
                   'orderby' => 60,
                   'tips' => '微信支付证书，在微信商家平台中可以下载！文件名一般为apiclient_cert.pem',
                   'options' => '',
               ],
               [
                   'channel_id' => $cid,
                   'title' => '支付证书秘钥',
                   'name' => 'pay_wxapp_cert_key',
                   'type' => 3,
                   'orderby' => 50,
                   'tips' => '微信支付证书密钥，在微信商家平台中可以下载！文件名一般为apiclient_key.pem',
                   'options' => '',
               ],
           ];
       }
	   if($saveData){
        (new PayChannelParam())->addAll($saveData);
	   }
        return $saveData;
    }

    /**
     * 获取通道基本信息
     */
    public function getChannelInfo($cid){
    	if(empty($cid)) return [];
    	return (new PayChannel)->getOne(['id'=>$cid])->toArray();
    }

    /**
     * 获取某个通道的所有兄弟通道
     */
    public function getMyBrothersChannel($cid, $switch = 1){
    	if(empty($cid)) return [];
    	$current = $this->getChannelInfo($cid);
    	$where = [
    		['pay_type', '=', $current['pay_type']],
    		['env', '=', $current['env']],
    		['id', '<>', $cid],
            ['mer_id', '=', $current['mer_id']]
    	];
    	if($switch){
    		$where[] = ['switch', '=', 1];
    	}
    	$data = (new PayChannel)->getSome($where)->toArray();
    	return $data;
    }

    /**
     * 修改通道参数
     * @param  [type] $cid  通道ID
     * @param  [type] $data 通道参数信息（必须包含open_this）
     * @return [type]       [description]
     */
    public function updateChannnelParams($cid, $data){
    	if(!isset($data['open_this'])){
    		return false;
    	}

    	$saveData = ['switch'=>($data['open_this'] ? 1 : 0)];
		if(isset($data['rate'])){
			$saveData['rate'] = $data['rate'];
		}
    	$update = (new PayChannel)->updateThis(['id'=>$cid], $saveData);
    	unset($data['open_this']);
		if(isset($data['rate'])){
			unset($data['rate']);
		}

    	foreach ($data as $key => $value) {
    		$save = [];
    		$save['value'] = $value;
    		$up = (new PayChannelParam)->updateThis(['name'=>$key,'channel_id'=>$cid], $save);
    	}
    	return true;
    }

    /**
     * 关闭通道
     * @param  [type] $cids 通道ID数组
     */
    public function shutdownChannels($cids){
    	if(empty($cids)) return ;
    	(new PayChannel)->updateThis([['id','in',$cids]], ['switch'=>0]);
    	return ;
    }

    public function getPayOrderInfo($orderid){
        return (new PayOrderInfo)->getByOrderNo($orderid)->toArray();
    }

    public function getByExtendsField($where){
        return (new PayOrderInfo)->getOne($where)->toArray();
    }

    /**
     * 获取支付单
     * @param  Array|String $orderids 支付单号，可以是一个支付单一维数组，也可以单独的一个单号
     * @return [type]           [description]
     */
    public function getPayOrders($orderids, $where = []){
    	if(empty($orderids)) return [];

    	if(is_array($orderids)){
    		$where[] = ['orderid', 'in', $orderids];
    	}else{
    		$where[] = ['orderid', '=', $orderids];
    	}
        $data = (new PayOrderInfo)->getSome($where);
        return !empty($data) ? $data->toArray() : [];
    }

    /**
     * 商家通过设备扫描用户的付款码支付
     * $auth_code  用户的付款码
     * $money      需要支付的金额
     * return bool|Array
     * 		[
     * 			status:,//1=支付成功  2=用户支付中  0=支付失败
     * 			pay_type:,//支付方式
     * 			order_no:,//支付单号（查询时候使用）
     * 			is_own:,//1=商户自有 2=门店自有支付 3=城市自有支付
     * 		]
     */
    public function scanPay($auth_code, $money){
		if(empty($auth_code)){
            fdump_api(['param' => $_POST,'auth_code' => $auth_code,'money' => $money,'msg' => '付款码必传'],'errPay/payServiceScanPayLog');
			throw new \think\Exception("付款码必传！");
		}

        if(preg_match('/^1[0-6][0-9]{16}$/',$auth_code)){
		//if(in_array(substr($auth_code, 0, 2), ['10','11','12','13','14','15'])){//只有微信的提供了规则
			$pay_type = 'wechat';
		}
		else{
			$pay_type = 'alipay';
		}
    	//判断支付方式，拿到对应的支付环境参数
		switch ($pay_type) {
			case 'wechat':
				$point_agent = 'wechat_h5';
				break;
			case 'alipay':
				$point_agent = 'h5';
				break;
			default:
                fdump_api(['param' => $_POST,'auth_code' => $auth_code,'money' => $money,'pay_type' => $pay_type,'msg' => '不支持的支付方式'],'errPay/payServiceScanPayLog');
				throw new \think\Exception("不支持的支付方式！");
		}
		if($money <= 0){
            fdump_api(['param' => $_POST,'auth_code' => $auth_code,'money' => $money,'msg' => '不支持的支付金额'],'errPay/payServiceScanPayLog');
			throw new \think\Exception("不支持的支付金额！");
		}
    	//1、生成支付订单号
		$order_no = $this->createOrderNo();

		$env = new EnvService($this->city_id, $this->mer_id, $this->store_id, $this->village_id);
		$envService = $env->getEnvService($point_agent);//就用普通H5的配置参数吧
		$channel = $envService->getChannel($pay_type,'scanpay');

		if(empty($channel) || empty($channel['config'])){
            fdump_api(['param' => $_POST,'auth_code' => $auth_code,'money' => $money,'msg' => '支付通道未配置'],'errPay/payServiceScanPayLog');
			throw new \think\Exception("支付通道未配置！");
		}
		$channel['config']['pay_type'] = $pay_type;

		//new 支付通道 调用第三方支付
		$channel_obj = ChannelService::getChannelService($channel['channel'], $point_agent, $channel['config']);
		$return = $channel_obj->scanPay($auth_code, $order_no, $money, $pay_type);

		//写入支付订单信息表 pigcms_pay_order_info
		$pay_db = new PayOrderInfo;
		$data = [
			'business' 				=> 'scanpay',
			'business_order_id' 	=> 0,
			'orderid' 				=> $order_no,
			'addtime' 				=> date('Y-m-d H:i:s'),
			'money' 				=> $money*100,
			'pay_type' 				=> $pay_type,
			'env'					=> $point_agent,
			'channel' 				=> isset($channel['channel']) ? $channel['channel'] : '',
			'pay_config' 			=> isset($channel['config']) ? $channel['config'] : [],
			'is_own'				=> isset($channel['is_own']) ? $channel['is_own'] : 0,
			'paid' 					=> 0,
			'paid_money' 			=> 0,
			'paid_time' 			=> '',
			'paid_extra' 			=> '',
			'current_score_use'		=> 0,
			'current_score_deducte'	=> 0,
			'current_system_balance'=> 0,
			'current_merchant_balance'	=> 0,
			'current_merchant_give_balance'	=> 0,
			'current_qiye_balance'	=> 0,
		];
		if($return['status'] == 1){
			$data['paid'] = 1;
			$data['paid_money'] = $channel['channel'] == 'hqpay' ? $money : $money*100;
			$data['paid_time'] = time();//date('Y-m-d H:i:s');
			$data['paid_extra'] = $return['transaction_no'] ?? '未回传';
			$insert = $pay_db->add($data);
			return [
				'status' => 1,//1=支付成功 2=用户支付中（需要调用查询service）
				'pay_type' => $pay_type,
				'order_no' => $order_no,
				'transaction_no' => $return['transaction_no'] ?? '',
				'is_own' => $channel['is_own']
			];
		}
		elseif($return['status'] == 2){
			$insert = $pay_db->add($data);
			return [
				'status' => 2,//1=支付成功 2=用户支付中（需要调用查询service）
				'pay_type' => $pay_type,
				'order_no' => $order_no,
				'is_own' => $channel['is_own']
			];
		}
		return false;
    }

    /**
     * 商家通过设备扫描用户付款码  查询接口
     * @param  [type] $order_no [description]
     * @param  [type] $pay_type [description]
     * @return [
     * 			status:,//1=支付成功  2=用户支付中  0=支付失败
     * 			pay_type:,//支付方式
     * 			order_no:,//支付单号（查询时候使用）
     * 			is_own:,//1=商户自有 2=门店自有支付 3=城市自有支付
     * 		]
     */
    public function queryScanPay($order_no, $pay_type){
    	//判断支付方式，拿到对应的支付环境参数
		switch ($pay_type) {
			case 'wechat':
				$point_agent = 'wechat_h5';
				break;
			case 'alipay':
				$point_agent = 'h5';
				break;
			default:
                fdump_api(['param' => $_POST,'order_no' => $order_no,'pay_type' => $pay_type,'msg' => '不支持的支付方式'],'errPay/payServiceQueryScanPayLog');
				throw new \think\Exception("不支持的支付方式！");
				break;
		}
		//找到本系统中的支付订单
		$pay_db = new PayOrderInfo;
		$order = $pay_db->getByOrderNo($order_no);
		if(empty($order)){
            fdump_api(['param' => $_POST,'order_no' => $order_no,'order' => $order,'msg' => '没有找到支付订单'],'errPay/payServiceQueryScanPayLog');
			throw new \think\Exception("没有找到支付订单");
		}
		//实例化通道
		$channel_config = json_decode($order['pay_config'], true);
		$channel_obj = ChannelService::getChannelService($order['channel'], $order['env'], $channel_config);

		// $env = new EnvService($this->city_id, $this->mer_id, $this->store_id);
		// $envService = $env->getEnvService($point_agent);//就用普通H5的配置参数吧
		// $channel = $envService->getChannel($pay_type);

		// if(empty($channel) || empty($channel['config'])){
		// 	throw new \think\Exception("支付通道未配置！");
		// }
		// $channel['config']['pay_type'] = $pay_type;

		// //new 支付通道 调用第三方支付
		// $channel_obj = ChannelService::getChannelService($channel['channel'], $point_agent, $channel['config']);
		$return = $channel_obj->query($order_no);
        fdump_api(['order_no' => $order_no,'pay_type' => $pay_type,'return' => $return],'after_pay/000queryScanPay',1);
		if(isset($return['status'])){
			if($return['status'] == 2){
				return [
					'status' => 2,//1=支付成功 2=用户支付中（需要调用查询service）
					'pay_type' => $pay_type,
					'order_no' => $order_no,
					'is_own' => $order['is_own'],
                    'pay_order_info_id'=>$order['id'],
				];
			}
			elseif($return['status'] == 1){
				$save_data = [
					'paid' => 1,
					'paid_money' => $order['money'],
					'paid_time' => time(),
				];
                if(isset($return['transaction_no']) && $return['transaction_no']){
                    $save_data['paid_extra']=$return['transaction_no'];
                }
				$pay_db->updateById($order['id'], $save_data);
				return [
					'status' => 1,//1=支付成功 2=用户支付中（需要调用查询service）
					'pay_type' => $pay_type,
					'order_no' => $order_no,
					'is_own' => $order['is_own'],
					'transaction_no' => $return['transaction_no'] ?? '',
                    'pay_order_info_id'=>$order['id'],
				];
			}
		}
		return [
				'status' => 0,//0=支付失败 1=支付成功 2=用户支付中（需要调用查询service）
				'pay_type' => $pay_type,
				'order_no' => $order_no,
				'is_own' => $order['is_own'],
                'pay_order_info_id'=>$order['id'],
			];
    }

    /**
     * 通过支付单号获取支付单对应的信息
     * @param  [type] $order_nos 支付单号一维数组
     * @return [type]            以支付单号为键名的二维数组
     */
    public function getPayOrderData($order_nos){
    	if(empty($order_nos)) return [];
    	$pay_db = new PayOrderInfo;
    	$where = [
    		['orderid', 'in', $order_nos]
    	];
    	$data = $pay_db->getSome($where, 'orderid, pay_type, env, channel, paid_extra')->toArray();
    	$return = [];
    	$pay_type_db = new PayType;
    	foreach ($data as $key => $value) {
    		if($value['pay_type']){
	    		$now_pay_type = $pay_type_db->getOne([['code','=', $value['pay_type']]])->toArray();
	    	}
    		$temp = [
    			'pay_type' => $value['pay_type'],
    			'pay_type_txt' => $now_pay_type['text'] ?? '余额支付',
    			'env' => $value['env'],
    			'env_txt' => $this->agent[$value['env']] ?? '未知',
    			'channel' => $value['channel'],
    			'channel_txt' => $this->channel[$value['channel']] ?? '未知',
    			'transaction_no' => $value['paid_extra']
    		];
    		$return[$value['orderid']] = $temp;
    	}
    	return $return;
    }

    /**
     * 通过业务订单ID获取支付单信息
     * @param  [type] $business          [description]
     * @param  [type] $business_order_id [description]
     * @return [type]                    [description]
     */
    public function getByBusinessOrderId($business, $business_order_id){
    	$where = [
    		['business', '=', $business],
    		['business_order_id', '=', $business_order_id],
    		['paid', '=', 1],
    	];
    	$pay_db = new PayOrderInfo;
    	$data = $pay_db->getSome($where, 'orderid, pay_type, env, channel, paid_extra');
    	if(empty($data)){
    		return [];
    	}
    	return $data->toArray();
    }

    /**
     * 获取支付方式展示文字
     * @param  string  $pay_type                    在线支付方式
     * @param  integer $money_score                 积分抵扣金额
     * @param  integer $money_system_balance        平台余额
     * @param  integer $money_merchant_balance      商家余额
     * @param  integer $money_merchant_give_balance 商家赠送余额
     * @param  integer $money_qiye_balance          企业预存款余额
     * @param  integer $employee_score_pay          员工卡积分支付
     * @param  integer $employee_balance_pay          员工卡余额支付
     * @return [type]                               [description]
     */
    public function getPayTypeText(string $payType = '', array $moneyType = []): string
    {
        $text = [];
        $type = [
            'wechat' => '微信支付',
            'alipay' => '支付宝支付',
            'ebankpay' => '日照银行',
            'douyin' => '抖音支付',
            'icbc' => '工行聚富通支付',
        ];

        if(isset($type[$payType])){
            $text[] = $type[$payType];
        }
        $balanceType = [
            'money_score' => cfg('score_name') . '抵扣',
            'money_system_balance' => '平台余额支付',
            'money_merchant_balance' => '商家会员卡余额',
            'money_merchant_give_balance' => '商家会员卡余额',
            'money_qiye_balance' => '企业预存款余额',
            'money_bank_balance' => '银行卡余额',
            'employee_score_pay' => '员工卡积分支付',
            'employee_balance_pay' => '员工卡余额支付',
            'discount_system_coupon' => '平台优惠券',
            'discount_merchant_coupon' => '商家优惠券',
            'internal_card_pay' => '一卡通支付',
        ];
        foreach ($balanceType as $key => $v){
            if(!empty($moneyType[$key])){
                if(floatval($moneyType[$key]) || $key == 'internal_card_pay'){
                    $text[] = $v;
                }
            }
        }
        return $text ? implode(' + ', $text) : '无';
    }


    /**
     * 日照银行支付-给商户清算打款-调用担保支付
     *
     */
    public function payEbankGuarantee($order = []) {
        fdump(1, 'Ebankpay_guarantee', 1);
        if (empty($order)) {
            fdump($order, 'Ebankpay_guarantee', 1);
            return false;
        }
        empty($order['env']) && $order['env'] = 'h5';
        $env = new EnvService($this->city_id, $this->mer_id, $this->store_id, $this->village_id);
        $envService = $env->getEnvService($order['env']);
        $channel = $envService->getChannel($order['pay_type'], $order['business']);
        if (empty($channel) || empty($channel['config'])) {
            fdump([$order, $channel], 'Ebankpay_guarantee', 1);
            return false;
        }
        $channel['config']['pay_type'] = $order['pay_type'];
        $channel['config']['uid'] = $order['uid'];
        $channel_obj = ChannelService::getChannelService($channel['channel'], $order['env'], $channel['config']);
        $channel_obj->EbankGuarantee($order['orderid'], $order['paid_money'] / 100);
        return true;
    }


	/**
	 * 获取业务订单
	 *
	 * @param string $payOrderid 支付订单号pigcms_pay_order_info表orderid
	 * @return array
	 * @author: zt
	 * @date: 2022/09/01
	 */
	public function getBusinessOrderInfoByPayOrderid($payOrderid)
	{
		$pay_db = new PayOrderInfo;
		$order = $pay_db->getByOrderNo($payOrderid);
		if (empty($order)) {
			throw new \think\Exception("没有找到支付订单");
		}

		switch ($order->business) {
			case 'dining':
				$business = new \app\foodshop\model\service\order\DiningOrderPayService;
				break;
			case 'group':
				$business = new \app\group\model\service\order\GroupOrderService;
				break;
			case 'package_room':
				$business = new \app\community\model\service\PackageRoomOrderService;
				break;
			case 'package':
				$business = new \app\community\model\service\PackageOrderService;
				break;
			case 'village_pay':
				$business = new \app\community\model\service\VillagePayOrderService;
				break;
			case 'mall':
				$business = new \app\mall\model\service\MallOrderCombineService;
				break;
			case 'shop':
				$business = new \app\shop\model\service\order\ShopOrderPayService;
				break;
			case 'house_meter':
				$business = new \app\community\model\service\HouseMeterPayService;
				break;
			case 'reward':
				$business = new \app\reward\model\service\order\RewardOrderPayService;
				break;
			case 'pile':
				$business = new \app\community\model\service\PileOrderPayService;
				break;
			case 'village_new_pay':
				$business = new \app\community\model\service\NewPayService();
				break;
            case 'building': //商场楼宇系统跳转支付中心订单
				$business = new \app\building\service\OrderService();
				break;
            case 'shopping_mall_park': //商场停车缴费
				$business = new \app\building\service\ParkOrderService();
				break;
            case 'social_activity': //活动报名
                $business = new \app\social_activity\model\service\SocialActivityOrderService();
                break;
			default:
				throw new \think\Exception('当前业务（' . $order->business . '）未对接');
		}
		$nowOrder = $business->getOrderPayInfo($order->business_order_id);
        $nowOrder['order_type'] = $order->business;
		return $nowOrder;
	}




    public function getQueryDetail($order_no){
        if(empty($order_no)){
            fdump_api(['param' => $_POST,'order_no' => $order_no,'msg' => 'order_no 异常！'],'errPay/payServiceQueryLog');
            return [
                'err' => 1,
                'msg' => 'order_no 异常',
            ];
        }

        //找到本系统中的支付订单
        $pay_db = new PayOrderInfo;
        $order = $pay_db->getByOrderNo($order_no);
        if(empty($order)){
            fdump_api(['param' => $_POST,'order_no' => $order_no,'order' => $order,'msg' => '没有找到支付订单'],'errPay/getQueryDetailLog');
            return [
                'err' => 1,
                'msg' => '没有找到支付订单',
            ];
        }

        //实例化通道
        $channel_config = json_decode($order['pay_config'], true);
        $channel_obj = ChannelService::getChannelService($order['channel'], $order['env'], $channel_config);

        if ('alipay' == $order['channel']) {
            $result = $channel_obj->getQueryDetail($order_no);
            $resultCode  = isset($result['resultCode'])  && $result['resultCode']  ? $result['resultCode']  : '';
            $tradeStatus = isset($result['tradeStatus']) && $result['tradeStatus'] ? $result['tradeStatus'] : '';
            if ($result && $resultCode == "10000") {
                $paid_other_json_arr = $result['result'];
                if ($paid_other_json_arr && !is_array($paid_other_json_arr)) {
                    $paid_other_json_arr = (array)$paid_other_json_arr;
                }
                // todo 处理记录相关信息
                $payOrderInfoSave      = [];
                if (isset($paid_other_json_arr['discount_amount']) && $paid_other_json_arr['discount_amount']) {
                    /** @var float paid_discount_amount 支付平台优惠金额，例如支付宝平台优惠 */
                    $paid_discount_amount = $paid_other_json_arr['discount_amount'];
                    $payOrderInfoSave['paid_discount_amount'] = $paid_discount_amount;
                    unset($paid_other_json_arr['discount_amount']);
                } else {
                    $paid_discount_amount = 0;
                }
                if (isset($paid_other_json_arr['buyer_logon_id']) && $paid_other_json_arr['buyer_logon_id']) {
                    /** @var string buyer_logon_id 买家账号 例如买家支付宝账号 */
                    $buyer_logon_id = $paid_other_json_arr['buyer_logon_id'];
                    $payOrderInfoSave['buyer_logon_id'] = $buyer_logon_id;
                    unset($paid_other_json_arr['buyer_logon_id']);
                } else {
                    $buyer_logon_id = '';
                }
                if (isset($paid_other_json_arr['buyer_pay_amount']) && $paid_other_json_arr['buyer_pay_amount']) {
                    /** @var float buyer_pay_amount 买家在支付平台实付金额，单位为元，两位小数。该金额代表该笔交易买家实际支付的金额，不包含商户折扣等金额 */
                    $buyer_pay_amount = $paid_other_json_arr['buyer_pay_amount'];
                    $payOrderInfoSave['buyer_pay_amount'] = $buyer_pay_amount;
                    unset($paid_other_json_arr['buyer_pay_amount']);
                } else {
                    $buyer_pay_amount = 0;
                }
                if (isset($paid_other_json_arr['buyer_user_id']) && $paid_other_json_arr['buyer_user_id']) {
                    /** @var string buyer_user_id 买家在支付平台的用户id 例如买家在支付宝的用户id */
                    $buyer_user_id = $paid_other_json_arr['buyer_user_id'];
                    $payOrderInfoSave['buyer_user_id'] = $buyer_user_id;
                    unset($paid_other_json_arr['buyer_user_id']);
                } else {
                    $buyer_user_id = '';
                }
                if (isset($paid_other_json_arr['fund_bill_list']) && $paid_other_json_arr['fund_bill_list']) {
                    /** @var array paid_fund_bill_list 交易支付使用的资金渠道。 只有在签约中指定需要返回资金明细，或者入参的query_options中指定时才返回该字段信息。 */
                    $paid_fund_bill_list = $paid_other_json_arr['fund_bill_list'];
                    $paid_fund_bill_list = json_encode($paid_fund_bill_list, JSON_UNESCAPED_UNICODE);
                    $payOrderInfoSave['paid_fund_bill_list'] = $paid_fund_bill_list;
                    unset($paid_other_json_arr['fund_bill_list']);
                } else {
                    $paid_fund_bill_list = '';
                }
                if (isset($paid_other_json_arr['invoice_amount']) && $paid_other_json_arr['invoice_amount']) {
                    /** @var float paid_invoice_amount 交易中用户支付的可开具发票的金额，单位为元，两位小数。该金额代表该笔交易中可以给用户开具发票的金额*/
                    $paid_invoice_amount = $paid_other_json_arr['invoice_amount'];
                    $payOrderInfoSave['paid_invoice_amount'] = $paid_invoice_amount;
                    unset($paid_other_json_arr['invoice_amount']);
                } else {
                    $paid_invoice_amount = 0;
                }
                if (isset($paid_other_json_arr['send_pay_date']) && $paid_other_json_arr['send_pay_date']) {
                    /** @var string paid_send_pay_date 本次交易打款给卖家的时间 */
                    $paid_send_pay_date = $paid_other_json_arr['send_pay_date'];
                    $payOrderInfoSave['paid_send_pay_date'] = $paid_send_pay_date;
                    unset($paid_other_json_arr['send_pay_date']);
                } else {
                    $paid_send_pay_date = '';
                }
                if (isset($paid_other_json_arr['receipt_amount']) && $paid_other_json_arr['receipt_amount']) {
                    /** @var string paid_receipt_amount 实收金额，单位为元，两位小数。该金额为本笔交易，商户账户能够实际收到的金额*/
                    $paid_receipt_amount = $paid_other_json_arr['receipt_amount'];
                    $payOrderInfoSave['paid_receipt_amount'] = $paid_receipt_amount;
                    unset($paid_other_json_arr['receipt_amount']);
                } else {
                    $paid_receipt_amount = 0;
                }
                if (!empty($paid_other_json_arr)) {
                    $paid_other_json_info = json_encode($paid_other_json_arr, JSON_UNESCAPED_UNICODE);
                    $payOrderInfoSave['paid_other_json_info'] = $paid_other_json_info;
                }
                if (!empty($payOrderInfoSave) && isset($order['id'])) {
                    $payOrderInfoSave['paid_record_time'] = time();
                    $pay_db->updateById($order['id'], $payOrderInfoSave);
                }
                return $result;
            } else {
                return [
                    'err' => 1,
                    'msg' => '获取错误'."[$resultCode][$tradeStatus]",
                ];
            }
        } else {
            $result = $channel_obj->query($order_no);
        }
        if(!$result || empty($result['paid_money'])){
            if ($order['channel'] == 'hqpay') {
                fdump_api(['param' => $_POST,'order_no' => $order_no,'order' => $order,'channel_config' => $channel_config,'result' => $result,'msg' => '未支付'],'errPay/getQueryDetailLog');
                return [
                    'err' => 1,
                    'msg' => '未支付',
                ];
            }
            fdump_api(['param' => $_POST,'order_no' => $order_no,'order' => $order,'channel_config' => $channel_config,'result' => $result,'msg' => 'query 返回有误'],'errPay/getQueryDetailLog');
            return [
                'err' => 1,
                'msg' => '返回有误',
            ];
        }
        return $result;
    }

    public function icbcRefund($order, $refundMoney)
    {
        $icbcPayInfo = IcbcPayInfo::where(['order_type' => $order['business'], 'order_no' => $order['orderid']])->find();
        if(empty($icbcPayInfo)) {
            throw_exception('支付信息不存在');
        }
        $refundMoney = bcdiv($refundMoney, 100, 2);
        $refundAll = false;
        if((float) $refundMoney == (float) $icbcPayInfo['pay_amount']) {
            $refundAll = true;
        }
        $subInfo = IcbcPaySubInfo::where(['pay_id' => $icbcPayInfo->id])->select()->toArray();
        $subRefunds = [];
        $merchant['mer_id'] = 0;
        foreach ($subInfo as $sub) {
            $subRefundMoney = $refundAll ? $sub['pay_money'] : bcmul($refundMoney/$icbcPayInfo['pay_amount'], $sub['pay_money'], 2);
            $subRefunds[] = [
                //收方商户编号
                'oV' => $sub['sub_icbc_no'],
                //子订单号
                'oI' => $sub['order_no'],
                //子退款订单号
                'rI' => $sub['order_no']."_".'r',
                //子订单退款金额
                'pA' => (string) $subRefundMoney
            ];
            $merchant['mer_id'] = $sub['mer_id'];
        }

        $channelConfig = json_decode($order['pay_config'], true);
        $refundOrder['order_type'] = $order['business'];
        $refundOrder['pay_orderno'] = $order['orderid'];
        return (new IcbcService($order['env'], $channelConfig))->refund($refundOrder, $merchant, $subRefunds);
    }
}
