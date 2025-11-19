<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * Date: 2020/1/7
 * Time: 10:40
 * 物料管理
 */

class MaterielAction extends BaseAction{
    public $materiel_size = 10; // 图片大小 默认10M
    public function checkout_old_type(){
        $d_materiel_type_msg = M('Materiel_type_msg');
        $whereTmp=array('from_id'=>$this->house_session['village_id'],'from'=>0,'ftype_id'=>0,'is_sub_type'=>0);
        $fmateriel=$d_materiel_type_msg->where($whereTmp)->field('*')->order('type_id ASC')->find();
        if(empty($fmateriel)){
            $whereOld = array('status' => 0,'from'=>0,'ftype_id'=>0,'is_sub_type'=>1,'add_time'=>array('lt',1725120000));
            $whereOld['from_id'] = $this->house_session['village_id'];
            $oldmateriel_type=$d_materiel_type_msg->where($whereOld)->field('type_id')->select();
            if($oldmateriel_type){
                $data=array('type_name'=>'老数据分类','from_id'=>$this->house_session['village_id'],'stock_num'=>0,'checkout_num'=>0);
                $nowtime=time();
                $data['add_time'] = $nowtime;
                $data['last_time']= $nowtime;
                $data['other_id'] = $this->house_session['property_id'] ? $this->house_session['property_id']:0;
                $data['xsort']= 999;
                $data['is_sub_type']= 0;
                $type_id = $d_materiel_type_msg->data($data)->add();
                $d_materiel_type_msg->where($whereOld)->data(array('ftype_id'=>$type_id))->save();
                return $type_id;
            }
        }
        return false;
    }
    public function index(){
        $isJsdhwy = D('Config_customization')->getPropertyRobotJsdhwy();
        $where = array('status' => 0,'from'=>0);
        if ($isJsdhwy){
            $this->checkout_old_type();
            $where['ftype_id']=0;
            $where['is_sub_type']=0;
        }
        $type_name =$_GET['type_name'] ? trim($_GET['type_name']):"";
        $where['from_id'] = $this->house_session['village_id'];
        if ($type_name) {
            $where['type_name'] = array('like', '%' . $type_name . '%');
        }
        $d_materiel_type_msg = M('Materiel_type_msg');
        $count = $d_materiel_type_msg->where($where)->count();
        $pageSize = 10;
        import('@.ORG.merchant_page');
        $p = new Page($count, $pageSize, 'page');
        
        $orderBy='`type_name` ASC, `type_id` DESC';
        $isAiHorse = D('Config_customization')->getIsAihorseCustomized();
        if($isAiHorse){
            $orderBy='CONVERT(`type_name` USING gbk) COLLATE gbk_chinese_ci ASC , `type_id` DESC';
        }
        if($isJsdhwy){
            $orderBy='xsort DESC, `type_id` DESC';
        }
        $list = $d_materiel_type_msg
            ->field('*,(stock_num-checkout_num) as surplus_num')
            ->where($where)->order($orderBy)
            ->limit($p->firstRow . ',' . $p->listRows)->select();
        $pagebar = $p->show();
        $totalPage = ceil($count / $pageSize);
        if($list){
            foreach ($list as $kk=>$vv){
                $list[$kk]['sub_type_count']=0;
                $countTmp=$d_materiel_type_msg->where(array('ftype_id'=>$vv['type_id'],'status' => 0,'from'=>0,'from_id'=> $this->house_session['village_id'],'is_sub_type'=>1))->count();
                $list[$kk]['sub_type_count']=$countTmp;
            }
        }
        $this->assign('list' , $list);
        $this->assign('pagebar' , $pagebar);
        $this->assign('totalPage' , $totalPage);
        
        if($isJsdhwy){
            $isAiHorse=true;
        }
        $this->assign('isAiHorse' , $isAiHorse);
        if($isJsdhwy){
            $this->display('index_fp');
        }else{
            $this->display();  
        }
    }

