<?php
class House_village_user_vacancyModel extends Model{
    public function house_village_user_vacancy_page_list($where, $fields = true ,$order = 'status desc,application_time desc,sort desc, pigcms_id desc' , $pageSize = 20,$property_id=0){
        if(!$where){
            return false;
        }
        if(!isset($where['is_del'])) {
            $where['is_del']  =0;
        }

        if  ($pageSize>0) {
            import('@.ORG.merchant_page');
            $count = $this->where($where)->count();
            $p = new Page($count,$pageSize,'page');
            $vacancy_list = $this->where($where)->field($fields)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
        } else {
            $vacancy_list = $this->where($where)->field($fields)->order($order)->select();
        }


        $database_house_village_floor = D('House_village_floor');
        $database_house_village_floor_type = D('House_village_floor_type');

        if (isset($where['village_id'])&&$where['village_id']) {
            $village_id = $where['village_id'];
        } else {
            $village_id = $_SESSION['house']['village_id'] ? $_SESSION['house']['village_id'] : $_GET['village_id'] + 0;
        }
        $floor_list = $database_house_village_floor->where(array('village_id'=>$village_id,'status'=>1))->getField('floor_id,floor_name,floor_layer,floor_type,single_id');
        $floor_type_list = $database_house_village_floor_type->where(array('village_id'=>$village_id,'status'=>1))->getField('id,name');
        // 业主属性
        $attribute_list = D('House_village_user_attribute')->get_attribute($where['village_id'],true);


        $single_id_arr =  $this->where($where)->order($order)->limit($p->firstRow.','.$p->listRows)->getField('single_id', true);
        if ($single_id_arr) {
            $where_single = [
                'village_id' => $village_id,
                'id' => ['in', $single_id_arr]
            ];
            $sing_arr = M('House_village_single')->where($where_single)->getField('id,single_name');
            $where_layer = [
                'village_id' => $village_id,
                'single_id' => ['in', $single_id_arr]
            ];
            $layer_arr = M('House_village_layer')->where($where_layer)->getField('id,layer_name');
        } else {
            $sing_arr = [];
            $layer_arr = [];
        }
        if($property_id){
            $is_new_charge=D('House_village')->checkNewCharge($property_id);
        }else{
            $is_new_charge = 0;
        }
        foreach($vacancy_list as $Key=>$row){
            if($is_new_charge){
                $serviceTimeInfo = $this->getRoomEndTime($row['pigcms_id'],$village_id,$property_id);
                if ($serviceTimeInfo['propertyStartTime']>1){
                    $row['service_start_time'] = date('Y-m-d',$serviceTimeInfo['propertyStartTime']);
                }else{
                    $row['service_start_time'] = '未设置';
                }
                if ($serviceTimeInfo['propertyEndTime']>1){
                    $row['service_end_time'] = date('Y-m-d',$serviceTimeInfo['propertyEndTime']);
                }else{
                    $row['service_end_time'] = '未设置';
                }
                $row['service_cycle'] = $row['service_start_time'].' - '.$row['service_end_time'];
            }
            $single = M('House_village_single')->where(array('id'=>$row['single_id']))->find();
            $row['floor_name'] = $floor_list[$row['floor_id']]['floor_name'];
            if ($row['single_id'] && $sing_arr[$row['single_id']]) {
                $row['floor_layer'] = $sing_arr[$row['single_id']];
            } else {
                $row['floor_layer'] = $floor_list[$row['floor_id']]['floor_layer'];
            }
            if ($row['layer_id'] && $layer_arr[$row['layer_id']]) {
                $row['layer'] = $layer_arr[$row['layer_id']];
            }
            $row['floor_type_name'] = $floor_type_list[$floor_list[$row['floor_id']]['floor_type']];
//            $row['address'] = $row['floor_name'].$row['floor_layer'].$row['layer'].$row['room'];
            $row['attribute_name'] = $attribute_list[$row['attribute']] ? $attribute_list[$row['attribute']] : '' ;
            // 处理地址

            $word_data = array();
            $sing_name = $single['single_name']?$single['single_name']:$row['floor_layer'];
            $word_data['floor_name'] = $row['floor_name'];
            $word_data['layer'] = $row['layer'];
            $word_data['room'] = $row['room'];
            $word_data['single_name'] = $sing_name;
            $room_diy_name = D('House_village')->word_replce_msg($word_data,$village_id);
            $row['address'] = $room_diy_name;
            $row['floor_layer'] = $row['floor_layer']?$row['floor_layer']:$single['single_name'];

            $vacancy_list[$Key] = $row;
        }
        if  ($pageSize>0) {
            $list['pageCount'] = ceil($count / $pageSize);
            $list['pagebar'] = $p->show();
        }
        $list['list'] = $vacancy_list;
        $list['new_charge_time'] = $is_new_charge;
        if($list){
            return array('status'=>1,'result'=>$list);
        }else{
            return array('status'=>0,'result'=>$list);
        }
    }


	//获取指定单元的房间列表 - wangdong
	public function house_village_user_vacancy_page_list_room($where, $fields = true ,$order = 'sort desc, pigcms_id desc' , $pageSize = 20){
        if(!$where){
            return false;
        }
        if(!isset($where['is_del'])) {
            $where['is_del']  =0;
        }
        if (!isset($where['status'])) {
            $where['status'] = array('not in',[0]);
        }
        import('@.ORG.merchant_page');
        $count = $this->where($where)->count();
        $p = new Page($count,$pageSize,'page');

        $vacancy_list_arr = $this->where($where)->field($fields)->order($order)->limit($p->firstRow.','.$p->listRows)->select();

		foreach($vacancy_list_arr as $k=>$v){
			if($v['uid']==0 && $v['name']!='' && $v['phone']!=''){

			}else{
			$vacancy_list[]=$v;
			}
		}


        $list['list'] = $vacancy_list_arr;
        $list['pagebar'] = $p->show();
        if($list){
            return array('status'=>1,'result'=>$list);
        }else{
            return array('status'=>0,'result'=>$list);
        }
    }


    //获取指定单元的房间列表 - wangdong
    public function house_village_user_vacancy_page_list_room_new($where, $fields = true ,$order = 'sort desc, pigcms_id desc' , $pageSize = 20){
        if(!$where){
            return false;
        }
        if(!isset($where['is_del'])) {
            $where['is_del']  =0;
        }

        $where['status'] = array('not in',[0]);
//        import('@.ORG.merchant_page');
//        $count = $this->where($where)->count();
//        $p = new Page($count,$pageSize,'page');

        $vacancy_list_arr = $this->where($where)->field($fields)->order($order)->select();

        foreach($vacancy_list_arr as $k=>$v){
            if($v['uid']==0 && $v['name']!='' && $v['phone']!=''){

            }else{
                $vacancy_list[]=$v;
            }
        }


        $list['list'] = $vacancy_list_arr;
        $list['pagebar'] = 0;
        if($list){
            return array('status'=>1,'result'=>$list);
        }else{
            return array('status'=>0,'result'=>$list);
        }
    }



    public function import_village_del($where){
        if(!$where){
            return false;
        }

        //验证是否绑定业主
        $list = $this->house_village_user_vacancy_page_list($where,'pigcms_id,type,village_id','',0);
        if(isset($list['result'])){
            $list=$list['result'];
        }
        if ($list) {
            $flag = false;
            foreach ($list as $key => $value) {
                $yz_bind_num = D('House_village_user_bind')->where(array('vacancy_id'=>$value['pigcms_id'],'type'=>array('in',array(0,1,2,3)),'status'=>array('in',array(1,2))))->count();
                if ($yz_bind_num) {
                    $flag = true;
                    break;
                }
            }
            if ($flag) {
                return array('status'=>0,'msg'=>'已绑定住户，无法删除！');
            }
        }else{
            return array('status'=>0,'msg'=>'房间不存在！');
        }
        $db_house_new_pay_order=M('House_new_pay_order');
        foreach ($list as $value) {
            $set = [
                'is_del' => 1,
                'del_time' => time()
            ];
            $set['usernum'] = array('exp', "concat(`usernum`,'del{$value['pigcms_id']}')");
            $insert_id = $this->where($where)->save($set);
            $whereArr=array();
            $whereArr['village_id']=$value['village_id'];
            $whereArr['room_id']=$value['pigcms_id'];
            $whereArr['is_paid']=2;
            $whereArr['is_discard']=1;
            $db_house_new_pay_order->where($whereArr)->data(['is_discard' => 2, 'discard_reason' => '房间被删除了', 'update_time' => time()])->save();
        }
        if($insert_id){
            return array('status'=>1,'msg'=>'删除成功！');
        }else{
            return array('status'=>0,'msg'=>'删除失败！');
        }
    }


    public function house_village_user_vacancy_detail($where,$fields = true){
        if(!$where){
            return false;
        }

        $detail = $this->where($where)->field($fields)->find();
        if($detail['type'] == 0){
            $detail['relation_val'] = C('config.open_house_user_name')?'房主&社长':'房主';
        }else if($detail['type'] == 1){
            $detail['relation_val'] = C('config.open_house_user_name')?'亲友':'家属';
        }else if($detail['type'] == 2){
            $detail['relation_val'] = C('config.open_house_user_name')?'租客&舍友':'租客';
        }else{
            $detail['relation_val'] = '未知';
        }

        $floor_info = D('House_village_floor')->where(array('floor_id'=>$detail['floor_id'],'status'=>1,'village_id'=>$_SESSION['house']['village_id']))->getField('floor_id,floor_name,floor_layer,floor_type,single_id');
        $single = M('House_village_single')->where(array('id'=>$floor_info[$detail['floor_id']]['single_id']))->find();
        $detail['floor_name'] = $floor_info[$detail['floor_id']]['floor_name'];
        $detail['layer_name'] = $single['single_name'];
        $database_house_village_floor_type = D('House_village_floor_type');
        $floor_type_name = $database_house_village_floor_type->where(array('id'=>$floor_info[$detail['floor_id']]['floor_type'],'status'=>1))->getField('name');
        $detail['floor_type_name'] = $floor_type_name;

        $where_layer = [
            'village_id' => $detail['village_id'],
            'id' => $detail['layer_id']
        ];
        $layer = M('House_village_layer')->where($where_layer)->getField('layer_name');
        if ($layer) {
            $detail['layer'] = $layer;
        }
        $word_data = array();
        $sing_name = $single['single_name'] ? $single['single_name'] : $floor_info[$detail['floor_id']]['floor_layer'];
        $word_data['floor_name'] = $floor_info[$detail['floor_id']]['floor_name'];
        $word_data['layer'] = $detail['layer'];
        $word_data['room'] = $detail['room'];
        $word_data['single_name'] = $sing_name;
        $room_diy_name = D('House_village')->word_replce_msg($word_data,$_SESSION['house']['village_id']);
        $detail['address'] = $room_diy_name;

        if($detail){
            return array('status'=>1,'detail'=>$detail);
        }else{
            return array('status'=>0,'detail'=>$detail);
        }
    }


    public function house_village_user_vacancy_edit($where,$data){
        if(!$where || !$data){
            return false;
        }

        $condition['usernum'] = $data['usernum'];
        /* $info = $this->where($condition)->find();*/
        //if(!$info){
            $database_house_village_user_bind = D('House_village_user_bind');
            $info = $database_house_village_user_bind->where($condition)->find();
            if(!$info){
                $database_house_village_floor = D('House_village_floor');
                $_condition['floor_name'] = $data['floor_name'];
                $_condition['floor_layer'] = $data['floor_layer'];

                $info = $database_house_village_floor->where($_condition)->find();

                if(!$info){
                    return array('status'=>0,'msg'=>'单元不存在！');
                }else{
                    $data['floor_id'] = $_where['floor_id'] = $info['floor_id'];
                    $_where['layer'] = $data['layer'];
                    $_where['room'] = $data['room'];

                    $vacancy_info = $this->where($_where)->find();
                    if($vacancy_info){
                        return array('status'=>0,'msg'=>'该房间已存在！');
                    }

                    $insert_id = $this->where($where)->data($data)->save();
                    if($insert_id){
                        return array('status'=>1,'msg'=>'修改成功！');
                    }else{
                        return array('status'=>0,'msg'=>'修改失败！');
                    }
                }
            }else{
                return array('status'=>0,'msg'=>'物业编号已存在！');
            }
        /*}else{
            return array('status'=>0,'msg'=>'物业编号已存在！');
        }*/
    }

