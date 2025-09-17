<?php
/*招聘
*20190327
*/
class RecruitAction extends BaseAction
{	
 	//协议配置
    public function config(){
        redirect(U('Config/index',array('galias'=>'recruit')));
    }

	// 简历管理
	public function resume_list()
	{
		$condition = array();
		if ($_GET['type']&&$_GET['keyword']) {
			$condition[$_GET['type']] = $_GET['keyword'];
		}

		if ($_GET['province_idss']) {
			$condition['province'] = $_GET['province_idss'];
		}
		if ($_GET['city_idss']) {
			$condition['city'] = $_GET['city_idss'];
		}
		if ($_GET['area_id']) {
			$condition['area'] = $_GET['area_id'];
		}

		// 工作年限
		$work_years_arr = D('Recruit_position')->work_years_arr;
		$work_years_arr[1] = '应届毕业生';
		// 学历
		$qualifications_arr = D('Recruit_position')->qualifications_arr;
		// 月薪
		$salary_arr = D('Recruit_position')->salary_arr;
		// 月薪
		$welfare_arr = D('Recruit_position')->welfare_arr;
		$list = D('Recruit_resume')->get_limit_list_page($condition);
		foreach ($list['list'] as &$value) {
		
			$value['work_years'] = $work_years_arr[$value['work_years']];
			$value['qualifications'] = $qualifications_arr[$value['qualifications']];
			$value['salary'] = $salary_arr[$value['salary']];
		}
		$this->assign('list',$list);
		$this->display();
	}

	// 简历管理
	public function resume_detail()
	{
		if (!intval($_GET['resume_id'])) {
			$this->error('参数不对');
		}
		$position = D('Recruit_resume')->get_one($_GET['resume_id']);
		
		// 工作年限
		$work_years_arr = D('Recruit_position')->work_years_arr;
		$work_years_arr[1] = '应届毕业生';
		// 学历
		$qualifications_arr = D('Recruit_position')->qualifications_arr;
		// 月薪
		$salary_arr = D('Recruit_position')->salary_arr;
		$position['work_years'] = $work_years_arr[$position['work_years']];
		$position['qualifications'] = $qualifications_arr[$position['qualifications']];
		$position['salary'] = $salary_arr[$position['salary']];


		$position_name = D('Recruit_position_category')->where(array('id'=>$position['position_cat_id']))->find();
		$position['position_name'] = $position_name['name'] ? $position_name['name'] : '暂无';
		$this->assign('detail',$position);
		$this->display();
	}

	// 简历状态
	public function ajax_change_resume_status()
	{
		$database_recruit_resume_detail = D('Recruit_resume_detail');	

		if (!intval($_POST['id'])) {
			echo json_encode(array('error'=>1,'msg'=>'参数不对'));die;
		}
		$resume_id = intval($_POST['id']);
		$now_resume = D('Recruit_resume')->get_one($resume_id);
		if (!$now_resume) {
			echo json_encode(array('error'=>1,'msg'=>'简历不存在'));die;
		}

		$res = D('Recruit_resume')->where(array('resume_id'=>$resume_id))->setField('is_hide',$_POST['is_hide']);
		if ($res) {
			echo json_encode(array('error'=>0,'msg'=>'操作成功'));die;
		}else{
			echo json_encode(array('error'=>1,'msg'=>'操作失败'));die;
		}
		
	}

	// 删除简历
	public function resume_del()
	{
		$database_recruit_resume_detail = D('Recruit_resume_detail');	

		if (!intval($_POST['id'])) {
			$this->error('参数不对');
		}
		$resume_id = intval($_POST['id']);
		$now_resume = D('Recruit_resume')->get_one($resume_id);
		if (!$now_resume) {
			$this->error('简历不存在');
		}

		$del = D('Recruit_resume')->where(array('resume_id'=>$resume_id))->delete();
		if ($del) {
			D('Recruit_resume_detail')->where(array('resume_id'=>$resume_id))->delete();
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}
		
	}

