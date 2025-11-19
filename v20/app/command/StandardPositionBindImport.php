<?php

declare (strict_types = 1);
/**
 * Author lihongshu
 * Date: 2023/11/118
 * Time: 15:15
 */

namespace app\command;
use app\common\ChunkReadFilter;
use app\community\model\db\HouseNewChargeRule;
use app\community\model\db\HouseNewChargeStandardBind;
use app\community\model\db\HouseVillageParkingGarage;
use app\community\model\db\HouseVillageParkingPosition;
use app\community\model\service\ImportExcelService;
use app\community\model\service\HouseNewChargeRuleService;
use app\community\model\service\NewBuildingService;
use app\traits\CacheTypeTraits;
use app\traits\CommonTraits;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

class StandardPositionBindImport extends Command
{

    use CacheTypeTraits,CommonTraits;

    protected function configure()
    {
        // 指令配置
        $this->setName('standardPositionBindImport')
            ->addArgument('json',Argument::IS_ARRAY)
            ->setDescription('车位绑定标准命令行导入指令');
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
        fdump_api(['接收参数=='.__LINE__,__DIR__,__FILE__,$basePath,$json,$params],'import_excel/standardPositionBindImport',1);
        $inputFileName             = $params['inputFileName']; //文件路径
        $excelBinFieldRelationShip = isset($params['excelBinFieldRelationShip']) ? $params['excelBinFieldRelationShip']:'';//对应关系数据
        $selectWorkSheetIndex      = $params['selectWorkSheetIndex']; //选择的选择的Sheet索引
        $worksheetName             = $params['worksheetName']; //选择的选择的Sheet表
        $nowVillageInfo            = $params['nowVillageInfo']; //小区信息
        $rule_id                   = $params['rule_id']; //标准id
        //$isRepeated                = $ImportExcelService->checkReplaceFieldSkipMethod($params['find_type']);//数据重复时,true 跳过[默认]，false覆盖
        $replaceField              = isset($params['find_value']) ? $params['find_value']:''; //当{$find_value}重复时覆盖现有楼栋数据
        $export_from               = isset($params['export_from']) ? $params['export_from']:'';

        $village_id = $nowVillageInfo['village_id'];
        // 正则检查是否为纯数字
        $check_number = "/^[0-9]+$/";
        $pattern_number = "/[^0-9]/";
        $houseNewChargeRuleDb = new HouseNewChargeRule();
        $houseNewChargeStandardBindDb = new HouseNewChargeStandardBind();
        $have_error=0;
        if($rule_id<1 || $village_id<1){
            $have_error=1;
            fdump_api([__LINE__,'msg'=>'标准ID或小区ID错误','rule_id'=>$rule_id,'village_id'=>$village_id],'import_excel/standardPositionBindImport'.$village_id,1);
        }
        $whereRule=array('id' => $rule_id,'village_id'=>$village_id);
        $ruleInfoObj = $houseNewChargeRuleDb->getOne($whereRule);
        $ruleInfo='';
        if($ruleInfoObj && !$ruleInfoObj->isEmpty()){
            $ruleInfo=$ruleInfoObj->toArray();
        }
        if(empty($ruleInfo) || $ruleInfo['status']==4){
            $have_error=1;
            fdump_api([__LINE__,'msg'=>'标准信息错误','rule_id'=>$rule_id,'village_id'=>$village_id,'ruleInfo'=>$ruleInfo],'import_excel/standardPositionBindImport'.$village_id,1);
        }
        if(!isset($params['type']) || empty($params['type'])){
            $params['type']='standardPositionBind';
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
            fdump_api(['processCacheKey设置失败=='.__LINE__,$processCacheKey,$processInfo,$params,$e->getMessage()],'import_excel/standardPositionBindImport'.$village_id,1);
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
        $houseVillageParkingGarageDb = new HouseVillageParkingGarage();
        $houseVillageParkingPositionDb = new  HouseVillageParkingPosition();

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
        if ($highestColumnIndex > 4) {
            // 目前获取存在问题 默认下错误吗列数
            $nextIndex = 'E';
        }
        //↓↓↓表格读取数据开始↓↓↓
        $sheetData = $spreadsheet->getSheetByName($worksheetName)->toArray(NULL, true, true, true);
        fdump_api(['sheetData=>'.__LINE__,$highestRow,$sheetData],'import_excel/standardPositionBindImport'.$village_id,1);
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
            fdump_api(['insert_rets'.__LINE__,$insert_rets,$processCacheKey,$processInfo],'import_excel/standardPositionBindImport'.$village_id,1);

            $garage_num= isset($data['A']) ? $data['A'] : ''; //车库名称
            $garage_num=strval($garage_num);
            $garage_num=trim($garage_num);
            $position_num= isset($data['B']) ? $data['B'] : ''; //车位号
            $position_num=strval($position_num);
            $position_num=trim($position_num);

            $cycle= isset($data['C']) ?$data['C'] : 1; //收费周期
            $cycle=strval($cycle);
            $cycle=trim($cycle); 
            $cycle=intval($cycle);
            $cycle=$cycle>0 ? $cycle:1;
            $order_add_time= isset($data['D']) ? $data['D'] : ''; //账单开始时间
            $order_add_time=strval($order_add_time);
            $order_add_time=trim($order_add_time);
            $order_add_time=strval($order_add_time);
            $order_add_time =$this->traitConvertDateToInt($order_add_time);
            $order_add_time= $order_add_time > 0 ? $order_add_time:0;
            if(
                (!empty($garage_num) && ($garage_num=='车库名称'))&&
                (!empty($position_num) && ($position_num=='车位号'))
            ){
                continue;
            }
            if(empty($garage_num) && empty($position_num)){
                continue;
            }
            $rowTrueNum++;
            //楼栋名称
            if (empty($garage_num)){
                $data[$nextIndex]= '请填写车库名称【必填项】';
                $allErrorData[] =  $data;
                $errorTotal += 1;
                continue;
            }
            if (!is_numeric($garage_num)){
                $garage_num = htmlspecialchars($garage_num, ENT_QUOTES,'UTF-8');
            }
            $whereGarage=array(['garage_num','=',$garage_num],['village_id','=',$village_id]);
            $parkingGarage=$houseVillageParkingGarageDb->getOne($whereGarage,'garage_id,garage_num,village_id');
            $garage_id=0;
            if($parkingGarage && !$parkingGarage->isEmpty()){
                $garage_id=$parkingGarage['garage_id'];
            }else{
                $data[$nextIndex]= '车库数据不存在！';
                $allErrorData[] =  $data;
                $errorTotal += 1;
                continue;
            }
            //楼栋名称
            if (empty($position_num)){
                $data[$nextIndex]= '请填车位号【必填项】';
                $allErrorData[] =  $data;
                $errorTotal += 1;
                continue;
            }
            if (!is_numeric($position_num)){
                $position_num = htmlspecialchars($position_num, ENT_QUOTES,'UTF-8');
            }
            $wherePosition=array(['garage_id','=',$garage_id],['village_id','=',$village_id],['position_num','=',$position_num]);
            $parkingPositionObj=$houseVillageParkingPositionDb->getFindOne($wherePosition,'position_id,garage_id,position_num,position_area,village_id');
            $parkingPosition='';
            if($parkingPositionObj && !$parkingPositionObj->isEmpty()){
                $parkingPosition=$parkingPositionObj->toArray();
            }
            if(empty($parkingPosition) || !isset($parkingPosition['position_id'])){
                $data[$nextIndex]= '车位号不存在！';
                $allErrorData[] =  $data;
                $errorTotal += 1;
                continue;
            }
            $custom_value='';
            if($ruleInfo['fees_type'] == 2 && empty($ruleInfo['unit_gage']) && $ruleInfo['unit_gage_type']==3){
                $custom_value = $parkingPosition['position_area'] ? $parkingPosition['position_area']:'';
            }
            $whereArr=array('rule_id' => $rule_id, 'position_id' => $parkingPosition['position_id'], 'is_del' => 1);
            $bindvacancyObj=$houseNewChargeStandardBindDb->getOne($whereArr,'id,village_id,rule_id,vacancy_id,position_id');
            if($bindvacancyObj && !$bindvacancyObj->isEmpty()){
                $data[$nextIndex]= '此车位已绑定过该消费标准了!';
                $allErrorData[] =  $data;
                $errorTotal += 1;
                continue;
            }
            $bind_data = array(
                'rule_id' => $rule_id,
                'project_id' => $ruleInfo['charge_project_id'],
                'village_id' => $village_id,
                'single_id' => 0,
                'floor_id' => 0,
                'layer_id' => 0,
                'vacancy_id' => 0,
                'garage_id' => $garage_id,
                'position_id' => $parkingPosition['position_id'],
                'bind_type' => 2,
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
        fdump_api(['insert_rets'.__LINE__,$insert_rets,$processCacheKey,$processInfo],'import_excel/standardPositionBindImport'.$village_id,1);
        $spreadsheet->disconnectWorksheets();
        fdump_api('===================','import_excel/standardPositionBindImport'.$village_id,1);
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
        fdump_api(['处理完成=='.__LINE__,'总花费时间:'.$second.'s',$record,$res,$allErrorData],'import_excel/standardPositionBindImport'.$village_id,1);
        return  true;
    }
}