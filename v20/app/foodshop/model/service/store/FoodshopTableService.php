<?php
/**
 * 餐饮桌台service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/30 11:30
 */

namespace app\foodshop\model\service\store;
use app\foodshop\model\db\FoodshopTable as FoodshopTableModel;
use app\foodshop\model\db\MerchantStoreFoodshop;
use app\foodshop\model\service\order\DiningOrderDetailService;
use app\foodshop\model\service\order\DiningOrderService;
use think\facade\Db;
class FoodshopTableService {
    public $foodshopStoreTableModel = null;
    public $weeks = [];
    public $statusArrForStaff = [];

    public $foodshopTableModel = null;

    public function __construct()
    {
        $this->foodshopTableModel = new FoodshopTableModel();
        $this->weeks = [
            L_('周日'),
            L_('周一'),
            L_('周二'),
            L_('周三'),
            L_('周四'),
            L_('周五'),
            L_('周六')
        ];
        $this->statusArrForStaff = [
            "1" => L_("空台"),
            "2" => L_("就餐中"),
            "3" => L_("点餐中"),
            "4" => L_("待清台"),
        ];
    }

    /**
     * 根据取桌台列表
     * @param $param array
     * @return array
     */
    public function getStaffTableList($param, $staff){
        $returnArr['table_list'] = [];
        $where = [];
        $where[] = ['table.store_id','=',$staff['store_id']];
        if(isset($param['table_id']) && $param['table_id']){
            // 桌台类型
            $where[] = ['table.tid','=',$param['table_id']];
        }

        $whereOrder = [];
        if(isset($param['order_status']) && $param['order_status']){
            $whereOrder[] = [
                'table_id', '>', 0
            ];
            $whereOrder[] = [
                'store_id', '=', $staff['store_id']
            ];
            // 状态 0-全部，1-空台，2-就餐中，3-点餐中, 4-待清台
            switch ($param['order_status']){
                case '1' :
                    $whereOrder[] = ['status', 'between', [20,39]];
                    $tableId = (new DiningOrderService())->getOrderListByCondition($whereOrder);
                    if($tableId){
                        $tableId = array_column($tableId,'table_id');
                        $where[] = ['table.id','not in',implode(',',$tableId)];
                    }
                    $whereOrder = [];
                break;
                case '2' :
                    $whereOrder[] = ['status', 'in', '20,30'];
                break;
                case '3' :
                    $whereOrder[] = ['status', '=', 21];
                break;
                case '4' :

                    $whereOrder[] = ['status', 'in', [20,30]];
                    $orderList = (new DiningOrderService())->getOrderListByCondition($whereOrder);

                    $orderIds = array_column($orderList,'order_id');
//                    var_dump($orderIds);
                    $whereOrder = [
                        ['order_id','in',implode(',',$orderIds)],
                        ['status' , 'in' , '0,1,2'],
                        ['num', 'exp', Db::raw(' > refundNum')],
                    ];
                    $orderDetailList = (new DiningOrderDetailService())->getSome($whereOrder);
                    $orderDetailIds = array_unique(array_column($orderDetailList,'order_id'));
//                    var_dump($orderDetailIds);
                    $orderIdArr = array_diff($orderIds,$orderDetailIds);



                    $whereOrder= [
                        [
                            'table_id', '>', 0
                        ],
                        [
                            'store_id', '=', $staff['store_id']
                        ],
                        [
                        'order_id', 'in', implode(',',$orderIdArr)
                        ]
                    ];

                    break;
            }
        }
//        var_dump($where,$whereOrder);
        // 查询桌台列表
        $rs = $this->getStaffTableArr($where, $whereOrder);
        if(empty($rs)){
            return $rs;
        }
        $orderList = [];
        $tableIds = array_column($rs, 'id');
        if($tableIds){

            // 查询订单
            $where = [
                ['table_id', 'in' ,  implode(',', $tableIds)],
                ['status', 'between',[20,39]]
            ];
            $diningOrderService = new DiningOrderService();
            $orderList = $diningOrderService->getOrderListByCondition($where,['order_id'=>'ASC']);
        }
//        var_dump( $orderList);
        $return = [];
        foreach ($rs as $key => $table){

            //订单数量
            $table['order_count'] = 0;
            // 就餐人数
            $table['dining_count'] = 0;;
            // 状态
            $table['status'] = 1;
            $table['status_str'] = L_('空台');

            $tableType = (new FoodshopTableTypeService())->geTableTypeById($table['tid']);
            $table['table_type_name'] = $tableType['name'];
            foreach ($orderList as $key => $order){

                if($order['table_id'] == $table['id'] ){
                    $table['order_count']++;
                    $table['dining_count'] += $order['book_num'];
                    if($table['status'] == 1){
                        $table['status'] = $diningOrderService->getStaffOrderStatus($order);
                        $table['status_str'] = $this->statusArrForStaff[$table['status']];
                    }
                }
            }

            if($param['order_status']==4 && $table['status']!=4){
                // unset($rs[$key]);
                continue;
            }

            if($param['order_status']==2 && ($table['status']==3 || $table['status']==4)){
                // 就餐中 去掉待清台的订单
                // unset($rs[$key]);
                continue;
            }

            if($param['order_status']==3 && ($table['status']==4 || $table['status']==2)){
                // 点餐中 去掉待清台和就餐中的订单
                // unset($rs[$key]);
                continue;
            }
            $return[] = $table;
        }
        $returnArr['table_list'] = $return;

        return $returnArr;
    }

