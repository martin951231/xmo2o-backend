<?php


namespace app\community\controller\village_api;


use app\common\model\service\config\ConfigCustomizationService;
use app\community\model\service\CommonPickupPersonService;
use app\community\model\service\ExpressPickupService;
use app\community\controller\CommunityBaseController;
use app\community\model\db\HouseVillage;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseVillageUserBindService;
use app\community\model\service\HouseVillageUserVacancyService;
use app\community\model\db\HouseVillageUserBind;

class ExpressPickUpApiController extends CommunityBaseController
{
    /**
     * 获取用户信息
     * @return \json
     * @author mashuaifei
     * @date_time 2025/09/23
     */
    public function getUserinfo($pigcms_id)
    {
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
        $bind_info = $service_house_village_user_bind->getBindInfo(['pigcms_id' => $pigcms_id], 'vacancy_id,village_id,type');
        $vacancy_id = isset($bind_info['vacancy_id']) && intval($bind_info['vacancy_id']) > 0 ? intval($bind_info['vacancy_id']) : -1;
        $user_info = [];
        if ($vacancy_id > 0) {
            $user_info = $service_house_village_user_vacancy->getUserVacancyInfo(['pigcms_id' => $vacancy_id], 'usernum,name,phone,single_id,floor_id,layer_id,room,pigcms_id,village_id');
        }
        $single_id = $user_info['single_id'];
        $floor_id = $user_info['floor_id'];
        $layer_id = $user_info['layer_id'];
        $room_id = $user_info['pigcms_id'];
        $room_num = $user_info['room'];
        return [
            'single_id' => $single_id,
            'floor_id' => $floor_id,
            'layer_id' => $layer_id,
            'room_id' => $room_id,
            'room_num' => $room_num,
            'room_name' => $user_info['usernum'],
        ];
    }
    /**
     * 代取地址列表
     * @return \json
     * @author mashuaifei
     * * @date_time 2025/09/17
     */
    public function getExpressPickupLocationList()
    {
        $pigcms_id = $this->request->post('pigcms_id');
        if (!$pigcms_id) {
            return api_output_error(1002, '登录信息错误,请重新登录!');
        }
        $village_id = $this->request->post('village_id');
        $location = $this->request->post('location');
        $page = $this->request->post('page', 1);
        $limit = $this->request->post('limit', 10);
        $service_express_pick_up = new ExpressPickupService();
        $chargeType = ['water', 'electric', 'gas'];
        //        $jz_wsxdsj_customized_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        //        if($jz_wsxdsj_customized_open){
        //            array_push($chargeType,'new_nuanqifei');
        //        }
        $where[] = ['village_id', '=', $village_id];
        $where[] = ['status', '=', 1];
        try {
            if ($location != '') {
                $where[] = ['location', 'like', '%' . $location . '%'];
            }
            $data = $service_express_pick_up->getExpressPickupLocationList($where, true, $page, $limit, 'p.id DESC')->toArray();

            $count = $service_express_pick_up->getExpressPickupLocationCount($where);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        $res = array();
        $res['total_limit'] = $limit;
        $res['list'] = $data;
        $res['count'] = $count;

        //        $houseVillageService=new HouseVillageService();
        //        $res['role_addmeter']=$houseVillageService->checkPermissionMenu(112104,$this->adminUser,$this->login_role,$this->dismissPermissionRole);


        return api_output(0, $res);
    }
    /**
     * 添加代取地址
     * @return \json
     * @author mashuaifei
     * * @date_time 2025/09/17
     */
    public function saveExpressPickupLocation()
    {
        $current_action = $this->request->post('current_action');
        $village_id = $current_action == 'edit' ? $this->request->post('village_id') : $this->adminUser['village_id'];
        $location = $this->request->post('location');
        $pickup_point_id = $this->request->post('id');
        if (!$location) {
            return api_output_error(1001, '请填写代取地址!');
        }
        if (!$village_id) {
            return api_output_error(1002, '用户身份信息获取错误,请重新登录!');
        }
        $data = [];
        $data['status'] = 1;
        $data['location'] = $location;
        if ($current_action == 'edit') {
            $data['update_by'] = $this->adminUser['user_name'];
            $data['update_time'] = date('Y-m-d H:i:s');
        } else {
            $data['create_by'] = $this->adminUser['user_name'];
            $data['create_time'] = date('Y-m-d H:i:s');
        }

        $data['village_id'] = $village_id;
        $service_express_pick_up = new ExpressPickupService();

        $exists = $service_express_pick_up->getExpressPickupLocationCount([
            'status' => 1,
            'village_id' => $data['village_id'],
            'location' => $data['location']
        ]);

        if ($exists > 0) {
            return api_output_error(1001, '代取地址已存在!');
        }

        try {
            if ($current_action == 'edit') {
                $res = $service_express_pick_up->savePickupLocation(['id' => $pickup_point_id], $data);
            } else {
                $res = $service_express_pick_up->addPickupLocation($data);
            }
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if ($res)
            return api_output(0, [], '保存成功');
        return api_output_error(1001, '服务异常');
    }
    public function delExpressPickupLocation()
    {
        $pickup_point_id = $this->request->post('id');
        $service_express_pick_up = new ExpressPickupService();
        if (!$pickup_point_id) {
            return api_output_error(1001, '请选择要删除的地址!');
        }
        try {
            $res = $service_express_pick_up->savePickupLocation(['id' => $pickup_point_id], ['status' => 0]);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if ($res)
            return api_output(0, [], '删除成功');
        return api_output_error(1001, '服务异常');
    }

    /**
     * 常用取件人列表
     * @return \json
     * @author mashuaifei
     * @date_time 2025/09/18
     */
    public function getExpressApplicationList()
    {
        $pigcms_id = $this->request->post('pigcms_id');
        if (!$pigcms_id) {
            return api_output_error(1002, '登录信息错误,请重新登录!');
        }
        $village_id = $this->request->post('village_id');
        //        $location_id = $this->request->post('location_id');
        $user_id = $this->request->post('uid');
        $recipient_name = $this->request->post('recipient_name');
        $recipient_phone = $this->request->post('recipient_phone');
        $search_list = $this->request->post('search');
        $page = $this->request->post('pageIndex', 1);
        $limit = $this->request->post('pageSize', 10);
        $userInfo = $this->getUserinfo($pigcms_id);
        $service_express_pick_up = new ExpressPickupService();

        $where[] = ['p.village_id', '=', $village_id];
//        $where[] = ['p.status', '<>', 99];
        //        $location_id && $where[] = ['p.location_id', '=', $location_id];

        $userInfo['single_id'] && $where[] = ['p.single_id', '=', $userInfo['single_id']];
        $userInfo['floor_id'] && $where[] = ['p.floor_id', '=', $userInfo['floor_id']];
        $userInfo['layer_id'] && $where[] = ['p.layer_id', '=', $userInfo['layer_id']];
        $userInfo['room_id'] && $where[] = ['p.room_id', '=', $userInfo['room_id']];
        $user_id && $where[] = ['p.user_id', '=', $user_id];

        $recipient_name && $where[] = ['p.recipient_name', 'like', '%' . $recipient_name . '%'];
        $recipient_phone && $where[] = ['p.recipient_phone', 'like', '%' . $recipient_phone . '%'];
        $field = 'p.*,e.location';

        if ($search_list['keyword'] != '') {
            $where[] = ['p.track_num|p.pickup_code|p.recipient_phone|p.recipient_name|e.location', 'like', '%' . trim($search_list['keyword']) . '%'];
        }

        if ($search_list['date_start'] != '') {
            $where[] =  ['p.create_time', '>=', $search_list['date_start']];
        }
        if ($search_list['date_end'] != '') {
            $where[] =  ['p.create_time', '<=', $search_list['date_end']];
        }
        if (count($search_list['locationId'])  > 0) {
            $where[] =  ['p.location_id', 'in', $search_list['locationId']];
        }
        //        var_dump($search_list['status']);die;

        if (count($search_list['status'])  > 0 && !in_array('all', $search_list['status'])) {
            $where[] =  ['p.status', 'in', $search_list['status']];
        }
        $order_where = $search_list['sort'] != '' ? 'p.create_time ' . $search_list['sort'] . ' , p.status DESC '  : 'p.id DESC , p.status DESC ';
        try {
            $data = $service_express_pick_up->getExpressApplicationList($where, $field, $page, $limit, $order_where)->toArray();

            $count = $service_express_pick_up->getExpressApplicationCount($where);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        $res = array();
        // 总页数 = 向上取整(总条数 / 每页条数)
        $totalPage = (int)ceil($count / $limit);

        // 是否有下一页：当前页 < 总页数
        $hasNextPage = $page < $totalPage;
        $res['total_limit'] = $limit;
        $res['list'] = $data;
        $res['count'] = $count;
        $res['hasMore'] = $hasNextPage;
        return api_output(0, $res);
    }

    /**
     * 添加申请
     * @return \json
     * @author mashuaifei
     * @date_time 2025/09/24
     */
    public function saveExpressApplication()
    {
        $pigcms_id = $this->request->post('pigcms_id');
//        $pigcms_id = 7025;
//        $pigcms_id = 6404;
        if (!$pigcms_id) {
            return api_output_error(1002, '登录信息错误,请重新登录!');
        }
        if ($this->request->post('pickup_code') == '') {
            return api_output_error(1001, '请填写取件码!');
        }
        if ($this->request->post('location_id') == '') {
            return api_output_error(1001, '请选择取件地址!');
        }
        if ($this->request->post('recipient_name') == '') {
            return api_output_error(1001, '请填写收件人姓名!');
        }
        if ($this->request->post('recipient_phone') == '') {
            return api_output_error(1001, '请填写收件人电话!');
        }
        $current_action = $this->request->post('current_action');
        $pickup_id = $this->request->post('id');
        $village_id = $this->request->post('village_id');

        $data = [];
        $data['status'] = 1;
        if ($current_action == 'edit') {
            $data['update_by'] = $this->adminUser['user_name'];
            $data['update_time'] = date('Y-m-d H:i:s');
        } else {
            $data['create_by'] = $this->adminUser['user_name'];
            $data['create_time'] = date('Y-m-d H:i:s');
        }
        $data['village_id'] = $village_id;
        $data['remark'] = $this->request->post()['remark'];
        $data['user_id'] = $this->request->post()['uid'];
        $data['pickup_code'] = $this->request->post()['pickup_code'];
        if ($this->request->post()['pickup_code']) {
            $data['track_num'] = $this->request->post()['track_num'];
        }

        $data['location_id'] = $this->request->post()['location_id'];
        //查看当前收件人是否为默认收件人, 如果不存在, 则添加默认收件人, 如果存在但不是默认, 则更新为默认, 否则不做处理
        $service_common_pickup_person = new CommonPickupPersonService();
        $recipient_info_where = [
            'p.recipient_name' => $this->request->post()['recipient_name'],
            'p.recipient_phone' => $this->request->post()['recipient_phone'],
            'p.village_id' => $village_id,
            'p.user_id' => $this->request->post()['uid'],
        ];
        $field = 'p.*,e.location';
        $recipient_info = $this->request->post()['recipient_id'] ? $service_common_pickup_person->getCommonPickupPersonInfo($recipient_info_where, $field) : [];
        $data['location_id'] = $this->request->post()['location_id'];

        $userInfo = $this->getUserinfo($pigcms_id);

        $data['single_id'] = $userInfo['single_id'];
        $data['floor_id'] = $userInfo['floor_id'];
        $data['layer_id'] = $userInfo['layer_id'];
        $data['room_id'] = $userInfo['room_id'];
        $data['room_name'] = $userInfo['room_name'];
        $data['create_by'] = $userInfo['name'];
        $data['recipient_name'] = $this->request->post()['recipient_name'];
        $data['recipient_phone'] = $this->request->post()['recipient_phone'];
        //如果没有绑定房间, 则提示他绑定房间
        if( !$userInfo['room_name'] ){
            return api_output_error(1001, '当前用户暂未绑定房间,请先绑定房间再尝试提交申请!');
        }

        $service_express_pick_up = new ExpressPickupService();

        $service_house_village = new HouseVillageService();
        $service_house_village_info = $service_house_village->getHouseVillageInfo(['village_id' => $village_id], 'express_person_average_entrust,express_project_daily_entrust,express_project_startDate,express_project_endDate');
        $express_person_average_entrust = $service_house_village_info['express_person_average_entrust'];
        $express_project_daily_entrust = $service_house_village_info['express_project_daily_entrust'];
        //查看是否签署了协议, 如果没有签署, 则不可以提交申请
        $where_user_bind=[
            ['village_id','=',$village_id],
            ['single_id','=',$userInfo['single_id']],
            ['floor_id','=',$userInfo['floor_id']],
            ['layer_id','=',$userInfo['layer_id']],
            ['room_addrss','=',$userInfo['room_num']],
            ['status','in',[1]],
        ];
        $field='pigcms_id,single_id,floor_id,layer_id,room_addrss,uid,type,bind_number,name,phone,vacancy_id,id_card,ic_card,nmv_card,memo,authentication_field';
        $HouseVillageUserBind = new HouseVillageUserBind();
        $user_bind_info = $HouseVillageUserBind->getList($where_user_bind,$field)->toArray();
        $auth_key = 0;
        //循环列表, 其中有一个签署了协议, 则认定为该户已签署协议
        foreach ($user_bind_info as $key => $value) {
            $authentication_field = $value['authentication_field'] ? unserialize( $value['authentication_field'] ) : [];
            if( $authentication_field && isset($authentication_field['is_sign_agreement']) && $authentication_field['is_sign_agreement']['value'] == '是' ) {
                $auth_key++;break;
            }
        }
        if( $auth_key <= 0 ){
            return api_output_error(1001, '请先到物业签署快递代取免责说明，签署后方可提供该服务!');
        }
        //如果有限制, 则判断当前用户是否超过限制
        if ($express_person_average_entrust > 0 || $express_project_daily_entrust > 0) {
            // $where = [
            //     'p.village_id' => $village_id,
            //     'p.room_id' => $userInfo['room_id'],
            // ];
            // 'p.create_time' => ['>=', date('Y-m-d 00:00:00')],
            // 'p.create_time' => ['<=', date('Y-m-d 23:59:59')],
            // $where[] =  ['p.create_time', '>=', $search_list['date_start']];
            $stratDate = date('Y-m-d 00:00:00');
            $endDate = date('Y-m-d 23:59:59');
            $where[] = ['p.village_id', '=', $village_id];
            $where[] = ['p.room_id', '=', $userInfo['room_id']];
            $where[] = ['p.create_time', 'between', [$stratDate, $endDate]];

            $count = $service_express_pick_up->getExpressApplicationCount($where);

            if ($express_person_average_entrust > 0 && $count >= $express_person_average_entrust) {
                return api_output_error(1001, '每户每天最多只能申请' . $express_person_average_entrust . '次!');
            }
            if ($express_project_daily_entrust > 0 && $count >= $express_project_daily_entrust) {
                return api_output_error(1001, '每天最多只能申请' . $express_project_daily_entrust . '次!');
            }
        }
        //如果设置了限制申请时间, 则判断当前时间是否在限制时间内, 如果在时间范围内则不允许提交申请
        $express_apply_start_time = $service_house_village_info['express_project_startDate'];
        $express_apply_end_time = $service_house_village_info['express_project_endDate'];

        if ($express_apply_start_time || $express_apply_end_time) {
            //判断当前时间是否在限制时间内
            $is_includes = $this->isTimeInRange($express_apply_start_time, $express_apply_end_time);
            if ($is_includes) {
                $phase1 = '';
                $phase2 = '';
                if( $express_apply_start_time >= '00:00' && $express_apply_start_time != '' ){
                    $phase1 = '00:00-'. $express_apply_start_time;
                }

                if( $express_apply_end_time < '23:59' && $express_apply_end_time != '' ){
                    $phase2 = $express_apply_end_time . '-23:59';
                }
                $phase_str = '';
                if( $phase1 != '' && $phase2 != '' ){
                    $phase_str = ' 及 ';
                }
                $phase_end = $phase1 . $phase_str . $phase2;

                return api_output_error(1001, '申请时间不在限制时间内,请在' . $phase_end. '之内提交申请!');
            }
        }

        if (!$recipient_info) {
            //如果当前收件人不存在, 则添加为默认收件人,返回收件人id, 并且把其余收件人设置为非默认
            //先把其他收件人设置为非默认
            $service_common_pickup_person->savePickupLocation([
                'village_id' => $village_id,
                'user_id' => $this->request->post()['uid'],
                'is_default' => 1,
            ], ['is_default' => 0]);
            $defalut_data = [
                'village_id' => $village_id,
                'user_id' => $this->request->post()['uid'],
                'recipient_name' => $this->request->post()['recipient_name'],
                'recipient_phone' => $this->request->post()['recipient_phone'],
                'location_id' => $this->request->post()['location_id'],
                'is_default' => 1,
                'create_by' => $this->adminUser['user_name'],
                'create_time' => date('Y-m-d H:i:s'),
                'single_id' => $userInfo['single_id'],
                'floor_id' => $userInfo['floor_id'],
                'layer_id' => $userInfo['layer_id'],
                'room_id' => $userInfo['room_id'],
                'room_name' => $userInfo['room_name'],
            ];
            $data['recipient_id'] = $service_common_pickup_person->addPickupLocation($defalut_data);
        } else {
            $data['recipient_id'] = $this->request->post()['recipient_id'];
            if ($recipient_info['is_default'] !== 1) {
                //先把其他收件人设置为非默认
                $service_common_pickup_person->savePickupLocation([
                    'village_id' => $village_id,
                    'user_id' => $this->request->post()['uid'],
                    'is_default' => 1,
                ], ['is_default' => 0]);
                //把当前这条设置为默认
                $service_common_pickup_person->savePickupLocation([
                    'village_id' => $village_id,
                    'recipient_name' => $recipient_info['recipient_name'],
                    'recipient_phone' => $recipient_info['recipient_phone'],
                    'user_id' => $this->request->post()['uid'],
                    'is_default' => 0,
                ], ['is_default' => 1]);
            }
        }


        try {
            if ($current_action == 'edit') {
                $res = $service_express_pick_up->savePickupApplication(['id' => $pickup_id], $data);
            } else {
                $res = $service_express_pick_up->addPickupApplication($data);
            }
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if ($res)
            return api_output(0, [], '保存成功');
        return api_output_error(1001, '服务异常');
    }
    /**
     * 查看收件人是否为默认收件人
     * @return \json
     * @author mashuaifei
     * @date_time 2025/09/18
     */
    public function checkDefaultRecipient($userInfo)
    {
        $recipient_id = $this->request->post('recipient_id');
        $service_express_pick_up = new ExpressPickupService();
        $res = $service_express_pick_up->checkDefaultRecipient($recipient_id);
    }
    /**
     * 删除快递
     * @return \json
     * @author mashuaifei
     * @date_time 2025/09/18
     */
    public function delExpressApplication()
    {
        $pigcms_id = $this->request->post('pigcms_id');
        if (!$pigcms_id) {
            return api_output_error(1002, '登录信息错误,请重新登录!');
        }
        $pickup_app_id = $this->request->post('id');
        $service_express_pick_up = new ExpressPickupService();
        if (!$pickup_app_id) {
            return api_output_error(1001, '请选择要删除的快递!');
        }
        try {
            $res = $service_express_pick_up->savePickupApplication(['id' => $pickup_app_id], ['status' => 99,'del_time' => date('Y-m-d H:i:s')]);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if ($res)
            return api_output(0, [], '删除成功');
        return api_output_error(1001, '服务异常');
    }

    function isTimeInRange($startTime = null, $endTime = null, $currentTime = null)
    {
        // 处理当前时间（默认实时时间）
        $currentTime = $currentTime ?? time();

        // 提取当前时间的时分并转换为分钟数
        $currentHour = (int)date('H', $currentTime);
        $currentMinute = (int)date('i', $currentTime);
        $currentTotal = $currentHour * 60 + $currentMinute;

        // 处理开始时间（仅判断是否在开始时间之后）,如果有开始时间, 没有结束时间, 则开始时间之后的都可以
        if ($startTime !== null && ( $endTime === null || $endTime === '') ) {
            // 验证时间格式有效性
            if (!preg_match('/^\d{2}:\d{2}$/', $startTime)) {
                return false; // 时间格式不正确时视为不匹配
            }

            list($startHour, $startMinute) = explode(':', $startTime);
            // 转换为整数并验证范围
            $startHour = (int)$startHour;
            $startMinute = (int)$startMinute;

            if ($startHour < 0 || $startHour > 23 || $startMinute < 0 || $startMinute > 59) {
                return false; // 小时或分钟超出有效范围
            }

            $startTotal = $startHour * 60 + $startMinute;
            return $currentTotal >= $startTotal;
        }

        // 处理结束时间（仅判断是否在结束时间之前）
        if ($endTime !== null && ( $startTime === null || $startTime === '')) {
            list($endHour, $endMinute) = explode(':', $endTime);
            $endTotal = (int)$endHour * 60 + (int)$endMinute;
            return $currentTotal <= $endTotal;
        }

        // 处理完整范围（同时有开始和结束时间）
        if ($startTime !== null && $endTime !== null) {
            list($startHour, $startMinute) = explode(':', $startTime);
            list($endHour, $endMinute) = explode(':', $endTime);
            $startTotal = (int)$startHour * 60 + (int)$startMinute;
            $endTotal = (int)$endHour * 60 + (int)$endMinute;
            return $currentTotal >= $startTotal && $currentTotal <= $endTotal;
        }

        // 都为null时默认返回true（无时间限制）
        return true;
    }
}
