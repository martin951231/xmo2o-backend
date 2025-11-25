<?php


namespace app\community\controller\village_api;


use app\common\model\service\config\ConfigCustomizationService;
use app\common\model\service\export\ExportService;
use app\community\model\service\ExpressPickupService;
use app\community\controller\CommunityBaseController;
use app\community\model\db\HouseVillage;
use app\community\model\service\HouseNewParkingService;
use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use think\facade\Cache;
use think\facade\Db;

class ExpressPickUpController extends CommunityBaseController
{
    /**
     * 代取地址列表
     * @return \json
     * @author mashuaifei
     * * @date_time 2025/09/17
     */
    public function getExpressPickupLocationList()
    {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
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
     * 代取申请列表
     * @return \json
     * @author mashuaifei
     * * @date_time 2025/09/17
     */
    public function getExpressApplicationList()
    {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $location_id = $this->request->post('location_id');
        $single_id = $this->request->post('single_id');
        $floor_id = $this->request->post('floor_id');
        $layer_id = $this->request->post('layer_id');
        $room_id = $this->request->post('room_id');
        $user_id = $this->request->post('user_id');
        $recipient_name = $this->request->post('recipient_name');
        $recipient_phone = $this->request->post('recipient_phone');
        $status = $this->request->post('status');
        $track_num = $this->request->post('track_num');
        $pickup_code = $this->request->post('pickup_code');
        $page = $this->request->post('page', 1);
        $limit = $this->request->post('limit', 10);
        $service_express_pick_up = new ExpressPickupService();
        $where[] = ['p.village_id', '=', $village_id];
        //        $where[] = ['p.status','=',1];
        ($status || $status === 0) && $where[] = ['p.status', '=', $status];
        $location_id && $where[] = ['p.location_id', '=', $location_id];
        $single_id && $where[] = ['p.single_id', '=', $single_id];
        $floor_id && $where[] = ['p.floor_id', '=', $floor_id];
        $layer_id && $where[] = ['p.layer_id', '=', $layer_id];
        $room_id && $where[] = ['p.room_id', '=', $room_id];
        $user_id && $where[] = ['p.user_id', '=', $user_id];
        $recipient_name && $where[] = ['p.recipient_name', 'like', '%' . $recipient_name . '%'];
        $recipient_phone && $where[] = ['p.recipient_phone', 'like', '%' . $recipient_phone . '%'];
        $track_num && $where[] = ['p.track_num', 'like', '%' . $track_num . '%'];
        $pickup_code && $where[] = ['p.pickup_code', 'like', '%' . $pickup_code . '%'];
        $field = 'p.*,e.location';

        try {
            $data = $service_express_pick_up->getExpressApplicationList($where, $field, $page, $limit, 'p.id DESC')->toArray();

            $count = $service_express_pick_up->getExpressApplicationCount($where, $field);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        $res = array();
        $res['total_limit'] = $limit;
        $res['list'] = $data;
        $res['count'] = $count;
        return api_output(0, $res);
    }

    /**
     * 导出列表
     * @return \json
     * @author mashuaifei
     * @date_time 2025/11/03
     */
    public function exportApplicationList()
    {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $village_name = $this->adminUser['village_name'];
        $location_id = $this->request->post('location_id');
        $location_ids = $this->request->post('selectedRowKeys');
        $single_id = $this->request->post('single_id');
        $floor_id = $this->request->post('floor_id');
        $layer_id = $this->request->post('layer_id');
        $room_id = $this->request->post('room_id');
        $pickup_code = $this->request->post('pickup_code');
        $recipient_name = $this->request->post('recipient_name');
        $recipient_phone = $this->request->post('recipient_phone');
        $status = $this->request->post('status');
        $track_num = $this->request->post('track_num');
        $service_express_pick_up = new ExpressPickupService();

        $where[] = ['p.village_id', '=', $village_id];
        ($status || $status === 0) && $where[] = ['p.status', '=', $status];
        $location_id && $where[] = ['p.location_id', '=', $location_id];
        $single_id && $where[] = ['p.single_id', '=', $single_id];
        $floor_id && $where[] = ['p.floor_id', '=', $floor_id];
        $layer_id && $where[] = ['p.layer_id', '=', $layer_id];
        $room_id && $where[] = ['p.room_id', '=', $room_id];
        $recipient_name && $where[] = ['p.recipient_name', 'like', '%' . $recipient_name . '%'];
        $recipient_phone && $where[] = ['p.recipient_phone', 'like', '%' . $recipient_phone . '%'];
        $track_num && $where[] = ['p.track_num', 'like', '%' . $track_num . '%'];
        $pickup_code && $where[] = ['p.pickup_code', 'like', '%' . $pickup_code . '%'];
        $location_ids && $where[] = ['p.id', 'in', $location_ids];
        $field = 'p.*,e.location';
        $data = $service_express_pick_up->getExpressApplicationList($where, $field, 1, 999999999, 'p.id DESC')->toArray();
        $filePath = root_path() . '../static/file/village/export_village_list.xlsx';
        try {
            Calculation::getInstance()->setCalculationCacheEnabled(false);
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $startRow = 2; // 数据起始行
            $currentRow = $startRow;
            $rooms = [];
            $firstRow = $data[0]; // 取第一条数据用于填充头部信息（避免变量未定义）
            // 状态映射关系
            $statusMap = [
                0 => '作废',
                1 => '待取',
                2 => '已取',
                99 => '已删除'
            ];
            // 填充数据列表
            foreach ($data as $value) {
                // 收集房间号（去重）
                if (!empty($value['room_id'])) {
                    $rooms[$value['room_id']] = $value['room_id'];
                }

                $statusText = $statusMap[$value['status'] ?? ''] ?? $value['status'];

                // 按列填充数据（使用字母数组更清晰）
                $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];
                $values = [
                    $village_name,
                    $value['room_name'] ?? '',
                    $value['track_num'] ?? '',
                    $value['pickup_code'] ?? '',
                    $value['location'] ?? '',
                    $value['recipient_name'] ?? '',
                    $value['recipient_phone'] ?? '',
                    $value['create_time'] ?? '',
                    $statusText,
                    $value['remark'] ?? '',
                    $value['reason'] ?? '',
                ];

                // 循环赋值，避免重复代码
                foreach ($columns as $index => $col) {
                    $worksheet->setCellValue($col . $currentRow, $values[$index]);
                }

                $currentRow++;
            }

            // 填充头部信息（使用isset避免未定义变量错误）
            $worksheet->setCellValue('A1', '小区名称');
            $worksheet->setCellValue('B1', '房间号');
            $worksheet->setCellValue('C1', '快递单号');
            $worksheet->setCellValue('D1', '取件码');
            $worksheet->setCellValue('E1', '取件地址');
            $worksheet->setCellValue('F1', '收件人');
            $worksheet->setCellValue('G1', '收件人电话');
            $worksheet->setCellValue('H1', '创建日期');
            $worksheet->setCellValue('I1', '取件状态');
            $worksheet->setCellValue('J1', '备注');
            $worksheet->setCellValue('K1', '代取结果描述');

            // 导出文件
            $filename = 'export_village_list-' . date('YmdHis') . '.xlsx';
            $exportService = new ExportService();
            $exportService->phpSpreadsheet($filename, $spreadsheet);

            // 获取下载链接
            $arr = (new HouseNewParkingService())->downloadExportFile($filename);
            return api_output(0, $arr, '导出成功');
        } catch (Exception $e) {
            // 捕获异常并返回错误信息
            return api_output(1, [], '导出失败：' . $e->getMessage());
        }
    }

}
