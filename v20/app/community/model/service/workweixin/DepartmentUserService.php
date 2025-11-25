<?php


namespace app\community\model\service\workweixin;


use app\community\model\db\HouseEnterpriseWxBind;
use app\community\model\db\HouseWorker;
use app\community\model\db\PropertyGroup;
use app\community\model\db\workweixin\WorkWeixinDepartment;
use app\community\model\service\HouseWorkerService;
use app\consts\WorkWeiXinConst;

use app\traits\WorkWeiXinToJobTraits;
use app\traits\house\WorkWeiXinTraits;

class DepartmentUserService
{
    use WorkWeiXinToJobTraits;
    use WorkWeiXinTraits;
    public $now_time, $sWorkWeiXinSuiteService, $dbPropertyGroup, $dbHouseWorker;
    public $timeLimit = 40;// 执行任务花费时间 单位秒
    public function __construct()
    {
        $this->sWorkWeiXinSuiteService = new WorkWeiXinSuiteService();
        $judgeConfig = $this->sWorkWeiXinSuiteService->judgeConfig();
        if (!$judgeConfig) {
            return [
                'code'    => 1001,
                'message' => WorkWeiXinConst::WORK_WEI_XIN_SUITE_CONFIG_ERR_TIP,
            ];
        }
        $this->now_time = time();
    }

    /**
     * 获取企业微信那边数据
     * @param $property_id
     * @param int $village_id
     * @return array
     */
    public function getDepartment($property_id, $village_id = 0) {
        if (!$property_id) {
            return [
                'code'    => 1001,
                'message' => '缺少物业ID',
            ];
        }
        $bindInfo = $this->filterHouseEnterpriseWxBind($property_id, $village_id);
        // todo 检查下是否获取过组织架构  否则先行执行 获取逻辑 
        $db_work_weixin_department = new WorkWeixinDepartment();
        $whereDepartment = [];
        $whereDepartment[] = ['from',    '=', WorkWeiXinConst::FROM_PROPERTY];
        $whereDepartment[] = ['from_id', '=', $property_id];
        $whereDepartment[] = ['corpid',  '=', $bindInfo['corpid']];
        $count = $db_work_weixin_department->getCount($whereDepartment);
        fdump_api(['msg' => '获取企业微信那边数据', 'line' => __LINE__, 'property_id' => $property_id, 'count' => $count, 'bindInfo' => $bindInfo], 'qiYeWeiXin/businessSynQwLog', 1);
        if (! $count) {
            $queueData = [
                'property_id'       => $property_id,
                'village_id'        => $village_id,
                'syncPropertyGroup' => 1,
                'jobType'           => 'getWorkWeiXinDepartmentList',
            ];
            try{
                $job_id = $this->traitCommonWorkWeiXin($queueData);
            }catch (\Exception $e){
                return [
                    'code'    => 1001,
                    'message' => $e->getMessage(),
                ];
            }
            if ($job_id == -1) {
                return [
                    'code'    => 1001,
                    'message' => '任务正在等候执行',
                ];
            }
            // 同步指令下发
            return [
                'code'    => 0,
                'message' => '同步指令下发,等待执行',
            ];
        } else {
            $arr = $this->syncPropertyGroupToWorkWeiXin($property_id, $village_id);
            $queueData = [
                'property_id'       => $property_id,
                'syncPropertyGroup' => 0,
                'jobType'           => 'getWorkWeiXinDepartmentList',
            ];
            $this->traitCommonWorkWeiXin($queueData, 300);
            return $arr;
        }
    }

