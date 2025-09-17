<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      楼栋、单元、楼层管理控制器
 */

namespace app\community\controller\village_api;

use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseVillageConfigService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseVillageSingleService;
use app\community\model\service\HouseVillageUserVacancyService;
use app\community\model\service\HouseWorkerService;
use app\community\model\service\KefuService;
use app\community\model\service\PropertyFrameworkService;
use app\traits\house\HouseTraits;

class UnitRentalController extends CommunityBaseController
{

    use HouseTraits;

    /**
     * 楼栋列表
     * @return \json
     */
    public function index()
    {

        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        try {
            $houseVillageSignService = new HouseVillageSingleService();
            $where = [];
            $where[] = ['village_id', '=', $village_id];
            $where[] = ['is_public_rental', '=', 1];
            $where[] = ['status', '<>', 4];
            $count = $houseVillageSignService->getBuildingCount($where);
            $field = "id,single_name,status,floor_num,vacancy_num,single_number,village_id,`sort`,measure_area,upper_layer_num,lower_layer_num,contract_time_start,contract_time_end";
            $oderby = ['sort' => 'DESC', 'id' => 'DESC'];
            $info = $houseVillageSignService->getList($where, $field, $oderby);
            $buiding = [];
            if ($info && !$info->isEmpty()) {
                $buiding = $info->toArray();
                foreach ($buiding as &$item) {
                    $item['contract_time_start'] = ($item['contract_time_start'] > 1) ? date('Y-m-d', $item['contract_time_start']) : '-';
                    $item['contract_time_end'] = ($item['contract_time_end'] > 1) ? date('Y-m-d', $item['contract_time_end']) : '-';
                }
                $data = [
                    'count' => $count,
                    'building' => $buiding,

                ];
                return api_output(0, $data);
            }
            return api_output(0, ['count'=>0,'building'=>array()]);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * 需要传递 single_id 楼栋id
     * 获取楼栋详细信息
     * @return \json
     */
    public function unitRentalInfo()
    {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }

        $single_id = $this->request->post('single_id', 0);
        $houseVillageInfoService = new HouseVillageService();
        $villageInfo = $houseVillageInfoService->getHouseVillageInfoExtend(['village_id' => $village_id], 'contract_time_end,contract_time_start');
        if ($single_id <= 0) {
            $buiding = array(
                "contract_time_end" => "",
                "contract_time_start" => "",
                "floor_num" => "",
                "id" => 0,
                "img" => "",
                "lat" => "",
                "long" => "",
                "lower_layer_num" => "",
                "measure_area" => "",
                "single_name" => "",
                "single_number" => "",
                "sort" => 0,
                "upper_layer_num" => "",
                "vacancy_num" => "",
                "village_contract_time_end" => "",
                "village_contract_time_start" => "",
                "village_id" => 50,
            );
            //$buiding["lat"]="39.8315";
            //$buiding["long"]="116.435852";
            $buiding['village_contract_time_start'] = ($villageInfo['contract_time_start'] > 1) ? date('Y-m-d', $villageInfo['contract_time_start']) : '';
            $buiding['village_contract_time_end'] = ($villageInfo['contract_time_end'] > 1) ? date('Y-m-d', $villageInfo['contract_time_end']) : '';
            $buiding['contract_time_start'] = $buiding['village_contract_time_start'];
            $buiding['contract_time_end'] = $buiding['village_contract_time_end'];
            return api_output(0, $buiding);
        }
        $houseVillageSignService = new HouseVillageSingleService();
        $where = [
            'village_id' => $village_id,
            'id' => $single_id
        ];

        $info = $houseVillageSignService->getSingleInfo($where);
        $buiding = [];
        if ($info) {
            $buiding = $info->toArray();

            $buiding['contract_time_start'] = ($buiding['contract_time_start'] > 1) ? date('Y-m-d', $buiding['contract_time_start']) : '';
            $buiding['contract_time_end'] = ($buiding['contract_time_end'] > 1) ? date('Y-m-d', $buiding['contract_time_end']) : '';

            //合同时间，是楼栋的合同时间必须在小区设置的合同时间范围内
            if (!empty($villageInfo)) {
                $buiding['village_contract_time_start'] = ($villageInfo['contract_time_start'] > 1) ? date('Y-m-d', $villageInfo['contract_time_start']) : '';
                $buiding['village_contract_time_end'] = ($villageInfo['contract_time_end'] > 1) ? date('Y-m-d', $villageInfo['contract_time_end']) : '';
            } else {
                $buiding['village_contract_time_start'] = '';
                $buiding['village_contract_time_end'] = '';
            }
            return api_output(0, $buiding);
        }
        return api_output(1001, [], '异常访问！');
    }

    /**
     * 更新单个楼栋信息
     * @return \json
     */
    public function updateUnitRentalInfoByID()
    {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }

        $single_id = $this->request->post('single_id', 0);
        $contract_time_end = $this->request->post('contract_time_end', 0);
        $contract_time_start = $this->request->post('contract_time_start', 0);
        $single_name = $this->request->post('single_name', '', 'trim');
        $status = $this->request->post('status', 0);
        $sort = $this->request->post('sort', 0);
        $measure_area = $this->request->post('measure_area', 0);
        $floor_num = $this->request->post('floor_num', 0);
        $vacancy_num = $this->request->post('vacancy_num', 0);
        $single_number = $this->request->post('single_number', 0);
        $upper_layer_num = $this->request->post('upper_layer_num', 0);
        $lower_layer_num = $this->request->post('lower_layer_num', 0);
        $single_keeper_name = $this->request->post('single_keeper_name', '');
        $single_keeper_phone = $this->request->post('single_keeper_phone', '');
        $single_keeper_head = $this->request->post('single_keeper_head', '');
        $long= $this->request->post('long', '');
        $lat= $this->request->post('lat', '');
        if (empty($single_name)) {
            return api_output(1001, [], '请填写楼栋名称！');
        }

        $houseVillageSingleService = new HouseVillageSingleService();

        $where = [];
        $where[] = ['village_id', '=', $village_id];
        $where[] = ['single_name', '=', $single_name];
        $where[] = ['id', '<>', $single_id];
        $where[] = ['status', '<>', 4];
        $checkNameRepeat = $houseVillageSingleService->getSingleInfo($where);
        if ($checkNameRepeat) {
            return api_output(1001, [], '楼栋名称【' . $single_name . '】已存在,请重新添加!！');
        }

        if (empty($contract_time_end) || empty($contract_time_start)) {
            return api_output(1001, [], '请设置合同开始结束时间！');
        }

        if ($contract_time_start >= $contract_time_end) {
            return api_output(1001, [], '合同开始时间不能大于合同结束时间！');
        }

        $houseVillageInfoService = new HouseVillageService();
        $villageInfo = $houseVillageInfoService->getHouseVillageInfoExtend(['village_id' => $village_id], 'contract_time_end,contract_time_start');
        //合同时间，是楼栋的合同时间必须在小区设置的合同时间范围内
        if (!empty($villageInfo)) {

            if ($villageInfo['contract_time_start'] < 1 || $villageInfo['contract_time_end'] < 1) {
                return api_output(1001, [], '请先去小区设置合同开始结束时间！');
            }

            if (strtotime($contract_time_start) < $villageInfo['contract_time_start']) {
                return api_output(1001, [], '楼栋合同开始时间不能小于小区合同开始时间！当前小区的合同开始时间【' . date('Y-m-d', $villageInfo['contract_time_start']) . '】');
            }
            if (strtotime($contract_time_end) > $villageInfo['contract_time_end']) {
                return api_output(1001, [], '楼栋合同开始时间不能大于小区合同结束时间！当前小区的合同结束时间【' . date('Y-m-d', $villageInfo['contract_time_end']) . '】');
            }
        } else {
            return api_output(1001, [], '请先去小区设置合同开始结束时间！');
        }

        $contract_time_start = strtotime($contract_time_start);
        $contract_time_end = strtotime($contract_time_end);

        //查询房间的物业服务时间是否大于设置合同时间
        $room_data = [];
        $room_data['village_id'] = $village_id;
        $room_data['single_id'] = $single_id;
        $room_data['contract_time_start'] = $contract_time_start;
        $room_data['contract_time_end'] = $contract_time_end;

        $checkRoomNowServiceTime = (new HouseVillageUserVacancyService())->checkVacancyServiceTime($room_data);
        if ($checkRoomNowServiceTime['status'] != 1) {
            return api_output(1001, [], $checkRoomNowServiceTime['msg']);
        }

        $villageConfig = (new HouseVillageConfigService())->getConfig(['village_id' => $village_id]);
        $village_single_support_digit = intval($villageConfig['village_single_support_digit']) ? intval($villageConfig['village_single_support_digit']) : 2;
        if (!$village_single_support_digit || $village_single_support_digit != 3) {
            $village_single_support_digit = 2;
        }

        $check_number = "/^[0-9]+$/";
        if (!preg_match($check_number, $single_number)) {
            return api_output(1001, [], '楼栋编号只允许数字！');
        }
        if ((intval($single_number) <= 0 || intval($single_number) > 99) && $village_single_support_digit < 3) {
            return api_output(1001, [], '楼栋编号必须为1-99的数字！');
        }
        if (strlen($single_number) > 2 && $village_single_support_digit < 3) {
            return api_output(1001, [], '楼栋编号只允最多2位数字！');
        } else if (strlen($single_number) < $village_single_support_digit) {
            // 不足2位 补足2位
            $single_number = str_pad($single_number, $village_single_support_digit, "0", STR_PAD_LEFT);
        }

        $where = [];
        $where[] = ['village_id', '=', $village_id];
        $where[] = ['single_number', '=', $single_number];
        $where[] = ['id', '<>', $single_id];
        $where[] = ['status', '<>', 4];
        $checkNameRepeat = $houseVillageSingleService->getSingleInfo($where);
        if ($checkNameRepeat && $checkNameRepeat->id != $single_id) {
            return api_output(1001, [], '该楼栋编号已经存在！');
        }
        $data = array();
        $data['single_number'] = $single_number;
        $data['single_name'] = $single_name;
        $data['status'] = $status;
        $data['sort'] = $sort;
        $data['measure_area'] = $measure_area;
        $data['floor_num'] = $floor_num;
        $data['vacancy_num'] = $vacancy_num;
        $data['upper_layer_num'] = $upper_layer_num;
        $data['lower_layer_num'] = $lower_layer_num;
        $data['single_keeper_name'] = $single_keeper_name;
        $data['single_keeper_phone'] = $single_keeper_phone;
        $data['single_keeper_head'] = $single_keeper_head;
        $data['contract_time_start'] = $contract_time_start;
        $data['contract_time_end'] = $contract_time_end;
        $data['long'] = $long;
        $data['lat'] = $lat;
        $data['is_public_rental'] = 1;
        if ($single_id <= 0) {
            $data['village_id'] = $village_id;
            $idd = $houseVillageSingleService->addSingleInfo($data);
            if ($idd > 0) {
                return api_output(0, ['idd' => $idd], '添加保存成功！');
            } else {
                return api_output(101, [], '添加保存失败！');
            }
        }
        $where = [
            'village_id' => $village_id,
            'id' => $single_id
        ];
        $saveResult = $houseVillageSingleService->saveSingleInfo($where, $data);

        if ($saveResult) {
            //TODO 如果是修改楼栋编号成功需要触发编号其楼栋下房屋编号和对应房屋人员编号变动 ---> 放入队列执行【如果是基于新版之后，其实不用作这层操作】
            //如果需要：cms/Lib/Model/House_village_user_vacancyModel.class.php --> change_number()
            return api_output(0, [], '信息保存成功！');
        } else {
            return api_output(0, [], '信息保存成功！');
        }

    }

