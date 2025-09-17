<?php


class AnalysisAction extends BaseAction
{
    public function index()
    {
        $qiye = M('Qiye')->where(['qiye_id' => $this->qiyeId])->find();
        $qiye['unsettlement_money'] < 0 && $qiye['unsettlement_money'] = 0;
        $this->assign('qiye', $qiye);
        $this->display();
    }


    public function trade($invoke = false)
    {
        if (empty($_GET['type']) || $_GET['type'] == 'shop') {
            $_GET['type'] = 'shop';
            $table = 'shop_order';
            $timeField = 'o.create_time';
        } else if ($_GET['type'] == 'store') {
            $table = 'store_order';
            $timeField = 'o.dateline';
        } else if ($_GET['type'] == 'micro') {
            $table = 'micropay_order';
            $timeField = 'o.create_time';
        } else {
            $this->error(L_('业务类型不存在'));
        }
        $qiyeId = $this->qiyeId;
        $qiye = M('Qiye')->where(['qiye_id' => $qiyeId])->find();
        if (empty($qiye)) {
            $this->error(L_('企业不存在'));
        }
        $this->assign('qiye', $qiye);
        if ($table == 'micropay_order') {
            $where = [
                'qs.qiye_id' => $this->qiyeId,
                'o.status' => 1
            ];
        } else {
            $where = [
                'qs.qiye_id' => $this->qiyeId,
                'o.qiye_pay' => ['gt', 0],
                'o.paid' => 1
            ];
        }

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

        if ($table == 'micropay_order') {
            $statics = M($table)->alias('o')
                ->join('INNER JOIN ' . C('DB_PREFIX') . 'qiye_staff AS qs ON qs.job_number = o.uid')
                ->where($where)
                ->field("count(1) AS order_count,SUM(total_fee) AS total_qiye_pay,0 as total_service_charge")
                ->find();
        }else{
            $statics = M($table)->alias('o')
                ->join('INNER JOIN ' . C('DB_PREFIX') . 'qiye_staff AS qs ON qs.job_number = o.uid')
                ->where($where)
                ->field("count(1) AS order_count,SUM(qiye_pay) AS total_qiye_pay,SUM(qiye_settlement_money) as total_service_charge")
                ->find();
        }

        $arr['order_count'] = intval($statics['order_count']);
        $arr['total_qiye_pay'] = floatval($statics['total_qiye_pay']);
        $arr['service_charge'] = floatval($statics['total_service_charge']);
        import('@.ORG.merchant_page');
        $p = new Page($arr['order_count'], 10, true);
        $arr['pagebar'] = $p->show();
        if ($arr['order_count'] > 0) {
            if ($table == 'micropay_order') {
                $arr['sale_date'] = M($table)->alias('o')
                    ->join('INNER JOIN ' . C('DB_PREFIX') . 'qiye_staff AS qs ON qs.job_number = o.uid')
                    ->field("FROM_UNIXTIME($timeField,'%Y-%m-%d') AS date_time,count(1) AS count,sum(o.total_fee) AS total_qiye_pay,0 AS total_service_charge")
                    ->where($where)
                    ->group('date_time')
                    ->order('date_time DESC')
                    ->select();
            }else{
                $arr['sale_date'] = M($table)->alias('o')
                    ->join('INNER JOIN ' . C('DB_PREFIX') . 'qiye_staff AS qs ON qs.job_number = o.uid')
                    ->field("FROM_UNIXTIME($timeField,'%Y-%m-%d') AS date_time,count(o.order_id) AS count,sum(o.qiye_pay) AS total_qiye_pay,sum(qiye_settlement_money) AS total_service_charge")
                    ->where($where)
                    ->group('date_time')
                    ->order('date_time DESC')
                    ->select();
            }
        }
        if ($invoke) {
            return $arr;
        }
        $orderTypeAlias = $this->getTypeAlias();
        $pay_method = D('Config')->get_pay_method();
        $this->assign('type_name', $orderTypeAlias);
        $this->assign('pay_method', $pay_method);
        $this->assign($arr);
        $this->display();
    }

