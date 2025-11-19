<?php
/**
 * 平台订单汇总
 * add by lumin
 */

namespace app\common\model\service\order;

use app\common\model\db\ShopOrder;
use app\common\model\db\SystemOrder;

use app\common\model\db\User;
use app\common\model\db\UserScoreList;
use app\common\model\service\UserScoreListService;
use app\mall\model\db\MallOrder;
use think\facade\Cache;

class SystemOrderService{
    /**
     * 创建订单
     * @param string $business 业务标识
     * @param integer $business_oid 业务订单ID
     * @param integer $uid 用户ID
     * @return mixed 插入成功后的系统订单ID
     */
	public function saveOrder($business, $business_oid, $uid, $saveData = []){
		$data = [
			'type' => $business,
			'order_id' => $business_oid,
			'uid' => $uid,
			'paid' => 0,
			'system_status' => $saveData['system_status'] ?? 0,
			'create_time' => time(),
			'is_del' => 0,
			'last_time' => time(),
		];
        $data = array_merge($data,$saveData);
        $res = (new SystemOrder)->add($data);
        $this->sendAppPushMessage($business_oid, $business);
        return $res;
	}


    /**
     * 修改订单信息
     * @param  $business 业务标识
     * @param  $business_oid 业务订单ID
     * @param  $data 更新内容
     * @return true | false
     */
    public function editOrder($business, $business_oid, $data = []){
        if(empty($data)){
            return true;
        }
        $where = [
            'type' => $business,
            'order_id' => $business_oid,
        ];
        $res = (new SystemOrder)->updateThis($where, $data);
        $this->sendAppPushMessage($business_oid, $business);
        return $res;
    }


	/**
	 * 支付订单
	 * @param  $business 业务标识
	 * @param  $business_oid 业务订单ID
	 * @return true | false
	 */
	public function paidOrder($business, $business_oid){
		$where = [
			'type' => $business,
			'order_id' => $business_oid,
		];
		$data = [
			'paid' => 1,
			'system_status' => 0,
			'last_time' => time(),
			'pay_time' => time(),
		];
        $res = (new SystemOrder)->updateThis($where, $data);
        $this->sendAppPushMessage($business_oid, $business);
        return $res;
	}

	/**
	 * 订单发货操作
	 * @param  $business 业务标识
	 * @param  $business_oid 业务订单ID
	 * @return true | false
	 */
	public function sendOrder($business, $business_oid){
		$where = [
			'type' => $business,
			'order_id' => $business_oid,
		];
		$data = [
			'system_status' => 1,
			'last_time' => time(),
		];
        $res = (new SystemOrder)->updateThis($where, $data);
        $this->sendAppPushMessage($business_oid, $business);
        return $res;
	}

	/**
	 * 订单收货操作（或完成操作）
	 * @param  $business 业务标识
	 * @param  $business_oid 业务订单ID
	 * @return true | false
	 */
	public function completeOrder($business, $business_oid){
		$where = [
			'type' => $business,
			'order_id' => $business_oid,
		];
		$data = [
			'system_status' => 2,
			'last_time' => time(),
		];
        $res = (new SystemOrder)->updateThis($where, $data);
        $this->sendAppPushMessage($business_oid, $business);
        return $res;
	}

	/**
	 * 订单评价操作
	 * @param  $business 业务标识
	 * @param  $business_oid 业务订单ID
	 * @return true | false
	 */
	public function commentOrder($business, $business_oid){
		$where = [
			'type' => $business,
			'order_id' => $business_oid,
		];
		$data = [
			'system_status' => 3,
			'last_time' => time(),
		];
        $res = (new SystemOrder)->updateThis($where, $data);
        $this->sendAppPushMessage($business_oid, $business);
        return $res;
	}

	/**
	 * 订单退款操作
	 * @param  $business 业务标识
	 * @param  $business_oid 业务订单ID
	 * @return true | false
	 */
	public function refundOrder($business, $business_oid){
		$where = [
			'type' => $business,
			'order_id' => $business_oid,
		];
		$data = [
			'system_status' => 4,
			'last_time' => time(),
		];
        $res = (new SystemOrder)->updateThis($where, $data);
        //退积分
//        invoke_cms_model('User_score_list/back_score',[
//            $business,
//            $business_oid
//        ]);
        $this->backScore($business,$business_oid);
        $this->sendAppPushMessage($business_oid, $business);
        return $res;
	}