	//查询和保存房间信息 - wangdong
	 public function house_village_user_vacancy_edit_find($where,$data){
        if(!$where || !$data){
            return false;
        }
        $condition['pigcms_id'] = $where['pigcms_id'];
		$room = $this->where($condition)->find();

		if($room){
			$database_house_village_floor = D('House_village_floor');
            $database_house_village_layer = D('House_village_layer');
			$_condition['floor_name'] = $data['floor_name'];
//            $_condition['floor_layer'] = $data['floor_layer'];
            if ($where['village_id']) {
                $_condition['village_id'] = $where['village_id'];
            }
            if ($room['floor_id']) {
                $_condition['floor_id'] = $room['floor_id'];
            }

			$info = $database_house_village_floor->where($_condition)->find();

			if(!$info){
				return array('status'=>0,'msg'=>'单元不存在！');
			}else{
				$data['floor_id'] = $_where['floor_id'] = $info['floor_id'];
				$_where['pigcms_id'] = $where['pigcms_id'];

                $vacancy_info = $room;
				if(!$vacancy_info){
					return array('status'=>0,'msg'=>'房间不存在！');
				}

                if ($data['room']!=$room['room']) {
                    // 房屋好进行了变动 查询下当前楼层下是否有重复房屋号
                    $where_vacancy = [];
                    $where_vacancy['room'] = $data['room'];
                    $where_vacancy['single_id'] = $room['single_id'];
                    $where_vacancy['floor_id'] = $room['floor_id'];
                    $where_vacancy['layer_id'] = $room['layer_id'];
                    $where_vacancy['village_id'] = $where['village_id'];
                    $where_vacancy['is_del'] = 0;
                    $house_village_user_vacancy = $this->where($where_vacancy)->find();
                    if (!empty($house_village_user_vacancy)) {
                        return array('status'=>0,'msg'=>'当前房屋已经存在 请确认后再进行修改！');
                    }
                }

				$data_info['usernum']   =  $data['usernum'];
				$data_info['layer']     =  $data['layer'];
				$data_info['room']      =  $data['room'];
				$data_info['sort']      =  $data['sort'];
				$data_info['house_type']      =  $data['house_type'];
				$data_info['user_status']      =  $data['user_status'];
				$data_info['sell_status']      =  $data['sell_status'];
                $data_info['contract_time_start']      = $data['contract_time_start']?$data['contract_time_start']:1;
                $data_info['contract_time_end']      =  $data['contract_time_end']?$data['contract_time_end']:1;

                $where_vacancy_bind = [
                    'vacancy_id' => $vacancy_info['pigcms_id'],
                    'type' => ['in', [0,3]],
                    'status' => ['not in', [3,4]]
                ];
                if ($where_vacancy_bind['uid']) {
                    $where_vacancy_bind['uid'] = $vacancy_info['uid'];
                } elseif ($where_vacancy_bind['phone']) {
                    $where_vacancy_bind['phone'] = $vacancy_info['phone'];
                }
                if ($where_vacancy_bind) {
                    $bind = M('House_village_user_bind')->where($where_vacancy_bind)->find();
                } else {
                    $bind = 0;
                }
                if (intval($vacancy_info['status'])==0 && $vacancy_info['uid'] && intval($data['status']) == 1) {
                    if ($bind && $bind['status'] == 1) {
                        $data_info['status'] = 3;
                    } elseif ($bind && $bind['status'] == 2) {
                        $data_info['status'] = 2;
                    } else {
                        $data_info['status']  =  1;
                    }
                } elseif (intval($data['status']) == 0 || intval($vacancy_info['status']) == 0) {
                    $data_info['status']  =  $data['status'];
                    if (intval($vacancy_info['status']) == 1) {
                        if ($bind && $bind['status'] == 1) {
                            $data_info['status'] = 3;
                        } elseif ($bind && $bind['status'] == 2) {
                            $data_info['status'] = 2;
                        }
                    }
                } else {
                    $data_info['status'] =  $vacancy_info['status'];
                    if (intval($vacancy_info['status']) == 1) {
                        if ($bind && $bind['status'] == 1) {
                            $data_info['status'] = 3;
                        } elseif ($bind && $bind['status'] == 2) {
                            $data_info['status'] = 2;
                        } else {
                            $data_info['status']  =  1;
                        }
                    }
                }

				$data_info['floor_id']  =  $data['floor_id'];
                $data_info['housesize']  =  $data['housesize'];
                $data_info['house_type']      =  $data['house_type'];
                $data_info['user_status']      =  $data['user_status'];
                $data_info['sell_status']      =  $data['sell_status'];
				$data_info['add_time']  =  time();


                if (isset($data['room_number']) && trim($data['room_number'])) {
                    $check_number = "/^[0-9]+$/";
                    $room_number = trim($data['room_number']);
                    if(!preg_match($check_number, $room_number)){
                        return array('status'=>0,'msg'=>'房屋编号只允许数字！');
                    }
                    if(intval($room_number)<=0 || intval($room_number)>9999){
                        return array('status'=>0,'msg'=>'房屋编号必须为1-9999的数字！');
                    }
                    if (strlen($room_number)>4) {
                        return array('status'=>0,'msg'=>'房屋编号只允最多4位数字！');
                    } elseif (strlen($room_number)<2) {
                        // 不足2位 补足2位
                        $room_number = str_pad($room_number,2,"0",STR_PAD_LEFT);
                    }
                    $data_info['room_number'] = $room_number;
                }
                if (!$data['room_number'] && $data['room']) {
                    $pattern_number = "/[^0-9]/";
                    $room_number = preg_replace($pattern_number,'',$data['room']);
                    // 房屋编号 暂时同一个小区下仅限1-99数字编号
                    if ($room_number) {
                        if (strlen($room_number)>2) {
                            // 超过2位 截取后2位
                            $room_number = substr($room_number, -2,2);
                        } elseif (strlen($room_number)<2) {
                            // 不足2位 补足2位
                            $room_number = str_pad($room_number,2,"0",STR_PAD_LEFT);
                        }
                        $data_info['room_number'] = $room_number;
                    }
                }
                if ($data['room_number']) {
                    $where_repeat = [
                        'village_id' => $where['village_id'],
                        'single_id' => $room['single_id'],
                        'floor_id' => $room['floor_id'],
                        'layer_id' => $room['layer_id'],
                        'room_number' => $data['room_number'],
                        'status' => ['neq', 4],
                        'is_del'=>0
                    ];
                    if (strlen($data['room_number'])>2) {
                        // 如果大于2位  考虑 同单元房屋编号
                        //unset($where_repeat['layer_id']);
                    }
                    $repeat_id = $this->where($where_repeat)->getField('pigcms_id');
                    if ($repeat_id && $repeat_id!=$room['pigcms_id']) {
                        return array('status'=>0,'msg'=>'该房屋编号已经存在，请填写1-9999的楼层编号！');
                    }
                    $room_number_str=intval($data['room_number']);
                    if($room_number_str < 100){
                        $room_number_str = str_pad($room_number_str, 2, "0", STR_PAD_LEFT);
                        $layer_number=intval($database_house_village_layer->where(['id'=>$room['layer_id']])->getField('layer_number'));
                        $layer_number = str_pad($layer_number, 2, "0", STR_PAD_LEFT);
                        $data_info['room_number'] = $layer_number.$room_number_str;
                    }

                } else {
                    return array('status'=>0,'msg'=>'请填写1-99的不重复房屋编号！');
                }
                if(isset($data['room_alias_id'])){
                    $data_info['room_alias_id']=$data['room_alias_id'];
                }
                if(isset($data['bind_people_num'])){
                    $data_info['bind_people_num']=$data['bind_people_num'];
                }
				$insert_id = $this->where($where)->data($data_info)->save();
				if($insert_id){
				    // 修改了房屋面积，且在绑定表格中存在对应的数据，同步更改其中的房屋面积 (更改对象包括房主，家属，租客及更新房主)
				    if ($data['housesize']) {
                        $bind_data_info = [];
                        $bind_data_info['housesize'] = $data['housesize'];
                        M('House_village_user_bind')->where(array('vacancy_id' => $room['pigcms_id']))->data($bind_data_info)->save();
                        $house_new_charge_standard_bindDb=M('House_new_charge_standard_bind');
                        $standard_bindArr=array('rb.vacancy_id'=>$room['pigcms_id'],'rb.village_id'=>$room['village_id']);
                        $standard_bindArr['rb.floor_id']=$room['floor_id'];
                        $standard_bindArr['rb.layer_id']=$room['layer_id'];
                        $standard_bindArr['rb.is_del']=1;
                        $standard_bindArr['rb.bind_type']=1;
                        //$standard_bindArr['rb.custom_value']=array('gt',1);
                        $standard_bindArr['rr.fees_type']=2;
                        $standard_bindArr['_string']='(rr.unit_gage="" OR rr.unit_gage IS NULL)';

                        $standard_bind=$house_new_charge_standard_bindDb->join('as rb left join ' . C('DB_PREFIX') . 'house_new_charge_rule rr ON rb.rule_id = rr.id')->where($standard_bindArr)->field('rb.id,rb.custom_value')->select();
                        if(!empty($standard_bind)){
                            foreach ($standard_bind as $vv){
                                $saveArr=array('custom_value'=>$data['housesize']);
                                $house_new_charge_standard_bindDb->where(array('id'=>$vv['id']))->data($saveArr)->save();
                            }
                        }
                    }
                    // 修改成功且修改了单元编号触发编号其单元下房屋编号和对应房屋人员编号变动
                    if ($data_info['room_number']) {
                        $change_param = [
                            'village_id' => $where['village_id'],
                            'room_id' => $room['pigcms_id'],
                            'room_number' => $data['room_number'],
                        ];
                        $this->change_number($change_param);
                    }
					return array('status'=>1,'msg'=>'修改成功！');
				}else{
					return array('status'=>0,'msg'=>'修改失败！');
				}
			}
		}else{
			return array('status'=>0,'msg'=>'房间不存在！');
		}

    }


	//获取指定房间信息 - wangdong
	public function get_find_room_info($pigcms_id,$field=""){

		if(empty($field)) $field=true;

		$condition['pigcms_id'] = $pigcms_id;

		$room_info = $this->field($field)->where($condition)->find();

		return $room_info;

	}

    public function get_one_village_user_vacancy($whereArr=array(),$field="*"){
        $room_info = $this->where($whereArr)->field($field)->find();
        return $room_info;
    }
	// 获取房间是否绑定业主 - wangdong
	public function get_find_room_count($pigcms_id){

		if(empty($pigcms_id)){
			return false;
		}
        $where_owner = [
            'vacancy_id' => $pigcms_id,
            'type' => ['in',[0,3]],
            'status' => 1
        ];
        $room_true_find = D('House_village_user_bind')->where($where_owner)->find();
        if ($room_true_find && !$room_true_find['uid'] && !$room_true_find['phone'] && !$room_true_find['name']) {
            $room_true_find = [];
        }

		return $room_true_find;
	}

	//查询用户所在小区的所有房主
    public function get_my_village_lists($uid , $field="",$type='uid',$village_id = 0){

		$condition[$type] = $uid;

		$condition['type'] = 0;
        $condition['status'] = array('neq',0);
        $condition['status'] = array('neq',1);
        if($village_id) {
            $condition['village_id'] = array('in',$village_id);
        }
        $where_owner = [
            'vacancy_id' => ['gt', 0],
            'uid' => $uid,
            'type' => ['in',[0,3]],
            'status' => 1
        ];
        $vacancy_id_arr = D('House_village_user_bind')->where($where_owner)->getField('vacancy_id',true);
        if ($vacancy_id_arr) {
            $condition['pigcms_id'] = ['in', $vacancy_id_arr];
        } else {
            return [];
        }

//		$condition['is_del'] = 0;

		if(empty($field)) $field = true;
        if(!isset($condition['is_del'])) {
            $condition['is_del']  =0;
        }

		$lists = $this->field($field)->where($condition)->order('village_id DESC,floor_id Asc,pigcms_id ASC')->select();
        //var_dump(D('')->_sql());
		return $lists;

	}

    /**
     * [get_count 获取数量]
     * @return [type] [description]
     */
    public function get_user_vacancy_count($where,$field=''){
        if(!isset($where['is_del'])) {
            $where['is_del']  =0;
        }
        $count = D('House_village_user_vacancy')->where($where)->count($field);
        return $count;
    }

    public function house_village_user_vacancy_lists($where, $fields = true ,$order = 'pigcms_id desc' , $pageSize = 20){

        if(!isset($where['is_del'])) {
            $where['is_del']  =0;
        }
        import('@.ORG.merchant_page');
        $count = $this->where($where)->count();
        $p = new Page($count,$pageSize,'page');

        $vacancy_list = $this->where($where)->field($fields)->order($order)->limit($p->firstRow.','.$p->listRows)->order($order)->select();
        $database_house_village_floor = D('House_village_floor');
        $database_house_village_floor_type = D('House_village_floor_type');

        $village_id = $where['village_id'];
        $floor_list = $database_house_village_floor->where(array('village_id'=>$village_id,'status'=>1))->getField('floor_id,floor_name,floor_layer,floor_type');
        $floor_type_list = $database_house_village_floor_type->where(array('village_id'=>$village_id,'status'=>1))->getField('id,name');
        foreach($vacancy_list as $Key=>$row){
            $row['floor_name'] = $floor_list[$row['floor_id']]['floor_name'];
            $row['floor_layer'] = $floor_list[$row['floor_id']]['floor_layer'];
            $row['floor_type_name'] = $floor_type_list[$floor_list[$row['floor_id']]['floor_type']];
            $row['room_addrss'] = $row['room'];
            $row['address'] = $row['floor_layer'].$row['floor_name'].$row['room'];
            $vacancy_list[$Key] = $row;
        }
        
        $return['totalPage'] = ceil($count / $pageSize);
        $return['user_list'] = $vacancy_list;
        $return['pagebar'] = $p->show();
        
        return $return;
    }


    public function get_all_user_vacancy($where , $fields = true , $order = 'v.pigcms_id desc',$pageSize = 20)
    {
        import('@.ORG.merchant_page');
        if(!isset($where['v.is_del'])) {
            $where['v.is_del']  =0;
        }
        $count = $this->alias('v')->where($where)->count();
        $p = new Page($count, $pageSize, 'page');
        $house_village_proerty_list = M('House_village_user_vacancy')
            ->join('as v left join ' . C('DB_PREFIX') . 'house_village_single s ON s.id = v.single_id')
            ->join(C('DB_PREFIX') . 'house_village_floor f ON f.floor_id = v.floor_id')
            ->join(C('DB_PREFIX') . 'house_village_layer l ON l.id = v.layer_id')
            ->join(C('DB_PREFIX') . 'house_village_property_standard_bind b ON b.pigcms_id = v.pigcms_id AND b.is_del = 1')
            ->where($where)
            ->field($fields)
            ->order($order)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        fdump(M('House_village_user_vacancy')->getLastSql());
        $list['list'] = $house_village_proerty_list;
        $list['pagebar'] = $p->show();
        if ($list) {
            return array('status' => 1, 'list' => $list);
        } else {
            return array('status' => 0, 'list' => $list);
        }
    }