    /**
     * 同步组织架构到企业微信
     * @param $property_id
     * @param $village_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function syncPropertyGroupToWorkWeiXin($property_id, $village_id) {
        // todo 同步物业组织架构到企业微信
        fdump_api(['msg' => '同步组织架构到企业微信', 'line' => __LINE__, 'property_id' => $property_id, 'village_id' => $village_id], 'qiYeWeiXin/businessSynQwLog', 1);
        if (!$property_id) {
            return [
                'code'    => 1001,
                'message' => '缺少物业ID',
            ];
        }
        $bindInfo = $this->filterHouseEnterpriseWxBind($property_id, $village_id);
        if (isset($bindInfo['PlaintextCorpId']) && $bindInfo['PlaintextCorpId']) {
            $corpid = $bindInfo['PlaintextCorpId'];
        } elseif (isset($bindInfo['corpid']) && $bindInfo['corpid']) {
            $corpid = $bindInfo['corpid'];
        }
        if (!isset($bindInfo['pigcms_id']) || !isset($corpid)) {
            fdump_api(['msg' => '物业不存在授权企业微信', 'line' => __LINE__, 'bindInfo' => $bindInfo], 'qiYeWeiXin/businessSynQwLog', 1);
            return [
                'code'    => 1001,
                'message' => '物业不存在授权企业微信',
            ];
        }
        if (!$this->dbPropertyGroup) {
            $this->dbPropertyGroup = new PropertyGroup();
        }
        $whereGroup = [];
        $whereGroup[] = ['property_id', '=', $property_id];
        $whereGroup[] = ['is_del',      '=', 0];
        $whereGroup[] = ['status',      '=', 1];
        if ($village_id) {
            $whereGroup[] = ['village_id', '=', $village_id];
        }
        $group_list = $this->dbPropertyGroup->getList($whereGroup, true, 0, 0, 'fid ASC, id ASC');
        if ($group_list && !is_array($group_list)) {
            $group_list = $group_list->toArray();
        }
        $fidArr = $this->dbPropertyGroup->getColumn($whereGroup, 'id, qy_corpid, create_id', 'id');
        foreach ($group_list as $key => $item) {
            // todo 走队列 执行 组织下发
            $qy_corpid = trim($item['qy_corpid']);
            if ($item['create_status'] == 4 && $item['is_del'] == 1 && $item['is_del'] == 1 && isset($item['qy_corpid']) && $corpid == $qy_corpid) {
                // todo 本地删除了 对应企业微信也同步删除了 不做后续处理
                fdump_api(['msg' => '本地删除了 对应企业微信也同步删除了 不做后续处理', 'line' => __LINE__, 'item' => $item], 'qiYeWeiXin/businessSynQwLog', 1);
                continue;
            }
            if ($item['is_del'] == 1 && !$item['create_id']) {
                // todo 本地删除了 对应企业微信没有同步 也不用处理
                fdump_api(['msg' => '本地删除了 对应企业微信也同步删除了 不做后续处理', 'line' => __LINE__, 'item' => $item], 'qiYeWeiXin/businessSynQwLog', 1);
                continue;
            }
            $operationParam = [
                'jobType'           => 'syncDepartmentToWorkWeiXin',
                'property_id'       => $item['property_id'],
                'village_id'        => $item['village_id'],
                'property_group_id' => $item['id'],
                'fid'               => $item['fid'],
                'name'              => $item['name'],
                'type'              => $item['type'],
                'status'            => $item['status'],
                'sort'              => $item['sort'],
                'is_del'            => $item['is_del'],
                'create_id'         => $item['create_id'],
                'qy_corpid'         => $qy_corpid,
                'corpid'            => $corpid,
            ];
            if ($item['fid'] == 0) {
                $fid_qy_corpid = $corpid;
                $fid_create_id = 1;
            } elseif (isset($fidArr[$item['fid']]) && $fidArr[$item['fid']]) {
                $fid_qy_corpid = $fidArr[$item['fid']]['qy_corpid'];
                $fid_create_id = $fidArr[$item['fid']]['create_id'];
            } else {
                $fid_qy_corpid = '';
                $fid_create_id = '';
            }
            $operationParam['fid_qy_corpid'] = $fid_qy_corpid;
            $operationParam['fid_create_id'] = $fid_create_id;
            $time = $key * 2;
            fdump_api(['msg' => '同步组织架构到企微队列', 'line' => __LINE__, 'operationParam' => $operationParam, 'time' => $time], 'qiYeWeiXin/businessSynQwLog', 1);
            $this->traitCommonWorkWeiXin($operationParam, $time);
        }
        return [
            'code'    => 0,
            'message' => '同步指令下发,等待执行',
        ];
    }

    /**
     * 同步单个组织架构
     * @param $param
     * @return array|bool
     */
    public function syncSingleGroupToWorkWeiXin($param) {
        fdump_api(['msg' => '同步单个组织架构', 'line' => __LINE__, 'param' => $param], 'qiYeWeiXin/businessSynQwLog', 1);
        $this->filterDepartment($param);
        $this->property_group_id = isset($param['id'])            && $param['id']            ? $param['id']                    : 0;
        if (!$this->property_group_id) {
            fdump_api(['msg' => '缺少部门ID', 'line' => __LINE__, 'param' => $param], 'qiYeWeiXin/businessSynQwLog', 1);
            return [
                'code'    => 1001,
                'message' => '缺少部门ID',
            ];
        }
        if (!$this->dbPropertyGroup) {
            $this->dbPropertyGroup = new PropertyGroup();
        }
        $whereGroup = [];
        $whereGroup[] = ['id', '=', $this->property_group_id];
        $group = $this->dbPropertyGroup->getOne($whereGroup);
        if ($group && !is_array($group)) {
            $group = $group->toArray();
        }
        if (!isset($group['id']) || !$group) {
            fdump_api(['msg' => '对应部门不存在或者已经被删除', 'line' => __LINE__, 'whereGroup' => $whereGroup, 'group' => $group, 'param' => $param], 'qiYeWeiXin/businessSynQwLog', 1);
            return [
                'code'    => 1001,
                'message' => '对应部门不存在或者已经被删除',
            ];
        }
        if (!$this->property_id) {
            $this->property_id = $group['property_id'];
        }
        $bindInfo = $this->filterHouseEnterpriseWxBind($this->property_id);
        if (isset($bindInfo['PlaintextCorpId']) && $bindInfo['PlaintextCorpId']) {
            $corpid = $bindInfo['PlaintextCorpId'];
        } elseif (isset($bindInfo['corpid']) && $bindInfo['corpid']) {
            $corpid = $bindInfo['corpid'];
        }
        if (!isset($bindInfo['pigcms_id']) || !isset($corpid)) {
            fdump_api(['msg' => '物业不存在授权企业微信', 'line' => __LINE__, 'property_id' => $this->property_id, 'bindInfo' => $bindInfo, 'param' => $param], 'qiYeWeiXin/businessSynQwLog', 1);
            return [
                'code'    => 1001,
                'message' => '物业不存在授权企业微信',
            ];
        }
        $qy_corpid = trim($group['qy_corpid']);
        if ($group['create_status'] == 4 && $group['is_del'] == 1 && $group['is_del'] == 1 && isset($group['qy_corpid']) && $corpid == $qy_corpid) {
            // todo 本地删除了 对应企业微信也同步删除了 不做后续处理
            fdump_api(['msg' => '本地删除了 对应企业微信也同步删除了 不做后续处理', 'line' => __LINE__, 'group' => $group, 'bindInfo' => $bindInfo, 'param' => $param], 'qiYeWeiXin/businessSynQwLog', 1);
            return true;
        }
        if ($group['is_del'] == 1 && !$group['create_id']) {
            // todo 本地删除了 对应企业微信没有同步 也不用处理
            fdump_api(['msg' => '本地删除了 对应企业微信没有同步 也不用处理', 'line' => __LINE__, 'group' => $group, 'bindInfo' => $bindInfo, 'param' => $param], 'qiYeWeiXin/businessSynQwLog', 1);
            return true;
        }
        if (isset($group['fid']) && $group['fid']) {
            $whereFidGroup = [];
            $whereFidGroup[] = ['id', '=', $group['fid']];
            $fidInfo = $this->dbPropertyGroup->getOne($whereFidGroup, 'id, qy_corpid, create_id', 'id');
            if ($fidInfo && !is_array($fidInfo)) {
                $fidInfo = $fidInfo->toArray();
            }
        }
        $operationParam = [
            'jobType'           => 'syncDepartmentToWorkWeiXin',
            'property_id'       => $group['property_id'],
            'village_id'        => $group['village_id'],
            'property_group_id' => $group['id'],
            'fid'               => $group['fid'],
            'name'              => $group['name'],
            'type'              => $group['type'],
            'status'            => $group['status'],
            'sort'              => $group['sort'],
            'is_del'            => $group['is_del'],
            'create_id'         => $group['create_id'],
            'qy_corpid'         => $qy_corpid,
            'corpid'            => $corpid,
        ];
        if ($group['fid'] == 0) {
            $fid_qy_corpid = $corpid;
            $fid_create_id = 1;
        } elseif (isset($fidInfo) && $fidInfo) {
            $fid_qy_corpid = $fidInfo['qy_corpid'];
            $fid_create_id = $fidInfo['create_id'];
        } else {
            $fid_qy_corpid = '';
            $fid_create_id = '';
        }
        $operationParam['fid_qy_corpid'] = $fid_qy_corpid;
        $operationParam['fid_create_id'] = $fid_create_id;
        fdump_api(['msg' => '同步组织下人员信息至企微', 'line' => __LINE__, 'operationParam' => $operationParam, 'param' => $param], 'qiYeWeiXin/businessSynQwLog', 1);
        $job_id = $this->traitCommonWorkWeiXin($operationParam);
        // todo 测试 展示直接调用
//        unset($operationParam['jobType']);
//        $msg = $this->syncDepartmentToWorkWeiXin($operationParam);
        return $job_id;
    }
    
