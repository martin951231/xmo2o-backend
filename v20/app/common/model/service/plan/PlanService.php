<?php
/**
 * 计划任务
 * Author: hengtingmei
 * Date Time: 2020/07/01 17:48
 */

namespace app\common\model\service\plan;
use app\common\model\db\ProcessPlan;
use app\common\model\db\ProcessSubPlan;

class PlanService {

    public $processPlanModel = null;
    public function __construct()
    {
        $this->processPlanModel = new ProcessPlan();
    }
    /**
	 * 计划任务执行
	 * @param  string  $param 参数
     * @author 衡婷妹
     * @date 2020/07/02
	 */
    public function runTask($param)
    {
        // service namespace
        $servicePath = '\\app\common\model\service\plan\file\\'.$param['file'];
        $exportObj = new $servicePath;
        $res = $exportObj->runTask($param);

        return $res;
    }

    /**添加任务
     * @author 张涛
     * @date 2020/06/30
     */
    public function addTask($param, $isSub = 0)
    {
        if (!is_array($param) || empty($param)) {
            throw new \Exception("参数必须是数组");
        }
        if (!empty($param['param']) && !is_array($param['param'])) {
            throw new \Exception("任务参数 param 必须为空是数组");
        }

        $data['add_time'] = time();
        if (!empty($param['param'])) {
            $data['param'] = serialize($param['param']);
        }
        $param['plan_time'] = $param['plan_time'] ?? 0;
        if (empty($param['plan_time'])) {
            throw new \Exception("任务类别 plan_time 参数必填且是时间戳格式");
        }
        $data['plan_time'] = $param['plan_time'];
        $param['space_time'] = intval($param['space_time'] ?? 0);
        if (!empty($param['space_time'])) {
            $data['space_time'] = $param['space_time'];
        }
        if (!empty($param['file'])) {
            $data['file'] = $param['file'];
        }
        if (!empty($param['url'])) {
            $data['url'] = $param['url'];
        }
        if (isset($param['unique_id']) && !empty($param['unique_id'])) {
            $data['unique_id'] = $param['unique_id'];
        }
        if ($isSub == 1) {
			$data['rand_number'] = mt_rand(1, max(cfg('sub_process_num'), 3));
            $id = (new ProcessSubPlan())->add($data);
            fdump_api(['id'=>$id,'data'=>$data],'000addTask',1);
        } else {
            $id = (new ProcessPlan())->add($data);
        }
        if ($id) {
            return true;
        } else {
            throw new \Exception("任务添加失败，请重试");
        }
    }

    /**
     * 删除任务
     * @param $id
     * @author 张涛
     * @date 2020/06/30
     */
    public function delTask($id)
    {
        if ($id < 1) {
            throw new \Exception("参数有误");
        }
        $rs = (new ProcessPlan())->where('id', $id)->delete();
        return boolval($rs);
    }

    /**
     * 根据条件获取一条记录
     * @param $where array
     * @author 衡婷妹
     * @date 2020/07/04
     * @return array
     */
    public function getOne($where) {
        if(empty($where)){
            return [];
        }

        $detail = $this->processPlanModel->getOne($where);
        if(!$detail) {
            return [];
        }

        return $detail->toArray();
    }


    /**
     * 根据条件获取多条记录
     * @param $where array
     * @author 衡婷妹
     * @date 2020/07/04
     * @return array
     */
    public function getSome($where) {
        if(empty($where)){
            return [];
        }

        $list = $this->processPlanModel->getSome($where);
        if(!$list) {
            return [];
        }

        return $list->toArray();
    }
	
	/**
     * 重构子计划任务中的 rand_number 字段，一般用于后台变更过子计划任务数量参数
     * @param $where array
     * @author 彭家庭
     * @date 2020/07/04
     * @return array
     */
    public function saveSubPlanRandNumber() {
		
		//目前无法评估强行变更可能会导致计划任务紊乱多次执行的风险，后续再看情况。
		(new ProcessSubPlan())->execute('UPDATE `pigcms_process_sub_plan` SET `rand_number` = FLOOR(1 + RAND( ) *3)');
		
        return true;
    }
}