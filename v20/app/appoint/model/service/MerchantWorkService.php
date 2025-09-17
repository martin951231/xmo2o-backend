<?php

namespace app\appoint\model\service;

use app\appoint\model\db\Appoint;
use app\appoint\model\db\AppointComment;
use app\appoint\model\db\AppointOrderRecords;
use app\appoint\model\db\AppointProduct;
use app\appoint\model\db\AppointSupply;
use app\appoint\model\db\MerchantWorkerHelp;
use app\appoint\model\db\MerchantWorkers;
use app\appoint\model\db\MerchantWorkersModifyOrderLog;
use app\appoint\model\db\MerchantWorkersMoney;
use app\appoint\model\db\RewardOrder;
use app\common\model\db\AppointCategory;
use app\common\model\db\AppointOrder;
use app\common\model\db\SystemOrder;
use app\common\model\db\User;
use app\common\model\db\WarnUser;
use app\common\model\service\address\AutoAddressService;
use app\common\model\service\BaiduMapService;
use app\common\model\service\weixin\TemplateNewsService;
use app\warn\model\db\WarnNotice;
use Carbon\Carbon;
use file_handle\FileHandle;
use map\longLat;
use think\facade\Db;
use token\Token;

class MerchantWorkService
{
    public $pageSize;
	const CHECK_TIME = 5;//轮询时间5秒
    public function __construct()
    {
        $this->pageSize = min(input('page_size', 10), 100);
    }

    /**
     * 技师主页详情
     * @param $params
     * @return mixed
     * @throws \think\Exception
     */
    public function getWorkInfo($params)
    {
        $id = $params['work_id'];
        if (!$id) {
            throw new \think\Exception(L_('参数错误！'));
        }
        $work_info = (new MerchantWorkers())
            ->field(
                'merchant_worker_id,name, nickname, info,desc,avatar_path,mobile,before_time,time_gap, help_status,
                merchant_store_id,mer_id, all_avg_score, reward_money, status, office_time, openid, now_money, commission'
            )
            ->with(['merchant_store' => function ($query) {
                $query->field('mer_id, store_id, name, phone, adress address, lat, long, score')
                    ->append(['mer_user_phone']);
            }])
            ->where(['merchant_worker_id' => $id])
            ->findOrEmpty()
            ->toArray();
        if (!$work_info) {
            throw_exception(L_(cfg('appoint_worker_name') . '不存在！'));
        }
        if (empty($work_info['status'])) {
            throw_exception('账号已被禁用');
        }
        $work_info['avatar_path'] = $work_info['avatar_path'] ? replace_file_domain('/upload/appoint/' . str_replace(',', '/', $work_info['avatar_path'])) : '';
        $work_info['info']        = $work_info['info'] ?: '';
        $work_info['desc']        = $work_info['desc'] ? replace_file_domain_content(htmlspecialchars_decode($work_info['desc'])) : '';
        $store = $work_info['merchant_store'];
        if (!empty($store['long']) && !empty($store['lat']) && !empty($params['lng']) && !empty($params['lat'])) {
            $location = (new longLat())->baiduToGcj02($store['lat'], $store['long']);
            $distance = getDistance($params['lat'], $params['lng'], $location['lat'], $location['lng']);
            $work_info['distance'] = $distance > 1000 ? bcdiv($distance,1000,2).'km': $distance.'m';
        }
        return $work_info;
    }

    public function login(array $params)
    {
        $worker = MerchantWorkers::where([
            'username' => $params['account'],
            'password' => md5(trim($params['password'])),
        ])->find();

        if (empty($worker)) throw_exception('账号不存在或密码错误！');

        if (empty($worker['status'])) throw_exception('此账号已冻结！');
        $worker->last_time = time();
        $worker->save();

        $ticket = Token::createToken($worker->merchant_worker_id, 11);//11 技师
        if (empty($ticket)) throw_exception('登录失败！');

        return ["ticket" => $ticket];
    }

    public function appointSupplyCount($workerId, $storeId, $status = 1)
    {
        return AppointSupply::withSearch(['worker_id'], ['worker_id' => $workerId])
            ->where('status', $status)
            ->where('store_id', $storeId)
            ->where('paid', 1)
            ->count();
    }

