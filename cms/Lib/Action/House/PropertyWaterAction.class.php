<?php
/**
 * Created by PhpStorm.
 * User: win 10
 * Date: 2019/12/6
 * Time: 10:17
 */

class PropertyWaterAction extends BaseAction{

    public function money_list()
    {
        //物业对账-查看 权限
//        if (!in_array(32, $this->house_session['menus'])) {
//            $this->error('对不起，您没有权限执行此操作');
//        }

        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'order_name') {
                $condition['order_name'] = $_GET['keyword'];
            } else if ($_GET['searchtype'] == 'phone') {
                $condition['phone'] = $_GET['keyword'] ;
            } else if ($_GET['searchtype'] == 'name') {
                $condition['name'] = $_GET['keyword'] ;
            } else if ($_GET['searchtype'] == 'usernum') {
                $condition['usernum'] = $_GET['keyword'] ;
            }
        }

        //       if(isset($_GET['searchstatus'])){
        //           $condition['is_pay_bill'] = $_GET['searchstatus'];
        //       }
        // if(($_GET['searchstatus']) == 0){
        // 	$condition['pay_type'] = 1;
        // }

        if(($_GET['searchstatus']) == 1){
            $condition['pay_type'] = 0;
        }

        if(($_GET['searchstatus']) == 2){
            $condition['pay_type'] = 1;
        }

        if(isset($_GET['begin_time'])&&isset($_GET['end_time'])&&!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
            if ($_GET['begin_time']>$_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
            $time_condition = " (pay_time BETWEEN ".$period[0].' AND '.$period[1].")";
            $condition['pay_time_str']=$time_condition;
        }

        $house_village_list = M('House_village')->where(array('property_id'=>$this->property_id))->select();
        foreach($house_village_list as $k=>$v){
            $village_ids[] = $v['village_id'];
        }

        $condition['village_id']= $village_ids;
        $condition['paid'] = 1;
        $result = D('House_village_pay_order')->get_limit_pay_order($condition,20);
        $finshtotal = $total = 0;
        if($result){
            foreach ($result['order_list'] as &$v){
                $total += $v['money'];								//本页的总额
                $v['is_pay_bill'] && $v['is_own']!=5 && $finshtotal += $v['money'];	//本页已对账的总额
                //查询线下支付方式
                if ($v['cashier_id']&&$v['pay_type']) {
                    $condition_table  = array(C('DB_PREFIX').'house_village_pay_cashier_order'=>'o',C('DB_PREFIX').'house_village_pay_type'=>'t');
                    $condition_where = " `o`.`village_id` = `t`.`village_id`  AND `o`.`pay_type` = `t`.`id`  AND `o`.`cashier_id` =".$v['cashier_id'];
                    $condition_field = '`t`.`name`';
                    $pay_type = D('')->field($condition_field)->table($condition_table)->where($condition_where)->find();
                    $v['pay_type_name'] = $pay_type['name'] ? $pay_type['name'] : '';
                }
                //查询线上支付方式
                if ($v['pay_type']==0) {
                    if ($v['fid']) {
                        $business_id = $v['fid'];
                    } else {
                        $business_id = $v['cashier_id'] ? $v['cashier_id'] : $v['order_id'];
                    }
                    $online_pay_type = D('Plat_order')->field('pay_type,is_mobile_pay,paid')->where(array('business_type'=> array('in',array('house_village_pay','pile')),'business_id'=>$business_id))->find();
                    $v['online_pay_type'] = D('Plat_order')->get_pay_name($online_pay_type['pay_type'],$online_pay_type['is_mobile_pay'],$online_pay_type['paid']);
                }
                if(isset($v['phone']) && !empty($v['phone'])){
                    $v['phone']=phone_desensitization($v['phone']);
                }
            }
        }
        $this->assign('finshtotal',$finshtotal);
        $this->assign('total',$total);
        $this->assign('order_list',$result);
        $this->display();
    }

    protected  function get_alias_c_name(){
        $c_name = array(
            'all'=>'所有分类',
            'sqrecharge'=>'充值',
            'withdraw'=>'提现',
            'village_pay'=>'小区缴费',
            'village_pay_cashier'=>'小区收银台缴费',
            'express'=>'快递',
        );

        return $c_name ;
    }

    public function village_money_export()
    {
        //物业余额-导出 权限


        $village_id = $this->house_session['village_id'];
        //$type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'meal';

        $type = 'income';
        $title = '';

        switch ($type) {

            case 'income':
                $title = '收入明细';
                break;


        }
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        // 设置当前的sheet
        $objExcel->setActiveSheetIndex(0);


        $objExcel->getActiveSheet()->setTitle($type);
        $objActSheet = $objExcel->getActiveSheet();
        $cell_income   = array('type'=>'类型','order_id'=>'订单编号', 'num'=>'数量', 'money'=>'金额','use_time'=>'对账时间','system_take'=>'平台佣金','percent'=>'佣金百分比','now_village_money'=>'当前小区余额','desc'=>'描述');
        // 开始填充头部
        $cell_name = 'cell_'.$type;
        $cell_count = count($$cell_name);
        $cell_start = 1;
        for($f='A';$f<='Z';$f++,$cell_start++){
            if($cell_start>$cell_count){
                break;
            }
            $col_char[]=$f;
        }
        $col_k=0;
        foreach($$cell_name as $key=>$v){
            $objActSheet->getColumnDimension($col_char[$col_k])->setWidth(20);
            $objActSheet->setCellValue($col_char[$col_k].'1', $v);
            $col_k++;
        }
        $i = 2;
        //if($type=='income'){
        $where['village_id']=$village_id;
        if($_GET['type']&&$_GET['type']!='all'){
            $where['type']=$_GET['type'];
        }
        if($_GET['order_id']){
            $where['order_id']=$_GET['order_id'];
        }
        if($_GET['store_id']){
            $where['store_id']=$_GET['store_id'];
        }


        if(isset($_GET['begin_time'])&&isset($_GET['end_time'])&&!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
            if ($_GET['begin_time']>$_GET['end_time']) {
                $this->error("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));

            $time_condition=" (use_time BETWEEN ".$period[0].' AND '.$period[1].")";
            $where['_string']=$time_condition;
        }
        $result = M('Village_money_list')->field('type,order_id,num,pow(-1,income+1)*money as money,use_time,desc,system_take,percent,now_village_money')->where($where)->order('use_time DESC')->select();

        //}
        $alias_name = $this->get_alias_c_name();
        foreach ($result as $row) {
            $col_k=0;
            foreach($$cell_name as $k=>$vv){
                switch($k){
                    case 'type':
                        $objActSheet->setCellValue($col_char[$col_k] . $i, $alias_name[$row[$k]].' ');
                        break;
                    case 'order_id':
                        $objActSheet->setCellValue($col_char[$col_k] . $i, $row[$k].' ');
                        break;
                    case 'real_orderid':
                        $objActSheet->setCellValue($col_char[$col_k] . $i, $row[$k].' ');
                        break;
                    case 'orderid':
                        $objActSheet->setCellValue($col_char[$col_k] . $i, $row[$k].' ');
                        break;
                    case 'pay_time':
                        $objActSheet->setCellValue($col_char[$col_k] . $i, $row[$k]?date('Y-m-d H:i:s', $row[$k]) : '');
                        break;
                    case 'use_time':
                        $objActSheet->setCellValue($col_char[$col_k] . $i, $row[$k]?date('Y-m-d H:i:s', $row[$k]) : '');
                        break;
                    case 'desc':
                        $objActSheet->setCellValue($col_char[$col_k] . $i, $row[$k].' ');
                        break;
                    default:
                        $objActSheet->setCellValue($col_char[$col_k] . $i, $row[$k]);
                        break;
                }
                $col_k++;
            }
            if($type!='income'){
                $objActSheet->setCellValue($col_char[$cell_count-1] . $i, $row['balance_pay']+$row['coupon_price']+$row['score_deducte']+$row['payment_money']);
            }
            $i++;
        }
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