    /** @var int property_id 表 property_group 中 property_id 物业id */
    protected $property_id;
    /** @var int village_id 表 property_group 中 village_id 小区id */
    protected $village_id;
    /** @var int syncPropertyGroup 标记是否进行 本地组织架构同步企业微信 */
    protected $syncPropertyGroup;
    /** @var string corpid 企业id或者应用id */
    protected $corpid;
    protected function filterDepartment($param) {
        $this->property_id       = isset($param['property_id'])       && $param['property_id']       ? $param['property_id']       : 0;
        $this->village_id        = isset($param['village_id'])        && $param['village_id']        ? $param['village_id']        : 0;
        $this->syncPropertyGroup = isset($param['syncPropertyGroup']) && $param['syncPropertyGroup'] ? $param['syncPropertyGroup'] : 0;
        $this->corpid            = isset($param['corpid'])            && $param['corpid']            ? trim($param['corpid'])      : '';
    }

    /** @var int property_group_id 表 property_group 中 id */
    protected $property_group_id;
    /** @var int 组织父级id */
    protected $fid;
    /** @var string 组织名称 */
    protected $name;
    /** @var int 类型  0是小区 1 小区部门 2物业部门 99 物业 */
    protected $type;
    /** @var int 类型  1 正常  */
    protected $status;
    /** @var int 排序 数值越大越靠前 */
    protected $sort;
    /** @var int 是否删除 1是 0否 */
    protected $is_del;
    /** @var int 企业微信对应创建的id */
    protected $create_id;
    /** @var int 同步到的企业的企业ID */
    protected $qy_corpid;
    /** @var int 父级同步到的企业的企业ID */
    protected $fid_qy_corpid;
    /** @var int 父级企业微信对应创建的id */
    protected $fid_create_id;
    /** @var int 子级组织的id */
    protected $child_id;
    /** @var bool 是否2次执行 */
    protected $repeat;
    protected function filterDepartmentOperation($param) {
        $this->property_group_id = isset($param['property_group_id']) && $param['property_group_id'] ? $param['property_group_id']     : 0;
        $this->fid               = isset($param['fid'])               && $param['fid']               ? $param['fid']                   : 0;
        $this->name              = isset($param['name'])              && $param['name']              ? $this->checkStr($param['name']) : '';
        $this->type              = isset($param['type'])              && $param['type']              ? $param['type']                  : 0;
        $this->status            = isset($param['status'])            && $param['status']            ? $param['status']                : 0;
        $this->sort              = isset($param['sort'])              && $param['sort']              ? $param['sort']                  : 0;
        $this->is_del            = isset($param['is_del'])            && $param['is_del']            ? $param['is_del']                : 0;
        $this->create_id         = isset($param['create_id'])         && $param['create_id']         ? $param['create_id']             : 0;
        $this->qy_corpid         = isset($param['qy_corpid'])         && $param['qy_corpid']         ? $param['qy_corpid']             : '';
        $this->fid_qy_corpid     = isset($param['fid_qy_corpid'])     && $param['fid_qy_corpid']     ? $param['fid_qy_corpid']         : '';
        $this->fid_create_id     = isset($param['fid_create_id'])     && $param['fid_create_id']     ? $param['fid_create_id']         : '';
        $this->child_id          = isset($param['child_id'])          && $param['child_id']          ? $param['child_id']              : 0;
        $this->repeat            = isset($param['repeat'])            && $param['repeat']            ? $param['repeat']                : false;
    }

    /**
     * 获取企业微信组织架构
     * @param $param
     * @return bool
     */
    public function getWorkWeiXinDepartmentList($param) {
        fdump_api(['msg' => '获取企业微信组织架构', 'line' => __LINE__, 'param' => $param], 'qiYeWeiXin/businessSynQwLog', 1);
        $this->filterDepartment($param);
        $listParam = [
            'property_id' => $this->property_id,
            'village_id'  => $this->village_id,
        ];
        $startTime = microtime(true);
        $this->sWorkWeiXinSuiteService->getDepartmentSimpleList($listParam);
        $endTime = microtime(true);
        $time = $endTime - $startTime;
        if ($this->timeLimit > $time  && $this->syncPropertyGroup) {
            $this->syncPropertyGroupToWorkWeiXin($this->property_id, $this->village_id);
        } elseif ($this->syncPropertyGroup) {
            // todo 走队列
            $queueData = [
                'property_id' => $this->property_id,
                'village_id'  => $this->village_id,
                'jobType'     => 'syncPropertyGroupToWorkWeiXin',
            ];
            $this->traitCommonWorkWeiXin($queueData);
        }
        return true;
    }

