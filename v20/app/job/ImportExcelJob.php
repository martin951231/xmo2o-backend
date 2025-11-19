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

	namespace app\job;

	use think\Exception;
	use think\facade\Console;
	use think\queue\Job as DoJob;

	class ImportExcelJob
	{

		public function buildingImportJob(DoJob $job,$data)
		{
            $this->importJobFdump(['接收队列=='.__LINE__,$job->attempts(),$data],'import_excel/importJob',1);
			if ($job->attempts() >= 1){
				$job->delete();
			}
			try {
                $this->importJobFdump(['开始执行队列,调用命令行=='.__LINE__,$data],'import_excel/importJob',1);
				Console::call($data['command'], [\json_encode($data)]);
			}catch (Exception $exception){
                $this->importJobFdump(['调用命令行异常了=='.__LINE__,$data,$exception->getMessage()],'import_excel/importJobError',1);
			}
			//如果任务执行成功后 记得删除任务，不然这个任务会重复执行，直到达到最大重试次数后失败后，执行failed方法
			$job->delete();
		}
		
		public function failed($data)
		{
            $this->importJobFdump(['队列执行失败了=='.__LINE__,$data],'import_excel/importJobError',1);
		}


        //临时打印日志
        public function importJobFdump($data, $filename='test', $append=false){
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