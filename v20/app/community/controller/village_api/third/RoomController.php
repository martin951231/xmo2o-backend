<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      三方数据相关导入处理显示等
 */
namespace app\community\controller\village_api\third;

use app\common\model\service\UploadFileService;
use app\community\controller\CommunityBaseController;

use app\traits\third\ImportExcelTraits;
use think\facade\Cache;
use think\facade\Config;
use app\community\model\service\third\ImportExcelService;


class RoomController extends CommunityBaseController{

    use ImportExcelTraits;
    
    public function villageUploadFile()
    {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台，再上传！');
        }
        $service    = new UploadFileService();
        $file       = $this->request->file('file');
        $upload_dir = $this->request->param('upload_dir');
        $type       = $this->request->param('type','');
        if (empty($type)){
            return api_output_error(1003, "缺少必传参数");
        }

        if (empty(Config::get('cache.stores.redis'))){
            return api_output(1003, ['error' => '请先检查和配置'.app()->getRootPath() .'config/cache.php'], '请先配置Redis');
        }
        $processCacheKey = $this->getProcessCacheKey($village_id,$type);
        try {
            $process = Cache::store('redis')->get($processCacheKey);
        }catch (\RedisException $e){
            return api_output(1003, ['error' => '请先检查和配置'.app()->getRootPath() .'config/cache.php'], $e->getMessage());
        }

        if (!is_null($process)){
//            return api_output_error(1003, "当前已有数据在执行中，当前执行进度【{$process}%】请耐心等待执行完，再导入！");
        }

        $valdate = [
            'fileSize' => 1024 * 1024 * 10, // 10M 
            'fileExt' => 'xls,xlsx'
        ];
        $upload_dir = $upload_dir .DIRECTORY_SEPARATOR.$village_id;
        try {
            $savepath = $service->uploadFile($file, $upload_dir,$valdate);
            return api_output(0, $savepath, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    //准备导入
    public function startRoomImport()
    {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }

        //1、接收到 文件链接
        //2、放入队列
        //3、告知前端调整到进度展示画面
        //4、队列调用命令行执行

        $inputFileName     = $this->request->post('inputFileName','');
        $nowVillageInfo    = [
            'village_id'        => $this->adminUser['village_id'],
            'village_name'      => $this->adminUser['village_name'],
            'village_address'   => $this->adminUser['village_address'],
            'account'           => $this->adminUser['account'],
            'property_phone'    => $this->adminUser['property_phone'],
            'adminName'         => $this->adminUser['adminName'],
            'user_name'         => $this->adminUser['user_name'],
            'adminId'           => $this->adminUser['adminId'],
            'loginType'         => $this->adminUser['loginType'],
        ];
        $nowTime = time();
        $orderGroupId = md5(uniqid().$nowTime);// 标记统一执行命令
        $queueData = [
            'inputFileName'             => $inputFileName,
            'nowVillageInfo'            => $nowVillageInfo,
            'worksheetName'             => 'Sheet1',
            'orderGroupId'              => $orderGroupId,
        ];
        $queueId = $this->roomDataImportQueue($queueData);
//        $fileArr = (new ImportExcelService())->RoomExecute($queueData);
        $message = '提交成功，准备导入！';
        return api_output(0, ['orderGroupId'=>$orderGroupId,'queueId'=>$queueId,'inputFileName'=>$inputFileName, 'message' => $message], $message);
    }

    //准备导入
    public function startUserImport()
    {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }

        //1、接收到 文件链接
        //2、放入队列
        //3、告知前端调整到进度展示画面
        //4、队列调用命令行执行

        $inputFileName     = $this->request->post('inputFileName','');
        $nowVillageInfo    = [
            'village_id'        => $this->adminUser['village_id'],
            'village_name'      => $this->adminUser['village_name'],
            'village_address'   => $this->adminUser['village_address'],
            'account'           => $this->adminUser['account'],
            'property_phone'    => $this->adminUser['property_phone'],
            'adminName'         => $this->adminUser['adminName'],
            'user_name'         => $this->adminUser['user_name'],
            'adminId'           => $this->adminUser['adminId'],
            'loginType'         => $this->adminUser['loginType'],
        ];
        $nowTime = time();
        $orderGroupId = md5(uniqid().$nowTime);// 标记统一执行命令
        $queueData = [
            'inputFileName'             => $inputFileName,
            'nowVillageInfo'            => $nowVillageInfo,
            'worksheetName'             => 'Sheet1',
            'orderGroupId'              => $orderGroupId,
        ];
        $queueId = $this->userDataImportQueue($queueData);
//        $fileArr = (new ImportExcelService())->UserExecute($queueData);
        $message = '提交成功，准备导入！';
        return api_output(0, ['orderGroupId'=>$orderGroupId,'queueId'=>$queueId,'inputFileName'=>$inputFileName, 'message' => $message], $message);
    }