    public function appointSupplyList($storeId, $params = [])
    {
		$reParams = $params;
        if(!empty($params['keywords'])){
            switch (mb_strlen($params['keywords'])){
                case 11:
                    $params['uid'] = User::where('phone',  $params['keywords'])->column('uid');
                    if(empty($params['uid'])){
                        $params['uid'] = -1;
                    }
                    break;
                case 22:
                    $params['order_id'] = AppointOrder::where('real_orderid', $params['keywords'])->value('order_id');
                    if(empty($params['order_id'])){
                        $params['order_id'] = -1;
                    }
                    break;
                default:
                    throw_exception(L_('请输入正确订单号/手机号'));
            }
        }
        if (!empty($params['status'])) {
            if ($params['status'] == AppointSupply::CANCEL) {
                $params['status'] = [AppointSupply::CANCEL, AppointSupply::REFUND];
            }
        }
		$reRequest = 0;
        $data = AppointSupply::where([
                'store_id' => $storeId,
                'is_hide'  => 0,
                'paid'     => 1
            ])
            ->withSearch(['status', 'worker_id','start_time', 'end_time','order_id', 'uid'], $params)
            ->with([
                'appoint' => function ($query) {
                    $query->field('appoint_id, appoint_name, is_code_verify');
                },
                'appoint_order' => function ($query) {
                    $query->with(['user' => function ($query) {
                        $query->field('uid, nickname, phone');
                    },'category' => function ($query) {
	                    $query->field('cat_id, service_cue_field');
                    }, 'refund' => function ($query) {
                        $query->where('status', 1)->field('order_id, uid, price, applytime');
                    }])
                        ->field(
                            'order_id, appoint_id, mer_id, cue_field, payment_money, pay_money, appoint_price, 
                            uid, appoint_type, 0 as distance,0 as lng,0 as lat, appoint_date, appoint_time, 
                            product_name, product_id, order_name, service_status, real_orderid, payment_status, 
                            is_initiative, paid, cat_id, is_del'
                        );
                },
                'merchant_store' => function ($query) {
                    $query->field('store_id, name, adress address, long, lat');
                },
                'user' => function ($query) {
                    $query->field('uid, nickname, phone');
                }])
            ->append(['status_text'])
            ->order('supply_id DESC')
            ->paginate($this->pageSize)
            ->each(function ($item) use ($params, &$reRequest) {
                $item->appoint_order->appoint_date = $item->appoint_order->appoint_date.' '.$item->appoint_order->appoint_time;
                $field = $item->appoint_order->cue_field[0] ?? [];
                if ($item->appoint_order->appoint_type == 1) {//上门
                    $item->appoint_order->address = $field['address'] ?? '';
                    if (!empty($field['long']) && !empty($field['lat'])) {
                        $location = (new longLat())->baiduToGcj02($field['lat'], $field['long']);
                        $item->appoint_order->lat = floatval($location['lat']);
                        $item->appoint_order->lng = floatval($location['lng']);
                        if (!empty($params['lng']) && !empty($params['lat'])) {
                            $item->appoint_order->distance = getDistance($params['lat'], $params['lng'], $location['lat'], $location['lng']);
                        }
                    }
                } else {//到店
                    $item->appoint_order->address = $item->merchant_store['address'];
                    if (!empty($item->merchant_store['long']) && !empty($item->merchant_store['lat'])) {
                        $location = (new longLat())->baiduToGcj02($item->merchant_store['lat'], $item->merchant_store['long']);
                        $item->appoint_order->lng = floatval($location['lng']);
                        $item->appoint_order->lat = floatval($location['lat']);
                        if (!empty($params['lng']) && !empty($params['lat'])) {
                            $item->appoint_order->distance = getDistance($params['lat'], $params['lng'], $location['lat'], $location['lng']);
                        }
                    }
                }
                if($item->appoint_order->paid == AppointOrder::PAY_REFUND) {
                    $item->status = AppointSupply::REFUND;
                    $item->save();
                }elseif($item->appoint_order->paid == AppointOrder::PAY_CANCEL) {
                    $item->status = AppointSupply::CANCEL;
                    $item->save();
                }
                $item->appoint_order->wait_final_paid = 0;
                if($item->appoint_order->payment_status == 1 && $item->appoint_order->is_initiative <= 0) {//有定金未主动支付
                    $item->appoint_order->wait_final_paid = 1;//待付尾款
                }
	            if($item->appoint_order->is_del) {
		            $item->is_hide = 1;
		            $item->save();
		            $reRequest = 1;
	            }
            });
		if($reRequest) {
			return $this->appointSupplyList($storeId, $reParams);
		}

        return $data;
    }
	
	public function newOrder($storeId, $workerId): array
	{
		$appointSupply = AppointSupply::where([
			['store_id', '=', $storeId],
			['worker_id', '=', 0],
			['is_hide', '=', 0],
			['paid', '=', 1],
			['create_time', '>', time() - self::CHECK_TIME]
		])->value('supply_id');

		$workerSupply = AppointSupply::where([
			['worker_id', '=', $workerId],
			['is_hide', '=', 0],
			['paid', '=', 1],
			['create_time', '>', time() - self::CHECK_TIME]
		])->value('supply_id');

		return [
			'appoint_supply' => boolval($appointSupply),
			'worker_supply'  => boolval($workerSupply),
		];
	}

    public function grapOrder($workerId, $supplyId)
    {
        $appointSupply = AppointSupply::where([
            'supply_id' => $supplyId,
            'is_hide'   => 0
        ])->find();
        if (empty($appointSupply)) {
            throw_exception('服务信息错误！');
        }
        if ($appointSupply['status'] != AppointSupply::WAIT_RECEIVE) {
            throw_exception('已被抢单，不能再抢了!');
        }
        $appointOrder = AppointOrder::where(['order_id' => $appointSupply['order_id']])->find();
        if ($appointOrder['is_del'] != 0) {
            $appointSupply->is_hide = 1;
            $appointSupply->status  = 0;
            $appointSupply->save();
            throw_exception('此单已经取消或者删除无法进行操作！');
        }
        $totalPrice = floatval($appointOrder['product_price'])>=0 ? $appointOrder['product_price'] : $appointOrder['appoint_price'];
        $params = [
            'worker_id' => $workerId,
            'status'    => AppointSupply::RECEIVE,
            'start_time'=>time(),
            'commission' => $this->appointServiceFee($workerId, $totalPrice)
        ];
        $appointSupply->setAttrs($params);
        if (false === $appointSupply->save()) {
            throw_exception('抢单失败！');
        }
        $appointOrder->merchant_worker_id = $workerId;
        $appointOrder->save();
	    $workers = MerchantWorkers::where(['merchant_worker_id' => $appointSupply['worker_id']])->find();
	    (new AppointOrderService())->addOrderRecord($appointSupply->mer_id, $appointSupply->order_id, '技师抢单', ['content' => [
		    [
			    'title' => '技师名称',
			    'value' => $workers['name'],
			    'type'  => 'text'
		    ],
		    [
			    'title' => '技师手机号',
			    'value' =>  $workers['mobile'],
			    'type'  => 'text'
		    ],
	    ]]);
        return true;
    }

    public function appointServiceFee($workerId, $price)
    {
        $percent = MerchantWorkers::where([
                'merchant_worker_id' => $workerId
            ])
            ->value('commission_percent');
        $commission = 0;
        if($percent){
            $commission = bcmul($price,$percent/100,2);
        }
        return $commission;
    }
    