	// 添加简历
	public function resume_edit()
	{

		// 工作年限
		$work_years_arr = D('Recruit_position')->work_years_arr;
		$work_years_arr[1] = '应届毕业生';
		// 学历
		$qualifications_arr = D('Recruit_position')->qualifications_arr;
		// 月薪
		$salary_arr = D('Recruit_position')->salary_arr;
		// 职位
		$position_category = D('Recruit_position_category')->where(array('fid'=>0,'is_hide'=>0))->select();
		$this->assign('work_years_arr',$work_years_arr);
		$this->assign('qualifications_arr',$qualifications_arr);
		$this->assign('salary_arr',$salary_arr);
		$this->assign('position_category',$position_category);
		if (IS_POST) {
			if($_POST['long_lat']){
				$long_lat = explode(',', $_POST['long_lat']);
				$_POST['lng'] = $long_lat[0];
				$_POST['lat'] = $long_lat[1];
				$now_city = $this->getLocationCity($_POST['lat'],$_POST['lng']);

				if ($now_city['area_id']) {
			        $condition_city['area_type'] = 2;
			        $condition_city['area_id'] = $now_city['area_id'];
			        $oCity = M('Area')->where($condition_city)->find();
			        $_POST['city'] = $now_city['area_id'];
					$_POST['area'] = $now_city['now_area_id'];
					$_POST['province'] = $oCity['area_pid'];

				}
			}

			$_POST['update_time'] = time();
			if ($_POST['resume_id']) {
				$res = D('Recruit_resume')->save($_POST);

				M('Recruit_resume_detail')->where(array('resume_id'=>$_POST['resume_id']))->delete();
				if ($_POST['company_name']) {
					foreach ($_POST['company_name'] as $key => $value) {
						$data = array();
						$data['resume_id'] = $_POST['resume_id'];
						$data['type'] = 1;
						$data['name'] = $_POST['company_name'][$key];
						$data['desc'] = $_POST['company_position'][$key];
						$data['start_time'] = strtotime($_POST['w_start_time'][$key]);
						$data['end_time'] = strtotime($_POST['w_end_time'][$key]);
						$data['add_time'] = time();
						$add_work = M('Recruit_resume_detail')->add($data);
					}
				}
				if ($_POST['q_name']) {
					foreach ($_POST['q_name'] as $key => $value) {
						$data = array();
						$data['resume_id'] = $_POST['resume_id'];
						$data['type'] = 2;
						$data['name'] = $_POST['q_name'][$key];
						$data['desc'] = $qualifications_arr[$_POST['q_qualifications'][$key]];
						$data['start_time'] = strtotime($_POST['q_start_time'][$key]);
						$data['end_time'] = strtotime($_POST['q_end_time'][$key]);
						$data['add_time'] = time();
						$add_education =M('Recruit_resume_detail')->add($data);
					}
				}

				$update_data['has_work']  = 0;
				$update_data['has_education']  = 0;
				if ($add_work) {
					$update_data['has_work']  = 1;
				}
				if ($add_education) {
					$update_data['has_education']  = 1;
				}
				$update_data['resume_id'] = $_POST['resume_id'];
				$res = D('Recruit_resume')->save($update_data);
				
			}else{
				$_POST['add_time'] = time();
				$res = D('Recruit_resume')->add($_POST);

				if ($_POST['company_name']) {
					foreach ($_POST['company_name'] as $key => $value) {
						$data = array();
						$data['resume_id'] = $res;
						$data['type'] = 1;
						$data['name'] = $_POST['company_name'][$key];
						$data['desc'] = $_POST['company_position'][$key];
						$data['start_time'] = strtotime($_POST['w_start_time'][$key]);
						$data['end_time'] = strtotime($_POST['w_end_time'][$key]);
						$data['add_time'] = time();
						$add_work = M('Recruit_resume_detail')->add($data);
					}
				}
				if ($_POST['q_name']) {
					foreach ($_POST['q_name'] as $key => $value) {
						$data = array();
						$data['resume_id'] = $res;
						$data['type'] = 2;
						$data['name'] = $_POST['q_name'][$key];
						$data['desc'] = $_POST['q_qualifications'][$key];
						$data['start_time'] = strtotime($_POST['q_start_time'][$key]);
						$data['end_time'] = strtotime($_POST['q_end_time'][$key]);
						$data['add_time'] = time();
						$add_education =M('Recruit_resume_detail')->add($data);
					}
				}
				$update_data['has_work']  = 0;
				$update_data['has_education']  = 0;
				if ($add_work) {
					$update_data['has_work']  = 1;
				}
				if ($add_education) {
					$update_data['has_education']  = 1;
				}
				$update_data['resume_id'] = $res;
				$res = D('Recruit_resume')->save($update_data);
			}
			if ($res!==false) {
				$this->success('编辑成功');
			}else{
				$this->error('编辑失败');
			}
		}else{
			if ($_GET['id']) {
				$condition['resume_id'] = intval($_GET['id']);
				$now_resume = D('Recruit_resume')->get_one($_GET['id']);
				// var_dump($now_resume);
				// $position_name = D('Recruit_position_category')->where(array('id'=>$position['position_cat_id']))->find();
				$this->assign('detail',$now_resume);
			}
			// var_dump($detail);
			$this->display();
		}
	}


	// 职位管理
	public function position_list()
	{
		$condition = array();
		if ($_GET['type']&&$_GET['keyword']) {
			$condition[$_GET['type']] = $_GET['keyword'];
		}

		if ($_GET['province_idss']) {
			$condition['province'] = $_GET['province_idss'];
		}
		if ($_GET['city_idss']) {
			$condition['city'] = $_GET['city_idss'];
		}
		if ($_GET['area_id']) {
			$condition['area'] = $_GET['area_id'];
		}

		$list = D('Recruit_position')->get_limit_list_page($condition);
		$this->assign('list',$list);
		$this->display();
	}