    /**
     * 同步组织架构到企业微信
     * @param $param
     * @return bool
     */
    public function syncDepartmentToWorkWeiXin($param) {
        fdump_api(['msg' => '同步组织架构到企业微信', 'line' => __LINE__, 'param' => $param], 'qiYeWeiXin/businessSynQwLog', 1);
        $this->filterDepartment($param);
        $this->filterDepartmentOperation($param);
        if (!$this->corpid) {
            $bindInfo = $this->filterHouseEnterpriseWxBind($this->property_id, $this->village_id);
            if (isset($bindInfo['PlaintextCorpId']) && $bindInfo['PlaintextCorpId']) {
                $this->corpid = $bindInfo['PlaintextCorpId'];
            } elseif (isset($bindInfo['corpid']) && $bindInfo['corpid']) {
                $this->corpid = $bindInfo['corpid'];
            }
            fdump_api(['msg' => '同步组织架构到企业微信', 'line' => __LINE__, 'bindInfo' => $bindInfo,'property_id' => $this->property_id, 'village_id' => $this->village_id], 'qiYeWeiXin/businessSynQwLog', 1);
        }
        if (!$this->dbPropertyGroup) {
            $this->dbPropertyGroup = new PropertyGroup();
        }
        $wherePropertyGroup = [];
        $wherePropertyGroup[] = ['id', '=', $this->property_group_id];
        if (!$this->is_del && $this->fid && !$this->fid_create_id) {
            // todo 非删除 且存在父级 不存在父级对应企业微信id 查询下
            fdump_api(['msg' => '删除 且存在父级 不存在父级对应企业微信id 查询下', 'line' => __LINE__, 'param' => $param], 'qiYeWeiXin/businessSynQwLog', 1);
            $sync = $this->syncFidDepartmentToWorkWeiXin();
            if (!$sync) {
                return false;
            }
            fdump_api(['msg' => '父级', 'line' => __LINE__, 'sync' => $sync], 'qiYeWeiXin/businessSynQwLog', 1);
        } elseif ($this->fid == 0 && !$this->fid_create_id) {
            $this->fid_create_id = 1; // 根目录id
        }
        $paramOperation = [
            'property_id' => $this->property_id,
            'village_id'  => $this->village_id,
            'corpid'      => $this->corpid,
            'parentid'    => $this->fid_create_id,
            'name'        => $this->name,
            'order'       => $this->sort,
        ];
        $sWorkWeiXinSuiteService = new WorkWeiXinSuiteService();
        $dataSave = [];
        if ($this->is_del) {
            // 已经删除的组织同步企业微信删除
            // 没有同步过 直接记录同步删除成功
            $delSave = [
                'del_time'      => $this->now_time,
                'create_status' => 4,
                'qy_corpid'     => $this->corpid,
            ];
            if ($this->create_id) {
                $paramOperation['id']             = $this->create_id;
                $paramOperation['operation_type'] = 'delete';
                fdump_api(['msg' => '同步删除', 'line' => __LINE__, 'del' => $this->is_del, 'paramOperation' => $paramOperation], 'qiYeWeiXin/businessSynQwLog', 1);
                $result = $sWorkWeiXinSuiteService->departmentOperation($paramOperation);
                fdump_api(['msg' => '同步删除-结果', 'line' => __LINE__, 'result' => $result], 'qiYeWeiXin/businessSynQwLog', 1);
                if (isset($result['code']) && $result['code']) {
                    // 报错更改记录
                    $delSave = [
                        'qy_reasons'    => $result['message']."【同步删除失败】",
                        'create_status' => 2,
                        'qy_corpid'     => $this->corpid,
                    ];
                    $delSave['qy_code'] = $result['code'];
                }
            }
            $this->dbPropertyGroup->editFind($wherePropertyGroup, $delSave);
            return true;
        } elseif ($this->create_id && $this->qy_corpid == $this->corpid) {
            // todo 同步过且是同一个企业微信 走更新逻辑
            $paramOperation['id']             = $this->create_id;
            $paramOperation['operation_type'] = 'update';
            fdump_api(['msg' => '同步过且是同一个企业微信走更新逻辑', 'line' => __LINE__, 'param' => $param, 'del' => $this->create_id, 'qy_corpid' => $this->qy_corpid, 'corpid' => $this->corpid, 'paramOperation' => $paramOperation], 'qiYeWeiXin/businessSynQwLog', 1);
            $result = $sWorkWeiXinSuiteService->departmentOperation($paramOperation);
            fdump_api(['msg' => '更新-结果', 'line' => __LINE__, 'param' => $param, 'del' => $this->create_id, 'qy_corpid' => $this->qy_corpid, 'corpid' => $this->corpid, 'paramOperation' => $paramOperation], 'qiYeWeiXin/businessSynQwLog', 1);
            $dataSave['update_time']   = time();
        } else {
            // todo 其他情况走添加
            if (!$this->create_id && $this->property_group_id) {
                $this->create_id = $this->property_group_id;
            }
            $paramOperation['id']             = $this->create_id;
            $paramOperation['operation_type'] = 'create';
            fdump_api(['msg' => '其他情况走添加', 'line' => __LINE__, 'paramOperation' => $paramOperation], 'qiYeWeiXin/businessSynQwLog', 1);
            $result = $sWorkWeiXinSuiteService->departmentOperation($paramOperation);
            fdump_api(['msg' => '其他情况走添加-结果', 'line' => __LINE__, 'result' => $result], 'qiYeWeiXin/businessSynQwLog', 1);
            $dataSave['create_time']   = time();
        }
        if (isset($result['id'])) {
            $dataSave['create_id'] = $result['id'];
        }
        if (isset($result['name'])) {
            $dataSave['qy_name'] = $result['name'];
        }
        if (isset($result['code']) && $result['code']) {
            // 报错更改记录
            $dataSave['qy_reasons']    = $result['message'];
            $dataSave['create_status'] = 2;
            $dataSave['qy_corpid']     = $this->corpid;
            $dataSave['qy_code']       = $result['code'];
        } else {
            // 报错更改记录
            $dataSave['qy_reasons']    = '';
            $dataSave['create_status'] = 1;
            $dataSave['qy_corpid']     = $this->corpid;
            $dataSave['qy_code']       = '';
        }
        $this->dbPropertyGroup->editFind($wherePropertyGroup, $dataSave);
        if ($this->child_id && $dataSave['create_status'] == 1) {
            // todo 父级同步成功 存在子级同步下子级
            fdump_api(['msg' => '父级同步成功存在子级同步下子级', 'line' => __LINE__, 'dataSave' => $dataSave], 'qiYeWeiXin/businessSynQwLog', 1);
            $this->syncChildDepartmentToWorkWeiXin($dataSave['create_id']);
        }
        if (!isset($dataSave['create_id']) && isset($paramOperation['id']) && $paramOperation['id'] && isset($paramOperation['operation_type']) && $paramOperation['operation_type'] === 'update') {
            $dataSave['create_id'] = $paramOperation['id'];
        }
        if (isset($dataSave['create_id']) && $dataSave['create_id']) {
            // todo 下发对应部门人员信息
            fdump_api(['msg' => '下发对应部门人员信息', 'line' => __LINE__, 'dataSave' => $dataSave], 'qiYeWeiXin/businessSynQwLog', 1);
            $operationParam = [
                'jobType'           => 'syncDepartmentUsersToWorkWeiXin',
                'property_id'       => $this->property_id,
                'village_id'        => $this->village_id,
                'property_group_id' => $this->property_group_id,
                'department_id'     => $dataSave['create_id'],
                'corpid'            => $this->corpid,
            ];
            fdump_api(['msg' => '下发对应部门人员信息-队列', 'line' => __LINE__, 'operationParam' => $operationParam], 'qiYeWeiXin/businessSynQwLog', 1);
            $this->traitCommonWorkWeiXin($operationParam);
//            $paramDepartmentUsers = [
//                'property_id'       => $this->property_id,
//                'village_id'        => $this->village_id,
//                'property_group_id' => $this->property_group_id,
//                'department_id'     => $dataSave['create_id'],
//                'corpid'            => $this->corpid,
//            ];
//            $this->syncDepartmentUsersToWorkWeiXin($paramDepartmentUsers);
        }
        return true;
    }

