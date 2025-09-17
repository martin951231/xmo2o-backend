<?php
class House_village_floorModel extends Model{
    public function house_village_floor_add($data){
        if(!$data){
            return false;
        }
        
        $data['floor_name'] = trim($data['floor_name']);
        if(!$data['floor_name']){
            return array('status'=>0,'msg'=>'单元名称不能为空！');
        }
//        $pattern = '/^[a-zA-Z0-9]+$/u';
//        if(!preg_match($pattern,trim($_POST['floor_name']))){
//            return array('status'=>0,'msg'=>'单元名称只允许字母或数字！');
//        }
//        $data['floor_layer'] = trim($data['floor_layer']);
//        if(!$data['floor_layer']){
//            return array('status'=>0,'msg'=>'单元楼号不能为空！');
//        }

//        if(!$data['floor_type']){
//            return array('status'=>0,'msg'=>'单元类型不能为空！');
//        }
        if (empty($data['single_id'])) {
            return array('status'=>0,'msg'=>'请上传关联楼栋id！');
        }

        $w = array(
            'floor_name' => $data['floor_name'],
            'single_id' => $data['single_id'],
            'village_id' => $_SESSION['house']['village_id'],
            'status' => ['neq', 4]
        );
        $msg = D('House_village_floor')->where($w)->find();
        if ($msg) {
            return array('status'=>0,'msg'=>'该单元已经添加不可重复添加！');
        }

        if (isset($data['floor_number']) && trim($data['floor_number'])) {
            $check_number = "/^[0-9]+$/";
            $floor_number = trim($data['floor_number']);
            if(!preg_match($check_number, $floor_number)){
                return array('status'=>0,'msg'=>'单元编号只允许数字！');
            }
            if(intval($floor_number)<=0 || intval($floor_number)>99){
                return array('status'=>0,'msg'=>'单元编号必须为1-99的数字！');
            }
            if (strlen($floor_number)>2) {
                return array('status'=>0,'msg'=>'单元编号只允最多2位数字！');
            } elseif (strlen($floor_number)<2) {
                // 不足2位 补足2位
                $floor_number = str_pad($floor_number,2,"0",STR_PAD_LEFT);
            }
            $data['floor_number'] = $floor_number;
        }
        if (!$data['floor_number'] && $data['floor_name']) {
            $pattern_number = "/[^0-9]/";
            $floor_number = preg_replace($pattern_number,'',$data['floor_name']);
            // 单元编号 暂时同一个小区下仅限1-99数字编号
            if ($floor_number) {
                if (strlen($floor_number)>2) {
                    // 超过2位 截取后2位
                    $floor_number = substr($floor_number, -2,2);
                } elseif (strlen($floor_number)<2) {
                    // 不足2位 补足2位
                    $floor_number = str_pad($floor_number,2,"0",STR_PAD_LEFT);
                }
                $data['floor_number'] = $floor_number;
            }
        }
        if ($data['floor_number']) {
            $where_repeat = [
                'village_id' => $_SESSION['house']['village_id'],
                'single_id' => $data['single_id'],
                'floor_number' => $data['floor_number'],
                'status' => ['neq', 4]
            ];
            $repeat_id = $this->where($where_repeat)->getField('floor_id');
            if ($repeat_id) {
                return array('status'=>0,'msg'=>'该单元编号已经存在，请填写1-99的单元编号！');
            }
        } else {
            return array('status'=>0,'msg'=>'请填写1-99的不重复单元编号！');
        }





        if(!$data['property_month_num']){
            $data['property_month_num'] = $data['diy_property_month_num'];
            unset($data['diy_property_month_num']);
        }

        if(!$data['presented_property_month_num']){
            $data['presented_property_month_num'] = $data['diy_presented_property_month_num'];
            unset($data['diy_presented_property_month_num']);
        }

		$data['start_layer_num'] = intval($data['start_layer_num']);
		$data['end_layer_num'] = intval($data['end_layer_num']);
        
        $data['add_time'] = time();
        $data['village_id'] = $_SESSION['house']['village_id'];
        $long_lat = explode(',',$data['long_lat']);
        if ($long_lat && $long_lat[0] && $long_lat[1]) {
            $data['long'] = floatval($long_lat[0]);
            $data['lat'] = floatval($long_lat[1]);
        }
        unset($data['long_lat']);
        $insert_id = $this->data($data)->add();
        if($insert_id){
			
			
			//若有效填写起始住人楼层和最高住人楼层，会自动添加楼层
			if($data['start_layer_num'] && $data['end_layer_num'] && $data['end_layer_num'] > $data['start_layer_num']){
				for($i=$data['start_layer_num'];$i<=$data['end_layer_num'];$i++){
					//0楼不能住人
					if($i == 0){
						continue; 
					}
					$data_layer['layer_name'] = $i . '层';
					$data_layer['sort'] = '0';
					$data_layer['status'] = '1';
					$data_layer['single_id'] = $data['single_id'];
					$data_layer['floor_id'] = $insert_id;
					$data_layer['village_id'] = $data['village_id'];
					M('House_village_layer')->data($data_layer)->add();
				}
			}
			
			
            //计划任务
			//D('House_village_face_door')->create_village_plan($data['village_id'],'sync_house_village_info', 0, 'floor', $insert_id);
			D('House_village_face_door')->sync_house_village_info($data['village_id'], 'floor', $insert_id);

            return array('status'=>1,'msg'=>'添加成功！','floor_id'=>$insert_id);
        }else{
            return array('status'=>0,'msg'=>'添加失败！');
        }
    }
    