    //获取楼栋管家详细页面数据
    public function getUnitRentalButler()
    {
        $village_id = $this->adminUser['village_id'];
        $property_id = $this->adminUser['property_id'];

        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $single_id = $this->request->post('single_id', 0, 'intval');
        $floor_id = $this->request->post('floor_id', 0, 'intval');
        $where = [
            'village_id' => $village_id,
            'single_id' => $single_id,
            'floor_id' => $floor_id,
        ];
        $buldingButler = (new KefuService())->getBuildingButlerInfo($where);
        $buldingButlerBindList = [];
        if ($buldingButler && $buldingButler->work_arr) {
            $work_arr = explode(',', $buldingButler->work_arr);
            if ($work_arr) {

                $where_workers = [
                    'wid' => $work_arr,
                    'status' => 1
                ];
                $buldingButlerBindList = (new HouseWorkerService())->getWorker($where_workers, "wid,name");
            }
        }

        //小区小所有工作人员
        $enterpriseWxCorpid = (new PropertyFrameworkService())->getEnterpriseWxBind(['bind_id' => $property_id, 'bind_type' => 0], 'corpid');
        $enterprise_wx_corpid = '';
        $where = [];
        if ($enterpriseWxCorpid) {
            $where[] = ['village_id', '=', $village_id];
            $where[] = ['qy_status', '=', 1];
            $where[] = ['qy_id', '<>', ''];
            $where[] = ['status', '=', 1];
            $enterprise_wx_corpid = $enterpriseWxCorpid->corpid;
        } else {
            $where = [
                'village_id' => $village_id,
                'status' => 1,
                'is_del' => 0
            ];
        }

        $buldingButlerList = (new HouseWorkerService())->getWorker($where);
        if(empty($buldingButler)){
            $buldingButler=array("housekeeper_id"=>0,"work_arr"=>"0","property_id"=>$property_id,"village_id"=>$village_id,"single_id"=>$single_id,"floor_id"=>$floor_id,"is_kefu"=>0,"welcome_tip"=>"","qy_qrcode"=>"","template_type"=>0,"template_url"=>"","effect_img"=>"","add_time"=>0,"last_time"=>0,"contact_way_json"=>"","config_id"=>"","qr_code"=>"","contact_way_time"=>0,"contact_way_reason"=>"");
        }
        $data = [
            'buldingButler' => $buldingButler,
            'buldingButlerBindList' => $buldingButlerBindList,
            'buldingButlerList' => $buldingButlerList,
            'corpid' => $enterprise_wx_corpid,
            'qyhelp_url' => cfg('site_url') . '/static/file/village/information/qyhelp.pdf'
        ];
        return api_output(0, $data, '查询成功！');
    }


