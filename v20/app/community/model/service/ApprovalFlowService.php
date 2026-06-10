<?php

namespace app\community\model\service;

use think\facade\Db;

class ApprovalFlowService
{
    const BUSINESS_SERVICE_TIME = 'service_time_change';
    const BUSINESS_DELIVERY_TIME = 'delivery_time_change';
    const BUSINESS_BILL_AMOUNT = 'bill_amount_change';

    private function defaultBusinessTypes()
    {
        return [
            ['type_key' => self::BUSINESS_SERVICE_TIME, 'type_name' => '调整物业服务时间'],
            ['type_key' => self::BUSINESS_DELIVERY_TIME, 'type_name' => '调整交房时间'],
            ['type_key' => self::BUSINESS_BILL_AMOUNT, 'type_name' => '修改账单金额'],
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
        $where = [
            ['property_id', '=', $scope['property_id']],
            ['village_id', '=', $scope['village_id']],
            ['type_key', '=', $typeKey],
            ['is_del', '=', 0],
        ];
        if ($id > 0) {
            $where[] = ['id', '<>', $id];
        }
        if (Db::name('community_approval_business_type')->where($where)->find()) {
            throw new \Exception('该业务类型标识已存在');
        }
        $now = time();
        $data = [
            'property_id' => $scope['property_id'],
            'village_id' => $scope['village_id'],
            'type_name' => $typeName,
            'type_key' => $typeKey,
            'status' => intval($params['status'] ?? 1) ? 1 : 0,
            'update_time' => $now,
        ];
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
            Db::name('community_approval_business_type')->where('id', $id)->update($data);
        } else {
            $data['is_system'] = 0;
            $data['is_del'] = 0;
            $data['add_time'] = $now;
            $id = Db::name('community_approval_business_type')->insertGetId($data);
        }
        return ['id' => $id];
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
                $worker = $this->getWorker($scope['property_id'], $wid);
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
                $query->where('w.village_id', 0)
                    ->whereOr('w.village_id', $scope['village_id']);
            })
            ->field('w.wid,w.name,w.phone,w.department_id,w.village_id,g.name as department_name')
            ->order('g.sort DESC,w.wid DESC')
            ->select()->toArray();
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
                continue;
            }
            $groupMap[$departmentId]['children'][] = [
                'title' => $worker['name'] . (!empty($worker['department_name']) ? ' - ' . $worker['department_name'] : ''),
                'value' => intval($worker['wid']),
                'key' => 'worker_' . $worker['wid'],
                'is_worker' => 1,
                'wid' => intval($worker['wid']),
                'name' => $worker['name'],
                'department_name' => $worker['department_name'] ?? '',
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
        $now = time();
        $applyWid = intval($adminUser['wid'] ?? 0);
        $applyName = $adminUser['name'] ?? ($adminUser['realname'] ?? ($adminUser['account'] ?? ''));
        Db::startTrans();
        try {
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
                Db::name('community_approval_instance_node')->insert([
                    'instance_id' => $instanceId,
                    'flow_node_id' => $node['id'],
                    'sort' => $node['sort'],
                    'node_name' => $node['node_name'],
                    'approver_wid' => $node['approver_wid'],
                    'approver_name' => $node['approver_name'],
                    'status' => 0,
                    'remark' => '',
                    'approve_time' => 0,
                    'add_time' => $now,
                ]);
            }
            $this->addRecord($instanceId, 'submit', $applyWid, $applyName, trim($params['apply_remark'] ?? ''), $now);
            Db::commit();
        } catch (\Exception $e) {
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
            $query->where('i.apply_wid', $wid);
        } elseif ($scopeType === 'reviewed') {
            $query->whereIn('i.id', $reviewedIds ?: [0]);
        } else {
            $query->where(function ($query) use ($wid, $reviewedIds) {
                $query->where('i.apply_wid', $wid)
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
            $item = $this->formatInstance($item, $wid);
        }
        return ['list' => $list, 'count' => $count, 'page_size' => $pageSize];
    }

    public function instanceDetail($adminUser, $id)
    {
        $scope = $this->getScope($adminUser);
        $wid = intval($adminUser['wid'] ?? 0);
        $info = Db::name('community_approval_instance')->alias('i')
            ->leftJoin('community_approval_instance_node n', 'n.instance_id = i.id AND n.sort = i.current_sort')
            ->field('i.*,n.node_name as current_node_name,n.approver_wid as current_approver_wid')
            ->where([['i.id', '=', intval($id)], ['i.property_id', '=', $scope['property_id']], ['i.village_id', '=', $scope['village_id']]])
            ->find();
        if (!$info) {
            throw new \Exception('审批记录不存在');
        }
        $isMine = intval($info['apply_wid']) === $wid;
        $isCurrentApprover = intval($info['status']) === 0 && intval($info['current_approver_wid'] ?? 0) === $wid;
        $hasReviewed = Db::name('community_approval_instance_node')->where([
            ['instance_id', '=', intval($info['id'])],
            ['approver_wid', '=', $wid],
        ])->whereIn('status', [1, 2])->count() > 0;
        if (!$isMine && !$isCurrentApprover && !$hasReviewed) {
            throw new \Exception('审批记录不存在');
        }
        $info = $this->formatInstance($info, $wid);
        $nodes = Db::name('community_approval_instance_node')->where('instance_id', $info['id'])->order('sort ASC,id ASC')->select()->toArray();
        foreach ($nodes as &$node) {
            $node['status_text'] = $this->nodeStatusText($node['status']);
            $node['approve_time_text'] = $this->timeText($node['approve_time'] ?? 0);
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
        $name = $adminUser['name'] ?? ($adminUser['realname'] ?? ($adminUser['account'] ?? ''));
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
                if (intval($info['apply_wid']) !== $wid) {
                    throw new \Exception('只有发起人可以撤回');
                }
                Db::name('community_approval_instance')->where('id', $id)->update(['status' => 3, 'update_time' => $now, 'finish_time' => $now]);
                $this->addRecord($id, 'withdraw', $wid, $name, $remark, $now);
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
            Db::name('house_new_pay_order')->where([
                ['order_id', '=', $orderId],
                ['village_id', '=', intval($instance['village_id'])],
            ])->update([
                'total_money' => $newMoney,
                'modify_money' => $newMoney,
                'pay_money' => $newMoney,
                'update_time' => time(),
            ]);
        }
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

    private function getWorker($propertyId, $wid)
    {
        return Db::name('house_worker')->where([['property_id', '=', $propertyId], ['wid', '=', $wid], ['status', '=', 1]])->field('wid,name')->find();
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

    private function formatInstance($item, $wid)
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
        $item['can_withdraw'] = intval($item['status']) === 0 && intval($item['apply_wid']) === $wid;
        return $item;
    }

    private function changeContentText($json, $villageId = 0)
    {
        $data = json_decode($json, true);
        if (!$data) {
            return '';
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
        ];
        foreach ($data as $key => $value) {
            if (in_array($key, ['room_id', 'room_ids', 'batch_change', 'change_mode', 'room_count', 'scope_type', 'scope_name', 'single_id', 'floor_id', 'layer_id', 'vacancy_id'], true)) {
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

