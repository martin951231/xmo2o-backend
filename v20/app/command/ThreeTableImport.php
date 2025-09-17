<?php

declare (strict_types = 1);

/**
 * Author lkz
 * Date: 2023/1/13
 * Time: 13:09
 */


namespace app\command;

use app\common\ChunkReadFilter;
use app\community\model\service\HouseVillageConfigService;
use app\community\model\service\HouseVillageSingleService;
use app\community\model\service\ImportExcelService;
use app\community\model\service\NewBuildingService;
use app\traits\CacheTypeTraits;
use app\traits\CommonTraits;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

class ThreeTableImport extends Command
{
    use CacheTypeTraits,CommonTraits;

    protected function configure()
    {
        // 指令配置
        $this->setName('threeTableImport')
            ->addArgument('json',Argument::IS_ARRAY)
            ->setDescription('定制三表(水电燃)命令行导入指令');
    }

    //临时打印日志
    private function threeTableImportFdump($data, $filename='test', $append=false){
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
        $this->threeTableImportFdump(['接收参数=='.__LINE__,$json,$params],'import_excel/threeTableImportExecute',1);
        $inputFileName             = $params['inputFileName']; //文件路径
        $excelBinFieldRelationShip = $params['excelBinFieldRelationShip'];//对应关系数据
        $selectWorkSheetIndex      = $params['selectWorkSheetIndex']; //选择的选择的Sheet索引
        $worksheetName             = $params['worksheetName']; //选择的选择的Sheet表
        $nowVillageInfo            = $params['nowVillageInfo']; //小区信息
        $isRepeated                = $ImportExcelService->checkReplaceFieldSkipMethod($params['find_type']);//数据重复时,true 跳过[默认]，false覆盖
        $replaceField              = $params['find_value'];//当{$find_value}重复时覆盖现有楼栋数据

        $colAndFileds = array_column($excelBinFieldRelationShip,'value','key');

        // 正则检查是否为纯数字
        $check_number = "/^[0-9]+$/";
        $village_id = $nowVillageInfo['village_id'];
        $villageConfig = (new HouseVillageConfigService())->getConfig(['village_id'=>$village_id],'village_single_support_digit');

        $village_single_support_digit = intval($villageConfig['village_single_support_digit']) ? intval($villageConfig['village_single_support_digit']) : 2;
        if (!$village_single_support_digit || $village_single_support_digit!=3) {
            $village_single_support_digit = 2;
        }
        $max_num = 99;
        if ($village_single_support_digit==3) {
            $max_num = 999;
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
            $this->threeTableImportFdump(['$processCacheKey设置失败=='.__LINE__,$processCacheKey,$processInfo,$params,$e->getMessage()],'import_excel/threeTableImportExecuteError',1);
        }
        $inputFileName = root_path() . '/../'.$inputFileName;

        $reader=$ImportExcelService->getFileReaderMethod($inputFileName,false);
        $spreadsheet = $reader->load($inputFileName);
        $sheet = $spreadsheet->getSheet($selectWorkSheetIndex);
        $highestRow = $sheet->getHighestRow(); // 总行数（不准确）)
        $highestColumn = $sheet->getHighestColumn();// 最后有数据的一列，比如 S 列
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);//把列字母转为数字,比如可以得到有 10 列
        $chunkSize = 50; //一次读取多少条
        $chunkFilter = new ChunkReadFilter();
        $reader->setReadFilter($chunkFilter);
        $j = 1;

        $allErrorData = [];

        $readRowTotal       = $highestRow - 1; //插件读取的总行数（不准确，如果Excel格式有问题的情况）
        $successInsertTotal = 0; //总共更新条数
        $errorTotal         = 0; //总失败行数（不包含空行）