    /**
     * 处理父级组织
     * @return bool
     */
    protected function syncFidDepartmentToWorkWeiXin() {
        if (!$this->dbPropertyGroup) {
            $this->dbPropertyGroup = new PropertyGroup();
        }
        $wherePropertyGroup = [];
        $wherePropertyGroup[] = ['id', '=', $this->property_group_id];
        $whereFid = [];
        $whereFid[] = ['id', '=', $this->fid];
        $fidInfo = $this->dbPropertyGroup->getOne($whereFid);
        if ($fidInfo && !is_array($fidInfo)) {
            $fidInfo = $fidInfo->toArray();
        }
        fdump_api(['msg' => '处理父级组织', 'line' => __LINE__, 'fidInfo' => $fidInfo], 'qiYeWeiXin/businessSynQwLog', 1);
        if (empty($fidInfo) || (isset($fidInfo['is_del']) && $fidInfo['is_del'])) {
            // todo 父级本地不存在已经删除了直接反馈记录错误
            $dataSave = [
                'qy_reasons'    => '父级组织不存在或者已经删除了',
                'create_status' => 2,
                'qy_corpid'     => $this->corpid,
            ];
            fdump_api(['msg' => '父级组织不存在或者已经删除了', 'line' => __LINE__, 'fidInfo' => $fidInfo], 'qiYeWeiXin/businessSynQwLog', 1);
            $this->dbPropertyGroup->editFind($wherePropertyGroup, $dataSave);
            return false;
        }
        if (isset($fidInfo['create_id']) && $fidInfo['create_id']) {
            $this->fid_create_id = $fidInfo['create_id'];
        } elseif(!$this->child_id && !$this->repeat){
            // todo 父级不存在 且不是2次触发的下发 先添加下父级
            $operationParam = [
                'jobType'           => 'syncDepartmentToWorkWeiXin',
                'property_id'       => $this->property_id,
                'village_id'        => $this->village_id,
                'property_group_id' => $fidInfo['id'],
                'fid'               => $fidInfo['fid'],
                'name'              => $fidInfo['name'],
                'type'              => $fidInfo['type'],
                'status'            => $fidInfo['status'],
                'sort'              => $fidInfo['sort'],
                'is_del'            => $fidInfo['is_del'],
                'create_id'         => $fidInfo['create_id'],
                'qy_corpid'         => $this->qy_corpid,
                'corpid'            => $this->corpid,
                'child_id'          => $this->property_group_id,
            ];
            fdump_api(['msg' => '父级不存在 且不是2次触发的下发 先添加下父级', 'line' => __LINE__, 'fidInfo' => $fidInfo], 'qiYeWeiXin/businessSynQwLog', 1);
            $this->traitCommonWorkWeiXin($operationParam);
            return false;
        } else {
            $dataSave = [
                'qy_reasons'    => '缺少已经同步企业微信的父级组织',
                'create_status' => 2,
                'qy_corpid'     => $this->corpid,
            ];
            fdump_api(['msg' => '缺少已经同步企业微信的父级组织', 'line' => __LINE__, 'fidInfo' => $fidInfo], 'qiYeWeiXin/businessSynQwLog', 1);
            $this->dbPropertyGroup->editFind($wherePropertyGroup, $dataSave);
            return false;
        }
        if (isset($fidInfo['qy_corpid']) && $fidInfo['qy_corpid']) {
            $this->fid_qy_corpid = $fidInfo['qy_corpid'];
        }
        return true;
    }

