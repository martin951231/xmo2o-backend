<?php
/**
 * 商家优惠券
 * add by lumin
 * 用这个才是王道， 用其他的我不承认
 */

namespace app\common\model\service\coupon;

use app\common\model\db\CardNewCoupon;
use app\common\model\db\CardNewCouponHadpull;
use app\common\model\db\CardNewCouponUseList;
use app\common\model\db\MerchantStore;
use app\common\model\db\SystemOrder;
use app\common\model\service\weixin\TemplateNewsService;
use app\mall\model\db\User;
use net\Http;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use app\common\model\service\export\ExportService as BaseExportService;
use think\Exception;


class MerchantCouponService{
	/**
	 * 获取优惠券详情
	 * @param  [int] $coupon_id 优惠券ID
	 * @return [array]  优惠券必要信息
	 */
	public function getCouponInfo($coupon_id){
		if(empty($coupon_id)) throw new Exception("$coupon illegal");
		$info = (new CardNewCoupon)->getOne(['coupon_id'=> $coupon_id]);

		if(empty($info)) return [];

		$data = $this->formatDiscount([$info]);
		return $data ? $data[0] : [];
	}

	/**
	 * 格式化优惠券
	 * @param  [array] $coupon 二维数组(循环处理)
	 * @param  bool $simple 简写
	 * @return [array]         
	 */
	public function formatDiscount($coupon, $simple=false){
		if(empty($coupon)) return [];
		foreach ($coupon as $key => $value) {
			$value['order_money'] = get_format_number($value['order_money']);
			$value['discount'] = get_format_number($value['discount']);
			$coupon[$key]['order_money'] = $value['order_money'];
			$coupon[$key]['discount'] = $value['discount'];
			$coupon[$key]['limit_date'] = [];
			if(isset( $value['start_time']) ){			
				$coupon[$key]['limit_date'] = date('Y.m.d', $value['start_time']).' - '.date('Y.m.d', $value['end_time']);
			}
			$coupon[$key]['discount_title'] = "";
			$coupon[$key]['discount_des'] = "";
			if(isset($value['is_discount'])&&$value['is_discount']){
				$coupon[$key]['discount_title'] = "打".$value['discount_value']."折";
				$coupon[$key]['discount_des'] = ($value['order_money'] > 0 ? "满".$value['order_money'] : '无门槛')."打".$value['discount_value']."折";
				if($simple){
					$coupon[$key]['discount_des'] = $value['discount'].'折';
				}
			}
			else{
				$coupon[$key]['discount_title'] = cfg('Currency_symbol').$value['discount'];
				$coupon[$key]['discount_des'] = ($value['order_money'] > 0 ? "满".$value['order_money'] : '无门槛')."减".$value['discount'];
				if($simple){
					$coupon[$key]['discount_des'] = '-'.cfg('Currency_symbol').$value['discount'];
				}
			}
		}
		return $coupon;
	}
    /**
     * 商家优惠券回收
     */
    public function updateUse($param)
    {
        try{
        $templateNewsService =new TemplateNewsService();
        $ret=(new CardNewCouponHadpull())->updateThis(['id'=>$param['id']],['is_use'=>2,'note'=>$param['note'],'recycle_time'=>time()]);
        $info = (new User())->getUserById($param['uid']);
        if($ret!==false){
            $datamsg = [
                'tempKey' => 'OPENTM400166399',
                'dataArr' => [
                    'wecha_id' => $info['openid'],
                    'first' => '您好，您的优惠券' . $param['coupon_name'] . '已被商家回收！',
                    'keyword1' => '优惠券状态提醒',
                    'keyword2' => '已回收',
                    'keyword3' => date('H:i'),
                ]
            ];
            $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
            return true;
        }else{
            return false;
        }
        }catch (\Exception $e){
            dd($e);
        }
    }
	/**
	 * 获取可用优惠券列表
	 * @param int $uid 当前用户ID
	 * @param int $mer_id 当前商户ID
	 * @param array $order_info [
	 *                          can_coupon_money:1,//当前订单可以参与优惠券金额计算的金额
	 *                          business:'shop',//业务代号 如：shop\mall
	 *                          store_id:2,//当前门店ID
	 *                          category:[1,2],//当前商品分类ID数组（商城不用传）
	 *                          platform:'wap',//当前环境 如：wap/app/weixin
	 *                          point_goods:[1=>100,2=>200],//限商城和外卖业务，传递格式：商品ID=>商品总价
	 *                          merchant_card:true,//是否使用了商家会员卡折扣 true为是 false为否
	 * ]
	 * @param bool $isCheck 是否验证订单条件
	 * @return array 二维数组，把用户的所有符合条件的优惠券返回
	 */
	public function getAvailableCoupon($uid, $mer_id = 0, $order_info = [], $isCheck = true){
		if(empty($uid)) throw new \Exception("uid 不合法");		

		$order_money = $order_info['can_coupon_money'] ?? 0;//业务方需传递可参与优惠券计算的金额
		$business = $order_info['business'] ?? '';//业务代号
		$store_id = $order_info['store_id'] ?? 0;//门店ID
		$category = $order_info['category'] ?? [];//所属分类
		$platform = $order_info['platform'] ?? '';//环境
		$point_goods  = $order_info['point_goods'] ?? [];//商品ID对应各个商品总价数组
		$merchant_card = $order_info['merchant_card'] ?? false;
		$business_type = $business=='mall'?'new_mall':$business;

		if($point_goods && !$business){
			throw new \Exception("若需要判断指定商品，则必须要传递业务代号");
		}

		$current_time = time();
		$where = [
			['c.end_time', '>', $current_time],
			['c.start_time', '<', $current_time],
			['c.status', 'in', [1, 3]],
			['h.is_use', '=', 0],
			['h.uid', '=', $uid],
		];
		if($mer_id){
			$where[] = ['c.mer_id', '=', $mer_id];
		}
		if($business){
			$where[] = ['c.cate_name', 'in', ['all', 'allin', $business]];
		}
		if($store_id){
			$where[] = ['c.store_id', 'find in set', $store_id];
		}
		if($merchant_card){
			$where[] = ['c.use_with_card', '=', 1];
		}
		$coupons = (new CardNewCouponHadpull)->getUserCoupon($where, 'h.id,c.*')->toArray();

		$return = [];
		foreach ($coupons as $key => $value) {
			$v_platform = $value['platform'] ? unserialize($value['platform']) : [];
			$v_cate_id = $value['cate_id'] ? unserialize($value['cate_id']) : [];
			$v_point_goodids = $value['point_goodids'] ? unserialize($value['point_goodids']) : [];
			if($isCheck){
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

				//过滤掉指定商品的
				if($v_point_goodids){
					if($point_goods && isset($v_point_goodids[$business_type])){
						$goods_money = 0;
						foreach ($point_goods as $k => $v) {
							if(in_array($k, $v_point_goodids[$business_type])){
								$goods_money += $v;
							}
						}
						if($goods_money == 0 || $value['order_money'] > $goods_money){
							continue;
						}
					}
					else{
						continue;
					}
				}
				
				//过滤掉订单金额不符合的
				if($order_money < $value['order_money']){
					continue;
				}
			}
			

			$return[] = $value;
		}
		return $return;
	}