    /**
     * 退积分
     */
    public function backScore($business,$business_oid,$refundMoney=0)
    {
        if(!customization('back_order_user_score')){
            return false;
        }
        if($business == 'mall3' && cfg('mall_score_back_status') === '0'){//新版商城配置项可以控制订单退款是否回收用户积分
            return false;
        }
        //查询用户id
        $systemOrder = (new SystemOrder())->where([
            'type' => $business,
            'order_id' => $business_oid
        ])->field('uid,mer_id,store_id')->find();
        if(!$systemOrder){
            return false;
        }
        $business = $business == 'mall3' ? 'mall' : $business;
        $add = (new UserScoreList())->where(['order_id'=>$business_oid,'order_type'=>$business,'type'=>1])->sum('score');
        $subtract = (new UserScoreList())->where(['order_id'=>$business_oid,'order_type'=>$business,'type'=>2])->sum('score');

        if($business == 'mall' && $refundMoney){
            //适配部分退款，计算退款金额站总支付金额的比例
            $orderInfo = (new MallOrder())->where(['order_id'=>$business_oid])->field('money_real,money_freight')->find();
            $score = getFormatNumber($refundMoney/($orderInfo['money_real']-$orderInfo['money_freight'])*$add);
        }else{
            //查询需要退款的积分
            //查询用户此订单获取的积分数量
            $setScore = $add - $subtract;
            if($setScore <= 0){//不用操作
                return true;
            }
            $score = $setScore;
        }
        if($systemOrder['uid']){
            (new UserScoreListService())->addRow($systemOrder['uid'],2,$score,'用户退单，扣除积分',true,false,0,[
                'mer_id' => $systemOrder['mer_id'],
                'store_id' => $systemOrder['store_id'],
                'order_id' => $business_oid,
                'order_type' => $business
            ]);
            (new User())->where(['uid'=>$systemOrder['uid']])->dec('score_count', $score)->update();
        }
        return true;
    }
    
	/**
	 * 订单取消操作
	 * @param  $business 业务标识
	 * @param  $business_oid 业务订单ID
	 * @return true | false
	 */
	public function cancelOrder($business, $business_oid){
		$where = [
			'type' => $business,
			'order_id' => $business_oid,
		];
		$data = [
			'system_status' => 5,
			'last_time' => time(),
		];
        $res = (new SystemOrder)->updateThis($where, $data);
        //退积分
//        invoke_cms_model('User_score_list/back_score',[
//            $business,
//            $business_oid
//        ]);
        $this->backScore($business,$business_oid);
        $this->sendAppPushMessage($business_oid, $business);
        return $res;
	}

    /**
     * 统计某个字段
     * @param  $where array 查询条件
     * @param  $field string 需要统计的字段
     * @author hengtingmei
     * @return string
     */
    public function getTotalByCondition($where, $field){
        $res = (new SystemOrder)->getTotalByCondition($where,$field);
        if(!$res){
            return 0;
        }

        return $res['total'];
    }

    /**
     * 根据字段统计商家排行榜
     * @param  $where array 查询条件
     * @param  $field string 需要统计的字段
     * @author hengtingmei
     * @return string
     */
    public function getMerchantRanking($where, $field, $limit=10){
        $res = (new SystemOrder)->getMerchantRanking($where,$field,$limit);
        if(!$res){
            return [];
        }

        return $res;
    }


    /**
     * 统计订单数量
     * @param  $where
     * @return string
     */
    public function getCount($where){
        return (new SystemOrder)->getCount($where);
    }

