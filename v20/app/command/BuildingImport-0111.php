<?php
	declare (strict_types = 1);

	namespace app\command;

	use app\common\ChunkReadFilter;
	use app\community\model\service\HouseVillageConfigService;
	use app\community\model\service\HouseVillageSingleService;
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
		
		public function dump($data)
		{
			echo "<pre>";
			var_dump($data);
			echo "</pre>";
		}

		protected function execute(Input $input, Output $output)
		{
			
			//1、接收参数 、绑定参数
			//2、按照映射关系写入库
			//3、然后使用 Redis 反馈实时导入进度
			//4、记录一下导入数据变更记录和 整理导入错误的文件
			
			$json   = $input->getArgument('json');
			$params = \json_decode($json[0],true);
//			file_put_contents('duilie.log',__FUNCTION__.' 命令行接收到的参数: - $jso----> '.print_r($json,true).'--'.print_r($params,true). PHP_EOL, 8);
// 
			$inputFileName             = $params['inputFileName'];
			$excelBinFieldRelationShip = $params['excelBinFieldRelationShip'];
			$selectWorkSheetIndex      = $params['selectWorkSheetIndex'];
			$worksheetName             = $params['worksheetName'];
			$nowVillageInfo            = $params['nowVillageInfo'];
			$isRepeated                = $params['isRepeated'];//数据重复时 0 跳过[默认]，1 覆盖
			$replaceField              = $params['replaceField'];//当{$replaceField}重复时覆盖现有楼栋数据
			
			$colAndFileds = array_column($excelBinFieldRelationShip,'value','key');
//			var_dump(['$colAndFileds'=>$colAndFileds]);
			
			// 正则检查是否为纯数字
			$check_number = "/^[0-9]+$/";
			$village_id = $nowVillageInfo['village_id'];
			$villageConfig = (new HouseVillageConfigService())->getConfig(['village_id'=>$village_id]);
		 
			$village_single_support_digit = intval($villageConfig['village_single_support_digit']) ? intval($villageConfig['village_single_support_digit']) : 2;
			if (!$village_single_support_digit || $village_single_support_digit!=3) {
				$village_single_support_digit = 2;
			}
			$max_num = 99;
			if ($village_single_support_digit==3) {
				$max_num = 999;
			}

			
			//使用Redis缓存 进度 
			$processCacheKey = 'fastwhale:building:process:'.$village_id;
 
            $processInfo = [
				'process'            => 0, //进度
	            'readRowTotal'       => 0, //总共有数据行数
	            'successInsterTotal' => 0, //总共插入表成功条数
	            'errorTotal'         => 0, //总共插入表失败条数
            ];
			var_dump($processCacheKey);
			try {
				$this->queueReids()->set($processCacheKey,$processInfo,3600);
			}catch (\RedisException $e){
				//TODO 应该直接通过 队列 压入对应的业务日志里
				var_dump(['------RedisException---------'=>$e->getMessage()]);
			}
			
			
			//		return;
			//	    $redisClent = new Redis()

//			var_dump($excelBinFieldRelationShip);
			$s = microtime(true);;
			echo "初始: ".round((memory_get_usage()/(1024*1024)),2)."M\n";
//			$inputFileName = root_path() .'/超级大文件.xlsx';
//			$inputFileName = root_path() .'/设备详情表.xls';
//			$inputFileName = root_path() .'/../upload/building/single_import_sample.xls';
			$inputFileName = root_path() . '/../'.$inputFileName;
			$fileArr = explode('.',$inputFileName);
			$fileExt = strtolower(end($fileArr));

			if ($fileExt === 'xls'){
				$inputFileType = "Xls";
			}else if($fileExt === 'xlsx'){
				$inputFileType = "Xlsx";
			}
			$reader = IOFactory::createReader($inputFileType);
//			$reader->setReadDataOnly(true); //只读

			$spreadsheet = $reader->load($inputFileName);
//			$sheet = $spreadsheet->getSheetByName($worksheetName);
			$sheet = $spreadsheet->getSheet($selectWorkSheetIndex);
			$highestRow = $sheet->getHighestRow(); // 总行数（不准确）)
			$highestColumn = $sheet->getHighestColumn();// 最后有数据的一列，比如 S 列
			$highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);//把列字母转为数字,比如可以得到有 10 列

			$str = "把列字母转为数字「{$highestColumnIndex}」总行数(不一定有数据)：【{$highestRow}】,最后一列【{$highestColumn}】".PHP_EOL;

