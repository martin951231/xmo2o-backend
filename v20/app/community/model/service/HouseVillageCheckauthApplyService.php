<?php


namespace app\community\model\service;

use app\common\model\service\weixin\TemplateNewsService;
use app\community\model\db\HouseNewOrderLog;
use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\HouseNewPayOrderModifyRecord;
use app\community\model\db\HouseNewRepairWorksOrder;
use app\community\model\db\HouseNewRepairWorksOrderLog;
use app\community\model\db\HouseVillageCheckauthApply;
use app\community\model\db\HouseVillageCheckauthDetail;
use app\community\model\db\HouseVillageCheckauthSet;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseWorker;
use app\community\model\db\HouseNewChargeProject;
use app\community\model\service\HouseNewCashierService;
use app\community\model\service\HouseVillageCheckauthSetService;
use app\community\model\service\HouseVillageCheckauthDetailService;
use app\community\model\service\workorder\HouseCommunityTransferOrderService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseAppJpushInfoService;
use app\consts\HouseWorkConst;
use think\Exception;

class HouseVillageCheckauthApplyService
{

    public function getOneData($where, $field = '*')
    {

        $db_VillageCheckauthApply = new HouseVillageCheckauthApply();
        $info = $db_VillageCheckauthApply->getOne($where, $field);
        return $info;
    }

    public function getApplyCount($where = array())
    {

        $db_VillageCheckauthApply = new HouseVillageCheckauthApply();
        $count = $db_VillageCheckauthApply->getCount($where);
        $count = $count > 0 ? $count : 0;
        return $count;
    }

    public function getCheckauthApplyList($charge_types = array(), $check_level_info = array(), $where = array(), $field = '*', $page = 1, $page_size = 10, $orderby = 'id DESC')
    {
        $returnArr = array('list' => array(), 'count' => 0, 'page_size' => 10);
        $db_VillageCheckauthApply = new HouseVillageCheckauthApply();
        $applysObj = $db_VillageCheckauthApply->getListData($where, $field, $page, $page_size, $orderby);
        if ($applysObj && !$applysObj->isEmpty()) {
            $applys = $applysObj->toArray();
            $count = $db_VillageCheckauthApply->getCount($where);
            $count = $count > 0 ? $count : 0;
            $returnArr['count'] = $count;
            $statusArr = array('未审核', '审核中', '审核通过', '审核未通过');
            $projectArr = array();
            $houseVillageService = new HouseVillageService();
            $order_log = new HouseNewOrderLog();
            $houseVillageCheckauthDetailDb = new HouseVillageCheckauthDetail();

            foreach ($applys as $kk => $avv) {
                $extra_data = $avv['extra_data'] ? json_decode($avv['extra_data'], true) : '';
                $apply_current_start_time = $extra_data && isset($extra_data['apply_current_start_time']) ? $extra_data['apply_current_start_time'] : 0;
                $apply_current_end_time = $extra_data && isset($extra_data['apply_current_end_time']) ? $extra_data['apply_current_end_time'] : 0;
                unset($applys[$kk]['extra_data']);
                $applys[$kk]['status_str'] = isset($statusArr[$avv['status']]) ? $statusArr[$avv['status']] : '未知';
                $applys[$kk]['service_start_time_str'] = $avv['service_start_time'] > 0 ? date('Y-m-d H:i:s', $avv['service_start_time']) : '';
                $applys[$kk]['service_end_time_str'] = $avv['service_end_time'] > 0 ? date('Y-m-d H:i:s', $avv['service_end_time']) : '';
                $charge_type_name = isset($charge_types[$avv['charge_type']]) ? $charge_types[$avv['charge_type']] : '';
                $applys[$kk]['charge_type_name'] = $charge_type_name;
                $project_name = '';
                if ($avv['project_id'] > 0 && isset($projectArr[$avv['project_id']])) {
                    $project_name = $projectArr[$avv['project_id']];
                } else {
                    $whereProjectArr = array(['id', '=', $avv['project_id']], ['village_id', '=', $avv['village_id']]);
                    $chargeProjectInfo = $this->getOneChargeProjectInfo($whereProjectArr, 'id,name');
                    $project_name = $chargeProjectInfo ? $chargeProjectInfo['name'] : '';
                    $projectArr[$avv['project_id']] = $project_name;
                }
                $applys[$kk]['applyCurrentServiceTime'] = '---';
                if ($apply_current_start_time > 100 && $apply_current_end_time > 100) {
                    $apply_current_start_time = $apply_current_start_time + 100;
                    $applys[$kk]['applyCurrentServiceTime'] = date('Y-m-d', $apply_current_start_time) . ' 00:00:00 至 ' . date('Y-m-d H:i:s', $apply_current_end_time);
                }
                $applys[$kk]['project_name'] = $project_name;
                $applys[$kk]['room_address'] = '';
                $applys[$kk]['add_time_str'] = date('Y-m-d H:i:s', $avv['add_time']);
                $applys[$kk]['apply_time_str'] = $avv['apply_time'] > 100 ? date('Y-m-d H:i:s', $avv['apply_time']) : '';
                $applys[$kk]['status_color'] = 'color: #000;font-weight: bold;';
                if ($avv['status'] == 2) {
                    $applys[$kk]['status_color'] = 'color: green;font-weight: bold;';
                } else if ($avv['status'] == 3) {
                    $applys[$kk]['status_color'] = 'color: red;font-weight: bold;';
                }
                if ($avv['room_id'] > 0) {
                    $user_address = $houseVillageService->getSingleFloorRoom(0, 0, 0, $avv['room_id'], $avv['village_id']);
                    $applys[$kk]['room_address'] = $user_address;
                }
                $applys[$kk]['currentServiceTime'] = '';
                if ($avv['project_id'] > 0 && $avv['room_id'] > 0) {
                    $whereArr = array(['project_id', '=', $avv['project_id']], ['room_id', '=', $avv['room_id']], ['village_id', '=', $avv['village_id']]);
                    $orderLogObj = $order_log->getOne($whereArr, '*', 'id DESC');
                    if ($orderLogObj && !$orderLogObj->isEmpty()) {
                        $orderLog = $orderLogObj->toArray();
                        $service_end_time = '';
                        if ($orderLog['service_end_time'] > 100) {
                            $service_end_time = date('Y-m-d H:i:s', $orderLog['service_end_time']);
                        }
                        $service_start_time = '';
                        if ($orderLog['service_start_time'] > 100 && $orderLog['service_start_time'] < $orderLog['service_end_time']) {
                            $service_start_time = date('Y-m-d H:i:s', $orderLog['service_start_time']);
                        } else {
                            $service_start_time = $service_end_time;
                        }
                        if ($service_end_time) {
                            $applys[$kk]['currentServiceTime'] = $service_start_time . ' 至 ' . $service_end_time;
                        }
                    }
                }

                $applys[$kk]['my_check_status'] = 0;
                if ($check_level_info && isset($check_level_info['wid']) && !empty($check_level_info['wid'])) {
                    $checkauthApplyWhere = array('village_id' => $avv['village_id'], 'wid' => $check_level_info['wid'], 'apply_id' => $avv['id']);
                    $checkDetail = $houseVillageCheckauthDetailDb->getOne($checkauthApplyWhere);
                    if (!empty($checkDetail) && $checkDetail['status'] == 0) {
                        $applys[$kk]['my_check_status'] = 1;  //需要审核
                    } elseif (!empty($checkDetail) && $checkDetail['status'] == 1) {
                        $applys[$kk]['my_check_status'] = 2;  //已审核通过
                    } elseif (!empty($checkDetail) && $checkDetail['status'] == 2) {
                        $applys[$kk]['my_check_status'] = 3;  //已审核未通过
                    }
                }
            }
            $returnArr['list'] = $applys;
        }

        return $returnArr;
    }

    public function checkOrderStatus($order_id)
    {
        $db_house_new_pay_order = new HouseNewPayOrder();
        $orderxTmp = $db_house_new_pay_order->get_one(['order_id' => $order_id], 'check_apply_id,check_status');
        if (!isset($orderxTmp['check_apply_id']) || !$orderxTmp['check_apply_id']) {
            return ['err' => 0, 'msg' => '无待审核业务'];
        }
        $houseVillageCheckauthApplyService = new HouseVillageCheckauthApplyService();
        $orderApply = $houseVillageCheckauthApplyService->getOneData(['id' => intval($orderxTmp['check_apply_id'])], 'id, village_id, xtype');
        $orderApplyType = isset($orderApply['xtype']) ? trim($orderApply['xtype']) : '';
        if ($orderxTmp['check_status'] == 1 && $orderApplyType == 'order_discard' && $orderxTmp['check_apply_id'] > 0) {
            return ['err' => 1, 'msg' => '订单作废申请审核中！'];
        } elseif ($orderxTmp['check_status'] == 2 && $orderApplyType == 'order_refund' && $orderxTmp['check_apply_id'] > 0) {
            return ['err' => 1, 'msg' => '订单退款申请审核中！'];
        } elseif ($orderxTmp['check_status'] == 5 && $orderApplyType == 'modify_money_check' && $orderxTmp['check_apply_id'] > 0) {
            return ['err' => 1, 'msg' => '订单金额修改申请审核中！'];
        } elseif ($orderxTmp['check_status'] == 6 && $orderApplyType == 'order_paytime_check' && $orderxTmp['check_apply_id'] > 0) {
            return ['err' => 1, 'msg' => '订单支付时间修改申请审核中！'];
        }
        return ['err' => 0, 'msg' => '无待审核业务'];
    }

    public function getOneAddApply($whereArr = array(), $field = '*', $orderBy = 'id desc')
    {
        $db_VillageCheckauthApply = new HouseVillageCheckauthApply();
        $oneApply = $db_VillageCheckauthApply->getOne($whereArr, $field, $orderBy);
        return $oneApply;
    }

    public function getOnePayOrderInfo($whereArr = array(), $field = '*', $orderBy = 'order_id DESC')
    {
        $db_house_new_pay_order = new HouseNewPayOrder();
        $orderxTmp = $db_house_new_pay_order->get_one($whereArr, $field, $orderBy);
        if ($orderxTmp && !$orderxTmp->isEmpty()) {
            $orderxTmp = $orderxTmp->toArray();
        } else {
            $orderxTmp = array();
        }
        return $orderxTmp;
    }

    public function getOneChargeProjectInfo($whereArr = array(), $field = '*')
    {
        $houseNewChargeProjectDb = new HouseNewChargeProject();
        $chargeProjectObj = $houseNewChargeProjectDb->getOne($whereArr, $field);
        $chargeProject = array();
        if ($chargeProjectObj && !$chargeProjectObj->isEmpty()) {
            $chargeProject = $chargeProjectObj->toArray();
        }
        return $chargeProject;
    }

    public function addDirectApply($orderRefundCheckArr = array())
    {
        $db_VillageCheckauthApply = new HouseVillageCheckauthApply();
        $insert_idd = $db_VillageCheckauthApply->addApply($orderRefundCheckArr);
        return $insert_idd;
    }

