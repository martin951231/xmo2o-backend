<?php

declare (strict_types = 1);
/**
 * Author lihongshu
 * Date: 2023/11/118
 * Time: 15:15
 */

namespace app\command;
use app\common\ChunkReadFilter;
use app\common\model\service\config\ConfigCustomizationService;
use app\community\model\db\HouseNewChargeRule;
use app\community\model\db\HouseNewChargeStandardBind;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\service\ImportExcelService;
use app\community\model\service\HouseVillageSingleService;
use app\community\model\service\HouseNewChargeRuleService;
use app\community\model\service\NewBuildingService;
use app\consts\newChargeConst;
use app\traits\CacheTypeTraits;
use app\traits\CommonTraits;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

class StandardVacancyBindImport extends Command
{

    use CacheTypeTraits,CommonTraits;

    protected function configure()
    {
        // 指令配置
        $this->setName('standardVacancyBindImport')
            ->addArgument('json',Argument::IS_ARRAY)
            ->setDescription('房间绑定标准命令行导入指令');
    }
    protected function execute(Input $input, Output $output)
    {
        set_time_limit(0);
        ini_set("memory_limit","1024M");
        $root=dirname(dirname(dirname(__FILE__)));
        $root=dirname($root);
        $basePath =  rtrim($root,'/');
        $_SERVER['DOCUMENT_ROOT']=$basePath;
      
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
        fdump_api(['接收参数=='.__LINE__,__DIR__,__FILE__,$basePath,$json,$params],'import_excel/standardVacancyBindImport',1);
        $inputFileName             = $params['inputFileName']; //文件路径
        $excelBinFieldRelationShip = isset($params['excelBinFieldRelationShip']) ? $params['excelBinFieldRelationShip']:'';//对应关系数据
        $selectWorkSheetIndex      = $params['selectWorkSheetIndex']; //选择的选择的Sheet索引
        $worksheetName             = $params['worksheetName']; //选择的选择的Sheet表
        $nowVillageInfo            = $params['nowVillageInfo']; //小区信息
        $rule_id                   = $params['rule_id']; //标准id
       // $isRepeated                = $ImportExcelService->checkReplaceFieldSkipMethod($params['find_type']);//数据重复时,true 跳过[默认]，false覆盖
        $replaceField              = isset($params['find_value']) ? $params['find_value']:'';//当{$find_value}重复时覆盖现有楼栋数据
        $export_from               = isset($params['export_from']) ? $params['export_from']:'';
        //$colAndFileds = array_column($excelBinFieldRelationShip,'value','key');
        $village_id = $nowVillageInfo['village_id'];
        // 正则检查是否为纯数字
        $check_number = "/^[0-9]+$/";
        $pattern_number = "/[^0-9]/";
        $houseNewChargeRuleDb = new HouseNewChargeRule();
        $houseNewChargeStandardBindDb = new HouseNewChargeStandardBind();
        $have_error=0;
        if($rule_id<1 || $village_id<1){
            $have_error=1;
            fdump_api([__LINE__,'msg'=>'标准ID或小区ID错误','rule_id'=>$rule_id,'village_id'=>$village_id],'import_excel/standardVacancyBindImport'.$village_id,1);
        }
        $whereRule=array('id' => $rule_id,'village_id'=>$village_id);
        $ruleInfoObj = $houseNewChargeRuleDb->getOne($whereRule);
        $ruleInfo='';
        if($ruleInfoObj && !$ruleInfoObj->isEmpty()){
            $ruleInfo=$ruleInfoObj->toArray();
        }
        if(empty($ruleInfo) || $ruleInfo['status']==4){
            $have_error=1;
            fdump_api([__LINE__,'msg'=>'标准信息错误','rule_id'=>$rule_id,'village_id'=>$village_id,'ruleInfo'=>$ruleInfo],'import_excel/standardVacancyBindImport'.$village_id,1);
        }
        if(!isset($params['type']) || empty($params['type'])){
            $params['type']='standardVacancyBind';
        }
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
            fdump_api(['processCacheKey设置失败=='.__LINE__,$processCacheKey,$processInfo,$params,$e->getMessage()],'import_excel/standardVacancyBindImport'.$village_id,1);
            return true;
        }
        if($have_error==1){
            $processInfo = [
                'process'            => 100, //进度
                'readRowTotal'       => 0, //总共有数据行数
                'successInsterTotal' => 0, //总共插入表成功条数
                'errorTotal'         => 0, //总共插入表失败条数
            ];
            $this->queueReids()->set($processCacheKey,$processInfo,3600);
            return true;
        }
        $house_village_user_vacancy_db = new HouseVillageUserVacancy();
        $jz_wsxdsj_net_customized_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        $inputFileName = root_path() . '/../'.$inputFileName;
        $inputFileName=str_replace('//','/',$inputFileName);
        $reader=$ImportExcelService->getFileReaderMethod($inputFileName,false);
        $spreadsheet = $reader->load($inputFileName);
        $worksheetData = $reader->listWorksheetInfo($inputFileName);
        $sheet = $spreadsheet->getSheet($selectWorkSheetIndex);
        $highestRow = $sheet->getHighestRow(); // 总行数（不准确）)
        $highestColumn = $sheet->getHighestColumn();// 最后有数据的一列，比如 S 列
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);//把列字母转为数字,比如可以得到有 10 列
        $chunkSize = 5; //一次读取多少条
        $chunkFilter = new ChunkReadFilter();
        $reader->setReadFilter($chunkFilter);
        $allErrorData = [];
        $readRowTotal       = $highestRow - 1; //插件读取的总行数（不准确，如果Excel格式有问题的情况）
        $successInsterTotal = 0; //总共更新条数
        $errorTotal         = 0; //总失败行数（不包含空行）
        if ($readRowTotal <= 0){
            return $this->queueReids()->set($processCacheKey,100,3); //进度
        }
        $nextIndex=Coordinate::stringFromColumnIndex($highestColumnIndex+1);
        if ($highestColumnIndex > 6) {
            // 目前获取存在问题 默认下错误吗列数
            $nextIndex = 'G';
        }
        //↓↓↓表格读取数据开始↓↓↓
        $houseVillageSingleService= new HouseVillageSingleService();
        
        $sheetData = $spreadsheet->getSheetByName($worksheetName)->toArray(NULL, true, true, true);
        fdump_api(['sheetData=>'.__LINE__,$highestRow,$sheetData],'import_excel/standardVacancyBindImport'.$village_id,1);
        if(empty($sheetData)){
            return true;
        }
        $houseNewChargeRuleService=new HouseNewChargeRuleService();
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
        $rowNum=0;
        $rowTrueNum=0;
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
            ];
            $insert_rets=$this->queueReids()->set($processCacheKey,$processInfo,3600); //进度（理论上暂时还不会有超过一个小时的数据要执行）
            fdump_api(['insert_rets'.__LINE__,$insert_rets,$processCacheKey,$processInfo],'import_excel/standardVacancyBindImport'.$village_id,1);

            $single_name= isset($data['A']) ? $data['A'] : ''; //楼栋名称
            $single_name=strval($single_name);
            $single_name=trim($single_name);
            $floor_name= isset($data['B']) ? $data['B'] : ''; //单元名称
            $floor_name=strval($floor_name);
            $floor_name=trim($floor_name);
            
            $layer_name= isset($data['C']) ? $data['C'] : ''; //楼层名称
            $layer_name=strval($layer_name);
            $layer_name=trim($layer_name);
            $room_name= isset($data['D']) ? $data['D'] : ''; //房间号
            $room_name=strval($room_name);
            $room_name=trim($room_name);
            $cycle= isset($data['E']) ?$data['E'] : 1; //收费周期
            $cycle=strval($cycle);
            $cycle=trim($cycle); 
            $cycle=intval($cycle);
            $cycle=$cycle>0 ? $cycle:1;
            $order_add_time= isset($data['F']) ? $data['F'] : ''; //账单开始时间
            $order_add_time=strval($order_add_time);
            $order_add_time=trim($order_add_time);
            $order_add_time=strval($order_add_time);
            $order_add_time =$this->traitConvertDateToInt($order_add_time);
            $order_add_time= $order_add_time > 0 ? $order_add_time:0;
            if(
                (!empty($single_name) && ($single_name=='楼栋' || $single_name=='楼栋名称'))  ||
                (!empty($floor_name) && ($floor_name=='单元' || $floor_name=='单元名称'))  ||
                (!empty($layer_name) && ($layer_name=='楼层' || $layer_name=='楼层名称' || $layer_name=='层号')) ||
                (!empty($room_name) && $room_name=='房间号')
            ){
                continue;
            }
            if(empty($single_name) && empty($floor_name)&& empty($layer_name) && empty($room_name)){
                continue;
            }
            $rowTrueNum++;
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
            if (empty($room_name)) {
                $data[$nextIndex]= '请填写房间号【必填项】';
                $allErrorData[] =  $data;
                $errorTotal += 1;
                continue;
            }
            $whereSingle = array();
            $whereSingle[] = ['village_id', '=', $village_id];
            $whereSingle[] = ['status', '=', 1];
            $whereSingle[] = ['single_name', '=', $single_name];
            $single_id = 0;
            $singleInfoObj = $houseVillageSingleService->getSingleInfo($whereSingle);
            if ($singleInfoObj && !$singleInfoObj->isEmpty()) {
                $single = $singleInfoObj->toArray();
                $single_id = $single['id'];
            }
            if($single_id<1){
                $data[$nextIndex]= '此楼栋不存在！';
                $allErrorData[] =  $data;
                $errorTotal += 1;
                continue;
            }
            //单元
            $floor_id=0;
            $whereFloor = array();
            $whereFloor[] = ['village_id', '=', $village_id];
            $whereFloor[] = ['status', '=', 1];
            $whereFloor[] = ['single_id', '=', $single_id];
            $whereFloor[] = ['floor_name', '=', $floor_name];
            //todo 过滤重复写入单元数据
            $floor_info_obj = $houseVillageSingleService->getFloorInfo($whereFloor);
            if ($floor_info_obj && !$floor_info_obj->isEmpty()) {
                $floor_info = $floor_info_obj->toArray();
                $floor_id = $floor_info ? $floor_info['floor_id'] : 0;
            }
            if($floor_id<1){
                $data[$nextIndex]= '楼栋下单元不存在！';
                $allErrorData[] =  $data;
                $errorTotal += 1;
                continue;
            }
            //楼层
            $layer_id=0;
            $whereLayer = array();
            $whereLayer[] = ['village_id', '=', $village_id];
            $whereLayer[] = ['status', '=', 1];
            $whereLayer[] = ['single_id', '=', $single_id];
            $whereLayer[] = ['floor_id', '=', $floor_id];
            $whereLayer[] = ['layer_name', '=', $layer_name];
            $layer_info_obj =$houseVillageSingleService->getLayerInfo($whereLayer);
            if($layer_info_obj && !$layer_info_obj->isEmpty()){
                $layer_info=$layer_info_obj->toArray();
                $layer_id = $layer_info ? $layer_info['id'] : 0;
            }
            if($layer_id<1){
                $data[$nextIndex]= '楼栋单元下楼层不存在！';
                $allErrorData[] =  $data;
                $errorTotal += 1;
                continue;
            }
            $vacancy_file = 'pigcms_id,village_id,single_id,floor_id,layer_id,housesize';
            if($jz_wsxdsj_net_customized_open){
                $vacancy_file .= ',heating_size,billable_size';
            }
            $whereRoom=array(['is_del','=',0]);
            $whereRoom[]=array('village_id','=',$village_id);
            $whereRoom[]=array('single_id','=',$single_id);
            $whereRoom[]=array('floor_id','=',$floor_id);
            $whereRoom[]=array('layer_id','=',$layer_id);
            $whereRoom[]=array('room','=',$room_name);
            $user_vacancy_obj = $house_village_user_vacancy_db->getOne($whereRoom,$vacancy_file);
            $user_vacancy='';
            if($user_vacancy_obj && !$user_vacancy_obj->isEmpty()){
                $user_vacancy=$user_vacancy_obj->toArray();
            }
            if(empty($user_vacancy)){
                $data[$nextIndex]= '此房间号房间不存在！';
                $allErrorData[] =  $data;
                $errorTotal += 1;
                continue;
            }
            $custom_value='';
            if($ruleInfo['fees_type'] == 2 && empty($ruleInfo['unit_gage']) && !in_array($ruleInfo['unit_gage_type'],[4,5])){
                $custom_value = $user_vacancy['housesize']? $user_vacancy['housesize']:1;
            }elseif($ruleInfo['unit_gage_type'] == 4){
                $custom_value = isset($user_vacancy['heating_size']) ? $user_vacancy['heating_size']:0;
                if($custom_value <= 0){
                    $data[$nextIndex]= '房间供暖面积为0，无法绑定！';
                    $allErrorData[] =  $data;
                    $errorTotal += 1;
                    continue;
                }
            }elseif($ruleInfo['unit_gage_type'] == 5){
                $custom_value = isset($user_vacancy['billable_size']) ? $user_vacancy['billable_size']:0;
                if($custom_value <= 0){
                    $data[$nextIndex]= '房间计费面积为0，无法绑定!';
                    $allErrorData[] =  $data;
                    $errorTotal += 1;
                    continue;
                }
            }
            if($ruleInfo['fees_type'] == newChargeConst::FEES_TYPE_NUMBER_OF_PARKING_SPACES) {
                $ruleHasParkNumInfo = $houseNewChargeRuleService->getRuleHasParkNumInfo($user_vacancy['pigcms_id'], $rule_id, 1, $ruleInfo);
                if (empty($ruleHasParkNumInfo) || !isset($ruleHasParkNumInfo['parkingNum']) || intval($ruleHasParkNumInfo['parkingNum']) <= 0) {
                    $data[$nextIndex]= '计费模式为车位数量缺少车位数量，无法绑定!';
                    $allErrorData[] =  $data;
                    $errorTotal += 1;
                    continue;
                }
            }
            $whereArr=array('rule_id' => $rule_id, 'vacancy_id' => $user_vacancy['pigcms_id'], 'is_del' => 1);
            $bindvacancyObj=$houseNewChargeStandardBindDb->getOne($whereArr,'id,village_id,rule_id,vacancy_id');
            if($bindvacancyObj && !$bindvacancyObj->isEmpty()){
                $data[$nextIndex]= '房间已绑定过该消费标准了!';
                $allErrorData[] =  $data;
                $errorTotal += 1;
                continue;
            }
            $bind_data = array(
                'rule_id' => $rule_id,
                'project_id' => $ruleInfo['charge_project_id'],
                'village_id' => $village_id,
                'single_id' => $single_id,
                'floor_id' => $floor_id,
                'layer_id' => $layer_id,
                'vacancy_id' => $user_vacancy['pigcms_id'],
                'bind_type' => 1,
                'order_add_time' => $order_add_time,
                'order_add_type' => $ruleInfo['bill_create_set'],
                'custom_value' => $custom_value,
                'charge_valid_time' => $ruleInfo['charge_valid_time'],
                'add_time' => time(),
                'update_time' => time(),
                'is_del' => 1,
                'cycle'=>$cycle,
            );
            $insert_id = $houseNewChargeStandardBindDb->addOne($bind_data);
            $successInsterTotal += 1;
            
        }

        //本次读取处理完毕
        $processInfo = [
            'process'            => 100, //进度
            'readRowTotal'       => $rowTrueNum, //总共有数据行数
            'successInsterTotal' => $successInsterTotal, //总共插入表成功条数
            'errorTotal'         => $errorTotal, //总共插入表失败条数
        ];
        $insert_rets=$this->queueReids()->set($processCacheKey,$processInfo,3600); //进度（理论上暂时还不会有超过一个小时的数据要执行）
        fdump_api(['insert_rets'.__LINE__,$insert_rets,$processCacheKey,$processInfo],'import_excel/standardVacancyBindImport'.$village_id,1);
        $spreadsheet->disconnectWorksheets();
        fdump_api('===================','import_excel/standardVacancyBindImport'.$village_id,1);
        //↑↑↑表格读取处理完成↑↑↑
        $excelTitle[$nextIndex]='失败原因';
        $record=[
            'village_id'=>$village_id,
            'type'=>$params['type'],
            'file_name'=>basename($inputFileName),
            'find_type'=>$params['find_type'],
            'find_value'=>$replaceField,
            'yw_common_id'=>$rule_id,
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
        fdump_api(['处理完成=='.__LINE__,'总花费时间:'.$second.'s',$record,$res,$allErrorData],'import_excel/standardVacancyBindImport'.$village_id,1);
        return  true;
    }
}