    /**
     * 根据id获取桌台信息
     * @param $id int 桌台id
     * @return array
     */
    public function getDetail($param){
        if(!$param){
            throw new \think\Exception(L_("缺少参数"),1001);
        }
        
        //餐饮店铺
        $foods_shop = (new MerchantStoreFoodshop())->where(['store_id'=>$param['store_id']])->findOrEmpty()->toArray();
        if(!$foods_shop){
            throw new \think\Exception(L_("店铺不存在！"),1001);
        }

        // 桌台
        $where = [];
        $where['id'] = $param['table_id'];
        $where['store_id'] = $param['store_id'];
        $table = $this->getOne($where);
        if(!$table) {
            throw new \think\Exception(L_("桌台不存在"),1003);
        }

        // 桌台类型
        $tableType = (new FoodshopTableTypeService)->geTableTypeById($table['tid']);
        if(!$tableType) {
            throw new \think\Exception(L_("桌台类型不存在"),1003);
        }

        $returnArr['table_name'] = $table['name'];
        $returnArr['id'] = $table['id'];
        $returnArr['tid'] = $table['tid'];
        $returnArr['table_type_name'] = $tableType['name'];
        $returnArr['min_people'] = $tableType['min_people'];
        $returnArr['max_people'] = $tableType['max_people'];
        $returnArr['scan_order_bg_img'] = $foods_shop['scan_order_bg_img'] ? replace_file_domain($foods_shop['scan_order_bg_img']) : '';
        return $returnArr;
    }


    /**
     * 根据条件返回
     * @param $where array 条件
     * @return array
     */
    public function getOne($where){
        $detail = $this->foodshopTableModel->getOne($where);
        if(!$detail) {
            return [];
        }
        return $detail->toArray();
    }

    /**
     * 根据条件返回总数
     * @param $where array 条件
     * @return array
     */
    public function getCount($where = []){
        $res = $this->foodshopTableModel->getCount($where);
        if(!$res) {
            return 0;
        }
        return $res;
    }


    /**
     * 根据条件返回总数
     * @param $where array 条件
     * @return array
     */
    public function getCountByOrder($where){

        $prefix = config('database.connections.mysql.prefix');
        $sql = 'select count( DISTINCT table_id ) as count from '.$prefix.'dining_order where table_id>0 AND '.$where.' order by order_id ASC';

        $res = $this->foodshopTableModel->query($sql);

        if(!$res) {
            return 0;
        }
        return $res[0]['count'];
    }