    /**
     * 获取一些数据
     * @param  $where
     * @return string
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0)
    {
        return (new SystemOrder)->getSome($where, $field, $order, $page, $limit);
    }
    public function sendAppPushMessage($orderId, $type)
    {
        if (in_array($type, ['dining'])) {
            invoke_cms_model('System_order/sendAppPushMessage', ['orderId' => $orderId, 'type' => $type]);
        }
    }


    /**
     * 申请退款操作
     * @param  $business 业务标识
     * @param  $business_oid 业务订单ID
     * @return true | false
     */
    public function refundingOrder($business, $business_oid) {
        $where = [
            'type' => $business,
            'order_id' => $business_oid,
        ];
        $data = [
            'system_status' => 7,
            'last_time' => time(),
        ];
        $res = (new SystemOrder)->updateThis($where, $data);
        $this->sendAppPushMessage($business_oid, $business);
        return $res;
    }



	/**
	 * 抖音订单同步推送
	 *
	 * @param string $payorderid  支付订单号 pigcms_pay_order_info.orderid  
	 * @param string $orderStatus 状态描述
	 * @return void
	 * @author: zt
	 * @date: 2022/09/15
	 */
	public function douyinOrderPush($payorderid, $orderStatus)
	{
		$dyAppid = cfg('dy_app_id');
		$dyAppSecret = cfg('dy_app_secret');
		if (empty($dyAppid) || empty($dyAppSecret)) {
			return false;
		}

		$paydb = new \app\pay\model\db\PayOrderInfo();
		$payRecord = $paydb->getOne(['orderid' => $payorderid]);
		if (empty($payRecord)) {
			return false;
		}

		$business = $payRecord->business;
		$businessOrderid = $payRecord->business_order_id;
		if ($business == 'group') {
			$orderInfo = (new \app\group\model\db\GroupOrder())->getOne(['order_id' => $businessOrderid]);
			$user = (new \app\common\model\db\User())->getOne(['uid' => $orderInfo->uid]);
			$group = (new \app\group\model\db\Group())->getOne(['group_id' => $orderInfo->group_id]);
		} else {
			return false;
		}

		$tokenCacheKey = 'douyinAccessToken';
		if (Cache::get($tokenCacheKey)) {
			$token = Cache::get($tokenCacheKey);
		} else {
			$url = 'https://developer.toutiao.com/api/apps/v2/token';
			$params = [
				'appid' => cfg('dy_app_id'),
				'secret' => cfg('dy_app_secret'),
				'grant_type' => 'client_credential'
			];
			$result = \net\Http::curlPost($url, json_encode($params), 30, 'json');
			if ($result['err_no'] == 0) {
				//获取token成功
				$token = $result['data']['access_token'];
				Cache::set($tokenCacheKey, $token, 3600);
			} else {
				//获取token失败
				return false;
			}
		}

		list($s1, $s2) = explode(' ', microtime());
		$microSecond = (int)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
		$goodsPics = explode(';',$group->pic);
		if ($token) {
			//推送同步订单
			$pushUrl = 'https://developer.toutiao.com/api/apps/order/v2/push';
			$orderDetail = [
				'order_id' => $payorderid,
				'create_time' => $microSecond,
				'status' => $orderStatus,
				'amount' => $orderInfo['num'],
				'total_price' => intval($orderInfo['total_money'] * 100),
				'detail_url' => 'pages/group/index/home',
				'item_list' => [
					[
						'item_code' => $business . '_' . $orderInfo->group_id,
						'img' => replace_file_domain($goodsPics[0] ?? ''),
						'title' => $group->name,
						'price' => intval($orderInfo['price'] * 100)
					]
				],
			];
			$orderStatusMap=[
				'待支付'=>0,
				'已支付'=>1,
				'已取消'=>2,
				'已核销'=>4,
				'退款中'=>5,
				'已退款'=>6,
				'退款失败'=>8,
			];
			$pushParam = [
				'access_token' => $token,
				'app_name' => 'douyin',
				'open_id' => $user->dy_openid,
				'order_detail' => json_encode($orderDetail),
				'order_type' => 0,
				'update_time' => $microSecond,
				'order_status' => $orderStatusMap[$orderStatus]??0,
			];
			$result = \net\Http::curlPost($pushUrl, json_encode($pushParam), 30, 'json');
		}
	}

}