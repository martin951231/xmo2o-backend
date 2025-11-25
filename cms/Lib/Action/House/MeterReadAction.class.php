<?php
/**
 * Created by PhpStorm.
 * User: win 10
 * Date: 2019/12/30
 * Time: 13:11
 */

class MeterReadAction extends BaseAction{


    protected $village_id;
    protected $village;

    public function _initialize()
    {
        parent::_initialize();

        $this->village_id = $this->house_session['village_id'];
        $this->village = M('House_village')->where(array('village_id' => $this->village_id))->find();
        if (empty($this->village)) {
            $this->error('该' . C('config.house_name') . '不存在！');
        }
    }

    public function cate_index()
    {

        $water = M('Meter_read_cate')->where(array('village_id'=>$this->village_id,'cate_name'=>'水费'))->find();
        $electric = M('Meter_read_cate')->where(array('village_id'=>$this->village_id,'cate_name'=>'电费'))->find();
        $gas = M('Meter_read_cate')->where(array('village_id'=>$this->village_id,'cate_name'=>'燃气费'))->find();
        if(!$water){
            $data['cate_name'] = '水费';
            $data['village_id'] = $this->village_id;
            $data['cycle_time'] = 0;
            M('Meter_read_cate')->data($data)->add();
        }
        if(!$electric){
            $data['cate_name'] = '电费';
            $data['village_id'] = $this->village_id;
            $data['cycle_time'] = 0;
            M('Meter_read_cate')->data($data)->add();
        }

        if(!$gas){
            $data['cate_name'] = '燃气费';
            $data['village_id'] = $this->village_id;
            $data['cycle_time'] = 0;
            M('Meter_read_cate')->data($data)->add();
        }
        $dataList = M('Meter_read_cate')->where(array('village_id'=>$this->village_id))->select();
        $this->assign('dataList',$dataList);
        $this->display();
    }

    //项目管理
    public function project_index()
    {

        $count_user = D('Meter_read_project')->where(array('village_id'=>$this->village_id,'cate_id'=>$_GET['cate_id']))->count();

        import('@.ORG.merchant_page');
        $p = new Page($count_user, 20, 'page');
        $dataList = M('Meter_read_project')->where(array('village_id'=>$this->village_id,'cate_id'=>$_GET['cate_id']))->select();
        foreach($dataList as $k=>&$v){
            $house_worker =  D('House_worker')->where(array('phone'=>$v['user_phone'],'village_id'=>$this->village_id))->find();
            $user = D('User')->where(array('uid'=>$v['uid']))->find();
            if($house_worker){

                $v['name']=$house_worker['name'];
            }

        }
        $this->assign('dataList',$dataList);
        $this->assign('pagebar',$p->show());
        $this->display();
    }

    public function project_add()
    {
        if(IS_POST){
            $data['village_id'] = $this->village_id;
            $data['cate_id'] = $_POST['cate_id'];
            $data['project_name'] = $_POST['project_name'];
            $data['unit_price'] = $_POST['unit_price'];
            $data['rate'] = $_POST['rate'];
            $data['pool'] = $_POST['pool'];//公摊
            $data['add_time'] = time();
            $inset_id = M('Meter_read_project')->data($data)->add();
            if($inset_id){
                $this->success('添加成功！',U('project_index',array('cate_id'=>$_POST['cate_id'])));
            }else{
                $this->error('添加失败');
            }
        }else{
            $this->display();
        }
    }

    public function project_edit()
    {
        if(IS_POST){
            $where['id'] = $_POST['id'];
            $data['project_name'] = $_POST['project_name'];
            $data['unit_price'] = $_POST['unit_price'];
            $data['rate'] = $_POST['rate'];
            $data['pool'] = $_POST['pool'];//公摊
            $res = M('Meter_read_project')->where($where)->save($data);
            if($res){
                $this->success('修改成功！',U('project_index',array('cate_id'=>$_GET['cate_id'])));
            }else{
                $this->error('添加失败');
            }
        }else{
            $meter_project = M('Meter_read_project')->where(array('id'=>$_GET['id']))->find();
            $this->assign('meter_project',$meter_project);
            $this->display();
        }
    }

