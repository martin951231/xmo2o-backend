<?php

namespace app\command;

use app\common\ChunkReadFilter;
use app\community\model\db\HouseNewChargeNumber;
use app\community\model\db\HouseNewChargeProject;
use app\community\model\db\HouseNewChargeRule;
use app\community\model\db\HouseNewChargeStandardBind;
use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageFloor;
use app\community\model\db\HouseVillageLayer;
use app\community\model\db\HouseVillageSingle;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\HouseVillageBindPosition;
use app\community\model\db\HouseVillageParkingGarage;
use app\community\model\db\HouseVillageParkingPosition;
use app\community\model\db\HouseWorker;
use app\community\model\service\HouseNewCashierService;
use app\community\model\service\HouseNewChargeRuleService;
use app\community\model\service\HouseNewChargeService;
use app\community\model\service\HouseVillageConfigService;
use app\community\model\service\HouseVillageSingleService;
use app\community\model\service\HouseVillageUserBindService;
use app\community\model\service\ImportExcelService;
use app\community\model\service\NewBuildingService;
use app\community\model\db\HouseNewPayOrderSummary;
use app\community\model\db\HouseVillageUserBind;
use app\traits\CacheTypeTraits;
use app\traits\CommonTraits;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

class BillExcelImport extends Command
{

    use CacheTypeTraits,CommonTraits;

    protected function configure()
    {
        // 指令配置
        $this->setName('billExcelImport')
            ->addArgument('json',Argument::IS_ARRAY)
            ->setDescription('欠费账单命令行导入指令');
    }
    protected function execute(Input $input, Output $output)
    {
        set_time_limit(0);
        ini_set("memory_limit","512M");
        $ImportExcelService=new ImportExcelService();
        $NewBuildingService=new NewBuildingService();
        $begin_time=$ImportExcelService->getMillisecondMethod(); //开始时间
        /**
         * 1、接收参数 、绑定参数
         * 2、按照映射关系写入库
         * 3、然后使用 Redis 反馈实时导入进度
         * 4、记录一下导入数据变更记录和 整理导入错误的文件
         */
        $json   = $input->getArgument('json');
        $params = \json_decode($json[0],true);
        fdump_api(['接收参数=='.__LINE__,$json,$params],'import_excel/billExcelImportExecute',1);
        if (isset($params['type']) && in_array($params['type'], ['cycleBillPayExcel', 'parkingBillPayExcel'])) {
            return $this->executeCycleBillPayExcel($params);
        }
        if (isset($params['type']) && $params['type'] == 'cycleBillExcel') {
            return $this->executeCycleBillExcel($params);
        }

        $inputFileName             = $params['inputFileName']; //文件路径
        $selectWorkSheetIndex      = $params['selectWorkSheetIndex']; //选择的选择的Sheet索引
        $worksheetName             = $params['worksheetName']; //选择的选择的Sheet表
        $nowVillageInfo            = $params['nowVillageInfo']; //小区信息
        $subjectId                 = $params['subjectId']; //收费科目id
        $projectId                 = $params['projectId']; //收费项目id
        $village_id                = $nowVillageInfo['village_id'];
        $village_info = (new HouseVillage())->getOne($village_id,'property_id');
        $property_id = isset($village_info['property_id']) ? intval($village_info['property_id']) : 0;
        //使用Redis缓存 进度 
        $processCacheKey=$ImportExcelService->getProcessCacheKeyMethod($village_id,$params['type']);
        $processInfo = [
            'process'            => 0, //进度
            'readRowTotal'       => 0, //总共有数据行数
            'successInsterTotal' => 0, //总共插入表成功条数
            'errorTotal'         => 0, //总共插入表失败条数
        ];
        try {
            $this->queueReids()->set($processCacheKey,$processInfo,3600);
        }catch (\RedisException $e){
            //TODO 应该直接通过 队列 压入对应的业务日志里
            fdump_api(['$processCacheKey设置失败=='.__LINE__,$processCacheKey,$processInfo,$params,$e->getMessage()],'import_excel/billExcelImportExecuteError',1);
        }
        $inputFileName = root_path() . '/../'.$inputFileName;
        $check_number = "/^[0-9]+$/";
        $max_num = 99;
        $reader=$ImportExcelService->getFileReaderMethod($inputFileName,false);
        $spreadsheet = $reader->load($inputFileName);
        $sheet = $spreadsheet->getSheet($selectWorkSheetIndex);
        $highestRow = $sheet->getHighestRow(); // 总行数（不准确）)
        $highestColumn = $sheet->getHighestColumn();// 最后有数据的一列，比如 S 列
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);//把列字母转为数字,比如可以得到有 10 列
        $chunkSize = 500; //一次读取多少条
        $chunkFilter = new ChunkReadFilter();
        $reader->setReadFilter($chunkFilter);
        $j = 1;
        $allErrorData = [];
        $allInsertData = []; //校验一下数据，用完可以删除
        $readRowTotal       = $highestRow - 1; //插件读取的总行数（不准确，如果Excel格式有问题的情况）
        $successInsertTotal = 0; //总共更新条数
        $errorTotal         = 0; //总失败行数（不包含空行）
        if ($readRowTotal <= 0){
            return $this->queueReids()->set($processCacheKey,100,3); //进度
        }
        $excelTitle=[];
//        $nextIndex=Coordinate::stringFromColumnIndex($highestColumnIndex+1);
        $nextIndex = 'K';
        if($params['type']=='billPayExcel'){
            $nextIndex = 'M';
        }
        //↓↓↓表格读取数据开始↓↓↓
        $dbHouseNewChargeNumber = new HouseNewChargeNumber();
        $whereChargeNumber = [];
        $whereChargeNumber[] = ['id', '=', $subjectId];
        $chargeNumberInfo = $dbHouseNewChargeNumber->get_one($whereChargeNumber, 'id, charge_type');
        $charge_type = isset($chargeNumberInfo['charge_type']) && $chargeNumberInfo['charge_type'] ? $chargeNumberInfo['charge_type'] : '';
        