    /**
     * 根据id获取桌台信息
     * @param $id int 桌台id
     * @return array
     */
	public function geTableById($id){
        if(!$id){
            return [];
        }
        
        $table = $this->foodshopTableModel->geTableById($id);
        if(!$table) {
            return [];
        }
        return $table->toArray();
    }

    /**
     * 获取热门搜索词列表
     * @param $order array 排序
     * @return array
     */
	public function getBookInfo($param){
        $storeId = isset($param['storeId']) ? intval($param['storeId']) : 0;//店铺ID
        $bookNum = isset($param['bookNum']) ? intval($param['bookNum']) : 0;
        $date = isset($param['date']) ? $param['date'] : 0;
        $time = isset($param['time']) ? $param['time'] : 0;
        $tableType = isset($param['tableType']) ? intval($param['tableType']) : 0;
        $bookTime = $date . ' ' . $time;

        if(!$storeId){
            throw new \think\Exception(L_("缺少参数"));
        }

        try {
            // 获得店铺
            $foodshopStore = (new MerchantStoreFoodshopService())->checkStore($storeId,true , false);
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }
		
		if ($foodshopStore['is_book'] == 0) {
            throw new \think\Exception(L_("该店铺不支预订"));
        }

        // 选择了桌台
        if($tableType){
            if(!$bookTime){
                throw new \think\Exception(L_("预订时间不能为空"));
            }
            $bookInfo['bookTime'] = $bookTime;
            $bookInfo['bookNum'] = $bookNum;
            $bookInfo['tableTypeId'] = $tableType;
        }else{
            // 获得默认选中的信息
            $bookInfo = $this->getDefaultBook($foodshopStore);
        }
        try {
            // 桌台时间数组
            $return = $this->formatData($foodshopStore, strtotime($bookInfo['bookTime']), $bookInfo['bookNum'], $bookInfo['tableTypeId']);
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }
        // 是否显示区号
        $return['international_phone'] = cfg('international_phone') ? cfg('international_phone') : 0;

        // 提前取消时间
        $return['cancel_time'] = $foodshopStore['cancel_time'];
        return $return; 

    }

    
	/**
     * 获得默认选中的桌台信息
	 * @param array $foodshopStore	店铺信息
	 * @return array
	 */
	private function getDefaultBook($foodshopStore){
        //查询可预约的桌台类型
        $where[] = ['store_id' , '=', $foodshopStore['store_id']];
        $where[] = ['num' , '>', 0];
        $order['max_people'] = 'ASC';
        $order['min_people'] = 'ASC';
        $order['id'] = 'DESC';
        $tableTypeList = (new FoodshopTableTypeService())->getTableTypeListByCondition($where,$order);
		if (empty($tableTypeList)) {
            throw new \think\Exception(L_("没有可预约的桌台"));
		}

		// 第一个可用桌台类型
		$tableType = $tableTypeList[0];

        // 预订人数
        $bookNum = isset($tableType['min_people']) ? $tableType['min_people'] : 2;
        
        // 第一个可用桌台类型id
        $tableTypeId = $tableType['id'];

        // 查询桌台类型下的订单
        $where = [];
        $where[] = ['table_type' , '=', $tableTypeId];
        $where[] = ['book_time' , '>', time()];
        $where[] = ['status' ,'>=', '0'];
        $where[] = ['status' ,'<', '40'];
        $orderList = (new DiningOrderService())->getOrderListByCondition($where);
        $orderTableList = [];
        foreach ($orderList as $order) {
            $orderTableList[date('YmdHi', $order['book_time'])][] = $order;
        }

        $orderDateCount = [];
        if ($orderTableList) {
            foreach ($orderTableList as $index => $row) {
                if ($tableType['num'] <= count($row)) {
                    $orderDateCount[$index] = 1;
                } else {
                    $orderDateCount[$index] = 0; 
                }
            }
        }
        
        
        // 可预约第一时间
        $nowCanBookTime = time() + $foodshopStore['advance_time'] * 60;

        // 预约时间间隔
        if(!$foodshopStore['book_time']){
            $foodshopStore['book_time'] = 60;
        }
        $loopTime = $foodshopStore['book_time'] * 60;

        // 预约开始时间
        $startTime = $foodshopStore['book_start'];
        
        // 预约结束时间
        $stopTime = $foodshopStore['book_stop'];

        if ($startTime == '00:00:00' && $stopTime == '00:00:00') {
            $stopTime = '23:59:59';
        }

        // book_day 最长可预约几天后

        $bookTime = 0;
        for ($d = 0; $d <= $foodshopStore['book_day']; $d++) {
            $thisStartTime = strtotime(date('Y-m-d ', strtotime("+{$d} day")) . $startTime);
            $thisStopTime = strtotime(date('Y-m-d ', strtotime("+{$d} day")) . $stopTime);
            if ($thisStartTime < $thisStopTime) {
                for ($t = $thisStartTime; $t <= $thisStopTime; $t += $loopTime) {
                    if ($t < $nowCanBookTime) {
                    } else {
                        if (isset($orderDateCount[date('YmdHi', $t)]) && $orderDateCount[date('YmdHi', $t)]) {
                        } else {
                            $bookTime = date('Y-m-d H:i', $t);
                            break;
                        }
                    }
                }
                if ($bookTime) break;
            } else {
                $stop_time_t = strtotime(date('Y-m-d ', strtotime("+{$d} day")) . '23:59:59');
                for ($t = $thisStartTime; $t <= $stop_time_t; $t += $loopTime) {
                    if ($t < $nowCanBookTime) {
                    } else {
                        if (isset($orderDateCount[date('YmdHi', $t)]) && $orderDateCount[date('YmdHi', $t)]) {
                        } else {
                            $bookTime = date('Y-m-d H:i', $t);
                            break;
                        }
                    }
                }
                if ($bookTime) break;
                $d_t = $d + 1;
                if ($d_t < $foodshopStore['book_day']) {
                    $start_time_t = strtotime(date('Y-m-d ', strtotime("+{$d_t} day")) . '00:00:00');
                    $stop_time_t = strtotime(date('Y-m-d ', strtotime("+{$d_t} day")) . $stopTime);
                    for ($t = $start_time_t; $t <= $stop_time_t; $t += $loopTime) {
                        if ($t < $nowCanBookTime) {
                        } else {
                            if (isset($orderDateCount[date('YmdHi', $t)]) && $orderDateCount[date('YmdHi', $t)]) {
                            } else {
                                $bookTime = date('Y-m-d H:i', $t);
                                break;
                            }
                        }
                    }
                    if ($bookTime) break;
                }
                if ($bookTime) break;
            }
        }

        $returnArr['bookTime'] = $bookTime;//预约时间
        $returnArr['tableTypeId'] = $tableTypeId;//预约桌台
        $returnArr['bookNum'] = $bookNum;//预约人数
        return $returnArr;
    }

