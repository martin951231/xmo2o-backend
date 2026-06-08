<?php

/**
 * Author lkz
 * Date: 2023/1/10
 * Time: 13:17
 */

namespace app\community\model\service;

use app\common\model\service\config\ConfigCustomizationService;
use app\common\model\service\export\ExportService as BaseExportService;
use app\common\model\service\UploadFileService;
use app\community\model\db\HouseNewOfflinePay;
use app\traits\CacheTypeTraits;
use app\traits\house\HouseTraits;
use app\traits\ImportExcelTraits;
use customization\customization;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ImportExcelService
{
    use HouseTraits, ImportExcelTraits, CacheTypeTraits;
    use customization;
    const upload_building = 'uploadBuilding'; //上传楼栋
    const upload_unit = 'uploadUnit'; //上传单元
    const upload_floor = 'uploadFloor'; //上传楼层
    const upload_room = 'uploadRoom'; //上传房间
    const upload_room_wsxdsj = 'uploadRoomWsxdsj'; //上传房间(定制)
    const upload_three_table = 'uploadThreeTable'; //定制三表 水电燃
    const UPLOAD_BILL_EXCEL = 'billExcel'; // 定制 欠费账单导入
    const UPLOAD_BILL_PAY_EXCEL = 'billPayExcel'; // 定制 一次性费用已缴账单导入
    const UPLOAD_CYCLE_BILL_EXCEL = 'cycleBillExcel'; // 定制 周期性账单导入
    const UPLOAD_CYCLE_BILL_PAY_EXCEL = 'cycleBillPayExcel'; // 定制 周期性已缴账单导入
    const upload_standard_vacancy_bind = 'standardVacancyBind'; // 定制 导入标准绑定房间数据
    const upload_standard_position_bind = 'standardPositionBind'; // 定制 导入标准绑定车位数据
    const upload_express_application = 'expressApplication';
    const replace_field_skip = 'skip'; //跳过
    const replace_field_cover = 'cover'; //覆盖

    //自定义命令 
    public $command = [
        self::upload_building => 'buildingImport',
        self::upload_unit => 'unitImport',
        self::upload_floor => 'floorImport',
        self::upload_room => 'roomImport',
        self::upload_three_table => 'threeTableImport',
        self::UPLOAD_BILL_EXCEL => 'billExcelImport',
        self::UPLOAD_BILL_PAY_EXCEL => 'billExcelImport',
        self::UPLOAD_CYCLE_BILL_EXCEL => 'billExcelImport',
        self::UPLOAD_CYCLE_BILL_PAY_EXCEL => 'billExcelImport',
        self::upload_standard_vacancy_bind => 'standardVacancyBindImport',
        self::upload_standard_position_bind => 'standardPositionBindImport',
        self::upload_express_application => 'expressApplication',
    ];

    //导入模板示例
    public $importTemplateUrl = [
        self::upload_building => '/static/file/single_import_sample.xls',
        self::upload_unit => '/static/file/unit_import_samples.xls',
        self::upload_floor => '/static/file/layer_import_sample.xls',
        self::upload_room => '/static/file/room_import_sample.xls',
        self::upload_room_wsxdsj => '/static/file/room_import_wsxdsj_sample.xls',
        self::upload_three_table => '/static/file/three_table_import_sample.xls',
        self::upload_standard_vacancy_bind => '/static/file/standard_vacancy_bind_sample.xls',
        self::upload_standard_position_bind => '/static/file/standard_position_bind_sample.xls',
        self::upload_express_application => '/static/file/express_application_sample.xlsx',
    ];

    //楼栋可覆盖字段
    public $buildingReplaceField = [
        ['option' => 'single_name', 'title' => '楼栋名称', 'msg' => ''],
    ];

    //单元可覆盖字段
    public $unitReplaceField = [
        ['option' => 'floor_name', 'title' => '单元名称', 'msg' => ''],
    ];

    //楼层可覆盖字段
    public $floorReplaceField = [
        ['option' => 'layer_name', 'title' => '楼层名称', 'msg' => ''],
    ];

    //房间可覆盖字段
    public $roomReplaceField = [
        ['option' => 'housesize', 'title' => '面积(m²)', 'msg' => ''],
        ['option' => 'house_type', 'title' => '使用类型(填住宅/商铺/办公)', 'msg' => ''],
        ['option' => 'sort', 'title' => '排序', 'msg' => ''],
        ['option' => 'room_alias_id', 'title' => '房间别名编号', 'msg' => ''],
        ['option' => 'property_starttime', 'title' => '物业开始时间', 'msg' => ''],
        ['option' => 'property_endtime', 'title' => '物业结束时间', 'msg' => ''],
    ];

    //====================================todo 共用方法 start======================================

    /**
     * 获取当前毫秒.
     * @return float
     */
    public function getMillisecondMethod()
    {
        [$msec, $sec] = explode(' ', microtime());
        return (float) sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    }

    //todo 组装指定类型标识
    public function getProcessCacheKeyMethod($village_id, $bussiness)
    {
        return 'fastwhale:' . $bussiness . ':process:' . $village_id;
    }

    //todo 根据来源类型 返回对应配置 针对数据重复时
    public function getReplaceFieldSelectMethod($type)
    {
        $data = [];
        switch ($type) {
            case self::upload_building:
                $data[] = ['key' => self::replace_field_skip, 'value' => '跳过', 'children' => []];
                $data[] = ['key' => self::replace_field_cover, 'value' => '覆盖', 'children' => $this->buildingReplaceField];
                break;
            case self::upload_unit:
                $data[] = ['key' => self::replace_field_skip, 'value' => '跳过', 'children' => []];
                $data[] = ['key' => self::replace_field_cover, 'value' => '覆盖', 'children' => $this->unitReplaceField];
                break;
            case self::upload_floor:
                $data[] = ['key' => self::replace_field_skip, 'value' => '跳过', 'children' => []];
                $data[] = ['key' => self::replace_field_cover, 'value' => '覆盖', 'children' => $this->floorReplaceField];
                break;
            case self::upload_room:
                $data[] = ['key' => self::replace_field_skip, 'value' => '跳过', 'children' => []];
                $children = $this->roomReplaceField;
                /**
                $isAihorseCustomizedJudge = (new ConfigCustomizationService())->getAihorseCustomizedJudge();
                if($isAihorseCustomizedJudge){
                    $children[]=array('option'=> 'water_balance','title' => '水费预存金额','msg'=>'重复导入时，值为正则加，值为负责减');
                }
                 **/
                $data[] = ['key' => self::replace_field_cover, 'value' => '覆盖', 'children' => $children];
                break;
            case self::upload_three_table:
                $data = [];
                break;
            case self::UPLOAD_BILL_EXCEL:
                $data = [];
                break;
            case 'billPayExcel':
                $data = [];
                break;
            case self::UPLOAD_CYCLE_BILL_EXCEL:
                $data = [];
                break;
            case self::UPLOAD_CYCLE_BILL_PAY_EXCEL:
                $data = [];
                break;
            case self::upload_standard_vacancy_bind:
                $data = [];
                break;
            case self::upload_standard_position_bind:
                $data = [];
                break;
            case self::upload_express_application:
                $data = [];
                break;
            default:
                throw new \think\Exception('暂无该类型参数');
        }
        return $data;
    }

    //todo 根据来源类型 返回对应映射字段结构
    public function getSourceMapFieldsMethod($type)
    {
        $data = [];
        switch ($type) {
            case self::upload_building:
                $data = [
                    ['key' => '楼栋名称', 'val' => [
                        'field'     => 'single_name',
                        'isMust'    => true,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '楼栋编号', 'val' => [
                        'field'     => 'single_number',
                        'isMust'    => true,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '楼栋面积', 'val' => [
                        'field'     => 'measure_area',
                        'isMust'    => false,
                        'fieldType' => 'number'
                    ], 'selected'  => false],
                    ['key' => '所含单元数', 'val' => [
                        'field'     => 'floor_num',
                        'isMust'    => false,
                        'fieldType' => 'number'
                    ], 'selected'  => false],
                    ['key' => '所含房屋数', 'val' => [
                        'field'     => 'vacancy_num',
                        'isMust'    => false,
                        'fieldType' => 'number'
                    ], 'selected'  => false],
                    ['key' => '排序(倒序)', 'val' => [
                        'field'     => 'sort',
                        'isMust'    => false,
                        'fieldType' => 'number'
                    ], 'selected'  => false],
                    ['key' => '合同开始时间', 'val' => [
                        'field'     => 'contract_time_start',
                        'isMust'    => false,
                        'fieldType' => 'date'
                    ], 'selected'  => false],
                    ['key' => '合同结束时间', 'val' => [
                        'field'     => 'contract_time_end',
                        'isMust'    => false,
                        'fieldType' => 'date'
                    ], 'selected'  => false]
                ];
                break;
            case self::upload_unit:
                $data = [
                    ['key' => '单元名称', 'val' => [
                        'field'     => 'floor_name',
                        'isMust'    => true,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '单元编号', 'val' => [
                        'field'     => 'floor_number',
                        'isMust'    => true,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '所属楼栋名称', 'val' => [
                        'field'     => 'single_name',
                        'isMust'    => true,
                        'fieldType' => 'number'
                    ], 'selected'  => false],
                    ['key' => '单元面积', 'val' => [
                        'field'     => 'floor_area',
                        'isMust'    => false,
                        'fieldType' => 'number'
                    ], 'selected'  => false],
                    ['key' => '排序', 'val' => [
                        'field'     => 'sort',
                        'isMust'    => false,
                        'fieldType' => 'number'
                    ], 'selected'  => false],
                ];
                break;
            case self::upload_floor:
                $data = [
                    ['key' => '楼层名称', 'val' => [
                        'field'     => 'layer_name',
                        'isMust'    => true,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '楼层编号', 'val' => [
                        'field'     => 'layer_number',
                        'isMust'    => true,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '所属楼栋名称', 'val' => [
                        'field'     => 'single_name',
                        'isMust'    => true,
                        'fieldType' => 'number'
                    ], 'selected'  => false],
                    ['key' => '所属单元名称', 'val' => [
                        'field'     => 'floor_name',
                        'isMust'    => true,
                        'fieldType' => 'number'
                    ], 'selected'  => false],
                    ['key' => '排序', 'val' => [
                        'field'     => 'sort',
                        'isMust'    => false,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                ];
                break;
            case self::upload_room:
                $data = [
                    ['key' => '物业编码', 'val' => [
                        'field'     => 'usernum',
                        'isMust'    => false,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '楼栋名称', 'val' => [
                        'field'     => 'single_name',
                        'isMust'    => true,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '楼栋编号', 'val' => [
                        'field'     => 'single_number',
                        'isMust'    => true,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '单元名称', 'val' => [
                        'field'     => 'floor_name',
                        'isMust'    => true,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '单元编号', 'val' => [
                        'field'     => 'floor_number',
                        'isMust'    => true,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '楼层名称', 'val' => [
                        'field'     => 'layer_name',
                        'isMust'    => true,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '楼层编号', 'val' => [
                        'field'     => 'layer_number',
                        'isMust'    => true,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '房间号', 'val' => [
                        'field'     => 'room_name',
                        'isMust'    => true,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '房间编号', 'val' => [
                        'field'     => 'room_number',
                        'isMust'    => true,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '面积(m²)', 'val' => [
                        'field'     => 'housesize',
                        'isMust'    => false,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '使用类型(填住宅/商铺/办公)', 'val' => [
                        'field'     => 'house_type',
                        'isMust'    => true,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '物业开始时间', 'val' => [
                        'field'     => 'property_starttime',
                        'isMust'    => false,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '物业结束时间', 'val' => [
                        'field'     => 'property_endtime',
                        'isMust'    => false,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '房间别名编号', 'val' => [
                        'field'     => 'room_alias_id',
                        'isMust'    => false,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '排序', 'val' => [
                        'field'     => 'sort',
                        'isMust'    => false,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                ];
                $isAihorseCustomizedJudge = (new ConfigCustomizationService())->getAihorseCustomizedJudge();
                if ($isAihorseCustomizedJudge && $this->hasAihorseWNet()) {
                    $data[] = ['key' => '入住日期', 'val' => [
                        'field'     => 'house_in_time',
                        'isMust'    => false,
                        'fieldType' => 'string'
                    ], 'selected'  => false];
                    /**
                    $data[] = ['key' => '水费预存金额','val' => [
                        'field'     => 'water_balance',
                        'isMust'    => false,
                        'fieldType' => 'string'
                    ],'selected'  => false];
                     **/
                }
                $jz_wsxdsj_net_customized_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
                if ($jz_wsxdsj_net_customized_open) {
                    $data[] = ['key' => '供暖面积(m²)', 'val' => [
                        'field'     => 'heating_size',
                        'isMust'    => false,
                        'fieldType' => 'string'
                    ], 'selected'  => false];
                    $data[] = ['key' => '计费面积(m²)', 'val' => [
                        'field'     => 'billable_size',
                        'isMust'    => false,
                        'fieldType' => 'string'
                    ], 'selected'  => false];
                }
                break;
            case self::upload_three_table:
                $data = [
                    ['key' => '楼栋名称', 'val' => [
                        'field'     => 'single_name',
                        'isMust'    => true,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '单元名称', 'val' => [
                        'field'     => 'floor_name',
                        'isMust'    => true,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '楼层名称', 'val' => [
                        'field'     => 'layer_name',
                        'isMust'    => true,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '房间号', 'val' => [
                        'field'     => 'room',
                        'isMust'    => true,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '电表编号', 'val' => [
                        'field'     => 'ele_number',
                        'isMust'    => false,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '冷水表编号', 'val' => [
                        'field'     => 'water_number',
                        'isMust'    => false,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '热水表编号', 'val' => [
                        'field'     => 'heat_water_number',
                        'isMust'    => false,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '燃气表编号', 'val' => [
                        'field'     => 'gas_number',
                        'isMust'    => false,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                ];
                break;
            case self::UPLOAD_BILL_EXCEL:
                $data = [];
                break;
            case 'billPayExcel':
                $data = [];
                break;
            case self::UPLOAD_CYCLE_BILL_EXCEL:
                $data = [];
                break;
            case self::UPLOAD_CYCLE_BILL_PAY_EXCEL:
                $data = [];
                break;
            case self::upload_express_application:
                $data = [
                    ['key' => '小区名称', 'val' => [
                        'field'     => 'village_name',
                        'isMust'    => true,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '楼栋名', 'val' => [
                        'field'     => 'single_name',
                        'isMust'    => true,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '单元名', 'val' => [
                        'field'     => 'floor_name',
                        'isMust'    => true,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '楼层名', 'val' => [
                        'field'     => 'layer_name',
                        'isMust'    => true,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '房间号', 'val' => [
                        'field'     => 'room_name',
                        'isMust'    => true,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '取件人', 'val' => [
                        'field'     => 'pickup_person_name',
                        'isMust'    => false,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '快递单号', 'val' => [
                        'field'     => 'track_num',
                        'isMust'    => false,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '取件码', 'val' => [
                        'field'     => 'pickup_code',
                        'isMust'    => true,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '取件地址', 'val' => [
                        'field'     => 'location',
                        'isMust'    => true,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '收件人', 'val' => [
                        'field'     => 'recipient_name',
                        'isMust'    => true,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '收件电话', 'val' => [
                        'field'     => 'recipient_phone',
                        'isMust'    => true,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '取件状态', 'val' => [
                        'field'     => 'status_name',
                        'isMust'    => true,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '业主备注', 'val' => [
                        'field'     => 'remark',
                        'isMust'    => false,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                    ['key' => '代取结果描述', 'val' => [
                        'field'     => 'reason',
                        'isMust'    => false,
                        'fieldType' => 'string'
                    ], 'selected'  => false],
                ];
                break;
            default:
                throw new \think\Exception('暂无该类型参数');
        }
        return $data;
    }

    //todo 读取文件
    public function getFileReaderMethod($fileName, $is_read = true)
    {
        $fileArr = explode('.', $fileName);
        $fileExt = strtolower(end($fileArr));
        if ($fileExt === 'xls') {
            $inputFileType = "Xls";
        } else if ($fileExt === 'xlsx') {
            $inputFileType = "Xlsx";
        }
        try {
            $reader = IOFactory::createReader($inputFileType);
            if ($is_read) {
                $reader->setReadDataOnly(true); //只读
            }
        } catch (\Exception $e) {
            $this->importQueueFdump(['读取文件失败==' . __LINE__, $fileName, $is_read, $e->getMessage()], 'import_excel/getFileReaderMethodError', 1);
            if ($is_read) {
                throw new \think\Exception($e->getMessage());
            } else {
                return [];
            }
        }
        return $reader;
    }

    //todo 校验数据重复时，是否跳过 true:跳过
    public function checkReplaceFieldSkipMethod($find_type)
    {
        $status = false;
        if ($find_type == self::replace_field_skip) { //跳过
            $status = true;
        }
        return $status;
    }

    //todo 生成错误导出文件
    public function exportExcelMethod($village_id, $title, $data, $fileName)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getDefaultColumnDimension()->setWidth(25);
        $sheet->getDefaultRowDimension()->setRowHeight(40); //设置行高
        //  $sheet->getStyle('A')->getNumberFormat()->setFormatCode('@');
        //设置单元格内容
        $titCol = 'A';
        $lastKey = '';
        foreach ($title as $key => $value) {
            //单元格内容写入
            $sheet->setCellValue($titCol . '1', $value);
            if ($value) {
                $lastKey = $titCol;
            }
            $titCol++;
        }

        $sheet->getColumnDimension($lastKey)->setWidth(80);

        $row = 2;
        foreach ($data as $item) {
            $dataCol = 'A';
            foreach ($item as $value) {
                //单元格内容写入
                $value = strval($value);
                $sheet->setCellValueExplicit($dataCol . $row, $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                //$sheet->setCellValue($dataCol . $row, $value);
                $dataCol++;
            }
            $row++;
        }
        //保存
        $styleArrayBody = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
        ];
        $total_rows = $row + 1;
        //添加所有边框/居中
        $sheet->getStyle('A1:O' . $total_rows)->applyFromArray($styleArrayBody);
        //下载
        $path = 'upload/villageExport/' . $village_id . '/';
        $filename = $fileName . '.xlsx';
        $basePath = dirname(root_path()) . '/' . $path;
        if (!is_dir($basePath)) {
            mkdir($basePath, 0777, true);
        }
        (new BaseExportService())->phpSpreadsheet($filename, $spreadsheet, $basePath);
        return  $path . $filename;
    }

    //todo 覆盖模式下 楼栋更新对应指定数据
    public function updateBuildingReplaceFieldMethod($village_id, $replaceField, $single_name, $single_number)
    {
        $where = [
            ['village_id', '=', $village_id],
            ['status', '=', 1],
        ];
        $value = '';
        $title = '';
        if ($replaceField == 'single_name') { //更新楼栋名称
            $value = $single_name;
            $title = '楼栋名称';
        } elseif ($replaceField == 'single_number') { //更新楼栋编号
            $value = $single_number;
            $title = '楼栋编号';
        }
        if (!$value) {
            return ['error' => false, 'msg' => '类型不存在', 'date' => []];
        }
        $where[] = [$replaceField, '=', $value];
        $field = 'id';
        $info = (new NewBuildingService())->getHouseVillageSingleFindMethod($where, $field);
        if (!$info) {
            return ['error' => false, 'msg' => '覆盖模式下,该[' . $title . ']对应的楼栋不存在', 'date' => []];
        }
        return ['error' => true, 'msg' => 'ok', 'data' => $info['id']];
    }

    //todo 覆盖模式下 单元更新对应指定数据
    public function updateUnitReplaceFieldMethod($village_id, $single_id, $replaceField, $floor_name, $floor_number)
    {
        $where = [
            ['village_id', '=', $village_id],
            ['single_id', '=', $single_id],
            ['status', '<>', 4]
        ];
        $value = '';
        $title = '';
        if ($replaceField == 'floor_name') { //更新单元名称
            $value = $floor_name;
            $title = '单元名称';
        } elseif ($replaceField == 'floor_number') { //更新单元编号
            $value = $floor_number;
            $title = '单元编号';
        }
        if (!$value) {
            return ['error' => false, 'msg' => '类型不存在', 'date' => []];
        }
        $where[] = [$replaceField, '=', $value];
        $field = 'floor_id as id';
        $info = (new NewBuildingService())->getHouseVillageFloorFindMethod($where, $field);
        if (!$info) {
            return ['error' => false, 'msg' => '覆盖模式下,该[' . $title . ']对应的单元不存在!', 'date' => []];
        }
        return ['error' => true, 'msg' => 'ok', 'data' => $info['id']];
    }


    //todo 覆盖模式下 楼层更新对应指定数据
    public function updateFloorReplaceFieldMethod($village_id, $floor_id, $replaceField, $layer_name, $layer_number)
    {
        $where = [
            ['village_id', '=', $village_id],
            ['floor_id', '=', $floor_id],
            ['status', '<>', 4]
        ];
        $value = '';
        $title = '';
        if ($replaceField == 'layer_name') { //更新楼层名称
            $value = $layer_name;
            $title = '楼层名称';
        } elseif ($replaceField == 'layer_number') { //更新楼层编号
            $value = $layer_number;
            $title = '楼层编号';
        }
        if (!$value) {
            return ['error' => false, 'msg' => '类型不存在', 'date' => []];
        }
        $where[] = [$replaceField, '=', $value];
        $field = 'id';
        $info = (new NewBuildingService())->getHouseVillageLayerFindMethod($where, $field);
        if (!$info) {
            return ['error' => false, 'msg' => '覆盖模式下,该[' . $title . ']对应的楼层不存在!', 'date' => []];
        }
        return ['error' => true, 'msg' => 'ok', 'data' => $info['id']];
    }


    //====================================todo 业务方法 start======================================

    //todo 上传文件 接收和返回类型
    public function uploadFile($type, $param, $upload_dir = 'villageImport')
    {
        $service = new UploadFileService();
        $processCacheKey = $this->getProcessCacheKeyMethod($param['village_id'], $type);
        try {
            $processInfo = $this->queueReids()->get($processCacheKey);
        } catch (\Exception $e) {
            throw new \think\Exception('请先配置Redis');
        }
        $process = $processInfo['process'];
        if (!is_null($process)) {
            if (in_array(intval($process), [0, 100])) {
                $this->queueReids()->delete($processCacheKey);
            } else {
                throw new \think\Exception('当前已有数据在执行中，当前执行进度【' . $process . '%】请耐心等待执行完，再导入！');
            }
        }
        $valdate = [
            'fileSize' => 1024 * 1024 * 10, // 10M 
            'fileExt' => 'xls,xlsx'
        ];
        $upload_dir = $upload_dir . DIRECTORY_SEPARATOR . $param['village_id'];
        try {
            $savepath = $service->uploadFile($param['file'], $upload_dir, $valdate);
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }
        return  ['path' => $savepath, 'type' => $type, 'file_name' => basename($savepath)];
    }

    //todo 文件读取 返回对应类型数据格式
    public function readImportFile($type, $param)
    {
        try {
            $reader = $this->getFileReaderMethod($param['inputFileName']);
            $worksheetData = $reader->listWorksheetInfo($param['inputFileName']);
            $allData = $this->readImportFileList($type, $param);
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }
        $replaceField = $this->getReplaceFieldSelectMethod($type);
        return  ['worksheet' => $worksheetData, 'allData' => $allData['data'],'invalidData' => $allData['invalidData'],'requiredFieldsMap' => $allData['requiredFieldsMap'], 'replaceFieldSelect' => $replaceField, 'type' => $type];
    }

    // 周期性账单上传后只需要读取sheet信息，具体表头和数据由周期性账单导入命令单独校验
    public function readCycleBillImportFile($param)
    {
        try {
            $reader = $this->getFileReaderMethod($param['inputFileName']);
            $worksheetData = $reader->listWorksheetInfo($param['inputFileName']);
            foreach ($worksheetData as &$sheetInfo) {
                $sheetInfo['totalRows'] = max(1, intval($sheetInfo['totalRows']) - 3);
            }
            unset($sheetInfo);
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }
        $type = isset($param['type']) && $param['type'] ? $param['type'] : self::UPLOAD_CYCLE_BILL_EXCEL;
        return  ['worksheet' => $worksheetData, 'allData' => [], 'invalidData' => [], 'requiredFieldsMap' => [], 'replaceFieldSelect' => [], 'type' => $type];
    }

    public function readImportFileList($type, $param)
    {
        try {
            $inputFileName = $param['inputFileName'];
            $reader = $this->getFileReaderMethod($inputFileName);

            $worksheetInfo = $reader->listWorksheetInfo($inputFileName);
            $finalData = [];         // 有效数据（所有必填字段均不为空）
            $invalidData = [];       // 无效数据（含失败原因）
            $sourceMapFields = [];
            $requiredFieldsMap = []; // 存储必填字段：系统字段名→中文名称（如 village_name→小区名称）

            foreach ($worksheetInfo as $sheetIndex => $sheetInfo) {
                $worksheetName = $sheetInfo['worksheetName'];
                $totalRows = $sheetInfo['totalRows'];
                $excelTotalCols = $sheetInfo['totalColumns'];

                $reader->setLoadSheetsOnly($worksheetName);
                $spreadsheet = $reader->load($inputFileName);
                $worksheet = $spreadsheet->getActiveSheet();

                // 1. 读取Excel表头
                $excelHeaders = [];
                for ($col = 1; $col <= $excelTotalCols; $col++) {
                    $cell = $worksheet->getCellByColumnAndRow($col, 1);
                    $cellValue = $cell->getValue();
                    $cellDataType = $cell->getDataType();
                    if ($cellDataType === 'f') {
                        $cellValue = $cell->getCalculatedValue() ?? '';
                    }
                    $excelHeaders[] = is_null($cellValue) ? '' : trim((string)$cellValue);
                }

                // 2. 获取系统映射关系，提取【必填字段（isMust: true）】及中文名称
                $sourceMapFields = $this->getSourceMapFieldsMethod($type, $excelHeaders);
                $headerToField = [];
                foreach ($sourceMapFields as $item) {
                    $systemField = $item['val']['field'];
                    $chineseName = $item['key']; // 字段中文名称（用于失败提示）
                    $headerToField[$item['key']] = $systemField;
                    // 仅存储 isMust: true 的字段（系统字段名→中文名称）
                    if ($item['val']['isMust'] === true) {
                        $requiredFieldsMap[$systemField] = $chineseName;
                    }
                }
                $requiredSystemFields = array_keys($requiredFieldsMap); // 必填的系统字段名数组

                // 3. 校验表头匹配性
                $unmatchedHeaders = [];
                foreach ($excelHeaders as $header) {
//                    if (!isset($headerToField[$header])) {
                    if (!$this->inputContainsArrayKey($header,$headerToField)) {
                        $unmatchedHeaders[] = $header;
                    }
                }
                if (!empty($unmatchedHeaders)) {
                    throw new \think\Exception("以下表头未找到对应系统字段：" . implode('、', $unmatchedHeaders));
                }

                // 4. 读取数据并校验，新增失败原因
                for ($row = 2; $row <= $totalRows; $row++) {
                    $rowData = [];
                    $emptyRequiredFields = []; // 记录当前行空值的必填字段（中文名称）

                    for ($col = 1; $col <= $excelTotalCols; $col++) {
                        $colIndex = $col - 1;
                        $currentHeader = $excelHeaders[$colIndex];
                        $systemField = $headerToField[$currentHeader];

                        // 获取单元格值
                        $cell = $worksheet->getCellByColumnAndRow($col, $row);
                        $cellValue = $cell->getValue();
                        $cellDataType = $cell->getDataType();
                        if ($cellDataType === 'f') {
                            $cellValue = $cell->getCalculatedValue() ?? '';
                        }
                        $cellValue = is_null($cellValue) ? '' : trim((string)$cellValue);
                        $rowData[$systemField] = $cellValue;

                        // 记录空值的必填字段（中文名称）
                        if (in_array($systemField, $requiredSystemFields) && $cellValue === '') {
                            $emptyRequiredFields[] = $requiredFieldsMap[$systemField];
                        }
                    }

                    // 分类存储：有效数据/含失败原因的无效数据
                    if (!empty($emptyRequiredFields)) {
                        // 新增失败原因字段
                        $rowData['failReason'] = "必填字段为空：" . implode('、', $emptyRequiredFields);
                        $invalidData[] = $rowData;
                    } else {
                        $finalData[] = $rowData;
                    }
                }

                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
                break;
            }

            $replaceField = $this->getReplaceFieldSelectMethod($type);
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }

        return [
            'data' => $finalData,         // 有效数据（无 failReason 字段）
            'invalidData' => $invalidData, // 无效数据（含 failReason 字段）
            'sourceMapFields' => $sourceMapFields,
            'replaceFieldSelect' => $replaceField,
            'type' => $type
        ];
    }

    public function inputContainsArrayKey($inputStr, $arr) {
        foreach (array_keys($arr) as $key) {
            // 检查传入字符串是否包含当前键名（区分大小写，中文无影响）
            if (strpos($inputStr, $key) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * 辅助方法：判断Excel表头名称与系统字段名称是否匹配（支持模糊匹配）
     * @param string $excelName Excel表头名称
     * @param string $sysName 系统字段中文名称
     * @return bool 是否匹配
     */
    private function isNameMatched($excelName, $sysName)
    {
        // 标准化：去空格、特殊字符、转小写
        $normalize = function ($str) {
            $str = trim($str);
            $str = preg_replace('/[^\p{L}\p{N}]/u', '', $str); // 移除非字母数字字符
            return mb_strtolower($str);
        };
        $excelNormalized = $normalize($excelName);
        $sysNormalized = $normalize($sysName);

        // 匹配规则：
        // 1. 完全相同（优先）
        // 2. Excel名称包含系统字段关键词
        // 3. 系统字段包含Excel名称关键词
        return $excelNormalized === $sysNormalized
            || strpos($excelNormalized, $sysNormalized) !== false
            || strpos($sysNormalized, $excelNormalized) !== false;
    }
    //todo 文件解析 返回对应类型数据
    public function analysisImportFile($type, $param)
    {
        try {
            $reader = $this->getFileReaderMethod($param['inputFileName']);
            $spreadsheet = $reader->load($param['inputFileName']);
        } catch (\Exception $e) {
            throw new \think\Exception('该文件不存在，请重新上传');
        }
        $sheet = $spreadsheet->getSheet($param['selectWorkSheetIndex']); //获取定制 sheet index 的工作表的内容
        $highestRow = $sheet->getHighestRow(); // 总行数
        $highestColumn = $sheet->getHighestColumn(); // 最后有数据的一列，比如 S 列
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn); //把列字母转为数字,比如可以得到有 10 列
        //获取列 （字母）
        $columnNames = [];        //列数 初始化,
        for ($j = 1; $j <= $highestColumnIndex; $j++) {
            $columnNames[] = Coordinate::stringFromColumnIndex($j); //得到列的首字母
        }
        //获取第一行（表头）
        $excelCloumns = [];
        $colTitle = [];
        $rowNum = 1;
        $A1text = $sheet->getCell('A1')->getValue();
        $B1text = $sheet->getCell('B1')->getValue();
        $C1text = $sheet->getCell('C1')->getValue();
        if ($type == 'uploadRoom' && (strpos($A1text, '意：楼栋编号/单元编号/楼层编号/房间编号必须填写阿拉伯数字') || (empty($A1text) && empty($B1text) && empty($C1text)))) {
            $rowNum = 2;
        }
        foreach ($columnNames as $col) {
            $temp = [];
            $text = $sheet->getCell($col . $rowNum)->getValue();
            if (empty($text)) {
                continue;
            }
            $text = ($text instanceof RichText) ? trim($text->getPlainText()) : trim($text);
            $text = str_replace('(必填)', '', $text);
            $text = str_replace('（必填）', '', $text);
            $text = str_replace('必填', '', $text);
            $text = str_replace('(选填)', '', $text);
            $text = str_replace('（选填）', '', $text);
            $text = str_replace('选填', '', $text);
            $text = str_replace('(个)', '', $text);
            $text = str_replace('（个）', '', $text);
            $text = str_replace('个', '', $text);
            $text = str_replace('(单位:m²)', '', $text);
            $text = str_replace('（单位:m²）', '', $text);
            $text = str_replace('单位:m²', '', $text);
            $text = strval($text);
            $text = trim($text);
            $temp['key']    = $col;
            $temp['val']    = $text;
            $excelCloumns[] = $temp;
            $colTitle[] =  $text;
        }

        $sourceMapFields = $this->getSourceMapFieldsMethod($type);
        fdump_api(['sourceMapFields' => $sourceMapFields, 'colTitle' => $colTitle], '$analysisImportFile');
        foreach ($sourceMapFields as &$source) {
            foreach ($colTitle as $title) {
                $title = trim($title);
                if ($source['key'] == $title) {
                    $source['selected'] = true;
                }
            }
        }
        return ['excelCloumns' => $excelCloumns, 'sourceMapFields' => $sourceMapFields, 'type' => $type];
    }

    //todo 触发导入 走队列执行
    public function triggerImportFile($type, $param)
    {
        if (!isset($this->command[$type])) {
            throw new \think\Exception('暂无该类型参数');
        }
        $param['command'] = $this->command[$type];
        $param['type'] = $type;
        $queueId = $this->buildingImportQueue($param);
        return ['queueId' => $queueId, 'type' => $type];
    }

    //todo 获取当前导入的进度条
    public function refreshProcess($type, $param)
    {
        $processCacheKey = $this->getProcessCacheKeyMethod($param['village_id'], $type);
        try {
            $processInfo = $this->queueReids()->get($processCacheKey);
        } catch (\Exception $e) {
            throw new \think\Exception('请先配置Redis');
        }
        $process = intval($processInfo['process']);
        if ($process == 100) {
            $this->queueReids()->delete($processCacheKey);
        }
        return ['process' => $process, 'processCacheKey' => $processCacheKey, 'processInfo' => $processInfo, 'type' => $type];
    }

    //todo 查询导入记录
    public function getVillageImportRecord($param)
    {
        $where = [
            ['village_id', '=', $param['village_id']],
            ['type', '=', $param['type']],
        ];
        if (isset($param['yw_common_id']) && $param['yw_common_id'] > 0) {
            $where[] = array('yw_common_id', '=', $param['yw_common_id']);
        }
        $field = 'file_name,file_url,duration,import_msg,status,add_time';
        $order = 'id DESC';
        $result = (new NewBuildingService())->getHouseVillageImportRecordMethod($where, $field, $order, $param['page'], $param['limit']);
        if ($result['list']) {
            foreach ($result['list'] as &$v) {
                $v['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
                $v['file_url'] = $v['file_url'] ?  cfg('site_url') . '/' . $v['file_url'] : '';
            }
        }
        $data['list'] = $result['list'];
        $data['total_limit'] = $param['limit'];
        $data['count'] = $result['count'];
        return $data;
    }

    //todo 获取对应导入模板示例
    public function getImportTemplateUrl($type, $param)
    {
        if (!isset($this->importTemplateUrl[$type])) {
            throw new \think\Exception('该类型不存在');
        }
        $jz_wsxdsj_net_customized_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        $isAihorseCustomizedJudge = (new ConfigCustomizationService())->getAihorseCustomizedJudge();
        if ($jz_wsxdsj_net_customized_open) {
            $type = ($type == 'uploadRoom') ? 'uploadRoomWsxdsj' : $type;
            $url = cfg('site_url') . $this->importTemplateUrl[$type];
        } else {
            $url = cfg('site_url') . $this->importTemplateUrl[$type];
        }
        if ($type == 'uploadRoom' && $isAihorseCustomizedJudge) {
            $url = cfg('site_url') . '/static/file/room_import_aihorse_sample.xls';
        }
        return ['url' => $url];
    }
    public function getOfflinePayTypeList($property_id = 0, $key_type = 'name')
    {
        $db_house_new_offline_pay = new HouseNewOfflinePay();
        $offline_pay = $db_house_new_offline_pay->getList(['property_id' => $property_id, 'status' => 1], 'id,name');
        $offlineArr = array();
        if (!empty($offline_pay)) {
            $offline_pay = $offline_pay->toArray();
            if (!empty($offline_pay)) {
                foreach ($offline_pay as $v) {
                    if ($key_type == 'name') {
                        $offlineArr[$v['name']] = $v;
                    } else {
                        $offlineArr[] = $v;
                    }
                }
            }
        }
        return $offlineArr;
    }
}