    public function house_village_floor_page_list($where,$field=true,$order='sort desc, floor_id desc',$pageSize=20){
        if(!$where){
            return false;
        }
        
        import('@.ORG.merchant_page');
        $count = $this->where($where)->count();
        $p = new Page($count,$pageSize,'page');
        
        $village_floor_list = $this->where($where)->field($field)->order($order)->limit($p->firstRow.','.$p->listRows)->select();

        $list['list'] = $village_floor_list;
        $list['pagebar'] = $p->show();
        if($list){
            return array('status'=>1,'list'=>$list);
        }else{
            return array('status'=>0,'list'=>$list);
        }
    }
    
    public function house_village_floor_del($where){
        if(!$where){
            return false;
        }

        $database_house_village_user_bind = D('House_village_user_bind');
        $bind_sum  = $database_house_village_user_bind->where($where)->count();
        if($bind_sum > 0){
            return array('status'=>0,'msg'=>'请先删除单元下所有业主！');
        }

        $insert_id = $this->where($where)->delete();
        
        if($insert_id){
            return array('status'=>1,'msg'=>'删除数据成功！');
        }else{
            return array('status'=>0,'msg'=>'删除数据失败！');
        }
    }
    
    
    public function house_village_floor_detail($where,$field=true){
        if(!$where){
            return false;
        }
        
        $detail = $this->where($where)->field($field)->find();
        if($detail){
            return array('status'=>1,'detail'=>$detail);
        }else{
            return array('status'=>0,'detail'=>$detail);
        }
    }
    
