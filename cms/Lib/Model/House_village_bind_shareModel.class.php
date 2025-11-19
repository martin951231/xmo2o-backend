<?php
class House_village_bind_shareModel extends Model
{
	// 获取 用户的关联分享相关信息
	private function bind_share_msg($uid){
        $share_where = array(
            'a.uid' =>$uid,
            'd.status' => 1
        );
        $day_txt = date('Y-m-d');
        $share_where['_string'] = "(b.share_time_type=3 AND b.share_use_day='{$day_txt}') OR b.share_time_type!=3";
        $share_info = M('House_village_bind_share')->alias('a')
            ->field('a.uid, a.id as bind_id, b.share_end_time, b.share_uid, c.village_name, c.village_address, d.pigcms_id, d.vacancy_id, d.parent_id, d.type, d.name, d.village_id, d.floor_id, d.property_price, d.property_endtime, d.phone, d.open_door_day, c.owe_property_open_door, c.owe_property_open_door_day')
            ->join('LEFT JOIN pigcms_house_village_door_share as b on b.door_share_id=a.door_share_id')
            ->join('LEFT JOIN pigcms_house_village as c on b.village_id=c.village_id')
            ->join('LEFT JOIN pigcms_house_village_user_bind as d on b.share_uid=d.uid and b.village_id=d.village_id')
            ->where($share_where)
            ->select();
        if (!$share_info) $share_info = array();
        return $share_info;
	}