	/**
	 * 计算优惠金额
	 * @param  [type] $coupon_id   优惠券ID
	 * @param  [type] $order_money 订单金额
	 * @return 得到的优惠金额
	 */
	public function computeDiscount($coupon_id, $order_money){
		$info = (new CardNewCoupon)->getOne(['coupon_id'=> $coupon_id]);

		if($info['order_money'] > $order_money){
			return 0;
		}
		return $info['discount'];
	}

	/**
	 * 通过优惠券领取ID获取优惠券信息（未使用、未禁用）
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
			$where[] = ['c.status', 'in', [1,3]];
		}
		$field = 'h.id,c.coupon_id,c.name,c.des,c.des_detial,c.had_pull,c.num,c.limit,c.use_limit,c.order_money,c.discount,c.discount as price,c.is_discount,c.discount_value';
		$coupon = (new CardNewCouponHadpull)->getUserCoupon($where, $field)->toArray();

		return $coupon ? $coupon[0] : [];
	}

	/**
	 * 获取某个商家能够使用的优惠券列表
	 * @param  $mer_id 商家ID
	 * @param  $goods_id 商品ID
	 * @param  $business 业务标识  传递了goods_id后，必须要传此参数
	 * @param  $platform 当前环境 如：wap/app/weixin
	 * @param  $uid 用户ID，传递则会判断当前用户是否已领取该券
	 * @param  $filter 是否过滤无效券  true=是
	 * @return array []
	 */
	public function getMerchantCouponList($mer_id, $goods_id = 0, $business = '', $platform = '', $uid = 0, $filter =false, $storeId = 0){
		if($goods_id){
			if(empty($business)){
				throw new \Exception("传递goods_id时，必须传递business参数");				
			}
		}
		$current_time = time();
		$where = [
			['end_time', '>', $current_time],
			['start_time', '<', $current_time],
			['status', 'in', [1, 3]],
			['mer_id', '=', $mer_id],
			['only_assign', '=', 0],
		];
		if($business){
			$where[] = ['cate_name', 'in', ['all', 'allin', $business]];
		}
        if ($storeId) {
            $where[] = ['store_id', 'find in set', $storeId];
        };

        $cardNewCouponMod = new CardNewCoupon();
		$merchant_coupons = $cardNewCouponMod->getSome($where, true, 'coupon_id desc');
		if($merchant_coupons){
			$merchant_coupons = $merchant_coupons->toArray();
		}
		else{
			$merchant_coupons = [];	
		}

		if($uid){
			$receive = (new CardNewCouponHadpull)->getSome(['uid'=>$uid], 'coupon_id,is_use');
			if($receive){
	            $receive = $receive->toArray();
	        }
	        else{
	        	$receive = [];
	        }
			$receive_ids = $receive ? array_unique(array_column($receive, 'coupon_id')) : [];
			$nouse_ids = [];
			foreach ($receive as $c) {
				if($c['is_use'] == '0' && !in_array($c['coupon_id'], $nouse_ids)){
					$nouse_ids[] = $c['coupon_id'];
				}
			}
		}

		$return = [];
		$cateNames = $cardNewCouponMod->getCatName();
        $business = $business=='mall'?'new_mall':$business;
		foreach ($merchant_coupons as $key => $value) {
			$v_point_goodids = [];//过滤指定商品
			if($value['point_goodids']){
				$v_point_goodids = unserialize($value['point_goodids']);
				if(!isset($v_point_goodids[$business])){
					continue;
				}
				if(!in_array($goods_id, $v_point_goodids[$business])){
					continue;
				}
			}

			$value['is_get'] = 0;//是否已领取
			if(isset($receive_ids) && in_array($value['coupon_id'], $receive_ids)){
				$value['is_get'] = 1;
			}
            if($filter && $value['is_get'] == 0){
                if($value['had_pull'] >= $value['num']  || $value['status'] == 3){
                    continue;
                }
            }
			$value['is_use'] = 0;//是否已使用
			if(isset($nouse_ids)&&$value['is_get']==1){
				if(!in_array($value['coupon_id'], $nouse_ids)){
					$value['is_use'] = 1;
				}
			}

			//过滤环境
			$v_platform = $value['platform'] ? unserialize($value['platform']) : [];
			if($platform && $v_platform && !in_array($platform, $v_platform)){
				continue;
			}

            //增加使用类别
            $value['cate_ch_name'] = $cateNames[$value['cate_name']] ?? '';
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
	public function receiveCoupon($uid, $coupon_id, $from = ''){
		if(empty($uid) || empty($coupon_id)) throw new \Exception("uid | coupon_id 必传");
		$current_coupon = $this->getCouponInfo($coupon_id);
		if(empty($current_coupon)) throw new \Exception("coupon 不存在");

		$model = new CardNewCoupon;
		$model_had = new CardNewCouponHadpull;

		$current_time = time();
		$check_new_user = false;//待开发， 等待陈翔的service，判断是否为新用户
		if($current_coupon['allow_new'] && !$check_new_user && $from != 'douyin'){
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
		if($all_receive >= $current_coupon['limit'] && $from != 'douyin'){
			throw new \Exception("超出领取上限");	
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
			if(count($insert_data) == 1){
			    $addId = $model_had->insertGetId($insert_data[0]);
			    if(!$addId){
                    throw new \Exception("领取失败!");
                }
                $current_coupon['add_id'] = $addId;
                return $current_coupon;
            }else
			if($model_had->addAll($insert_data)){
				return $current_coupon;
			}
		}
		throw new \Exception("领取失败!");	
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
        $thisCoupon = (new CardNewCouponHadpull())->getOneUserCoupon(['h.id' => $pullId, 'h.uid' => $uid, 'c.mer_id' => $merId]);
        if (empty($thisCoupon)) {
            throw new \think\Exception('您选择的商家优惠券不存在！');
        }
        $thisCoupon = $thisCoupon->toArray();
        if ($thisCoupon['is_use'] != CardNewCouponHadpull::CAN_USE) {
            throw new \think\Exception('您选择的商家优惠券已经被其他订单使用！');
        }
        $tm = time();
        if ($thisCoupon['start_time'] > $tm || $thisCoupon['end_time'] < $tm) {
            throw new \think\Exception('商家优惠券不在可用时间范围内');
        }
        return $thisCoupon;
    }


    /**
     * 使用优惠券
     * @param $pullId  领取记录ID
     * @param $orderId 订单短id
     * @param $orderType 订单类型
     * @param $merId 商家ID
     * @param $uid 用户ID
     * @author: 张涛
     * @date: 2020/8/22
     */
    public function useCoupon($pullId, $orderId, $orderType, $merId, $uid)
    {
        $thisCoupon = $this->checkCouponCanUse($pullId, $merId, $uid);

        $tm = time();
        $hadPullMod = new CardNewCouponHadpull();

        //更新使用状态
        $useData = [
            'use_time' => $tm,
            'is_use' => $hadPullMod::HAS_USED
        ];
        $useResult = $hadPullMod->updateThis(['id' => $pullId], $useData);
        if (!$useResult) {
            throw new \think\Exception('商家优惠券使用失败！');
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
        (new CardNewCouponUseList())->add($arr);
        return true;
	}
	

	/**
     * 获取商家优惠券核销记录
     * @param $param
     * @author: 张涛
     * @date: 2020/11/25
     */
    public function getUseRecords($param)
    {
        $where = [['o.card_id', '>', 0], ['p.use_time', '>', 0], ['p.is_use', '=', 1]];
		$mobilePay = (new \app\common\model\db\ShopOrder())->getMobilePayArr();
		if ($param['mer_id']) {
			$where[] = ['s.mer_id', '=', $param['mer_id']];
		}

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
		if ($param['store_id'] > 0) {
			$where[] = ['o.store_id', '=', $param['store_id']];
		}

        if ($param['business_type'] == 'shop' || $param['business_type'] == 'mall') {
            $orderMod = new \app\common\model\db\ShopOrder();
            if ($param['business_type'] == 'mall') {
                $where[] = ['o.order_from', '=', 1];
            } else {
                $where[] = ['o.order_from', '<>', 1];
            }
            if ($param['is_mobile_pay'] != -1) {
                $where[] = ['o.is_mobile_pay', '=', $param['is_mobile_pay']];
            }
            $fields = 'o.order_id,p.use_time,c.name,c.is_discount,o.is_mobile_pay,s.name AS store_name,u.nickname';
        } else if ($param['business_type'] == 'group') {
            $orderMod = new \app\group\model\db\GroupOrder();
            if ($param['is_mobile_pay'] != -1) {
                $where[] = ['o.is_mobile_pay', '=', $param['is_mobile_pay']];
            }
            $fields = 'o.order_id,p.use_time,c.name,c.is_discount,o.is_mobile_pay,s.name AS store_name,u.nickname';
        }

        $total = $orderMod->alias('o')
            ->join('card_new_coupon_hadpull p', 'p.id = o.card_id AND o.uid=p.uid')
            ->join('card_new_coupon c', 'c.coupon_id = p.coupon_id')
            ->join('merchant_store s', 's.store_id = o.store_id')
            ->join('user u', 'u.uid = o.uid')
            ->where($where)
			->count();
        if ($total > 0) {
            $list = $orderMod->alias('o')
				->join('card_new_coupon_hadpull p', 'p.id = o.card_id AND o.uid=p.uid')
				->join('card_new_coupon c', 'c.coupon_id = p.coupon_id')
				->join('merchant_store s', 's.store_id = o.store_id')
                ->join('user u', 'u.uid = o.uid')
                ->field($fields)
                ->where($where)
                ->page($param['page'], $param['pageSize'])
                ->select()->toArray();
        } else {
            $list = [];
        }
        foreach ($list as $k => $v) {
            $list[$k]['use_date'] = date('Y-m-d H:i:s', $v['use_time']);
            $list[$k]['mobile_pay_str'] = $mobilePay[$v['is_mobile_pay']] ?? '--';
        }
        return ['list' => $list, 'total' => $total];
    }

    //退款时退优惠券
    public function refundReturnCoupon($hadpull_id){
    	$hadPullMod = new CardNewCouponHadpull();

        //更新使用状态
        $useData = [
            'use_time' => 0,
            'is_use' => 0
        ];
        $useResult = $hadPullMod->updateThis(['id' => $hadpull_id], $useData);
        return $useResult;
    }


    public function getCouponInfoByCondition(array $condition){
        if(empty($condition)) {
            throw new Exception("参数非法");
        }
        $info = (new CardNewCoupon)->getSome($condition)->toArray();

        if(empty($info)) return [];

        $data = $this->formatDiscount($info);
        return $data;
    }

    /**
     * 商家优惠券领取记录
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
            ['h.uid', '>', 0],
            ['c.mer_id', '=', $param['mer_id']]
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
                case 5:
                    $search_type = 'cu.name';
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
                case 2:
                    $where[] = ['h.use_time', '>=', strtotime($param['begin_time'])];
                    $where[] = ['h.use_time', '<', strtotime($param['end_time']) + 86400];
                    break;
                case 3:
                    $where[] = ['h.recycle_time', '>=', strtotime($param['begin_time'])];
                    $where[] = ['h.recycle_time', '<', strtotime($param['end_time']) + 86400];
                    break;
                default:
                    $where[] = ['h.use_time', '>=', strtotime($param['begin_time'])];
                    $where[] = ['h.use_time', '<', strtotime($param['end_time']) + 86400];
                    break;
            }
        }
        $field  = 'h.*,h.id as hadpull_id,c.name as coupon_name,u.nickname,u.phone,cu.name as card_name';
        $field .= $search_store == 1 ? ',s.name as store_name' : ',"" as store_name';
        $result = (new CardNewCouponHadpull())->getAllCouponHadpullList($where, $limit, $search_store, $field, 'h.id desc', $param['mer_id']);
        if (!empty($result['data'])) {
            foreach ($result['data'] as $k => $v) {
                $result['data'][$k]['status']       = !empty($v['use_time']) ? '已使用' : ($v['is_use']==2?'不能使用':'未使用');
                $result['data'][$k]['use_time']     = !empty($v['use_time']) ? date('Y/m/d H:i:s', $v['use_time']) : '无';
                $result['data'][$k]['receive_time'] = !empty($v['receive_time']) ? date('Y/m/d H:i:s', $v['receive_time']) : '无';
                $result['data'][$k]['recycle_time'] = !empty($v['recycle_time']) ? date('Y/m/d H:i:s', $v['recycle_time']) : '无';
                if (!empty($v['use_time']) && empty($v['store_name'])) {
                    $useData = (new CardNewCouponUseList())->getOne(['hadpull_id' => $v['hadpull_id']]);
                    if (!empty($useData)) {
                        $useData   = $useData->toArray();
                        $orderData = (new SystemOrder())->getOne(['type' => $useData['order_type'], 'order_id' => $useData['order_id']]);
                        if (!empty($orderData)) {
                            $orderData = $orderData->toArray();
                            $result['data'][$k]['store_name'] = (new MerchantStore())->where(['store_id' => $orderData['store_id']])->value('name');
                        }
                    }
                }
                empty($v['card_name']) && $result['data'][$k]['card_name'] = '无';
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
    public function exportMerGetRecords($param, $systemUser = [], $merchantUser = [])
    {
        $title = '商家优惠券发放记录';
        $param['type'] = 'pc';
        $param['service_path'] = '\app\common\model\service\coupon\MerchantCouponService';
        $param['service_name'] = 'recordsExportPhpSpreadsheet';
        $param['rand_number']  = time();
        $param['system_user']['area_id']  = $systemUser ? $systemUser['area_id'] : 0;
        $param['merchant_user']['mer_id'] = $merchantUser ? $merchantUser['mer_id'] : 0;
        return $this->recordsExportPhpSpreadsheet($param);
        // $result = (new BaseExportService())->addExport($title, $param, 'xlsx');
        // return $result;
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
        $worksheet->setCellValueByColumnAndRow(3, 1, '员工会员名称');
        $worksheet->setCellValueByColumnAndRow(4, 1, '用户手机');
        $worksheet->setCellValueByColumnAndRow(5, 1, '优惠券名称');
        $worksheet->setCellValueByColumnAndRow(6, 1, '数量');
        $worksheet->setCellValueByColumnAndRow(7, 1, '状态');
        $worksheet->setCellValueByColumnAndRow(8, 1, '使用店铺');
        $worksheet->setCellValueByColumnAndRow(9, 1, '使用时间');
        $worksheet->setCellValueByColumnAndRow(10, 1, '领取时间');
        $worksheet->setCellValueByColumnAndRow(11, 1, '回收时间');
        $worksheet->setCellValueByColumnAndRow(12, 1, '回收备注');
        //设置单元格样式
        $worksheet->getStyle('A1:J1')->getFont()->setName('黑体')->setSize(12);
        $spreadsheet->getActiveSheet()->getStyle('A:J')->getAlignment()->setWrapText(true);
        $spreadsheet->getActiveSheet()->getStyle('A1:J1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EEEEEE');
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
        $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('L')->setWidth(30);
        $len = count($orderList);
        $j   = 0;
        $row = 0;
        $i   = 0;
        foreach ($orderList as $key => $val) {
            if ($i < $len) {
                $j = $i + 2; //从表格第2行开始
                $worksheet->setCellValueByColumnAndRow(1, $j, $orderList[$key]['hadpull_id']);
                $worksheet->setCellValueByColumnAndRow(2, $j, str_replace(['(',')','"'],'',$orderList[$key]['nickname']));
                $worksheet->setCellValueByColumnAndRow(3, $j, $orderList[$key]['card_name']);
                $worksheet->setCellValueByColumnAndRow(4, $j, trim($orderList[$key]['phone']));
                $worksheet->setCellValueByColumnAndRow(5, $j, $orderList[$key]['coupon_name']);
                $worksheet->setCellValueByColumnAndRow(6, $j, $orderList[$key]['num']);
                $worksheet->setCellValueByColumnAndRow(7, $j, $orderList[$key]['status']);
                $worksheet->setCellValueByColumnAndRow(8, $j, $orderList[$key]['store_name']);
                $worksheet->setCellValueByColumnAndRow(9, $j, $orderList[$key]['use_time']);
                $worksheet->setCellValueByColumnAndRow(10, $j, $orderList[$key]['receive_time']);
                $worksheet->setCellValueByColumnAndRow(11, $j, $orderList[$key]['recycle_time']);
                $worksheet->setCellValueByColumnAndRow(12, $j, $orderList[$key]['note']);
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
        
        $downFileUrl = cfg('site_url').'/v20/runtime/' . $filename;
        return ['file_url' => $downFileUrl];
    }


    /**
     * 显示抖音领取券详情
     * @date: 2022/12/07
     */
    public function showDouyinActivityCouponDetail($hadpullId, $activityId, $uid)
    {
        //探店领取记录
        $record = \think\facade\Db::name('douyin_activity_coupon_receive_record')
            ->where('uid', $uid)
            ->where('activity_id', $activityId)
            ->where('hadpull_id', $hadpullId)
            ->find();
        if (empty($record)) {
            throw new  Exception('探店领取记录不存在');
        }

        //优惠券领取记录
        $hadpullMod = new \app\common\model\db\CardNewCouponHadpull();
        $hadpull = $hadpullMod->where('uid', $uid)
            ->where('id', $hadpullId)
            ->where('coupon_id', $record['coupon_id'])
            ->findOrEmpty();
        if ($hadpull->isEmpty()) {
            throw new  Exception('优惠券领取记录不存在');
        }

        //优惠券信息
        $couponWhere = ['coupon_id' => $record['coupon_id']];
        $coupon = (new MerchantCouponService())->getCouponInfoByCondition($couponWhere);
        if (empty($coupon)) {
            throw new  Exception('优惠券不存在');
        }
        $coupon = $coupon[0];

        //探店活动
        $activity = (new \app\douyin\model\db\DouyinActivity())->where('id', $record['activity_id'])->findOrEmpty();

        //获取门店列表
        $stores = [];
        if ($coupon['store_id']) {
            $couponStores = (new \app\merchant\model\db\MerchantStore())->where([['store_id', 'in', $coupon['store_id']], ['status', '=', 1]])->select();
            foreach ($couponStores as $s) {
                $phones = array_unique(array_filter(explode(' ', $s->phone)));
                $stores[] = [
                    'store_id' => $s->store_id,
                    'store_name' => $s->name,
                    'score' => $s->score > 0 && $s->score <= 5 ? $s->score : 5,
                    'address' => $s->adress,
                    'lng' => $s->long,
                    'lat' => $s->lat,
                    'phone' => $phones
                ];
            }

        }
        $rs = [
            'hadpull_id' => $hadpullId,
            'coupon_id' => $coupon['coupon_id'],
            'img' => replace_file_domain($coupon['img']),
            'name' => $coupon['name'],
            'limit_date' => $coupon['limit_date'],
            'weixin_qrcode' => cfg('site_url') . '/index.php?c=Recognition&a=get_own_qrcode&qrCon=' . urlencode(get_base_url()),
            'coupon_qrcode' => cfg('site_url') . '/index.php?c=Recognition&a=get_own_qrcode&qrCon=mercoupon_' . $hadpullId . '_' . $uid,
            'is_use' => $hadpull['is_use'],
            'notes' => [
                [
                    'title' => '活动名称',
                    'value' => $activity->name
                ],
                [
                    'title' => '优惠内容',
                    'value' => $coupon['discount_des']
                ],
                [
                    'title' => '活动须知',
                    'value' => '最终解释权归活动商家所有'
                ],
            ],
            'stores' => $stores
        ];
        return $rs;
    }
}