	/**
	 * @param array $foodshop	店铺的详情
	 * @param int $bookTime	预定时间，时间戳格式
	 * @param int $book_num		预订人数
	 * @param int $table_type	桌台类型ID
	 * @return array
	 */
	private function formatData($foodshopStore, $bookTime, $bookNum, $tableType, $table_id=0)
	{
		$storeId = $foodshopStore['store_id'];
		//根据预订人数查找对应的桌台
        //查询可预约的桌台类型
        $where[] = ['store_id' , '=', $foodshopStore['store_id']];
        $where[] = ['num' , '>', 0];
        $order['sort'] = 'DESC';
        $order['max_people'] = 'ASC';
        $order['min_people'] = 'ASC';
        $order['id'] = 'DESC';
        $tableTypeList = (new FoodshopTableTypeService())->getTableTypeListByCondition($where,$order);
		
		$typeList = array();
		$typeIds = array();
		foreach ($tableTypeList as $type) {
			if ($type['min_people'] <= $bookNum && ($type['max_people'] >= $bookNum || $type['is_add'] == 1)) {
			    $type['can_book'] = 1;
			}else{
                $type['can_book'] = 0;
                if($type['min_people'] > $bookNum){
                    $type['book_msg'] = L_('X1人起订',['X1'=>$type['min_people']]);
                }elseif($type['max_people'] < $bookNum){
                    $type['book_msg'] = L_('最多X1人',['X1'=>$type['max_people']]);
                }
            }

            $typeList[$type['id']] = $type;
            $typeIds[] = $type['id'];
		}

		if ($typeIds) {
			if (!in_array($tableType, $typeIds)) {
				$tableType = $typeIds[0];
			}
		} else {
            throw new \think\Exception(L_("没有可供选择的桌台"));
		}

        //检验已选的桌台类型的各个时间点的预订情况
        $where = [];
        $where[] = ['table_type' , '=', $tableType];
        $where[] = ['book_time' , '>', time()];
        $where[] = ['status' ,'>=', '0'];
        $where[] = ['status' ,'<', '40'];
        $orderList = (new DiningOrderService())->getOrderListByCondition($where);

		$orderTableList = [];
		foreach ($orderList as $order) {
			$orderTableList[date('YmdHi', $order['book_time'])][] = $order;
        }
        
		$orderDateCount = array();
		if ($orderTableList) {
			foreach ($orderTableList as $index => $row) {
                if ($typeList[$tableType]['num'] <= count($row)) {
                    $orderDateCount[$index] = 1;
                } else {
                    $orderDateCount[$index] = 0;
                }
            
			}
		}

		$timeList = array();
		$dayList = array();
		$nowTime = time() + $foodshopStore['advance_time'] * 60;//开始预约时间
		$foodshopStore['book_time'] = $foodshopStore['book_time'] > 0 ? $foodshopStore['book_time'] : 60;
		$loopTime = $foodshopStore['book_time'] * 60;//预订时间间隔

		$startTime = $foodshopStore['book_start'];
		$stopTime = $foodshopStore['book_stop'];
		if ($startTime == '00:00:00' && $stopTime == '00:00:00') {
			$stopTime = '23:59:59';
		}

		$selectDateFlag = false;
		$errorCode = false; // 当前时间是否可定
		for ($d = 0; $d <= $foodshopStore['book_day']; $d++) {
            $index = date('Ymd', strtotime("+{$d} day"));
            
			//日期的列表
			$dayList[$index] = [
                'date' => date('Y-m-d', strtotime("+{$d} day")), 
                'title' => $this->weeks[date('w', strtotime("+{$d} day"))], 
                'day' => date('m', strtotime("+{$d} day")) .'.' . date('d', strtotime("+{$d} day")),
                'class'=>''
            ];

			//每日可供预约的时间点
			$thisStartTime = strtotime(date('YmdHi', strtotime(date('Y-m-d ', strtotime("+{$d} day")) . $startTime)));
			$thisStopTime = strtotime(date('YmdHi', strtotime(date('Y-m-d ', strtotime("+{$d} day")) . $stopTime)));
			$temp = null;
			if ($thisStartTime < $thisStopTime) {

				$temp['date'] = date('Y-m-d', $thisStartTime);
				for ($t = $thisStartTime; $t <= $thisStopTime; $t += $loopTime) {
					$class = '';
					if ($t < $nowTime) {
						$class = 'End';
						$t_a = array('class' => 'End', 'time' => date('H:i', $t));
					} else {
						if (isset($orderDateCount[date('YmdHi', $t)]) && $orderDateCount[date('YmdHi', $t)]) {
							$class = 'End';
							$t_a = array('class' => 'End', 'time' => date('H:i', $t));
						} else {
							$t_a = array('class' => '', 'time' => date('H:i', $t));
						}
					}
					if ($bookTime == $t) {
						if ($class == 'End') {
                            $errorCode = true;
//							return array('err_code' => true, 'msg' => '您选择餐桌在该时间不能再预订了！');
							$bookTime += $loopTime;
						} else {
							$selectDateFlag = true;
                            $t_a = array('class' => 'on', 'time' => date('H:i', $t));
                            $dayList[$index]['class'] = 'on';
						}
					}
					$temp['time_list'][] = $t_a;
				}
				$timeList[$index] = $temp;
			} else {
				$stop_time_t = strtotime(date('Y-m-d ', strtotime("+{$d} day")) . '23:59:59');
				$temp['date'] = date('Y-m-d', $thisStartTime);
				for ($t = $thisStartTime; $t <= $stop_time_t; $t += $loopTime) {
					$class = '';
					if ($t < $nowTime) {
						$class = 'End';
						$t_a = array('class' => 'End', 'time' => date('H:i', $t));
					} else {
						if (isset($orderDateCount[date('YmdHi', $t)]) && $orderDateCount[date('YmdHi', $t)]) {
							$class = 'End';
							$t_a = array('class' => 'End', 'time' => date('H:i', $t));
						} else {
							$t_a = array('class' => '', 'time' => date('H:i', $t));
						}
					}
					if ($bookTime == $t) {
						if ($class == 'End') {
                            throw new \think\Exception(L_("您选择餐桌在该时间不能再预订了！"));
							$bookTime += $loopTime;
						} else {
							$selectDateFlag = true;
							$t_a = array('class' => 'on', 'time' => date('H:i', $t));
                            $dayList[$index]['class'] = 'on';
						}
					}
					$temp['time_list'][] = $t_a;
				}
				$timeList[$index] = $temp;
				$d_t = $d + 1;
				if ($d_t < $foodshopStore['book_day']) {
					$start_time_t = strtotime(date('Y-m-d ', strtotime("+{$d_t} day")) . '00:00:00');
					$stop_time_t = strtotime(date('Y-m-d ', strtotime("+{$d_t} day")) . $stop_time);
					$temp['date'] = date('Y-m-d', $start_time_t);
					for ($t = $start_time_t; $t <= $stop_time_t; $t += $loopTime) {
						$class = '';
						if ($t < $nowTime) {
							$class = 'End';
							$t_a = array('class' => 'End', 'time' => date('H:i', $t));
						} else {
							if (isset($orderDateCount[date('YmdHi', $t)]) && $orderDateCount[date('YmdHi', $t)]) {
								$class = 'End';
								$t_a = array('class' => 'End', 'time' => date('H:i', $t));
							} else {
								$t_a = array('class' => '', 'time' => date('H:i', $t));
							}
						}
						if ($bookTime == $t) {
							if ($class == 'End') {
                                throw new \think\Exception(L_("您选择餐桌在该时间不能再预订了！"));
								$bookTime += $loopTime;
							} else {
								$selectDateFlag = true;
								$t_a = array('class' => 'on', 'time' => date('H:i', $t));
                                $dayList[$index]['class'] = 'on';
							}
						}
						$temp['time_list'][] = $t_a;
					}
					$timeList[date('Ymd', strtotime("+{$d_t} day"))] = $temp;
				}
			}
		}
		if ($selectDateFlag) {
            $data = [];
			$data['now_book'] = [
                'm' => date('m', $bookTime), 
                'd' => date('d', $bookTime), 
                'w' => $this->weeks[date('w', $bookTime)], 
                'o' => date('H:i', $bookTime), 
                'selectdate' => date('Y-m-d', $bookTime)
            ];
			$data['table_list'] = array_values($typeList);
			$data['day_list'] = $dayList;
			$data['time_list'] = $timeList;
			$data['now_book']['table_type'] = $tableType;
			$data['now_book']['book_time'] = date('Y-m-d H:i', $bookTime);
			$data['now_book']['book_num'] = $bookNum;
			$data['now_book']['book_price'] = floatval($typeList[$tableType]['deposit']);
			$data['err_code'] = $errorCode;
			$data['msg'] = L_('您选择餐桌在该时间不能再预订了！');
			return $data;
		} else {
            throw new \think\Exception(L_("没有可供预约的时间"));
		}

	}