	// 审核职位
	public function position_audit()
	{
		if (IS_POST) {
			$_POST['audit_time'] = time();
			$position = D('Recruit_position')->get_one($_POST['position_id']);
			$upd = D('Recruit_position')->save($_POST);
			if($upd){
				if ($_POST['status']==1&&$position['status']!=1) {
					$position = D('Recruit_position')->get_one($_POST['position_id']);
					// 添加滚动播报
    				$length = utf8_strlen($position['company_name']);
    				$company_name = $position['company_name'];
    				if ($length>8) {
    					$company_name = mb_substr($company_name,7,8);;
    				}
					$data['text'] = $company_name.'发布了'.$position['position_name'].'职位';
					$data['time'] = time();
					$data['business_id'] = $_POST['position_id'];
					$data['business_type'] = 'position';
					D('Recruit_scrollmsg')->add($data);
				}
				$this->success('审核成功');
			}else{
				$this->error('审核失败');
			}
		}else{
			if (!intval($_GET['position_id'])) {
				$this->error('参数不对');
			}

			$position = D('Recruit_position')->get_one($_GET['position_id']);

			// 工作年限
			$work_years_arr = D('Recruit_position')->work_years_arr;
			// 学历
			$qualifications_arr = D('Recruit_position')->qualifications_arr;
			// 月薪
			$salary_arr = D('Recruit_position')->salary_arr;
			// 月薪
			$welfare_arr = D('Recruit_position')->welfare_arr;
		
			$position['work_years'] = $work_years_arr[$position['work_years']];
			$position['qualifications'] = $qualifications_arr[$position['qualifications']];
			$position['salary'] = $salary_arr[$position['salary']];

			$welfare_list = explode(',',$position['welfare']);
			$welfare_txt_list = array();
			foreach ($welfare_list as $_welfare) {
				$welfare_txt_list[] =  $welfare_arr[$_welfare];
			}
			$position['welfare'] = implode(',',$welfare_txt_list);
			$this->assign('detail',$position);
			$this->display();
		}
	}

	// 删除职位
	public function position_del()
	{
		if (!intval($_POST['id'])) {
			$this->error('参数不对');
		}
		$position = D('Recruit_position')->get_one($_POST['id']);
		if (!$position) {
			$this->error('职位不存在');
		}
		$del = D('Recruit_position')->where(array('position_id'=>intval($_POST['id'])))->delete();
		if ($del) {
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}
	}

	// 通过父分类获得子分类
	public function ajax_position_category()
	{
		
		$fid = intval($_POST['fid']);

		$condition['fid'] = $fid;
		$list = D('Recruit_position_category')->where($condition)->select();
		$list = $list ? $list : array();
		echo json_encode($list);die;
		
	}

	// 分类
	public function position_category()
	{
		
		$fid = intval($_GET['fid']);
		$condition['fid'] = $fid;
		
		$list = D('Recruit_position_category')->where($condition)->select();
		$this->assign('list',$list);
		$this->display();
		
	}

	// 分类
	public function position_category_edit()
	{

		if (IS_POST) {

			if ($_POST['fid']) {
				$_POST['level'] = 2;
			}
			if ($_POST['id']) {
				$res = D('Recruit_position_category')->save($_POST);
			}else{
				$res = D('Recruit_position_category')->add($_POST);
			}
			if ($res) {
				$this->success('编辑成功');
			}else{
				$this->error('编辑失败');
			}
		}else{
			if ($_GET['id']) {
				$condition['id'] = intval($_GET['id']);
				$detail = D('Recruit_position_category')->where($condition)->find();
				$this->assign('detail',$detail);
			}
			$this->display();
		}
	}

	// 删除职位分类
	public function position_category_del()
	{
		if (!intval($_POST['id'])) {
			$this->error('参数不对');
		}
		$category = D('Recruit_position_category')->where(array('id'=>$_POST['id']))->find();
		if ($category) {
			$this->error('分类不存在');
		}
		$del = D('Recruit_position_category')->where(array('id'=>intval($_POST['id'])))->delete();
		if ($del) {
			D('Recruit_position_category')->where(array('fid'=>$_POST['id']))->delete();
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}
	}

	// 企业列表
	public function company_list()
	{
		$condition = array();
		if ($_GET['type']&&$_GET['keyword']) {
			$condition[$_GET['type']] = $_GET['keyword'];
		}
		if ($_GET['province_idss']) {
			$condition['province'] = $_GET['province_idss'];
		}
		if ($_GET['city_idss']) {
			$condition['city'] = $_GET['city_idss'];
		}
		if ($_GET['area_id']) {
			$condition['area'] = $_GET['area_id'];
		}
		$list = D('Recruit_company')->get_limit_list_page($condition);
		$this->assign('list',$list);
		$this->display();
	}

