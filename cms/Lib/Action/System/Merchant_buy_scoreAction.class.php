<?php
class Merchant_buy_scoreAction extends BaseAction{

    // 商家平台积分信息
    public function buy_score(){
        $condition_where = array();
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'mer_id') {
                $mer_id = trim($_GET['keyword']);
                $condition_where['a.mer_id'] = $mer_id;
            } else if ($_GET['searchtype'] == 'mer_name') {
                $condition_where['b.name'] = array('like', '%'. trim($_GET['keyword']).'%');
            }
        }
        if(!empty($_GET['start_time']) && !empty($_GET['end_time'])){
            $condition_where['a.add_time'] = array(array('gt',strtotime($_GET['start_time']." 00:00:00")),array('lt',strtotime($_GET['end_time']." 23:59:59")));
        }elseif($_GET['start_time']){
            $condition_where['a.add_time'] = array('gt',strtotime($_GET['start_time']." 00:00:00"));
        }elseif($_GET['end_time']){
            $condition_where['a.add_time'] = array('lt',strtotime($_GET['end_time']." 23:59:59"));
        }
        $m_merchant_score_order = M("Merchant_score_order");
        $sore_num = array();
        if($_GET['set_state'] != ''){
            if ($_GET['set_state'] == 1) {
                // 支付成功增加
                $condition_where['_string'] = "`paid`=1 AND `operation_type`=0 AND `set_type`=0";
                // 支付成功增加积分数
                $pay_score_add_num = $m_merchant_score_order->alias('a')
                    ->join('LEFT JOIN pigcms_merchant as b on a.mer_id=b.mer_id')
                    ->where($condition_where)->field('sum(sore_count) AS sore_count_num')->select();
                $sore_num['pay_score_add_num'] = $pay_score_add_num[0]['sore_count_num'];

            } elseif ($_GET['set_state'] == 2) {
                // 用户消费消耗
                $condition_where['_string'] = "`business_id`>0 AND `business_type`!='' AND `set_type`=1";
                // 用户消费消耗积分数
                $user_score_num = $m_merchant_score_order->alias('a')
                    ->join('LEFT JOIN pigcms_merchant as b on a.mer_id=b.mer_id')
                    ->where($condition_where)->field('sum(sore_count) AS sore_count_num')->select();
                $sore_num['user_score_num'] = $user_score_num[0]['sore_count_num'];
            } elseif ($_GET['set_state'] == 3) {
                // 管理员操作增加
                $condition_where['_string'] = "`operation_type`=1 AND `set_type`=0";
                // 管理员操作增加积分数
                $admin_score_add_num = $m_merchant_score_order->alias('a')
                    ->join('LEFT JOIN pigcms_merchant as b on a.mer_id=b.mer_id')
                    ->where($condition_where)->field('sum(sore_count) AS sore_count_num')->select();
                $sore_num['admin_score_add_num'] = $admin_score_add_num[0]['sore_count_num'];
            } elseif ($_GET['set_state'] == 4) {
                // 管理员操作减少
                $condition_where['_string'] = "`operation_type`=1 AND `set_type`=1";
                // 管理员操作减少积分数
                $admin_score_reduce_num = $m_merchant_score_order->alias('a')
                    ->join('LEFT JOIN pigcms_merchant as b on a.mer_id=b.mer_id')
                    ->where($condition_where)->field('sum(sore_count) AS sore_count_num')->select();
                $sore_num['admin_score_reduce_num'] = $admin_score_reduce_num[0]['sore_count_num'];
            }
        }else{
            $condition_where['_string'] = '(a.`paid`>=1) OR (a.`paid`=0 AND a.`operation_type`=1) OR (a.`paid`=0 AND a.`business_id`>0)';

            // 支付成功增加积分数
            $where_pay_score_add = $condition_where;
            $where_pay_score_add['_string'] = "a.`paid`=1 AND a.`operation_type`=0 AND a.`set_type`=0";
            $pay_score_add_num = $m_merchant_score_order->alias('a')
                ->join('LEFT JOIN pigcms_merchant as b on a.mer_id=b.mer_id')
                ->where($where_pay_score_add)->field('sum(sore_count) AS sore_count_num')->select();
            $sore_num['pay_score_add_num'] = $pay_score_add_num[0]['sore_count_num'];
            // 用户消费消耗积分数
            $where_user_score_num = $condition_where;
            $where_user_score_num['_string'] = "a.`business_id`>0 AND a.`business_type`!='' AND a.`set_type`=1";
            $user_score_num = $m_merchant_score_order->alias('a')
                ->join('LEFT JOIN pigcms_merchant as b on a.mer_id=b.mer_id')
                ->where($where_user_score_num)->field('sum(sore_count) AS sore_count_num')->select();
            $sore_num['user_score_num'] = $user_score_num[0]['sore_count_num'];
            // 管理员操作增加积分数
            $where_admin_score_num = $condition_where;
            $where_admin_score_num['_string'] = "a.`operation_type`=1 AND a.`set_type`=0";
            $admin_score_add_num = $m_merchant_score_order->alias('a')
                ->join('LEFT JOIN pigcms_merchant as b on a.mer_id=b.mer_id')
                ->where($where_admin_score_num)->field('sum(sore_count) AS sore_count_num')->select();
            $sore_num['admin_score_add_num'] = $admin_score_add_num[0]['sore_count_num'];
            // 管理员操作减少积分数
            $where_admin_score_num = $condition_where;
            $where_admin_score_num['_string'] = "a.`operation_type`=1 AND a.`set_type`=1";
            $admin_score_reduce_num = $m_merchant_score_order->alias('a')
                ->join('LEFT JOIN pigcms_merchant as b on a.mer_id=b.mer_id')
                ->where($where_admin_score_num)->field('sum(sore_count) AS sore_count_num')->select();
            $sore_num['admin_score_reduce_num'] = $admin_score_reduce_num[0]['sore_count_num'];

        }
        $this->assign('sore_num',$sore_num);


        $count = $m_merchant_score_order->alias('a')
            ->join('LEFT JOIN pigcms_merchant as b on a.mer_id=b.mer_id')
            ->join('LEFT JOIN pigcms_user as c on a.operation=c.uid')
            ->where($condition_where)->count();
        import('@.ORG.system_page');
        $p = new Page($count, 15);
        $orderList= $m_merchant_score_order->alias('a')
            ->join('LEFT JOIN pigcms_merchant as b on a.mer_id=b.mer_id')
            ->join('LEFT JOIN pigcms_user as c on a.operation=c.uid')
            ->where($condition_where)->field('a.*,b.name,c.nickname,c.phone')->order('order_id DESC')->limit($p->firstRow,$p->listRows)->select();
        $this->assign('pagebar',$p->show());
        $this->assign('orderList',$orderList);

        $this->display();
    }


    public function buy_score_export() {
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'mer_id') {
                $mer_id = trim($_GET['keyword']);
                $condition_where['a.mer_id'] = $mer_id;
            } else if ($_GET['searchtype'] == 'mer_name') {
                $condition_where['b.name'] = array('like', '%'. trim($_GET['keyword']).'%');
            }
        }

        if(!empty($_GET['start_time']) && !empty($_GET['end_time'])){
            $condition_where['a.add_time'] = array(array('gt',strtotime($_GET['start_time']." 00:00:00")),array('lt',strtotime($_GET['end_time']." 23:59:59")));
        }elseif($_GET['start_time']){
            $condition_where['a.add_time'] = array('gt',strtotime($_GET['start_time']." 00:00:00"));
        }elseif($_GET['end_time']){
            $condition_where['a.add_time'] = array('lt',strtotime($_GET['end_time']." 23:59:59"));
        }
        $m_merchant_score_order = M("Merchant_score_order");
        $sore_num = array();
        if($_GET['set_state'] != ''){
            if ($_GET['set_state'] == 1) {
                // 支付成功增加
                $condition_where['_string'] = "`paid`=1 AND `operation_type`=0 AND `set_type`=0";
                // 支付成功增加积分数
                $pay_score_add_num = $m_merchant_score_order->alias('a')
                    ->join('LEFT JOIN pigcms_merchant as b on a.mer_id=b.mer_id')
                    ->where($condition_where)->field('sum(sore_count) AS sore_count_num')->select();
                $sore_num['pay_score_add_num'] = $pay_score_add_num[0]['sore_count_num'];

            } elseif ($_GET['set_state'] == 2) {
                // 用户消费消耗
                $condition_where['_string'] = "`business_id`>0 AND `business_type`!='' AND `set_type`=1";
                // 用户消费消耗积分数
                $user_score_num = $m_merchant_score_order->alias('a')
                    ->join('LEFT JOIN pigcms_merchant as b on a.mer_id=b.mer_id')
                    ->where($condition_where)->field('sum(sore_count) AS sore_count_num')->select();
                $sore_num['user_score_num'] = $user_score_num[0]['sore_count_num'];
            } elseif ($_GET['set_state'] == 3) {
                // 管理员操作增加
                $condition_where['_string'] = "`operation_type`=1 AND `set_type`=0";
                // 管理员操作增加积分数
                $admin_score_add_num = $m_merchant_score_order->alias('a')
                    ->join('LEFT JOIN pigcms_merchant as b on a.mer_id=b.mer_id')
                    ->where($condition_where)->field('sum(sore_count) AS sore_count_num')->select();
                $sore_num['admin_score_add_num'] = $admin_score_add_num[0]['sore_count_num'];
            } elseif ($_GET['set_state'] == 4) {
                // 管理员操作减少
                $condition_where['_string'] = "`operation_type`=1 AND `set_type`=1";
                // 管理员操作减少积分数
                $admin_score_reduce_num = $m_merchant_score_order->alias('a')
                    ->join('LEFT JOIN pigcms_merchant as b on a.mer_id=b.mer_id')
                    ->where($condition_where)->field('sum(sore_count) AS sore_count_num')->select();
                $sore_num['admin_score_reduce_num'] = $admin_score_reduce_num[0]['sore_count_num'];
            }
        }else{
            $condition_where['_string'] = '(a.`paid`>=1) OR (a.`paid`=0 AND a.`operation_type`=1) OR (a.`paid`=0 AND a.`business_id`>0)';

            // 支付成功增加积分数
            $where_pay_score_add = $condition_where;
            $where_pay_score_add['_string'] = "a.`paid`=1 AND a.`operation_type`=0 AND a.`set_type`=0";
            $pay_score_add_num = $m_merchant_score_order->alias('a')
                ->join('LEFT JOIN pigcms_merchant as b on a.mer_id=b.mer_id')
                ->where($where_pay_score_add)->field('sum(sore_count) AS sore_count_num')->select();
            $sore_num['pay_score_add_num'] = $pay_score_add_num[0]['sore_count_num'];
            // 用户消费消耗积分数
            $where_user_score_num = $condition_where;
            $where_user_score_num['_string'] = "a.`business_id`>0 AND a.`business_type`!='' AND a.`set_type`=1";
            $user_score_num = $m_merchant_score_order->alias('a')
                ->join('LEFT JOIN pigcms_merchant as b on a.mer_id=b.mer_id')
                ->where($where_user_score_num)->field('sum(sore_count) AS sore_count_num')->select();
            $sore_num['user_score_num'] = $user_score_num[0]['sore_count_num'];
            // 管理员操作增加积分数
            $where_admin_score_num = $condition_where;
            $where_admin_score_num['_string'] = "a.`operation_type`=1 AND a.`set_type`=0";
            $admin_score_add_num = $m_merchant_score_order->alias('a')
                ->join('LEFT JOIN pigcms_merchant as b on a.mer_id=b.mer_id')
                ->where($where_admin_score_num)->field('sum(sore_count) AS sore_count_num')->select();
            $sore_num['admin_score_add_num'] = $admin_score_add_num[0]['sore_count_num'];
            // 管理员操作减少积分数
            $where_admin_score_num = $condition_where;
            $where_admin_score_num['_string'] = "a.`operation_type`=1 AND a.`set_type`=1";
            $admin_score_reduce_num = $m_merchant_score_order->alias('a')
                ->join('LEFT JOIN pigcms_merchant as b on a.mer_id=b.mer_id')
                ->where($where_admin_score_num)->field('sum(sore_count) AS sore_count_num')->select();
            $sore_num['admin_score_reduce_num'] = $admin_score_reduce_num[0]['sore_count_num'];

        }

        $title_msg = '支付成功增加总积分: ' . ($sore_num['pay_score_add_num'] ? $sore_num['pay_score_add_num'] : 0);
        $title_msg .= '；用户消费消耗总积分: ' . ($sore_num['user_score_num'] ? $sore_num['user_score_num'] : 0);
        $title_msg .= '；管理员操作增加总积分: ' . ($sore_num['admin_score_add_num'] ? $sore_num['admin_score_add_num'] : 0);
        $title_msg .= '；管理员操作减少总积分: ' . ($sore_num['admin_score_reduce_num'] ? $sore_num['admin_score_reduce_num'] : 0);



        $count = $m_merchant_score_order->alias('a')
            ->join('LEFT JOIN pigcms_merchant as b on a.mer_id=b.mer_id')
            ->join('LEFT JOIN pigcms_user as c on a.operation=c.uid')
            ->where($condition_where)->count();
        if($count <= 0 ){
            $this->error('无数据导出！');
        }

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';

        $title = $this->merchant_session['name'] . '商家-平台积分记录';

        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $length = ceil($count/1000);

        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle('共' . $count . '条记录');
            $objActSheet = $objExcel->getActiveSheet();
            $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('J')->setWidth(40);
            $objExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('L')->setWidth(20);

            $objExcel->getActiveSheet()->mergeCellsByColumnAndRow('0', '1',12, '1');
            $objExcel->getActiveSheet()->setCellValueByColumnAndRow('0', '1', $title_msg);

            $objActSheet->setCellValue('A2', '编号');
            $objActSheet->setCellValue('B2', '订单号');
            $objActSheet->setCellValue('C2', '商家名称');
            $objActSheet->setCellValue('D2', '支付金额');
            $objActSheet->setCellValue('E2', '购买/消耗积分数');
            $objActSheet->setCellValue('F2', '添加时间');
            $objActSheet->setCellValue('G2', '支付时间');
            $objActSheet->setCellValue('H2', '操作方式');
            $objActSheet->setCellValue('I2', '状态');
            $objActSheet->setCellValue('J2', '积分获得者/操作者');
            $objActSheet->setCellValue('K2', '操作类型');
            $objActSheet->setCellValue('L2', '备注');

            $orderList= $m_merchant_score_order->alias('a')
                ->join('LEFT JOIN pigcms_merchant as b on a.mer_id=b.mer_id')
                ->join('LEFT JOIN pigcms_user as c on a.operation=c.uid')
                ->where($condition_where)->field('a.*,b.name,c.nickname,c.phone')->order('order_id DESC')->limit($i * 1000 . ',' . 1000)->select();

            if (!empty($orderList)) {
                $index = 3;

                $cell_list = range('A','H');
                foreach ($cell_list as $cell) {
                    $objActSheet->getColumnDimension($cell)->setWidth(30);
                }

                foreach ($orderList as $value) {
                    $objActSheet->setCellValueExplicit('A' . $index, $value['order_id']);
                    $objActSheet->setCellValueExplicit('B' . $index, $value['orderid']);
                    $objActSheet->setCellValueExplicit('C' . $index, $value['name']);

                    if ($value['operation_type'] == 0 && intval($value['business_id']) <= 0) {
                        $objActSheet->setCellValueExplicit('D' . $index, $value['payment_money']);
                    } else {
                        $objActSheet->setCellValueExplicit('D' . $index, '-');
                    }

                    $objActSheet->setCellValueExplicit('E' . $index, $value['sore_count']);
                    $objActSheet->setCellValueExplicit('F' . $index, date('Y-m-d H:i:s', $value['add_time']));

                    if($value['pay_time']){
                        $objActSheet->setCellValueExplicit('G' . $index, date('Y-m-d H:i:s', $value['pay_time']));
                    }else{
                        $objActSheet->setCellValueExplicit('G' . $index, ' ');
                    }

                    if ($value['set_type'] == 0) {
                        if($value['pay_type'] == 'yue'){
                            $objActSheet->setCellValueExplicit('H' . $index, '在线充值');
                        }else if($value['pay_type'] == 'weixin'){
                            $objActSheet->setCellValueExplicit('H' . $index, '微信-在线充值');
                        }else if($value['pay_type'] == 'system'){
                            $objActSheet->setCellValueExplicit('H' . $index, '管理员操作');
                        }else{
                            $objActSheet->setCellValueExplicit('H' . $index, $value['pay_type']);
                        }
                    } else {
                        if($value['pay_type'] == 'system'){
                            $objActSheet->setCellValueExplicit('H' . $index, '管理员操作');
                        } else {
                            $objActSheet->setCellValueExplicit('H' . $index, '用户消耗');
                        }
                    }

                    if ($value['set_type'] == 0) {
                        if($value['paid'] == '1'){
                            $objActSheet->setCellValueExplicit('I' . $index, '支付成功');
                        }else if($value['paid'] == '0'){
                            $objActSheet->setCellValueExplicit('I' . $index, '未支付');
                        }else{
                            $objActSheet->setCellValueExplicit('I' . $index, '管理员操作');
                        }
                    } else {
                        if($value['paid'] == '2'){
                            $objActSheet->setCellValueExplicit('I' . $index, '管理员操作');
                        } else {
                            $objActSheet->setCellValueExplicit('I' . $index, '用户消耗');
                        }
                    }


                    if ($value['operation_type'] == 0 && $value['nickname']) {
                        $objActSheet->setCellValueExplicit('J' . $index, '获得用户： '. $value['nickname']);
                    } elseif($value['nickname']) {
                        $objActSheet->setCellValueExplicit('J' . $index, '管理员操作： ' .$value['nickname']);
                    }

                    if ($value['set_type'] == 1) {
                        $objActSheet->setCellValueExplicit('K' . $index, '减少');
                    } else {
                        $objActSheet->setCellValueExplicit('K' . $index, '增加');
                    }
                    $objActSheet->setCellValueExplicit('L' . $index, $value['remark']);
                    $index++;
                }
            }
            sleep(2);
            //输出
            $objWriter = new PHPExcel_Writer_Excel5($objExcel);
            ob_end_clean();
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
            header("Content-Transfer-Encoding:binary");
            $objWriter->save('php://output');
            exit();
        }
    }

}
?>