    public function house_village_user_vacancy_lists_new($where, $fields = true ,$order = 'pigcms_id desc' , $pageSize = 20){

        if(!isset($where['is_del'])) {
            $where['is_del']  =0;
        }
        import('@.ORG.merchant_page');
        $count = $this->where($where)->count();
        $p = new Page($count,$pageSize,'page');

        $vacancy_list = $this->where($where)->field($fields)->order($order)->limit($p->firstRow.','.$p->listRows)->order($order)->select();
        $database_house_village_floor = D('House_village_floor');
        $database_house_village_floor_type = D('House_village_floor_type');

        $village_id = $where['village_id'];
        $floor_list = $database_house_village_floor->where(array('village_id'=>$village_id,'status'=>1))->getField('floor_id,floor_name,floor_layer,floor_type');
        $floor_type_list = $database_house_village_floor_type->where(array('village_id'=>$village_id,'status'=>1))->getField('id,name');
        $single = M('House_village_single')->where(array('village_id'=>$village_id))->getField('id,single_name');
        $layer = M('House_village_layer')->where(array('village_id'=>$village_id))->getField('id,layer_name');
        foreach($vacancy_list as $Key=>$row){
            $row['floor_name'] = $floor_list[$row['floor_id']]['floor_name'];
            $row['floor_layer'] = $layer[$row['id']]['layer_name'];
            $row['floor_type_name'] = $floor_type_list[$floor_list[$row['floor_id']]['floor_type']];
            $row['room_addrss'] = $row['room'];
//            $row['address'] = $single[$row['single_id']].(D('House_village')->getHouseAlias('single_name')?:'栋').$floor_list[$row['floor_id']]['floor_name'].(D('House_village')->getHouseAlias('floor_name')?:'单元').$layer[$row['layer_id']].'层'.$row['room'];
            $row['address'] = D('House_village')->get_single_floor_room($row['single_id'],$row['floor_id'],$row['layer_id'],$row['pigcms_id'],$row['village_id']);
            $vacancy_list[$Key] = $row;
        }

        $return['totalPage'] = ceil($count / $pageSize);
        $return['user_list'] = $vacancy_list;
        $return['pagebar'] = $p->show();

        return $return;
    }
    /**
     * Notes:
     * @param $change_param 修改编号的关键值
     * {
     *     'room_id' => '1', // 修改房屋编号的 房屋id 即房屋表格中pigcms_id
     *      'property_number' => '01020101', // 修改后的房屋的物业编号
     * }
     * {
     *     'layer_id' => '1', // 修改楼层编号的 楼层id 即楼层表格中id
     *      'layer_number' => '01', // 修改后的楼层编号
     * }
     * {
     *     'floor_id' => '1', // 修改单元编号的 单元id 即单元表格中floor_id
     *      'floor_number' => '01', // 修改后的单元编号
     * }
     * {
     *     'single_id' => '1', // 修改楼栋编号的 楼栋id 即楼栋表格中id
     *      'single_number' => '01', // 修改后的楼栋编号
     * }
     * @return bool
     * @author: wanzy
     * @date_time: 2020/12/12 14:12
     */
    public function change_number($change_param) {
        if (empty($change_param)) {
            return false;
        }
        fdump_api(['更改编号传参>>'.__LINE__, $change_param],'house/change_number_log',1);
        if ($change_param['single_id'] && $change_param['single_number']) {
            if ($change_param['village_id']) {
                $village_id = $change_param['village_id'];
            } else {
                $where_single = [
                    'id' => $change_param['single_id'],
                ];
                $village_id = M('House_village_single')->where($where_single)->getField('village_id');
            }

            $database_house_village_config = D('House_village_config');
            $village_config = $database_house_village_config->where(array('village_id'=>$village_id))->find();
            $village_single_support_digit = intval($village_config['village_single_support_digit']) ? intval($village_config['village_single_support_digit']) : 2;
            if (!$village_single_support_digit || $village_single_support_digit!=3) {
                $village_single_support_digit = 2;
            }
            // 更改了楼栋编号
            if (strlen($change_param['single_number'])>$village_single_support_digit) {
                fdump_api(['更改楼栋编号错误>>'.__LINE__, $change_param, $village_single_support_digit],'house/change_number_err_log',1);
                return false;
            }
            $db_house_village_user_vacancy = M('House_village_user_vacancy');
            $where_vacancy = [
                'a.single_id' => $change_param['single_id'],
                'a.layer_id' => ['gt', 0],
                'a.floor_id' => ['gt', 0],
                '_string' => "a.room_number<>'' AND b.floor_number<>'' AND c.layer_number<>''",
            ];
            $field_single_vacancy = 'a.pigcms_id, a.property_number, a.room_number, a.single_id, a.floor_id, a.layer_id, b.floor_number, c.layer_number';
            $vacancy_list = $db_house_village_user_vacancy
                ->join(' AS a LEFT JOIN ' .C('DB_PREFIX') . 'house_village_floor AS b ON b.floor_id = a.floor_id')
                ->join(' LEFT JOIN ' .C('DB_PREFIX') . 'house_village_layer AS c ON c.id = a.layer_id')
                ->field($field_single_vacancy)
                ->where($where_vacancy)
                ->select();
            if (empty($vacancy_list)) {
                fdump_api(['更改楼栋编号无数据>>'.__LINE__, $change_param, $db_house_village_user_vacancy->_sql(), $db_house_village_user_vacancy->getDbError()],'house/change_number_err_log',1);
                return false;
            }
            foreach ($vacancy_list as $val) {
                if ($val['room_number'] && $val['floor_number'] && $val['layer_number']) {
                    if (strlen($change_param['single_number'])<$village_single_support_digit) {
                        // 不足2位 补足2位
                        $change_param['single_number'] = str_pad($change_param['single_number'],$village_single_support_digit,"0",STR_PAD_LEFT);
                    }
                    if (strlen($val['floor_number'])>2) {
                        fdump_api(['更改编号-单元编号错误>>'.__LINE__, $change_param, $val],'house/change_number_err_log',1);
                        continue;
                    } elseif (strlen($val['floor_number'])<2) {
                        // 不足2位 补足2位
                        $val['floor_number'] = str_pad($val['floor_number'],2,"0",STR_PAD_LEFT);
                    }
                    if (strlen($val['layer_number'])>2) {
                        fdump_api(['更改编号-楼层编号错误>>'.__LINE__, $change_param, $val],'house/change_number_err_log',1);
                        continue;
                    } elseif (strlen($val['layer_number'])<2) {
                        // 不足2位 补足2位
                        $val['layer_number'] = str_pad($val['layer_number'],2,"0",STR_PAD_LEFT);
                    }
                    if (strlen($val['room_number'])>4) {
                        fdump_api(['更改编号-房屋编号错误>>'.__LINE__, $change_param, $val],'house/change_number_err_log',1);
                        continue;
                    } elseif (strlen($val['room_number'])<=2) {
                        // 不足2位 补足4位
                        $val['room_number'] = str_pad($val['room_number'],2,"0",STR_PAD_LEFT);
                        $val['room_number'] = $val['layer_number'] . $val['room_number'];
                    } elseif (strlen($val['room_number'])<=4) {
                        // 不足4位 补足4位
                        $val['room_number'] = str_pad($val['room_number'],4,"0",STR_PAD_LEFT);
                    }
                    $property_number = $change_param['single_number'].$val['floor_number'].$val['layer_number'].$val['room_number'];
                    $this->change_room_property_number($val['pigcms_id'], $property_number, true);
                }
            }
        } elseif ($change_param['floor_id'] && $change_param['floor_number']) {
            if ($change_param['village_id']) {
                $village_id = $change_param['village_id'];
            } else {
                $where_floor = [
                    'floor_id' => $change_param['floor_id'],
                ];
                $village_id = M('House_village_floor')->where($where_floor)->getField('village_id');
            }
            $database_house_village_config = D('House_village_config');
            $village_config = $database_house_village_config->where(array('village_id'=>$village_id))->find();
            $village_single_support_digit = intval($village_config['village_single_support_digit']) ? intval($village_config['village_single_support_digit']) : 2;
            if (!$village_single_support_digit || $village_single_support_digit!=3) {
                $village_single_support_digit = 2;
            }
            // 更改了单元编号
            if (strlen($change_param['floor_number'])>2) {
                fdump_api(['更改编号-单元编号错误>>'.__LINE__, $change_param],'house/change_number_err_log',1);
                return false;
            }
            $db_house_village_user_vacancy = M('House_village_user_vacancy');
            $where_vacancy = [
                'a.floor_id' => $change_param['floor_id'],
                'a.single_id' => ['gt', 0],
                'a.layer_id' => ['gt', 0],
                '_string' => "a.room_number<>'' AND b.single_number<>'' AND c.layer_number<>''",
            ];
            $field_floor_vacancy = 'a.pigcms_id, a.property_number, a.room_number, a.single_id, a.floor_id, a.layer_id, b.single_number, c.layer_number';
            $vacancy_list = $db_house_village_user_vacancy
                ->join(' AS a LEFT JOIN ' .C('DB_PREFIX') . 'house_village_single AS b ON b.id = a.single_id')
                ->join(' LEFT JOIN ' .C('DB_PREFIX') . 'house_village_layer AS c ON c.id = a.layer_id')
                ->field($field_floor_vacancy)
                ->where($where_vacancy)
                ->select();
            if (empty($vacancy_list)) {
                fdump_api(['更改编号-单元编号无数据>>'.__LINE__, $change_param,$db_house_village_user_vacancy->_sql(),$db_house_village_user_vacancy->getDbError()],'house/change_number_err_log',1);
                return false;
            }
            foreach ($vacancy_list as $val) {
                if ($val['room_number'] && $val['single_number'] && $val['layer_number']) {
                    if (strlen($val['single_number'])>$village_single_support_digit) {
                        fdump_api(['更改编号-楼栋编号错误>>'.__LINE__, $change_param,$val,$village_single_support_digit],'house/change_number_err_log',1);
                        continue;
                    } elseif (strlen($val['single_number'])<$village_single_support_digit) {
                        // 不足2位 补足2位
                        $val['single_number'] = str_pad($val['single_number'],$village_single_support_digit,"0",STR_PAD_LEFT);
                    }
                    if (strlen($change_param['floor_number'])<2) {
                        // 不足2位 补足2位
                        $change_param['floor_number'] = str_pad($change_param['floor_number'],2,"0",STR_PAD_LEFT);
                    }
                    if (strlen($val['layer_number'])>2) {
                        fdump_api(['更改编号-楼层编号错误>>'.__LINE__, $change_param,$val,$village_single_support_digit],'house/change_number_err_log',1);
                        continue;
                    } elseif (strlen($val['layer_number'])<2) {
                        // 不足2位 补足2位
                        $val['layer_number'] = str_pad($val['layer_number'],2,"0",STR_PAD_LEFT);
                    }
                    if (strlen($val['room_number'])>4) {
                        fdump_api(['更改编号-房屋编号错误>>'.__LINE__, $change_param,$val,$village_single_support_digit],'house/change_number_err_log',1);
                        continue;
                    } elseif (strlen($val['room_number'])<=2) {
                        // 不足2位 补足4位
                        $val['room_number'] = str_pad($val['room_number'],2,"0",STR_PAD_LEFT);
                        $val['room_number'] = $val['layer_number'] . $val['room_number'];
                    } elseif (strlen($val['room_number'])<=4) {
                        // 不足4位 补足4位
                        $val['room_number'] = str_pad($val['room_number'],4,"0",STR_PAD_LEFT);
                    }
                    $property_number = $val['single_number'].$change_param['floor_number'].$val['layer_number'].$val['room_number'];
                    $this->change_room_property_number($val['pigcms_id'], $property_number, true);
                }
            }
        } elseif ($change_param['layer_id'] && $change_param['layer_number']) {
            if ($change_param['village_id']) {
                $village_id = $change_param['village_id'];
            } else {
                $where_layer = [
                    'id' => $change_param['layer_id'],
                ];
                $village_id = M('House_village_layer')->where($where_layer)->getField('village_id');
            }
            $database_house_village_config = D('House_village_config');
            $village_config = $database_house_village_config->where(array('village_id'=>$village_id))->find();
            $village_single_support_digit = intval($village_config['village_single_support_digit']) ? intval($village_config['village_single_support_digit']) : 2;
            if (!$village_single_support_digit || $village_single_support_digit!=3) {
                $village_single_support_digit = 2;
            }
            // 更改了楼层编号
            if (strlen($change_param['layer_number'])>2) {
                fdump_api(['更改编号-楼层编号错误>>'.__LINE__, $change_param,$village_single_support_digit],'house/change_number_err_log',1);
                return false;
            }
            $db_house_village_user_vacancy = M('House_village_user_vacancy');
            $where_vacancy = [
                'a.layer_id' => $change_param['layer_id'],
                'a.single_id' => ['gt', 0],
                'a.floor_id' => ['gt', 0],
                '_string' => "a.room_number<>'' AND b.single_number<>'' AND c.floor_number<>''",
            ];
            $field_layer_vacancy = 'a.pigcms_id, a.property_number, a.room_number, a.single_id, a.floor_id, a.layer_id, b.single_number, c.floor_number';
            $vacancy_list = $db_house_village_user_vacancy
                ->join(' AS a LEFT JOIN ' .C('DB_PREFIX') . 'house_village_single AS b ON b.id = a.single_id')
                ->join(' LEFT JOIN ' .C('DB_PREFIX') . 'house_village_floor AS c ON c.floor_id = a.floor_id')
                ->field($field_layer_vacancy)
                ->where($where_vacancy)
                ->select();
            if (empty($vacancy_list)) {
                fdump_api(['更改编号-楼层编号无数据>>'.__LINE__, $change_param,$db_house_village_user_vacancy->_sql(),$db_house_village_user_vacancy->getDbError()],'house/change_number_err_log',1);
                return false;
            }
            foreach ($vacancy_list as $val) {
                if ($val['room_number'] && $val['single_number'] && $val['floor_number']) {
                    if (strlen($val['single_number'])>$village_single_support_digit) {
                        fdump_api(['更改编号-楼栋编号错误>>'.__LINE__, $change_param,$val,$village_single_support_digit],'house/change_number_err_log',1);
                        continue;
                    } elseif (strlen($val['single_number'])<2) {
                        // 不足2位 补足2位
                        $val['single_number'] = str_pad($val['single_number'],2,"0",STR_PAD_LEFT);
                    }
                    if (strlen($val['floor_number'])>2) {
                        fdump_api(['更改编号-单元编号错误>>'.__LINE__, $change_param,$val,$village_single_support_digit],'house/change_number_err_log',1);
                        continue;
                    } elseif (strlen($val['floor_number'])<2) {
                        // 不足2位 补足2位
                        $val['floor_number'] = str_pad($val['floor_number'],2,"0",STR_PAD_LEFT);
                    }
                    if (strlen($change_param['layer_number'])<2) {
                        // 不足2位 补足2位
                        $change_param['layer_number'] = str_pad($change_param['layer_number'],2,"0",STR_PAD_LEFT);
                    }
                    if (strlen($val['room_number'])>4) {
                        fdump_api(['更改编号-房屋编号错误>>'.__LINE__, $change_param,$val,$village_single_support_digit],'house/change_number_err_log',1);
                        continue;
                    } elseif (strlen($val['room_number'])<=2) {
                        // 不足2位 补足4位
                        $val['room_number'] = str_pad($val['room_number'],2,"0",STR_PAD_LEFT);
                        $val['room_number'] = $val['layer_number'] . $val['room_number'];
                    } elseif (strlen($val['room_number'])<=4) {
                        // 不足4位 补足4位
                        $val['room_number'] = str_pad($val['room_number'],4,"0",STR_PAD_LEFT);
                    }
                    $property_number = $val['single_number'].$val['floor_number'].$change_param['layer_number'].$val['room_number'];
                    $this->change_room_property_number($val['pigcms_id'], $property_number, true);
                }
            }
        } elseif ($change_param['room_id'] && $change_param['property_number']) {
            // 房屋改动 直接传最新改动后的物业编号
            $this->change_room_property_number($change_param['room_id'], $change_param['property_number']);
        } elseif ($change_param['room_id'] && $change_param['room_number']) {
            // 房屋改动 物业编号
            $db_house_village_user_vacancy = M('House_village_user_vacancy');
            $where_vacancy = [
                'a.pigcms_id' => intval($change_param['room_id']),
                'a.single_id' => ['gt', 0],
                'a.floor_id' => ['gt', 0],
                'a.layer_id' => ['gt', 0],
                '_string' => "a.room_number<>'' AND b.single_number<>'' AND c.floor_number<>'' AND d.layer_number<>''",
            ];
            $field_vacancy = 'a.pigcms_id, a.property_number, a.room_number, a.single_id, a.floor_id, a.layer_id, b.single_number, c.floor_number, d.layer_number';
            $vacancy_info = $db_house_village_user_vacancy
                ->join(' AS a LEFT JOIN ' .C('DB_PREFIX') . 'house_village_single AS b ON b.id = a.single_id')
                ->join(' LEFT JOIN ' .C('DB_PREFIX') . 'house_village_floor AS c ON c.floor_id = a.floor_id')
                ->join(' LEFT JOIN ' .C('DB_PREFIX') . 'house_village_layer AS d ON d.id = a.layer_id')
                ->field($field_vacancy)
                ->where($where_vacancy)
                ->find();
            if (empty($vacancy_info)) {
                fdump_api(['更改编号-房屋编号无数据>>'.__LINE__, $change_param,$db_house_village_user_vacancy->_sql(),$db_house_village_user_vacancy->getDbError()],'house/change_number_err_log',1);
                return false;
            }
            if ($vacancy_info && $vacancy_info['single_number'] && $vacancy_info['floor_number'] && $vacancy_info['layer_number']) {

                if (strlen($change_param['room_number'])<=2) {
                    // 不足2位 补足4位
                    $change_param['room_number'] = str_pad($change_param['room_number'],2,"0",STR_PAD_LEFT);
                    $change_param['room_number'] = $vacancy_info['layer_number'] . $change_param['room_number'];
                } elseif (strlen($change_param['room_number'])<=4) {
                    // 不足4位 补足4位
                    $change_param['room_number'] = str_pad($change_param['room_number'],4,"0",STR_PAD_LEFT);
                }

                $property_number = $vacancy_info['single_number'].$vacancy_info['floor_number'].$vacancy_info['layer_number'].$change_param['room_number'];
                $this->change_room_property_number($change_param['room_id'], $property_number,true);
            }
        }
        return true;
    }