    /**
     * 处理子级组织
     * @param $fid_create_id
     * @return bool
     */
    protected function syncChildDepartmentToWorkWeiXin($fid_create_id) {
        fdump_api(['msg' => '处理子级组织', 'line' => __LINE__, 'fid_create_id' => $fid_create_id], 'qiYeWeiXin/businessSynQwLog', 1);
        if (!$this->dbPropertyGroup) {
            $this->dbPropertyGroup = new PropertyGroup();
        }
        $whereChild = [];
        $whereChild[] = ['id', '=', $this->child_id];
        $childInfo = $this->dbPropertyGroup->getOne($whereChild);
        if ($childInfo && !is_array($childInfo)) {
            $childInfo = $childInfo->toArray();
        }
        if ($childInfo) {
            $operationParam = [
                'jobType'           => 'syncDepartmentToWorkWeiXin',
                'property_id'       => $this->property_id,
                'village_id'        => $this->village_id,
                'property_group_id' => $childInfo['id'],
                'fid'               => $childInfo['fid'],
                'name'              => $childInfo['name'],
                'type'              => $childInfo['type'],
                'status'            => $childInfo['status'],
                'sort'              => $childInfo['sort'],
                'is_del'            => $childInfo['is_del'],
                'create_id'         => $childInfo['create_id'],
                'qy_corpid'         => $this->qy_corpid,
                'corpid'            => $this->corpid,
                'fid_qy_corpid'     => $this->corpid,
                'fid_create_id'     => $fid_create_id,
                'repeat'            => true,
            ];
            fdump_api(['msg' => '处理子级组织队列', 'line' => __LINE__, 'operationParam' => $operationParam], 'qiYeWeiXin/businessSynQwLog', 1);
            $this->traitCommonWorkWeiXin($operationParam);
            return true;
        }
    }
    
    /** @var bool 企业微信方部门id */
    protected $department_id;
    protected function filterDepartmentUsers($param) {
        $this->property_group_id = isset($param['property_group_id']) && $param['property_group_id'] ? $param['property_group_id'] : 0;
        $this->department_id     = isset($param['department_id'])     && $param['department_id']     ? $param['department_id']     : 0;
        $this->property_id       = isset($param['property_id'])       && $param['property_id']       ? $param['property_id']       : 0;
        $this->village_id        = isset($param['village_id'])        && $param['village_id']        ? $param['village_id']        : 0;
        $this->corpid            = isset($param['corpid'])            && $param['corpid']            ? trim($param['corpid'])      : '';
    }

    /**
     * 同步 物业 小区 部门下人员信息到企业微信
     * @param $param
     */
    public function syncDepartmentUsersToWorkWeiXin($param) {
        $this->filterDepartmentUsers($param);
        $whereWork = [];
        $whereWork[] = ['property_id', '=', $this->property_id];
        if ($this->village_id) {
            $whereWork[] = ['village_id', '=', $this->village_id];
        }
        if ($this->property_group_id) {
            $whereWork[] = ['department_id', '=', $this->property_group_id];
        }
        if (!$this->dbHouseWorker) {
            $this->dbHouseWorker = new HouseWorker();
        }
        $workList = $this->dbHouseWorker->getWorkLists($whereWork);
        if ($workList && !is_array($workList)) {
            $workList = $workList->toArray();
        }
        $sHouseWorkerService = new HouseWorkerService();
        $worker_name_arr = $sHouseWorkerService->worker_name;
        foreach ($workList as $work) {
            usleep(50000);//休眠50毫秒
            $queueData = [
                'jobType'           => 'syncWorkUserToWorkWeiXin',
                'property_group_id' => $this->property_group_id,
                'department_id'     => $this->department_id,
                'property_id'       => $this->property_id,
                'village_id'        => $this->village_id,
                'corpid'            => $this->corpid,
                'mobile'            => $work['phone'],
                'name'              => $work['name'],
                'type'              => $work['type'],
                'position'          => isset($worker_name_arr[$work['type']]) ? $worker_name_arr[$work['type']] : '',
                'gender'            => $work['gender'],
                'enable'            => $work['status'] == 1 ? 1 : 0,
                'wid'               => $work['wid'],
                'is_del'            => $work['is_del'],
                'qy_status'         => $work['qy_status'],
                'qy_corpid'         => $work['qy_corpid'],
            ];
            if ($work['qy_id']) {
                $queueData['userid'] = $work['qy_id'];
            } else {
                $queueData['userid'] = 'worker_'.$work['wid'];
            }
            fdump_api(['msg' => '部门下人员信息到企业微信-队列', 'line' => __LINE__, 'queueData' => $queueData], 'qiYeWeiXin/businessSynQwLog', 1);
            $this->traitCommonWorkWeiXin($queueData);
        }
        
    }