    public function addApply($orderRefundCheckArr = array(), $extra = array())
    {
        if (empty($orderRefundCheckArr)) {
            return false;
        }
        $templateNewsService = new TemplateNewsService();
        $houseAppJpushInfoService = new HouseAppJpushInfoService();
        $village_id = $orderRefundCheckArr['village_id'];
        $property_id = $orderRefundCheckArr['property_id'];
        $order_id = isset($orderRefundCheckArr['order_id']) ? $orderRefundCheckArr['order_id'] : 0;
        $db_VillageCheckauthApply = new HouseVillageCheckauthApply();
        $orderRefundCheckArr['add_time'] = time();
        $xtypeSet = 'order_refund_check';
        $project_name = isset($extra['project_name']) ? $extra['project_name'] : '';
        if ($orderRefundCheckArr['xtype'] == 'transfer_other_work') {
            // 工单转单至其他小区
            $xtypeSet = 'work_order_transfer_check';
            $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
            $where_order = [];
            $where_order[] = ['order_id', '=', $order_id];
            $order_info = $db_house_new_repair_works_order->getOne($where_order);
            if ($order_info && !is_array($order_info)) {
                $order_info = $order_info->toArray();
            }
            if (empty($order_info)) {
                return false;
            } elseif (in_array($order_info['event_status'], [28, 29])) {
                return false;
            } elseif ($order_info['check_status'] == 1) {
                return false;
            }
            fdump_api(['order_info' => $order_info], '00addApply', 1);
        } else if ($orderRefundCheckArr['xtype'] == 'order_servicetime_check') {
            $xtypeSet = 'order_servicetime_check';
        } else {
            $db_house_new_pay_order = new HouseNewPayOrder();
            $orderxTmp = $db_house_new_pay_order->get_one(['order_id' => $order_id], 'check_apply_id,check_status');
            if ($orderxTmp && !$orderxTmp->isEmpty()) {
                $orderxTmp = $orderxTmp->toArray();
            }
            if (empty($orderxTmp)) {
                return false;
            } elseif ($orderxTmp['check_status'] == 1 && $orderRefundCheckArr['xtype'] == 'order_discard' && $orderxTmp['check_apply_id'] > 0) {
                return false;
            } elseif ($orderxTmp['check_status'] == 2 && $orderRefundCheckArr['xtype'] == 'order_refund' && $orderxTmp['check_apply_id'] > 0) {
                return false;
            } elseif ($orderxTmp['check_status'] == 5 && $orderRefundCheckArr['xtype'] == 'modify_money_check' && $orderxTmp['check_apply_id'] > 0) {
                return false;
            } elseif ($orderxTmp['check_status'] == 6 && $orderRefundCheckArr['xtype'] == 'order_paytime_check' && $orderxTmp['check_apply_id'] > 0) {
                return false;
            }
        }
        $insert_idd = $db_VillageCheckauthApply->addApply($orderRefundCheckArr);
        if ($insert_idd > 0) {
            switch ($orderRefundCheckArr['xtype']) {
                case 'order_discard':
                case 'order_refund':
                    $xtypeSet = 'order_refund_check';
                    break;
                case 'modify_money_check':
                    $xtypeSet = 'order_modify_check';
                    break;
                case 'transfer_other_work':
                    $xtypeSet = 'work_order_transfer_check';
                case 'order_servicetime_check':
                    $xtypeSet = 'order_servicetime_check';
                    break;
                case 'order_paytime_check':
                    $xtypeSet = 'order_paytime_check';
                    break;
            }
            //添加一条审核记录
            if (isset($extra['checkauth_set'])) {
                $checkauthSet = $extra['checkauth_set'];
            } else {
                $houseVillageCheckauthSetService = new HouseVillageCheckauthSetService();
                $orderRefundCheckWhere = array('village_id' => $village_id, 'xtype' => $xtypeSet);
                $checkauthSet = $houseVillageCheckauthSetService->getOneCheckauthSet($orderRefundCheckWhere);
            }
            fdump_api(['checkauthSet' => $checkauthSet], '00addApply', 1);
            if (!empty($checkauthSet['check_level'])) {
                $level = -1;
                if (isset($extra['wid']) && ($extra['wid'] > 0)) {
                    foreach ($checkauthSet['check_level'] as $vv) {
                        if ($vv['level_wid'] == $extra['wid']) {
                            $level = $vv['level_v'];
                        }
                    }
                }
                $level_tmp = $level + 1;
                $houseVillageCheckauthDetailService = new HouseVillageCheckauthDetailService();
                $db_houseWorker = new HouseWorker();
                fdump_api(['level_tmp' => $level_tmp], '00addApply', 1);
                if ($level_tmp == 0) {
                    //取第一条数据，没有自动过审数据
                    $level_wid = $checkauthSet['check_level']['0']['level_wid'];
                    $whereArr = array('wid' => $level_wid, 'village_id' => $village_id);
                    $field = 'wid,village_id,phone,name,openid,status,type,property_id,people_type,department_id,account';
                    $worker = $db_houseWorker->get_one($whereArr, $field);
                    if (!$worker || $worker->isEmpty()) {
                        $worker = '';
                    } else {
                        $worker = $worker->toArray();
                    }
                    $detailArr = array('apply_id' => $insert_idd, 'property_id' => $property_id);
                    $detailArr['order_id'] = $order_id;
                    $detailArr['village_id'] = $village_id;
                    $detailArr['wid'] = $level_wid;
                    $detailArr['level'] = 0;
                    $detailArr['status'] = 0;
                    $detailArr['extra_data'] = !empty($worker) ? json_encode($worker, JSON_UNESCAPED_UNICODE) : '';
                    $idd = $houseVillageCheckauthDetailService->addDetail($detailArr);
                    if ($orderRefundCheckArr['xtype'] != 'order_servicetime_check') {
                        $otherData = array('village_id' => $village_id, 'wid' => $level_wid);
                        $houseAppJpushInfoService->handleJpushInfoSend($otherData, 'checkauth_apply_tips');
                    }
                    fdump_api(['detailArr' => $detailArr], '00addApply', 1);

                    if ($orderRefundCheckArr['xtype'] == 'order_discard') {
                        $orderUpdateArr = array('check_status' => 1, 'check_apply_id' => $insert_idd);
                        $db_house_new_pay_order->saveOne(['order_id' => $order_id], $orderUpdateArr);
                    } elseif ($orderRefundCheckArr['xtype'] == 'order_refund') {
                        $orderUpdateArr = array('check_status' => 2, 'check_apply_id' => $insert_idd);
                        $db_house_new_pay_order->saveOne(['order_id' => $order_id], $orderUpdateArr);
                    } elseif ($orderRefundCheckArr['xtype'] == 'modify_money_check') {
                        $orderUpdateArr = array('check_status' => 5, 'check_apply_id' => $insert_idd);
                        $db_house_new_pay_order->saveOne(['order_id' => $order_id], $orderUpdateArr);
                    } elseif ($orderRefundCheckArr['xtype'] == 'transfer_other_work') {
                        $orderUpdateArr = array('check_status' => 1, 'apply_time' => time(), 'check_apply_id' => $insert_idd, 'check_time' => '', 'check_reason' => '');
                        $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
                        $where_order = [];
                        $where_order[] = ['order_id', '=', $order_id];
                        $db_house_new_repair_works_order->saveOne($where_order, $orderUpdateArr);
                    } elseif ($orderRefundCheckArr['xtype'] == 'order_servicetime_check') {

                    } elseif ($orderRefundCheckArr['xtype'] == 'order_paytime_check') {
                        $orderUpdateArr = array('check_status' => 6, 'check_apply_id' => $insert_idd);
                        $db_house_new_pay_order->saveOne(['order_id' => $order_id], $orderUpdateArr);
                    }
                    if (!empty($worker) && !empty($worker['openid'])) {
                        $xtypeStr = '订单退款申请';
                        if ($orderRefundCheckArr['xtype'] == 'order_discard') {
                            $xtypeStr = '订单作废申请';
                        }
                        if ($orderRefundCheckArr['xtype'] == 'modify_money_check') {
                            $xtypeStr = '订单金额修改申请';
                        }
                        if ($orderRefundCheckArr['xtype'] == 'transfer_other_work') {
                            $xtypeStr = '工单转单申请';
                        }
                        if ($orderRefundCheckArr['xtype'] == 'order_paytime_check') {
                            $xtypeStr = '修改已支付订单支付时间';
                        }
                        $href = cfg('site_url') . '/packapp/community/pages/Community/reviewRefund/myApproval';
                        $apply_name = $orderRefundCheckArr['apply_name'];
                        $address = '无';
                        if ($orderRefundCheckArr['xtype'] == 'order_servicetime_check') {
                            $xtypeStr = '房间计费时间修改申请';
                            $href = '';
                            $user_info = (new HouseVillageUserBindService())->getBindInfo([['vacancy_id', '=', $orderRefundCheckArr['room_id']], ['type', 'in', '0,3'], ['status', '=', 1]], 'single_id,floor_id,layer_id,vacancy_id,village_id');
                            $address = (new HouseVillageService())->getSingleFloorRoom($user_info['single_id'], $user_info['floor_id'], $user_info['layer_id'], $user_info['vacancy_id'], $user_info['village_id']);
                        } elseif ($order_id > 0) {
                            $orderArr = $db_house_new_pay_order->get_one(['order_id' => $order_id], 'name,room_id');
                            if ($orderArr && !$orderArr->isEmpty()) {
                                $orderArr = $orderArr->toArray();
                                if ($orderArr && !empty($orderArr['name'])) {
                                    $apply_name = $orderArr['name'];
                                }
                                if ($orderArr['room_id']) {
                                    $user_info = (new HouseVillageUserBindService())->getBindInfo([['vacancy_id', '=', $orderArr['room_id']], ['type', 'in', '0,3'], ['status', '=', 1]], 'single_id,floor_id,layer_id,vacancy_id,village_id');
                                    $address = (new HouseVillageService())->getSingleFloorRoom($user_info['single_id'], $user_info['floor_id'], $user_info['layer_id'], $user_info['vacancy_id'], $user_info['village_id']);
                                }
                            }
                        }
                        $datamsg = [
                            'tempKey' => 'OPENTM417740354',//todo 类目模板OPENTM417740354
                            'dataArr' => [
                                'href' => $href,
                                'wecha_id' => $worker['openid'],
                                'first' => '审核提醒',
                                'keyword1' => $xtypeStr,
                                'keyword2' => $apply_name,
                                'keyword3' => date('Y-m-d H:i:s', $orderRefundCheckArr['add_time']),
                                'remark' => '您有一个新的申请消息需要审核！',
                                'new_info' => [//新版本发送需要的信息
                                    'tempKey' => '43258',//新模板号
                                    'thing2' => $xtypeStr,//流程名称
                                    'thing9' => $apply_name,//发起人
                                    'time10' => date('Y-m-d H:i:s', $orderRefundCheckArr['add_time']),//发起时间
                                    'thing7' => $address,//房源名称
                                ],
                            ]
                        ];
                        $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
                    }
                } else {
                    //有自动过审数据
                    $auto_level = $level_tmp - 1;
                    $nowtime = time();
                    fdump_api(['auto_level' => $auto_level], '00addApply', 1);
                    foreach ($checkauthSet['check_level'] as $cvv) {
                        if ($cvv['level_v'] <= $auto_level) {
                            $whereArr = array('wid' => $cvv['level_wid'], 'village_id' => $village_id);
                            $field = 'wid,village_id,phone,name,openid,status,type,property_id,people_type,department_id,account';
                            $worker = $db_houseWorker->get_one($whereArr, $field);
                            if (!$worker || $worker->isEmpty()) {
                                $worker = '';
                            } else {
                                $worker = $worker->toArray();
                            }
                            $detailArr = array('apply_id' => $insert_idd, 'property_id' => $property_id);
                            $detailArr['order_id'] = $order_id;
                            $detailArr['village_id'] = $village_id;
                            $detailArr['wid'] = $cvv['level_wid'];
                            $detailArr['level'] = $cvv['level_v'];
                            $detailArr['status'] = 1;
                            $detailArr['apply_time'] = $nowtime;
                            $detailArr['add_time'] = $nowtime;
                            $detailArr['bak'] = '自动过审';
                            $detailArr['extra_data'] = !empty($worker) ? json_encode($worker, JSON_UNESCAPED_UNICODE) : '';
                            $idd = $houseVillageCheckauthDetailService->addDetail($detailArr);

                            fdump_api(['detailArr' => $detailArr], '00addApply', 1);
                        }
                    }
                    //需要审核的 处理
                    if (isset($checkauthSet['check_level'][$level_tmp])) {
                        $level_wid = $checkauthSet['check_level'][$level_tmp]['level_wid'];
                        $whereArr = array('wid' => $level_wid, 'village_id' => $village_id);
                        $field = 'wid,village_id,phone,name,openid,status,type,property_id,people_type,department_id,account';
                        $worker = $db_houseWorker->get_one($whereArr, $field);
                        if (!$worker || $worker->isEmpty()) {
                            $worker = '';
                        } else {
                            $worker = $worker->toArray();
                        }
                        $detailArr = array('apply_id' => $insert_idd, 'property_id' => $property_id);
                        $detailArr['order_id'] = $order_id;
                        $detailArr['village_id'] = $village_id;
                        $detailArr['wid'] = $level_wid;
                        $detailArr['level'] = $level_tmp;
                        $detailArr['status'] = 0;
                        $detailArr['extra_data'] = !empty($worker) ? json_encode($worker, JSON_UNESCAPED_UNICODE) : '';
                        $idd = $houseVillageCheckauthDetailService->addDetail($detailArr);
                        if ($orderRefundCheckArr['xtype'] != 'order_servicetime_check') {
                            $otherData = array('village_id' => $village_id, 'wid' => $level_wid);
                            $houseAppJpushInfoService->handleJpushInfoSend($otherData, 'checkauth_apply_tips');
                        }
                        if ($orderRefundCheckArr['xtype'] == 'order_discard') {
                            $orderUpdateArr = array('check_status' => 1, 'check_apply_id' => $insert_idd);
                            $db_house_new_pay_order->saveOne(['order_id' => $order_id], $orderUpdateArr);
                        } elseif ($orderRefundCheckArr['xtype'] == 'order_refund') {
                            $orderUpdateArr = array('check_status' => 2, 'check_apply_id' => $insert_idd);
                            $db_house_new_pay_order->saveOne(['order_id' => $order_id], $orderUpdateArr);
                        } elseif ($orderRefundCheckArr['xtype'] == 'modify_money_check') {
                            $orderUpdateArr = array('check_status' => 5, 'check_apply_id' => $insert_idd);
                            $db_house_new_pay_order->saveOne(['order_id' => $order_id], $orderUpdateArr);
                        } elseif ($orderRefundCheckArr['xtype'] == 'transfer_other_work') {
                            $orderUpdateArr = array('check_status' => 1, 'apply_time' => time(), 'check_apply_id' => $insert_idd, 'check_time' => '', 'check_reason' => '');
                            $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
                            $where_order = [];
                            $where_order[] = ['order_id', '=', $order_id];
                            $db_house_new_repair_works_order->saveOne($where_order, $orderUpdateArr);
                        } elseif ($orderRefundCheckArr['xtype'] == 'order_servicetime_check') {

                        } elseif ($orderRefundCheckArr['xtype'] == 'order_paytime_check') {
                            $orderUpdateArr = array('check_status' => 6, 'check_apply_id' => $insert_idd);
                            $db_house_new_pay_order->saveOne(['order_id' => $order_id], $orderUpdateArr);
                        }
                        if (!empty($worker) && !empty($worker['openid'])) {
                            $xtypeStr = '订单退款申请';
                            if ($orderRefundCheckArr['xtype'] == 'order_discard') {
                                $xtypeStr = '订单作废申请';
                            }
                            if ($orderRefundCheckArr['xtype'] == 'modify_money_check') {
                                $xtypeStr = '订单金额修改申请';
                            }
                            if ($orderRefundCheckArr['xtype'] == 'transfer_other_work') {
                                $xtypeStr = '工单转单申请';
                            }
                            if ($orderRefundCheckArr['xtype'] == 'order_paytime_check') {
                                $xtypeStr = '修改已支付订单支付时间';
                            }
                            $href = cfg('site_url') . '/packapp/community/pages/Community/reviewRefund/myApproval';
                            $apply_name = $orderRefundCheckArr['apply_name'];

                            $address = '无';
                            if ($orderRefundCheckArr['xtype'] == 'order_servicetime_check') {
                                $xtypeStr = '房间计费时间修改申请';
                                $href = '';
                                $user_info = (new HouseVillageUserBindService())->getBindInfo([['vacancy_id', '=', $orderRefundCheckArr['room_id']], ['type', 'in', '0,3'], ['status', '=', 1]], 'single_id,floor_id,layer_id,vacancy_id,village_id');
                                $address = (new HouseVillageService())->getSingleFloorRoom($user_info['single_id'], $user_info['floor_id'], $user_info['layer_id'], $user_info['vacancy_id'], $user_info['village_id']);
                            } elseif ($order_id > 0) {
                                $orderArr = $db_house_new_pay_order->get_one(['order_id' => $order_id], 'name,room_id');
                                if ($orderArr && !$orderArr->isEmpty()) {
                                    $orderArr = $orderArr->toArray();
                                    if ($orderArr && !empty($orderArr['name'])) {
                                        $apply_name = $orderArr['name'];
                                    }
                                    if ($orderArr['room_id']) {
                                        $user_info = (new HouseVillageUserBindService())->getBindInfo([['vacancy_id', '=', $orderArr['room_id']], ['type', 'in', '0,3'], ['status', '=', 1]], 'single_id,floor_id,layer_id,vacancy_id,village_id');
                                        $address = (new HouseVillageService())->getSingleFloorRoom($user_info['single_id'], $user_info['floor_id'], $user_info['layer_id'], $user_info['vacancy_id'], $user_info['village_id']);
                                    }
                                }
                            }
                            $datamsg = [
                                'tempKey' => 'OPENTM417740354',//todo 类目模板OPENTM417740354
                                'dataArr' => [
                                    'href' => $href,
                                    'wecha_id' => $worker['openid'],
                                    'first' => '审核提醒',
                                    'keyword1' => $xtypeStr,
                                    'keyword2' => $apply_name,
                                    'keyword3' => date('Y-m-d H:i:s', $orderRefundCheckArr['add_time']),
                                    'remark' => '您有一个新的申请消息需要审核！',
                                    'new_info' => [//新版本发送需要的信息
                                        'tempKey' => '43258',//新模板号
                                        'thing2' => $xtypeStr,//流程名称
                                        'thing9' => $apply_name,//发起人
                                        'time10' => date('Y-m-d H:i:s', $orderRefundCheckArr['add_time']),//发起时间
                                        'thing7' => $address,//房源名称
                                    ],
                                ]
                            ];
                            $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
                        }
                    } else {
                        //全部自动过审了
                        $updateArr = array('status' => 2, 'apply_time' => time());
                        $db_VillageCheckauthApply->updateApply(['id' => $insert_idd], $updateArr);
                        if ($orderRefundCheckArr['xtype'] == 'order_discard') {
                            $orderUpdateArr = array('check_status' => 3, 'check_apply_id' => $insert_idd);
                            $db_house_new_pay_order->saveOne(['order_id' => $order_id], $orderUpdateArr);
                        } elseif ($orderRefundCheckArr['xtype'] == 'order_refund') {
                            $orderUpdateArr = array('check_status' => 3, 'check_apply_id' => $insert_idd);
                            $db_house_new_pay_order->saveOne(['order_id' => $order_id], $orderUpdateArr);
                        } elseif ($orderRefundCheckArr['xtype'] == 'modify_money_check') {
                            $orderUpdateArr = array('check_status' => 3, 'check_apply_id' => $insert_idd);
                            $db_house_new_pay_order->saveOne(['order_id' => $order_id], $orderUpdateArr);
                        } elseif ($orderRefundCheckArr['xtype'] == 'transfer_other_work') {
                            $orderUpdateArr = array('check_status' => 2, 'apply_time' => time(), 'check_apply_id' => $insert_idd, 'check_time' => time(), 'check_reason' => '自动过审');
                            $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
                            $where_order = [];
                            $where_order[] = ['order_id', '=', $order_id];
                            $db_house_new_repair_works_order->saveOne($where_order, $orderUpdateArr);
                        } elseif ($orderRefundCheckArr['xtype'] == 'order_servicetime_check') {
                            $order_log = new HouseNewOrderLog();
                            $logOrder = [
                                'order_type' => $orderRefundCheckArr['charge_type'],
                                'order_name' => $project_name,
                                'project_id' => $orderRefundCheckArr['project_id'],
                                'room_id' => $orderRefundCheckArr['room_id'],
                                'village_id' => $village_id,
                                'property_id' => $property_id,
                                'desc' => '通过申请修改计费时间',
                                'service_start_time' => $orderRefundCheckArr['service_start_time'],
                                'service_end_time' => $orderRefundCheckArr['service_end_time'],
                                'add_time' => time()
                            ];
                            $log_id = $order_log->addOne($logOrder);
                            $db_VillageCheckauthApply->updateApply(['id' => $insert_idd], array('other_relation_id' => $log_id));
                        } elseif ($orderRefundCheckArr['xtype'] == 'order_paytime_check') {
                            $orderUpdateArr = array('check_status' => 3, 'check_apply_id' => $insert_idd);
                            $db_house_new_pay_order->saveOne(['order_id' => $order_id], $orderUpdateArr);
                        }
                    }
                }
            }
        }
        return $insert_idd;
    }