    /**
     * Notes: 更改房屋下编号
     * @param integer $pigcms_id 房屋id
     * @param string $property_number 房屋物业编号
     * @param bool $set_room 是否需要修改房屋物业编号
     * @return bool
     * @author: wanzy
     * @date_time: 2020/12/12 15:01
     */
    public function change_room_property_number($pigcms_id, $property_number, $set_room=false) {
        if (!$pigcms_id || !$property_number) {
            return false;
        }
        $vacancy_id = intval($pigcms_id);
        $db_house_village_user_bind = M('House_village_user_bind');
        if ($set_room) {
            $db_house_village_user_vacancy = M('House_village_user_vacancy');
            $db_house_village_user_vacancy->where(['pigcms_id' => $vacancy_id])->setField('property_number', $property_number);
        }
        $where_user_bind['vacancy_id'] = $vacancy_id;
        $where_user_bind['status'] = ['not in', [0,3,4]];
        $field = 'pigcms_id,village_id, room_number, bind_number';
        $user_bind = $db_house_village_user_bind->where($where_user_bind)->field($field)->select();
        if ($user_bind) {
            // 改变用户编号
            foreach ($user_bind as $val) {
                $save_data = [];
                if ($val['room_number']) {
                    $bind_number = $property_number . $val['room_number'];
                    $where_repeat = [
                        'village_id' => $val['village_id'],
                        'bind_number' => $bind_number,
                        'pigcms_id' => ['neq', $val['pigcms_id']],
                        'status' => ['not in', [3,4]],
                    ];
                    $repeat_id = $db_house_village_user_bind->where($where_repeat)->getField('pigcms_id');
                    if ($repeat_id) {
                        $where_vacancy_max = [
                            'vacancy_id'=>$vacancy_id,
                            'pigcms_id' => ['neq', $val['pigcms_id']],
                            'status' => ['not in', [3,4]],
                        ];
                        $room_number_info = $db_house_village_user_bind->where($where_vacancy_max)->field('MAX(room_number+0) as room_number_max')->find();
                        if ($room_number_info) {
                            $room_number_max = $room_number_info['room_number_max'] ? intval($room_number_info['room_number_max']) : 0;
                        } else {
                            $room_number_max = 0;
                        }
                        $room_number = $room_number_max + 1;
                        $bind_number = $property_number . $val['room_number'];
                        $save_data['room_number'] = $room_number;
                        if ($bind_number && $bind_number!=$val['bind_number']) {
                            $save_data['bind_number'] = $bind_number;
                        }
                    } elseif ($bind_number && $bind_number!=$val['bind_number']) {
                        $save_data['bind_number'] = $bind_number;
                    }
                } else {
                    $room_number_info = $db_house_village_user_bind->where(['vacancy_id'=>$vacancy_id,'status' => ['not in', [3,4]]])->field('MAX(room_number+0) as room_number_max')->find();
                    if ($room_number_info) {
                        $room_number_max = $room_number_info['room_number_max'] ? intval($room_number_info['room_number_max']) : 0;
                    } else {
                        $room_number_max = 0;
                    }
                    $room_number = $room_number_max + 1;
                    $bind_number = $property_number . $val['room_number'];
                    $save_data['room_number'] = $room_number;
                    if ($bind_number && $bind_number!=$val['bind_number']) {
                        $save_data['bind_number'] = $bind_number;
                    }
                }
                if ($save_data) {
                    $user = $db_house_village_user_bind->where(['pigcms_id' => $val['pigcms_id']])->data($save_data)->save();
                    if (!$user) {
                        fdump_api(['更改房屋下编号错误>>'.__LINE__, $pigcms_id, $property_number, $val, $save_data, $db_house_village_user_bind->_sql(), $db_house_village_user_bind->getDbError()],'house/change_room_property_number_err_log',1);
                    }
                }
            }
        } else {
            fdump_api(['更改房屋下编号无数据>>'.__LINE__, $pigcms_id, $property_number, $db_house_village_user_bind->_sql(), $db_house_village_user_bind->getDbError()],'house/change_room_property_number_err_log',1);
        }
        return true;
    }

    /**
     * Notes:
     * @param $change_param
     * {
     *     'pigcms_id' => '1', // 修改用户编号的 用户小区绑定id 即用户绑定小区表格中pigcms_id
     * }
     * @return bool
     * @author: wanzy
     * @date_time: 2020/12/15 13:59
     */
    public function change_user_bind_number($change_param) {
        fdump_api(['修改编号>>'.__LINE__, $change_param],'house/change_user_bind_number_log',1);
        if (empty($change_param) || !$change_param['pigcms_id']) {
            return false;
        }
        $pigcms_id = intval($change_param['pigcms_id']);
        $db_house_village_user_bind = M('House_village_user_bind');
        $where_user_bind['pigcms_id'] = $pigcms_id;
        $field = 'pigcms_id, type, village_id, room_number, bind_number, vacancy_id';
        $user_bind = $db_house_village_user_bind->where($where_user_bind)->field($field)->find();
        if (!$user_bind) {
            return false;
        }
        $db_house_village_user_vacancy = M('House_village_user_vacancy');
        $save_data = [];
        if (in_array($user_bind['type'],[0,1,2,3]) && $user_bind['vacancy_id']) {
            // 如果是 业主 家属 租客 对应查询房屋 组成新的编号 然后记录上
            $vacancy_id = intval($user_bind['vacancy_id']);
            $where_vacancy = [
                'pigcms_id' => $vacancy_id
            ];
            $property_number = $db_house_village_user_vacancy->where($where_vacancy)->getField('property_number');
            if (!$property_number) {
                fdump_api(['房屋的物业编号未生成>>'.__LINE__, $change_param, $user_bind],'house/change_user_bind_number_err_log',1);
                return false;
            }

            if ($user_bind['room_number']) {
                $bind_number = $property_number . $user_bind['room_number'];
                $where_repeat = [
                    'village_id' => $user_bind['village_id'],
                    'bind_number' => $bind_number,
                    'pigcms_id' => ['neq', $pigcms_id],
                    'vacancy_id' =>$vacancy_id,
                    'status' => ['not in', [3,4]],
                ];
                $repeat_id = $db_house_village_user_bind->where($where_repeat)->getField('pigcms_id');
                if ($repeat_id) {
                    $where_vacancy_max = [
                        'vacancy_id'=>$vacancy_id,
                        'pigcms_id' => ['neq', $pigcms_id],
                        'status' => ['not in', [3,4]],
                    ];
                    $room_number_info = $db_house_village_user_bind->where($where_vacancy_max)->field('MAX(room_number+0) as room_number_max')->find();
                    if ($room_number_info) {
                        $room_number_max = $room_number_info['room_number_max'] ? intval($room_number_info['room_number_max']) : 0;
                    } else {
                        $room_number_max = 0;
                    }
                    $room_number = intval($room_number_max) + 1;
                    $bind_number = $property_number . $room_number;
                    $save_data['room_number'] = $room_number;
                    if ($bind_number && $bind_number!=$user_bind['bind_number']) {
                        $save_data['bind_number'] = $bind_number;
                    }
                } elseif ($bind_number && $bind_number!=$user_bind['bind_number']) {
                    $save_data['bind_number'] = $bind_number;
                }
            } else {
                $where_vacancy_max = [
                    'vacancy_id'=>$vacancy_id,
                    'pigcms_id' => ['neq', $pigcms_id],
                    'status' => ['not in', [3,4]],
                ];
                $room_number_info = $db_house_village_user_bind->where($where_vacancy_max)->field('MAX(room_number+0) as room_number_max')->find();
                if ($room_number_info) {
                    $room_number_max = $room_number_info['room_number_max'] ? intval($room_number_info['room_number_max']) : 0;
                } else {
                    $room_number_max = 0;
                }
                $room_number = intval($room_number_max) + 1;
                $bind_number = $property_number . $room_number;
                $save_data['room_number'] = $room_number;
                if ($bind_number && $bind_number!=$user_bind['bind_number']) {
                    $save_data['bind_number'] = $bind_number;
                }
            }
            if ($save_data) {
                $user = $db_house_village_user_bind->where(['pigcms_id' => $pigcms_id])->data($save_data)->save();
                if (!$user) {
                    fdump_api(['更改用户编号错误>>'.__LINE__, $pigcms_id, $vacancy_id, $property_number, $user_bind, $save_data, $db_house_village_user_bind->_sql(), $db_house_village_user_bind->getDbError()],'house/change_user_bind_number_err_log',1);
                }
            }
            return true;
        } else {
            // 当前其他身份 只处理 工作人员
            if (4==$user_bind['type']) {
                // 楼栋（00)单元（00）楼层（99） 楼层默认99
                $property_number = '000099';
                if ($user_bind['room_number']) {
                    $bind_number = $property_number . $user_bind['room_number'];
                    // 查询同一小区是否存在重复物业编号
                    $where_repeat = [
                        'village_id' => $user_bind['village_id'],
                        'type' => ['in',[4,6]],
                        'bind_number' => $bind_number,
                        'pigcms_id' => ['neq', $pigcms_id],
                        'status' => ['not in', [3,4]],
                    ];
                    $repeat_id = $db_house_village_user_bind->where($where_repeat)->getField('pigcms_id');
                    if ($repeat_id) {
                        $where_vacancy_max = [
                            'village_id'=>$user_bind['village_id'],
                            'pigcms_id' => ['neq', $pigcms_id],
                            'type' => ['in',[4,6]],
                            'status' => ['not in', [3,4]],
                        ];
                        $room_number_info = $db_house_village_user_bind->where($where_vacancy_max)->field('MAX(room_number+0) as room_number_max')->find();
                        if ($room_number_info) {
                            $room_number_max = $room_number_info['room_number_max'] ? intval($room_number_info['room_number_max']) : 0;
                        } else {
                            $room_number_max = 0;
                        }
                        $room_number = intval($room_number_max) + 1;
                        $bind_number = $property_number . $user_bind['room_number'];
                        $save_data['room_number'] = $room_number;
                        if ($bind_number && $bind_number!=$user_bind['bind_number']) {
                            $save_data['bind_number'] = $bind_number;
                        }
                    } elseif ($bind_number && $bind_number!=$user_bind['bind_number']) {
                        $save_data['bind_number'] = $bind_number;
                    }
                } else {
                    $where_vacancy_max = [
                        'village_id'=>$user_bind['village_id'],
                        'pigcms_id' => ['neq', $pigcms_id],
                        'type' => ['in',[4,6]],
                        'status' => ['not in', [3,4]],
                    ];
                    $room_number_info = $db_house_village_user_bind->where($where_vacancy_max)->field('MAX(room_number+0) as room_number_max')->find();
                    if ($room_number_info) {
                        $room_number_max = $room_number_info['room_number_max'] ? intval($room_number_info['room_number_max']) : 0;
                    } else {
                        $room_number_max = 0;
                    }
                    $room_number = intval($room_number_max) + 1;
                    $bind_number = $property_number . $user_bind['room_number'];
                    $save_data['room_number'] = $room_number;
                    if ($bind_number && $bind_number!=$user_bind['bind_number']) {
                        $save_data['bind_number'] = $bind_number;
                    }
                }
                if ($save_data) {
                    $user = $db_house_village_user_bind->where(['pigcms_id' => $pigcms_id])->data($save_data)->save();
                    if (!$user) {
                        fdump_api(['更改用户编号错误>>'.__LINE__, $pigcms_id, $property_number, $user_bind, $save_data, $db_house_village_user_bind->_sql(), $db_house_village_user_bind->getDbError()],'house/change_user_bind_number_err_log',1);
                    }
                }
                return true;
            }
        }
        return true;
    }


