<?php

declare (strict_types = 1);
/**
 * Author lkz
 * Date: 2023/1/11
 * Time: 20:14
 */

namespace app\command;

use app\common\ChunkReadFilter;
use app\common\model\service\config\ConfigCustomizationService;
use app\community\model\service\HouseVillageConfigService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseVillageSingleService;
use app\community\model\service\ImportExcelService;
use app\community\model\service\NewBuildingService;
use app\community\model\service\PackageOrderService;
use app\consts\RoomConst;
use app\traits\CacheTypeTraits;
use app\traits\CommonTraits;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use app\common\model\service\plan\PlanService;

class RoomImport extends Command
{

    use CacheTypeTraits,CommonTraits;

    protected function configure()
    {
        // 指令配置
        $this->setName('roomImport')
            ->addArgument('json',Argument::IS_ARRAY)
            ->setDescription('房间命令行导入指令');
    }

    //临时打印日志
    private function roomImportFdump($data, $filename='test', $append=false){
        $root=dirname(dirname(dirname(__DIR__)));
        $fileName =  rtrim($root,'/') . '/api/log/' . date('Ymd') . '/' . $filename . '_fdump.php';
        $dirName = dirname($fileName);
        if(!file_exists($dirName)){
            mkdir($dirName, 0777, true);
        }
        $debug_trace = debug_backtrace();
        $file = __FILE__ ;
        $line = "unknown";
        if (isset($debug_trace[0]) && isset($debug_trace[0]['file'])) {
            $file = $debug_trace[0]['file'] ;
            $line = $debug_trace[0]['line'];
        }
        $f_l = '['.$file.' : '.$line.']';

        if($append){
            if(!file_exists($fileName)){
                file_put_contents($fileName,'<?php');
            }
            file_put_contents($fileName,PHP_EOL.$f_l.PHP_EOL.date('Y-m-d H:i:s').' '.PHP_EOL.var_export($data,true).PHP_EOL,FILE_APPEND);
        }
        else{
            file_put_contents($fileName,'<?php'.PHP_EOL.date('Y-m-d H:i:s').' '.PHP_EOL.var_export($data,true));
        }
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
        $this->roomImportFdump(['接收参数=='.__LINE__,$json,$params],'import_excel/roomImportExecute',1);

        $inputFileName             = $params['inputFileName']; //文件路径
        $excelBinFieldRelationShip = $params['excelBinFieldRelationShip'];//对应关系数据
        $selectWorkSheetIndex      = $params['selectWorkSheetIndex']; //选择的选择的Sheet索引
        $worksheetName             = $params['worksheetName']; //选择的选择的Sheet表
        $nowVillageInfo            = $params['nowVillageInfo']; //小区信息
        $isRepeated                = $ImportExcelService->checkReplaceFieldSkipMethod($params['find_type']);//数据重复时,true 跳过[默认]，false覆盖
        $replaceField              = $params['find_value'];//当{$find_value}重复时覆盖现有楼栋数据
        $export_from               = isset($params['export_from']) ? $params['export_from']:'';
        $colAndFileds = array_column($excelBinFieldRelationShip,'value','key');
        $village_id = $nowVillageInfo['village_id'];
        // 正则检查是否为纯数字
        $check_number = "/^[0-9]+$/";
        $pattern_number = "/[^0-9]/";
        $villageConfig = (new HouseVillageConfigService())->getConfig(['village_id'=>$village_id],'village_single_support_digit');

        $village_single_support_digit = intval($villageConfig['village_single_support_digit']) ? intval($villageConfig['village_single_support_digit']) : 2;
        if (!$village_single_support_digit || $village_single_support_digit!=3) {
            $village_single_support_digit = 2;
        }
        $max_num = 99;
        if ($village_single_support_digit==3) {
            $max_num = 999;
        }
        $houseVillageService=new HouseVillageService();
        $villageInfoExtend=$houseVillageService->getHouseVillageInfoExtend(['village_id'=>$village_id],'*');
        $property_id=$villageInfoExtend['property_id'];
        if(isset($nowVillageInfo['property_id']) && $nowVillageInfo['property_id']>0){
            $property_id=$nowVillageInfo['property_id'];
        }
        $villageArr=array();
        $villageArr[]=$village_id;
        $whereArrTmp=array(['property_id','=',$property_id]);
        $whereArrTmp[]=array('village_id','>',0);
        $whereArrTmp[]=array('status','<',5);
        $villageObj=$houseVillageService->getList($whereArrTmp,'village_id',0,1000);
        if($villageObj && !$villageObj->isEmpty()){
            $villages=$villageObj->toArray();
            foreach ($villages as $vvv){
                $villageArr[]=$vvv['village_id'];
            }
        }
        $villageArr=array_unique($villageArr);
        //购买的功能套餐和房间套餐的总房间数量 start 2020/8/31
        $package_where=array();
        $package_where[]=['property_id','=',$property_id];
        $package_where[]=['status', '=' ,1];
        $package_where[]=['order_type','<>',3];
        $nowTime=time();
        $package_where[]=['package_end_time|package_try_end_time','>', $nowTime];
        $package_order_Arr = (new PackageOrderService())->getPackageOrderList($package_where,'*',0,0);
        $this->roomImportFdump(['room_num'.__LINE__,'package_order_Arr'=>$package_order_Arr],'import_excel/roomImportExecute',1);
        $package_order=$package_order_Arr['list'];
        $order_idArr=array();
        $package_order_num=0;
        if (empty($package_order)){
            $package_order = '';
            $map=array();
            $map[]=['property_id', '=',$property_id];
            $map[]=['status', '=' ,0];
            $map[]=['package_try_end_time','>', $nowTime];
            $package_order_Arr = (new PackageOrderService())->getPackageOrderList($map,'*',1,1);
            $package_order=$package_order_Arr['list'];
            $package_order=$package_order['0'];
            if(!empty($package_order)){
                $order_idArr[]=$package_order['order_id'];
                $package_order_num+=$package_order['room_num'];
            }
        }else{
            foreach($package_order as $pov){
                $order_idArr[]=$pov['order_id'];
                $package_order_num+=$pov['room_num'];
            }
            $package_order=$package_order['0'];
        }
        $pr_where['property_id'] = $property_id;
        $pr_where['pay_status'] = 1;
        $room_num = 0;
        if ($package_order) {
            //$package_order_num=$package_order['room_num'];
            $room_where=array();
            $room_where[]=['package_order_id','in',$order_idArr];
            $room_where[]=['property_id', '=',$property_id];
            $room_where[]=['status', '=' ,1];
            $room_order_list =(new PackageOrderService())->getRoomOrderList($room_where,'room_num,num',0,0);
            if($room_order_list){
                foreach ($room_order_list as $rov){
                    $room_num +=$rov['room_num']*$rov['num'];
                }
            }
        }
        $total_room_num = $package_order_num + $room_num;
        $contract_time_start=$villageInfoExtend ? $villageInfoExtend['contract_time_start']:0;
        $contract_time_end=$villageInfoExtend ? $villageInfoExtend['contract_time_end']:0;
        /*
        $whereArr=array();
        $whereArr[]=['village_id','=' ,$village_id] ;
        $whereArr[]=['is_del', '=',0];
        $exist_room_count = $houseVillageService->getVillageRoomNum($whereArr);
        */
        $whereArr=array();
        $whereArr[]=['village_id','in' ,$villageArr] ;
        $whereArr[]=['is_del', '=',0];
        $all_exist_room_count = $houseVillageService->getVillageRoomNum($whereArr);
        
        $surplus_count = $total_room_num - $all_exist_room_count;//剩余房间数量
        $this->roomImportFdump(['room_num'.__LINE__,$total_room_num,'all_exist_room_count'=>$all_exist_room_count],'import_excel/roomImportExecute',1);
        $total_count = $surplus_count >0 ?  $surplus_count:0 ;
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
            $this->roomImportFdump(['processCacheKey设置失败=='.__LINE__,$processCacheKey,$processInfo,$params,$e->getMessage()],'import_excel/roomImportExecuteError',1);
            return true;
        }
        $inputFileName = root_path() . '/../'.$inputFileName;

