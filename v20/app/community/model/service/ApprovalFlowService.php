<?php

namespace app\community\model\service;

use think\facade\Db;
use app\consts\HouseWorkConst;

class ApprovalFlowService
{
    private const APPLICANT_NORMAL_ADMIN_OFFSET = 2000000000;
    private const APPLICANT_ROOT_ADMIN_OFFSET = 3000000000;
    private const APPLICANT_SOURCE_ID_MAX = 999999999;

    const BUSINESS_SERVICE_TIME = 'service_time_change';
    const BUSINESS_DELIVERY_TIME = 'delivery_time_change';
    const BUSINESS_BILL_AMOUNT = 'bill_amount_change';
    const BUSINESS_DEPOSIT_RELEASE = 'deposit_release';
    const BUSINESS_ORDER_REFUND = 'order_refund';
    const BUSINESS_ORDER_DISCARD = 'order_discard';
    const BUSINESS_PUBLIC_REVENUE = 'public_revenue_change';

    private function defaultBusinessTypes()
    {
        return [
            ['type_key' => self::BUSINESS_SERVICE_TIME, 'type_name' => '调整物业服务时间'],
            ['type_key' => self::BUSINESS_DELIVERY_TIME, 'type_name' => '调整交房时间'],
            ['type_key' => self::BUSINESS_BILL_AMOUNT, 'type_name' => '修改账单金额'],
            ['type_key' => self::BUSINESS_DEPOSIT_RELEASE, 'type_name' => '押金解冻'],
            ['type_key' => self::BUSINESS_ORDER_REFUND, 'type_name' => '账单退款'],
            ['type_key' => self::BUSINESS_ORDER_DISCARD, 'type_name' => '账单作废'],
            ['type_key' => self::BUSINESS_PUBLIC_REVENUE, 'type_name' => '修改公共收益'],
        ];
    }

    private function ensureDefaultBusinessTypes($adminUser)
    {
        $scope = $this->getScope($adminUser);
        $now = time();
        foreach ($this->defaultBusinessTypes() as $type) {
            $exists = Db::name('community_approval_business_type')->where([
                ['property_id', '=', $scope['property_id']],
                ['village_id', '=', $scope['village_id']],
                ['type_key', '=', $type['type_key']],
                ['is_del', '=', 0],
            ])->find();
            if (!$exists) {
                Db::name('community_approval_business_type')->insert([
                    'property_id' => $scope['property_id'],
                    'village_id' => $scope['village_id'],
                    'type_key' => $type['type_key'],
                    'type_name' => $type['type_name'],
                    'status' => 1,
                    'is_system' => 1,
                    'is_del' => 0,
                    'add_time' => $now,
                    'update_time' => $now,
                ]);
            } elseif (intval($exists['is_system'] ?? 0) !== 1) {
                Db::name('community_approval_business_type')->where('id', $exists['id'])->update([
                    'is_system' => 1,
                    'update_time' => $now,
                ]);
            }
        }
    }

    public function businessTypeList($adminUser, $params)
    {
        $this->ensureDefaultBusinessTypes($adminUser);
        $scope = $this->getScope($adminUser);
        $page = max(1, intval($params['page'] ?? 1));
        $pageSize = max(1, intval($params['page_size'] ?? 10));
        $where = [
            ['property_id', '=', $scope['property_id']],
            ['village_id', '=', $scope['village_id']],
            ['is_del', '=', 0],
        ];
        $keyword = trim($params['keyword'] ?? '');
        if (isset($params['status']) && intval($params['status']) >= 0) {
            $where[] = ['status', '=', intval($params['status'])];
        }
        $query = Db::name('community_approval_business_type')->where($where);
        if ($keyword !== '') {
            $query->where(function ($query) use ($keyword) {
                $query->whereLike('type_name', '%' . $keyword . '%')
                    ->whereOr('type_key', 'like', '%' . $keyword . '%');
            });
        }
        $countQuery = clone $query;
        $count = $countQuery->count();
        $list = $query->page($page, $pageSize)->order('is_system DESC,id DESC')->select()->toArray();
        foreach ($list as &$item) {
            $item['add_time_text'] = $this->timeText($item['add_time'] ?? 0);
            $item['update_time_text'] = $this->timeText($item['update_time'] ?? 0);
        }
        return ['list' => $list, 'count' => $count, 'page_size' => $pageSize];
    }

    public function businessTypeOptions($adminUser)
    {
        $this->ensureDefaultBusinessTypes($adminUser);
        $scope = $this->getScope($adminUser);
        $list = Db::name('community_approval_business_type')->where([
            ['property_id', '=', $scope['property_id']],
            ['village_id', '=', $scope['village_id']],
            ['status', '=', 1],
            ['is_del', '=', 0],
        ])->field('id,type_key,type_name,is_system,status')->order('is_system DESC,id DESC')->select()->toArray();
        return ['list' => $list];
    }

    public function businessTypeSave($adminUser, $params)
    {
        $scope = $this->getScope($adminUser);
        $id = intval($params['id'] ?? 0);
        $typeName = trim($params['type_name'] ?? '');
        $typeKey = trim($params['type_key'] ?? '');
        if ($typeName === '') {
            throw new \Exception('请输入类型名称');
        }
        if ($typeKey === '' || !preg_match('/^[a-z][a-z0-9_]*$/', $typeKey)) {
            throw new \Exception('类型标识只能使用小写字母、数字、下划线，并以字母开头');
        }
        $repeatWhere = [
            ['property_id', '=', $scope['property_id']],
            ['village_id', '=', $scope['village_id']],
            ['type_key', '=', $typeKey],
            ['is_del', '=', 0],
        ];
        if ($id > 0) {
            $repeatWhere[] = ['id', '<>', $id];
        }
        if (Db::name('community_approval_business_type')->where($repeatWhere)->find()) {
            throw new \Exception('该业务类型标识已存在');
        }
        $now = time();
        $info = [];
        if ($id > 0) {
            $info = Db::name('community_approval_business_type')->where([
                ['id', '=', $id],
                ['property_id', '=', $scope['property_id']],
                ['village_id', '=', $scope['village_id']],
                ['is_del', '=', 0],
            ])->find();
            if (!$info) {
                throw new \Exception('业务类型不存在');
            }
            if (intval($info['is_system']) === 1 && $typeKey !== $info['type_key']) {
                throw new \Exception('系统内置类型标识不允许修改');
            }
        }
        $data = [
            'property_id' => $scope['property_id'],
            'village_id' => $scope['village_id'],
            'type_name' => $typeName,
            'type_key' => $typeKey,
            'status' => intval($params['status'] ?? 1) ? 1 : 0,
            'update_time' => $now,
        ];
        if ($id > 0) {
            Db::name('community_approval_business_type')->where('id', $id)->update($data);
        } else {
            $data['is_system'] = 0;
            $data['is_del'] = 0;
            $data['add_time'] = $now;
            $id = Db::name('community_approval_business_type')->insertGetId($data);
        }
        return ['id' => $id, 'type_key' => $typeKey];
    }

    public function businessTypeStatus($adminUser, $params)
    {
        $scope = $this->getScope($adminUser);
        $id = intval($params['id'] ?? 0);
        $status = intval($params['status'] ?? 0) ? 1 : 0;
        $res = Db::name('community_approval_business_type')->where([
            ['id', '=', $id],
            ['property_id', '=', $scope['property_id']],
            ['village_id', '=', $scope['village_id']],
            ['is_del', '=', 0],
        ])->update(['status' => $status, 'update_time' => time()]);
        if ($res === false) {
            throw new \Exception('操作失败');
        }
        return ['id' => $id, 'status' => $status];
    }

    private function getBusinessTypeById($adminUser, $id, $mustActive = true)
    {
        $this->ensureDefaultBusinessTypes($adminUser);
        $scope = $this->getScope($adminUser);
        $where = [
            ['id', '=', intval($id)],
            ['property_id', '=', $scope['property_id']],
            ['village_id', '=', $scope['village_id']],
            ['is_del', '=', 0],
        ];
        if ($mustActive) {
            $where[] = ['status', '=', 1];
        }
        return Db::name('community_approval_business_type')->where($where)->find();
    }

    private function getBusinessTypeByKey($adminUser, $typeKey, $mustActive = true)
    {
        $this->ensureDefaultBusinessTypes($adminUser);
        $scope = $this->getScope($adminUser);
        $where = [
            ['property_id', '=', $scope['property_id']],
            ['village_id', '=', $scope['village_id']],
            ['type_key', '=', trim($typeKey)],
            ['is_del', '=', 0],
        ];
        if ($mustActive) {
            $where[] = ['status', '=', 1];
        }
        return Db::name('community_approval_business_type')->where($where)->find();
    }