	// 审核企业
	public function company_audit()
	{
		if (IS_POST) {
			$_POST['audit_time'] = time();
			$upd = D('Recruit_company')->save($_POST);
			if($upd){
				$this->success('审核成功');
			}else{
				$this->error('审核失败');
			}
		}else{
			if (!intval($_GET['company_id'])) {
				$this->error('参数不对');
			}
			$company = D('Recruit_company')->get_one($_GET['company_id']); 
			$company['license_pic_v'] = implode(';',$company['license_pic']);
            $company['company_pic_v'] = implode(';',$company['company_pic']);
			$this->assign('detail',$company);
			$this->display();
		}
	}

	// 删除企业
	public function company_del()
	{
		if (!intval($_POST['id'])) {
			$this->error('参数不对');
		}
		
		$company = D('Recruit_company')->where(array('company_id'=>$_POST['id']))->find();
		if (!$company) {
			$this->error('企业不存在');
		}
		$del = D('Recruit_company')->where(array('company_id'=>intval($_POST['id'])))->delete();
		if ($del) {
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}
	}

	// 添加企业
	public function company_edit()
	{
		if (IS_POST) {
			if($_POST['license_pic']){
				$_POST['license_pic'] = json_encode($_POST['license_pic']);
			}

			if($_POST['company_pic']){
				$_POST['company_pic'] = json_encode($_POST['company_pic']);
			}
			if ($_POST['status']==1) {
				$_POST['audit_time'] = time();
			}

			if($_POST['long_lat']){
				$long_lat = explode(',', $_POST['long_lat']);
				$_POST['lng'] = $long_lat[0];
				$_POST['lat'] = $long_lat[1];
				$now_city = $this->getLocationCity($_POST['lat'],$_POST['lng']);

				if ($now_city['area_id']) {
			        $condition_city['area_type'] = 2;
			        $condition_city['area_id'] = $now_city['area_id'];
			        $oCity = M('Area')->where($condition_city)->find();
			        $_POST['city'] = $now_city['area_id'];
					$_POST['area'] = $now_city['now_area_id'];
					$_POST['province'] = $oCity['area_pid'];

				}
			}

			if ($_POST['company_id']) {
				$res = D('Recruit_company')->save($_POST);
			}else{
				$_POST['add_time'] = time();
				$res = D('Recruit_company')->add($_POST);
			}
			if ($res) {
				$this->success('编辑成功');
			}else{
				$this->error('编辑失败');
			}
		}else{
			if ($_GET['id']) {
				$condition['company_id'] = intval($_GET['id']);
				$company = D('Recruit_company')->get_one($_GET['id']);
				$company['license_pic_v'] = implode(';',$company['license_pic']);
	            $company['company_pic_v'] = implode(';',$company['company_pic']);
				$this->assign('detail',$company);
			}
			// var_dump($detail);
			$this->display();
		}
	}



	// 查看大图
	public function view_image()
	{
		$this->assign('url',$_GET['url']);
		$this->display();
	}

	// 商学院视频
	public function course_list()
	{

		$condition = array();
		if ($_GET['type']&&$_GET['keyword']) {
			$condition[$_GET['type']] = $_GET['keyword'];
		}

		$list = D('Recruit_course')->get_limit_list_page($condition);
		$this->assign('list',$list);
		$this->display();
	}

	// 封面图片修改
	public function course_edit()
	{
		if (IS_POST) {
			$upd = D('Recruit_course')->save($_POST);
			if($upd){
				$this->success('修改成功');
			}else{
				$this->error('修改失败');
			}
		}else{
			if (!intval($_GET['course_id'])) {
				$this->error('参数不对');
			}
			$course = D('Recruit_course')->where(array('course_id'=>$_GET['course_id']))->find();
			if ($course['imgUrl']) {
			$course['imgUrl'] = json_decode($course['imgUrl']);
		}
			$category = M('Recruit_course_category')->where(array('is_hide'=>0))->select();
			$this->assign('category',$category);
			$this->assign('detail',$course);
			$this->display();
		}
	}
	// 审核视频
	public function course_audit()
	{
		if (IS_POST) {
			$_POST['audit_time'] = time();
			$upd = D('Recruit_course')->save($_POST);
			if($upd){
				$this->success('审核成功');
			}else{
				$this->error('审核失败');
			}
		}else{
			if (!intval($_GET['course_id'])) {
				$this->error('参数不对');
			}
			$course = D('Recruit_course')->where(array('course_id'=>$_GET['course_id']))->find();
			if ($course['imgUrl']) {
			$course['imgUrl'] = json_decode($course['imgUrl']);
		}
			$this->assign('detail',$course);
			$this->display();
		}
	}