//			$worksheetDatas = $reader->listWorksheetInfo($inputFileName);
//			foreach ($worksheetDatas as $worksheetData){  //修正
//				if ($worksheetName == $worksheetData['worksheetName']){
//					$highestRow     = 	$worksheetData['totalRows'];
//				}
//			}
//			var_dump($worksheetDatas);
//			$str .= " 总行数(修正之后)：【 {$highestRow} 】".PHP_EOL;
//			$reader = IOFactory::createReader($inputFileType);
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
			
			//↓↓↓表格读取数据开始↓↓↓
			for ($startRow = 2; $startRow <= $highestRow; $startRow += $chunkSize) {
				
				$chunkFilter->setRows($startRow, $chunkSize);
				$spreadsheet = $reader->load($inputFileName);
 
				$sheetData = $spreadsheet->getSheetByName($worksheetName)->toArray(NULL, true, true, true);
				unset($sheetData[1]); //去掉表头
//				var_export(['---------------[ '.$j.' ]读取的数据$sheetData->---------------'=>$sheetData]);

				$insterData = [];
				foreach ($sheetData as  $dk => $data){
//					var_dump($data,$colAndFileds);
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
						continue;
					}
					
					//楼栋编号
					if (!empty($single_number)){
							if (!is_numeric($single_number)){
								if(!preg_match($check_number,$single_number)){
									$allErrorData[] = [ $colAndFileds['single_number'] => '楼栋编号只允许数字-'.$single_number];
									$errorTotal += 1;
									continue;
								}	
							}
						if ($single_number < 0 || $single_number > $max_num) {
							$allErrorData[] = [ $colAndFileds['single_number'] => "该楼栋编号【{$single_number}】不符合规则，请填写其他 1 - {$max_num} 号的楼栋编号！",'RowIndex'=>$dk];
							$errorTotal += 1;
							continue;
						}
					}else{
						$allErrorData[] = [ $colAndFileds['single_number'] => '请填写楼栋编号【必填项】','RowIndex'=>$dk];
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
					
					// 查询一下是否有同一楼栋名称信息
					$where[] = ['single_name','='  ,$single_name];
					$where[] = ['village_id' ,'='  ,$village_id];
					$where[] = ['status'     ,'<>' ,4];
					$isRepeateSignleName = (new HouseVillageSingleService())->getSingleInfo($where);
					if ($isRepeateSignleName && !$isRepeated){ //跳过楼栋名称
						$allErrorData[] =  [ $colAndFileds['single_name'] => '重复楼栋名称，已跳过','RowIndex'=>$dk];
						$errorTotal += 1;
						continue;
					}
				 

					//查询一下是否有同一个漏洞编号信息
					$where   = [];
					$where[] = ['village_id'    ,'='  ,$village_id];
					$where[] = ['single_number' ,'='  ,$single_number];
					$where[] = ['status'        ,'<>' ,4];
					$isRepeateSignleNumber = (new HouseVillageSingleService())->getSingleInfo($where);
					if ($isRepeateSignleNumber && !$isRepeated){
						$allErrorData[] =  [ $colAndFileds['single_name'] => "该楼栋编号【{$single_number}】已经存在，已跳过",'RowIndex'=>$dk];
						$errorTotal += 1;
						continue;
					}
					
					////单元数量
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
					$successInsterTotal += 1;
					$insterData[] = [
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
					$allInsterData[]  = [
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
				}//本次读取处理完毕
//				var_dump(['有错误咧'=>$errorData,'可以导入的数据$insterData'=>$insterData]);
				
				$process =  sprintf('%.2f',$startRow / $highestRow);
				if ($startRow + $chunkSize >=$highestRow ){
					$process = 1;
				}
				printf("\n [%-10s] (当前进度：%2d%% )", str_repeat(".", $j) . ">", $process * 100);
				$processInfo = [
					'process'            => floor($process * 100), //进度
					'readRowTotal'       => $readRowTotal, //总共有数据行数
					'successInsterTotal' => $successInsterTotal, //总共插入表成功条数
					'errorTotal'         => $errorTotal, //总共插入表失败条数
				];
				echo \json_encode($processInfo) . PHP_EOL;
				
//				$this->queueReids()->set($processCacheKey,$process * 100,3600); //进度（理论上暂时还不会有超过一个小时的数据要执行）
				$this->queueReids()->set($processCacheKey,$processInfo,3600); //进度（理论上暂时还不会有超过一个小时的数据要执行）
				
				
				$j++;
				$spreadsheet->disconnectWorksheets();
				unset($spreadsheet);
				$spreadsheet = NULL;
				unset($sheetData);
				$sheetData = NULL;
				
				sleep(1); 
			} 
			//↑↑↑表格读取处理完成↑↑↑

			if (!empty($allErrorData)){
				$errmsg = "请下载错误日志";
//				$this->dump(['所有的错误日志$allErrorData'=>$allErrorData,'所有可以导入的数据$allInsterData'=>$allInsterData]);
			}else{
				$errmsg = "所有数据已成功导入";	
			}

			file_put_contents('xxxx.log',print_r(['所有的错误日志$allErrorData'=>$allErrorData,'所有可以导入的数据$allInsterData'=>$allInsterData],true));
			
			
			$ss = $this->queueReids()->get($processCacheKey);
			echo "导入完成-".$errmsg .PHP_EOL;
			echo "获取Redis里【$processCacheKey]缓存的 = ".$ss . PHP_EOL;
			echo "总共读取有效行数：【 $readRowTotal 】，插入成功条数【 $successInsterTotal 】，失败条数【 $errorTotal 】". PHP_EOL;

 
			
			$e = microtime(true);
 
echo $str;
			echo "使用: ".round((memory_get_usage()/(1024*1024)),2)."M\n";
			unset($str);
			echo "释放: ".round((memory_get_usage()/(1024*1024)),2)."M\n";
			echo "峰值: ".round((memory_get_peak_usage() /(1024*1024) ),2)."M\n";
			echo "总花费时间: ".( $e - $s )  ." s\n";
		}
	}
