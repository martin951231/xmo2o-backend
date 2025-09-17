<?php
/**
 * 餐饮排号service
 * Created by vscode.
 * Author: 钱大双
 * Date Time: 2020年12月4日15:13:14
 */

namespace app\foodshop\model\service\store;

use app\foodshop\model\db\FoodshopQueue as FoodshopQueueModel;
use app\foodshop\model\db\FoodshopQueueNotice as FoodshopQueueNoticeModel;

use app\foodshop\model\service\store\FoodshopTableTypeService;
use app\foodshop\model\service\store\MerchantStoreFoodshopService;
use app\foodshop\model\service\order_print\PrintHaddleService;
use app\foodshop\model\service\message\WxMessageService;
use think\Exception;

class FoodshopQueueService
{
    public $foodshopQueueModel = null;

    public function __construct()
    {
        $this->foodshopQueueModel = new FoodshopQueueModel();
    }

    //线下取号列表
    public function getFoodshopQueueListOff($staff)
    {
        $foodshopTableTypeService = new FoodshopTableTypeService();
        $where = [
            ['store_id', '=', $staff['store_id']],
            ['num', '>', '0'],
            ['status', '<>', '0'],
            ['use_time', '>', '0'],
            ['number_prefix', '<>', '']
        ];

        $tableTypeList = $foodshopTableTypeService->getTableTypeListByCondition($where, $order = ['id asc']);
        $data = [];
        foreach ($tableTypeList as $list) {
            $arr['id'] = $list['id'];
            $arr['name'] = $list['name'];
            $arr['people_num'] = L_('X1-X2人', ['X1' => $list['min_people'], 'X2' => $list['max_people']]);
            $arr['num'] = $list['num'];
            $arr['number_prefix'] = $list['number_prefix'];
            $arr['use_time'] = $list['use_time'];
            if (empty($list['number_prefix']) || $list['use_time'] == 0) {
                $arr['type'] = 1;
            } else {
                $arr['type'] = 2;
            }
            $create_time = strtotime(date('Y-m-d 00:00:00'));
            //查询当日排号总数
            $where = [
                ['table_type', '=', $list['id']],
                ['create_time', '>=', $create_time],
            ];
            $total = $this->foodshopQueueModel->getCount($where);
            $arr['total'] = $total;
            $where = [
                ['store_id', '=', $staff['store_id']],
                ['table_type', '=', $list['id']],
                ['status', '=', '0'],
                ['create_time', '>=', $create_time],
            ];
            $field = 'id,number';
            $arr['count'] = $this->foodshopQueueModel->getCount($where);
            $arr['queue_list'] = $this->foodshopQueueModel->getSome($where, $field, $order = ['id asc'], 0, 5);
            $data[] = $arr;
        }
        //判断有没有配置排号小票打印机
        $is_queue = (new MerchantStoreFoodshopService())->checkStoreQueue($staff['store_id'], false, true);
        $returnArr['table_type_list'] = $data;
        $returnArr['is_queue'] = $is_queue == true ? 1 : 0;
        return $returnArr;
    }


