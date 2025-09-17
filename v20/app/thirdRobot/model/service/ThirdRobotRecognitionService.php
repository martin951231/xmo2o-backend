<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      机器人-CROSS
 * @documentLink https://cross-fit86.harix.iamidata.com/crss-docs/#/
 */

namespace app\thirdRobot\model\service;
use \app\common\model\service\ConfigService;
use net\Http as Http;
use think\Exception;

class ThirdRobotRecognitionService
{
    public $devApiUri = 'https://cross-fit86.harix.iamidata.com/crss-openapi/router/rest';  //测试地址
    public $masterApiUri = 'https://cross.harix.iamidata.com/crss-openapi/router/rest'; //正式地址
    public $requestApiUri = 'https://cross.harix.iamidata.com/crss-openapi/router/rest'; //请求地址
    public $appid = ''; //对接appid
    public $appsecret = ''; //对接appsecret
    public $third_robot_recognition_open = 0;  //机器人对接 0未开启，1开启
    public $third_robot_recognition_open_hi = 0; //机器人打招呼设置 0未开启，1开启
    public $third_robot_recognition_hitext = ''; //打招呼内容
    public $commonParam = array();

    public function __construct($appid = '', $appsecret = '')
    {
        //$this->requestApiUri = $this->devApiUri;
        $this->requestApiUri=$this->masterApiUri;
        $this->appid = $appid;
        $this->appsecret = $appsecret;
        $this->getConfigure();
        $this->checkConfigure();
        $this->getCommonParam();
    }
    protected function getConfigure()
    {
        if (empty($this->appid)) {
            $this->appid = cfg('third_robot_recognition_appid');
        }
        $this->appid = trim($this->appid);
        if (empty($this->appsecret)) {
            $this->appsecret = cfg('third_robot_recognition_appsecret');
        }
        $this->appsecret = trim($this->appsecret);
        $third_robot_recognition_open = cfg('third_robot_recognition_open');
        $third_robot_recognition_open_hi = cfg('third_robot_recognition_open_hi');
        $third_robot_recognition_hitext = cfg('third_robot_recognition_hitext');
        
        if(empty($this->appid) && empty($this->appsecret)){
            $configService=new ConfigService();
            $this->appid=$configService->getOneField('third_robot_recognition_appid');
            $this->appsecret=$configService->getOneField('third_robot_recognition_appsecret');
            $third_robot_recognition_open=$configService->getOneField('third_robot_recognition_open');
            $third_robot_recognition_open_hi=$configService->getOneField('third_robot_recognition_open_hi');
            $third_robot_recognition_hitext=$configService->getOneField('third_robot_recognition_hitext');
        }
        $this->appid = trim($this->appid);
        $this->appsecret = trim($this->appsecret);
        $this->third_robot_recognition_open = intval($third_robot_recognition_open);
        $this->third_robot_recognition_open_hi = $third_robot_recognition_open_hi;
        $this->third_robot_recognition_hitext = trim($third_robot_recognition_hitext);
    }
    protected function checkConfigure()
    {
        if ($this->third_robot_recognition_open < 1) {
            throw new \think\Exception("机器人功能未开启！", 20002);
        }
        if (empty($this->appid)) {
            throw new \think\Exception("对接AppId未填写！", 20002);
        }
        if (empty($this->appsecret)) {
            throw new \think\Exception("对接AppSecret未填写！", 20002);
        }
        return true;
    }

    /**
     * 数据签名.
     */
    protected function getThirdDataSign($datas = array())
    {
        //签名步骤一：按字典序排序参数
        ksort($datas);
        $buffStr = "";
        foreach ($datas as $kk => $vv) {
            if (!empty($vv)) {
                $buffStr .= $kk . $vv;
            }
        }
        $pString = '';
        if (strlen($buffStr) > 0) {
            //签名步骤二：在string后加入KEY
            $buffStr = $this->appsecret . $buffStr . $this->appsecret;
//            fdump_api($buffStr, 'thirdRobot/buffStrLog',1);
            $pString = md5($buffStr);
        }
        return $pString;
    }

