<?php

class ThirdImportAction extends BaseAction
{
    public function index() {
        $third_import_data_switch_judge = D('Config_customization')->getThirdImportDataSwitch();
        $this->assign('third_import_data_switch_judge',$third_import_data_switch_judge);
        if (!$third_import_data_switch_judge) {
            $this->error('不支持该功能！');
        }
        $this->display();
    }


    public function bindPositionOrderAndRoom() {
        $third_import_data_switch_judge = D('Config_customization')->getThirdImportDataSwitch();
        if (!$third_import_data_switch_judge) {
            $this->error('不支持该功能！');
        }
        $village_id = $this->house_session['village_id'];
        $where_village = [
            'village_id' => $village_id
        ];
        $bindList = D('House_village_bind_position')->where($where_village)->select();
        $db_house_village_user_bind =  M('House_village_user_bind');
        $db_house_new_pay_order = M('House_new_pay_order');
        $orderList = [];
        $userBindPositionIdArr = [];
        fdump_api($bindList, '$bindList');
        foreach ($bindList as $item) {
            $user_id     = $item['user_id'];
            $position_id = $item['position_id'];
            $userBind = $db_house_village_user_bind->where(['pigcms_id' => $user_id])->field('pigcms_id, vacancy_id, name, phone')->find();
            if (!empty($userBind)) {
                $whereOrderInfo = [
                    'village_id'  => $village_id,
                    'room_id'     => 0,
                    'position_id' => $position_id,
                ];
                $orders = $db_house_new_pay_order->where($whereOrderInfo)->field('order_id, pigcms_id, name, phone, position_id')->select();
                if (!empty($orders)) {
                    $orderList = array_merge($orderList, $orders);
                }
                $userBindPositionIdArr[$position_id] = $userBind;
                $db_house_new_pay_order_summary = M('House_new_pay_order_summary');
                $whereSummary = [
                    'village_id'  => $village_id,
                    'room_id'     => 0,
                    'position_id' => $position_id,
                ];
                $saveSummary = [
                    'room_id'   => $userBind['vacancy_id'],
                    'pigcms_id' => $user_id,
                ];
                $db_house_new_pay_order_summary->where($whereSummary)->save($saveSummary);
            }
        }
        $count_order = 0;
        fdump_api($orderList, '$orderList');
        fdump_api($userBindPositionIdArr, '$userBindPositionIdArr');
        foreach ($orderList as $order) {
            $userBind = isset($userBindPositionIdArr[$order['position_id']]) && $userBindPositionIdArr[$order['position_id']] ? $userBindPositionIdArr[$order['position_id']] : 0;
            if (empty($userBind)) {
                continue;
            }
            $saveOrder = [
                'room_id'   => $userBind['vacancy_id'],
            ];
            $user_id     = $userBind['pigcms_id'];
            if (!$order['pigcms_id']) {
                $saveOrder['pigcms_id'] = $user_id;
            }
            if (!$order['name'] && $userBind['name']) {
                $saveOrder['name'] = $userBind['name'];
            }
            if (!$order['phone'] && $userBind['phone']) {
                $saveOrder['phone'] = $userBind['phone'];
            }
            $db_house_new_pay_order->where(['order_id' => $order['order_id']])->save($saveOrder);
            $count_order++;
        }
        $return = [
            'count_order' => $count_order,
            'orderList'   => $orderList,
        ];
        $this->success($return);
    }
}