<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      通行日志记录 队列消费
 */

namespace app\job\device;

use app\community\model\service\Device\DeviceHkNeiBuHandleService;
use think\Exception;
use think\queue\Job as DoJob;

class HandleHouseUserLogJob
{
    public function fire(DoJob $job , $data)
    {
        if ($job->attempts() >= 1) {
            $job->delete();
        }
        $jobType = isset($data['jobType']) && trim($data['jobType']) ? trim($data['jobType']) : '';
        fdump_api(['line'=>__LINE__, 'data' => $data],'jobFire/handleHouseUserLogJobLog',1);
        try {
            switch ($jobType) {
                case 'filterCommunityEventAccess':
                    $communityEventAccessArr = isset($data['communityEventAccessArr']) ? $data['communityEventAccessArr'] : [];
                    (new DeviceHkNeiBuHandleService())->filterCommunityEventAccess($communityEventAccessArr, true);
                    break;
                case 'filterCommunityEventAccessLog':
                    $event = isset($data['event']) ? $data['event'] : [];
                    $deviceArr = isset($data['deviceArr']) ? $data['deviceArr'] : [];
                    (new DeviceHkNeiBuHandleService())->filterCommunityEventAccessLog($event, $deviceArr);
                    break;
                case 'handleUserLogToSaveImage':
                    (new DeviceHkNeiBuHandleService())->handleUserLogToSaveImage($data);
                    break;
            }
        } catch (Exception $e){
            fdump_api(['err'=>$e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'data' => $data],'jobFire/errHandleHouseUserLogJobLog',1);
        }
    }

}