    public function completeOrder($workerId, $supplyId)
    {
        $appointSupply = AppointSupply::where([
            'supply_id' => $supplyId,
            'worker_id' => $workerId,
            'status'    => AppointSupply::RECEIVE,
            'is_hide'   => 0
        ])->find();
        if (empty($appointSupply)) {
            throw_exception('服务信息错误！');
        }
        $appointOrder = AppointOrder::where(['order_id' => $appointSupply['order_id']])->find();
        if ($appointOrder['is_del'] != 0) {
            $appointSupply->is_hide = 1;
            $appointSupply->status  = 0;
            $appointSupply->save();
            throw_exception('此单已经取消或者删除无法进行操作！');
        }
	    if(cfg('appoint_visit_sign') && empty($appointSupply['push_position_info']) && $appointOrder['appoint_type'] == 1) {
		    throw_exception('未上报位置信息，无法验证服务！');
	    }
        try {
            Db::transaction(function () use ($appointSupply, $appointOrder) {
                $appointSupply->status   = AppointSupply::COMPLETE;
                $appointSupply->end_time = time();
                if (
                    (($appointOrder['appoint_price'] > $appointOrder['payment_money']) || 
                    ($appointOrder['product_price'] > $appointOrder['product_payment_price'])) && 
                    !$appointOrder['is_initiative'] && 
                    ($appointOrder['payment_money'] || $appointOrder['product_payment_price'])
                ) {
                    $dataOrder = [
                        'is_mobile'       => 1,
                        'complete_source' => 2,
                    ];
                } else {
                    $dataOrder = [
                        'is_mobile'       => 1,
                        'complete_source' => 1,
                    ];
                }
                $appointOrder->setAttrs($dataOrder);

                if(//一口价
                    ($appointOrder['product_price'] && empty(floatval($appointOrder['product_payment_price']))) ||
                    ($appointOrder['appoint_price'] && empty(floatval($appointOrder['payment_money'])))
                ){
                    $result = invoke_cms_model('Appoint_order/appoint_verify',[$appointSupply['order_id'], [
                        'supply_id' => $appointSupply['supply_id'],
                        'worker_id' => $appointSupply['worker_id']
                    ]]);
                    fdump_api($result,'appoint_order/completeOrder',1);
                    if ($result['retval']['error']) {
                        throw_exception('更新状态失败！');
                    }
                }
                $appointSupply->save();
                if (false === $appointOrder->save()) {
                    throw_exception('更新状态失败！');
                }
				$workers = MerchantWorkers::where(['merchant_worker_id' => $appointSupply['worker_id']])->find();
	            (new AppointOrderService())->addOrderRecord($appointSupply->mer_id, $appointSupply->order_id, '技师服务完成', ['content' => [
					[
						'title' => '技师名称',
						'value' => $workers['name'],
						'type'  => 'text'
					],
		            [
			            'title' => '技师手机号',
			            'value' =>  $workers['mobile'],
			            'type'  => 'text'
		            ],
	            ]]);
            });
        } catch (\Exception $e) {
            throw_exception($e->getMessage());
        }
        return true;
    }

    public function commentList($workerId)
    {
        $commentList = AppointComment::where([
            'status'             => 1,
            'merchant_worker_id' => $workerId
        ])
            ->order('id DESC')
            ->append(['score_text'])
            ->paginate($this->pageSize)
            ->each(function ($item) {
                $commentImg = $item->comment_img;
                if($commentImg){
                    foreach ($commentImg as &$img) {
                        $img = replace_file_domain($img);
                    }
                }
                $item->comment_img = serialize($commentImg);
                $user              = User::where(array('uid' => $item['uid']))->field('nickname,avatar')->find();
                $item['avatar']    = replace_file_domain($user['avatar']);
                $item['nickname']  = $user['nickname'];
                $item['avg_score'] = (int)(($item['profession_score'] + $item['communicate_score'] + $item['speed_score']) / 3);
            });

        return $commentList;
    }

