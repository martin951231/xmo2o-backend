<?php
class BaodiAction extends BaseAction{
	public function adver_list(){
		$block = $_GET['block'];
		$this->assign('block', $block);
		$this->assign('title', '区块'.$block);

		$where = [
			'block' => $block,
			'is_del' => 0
		];
		$list = M("Baodi_index")->field(true)->where($where)->order('`sort` DESC,`id` ASC')->select();
		foreach ($list as $key => $value) {
			$list[$key]['pic'] = file_domain().$value['pic'];
			$list[$key]['parent'] = '';
			if($block == 2){
				if($value['pid'] == '0'){
					$list[$key]['parent'] = '<span style="color:red;">我是一级</span>';
				}
				else{
					$list[$key]['parent'] = '<span style="color:green;">我是<'.M("Baodi_index")->where(['id'=>$value['pid']])->getField('title').'>的下级</span>';	
				}
			}
		}
		$this->assign('list',$list);

		$this->display();
	}

	public function adver_add(){
		$id = $_GET['id'];
		$info = [];
		if($id > 0){
			$info = M("Baodi_index")->where(['id'=>$id])->find();
			$_GET['block'] = $info['block'];
			$info['pic'] = file_domain().$info['pic'];
		}
		$this->assign('info', $info);
		$block = $_GET['block'];
		$this->assign('block', $block);

		$plist = [];
		if($block == 2 && (empty($id) || $info['pid'] > 0)){	
			$plist = M("Baodi_index")->where(['pid'=>0, 'is_del'=>0, 'block'=>2])->select();

		}
		$this->assign('plist', $plist);
		$this->display();
	}

	public function modify(){
		if($_FILES['pic']['error'] != 4){	
			$image = D('Image')->handle($this->system_session['id'], 'slider');
			if (!$image['error']) {			
				$_POST['pic'] = $image['url']['pic'];
			} else {
				$this->frame_submit_tips(0, $image['msg'] ? $image['msg'] : $image['message']);
			}
		}
		$database_slider = M('Baodi_index');
		$_POST['bgcolor'] = $_POST['font_color'];
		if($_POST['id'] > 0){
			if($database_slider->data($_POST)->where(['id'=>$_POST['id']])->save()){
				// D('Image')->update_table_id('/upload/slider/' . $_POST['pic'], $id, 'slider');

				$this->frame_submit_tips(1,'编辑成功！');
			}else{
				$this->frame_submit_tips(0,'编辑失败！请重试~');
			}
		}
		else{
			$_POST['add_time'] = $_SERVER['REQUEST_TIME'];
			if($id = $database_slider->data($_POST)->add()){
				// D('Image')->update_table_id('/upload/slider/' . $_POST['pic'], $id, 'slider');

				$this->frame_submit_tips(1,'添加成功！');
			}else{
				$this->frame_submit_tips(0,'添加失败！请重试~');
			}
		}		
		
	}

	public function adver_del(){
		$database_slider = M('Baodi_index');
		$condition_slider['id'] = $_POST['id'];
		$now_slider = $database_slider->field(true)->where($condition_slider)->find();
		if($database_slider->where($condition_slider)->data(['is_del'=>1])->save()){
			
			$this->success('删除成功');
		}else{
			$this->error('删除失败！请重试~');
		}
	}
}