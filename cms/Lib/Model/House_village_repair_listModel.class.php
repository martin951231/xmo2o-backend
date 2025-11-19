<?php
class House_village_repair_listModel extends Model{
	
	public function getlist($column, $sizePage=20,$order = [])
	{
		if(!$column['village_id']){
			return '';
		}
		$condition_table = array(C('DB_PREFIX') . 'house_village_repair_list' => 'r', C('DB_PREFIX') . 'house_village_user_bind' => 'b');
		$condition_where = " r.village_id=" . $column['village_id'];
		if($column['type']){
			$condition_where .= " AND r.type = ".$column['type'];
		}
        if ($column['cate_fid']) {
            $condition_where .= " AND r.cate_fid = ".$column['cate_fid'];
        }

        if ($column['repair_type']) {
            $classify=D('House_village_repair_classify')->where(['id'=>$column['repair_type']])->find();
            if($classify && $classify['type'] > 0){
                $condition_where .= " AND r.repair_type = ".$classify['type'];
            }else{
                $condition_where .= " AND r.classify_id = ".$column['repair_type'];
            }
        }

        if ($column['cate_id']) {
			$condition_where .= " AND r.cate_id = ".$column['cate_id'];
		}

		if($column['wid']){
			$condition_where .= " AND r.wid = ".intval($column['wid']);
		}
		if($column['bind_id']){
			$condition_where .= " AND r.bind_id = ".intval($column['bind_id']);
		}
		
		if($column['pigcms_id']){
			$condition_where .= " AND r.pigcms_id = ".intval($column['pigcms_id']);
		}

		if($column['usernum']){
			$condition_where .= " AND (b.usernum like '%".$column['usernum']."%' OR b.bind_number like '%".$column['usernum']."%')";
		}elseif($column['phone']){
			$condition_where .= " AND b.phone like '%".$column['phone']."%'";
		}elseif($column['address']){
			$condition_where .= " AND b.address like '%".$column['address']."%'";
		}elseif($column['name']){
			$condition_where .= " AND b.name like '%".$column['name']."%'";
		}

		if($column['status'] > -1){
			$condition_where .= " AND r.status in (".$column['status'].')';
		}

		if($column['_string'] ){
			$condition_where .= " AND ".$column['_string'];
		}


		if (($column['begin_time'] > 0) && ($column['end_time'] > 0)) {
			$condition_where .= " AND r.time>'{$column['begin_time']}' AND r.time<'{$column['end_time']}'";
		}elseif($column['begin_time'] > 0){
			$condition_where .= " AND r.time>'{$column['begin_time']}'";
		}elseif($column['end_time'] > 0){
			$condition_where .= " AND r.time<'{$column['end_time']}'";
		}

		if(!empty($order) && $order['time']){
			$order = " r.time ".$order['time'];
		}elseif (!empty($order) && $order['sort_word']) {
			$order = " r.".$order['sort_word'] . ' DESC';
		}else{
			//$order = ' r.status ASC, r.pigcms_id DESC ';
			$order = ' `r`.`time` DESC ';
		}



		$condition_field = 'r.pigcms_id as pid,r.type as r_type,r.status as r_status,r.phone as repair_phone,r.*,b.pigcms_id,b.usernum,b.bind_number,b.name,b.phone,b.layer_num,b.floor_id,b.layer_id,b.vacancy_id,b.room_addrss,b.single_id,b.address,b.parent_id,w.name as worker_name,w.phone as worker_phone';


		import('@.ORG.merchant_page');
		$sql = 'SELECT count(1) AS cnt FROM ' . C('DB_PREFIX') . 'house_village_repair_list AS r LEFT JOIN ' . C('DB_PREFIX') . 'house_village_user_bind AS b ON r.village_id = b.village_id AND r.bind_id = b.pigcms_id LEFT JOIN ' . C('DB_PREFIX') . 'house_worker AS w ON w.village_id = r.village_id AND w.wid = r.wid';
		$sql .= ' WHERE' . $condition_where;
		$count_repair = $this->query($sql);
		$count_repair = isset($count_repair[0]['cnt']) ? intval($count_repair[0]['cnt']) : 0;
		
		if ($sizePage === -100) return $count_repair;
// 		$count_repair = D('')->table($condition_table)->where($condition_where)->count();

		$p = new Page($count_repair,$sizePage,'page');
		
		$sql = 'SELECT ' . $condition_field . ' FROM ' . C('DB_PREFIX') . 'house_village_repair_list AS r LEFT JOIN ' . C('DB_PREFIX') . 'house_village_user_bind AS b ON r.village_id = b.village_id AND r.bind_id = b.pigcms_id LEFT JOIN ' . C('DB_PREFIX') . 'house_worker AS w ON w.village_id = r.village_id AND w.wid = r.wid';
		$sql .= ' WHERE' . $condition_where . ' ORDER BY ' . $order . ' LIMIT ' . $p->firstRow.','.$p->listRows;
		$repair_list = $this->query($sql);
// 		$repair_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
		$return = array();

		$sing_arr = M('House_village_single')->where(array('village_id' => $column['village_id']))->getField('id,single_name');
		$floor_arr = M('House_village_floor')->where(array('village_id' => $column['village_id']))->getField('floor_id,floor_name,floor_layer');
		foreach ($repair_list as &$v) {
			if ($v['pic']) {
				$pic = explode('|', $v['pic']);
				$picArray = array();
				foreach ($pic as $picinfo) {
					if(substr($picinfo,1,6) == 'upload'){
						$picArray[] = file_domain() . $picinfo;
					}elseif(substr($picinfo,3,7) == '000/000'){
						$picArray[] = file_domain() . "/upload/activity/" . $picinfo;
					}else{
						$picArray[] = file_domain() . "/upload/house/" . $picinfo;
					}
				}
				$v['pic'] = $picArray;
			}else{
				$v['pic'] = array();
			}
			if ($v['reply_pic']) {
				$pic = explode('|', $v['reply_pic']);
				$picArray = array();
				foreach ($pic as $picinfo) {
                    if(substr($picinfo,1,6) == 'upload'){
                        $picArray[] = file_domain() . $picinfo;
//                    }elseif(substr($picinfo,3,7) == '000/000'){
//                        $picArray[] = file_domain() . "/upload/worker/" . $picinfo;
                    }else{
                        $picArray[] = file_domain() . "/upload/worker/" . $picinfo;
                    }
				}
				$v['reply_pic'] = $picArray;
			}else{
				$v['reply_pic'] = array();
			}
			
			if ($v['comment_pic']) {
				$pic = explode('|', $v['comment_pic']);
				$picArray = array();
				foreach ($pic as $picinfo) {
                    if(substr($picinfo,1,6) == 'upload'){
                        $picArray[] = file_domain() . $picinfo;
                    }elseif(substr($picinfo,3,7) == '000/000'){
                        $picArray[] = file_domain() . "/upload/house/" . $picinfo;
                    }else{
                        $picArray[] = file_domain() . "/upload/house/" . $picinfo;
                    }
				}
				$v['comment_pic'] = $picArray;
			}else{
				$v['comment_pic'] = array();
			}
			if ($v['waste_type']) {
				$v['waste_type_name'] = $this->waste_type($v['waste_type']);
			}

//			if(!$v['address']&&$v['parent_id']) {
//				$parent_info = M('House_village_user_bind')->field('address')->where(array('pigcms_id'=>$v['parent_id']))->find();
//				$v['address'] = $parent_info['address'];
//			}
			if ($v['pigcms_id']) {
				$floor_info = $floor_arr[$v['floor_id']];
				$word_data = array();
				if ($sing_arr[$v['single_id']] && $v['single_id']>0) {
					$sing_name = $sing_arr[$v['single_id']];
				} else {
					$sing_name = '';
				}
				if (!empty($floor_info)) {
					$word_data['floor_name'] = $floor_info['floor_name'];
					$word_data['layer'] = $v['layer_num'];
					$word_data['room'] = $v['room_addrss'];
					if (!$sing_name) {
						$sing_name = $floor_info['floor_layer'];
					}
				}
				$word_data['single_name'] = $sing_name;
				$room_diy_name = D('House_village')->word_replce_msg($word_data,$column['village_id']);
				$v['address'] = $room_diy_name;
			} else {
				$v['address'] = '';
			}


		}
		$return['pagebar'] = $p->show();
		$return['repair_list'] = $repair_list;
//		var_dump($repair_list);
		$return['pageCount'] = ceil($count_repair/$sizePage);
		return $return;
	}
	