    public function serviceDetail($workerId, $params = [])
    {
        empty($params['supply_id']) && empty($params['order_id']) && throw_exception(L_('订单请求参数缺失'));
        $appointSupply = AppointSupply::where([
            'worker_id' => $workerId,
            'is_hide'   => 0
        ])
            ->withSearch(['supply_id','order_id'], $params)
            ->with([
                'appoint'        => function ($query) {
                    $query->field('appoint_id, appoint_name, is_code_verify, pic');
                },
                'appoint_order'  => function ($query) {
                    $query->with(['user' => function ($query) {
                        $query->field('uid, nickname, phone');
                    }, 'category' => function ($query) {
	                    $query->field('cat_id, service_cue_field');
                    }])
                        ->field(
                            'order_id, real_orderid, appoint_id, mer_id, cue_field, payment_money, appoint_price, uid, product_id, 
                            product_name, product_payment_price, product_price, payment_money ,appoint_date, appoint_time, product_name,
                            order_time, service_status, order_name, appoint_type, user_pay_time, paid, is_initiative, cat_id'
                        );
                },
                'merchant_store' => function ($query) {
                    $query->field('store_id, name');
                }])
            ->find();
        if (empty($appointSupply)) {
            throw_exception('该订单非您所有，请先核实！');
        }
        $picArr = explode(';',$appointSupply->appoint['pic']);
        foreach ($picArr as &$img){
            $img = (new AppointService())->getImageByPath($img,'s');
        }
        $appointSupply->appoint['pic'] = $picArr;
	    $appointSupply->appoint_order->service_records = $appointSupply->service_records;
	    $appointSupply->appoint_order->push_position_info = $appointSupply->push_position_info;
        $orderData  = [
            [
                'title' => '下单时间',
                'value' => $appointSupply->appoint_order->order_time,
            ], [
                'title' => '预约店铺',
                'value' => $appointSupply->merchant_store->name,
            ], [
                'type' => 'service_status',
                'title' => '服务状态',
                'value' => $appointSupply->status_text,
                'service_status' => $appointSupply->status
            ]
        ];
        $lng = 0;
        $lat = 0;
        $field = $appointSupply->appoint_order->cue_field[0] ?? [];
        if ($appointSupply->appoint_order->appoint_type == 1) {
            if (!empty($field['long']) && !empty($field['lat'])) {
                $lng = $field['long'];
                $lat = $field['lat'];
            }
        } else {
            if (!empty($appointSupply->merchant_store['long']) && !empty($appointSupply->merchant_store['lat'])) {
                $lng = $appointSupply->merchant_store['long'];
                $lat = $appointSupply->merchant_store['lat'];
            }
        }
        if(!empty($lat) && !empty($lng)){
            $location = (new longLat())->baiduToGcj02($field['lat'], $field['long']);
            $lat = floatval($location['lat']);
            $lng = floatval($location['lng']);
        }
        if ($appointSupply->appoint_order->appoint_type == 1) {
            $address = $field['address'] ?? '';
        } else {
            $address = $appointSupply->merchant_store['address'];
        }
	    $reward = $this->reward($workerId, $appointSupply->appoint_order->order_id);

	    $supplyData = [
		    [
			    'title' => '订单号',
			    'value' => $appointSupply->appoint_order->real_orderid,
		    ], [
			    'title' => '预约时间',
			    'value' => $appointSupply->appoint_order->appoint_date . ' ' . $appointSupply->appoint_order->appoint_time,
		    ], [
			    'type'  => 'phone',
			    'title' => '下单人手机号',
			    'value' => $appointSupply->appoint_order->user->phone,
		    ],[
			    'type'  => 'address',
			    'title' => '服务地址',
			    'value' => $address,
			    'lat'   => $lat,
			    'lng'   => $lng,
		    ]
	    ];
		if(empty($params['is_confirm_arrival'])) {
			array_push($supplyData,
				[
					'title' => '打赏金额',
					'value' => '￥' . $reward
				], [
					'title' => '服务费',
					'value' => '￥' . $appointSupply->commission
				], [
					'title' => '下单人名称',
					'value' => $appointSupply->appoint_order->user->nickname,
				], [
					'title' => '服务名称',
					'value' => $appointSupply->appoint->appoint_name,
				]
			);
		}
		if($cueField = $appointSupply->appoint_order->category->service_cue_field) {
			$appointSupply->appoint_order->category->service_cue_field = unserialize($cueField);
		}
        $appointSupply->appoint_order->worker_service_status = $appointSupply->status;
        $appointSupply->appoint_order->supply_id = $appointSupply->supply_id;
        //判断技师是否可以修改订单金额
        $worker = (new MerchantWorkers())->where(['merchant_worker_id'=>$workerId])->findOrEmpty()->toArray();
        $can_change_price = 0;
        if($worker['modify_order_price']){
            if(($appointSupply->appoint_order->paid==1&&($appointSupply->appoint_order->appoint_price>0||$appointSupply->appoint_order->product_price>0))||($appointSupply->appoint_order->paid==0&&$appointSupply->appoint_order->appoint_price<=0&&$appointSupply->appoint_order->product_price<=0)){
                if($appointSupply->appoint_order->service_status==0){
                    $can_change_price = 1;
                }
                if($appointSupply->appoint_order->user_pay_time==0){
                    $can_change_price = 1;
                }
                if($can_change_price==1&&$appointSupply->appoint_order->is_initiative>0){
                    $can_change_price = 0;
                }
            }
        }
        return [
            'appoint'       => $appointSupply->appoint,
            'appoint_order' => $appointSupply->appoint_order,
            'order_data'    => $orderData,
            'supply_data'   => $supplyData,
            'can_change_price' => $can_change_price
        ];
    }

