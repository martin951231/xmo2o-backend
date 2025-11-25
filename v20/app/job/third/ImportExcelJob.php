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

	namespace app\job\third;

	use think\Exception;
	use think\facade\Console;
	use think\queue\Job as DoJob;
    use app\community\model\service\third\ImportExcelService;

	class ImportExcelJob
	{

        public function roomDataImportJob(DoJob $job,$data)
        {
            file_put_contents('thirdRoomData.log','----任务已经重试了几次----->：'.$job->attempts().' ----- > '.print_r($data,true). PHP_EOL, 8);
            if ($job->attempts() >= 1){
                $job->delete();
            }
            try {
                file_put_contents('thirdRoomData.log','开始执行队列,调用命令行---->roomDataImport --->：'.__FUNCTION__.' ----- > '.print_r($data,true). PHP_EOL, 8);
                (new ImportExcelService())->RoomExecute($data);
            }catch (Exception $exception){
                file_put_contents('thirdRoomData.log',' ,调用命令行异常了 --->： ----- > '.print_r($exception->getMessage(),true). PHP_EOL, 8);
            }
            //如果任务执行成功后 记得删除任务，不然这个任务会重复执行，直到达到最大重试次数后失败后，执行failed方法
            $job->delete();
        }

        public function userDataImportJob(DoJob $job,$data)
        {
            file_put_contents('thirdUserData.log','----任务已经重试了几次----->：'.$job->attempts().' ----- > '.print_r($data,true). PHP_EOL, 8);
            if ($job->attempts() >= 1){
                $job->delete();
            }
            try {
                file_put_contents('thirdUserData.log','开始执行队列,调用命令行---->userDataImport --->：'.__FUNCTION__.' ----- > '.print_r($data,true). PHP_EOL, 8);
                (new ImportExcelService())->UserExecute($data);
            }catch (Exception $exception){
                file_put_contents('thirdUserData.log',' ,调用命令行异常了 --->： ----- > '.print_r($exception->getMessage(),true). PHP_EOL, 8);
            }
            //如果任务执行成功后 记得删除任务，不然这个任务会重复执行，直到达到最大重试次数后失败后，执行failed方法
            $job->delete();
        }

        public function chargeDataImportJob(DoJob $job,$data)
        {
            file_put_contents('thirdChargeData.log','----任务已经重试了几次----->：'.$job->attempts().' ----- > '.print_r($data,true). PHP_EOL, 8);
            if ($job->attempts() >= 1){
                $job->delete();
            }
            try {
                file_put_contents('thirdChargeData.log','开始执行队列,调用命令行---->userDataImport --->：'.__FUNCTION__.' ----- > '.print_r($data,true). PHP_EOL, 8);
                (new ImportExcelService())->chargeExecute($data);
            }catch (Exception $exception){
                file_put_contents('thirdChargeData.log',' ,调用命令行异常了 --->： ----- > '.print_r($exception->getMessage(),true). PHP_EOL, 8);
            }
            //如果任务执行成功后 记得删除任务，不然这个任务会重复执行，直到达到最大重试次数后失败后，执行failed方法
            $job->delete();
        }
		
		public function failed($data)
		{
			file_put_contents('thirdUserData.log','队列执行失败了--->：'.__FUNCTION__.' ----- > '.print_r($data,true). PHP_EOL, 8);
		}
	}