        if ($readRowTotal <= 0){
            return $this->queueReids()->set($processCacheKey,100,3); //进度
        }
        $excelTitle=[];
        $nextIndex=Coordinate::stringFromColumnIndex($highestColumnIndex+1);
        //↓↓↓表格读取数据开始↓↓↓
        for ($startRow = 2; $startRow <= $highestRow; $startRow += $chunkSize) {
            $chunkFilter->setRows($startRow, $chunkSize);
            $spreadsheet = $reader->load($inputFileName);
            $sheetData = $spreadsheet->getSheetByName($worksheetName)->toArray(NULL, true, true, true);
            if(!$sheetData){
                $this->threeTableImportFdump(['$sheetData为空=='.__LINE__,$startRow,$highestRow,$sheetData],'import_excel/threeTableImportExecuteError',1);
                continue;
            }
            $excelTitle=$sheetData[1];
            unset($sheetData[1]); //去掉表头
            //开始业务逻辑
            foreach ($sheetData as  $dk => $data){
                if ((isset($data[$colAndFileds['single_name']]) && empty($data[$colAndFileds['single_name']]))
                    && (isset($data[$colAndFileds['floor_name']]) && empty($data[$colAndFileds['floor_name']]))
                    && (isset($data[$colAndFileds['layer_name']]) && empty($data[$colAndFileds['layer_name']]))
                    && (isset($data[$colAndFileds['room']]) && empty($data[$colAndFileds['room']]))
                    && (isset($data[$colAndFileds['water_number']]) && empty($data[$colAndFileds['water_number']]))
                    && (isset($data[$colAndFileds['heat_water_number']]) && empty($data[$colAndFileds['heat_water_number']]))
                    && (isset($data[$colAndFileds['ele_number']]) && empty($data[$colAndFileds['ele_number']]))
                    && (isset($data[$colAndFileds['gas_number']]) && empty($data[$colAndFileds['gas_number']]))
                ) {
                    continue;
                }
                //楼层名称
                $single_name= isset($data[$colAndFileds['single_name']]) ? $data[$colAndFileds['single_name']] : '';
                $single_name=$single_name  ? strval($single_name):'';
                $single_name=trim($single_name);
                //单元名称
                $floor_name = isset($data[$colAndFileds['floor_name']]) ? $data[$colAndFileds['floor_name']] : '';
                $floor_name=$floor_name  ? strval($floor_name):'';
                $floor_name=trim($floor_name);
                //楼层名称
                $layer_name = isset($data[$colAndFileds['layer_name']])  ? $data[$colAndFileds['layer_name']]  : '';
                $layer_name=$layer_name  ? strval($layer_name):'';
                $layer_name=trim($layer_name);
                //房间
                $room  = isset($data[$colAndFileds['room']]) ? $data[$colAndFileds['room']] : '';
                $room=$room  ? strval($room):'';
                $room=trim($room);
                //冷水表表号
                $water_number= isset($data[$colAndFileds['water_number']])  ? $data[$colAndFileds['water_number']]  : '';
                $water_number=$water_number  ? strval($water_number):'';
                $water_number=trim($water_number);
                //热水表表号
                $heat_water_number= isset($data[$colAndFileds['heat_water_number']])  ? $data[$colAndFileds['heat_water_number']] : '';
                $heat_water_number=$heat_water_number  ? strval($heat_water_number):'';
                $heat_water_number=trim($heat_water_number);
                //电表表号
                $ele_number = isset($data[$colAndFileds['ele_number']])  ? $data[$colAndFileds['ele_number']]  : '';
                $ele_number=$ele_number  ? strval($ele_number):'';
                $ele_number=trim($ele_number);
                //燃气表表号
                $gas_number= isset($data[$colAndFileds['gas_number']])  ? $data[$colAndFileds['gas_number']]  : '';
                $gas_number=$gas_number  ? strval($gas_number):'';
                $gas_number=trim($gas_number);
                if (empty($single_name) && empty($floor_name) && empty($layer_name) && empty($room) && empty($water_number) && empty($heat_water_number) && empty($ele_number) && empty($gas_number)){
                    $this->threeTableImportFdump(['数据为空=='.__LINE__,$data],'import_excel/threeTableImportExecuteError',1);
                    continue;
                }
                //楼栋名称
                if (empty($single_name)){
                    $data[$nextIndex]= '请填写楼栋名称【必填项】';
                    $allErrorData[] =  $data;
                    $errorTotal += 1;
                    continue;
                }
                //单元名称
                if (empty($floor_name)){
                    $data[$nextIndex]= '请填写单元名称【必填项】';
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
                //房间号
                if (empty($room)){
                    $data[$nextIndex]= '请填写房间号【必填项】';
                    $allErrorData[] =  $data;
                    $errorTotal += 1;
                    continue;
                }
                if(!$water_number && !$heat_water_number && !$ele_number && !$gas_number){
                    $data[$nextIndex]= '电表编号/冷水表编号/热水表编号/燃气表编号,请完善其中一项';
                    $allErrorData[] =  $data;
                    $errorTotal += 1;
                    continue;
                }
                //查询楼栋是否存在
                $single=$NewBuildingService->getHouseVillageSingleFindMethod([
                    ['single_name','=',$single_name],
                    ['village_id','=',$village_id],
                    ['status','=',1],
                ],'id');
                if (!$single){
                    $data[$nextIndex]= "该楼栋【{$single_name}】不存在！";
                    $allErrorData[] =  $data;
                    $errorTotal += 1;
                    continue;
                }
                //查询单元是否存在
                $floor=$NewBuildingService->getHouseVillageFloorFindMethod([
                    ['floor_name','=',$floor_name],
                    ['single_id','=',$single['id']],
                    ['village_id','=',$village_id],
                    ['status','=',1],
                ],'floor_id');
                if (!$floor){
                    $data[$nextIndex]= "该单元【{$floor_name}】不存在！";
                    $allErrorData[] =  $data;
                    $errorTotal += 1;
                    continue;
                }
                //查询楼层是否存在
                $layer=$NewBuildingService->getHouseVillageLayerFindMethod([
                    ['village_id','=',$village_id],
                    ['single_id','=',$single['id']],
                    ['floor_id','=',$floor['floor_id']],
                    ['layer_name','=',$layer_name],
                    ['status','<>' ,4]
                ],'id');
                if(!$layer){
                    $data[$nextIndex]= "该楼层【{$layer_name}】不存在！";
                    $allErrorData[] =  $data;
                    $errorTotal += 1;
                    continue;
                }

                //查询房间是否存在
                $vacancy=$NewBuildingService->getHouseVillageUserVacancyFindMethod([
                    ['village_id','=',$village_id],
                    ['single_id','=',$single['id']],
                    ['floor_id','=',$floor['floor_id']],
                    ['layer_id','=',$layer['id']],
                    ['room','=',$room],
                    ['is_del','=' ,0]
                ],'pigcms_id');
                if(!$vacancy){
                    $data[$nextIndex]= "该房间号【{$room}】不存在！";
                    $allErrorData[] =  $data;
                    $errorTotal += 1;
                    continue;
                }
                $vacancy_num_data=[
                    'water_number'=>$water_number,
                    'heat_water_number'=>$heat_water_number,
                    'ele_number'=>$ele_number,
                    'gas_number'=>$gas_number
                ];
                $res=$NewBuildingService->checkWaterElectricGasParamMethod($village_id,$vacancy['pigcms_id'],$vacancy_num_data);
                if(!$res['error']){
                    $data[$nextIndex]= $res['msg'];
                    $allErrorData[] =  $data;
                    $errorTotal += 1;
                    continue;
                }
                //成功条数
                $successInsertTotal += 1;
            }
            //业务逻辑处理完毕
            $process =  sprintf('%.2f',$startRow / $highestRow);
            if ($startRow + $chunkSize >=$highestRow ){
                $process = 1;
            }
            $processInfo = [
                'process'            => floor($process * 100), //进度
                'readRowTotal'       => $readRowTotal, //总共有数据行数
                'successInsterTotal' => $successInsertTotal, //总共插入表成功条数
                'errorTotal'         => $errorTotal, //总共插入表失败条数
            ];
            $this->queueReids()->set($processCacheKey,$processInfo,3600); //进度（理论上暂时还不会有超过一个小时的数据要执行）
            $j++;
            $spreadsheet->disconnectWorksheets();
//            sleep(1);
        }
        //↑↑↑表格读取处理完成↑↑↑
        $excelTitle[$nextIndex]='失败原因';
        $record=[
            'village_id'=>$village_id,
            'type'=>$params['type'],
            'file_name'=>basename($inputFileName),
            'find_type'=>$params['find_type'],
            'find_value'=>$replaceField,
            'import_msg'=>'总行数:'.$readRowTotal.'条,成功:'.$successInsertTotal.'条,失败:'.$errorTotal.'条',
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
        $this->threeTableImportFdump(['处理完成=='.__LINE__,'总花费时间:'.$second.'s',$record,$res,$allErrorData],'import_excel/threeTableImportExecute',1);
        return  true;
    }

}