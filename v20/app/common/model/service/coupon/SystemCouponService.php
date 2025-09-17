<?php
/**
 * 系统优惠券
 * add by lumin
 * 用这个才是王道， 用其他的我不承认
 */

namespace app\common\model\service\coupon;

use app\common\model\db\MerchantStore;
use app\common\model\db\ShopOrder;
use app\common\model\db\StoreOrder;
use app\common\model\db\SystemCoupon;
use app\common\model\db\SystemCouponHadpull;
use app\common\model\db\SystemCouponStore;
use app\common\model\db\SystemCouponUseList;
use app\common\model\db\SystemOrder;
use app\common\model\service\UserService;
use app\group\model\db\GroupOrder;
use app\common\model\service\export\ExportService as BaseExportService;
use app\mall\model\db\MallOrder;
use net\Http;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class SystemCouponService{
	/**
	 * 获取优惠券详情
	 * @param  [int] $coupon_id 优惠券ID
	 * @return [array]  优惠券必要信息
	 */
	public function getCouponInfo($coupon_id){
		if(empty($coupon_id)) throw new Exception("$coupon illegal");
		$info = (new SystemCoupon)->getOne(['coupon_id'=> $coupon_id]);

		if(empty($info)) return [];

		$data = $this->formatDiscount([$info]);
		return $data ? $data[0] : [];
	}

	/**
	 * 格式化优惠券
	 * @param  [array] $coupon 二维数组(循环处理)
	 * @return [array]         
	 */
	public function formatDiscount($coupons, $simple=false){
		if(empty($coupons)) return [];
		foreach ($coupons as $key => $value) {
			$value['order_money'] = get_format_number($value['order_money']);
			$value['discount'] = get_format_number($value['discount']);
			$value['discount_value'] = get_format_number($value['discount_value']);
			$coupons[$key]['order_money'] = $value['order_money'];
			$coupons[$key]['discount'] = $value['discount'];
			$coupons[$key]['limit_date'] = isset($value['start_time']) && isset($value['end_time']) ? date('Y.m.d', $value['start_time']).' - '.date('Y.m.d', $value['end_time']) : '';
			$coupons[$key]['discount_title'] = "";
			$coupons[$key]['discount_des'] = "";
			if($value['is_discount']){
				$coupons[$key]['discount_title'] = "打".$value['discount_value']."折";
				$coupons[$key]['discount_des'] = ($value['order_money'] > 0 ? "满".$value['order_money'] : '无门槛')."打".$value['discount_value']."折";
				if($simple){
                    $coupons[$key]['discount_des'] = $value['discount_value'].'折';
				}
			}
			else{
				$coupons[$key]['discount_title'] = cfg('Currency_symbol').$value['discount'];
				$coupons[$key]['discount_des'] = ($value['order_money'] > 0 ? "满".$value['order_money'] : '无门槛')."减".$value['discount'];
				if($simple){
                    $coupons[$key]['discount_des'] = '-'.cfg('Currency_symbol').$value['discount'];
				}
			}
		}
		return $coupons;
	}

	/**
	 * 获取可用优惠券列表
	 * @param int $uid 当前用户ID
	 * @param array $order_info [
	 *                          can_coupon_money:1,//当前订单可以参与优惠券金额计算的金额
	 *                          freight_charge:1,//当前订单里面的运费（有就传，是用来计算运费券）
	 *                          business:'shop',//业务代号 如：shop\mall
	 *                          store_id:2,//当前门店ID
	 *                          category:[1,2],//当前商品分类ID数组（商城不用传）
	 *                          platform:'wap',//当前环境 如：wap/app/weixin
	 *                          city_id:1,//当前订单所属城市
	 *                          deliver_type:0,//0=无 1=自取 2=配送（仅外卖业务需传递）
	 *                          is_discount:0,//是否需要折扣券 1=需要（目前仅外卖）
	 * ]
	 * @param bool $filter_repeat 去掉重复的券
	 * @return array 二维数组，把用户的所有符合条件的优惠券返回
	 */
	public function getAvailableCoupon($uid, $order_info = [], $filter_repeat = false){
		if(empty($uid)) throw new \Exception("uid 不合法");	

		$order_money = $order_info['can_coupon_money'] ?? 0;//业务方需传递可参与优惠券计算的金额
		$freight_charge = $order_info['freight_charge'] ?? 0;//业务方运费，没有可以不传

		$business = $order_info['business'] ?? '';//业务代号
		$category = $order_info['category'] ?? [];//所属分类
		$platform = $order_info['platform'] ?? '';//环境
		$store_id = $order_info['store_id'] ?? 0;//门店ID
		$city_id  = $order_info['city_id'] ?? 0;//所属城市
		$deliver_type = $order_info['deliver_type'] ?? 0;//0=无 1=自取 2=配送（仅外卖业务传递）
		$is_discount = $order_info['is_discount'] ?? 0;//折扣券

		$current_time = time();
		$where = [
			['c.end_time', '>', $current_time],
			['c.start_time', '<', $current_time],
			['c.status', 'in', [1, 3]],
			['h.is_use', '=', 0],
			['h.uid', '=', $uid],
			['c.order_money', '<=', $order_money],
		];
		if($business){
			$where[] = ['c.cate_name', 'in', ['all','allin', $business]];
		}
		if($city_id){
			$where[] = ['in', ['c.city_id', 0, $city_id]];
		}
		if($deliver_type){
			$where[] = ['c.deliver_type', 'in', [0, $deliver_type]];
		}
		if($is_discount === 0){
			$where[] = ['c.is_discount', '=', 0];
		}

        if(!isset($order_info['is_discount'])){
            foreach($where as $k=>$v) {
                if(['c.is_discount', '=', 0] == $v) unset($where[$k]);
            }
            $where=array_values($where);
        }
		$coupons = (new SystemCouponHadpull)->getUserCoupon($where, 'h.id,h.receive_time,c.*')->toArray();

		$return = [];
		$repeat_coupon = [];
		foreach ($coupons as $key => $value) {
			$v_platform = $value['platform'] ? unserialize($value['platform']) : [];
			$v_cate_id = $value['cate_id'] ? unserialize($value['cate_id']) : [];

			//过滤可使用天数
			if($value['valid_time']){
				$endtime = max($value['receive_time'],$value['start_time']) + $value['valid_time']*86400;
				if($endtime < $current_time){
					continue;
				}
			}

			//过滤掉环境不符合的优惠券
			if($platform && $v_platform && !in_array($platform, $v_platform)){
				continue;
			}

			//过滤掉分类不符合的
			if($category && $v_cate_id){
				$cate_id = $v_cate_id['cat_id'];
				if(!in_array($cate_id, $category)){
					continue;
				}
			}

			//过滤店铺
			if($store_id){
				$coupon_stores = (new SystemCouponStore)->getSome(['coupon_id'=>$value['coupon_id']])->toArray();
				if($coupon_stores && !in_array($store_id, array_column($coupon_stores, 'store_id'))){
					continue;
				}
			}

			//计算优惠金额（要换算折扣券与普通券的抵扣金额）
			$value['discount_money'] = $value['discount'];
			if($value['is_discount']==1){//折扣券
				if($value['discount_type'] == 1){//减运费
					$value['discount_money'] = round($freight_charge * (100-$value['discount_value']*10)/100,2);
				}
				else{
					$value['discount_money'] = round($order_money * (100-$value['discount_value']*10)/100,2);
				}
			}

			if($filter_repeat === true && in_array($value['coupon_id'], $repeat_coupon)){//去掉重复的券
				continue;
			}
			$repeat_coupon[] = $value['coupon_id'];

			$return[] = $value;
		}
		return sortArrayAsc($return, 'discount_money');
	}

	/**
	 * 计算优惠金额
	 * @param  [type] $coupon_id   优惠券ID
	 * @param  [type] $order_money 订单金额
	 * @return [
	 *         order_money_discount:订单优惠的金额
	 *         freight_charge_discount:运费优惠的金额
	 * ]
	 */
	public function computeDiscount($coupon_id, $order_money, $freight_charge = 0){
		$info = (new SystemCoupon)->getOne(['coupon_id'=> $coupon_id]);

		$return = [
			'order_money_discount' => 0,
			'freight_charge_discount' => 0
		];
		if($info['order_money'] > $order_money){
			return $return;
		}
		if($info['is_discount']==1){//折扣券
			if($info['discount_type'] == 1){//减运费
				$return['freight_charge_discount'] = round($freight_charge * (100-$info['discount_value']*10)/100,2);
			}
			else{
				$return['order_money_discount'] = round($order_money * (100-$info['discount_value']*10)/100,2);
			}
		}
		else{
			$return['order_money_discount'] = $info['discount'];
		}
		return $return;
	}

	/**
	 * 通过优惠券领取ID获取优惠券信息（未使用、未过期）
	 * @param  [type]  $hadpull_id     领取记录ID
	 * @param bool  $check 是否需要校验优惠券是否过期等
	 * @return 优惠券信息数组
	 */
	public function getCouponByHadpullId($hadpull_id, $check = true){
		$where = [
			['h.id', '=', $hadpull_id]
		];
		if($check){
			$where[] = ['h.is_use', '=', 0];
			$where[] = ['c.end_time', '>', time()];
		}
		$field = 'h.id,c.coupon_id,c.name,c.des,c.des_detial,c.had_pull,c.num,c.limit,c.use_limit,c.order_money,c.discount as price,c.discount,c.is_discount,c.discount_value,c.discount_type,c.is_share_with_discount,c.plat_money,c.merchant_money';
		$coupon = (new SystemCouponHadpull)->getUserCoupon($where, $field)->toArray();

		return $coupon ? $coupon[0] : [];
	}

	/**
	 * 获取优惠券列表
	 * @param  $business 业务标识  传递了goods_id后，必须要传此参数
	 * @param  $platform 当前环境 如：wap/app/weixin
	 * @param  $uid 用户ID，传递则会判断当前用户是否已领取该券
	 * @param  $filter 是否过滤无效券  true=是
	 * @return array []
	 */
	public function getSystemCouponList($business = '', $platform = '', $uid = 0, $filter = false, $filter_discount = false){
		$current_time = time();
		$where = [
			['end_time', '>', $current_time],
			['start_time', '<', $current_time],
			['is_hide', '=', '0'],
            ['is_privileged', '=', '0'],
            ['is_partition', '=', '0'],
            ['allow_im_get', '=', '0'],
			['status', 'in', [1, 3]]
		];
		if($filter_discount){
			$where[] = ['is_discount', '=', '0'];
		}
		if($business){
			$where[] = ['cate_name', 'in', ['all','allin', $business]];
		}
		$system_coupons = (new SystemCoupon)->getSome($where, true, 'coupon_id desc');
		if($system_coupons){
			$system_coupons = $system_coupons->toArray();
		}
		else{
			$system_coupons = [];	
		}

		if($uid){
			$receive = (new SystemCouponHadpull)->getSome(['uid'=>$uid], 'coupon_id,is_use');
			if($receive){
	            $receive = $receive->toArray();
	        }
	        else{
	        	$receive = [];
	        }
			$receive_ids = $receive ? array_unique(array_column($receive, 'coupon_id')) : [];
			$sys_coupon_ids = $system_coupons?array_column($system_coupons,'coupon_id'):[];
            $nouse_ids = array_diff($sys_coupon_ids,$receive_ids);
			foreach ($receive as $c) {
				if($c['is_use'] == '0' && !in_array($c['coupon_id'], $nouse_ids)){
					$nouse_ids[] = $c['coupon_id'];
				}
			}
		}

		$return = [];
		foreach ($system_coupons as $key => $value) {
			//过滤环境
			$v_platform = $value['platform'] ? unserialize($value['platform']) : [];
			if($platform && $v_platform && !in_array($platform, $v_platform)){
				continue;
			}

			if($filter){
				if($value['had_pull'] >= $value['num'] || $value['status'] == 3){
					continue;
				}
			}

			$value['is_get'] = 0;//是否已领取
			if(isset($receive_ids) && in_array($value['coupon_id'], $receive_ids)){
				$value['is_get'] = 1;
			}
			$value['is_use'] = 0;//是否已使用
			if(isset($nouse_ids) && $nouse_ids){
				if(!in_array($value['coupon_id'], $nouse_ids)){
					$value['is_use'] = 1;
				}
			}

			$return[] = $value;
		}
		
		return $this->formatDiscount($return);
	}

	/**
	 * 领取（派发）优惠券
	 * @param  [type] $uid       用户ID
	 * @param  [type] $coupon_id 优惠券ID
	 * @return 成功领取后的优惠券信息数组
	 */
	public function receiveCoupon($uid, $coupon_id){
		if(empty($uid) || empty($coupon_id)) throw new \Exception("uid | coupon_id 必传");
		$current_coupon = $this->getCouponInfo($coupon_id);
		if(empty($current_coupon) || $current_coupon['status'] == '4') throw new \Exception("coupon 不存在");

		$model = new SystemCoupon;
		$model_had = new SystemCouponHadpull;

		$now_user = (new UserService)->getUser($uid);
		$current_time = time();
		$check_new_user = false;//待开发， 等待陈翔的service，判断是否为新用户
		if($current_coupon['allow_new'] && !$check_new_user){
			throw new \Exception("只允许新用户领取");			
		}
		if($current_coupon['end_time'] < $current_time || $current_coupon['status'] == '2'){
			$current_coupon['status'] != '2' && $model->updateThis(['coupon_id'=>$coupon_id], ['status'=>2]);
			throw new \Exception("当前优惠券已过期");				
		}
		if($current_coupon['status'] == '0'){
			throw new \Exception("当前优惠券未启用");	
		}
		if($current_coupon['num'] == $current_coupon['had_pull'] || $current_coupon['status'] == 3){
			$current_coupon['status'] != '3' && $model->updateThis(['coupon_id'=>$coupon_id], ['status'=>3]);
			throw new \Exception("当前优惠券领完了");	
		}
		$all_receive = $model_had->getCount(['uid'=>$uid, 'coupon_id'=>$coupon_id]);
		if($all_receive >= $current_coupon['limit']){
			throw new \Exception("超出领取上限");	
		}
		if($current_coupon['city_id'] && $now_user['city_id'] != $current_coupon['city_id']){
			throw new \Exception("当前用户所属城市不能领取该券");	
		}

		$receive_nums = 1;//领取张数

		if($model->updateThis(['coupon_id'=>$coupon_id], ['had_pull'=>($current_coupon['had_pull']+$receive_nums), 'last_time'=>$current_time])){
			$insert_data = [];
			for($i = 0; $i < $receive_nums; $i++){
				$insert_data[] = [
					'coupon_id' => $coupon_id,
					'uid' => $uid,
					'num' => 1,
					'receive_time' => $current_time,
					'is_use' => 0
				];
			}
			if($model_had->addAll($insert_data)){
				return $current_coupon;
			}
		}
		throw new \Exception("领取失败!");	
	}

	/**
	 * 微信支付后发送优惠券给当前用户
	 * @param uid 用户ID
	 * @param money 金额（金额不足不发券）
	 * @return true | false
	 */
	public function afterWechatPay($uid, $money){
		if($money <= cfg('weixin_send_money')) return false;
		$coupon_ids = explode(',', cfg('weixin_send_coupon_list'));
		if(empty($coupon_ids)) return false;

		foreach ($coupon_ids as $coupon_id) {
			$this->receiveCoupon($uid, $coupon_id);
		}
		return true;
	}

    /**
     * 检查用户优惠券是否可用
     * @param $pullId
     * @param $merId
     * @param $uid
     * @author: 张涛
     * @date: 2020/8/22
     */
    public function checkCouponCanUse($pullId, $merId, $uid)
    {
        $thisCoupon = (new SystemCouponHadpull())->getOneUserCoupon(['h.id' => $pullId, 'h.uid' => $uid]);
        if (empty($thisCoupon)) {
            throw new \think\Exception('您选择的平台优惠券不存在！');
        }
        $thisCoupon = $thisCoupon->toArray();
        if ($thisCoupon['is_use'] != SystemCouponHadpull::CAN_USE) {
            throw new \think\Exception('您选择的平台优惠券已经被其他订单使用！');
        }
        $tm = time();
        if ($thisCoupon['start_time'] > $tm || $thisCoupon['end_time'] < $tm) {
            throw new \think\Exception('平台优惠券不在可用时间范围内');
        }

        //查看使用期限是否到期 用户可使用的天数
        if ($thisCoupon['valid_time']) {
            if ($thisCoupon['receive_time'] < $thisCoupon['start_time']) {
                $endTime = $thisCoupon['start_time'] + $thisCoupon['valid_time'] * 86400;
            } else {
                $endTime = $thisCoupon['receive_time'] + $thisCoupon['valid_time'] * 86400;
            }
            if ($endTime <= $tm) {
                throw new \think\Exception('平台优惠券已到期了');
            }
        }
        return $thisCoupon;
    }


    /**
     * 使用优惠券
     * @param $pullId int 领取记录ID
     * @param $orderId int  订单短id
     * @param $orderType string 订单类型
     * @param $merId int 商家ID
     * @param $uid int 用户ID
     * @author: 张涛
     * @date: 2020/8/22
     */
    public function useCoupon($pullId, $orderId, $orderType, $merId, $uid)
    {
        $thisCoupon = $this->checkCouponCanUse($pullId, $merId, $uid);

        $tm = time();
        $hadPullMod = new SystemCouponHadpull();

        //更新使用状态
        $useData = [
            'use_time' => $tm,
            'is_use' => $hadPullMod::HAS_USED
        ];
        $useResult = $hadPullMod->where(['id' => $pullId])->update($useData);
        if (!$useResult) {
            throw new \think\Exception('平台优惠券使用失败！');
        }

        //消费微信卡包
        if ($thisCoupon['is_wx_card']) {
            $http = new Http();
            $accessTokenArray = (new \app\common\model\service\weixin\AccessTokenExpiresService)->getAccessToken();
            if ($accessTokenArray['errcode']) {
                throw new \think\Exception('获取access_token发生错误：错误代码' . $accessTokenArray['errcode'] . ',微信返回错误信息：' . $accessTokenArray['errmsg']);
            }
            $wxDate['code'] = $thisCoupon['wx_card_code'];
            http_request('https://api.weixin.qq.com/card/code/consume?access_token=' . $accessTokenArray['access_token'], 'post', json_encode($wxDate, JSON_UNESCAPED_UNICODE));
        }

        //写入优惠券使用记录
        $arr = [
            'coupon_id' => $thisCoupon['coupon_id'],
            'order_type' => $orderType,
            'order_id' => $orderId,
            'hadpull_id' => $pullId,
            'uid' => $uid,
            'num' => 1,
            'use_time' => $tm
        ];
        (new SystemCouponUseList())->add($arr);
        return true;
    }

    //退款时退优惠券
    public function refundReturnCoupon($hadpull_id){
    	$hadPullMod = new SystemCouponHadpull();

        //更新使用状态
        $useData = [
            'use_time' => 0,
            'is_use' => 0
        ];
        $useResult = $hadPullMod->updateThis(['id' => $hadpull_id], $useData);
        return $useResult;
    }

    /**
     * 获取平台补贴 和商家补贴的数额 （目前只有商城业务在用这个逻辑）
     * @param  [type]  $hadpull_id 用户领取记录ID
     * @param  integer $money      实际使用扣除的金额（在折扣券时需要换算比例）
     * @return array              ['plat_money' => 1, 'merchant_money' => 2]
     */
    public function getMoney($hadpull_id, $money)
    {
        if (empty($hadpull_id) || empty($money)) return [];
        $hadPullMod = new SystemCouponHadpull();
        $get = $hadPullMod->getOne([['id', '=', $hadpull_id]], 'coupon_id');
        if (empty($get)) {
            return [];
        }
        $get = $get->toArray();
        $coupon = $this->getCouponInfo($get['coupon_id']);
        if ($coupon['cate_name'] == 'mall' && $coupon['plat_money'] > 0) {
            if ($coupon['is_discount'] == '1') {
                $plat_money = get_format_number($money * $coupon['plat_money']);
                return ['plat_money' => $plat_money, 'merchant_money' => $money - $plat_money];
            } else {
                return ['plat_money' => $coupon['plat_money'], 'merchant_money' => $money - $coupon['plat_money']];
            }
        } else {
            return ['plat_money' => $money, 'merchant_money' => 0];
        }
    }
    /**
     * 获取系统优惠券核销记录
     * @param $param
     * @author: 张涛
     * @date: 2020/11/25
     */
    public function getUseRecords($param)
    {
        $where = [['p.use_time', '>', 0], ['p.is_use', '=', 1]];
        $mobilePay = (new \app\common\model\db\ShopOrder())->getMobilePayArr();

        if ($param['keyword']) {
            $where[] = ['c.name', 'like', '%' . $param['keyword'] . '%'];
        }
        if ($param['start_time']) {
            $where[] = ['p.use_time', '>', strtotime($param['start_time'])];
        }
        if ($param['end_time']) {
            $where[] = ['p.use_time', '<', strtotime($param['end_time']) + 86400];
        }
        if ($param['is_discount'] != -1) {
            $where[] = ['c.is_discount', '=', $param['is_discount']];
        }

        if ($param['business_type'] == 'mall') {
            //商城
            $where[] = ['o.system_coupon_id', '>', 0];
            $orderMod = new MallOrder();
            if ($param['is_mobile_pay'] != -1) {
                switch ($param['is_mobile_pay']) {
                    case 1:
                        $where[] = ['o.source', 'in', ['wechat_h5','h5']];
                        break;
                    case 2:
                        $where[] = ['o.source', 'in', ['androidapp','iosapp']];
                        break;
                    case 3:
                        $where[] = ['o.source', 'in', ['wechat_mini']];
                        break;
                }
            }
            $fields = 'o.order_id,o.source,p.coupon_id,p.use_time,c.name,c.is_discount,s.name AS store_name,u.nickname';
            $joinMod = $orderMod->alias('o')->join('system_coupon_hadpull p', 'p.id = o.system_coupon_id AND o.uid=p.uid');
        }else{
            $where[] = ['o.coupon_id', '>', 0];
            if ($param['business_type'] == 'shop') {
                //外卖
                $orderMod = new ShopOrder();
                if ($param['business_type'] == 'mall') {
                    $where[] = ['o.order_from', '=', 1];
                } else {
                    $where[] = ['o.order_from', '<>', 1];
                    $where[] = ['o.status', 'NOT IN', [0, 5]];
                }
                if ($param['is_mobile_pay'] != -1) {
                    $where[] = ['o.is_mobile_pay', '=', $param['is_mobile_pay']];
                }
                $fields = 'o.order_id,o.is_mobile_pay,p.coupon_id,p.use_time,c.name,c.is_discount,s.name AS store_name,u.nickname';
            } else if ($param['business_type'] == 'group') {
                //团购
                $orderMod = new GroupOrder();
                if ($param['is_mobile_pay'] != -1) {
                    $where[] = ['o.is_mobile_pay', '=', $param['is_mobile_pay']];
                }
                $fields = 'o.order_id,o.is_mobile_pay,p.coupon_id,p.use_time,c.name,c.is_discount,s.name AS store_name,u.nickname';
            }else if($param['business_type'] == 'store'||$param['business_type'] == 'cash'){
                //优惠买单+店内收银
                $orderMod = new StoreOrder();
                if ($param['is_mobile_pay'] != -1) {
                    $where[] = ['o.is_mobile_pay', '=', $param['is_mobile_pay']];
                }
                if ($param['business_type'] == 'store') {
                    if (customization('internal_membership_card')) {
                        $where[] = ['o.from_plat', 'IN', [1, 4]];
                    } else {
                        $where[] = ['o.from_plat', '=', 1];
                    }
                } else {
                    $where[] = ['o.from_plat', 'IN', [2,3]];
                }
                $fields = 'o.order_id,o.is_mobile_pay,p.coupon_id,p.use_time,c.name,c.is_discount,s.name AS store_name,u.nickname';
            }
            $joinMod = $orderMod->alias('o')->join('system_coupon_hadpull p', 'p.id = o.coupon_id AND o.uid=p.uid');
        }

        $list = $joinMod
            ->join('system_coupon c', 'c.coupon_id = p.coupon_id')
            ->join('merchant_store s', 's.store_id = o.store_id')
            ->join('user u', 'u.uid = o.uid')
            ->field($fields)
            ->where($where)
            ->order('p.use_time DESC')
            ->paginate($param['pageSize'])
            ->each(function ($item) use ($mobilePay, $param) {
                $item['use_date'] = date('Y-m-d H:i:s', $item['use_time']);
                if ($param['business_type'] == 'mall') {
                    switch ($item['source']) {
                        case 'wechat_h5':
                        case 'h5':
                            $item['mobile_pay_str'] = '微信端';
                            break;
                        case 'androidapp':
                        case 'iosapp':
                            $item['mobile_pay_str'] = 'APP';
                            break;
                        case 'wechat_mini':
                            $item['mobile_pay_str'] = '小程序';
                            break;
                        default:
                            $item['mobile_pay_str'] = '--';
                            break;
                    }
                } else {
                    $item['mobile_pay_str'] = $mobilePay[$item['is_mobile_pay']] ?? '--';
                }
                return $item;
            })
            ->toArray();
        return ['list' => $list['data'] ?? [], 'total' => $list['total'] ?? 0];
    }

    /**
     * 平台优惠券领取记录
     * @param $param
     * @param $type 1=导出
     */
    public function getHadpullRecords($param, $type = 0)
    {
        $limit = [
            'page' => $param['page'] ?? 1,
            'list_rows' => $param['pageSize'] ?? 10
        ];
        $type == 1 && $limit = 0;
        $where = [
            ['h.uid', '>', 0]
        ];
        $search_store = 0;
        if (!empty($param['keyword'])) {
            switch ($param['search_type']) {
                case 1:
                    $search_type = 'u.nickname';
                    break;
                case 2:
                    $search_type = 'u.phone';
                    break;
                case 3:
                    $search_type = 'c.name';
                    break;
                case 4: //店铺名称单独处理
                    $search_store = 1;
                    $search_type  = 's.name';
                    $where[] = ['h.use_time', '>', 0];
                    break;
            }
            $where[] = [$search_type, 'like', '%' . $param['keyword'] . '%'];
        }
        if (!empty($param['begin_time']) && !empty($param['end_time'])) {
            switch ($param['time_type']) {
                case 1:
                    $where[] = ['h.receive_time', '>=', strtotime($param['begin_time'])];
                    $where[] = ['h.receive_time', '<', strtotime($param['end_time']) + 86400];
                    break;
                default:
                    $where[] = ['h.use_time', '>=', strtotime($param['begin_time'])];
                    $where[] = ['h.use_time', '<', strtotime($param['end_time']) + 86400];
                    break;
            }
        }
        $field  = 'h.*,h.id as hadpull_id,c.name as coupon_name,u.nickname,u.phone';
        $field .= $search_store == 1 ? ',s.name as store_name' : ',"" as store_name';
        $result = (new SystemCouponHadpull())->getAllCouponHadpullList($where, $limit, $search_store, $field);
        if (!empty($result['data'])) {
            foreach ($result['data'] as $k => $v) {
                $result['data'][$k]['status']       = !empty($v['use_time']) ? '已使用' : '未使用';
                $result['data'][$k]['use_time']     = !empty($v['use_time']) ? date('Y/m/d H:i:s', $v['use_time']) : '无';
                $result['data'][$k]['receive_time'] = !empty($v['receive_time']) ? date('Y/m/d H:i:s', $v['receive_time']) : '无';
                if (!empty($v['use_time']) && empty($v['store_name'])) {
                    $useData = (new SystemCouponUseList())->getOne(['hadpull_id' => $v['hadpull_id']],true,'id desc');
                    if (!empty($useData)) {
                        $useData   = $useData->toArray();
                        if ($useData['order_type'] == 'mall') {
                            $orderData = (new SystemOrder())->getOne(['type' => 'mall3', 'order_id' => $useData['order_id']]);
                        } else if ($useData['order_type'] == 'store') {
                            $orderData = (new SystemOrder())->getOne([['type', 'in', 'store,cash'], ['order_id', '=', $useData['order_id']]]);
                        } else {
                            $orderData = (new SystemOrder())->getOne(['type' => $useData['order_type'], 'order_id' => $useData['order_id']]);
                        }
                        if (!empty($orderData)) {
                            $orderData = $orderData->toArray();
                            $result['data'][$k]['store_name'] = (new MerchantStore())->where(['store_id' => $orderData['store_id']])->value('name');
                        }
                    }
                }
            }
        }
        return $result;
    }

    /**
     * 添加导出计划任务
     * @param $param
     * @param array $systemUser
     * @param array $merchantUser
     * @return array
     * @throws \think\Exception
     */
    public function exportSysGetRecords($param, $systemUser = [], $merchantUser = [])
    {
        $title = '平台优惠券发放记录';
        $param['type'] = 'pc';
        $param['service_path'] = '\app\common\model\service\coupon\SystemCouponService';
        $param['service_name'] = 'recordsExportPhpSpreadsheet';
        $param['rand_number']  = time();
        $param['system_user']['area_id']  = $systemUser ? $systemUser['area_id'] : 0;
        $param['merchant_user']['mer_id'] = $merchantUser ? $merchantUser['mer_id'] : 0;
        $result = (new BaseExportService())->addExport($title, $param, 'xlsx');
        return $result;
    }

    /**
     * 导出(Spreadsheet方法)
     * @param $param
     */
    public function recordsExportPhpSpreadsheet($param)
    {
        $orderList   = $this->getHadpullRecords($param, 1)['data'];
        $spreadsheet = new Spreadsheet();
        $worksheet   = $spreadsheet->getActiveSheet();
        //设置单元格内容
        $worksheet->setCellValueByColumnAndRow(1, 1, '领取ID');
        $worksheet->setCellValueByColumnAndRow(2, 1, '用户昵称');
        $worksheet->setCellValueByColumnAndRow(3, 1, '用户手机');
        $worksheet->setCellValueByColumnAndRow(4, 1, '优惠券名称');
        $worksheet->setCellValueByColumnAndRow(5, 1, '数量');
        $worksheet->setCellValueByColumnAndRow(6, 1, '状态');
        $worksheet->setCellValueByColumnAndRow(7, 1, '使用店铺');
        $worksheet->setCellValueByColumnAndRow(8, 1, '使用时间');
        $worksheet->setCellValueByColumnAndRow(9, 1, '领取时间');
        //设置单元格样式
        $worksheet->getStyle('A1:I1')->getFont()->setName('黑体')->setSize(12);
        $spreadsheet->getActiveSheet()->getStyle('A:I')->getAlignment()->setWrapText(true);
        $spreadsheet->getActiveSheet()->getStyle('A1:I1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EEEEEE');
        $spreadsheet->getActiveSheet()->getRowDimension('1')->setRowHeight(26);
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(26);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(13);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(42);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(30);
        $len = count($orderList);
        $j   = 0;
        $row = 0;
        $i   = 0;
        foreach ($orderList as $key => $val) {
            if ($i < $len) {
                $j = $i + 2; //从表格第2行开始
                $worksheet->setCellValueByColumnAndRow(1, $j, $orderList[$key]['hadpull_id']);
                $worksheet->setCellValueByColumnAndRow(2, $j, $orderList[$key]['nickname']);
                $worksheet->setCellValueByColumnAndRow(3, $j, $orderList[$key]['phone']);
                $worksheet->setCellValueByColumnAndRow(4, $j, $orderList[$key]['coupon_name']);
                $worksheet->setCellValueByColumnAndRow(5, $j, $orderList[$key]['num']);
                $worksheet->setCellValueByColumnAndRow(6, $j, $orderList[$key]['status']);
                $worksheet->setCellValueByColumnAndRow(7, $j, $orderList[$key]['store_name']);
                $worksheet->setCellValueByColumnAndRow(8, $j, $orderList[$key]['use_time']);
                $worksheet->setCellValueByColumnAndRow(9, $j, $orderList[$key]['receive_time']);
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
        $worksheet->getStyle('A1:J' . $total_rows)->applyFromArray($styleArrayBody);
        //下载
        $filename = date("Y-m-d", time()) . '-' . $param['rand_number'] . '.xlsx';
        (new BaseExportService())->phpSpreadsheet($filename, $spreadsheet);

    }

}