    /*
     * 查询房间的物业服务时间是否大于设置合同时间
     */
    public  function getVacancyServiceTime($data){
        $error=[];
        $error['status']=1;
        $error['msg']='合同时间设置符合设置规则';
        if (!empty($data['contract_time_start'])&&!empty($data['contract_time_end'])){
            if (!empty($data['village_id'])&&empty($data['single_id'])){
                $where=[];
                $where['village_id']=$data['village_id'];
                $where['order_type']='property';
                $time_info1=D('House_new_order_log')->where($where)->order('service_end_time DESC')->find();
                $time_info2=D('House_new_order_log')->where($where)->order('service_end_time ASC')->find();

                //合同结束时间不能小于最大的物业服务结束时间
                if (!empty($time_info1)&& $time_info1['service_end_time'] >100 &&$time_info1['service_end_time']>$data['contract_time_end']){
                    $error['status']=0;
                    $error['msg']='合同结束时间不能小于'.date('Y-m-d',$time_info1['service_end_time']);
                    return $error;
                }
                //合同开始时间不能大于最小的物业服务结束时间
                if (!empty($time_info2)&& $time_info2['service_end_time'] >100 &&$time_info2['service_end_time']<$data['contract_time_start']){
                    $error['status']=0;
                    $error['msg']='合同开始时间不能大于'.date('Y-m-d',$time_info2['service_end_time']);
                    return $error;
                }
            }
            if (!empty($data['village_id'])&&!empty($data['single_id'])){
                $where1=[];
                $where1['village_id']=$data['village_id'];
                $where1['single_id']=$data['single_id'];
                $roomid_arr=$this->where($where1)->getField('pigcms_id',true);
                if (!empty($roomid_arr)){
                    $where=[];
                    $where['village_id']=$data['village_id'];
                    $where['order_type']='property';
                    $where['room_id']=array('in',$roomid_arr);
                    $time_info1=D('House_new_order_log')->where($where)->order('service_end_time DESC')->find();
                    $time_info2=D('House_new_order_log')->where($where)->order('service_end_time ASC')->find();

                    //合同结束时间不能小于最大的物业服务结束时间
                    if (!empty($time_info1)&& $time_info1['service_end_time'] >100 &&$time_info1['service_end_time']>$data['contract_time_end']){
                        $error['status']=0;
                        $error['msg']='合同结束时间不能小于'.date('Y-m-d',$time_info1['service_end_time']);
                        return $error;
                    }
                    //合同开始时间不能大于最小的物业服务结束时间
                    if (!empty($time_info2)&& $time_info2['service_end_time'] >100 &&$time_info2['service_end_time']<$data['contract_time_start']){
                        $error['status']=0;
                        $error['msg']='合同开始时间不能大于'.date('Y-m-d',$time_info2['service_end_time']);
                        return $error;
                    }
                }

            }
        }else{
            $error['status']=0;
            $error['msg']='请先设置合同时间';
            return $error;
        }

        return $error;

    }

public function roomExport(&$objProps, &$objExcel, $param){
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        $param['type'] = 'room_export';
        $title = L_('房间列表');
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        // 设置当前的sheet
        $objExcel->setActiveSheetIndex(0);


        // 查询数据
        $where = [];
        $village_id = $param['village_id'];
        $where['village_id'] = $village_id;
        if (isset($param['status']) && $param['status'] != '') {
            $where['status'] = $param['status'];
        }
        $where['is_public_rental']=0;  //1公租房
        $find_type = isset($param['find_type']) ? $param['find_type']:0;
        $find_value = isset($param['find_value']) && !empty($param['find_value']) ? trim($param['find_value']):'';
        if(isset($param['export_from']) && ($param['export_from']=='unitRental')){
            //公租房导出
            $keytypeArr=array('usernum'=>1,'name'=>2,'phone'=>3);
            if(isset($param['keytype']) && $param['keytype']){
                $find_type=$keytypeArr[$param['keytype']];
            }
            if(isset($param['keyword']) && $param['keyword']){
                $find_value=$param['keyword'];
            }
            $param['single_id']=0;
            $param['floor_id']=0;
            $param['layer_id']=0;
            $param['vacancy_id']=0;
            if(isset($param['vacancy']) && !empty($param['vacancy']) && is_array($param['vacancy'])){
                $param['single_id']=$param['vacancy'][0];
                if(isset($param['vacancy'][1]) && $param['vacancy'][1]>0){
                    $param['floor_id']=$param['vacancy'][1];
                }
                if(isset($param['vacancy'][2]) && $param['vacancy'][2]>0){
                    $param['layer_id']=$param['vacancy'][2];
                }
                if(isset($param['vacancy'][3]) && $param['vacancy'][3]>0){
                    $param['vacancy_id']=$param['vacancy'][3];
                }
            }
            unset($where['status']);
            if(isset($param['status']) && $param['status']==-1){
                $where['status'] = 0;
            }elseif(isset($param['status']) && $param['status']>0){
                $where['status'] = $param['status'];
            }
            $where['is_public_rental']=1;
        }
        if ($find_value) {
            $find_value=htmlspecialchars($find_value,ENT_QUOTES);
            if ($find_type == 1) {
                $where['_string'] = "usernum LIKE '%{$find_value}%' OR property_number LIKE '%{$find_value}%'";
            } else if ($find_type == 2) {
                $where['pigcms_id']=-1;
                $user_bind_where['name'] = array('like', '%' . $find_value . '%');
                $user_bind_where['village_id'] = $village_id;
                $user_bind = D('House_village_user_bind')->field('vacancy_id')->where($user_bind_where)->select();
                if ($user_bind) {
                    $uids = [];
                    foreach ($user_bind as $k => $v) {
                        $uids[] = $v['vacancy_id'];
                    }
                    $where['pigcms_id'] = array('in', $uids);
                }

            } else if ($find_type == 3) {
                $where['pigcms_id']=-1;
                $user_bind_where['phone'] = array('like', '%' . $find_value . '%');
                $user_bind_where['village_id'] = $village_id;
                $user_bind = D('House_village_user_bind')->field('vacancy_id')->where($user_bind_where)->select();
                if ($user_bind) {
                    $uids = [];
                    foreach ($user_bind as $k => $v) {
                        $uids[] = $v['vacancy_id'];
                    }
                    $where['pigcms_id'] = array('in', $uids);

                }
            } else if ($find_type == 4) {
                $where['address'] = array('like', '%' . $find_value . '%');
            } else if ($find_type == 5) {
                $where['card_no'] = array('like', '%' . $find_value . '%');
            }
        }
        if (isset($param['single_id'])&&$param['single_id'] != 0) {
            $where['single_id'] = $param['single_id'];
        }
        if (isset($param['floor_id'])&&$param['floor_id'] != 0) {
            $where['floor_id'] = $param['floor_id'];
        }
        if (isset($param['layer_id'])&&$param['layer_id'] != 0) {
            $where['layer_id'] = $param['layer_id'];
        }
        if (isset($param['vacancy_id'])&&$param['vacancy_id'] != 0) {
            $where['pigcms_id'] = $param['vacancy_id'];
        }

        $where['is_del'] = 0;
        if (isset($param['property_id']) && $param['property_id'] != '') {
            $property_id = $param['property_id'];
        } else {
            $property_id = 0;
        }
        $order_count = $this->where($where)->count();
        $length = ceil($order_count / 1000);
        $order_sort = 'sort desc,pigcms_id desc';
        $user_status_data = '业主入住,未入住,租客入住,无';
        $house_type_data = '住宅,商铺,办公,无';
        $sell_status_data = '正常居住,出售中,出租中';
        $db_order = D('Order');
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);
            $objActSheet = $objExcel->getActiveSheet();
            $objActSheet->setTitle(L_('第X1个千条房间信息', ($i + 1)));

            // 设置表头
            // 物业编号	楼号	单元名称	层号	房间号	使用状态	已解绑住户记录	IC卡管理	物业服务时间	状态  房屋面积
            $title_arr = array(
                array(20, L_('房间ID')),
                array(20, L_('物业编号')),
                array(20, L_('楼号')),
                array(20, L_('单元名称')),
                array(20, L_('层号')),
                array(20, L_('房间号')),
                array(20, L_('使用状态[可变更]')),
                array(20, L_('状态')),
                array(20, L_('添加时间')),
                array(20, L_('房屋面积[可变更]')),
                array(20, L_('房屋类型[可变更]')),
                array(20, L_('出售状态[可变更]')),
            );