    public function workTime($workUser, $recentTime = false)
    {
        $officeTime = unserialize($workUser['office_time']);
        if (isset($officeTime['open']) && isset($officeTime['close'])) {
            $officeTime = array($officeTime);
        }
        if (
            (count($officeTime) <= 1) ||
            ((isset($officeTime['open']) && $officeTime['open'] == '00:00') &&
                (isset($officeTime['close']) && $officeTime['close'] == '00:00'))
        ) {
            unset($officeTime[0]);
            $officeTime['open']  = '00:00';
            $officeTime['close'] = '24:00';
        } else {
            foreach ($officeTime as $i => $time) {
                if ($time['open'] == '00:00' && $time['close'] == '00:00') {
                    unset($officeTime[$i]);
                }
            }
        }
        if (isset($officeTime['open']) && isset($officeTime['close'])) {
            $officeTime = array($officeTime);
        }

        $beforeTime = $workUser['before_time'] > 0 ? ($workUser['before_time']) * 3600 : 0;
        $gap        = $workUser['time_gap'] * 60 > 0 ? $workUser['time_gap'] * 60 : 1800;
        $tempTime   = array();
        foreach ($officeTime as $key => $value) {
            $startTime = strtotime(date('Y-m-d') . ' ' . $value['open']);
            $endTime   = strtotime(date('Y-m-d') . ' ' . $value['close']);
            for ($time = $startTime; $time < $endTime; $time = $time + $gap) {
                $tempKey = date('H:i', $time) . '-' . date('H:i', $time + $gap);
                $tempTime[$tempKey] = [
                    'time'  => $tempKey,
                    'start' => date('H:i', $time),
                    'end'   => date('H:i', $time + $gap),
                    'order' => 'no',
                ];
                if ((date('H:i') < date('H:i', $time - $beforeTime))) {
                    $tempTime[$tempKey]['order'] = 'yes';
                }
            }
        }
        $startTimeAppoint = strtotime('now');
        $endTimeAppoint   = strtotime('+3 day');

        $dateArray[date('Y-m-d', $startTimeAppoint)] = date('Y-m-d', $startTimeAppoint);
        $dateArray[date('Y-m-d', $endTimeAppoint)]   = date('Y-m-d', $endTimeAppoint);
        for ($date = $startTimeAppoint; $date < $endTimeAppoint; $date = $date + 86400) {
            $dateArray[date('Y-m-d', $date)] = date('Y-m-d', $date);
        }
        $timeOrder = [];
        $week      = [];
        ksort($dateArray);
        $weekText = [
            '日', '一', '二', '三', '四', '五', '六'
        ];
        foreach ($dateArray as $i => $date) {
            $timeOrder[$date] = $tempTime;
            $week[$date]      = '周' . $weekText[date('w', strtotime($date))];
        }
        $appointNum = (new AppointOrderService())->workerAppointCount($workUser['merchant_worker_id'], $startTimeAppoint, $endTimeAppoint);
        ksort($timeOrder);
        foreach ($timeOrder as $i => $item) {
            foreach ($item as $key => $value) {
                if (strtotime($i . ' ' . $value['end']) < strtotime('now') + $beforeTime && ($value['order'] == 'yes')) {
                    $timeOrder[$i][$key]['order'] = 'no';
                } elseif (strtotime($i . ' ' . $value['end']) > strtotime('now') + $beforeTime + $gap && strtotime($i . ' ' . $value['start']) > strtotime('now') + $beforeTime && ($value['order'] == 'no')) {
                    $timeOrder[$i][$key]['order'] = 'yes';
                }
                if($recentTime && $timeOrder[$i][$key]['order'] == 'yes'){
                    if (count($appointNum) > 0) {
                        foreach ($appointNum as $val) {
                            if($val['appoint_date'] == $i && $timeOrder[$i][$key]['start'] == $val['appoint_time']){
                                $timeOrder[$i][$key]['order'] = 'all';
                            }
                        }
                    }
                    if($timeOrder[$i][$key]['order'] == 'yes'){
                        return $timeOrder[$i][$key]['start'];
                    }
                }
            }
        }
        if($recentTime){
            return '00:00';
        }
        if (count($appointNum) > 0) {
            foreach ($appointNum as $val) {
                $key = date('Y-m-d', strtotime($val['appoint_date']));
                if (isset($timeOrder[$key])) {
                    foreach ($timeOrder[$key] as $tk => &$t) {
                        if ($t['start'] == $val['appoint_time']) {
                            $t['order'] = 'all';
                        }
                    }
                }
            }
        }

        return ['time' => $timeOrder, 'week' => $week];
    }

    public function workTodaySupplyInfo($workerId)
    {
        $todayCount = AppointSupply::where([
                'worker_id' => $workerId,
                'status' => AppointSupply::COMPLETE
            ])
            ->where('end_time', 'BETWEEN', [strtotime(date('Y-m-d 00:00:00')), strtotime(date('Y-m-d 23:59:59'))])
            ->count();
        
        $todayMoney = MerchantWorkersMoney::where([
                'merchant_worker_id' => $workerId,
                'type' => 1
            ])
            ->where('time', 'BETWEEN', [strtotime(date('Y-m-d 00:00:00')), strtotime(date('Y-m-d 23:59:59'))])
            ->sum('money');
        
        $todayReward = RewardOrder::where([
                'reward_id' => $workerId,
                'type' => 3,
                'status' => 1
            ])
            ->where('add_time', 'BETWEEN', [strtotime(date('Y-m-d 00:00:00')), strtotime(date('Y-m-d 23:59:59'))])
            ->sum('worker_money');
        
        return [
            'today_count'  => $todayCount,
            'today_money'  => $todayMoney,
            'today_reward' => $todayReward,
        ];
    }

    public function oneClickHelp($worker, $params)
    {
        try {
            $data  = (new BaiduMapService())->reverseGeocoding($params);
            $address = $data['result']['formatted_address_poi'] ?? "地址解析失败";
        } catch (\Exception $e) {
            $address = "地址解析失败";
        }

        $title = "[技师需要帮助]【{$worker['name']}】需要帮助";
        $address = "发出求助位置：（经度：{$params['lng']}, 纬度：{$params['lat']}）{$address},技师电话：{$worker['mobile']}";
        $help = new WarnNotice();
        $help->setAttrs([
            'business' => 'appoint',
            'type'     => 9,//求救信息
            'mer_id'   => $worker['mer_id'],
            'store_id' => $worker['merchant_store_id'],
            'title'    => $title,
            'content'  => $address,
            'create_time'=>time(),
        ]);
        $help->save();
        $user = (new WarnUser())->getUsersData([
            'a.mer_id' => $worker['mer_id'],
            'a.status' => 1,
            'a.is_del' => 0
        ],'b.openid');
        
        foreach ($user as $openId){
            if(empty($openId)){
                continue;
            }
            $tempMsgArr = [];
            $tempMsgArr['title'] = "【{$worker['name']}】需要帮助";
            $tempMsgArr['status_text'] = $address;
            (new TemplateNewsService())->sendTempMsg('TM00017', $tempMsgArr, 0, 0, 999);
        }
        
        return true;
    }