    //更新数据
    public function updateApply($where = array(), $updateArr = array())
    {
        if (empty($where) || empty($updateArr)) {
            return false;
        }
        $db_VillageCheckauthApply = new HouseVillageCheckauthApply();
        return $db_VillageCheckauthApply->updateApply($where, $updateArr);
    }

    //审核操作
    public function verifyCheckauthApply($order = array(), $verifyData = array(), $check_level_info = array(), $check_apply_id = 0)
    {
        $db_VillageCheckauthApply = new HouseVillageCheckauthApply();
        $where = array('id' => $order['check_apply_id'], 'village_id' => $order['village_id'], 'order_id' => $order['order_id']);
        $where['xtype'] = $verifyData['xtype'];
        $xtype = $verifyData['xtype'];
        $now_login = [];
        if (isset($verifyData['now_login'])) {
            $now_login = $verifyData['now_login'];
            unset($verifyData['now_login']);
        }
        $login_role = 0;
        if (isset($verifyData['login_role'])) {
            $login_role = intval($verifyData['login_role']);
            unset($verifyData['login_role']);
        }
        $log_uid = 0;
        if (isset($verifyData['log_uid'])) {
            $log_uid = intval($verifyData['log_uid']);
            unset($verifyData['log_uid']);
        }
        unset($verifyData['xtype']);
        $houseAppJpushInfoService = new HouseAppJpushInfoService();
        $village_id = $order['village_id'];
        $tmpApply = $db_VillageCheckauthApply->getOne($where);
        fdump_api(['tmpApply' => $tmpApply, 'where' => $where, 'order' => $order], 'verifyCheckauthApply', 1);
        $houseVillageCheckauthDetailService = new HouseVillageCheckauthDetailService();
        $db_house_new_pay_order = new HouseNewPayOrder();
        $templateNewsService = new TemplateNewsService();
        if (empty($tmpApply) || empty($check_level_info) || $check_level_info['wid'] < 1) {
            throw new \think\Exception("审核申请信息不存在！");
        }
        $detailWhere = array('apply_id' => $tmpApply['id'], 'order_id' => $tmpApply['order_id'], 'wid' => $check_level_info['wid'], 'status' => 0);
        $detailData = $houseVillageCheckauthDetailService->getOneData($detailWhere);
        if (empty($detailData)) {
            throw new \think\Exception("审核信息不存在！");
        }
        $nowtime = time();
        $verifyData['apply_time'] = $nowtime;
        $houseNewCashierService = new HouseNewCashierService();
        if ($verifyData['status'] == 1 && !isset($check_level_info['check_level'][$detailData['level'] + 1])) {
            if (!in_array($xtype, ['transfer_other_work', 'order_servicetime_check'])) {
                $orderInfo = $houseNewCashierService->getInfo(['order_id' => $tmpApply['order_id']]);
            } else {
                $orderInfo = [];
            }
            if ($xtype == 'modify_money_check' && !empty($orderInfo)) {
                // 没有拦截
                fdump_api(['xtype' => $xtype, 'msg' => '没有拦截'], 'verifyCheckauthApply', 1);
            } elseif (!empty($orderInfo)) {
                $projectInfo = $houseNewCashierService->getProjectInfo(['id' => $orderInfo['project_id']], 'type');
                if ($projectInfo['type'] == 2) {
                    //查询最新未缴账单
                    $subject_id_arr = $houseNewCashierService->getNumberArr(['charge_type' => $orderInfo['order_type'], 'status' => 1], 'id');
                    if (!empty($subject_id_arr)) {
                        $getProjectArr = $houseNewCashierService->getProjectArr(['subject_id' => $subject_id_arr, 'type' => 2, 'status' => 1], 'id');
                    }
                    if (!empty($orderInfo['position_id'])) {
                        $pay_where = ['is_discard' => 1, 'is_paid' => 2, 'position_id' => $orderInfo['position_id'], 'order_type' => $orderInfo['order_type']];
                        if (isset($getProjectArr) && !empty($getProjectArr)) {
                            $pay_where['project_id'] = $getProjectArr;
                        }
                        $pay_order_info = $houseNewCashierService->getInfo($pay_where, 'order_id,project_id,service_start_time,service_end_time', 'service_end_time DESC,order_id DESC');
                    } else {
                        $pay_where = ['is_discard' => 1, 'is_paid' => 2, 'room_id' => $orderInfo['room_id'], 'order_type' => $orderInfo['order_type']];
                        if (isset($getProjectArr) && !empty($getProjectArr)) {
                            $pay_where['project_id'] = $getProjectArr;
                        }
                        $pay_order_info = $houseNewCashierService->getInfo($pay_where, 'order_id,project_id,service_start_time,service_end_time', 'service_end_time DESC,order_id DESC');
                    }

                    //判断当前订单是否是最新的订单
                    if (cfg('new_pay_order') == 1 && (!empty($pay_order_info) && $pay_order_info['order_id'] != $orderInfo['order_id'])) {
                        if ($tmpApply['xtype'] == 'order_discard') {
                            throw new \think\Exception('当前账单无法作废,请先作废最新的账单');
                        }
                    }
                }
            }
        }
        $ret = $houseVillageCheckauthDetailService->updateDetail(array('id' => $detailData['id'], 'apply_id' => $tmpApply['id']), $verifyData);
        if (!$ret) {
            throw new \think\Exception("审核数据有误，审核失败！");
        }
        if ($verifyData['status'] == 2) {
            //审核不通过后处理
            if ($tmpApply['xtype'] == 'order_refund') {
                //退款
                $updateArr = array('status' => 3, 'apply_time' => $nowtime);
                $db_VillageCheckauthApply->updateApply(array('id' => $tmpApply['id'], 'order_id' => $order['order_id']), $updateArr);

                $orderUpdateArr = array('check_status' => 4);
                $db_house_new_pay_order->saveOne(['order_id' => $order['order_id']], $orderUpdateArr);
                //	check_status 1作废审核中2退款审核中3审核通过4审核未通过
                return array('check_status' => 4, 'check_apply_id' => $order['check_apply_id'], 'wid' => $check_level_info['wid']);
            } elseif ($tmpApply['xtype'] == 'order_discard') {
                //作废
                $updateArr = array('status' => 3, 'apply_time' => $nowtime);
                $db_VillageCheckauthApply->updateApply(array('id' => $tmpApply['id'], 'order_id' => $order['order_id']), $updateArr);

                $orderUpdateArr = array('check_status' => 4);
                $db_house_new_pay_order->saveOne(['order_id' => $order['order_id']], $orderUpdateArr);
                //	check_status 1作废审核中2退款审核中3审核通过4审核未通过
                return array('check_status' => 4, 'check_apply_id' => $order['check_apply_id'], 'wid' => $check_level_info['wid']);
            } elseif ($tmpApply['xtype'] == 'modify_money_check') {

                fdump_api(['xtype' => $xtype, 'msg' => '审核不通过后处理'], 'verifyCheckauthApply', 1);
                //修改费用
                $updateArr = array('status' => 3, 'apply_time' => $nowtime);
                $db_VillageCheckauthApply->updateApply(array('id' => $tmpApply['id'], 'order_id' => $order['order_id']), $updateArr);

                $orderUpdateArr = array('check_status' => 4);
                $db_house_new_pay_order->saveOne(['order_id' => $order['order_id']], $orderUpdateArr);
                //	check_status 1作废审核中2退款审核中3审核通过4审核未通过
                return array('check_status' => 4, 'check_apply_id' => $order['check_apply_id'], 'wid' => $check_level_info['wid']);
            } elseif ($tmpApply['xtype'] == 'transfer_other_work') {

                fdump_api(['$xtype' => $xtype, 'order' => $order, 'msg' => '工单转单审核不通过后处理'], 'verifyCheckauthApply', 1);
                //修改费用
                $updateArr = array('status' => 3, 'apply_time' => $nowtime);
                $db_VillageCheckauthApply->updateApply(array('id' => $tmpApply['id'], 'order_id' => $order['order_id']), $updateArr);

                $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
                $where_order = [];
                $where_order[] = ['order_id', '=', $order['order_id']];
                $bak = isset($verifyData['bak']) ? trim($verifyData['bak']) : '';
                $orderUpdateArr = array('check_status' => 3, 'check_time' => time());
                if ($bak) {
                    $orderUpdateArr['check_reason'] = $bak;
                }
                $log_name = 'refuse_work_change_check';
                $operator_type = 30;
                switch ($login_role) {
                    case HouseWorkConst::MANAGE_APP_VILLAGE_WORK:
                        $operator_type = 30;
                        break;
                    case HouseWorkConst::MANAGE_APP_VILLAGE_ADMIN:
                        $operator_type = 43;
                        break;
                    case HouseWorkConst::MANAGE_APP_PROPERTY_USER:
                        $operator_type = 42;
                        break;
                    case HouseWorkConst::MANAGE_APP_PROPERTY_ADMIN:
                        $operator_type = 41;
                        break;
                }
                $extra_data = isset($detailData['extra_data']) ? json_decode($detailData['extra_data'], true) : [];
                $order_content = '工作人员转单审核拒绝';
                $event_status = isset($extra_data['event_status']) && intval($extra_data['event_status']) > 0 ? intval($extra_data['event_status']) : 20;
                $worker_id = isset($extra_data['worker_id']) && intval($extra_data['worker_id']) > 0 ? intval($extra_data['worker_id']) : 0;

                $orderUpdateArr['event_status'] = $event_status;
                $worker_id && $orderUpdateArr['worker_id'] = $worker_id;
                $db_house_new_repair_works_order->saveOne($where_order, $orderUpdateArr);
                $log_data = [
                    'order_id' => $order['order_id'],
                    'log_name' => $log_name,
                    'village_id' => isset($order['village_id']) ? intval($order['village_id']) : '',
                    'property_id' => isset($order['property_id']) ? intval($order['property_id']) : '',
                    'log_operator' => isset($now_login['login_name']) ? $now_login['login_name'] : '',
                    'log_phone' => isset($now_login['login_phone']) ? $now_login['login_phone'] : '',
                    'operator_type' => $operator_type,
                    'operator_id' => $log_uid,
                    'log_uid' => $order['uid'] ? $order['uid'] : 0,
                    'log_content' => $order_content ? $order_content : '',
                    'log_imgs' => '',
                    'evaluate' => 0,
                    'add_time' => time(),
                ];
                $db_house_new_repair_works_order_log = new HouseNewRepairWorksOrderLog();
                $where_count = [];
                $where_count[] = ['order_id', '=', $order['order_id']];
                $where_count[] = ['log_name', '=', strval($log_data['log_name'])];
                $log_num = $db_house_new_repair_works_order_log->getCount($where_count);
                if (!$log_num) {
                    $log_num = 1;
                } else {
                    $log_num = intval($log_num) + 1;
                }
                $log_data['log_num'] = $log_num;
                $log_data['change_reason'] = '工作人员转单审核拒绝';
                $log_id = (new HouseNewRepairService())->addRepairLog($log_data);
                //	check_status 1作废审核中2退款审核中3审核通过4审核未通过
                return array('check_status' => 4, 'check_apply_id' => $order['check_apply_id'], 'wid' => $check_level_info['wid']);
            } else if ($tmpApply['xtype'] == 'order_servicetime_check') {
                $updateArr = array('status' => 3, 'apply_time' => $nowtime);
                $db_VillageCheckauthApply->updateApply(array('id' => $tmpApply['id'], 'xtype' => 'order_servicetime_check'), $updateArr);
                return array('check_status' => 4, 'check_apply_id' => $order['check_apply_id'], 'wid' => $check_level_info['wid']);
            } else if ($tmpApply['xtype'] == 'order_paytime_check') {
                $updateArr = array('status' => 3, 'apply_time' => $nowtime);
                $db_VillageCheckauthApply->updateApply(array('id' => $tmpApply['id'], 'xtype' => 'order_paytime_check'), $updateArr);
                $orderUpdateArr = array('check_status' => 4);
                $db_house_new_pay_order->saveOne(['order_id' => $order['order_id']], $orderUpdateArr);
                $houseNewPayOrderModifyRecord = new HouseNewPayOrderModifyRecord();
                $houseNewPayOrderModifyRecord->saveOneData(array('apply_id' => $tmpApply['id']), array('check_status' => 3, 'check_time' => $nowtime));
                return array('check_status' => 4, 'check_apply_id' => $tmpApply['id'], 'wid' => $check_level_info['wid']);
            }

        } elseif ($verifyData['status'] == 1) {
            //审核通过后处理
            $current_level = $detailData['level'];
            $next_level = $current_level + 1;
            $db_houseWorker = new HouseWorker();
            fdump_api(['xtype' => $xtype, 'msg' => '审核通过后处理'], 'verifyCheckauthApply', 1);
            if ($tmpApply['xtype'] == 'order_refund') {
                //退款
                /*
                $level=-1;
                    foreach ($check_level_info['check_level'] as $vv){
                        if($vv['level_wid']==$check_level_info['wid']){
                            $level=$vv['level_v'];
                        }
                    }
                $level_tmp=$level+1;
                */
                if (isset($check_level_info['check_level'][$next_level])) {
                    //有下一等级的
                    $next_level_wid = $check_level_info['check_level'][$next_level]['level_wid'];
                    $whereArr = array('wid' => $next_level_wid, 'village_id' => $village_id);
                    $field = 'wid,village_id,phone,name,openid,status,type,property_id,people_type,department_id,account';
                    $worker = $db_houseWorker->get_one($whereArr, $field);
                    if (!$worker || $worker->isEmpty()) {
                        $worker = '';
                    } else {
                        $worker = $worker->toArray();
                    }
                    $detailArr = array('apply_id' => $tmpApply['id'], 'property_id' => $tmpApply['property_id']);
                    $detailArr['order_id'] = $tmpApply['order_id'];
                    $detailArr['village_id'] = $village_id;
                    $detailArr['wid'] = $next_level_wid;
                    $detailArr['level'] = $next_level;
                    $detailArr['status'] = 0;
                    $detailArr['extra_data'] = !empty($worker) ? json_encode($worker, JSON_UNESCAPED_UNICODE) : '';
                    $idd = $houseVillageCheckauthDetailService->addDetail($detailArr);
                    $otherData = array('village_id' => $village_id, 'wid' => $next_level_wid);
                    $houseAppJpushInfoService->handleJpushInfoSend($otherData, 'checkauth_apply_tips');
                    if (!empty($worker) && !empty($worker['openid'])) {
                        $xtypeStr = '订单退款申请';
                        $href = cfg('site_url') . '/packapp/community/pages/Community/reviewRefund/myApproval';
                        $apply_name = $tmpApply['apply_name'];
                        $address = '无';
                        $orderArr = $db_house_new_pay_order->get_one(['order_id' => $tmpApply['order_id']], 'name,room_id');
                        if ($orderArr && !$orderArr->isEmpty()) {
                            $orderArr = $orderArr->toArray();
                            if ($orderArr && !empty($orderArr['name'])) {
                                $apply_name = $orderArr['name'];
                            }
                            if ($orderArr['room_id']) {
                                $user_info = (new HouseVillageUserBindService())->getBindInfo([['vacancy_id', '=', $orderArr['room_id']], ['type', 'in', '0,3'], ['status', '=', 1]], 'single_id,floor_id,layer_id,vacancy_id,village_id');
                                $address = (new HouseVillageService())->getSingleFloorRoom($user_info['single_id'], $user_info['floor_id'], $user_info['layer_id'], $user_info['vacancy_id'], $user_info['village_id']);
                            }
                        }
                        $datamsg = [
                            'tempKey' => 'OPENTM417740354',//todo 类目模板OPENTM417740354
                            'dataArr' => [
                                'href' => $href,
                                'wecha_id' => $worker['openid'],
                                'first' => '审核提醒',
                                'keyword1' => $xtypeStr,
                                'keyword2' => $apply_name,
                                'keyword3' => date('Y-m-d H:i:s', $tmpApply['add_time']),
                                'remark' => '您有一个新的申请消息需要审核！',
                                'new_info' => [//新版本发送需要的信息
                                    'tempKey' => '43258',//新模板号
                                    'thing2' => $xtypeStr,//流程名称
                                    'thing9' => $apply_name,//发起人
                                    'time10' => date('Y-m-d H:i:s', $tmpApply['add_time']),//发起时间
                                    'thing7' => $address,//房源名称
                                ],
                            ]
                        ];
                        $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
                    }
                    $updateArr = array('status' => 1, 'apply_time' => time());
                    $db_VillageCheckauthApply->updateApply(['id' => $tmpApply['id']], $updateArr);
                    return array('check_status' => 2, 'check_apply_id' => $tmpApply['id'], 'wid' => $check_level_info['wid']);
                } else {
                    //没有下一等级 审核完成
                    $houseNewCashierService = new HouseNewCashierService();
                    $apply_extra_data = json_decode($tmpApply['extra_data'], true);
                    $role_id = $apply_extra_data['role_id'];
                    $order_id = $apply_extra_data['order_id'];
                    $refund_type = $apply_extra_data['refund_type'];
                    $refund_money = $apply_extra_data['refund_money'];
                    $refund_reason = $apply_extra_data['refund_reason'];
                    $extra = array('opt_type' => 'check_pass_refund');
                    $ret = $houseNewCashierService->addRefundInfo($role_id, $order_id, $refund_type, $refund_money, $refund_reason, $extra);
                    $updateArr = array('status' => 2, 'apply_time' => time());
                    $db_VillageCheckauthApply->updateApply(['id' => $tmpApply['id']], $updateArr);
                    //	check_status 1作废审核中2退款审核中3审核通过4审核未通过
                    return array('check_status' => 3, 'check_apply_id' => $tmpApply['id'], 'wid' => $check_level_info['wid']);
                }
            } elseif ($tmpApply['xtype'] == 'order_discard') {
                //作废
                if (isset($check_level_info['check_level'][$next_level])) {
                    //有下一等级的
                    $next_level_wid = $check_level_info['check_level'][$next_level]['level_wid'];
                    $whereArr = array('wid' => $next_level_wid, 'village_id' => $village_id);
                    $field = 'wid,village_id,phone,name,openid,status,type,property_id,people_type,department_id,account';
                    $worker = $db_houseWorker->get_one($whereArr, $field);
                    if (!$worker || $worker->isEmpty()) {
                        $worker = '';
                    } else {
                        $worker = $worker->toArray();
                    }
                    $detailArr = array('apply_id' => $tmpApply['id'], 'property_id' => $tmpApply['property_id']);
                    $detailArr['order_id'] = $tmpApply['order_id'];
                    $detailArr['village_id'] = $village_id;
                    $detailArr['wid'] = $next_level_wid;
                    $detailArr['level'] = $next_level;
                    $detailArr['status'] = 0;
                    $detailArr['extra_data'] = !empty($worker) ? json_encode($worker, JSON_UNESCAPED_UNICODE) : '';
                    $idd = $houseVillageCheckauthDetailService->addDetail($detailArr);
                    $otherData = array('village_id' => $village_id, 'wid' => $next_level_wid);
                    $houseAppJpushInfoService->handleJpushInfoSend($otherData, 'checkauth_apply_tips');
                    if (!empty($worker) && !empty($worker['openid'])) {
                        $xtypeStr = '订单作废申请';
                        $href = cfg('site_url') . '/packapp/community/pages/Community/reviewRefund/myApproval';
                        $apply_name = $tmpApply['apply_name'];
                        $address = '无';
                        $orderArr = $db_house_new_pay_order->get_one(['order_id' => $tmpApply['order_id']], 'name,room_id');
                        if ($orderArr && !$orderArr->isEmpty()) {
                            $orderArr = $orderArr->toArray();
                            if ($orderArr && !empty($orderArr['name'])) {
                                $apply_name = $orderArr['name'];
                            }
                            if ($orderArr['room_id']) {
                                $user_info = (new HouseVillageUserBindService())->getBindInfo([['vacancy_id', '=', $orderArr['room_id']], ['type', 'in', '0,3'], ['status', '=', 1]], 'single_id,floor_id,layer_id,vacancy_id,village_id');
                                $address = (new HouseVillageService())->getSingleFloorRoom($user_info['single_id'], $user_info['floor_id'], $user_info['layer_id'], $user_info['vacancy_id'], $user_info['village_id']);
                            }
                        }
                        $datamsg = [
                            'tempKey' => 'OPENTM417740354',//todo 类目模板OPENTM417740354
                            'dataArr' => [
                                'href' => $href,
                                'wecha_id' => $worker['openid'],
                                'first' => '审核提醒',
                                'keyword1' => $xtypeStr,
                                'keyword2' => $apply_name,
                                'keyword3' => date('Y-m-d H:i:s', $tmpApply['add_time']),
                                'remark' => '您有一个新的申请消息需要审核！',
                                'new_info' => [//新版本发送需要的信息
                                    'tempKey' => '43258',//新模板号
                                    'thing2' => $xtypeStr,//流程名称
                                    'thing9' => $apply_name,//发起人
                                    'time10' => date('Y-m-d H:i:s', $tmpApply['add_time']),//发起时间
                                    'thing7' => $address,//房源名称
                                ],
                            ]
                        ];
                        $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
                    }
                    $updateArr = array('status' => 1, 'apply_time' => time());
                    $db_VillageCheckauthApply->updateApply(['id' => $tmpApply['id']], $updateArr);
                    return array('check_status' => 2, 'check_apply_id' => $tmpApply['id'], 'wid' => $check_level_info['wid']);
                } else {
                    //没有下一等级 审核完成
                    $houseNewCashierService = new HouseNewCashierService();
                    $apply_extra_data = json_decode($tmpApply['extra_data'], true);
                    $discard_reason = $apply_extra_data['discard_reason'];
                    $saveData = ['is_discard' => 2, 'discard_reason' => $discard_reason, 'update_time' => time()];
                    $saveData['check_status'] = 3;
                    $houseNewCashierService->saveOrder(['order_id' => $tmpApply['order_id']], $saveData);
                    if (isset($order['primitive_order_id']) && $order['primitive_order_id'] > 0) {
                        $houseNewCashierService->discardPaidOrderServiceTime($order['primitive_order_id']);
                    }
                    $updateArr = array('status' => 2, 'apply_time' => time());
                    $db_VillageCheckauthApply->updateApply(['id' => $tmpApply['id']], $updateArr);
                    //	check_status 1作废审核中2退款审核中3审核通过4审核未通过
                    return array('check_status' => 3, 'check_apply_id' => $tmpApply['id'], 'wid' => $check_level_info['wid']);
                }

            } elseif ($tmpApply['xtype'] == 'modify_money_check') {
                //修改费用
                fdump_api(['next_level' => $next_level, 'msg' => '修改费用'], 'verifyCheckauthApply', 1);
                if (isset($check_level_info['check_level'][$next_level])) {
                    //有下一等级的
                    fdump_api(['next_level' => $next_level, 'msg' => '有下一等级的'], 'verifyCheckauthApply', 1);
                    $next_level_wid = $check_level_info['check_level'][$next_level]['level_wid'];
                    $whereArr = array('wid' => $next_level_wid, 'village_id' => $village_id);
                    $field = 'wid,village_id,phone,name,openid,status,type,property_id,people_type,department_id,account';
                    $worker = $db_houseWorker->get_one($whereArr, $field);
                    if (!$worker || $worker->isEmpty()) {
                        $worker = '';
                    } else {
                        $worker = $worker->toArray();
                    }
                    $detailArr = array('apply_id' => $tmpApply['id'], 'property_id' => $tmpApply['property_id']);
                    $detailArr['order_id'] = $tmpApply['order_id'];
                    $detailArr['village_id'] = $village_id;
                    $detailArr['wid'] = $next_level_wid;
                    $detailArr['level'] = $next_level;
                    $detailArr['status'] = 0;
                    $detailArr['extra_data'] = !empty($worker) ? json_encode($worker, JSON_UNESCAPED_UNICODE) : '';
                    fdump_api(['detailArr' => $detailArr, 'msg' => '有下一等级的'], 'verifyCheckauthApply', 1);
                    $idd = $houseVillageCheckauthDetailService->addDetail($detailArr);
                    $otherData = array('village_id' => $village_id, 'wid' => $next_level_wid);
                    $houseAppJpushInfoService->handleJpushInfoSend($otherData, 'checkauth_apply_tips');
                    if (!empty($worker) && !empty($worker['openid'])) {
                        $xtypeStr = '订单金额修改申请';
                        $href = cfg('site_url') . '/packapp/community/pages/Community/reviewRefund/myApproval';
                        $apply_name = $tmpApply['apply_name'];
                        $address = '无';
                        $orderArr = $db_house_new_pay_order->get_one(['order_id' => $tmpApply['order_id']], 'name,room_id');
                        if ($orderArr && !$orderArr->isEmpty()) {
                            $orderArr = $orderArr->toArray();
                            if ($orderArr && !empty($orderArr['name'])) {
                                $apply_name = $orderArr['name'];
                            }
                            if ($orderArr['room_id']) {
                                $user_info = (new HouseVillageUserBindService())->getBindInfo([['vacancy_id', '=', $orderArr['room_id']], ['type', 'in', '0,3'], ['status', '=', 1]], 'single_id,floor_id,layer_id,vacancy_id,village_id');
                                $address = (new HouseVillageService())->getSingleFloorRoom($user_info['single_id'], $user_info['floor_id'], $user_info['layer_id'], $user_info['vacancy_id'], $user_info['village_id']);
                            }
                        }
                        $datamsg = [
                            'tempKey' => 'OPENTM417740354',//todo 类目模板OPENTM417740354
                            'dataArr' => [
                                'href' => $href,
                                'wecha_id' => $worker['openid'],
                                'first' => '审核提醒',
                                'keyword1' => $xtypeStr,
                                'keyword2' => $apply_name,
                                'keyword3' => date('Y-m-d H:i:s', $tmpApply['add_time']),
                                'remark' => '您有一个新的申请消息需要审核！',
                                'new_info' => [//新版本发送需要的信息
                                    'tempKey' => '43258',//新模板号
                                    'thing2' => $xtypeStr,//流程名称
                                    'thing9' => $apply_name,//发起人
                                    'time10' => date('Y-m-d H:i:s', $tmpApply['add_time']),//发起时间
                                    'thing7' => $address,//房源名称
                                ],
                            ]
                        ];
                        $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
                    }
                    $updateArr = array('status' => 1, 'apply_time' => time());
                    $db_VillageCheckauthApply->updateApply(['id' => $tmpApply['id']], $updateArr);
                    return array('check_status' => 2, 'check_apply_id' => $tmpApply['id'], 'wid' => $check_level_info['wid']);
                } else {
                    fdump_api(['tmpApply' => $tmpApply, 'msg' => '没有下一等级审核完成'], 'verifyCheckauthApply', 1);
                    //没有下一等级 审核完成
                    $discount_type = '';
                    $discount_v = '';
                    $cut_money = 0;
                    if (isset($tmpApply['extra_data']) && !empty($tmpApply['extra_data'])) {
                        $order_apply_info = json_decode($tmpApply['extra_data'], true);
                        $money = isset($order_apply_info['money']) ? $order_apply_info['money'] : 0;
                        $modify_reason = isset($order_apply_info['modify_reason']) ? $order_apply_info['modify_reason'] : '';
                        if (isset($order_apply_info['discount_type'])) {
                            $discount_type = $order_apply_info['discount_type'];
                            $discount_v = $order_apply_info['discount_v'];
                            $cut_money = $order_apply_info['cut_money'];
                        }
                    } else {
                        $money = isset($tmpApply['money']) ? $tmpApply['money'] : 0;
                        $modify_reason = '修改审核申请通过更改订单金额';
                    }
                    fdump_api(['check_status' => 3, 'modify_money' => $money, 'modify_reason' => $modify_reason, 'modify_time' => $nowtime, 'msg' => '没有下一等级审核完成'], 'verifyCheckauthApply', 1);
                    $saveOrderData = ['check_status' => 3, 'modify_money' => $money, 'modify_reason' => $modify_reason, 'modify_time' => $nowtime];
                    if ($discount_type && in_array($discount_type, array('discount', 'cut_money'))) {
                        $order_info = $db_house_new_pay_order->get_one(['order_id' => $tmpApply['order_id']], '*');
                        $order_modify_discount = array('check_apply_id' => $tmpApply['id'], 'check_apply_type' => 'modify_money_check', 'discount_type' => $discount_type, 'discount_v' => $discount_v, 'old_money' => round($order_info['modify_money'], 2), 'cut_money' => $cut_money, 'modify_money' => $money, 'modify_time' => $nowtime, 'modify_reason' => $modify_reason);
                        $saveOrderData['order_modify_discount'] = json_encode($order_modify_discount, JSON_UNESCAPED_UNICODE);
                    }
                    $houseNewCashierService->saveOrder(['order_id' => $tmpApply['order_id']], $saveOrderData);
                    $updateArr = array('status' => 2, 'apply_time' => $nowtime);
                    $db_VillageCheckauthApply->updateApply(['id' => $tmpApply['id']], $updateArr);
                    //	check_status 1作废审核中2退款审核中3审核通过4审核未通过5
                    return array('check_status' => 3, 'check_apply_id' => $tmpApply['id'], 'wid' => $check_level_info['wid']);
                }

            } elseif ($tmpApply['xtype'] == 'transfer_other_work') {
                //修改费用
                fdump_api(['next_level' => $next_level, 'msg' => '工单转单审核通过后处理'], 'verifyCheckauthApply', 1);
                if (isset($check_level_info['check_level'][$next_level])) {
                    //有下一等级的
                    fdump_api(['next_level' => $next_level, 'msg' => '有下一等级的'], 'verifyCheckauthApply', 1);
                    $next_level_wid = $check_level_info['check_level'][$next_level]['level_wid'];
                    $whereArr = array('wid' => $next_level_wid, 'village_id' => $village_id);
                    $field = 'wid,village_id,phone,name,openid,status,type,property_id,people_type,department_id,account';
                    $worker = $db_houseWorker->get_one($whereArr, $field);
                    if (!$worker || $worker->isEmpty()) {
                        $worker = '';
                    } else {
                        $worker = $worker->toArray();
                    }
                    $detailArr = array('apply_id' => $tmpApply['id'], 'property_id' => $tmpApply['property_id']);
                    $detailArr['order_id'] = $tmpApply['order_id'];
                    $detailArr['village_id'] = $village_id;
                    $detailArr['wid'] = $next_level_wid;
                    $detailArr['level'] = $next_level;
                    $detailArr['status'] = 0;
                    $detailArr['extra_data'] = !empty($worker) ? json_encode($worker, JSON_UNESCAPED_UNICODE) : '';
                    fdump_api(['detailArr' => $detailArr, 'msg' => '有下一等级的'], 'verifyCheckauthApply', 1);
                    $idd = $houseVillageCheckauthDetailService->addDetail($detailArr);
                    $otherData = array('village_id' => $village_id, 'wid' => $next_level_wid);
                    $houseAppJpushInfoService->handleJpushInfoSend($otherData, 'checkauth_apply_tips');
                    if (!empty($worker) && !empty($worker['openid'])) {
                        $xtypeStr = '工单转单申请';
                        $href = cfg('site_url') . '/packapp/community/pages/Community/reviewRefund/myApproval';
                        $apply_name = $tmpApply['apply_name'];
                        $address = '无';
                        $datamsg = [
                            'tempKey' => 'OPENTM417740354',//todo 类目模板OPENTM417740354
                            'dataArr' => [
                                'href' => $href,
                                'wecha_id' => $worker['openid'],
                                'first' => '审核提醒',
                                'keyword1' => $xtypeStr,
                                'keyword2' => $apply_name,
                                'keyword3' => date('Y-m-d H:i:s', $tmpApply['add_time']),
                                'remark' => '您有一个新的申请消息需要审核！',
                                'new_info' => [//新版本发送需要的信息
                                    'tempKey' => '43258',//新模板号
                                    'thing2' => $xtypeStr,//流程名称
                                    'thing9' => $apply_name,//发起人
                                    'time10' => date('Y-m-d H:i:s', $tmpApply['add_time']),//发起时间
                                    'thing7' => $address,//房源名称
                                ],
                            ]
                        ];
                        $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
                    }
                    $updateArr = array('status' => 1, 'apply_time' => time());
                    $db_VillageCheckauthApply->updateApply(['id' => $tmpApply['id']], $updateArr);
                    return array('check_status' => 2, 'check_apply_id' => $tmpApply['id'], 'wid' => $check_level_info['wid']);
                } else {
                    fdump_api(['tmpApply' => $tmpApply, 'msg' => '没有下一等级审核完成'], 'verifyCheckauthApply', 1);
                    //没有下一等级 审核完成
                    if (isset($tmpApply['extra_data']) && !empty($tmpApply['extra_data'])) {
                        $order_apply_info = json_decode($tmpApply['extra_data'], true);
                        $apply_reason = isset($order_apply_info['apply_reason']) ? $order_apply_info['apply_reason'] : '';
                    } else {
                        $order_apply_info = [];
                        $apply_reason = '工单转单申请审核通过';
                    }
                    fdump_api(['check_status' => 3, 'apply_reason' => $apply_reason, 'modify_time' => $nowtime, 'msg' => '没有下一等级审核完成'], 'verifyCheckauthApply', 1);


                    $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
                    $where_order = [];
                    $where_order[] = ['order_id', '=', $order['order_id']];
                    $bak = isset($verifyData['bak']) ? trim($verifyData['bak']) : '';
                    $log_name = 'pass_work_change_check';
                    $operator_type = 30;
                    switch ($login_role) {
                        case HouseWorkConst::MANAGE_APP_VILLAGE_WORK:
                            $operator_type = 30;
                            break;
                        case HouseWorkConst::MANAGE_APP_VILLAGE_ADMIN:
                            $operator_type = 43;
                            break;
                        case HouseWorkConst::MANAGE_APP_PROPERTY_USER:
                            $operator_type = 42;
                            break;
                        case HouseWorkConst::MANAGE_APP_PROPERTY_ADMIN:
                            $operator_type = 41;
                            break;
                    }
                    $order_content = '工作人员转单审核通过';
                    $to_village_id = isset($order_apply_info['village_id']) && intval($order_apply_info['village_id']) > 0 ? intval($order_apply_info['village_id']) : 0;
                    $to_work_id = isset($order_apply_info['to_work_id']) && intval($order_apply_info['to_work_id']) > 0 ? intval($order_apply_info['to_work_id']) : 0;

                    $jzParams = [
                        'operator_id' => $log_uid, 'order_id' => $order['order_id'], 'village_id' => $to_village_id,
                        'order_info' => $order, 'log_name' => $log_name, 'to_work_id' => $to_work_id, 'notCheck' => 1,
                    ];
                    fdump_api(['jzParams' => $jzParams, 'order_apply_info' => $order_apply_info, 'msg' => '没有下一等级审核完成'], 'verifyCheckauthApply', 1);
                    $jzResult = (new HouseCommunityTransferOrderService())->transferOrderToOtherWork($jzParams);
                    fdump_api(['jzResult' => $jzResult, 'msg' => '没有下一等级审核完成'], 'verifyCheckauthApply', 1);
                    $order_set = isset($jzResult['order_set']) ? $jzResult['order_set'] : [];
                    $order_set['check_status'] = 2;
                    $order_set['check_time'] = time();
                    if ($bak) {
                        $order_set['check_reason'] = $bak;
                    }
                    $db_house_new_repair_works_order->saveOne($where_order, $order_set);
                    $log_data = [
                        'order_id' => $order['order_id'],
                        'log_name' => $log_name,
                        'village_id' => isset($order['village_id']) ? intval($order['village_id']) : '',
                        'property_id' => isset($order['property_id']) ? intval($order['property_id']) : '',
                        'log_operator' => isset($now_login['login_name']) ? $now_login['login_name'] : '',
                        'log_phone' => isset($now_login['login_phone']) ? $now_login['login_phone'] : '',
                        'operator_type' => $operator_type,
                        'operator_id' => $log_uid,
                        'log_uid' => $order['uid'] ? $order['uid'] : 0,
                        'log_content' => $order_content ? $order_content : '',
                        'log_imgs' => '',
                        'evaluate' => 0,
                        'add_time' => time(),
                    ];
                    $db_house_new_repair_works_order_log = new HouseNewRepairWorksOrderLog();
                    $where_count = [];
                    $where_count[] = ['order_id', '=', $order['order_id']];
                    $where_count[] = ['log_name', '=', strval($log_data['log_name'])];
                    $log_num = $db_house_new_repair_works_order_log->getCount($where_count);
                    if (!$log_num) {
                        $log_num = 1;
                    } else {
                        $log_num = intval($log_num) + 1;
                    }
                    $log_data['log_num'] = $log_num;
                    $log_data['change_reason'] = '工作人员转单审核通过';
                    $log_id = (new HouseNewRepairService())->addRepairLog($log_data);
                    $updateArr = array('status' => 2, 'apply_time' => $nowtime);
                    $db_VillageCheckauthApply->updateApply(['id' => $tmpApply['id']], $updateArr);
                    //	check_status 1作废审核中2退款审核中3审核通过4审核未通过
                    return array('check_status' => 3, 'check_apply_id' => $tmpApply['id'], 'wid' => $check_level_info['wid']);
                }

            } else if ($tmpApply['xtype'] == 'order_servicetime_check') {
                //房间计费时间修改审核
                if (isset($check_level_info['check_level'][$next_level])) {
                    //有下一等级的
                    $next_level_wid = $check_level_info['check_level'][$next_level]['level_wid'];
                    $whereArr = array('wid' => $next_level_wid, 'village_id' => $village_id);
                    $field = 'wid,village_id,phone,name,openid,status,type,property_id,people_type,department_id,account';
                    $worker = $db_houseWorker->get_one($whereArr, $field);
                    if (!$worker || $worker->isEmpty()) {
                        $worker = '';
                    } else {
                        $worker = $worker->toArray();
                    }
                    $detailArr = array('apply_id' => $tmpApply['id'], 'property_id' => $tmpApply['property_id']);
                    $detailArr['order_id'] = $tmpApply['order_id'];
                    $detailArr['village_id'] = $village_id;
                    $detailArr['wid'] = $next_level_wid;
                    $detailArr['level'] = $next_level;
                    $detailArr['status'] = 0;
                    $detailArr['extra_data'] = !empty($worker) ? json_encode($worker, JSON_UNESCAPED_UNICODE) : '';
                    $idd = $houseVillageCheckauthDetailService->addDetail($detailArr);
                    if (!empty($worker) && !empty($worker['openid'])) {
                        $xtypeStr = '房间计费时间修改申请';
                        $apply_name = $tmpApply['apply_name'];
                        $href = '';
                        $user_info = (new HouseVillageUserBindService())->getBindInfo([['vacancy_id', '=', $tmpApply['room_id']], ['type', 'in', '0,3'], ['status', '=', 1]], 'single_id,floor_id,layer_id,vacancy_id,village_id');
                        $address = (new HouseVillageService())->getSingleFloorRoom($user_info['single_id'], $user_info['floor_id'], $user_info['layer_id'], $user_info['vacancy_id'], $user_info['village_id']);
                        $datamsg = [
                            'tempKey' => 'OPENTM417740354',//todo 类目模板OPENTM417740354
                            'dataArr' => [
                                'href' => $href,
                                'wecha_id' => $worker['openid'],
                                'first' => '审核提醒',
                                'keyword1' => $xtypeStr,
                                'keyword2' => $apply_name,
                                'keyword3' => date('Y-m-d H:i:s', $tmpApply['add_time']),
                                'remark' => '您有一个新的申请消息需要审核！',
                                'new_info' => [//新版本发送需要的信息
                                    'tempKey' => '43258',//新模板号
                                    'thing2' => $xtypeStr,//流程名称
                                    'thing9' => $apply_name,//发起人
                                    'time10' => date('Y-m-d H:i:s', $tmpApply['add_time']),//发起时间
                                    'thing7' => $address,//房源名称
                                ],
                            ]
                        ];
                        $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
                    }
                    $updateArr = array('status' => 1, 'apply_time' => time());
                    $db_VillageCheckauthApply->updateApply(['id' => $tmpApply['id']], $updateArr);
                    return array('check_status' => 6, 'check_apply_id' => $tmpApply['id'], 'wid' => $check_level_info['wid']);
                } else {
                    //没有下一等级 审核完成
                    $order_log = new HouseNewOrderLog();
                    $apply_extra_data = json_decode($tmpApply['extra_data'], true);
                    $project_name = isset($apply_extra_data['project_name']) ? $apply_extra_data['project_name'] : '';
                    $logOrder = [
                        'order_type' => $tmpApply['charge_type'],
                        'order_name' => $project_name,
                        'project_id' => $tmpApply['project_id'],
                        'room_id' => $tmpApply['room_id'],
                        'village_id' => $tmpApply['village_id'],
                        'property_id' => $tmpApply['property_id'],
                        'desc' => '通过申请修改计费时间',
                        'service_start_time' => $tmpApply['service_start_time'],
                        'service_end_time' => $tmpApply['service_end_time'],
                        'add_time' => time()
                    ];
                    $log_id = $order_log->addOne($logOrder);
                    $updateArr = array('status' => 2, 'apply_time' => time(), 'other_relation_id' => $log_id);
                    $db_VillageCheckauthApply->updateApply(['id' => $tmpApply['id']], $updateArr);
                    //	check_status 1作废审核中2退款审核中3审核通过4审核未通过
                    return array('check_status' => 3, 'check_apply_id' => $tmpApply['id'], 'wid' => $check_level_info['wid']);
                }
            } else if ($tmpApply['xtype'] == 'order_paytime_check') {
                //已缴账单支付时间修改申请
                if (isset($check_level_info['check_level'][$next_level])) {
                    //有下一等级的
                    fdump_api(['next_level' => $next_level, 'msg' => '有下一等级的'], 'verifyCheckauthApply', 1);
                    $next_level_wid = $check_level_info['check_level'][$next_level]['level_wid'];
                    $whereArr = array('wid' => $next_level_wid, 'village_id' => $village_id);
                    $field = 'wid,village_id,phone,name,openid,status,type,property_id,people_type,department_id,account';
                    $worker = $db_houseWorker->get_one($whereArr, $field);
                    if (!$worker || $worker->isEmpty()) {
                        $worker = '';
                    } else {
                        $worker = $worker->toArray();
                    }
                    $detailArr = array('apply_id' => $tmpApply['id'], 'property_id' => $tmpApply['property_id']);
                    $detailArr['order_id'] = $tmpApply['order_id'];
                    $detailArr['village_id'] = $village_id;
                    $detailArr['wid'] = $next_level_wid;
                    $detailArr['level'] = $next_level;
                    $detailArr['status'] = 0;
                    $detailArr['extra_data'] = !empty($worker) ? json_encode($worker, JSON_UNESCAPED_UNICODE) : '';
                    fdump_api(['detailArr' => $detailArr, 'msg' => '有下一等级的'], 'verifyCheckauthApply', 1);
                    $idd = $houseVillageCheckauthDetailService->addDetail($detailArr);
                    $otherData = array('village_id' => $village_id, 'wid' => $next_level_wid);
                    $houseAppJpushInfoService->handleJpushInfoSend($otherData, 'checkauth_apply_tips');
                    if (!empty($worker) && !empty($worker['openid'])) {
                        $xtypeStr = '已缴账单支付时间修改申请';
                        $href = cfg('site_url') . '/packapp/community/pages/Community/reviewRefund/myApproval';
                        $apply_name = $tmpApply['apply_name'];
                        $address = '无';
                        $orderArr = $db_house_new_pay_order->get_one(['order_id' => $tmpApply['order_id']], 'name,room_id');
                        if ($orderArr && !$orderArr->isEmpty()) {
                            $orderArr = $orderArr->toArray();
                            if ($orderArr && !empty($orderArr['name'])) {
                                $apply_name = $orderArr['name'];
                            }
                            if ($orderArr['room_id']) {
                                $user_info = (new HouseVillageUserBindService())->getBindInfo([['vacancy_id', '=', $orderArr['room_id']], ['type', 'in', '0,3'], ['status', '=', 1]], 'single_id,floor_id,layer_id,vacancy_id,village_id');
                                $address = (new HouseVillageService())->getSingleFloorRoom($user_info['single_id'], $user_info['floor_id'], $user_info['layer_id'], $user_info['vacancy_id'], $user_info['village_id']);
                            }
                        }
                        $datamsg = [
                            'tempKey' => 'OPENTM417740354',//todo 类目模板OPENTM417740354
                            'dataArr' => [
                                'href' => $href,
                                'wecha_id' => $worker['openid'],
                                'first' => '审核提醒',
                                'keyword1' => $xtypeStr,
                                'keyword2' => $apply_name,
                                'keyword3' => date('Y-m-d H:i:s', $tmpApply['add_time']),
                                'remark' => '您有一个新的申请消息需要审核！',
                                'new_info' => [//新版本发送需要的信息
                                    'tempKey' => '43258',//新模板号
                                    'thing2' => $xtypeStr,//流程名称
                                    'thing9' => $apply_name,//发起人
                                    'time10' => date('Y-m-d H:i:s', $tmpApply['add_time']),//发起时间
                                    'thing7' => $address,//房源名称
                                ],
                            ]
                        ];
                        $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
                    }
                    $updateArr = array('status' => 1, 'apply_time' => time());
                    $db_VillageCheckauthApply->updateApply(['id' => $tmpApply['id']], $updateArr);
                    $houseNewPayOrderModifyRecord = new HouseNewPayOrderModifyRecord();
                    $houseNewPayOrderModifyRecord->saveOneData(array('apply_id' => $tmpApply['id']), array('check_status' => 1, 'check_time' => $nowtime));
                    return array('check_status' => 2, 'check_apply_id' => $tmpApply['id'], 'wid' => $check_level_info['wid']);
                } else {
                    //没有下一等级 审核完成
                    $houseNewCashierService = new HouseNewCashierService();
                    $apply_extra_data = json_decode($tmpApply['extra_data'], true);
                    $modify_pay_time_int=$apply_extra_data['mdfy_pay_time'];
                    $saveData = ['pay_time' => $modify_pay_time_int, 'update_time' => time()];
                    $saveData['check_status'] = 3;
                    $houseNewCashierService->saveOrder(['order_id' => $tmpApply['order_id']], $saveData);
                    $updateArr = array('status' => 2, 'apply_time' => time());
                    $db_VillageCheckauthApply->updateApply(['id' => $tmpApply['id']], $updateArr);
                    $houseNewPayOrderModifyRecord = new HouseNewPayOrderModifyRecord();
                    $houseNewPayOrderModifyRecord->saveOneData(array('apply_id' => $tmpApply['id']), array('check_status' => 2, 'check_time' => $nowtime));
                    //	check_status 1作废审核中2退款审核中3审核通过4审核未通过
                    return array('check_status' => 3, 'check_apply_id' => $tmpApply['id'], 'wid' => $check_level_info['wid']);
                }
            }
        } else {
            throw new \think\Exception("审核保存失败！");
        }
    }
        //自动作废 订单处理
        public
        function verifyDiscardCheckauthApply($order = array(), $verifyData = array())
        {
            $houseVillageCheckauthDetailService = new HouseVillageCheckauthDetailService();
            $db_VillageCheckauthApply = new HouseVillageCheckauthApply();
            $where = array('id' => $order['check_apply_id'], 'village_id' => $order['village_id'], 'order_id' => $order['order_id']);
            $where['xtype'] = $verifyData['xtype'];
            unset($verifyData['xtype']);
            $village_id = $order['village_id'];
            $tmpApply = $db_VillageCheckauthApply->getOne($where);
            if (!empty($tmpApply)) {
                $nowtime = time();
                $verifyData['apply_time'] = $nowtime;
                $detailWhere = array('apply_id' => $tmpApply['id'], 'order_id' => $tmpApply['order_id'], 'status' => 0);
                $ret = $houseVillageCheckauthDetailService->updateDetail($detailWhere, $verifyData);
                $updateArr = array('status' => 2, 'apply_time' => $nowtime);
                $db_VillageCheckauthApply->updateApply(['id' => $tmpApply['id']], $updateArr);
            }
            return true;
        }

