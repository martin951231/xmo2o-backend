<?php
//骑手配送记录service

namespace app\mall\model\service;

use app\common\model\db\MerchantStore;
use app\mall\model\db\MallRiderRecord;
use app\mall\model\service\MerchantStoreMallService;
use app\deliver\model\service\DeliverSupplyService;
use app\merchant\model\service\MerchantStoreOpenTimeService;

class MallRiderService
{
	public $rider_status = [
		'0' => '店员接单',
		'1' => '骑手接单',
		'2' => '骑手到店',
		'3' => '骑手取货',
		'4' => '已送达',
		'5' => '配送失败',
	];
	/**
	 * 增加骑手配送轨迹
	 * @param [string] $type    order|periodic  (order=普通订单 periodic=周期购业务)
	 * @param [int] $send_id 订单ID  跟$type类型走
	 * @param [int] $status  骑手状态 1=接单 2=到店 3=取货 4=送达 5=失败
	 * @param string $note    描述
	 * @param string $lng     经度
	 * @param string $lat     纬度
	 * @param array $rider_info     配送员信息，格式如下：
	 *                              [
	 *                              	id:1,//ID
	 *                              	phone:'',//配送员手机
	 *                              	name:''//配送员姓名
	 *                              ]
	 */
	public function addRecord($type, $send_id, $status, $note = '', $lng = '', $lat = '', $rider_info = []){
		if(!in_array($type, ['order', 'periodic'])){
			throw new \think\Exception("type传值不正确");			
		}
		if(empty($send_id)){
			throw new \think\Exception("send_id传值不正确");				
		}
		if($status < 0 || $status > 5){
			throw new \think\Exception("status传值不正确");				
		}
        $note = isset($rider_info['note'])&&$rider_info['note'] ? $note.'('.$rider_info['note'].')' : $note;
		$data = [
			'type' => $type,
			'send_id' => $send_id,
			'status' => $status,
			'note' => $note,
			'lng' => $lng,
			'lat' => $lat,
			'rider_id' => $rider_info['id'] ?? 0,
			'rider_name' => $rider_info['name'] ?? '',
			'rider_phone' => $rider_info['phone'] ?? '',
			'addtime' => $rider_info['add_time']??time()
		];
		return (new MallRiderRecord)->add($data);
	}

	/**
	 * 获取配送轨迹
	 * @param [string] $type    order|periodic  (order=普通订单 periodic=周期购业务)
	 * @param [int] $send_id 订单ID  跟$type类型走
	 * @return [type]          [description]
	 */
	public function getRecord($type, $send_id, $order = 'addtime desc'){
		if(!in_array($type, ['order', 'periodic'])){
			throw new \think\Exception("type传值不正确");			
		}
		if(empty($send_id)){
			throw new \think\Exception("send_id传值不正确");				
		}
		$where = [
			['type', '=', $type],
			['send_id', '=', $send_id],
		];
		$data = (new MallRiderRecord)->getSome($where, true, $order);
		if($data){
			return $data->toArray();
		}
		else{
			return [];
		}
	}

	/**
	 * 计算运费
	 * @param  [type] $start_lng 起送点经度
	 * @param  [type] $start_lat 起送点纬度
	 * @param  [type] $end_lng   终点经度
	 * @param  [type] $end_lat   终点纬度
	 * @param  [type] $time  用户选择的时间  格式如： 时间戳1,时间戳2
	 * @return [type]            [description]
	 */
	public function computeFee($start_lng, $start_lat, $end_lng, $end_lat, $time){
		if(empty($start_lng) || empty($start_lat) || empty($end_lng) || empty($end_lat) || empty($time)){
			return 0;
		}
		list($start, $end) = explode(',', $time);
		$money = (new DeliverSupplyService)->calculationFreight(['lat'=>$start_lat, 'lng'=>$start_lng], ['lat'=>$end_lat, 'lng'=>$end_lng], date('Y-m-d H:i:s', $start), 1);

		return ['money' => $money['freight'], 'distance' => $money['distance']];
	}