    /** @var string userid 成员UserID */
    protected $userid;
    /** @var string mobile 手机号码。企业内必须唯一，mobile/email二者不能同时为空 */
    protected $mobile;
    /** @var string work_name 成员名称。长度为1~64个utf8字符 */
    protected $work_name;
    /** @var string work_type 0 客服专员 1 维修技工 2 物业人员 3 保洁人员 4 保安人员 5 招商开发 6 财务 7 人力资源 8 管理决策 9 后勤 99 其他' */
    protected $work_type;
    /** @var string position 职务信息' */
    protected $position;
    /** @var string gender 性别。1表示男性，2表示女性 */
    protected $gender;
    /** @var string enable 启用/禁用成员。1表示启用成员，0表示禁用成员 */
    protected $enable;
    /** @var string wid 工作人员id */
    protected $wid;
    /** @var string qy_status 创建状态 0 未同步或者未创建 1 同步或者创建了 2同步失败  4 同步删除了 */
    protected $qy_status;
    protected function filterWorkUsers($param) {
        $this->userid    = isset($param['userid'])    && $param['userid']    ? $param['userid']                : 0;
        $this->mobile    = isset($param['mobile'])    && $param['mobile']    ? $param['mobile']                : 0;
        $this->work_name = isset($param['name'])      && $param['name']      ? $this->checkStr($param['name']) : 0;
        $this->work_type = isset($param['type'])      && $param['type']      ? $param['type']                  : 0;
        $this->position  = isset($param['position'])  && $param['position']  ? $param['position']              : '';
        $this->gender    = isset($param['gender'])    && $param['gender']    ? $param['gender']                : 1;
        $this->enable    = isset($param['enable'])    && $param['enable']    ? $param['enable']                : 1;
        $this->wid       = isset($param['wid'])       && $param['wid']       ? $param['wid']                   : 0;
        $this->is_del    = isset($param['is_del'])    && $param['is_del']    ? $param['is_del']                : 0;
        $this->qy_corpid = isset($param['qy_corpid']) && $param['qy_corpid'] ? $param['qy_corpid']             : '';
        $this->qy_status = isset($param['qy_status']) && $param['qy_status'] ? $param['qy_status']             : '';
    }

    /**
     * 同步工作人员到企业微信
     * @param $param
     * @return bool
     */
    public function syncWorkUserToWorkWeiXin($param) {
        fdump_api(['msg' => '同步工作人员到企业微信', 'line' => __LINE__, 'param' => $param], 'qiYeWeiXin/businessSynQwLog', 1);
        $this->filterDepartmentUsers($param);
        $this->filterWorkUsers($param);

        if (!$this->department_id && $this->property_group_id) {
            if (!$this->dbPropertyGroup) {
                $this->dbPropertyGroup = new PropertyGroup();
            }
            $whereGroup = [];
            $whereGroup[] = ['id', '=', $this->property_group_id];
            $groupInfo = $this->dbPropertyGroup->getOne($whereGroup);
            if ($groupInfo && !is_array($groupInfo)) {
                $groupInfo = $groupInfo->toArray();
            }
            $this->department_id = isset($groupInfo['create_id']) && $groupInfo['create_id'] ? $groupInfo['create_id'] : 0;
        }
        $userOperation = [
            'property_id'    => $this->property_id,
            'village_id'     => $this->village_id,
            'corpid'         => $this->corpid,
            'userid'         => $this->userid,
            'mobile'         => $this->mobile,
            'name'           => $this->work_name,
            'to_invite'      => true,
            'position'       => $this->position,
            'gender'         => $this->gender,
            'enable'         => $this->enable,
            'add_department' => $this->department_id,
        ];
        $sWorkWeiXinSuiteService = new WorkWeiXinSuiteService();
        if (!$this->dbHouseWorker) {
            $this->dbHouseWorker = new HouseWorker();
        }
        $whereWork = [];
        $whereWork[] = ['wid', '=', $this->wid];
        $dataSave = [];
        if ($this->is_del) {
            // 已经删除的组织同步企业微信删除
            // 没有同步过 直接记录同步删除成功
            $delSave = [
                'del_time'      => $this->now_time,
                'qy_status'     => 4,
                'qy_corpid'     => $this->corpid,
            ];
            if ($this->userid) {
                $userOperation['userid']         = $this->userid;
                $userOperation['operation_type'] = 'delete';
                fdump_api(['msg' => '同步工作人员到企业微信-删除', 'line' => __LINE__, 'userOperation' => $userOperation], 'qiYeWeiXin/businessSynQwLog', 1);
                $result = $sWorkWeiXinSuiteService->userOperation($userOperation);
                fdump_api(['msg' => '同步工作人员到企业微信-删除-结果', 'line' => __LINE__, 'result' => $result], 'qiYeWeiXin/businessSynQwLog', 1);
                if (isset($result['code']) && $result['code']) {
                    // 报错更改记录
                    $delSave = [
                        'qy_reasons'    => $result['message'],
                        'qy_status'     => 2,
                        'qy_corpid'     => $this->corpid,
                    ];
                    if (isset($result['userid']) && $result['userid']) {
                        $delSave['qy_id'] = $result['userid'];
                    }
                    $delSave['qy_code'] = $result['code'];
                }
            }
            $this->dbHouseWorker->editData($whereWork, $delSave);
            fdump_api(['msg' => '记录删除同步', 'line' => __LINE__, 'whereWork' => $whereWork, 'delSave' => $delSave], 'qiYeWeiXin/businessSynQwLog', 1);
            return true;
        } elseif ($this->userid && $this->qy_corpid == $this->corpid) {
            // todo 同步过且是同一个企业微信 走更新逻辑
            $userOperation['userid']         = $this->userid;
            $userOperation['operation_type'] = 'update';
            $result = $sWorkWeiXinSuiteService->userOperation($userOperation, $whereWork);
            fdump_api(['msg' => '更新', 'line' => __LINE__, 'result' => $result, 'whereWork' => $whereWork, 'userOperation' => $userOperation], 'qiYeWeiXin/businessSynQwLog', 1);
            $dataSave['update_time']   = time();
        } else {
            // todo 其他情况走添加
            $userOperation['userid']         = $this->userid;
            $userOperation['operation_type'] = 'create';
            fdump_api(['msg' => '同步工作人员到企业微信-添加', 'line' => __LINE__, 'whereWork' => $whereWork, 'userOperation' => $userOperation], 'qiYeWeiXin/businessSynQwLog', 1);
            $result = $sWorkWeiXinSuiteService->userOperation($userOperation, $whereWork);
            fdump_api(['msg' => '同步工作人员到企业微信-添加-结果', 'line' => __LINE__, 'result' => $result], 'qiYeWeiXin/businessSynQwLog', 1);
            $dataSave['qy_time']   = time();
        }
        $qy_unique_json = isset($result['qy_unique_json']) && $result['qy_unique_json'] ? $result['qy_unique_json'] : '';
        if (isset($result['userid'])) {
            $dataSave['qy_id']   = $result['userid'];
        }
        if (isset($result['name'])) {
            $dataSave['qy_name'] = $result['name'];
        }
        if (isset($result['code']) && $result['code']) {
            // 报错更改记录
            $dataSave['qy_reasons'] = $result['message'];
            $dataSave['qy_status']  = 2;
            $dataSave['qy_corpid']  = $this->corpid;
            $dataSave['qy_code']    = $result['code'];
        } else {
            // 报错更改记录
            $dataSave['qy_reasons'] = '';
            $dataSave['qy_status']  = 1;
            $dataSave['qy_corpid']  = $this->corpid;
            $dataSave['qy_code']    = '';
        }
        if ($qy_unique_json) {
            $dataSave['qy_unique_json'] = $qy_unique_json;
        }
        $this->dbHouseWorker->editData($whereWork, $dataSave);
        fdump_api(['msg' => '记录同步', 'line' => __LINE__, 'whereWork' => $whereWork, 'dataSave' => $dataSave], 'qiYeWeiXin/businessSynQwLog', 1);
        return $result;
    }