    public function house_village_floor_edit($where,$data){
        if(!$where || !$data){
            return false;
        }

        if(!$data['floor_name']){
            return array('status'=>0,'msg'=>'单元名称不能为空！');
        }
//        $pattern = '/^[a-zA-Z0-9]+$/u';
//        if(!preg_match($pattern,trim($data['floor_name']))){
//            return array('status'=>0,'msg'=>'单元名称只允许字母或数字！');
//        }
        $floor_info = $this->where($where)->find();
        if ($floor_info) {
            $w = array(
                'floor_name' => $data['floor_name'],
                'single_id' => $floor_info['single_id'],
                'village_id' => $floor_info['village_id'],
                'status' => ['neq', 4],
                'floor_id' => ['neq', $floor_info['floor_id']]
            );
            $msg = D('House_village_floor')->where($w)->find();
            if ($msg) {
                return array('status'=>0,'msg'=>'该单元已经添加不可重复添加！');
            }
        }
        if (isset($data['floor_number']) && trim($data['floor_number'])) {
            $check_number = "/^[0-9]+$/";
            $floor_number = trim($data['floor_number']);
            if(!preg_match($check_number, $floor_number)){
                return array('status'=>0,'msg'=>'单元编号只允许数字！');
            }
            if(intval($floor_number)<=0 || intval($floor_number)>99){
                return array('status'=>0,'msg'=>'单元编号必须为1-99的数字！');
            }
            if (strlen($floor_number)>2) {
                return array('status'=>0,'msg'=>'单元编号只允最多2位数字！');
            } elseif (strlen($floor_number)<2) {
                // 不足2位 补足2位
                $floor_number = str_pad($floor_number,2,"0",STR_PAD_LEFT);
            }
            $data['floor_number'] = $floor_number;
        }
        if (!$data['floor_number'] && $data['floor_name']) {
            $pattern_number = "/[^0-9]/";
            $floor_number = preg_replace($pattern_number,'',$data['floor_name']);
            // 单元编号 暂时同一个小区下仅限1-99数字编号
            if ($floor_number) {
                if (strlen($floor_number)>2) {
                    // 超过2位 截取后2位
                    $floor_number = substr($floor_number, -2,2);
                } elseif (strlen($floor_number)<2) {
                    // 不足2位 补足2位
                    $floor_number = str_pad($floor_number,2,"0",STR_PAD_LEFT);
                }
                $data['floor_number'] = $floor_number;
            }
        }
        if ($data['floor_number']) {
            $where_repeat = [
                'village_id' => $floor_info['village_id'],
                'single_id' => $floor_info['single_id'],
                'floor_number' => $data['floor_number'],
                'status' => ['neq', 4]
            ];
            $repeat_id = $this->where($where_repeat)->getField('floor_id');
            if ($repeat_id && $repeat_id!=$floor_info['floor_id']) {
                return array('status'=>0,'msg'=>'该单元编号已经存在，请填写1-99的单元编号！');
            }
        } else {
            return array('status'=>0,'msg'=>'请填写1-99的不重复单元编号！');
        }

//        if(!$data['floor_layer']){
//            return array('status'=>0,'msg'=>'单元楼号不能为空！');
//        }

//        if(!$data['floor_type']){
//            return array('status'=>0,'msg'=>'单元类型不能为空！');
//        }

        if(!$data['property_month_num']){
            $data['property_month_num'] = $data['diy_property_month_num'];
            unset($data['diy_property_month_num']);
        }

        if(!$data['presented_property_month_num']){
            $data['presented_property_month_num'] = $data['diy_presented_property_month_num'];
            unset($data['diy_presented_property_month_num']);
        }
        
        $data['add_time'] = time();

        $long_lat = explode(',',$data['long_lat']);
        if ($long_lat && $long_lat[0] && $long_lat[1]) {
            $data['long'] = floatval($long_lat[0]);
            $data['lat'] = floatval($long_lat[1]);
        }
        unset($data['long_lat']);
        $insert_id = $this->where($where)->data($data)->save();
        
        if($insert_id){
            // 修改成功且修改了单元编号触发编号其单元下房屋编号和对应房屋人员编号变动
            if ($data['floor_number']) {
                $change_param = [
                    'village_id' => $floor_info['village_id'],
                    'floor_id' => $floor_info['floor_id'],
                    'floor_number' => $data['floor_number'],
                ];
                D('House_village_user_vacancy')->change_number($change_param);
            }
            // 计划任务
//             D('House_village_face_door')->create_village_plan($data['village_id'],'sync_house_village_info', 0, 'floor', $insert_id);
            D('House_village_face_door')->sync_house_village_info($data['village_id'], 'floor', $insert_id);
            return array('status'=>1,'msg'=>'修改成功！');
        }else{
            return array('status'=>0,'msg'=>'修改失败！');
        }
    }


    public function get_floor_info($floor_id){
        if(!$floor_id){
            return false;
        }

        $condition_table = array(C('DB_PREFIX').'house_village_floor'=>'`hvf`',C('DB_PREFIX').'house_village_floor_type'=>'`hvft`');
        $condition_where = '`hvft`.`status`=1 AND `hvf`.`status`=1 AND `hvf`.`floor_type` = `hvft`.`id` AND `hvf`.`floor_id` = '.$floor_id;
        $condition_field = array('`hvf`.*,`hvft`.`name`');
        $result = D('')->where($condition_where)->table($condition_table)->field($condition_field)->find();
        return $result;
    }
	
	
	//查询小区单元 - wangdong
	public function get_unit_list($village_id){
		
		$condition['village_id'] = $village_id;
		
		$condition['status'] = 1;
		
		$unit_list = $this->where($condition)->order('sort desc, floor_id desc')->select();
		
		return $unit_list;
			
	}
	
	//查询单元信息
	public function get_unit_find($floor_id , $field=""){
		
		if(empty($field)) $field=true;
		
		$condition['floor_id'] = $floor_id;

		$info = $this->field($field)->where($condition)->find();
		
		return $info;
			
	}
	
}