    /**
     * 公用参数.
     */
    protected function getCommonParam()
    {
        $commonParam = array();
        $commonParam['method'] = '';
        $commonParam['appId'] = $this->appid;
        $commonParam['timestamp'] = time();
        $commonParam['v'] = '1.0';
        $this->commonParam = $commonParam;
        return $this->commonParam;
    }

    /**
     * 公用的组装请求.
     */
    public function commonRequestRobot($params, $method)
    {
        ksort($params);
        $method = trim($method);
        $requestData = $this->commonParam;
        $requestData['method'] = $method;
        $requestData['params'] = json_encode($params, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $signStr = $this->getThirdDataSign($requestData);
        $requestData['sign'] = $signStr;
        $requestData['params'] = $params;
        $headers = array();
        $headers[] = "Accept-Charset: utf-8";
        $headers[] = "Content-type: application/json;";
        $requestJsonStr = json_encode($requestData, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $result = http_request($this->requestApiUri, 'POST', $requestJsonStr, $headers);
        $responseData = array();
        $methodString = str_replace('.', '_', $method);
        $methodString=$methodString.'_'.date('H');
        fdump_api([$method . '_Line_' . __LINE__, 'params' => $params, 'requestJsonStr' => $requestJsonStr, 'result' => $result], 'thirdRobot/' . $methodString . 'Log', 1);
        if ($result && $result['1']) {
            $responseData = json_decode($result['1'], 1);
        }
        return $responseData;
    }

    /**
     * 2.1.1 查询组织机构.
     */
    public function getRoboterBranchList($params = [])
    {
        return $this->commonRequestRobot($params, 'cross.base.getBranchList');
    }

    /**
     * 2.1.2 添加组织机构.
     */
    public function addRoboterBranch($params = array())
    {
        return $this->commonRequestRobot($params, 'cross.base.addBranch');
    }

    /**
     * 2.1.3 修改组织机构.
     */
    public function updateRoboterBranch($params = array())
    {
        return $this->commonRequestRobot($params, 'cross.base.updateBranch');
    }

    /**
     * 2.1.4 删除组织机构.
     */
    public function deleteRoboterBranch($params = array())
    {
        return $this->commonRequestRobot($params, 'cross.base.deleteBranch');
    }

    /**
     * 2.1.5 校验组织机构.
     */
    public function checkRoboterBranch($params = array())
    {
        return $this->commonRequestRobot($params, 'cross.base.checkBranch');
    }

    /**
     * 2.1.6 新增人员信息.
     */
    public function addRoboterFace($params = array())
    {
        return $this->commonRequestRobot($params, 'cross.base.addFace');
    }

    /**
     * 2.1.7 查询人员信息.
     */
    public function pageRoboterFace($params = array())
    {
        return $this->commonRequestRobot($params, 'cross.base.pageFace');
    }

    /**
     * 2.1.8 修改人员信息.
     */
    public function updateRoboterFace($params = array())
    {
        return $this->commonRequestRobot($params, 'cross.base.updateFace');
    }

    /**
     * 2.1.9 删除人员信息.
     */
    public function deleteRoboterFace($params = array())
    {
        return $this->commonRequestRobot($params, 'cross.base.deleteFace');
    }

    /**
     * 2.2.1 查询机器人列表.
     */
    public function getRoboterList($params = array())
    {
        return $this->commonRequestRobot($params, 'cross.robot.getRobotList');
    }

    /**
     * 2.2.2 机器人运行数据查询.
     */
    public function getRobotInfo($params = array())
    {
        return $this->commonRequestRobot($params, 'cross.robot.getRobotInfo');
    }

    /**
     * 2.2.3 查询机器人事件列表.
     */
    public function getRobotEventList($params = array())
    {
        return $this->commonRequestRobot($params, 'cross.robot.getRobotEventList');
    }

    /**
     * 2.2.4 查询机器人告警列表.
     * 获取机器人告警列表。
     */
    public function getRobotAlarmList($params = array())
    {
        return $this->commonRequestRobot($params, 'cross.robot.getRobotAlarmList');
    }

    /**
     * 2.2.5 查询机器人业务点列表.
     * 查询机器人业务点列表.
     */
    public function getBizPointList($params = array())
    {
        return $this->commonRequestRobot($params, 'cross.robot.getBizPointList');
    }

    /**
     * 2.2.6 查询AI识别记录.
     * 查询AI识别记录.
     */
    public function getVisionLog($params = array())
    {
        return $this->commonRequestRobot($params, 'cross.robot.getVisionLog');
    }

    /**
     * 2.2.7 机器人指令下发.
     */
    public function doRobotAction($params = array())
    {
        return $this->commonRequestRobot($params, 'cross.robot.doRobotAction');
    }

    /**
     * 2.2.8 NLP下发.
     */
    public function doNlpIntent($params = array())
    {
        return $this->commonRequestRobot($params, 'cross.robot.doNlpIntent');
    }

    /**
     * 2.2.9 查询设备登录信息.
     * 查询设备登录信息。
     */
    public function queryRobotLoginInfo($params = array())
    {
        return $this->commonRequestRobot($params, 'cross.robot.queryRobotLoginInfo');
    }

    /**
     * 2.3.1 发起多点召唤任务.
     * 召唤机器人到指定目标点。
     */
    public function callRobot($params = array())
    {
        return $this->commonRequestRobot($params, 'cross.task.callRobot');
    }

    /**
     * 2.3.2 发起充电任务.
     * 发起充电任务。
     */
    public function taskCharge($params = array())
    {
        return $this->commonRequestRobot($params, 'cross.task.charge');
    }

    /**
     * 2.3.3 查询进行中的任务.
     * 查询进行中的任务。
     */
    public function getRoboterRunningTask($params = array())
    {
        return $this->commonRequestRobot($params, 'cross.task.getRunningTask');
    }

    /**
     * 2.3.4 查询历史任务列表.
     * 查询历史任务列表。
     */
    public function getRoboterHistoryTask($params = array())
    {
        return $this->commonRequestRobot($params, 'cross.task.getTaskList');
    }

    /**
     * 2.3.5 查询任务执行详情
     */
    public function getRoboterTaskTaskDetails($params = array())
    {
        return $this->commonRequestRobot($params, 'cross.task.getTaskExecuteDtl');
    }

    /**
     * 2.3.5 查询机器人任务计划.
     */
    public function getRoboterTaskPlan($params = array())
    {
        return $this->commonRequestRobot($params, 'cross.task.getTaskPlan');
    }

    /**
     * 2.4.1 订阅注册.
     */
    public function registerSubscribe($params = array())
    {
        return $this->commonRequestRobot($params, 'cross.message.subscribe');
    }

    /**
     * 2.5.1 机器人汇总数据统计.
     */
    public function getRobotSummary($params = array())
    {
        return $this->commonRequestRobot($params, 'cross.statistics.getRobotSummary');
    }

    /**
     * 2.5.2 按日统计机器人任务数量.
     */
    public function getRoboterDailyTask($params = array())
    {
        return $this->commonRequestRobot($params, 'cross.statistics.getDailyTask');
    }

    /**
     * 2.1.2 根据组织机构id查询详情
     */
    public function getRemoteBranchById($params = array())
    {
        return $this->commonRequestRobot($params, 'cross.base.getBranchById');
    }

    /**
     * 2.5.3 按机器人类型统计汇总数据.
     */
    public function getTaskSummary($params = array())
    {
        return $this->commonRequestRobot($params, 'cross.statistics.getTaskSummary');
    }

    /**
     * 任务取消
     * @param array $params
     * @return array|mixed
     */
    public function cancelTask($params = array())
    {
        return $this->commonRequestRobot($params, 'cross.task.cancel');
    }
}