    //准备导入
    public function startChargeImport()
    {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }

        //1、接收到 文件链接
        //2、放入队列
        //3、告知前端调整到进度展示画面
        //4、队列调用命令行执行

        $inputFileName     = $this->request->post('inputFileName','');
        $nowVillageInfo    = [
            'village_id'        => $this->adminUser['village_id'],
            'property_id'       => $this->adminUser['property_id'],
            'village_name'      => $this->adminUser['village_name'],
            'village_address'   => $this->adminUser['village_address'],
            'account'           => $this->adminUser['account'],
            'property_phone'    => $this->adminUser['property_phone'],
            'adminName'         => $this->adminUser['adminName'],
            'user_name'         => $this->adminUser['user_name'],
            'adminId'           => $this->adminUser['adminId'],
            'loginType'         => $this->adminUser['loginType'],
        ];
        $nowTime = time();
        $orderGroupId = md5(uniqid().$nowTime);// 标记统一执行命令
        $queueData = [
            'inputFileName'             => $inputFileName,
            'nowVillageInfo'            => $nowVillageInfo,
            'worksheetName'             => 'Sheet1',
            'orderGroupId'              => $orderGroupId,
        ];
        $queueId = $this->chargeDataImportQueue($queueData);
//        $fileArr = (new ImportExcelService())->chargeExecute($queueData);

        $message = '提交成功，准备导入！';
        return api_output(0, ['orderGroupId'=>$orderGroupId,'queueId'=>$queueId,'inputFileName'=>$inputFileName, 'message' => $message], $message);
    }
    
    
    //获取当前导入的进度条
    public function refreshProcess()
    {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $type = $this->request->param('type','');
        if (empty($type)){
            return api_output_error(1003, "缺少必传参数");
        }
        $orderGroupId = $this->request->param('orderGroupId','');
        if (empty($orderGroupId)){
            $orderGroupId = $village_id;
        }
        $processCacheKey = $this->getProcessCacheKey($orderGroupId,$type);
        $totalCacheKey   = $this->getTotalCacheKey($orderGroupId,$type);
        $surplusCacheKey = $this->getSurplusCacheKey($orderGroupId,$type);
        $excelCacheKey   = $this->getExcelCacheKey($orderGroupId,$type);
        try {
            $process   = Cache::store('redis')->get($processCacheKey);
            $total     = Cache::store('redis')->get($totalCacheKey);
            $surplus   = Cache::store('redis')->get($surplusCacheKey);
            $excelInfo = Cache::store('redis')->get($excelCacheKey);
        }catch (\RedisException $e){
            return api_output(2, ['error' => app()->getRootPath() .'config/cache.php'], $e->getMessage());
        }
        if ($excelInfo) {
            $process = 100;
        }
        if ($process == 99) {
            $success = $total - 1;
            $surplus = 1;
        } elseif ($process >= 100){
            Cache::store('redis')->delete($processCacheKey);
            Cache::store('redis')->delete($totalCacheKey);
            Cache::store('redis')->delete($surplusCacheKey);
            Cache::store('redis')->delete($excelCacheKey);
            $success = $total;
            $surplus = 0;
            $process = 100;
        } else {
            $success = intval($total) - intval($surplus);
        }
        if (!$process) {
            $success = 0;
            $process = 0;
            if ($total && !$surplus) {
                $surplus = intval($total);
            }
        }
        return  api_output(0, ['process'=>floatval($process), 'total' => intval($total),'success' => intval($success), 'surplus' => intval($surplus), 'excelInfo' => $excelInfo], '当前进度！');
    }
    
    
    /**
     * 进展  0-100
     * @param integer $village_id 小区ID
     * @param string $bussiness 业务名称
     *
     * @return string
     */
    protected function getProcessCacheKey($village_id,$bussiness)
    {
        return 'thirdimport:'.$bussiness.':process:'.$village_id;
    }

    /**
     * 执行总数
     * @param integer $village_id 小区ID
     * @param string $bussiness 业务名称
     *
     * @return string
     */
    protected function getTotalCacheKey($village_id,$bussiness)
    {
        return 'thirdimport:'.$bussiness.':total:'.$village_id;
    }

    /**
     * @param integer $village_id 小区ID
     * @param string $bussiness 业务名称
     *
     * @return string
     */
    protected function getSurplusCacheKey($village_id,$bussiness)
    {
        return 'thirdimport:'.$bussiness.':surplus:'.$village_id;
    }

    /**
     * @param integer $village_id 小区ID
     * @param string $bussiness 业务名称
     *
     * @return string
     */
    protected function getExcelCacheKey($village_id,$bussiness)
    {
        return 'thirdimport:'.$bussiness.':excel:'.$village_id;
    }
}