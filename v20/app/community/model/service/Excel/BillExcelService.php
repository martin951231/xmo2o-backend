<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      欠费账单导入
 */
namespace app\community\model\service\Excel;

use app\community\model\db\HouseNewChargeNumber;
use app\community\model\db\HouseNewChargeProject;
use app\community\model\db\HouseNewChargeRule;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\service\HouseWorkerService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class BillExcelService
{
    public function getImportTemplateUrl($param) {
        $opttype='billExcel';
        if(isset($param['opttype']) && $param['opttype']){
            $opttype= $param['opttype'];
        }
        $excelTableTile = [
            'A' => '楼栋名称', 'B' => '单元名称', 'C' => '楼层名称', 'D' => '房间号', 'E' => '欠缴费用名称', 'F' => '欠缴金额', 'G' => '备注','H'=>'账单计费开始时间','I'=>'账单计费结束时间','J'=>'账单生成时间(可不填)',
        ];
        $data = [
            [
                'A' => '1', 'B' => '1', 'C' => '5', 'D' => '2', 'E' => '21年物业费', 'F' => '5380.25', 'G' => '2021年欠缴的物业费','H'=>'2024-06-01','I'=>'2024-06-10','J'=>'',
            ],
            [
                'A' => '1', 'B' => '1', 'C' => '5', 'D' => '2', 'E' => '22年物业费', 'F' => '3526.5', 'G' => '2022年欠缴的物业费','H'=>'2024-06-20','I'=>'2024-06-21','J'=>'2024-06-01 10:30:00',
            ],
        ];
        $excelTemplate='欠费导入模板';
        if($opttype=='billPayExcel'){
            $excelTableTile = [
                'A' => '楼栋名称', 'B' => '单元名称', 'C' => '楼层名称', 'D' => '房间号', 'E' => '已缴费用名称', 'F' => '已缴金额', 'G' => '备注','H'=>'账单计费开始时间','I'=>'账单计费结束时间','J'=>'账单生成时间(可不填)','K'=>'缴费时间','L'=>'缴费方式（仅支持系统里的线下支付方式）',
            ];
            $data = [
                [
                    'A' => '1', 'B' => '1', 'C' => '5', 'D' => '2', 'E' => '21年物业费', 'F' => '5380.25', 'G' => '2021年已缴的物业费','H'=>'2024-06-01','I'=>'2024-06-10','J'=>'','K'=>'2024-06-01 14:30:00','L'=>'现金交易',
                ],
                [
                    'A' => '1', 'B' => '1', 'C' => '5', 'D' => '2', 'E' => '22年物业费', 'F' => '3526.5', 'G' => '2022年已缴的物业费','H'=>'2024-06-20','I'=>'2024-06-21','J'=>'2024-06-01 10:30:00','K'=>'2024-06-01 14:30:00','L'=>'微信',
                ],
            ];
            $excelTemplate='一次性已缴费用导入模板';
        }
        $result = $this->saveExcel($data, $excelTableTile, $opttype, $excelTemplate.'.xlsx', 'Xlsx');
        return $result;
    }

    public function getCycleImportTemplateUrl($param) {
        $subject_id = isset($param['subject_id']) ? intval($param['subject_id']) : 0;
        $charge_project_id = isset($param['charge_project_id']) ? intval($param['charge_project_id']) : 0;
        if (!$subject_id || !$charge_project_id) {
            throw new \think\Exception('请先选择收费科目、收费项目');
        }
        $subjectInfo = (new HouseNewChargeNumber())->get_one(
            ['id' => $subject_id, 'status' => 1],
            'id,charge_number_name'
        );
        $projectInfo = (new HouseNewChargeProject())->getOne(
            [
                'id' => $charge_project_id,
                'subject_id' => $subject_id,
                'village_id' => $param['village_id'],
                'type' => 2,
                'status' => 1,
            ],
            'id,name,type'
        );
        if (!$subjectInfo || !$projectInfo) {
            throw new \think\Exception('模板参数对应的数据不存在');
        }
        $excelTableTile = [
            'A' => '楼栋名称',
            'B' => '单元名称',
            'C' => '楼层名称',
            'D' => '房间号',
            'E' => '收费标准名称',
            'F' => '账单金额',
            'G' => '备注',
            'H' => '账单计费开始时间',
            'I' => '账单计费结束时间',
            'J' => '账单生成时间(可不填)',
        ];
        $data = [
            [
                'A' => '1',
                'B' => '1',
                'C' => '5',
                'D' => '2',
                'E' => '物业费标准',
                'F' => '5380.25',
                'G' => '周期性账单导入',
                'H' => '2024-06-01',
                'I' => '2024-06-30',
                'J' => '',
            ],
        ];
        return $this->saveCycleExcel($data, $excelTableTile, '周期性账单导入模板.xlsx', 'Xlsx', [
            'subject_name' => $subjectInfo['charge_number_name'],
            'project_name' => $projectInfo['name'],
            'subject_id' => $subject_id,
            'charge_project_id' => $charge_project_id,
            'village_id' => $param['village_id'],
        ]);
    }

    public function getCyclePayImportTemplateUrl($param) {
        $subject_id = isset($param['subject_id']) ? intval($param['subject_id']) : 0;
        $charge_project_id = isset($param['charge_project_id']) ? intval($param['charge_project_id']) : 0;
        if (!$subject_id || !$charge_project_id) {
            throw new \think\Exception('请先选择收费科目、收费项目');
        }
        $subjectInfo = (new HouseNewChargeNumber())->get_one(['id' => $subject_id, 'status' => 1], 'id,charge_number_name');
        $projectInfo = (new HouseNewChargeProject())->getOne(['id' => $charge_project_id, 'subject_id' => $subject_id, 'village_id' => $param['village_id'], 'type' => 2, 'status' => 1], 'id,name,type');
        if (!$subjectInfo || !$projectInfo) {
            throw new \think\Exception('模板参数对应的数据不存在');
        }
        $excelTableTile = [
            'A' => '楼栋名称', 'B' => '单元名称', 'C' => '楼层名称', 'D' => '房间号', 'E' => '收费标准名称',
            'F' => '账单金额', 'G' => '备注', 'H' => '账单计费开始时间', 'I' => '账单计费结束时间', 'J' => '账单生成时间(可不填)',
            'K' => '缴费人姓名', 'L' => '缴费人手机号', 'M' => '缴费时间', 'N' => '开票状态',
            'O' => '实际缴费金额', 'P' => '实际到账金额', 'Q' => '押金抵扣', 'R' => '积分抵扣金额', 'S' => '积分抵扣数量',
            'T' => '支付方式', 'U' => '收款人',
        ];
        $data = [
            [
                'A' => '1', 'B' => '1', 'C' => '5', 'D' => '2', 'E' => '物业费标准',
                'F' => '5380.25', 'G' => '周期性已缴账单导入', 'H' => '2024-06-01', 'I' => '2024-06-30', 'J' => '',
                'K' => '请填写缴费人姓名', 'L' => '请填写缴费人手机号', 'M' => '2024-06-30 10:30:00', 'N' => '未开票',
                'O' => '5380.25', 'P' => '5380.25', 'Q' => '0', 'R' => '0', 'S' => '0',
                'T' => '现金交易', 'U' => '请填写收款人姓名',
            ],
        ];
        return $this->saveCycleExcel($data, $excelTableTile, '周期性已缴账单导入模板.xlsx', 'Xlsx', [
            'subject_name' => $subjectInfo['charge_number_name'],
            'project_name' => $projectInfo['name'],
            'subject_id' => $subject_id,
            'charge_project_id' => $charge_project_id,
            'village_id' => $param['village_id'],
        ]);
    }

    public function getParkingPayImportTemplateUrl($param) {
        $excelTableTile = [
            'A' => '车库名称', 'B' => '车库ID', 'C' => '车位号', 'D' => '收费标准名称',
            'E' => '账单金额', 'F' => '备注', 'G' => '账单计费开始时间', 'H' => '账单计费结束时间', 'I' => '账单生成时间(可不填)',
            'J' => '缴费人姓名', 'K' => '缴费人手机号', 'L' => '缴费时间', 'M' => '开票状态',
            'N' => '实际缴费金额', 'O' => '实际到账金额', 'P' => '押金抵扣', 'Q' => '积分抵扣金额', 'R' => '积分抵扣数量',
            'S' => '支付方式', 'T' => '收款人',
        ];
        $data = [
            [
                'A' => '地下车库', 'B' => '1', 'C' => 'A001', 'D' => '月租车管理费标准',
                'E' => '300.00', 'F' => '车位管理费已缴账单导入', 'G' => '2026-07-01', 'H' => '2026-07-31', 'I' => '',
                'J' => '请填写缴费人姓名', 'K' => '请填写缴费人手机号', 'L' => '2026-07-31 10:30:00', 'M' => '未开票',
                'N' => '300.00', 'O' => '300.00', 'P' => '0', 'Q' => '0', 'R' => '0',
                'S' => '现金交易', 'T' => '请填写收款人姓名',
            ],
        ];
        return $this->saveCycleExcel($data, $excelTableTile, '车位管理费已缴账单导入模板.xlsx', 'Xlsx', [
            'subject_name' => '车位管理费',
            'project_name' => '车位管理费',
            'village_id' => $param['village_id'],
            'is_parking_bill' => true,
        ]);
    }

    public function saveExcel($data = [], $excelTableTile = [], $opttype='billExcel', $file_name = '', $inputFileType = 'Xlsx')
    {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        //设置单元格内容
        foreach ($excelTableTile as $pCoordinate => $excelHead) {
            $worksheet->setCellValue($pCoordinate.'1', $excelHead);
        }
        //设置单元格样式
        $worksheet->getStyle('A1:O1')->getFont()->setName('黑体')->setSize(12);
        foreach ($excelTableTile as $pColumn => $excelHead) {
            if($opttype=='billPayExcel' && $pColumn=='L'){
                $worksheet->getColumnDimension($pColumn)->setWidth(50);
            }else {
                $worksheet->getColumnDimension($pColumn)->setWidth(30);
            }
        }
        $len = count($data)-1;
        $row = 0;
        $i   = 0;
        $j   = 0;
        foreach ($data as $key => $val) {
            if ($i < $len+1) {
                $j = $i + 2; //从表格第2行开始
                foreach ($excelTableTile as $pCoordinate => $excelHead) {
                    $value=isset($val[$pCoordinate]) && $val[$pCoordinate] ? $val[$pCoordinate]:'';
                    $value=strval($value);
                    $worksheet->setCellValueExplicit($pCoordinate.$j, $value,\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    //$worksheet->setCellValue($pCoordinate.$j, isset($val[$pCoordinate]) && $val[$pCoordinate] ? $val[$pCoordinate] : '');
                }
                $i++;
            }
            $row++;
        }
        $styleArrayBody = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
        ];
        $total_rows = $row + 1;
        //添加所有边框/居中
        $worksheet->getStyle('A1:O' . $total_rows)->applyFromArray($styleArrayBody);
        //下载
        $fileInfo = $this->phpSpreadsheet($file_name, $spreadsheet, $inputFileType);
        if (!$fileInfo) {
            return [];
        }
        return $this->downloadExportFile($file_name);
    }

    private function saveCycleExcel($data = [], $excelTableTile = [], $file_name = '', $inputFileType = 'Xlsx', $extra = [])
    {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->setTitle('周期账单导入');
        $worksheet->setCellValue('A1', '收费科目：'.($extra['subject_name'] ?? ''));
        $worksheet->setCellValue('A2', '收费项目：'.($extra['project_name'] ?? ''));
        $description = !empty($extra['is_parking_bill'])
            ? '说明：按车库ID和车位号定位车位，自动使用车位当前绑定的车位管理费项目；收费标准名称必须与当前绑定标准完全一致。'
            : '说明：导入时以当前选择的收费科目和收费项目为准；收费周期默认1，收费周期类型默认自定义收费周期。';
        $worksheet->setCellValue('A3', $description);
        foreach ($excelTableTile as $pCoordinate => $excelHead) {
            $worksheet->setCellValue($pCoordinate.'4', $excelHead);
        }
        $lastColumn = !empty($excelTableTile) ? max(array_keys($excelTableTile)) : 'O';
        $worksheet->getStyle('A4:' . $lastColumn . '4')->getFont()->setName('黑体')->setSize(12);
        foreach ($excelTableTile as $pColumn => $excelHead) {
            $worksheet->getColumnDimension($pColumn)->setWidth(30);
        }
        $row = 0;
        foreach ($data as $key => $val) {
            $j = $row + 5;
            foreach ($excelTableTile as $pCoordinate => $excelHead) {
                $value=isset($val[$pCoordinate]) && $val[$pCoordinate] ? $val[$pCoordinate]:'';
                $value=strval($value);
                $worksheet->setCellValueExplicit($pCoordinate.$j, $value,\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
            $row++;
        }
        $worksheet->getStyle('A1:' . $lastColumn . ($row + 4))->applyFromArray([
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
        ]);
        if (empty($extra['is_parking_bill'])) {
            $this->appendCycleRuleReferenceSheet($spreadsheet, $extra);
        }
        $spreadsheet->setActiveSheetIndex(0);
        $fileInfo = $this->phpSpreadsheet($file_name, $spreadsheet, $inputFileType);
        if (!$fileInfo) {
            return [];
        }
        return $this->downloadExportFile($file_name);
    }

    private function appendCycleRuleReferenceSheet($spreadsheet, $extra)
    {
        $reference = $spreadsheet->createSheet();
        $reference->setTitle('收费标准参考');
        $titles = [
            'A' => '收费标准ID',
            'B' => '收费标准名称',
            'C' => '收费金额',
            'D' => '计费模式',
        ];
        foreach ($titles as $col => $title) {
            $reference->setCellValue($col.'1', $title);
            $reference->getColumnDimension($col)->setWidth(30);
        }
        $feesType = [1 => '固定费用', 2 => '单价计量单位', 3 => '临时车', 4 => '月租车', 5 => '车位数量'];
        $where = [
            ['village_id', '=', $extra['village_id'] ?? 0],
            ['subject_id', '=', $extra['subject_id'] ?? 0],
            ['charge_project_id', '=', $extra['charge_project_id'] ?? 0],
            ['status', '=', 1],
        ];
        $list = (new HouseNewChargeRule())->getList($where, 'id,charge_name,charge_price,unit_price,fees_type', 'id desc');
        $row = 2;
        if ($list && !$list->isEmpty()) {
            foreach ($list as $rule) {
                $reference->setCellValueExplicit('A'.$row, strval($rule['id']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $reference->setCellValueExplicit('B'.$row, strval($rule['charge_name']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $price = $rule['charge_price'] ? $rule['charge_price'] : $rule['unit_price'];
                $reference->setCellValueExplicit('C'.$row, strval($price), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $reference->setCellValueExplicit('D'.$row, $feesType[intval($rule['fees_type'])] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $row++;
            }
        }
        $reference->getStyle('A1:D' . max(1, $row - 1))->applyFromArray([
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
        ]);
    }

    public function phpSpreadsheet($filename,$spreadsheet, $inputFileType = 'Xlsx'){
        // 设置输出头部信息
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');
        try{
            $writer =  IOFactory::createWriter($spreadsheet, $inputFileType);
            $document_root = request()->server('DOCUMENT_ROOT');
            if (!$document_root) {
                $document_root = app()->getRootPath() . '..';
            }
            $basePath = $document_root.'/v20/runtime/';
            $filename = $basePath.$filename;
            $writer->save($filename);
            return true;
        }catch(\Exception $e){
            return false;
        }

    }

    /**
     * 下载表格
     */
    public function downloadExportFile($filename)
    {
        $document_root = request()->server('DOCUMENT_ROOT');
        if (!$document_root) {
            $document_root = app()->getRootPath() . '..';
        }
        $returnArr = [];
        if (!file_exists($document_root . '/v20/runtime/' . $filename)) {
            $returnArr['error'] = 1;
            return $returnArr;
        }

        $returnArr['url'] = cfg('site_url') . '/v20/runtime/' . $filename;
        $returnArr['error'] = 0;
        return $returnArr;
    }
}