	// 删除视频
	public function course_del()
	{
		if (!intval($_POST['id'])) {
			$this->error('参数不对');
		}
		$position = D('Recruit_course')->where(array('course_id'=>$_POST['id']))->find();
		if (!$position) {
			$this->error('视频不存在');
		}
		$del = D('Recruit_course')->where(array('course_id'=>intval($_POST['id'])))->delete();
		if ($del) {
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}
	}

	// 视频分类
	public function course_category()
	{
		$list = D('Recruit_course_category')->select();
		$this->assign('list',$list);
		$this->display();
		
	}

	// 视频分类
	public function course_category_edit()
	{
		if (IS_POST) {
			if ($_POST['id']) {
				$res = D('Recruit_course_category')->save($_POST);
			}else{
				$res = D('Recruit_course_category')->add($_POST);
			}
			if ($res) {
				$this->success('编辑成功');
			}else{
				$this->error('编辑失败');
			}
		}else{
			if ($_GET['id']) {
				$condition['id'] = intval($_GET['id']);
				$detail = D('Recruit_course_category')->where($condition)->find();
				$this->assign('detail',$detail);
			}
			$this->display();
		}
	}



	// 积分购买套餐
	public function score_package()
	{	
		$condition['is_del'] = 0;
		$list = D('Recruit_score_package')->get_limit_list_page($condition);
		$this->assign('list',$list);
		$this->display();
	}
	
	//添加积分购买套餐
	public function score_package_add(){
		if(IS_POST){
			$_POST['add_time'] = time();
			if(M('Recruit_score_package')->data($_POST)->add()){
				D('Config')->clearCache();
				$this->frame_submit_tips(1,'添加成功！');
			}else{
				$this->frame_submit_tips(0,'添加失败！请重试~');
			}
		}else{
			$this->display();
		}
	}

	//修改积分购买套餐
	public function score_package_edit(){
		if(IS_POST){
			if(M('Recruit_score_package')->data($_POST)->save()){
				D('Config')->clearCache();
				$this->frame_submit_tips(1,'修改成功！');
			}else{
				$this->frame_submit_tips(0,'修改失败！请重试~');
			}
		}else{
			$now_package = D('Recruit_score_package')->get_one($_GET['package_id']);
			$this->assign('now_package',$now_package);
			$this->display();
		}
	}

	//删除积分购买套餐
	public function score_package_del(){
		$id 	= intval($_POST['id']);
		$where  = array('package_id'=>$id);
		if(M('Recruit_score_package')->where($where)->setField('is_del',1)){
			$this->success('删除成功');
		}
	}

	//积分订单列表
	public function score_package_order(){
		if ($_POST['keyword']) {
			switch ($_POST['type']) {
				case 'name':
					$where['name'] = $_POST['type'];
					break;
				case 'nickname':
					$where['nickname'] = $_POST['nickname'];
					break;
				case 'phone':
					$where['phone'] = $_POST['phone'];
					break;
			}
		}
		$list = D('Recruit_score_package_order')->get_limit_list_page($where);
		foreach ($list['list'] as &$value) {
			$value['pay_type'] = D('Pay')->get_pay_name($value['pay_type'],0);
		}
		$this->assign('list',$list);
		$this->display();
	}

	//积分订单详情
	public function score_package_order_detail(){
		$where['order_id'] = $_GET['order_id'];
		$list = D('Recruit_score_package_order')->get_limit_list_page($where);
		foreach ($list['list'] as &$value) {
			$value['pay_type'] = D('Pay')->get_pay_name($value['pay_type'],0);
		}
		$this->assign('detail',$list['list'][0]);
		$this->display();
	}

	// 能量包购买
	public function energy_package()
	{	
		$condition['is_del'] = 0;
		$list = D('Recruit_energy_package')->get_limit_list_page($condition);
		$this->assign('list',$list);
		$this->display();
	}
	
	//添加积分购买套餐
	public function energy_package_add(){
		if(IS_POST){
			$_POST['add_time'] = time();
			if(M('Recruit_energy_package')->data($_POST)->add()){
				D('Config')->clearCache();
				$this->frame_submit_tips(1,'添加成功！');
			}else{
				$this->frame_submit_tips(0,'添加失败！请重试~');
			}
		}else{
			$this->display();
		}
	}

	//修改积分购买套餐
	public function energy_package_edit(){
		if(IS_POST){
			if(M('Recruit_energy_package')->data($_POST)->save()){
				D('Config')->clearCache();
				$this->frame_submit_tips(1,'修改成功！');
			}else{
				$this->frame_submit_tips(0,'修改失败！请重试~');
			}
		}else{
			$now_package = D('Recruit_energy_package')->get_one($_GET['package_id']);
			$this->assign('now_package',$now_package);
			$this->display();
		}
	}