    /**
     * 传参获取对应工作人员然后进行同步
     * @param $param
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function syncSingleWorkToWorkWeiXin($param) {
        fdump_api(['msg' => '传参获取对应工作人员然后进行同步', 'line' => __LINE__, 'param' => $param], 'qiYeWeiXin/businessSynQwLog', 1);
        $this->wid       = isset($param['wid'])       && $param['wid']       ? $param['wid']                   : 0;
        if (!$this->wid) {
            return [
                'code'    => 1001,
                'message' => '缺少工作人员ID',
            ];
        }
        $whereWork = [];
        $whereWork[] = ['wid', '=', $this->wid];
        if (!$this->dbHouseWorker) {
            $this->dbHouseWorker = new HouseWorker();
        }
        $work = $this->dbHouseWorker->get_one($whereWork);
        if ($work && !is_array($work)) {
            $work = $work->toArray();
        }
        $bindInfo = $this->filterHouseEnterpriseWxBind($work['property_id']);
        if (isset($bindInfo['PlaintextCorpId']) && $bindInfo['PlaintextCorpId']) {
            $corpid = $bindInfo['PlaintextCorpId'];
        } elseif (isset($bindInfo['corpid']) && $bindInfo['corpid']) {
            $corpid = $bindInfo['corpid'];
        }
        fdump_api(['msg' => '传参获取对应工作人员然后进行同步', 'line' => __LINE__, 'bindInfo' => $bindInfo], 'qiYeWeiXin/businessSynQwLog', 1);
        if (!isset($bindInfo['pigcms_id']) || !isset($corpid)) {
            fdump_api(['msg' => '物业不存在授权企业微信', 'line' => __LINE__, 'bindInfo' => $bindInfo], 'qiYeWeiXin/businessSynQwLog', 1);
            return [
                'code'    => 1001,
                'message' => '物业不存在授权企业微信',
            ];
        }
        if (!$this->dbPropertyGroup) {
            $this->dbPropertyGroup = new PropertyGroup();
        }
        $whereGroup = [];
        $whereGroup[] = ['id', '=', $work['department_id']];
        $groupInfo = $this->dbPropertyGroup->getOne($whereGroup);
        if ($groupInfo && !is_array($groupInfo)) {
            $groupInfo = $groupInfo->toArray();
        }
        if (isset($groupInfo['create_id']) && isset($groupInfo['create_id'])) {
            $sHouseWorkerService = new HouseWorkerService();
            $worker_name_arr = $sHouseWorkerService->worker_name;
            $queueData = [
                'jobType'           => 'syncWorkUserToWorkWeiXin',
                'property_group_id' => $work['department_id'],
                'department_id'     => $groupInfo['create_id'],
                'property_id'       => $work['property_id'],
                'village_id'        => $work['village_id'],
                'mobile'            => $work['phone'],
                'name'              => $work['name'],
                'type'              => $work['type'],
                'position'          => isset($worker_name_arr[$work['type']]) ? $worker_name_arr[$work['type']] : '',
                'gender'            => $work['gender'],
                'enable'            => $work['status'] == 1 ? 1 : 0,
                'wid'               => $work['wid'],
                'is_del'            => $work['is_del'],
                'qy_status'         => $work['qy_status'],
                'qy_corpid'         => $work['qy_corpid'],
                'corpid'           => $corpid,
            ];
            if ($work['qy_id']) {
                $queueData['userid'] = $work['qy_id'];
            } else {
                $queueData['userid'] = 'worker_'.$work['wid'];
            }
            fdump_api(['msg' => '传参获取对应工作人员然后进行同步-队列', 'line' => __LINE__, 'queueData' => $queueData], 'qiYeWeiXin/businessSynQwLog', 1);
            $job_id = $this->traitCommonWorkWeiXin($queueData);
            // todo 测试 展示直接调用
//            unset($queueData['jobType']);
//            $msg = $this->syncWorkUserToWorkWeiXin($queueData);
            return $job_id;
        }
        return true;
    }
    
    
    
    /**
     * 查询绑定企业微信信息
     * @param $property_id
     * @param int $village_id
     * @return array|\think\Model|null
     */
    protected function filterHouseEnterpriseWxBind($property_id, $village_id = 0) {
        $where = [];
        $where[] = ['bind_id',   '=', $property_id];
        $dbHouseEnterpriseWxBind = new HouseEnterpriseWxBind();
        $bindInfo = $dbHouseEnterpriseWxBind->getOne($where, true,'pigcms_id DESC');
        if ($bindInfo && !is_array($bindInfo)) {
            $bindInfo = $bindInfo->toArray();
        }
        return $bindInfo;
    }
    
}