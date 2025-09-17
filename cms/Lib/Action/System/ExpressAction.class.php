<?php
/*
 * 快递公司
 *
 * @  Writers    Jaty
 * @  BuildTime  2014/12/21 16:50
 * 
 */
class ExpressAction extends BaseAction{
	public function index(){
		$database_express = D('Express');

		$express_list = $database_express->field(true)->order('`sort` DESC,`id` ASC')->select();
		if ($express_list) {
		    foreach ($express_list as &$item) {
		        if ($item['pic']) {
                    $item['pic_txt'] = replace_file_domain($item['pic']);
                } else {
                    $item['pic_txt'] = '';
                }
            }
        }

		$this->assign('express_list',$express_list);
		
		$this->display();
	}
	public function add(){
		$this->assign('bg_color','#F3F3F3');
		$this->display();
	}
	public function modify(){
		if(IS_POST){
			if($_FILES['pic']['error'] != 4){
				$image = D('Image')->handle($this->system_session['id'], 'single_face', 0, array('size' => 10), false);
				if (!$image['error']) {
					$_POST = array_merge($_POST, $image['url']);
				} else {
					$this->frame_submit_tips(0, $image['msg'] ? $image['msg'] : $image['message']);
				}
			}
            
			$_POST['add_time'] = $_SERVER['REQUEST_TIME'];
			$database_express = D('Express');
			if($id = $database_express->data($_POST)->add()){
                D('Image')->update_table_id('/upload/single_face/' . $_POST['pic'], $id, 'express');
				S('express_list',NULL);
                $this->frame_submit_tips(1,'添加成功！');
			}else{
                $this->frame_submit_tips(0,'添加失败！请重试~');
			}
		}else{
            $this->frame_submit_tips(0,'非法提交,请重新提交~');
		}
	}
	public function edit(){
		$this->assign('bg_color','#F3F3F3');
		$database_express = D('Express');
		$condition_express['id'] = intval($_GET['id']);
		$express = $database_express->field(true)->where($condition_express)->find();
        if ($express['pic']) {
            $express['pic_txt'] = replace_file_domain("/upload/single_face/{$express['pic']}");
        } else {
            $express['pic_txt'] = '';
        }
		$this->assign('express',$express);
		$this->display();
	}
	public function amend(){
		if(IS_POST){
			$_POST['add_time'] = $_SERVER['REQUEST_TIME'];
			$database_express = D('Express');
            $condition_express['id'] = $_POST['id'];
            $now_express = $database_express->field(true)->where($condition_express)->find();
            if($_FILES['pic']['error'] != 4){
                $image = D('Image')->handle($this->system_session['id'], 'single_face', 0, array('size' => 10), false);
                if (!$image['error']) {
                    $_POST = array_merge($_POST, $image['url']);
                } else {
                    $this->frame_submit_tips(0, $image['msg'] ? $image['msg'] : $image['message']);
                }
            }
			if($database_express->data($_POST)->save()){
                D('Image')->update_table_id('/upload/single_face/' . $_POST['pic'], $_POST['id'], 'express');
                if($_POST['pic'] && !empty($now_express['pic'])){
                    unlink('./upload/single_face/'.$now_express['pic']);
                }
				S('express_list',NULL);
                $this->frame_submit_tips(1,'修改成功！');
			}else{
                $this->frame_submit_tips(0,'修改失败！请检查是否有过修改后重试~');
			}
		}else{
            $this->frame_submit_tips(0,'非法提交,请重新提交~');
		}
	}
	public function del(){
		if(IS_POST){
			$database_express = D('Express');
			$condition_express['id'] = intval($_POST['id']);
			$now_express = $database_express->field(true)->where($condition_express)->find();
			if($database_express->where($condition_express)->delete()){
				S('express_list',NULL);
				if(!empty($now_express['pic'])){
                    unlink('./upload/single_face/'.$now_express['pic']);
				}
				$this->success('删除成功！');
			}else{
				$this->error('删除失败！请重试~');
			}
		}else{
			$this->error('非法提交,请重新提交~');
		}
	}
}