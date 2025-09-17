<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      kumike（酷米客-杂淘）-A4(咚咚)人脸设备队列
 */

namespace app\job\device;

use app\community\model\service\Device\A4DeviceService;
use app\consts\device\A4DeviceConst;
use think\facade\Cache;
use think\Exception;
use think\queue\Job as DoJob;

class A4DeviceJob
{
    public function fire(DoJob $job , $data)
    {
        if ($job->attempts() >= 1) {
            $job->delete();
        }
        $job_id = $job->getJobId();
        $cacheTag  = A4DeviceConst::A4_DEVICE_JOB_REDIS_TAG;
        $cacheKey   = A4DeviceConst::A4_DEVICE_JOB_REDIS_KEY . $job_id;
        Cache::store('redis')->tag($cacheTag)->set($cacheKey,1);
        $jobFunc = isset($data['jobFunc']) ? trim($data['jobFunc']) : '';
        switch ($jobFunc) {
            case 'a4ArrPersonMsgHandleBox':
                (new A4DeviceService())->a4ArrPersonMsgHandleBox($data);
                break;
            case 'addA4MemberHouseholdBox':
                (new A4DeviceService())->addA4MemberHouseholdBox($data);
                break;
            case 'addA4Community':
                (new A4DeviceService())->addA4Community($data);
                break;
            case 'addA4Zone':
                (new A4DeviceService())->addA4Zone($data);
                break;
            case 'addVillageA4Single':
                (new A4DeviceService())->addVillageA4Single($data);
                break;
            case 'addA4Building':
                (new A4DeviceService())->addA4Building($data);
                break;
            case 'addSingleA4Floor':
                (new A4DeviceService())->addSingleA4Floor($data);
                break;
            case 'addA4Unit':
                (new A4DeviceService())->addA4Unit($data);
                break;
            case 'addUnitA4Room':
                (new A4DeviceService())->addUnitA4Room($data);
                break;
            case 'addA4Room':
                (new A4DeviceService())->addA4Room($data);
                break;
            case 'addAllA4Property':
                (new A4DeviceService())->addAllA4Property($data);
                break;
            case 'addA4Property':
                (new A4DeviceService())->addA4Property($data);
                break;
            case 'addA4RoomHolds':
                (new A4DeviceService())->addA4RoomHolds($data);
                break;
            case 'notifyA4ImgUp':
                (new A4DeviceService())->notifyA4ImgUp($data);
                break;
        }

        try {
            
        } catch (Exception $e){
            fdump_api([$data,$e->getMessage()],'A4/errA4DeviceJobLog',1);
        }
    }

}