	public function notice_work()
	{
		$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
		
		$sql = "SELECT openid, l.pigcms_id, l.type FROM " . C('DB_PREFIX') . "house_village AS v INNER JOIN " . C('DB_PREFIX') . "house_village_repair_list AS l ON l.village_id=v.village_id WHERE v.handle_type=1 AND l.time+v.hour*3600<" . time();
		
		$res = $this->query($sql);
		foreach ($res as $r) {
			if ($r['type'] == 1) {
				$first = '报修';
			} elseif ($r['type'] == 2) {
				$first = '水电煤';
			} else {
				$first = '投诉建议';
			}
			$href = C('config.site_url').'/wap.php?c=Customer&a=detail&pigcms_id=' . $r['pigcms_id'];
			$model->sendTempMsg('OPENTM405462911', 
				array(
					'href' => $href, 
					'wecha_id' => $r['openid'], 
					'first' => '您有一个' . $first . '需处理', 
					'keyword1' => $first, 
					'keyword2' =>'已发送',
					'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']), 
					'keyword4' => '物业', 
					'remark' => '\n请点击查看详细信息！'
				)
			);
		}

	}
	
	//小程序获取小区物业报修列表 - wangdong
	public function wxapp_getList($uid , $village_id , $bind_id , $type){
		
		$where['uid'] = $uid;
		$where['village_id'] = $village_id;
		$where['bind_id'] = $bind_id;
		$where['type'] = $type;
		$list = $this->field(true)->where($where)->select();

		return $list;
			
	}

	//废品属性选项
	public function waste_type($type=0){
		$waste_type =  array(
			1 => '玻璃制品',
			2 => '纸制品',
			3 => '金属制品',
			4 => '塑料制品',
			5 => '其他'
		);

		if ($type) {
			return $waste_type[$type];
		}else{
			return $waste_type;
		}
			
	}

	public function get_repair_cate($repair){
	    $House_village_repair_cate=M('House_village_repair_cate');
        $cate_name= '-';
        if($repair['cate_id']){
            $repai_cate = $House_village_repair_cate->where(array('id'=>$repair['cate_id']))->find();
            if($repai_cate){
                if($repai_cate['parent_id'] == 0){
                    $cate_name = $repai_cate['cate_name'];
                }else{
                    $repai_f_cate = $House_village_repair_cate->where(array('id'=>$repai_cate['parent_id']))->find();
                    if($repai_f_cate && $repai_cate){
                        $cate_name = $repai_f_cate['cate_name'].'&nbsp;&nbsp;-&nbsp;&nbsp;'.$repai_cate['cate_name'];
                    }elseif($repai_f_cate){
                        $cate_name = $repai_f_cate['cate_name'];
                    }
                }
            }
        }elseif($repair['cate_fid']){
            $repai_f_cate = $House_village_repair_cate->where(array('id'=>$repair['cate_fid']))->getField('cate_name');
            if($repai_f_cate){
                $cate_name = $repai_f_cate;
            }
        }
        return $cate_name;
    }
	
}