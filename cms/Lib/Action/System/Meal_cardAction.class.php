<?php
/**
 * Created by PigCMS.
 * User: JJC.
 * Date: 2019/12/5.
 * Time: 11:51.
 */

/**
 * Class Meal_cardAction 定制：饭票模板管理
 */
class Meal_cardAction extends BaseAction
{
    //饭票管理页面
    public function index(){
        $model = D('Meal_card');
        $where = ['is_del'=>0];
        if($_GET['keyword']){
            $where['name'] = array('like','%'.$_GET['keyword'].'%');
        }

        if(!empty($_GET['begin_time'])){
            $where['create_time'] = array('gt',strtotime($_GET['begin_time'] . " 00:00:00"));
        }
        if(!empty($_GET['end_time'])){
            $end_time =strtotime($_GET['end_time'] . " 23:59:59");
            $where['_string'] = 'effective_day=0 OR effective_time<='.$end_time;
        }

        $count = $model->where($where)->count();
        import('@.ORG.system_page');
        $p = new Page($count, 30);
        $list = $model->field(true)->where($where)->order('id desc')->limit($p->firstRow . ',' . $p->listRows)->select();
        $list = $this->deal_list($list);
        $this->assign('list', $list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
    }

    private function deal_list($list){
        if($list){
            foreach($list as $key => $val){
                if($val['merchant']==0){
                    $list[$key]['merchant_str'] = '全部商家';
                }else{
                    $merchant = explode(',',$val['merchant']);
                    $list[$key]['merchant_str'] = count($merchant);
                }
                $list[$key]['recharge_sum'] = M('Card_new_recharge_order')->where(array('meal_card_id'=>$val['id']))->count();
				$where = array();
				$where['co.meal_card_id'] = $val['id'];
				$where['po.paid'] = 1;
				$where['po.business_type'] = 'card_new_recharge';
				$list[$key]['recharge_count'] = M('Card_new_recharge_order')->alias('co')->join('left join '.C('DB_PREFIX').'plat_order as po on co.order_id=po.business_id')->where($where)->count();
                $list[$key]['sum_money'] = $this->get_sum_money($val['id']);
            }
        }
        return $list;
    }

    private function get_sum_money($id){
        if(!$id) return 0;
        $model = M('Card_new_recharge_order');
        $where = array();
        $where['co.meal_card_id'] = $id;
        $where['po.paid'] = 1;
        $where['po.business_type'] = 'card_new_recharge';
        $sum = $model->alias('co')->join('left join '.C('DB_PREFIX').'plat_order as po on co.order_id=po.business_id')->where($where)->sum('pay_money');
        return $sum?$sum:0;
    }

    //单个饭票模板充值列表
    public function buy_list(){

        $id = $_GET['id'];
        $model = M('Card_new_recharge_order');
        $where = array();
		if($_GET['keyword']){
            if($_GET['searchtype']=='mer_name'){
                $where['m.name'] = array('like','%'.$_GET['keyword'].'%');
            }else{
                $where['u.nickname'] = array('like','%'.$_GET['keyword'].'%');
            }
        }
		if($id){
			$where['co.meal_card_id'] = $id;
		}
        $time_string = '';
        if(!empty($_GET['begin_time'])){
            $begin_time = strtotime($_GET['begin_time'] . " 00:00:00");
            $time_string .= 'po.pay_time>='.$begin_time;
        }
        if(!empty($_GET['end_time'])){
            $end_time =strtotime($_GET['end_time'] . " 23:59:59");
            if($time_string){
                $time_string .= ' AND po.pay_time<='.$end_time;
            }else{
                $time_string = ' AND po.pay_time<='.$end_time;
            }
        }
        if($time_string){
            $where['_string'] = $time_string;
        }
        $where['po.paid'] = 1;
        $where['po.business_type'] = 'card_new_recharge';
        $field = 'co.order_id,m.name as mer_name,u.nickname as user_name,co.money,po.pay_time,po.order_id as ppd';
        $count = $model->alias('co')
			->join('left join '.C('DB_PREFIX').'plat_order as po on co.order_id=po.business_id')
			->join('left join '.C('DB_PREFIX').'user as u on co.uid=u.uid')
            ->join('left join '.C('DB_PREFIX').'merchant as m on co.mer_id=m.mer_id')
			->where($where)->count();

        import('@.ORG.system_page');
        $p = new Page($count, 30);
        $list = $model->alias('co')
            ->join('left join '.C('DB_PREFIX').'plat_order as po on co.order_id=po.business_id')
            ->join('left join '.C('DB_PREFIX').'user as u on co.uid=u.uid')
            ->join('left join '.C('DB_PREFIX').'merchant as m on co.mer_id=m.mer_id')
            ->field($field)
            ->where($where)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        $this->assign('list', $list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
    }
	
	public function buy_export(){
		$param = $_POST;
        $param['type'] = 'meal_card_buy';
        $param['rand_number'] = $_SERVER['REQUEST_TIME'];

        if ($res = D('Order')->order_export($param)) {
            echo json_encode(array('error_code' => 0, 'msg' => '添加导出计划成功', 'file_name' => $res['file_name'], 'export_id' => $res['export_id'], 'rand_number' => $param['rand_number']));
        } else {
            echo json_encode(array('error_code' => 1, 'msg' => '导出失败'));
        }
        die;
		
		set_time_limit(0);
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title = '饭票购买记录';
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        // 设置当前的sheet
		
		
		$id = $_POST['id'];
        $model = M('Card_new_recharge_order');
        $where = array();
		//dump($_POST);exit;
		if($_POST['keyword']){
            if($_POST['searchtype']=='mer_name'){
                $where['m.name'] = array('like','%'.$_POST['keyword'].'%');
            }else{
                $where['u.nickname'] = array('like','%'.$_POST['keyword'].'%');
            }
        }
		if($id){
			$where['co.meal_card_id'] = $id;
		}
        $time_string = '';
        if(!empty($_POST['begin_time'])){
            $begin_time = strtotime($_POST['begin_time'] . " 00:00:00");
            $time_string .= 'po.pay_time>='.$begin_time;
        }
        if(!empty($_POST['end_time'])){
            $end_time =strtotime($_POST['end_time'] . " 23:59:59");
            if($time_string){
                $time_string .= ' AND po.pay_time<='.$end_time;
            }else{
                $time_string = ' AND po.pay_time<='.$end_time;
            }
        }
        if($time_string){
            $where['_string'] = $time_string;
        }
        $where['po.paid'] = 1;
        $where['po.business_type'] = 'card_new_recharge';
		$field = 'co.order_id,m.name as mer_name,u.nickname as user_name,co.money,po.pay_time,po.order_id as ppd';
		
        $count = $model->alias('co')
            ->join('left join '.C('DB_PREFIX').'plat_order as po on co.order_id=po.business_id')
            ->join('left join '.C('DB_PREFIX').'user as u on co.uid=u.uid')
            ->join('left join '.C('DB_PREFIX').'merchant as m on co.mer_id=m.mer_id')
            ->field($field)
            ->where($where)
            ->count();
			
        $length = ceil($count / 1000);
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle('第' . ($i + 1) . '个一千条购买记录');
            $objActSheet = $objExcel->getActiveSheet();

            $objActSheet->setCellValue('A1', '序号');
            $objActSheet->setCellValue('B1', '使用商家');
            $objActSheet->setCellValue('C1', '充值用户');
            $objActSheet->setCellValue('D1', '充值金额（元）');
            $objActSheet->setCellValue('E1', '充值时间');


			$list = $model->alias('co')
					->join('left join '.C('DB_PREFIX').'plat_order as po on co.order_id=po.business_id')
					->join('left join '.C('DB_PREFIX').'user as u on co.uid=u.uid')
					->join('left join '.C('DB_PREFIX').'merchant as m on co.mer_id=m.mer_id')
					->field($field)
					->where($where)
					->limit($i * 1000 . ',1000')->select();

            if (!empty($list)) {
                $index = 2;
                foreach ($list as $value) {

                    $objActSheet->setCellValueExplicit('A' . $index, $value['order_id']);
                    $objActSheet->setCellValueExplicit('B' . $index, $value['mer_name']);
                    $objActSheet->setCellValueExplicit('C' . $index, $value['user_name']);
                    $objActSheet->setCellValueExplicit('D' . $index, $value['money']);
                    $objActSheet->setCellValueExplicit('E' . $index, date('Y-m-d H:i:s', $value['pay_time']));
                    $index++;
                }
            }
            sleep(2);
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
        header('Content-Disposition:attachment;filename="' . $title . '_' . date("Y-m-d h:i:sa", time()) . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();
	}

    //花费饭票购买商品的交易记录（即原来的会员卡消费记录）
    public function spend_list(){
        import('@.ORG.system_page');
        $where = array();
        if($_GET['keyword']){
            if($_GET['searchtype']=='mer_name'){
                $where['m.name'] = array('like','%'.$_GET['keyword'].'%');
            }else{
                $where['uu.nickname'] = array('like','%'.$_GET['keyword'].'%');
            }
        }
        $time_string = '';
        if(!empty($_GET['begin_time'])){
            $begin_time = strtotime($_GET['begin_time'] . " 00:00:00");
            $time_string .= 'c.time>='.$begin_time;
        }
        if(!empty($_GET['end_time'])){
            $end_time =strtotime($_GET['end_time'] . " 23:59:59");
            if($time_string){
                $time_string .= ' AND c.time<='.$end_time;
            }else{
                $time_string = ' AND c.time<='.$end_time;
            }
        }
        if($time_string){
            $where['_string'] = $time_string;
        }


        $count = M(_view('Card_new_record'))
            ->join('as c left join ' . C('DB_PREFIX') . 'card_userlist as u ON c.card_id = u.id')
            ->join('left join ' . C('DB_PREFIX') . 'merchant as m on u.mer_id=m.mer_id')
            ->where($where)->count();

        $p = new Page($count, 30);
        $list = M(_view('Card_new_record'))
            ->field('c.*,uu.phone,uu.nickname,m.name as mer_name')
            ->join('as c left join ' . C('DB_PREFIX') . 'card_userlist as u ON c.card_id = u.id left join ' . C('DB_PREFIX') . 'user as uu on uu.uid=u.uid')
            ->join('left join ' . C('DB_PREFIX') . 'merchant as m on u.mer_id=m.mer_id')
            ->where($where)
            ->order('c.time DESC')
            ->limit($p->firstRow, $p->listRows)
            ->select();

        $pagebar = $p->show();
        $this->assign('list', $list);
        $this->assign('pagebar', $pagebar);
        $this->display();
    }
	
	public function spend_export(){
		$param = $_POST;
        $param['type'] = 'meal_card_spend';
        $param['rand_number'] = $_SERVER['REQUEST_TIME'];

        if ($res = D('Order')->order_export($param)) {
            echo json_encode(array('error_code' => 0, 'msg' => '添加导出计划成功', 'file_name' => $res['file_name'], 'export_id' => $res['export_id'], 'rand_number' => $param['rand_number']));
        } else {
            echo json_encode(array('error_code' => 1, 'msg' => '导出失败'));
        }
	die;
	}

    //新增&&编辑饭票模板页面
    public function add(){
        $db = M('Meal_card');
        if(IS_POST){
            $recharge_money =implode(',', array_filter($_POST['recharge_money']));
            $time = time();
            $merchant = $_POST['coupon_ids']?implode(',',$_POST['coupon_ids']):0;
            $effective_day = intval($_POST['effective_day']);
            $effective_time = $effective_day?$time+60*60*24*$effective_day:0;
            $status = intval($_POST['status']);
			$this->assign('info', $_POST);
            $id = intval($_POST['id']);
			
			if(empty($_POST['name'])){
				$this->error_tips('活动名称不能为空！');
			}
			if(empty($_POST['recharge_money'])){
				$this->error_tips('充值金额必填！');
			}else{
				$count = count($_POST['recharge_money']);
				if($count<3||$count>6){
					$this->error_tips('自定义充值金额只能设置3-6个');
				}
				foreach($_POST['recharge_money'] as $val){
					if($val<1){
						$this->error_tips('充值金额必须大于1！');
					}
				}
			}
			
			if($effective_day<0){
				$this->error_tips('活动有效期不能小于0！');
			}

            $data = array();
            $data['name'] = $_POST['name'];
            $data['recharge_money'] = $recharge_money;
            $data['merchant'] = $merchant;
            $data['effective_time'] = $effective_time;
            $data['effective_day'] = $effective_day;
            $data['status'] = $status;
            $data['create_time'] = $time;

            if($status==1){//只可以启用一个模板，这里需要加个判断
                $check_where = array('is_del'=>0,'status'=>1);
                //$check_where['_string'] = "effective_day=0 OR effective_time<".$time;
                if($id){
                    $check_where['id'] = array('neq',$id);
                }
                $check = $db->where($check_where)->find();
                if($check){
                    $this->error_tips('抱歉，只能启用一个模板！');
                }
            }

            if($id){//编辑
                $where = ['id'=>$id];
                $info = $db->where($where)->find();
                $data['effective_time'] = $info['create_time']+$effective_day*60*60*24;
                $result = $db->where($where)->save($data);
                if($result){
                    $this->success("编辑成功",U('index'));
                }else{
                    $this->error_tips('编辑失败！');
                }
            }else{//新增

                $result = $db->add($data);
                if($result){
                    $this->success("添加成功",U('index'));
                }else{
                    $this->error_tips('添加失败！');
                }
            }

        }else{
            $id = intval($_GET['id']);
            $where = ['id'=>$id];
            $info = $db->where($where)->find();
            if($info){
                $info['recharge_money_arr'] = explode(',',$info['recharge_money']);
                $info['merchant_arr'] = $this->getMerchantArr($info['merchant']);
            }
            $this->assign('info', $info);
            $this->display();
        }

    }

    public function delete_card(){
        $id = $_POST['id'];
		
		$model = M('Card_new_recharge_order');
        $where = array();
        $where['co.meal_card_id'] = $id;
        $where['po.paid'] = 1;
        $where['po.business_type'] = 'card_new_recharge';
        $check = $model->alias('co')->join('left join '.C('DB_PREFIX').'plat_order as po on co.order_id=po.business_id')->where($where)->count();
        
        if($check){
            $this->error('该模板已产生充值记录无法删除！');
        }
        $r = M('Meal_card')->where(array('id'=>$id))->setField('is_del',1);
        if($r){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }


    private function getMerchantArr($merchant){
        $arr = explode(',',$merchant);
        $where = array();
        $where['mer_id'] = array('in',$arr);
        return M('Merchant')->where($where)->field('mer_id,name')->select();
    }

    public function ajaxGetMerchant(){
        $selected = trim(I("selected"),',');
        if($selected){
            $selected_arr = explode(',', $selected);
        }

        $condition = array('mer_id'=>array('in', $selected));
        $order_string = '`mer_id` DESC';
        $coupon_list = D('Merchant')->field('mer_id,name')->where($condition)->order($order_string)->select();
        echo json_encode($coupon_list);
        exit;
    }
	
	



}

?>