    //线上取号列表
    public function getFoodshopQueueListOn($param)
    {
        $foodshopTableTypeService = new FoodshopTableTypeService();
        $where = [
            ['store_id', '=', $param['store_id']],
            ['num', '>', '0'],
            ['status', '<>', '0'],
            ['use_time', '>', '0'],
            ['number_prefix', '<>', '']
        ];

        $tableTypeList = $foodshopTableTypeService->getTableTypeListByCondition($where, ['sort' => 'desc', 'id' => 'asc']);
        $data = [];
        foreach ($tableTypeList as $list) {
            $arr['id'] = $list['id'];
            $arr['store_id'] = $list['store_id'];
            $arr['name'] = $list['name'];
            $arr['use_time'] = $list['use_time'];
            $create_time = strtotime(date('Y-m-d 00:00:00'));
            $where = [
                ['store_id', '=', $param['store_id']],
                ['table_type', '=', $list['id']],
                ['status', '=', '0'],
                ['create_time', '>=', $create_time],
                ['uid', '=', $param['uid']],
            ];
            
            $arr['people_num'] = L_('X1-X2人', ['X1' => $list['min_people'], 'X2' => $list['max_people']]) . '桌';
            $arr['url'] = get_base_url().'pages/store/queue/queueDetail?store_id='.$list['store_id'].'&people_num='.urlencode($arr['people_num']).'&isStoreHomePage=true';
            //查询用户桌台类型是否已有排号记录
            $queue_info = $this->foodshopQueueModel->getOne($where);
            if ($queue_info) {
                $arr['queue_id'] = $queue_info['id'];//1:排队ID

                $arr['url'] .= '&queue_id='.$arr['queue_id'];
                //查询用户前面的排号数据信息
                $where = [
                    ['store_id', '=', $param['store_id']],
                    ['table_type', '=', $list['id']],
                    ['status', '=', '0'],
                    ['create_time', '>=', $create_time],
                    ['id', '<', $queue_info['id']],
                ];
                $count = $this->foodshopQueueModel->getCount($where);
                $arr['count'] = $count;
                if ($count == 0) {
                    $arr['estimate_time'] = 0;
                } else {
                    $arr['estimate_time'] = $count * $list['use_time'];
//                    $list = $this->foodshopQueueModel->getSome($where, true, $order = ['id asc']);
//                    $total = count($list);
//                    $estimate_time = ($list[$total - 1]['use_time'] - time()) / 60;
//                    $arr['estimate_time'] = $estimate_time > 0 ? ceil($estimate_time) : 0;
                }
            } else {
                $arr['queue_id'] = 0;//取号
                $where = [
                    ['store_id', '=', $param['store_id']],
                    ['table_type', '=', $list['id']],
                    ['status', '=', '0'],
                    ['create_time', '>=', $create_time],
                ];
                $count = $this->foodshopQueueModel->getCount($where);
                $arr['count'] = $count;
                if ($count == 0) {
                    $arr['estimate_time'] = 0;
                } else {
//                    $list = $this->foodshopQueueModel->getSome($where, true, $order = ['id asc']);
//                    $total = count($list);
//                    $estimate_time = ($list[$total - 1]['use_time'] - time()) / 60;
//                    $arr['estimate_time'] = $estimate_time > 0 ? ceil($estimate_time) : 0;
                    $arr['estimate_time'] = $count * $list['use_time'];
                }
            }
            $data[] = $arr;
        }
        $returnArr['queue_list'] = $data;
        return $returnArr;
    }


