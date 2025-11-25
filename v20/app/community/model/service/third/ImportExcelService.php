<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      三方数据房产相关导入处理
 */
namespace app\community\model\service\third;


use app\community\model\db\HouseNewChargeNumber;
use app\community\model\db\HouseNewChargeProject;
use app\community\model\db\HouseNewChargeRule;
use app\community\model\db\HouseNewOfflinePay;
use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\HouseNewPayOrderSummary;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageBindPosition;
use app\community\model\db\HouseVillageFloor;
use app\community\model\db\HouseVillageLayer;
use app\community\model\db\HouseVillageParkingGarage;
use app\community\model\db\HouseVillageParkingPosition;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\PlatOrder;
use app\community\model\db\third\HouseVillageThirdImportOrder;
use app\community\model\db\third\HouseVillageThirdImportPark;
use app\community\model\db\third\HouseVillageThirdImportRoom;
use app\community\model\db\third\HouseVillageUserBindRelation;
use app\community\model\service\Car\CarHandleGetInfoService;
use app\community\model\service\Car\HouseVillageCarFlowService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use app\common\ChunkReadFilter;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use think\facade\Cache;
use think\facade\Config;

use app\traits\third\ImportExcelTraits;
use app\community\model\db\third\HouseVillageThirdImportData;
use app\consts\thirdImportDataConst;

use app\community\model\db\HouseVillageSingle;

class ImportExcelService
{
    use ImportExcelTraits;
    
    // 房产表EXCEL
    protected $roomColAndFiledArr = [
        [ 'key'   => "type_id",               'value' => "A"],// 序号
        [ 'key'   => "third_address",         'value' => "B"],// 房产
        [ 'key'   => "third_main_status_txt", 'value' => "C"],// 房产状态
        [ 'key'   => "third_main_value1",     'value' => "D"],// 建筑面积
        [ 'key'   => "third_main_value2",     'value' => "E"],// 计费面积
        [ 'key'   => "third_main_value3",     'value' => "F"],// 辅助计费面积
        [ 'key'   => "third_unit_name",       'value' => "G"],// 收费项目
        [ 'key'   => "third_unit_price",      'value' => "H"],// 费用单价
        [ 'key'   => "third_type1_txt",       'value' => "I"],// 房产类型
        [ 'key'   => "third_type2_txt",       'value' => "J"],// 房产性质
        [ 'key'   => "third_name",            'value' => "K"],// 业主
        [ 'key'   => "third_phone",           'value' => "L"],// 业主电话
        [ 'key'   => "third_remark",          'value' => "M"],// 备注
        [ 'key'   => "third_key",             'value' => "N"],// 关键字
    ];
    
    public    $pattern_number = "/[^0-9]/";
    
    public    $nowTime;
    protected $village_id;
    protected $property_id;
    protected $third_address,$third_main_status_txt,$third_type1_txt,$third_type2_txt,$third_main_status,$third_type1,$third_type2,$third_main_value1,$third_main_value2,$third_main_value3,$third_unit_name,$third_unit_price;
    // 数据库表
    protected $dbHouseVillageThirdImportData;
    protected $dbHouseVillageParkingGarage;
    protected $dbHouseVillageParkingPosition;
    protected $dbHouseVillageThirdImportPark;
    protected $dbHouseVillageSingle;
    protected $dbHouseVillageFloor;
    protected $dbHouseVillageLayer;
    protected $dbHouseVillageUserVacancy;
    protected $dbHouseVillageThirdImportRoom;

    protected $dbHouseVillage;
    protected $dbHouseNewChargeNumber;
    protected $dbHouseNewChargeProject;
    protected $dbHouseNewChargeRule;
    protected $dbHouseNewPayOrder;
    protected $dbHouseNewOfflinePay;
    protected $dbHouseNewPayOrderSummary;
    protected $dbPlatOrder;
    protected $dbHouseVillageThirdImportOrder;
    protected $dbHouseVillageBindPosition;
    protected $houseVillageInfo = [];
    
    protected $order_group_id;
    protected $order_group_err;
    
    // 业主表EXCEL
    protected $userColAndFiledArr = [
        [ 'key'   => "third_type1_txt",   'value' => "A"],// 分子公司
        [ 'key'   => "type_id",           'value' => "B"],// 个人客户ID
        [ 'key'   => "third_name",        'value' => "C"],// 业主姓名
        [ 'key'   => "third_main_value1", 'value' => "D"],// 业主性别
        [ 'key'   => "third_main_value2", 'value' => "E"],// 业主婚姻状况
        [ 'key'   => "third_main_value3", 'value' => "F"],// 项目名称
        [ 'key'   => "third_address",     'value' => "G"],// 房号
        [ 'key'   => "type2_id",          'value' => "H"],// 成员家庭ID
    ];
    /**
     * member_id                   成员家庭ID; member_name         家庭成员姓名; member_relation             与户主关系;
     * member_sex                    成员性别; special_identity       特殊身份; member_nation                 成员民族;
     * member_native_place           成员籍贯; member_birthday     成员出生日期; member_marital_status     成员婚姻状况;
     * member_card_type           成员证件类型; member_card         成员证件号码; member_education          成员文化程度;
     * member_phone                  成员手机; member_nationality     成员国籍; registered_residence     成员户口所在地;
     * member_province               成员省份; member_city            成员城市; member_settlement_city     成员定居城市;
     * member_resident_type        员常驻类型; member_home_address 成员家庭住址; member_emergency_contact  成员紧急联系人;
     * member_emergency_phone 成员紧急联系电话;
     * @var \string[][] 
     */
    protected $userThirdOtherJsonArr = [
        [ 'key'   => "member_id",                'value' => "H"],// 成员家庭ID
        [ 'key'   => "member_name",              'value' => "I"],// 家庭成员姓名
        [ 'key'   => "member_relation",          'value' => "J"],// 与户主关系
        [ 'key'   => "member_sex",               'value' => "K"],// 成员性别
        [ 'key'   => "special_identity",         'value' => "L"],// 特殊身份
        [ 'key'   => "member_nation",            'value' => "M"],// 成员民族
        [ 'key'   => "member_native_place",      'value' => "N"],// 成员籍贯
        [ 'key'   => "member_birthday",          'value' => "O"],// 成员出生日期
        [ 'key'   => "member_marital_status",    'value' => "P"],// 成员婚姻状况
        [ 'key'   => "member_card_type",         'value' => "Q"],// 成员证件类型
        [ 'key'   => "member_card",              'value' => "R"],// 成员证件号码
        [ 'key'   => "member_education",         'value' => "S"],// 成员文化程度
        [ 'key'   => "member_phone",             'value' => "T"],// 成员手机
        [ 'key'   => "member_nationality",       'value' => "U"],// 成员国籍
        [ 'key'   => "registered_residence",     'value' => "V"],// 成员户口所在地
        [ 'key'   => "member_province",          'value' => "W"],// 成员省份
        [ 'key'   => "member_city",              'value' => "X"],// 成员城市
        [ 'key'   => "member_settlement_city",   'value' => "Y"],// 成员定居城市
        [ 'key'   => "member_resident_type",     'value' => "Z"],// 成员常驻类型
        [ 'key'   => "member_home_address",      'value' => "AA"],// 成员家庭住址
        [ 'key'   => "member_emergency_contact", 'value' => "AB"],// 成员紧急联系人
        [ 'key'   => "member_emergency_phone",   'value' => "AC"],// 成员紧急联系电话
    ];

    // 收费表EXCEL
    protected $chargeColAndFiledArr = [
        [ 'key'   => "third_type1_txt",       'value' => "A"],// 组团
        [ 'key'   => "third_address",         'value' => "B"],// 房产
        [ 'key'   => "third_main_status_txt", 'value' => "C"],// 房态
        [ 'key'   => "third_main_value1",     'value' => "D"],// 房产面积
        [ 'key'   => "third_main_value2",     'value' => "E"],// 计算面积
        [ 'key'   => "third_main_value3",     'value' => "F"],// 数量
        [ 'key'   => "third_name",            'value' => "G"],// 客户
        [ 'key'   => "third_phone",           'value' => "H"],// 联系电话
        [ 'key'   => "third_unit_name",       'value' => "I"],// 收费项目
        [ 'key'   => "third_unit_price",      'value' => "J"],// 单价
        [ 'key'   => "third_type2_txt",       'value' => "L"],// 计费周期
    ];
    
    protected $chargeThirdOtherJsonArr = [
        [ 'key'   => "charge_receivable_date",   'value' => "K"],// 应收日期
        [ 'key'   => "charge_billing_cycle",     'value' => "L"],// 计费周期
        [ 'key'   => "charge_amount_receivable", 'value' => "M"],// 应收金额
        [ 'key'   => "charge_discount",          'value' => "N"],// 折扣
        [ 'key'   => "charge_actual_receivable", 'value' => "O"],// 实际应收
        [ 'key'   => "charge_received_amount",   'value' => "P"],// 已收金额
        [ 'key'   => "charge_amount_owed",       'value' => "Q"],// 欠费金额
        [ 'key'   => "charge_amount_on_account", 'value' => "R"],// 挂账金额
        [ 'key'   => "charge_remarks",           'value' => "S"],// 备注
    ];

    /**
     * @var \think\cache\Driver redis缓存
     */
    protected $redisCache;
    
    public function __construct(){
       $this->redisCache =  Cache::store('redis');
    }

    public function chargeExecute($param) {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);
        $this->nowTime = time();
        //1、接收参数 、绑定参数
        //2、按照映射关系写入库
        //3、然后使用 Redis 反馈实时导入进度
        //4、记录一下导入数据变更记录和 整理导入错误的文件

        $inputFileName             = $param['inputFileName'];
        $nowVillageInfo            = $param['nowVillageInfo'];
        $worksheetName             = $param['worksheetName'];

        $this->village_id          = $nowVillageInfo['village_id'];
        $orderGroupId              = isset($param['orderGroupId']) ? $param['orderGroupId'] : $this->village_id;
        
        $this->order_group_id      = $orderGroupId;
        
        $this->property_id               = isset($nowVillageInfo['property_id']) ? $nowVillageInfo['property_id'] : 0;

        $colAndFiledArr = array_column($this->chargeColAndFiledArr,'value','key');
        $thirdOtherJsonArr = array_column($this->chargeThirdOtherJsonArr,'key','value');

        //使用Redis缓存 进度 
        $processCacheKey = 'thirdimport:thirdChargeInfo:process:'.$orderGroupId;
        $totalCacheKey   = 'thirdimport:thirdChargeInfo:total:'.$orderGroupId;
        $surplusCacheKey = 'thirdimport:thirdChargeInfo:surplus:'.$orderGroupId;
        $excelCacheKey   = 'thirdimport:thirdChargeInfo:excel:'.$orderGroupId;

        if (empty(Config::get('cache.stores.redis'))){
            fdump_api(['msg' => "请先配置 Redis 缓存 " . app()->getRootPath() . 'config/cache.php'],'$importExcelErrChargeLog',1);
        }

        try {
            $this->redisCache->set($processCacheKey,0,3600);
        }catch (\Exception $e){
            //TODO 应该直接通过 队列 压入对应的业务日志里
            fdump_api(['msg' => '设置redis缓存错误', 'eMessage'=>$e->getMessage()],'$importExcelErrChargeLog',1);
        }


        $inputFileName = root_path() . '/../'.$inputFileName;
        $fileArr       = explode('.',$inputFileName);
        $fileExt       = strtolower(end($fileArr));
        $inputFileName = str_replace('//', '/', $inputFileName);

        if ($fileExt === 'xls'){
            $inputFileType = "Xls";
        }else if($fileExt === 'xlsx'){
            $inputFileType = "Xlsx";
        } else {
            $inputFileType = "Xls";
        }
        $reader = IOFactory::createReader($inputFileType);

        $spreadsheet        = $reader->load($inputFileName);
        $sheet              = $spreadsheet->getSheetByName($worksheetName);
        if (!$sheet) {
            $sheet          = $spreadsheet->getActiveSheet();
            $worksheetName  = '';
        }
        $highestRow         = $sheet->getHighestRow(); // 总行数（）)
        $highestColumn      = $sheet->getHighestColumn();// 最后有数据的一列，比如 S 列
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);//把列字母转为数字,比如可以得到有 10 列


        $errColumnKey       = Coordinate::stringFromColumnIndex($highestColumnIndex + 1);
        $errColumnKeyTip    = Coordinate::stringFromColumnIndex($highestColumnIndex + 2);
        if (!$errColumnKey) {
            $errColumnKey = 'O';
        }

        $this->redisCache->set($totalCacheKey,$highestRow-1,3600); //总行数
        $surplusRow = $highestRow-1;