    public function project_del()
    {
        $id =  M('Meter_read_project')->where(array('id'=>$_GET['id']))->delete();
        if($id){

            $this->success('删除成功！');
        }else{
            $this->error('删除失败！');
        }
    }






    //负责人
    public function person_index()
    {

        $count_user = D('Meter_read_person')->where(array('village_id'=>$this->village_id,'cate_id'=>$_GET['cate_id']))->count();

        import('@.ORG.merchant_page');
        $p = new Page($count_user, 20, 'page');
        $dataList = M('Meter_read_person')->where(array('village_id'=>$this->village_id,'cate_id'=>$_GET['cate_id']))->select();
        foreach($dataList as $k=>&$v){
            $house_worker =  D('House_worker')->where(array('wid'=>$v['uid'],'village_id'=>$this->village_id))->find();
//            $user = D('User')->where(array('uid'=>$v['uid']))->find();
            if($house_worker){

                $v['name']=$house_worker['name'];
            }

        }
        $this->assign('dataList',$dataList);
        $this->assign('pagebar',$p->show());
        $this->display();
    }



    public function person_add()
    {
       if(IS_POST){

           $data['cate_id'] = $_POST['cate_id'];
           $data['uid'] = $_POST['uid'];
           $data['user_phone'] = $_POST['user_phone'];
           $data['remind_time'] = $_POST['remind_time'];
           $data['status'] = $_POST['status'];
           $data['add_time'] = time();
           $data['village_id'] = $this->village_id;

           $inset_id = M('Meter_read_person')->data($data)->add();
           if($inset_id){
               $this->success('添加成功！',U('person_index',array('cate_id'=>$_POST['cate_id'])));
           }else{
               $this->error('添加失败');
           }
       }else{
           $where['phone'] = array('neq','');
           //$where['openid'] = array('neq','');
           $house_worker = D('House_worker')->where(array('village_id'=>$this->village_id,'is_del'=>0,'status'=>1))->where($where)->select();
//           var_dump(D('House_worker')->_sql());
//           foreach($house_worker as $k=>&$v){
//
//               $user = D('User')->where(array('phone'=>$v['phone']))->find();
//               if($user){
//                   $v['uid']=$user['uid'];
//               }else{
//
//               }
//           }
           $this->assign('house_worker',$house_worker);
           $this->display();
       }

    }

    public function person_edit()
    {
        if(IS_POST){
            $data['uid'] = $_POST['uid'];
            $data['user_phone'] = $_POST['user_phone'];
            $data['remind_time'] = $_POST['remind_time'];
            $data['status'] = $_POST['status'];


            $inset_id = M('Meter_read_person')->where(array('id'=>$_POST['id']))->data($data)->save();
            if($inset_id){
                $this->success('修改成功！',U('person_index',array('cate_id'=>$_POST['cate_id'])));
            }else{
                $this->error('修改失败');
            }
        }else{
            $meter_person = M('Meter_read_person')->where(array('id'=>$_GET['id']))->find();
            $this->assign('meter_person',$meter_person);
            $house_worker = D('House_worker')->where(array('village_id'=>$this->village_id,'is_del'=>0,'status'=>1))->select();
//            foreach($house_worker as $k=>&$v){
////                $user = D('User')->where(array('phone'=>$v['phone']))->find();
////                if($user){
////                    $v['uid']=$user['uid'];
////                }
//            }
            $this->assign('house_worker',$house_worker);
            $this->display();
        }
    }

    public function person_del()
    {
        $id =  M('Meter_read_person')->where(array('id'=>$_GET['id']))->delete();
        if($id){

            $this->success('删除成功！');
        }else{
            $this->error('删除失败！');
        }
    }

    //周期
    public function payment_cycle(){

        if(IS_POST){
            $data['cycle_time'] = $_POST['cycle_time'];
            $inset_id = M('Meter_read_cate')->where(array('id'=>$_POST['id']))->data($data)->save();
            if($inset_id){
                $this->success('修改成功！',U('Meter_read_cate'));
            }else{
                $this->error('修改失败');
            }
        }else{

            $meter_read_cate = M('Meter_read_cate')->where(array('village_id'=>$this->village_id,'id'=>$_GET['id']))->find();
            $this->assign('meter_read_cate',$meter_read_cate);
            $this->display();
        }
    }


