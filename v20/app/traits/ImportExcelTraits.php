<?php
	/**
	 * +----------------------------------------------------------------------
	 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
	 * +----------------------------------------------------------------------
	 * @author    合肥快鲸科技有限公司
	 * @copyright 合肥快鲸科技有限公司
	 * @link      https://www.kuaijing.com.cn
	 * @Desc      代码功能描述
	 */

	namespace app\traits;

 
	use think\facade\Queue;

	trait ImportExcelTraits
	{
		/**
		 * 队列名称
		 * @return string
		 */
		public function queueNameImport()
		{
			return 'import-excel';
		}

		public function buildingImportQueue($queuData)
		{
			$queueId = Queue::push('app\job\ImportExcelJob@buildingImportJob',$queuData,$this->queueNameImport());

            $this->importQueueFdump(['压入队列成功=='.__LINE__,$queueId,$queuData],'import_excel/importTraits',1);
            
			return $queueId;
		}


        //临时打印日志
        public function importQueueFdump($data, $filename='test', $append=false){
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

        
    }