    /**
     * 根据店铺ID获取所有桌台列表
     * @param $storeId
     * @author 张涛
     * @date 2020/07/10
     */
    public function getTableByStoreId($storeId)
    {
        return $this->foodshopTableModel->getTableByStoreId($storeId);
    }

    /**
     * 根据条件获取桌台列表
     * @param $storeId
     * @author 张涛
     * @date 2020/07/10
     */
    public function getSome($where = [], $field = true, $order=true, $page=0, $limit=0)
    {
        $res = $this->foodshopTableModel->getSome($where, $field, $order, $page, $limit);

        if(!$res){
            return [];
        }

        return $res->toArray();
    }

    public function getStaffTableArr($where = [], $whereOrder = [])
    {
        $res = $this->foodshopTableModel->getStaffTableArr($where, $whereOrder);
//        var_dump($this->foodshopTableModel->getLastSql());
        if(!$res){
            return [];
        }

        return $res->toArray();
    }

    /**
     * 保存桌台
     * @param $storeId
     * @author 张涛
     * @date 2020/07/10
     */
    public function saveTable($post)
    {

        $id = $post['id'] ?? 0;
        //先判断桌台类型是否存在
        $storeId = $post['store_id'] ?? 0;
        $tid = $post['tid'] ?? 0;
        $tableType = (new FoodshopTableTypeService)->geTableTypeById($tid);
        if (empty($tableType)) {
            throw new \think\Exception("桌台类型不存在");
        }
        $data = [
            'tid' => $post['tid'],
            'store_id' => $post['store_id'],
            'name' => $post['name'],
            'sort' => $post['sort']??0,
        ];

        $updateTableTypeIds = [];
        if ($id > 0) {
            //修改
            $item = $this->foodshopTableModel->where('id', $id)->findOrEmpty()->toArray();
            if (empty($item)) {
                throw new \think\Exception("桌台记录不存在");
            }

            $rs = $this->foodshopTableModel->where('id', $id)->update($data);
            $updateTableTypeIds = [$item['tid'], $post['tid']];
        } else {
            //新增
            $rs = $this->foodshopTableModel->insert($data);
            $updateTableTypeIds = [$post['tid']];
        }
        $updateTableTypeIds = array_unique($updateTableTypeIds);
        foreach ($updateTableTypeIds as $id) {
            (new FoodshopTableTypeService())->updateNumById($id);
        }
        return true;
    }

    /**
     * 更新桌台
     * @param $storeId
     * @author chenxiang
     * @date 2020/07/10
     */
    public function updateTable($post)
    {
        $id = $post['id'] ?? 0;

        if ($id > 0) {
            //修改
            $rs = $this->foodshopTableModel->where('id', $id)->update($post);
        }
        if ($rs === false) {
            throw new \think\Exception("保存失败");
        }
        return true;
    }

    /**
     * 删除
     * @author 张涛
     * @date 2020/07/10
     */
    public function delTable($id, $storeId = 0)
    {
        if ($id < 1) {
            throw new \think\Exception("请选择一条记录");
        }
        $where = ['id' => $id];
        $storeId > 0 && $where['store_id'] = $storeId;
        $thisTable = $this->foodshopTableModel->where($where)->findOrEmpty();
        if(empty($thisTable)){
            throw new \think\Exception("桌台不存在");
        }

        $rs = $this->foodshopTableModel->where($where)->delete();
        if (!$rs) {
            throw new \think\Exception("删除失败");
        }
        (new FoodshopTableTypeService())->updateNumById($thisTable['tid']);
        return true;
    }

}