        //根据申请id，获取审核明细
        public
        function getRefundApplyCheckDetail($order_id = 0, $apply_id = 0, $xtype = 'order_refund')
        {

            $data = [];
            $data['list'] = array();
            $data['count'] = 0;
            $data['apply_info'] = array('has_data' => 0);
            $data['total_limit'] = 10;
            if ($order_id < 1 || $apply_id < 1) {
                return $data;
            }
            $db_VillageCheckauthApply = new HouseVillageCheckauthApply();
            $where = array('id' => $apply_id, 'order_id' => $order_id, 'xtype' => $xtype);
            $tmpApply = $db_VillageCheckauthApply->getOne($where);
            if (empty($tmpApply)) {
                $where = array('id' => $apply_id, 'order_id' => $order_id);
                $tmpApply = $db_VillageCheckauthApply->getOne($where);
                if (empty($tmpApply)) {
                    return $data;
                }
            }
            $data['apply_info']['apply_name'] = $tmpApply['apply_name'];
            $data['apply_info']['apply_uid'] = $tmpApply['apply_uid'];
            $data['apply_info']['apply_login_role'] = $tmpApply['apply_login_role'];
            $apply_extra_data = json_decode($tmpApply['extra_data'], 1);
            $data['apply_info']['add_time_str'] = date('Y-m-d H:i:s', $tmpApply['add_time']);
            $data['apply_info']['apply_reason'] = '';
            $data['apply_info']['apply_money'] = 0;
            if (isset($apply_extra_data['refund_reason'])) {
                $data['apply_info']['apply_reason'] = $apply_extra_data['refund_reason'];
                $data['apply_info']['apply_money'] = $apply_extra_data['refund_money'];
            } elseif (isset($apply_extra_data['discard_reason'])) {
                $data['apply_info']['apply_reason'] = $apply_extra_data['discard_reason'];
                $data['apply_info']['apply_money'] = $apply_extra_data['total_money'];
            } elseif (isset($apply_extra_data['modify_reason'])) {
                $data['apply_info']['apply_reason'] = $apply_extra_data['modify_reason'];
                $data['apply_info']['apply_money'] = $apply_extra_data['money'];
                $data['apply_info']['total_money'] = $apply_extra_data['modify_money'];
            }
            $property_id = $tmpApply['property_id'];
            $village_id = $tmpApply['village_id'];
            $houseVillageCheckauthSetService = new HouseVillageCheckauthSetService();
            $orderRefundCheckWhere = array('village_id' => $village_id, 'xtype' => 'order_refund_check');
            $checkauthSet = $houseVillageCheckauthSetService->getOneCheckauthSet($orderRefundCheckWhere);
            $houseVillageCheckauthDetailService = new HouseVillageCheckauthDetailService();
            $whereArr = array('apply_id' => $apply_id, 'order_id' => $order_id, 'village_id' => $tmpApply['village_id']);
            $detailList = $houseVillageCheckauthDetailService->getDetailList($whereArr);
            $tmp_level = -1;
            if (!empty($detailList)) {
                foreach ($detailList as $vv) {
                    $tmpdata = $vv;
                    $tmpdata['apply_time_str'] = '';
                    if ($tmpdata['apply_time'] > 0) {
                        $tmpdata['apply_time_str'] = date('Y-m-d H:i:s', $tmpdata['apply_time']);
                    }
                    $extra_data = $tmpdata['extra_data'];
                    unset($tmpdata['extra_data']);
                    $extra_data = json_decode($extra_data, 1);
                    $tmpdata['pname'] = $extra_data['name'];
                    $tmpdata['phone'] = $extra_data['phone'];
                    $tmpdata['openid'] = $extra_data['openid'];
                    $tmpdata['department_id'] = $extra_data['department_id'];
                    if ($vv['level'] > $tmp_level) {
                        $tmp_level = $vv['level'];
                    }
                    $tmpdata['color_v'] = 'gray';
                    $tmpdata['status_str'] = '未审核';
                    if ($tmpdata['status'] == 1) {
                        $tmpdata['color_v'] = 'green';
                        $tmpdata['status_str'] = '审核通过';
                    } elseif ($tmpdata['status'] == 2) {
                        $tmpdata['color_v'] = 'red';
                        $tmpdata['status_str'] = '审核不通过';
                    }
                    $data['list'][] = $tmpdata;
                    $data['count']++;
                }
            }
            //没有记录的从 checkauthSet['check_level'] 里取
            if (!empty($checkauthSet) && !empty($checkauthSet['check_level'])) {
                $db_houseWorker = new HouseWorker();
                foreach ($checkauthSet['check_level'] as $cvv) {
                    if ($cvv['level_v'] > $tmp_level) {
                        $tmpdata['id'] = 0;
                        $tmpdata['apply_id'] = $apply_id;
                        $tmpdata['order_id'] = $order_id;
                        $tmpdata['property_id'] = $property_id;
                        $tmpdata['village_id'] = $village_id;
                        $tmpdata['wid'] = $cvv['level_wid'];
                        $tmpdata['level'] = $cvv['level_v'];
                        $tmpdata['status'] = 0;
                        $tmpdata['apply_time'] = 0;
                        $tmpdata['apply_time_str'] = '';
                        $tmpdata['bak'] = '';
                        $tmpdata['color_v'] = 'gray';
                        $tmpdata['status_str'] = '未审核';
                        $field = 'wid,village_id,phone,name,openid,status,type,property_id,people_type,department_id,account';
                        $whereArr = array('wid' => $cvv['level_wid'], 'village_id' => $village_id);
                        $worker = $db_houseWorker->get_one($whereArr, $field);
                        if (!$worker || $worker->isEmpty()) {
                            $tmpdata['pname'] = '';
                            $tmpdata['phone'] = '';
                            $tmpdata['openid'] = '';
                            $tmpdata['department_id'] = 0;
                        } else {
                            $worker = $worker->toArray();
                            $tmpdata['pname'] = $worker['name'];
                            $tmpdata['phone'] = $worker['phone'];
                            $tmpdata['openid'] = $worker['openid'];
                            $tmpdata['department_id'] = $worker['department_id'];
                        }
                        $data['list'][] = $tmpdata;
                        $data['count']++;
                    }
                }
            }
            return $data;
        }

