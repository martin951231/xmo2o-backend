<?php

/**
 * 企业管理
 */
class QiyeAction extends BaseAction
{
    public $qiyeMod;

    protected function _initialize()
    {
        parent::_initialize();
        $this->qiyeMod = M('Qiye');
    }

    public function lists()
    {
        import('@.ORG.system_page');
        $where = [];
        $sort = 'qiye_id DESC';
        if (!empty($_GET['province_idss'])) {
            $where['province_id'] = $_GET['province_idss'];
        }
        if (!empty($_GET['city_idss'])) {
            $where['city_id'] = $_GET['city_idss'];
        }
        if (!empty($_GET['area_id'])) {
            $where['area_id'] = $_GET['area_id'];
        }
        if (!empty(trim($_GET['keyword']))) {
            if ($_GET['searchtype'] == 'name') {
                $where['name'] = ['LIKE', '%' . trim($_GET['keyword']) . '%'];
            } else if ($_GET['searchtype'] == 'id') {
                $where['account_id'] = trim($_GET['keyword']);
            }
        }
        if (isset($_GET['searchstatus'])) {
            $where['status'] = $_GET['searchstatus'];
        }
        $count = M('Qiye')->where($where)->count();
        $p = new Page($count, 15);
        $qiyes = M('Qiye')->where($where)->order($sort)->limit($p->firstRow . ',' . $p->listRows)->select();
        foreach ($qiyes as &$v) {
            $v['staff_num'] = M('Qiye_staff')->where(['qiye_id' => $v['qiye_id']])->count();
            $v['merchant_num'] = M('Qiye_merchant')->where(['qiye_id' => $v['qiye_id']])->count();
        }
        $this->assign('qiyes', $qiyes);
        $this->assign('pagebar', $p->show());
        $this->display();
    }

    public function add()
    {
        if (IS_POST) {
            $_POST['salt'] = createRandomStr();
            $_POST['reg_time'] = time();
            if (empty($_POST['account'])) {
                $this->error(L_('企业账号不能为空'));
            }
            if (empty($_POST['pwd'])) {
                $this->error(L_('企业密码不能为空'));
            }
            if (empty($_POST['name'])) {
                $this->error(L_('企业名称不能为空'));
            }
            if (empty($_POST['phone'])) {
                $this->error(L_('联系电话不能为空'));
            }
            if (empty($_POST['province_id']) || empty($_POST['city_id']) || empty($_POST['area_id'])) {
                $this->error(L_('请选择所在区域'));
            }
            if ($this->qiyeMod->where(['account' => $_POST['account']])->find()) {
                $this->error(L_('企业账号已存在'));
            }
            if ($this->qiyeMod->where(['phone' => $_POST['phone']])->find()) {
                $this->error(L_('手机号码已存在'));
            }
            $areaShortName = D('Area')->concatCityShortName([$_POST['city_id'], $_POST['area_id']]);
            $_POST['pwd'] = md5($_POST['pwd'] . $_POST['salt']);
            $_POST['account_id'] = $areaShortName . date('YmdHi') . createRandomStr(5, true);
            $data = $this->qiyeMod->create($_POST);
            if ($data) {
                $this->qiyeMod->add($data);
                $this->success('添加成功！');
            } else {
                $this->error(L_($this->qiyeMod->getError()));
            }
        } else {
            $this->display();
        }
    }

    public function edit()
    {
        if (IS_POST) {
            if (empty($_POST['qiye_id']) || $_POST['qiye_id'] < 1) {
                $this->error(L_('企业记录不存在'));
            }
            $qiyeId = intval($_POST['qiye_id']);
            $info = $this->qiyeMod->where(['qiye_id' => $qiyeId])->find();
            if (!$info) {
                $this->error(L_('企业记录不存在'));
            }
            if (empty($_POST['name'])) {
                $this->error(L_('企业名称不能为空'));
            }
            if (empty($_POST['phone'])) {
                $this->error(L_('联系电话不能为空'));
            }
            if (empty($_POST['province_id']) || empty($_POST['city_id']) || empty($_POST['area_id'])) {
                $this->error(L_('请选择所在区域'));
            }
            if ($this->qiyeMod->where(['phone' => $_POST['phone'], 'qiye_id' => ['NEQ', $qiyeId]])->find()) {
                $this->error(L_('手机号码已被其他企业使用，请更换'));
            }
            if ($_POST['pwd']) {
                $_POST['pwd'] = md5($_POST['pwd'] . $info['salt']);
            } else {
                unset($_POST['pwd']);
            }

            if (empty($info['account_id'])) {
                $areaShortName = D('Area')->concatCityShortName([$_POST['city_id'], $_POST['area_id']]);
                $_POST['account_id'] = $areaShortName . date('ymdHi') . createRandomStr(5, true);
            }
            //企业待结算金额为0，则可以修改结算方式
            if ($info['money'] != 0 || !in_array($_POST['settlement_type'], [1, 2])) {
                unset($_POST['settlement_type']);
            }
            unset($_POST['qiye_id']);
            $data = $this->qiyeMod->create($_POST);
            if ($data) {
                $this->qiyeMod->where(['qiye_id' => $qiyeId])->save($data);
                $this->success('编辑成功！');
            } else {
                $this->error(L_($this->qiyeMod->getError()));
            }
        } else {
            $qiyeId = isset($_GET['qiye_id']) ? intval($_GET['qiye_id']) : 0;
            $info = $this->qiyeMod->where(['qiye_id' => $qiyeId])->find();
            if (!$info) {
                $this->error(L_('企业不存在'));
            }
            $this->assign('info', $info);
            $this->display();
        }

    }