    public function statistics($workerId, $startTime, $endTime)
    {
        $searchTime = [
            'time_start' => $startTime,
            'time_end'   => $endTime
        ];

        $countList = AppointSupply::where('worker_id', $workerId)
            ->withSearch(['time_start', 'time_end'], $searchTime)
            ->field('count(*) as cnt, get_type')
            ->group('get_type')
            ->select();

        $alreadyServiceCount = AppointSupply::where('worker_id', $workerId)
            ->withSearch(['time_start', 'time_end'], $searchTime)
            ->where('status', AppointSupply::COMPLETE)
            ->count();

        $result['commission'] = AppointSupply::where('worker_id', $workerId)
            ->withSearch(['time_start', 'time_end'], $searchTime)
            ->where('status', AppointSupply::COMPLETE)
            ->sum('commission');

        $customCount = AppointSupply::where('worker_id', $workerId)
            ->withSearch(['time_start', 'time_end'], $searchTime)
            ->where('uid', '>', 0)
            ->field('count(DISTINCT(uid)) as user_count')
            ->find();
        
        $result['self_count']   = 0;
        $result['system_count'] = 0;
        $result['change_count'] = 0;
        foreach ($countList as $row) {
            if ($row['get_type'] == 0) {
                $result['self_count'] = $row['cnt'];
            } elseif ($row['get_type'] == 1) {
                $result['system_count'] = $row['cnt'];
            } elseif ($row['get_type'] == 2) {
                $result['change_count'] = $row['cnt'];
            }
        }
        $result['already_total'] = $alreadyServiceCount;
        $result['user_count']    = $customCount['user_count'];

        return $result;
    }
    
    public function reward($workerId, $orderId)
    {
        $money = RewardOrder::where([
            'reward_id' => $workerId,
            'status' => 1,
            'is_del' => 1,
            'type' => 3,
            'business_order_id' => $orderId
        ])->sum('worker_money');
        
        return $money;
    }
    
    public function settlementServiceFee()
    {
        $time = time() - cfg('worker_service_fee_freeze_day')*3600*24;
        $moneyList = MerchantWorkersMoney::where([
                'from_type' => 2,//服务费
                'type' => 3
            ])
            ->where('time', '<', $time)
            ->limit(100)
            ->select();
        
        foreach ($moneyList as $workerMoney){
            Db::transaction(function ()use($workerMoney){
                $workerMoney->type = 1;//添加金额
                $workerMoney->save();
                
                $worker = MerchantWorkers::where('merchant_worker_id', $workerMoney->merchant_worker_id)->find();
                $worker->now_money += $workerMoney->money;
                $worker->commission += $workerMoney->money;
                $worker->save();
            });
        }
        return true;
    }
    
    public function changePwd($workUserId, $oldPassword, $password)
    {
        $user = MerchantWorkers::where('merchant_worker_id', $workUserId)
            ->field('merchant_worker_id, password')
            ->find();
        
        if(md5($oldPassword) != $user['password']){
            throw_exception(L_('旧密码错误！'));
        }
        if(mb_strlen($password) < 6 || is_numeric($password)){
            throw_exception(L_('密码不可全数字且至少6位字符！'));
        }
        $user->password = md5($password);
        return $user->save();
    }
    
    public function rankList($workUser, $type)
    {
        $date = [
            1 => [Carbon::now()->startOfDay()->timestamp, Carbon::now()->endOfDay()->timestamp],
            2 => [Carbon::now()->startOfWeek()->timestamp, Carbon::now()->endOfWeek()->timestamp],
            3 => [Carbon::now()->startOfMonth()->timestamp, Carbon::now()->endOfMonth()->timestamp],
        ];
        $data = AppointSupply::where('end_time','BETWEEN', $date[$type])
            ->where('status', AppointSupply::COMPLETE)
            ->where('mer_id', $workUser['mer_id'])
            ->where('worker_id','>', 0)
            ->with(['worker' => function($query){
                $query->field('avatar_path, name,merchant_worker_id');
            }])
            ->field('COUNT(*) cnt, worker_id')
            ->group('worker_id')
            ->order('cnt DESC')
            ->paginate($this->pageSize)
            ->each(function ($item)use($workUser){
                $item->worker->avatar_path = $this->getImagePath($item->worker->avatar_path,'m');
                $item->is_self = 0;
                if($item->worker_id == $workUser['merchant_worker_id']){
                    $item->is_self = 1;
                }
            });
        
        return $data;
    }

    private function getImagePath($path,$image_type='-1'){
        if(!empty($path)){
            $image_tmp = explode(',',$path);
            if($image_type == '-1'){
                $return['image'] = file_domain().'/upload/appoint/'.$image_tmp[0].'/'.$image_tmp['1'];
                $return['m_image'] = file_domain().'/upload/appoint/'.$image_tmp[0].'/m_'.$image_tmp['1'];
                $return['s_image'] = file_domain().'/upload/appoint/'.$image_tmp[0].'/s_'.$image_tmp['1'];
            }else{
                $return = file_domain().'/upload/appoint/'.$image_tmp[0].'/'.$image_type.'_'.$image_tmp['1'];
            }
            return $return;
        }else{
            return false;
        }
    }
    