    public function ajax_user_info(){
        $uid = $_POST['uid'];
        if(!$uid){
            echo json_encode(array('code'=>1,'msg'=>'用户不存在!'));die;
        }
        $user = D('House_worker')->where(array('wid'=>$uid,'is_del'=>0,'status'=>1))->find();
        if($user){
            echo json_encode(array('code'=>2,'msg'=>$user['phone']));die;
        }else{
            echo json_encode(array('code'=>1,'msg'=>'用户不存在!'));die;
        }
    }

    public function record_index()
    {
        $where = [];
        $find_type = $_GET['find_type'];
        $find_value = $_GET['find_value'];
        $start_time = $_GET['property_endtime_start'];
        $end_time = $_GET['property_endtime_end'];
        $this->assign('find_type',$_GET['find_type']);
        $this->assign('property_endtime_start',$_GET['property_endtime_start']);
        $this->assign('property_endtime_end',$_GET['property_endtime_end']);
        if ($find_value) {
            if ($find_type == 1) {
                $where['room'] = array('like', '%' . $find_value . '%');
            } else if ($find_type == 2) {
                $where['charge_name'] = array('like', '%' . str_replace(' ','',$find_value) . '%');
            } else if ($find_type == 3) {
                $where['floor_name'] = array('like', '%' . $find_value . '%');
            } else if ($find_type == 4) {
                $where['floor_layer'] = array('like', '%' . $find_value . '%');
            }
        }
        $database_house_village_floor = D('House_village_floor');
        $database_house_village_user_vacancy = D('House_village_user_vacancy');
        //新增优化搜索 start
        $single_id = $_GET['single_id'];
        $floor_id = $_GET['floor_id'];
        $layer_id = $_GET['layer_id'];
        $vacancy_id = $_GET['vacancy_id'];
        if($single_id){
            $floor_ids = $database_house_village_floor->where(['village_id'=>$this->village_id,'single_id'=>$single_id])->getField('floor_id',true);
            $where['floor_id'] = ['in',$floor_ids];
        }
        if($floor_id){
            unset($where['floor_id']);
            $where['floor_id'] = $floor_id;
        }
        if($layer_id){
            $map['is_del'] = 0;
            $map['layer_id'] = $layer_id;
            $map['floor_id'] = $where['floor_id'];
            $map['village_id'] = $this->village_id;
            $pigcms_id = $database_house_village_user_vacancy->where($map)->getField('pigcms_id',true);
            $where['room_id'] = ['in',$pigcms_id];
        }
        if($vacancy_id){
            unset($where['room_id']);
            $where['room_id'] = $vacancy_id;
        }
        if($_GET['project_name']){
            $map = [];
            $map['project_name'] = array('like', '%' . trim(str_replace(' ','',$_GET['project_name'])) . '%');
            $map['village_id'] = $this->village_id;
            $project_ids = M('Meter_read_project')->where($map)->getField('id',true);
            $where['project_id'] = ['in',$project_ids];
        }
        //新增优化搜索 end
        if($start_time && !$end_time){
            $where['add_time'] = array('gt',strtotime($start_time));
        }
        if($end_time && !$start_time){
            $where['add_time'] = array('lt',strtotime($end_time));
        }
        if($start_time && $end_time){
            $where['add_time'] = array('between',array(strtotime($start_time),strtotime($end_time)));
        }
        $where['village_id'] = $this->village_id;


        $count_user = M('Meter_read_record')->where($where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 20, 'page');
        $record_list = M('Meter_read_record')->where($where)->order('add_time desc')->limit($p->firstRow . ',' . $p->listRows)->select();
        foreach($record_list as $k=>&$v){
            $list['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
            $list['start_num'] = $v['start_num'];//起度
            $list['end_num'] = $v['end_num'];//止度
            $users = D('User')->where(array('uid'=>$v['uid'],'status'=>1))->find();
            $user = D('User')->where(array('uid'=>$v['person_id'],'status'=>1))->find();
            $house_worker = D('House_worker')->where(array('phone'=>$user['phone'],'is_del'=>0,'status'=>1,'village_id'=>$v['village_id']))->find();
            $list['user_name'] = $users['nickname'];
            $list['worker_name'] = $house_worker['name'];
            $find_village_room = $database_house_village_user_vacancy->get_find_room_info($v['room_id'] , "uid,name,phone,floor_id,village_id,layer,room,housesize,layer_id");
            $find_village_floor = $database_house_village_floor->get_unit_find($find_village_room['floor_id'] , 'village_id,floor_name,floor_layer,single_id');
            $find_village_single = M('House_village_single')->where(['id'=>$find_village_floor['single_id']])->find();
            $layer = M("House_village_layer")->where(['id'=>$find_village_room['layer_id']])->field('layer_name')->find();
            $list['room_name'] = $find_village_room['room'];
            $list['layer_name'] = $layer['layer_name'];
            $list['user_name'] = $find_village_room['name'];
            $list['user_phone'] = $find_village_room['phone'];
            $list['floor_name'] = $find_village_floor['floor_name'];
            $list['floor_layer'] = $find_village_single['single_name'];
            $list['layer'] = $find_village_room['layer'];
            $list['room'] = $find_village_room['room'];
            $list['user_address'] = $find_village_floor['floor_layer'].$find_village_floor['floor_name'].$find_village_room['layer'].'#'.$find_village_room['room'];

            $project = M('Meter_read_project')->where(array('id'=>$v['project_id'],'village_id'=>$v['village_id'],'cate_id'=>$v['cate_id']))->find();
            $cate = M('meter_read_cate')->where(array('id'=>$v['cate_id']))->find();
            $list['cate_name'] = $cate['cate_name'];
            $list['project_name'] = $project['project_name'];
            $list['unit_price'] = $project['unit_price'];//单价
            $list['rate'] = $project['rate'];//倍率
            $list['pool'] = $project['pool'];//公摊
            $dataList[] = $list;
        }
        //楼栋
        $single_list = M('House_village_single')->where(array('village_id'=>$this->village_id,'status'=>1))->order('sort desc, id asc')->select();
        if($single_list){
            $this->assign('single_list',$single_list);
        }else{
            $this->assign('single_list',[]);
        }
        if($_GET['vacancy_id']){
            $this->assign('vacancy_id',$_GET['vacancy_id']);
        }else{
            $this->assign('vacancy_id','');
        }
        $this->assign('dataList',$dataList);
        $this->assign('pagebar',$p->show());


        $this->display();
    }
    //移动抄表导出 优化新增 start
    public function move_export()
    {
        $where = [];
        $start_time = $_GET['property_endtime_start'];
        $end_time = $_GET['property_endtime_end'];
        $database_house_village_floor = D('House_village_floor');
        $database_house_village_user_vacancy = D('House_village_user_vacancy');

        $single_id = $_GET['single_id'];
        $floor_id = $_GET['floor_id'];
        $layer_id = $_GET['layer_id'];
        $vacancy_id = $_GET['vacancy_id'];
        if($single_id){
            $floor_ids = $database_house_village_floor->where(['village_id'=>$this->village_id,'single_id'=>$single_id])->getField('floor_id',true);
            $where['r.floor_id'] = ['in',$floor_ids];
        }
        if($floor_id){
            unset($where['floor_id']);
            $where['r.floor_id'] = $floor_id;
        }
        if($layer_id){
            $map['is_del'] = 0;
            $map['layer_id'] = $layer_id;
            $map['floor_id'] = $where['floor_id'];
            $map['village_id'] = $this->village_id;
            $pigcms_id = $database_house_village_user_vacancy->where($map)->getField('pigcms_id',true);
            $where['r.room_id'] = ['in',$pigcms_id];
        }
        if($vacancy_id){
            unset($where['room_id']);
            $where['r.room_id'] = $vacancy_id;
        }
        if($_GET['project_name']){
            $map = [];
            $map['project_name'] = array('like', '%' . trim(str_replace(' ','',$_GET['project_name'])) . '%');
            $map['village_id'] = $this->village_id;
            $project_ids = M('Meter_read_project')->where($map)->getField('id',true);
            $where['r.project_id'] = ['in',$project_ids];
        }

        if($start_time && !$end_time){
            $where['r.add_time'] = array('gt',strtotime($start_time));
        }
        if($end_time && !$start_time){
            $where['r.add_time'] = array('lt',strtotime($end_time));
        }
        if($start_time && $end_time){
            $where['r.add_time'] = array('between',array(strtotime($start_time),strtotime($end_time)));
        }
        $where['r.village_id'] = $this->village_id;
        $left_join = ' left join pigcms_house_village_user_vacancy as v on r.room_id=v.pigcms_id left join pigcms_house_village_floor as f on f.floor_id = r.floor_id left join pigcms_house_village_single as s on f.single_id=s.id left join pigcms_house_village_layer as l on l.id=v.layer_id left join pigcms_meter_read_project as p on r.project_id=p.id';
        $field = 'r.*,v.room,v.usernum,v.property_number,f.floor_name,s.single_name,l.layer_name,p.project_name';
        $record_list = M('Meter_read_record')->alias('r')->join($left_join)->field($field)->where($where)->order('r.add_time desc')->select();
        if(count($record_list) <= 0 ){
            $this->error('无数据导出！');
        }

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';

        $title = '抄表单列表';

        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $length = ceil(count($record_list)/1000);

        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle('移动抄表列表');
            $objActSheet = $objExcel->getActiveSheet();

            $objActSheet->setCellValue('A1', '月份');
            $objActSheet->setCellValue('B1', '楼号');
            $objActSheet->setCellValue('C1', '单元名称');
            $objActSheet->setCellValue('D1', '楼层');
            $objActSheet->setCellValue('E1', '房间号');
            $objActSheet->setCellValue('F1', '收费项目');
            $objActSheet->setCellValue('G1', '收费单价');
            $objActSheet->setCellValue('H1', '起度');
            $objActSheet->setCellValue('I1', '止度');
            $objActSheet->setCellValue('J1', '倍率');
            $objActSheet->setCellValue('K1', '公摊');
            $objActSheet->setCellValue('L1', '添加时间');
            $objActSheet->setCellValue('M1', '备注');
            if (!empty($record_list)) {
                $index = 2;

                $cell_list = range('A','M');
                foreach ($cell_list as $cell) {
                    $objActSheet->getColumnDimension($cell)->setWidth(20);
                }

                foreach ($record_list as $value) {
                    if($value['add_time'] && $value['add_time'] != 0){
                        $add_time = date('Y-m-d H:i:s',$value['add_time']);
                    }else{
                        $add_time = '';
                    }
                    $objActSheet->setCellValueExplicit('A' . $index, date('Y-m',$value['add_time']));
                    $objActSheet->setCellValueExplicit('B' . $index, $value['single_name']);
                    $objActSheet->setCellValueExplicit('C' . $index, $value['floor_name']);
                    $objActSheet->setCellValueExplicit('D' . $index, $value['layer_name']);
                    $objActSheet->setCellValueExplicit('E' . $index, $value['room']);
                    $objActSheet->setCellValueExplicit('F' . $index, $value['project_name']);
                    $objActSheet->setCellValueExplicit('G' . $index, $value['unit_price']);
                    $objActSheet->setCellValueExplicit('H' . $index, $value['start_num']);
                    $objActSheet->setCellValueExplicit('I' . $index, $value['end_num']);
                    $objActSheet->setCellValueExplicit('J' . $index, $value['rate']);
                    $objActSheet->setCellValueExplicit('K' . $index, $value['pool_ammeter']);
                    $objActSheet->setCellValueExplicit('L' . $index, $add_time);
                    $objActSheet->setCellValueExplicit('M' . $index, $value['note']);
                    $index++;
                }
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
    //移动抄表导出 优化新增 end
    public function room_index()
    {
        $project_id = $_GET['project_id'];
        $where['id'] = $project_id;
        $where['cate_id'] = $_GET['cate_id'];
        $project = M('Meter_read_project')->where($where)->find();
        if(empty($project['room_id'])){
            $this->assign('result',[]);
        }else{
            $project_room = explode(',',$project['room_id']);
            $database_house_village_user_vacancy = D('House_village_user_vacancy');
            $where['village_id'] = $this->village_id;
            $where['pigcms_id'] = array('in',$project_room);
            $where['is_del']  =0;
            $result = $database_house_village_user_vacancy->house_village_user_vacancy_page_list($where);
            if(!$result['result']){
                $this->error('数据处理有误！');
            } else {
                $list = $result['result'];
                $where_read_cate = [
                    'cate_id' => $project['cate_id']
                ];
                $read_cate = M('Meter_read_cate')->where($where_read_cate)->select();
                foreach ($list['list'] as &$val) {
                    $record = M('Meter_read_record')->order('add_time DESC')->where(array('cate_id'=>$project['cate_id'],'room_id'=>$val['pigcms_id'],'village_id'=>$this->village_id))->find();

                    if($record){
                        $add_time_d = date('d',$record['add_time']);
                        if(intval($add_time_d) >= intval($read_cate['cycle_time'])){
                            $val['read_status'] = 2;
                            $val['read_status_desc'] = '已抄';
                            $val['read_status_color'] = '#37dc9a';
                        }else{
                            $val['read_status'] = 1;
                            $val['read_status_desc'] = '未抄完';
                            $val['read_status_color'] = 'rgb(125, 168, 247)';
                        }
                    }else{
                        $val['read_status'] = 1;
                        $val['read_status_desc'] = '未抄';
                        $val['read_status_color'] = '#888888';
                    }
                }
            }
            $this->assign('result',$list);
        }
        $this->assign('selecteds',$project['room_id']);
        $this->display();

    }

    public function room_add() {

        $selecteds = $_GET['selecteds'];
        $selectedArr = explode(',', $selecteds);

        if (empty($selectedArr)) {
            $selectedArr = array();
        }
        $this->assign('selectedArr', $selectedArr);
        $this->assign('selecteds', $selecteds);

        $project_id = $_GET['project_id'];
//        $where['id'] = $project_id;
        $where['cate_id'] = $_GET['cate_id'];
        $project = M('Meter_read_project')->where($where)->find();
        if($project['room_id']){
            $project_room = explode(',',$project['room_id']);
            $where['pigcms_id'] = array('not in',$project_room);
        }
        $database_house_village_user_vacancy = D('House_village_user_vacancy');
        $where['village_id'] = $this->village_id;

        $where['is_del']  =0;
        // $where['uid'] = array('neq',0);
        $result = $database_house_village_user_vacancy->house_village_user_vacancy_page_list($where);
        if(!$result){
            $this->error('数据处理有误！');
        }

        $this->assign('list',$result['result']['list']);
        $this->assign('result',$result['result']);
        $this->display();
    }


    public function room_del(){
        $room_id = $_GET['room_id'];
        $where['id'] = $_GET['project_id'];
        $where['cate_id'] = $_GET['cate_id'];
        $project = M('Meter_read_project')->where($where)->find();
        if($project['room_id']) {
            $room_ids = explode(',', $project['room_id']);
            foreach($room_ids as $k=>$v){
                if($room_id == $v){
                    unset($room_ids[$k]);
                }
            }
            $room_id = implode(',', $room_ids);
            $data['room_id'] = $room_id;
            $project = M('Meter_read_project')->where($where)->data($data)->save();
            if($project){

                $this->success('删除成功！');
            }else{
                $this->error('删除失败！');
            }
        }


    }


    public function add_room(){
        $room_id = $_POST['room_id'];
        $type = $_POST['type'];
        $project_id = $_POST['project_id'];
        $cate_id = $_POST['cate_id'];
        if($type == 1){//为+
            $where['id'] = $project_id;
            $where['cate_id'] = $cate_id;
            $project = M('Meter_read_project')->where($where)->find();
            if($project['room_id']){
                $room_ids =explode(',',$project['room_id']);
                $room_ids[] = $room_id;
                $room_ids = implode(',',$room_ids);
                $data['room_id'] = $room_ids;
                $project = M('Meter_read_project')->where($where)->data($data)->save();
            }else{
                $data['room_id'] = $room_id;
                $project = M('Meter_read_project')->where($where)->data($data)->save();
            }
        }elseif($type == 2){//为-
            $where['id'] = $project_id;
            $where['cate_id'] = $cate_id;
            $project = M('Meter_read_project')->where($where)->find();
            if($project['room_id']) {
                $room_ids = explode(',', $project['room_id']);
                foreach($room_ids as $k=>$v){
                    if($room_id == $v){
                        unset($room_ids[$k]);
                    }
                }
                $room_id = implode(',', $room_ids);
                $data['room_id'] = $room_id;
                $project = M('Meter_read_project')->where($where)->data($data)->save();
            }
        }
    }


}