        /**
         * 物业服务时间审核
         * User: zhanghan
         * Date: 2022/2/10
         * Time: 15:06
         */
        public
        function verifyCheckauthApplyService($apply_id, $status, $bak, $village_id, $wid)
        {
            $checkauth_apply = new HouseVillageCheckauthApply();
            $checkauth_detail = new HouseVillageCheckauthDetail();
            $checkauth_set = new HouseVillageCheckauthSet();
            $templateNewsService = new TemplateNewsService();
            if (!$apply_id) {
                throw new Exception('参数异常');
            }
            if ($status == 2 && empty($bak)) {
                throw new Exception('请填写不通过原因');
            } else {
                $wid_count = 0;
                $level = 0;

                $whereArr = [];
                $whereArr[] = ['village_id', '=', $village_id];
                $whereArr[] = ['xtype', '=', 'service_time_check'];
                $service_time_check = $checkauth_set->getOne($whereArr);
                if (empty($service_time_check)) {
                    throw new Exception('您无权限审核');
                } else {
                    $rule_list = json_decode($service_time_check['set_datas'], true);
                    if (empty($rule_list)) {
                        throw new Exception('您无权限审核');
                    } else {
                        foreach ($rule_list as $v) {
                            $wid_count += 1;
                            if ($v['level_wid'] == $wid) {
                                $level = $v['level_v'];
                            }
                        }
                        if (!$wid) {
                            throw new Exception('您无权限审核');
                        }
                    }
                }

                $where = [];
                $where[] = ['id', '=', $apply_id];
                $where[] = ['status', 'in', [0, 1]];
                $reply_info = $checkauth_apply->getOne($where);
                if (!$reply_info) {
                    throw new Exception('没有正在审核的物业服务时间');
                } else {
                    $where_detail = [];
                    $where_detail[] = ['apply_id', '=', $reply_info['id']];
                    $where_detail[] = ['wid', '=', $wid];
                    $reply_detail_info = $checkauth_detail->getOne($where_detail);
                    if ($reply_detail_info && $reply_detail_info['status'] > 0) {
                        throw new Exception('您已经审核过了，无法再次审核');
                    }
                    if ($wid_count == 1) {
                        $res = $checkauth_apply->updateApply([['id', '=', $reply_info['id']]], ['status' => $status + 1, 'apply_time' => time()]);
                        if ($status == 1) {
                            //todo 更新house_new_order_log物业服务时间
                            $this->update_order_log($reply_info, $village_id);
                        }
                    } else {
                        if ($status == 2) {
                            $res = $checkauth_apply->updateApply([['id', '=', $reply_info['id']]], ['status' => 3, 'apply_time' => time()]);
                        } else {
                            $res = $checkauth_apply->updateApply([['id', '=', $reply_info['id']]], ['status' => $level + 1, 'apply_time' => time()]);
                            //todo 更新house_new_order_log物业服务时间
                            $this->update_order_log($reply_info, $village_id);
                        }
                    }
                    if ($res) {
                        $house_worker = new HouseWorker();
                        $worker_info = $house_worker->get_one([['wid', '=', $wid]]);
                        if (!empty($reply_detail_info)) {
                            // 更新
                            $res = $checkauth_detail->updateDetail($where_detail, [
                                'status' => $status,
                                'bak' => $bak,
                                'apply_time' => time(),
                            ]);
                        } else {
                            // 插入
                            $res = $checkauth_detail->addDetail([
                                'apply_id' => $reply_info['id'],
                                'property_id' => $reply_info['property_id'],
                                'village_id' => $village_id,
                                'wid' => $wid,
                                'level' => $level,
                                'status' => $status,
                                'bak' => $bak,
                                'apply_time' => time(),
                                'add_time' => time(),
                                'extra_data' => json_encode([
                                    'wid' => $wid,
                                    'village_id' => $worker_info['village_id'],
                                    'phone' => $worker_info['phone'],
                                    'name' => $worker_info['name'],
                                    'openid' => $worker_info['openid'],
                                    'status' => $worker_info['status'],
                                    'type' => $worker_info['type'],
                                    'property_id' => $worker_info['property_id'],
                                    'people_type' => $worker_info['people_type'],
                                    'department_id' => $worker_info['department_id'],
                                    'account' => $worker_info['account']
                                ], JSON_UNESCAPED_UNICODE)
                            ]);
                        }

                        if ($res) {
                            // 审核通过，插入下一级未审核数据 物业服务时间审核只有两人
                            if ($status == 1 && $level < $wid_count - 1) {
                                $houseAppJpushInfoService = new HouseAppJpushInfoService();
                                $worker_info_sup = $house_worker->get_one([['wid', '=', $rule_list[1]['level_wid']]]);
                                $checkauth_detail->addDetail([
                                    'apply_id' => $reply_info['id'],
                                    'property_id' => $reply_info['property_id'],
                                    'village_id' => $village_id,
                                    'wid' => $rule_list[1]['level_wid'],
                                    'level' => $rule_list[1]['level_v'],
                                    'status' => 0,
                                    'apply_time' => time(),
                                    'add_time' => time(),
                                    'extra_data' => json_encode([
                                        'wid' => $rule_list[1]['level_wid'],
                                        'village_id' => $worker_info_sup['village_id'],
                                        'phone' => $worker_info_sup['phone'],
                                        'name' => $worker_info_sup['name'],
                                        'openid' => $worker_info_sup['openid'],
                                        'status' => $worker_info_sup['status'],
                                        'type' => $worker_info_sup['type'],
                                        'property_id' => $worker_info_sup['property_id'],
                                        'people_type' => $worker_info_sup['people_type'],
                                        'department_id' => $worker_info_sup['department_id'],
                                        'account' => $worker_info_sup['account']
                                    ], JSON_UNESCAPED_UNICODE)
                                ]);
                                $otherData = array('village_id' => $village_id, 'wid' => $rule_list[1]['level_wid']);
                                $houseAppJpushInfoService->handleJpushInfoSend($otherData, 'checkauth_apply_tips');
                                if (!empty($worker_info_sup) && !empty($worker_info_sup['openid'])) {
                                    $href = cfg('site_url') . '/packapp/community/pages/Community/reviewRefund/myApproval';
                                    $houseVillageUserBind = new HouseVillageUserBind();
                                    $bind_info = $houseVillageUserBind->getOne([['pigcms_id', '=', $reply_info['pigcms_id']]], 'name,single_id,floor_id,layer_id,vacancy_id,village_id');
                                    $apply_name = $reply_info['apply_name'];
                                    $address = '无';
                                    if ($bind_info && !$bind_info->isEmpty()) {
                                        $bind_info = $bind_info->toArray();
                                        $apply_name = $bind_info['name'];
                                        $address = (new HouseVillageService())->getSingleFloorRoom($bind_info['single_id'], $bind_info['floor_id'], $bind_info['layer_id'], $bind_info['vacancy_id'], $bind_info['village_id']);
                                    }
                                    $datamsg = [
                                        'tempKey' => 'OPENTM417740354',//todo 类目模板OPENTM417740354
                                        'dataArr' => [
                                            'href' => $href,
                                            'wecha_id' => $worker_info_sup['openid'],
                                            'first' => '审核提醒',
                                            'keyword1' => '修改物业服务时间申请',
                                            'keyword2' => $apply_name,
                                            'keyword3' => date('Y-m-d H:i:s', $reply_info['add_time']),
                                            'remark' => '您有一个新的申请消息需要审核！',
                                            'new_info' => [//新版本发送需要的信息
                                                'tempKey' => '43258',//新模板号
                                                'thing2' => '修改物业服务时间申请',//流程名称
                                                'thing9' => $apply_name,//发起人
                                                'time10' => date('Y-m-d H:i:s', $reply_info['add_time']),//发起时间
                                                'thing7' => $address,//房源名称
                                            ],
                                        ]
                                    ];
                                    $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
                                }
                            }
                            return true;
                        } else {
                            return false;
                        }
                    } else {
                        throw new Exception('更新失败');
                    }
                }
            }
        }