    public function workerList($params = [])
    {
        $where = [
            ['a.appoint_status','=',0],
            ['a.check_status','=',1],
            ['w.status','=',1],
            ['m.status','=',1],
            ['s.status','=',1],
        ];
        if(!empty($params['worker_name'])){
            array_push($where, [
                'w.name' ,'=', $params['worker_name']
            ]);
        }
        if(!empty($params['cat_fid'])){
            array_push($where, [
                'a.cat_fid', '=', $params['cat_fid']
            ]);
        }
        if(!empty($params['province_id'])){
            array_push($where, [
                's.province_id', '=',  $params['province_id']
            ]);
        }
        if(!empty($params['city_id'])){
            array_push($where, [
                's.city_id', '=',  $params['city_id']
            ]);
        }
        if(!empty($params['area_id'])){
            array_push($where, [
                's.area_id', '=',  $params['area_id']
            ]);
        }
        if(!empty($params['circle_id'])){
            array_push($where, [
                's.circle_id', '=',  $params['circle_id']
            ]);
        }
        if(!empty($params['start_score'])){
            array_push($where, [
                'w.all_avg_score' , '>=', $params['start_score']
            ]);
        }
        if(!empty($params['end_score'])){
            array_push($where, [
                'w.all_avg_score', '<=', $params['end_score']
            ]);
        }
        if(!empty($params['payment_status'])){
            array_push($where, [
                'a.payment_status', '=', $params['payment_status']-1
            ]);
        }
        
        $data = MerchantWorkers::alias('w')
            ->join('merchant_workers_appoint wa', 'w.merchant_worker_id = wa.merchant_worker_id')
            ->join('appoint a', 'wa.appoint_id = a.appoint_id')
            ->join('merchant_store s','wa.merchant_store_id = s.store_id')
            ->join('merchant m','m.mer_id = s.mer_id')
            ->where($where)
            ->group('w.merchant_worker_id')
            ->field(
                'w.merchant_worker_id, w.name, w.avatar_path, w.all_avg_score, w.info, w.office_time, w.before_time, 
                w.time_gap, s.long store_long, s.lat store_lat'
            )
            ->paginate($this->pageSize)
            ->each(function ($item)use($params){
                $item->avatar_path  = $item->avatar_path ? replace_file_domain('/upload/appoint/' . str_replace(',', '/', $item->avatar_path )) : '';
                $item->info = $item->info ?: '';
                $item->appoint_time = $this->workTime($item, true);
                $item->distance = 0;
                if (!empty($item->store_long) && !empty($item->store_lat) && !empty($params['lng']) && !empty($params['lat'])) {
                    $location = (new longLat())->baiduToGcj02($item->store_lat, $item->store_long);
                    $distance = getDistance($params['lat'], $params['lng'], $location['lat'], $location['lng']);
                    $item->distance = $distance > 1000 ? bcdiv($distance,1000,2).'km': $distance.'m';
                }
            });
        
        return $data;
    }
    
    public function workerAppointList($workerId,$type=[])
    {
        $where = [
            ['w.merchant_worker_id', '=', $workerId],
            ['a.appoint_status', '=',  0],
            ['a.check_status', '=', 1],
            ['a.start_time', '<', time()],
            ['a.end_time', '>', time()],
        ];
        if($type){
            $where[] = ['ac.is_autotrophic','in',$type];
        }
        $data = Appoint::alias('a')
            ->join('merchant_workers_appoint wa', 'wa.appoint_id = a.appoint_id')
            ->join('merchant_workers w', 'w.merchant_worker_id = wa.merchant_worker_id')
            ->join('merchant_store s','wa.merchant_store_id = s.store_id')
            ->join('appoint_category ac','a.cat_id = ac.cat_id')
            ->where($where)
            ->with(['appoint_product'])
            ->field([
                'a.appoint_id', 'a.appoint_name', 'a.cat_id','a.payment_status','a.pic', 'a.appoint_price','s.store_id','s.name','a.expend_time',
                '(a.appoint_sum + a.virtual_sales) appoint_sum', 'a.appoint_type', 'a.score_all', 'a.is_appoint_price, ac.is_autotrophic'
            ])
            ->paginate($this->pageSize)
            ->each(function ($item){
                $picArr = explode(';',$item['pic']);
                $item['pic'] = (new AppointService())->getImageByPath($picArr[0],'s');

                if (empty($item->is_appoint_price) && floatval($item->appoint_price)<=0) {
                    $item->appoint_price = '面议';
                }

                $specPrice = AppointProduct::where('appoint_id', $item->appoint_id)->column('price');
                if(!empty($specPrice)){
                    $item->appoint_price = min($specPrice);
                }
            });
        
        return $data;
    }

    /**
     * 技师修改订单总价
     * @param $workerId
     * @param $params
     * @return bool
     */
    public function modifyOrderPrice($workerId,$params){
        $order_id = $params['order_id']??0;
        $price = $params['price']??0;
        $note = $params['note']??'';
        $note = $note?trim($note):'';
        $where[] = ['merchant_worker_id','=',$workerId];
        $worker = (new MerchantWorkers())->where($where)->findOrEmpty()->toArray();

        if(!$worker['modify_order_price']){
            throw_exception(L_('没有修改价格的权限！'));
        }
        if(!$note){
            throw_exception(L_('备注必填！'));
        }
        if($price<0){
            throw_exception(L_('金额错误！'));
        }
        $where[] = ['order_id','=',$order_id];
        $now_order = (new AppointOrder())->where($where)->findOrEmpty()->toArray();
        if(!$now_order){
            throw_exception(L_('订单不存在！'));
        }
        if(($now_order['paid']==1&&($now_order['appoint_price']>0||$now_order['product_price']>0))||($now_order['paid']==0&&$now_order['appoint_price']<=0&&$now_order['product_price']<=0)){
            if($now_order['service_status']!=0){
                throw_exception(L_('订单状态不是未服务状态！'));
            }
            if($now_order['is_initiative']>0){
                throw_exception(L_('订单尾款已支付！'));
            }
        }else{
            throw_exception(L_('订单不能修改总价！'));
        }
        if($now_order['payment_money']>0&&$now_order['payment_money']>$price){
            throw_exception(L_('订单总价不能小于定金金额！'));
        }
        if($now_order['product_payment_price']>0&&$now_order['product_payment_price']>$price){
            throw_exception(L_('订单总价不能小于定金金额！'));
        }
        
        $save_log = [
            'worker_id' => $workerId,
            'order_id' => $order_id,
            'old_price' => $now_order['product_id']>0?$now_order['product_price']:$now_order['appoint_price'],
            'now_price' => $price,
            'add_time' => time(),
            'product_id' => $now_order['product_id'],
            'type' => 0,
            'note' => $note,
        ];
        Db::startTrans();
        try {
            if ($now_order['product_id']>0) {
                (new AppointOrder())->where($where)->save(['product_price'=>$price]);
            }else{
                (new AppointOrder())->where($where)->save(['appoint_price'=>$price]);
            }
            (new SystemOrder())->where(['type' => 'appoint', 'order_id' => $order_id])->save(['total_price'=>$price]);
            (new MerchantWorkersModifyOrderLog())->save($save_log);
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            throw_exception(L_($e->getMessage()));
        }

        return true;
    }
	