    //取号
    public function addFoodshopQueue($param)
    {
        try {
            $foodshopTableTypeService = new FoodshopTableTypeService();
            if ($param['table_type'] > 0) {//店员移动端或现场取号
                $table_type = $param['table_type'];
            } else {
                if (!isset($param['num']) || $param['num'] == 0) {
                    throw new Exception('缺少就餐人数');
                }
                $where = [
                    ['store_id', '=', $param['store_id']],
                    ['min_people', '>=', $param['num']],
                    ['status', '<>', '0'],
                    ['number_prefix', '<>', ''],
                    ['num', '>', '0'],
                ];
                $tableTypeList = $foodshopTableTypeService->getTableTypeListByCondition($where, $order = ['id asc']);
                if (empty($tableTypeList)) {
                    throw new Exception('未匹配到合适的桌台类型');
                }
                foreach ($tableTypeList as $list) {
                    $table_type = $list['id'];
                    break;
                }
            }

            $beginTime = strtotime(date('Y-m-d 00:00:00'));
            $endTime = strtotime(date('Y-m-d 23:59:59'));
            //线上需判断用户是否重复取号
            if ($param['queue_from'] == 0) {
                $where = [
                    ['store_id', '=', $param['store_id']],
                    ['table_type', '=', $param['table_type']],
                    ['status', '=', '0'],
                    ['create_time', 'between', [$beginTime, $endTime]],
                    ['uid', '=', $param['uid']],
                ];
                //查询用户桌台类型是否已有排号记录
                $queue_info = $this->foodshopQueueModel->getOne($where);
                if ($queue_info) {
                    throw new Exception('您已取号，无需重复取号');
                }
            }

            //桌台类型信息
            $tableTypeInfo = $foodshopTableTypeService->getTableTypeInfoByCondition($where = ['id' => $table_type]);
            if (empty($tableTypeInfo)) {
                throw new Exception('桌台类型不存在');
            }

            if ($tableTypeInfo['status'] == 0) {
                throw new Exception('桌台类型不可用');
            }

            if ($tableTypeInfo['num'] == 0) {
                throw new Exception('暂无桌台');
            }

            $where = [
                ['store_id', '=', $param['store_id']],
                ['table_type', '=', $table_type],
                ['create_time', 'between', [$beginTime, $endTime]]
            ];
            $number_prefix = strtoupper($tableTypeInfo['number_prefix']);
            $new_queue = $this->foodshopQueueModel->getOne($where, true, $order = ['id DESC']);
            if ($new_queue) {
                $number = str_replace($number_prefix, '', $new_queue['number']);
            } else {
                $number = 0;
            }
            $number = intval($number) + 1;
            $new_number = $number_prefix . $number;
            $now_time = time();

            //当前桌型已排号总数
            $where = [
                ['store_id', '=', $param['store_id']],
                ['table_type', '=', $table_type],
                ['status', '=', '0'],
                ['create_time', 'between', [$beginTime, $endTime]]
            ];
            $count = $this->foodshopQueueModel->getCount($where);
            $use_time = $now_time + ceil(($count + 1) / $tableTypeInfo['num']) * $tableTypeInfo['use_time'] * 60;

            $data = [];
            $data['store_id'] = $param['store_id'];
            $data['uid'] = isset($param['uid']) ? $param['uid'] : 0;
            $data['number'] = $new_number;
            $data['status'] = 0;
            $data['create_time'] = $now_time;
            $data['use_time'] = $use_time;
            $data['num'] = isset($param['num']) ? $param['num'] : 0;
            $data['table_type'] = $table_type;
            $data['queue_from'] = $param['queue_from'];
            $data['phone'] = isset($param['phone']) ? $param['phone'] : '';
            $id = $this->foodshopQueueModel->insert_queue($data);
            if (!$id) {
                throw new Exception('取号失败');
            } else {
//                $queue = $this->foodshopQueueModel->getOne($where = ['id' => $id], true);
                $queue = $data;
                $queue['id'] = $id;
                if ($param['queue_from'] == 1) {//线下取号成功打印排号小票
                    $params = [];
                    $params['store_id'] = $param['store_id'];
                    $params['count'] = $count;
                    (new PrintHaddleService())->frontPrintQueue($queue, $params);
                } else {//线上取号成功发送小程序订阅消息
                    $queue['count'] = $count;
                    $queue['people_num'] = L_('X1-X2人', ['X1' => $tableTypeInfo['min_people'], 'X2' => $tableTypeInfo['max_people']]);
                    $this->sendMessage(1, $queue);

                    if ($count == 0) {//如果是线上第一个取号并同时发送到号提醒通知
                        $this->sendMessage(3, $queue);
                    }
                }
            }
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
        return true;
    }

    //叫号、过号
    public function updateFoodshopQueue($param)
    {
        $rs = $this->foodshopQueueModel->updateThis($where = ['id' => $param['id']], $data = ['status' => $param['status']]);
        if ($param['queue_from'] == 1) {
            $where = [];
            $where['id'] = $param['id'];
            $queue_info = $this->getInfo($where);
            $create_time = strtotime(date('Y-m-d 00:00:00'));
            $where = [
                ['store_id', '=', $queue_info['store_id']],
                ['table_type', '=', $queue_info['table_type']],
                ['status', '=', '0'],
                ['create_time', '>=', $create_time],
                ['id', '>', $param['id']],
            ];
            $result = $this->foodshopQueueModel->getSome($where, true, $order = ['id asc']);
            $list = $result->toArray();

            if (!empty($list) && isset($list[2]) && $list[2]['queue_from'] == 0) {
                //桌台类型信息
                $foodshopTableTypeService = new FoodshopTableTypeService();
                $tableTypeInfo = $foodshopTableTypeService->getTableTypeInfoByCondition($where = ['id' => $queue_info['table_type']]);
                $where = [];
                $where['id'] = $list[1]['id'];
                $queue = $this->getInfo($where);
                $queue['count'] = 2;
                $queue['people_num'] = L_('X1-X2人', ['X1' => $tableTypeInfo['min_people'], 'X2' => $tableTypeInfo['max_people']]);
                $this->sendMessage(2, $queue);
            }
        }
        return $rs;
    }

    //线上排号详情
    public function getDetailById($param)
    {
        //查询用户桌台类型是否已有排号记录
        $where = [];
        $where['id'] = $param['id'];
        $queue_info = $this->getInfo($where);
        if (empty($queue_info)) {
            throw new Exception('排号信息不存在');
        }
        $flag = 1;//标识当前排号是否有效
        //当前排号已过号
        if ($queue_info['status'] == 3) {
            $flag = 2;
        }


        $beginTime = strtotime(date('Y-m-d 00:00:00'));
        $endTime = strtotime(date('Y-m-d 23:59:59'));

        if ($flag == 1) {
            $where = [
                ['store_id', '=', $queue_info['store_id']],
                ['table_type', '=', $queue_info['table_type']],
                ['status', '=', '1'],
                ['create_time', 'between', [$beginTime, $endTime]],
                ['id', '>', $param['id']]
            ];
            $rs = $this->foodshopQueueModel->getOne($where, true);
            if ($rs) {
                $flag = 2;
            }
        }

        $return = [];
        $return['store_id'] = '';//店铺ID
        $return['number'] = '';//排队号码
        $return['name'] = '';//桌台类型名称
        $return['min_people'] = 0;//最小使用人数
        $return['max_people'] = 0;//最大使用人数
        $return['count'] = 0;//前面排队人数
        $return['create_time'] = 0;//取号时间，时间戳
        $return['status'] = $flag;//1 有效 2无效
        if ($flag == 1) {//当前排号有效
            //查询用户前面的排号数据信息
            $where = [
                ['store_id', '=', $queue_info['store_id']],
                ['table_type', '=', $queue_info['table_type']],
                ['status', '=', '0'],
                ['create_time', 'between', [$beginTime, $endTime]],
                ['id', '<', $queue_info['id']],
            ];
            $count = $this->foodshopQueueModel->getCount($where);
            //桌台类型信息
            $foodshopTableTypeService = new FoodshopTableTypeService();
            $tableTypeInfo = $foodshopTableTypeService->getTableTypeInfoByCondition($where = ['id' => $queue_info['table_type']]);
            $return['store_id'] = $queue_info['store_id'];
            $return['number'] = $queue_info['number'];
            $return['min_people'] = $tableTypeInfo['min_people'];//最小使用人数
            $return['max_people'] = $tableTypeInfo['max_people'];//最大使用人数
            $return['name'] = $tableTypeInfo['name'];//桌台类型名称
            $return['count'] = $count;
            $return['create_time'] = $queue_info['create_time'];
        }
        $returnArr['detail'] = $return;
        return $returnArr;
    }

    //获取排号基本信息
    public function getInfo($where)
    {
        if (empty($where)) {
            return [];
        }
        $rs = $this->foodshopQueueModel->getOne($where);
        $queue_info = [];
        if ($rs) {
            $queue_info = $rs->toArray();
        }
        return $queue_info;
    }

    /**根据条件删除排号数据
     * @param $param
     * @return mixed
     */
    public function delData($param)
    {
        $beginTime = strtotime(date('Y-m-d 00:00:00'));
        $endTime = strtotime(date('Y-m-d 23:59:59'));
        $where = [
            ['store_id', '=', $param['store_id']],
            ['status', '=', 0],
            ['create_time', 'between', [$beginTime, $endTime]]
        ];
        $res = $this->foodshopQueueModel->del($where);
        return $res;
    }

    /**
     * @param $type 1:取号成功通知 2：排队提醒通知 3：到号提醒通知
     * @param $queue_info  排号信息
     */
    public function sendMessage($type, $queue)
    {
        $params = [];

        $params['uid'] = $queue['uid'];
        $params['store_id'] = $queue['store_id'];
        $params['page'] = '';//排号详情页面 待定
        $params['number'] = $queue['number'];//排队号码
        $params['count'] = $queue['count'];//等待人数
        $params['create_time'] = $queue['create_time'];
        if ($type == 1) {
            $params['type'] = 'queue_success';
            $params['page'] = '/packapp/plat/pages/store/queue/queueDetail?store_id=' . $queue
                ['store_id'] . '&queue_id=' . $queue['id'] . '&people_num=' . $queue['people_num'];
            (new WxMessageService())->sendWxappMessage($params);
        } else {
            $where = ['queue_id' => $queue['id'], 'type' => $type];
            $result = (new FoodshopQueueNoticeModel())->getOne($where);
            $info = [];
            if ($result) {
                $info = $result->toArray();
            }
            if (empty($info)) {//没有排号提醒记录
                if ($type == 2) {
                    $params['type'] = 'queue_notice';
                    $params['remark'] = '您当前还有' . $queue['count'] . '桌，请做好就餐准备';
                } else {
                    $params['type'] = 'queue_complete';
                    $params['remark'] = '您已到号，请尽快到店就餐';
                }
                $params['page'] = get_base_url('pages/store/queue/queueDetail?store_id=' . $queue['store_id'] . '&queue_id=' . $queue['id'] . '&people_num=' . $queue['people_num']);
                $data = [];
                $data['store_id'] = $queue['store_id'];
                $data['uid'] = $queue['uid'];
                $data['openid'] = '';
                $data['status'] = 1;
                $data['create_time'] = time();
                $data['queue_id'] = $queue['id'];
                $data['type'] = $type;
                (new FoodshopQueueNoticeModel())->add($data);
                (new WxMessageService())->sendWxappMessage($params);
            }
        }
    }
}