    //企业预存款申请提现列表
    public function uncashout_list()
    {
        $model = M('Qiye_cashout as c');
        $where = array();
        $where['c.status'] = 0;
        $count = $model->where($where)->count();
        $p = new Page($count, 20);
        $list = $model->join('left join ' . C('DB_PREFIX') . 'merchant m ON m.mer_id = c.mer_id')->field('c.*,m.name as mer_name')->where($where)->limit($p->firstRow, $p->listRows)->select();
        $list = $this->deal_cashout_list($list);
        $this->assign('list', $list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
    }

    //企业预存款已申请提现列表
    public function cashout_list()
    {
        import('@.ORG.system_page');
        $model = M('Qiye_cashout as c');
        $where = array();
        $where['c.status'] = 1;
        $count = $model->where($where)->count();
        $p = new Page($count, 20);
        $list = $model->join('left join ' . C('DB_PREFIX') . 'merchant m ON m.mer_id = c.mer_id')->field('c.*,m.name as mer_name')->where($where)->limit($p->firstRow, $p->listRows)->select();
        $list = $this->deal_cashout_list($list);
        $this->assign('list', $list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
    }

    protected function deal_cashout_list($data)
    {
        if (empty($data)) return array();
        $where['from'] = 1;
        $where['type'] = 0;
        $bank_list = M('User_withdraw_account_list')->field('id,account,account_name,remark')->where($where)->order('is_default DESC,id DESC')->select();
        foreach ($data as $key => $val) {
            if ($val['account_type'] == 1) {
                foreach ($bank_list as $k => $v) {
                    if ($val['account'] == $v['id']) {
                        $data[$key]['account'] = $v['remark'] . $v['account_name'] . $v['account'];
                    }
                }
            }
        }
        return $data;
    }

    public function edit_reason()
    {
        $type = $_GET['type'];
        if (IS_POST) {
            if ($_POST['status'] == 2 && empty($_POST['reason'])) {
                $this->error('驳回理由不能为空！');
            }
            $data = array();
            $data['reason'] = $_POST['reason'];
            $data['status'] = $_POST['status'];
            $data['cashout_time'] = time();
            $id = intval($_POST['id']);

            $cashout_mod = M('Qiye_cashout');
            $cashout_record = $cashout_mod->where(['id' => $id])->find();
            if (empty($cashout_record)) {
                $this->error('提现记录不存在');
            }
            $merchant = M('Merchant')->where(['mer_id' => $cashout_record['mer_id']])->field('qiye_money')->find();
            if (!$merchant || $merchant['qiye_money'] < $cashout_record['money']) {
                $this->error('商家企业预存款不足');
            }

            $cashout_mod->startTrans();
            try {
                $res = M('Qiye_cashout')->where(array('id' => $_POST['id']))->data($data)->save();
                if (!$res) {
                    throw new Exception('保存失败');
                }
                //审核成功扣减余额
                if ($_POST['status'] == 1) {
                    $res = M('Merchant')->where(['mer_id' => $cashout_record['mer_id']])->setDec('qiye_money', $cashout_record['money']);
                    if (!$res) {
                        throw new Exception('商家企业预存款扣款失败');
                    }

                    $now_mer_money = M('Merchant')->field('money,qiye_money')->where(['mer_id' => $cashout_record['mer_id']])->find();
                    $merchant_qiye_money_data = [
                        'mer_id' => $cashout_record['mer_id'],
                        'type' => 'withdraw',
                        'money' => $cashout_record['money'],
                        'system_take' => 0,
                        'total_money' => $cashout_record['money'],
                        'income' => 2,
                        'use_time' => time(),
                        'desc' => '企业预存款提现',
                        'now_mer_qiye_money' => $now_mer_money['qiye_money']
                    ];
                    M('Merchant_qiye_money_list')->add($merchant_qiye_money_data);
                }
                $cashout_mod->commit();
                $this->success('保存成功！');
            } catch (\Exception $e) {
                $cashout_mod->rollback();
                $this->error($e->getMessage());
            }
        } else {
            $this->display();
        }
    }


    public function applyMoneyList()
    {
        import('@.ORG.system_page');
        $model = M('Qiye_apply_money');
        $where = array();
        $defaultGet = [
            'keyword' => '',
            'searchtype' => 'all',
            'searchstatus' => -1,
        ];
        $_GET = array_merge($defaultGet, $_GET);
        $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
        $searchtype = isset($_GET['searchtype']) ? trim($_GET['searchtype']) : '';
        $searchstatus = isset($_GET['searchstatus']) ? trim($_GET['searchstatus']) : '';
        $searchTimeType = isset($_GET['searchTimeType']) ? trim($_GET['searchTimeType']) : '';
        $beginTime = isset($_GET['begin_time'])?trim($_GET['begin_time']):'';
        $endTime = isset($_GET['end_time'])?trim($_GET['end_time']):'';

        if ($keyword) {
            if ($searchtype == 'qiye_name') {
                $where['q.name'] = ['LIKE', '%' . $keyword . '%'];
            } else if ($searchtype == 'staff_name') {
                $where['s.name'] = ['LIKE', '%' . $keyword . '%'];
            } else if ($searchtype == 'all') {
                $where['q.name|s.name'] = ['LIKE', '%' . $keyword . '%'];
            }
        }
        if ($beginTime && $endTime) {
            if ($searchTimeType == 'time') {
                $where['l.time'] = [
                    ['EGT', strtotime($beginTime)],
                    ['LT', strtotime($endTime) + 86400]
                ];
            } else if ($searchTimeType == 'handle_time') {
                $where['l.handle_time'] = [
                    ['EGT', strtotime($beginTime)],
                    ['LT', strtotime($endTime) + 86400]
                ];
            }
        }
        if ($searchstatus != -1) {
            $where['l.status'] = $searchstatus;
        }

        $count = $model->alias('l')->join('left join ' . C('DB_PREFIX') . 'qiye_staff AS s ON s.staff_id = l.staff_id')
            ->join('left join ' . C('DB_PREFIX') . 'qiye AS q ON s.qiye_id = q.qiye_id')->where($where)->count();
        $p = new Page($count, 20);
        $list = $model->alias('l')->join('left join ' . C('DB_PREFIX') . 'qiye_staff AS s ON s.staff_id = l.staff_id')
            ->join('left join ' . C('DB_PREFIX') . 'qiye AS q ON s.qiye_id = q.qiye_id')
            ->field('l.*,q.name AS qiye_name,s.name AS staff_name')
            ->where($where)
            ->order('l.id DESC')
            ->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('list', $list);
        $this->assign('pagebar', $p->show());
        $this->display();
    }

    public function handle()
    {
        if (IS_POST) {
            $idStr = isset($_POST['id']) ? trim($_POST['id']) : 0;
            $ids = array_unique(explode(',', $idStr));
            $handleStatus = isset($_POST['handle_status']) ? intval($_POST['handle_status']) : 0;
            $handleRemark = isset($_POST['handle_remark']) ? trim($_POST['handle_remark']) : '';
            if (empty($ids)) {
                $this->error(L_('请选择一条记录'));
            }
            if (!in_array($handleStatus, [1, 2])) {
                $this->error(L_('请选择审核状态'));
            }
            if ($handleStatus == 2 && empty($handleRemark)) {
                $this->error(L_('驳回申请必须填写备注'));
            }
            if (M('Qiye_apply_money')->where(['id' => ['IN', $ids], 'status' => ['NEQ', 0]])->find()) {
                $this->error(L_('请勿选择已审核的记录'));
            }

            $data = [
                'status' => $handleStatus,
                'handle_remark' => $handleRemark,
                'handle_time' => time(),
                'admin_account' => $this->system_session['account']
            ];
            $mod = M();
            $mod->startTrans();
            sort($ids, SORT_NUMERIC);
            try {
                foreach ($ids as $id) {
                    $apply = M('Qiye_apply_money')->where(['id' => $id])->find();
                    if (!$apply) {
                        throw new \Exception(L_('申请记录不存在'));
                    }
                    if ($apply['status'] != 0) {
                        throw new \Exception(L_('申请记录已审核，请勿重复审核'));
                    }
                    $staff = M('Qiye_staff')->where(['staff_id' => $apply['staff_id']])->find();
                    if (!$staff) {
                        throw new \Exception(L_('申请用户不存在'));
                    }

                    if ($handleStatus == 1) {
                        //通过
                        if ($apply['operate'] == 1) {
                            //预存
                            $staffUpdate = [
                                'money' => $staff['money'] + $apply['money'],
                                'unpaid_money' => $staff['unpaid_money'] - $apply['money'],
                            ];
                            if (M('Qiye_staff')->where(['staff_id' => $apply['staff_id']])->save($staffUpdate)) {
                                //预存款审核通过，先消费后结算企业，结算给企业，用户消费不再结算
                                $qiye = M('Qiye')->where(['qiye_id' => $staff['qiye_id']])->find();
                                if ($qiye['settlement_type'] == 1) {
                                    $settleMoney = round($apply['money'] * (1 + $qiye['settlement_ratio'] / 100), 2);
                                    if (!M('Qiye')->where(['qiye_id' => $qiye['qiye_id']])->setInc('money', $settleMoney)) {
                                        throw new \Exception(L_('预存金额结算给企业失败'));
                                    } else {
                                        $log = [
                                            'qiye_id' => $qiye['qiye_id'],
                                            'type' => 1,
                                            'qiye_pay' => 0,
                                            'money' => $settleMoney,
                                            'settlement_ratio' => $qiye['settlement_ratio'],
                                            'settlement_type' => $qiye['settlement_type'],
                                            'time' => time(),
                                            'ip' => get_client_ip(),
                                            'desc' => '用户预存款结算'
                                        ];
                                        M('Qiye_money_list')->add($log);
                                    }
                                }

                                $data['send_time'] = time();
                                $data['after_money'] = $staff['money'] + $apply['money'];
                                $log = [
                                    'staff_id' => $apply['staff_id'],
                                    'uid' => $staff['job_number'],
                                    'type' => 1,
                                    'money' => $apply['money'],
                                    'time' => time(),
                                    'ip' => get_client_ip(),
                                    'desc' => '预存款充值',
                                    'now_money' => $data['after_money']
                                ];
                                M('Qiye_staff_money_list')->add($log);
                            } else {
                                throw new \Exception(L_('增加预存款失败'));
                            }
                        } else if ($apply['operate'] == 2) {
                            //扣减
                            if (M('Qiye_staff')->where(['staff_id' => $apply['staff_id']])->setDec('money', $apply['money'])) {
                                $data['send_time'] = time();
                                $data['after_money'] = $staff['money'] - $apply['money'];
                                $log = [
                                    'staff_id' => $apply['staff_id'],
                                    'uid' => $staff['job_number'],
                                    'type' => 2,
                                    'money' => $apply['money'],
                                    'time' => time(),
                                    'ip' => get_client_ip(),
                                    'desc' => '扣减预存款',
                                    'now_money' => $data['after_money']
                                ];
                                M('Qiye_staff_money_list')->add($log);
                            } else {
                                throw new \Exception(L_('扣除预存款失败,请查看余额是否充足'));
                            }
                        }
                    }else{
                        //预存
                        $staffUpdate = ['unpaid_money' => $staff['unpaid_money'] - $apply['money']];
                        M('Qiye_staff')->where(['staff_id' => $apply['staff_id']])->save($staffUpdate);
                    }

                    M('Qiye_apply_money')->where(['id' => $id])->save($data);
                }
                $mod->commit();
                $this->success(L_('审核成功'));
            } catch (\Exception $e) {
                $mod->rollback();
                throw new \Exception(L_('审核失败'));
            }
        } else {
            $id = isset($_GET['id']) ? trim($_GET['id']) : 0;
            $ids = array_unique(explode(',', $id));
            if (empty($ids)) {
                $this->error(L_('请选择一条记录'));
            }
            if (M('Qiye_apply_money')->where(['id' => ['IN', $ids], 'status' => ['NEQ', 0]])->find()) {
                $this->error(L_('请勿选择已审核的记录'));
            }
            if (count($ids) == 1) {
                $info = M('Qiye_apply_money')->alias('l')->join('left join ' . C('DB_PREFIX') . 'qiye_staff AS s ON s.staff_id = l.staff_id')
                    ->join('left join ' . C('DB_PREFIX') . 'qiye AS q ON s.qiye_id = q.qiye_id')
                    ->field('l.*,q.name AS qiye_name,s.name AS staff_name')
                    ->where(['l.id' => $id])
                    ->find();
                $this->assign('info', $info);
                $tpl = 'handle';
            } else {
                $this->assign('id', implode($ids, ','));
                $this->assign('count', count($ids));
                $tpl = 'handle_all';
            }
            $this->display($tpl);
        }
    }

    public function handle2()
    {
        //旧版废弃
        return false;
        if (IS_POST) {
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $handleStatus = isset($_POST['handle_status']) ? intval($_POST['handle_status']) : 0;
            $handleRemark = isset($_POST['handle_remark']) ? trim($_POST['handle_remark']) : '';
            if ($id < 1) {
                $this->error(L_('请选择一条记录'));
            }
            if (!in_array($handleStatus, [1, 2])) {
                $this->error(L_('请选择审核状态'));
            }
            if ($handleStatus == 2 && empty($handleRemark)) {
                $this->error(L_('驳回申请必须填写备注'));
            }
            $apply = M('Qiye_apply_money')->where(['id' => $id])->find();
            if (!$apply) {
                $this->error(L_('申请记录不存在'));
            }
            if ($apply['status'] != 0) {
                $this->error(L_('申请记录已审核，请勿重复审核'));
            }
            $staff = M('Qiye_staff')->where(['staff_id' => $apply['staff_id']])->find();
            if (!$staff) {
                $this->error(L_('申请用户不存在'));
            }
            $data = [
                'status' => $handleStatus,
                'handle_remark' => $handleRemark,
                'handle_time' => time(),
                'admin_account' => $this->system_session['account']
            ];
            if ($handleStatus == 1) {
                //通过
                if ($apply['operate'] == 1) {
                    //预存
                    $staffUpdate = [
                        'money'=>$staff['money'] + $apply['money'],
                        'unpaid_money'=>$staff['unpaid_money'] - $apply['money'],
                    ];
                    if ( M('Qiye_staff')->where(['staff_id' => $apply['staff_id']])->save($staffUpdate)) {
                        //预存款审核通过，先消费后结算企业，结算给企业，用户消费不再结算
                        $qiye = M('Qiye')->where(['qiye_id' => $staff['qiye_id']])->find();
                        if ($qiye['settlement_type'] == 1) {
                            $settleMoney = round($apply['money'] * (1 + $qiye['settlement_ratio'] / 100), 2);
                            if (!M('Qiye')->where(['qiye_id' => $qiye['qiye_id']])->setInc('money', $settleMoney)) {
                                throw new \Exception(L_('预存金额结算给企业失败'));
                            } else {
                                $log = [
                                    'qiye_id' => $qiye['qiye_id'],
                                    'type' => 1,
                                    'qiye_pay' => 0,
                                    'money' => $settleMoney,
                                    'settlement_ratio' => $qiye['settlement_ratio'],
                                    'settlement_type' => $qiye['settlement_type'],
                                    'time' => time(),
                                    'ip' => get_client_ip(),
                                    'desc' => '用户预存款结算'
                                ];
                                M('Qiye_money_list')->add($log);
                            }
                        }

                        $data['send_time'] = time();
                        $data['after_money'] = $staff['money'] + $apply['money'];
                        $log = [
                            'staff_id' => $apply['staff_id'],
                            'uid' => $staff['job_number'],
                            'type' => 1,
                            'money' => $apply['money'],
                            'time' => time(),
                            'ip' => get_client_ip(),
                            'desc' => '预存款充值',
                            'now_money'=>$data['after_money']
                        ];
                        M('Qiye_staff_money_list')->add($log);
                    } else {
                        $this->error(L_('增加预存款失败'));
                    }
                } else if ($apply['operate'] == 2) {
                    //扣减
                    if (M('Qiye_staff')->where(['staff_id' => $apply['staff_id']])->setDec('money', $apply['money'])) {
                        $data['send_time'] = time();
                        $data['after_money'] = $staff['money'] - $apply['money'];
                        $log = [
                            'staff_id' => $apply['staff_id'],
                            'uid' => $staff['job_number'],
                            'type' => 2,
                            'money' => $apply['money'],
                            'time' => time(),
                            'ip' => get_client_ip(),
                            'desc' => '扣减预存款',
                            'now_money'=>$data['after_money']
                        ];
                        M('Qiye_staff_money_list')->add($log);
                    } else {
                        $this->error(L_('扣除预存款失败,请查看余额是否充足'));
                    }
                }
            }
            if (M('Qiye_apply_money')->where(['id' => $id])->save($data) !== false) {
                $this->success(L_('审核成功'));
            } else {
                $this->success(L_('审核失败'));
            }
        } else {
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            $info = M('Qiye_apply_money')->alias('l')->join('left join ' . C('DB_PREFIX') . 'qiye_staff AS s ON s.staff_id = l.staff_id')
                ->join('left join ' . C('DB_PREFIX') . 'qiye AS q ON s.qiye_id = q.qiye_id')
                ->field('l.*,q.name AS qiye_name,s.name AS staff_name')
                ->where(['l.id' => $id])
                ->find();
            $this->assign('info', $info);
            $this->display();
        }
    }

    public function detail()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $info = M('Qiye_apply_money')->alias('l')->join('left join ' . C('DB_PREFIX') . 'qiye_staff AS s ON s.staff_id = l.staff_id')
            ->join('left join ' . C('DB_PREFIX') . 'qiye AS q ON s.qiye_id = q.qiye_id')
            ->field('l.*,q.name AS qiye_name,s.name AS staff_name')
            ->where(['l.id' => $id])
            ->find();
        $this->assign('info', $info);
        $this->display();
    }

    public function login()
    {
        $qiyeId = isset($_GET['qiye_id']) ? intval($_GET['qiye_id']) : 0;
        $nowQiye = M('Qiye')->field('operate_pwd,pwd', true)->where(['qiye_id' => $qiyeId])->find();
        if (!$nowQiye) {
            $this->error(L_('企业不存在'));
        }
        session('qiye', $nowQiye);
        redirect($this->config['site_url'] . '/enterprise.php');
    }

    public function settlement()
    {
        import('@.ORG.system_page');
        $model = M('Qiye_apply_settlement');
        $where = array();
        $defaultGet = [
            'keyword' => '',
            'searchtype' => 'all',
            'searchstatus' => -1
        ];
        $_GET = array_merge($defaultGet, $_GET);
        $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
        $searchtype = isset($_GET['searchtype']) ? trim($_GET['searchtype']) : '';
        $searchstatus = isset($_GET['searchstatus']) ? trim($_GET['searchstatus']) : '';

        if ($keyword) {
            if ($searchtype == 'qiye_name') {
                $where['q.name'] = ['LIKE', '%' . $keyword . '%'];
            } else {
                $where['q.name|q.account'] = ['LIKE', '%' . $keyword . '%'];
            }
        }
        if ($searchstatus != -1) {
            $where['l.status'] = $searchstatus;
        }
        $count = $model->where($where)->count();
        $p = new Page($count, 20);
        $list = $model->alias('l')
            ->join('LEFT JOIN ' . C('DB_PREFIX') . 'qiye AS q ON l.qiye_id = q.qiye_id')
            ->join('LEFT JOIN ' . C('DB_PREFIX') . 'area AS a1 ON a1.area_id = q.province_id')
            ->join('LEFT JOIN ' . C('DB_PREFIX') . 'area AS a2 ON a2.area_id = q.city_id')
            ->join('LEFT JOIN ' . C('DB_PREFIX') . 'area AS a3 ON a3.area_id = q.area_id')
            ->field('l.*,q.name AS qiye_name,q.money AS qiye_money,q.account,CONCAT(a1.`area_name`," ",a2.`area_name`," ",a3.`area_name`) AS address')
            ->where($where)
            ->order('l.id DESC')
            ->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('list', $list);
        $this->assign('pagebar', $p->show());
        $this->display();
    }

    public function handleSettlement()
    {
        if (IS_POST) {
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $handleStatus = isset($_POST['handle_status']) ? intval($_POST['handle_status']) : 0;
            $handleRemark = isset($_POST['handle_remark']) ? trim($_POST['handle_remark']) : '';
            if ($id < 1) {
                $this->error(L_('请选择一条记录'));
            }
            if (!in_array($handleStatus, [1, 2])) {
                $this->error(L_('请选择审核状态'));
            }
            if ($handleStatus == 2 && empty($handleRemark)) {
                $this->error(L_('驳回申请必须填写备注'));
            }
            $apply = M('Qiye_apply_settlement')->where(['id' => $id])->find();
            if (!$apply) {
                $this->error(L_('申请记录不存在'));
            }
            if ($apply['status'] != 0) {
                $this->error(L_('申请记录已审核，请勿重复审核'));
            }
            $qiye = M('Qiye')->where(['qiye_id' => $apply['qiye_id']])->find();
            if (!$qiye) {
                $this->error(L_('企业不存在'));
            }
            $data = [
                'status' => $handleStatus,
                'handle_remark' => $handleRemark,
                'handle_time' => time(),
                'admin_account' => $this->system_session['account']
            ];
            if ($handleStatus == 1) {
                //通过
                if ($qiye['money'] < $apply['money']) {
                    $this->error(L_('企业结算余额不足'));
                }
                $log = [
                    'qiye_id' => $apply['qiye_id'],
                    'type' => 2,
                    'money' => $apply['money'],
                    'time' => time(),
                    'ip' => get_client_ip(),
                    'desc' => '企业结算'
                ];
                M('Qiye_money_list')->add($log);
                //先结算后消费企业，当结算金额为0时，发放用户申请金额到对应账户
                if ($qiye['settlement_type'] == 2 && $qiye['money'] - $apply['money'] == 0) {
                    D('Qiye')->sendMoney2Staff($qiye['qiye_id'], time());
                }
            }
            if (M('Qiye_apply_settlement')->where(['id' => $id])->save($data) !== false) {
                //减少用户已申请未审核金额
                $update = [
                    'apply_money' => $qiye['apply_money'] - $apply['money']
                ];
                if ($handleStatus == 2) {
                    $update['money'] = $qiye['money'] + $apply['money'];
                    $update['unsettlement_money'] = $qiye['unsettlement_money'] + $apply['money'];
                }
                M('Qiye')->where(['qiye_id' => $qiye['qiye_id']])->save($update);
                $this->success(L_('审核成功'));
            } else {
                $this->success(L_('审核失败'));
            }
        } else {
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            $info = M('Qiye_apply_settlement')->alias('l')
                ->join('LEFT JOIN ' . C('DB_PREFIX') . 'qiye AS q ON l.qiye_id = q.qiye_id')
                ->join('LEFT JOIN ' . C('DB_PREFIX') . 'area AS a1 ON a1.area_id = q.province_id')
                ->join('LEFT JOIN ' . C('DB_PREFIX') . 'area AS a2 ON a2.area_id = q.city_id')
                ->join('LEFT JOIN ' . C('DB_PREFIX') . 'area AS a3 ON a3.area_id = q.area_id')
                ->field('l.*,q.name AS qiye_name,q.money AS qiye_money,q.account,CONCAT(a1.`area_name`," ",a2.`area_name`," ",a3.`area_name`) AS address')
                ->where(['l.id' => $id])
                ->find();
            $this->assign('info', $info);
            $this->display();
        }
    }

    public function settlementDetail()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $info = M('Qiye_apply_settlement')->alias('l')
            ->join('LEFT JOIN ' . C('DB_PREFIX') . 'qiye AS q ON l.qiye_id = q.qiye_id')
            ->join('LEFT JOIN ' . C('DB_PREFIX') . 'area AS a1 ON a1.area_id = q.province_id')
            ->join('LEFT JOIN ' . C('DB_PREFIX') . 'area AS a2 ON a2.area_id = q.city_id')
            ->join('LEFT JOIN ' . C('DB_PREFIX') . 'area AS a3 ON a3.area_id = q.area_id')
            ->field('l.*,q.name AS qiye_name,q.money AS qiye_money,q.account,CONCAT(a1.`area_name`," ",a2.`area_name`," ",a3.`area_name`) AS address')
            ->where(['l.id' => $id])
            ->find();
        $this->assign('info', $info);
        $this->display();
    }