	//删除积分购买套餐
	public function energy_package_del(){
		$id 	= intval($_POST['id']);
		$where  = array('package_id'=>$id);
		if(M('Recruit_energy_package')->where($where)->setField('is_del',1)){
			$this->success('删除成功');
		}
	}

	//积分订单列表
	public function energy_package_order(){
		if ($_POST['keyword']) {
			switch ($_POST['type']) {
				case 'name':
					$where['name'] = $_POST['type'];
					break;
				case 'nickname':
					$where['nickname'] = $_POST['nickname'];
					break;
				case 'phone':
					$where['phone'] = $_POST['phone'];
					break;
			}
		}
		$list = D('Recruit_energy_package_order')->get_limit_list_page($where);
		foreach ($list['list'] as &$value) {
			$value['pay_type'] = D('Pay')->get_pay_name($value['pay_type'],0);
		}
		$this->assign('list',$list);
		$this->display();
	}

	//积分订单详情
	public function energy_package_order_detail(){
		$where['order_id'] = $_GET['order_id'];
		$list = D('Recruit_energy_package_order')->get_limit_list_page($where);
		foreach ($list['list'] as &$value) {
			$value['pay_type'] = D('Pay')->get_pay_name($value['pay_type'],0);
		}
		var_dump($list['list'][0]);
		$this->assign('detail',$list['list'][0]);
		$this->display();
	}

	// 排名购买列表
	public function ranking()
	{	
		$condition['is_del'] = 0;
		$list = D('Recruit_ranking')->get_limit_list_page($condition);
		foreach ($list['list'] as &$value) {
			// 是否已指定区域
			$bind_area = M('Recruit_ranking_bind_area')->where(array('package_id'=>$value['package_id']))->count();
			$value['bind_area'] = $bind_area;
		}
	
		$this->assign('list',$list);
		$this->display();
	}
	
	//添加排名购买
	public function ranking_add(){
		if(IS_POST){
			$_POST['add_time'] = time();
			if(M('Recruit_ranking')->data($_POST)->add()){
				D('Config')->clearCache();
				$this->frame_submit_tips(1,'添加成功！');
			}else{
				$this->frame_submit_tips(0,'添加失败！请重试~');
			}
		}else{
			$this->display();
		}
	}

	//修改排名购买
	public function ranking_edit(){
		if(IS_POST){
			if(M('Recruit_ranking')->data($_POST)->save()){
				D('Config')->clearCache();
				$this->frame_submit_tips(1,'修改成功！');
			}else{
				$this->frame_submit_tips(0,'修改失败！请重试~');
			}
		}else{
			$now_package = D('Recruit_ranking')->get_one($_GET['package_id']);
			$this->assign('now_package',$now_package);
			$this->display();
		}
	}

	//排名购买订单列表
	public function ranking_order(){
		if ($_POST['keyword']) {
			switch ($_POST['type']) {
				case 'name':
					$where['name'] = $_POST['type'];
					break;
				case 'nickname':
					$where['nickname'] = $_POST['nickname'];
					break;
				case 'phone':
					$where['phone'] = $_POST['phone'];
					break;
			}
		}
		$list = D('Recruit_ranking_order')->get_limit_list_page($where);
		foreach ($list['list'] as &$value) {
			$value['pay_type'] = D('Pay')->get_pay_name($value['pay_type'],0);
		}
		$this->assign('list',$list);
		$this->display();
	}

	//排名购买订单详情
	public function ranking_order_detail(){
		$where['order_id'] = $_GET['order_id'];
		$list = D('Recruit_ranking_order')->get_limit_list_page($where);
		foreach ($list['list'] as &$value) {
			$value['pay_type'] = D('Pay')->get_pay_name($value['pay_type'],0);
		}
		$this->assign('detail',$list['list'][0]);
		$this->display();
	}

	//排名购买订单详情
	public function bind_area_list(){
		$package_id = $_GET['package_id'];
		$list = D('Recruit_ranking_bind_area')->where(array('package_id'=>$package_id))->select();

	   	$areaList = D('Area')->field('area_id, area_name')->select();
        $tmpMap = array();
        foreach ($areaList as $item) {
            $tmpMap[$item['area_id']] = $item['area_name'];
        }
		foreach ($list as &$value) {
			$value['province_name'] = $tmpMap[$value['province']];
			$value['city_name'] = $tmpMap[$value['city']];
			$value['area_name'] = $tmpMap[$value['area']];
		}
		// var_dump($list);
		$this->assign('list',$list);
		$this->display();
	}