    //添加或者更新楼栋管家
    public function saveUnitRentalButler()
    {
        $village_id = $this->adminUser['village_id'];
        $property_id = $this->adminUser['property_id'];

        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $single_id = $this->request->post('single_id', 0, 'intval');
        $housekeeper_id = $this->request->post('housekeeper_id', 0); //编辑的时候不需要传也是可以
        $is_kefu = $this->request->post('is_kefu', 0);
        $welcome_tip = $this->request->post('welcome_tip', '', 'trim');
        $work_arr = $this->request->post('work_arr', '');
        $qy_qrcode = $this->request->post('qy_qrcode', '');
        $template_type = $this->request->post('template_type', 0, 'intval');
        $template_url = $this->request->post('template_url', 0, 'trim');
        $effect_img = $this->request->post('effect_img', 0, 'trim');
        $floor_id = $this->request->post('floor_id', 0, 'intval');
        if ($this->request->has('housekeeper_id') && $housekeeper_id > 0) { //编辑
            $where = [
                'village_id' => $village_id,
                'housekeeper_id' => $housekeeper_id,

            ];
            $buldingButler = (new KefuService())->getBuildingButlerInfo($where);
            if (empty($buldingButler)) {
                return api_output(1001, [], '非法操作对象！');
            }
        }
        if($single_id<1){
            return api_output(1001, [], '楼栋参数数据ID错误！');
        }
        $data['village_id'] = $village_id;
        $data['property_id'] = $property_id;

        $data['is_kefu'] = $is_kefu;
        $data['work_arr'] = $work_arr ? implode(',', $work_arr) : '';
        $data['welcome_tip'] = $welcome_tip;
        $data['qy_qrcode'] = $qy_qrcode;
        $data['template_type'] = $template_type;
        $data['template_url'] = $template_url;
        $data['effect_img'] = $effect_img;

        if ($this->request->has('housekeeper_id') && $housekeeper_id > 0) {
            $data['last_time'] = time();
            $where = [
                'housekeeper_id' => $buldingButler->housekeeper_id,
            ];
            $result = (new KefuService())->saveBuildingButlerInfo($where, $data);
        } else {
            $data['single_id'] = $single_id;
            $data['floor_id'] = $floor_id;
            $data['add_time'] = time();
            $result = (new KefuService())->addBuildingButlerInfo($data);
        }
        if (!$result) {
            return api_output(101, [], '楼栋管家更改或添加失败！');
        }
        return api_output(0, [], '修改成功！');
    }