        /**
         * 更新house_new_order_log物业服务时间
         * User: zhanghan
         * Date: 2022/2/10
         * Time: 15:48
         * @param $reply_info
         * @param $village_id
         */
        public function update_order_log($reply_info, $village_id)
        {
            $order_log = new HouseNewOrderLog();
            $user_bind = new HouseVillageUserBind();
            $userBindInfo = $user_bind->getOne([['pigcms_id', '=', $reply_info['pigcms_id']]], 'vacancy_id');
            $userBindInfo = ($userBindInfo && !$userBindInfo->isEmpty()) ? $userBindInfo->toArray() : [];
            $temp = [
                'order_type' => 'property',
                'order_name' => '物业费',
                'room_id' => !empty($userBindInfo) ? $userBindInfo['vacancy_id'] : 0,
                'village_id' => $village_id,
                'property_id' => $reply_info['property_id'],
                'desc' => '业主列表修改物业服务时间',
                'service_start_time' => $reply_info['service_start_time'],
                'service_end_time' => $reply_info['service_end_time'],
                'add_time' => time()
            ];
            $room_id = !empty($userBindInfo) ? $userBindInfo['vacancy_id'] : 0;
            $order_log->addOne($temp);
            $order_log_info = $order_log->getOne(['room_id' => $room_id, 'order_type' => 'property'], '*', 'id ASC');
            if (!empty($order_log_info)) {
                $order_log->saveOne(['room_id' => $room_id, 'order_type' => 'property', 'id' => $order_log_info['id']], ['service_start_time' => $reply_info['service_start_time']]);
            }
        }
    }