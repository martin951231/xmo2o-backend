<?php

namespace app\community\model\service;

use think\facade\Db;

class PublicRevenueService
{
    const SUBJECT_INCOME = 1;
    const SUBJECT_EXPENSE = 2;
    const PERIOD_DRAFT = 0;
    const PERIOD_EFFECTIVE = 1;
    const SOURCE_MANUAL = 1;
    const SOURCE_AUTO = 2;

    public function subjectList($adminUser, $params = [])
    {
        $villageId = $this->villageId($adminUser);
        $this->ensureDefaultSubjects($villageId);
        $where = [
            ['village_id', '=', $villageId],
            ['is_del', '=', 0],
        ];
        $type = intval($params['type'] ?? 0);
        if (in_array($type, [self::SUBJECT_INCOME, self::SUBJECT_EXPENSE], true)) {
            $where[] = ['type', '=', $type];
        }
        return Db::name('public_revenue_subject')->where($where)->order('type ASC,sort DESC,id ASC')->select()->toArray();
    }

    public function saveSubject($adminUser, $params)
    {
        $villageId = $this->villageId($adminUser);
        $id = intval($params['id'] ?? 0);
        $type = intval($params['type'] ?? 0);
        $name = trim($params['name'] ?? '');
        if (!in_array($type, [self::SUBJECT_INCOME, self::SUBJECT_EXPENSE], true) || $name === '') {
            throw new \Exception('请填写正确的收支类型和项目名称');
        }
        if (mb_strlen($name) > 50) {
            throw new \Exception('项目名称不能超过50个字符');
        }
        $repeat = Db::name('public_revenue_subject')->where([
            ['village_id', '=', $villageId],
            ['type', '=', $type],
            ['name', '=', $name],
            ['is_del', '=', 0],
        ]);
        if ($id > 0) {
            $repeat->where('id', '<>', $id);
        }
        if ($repeat->find()) {
            throw new \Exception('同类型下已存在同名项目');
        }
        $now = time();
        $data = [
            'village_id' => $villageId,
            'type' => $type,
            'name' => $name,
            'sort' => intval($params['sort'] ?? 0),
            'status' => intval($params['status'] ?? 1) ? 1 : 0,
            'update_time' => $now,
        ];
        if ($id > 0) {
            $info = Db::name('public_revenue_subject')->where([
                ['id', '=', $id],
                ['village_id', '=', $villageId],
                ['is_del', '=', 0],
            ])->find();
            if (!$info) {
                throw new \Exception('收支项目不存在');
            }
            Db::name('public_revenue_subject')->where('id', $id)->update($data);
        } else {
            $data['create_time'] = $now;
            $id = Db::name('public_revenue_subject')->insertGetId($data);
        }
        $this->addLog($adminUser, 'subject_save', $id, [], $data, '');
        return ['id' => $id];
    }

    public function subjectStatus($adminUser, $params)
    {
        $villageId = $this->villageId($adminUser);
        $id = intval($params['id'] ?? 0);
        $status = intval($params['status'] ?? 0) ? 1 : 0;
        $info = Db::name('public_revenue_subject')->where([
            ['id', '=', $id], ['village_id', '=', $villageId], ['is_del', '=', 0],
        ])->find();
        if (!$info) {
            throw new \Exception('收支项目不存在');
        }
        Db::name('public_revenue_subject')->where('id', $id)->update(['status' => $status, 'update_time' => time()]);
        $this->addLog($adminUser, 'subject_status', $id, ['status' => $info['status']], ['status' => $status], '');
        return ['id' => $id, 'status' => $status];
    }

    public function deleteSubject($adminUser, $params)
    {
        $villageId = $this->villageId($adminUser);
        $id = intval($params['id'] ?? 0);
        $info = Db::name('public_revenue_subject')->where([
            ['id', '=', $id], ['village_id', '=', $villageId], ['is_del', '=', 0],
        ])->find();
        if (!$info) {
            throw new \Exception('收支项目不存在');
        }
        if (Db::name('public_revenue_detail')->where(['village_id' => $villageId, 'subject_id' => $id])->find()) {
            throw new \Exception('项目已被使用，只能禁用');
        }
        Db::name('public_revenue_subject')->where('id', $id)->update(['is_del' => 1, 'status' => 0, 'update_time' => time()]);
        $this->addLog($adminUser, 'subject_delete', $id, $info, [], '');
        return ['id' => $id];
    }

    public function periodDetail($adminUser, $params)
    {
        $villageId = $this->villageId($adminUser);
        [$year, $quarter] = $this->periodKey($params);
        $period = Db::name('public_revenue_period')->where([
            ['village_id', '=', $villageId], ['year', '=', $year], ['quarter', '=', $quarter],
        ])->find();
        $details = [];
        if ($period) {
            $details = Db::name('public_revenue_detail')->where([
                ['village_id', '=', $villageId], ['period_id', '=', $period['id']],
            ])->order('type ASC,sort DESC,id ASC')->select()->toArray();
            $period['draft'] = $this->decode($period['draft_data'] ?? '');
            $period['pending_status'] = $this->pendingStatus($period);
        }
        return [
            'period' => $period ?: null,
            'details' => $details,
            'subjects' => $this->subjectList($adminUser),
            'quarter_opening' => $this->calculateOpening($villageId, $year, $quarter),
            'quarter_effective_status' => $this->quarterEffectiveStatus($villageId, $year),
        ];
    }

