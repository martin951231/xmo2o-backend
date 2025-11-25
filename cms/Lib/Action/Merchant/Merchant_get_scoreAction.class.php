<?php
class Merchant_get_scoreAction extends BaseAction{

    // 商户平台积分信息
    public function index(){
        if(empty($this->merchant_session)){
            $this->error(L_('请先进行登录！'),U('Login/index'));
        }

        $mer_id = $this->merchant_session['mer_id'];
        $mer_info = D('Merchant')->get_info($mer_id);
        if(empty($mer_info)){
            $this->error_tips(L_('未获取到您的帐号信息，请重试！'));
        }

        $this->assign('mer_info',$mer_info);
        $condition_where['mer_id'] = $mer_id;
        // 时间
        if(!empty($_GET['start_time']) && !empty($_GET['end_time'])){
            $condition_where['time'] = array(array('gt',strtotime($_GET['start_time']." 00:00:00")),array('lt',strtotime($_GET['end_time']." 23:59:59")));
        }elseif($_GET['start_time']){
            $condition_where['time'] = array('gt',strtotime($_GET['start_time']." 00:00:00"));
        }elseif($_GET['end_time']){
            $condition_where['time'] = array('lt',strtotime($_GET['end_time']." 23:59:59"));
        }
        // 类型
        if($_GET['type']){               
            $condition_where['type'] = $_GET['type'];
        }

        $db_merchant_score_list = D("Merchant_score_list");
        $score_list = $db_merchant_score_list->get_limit_list_page($condition_where);
        $sore_num = array();
        $condition_where['type'] = 1;
        $score_get = $db_merchant_score_list->field('sum(score) as num')->where($condition_where)->find();
        $sore_num['score_get'] = floatval($score_get['num']) ? $score_get['num'] : 0;

        $condition_where['type'] = 2;
        $score_use = $db_merchant_score_list->field('sum(score) as num')->where($condition_where)->find();
        $sore_num['score_use'] = floatval($score_use['num']) ? $score_use['num'] : 0;
        $this->assign('score_list',$score_list);
        $this->assign('sore_num',$sore_num);

        $this->display();
    }