        $reader=$ImportExcelService->getFileReaderMethod($inputFileName,false);
        $spreadsheet = $reader->setReadDataOnly(true)->setReadEmptyCells(false)->load($inputFileName);
        $worksheetData = $reader->listWorksheetInfo($inputFileName);
        $sheet = $spreadsheet->getSheet($selectWorkSheetIndex);
        $highestRow = $sheet->getHighestRow(); // 总行数（不准确）)
        $highestColumn = $sheet->getHighestColumn();// 最后有数据的一列，比如 S 列
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);//把列字母转为数字,比如可以得到有 10 列
        $chunkSize = 5; //一次读取多少条
        $chunkFilter = new ChunkReadFilter();
        $reader->setReadFilter($chunkFilter);
        $j = 1;
        $allErrorData = [];
        $allInsterData = []; //校验一下数据，用完可以删除
        $readRowTotal       = $highestRow - 1; //插件读取的总行数（不准确，如果Excel格式有问题的情况）
        $successInsterTotal = 0; //总共更新条数
        $errorTotal         = 0; //总失败行数（不包含空行）
        if ($readRowTotal <= 0){
            return $this->queueReids()->set($processCacheKey,100,3); //进度
        }
        $excelTitle=[];
        $nextIndex=Coordinate::stringFromColumnIndex($highestColumnIndex+1);
        if ($highestColumnIndex > 17) {
            // 目前获取存在问题 默认下错误吗列数
            $nextIndex = 'R';
        }
        //↓↓↓表格读取数据开始↓↓↓
        $houseVillageSingleService= new HouseVillageSingleService();
        $single_arr = [];
        $floor_arr = [];
        $floor_temporary=[];
        //$this->roomImportFdump(['for'.__LINE__,$highestRow],'import_excel/roomImportExecute',1);
        //for ($startRow = 2; $startRow <= $highestRow; $startRow += $chunkSize) {
        //$chunkFilter->setRows($startRow, $chunkSize);
        //$spreadsheet = $reader->load($inputFileName);
        $sheetData = $spreadsheet->getSheetByName($worksheetName)->toArray('', true, true, true);
        $this->roomImportFdump(['sheetData=>'.__LINE__,$highestRow,$sheetData],'import_excel/roomImportSheetData'.$village_id,1);
        if(empty($sheetData)){
            //$this->roomImportFdump(['sheetData为空=='.__LINE__,$highestRow,$sheetData],'import_excel/roomImportExecuteError',1);
            return true;
        }
        $excelTitle=$sheetData[1];
        unset($sheetData[1]); //去掉表头
        if(!empty($excelTitle['A']) && empty($excelTitle['B'])&& empty($excelTitle['C'])&& empty($excelTitle['D'])){
            $excelTitle=$sheetData[2];
            unset($sheetData[2]); //去掉表头
            $readRowTotal=$highestRow - 2;
            if ($readRowTotal <= 0){
                return $this->queueReids()->set($processCacheKey,100,3); //进度
            }
        }
        //$this->roomImportFdump(['foreach_start'.__LINE__,$sheetData,$colAndFileds],'import_excel/roomImportExecute',1);
        $rowNum=0;
        $rowTrueNum=0;
        $configCustomizationService= new ConfigCustomizationService();
        $jz_open = $configCustomizationService->getWSXCustomizedOpen();
        $isAihorseCustomizedJudge=$configCustomizationService->getAihorseCustomizedJudge(true);
        $pattern = "/[^0-9]/";
        foreach ($sheetData as  $dk => $data){
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
                'successInsterTotal' => $successInsterTotal, //总共插入表成功条数
                'errorTotal'         => $errorTotal, //总共插入表失败条数
                'completeTime' =>date('Y-m-d H:i:s'),
                'rowNum'=>$rowNum,
                'highestRow'=>$highestRow,
            ];
            $insert_rets=$this->queueReids()->set($processCacheKey,$processInfo,3600); //进度（理论上暂时还不会有超过一个小时的数据要执行）
            $this->roomImportFdump(['insert_rets'.__LINE__,$insert_rets,$processCacheKey,$processInfo],'import_excel/roomImportExecute',1);

            $update_id=0;
            // $this->roomImportFdump(['foreach_start_data'.__LINE__,$data],'import_excel/roomImportExecute',1);
            $usernum= isset($data[$colAndFileds['usernum']]) ? $data[$colAndFileds['usernum']] : '';//物业编码
            $usernum=$usernum ? strval($usernum):'';
            $usernum=trim($usernum);
            $single_name= isset($data[$colAndFileds['single_name']]) ? $data[$colAndFileds['single_name']] : '';//楼栋名称
            $single_number= isset($data[$colAndFileds['single_number']]) ? $data[$colAndFileds['single_number']] : '';//楼栋编号
            $single_number=$single_number ? strval($single_number):'';
            $single_number=trim($single_number);
            $floor_name= isset($data[$colAndFileds['floor_name']]) ? $data[$colAndFileds['floor_name']] : '';//单元名称
            $floor_number  = isset($data[$colAndFileds['floor_number']])   ? $data[$colAndFileds['floor_number']]   : '';//单元编号
            $floor_number=$floor_number ? strval($floor_number):'';
            $floor_number=trim($floor_number);
            $layer_name= isset($data[$colAndFileds['floor_name']]) ? $data[$colAndFileds['layer_name']] : '';//楼层名称
            $layer_number  = isset($data[$colAndFileds['floor_number']])   ? $data[$colAndFileds['layer_number']]   : '';//楼层编号
            $layer_number=$layer_number ? strval($layer_number):'';
            $layer_number=trim($layer_number);
            $room_name    = isset($data[$colAndFileds['room_name']])     ? $data[$colAndFileds['room_name']]     : '';//房间号
            $room_name=$room_name ? strval($room_name):'';
            $room_name=trim($room_name);
            $room_number  = isset($data[$colAndFileds['room_number']])   ? $data[$colAndFileds['room_number']]   : '';//房间编号
            $room_number=$room_number ? strval($room_number):'';
            $room_number=trim($room_number);
            $housesize = isset($data[$colAndFileds['housesize']])  ? $data[$colAndFileds['housesize']]  : 0;//面积(m²)
            $house_type         = isset($data[$colAndFileds['house_type']])          ? $data[$colAndFileds['house_type']]          : '';//使用类型(填住宅/商铺/办公)
            $house_type=$house_type ? strval($house_type):'';
            $house_type=trim($house_type);
            $property_starttime = isset($data[$colAndFileds['property_starttime']])  ? $data[$colAndFileds['property_starttime']]  : '';//物业开始时间
            $property_endtime   = isset($data[$colAndFileds['property_endtime']])    ? $data[$colAndFileds['property_endtime']]    : '';//物业结束时间

            $room_alias_id   = isset($data[$colAndFileds['room_alias_id']])    ? $data[$colAndFileds['room_alias_id']]    : '';//房间别名编号
            $room_alias_id=$room_alias_id ? strval($room_alias_id):'';
            $room_alias_id=trim($room_alias_id);
            $sort   = isset($data[$colAndFileds['sort']])    ? $data[$colAndFileds['sort']]    : '';//排序
            $heating_size = isset($data[$colAndFileds['heating_size']])  ? $data[$colAndFileds['heating_size']]  : 0;//供暖面积(m²)
            $billable_size = isset($data[$colAndFileds['billable_size']])  ? $data[$colAndFileds['billable_size']]  : 0;//计费面积(m²)
            $house_in_time_str = isset($data[$colAndFileds['house_in_time']])  ? $data[$colAndFileds['house_in_time']]  : 0;//入住日期
            //$water_balance = isset($data[$colAndFileds['water_balance']])  ? $data[$colAndFileds['water_balance']]  : 0;//水费预存金额
            $water_balance=0;
            if((!empty($single_number) && $single_number=='楼栋编号') ||
                (!empty($floor_number) && $floor_number=='单元编号') ||
                (!empty($layer_number) && $layer_number=='楼层编号') ||
                (!empty($room_name) && $room_name=='房间号') ||
                (!empty($single_name) && ($single_name=='楼栋' || $single_name=='楼栋名称'))  ||
                (!empty($floor_name) && ($floor_name=='单元' || $floor_name=='单元名称'))  ||
                (!empty($layer_name) && ($layer_name=='楼层' || $layer_name=='楼层名称'))
            ){
                //$this->roomImportFdump(['数据为空为空=='.__LINE__,$data],'import_excel/roomImportExecute',1);
                continue;
            }
            if(empty($single_number) && empty($floor_number)&& empty($layer_number) && empty($room_name)&& empty($single_name)&& empty($floor_name)&& empty($layer_name)){
                continue;
            }
            $rowTrueNum++;
            if ($contract_time_end<=1||$contract_time_start<=1){
                $data[$nextIndex] = "小区合同时间未设置，请先到小区基本信息中设置合同起始时间";
                $allErrorData[] =  $data;
                $errorTotal += 1;
                continue;
            }
            //套餐 start
            if(empty($package_order)){
                $data[$nextIndex] = "您需要购买功能套餐,无法导入！";
                $allErrorData[] =  $data;
                $errorTotal += 1;
                continue;
            }else if ($surplus_count <= 0) {
                $data[$nextIndex] = "成功导入{$successInsterTotal}房间数据,剩下的房间可通过购买房间套餐或者升级功能套餐后继续导入！";
                $allErrorData[] =  $data;
                $errorTotal += 1;
                continue;
            }
            //套餐 end
            //楼栋编号
            if (!empty($single_number)){
                if (!is_numeric($single_number)){
                    if(!preg_match($check_number,$single_number)){
                        $data[$nextIndex]= '楼栋编号只允许数字-'.$single_number;
                        $allErrorData[] =  $data;
                        $errorTotal += 1;
                        continue;
                    }
                }
                if ($single_number < 0 || $single_number > $max_num) {
                    $data[$nextIndex]= "该楼栋编号【{$single_number}】不符合规则，请填写其他 1 - {$max_num} 号的楼栋编号！";
                    $allErrorData[] =  $data;
                    $errorTotal += 1;
                    continue;
                }
            } else{
                $data[$nextIndex]= '请填写楼栋编号【必填项】';
                $allErrorData[] =  $data;
                $errorTotal += 1;
                continue;
            }
            //楼栋名称
            if (empty($single_name)){
                $data[$nextIndex]= '请填写楼栋名称【必填项】';
                $allErrorData[] =  $data;
                $errorTotal += 1;
                continue;
            }
            if (!is_null($single_name) && !is_numeric($single_name)){
                $single_name = htmlspecialchars($single_name, ENT_QUOTES,'UTF-8');
            }
            //$single_name_num=filter_var($single_name, FILTER_SANITIZE_NUMBER_INT);
            $single_name_num=preg_replace('/[^0-9]/', '', $single_name);
            //单元编号
            if (!empty($floor_number)){
                if (!is_numeric($floor_number)){
                    if(!preg_match($check_number,$floor_number)){
                        $data[$nextIndex]= '单元编号只允许数字-'.$floor_number;
                        $allErrorData[] =  $data;
                        $errorTotal += 1;
                        continue;
                    }
                }
            } else{
                $data[$nextIndex]= '请填写单元编号【必填项】';
                $allErrorData[] =  $data;
                $errorTotal += 1;
                continue;
            }
            //楼栋名称
            if (empty($floor_name)){
                $data[$nextIndex]= '请填写单元名称【必填项】';
                $allErrorData[] =  $data;
                $errorTotal += 1;
                continue;
            }
            if (!is_null($floor_name) && !is_numeric($floor_name)){
                $floor_name = htmlspecialchars($floor_name, ENT_QUOTES,'UTF-8');
            }
            $floor_name_num=preg_replace('/[^0-9]/', '', $floor_name);
            //楼层编号
            if (!empty($layer_number)){
                if (!is_numeric($layer_number)){
                    if(!preg_match($check_number,$layer_number)){
                        $data[$nextIndex]= '楼层编号只允许数字-'.$layer_number;
                        $allErrorData[] =  $data;
                        $errorTotal += 1;
                        continue;
                    }
                }
            } else{
                $data[$nextIndex]= '请填写楼层编号【必填项】';
                $allErrorData[] =  $data;
                $errorTotal += 1;
                continue;
            }
            //楼层名称
            if (empty($layer_name)){
                $data[$nextIndex]= '请填写楼层名称【必填项】';
                $allErrorData[] =  $data;
                $errorTotal += 1;
                continue;
            }
            if (!is_null($layer_name) && !is_numeric($layer_name)){
                $layer_name = htmlspecialchars($layer_name, ENT_QUOTES,'UTF-8');
            }
            $layer_name_num=preg_replace('/[^0-9]/', '', $layer_name);
            if (empty($room_name)) {
                $data[$nextIndex]= '请填写房间号【必填项】';
                $allErrorData[] =  $data;
                $errorTotal += 1;
                continue;
            }
            $room_name_num=preg_replace('/[^0-9]/', '', $room_name);
            if ( empty($room_number)) {
                $data[$nextIndex]= '请填写房间编号【必填项】';
                $allErrorData[] =  $data;
                $errorTotal += 1;
                continue;
            }
            
            if(empty($house_type)){
                $data[$nextIndex]= '请填写房间使用类型！【必填项】';
                $allErrorData[] =  $data;
                $errorTotal += 1;
                continue;
            }
            if(empty($usernum) && $single_name_num && $room_name_num){
                $usernum=$single_name_num;
                if($floor_name_num){
                    $usernum=$usernum.'-'.$floor_name_num;
                    if($layer_name_num){
                        $usernum=$usernum.'-'.$layer_name_num;
                    }
                }
                $usernum=$usernum.'-'.$room_name_num;
            }
            
            $house_type=trim($house_type);
            $house_type_int=1;
            //使用类型 1住宅 2商铺 3办公
            if ($house_type == '住宅') {
                $house_type_int = 1;
            } elseif ($house_type == '商铺') {
                $house_type_int = 2;
            } elseif ($house_type == '办公') {
                $house_type_int = 3;
            }else{
                $data[$nextIndex]= '房间使用类型请填写错误了！';
                $allErrorData[] =  $data;
                $errorTotal += 1;
                continue;
            }

            if(empty($property_starttime)&&!empty($property_endtime)){
                $data[$nextIndex]= '请填写物业开始时间！';
                $allErrorData[] =  $data;
                $errorTotal += 1;
                continue;
            }else if(!empty($property_starttime)&&empty($property_endtime)){
                $data[$nextIndex]= '请填写物业结束时间！';
                $allErrorData[] =  $data;
                $errorTotal += 1;
                continue;
            }
            //房间的物业时间
            if ($property_starttime) {
                $property_starttime =$this->traitConvertDateToInt($property_starttime);
                if(empty($property_starttime)||($property_starttime<31507200)){
                    $data[$nextIndex]= '物业开始时间格式错误无法解析，请检查！';
                    $allErrorData[] =  $data;
                    $errorTotal += 1;
                    continue;
                }
            }
            if ($property_endtime) {
                $property_endtime=$this->traitConvertDateToInt($property_endtime);
                if(empty($property_endtime)||($property_endtime<31507200)){
                    $data[$nextIndex]= '物业结束时间格式错误无法解析，请检查！';
                    $allErrorData[] =  $data;
                    $errorTotal += 1;
                    continue;
                }
            }
            $house_in_time=0;
            if ($house_in_time_str && $isAihorseCustomizedJudge) {
                $house_in_time=$this->traitConvertDateToInt($house_in_time_str);
                if(empty($house_in_time)||($house_in_time<31507200)){
                    $data[$nextIndex]= '入住日期格式错误无法解析，请检查！';
                    $allErrorData[] =  $data;
                    $errorTotal += 1;
                    continue;
                }
            }
            
            $sort=!empty($sort) ? intval($sort):0;
            $sort=$sort>0 ?$sort:0;
            if ($single_number && strlen($single_number) < $village_single_support_digit) {
                // 不足位 补足对应位
                $single_number = str_pad($single_number, $village_single_support_digit, "0", STR_PAD_LEFT);
            }
            if ($floor_number && strlen($floor_number) < 2) {
                // 不足2位 补足2位
                $floor_number = str_pad($floor_number, 2, "0", STR_PAD_LEFT);
            }
            if ($layer_number && strlen($layer_number) < 2) {
                // 不足2位 补足2位
                $layer_number = str_pad($layer_number, 2, "0", STR_PAD_LEFT);
            }
            if ($room_number && strlen($room_number) < 2) {
                // 不足2位 补足2位
                $room_number = str_pad($room_number, 2, "0", STR_PAD_LEFT);
            }
            // $this->roomImportFdump(['foreach_room_data'.__LINE__,$village_id],'import_excel/roomImportExecute',1);
            /**
             * todo 跳过模式 数据新增
             */
            // 查询一下是否有同一楼栋名称信息
            $whereSingle = array();
            $whereSingle[] = ['village_id', '=', $village_id];
            $whereSingle[] = ['status', '=', 1];
            $single_data = [];
            $single_data['village_id'] = $village_id;
            $single_data['status'] = 1;
            if (!empty($single_name)) {
                $whereSingle[] = ['single_name', '=', $single_name];
                $single_data['single_name'] = $single_name;
            } elseif ($single_number) {
                $single_data['single_number'] = $single_number;
                $single_number_int = intval($single_number);
                $whereSingle[] = ['single_number', 'in', array($single_number,$single_number_int)];
            }
            $is_public_rental = 0;
            $single_id = 0;
            $single = array();
            $single_contract_time_start=0;
            $single_contract_time_end=0;
            $SingleInfoObj = $houseVillageSingleService->getSingleInfo($whereSingle);
            if ($SingleInfoObj && !$SingleInfoObj->isEmpty()) {
                $single = $SingleInfoObj->toArray();
                if (isset($single['is_public_rental'])) {
                    $is_public_rental = $single['is_public_rental'];
                }
                $single_id = $single['id'];
                $single_contract_time_start=$single['contract_time_start'];
                $single_contract_time_end=$single['contract_time_end'];
                $single_arr[$single_id] = [
                    'id' => $single_id,
                    'update_time' => $single['update_time']
                ];
            }
            $singe_set = [];
            if (empty($single)) {
                // 楼栋编号 暂时同一个小区下仅限1-99或1-999数字编号
                if ($single_number) {
                    $single_data['single_name'] = $single_name ? $single_name : $single_number;
                    $single_data['single_number'] = $single_number;
                    // 去除重复
                    $where_repeat=array();
                    $where_repeat[]= ['village_id' ,'=', $village_id];
                    $where_repeat[]= ['single_number' ,'=', $single_number];
                    $where_repeat[]= ['status' ,'<>',  4];
                    $repeat_single = $houseVillageSingleService->getSingleInfo($where_repeat,'id');
                    if ($repeat_single && $repeat_single['id']) {
                        $data[$nextIndex]="该楼栋编号【{$single_number}】已经存在，请填写其他1-{$max_num}的楼栋编号！";
                        $allErrorData[] =  $data;
                        $errorTotal += 1;
                        continue;
                    }
                }

                if($export_from=='unitRental'){
                    $is_public_rental=1;
                    $single_data['is_public_rental']=$is_public_rental;
                }

                $single_id = $houseVillageSingleService->addSingleInfo($single_data);
                if (!$single_id) {
                    $data[$nextIndex]="对应楼栋添加失败！";
                    $allErrorData[] =  $data;
                    $errorTotal += 1;
                    continue;
                }
                $single_arr[$single_id] = [
                    'id' => $single_id,
                    'update_time' => 0
                ];
            } elseif (!$single['single_number']) {
                // 楼栋编号 暂时同一个小区下仅限1-99或1-999数字编号
                if ($single_number) {
                    $singe_set = [
                        'single_number' => $single_number
                    ];

                }
            }
            // $this->roomImportFdump(['foreach_room_single'.__LINE__,$single_id],'import_excel/roomImportExecute',1);
            if (!empty($singe_set) && $single_id>0) {
                if ($singe_set['single_number']) {
                    // 去除重复
                    $where_repeat=array();
                    $where_repeat[]= ['village_id' ,'=', $village_id];
                    $where_repeat[]= ['single_number' ,'=', $singe_set['single_number']];
                    $where_repeat[]= ['id' ,'<>', $single_id];
                    $where_repeat[]= ['status' ,'<>',  4];
                    $repeat_single = $houseVillageSingleService->getSingleInfo($where_repeat,'id');
                    if ($repeat_single && $repeat_single['id']) {
                        $data[$nextIndex]="该楼栋【ID:".$single_id."】的编号【{$singe_set['single_number']}】已经存在，请填写其他1-99的楼栋编号。";
                        $allErrorData[] =  $data;
                        $errorTotal += 1;
                        continue;
                    }
                }
                // 如果需要变动 直接对应变动
                $houseVillageSingleService->saveSingleInfo(['id' => $single_id],$singe_set);
            }

            if ($property_starttime>1&&$property_endtime>1){
                if ($property_starttime>=$property_endtime){
                    $data[$nextIndex]= '物业服务结束时间不能小于物业服务开始时间！';
                    $allErrorData[] =  $data;
                    $errorTotal += 1;
                    continue;
                }
                if ($contract_time_start>1&&$contract_time_end>1){
                    $contract_time_start=intval($contract_time_start);
                    $contract_time_end=intval($contract_time_end);
                    if ($property_starttime<$contract_time_start||$property_endtime>$contract_time_end){
                        $data[$nextIndex]= '物业服务起止时间不能超出【'.date('Y-m-d',$contract_time_start).'至'.date('Y-m-d',$contract_time_end).'】的合同时间范围！';
                        $allErrorData[] =  $data;
                        $errorTotal += 1;
                        continue;
                    }
                }
            }
            $property_starttime=$property_starttime>0 ? $property_starttime:0;
            $property_endtime=$property_endtime>0 ? $property_endtime:0;
            if ($property_starttime>1 && $property_endtime>1){
                $property_endtime +=86399;
            }
            //单元
            $floor_data = [];
            $floor_data['status'] = 1;
            $floor_data['village_id'] = $village_id;
            $floor_data['single_id'] = $single_id;

            $whereFloor = array();
            $whereFloor[] = ['village_id', '=', $village_id];
            $whereFloor[] = ['status', '=', 1];
            $whereFloor[] = ['single_id', '=', $single_id];

            if (!empty($floor_name)) {
                $floor_name=strval($floor_name);
                $floor_data['floor_name'] = $floor_name;
                $whereFloor[] = ['floor_name', '=', $floor_name];
            } elseif ($floor_number) {
                $floor_data['floor_number'] = $floor_number;
                $floor_number_int = intval($floor_number);
                $whereFloor[] = ['floor_number', 'in', array($floor_number,$floor_number_int)];
            }
            //todo 过滤重复写入单元数据
            $floor_str=md5($village_id.$single_id.$floor_number);
            if(isset($floor_temporary[$floor_str]) && !empty($floor_temporary[$floor_str])){
                $house_village_floor_info=$floor_temporary[$floor_str];
            }else{
                $house_village_floor_info =$houseVillageSingleService->getFloorInfo($whereFloor);
                if($house_village_floor_info && is_object($house_village_floor_info) && !$house_village_floor_info->isEmpty()){
                    $house_village_floor_info=$house_village_floor_info->toArray();
                }
            }

            $floor_id = $house_village_floor_info['floor_id'] ? $house_village_floor_info['floor_id'] : 0;
            if ($house_village_floor_info) {
                $floor_arr[$floor_id] = [
                    'floor_id' => $floor_id,
                    'update_time' => $house_village_floor_info['update_time']
                ];
                $floor_temporary[$floor_str]=$house_village_floor_info;
            }
            $floor_set = [];
            if (empty($house_village_floor_info)) {
                $floor_data['add_time'] = time();
                $floor_data['floor_name'] = $floor_name ? $floor_name : $floor_number;
                $floor_data['floor_number'] = $floor_number;
                // 去除重复
                $where_repeat=array();
                $where_repeat[]= ['village_id' ,'=', $village_id];
                $where_repeat[]= ['single_id' ,'=', $single_id];
                $where_repeat[]= ['floor_number' ,'=', $floor_number];
                $where_repeat[]= ['status' ,'<>',  4];

                $floor_repeat=$houseVillageSingleService->getFloorInfo($where_repeat,'floor_id');
                if ($floor_repeat && $floor_repeat['floor_id']) {
                    $data[$nextIndex]= "该单元编号【{$floor_number}】已经存在，请填写其他1-99的单元编号！";
                    $allErrorData[] =  $data;
                    $errorTotal += 1;
                    continue;
                }
                if($is_public_rental>0){
                    $floor_data['is_public_rental']=1;
                }
                $floor_id = $houseVillageSingleService->addFloorInfo($floor_data);
                if (!$floor_id) {
                    $data[$nextIndex]= "对应单元添加失败";
                    $allErrorData[] =  $data;
                    $errorTotal += 1;
                    continue;
                }
                $floor_data['floor_id']=$floor_id;
                $floor_temporary[$floor_str]=$floor_data;
                $floor_arr[$floor_id] = [
                    'floor_id' => $floor_id,
                    'update_time' => 0
                ];
            } elseif (!$house_village_floor_info['floor_number']) {
                // 单元编号 暂时同一个楼栋下仅限1-99数字编号
                if ($floor_number) {
                    $floor_set = [
                        'floor_number' => $floor_number
                    ];
                }
            }
            //$this->roomImportFdump(['foreach_room_floor'.__LINE__,$floor_id],'import_excel/roomImportExecute',1);
            if ($floor_set && $floor_id) {
                if ($floor_set['floor_number']) {
                    // 去除重复
                    $where_repeat=array();
                    $where_repeat[]= ['village_id' ,'=', $village_id];
                    $where_repeat[]= ['single_id' ,'=', $single_id];
                    $where_repeat[]= ['floor_number' ,'=', $floor_set['floor_number']];
                    $where_repeat[]= ['floor_id' ,'<>', $floor_id];
                    $where_repeat[]= ['status' ,'<>',  4];
                    $floor_repeat=$houseVillageSingleService->getFloorInfo($where_repeat,'floor_id');
                    if ($floor_repeat && $floor_repeat['floor_id']) {
                        $data[$nextIndex]="该单元编号【{$floor_set['floor_number']}】已经存在，请填写其他1-99的单元编号！";
                        $allErrorData[] =  $data;
                        $errorTotal += 1;
                        continue;
                    }
                }
                // 如果需要变动 直接对应变动
                $houseVillageSingleService->saveFloorInfo(['floor_id' => $floor_id],$floor_set);
            }
            //$this->roomImportFdump(['foreach_room_floor'.__LINE__,$floor_id],'import_excel/roomImportExecute',1);
            //楼层
            $layer_data = [];
            $layer_data['village_id'] = $village_id;
            $layer_data['floor_id'] = $floor_id;
            $layer_data['single_id'] = $single_id;
            $layer_data['status'] = 1;

            $whereLayer = array();
            $whereLayer[] = ['village_id', '=', $village_id];
            $whereLayer[] = ['status', '=', 1];
            $whereLayer[] = ['single_id', '=', $single_id];
            $whereLayer[] = ['floor_id', '=', $floor_id];
            if (!empty($layer_name)) {
                $layer_data['layer_name'] = $layer_name;
                $whereLayer[] = ['layer_name', '=', $layer_name];
            } elseif ($layer_number) {
                $layer_data['layer_number'] = $layer_number;
                $layer_number_int = intval($layer_number);
                $whereLayer[] = ['layer_number', 'in', array($layer_number,$layer_number_int)];
            }
            $house_village_layer =$houseVillageSingleService->getLayerInfo($whereLayer);
            if($house_village_layer && is_object($house_village_layer) && !$house_village_layer->isEmpty()){
                $house_village_layer=$house_village_layer->toArray();
            }
            $layer_id = $house_village_layer['id'] ? $house_village_layer['id'] : 0;
            $layer_set = [];
            //$this->roomImportFdump(['house_village_layer'.__LINE__,$house_village_layer],'import_excel/roomImportExecute',1);
            if (empty($house_village_layer)) {
                $layer_data['layer_number'] = $layer_number;
                $layer_data['layer_name'] = $layer_name ? $layer_name : $layer_number;
                // 去除重复
                $where_repeat=array();
                $where_repeat[]= ['village_id' ,'=', $village_id];
                $where_repeat[]= ['single_id' ,'=', $single_id];
                $where_repeat[] = ['floor_id', '=', $floor_id];
                $where_repeat[]= ['layer_number' ,'=', $layer_number];
                $where_repeat[]= ['status' ,'<>',  4];
                $layer_repeat =$houseVillageSingleService->getLayerInfo($where_repeat,'id');
                //$this->roomImportFdump(['foreach_room_layer_repeat'.__LINE__,$layer_repeat],'import_excel/roomImportExecute',1);
                if ($layer_repeat && $layer_repeat['id']) {
                    $data[$nextIndex]= "该楼层编号【{$layer_number}】已经存在，请填写其他1-99的楼层编号！";
                    $allErrorData[] =  $data;
                    $errorTotal += 1;
                    //$this->roomImportFdump(['foreach_room_layer_repeat'.__LINE__,$allErrorData],'import_excel/roomImportExecute',1);
                    continue;
                }
                //$this->roomImportFdump(['foreach_room_layer_repeat'.__LINE__,'aaaa'],'import_excel/roomImportExecute',1);
                if($is_public_rental>0){
                    $layer_data['is_public_rental']=1;
                }
                //$this->roomImportFdump(['foreach_room_layer_data'.__LINE__,$layer_data],'import_excel/roomImportExecute',1);
                $layer_id = $houseVillageSingleService->addLayerInfo($layer_data);
                if (!$layer_id) {
                    $data[$nextIndex]= "对应添楼层加失败！";
                    $allErrorData[] =  $data;
                    $errorTotal += 1;
                    continue;
                }
            } elseif (!$house_village_layer['layer_number']) {
                // 楼层编号 暂时同一个单元下仅限1-99数字编号
                if ($layer_number) {
                    $layer_set = [
                        'layer_number' => $layer_number
                    ];
                }
            }
            //$this->roomImportFdump(['foreach_room_layer_id'.__LINE__,$layer_id],'import_excel/roomImportExecute',1);
            if ($layer_set && $layer_id) {
                if ($layer_set['layer_number']) {
                    // 去除重复
                    $where_repeat=array();
                    $where_repeat[]= ['village_id' ,'=', $village_id];
                    $where_repeat[]= ['single_id' ,'=', $single_id];
                    $where_repeat[] = ['floor_id', '=', $floor_id];
                    $where_repeat[]= ['layer_number' ,'=', $layer_set['layer_number']];
                    $where_repeat[]= ['id' ,'<>',  $layer_id];
                    $where_repeat[]= ['status' ,'<>',  4];
                    $layer_repeat =$houseVillageSingleService->getLayerInfo($where_repeat,'id');
                    if ($layer_repeat && $layer_repeat['id']) {
                        $data[$nextIndex]="该楼层编号【{$layer_set['layer_number']}】已经存在，请填写其他1-99的楼层编号！";
                        $allErrorData[] =  $data;
                        $errorTotal += 1;
                        continue;
                    }

                }
                // 如果需要变动 直接对应变动
                $houseVillageSingleService->saveLayerInfo(['id' => $layer_id],$layer_set);
            }
            //$this->roomImportFdump(['foreach_room_layer_id'.__LINE__,$layer_id],'import_excel/roomImportExecute',1);
            //房间
            if ($layer_id) {
                $vacancy_data = [];
                $vacancy_data['single_id'] = $single_id;
                $vacancy_data['floor_id'] = $floor_id;
                $vacancy_data['layer_id'] = $layer_id;
                $vacancy_data['village_id'] = $village_id;
                $vacancy_data['is_del'] = 0;//未删除的

                $whereVacancy = array();
                $whereVacancy[] = ['village_id', '=', $village_id];
                $whereVacancy[] = ['is_del', '=', 0];
                $whereVacancy[] = ['single_id', '=', $single_id];
                $whereVacancy[] = ['floor_id', '=', $floor_id];
                $whereVacancy[] = ['layer_id', '=', $layer_id];
                if ($room_number) {
                    $room_number_int = intval($room_number);
                }
                if (!empty($room_name)) {
                    $vacancy_data['room'] = $room_name;
                    $whereVacancy[] = ['room', '=',$room_name];
                } elseif ($room_number) {
                    $vacancy_data['room_number'] = $room_number;
                    $whereVacancy[] = ['room_number', 'in', array($room_number,$room_number_int)];
                }
                $house_village_user_vacancy =$houseVillageSingleService->getOneRoom($whereVacancy,'*');
                if(!empty($room_alias_id)){
                    $room_alias_where=array(['village_id','=',$village_id],['is_del','=',0],['room_alias_id','=',$room_alias_id]);
                    $room_id_tmp='';
                    if(!empty($house_village_user_vacancy)){
                        $room_id_tmp=$house_village_user_vacancy['pigcms_id'];
                        $room_alias_where[]=array('pigcms_id','<>',$house_village_user_vacancy['pigcms_id']);
                    }
                    $village_user_vacancy_tmp=$houseVillageSingleService->getOneRoom($room_alias_where,'pigcms_id,room_alias_id,name,phone');
                    if(!empty($village_user_vacancy_tmp)){
                        $data[$nextIndex]='该房间别名编号【'.$room_alias_id.'】已经存在，请确保唯一性！'.$room_id_tmp;
                        $allErrorData[] =  $data;
                        $errorTotal += 1;
                        continue;
                    }
                }
                if (empty($house_village_user_vacancy)) {
                    $user_num_by = 0;
                    //住房面积
                    $vacancy_data['housesize'] = $housesize >0 ? $housesize:0;
                    //使用类型 1住宅 2商铺 3办公
                    $vacancy_data['house_type'] = $house_type_int;
                    $vacancy_data['add_time'] = time();
                    $vacancy_data['status'] = 1;
                    $user_num_string = trim($usernum);
                    $single_join = '';
                    $floor_join = '';
                    $layer_join = '';
                    $room_join = '';
                    if (empty($usernum) && $jz_open) {
                        $single_name &&  $singleNum = preg_replace($pattern,'',$single_name);
                        if (isset($singleNum) && is_numeric($singleNum)) {
                            $single_join = $singleNum;
                        } else {
                            $single_join = $single_number;
                        }
                        $floor_name &&  $floorNum = preg_replace($pattern,'',$floor_name);
                        if (isset($floorNum) && is_numeric($floorNum)) {
                            $floor_join = $floorNum;
                        } else {
                            $floor_join = $floor_number;
                        }
                        $layer_name &&  $layerNum = preg_replace($pattern,'',$layer_name);
                        if (isset($layerNum) && is_numeric($layerNum)) {
                            $layer_join = $layerNum;
                        } else {
                            $layer_join = $layer_number;
                        }
                        $vacancy_data['room'] && $roomNum = preg_replace($pattern,'',$vacancy_data['room']);
                        if (isset($roomNum) && is_numeric($roomNum)) {
                            $room_join = $roomNum;
                        }
                    } elseif (empty($usernum) && !$jz_open) {
                        //物业编号非必填，没有填的默认生成，按照小区ID+楼栋ID+单元ID+楼层ID+房号ID生成
                        $user_num_string = $village_id . '-' . $single_id . '-' . $floor_id . '-' . $layer_id;
                        $user_num_by = RoomConst::ROOM_USERNUM_BY_AUTO_BELONG_ID;
                    } else {
                        $user_num_string = trim($usernum);
                        $user_num_by = RoomConst::ROOM_USERNUM_BY_IMPORT_USER;
                    }
                    $vacancy_data['layer'] = $layer_name;
                    if (!$room_number) {
                        $room_number = preg_replace($pattern_number, '', $vacancy_data['room']);
                        // 房间编号 同一个楼层下仅限1-9999 此处处理为4位 截取后4位
                        if ($room_number) {
                            if (strlen($room_number) > 4) {
                                // 超过4位 截取后4位
                                $room_number = substr($room_number, -4, 4);
                            } elseif (strlen($room_number) < 2) {
                                // 不足2位 补足2位
                                $room_number = str_pad($room_number, 2, "0", STR_PAD_LEFT);
                            } elseif (strlen($room_number) > 2 && strlen($room_number) < 4) {
                                // 大于2位不足4位 补足4位
                                $room_number = str_pad($room_number, 4, "0", STR_PAD_LEFT);
                            }
                            $vacancy_data['room_number'] = $room_number;
                        }
                    } else {
                        /*
                        if ($room_number_int<100 && $layer_number) {
                            $room_number = $layer_number . $room_number;
                        }
                        */
                        if (strlen($room_number) >= 2 && strlen($room_number) < 4) {
                            // 大于2位不足4位 补足4位
                            $room_number = str_pad($room_number, 4, "0", STR_PAD_LEFT);
                        }
                        $vacancy_data['room_number'] = $room_number;
                    }
                    if (!$user_num_string) {
                        !$room_join && $room_number && $room_join = $room_number;
                        if ($room_join && $layer_join && $floor_join && $single_join) {
                            if ($jz_open) {
                                $user_num_string = $single_join . '-' . $floor_join . '-' . $room_join;
                                $user_num_by = RoomConst::ROOM_USERNUM_BY_AUTO_NUMBER;
                            } else {
                                $user_num_string = $single_join . '-' . $floor_join . '-' . $layer_join . '-' . $room_join;
                                $user_num_by = RoomConst::ROOM_USERNUM_BY_AUTO_BELONG_NUMBER;
                            }
                        } else {
                            // 物业编号非必填，没有填的默认生成，按照小区ID+楼栋ID+单元ID+楼层ID+房号ID生成
                            $user_num_string = $village_id . '-' . $single_id . '-' . $floor_id . '-' . $layer_id;
                            $user_num_by = RoomConst::ROOM_USERNUM_BY_AUTO_BELONG_ID;
                        }
                    }
                    if ($single_number && $floor_number && $layer_number && $room_number) {
                        if (strlen($room_number) == 2) {
                            $property_number = $single_number . $floor_number . $layer_number . $layer_number . $room_number;
                        } elseif (strlen($room_number) == 4) {
                            $property_number = $single_number . $floor_number . $layer_number . $room_number;
                        } elseif (strlen($room_number) > 4) {
                            // 超过4位 截取后4位
                            $room_number = substr($room_number, -4, 4);
                            $vacancy_data['room_number'] = $room_number;
                            $property_number = $single_number . $floor_number . $layer_number . $room_number;
                        }
                        $vacancy_data['property_number'] = $property_number;
                    }
                    if (isset($vacancy_data['room_number'])) {
                        $where_repeat=array();
                        $where_repeat[]= ['village_id' ,'=', $village_id];
                        $where_repeat[]= ['single_id' ,'=', $single_id];
                        $where_repeat[] = ['floor_id', '=', $floor_id];
                        $where_repeat[] = ['layer_id', '=', $layer_id];
                        $where_repeat[]= ['room_number' ,'=', $vacancy_data['room_number']];
                        $where_repeat[]= ['is_del' ,'=',  0];
                        $where_repeat[]= ['status' ,'<>',  4];
                        $vacancy_repeat =$houseVillageSingleService->getOneRoom($where_repeat,'pigcms_id');
                        if ($vacancy_repeat && isset($vacancy_repeat['pigcms_id'])) {
                            $data[$nextIndex]='该房间编号【'.$vacancy_data['room_number'].'】已经存在，请填写其他1-9999的房间编号！';
                            $allErrorData[] =  $data;
                            $errorTotal += 1;
                            continue;
                        }
                    }
                    $vacancy_data['property_endtime']=$property_endtime;
                    $vacancy_data['property_starttime']=$property_starttime;

                    if($is_public_rental>0){
                        $vacancy_data['is_public_rental']=1;
                    }

                    $vacancy_data['room_alias_id']=$room_alias_id;
                    $vacancy_data['sort']=$sort;

                    $jz_wsxdsj_net_customized_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
                    if($jz_wsxdsj_net_customized_open){
                        $vacancy_data['heating_size'] = $heating_size ?: 0;
                        $vacancy_data['billable_size'] = $billable_size ?: 0;
                    }
                    $vacancy_data['usernum'] = $user_num_string;
                    $vacancy_data['user_num_by'] = $user_num_by;
                    if($isAihorseCustomizedJudge){
                        $vacancy_data['house_in_time']=$house_in_time>100 ? $house_in_time:0;
                       /** $vacancy_data['water_balance']=$water_balance>0 ? $water_balance:0; **/
                        if($house_in_time>100){
                            $vacancy_data['user_status']=1;
                        }
                    }
                    $room_id =$houseVillageSingleService->addUserRoomInfo($vacancy_data);
                    //$this->roomImportFdump(['foreach_room_room_id'.__LINE__,$room_id,$vacancy_data],'import_excel/roomImportExecute',1);
                    if($room_id){
                        if ($surplus_count > 0) {
                            $surplus_count--;
                        }
                        /**
                        if($isAihorseCustomizedJudge && isset($vacancy_data['water_balance'])){
                            $logBalanceRoomArr=array('room_id'=>$room_id,'village_id'=>$village_id,'current_v'=>$vacancy_data['water_balance']);
                            $logBalanceRoomArr['xtype']='room_water_balance';
                            $logBalanceRoomArr['change_v']=$water_balance;
                            $logBalanceRoomArr['cfrom']='import_room_add';
                            $logBalanceRoomArr['add_time']=time();
                            $logBalanceRoomArr['bak']='房间初始导入'.date('Y-m-d H:i:s');
                            $houseVillageSingleService->addRoomWaterBalanceLog($logBalanceRoomArr);
                        }
                        ***/
                        if ($property_starttime>1 && $property_endtime>1){
                            //todo order_log表需写入物业服务时间
                            $arr = [];
                            $arr['order_type'] = 'property';
                            $arr['order_name'] = '物业费';
                            $arr['room_id'] = $room_id;
                            $arr['property_id'] = $property_id;
                            $arr['village_id'] = $village_id;
                            $arr['service_start_time'] = $property_starttime;
                            $arr['service_end_time'] = $property_endtime;
                            $arr['desc'] = '导入房间物业服务时间';
                            $arr['add_time'] = time();
                            $houseVillageService->addHouseNewOrderLog($arr);
                        }
                        $vacancy_info =$houseVillageSingleService->getOneRoom(['pigcms_id'=>$room_id],'usernum');
                        $vacancy_datas=array();
                        if (empty($usernum) && $user_num_by == RoomConst::ROOM_USERNUM_BY_AUTO_BELONG_ID) {
                            // 导入的房间没有填写默认加上房屋ID
                            $vacancy_datas['usernum'] = $vacancy_info['usernum'] . '-' . $room_id;
                            $houseVillageSingleService->saveUserRoomInfo(['pigcms_id'=>$room_id],$vacancy_datas);
                        }
                        $arr = array();
                        $arr['param'] = array('vacancy_id' =>$room_id,'type'=>'house');
                        $arr['plan_time'] = time()+60;
                        $arr['space_time'] = 0;
                        $arr['add_time'] = time();
                        $arr['file'] = 'sub_house_village_third';
                        $arr['time_type'] = 1;
                        $arr['unique_id'] = 'sub_house_village_third_house_'.$room_id;
                        $arr['rand_number'] = mt_rand(1, max(cfg('config.sub_process_num'), 3));
                        $planService=new PlanService();
                        $planService->addTask($arr,1);
                    }
                    //$this->roomImportFdump(['foreach_room_end'.__LINE__,$room_id],'import_excel/roomImportExecute',1);
                } else {
                    $room_id=$house_village_user_vacancy['pigcms_id'];
                    if(!$isRepeated && $room_id >0 && !empty($replaceField) && isset($colAndFileds[$replaceField])){
                        //覆盖更新字段
                        $vvkey=$colAndFileds[$replaceField];
                        $replaceValue=(isset($data[$vvkey]) && $data[$vvkey]) ? $data[$vvkey] : '';
                        
                        if ($replaceField=='housesize') {
                            $saveBind = [
                                'housesize' => $replaceValue
                            ];
                            $whereBind = [
                                'vacancy_id' => $room_id,
                                'village_id' => $village_id,
                            ];
                            $houseVillageSingleService->saveUserBindInfo($whereBind,$saveBind);
                            $whereTmp = [
                                'pigcms_id' => $room_id,
                                'village_id' => $village_id,
                            ];
                            $houseVillageSingleService->saveUserRoomInfo($whereTmp,$saveBind);
                        }else if (($replaceField =='property_starttime' || $replaceField =='property_endtime') && $property_starttime>1 && $property_endtime>1) {
                            //todo order_log表需写入物业服务时间
                            $arr = [];
                            $arr['order_type'] = 'property';
                            $arr['order_name'] = '物业费';
                            $arr['room_id'] = $room_id;
                            $arr['property_id'] = $property_id;
                            $arr['village_id'] = $village_id;
                            $arr['service_start_time'] = $property_starttime;
                            $arr['service_end_time'] = $property_endtime;
                            $arr['desc'] = '房间再次导入修改'.date('Y-m-d H:i:s');;
                            $arr['add_time'] = time();
                            $houseVillageService->addHouseNewOrderLog($arr);
                        }else if(false && $replaceField=='water_balance' && $water_balance){
                            /***去掉不要了***/
                            $whereTmp = [
                                'pigcms_id' => $room_id,
                                'village_id' => $village_id,
                            ];
                            $water_balance_tmp=$house_village_user_vacancy['water_balance'];
                            if($water_balance>0){
                                $water_balance_tmp=$water_balance_tmp+$water_balance;
                            }else if($water_balance<0){
                                $water_balance_a=abs($water_balance);
                                $water_balance_tmp=$water_balance_tmp-$water_balance_a;
                            }
                            $saveTmpArr = [
                                'water_balance' => $water_balance_tmp
                            ];
                            $houseVillageSingleService->saveUserRoomInfo($whereTmp,$saveTmpArr);
                            $logBalanceRoomArr=array('room_id'=>$room_id,'village_id'=>$village_id,'current_v'=>$water_balance_tmp);
                            $logBalanceRoomArr['xtype']='room_water_balance';
                            $logBalanceRoomArr['change_v']=$water_balance;
                            $logBalanceRoomArr['old_v']=$house_village_user_vacancy['water_balance'];
                            $logBalanceRoomArr['cfrom']='import_room_update';
                            $logBalanceRoomArr['is_need_order']=1;
                            $logBalanceRoomArr['add_time']=time();
                            $logBalanceRoomArr['bak']='房间再次导入修改'.date('Y-m-d H:i:s');
                            $houseVillageSingleService->addRoomWaterBalanceLog($logBalanceRoomArr);
                        }else{
                            $updateRoomData=[
                                $replaceField=> $replaceValue
                            ];
                            $houseVillageSingleService->saveUserRoomInfo(['pigcms_id'=>$room_id],$updateRoomData);
                        }
                    }else{
                        $data[$nextIndex]= '当前房屋已经存在 请确认后再进行导入！';
                        $allErrorData[] =  $data;
                        $errorTotal += 1;
                        continue;
                    }
                }
            } elseif (!$single_id) {
                $data[$nextIndex]= '当前楼栋不存在或者添加失败！';
                $allErrorData[] =  $data;
                $errorTotal += 1;
                continue;
            } elseif (!$floor_id) {
                $data[$nextIndex]= '当前单元不存在或者添加失败！';
                $allErrorData[] =  $data;
                $errorTotal += 1;
                continue;
            } elseif (!$layer_id) {
                $data[$nextIndex]= '当前楼层不存在或者添加失败！';
                $allErrorData[] =  $data;
                $errorTotal += 1;
                continue;
            }

            //$this->roomImportFdump(['foreach_room_allErrorData'.__LINE__,$allErrorData],'import_excel/roomImportExecute',1);
            //组装入库数据
            $successInsterTotal += 1;
            /*
            //本次读取处理完毕
            $process =  sprintf('%.2f',$rowNum / $highestRow);
            $process=$process*100;
            if($process>0 && $process<1){
                $process=1;
            }else{
                $process=floor($process);
            }
            if ($rowNum >=$highestRow ){
                $process = 100;
            }
            $rowNum++;
            $processInfo = [
                'process'            =>$process, //进度
                'readRowTotal'       => $readRowTotal, //总共有数据行数
                'successInsterTotal' => $successInsterTotal, //总共插入表成功条数
                'errorTotal'         => $errorTotal, //总共插入表失败条数
            ];
            $insert_rets=$this->queueReids()->set($processCacheKey,$processInfo,3600); //进度（理论上暂时还不会有超过一个小时的数据要执行）
            $this->roomImportFdump(['insert_rets'.__LINE__,$insert_rets,$processCacheKey,$processInfo],'import_excel/roomImportExecute',1);
            */
        }
        //$this->roomImportFdump(['foreach_end'.__LINE__,$allErrorData,$successInsterTotal],'import_excel/roomImportExecute',1);
        //本次读取处理完毕
        $processInfo = [
            'process'            => 100, //进度
            'readRowTotal'       => $rowTrueNum, //总共有数据行数
            'successInsterTotal' => $successInsterTotal, //总共插入表成功条数
            'errorTotal'         => $errorTotal, //总共插入表失败条数
            'completeTime' =>date('Y-m-d H:i:s'),
        ];
        $insert_rets=$this->queueReids()->set($processCacheKey,$processInfo,3600); //进度（理论上暂时还不会有超过一个小时的数据要执行）
        $this->roomImportFdump(['insert_rets'.__LINE__,$insert_rets,$processCacheKey,$processInfo],'import_excel/roomImportExecute',1);
        //$j++;
        $spreadsheet->disconnectWorksheets();
        //sleep(1);
        //}
        //↑↑↑表格读取处理完成↑↑↑
        $excelTitle[$nextIndex]='失败原因';
        $record=[
            'village_id'=>$village_id,
            'type'=>$params['type'],
            'file_name'=>basename($inputFileName),
            'find_type'=>$params['find_type'],
            'find_value'=>$replaceField,
            'import_msg'=>'总行数:'.$rowNum.'条,成功:'.$successInsterTotal.'条,失败:'.$errorTotal.'条',
            'add_time'=>time()
        ];
        if (!empty($allErrorData)){
            $record['status']=0;
            //生成错误表格文件路径
            $record['file_url']=$ImportExcelService->exportExcelMethod($village_id,$excelTitle,$allErrorData,$params['type'].'_'.time().'_'.uniqid());
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
        $this->roomImportFdump(['处理完成导入=='.__LINE__,'总花费时间:'.$second.'s','time'=>date('Y-m-d H:i:s'),$record,$res,$allErrorData],'import_excel/roomImportExecute',1);
        //楼栋相关数据
        if ($single_arr) {
            $newBuildingService = new NewBuildingService();
            foreach ($single_arr as $key => $val) {
                $map=array();
                $map[]=['single_id','=',$val['id']];
                $map[]=['village_id','=', $village_id];

                if ($val['update_time'] == 0) {
                    //计算所有房间总面积
                    $room_where=$map;
                    $room_where[]=['is_del','=', 0];
                    $house_size=$houseVillageSingleService->getRoomInfoSum($room_where,'housesize');
                    //计算所有房间
                    $room_count = $houseVillageService->getVillageRoomNum($room_where);
                    //地上建筑层数
                    $layer_where=$map;
                    $layer_where[]=['layer_name','>', 0];
                    $layer_where[]=['status','<>', 4];
                    $layer_ground_arr = $newBuildingService->getHouseVillageLayerSelectMethod($layer_where,'id, layer_number');
                    $layer_ground_unique_arr = array_unique(array_column($layer_ground_arr, 'layer_number'));
                    $layer_ground_count = count($layer_ground_unique_arr);
                    //地下建筑层
                    $layer_where=$map;
                    $layer_where[]=['layer_name','<', 0];
                    $layer_where[]=['status','<>', 4];
                    $layer_underground_arr = $newBuildingService->getHouseVillageLayerSelectMethod($layer_where,'id, layer_number');
                    $layer_underground_unique_arr = array_unique(array_column($layer_underground_arr, 'layer_number'));
                    $layer_underground_count = count($layer_underground_unique_arr);
                    //单元数量
                    $floor_where=$map;
                    $floor_where[]=['status','<>', 4];
                    $floor_count = $houseVillageSingleService->getSingleFloorCount($floor_where);

                    $single_data = [
                        'measure_area' => $house_size,
                        'floor_num' => $floor_count,
                        'vacancy_num' => $room_count,
                        'upper_layer_num' => $layer_ground_count,
                        'lower_layer_num' => $layer_underground_count,
                    ];
                    $houseVillageSingleService->saveSingleInfo(['id' => $val['id']],$single_data);
                }
            }
        }
        //单元相关数据
        if ($floor_arr) {
            foreach ($floor_arr as $key => $val) {
                $floor_map=array();
                $floor_map[]=['floor_id','=', $val['floor_id']];
                $floor_map[]=['village_id','=',$village_id];
                if ($val['update_time'] == 0) {
                    //计算所有房间总面积
                    $room_where=$floor_map;
                    $room_where[]=['is_del','=', 0];
                    $house_size=$houseVillageSingleService->getRoomInfoSum($room_where,'housesize');
                    //计算所有房间
                    $room_count = $houseVillageService->getVillageRoomNum($room_where);
                    //地上建筑层数
                    $layer_where=$floor_map;
                    $layer_where[]=['layer_name','>', 0];
                    $layer_where[]=['status','<>', 4];
                    $layer_ground_count =$houseVillageSingleService->getLayerInfoCount($layer_where);
                    //最高住人层
                    $max_layer = $houseVillageSingleService->getLayerInfo($layer_where,'layer_name','layer_name desc');
                    //地下建筑层
                    $layer_where=$floor_map;
                    $layer_where[]=['layer_name','<', 0];
                    $layer_where[]=['status','<>', 4];
                    $layer_underground_count =$houseVillageSingleService->getLayerInfoCount($layer_where);
                    //最低住人层
                    $min_layer = $houseVillageSingleService->getLayerInfo($layer_where,'layer_name','layer_name asc');

                    $floor_data = [
                        'floor_area' => $house_size,
                        'house_num' => $room_count,
                        'floor_upper_layer_num' => $layer_ground_count,
                        'floor_lower_layer_num' => $layer_underground_count,
                        'start_layer_num' => $min_layer ? $min_layer : 1,
                        'end_layer_num' => $max_layer,
                    ];
                    $houseVillageSingleService->saveFloorInfo(['floor_id' => $val['floor_id']],$floor_data);
                }
            }
        }

        $this->roomImportFdump(['处理完成整个流程=='.__LINE__,'time'=>date('Y-m-d H:i:s')],'import_excel/roomImportExecute',1);
        return  true;
    }
}