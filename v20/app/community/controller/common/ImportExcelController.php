<?php

/**
 * Author lkz
 * Date: 2023/1/10
 * Time: 11:54
 */

namespace app\community\controller\common;

use app\community\controller\CommunityBaseController;
use app\community\model\service\ImportExcelService;
use app\community\model\db\User;
use app\community\model\db\ExpressPickUpLocation;
use app\community\model\db\CommonPickupPerson;
use app\community\model\db\HouseVillageSingle;
use app\community\model\db\HouseVillageFloor;
use app\community\model\db\HouseVillageLayer;
use app\community\model\db\HouseVillageUserVacancy;
use think\Model;
use think\facade\Db;
use think\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use app\common\model\service\export\ExportService;
use app\community\model\service\HouseNewParkingService;

class ImportExcelController extends CommunityBaseController
{
    //上传文件
    public function villageUploadFile()
    {
        $village_id = $this->adminUser['village_id'];
        $file = $this->request->file('file');
        $type = $this->request->param('type', '', 'trim');
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        if (empty($file)) {
            return api_output(1001, [], '请上传文件');
        }
        if (empty($type)) {
            return api_output(1001, [], '缺少必要参数');
        }
        try {
            $param = array(
                'village_id' => $village_id,
                'file' => $file,
            );
            $result = (new ImportExcelService())->uploadFile($type, $param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }

    //文件解析
    public function importForExcel()
    {
        $village_id = $this->adminUser['village_id'];
        $excelPath = $this->request->param('inputFileName', '', 'trim');
        $type = $this->request->param('type', '', 'trim');
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        if (empty($excelPath)) {
            return api_output(1001, [], '请上传文件');
        }
        if (empty($type)) {
            return api_output(1001, [], '缺少必要参数');
        }
        try {
            $param = array(
                'village_id' => $village_id,
                'inputFileName' => app()->getRootPath() . '..' . $excelPath,
            );
            $result = (new ImportExcelService())->readImportFile($type, $param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }

    //返回当前选择 sheet 和 系统支持的字段和 Excel 的列和表头
    public function startBindExcelCol()
    {
        $village_id = $this->adminUser['village_id'];
        $excelPath = $this->request->param('inputFileName', '', 'trim');
        $type = $this->request->param('type', '', 'trim');
        $worksheetName = $this->request->post('worksheetName', '');
        $selectWorkSheetIndex = $this->request->post('selectWorkSheetIndex', 0);
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        if (empty($excelPath)) {
            return api_output(1001, [], '请上传文件');
        }
        if (empty($type)) {
            return api_output(1001, [], '缺少必要参数');
        }
        try {
            $param = array(
                'village_id' => $village_id,
                'inputFileName' => app()->getRootPath() . '..' . $excelPath,
                'worksheetName' => $worksheetName,
                'selectWorkSheetIndex' => $selectWorkSheetIndex
            );
            $result = (new ImportExcelService())->analysisImportFile($type, $param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }

    //准备导入
    public function startImport()
    {
        $village_id = $this->adminUser['village_id'];
        $type = $this->request->param('type', '', 'trim');
        $excelBinFieldRelationShip = $this->request->post('relationship', '');
        $inputFileName          = $this->request->post('inputFileName', '');
        $worksheetName          = $this->request->post('worksheetName', '');
        $find_type             = $this->request->post('find_type', '', 'trim'); //数据重复时
        $find_value           = $this->request->post('find_value', '', 'trim'); //当{$find_value}重复时覆盖现有楼栋数据
        $selectWorkSheetIndex   = $this->request->post('selectWorkSheetIndex', 0);
        $rule_id          = $this->request->post('rule_id', 0, 'intval');
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        if (empty($type)) {
            return api_output(1001, [], '缺少必要参数');
        }
        $nowVillageInfo    = [
            'village_id'        => $this->adminUser['village_id'],
            'village_name'      => $this->adminUser['village_name'],
            'village_address'   => $this->adminUser['village_address'],
            'account'           => $this->adminUser['account'],
            'property_id'       => $this->adminUser['property_id'],
            'property_phone'    => $this->adminUser['property_phone'],
            'adminName'         => $this->adminUser['adminName'],
            'user_name'         => $this->adminUser['user_name'],
            'adminId'           => $this->adminUser['adminId'],
            'loginType'         => $this->adminUser['loginType'],
        ];
        try {
            $param = array(
                'excelBinFieldRelationShip' => $excelBinFieldRelationShip,
                'selectWorkSheetIndex'      => $selectWorkSheetIndex,
                'inputFileName'             => $inputFileName,
                'worksheetName'             => $worksheetName,
                'find_type'                 => $find_type,
                'find_value'                => $find_value,
                'nowVillageInfo'            => $nowVillageInfo,
                'rule_id'                   => $rule_id,
            );
            $result = (new ImportExcelService())->triggerImportFile($type, $param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }

    public function importApplicationList()
    {
        $village_id = $this->adminUser['village_id'];
        $user_id = $this->adminUser;
        $data = $this->request->post('allData', []);
        if( count($data) == 0 ){
            return api_output(1001, [], '请上传正确文件');
        }
        $invalidData = $this->request->post('invalidData', []);
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $params = array(
            'village_id' => $village_id,
        );
        $invalid_data = array();
        $insert_data_list = array();
        $insert_data = array();
        foreach ($data as $key => $value) {
            $insert_data['village_id'] = $village_id;
            $insert_data['create_time'] = date('Y-m-d H:i:s');
            $insert_data['track_num'] = $value['track_num'];
            $insert_data['pickup_code'] = $value['pickup_code'];
            $insert_data['location'] = $value['location'];
            $location_info = (new ExpressPickUpLocation())->getOne(['location' => $value['location'], 'village_id' => $village_id], 'id');
            if (empty($location_info)) {
                $value['failReason'] = '代取点不存在';
                $invalid_data[] = $value;
                continue;
            }
            $insert_data['location_id'] = $location_info['id'];
            $insert_data['room_name'] = $value['single_name'] . '-' . $value['floor_name'] . '-' . $value['layer_name'] . '-' . $value['room_name'];
            $insert_data['recipient_name'] = $value['recipient_name'];
            $insert_data['recipient_phone'] = $value['recipient_phone'];
            $insert_data['reason'] = $value['reason'];
            $insert_data['remark'] = $value['remark'];
            switch ($value['status_name']) {
                case '已取回':
                    $insert_data['status'] = 2;
                    break;
                case '待取件':
                    $insert_data['status'] = 1;
                    break;
                case '已作废':
                    $insert_data['status'] = 0;
                    break;
                default:
            }
            //楼栋
            $where = [
                'village_id' => $village_id,
                'single_name' => $value['single_name'],
                'status' => 1
            ];
            $single_info = (new HouseVillageSingle())->getOne($where, 'id');
            if (empty($single_info)) {
                $value['failReason'] = '楼栋不存在';
                $invalid_data[] = $value;
                continue;
            }
            $single_id = $single_info['id'];
            $insert_data['single_id'] = $single_info['id'];
            //单元
            $where = [
                'village_id' => $village_id,
                'single_id' => $single_id,
                'floor_name' => $value['floor_name'],
                'status' => 1
            ];
            $floor_info = (new HouseVillageFloor())->getOne($where, 'floor_id');
            if (empty($floor_info)) {
                $value['failReason'] = '单元不存在';
                $invalid_data[] = $value;
                continue;
            }
            $floor_id = $floor_info['floor_id'];
            $insert_data['floor_id'] = $floor_info['floor_id'];
            //楼层
            $where = [
                'village_id' => $village_id,
                'single_id' => $single_id,
                'floor_id' => $floor_id,
                'layer_name' => $value['layer_name'],
                'status' => 1
            ];
            $layer_info = (new HouseVillageLayer())->getOne($where, 'id');
            if (empty($layer_info)) {
                $value['failReason'] = '楼层不存在';
                $invalid_data[] = $value;
                continue;
            }
            $layer_id = $layer_info['id'];
            $insert_data['layer_id'] = $layer_info['id'];
            //房间
            $where = [
                'village_id' => $village_id,
                'single_id' => $single_id,
                'floor_id' => $floor_id,
                'layer_id' => $layer_id,
                'room' => $value['room_name'],
                // 'status' => 3
            ];
            $room_info = (new HouseVillageUserVacancy())->getOne($where, 'pigcms_id,uid');
            if (empty($room_info)) {
                $value['failReason'] = '房间不存在';
                $invalid_data[] = $value;
                continue;
            }
            $insert_data['room_id'] = $room_info['pigcms_id'];
            $insert_data['user_id'] = $room_info ? $room_info['uid'] : 0;
            $insert_data_list[] = $insert_data;
        }
        $dbResult = $this->batchInsertLargeData('express_application', $insert_data_list, 1000);
        if ($dbResult['code'] == 0) {
            $result = [
                'code' => 0,
                'msg' => '导入成功',
                'data' => $dbResult['success'],
                'invalidData' => array_merge($invalid_data, $dbResult['failData'])
            ];
        } else {
            $result = [
                'code' => 0,
                'msg' => '导入成功',
                'data' => $dbResult['success'],
                'invalidData' => array_merge($invalid_data, $dbResult['failData'])
            ];
        }

        if (!empty($invalid_data)) {
            try {
            // 禁用计算缓存，提升性能
            Calculation::getInstance()->setCalculationCacheEnabled(false);
            $filePath = root_path() . '../static/file/village/import_village_fail_list.xlsx';
            // 1. 创建全新的Excel对象（不再加载本地文件）
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $worksheet->setTitle('小区快递列表'); // 设置工作表名称

            // 2. 填充表头信息（直接定义，无需依赖模板）
            $headers = [
                'A1' => '小区名称',
                'B1' => '楼栋名',
                'C1' => '单元名',
                'D1' => '楼层名',
                'E1' => '房间号',
                'F1' => '取件人',
                'G1' => '快递单号',
                'H1' => '取件码',
                'I1' => '取件地址',
                'J1' => '收件人',
                'K1' => '收件电话',
                'L1' => '业主备注',
                'M1' => '代取结果描述',
                'M1' => '取件状态',
                'O1' => '失败原因',
            ];
            foreach ($headers as $cell => $value) {
                $worksheet->setCellValue($cell, $value);
            }

            // 3. 数据起始行（表头占第1行，数据从第2行开始）
            $startRow = 2;
            $currentRow = $startRow;
            $rooms = []; // 用于收集房间号（保持原有逻辑）
            $statusMap = [0 => '作废', 1 => '待取', 2 => '已取', 99 => '已删除']; // 状态映射

            // 4. 填充数据列表
            foreach ($invalid_data as $value) {
                // 收集房间号（去重，保持原有逻辑）
                if (!empty($value['room_id'])) {
                    $rooms[$value['room_id']] = $value['room_id'];
                }

                // 按列定义数据（与表头顺序对应）
                $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K','L','M','N','O'];
                $values = [
                    $value['village_name'] ?? '', // 小区名称（从数据中取，原代码中$village_name未定义，此处修正）
                    $value['single_name'] ?? '',
                    $value['floor_name'] ?? '',
                    $value['layer_name'] ?? '',
                    $value['room_name'] ?? '',
                    '',
                    $value['track_num'] ?? '',
                    $value['pickup_code'] ?? '',
                    $value['location'] ?? '',
                    $value['recipient_name'] ?? '',
                    $value['recipient_phone'] ?? '',
                    $value['remark'] ?? '',
                    $value['reason'] ?? '',
                    $value['status_name'] ?? '',
                    $value['failReason'] ?? '',
                ];

                // 循环填充当前行数据
                foreach ($columns as $index => $col) {
                    $worksheet->setCellValue($col . $currentRow, $values[$index]);
                }

                $currentRow++; // 行数自增
            }

            // 5. 生成导出文件名
            $filename = 'export_village_list-' . date('YmdHis') . '.xlsx';

            // 6. 调用导出服务（保持原有导出逻辑）
            $exportService = new ExportService();
            $exportService->phpSpreadsheet($filename, $spreadsheet);

            // 7. 获取下载链接（保持原有逻辑）
            $arr = (new HouseNewParkingService())->downloadExportFile($filename);
            return api_output(0, $arr, '导出成功');
        } catch (Exception $e) {
            return $e->getMessage();
        }
        }else{
            return api_output(0, '', '导入成功');
        }
    }

    public function get_excel_download_url($data, $table)
    {
        if( count( $data ) == 0 ){
            return '';
        }
        try {
            // 禁用计算缓存，提升性能
            Calculation::getInstance()->setCalculationCacheEnabled(false);
            $filePath = root_path() . '../static/file/village/import_village_fail_list.xlsx';
            // 1. 创建全新的Excel对象（不再加载本地文件）
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $worksheet->setTitle('小区快递列表'); // 设置工作表名称

            // 2. 填充表头信息（直接定义，无需依赖模板）
            $headers = [
                'A1' => '小区名称',
                'B1' => '楼栋名',
                'C1' => '单元名',
                'D1' => '楼层名',
                'E1' => '房间号',
                'F1' => '取件人',
                'G1' => '快递单号',
                'H1' => '取件码',
                'I1' => '取件地址',
                'J1' => '收件人',
                'K1' => '收件电话',
                'L1' => '业主备注',
                'M1' => '代取结果描述',
                'M1' => '取件状态',
                'O1' => '失败原因',
            ];
            foreach ($headers as $cell => $value) {
                $worksheet->setCellValue($cell, $value);
            }

            // 3. 数据起始行（表头占第1行，数据从第2行开始）
            $startRow = 2;
            $currentRow = $startRow;
            $rooms = []; // 用于收集房间号（保持原有逻辑）
            $statusMap = [0 => '作废', 1 => '待取', 2 => '已取', 99 => '已删除']; // 状态映射

            // 4. 填充数据列表
            foreach ($data as $value) {
                // 收集房间号（去重，保持原有逻辑）
                if (!empty($value['room_id'])) {
                    $rooms[$value['room_id']] = $value['room_id'];
                }

                // 按列定义数据（与表头顺序对应）
                $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K','L','M','N','O'];
                $values = [
                    $value['village_name'] ?? '', // 小区名称（从数据中取，原代码中$village_name未定义，此处修正）
                    $value['single_name'] ?? '',
                    $value['floor_name'] ?? '',
                    $value['layer_name'] ?? '',
                    $value['room_name'] ?? '',
                    '',
                    $value['track_num'] ?? '',
                    $value['pickup_code'] ?? '',
                    $value['location'] ?? '',
                    $value['recipient_name'] ?? '',
                    $value['recipient_phone'] ?? '',
                    $value['remark'] ?? '',
                    $value['reason'] ?? '',
                    $value['status_name'] ?? '',
                    $value['failReason'] ?? '',
                ];

                // 循环填充当前行数据
                foreach ($columns as $index => $col) {
                    $worksheet->setCellValue($col . $currentRow, $values[$index]);
                }

                $currentRow++; // 行数自增
            }

            // 5. 生成导出文件名
            $filename = 'export_village_list-' . date('YmdHis') . '.xlsx';

            // 6. 调用导出服务（保持原有导出逻辑）
            $exportService = new ExportService();
            $exportService->phpSpreadsheet($filename, $spreadsheet);

            // 7. 获取下载链接（保持原有逻辑）
            $arr = (new HouseNewParkingService())->downloadExportFile($filename);
            return api_output(0, $arr, '导出成功');
            return $arr;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * 大数据量批量插入数据库（1万+数据适配）
     * @param string $table 目标数据库表名
     * @param array $data 待插入数据（键值对数组，键=表字段）
     * @param int $batchSize 每批次条数（默认1000，1万条≈10批）
     * @return array 详细插入结果
     */
    public function batchInsertLargeData($table, $data, $batchSize = 1000)
    {
        if (empty($data) || !is_array($data)) {
            return [
                'code' => 0,
                'success' => 0,
                'fail' => 0,
                'failData' => [], // 存储失败的具体数据（含失败原因）
                'msg' => '待插入数据为空或格式错误'
            ];
        }

        $total = count($data);
        $successCount = 0;
        $failCount = 0;
        $failData = []; // 记录失败的单条数据及原因

        // 分批次处理（1万条→10批，每批1000条）
        $batches = array_chunk($data, $batchSize);
        $batchTotal = count($batches);

        foreach ($batches as $batchIndex => $batch) {
            $batchNo = $batchIndex + 1;
            $batchDataCount = count($batch);

            // 第一步：尝试批量插入当前批次
            Db::startTrans();
            try {
                $insertNum = Db::name($table)->insertAll($batch);

                if ($insertNum === false) {
                    throw new Exception("批量插入失败，触发单条降级处理");
                }

                Db::commit();
                $successCount += $insertNum;
                continue; // 批次成功，处理下一批
            } catch (Exception $e) {
                Db::rollback();
                // 第二步：批量失败，降级为逐条插入（定位问题数据）
                foreach ($batch as $rowIndex => $row) {
                    $dataNo = ($batchIndex * $batchSize) + $rowIndex + 1; // 全局数据序号
                    Db::startTrans();
                    try {
                        // 单条插入（带数据校验，避免无效数据影响）
                        $row = $this->filterInvalidData($row); // 过滤非法值（如null转空字符串）
                        $res = Db::name($table)->insert($row);
                        if ($res === false) {
                            throw new Exception("数据库插入执行失败");
                        }
                        Db::commit();
                        $successCount++;
                    } catch (Exception $e) {
                        Db::rollback();
                        $failCount++;
                        // 记录失败数据及原因（方便后续修正）
                        $failData[] = [
                            'dataNo' => $dataNo, // 第N条数据
                            'data' => $row,      // 失败的具体数据
                            'reason' => $e->getMessage()
                        ];
                    }
                }
            }
        }

        // 结果汇总
        return [
            'code' => $failCount > 0 ? 0 : 1,
            'success' => $successCount,
            'fail' => $failCount,
            'failData' => $failData,
            'msg' => "总数据{$total}条，成功插入{$successCount}条，失败{$failCount}条。" .
                ($failCount > 0 ? "失败数据已记录，可查看failData字段定位问题" : "全部插入成功")
        ];
    }

    //获取当前导入的进度条
    public function refreshProcess()
    {
        $village_id = $this->adminUser['village_id'];
        $type = $this->request->param('type', '', 'trim');
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        if (empty($type)) {
            return api_output(1001, [], '缺少必要参数');
        }
        try {
            $param = array(
                'village_id' => $village_id,
            );
            $result = (new ImportExcelService())->refreshProcess($type, $param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }

    //查询导入记录
    public function getVillageImportRecord()
    {
        $village_id = $this->adminUser['village_id'];
        $type = $this->request->param('type', '', 'trim');
        $yw_common_id = $this->request->param('yw_common_id', 'yw_common_id', 'trim');
        $page = $this->request->param('page', 1, 'int');
        $limit = $this->request->param('limit', 10, 'int');
        if (!$village_id) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        if (empty($type)) {
            return api_output(1001, [], '缺少必要参数');
        }
        try {
            $param = array(
                'village_id' => $village_id,
                'yw_common_id' => $yw_common_id,
                'type' => $type,
                'page' => $page,
                'limit' => $limit
            );
            $list = (new ImportExcelService())->getVillageImportRecord($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list);
    }

    //获取对应导入模板示例
    public function getImportTemplateUrl()
    {
        $village_id = $this->adminUser['village_id'];
        $type = $this->request->param('type', '', 'trim');
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        if (empty($type)) {
            return api_output(1001, [], '缺少必要参数');
        }
        try {
            $param = array(
                'village_id' => $village_id,
            );
            $result = (new ImportExcelService())->getImportTemplateUrl($type, $param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }
}