	public function bind_area()
    {
        $package_id = isset($_GET['package_id']) ? intval($_GET['package_id']) : 0;
        $now_ranking = D('Recruit_ranking')->field(true)->where(array('package_id' => $package_id))->find();
        if (!$now_ranking) {
            $this->frame_submit_tips(0,'此排名不存在');
        }

		$bind_area = M('Recruit_ranking_bind_area')->where(array('package_id'=>$package_id))->count();
		if ($bind_area) {
            $this->frame_submit_tips(0,'绑定区域不能修改');
		}
        
        if (IS_POST) {
            $areaArray = $_POST['areaIds'];
            $data = array();
            $now_time = time();
            foreach ($areaArray as $row) {
                $arr = explode('-', $row);
            	if (count($arr) == 3) {
                    $data[] = array(
                    	'package_id' => $package_id, 
                    	'province' => $arr[0], 
                    	'city' => $arr[1], 
                    	'area' => $arr[2],
                    	'add_time' => $now_time,
                    );
                }
            }
            if (empty($data)) {
                $this->frame_submit_tips(0,'请选择区域');
            }
            
            $database_recruit_ranking_bind_area = D('Recruit_ranking_bind_area');
            $res = $database_recruit_ranking_bind_area->addAll($data);
            if ( $res) {
            	$this->frame_submit_tips(1,'设置成功');
            }else{
            	$this->frame_submit_tips(0,'设置失败');
            }
        } else {
            $where = array('area_type' => array('lt', 4), 'is_open' => 1);
            if ($this->system_session['area_id']) {
                $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
                if ($now_area['area_type'] == 3) {
                    $where['area_id'] = $this->system_session['area_id'];
                }  else {
                    $where['area_pid'] = $this->system_session['area_id'];
                }
            }
            $areaList = D('Area')->field('area_id, area_pid, area_name')->where($where)->select();
            $tmpMap = array();
            foreach ($areaList as $item) {
                $item['select'] = 0;
                $tmpMap[$item['area_id']] = $item;
            }
            
            $list = array();
            foreach ($areaList as $item) {
                if (isset($tmpMap[$item['area_pid']])) {
                    $tmpMap[$item['area_pid']]['son_list'][$item['area_id']] = &$tmpMap[$item['area_id']];
                } else {
                    $list[$item['area_id']] = &$tmpMap[$item['area_id']];
                }
            }
            $this->assign('area_list', $list);
            $this->display();
        }
        
    }


	// 视频评论
	public function course_commend()
	{
		$condition = array();
		if ($_GET['type']&&$_GET['keyword']) {
			$condition[$_GET['type']] = $_GET['keyword'];
		}

		if ($condition['nickname']) {
			$condition_where['u.nickname'] = array('like','%'.$condition['nickname'].'%');
		}

		if ($condition['title']) {
			$condition_where['co.title'] = array('like','%'.$condition['title'].'%');
		}

 		$page = intval($_GET['page']) ? intval($_GET['page']) : 1;

		$count = M('Video_comment')->field($field)->join('as c LEFT JOIN '.C('DB_PREFIX').'user as u ON c.cl_UserId = u.uid LEFT JOIN '.C('DB_PREFIX').'recruit_course as co ON c.cl_VideoId = co.course_id')->where($condition_where)->count();
		
        import('@.ORG.system_page');
        $p = new Page($count,20,'page');
        $pagebar = $p->show();

		$field = 'c.*,u.nickname,u.avatar,u.uid,co.title';
		$list = M('Video_comment')->field($field)->join('as c LEFT JOIN '.C('DB_PREFIX').'user as u ON c.cl_UserId = u.uid LEFT JOIN '.C('DB_PREFIX').'recruit_course as co ON c.cl_VideoId = co.course_id')->order('c.cl_Id DESC')->where($condition_where)->limit((($page-1)*20).',20')->select();
		$this->assign('list',$list);
		$this->assign('pagebar',$pagebar);
		$this->display();
	}

	// 删除视频
	public function course_commend_del()
	{
		if (!intval($_POST['id'])) {
			$this->error('参数不对');
		}
		
		$del = D('Video_comment')->where(array('cl_Id'=>intval($_POST['id'])))->delete();
		if ($del) {
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}
	}

	//通过城市名或经纬度获取城市信息
	public function getLocationCity($lat,$lng){	
		$arr = $this->geocoder($lat,$lng);
		$city = $this->cityMatchingAll($arr['city'],$arr['district']);
		return $city ? $city : array();
	}

	public function geocoder($lat, $lng){
		if ($lat&&$lng) {
			$url = 'http://api.map.baidu.com/geocoder/v2/?location='.$lat.','.$lng.'&output=json&pois=1&ak=' . $this->config['baidu_map_ak'];
			import('ORG.Net.Http');
			$http = new Http();
			$result = $http->curlGet($url);
			if($result){
				$result = json_decode($result,true);
				$arr = array(
						'city' => $result['result']['addressComponent']['city'],
						'province' => $result['result']['addressComponent']['province'],
						'district' => $result['result']['addressComponent']['district'],
					);
				return $arr;
			}else{
				return array();
			}
		}else{
			return array();
		}
	}