	// 获取 用户从分享获取到的门禁
	/**
	 * @param int $uid 当前获取的开门用户uid
	 * @param array $open_list_info 相关数据-层次为  小区-门禁-具体信息
	 * @param array $door_list_info 门禁信息存储
	 * @param array $floor_list_info 单元信息存储
	 * @param array $aDoor_is_open 可以开门的信息存储  其中蓝牙关键字 以 ly_ + 蓝牙门禁id  人脸门禁未 face_ + 人脸门禁id
     * @param int $face_device_type
	 * @param array $params 额外参数
	 * @return array
	 */
	public function bind_share_door_info($uid, $open_list_info = array(), $door_list_info = array(),  $aDoor_is_open = array(),$face_device_type=0,$params =[]){

		$share_info = $this->bind_share_msg($uid);
		if (empty($open_list_info)) {
			$open_list_info = array();
		}

		$m_house_village_door_user = M('House_village_door_user');
		$m_house_village_door = M('House_village_door');
		$m_house_village_user_bind = M('House_village_user_bind');
		$d_house_face_device = D('House_face_device');

		$aDoor = array();
		$invalid_share_arr = array();
		// 是否有蓝牙门禁
		$is_ly = false;
		// 是否有人脸门禁
		$is_face = false;
		// 存储信息
		$bind_info = array();
        // 公共区域直接获取
        $db_house_village_public_area = M('House_village_public_area');
        $house_village_public_area = $db_house_village_public_area->getField('public_area_id,public_area_name');
		if ($share_info) {
			$now_time = time();
            $village_ids = array_diff(array_unique(array_column($share_info, 'village_id')), [0, '0', null]);
            $whereChannel = [
                'isDel' => 0,
                'device_type' => 'house_face_device',
                'village_id' => ['in', $village_ids]
            ];
            $deviceChannels = M('House_device_channel')->where($whereChannel)->select();
            $deviceChannelArr = [];
            foreach ($deviceChannels as $channel) {
                !isset($deviceChannelArr[$channel['device_id']]) && $deviceChannelArr[$channel['device_id']] = [];
                $deviceChannelArr[$channel['device_id']][] = $channel;
            }
            unset($channel);
            $condition_face_door = [];
            $condition_face_door['is_del'] = 0;
            $condition_door['village_id'] = ['in', $village_ids];
            $aFaceDoorList = $d_house_face_device->field(true)->where($condition_face_door)->order('add_time DESC, device_id DESC')->select();
            foreach ($aFaceDoorList as $door2) {
                $is_face = true;
                !isset($door_list_info[$door2['village_id']]) && $door_list_info[$door2['village_id']] = [];
                if (isset($deviceChannelArr[$door2['device_id']]) && $deviceChannelArr[$door2['device_id']]) {
                    if (count($deviceChannelArr[$door2['device_id']]) == 1) {
                        $channel = reset($deviceChannelArr[$door2['device_id']]);
                        $door2['device_id'] = 'hik_channel_' . $channel['channel_id'];
                        $door_list_info[$door2['village_id']][] = $door2;
                    } else {
                        foreach ($deviceChannelArr[$door2['device_id']] as $channelItem) {
                            $door2['device_id'] = 'hik_channel_' . $channelItem['channel_id'];
                            $door2['device_name'] = $channelItem['channelName'];
                            $door_list_info[$door2['village_id']][] = $door2;
                        }
                    }
                } else {
                    $door_list_info[$door2['village_id']][] = $door2;
                }
            }
            unset($door2);

            $condition_door = [];
            $condition_door['door_status'] = 1;
            $condition_door['village_id'] = ['in', $village_ids];
            $aDoorList = $m_house_village_door->field(true)->where($condition_door)->select();
            foreach ($aDoorList as $door1) {
                $is_ly = true;
                !isset($door_list_info[$door1['village_id']]) && $door_list_info[$door1['village_id']] = [];
                $door_list_info[$door1['village_id']][] = $door1;
            }
            unset($door1);
            
			foreach ($share_info as $val) {
                if(isset($val['open_door_day']) && $val['open_door_day']>0){
                    $val['owe_property_open_door_day']=$val['open_door_day'];
                }
				if ($val['type'] == 1 || $val['type'] == 2) {
					// 家属和租客 物业费信息以业主为准
					if ($bind_info[$val['parent_id']]) {
						$val['property_price'] = $bind_info[$val['parent_id']]['property_price'];
						$val['property_endtime'] = $bind_info[$val['parent_id']]['property_endtime'];
					} else {
						$info = $m_house_village_user_bind->where(array('pigcms_id' => $val['parent_id']))->field('property_price, property_endtime')->find();
						$val['property_price'] = $info['property_price'];
						$val['property_endtime'] = $info['property_endtime'];
					}
				}
				if ($val['type'] == 4) {
					continue;
					// 获取工作人员信息
//                    // 考虑到安全问题，工作人员门禁权限不可分享
				} else {
					$bind_info[$val['pigcms_id']] = $val;

					// 单独处理-由于工作人员不处理，放置到非工作人员绑定
					if (!$open_list_info[$val['village_id']]) {
						// 处理小区层信息
						$open_list_info[$val['village_id']] = array();
						$open_list_info[$val['village_id']]['village_name'] = $val['village_name'];
						$open_list_info[$val['village_id']]['village_address'] = $val['village_address'];
					}
					if (!$open_list_info[$val['village_id']]['door_list']) {
						// 放置门禁信息数组
						$open_list_info[$val['village_id']]['door_list'] = array();
					}


					if ($val['share_end_time'] > $now_time) {
						$val['time_limit'] = 1; // 有效
					} else {
						$val['time_limit'] = 2; // 失效
						$invalid_share_arr[$val['bind_id']] = $val['bind_id'];
					}

					// 获取门禁
					if ($door_list_info && $door_list_info[$val['village_id']]) {
						$aDoorList = $door_list_info[$val['village_id']];
					} else {
                        if(isset($params['DEVICE_ID'])&&($params['DEVICE_ID']=='wxapp' || $params['is_app_browser'])) {
                            // 小程序才能使用蓝牙 所以非小程序不进行蓝牙设备获取
                            $condition_door['door_status'] = 1;
                            $condition_door['village_id'] = $val['village_id'];
                            $aDoorList = $m_house_village_door->group('village_id, door_device_id')->field(true)->where($condition_door)->select();
                        } else {
                            $aDoorList = [];
                        }
						//访客 蓝牙开门/人脸开门 不展示远程开门 start 2020/12/7
						if($face_device_type !=2 ) {
                            // 获取对应的人脸门禁设备 离线状态设备正常展示
                            $condition_face_door['is_del'] = 0;
                            $condition_face_door['village_id'] = $val['village_id'];
                            $aFaceDoorList = $d_house_face_device->group('village_id, device_id')->field(true)->where($condition_face_door)->select();
                            if (!empty($aFaceDoorList) && !$is_face) {
                                $is_face = true;
                            }
                            if ($aFaceDoorList) {
                                $aDoorList = array_merge($aDoorList, $aFaceDoorList);
                            }
                        }
						$door_list_info[$val['village_id']] = $aDoorList;
					}

					if (empty($aDoorList)) {
						unset($open_list_info[$val['village_id']]);
						continue;
					} else {
						$open_list_info[$val['village_id']]['have_village_door'] = true;
						$open_list_info[$val['village_id']]['tip_info'] = '';
					}

					// 绑定的单元-即拥有该小区的大门和改单元门禁权限
					$village_floor_id = $val['floor_id'];
					// 物业门禁开锁到期时间处理
					$property_endtime = 0;

					// 允许开门情况下继续判断
					if (!$val['property_endtime']) {
						$ban_open_status = false;   // 是否禁止开门  false 可以开门  true 禁止开门
					} else {
						$ban_open_status = false; // 默认可以开门
						$owe_property_days = 0;
						if ( $val['property_endtime'] > 0) {
							// 计算 当前时间到物业截止时间的天数
							$owe_property_days = (strtotime(date('y-m-d', time())) - strtotime(date('y-m-d', $val['property_endtime']))) / 86400;
						}
						if (!$val['owe_property_open_door'] && $val['property_endtime']>0 && (time() > $val['property_endtime'])) {
							// 判断  不能欠款延期开门 且 当前时间大于 物业截止时间  开门禁止
							$ban_open_status = true;
						} else if ($val['owe_property_open_door_day'] > 0 && $owe_property_days >= 0 && $owe_property_days > $val['owe_property_open_door_day']) {
							// 判断  可以欠款延期开门 且 延期天数大于0 且  当前时间到物业截止时间的天数存在 且大于 延期天数 开门禁止
							$ban_open_status = true;
						}
						// 处理下开门到期时间
						if ($val['property_endtime'] > 0) {
							$property_endtime = $val['property_endtime'];
							if ($val['owe_property_open_door'] && $val['owe_property_open_door_day'] > 0) {
								$property_endtime +=  $val['owe_property_open_door_day'] * 86400;
							}
						}
					}

					foreach ($aDoorList as $kk => &$vv) {
                        $vv['pigcms_id'] = $val['pigcms_id'];
						if (isset($vv['device_id']) && $vv['device_id']) {
							$key = 'face_' . $vv['device_id'];
							$vv['key_type'] = 'face';
						} elseif (isset($vv['door_id']) && $vv['door_id']) {
							$key = 'ly_' . $vv['door_id'];
							$vv['key_type'] = 'ly';
						} else {
							fdump('--------------------'.__LINE__,'open_List', true);
							fdump($vv,'open_List', true);
							continue;
						}
                        $thirdProtocol = isset($vv['thirdProtocol']) ? intval($vv['thirdProtocol']) : 0;
                        if ($thirdProtocol == $d_house_face_device::DH_YUNRUI) {
                            $vv['videoIntercom']     = 1;
                            $vv['videoIntercomType'] = 'dh_yun_rui';
                            $vv['videoIntercomText'] = '大华云睿';
                        } elseif ($vv['device_type'] == 5) {
                            // 云眸内部协议暂时走和外部协议相同的
                            $vv['videoIntercom']     = 1;
                            $vv['videoIntercomType'] = 'hik_yun_mou_wai_bu';
                            $vv['videoIntercomText'] = '云牟外部独立协议';
                        } else {
                            $vv['videoIntercom']     = 0;
                            $vv['videoIntercomType'] = '';
                            $vv['videoIntercomText'] = '';
                        }
						// 如果已经存在业主开门权限且到期时间长于分享的截止时间，后续操作将不再进行，直接使用业主开门权限
						if ($open_list_info[$val['village_id']] && $open_list_info[$vv['village_id']]['door_list'] && $open_list_info[$vv['village_id']]['door_list'][$key]) {
							$door_end_time = $open_list_info[$vv['village_id']]['door_list'][$key]['door_end_time'];
							if (strtotime($door_end_time) <= 0) {
								continue;
							} elseif (strtotime($door_end_time) >0 && $property_endtime > 0 && strtotime($door_end_time) > $property_endtime) {
								continue;
							} elseif (strtotime($door_end_time) >0 && $val['share_end_time'] > 0 && strtotime($door_end_time) > $val['share_end_time']) {
								continue;
							}
						}
                        $floor_arr = [];
						if (!empty($aDoor_is_open) && !empty($aDoor_is_open[$key])) {
							// 门禁信息已经查询过，直接赋予新的状态
							$vv = $aDoor_is_open[$key];
						} elseif ($vv['floor_id'] != "-1") {
                            $floor_arr = explode(',',$vv['floor_id']);
                            if (count($floor_arr)==1) {
                                $floor_id = $floor_arr[0];
                            } else {
                                $floor_id = 0;
                            }
                            if ($floor_id>0) {
                                $vv['floor_name'] = $vv['device_name']?$vv['device_name']:$vv['door_name'];
                                $vv['floor_layer'] = '';
                            } else if($floor_arr && !$floor_id) {
                                $vv['floor_name'] = $vv['device_name'];
                                $vv['floor_layer'] = '';
                            }
							$vv['door_type'] = 1; // 小区单元门
						} elseif (isset($vv['public_area_id']) && $vv['public_area_id']>0) {
                            $house_name = C('config.house_name') ? C('config.house_name').L_('大门') : L_('小区').L_('大门');
                            $vv['floor_name'] = $house_village_public_area[$vv['public_area_id']] ? $house_village_public_area[$vv['public_area_id']] : $house_name;
                            $vv['floor_layer'] = $vv['device_name'];
                            $vv['door_type'] = 2; // 小区大门
                        } else {
                            $vv['floor_name'] = $vv['device_name']?$vv['device_name']:$vv['door_name'];
							$vv['floor_layer'] = '';
							$vv['door_type'] = 2; // 小区大门
						}
                        $floor_name_arr = explode((D('House_village')->getHouseAlias('single_name')?:'栋'), $vv['floor_name']);
                        $lens = mb_strlen($vv['floor_name']);
                        if (count($floor_name_arr)==2) {
                            $vv['device_name_top'] = $floor_name_arr[0].(D('House_village')->getHouseAlias('single_name')?:'栋');
                            $vv['device_name_txt'] = $floor_name_arr[1];
                        } elseif ($lens>4 && $lens<7) {
                            $vv['device_name_top'] = mb_substr($vv['floor_name'],0,2);
                            $vv['device_name_txt'] = mb_substr($vv['floor_name'],2,$lens-2);
                        } elseif ($lens>=7) {
                            $vv['device_name_top'] = mb_substr($vv['floor_name'],0,4);
                            $vv['device_name_txt'] = mb_substr($vv['floor_name'],4,$lens-2);
                        } else {
                            $vv['device_name_top'] = '';
                            $vv['device_name_txt'] = $vv['floor_name'];
                        }
						$vv['door_user_type'] = 2; // 临时访客
						if ($property_endtime > 0 && $property_endtime < $val['share_end_time']) {
							$vv['door_end_time'] = $property_endtime>0?date('Y-m-d H:i', $property_endtime):0;
						} else {
							$vv['door_end_time'] = $val['share_end_time']>0?date('Y-m-d H:i', $val['share_end_time']):0;
						}
						if (isset($vv['door_id']) && $vv['door_id']>0) {
							$userWhere = array(
								'user_id' => $uid,
								'door_fid' => $vv['door_id'],
							);
							$aDoorFind = $m_house_village_door_user->field(true)->where($userWhere)->find();
						} else {
							$aDoorFind = array();
						}
                        if (!$ban_open_status && $vv['thirdChnNum'] && ! empty($floor_arr) && intval($val['vacancy_id']) > 0) {
                            $whereRoom = [];
                            $whereRoom['pigcms_id'] = $val['vacancy_id'];
                            $whereRoom['room'] = ['like', '%' . $vv['thirdChnNum']];
                            $roomInfo = M('House_village_user_vacancy')->where($whereRoom)->find();
                            if (!$roomInfo) {
                                $ban_open_status = true;
                            }
                        }
						if ($val['time_limit'] == 2) {
							$aDoorList[$kk]['open_status'] = 6;    // 门禁超时
							$aDoorList[$kk]['open_status_txt'] = L_('分享获得门禁已经超时，不能开门');
							$open_list_info[$vv['village_id']]['door_list'][$key] = $vv;
						} elseif (!$ban_open_status && empty($aDoorFind) && ($vv['floor_id'] == -1 || intval($vv['floor_id']) === 0)) {
							if (empty($aDoor_is_open) || empty($aDoor_is_open[$key])) {
								$vv['open_status'] = 1;
								$vv['open_status_txt'] = L_('可以打开');
								$aDoor[] = $vv;
								$aDoor_is_open[$key] = $vv;
							}
							$open_list_info[$vv['village_id']]['door_list'][$key] = $vv;
						} elseif (!$ban_open_status && empty($aDoorFind) && ($village_floor_id==0 || $vv['floor_id'] == $village_floor_id || in_array($village_floor_id, $floor_arr))) {
							if (empty($aDoor_is_open) || empty($aDoor_is_open[$key])) {
								$vv['open_status'] = 1;
								$vv['open_status_txt'] = L_('可以打开');
								$aDoor[] = $vv;
								$aDoor_is_open[$key] = $vv;
							}
                            if (!$is_ly&&$vv['key_type']=='ly') {
                                $is_ly = true;
                            }
							$open_list_info[$vv['village_id']]['door_list'][$key] = $vv;
						} elseif (!$ban_open_status && $aDoorFind['status'] == 1 && ($village_floor_id==0 || $vv['floor_id'] == $village_floor_id || in_array($village_floor_id, $floor_arr))) {
							if ($aDoorFind == 0 || time() > $aDoorFind['end_time']) {
								if (empty($aDoor_is_open) || empty($aDoor_is_open[$key])) {
									$vv['open_status'] = 1;
									$vv['open_status_txt'] = L_('可以打开');
									$aDoor[] = $vv;
									$aDoor_is_open[$key] = $vv;
								}
                                if (!$is_ly&&$vv['key_type']=='ly') {
                                    $is_ly = true;
                                }
							} else {
								$vv['open_status']	=	2;	//时间过期
								$vv['open_status_txt']	=	L_('已过期，请联系物业');
							}
							$open_list_info[$vv['village_id']]['door_list'][$key] = $vv;
						} elseif (!$ban_open_status && $aDoorFind['status'] == 0 && ($village_floor_id==0 || $vv['floor_id'] == $village_floor_id || in_array($village_floor_id, $floor_arr))) {
							$vv['open_status']	=	4;		//禁止使用
							$vv['open_status_txt']	=	L_('分享者已被禁止使用');		//禁止使用
							$open_list_info[$vv['village_id']]['door_list'][$key] = $vv;
						} elseif ($ban_open_status && ($village_floor_id==0 || $vv['floor_id'] == $village_floor_id || in_array($village_floor_id, $floor_arr))) {
							$vv['open_status']	=	3;//欠物业费
							$vv['open_status_txt']	=	L_('分享者已欠物业费');
							$open_list_info[$vv['village_id']]['door_list'][$key] = $vv;
						} elseif ($ban_open_status && $vv['floor_id'] == -1) {
							$vv['open_status']	=	3;//欠物业费
							$vv['open_status_txt']	=	L_('分享者已欠物业费');
							$open_list_info[$vv['village_id']]['door_list'][$key] = $vv;
						}
						if (empty($open_list_info[$vv['village_id']]['door_list']) && $open_list_info[$vv['village_id']]['have_village_door']) {
							$open_list_info[$vv['village_id']]['have_floor_door'] = false;
							$open_list_info[$vv['village_id']]['tip_info'] = L_('分享者入住的单元暂未添加门禁');
						} else {
							$open_list_info[$vv['village_id']]['have_floor_door'] = true;
						}
                        $open_list_info[$vv['village_id']]['village_id'] = $vv['village_id'];
					}
				}
			}

		}
		$share_info = array(
			'aDoor' => $aDoor,
			'open_list_info' => $open_list_info,
			'invalid_share_arr' => $invalid_share_arr,
			'is_ly' => $is_ly,
			'is_face' => $is_face,
		);
		return $share_info;
	}