    public function exportTrade()
    {
        $data = $this->trade(true);
        $saleData = isset($data['sale_date']) ? $data['sale_date'] : [];
        if (empty($saleData)) {
            $this->error('没有数据');
        }
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title = '导出消费报表';
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
        $objActSheet->setCellValue('A1', '日期');
        $objActSheet->setCellValue('B1', '消费金额（元）');
        $objActSheet->setCellValue('C1', '消费笔数（元）');
        $objActSheet->setCellValue('D1', '应付手续费（元）');
        $objActSheet->setCellValue('E1', '应付结算金额');

        $index = 2;
        foreach ($saleData as $key => $value) {
            $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(25);
            $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(30);
            $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(30);
            $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(25);
            $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);

            $objActSheet->setCellValueExplicit('A' . $index, $value['date_time']);
            $objActSheet->setCellValueExplicit('B' . $index, floatval($value['total_qiye_pay']));
            $objActSheet->setCellValueExplicit('C' . $index, $value['count']);
            $objActSheet->setCellValueExplicit('D' . $index, floatval($value['total_service_charge']));
            $objActSheet->setCellValueExplicit('E' . $index, round($value['total_qiye_pay'] + $value['total_service_charge'], 2));
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

    public function settlement()
    {
        $qiye = M('Qiye')->where(['qiye_id' => $this->qiyeId])->find();
        import('@.ORG.merchant_page');
        $count = M('Qiye_apply_settlement')->where(['qiye_id' => $this->qiyeId])->count();
        $p = new Page($count, 10);
        $lists = M('Qiye_apply_settlement')->where(['qiye_id' => $this->qiyeId])->order('id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('money', $qiye['money'] ?: 0);
        $this->assign('lists', $lists);
        $this->assign('pagebar', $p->show());
        $this->display();
    }

    public function applySettlement()
    {
        $qiye = M('Qiye')->where(['qiye_id' => $this->qiyeId])->find();
        if (IS_POST) {
            if (empty($qiye)) {
                $this->error(L_('企业不存在'));
            }
            if ($this->qiyeId != $_POST['qiye_id']) {
                $this->error(L_('非法提交申请结算'));
            }
            if ($_POST['money'] <= 0) {
                $this->error(L_('申请结算金额必须大于0'));
            }
            $maxApplyMoney = $qiye['money'];
            if ($_POST['money'] > $maxApplyMoney) {
                $this->error(L_('最大申请结算金额不能超过' . $maxApplyMoney));
            }
            $data = [
                'qiye_id' => intval($_POST['qiye_id']),
                'money' => floatval($_POST['money']),
                'remark' => $_POST['remark'],
                'pay_date' => $_POST['pay_date'],
                'pic_info' => $_POST['pic_info'],
                'status' => 0,
                'ip' => get_client_ip(),
                'apply_time' => time()
            ];
            M('Qiye_apply_settlement')->startTrans();
            try {
                if (!M('Qiye_apply_settlement')->add($data)) {
                    $this->error(L_('提交申请失败'));
                } else {
                    //增加用户已申请未审核金额
                    $update = [
                        'money' => $qiye['money'] - $_POST['money'],
                        'unsettlement_money' => $qiye['unsettlement_money'] - $_POST['money'],
                        'apply_money' => $qiye['apply_money'] + $_POST['money']
                    ];
                    M('Qiye')->where(['qiye_id' => intval($_POST['qiye_id'])])->save($update);
                    M('Qiye_apply_settlement')->commit();
                    $this->success(L_('提交成功'));
                }
            } catch (\Exception $e) {
                M('Qiye_apply_settlement')->rollback();
                $this->error(L_('提交申请失败'));
            }
        } else {
            $maxApplyMoney = $qiye['money'];
            $this->assign('maxApplyMoney', $maxApplyMoney);
            $this->assign('qiye', $qiye);
            $this->display();
        }
    }

    public function getTypeAlias()
    {
        $orderTypeAlias = [
            'shop' => '快店',
            'store' => '到店付',
            'micro' => '新大陆扫码'
        ];
        if (!$this->config['shop_alias_name']) {
            unset($orderTypeAlias['shop']);
        } else {
            $orderTypeAlias['shop'] = $this->config['shop_alias_name'];
        }
        return $orderTypeAlias;
    }


    public function orders($isExport = false)
    {
        $orderTypeAlias = $this->getTypeAlias();
        $where = ['staff.qiye_id'=>$this->qiyeId];
        if (!empty($_GET['order_type'])) {
            $where['log.order_type'] = trim($_GET['order_type']);
        }
        if (!empty($_GET['store_id'])) {
            $where['log.store_id'] = intval($_GET['store_id']);
        }
        if (!empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            $where['log.time'] = [['gt', strtotime($_GET['begin_time'])], ['lt', strtotime($_GET['end_time']) + 86400]];
        }
        if (!empty($_GET['order_id'])) {
            $where['log.real_orderid'] = trim($_GET['order_id']);
        }

        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'qiye_staff AS staff ON staff.staff_id = log.staff_id ';
        $join .= 'LEFT JOIN ' . C('DB_PREFIX') . 'merchant_store AS store ON store.store_id = log.store_id';
        $fields = "FROM_UNIXTIME(log.time) AS log_date, staff.`name` AS staff_name, log.money, log.order_type, log.now_money,log.order_type,store.name AS store_name,log.real_orderid AS orderid";
        if ($isExport) {
            $orders = M('Qiye_staff_money_list')->alias('log')
                ->join($join)
                ->field($fields)
                ->where($where)
                ->order('log.id DESC')
                ->select();
            return $orders;
        }

        import('@.ORG.merchant_page');
        $count = M('Qiye_staff_money_list')->alias('log')
            ->join($join)
            ->where($where)->count();
        $p = new Page($count, 10);
        $orders = M('Qiye_staff_money_list')->alias('log')
            ->join($join)
            ->field($fields)
            ->where($where)->order('log.id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('orders', $orders);
        $this->assign('orderType', $orderTypeAlias);
        $this->assign('pagebar', $p->show());
        $this->display();
    }

    public function exportOrders()
    {
        $data = $this->orders(true);
        $orderType = $this->getTypeAlias();
        ini_set('memory_limit', '1024M');
        set_time_limit(3600);
        if (empty($data)) {
            $this->error('没有数据');
        }
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title = '消费记录';
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
        $objActSheet->setCellValue('A1', '支付时间');
        $objActSheet->setCellValue('B1', '员工名称');
        $objActSheet->setCellValue('C1', '消费金额');
        $objActSheet->setCellValue('D1', '消费类型');
        $objActSheet->setCellValue('E1', '店铺名称');
        $objActSheet->setCellValue('F1', '订单编号');
        $objActSheet->setCellValue('G1', '当前员工补贴金额');

        $index = 2;
        foreach ($data as $key => $value) {
            $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(25);
            $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(30);
            $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(30);
            $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(30);
            $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(30);
            $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(25);
            $objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);

            $objActSheet->setCellValueExplicit('A' . $index, $value['log_date']);
            $objActSheet->setCellValueExplicit('B' . $index, $value['staff_name']);
            $objActSheet->setCellValueExplicit('C' . $index, $value['money']);
            $objActSheet->setCellValueExplicit('D' . $index, isset($orderType[$value['order_type']]) ? $orderType[$value['order_type']] : '--');
            $objActSheet->setCellValueExplicit('E' . $index, $value['store_name']);
            $objActSheet->setCellValueExplicit('F' . $index, $value['orderid']);
            $objActSheet->setCellValueExplicit('G' . $index, $value['now_money']);
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

    public function stores()
    {
        import('@.ORG.merchant_page');
        $where = ['store.status' => ['NEQ', 4]];
        if (!empty($_GET['keyword'])) {
            $where['store.name'] = ['like', '%' . trim($_GET['keyword']) . '%'];
        }
        $count = M('Qiye_merchant')->alias('qm')
            ->join('INNER JOIN ' . C('DB_PREFIX') . 'merchant AS m ON qm.mer_id = m.mer_id AND qm.qiye_id =' . $this->qiyeId)
            ->join('INNER JOIN ' . C('DB_PREFIX') . 'merchant_store AS store ON store.mer_id = m.mer_id')
            ->where($where)
            ->count();
        $p = new Page($count, 10);
        if ($count > 0) {
            $stores = M('Qiye_merchant')->alias('qm')
                ->join('INNER JOIN ' . C('DB_PREFIX') . 'merchant AS m ON qm.mer_id = m.mer_id AND qm.qiye_id =' . $this->qiyeId)
                ->join('INNER JOIN ' . C('DB_PREFIX') . 'merchant_store AS store ON store.mer_id = m.mer_id')
                ->field('store.name,store.store_id')->where($where)->group('store.store_id')->limit($p->firstRow . ',' . $p->listRows)->select();
        } else {
            $stores = [];
        }
        $this->assign('stores', $stores);
        $this->assign('pagebar', $p->show());
        $this->display();
    }

    public function balance($isExport = false)
    {
        import('@.ORG.merchant_page');
        $where = ['q.qiye_id' => $this->qiyeId];
        if ($_GET['begin_time'] && $_GET['end_time']) {
            $startTime = strtotime($_GET['begin_time']);
            $endTime = strtotime($_GET['end_time']) + 86400;
            unset($_GET['selectTimeType']);
        } else if ($_GET['selectTimeType'] > 0) {
            $startTime = strtotime(date('Y-m-d', time() - ($_GET['selectTimeType'] - 1) * 86400));
            $endTime = time();
        }
        if ($startTime && $endTime) {
            $where['a.handle_time'] = [['gt', $startTime], ['lt', $endTime]];
        }
        if (isset($_GET['status']) && $_GET['status'] >= 0) {
            $where['a.status'] = $_GET['status'];
        }
        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'qiye_staff AS s ON a.staff_id = s.staff_id ';
        $join .= 'LEFT JOIN ' . C('DB_PREFIX') . 'qiye AS q ON q.qiye_id = s.qiye_id ';
        $join .= 'LEFT JOIN ' . C('DB_PREFIX') . 'qiye_staff_group AS g ON g.group_id = s.group_id';
        $count = M('Qiye_apply_money')->alias('a')->join($join)->where($where)->count();
        if($isExport){
            $lists = M('Qiye_apply_money')->alias('a')->join($join)->where($where)->field('a.*,s.job_number,s.name AS staff_name,g.name AS group_name')->order('a.id DESC')->select();
            return $lists;
        }
        $p = new Page($count, 10);
        $lists = M('Qiye_apply_money')->alias('a')->join($join)->where($where)->field('a.*,s.job_number,s.name AS staff_name,g.name AS group_name')->order('a.id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('lists', $lists);
        $this->assign('pagebar', $p->show());
        $this->assign('handleStatus', $this->_getHandleStatus());
        $this->display();
    }

    public function exportBalance()
    {
        $data = $this->balance(true);
        ini_set('memory_limit', '1024M');
        set_time_limit(3600);
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title = '余额操作记录';
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
        $objActSheet->setCellValue('A1', '操作时间');
        $objActSheet->setCellValue('B1', '卡号/工号/ID');
        $objActSheet->setCellValue('C1', '组别名称');
        $objActSheet->setCellValue('D1', '员工名称');
        $objActSheet->setCellValue('E1', '操作类型');
        $objActSheet->setCellValue('F1', '变动金额');
        $objActSheet->setCellValue('G1', '审核状态');
        $objActSheet->setCellValue('H1', '操作后余额');

        $index = 2;
        foreach ($data as $key => $value) {
            $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(25);
            $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
            $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(25);
            $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(25);
            $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(25);
            $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(25);
            $objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(25);
            $objExcel->getActiveSheet()->getColumnDimension('H')->setWidth(25);

            $objActSheet->setCellValueExplicit('A' . $index, date('Y-m-d H:i:s',$value['handle_time']));
            $objActSheet->setCellValueExplicit('B' . $index, $value['job_number']);
            $objActSheet->setCellValueExplicit('C' . $index, $value['group_name']?:'');
            $objActSheet->setCellValueExplicit('D' . $index, $value['staff_name']);
            $objActSheet->setCellValueExplicit('E' . $index, $value['operate']==1?'预存':'减扣');
            $objActSheet->setCellValueExplicit('F' . $index, $value['money']);
            $objActSheet->setCellValueExplicit('G' . $index, $value['status']==0?'待审核':($value['status']==1?'通过':'驳回'));
            $objActSheet->setCellValueExplicit('H' . $index, $value['after_money']);
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

    private function _getHandleStatus()
    {
        return [
            '0' => '待审核',
            '1' => '通过',
            '2' => '驳回'
        ];
    }
}