    public function savePeriod($adminUser, $params)
    {
        $villageId = $this->villageId($adminUser);
        [$year, $quarter] = $this->periodKey($params);
        $saveMode = trim(strval($params['save_mode'] ?? ''));
        if ($saveMode !== '' && !in_array($saveMode, ['draft', 'submit'], true)) {
            throw new \Exception('保存方式不正确');
        }
        // 新版调用必须明确声明保存方式；兼容旧版页面时才读取 submit 参数。
        $submit = $saveMode !== '' ? $saveMode === 'submit' : intval($params['submit'] ?? 0) === 1;
        $this->validateQuarterSequence($villageId, $year, $quarter);
        if ($submit) {
            $this->validateQuarterStarted($year, $quarter);
        }
        if ($submit && intval($params['confirmed'] ?? 0) !== 1) {
            throw new \Exception('请二次确认后再提交');
        }
        $candidate = $this->normalizeCandidate($villageId, $year, $quarter, $params);
        $period = Db::name('public_revenue_period')->where([
            ['village_id', '=', $villageId], ['year', '=', $year], ['quarter', '=', $quarter],
        ])->find();
        Db::startTrans();
        try {
            $now = time();
            if (!$period) {
                $periodId = Db::name('public_revenue_period')->insertGetId([
                    'village_id' => $villageId,
                    'year' => $year,
                    'quarter' => $quarter,
                    'status' => self::PERIOD_DRAFT,
                    'source_type' => self::SOURCE_MANUAL,
                    'create_by' => $this->operatorId($adminUser),
                    'update_by' => $this->operatorId($adminUser),
                    'create_time' => $now,
                    'update_time' => $now,
                ]);
                $period = ['id' => $periodId, 'village_id' => $villageId, 'status' => self::PERIOD_DRAFT];
            }
            $result = $this->savePeriodChange($adminUser, $period, $candidate, $submit, trim($params['reason'] ?? ''));
            Db::commit();
            return $result;
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    private function savePeriodChange($adminUser, $period, $candidate, $submit, $reason)
    {
        $period = Db::name('public_revenue_period')->where([
            ['id', '=', intval($period['id'])],
            ['village_id', '=', intval($period['village_id'])],
        ])->lock(true)->find();
        if (!$period) {
            throw new \Exception('公共收益季度数据不存在');
        }
        if ($this->pendingStatus($period) === 0) {
            throw new \Exception('当前季度已有申请正在审批中');
        }
        Db::name('public_revenue_period')->where('id', $period['id'])->update([
            'draft_data' => json_encode($candidate, JSON_UNESCAPED_UNICODE),
            'update_by' => $this->operatorId($adminUser),
            'update_time' => time(),
        ]);
        $effective = intval($period['status']) === self::PERIOD_EFFECTIVE;
        if (!$submit) {
            if (!$effective) {
                $this->replacePeriod(intval($period['id']), intval($period['village_id']), $candidate, $adminUser);
            }
            $this->addLog($adminUser, $effective ? 'adjustment_draft_save' : 'draft_save', intval($period['id']), [], $candidate, '');
            return ['id' => intval($period['id']), 'effective' => $effective ? 1 : 0, 'approval_required' => 0, 'save_mode' => 'draft'];
        }
        $before = $this->periodSnapshot(intval($period['village_id']), intval($period['id']));
        if (!$this->hasPublicRevenueApprovalFlow($adminUser)) {
            throw new \Exception('还没有配置调整公共收益流程，请前往配置再重新提交');
        }
        if ($reason === '' && !$effective) {
            $reason = '首次提交公共收益';
        }
        if ($reason === '') {
            throw new \Exception('本次调整需要审批，请填写调整原因');
        }
        $changeData = [
            'action' => ApprovalFlowService::BUSINESS_PUBLIC_REVENUE,
            'period_id' => intval($period['id']),
            'year' => intval($period['year']),
            'quarter' => intval($period['quarter']),
            'apply_reason' => $reason,
            'before' => $before,
            'candidate' => $candidate,
            'role_id' => $this->operatorId($adminUser),
        ];
        $approval = (new ApprovalFlowService())->createInstance($adminUser, [
            'business_type' => ApprovalFlowService::BUSINESS_PUBLIC_REVENUE,
            'business_id' => intval($period['id']),
            'title' => ($effective ? '修改公共收益（' : '提交公共收益（') . $period['year'] . '年' . $this->quarterName(intval($period['quarter'])) . '）',
            'apply_reason' => $reason,
            'apply_remark' => $reason,
            'change_data' => $changeData,
        ]);
        Db::name('public_revenue_period')->where('id', $period['id'])->update([
            'pending_approval_id' => intval($approval['id'] ?? 0),
            'update_time' => time(),
        ]);
        $this->addLog($adminUser, $effective ? 'adjustment_submit' : 'first_submit', intval($period['id']), $before, $candidate, $reason);
        return ['id' => intval($period['id']), 'effective' => $effective ? 1 : 0, 'approval_required' => 1, 'approval_id' => intval($approval['id'] ?? 0), 'save_mode' => 'submit'];
    }

    private function hasPublicRevenueApprovalFlow($adminUser)
    {
        $villageId = $this->villageId($adminUser);
        $propertyId = intval($adminUser['property_id'] ?? 0);
        if ($propertyId <= 0) {
            $propertyId = intval(Db::name('house_village')->where('village_id', $villageId)->value('property_id'));
        }
        $businessTypeId = intval(Db::name('community_approval_business_type')->where([
            ['property_id', '=', $propertyId],
            ['village_id', '=', $villageId],
            ['type_key', '=', ApprovalFlowService::BUSINESS_PUBLIC_REVENUE],
            ['status', '=', 1],
            ['is_del', '=', 0],
        ])->value('id'));
        $query = Db::name('community_approval_flow')->where([
            ['property_id', '=', $propertyId],
            ['village_id', '=', $villageId],
            ['status', '=', 1],
            ['is_del', '=', 0],
        ])->where(function ($query) use ($businessTypeId) {
            if ($businessTypeId > 0) {
                $query->where('business_type_id', $businessTypeId)
                    ->whereOr('business_type', ApprovalFlowService::BUSINESS_PUBLIC_REVENUE);
            } else {
                $query->where('business_type', ApprovalFlowService::BUSINESS_PUBLIC_REVENUE);
            }
        });
        $flow = $query->find();
        if (!$flow) {
            return false;
        }
        return Db::name('community_approval_flow_node')->where([
            ['flow_id', '=', intval($flow['id'])],
            ['status', '=', 1],
            ['is_del', '=', 0],
        ])->count() > 0;
    }

    public function applyApprovedChange($instance, $data)
    {
        $periodId = intval($data['period_id'] ?? ($instance['business_id'] ?? 0));
        $villageId = intval($instance['village_id'] ?? 0);
        $period = Db::name('public_revenue_period')->where([
            ['id', '=', $periodId], ['village_id', '=', $villageId],
        ])->lock(true)->find();
        if (!$period || empty($data['candidate']) || !is_array($data['candidate'])) {
            throw new \Exception('公共收益调整数据不存在，无法生效');
        }
        $this->validateQuarterSequence($villageId, intval($period['year']), intval($period['quarter']));
        $this->validateQuarterStarted(intval($period['year']), intval($period['quarter']));
        if (intval($period['pending_approval_id'] ?? 0) !== intval($instance['id'] ?? 0)) {
            throw new \Exception('公共收益调整申请已失效');
        }
        $adminUser = [
            'village_id' => $villageId,
            '_userId' => intval($data['role_id'] ?? 0),
            'user_name' => $instance['apply_name'] ?? '',
        ];
        $before = $this->periodSnapshot($villageId, $periodId);
        $this->replacePeriod($periodId, $villageId, $data['candidate'], $adminUser);
        Db::name('public_revenue_period')->where('id', $periodId)->update([
            'status' => self::PERIOD_EFFECTIVE, 'submit_time' => time(),
            'draft_data' => '', 'pending_approval_id' => 0,
            'update_by' => intval($data['role_id'] ?? 0), 'update_time' => time(),
        ]);
        $this->recalculateFrom($villageId, intval($period['year']));
        $after = $this->periodSnapshot($villageId, $periodId);
        $this->addLog($adminUser, 'adjustment_approved', $periodId, $before, $after, trim($instance['apply_reason'] ?? ''));
    }

    public function closeApproval($instance, $action)
    {
        if (($instance['business_type'] ?? '') !== ApprovalFlowService::BUSINESS_PUBLIC_REVENUE) {
            return;
        }
        $periodId = intval($instance['business_id'] ?? 0);
        Db::name('public_revenue_period')->where([
            ['id', '=', $periodId],
            ['village_id', '=', intval($instance['village_id'] ?? 0)],
            ['pending_approval_id', '=', intval($instance['id'] ?? 0)],
        ])->update(['pending_approval_id' => 0, 'update_time' => time()]);
    }

    public function formatApprovalChangeText($data)
    {
        $candidate = is_array($data['candidate'] ?? null) ? $data['candidate'] : [];
        $before = is_array($data['before'] ?? null) ? $data['before'] : [];
        $lines = [
            '调整期间：' . intval($data['year'] ?? 0) . '年' . $this->quarterName(intval($data['quarter'] ?? 0)),
            '调整原因：' . ($data['apply_reason'] ?? '-'),
        ];
        if (intval($data['quarter'] ?? 0) === 0) {
            $lines[] = '年初结余：' . $this->money($before['opening_actual_balance'] ?? 0) . ' → ' . $this->money($candidate['opening_actual_balance'] ?? 0) . ' 元';
        } else {
            $lines = array_merge($lines, $this->detailChangeLines($before['details'] ?? [], $candidate['details'] ?? []));
        }
        return implode("\n", $lines);
    }

    public function logList($adminUser, $params)
    {
        $villageId = $this->villageId($adminUser);
        $page = max(1, intval($params['page'] ?? 1));
        $limit = max(1, min(100, intval($params['page_size'] ?? 20)));
        $query = Db::name('public_revenue_operation_log')->where('village_id', $villageId);
        $count = (clone $query)->count();
        $list = $query->order('id DESC')->page($page, $limit)->select()->toArray();
        foreach ($list as &$item) {
            $item['add_time_text'] = $item['add_time'] ? date('Y-m-d H:i:s', $item['add_time']) : '';
        }
        return ['list' => $list, 'count' => $count, 'page_size' => $limit];
    }

    public function publicOverview($villageId)
    {
        $villageId = intval($villageId);
        if ($villageId <= 0) {
            throw new \Exception('缺少小区ID');
        }
        $villageName = Db::name('house_village')->where('village_id', $villageId)->value('village_name');
        $pageConfig = $this->publicPageConfig($villageId);
        $currentYear = intval(date('Y'));
        $currentQuarter = $this->currentQuarter();
        $period = Db::name('public_revenue_period')->where([
            ['village_id', '=', $villageId], ['status', '=', self::PERIOD_EFFECTIVE], ['year', '=', $currentYear],
        ])->whereBetween('quarter', [0, $currentQuarter])
            ->order('year DESC,quarter DESC,id DESC')
            ->find();
        if (!$period) {
            $period = Db::name('public_revenue_period')->where([
                ['village_id', '=', $villageId], ['status', '=', self::PERIOD_EFFECTIVE], ['year', '<', $currentYear],
            ])->whereBetween('quarter', [0, 4])
                ->order('year DESC,quarter DESC,id DESC')
                ->find();
        }
        if (!$period) {
            [$year, $quarter] = $this->previousQuarter();
            return ['has_data' => 0, 'annual_settled' => 0, 'village_name' => $villageName ?: '', 'year' => $year, 'quarter' => $quarter, 'as_of_date' => $this->quarterEndDate($year, $quarter), 'page_config' => $pageConfig, 'background_color' => $pageConfig['background_color'], 'background_image' => $pageConfig['background_image']];
        }
        $year = intval($period['year']);
        $quarter = intval($period['quarter']);
        $opening = Db::name('public_revenue_period')->where([
            ['village_id', '=', $villageId], ['year', '=', $year], ['quarter', '=', 0], ['status', '=', self::PERIOD_EFFECTIVE],
        ])->find() ?: [];
        $annual = [];
        if ($quarter > 0) {
            $annual = Db::name('public_revenue_period')->where([
                ['village_id', '=', $villageId], ['year', '=', $year], ['status', '=', self::PERIOD_EFFECTIVE],
            ])->whereBetween('quarter', [1, $quarter])
                ->field('SUM(accrual_income_total) AS accrual_income_total,SUM(actual_income_total) AS actual_income_total,SUM(accrual_expense_total) AS accrual_expense_total,SUM(actual_expense_total) AS actual_expense_total')
                ->find() ?: [];
        }
        $actualBalance = ($opening['opening_actual_balance'] ?? 0)
            + ($annual['actual_income_total'] ?? 0)
            - ($annual['actual_expense_total'] ?? 0);
        return [
            'has_data' => 1,
            'annual_settled' => 1,
            'village_name' => $villageName ?: '',
            'year' => $year,
            'quarter' => $quarter,
            'quarter_name' => $this->quarterName($quarter),
            'as_of_date' => $quarter > 0 ? $this->quarterEndDate($year, $quarter) : $year . '年1月1日',
            'opening_accrual_balance' => $this->money($opening['opening_accrual_balance'] ?? 0),
            'opening_actual_balance' => $this->money($opening['opening_actual_balance'] ?? 0),
            'annual_accrual_income' => $this->money($annual['accrual_income_total'] ?? 0),
            'annual_actual_income' => $this->money($annual['actual_income_total'] ?? 0),
            'annual_accrual_expense' => $this->money($annual['accrual_expense_total'] ?? 0),
            'annual_actual_expense' => $this->money($annual['actual_expense_total'] ?? 0),
            'annual_accrual_net' => $this->money(($annual['accrual_income_total'] ?? 0) - ($annual['accrual_expense_total'] ?? 0)),
            'annual_actual_net' => $this->money(($annual['actual_income_total'] ?? 0) - ($annual['actual_expense_total'] ?? 0)),
            'actual_balance' => $this->money($actualBalance),
            'page_config' => $pageConfig,
            'background_color' => $pageConfig['background_color'],
            'background_image' => $pageConfig['background_image'],
        ];
    }

    public function publicQuarterDetail($villageId)
    {
        $villageId = intval($villageId);
        if ($villageId <= 0) {
            throw new \Exception('缺少小区ID');
        }
        $pageConfig = $this->publicPageConfig($villageId);
        $period = $this->latestEffectiveQuarter($villageId);
        if (!$period) {
            [$year, $quarter] = $this->previousQuarter();
            return ['has_data' => 0, 'year' => $year, 'quarter' => $quarter, 'quarter_name' => $this->quarterName($quarter), 'page_config' => $pageConfig, 'background_color' => $pageConfig['background_color'], 'background_image' => $pageConfig['background_image']];
        }
        $year = intval($period['year']);
        $quarter = intval($period['quarter']);
        $details = Db::name('public_revenue_detail')->alias('d')
            ->leftJoin('public_revenue_subject s', 's.id=d.subject_id AND s.village_id=d.village_id')
            ->where([['d.village_id', '=', $villageId], ['d.period_id', '=', $period['id']]])
            ->where(function ($query) {
                $query->where('d.accrual_amount', '<>', 0)->whereOr('d.actual_amount', '<>', 0);
            })->field('d.subject_id,d.type,d.accrual_amount,d.actual_amount,d.remark,s.name as subject_name')
            ->order('d.type ASC,d.sort DESC,d.id ASC')->select()->toArray();
        foreach ($details as &$detail) {
            $detail['accrual_amount'] = $this->money($detail['accrual_amount']);
            $detail['actual_amount'] = $this->money($detail['actual_amount']);
        }
        return [
            'has_data' => 1, 'year' => $year, 'quarter' => $quarter, 'quarter_name' => $this->quarterName($quarter),
            'as_of_date' => $this->quarterEndDate($year, $quarter), 'period' => $period, 'details' => $details,
            'page_config' => $pageConfig,
            'background_color' => $pageConfig['background_color'],
            'background_image' => $pageConfig['background_image'],
        ];
    }

    private function publicPageConfig($villageId)
    {
        $config = Db::name('house_village_info')->where('village_id', $villageId)
            ->field('public_revenue_page_bg_color,public_revenue_page_bg_image')->find() ?: [];
        $color = strtoupper(trim(strval($config['public_revenue_page_bg_color'] ?? '')));
        if (!preg_match('/^#[0-9A-F]{6}$/', $color)) {
            $color = '#F3F4F6';
        }
        $image = trim(strval($config['public_revenue_page_bg_image'] ?? ''));
        return [
            'background_color' => $color,
            'background_image' => $image !== '' ? replace_file_domain($image) : '',
        ];
    }

    private function normalizeCandidate($villageId, $year, $quarter, $params)
    {
        if ($quarter === 0) {
            return [
                'year' => $year, 'quarter' => 0,
                'opening_accrual_balance' => 0,
                'opening_actual_balance' => $this->number($params['opening_actual_balance'] ?? 0),
                'details' => [],
            ];
        }
        $details = $params['details'] ?? [];
        if (is_string($details)) {
            $details = json_decode($details, true);
        }
        if (!is_array($details)) {
            throw new \Exception('收支明细格式不正确');
        }
        $subjectIds = [];
        $normalized = [];
        foreach ($details as $index => $detail) {
            $subjectId = intval($detail['subject_id'] ?? 0);
            $type = intval($detail['type'] ?? 0);
            if ($subjectId <= 0 || !in_array($type, [self::SUBJECT_INCOME, self::SUBJECT_EXPENSE], true)) {
                continue;
            }
            if (isset($subjectIds[$subjectId])) {
                throw new \Exception('同一季度不能重复录入同一项目');
            }
            $subject = Db::name('public_revenue_subject')->where([
                ['id', '=', $subjectId], ['village_id', '=', $villageId], ['type', '=', $type], ['is_del', '=', 0],
            ])->find();
            if (!$subject) {
                throw new \Exception('收支项目不存在或不属于当前小区');
            }
            $subjectIds[$subjectId] = true;
            $normalized[] = [
                'subject_id' => $subjectId,
                'subject_name' => $subject['name'],
                'type' => $type,
                'accrual_amount' => $this->number($detail['accrual_amount'] ?? 0),
                'actual_amount' => $this->number($detail['actual_amount'] ?? 0),
                'remark' => mb_substr(trim($detail['remark'] ?? ''), 0, 255),
                'sort' => intval($subject['sort'] ?? (1000 - $index)),
            ];
        }
        return ['year' => $year, 'quarter' => $quarter, 'details' => $normalized];
    }

    private function replacePeriod($periodId, $villageId, $candidate, $adminUser)
    {
        $quarter = intval($candidate['quarter']);
        $now = time();
        $save = ['update_by' => $this->operatorId($adminUser), 'update_time' => $now];
        if ($quarter === 0) {
            $save['opening_accrual_balance'] = $candidate['opening_accrual_balance'];
            $save['opening_actual_balance'] = $candidate['opening_actual_balance'];
            $save['accrual_balance'] = $candidate['opening_accrual_balance'];
            $save['actual_balance'] = $candidate['opening_actual_balance'];
            Db::name('public_revenue_detail')->where(['village_id' => $villageId, 'period_id' => $periodId])->delete();
        } else {
            Db::name('public_revenue_detail')->where(['village_id' => $villageId, 'period_id' => $periodId])->delete();
            $totals = ['accrual_income_total' => 0, 'actual_income_total' => 0, 'accrual_expense_total' => 0, 'actual_expense_total' => 0];
            foreach ($candidate['details'] as $detail) {
                Db::name('public_revenue_detail')->insert([
                    'village_id' => $villageId, 'period_id' => $periodId, 'subject_id' => $detail['subject_id'],
                    'type' => $detail['type'], 'accrual_amount' => $detail['accrual_amount'], 'actual_amount' => $detail['actual_amount'],
                    'remark' => $detail['remark'], 'sort' => $detail['sort'], 'create_time' => $now, 'update_time' => $now,
                ]);
                if (intval($detail['type']) === self::SUBJECT_INCOME) {
                    $totals['accrual_income_total'] += $detail['accrual_amount'];
                    $totals['actual_income_total'] += $detail['actual_amount'];
                } else {
                    $totals['accrual_expense_total'] += $detail['accrual_amount'];
                    $totals['actual_expense_total'] += $detail['actual_amount'];
                }
            }
            foreach ($totals as $key => $value) {
                $save[$key] = $this->number($value);
            }
        }
        Db::name('public_revenue_period')->where(['id' => $periodId, 'village_id' => $villageId])->update($save);
    }

    private function recalculateFrom($villageId, $startYear)
    {
        $maxYear = intval(Db::name('public_revenue_period')->where([
            ['village_id', '=', $villageId], ['status', '=', self::PERIOD_EFFECTIVE],
        ])->max('year'));
        $maxYear = max($startYear, $maxYear);
        for ($year = $startYear; $year <= $maxYear; $year++) {
            $previousQ4 = Db::name('public_revenue_period')->where([
                ['village_id', '=', $villageId], ['year', '=', $year - 1], ['quarter', '=', 4], ['status', '=', self::PERIOD_EFFECTIVE],
            ])->find();
            $opening = Db::name('public_revenue_period')->where([
                ['village_id', '=', $villageId], ['year', '=', $year], ['quarter', '=', 0],
            ])->find();
            if ($previousQ4) {
                $openingData = [
                    'opening_accrual_balance' => $previousQ4['accrual_balance'],
                    'opening_actual_balance' => $previousQ4['actual_balance'],
                    'accrual_balance' => $previousQ4['accrual_balance'],
                    'actual_balance' => $previousQ4['actual_balance'],
                    'status' => self::PERIOD_EFFECTIVE, 'source_type' => self::SOURCE_AUTO, 'update_time' => time(),
                ];
                if ($opening) {
                    Db::name('public_revenue_period')->where('id', $opening['id'])->update($openingData);
                    $opening = array_merge($opening, $openingData);
                } else {
                    $openingData += ['village_id' => $villageId, 'year' => $year, 'quarter' => 0, 'create_time' => time()];
                    $openingData['id'] = Db::name('public_revenue_period')->insertGetId($openingData);
                    $opening = $openingData;
                }
            }
            $accrualBalance = $this->number($opening['opening_accrual_balance'] ?? 0);
            $actualBalance = $this->number($opening['opening_actual_balance'] ?? 0);
            for ($quarter = 1; $quarter <= 4; $quarter++) {
                $period = Db::name('public_revenue_period')->where([
                    ['village_id', '=', $villageId], ['year', '=', $year], ['quarter', '=', $quarter], ['status', '=', self::PERIOD_EFFECTIVE],
                ])->find();
                if (!$period) {
                    continue;
                }
                $newAccrual = $this->number($accrualBalance + $period['accrual_income_total'] - $period['accrual_expense_total']);
                $newActual = $this->number($actualBalance + $period['actual_income_total'] - $period['actual_expense_total']);
                Db::name('public_revenue_period')->where('id', $period['id'])->update([
                    'opening_accrual_balance' => $accrualBalance, 'opening_actual_balance' => $actualBalance,
                    'accrual_balance' => $newAccrual, 'actual_balance' => $newActual, 'update_time' => time(),
                ]);
                $accrualBalance = $newAccrual;
                $actualBalance = $newActual;
                if ($quarter === 4 && $year === $maxYear) {
                    $maxYear = max($maxYear, $year + 1);
                }
            }
        }
    }

    private function calculateOpening($villageId, $year, $quarter)
    {
        if ($quarter === 0) {
            return ['accrual' => '0.00', 'actual' => '0.00'];
        }
        $opening = Db::name('public_revenue_period')->where([
            ['village_id', '=', $villageId], ['year', '=', $year], ['quarter', '=', 0], ['status', '=', self::PERIOD_EFFECTIVE],
        ])->find() ?: [];
        $accrual = floatval($opening['opening_accrual_balance'] ?? 0);
        $actual = floatval($opening['opening_actual_balance'] ?? 0);
        $previous = Db::name('public_revenue_period')->where([
            ['village_id', '=', $villageId], ['year', '=', $year], ['status', '=', self::PERIOD_EFFECTIVE],
        ])->whereBetween('quarter', [1, max(1, $quarter - 1)])->select()->toArray();
        if ($quarter > 1) {
            foreach ($previous as $item) {
                $accrual += $item['accrual_income_total'] - $item['accrual_expense_total'];
                $actual += $item['actual_income_total'] - $item['actual_expense_total'];
            }
        }
        return ['accrual' => $this->money($accrual), 'actual' => $this->money($actual)];
    }

    private function periodSnapshot($villageId, $periodId)
    {
        $period = Db::name('public_revenue_period')->where(['id' => $periodId, 'village_id' => $villageId])->find() ?: [];
        if ($period) {
            unset($period['draft_data']);
            $period['details'] = Db::name('public_revenue_detail')->alias('d')->leftJoin('public_revenue_subject s', 's.id=d.subject_id')
                ->where([['d.village_id', '=', $villageId], ['d.period_id', '=', $periodId]])
                ->field('d.subject_id,d.type,d.accrual_amount,d.actual_amount,d.remark,s.name as subject_name')
                ->order('d.type ASC,d.sort DESC,d.id ASC')->select()->toArray();
        }
        return $period;
    }

    private function pendingStatus($period)
    {
        $approvalId = intval($period['pending_approval_id'] ?? 0);
        if ($approvalId <= 0) {
            return null;
        }
        $status = Db::name('community_approval_instance')->where('id', $approvalId)->value('status');
        if ($status === null) {
            return null;
        }
        if (intval($status) !== 0) {
            Db::name('public_revenue_period')->where('id', $period['id'])->update(['pending_approval_id' => 0]);
        }
        return intval($status);
    }

    private function addLog($adminUser, $action, $businessId, $before, $after, $reason)
    {
        Db::name('public_revenue_operation_log')->insert([
            'village_id' => intval($adminUser['village_id'] ?? 0), 'business_type' => 'public_revenue',
            'business_id' => intval($businessId), 'action' => $action, 'operator_id' => $this->operatorId($adminUser),
            'operator_wid' => intval($adminUser['wid'] ?? 0), 'operator_name' => $this->operatorName($adminUser),
            'reason' => mb_substr($reason, 0, 500), 'before_data' => json_encode($before, JSON_UNESCAPED_UNICODE),
            'after_data' => json_encode($after, JSON_UNESCAPED_UNICODE), 'add_time' => time(),
        ]);
    }

    private function detailChangeLines($beforeDetails, $candidateDetails)
    {
        $beforeMap = [];
        $candidateMap = [];
        foreach (is_array($beforeDetails) ? $beforeDetails : [] as $detail) {
            $beforeMap[intval($detail['subject_id'] ?? 0)] = $detail;
        }
        foreach (is_array($candidateDetails) ? $candidateDetails : [] as $detail) {
            $candidateMap[intval($detail['subject_id'] ?? 0)] = $detail;
        }
        $subjectIds = array_values(array_unique(array_merge(array_keys($beforeMap), array_keys($candidateMap))));
        sort($subjectIds, SORT_NUMERIC);
        $lines = [];
        foreach ($subjectIds as $subjectId) {
            if ($subjectId <= 0) {
                continue;
            }
            $before = $beforeMap[$subjectId] ?? [];
            $candidate = $candidateMap[$subjectId] ?? [];
            $type = intval($candidate['type'] ?? $before['type'] ?? 0);
            $first = $type === self::SUBJECT_INCOME ? '应收' : '应付';
            $second = $type === self::SUBJECT_INCOME ? '实收' : '实付';
            $changes = [];
            if ($this->number($before['accrual_amount'] ?? 0) !== $this->number($candidate['accrual_amount'] ?? 0)) {
                $changes[] = $first . ' ' . $this->money($before['accrual_amount'] ?? 0) . ' → ' . $this->money($candidate['accrual_amount'] ?? 0) . ' 元';
            }
            if ($this->number($before['actual_amount'] ?? 0) !== $this->number($candidate['actual_amount'] ?? 0)) {
                $changes[] = $second . ' ' . $this->money($before['actual_amount'] ?? 0) . ' → ' . $this->money($candidate['actual_amount'] ?? 0) . ' 元';
            }
            $beforeRemark = trim(strval($before['remark'] ?? ''));
            $candidateRemark = trim(strval($candidate['remark'] ?? ''));
            if ($beforeRemark !== $candidateRemark) {
                $changes[] = '备注 ' . ($beforeRemark === '' ? '无' : $beforeRemark) . ' → ' . ($candidateRemark === '' ? '无' : $candidateRemark);
            }
            if ($changes) {
                $name = $candidate['subject_name'] ?? $before['subject_name'] ?? ('项目' . $subjectId);
                $lines[] = $name . '：' . implode('；', $changes);
            }
        }
        return $lines ?: ['收支明细无变化'];
    }

    private function previousQuarter()
    {
        $month = intval(date('n'));
        $currentQuarter = intval(ceil($month / 3));
        if ($currentQuarter === 1) {
            return [intval(date('Y')) - 1, 4];
        }
        return [intval(date('Y')), $currentQuarter - 1];
    }

    private function latestEffectiveQuarter($villageId)
    {
        $currentYear = intval(date('Y'));
        $period = Db::name('public_revenue_period')->where([
            ['village_id', '=', intval($villageId)], ['status', '=', self::PERIOD_EFFECTIVE], ['year', '=', $currentYear],
        ])->whereBetween('quarter', [1, $this->currentQuarter()])
            ->order('year DESC,quarter DESC,id DESC')
            ->find();
        if ($period) {
            return $period;
        }
        return Db::name('public_revenue_period')->where([
            ['village_id', '=', intval($villageId)], ['status', '=', self::PERIOD_EFFECTIVE], ['year', '<', $currentYear],
        ])->whereBetween('quarter', [1, 4])
            ->order('year DESC,quarter DESC,id DESC')->find();
    }

    private function currentQuarter()
    {
        return intval(ceil(intval(date('n')) / 3));
    }

    private function quarterEffectiveStatus($villageId, $year)
    {
        $status = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
        $quarters = Db::name('public_revenue_period')->where([
            ['village_id', '=', intval($villageId)], ['year', '=', intval($year)],
            ['quarter', 'between', [1, 4]], ['status', '=', self::PERIOD_EFFECTIVE],
        ])->column('quarter');
        foreach ($quarters as $quarter) {
            $quarter = intval($quarter);
            if (isset($status[$quarter])) {
                $status[$quarter] = 1;
            }
        }
        return $status;
    }

    private function validateQuarterSequence($villageId, $year, $quarter)
    {
        $quarter = intval($quarter);
        if ($quarter <= 1) {
            return;
        }
        $effective = $this->quarterEffectiveStatus($villageId, $year);
        for ($previous = 1; $previous < $quarter; $previous++) {
            if (intval($effective[$previous] ?? 0) !== 1) {
                throw new \Exception('请先填写并提交生效' . intval($year) . '年' . $this->quarterName($previous) . '数据');
            }
        }
    }

    private function validateQuarterStarted($year, $quarter)
    {
        $year = intval($year);
        $quarter = intval($quarter);
        if ($quarter === 0) {
            return;
        }
        $currentYear = intval(date('Y'));
        if ($year > $currentYear || ($year === $currentYear && $quarter > $this->currentQuarter())) {
            throw new \Exception('尚未进入' . $year . '年' . $this->quarterName($quarter) . '，当前只能保存草稿');
        }
    }

    private function quarterEndDate($year, $quarter)
    {
        $year = intval($year);
        $quarter = max(1, min(4, intval($quarter)));
        return date('Y年n月j日', mktime(0, 0, 0, $quarter * 3 + 1, 0, $year));
    }

    private function quarterName($quarter)
    {
        return $quarter === 0 ? '年初结余' : '第' . ['零', '一', '二', '三', '四'][$quarter] . '季度';
    }

    private function periodKey($params)
    {
        $year = intval($params['year'] ?? 0);
        $quarter = intval($params['quarter'] ?? -1);
        if ($year < 2000 || $year > 2100 || $quarter < 0 || $quarter > 4) {
            throw new \Exception('年度或季度不正确');
        }
        return [$year, $quarter];
    }

    private function ensureDefaultSubjects($villageId)
    {
        if (Db::name('public_revenue_subject')->where(['village_id' => $villageId, 'is_del' => 0])->count() > 0) {
            return;
        }
        $now = time();
        $defaults = [
            [self::SUBJECT_INCOME, '公共场地租赁收入', 100],
            [self::SUBJECT_INCOME, '广告收入', 90],
            [self::SUBJECT_INCOME, '停车场经营收入', 80],
            [self::SUBJECT_INCOME, '公共设施经营收入', 70],
            [self::SUBJECT_INCOME, '其他收入', 10],
            [self::SUBJECT_EXPENSE, '公共设施设备维修', 100],
            [self::SUBJECT_EXPENSE, '公共区域维护', 90],
            [self::SUBJECT_EXPENSE, '公共能耗', 80],
            [self::SUBJECT_EXPENSE, '税费', 70],
            [self::SUBJECT_EXPENSE, '其他支出', 10],
        ];
        $rows = [];
        foreach ($defaults as $default) {
            $rows[] = [
                'village_id' => $villageId,
                'type' => $default[0],
                'name' => $default[1],
                'sort' => $default[2],
                'status' => 1,
                'is_del' => 0,
                'create_time' => $now,
                'update_time' => $now,
            ];
        }
        Db::name('public_revenue_subject')->insertAll($rows);
    }

    private function villageId($adminUser)
    {
        $villageId = intval($adminUser['village_id'] ?? 0);
        if ($villageId <= 0) {
            throw new \Exception('请先登录到小区后台');
        }
        return $villageId;
    }

    private function operatorId($adminUser)
    {
        foreach (['id', 'normal_admin_id', '_userId', 'adminId'] as $field) {
            if (intval($adminUser[$field] ?? 0) > 0) {
                return intval($adminUser[$field]);
            }
        }
        return 0;
    }

    private function operatorName($adminUser)
    {
        foreach (['name', 'realname', 'user_name', 'account'] as $field) {
            if (trim($adminUser[$field] ?? '') !== '') {
                return trim($adminUser[$field]);
            }
        }
        return '';
    }

    private function number($value)
    {
        if (!is_numeric($value)) {
            throw new \Exception('金额格式不正确');
        }
        if (abs(floatval($value)) > 999999999999.99) {
            throw new \Exception('金额超出允许范围');
        }
        return round(floatval($value), 2, PHP_ROUND_HALF_UP);
    }

    private function money($value)
    {
        return number_format(round(floatval($value), 2, PHP_ROUND_HALF_UP), 2, '.', '');
    }

    private function decode($json)
    {
        $data = json_decode($json ?: '', true);
        return is_array($data) ? $data : null;
    }
}