	/**
	 * 添加分享信息
	 * @param $village_id
	 * @param $share_data
	 * array(
	 *		'share_uid' => '分享者uid'，
	 *		'open_uid' => '被分享使用用户id'，
	 *		'share_info' => '分享信息'，
	 *		'share_address' => '分享所在地址'，
	 *		'share_use_day' => '分享给用户的具体使用日期时间 例如 2020-04-08'， // 记录的值是 date('Y-m-d')
	 * )
	 * @return mixed
	 */
	public function add_visitor_share($village_id,$share_data) {
		if ($share_data['share_use_day']) {
			$share_data['share_end_time'] = strtotime($share_data['share_use_day'] . ' 23:59:59');
		}
		$share_data['share_time'] = time();
		$share_data['share_time_type'] = 3;
		$share_data['village_id'] = $village_id;
		$add_id = M('House_village_door_share')->data($share_data)->add();
		return $add_id;
	}

	/**
	 * 绑定分享信息
	 * @param $door_share_id
	 * @param $uid
	 * @return array
	 *  {
	 *		'error' => 1,// 1时返回错误 msg 为对应错误信息 0 为正常
	 *		'msg' => '该分享已被其他人使用，请联系业主重新生成',
	 *		'add_id' => 绑定后的id,
	 * }
	 */
	public function bind_share($door_share_id, $uid) {

		$door_share = M('House_village_bind_share')->where(array('door_share_id'=>$door_share_id))->find();
		$ret = array(
			'error' => 0,
			'msg' => ''
		);
		if ($door_share && $door_share['uid'] != $uid) {
			$ret['error'] = 1;
			$ret['msg'] = '该分享已被其他人使用，请联系业主重新生成。';
		} elseif (!$door_share) {
			$data = array(
				'door_share_id' => $door_share_id,
				'uid' => $uid,
				'add_time' => time(),
			);
			$add_id = M('House_village_bind_share')->data($data)->add();
			$ret['add_id'] = $add_id;
		}
		return $ret;
	}

}
?>