    public function staff()
    {
        import('@.ORG.system_page');
        $qiyeId = isset($_GET['qiye_id']) ? intval($_GET['qiye_id']) : 0;
        $where = ['s.qiye_id' => $qiyeId];
        $count = M('Qiye_staff')->alias('s')
            ->join('LEFT JOIN ' . C('DB_PREFIX') . 'qiye_staff_group AS g ON s.group_id = g.group_id')
            ->where($where)->count();
        $p = new Page($count, 15);
        if ($count > 0) {
            $staffs = M('Qiye_staff')->alias('s')
                ->join('LEFT JOIN ' . C('DB_PREFIX') . 'qiye_staff_group AS g ON s.group_id = g.group_id')
                ->where($where)->field('s.*,g.name AS group_name')->limit($p->firstRow . ',' . $p->listRows)->select();
        }
        $this->assign('pagebar', $p->show());
        $this->assign('staffs', $staffs);
        $this->display();
    }

    public function merchant()
    {
        import('@.ORG.system_page');
        $qiyeId = isset($_GET['qiye_id']) ? intval($_GET['qiye_id']) : 0;
        $where = array();
        $where['q.qiye_id'] = $qiyeId;
        $count = M('Qiye_merchant as q')->join('LEFT JOIN ' . C('DB_PREFIX') . 'merchant AS m ON m.mer_id = q.mer_id')->where($where)->count();
        $p = new Page($count, 15);
        if ($count > 0) {
            $merchants = M('Qiye_merchant')->alias('qm')
                ->join('LEFT JOIN ' . C('DB_PREFIX') . 'merchant AS m ON m.mer_id = qm.mer_id')
                ->join('LEFT JOIN ' . C('DB_PREFIX') . 'merchant_store AS s ON s.mer_id = m.mer_id')
                ->join('LEFT JOIN ' . C('DB_PREFIX') . 'area AS a1 ON a1.area_id = m.province_id')
                ->join('LEFT JOIN ' . C('DB_PREFIX') . 'area AS a2 ON a2.area_id = m.city_id')
                ->join('LEFT JOIN ' . C('DB_PREFIX') . 'area AS a3 ON a3.area_id = m.area_id')
                ->where(['qm.qiye_id' => $qiyeId])->field('qm.id,count(s.store_id) AS store_num,m.*,CONCAT(a1.`area_name`," ",a2.`area_name`," ",a3.`area_name`) AS address')
                ->group('m.mer_id')
                ->order('qm.id DESC')
                ->limit($p->firstRow . ',' . $p->listRows)->select();
        } else {
            $merchants = [];
        }
        $this->assign('merchants', $merchants);
        $this->assign('pagebar', $p->show());
        $this->display();
    }

