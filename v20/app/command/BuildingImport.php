<?php

declare (strict_types = 1);

/**
 * Author lkz
 * Date: 2023/1/11
 * Time: 9:22
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

class BuildingImport extends Command
{

    use CacheTypeTraits,CommonTraits;

    protected function configure()
    {
        // 指令配置
        $this->setName('buildingImport')
            ->addArgument('json',Argument::IS_ARRAY)
            ->setDescription('楼栋命令行导入指令');
    }

    //临时打印日志
    private function buildingImportFdump($data, $filename='test', $append=false){
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
        $this->buildingImportFdump(['接收参数=='.__LINE__,$json,$params],'import_excel/buildingImportExecute',1);
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
            $this->buildingImportFdump(['$processCacheKey设置失败=='.__LINE__,$processCacheKey,$processInfo,$params,$e->getMessage()],'import_excel/buildingImportExecuteError',1);
        }
        $inputFileName = root_path() . '/../'.$inputFileName;
        
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
        $nextIndex=Coordinate::stringFromColumnIndex($highestColumnIndex+1);
        //↓↓↓表格读取数据开始↓↓↓
        for ($startRow = 2; $startRow <= $highestRow; $startRow += $chunkSize) {
            $chunkFilter->setRows($startRow, $chunkSize);
            $spreadsheet = $reader->load($inputFileName);
            $sheetData = $spreadsheet->getSheetByName($worksheetName)->toArray(NULL, true, true, true);
            if(!$sheetData){
                $this->buildingImportFdump(['$sheetData为空=='.__LINE__,$startRow,$highestRow,$sheetData],'import_excel/buildingImportExecuteError',1);
                continue;
            }
            $excelTitle=$sheetData[1];
            unset($sheetData[1]); //去掉表头
            //开始业务逻辑
            foreach ($sheetData as  $dk => $data){
                if (   (isset($data[$colAndFileds['single_name']])   && empty($data[$colAndFileds['single_name']]) )
                    && (isset($data[$colAndFileds['single_number']]) && empty($data[$colAndFileds['single_number']]))
                    && (isset($data[$colAndFileds['measure_area']])  && empty($data[$colAndFileds['measure_area']]))
                    && (isset($data[$colAndFileds['floor_num']])     && empty($data[$colAndFileds['floor_num']]))
                    && (isset($data[$colAndFileds['vacancy_num']])   && empty($data[$colAndFileds['vacancy_num']]))
                    && (isset($data[$colAndFileds['contract_time_start']])         && empty($data[$colAndFileds['contract_time_start']]))
                    && (isset($data[$colAndFileds['contract_time_end']])           && empty($data[$colAndFileds['contract_time_end']]))
                ){
                    continue;
                }
                $single_number= isset($data[$colAndFileds['single_number']]) ? $data[$colAndFileds['single_number']] : null;//楼栋编号
                $single_name  = isset($data[$colAndFileds['single_name']])   ? $data[$colAndFileds['single_name']]   : null;//楼栋名称
                $floor_num    = isset($data[$colAndFileds['floor_num']])     ? $data[$colAndFileds['floor_num']]     : null;//单元数量
                $vacancy_num  = isset($data[$colAndFileds['vacancy_num']])   ? $data[$colAndFileds['vacancy_num']]   : null;//房间数量
                $measure_area = isset($data[$colAndFileds['measure_area']])  ? $data[$colAndFileds['measure_area']]  : null;//房间数量
                $sort         = isset($data[$colAndFileds['sort']])          ? $data[$colAndFileds['sort']]          : null;//排序
                $contract_time_start = isset($data[$colAndFileds['contract_time_start']])  ? $data[$colAndFileds['contract_time_start']]  : null;//合同开始时间
                $contract_time_end   = isset($data[$colAndFileds['contract_time_end']])    ? $data[$colAndFileds['contract_time_end']]    : null;//合同结束时间

                if (empty($single_number) && empty($single_name) && empty($floor_num) && empty($vacancy_num) && empty($measure_area) && empty($sort) && empty($contract_time_start) && empty($contract_time_end)){
                    $this->buildingImportFdump(['数据为空=='.__LINE__,$data],'import_excel/buildingImportExecuteError',1);
                    continue;
                }

                //楼栋编号
                if (!empty($single_number)){
                    if (!is_numeric($single_number)){
                        if(!preg_match($check_number,$single_number)){
//                            $data['RowIndex']=$dk;
                            $data[$nextIndex]= "该楼栋编号【{$single_number}】只允许数字";
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
                }
                else{
                    $data[$nextIndex]= '请填写楼栋编号【必填项】';
                    $allErrorData[] =  $data;
                    $errorTotal += 1;
                    continue;
                }

                //楼栋名称
                if (empty($single_name)){
                    $single_name = $single_number;
                }

                if (!is_null($single_name) && !is_numeric($single_name)){
                    $single_name = htmlspecialchars($single_name, ENT_QUOTES,'UTF-8');
                }
                
                //覆盖指定字段
                if(!$isRepeated && empty($replaceField)){
                    $data[$nextIndex]= "数据重复=>选择[覆盖],请选择覆盖字段！";
                    $allErrorData[] =  $data;
                    $errorTotal += 1;
                    continue;
                }

                // 查询是否有同一楼栋名称
                $where[] = ['single_name','='  ,$single_name];
                $where[] = ['village_id' ,'='  ,$village_id];
                $where[] = ['status'     ,'<>' ,4];
                $isRepeateSignleName = (new HouseVillageSingleService())->getSingleInfo($where,'id,single_name');
                if ($isRepeateSignleName && $isRepeated){ //跳过楼栋名称
                    $data[$nextIndex]= "该楼栋名称【{$single_name}】已存在！";
                    $allErrorData[] =  $data;
                    $errorTotal += 1;
                    continue;
                }

                //查询是否有同一个楼栋编号
                $where   = [];
                $where[] = ['village_id'    ,'='  ,$village_id];
                $where[] = ['single_number' ,'='  ,$single_number];
                $where[] = ['status'        ,'<>' ,4];
                $isRepeateSignleNumber = (new HouseVillageSingleService())->getSingleInfo($where,'id,single_name');
                if ($isRepeateSignleNumber && $isRepeated){
                    $data[$nextIndex]= "该楼栋编号【{$single_number}】已存在！";
                    $allErrorData[] =  $data;
                    $errorTotal += 1;
                    continue;
                }
                
                //单元数量
                if (empty($floor_num)){
                    $floor_num = 0;
                }
                //房间数量
                if (empty($vacancy_num)){
                    $vacancy_num = 0;
                }
                //房间数量
                if (empty($measure_area)){
                    $measure_area = 0;
                }
                //排序
                if (empty($sort)){
                    $sort = 0;
                }
                //合同开始时间
                if (empty($contract_time_start)){
                    $contract_time_start = 0;
                }else{
                    $contract_time_start = $this->traitConvertDateToInt($contract_time_start);
                }
                //合同结束时间
                if (empty($contract_time_end)){
                    $contract_time_end = 0;
                }else{
                    $contract_time_end = $this->traitConvertDateToInt($contract_time_end);
                }
                //组装入库数据
                $successInsertTotal += 1;
                if($isRepeateSignleNumber && !$isRepeated){  //更新
                    $update_id=$isRepeateSignleNumber['id'];
                    $replaceValue=(isset($data[$colAndFileds[$replaceField]]) && $data[$colAndFileds[$replaceField]]) ? $data[$colAndFileds[$replaceField]] : '';
                    $updateData=[
                        $replaceField=> $replaceValue
                    ];
                    $update_status=$NewBuildingService->saveAllHouseVillageSingleMethod($update_id,$updateData);
                    $this->buildingImportFdump(['覆盖数据=='.__LINE__,$replaceField,$update_id,$updateData,$update_status],'import_excel/buildingImportExecuteUpdate',1);
                }
                else{ //新增
                    $allInsertData[]  = [
                        'single_name'    => $single_name,
                        'single_number'  => $single_number,
                        'village_id'     => $village_id,
                        'status'         => 1,
                        'vacancy_num'    => $vacancy_num,
                        'floor_num'      => $floor_num,
                        'measure_area'   => $measure_area,
                        'sort'           => $sort,
                        'contract_time_start'   => $contract_time_start,
                        'contract_time_end'     => $contract_time_end
                    ];
                }
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
        if($allInsertData){ //写入楼栋数据
            $NewBuildingService->addAllHouseVillageSingleMethod($allInsertData);
        }
        $this->buildingImportFdump(['处理完成=='.__LINE__,'总花费时间:'.$second.'s',$record,$res,$allErrorData,$allInsertData],'import_excel/buildingImportExecute',1);
        return  true;
    }
    
}