	/**
	 * 获取前端送货时间清单
	 * @param  [type] $store_id  店铺ID
	 * @param  [type] $start_lng 起送点经度
	 * @param  [type] $start_lat 起送点纬度
	 * @param  [type] $end_lng   终点经度
	 * @param  [type] $end_lat   终点纬度
	 * @return [type]            [description]
	 */
	public function getTimeList($store_id, $start_lng, $start_lat, $end_lng, $end_lat){
		if(empty($store_id) || empty($start_lng) || empty($start_lat) || empty($end_lng) || empty($end_lat)){
			return [];
		}
		/**算法
		* 当前时间+店铺配货时间+骑手预计时间  （起点）
		* 过滤掉店铺非营业时间
		* 过滤掉骑手非营业时间
		* 得出结果
		* */
		$current_time = time();//当前时间
		$distribution_time = 0;//配货时间
		$rider_time = 0;//骑手需要的时间
		$space = 2*3600;//2个小时作为间隔时间
		$step_days = 5;//一共可以选择的是5天

		//获取配货时间
		$mall_store_info = (new MerchantStoreMallService)->getStoremallInfo($store_id, 'stockup_time,is_houseman,horseman_type');
		if(isset($mall_store_info['stockup_time'])){
			$stockup_time = explode(';', $mall_store_info['stockup_time']);
			if(count($stockup_time) === 3){
				$distribution_time = $stockup_time[0]*86400 + $stockup_time[1]*3600 + $stockup_time[2]*60;
			}
		}

		$horseman_type = 1; //1-平台配送员，2-商家配送员
        $mer_id = (new MerchantStore())->where(['store_id'=>$store_id])->value('mer_id');
		if($mall_store_info['is_houseman']&&$mall_store_info['horseman_type']==1){
            $horseman_type = 2;
        }
		//获取骑手时间
		$rider_info = (new DeliverSupplyService)->getDeliverTimeSetting(['lat'=>$start_lat, 'lng'=>$start_lng], ['lat'=>$end_lat, 'lng'=>$end_lng], $horseman_type, $mer_id);
		if(!$rider_info['status']){
			return [];
		}
		$rider_time = $rider_info['time']*60;

		//获取店铺营业时间
		$store_open_time = (new MerchantStoreOpenTimeService)->getTimeByWeek($store_id);
		if(empty($store_open_time)){
			return [];
		}

		$return = [];
		//过滤店铺营业时间和配送员营业时间
		$real_send_time = get_format_number($current_time + $distribution_time + $rider_time, 0);
		//向上取整（避免时间杂乱 已30分钟为整数）
		$yu30 = $real_send_time%1800;
		if($yu30 > 0){
			$real_send_time += 1800-$yu30;
		}
		$start_time = $real_send_time;
		$end_time = $real_send_time;
		$last_ymd = date('Ymd', $real_send_time);
		$i = 0;
		while (true) {
			if($i > 1000) break;//防止500溢出
			if($step_days === 0){ 
				unset($return[$last_ymd]);
				break;
			}

			$start_time = $end_time;
			if(date('H:i', $start_time) == '23:59'){
				$start_time = strtotime(date('Y-m-d 00:00:00', $start_time+61));
			}
			$end_time = $start_time + $space;

			//判断时间段在不在营业时间内
			if($this->checkTime($start_time, $end_time, $store_open_time, $rider_info['business_time_list'])){
				if(date('Ymd', $start_time) != $last_ymd){
					$last_ymd = date('Ymd', $start_time);
					$return[$last_ymd] = [
						'title' => $last_ymd == date('Ymd') ? '今天' : ($last_ymd == date('Ymd', time()+86400) ? '明天' : date('Y-m-d', $start_time)),
						'list' => [
							[
								'text' => date('H:i', $start_time).'-'.date('H:i', $end_time),
								'val' => $start_time.','.$end_time
							]
						]
					];
					$step_days--;
				}
				elseif(!isset($return[$last_ymd])){
					$return[$last_ymd] = [
						'title' => $last_ymd == date('Ymd') ? '今天' : ($last_ymd == date('Ymd', time()+86400) ? '明天' : date('Y-m-d', $start_time)),
						'list' => [
							[
								'text' => date('H:i', $start_time).'-'.date('H:i', $end_time),
								'val' => $start_time.','.$end_time
							]
						]
					];
				}
				else{
					$return[$last_ymd]['list'][] = [
						'text' => date('H:i', $start_time).'-'.date('H:i', $end_time),
						'val' => $start_time.','.$end_time
					];
				}
			}

			$i++;
		}
		return array_values($return);
	}

	//判断时间段是否在合法营业时间内
	private function checkTime(&$start_time, &$end_time, $store_open_time, $business_time_list){
		$ymd = date('Y-m-d', $start_time);

		$week = date('w', $start_time);
		if(!isset($store_open_time[$week])){
			return false;
		}

		$cross = false;//这一轮是否检查通过
		foreach ($store_open_time[$week] as $value) {
			if(($value['open_time'] == '00:00:00' && $value['close_time'] == '00:00:00') || ($value['open_time'] == '00:00:00' && $value['close_time'] == '00:00:59')){
				$cross = true;
				break;
			}
			$value_start = strtotime($ymd.' '.$value['open_time']);
			$value_end = strtotime($ymd.' '.$value['close_time']);
			if($start_time <= $value_start && $end_time >= $value_end){
				$start_time = $value_start;
				$end_time = $value_end;
				$cross = true;
			}
			elseif($start_time <= $value_start && $end_time <= $value_end && $end_time > $value_start){
				$start_time = $value_start;
				$cross = true;
			}
			elseif($start_time >= $value_start && $end_time >= $value_end && $start_time < $value_end){
				$end_time = $value_end;
				$cross = true;
			}
			elseif($start_time >= $value_start && $end_time <= $value_end){
				$cross = true;
			}
			elseif($end_time <= $value_start){
				continue;//这段时间不合法
			}
			elseif($start_time >= $value_end){
				continue;//这段时间不合法
			}
		}
		if(!$cross){
			return false;
		}


		$cross = false;//这一轮是否检查通过
		foreach ($business_time_list as $value) {
			if(($value['start_time'] == '00:00:00' && $value['end_time'] == '00:00:00') || ($value['start_time'] == '00:00:00' && $value['end_time'] == '00:00:59')){
				$cross = true;
				break;
			}
			$value_start = strtotime($ymd.' '.$value['start_time']);
			$value_end = strtotime($ymd.' '.$value['end_time']);
			if($start_time <= $value_start && $end_time >= $value_end){
				$start_time = $value_start;
				$end_time = $value_end;
				$cross = true;
			}
			elseif($start_time <= $value_start && $end_time <= $value_end && $end_time > $value_start){
				$start_time = $value_start;
				$cross = true;
			}
			elseif($start_time >= $value_start && $end_time >= $value_end && $start_time < $value_end){
				$end_time = $value_end;
				$cross = true;
			}
			elseif($start_time >= $value_start && $end_time <= $value_end){
				$cross = true;
			}
			elseif($end_time <= $value_start){
				continue;//这段时间不合法
			}
			elseif($start_time >= $value_end){
				continue;//这段时间不合法
			}
		}
		if(!$cross){
			return false;
		}
		return true;
	}
}