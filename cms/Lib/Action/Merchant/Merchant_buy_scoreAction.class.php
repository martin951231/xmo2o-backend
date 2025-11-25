<?php
class Merchant_buy_scoreAction extends BaseAction{

    // 商户平台积分信息
    public function buy_score(){
        if(empty($this->merchant_session)){
            $this->error(L_('请先进行登录！'),U('Login/index'));
        }

        $mer_id = $this->merchant_session['mer_id'];
        $mer_info = D('Merchant')->get_info($mer_id);
        if(empty($mer_info)){
            $this->error_tips(L_('未获取到您的帐号信息，请重试！'));
        }
        if (empty($_GET['type'])) {
            $_GET['type'] = 'buy'; // 默认取购买记录
        }

        $this->assign('mer_info',$mer_info);
        $condition_where['mer_id'] = $mer_id;
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
                $condition_where['_string'] = "a.`paid`=1 AND a.`operation_type`=0 AND a.`set_type`=0";
                // 支付成功增加积分数
                $pay_score_add_num = $m_merchant_score_order->alias('a')->where($condition_where)->field('sum(sore_count) AS sore_count_num')->select();
                $sore_num['pay_score_add_num'] = $pay_score_add_num[0]['sore_count_num'];

            } elseif ($_GET['set_state'] == 2) {
                // 用户消费消耗
                $condition_where['_string'] = "a.`business_id`>0 AND a.`business_type`!='' AND a.`set_type`=1";
                // 用户消费消耗积分数
                $user_score_num = $m_merchant_score_order->alias('a')->where($condition_where)->field('sum(sore_count) AS sore_count_num')->select();
                $sore_num['user_score_num'] = $user_score_num[0]['sore_count_num'];
            } elseif ($_GET['set_state'] == 3) {
                // 管理员操作增加
                $condition_where['_string'] = "a.`operation_type`=1 AND a.`set_type`=0";
                // 管理员操作增加积分数
                $admin_score_add_num = $m_merchant_score_order->alias('a')->where($condition_where)->field('sum(sore_count) AS sore_count_num')->select();
                $sore_num['admin_score_add_num'] = $admin_score_add_num[0]['sore_count_num'];
            } elseif ($_GET['set_state'] == 4) {
                // 管理员操作减少
                $condition_where['_string'] = "a.`operation_type`=1 AND a.`set_type`=1";
                // 管理员操作减少积分数
                $admin_score_reduce_num = $m_merchant_score_order->alias('a')->where($condition_where)->field('sum(sore_count) AS sore_count_num')->select();
                $sore_num['admin_score_reduce_num'] = $admin_score_reduce_num[0]['sore_count_num'];
            }
        }else{
            $condition_where['_string'] = '(a.`paid`>=1) OR (a.`paid`=0 AND a.`operation_type`=1) OR (a.`paid`=0 AND a.`business_id`>0)';

            // 支付成功增加积分数
            $where_pay_score_add = $condition_where;
            $where_pay_score_add['_string'] = "a.`paid`=1 AND a.`operation_type`=0 AND a.`set_type`=0";
            $pay_score_add_num = $m_merchant_score_order->alias('a')->where($where_pay_score_add)->field('sum(sore_count) AS sore_count_num')->select();
            $sore_num['pay_score_add_num'] = $pay_score_add_num[0]['sore_count_num'];
            // 用户消费消耗积分数
            $where_user_score_num = $condition_where;
            $where_user_score_num['_string'] = "a.`business_id`>0 AND a.`business_type`!='' AND a.`set_type`=1";
            $user_score_num = $m_merchant_score_order->alias('a')->where($where_user_score_num)->field('sum(sore_count) AS sore_count_num')->select();
            $sore_num['user_score_num'] = $user_score_num[0]['sore_count_num'];
            // 管理员操作增加积分数
            $where_admin_score_num = $condition_where;
            $where_admin_score_num['_string'] = "a.`operation_type`=1 AND a.`set_type`=0";
            $admin_score_add_num = $m_merchant_score_order->alias('a')->where($where_admin_score_num)->field('sum(sore_count) AS sore_count_num')->select();
            $sore_num['admin_score_add_num'] = $admin_score_add_num[0]['sore_count_num'];
            // 管理员操作减少积分数
            $where_admin_score_num = $condition_where;
            $where_admin_score_num['_string'] = "a.`operation_type`=1 AND a.`set_type`=1";
            $admin_score_reduce_num = $m_merchant_score_order->alias('a')->where($where_admin_score_num)->field('sum(sore_count) AS sore_count_num')->select();
            $sore_num['admin_score_reduce_num'] = $admin_score_reduce_num[0]['sore_count_num'];

        }
        $this->assign('sore_num',$sore_num);


        $count = $m_merchant_score_order->alias('a')
            ->join('LEFT JOIN pigcms_user as c on a.operation=c.uid')
            ->where($condition_where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count, 10);
        $orderList= $m_merchant_score_order->alias('a')
            ->join('LEFT JOIN pigcms_user as c on a.operation=c.uid')
            ->where($condition_where)->field('a.*,c.nickname,c.phone')->order('order_id DESC')->limit($p->firstRow,$p->listRows)->select();
        $this->assign('pagebar',$p->show());
        $this->assign('orderList',$orderList);

        $this->display();
    }

    public function score_buy(){
        if(empty($this->merchant_session)){
            $this->error(L_('请先进行登录！'),U('Login/index'));
        }
        
        if($_GET['payment_money'] && $_GET['payment_money'] > 0){
            $mer_id = $this->merchant_session['mer_id'];
            $data_sms_buy_order['mer_id'] = $mer_id;
            $data_sms_buy_order['payment_money'] = $_GET['payment_money'];
            $data_sms_buy_order['sore_count'] = $this->config['merchant_buy_points']*$_GET['payment_money'];
            $data_sms_buy_order['add_time'] = $_SERVER['REQUEST_TIME'];
            $data_sms_buy_order['orderid'] = 'mer_'.date("ymdHis").rand(10,99).sprintf("%06d",$mer_id);

            if ($order_id = M('Merchant_score_order')->data($data_sms_buy_order)->add()) {
                redirect(U('Merchant_buy_score/merchant_buy_score_order_check', array('order_id' => $order_id, 'type' => 'buy_score')));
            } else {
                $this->error(L_('订单创建失败，请重试。'));
            }
        }
        $this->display();
    }

    public function merchant_buy_score_order_check(){
        if(empty($this->merchant_session)){
            $this->error(L_('请先进行登录！'),U('Login/index'));
        }
        if(!in_array($_GET['type'],array('buy_score'))){
            $this->error(L_('订单来源无法识别，请重试。'));
        }
        $mer_id = $this->merchant_session['mer_id'];

        $order_info = D('Merchant_score_order')->where(array('order_id'=>$_GET['order_id'],'mer_id'=>$mer_id))->find();
        $mer_info = D('Merchant')->get_info($mer_id);
        if(empty($mer_info)){
            $this->error_tips(L_('未获取到您的帐号信息，请重试！'));
        }

        $pay_method = D('Config')->get_pay_method();
        $tmp_pay_method['weixin'] = $pay_method['weixin'];
        if(empty($pay_method)){
            $this->error_tips(L_('系统管理员没开启任一一种支付方式！'));
        }
        $order_info['order_type'] = 'sqscorebuy';
        $this->assign('pay_method',array());
        $this->assign('mer_info',$mer_info);
        $this->assign('order_info',$order_info);

        $this->display();
    }


    public function go_pay_yue(){
        if(empty($this->merchant_session)){
            $this->error(L_('请先进行登录！'),U('Login/index'));
        }
        $mer_id = $this->merchant_session['mer_id'];
        $order_info = D('Merchant_score_order')->where(array('order_id'=>$_POST['order_id'],'mer_id'=>$mer_id))->find();

        if($order_info['paid'] == 1){
            exit(json_encode(array('error'=>4,'msg'=>L_('订单已支付'))));
        }

        $mer_info = D('Merchant')->get_info($mer_id);
        if(empty($mer_info)){
            $this->error_tips(L_('未获取到您的帐号信息，请重试！'));
        }
        if($mer_info['money']<$order_info['payment_money']){
            exit(json_encode(array('error'=>2,'msg'=>L_('当前商户余额不足请充值。'), 'url' => $this->config['site_url'] . '/merchant.php?g=Merchant&c=Merchant_money&a=mer_recharge')));
        }

        $use_money = D('Merchant_money_list')->use_money($mer_id, $order_info['payment_money'], 'buy_score', L_('购买平台积分扣除商家余额'),$_POST['order_id']);
        if (!$use_money['error_code']) {
            M('Merchant_score_order')->where(array('order_id'=>$_POST['order_id']))->data(array('paid'=>1,'pay_time'=>time(),'pay_type'=>'yue'))->save();
            M('Merchant')->where(array('mer_id'=>$mer_id))->setInc('platform_score',$order_info['sore_count']);
            exit(json_encode(array('error'=>1,'msg'=>L_('支付成功'))));
        } else {
            if ($use_money['msg']) {
                exit(json_encode(array('error'=>3,'msg'=>$use_money['msg'])));
            } else {
                exit(json_encode(array('error'=>3,'msg'=>L_('支付失败请重试'))));
            }
        }
    }


    public function buy_score_export() {
        if(empty($this->merchant_session)){
            $this->error(L_('请先进行登录！'),U('Login/index'));
        }

        $mer_id = $this->merchant_session['mer_id'];
        $mer_info = D('Merchant')->get_info($mer_id);
        if(empty($mer_info)){
            $this->error_tips(L_('未获取到您的帐号信息，请重试！'));
        }
        $condition_where['mer_id'] = $mer_id;

        if(!empty($_GET['start_time']) && !empty($_GET['end_time'])){
            $condition_where['add_time'] = array(array('gt',strtotime($_GET['start_time']." 00:00:00")),array('lt',strtotime($_GET['end_time']." 23:59:59")));
        }elseif($_GET['start_time']){
            $condition_where['add_time'] = array('gt',strtotime($_GET['start_time']." 00:00:00"));
        }elseif($_GET['end_time']){
            $condition_where['add_time'] = array('lt',strtotime($_GET['end_time']." 23:59:59"));
        }

        $m_merchant_score_order = M("Merchant_score_order");
        $sore_num = array();
        if($_GET['set_state'] != ''){
            if ($_GET['set_state'] == 1) {
                // 支付成功增加
                $condition_where['_string'] = "`paid`=1 AND `operation_type`=0 AND `set_type`=0";
                // 支付成功增加积分数
                $pay_score_add_num = $m_merchant_score_order->where($condition_where)->field('sum(sore_count) AS sore_count_num')->select();
                $sore_num['pay_score_add_num'] = $pay_score_add_num[0]['sore_count_num'];

            } elseif ($_GET['set_state'] == 2) {
                // 用户消费消耗
                $condition_where['_string'] = "`business_id`>0 AND `business_type`!='' AND `set_type`=1";
                // 用户消费消耗积分数
                $user_score_num = $m_merchant_score_order->where($condition_where)->field('sum(sore_count) AS sore_count_num')->select();
                $sore_num['user_score_num'] = $user_score_num[0]['sore_count_num'];
            } elseif ($_GET['set_state'] == 3) {
                // 管理员操作增加
                $condition_where['_string'] = "`operation_type`=1 AND `set_type`=0";
                // 管理员操作增加积分数
                $admin_score_add_num = $m_merchant_score_order->where($condition_where)->field('sum(sore_count) AS sore_count_num')->select();
                $sore_num['admin_score_add_num'] = $admin_score_add_num[0]['sore_count_num'];
            } elseif ($_GET['set_state'] == 4) {
                // 管理员操作减少
                $condition_where['_string'] = "`operation_type`=1 AND `set_type`=1";
                // 管理员操作减少积分数
                $admin_score_reduce_num = $m_merchant_score_order->where($condition_where)->field('sum(sore_count) AS sore_count_num')->select();
                $sore_num['admin_score_reduce_num'] = $admin_score_reduce_num[0]['sore_count_num'];
            }
        }else{
            $condition_where['_string'] = '(`paid`>=1) OR (`paid`=0 AND `operation_type`=1) OR (`paid`=0 AND `business_id`>0)';

            // 支付成功增加总积分
            $where_pay_score_add = $condition_where;
            $where_pay_score_add['_string'] = "`paid`=1 AND `operation_type`=0 AND `set_type`=0";
            $pay_score_add_num = $m_merchant_score_order->where($where_pay_score_add)->field('sum(sore_count) AS sore_count_num')->select();
            $sore_num['pay_score_add_num'] = $pay_score_add_num[0]['sore_count_num'];
            // 用户消费消耗总积分
            $where_user_score_num = $condition_where;
            $where_user_score_num['_string'] = "`business_id`>0 AND `business_type`!='' AND `set_type`=1";
            $user_score_num = $m_merchant_score_order->where($where_user_score_num)->field('sum(sore_count) AS sore_count_num')->select();
            $sore_num['user_score_num'] = $user_score_num[0]['sore_count_num'];
            // 管理员操作增加总积分
            $where_admin_score_num = $condition_where;
            $where_admin_score_num['_string'] = "`operation_type`=1 AND `set_type`=0";
            $admin_score_add_num = $m_merchant_score_order->where($where_admin_score_num)->field('sum(sore_count) AS sore_count_num')->select();
            $sore_num['admin_score_add_num'] = $admin_score_add_num[0]['sore_count_num'];
            // 管理员操作减少总积分
            $where_admin_score_num = $condition_where;
            $where_admin_score_num['_string'] = "`operation_type`=1 AND `set_type`=1";
            $admin_score_reduce_num = $m_merchant_score_order->where($where_admin_score_num)->field('sum(sore_count) AS sore_count_num')->select();
            $sore_num['admin_score_reduce_num'] = $admin_score_reduce_num[0]['sore_count_num'];

        }

        $title_msg = L_('支付成功增加总积分: ') . ($sore_num['pay_score_add_num'] ? $sore_num['pay_score_add_num'] : 0);
        $title_msg .= L_('；用户消费消耗总积分: ') . ($sore_num['user_score_num'] ? $sore_num['user_score_num'] : 0);
        $title_msg .= L_('；管理员操作增加总积分: ') . ($sore_num['admin_score_add_num'] ? $sore_num['admin_score_add_num'] : 0);
        $title_msg .= L_('；管理员操作减少总积分: ') . ($sore_num['admin_score_reduce_num'] ? $sore_num['admin_score_reduce_num'] : 0);

        $count = $m_merchant_score_order->alias('a')
            ->join('LEFT JOIN pigcms_user as c on a.operation=c.uid')
            ->where($condition_where)->count();
        if($count <= 0 ){
            $this->error(L_('无数据导出！'));
        }

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';

        $title = $this->merchant_session['name'] . '商户-平台积分记录';

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

            $objExcel->getActiveSheet()->setTitle(L_('共X1条记录',$count));
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

            $objActSheet->setCellValue('A2', L_('编号'));
            $objActSheet->setCellValue('B2', L_('订单号'));
            $objActSheet->setCellValue('C2', L_('商户名称'));
            $objActSheet->setCellValue('D2', L_('支付金额'));
            $objActSheet->setCellValue('E2', '购买/消耗积分数');
            $objActSheet->setCellValue('F2', L_('添加时间'));
            $objActSheet->setCellValue('G2', L_('支付时间'));
            $objActSheet->setCellValue('H2', L_('操作方式'));
            $objActSheet->setCellValue('I2', L_('状态'));
            $objActSheet->setCellValue('J2', '积分获得者/操作者');
            $objActSheet->setCellValue('K2', L_('操作类型'));
            $objActSheet->setCellValue('L2', L_('备注'));


            $orderList= $m_merchant_score_order->alias('a')
                ->join('LEFT JOIN pigcms_user as c on a.operation=c.uid')
                ->where($condition_where)->field('a.*,c.nickname,c.phone')->order('order_id DESC')->limit($i * 1000 . ',' . 1000)->select();

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
                            $objActSheet->setCellValueExplicit('H' . $index, L_('在线充值'));
                        }else if($value['pay_type'] == 'weixin'){
                            $objActSheet->setCellValueExplicit('H' . $index, '微信-在线充值');
                        }else if($value['pay_type'] == 'system'){
                            $objActSheet->setCellValueExplicit('H' . $index, L_('管理员操作'));
                        }else{
                            $objActSheet->setCellValueExplicit('H' . $index, $value['pay_type']);
                        }
                    } else {
                        if($value['pay_type'] == 'system'){
                            $objActSheet->setCellValueExplicit('H' . $index, L_('管理员操作'));
                        } else {
                            $objActSheet->setCellValueExplicit('H' . $index, L_('用户消耗'));
                        }
                    }

                    if ($value['set_type'] == 0) {
                        if($value['paid'] == '1'){
                            $objActSheet->setCellValueExplicit('I' . $index, L_('支付成功'));
                        }else if($value['paid'] == '0'){
                            $objActSheet->setCellValueExplicit('I' . $index, L_('未支付'));
                        }else{
                            $objActSheet->setCellValueExplicit('I' . $index, L_('管理员操作'));
                        }
                    } else {
                        if($value['paid'] == '2'){
                            $objActSheet->setCellValueExplicit('I' . $index, L_('管理员操作'));
                        } else {
                            $objActSheet->setCellValueExplicit('I' . $index, L_('用户消耗'));
                        }
                    }


                    if ($value['operation_type'] == 0 && $value['nickname']) {
                        $objActSheet->setCellValueExplicit('J' . $index, L_('获得用户： '). $value['nickname']);
                    } elseif($value['nickname']) {
                        $objActSheet->setCellValueExplicit('J' . $index, L_('管理员操作： ') .$value['nickname']);
                    }

                    if ($value['set_type'] == 1) {
                        $objActSheet->setCellValueExplicit('K' . $index, L_('减少'));
                    } else {
                        $objActSheet->setCellValueExplicit('K' . $index, L_('增加'));
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