//
//        $str = "把列字母转为数字「{$highestColumnIndex}」总行数(有数据)：【{$highestRow}】,最后一列【{$highestColumn}】".PHP_EOL;
//        fdump_api(['msg' => "把列字母转为数字「{$highestColumnIndex}」总行数(有数据)：【{$highestRow}】,最后一列【{$highestColumn}】"],'$importExcelChargeLog',1);

        $chunkSize = 1000;
        $chunkFilter = new ChunkReadFilter();
        $reader->setReadFilter($chunkFilter);
        $j = 1;

        $this->dbHouseVillageThirdImportData = new HouseVillageThirdImportData();

        $insertErrData = [];
        $excelTableTile = [];
        // 过滤出数字
        $hasNext = true;
        for ($startRow = 2; $startRow <= $highestRow; $startRow += $chunkSize) {
            if (!$hasNext) {
                $startRow = $highestRow;
                $process = $startRow / $highestRow;
                if ($startRow + $chunkSize >=$highestRow ){
                    $process = 0.99;
                }
                $process = round($process, 4);
                if ($process >= 1) {
                    $process = 0.99;
                }
                $this->redisCache->set($processCacheKey,$process * 100,3600); //进度
                break;
            }
            $chunkFilter->setRows($startRow, $chunkSize);
            $spreadsheet = $reader->load($inputFileName);

            if ($worksheetName) {
                $sheetData = $spreadsheet->getSheetByName($worksheetName)->toArray(NULL, true, true, true);
            } else {
                $sheetData = $sheet->toArray(NULL, true, true, true);
            }
            if (count($sheetData) >= $highestRow) {
                $hasNext = false;
            }
            $excelTableTile = $sheetData[1];
            $excelTableTile[$errColumnKey]    = '错误信息';
            $excelTableTile[$errColumnKeyTip] = '错误信息在导入表行数';
            unset($sheetData[1]); //去掉表头

            foreach ($sheetData as $dk => $data) {
                $surplusRow = $surplusRow - 1;
                $this->order_group_err = '';
                if (!isset($data[$errColumnKeyTip]) || !$data[$errColumnKeyTip]) {
                    $data[$errColumnKeyTip] = $dk;
                }
                $third_address   = isset($data[$colAndFiledArr['third_address']])   && $data[$colAndFiledArr['third_address']]   ? $data[$colAndFiledArr['third_address']]   : '';  // 地址
                $third_unit_name = isset($data[$colAndFiledArr['third_unit_name']]) && $data[$colAndFiledArr['third_unit_name']] ? $data[$colAndFiledArr['third_unit_name']] : '';// 收费项目
                $third_type2_txt = isset($data[$colAndFiledArr['third_type2_txt']]) && $data[$colAndFiledArr['third_type2_txt']] ? $data[$colAndFiledArr['third_type2_txt']] : '';// 计费周期

                if (!$third_address || !$third_unit_name) {
                    $this->filterRecordErr('缺少必要参数【房产】[B列]或者【收费项目】[I列]');
                    $data[$errColumnKey] = $this->order_group_err;
                    $insertErrData[] = $data;
                    continue;
                }

                $thirdOtherJsonData = $data;
                $third_import_data = [
                    'type'               => thirdImportDataConst::THIRD_YWYL_CHARGE_DATA,
                    'type_id'            => 0,
                    'type2_id'           => 0,
                    'third_address'      => $third_address,
                    'village_id'         => $this->village_id,
                    'third_unit_name'    => $third_unit_name,
                    'third_type2_txt'    => $third_type2_txt,
                ];
                // 区分唯一值
                $repeat_key_arr = $third_import_data;
                $third_key      = $third_address;

                unset($thirdOtherJsonData[$colAndFiledArr['third_address']], $thirdOtherJsonData[$colAndFiledArr['third_unit_name']]);

                $keyA = strval($colAndFiledArr['third_type1_txt']);
                if (isset($data[$keyA])) {
                    $third_type1_txt = $data[$keyA] ? $data[$keyA] : '';
                    unset($thirdOtherJsonData[$keyA]);
                    $third_import_data['third_type1_txt'] = $third_type1_txt;
                } else {
                    $third_type1_txt = '';
                    unset($thirdOtherJsonData[$keyA]);
                }

                $keyC = strval($colAndFiledArr['third_main_status_txt']);
                if (isset($data[$keyC])) {
                    $third_main_status_txt = $data[$keyC] ? $data[$keyC] : '';
                    unset($thirdOtherJsonData[$keyC]);
                    $third_import_data['third_main_status_txt'] = $third_main_status_txt;
                } else {
                    $third_main_status_txt = '';
                    unset($thirdOtherJsonData[$keyC]);
                }

                $keyD = strval($colAndFiledArr['third_main_value1']);
                if (isset($data[$keyD])) {
                    $third_main_value1 = $data[$keyD] ? $data[$keyD] : '';
                    unset($thirdOtherJsonData[$keyD]);
                    $third_import_data['third_main_value1'] = $third_main_value1;
                } else {
                    $third_main_value1 = '';
                    unset($thirdOtherJsonData[$keyD]);
                }

                $keyE = strval($colAndFiledArr['third_main_value2']);
                if (isset($data[$keyE])) {
                    $third_main_value2 = $data[$keyE] ? $data[$keyE] : '';
                    unset($thirdOtherJsonData[$keyE]);
                    $third_import_data['third_main_value2'] = $third_main_value2;
                } else {
                    $third_main_value2 = '';
                    unset($thirdOtherJsonData[$keyE]);
                }

                $keyF = strval($colAndFiledArr['third_main_value3']);
                if (isset($data[$keyF])) {
                    $third_main_value3 = $data[$keyF] ? $data[$keyF] : '';
                    unset($thirdOtherJsonData[$keyF]);
                    $third_import_data['third_main_value3'] = $third_main_value3;
                } else {
                    $third_main_value3 = '';
                    unset($thirdOtherJsonData[$keyF]);
                }

                $keyG = strval($colAndFiledArr['third_name']);
                if (isset($data[$keyG])) {
                    $third_name = $data[$keyG] ? $data[$keyG] : '';
                    unset($thirdOtherJsonData[$keyG]);
                    $third_import_data['third_name'] = $third_name;
                } else {
                    $third_name = '';
                    unset($thirdOtherJsonData[$keyG]);
                }

                $keyH = strval($colAndFiledArr['third_phone']);
                if (isset($data[$keyH])) {
                    $third_phone = $data[$keyH] ? $data[$keyH] : '';
                    $third_phone = str_replace('.0', '', $third_phone);
                    unset($thirdOtherJsonData[$keyH]);
                    $third_import_data['third_phone'] = $third_phone;
                } else {
                    $third_phone = '';
                    unset($thirdOtherJsonData[$keyH]);
                }

                $keyJ = strval($colAndFiledArr['third_unit_price']);
                if (isset($data[$keyJ])) {
                    $third_unit_price = $data[$keyJ] ? $data[$keyJ] : '';
                    unset($thirdOtherJsonData[$keyJ]);
                    $third_import_data['third_unit_price'] = $third_unit_price;
                } else {
                    $third_unit_price = 0;
                    unset($thirdOtherJsonData[$keyJ]);
                }


                if (!empty($thirdOtherJsonData)) {
                    foreach ($thirdOtherJsonData as $keyJson => $valJson) {
                        if (isset($thirdOtherJsonArr[$keyJson])) {
                            $thirdOtherJsonData[$thirdOtherJsonArr[$keyJson]] = $valJson ? $valJson : '';
                            unset($thirdOtherJsonData[$keyJson]);
                        }
                    }
                }

                // 区分唯一值
                $charge_amount_on_account = isset($thirdOtherJsonData['charge_amount_on_account'])              ? $thirdOtherJsonData['charge_amount_on_account'] : 0;
                $charge_amount_owed       = isset($thirdOtherJsonData['charge_amount_owed'])                    ? $thirdOtherJsonData['charge_amount_owed']       : '';
                $charge_received_amount   = isset($thirdOtherJsonData['charge_received_amount'])                ? $thirdOtherJsonData['charge_received_amount']   : '';
                $charge_amount_receivable = isset($thirdOtherJsonData['charge_amount_receivable'])              ? $thirdOtherJsonData['charge_amount_receivable'] : 0;
                $charge_actual_receivable = isset($thirdOtherJsonData['charge_actual_receivable'])              ? $thirdOtherJsonData['charge_actual_receivable'] : 0;

                $repeat_key_arr['charge_amount_on_account'] = $charge_amount_on_account;
                $repeat_key_arr['charge_amount_owed']       = $charge_amount_owed;
                $repeat_key_arr['charge_received_amount']   = $charge_received_amount;
                $repeat_key_arr['charge_actual_receivable'] = $charge_actual_receivable;
                $repeat_key_arr['charge_amount_receivable'] = $charge_amount_receivable;

                $repeat_key = md5(json_encode($repeat_key_arr, JSON_UNESCAPED_UNICODE));
                $third_import_data['repeat_key']                = $repeat_key;

                $third_import_data['third_other_json'] = json_encode($thirdOtherJsonData, JSON_UNESCAPED_UNICODE);

                $third_import_data['add_time']              = $this->nowTime;
                $id = $this->dbHouseVillageThirdImportData->add($third_import_data);
                if (!$id) {
                    $this->filterRecordErr('添加导入记录失败');
                    $data[$errColumnKey] = $this->order_group_err;
                    $insertErrData[] = $data;
                }
                // todo 处理 同步 楼栋单元楼层房屋
                if ($id) {
                    $third_address_arr = explode('->', $third_address);
                    // todo 变更状态
                    if (!isset($third_address_arr[0]) || !$third_address_arr[0]) {
                        $whereUpdate = [];
                        $whereUpdate[] = ['id','=', $id];
                        $handle_err_json = json_encode(['line'=> __LINE__, 'village_id' => $this->village_id, 'third_id' => $id, '$third_address' => $third_address, 'third_address_arr' => $third_address_arr], JSON_UNESCAPED_UNICODE);
                        $updateData = [
                            'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                            'handle_msg'      => '缺少房产',
                            'handle_err_json' => $handle_err_json,
                            'update_time'     => $this->nowTime,
                        ];
                        $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
                        $this->filterRecordErr('缺少房产【房产】[B列]');
                        $data[$errColumnKey] = $this->order_group_err;
                        $insertErrData[] = $data;
                        continue;
                    }

                    $houseNewPayOrderArr = [
                        'village_id'   => $this->village_id,
                        'property_id'  => $this->property_id,
                    ];
                    $room_id     = -1;
                    $position_id = -1;
                    if (count($third_address_arr) == 1) {
                        // 只有1个长度不进行房屋处理
                        $room_id     = 0;
                        $position_id = 0;
                    }
                    $houseArr = [
                        'village_id'  => $this->village_id,
                        'third_id'    => $id,
                        'cut_address' => $third_address,
                    ];

                    $third_key_json_arr = [
                        'cut_type'   => 'park',
                        'cut_table'  => 'house_village_third_import_data',
                        'third_key'  => $third_key,
                        'village_id' => $this->village_id,
                    ];
                    $park_third_key_json = md5(json_encode($third_key_json_arr, JSON_UNESCAPED_UNICODE));
                    $parkInfo = $this->getThirdImportPark($park_third_key_json, $third_key_json_arr);
                    if ($this->order_group_err) {
                        $data[$errColumnKey] = $this->order_group_err;
                        $insertErrData[] = $data;
                        continue;
                    }
                    if (isset($parkInfo['position_id']) && $parkInfo['position_id']) {
                        $position_id = $parkInfo['position_id'];
                    }

                    if(!$position_id || $position_id == -1) {
                        if (isset($third_key) && $third_key) {
                            $third_key_json_arr = [
                                'cut_type'   => 'room',
                                'cut_table'  => 'house_village_third_import_data',
                                'third_key'  => $third_key,
                                'village_id' => $this->village_id,
                            ];
                            $third_key_json_room = md5(json_encode($third_key_json_arr, JSON_UNESCAPED_UNICODE));
                            $houseArr = $this->getThirdImportRoom($third_key_json_room, $third_key_json_arr);
                            if ($this->order_group_err) {
                                $data[$errColumnKey] = $this->order_group_err;
                                $insertErrData[] = $data;
                                continue;
                            }
                            $houseArr['third_key'] = $third_key;
                        } else {
                            $this->third_main_status_txt = isset($third_main_status_txt) ? $third_main_status_txt : '';
                            if (count($third_address_arr)==3) {
                                $houseArr = $this->thirdAddress3Handle($third_address_arr, $id, $third_import_data, $houseArr);
                            } elseif (count($third_address_arr)==4) {
                                $houseArr = $this->thirdAddress4Handle($third_address_arr, $id, $third_import_data, $houseArr);
                            } elseif (count($third_address_arr)==5) {
                                $houseArr = $this->thirdAddress5Handle($third_address_arr, $id, $third_import_data, $houseArr);
                            }
                            if (!empty($houseArr)) {
                                $houseArr['cut_type'] = 'charge';
                                $houseArr = $this->handleThirdHouseInfo($houseArr);
                            }
                        }
                        if (isset($houseArr['room_id']) && $houseArr['room_id']) {
                            $room_id = $houseArr['room_id'];
                        }
                        if ($this->order_group_err) {
                            $data[$errColumnKey] = $this->order_group_err;
                            $insertErrData[] = $data;
                            continue;
                        }
                    }
                    if (!$this->dbHouseVillageUserBind) {
                        $this->dbHouseVillageUserBind = new HouseVillageUserBind();
                    }
                    $whereUserBind = [];
                    if ($room_id > 0 && isset($third_phone) && $third_phone) {
                        // todo  查询对应用户在小区身份
                        // 查询下住户是否存在了
                        $whereUserBind[] = ['village_id', '=', $this->village_id];
                        $whereUserBind[] = ['vacancy_id', '=', $room_id];
                        $whereUserBind[] = ['status', '=', 1];
                        $whereUserBind[] = ['phone', '=', $third_phone];
                        $houseNewPayOrderArr['room_id'] = $room_id;

                    } elseif ($room_id > 0 && isset($third_name) && $third_name) {
                        // todo  查询对应用户在小区身份
                        // 查询下住户是否存在了
                        $whereUserBind[] = ['village_id', '=', $this->village_id];
                        $whereUserBind[] = ['vacancy_id', '=', $room_id];
                        $whereUserBind[] = ['status', '=', 1];
                        $whereUserBind[] = ['name', '=', $third_name];
                        $houseNewPayOrderArr['room_id'] = $room_id;
                    }
                    if (isset($third_name) && $third_name) {
                        $houseNewPayOrderArr['pay_bind_name'] = $third_name;
                    }
                    if (isset($third_phone) && $third_phone) {
                        $houseNewPayOrderArr['pay_bind_phone'] = $third_phone;
                    }
                    if (!empty($whereUserBind)) {
                        $userBind = $this->dbHouseVillageUserBind->getOne($whereUserBind);
                        if ($userBind && !is_array($userBind)) {
                            $userBind = $userBind->toArray();
                        }
                        if (isset($userBind['uid']) && $userBind['uid']) {
                            $thirdOtherJsonData['pay_uid'] = $userBind['uid'];
                        }
                        if (isset($userBind['pigcms_id']) && $userBind['pigcms_id']) {
                            $houseNewPayOrderArr['pay_bind_id'] = $userBind['pigcms_id'];
                        }
                        if (isset($userBind['name']) && $userBind['name']) {
                            $houseNewPayOrderArr['pay_bind_name'] = $userBind['name'];
                        }
                        if (isset($userBind['phone']) && $userBind['phone']) {
                            $houseNewPayOrderArr['pay_bind_phone'] = $userBind['phone'];
                        }
                        $whereUserBindOwner = [];
                        $whereUserBindOwner[] = ['type', 'in', [0,3]];
                        $whereUserBindOwner[] = ['village_id', '=', $this->village_id];
                        $whereUserBindOwner[] = ['vacancy_id', '=', $room_id];
                        $whereUserBindOwner[] = ['status', '=', 1];
                        $userBindOwner = $this->dbHouseVillageUserBind->getOne($whereUserBindOwner);
                        if ($userBindOwner && !is_array($userBindOwner)) {
                            $userBindOwner = $userBindOwner->toArray();
                        }
                        if (isset($userBindOwner['uid']) && $userBindOwner['uid']) {
                            $houseNewPayOrderArr['uid'] = $userBindOwner['uid'];
                        }
                        if (isset($userBindOwner['pigcms_id']) && $userBindOwner['pigcms_id']) {
                            $houseNewPayOrderArr['pigcms_id'] = $userBindOwner['pigcms_id'];
                        }
                        if (isset($userBindOwner['name']) && $userBindOwner['name']) {
                            $houseNewPayOrderArr['name'] = $userBindOwner['name'];
                        }
                        if (isset($userBindOwner['phone']) && $userBindOwner['phone']) {
                            $houseNewPayOrderArr['phone'] = $userBindOwner['phone'];
                        }
                    }
                    if (isset($position_id) && $position_id > 0) {
                        $houseNewPayOrderArr['position_id'] = $position_id;

                        if (!$this->dbHouseVillageBindPosition) {
                            $this->dbHouseVillageBindPosition = new HouseVillageBindPosition();
                        }
                        $whereBindPosition = [];
                        $whereBindPosition[] = ['position_id', '=', $position_id];
                        $whereBindPosition[] = ['village_id',  '=', $this->village_id];
                        $bindPosition = $this->dbHouseVillageBindPosition->getOne($whereBindPosition);
                        if ($bindPosition && !is_array($bindPosition)) {
                            $bindPosition = $bindPosition->toArray();
                        }
                        $bind_id = isset($bindPosition['user_id']) && $bindPosition['user_id'] ? $bindPosition['user_id'] : 0;
                        if ($bind_id) {
                            $whereUserBindOwner = [];
                            $whereUserBindOwner[] = ['village_id', '=', $this->village_id];
                            $whereUserBindOwner[] = ['pigcms_id', '=', $bind_id];
                            $whereUserBindOwner[] = ['status', '=', 1];
                            $userBindOwner = $this->dbHouseVillageUserBind->getOne($whereUserBindOwner);
                            if ($userBindOwner && !is_array($userBindOwner)) {
                                $userBindOwner = $userBindOwner->toArray();
                            }
                            if (isset($userBindOwner['uid']) && $userBindOwner['uid']) {
                                $houseNewPayOrderArr['uid'] = $userBindOwner['uid'];
                            }
                            if (isset($userBindOwner['pigcms_id']) && $userBindOwner['pigcms_id']) {
                                $houseNewPayOrderArr['pigcms_id'] = $userBindOwner['pigcms_id'];
                            }
                            if (isset($userBindOwner['name']) && $userBindOwner['name']) {
                                $houseNewPayOrderArr['name'] = $userBindOwner['name'];
                            }
                            if (isset($userBindOwner['phone']) && $userBindOwner['phone']) {
                                $houseNewPayOrderArr['phone'] = $userBindOwner['phone'];
                            }
                        }
                    }

                    $this->commonThirdAddressHandle($third_import_data);

                    if (isset($third_key) && $third_key) {
                        $thirdOtherJsonData['third_key'] = $third_key;
                    }
                    // todo 去生成或者获取收费标准
                    $chargeRuleArr =  $this->handleVillageChargeRule($third_import_data, $thirdOtherJsonData, $id);
                    if (!$chargeRuleArr) {
                        if ($this->order_group_err) {
                            $data[$errColumnKey] = $this->order_group_err;
                            $insertErrData[] = $data;
                            continue;
                        }
                        continue;
                    }

                    $orderInfoArr = $houseNewPayOrderArr;
                    $this->handleThirdrderInfo($orderInfoArr, $thirdOtherJsonData, $chargeRuleArr, $id);

                    if ($this->order_group_err) {
                        $data[$errColumnKey] = $this->order_group_err;
                        $insertErrData[] = $data;
                        continue;
                    }
                }

                $this->redisCache->set($surplusCacheKey,$surplusRow,3600); //剩余数

                $surplusProcess = $surplusRow / ($highestRow - 1);
                if ($surplusProcess > 1) {
                    $process = 0;
                } else {
                    $process = 1 - $surplusProcess;
                }
                $process = round($process, 4);
                if ($process >= 1) {
                    $process = 0.99;
                }
                $this->redisCache->set($processCacheKey,$process * 100,3600); //进度
            }
            
            $process = $startRow / $highestRow;
            if ($startRow + $chunkSize >=$highestRow ){
                $process = 0.99;
            }
            $process = round($process, 4);
            if ($process >= 1) {
                $process = 0.99;
            }
            $this->redisCache->set($processCacheKey,$process * 100,3600); //进度
            
            $j++;
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            $spreadsheet = NULL;
            unset($sheetData);
            $sheetData = NULL;
        }

        if (!empty($insertErrData) && !empty($excelTableTile)) {
            $fileInfo = pathinfo($inputFileName);
            if (isset($fileInfo['filename']) && $fileInfo['filename'] && isset($fileInfo['extension']) && $fileInfo['extension']) {
                $file_name = $fileInfo['filename'] . '-导入错误表.' . $fileInfo['extension'];
            } else {
                $file_name = '账单-导入错误表.xls';
            }
            $excelInfo = $this->saveExcel($insertErrData, $excelTableTile, $errColumnKey, $file_name, $inputFileType);
            if (isset($excelInfo['url']) && $excelInfo['url']) {
                $this->redisCache->set($excelCacheKey,$excelInfo['url'],3600); //错误表格
            }
            sleep(2);
            $this->redisCache->set($processCacheKey,100,3600); //进度
        } else {
            $this->redisCache->set($processCacheKey,100,3600); //进度
        }
        
        return true;
    }

    protected function handleThirdrderInfo($orderInfoArr, $thirdOtherJsonData, $chargeRuleArr, $third_id) {
        $charge_received_amount   = isset($thirdOtherJsonData['charge_received_amount'])                ? trim($thirdOtherJsonData['charge_received_amount'])   : '';
        $charge_amount_owed       = isset($thirdOtherJsonData['charge_amount_owed'])                    ? trim($thirdOtherJsonData['charge_amount_owed'])       : '';
        $charge_amount_receivable = isset($thirdOtherJsonData['charge_amount_receivable'])              ? trim($thirdOtherJsonData['charge_amount_receivable']) : 0;
        $charge_actual_receivable = isset($thirdOtherJsonData['charge_actual_receivable'])              ? trim($thirdOtherJsonData['charge_actual_receivable']) : 0;
        $charge_amount_on_account = isset($thirdOtherJsonData['charge_amount_on_account'])              ? trim($thirdOtherJsonData['charge_amount_on_account']) : 0;
        $charge_remarks           = isset($thirdOtherJsonData['charge_remarks'])                        ? trim($thirdOtherJsonData['charge_remarks'])           : '';
        $charge_receivable_date   = isset($thirdOtherJsonData['charge_receivable_date'])                ? trim($thirdOtherJsonData['charge_receivable_date'])   : 0;
        $charge_billing_cycle     = isset($thirdOtherJsonData['charge_billing_cycle'])                  ? trim($thirdOtherJsonData['charge_billing_cycle'])     : '';
        $pay_uid                  = isset($thirdOtherJsonData['pay_uid'])                               ? $thirdOtherJsonData['pay_uid']                        : 0;
        $third_key                = isset($thirdOtherJsonData['third_key'])                             ? trim($thirdOtherJsonData['third_key'])                : '';
        $room_id                  = isset($orderInfoArr['room_id'])     && $orderInfoArr['room_id']     ? $orderInfoArr['room_id']                              : '';
        $position_id              = isset($orderInfoArr['position_id']) && $orderInfoArr['position_id'] ? $orderInfoArr['position_id']                          : '';
        
        $charge_received_amount   = str_replace(',', '', $charge_received_amount);
        $charge_amount_owed       = str_replace(',', '', $charge_amount_owed);
        $charge_amount_receivable = str_replace(',', '', $charge_amount_receivable);
        $charge_actual_receivable = str_replace(',', '', $charge_actual_receivable);
        $charge_amount_on_account = str_replace(',', '', $charge_amount_on_account);

        // 如果已收没有值 只是待缴账单   如果欠费还有值那就是有欠费未缴
        $charge_received_amount   = floatval($charge_received_amount);
        $charge_amount_owed       = floatval($charge_amount_owed);
        $charge_amount_receivable = floatval($charge_amount_receivable);
        $charge_actual_receivable = floatval($charge_actual_receivable);
        $charge_amount_on_account = floatval($charge_amount_on_account);
        
        $service_time_arr = explode('-', $this->third_type2_txt);
        if (isset($service_time_arr[0]) && $service_time_arr[0]) {
            $service_start_time = strtotime($service_time_arr[0]);
        } else {
            $service_start_time = 1;
        }
        if (isset($service_time_arr[1]) && $service_time_arr[1]) {
            $service_end_time   = strtotime($service_time_arr[1]);
        } else {
            $service_end_time   = 1;
        }
        $order_type     = $chargeRuleArr['order_type'];
        $order_name     = $chargeRuleArr['order_name'];
        $subject_id     = $chargeRuleArr['subject_id'];
        $project_id     = $chargeRuleArr['project_id'];
        $rule_id        = $chargeRuleArr['rule_id'];
        $not_house_rate = $chargeRuleArr['not_house_rate'];
        $unit_price     = $chargeRuleArr['unit_price'];
        $whereRepeat = [];
        $whereRepeat[] = ['village_id',         '=', $this->village_id];
        if ($room_id) {
            $whereRepeat[] = ['room_id',        '=', $room_id];
        }
        if ($position_id) {
            $whereRepeat[] = ['position_id',        '=', $position_id];
        }
        $whereRepeat[] = ['rule_id',            '=', $rule_id];
        $whereRepeat[] = ['unit_price',         '=', $unit_price];
        $whereRepeat[] = ['service_start_time', '=', $service_start_time];
        $whereRepeat[] = ['service_end_time',   '=', $service_end_time];
        
        if (!$this->dbHouseNewPayOrder) {
            $this->dbHouseNewPayOrder = new HouseNewPayOrder();
        }
        if (!$this->dbHouseNewPayOrderSummary) {
            $this->dbHouseNewPayOrderSummary = new HouseNewPayOrderSummary();
        }
        if (!$this->nowTime) {
            $this->nowTime = time();
        }
        $charge_receivable_date_time = $charge_receivable_date ? strtotime($charge_receivable_date) : '';
        if (!$charge_receivable_date_time) {
            $charge_receivable_date_time = $this->nowTime;
        }
        if (!$this->dbHouseNewOfflinePay) {
            $this->dbHouseNewOfflinePay = new HouseNewOfflinePay();
        }

        $third_import_order = [];
        $third_import_order['cut_table']                = 'house_village_third_import_data';
        $third_import_order['cut_id']                   = $third_id;
        $third_import_order['cut_address']              = $this->third_address;
        $third_import_order['village_id']               = $this->village_id;
        $third_import_order['property_id']              = $this->property_id;
        $third_import_order['subject_id']               = $subject_id;
        $third_import_order['project_id']               = $project_id;
        $third_import_order['rule_id']                  = $rule_id;
        $third_import_order['charge_amount_on_account'] = $charge_amount_on_account;
        $third_import_order['charge_amount_owed']       = $charge_amount_owed;
        $third_import_order['charge_received_amount']   = $charge_received_amount;
        $third_import_order['charge_actual_receivable'] = $charge_actual_receivable;
        $third_import_order['charge_amount_receivable'] = $charge_amount_receivable;
        $third_import_order['charge_billing_cycle']     = $charge_billing_cycle;
        $cut_address_arr = explode('->', $third_import_order['cut_address']);
        if (count($cut_address_arr) > 1 && isset($cut_address_arr[0])) {
            unset($cut_address_arr[0]);
            $cut_address_arr = array_values($cut_address_arr);
            $cut_address = implode('->', $cut_address_arr);
            $third_import_order['cut_address']  = $cut_address;
        }

        $unique_cut_key_source_arr = $third_import_order;

        if ($charge_received_amount>0) {
            // 已收金额
            $is_discard     = 1;  // 是否作废 1未作废 2已作废
            $third_import_order['cut_type']             = 'house_amount_paid';
            $unique_cut_key_source_arr['cut_type']      = $third_import_order['cut_type'];
            $unique_cut_key_source_arr['is_discard']    = $is_discard;
            if ($third_key) {
                $third_key_json_arr = $unique_cut_key_source_arr;
                unset($third_key_json_arr['cut_id']);
                unset($third_key_json_arr['cut_address']);
                unset($third_key_json_arr['property_id']);
                $third_key_json_arr['third_key'] = $third_key;
                $third_key_json = md5(json_encode($third_key_json_arr, JSON_UNESCAPED_UNICODE));
                $third_import_order['third_key']      = $third_key;
                $third_import_order['third_key_json'] = $third_key_json;
            }
            $unique_cut_key_arr = $unique_cut_key_source_arr;
            unset($unique_cut_key_arr['cut_id']);
            $repeatInfo = $this->handleThirdImportOrder($third_import_order, $unique_cut_key_arr);
            
            $is_paid        = 1; // 是否支付   1已支付 2未支付
            $is_online      = 2; // 是否是线上支付  1是 2否
            $pay_type       = 2; // 支付方式 支付方式 1扫码支付  2线下支付 3收款码支付 4线上支付
            $from           = 2; // 是否为收银台订单  1是 2否
            $is_pay_bill    = 1;  // 是否已对账 1未对账 2已对账
            $order_pay_type = 'thirdImportPay';
            $whereRepeatOffline = [];
            $offlinePayName = '三方导入支付';
            $whereRepeatOffline[] = ['property_id', '=', $this->property_id];
            $whereRepeatOffline[] = ['name', '=', $offlinePayName];
            $whereRepeatOffline[] = ['status', '=', 1];
            $offlinePayInfo = $this->dbHouseNewOfflinePay->get_one($whereRepeatOffline);
            if ($offlinePayInfo && !is_array($offlinePayInfo)) {
                $offlinePayInfo = $offlinePayInfo->toArray();
            }
            if (isset($offlinePayInfo) && $offlinePayInfo['id']) {
                $offline_pay_type = $offlinePayInfo['id'];
            } else {
                $offlinePayArr = [
                    'property_id'=> $this->property_id,
                    'name'       => $offlinePayName,
                    'status'     => 1,
                    'add_time'   => $this->nowTime
                ];
                $offline_pay_type = $this->dbHouseNewOfflinePay->addOne($offlinePayArr);
            }
            if ($pay_uid) {
                $order_no = build_real_orderid($pay_uid);
            } else {
                $order_no = build_real_orderid($room_id);
            }

            $total_money  = round($charge_amount_receivable - $charge_amount_owed,2);// 应支付金额 = 实际应收金额 - 欠费金额
            $modify_money = $charge_actual_receivable; // 修改后的金额 = 实际应收
            $pay_money    = $charge_received_amount;   // 修改后的金额 = 已收金额
            // todo 已收金额  添加已缴账单
            $whereRepeatPaid = $whereRepeat;
            
            $paidOrder   = $orderInfoArr;// 已缴账单
            $paidOrder['order_type']         = $order_type;
            $paidOrder['order_name']         = $order_name;
            $paidOrder['total_money']        = $total_money;
            $paidOrder['modify_money']       = $modify_money;
            $paidOrder['pay_money']          = $pay_money;
            $paidOrder['is_paid']            = $is_paid;
            $paidOrder['is_discard']         = $is_discard;
            $paidOrder['pay_type']           = $pay_type;
            $paidOrder['offline_pay_type']   = $offline_pay_type;
            $paidOrder['is_online']          = $is_online;
            $paidOrder['pay_time']           = $charge_receivable_date_time;
            $paidOrder['order_pay_type']     = $order_pay_type;
            $paidOrder['service_start_time'] = $service_start_time;
            $paidOrder['service_end_time']   = $service_end_time;
            $paidOrder['is_auto']            = 0;
            $paidOrder['rule_id']            = $rule_id;
            $paidOrder['project_id']         = $project_id;
            $paidOrder['unit_price']         = $unit_price;
            $paidOrder['from']               = 2;
            $paidOrder['remark']             = $charge_remarks;
            $paidOrder['not_house_rate']     = $not_house_rate;
            $paidOrder['is_pay_bill']        = $is_pay_bill;
            $order_id = 0;
            if  (isset($repeatInfo['order_id']) && $repeatInfo['order_id']) {
                $order_id = $repeatInfo['order_id'];
                $whereRepeatPaid = [];
                $whereRepeatPaid[] = ['order_id', '=', $order_id];
            } else {
                $whereRepeatPaid[] = ['total_money',  '=', $paidOrder['total_money']];
                $whereRepeatPaid[] = ['modify_money', '=', $paidOrder['modify_money']];
                $whereRepeatPaid[] = ['is_paid',      '=', $is_paid];
                $whereRepeatPaid[] = ['is_discard',   '=', $is_discard];
                $payOrderInfo = $this->dbHouseNewPayOrder->get_one($whereRepeatPaid);
                if ($payOrderInfo && !is_array($payOrderInfo)) {
                    $payOrderInfo = $payOrderInfo->toArray();
                }
                if (isset($payOrderInfo['order_id']) && $payOrderInfo['order_id']) {
                    $order_id = $payOrderInfo['order_id'];
                }
            }
            $is_save = false;
            if ($order_id > 0) {
                $save = $this->dbHouseNewPayOrder->saveOne($whereRepeatPaid, $paidOrder);
                $is_save  = true;
            } else {
                $paidOrder['add_time'] = $this->nowTime;
                $order_id = $this->dbHouseNewPayOrder->addOne($paidOrder);
            }
            if (!$order_id) {
                $whereUpdate = [];
                $whereUpdate[] = ['id','=', $third_id];
                $handle_err_json = json_encode(['line'=> __LINE__, 'paidOrder' => $paidOrder, 'third_id' => $third_id, 'thirdOtherJsonData' => $thirdOtherJsonData], JSON_UNESCAPED_UNICODE);
                $updateData = [
                    'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                    'handle_msg'      => '收费订单添加失败',
                    'handle_err_json' => $handle_err_json,
                    'update_time'     => $this->nowTime,
                ];
                $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
                $this->filterRecordErr('收费订单添加失败');
                return false;
            }
            $third_import_order['order_id']     = $order_id;
            $this->handleThirdImportOrder($third_import_order, $unique_cut_key_arr);

            // todo 添加父级
            $houseNewPayOrderSummary = [
                'property_id'      => $this->property_id,
                'village_id'       => $this->village_id,
                'uid'              => isset($orderInfoArr['uid'])         && $orderInfoArr['uid']         ? $orderInfoArr['uid']         : '',
                'pay_uid'          => $pay_uid,
                'pigcms_id'        => isset($orderInfoArr['pigcms_id'])   && $orderInfoArr['pigcms_id']   ? $orderInfoArr['pigcms_id']   : '',
                'pay_bind_id'      => isset($orderInfoArr['pay_bind_id']) && $orderInfoArr['pay_bind_id'] ? $orderInfoArr['pay_bind_id'] : '',
                'room_id'          => $room_id,
                'position_id'      => isset($orderInfoArr['position_id']) && $orderInfoArr['position_id'] ? $orderInfoArr['position_id'] : '',
                'total_money'      => $charge_amount_owed,
                'pay_money'        => $charge_amount_owed,
                'is_paid'          => $is_paid,
                'pay_time'         => $charge_receivable_date_time,
                'is_online'        => $is_online,
                'pay_type'         => $pay_type,
                'offline_pay_type' => isset($offline_pay_type) && $offline_pay_type ? $offline_pay_type : 0,
                'order_pay_type'   => $order_pay_type,
                'remark'           => $charge_remarks,
                'from'             => $from,
                'is_pay_bill'      => $is_pay_bill,
            ];
            if (!$is_save || !isset($payOrderInfo['summary_id']) || !$payOrderInfo['summary_id']) {
                $whereRepeatSummary = [];
                $whereRepeatSummary[] = ['property_id',      '=', $houseNewPayOrderSummary['property_id']];
                $whereRepeatSummary[] = ['village_id',       '=', $houseNewPayOrderSummary['village_id']];
                $whereRepeatSummary[] = ['uid',              '=', $houseNewPayOrderSummary['uid']];
                $whereRepeatSummary[] = ['pay_uid',          '=', $houseNewPayOrderSummary['pay_uid']];
                $whereRepeatSummary[] = ['pigcms_id',        '=', $houseNewPayOrderSummary['pigcms_id']];
                $whereRepeatSummary[] = ['pay_bind_id',      '=', $houseNewPayOrderSummary['pay_bind_id']];
                $whereRepeatSummary[] = ['room_id',          '=', $houseNewPayOrderSummary['room_id']];
                $whereRepeatSummary[] = ['position_id',      '=', $houseNewPayOrderSummary['position_id']];
                $whereRepeatSummary[] = ['total_money',      '=', $houseNewPayOrderSummary['total_money']];
                $whereRepeatSummary[] = ['pay_money',        '=', $houseNewPayOrderSummary['pay_money']];
                $whereRepeatSummary[] = ['is_paid',          '=', $houseNewPayOrderSummary['is_paid']];
                $whereRepeatSummary[] = ['is_online',        '=', $houseNewPayOrderSummary['is_online']];
                $whereRepeatSummary[] = ['pay_type',         '=', $houseNewPayOrderSummary['pay_type']];
                $whereRepeatSummary[] = ['offline_pay_type', '=', $houseNewPayOrderSummary['offline_pay_type']];
                $whereRepeatSummary[] = ['from',             '=', $houseNewPayOrderSummary['from']];
                $whereRepeatSummary[] = ['is_pay_bill',      '=', $houseNewPayOrderSummary['is_pay_bill']];
                $summaryInfo = $this->dbHouseNewPayOrderSummary->getOne($whereRepeatSummary);
                if ($summaryInfo && !is_array($summaryInfo)) {
                    $summaryInfo = $summaryInfo->toArray();
                }
                if (isset($summaryInfo['order_no']) && $summaryInfo['order_no']) {
                    $order_no = $summaryInfo['order_no'];
                }
                if (isset($summaryInfo['summary_id']) && $summaryInfo['summary_id']) {
                    $summary_id = $summaryInfo['summary_id'];
                    $this->dbHouseNewPayOrderSummary->saveOne($whereRepeatSummary, $houseNewPayOrderSummary);
                } else {
                    $houseNewPayOrderSummary['order_no'] = $order_no;
                    $houseNewPayOrderSummary['add_time'] = time();
                    $summary_id = $this->dbHouseNewPayOrderSummary->addOne($houseNewPayOrderSummary);
                }
                if (!$summary_id) {
                    $whereUpdate = [];
                    $whereUpdate[] = ['id','=', $third_id];
                    $handle_err_json = json_encode(['line'=> __LINE__, 'houseNewPayOrderSummary' => $houseNewPayOrderSummary,'summaryInfo' => $summaryInfo, 'third_id' => $third_id, 'thirdOtherJsonData' => $thirdOtherJsonData], JSON_UNESCAPED_UNICODE);
                    $updateData = [
                        'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                        'handle_msg'      => '收费父订单添加失败',
                        'handle_err_json' => $handle_err_json,
                        'update_time'     => $this->nowTime,
                    ];
                    $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
                    $this->filterRecordErr('收费父订单添加失败');
                    return false;
                }
                $whereRepeatPaid = [];
                $whereRepeatPaid[] = ['order_id', '=', $order_id];
                $paidOrder = [
                    'summary_id' => $summary_id,
                    'order_no'   => $order_no,
                ];
                $this->dbHouseNewPayOrder->saveOne($whereRepeatPaid, $paidOrder);
            } else {
                $summary_id = $payOrderInfo['summary_id'];
                $whereRepeatSummary = [];
                $whereRepeatSummary[] = ['summary_id', '=', $summary_id];
                $this->dbHouseNewPayOrderSummary->saveOne($whereRepeatSummary, $houseNewPayOrderSummary);
                $order_no   = $payOrderInfo['order_no'];
            }
            // 处理写入plat_order表
            if (!$this->dbPlatOrder) {
                $this->dbPlatOrder = new PlatOrder();
            }
            $platOrderArr = [
                'orderid'       => $order_no,
                'business_type' => 'house_village_pay',
                'business_id'   => $summary_id,
                'uid'           => isset($orderInfoArr['uid'])         && $orderInfoArr['uid']         ? $orderInfoArr['uid']         : '',
                'total_money'   => $total_money,
                'pay_time'      => $charge_receivable_date_time,
                'third_id'      => $third_id,
                'is_own'        => 0,
                'paid'          => 1,
            ];
            $whereRepeatPlatOrder = [];
            $whereRepeatPlatOrder[] = ['business_type', '=', $platOrderArr['business_type']];
            $whereRepeatPlatOrder[] = ['business_id',   '=', $summary_id];
            $platOrderInfo = $this->dbPlatOrder->get_one($whereRepeatPlatOrder);
            if ($platOrderInfo && !is_array($platOrderInfo)) {
                $platOrderInfo = $platOrderInfo->toArray();
            }
            if ($platOrderInfo && isset($platOrderInfo['order_id']) && $platOrderInfo['order_id']) {
                $plat_order_id = $platOrderInfo['order_id'];
            } else {
                $plat_order_id = $this->dbPlatOrder->add_order($platOrderArr);
            }
            if (!$plat_order_id) {
                $whereUpdate = [];
                $whereUpdate[] = ['id','=', $third_id];
                $handle_err_json = json_encode(['line'=> __LINE__, 'platOrderArr' => $platOrderArr,'platOrderInfo' => $platOrderInfo, 'third_id' => $third_id, 'thirdOtherJsonData' => $thirdOtherJsonData], JSON_UNESCAPED_UNICODE);
                $updateData = [
                    'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                    'handle_msg'      => '收费关联表失败',
                    'handle_err_json' => $handle_err_json,
                    'update_time'     => $this->nowTime,
                ];
                $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
                $this->filterRecordErr('收费关联表失败');
                return false;
            }
        }
        if ($charge_amount_owed>0) {
            // 欠费金额
            $third_import_order['cut_type']          = 'house_amount_to_be_paid';
            $is_discard     = 1;  // 是否作废 1未作废 2已作废
            $unique_cut_key_source_arr['cut_type']   = $third_import_order['cut_type'];
            $unique_cut_key_source_arr['is_discard'] = $is_discard;
            if ($third_key) {
                $third_key_json_arr = $unique_cut_key_source_arr;
                unset($third_key_json_arr['cut_id']);
                unset($third_key_json_arr['cut_address']);
                unset($third_key_json_arr['property_id']);
                $third_key_json_arr['third_key']  = $third_key;
                $third_key_json = md5(json_encode($third_key_json_arr, JSON_UNESCAPED_UNICODE));
                $third_import_order['third_key']      = $third_key;
                $third_import_order['third_key_json'] = $third_key_json;
            }
            if (isset($third_import_order['order_id'])) {
                unset($third_import_order['order_id']);
            }
            $unique_cut_key_arr = $unique_cut_key_source_arr;
            unset($unique_cut_key_arr['cut_id']);
            $repeatInfo = $this->handleThirdImportOrder($third_import_order, $unique_cut_key_arr);
            // todo 欠费金额  添加待缴账单
            $is_paid        = 2;// 是否支付   1已支付 2未支付
            $UnpaidOrder   = $orderInfoArr;// 已缴账单
            $whereRepeatUnpaid = $whereRepeat;
            $UnpaidOrder['order_type']         = $order_type;
            $UnpaidOrder['order_name']         = $order_name;
            $UnpaidOrder['total_money']        = $charge_amount_owed;
            $UnpaidOrder['modify_money']       = $charge_amount_owed;
            $UnpaidOrder['is_paid']            = $is_paid;
            $UnpaidOrder['is_discard']         = $is_discard;
            $UnpaidOrder['service_start_time'] = $service_start_time;
            $UnpaidOrder['service_end_time']   = $service_end_time;
            $UnpaidOrder['is_auto']            = 0;
            $UnpaidOrder['rule_id']            = $rule_id;
            $UnpaidOrder['project_id']         = $project_id;
            $UnpaidOrder['unit_price']         = $unit_price;
            $UnpaidOrder['from']               = 2;
            $UnpaidOrder['remark']             = $charge_remarks;
            $UnpaidOrder['not_house_rate']     = $not_house_rate;
            $order_id = 0;
            if  (isset($repeatInfo['order_id']) && $repeatInfo['order_id']) {
                $order_id = $repeatInfo['order_id'];
                $whereRepeatUnpaid = [];
                $whereRepeatUnpaid[] = ['order_id', '=', $order_id];
            } else {
                $whereRepeatUnpaid[] = ['total_money',  '=', $UnpaidOrder['total_money']];
                $whereRepeatUnpaid[] = ['modify_money', '=', $UnpaidOrder['modify_money']];
                $whereRepeatUnpaid[] = ['is_discard',   '=', $is_discard];
                $payOrderInfo = $this->dbHouseNewPayOrder->get_one($whereRepeatUnpaid);
                if ($payOrderInfo && !is_array($payOrderInfo)) {
                    $payOrderInfo = $payOrderInfo->toArray();
                }
                if (isset($payOrderInfo['order_id']) && $payOrderInfo['order_id']) {
                    $order_id = $payOrderInfo['order_id'];
                }
            }
            if ($order_id > 0) {
                $UnpaidOrder['update_time'] = $this->nowTime;
                $save = $this->dbHouseNewPayOrder->saveOne($whereRepeatUnpaid, $UnpaidOrder);
            } else {
                $UnpaidOrder['add_time'] = $this->nowTime;
                $order_id = $this->dbHouseNewPayOrder->addOne($UnpaidOrder);
            }
            if (!$order_id) {
                $whereUpdate = [];
                $whereUpdate[] = ['id','=', $third_id];
                $handle_err_json = json_encode(['line'=> __LINE__, 'UnpaidOrder' => $UnpaidOrder, 'third_id' => $third_id, 'thirdOtherJsonData' => $thirdOtherJsonData], JSON_UNESCAPED_UNICODE);
                $updateData = [
                    'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                    'handle_msg'      => '收费订单添加失败',
                    'handle_err_json' => $handle_err_json,
                    'update_time'     => $this->nowTime,
                ];
                $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
                $this->filterRecordErr('收费订单添加失败');
                return false;
            }
            $third_import_order['order_id']     = $order_id;
            $this->handleThirdImportOrder($third_import_order, $unique_cut_key_arr);
        }
    }

    /**
     * 车位处理完毕记录关联关系
     * @param $third_import_order
     * @param $unique_cut_key_arr
     * @return bool
     */
    protected function handleThirdImportOrder($third_import_order, $unique_cut_key_arr) {
        // 区分唯一值
        $unique_cut_key = md5(json_encode($unique_cut_key_arr, JSON_UNESCAPED_UNICODE));
        $third_import_order['unique_cut_key']                = $unique_cut_key;

        if (!$this->nowTime) {
            $this->nowTime = time();
        }
        if (!$this->dbHouseVillageThirdImportOrder) {
            $this->dbHouseVillageThirdImportOrder = new HouseVillageThirdImportOrder();
        }
        
        if (isset($third_import_park['third_key_json']) && $third_import_park['third_key_json']) {
            $whereUnique = [];
            $whereUnique[] = ['third_key_json', '=', $third_import_park['third_key_json']];
            $repeatInfo = $this->dbHouseVillageThirdImportOrder->getOne($whereUnique, true, 'bind_id DESC');
            if ($repeatInfo && !is_array($repeatInfo)) {
                $repeatInfo = $repeatInfo->toArray();
            }
        }
        
        if (!isset($repeatInfo) || empty($repeatInfo) || !isset($repeatInfo['bind_id'])) {
            $whereUnique = [];
            $whereUnique[] = ['unique_cut_key', '=', $unique_cut_key];
            $repeatInfo = $this->dbHouseVillageThirdImportOrder->getOne($whereUnique, true, 'bind_id DESC');
            if ($repeatInfo && !is_array($repeatInfo)) {
                $repeatInfo = $repeatInfo->toArray();
            }
        }
        
        if (!$repeatInfo) {
            $repeatInfo = [];
        }
        if (isset($repeatInfo['bind_id'])) {
            $third_import_order['update_time'] = $this->nowTime;
            $this->dbHouseVillageThirdImportOrder->updateThis($whereUnique, $third_import_order);
        } else {
            $third_import_order['add_time'] = $this->nowTime;
            $this->dbHouseVillageThirdImportOrder->add($third_import_order);
        }
        return $repeatInfo;
    }

    /**
     * 处理收费科目-收费项目-收费标准
     * @param $third_import_data
     * @param $thirdOtherJsonData
     * @param $id
     * @return array|false
     */
    protected function handleVillageChargeRule($third_import_data, $thirdOtherJsonData, $id) {
        if (!$this->dbHouseVillage) {
            $this->dbHouseVillage = new HouseVillage();
        }
        $charge_discount = isset($thirdOtherJsonData['charge_discount']) ? floatval($thirdOtherJsonData['charge_discount']) : 0;
        if (!$this->property_id) {
            $villageInfo = $this->dbHouseVillage->getOne($this->village_id,'village_id, property_id');
            if ($villageInfo && !is_array($villageInfo)) {
                $villageInfo = $villageInfo->toArray();
            }
            $this->property_id = $villageInfo['property_id'];
        }
        // 添加或者获取物业层 收费科目
        if (strstr($this->third_unit_name, '物业管理费')) {
            $charge_type = 'property';
            $img         = "/static/community/house_new/wuyefei.png";
        } else {
            $charge_type = 'other';
            $img         = "/static/community/house_new/qita.png";
        }
        $status = 1;//状态 1:开启 2：关闭 4：删除
        $houseNewChargeNumberData = [
            'property_id'        => $this->property_id,
            'charge_type'        => $charge_type,
            'charge_number_name' => $this->third_unit_name,
            'status'             => $status,
        ];
        $whereRepeat = [];
        $whereRepeat[] = ['property_id', '=', $this->property_id];
        $whereRepeat[] = ['charge_type', '=', $charge_type];
        $whereRepeat[] = ['charge_number_name', '=', $this->third_unit_name];
        $whereRepeat[] = ['status', '=', $status];
        if (!$this->dbHouseNewChargeNumber) {
            $this->dbHouseNewChargeNumber = new HouseNewChargeNumber();
        }
        if (!$this->nowTime) {
            $this->nowTime = time();
        }
        // 获取或者添加收费科目
        $houseNewChargeNumberInfo = $this->dbHouseNewChargeNumber->get_one($whereRepeat);
        if ($houseNewChargeNumberInfo && !is_array($houseNewChargeNumberInfo)) {
            $houseNewChargeNumberInfo = $houseNewChargeNumberInfo->toArray();
        }
        if ($houseNewChargeNumberInfo && isset($houseNewChargeNumberInfo['id'])) {
            $subject_id = $houseNewChargeNumberInfo['id'];
        } else {
            $houseNewChargeNumberData['add_time'] = $this->nowTime;
            $subject_id = $this->dbHouseNewChargeNumber->addOne($houseNewChargeNumberData);
        }
        if (!$subject_id) {
            $whereUpdate = [];
            $whereUpdate[] = ['id','=', $id];
            $handle_err_json = json_encode(['line'=> __LINE__, 'houseNewChargeNumberData' => $houseNewChargeNumberData, 'third_id' => $id, 'third_import_data' => $third_import_data], JSON_UNESCAPED_UNICODE);
            $updateData = [
                'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                'handle_msg'      => '收费科目添加失败',
                'handle_err_json' => $handle_err_json,
                'update_time'     => $this->nowTime,
            ];
            $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
            $this->filterRecordErr('小区对应物业收费科目添加失败');
            return false;
        }
        $type = 1;
        $houseNewChargeProjectData = [
            'village_id' => $this->village_id,
            'subject_id' => $subject_id,
            'name'       => $this->third_unit_name,
            'img'        => $img,
            'type'       => $type, // 收费模式 1:一次性费用 2：周期性费用
            'status'     => $status,
        ];
        $whereRepeatProject = [];
        $whereRepeatProject[] = ['village_id', '=', $this->village_id];
        $whereRepeatProject[] = ['subject_id', '=', $subject_id];
        $whereRepeatProject[] = ['name', '=', $this->third_unit_name];
        $whereRepeatProject[] = ['type', '=', $type];
        $whereRepeatProject[] = ['status', '=', $status];
        if (!$this->dbHouseNewChargeProject) {
            $this->dbHouseNewChargeProject = new HouseNewChargeProject();
        }
        // 获取或者添加收费项目
        $houseNewChargeProjectInfo = $this->dbHouseNewChargeProject->getOne($whereRepeatProject);
        if ($houseNewChargeProjectInfo && isset($houseNewChargeProjectInfo['id'])) {
            $project_id = $houseNewChargeProjectInfo['id'];
        } else {
            $houseNewChargeProjectData['add_time'] = $this->nowTime;
            $project_id = $this->dbHouseNewChargeProject->addFind($houseNewChargeProjectData);
        }
        if (!$project_id) {
            $whereUpdate = [];
            $whereUpdate[] = ['id','=', $id];
            $handle_err_json = json_encode(['line'=> __LINE__, 'houseNewChargeProjectData' => $houseNewChargeProjectData, 'third_id' => $id, 'third_import_data' => $third_import_data], JSON_UNESCAPED_UNICODE);
            $updateData = [
                'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                'handle_msg'      => '收费项目添加失败',
                'handle_err_json' => $handle_err_json,
                'update_time'     => $this->nowTime,
            ];
            $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
            $this->filterRecordErr('收费项目添加失败');
            return false;
        }
        if ($charge_discount <= 0 || $charge_discount >= 0) {
            $not_house_rate = 100;
        } else {
            $not_house_rate = round_number(100 * $charge_discount);
        }
        $charge_time_arr = explode('-', $this->third_type2_txt);
        if (isset($charge_time_arr[0]) && $charge_time_arr[0]) {
            $charge_valid_time = strtotime($charge_time_arr[0]);
        } else {
            $charge_valid_time = $this->nowTime;
        }
        $charge_valid_type = 2; // 1:年月日 2：年月 3：年
        $charge_amount_receivable = isset($thirdOtherJsonData['charge_amount_receivable']) ? $thirdOtherJsonData['charge_amount_receivable'] : 0;
        $charge_amount_receivable = str_replace(',', '', $charge_amount_receivable);
        $charge_amount_receivable = floatval($charge_amount_receivable);
        if ($this->third_unit_price && (floatval($this->third_main_value2)>0 || floatval($this->third_main_value3)>0)) {
            $fees_type           = 2; // 计费模式 1:固定费用 2：单价计量单位
            if (floatval($this->third_main_value2)>0) {
                $unit_gage       = $this->third_main_value2;
            } else {
                $unit_gage       = $this->third_main_value3;
            }
            $unit_price          = $this->third_unit_price;
            $charge_price        = $this->third_unit_price;
            $cyclicity_set       = 0;
            $bill_create_set     = 0;
            $bill_arrears_set    = 0;
            $bill_type           = 1;
            $is_prepaid          = 2;
        } else {
            $fees_type           = 1;
            $unit_gage           = '';
            $cyclicity_set       = 0;
            $bill_create_set     = 0;
            $bill_arrears_set    = 0;
            $bill_type           = 1;
            $is_prepaid          = 2;
            $unit_price          = $charge_amount_receivable;
            $charge_price        = $charge_amount_receivable;
            
        }
        if (!$unit_price && !$charge_price) {
            $whereUpdate = [];
            $whereUpdate[] = ['id','=', $id];
            $handle_err_json = json_encode(['line'=> __LINE__, 'third_id' => $id, 'third_import_data' => $third_import_data, 'thirdOtherJsonData' => $thirdOtherJsonData], JSON_UNESCAPED_UNICODE);
            $updateData = [
                'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                'handle_msg'      => '缺少费用或者单价',
                'handle_err_json' => $handle_err_json,
                'update_time'     => $this->nowTime,
            ];
            $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
            $this->filterRecordErr('缺少费用或者单价');
            return false;
        }
        $houseNewChargeRuleData = [
            'village_id'        => $this->village_id,
            'subject_id'        => $subject_id,
            'charge_project_id' => $project_id,
            'charge_name'       => $this->third_unit_name,
            'unit_price'        => $unit_price,
            'not_house_rate'    => $not_house_rate,
            'charge_valid_time' => $charge_valid_time,
            'charge_valid_type' => $charge_valid_type,
            'fees_type'         => $fees_type,
            'unit_gage'         => $unit_gage,
            'charge_price'      => $charge_price,
            'cyclicity_set'     => $cyclicity_set,
            'bill_create_set'   => $bill_create_set,
            'bill_arrears_set'  => $bill_arrears_set,
            'bill_type'         => $bill_type,
            'is_prepaid'        => $is_prepaid,
            'status'            => $status,
        ];
        $whereRepeatRule = [];
        $whereRepeatRule[] = ['village_id',        '=', $houseNewChargeRuleData['village_id']];
        $whereRepeatRule[] = ['subject_id',        '=', $houseNewChargeRuleData['subject_id']];
        $whereRepeatRule[] = ['charge_project_id', '=', $houseNewChargeRuleData['charge_project_id']];
        $whereRepeatRule[] = ['charge_name',       '=', $houseNewChargeRuleData['charge_name']];
        $whereRepeatRule[] = ['unit_price',        '=', $houseNewChargeRuleData['unit_price']];
        $whereRepeatRule[] = ['not_house_rate',    '=', $houseNewChargeRuleData['not_house_rate']];
        $whereRepeatRule[] = ['fees_type',         '=', $houseNewChargeRuleData['fees_type']];
        $whereRepeatRule[] = ['charge_price',      '=', $houseNewChargeRuleData['charge_price']];
        $whereRepeatRule[] = ['cyclicity_set',     '=', $houseNewChargeRuleData['cyclicity_set']];
        $whereRepeatRule[] = ['bill_create_set',   '=', $houseNewChargeRuleData['bill_create_set']];
        $whereRepeatRule[] = ['bill_arrears_set',  '=', $houseNewChargeRuleData['bill_arrears_set']];
        $whereRepeatRule[] = ['bill_type',         '=', $houseNewChargeRuleData['bill_type']];
        $whereRepeatRule[] = ['is_prepaid',        '=', $houseNewChargeRuleData['is_prepaid']];
        $whereRepeatRule[] = ['status',            '=', $houseNewChargeRuleData['status']];
        
        if (!$this->dbHouseNewChargeRule) {
             $this->dbHouseNewChargeRule = new HouseNewChargeRule();
        }
        $ruleInfo = $this->dbHouseNewChargeRule->getOne($whereRepeatRule);
        if ($ruleInfo && !is_array($ruleInfo)) {
            $ruleInfo = $ruleInfo->toArray();
        }
        if ($ruleInfo && isset($ruleInfo['id'])) {
            $rule_id = $ruleInfo['id'];
        } else {
            $houseNewChargeRuleData['add_time'] = $this->nowTime;
            $rule_id = $this->dbHouseNewChargeRule->addFind($houseNewChargeRuleData);
        }
        if (!$rule_id) {
            $whereUpdate = [];
            $whereUpdate[] = ['id','=', $id];
            $handle_err_json = json_encode(['line'=> __LINE__, 'houseNewChargeRuleData' => $houseNewChargeRuleData, 'third_id' => $id, 'third_import_data' => $third_import_data, 'thirdOtherJsonData' => $thirdOtherJsonData], JSON_UNESCAPED_UNICODE);
            $updateData = [
                'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                'handle_msg'      => '收费标准添加失败',
                'handle_err_json' => $handle_err_json,
                'update_time'     => $this->nowTime,
            ];
            $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
            $this->filterRecordErr('收费标准添加失败');
            return false;
        }
        $houseNewChargeRuleArr = [
            'property_id'    => $this->property_id,
            'village_id'     => $this->village_id,
            'subject_id'     => $subject_id,
            'project_id'     => $project_id,
            'rule_id'        => $rule_id,
            'not_house_rate' => $not_house_rate,
            'order_type'     => $charge_type,
            'order_name'     => $this->third_unit_name,
            'unit_price'     => $unit_price,
        ];
        return $houseNewChargeRuleArr;
    }
    
    /**
     * 业主导入
     * @param $param
     * @return bool
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function UserExecute($param) {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);
        $this->nowTime = time();
        //1、接收参数 、绑定参数
        //2、按照映射关系写入库
        //3、然后使用 Redis 反馈实时导入进度
        //4、记录一下导入数据变更记录和 整理导入错误的文件

        $inputFileName             = $param['inputFileName'];
        $nowVillageInfo            = $param['nowVillageInfo'];
        $worksheetName             = $param['worksheetName'];
        
        $this->village_id                = $nowVillageInfo['village_id'];
        $orderGroupId              = isset($param['orderGroupId']) ? $param['orderGroupId'] : $this->village_id;

        $this->order_group_id      = $orderGroupId;
        
        $colAndFiledArr = array_column($this->userColAndFiledArr,'value','key');
        $thirdOtherJsonArr = array_column($this->userThirdOtherJsonArr,'key','value');
        
        //使用Redis缓存 进度 
        $processCacheKey = 'thirdimport:thirdUserInfo:process:'.$orderGroupId;
        $totalCacheKey   = 'thirdimport:thirdUserInfo:total:'.$orderGroupId;
        $surplusCacheKey = 'thirdimport:thirdUserInfo:surplus:'.$orderGroupId;
        $excelCacheKey   = 'thirdimport:thirdUserInfo:excel:'.$orderGroupId;

        if (empty(Config::get('cache.stores.redis'))){
            fdump_api(['msg' => "请先配置 Redis 缓存 " . app()->getRootPath() . 'config/cache.php'],'$importExcelErrUserLog',1);
        }

        try {
            $this->redisCache->set($processCacheKey,0,3600);
        }catch (\Exception $e){
            //TODO 应该直接通过 队列 压入对应的业务日志里
            fdump_api(['msg' => '设置redis缓存错误', 'eMessage'=>$e->getMessage()],'$importExcelErrUserLog',1);
        }
        
//        $startTime = microtime(true);

        $inputFileName = root_path() . '/../'.$inputFileName;
        $fileArr       = explode('.',$inputFileName);
        $fileExt       = strtolower(end($fileArr));
        $inputFileName = str_replace('//', '/', $inputFileName);

        if ($fileExt === 'xls'){
            $inputFileType = "Xls";
        }else if($fileExt === 'xlsx'){
            $inputFileType = "Xlsx";
        } else {
            $inputFileType = "Xls";
        }
        $reader = IOFactory::createReader($inputFileType);

        $spreadsheet        = $reader->load($inputFileName);
        $sheet              = $spreadsheet->getSheetByName($worksheetName);
        if (!$sheet) {
            $sheet          = $spreadsheet->getActiveSheet();
            $worksheetName  = '';
        }
        $highestRow         = $sheet->getHighestRow(); // 总行数（）)
        $highestColumn      = $sheet->getHighestColumn();// 最后有数据的一列，比如 S 列
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);//把列字母转为数字,比如可以得到有 10 列
        
        $errColumnKey       = Coordinate::stringFromColumnIndex($highestColumnIndex + 1);
        $errColumnKeyTip    = Coordinate::stringFromColumnIndex($highestColumnIndex + 2);
        if (!$errColumnKey) {
            $errColumnKey = 'O';
        }

        $this->redisCache->set($totalCacheKey,$highestRow-1,3600); //总行数
        $surplusRow = $highestRow-1;

//        $str = "把列字母转为数字「{$highestColumnIndex}」总行数(有数据)：【{$highestRow}】,最后一列【{$highestColumn}】".PHP_EOL;

        $chunkSize = 1000;
        $chunkFilter = new ChunkReadFilter();
        $reader->setReadFilter($chunkFilter);
        $j = 1;


        $this->dbHouseVillageThirdImportData = new HouseVillageThirdImportData();
        
        $insertErrData = [];
        $excelTableTile = [];
        // 过滤出数字
        $hasNext = true;
        for ($startRow = 2; $startRow <= $highestRow; $startRow += $chunkSize) {
            if (!$hasNext) {
                $startRow = $highestRow;
                $process = $startRow / $highestRow;
                if ($startRow + $chunkSize >=$highestRow ){
                    $process = 0.99;
                }
                $process = round($process, 4);
                if ($process >= 1) {
                    $process = 0.99;
                }
                $this->redisCache->set($processCacheKey,$process * 100,3600); //进度
                break;
            }
            $chunkFilter->setRows($startRow, $chunkSize);
            $spreadsheet = $reader->load($inputFileName);

            if ($worksheetName) {
                $sheetData = $spreadsheet->getSheetByName($worksheetName)->toArray(NULL, true, true, true);
            } else {
                $sheetData = $sheet->toArray(NULL, true, true, true);
            }
            if (count($sheetData) >= $highestRow) {
                $hasNext = false;
            }
            $excelTableTile = $sheetData[1];
            $excelTableTile[$errColumnKey]    = '错误信息';
            $excelTableTile[$errColumnKeyTip] = '错误信息在导入表行数';
            unset($sheetData[1]); //去掉表头
            
            foreach ($sheetData as $dk => $data) {
                $surplusRow = $surplusRow - 1;
                $this->order_group_err = '';
                if (!isset($data[$errColumnKeyTip]) || !$data[$errColumnKeyTip]) {
                    $data[$errColumnKeyTip] = $dk;
                }
                $type_id               = isset($data[$colAndFiledArr['type_id']])       && $data[$colAndFiledArr['type_id']]       ? $data[$colAndFiledArr['type_id']]       : 0;
                $type2_id              = isset($data[$colAndFiledArr['type2_id']])      && $data[$colAndFiledArr['type2_id']]      ? $data[$colAndFiledArr['type2_id']]      : 0;
                $third_address         = isset($data[$colAndFiledArr['third_address']]) && $data[$colAndFiledArr['third_address']] ? $data[$colAndFiledArr['third_address']] : '';
                
                if (!$third_address) {
                    $this->filterRecordErr('缺少必要参数【房号】[G列]');
                    $data[$errColumnKey] = $this->order_group_err;
                    $insertErrData[] = $data;
                    continue;
                }
                if (isset($data['T']) && $data['T']) {
                    $data['T'] = str_replace('.0', '', $data['T']);
                }
                if (isset($data['AC']) && $data['AC']) {
                    $data['AC'] = str_replace('.0', '', $data['AC']);
                }
                
                $thirdOtherJsonData = $data;
                $third_import_data = [
                    'type'          => thirdImportDataConst::THIRD_YWYL_USER_DATA,
                    'type_id'       => $type_id,
                    'type2_id'      => $type2_id,
                    'third_address' => $third_address,
                    'village_id'    => $this->village_id,
                ];

                unset($thirdOtherJsonData[$colAndFiledArr['type_id']], $thirdOtherJsonData[$colAndFiledArr['third_address']]);

                if (isset($data[$colAndFiledArr['third_name']])) {
                    $third_name = $data[$colAndFiledArr['third_name']] ? trim($data[$colAndFiledArr['third_name']]) : '';
                    unset($thirdOtherJsonData[$colAndFiledArr['third_name']]);
                    $third_import_data['third_name'] = $third_name;
                } else {
                    $third_name = '';
                }
                if (isset($data[$colAndFiledArr['third_main_value1']])) {
                    $third_main_value1 = $data[$colAndFiledArr['third_main_value1']] ? trim($data[$colAndFiledArr['third_main_value1']]) : '';
                    unset($thirdOtherJsonData[$colAndFiledArr['third_main_value1']]);
                    $third_import_data['third_main_value1'] = $third_main_value1;
                } else {
                    $third_main_value1 = '';
                }
                if (isset($data[$colAndFiledArr['third_main_value2']])) {
                    $third_main_value2 = $data[$colAndFiledArr['third_main_value2']]     ? trim($data[$colAndFiledArr['third_main_value2']])     : '';
                    unset($thirdOtherJsonData[$colAndFiledArr['third_main_value2']]);
                    $third_import_data['third_main_value2']     = $third_main_value2;
                } else {
                    $third_main_value2 = '';
                }
                if (isset($data[$colAndFiledArr['third_main_value3']])) {
                    $third_main_value3 = $data[$colAndFiledArr['third_main_value3']]     ? trim($data[$colAndFiledArr['third_main_value3']])     : '';
                    unset($thirdOtherJsonData[$colAndFiledArr['third_main_value3']]);
                    $third_import_data['third_main_value3']     = $third_main_value3;
                } else {
                    $third_main_value3 = '';
                }
                
                if (!empty($thirdOtherJsonData)) {
                    foreach ($thirdOtherJsonData as $keyJson => $valJson) {
                        if (isset($thirdOtherJsonArr[$keyJson])) {
                            $thirdOtherJsonData[$thirdOtherJsonArr[$keyJson]] = $valJson ? trim($valJson) : '';
                            unset($thirdOtherJsonData[$keyJson]);
                        }
                    }
                }
                $userOwnerArr = [];
                if (isset($third_import_data['third_name']) && $third_import_data['third_name']) {
                    $userOwnerArr['name'] = $third_import_data['third_name'];
                }
                $member_relation_type = -1;
                $memberRelationTxt = isset($thirdOtherJsonData['member_relation']) && $thirdOtherJsonData['member_relation'] ? trim($thirdOtherJsonData['member_relation']) : '';
                if ($memberRelationTxt) {
                    $member_relation_type = $this->getMemberRelationType($memberRelationTxt);
                    $thirdOtherJsonData['member_relation_type'] = $member_relation_type;
                }
                // 判断整行是不是都是业主数据
                $isRowOwnerMsg = false;
                if ($member_relation_type == thirdImportDataConst::HOUSEHOLDER_RELATIONSHIP_OWNER) {
                    $isRowOwnerMsg = true;
                }
                
                $third_import_data['third_other_json'] = json_encode($thirdOtherJsonData, JSON_UNESCAPED_UNICODE);


                $repeat_key = md5(json_encode($third_import_data, JSON_UNESCAPED_UNICODE));
                $third_import_data['repeat_key']                = $repeat_key;
                
                $third_import_data['add_time']              = $this->nowTime;
                $id = $this->dbHouseVillageThirdImportData->add($third_import_data);
                if (!$id) {
                    $this->filterRecordErr('添加记录导入失败');
                    $data[$errColumnKey] = $this->order_group_err;
                    $insertErrData[] = $data;
                }
                // todo 处理 同步 楼栋单元楼层房屋
                if ($id) {
                    $third_address_arr = explode('->', $third_address);
                    // todo 变更状态
                    if (!isset($third_address_arr[0]) || !$third_address_arr[0]) {
                        $whereUpdate = [];
                        $whereUpdate[] = ['id','=', $id];
                        $handle_err_json = json_encode(['line'=> __LINE__, 'village_id' => $this->village_id, 'third_id' => $id, '$third_address' => $third_address, 'third_address_arr' => $third_address_arr], JSON_UNESCAPED_UNICODE);
                        $updateData = [
                            'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                            'handle_msg'      => '缺少房号',
                            'handle_err_json' => $handle_err_json,
                            'update_time'     => $this->nowTime,
                        ];
                        $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
                        $this->filterRecordErr('必要参数结构异常【房号】[G列]');
                        $data[$errColumnKey] = $this->order_group_err;
                        $insertErrData[] = $data;
                        continue;
                    }

                    if ($third_address_arr[0] && $third_address_arr[0] != $nowVillageInfo['village_name']) {
                        $whereUpdate = [];
                        $whereUpdate[] = ['id','=', $id];
                        $handle_err_json = json_encode(['line'=> __LINE__, 'village_id' => $this->village_id, 'third_id' => $id, '$third_address' => $third_address, 'third_address_arr' => $third_address_arr], JSON_UNESCAPED_UNICODE);
                        $updateData = [
                            'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                            'handle_msg'      => '小区不匹配',
                            'handle_err_json' => $handle_err_json,
                            'update_time'     => $this->nowTime,
                        ];
                        $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
                        $this->filterRecordErr('小区不匹配(地址第一级和当前小区名称不同)【房号】[G列]');
                        $data[$errColumnKey] = $this->order_group_err;
                        $insertErrData[] = $data;
                        continue;
                    }

                    // 去除小区这一层
                    unset($third_address_arr[0]);
                    $third_address_arr = array_values($third_address_arr);
                    $third_address     = implode('->', $third_address_arr);
                    
                    $houseArr = [
                        'village_id'  => $this->village_id,
                        'third_id'    => $id,
                        'cut_address' => $third_address,
                    ];
                    $third_key = $third_address;
                    if (isset($third_key) && $third_key) {
                        $houseArr['third_key']           = $third_key;
                        $thirdOtherJsonData['third_key'] = $third_key;
                    }
                    $this->order_group_err = '';
                    if ($isRowOwnerMsg && isset($thirdOtherJsonData['member_phone']) && $thirdOtherJsonData['member_phone']) {
                        $userOwnerArr['phone'] = $thirdOtherJsonData['member_phone'];
                    }
                    if ($third_main_value1 && $isRowOwnerMsg && (!isset($thirdOtherJsonData['member_sex']) || !$thirdOtherJsonData['member_sex'])) {
                        $thirdOtherJsonData['member_sex'] = $third_main_value1;
                    }
                    
                    $handleExtraUserInfo = $this->handleExtraUserInfo($third_address_arr, $thirdOtherJsonData, $member_relation_type);
                    if ($this->order_group_err) {
                        $data[$errColumnKey] = $this->order_group_err;
                        $insertErrData[] = $data;
                        continue;
                    }
           
                    $otherUserBind       = $handleExtraUserInfo['otherUserBind'];
                    $userBindRelation    = $handleExtraUserInfo['userBindRelation'];
                    // 处理业主信息
                    if ($isRowOwnerMsg && isset($otherUserBind['id_card']) && $otherUserBind['id_card']) {
                        $userOwnerArr['id_card'] = $otherUserBind['id_card'];
                    }
                    if ($isRowOwnerMsg && !isset($userOwnerArr['name']) && isset($otherUserBind['name']) && $otherUserBind['name']) {
                        $userOwnerArr['name'] = $otherUserBind['name'];
                    }
                    if ($isRowOwnerMsg && !isset($userOwnerArr['phone']) && isset($otherUserBind['phone']) && $otherUserBind['phone']) {
                        $userOwnerArr['phone'] = $otherUserBind['phone'];
                    }
                    if ($isRowOwnerMsg && isset($userBindRelation['relation_bind_id']) && $userBindRelation['relation_bind_id']) {
                        $userOwnerArr['relation_bind_id'] = $userBindRelation['relation_bind_id'];
                    }

                    $third_key_json_arr = [
                        'cut_type'   => 'park',
                        'cut_table'  => 'house_village_third_import_data',
                        'third_key'  => $third_key,
                        'village_id' => $this->village_id,
                    ];
                    $park_third_key_json = md5(json_encode($third_key_json_arr, JSON_UNESCAPED_UNICODE));

                    if (isset($userOwnerArr['phone']) && $userOwnerArr['phone']) {
                        $parkPhone = $userOwnerArr['phone'];
                    } else {
                        $parkPhone = '';
                    }
                    $parkBindUser = true;
                    
                    $park = $this->getThirdImportPark($park_third_key_json,$third_key_json_arr, $parkBindUser, $parkPhone);
                    if ($this->order_group_err) {
                        $data[$errColumnKey] = $this->order_group_err;
                        $insertErrData[] = $data;
                        continue;
                    } elseif (isset($park['bind_id']) && $park['bind_id']) {
                        // 车场成功匹配
                        continue;
                    }

                    $userOwnerArr =  $this->handleOwnerInfo($third_address_arr, $id, $third_import_data, $houseArr, $userOwnerArr);
                    if (!$userOwnerArr || empty($userOwnerArr)) {
                        if (!$this->order_group_err) {
                            $this->filterRecordErr('缺少对应房屋');
                        }
                        $data[$errColumnKey] = $this->order_group_err;
                        $insertErrData[] = $data;
                        continue;
                    }
                    if (!$isRowOwnerMsg) {
                        // todo 拓展数据为房间其他身份住户先行添加
                        $otherUserBind['parent_id']  = $userOwnerArr['owner_id'];
                        $otherUserBind['village_id'] = $this->village_id;
                        $otherUserBind['single_id']  = $userOwnerArr['single_id'];
                        $otherUserBind['floor_id']   = $userOwnerArr['floor_id'];
                        $otherUserBind['layer_id']   = $userOwnerArr['layer_id'];
                        $otherUserBind['vacancy_id'] = $userOwnerArr['vacancy_id'];
                        if (isset($userBindRelation['relation_bind_id']) && $userBindRelation['relation_bind_id']) {
                            $otherUserBind['relation_bind_id'] = $userBindRelation['relation_bind_id'];
                        }
                        $userBindArr = $this->handleBindUserInfo($third_address_arr, $id, $otherUserBind);
                        if (!$userBindArr) {
                            if ($this->order_group_err) {
                                $data[$errColumnKey] = $this->order_group_err;
                                $insertErrData[] = $data;
                            }
                            continue;
                        }
                        if (!isset($otherUserBind['relation_bind_id']) || !$otherUserBind['relation_bind_id']) {
                            $pigcms_id = $userBindArr['pigcms_id'];
                            $handleExtraUserInfo = $this->handleExtraUserInfo($third_address_arr, $thirdOtherJsonData, $member_relation_type, $pigcms_id);
                        }
                    } else {
                        if (!isset($userOwnerArr['relation_bind_id']) || !$userOwnerArr['relation_bind_id']) {
                            $pigcms_id = $userOwnerArr['pigcms_id'];
                            $handleExtraUserInfo = $this->handleExtraUserInfo($third_address_arr, $thirdOtherJsonData, $member_relation_type, $pigcms_id);
                        }
                    }

                    if ($this->order_group_err) {
                        $data[$errColumnKey] = $this->order_group_err;
                        $insertErrData[] = $data;
                    }
                }
                
                $this->redisCache->set($surplusCacheKey,$surplusRow,3600); //剩余数

                $surplusProcess = $surplusRow / ($highestRow - 1);
                if ($surplusProcess > 1) {
                    $process = 0;
                } else {
                    $process = 1 - $surplusProcess;
                }
                $process = round($process, 4);
                if ($process >= 1) {
                    $process = 0.99;
                }
                $this->redisCache->set($processCacheKey,$process * 100,3600); //进度
            }

            $process = $startRow / $highestRow;
            if ($startRow + $chunkSize >=$highestRow ){
                $process = 0.99;
            }
            $process = round($process, 4);
            if ($process >= 1) {
                $process = 0.99;
            }
            $this->redisCache->set($processCacheKey,$process * 100,3600); //进度
            
            $j++;
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            $spreadsheet = NULL;
            unset($sheetData);
            $sheetData = NULL;
        }

        if (!empty($insertErrData) && !empty($excelTableTile)) {
            $fileInfo = pathinfo($inputFileName);
            if (isset($fileInfo['filename']) && $fileInfo['filename'] && isset($fileInfo['extension']) && $fileInfo['extension']) {
                $file_name = $fileInfo['filename'] . '-导入错误表.' . $fileInfo['extension'];
            } else {
                $file_name = '业主-导入错误表.xls';
            }
             $excelInfo = $this->saveExcel($insertErrData, $excelTableTile, $errColumnKey, $file_name, $inputFileType);
            if (isset($excelInfo['url']) && $excelInfo['url']) {
                $this->redisCache->set($excelCacheKey,$excelInfo['url'],3600); //错误表格
            }
            sleep(2);
            $this->redisCache->set($processCacheKey,100,3600); //进度
        } else {
            $this->redisCache->set($processCacheKey,100,3600); //进度
        }
        return true;
    }
    
    protected $dbHouseVillageUserBindRelation;

    /**
     * 处理住户其他信息
     * @param $third_address_arr
     * @param $thirdOtherJsonData
     * @param $member_relation_type
     * @param int $relation_bind_id
     * @return array
     */
    protected function handleExtraUserInfo($third_address_arr, $thirdOtherJsonData, $member_relation_type, $relation_bind_id = 0) {
        $userBindRelation = [];
        $otherUserBind    = [];
        $userBindRelation['from_type']  = thirdImportDataConst::THIRD_YWYL_OWNER_EXCEL;
        $userBindRelation['from_txt']   = '三方EXCEL业主信息导入';
        $userBindRelation['village_id'] = $this->village_id;
        if ($relation_bind_id) {
            $userBindRelation['relation_bind_id'] = $relation_bind_id;
        }
        $third_key_json_arr = [
            'from_type'  => $userBindRelation['from_type'],
            'village_id' => $this->village_id,
        ];

        $third_key = isset($thirdOtherJsonData['third_key']) && $thirdOtherJsonData['third_key'] ? trim($thirdOtherJsonData['third_key']) : ''; 
        
        $from_address_arr = $third_address_arr;
        unset($from_address_arr[0]);
        $from_address_arr = array_values($from_address_arr);
        $from_address =  implode('->', $from_address_arr);;
        $userBindRelation['from_address'] = $from_address;
        $userBindRelationRepeatKeyArr = [];
        $userBindRelationRepeatKeyArr['from_address'] = $userBindRelation['from_address'];
        $userBindRelationRepeatKeyArr['from_type']    = $userBindRelation['from_type'];
        $userBindRelationRepeatKeyArr['village_id']   = $this->village_id;

        if (isset($thirdOtherJsonData['member_id']) && $thirdOtherJsonData['member_id']) {
            $userBindRelation['from_id']                = $thirdOtherJsonData['member_id'];
            $userBindRelationRepeatKeyArr['from_id']    = $userBindRelation['from_id'];
            $third_key_json_arr['from_id']              = $userBindRelation['from_id'];
        }
        if (isset($third_import_data['type_id']) && $third_import_data['type_id']) {
            $userBindRelation['from2_id']                = $third_import_data['type_id'];
            $userBindRelationRepeatKeyArr['from2_id']    = $userBindRelation['from2_id'];
            $third_key_json_arr['from2_id']              = $userBindRelation['from_id'];
        }
        if (isset($thirdOtherJsonData['member_name']) && $thirdOtherJsonData['member_name']) {
            $userBindRelation['from_user_name']                = $thirdOtherJsonData['member_name'];
            $userBindRelationRepeatKeyArr['from_user_name']    = $userBindRelation['from_user_name'];
            $third_key_json_arr['from_user_name']              = $userBindRelation['from_user_name'];
            $otherUserBind['name'] = $thirdOtherJsonData['member_name'];
        }
        if (isset($thirdOtherJsonData['member_phone']) && $thirdOtherJsonData['member_phone']) {
            $userBindRelation['from_user_phone']                = $thirdOtherJsonData['member_phone'];
            $userBindRelationRepeatKeyArr['from_user_phone']    = $userBindRelation['from_user_phone'];
            $third_key_json_arr['from_user_phone']              = $userBindRelation['from_user_phone'];
            $otherUserBind['phone'] = $thirdOtherJsonData['member_phone'];
        }
        if (isset($thirdOtherJsonData['member_relation']) && $thirdOtherJsonData['member_relation']) {
            $userBindRelation['owner_relation_txt'] = $thirdOtherJsonData['member_relation'];
        }
        if ($member_relation_type != -1) {
            $userBindRelation['owner_relation']             = $member_relation_type;
            $userBindRelationRepeatKeyArr['owner_relation'] = $member_relation_type;
            $third_key_json_arr['owner_relation']           = $userBindRelation['owner_relation'];
        }
        if (isset($thirdOtherJsonData['member_sex']) && $thirdOtherJsonData['member_sex']) {
            $userBindRelation['sex'] = $this->getMemberSex($thirdOtherJsonData['member_sex']);
        }
        if (isset($thirdOtherJsonData['special_identity']) && $thirdOtherJsonData['special_identity']) {
            $userBindRelation['special_identity'] = $thirdOtherJsonData['special_identity'];
        }
        if (isset($thirdOtherJsonData['member_nation']) && $thirdOtherJsonData['member_nation']) {
            $userBindRelation['nation'] = $thirdOtherJsonData['member_nation'];
        }
        if (isset($thirdOtherJsonData['member_native_place']) && $thirdOtherJsonData['member_native_place']) {
            $userBindRelation['native_place'] = $thirdOtherJsonData['member_native_place'];
        }
        if (isset($thirdOtherJsonData['member_birthday']) && $thirdOtherJsonData['member_birthday']) {
            $userBindRelation['birthday'] = $thirdOtherJsonData['member_birthday'];
        }
        if (isset($thirdOtherJsonData['member_marital_status']) && $thirdOtherJsonData['member_marital_status']) {
            $userBindRelation['marital_status'] = $thirdOtherJsonData['member_marital_status'];
        }
        if (isset($thirdOtherJsonData['member_card_type']) && $thirdOtherJsonData['member_card_type']) {
            $userBindRelation['card_type'] = $this->getCardType($thirdOtherJsonData['member_card_type']);
        }
        if (isset($thirdOtherJsonData['member_card']) && $thirdOtherJsonData['member_card']) {
            $userBindRelation['card'] = $thirdOtherJsonData['member_card'];
            if (isset($userBindRelation['card_type']) && $userBindRelation['card_type'] == thirdImportDataConst::CARD_TYPE_RESIDENT_ID_CARD) {
                $otherUserBind['id_card'] = $userBindRelation['card'];
            }
        }
        if (isset($thirdOtherJsonData['member_education']) && $thirdOtherJsonData['member_education']) {
            $userBindRelation['education_level'] = $thirdOtherJsonData['member_education'];
        }
        if (isset($thirdOtherJsonData['member_nationality']) && $thirdOtherJsonData['member_nationality']) {
            $userBindRelation['nationality'] = $thirdOtherJsonData['member_nationality'];
        }
        if (isset($thirdOtherJsonData['registered_residence']) && $thirdOtherJsonData['registered_residence']) {
            $userBindRelation['registered_residence'] = $thirdOtherJsonData['registered_residence'];
        }
        if (isset($thirdOtherJsonData['member_province']) && $thirdOtherJsonData['member_province']) {
            $userBindRelation['province'] = $thirdOtherJsonData['member_province'];
        }
        if (isset($thirdOtherJsonData['member_city']) && $thirdOtherJsonData['member_city']) {
            $userBindRelation['city'] = $thirdOtherJsonData['member_city'];
        }
        if (isset($thirdOtherJsonData['member_settlement_city']) && $thirdOtherJsonData['member_settlement_city']) {
            $userBindRelation['settlement_city'] = $thirdOtherJsonData['member_settlement_city'];
        }
        if (isset($thirdOtherJsonData['member_resident_type']) && $thirdOtherJsonData['member_resident_type']) {
            $userBindRelation['resident_type'] = $thirdOtherJsonData['member_resident_type'];
        }
        if (isset($thirdOtherJsonData['member_home_address']) && $thirdOtherJsonData['member_home_address']) {
            $userBindRelation['home_address'] = $thirdOtherJsonData['member_home_address'];
        }
        if (isset($thirdOtherJsonData['member_emergency_contact']) && $thirdOtherJsonData['member_emergency_contact']) {
            $userBindRelation['emergency_contact'] = $thirdOtherJsonData['member_emergency_contact'];
        }
        if (isset($thirdOtherJsonData['member_emergency_phone']) && $thirdOtherJsonData['member_emergency_phone']) {
            $userBindRelation['emergency_phone'] = $thirdOtherJsonData['member_emergency_phone'];
        }

        if ($third_key) {
            $userBindRelation['third_key'] = $third_key;
            $third_key_json_arr['third_key'] = $third_key;
            $third_key_json = md5(json_encode($third_key_json_arr, JSON_UNESCAPED_UNICODE));
            $userBindRelation['third_key_json'] = $third_key_json;
        }

        $repeat_key = md5(json_encode($userBindRelationRepeatKeyArr, JSON_UNESCAPED_UNICODE));
        $userBindRelation['repeat_key'] = $repeat_key;

        if (!$this->dbHouseVillageUserBindRelation) {
            $this->dbHouseVillageUserBindRelation = new HouseVillageUserBindRelation();
        }
        if (!$this->dbHouseVillageUserBind) {
            $this->dbHouseVillageUserBind = new HouseVillageUserBind();
        }
        
        if (isset($third_import_park['third_key_json']) && $third_import_park['third_key_json']) {
            $whereRepeat = [];
            $whereRepeat[] = ['third_key_json', '=', $third_import_park['third_key_json']];
            $repeatInfo = $this->dbHouseVillageUserBindRelation->getOne($whereRepeat, true, 'add_time DESC');
            if ($repeatInfo && !is_array($repeatInfo)) {
                $repeatInfo = $repeatInfo->toArray();
            }
        }

        if (!isset($repeatInfo) || empty($repeatInfo) || !isset($repeatInfo['relation_bind_id'])) {
            $whereRepeat = [];
            $whereRepeat[] = ['repeat_key', '=', $repeat_key];
            $repeatInfo = $this->dbHouseVillageUserBindRelation->getOne($whereRepeat, true, 'add_time DESC');
            if ($repeatInfo && !is_array($repeatInfo)) {
                $repeatInfo = $repeatInfo->toArray();
            }
        }
        
        if (!$this->nowTime) {
            $this->nowTime = time();
        }
        if (isset($repeatInfo['relation_bind_id']) && $repeatInfo['relation_bind_id']) {
            $whereUserBind = [];
            $whereUserBind[] = ['pigcms_id', '=', $repeatInfo['relation_bind_id']];
            $userBind = $this->dbHouseVillageUserBind->getOne($whereUserBind);
            if ($userBind && !is_array($userBind)) {
                $userBind = $userBind->toArray();
            }
            if (isset($userBind['pigcms_id']) && $userBind['pigcms_id'] && $userBind['status'] != 1) {
                $repeatInfo = [];
            }
        }
        
        if ($repeatInfo && isset($repeatInfo['relation_bind_id'])) {
            $userBindRelation['update_time'] = $this->nowTime;
            $this->dbHouseVillageUserBindRelation->updateThis($whereRepeat, $userBindRelation);
            $userBindRelation['relation_bind_id'] = $repeatInfo['relation_bind_id'];
        } else {
            $userBindRelation['add_time'] = $this->nowTime;
            $add = $this->dbHouseVillageUserBindRelation->add($userBindRelation);
            if (!$add) {
                $this->filterRecordErr('添加关联关系失败');
            }
        }
        $otherUserBind = $this->getUserBindType($member_relation_type, $otherUserBind);
        if (isset($userBindRelation['relation_bind_id']) && $userBindRelation['relation_bind_id'] == $repeat_key) {
            unset($userBindRelation['relation_bind_id']);
        }
        
        return [
            'userBindRelation' => $userBindRelation,
            'otherUserBind'    => $otherUserBind,
        ];
    }
    
    protected $dbHouseVillageUserBind;
    /**
     * 处理房间住户（非业主）信息
     * @param $third_address_arr
     * @param $id
     * @param $userBindArr
     * @return false|array
     */
    protected function handleBindUserInfo($third_address_arr, $id, $userBindArr) {
        $single_id  = isset($userBindArr['single_id'])    && $userBindArr['single_id']  ? $userBindArr['single_id']   : 0;
        $floor_id   = isset($userBindArr['floor_id'])     && $userBindArr['floor_id']   ? $userBindArr['floor_id']    : 0;
        $layer_id   = isset($userBindArr['layer_id'])     && $userBindArr['layer_id']   ? $userBindArr['layer_id']    : 0;
        $vacancy_id = isset($userBindArr['vacancy_id'])   && $userBindArr['vacancy_id'] ? $userBindArr['vacancy_id']  : 0;
        $village_id = isset($userBindArr['village_id'])   && $userBindArr['village_id'] ? $userBindArr['village_id']  : 0;
        $parent_id  = isset($userBindArr['parent_id'])    && $userBindArr['parent_id']  ? $userBindArr['parent_id']   : 0;
        $name       = isset($userBindArr['name'])         && $userBindArr['name']       ? trim($userBindArr['name'])  : '';
        $phone      = isset($userBindArr['phone'])        && $userBindArr['phone']      ? trim($userBindArr['phone']) : '';
        if (isset($userBindArr['relation_bind_id'])) {
            $relation_bind_id = intval($userBindArr['relation_bind_id']);
            unset($userBindArr['relation_bind_id']);
        } else {
            $relation_bind_id = 0;
        }
        if (!$single_id || !$floor_id || !$layer_id || !$vacancy_id || !$village_id || !$parent_id){
            $whereUpdate = [];
            $whereUpdate[] = ['id','=', $id];
            $handle_err_json = json_encode(['line'=> __LINE__, 'village_id' => $this->village_id, 'third_id' => $id, 'third_address_arr' => $third_address_arr, 'userBindArr' => $userBindArr], JSON_UNESCAPED_UNICODE);
            $updateData = [
                'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                'handle_msg'      => '家属信息缺少对应房屋归属信息[同步住户]',
                'handle_err_json' => $handle_err_json,
                'update_time'     => $this->nowTime,
            ];
            $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
            $this->filterRecordErr('家属信息缺少对应房屋归属信息');
            return false;
        }
        if (!$name && !$phone){
            $whereUpdate = [];
            $whereUpdate[] = ['id','=', $id];
            $handle_err_json = json_encode(['line'=> __LINE__, 'village_id' => $this->village_id, 'third_id' => $id, 'third_address_arr' => $third_address_arr, 'userBindArr' => $userBindArr], JSON_UNESCAPED_UNICODE);
            $updateData = [
                'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                'handle_msg'      => '家属信息姓名和手机号不可同时为空[同步住户]',
                'handle_err_json' => $handle_err_json,
                'update_time'     => $this->nowTime,
            ];
            $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
            $this->filterRecordErr('家属信息姓名和手机号不可同时为空');
            return false;
        }
        
        $whereRepeat = [];
        $whereRepeat[] = ['village_id', '=', $this->village_id];
        $whereRepeat[] = ['single_id', '=', $single_id];
        $whereRepeat[] = ['floor_id', '=', $floor_id];
        $whereRepeat[] = ['layer_id', '=', $layer_id];
        $whereRepeat[] = ['vacancy_id', '=', $vacancy_id];
        $whereRepeat[] = ['status', '=', 1];
        $whereRepeatOwner    = $whereRepeat;
        $whereUserBindRepeat = $whereRepeat;
        $wherePhoneRepeat    = $whereRepeat;
        $whereRepeatOwner[] = ['type', 'in', [0,3]];
        $whereRepeatOwner[] = ['pigcms_id', '=', $parent_id];
        // 查询下住户是否存在了
        if (!$this->dbHouseVillageUserBind) {
            $this->dbHouseVillageUserBind = new HouseVillageUserBind();
        }
        $repeatOwner = $this->dbHouseVillageUserBind->getOne($whereRepeatOwner, 'pigcms_id');
        if ($repeatOwner && !is_array($repeatOwner)) {
            $repeatOwner = $repeatOwner->toArray();
        }
        if (empty($repeatOwner) || !isset($repeatOwner['pigcms_id'])) {
            $whereUpdate = [];
            $whereUpdate[] = ['id','=', $id];
            $handle_err_json = json_encode(['line'=> __LINE__, 'village_id' => $this->village_id, 'third_id' => $id, 'third_address_arr' => $third_address_arr, 'userBindArr' => $userBindArr], JSON_UNESCAPED_UNICODE);
            $updateData = [
                'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                'handle_msg'      => '家属信息对应业主不存在[同步住户]',
                'handle_err_json' => $handle_err_json,
                'update_time'     => $this->nowTime,
            ];
            $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
            $this->filterRecordErr('家属信息对应业主不存在');
            return false;
        }
        if (!$relation_bind_id) {
            if ($phone) {
                $whereUserBindRepeat[] = ['phone', '=', $phone];
            } else  {
                $whereUserBindRepeat[] = ['name', '=', $name];
            }
            $repeatUserBindInfo = $this->dbHouseVillageUserBind->getOne($whereUserBindRepeat, 'pigcms_id');
            if ($repeatUserBindInfo && !is_array($repeatUserBindInfo)) {
                $repeatUserBindInfo = $repeatUserBindInfo->toArray();
            }
            if ($repeatUserBindInfo && isset($repeatUserBindInfo['pigcms_id'])) {
                $relation_bind_id = $repeatUserBindInfo['pigcms_id'];
            }
        }
        if (isset($phone)) {
            $wherePhoneRepeat[] = ['phone', '=', $phone];
            if ($relation_bind_id>0) {
                $wherePhoneRepeat[] = ['pigcms_id', '<>', $relation_bind_id];
            }
            $repeatPhone = $this->dbHouseVillageUserBind->getOne($wherePhoneRepeat);
            if ($repeatPhone && !is_array($repeatPhone)) {
                $repeatPhone = $repeatPhone->toArray();
            }
            if (isset($repeatPhone['phone']) && $repeatPhone['phone']) {
                $whereUpdate = [];
                $whereUpdate[] = ['id','=', $id];
                $handle_err_json = json_encode(['line'=> __LINE__, 'userBindArr' => $userBindArr, 'third_id' => $id, 'third_address_arr' => $third_address_arr, 'repeatPhone' => $repeatPhone], JSON_UNESCAPED_UNICODE);
                $updateData = [
                    'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                    'handle_msg'      => '家属信息房屋中存在相同手机号住户[同步住户]',
                    'handle_err_json' => $handle_err_json,
                    'update_time'     => $this->nowTime,
                ];
                $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
                $this->filterRecordErr('家属信息房屋中存在相同手机号住户');
                return false;
            }
        }
        if (!$relation_bind_id) {
            $userBindArr['usernum']  = $this->village_id.'_'.$single_id.'_'.$floor_id.'_'.$layer_id.'_'.$vacancy_id.'_'.$id.'_'.$userBindArr['type'];
            $userBindArr['add_time'] = $this->nowTime;
            $pigcms_id = $this->dbHouseVillageUserBind->addOne($userBindArr);
            if (!$pigcms_id){
                $whereUpdate = [];
                $whereUpdate[] = ['id','=', $id];
                $handle_err_json = json_encode(['line'=> __LINE__, 'userBindArr' => $userBindArr, 'third_id' => $id, 'third_address_arr' => $third_address_arr, 'repeatPhone' => $repeatPhone], JSON_UNESCAPED_UNICODE);
                $updateData = [
                    'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                    'handle_msg'      => '家属信息添加失败[同步住户]',
                    'handle_err_json' => $handle_err_json,
                    'update_time'     => $this->nowTime,
                ];
                $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
                $this->filterRecordErr('家属信息添加失败');
                return false;
            }
            $whereUpdate = [];
            $whereUpdate[] = ['pigcms_id','=',$pigcms_id];
            $update = [];
            $update['usernum']    = $this->village_id . '_' . $single_id . '_' . $floor_id . '_' . $layer_id . '_' .$vacancy_id . '_' .$id.'_'.$pigcms_id;
            $this->dbHouseVillageUserBind->saveOne($whereUpdate, $update);
        } else {
            $pigcms_id = $relation_bind_id;
            $whereSave = [];
            $whereSave[]  = ['pigcms_id', '=', $pigcms_id];
            $this->dbHouseVillageUserBind->saveOne($whereSave, $userBindArr);
        }
        $userBindArr['pigcms_id'] = $pigcms_id;
        return $userBindArr;
    }
   
    /**
     *处理业主信息
     * @param $third_address_arr
     * @param $id
     * @param $third_import_data
     * @param $houseArr
     * @param $userOwnerArr
     * @return false|array
     */
    protected function handleOwnerInfo($third_address_arr, $id, $third_import_data, $houseArr, $userOwnerArr) {
        // 判断下地址是车位还是房间
        $third_key  = isset($houseArr['third_key']) && $houseArr['third_key'] ? $houseArr['third_key'] : '';
        if ($third_key) {
            $third_key_json_arr = [
                'cut_type'   => 'room',
                'cut_table'  => 'house_village_third_import_data',
                'third_key'  => $third_key,
                'village_id' => $this->village_id,
            ];
            $third_key_json_room = md5(json_encode($third_key_json_arr, JSON_UNESCAPED_UNICODE));
            $houseArr = $this->getThirdImportRoom($third_key_json_room, $third_key_json_arr);
            if ($this->order_group_err) {
                return false;
            }
            $houseArr['third_key'] = $third_key;
        } else {
            if (count($third_address_arr)==3) {
                $houseArr = $this->thirdAddress3Handle($third_address_arr, $id, $third_import_data, $houseArr);
            } elseif (count($third_address_arr)==4) {
                $houseArr = $this->thirdAddress4Handle($third_address_arr, $id, $third_import_data, $houseArr);
            } elseif (count($third_address_arr)==5) {
                $houseArr = $this->thirdAddress5Handle($third_address_arr, $id, $third_import_data, $houseArr);
            }
            if (!empty($houseArr)) {
                $houseArr['cut_type'] = 'user';
                $houseArr = $this->handleThirdHouseInfo($houseArr);
            }
        }
        $single_id  = isset($houseArr['single_id']) && $houseArr['single_id'] ? $houseArr['single_id'] : 0;
        $floor_id   = isset($houseArr['floor_id'])  && $houseArr['floor_id']  ? $houseArr['floor_id']  : 0;
        $layer_id   = isset($houseArr['layer_id'])  && $houseArr['layer_id']  ? $houseArr['layer_id']  : 0;
        $vacancy_id = isset($houseArr['room_id'])   && $houseArr['room_id']   ? $houseArr['room_id']   : 0;
        if (isset($userOwnerArr['relation_bind_id'])) {
            $relation_bind_id = intval($userOwnerArr['relation_bind_id']);
            unset($userOwnerArr['relation_bind_id']);
        } else {
            $relation_bind_id = 0;
        }
        if (empty($houseArr) || !$single_id || !$floor_id || !$layer_id || !$vacancy_id){
            $whereUpdate = [];
            $whereUpdate[] = ['id','=', $id];
            $handle_err_json = json_encode(['line'=> __LINE__, 'village_id' => $this->village_id, 'third_id' => $id, 'third_address_arr' => $third_address_arr, 'houseArr' => $houseArr], JSON_UNESCAPED_UNICODE);
            $updateData = [
                'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                'handle_msg'      => '缺少对应房屋信息[同步业主]',
                'handle_err_json' => $handle_err_json,
                'update_time'     => $this->nowTime,
            ];
            $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
            $this->filterRecordErr('添加业主缺少对应房屋信息');
            return false;
        }
        $userOwnerArr['village_id'] = $this->village_id;
        $userOwnerArr['single_id']  = $single_id;
        $userOwnerArr['floor_id']   = $floor_id;
        $userOwnerArr['layer_id']   = $layer_id;
        $userOwnerArr['vacancy_id'] = $vacancy_id;

        $whereRepeat = [];
        $whereRepeat[] = ['village_id', '=', $this->village_id];
        $whereRepeat[] = ['single_id', '=', $single_id];
        $whereRepeat[] = ['floor_id', '=', $floor_id];
        $whereRepeat[] = ['layer_id', '=', $layer_id];
        $whereRepeat[] = ['vacancy_id', '=', $vacancy_id];
        $whereRepeat[] = ['status', '=', 1];
        $whereRepeatOwner = $whereRepeat;
        $wherePhoneRepeat = $whereRepeat;
        $whereRepeatOwner[] = ['type', 'in', [0,3]];
        // 查询下业主是否已经存在了
        if (!$this->dbHouseVillageUserBind) {
            $this->dbHouseVillageUserBind = new HouseVillageUserBind();
        }
        $repeatOwner = $this->dbHouseVillageUserBind->getOne($whereRepeatOwner);
        if ($repeatOwner && !is_array($repeatOwner)) {
            $repeatOwner = $repeatOwner->toArray();
        }
        $isSave         = false;
        $save_pigcms_id = 0;
        $owner_id       = 0;
        $userOwnerArr['type'] = 0;
        if (isset($repeatOwner['pigcms_id']) && $repeatOwner['pigcms_id'] && !$repeatOwner['phone'] && !$repeatOwner['name'] && !$repeatOwner['uid']) {
            // todo 虚拟业主直接替换
            $isSave         = true;
            $save_pigcms_id = $repeatOwner['pigcms_id'];
            $owner_id       = $save_pigcms_id;
        } elseif (isset($repeatOwner['pigcms_id']) && isset($userOwnerArr['phone']) && $repeatOwner['phone'] && $userOwnerArr['phone'] == $repeatOwner['phone']) {
            // todo 同一个房间同一个 手机号 认为是一个人 有就进行更新
            $isSave         = true;
            $save_pigcms_id = $repeatOwner['pigcms_id'];
            $owner_id       = $save_pigcms_id;
        } elseif (isset($repeatOwner['pigcms_id']) && isset($userOwnerArr['name']) && $repeatOwner['name'] && $userOwnerArr['name'] == $repeatOwner['name']) {
            // todo 同一个房间同一个 名字  认为是一个人  有就进行更新
            $isSave         = true;
            $save_pigcms_id = $repeatOwner['pigcms_id'];
            $owner_id       = $save_pigcms_id;
        } elseif (isset($repeatOwner['pigcms_id']) && $repeatOwner['pigcms_id']) {
            // todo 因为有业主了  且 手机号或者姓名不同  如果数据存在手机号 记录为家属
            $owner_id       = $repeatOwner['pigcms_id'];
            $userOwnerArr['type']      = 1;
            $userOwnerArr['parent_id'] = $owner_id;
            // 如果没有名称和手机号不进行操作
            if ((!isset($userOwnerArr['name']) || !$userOwnerArr['name']) && (!isset($userOwnerArr['phone']) || !$userOwnerArr['phone'])) {
                $this->filterRecordErr('没有名称和手机号不进行操作');
                return false;   
            }
        } else{
            // todo 没有业主 
            $isSave = false;
        }
        if (isset($userOwnerArr['phone']) && $userOwnerArr['phone']) {
            $wherePhoneRepeat[] = ['phone', '=', $userOwnerArr['phone']];
            if ($save_pigcms_id) {
                $wherePhoneRepeat[] = ['pigcms_id', '<>', $save_pigcms_id];
            }
            $repeatPhone = $this->dbHouseVillageUserBind->getOne($wherePhoneRepeat);
            if ($repeatPhone && !is_array($repeatPhone)) {
                $repeatPhone = $repeatPhone->toArray();
            }
            if (isset($repeatPhone['phone']) && $repeatPhone['phone']) {
                $whereUpdate = [];
                $whereUpdate[] = ['id','=', $id];
                $handle_err_json = json_encode(['line'=> __LINE__, 'userOwnerArr' => $userOwnerArr, 'third_id' => $id, 'third_address_arr' => $third_address_arr, 'houseArr' => $houseArr, 'repeatPhone' => $repeatPhone], JSON_UNESCAPED_UNICODE);
                $updateData = [
                    'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                    'handle_msg'      => '房屋中存在相同手机号身份[同步业主]',
                    'handle_err_json' => $handle_err_json,
                    'update_time'     => $this->nowTime,
                ];
                $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
                $this->filterRecordErr('房屋中存在相同手机号其他身份');
                return false;
            }
        }
        if (!$isSave) {
            $userOwnerArr['usernum']    = $this->village_id . '_' . $single_id . '_' . $floor_id . '_' . $layer_id . '_' .$vacancy_id . '_' .$id;
            $userBindArr['add_time']    = $this->nowTime;
            $pigcms_id = $this->dbHouseVillageUserBind->addOne($userOwnerArr);
            if (!$owner_id) {
                $owner_id = $pigcms_id;
            }
            if (!$pigcms_id){
                $whereUpdate = [];
                $whereUpdate[] = ['id','=', $id];
                $handle_err_json = json_encode(['line'=> __LINE__, 'userOwnerArr' => $userOwnerArr, 'third_id' => $id, 'third_address_arr' => $third_address_arr, 'houseArr' => $houseArr, 'repeatPhone' => $repeatPhone], JSON_UNESCAPED_UNICODE);
                $updateData = [
                    'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                    'handle_msg'      => '业主添加失败[同步业主]',
                    'handle_err_json' => $handle_err_json,
                    'update_time'     => $this->nowTime,
                ];
                $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
                return false;
            }
            $whereUpdate = [];
            $whereUpdate[] = ['pigcms_id','=',$pigcms_id];
            $update = [];
            $update['usernum']    = $this->village_id . '_' . $single_id . '_' . $floor_id . '_' . $layer_id . '_' .$vacancy_id . '_' .$id.'_'.$pigcms_id;
            $this->dbHouseVillageUserBind->saveOne($whereUpdate, $update);
            $userOwnerArr['usernum'] = $update['usernum'];
        } else {
            $owner_id  = $save_pigcms_id;
            $pigcms_id = $save_pigcms_id;
            $this->dbHouseVillageUserBind->saveOne($whereRepeatOwner, $userOwnerArr);
        }
        $userOwnerArr['pigcms_id'] = $pigcms_id;
        $userOwnerArr['owner_id']  = $owner_id;
        if ($pigcms_id == $owner_id && isset($userOwnerArr['phone']) && $userOwnerArr['phone']) {
            $whereBindUser = [];
            $whereBindUser[] = ['village_id', '=', $this->village_id];
            $whereBindUser[] = ['cut_type',   '=', 'park'];
            $whereBindUser[] = ['cut_table',  '=', 'house_village_third_import_data'];
            $whereBindUser[] = ['phone',      '=', $userOwnerArr['phone']];
            
            if (!$this->dbHouseVillageThirdImportPark) {
                $this->dbHouseVillageThirdImportPark = new HouseVillageThirdImportPark();
            }
            $positionIdArr = $this->dbHouseVillageThirdImportPark->getColumn($whereBindUser, 'position_id');
            if (!$this->dbHouseVillageParkingPosition) {
                $this->dbHouseVillageParkingPosition = new HouseVillageParkingPosition();
            }
            if (!empty($positionIdArr)) {
                $wherePosition = [];
                $wherePosition[] = ['village_id',      '=', $this->village_id];
                $wherePosition[] = ['position_id',     'in', $positionIdArr];
                $wherePosition[] = ['position_status', '<>', 4];
                $positionIdArr = $this->dbHouseVillageParkingPosition->getColumn($wherePosition, 'position_id');
            }
            if (!empty($positionIdArr)) {
                if (!$this->dbHouseVillageBindPosition) {
                    $this->dbHouseVillageBindPosition = new HouseVillageBindPosition();
                }
                $whereBindPosition = [];
                $whereBindPosition[] = ['position_id', 'in', $positionIdArr];
                $whereBindPosition[] = ['village_id',  '=', $this->village_id];
                $bindPositionList = $this->dbHouseVillageBindPosition->getList($whereBindPosition);
                if ($bindPositionList && !is_array($bindPositionList)) {
                    $bindPositionList = $bindPositionList->toArray();
                }
                $bindArr = [];
                $isErr = false;
                foreach ($bindPositionList as $bindPosition) {
                    if (!$isErr && $bindPosition && $bindPosition['user_id'] != $pigcms_id) {
                        $isErr = true;
//                        $this->filterRecordErr('车位已经绑定了其他业主(业主未绑定对应车位)');
                    }
                    $bindArr[$bindPosition['position_id']] = 1;
                }
                foreach ($positionIdArr as $position_id) {
                    if (isset($bindArr[$position_id]) && $bindArr[$position_id]) {
                        continue;
                    }
                    $bindPositionData = [
                        'position_id' => $position_id,
                        'village_id'  => $this->village_id,
                        'user_id'     => $pigcms_id,
                    ];
                    $bind_id = $this->dbHouseVillageBindPosition->addOne($bindPositionData);
                    if (!$bind_id) {
                        $this->filterRecordErr('业主绑定车位失败(业主未绑定到对应车位)');
                    } else {
                        $wherePosition = [];
                        $wherePosition[] = ['position_id', '=', $position_id];
                        $position_data = [
                            'position_status' => 2
                        ];
                        $this->dbHouseVillageParkingPosition->saveOne($wherePosition, $position_data);
                    }
                }
            }
        }
        return $userOwnerArr;
    }
    
    /**
     * 房产信息导入-房屋+车场车位
     * @param $param
     * @return bool
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function RoomExecute($param) {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);
        $this->nowTime = time();
        //1、接收参数 、绑定参数
        //2、按照映射关系写入库
        //3、然后使用 Redis 反馈实时导入进度
        //4、记录一下导入数据变更记录和 整理导入错误的文件
        
        $inputFileName             = $param['inputFileName'];
        $nowVillageInfo            = $param['nowVillageInfo'];
        $worksheetName             = $param['worksheetName'];


        $village_id = $this->village_id = $nowVillageInfo['village_id'];
        $orderGroupId              = isset($param['orderGroupId']) ? $param['orderGroupId'] : $village_id;

        $this->order_group_id      = $orderGroupId;
        
        
        $colAndFileds = array_column($this->roomColAndFiledArr,'value','key');
        
        //使用Redis缓存 进度 
        $processCacheKey = 'thirdimport:thirdRoomInfo:process:'.$orderGroupId;
        $totalCacheKey   = 'thirdimport:thirdRoomInfo:total:'.$orderGroupId;
        $surplusCacheKey = 'thirdimport:thirdRoomInfo:surplus:'.$orderGroupId;
        $excelCacheKey   = 'thirdimport:thirdRoomInfo:excel:'.$orderGroupId;
        if (empty(Config::get('cache.stores.redis'))){
            fdump_api(['msg' => "请先配置 Redis 缓存 " . app()->getRootPath() . 'config/cache.php'],'$importExcelErrRoomLog',1);
        }

        try {
            $this->redisCache->set($processCacheKey,0,3600);
        }catch (\Exception $e){
            //TODO 应该直接通过 队列 压入对应的业务日志里
            fdump_api(['msg' => '设置redis缓存错误 ' . $e->getMessage()],'$importExcelErrRoomLog',1);
        }


        $startTime = microtime(true);
        
        $inputFileName = root_path() . '/../'.$inputFileName;
        $fileArr       = explode('.',$inputFileName);
        $fileExt       = strtolower(end($fileArr));
        $inputFileName = str_replace('//', '/', $inputFileName);

        if ($fileExt === 'xls'){
            $inputFileType = "Xls";
        }else if($fileExt === 'xlsx'){
            $inputFileType = "Xlsx";
        } else {
            $inputFileType = "Xls";
        }
        $reader = IOFactory::createReader($inputFileType);

        $spreadsheet        = $reader->load($inputFileName);
        $sheet              = $spreadsheet->getSheetByName($worksheetName);
        if (!$sheet) {
            $sheet          = $spreadsheet->getActiveSheet();
            $worksheetName  = '';
        }
        $highestRow         = $sheet->getHighestRow(); // 总行数（）)
        $highestColumn      = $sheet->getHighestColumn();// 最后有数据的一列，比如 S 列
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);//把列字母转为数字,比如可以得到有 10 列
        
        $errColumnKey       = Coordinate::stringFromColumnIndex($highestColumnIndex + 1);
        $errColumnKeyTip    = Coordinate::stringFromColumnIndex($highestColumnIndex + 2);
        if (!$errColumnKey) {
            $errColumnKey = 'O';
        }
        

        $this->redisCache->set($totalCacheKey,$highestRow-1,3600); //总行数
        $surplusRow = $highestRow-1;

//        $str = "把列字母转为数字「{$highestColumnIndex}」总行数(有数据)：【{$highestRow}】,最后一列【{$highestColumn}】".PHP_EOL;

//        fdump_api(['msg' => "把列字母转为数字「{$highestColumnIndex}」总行数(有数据)：【{$highestRow}】,最后一列【{$highestColumn}】"],'$importExcelRoomLog',1);
        
        $chunkSize = 1000;
        $chunkFilter = new ChunkReadFilter();
        $reader->setReadFilter($chunkFilter);
        $j = 1;

        $this->dbHouseVillageThirdImportData = new HouseVillageThirdImportData();

        $insertErrData = [];
        $excelTableTile = [];
        // 过滤出数字
        $hasNext = true;
        for ($startRow = 2; $startRow <= $highestRow; $startRow += $chunkSize) {
            if (!$hasNext) {
                $startRow = $highestRow;
                $process = $startRow / $highestRow;
                if ($startRow + $chunkSize >=$highestRow ){
                    $process = 0.99;
                }
                $process = round($process, 4);
                if ($process >= 1) {
                    $process = 0.99;
                }
                $this->redisCache->set($processCacheKey,$process * 100,3600); //进度
                break;
            }
            $chunkFilter->setRows($startRow, $chunkSize);
            $spreadsheet = $reader->load($inputFileName);
            if ($worksheetName) {
                $sheetData = $spreadsheet->getSheetByName($worksheetName)->toArray(NULL, true, true, true);
            } else {
                $sheetData = $sheet->toArray(NULL, true, true, true);
            }
            if (count($sheetData) >= $highestRow) {
                $hasNext = false;
            }
            $excelTableTile = $sheetData[1];
            $excelTableTile[$errColumnKey]    = '错误信息';
            $excelTableTile[$errColumnKeyTip] = '错误信息在导入表行数';
            unset($sheetData[1]); //去掉表头

            
            foreach ($sheetData as $dk => $data) {
                $surplusRow = $surplusRow - 1;
                $this->order_group_err = '';
                if (!isset($data[$errColumnKeyTip]) || !$data[$errColumnKeyTip]) {
                    $data[$errColumnKeyTip] = $dk;
                }
                $type_id               = isset($data[$colAndFileds['type_id']])       && $data[$colAndFileds['type_id']]       ? $data[$colAndFileds['type_id']]       : '';
                $third_address         = isset($data[$colAndFileds['third_address']]) && $data[$colAndFileds['third_address']] ? $data[$colAndFileds['third_address']] : '';
                if (!$type_id || !$third_address) {
                    $this->filterRecordErr('缺少必要参数【序号】[A列]或者【房产】[B列]');
                    $data[$errColumnKey] = $this->order_group_err;
                    $insertErrData[] = $data;
                    continue;
                }
                $thirdOtherJsonData = $data;
                $third_import_data = [
                    'type'                                      => thirdImportDataConst::THIRD_YWYL_HOUSE_DATA,
                    'type_id'                                   => $type_id,
                    'third_address'                             => $third_address,
                    'village_id'                                => $village_id,
                ];
                
                unset($thirdOtherJsonData[$colAndFileds['type_id']], $thirdOtherJsonData[$colAndFileds['third_address']]);

                if (isset($data[$colAndFileds['third_main_status_txt']])) {
                    $third_main_status_txt = $data[$colAndFileds['third_main_status_txt']] ? $data[$colAndFileds['third_main_status_txt']] : '';
                    unset($thirdOtherJsonData[$colAndFileds['third_main_status_txt']]);
                    $third_import_data['third_main_status_txt'] = $third_main_status_txt;
                    $third_main_status = $this->getHouseStatus($third_main_status_txt);
                    if ($third_main_status) {
                        $third_import_data['third_main_status'] = $third_main_status;
                    }
                } else {
                    $third_main_status_txt         = '';
                    $third_main_status             = '';
                }
                if (isset($data[$colAndFileds['third_main_value1']])) {
                    $third_main_value1 = $data[$colAndFileds['third_main_value1']]     ? $data[$colAndFileds['third_main_value1']]     : '';
                    unset($thirdOtherJsonData[$colAndFileds['third_main_value1']]);
                    $third_import_data['third_main_value1']     = $third_main_value1;
                } else {
                    $third_main_value1         = '';
                }
                if (isset($data[$colAndFileds['third_main_value2']])) {
                    $third_main_value2 = $data[$colAndFileds['third_main_value2']]     ? $data[$colAndFileds['third_main_value2']]     : '';
                    unset($thirdOtherJsonData[$colAndFileds['third_main_value2']]);
                    $third_import_data['third_main_value2']     = $third_main_value2;
                } else {
                    $third_main_value2         = '';
                }
                if (isset($data[$colAndFileds['third_main_value3']])) {
                    $third_main_value3 = $data[$colAndFileds['third_main_value3']]     ? $data[$colAndFileds['third_main_value3']]     : '';
                    unset($thirdOtherJsonData[$colAndFileds['third_main_value3']]);
                    $third_import_data['third_main_value3']     = $third_main_value3;
                } else {
                    $third_main_value3         = '';
                }
                if (isset($data[$colAndFileds['third_unit_name']])) {
                    $third_unit_name   = $data[$colAndFileds['third_unit_name']]       ? $data[$colAndFileds['third_unit_name']]       : '';
                    unset($thirdOtherJsonData[$colAndFileds['third_unit_name']]);
                    $third_import_data['third_unit_name']       = $third_unit_name;
                } else {
                    $third_unit_name         = '';
                }
                if (isset($data[$colAndFileds['third_unit_price']])) {
                    $third_unit_price   = $data[$colAndFileds['third_unit_price']]      ? $data[$colAndFileds['third_unit_price']]      : '';
                    unset($thirdOtherJsonData[$colAndFileds['third_unit_price']]);
                    $third_import_data['third_unit_price']      = $third_unit_price;
                } else {
                    $third_unit_price         = '';
                }
                if (isset($data[$colAndFileds['third_type1_txt']])) {
                    $third_type1_txt    = $data[$colAndFileds['third_type1_txt']]       ? $data[$colAndFileds['third_type1_txt']]       : '';
                    unset($thirdOtherJsonData[$colAndFileds['third_type1_txt']]);
                    $third_import_data['third_type1_txt']       = $third_type1_txt;
                    $third_type1 = $this->getHouseType($third_type1_txt);
                    if ($third_type1) {
                        $third_import_data['third_type1'] = $third_type1;
                    }
                } else {
                    $third_type1_txt         = '';
                    $third_type1             = '';
                }
                if (isset($data[$colAndFileds['third_type2_txt']])) {
                    $third_type2_txt    = $data[$colAndFileds['third_type2_txt']]       ? $data[$colAndFileds['third_type2_txt']]       : '';
                    unset($thirdOtherJsonData[$colAndFileds['third_type2_txt']]);
                    $third_import_data['third_type2_txt']       = $third_type2_txt;
                    $third_type2 = $this->getHouseNature($third_type2_txt);
                    if ($third_type2) {
                        $third_import_data['third_type2'] = $third_type2;
                    }
                } else {
                    $third_type2_txt         = '';
                    $third_type2             = '';
                }
                if (isset($data[$colAndFileds['third_name']])) {
                    $third_name         = $data[$colAndFileds['third_name']]            ? trim($data[$colAndFileds['third_name']])            : '';
                    unset($thirdOtherJsonData[$colAndFileds['third_name']]);
                    $third_import_data['third_name']            = $third_name;
                } else {
                    $third_name         = '';
                }
                if (isset($data[$colAndFileds['third_phone']])) {
                    $third_phone        = $data[$colAndFileds['third_phone']]           ? trim($data[$colAndFileds['third_phone']])           : '';
                    unset($thirdOtherJsonData[$colAndFileds['third_phone']]);
                    $third_import_data['third_phone']           = $third_phone;
                } else {
                    $third_phone        = '';
                }
                if (isset($data[$colAndFileds['third_remark']])) {
                    $third_remark        = $data[$colAndFileds['third_remark']]         ? $data[$colAndFileds['third_remark']]           : '';
                    unset($thirdOtherJsonData[$colAndFileds['third_remark']]);
                    $third_import_data['third_remark']           = $third_remark;
                } else {
                    $third_remark        = '';
                }
                if (isset($data[$colAndFileds['third_key']])) {
                    $third_key        = $data[$colAndFileds['third_key']]               ? trim($data[$colAndFileds['third_key']])        : '';
                    unset($thirdOtherJsonData[$colAndFileds['third_key']]);
                    $third_import_data['third_key']           = $third_key;
                } else {
                    $third_key        = '';
                }
                if (!$third_key) {
                    $this->filterRecordErr('缺少关键字【关键字】[N列]');
                    $data[$errColumnKey] = $this->order_group_err;
                    $insertErrData[] = $data;
                    continue;
                }
                
                $third_import_data['third_other_json']          = json_encode($thirdOtherJsonData, JSON_UNESCAPED_UNICODE);

                $repeat_key = md5(json_encode($third_import_data, JSON_UNESCAPED_UNICODE));
                $third_import_data['repeat_key']                = $repeat_key;
                $third_import_data['add_time']                  = $this->nowTime;
                $id = $this->dbHouseVillageThirdImportData->add($third_import_data);
                if (!$id) {
                    $this->filterRecordErr('添加记录导入失败');
                    $data[$errColumnKey] = $this->order_group_err;
                    $insertErrData[] = $data;
                }
                // todo 处理 同步 楼栋单元楼层房屋
                if ($id) {
                    // todo 变更状态
                    $this->dbHouseVillageThirdImportData->updateThis([['id','=', $id]], ['handle_status' => thirdImportDataConst::HANDLE_STATUS_PROCESSING]);
                    $third_address_arr = explode('->', $third_address);
                    if (isset($third_address_arr[0]) && (strstr($third_address_arr[0], '车位') || strstr($third_address_arr[0], '停车库') || (isset($third_type1_txt) && $third_type1_txt == '车位'))) {
                        // todo 车位暂不处理
                        if (isset($third_address_arr[2]) && $third_address_arr[2]) {
                            $carInfoArr = [
                                'village_id'  => $village_id,
                                'third_id'    => $id,
                                'cut_address' => $third_address,
                                'garage'      => $third_address_arr[0], // 车场或车库 例如 地下车场
                                'parkingAre'  => $third_address_arr[1], // 车场区域  例如 A区、B区
                                'parkingLot'  => $third_address_arr[2], // 车位  例如 A11、B55
                            ];
                        } else {
                            $carInfoArr = [
                                'village_id'  => $village_id,
                                'third_id'    => $id,
                                'cut_address' => $third_address,
                                'garage'      => $third_address_arr[0], // 车场或车库 例如 地下车场
                                'parkingLot'  => $third_address_arr[1], // 车位  例如 11、55
                            ];
                        }
                        if (isset($third_name) && $third_name) {
                            $carInfoArr['name'] = $third_name;
                        }
                        if (isset($third_phone) && $third_phone) {
                            $carInfoArr['phone'] = $third_phone;
                        }
                        $carInfoArr['bindUser'] = false;
                        if (isset($third_type2_txt)) {
                            if (!isset($third_type2)) {
                                $third_type2 = $this->getHouseNature($third_type2_txt);
                            }
                            if ($third_type2) {
                                $carInfoArr['house_nature'] = $third_type2;
                            }
                        }
                        if (isset($third_key) && $third_key) {
                            $carInfoArr['third_key'] = $third_key;
                        }
                        $this->order_group_err = '';
                        $this->handleThirdParkInfo($carInfoArr);
                        if ($this->order_group_err) {
                            $data[$errColumnKey] = $this->order_group_err;
                            $insertErrData[] = $data;
                        }
                    } else {
                        $houseArr = [
                            'village_id'  => $village_id,
                            'third_id'    => $id,
                            'cut_address' => $third_address,
                        ];
                        if (isset($third_key) && $third_key) {
                            $houseArr['third_key'] = $third_key;
                        }
                        if (count($third_address_arr)==3) {
                            $houseArr = $this->thirdAddress3Handle($third_address_arr, $id, $third_import_data, $houseArr);
                        } elseif (count($third_address_arr)==4) {
                            $houseArr = $this->thirdAddress4Handle($third_address_arr, $id, $third_import_data, $houseArr);
                        } elseif (count($third_address_arr)==5) {
                            $houseArr = $this->thirdAddress5Handle($third_address_arr, $id, $third_import_data, $houseArr);
                        }
                        if (!empty($houseArr)) {
                            $houseArr['cut_type'] = 'room';
                            $this->handleThirdHouseInfo($houseArr);
                        } else {
                            $this->filterRecordErr('缺少对应车库车位信息【房产】[B列]');
                        }
                        if ($this->order_group_err) {
                            $data[$errColumnKey] = $this->order_group_err;
                            $insertErrData[] = $data;
                        }
                    }
                }
                $this->redisCache->set($surplusCacheKey,$surplusRow,3600); //剩余数

                $surplusProcess = $surplusRow / ($highestRow - 1);
                if ($surplusProcess > 1) {
                    $process = 0;
                } else {
                    $process = 1 - $surplusProcess;
                }
                $process = round($process, 4);
                if ($process >= 1) {
                    $process = 0.99;
                }
                $this->redisCache->set($processCacheKey,$process * 100,3600); //进度
            }
            
            $process = $startRow / $highestRow;
            if ($startRow + $chunkSize >=$highestRow ){
                $process = 0.99;
            }
            $process = round($process, 4);
            if ($process >= 1) {
                $process = 0.99;
            }
            $this->redisCache->set($processCacheKey,$process * 100,3600); //进度
            $j++;
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            $spreadsheet = NULL;
            unset($sheetData);
            $sheetData = NULL;
        }
        if (!empty($insertErrData) && !empty($excelTableTile)) {
            $fileInfo = pathinfo($inputFileName);
            if (isset($fileInfo['filename']) && $fileInfo['filename'] && isset($fileInfo['extension']) && $fileInfo['extension']) {
                $file_name = $fileInfo['filename'] . '-导入错误表.' . $fileInfo['extension'];
            } else {
                $file_name = '房产-导入错误表.xls';
            }
            $excelInfo = $this->saveExcel($insertErrData, $excelTableTile, $errColumnKey, $file_name, $inputFileType);
            if (isset($excelInfo['url']) && $excelInfo['url']) {
                $this->redisCache->set($excelCacheKey,$excelInfo['url'],3600); //错误表格
            }
            sleep(2);
            $this->redisCache->set($processCacheKey,100,3600); //进度
        } else {
            $this->redisCache->set($processCacheKey,100,3600); //进度
        }
        return true;
    }


    public function saveExcel($data = [], $excelTableTile = [], $errColumnKey='', $file_name = '', $inputFileType = 'Xls')
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
            if ($pColumn == $errColumnKey) {
                $spreadsheet->getActiveSheet()->getColumnDimension($pColumn)->setWidth(50);
            } else {
                $spreadsheet->getActiveSheet()->getColumnDimension($pColumn)->setWidth(20);
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
                    $worksheet->setCellValue($pCoordinate.$j, isset($val[$pCoordinate]) && $val[$pCoordinate] ? $val[$pCoordinate] : '');
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

    public function phpSpreadsheet($filename,$spreadsheet, $inputFileType = 'Xls'){
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
//        $ua = request()->server('HTTP_USER_AGENT');
//        $ua = strtolower($ua);
//        if (preg_match('/msie/', $ua) || preg_match('/edge/', $ua) || preg_match('/trident/', $ua)) { //判断是否为IE或Edge浏览器
//            $filename = str_replace('+', '%20', urlencode($filename)); //使用urlencode对文件名进行重新编码
//        }

        $returnArr['url'] = cfg('site_url') . '/v20/runtime/' . $filename;
        $returnArr['error'] = 0;
        return $returnArr;
    }
    
    //************车库车位导入**********//
    protected function handleThirdParkInfo($carInfoArr) {
        $village_id    = $carInfoArr['village_id'];
        $third_id      = isset($carInfoArr['third_id'])         && $carInfoArr['third_id']         ? $carInfoArr['third_id']               : 0;
        $cut_address   = isset($carInfoArr['cut_address'])      && $carInfoArr['cut_address']      ? $carInfoArr['cut_address']            : '';
        $cut_table     = isset($carInfoArr['cut_table'])        && $carInfoArr['cut_table']        ? $carInfoArr['cut_table']              : 'house_village_third_import_data';
        $cut_id        = isset($carInfoArr['cut_id'])           && $carInfoArr['cut_id']           ? $carInfoArr['cut_id']                 : $third_id;
        $garage        = isset($carInfoArr['garage'])           && $carInfoArr['garage']           ? $carInfoArr['garage']                 : '';
        $parkingAre    = isset($carInfoArr['parkingAre'])       && $carInfoArr['parkingAre']       ? $carInfoArr['parkingAre']             : '';
        $parkingLot    = isset($carInfoArr['parkingLot'])       && $carInfoArr['parkingLot']       ? $carInfoArr['parkingLot']             : '';
        $house_nature  = isset($carInfoArr['house_nature'])     && $carInfoArr['house_nature']     ? $carInfoArr['house_nature']           : 0;
        $third_key     = isset($carInfoArr['third_key'])        && $carInfoArr['third_key']        ? trim($carInfoArr['third_key'])        : '';
        $name          = isset($carInfoArr['name'])             && $carInfoArr['name']             ? trim($carInfoArr['name'])             : '';
        $phone         = isset($carInfoArr['phone'])            && $carInfoArr['phone']            ? trim($carInfoArr['phone'])            : '';
        $bindUser      = isset($carInfoArr['bindUser'])         && $carInfoArr['bindUser']         ? $carInfoArr['bindUser']               : false;
        
        $third_main_value1  = isset($carInfoArr['third_main_value1']) && $carInfoArr['third_main_value1']  ? $carInfoArr['third_main_value1'] : 0;// 建筑面积
        $third_main_value2  = isset($carInfoArr['third_main_value2']) && $carInfoArr['third_main_value2']  ? $carInfoArr['third_main_value2'] : 0;// 计费面积
        $third_main_value3  = isset($carInfoArr['third_main_value3']) && $carInfoArr['third_main_value3']  ? $carInfoArr['third_main_value3'] : 0;// 辅助计费面
        if ($third_main_value2) {
            $carInfoArr['position_area'] = $third_main_value2;
        } elseif ($third_main_value3) {
            $carInfoArr['position_area'] = $third_main_value3;
        } elseif ($third_main_value1) {
            $carInfoArr['position_area'] = $third_main_value1;
        }
        
        if (!$this->dbHouseVillageThirdImportData) {
            $this->dbHouseVillageThirdImportData = new HouseVillageThirdImportData();
        }
        if (!$this->nowTime) {
            $this->nowTime = time();
        }
        if (!$garage || !$parkingLot) {
            $whereUpdate = [];
            $whereUpdate[] = ['id','=', $third_id];
            $handle_err_json = json_encode(['line'=> __LINE__, 'village_id' => $village_id, 'third_id' => $third_id, 'carInfoArr' => $carInfoArr], JSON_UNESCAPED_UNICODE);
            $updateData = [
                'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                'handle_msg'      => '缺少对应车库车位信息',
                'handle_err_json' => $handle_err_json,
                'update_time'     => $this->nowTime,
            ];
            $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
            $this->filterRecordErr('缺少对应车库车位信息【房产】');
            return false;
        }
        
        // 记录数组
        $third_import_park = [];
        // 去重数组
        $unique_cut_key    = [];
        // 目前这里默认为车位
        $cut_type = 'park';
        $third_import_park['village_id']   = $village_id;
        $third_import_park['cut_address']  = $cut_address;
        $third_import_park['cut_table']    = $cut_table;
        $third_import_park['cut_id']       = $cut_id;
        $third_import_park['cut_type']     = $cut_type;
        $third_import_park['house_nature'] = $house_nature;
        if ($name) {
            $third_import_park['name'] = $name;
        }
        if ($phone) {
            $third_import_park['phone'] = $phone;
        }
        if ($third_key) {
            $third_import_park['third_key'] = $third_key;
            $third_key_json_arr = [
                'cut_type'   => $cut_type,
                'cut_table'  => $cut_table,
                'third_key'  => $third_key,
                'village_id' => $village_id,
            ];
            $third_key_json = md5(json_encode($third_key_json_arr, JSON_UNESCAPED_UNICODE));
            $third_import_park['third_key_json'] = $third_key_json;
        }

        $unique_cut_key['village_id']  = $village_id;
        $unique_cut_key['cut_address'] = $cut_address;
        $unique_cut_key['cut_table']   = $cut_table;
        $unique_cut_key['cut_type']    = $cut_type;
        

        // 处理车场
        $carInfoArr = $this->handleRecordGarageData($carInfoArr, $village_id, $third_id,$third_import_park, $unique_cut_key);
        if (!$carInfoArr) {
            return false;
        }
        // 处理车位
        $carInfoArr = $this->handleRecordParkData($carInfoArr, $village_id, $third_id,$third_import_park, $unique_cut_key);
        if (!$carInfoArr) {
            return false;
        }
        // 处理整体记录
        $this->handleThirdImportPark($third_import_park, $unique_cut_key);
        // 处理下和业主绑定关系
        if (!$this->dbHouseVillageUserBind) {
            $this->dbHouseVillageUserBind = new HouseVillageUserBind();
        }
        $position_id = $carInfoArr['position_id'];
        if ($bindUser && $phone) {
            $whereBindUser = [];
            $whereBindUser[] = ['village_id', '=', $village_id];
            $whereBindUser[] = ['type',       'in', [0,3]];
            $whereBindUser[] = ['status',     '=', 1];
            $whereBindUser[] = ['phone',      '=', $phone];
            $ownerUser =  $this->dbHouseVillageUserBind->getOne($whereBindUser,'pigcms_id, village_id');
            if ($ownerUser && !is_array($ownerUser)) {
                $ownerUser = $ownerUser->toArray();
            }
            if (empty($ownerUser)) {
                $this->filterRecordErr('当前无对应手机号业主(车位未绑定业主)');
            } else {
                if (!$this->dbHouseVillageBindPosition) {
                    $this->dbHouseVillageBindPosition = new HouseVillageBindPosition();
                }
                $whereBindPosition = [];
                $whereBindPosition[] = ['position_id', '=', $position_id];
                $whereBindPosition[] = ['village_id',  '=', $village_id];
                $bindPosition = $this->dbHouseVillageBindPosition->getOne($whereBindPosition);
                if ($bindPosition && !is_array($bindPosition)) {
                    $bindPosition = $bindPosition->toArray();
                }
                if (!$this->dbHouseVillageParkingPosition) {
                    $this->dbHouseVillageParkingPosition = new HouseVillageParkingPosition();
                }
                if ($bindPosition && isset($bindPosition['user_id']) && $bindPosition['user_id'] != $ownerUser['pigcms_id']) {
                    $wherePosition = [];
                    $wherePosition[] = ['position_id', '=', $position_id];
                    $position_data = [
                        'position_status' => 2
                    ];
                    $this->dbHouseVillageParkingPosition->saveOne($wherePosition, $position_data);
                    $this->filterRecordErr('车位已经绑定了其他业主(车位未绑定当前业主)');
                } elseif (empty($bindPosition)) {
                    $bindPositionData = [
                        'position_id' => $position_id,
                        'village_id'  => $village_id,
                        'user_id'     => $ownerUser['pigcms_id'],
                    ];
                    $bind_id = $this->dbHouseVillageBindPosition->addOne($bindPositionData);
                    if (!$bind_id) {
                        $this->filterRecordErr('车位绑定业主失败(车位未绑定业主)');
                    } else {
                        $wherePosition = [];
                        $wherePosition[] = ['position_id', '=', $position_id];
                        $position_data = [
                            'position_status' => 2
                        ];
                        $this->dbHouseVillageParkingPosition->saveOne($wherePosition, $position_data);
                    }
                }
            }
        } elseif ($bindUser && $name) {
            $this->filterRecordErr('缺少业主手机号未匹配绑定到业主(车位未绑定业主)');
        }
        
        return $carInfoArr;
    }
    
    protected function filterRecordErr($msg) {

        if ($this->order_group_err) {
            $this->order_group_err .= '；'.$msg;
        } else {
            $this->order_group_err = $msg;
        }
    }

    /**
     * 单个处理车位
     * @param $carInfoArr
     * @param $village_id
     * @param $third_id
     * @param $third_import_park
     * @param $unique_cut_key
     * @return false|array
     * @throws DbException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function handleRecordParkData($carInfoArr, $village_id, $third_id,&$third_import_park, &$unique_cut_key) {
        $garage_id        = isset($carInfoArr['garage_id'])  && $carInfoArr['garage_id']  ? $carInfoArr['garage_id']  : '';
        $parkingLot       = isset($carInfoArr['parkingLot']) && $carInfoArr['parkingLot'] ? $carInfoArr['parkingLot'] : '';
        $parkingAre       = isset($carInfoArr['parkingAre']) && $carInfoArr['parkingAre'] ? $carInfoArr['parkingAre'] : '';
        $cut_address      = isset($carInfoArr['cut_address']) && $carInfoArr['cut_address'] ? $carInfoArr['cut_address'] : '';
        
        $wherePosition   = [];
        $wherePosition[] = ['village_id',      '=', $village_id];
        $wherePosition[] = ['garage_id',       '=', $garage_id];
        $wherePosition[] = ['position_num',    '=', $parkingLot];
        $wherePosition[] = ['position_status', '<>', 4];
        $position_data = [];
        if (!$parkingLot){
            $whereUpdate = [];
            $whereUpdate[] = ['id','=', $third_id];
            $handle_err_json = json_encode(['line'=> __LINE__, 'village_id' => $village_id, 'third_id' => $third_id, 'carInfoArr' => $carInfoArr, 'third_import_park' => $third_import_park], JSON_UNESCAPED_UNICODE);
            $updateData = [
                'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                'handle_msg'      => '缺少对应车位信息',
                'handle_err_json' => $handle_err_json,
                'update_time'     => $this->nowTime,
            ];
            $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
            $this->filterRecordErr('缺少对应车位信息【房产】');
            return false;
        }
        if (!$this->dbHouseVillageParkingPosition) {
            $this->dbHouseVillageParkingPosition = new HouseVillageParkingPosition();
        }

        $positionInfo = $this->dbHouseVillageParkingPosition->getFind($wherePosition);
        if ($positionInfo && !is_array($positionInfo)) {
            $positionInfo = $positionInfo->toArray();
        }
        $position_data['garage_id']     = $garage_id;
        $position_data['position_num']  = $parkingLot;
        $position_data['village_id']    = $village_id;
        $position_data['position_note'] = $cut_address ? $cut_address : $parkingAre;
        $position_data['position_area'] = isset($carInfoArr['position_area']) ? $carInfoArr['position_area'] : '';

        if (isset($positionInfo['position_id'])) {
            $this->dbHouseVillageParkingPosition->saveOne($wherePosition, $position_data);
            $position_id  = $positionInfo['position_id'];
        } else {
            $position_data['third_id']       = $third_id;
            $position_id =  $this->dbHouseVillageParkingPosition->addOne($position_data);
        }
        if (!$position_id) {
            $whereUpdate = [];
            $whereUpdate[] = ['id','=', $third_id];
            $handle_err_json = json_encode(['line'=> __LINE__, 'village_id' => $village_id, 'third_id' => $third_id, 'carInfoArr' => $carInfoArr, 'third_import_park' => $third_import_park], JSON_UNESCAPED_UNICODE);
            $updateData = [
                'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                'handle_msg'      => '车位添加失败',
                'handle_err_json' => $handle_err_json,
                'update_time'     => $this->nowTime,
            ];
            $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
            $this->filterRecordErr('车位添加失败');
            return false;
        }
        $third_import_park['position_id']   = $position_id;
        $unique_cut_key['position_id']      = $position_id;
        $carInfoArr['position_id']          = $position_id;
        return $carInfoArr;
    }

    /**
     * 单个处理车库
     * @param $carInfoArr
     * @param $village_id
     * @param $third_id
     * @param $third_import_park
     * @param $unique_cut_key
     * @return false|array
     * @throws DbException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function handleRecordGarageData($carInfoArr, $village_id, $third_id,&$third_import_park, &$unique_cut_key) {
        $garage_num        = isset($carInfoArr['garage']) && $carInfoArr['garage'] ? $carInfoArr['garage'] : '';
        $garage_position   = $garage_num;
        $whereGarage   = [];
        $whereGarage[] = ['village_id', '=', $village_id];
        $whereGarage[] = ['garage_num', '=', $garage_num];
        $whereGarage[] = ['status', '=', 1];
        $garage_data = [];
        if (!$garage_num){
            $whereUpdate = [];
            $whereUpdate[] = ['id','=', $third_id];
            $handle_err_json = json_encode(['line'=> __LINE__, 'village_id' => $village_id, 'third_id' => $third_id, 'carInfoArr' => $carInfoArr, 'third_import_park' => $third_import_park], JSON_UNESCAPED_UNICODE);
            $updateData = [
                'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                'handle_msg'      => '缺少对应车库信息',
                'handle_err_json' => $handle_err_json,
                'update_time'     => $this->nowTime,
            ];
            $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
            $this->filterRecordErr('缺少对应车库车位信息【房产】');
            return false;
        }
        if (!$this->dbHouseVillageParkingGarage) {
            $this->dbHouseVillageParkingGarage = new HouseVillageParkingGarage();
        }

        $garageInfo = $this->dbHouseVillageParkingGarage->getOne($whereGarage);
        if ($garageInfo && !is_array($garageInfo)) {
            $garageInfo = $garageInfo->toArray();
        }
        $garage_data['garage_num'] = $garage_num;
        $garage_data['garage_position'] = $garage_position;
        $garage_data['village_id']      = $village_id;
        $garage_data['status']          = 1;
        if (isset($garageInfo['garage_id'])) {
            $this->dbHouseVillageParkingGarage->saveOne($whereGarage, $garage_data);
            $garage_id  = $garageInfo['garage_id'];
        } else {
            $garage_data['garage_addtime'] = $this->nowTime;
            $garage_data['third_id']       = $third_id;
            $garage_id =  $this->dbHouseVillageParkingGarage->addOne($garage_data);
        }
        (new CarHandleGetInfoService())->clearCarParkingGarageInfo($village_id);
        if (!$garage_id) {
            $whereUpdate = [];
            $whereUpdate[] = ['id','=', $third_id];
            $handle_err_json = json_encode(['line'=> __LINE__, 'village_id' => $village_id, 'third_id' => $third_id, 'carInfoArr' => $carInfoArr, 'third_import_park' => $third_import_park], JSON_UNESCAPED_UNICODE);
            $updateData = [
                'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                'handle_msg'      => '车库添加失败',
                'handle_err_json' => $handle_err_json,
                'update_time'     => $this->nowTime,
            ];
            $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
            $this->filterRecordErr('车库添加失败');
            return false;
        }
        $third_import_park['garage_id']   = $garage_id;
        $unique_cut_key['garage_id']      = $garage_id;
        $carInfoArr['garage_id']          = $garage_id;
        return $carInfoArr;
    }

    /**
     * 获取对应绑定车位信息
     * @param string $third_key_json
     * @param array $whereThirdKey
     * @param bool $bindUser
     * @param string $phone
     * @return array|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function getThirdImportPark($third_key_json, $whereThirdKey = [], $bindUser = false, $phone = '') {
        if (!$this->dbHouseVillageThirdImportPark) {
            $this->dbHouseVillageThirdImportPark = new HouseVillageThirdImportPark();
        }
        if (!empty($whereThirdKey)) {
            $repeatInfo = $this->dbHouseVillageThirdImportPark->getOne($whereThirdKey, true, 'bind_id DESC');
            if ($repeatInfo && !is_array($repeatInfo)) {
                $repeatInfo = $repeatInfo->toArray();
            }
        }
        if (!isset($repeatInfo) || empty($repeatInfo)) {
            $whereUnique = [];
            $whereUnique[] = ['third_key_json', '=', $third_key_json];
            $repeatInfo = $this->dbHouseVillageThirdImportPark->getOne($whereUnique, true, 'bind_id DESC');
            if ($repeatInfo && !is_array($repeatInfo)) {
                $repeatInfo = $repeatInfo->toArray();
            }
        }
        if (empty($repeatInfo)) {
            return [];
        }
        if (!$this->dbHouseVillageParkingGarage) {
            $this->dbHouseVillageParkingGarage = new HouseVillageParkingGarage();
        }
        $whereGarage   = [];
        $whereGarage[] = ['garage_id',      '=', $repeatInfo['garage_id']];
        $garageInfo = $this->dbHouseVillageParkingGarage->getOne($whereGarage);
        if ($garageInfo && !is_array($garageInfo)) {
            $garageInfo = $garageInfo->toArray();
        }
        if (empty($garageInfo) || !isset($garageInfo['garage_id']) || $garageInfo['status'] == 0) {
            $this->filterRecordErr('对应车库已经不存在或者被删除');
            return [];
        }
        if (!$this->dbHouseVillageParkingPosition) {
            $this->dbHouseVillageParkingPosition = new HouseVillageParkingPosition();
        }
        $wherePosition   = [];
        $wherePosition[] = ['position_id',      '=', $repeatInfo['position_id']];
        $positionInfo = $this->dbHouseVillageParkingPosition->getFind($wherePosition, 'position_id, position_status');
        if ($positionInfo && !is_array($positionInfo)) {
            $positionInfo = $positionInfo->toArray();
        }
        if (empty($positionInfo) || !isset($positionInfo['position_id']) || $positionInfo['position_status'] == 4) {
            $this->filterRecordErr('对应车位已经不存在或者被删除');
            return [];
        }
        if ($bindUser && $phone) {
            $whereBindUser = [];
            $whereBindUser[] = ['village_id', '=', $this->village_id];
            $whereBindUser[] = ['type',       'in', [0,3]];
            $whereBindUser[] = ['status',     '=', 1];
            $whereBindUser[] = ['phone',      '=', $phone];
            $ownerUser =  $this->dbHouseVillageUserBind->getOne($whereBindUser,'pigcms_id, village_id');
            if ($ownerUser && !is_array($ownerUser)) {
                $ownerUser = $ownerUser->toArray();
            }
            if (empty($ownerUser)) {
                $this->filterRecordErr('当前无对应手机号业主(车位未绑定业主)');
            } else {
                if (!$this->dbHouseVillageBindPosition) {
                    $this->dbHouseVillageBindPosition = new HouseVillageBindPosition();
                }
                $whereBindPosition = [];
                $whereBindPosition[] = ['position_id', '=', $repeatInfo['position_id']];
                $whereBindPosition[] = ['village_id',  '=', $this->village_id];
                $bindPosition = $this->dbHouseVillageBindPosition->getOne($whereBindPosition);
                if ($bindPosition && !is_array($bindPosition)) {
                    $bindPosition = $bindPosition->toArray();
                }
                if ($bindPosition && isset($bindPosition['user_id']) && $bindPosition['user_id'] != $ownerUser['pigcms_id']) {
                    $wherePosition = [];
                    $wherePosition[] = ['position_id', '=', $repeatInfo['position_id']];
                    $position_data = [
                        'position_status' => 2
                    ];
                    $this->dbHouseVillageParkingPosition->saveOne($wherePosition, $position_data);
                    $this->filterRecordErr('车位已经绑定了其他业主(车位未绑定当前业主)');
                } elseif (empty($bindPosition)) {
                    $bindPositionData = [
                        'position_id' => $repeatInfo['position_id'],
                        'village_id'  => $this->village_id,
                        'user_id'     => $ownerUser['pigcms_id'],
                    ];
                    $bind_id = $this->dbHouseVillageBindPosition->addOne($bindPositionData);
                    if (!$bind_id) {
                        $this->filterRecordErr('车位绑定业主失败(车位未绑定业主)');
                    } else {
                        $wherePosition = [];
                        $wherePosition[] = ['position_id', '=', $repeatInfo['position_id']];
                        $position_data = [
                            'position_status' => 2
                        ];
                        $this->dbHouseVillageParkingPosition->saveOne($wherePosition, $position_data);
                    }
                }
            }
        } elseif ($bindUser) {
            $this->filterRecordErr('缺少业主手机号未匹配绑定到业主(车位未绑定业主)');
        }
        return $repeatInfo;
    }
    
    /**
     * 车位处理完毕记录关联关系
     * @param $third_import_park
     * @param $unique_cut_key_arr
     * @return bool
     */
    protected function handleThirdImportPark($third_import_park, $unique_cut_key_arr) {
        // 区分唯一值
        $unique_cut_key = md5(json_encode($unique_cut_key_arr, JSON_UNESCAPED_UNICODE));
        $third_import_park['unique_cut_key']                = $unique_cut_key;

        if (!$this->nowTime) {
            $this->nowTime = time();
        }
        if (!$this->dbHouseVillageThirdImportPark) {
            $this->dbHouseVillageThirdImportPark = new HouseVillageThirdImportPark();
        }
        if (isset($third_import_park['third_key_json']) && $third_import_park['third_key_json']) {
            $whereUnique = [];
            $whereUnique[] = ['third_key_json', '=', $third_import_park['third_key_json']];
            $repeatInfo = $this->dbHouseVillageThirdImportPark->getOne($whereUnique);
            if ($repeatInfo && !is_array($repeatInfo)) {
                $repeatInfo = $repeatInfo->toArray();
            }
        }
        if (!isset($repeatInfo) || empty($repeatInfo) || !isset($repeatInfo['bind_id'])) {
            $whereUnique = [];
            $whereUnique[] = ['unique_cut_key', '=', $unique_cut_key];
            $repeatInfo = $this->dbHouseVillageThirdImportPark->getOne($whereUnique);
            if ($repeatInfo && !is_array($repeatInfo)) {
                $repeatInfo = $repeatInfo->toArray();
            }
        }
        
        
        if (isset($repeatInfo['bind_id']) && isset($whereUnique)) {
            $third_import_park['update_time'] = $this->nowTime;
            $this->dbHouseVillageThirdImportPark->updateThis($whereUnique, $third_import_park);
        } else {
            $third_import_park['add_time'] = $this->nowTime;
            $this->dbHouseVillageThirdImportPark->add($third_import_park);
        }
        return true;
    }
    
    //************房屋导入*************//
    protected function handleThirdHouseInfo($houseArr) {
        $village_id    = $houseArr['village_id'];
        $third_id      = isset($houseArr['third_id'])    && $houseArr['third_id']    ? $houseArr['third_id']    : 0;
        $cut_address   = isset($houseArr['cut_address']) && $houseArr['cut_address'] ? $houseArr['cut_address'] : '';
        $cut_table     = isset($houseArr['cut_table'])   && $houseArr['cut_table']   ? $houseArr['cut_table']   : 'house_village_third_import_data';
        $cut_id        = isset($houseArr['cut_id'])      && $houseArr['cut_id']      ? $houseArr['cut_id']      : $third_id;
        $cut_type      = isset($houseArr['cut_type'])    && $houseArr['cut_type']    ? $houseArr['cut_type']    : 'room';
        $third_key     = isset($houseArr['third_key'])   && $houseArr['third_key']   ? $houseArr['third_key']   : '';
        
        if (!$this->dbHouseVillageThirdImportData) {
            $this->dbHouseVillageThirdImportData = new HouseVillageThirdImportData();
        }
        if (!$this->nowTime) {
            $this->nowTime = time();
        }
        if (!isset($houseArr['singleArr']) || empty($houseArr['singleArr'])) {
            $whereUpdate = [];
            $whereUpdate[] = ['id','=', $third_id];
            $handle_err_json = json_encode(['line'=> __LINE__, 'village_id' => $village_id, 'third_id' => $third_id, 'houseArr' => $houseArr], JSON_UNESCAPED_UNICODE);
            $updateData = [
                'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                'handle_msg'      => '缺少对应楼栋信息',
                'handle_err_json' => $handle_err_json,
                'update_time'     => $this->nowTime,
            ];
            $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
            $this->filterRecordErr('缺少对应楼栋信息【房产或房号】');
            return false;
        }
        if (!isset($houseArr['floorArr']) || empty($houseArr['floorArr'])) {
            $whereUpdate = [];
            $whereUpdate[] = ['id','=', $third_id];
            $handle_err_json = json_encode(['line'=> __LINE__, 'village_id' => $village_id, 'third_id' => $third_id, 'houseArr' => $houseArr], JSON_UNESCAPED_UNICODE);
            $updateData = [
                'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                'handle_msg'      => '缺少对应单元信息',
                'handle_err_json' => $handle_err_json,
                'update_time'     => $this->nowTime,
            ];
            $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
            $this->filterRecordErr('缺少对应单元信息【房产或房号】');
            return false;
        }
        if (!isset($houseArr['LayerArr']) || empty($houseArr['LayerArr'])) {
            $whereUpdate = [];
            $whereUpdate[] = ['id','=', $third_id];
            $handle_err_json = json_encode(['line'=> __LINE__, 'village_id' => $village_id, 'third_id' => $third_id, 'houseArr' => $houseArr], JSON_UNESCAPED_UNICODE);
            $updateData = [
                'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                'handle_msg'      => '缺少对应楼层信息',
                'handle_err_json' => $handle_err_json,
                'update_time'     => $this->nowTime,
            ];
            $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
            $this->filterRecordErr('缺少对应楼层信息【房产或房号】');
            return false;
        }
        if (!isset($houseArr['roomArr']) || empty($houseArr['roomArr'])) {
            $whereUpdate = [];
            $whereUpdate[] = ['id','=', $third_id];
            $handle_err_json = json_encode(['line'=> __LINE__, 'village_id' => $village_id, 'third_id' => $third_id, 'houseArr' => $houseArr], JSON_UNESCAPED_UNICODE);
            $updateData = [
                'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                'handle_msg'      => '缺少对应房屋信息',
                'handle_err_json' => $handle_err_json,
                'update_time'     => $this->nowTime,
            ];
            $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
            $this->filterRecordErr('缺少对应房屋信息【房产或房号】');
            return false;
        }
        // 地址为了区分唯一去除一级地址（目前看一级地址有时候是 类别有时候是小区 无法统一 所以去除）
        $cut_address_arr = explode('->', $cut_address);
        if (isset($cut_address_arr[0])) {
            unset($cut_address_arr[0]);
            $cut_address_arr = array_values($cut_address_arr);
            $cut_address = implode('->', $cut_address_arr);
        }
        // 记录数组
        $third_import_room = [];
        // 去重数组
        $unique_cut_key    = [];
        // 目前这里默认为房间
        $third_import_room['village_id']  = $village_id;
        $third_import_room['cut_address'] = $cut_address;
        $third_import_room['cut_table']   = $cut_table;
        $third_import_room['cut_id']      = $cut_id;
        $third_import_room['cut_type']    = $cut_type;

        $unique_cut_key['village_id']  = $village_id;
        $unique_cut_key['cut_address'] = $cut_address;
        $unique_cut_key['cut_table']   = $cut_table;
        $unique_cut_key['cut_type']    = $cut_type;
        
        // 处理楼栋
        $houseArr = $this->handleRecordSingleData($houseArr, $village_id, $third_id,$third_import_room, $unique_cut_key);
        // 处理单元
        $houseArr = $this->handleRecordFloorData($houseArr, $village_id, $third_id,$third_import_room, $unique_cut_key);
        // 处理楼层
        $houseArr = $this->handleRecordLayerData($houseArr, $village_id, $third_id,$third_import_room, $unique_cut_key);
        // 处理房屋
        $houseArr = $this->handleRecordRoomData($houseArr, $village_id, $third_id,$third_import_room, $unique_cut_key);
        // 处理整体记录
        if ($third_key) {
            $third_import_room['third_key'] = $third_key;
            $third_key_json_arr = [
                'cut_type'   => $cut_type,
                'cut_table'  => $cut_table,
                'third_key'  => $third_key,
                'village_id' => $village_id,
            ];
            $third_key_json = md5(json_encode($third_key_json_arr, JSON_UNESCAPED_UNICODE));
            $third_import_room['third_key_json'] = $third_key_json;
        }
        $this->handleThirdImportRoom($third_import_room, $unique_cut_key);
        
        return $houseArr;
    }

    /**
     * 单个处理楼栋
     * @param $houseArr
     * @param $village_id
     * @param $third_id
     * @param $third_import_room
     * @param $unique_cut_key
     * @return bool|array
     */
    protected function handleRecordSingleData($houseArr, $village_id, $third_id,&$third_import_room, &$unique_cut_key) {
        // 处理楼栋
        $singleArr     = $houseArr['singleArr'];
        $single_number = isset($singleArr['single_number']) && $singleArr['single_number'] ? $singleArr['single_number'] : '';
        $single_name   = isset($singleArr['single_name'])   && $singleArr['single_name']   ? $singleArr['single_name']   : '';
        $whereSingle   = [];
        $whereSingle[] = ['village_id', '=', $village_id];
        $whereSingle[] = ['status', '=', 1];
        $singe_data = [];
        if ($single_number) {
            $whereSingle[] = ['single_number', '=', $single_number];
        } elseif ($single_name) {
            $whereSingle[] = ['single_name', '=', $single_name];
        } else {
            $whereUpdate = [];
            $whereUpdate[] = ['id','=', $third_id];
            $handle_err_json = json_encode(['line'=> __LINE__, 'village_id' => $village_id, 'third_id' => $third_id, 'third_import_room' => $third_import_room], JSON_UNESCAPED_UNICODE);
            $updateData = [
                'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                'handle_msg'      => '缺少对应楼栋信息',
                'handle_err_json' => $handle_err_json,
                'update_time'     => $this->nowTime,
            ];
            $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
            $this->filterRecordErr('缺少对应楼栋信息【房产或房号】');
            return false;
        }
        if (!$this->dbHouseVillageSingle) {
            $this->dbHouseVillageSingle = new HouseVillageSingle();
        }

        $singleInfo = $this->dbHouseVillageSingle->getOne($whereSingle);
        if ($singleInfo && !is_array($singleInfo)) {
            $singleInfo = $singleInfo->toArray();
        }
        if ($single_name) {
            $singe_data['single_name'] = $single_name;
        } elseif ((!isset($singleInfo['single_name']) || !$singleInfo['single_name']) && $single_number) {
            $singe_data['single_name'] = $single_number;
        }
        if ($single_number) {
            $singe_data['single_number'] = $single_number;
        }
        $singe_data['village_id']      = $village_id;
        $singe_data['status']          = 1;
        $singe_data['update_time']     = $this->nowTime;
        if (isset($singleInfo['id'])) {
            $this->dbHouseVillageSingle->saveOne($whereSingle, $singe_data);
            $single_id  = $singleInfo['id'];
        } else {
            $singe_data['third_single_id'] = $third_id;
            $single_id =  $this->dbHouseVillageSingle->addOne($singe_data);
        }
        if (!$single_id) {
            $whereUpdate = [];
            $whereUpdate[] = ['id','=', $third_id];
            $handle_err_json = json_encode(['line'=> __LINE__, 'village_id' => $village_id, 'third_id' => $third_id, 'third_import_room' => $third_import_room], JSON_UNESCAPED_UNICODE);
            $updateData = [
                'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                'handle_msg'      => '楼栋添加失败',
                'handle_err_json' => $handle_err_json,
                'update_time'     => $this->nowTime,
            ];
            $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
            $this->filterRecordErr('楼栋添加失败');
            return false;
        }
        $third_import_room['single_id']   = $single_id;
        $unique_cut_key['single_id']      = $single_id;
        $houseArr['single_id']            = $single_id;
        return $houseArr;
    }

    /**
     * 单个处理单元
     * @param $houseArr
     * @param $village_id
     * @param $third_id
     * @param $third_import_room
     * @param $unique_cut_key
     * @return bool|array
     */
    protected function handleRecordFloorData($houseArr, $village_id, $third_id,&$third_import_room, &$unique_cut_key) {
        $single_id   = isset($houseArr['single_id']) && $houseArr['single_id'] ? $houseArr['single_id'] : '';
        if (!$single_id && isset($third_import_room['single_id']) && $third_import_room['single_id']) {
            $single_id = $third_import_room['single_id'];
        }
        if (!$village_id || !$single_id) {
            $whereUpdate = [];
            $whereUpdate[] = ['id','=', $third_id];
            $handle_err_json = json_encode(['line'=> __LINE__, 'village_id' => $village_id, 'single_id' => $single_id, 'third_id' => $third_id, 'third_import_room' => $third_import_room], JSON_UNESCAPED_UNICODE);
            $updateData = [
                'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                'handle_msg'      => '缺少对应小区或者楼栋信息',
                'handle_err_json' => $handle_err_json,
                'update_time'     => $this->nowTime,
            ];
            $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
            $this->filterRecordErr('缺少对应小区或者楼栋信息【房产或房号】');
            return false;
        } 
        $floorArr     = $houseArr['floorArr'];
        $floor_number = isset($floorArr['floor_number']) && $floorArr['floor_number'] ? $floorArr['floor_number'] : '';
        $floor_name   = isset($floorArr['floor_name'])   && $floorArr['floor_name']   ? $floorArr['floor_name']   : '';
        $whereFloor   = [];
        $whereFloor[] = ['village_id', '=', $village_id];
        $whereFloor[] = ['single_id', '=', $single_id];
        $whereFloor[] = ['status', '=', 1];
        $floor_data = [];
        if ($floor_number) {
            $whereFloor[] = ['floor_number', '=', $floor_number];
        } elseif ($floor_name) {
            $whereFloor[] = ['floor_name', '=', $floor_name];
        } else {
            $whereUpdate = [];
            $whereUpdate[] = ['id','=', $third_id];
            $handle_err_json = json_encode(['line'=> __LINE__, 'village_id' => $village_id, 'single_id' => $single_id, 'third_id' => $third_id, 'third_import_room' => $third_import_room], JSON_UNESCAPED_UNICODE);
            $updateData = [
                'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                'handle_msg'      => '缺少对应单元信息',
                'handle_err_json' => $handle_err_json,
                'update_time'     => $this->nowTime,
            ];
            $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
            $this->filterRecordErr('缺少对应单元信息【房产或房号】');
            return false;
        }
        if (!$this->dbHouseVillageFloor) {
            $this->dbHouseVillageFloor = new HouseVillageFloor();
        }
        $floorInfo = $this->dbHouseVillageFloor->getOne($whereFloor);
        if ($floorInfo && !is_array($floorInfo)) {
            $floorInfo = $floorInfo->toArray();
        }
        if ($floor_name) {
            $floor_data['floor_name'] = $floor_name;
        } elseif ((!isset($floor_name['floor_name']) || !$floor_name['floor_name']) && $floor_number) {
            $floor_data['floor_name'] = $floor_number;
        }
        if ($floor_number) {
            $floor_data['floor_number'] = $floor_number;
        }
        $floor_data['village_id']      = $village_id;
        $floor_data['single_id']       = $single_id;
        $floor_data['status']          = 1;
        if (isset($floorInfo['floor_id'])) {
            $floor_data['update_time']    = $this->nowTime;
            $this->dbHouseVillageFloor->saveOne($whereFloor, $floor_data);
            $floor_id  = $floorInfo['floor_id'];
        } else {
            $floor_data['add_time']       = $this->nowTime;
            $floor_data['third_floor_id'] = $third_id;
            $floor_id =  $this->dbHouseVillageFloor->addOne($floor_data);
        }
        if (!$floor_id) {
            $whereUpdate = [];
            $whereUpdate[] = ['id','=', $third_id];
            $handle_err_json = json_encode(['line'=> __LINE__, 'village_id' => $village_id, 'single_id' => $single_id, 'third_id' => $third_id, 'third_import_room' => $third_import_room], JSON_UNESCAPED_UNICODE);
            $updateData = [
                'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                'handle_msg'      => '单元添加失败',
                'handle_err_json' => $handle_err_json,
                'update_time'     => $this->nowTime,
            ];
            $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
            $this->filterRecordErr('单元添加失败');
            return false;
        }
        $third_import_room['floor_id']    = $floor_id;
        $unique_cut_key['floor_id']       = $floor_id;
        $houseArr['floor_id']             = $floor_id;
        return $houseArr;
    }

    /**
     * 单个处理楼层
     * @param $houseArr
     * @param $village_id
     * @param $third_id
     * @param $third_import_room
     * @param $unique_cut_key
     * @return bool|array
     */
    protected function handleRecordLayerData($houseArr, $village_id, $third_id,&$third_import_room, &$unique_cut_key) {
        $single_id   = isset($houseArr['single_id']) && $houseArr['single_id'] ? $houseArr['single_id'] : '';
        if (!$single_id && isset($third_import_room['single_id']) && $third_import_room['single_id']) {
            $single_id = $third_import_room['single_id'];
        }
        $floor_id    = isset($houseArr['floor_id'])  && $houseArr['floor_id']  ? $houseArr['floor_id']  : '';
        if (!$floor_id && isset($third_import_room['floor_id']) && $third_import_room['floor_id']) {
            $floor_id = $third_import_room['floor_id'];
        }
        $LayerArr     = $houseArr['LayerArr'];
        $layer_number = isset($LayerArr['layer_number']) && $LayerArr['layer_number'] ? $LayerArr['layer_number'] : '';
        $layer_name   = isset($LayerArr['layer_name'])   && $LayerArr['layer_name']   ? $LayerArr['layer_name']   : '';
        $whereLayer   = [];
        $whereLayer[] = ['village_id', '=', $village_id];
        $whereLayer[] = ['single_id', '=', $single_id];
        $whereLayer[] = ['floor_id', '=', $floor_id];
        $whereLayer[] = ['status', '=', 1];
        $layer_data = [];
        if ($layer_number) {
            $whereLayer[] = ['layer_number', '=', $layer_number];
        } elseif ($layer_name) {
            $whereLayer[] = ['layer_name', '=', $layer_name];
        } else {
            $whereUpdate = [];
            $whereUpdate[] = ['id','=', $third_id];
            $handle_err_json = json_encode(['line'=> __LINE__, 'village_id' => $village_id, 'single_id' => $single_id, 'third_id' => $third_id, 'third_import_room' => $third_import_room], JSON_UNESCAPED_UNICODE);
            $updateData = [
                'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                'handle_msg'      => '缺少对应楼层信息',
                'handle_err_json' => $handle_err_json,
                'update_time'     => $this->nowTime,
            ];
            $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
            $this->filterRecordErr('缺少对应楼层信息【房产或房号】');
            return false;
        }
        if (!$this->dbHouseVillageLayer) {
            $this->dbHouseVillageLayer = new HouseVillageLayer();
        }

        $layerInfo = $this->dbHouseVillageLayer->getOne($whereLayer);
        if ($layerInfo && !is_array($layerInfo)) {
            $layerInfo = $layerInfo->toArray();
        }
        if ($layer_name) {
            $layer_data['layer_name'] = $layer_name;
        } elseif ((!isset($layerInfo['layer_name']) || !$layerInfo['layer_name']) && $layer_number) {
            $layer_data['layer_name'] = $layer_number;
        }
        if ($layer_number) {
            $layer_data['layer_number'] = $layer_number;
        }
        $layer_data['village_id']      = $village_id;
        $layer_data['single_id']       = $single_id;
        $layer_data['floor_id']        = $floor_id;
        $layer_data['status']          = 1;
        if (isset($layerInfo['id'])) {
            $this->dbHouseVillageLayer->saveOne($whereLayer, $layer_data);
            $layer_id  = $layerInfo['id'];
        } else {
            $layer_data['add_time']       = $this->nowTime;
            $layer_data['third_layer_id'] = $third_id;
            $layer_id =  $this->dbHouseVillageLayer->addOne($layer_data);
        }
        if (!$layer_id) {
            $whereUpdate = [];
            $whereUpdate[] = ['id','=', $third_id];
            $handle_err_json = json_encode(['line'=> __LINE__, 'village_id' => $village_id, 'single_id' => $single_id, 'third_id' => $third_id, 'third_import_room' => $third_import_room], JSON_UNESCAPED_UNICODE);
            $updateData = [
                'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                'handle_msg'      => '楼层添加失败',
                'handle_err_json' => $handle_err_json,
                'update_time'     => $this->nowTime,
            ];
            $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
            $this->filterRecordErr('楼层添加失败【房产或房号】');
            return false;
        }
        $third_import_room['layer_id']    = $layer_id;
        $unique_cut_key['layer_id']       = $layer_id;
        $houseArr['layer_id']             = $layer_id;
        return $houseArr;
    }

    /**
     * 单个处理房屋
     * @param $houseArr
     * @param $village_id
     * @param $third_id
     * @param $third_import_room
     * @param $unique_cut_key
     * @return bool|array
     */
    protected function handleRecordRoomData($houseArr, $village_id, $third_id,&$third_import_room, &$unique_cut_key) {
        $single_id   = isset($houseArr['single_id']) && $houseArr['single_id'] ? $houseArr['single_id'] : '';
        if (!$single_id && isset($third_import_room['single_id']) && $third_import_room['single_id']) {
            $single_id = $third_import_room['single_id'];
        }
        $floor_id    = isset($houseArr['floor_id'])  && $houseArr['floor_id']  ? $houseArr['floor_id']  : '';
        if (!$floor_id && isset($third_import_room['floor_id']) && $third_import_room['floor_id']) {
            $floor_id = $third_import_room['floor_id'];
        }
        $layer_id    = isset($houseArr['layer_id'])  && $houseArr['layer_id']  ? $houseArr['layer_id']  : '';
        if (!$layer_id && isset($third_import_room['layer_id']) && $third_import_room['layer_id']) {
            $layer_id = $third_import_room['layer_id'];
        }
        if (!$village_id || !$single_id || !$floor_id || !$layer_id) {
            $whereUpdate = [];
            $whereUpdate[] = ['id','=', $third_id];
            $handle_err_json = json_encode(['line'=> __LINE__, 'village_id' => $village_id, 'single_id' => $single_id, 'floor_id' => $floor_id, 'layer_id' => $layer_id], JSON_UNESCAPED_UNICODE);
            $updateData = [
                'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                'handle_msg'      => "缺少对应上级信息",
                'handle_err_json' => $handle_err_json,
                'update_time'     => $this->nowTime,
            ];
            $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
            return false;
        }
        
        $roomArr     = $houseArr['roomArr'];
        $room_number = isset($roomArr['room_number']) && $roomArr['room_number'] ? $roomArr['room_number'] : '';
        $room_name   = isset($roomArr['room'])        && $roomArr['room']        ? $roomArr['room']        : '';
        $whereRoom   = [];
        $whereRoom[] = ['village_id', '=', $village_id];
        $whereRoom[] = ['single_id', '=', $single_id];
        $whereRoom[] = ['floor_id', '=', $floor_id];
        $whereRoom[] = ['layer_id', '=', $layer_id];
        $whereRoom[] = ['status', '<>', 4];
        $whereRoom[] = ['is_del', '=', 0];
        $room_data = [];
        if ($room_number) {
            $whereRoom[] = ['room_number', '=', $room_number];
        } elseif ($room_name) {
            $whereRoom[] = ['room', '=', $room_name];
        } else {
            $whereUpdate = [];
            $whereUpdate[] = ['id','=', $third_id];
            $handle_err_json = json_encode($roomArr, JSON_UNESCAPED_UNICODE);
            $updateData = [
                'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                'handle_msg'      => "缺少对应房屋信息",
                'handle_err_json' => $handle_err_json,
                'update_time'     => $this->nowTime,
            ];
            $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
            return false;
        }
        if (!$this->dbHouseVillageUserVacancy) {
            $this->dbHouseVillageUserVacancy = new HouseVillageUserVacancy();
        }

        $roomInfo = $this->dbHouseVillageUserVacancy->getOne($whereRoom);
        if ($roomInfo && !is_array($roomInfo)) {
            $roomInfo = $roomInfo->toArray();
        }
        if ($room_name) {
            $room_data['room'] = $room_name;
        } elseif ((!isset($roomInfo['room']) || !$roomInfo['room']) && $room_number) {
            $room_data['room'] = $room_number;
        }
        if ($room_number) {
            $room_data['room_number'] = $room_number;
        }
        $room_data['village_id']      = $village_id;
        $room_data['single_id']       = $single_id;
        $room_data['floor_id']        = $floor_id;
        $room_data['layer_id']        = $layer_id;
        $room_data['status']          = 1;

        if (isset($roomArr['user_status'])) {
            $room_data['user_status']     = $roomArr['user_status'];
        }
        if (isset($roomArr['house_type'])) {
            $room_data['house_type']      = $roomArr['house_type'];
            $third_import_room['house_type']    = $roomArr['house_type'];
        }
        if (isset($roomArr['housesize'])) {
            $room_data['housesize']       = $roomArr['housesize'];
        }
        $usernum = $village_id.'_'.$single_id.'_'.$floor_id.'_'.$layer_id;
        $save = false;
        if (isset($roomInfo['pigcms_id'])) {
            if (isset($roomInfo['usernum']) && !$roomInfo['usernum']) {
                $room_data['usernum']          = $usernum.'_'.$roomInfo['pigcms_id'];
            }
            $this->dbHouseVillageUserVacancy->saveOne($whereRoom, $room_data);
            $room_id  = $roomInfo['pigcms_id'];
        } else {
            $room_data['usernum']          = $usernum;
            $room_data['add_time']         = $this->nowTime;
            $room_data['vacancy_third_id'] = $third_id;
            $room_id =  $this->dbHouseVillageUserVacancy->addOne($room_data);
            $save = true;
        }
        if (!$room_id) {
            $whereUpdate = [];
            $whereUpdate[] = ['id','=', $third_id];
            $handle_err_json = json_encode($room_data, JSON_UNESCAPED_UNICODE);
            $updateData = [
                'handle_status'   => thirdImportDataConst::HANDLE_STATUS_FAIL,
                'handle_msg'      => '楼层房屋失败',
                'handle_err_json' => $handle_err_json,
                'update_time'     => $this->nowTime,
            ];
            $this->dbHouseVillageThirdImportData->updateThis($whereUpdate, $updateData);
            return false;
        }
        if ($save) {
            $usernum = $village_id.'_'.$single_id.'_'.$floor_id.'_'.$layer_id.'_'.$room_id;
            $whereRoomSave = [];
            $whereRoomSave['pigcms_id'] = $room_id;
            $this->dbHouseVillageUserVacancy->saveOne($whereRoomSave, ['usernum' => $usernum]);
        }
        
        $third_import_room['room_id']    = $room_id;
        $unique_cut_key['room_id']       = $room_id;

        if (isset($roomArr['house_status'])) {
            $third_import_room['house_status']    = $roomArr['house_status'];
        }
        if (isset($roomArr['house_nature'])) {
            $third_import_room['house_nature']    = $roomArr['house_nature'];
        }
        
        $houseArr['room_id']             = $room_id;
        return $houseArr;
    }

    /**
     * @param $third_key_json
     * @param $whereThirdKey
     * @return array|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function getThirdImportRoom($third_key_json, $whereThirdKey = []) {
        if (!$this->dbHouseVillageThirdImportRoom) {
            $this->dbHouseVillageThirdImportRoom = new HouseVillageThirdImportRoom();
        }
        if (!empty($whereThirdKey)) {
            $repeatInfo = $this->dbHouseVillageThirdImportRoom->getOne($whereThirdKey, true, 'bind_id DESC');
            if ($repeatInfo && !is_array($repeatInfo)) {
                $repeatInfo = $repeatInfo->toArray();
            }
        }
        if (!isset($repeatInfo) || empty($repeatInfo)) {
            $whereUnique = [];
            $whereUnique[] = ['third_key_json', '=', $third_key_json];
            $repeatInfo = $this->dbHouseVillageThirdImportRoom->getOne($whereUnique, true, 'bind_id DESC');
            if ($repeatInfo && !is_array($repeatInfo)) {
                $repeatInfo = $repeatInfo->toArray();
            }
        }
        if (empty($repeatInfo)) {
            return [];
        }
        if (!$this->dbHouseVillageSingle) {
            $this->dbHouseVillageSingle = new HouseVillageSingle();
        }
        $whereSingle   = [];
        $whereSingle[] = ['id',      '=', $repeatInfo['single_id']];
        $singleInfo = $this->dbHouseVillageSingle->getOne($whereSingle, 'id, status');
        if ($singleInfo && !is_array($singleInfo)) {
            $singleInfo = $singleInfo->toArray();
        }
        if (empty($singleInfo) || !isset($singleInfo['id']) || $singleInfo['status'] != 1) {
            $this->filterRecordErr('对应楼栋被删除或者被禁用');
            return [];
        }
        if (!$this->dbHouseVillageFloor) {
            $this->dbHouseVillageFloor = new HouseVillageFloor();
        }
        $whereFloor   = [];
        $whereFloor[] = ['floor_id',      '=', $repeatInfo['floor_id']];
        $floorInfo = $this->dbHouseVillageFloor->getOne($whereFloor, 'floor_id, status');
        if ($floorInfo && !is_array($floorInfo)) {
            $floorInfo = $floorInfo->toArray();
        }
        if (empty($floorInfo) || !isset($floorInfo['floor_id']) || $floorInfo['status'] != 1) {
            $this->filterRecordErr('对应单元被删除或者被禁用');
            return [];
        }
        if (!$this->dbHouseVillageLayer) {
            $this->dbHouseVillageLayer = new HouseVillageLayer();
        }
        $whereLayer   = [];
        $whereLayer[] = ['id',      '=', $repeatInfo['layer_id']];
        $layerInfo = $this->dbHouseVillageLayer->getOne($whereLayer, 'id, status');
        if ($layerInfo && !is_array($layerInfo)) {
            $layerInfo = $layerInfo->toArray();
        }
        if (empty($layerInfo) || !isset($layerInfo['id']) || $layerInfo['status'] != 1) {
            $this->filterRecordErr('对应楼层被删除或者被禁用');
            return [];
        }
        if (!$this->dbHouseVillageUserVacancy) {
            $this->dbHouseVillageUserVacancy = new HouseVillageUserVacancy();
        }
        $whereRoom   = [];
        $whereRoom[] = ['pigcms_id',      '=', $repeatInfo['room_id']];
        $roomInfo = $this->dbHouseVillageUserVacancy->getOne($whereRoom, 'pigcms_id, status, is_del');
        if ($roomInfo && !is_array($roomInfo)) {
            $roomInfo = $roomInfo->toArray();
        }
        if (empty($roomInfo) || !isset($roomInfo['pigcms_id']) || $roomInfo['status'] == 4 || $roomInfo['is_del'] == 1) {
            $this->filterRecordErr('对应房屋被删除或者被禁用');
            return [];
        }
        return $repeatInfo;
    }
    
    /**
     * 房间处理完毕记录关联关系
     * @param $third_import_room
     * @param $unique_cut_key_arr
     * @return bool
     */
    protected function handleThirdImportRoom($third_import_room, $unique_cut_key_arr) {
        // 区分唯一值
        $unique_cut_key = md5(json_encode($unique_cut_key_arr, JSON_UNESCAPED_UNICODE));
        $third_import_room['unique_cut_key']                = $unique_cut_key;

        if (!$this->nowTime) {
            $this->nowTime = time();
        }

        if (!$this->dbHouseVillageThirdImportRoom) {
            $this->dbHouseVillageThirdImportRoom = new HouseVillageThirdImportRoom();
        }
        if (isset($third_import_park['third_key_json']) && $third_import_park['third_key_json']) {
            $whereUnique = [];
            $whereUnique[] = ['third_key_json', '=', $third_import_park['third_key_json']];
            $repeatInfo = $this->dbHouseVillageThirdImportRoom->getOne($whereUnique);
            if ($repeatInfo && !is_array($repeatInfo)) {
                $repeatInfo = $repeatInfo->toArray();
            }
        }

        if (!isset($repeatInfo) || empty($repeatInfo) || !isset($repeatInfo['bind_id'])) {
            $whereUnique = [];
            $whereUnique[] = ['unique_cut_key', '=', $unique_cut_key];
            $repeatInfo = $this->dbHouseVillageThirdImportRoom->getOne($whereUnique);

            if ($repeatInfo && !is_array($repeatInfo)) {
                $repeatInfo = $repeatInfo->toArray();
            }
        }
        
        if (isset($repeatInfo['bind_id'])) {
            $third_import_room['update_time'] = $this->nowTime;
            $this->dbHouseVillageThirdImportRoom->updateThis($whereUnique, $third_import_room);
        } else {
            $third_import_room['add_time'] = $this->nowTime;
            $this->dbHouseVillageThirdImportRoom->add($third_import_room);
        }
        return true;
    }
    
    /**
     * 针对房产三段时地址处理
     * @param array $third_address_arr
     * @param integer $cut_id
     * @param array $third_import_data
     * @param array $houseArr
     * @return array
     */
    protected function thirdAddress3Handle(array $third_address_arr,int $cut_id, $third_import_data = [], $houseArr = []) {
        $this->commonThirdAddressHandle($third_import_data);
        $houseArr  = $this->singleHandle($third_address_arr, $houseArr);
        $floorArr = [
            'floor_name'    => '1',
            'floor_number'  => '01',
        ];
        $houseArr['floorArr']  = $floorArr;

        $houseArr  = $this->roomHandle($third_address_arr, trim($third_address_arr[2]), $houseArr);
        $houseArr['cut_table'] = 'house_village_third_import_data';
        $houseArr['cut_id']    = $cut_id;
        return $houseArr;
    }

    /**
     * 针对房产四段时地址处理
     * @param array $third_address_arr
     * @param integer $cut_id
     * @param array $third_import_data
     * @param array $houseArr
     * @return array
     */
    protected function thirdAddress4Handle(array $third_address_arr,int $cut_id, $third_import_data = [], $houseArr = []) {
        $this->commonThirdAddressHandle($third_import_data);
        $houseArr  = $this->singleHandle($third_address_arr, $houseArr);
        $houseArr  = $this->floorHandle($third_address_arr, $houseArr);
        $houseArr  = $this->roomHandle($third_address_arr, trim($third_address_arr[3]), $houseArr);
        $houseArr['cut_table'] = 'house_village_third_import_data';
        $houseArr['cut_id']    = $cut_id;
        return $houseArr;
    }

    /**
     * 针对房产五段时地址处理
     * @param array $third_address_arr
     * @param integer $cut_id
     * @param array $third_import_data
     * @param array $houseArr
     * @return array
     */
    protected function thirdAddress5Handle(array $third_address_arr,int $cut_id, $third_import_data = [], $houseArr = []) {
        $this->commonThirdAddressHandle($third_import_data);
        $houseArr  = $this->singleHandle($third_address_arr, $houseArr);
        $houseArr  = $this->floorHandle($third_address_arr, $houseArr);
        $houseArr  = $this->layerHandle($third_address_arr, $houseArr);
        $houseArr  = $this->roomHandle($third_address_arr, trim($third_address_arr[4]), $houseArr);
        $houseArr['cut_table'] = 'house_village_third_import_data';
        $houseArr['cut_id']    = $cut_id;
        return $houseArr;
    }


    
    
    protected function commonThirdAddressHandle($third_import_data = []) {
        $this->third_main_status_txt = isset($third_import_data['third_main_status_txt']) ? $third_import_data['third_main_status_txt'] : '';
        $this->third_type1_txt       = isset($third_import_data['third_type1_txt'])       ? $third_import_data['third_type1_txt']       : '';
        $this->third_type2_txt       = isset($third_import_data['third_type2_txt'])       ? $third_import_data['third_type2_txt']       : '';
        $this->third_main_status     = isset($third_import_data['third_main_status'])     ? $third_import_data['third_main_status']     : '-1';
        $this->third_type1           = isset($third_import_data['third_type1'])           ? $third_import_data['third_type1']           : '-1';
        $this->third_type2           = isset($third_import_data['third_type2'])           ? $third_import_data['third_type2']           : '-1';
        $this->third_main_value1     = isset($third_import_data['third_main_value1'])     ? $third_import_data['third_main_value1']     : '';
        $this->third_main_value2     = isset($third_import_data['third_main_value2'])     ? $third_import_data['third_main_value2']     : '';
        $this->third_main_value3     = isset($third_import_data['third_main_value3'])     ? $third_import_data['third_main_value3']     : '';
        $this->third_unit_name       = isset($third_import_data['third_unit_name'])       ? $third_import_data['third_unit_name']       : '';
        $this->third_unit_price      = isset($third_import_data['third_unit_price'])      ? $third_import_data['third_unit_price']      : '';
        $this->third_address         = isset($third_import_data['third_address'])         ? $third_import_data['third_address']         : '';
    }

    /**
     * 处理楼栋
     * @param $third_address_arr
     * @param array $houseArr
     * @param string $single_name
     * @return array|mixed
     */
    protected function singleHandle($third_address_arr, $houseArr = [], $single_name = '') {
        if (!$single_name) {
            $single_name   = trim($third_address_arr[1]);
        }
        $single_number = preg_replace($this->pattern_number,'',$single_name);
        if (intval($single_number)>0 && strlen($single_number)<2) {
            // 不足2位 补足2位
            $single_number = str_pad($single_number,2,"0",STR_PAD_LEFT);
        }
        $singleArr =  [
            'single_name'   => $single_name,
            'single_number' => $single_number ? $single_number : '',
        ];
        $houseArr['singleArr'] = $singleArr;
        return $houseArr;
    }

    /**
     * 处理单元
     * @param $third_address_arr
     * @param array $houseArr
     * @param string $floor_name
     * @return array
     */
    protected function floorHandle($third_address_arr, $houseArr = [], $floor_name = '') {
        if (!$floor_name) {
            $floor_name    = trim($third_address_arr[2]);
        }
        $floor_number  = preg_replace($this->pattern_number,'',$floor_name);
        if (intval($floor_number)>0 && strlen($floor_number)<2) {
            // 不足2位 补足2位
            $floor_number = str_pad($floor_number,2,"0",STR_PAD_LEFT);
        }
        $floorArr = [
            'floor_name'    => $floor_name,
            'floor_number'  => $floor_number,
        ];
        $houseArr['floorArr'] = $floorArr;
        return $houseArr;
    }


    /**
     * 处理楼层
     * @param $third_address_arr
     * @param array $houseArr
     * @param string $layer_name
     * @return array
     */
    protected function layerHandle($third_address_arr, $houseArr = [], $layer_name = '') {
        if (!$layer_name) {
            $layer_name    = trim($third_address_arr[3]);
        }
        $layer_number  = preg_replace($this->pattern_number,'',$layer_name);
        if (intval($layer_number)>0 && strlen($layer_number)<2) {
            // 不足2位 补足2位
            $layer_number = str_pad($layer_number,2,"0",STR_PAD_LEFT);
        }
        $floorArr = [
            'layer_name'    => $layer_name,
            'layer_number'  => $layer_number,
        ];
        $houseArr['floorArr'] = $floorArr;
        return $houseArr;
    }
    

    /**
     * 处理房屋+楼层
     * @param $third_address_arr
     * @param string $keyRoom
     * @param array $houseArr
     * @return array
     */
    protected function roomHandle($third_address_arr, $keyRoom='', $houseArr = []) {
        if (!$keyRoom && count($third_address_arr)==2) {
            $keyRoom           = trim($third_address_arr[1]);
        } elseif (!$keyRoom && count($third_address_arr)==3) {
            $keyRoom           = trim($third_address_arr[2]);
        } elseif (!$keyRoom && count($third_address_arr)==4) {
            $keyRoom           = trim($third_address_arr[3]);
        } elseif (!$keyRoom && count($third_address_arr)==5) {
            $keyRoom           = trim($third_address_arr[4]);
        }
        $roomExplodeArr = explode('-', $keyRoom);
        $room           = array_pop($roomExplodeArr);
        $length         = strlen($room) - 2;
        if (isset($houseArr['layer_name']) && $houseArr['layer_name']) {
            $LayerArr = [
                'layer_name'    => $houseArr['layer_name'],
                'layer_number'  => $houseArr['layer_number'],
            ];
        } else if ($length>0) {
            $layer_name     = substr($room, 0,$length);
            $layer_number   = preg_replace($this->pattern_number,'',$layer_name);
            if (intval($layer_number)>0 && strlen($layer_number)<2) {
                // 不足2位 补足2位
                $layer_number = str_pad($layer_number,2,"0",STR_PAD_LEFT);
            }
            $LayerArr = [
                'layer_name'    => $layer_name,
                'layer_number'  => $layer_number,
            ];
        } else {
            $LayerArr = [
                'layer_name'    => 1,
                'layer_number'  => '01',
            ];
        }
        $room_number   = preg_replace($this->pattern_number,'',$room);
        if (intval($room_number)>0 && strlen($room_number)<2) {
            // 不足2位 补足2位
            $room_number = str_pad($room_number,2,"0",STR_PAD_LEFT);
        }
        $roomArr = [
            'room'        => $room,
            'room_number' => $room_number,
        ];
        if ($this->third_main_status_txt) {
            $user_status = $this->getUserStatus($this->third_main_status_txt);
            if ($user_status) {
                $roomArr['user_status'] = $user_status;
            }
            if (!isset($this->third_main_status) || $this->third_main_status == '-1') {
                $this->third_main_status = $this->getHouseStatus($this->third_main_status_txt);
            }
            if ($this->third_main_status) {
                $roomArr['house_status'] = $this->third_main_status;
            }
        }
        if ($this->third_type1_txt) {
            if (!isset($this->third_type1) || $this->third_type1 == '-1') {
                $this->third_type1 = $this->getHouseType($this->third_type1_txt);
            }
            if ($this->third_type1) {
                $roomArr['house_type'] = $this->third_type1;
            }
        }
        if ($this->third_type2_txt) {
            if (!isset($third_type2) || $third_type2 == '-1') {
                $third_type2 = $this->getHouseNature($this->third_type2_txt);
            }
            if ($third_type2) {
                $roomArr['house_nature'] = $third_type2;
            }
        }
        if (floatval($this->third_main_value2) > 0) {
            $roomArr['housesize'] = $this->third_main_value2;
        }
        $houseArr['LayerArr']  = $LayerArr;
        $houseArr['roomArr']   = $roomArr;
        return $houseArr;
    }
    
    // 获取房间关联导入信息
    public function getThirdImportRoomInfo($village_id, $room_id) {
        if (!$village_id || !$room_id) {
            return [];
        }
        if (!$this->dbHouseVillageThirdImportRoom) {
            $this->dbHouseVillageThirdImportRoom = new HouseVillageThirdImportRoom();
        }
        if (!$this->dbHouseVillageThirdImportData) {
            $this->dbHouseVillageThirdImportData = new HouseVillageThirdImportData();
        }
        $where = [];
        $where[] = ['village_id', '=', $village_id];
        $where[] = ['room_id',    '=', $room_id];
        $where[] = ['cut_type',   '=', 'room'];
        $info = $this->dbHouseVillageThirdImportRoom->getOne($where, true, 'bind_id DESC');
        if ($info && !is_array($info)) {
            $info = $info->toArray();
        }
        if (empty($info)) {
            return [];
        }
        $showArr = [];
        if (isset($info['house_status'])) {
            $house_status_txt = $this->getHouseStatusTxt($info['house_status']);
            if ($house_status_txt) {
                $showArr[] = [
                    'title' => '房产状态',
                    'value' => $house_status_txt,
                ];
            } else {
                $showArr[] = [
                    'title' => '房产状态',
                    'value' => '无',
                ];
            }
        }
        if (isset($info['house_type'])) {
            $house_type_txt = $this->getHouseTypeTxt($info['house_type']);
            if ($house_type_txt) {
                $showArr[] = [
                    'title' => '房产类型',
                    'value' => $house_type_txt,
                ];
            } else {
                $showArr[] = [
                    'title' => '房产类型',
                    'value' => '无',
                ];
            }
        }
        if (isset($info['house_nature'])) {
            $house_nature_txt = $this->getHouseNatureTxt($info['house_nature']);
            if ($house_nature_txt) {
                $showArr[] = [
                    'title' => '房产性质',
                    'value' => $house_nature_txt,
                ];
            } else {
                $showArr[] = [
                    'title' => '房产性质',
                    'value' => '无',
                ];
            }
        }
        if (isset($info['cut_table']) && isset($info['cut_id']) && $info['cut_id'] && $info['cut_table'] == 'house_village_third_import_data') {
            $whereThirdImportData = [];
            $whereThirdImportData[] = ['id', '=', $info['cut_id']];
            $cutInfo = $this->dbHouseVillageThirdImportData->getOne($whereThirdImportData);
            if ($cutInfo && !is_array($cutInfo)) {
                $cutInfo = $cutInfo->toArray();
            }
            if (isset($cutInfo['third_main_value1'])) {
                $showArr[] = [
                    'title' => '建筑面积',
                    'value' => $cutInfo['third_main_value1'] ? $cutInfo['third_main_value1'] : 0,
                ];
            }
            if (isset($cutInfo['third_main_value3'])) {
                $showArr[] = [
                    'title' => '辅助计费面积',
                    'value' => $cutInfo['third_main_value3'] ? $cutInfo['third_main_value3'] : 0,
                ];
            }
            if (isset($cutInfo['third_remark'])) {
                $showArr[] = [
                    'title' => '备注',
                    'value' => $cutInfo['third_remark'] ? $cutInfo['third_remark'] : '无',
                ];
            }
        }
        if (isset($info['third_key'])) {
            $showArr[] = [
                'title' => '关键字',
                'value' => $info['third_key'] ? $info['third_key'] : '无',
            ];
        }
        $arr = [];
        $arr['showArr'] = $showArr;
        return $arr;
    }
    
    // 获取住户关联导入信息
    public function getThirdImportUserInfo($village_id, $bind_id) {
        if (!$village_id || !$bind_id) {
            return [];
        }
        if (!$this->dbHouseVillageUserBindRelation) {
            $this->dbHouseVillageUserBindRelation = new HouseVillageUserBindRelation();
        }
        $where = [];
        $where[] = ['village_id',       '=', $village_id];
        $where[] = ['relation_bind_id', '=', $bind_id];
        $where[] = ['from_type',        '=',  thirdImportDataConst::THIRD_YWYL_OWNER_EXCEL];
        $info = $this->dbHouseVillageUserBindRelation->getOne($where, true, 'add_time DESC');
        if ($info && !is_array($info)) {
            $info = $info->toArray();
        }
        if (empty($info)) {
            return [];
        }
        $showArr = [];
        if (isset($info['sex'])) {
            $member_sex = $this->getMemberSexTxt($info['sex']);
            if ($member_sex) {
                $showArr[] = [
                    'title' => '性別',
                    'value' => $member_sex,
                ];
            }
        }
        if (isset($info['nation']) && $info['nation']) {
            $showArr[] = [
                'title' => '民族',
                'value' => $info['nation'],
            ];
        }
        if (isset($info['special_identity']) && $info['special_identity']) {
            $showArr[] = [
                'title' => '特殊身份',
                'value' => $info['special_identity'],
            ];
        }
        if (isset($info['native_place']) && $info['native_place']) {
            $showArr[] = [
                'title' => '籍贯',
                'value' => $info['native_place'],
            ];
        }
        if (isset($info['birthday']) && $info['birthday']) {
            $showArr[] = [
                'title' => '生日',
                'value' => $info['birthday'],
            ];
        }
        if (isset($info['marital_status']) && $info['marital_status']) {
            $showArr[] = [
                'title' => '成员婚姻状况',
                'value' => $info['marital_status'],
            ];
        }
        if (isset($info['card_type'])) {
            $card_type_txt = $this->getCardTypeTxt($info['card_type']);
            if ($card_type_txt) {
                $showArr[] = [
                    'title' => '证件类型',
                    'value' => $card_type_txt,
                ];
                $showArr[] = [
                    'title' => '证件号码',
                    'value' => $info['card'],
                ];
            }
        }
        if (isset($info['education_level']) && $info['education_level']) {
            $showArr[] = [
                'title' => '教育程度',
                'value' => $info['education_level'],
            ];
        }
        if (isset($info['nationality']) && $info['nationality']) {
            $showArr[] = [
                'title' => '国籍',
                'value' => $info['nationality'],
            ];
        }
        if (isset($info['registered_residence']) && $info['registered_residence']) {
            $showArr[] = [
                'title' => '户口所在地',
                'value' => $info['registered_residence'],
            ];
        }
        if (isset($info['province']) && $info['province']) {
            $showArr[] = [
                'title' => '省份',
                'value' => $info['province'],
            ];
        }
        if (isset($info['city']) && $info['city']) {
            $showArr[] = [
                'title' => '城市',
                'value' => $info['city'],
            ];
        }
        if (isset($info['settlement_city']) && $info['settlement_city']) {
            $showArr[] = [
                'title' => '定居城市',
                'value' => $info['settlement_city'],
            ];
        }
        if (isset($info['resident_type']) && $info['resident_type']) {
            $showArr[] = [
                'title' => '常驻类型',
                'value' => $info['resident_type'],
            ];
        }
        if (isset($info['home_address']) && $info['home_address']) {
            $showArr[] = [
                'title' => '家庭住址',
                'value' => $info['home_address'],
            ];
        }
        if (isset($info['emergency_contact']) && $info['emergency_contact']) {
            $showArr[] = [
                'title' => '紧急联系人',
                'value' => $info['emergency_contact'],
            ];
        }
        if (isset($info['emergency_phone']) && $info['emergency_phone']) {
            $showArr[] = [
                'title' => '紧急联系电话',
                'value' => $info['emergency_phone'],
            ];
        }
        $arr = [];
        $arr['showArr'] = $showArr;
        return $arr;
    }

    // 获取订单关联导入信息
    public function getThirdImportOrderInfo($village_id, $order_id) {
        if (!$village_id || !$order_id) {
            return [];
        }
        if (!$this->dbHouseVillageThirdImportOrder) {
            $this->dbHouseVillageThirdImportOrder = new HouseVillageThirdImportOrder();
        }
        $where = [];
        $where[] = ['cut_table',  '=', 'house_village_third_import_data'];
        $where[] = ['village_id', '=', $village_id];
        $where[] = ['order_id',   '=', $order_id];
        $info = $this->dbHouseVillageThirdImportOrder->getOne($where, true, 'bind_id DESC');
        if ($info && !is_array($info)) {
            $info = $info->toArray();
        }
        if (empty($info)) {
            return [];
        }
        $showArr = [];
        if (isset($info['charge_amount_on_account']) && $info['charge_amount_on_account']) {
            $showArr[] = [
                'title' => '挂账金额',
                'value' => $info['charge_amount_on_account'],
            ];
        } else {
            $showArr[] = [
                'title' => '挂账金额',
                'value' => 0,
            ];
        }
        if (isset($info['charge_amount_owed']) && $info['charge_amount_owed']) {
            $showArr[] = [
                'title' => '欠费金额',
                'value' => $info['charge_amount_owed'],
            ];
        } else {
            $showArr[] = [
                'title' => '欠费金额',
                'value' => 0,
            ];
        }
        if (isset($info['charge_received_amount']) && $info['charge_received_amount']) {
            $showArr[] = [
                'title' => '已收金额',
                'value' => $info['charge_received_amount'],
            ];
        } else {
            $showArr[] = [
                'title' => '已收金额',
                'value' => 0,
            ];
        }
        if (isset($info['charge_actual_receivable']) && $info['charge_actual_receivable']) {
            $showArr[] = [
                'title' => '实际应收金额',
                'value' => $info['charge_actual_receivable'],
            ];
        } else {
            $showArr[] = [
                'title' => '实际应收金额',
                'value' => 0,
            ];
        }
        if (isset($info['charge_amount_receivable']) && $info['charge_amount_receivable']) {
            $showArr[] = [
                'title' => '应收金额',
                'value' => $info['charge_amount_receivable'],
            ];
        } else {
            $showArr[] = [
                'title' => '应收金额',
                'value' => 0,
            ];
        }
        $arr = [];
        $arr['showArr'] = $showArr;
        return $arr;
    }
}