    protected function get_alias_c_name()
    {
        $alias_name = array(
            'shop' => $this->config['shop_alias_name'],
            'store' => L_('到店'),
        );
        if (!isset($this->config['appoint_alias_name'])) {
            unset($alias_name['appoint']);
        }
        return $alias_name;
    }

    public function trade()
    {
        $qiyeId = isset($_GET['qiye_id']) ? intval($_GET['qiye_id']) : 0;
        if (empty($_GET['type'])) {
            $_GET['type'] = 'shop';
            $table = 'shop_order';
        }
        $table = $_GET['type'] == 'shop' ? 'shop_order' : 'store_order';
        $timeField = $_GET['type'] == 'shop' ? 'o.create_time' : 'o.dateline';
        $qiye = M('Qiye')->where(['qiye_id' => $qiyeId])->find();
        if (empty($qiye)) {
            $this->error(L_('企业不存在'));
        }
        $this->assign('qiye', $qiye);
        $where = [
            'qs.qiye_id' => $qiyeId,
            'o.qiye_pay' => ['gt', 0],
            'o.paid' => 1
        ];
        if ($table == 'shop_order') {
            $where['o.is_refund'] = 0;
            $where['o.status'] = ['IN', [2, 3]];
        } elseif ($table == 'store_order') {
            $where['o.refund'] = 0;
        }

        if ($_GET['begin_time'] && $_GET['end_time']) {
            $startTime = strtotime($_GET['begin_time']);
            $endTime = strtotime($_GET['end_time']) + 86400;
            unset($_GET['selectTimeType']);
        } else if ($_GET['selectTimeType'] > 0) {
            $startTime = strtotime(date('Y-m-d', time() - ($_GET['selectTimeType'] - 1) * 86400));
            $endTime = time();
        } else {
            //默认最近20天
            $startTime = strtotime(date('Y-m-d', time() - 20 * 86400));
            $endTime = time();
        }
        if ($startTime && $endTime) {
            $where[$timeField] = [['gt', $startTime], ['lt', $endTime]];
        }

        $statics = M($table)->alias('o')
            ->join('INNER JOIN ' . C('DB_PREFIX') . 'qiye_staff AS qs ON qs.job_number = o.uid')
            ->where($where)
            ->field("count(distinct FROM_UNIXTIME($timeField,'%Y-%m-%d')) AS total_count,count(o.order_id) AS order_count,SUM(qiye_pay) AS total_qiye_pay,SUM(qiye_settlement_money) as total_service_charge")
            ->find();
        $arr['total_count'] = intval($statics['total_count']);
        $arr['order_count'] = intval($statics['order_count']);
        $arr['total_qiye_pay'] = floatval($statics['total_qiye_pay']);
        $arr['service_charge'] = floatval($statics['total_service_charge']);
        import('@.ORG.system_page');
        $p = new Page($arr['total_count'], 15, true);
        $arr['pagebar'] = $p->show();
        if ($arr['total_count'] > 0) {
            $arr['sale_date'] = M($table)->alias('o')
                ->join('INNER JOIN ' . C('DB_PREFIX') . 'qiye_staff AS qs ON qs.job_number = o.uid')
                ->field("FROM_UNIXTIME($timeField,'%Y-%m-%d') AS date_time,count(o.order_id) AS count,sum(o.qiye_pay) AS total_qiye_pay,sum(qiye_settlement_money) AS total_service_charge")
                ->where($where)
                ->group('date_time')
                ->order('date_time DESC')
                ->select();
        }
        $this->assign('type_name', $this->get_alias_c_name());
        $this->assign('qiye_id', $qiyeId);
        $this->assign($arr);
        $this->display();
    }
}