    public function index_sub(){
        $isJsdhwy = D('Config_customization')->getPropertyRobotJsdhwy();
        $type_name =$_GET['type_name'] ? trim($_GET['type_name']):"";
        $ftype_id =$_GET['ftype_id'] ? trim($_GET['ftype_id']):0;
        $where = array('status' => 0,'from'=>0,'ftype_id'=>$ftype_id,'is_sub_type'=>1);
        $where['from_id'] = $this->house_session['village_id'];
        if ($type_name) {
            $where['type_name'] = array('like', '%' . $type_name . '%');
        }
        $d_materiel_type_msg = M('Materiel_type_msg');
        $count = $d_materiel_type_msg->where($where)->count();
        $pageSize = 10;
        import('@.ORG.merchant_page');
        $p = new Page($count, $pageSize, 'page');
        $orderBy='`type_name` ASC, `type_id` DESC';
        $isAiHorse = D('Config_customization')->getIsAihorseCustomized();
        if($isAiHorse){
            $orderBy='CONVERT(`type_name` USING gbk) COLLATE gbk_chinese_ci ASC , `type_id` DESC';
        }
        if($isJsdhwy){
            $orderBy='xsort DESC, `type_id` DESC';
        }
        $list = $d_materiel_type_msg
            ->field('type_id,type_name,stock_num, checkout_num, stock_num-checkout_num as surplus_num')
            ->where($where)->order($orderBy)
            ->limit($p->firstRow . ',' . $p->listRows)->select();

        $pagebar = $p->show();
        $totalPage = ceil($count / $pageSize);
        $this->assign('list' , $list);
        $this->assign('pagebar' , $pagebar);
        $this->assign('totalPage' , $totalPage);
        $whereTmp=array('type_id'=>$ftype_id,'from_id'=>$this->house_session['village_id'],'from'=>0,'ftype_id'=>0,'is_sub_type'=>0);
        $fmateriel=$d_materiel_type_msg->where($whereTmp)->field('type_id,type_name')->find();
        $this->assign('ftype_name' , $fmateriel['type_name']);
        if($isJsdhwy){
            $isAiHorse=true;
        }
        $this->assign('isAiHorse' , $isAiHorse);
        $this->assign('ftype_id' , $ftype_id);
        
        $this->display();
    }
    public function stock_export()
    {
        $type_name = $_GET['type_name'];
        $ftype_id =$_GET['ftype_id'] ? trim($_GET['ftype_id']):0;
        $where = array('status' => 0);
        if($ftype_id>0){
            $where['ftype_id']=$ftype_id;
        }
        $where['from_id'] = $this->house_session['village_id'];
        if ($type_name) {
            $where['type_name'] = array('like', '%' . $type_name . '%');
        }
        $d_materiel_type_msg = M('Materiel_type_msg');
        $count = $d_materiel_type_msg->where($where)->count();
        if (! $count) {
            $this->error('没有任何导出信息！');
        }
        $db_materiel_warehousing = M('Materiel_warehousing');
        
        $nowTime = time();
        $nowTimeString = date('Y-m-d H:i:s', $nowTime);
        set_time_limit(0);
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title = "物料类别剩余库存记录";
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        $length = ceil($count / 1000);
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);
            $objExcel->getActiveSheet()->setTitle('第' . ($i + 1) . '个一千个订单信息');
            $objActSheet = $objExcel->getActiveSheet();
            $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);

            $objActSheet->setCellValue('A1', '类别名称');
            $objActSheet->setCellValue('B1', '数量（剩余库存）');
            $objActSheet->setCellValue('C1', '导出时间');
            $objActSheet->setCellValue('D1', '单价（入库均价）');
            
            $list = $d_materiel_type_msg
                ->field('type_id,type_name,stock_num, checkout_num, stock_num-checkout_num as surplus_num')
                ->where($where)->order('`type_name` ASC, `type_id` DESC') ->limit($i * 1000,1000)->select();

            if (!empty($list)) {
                $index = 1;
                foreach ($list as $value) {
                    $sumPrice = $db_materiel_warehousing->where(['type_id' => $value['type_id']])->sum('price*num');
                    $stock_num = isset($value['stock_num']) && $value['stock_num'] ? intval($value['stock_num']) : 1;
                    $avgPrice = getFormatNumber(($sumPrice / $stock_num), 2);
                    $index++;
                    $objActSheet->setCellValueExplicit('A' . $index, $value['type_name']);
                    $objActSheet->setCellValueExplicit('B' . $index, intval($value['surplus_num']));
                    $objActSheet->setCellValueExplicit('C' . $index, $nowTimeString);
                    $objActSheet->setCellValueExplicit('D' . $index, $avgPrice);
                }
            }
            sleep(2);
        }

        $objWriter = new PHPExcel_Writer_Excel5($objExcel);
        ob_end_clean();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", $nowTime) . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();
    }
    
    // 添加物料类型
    public function add() {
        $type_id = I('type_id', 0, 'intval');
        $ftype_id = I('ftype_id', 0, 'intval');
        $d_materiel_type_msg = M('Materiel_type_msg');
        $isJsdhwy = D('Config_customization')->getPropertyRobotJsdhwy();
        if(IS_POST){
            $type_name = I('type_name', '', 'trim');
            $xsort = I('xsort', 0, 'intval');
            if (!$type_name) {
                $this->error('名称不能为空！');
            }
            $data = array(
                'type_name' => $type_name,
                'xsort'=>$xsort
            );
            if ($type_id) {
                $data['last_time'] = time();
                $add_id = $d_materiel_type_msg->where(array('type_id' => $type_id))->data($data)->save();
                if ($add_id) {
                    $msg = array(
                        'message' => '编辑成功！',
                        'status' => 1
                    );
                    $this->ajaxReturn($msg);
                } else {
                    $this->error('编辑失败！');
                }
            } else {
                $data['add_time'] = time();
                $data['from_id'] = $this->house_session['village_id'];
                $data['other_id'] = $this->house_session['property_id']?$this->house_session['property_id']:0;
                if($isJsdhwy){
                    if($ftype_id>0){
                        $data['ftype_id']=$ftype_id;
                    }else{
                        $data['is_sub_type']=0;
                    }
                }
                $add_id = $d_materiel_type_msg->data($data)->add();
                if ($add_id) {
                    $msg = array(
                        'message' => '添加成功！',
                        'status' => 1
                    );
                    $this->ajaxReturn($msg);
                } else {
                    $this->error('添加失败！');
                }
            }
        } else {
            $ftype_name='';
            if($ftype_id>0){
                $whereTmp=array('type_id'=>$ftype_id,'from_id'=>$this->house_session['village_id'],'from'=>0,'ftype_id'=>0,'is_sub_type'=>0);
                $fmateriel=$d_materiel_type_msg->where($whereTmp)->field('type_id,type_name')->find();
                $ftype_name=$fmateriel ? $fmateriel['type_name']:'';
            }
            $this->assign('ftype_name' , $ftype_name);
            if ($type_id && $type_id>0) {
                $type_msg = $d_materiel_type_msg->where(array('type_id' => $type_id))->find();
                $this->assign('type_msg' , $type_msg);
            }
        }
        $this->display();
    }
    public function delMaterielType(){
        $type_id = I('post.type_id', 0, 'intval');
        $d_materiel_type_msg = M('Materiel_type_msg');
        if($type_id>0){
            $whereTmp=array('type_id'=>$type_id,'from_id'=>$this->house_session['village_id'],'from'=>0);
            $materiel=$d_materiel_type_msg->where($whereTmp)->field('*')->find();
            $nowtime=time();
            if($materiel){
                $delSave=array('del_time'=>$nowtime,'status'=>1);
                $d_materiel_type_msg->where($whereTmp)->data($delSave)->save();
                if($materiel['ftype_id']<1 && $materiel['is_sub_type']<1){
                    $delSave=array('del_time'=>$nowtime,'status'=>1);
                    $whereSubTmp=array('ftype_id'=>$type_id,'from_id'=>$this->house_session['village_id'],'from'=>0);
                    $d_materiel_type_msg->where($whereSubTmp)->data($delSave)->save();
                }
                $msg = array(
                    'message' => '操作成功！',
                    'err_code' => 0
                );
                $this->ajaxReturn($msg);
            }
        }
        $msg = array(
            'message' => '操作失败！',
            'err_code' => 1
        );
        $this->ajaxReturn($msg);
    }
    // 入库
    public function warehousing_index() {
        $type_id = I('type_id', '', 'intval');
        $village_id = $this->house_session['village_id'];
        if (empty($type_id)) {
            $list = array();
            $pagebar = '';
            $totalPage = 0;
        } else {
            $where = array('type_id' => $type_id);
            $d_materiel_warehousing = M('Materiel_warehousing');
            $count = $d_materiel_warehousing->where($where)->count();
            $pageSize = 10;
            import('@.ORG.merchant_page');
            $p = new Page($count, $pageSize, 'page');
            $list = $d_materiel_warehousing->field(true)->where($where)->order('`type_id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
            if(!empty($list)){
                $d_house_village=D('House_village');
                $village_info = $d_house_village->field('*')->where(array('village_id'=>$village_id))->find();
                foreach ($list as $kk=>$vv){
                    if(isset($vv['supplier_phone']) && !empty($vv['supplier_phone'])){
                        $list[$kk]['supplier_phone']=$d_house_village->phone_desensitization_v($vv['supplier_phone'],$village_id,$village_info);
                    }
                }

            }
            $pagebar = $p->show();
            $totalPage = ceil($count / $pageSize);
        }

        $this->assign('list' , $list);
        $this->assign('pagebar' , $pagebar);
        $this->assign('totalPage' , $totalPage);
        if ($type_id && $type_id>0) {
            $d_materiel_type_msg = M('Materiel_type_msg');
            $type_msg = $d_materiel_type_msg->where(array('type_id' => $type_id))->find();
            $this->assign('type_msg' , $type_msg);
        }
        $this->display();
    }

    public function warehousing_export()
    {
        $type_id = I('type_id', '', 'intval');
        $where = array('type_id' => $type_id);
        $d_materiel_warehousing = M('Materiel_warehousing');
        $count = $d_materiel_warehousing->where($where)->count();
        if (! $count) {
            $this->error('没有任何导出信息！');
        }
        $nowTime = time();
        $nowTimeString = date('Y-m-d H:i:s', $nowTime);
        set_time_limit(0);
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title = "入库记录";
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        $length = ceil($count / 1000);
        
        $d_materiel_type_msg = M('Materiel_type_msg');
        $type_msg = $d_materiel_type_msg->where(array('type_id' => $type_id))->find();
        
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);
            $objExcel->getActiveSheet()->setTitle('第' . ($i + 1) . '个一千个订单信息');
            $objActSheet = $objExcel->getActiveSheet();
            $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(40);

            $objActSheet->setCellValue('A1', '类别名称');
            $objActSheet->setCellValue('B1', '物料数量（入库数量）');
            $objActSheet->setCellValue('C1', '物料单价');
            $objActSheet->setCellValue('D1', '供应商名称');
            $objActSheet->setCellValue('E1', '供应商联系方式');
            $objActSheet->setCellValue('F1', '入库时间');
            $objActSheet->setCellValue('G1', '备注');
            
            $list = $d_materiel_warehousing->where($where)->order('`type_id` DESC')->limit($i * 1000,1000)->select();

            if (!empty($list)) {
                $index = 1;
                foreach ($list as $value) {
                    $index++;
                    $objActSheet->setCellValueExplicit('A' . $index, $type_msg['type_name']);
                    $objActSheet->setCellValueExplicit('B' . $index, $value['num']);
                    $objActSheet->setCellValueExplicit('C' . $index, $value['price']);
                    $objActSheet->setCellValueExplicit('D' . $index, $value['supplier_name'] ? $value['supplier_name'] : '');
                    $objActSheet->setCellValueExplicit('E' . $index, $value['supplier_phone'] ? $value['supplier_phone'] : '');
                    $objActSheet->setCellValueExplicit('F' . $index, $value['add_time'] ? date('Y-m-d H:i:s', $value['add_time']) : '');
                    $objActSheet->setCellValueExplicit('G' . $index, $value['remark'] ? $value['remark'] : '');
                }
            }
            sleep(2);
        }

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
    
    // 入库添加
    public function warehousing_add() {
        $type_id = I('type_id', '', 'intval');
        $id = I('id', '', 'intval');
        $d_materiel_warehousing = M('Materiel_warehousing');
        if(IS_POST){
            if (!$type_id || $type_id<=0) {
                $this->error('请先关联物料类别！');
            }
            $num = I('num', 0, 'floatval');
            if (!$num || $num<=0) {
                $this->error('请填写大于0的物料数量！');
            }
            $price = I('price', 0, 'floatval');
            if ($price<0) {
                $this->error('请填写不小于0的物料价格！');
            }
            $maintenance_time = I('maintenance_time', '', 'intval');
            $warehousing_time = I('warehousing_time', '');
            $supplier_name = I('supplier_name', '', 'trim');
            $supplier_phone = I('supplier_phone', '', 'trim');
            $remark = I('remark', '', 'trim');
            if ($warehousing_time) {
                $warehousing_time = strtotime($warehousing_time);
            }

            $data = array(
                'type_id' => $type_id,
                'num' => $num,
                'price' => $price,
                'maintenance_time' => $maintenance_time,
                'warehousing_time' => $warehousing_time,
                'supplier_name' => $supplier_name,
                'supplier_phone' => $supplier_phone,
                'remark' => $remark,
            );
            if ($id) {
                $data['last_time'] = time();
                unset($data['num']);
                $add_id = $d_materiel_warehousing->where(array('id' => $id))->data($data)->save();
                if ($add_id) {
                    $msg = array(
                        'message' => '编辑成功！',
                        'status' => 1
                    );
                    $this->ajaxReturn($msg);
                } else {
                    $this->error('编辑失败！');
                }
            } else {
                $data['add_time'] = time();
                $add_id = $d_materiel_warehousing->data($data)->add();
                if ($add_id) {
                    // 添加成功  同步添加 对应物料 入库数量
                    $d_materiel_type_msg = M('Materiel_type_msg');
                    $d_materiel_type_msg->where(array('type_id' => $type_id))->setInc('stock_num', $num);
                    $msg = array(
                        'message' => '添加成功！',
                        'status' => 1
                    );
                    $stock_num = $d_materiel_type_msg->where(array('type_id' => $type_id))->getField('stock_num');
                    $d_materiel_warehousing->where(['id' => $add_id])->setField('stock_num', $stock_num);
                    $this->ajaxReturn($msg);
                } else {
                    $this->error('添加失败！');
                }
            }
        } else {
            if ($id && $id>0) {
                $msg = $d_materiel_warehousing->where(array('id' => $id))->find();
                if ($msg['warehousing_time']) {
                    $msg['warehousing_time'] = date('Y-m-d H:i',$msg['warehousing_time']);
                }
                $this->assign('msg' , $msg);
            } elseif (!$type_id) {
                $this->error('请选择正确路径跳转！');
            }
        }
        $this->display();
    }
    
    // 出库管理
    public function checkoutList()
    {
        $type_id = I('type_id', '', 'intval');
        $receive_name = I('receive_name', '', 'strval');
        $receive_time_start = I('receive_time_start', '', 'strval');
        $receive_time_end = I('receive_time_end', '', 'strval');
        $select_type_id = I('select_type_id', '', 'intval');
        $where = array('receive_type' => 0,'from_id' => $this->house_session['village_id'], 'from' => 0);
        $village_id = $this->house_session['village_id'];
        if ($type_id > 0) {
            $where['type_id'] = $type_id;
        }else if($select_type_id>0){
            $where['type_id'] = $select_type_id;
        }
        if ($receive_name) {
            $where['receive_name'] = array('like', '%' . $receive_name . '%');
        }
        if ($receive_time_start) {
            $start = @strtotime($receive_time_start);
            $receive_time_start_txt = date('Y-m-d 00:00:00', $start);
            $start_time = @strtotime($receive_time_start_txt);
        }
        if ($receive_time_end) {
            $end = @strtotime($receive_time_end);
            $receive_time_end_txt = date('Y-m-d 23:59:59', $end);
            $end_time = @strtotime($receive_time_end_txt);
        }
        if (isset($start_time) && $start_time && isset($end_time) && $end_time) {
            $where['receive_time'] = [
                ['egt', $start_time], ['elt', $end_time]
            ];
        } elseif (isset($start_time) && $start_time) {
            $where['receive_time'] = ['egt', $start_time];
        } elseif (isset($end_time) && $end_time) {
            $where['receive_time'] = ['elt', $end_time];
        }
        $d_materiel_type_msg = M('Materiel_type_msg');
        $materiel_type_msgArr = array();
        $type_msgArr = $d_materiel_type_msg->where(array('from_id' => $this->house_session['village_id'], 'from' => 0,'status' => 0))->field('type_id,type_name')->select();
        if ($type_msgArr) {
            foreach ($type_msgArr as $tvv) {
                $materiel_type_msgArr[$tvv['type_id']] = $tvv['type_name'];
            }
        }
        $d_materiel_checkout = M('materiel_checkout');
        $count = $d_materiel_checkout->where($where)->count();
        $pageSize = 10;
        import('@.ORG.merchant_page');
        $p = new Page($count, $pageSize, 'page');
        $list = $d_materiel_checkout->where($where)->order('id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        if (!empty($list)) {
            $d_house_village=D('House_village');
            $village_info = $d_house_village->field('*')->where(array('village_id'=>$village_id))->find();
            foreach ($list as $kk => $vv) {
                if (isset($vv['receive_phone']) && !empty($vv['receive_phone'])) {
                    $list[$kk]['receive_phone']=$d_house_village->phone_desensitization_v($vv['receive_phone'],$village_id,$village_info);
                }
                $list[$kk]['type_name'] = '';
                if ($materiel_type_msgArr && isset($materiel_type_msgArr[$vv['type_id']])) {
                    $list[$kk]['type_name'] = $materiel_type_msgArr[$vv['type_id']];
                }
            }
        }
        $pagebar = $p->show();
        $totalPage = ceil($count / $pageSize);
        $this->assign('type_msgArr', $type_msgArr);
        $this->assign('list', $list);
        $this->assign('pagebar', $pagebar);
        $this->assign('totalPage', $totalPage);
        $this->assign('type_id', $type_id);
        $village_id = $this->house_session['village_id'];
        $this->assign('village_id', $village_id);
        $this->display();
    }
    
    // 出库详情
    public function checkout_index() {
        $type_id = I('type_id', '', 'intval');
        $checkout_id = I('checkout_id', '', 'intval');
        $receive_name = I('receive_name', '', 'strval');
        $status = I('status', 0, 'intval');
        $receive_time_start = I('receive_time_start', '', 'strval');
        $receive_time_end = I('receive_time_end', '', 'strval');
        $village_id = $this->house_session['village_id'];
        if (empty($type_id)) {
            $list = array();
            $pagebar = '';
            $totalPage = 0;
        } else {
            $where = array('mc.type_id' => $type_id, 'mc.receive_type'=>0);
            if ($checkout_id) {
                $where['cl.checkout_id'] = $checkout_id;
            }
            if ($receive_name) {
                $where['mc.receive_name'] = array('like', '%' . $receive_name . '%');
            }
            if ($status > 0) {
                $where['cl.status'] = $status - 1;
            }
           if ($receive_time_start) {
                $start = @strtotime($receive_time_start);
                $receive_time_start_txt = date('Y-m-d 00:00:00', $start);
                $start_time = @strtotime($receive_time_start_txt);
            }
           if ($receive_time_end) {
                $end = @strtotime($receive_time_end);
                $receive_time_end_txt = date('Y-m-d 23:59:59', $end);
                $end_time = @strtotime($receive_time_end_txt);
            }
            if (isset($start_time) && $start_time && isset($end_time) && $end_time) {
                $where['mc.receive_time'] = [
                    ['egt', $start_time], ['elt', $end_time]
                ];
            } elseif (isset($start_time) && $start_time) {
                $where['mc.receive_time'] = ['egt', $start_time];
            } elseif (isset($end_time) && $end_time) {
                $where['mc.receive_time'] = ['elt', $end_time];
            }
           
            $d_materiel_checkout_list = M('Materiel_checkout_list');
            $count = $d_materiel_checkout_list->join('as cl LEFT JOIN '.C('DB_PREFIX').'materiel_checkout as mc ON cl.checkout_id=mc.id')->where($where)->count();
            fdump_api(['where' => $where, 'sql' => $d_materiel_checkout_list->_sql(), 'count' => $count], '$d_materiel_checkout_list');
            $pageSize = 10;
            import('@.ORG.merchant_page');
            $p = new Page($count, $pageSize, 'page');
            $field = 'mc.type_id, mc.type_id, mc.receive_name, mc.receive_phone, mc.receive_time, mc.remark, mc.confirm_status, cl.repair_num, cl.status, cl.id';
            $list = $d_materiel_checkout_list->join('as cl LEFT JOIN '.C('DB_PREFIX').'materiel_checkout as mc ON cl.checkout_id=mc.id')->field($field)->where($where)->order('cl.id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
            if(!empty($list)){
                $d_house_village=D('House_village');
                $village_info = $d_house_village->field('*')->where(array('village_id'=>$village_id))->find();
                foreach ($list as $kk=>$vv){
                    if(isset($vv['receive_phone']) && !empty($vv['receive_phone'])){
                        $list[$kk]['receive_phone']=$d_house_village->phone_desensitization_v($vv['receive_phone'],$village_id,$village_info);
                    }
                }
            }
            $pagebar = $p->show();
            $totalPage = ceil($count / $pageSize);
        }
        $this->assign('list' , $list);
        $this->assign('pagebar' , $pagebar);
        $this->assign('totalPage' , $totalPage);
        if ($type_id && $type_id>0) {
            $d_materiel_type_msg = M('Materiel_type_msg');
            $type_msg = $d_materiel_type_msg->where(array('type_id' => $type_id))->find();
            $this->assign('type_msg' , $type_msg);
        }
        $this->display();
    }

    public function checkout_export()
    {
        $type_id = I('type_id', '', 'intval');
        $receive_name = I('receive_name', '', 'strval');
        $status = I('status', 0, 'intval');
        $receive_time_start = I('receive_time_start', '', 'strval');
        $receive_time_end = I('receive_time_end', '', 'strval');
        $d_materiel_type_msg = M('Materiel_type_msg');
        $select_type_id = I('select_type_id', '', 'intval');
        /*
        if (empty($type_id)) {
            $this->error('缺少物料类别id！');
        }
        $type_msg = $d_materiel_type_msg->where(array('type_id' => $type_id))->find();
        */
        $d_materiel_checkout = M('Materiel_checkout');
        $where = array('receive_type'=>0,'from_id' => $this->house_session['village_id'], 'from' => 0);
        if($type_id>0){
            $where['type_id']=$type_id;
        }else if($select_type_id>0){
            $where['type_id'] = $select_type_id;
        }
        if ($receive_name) {
            $where['receive_name'] = array('like', '%' . $receive_name . '%');
        }
        if ($receive_time_start) {
            $start = @strtotime($receive_time_start);
            $receive_time_start_txt = date('Y-m-d 00:00:00', $start);
            $start_time = @strtotime($receive_time_start_txt);
        }
        if ($receive_time_end) {
            $end = @strtotime($receive_time_end);
            $receive_time_end_txt = date('Y-m-d 23:59:59', $end);
            $end_time = @strtotime($receive_time_end_txt);
        }
        if (isset($start_time) && $start_time && isset($end_time) && $end_time) {
            $where['receive_time'] = [
                ['egt', $start_time], ['elt', $end_time]
            ];
        } elseif (isset($start_time) && $start_time) {
            $where['receive_time'] = ['egt', $start_time];
        } elseif (isset($end_time) && $end_time) {
            $where['receive_time'] = ['elt', $end_time];
        }
        $count = $d_materiel_checkout->where($where)->count();
        if (! $count) {
            $this->error('没有任何导出信息！');
        }

        set_time_limit(0);
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title = "物料领用记录";
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        $length = ceil($count / 1000);
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);
            $objExcel->getActiveSheet()->setTitle('第' . ($i + 1) . '个一千个订单信息');
            $objActSheet = $objExcel->getActiveSheet();
            $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
            
            $objActSheet->setCellValue('A1', '类别名称');
            $objActSheet->setCellValue('B1', '领用人');
            $objActSheet->setCellValue('C1', '出库日期');
            $objActSheet->setCellValue('D1', '出库数量');
            $objActSheet->setCellValue('E1', '剩余库存');
            $objActSheet->setCellValue('F1', '备注');


            $list = $d_materiel_checkout->where($where)->order('id DESC')->limit($i * 1000,1000)->select();
            $materiel_type_msgArr=array();
            if (!empty($list)) {
                $index = 1;
                foreach ($list as $value) {
                    $index++;
                    $type_name='';
                    if($materiel_type_msgArr && isset($materiel_type_msgArr[$value['type_id']])){
                        $type_name=$materiel_type_msgArr[$value['type_id']];
                    }else{
                        $type_msg = $d_materiel_type_msg->where(array('type_id' => $value['type_id']))->field('type_id,type_name')->find();
                        $type_name=$type_msg['type_name'];
                        $materiel_type_msgArr[$value['type_id']]=$type_msg['type_name'];
                    }
                    $objActSheet->setCellValueExplicit('A' . $index, $type_name);
                    $objActSheet->setCellValueExplicit('B' . $index, $value['receive_name']);
                    $objActSheet->setCellValueExplicit('C' . $index, $value['receive_time'] ? date('Y-m-d H:i:s', $value['receive_time']) : '');
                    $objActSheet->setCellValueExplicit('D' . $index, $value['receive_num']);
                    $objActSheet->setCellValueExplicit('E' . $index, $value['surplus_num']);
                    $objActSheet->setCellValueExplicit('F' . $index, $value['remark'] ? $value['remark'] : '');
                }
            }
            sleep(2);
        }

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
    
    // 出库添加
    public function checkout_add() {
        $type_id = I('type_id', '', 'intval');
        $id = I('id', '', 'intval');
        $d_materiel_checkout = M('Materiel_checkout');
        if(IS_POST){
            if (!$type_id || $type_id<=0) {
                $this->error('请先关联物料类别！');
            }
            $receive_id = I('receive_id', '0', 'intval');
            if ($receive_id<0) {
                $this->error('请选择领用人！');
            }
            $receive_num = I('receive_num', 0, 'floatval');
            if (!$receive_num || $receive_num<=0) {
                $this->error('请填写大于0的领取数量！');
            }
            $d_materiel_type_msg = M('Materiel_type_msg');
            $where['type_id'] = $type_id;
            $type_msg = $d_materiel_type_msg->field('type_id,type_name,from,from_id,stock_num, checkout_num, stock_num-checkout_num as surplus_num')->where($where)->find();
            if($type_msg && intval($type_msg['surplus_num']) < $receive_num) {
                $this->error("当前不能领取{$receive_num}份，剩余库存为{$type_msg['surplus_num']}份！");
            } elseif (!$type_msg) {
                $this->error('领取物料不存在或者已经被删除！');
            }

            $receive_phone = I('receive_phone', '');
            $receive_name = I('receive_name', '');
            $receive_time = I('receive_time', '', 'trim');
            $remark = I('remark', '', 'trim');
            if ($receive_time) {
                $receive_time = strtotime($receive_time);
            }
            if (!$receive_name) {
                $d_house_worker = M('House_worker');
                $house_worker = $d_house_worker->where(array('wid' => $receive_id))->field('wid,name,nickname,phone')->find();
                if ($house_worker && $house_worker['name']) {
                    $receive_name = $house_worker['name'];
                } elseif ($house_worker && $house_worker['nickname']) {
                    $receive_name = $house_worker['nickname'];
                }
                if (!$receive_phone && $house_worker && $house_worker['phone']) {
                    $receive_phone = $house_worker['phone'];
                }
            }
            $data = array(
                'type_id' => $type_id,
                'receive_id' => $receive_id,
                'receive_num' => $receive_num,
                'receive_phone' => $receive_phone,
                'receive_name' => $receive_name,
                'receive_time' => $receive_time,
                'remark' => $remark,
                'from' => $type_msg['from'] ?? 0,
                'from_id' => $type_msg['from_id'] ?? 0,
            );
            if ($id) {
                $data['last_time'] = time();
                unset($data['receive_num']);
                $add_id = $d_materiel_checkout->where(array('id' => $id))->data($data)->save();
                if ($add_id) {
                    $msg = array(
                        'message' => '编辑成功！',
                        'status' => 1
                    );
                    $this->ajaxReturn($msg);
                } else {
                    $this->error('编辑失败！');
                }
            } else {
                $data['add_time'] = time();
                $add_id = $d_materiel_checkout->data($data)->add();
                if ($add_id) {
                    // 添加成功  同步添加 对应物料 出库数量
                    $d_materiel_type_msg = M('Materiel_type_msg');
                    $checkoutTotal = $d_materiel_checkout->where(array('type_id' => $type_id))->sum('receive_num');
                    $d_materiel_type_msg->where(array('type_id' => $type_id))->setField('checkout_num', $checkoutTotal);
                    $stock_num = $type_msg['stock_num'];
                    $surplus_num = $stock_num - $checkoutTotal;
                    if ($surplus_num < 0) {
                        $surplus_num = 0;
                    }
                    $checkoutData = ['stock_num' => $stock_num, 'surplus_num' => $surplus_num];
                    $d_materiel_checkout->where(array('id' => $add_id))->save($checkoutData);
                    
                    // 出库成功 同步生成等数量的 物料出库详细
                    $detail = array(
                        'checkout_id' => $add_id,
                        'type_id' => $type_id,
                        'status' => 0,
                        'add_time' => time()
                    );
                    $d_materiel_checkout_list = M('Materiel_checkout_list');
                    $add_all = array();
                    for ($i=0;$i<$receive_num;$i++) {
                        $add_all[]=$detail;
                    }
                    $add_all_id = $d_materiel_checkout_list->addAll($add_all);
                    if (!$add_all_id) {
                        fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/checkout_add_err_log',true);
                        fdump($add_all,'api/log/'.date('Ymd').'/checkout_add_err_log',true);
                        fdump($d_materiel_checkout_list->getLastSql(),'api/log/'.date('Ymd').'/checkout_add_err_log',true);
                        fdump($d_materiel_checkout_list->getDbError(),'api/log/'.date('Ymd').'/checkout_add_err_log',true);
                        fdump('-----行数---'.__LINE__,'api/log/'.date('Ymd').'/checkout_add_err_log',true);
                    }
                    // 给对应的领取者发消息
                    // 获取下 相关工作人员
                    if ($type_id && $type_id>0) {
                        $d_materiel_type_msg = M('Materiel_type_msg');
                        $type_msg = $d_materiel_type_msg->where(array('type_id' => $type_id))->find();
                    }
                    $d_house_worker = M('House_worker');
                    $worker = $d_house_worker->where(array('wid' => $receive_id))->find();
                    $first = '领用物料通知';
                    $remark = '点击查看并确认';
                    $type_name = '';
                    if ($type_msg) {
                        $keyword1 = '领取物料【' . $type_msg['type_name'].'】';
                        $type_name = '【' . $type_msg['type_name'].'】';
                    } else {
                        $keyword1 = '领取物料';
                    }
                    $base_url = D('House_village')->base_url;
                    $href = $this->config['site_url'].$base_url . 'pages/village/commonly_used/pickUpMaterials?receive_id='.$receive_id;
                    $keyword2 = '待领取者确认';
//                    $data = array(
//                        'tempKey' => 'OPENTM400166399',
//                        'dataArr' => array(
//                            'href' => $href,
//                            'wecha_id' => $worker['openid'],
//                            'first' => $first,
//                            'keyword1' => $keyword1,
//                            'keyword2' =>$keyword2,
//                            'keyword3' => date("Y年m月d日 H:i"),
//                            'remark' => $remark
//                        )
//                    );
//                    $token_info = D('Property_group')->send_msg('work', $val['wid'], $data, $token_info);
                    if ($worker['openid']) {
                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        $model->sendTempMsg('OPENTM400166399',
                            array(
                                'href' => $href,
                                'wecha_id' => $worker['openid'],
                                'first' => $first,
                                'keyword1' => $keyword1,
                                'keyword2' => $keyword2,
                                'keyword3' => date("Y年m月d日 H:i"),
                                'remark' => $remark,
                                'new_info' => [//新版本发送需要的信息
                                    'tempKey'=>'61396',//新模板号
                                    'thing1'=>$first.$type_name,//服务项目
                                    'const2'=>$keyword2,//订单类型
                                    'time3'=>date("Y年m月d日 H:i"),//服务时间
                                ],
                            )
                        );
                    }
                    $msg = array(
                        'message' => '添加成功！',
                        'status' => 1
                    );
                    $this->ajaxReturn($msg);
                } else {
                    $this->error('添加失败！');
                }
            }
        } else {
            if ($id && $id>0) {
                $msg = $d_materiel_checkout->where(array('id' => $id))->find();
                if ($msg['receive_time']) {
                    $msg['receive_time'] = date('Y-m-d H:i',$msg['receive_time']);
                }
                $this->assign('msg' , $msg);
            } elseif (!$type_id) {
                $this->error('请选择正确路径跳转！');
            }
            // 获取下 相关工作人员
            $d_house_worker = M('House_worker');
            $where_worker = array(
                'is_del' => 0,
                'status' => 1,
            );
            if ($this->house_session['village_id']) {
                $where_worker['village_id'] = $this->house_session['village_id'];
            } elseif ($this->house_session['property_id']) {
                $where_worker['property_id'] = $this->house_session['property_id'];
            }
            $worker_list = $d_house_worker->where($where_worker)->field('wid,name,nickname,phone')->select();
            foreach ($worker_list as &$val) {
                if (!$val['name'] && $val['nickname']) {
                    $val['name'] = $val['nickname'];
                }
            }
            $this->assign('worker_list' , $worker_list);

        }
        $this->display();
    }

    // 批量出库添加
    public function materiel_checkouts()
    {
        if (IS_POST) {
            $receive_id = I('receive_id', '0', 'intval');
            if ($receive_id<0) {
                $this->error('请选择领用人！');
            }
            $receive_phone = I('receive_phone', '');
            $receive_name = I('receive_name', '');
            $receive_time = I('receive_time', '', 'trim');
            $type = $_POST['type'];
            $typeArray = [];
            foreach ($type as $item) {
                $type_id = isset($item['type_id']) ? intval($item['type_id']) : 0;
                if ($type_id <= 0) {
                    continue;
                }
                $receive_num = isset($item['receive_num']) ? intval($item['receive_num']) : 0;
                if ($receive_num <= 0) {
                    $d_materiel_type_msg = M('Materiel_type_msg');
                    $type_name = $d_materiel_type_msg->where(['type_id' => $type_id])->getField('type_name');
                    $this->error("物料类别【{$type_name}】请填写大于0的领取数量或者删除此项！");
                    continue;
                }
                $remark = isset($item['remark']) ? $item['remark'] : '';
                $typeArray[] = [
                    'type_id' => $type_id, 'receive_num' => $receive_num, 'remark' => $remark,
                ];
            }
            if ($receive_time) {
                $receive_time = strtotime($receive_time);
            }
            if (!$receive_name) {
                $d_house_worker = M('House_worker');
                $house_worker = $d_house_worker->where(array('wid' => $receive_id))->field('wid,name,nickname,phone')->find();
                if ($house_worker && $house_worker['name']) {
                    $receive_name = $house_worker['name'];
                } elseif ($house_worker && $house_worker['nickname']) {
                    $receive_name = $house_worker['nickname'];
                }
                if (!$receive_phone && $house_worker && $house_worker['phone']) {
                    $receive_phone = $house_worker['phone'];
                }
            }
            $d_materiel_checkout = M('Materiel_checkout');
            $d_materiel_type_msg = M('Materiel_type_msg');
            foreach ($typeArray as $typeItem) {
                $type_id = isset($typeItem['type_id']) ? intval($typeItem['type_id']) : 0;
                $receive_num = isset($typeItem['receive_num']) ? intval($typeItem['receive_num']) : 0;
                $remark = isset($typeItem['remark']) ? $typeItem['remark'] : '';

                $where = [];
                $where['type_id'] = $type_id;
                $type_msg = $d_materiel_type_msg->field('type_id,type_name,from,from_id,stock_num, checkout_num, stock_num-checkout_num as surplus_num')->where($where)->find();
                if($type_msg && intval($type_msg['surplus_num']) < $receive_num) {
                    $this->error("物料类别【{$type_msg['type_name']}】当前不能领取{$receive_num}份，剩余库存为{$type_msg['surplus_num']}份！");
                } elseif (!$type_msg) {
                    $this->error('领取物料不存在或者已经被删除！');
                }
                $data = array(
                    'type_id' => $type_id,
                    'receive_id' => $receive_id,
                    'receive_num' => $receive_num,
                    'receive_phone' => $receive_phone,
                    'receive_name' => $receive_name,
                    'receive_time' => $receive_time,
                    'remark' => $remark,
                    'from' => $type_msg['from'] ?? 0,
                    'from_id' => $type_msg['from_id'] ?? 0,
                );
                $data['add_time'] = time();
                $add_id = $d_materiel_checkout->data($data)->add();
                if ($add_id) {
                    // 添加成功  同步添加 对应物料 出库数量
                    $checkoutTotal = $d_materiel_checkout->where(array('type_id' => $type_id))->sum('receive_num');
                    $d_materiel_type_msg->where(array('type_id' => $type_id))->setField('checkout_num', $checkoutTotal);
                    $stock_num = $type_msg['stock_num'];
                    $surplus_num = $stock_num - $checkoutTotal;
                    if ($surplus_num < 0) {
                        $surplus_num = 0;
                    }
                    $checkoutData = ['stock_num' => $stock_num, 'surplus_num' => $surplus_num];
                    $d_materiel_checkout->where(array('id' => $add_id))->save($checkoutData);

                    // 出库成功 同步生成等数量的 物料出库详细
                    $detail = array(
                        'checkout_id' => $add_id,
                        'type_id' => $type_id,
                        'status' => 0,
                        'add_time' => time()
                    );
                    $d_materiel_checkout_list = M('Materiel_checkout_list');
                    $add_all = array();
                    for ($i=0;$i<$receive_num;$i++) {
                        $add_all[]=$detail;
                    }
                    $add_all_id = $d_materiel_checkout_list->addAll($add_all);
                    if (!$add_all_id) {
                        fdump_api(['$add_all' => $add_all, '$data' => $data], 'checkout_add_err_log', 1);
                    }
                    // 给对应的领取者发消息
                    // 获取下 相关工作人员
                    $d_house_worker = M('House_worker');
                    $worker = $d_house_worker->where(array('wid' => $receive_id))->find();
                    $first = '领用物料通知';
                    $type_name = '';
                    if ($type_msg) {
                        $keyword1 = '领取物料【' . $type_msg['type_name'].'】';
                        $type_name = '【' . $type_msg['type_name'].'】';
                    } else {
                        $keyword1 = '领取物料';
                    }
                    $base_url = D('House_village')->base_url;
                    $href = $this->config['site_url'].$base_url . 'pages/village/commonly_used/pickUpMaterials?receive_id='.$receive_id;
                    $keyword2 = '待领取者确认';
                    if ($worker['openid']) {
                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        $model->sendTempMsg('OPENTM400166399',
                            array(
                                'href' => $href,
                                'wecha_id' => $worker['openid'],
                                'first' => $first,
                                'keyword1' => $keyword1,
                                'keyword2' => $keyword2,
                                'keyword3' => date("Y年m月d日 H:i"),
                                'remark' => '点击查看并确认',
                                'new_info' => [//新版本发送需要的信息
                                    'tempKey'=>'61396',//新模板号
                                    'thing1'=>$first.$type_name,//服务项目
                                    'const2'=>$keyword2,//订单类型
                                    'time3'=>date("Y年m月d日 H:i"),//服务时间
                                ],
                            )
                        );
                    }
                } else {
                    $this->error('添加失败！');
                }
            }
            $msg = array('message' => '添加成功！','status' => 1);
            $this->ajaxReturn($msg);
        } else {
            // 获取下 相关工作人员
            $d_house_worker = M('House_worker');
            $where_worker = array('is_del' => 0, 'status' => 1);
            if ($this->house_session['village_id']) {
                $where_worker['village_id'] = $this->house_session['village_id'];
                $this->assign('village_id', $this->house_session['village_id']);
            } elseif ($this->house_session['property_id']) {
                $where_worker['property_id'] = $this->house_session['property_id'];
                $this->assign('property_id', $this->house_session['property_id']);
            }
            $worker_list = $d_house_worker->where($where_worker)->field('wid,name,nickname,phone')->select();
            foreach ($worker_list as &$val) {
                if (!$val['name'] && $val['nickname']) {
                    $val['name'] = $val['nickname'];
                }
            }
            $this->assign('worker_list', $worker_list);
            $d_materiel_type_msg = M('Materiel_type_msg');
            $where = array('status' => 0);
            $where['from_id'] = $this->house_session['village_id'];
            $typeMsgList = $d_materiel_type_msg->where($where)->order('`type_name` ASC, `type_id` DESC')->select();
            $this->assign('typeMsgList', $typeMsgList);
            $this->assign('typeMsgListJson',json_encode($typeMsgList,JSON_UNESCAPED_UNICODE));
        }
        $this->display();
    }

    // 编辑出库详情记录
    public function checkout_list_edit() {
        $type_id = I('type_id', '', 'intval');
        $id = I('id', '', 'intval');
        $d_materiel_checkout_list = M('Materiel_checkout_list');
        if(IS_POST){
            if (!$id || $id<=0) {
                $this->error('请上传修改对象！');
            }
            $status = I('status', '0', 'intval');
            if ($status<0) {
                $this->error('请选择状态！');
            }
            $receive_time=I('receive_time', '', 'trim');
            $scrap_reason = I('scrap_reason', '', 'trim');
            if ($status==1 && !$scrap_reason) {
                $this->error('请填写报废原因！');
            }
            if ($status!=1) {
                $scrap_reason = '';
            }
            $receive_name = I('receive_name', '', 'trim');
            $receive_phone = I('receive_phone', '', 'trim');
            $data = array(
                'status' => $status,
                'scrap_reason' => $scrap_reason
            );
            if ($receive_time) {
                $receive_time = strtotime($receive_time);
                $saveData=array();
                $saveData['receive_time']=$receive_time;
                if($receive_name){
                    $saveData['receive_name']=$receive_name;
                }
                if($receive_phone){
                    $saveData['receive_phone']=$receive_phone;
                }
                $msgTmp = $d_materiel_checkout_list->field('*')->where(array('id' => $id))->find();
                if($msgTmp && isset($msgTmp['checkout_id'])){
                    M('Materiel_checkout')->where(array('id' => $msgTmp['checkout_id']))->data($saveData)->save();
                }
            }
            $data['last_time'] = time();
            $add_id = $d_materiel_checkout_list->where(array('id' => $id))->data($data)->save();
            if ($add_id) {
                $msg = array(
                    'message' => '编辑成功！',
                    'status' => 1
                );
                $this->ajaxReturn($msg);
            } else {
                $this->error('编辑失败！');
            }
        } else {
            if ($id && $id>0) {
                $where = array(
                    'cl.id' => $id
                );
                $field = 'cl.id, cl.status, cl.scrap_reason, mc.receive_name,mc.receive_time, mc.receive_phone';
                $msg = $d_materiel_checkout_list->field($field)->join('as cl LEFT JOIN '.C('DB_PREFIX').'materiel_checkout as mc ON cl.checkout_id=mc.id')->where($where)->find();
                if ($msg['receive_time']) {
                    $msg['receive_time'] = date('Y-m-d H:i',$msg['receive_time']);
                }
                $this->assign('msg' , $msg);
            } elseif (!$id) {
                $this->error('请选择正确路径跳转！');
            }
            if ($type_id && $type_id>0) {
                $d_materiel_type_msg = M('Materiel_type_msg');
                $type_msg = $d_materiel_type_msg->where(array('type_id' => $type_id))->find();
                $this->assign('type_msg' , $type_msg);
            }

        }
        $this->display();
    }
    
    // 修理记录
    public function checkout_repair() {

        $type_id = I('type_id', '', 'intval');
        $checkout_list_id = I('id', '', 'intval');
        $where = array('status' => 0);
        if ($checkout_list_id) {
            $where['checkout_list_id'] = $checkout_list_id;
        }

        $where['from_id'] = $this->house_session['village_id'];
        $d_materiel_checkout_repair = M('Materiel_checkout_repair');
        $count = $d_materiel_checkout_repair->where($where)->count();
        $pageSize = 15;
        import('@.ORG.merchant_page');
        $p = new Page($count, $pageSize, 'page');
        $list = $d_materiel_checkout_repair->field(true)->where($where)->order('`id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();

        $pagebar = $p->show();
        $totalPage = ceil($count / $pageSize);

        $this->assign('list' , $list);
        $this->assign('pagebar' , $pagebar);
        $this->assign('totalPage' , $totalPage);
        if ($type_id && $type_id>0) {
            $d_materiel_type_msg = M('Materiel_type_msg');
            $type_msg = $d_materiel_type_msg->where(array('type_id' => $type_id))->find();
            $this->assign('type_msg' , $type_msg);
        }

        $this->display();
    }

    // 添加维修记录
    public function repair_add() {
        $type_id = I('type_id', '', 'intval');
        $id = I('id', '', 'intval');
        $checkout_list_id = I('checkout_list_id', '', 'intval');
        $d_materiel_checkout_repair = M('Materiel_checkout_repair');
        if(IS_POST){
            if (!$checkout_list_id || $checkout_list_id<=0) {
                $this->error('请先关联出库详情！');
            }
            $repair_money = I('repair_money', '', 'floatval');
            $repair_time = I('repair_time', '', 'trim');
            $remark = I('remark', '', 'trim');
            if ($repair_time) {
                $repair_time = strtotime($repair_time);
            }
            $appendix_file = $_POST['pic'];
            if ($appendix_file) {
                $appendix_file = serialize($appendix_file);
            }



            $data = array(
                'checkout_list_id' => $checkout_list_id,
                'repair_money' => $repair_money,
                'repair_time' => $repair_time,
                'appendix_file' => $appendix_file?$appendix_file:'',
                'remark' => $remark,
            );
            if ($id) {
                $data['last_time'] = time();
                $add_id = $d_materiel_checkout_repair->where(array('id' => $id))->data($data)->save();
                if ($add_id) {
                    // 编辑后每次重新更改总金额
                    $sum_money = $d_materiel_checkout_repair->where(array('checkout_list_id' => $checkout_list_id))->sum('repair_money');
                    if (floatval($sum_money)>0) {
                        $d_materiel_checkout_list = M('Materiel_checkout_list');
                        $d_materiel_checkout_list->where(array('id' => $checkout_list_id))->setField('repair_money', $sum_money);
                    }
                    $msg = array(
                        'message' => '编辑成功！',
                        'status' => 1
                    );
                    $this->ajaxReturn($msg);
                } else {
                    $this->error('编辑失败！');
                }
            } else {
                $data['add_time'] = time();
                $add_id = $d_materiel_checkout_repair->data($data)->add();
                if ($add_id) {
                    // 每添加一条维修记录 自动累加
                    $d_materiel_checkout_list = M('Materiel_checkout_list');
                    $d_materiel_checkout_list->where(array('id' => $checkout_list_id))->setInc('repair_num', 1);
                    // 同时重新计算下金额
                    $sum_money = $d_materiel_checkout_repair->where(array('checkout_list_id' => $checkout_list_id))->sum('repair_money');
                    if (floatval($sum_money)>0) {
                        $d_materiel_checkout_list = M('Materiel_checkout_list');
                        $d_materiel_checkout_list->where(array('id' => $checkout_list_id))->setField('repair_money', $sum_money);
                    }
                    $msg = array(
                        'message' => '添加成功！',
                        'status' => 1
                    );
                    $this->ajaxReturn($msg);
                } else {
                    $this->error('添加失败！');
                }
            }
        } else {
            if ($id && $id>0) {
                $msg = $d_materiel_checkout_repair->where(array('id' => $id))->find();
                if ($msg['repair_time']) {
                    $msg['repair_time'] = date('Y-m-d H:i',$msg['repair_time']);
                }
                if ($msg['appendix_file']) {
                    $msg['appendix_file'] = unserialize($msg['appendix_file']);
                }
                $this->assign('msg' , $msg);
            } elseif (!$type_id) {
                $this->error('请选择正确路径跳转！');
            }
        }
        $this->display();
    }
    
    public function ajax_upload_pic(){
        if ($_FILES['imgFile']['error'] != 4) {
            $img_mer_id = sprintf("%09d", $this->house_session['village_id']);
            $rand_num = mt_rand(10, 99) . '/' . substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);

            $upload_dir = './upload/materiel/' . $rand_num . '/';

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            import('ORG.Net.UploadFile');
            $upload = new UploadFile();
            $upload->maxSize = $this->materiel_size * 1024 * 1024;
            $upload->allowExts = array('jpg', 'jpeg', 'png', 'gif');
            $upload->allowTypes = array('image/png', 'image/jpg', 'image/jpeg', 'image/gif');
            $upload->savePath = $upload_dir;
            $upload->saveRule = 'uniqid';
            if ($upload->upload()) {
                $uploadList = $upload->getUploadFileInfo();
                $title = $rand_num . ',' . $uploadList[0]['savename'];
                $slide_image_class = new slide_image();
                $url = $slide_image_class->get_image_by_path($title , 7);
                $title = '/upload/materiel/' . $rand_num . '/' . $uploadList[0]['savename'];
                exit(json_encode(array('error' => 0, 'url' => $url, 'title' => $title)));
            } else {
                exit(json_encode(array('error' => 1, 'message' => $upload->getErrorMsg())));
            }
        } else {
            exit(json_encode(array('error' => 1, 'message' => '没有选择图片')));
        }
    }

    public function img_info() {
        $id = I('id', '', 'intval');
        $d_materiel_checkout_repair = M('Materiel_checkout_repair');
        $msg = $d_materiel_checkout_repair->where(array('id' => $id))->find();
        if ($msg['repair_time']) {
            $msg['repair_time'] = date('Y-m-d H:i',$msg['repair_time']);
        }
        if ($msg['appendix_file']) {
            $msg['appendix_file'] = unserialize($msg['appendix_file']);
        }
        fdump($msg,'$msg');
        $this->assign('msg' , $msg);
        $this->display();
    }

}