    public function flowList($adminUser, $params)
    {
        $this->ensureDefaultBusinessTypes($adminUser);
        $scope = $this->getScope($adminUser);
        $page = max(1, intval($params['page'] ?? 1));
        $pageSize = max(1, intval($params['page_size'] ?? 10));
        $where = [
            ['property_id', '=', $scope['property_id']],
            ['village_id', '=', $scope['village_id']],
            ['is_del', '=', 0],
        ];
        $filterBusinessType = null;
        if (!empty($params['business_type_id'])) {
            $filterBusinessType = $this->getBusinessTypeById($adminUser, intval($params['business_type_id']), false);
        } elseif (!empty($params['business_type'])) {
            $where[] = ['business_type', '=', trim($params['business_type'])];
        }
        if (isset($params['status']) && intval($params['status']) >= 0) {
            $where[] = ['status', '=', intval($params['status'])];
        }
        $query = Db::name('community_approval_flow')->where($where);
        if ($filterBusinessType) {
            $query->where(function ($query) use ($filterBusinessType) {
                $query->where('business_type_id', intval($filterBusinessType['id']))
                    ->whereOr('business_type', $filterBusinessType['type_key']);
            });
        }
        $count = $query->count();
        $list = $query->page($page, $pageSize)->order('id DESC')->select()->toArray();
        foreach ($list as &$item) {
            $item['node_count'] = Db::name('community_approval_flow_node')->where([
                ['flow_id', '=', $item['id']],
                ['is_del', '=', 0],
            ])->count();
            $type = $this->getFlowBusinessType($scope, $item);
            $item['business_type_id'] = intval($type['id'] ?? ($item['business_type_id'] ?? 0));
            $item['business_type_name'] = $type['type_name'] ?? ($item['business_type'] ?? '');
            $item['add_time_text'] = $this->timeText($item['add_time'] ?? 0);
            $item['update_time_text'] = $this->timeText($item['update_time'] ?? 0);
        }
        return ['list' => $list, 'count' => $count, 'page_size' => $pageSize];
    }

    public function flowDetail($adminUser, $id)
    {
        $scope = $this->getScope($adminUser);
        $info = Db::name('community_approval_flow')->where([
            ['id', '=', intval($id)],
            ['property_id', '=', $scope['property_id']],
            ['village_id', '=', $scope['village_id']],
            ['is_del', '=', 0],
        ])->find();
        if (!$info) {
            throw new \Exception('审批流不存在');
        }
        $type = $this->getFlowBusinessType($scope, $info);
        $info['business_type_id'] = intval($type['id'] ?? ($info['business_type_id'] ?? 0));
        $info['business_type_name'] = $type['type_name'] ?? ($info['business_type'] ?? '');
        $nodes = Db::name('community_approval_flow_node')->where([
            ['flow_id', '=', $info['id']],
            ['is_del', '=', 0],
        ])->order('sort ASC,id ASC')->select()->toArray();
        $approverWids = array_values(array_unique(array_filter(array_map(function ($node) {
            return intval($node['approver_wid'] ?? 0);
        }, $nodes))));
        $approverMap = $this->getApprovalApproverInfoMap($scope, $approverWids);
        foreach ($nodes as &$node) {
            $approverInfo = $approverMap[intval($node['approver_wid'] ?? 0)] ?? [];
            $node['approver_organization'] = $approverInfo['organization'] ?? '';
            $node['approver_account'] = $approverInfo['account'] ?? '';
            $node['approver_phone'] = $approverInfo['phone'] ?? '';
            $node['approver_login_role'] = $approverInfo['login_role'] ?? '';
            $node['approver_login_role_text'] = $approverInfo['login_role_text'] ?? '';
        }
        unset($node);
        return ['info' => $info, 'nodes' => $nodes];
    }