    /**
     * 软删除楼栋信息
     * @return \json
     */
    public function deleteUnitRental()
    {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }

        $single_id = $this->request->post('single_id', 0);

        $houseVillageSignService = new HouseVillageSingleService();
        $where = [];
        $where[] = ['village_id', '=', $village_id];
        $where[] = ['single_id', '=', $single_id];
        $where[] = ['status', '<>', 4];
        $checkHasFloor = $houseVillageSignService->getFloorInfo($where, 'floor_id');
        if ($checkHasFloor) {
            return api_output(1001, [], '该楼栋下存在单元不可删除，请先删除单元！');
        }

        $where = [
            'village_id' => $village_id,
            'id' => $single_id,
        ];
        $data = ['status' => 4];
        $houseVillageSignService->softDeleteBuilding($where, $data);

        return api_output(0, [], '删除楼栋成功！');
    }

    /**
     * 软删除单元信息
     * @return \json
     */
    public function deleteUnitRentalFloor()
    {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }

        $single_id = $this->request->post('single_id', 0, 'intval');
        $floor_id = $this->request->post('floor_id', 0, 'intval');
        $houseVillageSignService = new HouseVillageSingleService();
        $where = [];
        $where[] = ['village_id', '=', $village_id];
        $where[] = ['single_id', '=', $single_id];
        $where[] = ['floor_id', '=', $floor_id];
        $where[] = ['status', '<>', 4];
        $checkHasFloor = $houseVillageSignService->getLayerInfo($where, 'id');
        if ($checkHasFloor) {
            return api_output(1003, [], '该单元下存在楼层不可删除，请先删除楼层！');
        }

        $where = [
            'village_id' => $village_id,
            'single_id' => $single_id,
            'floor_id' => $floor_id,
        ];
        $data = ['status' => 4];
        $houseVillageSignService->softDeleteFloor($where, $data);

        return api_output(0, [], '删除单元成功！');
    }

    public function updateUnitRentalStatus()
    {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $single_id = $this->request->post('single_id', 0);
        $status = $this->request->post('status', 0, 'intval');

        $houseVillageSingleService = new HouseVillageSingleService();
        $where = [
            'village_id' => $village_id,
            'id' => $single_id,
        ];
        $checkNameRepeat = $houseVillageSingleService->getSingleInfo($where);
        if (empty($checkNameRepeat)) {
            return api_output(1001, [], '非法操作！');
        }

        $data = [
            'status' => $status
        ];

        $houseVillageSingleService->saveSingleInfo(['id' => $checkNameRepeat->id], $data);
        return api_output(0, [], '修改成功！');
    }

    //获取楼层列表
    public function unitRentalFloorList()
    {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $single_id = $this->request->post('single_id', 0);
        if ($single_id <= 0) {
            return api_output(1001, [], '楼栋ID错误！');
        }
        try {
            $houseVillageSignService = new HouseVillageSingleService();
            $where = [];
            $where[] = ['village_id', '=', $village_id];
            $where[] = ['single_id', '=', $single_id];
            $where[] = ['is_public_rental', '=', 1];
            $where[] = ['status', '<>', 4];
            $count = $houseVillageSignService->getSingleFloorCount($where);
            $field = "*";
            $oderby = 'sort DESC,floor_id desc';
            $floorList = $houseVillageSignService->getSingleFloorList($where, $field, $oderby);
            $data = [
                'count' => $count,
                'dataList' => $floorList,

            ];
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function getQyWxCode()
    {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }

        $params = [
            'qy_qrcode' => '/upload/house/village/20220801/09/62e7264d7705f.jpg',
            'template_type' => 1,
            'village_name' => "哈哈哈哈"
        ];
        $s = $this->traitMakePreviewImgForQyWx($village_id, $params);
        return api_output(0, $s, '1111！');
    }

    public function updateUnitRentalFloorStatus()
    {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $floor_id = $this->request->post('floor_id', 0, 'intval');
        $status = $this->request->post('status', 0, 'intval');
        try {
            $houseVillageSingleService = new HouseVillageSingleService();
            $where = [
                'village_id' => $village_id,
                'floor_id' => $floor_id,
            ];
            $floorInfoObj = $houseVillageSingleService->getFloorInfo($where);
            if (empty($floorInfoObj) || $floorInfoObj->isEmpty()) {
                return api_output(1001, [], '单元不存在,非法操作！');
            }

            $data = [
                'status' => $status
            ];

            $houseVillageSingleService->saveFloorInfo(['floor_id' => $floorInfoObj->floor_id], $data);
            return api_output(0, [], '修改成功！');
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function unitRentalFloorInfo()
    {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $single_id = $this->request->post('single_id', 0, 'intval');
        $floor_id = $this->request->post('floor_id', 0, 'intval');
        if ($floor_id <= 0) {
            $floorInfo = array(
                "floor_id" => 0,
                "village_id" => $village_id,
                "floor_name" => "",
                "status" => 1,
                "floor_type" => 0,
                "property_fee" => "",
                "water_fee" => "",
                "electric_fee" => "",
                "gas_fee" => "",
                "parking_fee" => "",
                "door_control" => "",
                "sort" => 0,
                "long" => "",
                "lat" => "",
                "house_num" => 0,
                "floor_upper_layer_num" => "",
                "floor_lower_layer_num" => "",
                "floor_area" => "",
                "single_id" => $single_id,
                "floor_keeper_name" => "",
                "floor_keeper_phone" => "",
                "floor_keeper_head" => "",
                "start_layer_num" => 1,
                "end_layer_num" => 32,
                "floor_number" => "",
            );
            return api_output(0, $floorInfo);

        }
        try {
            $houseVillageSingleService = new HouseVillageSingleService();
            $where = [
                'village_id' => $village_id,
                'floor_id' => $floor_id,
            ];
            $floorInfoObj = $houseVillageSingleService->getFloorInfo($where);
            if (!empty($floorInfoObj) && !$floorInfoObj->isEmpty()) {
                $floorInfo = $floorInfoObj->toArray();
                return api_output(0, $floorInfo);
            } else {
                return api_output(1001, [], '单元不存在,非法操作！');
            }
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    //保存单元
    public function saveUnitRentalFloorInfo()
    {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $saveArr = array();
        $single_id = $this->request->post('single_id', 0, 'int');
        $floor_id = $this->request->post('floor_id', 0, 'int');
        $floor_name = $this->request->post('floor_name', '', 'trim');
        if (empty($single_id) || $single_id < 1) {
            return api_output(1001, [], '单元所属楼栋ID错误！');
        }

        if (empty($floor_name)) {
            return api_output(1001, [], '单元名称未填写！');
        }
        $saveArr['floor_name'] = $floor_name;
        $floor_number = $this->request->post('floor_number', '', 'trim');
        if (empty($floor_number)) {
            return api_output(1001, [], '单元编号未填写！');
        }
        $villageConfig = (new HouseVillageConfigService())->getConfig(['village_id' => $village_id]);
        $village_single_support_digit = intval($villageConfig['village_single_support_digit']) ? intval($villageConfig['village_single_support_digit']) : 2;
        if (!$village_single_support_digit || $village_single_support_digit != 3) {
            $village_single_support_digit = 2;
        }

        $check_number = "/^[0-9]+$/";
        if (!preg_match($check_number, $floor_number)) {
            return api_output(1001, [], '单元编号只允许数字！');
        }
        if ((intval($floor_number) <= 0 || intval($floor_number) > 99) && $village_single_support_digit < 3) {
            return api_output(1001, [], '单元编号必须为1-99的数字！');
        }
        if (strlen($floor_number) > 2 && $village_single_support_digit < 3) {
            return api_output(1001, [], '单元编号只允最多2位数字！');
        } else if (strlen($floor_number) < $village_single_support_digit) {
            // 不足2位 补足2位
            $floor_number = str_pad($floor_number, $village_single_support_digit, "0", STR_PAD_LEFT);
        }
        $saveArr['floor_number'] = $floor_number;
        $lat = $this->request->post('lat', 0);
        $saveArr['lat'] = $lat;
        $long = $this->request->post('long', 0);
        $saveArr['long'] = $long;
        if (empty($lat) || empty($long)) {
            return api_output(1001, [], '未选择单元地址！');
        }
        $floor_keeper_name = $this->request->post('floor_keeper_name', '', 'trim');
        $saveArr['floor_keeper_name'] = $floor_keeper_name;
        $floor_keeper_phone = $this->request->post('floor_keeper_phone', '', 'trim');
        $saveArr['floor_keeper_phone'] = $floor_keeper_phone;
        $floor_keeper_head = $this->request->post('floor_keeper_head', '', 'trim');
        $saveArr['floor_keeper_head'] = $floor_keeper_head;
        $sort = $this->request->post('sort', 0, 'int');
        $saveArr['sort'] = $sort;
        $status = $this->request->post('status', 0, 'int');
        $saveArr['status'] = $status;

        $property_fee = $this->request->post('property_fee', '', 'trim');
        $saveArr['property_fee'] = $property_fee;
        $water_fee = $this->request->post('water_fee', '', 'trim');
        $saveArr['water_fee'] = $water_fee;
        $electric_fee = $this->request->post('electric_fee', '', 'trim');
        $saveArr['electric_fee'] = $electric_fee;
        $gas_fee = $this->request->post('gas_fee', '', 'trim');
        $saveArr['gas_fee'] = $gas_fee;
        $parking_fee = $this->request->post('parking_fee', '', 'trim');
        $saveArr['parking_fee'] = $parking_fee;
        $floor_area = $this->request->post('floor_area', '', 'trim');
        $saveArr['floor_area'] = $floor_area;
        $floor_upper_layer_num = $this->request->post('floor_upper_layer_num', '', 'trim');
        $saveArr['floor_upper_layer_num'] = $floor_upper_layer_num;
        $house_num = $this->request->post('house_num', '', 'trim');
        $saveArr['house_num'] = $house_num;
        $floor_lower_layer_num = $this->request->post('floor_lower_layer_num', '', 'trim');
        $saveArr['floor_lower_layer_num'] = $floor_lower_layer_num;
        $start_layer_num = $this->request->post('start_layer_num', 0, 'intval');
        $saveArr['start_layer_num'] = $start_layer_num;
        $end_layer_num = $this->request->post('end_layer_num', 0, 'intval');
        $saveArr['end_layer_num'] = $end_layer_num;
        $saveArr['is_public_rental'] = 1;
        try {
            $where = [];
            $where[] = ['village_id', '=', $village_id];
            $where[] = ['floor_name', '=', $floor_name];
            $where[] = ['floor_id', '<>', $floor_id];
            $where[] = ['single_id', '=', $single_id];
            $where[] = ['status', '<>', 4];
            $houseVillageSingleService = new HouseVillageSingleService();
            $checkRepeat = $houseVillageSingleService->getFloorInfo($where, 'floor_id');
            if ($checkRepeat && !$checkRepeat->isEmpty()) {
                return api_output(1001, [], '该单元名称已经存在！');
            }

            $where = [];
            $where[] = ['village_id', '=', $village_id];
            $where[] = ['floor_number', '=', $floor_number];
            $where[] = ['floor_id', '<>', $floor_id];
            $where[] = ['single_id', '=', $single_id];
            $where[] = ['status', '<>', 4];
            $houseVillageSingleService = new HouseVillageSingleService();
            $checkRepeat = $houseVillageSingleService->getFloorInfo($where, 'floor_id');
            if ($checkRepeat && !$checkRepeat->isEmpty()) {
                return api_output(1001, [], '该单元编号已经存在！');
            }

            if ($floor_id > 0) {
                //编辑
                $whereArr = [];
                $whereArr[] = ['village_id', '=', $village_id];
                $whereArr[] = ['floor_id', '=', $floor_id];
                $saveArr['update_time'] = time();
                $houseVillageSingleService->saveFloorInfo($whereArr, $saveArr);
                $saveArr['village_id'] = $village_id;
                $saveArr['single_id'] = $single_id;
                $saveArr['floor_id'] = $floor_id;
                return api_output(0, $saveArr, '编辑修改成功！');
            } else {
                //添加
                $saveArr['village_id'] = $village_id;
                $saveArr['single_id'] = $single_id;
                $saveArr['add_time'] = time();
                $idd = $houseVillageSingleService->addFloorInfo($saveArr);
                $saveArr['floor_id'] = $idd;
                return api_output(0, $saveArr, '添加成功！');
            }
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

    }

    //获取楼层列表
    public function unitRentalLayerList()
    {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $single_id = $this->request->post('single_id', 0, 'int');
        if ($single_id <= 0) {
            return api_output(1001, [], '楼栋ID错误！');
        }
        $floor_id = $this->request->post('floor_id', 0, 'int');
        if ($floor_id <= 0) {
            return api_output(1001, [], '单元ID错误！');
        }
        try {
            $houseVillageSignService = new HouseVillageSingleService();
            $where = [];
            $where[] = ['village_id', '=', $village_id];
            $where[] = ['single_id', '=', $single_id];
            $where[] = ['floor_id', '=', $floor_id];
            $where[] = ['is_public_rental', '=', 1];
            $where[] = ['status', '<>', 4];
            $count = $houseVillageSignService->getSingleLayerCount($where);
            $field = "*";
            $oderby = 'sort DESC,id desc';
            $floorList = $houseVillageSignService->getSingleLayerList($where, $field, $oderby);
            $data = [
                'count' => $count,
                'dataList' => $floorList,

            ];
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function unitRentalLayerInfo()
    {

        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $single_id = $this->request->post('single_id', 0, 'intval');
        $floor_id = $this->request->post('floor_id', 0, 'intval');
        $layer_id = $this->request->post('layer_id', 0, 'intval');
        if ($layer_id <= 0) {
            $layerInfo = array(
                "id" => 0,
                "floor_id" => $floor_id,
                "village_id" => $village_id,
                "layer_name" => "",
                "status" => 1,
                "sort" => 0,
                "single_id" => $single_id,
                "layer_number" => "",
            );
            return api_output(0, $layerInfo);
        }
        try {
            $houseVillageSingleService = new HouseVillageSingleService();
            $where = [
                'village_id' => $village_id,
                'floor_id' => $floor_id,
                'id' => $layer_id,
            ];
            $layerInfoObj = $houseVillageSingleService->getLayerInfo($where);
            if (!empty($layerInfoObj) && !$layerInfoObj->isEmpty()) {
                $layerInfo = $layerInfoObj->toArray();
                return api_output(0, $layerInfo);
            } else {
                return api_output(1001, [], '楼层数据不存在,非法操作！');
            }
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    //保存楼层
    public function saveUnitRentalLayerInfo()
    {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $saveArr = array();
        $single_id = $this->request->post('single_id', 0, 'int');
        $floor_id = $this->request->post('floor_id', 0, 'int');
        $layer_id = $this->request->post('id', 0, 'int');
        $layer_name = $this->request->post('layer_name', '', 'trim');
        $saveArr['layer_name'] = $layer_name;
        $layer_number = $this->request->post('layer_number', '', 'trim');
        $saveArr['layer_number'] = $layer_number;
        $sort = $this->request->post('sort', 0, 'intval');
        $saveArr['sort'] = $sort;
        $status = $this->request->post('status', 0, 'intval');
        $saveArr['status'] = $status;
        if (empty($single_id) || $single_id < 1) {
            return api_output(1001, [], '楼层所属楼栋ID错误！');
        }
        if ($floor_id <= 0) {
            return api_output(1001, [], '楼层所属的单元ID错误！');
        }
        $saveArr['is_public_rental'] = 1;
        try {
            $villageConfig = (new HouseVillageConfigService())->getConfig(['village_id' => $village_id]);
            $village_single_support_digit = intval($villageConfig['village_single_support_digit']) ? intval($villageConfig['village_single_support_digit']) : 2;
            if (!$village_single_support_digit || $village_single_support_digit != 3) {
                $village_single_support_digit = 2;
            }

            $check_number = "/^[0-9]+$/";
            if (!preg_match($check_number, $layer_number)) {
                return api_output(1001, [], '楼层编号只允许数字！');
            }
            if ((intval($layer_number) <= 0 || intval($layer_number) > 99) && $village_single_support_digit < 3) {
                return api_output(1001, [], '楼层编号必须为1-99的数字！');
            }
            if (strlen($layer_number) > 2 && $village_single_support_digit < 3) {
                return api_output(1001, [], '楼层编号只允最多2位数字！');
            } else if (strlen($layer_number) < $village_single_support_digit) {
                // 不足2位 补足2位
                $layer_number = str_pad($layer_number, $village_single_support_digit, "0", STR_PAD_LEFT);
            }
            $saveArr['layer_number'] = $layer_number;
            $saveArr['add_time'] = time();
            $houseVillageSingleService = new HouseVillageSingleService();
            $where_repeat = [];
            $where_repeat[] = ['village_id', '=', $village_id];
            $where_repeat[] = ['single_id', '=', $single_id];
            $where_repeat[] = ['floor_id', '=', $floor_id];
            $where_repeat[] = ['layer_name', '=', $layer_name];
            $where_repeat[] = ['status', '<>', 4];
            $where_repeat[] = ['id', '<>', $layer_id];
            $layerInfoObj = $houseVillageSingleService->getLayerInfo($where_repeat, 'id');
            if ($layerInfoObj && !$layerInfoObj->isEmpty()) {
                return api_output(1001, [], '该楼层名称已经存在！');
            }

            $where_repeat = [];
            $where_repeat[] = ['village_id', '=', $village_id];
            $where_repeat[] = ['single_id', '=', $single_id];
            $where_repeat[] = ['layer_number', '=', $layer_number];
            $where_repeat[] = ['floor_id', '=', $floor_id];
            $where_repeat[] = ['id', '<>', $layer_id];
            $where_repeat[] = ['status', '<>', 4];
            $houseVillageSingleService = new HouseVillageSingleService();
            $checkRepeat = $houseVillageSingleService->getLayerInfo($where_repeat, 'id');
            if ($checkRepeat && !$checkRepeat->isEmpty()) {
                return api_output(1001, [], '该单元编号已经存在！');
            }

            if ($layer_id > 0) {
                //编辑
                $whereArr = [];
                $whereArr[] = ['village_id', '=', $village_id];
                $whereArr[] = ['floor_id', '=', $floor_id];
                $whereArr[] = ['id', '=', $layer_id];
                $houseVillageSingleService->saveLayerInfo($whereArr, $saveArr);
                $saveArr['village_id'] = $village_id;
                $saveArr['single_id'] = $single_id;
                $saveArr['floor_id'] = $floor_id;
                $saveArr['id'] = $layer_id;
                return api_output(0, $saveArr, '编辑修改成功！');
            } else {
                //添加
                $saveArr['village_id'] = $village_id;
                $saveArr['single_id'] = $single_id;
                $saveArr['floor_id'] = $floor_id;
                $idd = $houseVillageSingleService->addLayerInfo($saveArr);
                $saveArr['id'] = $idd;
                return api_output(0, $saveArr, '添加成功！');
            }
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function updateUnitRentalLayerStatus(){
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $layer_id = $this->request->post('layer_id', 0, 'intval');
        $floor_id = $this->request->post('floor_id', 0, 'intval');
        $status = $this->request->post('status', 0, 'intval');
        try {
            $houseVillageSingleService = new HouseVillageSingleService();
            $where = [
                'village_id' => $village_id,
                'floor_id' => $floor_id,
                'id' => $layer_id,
            ];
            $layerInfoObj = $houseVillageSingleService->getLayerInfo($where,'id');
            if (empty($layerInfoObj) || $layerInfoObj->isEmpty()) {
                return api_output(1001, [], '楼层不存在,非法操作！');
            }

            $data = [
                'status' => $status
            ];

            $houseVillageSingleService->saveLayerInfo(['id' => $layerInfoObj->id], $data);
            return api_output(0, [], '修改成功！');
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function deleteUnitRentalLayer(){
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }

        $floor_id = $this->request->post('floor_id', 0, 'intval');
        $layer_id = $this->request->post('layer_id', 0, 'intval');
        $houseVillageSignService = new HouseVillageSingleService();
        $where = [];
        $where[] = ['layer_id', '=', $layer_id];
        $where[] = ['village_id', '=', $village_id];
        $where[] = ['floor_id', '=', $floor_id];
        $where[] = ['is_del', '=', 0];
        $checkHasRoom = $houseVillageSignService->getOneRoom($where, 'pigcms_id');
        if ($checkHasRoom) {
            return api_output(1003, [], '该楼层下存在房间不可删除，请先删除房间！');
        }
        $where = [
            'id' => $layer_id,
            'village_id' => $village_id,
            'floor_id' => $floor_id
        ];
        $data = ['status' => 4];
        $houseVillageSignService->softDeleteLayer($where, $data);

        return api_output(0, [], '删除楼层成功！');

    }
}