    public function score_export() {
        if(empty($this->merchant_session)){
            $this->error(L_('请先进行登录！'),U('Login/index'));
        }

        $mer_id = $this->merchant_session['mer_id'];
        $mer_info = D('Merchant')->get_info($mer_id);
        if(empty($mer_info)){
            $this->error_tips(L_('未获取到您的帐号信息，请重试！'));
        }

        $this->assign('mer_info',$mer_info);
        $condition_where['mer_id'] = $mer_id;
        // 时间
        if(!empty($_GET['start_time']) && !empty($_GET['end_time'])){
            $condition_where['time'] = array(array('gt',strtotime($_GET['start_time']." 00:00:00")),array('lt',strtotime($_GET['end_time']." 23:59:59")));
        }elseif($_GET['start_time']){
            $condition_where['time'] = array('gt',strtotime($_GET['start_time']." 00:00:00"));
        }elseif($_GET['end_time']){
            $condition_where['time'] = array('lt',strtotime($_GET['end_time']." 23:59:59"));
        }
        // 类型
        if($_GET['type']){               
            $condition_where['type'] = $_GET['type'];
        }

        $db_merchant_score_list = D("Merchant_score_list");
        $sore_num = array();
        $condition_count = $condition_where;
        $condition_count['type'] = 1;
        $score_get = $db_merchant_score_list->field('sum(score) as num')->where($condition_count)->find();
        $sore_num['score_get'] = floatval($score_get['num']) ? $score_get['num'] : 0;

        $condition_count['type'] = 2;
        $score_use = $db_merchant_score_list->field('sum(score) as num')->where($condition_count)->find();
        $sore_num['score_use'] = floatval($score_use['num']) ? $score_use['num'] : 0;

        $title_msg = L_('获得总积分: ') . $sore_num['score_get'];
        $title_msg .= L_('；消耗总积分: ') . $sore_num['score_use'];

        $count = $db_merchant_score_list->where($condition_where)->count();
        if($count <= 0 ){
            $this->error(L_('无数据导出！'));
        }

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';

        $title = $this->merchant_session['name'] . L_('平台商户积分记录');

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
            // $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
            // $objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
            // $objExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
            // $objExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
            // $objExcel->getActiveSheet()->getColumnDimension('J')->setWidth(40);
            // $objExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
            // $objExcel->getActiveSheet()->getColumnDimension('L')->setWidth(20);

            $objExcel->getActiveSheet()->mergeCellsByColumnAndRow('0', '1',12, '1');
            $objExcel->getActiveSheet()->setCellValueByColumnAndRow('0', '1', $title_msg);

            $objActSheet->setCellValue('A2', L_('编号'));
            $objActSheet->setCellValue('B2', L_('类型'));
            $objActSheet->setCellValue('C2', L_('积分数'));
            $objActSheet->setCellValue('D2', L_('添加时间'));
            $objActSheet->setCellValue('E2', L_('备注'));

            $_GET['page'] = $i+1;
            $score_list = $db_merchant_score_list->get_limit_list_page($condition_where,1000);
           
            if (!empty($score_list['list'])) {
                $index = 3;
                foreach ($score_list['list'] as $value) {
                    $objActSheet->setCellValueExplicit('A' . $index, $value['id']);
                    $objActSheet->setCellValueExplicit('B' . $index, $value['type']==1?L_('增加'):L_('消耗'));
                    $objActSheet->setCellValueExplicit('C' . $index, $value['score']);                    
                    $objActSheet->setCellValueExplicit('D' . $index, date('Y-m-d H:i:s',$value['time']));
                    $objActSheet->setCellValueExplicit('E' . $index, $value['desc']);
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

    // 积分商城订单
    public function gift_order(){
        if(empty($this->merchant_session)){
            $this->error(L_('请先进行登录！'),U('Login/index'));
        }
        $mer_id = $this->merchant_session['mer_id'];
        $mer_info = D('Merchant')->get_info($mer_id);
        if(empty($mer_info)){
            $this->error_tips(L_('未获取到您的帐号信息，请重试！'));
        }

        $this->assign('mer_info',$mer_info);
        $condition_where['mer_id'] = $mer_id;
        // 时间
        // if(!empty($_GET['start_time']) && !empty($_GET['end_time'])){
        //     $condition_where['time'] = array(array('gt',strtotime($_GET['start_time']." 00:00:00")),array('lt',strtotime($_GET['end_time']." 23:59:59")));
        // }elseif($_GET['start_time']){
        //     $condition_where['time'] = array('gt',strtotime($_GET['start_time']." 00:00:00"));
        // }elseif($_GET['end_time']){
        //     $condition_where['time'] = array('lt',strtotime($_GET['end_time']." 23:59:59"));
        // }
        
       
        $database_gift_order = D('Gift_order');
        $orders = $database_gift_order->get_order_list($this->merchant_session['mer_id'],0,true,20,2);
        $this->assign('list', $orders);
        $this->display();
    }


    public function gift_order_export() {
        if(empty($this->merchant_session)){
            $this->error(L_('请先进行登录！'),U('Login/index'));
        }

        $mer_id = $this->merchant_session['mer_id'];
        $mer_info = D('Merchant')->get_info($mer_id);
        if(empty($mer_info)){
            $this->error_tips(L_('未获取到您的帐号信息，请重试！'));
        }

        $this->assign('mer_info',$mer_info);
        $condition_where['mer_id'] = $mer_id;
    

        $database_gift_order = D('Gift_order');
        $orders = $database_gift_order->get_order_list($this->merchant_session['mer_id'],0,true,20,2);

        $count = $orders['total_count'];
        if($count <= 0 ){
            $this->error(L_('无数据导出！'));
        }

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';

        $title = $this->merchant_session['name'] . L_('商户积分商城订单记录');

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
            $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(30);
            $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);


            $objActSheet->setCellValue('A2', L_('编号'));
            $objActSheet->setCellValue('B2', L_('商品名称'));
            $objActSheet->setCellValue('C2', L_('数量'));
            $objActSheet->setCellValue('D2', L_('总价'));
            $objActSheet->setCellValue('E2', L_('订单状态'));
            $objActSheet->setCellValue('F2', L_('下单时间'));

            $_GET['page'] = $i+1;
            $orders = $database_gift_order->get_order_list($this->merchant_session['mer_id'],0,true,1000,2);
           
            if (!empty($orders['order_list'])) {
                $index = 3;
                foreach ($orders['order_list'] as $value) {
                    $objActSheet->setCellValueExplicit('A' . $index, $value['order_id']);
                    $objActSheet->setCellValueExplicit('B' . $index, $value['order_name']);
                    $objActSheet->setCellValueExplicit('C' . $index, $value['num']);
                    if ($value['exchange_type']==1) {
                        $momey_txt = C('config.score_name').'：' .$value['total_integral'].';'.L_('金额：').$value['total_price'];
                    }else{

                        $momey_txt = C('config.score_name').'：' .$value['total_integral'];
                    }
                    $objActSheet->setCellValueExplicit('D' . $index,$momey_txt);
                    $objActSheet->setCellValueExplicit('E' . $index,$value['paid']==1 ? L_('已支付') : L_('未支付'));
                    $objActSheet->setCellValueExplicit('F' . $index, date('Y-m-d H:i:s',$value['order_time']));
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

    public function merchant_login(){
        if($this->merchant_session){
            session('merchant_gift',$this->merchant_session);
            redirect($this->config['site_url'].'/index.php?c=Gift&a=index');
        }else{
            $this->error(L_('请先进行登录！'),U('Login/index'));
        }
    }

}
?>