    public function flowSave($adminUser, $params)
    {
        $scope = $this->getScope($adminUser);
        $id = intval($params['id'] ?? 0);
        $flowName = trim($params['flow_name'] ?? '');
        $businessTypeId = intval($params['business_type_id'] ?? 0);
        $nodes = $params['nodes'] ?? [];
        if ($flowName === '') {
            throw new \Exception('请输入流程名称');
        }
        $businessTypeInfo = $this->getBusinessTypeById($adminUser, $businessTypeId);
        if (!$businessTypeInfo) {
            throw new \Exception('业务类型不存在或已停用');
        }
        $businessType = $businessTypeInfo['type_key'];
        if (empty($nodes) || !is_array($nodes)) {
            throw new \Exception('请至少添加一个审批节点');
        }
        foreach ($nodes as $node) {
            if (empty($node['node_name']) || empty($node['approver_wid'])) {
                throw new \Exception('请完善节点名称和审批人');
            }
        }
        $now = time();
        Db::startTrans();
        try {
            $data = [
                'property_id' => $scope['property_id'],
                'village_id' => $scope['village_id'],
                'flow_name' => $flowName,
                'business_type' => $businessType,
                'business_type_id' => intval($businessTypeInfo['id']),
                'status' => intval($params['status'] ?? 1),
                'update_time' => $now,
            ];
            if ($id > 0) {
                $flow = Db::name('community_approval_flow')->where([
                    ['id', '=', $id],
                    ['property_id', '=', $scope['property_id']],
                    ['village_id', '=', $scope['village_id']],
                    ['is_del', '=', 0],
                ])->find();
                if (!$flow) {
                    throw new \Exception('审批流不存在');
                }
                $data['business_type_id'] = intval($flow['business_type_id'] ?? $businessTypeInfo['id']);
                $data['business_type'] = $flow['business_type'] ?: $businessType;
                Db::name('community_approval_flow')->where('id', $id)->update($data);
            } else {
                $exists = Db::name('community_approval_flow')->where([
                    ['property_id', '=', $scope['property_id']],
                    ['village_id', '=', $scope['village_id']],
                    ['is_del', '=', 0],
                ])->where(function ($query) use ($businessTypeInfo, $businessType) {
                    $query->where('business_type_id', intval($businessTypeInfo['id']))
                        ->whereOr('business_type', $businessType);
                })->find();
                if ($exists) {
                    throw new \Exception('该业务类型已存在审批流，请直接编辑');
                }
                $data['add_time'] = $now;
                $id = Db::name('community_approval_flow')->insertGetId($data);
            }
            Db::name('community_approval_flow_node')->where('flow_id', $id)->update(['is_del' => 1, 'update_time' => $now]);
            foreach (array_values($nodes) as $index => $node) {
                $wid = intval($node['approver_wid']);
                $worker = $this->getWorker($scope, $wid);
                if (!$worker) {
                    throw new \Exception('审批人不存在或不属于当前物业公司');
                }
                Db::name('community_approval_flow_node')->insert([
                    'flow_id' => $id,
                    'property_id' => $scope['property_id'],
                    'village_id' => $scope['village_id'],
                    'node_name' => trim($node['node_name']),
                    'sort' => $index + 1,
                    'approver_wid' => $wid,
                    'approver_name' => $worker['name'],
                    'status' => intval($node['status'] ?? 1),
                    'is_del' => 0,
                    'add_time' => $now,
                    'update_time' => $now,
                ]);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
        return ['id' => $id];
    }

    public function flowStatus($adminUser, $params)
    {
        $scope = $this->getScope($adminUser);
        $id = intval($params['id'] ?? 0);
        $status = intval($params['status'] ?? 0) ? 1 : 0;
        $res = Db::name('community_approval_flow')->where([
            ['id', '=', $id],
            ['property_id', '=', $scope['property_id']],
            ['village_id', '=', $scope['village_id']],
            ['is_del', '=', 0],
        ])->update(['status' => $status, 'update_time' => time()]);
        if ($res === false) {
            throw new \Exception('操作失败');
        }
        return ['id' => $id, 'status' => $status];
    }

    public function propertyWorkers($adminUser)
    {
        $scope = $this->getScope($adminUser);
        $propertyName = Db::name('house_property')->where('id', $scope['property_id'])->value('property_name');
        $villageName = Db::name('house_village')->where('village_id', $scope['village_id'])->value('village_name');
        $groups = Db::name('property_group')
            ->where([
                ['property_id', '=', $scope['property_id']],
                ['is_del', '=', 0],
                ['status', '=', 1],
            ])
            ->where(function ($query) use ($scope) {
                $query->where('type', 99)
                    ->whereOr(function ($query) use ($scope) {
                        $query->where('village_id', 0)->where('type', 2);
                    })
                    ->whereOr(function ($query) use ($scope) {
                        $query->where('village_id', $scope['village_id']);
                    });
            })
            ->field('id,fid,name,type,village_id,sort')
            ->order('sort DESC,id ASC')
            ->select()->toArray();
        $workers = Db::name('house_worker')->alias('w')
            ->leftJoin('property_group g', 'g.id = w.department_id')
            ->where([
                ['w.property_id', '=', $scope['property_id']],
                ['w.status', '=', 1],
                ['w.is_del', '=', 0],
            ])
            ->where(function ($query) use ($scope) {
                $query->where('w.village_id', 0)->whereOr('w.village_id', intval($scope['village_id']));
            })
            ->field('w.wid,w.name,w.account,w.phone,w.department_id,w.village_id,g.name as department_name')
            ->order('g.sort DESC,w.wid DESC')
            ->select()->toArray();
        foreach ($workers as &$worker) {
            $worker['organization'] = $this->formatApproverOrganization($worker, $scope, $propertyName, $villageName);
            $worker['login_role'] = $this->getApproverLoginRole($worker);
            $worker['login_role_text'] = $this->getApproverLoginRoleText($worker);
            $worker['title'] = $this->formatApproverTreeTitle($worker);
        }
        unset($worker);
        return [
            'list' => $workers,
            'tree' => $this->buildWorkerTree($groups, $workers, $scope, $propertyName, $villageName),
        ];
    }

    private function buildWorkerTree($groups, $workers, $scope, $propertyName, $villageName)
    {
        $rootId = 0;
        $groupMap = [];
        foreach ($groups as $group) {
            if (intval($group['type']) === 99) {
                $rootId = intval($group['id']);
            }
            $groupMap[intval($group['id'])] = [
                'title' => $group['name'],
                'value' => 'group_' . $group['id'],
                'key' => 'group_' . $group['id'],
                'selectable' => false,
                'children' => [],
                '_id' => intval($group['id']),
                '_fid' => intval($group['fid']),
                '_type' => intval($group['type']),
                '_village_id' => intval($group['village_id']),
            ];
        }
        if (!$rootId) {
            $rootId = -1;
            $groupMap[$rootId] = [
                'title' => $propertyName ?: '物业公司',
                'value' => 'group_root',
                'key' => 'group_root',
                'selectable' => false,
                'children' => [],
                '_id' => $rootId,
                '_fid' => 0,
                '_type' => 99,
                '_village_id' => 0,
            ];
        } elseif (!empty($propertyName)) {
            $groupMap[$rootId]['title'] = $propertyName;
        }
        $hasCurrentVillage = false;
        foreach ($groupMap as $node) {
            if ($node['_type'] === 0 && $node['_village_id'] === intval($scope['village_id'])) {
                $hasCurrentVillage = true;
                break;
            }
        }
        if (!$hasCurrentVillage) {
            $syntheticVillageId = -2;
            $groupMap[$syntheticVillageId] = [
                'title' => $villageName ?: '当前小区',
                'value' => 'group_current_village',
                'key' => 'group_current_village',
                'selectable' => false,
                'children' => [],
                '_id' => $syntheticVillageId,
                '_fid' => $rootId,
                '_type' => 0,
                '_village_id' => intval($scope['village_id']),
            ];
        }
        foreach ($groupMap as $id => &$node) {
            if ($node['_type'] === 0 && $node['_village_id'] === intval($scope['village_id']) && !empty($villageName)) {
                $node['title'] = $villageName;
            }
            if ($id === $rootId) {
                continue;
            }
            $fid = $node['_fid'];
            if (isset($groupMap[$fid])) {
                $groupMap[$fid]['children'][] = &$node;
            } elseif (isset($groupMap[$rootId])) {
                $groupMap[$rootId]['children'][] = &$node;
            }
        }
        unset($node);
        foreach ($workers as $worker) {
            $departmentId = intval($worker['department_id']);
            if (!isset($groupMap[$departmentId])) {
                $departmentId = $rootId;
            }
            $groupMap[$departmentId]['children'][] = [
                'title' => $this->formatApproverTreeTitle($worker),
                'value' => intval($worker['wid']),
                'key' => 'worker_' . $worker['wid'],
                'is_worker' => 1,
                'wid' => intval($worker['wid']),
                'name' => $worker['name'],
                'department_name' => $worker['department_name'] ?? '',
                'account' => $worker['account'] ?? '',
                'phone' => $worker['phone'] ?? '',
                'login_role' => $this->getApproverLoginRole($worker),
                'login_role_text' => $this->getApproverLoginRoleText($worker),
            ];
        }
        $tree = [$this->cleanTreeNode($groupMap[$rootId])];
        return $tree;
    }

    private function cleanTreeNode($node)
    {
        unset($node['_id'], $node['_fid'], $node['_type'], $node['_village_id']);
        if (!empty($node['children'])) {
            foreach ($node['children'] as &$child) {
                $child = $this->cleanTreeNode($child);
            }
            unset($child);
        }
        return $node;
    }

    public function createInstance($adminUser, $params)
    {
        $scope = $this->getScope($adminUser);
        $businessType = trim($params['business_type'] ?? '');
        $changeData = $params['change_data'] ?? [];
        $changeAction = is_array($changeData) ? trim($changeData['action'] ?? '') : '';
        if ($businessType === self::BUSINESS_DEPOSIT_RELEASE) {
            $changeAction = 'deposit_release';
        } elseif ($changeAction === '') {
            $isDepositCategory = Db::name('house_new_deposit_category')->where([
                ['village_id', '=', $scope['village_id']],
                ['cate_key', '=', $businessType],
                ['is_del', '=', 0],
            ])->find();
            if ($isDepositCategory) {
                // 兼容旧页面仍使用押金分类 cate_key 发起解冻审批。
                $changeAction = 'deposit_release';
            }
        }
        if ($changeAction === 'deposit_release') {
            $changeData['action'] = 'deposit_release';
            $params['change_data'] = $changeData;
        }
        $actionBusinessTypeMap = [
            'deposit_release' => self::BUSINESS_DEPOSIT_RELEASE,
            'order_refund' => self::BUSINESS_ORDER_REFUND,
            'order_discard' => self::BUSINESS_ORDER_DISCARD,
            'bill_amount_change' => self::BUSINESS_BILL_AMOUNT,
        ];
        if (isset($actionBusinessTypeMap[$changeAction])) {
            // 业务动作只允许命中对应的系统内置类型，兼容旧前端仍传押金分类 key 的情况。
            $businessType = $actionBusinessTypeMap[$changeAction];
        }
        // 按归一后的系统业务执行防重，避免缺失或不一致的 action 绕过检查。
        if (isset($actionBusinessTypeMap[$businessType])) {
            $changeAction = $businessType;
        }
        $isOrderApproval = in_array($changeAction, ['deposit_release', 'order_refund', 'order_discard', 'bill_amount_change'], true);
        if ($isOrderApproval) {
            $businessId = intval($params['business_id'] ?? ($changeData['order_id'] ?? 0));
            $params['business_id'] = $businessId;
            if ($changeAction === 'deposit_release') {
                $roomId = intval($changeData['room_id'] ?? 0);
                if ($roomId <= 0 && $businessId > 0) {
                    $roomId = intval(Db::name('house_new_pay_order')
                        ->where('order_id', $businessId)
                        ->where('village_id', $scope['village_id'])
                        ->value('room_id'));
                }
                $attachmentStatusMap = (new ChatSidebarService())->getDepositAttachmentStatusMap($scope['village_id'], [$roomId]);
                if ($roomId <= 0 || empty($attachmentStatusMap[$roomId])) {
                    throw new \Exception('没有上传押金附件的不允许解冻押金，请到押金详情 -> 押金抵扣记录 -> 押金附件中上传');
                }
            }
        }
        $businessTypeInfo = $this->getBusinessTypeByKey($adminUser, $businessType);
        if (!$businessTypeInfo) {
            throw new \Exception('业务类型不正确');
        }
        $flow = Db::name('community_approval_flow')->where([
            ['property_id', '=', $scope['property_id']],
            ['village_id', '=', $scope['village_id']],
            ['business_type_id', '=', intval($businessTypeInfo['id'])],
            ['status', '=', 1],
            ['is_del', '=', 0],
        ])->find();
        if (!$flow) {
            $flow = Db::name('community_approval_flow')->where([
                ['property_id', '=', $scope['property_id']],
                ['village_id', '=', $scope['village_id']],
                ['business_type', '=', $businessType],
                ['status', '=', 1],
                ['is_del', '=', 0],
            ])->find();
            if ($flow && empty($flow['business_type_id'])) {
                Db::name('community_approval_flow')->where('id', $flow['id'])->update([
                    'business_type_id' => intval($businessTypeInfo['id']),
                    'update_time' => time(),
                ]);
            }
        }
        if (!$flow) {
            throw new \Exception('当前没有创建审批流程，请联系管理员创建');
        }
        $nodes = Db::name('community_approval_flow_node')->where([
            ['flow_id', '=', $flow['id']],
            ['status', '=', 1],
            ['is_del', '=', 0],
        ])->order('sort ASC,id ASC')->select()->toArray();
        if (!$nodes) {
            throw new \Exception('审批流没有启用节点');
        }
        if ($businessType === self::BUSINESS_PUBLIC_REVENUE && intval($adminUser['wid'] ?? 0) > 0) {
            foreach ($nodes as $node) {
                if (intval($node['approver_wid'] ?? 0) === intval($adminUser['wid'])) {
                    throw new \Exception('修改公共收益的提交人不能审批自己的申请，请调整审批流人员');
                }
            }
        }
        $now = time();
        $applyWid = $this->getApplicantIdentity($adminUser);
        $applyName = $this->getAdminUserName($adminUser);
        Db::startTrans();
        try {
            if ($isOrderApproval) {
                // 同一订单的申请串行执行，锁持有至审批主表、节点及日志全部提交。
                $lockedOrder = Db::name('house_new_pay_order')->where([
                    ['order_id', '=', $businessId],
                    ['village_id', '=', $scope['village_id']],
                ])->lock(true)->find();
                if (!$lockedOrder) {
                    throw new \Exception('账单不存在，无法提交审批');
                }
                $pendingApprovalQuery = Db::name('community_approval_instance')->where([
                    ['property_id', '=', $scope['property_id']],
                    ['village_id', '=', $scope['village_id']],
                    ['business_id', '=', $businessId],
                    ['status', '=', 0],
                ]);
                if ($changeAction === 'deposit_release') {
                    $pendingApprovalQuery->where(function ($query) use ($businessType) {
                        $query->where('business_type', $businessType)
                            ->whereOr('change_data', 'like', '%"action":"deposit_release"%');
                    });
                } else {
                    $pendingApprovalQuery->where('business_type', $businessType);
                }
                $pendingApproval = $pendingApprovalQuery->lock(true)->find();
                if ($pendingApproval) {
                    $actionNames = [
                        'deposit_release' => '押金解冻',
                        'order_refund' => '账单退款',
                        'order_discard' => '账单作废',
                        'bill_amount_change' => '修改账单金额',
                    ];
                    throw new \Exception(($actionNames[$changeAction] ?? '该业务') . '申请正在审批中，请勿重复提交');
                }
            }
            $instanceId = Db::name('community_approval_instance')->insertGetId([
                'flow_id' => $flow['id'],
                'property_id' => $scope['property_id'],
                'village_id' => $scope['village_id'],
                'business_type' => $businessType,
                'business_type_id' => intval($businessTypeInfo['id']),
                'business_id' => intval($params['business_id'] ?? 0),
                'title' => trim($params['title'] ?? $flow['flow_name']),
                'apply_wid' => $applyWid,
                'apply_name' => $applyName,
                'apply_remark' => trim($params['apply_remark'] ?? ''),
                'apply_reason' => trim($params['apply_reason'] ?? ($params['apply_remark'] ?? '')),
                'change_data' => json_encode($params['change_data'] ?? [], JSON_UNESCAPED_UNICODE),
                'current_sort' => 1,
                'status' => 0,
                'add_time' => $now,
                'update_time' => $now,
            ]);
            foreach ($nodes as $node) {
                $approver = $this->getWorker($scope, intval($node['approver_wid'] ?? 0));
                if (!$approver) {
                    throw new \Exception('审批节点“' . ($node['node_name'] ?? '') . '”的审批人不属于当前物业或当前小区，请重新选择审批人');
                }
                Db::name('community_approval_instance_node')->insert([
                    'instance_id' => $instanceId,
                    'flow_node_id' => $node['id'],
                    'sort' => $node['sort'],
                    'node_name' => $node['node_name'],
                    'approver_wid' => $node['approver_wid'],
                    'approver_name' => $approver['name'],
                    'status' => 0,
                    'remark' => '',
                    'approve_time' => 0,
                    'add_time' => $now,
                ]);
            }
            $this->addRecord($instanceId, 'submit', $applyWid, $applyName, trim($params['apply_remark'] ?? ''), $now);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
        return ['id' => $instanceId];
    }

    public function instanceList($adminUser, $params)
    {
        $scope = $this->getScope($adminUser);
        $page = max(1, intval($params['page'] ?? 1));
        $pageSize = max(1, intval($params['page_size'] ?? 10));
        $wid = intval($adminUser['wid'] ?? 0);
        $applicantIdentity = $this->getApplicantIdentity($adminUser);
        $reviewedIds = Db::name('community_approval_instance_node')->where([
            ['approver_wid', '=', $wid],
        ])->whereIn('status', [1, 2])->column('instance_id');
        $where = [
            ['i.property_id', '=', $scope['property_id']],
            ['i.village_id', '=', $scope['village_id']],
        ];
        if (!empty($params['business_type_id'])) {
            $where[] = ['i.business_type_id', '=', intval($params['business_type_id'])];
        } elseif (!empty($params['business_type'])) {
            $where[] = ['i.business_type', '=', trim($params['business_type'])];
        }
        if (isset($params['status']) && intval($params['status']) >= 0) {
            $where[] = ['i.status', '=', intval($params['status'])];
        }
        $scopeType = trim($params['scope'] ?? 'todo');
        $query = Db::name('community_approval_instance')->alias('i')
            ->leftJoin('community_approval_instance_node n', 'n.instance_id = i.id AND n.sort = i.current_sort')
            ->field('i.*,n.node_name as current_node_name,n.approver_wid as current_approver_wid')
            ->where($where);
        if ($scopeType === 'todo') {
            $query->where('i.status', 0)->where('n.approver_wid', $wid)->where('n.status', 0);
        } elseif ($scopeType === 'mine') {
            $query->where('i.apply_wid', $applicantIdentity);
        } elseif ($scopeType === 'reviewed') {
            $query->whereIn('i.id', $reviewedIds ?: [0]);
        } else {
            $query->where(function ($query) use ($wid, $applicantIdentity, $reviewedIds) {
                $query->where('i.apply_wid', $applicantIdentity)
                    ->whereOr(function ($query) use ($wid) {
                        $query->where('i.status', 0)
                            ->where('n.approver_wid', $wid)
                            ->where('n.status', 0);
                    });
                if ($reviewedIds) {
                    $query->whereOr('i.id', 'in', $reviewedIds);
                }
            });
        }
        $countQuery = clone $query;
        $count = $countQuery->count();
        $list = $query->page($page, $pageSize)->order('i.id DESC')->select()->toArray();
        foreach ($list as &$item) {
            $item = $this->formatInstance($item, $wid, $applicantIdentity);
        }
        return ['list' => $list, 'count' => $count, 'page_size' => $pageSize];
    }

    public function instanceDetail($adminUser, $id)
    {
        $scope = $this->getScope($adminUser);
        $wid = intval($adminUser['wid'] ?? 0);
        $applicantIdentity = $this->getApplicantIdentity($adminUser);
        $info = Db::name('community_approval_instance')->alias('i')
            ->leftJoin('community_approval_instance_node n', 'n.instance_id = i.id AND n.sort = i.current_sort')
            ->field('i.*,n.node_name as current_node_name,n.approver_wid as current_approver_wid')
            ->where([['i.id', '=', intval($id)], ['i.property_id', '=', $scope['property_id']], ['i.village_id', '=', $scope['village_id']]])
            ->find();
        if (!$info) {
            throw new \Exception('审批记录不存在');
        }
        $isMine = intval($info['apply_wid']) === $applicantIdentity;
        $isCurrentApprover = intval($info['status']) === 0 && intval($info['current_approver_wid'] ?? 0) === $wid;
        $hasReviewed = Db::name('community_approval_instance_node')->where([
            ['instance_id', '=', intval($info['id'])],
            ['approver_wid', '=', $wid],
        ])->whereIn('status', [1, 2])->count() > 0;
        if (!$isMine && !$isCurrentApprover && !$hasReviewed) {
            throw new \Exception('审批记录不存在');
        }
        $info = $this->formatInstance($info, $wid, $applicantIdentity);
        $info['bill_info_text'] = $this->currentBillInfoText($info);
        $nodes = Db::name('community_approval_instance_node')->where('instance_id', $info['id'])->order('sort ASC,id ASC')->select()->toArray();
        $approverWids = array_values(array_unique(array_filter(array_map(function ($node) {
            return intval($node['approver_wid'] ?? 0);
        }, $nodes))));
        $approverMap = $this->getApprovalApproverInfoMap($scope, $approverWids);
        foreach ($nodes as &$node) {
            $node['status_text'] = $this->nodeStatusText($node['status']);
            $node['approve_time_text'] = $this->timeText($node['approve_time'] ?? 0);
            $approverInfo = $approverMap[intval($node['approver_wid'] ?? 0)] ?? [];
            $node['approver_organization'] = $approverInfo['organization'] ?? '';
            $node['approver_account'] = $approverInfo['account'] ?? '';
            $node['approver_login_role'] = $approverInfo['login_role'] ?? '';
            $node['approver_login_role_text'] = $approverInfo['login_role_text'] ?? '';
        }
        return ['info' => $info, 'nodes' => $nodes];
    }

    public function approvalAction($adminUser, $params)
    {
        $scope = $this->getScope($adminUser);
        $id = intval($params['id'] ?? 0);
        $action = trim($params['action'] ?? '');
        $remark = trim($params['remark'] ?? '');
        $wid = intval($adminUser['wid'] ?? 0);
        $applicantIdentity = $this->getApplicantIdentity($adminUser);
        $name = $this->getAdminUserName($adminUser);
        $now = time();
        $info = Db::name('community_approval_instance')->where([
            ['id', '=', $id],
            ['property_id', '=', $scope['property_id']],
            ['village_id', '=', $scope['village_id']],
        ])->find();
        if (!$info) {
            throw new \Exception('审批记录不存在');
        }
        if ($info['status'] != 0) {
            throw new \Exception('当前审批已结束');
        }
        Db::startTrans();
        try {
            if ($action === 'withdraw') {
                if (intval($info['apply_wid']) !== $applicantIdentity) {
                    throw new \Exception('只有发起人可以撤回');
                }
                if ($remark === '') {
                    throw new \Exception('请输入撤回原因');
                }
                Db::name('community_approval_instance')->where('id', $id)->update(['status' => 3, 'update_time' => $now, 'finish_time' => $now]);
                $this->closeBusinessApproval($info, 'withdraw');
                $this->addRecord($id, 'withdraw', $applicantIdentity, $name, $remark, $now);
                Db::commit();
                return ['id' => $id, 'status' => 3];
            }
            $node = Db::name('community_approval_instance_node')->where([
                ['instance_id', '=', $id],
                ['sort', '=', intval($info['current_sort'])],
                ['status', '=', 0],
            ])->find();
            if (!$node || intval($node['approver_wid']) !== $wid) {
                throw new \Exception('当前账号不是本节点审批人');
            }
            if ($action === 'reject') {
                Db::name('community_approval_instance_node')->where('id', $node['id'])->update(['status' => 2, 'remark' => $remark, 'approve_time' => $now]);
                Db::name('community_approval_instance')->where('id', $id)->update(['status' => 2, 'update_time' => $now, 'finish_time' => $now]);
                $this->closeBusinessApproval($info, 'reject');
                $this->addRecord($id, 'reject', $wid, $name, $remark, $now);
                Db::commit();
                return ['id' => $id, 'status' => 2];
            }
            if ($action !== 'pass') {
                throw new \Exception('审批动作不正确');
            }
            Db::name('community_approval_instance_node')->where('id', $node['id'])->update(['status' => 1, 'remark' => $remark, 'approve_time' => $now]);
            $next = Db::name('community_approval_instance_node')->where([
                ['instance_id', '=', $id],
                ['sort', '>', intval($info['current_sort'])],
            ])->order('sort ASC,id ASC')->find();
            if ($next) {
                Db::name('community_approval_instance')->where('id', $id)->update(['current_sort' => $next['sort'], 'update_time' => $now]);
                $status = 0;
            } else {
                $this->applyBusinessEffect($info);
                Db::name('community_approval_instance')->where('id', $id)->update(['status' => 1, 'update_time' => $now, 'finish_time' => $now]);
                $status = 1;
            }
            $this->addRecord($id, 'pass', $wid, $name, $remark, $now);
            Db::commit();
            return ['id' => $id, 'status' => $status];
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    private function applyBusinessEffect($instance)
    {
        $data = json_decode($instance['change_data'] ?? '[]', true);
        if (!is_array($data)) {
            $data = [];
        }
        if (($data['action'] ?? '') === self::BUSINESS_PUBLIC_REVENUE || ($instance['business_type'] ?? '') === self::BUSINESS_PUBLIC_REVENUE) {
            (new PublicRevenueService())->applyApprovedChange($instance, $data);
            return;
        }
        if (($data['action'] ?? '') === 'deposit_release' || $this->isDepositReleaseInstance($instance, $data)) {
            $this->applyDepositReleaseEffect($instance, $data);
            return;
        }
        if (($data['action'] ?? '') === 'order_refund' || $instance['business_type'] === self::BUSINESS_ORDER_REFUND) {
            $this->applyOrderRefundEffect($instance, $data);
            return;
        }
        if (($data['action'] ?? '') === 'order_discard' || $instance['business_type'] === self::BUSINESS_ORDER_DISCARD) {
            $this->applyOrderDiscardEffect($instance, $data);
            return;
        }
        if ($instance['business_type'] === self::BUSINESS_SERVICE_TIME || $instance['business_type'] === self::BUSINESS_DELIVERY_TIME) {
            $roomIds = [];
            $where = [
                ['village_id', '=', intval($instance['village_id'])],
                ['is_del', '=', 0],
            ];
            if (!empty($data['room_ids']) && is_array($data['room_ids'])) {
                $roomIds = array_values(array_unique(array_filter(array_map('intval', $data['room_ids']))));
                $where[] = ['pigcms_id', 'in', $roomIds ?: [0]];
            } elseif (!empty($data['scope_type'])) {
                if (!empty($data['vacancy_id'])) {
                    $where[] = ['pigcms_id', '=', intval($data['vacancy_id'])];
                } elseif (!empty($data['layer_id'])) {
                    $where[] = ['layer_id', '=', intval($data['layer_id'])];
                } elseif (!empty($data['floor_id'])) {
                    $where[] = ['floor_id', '=', intval($data['floor_id'])];
                } elseif (!empty($data['single_id'])) {
                    $where[] = ['single_id', '=', intval($data['single_id'])];
                }
            } else {
                $roomId = intval($data['room_id'] ?? 0);
                if ($roomId > 0) {
                    $where[] = ['pigcms_id', '=', $roomId];
                }
            }
            if (count($where) <= 2) {
                throw new \Exception('缺少房间ID，无法生效业务');
            }
            $save = [];
            if (isset($data['property_service_start_time'])) {
                $save['property_service_start_time'] = $data['property_service_start_time'] ? strtotime($data['property_service_start_time'] . ' 00:00:00') : 0;
            }
            if (isset($data['property_service_end_time'])) {
                $save['property_service_end_time'] = $data['property_service_end_time'] ? strtotime($data['property_service_end_time'] . ' 23:59:59') : 0;
            }
            if (isset($data['contract_time_start'])) {
                $save['contract_time_start'] = $data['contract_time_start'] ? strtotime($data['contract_time_start'] . ' 00:00:00') : 0;
            }
            if (isset($data['contract_time_end'])) {
                $save['contract_time_end'] = $data['contract_time_end'] ? strtotime($data['contract_time_end'] . ' 23:59:59') : 0;
            }
            if (isset($data['house_in_time'])) {
                $save['house_in_time'] = $data['house_in_time'] ? strtotime($data['house_in_time'] . ' 00:00:00') : 0;
            }
            if (isset($data['delivery_time'])) {
                $save['delivery_time'] = $data['delivery_time'] ? strtotime($data['delivery_time'] . ' 00:00:00') : 0;
            }
            if (!$save) {
                throw new \Exception('缺少服务时间变更内容，无法生效业务');
            }
            $affectedRoomIds = Db::name('house_village_user_vacancy')->where($where)->column('pigcms_id');
            Db::name('house_village_user_vacancy')->where($where)->update($save);
            if ($affectedRoomIds && (isset($save['property_service_start_time']) || isset($save['property_service_end_time']) || isset($save['delivery_time']))) {
                $billService = new HouseNewManualBillService();
                foreach ($affectedRoomIds as $affectedRoomId) {
                    try {
                        $billService->syncRoomVacancyFeeBill(['village_id' => intval($instance['village_id'])], ['room_id' => intval($affectedRoomId)]);
                    } catch (\Throwable $e) {
                    }
                }
            }
            return;
        }
        if ($instance['business_type'] === self::BUSINESS_BILL_AMOUNT) {
            $orderId = intval($data['order_id'] ?? ($instance['business_id'] ?? 0));
            $newMoney = isset($data['new_money']) ? round(floatval($data['new_money']), 2) : null;
            if ($orderId <= 0 || $newMoney === null || $newMoney < 0) {
                throw new \Exception('缺少账单金额变更内容，无法生效业务');
            }
            $order = Db::name('house_new_pay_order')->where([
                ['order_id', '=', $orderId],
                ['village_id', '=', intval($instance['village_id'])],
            ])->lock(true)->find();
            if (!$order || intval($order['is_paid']) !== 2 || intval($order['is_discard']) !== 1) {
                throw new \Exception('账单不存在或已缴费、已作废，不能生效金额修改');
            }
            if (isset($data['old_money']) && round(floatval($order['modify_money']), 2) !== round(floatval($data['old_money']), 2)) {
                throw new \Exception('账单金额已发生变化，请重新提交金额修改审批');
            }
            $saveData = [
                'modify_money' => $newMoney,
                'modify_reason' => trim($data['modify_reason'] ?? ($instance['apply_reason'] ?? '')),
                'modify_time' => time(),
                'update_time' => time(),
            ];
            if (in_array($data['discount_type'] ?? '', ['discount', 'cut_money'], true)) {
                $saveData['order_modify_discount'] = json_encode([
                    'approval_id' => intval($instance['id']),
                    'discount_type' => $data['discount_type'],
                    'discount_v' => $data['discount_v'] ?? 0,
                    'old_money' => round(floatval($order['modify_money']), 2),
                    'cut_money' => $data['cut_money'] ?? 0,
                    'modify_money' => $newMoney,
                    'modify_time' => $saveData['modify_time'],
                    'modify_reason' => $saveData['modify_reason'],
                    'rule_digit' => intval($data['rule_digit'] ?? 2),
                ], JSON_UNESCAPED_UNICODE);
            }
            Db::name('house_new_pay_order')->where('order_id', $orderId)->update($saveData);
        }
    }


    private function isDepositReleaseInstance($instance, $data)
    {
        if (($data['action'] ?? '') === 'deposit_release') {
            return true;
        }
        $orderId = intval($data['order_id'] ?? ($instance['business_id'] ?? 0));
        if ($orderId <= 0) {
            return false;
        }
        $detail = Db::name('house_new_bill_deposit_order')->alias('d')
            ->leftJoin('house_new_deposit_category c', 'c.id = d.category_id')
            ->where('d.order_id', $orderId)
            ->field('d.id,d.account_status,c.cate_key')
            ->find();
        if (!$detail || intval($detail['account_status']) !== 1 || empty($detail['cate_key'])) {
            return false;
        }
        if (($instance['business_type'] ?? '') === $detail['cate_key']) {
            return true;
        }
        $businessTypeId = intval($instance['business_type_id'] ?? 0);
        if ($businessTypeId > 0) {
            $typeKey = Db::name('community_approval_business_type')->where('id', $businessTypeId)->value('type_key');
            return $typeKey === $detail['cate_key'];
        }
        return false;
    }

    private function applyDepositReleaseEffect($instance, $data)
    {
        $villageId = intval($instance['village_id'] ?? 0);
        $orderId = intval($data['order_id'] ?? ($instance['business_id'] ?? 0));
        if ($villageId <= 0 || $orderId <= 0) {
            throw new \Exception('缺少押金订单信息，无法生效业务');
        }
        $detail = Db::name('house_new_bill_deposit_order')->where([
            ['order_id', '=', $orderId],
            ['village_id', '=', $villageId],
        ])->lock(true)->find();
        if (!$detail || intval($detail['account_status'] ?? 0) !== 1) {
            throw new \Exception('押金尚未冻结，无法解冻');
        }
        $depositMoney = $this->money4($detail['deposit_money'] ?? 0);
        $refundedMoney = $this->money4($detail['refunded_money'] ?? 0);
        $remainMoney = $this->money4($depositMoney - $refundedMoney);
        $releaseMoney = $this->money4($data['deposit_money'] ?? 0);
        if ($releaseMoney <= 0 || $releaseMoney > $remainMoney) {
            $releaseMoney = $remainMoney;
        }
        if ($releaseMoney <= 0) {
            return;
        }
        $account = Db::name('house_new_bill_deposit_account')->where([
            ['village_id', '=', $villageId],
            ['room_id', '=', intval($detail['room_id'])],
        ])->lock(true)->find();
        if (!$account) {
            throw new \Exception('押金账户不存在，无法解冻');
        }
        $now = time();
        $newRefundedMoney = $this->money4($refundedMoney + $releaseMoney);
        Db::name('house_new_bill_deposit_account')->where('id', $account['id'])->update([
            'total_money' => $this->money4(($account['total_money'] ?? 0) + $releaseMoney),
            'update_time' => $now,
        ]);
        Db::name('house_new_bill_deposit_freeze')->where([
            ['village_id', '=', $villageId],
            ['room_id', '=', intval($detail['room_id'])],
            ['order_id', '=', $orderId],
            ['status', '=', 0],
        ])->update([
            'status' => 2,
            'update_time' => $now,
        ]);
        Db::name('house_new_bill_deposit_order')->where('id', $detail['id'])->update([
            'refunded_money' => $newRefundedMoney,
            'account_status' => $newRefundedMoney >= $depositMoney ? 2 : 1,
            'update_time' => $now,
        ]);
        $oldDepositInfo = Db::name('house_new_deposit_log')->where([
            ['village_id', '=', $villageId],
            ['room_id', '=', intval($detail['room_id'])],
        ])->order('id DESC')->find();
        $beforeMoney = $oldDepositInfo ? $this->money4($oldDepositInfo['total_money'] ?? 0) : 0;
        $oldTotalMoney = $this->money4($beforeMoney + $releaseMoney);
        $payOrder = Db::name('house_new_pay_order')->where([
            ['village_id', '=', $villageId],
            ['order_id', '=', $orderId],
        ])->field('order_no,summary_id')->find();
        $orderNo = $payOrder['order_no'] ?? '';
        if (empty($orderNo) && !empty($payOrder['summary_id'])) {
            $orderNo = Db::name('house_new_pay_order_summary')->where('summary_id', intval($payOrder['summary_id']))->value('order_no') ?: '';
        }
        $existsLog = Db::name('house_new_deposit_log')->where([
            ['village_id', '=', $villageId],
            ['room_id', '=', intval($detail['room_id'])],
            ['order_id', '=', $orderId],
            ['type', '=', 1],
        ])->find();
        if (!$existsLog) {
            Db::name('house_new_deposit_log')->insert([
                'village_id' => $villageId,
                'room_id' => intval($detail['room_id']),
                'order_id' => $orderId,
                'order_no' => $orderNo,
                'type' => 1,
                'before_money' => $beforeMoney,
                'money' => $releaseMoney,
                'total_money' => $oldTotalMoney,
                'add_time' => $now,
                'role_id' => $this->getBusinessRoleId($instance, $data),
            ]);
        }
    }

    private function money4($money)
    {
        return round(floatval($money), 4);
    }

    private function generateBusinessTypeKey($propertyId, $villageId, $typeName)
    {
        $prefix = 'approval';
        for ($i = 0; $i < 20; $i++) {
            $key = $prefix . '_' . substr(md5($typeName . microtime(true) . mt_rand(1000, 999999)), 0, 10);
            $exists = Db::name('community_approval_business_type')->where([
                ['property_id', '=', intval($propertyId)],
                ['village_id', '=', intval($villageId)],
                ['type_key', '=', $key],
                ['is_del', '=', 0],
            ])->find();
            if (!$exists) {
                return $key;
            }
        }
        return $prefix . '_' . uniqid();
    }
    private function getScope($adminUser)
    {
        $villageId = intval($adminUser['village_id'] ?? 0);
        if ($villageId <= 0) {
            throw new \Exception('请先登录到社区');
        }
        $propertyId = intval($adminUser['property_id'] ?? 0);
        if ($propertyId <= 0) {
            $propertyId = intval(Db::name('house_village')->where('village_id', $villageId)->value('property_id'));
        }
        if ($propertyId <= 0) {
            throw new \Exception('未找到当前社区所属物业公司');
        }
        return ['village_id' => $villageId, 'property_id' => $propertyId];
    }

    private function applyOrderRefundEffect($instance, $data)
    {
        $orderId = intval($data['order_id'] ?? ($instance['business_id'] ?? 0));
        $refundType = intval($data['refund_type'] ?? 0);
        $refundMoney = round(floatval($data['refund_money'] ?? 0), 2);
        $refundReason = trim($data['refund_reason'] ?? ($instance['apply_reason'] ?? ''));
        if ($orderId <= 0 || !in_array($refundType, [1, 2], true) || $refundMoney <= 0 || $refundReason === '') {
            throw new \Exception('退款申请数据不完整，无法执行退款');
        }
        $orderInfo = Db::name('house_new_pay_order')->where([
            ['order_id', '=', $orderId],
            ['village_id', '=', intval($instance['village_id'] ?? 0)],
            ['is_paid', '=', 1],
            ['is_discard', '=', 1],
        ])->lock(true)->field('order_id,summary_id,pay_money,refund_money,discount_money,discount_detail,is_refund')->find();
        if (!$orderInfo) {
            throw new \Exception('账单不存在或当前状态已不能退款');
        }
        $cashierService = new HouseNewCashierService();
        $refundMoneyInfo = $cashierService->getOrderRefundMoneyInfo($orderInfo);
        $payMoney = $refundMoneyInfo['total_money'];
        $refundedMoney = round(floatval($orderInfo['refund_money'] ?? 0), 2);
        $remainMoney = $refundMoneyInfo['remain_money'];
        if ($remainMoney <= 0 || $refundMoney > $remainMoney) {
            throw new \Exception('退款金额超过当前账单可退金额');
        }
        if ($refundType === 2 && ($refundedMoney > 0 || abs($refundMoney - $payMoney) > 0.001)) {
            throw new \Exception('退款且还原账单仅支持未退款账单全额退款');
        }
        $adminUser = [
            'property_id' => intval($instance['property_id'] ?? 0),
            'village_id' => intval($instance['village_id'] ?? 0),
            'wid' => $this->getBusinessRoleId($instance, $data),
            'name' => trim($instance['apply_name'] ?? ''),
            'user_name' => trim($data['role_name'] ?? ($instance['apply_name'] ?? '')),
        ];
        $depositService = new HouseNewDepositService();
        $depositService->validateDepositBillRefund($adminUser, $orderId, $refundMoney);
        $extra = [
            'login_role' => trim($data['login_role'] ?? ''),
            'wid' => $this->getBusinessRoleId($instance, $data),
            'apply_uid' => intval($data['role_id'] ?? 0),
            'apply_name' => trim($data['role_name'] ?? ($instance['apply_name'] ?? '')),
            'apply_phone' => trim($data['apply_phone'] ?? ''),
            'opt_type' => 'check_pass_refund',
        ];
        (new HouseNewCashierService())->addRefundInfo(
            $this->getBusinessRoleId($instance, $data),
            $orderId,
            $refundType,
            $refundMoney,
            $refundReason,
            $extra
        );
        $depositService->applyDepositBillRefund($adminUser, $orderId, $refundMoney);
    }

    private function applyOrderDiscardEffect($instance, $data)
    {
        $orderId = intval($data['order_id'] ?? ($instance['business_id'] ?? 0));
        $discardReason = trim($data['discard_reason'] ?? ($instance['apply_reason'] ?? ''));
        $villageId = intval($instance['village_id'] ?? 0);
        if ($orderId <= 0 || $villageId <= 0 || $discardReason === '') {
            throw new \Exception('作废申请数据不完整，无法执行作废');
        }
        $cashierService = new HouseNewCashierService();
        $orderInfo = $cashierService->getInfo([
            ['order_id', '=', $orderId],
            ['village_id', '=', $villageId],
            ['is_discard', '=', 1],
            ['is_paid', '=', 2],
        ], '*');
        if (empty($orderInfo)) {
            throw new \Exception('账单不存在或当前状态已不能作废');
        }
        (new NewPayService())->editFrozenlog(['order_id' => $orderId, 'type' => 3]);
        $cashierService->saveOrder(['order_id' => $orderId], [
            'is_discard' => 2,
            'discard_reason' => $discardReason,
            'update_time' => time(),
            'role_id' => $this->getBusinessRoleId($instance, $data),
            'login_role' => trim($data['login_role'] ?? ''),
            'role_name' => trim($data['role_name'] ?? ($instance['apply_name'] ?? '')),
        ]);
        if (!empty($orderInfo['primitive_order_id'])) {
            $cashierService->discardPaidOrderServiceTime(intval($orderInfo['primitive_order_id']));
        }
    }
    private function getApprovalApproverInfoMap($scope, $wids)
    {
        if (empty($wids)) {
            return [];
        }
        $propertyName = Db::name('house_property')->where('id', intval($scope['property_id']))->value('property_name');
        $villageName = Db::name('house_village')->where('village_id', intval($scope['village_id']))->value('village_name');
        $workers = Db::name('house_worker')->alias('w')
            ->leftJoin('property_group g', 'g.id = w.department_id')
            ->where([
                ['w.property_id', '=', intval($scope['property_id'])],
                ['w.status', '=', 1],
                ['w.is_del', '=', 0],
            ])
            ->where(function ($query) use ($scope) {
                $query->where('w.village_id', 0)->whereOr('w.village_id', intval($scope['village_id']));
            })
            ->whereIn('w.wid', $wids)
            ->field('w.wid,w.account,w.phone,w.department_id,w.village_id,g.name as department_name')
            ->select()->toArray();
        $map = [];
        foreach ($workers as $worker) {
            $organization = [];
            if (!empty($propertyName)) {
                $organization[] = $propertyName;
            }
            if (intval($worker['village_id'] ?? 0) === intval($scope['village_id']) && !empty($villageName)) {
                $organization[] = $villageName;
            }
            if (!empty($worker['department_name'])) {
                $organization[] = $worker['department_name'];
            }
            $map[intval($worker['wid'])] = [
                'organization' => $organization ? implode(' / ', $organization) : ($worker['department_name'] ?? ''),
                'account' => $worker['account'] ?? ($worker['phone'] ?? ''),
                'phone' => $worker['phone'] ?? '',
                'login_role' => $this->getApproverLoginRole($worker),
                'login_role_text' => $this->getApproverLoginRoleText($worker),
            ];
        }
        return $map;
    }
    private function formatApproverOrganization($worker, $scope, $propertyName, $villageName)
    {
        $organization = [];
        if (!empty($propertyName)) {
            $organization[] = $propertyName;
        }
        if (intval($worker['village_id'] ?? 0) === intval($scope['village_id']) && !empty($villageName)) {
            $organization[] = $villageName;
        }
        if (!empty($worker['department_name'])) {
            $organization[] = $worker['department_name'];
        }
        return $organization ? implode(' / ', $organization) : ($worker['department_name'] ?? '');
    }

    private function getApproverLoginRole($worker)
    {
        return intval($worker['village_id'] ?? 0) > 0 ? HouseWorkConst::MANAGE_APP_VILLAGE_WORK : HouseWorkConst::MANAGE_APP_PROPERTY_USER;
    }

    private function getApproverLoginRoleText($worker)
    {
        return intval($worker['village_id'] ?? 0) > 0 ? '小区物业工作人员' : '物业工作人员';
    }

    private function formatApproverTreeTitle($worker)
    {
        $parts = [$worker['name'] ?? ''];
        if (!empty($worker['department_name'])) {
            $parts[] = $worker['department_name'];
        }
        $parts[] = $this->getApproverLoginRoleText($worker);
        return implode(' - ', array_filter($parts, function ($part) {
            return $part !== '';
        }));
    }

    private function getWorker($scope, $wid)
    {
        return Db::name('house_worker')->where([
            ['property_id', '=', intval($scope['property_id'])],
            ['wid', '=', intval($wid)],
            ['status', '=', 1],
            ['is_del', '=', 0],
        ])->where(function ($query) use ($scope) {
            $query->where('village_id', 0)->whereOr('village_id', intval($scope['village_id']));
        })->field('wid,name,account,phone,department_id,village_id')->find();
    }

    private function addRecord($instanceId, $action, $wid, $name, $remark, $time)
    {
        Db::name('community_approval_record')->insert([
            'instance_id' => $instanceId,
            'action' => $action,
            'operator_wid' => $wid,
            'operator_name' => $name,
            'remark' => $remark,
            'add_time' => $time,
        ]);
    }

    private function formatInstance($item, $wid, $applicantIdentity)
    {
        $item['add_time_text'] = $this->timeText($item['add_time'] ?? 0);
        $item['update_time_text'] = $this->timeText($item['update_time'] ?? 0);
        $item['finish_time_text'] = $this->timeText($item['finish_time'] ?? 0);
        $item['change_content_text'] = $this->changeContentText($item['change_data'] ?? '', intval($item['village_id'] ?? 0));
        $type = $this->getFlowBusinessType([
            'property_id' => intval($item['property_id'] ?? 0),
            'village_id' => intval($item['village_id'] ?? 0),
        ], $item);
        $item['business_type_name'] = $type['type_name'] ?? '';
        $item['can_approve'] = intval($item['status']) === 0 && intval($item['current_approver_wid'] ?? 0) === $wid;
        $item['can_withdraw'] = intval($item['status']) === 0 && intval($item['apply_wid']) === $applicantIdentity;
        return $item;
    }

    private function getApplicantIdentity($adminUser)
    {
        $wid = intval($adminUser['wid'] ?? 0);
        if ($wid > 0) {
            return $wid;
        }
        $normalAdminId = intval($adminUser['normal_admin_id'] ?? 0);
        if ($normalAdminId > 0 && $normalAdminId <= self::APPLICANT_SOURCE_ID_MAX) {
            return self::APPLICANT_NORMAL_ADMIN_OFFSET + $normalAdminId;
        }
        $userId = intval($adminUser['_userId'] ?? 0);
        if ($userId > 0 && $userId <= self::APPLICANT_SOURCE_ID_MAX) {
            return self::APPLICANT_ROOT_ADMIN_OFFSET + $userId;
        }
        throw new \Exception('当前登录账号缺少有效的审批发起人身份');
    }

    /**
     * 审批表使用带来源偏移的复合申请人 ID，业务表 role_id 只保存真实账号 ID。
     */
    private function getBusinessRoleId($instance, $data = [])
    {
        $roleId = intval($data['role_id'] ?? 0);
        if ($roleId > 0) {
            return $roleId;
        }
        $applicantIdentity = intval($instance['apply_wid'] ?? 0);
        if ($applicantIdentity >= self::APPLICANT_ROOT_ADMIN_OFFSET
            && $applicantIdentity <= self::APPLICANT_ROOT_ADMIN_OFFSET + self::APPLICANT_SOURCE_ID_MAX) {
            return $applicantIdentity - self::APPLICANT_ROOT_ADMIN_OFFSET;
        }
        if ($applicantIdentity >= self::APPLICANT_NORMAL_ADMIN_OFFSET
            && $applicantIdentity <= self::APPLICANT_NORMAL_ADMIN_OFFSET + self::APPLICANT_SOURCE_ID_MAX) {
            return $applicantIdentity - self::APPLICANT_NORMAL_ADMIN_OFFSET;
        }
        return max(0, $applicantIdentity);
    }

    private function getAdminUserName($adminUser)
    {
        foreach (['name', 'realname', 'account', 'user_name', 'property_name', 'village_name'] as $field) {
            $name = trim(strval($adminUser[$field] ?? ''));
            if ($name !== '') {
                return $name;
            }
        }
        return '';
    }

    private function changeContentText($json, $villageId = 0)
    {
        $data = json_decode($json, true);
        if (!$data) {
            return '';
        }
        if (($data['action'] ?? '') === self::BUSINESS_PUBLIC_REVENUE) {
            return (new PublicRevenueService())->formatApprovalChangeText($data);
        }
        unset($data['effect_table'], $data['effect_where'], $data['effect_data']);
        $lines = [];
        if (!empty($data['scope_type'])) {
            $lines[] = '变更范围：' . ($data['scope_name'] ?? '-');
            if (!empty($data['room_count'])) {
                $lines[] = '房间数量：' . intval($data['room_count']);
            }
        } elseif (!empty($data['room_ids']) && is_array($data['room_ids'])) {
            $roomIds = array_values(array_unique(array_filter(array_map('intval', $data['room_ids']))));
            if ($roomIds) {
                $lines[] = '房间数量：' . count($roomIds);
                $lines[] = '房间列表：';
                foreach ($roomIds as $index => $roomId) {
                    $roomInfo = $this->approvalRoomInfo($roomId, $villageId);
                    $roomText = ($index + 1) . '. ' . ($roomInfo['room_no'] ?? ('房间ID：' . $roomId));
                    if (!empty($roomInfo['owner_name'])) {
                        $roomText .= '，业主姓名：' . $roomInfo['owner_name'];
                    }
                    if (!empty($roomInfo['owner_phone'])) {
                        $roomText .= '，手机号：' . $roomInfo['owner_phone'];
                    }
                    $lines[] = $roomText;
                }
            }
        } elseif (!empty($data['room_id'])) {
            $roomInfo = $this->approvalRoomInfo(intval($data['room_id']), $villageId);
            if (!empty($roomInfo['room_no'])) {
                $lines[] = '房间号：' . $roomInfo['room_no'];
            }
            if (!empty($roomInfo['owner_name'])) {
                $lines[] = '业主姓名：' . $roomInfo['owner_name'];
            }
            if (!empty($roomInfo['owner_phone'])) {
                $lines[] = '手机号：' . $roomInfo['owner_phone'];
            }
        }
        $fieldMap = [
            'room_id' => '房间ID',
            'property_service_start_time' => '物业服务开始时间',
            'property_service_end_time' => '物业服务结束时间',
            'old_property_service_start_time' => '原物业服务开始时间',
            'old_property_service_end_time' => '原物业服务结束时间',
            'delivery_time' => '交房时间',
            'old_delivery_time' => '原交房时间',
            'contract_time_start' => '合同开始时间',
            'contract_time_end' => '合同结束时间',
            'old_contract_time_start' => '原合同开始时间',
            'old_contract_time_end' => '原合同结束时间',
            'house_in_time' => '入住时间',
            'old_house_in_time' => '原入住时间',
            'order_id' => '账单ID',
            'new_money' => '新金额',
            'old_money' => '原金额',
            'apply_reason' => '申请原因',
            'total_money' => '账单金额',
            'refund_type_text' => '退款模式',
            'refund_money' => '退款金额',
            'refund_reason' => '退款原因',
            'discard_reason' => '作废原因',
        ];
        foreach ($data as $key => $value) {
            if (in_array($key, ['action', 'room_id', 'room_ids', 'batch_change', 'change_mode', 'room_count', 'scope_type', 'scope_name', 'single_id', 'floor_id', 'layer_id', 'vacancy_id', 'refund_type', 'role_id', 'login_role', 'role_name', 'apply_phone'], true)) {
                continue;
            }
            $label = $fieldMap[$key] ?? $key;
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            $lines[] = $label . '：' . ($value === '' || $value === null ? '-' : $value);
        }
        return implode("\n", $lines);
    }

    private function closeBusinessApproval($instance, $action)
    {
        if (($instance['business_type'] ?? '') === self::BUSINESS_PUBLIC_REVENUE) {
            (new PublicRevenueService())->closeApproval($instance, $action);
        }
    }

    private function currentBillInfoText($instance)
    {
        $data = json_decode($instance['change_data'] ?? '', true);
        $orderId = intval($data['order_id'] ?? 0);
        if ($orderId <= 0 && in_array($instance['business_type'] ?? '', [self::BUSINESS_BILL_AMOUNT, self::BUSINESS_ORDER_REFUND, self::BUSINESS_ORDER_DISCARD], true)) {
            $orderId = intval($instance['business_id'] ?? 0);
        }
        if ($orderId <= 0) {
            return '';
        }
        $order = Db::name('house_new_pay_order')->alias('o')
            ->leftJoin('house_new_charge_project p', 'p.id = o.project_id')
            ->leftJoin('house_new_charge_rule r', 'r.id = o.rule_id')
            ->where([
                ['o.order_id', '=', $orderId],
                ['o.village_id', '=', intval($instance['village_id'] ?? 0)],
            ])->field('o.order_id,o.room_id,o.position_id,o.order_name,o.name,o.phone,o.total_money,o.modify_money,o.pay_money,o.is_paid,o.is_discard,o.service_start_time,o.service_end_time,p.name as project_name,r.charge_name')
            ->find();
        if (!$order) {
            return '账单ID：' . $orderId . "\n账单状态：账单不存在";
        }
        $targetText = '';
        if (intval($order['room_id'] ?? 0) > 0) {
            $roomInfo = $this->approvalRoomInfo(intval($order['room_id']), intval($instance['village_id'] ?? 0));
            $targetText = $roomInfo['room_no'] ?? '';
        } elseif (intval($order['position_id'] ?? 0) > 0) {
            $targetText = strval(Db::name('house_village_parking_position')->where('position_id', intval($order['position_id']))->value('position_num') ?: '');
        }
        $projectName = trim(strval($order['project_name'] ?? '')) ?: trim(strval($order['order_name'] ?? ''));
        $ruleName = trim(strval($order['charge_name'] ?? '')) ?: trim(strval($order['order_name'] ?? ''));
        $statusText = intval($order['is_discard'] ?? 1) !== 1 ? '已作废' : (intval($order['is_paid'] ?? 2) === 1 ? '已缴费' : '待缴费');
        $lines = [
            '账单ID：' . $order['order_id'],
            '收费对象：' . ($targetText ?: '-'),
            '缴费人：' . (trim(strval($order['name'] ?? '')) ?: '-'),
            '联系电话：' . (trim(strval($order['phone'] ?? '')) ?: '-'),
            '收费项目：' . ($projectName ?: '-'),
            '收费标准：' . ($ruleName ?: '-'),
            '计费时间：' . $this->billDateText($order['service_start_time'] ?? 0) . ' 至 ' . $this->billDateText($order['service_end_time'] ?? 0),
            '账单金额：' . number_format(floatval($order['total_money'] ?? 0), 2, '.', ''),
            '应收金额：' . number_format(floatval($order['modify_money'] ?? 0), 2, '.', ''),
            '已收金额：' . number_format(floatval($order['pay_money'] ?? 0), 2, '.', ''),
            '账单状态：' . $statusText,
        ];
        return implode("\n", $lines);
    }

    private function billDateText($time)
    {
        return intval($time) > 1 ? date('Y-m-d', intval($time)) : '-';
    }

    private function approvalRoomInfo($roomId, $villageId)
    {
        $where = [['pigcms_id', '=', $roomId]];
        if ($villageId > 0) {
            $where[] = ['village_id', '=', $villageId];
        }
        $room = Db::name('house_village_user_vacancy')->where($where)
            ->field('pigcms_id,village_id,single_id,floor_id,layer_id,room,usernum,property_number,name,phone')
            ->find();
        if (!$room) {
            return [];
        }
        $bind = Db::name('house_village_user_bind')->where([
            ['vacancy_id', '=', $roomId],
            ['status', '=', 1],
        ])->whereIn('type', [0, 3])->field('name,phone')->order('type ASC,pigcms_id DESC')->find();
        if (!$bind) {
            $bind = Db::name('house_village_user_bind')->where([
                ['vacancy_id', '=', $roomId],
                ['status', '=', 1],
            ])->field('name,phone')->order('pigcms_id DESC')->find();
        }
        $singleName = !empty($room['single_id']) ? Db::name('house_village_single')->where('id', intval($room['single_id']))->value('single_name') : '';
        $floorName = !empty($room['floor_id']) ? Db::name('house_village_floor')->where('floor_id', intval($room['floor_id']))->value('floor_name') : '';
        $layerName = !empty($room['layer_id']) ? Db::name('house_village_layer')->where('id', intval($room['layer_id']))->value('layer_name') : '';
        $roomName = $room['room'] ?: ($room['usernum'] ?: ($room['property_number'] ?? ''));
        $roomParts = array_filter([$singleName, $floorName, $layerName, $roomName], function ($value) {
            return $value !== '' && $value !== null;
        });
        return [
            'room_no' => implode('', $roomParts),
            'owner_name' => $bind['name'] ?? ($room['name'] ?? ''),
            'owner_phone' => $bind['phone'] ?? ($room['phone'] ?? ''),
        ];
    }

    private function getFlowBusinessType($scope, $flow)
    {
        if (!empty($flow['business_type_id'])) {
            $type = Db::name('community_approval_business_type')->where([
                ['id', '=', intval($flow['business_type_id'])],
                ['property_id', '=', $scope['property_id']],
                ['village_id', '=', $scope['village_id']],
                ['is_del', '=', 0],
            ])->find();
            if ($type) {
                return $type;
            }
        }
        if (!empty($flow['business_type'])) {
            return Db::name('community_approval_business_type')->where([
                ['property_id', '=', $scope['property_id']],
                ['village_id', '=', $scope['village_id']],
                ['type_key', '=', $flow['business_type']],
                ['is_del', '=', 0],
            ])->find();
        }
        return [];
    }

    public function hasActiveFlow($adminUser, $businessType)
    {
        $scope = $this->getScope($adminUser);
        $businessTypeInfo = $this->getBusinessTypeByKey($adminUser, $businessType);
        if (!$businessTypeInfo) {
            return false;
        }
        $count = Db::name('community_approval_flow')->where([
            ['property_id', '=', $scope['property_id']],
            ['village_id', '=', $scope['village_id']],
            ['business_type_id', '=', intval($businessTypeInfo['id'])],
            ['status', '=', 1],
            ['is_del', '=', 0],
        ])->count();
        if ($count > 0) {
            return true;
        }
        return Db::name('community_approval_flow')->where([
            ['property_id', '=', $scope['property_id']],
            ['village_id', '=', $scope['village_id']],
            ['business_type', '=', trim($businessType)],
            ['status', '=', 1],
            ['is_del', '=', 0],
        ])->count() > 0;
    }

    private function timeText($time)
    {
        return intval($time) > 0 ? date('Y-m-d H:i:s', intval($time)) : '';
    }

    private function nodeStatusText($status)
    {
        $map = [0 => '待审批', 1 => '已通过', 2 => '已驳回', 3 => '已撤回'];
        return $map[intval($status)] ?? '未知';
    }
}