        $dbHouseVillageSingle = new HouseVillageSingle();
        $dbHouseVillageFloor = new HouseVillageFloor();
        $dbHouseVillageLayer = new HouseVillageLayer();
        $dbHouseVillageUserVacancy = new HouseVillageUserVacancy();
        $dbHouseNewChargeRule = new HouseNewChargeRule();
        $dbHouseNewChargeStandardBind = new HouseNewChargeStandardBind();
        $dbHouseNewPayOrder = new HouseNewPayOrder();
        $service_house_new_charge_rule = new HouseNewChargeRuleService();
        $service_house_new_charge = new HouseNewChargeService();
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $db_house_new_pay_order_summary = new HouseNewPayOrderSummary();
        $charge_type_arr = $service_house_new_charge->charge_type;
        $nowTime = time();
        // 计费模式 1:固定费用 2：单价计量单位 3 临时车  4 月租车  5 车位数量
        $fees_type           = 1;
        // 计量单位
        $unit_gage           = '';
        // 周期性设置 周期数 0:不限周期
        $cyclicity_set       = 0;
        // 账单生成周期设置 1：按日生成2：按月生成3：按年生成
        $bill_create_set     = 0;
        // 账单欠费模式  1:预生成  2：后生成
        $bill_arrears_set    = 0;
        // 生成账单模式 1:手动生成 2：自动生成
        $bill_type           = 1;
        // 是否支持预缴 1:支持 2：不支持
        $is_prepaid          = 2;
        // 1:年月日 2：年月 3：年
        $charge_valid_type   = 2;
        // 收费标准生效时间
        $charge_valid_time = $nowTime;
        fdump_api(['时间=='.__LINE__,'nowTime' => $nowTime],'import_excel/errBillExcelImportExecute');
        /*
        for ($startRow = 2; $startRow <= $highestRow; $startRow += $chunkSize) {
            $chunkFilter->setRows($startRow, $chunkSize);
            $spreadsheet = $reader->load($inputFileName);
            */
            $sheetData = $spreadsheet->getSheetByName($worksheetName)->toArray(NULL, true, true, true);
            $excelTitle = $sheetData[1];
            unset($sheetData[1]); //去掉表头
            fdump_api(['时间=='.__LINE__,'sheetData' => $sheetData,'nextIndex' => $nextIndex],'import_excel/errBillExcelImportExecute', 1);
            //开始业务逻辑
            try {
                $rowTrueNum=0;
                $rowNum=0;
                $offlineArr=$ImportExcelService->getOfflinePayTypeList($property_id,'name');
                foreach ($sheetData as $dk => $data) {
                    $nowTime = time();
                    //本次读取处理完毕
                    $process =  sprintf('%.2f',$rowNum / $highestRow);
                    $process=$process*100;
                    if($process>0 && $process<1){
                        $process=1;
                    }else{
                        $process=floor($process);
                    }
                    if ($rowNum >=$highestRow ){
                        $readRowTotal=$rowTrueNum;
                        $process = 100;
                    }
                    $rowNum++;
                    $processInfo = [
                        'process'            =>$process, //进度
                        'readRowTotal'       => $readRowTotal, //总共有数据行数
                        'successInsterTotal' => $successInsertTotal, //总共插入表成功条数
                        'errorTotal'         => $errorTotal, //总共插入表失败条数
                    ];
                    $insert_rets=$this->queueReids()->set($processCacheKey,$processInfo,3600); //进度（理论上暂时还不会有超过一个小时的数据要执行）
                    
                    $single = isset($data['A']) && $data['A'] ? $data['A'] : '';
                    $single = strval($single);
                    $single = trim($single);
                    $floor = isset($data['B']) && $data['B'] ? $data['B'] : '';
                    $floor = strval($floor);
                    $floor = trim($floor);
                    $layer = isset($data['C']) && $data['C'] ? $data['C'] : '';
                    $layer = strval($layer);
                    $layer = trim($layer);
                    $room = isset($data['D']) && $data['D'] ? $data['D'] : '';
                    $room = strval($room);
                    $room = trim($room);
                    $charge_name = isset($data['E']) && $data['E'] ? $data['E'] : '';
                    $charge_name = strval($charge_name);
                    $charge_name = trim($charge_name);
                    $charge_price = isset($data['F']) && $data['F'] ? $data['F'] : '';
                    $charge_price = strval($charge_price);
                    $charge_price = trim($charge_price);  //费用
                    $remark = isset($data['G']) && $data['G'] ? $data['G'] : '';
                    $remark = strval($remark);
                    $remark = trim($remark);
                    $service_start_day = isset($data['H']) && $data['H'] ? $data['H'] : '';
                    $service_start_day = $service_start_day ? strval($service_start_day):'';
                    $service_start_day = trim($service_start_day);
                    $service_end_day = isset($data['I']) && $data['I'] ? $data['I'] : '';
                    $service_end_day = $service_end_day ? strval($service_end_day):'';
                    $service_end_day = trim($service_end_day);
                    $add_time_temp = isset($data['J']) && $data['J'] ? $data['J'] : '';
                    if($add_time_temp){
                        $add_time_temp =  strval($add_time_temp);
                        $add_time_temp = trim($add_time_temp);
                    }
                    $offline_pay_type_str='';
                    $pay_time_temp=0;
                    $tpstype='欠缴';
                    if($params['type']=='billPayExcel'){
                        $tpstype='已缴';
                        //一次性已缴账单导入
                        $pay_time_temp = isset($data['K']) && $data['K'] ? $data['K'] : 0;
                        if($pay_time_temp){
                            $pay_time_temp =  strval($pay_time_temp);
                            $pay_time_temp = trim($pay_time_temp);
                        }
                        $offline_pay_type_str = isset($data['L']) && $data['L'] ? $data['L'] : '';
                        if($offline_pay_type_str){
                            $offline_pay_type_str =  strval($offline_pay_type_str);
                            $offline_pay_type_str = trim($offline_pay_type_str);
                        }
                    }
                    if(empty($single) && empty($floor) && empty($layer) && empty($room) && empty($charge_name)){
                        continue;
                    }
                    $rowTrueNum++;
                    if (!$single) {
                        $data[$nextIndex]= "缺少【楼栋】";
                        $allErrorData[] =  $data;
                        $errorTotal += 1;
                        continue;
                    }
                    if (!$floor) {
                        $data[$nextIndex] = "缺少【单元】";
                        $allErrorData[] =  $data;
                        $errorTotal += 1;
                        fdump_api([__LINE__,'data' => $data],'import_excel/errBillExcelImportExecute', 1);
                        continue;
                    }
                    fdump_api([__LINE__,'layer' => $layer],'import_excel/errBillExcelImportExecute', 1);
                    if (!$layer) {
                        $data[$nextIndex]= "缺少【楼层】";
                        $allErrorData[] =  $data;
                        $errorTotal += 1;
                        fdump_api([__LINE__,'data' => $data],'import_excel/errBillExcelImportExecute', 1);
                        continue;
                    }
                    if (!$room) {
                        $data[$nextIndex]= "缺少【房间号】";
                        $allErrorData[] =  $data;
                        $errorTotal += 1;
                        fdump_api([__LINE__,'data' => $data],'import_excel/errBillExcelImportExecute', 1);
                        continue;
                    }
                    if (!$charge_name) {
                        $data[$nextIndex]= "缺少【欠缴费用名称】";
                        $allErrorData[] =  $data;
                        $errorTotal += 1;
                        fdump_api([__LINE__,'data' => $data],'import_excel/errBillExcelImportExecute', 1);
                        continue;
                    }
                    if (!$charge_price) {
                        $data[$nextIndex]= "缺少【".$tpstype."金额】";
                        $allErrorData[] =  $data;
                        $errorTotal += 1;
                        fdump_api([__LINE__,'data' => $data],'import_excel/errBillExcelImportExecute', 1);
                        continue;
                    }
                    if (floatval($charge_price) <= 0) {
                        $data[$nextIndex]= "【".$tpstype."金额】必须大于0";
                        $allErrorData[] =  $data;
                        $errorTotal += 1;
                        fdump_api([__LINE__,'data' => $data],'import_excel/errBillExcelImportExecute', 1);
                        continue;
                    }
                    $offline_pay_type=0;
                    if($params['type']=='billPayExcel'){
                        //一次性已缴账单导入
                        if($pay_time_temp){
                            if (is_numeric($pay_time_temp)){
                                $value = date("Y-m-d", ($pay_time_temp - 25569) * 24 * 3600);
                                $pay_time_temp =strtotime($value);
                            }else if (preg_match('/[\x7f-\xff]/',$pay_time_temp)){
                                $arr = date_parse_from_format('Y年m月d日', $pay_time_temp);
                                $pay_time_temp = mktime($arr['hour'], $arr['minute'], $arr['second'], $arr['month'], $arr['day'], $arr['year']);
                            }else{
                                $pay_time_temp =strtotime($pay_time_temp);
                            }
                        }
                        if($pay_time_temp<631123200){
                            $data[$nextIndex]= "请正确填写【缴费时间】,保证数据是文本格式！";
                            $allErrorData[] =  $data;
                            $errorTotal += 1;
                            continue;
                        }
                        if($offlineArr && isset($offlineArr[$offline_pay_type_str])){
                            $offline_pay_type=$offlineArr[$offline_pay_type_str]['id'];
                        }
                        if($offline_pay_type<1){
                            $data[$nextIndex]= "请正确填写【缴费方式】,仅支持系统里的线下支付方式";
                            $allErrorData[] =  $data;
                            $errorTotal += 1;
                            continue;
                        }
                    }
                    // 先匹配对应房间 不做新增 匹配不上直接报错
                    // 先匹配楼栋名称
                    $whereSingle = [];
                    $whereSingle[] = ['village_id', '=', $village_id];
                    $whereSingle[] = ['status', '=', 1];
                    $whereSingle[] = ['single_name', '=', $single];
                    $singleInfo = $dbHouseVillageSingle->getOne($whereSingle, 'id, single_name');
                    if ($singleInfo && ! is_array($singleInfo)) {
                        $singleInfo = $singleInfo->toArray();
                    }
                    if (empty($singleInfo)) {
                        // 匹配不上名字 再匹配楼栋编号
                        $whereSingle = [];
                        $whereSingle[] = ['village_id', '=', $village_id];
                        $whereSingle[] = ['status', '=', 1];
                        $whereSingle[] = ['single_number', '=', $single];
                        $singleInfo = $dbHouseVillageSingle->getOne($whereSingle, 'id, single_name');
                        if ($singleInfo && ! is_array($singleInfo)) {
                            $singleInfo = $singleInfo->toArray();
                        }
                    }
                    if (! isset($singleInfo['id']) || ! $singleInfo['id']) {
                        $data[$nextIndex]= "对应【楼栋】不存在";
                        $allErrorData[] =  $data;
                        $errorTotal += 1;
                        fdump_api(['对应【楼栋】不存在=='.__LINE__,'whereSingle' => $whereSingle,'singleInfo' => $singleInfo],'import_excel/errBillExcelImportExecute', 1);
                        continue;
                    }
                    $single_id = $singleInfo['id'];

                    // 先匹配单元名称
                    $whereFloor = [];
                    $whereFloor[] = ['village_id', '=', $village_id];
                    $whereFloor[] = ['single_id', '=', $single_id];
                    $whereFloor[] = ['status', '=', 1];
                    $whereFloor[] = ['floor_name', '=', $floor];
                    $floorInfo = $dbHouseVillageFloor->getOne($whereFloor, 'floor_id, floor_name, single_id');
                    if ($floorInfo && !$floorInfo->isEmpty()) {
                        $floorInfo = $floorInfo->toArray();
                    }else{
                        $whereFloor = [];
                        $whereFloor[] = ['village_id', '=', $village_id];
                        $whereFloor[] = ['single_id', '=', $single_id];
                        $whereFloor[] = ['status', '=', 1];
                        $whereFloor[] = ['floor_number', '=', $floor];
                        $floorInfo = $dbHouseVillageFloor->getOne($whereFloor, 'floor_id, floor_name, single_id');
                        if($floorInfo && !$floorInfo->isEmpty()){
                            $floorInfo = $floorInfo->toArray();
                        }
                    }
                    if (empty($floorInfo)) {
                        // 匹配不上名字 再匹配单元编号
                        $whereFloor = [];
                        $whereFloor[] = ['village_id', '=', $village_id];
                        $whereFloor[] = ['single_id', '=', $single_id];
                        $whereFloor[] = ['status', '=', 1];
                        $whereFloor[] = ['floor_number', '=', $floor];
                        $floorInfo = $dbHouseVillageFloor->getOne($whereFloor, 'floor_id, floor_name, single_id');
                        if ($floorInfo && ! is_array($floorInfo)) {
                            $floorInfo = $floorInfo->toArray();
                        }
                    }
                    if (! isset($floorInfo['floor_id']) || ! $floorInfo['floor_id']) {
                        $data[$nextIndex]= "对应【单元】不存在";
                        $allErrorData[] =  $data;
                        $errorTotal += 1;
                        fdump_api(['对应【单元】不存在=='.__LINE__,'whereFloor' => $whereFloor,'floorInfo' => $floorInfo],'import_excel/errBillExcelImportExecute', 1);
                        continue;
                    }
                    $floor_id = $floorInfo['floor_id'];

                    // 先匹配楼层名称
                    $whereLayer = [];
                    $whereLayer[] = ['village_id', '=', $village_id];
                    $whereLayer[] = ['floor_id', '=', $floor_id];
                    $whereLayer[] = ['status', '=', 1];
                    $whereLayer[] = ['layer_name', '=', $layer];
                    $layerInfo = $dbHouseVillageLayer->getOne($whereLayer, 'id, layer_name, single_id, floor_id');
                    if ($layerInfo &&  !$layerInfo->isEmpty()) {
                        $layerInfo = $layerInfo->toArray();
                    }else{
                        $whereLayer = [];
                        $whereLayer[] = ['village_id', '=', $village_id];
                        $whereLayer[] = ['floor_id', '=', $floor_id];
                        $whereLayer[] = ['status', '=', 1];
                        $whereLayer[] = ['layer_number', '=', $layer];
                        $layerInfo = $dbHouseVillageLayer->getOne($whereLayer, 'id, layer_name, single_id, floor_id');
                        if($layerInfo && !$layerInfo->isEmpty()){
                            $layerInfo = $layerInfo->toArray();
                        }
                    }
                    if (empty($layerInfo)) {
                        // 匹配不上名字 再匹配楼层编号
                        $whereLayer = [];
                        $whereLayer[] = ['village_id', '=', $village_id];
                        $whereLayer[] = ['floor_id', '=', $floor_id];
                        $whereLayer[] = ['status', '=', 1];
                        $whereLayer[] = ['layer_number', '=', $layer];
                        $layerInfo = $dbHouseVillageLayer->getOne($whereLayer, 'id, layer_name, single_id, floor_id');
                        if ($layerInfo && ! is_array($layerInfo)) {
                            $layerInfo = $layerInfo->toArray();
                        }
                    }
                    if (! isset($layerInfo['id']) || ! $layerInfo['id']) {
                        $data[$nextIndex]= "对应【楼层】不存在";
                        $allErrorData[] =  $data;
                        $errorTotal += 1;
                        fdump_api(['对应【楼层】不存在=='.__LINE__,'whereLayer' => $whereLayer,'layerInfo' => $layerInfo],'import_excel/errBillExcelImportExecute', 1);
                        continue;
                    }
                    $layer_id = $layerInfo['id'];

                    // 先匹配房间名称
                    $whereVacancy = [];
                    $whereVacancy[] = ['village_id', '=', $village_id];
                    $whereVacancy[] = ['single_id', '=', $single_id];
                    $whereVacancy[] = ['floor_id', '=', $floor_id];
                    $whereVacancy[] = ['layer_id', '=', $layer_id];
                    $whereVacancy[] = ['status', '<>', 0];
                    $whereVacancy[] = ['is_del', '=', 0];
                    $whereVacancy[] = ['room', '=', $room];
                    $roomInfo = $dbHouseVillageUserVacancy->getOne($whereVacancy, 'pigcms_id, room, village_id, single_id, floor_id, layer_id');
                    if ($roomInfo && ! is_array($roomInfo)) {
                        $roomInfo = $roomInfo->toArray();
                    }
                    if (empty($roomInfo)) {
                        // 匹配不上名字 再匹配房间编号
                        $whereVacancy = [];
                        $whereVacancy[] = ['village_id', '=', $village_id];
                        $whereVacancy[] = ['single_id', '=', $single_id];
                        $whereVacancy[] = ['floor_id', '=', $floor_id];
                        $whereVacancy[] = ['layer_id', '=', $layer_id];
                        $whereVacancy[] = ['status', '<>', 0];
                        $whereVacancy[] = ['is_del', '=', 0];
                        $whereVacancy[] = ['room_number', '=', $room];
                        $roomInfo = $dbHouseVillageUserVacancy->getOne($whereVacancy, 'pigcms_id, room, village_id, single_id, floor_id, layer_id');
                        if ($roomInfo && ! is_array($roomInfo)) {
                            $roomInfo = $roomInfo->toArray();
                        }
                    }
                    if (! isset($roomInfo['pigcms_id']) || ! $roomInfo['pigcms_id']) {
                        $data[$nextIndex]= "对应【房间号】不存在";
                        $allErrorData[] =  $data;
                        $errorTotal += 1;
                        fdump_api(['对应【房间号】不存在=='.__LINE__,'whereVacancy' => $whereVacancy,'roomInfo' => $roomInfo],'import_excel/errBillExcelImportExecute', 1);
                        continue;
                    }
                    $room_id = $roomInfo['pigcms_id'];

                    $whereRepeatRule = [];
                    $status = 1;//状态 1:开启 2：关闭 4：删除
                    $whereRepeatRule[] = ['village_id', '=', $village_id];
                    $whereRepeatRule[] = ['subject_id', '=', $subjectId];
                    $whereRepeatRule[] = ['charge_project_id', '=', $projectId];
                    $whereRepeatRule[] = ['charge_name', '=', $charge_name];
                  
                    $whereRepeatRule[] = ['unit_price', '=', $charge_price];
                    $whereRepeatRule[] = ['charge_price', '=', $charge_price];
                    
                    $whereRepeatRule[] = ['fees_type', '=', $fees_type];
                    $whereRepeatRule[] = ['cyclicity_set', '=', $cyclicity_set];
                    $whereRepeatRule[] = ['bill_create_set', '=', $bill_create_set];
                    $whereRepeatRule[] = ['bill_arrears_set', '=', $bill_arrears_set];
                    $whereRepeatRule[] = ['bill_type', '=', $bill_type];
                    $whereRepeatRule[] = ['is_prepaid', '=', $is_prepaid];
                    $whereRepeatRule[] = ['status', '=', $status];
                    $whereRepeatRule[] = ['from_type', '=', 'bill_excel'];
                    $ruleInfo = $dbHouseNewChargeRule->getOne($whereRepeatRule, 'id');
                    if (isset($ruleInfo['id']) && $ruleInfo['id']) {
                        $ruleId = $ruleInfo['id'];
                    } else {
                        $ruleData = [
                            'village_id' => $village_id,
                            'subject_id' => $subjectId,
                            'charge_project_id' => $projectId,
                            'charge_name' => $charge_name,
                            'unit_price' => $charge_price,
                            'charge_price' => $charge_price,
                            'fees_type' => $fees_type,
                            'cyclicity_set' => $cyclicity_set,
                            'bill_create_set' => $bill_create_set,
                            'bill_arrears_set' => $bill_arrears_set,
                            'bill_type' => $bill_type,
                            'is_prepaid' => $is_prepaid,
                            'status' => $status,
                            'from_type' => 'bill_excel',
                            'charge_valid_type' => $charge_valid_type,
                            'charge_valid_time' => $charge_valid_time,
                            'not_house_rate' => 100,
                            'unit_gage' => $unit_gage,
                            'add_time' => $nowTime,
                        ];
                        $ruleId = $dbHouseNewChargeRule->addFind($ruleData);
                        if (! $ruleId) {
                            $data[$nextIndex]= "收费标准新增失败";
                            $allErrorData[] =  $data;
                            $errorTotal += 1;
                            fdump_api(['收费标准新增失败=='.__LINE__,'ruleData' => $ruleData],'import_excel/errBillExcelImportExecute', 1);
                            continue;
                        }
                    }
                    $bind_type = 1;
                    $cycle = 1;
                    $whereChargeBind = [];
                    $whereChargeBind[] = ['rule_id', '=', $ruleId];
                    $whereChargeBind[] = ['project_id', '=', $projectId];
                    $whereChargeBind[] = ['village_id', '=', $village_id];
                    $whereChargeBind[] = ['single_id', '=', $single_id];
                    $whereChargeBind[] = ['floor_id', '=', $floor_id];
                    $whereChargeBind[] = ['layer_id', '=', $layer_id];
                    $whereChargeBind[] = ['vacancy_id', '=', $room_id];
                    $whereChargeBind[] = ['bind_type', '=', $bind_type];
                    $whereChargeBind[] = ['cycle', '=', $cycle];
                    $whereChargeBind[] = ['is_del', '=', 1];
                    $chargeStandardBindInfo = $dbHouseNewChargeStandardBind->getOne($whereChargeBind, 'id');
                    $charge_standard_bind_id = isset($chargeStandardBindInfo['id']) ? intval($chargeStandardBindInfo['id']) : 0;
                    if (! $charge_standard_bind_id) {
                        $bindData = [];
                        $bindData['cycle'] = $cycle;
                        $bindData['village_id'] = $village_id;
                        $bindData['rule_id'] = $ruleId;
                        $bindData['order_add_time'] = $nowTime - 10;
                        $bindData['bind_type'] = $bind_type;
                        $bindData['single_id'] = $single_id;
                        $bindData['floor_id'] = $floor_id;
                        $bindData['layer_id'] = $layer_id;
                        $bindData['vacancy_id'] = $room_id;
                        $bindData['custom_value'] = '';
                        try{
                            $res = $service_house_new_charge_rule->addStandardBind($bindData);
                            if (isset($res['id']) && $res['id']) {
                                $charge_standard_bind_id = $res['id'];
                            }
                        }catch (\Exception $e){
                            $data[$nextIndex]= $e->getMessage();
                            $allErrorData[] =  $data;
                            $errorTotal += 1;
                            fdump_api(['绑定错误=='.__LINE__,'bindData' => $bindData,'getMessage' => $e->getMessage()],'import_excel/errBillExcelImportExecute',1);
                            continue;
                        }
                    }
                    if (! $charge_standard_bind_id) {
                        $data[$nextIndex]= "收费标准绑定房间失败";
                        $allErrorData[] =  $data;
                        $errorTotal += 1;
                        fdump_api(['收费标准绑定房间失败=='.__LINE__,'whereChargeBind' => isset($whereChargeBind) ? $whereChargeBind : [], 'res' => $res],'import_excel/errBillExcelImportExecute', 1);
                        continue;
                    }

                    $payOrderData = [];
                    $payOrderData['order_type'] = $charge_type;
                    $payOrderData['order_name'] = isset($charge_type_arr[$charge_type]) ? $charge_type_arr[$charge_type] : $charge_name;
                    $payOrderData['village_id'] = $village_id;
                    $payOrderData['property_id'] = $property_id;
                    $payOrderData['total_money'] = $charge_price;
                    $payOrderData['modify_money'] = $payOrderData['total_money'];
                    $payOrderData['is_paid'] = 2;
                    $payOrderData['is_prepare'] = 2;
                    $payOrderData['rule_id'] = $ruleId;
                    $payOrderData['project_id'] = $projectId;
                    $payOrderData['order_no'] = '';
                    $payOrderData['update_time'] = $nowTime;
                    $add_time=$nowTime;
                    if($add_time_temp){
                        if (is_numeric($add_time_temp)){
                            $value = date("Y-m-d", ($add_time_temp - 25569) * 24 * 3600);
                            $add_time_temp =strtotime($value);
                        }else if (preg_match('/[\x7f-\xff]/',$add_time_temp)){
                            $arr = date_parse_from_format('Y年m月d日', $add_time_temp);
                            $add_time_temp = mktime($arr['hour'], $arr['minute'], $arr['second'], $arr['month'], $arr['day'], $arr['year']);
                        }else{
                            $add_time_temp =strtotime($add_time_temp);
                        }
                        if($add_time_temp>100){
                            $add_time=$add_time_temp;
                        }
                    }
                    $payOrderData['add_time'] = $add_time;
                    if($service_start_day){
                        $service_start_time =$this->traitConvertDateToInt($service_start_day);
                    }
                    if($service_end_day){
                        $service_end_time =$this->traitConvertDateToInt($service_end_day);
                    }
                    if($service_start_time>100){
                        $payOrderData['service_start_time']=$service_start_time;
                        if($service_end_time<100){
                            $payOrderData['service_end_time']=$service_start_time+24*3600-1;
                        }else{
                            $payOrderData['service_end_time']=$service_end_time;
                        }
                    }else if($service_end_time>100){
                        $payOrderData['service_start_time']=$service_end_time;
                        $payOrderData['service_end_time']=$service_end_time+24*3600-1;
                    }
                    $whereRepeatOrder = [];
                    $whereRepeatOrder[] = ['rule_id', '=', $ruleId];
                    $whereRepeatOrder[] = ['project_id', '=', $projectId];
                    $whereRepeatOrder[] = ['room_id', '=', $room_id];
                    $whereRepeatOrder[] = ['order_pay_type', '=', 'pc_excel_import'];
                    $whereRepeatOrder[] = ['service_start_time', '=', $payOrderData['service_start_time']];
                    $whereRepeatOrder[] = ['service_end_time', '=', $payOrderData['service_end_time']];
                    $whereRepeatOrder['string'] = $this->paidEffectiveOrderRaw();
                    $start_time=$nowTime-300;
                    $end_time=$nowTime+10;
                    $whereRepeatOrder[] = ['update_time', '>', $start_time];
                    $whereRepeatOrder[] = ['update_time', '<', $end_time];

                    $orderInfo = $dbHouseNewPayOrder->get_one($whereRepeatOrder, 'order_id');
                    if (isset($orderInfo['order_id']) && $orderInfo['order_id']) {
                        $data[$nextIndex]= "订单已经导入过了";
                        $allErrorData[] =  $data;
                        $errorTotal += 1;
                        fdump_api(['订单已经导入=='.__LINE__,'whereRepeatOrder' => $whereRepeatOrder, 'orderInfo' => $orderInfo],'import_excel/errBillExcelImportExecute', 1);
                        continue;
                    }
                    // 3-导入欠费账单
                    $payOrderData['from'] = 3;
                    $payOrderData['unit_price'] = $charge_price;
                    $payOrderData['room_id'] = $room_id;
                    $user_info = $service_house_village_user_bind->getBindInfo([['vacancy_id', '=', $room_id], ['type', 'in', '0,3'], ['status', '=', 1]], 'uid,pigcms_id,name,phone');
                    $payOrderData['pigcms_id']=0;
                    $payOrderData['name']='';
                    $payOrderData['uid']=0;
                    $payOrderData['phone']='';
                    $payOrderData['order_pay_type']='pc_excel_import';
                    if ($user_info) {
                        $payOrderData['pigcms_id'] = $user_info['pigcms_id'] ? $user_info['pigcms_id'] : 0;
                        $payOrderData['name'] = $user_info['name'] ? $user_info['name'] : '';
                        $payOrderData['uid'] = $user_info['uid'] ? $user_info['uid'] : 0;
                        $payOrderData['phone'] = $user_info['phone'] ? $user_info['phone'] : '';
                    }
                    $remark=$remark ? $remark : date('Y-m-d H:i:s').'通过线下表格导入';
                    $payOrderData['remark'] = $remark;
                    $order_id = $dbHouseNewPayOrder->addOne($payOrderData);
                    if (!$order_id) {
                        $data[$nextIndex]= "订单新增失败";
                        $allErrorData[] =  $data;
                        $errorTotal += 1;
                        fdump_api(['订单新增失败=='.__LINE__,'payOrderData' => $payOrderData],'import_excel/errBillExcelImportExecute',1);
                        continue;
                    }
                    if($params['type']=='billPayExcel'){
                        $summaryData=array();
                        $summaryData['village_id'] = $village_id;
                        $summaryData['property_id'] = $property_id;
                        $summaryData['room_id'] = $room_id;
                        $summaryData['position_id'] = 0;
                        $summaryData['pay_uid'] = $payOrderData['uid'];
                        $summaryData['uid']=$payOrderData['uid'];
                        $summaryData['total_money']=$payOrderData['total_money'];
                        $summaryData['pay_money']=$payOrderData['total_money'];
                        $summaryData['pigcms_id']=$payOrderData['pigcms_id'];
                        $summaryData['pay_bind_id']=$payOrderData['pigcms_id'];
                        $summaryData['order_no'] =  build_real_orderid($summaryData['pay_uid']);
                        $summaryData['is_paid'] = 1;  //已支付
                        $summaryData['pay_time']=$pay_time_temp;
                        $summaryData['is_online'] = 2;
                        $summaryData['pay_type'] = 2;
                        $summaryData['offline_pay_type'] = $offline_pay_type;
                        $summaryData['order_pay_type']='pc_excel_import';
                        $summaryData['remark'] = $remark;
                        $summaryData['add_time'] = $add_time;
                        $summary_id=$db_house_new_pay_order_summary->addOne($summaryData);
                        if($summary_id){
                            $saveArr=array('summary_id'=>$summary_id,'pay_type'=>2);
                            $saveArr['offline_pay_type']=$offline_pay_type;
                            $saveArr['pay_time']=$pay_time_temp;
                            $saveArr['is_online']=2;
                            $saveArr['is_paid']=1;
                            $saveArr['pay_time']=$pay_time_temp;
                            $saveArr['order_no']=$summaryData['order_no'];
                            $saveArr['is_discard'] = 1;
                            $saveArr['pay_bind_id'] = $payOrderData['pigcms_id'];
                            $saveArr['pay_bind_name'] = $payOrderData['name'];
                            $saveArr['pay_bind_phone'] = $payOrderData['phone'];
                            $saveArr['pay_money'] = $payOrderData['total_money'];
                            $saveArr['discard_reason'] = '';
                            $dbHouseNewPayOrder->saveOne(array('order_id'=>$order_id),$saveArr);
                        }
                    }
                    $successInsertTotal += 1;
                }
            }catch (\Exception $exception){
                fdump_api(['调用命令行异常了=='.__LINE__,'err' => $exception->getMessage(), 'line' => $exception->getLine()],'import_excel/errBillExcelImportExecute', 1);
            }
            /*
            //业务逻辑处理完毕
            $process =  sprintf('%.2f',$startRow / $highestRow);
            if ($startRow + $chunkSize >=$highestRow ){
                $process = 1;
            }
            fdump_api(['结果=='.__LINE__,'process' => $process],'import_excel/errBillExcelImportExecute', 1);
            $processInfo = [
                'process'            => floor($process * 100), //进度
                'readRowTotal'       => $readRowTotal, //总共有数据行数
                'successInsterTotal' => $successInsertTotal, //总共插入表成功条数
                'errorTotal'         => $errorTotal, //总共插入表失败条数
            ];
            $this->queueReids()->set($processCacheKey,$processInfo,3600); //进度（理论上暂时还不会有超过一个小时的数据要执行）
            */
            //本次读取处理完毕
            $processInfo = [
                'process'            => 100, //进度
                'readRowTotal'       => $rowTrueNum, //总共有数据行数
                'successInsterTotal' => $successInsertTotal, //总共插入表成功条数
                'errorTotal'         => $errorTotal, //总共插入表失败条数
            ];
            $insert_rets=$this->queueReids()->set($processCacheKey,$processInfo,3600); //进度（理论上暂时还不会有超过一个小时的数据要执行）
            $spreadsheet->disconnectWorksheets();
            
