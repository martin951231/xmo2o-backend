<?php

class StaffAction extends BaseAction
{
    public function index()
    {
        import('@.ORG.merchant_page');
        $qiyeId = $this->enterprise_session['qiye_id'];
        $where = ['s.qiye_id' => $qiyeId];
        if (isset($_GET['group_id']) && $_GET['group_id']) {
            $where['g.group_id'] = intval($_GET['group_id']);
        }
        $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
        $searchtype = isset($_GET['searchtype']) ? trim($_GET['searchtype']) : '';
        if ($keyword && $searchtype) {
            if ($searchtype == 'group_name') {
                $where['g.name'] = ['LIKE', '%' . $keyword . '%'];
            } else {
                $where['s.' . $searchtype] = ['LIKE', '%' . $keyword . '%'];
            }
        }
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

    public function export()
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(3600);
        $qiyeId = $this->enterprise_session['qiye_id'];
        $where = ['s.qiye_id' => $qiyeId];
        if (isset($_GET['group_id']) && $_GET['group_id']) {
            $where['g.group_id'] = intval($_GET['group_id']);
        }
        $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
        $searchtype = isset($_GET['searchtype']) ? trim($_GET['searchtype']) : '';
        if ($keyword && $searchtype) {
            if ($searchtype == 'group_name') {
                $where['g.name'] = ['LIKE', '%' . $keyword . '%'];
            } else {
                $where['s.' . $searchtype] = ['LIKE', '%' . $keyword . '%'];
            }
        }
        $staffs = M('Qiye_staff')->alias('s')->join('LEFT JOIN ' . C('DB_PREFIX') . 'qiye_staff_group AS g ON s.group_id = g.group_id')
            ->where($where)->field('s.*,g.name AS group_name')->select();
        if (empty($staffs)) {
            $this->error('没有数据');
        }
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title = '导出会员';
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $objExcel->createSheet();
        $objExcel->setActiveSheetIndex(0);
        $objActSheet = $objExcel->getActiveSheet();
        $objActSheet->setCellValue('A1', '卡号/工号/ID');
        $objActSheet->setCellValue('B1', '姓名');
        $objActSheet->setCellValue('C1', '手机号码');
        $objActSheet->setCellValue('D1', '预存余额');
        $objActSheet->setCellValue('E1', '未到账余额');
        $objActSheet->setCellValue('F1', '实体卡ID');
        $objActSheet->setCellValue('G1', '员工组别');
        $objActSheet->setCellValue('H1', '备注');
        $objActSheet->setCellValue('I1', '状态');

        $index = 2;
        foreach ($staffs as $key => $value) {
            $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(25);
            $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(30);
            $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(30);
            $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(30);
            $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(30);
            $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(25);
            $objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('H')->setWidth(30);
            $objExcel->getActiveSheet()->getColumnDimension('I')->setWidth(10);

            $objActSheet->setCellValueExplicit('A' . $index, $value['staff_number']);
            $objActSheet->setCellValueExplicit('B' . $index, $value['name']);
            $objActSheet->setCellValueExplicit('C' . $index, $value['phone']);
            $objActSheet->setCellValueExplicit('D' . $index, $value['money']);
            $objActSheet->setCellValueExplicit('E' . $index, $value['unpaid_money']);
            $objActSheet->setCellValueExplicit('F' . $index, $value['card_id']);
            $objActSheet->setCellValueExplicit('G' . $index, $value['group_name']);
            $objActSheet->setCellValueExplicit('H' . $index, $value['remark']);
            $objActSheet->setCellValueExplicit('I' . $index, $value['status'] == 0 ? '禁用' : '正常');
            $index++;
        }
        //输出
        ob_end_clean();
        $objWriter = new PHPExcel_Writer_Excel5($objExcel);
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="' . $title . '_' . date("Y-m-d h:i:s", time()) . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();
    }

