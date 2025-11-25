<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      普通操作日志队列 ，本队列不允许塞业务日志，
 *            另外，注意队列重复消费的问题
 */

namespace app\job;

use app\common\model\service\CommonSystemLogService;
use app\community\model\service\Car\ParkCarToJobService;
use app\community\model\service\order\ScrcuService;
use app\community\model\service\PileUserService;
use think\Exception;
use think\queue\Job as DoJob;

class CommonLogSysJob
{
    /**
     * @param DoJob $job
     * @param     $data  eg.
     *                       $data = [
     * 'logData' => [ //当前要记录的信息
     * 'tbname' => '物业新版收费科目管理设置表',
     * 'table'  => 'house_new_charge',
     * 'client' => '物业后台',
     * 'trigger_path' => '缴费管理->收费科目管理',
     * 'trigger_type' => $this->getAddLogName(),
     * 'addtime'      => time(),
     * //其他的依赖项，请具体看 v20/app/common/model/db/CommonSystemLog.php $schema 定义
     * ],
     * 'newData' => [], //当前要更新的数据
     * 'oldData' => [],  //表更新之前的数据
     *                          'ipData'  => [], //当前操作的IP信息
     * ];
     *
     *  可以参考：v20/app/traits/CommonLogTraits.php
     */
    public function fire(DoJob $job, $data)
    {
        if ($job->attempts() >= 1) { //
            $job->delete();
        }
        $jobType = isset($data['jobType']) ? trim($data['jobType']) : '';
        if ($jobType) {
            try {
                switch ($jobType) {
                    case 'lvccQueryResult':
                        (new PileUserService())->lvccQueryResult($data);
                        break;
                    case 'scrcuQueryResult':
                        $job_id = $job->getJobId();
                        $data['job_id'] = $job_id;
                        (new ScrcuService())->scrcu($data);
                        break;
                    case 'clearScrcuQueryResult':
                        (new ScrcuService())->clearScrcuQueryResult($data);
                        break;
                    case 'handleCarPassImageRecord':
                        (new ParkCarToJobService())->handleCarPassImageRecord($data);
                        break;
                    case 'handleOutToInPark':
                        (new ParkCarToJobService())->handleOutToInPark($data);
                        break;
                }
            } catch (Exception $e) {
                fdump_api(['line' => $e->getLine(), 'code' => $e->getCode(), 'err' => $e->getMessage()], 'lvcc/errFireJob', 1);
            }
        } else {
            $diff=array();
            if(isset($data['oldData']) && $data['oldData']){
                $diff = array_diff_assoc($data['newData'], $data['oldData']);
            }
            $data['logData']['content'] = \json_encode($data['newData'],JSON_UNESCAPED_UNICODE);
            try {
                (new CommonSystemLogService())->addComonLlog($data, $diff);
            } catch (Exception $e) {

            }
        }
    }

    public function failed($data)
    {
    }
}