        //}
        //↑↑↑表格读取处理完成↑↑↑
        $excelTitle[$nextIndex]='失败原因';
        $record=[
            'village_id'=>$village_id,
            'type'=>$params['type'],
            'file_name'=>basename($inputFileName),
            'import_msg'=>'总行数:'.$rowTrueNum.'条,成功:'.$successInsertTotal.'条,失败:'.$errorTotal.'条',
            'add_time'=> $nowTime
        ];
        if (!empty($allErrorData)){
            $record['status']=0;
            //生成错误表格文件路径
            $record['file_url']=$ImportExcelService->exportExcelMethod($village_id,$excelTitle,$allErrorData,$params['type'].'_'.$nowTime.'_'.uniqid());
        }
        else{
            $record['status']=1;
            $record['file_url']='';
        }
        $end_time=$ImportExcelService->getMillisecondMethod();//结束时间
        $second=sprintf('%.2f',intval($end_time - $begin_time)/1000);
        $record['duration']=$second;
        //写入导入数据
        $res=$NewBuildingService->addHouseVillageImportRecordMethod($record);
        fdump_api(['处理完成=='.__LINE__,'总花费时间:'.$second.'s',$record,$res,$allErrorData],'import_excel/billExcelImportExecute',1);
        return  true;
    }

    public function executeCycleBillExcel($params)
    {
        set_time_limit(0);
        ini_set("memory_limit","512M");
        $ImportExcelService = new ImportExcelService();
        $NewBuildingService = new NewBuildingService();
        $begin_time = $ImportExcelService->getMillisecondMethod();

        $inputFileName        = $params['inputFileName'];
        $selectWorkSheetIndex = $params['selectWorkSheetIndex'];
        $worksheetName        = $params['worksheetName'];
        $nowVillageInfo       = $params['nowVillageInfo'];
        $subjectId            = intval($params['subjectId']);
        $chargeProjectId      = intval($params['projectId']);
        $village_id           = intval($nowVillageInfo['village_id']);
        $village_info         = (new HouseVillage())->getOne($village_id, 'property_id');
        $property_id          = isset($village_info['property_id']) ? intval($village_info['property_id']) : 0;
        $nowTime              = time();

        $processCacheKey = $ImportExcelService->getProcessCacheKeyMethod($village_id, $params['type']);
        $processInfo = [
            'process'            => 0,
            'readRowTotal'       => 0,
            'successInsterTotal' => 0,
            'errorTotal'         => 0,
        ];
        try {
            $this->queueReids()->set($processCacheKey, $processInfo, 3600);
        } catch (\RedisException $e) {
            fdump_api(['$processCacheKey设置失败=='.__LINE__, $processCacheKey, $processInfo, $params, $e->getMessage()], 'import_excel/cycleBillExcelImportExecuteError', 1);
        }

        $projectInfo = (new HouseNewChargeProject())->getOne([
            ['id', '=', $chargeProjectId],
            ['subject_id', '=', $subjectId],
            ['village_id', '=', $village_id],
            ['type', '=', 2],
            ['status', '=', 1],
        ], 'id,name,type');
        if (!$projectInfo) {
            throw new \think\Exception('请选择有效的周期性收费项目');
        }

        $inputFileName = root_path() . '/../' . $inputFileName;
        $reader = $ImportExcelService->getFileReaderMethod($inputFileName, false);
        $spreadsheet = $reader->load($inputFileName);
        $sheet = $spreadsheet->getSheet($selectWorkSheetIndex);
        $highestRow = $sheet->getHighestRow();
        $sheetData = $spreadsheet->getSheetByName($worksheetName)->toArray(NULL, true, true, true);
        $titleRow = 1;
        if (isset($sheetData[1]['A']) && strpos(strval($sheetData[1]['A']), '收费科目') !== false) {
            $titleRow = 4;
            unset($sheetData[1], $sheetData[2], $sheetData[3]);
        }
        $excelTitle = isset($sheetData[$titleRow]) ? $sheetData[$titleRow] : [];
        unset($sheetData[$titleRow]);

        $nextIndex = 'K';
        $allErrorData = [];
        $successInsertTotal = 0;
        $errorTotal = 0;
        $rowTrueNum = 0;
        $rowNum = 0;
        $readRowTotal = max(0, count($sheetData));
        $importRanges = [];

        $dbHouseVillageSingle = new HouseVillageSingle();
        $dbHouseVillageFloor = new HouseVillageFloor();
        $dbHouseVillageLayer = new HouseVillageLayer();
        $dbHouseVillageUserVacancy = new HouseVillageUserVacancy();
        $dbHouseNewChargeRule = new HouseNewChargeRule();
        $dbHouseNewChargeStandardBind = new HouseNewChargeStandardBind();
        $dbHouseNewPayOrder = new HouseNewPayOrder();
        $service_house_new_charge = new HouseNewChargeService();
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $charge_type_arr = $service_house_new_charge->charge_type;
        $chargeNumberInfo = (new HouseNewChargeNumber())->get_one(['id' => $subjectId], 'id,charge_type');
        $charge_type = isset($chargeNumberInfo['charge_type']) && $chargeNumberInfo['charge_type'] ? $chargeNumberInfo['charge_type'] : '';

        foreach ($sheetData as $dk => $data) {
            $process = $highestRow > 0 ? sprintf('%.2f', $rowNum / $highestRow) * 100 : 100;
            $process = $process > 0 && $process < 1 ? 1 : floor($process);
            $rowNum++;
            $this->queueReids()->set($processCacheKey, [
                'process'            => $process,
                'readRowTotal'       => $readRowTotal,
                'successInsterTotal' => $successInsertTotal,
                'errorTotal'         => $errorTotal,
            ], 3600);

            $single = $this->trimExcelValue($data, 'A');
            $floor = $this->trimExcelValue($data, 'B');
            $layer = $this->trimExcelValue($data, 'C');
            $room = $this->trimExcelValue($data, 'D');
            $charge_name = $this->trimExcelValue($data, 'E');
            $charge_price = $this->trimExcelCellValue($sheet, 'F', $dk, $data);
            $remark = $this->trimExcelValue($data, 'G');
            $service_start_day = $this->trimExcelValue($data, 'H');
            $service_end_day = $this->trimExcelValue($data, 'I');
            $add_time_temp = $this->trimExcelValue($data, 'J');
            $bill_create_set = 4;
            $cycle = 1;

            if (empty($single) && empty($floor) && empty($layer) && empty($room) && empty($charge_name)) {
                continue;
            }
            $rowTrueNum++;

            $failReason = '';
            if (!$single) {
                $failReason = '缺少【楼栋名称】';
            } elseif (!$floor) {
                $failReason = '缺少【单元名称】';
            } elseif (!$layer) {
                $failReason = '缺少【楼层名称】';
            } elseif (!$room) {
                $failReason = '缺少【房间号】';
            } elseif (!$charge_name) {
                $failReason = '缺少【收费标准名称】';
            } elseif (!$charge_price || floatval($charge_price) <= 0) {
                $failReason = '【欠缴金额】必须大于0';
            } elseif (!$service_start_day || !$service_end_day) {
                $failReason = '账单计费开始时间和结束时间必填';
            }
            if ($failReason) {
                $this->appendBillExcelError($data, $nextIndex, $failReason, $allErrorData, $errorTotal);
                continue;
            }
            $charge_price = $this->normalizeExcelMoney($charge_price);

            $service_start_time = $this->normalizeCycleBillDateTime($service_start_day, false);
            if ($service_start_time < 100) {
                $this->appendBillExcelError($data, $nextIndex, '账单计费开始时间不是有效日期', $allErrorData, $errorTotal);
                continue;
            }
            $service_end_time = $this->normalizeCycleBillDateTime($service_end_day, true);
            if ($service_end_time < 100) {
                $this->appendBillExcelError($data, $nextIndex, '账单计费结束时间不是有效日期', $allErrorData, $errorTotal);
                continue;
            }
            if ($service_end_time < $service_start_time) {
                $this->appendBillExcelError($data, $nextIndex, '账单计费时间填写错误', $allErrorData, $errorTotal);
                continue;
            }

            $roomInfo = $this->matchImportRoom($dbHouseVillageSingle, $dbHouseVillageFloor, $dbHouseVillageLayer, $dbHouseVillageUserVacancy, $village_id, $single, $floor, $layer, $room);
            if (isset($roomInfo['error'])) {
                $this->appendBillExcelError($data, $nextIndex, $roomInfo['error'], $allErrorData, $errorTotal);
                continue;
            }

            $ruleWhere = [
                ['village_id', '=', $village_id],
                ['subject_id', '=', $subjectId],
                ['charge_project_id', '=', $chargeProjectId],
                ['charge_name', '=', $charge_name],
                ['status', '=', 1],
            ];
            $ruleList = $dbHouseNewChargeRule->getList($ruleWhere, 'id,charge_name,bill_create_set,charge_price,unit_price', 'id desc');
            if (!$ruleList || $ruleList->isEmpty()) {
                $this->appendBillExcelError($data, $nextIndex, '收费标准不存在', $allErrorData, $errorTotal);
                continue;
            }
            $ruleIds = [];
            foreach ($ruleList as $ruleItem) {
                $ruleIds[] = $ruleItem['id'];
            }
            $bindWhere = [
                ['rule_id', 'in', $ruleIds],
                ['project_id', '=', $chargeProjectId],
                ['village_id', '=', $village_id],
                ['vacancy_id', '=', $roomInfo['room_id']],
                ['bind_type', '=', 1],
                ['cycle', '=', $cycle],
                ['is_del', '=', 1],
            ];
            $bindInfo = $dbHouseNewChargeStandardBind->getLists1($bindWhere, 'id,rule_id,cycle,project_id,vacancy_id', 'id desc', 0, 1);
            if (!$bindInfo || $bindInfo->isEmpty()) {
                $this->appendBillExcelError($data, $nextIndex, '房间未绑定该收费标准', $allErrorData, $errorTotal);
                continue;
            }
            $bindInfo = $bindInfo->toArray();
            $bindInfo = $bindInfo[0];
            $ruleId = intval($bindInfo['rule_id']);

            $rangeKey = $roomInfo['room_id'] . '_' . $ruleId . '_' . $chargeProjectId;
            if (isset($importRanges[$rangeKey])) {
                foreach ($importRanges[$rangeKey] as $range) {
                    if ($this->isTimeRangeOverlap($service_start_time, $service_end_time, $range['start'], $range['end'])) {
                        $this->appendBillExcelError($data, $nextIndex, 'Excel内存在重复周期账单', $allErrorData, $errorTotal);
                        continue 2;
                    }
                }
            }
            $whereRepeatOrder = [
                ['rule_id', '=', $ruleId],
                ['project_id', '=', $chargeProjectId],
                ['room_id', '=', $roomInfo['room_id']],
                ['is_discard', '=', 1],
                ['service_start_time', '<=', $service_end_time],
                ['service_end_time', '>=', $service_start_time],
            ];
            $whereRepeatOrder['string'] = $this->paidEffectiveOrderRaw();
            $orderInfo = $dbHouseNewPayOrder->get_one($whereRepeatOrder, 'order_id,service_start_time,service_end_time');
            if (isset($orderInfo['order_id']) && $orderInfo['order_id']) {
                $this->appendBillExcelError($data, $nextIndex, '账单计费时间与已有账单重叠', $allErrorData, $errorTotal);
                continue;
            }

            $add_time = $nowTime;
            if ($add_time_temp) {
                $add_time_tmp = $this->traitConvertDateToInt($add_time_temp);
                if ($add_time_tmp > 100) {
                    $add_time = $add_time_tmp;
                }
            }
            $user_info = $service_house_village_user_bind->getBindInfo([['vacancy_id', '=', $roomInfo['room_id']], ['type', 'in', '0,3'], ['status', '=', 1]], 'uid,pigcms_id,name,phone');
            $payOrderData = [
                'order_type' => $charge_type,
                'order_name' => isset($charge_type_arr[$charge_type]) ? $charge_type_arr[$charge_type] : $charge_name,
                'village_id' => $village_id,
                'property_id' => $property_id,
                'total_money' => $charge_price,
                'modify_money' => $charge_price,
                'is_paid' => 2,
                'is_prepare' => 2,
                'is_discard' => 1,
                'rule_id' => $ruleId,
                'project_id' => $chargeProjectId,
                'order_no' => '',
                'update_time' => $nowTime,
                'add_time' => $add_time,
                'service_start_time' => $service_start_time,
                'service_end_time' => $service_end_time,
                'service_month_num' => 1,
                'from' => 3,
                'unit_price' => $charge_price,
                'room_id' => $roomInfo['room_id'],
                'position_id' => 0,
                'pigcms_id' => $user_info && $user_info['pigcms_id'] ? $user_info['pigcms_id'] : 0,
                'name' => $user_info && $user_info['name'] ? $user_info['name'] : '',
                'uid' => $user_info && $user_info['uid'] ? $user_info['uid'] : 0,
                'phone' => $user_info && $user_info['phone'] ? $user_info['phone'] : '',
                'order_pay_type' => 'pc_excel_import',
                'remark' => $remark ? $remark : date('Y-m-d H:i:s') . '通过线下表格导入周期性账单',
            ];
            $order_id = $dbHouseNewPayOrder->addOne($payOrderData);
            if (!$order_id) {
                $this->appendBillExcelError($data, $nextIndex, '订单新增失败', $allErrorData, $errorTotal);
                continue;
            }
            $importRanges[$rangeKey][] = ['start' => $service_start_time, 'end' => $service_end_time];
            $successInsertTotal++;
        }

        $this->queueReids()->set($processCacheKey, [
            'process'            => 100,
            'readRowTotal'       => $rowTrueNum,
            'successInsterTotal' => $successInsertTotal,
            'errorTotal'         => $errorTotal,
        ], 3600);
        $spreadsheet->disconnectWorksheets();
        $excelTitle[$nextIndex] = '失败原因';
        $record = [
            'village_id' => $village_id,
            'type' => $params['type'],
            'file_name' => basename($inputFileName),
            'import_msg' => '总行数'.$rowTrueNum.'条，成功:'.$successInsertTotal.'条，失败:'.$errorTotal.'条',
            'add_time' => time(),
        ];
        if (!empty($allErrorData)) {
            $record['status'] = 0;
            $record['file_url'] = $ImportExcelService->exportExcelMethod($village_id, $excelTitle, $allErrorData, $params['type'].'_'.time().'_'.uniqid());
        } else {
            $record['status'] = 1;
            $record['file_url'] = '';
        }
        $end_time = $ImportExcelService->getMillisecondMethod();
        $second = sprintf('%.2f', intval($end_time - $begin_time) / 1000);
        $record['duration'] = $second;
        $res = $NewBuildingService->addHouseVillageImportRecordMethod($record);
        fdump_api(['周期性账单导入处理完成=='.__LINE__, '总花费时间:'.$second.'s', $record, $res, $allErrorData], 'import_excel/cycleBillExcelImportExecute', 1);
        return [
            'readRowTotal'       => $rowTrueNum,
            'successInsterTotal' => $successInsertTotal,
            'errorTotal'         => $errorTotal,
            'file_url'           => isset($record['file_url']) ? $record['file_url'] : '',
            'import_msg'         => $record['import_msg'],
        ];
    }

    public function executeCycleBillPayExcel($params)
    {
        set_time_limit(0);
        ini_set("memory_limit", "512M");
        $isParkingBill = isset($params['type']) && $params['type'] == ImportExcelService::UPLOAD_PARKING_BILL_PAY_EXCEL;
        $ImportExcelService = new ImportExcelService();
        $NewBuildingService = new NewBuildingService();
        $begin_time = $ImportExcelService->getMillisecondMethod();

        $inputFileName        = $params['inputFileName'];
        $selectWorkSheetIndex = $params['selectWorkSheetIndex'];
        $worksheetName        = $params['worksheetName'];
        $nowVillageInfo       = $params['nowVillageInfo'];
        $subjectId            = isset($params['subjectId']) ? intval($params['subjectId']) : 0;
        $chargeProjectId      = isset($params['projectId']) ? intval($params['projectId']) : 0;
        $village_id           = intval($nowVillageInfo['village_id']);
        $village_info         = (new HouseVillage())->getOne($village_id, 'property_id');
        $property_id          = isset($village_info['property_id']) ? intval($village_info['property_id']) : 0;
        $nowTime              = time();

        $processCacheKey = $ImportExcelService->getProcessCacheKeyMethod($village_id, $params['type']);
        try {
            $this->queueReids()->set($processCacheKey, [
                'process'            => 0,
                'readRowTotal'       => 0,
                'successInsterTotal' => 0,
                'errorTotal'         => 0,
            ], 3600);
        } catch (\RedisException $e) {
            fdump_api(['$processCacheKey set fail'.__LINE__, $processCacheKey, $params, $e->getMessage()], 'import_excel/cycleBillPayExcelImportExecuteError', 1);
        }

        if (!$isParkingBill) {
            $projectInfo = (new HouseNewChargeProject())->getOne([
                ['id', '=', $chargeProjectId],
                ['subject_id', '=', $subjectId],
                ['village_id', '=', $village_id],
                ['type', '=', 2],
                ['status', '=', 1],
            ], 'id,name,type');
            if (!$projectInfo) {
                throw new \think\Exception('请选择有效的周期性收费项目');
            }
        }

        $inputFileName = root_path() . '/../' . $inputFileName;
        $reader = $ImportExcelService->getFileReaderMethod($inputFileName, false);
        $spreadsheet = $reader->load($inputFileName);
        $sheet = $spreadsheet->getSheet($selectWorkSheetIndex);
        $highestRow = $sheet->getHighestRow();
        $sheetData = $spreadsheet->getSheetByName($worksheetName)->toArray(NULL, true, true, true);
        $titleRow = 1;
        if (isset($sheetData[1]['A']) && strpos(strval($sheetData[1]['A']), '收费科目') !== false) {
            $titleRow = 4;
            unset($sheetData[1], $sheetData[2], $sheetData[3]);
        }
        $excelTitle = isset($sheetData[$titleRow]) ? $sheetData[$titleRow] : [];
        unset($sheetData[$titleRow]);

        $nextIndex = $isParkingBill ? 'U' : 'V';
        $allErrorData = [];
        $successInsertTotal = 0;
        $errorTotal = 0;
        $rowTrueNum = 0;
        $rowNum = 0;
        $readRowTotal = max(0, count($sheetData));
        $importRanges = [];

        $dbHouseVillageSingle = new HouseVillageSingle();
        $dbHouseVillageFloor = new HouseVillageFloor();
        $dbHouseVillageLayer = new HouseVillageLayer();
        $dbHouseVillageUserVacancy = new HouseVillageUserVacancy();
        $dbHouseVillageParkingGarage = new HouseVillageParkingGarage();
        $dbHouseVillageParkingPosition = new HouseVillageParkingPosition();
        $dbHouseVillageBindPosition = new HouseVillageBindPosition();
        $dbHouseNewChargeRule = new HouseNewChargeRule();
        $dbHouseNewChargeStandardBind = new HouseNewChargeStandardBind();
        $dbHouseNewPayOrder = new HouseNewPayOrder();
        $dbHouseNewPayOrderSummary = new HouseNewPayOrderSummary();
        $service_house_new_charge = new HouseNewChargeService();
        $charge_type_arr = $service_house_new_charge->charge_type;
        $charge_type = 'park_new';
        if (!$isParkingBill) {
            $chargeNumberInfo = (new HouseNewChargeNumber())->get_one(['id' => $subjectId], 'id,charge_type');
            $charge_type = isset($chargeNumberInfo['charge_type']) && $chargeNumberInfo['charge_type'] ? $chargeNumberInfo['charge_type'] : '';
        }
        $offlineArr = $ImportExcelService->getOfflinePayTypeList($property_id, 'name');

        foreach ($sheetData as $dk => $data) {
            $process = $highestRow > 0 ? sprintf('%.2f', $rowNum / $highestRow) * 100 : 100;
            $process = $process > 0 && $process < 1 ? 1 : floor($process);
            $rowNum++;
            $this->queueReids()->set($processCacheKey, [
                'process'            => $process,
                'readRowTotal'       => $readRowTotal,
                'successInsterTotal' => $successInsertTotal,
                'errorTotal'         => $errorTotal,
            ], 3600);

            if ($isParkingBill) {
                $garageName = $this->trimExcelValue($data, 'A');
                $garageId = $this->trimExcelValue($data, 'B');
                $positionNum = $this->trimExcelValue($data, 'C');
                $charge_name = $this->trimExcelValue($data, 'D');
                $bill_money = $this->trimExcelCellValue($sheet, 'E', $dk, $data);
                $remark = $this->trimExcelValue($data, 'F');
                $service_start_day = $this->trimExcelValue($data, 'G');
                $service_end_day = $this->trimExcelValue($data, 'H');
                $add_time_temp = $this->trimExcelValue($data, 'I');
                $rowPayBindName = $this->trimExcelValue($data, 'J');
                $rowPayBindPhone = $this->trimExcelValue($data, 'K');
                $pay_time_temp = $this->trimExcelValue($data, 'L');
                $invoice_status_str = $this->trimExcelValue($data, 'M');
                $total_pay_money = $this->trimExcelCellValue($sheet, 'N', $dk, $data);
                $actual_pay_money = $this->trimExcelCellValue($sheet, 'O', $dk, $data);
                $deposit_money = $this->trimExcelCellValue($sheet, 'P', $dk, $data);
                $score_deducte = $this->trimExcelCellValue($sheet, 'Q', $dk, $data);
                $score_used_count = $this->trimExcelCellValue($sheet, 'R', $dk, $data);
                $offline_pay_type_str = $this->trimExcelValue($data, 'S');
                $rowCashierName = $this->trimExcelValue($data, 'T');
            } else {
                $single = $this->trimExcelValue($data, 'A');
                $floor = $this->trimExcelValue($data, 'B');
                $layer = $this->trimExcelValue($data, 'C');
                $room = $this->trimExcelValue($data, 'D');
                $charge_name = $this->trimExcelValue($data, 'E');
                $bill_money = $this->trimExcelCellValue($sheet, 'F', $dk, $data);
                $remark = $this->trimExcelValue($data, 'G');
                $service_start_day = $this->trimExcelValue($data, 'H');
                $service_end_day = $this->trimExcelValue($data, 'I');
                $add_time_temp = $this->trimExcelValue($data, 'J');
                $rowPayBindName = $this->trimExcelValue($data, 'K');
                $rowPayBindPhone = $this->trimExcelValue($data, 'L');
                $pay_time_temp = $this->trimExcelValue($data, 'M');
                $invoice_status_str = $this->trimExcelValue($data, 'N');
                $total_pay_money = $this->trimExcelCellValue($sheet, 'O', $dk, $data);
                $actual_pay_money = $this->trimExcelCellValue($sheet, 'P', $dk, $data);
                $deposit_money = $this->trimExcelCellValue($sheet, 'Q', $dk, $data);
                $score_deducte = $this->trimExcelCellValue($sheet, 'R', $dk, $data);
                $score_used_count = $this->trimExcelCellValue($sheet, 'S', $dk, $data);
                $offline_pay_type_str = $this->trimExcelValue($data, 'T');
                $rowCashierName = $this->trimExcelValue($data, 'U');
            }
            $cycle = 1;

            if (($isParkingBill && empty($garageName) && empty($garageId) && empty($positionNum) && empty($charge_name))
                || (!$isParkingBill && empty($single) && empty($floor) && empty($layer) && empty($room) && empty($charge_name))) {
                continue;
            }
            $rowTrueNum++;

            $failReason = '';
            if ($isParkingBill && !$garageName) {
                $failReason = '缺少【车库名称】';
            } elseif ($isParkingBill && (!$garageId || intval($garageId) < 1)) {
                $failReason = '请正确填写【车库ID】';
            } elseif ($isParkingBill && !$positionNum) {
                $failReason = '缺少【车位号】';
            } elseif (!$isParkingBill && !$single) {
                $failReason = '缺少【楼栋名称】';
            } elseif (!$isParkingBill && !$floor) {
                $failReason = '缺少【单元名称】';
            } elseif (!$isParkingBill && !$layer) {
                $failReason = '缺少【楼层名称】';
            } elseif (!$isParkingBill && !$room) {
                $failReason = '缺少【房间号】';
            } elseif (!$charge_name) {
                $failReason = '缺少【收费标准名称】';
            } elseif (!$bill_money || floatval($bill_money) <= 0) {
                $failReason = '【账单金额】必须大于0';
            } elseif (!$service_start_day || !$service_end_day) {
                $failReason = '账单计费开始时间和结束时间必填';
            } elseif (!$pay_time_temp) {
                $failReason = '缺少【缴费时间】';
            } elseif (!$total_pay_money || floatval($total_pay_money) < 0) {
                $failReason = '请正确填写【实际缴费金额】';
            } elseif ($actual_pay_money === '' || floatval($actual_pay_money) < 0) {
                $failReason = '请正确填写【实际到账金额】';
            } elseif (!$offline_pay_type_str) {
                $failReason = '缺少【支付方式】';
            } elseif (!$rowPayBindName) {
                $failReason = '缺少【缴费人姓名】';
            } elseif (!$rowPayBindPhone) {
                $failReason = '缺少【缴费人手机号】';
            } elseif (!$rowCashierName) {
                $failReason = '缺少【收款人】';
            }
            if ($failReason) {
                $this->appendBillExcelError($data, $nextIndex, $failReason, $allErrorData, $errorTotal);
                continue;
            }

            $service_start_time = $this->normalizeCycleBillDateTime($service_start_day, false);
            if ($service_start_time < 100) {
                $this->appendBillExcelError($data, $nextIndex, '账单计费开始时间不是有效日期', $allErrorData, $errorTotal);
                continue;
            }
            $service_end_time = $this->normalizeCycleBillDateTime($service_end_day, true);
            if ($service_end_time < 100) {
                $this->appendBillExcelError($data, $nextIndex, '账单计费结束时间不是有效日期', $allErrorData, $errorTotal);
                continue;
            }
            if ($service_end_time < $service_start_time) {
                $this->appendBillExcelError($data, $nextIndex, '账单计费时间填写错误', $allErrorData, $errorTotal);
                continue;
            }
            $pay_time = $this->normalizeCycleBillPayTime($pay_time_temp);
            if ($pay_time < 100) {
                $this->appendBillExcelError($data, $nextIndex, '缴费时间不是有效日期', $allErrorData, $errorTotal);
                continue;
            }

            $offline_pay_type = 0;
            if ($offlineArr && isset($offlineArr[$offline_pay_type_str])) {
                $offline_pay_type = intval($offlineArr[$offline_pay_type_str]['id']);
            }
            if ($offline_pay_type < 1) {
                $this->appendBillExcelError($data, $nextIndex, '请正确填写【支付方式】，仅支持系统里的线下支付方式', $allErrorData, $errorTotal);
                continue;
            }

            $positionInfo = [];
            if ($isParkingBill) {
                $garageInfo = $dbHouseVillageParkingGarage->getOne([
                    ['garage_id', '=', intval($garageId)],
                    ['village_id', '=', $village_id],
                    ['status', '=', 1],
                ], 'garage_id,garage_num');
                if ($garageInfo && !is_array($garageInfo)) {
                    $garageInfo = $garageInfo->toArray();
                }
                if (!$garageInfo) {
                    $this->appendBillExcelError($data, $nextIndex, '车库ID不存在或车库已停用', $allErrorData, $errorTotal);
                    continue;
                }
                if (trim(strval($garageInfo['garage_num'])) !== $garageName) {
                    $this->appendBillExcelError($data, $nextIndex, '车库名称与车库ID不匹配', $allErrorData, $errorTotal);
                    continue;
                }
                $positionInfo = $dbHouseVillageParkingPosition->getFind([
                    ['village_id', '=', $village_id],
                    ['garage_id', '=', intval($garageId)],
                    ['position_num', '=', $positionNum],
                ], 'position_id,garage_id,position_num');
                if ($positionInfo && !is_array($positionInfo)) {
                    $positionInfo = $positionInfo->toArray();
                }
                if (!$positionInfo) {
                    $this->appendBillExcelError($data, $nextIndex, '当前车库下不存在该车位号', $allErrorData, $errorTotal);
                    continue;
                }
                $payerInfo = $dbHouseVillageBindPosition->getRoomUserBindByPosition([
                    ['bp.position_id', '=', $positionInfo['position_id']],
                    ['bp.village_id', '=', $village_id],
                    ['ub.village_id', '=', $village_id],
                    ['ub.name', '=', $rowPayBindName],
                    ['ub.phone', '=', $rowPayBindPhone],
                    ['ub.status', '=', 1],
                    ['ub.vacancy_id', '>', 0],
                ], 'ub.pigcms_id,ub.uid,ub.name,ub.phone,ub.vacancy_id,ub.type');
            } else {
                $roomInfo = $this->matchImportRoom($dbHouseVillageSingle, $dbHouseVillageFloor, $dbHouseVillageLayer, $dbHouseVillageUserVacancy, $village_id, $single, $floor, $layer, $room);
                if (isset($roomInfo['error'])) {
                    $this->appendBillExcelError($data, $nextIndex, $roomInfo['error'], $allErrorData, $errorTotal);
                    continue;
                }
                $payerInfo = (new HouseVillageUserBind())->getOne([
                    ['village_id', '=', $village_id],
                    ['name', '=', $rowPayBindName],
                    ['phone', '=', $rowPayBindPhone],
                    ['vacancy_id', '=', $roomInfo['room_id']],
                    ['status', '=', 1],
                ], 'pigcms_id,uid,name,phone,vacancy_id,type');
            }
            if ($payerInfo && !is_array($payerInfo)) {
                $payerInfo = $payerInfo->toArray();
            }
            if (!$payerInfo) {
                $reason = $isParkingBill ? '缴费人未绑定当前车位或姓名、手机号不一致' : '缴费人不存在';
                $this->appendBillExcelError($data, $nextIndex, $reason, $allErrorData, $errorTotal);
                continue;
            }
            $cashierList = (new HouseWorker())->getGroupWork([
                ['w.village_id', '=', $village_id],
                ['w.is_del', '=', 0],
                ['w.status', '<>', 4],
                ['w.department_id', '>', 0],
                ['w.name', '=', $rowCashierName],
            ], 'w.relation_id as opt_id,w.wid,w.phone,w.name,w.department_id,g.name as group_name', 'w.wid desc');
            $cashierInfo = (!$cashierList || $cashierList->isEmpty()) ? [] : $cashierList[0];
            if ($cashierInfo && !is_array($cashierInfo)) {
                $cashierInfo = $cashierInfo->toArray();
            }
            if (!$cashierInfo) {
                $this->appendBillExcelError($data, $nextIndex, '收款人不存在', $allErrorData, $errorTotal);
                continue;
            }
            if (!$isParkingBill && intval($payerInfo['vacancy_id']) != intval($roomInfo['room_id'])) {
                $this->appendBillExcelError($data, $nextIndex, '缴费人不属于当前房间', $allErrorData, $errorTotal);
                continue;
            }
            $rowCashierId = intval($cashierInfo['opt_id'] ?: $cashierInfo['wid']);

            if ($isParkingBill) {
                $parkingBindList = $dbHouseNewChargeStandardBind->getList([
                    ['b.village_id', '=', $village_id],
                    ['b.position_id', '=', $positionInfo['position_id']],
                    ['b.bind_type', '=', 2],
                    ['b.is_del', '=', 1],
                    ['p.village_id', '=', $village_id],
                    ['p.type', '=', 2],
                    ['p.status', '=', 1],
                    ['n.status', '=', 1],
                    ['r.status', '=', 1],
                    ['r.charge_valid_time', '<=', $nowTime],
                ], 'b.id,b.rule_id,b.project_id,b.position_id,b.cycle,r.charge_name,r.charge_valid_time,p.subject_id,n.charge_type', 0, 6, 'r.charge_valid_time DESC,b.id DESC');
                $currentParkingBinds = [];
                if ($parkingBindList && !$parkingBindList->isEmpty()) {
                    foreach ($parkingBindList->toArray() as $parkingBind) {
                        if (!isset($currentParkingBinds[$parkingBind['project_id']])) {
                            $currentParkingBinds[$parkingBind['project_id']] = $parkingBind;
                        }
                    }
                }
                if (!$currentParkingBinds) {
                    $this->appendBillExcelError($data, $nextIndex, '当前车位未绑定有效的车位管理费收费标准', $allErrorData, $errorTotal);
                    continue;
                }
                $matchedParkingBinds = [];
                foreach ($currentParkingBinds as $parkingBind) {
                    if (trim(strval($parkingBind['charge_name'])) === $charge_name) {
                        $matchedParkingBinds[] = $parkingBind;
                    }
                }
                if (!$matchedParkingBinds) {
                    $currentRuleNames = array_values(array_unique(array_column($currentParkingBinds, 'charge_name')));
                    $this->appendBillExcelError($data, $nextIndex, '收费标准名称与当前车位绑定的收费标准不一致，当前为【'.implode('、', $currentRuleNames).'】', $allErrorData, $errorTotal);
                    continue;
                }
                if (count($matchedParkingBinds) > 1) {
                    $this->appendBillExcelError($data, $nextIndex, '当前车位存在多个同名车位管理费收费标准，无法确定收费项目', $allErrorData, $errorTotal);
                    continue;
                }
                $bindInfo = $matchedParkingBinds[0];
                $ruleId = intval($bindInfo['rule_id']);
                $chargeProjectId = intval($bindInfo['project_id']);
                $subjectId = intval($bindInfo['subject_id']);
                $charge_type = !empty($bindInfo['charge_type']) ? $bindInfo['charge_type'] : 'park_new';
                $cycle = intval($bindInfo['cycle']) > 0 ? intval($bindInfo['cycle']) : 1;
                $roomId = isset($payerInfo['vacancy_id']) ? intval($payerInfo['vacancy_id']) : 0;
                $positionId = intval($positionInfo['position_id']);
                $bindTargetField = 'position_id';
                $bindTargetId = $positionId;
            } else {
                $ruleList = $dbHouseNewChargeRule->getList([
                    ['village_id', '=', $village_id],
                    ['subject_id', '=', $subjectId],
                    ['charge_project_id', '=', $chargeProjectId],
                    ['charge_name', '=', $charge_name],
                    ['status', '=', 1],
                ], 'id,charge_name,bill_create_set,charge_price,unit_price', 'id desc');
                if (!$ruleList || $ruleList->isEmpty()) {
                    $this->appendBillExcelError($data, $nextIndex, '收费标准不存在', $allErrorData, $errorTotal);
                    continue;
                }
                $ruleIds = [];
                foreach ($ruleList as $ruleItem) {
                    $ruleIds[] = $ruleItem['id'];
                }
                $bindInfo = $dbHouseNewChargeStandardBind->getLists1([
                    ['rule_id', 'in', $ruleIds],
                    ['project_id', '=', $chargeProjectId],
                    ['village_id', '=', $village_id],
                    ['vacancy_id', '=', $roomInfo['room_id']],
                    ['bind_type', '=', 1],
                    ['cycle', '=', $cycle],
                    ['is_del', '=', 1],
                ], 'id,rule_id,cycle,project_id,vacancy_id', 'id desc', 0, 1);
                if (!$bindInfo || $bindInfo->isEmpty()) {
                    $this->appendBillExcelError($data, $nextIndex, '房间未绑定该收费标准', $allErrorData, $errorTotal);
                    continue;
                }
                $bindInfo = $bindInfo->toArray();
                $ruleId = intval($bindInfo[0]['rule_id']);
                $roomId = intval($roomInfo['room_id']);
                $positionId = 0;
                $bindTargetField = 'room_id';
                $bindTargetId = $roomId;
            }

            $rangeKey = $bindTargetField . '_' . $bindTargetId . '_' . $ruleId . '_' . $chargeProjectId;
            if (isset($importRanges[$rangeKey])) {
                foreach ($importRanges[$rangeKey] as $range) {
                    if ($this->isTimeRangeOverlap($service_start_time, $service_end_time, $range['start'], $range['end'])) {
                        $this->appendBillExcelError($data, $nextIndex, 'Excel内存在重复周期账单', $allErrorData, $errorTotal);
                        continue 2;
                    }
                }
            }
            $bill_money = $this->normalizeExcelMoney($bill_money);
            $total_pay_money = $this->normalizeExcelMoney($total_pay_money);
            $actual_pay_money = $this->normalizeExcelMoney($actual_pay_money);
            $deposit_money = $deposit_money === '' ? 0 : $this->normalizeExcelMoney($deposit_money);
            $score_deducte = $score_deducte === '' ? 0 : $this->normalizeExcelMoney($score_deducte);
            $score_used_count = $score_used_count === '' ? 0 : $this->normalizeExcelMoney($score_used_count);
            if ($deposit_money < 0 || $score_deducte < 0 || $score_used_count < 0) {
                $this->appendBillExcelError($data, $nextIndex, '抵扣金额和积分抵扣数量不能小于0', $allErrorData, $errorTotal);
                continue;
            }
            $serviceStartDayBegin = strtotime(date('Y-m-d', $service_start_time));
            $serviceStartDayEnd = $serviceStartDayBegin + 86400 - 1;
            $serviceEndDayBegin = strtotime(date('Y-m-d', $service_end_time));
            $serviceEndDayEnd = $serviceEndDayBegin + 86400 - 1;
            if ($isParkingBill) {
                $overlapParkingOrder = $dbHouseNewPayOrder->get_one([
                    ['village_id', '=', $village_id],
                    ['rule_id', '=', $ruleId],
                    ['project_id', '=', $chargeProjectId],
                    ['position_id', '=', $positionId],
                    ['is_discard', '=', 1],
                    ['service_start_time', '<=', $service_end_time],
                    ['service_end_time', '>=', $service_start_time],
                    'string' => $this->paidEffectiveOrderRaw(),
                ], 'order_id,service_start_time,service_end_time');
                if (isset($overlapParkingOrder['order_id']) && $overlapParkingOrder['order_id']) {
                    $this->appendBillExcelError($data, $nextIndex, '账单计费时间与已有车位管理费账单重叠', $allErrorData, $errorTotal);
                    continue;
                }
            }
            if (!$isParkingBill) {
                $paidOrderInfo = $dbHouseNewPayOrder->get_one([
                    ['rule_id', '=', $ruleId],
                    ['project_id', '=', $chargeProjectId],
                    [$bindTargetField, '=', $bindTargetId],
                    ['is_discard', '=', 1],
                    ['service_start_time', '<=', $service_end_time],
                    ['service_end_time', '>=', $service_start_time],
                    'string' => $this->paidEffectiveOrderRaw(),
                ], 'order_id,service_start_time,service_end_time');
                if (isset($paidOrderInfo['order_id']) && $paidOrderInfo['order_id']) {
                    $this->appendBillExcelError($data, $nextIndex, '账单计费时间与已有账单重叠', $allErrorData, $errorTotal);
                    continue;
                }
            }
            $sameOrderRaw = '`o`.`is_paid`=2 AND (ROUND(`o`.`modify_money`,2)=' . $bill_money . ' OR ROUND(`o`.`total_money`,2)=' . $bill_money . ')';
            $sameOrder = $dbHouseNewPayOrder->get_one([
                ['rule_id', '=', $ruleId],
                ['project_id', '=', $chargeProjectId],
                [$bindTargetField, '=', $bindTargetId],
                ['is_discard', '=', 1],
                ['service_start_time', '>=', $serviceStartDayBegin],
                ['service_start_time', '<=', $serviceStartDayEnd],
                ['service_end_time', '>=', $serviceEndDayBegin],
                ['service_end_time', '<=', $serviceEndDayEnd],
                'string' => $sameOrderRaw,
            ], 'order_id,is_paid,total_money,modify_money,service_start_time,service_end_time');
            $matchedUnpaidOrder = [];

            if (isset($sameOrder['order_id']) && $sameOrder['order_id']) {
                $matchedUnpaidOrder = $sameOrder;
            }

            $bill_money = $this->normalizeExcelMoney($bill_money);
            $total_pay_money = $this->normalizeExcelMoney($total_pay_money);
            $actual_pay_money = $this->normalizeExcelMoney($actual_pay_money);
            $deposit_money = $deposit_money === '' ? 0 : $this->normalizeExcelMoney($deposit_money);
            $score_deducte = $score_deducte === '' ? 0 : $this->normalizeExcelMoney($score_deducte);
            $score_used_count = $score_used_count === '' ? 0 : $this->normalizeExcelMoney($score_used_count);
            if ($deposit_money < 0 || $score_deducte < 0 || $score_used_count < 0) {
                $this->appendBillExcelError($data, $nextIndex, '抵扣金额和积分抵扣数量不能小于0', $allErrorData, $errorTotal);
                continue;
            }
            $discount_money = round(max(0, $total_pay_money - $actual_pay_money), 2);
            $invoice_status = in_array($invoice_status_str, ['已开票', '1', '是', '已开发票']) ? 1 : 0;
            $add_time = $nowTime;
            if ($add_time_temp) {
                $add_time_tmp = $this->traitConvertDateToInt($add_time_temp);
                if ($add_time_tmp > 100) {
                    $add_time = $add_time_tmp;
                }
            }
            $payDetail = [
                ['name' => $offline_pay_type_str, 'pay_money' => $actual_pay_money, 'discount_money' => 0],
            ];
            if ($deposit_money > 0) {
                $payDetail[] = ['name' => '押金抵扣', 'pay_money' => 0, 'discount_money' => $deposit_money];
            }
            if ($score_deducte > 0) {
                $payDetail[] = ['name' => '积分抵扣', 'pay_money' => 0, 'discount_money' => $score_deducte];
            }

            $remark = $remark ? $remark : date('Y-m-d H:i:s') . ($isParkingBill ? '通过线下表格导入车位管理费已缴账单' : '通过线下表格导入周期性已缴账单');
            $payOrderData = [
                'order_type' => $charge_type,
                'order_name' => isset($charge_type_arr[$charge_type]) ? $charge_type_arr[$charge_type] : $charge_name,
                'village_id' => $village_id,
                'property_id' => $property_id,
                'total_money' => $bill_money,
                'modify_money' => $total_pay_money,
                'pay_money' => $actual_pay_money,
                'discount_money' => $discount_money,
                'discount_detail' => $discount_money > 0 ? json_encode($payDetail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '',
                'deposit_money' => $deposit_money,
                'score_deducte' => $score_deducte,
                'score_used_count' => $score_used_count,
                'is_paid' => 1,
                'is_prepare' => 2,
                'is_discard' => 1,
                'rule_id' => $ruleId,
                'project_id' => $chargeProjectId,
                'summary_id' => 0,
                'order_no' => '',
                'update_time' => $nowTime,
                'add_time' => $add_time,
                'service_start_time' => $service_start_time,
                'service_end_time' => $service_end_time,
                'service_month_num' => 1,
                'from' => 3,
                'unit_price' => $bill_money,
                'room_id' => $roomId,
                'position_id' => $positionId,
                'pigcms_id' => $payerInfo['pigcms_id'],
                'name' => $payerInfo['name'],
                'uid' => $payerInfo['uid'] ? $payerInfo['uid'] : 0,
                'phone' => $payerInfo['phone'] ? $payerInfo['phone'] : '',
                'pay_bind_id' => $payerInfo['pigcms_id'],
                'pay_bind_name' => $payerInfo['name'],
                'pay_bind_phone' => $payerInfo['phone'] ? $payerInfo['phone'] : '',
                'pay_time' => $pay_time,
                'pay_type' => 2,
                'offline_pay_type' => $offline_pay_type,
                'is_online' => 2,
                'order_pay_type' => 'pc_excel_import',
                'invoice_status' => $invoice_status,
                'role_id' => $rowCashierId,
                'role_name' => $rowCashierName,
                'login_role' => 401,
                'remark' => $remark,
            ];
            if ($isParkingBill) {
                $payOrderData['car_type'] = 'month_type';
                $payOrderData['parking_lot'] = $positionInfo['position_num'];
            }
            $matchedOrderId = isset($matchedUnpaidOrder['order_id']) ? intval($matchedUnpaidOrder['order_id']) : 0;
            if ($matchedOrderId) {
                $order_id = $matchedOrderId;
            } else {
                $order_id = $dbHouseNewPayOrder->addOne($payOrderData);
            }
            if (!$order_id) {
                $this->appendBillExcelError($data, $nextIndex, '订单新增失败', $allErrorData, $errorTotal);
                continue;
            }

            $summaryData = [
                'village_id' => $village_id,
                'property_id' => $property_id,
                'room_id' => $roomId,
                'position_id' => $positionId,
                'pay_uid' => $payOrderData['uid'],
                'uid' => $payOrderData['uid'],
                'total_money' => $total_pay_money,
                'pay_money' => $actual_pay_money,
                'discount_money' => $discount_money,
                'pay_detail' => json_encode($payDetail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'deposit_money' => $deposit_money,
                'score_deducte' => $score_deducte,
                'score_used_count' => $score_used_count,
                'pigcms_id' => $payerInfo['pigcms_id'],
                'pay_bind_id' => $payerInfo['pigcms_id'],
                'order_no' => build_real_orderid($payOrderData['uid']),
                'is_paid' => 1,
                'pay_time' => $pay_time,
                'is_online' => 2,
                'pay_type' => 2,
                'offline_pay_type' => $offline_pay_type,
                'order_pay_type' => 'pc_excel_import',
                'remark' => $remark,
                'add_time' => $add_time,
            ];
            $summary_id = $dbHouseNewPayOrderSummary->addOne($summaryData);
            if (!$summary_id) {
                if ($matchedOrderId) {
                    $this->appendBillExcelError($data, $nextIndex, '总订单新增失败', $allErrorData, $errorTotal);
                    continue;
                }
                $dbHouseNewPayOrder->saveOne(['order_id' => $order_id], ['is_discard' => 2, 'discard_reason' => '总订单新增失败']);
                $this->appendBillExcelError($data, $nextIndex, '总订单新增失败', $allErrorData, $errorTotal);
                continue;
            }
            if ($matchedOrderId) {
                $saveOrderData = $payOrderData;
                unset($saveOrderData['add_time']);
                $saveOrderData['summary_id'] = $summary_id;
                $saveOrderData['order_no'] = $summaryData['order_no'];
                $saveOrderData['service_start_time'] = $matchedUnpaidOrder['service_start_time'];
                $saveOrderData['service_end_time'] = $matchedUnpaidOrder['service_end_time'];
                $dbHouseNewPayOrder->saveOne(['order_id' => $order_id], $saveOrderData);
            } else {
                $dbHouseNewPayOrder->saveOne(['order_id' => $order_id], [
                    'summary_id' => $summary_id,
                    'order_no' => $summaryData['order_no'],
                ]);
            }
            $importRanges[$rangeKey][] = ['start' => $service_start_time, 'end' => $service_end_time];
            $successInsertTotal++;
        }

        $this->queueReids()->set($processCacheKey, [
            'process'            => 100,
            'readRowTotal'       => $rowTrueNum,
            'successInsterTotal' => $successInsertTotal,
            'errorTotal'         => $errorTotal,
        ], 3600);
        $spreadsheet->disconnectWorksheets();
        $excelTitle[$nextIndex] = '失败原因';
        $record = [
            'village_id' => $village_id,
            'type' => $params['type'],
            'file_name' => basename($inputFileName),
            'import_msg' => '总行数:'.$rowTrueNum.'条，成功:'.$successInsertTotal.'条，失败:'.$errorTotal.'条',
            'add_time' => time(),
        ];
        if (!empty($allErrorData)) {
            $record['status'] = 0;
            $record['file_url'] = $ImportExcelService->exportExcelMethod($village_id, $excelTitle, $allErrorData, $params['type'].'_'.time().'_'.uniqid());
        } else {
            $record['status'] = 1;
            $record['file_url'] = '';
        }
        $end_time = $ImportExcelService->getMillisecondMethod();
        $record['duration'] = sprintf('%.2f', intval($end_time - $begin_time) / 1000);
        $res = $NewBuildingService->addHouseVillageImportRecordMethod($record);
        fdump_api(['cycleBillPayExcel done'.__LINE__, $record, $res, $allErrorData], 'import_excel/cycleBillPayExcelImportExecute', 1);
        return [
            'readRowTotal'       => $rowTrueNum,
            'successInsterTotal' => $successInsertTotal,
            'errorTotal'         => $errorTotal,
            'file_url'           => isset($record['file_url']) ? $record['file_url'] : '',
            'import_msg'         => $record['import_msg'],
        ];
    }

    private function trimExcelValue($data, $key)
    {
        $value = isset($data[$key]) && $data[$key] ? $data[$key] : '';
        $value = strval($value);
        return trim($value);
    }

    private function trimExcelCellValue($sheet, $column, $row, $data)
    {
        try {
            $value = $sheet->getCell($column . $row)->getFormattedValue();
            $value = strval($value);
            if (trim($value) !== '') {
                return trim($value);
            }
        } catch (\Throwable $e) {
        }
        return $this->trimExcelValue($data, $column);
    }

    private function normalizeExcelMoney($value)
    {
        $value = str_replace([',', '￥', '¥', ' '], '', strval($value));
        return sprintf('%.2f', round(floatval($value), 2));
    }

    private function appendBillExcelError($data, $nextIndex, $reason, &$allErrorData, &$errorTotal)
    {
        $data[$nextIndex] = $reason;
        $allErrorData[] = $data;
        $errorTotal++;
    }

    private function normalizeBillStartTime($value)
    {
        $time = $this->traitConvertDateToInt($value);
        return $time > 100 ? strtotime(date('Y-m-d', $time)) : 0;
    }

    private function normalizeBillEndTime($value)
    {
        $time = $this->traitConvertDateToInt($value);
        return $time > 100 ? strtotime(date('Y-m-d', $time)) + 24 * 3600 - 1 : 0;
    }

    private function normalizeCycleBillDateTime($value, $isEnd = false)
    {
        $value = trim(strval($value));
        if ($value === '') {
            return 0;
        }
        if (is_numeric($value)) {
            $time = $this->traitConvertDateToInt($value);
            return $time > 100 ? strtotime(date('Y-m-d', $time)) + ($isEnd ? 24 * 3600 - 1 : 0) : 0;
        }

        $value = str_replace('/', '-', $value);
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})(?:\s+\d{1,2}:\d{1,2}(?::\d{1,2})?)?$/', $value, $match)) {
            $year = intval($match[1]);
            $month = intval($match[2]);
            $day = intval($match[3]);
        } elseif (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})(?:\s+\d{1,2}:\d{1,2}(?::\d{1,2})?)?$/', $value, $match)) {
            $month = intval($match[1]);
            $day = intval($match[2]);
            $year = intval($match[3]);
        } else {
            return 0;
        }

        if (!checkdate($month, $day, $year)) {
            return 0;
        }
        return mktime($isEnd ? 23 : 0, $isEnd ? 59 : 0, $isEnd ? 59 : 0, $month, $day, $year);
    }

    private function normalizeCycleBillPayTime($value)
    {
        $value = trim(strval($value));
        if ($value === '') {
            return 0;
        }
        if (is_numeric($value)) {
            $time = $this->traitConvertDateToInt($value);
            return $time > 100 ? $time : 0;
        }

        $value = str_replace('/', '-', $value);
        $hour = 0;
        $minute = 0;
        $second = 0;
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})(?:\s+(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?$/', $value, $match)) {
            $year = intval($match[1]);
            $month = intval($match[2]);
            $day = intval($match[3]);
            $hour = isset($match[4]) ? intval($match[4]) : 0;
            $minute = isset($match[5]) ? intval($match[5]) : 0;
            $second = isset($match[6]) ? intval($match[6]) : 0;
        } elseif (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})(?:\s+(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?$/', $value, $match)) {
            $month = intval($match[1]);
            $day = intval($match[2]);
            $year = intval($match[3]);
            $hour = isset($match[4]) ? intval($match[4]) : 0;
            $minute = isset($match[5]) ? intval($match[5]) : 0;
            $second = isset($match[6]) ? intval($match[6]) : 0;
        } else {
            return 0;
        }

        if (!checkdate($month, $day, $year) || $hour > 23 || $minute > 59 || $second > 59) {
            return 0;
        }
        return mktime($hour, $minute, $second, $month, $day, $year);
    }

    private function isTimeRangeOverlap($start, $end, $oldStart, $oldEnd)
    {
        return $start <= $oldEnd && $end >= $oldStart;
    }

    private function paidEffectiveOrderRaw($alias = 'o')
    {
        $prefix = $alias ? '`' . $alias . '`.' : '';
        return '(' . $prefix . '`is_paid`=1'
            . ' AND ' . $prefix . '`is_discard`=1'
            . ' AND ' . $prefix . '`is_refund`=1'
            . ' AND IFNULL(' . $prefix . '`refund_money`,0)<=0)';
    }

    private function matchImportRoom($dbHouseVillageSingle, $dbHouseVillageFloor, $dbHouseVillageLayer, $dbHouseVillageUserVacancy, $village_id, $single, $floor, $layer, $room)
    {
        $singleInfo = $dbHouseVillageSingle->getOne([
            ['village_id', '=', $village_id],
            ['status', '=', 1],
            ['single_name', '=', $single],
        ], 'id, single_name');
        if ($singleInfo && !is_array($singleInfo)) {
            $singleInfo = $singleInfo->toArray();
        }
        if (empty($singleInfo)) {
            $singleInfo = $dbHouseVillageSingle->getOne([
                ['village_id', '=', $village_id],
                ['status', '=', 1],
                ['single_number', '=', $single],
            ], 'id, single_name');
            if ($singleInfo && !is_array($singleInfo)) {
                $singleInfo = $singleInfo->toArray();
            }
        }
        if (!isset($singleInfo['id']) || !$singleInfo['id']) {
            return ['error' => '对应【楼栋】不存在'];
        }
        $single_id = $singleInfo['id'];

        $floorInfo = $dbHouseVillageFloor->getOne([
            ['village_id', '=', $village_id],
            ['single_id', '=', $single_id],
            ['status', '=', 1],
            ['floor_name', '=', $floor],
        ], 'floor_id, floor_name, single_id');
        if ($floorInfo && !$floorInfo->isEmpty()) {
            $floorInfo = $floorInfo->toArray();
        } else {
            $floorInfo = $dbHouseVillageFloor->getOne([
                ['village_id', '=', $village_id],
                ['single_id', '=', $single_id],
                ['status', '=', 1],
                ['floor_number', '=', $floor],
            ], 'floor_id, floor_name, single_id');
            if ($floorInfo && !$floorInfo->isEmpty()) {
                $floorInfo = $floorInfo->toArray();
            }
        }
        if (!isset($floorInfo['floor_id']) || !$floorInfo['floor_id']) {
            return ['error' => '对应【单元】不存在'];
        }
        $floor_id = $floorInfo['floor_id'];

        $layerInfo = $dbHouseVillageLayer->getOne([
            ['village_id', '=', $village_id],
            ['floor_id', '=', $floor_id],
            ['status', '=', 1],
            ['layer_name', '=', $layer],
        ], 'id, layer_name, single_id, floor_id');
        if ($layerInfo && !$layerInfo->isEmpty()) {
            $layerInfo = $layerInfo->toArray();
        } else {
            $layerInfo = $dbHouseVillageLayer->getOne([
                ['village_id', '=', $village_id],
                ['floor_id', '=', $floor_id],
                ['status', '=', 1],
                ['layer_number', '=', $layer],
            ], 'id, layer_name, single_id, floor_id');
            if ($layerInfo && !$layerInfo->isEmpty()) {
                $layerInfo = $layerInfo->toArray();
            }
        }
        if (!isset($layerInfo['id']) || !$layerInfo['id']) {
            return ['error' => '对应【楼层】不存在'];
        }
        $layer_id = $layerInfo['id'];

        $roomInfo = $dbHouseVillageUserVacancy->getOne([
            ['village_id', '=', $village_id],
            ['single_id', '=', $single_id],
            ['floor_id', '=', $floor_id],
            ['layer_id', '=', $layer_id],
            ['status', '<>', 0],
            ['is_del', '=', 0],
            ['room', '=', $room],
        ], 'pigcms_id, room, village_id, single_id, floor_id, layer_id');
        if ($roomInfo && !is_array($roomInfo)) {
            $roomInfo = $roomInfo->toArray();
        }
        if (empty($roomInfo)) {
            $roomInfo = $dbHouseVillageUserVacancy->getOne([
                ['village_id', '=', $village_id],
                ['single_id', '=', $single_id],
                ['floor_id', '=', $floor_id],
                ['layer_id', '=', $layer_id],
                ['status', '<>', 0],
                ['is_del', '=', 0],
                ['room_number', '=', $room],
            ], 'pigcms_id, room, village_id, single_id, floor_id, layer_id');
            if ($roomInfo && !is_array($roomInfo)) {
                $roomInfo = $roomInfo->toArray();
            }
        }
        if (!isset($roomInfo['pigcms_id']) || !$roomInfo['pigcms_id']) {
            return ['error' => '对应【房间号】不存在'];
        }
        return [
            'single_id' => $single_id,
            'floor_id' => $floor_id,
            'layer_id' => $layer_id,
            'room_id' => $roomInfo['pigcms_id'],
        ];
    }

}
