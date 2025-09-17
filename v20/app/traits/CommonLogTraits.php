<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      普通队列相关属性
 */

namespace app\traits;

use app\job\CommonLogSysJob;
use think\Exception;
use think\facade\Queue;

trait CommonLogTraits
{
    use WebIpLocationTraits;

    /**
     * 队列名称
     * @return string
     */
    public function queueNameComonLog()
    {
        return 'common-log';
    }

    /**
     * 修改类型: 新增
     * @return string
     */
    public function getAddLogName()
    {
        return 'inster';
    }

    /**
     * 修改类型: 更新、编辑
     * @return string
     */
    public function getUpdateNmae()
    {
        return 'update';
    }

    /**
     * 修改类型: 删除
     * @return string
     */
    public function getDeleteName()
    {
        return 'delete';
    }

    /**
     * 把配置日志压入队列，默认延迟5秒执行
     * @param     $queuData
     *             eg.
     *  $data = [
     *          'logData' => [ //当前要记录的信息
     *          'tbname' => '物业新版收费科目管理设置表',
     *          'table'  => 'house_new_charge',
     *          'client' => '物业后台',
     *          'trigger_path' => '缴费管理->收费科目管理',
     *          'trigger_type' => $this->getAddLogName(),
     *          'addtime'      => time(),
     *          '*   //其他的依赖项，请具体看 v20/app/common/model/db/CommonSystemLog.php $schema 定义
     *      ],
     *      'newData' => [], //当前要更新的数据
     *      'oldData' => []  //表更新之前的数据
     *    ];
     *
     * @param int $time default 5 s
     */
    public function laterLogInQueue($queuData, $time = 15,$throwException=false)
    {
        $queuData['ipData'] = $this->getIpBrowserInfo(request()->ip());
        $queuData['ipData']['add_time'] = time();
        try {
           return Queue::later($time, CommonLogSysJob::class, $queuData, $this->queueNameComonLog());
        } catch (\Exception $e) {
            if($throwException){
                //	throw new Exception("请检查是否安装了Redis或未启动Redis服务");
            }
        }
    }

    /**
     * 管理员登录日志
     * @param     $data eg.
     *                    realname      真名
     *                    account       登录账号
     *                    login_type    登录类型
     * @param int $time
     */
    public function laterAdminLoginLogQueue($data, $time = 15)
    {
        $queuData = $this->getIpBrowserInfo(request()->ip());
        $queuData['add_time'] = time();
        $queuData['realname'] = $data['realname'];
        $queuData['login_type'] = $data['login_type'];
        $queuData['account'] = $data['account'];
        if (isset($data['login_status'])) {
            $queuData['login_status'] = $data['login_status'];
        }
        if (isset($data['reson'])) {
            $queuData['reson'] = $data['reson'];
        }
        try {
            Queue::later($time, 'app\job\LoginLogJob@adminLoginLog', $queuData, $this->queueNameComonLog());
        } catch (\Exception $e) {
//			 throw new Exception("请检查是否安装了Redis或未启动Redis服务");
        }
    }

    /**
     * 社区 普通用户登录日志
     * @param     $data eg.
     *                    login_client int 登录类型对应街道、小区、物业'
     *                    login_id     int  当前登录系统的绑定 id 对应街道、小区、物业
     *                    account       当前登录账号
     *
     * @param int $time
     */
    public function laterUserLoginLogQueue($data, $time = 15)
    {
        $queuData = $this->getIpBrowserInfo(request()->ip());
        $queuData['add_time'] = time();
        $queuData['login_client'] = $data['login_client'];
        $queuData['login_type'] = $data['login_type'];
        $queuData['account'] = $data['account'];
        $queuData['login_id'] = $data['login_id'];
        if (isset($data['login_status'])) {
            $queuData['login_status'] = $data['login_status'];
        }
        if (isset($data['reson'])) {
            $queuData['reson'] = $data['reson'];
        }
        if (isset($data['realname'])) {
            $queuData['realname'] = $data['realname'];
        }
        try {
            Queue::later($time, 'app\job\LoginLogJob@userLoginLog', $queuData, $this->queueNameComonLog());
        } catch (\Exception $e) {
//			 throw new Exception("请检查是否安装了Redis或未启动Redis服务");
        }
    }

    // 首次驴充充查询订单结果间隔
    public $lvccQueryTimes = 13;
    // 首次驴充充查询订单2次查询间隔
    public $lvccQuerySpaceTimes = 5;
    // 农信支付查询支付结果间隔时间
    public $scrcuQuerySpaceTimes = 15;
    // 停车图片异步处理 间隔时间
    public $parkCarImageSpaceTimes = 6;
    // 停车出场异步处理 间隔时间
    public $parkOutSpaceTimes = 10;

    public function traitCommonLvCC($queueData, $time = 0)
    {
        if (!isset($queueData['jobType']) || !$queueData['jobType']) {
            $queueData['jobType'] = 'lvccQueryResult';
        }
        if ($time > 0) {
            $job_id = $this->traitCommonLvCCLaterToJob($queueData, $time);
        } else {
            $job_id = $this->traitCommonLvCCPushToJob($queueData);
        }
        return $job_id;
    }

    public function traitCommonLvCCLaterToJob($queueData, $time = 5)
    {
        return Queue::later($time, CommonLogSysJob::class, $queueData, $this->queueNameComonLog());
    }

    public function traitCommonLvCCPushToJob($queueData)
    {
        return Queue::push(CommonLogSysJob::class, $queueData, $this->queueNameComonLog());
    }
}