            $title_arr[] = array(20, L_('排序[可变更]'));
            $index = 1;
            $_GET['page'] = $i+1;
            $order_list = $this->house_village_user_vacancy_page_list($where, '*', $order_sort,3000,$property_id);
            $new_charge_time = isset($order_list['result'])&&isset($order_list['result']['new_charge_time']) ? $order_list['result']['new_charge_time']:0;
            if ($new_charge_time==1) {
                $title_arr[] = array(20, L_('物业服务开始时间[可变更]'));
                $title_arr[] = array(20, L_('物业服务结束时间[可变更]'));
                $title_arr[] = array(20, L_('房间别名编号[可变更]'));
            }
            $isAihorseCustomized=D('Config_customization')->getIsAihorseCustomized();
            if($isAihorseCustomized){
                $title_arr[] = array(20, L_('入住日期'));
            }
            $jz_wsxdsj_net_customized_open = D('Config_customization')->getWSXCustomizedOpen();
            if($jz_wsxdsj_net_customized_open){
                $title_arr[] = array(20, L_('供暖面积(m²)[可变更]'));
                $title_arr[] = array(20, L_('计费面积(m²)[可变更]'));
            }
            if(C('config.customized_meter_reading')){
                $title_arr[] = array(20, L_('冷水表编号'));
                $title_arr[] = array(20, L_('热水表编号'));
                $title_arr[] = array(20, L_('电表编号'));
                $title_arr[] = array(20, L_('燃气表编号'));
            }
            $lastColumn = $db_order->setPHPExcelHeader($objExcel, $title_arr, 1, 16);
            if (isset($order_list['result'])&&isset($order_list['result']['list'])) {
                $order_list = $order_list['result']['list'];
            } else {
                $order_list = [];
            }
            $house_village_vacancy_numDb=M('House_village_vacancy_num');
            foreach ($order_list as $key1  => $value1) {
                $index++;
                $column = 0;
                $objActSheet->setCellValueExplicit($db_order->getLetterColumn($column++) . $index, $value1['pigcms_id']);
                if (isset($value1['property_number'])&&$value1['property_number']) {
                    $property_number = $value1['property_number'];
                } else {
                    $property_number = $value1['usernum'];
                }
                $objActSheet->setCellValueExplicit($db_order->getLetterColumn($column++) . $index, $property_number);
                $floor_layer = isset($value1['floor_layer'])&&$value1['floor_layer']?$value1['floor_layer']:'';
                $objActSheet->setCellValueExplicit($db_order->getLetterColumn($column++) . $index, $floor_layer);
                $floor_name = isset($value1['floor_name'])&&$value1['floor_name']?$value1['floor_name']:'';
                $objActSheet->setCellValueExplicit($db_order->getLetterColumn($column++) . $index, $floor_name);
                $layer = isset($value1['layer'])&&$value1['layer']?$value1['layer']:'';
                $objActSheet->setCellValueExplicit($db_order->getLetterColumn($column++) . $index, $layer);
                $room = isset($value1['room'])&&$value1['room']?$value1['room']:'';
                $objActSheet->setCellValueExplicit($db_order->getLetterColumn($column++) . $index, $room);
                // 使用状态[可变更]
                if (isset($value1['user_status'])&&$value1['user_status']==1) {
                    $user_status_txt = '业主入住';
                } elseif (isset($value1['user_status'])&&$value1['user_status']==2) {
                    $user_status_txt = '未入住';
                } elseif (isset($value1['user_status'])&&$value1['user_status']==3) {
                    $user_status_txt = '租客入住';
                } else {
                    $user_status_txt = '无';
                }
                $objExcel->setActiveSheetIndex(0);
                $objActSheet = $objActSheet;
                $cell = $db_order->getLetterColumn($column++) . $index;
                $objValidation = $objActSheet->getCell($cell)->getDataValidation(); //这一句为要设置数据有效性的单元格
                $objValidation->setType(PHPExcel_Cell_DataValidation::TYPE_LIST)
                    ->setErrorStyle(PHPExcel_Cell_DataValidation::STYLE_INFORMATION)
                    ->setAllowBlank(false)
                    ->setShowInputMessage(true)
                    ->setShowErrorMessage(true)
                    ->setShowDropDown(true)
                    ->setErrorTitle('输入的值有误')
                    ->setError('您输入的值不在下拉框列表内.')
                    ->setPromptTitle('使用状态')
                    ->setFormula1('"' . $user_status_data . '"');

                $objActSheet->setCellValueExplicit($cell, $user_status_txt);

                if (isset($value1["status"])&&$value1["status"]==1) {
                    $statusTxt = '空置';
                } elseif (isset($value1["status"])&&$value1["status"]==2) {
                    $statusTxt = '审核中';
                } elseif (isset($value1["status"])&&$value1["status"]==3) {
                    $statusTxt = '已绑定业主';
                    if ($value1["uid"]==0&&$value1["name"]!=''&&$value1["phone"]!='') {
                        $statusTxt .= '[未注册]';
                    }
                } else {
                    $statusTxt = '关闭';
                }
                $cell01 = $db_order->getLetterColumn($column++) . $index;
                $objActSheet->setCellValueExplicit($cell01, $statusTxt);

                $cell02 = $db_order->getLetterColumn($column++) . $index;
                $addTimeTxt = isset($value1['add_time'])&&$value1['add_time']?date('Y-m-d H:i:s', $value1['add_time']):'';
                $objActSheet->setCellValueExplicit($cell02, $addTimeTxt);
                // 房屋面积[可变更]
                $cell03 = $db_order->getLetterColumn($column++) . $index;
                $housesize = isset($value1['housesize'])&&$value1['housesize']?$value1['housesize']:'';
                $objActSheet->setCellValueExplicit($cell03, $housesize);
                // 房屋类型[可变更]
                if (isset($value1['house_type'])&&$value1['house_type']==1) {
                    $house_type_txt = '住宅';
                } elseif (isset($value1['house_type'])&&$value1['house_type']==2) {
                    $house_type_txt = '商铺';
                } elseif (isset($value1['house_type'])&&$value1['house_type']==3) {
                    $house_type_txt = '办公';
                } else {
                    $house_type_txt = '无';
                }
                $objExcel->setActiveSheetIndex(0);
                $cell1 = $db_order->getLetterColumn($column++) . $index;
                $objValidation = $objActSheet->getCell($cell1)->getDataValidation(); //这一句为要设置数据有效性的单元格
                $objValidation->setType(PHPExcel_Cell_DataValidation::TYPE_LIST)
                    ->setErrorStyle(PHPExcel_Cell_DataValidation::STYLE_INFORMATION)
                    ->setAllowBlank(false)
                    ->setShowInputMessage(true)
                    ->setShowErrorMessage(true)
                    ->setShowDropDown(true)
                    ->setErrorTitle('输入的值有误')
                    ->setError('您输入的值不在下拉框列表内.')
                    ->setPromptTitle('房屋类型')
                    ->setFormula1('"' . $house_type_data . '"');

                $objActSheet->setCellValueExplicit($cell1, $house_type_txt);
                // 出售状态[可变更]
                if (isset($value1['sell_status'])&&$value1['sell_status']==1) {
                    $sell_status_txt = '正常居住';
                } elseif (isset($value1['sell_status'])&&$value1['sell_status']==2) {
                    $sell_status_txt = '出售中';
                } elseif (isset($value1['sell_status'])&&$value1['sell_status']==3) {
                    $sell_status_txt = '出租中';
                } else {
                    // 默认正常居住
                    $sell_status_txt = '正常居住';
                }
                $objExcel->setActiveSheetIndex(0);
                $cell2 = $db_order->getLetterColumn($column++) . $index;
                $objValidation = $objActSheet->getCell($cell2)->getDataValidation(); //这一句为要设置数据有效性的单元格
                $objValidation->setType(PHPExcel_Cell_DataValidation::TYPE_LIST)
                    ->setErrorStyle(PHPExcel_Cell_DataValidation::STYLE_INFORMATION)
                    ->setAllowBlank(false)
                    ->setShowInputMessage(true)
                    ->setShowErrorMessage(true)
                    ->setShowDropDown(true)
                    ->setErrorTitle('输入的值有误')
                    ->setError('您输入的值不在下拉框列表内.')
                    ->setPromptTitle('出售状态')
                    ->setFormula1('"' . $sell_status_data . '"');
                $objActSheet->setCellValueExplicit($cell2, $sell_status_txt);
                
                $cell11 = $db_order->getLetterColumn($column++) . $index;
                $objActSheet->setCellValueExplicit($cell11, $value1['sort']);
                
                if ($new_charge_time==1) {
                    $cell12 = $db_order->getLetterColumn($column++) . $index;
                    if ($value1['service_start_time']&&$value1['service_start_time']!='未设置') {
                        $service_start_time = strtotime($value1['service_start_time']);
                        if (!$service_start_time) {
                            $service_start_time = $value1['service_start_time'];
                        } else {
                            $service_start_time = date('Y年m月d日', $service_start_time);
                        }
                    } else {
                        $service_start_time = '';
                    }
                    $objActSheet->setCellValueExplicit($cell12,' '.$service_start_time);
                    $cell13 = $db_order->getLetterColumn($column++) . $index;
                    if ($value1['service_end_time']&&$value1['service_end_time']!='未设置') {
                        $service_end_time = strtotime($value1['service_end_time']);
                        if (!$service_end_time) {
                            $service_end_time = $value1['service_end_time'];
                        } else {
                            $service_end_time = date('Y年m月d日', $service_end_time);
                        }
                    } else {
                        $service_end_time = '';
                    }
                    $objActSheet->setCellValueExplicit($cell13,' '.$service_end_time);
                    $room_alias_id=isset($value1['room_alias_id']) && !empty($value1['room_alias_id']) ? $value1['room_alias_id']:'';
                    $cell14 = $db_order->getLetterColumn($column++) . $index;
                    $objActSheet->setCellValueExplicit($cell14,' '.$room_alias_id);
                }
                if($isAihorseCustomized){
                    $cell_index = $db_order->getLetterColumn($column++) . $index;
                    $house_in_time = isset($value1['house_in_time'])&& $value1['house_in_time'] >100 ? date('Y-m-d', $value1['house_in_time']):'';
                    $objActSheet->setCellValueExplicit($cell_index,' '.$house_in_time);
                }
                if($jz_wsxdsj_net_customized_open){
                    $heating_size = $value1['heating_size'] ?? 0;
                    $cell15 = $db_order->getLetterColumn($column++) . $index;
                    $objActSheet->setCellValueExplicit($cell15,' '.$heating_size);
                    $billable_size = $value1['billable_size'] ?? 0;
                    $cell16 = $db_order->getLetterColumn($column++) . $index;
                    $objActSheet->setCellValueExplicit($cell16,' '.$billable_size);
                }
                if(C('config.customized_meter_reading')) {
                    $vacancy_num=$house_village_vacancy_numDb->where(array('village_id'=> $village_id,'vacancy_id'=> $value1['pigcms_id']))->field('id,water_number,heat_water_number,ele_number,gas_number')->find();
                    if($vacancy_num){
                        $cell15 = $db_order->getLetterColumn($column++) . $index;
                        $objActSheet->setCellValueExplicit($cell15,' '.$vacancy_num['water_number']);
                        $cell16 = $db_order->getLetterColumn($column++) . $index;
                        $objActSheet->setCellValueExplicit($cell16,' '.$vacancy_num['heat_water_number']);
                        $cell17 = $db_order->getLetterColumn($column++) . $index;
                        $objActSheet->setCellValueExplicit($cell17,' '.$vacancy_num['ele_number']);
                        $cell18 = $db_order->getLetterColumn($column++) . $index;
                        $objActSheet->setCellValueExplicit($cell18,' '.$vacancy_num['gas_number']);
                    }
                }
            }
        }
        sleep(2);
    }

    public function roomExportImport($upload_dir,$param=[])
    {
        import('ORG.Net.UploadFile');
        $upload = new UploadFile();
        $upload->maxSize = 10 * 1024 * 1024;
        $upload->allowExts = array('xls', 'xlsx');
        $upload->allowTypes = array(); // 允许上传的文件类型 留空不做检查
        $upload->savePath = $upload_dir;
        $upload->thumb = false;
        $upload->thumbType = 0;
        $upload->imageClassPath = '';
        $upload->thumbPrefix = '';
        $upload->saveRule = 'uniqid';
        if ($upload->upload()) {
            $uploadList = $upload->getUploadFileInfo();
            require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel/IOFactory.php';
            $path = $uploadList['0']['savepath'] . $uploadList['0']['savename'];
            $fileType = PHPExcel_IOFactory::identify($path); //文件名自动判断文件类型
            $objReader = PHPExcel_IOFactory::createReader($fileType);
            $excelObj = $objReader->load($path);
            $result = $excelObj->getActiveSheet()->toArray(null, true, true, true);
            fdump_api(['fileType' => $fileType,'result' => $result],'000roomExportImport');
            unlink($path);  //删除文件
            $error_arr = [];
            if (!empty($result) && is_array($result)) {
                unset($result[1]);
                if (count($result) == 0) {
                    throw new Exception(L_('表格未成功识别。请使用 excel软件 将表格更换为 xlsx格式 重试一下'));
                }
            }
            if (isset($param['village_id']) && $param['village_id']) {
                $village_id = $param['village_id'];
            } else {
                $village_id = 0;
            }
            if (isset($param['property_id']) && $param['property_id']) {
                $property_id = $param['property_id'];
            } else {
                $property_id = 0;
            }
            $arrUserStatusKey = ['业主入住', '未入住', '租客入住', '无'];
            $arrUserStatusVal = [
                '无' => 0,
                '业主入住' => 1,
                '未入住' => 2,
                '租客入住' => 3,
            ];
            $arrHouseTypeKey = ['住宅', '商铺', '办公', '无'];
            $arrHouseTypeVal = [
                '无' => 0,
                '住宅' => 1,
                '商铺' => 2,
                '办公' => 3,
            ];
            $arrSellStatusKey = ['正常居住', '出售中', '出租中'];
            $arrSellStatusVal = [
                '正常居住' => 1,
                '出售中' => 2,
                '出租中' => 3,
            ];
            if ($property_id) {
                $is_new_charge = D('House_village')->checkNewCharge($property_id);
                $setUserStatusKey = 'G'; // 使用状态[可变更] 列
                $setRoomHouseSizeKey = 'J'; // 房屋面积[可变更] 列
                $setHouseTypeKey = 'K'; // 房屋类型[可变更] 列
                $setSellStatusKey = 'L'; // 出售状态[可变更] 列
                $setSortKey = 'M'; // 排序[可变更] 列
                if ($is_new_charge) {
                    $setStartTime = 'N';
                    $setEndTime = 'O';
                    $roomAliasId='P'; //房间别名编号
                    $errKey = 'Q';// 错误返回列
                } else {
                    $setStartTime = '';
                    $setEndTime = '';
                    $errKey = 'N';// 错误返回列
                }
            } else {
                $is_new_charge = 0;
                $setUserStatusKey = 'G'; // 使用状态[可变更] 列
                $setRoomHouseSizeKey = 'J'; // 房屋面积[可变更] 列
                $setHouseTypeKey = 'K'; // 房屋类型[可变更] 列
                $setSellStatusKey = 'L'; // 出售状态[可变更] 列
                $setSortKey = 'M'; // 排序[可变更] 列
                $setStartTime = '';
                $setEndTime = '';
                $errKey = 'N';// 错误返回列
            }
            $village_time = D('House_village_info')->field('contract_time_start,contract_time_end')->where(array('village_id' => $village_id))->find();
            if (!empty($village_time) && $village_time['contract_time_start'] > 1) {
                $contract_time_start = $village_time['contract_time_start'];
                $contract_time_end = $village_time['contract_time_end'];
            } else {
                $contract_time_start = 0;
                $contract_time_end = 0;
            }
            $db_house_village_single = D('House_village_single');
            $db_house_new_order_log = D('House_new_order_log');
            $worksheet = $excelObj->getActiveSheet();
            foreach ($result as $kk => $vv) {
                if (!$vv['A']){
                    $vv[$errKey] = '请带上房间ID';
                    $error_arr[] = $vv;
                    continue;
                }
                // todo 根据反馈对应处理
                $save = [];
                // 使用状态[可变更] 列
                if ($setUserStatusKey && !trim($vv[$setUserStatusKey])) {
                    $vv[$errKey] = '请选择使用状态';
                    $error_arr[] = $vv;
                    continue;
                }
                $key1 = trim($vv[$setUserStatusKey]);
                if ($setUserStatusKey && !in_array($key1, $arrUserStatusKey)) {
                    $vv[$errKey] = '请选择正确的使用状态'."[{$key1}]";
                    $error_arr[] = $vv;
                    continue;
                }
                if ($setUserStatusKey && $key1) {
                    if (isset($arrUserStatusVal[$key1])) {
                        $save['user_status'] = $arrUserStatusVal[$key1];
                    } else {
                        $save['user_status'] = 0;
                    }
                }
                // 房屋类型[可变更] 列
                if ($setHouseTypeKey && !trim($vv[$setHouseTypeKey])) {
                    $vv[$errKey] = '请选择房屋类型';
                    $error_arr[] = $vv;
                    continue;
                }
                $key2 = trim($vv[$setHouseTypeKey]);
                if ($setHouseTypeKey && !in_array($key2, $arrHouseTypeKey)) {
                    $vv[$errKey] = '请选择正确的房屋类型'."[{$key2}]";
                    $error_arr[] = $vv;
                    continue;
                }
                if ($setHouseTypeKey && $key2) {
                    if (isset($arrHouseTypeVal[$key2])) {
                        $save['house_type'] = $arrHouseTypeVal[$key2];
                    } else {
                        $save['house_type'] = 0;
                    }
                }
                // 出售状态[可变更] 列
                if ($setSellStatusKey && !trim($vv[$setSellStatusKey])) {
                    $vv[$errKey] = '请选择出售状态';
                    $error_arr[] = $vv;
                    continue;
                }
                $key3 = trim($vv[$setSellStatusKey]);
                if ($key3 && !in_array($key3, $arrSellStatusKey)) {
                    $vv[$errKey] = '请选择正确的出售状态'."[{$key3}]";
                    $error_arr[] = $vv;
                    continue;
                }
                if ($setSellStatusKey && $key3) {
                    if (isset($arrSellStatusVal[$key3])) {
                        $save['sell_status'] = $arrSellStatusVal[$key3];
                    } else {
                        $save['sell_status'] = 0;
                    }
                }
                if (!trim($vv[$setRoomHouseSizeKey])) {
                    $vv[$errKey] = '当前只支持变更【房屋面积】，面积不允许为空或者0';
                    $error_arr[] = $vv;
                    continue;
                }
                $setRoomHouseSize = trim($vv[$setRoomHouseSizeKey]);
                if (floatval($setRoomHouseSize) <= 0) {
                    $vv[$errKey] = '当前只支持变更【房屋面积】，面积不允许小于0';
                    $error_arr[] = $vv;
                    continue;
                }
                $pigcms_id = intval($vv['A']);
                $whereRoom = [
                    'pigcms_id' => $pigcms_id,
                    'village_id' => $village_id,
                ];
                $roomHouseInfo = $this->where($whereRoom)->field('pigcms_id,village_id,housesize,single_id,contract_time_start,contract_time_end')->find();
                if (empty($roomHouseInfo)) {
                    $vv[$errKey] = '变更房屋不存在';
                    $error_arr[] = $vv;
                    continue;
                }
                if (floatval($roomHouseInfo['housesize']) == floatval($setRoomHouseSize)) {
//                    $vv[$errKey] = '当前只支持变更【房屋面积】，但是面积没有进行变更';
//                    $error_arr[] = $vv;
                } else {
                    $save['housesize'] = $setRoomHouseSize;
                }
             
                $sortV = trim($vv[$setSortKey]);
                $sortV= !empty($sortV) ? intval($sortV):0;
                $save['sort'] = $sortV;
               
                $property_starttime = $property_endtime = 1;
                // 处理物业服务时间变更
                if ($is_new_charge && $setStartTime && $setEndTime) {
                    if (!$contract_time_end || $contract_time_end <= 1 || !$contract_time_start || $contract_time_start <= 1) {
                        $vv[$errKey] = "小区合同时间未设置，请先到小区基本信息中设置合同起始时间(服务时间不进行变更)";
                        $error_arr[] = $vv;
                        continue;
                    }
                    if (!empty($roomHouseInfo) && $roomHouseInfo['contract_time_start'] > 1) {
                        $contract_time_start = $roomHouseInfo['contract_time_start'];
                        $contract_time_end = $roomHouseInfo['contract_time_end'];
                    } else {
                        $single_time = $db_house_village_single->field('contract_time_start,contract_time_end')->where(array('id' => $roomHouseInfo['single_id']))->find();
                        if (!empty($single_time) && $single_time['contract_time_start'] > 1) {
                            $contract_time_start = $single_time['contract_time_start'];
                            $contract_time_end = $single_time['contract_time_end'];
                        }
                    }
                    if (isset($vv[$setStartTime]) && $vv[$setStartTime]) {
                        if ($vv[$setEndTime] != '未设置') {
                            $cell = $worksheet->getCellByColumnAndRow(12, $kk);
                            $value = $cell->getValue();
                            $p_valueStartTime = PHPExcel_Shared_Date::ExcelToPHP($value);
                            if ($p_valueStartTime && $p_valueStartTime > 0 && date('Y-m-d', $p_valueStartTime)) {
                                $property_starttime = strtotime(date('Y-m-d', $p_valueStartTime));
                            }
                        }
                        if ($vv[$setStartTime] == '未设置') {
                            $property_starttime = 1;
                        } elseif ($property_endtime <= 1 && preg_match('/[\x7f-\xff]/', $vv[$setStartTime])) {
                            $arr = date_parse_from_format('Y年m月d日', $vv[$setStartTime]);
                            $property_starttime = mktime(0, 0, 0, $arr['month'], $arr['day'], $arr['year']);
                        } elseif ($property_endtime <= 1) {
                            $arr = explode("-", $vv[$setStartTime]);
                            $property_starttime = mktime(0, 0, 0, $arr[1], $arr[2], $arr[0]);
                        }
                    }
                    if (isset($vv[$setEndTime]) && $vv[$setEndTime]) {
                        if ($vv[$setEndTime] != '未设置') {
                            $cell = $worksheet->getCellByColumnAndRow(13, $kk);
                            $value = $cell->getValue();
                            $p_valueEndTime = PHPExcel_Shared_Date::ExcelToPHP($value);
                            if ($p_valueEndTime && $p_valueEndTime > 0 && date('Y-m-d', $p_valueEndTime)) {
                                $property_endtime = strtotime(date('Y-m-d', $p_valueEndTime));
                            }
                        }
                        if ($vv[$setEndTime] == '未设置') {
                            $property_endtime = 1;
                        } elseif ($property_endtime <= 1 && preg_match('/[\x7f-\xff]/', $vv[$setEndTime])) {
                            $arr = date_parse_from_format('Y年m月d日', $vv[$setEndTime]);
                            $property_endtime = mktime(0, 0, 0, $arr['month'], $arr['day'], $arr['year']);
                        } elseif ($property_endtime <= 1) {
                            $arr = explode("-", $vv[$setEndTime]);
                            $property_endtime = mktime(0, 0, 0, $arr[1], $arr[2], $arr[0]);
                        }
                    }
                    if ($property_starttime > 1 && $property_endtime > 1) {
                        if ($property_starttime >= $property_endtime) {
                            $vv[$errKey] = "物业服务结束时间不能小于物业服务开始时间";
                            $error_arr[] = $vv;
                            continue;
                        }
                        if ($contract_time_start > 1 && $contract_time_end > 1) {
                            if ($property_starttime < $contract_time_start || $property_endtime > $contract_time_end) {
                                $vv[$errKey] = '物业服务起止时间不能超出【' . date('Y-m-d', $contract_time_start) . '至' . date('Y-m-d', $contract_time_end) . '】的合同时间范围！';
                                $error_arr[] = $vv;
                                continue;
                            }
                        }
                        if($property_endtime>100){
                            $property_endtime +=86399;
                        }
                        $save['property_endtime'] = $property_endtime;
                        $save['property_starttime'] = $property_starttime;
                    }
                }
                if($is_new_charge && $roomAliasId){
                    $room_alias_id=$vv[$roomAliasId];
                    $room_alias_id=trim($room_alias_id);
                    if($room_alias_id && is_numeric($room_alias_id)){
                        $whereArr=array('village_id'=>$village_id,'is_del'=>0,'room_alias_id'=>$room_alias_id);
                        $whereArr['_string']='pigcms_id !='.$pigcms_id;
                        $village_user_vacancy_tmp=$this->get_one_village_user_vacancy($whereArr,'pigcms_id,room_alias_id,name,phone');
                        if(!empty($village_user_vacancy_tmp)){
                            $vv[$errKey] = '该房间别名编号【'.$room_alias_id.'】已经存在，因此房间别名编号未做改动！';
                            $error_arr[] = $vv;
                            //continue;
                        }else{
                            $save['room_alias_id'] =$room_alias_id;
                        }
                    }
                }
                if (!empty($save)) {
                    $saveId = $this->where($whereRoom)->save($save);
                    if ($saveId === false) {
                        $vv[$errKey] = '房屋信息变更失败';
                        $error_arr[] = $vv;
                        continue;
                    }
                    if (isset($save['housesize']) && $save['housesize']) {
                        $saveBind = [
                            'housesize' => $save['housesize']
                        ];
                        $whereBind = [
                            'vacancy_id' => $pigcms_id,
                            'village_id' => $village_id,
                        ];
                        $saveBindId = D('House_village_user_bind')->where($whereBind)->save($saveBind);
                        if ($saveBindId === false) {
                            $vv[$errKey] = '【房屋面积】对应用户面积变更失败';
                            $error_arr[] = $vv;
                            continue;
                        }
                    }
                    if ($property_starttime > 1 && $property_endtime > 1) {
                        //todo order_log表需写入物业服务时间
                        $arr = [];
                        $arr['order_type'] = 'property';
                        $arr['order_name'] = '物业费';
                        $arr['room_id'] = $roomHouseInfo['pigcms_id'];
                        $arr['property_id'] = $property_id;
                        $arr['village_id'] = $village_id;
                        $arr['service_start_time'] = $property_starttime;
                        $arr['service_end_time'] = $property_endtime;
                        $arr['desc'] = '导入房间物业服务时间';
                        $arr['add_time'] = time();
                        $oderId = $db_house_new_order_log->data($arr)->add();
                        if (!$oderId) {
                            $vv[$errKey] = '【物业服务时间】变更失败';
                            $error_arr[] = $vv;
                            continue;
                        }
                    }
                }
            }
            if ($error_arr && count($error_arr) > 0) {
                $num = count($error_arr);
                echo '<javascript>alert("失败' . $num . '条，点击下载)";</javascript>';
                //导出失败信息
                require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
                error_reporting(E_ALL);
                date_default_timezone_set('Europe/London');
                $objExcel = new PHPExcel();

                $title = '房间导入变更失败列表';
                /*以下是一些设置 ，什么作者  标题之类的*/
                $objExcel->getProperties()->setCreator($title)
                    ->setLastModifiedBy($title)
                    ->setTitle($title)
                    ->setSubject("失败信息导出")
                    ->setDescription("备份数据")
                    ->setKeywords("excel")
                    ->setCategory("result file");

                $i = 0;
                $objExcel->createSheet();
                $objExcel->setActiveSheetIndex($i);
                $objExcel->getActiveSheet()->setTitle('房间列表');
                $objExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(16);

                $objActSheet = $objExcel->getActiveSheet();
                $objActSheet->getColumnDimension('A')->setWidth(20);
                $objActSheet->setCellValue('A1', '房间ID');
                $objActSheet->getColumnDimension('B')->setWidth(20);
                $objActSheet->setCellValue('B1', '物业编号');
                $objActSheet->getColumnDimension('C')->setWidth(20);
                $objActSheet->setCellValue('C1', '楼号');
                $objActSheet->getColumnDimension('D')->setWidth(20);
                $objActSheet->setCellValue('D1', '单元名称');
                $objActSheet->getColumnDimension('E')->setWidth(20);
                $objActSheet->setCellValue('E1', '层号');
                $objActSheet->getColumnDimension('F')->setWidth(20);
                $objActSheet->setCellValue('F1', '房间号');
                $objActSheet->getColumnDimension('G')->setWidth(20);
                $objActSheet->setCellValue('G1', '使用状态');
                $objActSheet->getColumnDimension('H')->setWidth(20);
                $objActSheet->setCellValue('H1', '状态');
                $objActSheet->getColumnDimension('I')->setWidth(20);
                $objActSheet->setCellValue('I1', '添加时间');
                $objActSheet->getColumnDimension('J')->setWidth(20);
                $objActSheet->setCellValue('J1', '房屋面积[可变更]');
                $objActSheet->getColumnDimension('K')->setWidth(20);
                $objActSheet->setCellValue('K1', '房屋类型[可变更]');
                $objActSheet->getColumnDimension('L')->setWidth(20);
                $objActSheet->setCellValue('L1', '出售状态[可变更]');
                $objActSheet->getColumnDimension('M')->setWidth(20);
                $objActSheet->setCellValue('M1', '排序[可变更]');
                if ($is_new_charge) {
                    $objActSheet->getColumnDimension('N')->setWidth(20);
                    $objActSheet->setCellValue('N1', '物业服务开始时间[可变更]');
                    $objActSheet->getColumnDimension('O')->setWidth(20);
                    $objActSheet->setCellValue('O1', '物业服务结束时间[可变更]');
                    $objActSheet->getColumnDimension('P')->setWidth(20);
                    $objActSheet->setCellValue('P1', '房间别名编号[可变更]');
                    $objActSheet->getColumnDimension('Q')->setWidth(100);
                    $objActSheet->setCellValue('Q1', '失败原因');
                } else {
                    $objActSheet->getColumnDimension('N')->setWidth(100);
                    $objActSheet->setCellValue('N1', '失败原因');
                }

                $index = 2;
//                fdump_api(['$error_arr'=>$error_arr],'$log11');
                foreach ($error_arr as $value) {
                    $objActSheet->setCellValueExplicit('A' . $index, $value['A']);
                    $objActSheet->setCellValueExplicit('B' . $index, $value['B']);
                    $objActSheet->setCellValueExplicit('C' . $index, $value['C']);
                    $objActSheet->setCellValueExplicit('D' . $index, $value['D']);
                    $objActSheet->setCellValueExplicit('E' . $index, $value['E']);
                    $objActSheet->setCellValueExplicit('F' . $index, $value['F']);
                    $objActSheet->setCellValueExplicit('G' . $index, $value['G']);
                    $objActSheet->setCellValueExplicit('H' . $index, $value['H']);
                    $objActSheet->setCellValueExplicit('I' . $index, $value['I']);
                    $objActSheet->setCellValueExplicit('J' . $index, $value['J']);
                    $objActSheet->setCellValueExplicit('K' . $index, $value['K']);
                    $objActSheet->setCellValueExplicit('L' . $index, $value['L']);
                    $objActSheet->setCellValueExplicit('M' . $index, $value['M']);
                    $objActSheet->setCellValueExplicit('N' . $index, $value['N']);
                    if ($is_new_charge) {
                        $value['O'] = str_replace('"','',$value['O']);
                        $value['N'] = str_replace('"','',$value['P']);
                        $objActSheet->setCellValueExplicit('O' . $index, $value['O']);
                        $objActSheet->setCellValueExplicit('P' . $index, $value['P']);
                        $objActSheet->setCellValueExplicit('Q' . $index, $value['Q']);
                    }
                    $index++;
                }
                $param['rand_number'] = $param['rand_number']?$param['rand_number']:time();
                $filename = $title . date("Y-m-d", time()) . '-' . '.xls';
                $objWriter = PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');
                $objWriter->save('./runtime/' . $filename);
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
                header('Content-Disposition:attachment;filename="' . $title . '_' . date("Y-m-d h:i:s", time()) . '.xls"');
                header("Content-Transfer-Encoding:binary");
                $objWriter->save('php://output');
                exit();
            }
            return true;
        }
    }


    public function getRoomEndTime($room_id,$village_id=0,$property_id=0,$isDelay=false)
    {
        if (!$village_id) {
            $conditionRoom = [];
            $conditionRoom['pigcms_id'] = $room_id;
            $village_id = $this->where($conditionRoom)->getField('village_id');
        }
        if (!$property_id) {
            $conditionVillage = [];
            $conditionVillage['village_id'] = $village_id;
            $property_id = D('House_village')->where($conditionVillage)->getField('property_id');
        }
        // 存在物业id，查询下物业新版收费生效与否 确定物业服务时间截止
        $is_new_charge=D('House_village')->checkNewCharge($property_id);
        $arr = [];
        $arr['village_id'] = $village_id;
        $arr['property_id'] = $property_id;
        if ($is_new_charge) {
            // 新版生效了  查询下
            $whereNewOrder = array('room_id'=>$room_id, 'order_type'=>'property');
            //查当前房间的服务结束时间
            $order_log = D('House_new_order_log')->where($whereNewOrder)->order('id DESC')->find();
            //查当前房间的服务开始时间
            $order_log_start = D('House_new_order_log')->where($whereNewOrder)->order('id ASC')->find();
            $contract_time=D('House_village')->getContractTime($room_id);
            if ($contract_time['contract_time_start']==0&&empty($order_log)){
                $arr['propertyStartTime'] = 0;
                $arr['propertyEndTime'] = 0;
            }elseif($contract_time['contract_time_start']==0&&!empty($order_log)){
                $arr['propertyStartTime'] = $order_log_start['service_start_time'];
                $arr['propertyEndTime'] = $order_log['service_end_time'];
            }elseif($contract_time['contract_time_start']!=0&&empty($order_log)){
                $arr['propertyStartTime'] = $contract_time['contract_time_start'];
                $arr['propertyEndTime'] = $contract_time['contract_time_end'];
            }elseif($contract_time['contract_time_start']!=0&&!empty($order_log)){
                if ($contract_time['contract_time_start']<$order_log_start['service_start_time']){
                    $arr['propertyStartTime'] = $order_log_start['service_start_time'];
                }else{
                    $arr['propertyStartTime'] = $contract_time['contract_time_start'];
                }
                $arr['propertyEndTime'] = $order_log['service_end_time'];
            }
            if ($isDelay && isset($param['owe_property_open_door']) && isset($arr['propertyEndTime']) && isset($param['owe_property_open_door_day']) && $param['owe_property_open_door_day'] && $param['owe_property_open_door']&& $arr['propertyEndTime']>0) {
                $arr['propertyEndTime'] +=  intval($param['owe_property_open_door_day']) * 86400;
            } else if ($isDelay && isset($param['owe_property_open_door']) && isset($arr['propertyEndTime']) && isset($param['owe_property_open_door_day']) && !$param['owe_property_open_door_day'] && $param['owe_property_open_door']&& $arr['propertyEndTime']>0) {
                // 允许延期开门 且为0 默认不限制 2035
                $arr['propertyEndTime'] =  2082729599;
            }
        }
        return $arr;
    }

 


    //todo 校验定制三表水表表号唯一性
    public function checkVacancyNum($village_id,$vacancy_id,$data=[]){
        if(!C('config.customized_meter_reading') || empty($data)){
            return ['error'=>true,'msg'=>'非定制/无数据操作'];
        }
        $data['water_number']=trim($data['water_number']);
        $data['heat_water_number']=trim($data['heat_water_number']);
        if(!empty($data['water_number']) && !empty($data['heat_water_number']) && $data['water_number'] == $data['heat_water_number']){
            return ['error'=>false,'msg'=>'该冷/热水表表号不可相同，请重新添加'];
        }
        $House_village_vacancy_num= M('House_village_vacancy_num');
        $vacancy_num=$House_village_vacancy_num->where(['vacancy_id'=>$vacancy_id])->field('id')->find();
        $where_str='(village_id = '.$village_id.')';
        if(!empty($data['water_number']) && !empty($data['heat_water_number'])){ //存在冷/热水水表号
            $where_str.=' AND ((water_number = "'.$data['water_number'].'" AND water_number <> "") OR (heat_water_number = "'.$data['heat_water_number'].'" AND heat_water_number <> "") OR (water_number = "'.$data['heat_water_number'].'" AND water_number <> "") OR (heat_water_number = "'.$data['water_number'].'" AND heat_water_number <> ""))';
        }else{ //存在冷或热水水表号
            $number='';
            if(!empty($data['water_number'])){
                $number=$data['water_number'];
            }elseif (!empty($data['heat_water_number'])){
                $number=$data['heat_water_number'];
            }
            if(!empty($number)){
                $where_str.=' AND ((water_number = "'.$number.'" AND water_number <> "") OR (heat_water_number = "'.$number.'" AND heat_water_number <> ""))';
            }
        }
        if($vacancy_num){
            $where_str.=' AND (id <> '.$vacancy_num['id'].')';
        }else{
            $where_str.=' AND (vacancy_id = '.$vacancy_id.')';
        }
        $res=$House_village_vacancy_num->where($where_str)->field('id,water_number,heat_water_number')->find();
        if($res){
            $water_type_arr=[
                $res['water_number'],
                $res['heat_water_number']
            ];
            if(in_array($data['water_number'],$water_type_arr)){
                return ['error'=>false,'msg'=>'该冷水表表号【'.$data['water_number'].'】已存在，请重新添加'];
            }elseif (in_array($data['heat_water_number'],$water_type_arr)){
                return ['error'=>false,'msg'=>'该热水表表号【'.$data['heat_water_number'].'】已存在，请重新添加'];
            }
        }
        $data['village_id']=$village_id;
        if($vacancy_num){
            $House_village_vacancy_num->where(['vacancy_id'=>$vacancy_id])->save($data);
        }else{
            $data['vacancy_id']=$vacancy_id;
            $res=$House_village_vacancy_num->add($data);
            if(!$res){
                fdump_api(['获取$res=='.__LINE__,$village_id,$vacancy_id,$data,$House_village_vacancy_num->_sql()],'checkVacancyNum/error_log',1);
                return ['error'=>false,'msg'=>'操作失败,请重试'];
            }
        }
        return ['error'=>true,'msg'=>'操作成功'];

    }

 
    public function vacancy_iccard_del($idd=0,$village_id=0){
        //$idd=isset($params['id']) ? $params['id']:0;
        //$village_id=isset($params['village_id']) ? $params['village_id']:0;
        if (!empty($idd) && $village_id>0){
            $card_info=M('House_village_vacancy_iccard')->where(array('id' => $idd,'status'=>1))->find();
            $res=M('House_village_vacancy_iccard')->where(array('id' => $idd,'status'=>1))->data(['status'=>4])->save();
            if ($res>0){
                $user_info=D('House_village_user_bind')->where(['village_id'=>$village_id,'vacancy_id'=>$card_info['vacancy_id'],'type'=>0,'status'=>1])->field('single_id,floor_id,layer_id,vacancy_id,type,parent_id,pigcms_id,uid,phone,status,property_starttime,property_endtime')->find();
                $device_list=D('House_face_device')->where(['village_id'=>$village_id,'is_del'=>0])->field('device_type,device_id,device_name,device_sn,floor_id')->select();
                if (!empty($device_list)&&!empty($user_info)){
                    foreach ($device_list as $kk => &$vv) {
                        if (in_array($vv['device_type'],[26])){
                            D('Face_d6_request_record')->del_card_record($card_info['ic_card'],$vv['device_id'],$user_info['pigcms_id'],$card_info['village_id']);
                        }
                    }
                }
                return array('status'=>1,'idd'=>$idd,'village_id'=>$village_id,'msg'=>'删除成功！');
            }
        }
        return array('status'=>0,'idd'=>$idd,'village_id'=>$village_id,'msg'=>'删除失败！');
    }


    //迁移代码到model
    public function check_add_vacancy($village_id,$param=[]){
        $house_village = D('House_village')->where(array('village_id'=>$village_id))->find();
        $arr['qrcode_url'] = '';
        $arr['qrcode_url_status'] = 3;
        //如果APP webview，直接处理为无须关注
        if(isset($param['is_app_browser']) && $param['is_app_browser']){
            $house_village['is_set_public_num'] = 3;
        }
        if($house_village['is_set_public_num'] != 3){
            if($house_village){
                $weixin_bind = D('Weixin_bind')->where(array('mer_id' => $house_village['property_id'], 'type' => 2))->find();
            }else{
                $weixin_bind = array();
            }
            //判断物业是否有公众号
            if(!$weixin_bind){
                //增加渠道二维码以及物业二维码
                $user = D('User')->where(['uid'=>$param['_uid']])->find();
                if($user['openid'] ){//有,则关注过
                    if($user['is_follow']){
                        $arr['qrcode_url'] = '';
                        $arr['qrcode_url_status'] = 3;
                    }else{
                        ## 加强是否关注的检测，避免对用户的干扰 start
                        $access_token_array = D('Access_token_expires')->get_access_token();
                        if (!$access_token_array['errcode']) {
                            import('ORG.Net.Http');
                            $http = new Http();
                            $return = $http->curlGet('https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . $access_token_array['access_token'] . '&openid=' . $user['openid'] . '&lang=zh_CN');
                            $userifo = json_decode($return, true);
                            if ($userifo['subscribe']) {
                                D('User')->save_user($user['uid'], 'is_follow', '1');
                                $house_village['is_set_public_num'] = 3;
                            }
                        }
                        ## 加强是否关注的检测，避免对用户的干扰 end
                        //未关注
                        if($house_village){
                            if($house_village['is_set_public_num'] == 1){//强制关注
                                $pay_qrcode_url= $qrcode_return = D('Recognition')->get_tmp_qrcode((2700000000 + $param['pigcms_id']));
                                //二维码
                                if($pay_qrcode_url['error_code'] == false){
                                    $arr['qrcode_url'] = $pay_qrcode_url['ticket'];
                                }else{
                                    $arr['qrcode_url'] = $param['wechat_qrcode'];
                                }
                                $arr['qrcode_url_status'] = 1;
                            }elseif($house_village['is_set_public_num'] == 2){//可选关注
                                $pay_qrcode_url= $qrcode_return = D('Recognition')->get_tmp_qrcode((2700000000 + $param['pigcms_id']));
                                //二维码
                                if($pay_qrcode_url['error_code'] == false){
                                    $arr['qrcode_url'] = $pay_qrcode_url['ticket'];
                                }else{
                                    $arr['qrcode_url'] = $param['wechat_qrcode'];
                                }
                                $arr['qrcode_url_status'] = 2;
                            }elseif($house_village['is_set_public_num'] == 3){//无需关注
                                $arr['qrcode_url'] = '';
                                $arr['qrcode_url_status'] = 3;
                            }
                        }
                    }
                }else{	//未关注
                    if($house_village  &&  ($house_village['is_set_public_num'] == 1 || $house_village['is_set_public_num'] == 2)){  //强制关注，可选关注
                        $pay_qrcode_url= $qrcode_return = D('Recognition')->get_tmp_qrcode((2700000000 + $param['pigcms_id']));
                        //二维码
                        if($pay_qrcode_url['error_code'] == false){
                            $arr['qrcode_url'] = $pay_qrcode_url['ticket'];
                        }else{
                            $arr['qrcode_url'] = $param['wechat_qrcode'];
                        }
                        $arr['qrcode_url_status'] = $house_village['is_set_public_num']  == 1 ? 1 : 2;
                    }elseif($house_village['is_set_public_num'] == 3){ //无需关注
                        $arr['qrcode_url'] = '';
                        $arr['qrcode_url_status'] = 3;
                    }
                }
            }else{
                if($house_village){
                    $weixin_bind_user = M('Weixin_bind_user')->where(['uid'=>$param['_uid'], 'property_id' => $house_village['property_id']])->find();
                    if(empty($weixin_bind_user)){
                        if ($param['is_weixin_browser']) {
                            $rtn = D('Weixin_bind')->is_property_weixin($house_village['property_id'],$param['_uid']);
                            if($rtn['error'] == 1001){
                                $arr['redirect_uri'] = $param['site_url'].$rtn['redirect_uri'];
                            }else{
                                $arr['redirect_uri'] = '';
                            }
                        } else {
                            $arr['redirect_uri'] = '';
                        }
                    }
                    if($weixin_bind_user && $weixin_bind_user['is_follow']){
                        $arr['qrcode_url'] = '';
                        $arr['qrcode_url_status'] = 3;
                    }else{
                        if($house_village  &&  ($house_village['is_set_public_num'] == 1 || $house_village['is_set_public_num'] == 2)){  //强制关注，可选关注

                            $url = urlencode($weixin_bind['qrcode_url']);
                            $arr['qrcode_url'] = D('Image')->wx_image_download($url,$house_village['property_id']);
                            $arr['qrcode_url_status'] = $house_village['is_set_public_num']  == 1 ? 1 : 2;
                        }elseif($house_village['is_set_public_num'] == 3){ //无需关注
                            $arr['qrcode_url'] = '';
                            $arr['qrcode_url_status'] = 3;
                        }
                    }
                }
            }
        }
        return $arr;
    }

}
