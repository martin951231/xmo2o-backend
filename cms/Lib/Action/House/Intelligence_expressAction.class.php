<?php
/**
 * Created by PhpStorm.
 * User: 郭杰
 * Date: 2019/12/11
 * Time: 18:58
 */
class Intelligence_expressAction extends BaseAction
{
    protected $village_id;

    public function _initialize()
    {
        parent::_initialize();
        $this->village_id = $this->house_session['village_id'];
    }

    public function express_list(){
        $where = [];
        $where['village_id'] = $this->village_id;
        $count = M('Intelligence_express')->where($where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count, 20, 'page');
        $dataList = M('Intelligence_express')->where($where)->select();
        $this->assign('dataList',$dataList);
        $this->assign('pagebar',$p->show());
        $res = D('House_open_door_adver')->equipmentTypeArr();
        $this->assign('device_type_info',$res[2]);
        $this->display();
    }

    public function express_add(){

        if(IS_POST){
            $now_express = M('Intelligence_express')->where(array('equipment_id'=>$_POST['equipment_id']))->find();
            if($now_express){
                $this->error('快递设备号必须唯一！');
            }
            $data['equipment_id'] = $_POST['equipment_id'];
            $data['equipment_name'] = $_POST['equipment_name'];
            $data['screen_size_type'] = $_POST['screen_size_type'];
            $data['num'] = $_POST['num'];
            $data['create_time'] = time();
            $data['status'] = 1;
            $data['village_id'] = $this->village_id;
            $insert_id = M('Intelligence_express')->data($data)->add();
            if($insert_id){
                for ($x=0; $x<$_POST['num']; $x++) {
                    $num['number_id'] = $x+1;
                    $num['type'] = 0;
                    $num['is_null'] = 0;
                    $num['is_null'] = 0;
                    $num['equipment_id'] = $insert_id;
                    $num['create_time'] = time();
                    D('Express_num')->data($num)->add();

                }
                $this->success('添加成功！');
            }else{
                $this->error('添加失败！');
            }
        }else{
            $res = D('House_open_door_adver')->equipmentTypeArr();
            $this->assign('device_type_info',$res[2]);

            $this->display();
        }

    }

    public function express_edit(){

        if(IS_POST){

            $data['equipment_id'] = $_POST['equipment_id'];
            $data['equipment_name'] = $_POST['equipment_name'];
            $data['screen_size_type'] = $_POST['screen_size_type'];
            $data['num'] = $_POST['num'];
            $express = M('Intelligence_express')->where(array('id'=>$_POST['id']))->find();
            if($express['num'] != $_POST['num']){
                $express_num = D('Express_num')->where(array('equipment_id'=>$_POST['id']))->delete();

            }
            $res = M('Intelligence_express')->where(array('id'=>$_POST['id']))->save($data);
            if($res){
                for ($x=0; $x<$_POST['num']; $x++) {
                    $num['number_id'] = $x+1;
                    $num['type'] = 0;
                    $num['is_null'] = 0;
                    $num['is_null'] = 0;
                    $num['equipment_id'] = $_POST['id'];
                    $num['create_time'] = time();
                    D('Express_num')->data($num)->add();
                }
                $this->success('修改成功！');
            }else{
                $this->error('修改失败！');
            }
        }else{
            $express = M('Intelligence_express')->where(array('id'=>$_GET['id']))->find();
            $res = D('House_open_door_adver')->equipmentTypeArr();
            $this->assign('device_type_info',$res[2]);
            $this->assign('express',$express);
            $this->display();
        }

    }

    public function express_del(){
        if($_GET['id']){
            $result = M('Intelligence_express')->where(array('id'=>$_GET['id']))->delete();
            if($result){
                D('Express_num')->where(array('equipment_id'=>$_GET['id']))->delete();
                $this->success('删除成功');
            }else{
                $this->error('删除失败');
            }
        }else{
            $this->error('数据不存在');
        }
    }