    public function cityMatchingAll($city_id,$area_name='',$lat=0,$lng=0){
        $long   =   strlen($city_id);
        if($long >= 7){
            $city_id    =   str_replace('市','',$city_id);
			$city_id    =   str_replace('地区','',$city_id);
            $city_id    =   str_replace('特别行政区','',$city_id);
            $city_id    =   str_replace('蒙古自治州','',$city_id);
            $city_id    =   str_replace('回族自治州','',$city_id);
            $city_id    =   str_replace('柯尔克孜自治州','',$city_id);
            $city_id    =   str_replace('哈萨克自治州','',$city_id);
            $city_id    =   str_replace('土家族苗族自治州','',$city_id);
            $city_id    =   str_replace('藏族羌族自治州','',$city_id);
			$city_id    =   str_replace('傣族自治州','',$city_id);
			$city_id    =   str_replace('布依族苗族自治州','',$city_id);
			$city_id    =   str_replace('苗族侗族自治州','',$city_id);
			$city_id    =   str_replace('壮族苗族自治州','',$city_id);
			$city_id    =   str_replace('澳门','',$city_id);
			$city_id    =   str_replace('朝鲜族自治州','',$city_id);
			$city_id    =   str_replace('哈尼族彝族自治州','',$city_id);
			$city_id    =   str_replace('傣族景颇族自治州','',$city_id);
			$city_id    =   str_replace('藏族自治州','',$city_id);
			$city_id    =   str_replace('彝族自治州','',$city_id);
			$city_id    =   str_replace('白族自治州','',$city_id);
			$city_id    =   str_replace('傈僳族自治州','',$city_id);
        }
        $database_area = D('Area');
        $database_field = '`area_id`,`area_name`,`area_url`';
        $condition_all_city['area_name'] = $city_id;
        $condition_all_city['area_type'] = 2;
        $condition_all_city['is_open'] = 1;
        $oCity = $database_area->field($database_field)->where($condition_all_city)->find();
        if($city_id&&$oCity){
            // 获取当前区县
            if($area_name){
                $maybe_areaname = str_replace(array('区','县'),'',$area_name);
                $where_area['area_pid'] = $oCity['area_id'];
                $where_area['_string'] = '(`area_name`= "'.$area_name.'" OR `area_name`="'.$maybe_areaname.'")';
                $now_area = D('Area')->where($where_area)->find();
                
                if(empty($now_area)){
                    import('@.ORG.words');
                    $words_class = new words();
                    $data_area['area_pid'] = $oCity['area_id'];
                    $data_area['area_name'] = $area_name;
                    $data_area['area_url'] = $words_class->stringgetFirstCharter($area_name);
                    $data_area['area_type'] = '3';
                    if($data_area['area_id'] = D('Area')->data($data_area)->add()){
                        $now_area = $data_area;
                    }
                }
                
                $oCity['now_area_id'] = $now_area['area_id'];
                $oCity['now_area_name'] = $now_area['area_name'];
            }

            // 通过经纬度获取该区县下距离最近的商场信息
            if($lat && $lng ){
                $oCity['now_area_id'] = $oCity['area_id'];
                $oCity['now_area_name'] = $oCity['area_name'];
                if ($now_area) {
                    $now_tranding = D('Area')->get_tranding_by_countyPid($now_area['area_id'],true);
                    $min_distance_tranding = array();
                    $distance_old = 0;
                    if ($now_tranding) {
                        foreach ($now_tranding as $key => $value) {
                            $distance = getDistance($lat, $lng, $value['lat'], $value['lng']); // 距离
                            if($min_distance_tranding){
                                if ($distance < $distance_old) { // 取较小的距离
                                    $min_distance_tranding = $value;
                                }
                            }else{
                                $min_distance_tranding = $value;
                                $distance_old = $distance;
                            }
                        }
                        if ($min_distance_tranding) {
                            $oCity['now_area_id'] = $min_distance_tranding['market_id'];
                            $oCity['now_area_name'] = $min_distance_tranding['market_name'];
                        }
                    }
                }
            }
            return  $oCity;
        }else{
	        $condition_all_city = array();
			$condition_all_city['area_id'] = $this->config['now_city'];
	        $condition_all_city['area_type'] = 2;
	        $condition_all_city['is_open'] = 1;
	        $oCity = $database_area->field($database_field)->where($condition_all_city)->find();
        	if ($oCity) {
	            $oCity['now_area_id'] = $oCity['area_id'];
	            $oCity['now_area_name'] = $oCity['area_name'];
            	return  $oCity;
        	}else{
        		return array();
        	}
        }
    }
}
?>