<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      公共订单业务关联记录表 队列
 */

namespace app\job\order;

use app\common\model\service\order\CommonOrderBusinessRecordService;
use think\Exception;
use think\queue\Job as DoJob;

class CommonOrderBusinessRecordJob
{

    public function fire(DoJob $job , $data)
    {
        if ($job->attempts() >= 1){
            $job->delete();
        }
        try {
            // todo 对应执行
            if (isset($data['job_id'])) {
                unset($data['job_id']);
            }
            (new CommonOrderBusinessRecordService())->toExecute($data);
        } catch (Exception $e){
            fdump_api([$data,$e->getMessage()],'ErrCommonOrderBusinessRecordJobFire',1);
        }
    }


    public function failed($data)
    {
    }
}