<?php
	declare (strict_types = 1);

	namespace app\command;

	use app\common\ChunkReadFilter;
	use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
	use PhpOffice\PhpSpreadsheet\IOFactory;
	use PhpOffice\PhpSpreadsheet\Spreadsheet;
	use redis\Redis;
	use think\console\Command;
	use think\console\Input;
	use think\console\input\Argument;
	use think\console\input\Option;
	use think\console\Output;
	use think\facade\Cache;
	use think\facade\Config;

	class BuildingImport extends Command
	{
		protected function configure()
		{
			// 指令配置
			$this->setName('buildingImport')
				->setDescription('楼栋命令行导入指令');
		}

		protected function execute(Input $input, Output $output)
		{

			//使用Redis缓存
//	    $cacheRedisConfig = Config::get('cache.stores.redis');
//		dump($cacheRedisConfig);
//		return;
//	    $redisClent = new Redis()

			$s = microtime(true);;
			echo "初始: ".(memory_get_usage()/(1024*1024))."M\n";
			$inputFileName = root_path() .'/超级大文件.xlsx';
//			$inputFileName = root_path() .'/设备详情表.xls';
			$fileArr = explode('.',$inputFileName);
			$fileExt = strtolower(end($fileArr));

			if ($fileExt === 'xls'){
				$inputFileType = "Xls";
			}else if($fileExt === 'xlsx'){
				$inputFileType = "Xlsx";
			}
			$reader = IOFactory::createReader($inputFileType);
			$reader->setReadDataOnly(true); //只读
//
//			$spreadsheet = $reader->load($filePathName);
//
//			$sheetIndex = 0; //通过参数读取第那个个 sheet
//			$sheet = $spreadsheet->getSheet($sheetIndex);
//			$highestRow = $sheet->getHighestRow(); // 总行数
//			$highestColumn = $sheet->getHighestColumn();// 最后有数据的一列，比如 S 列
//			$highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);//把列字母转为数字,比如可以得到有 10 列

			$worksheetData = $reader->listWorksheetInfo($inputFileName);
			echo '<h3>工作表信息</h3>';
			echo '<ol>';
			$rawRows = 0;
			foreach ($worksheetData as $worksheet) {
				$rawRows = $worksheet['totalRows'];
//				var_dump($worksheet);
//				echo '<li>', $worksheet['worksheetName'], '<br />';
//				echo '总行数: ', $worksheet['totalRows'],
//				' 一共: ', $worksheet['totalColumns'], ' 列<br />';
//				echo 'Cell Range: A1:',
//				$worksheet['lastColumnLetter'];
//				echo '</li>';
			}
//			echo '</ol>';

			echo "<hr/>";


//			$output->writeln('执行完-->总行数：'.$highestRow);
//			$output->writeln('最后有数据的一列-->：'.$highestColumn);
//			$output->writeln('把列字母转为数字-->：'.$highestColumnIndex);
//


//			for ($rowIndex=2 ; $rowIndex <= $highestRow ; $rowIndex++){
//				//		    echo "第{$rowIndex}行 ，总列数：{$highestColumnIndex} <br/>";
//				for ($colIndex = 1; $colIndex < $highestColumnIndex ;$colIndex++ ){
//					$rowOjb = $sheet->getCellByColumnAndRow($colIndex,$rowIndex);
//					$value = $rowOjb->getValue();
//								    echo $value . "( ". $rowOjb->getColumn() .$colIndex.")\t" ;
////					file_put_contents('log.log', $rowOjb->getColumn() .$colIndex.':'.$value .' ',8);
//					unset($value);
//				}
////				file_put_contents('log.log', PHP_EOL ,8);
//				//		    echo "<br/>-----第[{$rowIndex}]行完成 <br/>";
//				//根据导入行，实现进度条
//				//		    printf("<br/> [%-100s] (%2d%%/%2d%%)", str_repeat("=", $rowIndex) . ">", ($rowIndex / $highestRow) * 100, $highestRow);
//			}


			$reader = IOFactory::createReader($inputFileType);
			$chunkSize = 1000;
			$chunkFilter = new ChunkReadFilter();
			$reader->setReadFilter($chunkFilter);

			$j = 1;
			for ($startRow = 2; $startRow <= $rawRows; $startRow += $chunkSize) {
				$chunkFilter->setRows($startRow, $chunkSize);
				$spreadsheet = $reader->load($inputFileName);

				// Do some processing here

				$sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
//				var_dump($sheetData);




				$process = $startRow / $rawRows;
				if ($startRow + $chunkSize >=$rawRows ){
					$process = 1;
				}

				printf("\n [%-10s] (当前进度：%2d%% / 总数：%2d%%)", str_repeat(".", $j) . ">", $process * 100, $rawRows);
				$j++;
				$spreadsheet->disconnectWorksheets();
				unset($spreadsheet);
				$spreadsheet = NULL;
				unset($sheetData);
				$sheetData = NULL;
			}



			$e = microtime(true);
			Cache::store('redis')->set('laotian','666666666',3600);

			echo "使用: ".(memory_get_usage()/(1024*1024))."M\n";
			unset($str);
			echo "释放: ".(memory_get_usage()/(1024*1024))."M\n";
			echo "峰值: ".(memory_get_peak_usage() /(1024*1024) )."M\n";
			echo "总花费时间: ".( $e - $s )  ." s\n";
		}
	}