	public function workerArrive(int $workerId, int $supplyId, $latitude, $longitude): array
	{
		if (!cfg('appoint_visit_sign')) {
			throw_exception('上门打卡未开启，无需校验');
		}
		$arriveMeter = cfg('appoint_visit_sign_meter');
		if (empty($arriveMeter)) {
			throw_exception('上门打卡范围未设置，无需校验');
		}
		$supply = AppointSupply::where(['supply_id' => $supplyId, 'worker_id' => $workerId])->with(['appoint_order'])->find();
		empty($supply) && throw_exception('当前预约服务不存在');
		$appointOrder = $supply->appoint_order;
		$lat = $lng = 0;
		if ($cueField = $appointOrder->cue_field) {
			$lat = $cueField[0]['lat'];
			$lng = $cueField[0]['long'];
		}
		if (empty($lat) || empty($lng)) {
			throw_exception('当前预约服务地址异常');
		}

		$distance = getDistance($lat, $lng, $latitude, $longitude);
		$address = [];
		if($latitude && $longitude) {
			$address = (new AutoAddressService())->cityMatching($latitude, $longitude);
		}
		return [
			'worker_arrived' => $distance <= $arriveMeter,
			'address' => $address['address_addr'] ?? '',
		];
	}

	public function recordServiceInfo(int $workerId, int $supplyId, array $params): bool
	{
		$positionInfo = $params['push_position_info'] ?? [];
		$serviceRecords = $params['service_records'] ?? [];

		$supply = AppointSupply::where(['supply_id' => $supplyId, 'worker_id' => $workerId])->with(['appoint_order.category'])->find();
		empty($supply) && throw_exception('当前预约服务不存在');
		if($supply->status != AppointSupply::RECEIVE) {
			throw_exception('当前预约服务状态非已接单');
		}
		/** @var AppointOrderService $appointOrderService */
		$appointOrderService = app()->make(AppointOrderService::class);
		if ($positionInfo) {
			$supply->push_position_info = $positionInfo;
			$params['content'] = [];
			if($positionInfo['notes']) {
				$params['content'][] = [
					'title' => '备注',
					'value' => $positionInfo['notes'],
					'type'  => 'text'
				];
			}
			if($positionInfo['imgs']) {
				foreach ($positionInfo['imgs'] as &$img){
					$img = replace_file_domain($img);
				}
				$params['content'][] = [
					'title' => '图片',
					'value' => $positionInfo['imgs'],
					'type'  => 'image'
				];
			}
			if(cfg('appoint_visit_must_sign') && (empty($positionInfo['lat']) || empty($positionInfo['lng']))) {
				throw_exception('请上报经纬度打卡信息！');
			}
			if($positionInfo['address']) {
				$params['content'][] = [
					'title' => '打卡地址',
					'value' => "{$positionInfo['address']}({$positionInfo['lng']},{$positionInfo['lat']})",
					'type'  => 'text'
				];
			}
			empty($params['content']) && throw_exception('请上报打卡信息！');
			$appointOrderService->addOrderRecord($supply->mer_id, $supply->order_id, '技师上门打卡', $params);
		}
		$serviceCueField = AppointCategory::where(['cat_id' => AppointOrder::where(['order_id' => $supply->order_id])->value('cat_id', 0)])->value('service_cue_field');
		$recordsContent = [];
		if ($serviceRecords && $serviceCueField && $serviceCueField = unserialize($serviceCueField)) {
			foreach ($serviceCueField as $cueField) {
				$cueFieldContent = [];
				foreach ($serviceRecords as &$cueFieldItem) {
					if ($cueFieldItem['type'] == $cueField['type'] && $cueFieldItem['name'] == $cueField['name']) {
						$type = 'text';
						if ($cueField['type'] == 11) {
							$path = request()->server('DOCUMENT_ROOT') . '/upload/appoint/order_sign/';
							if (!is_dir($path)) {
								mkdir($path, 0777, true);
							}
							$fileName = "appoint_{$workerId}_{$supplyId}";
							$saveTmpPath = base64_to_img($path, $fileName, $cueFieldItem['value'], 'png');
							if($saveTmpPath){
								//判断是否需要上传至云存储
								$file_handle = new FileHandle();
								if($file_handle->check_open_oss()){
									$info = $file_handle->upload($saveTmpPath);
									fdump_api(['info'=>$info],'appoint/image_upload',1);
								}
							}else{
								throw_exception('签名图片上传失败');
							}
							$cueFieldItem['value'] = replace_file_domain(cfg('site_url') . '/upload/appoint/order_sign/' . $fileName . '.png');
							$cueFieldContent = [
								'title' => $cueFieldItem['name'],
								'value' => [$cueFieldItem['value']],
								'type'  => 'image'
							];
						} else {
							if ($cueField['type'] == 12) {
								$type = 'image';
								if($cueFieldItem['value']) {
									foreach ($cueFieldItem['value'] as &$img){
										$img = replace_file_domain($img);
									}
								}
							}
							$cueFieldContent = [
								'title' => $cueFieldItem['name'],
								'value' => $cueFieldItem['value'],
								'type'  => $type
							];
						}
					}
				}
				if ($cueField['iswrite'] && empty($cueFieldContent)) {
					throw_exception('请填写必填项');
				}
				$cueFieldContent && $recordsContent[] = $cueFieldContent;
			}
		}
		if ($recordsContent) {
			$supply->service_records = $serviceRecords;
			$appointOrderService->addOrderRecord($supply->mer_id, $supply->order_id, '技师服务报告', ['content' => $recordsContent]);
		}

		return $supply->save();
	}
}