    public function express_index()
    {
        $id = $_GET['id'];
        if(empty($id)){
            $this->success('数据有误!');
        }
        $express_num_list = D('Express_num')->where(array('equipment_id'=>$id))->order('number_id')->select();

        $express_num_count = D('Express_num')->where(array('equipment_id'=>$id))->count();
        $express_num_count_min = D('Express_num')->where(array('equipment_id'=>$id,'type'=>0))->count();
        $express_num_count_in = D('Express_num')->where(array('equipment_id'=>$id,'type'=>1))->count();
        $express_num_count_max = D('Express_num')->where(array('equipment_id'=>$id,'type'=>2))->count();
        if(!$express_num_count){
            $express_num_count = 0;
        }
        if(!$express_num_count_min){
            $express_num_count_min = 0;
        }
        if(!$express_num_count_in){
            $express_num_count_in = 0;
        }
        if(!$express_num_count_max){
            $express_num_count_max = 0;
        }

        $express_un_num_count = D('Express_num')->where(array('equipment_id'=>$id,'is_null'=>0))->count();
        $express_un_num_count_min = D('Express_num')->where(array('equipment_id'=>$id,'type'=>0,'is_null'=>0))->count();
        $express_un_num_count_in = D('Express_num')->where(array('equipment_id'=>$id,'type'=>1,'is_null'=>0))->count();
        $express_un_num_count_max = D('Express_num')->where(array('equipment_id'=>$id,'type'=>2,'is_null'=>0))->count();
        if(!$express_un_num_count){
            $express_un_num_count = 0;
        }
        if(!$express_un_num_count_min){
            $express_un_num_count_min = 0;
        }
        if(!$express_un_num_count_in){
            $express_un_num_count_in = 0;
        }
        if(!$express_un_num_count_max){
            $express_un_num_count_max = 0;
        }
        $this->assign('dataList',$express_num_list);
        $this->assign('express_num_count',$express_num_count);
        $this->assign('express_num_count_min',$express_num_count_min);
        $this->assign('express_num_count_in',$express_num_count_in);
        $this->assign('express_num_count_max',$express_num_count_max);

        $this->assign('express_un_num_count',$express_un_num_count);
        $this->assign('express_un_num_count_min',$express_un_num_count_min);
        $this->assign('express_un_num_count_in',$express_un_num_count_in);
        $this->assign('express_un_num_count_max',$express_un_num_count_max);


        $this->assign('express_num_id',$id);
        $this->display();
    }

    public function express_num_edit(){

        if(IS_POST){
            $data['type'] = $_POST['type'];
            $res = M('Express_num')->where(array('id'=>$_POST['id']))->save($data);
            if($res){
                $this->success('修改成功！');
            }else{
                $this->error('修改失败！');
            }

        }else{
            $express = D('Express_num')->where(array('id'=>$_GET['id']))->find();
            $this->assign('express',$express);
            $this->display();
        }

    }


    public function express_num_log_list(){
        if(empty($_GET['id'])){
            $this->error('快递柜信息不存在!');
        }
        $condition['id'] = $_GET['id'];

        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'pickup_code') {
                $condition['pickup_code'] = $_GET['keyword'];
            } else if ($_GET['searchtype'] == 'express_odd_numbers') {
                $condition['express_odd_numbers'] = $_GET['keyword'] ;
            }
        }
        if($_GET['phone']){
            $condition['phone'] = $_GET['phone'];
        }

        if ($_GET['begin_time']&&!$_GET['end_time']) {
            $period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
            $time_condition = " (create_time > ".$period[0].")";
            $condition['pay_time_str']=$time_condition;
        }

        if (!$_GET['begin_time']&&$_GET['end_time']) {
            $period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
            $time_condition = " (create_time < ".$period[1].")";
            $condition['pay_time_str']=$time_condition;
        }

        if(isset($_GET['begin_time'])&&isset($_GET['end_time'])&&!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
            if ($_GET['begin_time']>$_GET['end_time']) {
                $this->error("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
            $time_condition = " (create_time BETWEEN ".$period[0].' AND '.$period[1].")";
            $condition['pay_time_str']=$time_condition;
        }

        $count = M('Express_num_log')->where($condition)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count, 20, 'page');
        $dataList = M('Express_num_log')->where($condition)->select();
        $this->assign('dataList',$dataList);
        $this->assign('pagebar',$p->show());
        $this->display();
    }



}