    public function getStaffByGroup()
    {
        import('@.ORG.merchant_page');
        $qiyeId = $this->enterprise_session['qiye_id'];
        $where = ['s.qiye_id' => $qiyeId];
        $where['g.group_id'] = intval($_GET['group_id']);
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

    public function add()
    {
        if (IS_POST) {
            $post = $_POST;
            $mod = M('Qiye_staff');
            $rs = ['error_no' => 0, 'error_msg' => ''];
            try {
                if (empty($post['staff_number'])) {
                    throw new \Exception(L_('卡号/工号/ID不能为空'));
                }
                if (empty($post['name'])) {
                    throw new \Exception(L_('姓名不能为空'));
                }
                if ($mod->where(['staff_number' => $post['staff_number'], 'qiye_id' => $this->qiyeId])->find()) {
                    throw new \Exception(L_('卡号/工号/ID已存在'));
                }
                if (!empty($post['phone'])) {
                    $user = M('User')->where(['phone' => $post['phone']])->find();
                    if (!$user) {
                        throw new \Exception(L_('手机号未绑定平台用户'));
                    }
                    if ($mod->where(['job_number' => $user['uid']])->find()) {
                        throw new \Exception(L_('抱歉！该号码已被重复绑定，无法再次绑定'));
                    }
                    $post['job_number'] = $user['uid'];
                }
                $post['qiye_id'] = $this->qiyeId;
                $post['create_time'] = time();
                unset($post['staff_id']);
                $data = $mod->create($post);
                if (!$mod->add($data)) {
                    throw new \Exception(L_('新增会员失败'));
                }
            } catch (\Exception $e) {
                $rs['error_no'] = 1;
                $rs['error_msg'] = $e->getMessage();
            }
            $this->json($rs);
        } else {
            $groups = M('Qiye_staff_group')->where(['qiye_id' => $this->qiyeId])->select();
            $this->assign('groups', $groups);
            $this->display();
        }
    }

    public function edit()
    {
        if (IS_POST) {
            $post = $_POST;
            $mod = M('Qiye_staff');
            $rs = ['error_no' => 0, 'error_msg' => ''];
            try {
                if (empty($post['staff_id'])) {
                    throw new \Exception(L_('请选择一条记录'));
                }
                if (empty($post['name'])) {
                    throw new \Exception(L_('姓名不能为空'));
                }
                $staffId = intval($post['staff_id']);
                $staff = $mod->find($staffId);
                if (!$staff) {
                    throw new \Exception(L_('员工不存在'));
                }
                unset($post['staff_id'], $post['phone'], $post['job_number']);
                $data = $mod->create($post);
                $save = $mod->where(['staff_id' => $staffId])->save($data);
                if ($save === false) {
                    throw new \Exception(L_('编辑失败'));
                }
            } catch (\Exception $e) {
                $rs['error_no'] = 1;
                $rs['error_msg'] = $e->getMessage();
            }
            $this->json($rs);
        } else {
            $staffId = isset($_GET['staff_id']) ? intval($_GET['staff_id']) : 0;
            if ($staffId < 0) {
                $this->error('参数有误');
            }
            $staffInfo = M('Qiye_staff')->find($staffId);
            $groups = M('Qiye_staff_group')->where(['qiye_id' => $this->qiyeId])->select();
            $this->assign('groups', $groups);
            $this->assign('staffInfo', $staffInfo);
            $this->display();
        }
    }

    public function group()
    {
        import('@.ORG.merchant_page');
        $count = M('Qiye_staff_group')
            ->where(['qiye_id' => $this->qiyeId])->count();
        $p = new Page($count, 15);
        $groups = M('Qiye_staff_group')
            ->where(['qiye_id' => $this->qiyeId])->limit($p->firstRow . ',' . $p->listRows)->select();
        foreach ($groups as $k => $v) {
            $groups[$k]['staff_num'] = M('Qiye_staff')->where(['group_id' => $v['group_id']])->count();
        }
        $this->assign('groups', $groups);
        $this->assign('pagebar', $p->show());
        $this->display();
    }

    public function addGroup()
    {
        if (IS_POST) {
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $remark = isset($_POST['remark']) ? trim($_POST['remark']) : '';
            $rs = ['error_no' => 0, 'error_msg' => ''];
            try {
                if (empty($name)) {
                    throw new \Exception(L_('组别名称不能为空'));
                }
                if (empty($remark)) {
                    throw new \Exception(L_('组别备注不能为空'));
                }
                $data = [
                    'name' => $name,
                    'remark' => $remark,
                    'qiye_id' => $this->qiyeId
                ];
                if (false == (M('Qiye_staff_group')->add($data))) {
                    throw new \Exception(L_('新建分组失败'));
                }
            } catch (\Exception $e) {
                $rs['error_no'] = 1;
                $rs['error_msg'] = $e->getMessage();
            }
            $this->json($rs);
        } else {
            $this->display();
        }
    }

    public function editGroup()
    {
        if (IS_POST) {
            $groupId = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $remark = isset($_POST['remark']) ? trim($_POST['remark']) : '';
            $qiyeId = $this->qiyeId;
            $rs = ['error_no' => 0, 'error_msg' => ''];
            try {
                if (empty($name)) {
                    throw new \Exception(L_('组别名称不能为空'));
                }
                if (empty($remark)) {
                    throw new \Exception(L_('组别备注不能为空'));
                }
                $group = M('Qiye_staff_group')->where(['qiye_id' => $qiyeId, 'group_id' => $groupId])->find();
                if (!$group) {
                    throw new \Exception(L_('组别不存在'));
                }
                $data = [
                    'name' => $name,
                    'remark' => $remark
                ];
                if (false === (M('Qiye_staff_group')->where(['group_id' => $groupId])->save($data))) {
                    throw new \Exception(L_('编辑分组失败'));
                }
            } catch (\Exception $e) {
                $rs['error_no'] = 1;
                $rs['error_msg'] = $e->getMessage();
            }
            $this->json($rs);
        } else {
            $gid = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
            $group = M('Qiye_staff_group')->where(['group_id' => $gid])->find();
            if (!$group) {
                $this->error(L_('组别不存在'));
            }
            $this->assign('group', $group);
            $this->display('addGroup');
        }
    }


    public function showMoney()
    {
        $staffId = isset($_GET['staff_id']) ? intval($_GET['staff_id']) : 0;
        if ($staffId < 0) {
            $this->error('参数有误');
        }

        $staffInfo = M('Qiye_staff')->alias('s')
            ->join('LEFT JOIN ' . C('DB_PREFIX') . 'qiye_staff_group AS g ON s.group_id = g.group_id')
            ->where(['s.staff_id' => $staffId])
            ->field('s.*,g.name AS group_name')
            ->find();
        $this->assign('staffInfo', $staffInfo);
        $this->display();
    }

    public function applayMoneyBatch()
    {
        $rs = ['error_no' => 0, 'error_msg' => ''];
        if (IS_POST) {
            $money = isset($_POST['money']) ? round($_POST['money'], 2) : 0;
            $operate = isset($_POST['operate']) ? $_POST['operate'] : 0;
            $password = isset($_POST['password']) ? trim($_POST['password']) : '';
            $remark = isset($_POST['remark']) ? $_POST['remark'] : '';
            $staffid = isset($_POST['staff_id']) ? intval($_POST['staff_id']) : 0;
            $ids = isset($_POST['staff_ids']) ? trim($_POST['staff_ids']) : '';
            $staffIds = $staffid > 0 ? [$staffid] : explode(',', $ids);
            if (empty($staffIds)) {
                $rs['error_no'] = 1;
                $rs['error_msg'] = L_('请至少选择一个员工');
            } else if (empty($password)) {
                $rs['error_no'] = 1;
                $rs['error_msg'] = L_('操作密码不能为空');
            } else if ($money <= 0) {
                $rs['error_no'] = 1;
                $rs['error_msg'] = L_('金额必须大于0');
            } else if (empty($remark)) {
                $rs['error_no'] = 1;
                $rs['error_msg'] = L_('备注不能为空');
            } else if (!in_array($operate, [1, 2])) {
                $rs['error_no'] = 1;
                $rs['error_msg'] = L_('请选择操作');
            } else {
                try {
                    //判断员工是否属于均属于登录企业
                    $qiyeId = $this->qiyeId;
                    if (count($staffIds) != M('Qiye_staff')->where(['staff_id' => ['IN', $staffIds], 'qiye_id' => $qiyeId])->count()) {
                        throw new \Exception(L_('操作员工所属多个企业'));
                    }
                    //如果是扣减，判断用户余额是否充足
                    if ($operate == 2) {
                        $notEnoughMoneyStaffName = M('Qiye_staff')->where(['staff_id' => ['IN', $staffIds], 'money' => ['LT', $money]])->getField('name', true);
                        if($notEnoughMoneyStaffName){
                            throw new \Exception('【' . implode($notEnoughMoneyStaffName, ',') . '】扣减余额不足');
                        }
                    }

                    $qiye = M('Qiye')->where(['qiye_id' => $qiyeId])->find();
                    if ($qiye['operate_pwd'] != md5($password . $qiye['salt'])) {
                        throw new \Exception(L_('操作密码不正确'));
                    }

                    $tm = time();
                    $ip = get_client_ip();
                    foreach ($staffIds as $sid) {
                        $staff = M('Qiye_staff')->where(['staff_id' => $sid])->find();
                        if (!$staff) {
                            throw new \Exception(L_('员工不存在'));
                        }

                        $data = [
                            'staff_id' => $sid,
                            'operate' => $operate,
                            'money' => $money,
                            'time' => $tm,
                            'ip' => $ip,
                            'remark' => $remark,
                            'status' => 0
                        ];
                        if ($operate == 2) {
                            //扣减不审核
                            if ($staff['money'] < $money) {
                                throw new \Exception(L_('用户企业预存款余额不足，无法扣减'));
                            } else {
                                if (M('Qiye_staff')->where(['staff_id' => $staff['staff_id']])->setDec('money', $money)) {
                                    M('Qiye')->where(['qiye_id' => $qiyeId])->setDec('money', $money);
                                    $data['status'] = 1;
                                    $data['handle_remark'] = '扣减';
                                    $data['handle_time'] = $tm;
                                    $data['admin_account'] = 'system';
                                    $data['after_money'] = $staff['money'] - $money;
                                } else {
                                    $this->error(L_('扣除预存款失败,请查看余额是否充足'));
                                }
                            }
                        }

                        //如果企业是先结算后消费，则提交预存申请的时候就结算给用户
                        if ($qiye['settlement_type'] == 2 && $operate == 1) {
                            //预存直接结算给企业，默认审核成功
                            $settleMoney = round($money * (1 + $qiye['settlement_ratio'] / 100), 2);
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
                                    'time' => $tm,
                                    'ip' => get_client_ip(),
                                    'desc' => '用户预存款先结算后消费'
                                ];
                                M('Qiye_money_list')->add($log);
                            }
                            $data = [
                                'staff_id' => $sid,
                                'operate' => $operate,
                                'money' => $money,
                                'time' => $tm,
                                'ip' => $ip,
                                'remark' => '',
                                'status' => 1,
                                'handle_remark' => '先结算后消费自动审核通过',
                                'handle_time' => $tm,
                                'admin_account' => '',
                                'after_money' => $staff['money']
                            ];
                        }

                        $data = M('Qiye_apply_money')->create($data);
                        if (!M('Qiye_apply_money')->add($data)) {
                            throw new \Exception(L_('申请失败'));
                        }
                        if ($operate == 1) {
                            M('Qiye_staff')->where(['staff_id' => $staff['staff_id']])->setInc('unpaid_money', $money);
                        }
                    }
                } catch (\Exception $e) {
                    $rs['error_no'] = 1;
                    $rs['error_msg'] = $e->getMessage();
                }
            }
        } else {
            $rs['error_no'] = 1;
            $rs['error_msg'] = L_('请求方式有误');
        }
        $this->ajaxReturn($rs);
    }

    /**
     * 废弃
     * @param string $param
     * @return bool
     */
    protected function applyMoney($param='')
    {
        return false;
        $rs = ['error_no' => 0, 'error_msg' => ''];
        $post = $_POST;
        try {
            $staff = M('Qiye_staff')->where(['staff_id' => $post['staff_id']])->find();
            if (!$staff) {
                throw new \Exception(L_('员工不存在'));
            }
            $qiye = M('Qiye')->where(['qiye_id' => $staff['qiye_id']])->find();
            if (!$qiye || $qiye['status'] != 1) {
                throw new \Exception(L_('企业不存在或者关闭'));
            }
            if ($post['money'] <= 0) {
                throw new \Exception(L_('金额必须大于0'));
            }
            if (empty($post['password'])) {
                throw new \Exception(L_('操作密码不能为空'));
            }
            if ($qiye['operate_pwd'] != md5($post['password'] . $qiye['salt'])) {
                throw new \Exception(L_('操作密码不正确'));
            }

            if ($qiye['settlement_type'] == 1) {
                //先消费后结算
                $data = [
                    'staff_id' => $post['staff_id'],
                    'operate' => $post['operate'],
                    'money' => $post['money'],
                    'time' => time(),
                    'ip' => get_client_ip(),
                    'remark' => $post['remark'],
                    'status' => 0
                ];
                if ($post['operate'] == 2) {
                    //扣减不审核
                    if ($staff['money'] < $post['money']) {
                        throw new \Exception(L_('用户企业预存款余额不足，无法扣减'));
                    } else {
                        if (M('Qiye_staff')->where(['staff_id' => $staff['staff_id']])->setDec('money', $post['money'])) {
                            $data['status'] = 1;
                            $data['handle_remark'] = '扣减';
                            $data['handle_time'] = time();
                            $data['admin_account'] = 'system';
                            $data['after_money'] = $staff['money'] - $post['money'];
                        } else {
                            $this->error(L_('扣除预存款失败,请查看余额是否充足'));
                        }
                    }
                }
                $data = M('Qiye_apply_money')->create($data);
                if (!M('Qiye_apply_money')->add($data)) {
                    throw new \Exception(L_('申请失败'));
                }
                if ($post['operate'] == 1) {
                    M('Qiye_staff')->where(['staff_id' => $staff['staff_id']])->setInc('unpaid_money', $post['money']);
                }
            } else if ($qiye['settlement_type'] == 2) {
                //先结算后消费
                if ($post['operate'] == 1) {
                    //预存直接结算给企业，默认审核成功
                    $settleMoney = floatval($post['money'] * (1 + $qiye['settlement_ratio'] / 100));
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
                            'desc' => '用户预存款先结算后消费'
                        ];
                        M('Qiye_money_list')->add($log);
                    }
                } else {
                    if ($staff['money'] < $post['money']) {
                        throw new \Exception(L_('用户企业预存款余额不足，无法扣减'));
                    } else {
                        if (M('Qiye_staff')->where(['staff_id' => $staff['staff_id']])->setDec('money', $post['money'])) {
                            $log = [
                                'staff_id' => $staff['staff_id'],
                                'uid' => $staff['job_number'],
                                'type' => 2,
                                'money' => $post['money'],
                                'time' => time(),
                                'ip' => get_client_ip(),
                                'desc' => '扣减预存款',
                                'after_money' => $staff['money'] - $post['money']
                            ];
                            M('Qiye_staff_money_list')->add($log);
                        } else {
                            $this->error(L_('扣除预存款失败,请查看余额是否充足'));
                        }
                    }
                }
                $data = [
                    'staff_id' => $post['staff_id'],
                    'operate' => $post['operate'],
                    'money' => $post['money'],
                    'time' => time(),
                    'ip' => get_client_ip(),
                    'remark' => '',
                    'status' => 1,
                    'handle_remark' => '先结算后消费自动审核通过',
                    'handle_time' => time(),
                    'admin_account' => ''
                ];
                $data = M('Qiye_apply_money')->create($data);
                if (!M('Qiye_apply_money')->add($data)) {
                    throw new \Exception(L_('申请失败'));
                } else {
                    M('Qiye_staff')->where(['staff_id' => $staff['staff_id']])->setInc('unpaid_money', $post['money']);
                }
            }
        } catch (\Exception $e) {
            $rs['error_no'] = 1;
            $rs['error_msg'] = $e->getMessage();
        }

        $this->ajaxReturn($rs);
    }


    public function clearBalance()
    {
        $post = $_POST;
        $mod = M('Qiye_staff');
        $rs = ['error_no' => 0, 'error_msg' => ''];
        try {
            $qiye = M('Qiye')->where(['qiye_id' => $this->qiyeId])->find();
            if (!$qiye) {
                throw new \Exception(L_('企业不存在'));
            }
            if (empty($post['password'])) {
                throw new \Exception(L_('操作密码不能为空'));
            }
            if ($qiye['operate_pwd'] != md5($post['password'] . $qiye['salt'])) {
                throw new \Exception(L_('操作密码不正确'));
            }
            if (empty($post['staff_id'])) {
                throw new \Exception(L_('至少选择一个员工'));
            }

            $staffMod = M('Qiye_staff');
            $logMod = M('Qiye_staff_money_list');
            $stafffids = array_unique($post['staff_id']);
            foreach ($stafffids as $s) {
                $staff = $staffMod->where(['staff_id' => $s])->find();
                if (!$staff || $staff['money'] == 0) {
                    continue;
                }
                if ($staffMod->where(['staff_id' => $s])->save(['money' => 0])) {
                    $log = [
                        'staff_id' => $staff['staff_id'],
                        'uid' => $staff['job_number'],
                        'type' => 2,
                        'money' => $staff['money'],
                        'time' => time(),
                        'ip' => get_client_ip(),
                        'desc' => '清零,操作人:' . $this->enterprise_session['account'],
                        'now_money' => 0
                    ];
                    $logMod->add($log);
                }
            }
        } catch (\Exception $e) {
            $rs['error_no'] = 1;
            $rs['error_msg'] = $e->getMessage();
        }
        $this->json($rs);
    }

    public function mBalanceByBatch()
    {
        $ids = isset($_GET['ids']) ? trim($_GET['ids']) : '';
        $staffIds = explode(',', $ids);
        $count = M('Qiye_staff')->where(['staff_id' => ['IN', $ids]])->count();
        $this->assign('count', $count);
        $this->assign('staffIds', $ids);
        $this->display();
    }

    public function importStaff()
    {
        if (IS_POST) {
            if ($_FILES['file']['error'] != 4) {
                $uploadDir = './upload/excel/qiye/staff/' . date('Ymd') . '/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                import('ORG.Net.UploadFile');
                $config = [
                    'maxSize' => 10 * 1024 * 1024,
                    'allowExts' => ['xls', 'xlsx'],
                    'savePath' => $uploadDir,
                    'thumb' => false,
                    'saveRule' => 'uniqid'
                ];
                $upload = new UploadFile($config);
                if ($upload->upload()) {
                    $uploadList = $upload->getUploadFileInfo();
                    $file = $uploadList['0']['savepath'] . $uploadList['0']['savename'];
                    $data = format_excel2array($file);
                    if (empty($data)) {
                        $this->error('excel没数据');
                    }else{
                        $staffNumber = [];
                        $phone = [];
                        $staff = [];
                        $tm = time();
                        $staffMod = M('Qiye_staff');
                        $staffGroupMod = M('Qiye_staff_group');
                        foreach ($data as $k => $d) {
                            if ($k == 1) {
                                continue;
                            }
                            if (empty($d['A'])) {
                                $this->error(L_('卡号/工号/ID不能为空：' . $k . '行'));
                            }
                            if (in_array($d['A'], $staffNumber)) {
                                $this->error(L_('卡号/工号/ID重复：') . $d['A']);
                            }

                            if (empty($d['B'])) {
                                $this->error(L_('姓名不能为空：') . $k . '行');
                            }
                            if ($staffMod->where(['staff_number' => $d['A'], 'qiye_id' => $this->qiyeId])->find()) {
                                $this->error(L_('卡号/工号/ID已存在：') . $d['A']);
                            }

                            if (!empty($d['C'])) {
                                if (in_array($d['C'], $phone)) {
                                    $this->error(L_('手机号重复：') . $d['C']);
                                }
                                $user = M('User')->where(['phone' => $d['C']])->find();
                                if (!$user) {
                                    $this->error(L_('手机号未绑定平台用户：') . $d['C']);
                                }
                                if ($staffMod->where(['job_number' => $user['uid']])->find()) {
                                    $this->error(L_('该手机号已绑定员工账号，请勿重复绑定：') . $d['C']);
                                }
                                $d['job_number'] = $user['uid'];

                            }
                            if (empty($d['G']) && !in_array($d['G'], ['正常', '禁用'])) {
                                $this->error('“状态”一栏，只能填写“正常”或者“禁用”');
                            }
                            if (!empty($d['E'])) {
                                $group = $staffGroupMod->where(['qiye_id' => $this->qiyeId, 'name' => $d['E']])->find();
                                if (!$group) {
                                    $this->error('员工组别不存在：' . $d['E']);
                                }
                                $d['group_id'] = $group['group_id'];
                            }

                            $staff[] = [
                                'qiye_id' => $this->qiyeId,
                                'create_time' => $tm,
                                'staff_number' => $d['A'],
                                'job_number' => isset($d['job_number']) ? $d['job_number'] : 0,
                                'name' => $d['B'],
                                'phone' => $d['C'],
                                'card_id' => $d['D'],
                                'group_id' => isset($d['group_id']) ? $d['group_id'] : 0,
                                'remark' => $d['F'],
                                'status' => $d['G'] == '正常' ? 1 : 0
                            ];
                            $staffNumber[] = $d['A'];
                            if ($d['C']) {
                                $phone[] = $d['C'];
                            }
                        }
                        if ($staff) {
                            if ($staffMod->addAll($staff)) {
                                $this->success('成功');
                            } else {
                                $this->error($staffMod->getError());
                            }
                        } else {
                            $this->error('表格数据为空');
                        }
                    }

                } else {
                    $this->error($upload->getErrorMsg());
                }
            }
        }else{
            $this->display();
        }
    }
}
