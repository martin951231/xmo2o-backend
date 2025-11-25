<?php
class Property_groupModel extends Model
{
    private $replace_code='42001'; // access_token已过期
    private $department_already_exists_code='60008'; // 部门已经存在
    private $user_already_exists_code='60102'; // 	UserID已存在
    private $type_arr = array(
        '0'=>'客服专员',
        '1'=>'维修技工',
//		'2'=>'物业人员',
        '3'=>'保洁人员',
        '4'=>'保安人员',
        '5'=>'招商开发',
        '6'=>'财务',
        '7'=>'人力资源',
        '8'=>'管理决策',
        '9'=>'后勤',
        '99'=>'其他',
    );
    private $http = '';
    private $property_id = '';

    private $first_fid = '1';


    // 同步组织架构至企业微信

    /**
     * @param int $property_id
     * @return bool
     */
    public function sync_to_enterprise_wechat($property_id=0,$village_id=0) {
//        $property = session('property');
//        if ($property && $property['id'] && !$property_id) {
//            $where['property_id'] = $property['id'];
//            $property_id = $property['id'];
//        }
        // 必须传值物业id
        $this->property_id = $property_id;
        $where = [];
        $where['bind_id'] = $property_id;
        $wx_bind_id = M('House_enterprise_wx_bind')->where($where)->getField('pigcms_id');
        if (!$property_id || empty($wx_bind_id)) {
            fdump_api(['参数缺失错误:'.__LINE__,$property_id,$wx_bind_id],'qyweixin/Enterprise/sync_to_enterprise_wechat_err_log',1);
            // 同步成功返回
            return true;
        }

        if (!$this->http) {
            import('ORG.Net.Http');
            $this->http = new Http();
        }

        // 优先查询下 未删除的组织架构 做添加或者更新部门信息处理
        $db_property_group = M('Property_group');
        $where_normal = [];
        $where_normal['property_id'] = $property_id;
        $where_normal['is_del'] = 0;
        $where_normal['status'] = 1;
        if ($village_id) {
            $where_normal['village_id'] = $village_id;
        }
        $property_group_list = $db_property_group->where($where_normal)->order('id ASC')->select();
        $token_info = array();
        $token_info = $this->department_list($token_info,0,false,$property_id);

        if (!$token_info || $token_info['errcode'] || !$token_info['access_token']) {
            fdump_api(['同步组织架构至企业微信token错误:'.__LINE__,$token_info,$property_group_list],'qyweixin/Enterprise/sync_to_enterprise_wechat_log',1);
            // 同步成功返回
            return true;
        }
        $add_success = [];
        if ($property_group_list) {
            fdump_api(['同步组织架构至企业微信:'.__LINE__,$this->first_fid,$db_property_group->getLastSql(),$property_group_list],'qyweixin/Enterprise/sync_to_enterprise_wechat_log',1);

            foreach ($property_group_list as $val) {
                if ($val['fid']) {
                    $parentid = $db_property_group->where(['id' => $val['fid']])->getField('create_id');
                    if (!$parentid) {
                        $parentid = $val['fid'];
                    }
                } else {
                    $parentid = 0;
                }
                $data = array(
                    'name' => $val['name'],
                    'parentid' => $parentid,
                    'order' => $val['sort'],
                    'id' => $val['id'],
                );
                if (!$val['create_id']) {
                    $token_info = $this->department_create($val['id'], $data, $token_info, false, $property_id);
                    if ($token_info) {
                        $add_success[] = $val['id'];
                    }
                } elseif ($val['create_id']) {
                    $token_info = $this->department_update($val['id'], $data, $token_info, false, $property_id);
                    $add_success[] = $val['id'];
                }
            }
        }
        // 查询下 人员
        $db_house_worker = M('House_worker');

        $where_del_worker = $where;
        // 处于非正常状态的工作人员均删除
        $where_del_worker['_string'] = '(is_del=1 OR status!=1)AND qy_status<>4';
        $del_house_worker = $db_house_worker->where($where_del_worker)->select();
        if ($del_house_worker) {
            // 先处理删除的工作人员
            foreach ($del_house_worker as $val1) {
                if ($val1['is_del']==1 && $val1['qy_id'] && $val1['qy_status']!=4) {
                    $token_info = $this->user_delete($val1['wid'], $token_info, false, $property_id);
                }
            }
        }

        $where_worker = $where;
        $where_worker['is_del'] = 0;
        $where_worker['status'] = 1;
        $house_worker_list = $db_house_worker->where($where_worker)->select();
        $position = $this->type_arr;
        if ($house_worker_list) {
            // 未删除的工作人员
            fdump_api(['人员:'.__LINE__,$db_house_worker->getLastSql(),$house_worker_list],'qyweixin/Enterprise/sync_to_enterprise_wechat_log',1);

            $house_worker_arr = [];
            foreach ($house_worker_list as $item) {
                // 如果组织添加失败 则  不做对应成员操作
                if ($add_success && !in_array($item['department_id'],$add_success)) {
                    continue;
                }
                if (!$item['department_id'] && $this->first_fid) {
                    $item['department_id'] = $this->first_fid;
                }
                if (isset($house_worker_arr[$item['phone']]) && !empty($house_worker_arr[$item['phone']])) {
                    if ($house_worker_arr[$item['phone']]['department']) {
                        $department = $house_worker_arr[$item['phone']]['department'];
                        $department[] = $item['department_id'];
                        $department = array_unique($department);
                        $house_worker_arr[$item['phone']]['department'] = $department;
                    } else {
                        $house_worker_arr[$item['phone']]['department'] = [$item['department_id']];
                    }
                } else {
                    $house_worker_arr[$item['phone']] = [
                        'userid' => 'worker_'.$item['wid'],
                        'name' => $item['name'],
                        'mobile' => $item['phone'],
                        'department' => [$item['department_id']],
                        'position' => $position[$item['type']],
                        'gender' => $item['gender'],
                        'enable' => $item['status'],
                        'wid' => $item['wid'],
                        'qy_id' => $item['qy_id'],
                        'is_del' => $item['is_del'],
                        'qy_status' => $item['qy_status'],
                    ];
                }
            }
            $house_worker_arr = array_values($house_worker_arr);
            fdump_api(['人员整理后数据:'.__LINE__,$house_worker_arr,$add_success],'qyweixin/Enterprise/sync_to_enterprise_wechat_log2',1);

            foreach ($house_worker_arr as $vals) {
                $worker_data = array(
                    'userid' => $vals['userid'],
                    'name' => $vals['name'],
                    'mobile' => $vals['mobile'],
                    'department' => $vals['department'],
                    'position' => $vals['position'],
                    'gender' => $vals['gender'],
                    'enable' => $vals['enable'],
                );
                if ($vals['is_del']!=1 && !$vals['qy_id']) {
                    $token_info = $this->user_create($vals['wid'], $worker_data, $token_info,$property_id);
                } elseif ($vals['is_del']!=1 && $vals['qy_id']) {
                    $worker_data['userid'] = $vals['qy_id'];
                    $token_info = $this->user_update($vals['wid'], $worker_data, $token_info,$property_id);
                } elseif ($vals['is_del']==1 && $vals['qy_id'] && $vals['qy_status']!=4) {
                    $token_info = $this->user_delete($vals['wid'], $token_info);
                }
            }
        }

        // 查询下组织架构 处于删除状态 且同步组织结构且未同步删除的组织
        $where_del = $where;
        $where_del['is_del'] = 1;
        $where_del['create_status'] = 1;
        $where_del['fid'] = array('gt', 0);
        $property_del_list = $db_property_group->where($where_del)->order('id ASC')->getField('id,id,fid,create_id,create_time');
        if ($property_del_list) {
            fdump_api(['未同步删除的组织:'.__LINE__,$db_property_group->getLastSql(),$property_del_list],'qyweixin/Enterprise/sync_to_enterprise_wechat_log',1);
            foreach ($property_del_list as $items){
                $token_info = $this->property_tree_del($items, $token_info, $del_arr);
            }
        }
        // 同步成功返回
        return true;
    }


    public function property_tree_del($property_info, $token_info, &$del_arr) {
        if (in_array($property_info['id'], $del_arr)) {
            fdump_api(['property_tree_del删除:'.__LINE__,$property_info,$token_info,$del_arr],'qyweixin/Enterprise/property_tree_del_log',1);
            return $token_info;
        }
        $db_property_group = M('Property_group');
        $next_info = $db_property_group->where(array('fid'=>$property_info['id'], 'create_status' => 1))->field('id,fid')->find();

        fdump_api(['property_tree_del删除:'.__LINE__,$property_info,$token_info,$next_info,$del_arr],'qyweixin/Enterprise/property_tree_del_log',1);

        if ($next_info) {
            $this->property_tree_del($next_info, $token_info, $del_arr);
        } else {
            $token_info = $this->department_delete($property_info['id'], $token_info, false,$property_info['property_id']);
            if ($token_info) {
                $del_arr[] = $property_info['id'];
                $prev_info = $db_property_group->where(array('id'=>$property_info['fid'], 'create_status' => 1, 'is_del' => 1))->find();
                if ($prev_info) {
                    $this->property_tree_del($prev_info, $token_info, $del_arr);
                } else {
                    return $token_info;
                }
            }
        }
    }

    // 获取通讯录应用目录
    public function get_address_book_token($property_id=0,$corpid='',$corpsecret='') {
//        if (!$corpid) {
//            if ($this->property_id) {
//                $where['bind_id'] = $this->property_id;
//            } else {
//                $property = session('property');
//                if ($property && $property['id']) {
//                    $where['bind_id'] = $property['id'];
//                }
//            }
//            $corpid = M('House_enterprise_wx_bind')->where($where)->getField('corpid');
//        }
        if (!$property_id && $this->property_id) {
            $property_id = $this->property_id;
        }
        if ($corpsecret && $corpid) {
            // 直接使用
        } elseif ($property_id) {
            $where['bind_id'] = $property_id;
            $access_obj = M('House_enterprise_wx_bind')->where($where)->field('corpid, qy_address_book_secret')->find();
            if ($access_obj['corpid'] && isset($access_obj['qy_address_book_secret']) && trim($access_obj['qy_address_book_secret'])) {
                $corpsecret = trim($access_obj['qy_address_book_secret']);
                $corpid = $access_obj['corpid'];
            }
        }
        if (!$corpid) {
            $where_orpid_ticket = [
                'name' => 'enterprise_wx_corpid'
            ];
            $corpid = M('Config')->where($where_orpid_ticket)->getField('value');
            $corpid = trim($corpid);
        }
        // 企业微信通讯录
        if (!$corpsecret) {
            $corpsecret = C('config.qy_address_book_secret')?C('config.qy_address_book_secret'):'';
        }
        $db_access_token_common_expires = M('Access_token_common_expires');
        $where_access_token = [
            'type' =>'address_book_token',
            'access_id' =>$corpid.'_'.$corpsecret,
        ];
        $access_token_info = $db_access_token_common_expires->where($where_access_token)->find();
        $now = time();
        // 过期时间  向前1800秒 提前刷新
        if ($access_token_info && intval($access_token_info['access_token_expire']) > intval($now + 1800)) {
            return array('errcode' => 0, 'access_token' =>$access_token_info['access_token']);
        } else {
            // 如果不存在或者过期  重新获取
            $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid={$corpid}&corpsecret={$corpsecret}";
            $result = $this->curlGet($url);
            if (!is_array($result)) {
                $result = json_decode($result,true);
            }
            if (empty($result['errcode'])) {
                $set = array(
                    'access_token' => $result['access_token'],
                    'access_token_expire' => $result['expires_in'] + $now,
                );
                if ($access_token_info) {
                    $db_access_token_common_expires->where($where_access_token)->data($set)->save();
                } else {
                    $set['type'] = 'address_book_token';
                    $set['access_id'] = $corpid.'_'.$corpsecret;
                    $db_access_token_common_expires->data($set)->add();
                }
                return array('errcode' => 0, 'access_token' => $result['access_token']);
            }
            fdump_api(['token获取失败:'.__LINE__,$corpid,$corpsecret,$url,$result],'qyweixin/Enterprise/get_address_book_token_err_log',1);
            return array('errcode' => $result['errcode'], 'errmsg' => $result['errmsg']);
        }

    }

    // 获取 同步通讯录时候需要使用的access_token
    public function get_access_token($property_id=0,$is_send=false)
    {
        if ($property_id) {
            $where['bind_id'] = $property_id;
            $access_obj = M('House_enterprise_wx_bind')->where($where)->field('corpid, qy_address_book_secret')->find();
            $corpsecret = trim($access_obj['qy_address_book_secret']);
            $corpid = trim($access_obj['corpid']);
        } else {
            $corpsecret = C('config.qy_address_book_secret');
        }
        if ($corpsecret && !$is_send) {
            return $this->get_address_book_token($property_id,$corpid,$corpsecret);
        } else {
            import('@.ORG.qywx.config');
            $config = new config();
            $token_config = $config->qyGetCorpToken($property_id);
            return $token_config;
        }
    }

    // 直接获取 最新的 企业凭证
    public function get_new_token($suiteid,$property_id=0) {
        $where = array(
            'wx_provider_suiteid' => $suiteid,
            'bind_type' => 0,
        );
        if (!$property_id && $this->property_id) {
            $property_id = $this->property_id;
        } elseif (!$property_id && session('property')) {
            $property = session('property');
            if ($property && $property['id']) {
                $property_id = $property['id'];
            }
        }
        $where['bind_id'] = $property_id;
        $access_obj = M('House_enterprise_wx_bind')->where($where)->field('access_token, expires_in, corpid, permanent_code')->find();

        $where_suite_ticket = [
            'name' => 'enterprise_wx_suite_ticket'
        ];
        $SuiteTicket = M('Config')->where($where_suite_ticket)->getField('value');
        $SuiteTicket = trim($SuiteTicket);
        $provider_config = [
            'corpid' => C('config.enterprise_wx_corpid'),
            'Secret' => C('config.enterprise_wx_provider_secret'),
            'SuiteID' => C('config.enterprise_wx_provider_suiteid'),
            'Token' => C('config.enterprise_wx_provider_token'),
            'SuiteTicket' => $SuiteTicket,
            'EncodingAESKey' => C('config.enterprise_wx_provider_secret'),
        ];
        $now = time();
        $db_access_token_common_expires = D('Access_token_common_expires');
        // 获取 获取第三方应用凭证
        $suite_access_token_msg = $db_access_token_common_expires->get_suite_access_token($property_id,$provider_config,$now);
        if (!empty($suite_access_token_msg['errcode'])) {
            return array('errcode' => 1, 'errmsg' => $suite_access_token_msg['errcode']);
        }
        $suite_access_token = $suite_access_token_msg['access_token'];
        $provider_config['permanent_code'] = $access_obj['permanent_code'];
        $corp_token_msg = $db_access_token_common_expires->get_corp_token($property_id,$suite_access_token,$provider_config,$now);
        if (!empty($corp_token_msg['errcode'])) {
            return array('errcode' => 1, 'errmsg' => $corp_token_msg['errcode']);
        }
        $corp_token = $corp_token_msg['access_token'];
        return array('errcode' => 0, 'access_token' => $corp_token);
    }


    // 企业获取部门列表 https://qyapi.weixin.qq.com/cgi-bin/department/list?access_token=ACCESS_TOKEN
    /**
     * @param array $token_info 调用接口凭证
     * @param bool $is_replace 保证不多次重复调用
     * @return bool
     */
    public function department_list($token_info=array(),$id=0, $is_replace=false,$property_id=0) {
        if (empty($token_info)) {
            $token_info = $this->get_access_token($property_id);
        }
        if ($token_info && !$token_info['errcode'] && $token_info['access_token']) {
            $access_token = $token_info['access_token'];
            $list_url = 'https://qyapi.weixin.qq.com/cgi-bin/department/list?access_token='.$access_token;
            if ($id) {
                $list_url .='&id='.$id;
            }
            $list_data = $this->curlGet($list_url);
            if (!is_array($list_data)) {
                $list_data = json_decode($list_data,true);
            }
            fdump_api(['企业获取部门列表:'.__LINE__,$token_info,$list_data],'qyweixin/Enterprise/department_list_log',1);
            if (empty($list_data['errcode'])) {
                $department = $list_data['department'];
                if ($id) {
                    return $department;
                } elseif ($department && $department[0] && !$this->first_fid) {
                    $this->first_fid = $department[0]['id'];
                    $this->first_ffid = $department[0]['parentid'];
                }
                return $token_info;
            } else {
                fdump_api(['企业获取部门列表错误:'.__LINE__,$token_info,$list_data],'qyweixin/Enterprise/department_list_err_log',1);
                return false;
            }
        } elseif ($this->replace_code == $token_info['errcode'] && !$is_replace) {
            $token_info = $this->get_access_token($property_id);
            if (!$token_info['errcode'] && $token_info['access_token']) {
                $this->department_list($token_info, 0,true, $property_id);
            }
        }
        fdump_api(['企业获取部门列表错误:'.__LINE__,$token_info],'qyweixin/Enterprise/department_list_err_log',1);
        return false;
    }

    // 企业微信创建部门
    /**
     * @param int $id 组织架构id
     * @param array $data
     * array (
     *  "name": "广州研发中心", // 必传 部门名称。长度限制为1~32个字符，字符不能包括\:?”<>｜
     *  "name_en": "RDGZ",  // 选传 英文名称，需要在管理后台开启多语言支持才能生效。长度限制为1~32个字符，字符不能包括\:?”<>｜
     *  "parentid": 1, // 必传 父部门id，32位整型
     *  "order": 1, // 选传 在父部门中的次序值。order值大的排序靠前。有效的值范围是[0, 2^32)
     *  "id": 2, // 选传 部门id，32位整型，指定时必须大于1。若不填该参数，将自动生成id
     * )
     * @param array $token_info 调用接口凭证
     * @param bool $is_replace 保证不多次重复调用
     * @return bool
     */
    public function department_create($id,$data=array(),$token_info=array(),$is_replace=false, $property_id=0) {
        if (empty($token_info)) {
            $token_info = $this->get_access_token($property_id);
        }
        if (!$data['parentid'] && $this->first_fid) {
            $data['parentid'] = $this->first_fid;
        }
        if (!$token_info['errcode'] && $token_info['access_token'] && $data) {
            $access_token = $token_info['access_token'];
            $create_url = 'https://qyapi.weixin.qq.com/cgi-bin/department/create?access_token='.$access_token;
            if (!$this->http) {
                import('ORG.Net.Http');
                $this->http = new Http();
            }
            $create_data = $this->http->curlQyWxPost($create_url, json_encode($data,JSON_UNESCAPED_UNICODE));
            if (!is_array($create_data)) {
                $create_data = json_decode($create_data,true);
            }
            if (empty($create_data['errcode'])) {
                $set = array(
                    'create_id' => $create_data['id'],
                    'create_time' => time(),
                    'create_status' => 1,
                    'qy_reasons' => '',
                );
                $this->where(array('id'=> $id))->data($set)->save();
                fdump_api(['添加组织成功：'.__LINE__,$id,$data,$create_data],'qyweixin/Enterprise/department_create_log',1);
                return $token_info;
            } else {
                if ($this->department_already_exists_code==$create_data['errcode']) {
                    $department = $this->department_list($token_info, $data['parentid']);
                    $create_id = $id;
                    foreach ($department as $item) {
                        if ($item['name']==$data['name']) {
                            $create_id = $item['id'];
                            break;
                        }
                    }
                    $set = array(
                        'create_id' => $create_id,
                        'create_time' => time(),
                        'create_status' => 1,
                    );
                    $this->where(array('id'=> $id))->data($set)->save();
                    return $token_info;
                }
                $set = array(
                    'qy_reasons' => serialize($create_data),
                    'create_status' => 2,
                    'create_time' => time(),
                );
                $this->where(array('id'=> $id))->data($set)->save();

                fdump_api(['添加组织失败：'.__LINE__,$id,$data,$create_data],'qyweixin/Enterprise/department_create_err_log',1);
                return false;
            }
        } elseif ($this->replace_code == $token_info['errcode'] && !$is_replace) {
            $token_info = $this->get_access_token($property_id);
            if (!$token_info['errcode'] && $token_info['access_token'] && $data) {
                $this->department_create($id,$data,$token_info,true, $property_id);
            }
        }
        fdump_api(['添加组织失败token_info：'.__LINE__,$id,$data,$token_info],'qyweixin/Enterprise/department_create_err_log',1);
        return false;
    }

    // 企业微信更新部门

    /**
     * @param int $id 组织架构id
     * @param array $data
     * array (
     *  "id": 2, // 必传 部门id，32位整型，指定时必须大于1。若不填该参数，将自动生成id  不传 从数据库获取
     *  "name": "广州研发中心", // 必传 部门名称。长度限制为1~32个字符，字符不能包括\:?”<>｜
     *  "name_en": "RDGZ",  // 选传 英文名称，需要在管理后台开启多语言支持才能生效。长度限制为1~32个字符，字符不能包括\:?”<>｜
     *  "parentid": 1, // 必传 父部门id，32位整型
     *  "order": 1, // 选传 在父部门中的次序值。order值大的排序靠前。有效的值范围是[0, 2^32)
     * )
     * @param array $token_info 调用接口凭证
     * @param bool $is_replace 保证不多次重复调用
     * @return bool
     */
    public function department_update($id,$data=array(),$token_info=array(),$is_replace=false, $property_id=0) {
        $property_group = $this->where(array('id'=> $id))->field('create_id, status, create_status, is_del')->find();
        if ($property_group['create_id']>0 && $property_group['is_del']!=1 && $property_group['create_status']==1) {
            if (empty($token_info)) {
                $token_info = $this->get_access_token($property_id);
            }
            if (!$token_info['errcode'] && $token_info['access_token'] && $data) {
                if (!$data['parentid'] && $this->first_fid) {
                    $data['parentid'] = $this->first_fid;
                }
                $access_token = $token_info['access_token'];
                $update_url = 'https://qyapi.weixin.qq.com/cgi-bin/department/update?access_token='.$access_token;
                $data['id'] = $property_group['create_id'];
                if (!$this->http) {
                    import('ORG.Net.Http');
                    $this->http = new Http();
                }
                $update_data = $this->http->curlQyWxPost($update_url, json_encode($data,JSON_UNESCAPED_UNICODE));
                if (!is_array($update_data)) {
                    $update_data = json_decode($update_data,true);
                }
                if (empty($update_data['errcode'])) {
                    $set = array(
                        'create_id' => $update_data['id'],
                        'update_time' => time(),
                        'create_status' => 1,
                        'qy_reasons' => '',
                    );
                    $this->where(array('id'=> $id))->data($set)->save();
                    fdump_api(['更新组织成功：'.__LINE__,$id,$data,$update_data,$token_info],'qyweixin/Enterprise/department_update_log',1);
                    return $token_info;
                }elseif (60003==$update_data['errcode']) {
                    // 反馈说对应组织不存在 重新添加
                    $set = array(
                        'create_id' => 0,
                        'update_time' => time(),
                        'create_status' => 0,
                        'qy_reasons' => '',
                    );
                    $this->where(array('id'=> $id))->data($set)->save();
                    $info = $this->department_create($id, $data, $token_info);
                    fdump_api(['更新组织失败,组织不存在：'.__LINE__,$id,$data,$update_data,$token_info, $info],'qyweixin/Enterprise/department_update_err_log',1);
                }elseif (60004==$update_data['errcode'] && $data['parentid']) {
                    // 反馈说对应父级组织不存在 重新添加
                    $set = array(
                        'create_id' => 0,
                        'update_time' => time(),
                        'create_status' => 0,
                        'qy_reasons' => '',
                    );
                    $this->where(array('id'=> $data['parentid']))->data($set)->save();

                    $db_property_group = M('Property_group');
                    $where_paren = [];
                    $where_paren['id'] = $data['parentid'];
                    $where_paren['is_del'] = 0;
                    $where_paren['status'] = 1;
                    $property_group = $db_property_group->where($where_paren)->find();
                    if ($property_group) {
                        $parendata = array(
                            'name' => $property_group['name'],
                            'parentid' => $property_group['create_id']?$property_group['create_id']:intval($property_group['fid']),
                            'order' => $property_group['sort'],
                            'id' => $property_group['id'],
                        );
                        $token_info = $this->department_create($data['parentid'], $parendata, $token_info, false,$property_id);
                    } else {
                        fdump_api(['更新组织失败,上级不存在：'.__LINE__,$id,$data,$update_data,$token_info],'qyweixin/Enterprise/department_update_err_log',1);
                    }
                }else {
                    fdump_api(['更新组织失败：'.__LINE__,$id,$data,$update_data,$token_info],'qyweixin/Enterprise/department_update_err_log',1);
                    return false;
                }
            } elseif ($this->replace_code == $token_info['errcode'] && !$is_replace) {
                $token_info = $this->get_access_token($property_id);
                if (!$token_info['errcode'] && $token_info['access_token'] && $data) {
                    $this->department_update($id,$data,$token_info,true, $property_id);
                }
            }
        } elseif ($property_group['create_id']>0 && $property_group['is_del']==1 && $property_group['create_status']!=4) {
            // 组织已经被删除  同步删除
            return $this->department_delete($id,$token_info, false, $property_id);
        }
        fdump_api(['更新组织失败token_info：'.__LINE__,$id,$data,$token_info,$property_group],'qyweixin/Enterprise/department_update_err_log',1);
        return false;
    }

    // 企业微信删除部门
    /**
     * @param int $id 组织架构id
     * @param array $token_info 调用接口凭证
     * @param bool $is_replace 保证不多次重复调用
     * @return bool
     */
    public function department_delete($id, $token_info=array(), $is_replace=false, $property_id=0) {
        $property_group = $this->where(array('id'=> $id))->field('create_id, status, create_status, is_del')->find();
        if ($property_group['create_id']>0 && $property_group['is_del']==1 && $property_group['create_status']==1) {
            if (empty($token_info)) {
                $token_info = $this->get_access_token($property_id);
            }
            if (!$token_info['errcode'] && $token_info['access_token']) {
                $access_token = $token_info['access_token'];
//                $delete_url = 'https://qyapi.weixin.qq.com/cgi-bin/department/delete?access_token='.$access_token.'&id='.$property_group['create_id'];
//                $delete_data = $this->curlGet($delete_url);
//                if (!is_array($delete_data)) {
//                    $delete_data = json_decode($delete_data,true);
//                }
                $delete_data = [];
                $delete_data['errcode'] = 0;
                if (empty($delete_data['errcode'])) {
                    $set = array(
                        'del_time' => time(),
                        'create_status' => 4,
                        'qy_reasons' => '',
                    );
                    $this->where(array('id'=> $id))->data($set)->save();
                    return $token_info;
                } else {
                    fdump_api(['department_delete删除:'.__LINE__,$id,$property_group,$delete_data],'qyweixin/Enterprise/department_delete_err_log',1);
                    return false;
                }
            } elseif ($this->replace_code == $token_info['errcode'] && !$is_replace) {
                $token_info = $this->get_access_token($property_id);
                if (!$token_info['errcode'] && $token_info['access_token']) {
                    $this->department_delete($id,$token_info,true, $property_id);
                }
            }
        } elseif ($property_group['create_id']>0 && $property_group['is_del']==1 && $property_group['create_status']==4) {
            // 已经删除过了
            fdump_api(['department_delete已经删除过了，避免重复删除:'.__LINE__,$id,$property_group,$token_info],'qyweixin/Enterprise/department_delete_err_log',1);
            return $token_info;
        }
        if (!$property_group['create_id']) {
            // 如果id不存在 默认删除成功
            $set = array(
                'del_time' => time(),
                'create_status' => 4,
                'qy_reasons' => '',
            );
            $this->where(array('id'=> $id))->data($set)->save();
            return $token_info;
        }
        fdump_api(['department_delete删除逻辑未执行:'.__LINE__,$id,$property_group,$token_info],'qyweixin/Enterprise/department_delete_err_log',1);
        return false;
    }


    // 企业微信添加用户
    /**
     * @param int $wid 工作人员id
     * @param array $data
     * array (
     *  "userid": "worker_1001", // 必传 成员UserID。对应管理端的帐号，企业内必须唯一。不区分大小写，长度为1~64个字节。只能由数字、字母和“_-@.”四种字符组成，且第一个字符必须是数字或字母。
     *  "name": "万万",  // 必传 成员名称。长度为1~64个utf8字符
     *  "alias": "jackzhang", // 选传 成员别名。长度1~32个utf8字符
     *  "mobile": "15913215421", // 选传 手机号码。企业内必须唯一，mobile/email二者不能同时为空
     *  "department": [1, 2], // 必传 成员所属部门id列表,不超过20个
     *  "order":[10,40], // 选传 部门内的排序值，默认为0，成员次序以创建时间从小到大排列。数量必须和department一致，数值越大排序越前面。有效的值范围是[0, 2^32)
     *  "position": "产品经理",// 选传 职务信息。长度为0~128个字符
     *  "gender": "1", // 选传 性别。1表示男性，2表示女性
     *  "email": "zhangsan@gzdev.com", // 选传 邮箱。长度6~64个字节，且为有效的email格式。企业内必须唯一，mobile/email二者不能同时为空
     *  "is_leader_in_dept": [1, 0],// 选传 个数必须和department一致，表示在所在的部门内是否为上级。1表示为上级，0表示非上级。在审批等应用里可以用来标识上级审批人
     *  "enable":1, // 选传 启用/禁用成员。1表示启用成员，0表示禁用成员
     *  "avatar_mediaid": "2-G6nrLmr5EC3MNb_-zL1dDdzkd0p7cNliYu9V5w7o8K0", //  选传 成员头像的mediaid，通过素材管理接口上传图片获得的mediaid
     *  "telephone": "020-123456", // 选传 座机。32字节以内，由纯数字或’-‘号组成。
     *  "address": "广州市海珠区新港中路",// 选传 地址。长度最大128个字符
     *   "extattr": { // 选传 自定义字段。自定义字段需要先在WEB管理端添加，见扩展属性添加方法，否则忽略未知属性的赋值。与对外属性一致，不过只支持type=0的文本和type=1的网页类型，详细描述查看对外属性
     *        "attrs": [
     *                  {
     *                      "type": 0,
     *                      "name": "文本名称",
     *                      "text": {
     *                          "value": "文本"
     *                      }
     *                  },
     *                  {
     *                      "type": 1,
     *                      "name": "网页名称",
     *                      "web": {
     *                          "url": "http://www.test.com",
     *                          "title": "标题"
     *                      }
     *                 }
     *        ]
     *    },
     *   "to_invite": true, // 选传 是否邀请该成员使用企业微信（将通过微信服务通知或短信或邮件下发邀请，每天自动下发一次，最多持续3个工作日），默认值为true。
     *   "external_position": "高级产品经理", // 选传 对外职务，如果设置了该值，则以此作为对外展示的职务，否则以position来展示。长度12个汉字内
     *   "external_profile": {// 选传 成员对外属性，字段详情见对外属性
     *          "external_corp_name": "企业简称",
     *          "external_attr": [
     *                  {
     *                      "type": 0,
     *                      "name": "文本名称",
     *                      "text": {
     *                          "value": "文本"
     *                      }
     *                  },
     *                  {
     *                      "type": 1,
     *                      "name": "网页名称",
     *                      "web": {
     *                          "url": "http://www.test.com",
     *                           "title": "标题"
     *                      }
     *                  },
     *                  {
     *                      "type": 2,
     *                      "name": "测试app",
     *                      "miniprogram": {
     *                          "appid": "wx8bd80126147df384",
     *                          "pagepath": "/index",
     *                          "title": "my miniprogram"
     *                      }
     *                  }
     *          ]
     *      }
     * )
     * @param array $token_info 调用接口凭证
     * @param bool $is_replace 保证不多次重复调用
     * @return bool
     */
    public function user_create($wid,$data=array(), $token_info=array(),$property_id=0,$is_replace=false) {
        if (empty($token_info)) {
            $token_info = $this->get_access_token($property_id);
        }
        if (!$token_info['errcode'] && $token_info['access_token'] && $data) {
            $access_token = $token_info['access_token'];
            $create_url = 'https://qyapi.weixin.qq.com/cgi-bin/user/create?access_token='.$access_token;
            if (!$this->http) {
                import('ORG.Net.Http');
                $this->http = new Http();
            }
            if (!isset($data['to_invite'])) {
                $data['to_invite'] = true;
            }
            $create_data = $this->http->curlQyWxPost($create_url, json_encode($data,JSON_UNESCAPED_UNICODE));
            if (!is_array($create_data)) {
                $create_data = json_decode($create_data,true);
            }
            if (!$create_data['errcode']) {
                $set = array(
                    'qy_id' => $data['userid'],
                    'qy_time' => time(),
                    'qy_status' => 1,
                    'qy_reasons' => '',
                );
                M('House_worker')->where(array('wid'=> $wid))->data($set)->save();
                if ($property_id && $data['mobile'] && $data['department']) {
                    $where_other = [
                        'phone' => $data['mobile'],
                        'department_id' => ['in',$data['department']],
                        'is_del' => 0,
                        'property_id' => $property_id,
                    ];
                    M('House_worker')->where($where_other)->data($set)->save();
                }
                fdump_api(['user_create添加:'.__LINE__,$wid,$data,$token_info,$create_data],'qyweixin/Enterprise/user_create_log',1);
                return $token_info;
            } else {
                if ($this->user_already_exists_code==$create_data['errcode']) {
                    $set = array(
                        'qy_id' => $data['userid'],
                        'qy_time' => time(),
                        'qy_status' => 1,
                        'qy_reasons' => '',
                    );
                    $this->where(array('id'=> $wid))->data($set)->save();
                    if ($property_id && $data['mobile'] && $data['department']) {
                        $where_other = [
                            'phone' => $data['mobile'],
                            'department_id' => ['in',$data['department']],
                            'is_del' => 0,
                            'property_id' => $property_id,
                        ];
                        M('House_worker')->where($where_other)->data($set)->save();
                    }
                    return $token_info;
                } elseif(60104 == $create_data['errcode']) {
                    $mobile = M('House_worker')->where(array('wid'=> $wid))->getField('phone');
                    $ret = $this->get_userid($wid,$mobile,$token_info, $data['department'],$property_id);
                    if ($ret) {
                        return $ret;
                    }
                }
                $set = array(
                    'qy_reasons' => serialize($create_data),
                    'qy_status' => 2,
                    'update_time' => time()
                );
                M('House_worker')->where(array('wid'=> $wid))->data($set)->save();

                fdump_api(['user_create添加失败:'.__LINE__,$wid,$data,$token_info,$create_data],'qyweixin/Enterprise/user_create_err_log',1);
                return false;
            }
        } elseif ($this->replace_code == $token_info['errcode'] && !$is_replace) {
            $token_info = $this->get_access_token($property_id);
            if (!$token_info['errcode'] && $token_info['access_token']) {
                return $this->user_create($wid,$data,$token_info,$property_id,true);
            }
        }
        fdump_api(['user_create添加错误-未执行:'.__LINE__,$wid,$data,$token_info],'qyweixin/Enterprise/user_create_err_log',1);
        return false;
    }

    // 企业更新成员
    /**
     * 更新成员
     * @param int $wid 工作人员id
     * @param array $data
     * array (
     *  "userid": "worker_1001", // 必传 成员UserID。对应管理端的帐号，企业内必须唯一。不区分大小写，长度为1~64个字节。只能由数字、字母和“_-@.”四种字符组成，且第一个字符必须是数字或字母。
     *  "name": "万万",  // 必传 成员名称。长度为1~64个utf8字符
     *  "alias": "jackzhang", // 选传 成员别名。长度1~32个utf8字符
     *  "mobile": "15913215421", // 选传 手机号码。企业内必须唯一，mobile/email二者不能同时为空
     *  "department": [1, 2], // 必传 成员所属部门id列表,不超过20个
     *  "order":[10,40], // 选传 部门内的排序值，默认为0，成员次序以创建时间从小到大排列。数量必须和department一致，数值越大排序越前面。有效的值范围是[0, 2^32)
     *  "position": "产品经理",// 选传 职务信息。长度为0~128个字符
     *  "gender": "1", // 选传 性别。1表示男性，2表示女性
     *  "email": "zhangsan@gzdev.com", // 选传 邮箱。长度6~64个字节，且为有效的email格式。企业内必须唯一，mobile/email二者不能同时为空
     *  "is_leader_in_dept": [1, 0],// 选传 个数必须和department一致，表示在所在的部门内是否为上级。1表示为上级，0表示非上级。在审批等应用里可以用来标识上级审批人
     *  "enable":1, // 选传 启用/禁用成员。1表示启用成员，0表示禁用成员
     *  "avatar_mediaid": "2-G6nrLmr5EC3MNb_-zL1dDdzkd0p7cNliYu9V5w7o8K0", //  选传 成员头像的mediaid，通过素材管理接口上传图片获得的mediaid
     *  "telephone": "020-123456", // 选传 座机。32字节以内，由纯数字或’-‘号组成。
     *  "address": "广州市海珠区新港中路",// 选传 地址。长度最大128个字符
     *   "extattr": { // 选传 自定义字段。自定义字段需要先在WEB管理端添加，见扩展属性添加方法，否则忽略未知属性的赋值。与对外属性一致，不过只支持type=0的文本和type=1的网页类型，详细描述查看对外属性
     *        "attrs": [
     *                  {
     *                      "type": 0,
     *                      "name": "文本名称",
     *                      "text": {
     *                          "value": "文本"
     *                      }
     *                  },
     *                  {
     *                      "type": 1,
     *                      "name": "网页名称",
     *                      "web": {
     *                          "url": "http://www.test.com",
     *                          "title": "标题"
     *                      }
     *                 }
     *        ]
     *    },
     *   "to_invite": true, // 选传 是否邀请该成员使用企业微信（将通过微信服务通知或短信或邮件下发邀请，每天自动下发一次，最多持续3个工作日），默认值为true。
     *   "external_position": "高级产品经理", // 选传 对外职务，如果设置了该值，则以此作为对外展示的职务，否则以position来展示。长度12个汉字内
     *   "external_profile": {// 选传 成员对外属性，字段详情见对外属性
     *          "external_corp_name": "企业简称",
     *          "external_attr": [
     *                  {
     *                      "type": 0,
     *                      "name": "文本名称",
     *                      "text": {
     *                          "value": "文本"
     *                      }
     *                  },
     *                  {
     *                      "type": 1,
     *                      "name": "网页名称",
     *                      "web": {
     *                          "url": "http://www.test.com",
     *                           "title": "标题"
     *                      }
     *                  },
     *                  {
     *                      "type": 2,
     *                      "name": "测试app",
     *                      "miniprogram": {
     *                          "appid": "wx8bd80126147df384",
     *                          "pagepath": "/index",
     *                          "title": "my miniprogram"
     *                      }
     *                  }
     *          ]
     *      }
     * )
     * @param array $token_info 调用接口凭证
     * @param bool $is_replace 保证不多次重复调用
     * @return bool
     */
    public function user_update($wid,$data=array(), $token_info=array(),$property_id=0,$is_replace=false) {
        $house_worker = M('House_worker')->where(array('wid'=> $wid))->field('qy_id, status, qy_status, is_del')->find();
        if ($house_worker['qy_id'] && $house_worker['is_del']!=1 && $house_worker['qy_status']==1) {
            if (empty($token_info)) {
                $token_info = $this->get_access_token($property_id);
            }
            if (!$token_info['errcode'] && $token_info['access_token'] && $data) {
                $access_token = $token_info['access_token'];
                $update_url = 'https://qyapi.weixin.qq.com/cgi-bin/user/update?access_token='.$access_token;
                if (!$data['userid']) {
                    $data['userid'] = $house_worker['qy_id'];
                }
                if (!$this->http) {
                    import('ORG.Net.Http');
                    $this->http = new Http();
                }
                if (!isset($data['to_invite'])) {
                    $data['to_invite'] = true;
                }
                $update_data = $this->http->curlQyWxPost($update_url, json_encode($data,JSON_UNESCAPED_UNICODE));
                if (!is_array($update_data)) {
                    $update_data = json_decode($update_data,true);
                }
                if (empty($update_data['errcode'])) {
                    $set = array(
                        'update_time' => time()
                    );
                    M('House_worker')->where(array('wid'=> $wid))->data($set)->save();
                    if ($property_id && $data['mobile'] && $data['department']) {
                        $where_other = [
                            'phone' => $data['mobile'],
                            'department_id' => ['in',$data['department']],
                            'is_del' => 0,
                            'property_id' => $property_id,
                        ];
                        // 重新更新下时间和数据
                        $set['qy_id'] = $data['userid'];
                        $set['qy_status'] = 1;
                        $set['qy_reasons'] = '';
                        M('House_worker')->where($where_other)->data($set)->save();
                    }
                    fdump_api(['user_update更新:'.__LINE__,$wid,$data,$token_info,$update_data],'qyweixin/Enterprise/user_update_log',1);
                    return $token_info;
                } elseif ($update_data['errcode'] == 60104) {

                    $mobile = M('House_worker')->where(array('wid'=> $wid))->getField('phone');
                    $ret = $this->get_userid($wid,$mobile,$token_info, $data['department'],$property_id);
                    if ($ret) {
                        return $ret;
                    }
                    fdump_api(['user_update更新失败:'.__LINE__,$wid,$data,$token_info,$house_worker,$update_data],'qyweixin/Enterprise/user_update_err_log',1);
                } elseif ($update_data['errcode'] == 60111) {
                    fdump_api(['user_update更新失败重新添加:'.__LINE__,$wid,$data,$token_info,$house_worker,$update_data],'qyweixin/Enterprise/user_update_err_log',1);
                    return $this->user_create($wid,$data,$token_info,$property_id);
                } else {
                    fdump_api(['user_update更新失败:'.__LINE__,$wid,$data,$token_info,$house_worker,$update_data],'qyweixin/Enterprise/user_update_err_log',1);
                    return false;
                }
            } elseif ($this->replace_code == $token_info['errcode'] && !$is_replace) {
                $token_info = $this->get_access_token($property_id);
                if (!$token_info['errcode'] && $token_info['access_token']) {
                    return $this->user_update($wid,$data,$token_info,$property_id,true);
                }
            } else {
                fdump_api(['user_update更新token错误:'.__LINE__,$wid,$data,$token_info,$house_worker],'qyweixin/Enterprise/user_update_err_log',1);
                return false;
            }
        } elseif ($house_worker['qy_id']>0 && $house_worker['is_del']==1 && $house_worker['create_status']!=4) {
            $this->department_delete($wid,$token_info, false, $property_id);
        }
        fdump_api(['user_update更新异常:'.__LINE__,$wid,$data,$token_info,$house_worker],'qyweixin/Enterprise/user_update_err_log',1);
        return false;
    }

    /**
     * Notes: 通过手机号获取
     * @param $wid
     * @param $mobile
     * @param array $token_info
     * @param bool $is_replace
     * @return array|bool
     * @author: wanzy
     * @date_time: 2021/5/20 16:20
     */
    public function get_userid($wid,$mobile,$token_info=array(),$department=[],$property_id=0,$is_replace=false) {
        if (empty($token_info)) {
            $token_info = $this->get_access_token($property_id);
        }
        if (!$token_info['errcode'] && $token_info['access_token'] && $mobile) {
            $access_token = $token_info['access_token'];
            $get_url = 'https://qyapi.weixin.qq.com/cgi-bin/user/getuserid?access_token='.$access_token;
            if (!$this->http) {
                import('ORG.Net.Http');
                $this->http = new Http();
            }
            $data = [
                'mobile' => $mobile
            ];
            $get_data = $this->http->curlQyWxPost($get_url, json_encode($data,JSON_UNESCAPED_UNICODE));
            if (!is_array($get_data)) {
                $get_data = json_decode($get_data,true);
            }
            if (!$get_data['errcode']) {
                $set = array(
                    'qy_id' => $get_data['userid'],
                    'qy_time' => time(),
                    'qy_status' => 1,
                    'qy_reasons' => '请重新同步一次'
                );
                M('House_worker')->where(array('wid'=> $wid))->data($set)->save();
                if ($property_id && $mobile && $department) {
                    $where_other = [
                        'phone' => $mobile,
                        'department_id' => ['in',$department],
                        'is_del' => 0,
                        'property_id' => $property_id,
                    ];
                    M('House_worker')->where($where_other)->data($set)->save();
                }
                fdump_api(['get_userid获取:'.__LINE__,$wid,$data,$token_info,$get_data],'qyweixin/Enterprise/get_userid_log',1);
                return $token_info;
            } else {
                fdump_api(['get_userid获取失败:'.__LINE__,$wid,$data,$token_info,$get_data],'qyweixin/Enterprise/get_userid_log',1);
                return false;
            }
        } elseif ($this->replace_code == $token_info['errcode'] && !$is_replace) {
            $token_info = $this->get_access_token($property_id);
            if (!$token_info['errcode'] && $token_info['access_token']) {
                $this->get_userid($wid,$mobile,$token_info,$department,$property_id,true);
            }
        }
        fdump_api(['get_userid获取错误-未执行:'.__LINE__,$wid,$mobile,$token_info],'qyweixin/Enterprise/get_userid_log',1);
        return false;
    }

    // 企业单个删除成员
    /**
     * @param int $wid 工作人员id
     * @param array $token_info 调用接口凭证
     * @param bool $is_replace 保证不多次重复调用
     * @return bool
     */
    public function user_delete($wid, $token_info=array(),$is_replace=false,$property_id=0) {
        $house_worker = M('House_worker')->where(array('wid'=> $wid))->field('qy_id, status, qy_status, is_del')->find();
        if ($house_worker['qy_id'] && $house_worker['is_del']==1 && $house_worker['qy_status']==1) {
            $qy_id_arr = explode('_', $house_worker['qy_id']);
            if ($qy_id_arr[0]!='worker') {
                // 由企业微信那边先添加的不进行同步删除
                fdump_api(['又企业微信那边先添加的不进行同步删除:'.__LINE__,$wid,$token_info,$house_worker,$qy_id_arr],'qyweixin/Enterprise/user_delete_err_log',1);
                return $token_info;
            }
            if (empty($token_info)) {
                $token_info = $this->get_access_token($property_id);
            }
            if (!$token_info['errcode'] && $token_info['access_token']) {
                $access_token = $token_info['access_token'];
                $delete_data = [];
                $delete_data['errcode'] = 0;
//                $delete_url = 'https://qyapi.weixin.qq.com/cgi-bin/user/delete?access_token='.$access_token.'&userid='.$house_worker['qy_id'];
//                $delete_data = $this->curlGet($delete_url);
//                if (!is_array($delete_data)) {
//                    $delete_data = json_decode($delete_data,true);
//                }
                if (empty($delete_data['errcode'])) {
                    $set = array(
                        'del_time' => time(),
                        'qy_status' => 4,
                        'qy_id' => '',
                    );
                    M('House_worker')->where(array('wid'=> $wid))->data($set)->save();
                    fdump_api(['user_delete删除:'.__LINE__,$wid,$token_info,$house_worker,$delete_data],'qyweixin/Enterprise/user_delete_log',1);
                    return $token_info;
                } elseif ($delete_data['errcode'] == 60111) {
                    $set = array(
                        'del_time' => time(),
                        'qy_status' => 4,
                        'qy_id' => '',
                    );
                    M('House_worker')->where(array('wid'=> $wid))->data($set)->save();
                    fdump_api(['线上不存在 本地记录成功:'.__LINE__,$wid,$token_info,$house_worker,$delete_data],'qyweixin/Enterprise/user_delete_log',1);
                    return $token_info;
                }  else {
                    fdump_api(['user_delete删除失败:'.__LINE__,$wid,$token_info,$house_worker,$delete_data],'qyweixin/Enterprise/user_delete_err_log',1);
                    return false;
                }
            } elseif ($this->replace_code == $token_info['errcode'] && !$is_replace) {
                $token_info = $this->get_access_token($property_id);
                if (!$token_info['errcode'] && $token_info['access_token']) {
                    $this->user_delete($wid,$token_info,true,$property_id);
                }
            } else {
                fdump_api(['user_delete删除错误:'.__LINE__,$wid,$token_info,$house_worker],'qyweixin/Enterprise/user_delete_err_log',1);
                return false;
            }

        }
        fdump_api(['user_delete删除异常:'.__LINE__,$wid,$token_info,$house_worker],'qyweixin/Enterprise/user_delete_err_log',1);
        return $token_info;
    }

    // 企业批量删除成员
    /**
     * @param array $wid_list
     * ["1", "2"]
     * @param array $token_info 调用接口凭证
     * @param bool $is_replace 保证不多次重复调用
     * @return bool
     */
    public function user_batchdelete($wid_list, $token_info=array(),$is_replace=false,$property_id=0) {
        $house_worker = M('House_worker')->where(array('wid'=> array('in', $wid_list)))->field('qy_id, status, qy_status, is_del')->select();
        $useridlist = array();
        if ($house_worker) {
            foreach ($house_worker as $val) {
                if ($val['qy_id'] && $val['is_del']==1 && $val['qy_status']==1) {
                    $useridlist[] = $val['qy_id'];
                }
            }
            if ($useridlist) {
                if (empty($token_info)) {
                    $token_info = $this->get_access_token($property_id);
                }
                if (!$token_info['errcode'] && $token_info['access_token']) {
                    $access_token = $token_info['access_token'];
                    $data = array();
                    $data['useridlist'] = $useridlist;
                    $delete_url = 'https://qyapi.weixin.qq.com/cgi-bin/user/batchdelete?access_token='.$access_token;
                    if (!$this->http) {
                        import('ORG.Net.Http');
                        $this->http = new Http();
                    }
                    $delete_data = $this->http->curlQyWxPost($delete_url, json_encode($data,JSON_UNESCAPED_UNICODE));
                    if (!is_array($delete_data)) {
                        $delete_data = json_decode($delete_data,true);
                    }
                    if (empty($delete_data['errcode'])) {
                        $set = array(
                            'del_time' => time(),
                            'qy_status' => 4,
                        );
                        M('House_worker')->where(array('wid'=> array('in', $wid_list)))->data($set)->save();
                        fdump_api(['user_batchdelete删除:'.__LINE__,$wid_list,$token_info,$house_worker,$delete_data],'qyweixin/Enterprise/user_batchdelete_log',1);
                        return $token_info;
                    } else {
                        fdump_api(['user_batchdelete删除失败:'.__LINE__,$wid_list,$token_info,$house_worker,$data,$delete_data],'qyweixin/Enterprise/user_batchdelete_err_log',1);
                        return false;
                    }
                }elseif ($this->replace_code == $token_info['errcode'] && !$is_replace) {
                    $token_info = $this->get_access_token($property_id);
                    if (!$token_info['errcode'] && $token_info['access_token']) {
                        $this->user_batchdelete($wid_list,$token_info,true,$property_id);
                    }
                } else {
                    fdump_api(['user_batchdelete删除错误:'.__LINE__,$wid_list,$token_info,$house_worker,$useridlist],'qyweixin/Enterprise/user_batchdelete_err_log',1);
                    return false;
                }
            } else {
                fdump_api(['user_batchdelete删除对象不存在:'.__LINE__,$wid_list,$token_info,$house_worker,$useridlist],'qyweixin/Enterprise/user_batchdelete_err_log',1);
                return false;
            }
        }
        fdump_api(['user_batchdelete删除对象不存在:'.__LINE__,$wid_list,$token_info,$house_worker,$useridlist],'qyweixin/Enterprise/user_batchdelete_err_log',1);
        return false;
    }

    /**整合推送消息*/
    /**
     * @param $type   // 所属类型  当个工作人员 work  部门 property_group
     * @param $type_id // 对应类型id  work 就是工作人员id property_group 就是对应部门id
     * @param $data
     * array(
     *  'tempKey'=> 'OPENTM405462911',
     *  'dataArr' => array(
     *     'href' => '/wap.php?c=Worker&a=detail&wxscan=1&pigcms_id=1',
     *     'wecha_id' => '132uop43214p32oip42io2',
     *     'first' => '您有一个物业报修工单',
     *     'keyword1' => '物业报修工单',
     *     'keyword2' => '已发送',
     *     'keyword3' => '13时20分',
     *     'keyword4' => '物业',
     *     'remark' => '\n请点击查看详细信息！',
     * ),
     *  'mer_id' => '' // 对应商家id
     * )
     * @param array $token_info 调用接口凭证
     * @param bool $is_replace 保证不多次重复调用
     * @return bool
     */
    public function send_msg($type='work', $type_id=0, $data, $token_info=array(),$is_replace=false, $that='') {
        if ($that && gettype($that)=='object') {
            $that->keepThread();
        }
        if ($type && 'work'==$type && $type_id>0) {
            // 工作人员对应查询到对应工作人员
            $worker = M('House_worker')->where(array('wid'=> $type_id))->field('qy_id,property_id')->find();
            if ($worker['qy_id']) {
                $where['bind_id'] = $worker['property_id'];
                $access_obj = M('House_enterprise_wx_bind')->where($where)->field('access_token, expires_in, corpid, permanent_code,auth_info')->find();
                if ($that && gettype($that)=='object') {
                    $that->keepThread();
                }
                if ($access_obj['corpid'] && $access_obj['permanent_code'] && $access_obj['auth_info']) {
                    $auth_info = unserialize($access_obj['auth_info']);
                    $agent = $auth_info['agent'][0];
                    $agentid = $agent['agentid'];
                    if (empty($token_info)) {
                        $token_info = $this->get_access_token($worker['property_id'],true);
                        if ($that && gettype($that)=='object') {
                            $that->keepThread();
                        }
                    }
                    if (!$token_info['errcode'] && $token_info['access_token'] && $data) {
                        $access_token = $token_info['access_token'];
                        $send_url = 'https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token='.$access_token;

                        $content = "";
                        if ($data['dataArr']['first']) {
                            $content .= "".$data['dataArr']['first']."";
                        }
                        if ($data['dataArr']['keyword1']) {
                            $content .= "<div>".$data['dataArr']['keyword1']."</div>";
                        }
                        if ($data['dataArr']['keyword2']) {
                            $content .= "<div>状态:".$data['dataArr']['keyword2']."</div>";
                        }
                        if ($data['dataArr']['keyword3']) {
                            $content .= "<div>时间:".$data['dataArr']['keyword3']."</div>";
                        }
                        if ($data['dataArr']['keyword4']) {
                            $content .= "<div>类型:".$data['dataArr']['keyword4']."</div>";
                        }
                        if ($data['dataArr']['remark']) {
                            if ($data['dataArr']['href']) {
                                $href = $data['dataArr']['href'];
                                $remark = $data['dataArr']['remark'];
                                $remark = str_replace('\n','',$remark);
                                $content .= "<a href='".$href."'>".$remark."</a>";
                            } else {
                                $content .= " {$data['dataArr']['remark']}";
                            }
                        }

                        $qy_data = array(
                            "touser"=> "".$worker['qy_id'],
                            "msgtype"=> "text",
                            "agentid"=> $agentid,
                            "text"=> array(
                                "content"=> $content
                            ),
                            "safe"=>0,
                            "enable_id_trans"=> 0,
                            "enable_duplicate_check"=> 0
                        );

                        if (!$this->http) {
                            import('ORG.Net.Http');
                            $this->http = new Http();
                        }
                        $send_data = $this->http->curlQyWxPost($send_url, json_encode($qy_data,JSON_UNESCAPED_UNICODE));
                        if ($that && gettype($that)=='object') {
                            $that->keepThread();
                        }
                        if (!is_array($send_data)) {
                            $send_data = json_decode($send_data,true);
                        }
                        if (!$send_data['errcode']) {
                            fdump_api(['整合推送消息:'.__LINE__,$type,$type_id,$data,$token_info,$worker,$send_data],'qyweixin/Enterprise/send_msg_log',1);
                            return $token_info;
                        } else {
                            fdump_api(['推送消息失败:'.__LINE__,$type,$type_id,$data,$token_info,$worker,$send_data],'qyweixin/Enterprise/send_msg_err_log',1);
                        }
                    } elseif ($this->replace_code == $token_info['errcode'] && !$is_replace) {
                        $token_info = $this->get_access_token($worker['property_id'],true);
                        if ($that && gettype($that)=='object') {
                            $that->keepThread();
                        }
                        if (!$token_info['errcode'] && $token_info['access_token']) {
                            $this->send_msg($type,$type_id,$data,$token_info,true,$that);
                        }
                    }

                }
            }
            if ($data['tempKey']) {
                $weixin_bind = D('Weixin_bind')->where(array('mer_id' => $worker['property_id'], 'type' => 2))->find();
                if($weixin_bind) {
                    if ($that && gettype($that)=='object') {
                        $that->keepThread();
                    }
                    $this->sendTempMsg($data,$worker['property_id'],2);
                } else {
                    if ($that && gettype($that)=='object') {
                        $that->keepThread();
                    }
                    $this->sendTempMsg($data,0,0);
                }
            } else {
                fdump_api(['未推送消息:'.__LINE__,$type,$type_id,$data,$token_info,$worker,$send_data],'qyweixin/Enterprise/send_msg_err_log',1);
                return true;
            }
        } elseif ($data['tempKey']) {
            if ($that && gettype($that)=='object') {
                $that->keepThread();
            }
            $this->sendTempMsg($data,0,0);
        }
        return true;
    }

    private function sendTempMsg($data,$property_id=0,$type=0) {
        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
        fdump_api(['未推送消息:'.__LINE__,$data['tempKey'],$data['dataArr'],$property_id,$type],'send_msg_err_log',1);

        $model->sendTempMsg($data['tempKey'], $data['dataArr'], 0,$property_id,$type);
    }



    // 企业微信 获取access_token
    public $qy_type = 'contact_way';

    public $work_param_word = array(
        0=>array('key' => '{姓名}', 'word' => 'name'),
        1=>array('key' => '{手机号}', 'word' => 'phone'),
    );
    public $tag_arr = [
        ['name' => '小区','key'=>'village_name', 'order'=>'200'],
        ['name' => '楼栋','key'=>'single_name', 'order'=>'199'],
        ['name' => '单元','key'=>'floor_name', 'order'=>'198'],
        ['name' => '楼层','key'=>'layer_name', 'order'=>'197'],
        ['name' => '房间号','key'=>'room', 'order'=>'196'],
        ['name' => '业主类型','key'=>'type', 'order'=>'195'],
    ];

    public function get_qy_access_token($corpid='',$property_id=0,$village_id=0,$is_new=false) {
//        if (!$corpid) {
//            if ($property_id) {
//                $where['bind_id'] = $property_id;
//            } elseif($village_id) {
//                $property_id = M('House_village')->where(['village_id'=>$village_id])->getField('property_id');
//                $where['bind_id'] = $property_id;
//            } elseif ($this->property_id) {
//                $where['bind_id'] = $this->property_id;
//            } elseif(session('property')) {
//                $property = session('property');
//                if ($property && $property['id']) {
//                    $where['bind_id'] = $property['id'];
//                }
//            } else {
//                return array('errcode' => 1001, 'errmsg' => '缺少获取对象');
//            }
//            $corpid = M('House_enterprise_wx_bind')->where($where)->getField('corpid');
//        }
        if ($property_id) {
            $where['bind_id'] = $property_id;
            $access_obj = M('House_enterprise_wx_bind')->where($where)->field('corpid, contact_way_secret')->find();
            if ($access_obj) {
                $corpsecret = trim($access_obj['contact_way_secret']);
                $corpid = trim($access_obj['corpid']);
            }
        }
        if (!$corpid) {
            $where_orpid_ticket = [
                'name' => 'enterprise_wx_corpid'
            ];
            $corpid = M('Config')->where($where_orpid_ticket)->getField('value');
            $corpid = trim($corpid);
        }
        if (!$corpsecret) {
            $corpsecret = C('config.contact_way_secret');
        }
        $db_access_token_common_expires = M('Access_token_common_expires');
        $where_access_token = [
            'type' =>$this->qy_type,
            'access_id' =>$corpid.'_'.$corpsecret,
        ];
        $access_token_info = $db_access_token_common_expires->where($where_access_token)->find();
        $now = time();
        // 过期时间  向前1800秒 提前刷新
        if (!$is_new && $access_token_info && intval($access_token_info['access_token_expire']) > intval($now + 1800)) {
            return array('errcode' => 0, 'access_token' =>$access_token_info['access_token']);
        } else {
            // 如果不存在或者过期  重新获取
            $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid={$corpid}&corpsecret={$corpsecret}";
            $result = $this->curlGet($url);
            if (!is_array($result)) {
                $result = json_decode($result,true);
            }
            if (empty($result['errcode'])) {
                $set = array(
                    'access_token' => $result['access_token'],
                    'access_token_expire' => $result['expires_in'] + $now,
                );
                if ($access_token_info) {
                    $db_access_token_common_expires->where($where_access_token)->data($set)->save();
                } else {
                    $set['type'] = $this->qy_type;
                    $set['access_id'] = $corpid.'_'.$corpsecret;
                    $db_access_token_common_expires->data($set)->add();
                }
                return array('errcode' => 0, 'access_token' => $result['access_token']);
            }
            fdump_api(['token获取失败:'.__LINE__,$corpid,$corpsecret,$url,$result],'qyweixin/Enterprise/get_qy_access_token_err_log',1);
            return array('errcode' => $result['errcode'], 'errmsg' => $result['errmsg']);
        }

    }

    /**
     * Notes: 获取企业工作人员
     * @return mixed
     * @author: wanzy
     * @date_time: 2021/1/29 11:45
     */
    public function get_follow_user_list() {
        $token_info = $this->get_qy_access_token();
        if (!$token_info['errcode'] && $token_info['access_token']) {
            $url = "https://qyapi.weixin.qq.com/cgi-bin/externalcontact/get_follow_user_list?access_token={$token_info['access_token']}";
            if (!$this->http) {
                import('ORG.Net.Http');
                $this->http = new Http();
            }
            $result = Http::curlGet($url);
            if (!is_array($result)) {
                $result = json_decode($result,true);
            }
            return $result;
        }
    }


    // 企业微信 配置客户联系「联系我」方式 【已经弃用，改用\app\community\model\service\QywxService/contactWayHousekeeper】
    public function contact_way($housekeeper_id, $housekeeper_info=[], $room_id=0, $is_replace=false) {
        $db_house_services_housekeeper = M('House_services_housekeeper');
        if (empty($housekeeper_info)) {
            $where_housekeeper = [
                'housekeeper_id' => $housekeeper_id
            ];
            $housekeeper_info = $db_house_services_housekeeper->where($where_housekeeper)->find();
        }
        $property_id = $housekeeper_info['property_id'] ? $housekeeper_info['property_id'] : 0;
        $village_id = $housekeeper_info['village_id'] ? $housekeeper_info['village_id'] : 0;
        $token_info = $this->get_qy_access_token('',$property_id,$village_id);
        fdump_api(['创建或更新二维码：'.__LINE__,$housekeeper_id,$housekeeper_info,$room_id,$token_info],'qyweixin/contact_way_log',1);

        if (!$token_info['errcode'] && $token_info['access_token'] && $housekeeper_info) {
            $access_token = $token_info['access_token'];
            $house_worker = M('House_worker');
            $work_arr = explode(',',$housekeeper_info['work_arr']);
            if ($work_arr) {
                $where_workers = [
                    'wid' => ['in', $work_arr],
                    'status' => 1,
                    'qy_id' => ['neq', ''],
                    'qy_status' => 1
                ];
                $user = $house_worker->where($where_workers)->getField('qy_id',true);
                $toResult = $this->cgiBinBatchUseridToOpenuserid($user, $access_token);
                if (isset($toResult['open_userid_list']) && !empty($toResult['open_userid_list'])) {
                    $user = [];
                    foreach ($toResult['open_userid_list'] as $open_userids) {
                        $qy_open_userid = $open_userids['open_userid'];
                        $user[] = $qy_open_userid;
                        $whereWork = ['qy_id' => $open_userids['userid']];
                        $house_worker->where($whereWork)->save(['qy_open_userid' => $qy_open_userid]);
                    }
                }
            } else {
                return false;
            }
            if ($housekeeper_info['floor_id']>0) {
                $remark = '单元管家';
                $state = "housekeeper#".strval($housekeeper_info['housekeeper_id']).'|floor_id#'.strval($housekeeper_info['floor_id']);
            } elseif ($housekeeper_info['single_id']>0) {
                $remark = '楼栋管家';
                $state = "housekeeper#".strval($housekeeper_info['housekeeper_id']).'|single_id#'.strval($housekeeper_info['single_id']);
            } else {
                $remark = '物业管家';
                $state = "housekeeper#".strval($housekeeper_info['housekeeper_id']).'|village_id#'.strval($housekeeper_info['village_id']);
            }
            if ($room_id) {
                $state = "housekeeper#".strval($housekeeper_info['housekeeper_id']).'|room_id#'.strval($room_id);
            }


            // type 联系方式类型,1-单人, 2-多人
            // scene 场景，1-在小程序中联系，2-通过二维码联系
            // remark 联系方式的备注信息，用于助记，不超过30个字符
            // skip_verify 外部客户添加时是否无需验证，默认为true
            // state 企业自定义的state参数，用于区分不同的添加渠道，在调用“获取外部联系人详情”时会返回该参数值，不超过30个字符
            // user 使用该联系方式的用户userID列表，在type为1时为必填，且只能有一个
            $data = [
                'type' => 2,
                'scene' => 2,
                'remark' => $remark,
                'skip_verify' => true,
                'state' => $state,
                'user' => $user,
                'is_temp'=> false
            ];
            $contact_way_json = md5(json_encode($data,true));
            fdump_api(['创建或更新二维码contact_way_json：'.__LINE__,$data,$contact_way_json],'qyweixin/contact_way_log',1);
            if ($housekeeper_info['qr_code'] && $housekeeper_info['contact_way_json'] && $contact_way_json == $housekeeper_info['contact_way_json']) {
                $qr_info = [
                    'config_id' => $housekeeper_info['config_id'],
                    'qr_code' => $housekeeper_info['qr_code'].'?.png',
                ];
                return $qr_info;
            }

            if (!$housekeeper_info['config_id']) {
                $contact_way_url = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/add_contact_way?access_token='.$access_token;
            } else {
                unset($data['type']);
                unset($data['scene']);
                $data['config_id'] = $housekeeper_info['config_id'];
                $contact_way_url = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/update_contact_way?access_token='.$access_token;
            }
            $contact_way_data = $this->curlPost($contact_way_url, json_encode($data,JSON_UNESCAPED_UNICODE));
            fdump_api(['创建或更新二维码请求：'.__LINE__,$contact_way_url,$data,$contact_way_data],'qyweixin/contact_way_log',1);
            if (!is_array($contact_way_data)) {
                $contact_way_data = json_decode($contact_way_data,true);
            }
            if (!$contact_way_data['errcode']) {
                if ($contact_way_data['config_id']) {
                    $set = array(
                        'contact_way_json' => $contact_way_json,
                        'config_id' => $contact_way_data['config_id'],
                        'qr_code' => $contact_way_data['qr_code'],
                        'contact_way_time' => time(),
                        'contact_way_reason' => ''
                    );
                    $where_housekeeper = [
                        'housekeeper_id' => $housekeeper_id
                    ];
                    $db_house_services_housekeeper->where($where_housekeeper)->data($set)->save();
                    $qr_info = [
                        'config_id' => $contact_way_data['config_id'],
                        'qr_code' => $contact_way_data['qr_code'].'?.png',
                    ];
                } else {
                    $set = array(
                        'contact_way_json' => $contact_way_json,
                        'contact_way_time' => time(),
                    );
                    $where_housekeeper = [
                        'housekeeper_id' => $housekeeper_id
                    ];
                    $db_house_services_housekeeper->where($where_housekeeper)->data($set)->save();
                    $qr_info = [
                        'config_id' => $housekeeper_info['config_id'],
                        'qr_code' => $housekeeper_info['qr_code'].'?.png',
                    ];
                }
                return $qr_info;
            } else {
                $set = array(
                    'contact_way_reason' => serialize($contact_way_data),
                );
                $db_house_services_housekeeper->where($where_housekeeper)->data($set)->save();

                fdump_api(['创建或更新二维码失败：'.__LINE__,$contact_way_url,$data,$contact_way_data,$room_id,$housekeeper_info],'qyweixin/contact_way_err_log',1);
                $qr_info = [
                    'config_id' => '',
                    'qr_code' => '',
                ];
                return $qr_info;
            }
        } elseif ($this->replace_code == $token_info['errcode'] && !$is_replace) {
            $suiteid = C('config.enterprise_wx_provider_suiteid');
//            $token_info = $this->get_new_token($suiteid);
            $token_info = $this->get_qy_access_token('',$property_id,$village_id,true);
            if (!$token_info['errcode'] && $token_info['access_token']) {
                $this->contact_way($housekeeper_id, $housekeeper_info,$room_id,true);
            }
        }
        fdump_api(['创建或更新获取token失败：'.__LINE__,$token_info,$room_id,$housekeeper_info],'qyweixin/contact_way_err_log',1);
        return false;
    }

    public function cgiBinBatchUseridToOpenuserid($userid_list, $access_token) {
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/batch/userid_to_openuserid?access_token='.$access_token;
        $param = [
            'userid_list' => $userid_list
        ];
        $result = $this->curlPost($url, json_encode($param,JSON_UNESCAPED_UNICODE));
        if (!is_array($result)) {
            $result = json_decode($result,true);
        }
        fdump_api(['param' => $param, 'url' => $url,'userid_list' => $userid_list, 'result' => $result], 'qyweixin/cgiBinBatchUseridToOpenuserid',1);
        return $result;
    }

    public $tipLabelName = '<span><span class="ant-tag ant-tag-orange" contenteditable="false">客户名称</span></span>';
    /**
     * Notes: 发送欢迎语
     * @param $contact_way_user_data
     * @param int $housekeeper_id
     * @param array $where_housekeeper
     * @param array $housekeeper_info
     * @param bool $is_replace
     * @return bool
     * @author: wanzy
     * @date_time: 2021/1/29 20:56
     */
    public function send_welcome_msg($contact_way_user_data, $housekeeper_id=0,$where_housekeeper=[], $housekeeper_info=[], $is_replace=false) {
        fdump_api(['发送欢迎语--line:'.__LINE__,$contact_way_user_data, $housekeeper_id,$where_housekeeper, $housekeeper_info, $is_replace],'qyweixin/send_welcome_msg_log',true);
        fdump_api(['$contact_way_user_data' => $contact_way_user_data, '$housekeeper_id' => $housekeeper_id, '$where_housekeeper' => $where_housekeeper, '$housekeeper_info' => $housekeeper_info, '$is_replace' => $is_replace], 'qyweixin/contact_way_receive_log',true);
        if (!$contact_way_user_data['WelcomeCode']) {
            return false;
        }
        $data = [
            'welcome_code' => $contact_way_user_data['WelcomeCode']
        ];
        if (('house_services_housekeeper'==$contact_way_user_data['business_type'] || !$contact_way_user_data['business_type']) && ($contact_way_user_data['housekeeper_id'] || $housekeeper_id)) {
            // 楼栋单元管家 扫码进入的  信息发送欢迎语
            $db_house_services_housekeeper = M('House_services_housekeeper');
            if (!$housekeeper_id) {
                $contact_way_user_data['housekeeper_id'] = $housekeeper_id;
            }
            if (empty($housekeeper_info)) {
                if ($housekeeper_id) {
                    $where_housekeeper = [
                        'housekeeper_id' => $housekeeper_id
                    ];
                }
                $housekeeper_info = $db_house_services_housekeeper->where($where_housekeeper)->find();
            }
            if (empty($housekeeper_info)) {
                return false;
            }
            if ($housekeeper_info['welcome_tip']) {
                $work_qy_id = $contact_way_user_data['UserID'] ? $contact_way_user_data['UserID'] : 0;
                if ($work_qy_id) {
                    $where_work = [
                        'qy_id' => $work_qy_id
                    ];
                } else {
                    $where_work = [];
                    $work_arr = $housekeeper_info['work_arr'];
                    $where_work['_string'] = "wid in ({$work_arr})";
                    $where_work['status'] = 1;
                    $where_work['qy_id'] = ['neq', ''];
                    $where_work['qy_status'] = 1;
                }
                $work = M('House_worker')->where($where_work)->field('wid,name, phone')->find();
                $work_param_word = $this->work_param_word;
                $welcome_tip = $housekeeper_info['welcome_tip'];
                foreach ($work_param_word as $val) {
                    if ($val && $val['word']) {
                        // 不存在的信息替换成空
                        if (!$work[$val['word']]) {
                            $work[$val['word']] = '';
                        }
                        $welcome_tip = preg_replace('/' . $val['key'] . '/', $work[$val['word']] . $val['name'], $welcome_tip);
                    }
                }
                $data['text'] = [
                    'content' => $welcome_tip
                ];
            }
            if ($housekeeper_info['floor_id']>0) {
                $where_floor = [
                    'floor_id' => $housekeeper_info['floor_id']
                ];
                $floor_name = M('House_village_floor')->where($where_floor)->getField('floor_name');
            }
            if ($housekeeper_info['single_id']>0) {
                $where_floor = [
                    'id' => $housekeeper_info['single_id']
                ];
                $single_name = M('House_village_single')->where($where_floor)->getField('single_name');
            }
            if ($housekeeper_info['village_id']) {
                $where_village = [
                    'village_id' => $housekeeper_info['village_id']
                ];
                $village_logo = M('House_village')->where($where_village)->getField('village_logo');
            } elseif ($contact_way_user_data['village_id']) {
                $where_village = [
                    'village_id' => $contact_way_user_data['village_id']
                ];
                $village_logo = M('House_village')->where($where_village)->getField('village_logo');
            }
            if ($village_logo) {
                $picurl = replace_file_domain($village_logo);
            } else {
                $picurl = C('config.site_url') . '/static/images/house/qywx/manager.png';
            }
            $title = "加入";
            if ($single_name) {
                $title .= $single_name;
            }
            if ($floor_name) {
                $title .= $floor_name;
            }
            if (!$single_name && !$floor_name) {
                $title .= '物业';
            }
            $title .= '管家服务群';
            if ($contact_way_user_data['customer_id']) {
                $url = C('config.site_url')."/wap.php?g=Wap&c=HouseEnterpriseWeixin&a=contact_way_invitation&customer_id={$contact_way_user_data['customer_id']}";
            } else {
                $url = C('config.site_url')."/wap.php?g=Wap&c=HouseEnterpriseWeixin&a=contact_way_invitation&welcome_code={$contact_way_user_data['WelcomeCode']}";
            }
            if ($housekeeper_info['template_type']==0 && $housekeeper_info['qy_qrcode']) {
                $data['link'] = [
                    'title' => $title,
                    'picurl' => $picurl,
                    'desc' => '快速接收物业通知，邻里沟通',
                    'url' => $url,
                ];
            } elseif ($housekeeper_info['template_type']!=0 && $housekeeper_info['effect_img']) {
                $data['link'] = [
                    'title' => $title,
                    'picurl' => $picurl,
                    'desc' => '快速接收物业通知，邻里沟通',
                    'url' => $url,
                ];
            }
            $property_id = $housekeeper_info['property_id'] ? $housekeeper_info['property_id'] : 0;
            $village_id = $housekeeper_info['village_id'] ? $housekeeper_info['village_id'] : 0;
        } elseif ('village_qywx_channel_code' == $contact_way_user_data['business_type'] && $contact_way_user_data['business_id']) {
            // 通过渠道活码进入的发送欢迎语
            $db_village_qywx_channel_code = M('Village_qywx_channel_code');
            $code_id = $contact_way_user_data['business_id'];
            $where_channel_code = [
                'code_id' => $code_id
            ];
            $code_info = $db_village_qywx_channel_code->where($where_channel_code)->find();
            fdump_api(['tip' => '待发送消息主题', '$contact_way_user_data' => $contact_way_user_data, '$housekeeper_id' => $housekeeper_id, '$code_info' => $code_info], 'qyweixin/contact_way_receive_log',true);
            if (2==$code_info['is_send']) {
                fdump_api(['tip' => '不发送欢迎语'], 'qyweixin/contact_way_receive_log',true);
                return false;
            } elseif (!$code_info['welcome_tip'] && !$code_info['welcome_img'] && !$code_info['engine_content_id']) {
                fdump_api(['tip' => '缺少相关数据不发送欢迎语'], 'qyweixin/contact_way_receive_log',true);
                return false;
            }
            if ($code_info['welcome_tip']) {
//                $tipLabelName = $this->tipLabelName;
                $welcome_tip = $code_info['welcome_tip'];
//                $name = $contact_way_user_data['name'];
//                $welcome_tip = preg_replace('/' . $tipLabelName . '/', $name, $welcome_tip);
                if ($welcome_tip) {
                    $data['text'] = [
                        'content' => $welcome_tip
                    ];
                }
            }
            $property_id = $code_info['property_id'] ? $code_info['property_id'] : 0;
            $village_id = $code_info['village_id'] ? $code_info['village_id'] : 0;
            if ($code_info['welcome_img']) {
                $media_id = $this->uploadQywxMedia($code_info['welcome_img'],$property_id,$village_id);
                fdump_api(['tip' => '图片上传', '$media_id' => $media_id, 'welcome_img' => $code_info['welcome_img'], '$property_id' => $property_id, '$village_id' => $village_id], 'qyweixin/contact_way_receive_log',true);
                if ($media_id) {
                    $data['image'] = [
                        'media_id' => $media_id
                    ];
                }
            }
            if ($code_info['engine_content_id']) {
                $where_engine_content = [
                    'id' => $code_info['engine_content_id']
                ];
                $info = M('Village_qywx_engine_content')->where($where_engine_content)->find();
                $link = [];
                if ($info['title'] && $info['content']) {
                    $link['title'] = $info['title'];
                    $link['url'] = $info['content'];
                    if ($info['share_img']) {
                        $link['picurl'] = replace_file_domain($info['share_img']);
                    }
                    if ($info['share_dsc']) {
                        $link['desc'] = $info['share_dsc'];
                    }
                }
                if (!empty($link)) {
                    $data['link'] = $link;
                }
            }
        } else {
            fdump_api(['tip' => '类型有问题', '$contact_way_user_data' => $contact_way_user_data], 'qyweixin/contact_way_receive_log',true);
            return false;
        }
        $token_info = $this->get_qy_access_token('',$property_id,$village_id);
        fdump_api(['tip' => '发送欢迎语token', '$token_info' => $token_info, '$contact_way_user_data' => $contact_way_user_data, '$property_id' => $property_id, '$village_id' => $village_id], 'qyweixin/contact_way_receive_log',true);

        if (!$token_info['errcode'] && $token_info['access_token'] && !empty($data)) {
            $access_token = $token_info['access_token'];
            $send_welcome_msg_url = "https://qyapi.weixin.qq.com/cgi-bin/externalcontact/send_welcome_msg?access_token={$access_token}";
            $send_welcome_msg = $this->curlPost($send_welcome_msg_url, json_encode($data,JSON_UNESCAPED_UNICODE));
            fdump_api(['tip' => '发送欢迎语接口', '$send_welcome_msg_url' => $send_welcome_msg_url, '$send_welcome_msg' => $send_welcome_msg], 'qyweixin/contact_way_receive_log',true);
            if (!is_array($send_welcome_msg)) {
                $send_welcome_msg = json_decode($send_welcome_msg,true);
            }
            if ($code_info && !empty($code_info['label_txt'])) {
                // 渠道活码存在标签
                $param = [
                    'type' => 'code_mark_user',
                    'code_id' => $code_info['code_id'],
                    'userid' => $contact_way_user_data['UserID'],
                    'external_userid' => $contact_way_user_data['ExternalUserID'],
                    'property_id' => $contact_way_user_data['property_id'],
                    'customer_id' => $contact_way_user_data['customer_id'],
                    'unique_id' =>  'code_mark_user_'.$contact_way_user_data['property_id'].'_'.$contact_way_user_data['customer_id'].'_'.$code_info['code_id'],
                    'plan_time' => -10,
                ];
                fdump_api(['tip' => '渠道活码存在标签', '$code_info' => $code_info, '$param' => $param], 'qyweixin/contact_way_receive_log',true);
                $this->add_plan($param);
            }
            return true;
        } elseif ($this->replace_code == $token_info['errcode'] && !$is_replace) {
            $token_info = $this->get_qy_access_token('',$property_id,$village_id,true);
            if (!$token_info['errcode'] && $token_info['access_token']) {
                $this->send_welcome_msg($contact_way_user_data, $housekeeper_id, $where_housekeeper, $housekeeper_info,true);
            }
        }
        fdump_api(['获取token失败：'.__LINE__,$token_info,$data,$contact_way_user_data,$data],'qyweixin/contact_way_access_token_err_log',1);
        return false;
    }

    public function code_mark_user($code_id, $customer_id, $property_id=0, $param=[]) {
        fdump_api(['打标签--line:'.__LINE__,$code_id, $customer_id, $property_id, $param],'qyweixin/code_mark_user_log',true);
        $db_village_qywx_channel_code = M('Village_qywx_channel_code');
        $where_channel_code = [
            'code_id' => $code_id
        ];
        $code_info = $db_village_qywx_channel_code->where($where_channel_code)->find();
        if (!$property_id) {
            $property_id = $code_info['property_id'];
        }
        if (!$param['userid'] && !$param['external_userid']) {
            $db_house_contact_way_user = M('House_contact_way_user');
            $where = [
                'customer_id' => $customer_id,
            ];
            $contact_way_user_data = $db_house_contact_way_user->where($where)->find();
            $userid = $contact_way_user_data['UserID'];
            $external_userid = $contact_way_user_data['ExternalUserID'];
        } else {
            $userid = $param['userid'];
            $external_userid = $param['external_userid'];
        }
        $where = [
            'a.code_id' => $code_id,
            'b.status' => 1,
            'c.property_id' => $property_id
        ];
        $field = 'b.label_id,b.qy_tag_id,b.label_name,c.label_group_id,c.label_group_name,c.qy_group_id';
        $label_list = D('Village_qywx_code_bind_label')
            ->join('as a left join '.C('DB_PREFIX').'village_qywx_code_label as b ON a.label_id = b.label_id')
            ->join(' left join '.C('DB_PREFIX').'village_qywx_code_label_group as c ON b.label_group_id = c.label_group_id')
            ->where($where)
            ->field($field)
            ->group('b.label_id,b.label_name')
            ->select();
        fdump_api(['获取标签--line:'.__LINE__,$label_list],'qyweixin/code_mark_user_log',true);
        if (!empty($label_list)) {
            $add_tag = [];
            $remove_tag = [];
            $del_bind_label = [];
            $tag_group_msg = [];
            foreach ($label_list as &$val) {
                if ($val && $val['qy_tag_id']) {
                    $add_tag[] = $val['qy_tag_id'];
                } elseif (!isset($tag_group_msg[$val['label_group_id']]) && empty($tag_group_msg[$val['label_group_id']])) {
                    $tag = [];
                    $tag[] = [
                        'name' => $val['label_name'],
                        'order' => $val['label_id'],
                    ];
                    $label_group = [
                        'label_group_id' => $val['label_group_id'],
                        'qy_group_id' => $val['qy_group_id'],
                        'label_group_name' => $val['label_group_name'],
                        'tag' => $tag
                    ];
                    $tag_group_msg[$val['label_group_id']]  = $label_group;
                } elseif ($val) {
                    $tag = $tag_group_msg[$val['label_group_id']]['tag'];
                    $tag[] = [
                        'name' => $val['label_name'],
                        'order' => $val['label_id']
                    ];
                    $tag_group_msg[$val['label_group_id']]['tag'] = $tag;
                }
            }
            fdump_api(['标签--line:'.__LINE__,$tag_group_msg],'qyweixin/code_mark_user_log',true);
            if (!empty($tag_group_msg)) {
                $tag_group_msg = array_values($tag_group_msg);
                $tag_arr = [];
                foreach ($tag_group_msg as &$val1) {
                    if ($val1) {
                        $tag = $val1['tag'];
                        $param = [
                            'customer_id' => $customer_id,
                            'ExternalUserID' => $external_userid,
                        ];
                        $tag_group = $this->qy_add_corp_tag($property_id, $val1['qy_group_id'], $val1['label_group_name'], $val1['label_group_id'], $tag, $param);
                        fdump_api(['循环处理标签--line:'.__LINE__,$tag_group],'qyweixin/code_mark_user_log',true);
                        if (isset($tag_group['add_tag']) && $tag_group['add_tag']) {
                            $add_tag = array_merge($tag_group['add_tag'], $add_tag);
                        }
                        if (isset($tag_group['tag_arr']) && $tag_group['tag_arr']) {
                            $tag_arr = array_merge($tag_group['tag_arr'], $tag_arr);
                        }
                        if (isset($tag_group['group_id']) && $tag_group['group_id'] && !$val1['qy_group_id']) {
                            M('Village_qywx_code_label_group')->where(['label_group_id'=>$val1['label_group_id']])->setField('qy_group_id', $tag_group['group_id']);
                        }
                    }
                }
            }
            fdump_api(['标签--line:'.__LINE__,$tag_arr],'qyweixin/code_mark_user_log',true);
            if ($tag_arr) {
                foreach ($label_list as &$val3) {
                    if ($val3['label_name'] && isset($tag_arr[$val3['label_name']]) && $tag_arr[$val3['label_name']]) {
                        $qy_tag_id = $tag_arr[$val3['label_name']];
                        M('Village_qywx_code_label')->where(['label_id'=>$val3['label_id']])->setField('qy_tag_id', $qy_tag_id);
                    }
                }
            }
            fdump_api(['标签--line:'.__LINE__,$add_tag],'qyweixin/code_mark_user_log',true);
            if ($add_tag) {
                $mark_tag = $this->mark_tag($customer_id, [],$add_tag);
                fdump_api(['打标签：'.__LINE__,$add_tag,$mark_tag],'qyweixin/code_mark_user_log',1);
            }
            return true;
        }
    }

    public function qy_add_corp_tag($property_id,$group_id='',$group_name='',$order=0,$tag=[], $param=[],$is_replace=false) {
        if ($property_id) {
            $where['bind_id'] = $property_id;
        }
        fdump_api(['标签组--line:'.__LINE__,$property_id,$group_id='',$group_name,$order,$tag, $param],'qyweixin/qy_add_corp_tag_log',true);
        if (!$group_id && !$group_name) {
            return false;
        }
        $corpid = M('House_enterprise_wx_bind')->where($where)->getField('corpid');
        fdump_api(['标签组--line:'.__LINE__,$corpid],'qyweixin/qy_add_corp_tag_log',true);
        if (!$corpid) {
            return false;
        }
        $house_corp_tag_group = M('House_corp_tag_group');
        $where_tag_group = [];
        if ($group_id) {
            $where_tag_group['group_id'] = $group_id;
        }
        if ($group_name) {
            $where_tag_group['group_name'] = $group_name;
        }
        $where_tag_group['property_id'] = $property_id;
        $corp_tag_group = $house_corp_tag_group->where($where_tag_group)->find();
        if ($corp_tag_group) {
            if (!$group_id && $corp_tag_group['group_id']) {
                $group_id = $corp_tag_group['group_id'];
            }
            if (!$group_name && $corp_tag_group['group_name']) {
                $group_name = $corp_tag_group['group_name'];
            }
            if (!$order && $corp_tag_group['order']) {
                $order = $corp_tag_group['order'];
            }
        }
        $db_house_corp_tag = M('House_corp_tag');
        $tag_new = [];
        foreach ($tag as $val) {
            if ($val && $group_id) {
                $where_repeat = [
                    'group_id' => $group_id,
                    'name' => $val['name'],
                ];
                $repeat_id = $db_house_corp_tag->where($where_repeat)->getField('corp_tag_id');
                if (!$repeat_id) {
                    $tag_new[] = [
                        'name' => $val['name'],
                        'order' => $val['order']
                    ];
                }
            }
        }
        $token_info = $this->get_qy_access_token($corpid,$property_id);
        fdump_api(['添加标签：'.__LINE__,$token_info,$tag_new],'qyweixin/qy_add_corp_tag_log',1);
        if (!$token_info['errcode'] && $token_info['access_token']) {
            $access_token = $token_info['access_token'];
            $add_corp_tag_url = "https://qyapi.weixin.qq.com/cgi-bin/externalcontact/add_corp_tag?access_token={$access_token}";
            $tag_group = [];
            if ($group_id) {
                $tag_group['group_id'] = $group_id;
            }
            $tag_group['group_name'] = $group_name;
            $tag_group['order'] = $order;
            if ($tag) {
                $tag_group['tag'] = $tag;
            }
            $add_corp_tag = $this->curlPost($add_corp_tag_url, json_encode($tag_group,JSON_UNESCAPED_UNICODE));
            fdump_api(['添加标签请求：'.__LINE__,$add_corp_tag_url,$tag_group,$add_corp_tag],'qyweixin/qy_add_corp_tag_log',1);
            if (!is_array($add_corp_tag)) {
                $add_corp_tag = json_decode($add_corp_tag,true);
            }
            if (isset($add_corp_tag['errcode']) && $add_corp_tag['errcode'] == 40068) {
                if (isset($tag_group['group_id'])) {
                    unset($tag_group['group_id']);
                }
                $tag_group['group_name'] = $this->checkStr($group_name);
                $add_corp_tag = $this->curlPost($add_corp_tag_url, json_encode($tag_group,JSON_UNESCAPED_UNICODE));
                fdump_api(['2次添加标签请求：'.__LINE__,$add_corp_tag_url,$tag_group,$add_corp_tag],'qyweixin/qy_add_corp_tag_log',1);
                if (!is_array($add_corp_tag)) {
                    $add_corp_tag = json_decode($add_corp_tag,true);
                }
            }
            if (!$add_corp_tag['errcode']) {
                if ($add_corp_tag['tag_group']) {
                    $tag_group = $add_corp_tag['tag_group'];
                    $group_id = $tag_group['group_id'];
                    $group_name = $tag_group['group_name'];
                    $create_time = $tag_group['create_time'];
                    $corp_tag = $tag_group['tag'];
                    $add_tag = [];
                    // 记录下标签组
                    $group_msg = [
                        'group_id' => $group_id,
                        'property_id' => $property_id,
                        'group_name' => $group_name,
                        'order' => $add_corp_tag['order']?intval($add_corp_tag['order']) : 0,
                        'add_time' => $create_time ? $create_time : time(),
                    ];
                    $tag_group_id = $house_corp_tag_group->add($group_msg);
                    // 记录下标签
                    $tag_arr = [];
                    if ($corp_tag) {
                        $db_village_qywx_bind_label = M('Village_qywx_bind_label');
                        $now_time = time();
                        foreach ($corp_tag as $val) {
                            $msg = [
                                'name' => $val['name'],
                                'tag_id' => $val['id'],
                                'group_id' => $group_id,
                                'property_id' => $property_id,
                                'tag_group_id' => $tag_group_id,
                                'create_time' => $val['create_time'] ? $val['create_time'] : time(),
                                'order' => $val['order'],
                                'add_time' => $now_time,
                            ];
                            $add_tag[] = $val['id'];
                            $tag_arr[$val['name']] = $val['id'];
                            $corp_tag_id = $db_house_corp_tag->add($msg);
                            if ($corp_tag_id && !empty($param) && $param['customer_id'] && $param['ExternalUserID']) {
                                $bind_data = [
                                    'bind_type' => 0,
                                    'property_id' => $property_id,
                                    'customer_id' => $param['customer_id'],
                                    'ExternalUserID' => $param['ExternalUserID'],
                                    'corp_tag_id' => $corp_tag_id,
                                    'name' => $val['name'],
                                    'tag_id' => $val['id'],
                                    'add_time' => $now_time,
                                ];
                                $db_village_qywx_bind_label->add($bind_data);
                            }
                        }
                    }
                }
                $tag_group['add_tag'] = $add_tag;
                $tag_group['tag_arr'] = $tag_arr;
                return $tag_group;
            } else {
                fdump_api(['添加标签失败：'.__LINE__,$add_corp_tag],'qyweixin/add_corp_tag_err_log',1);
                return false;
            }
        } elseif ($this->replace_code == $token_info['errcode'] && !$is_replace) {
            $token_info = $this->get_qy_access_token($corpid,$property_id,0,true);
            if (!$token_info['errcode'] && $token_info['access_token']) {
                $this->qy_add_corp_tag($property_id,$group_id,$group_name,$order,$tag_new,$param, true);
            }
        }
        return false;
    }

    /**
     * Notes: 给对应用户打标签
     * @param $user_bind
     * @param $contact_way_user
     * @param $housekeeper_info
     * @return bool
     * @author: wanzy
     * @date_time: 2021/2/2 13:20
     */
    public function mark_contact_way_tag($user_bind, $contact_way_user,$housekeeper_info) {
        if (empty($housekeeper_info) || empty($contact_way_user) || empty($user_bind)) {
            return false;
        }
        fdump_api(['给客户添加标签：'.__LINE__,$user_bind,$contact_way_user,$housekeeper_info],'qyweixin/mark_contact_way_tag_log',1);
        if ($housekeeper_info['property_id']) {
            $where_data_config = [
                'property_id' =>$housekeeper_info['property_id'],
                'is_syn' => 1,
            ];
            $house_village_data_config = M('House_village_data_config')->where($where_data_config)->select();
        }
        if (!isset($user_bind['authentication_field'])) {
            $where_bind = [
                'pigcms_id' => $user_bind['pigcms_id']
            ];
            $authentication_field = M('House_village_user_bind')->where($where_bind)->getField('authentication_field');
        } else {
            $authentication_field = $user_bind['authentication_field'];
        }
        $authentication_field = unserialize($authentication_field);
        $house_corp_tag_group = M('House_corp_tag_group');
        $db_house_corp_tag = M('House_corp_tag');
        fdump_api(['客户资料字段：'.__LINE__,$house_village_data_config],'qyweixin/mark_contact_way_tag_log',1);
        if ($house_village_data_config && $authentication_field) {
            // 客户绑定标签
            $add_tag = [];
            foreach ($house_village_data_config as $val) {
                if ($val['corp_tag_group_id'] && $val['key'] && $authentication_field[$val['key']] && $authentication_field[$val['key']]['value']) {
                    $value = $authentication_field[$val['key']]['value'];
                    $where_tag = [
                        'group_id' => $val['corp_tag_group_id'],
                        'name' => $value,
                    ];
                    $tag_id = $db_house_corp_tag->where($where_tag)->getField('tag_id');
                    if ($tag_id) {
                        $add_tag[] = $tag_id;
                    } else {
                        $tags = [];
                        $tags[] = [
                            'name' => $value,
                            'order' => 0
                        ];
                        $add_corp_tag = $this->add_corp_tag($val['acid'], $val, $val['property_id'],'', $val['title'], $val['sort'], $tags,false,$contact_way_user);

                        fdump_api(['添加组标签line：'.__LINE__,$add_corp_tag],'qyweixin/mark_contact_way_tag_log',1);
                        if ($add_corp_tag && $add_corp_tag['tag']) {
                            foreach ($add_corp_tag['tag'] as $k1) {
                                $add_tag[] = $k1['id'];
                            }
                        }
                    }
                } elseif ($val['key'] && $authentication_field[$val['key']] && $authentication_field[$val['key']]['value']) {
                    $where_tag_group = [];
                    $where_tag_group['group_name'] = $val['title'];
                    $where_tag_group['group_id'] = ['neq', ''];
                    $where_tag_group['property_id'] = $val['property_id'];
                    $corp_tag_group = $house_corp_tag_group->where($where_tag_group)->find();
                    $value = $authentication_field[$val['key']]['value'];
                    if ($corp_tag_group && $corp_tag_group['group_id']) {
                        $where_tag = [
                            'group_id' => $corp_tag_group['group_id'],
                            'name' => $value,
                        ];
                        $tag_id = $db_house_corp_tag->where($where_tag)->getField('tag_id');
                        if ($tag_id) {
                            $add_tag[] = $tag_id;
                        } else {
                            $tags = [];
                            $tags[] = [
                                'name' => $value,
                                'order' => 0
                            ];
                            $add_corp_tag = $this->add_corp_tag($val['acid'], $val, $val['property_id'], '', $val['title'], $val['sort'], $tags,false,$contact_way_user);

                            fdump_api(['添加组标签line：'.__LINE__,$add_corp_tag],'qyweixin/mark_contact_way_tag_log',1);
                            if ($add_corp_tag && $add_corp_tag['tag']) {
                                foreach ($add_corp_tag['tag'] as $k1) {
                                    $add_tag[] = $k1['id'];
                                }
                            }
                        }
                    } else {
                        $tags = [];
                        $tags[] = [
                            'name' => $value,
                            'order' => 0
                        ];
                        $add_corp_tag = $this->add_corp_tag($val['acid'], $val, $val['property_id'], '', $val['title'], $val['sort'], $tags,false,$contact_way_user);

                        fdump_api(['添加组标签line：'.__LINE__,$add_corp_tag],'qyweixin/mark_contact_way_tag_log',1);
                        if ($add_corp_tag && $add_corp_tag['tag']) {
                            foreach ($add_corp_tag['tag'] as $k1) {
                                $add_tag[] = $k1['id'];
                            }
                        }
                    }
                }
            }
        }
        $tag_arr = $this->tag_arr;
        if ($tag_arr) {
            $where_village = [
                'village_id' => $user_bind['village_id']
            ];
            $village_name = M('House_village')->where($where_village)->getField('village_name');
            $where_single = [
                'id' => $user_bind['single_id']
            ];
            $single_name = M('House_village_single')->where($where_single)->getField('single_name');
            $where_floor = [
                'floor_id' => $user_bind['floor_id']
            ];
            $floor_name = M('House_village_floor')->where($where_floor)->getField('floor_name');
            $where_floor = [
                'id' => $user_bind['layer_id']
            ];
            $layer_name = M('House_village_layer')->where($where_floor)->getField('layer_name');
            $where_vacancy = [
                'pigcms_id' => $user_bind['vacancy_id']
            ];
            $room = M('House_village_user_vacancy')->where($where_vacancy)->getField('room');

            $type = $user_bind['type'];
            $open_house_user_name = C('config.open_house_user_name');
            if (0==$type) {
                if ($open_house_user_name) {
                    $type_desc = "房主&社长";
                } else {
                    $type_desc = "房主";
                }
            } elseif (1==$type){
                if ($open_house_user_name) {
                    $type_desc = "亲友";
                } else {
                    $type_desc = "家属";
                }
            } elseif (2==$type){
                if ($open_house_user_name) {
                    $type_desc = "租客&社员";
                } else {
                    $type_desc = "租客";
                }
            }
            foreach ($tag_arr as &$item) {
                $where_tag_group = [];
                $where_tag_group['group_name'] = $item['name'];
                $where_tag_group['group_id'] = ['neq', ''];
                $where_tag_group['property_id'] = $housekeeper_info['property_id'];
                $corp_tag_group = $house_corp_tag_group->where($where_tag_group)->find();
                if ($corp_tag_group && $corp_tag_group['group_id']) {
                    if ('type' == $item['key']) {
                        $value = $type_desc;
                    } elseif ('room' == $item['key']) {
                        $value = $room;
                    } elseif ('layer_name' == $item['key']) {
                        $value = $layer_name;
                    } elseif ('floor_name' == $item['key']) {
                        $value = $floor_name;
                    } elseif ('single_name' == $item['key']) {
                        $value = $single_name;
                    } elseif ('village_name' == $item['key']) {
                        $value = $village_name;
                    } else {
                        continue;
                    }
                    $where_tag = [
                        'group_id' => $corp_tag_group['group_id'],
                        'name' => $value,
                    ];
                    $tag_id = $db_house_corp_tag->where($where_tag)->getField('tag_id');
                    if ($tag_id) {
                        $add_tag[] = $tag_id;
                    } else {
                        $tags = [];
                        $tags[] = [
                            'name' => $value,
                            'order' => 0
                        ];
                        $add_corp_tag = $this->add_corp_tag(0, [], $housekeeper_info['property_id'],'',$item['name'],$item['order'], $tags,false,$contact_way_user);

                        fdump_api(['添加组标签line：'.__LINE__,$add_corp_tag],'qyweixin/mark_contact_way_tag_log',1);
                        if ($add_corp_tag && $add_corp_tag['group_id']) {
                            $where_tag = [
                                'group_id' => $corp_tag_group['group_id'],
                                'name' => $value,
                            ];
                            if ($add_corp_tag['tag'] && $add_corp_tag['tag'][0]) {
                                $tags_info = $add_corp_tag['tag'][0];
                                $tag_id = $tags_info['id'];
                                $add_tag[] = $tag_id;
                            } else {
                                $tag_id = $db_house_corp_tag->where($where_tag)->getField('tag_id');
                                if ($tag_id) {
                                    $add_tag[] = $tag_id;
                                } else {
                                    $tag[] = [
                                        'name' => $value,
                                        'order' => 0
                                    ];
                                }
                            }
                        }
                    }
                } else {
                    if ('type' == $item['key']) {
                        $value = $type_desc;
                    } elseif ('room' == $item['key']) {
                        $value = $room;
                    } elseif ('layer_name' == $item['key']) {
                        $value = $layer_name;
                    } elseif ('floor_name' == $item['key']) {
                        $value = $floor_name;
                    } elseif ('single_name' == $item['key']) {
                        $value = $single_name;
                    } elseif ('village_name' == $item['key']) {
                        $value = $village_name;
                    } else {
                        continue;
                    }
                    $tags = [];
                    $tags[] = [
                        'name' => $value,
                        'order' => 0
                    ];
                    $add_corp_tag = $this->add_corp_tag(0, [],$housekeeper_info['property_id'],'',$item['name'],$item['order'], $tags,false,$contact_way_user);

                    fdump_api(['添加组标签line：'.__LINE__,$add_corp_tag],'qyweixin/mark_contact_way_tag_log',1);
                    if ($add_corp_tag && $add_corp_tag['group_id']) {
                        $where_tag = [
                            'group_id' => $corp_tag_group['group_id'],
                            'name' => $value,
                        ];
                        if ($add_corp_tag['tag'] && $add_corp_tag['tag'][0]) {
                            $tags_info = $add_corp_tag['tag'][0];
                            $tag_id = $tags_info['id'];
                            $add_tag[] = $tag_id;
                        } else {
                            $tag_id = $db_house_corp_tag->where($where_tag)->getField('tag_id');
                            if ($tag_id) {
                                $add_tag[] = $tag_id;
                            }
                        }
                    }
                }
            }
        }
        if ($add_tag) {
            $mark_tag = $this->mark_tag($contact_way_user['customer_id'], $contact_way_user,$add_tag);
            fdump_api(['添加客户标签line：'.__LINE__,$add_tag,$mark_tag],'qyweixin/mark_contact_way_tag_log',1);
        }
        return true;
    }

    /**
     * Notes: 添加组标签
     * @param int $acid
     * @param array $house_village_data_config
     * @param string $group_id
     * @param string $group_name
     * @param int $order
     * @param array $tag
     * 微信传参
     * {
     *   "group_id": "GROUP_ID",
     *   "group_name": "GROUP_NAME",
     *   "order": 1,
     *   "tag": [{
     *              "name": "TAG_NAME_1",
     *              "order": 1
     *          },
     *         {
     *              "name": "TAG_NAME_2",
     *              "order": 2
     *          }
     *   ],
     *   "agentid" : 1000014
     *   }
     * 注意:
     * 如果要向指定的标签组下添加标签，需要填写group_id参数；如果要创建一个全新的标签组以及标签，则需要通过group_name参数指定新标签组名称，如果填写的groupname已经存在，则会在此标签组下新建标签。
     * 如果填写了group_id参数，则group_name和标签组的order参数会被忽略。
     * 不支持创建空标签组。
     * 标签组内的标签不可同名，如果传入多个同名标签，则只会创建一个。
     * @return bool
     * @author: wanzy
     * @date_time: 2021/1/30 17:03
     */
    public function add_corp_tag($acid,$house_village_data_config=[],$property_id=0, $group_id='', $group_name='', $order=0, $tag=[], $is_replace=false,$contact_way_user=[]) {
        if ($this->property_id) {
            $where['bind_id'] = $this->property_id;
        } else {
            $property = session('property');
            if ($property && $property['id']) {
                $where['bind_id'] = $property['id'];
            }
        }
        $corpid = M('House_enterprise_wx_bind')->where($where)->getField('corpid');
        if (!$corpid) {
            return false;
        }
        if (!$acid && !$group_id && !$group_name) {
            fdump_api(['数据缺少：'.__LINE__,$acid,$house_village_data_config,$property_id, $group_id, $group_name, $order, $tag, $is_replace,$contact_way_user],'qyweixin/add_corp_tag',1);
            return false;
        }
        if ($acid && !$house_village_data_config) {
            $where_data_config = [
                'acid' =>$acid
            ];
            $house_village_data_config = M('House_village_data_config')->where($where_data_config)->find();
        }
        if (!$property_id && $house_village_data_config) {
            $property_id = $house_village_data_config['property_id'];
        }
        $house_corp_tag_group = M('House_corp_tag_group');
        if (!$acid && !$house_village_data_config && $property_id) {
            $where_tag_group = [];
            if ($group_id) {
                $where_tag_group['group_id'] = $group_id;
            }
            if ($group_name) {
                $where_tag_group['group_name'] = $group_name;
            }
            $where_tag_group['property_id'] = $property_id;
            $corp_tag_group = $house_corp_tag_group->where($where_tag_group)->find();
            if ($corp_tag_group) {
                if (!$group_id && $corp_tag_group['group_id']) {
                    $group_id = $corp_tag_group['group_id'];
                }
                if (!$group_name && $corp_tag_group['group_name']) {
                    $group_name = $corp_tag_group['group_name'];
                }
                if (!$order && $corp_tag_group['order']) {
                    $order = $corp_tag_group['order'];
                }
            }
        }
        if (!$group_id && $house_village_data_config['corp_tag_group_id'] && 1==$house_village_data_config['corp_tag_status']) {
            $group_id = $house_village_data_config['corp_tag_group_id'];
        }
        if (!$group_name && $house_village_data_config['title']) {
            $group_name = $house_village_data_config['title'];
        }
        if (!$order && $house_village_data_config['sort']) {
            $order = $house_village_data_config['sort'];
        }
        $db_house_corp_tag = M('House_corp_tag');
        if ($house_village_data_config && $house_village_data_config['type']==2 && !$tag) {
            if ($house_village_data_config['is_system'] == 1) {
                $use_field = D('House_village')->get_system_value($house_village_data_config['key']);
            } else {
                $use_field = explode(',',$house_village_data_config['use_field']);
            }
            $tag = [];
            foreach ($use_field as $key => $val) {
                if ($val && $group_id) {
                    $where_repeat = [
                        'group_id' => $group_id,
                        'name' => $val,
                    ];
                    $repeat_id = $db_house_corp_tag->where($where_repeat)->getField('corp_tag_id');
                    if (!$repeat_id) {
                        $tag[] = [
                            'name' => $val,
                            'order' => $key
                        ];
                    }
                }
            }
        }
        $token_info = $this->get_qy_access_token($corpid,$property_id);
        fdump_api(['添加标签：'.__LINE__,$token_info,$house_village_data_config],'qyweixin/add_corp_tag_log',1);

        if (!$token_info['errcode'] && $token_info['access_token']) {
            $access_token = $token_info['access_token'];
            $add_corp_tag_url = "https://qyapi.weixin.qq.com/cgi-bin/externalcontact/add_corp_tag?access_token={$access_token}";
            $tag_group = [];
            if ($group_id) {
                $tag_group['group_id'] = $group_id;
            }
            $tag_group['group_name'] = $group_name;
            $tag_group['order'] = $order;
            if ($tag) {
                $tag_group['tag'] = $tag;
            }
            $add_corp_tag = $this->curlPost($add_corp_tag_url, json_encode($tag_group,JSON_UNESCAPED_UNICODE));
            fdump_api(['添加标签：'.__LINE__,$add_corp_tag_url,$tag_group,$add_corp_tag],'qyweixin/add_corp_tag_log',1);
            if (!is_array($add_corp_tag)) {
                $add_corp_tag = json_decode($add_corp_tag,true);
            }
            if (!$add_corp_tag['errcode']) {
                if ($add_corp_tag['tag_group']) {
                    $tag_group = $add_corp_tag['tag_group'];
                    $group_id = $tag_group['group_id'];
                    $group_name = $tag_group['group_name'];
                    $create_time = $tag_group['create_time'];
                    $corp_tag = $tag_group['tag'];
                    $where_data_config = [
                        'acid' =>$acid
                    ];
                    $set = [
                        'syn_time' => $create_time ? $create_time : time(),
                        'corp_tag_group_id' => $group_id,
                        'corp_tag_status' => 1
                    ];
                    M('House_village_data_config')->where($where_data_config)->save($set);
                    // 记录下标签组
                    $group_msg = [
                        'acid' => $acid,
                        'group_id' => $group_id,
                        'property_id' => $property_id,
                        'group_name' => $group_name,
                        'order' => $add_corp_tag['order']?intval($add_corp_tag['order']) : 0,
                        'add_time' => $create_time ? $create_time : time(),
                    ];
                    $tag_group_id = $house_corp_tag_group->add($group_msg);
                    // 记录下标签
                    if ($corp_tag) {
                        $db_village_qywx_bind_label = M('Village_qywx_bind_label');
                        $now_time = time();
                        foreach ($corp_tag as $val) {
                            $msg = [
                                'acid' => $acid,
                                'name' => $val['name'],
                                'tag_id' => $val['id'],
                                'group_id' => $group_id,
                                'property_id' => $property_id,
                                'tag_group_id' => $tag_group_id,
                                'create_time' => $val['create_time'] ? $val['create_time'] : time(),
                                'order' => $val['order'],
                                'add_time' => $now_time,
                            ];
                            $corp_tag_id = $db_house_corp_tag->add($msg);
                            if ($corp_tag_id && !empty($contact_way_user)) {
                                $bind_data = [
                                    'bind_type' => 0,
                                    'property_id' => $property_id,
                                    'customer_id' => $contact_way_user['customer_id'],
                                    'ExternalUserID' => $contact_way_user['ExternalUserID'],
                                    'corp_tag_id' => $corp_tag_id,
                                    'name' => $val['name'],
                                    'tag_id' => $val['id'],
                                    'add_time' => $now_time,
                                ];
                                $db_village_qywx_bind_label->add($bind_data);
                            }
                        }
                    }
                }
                return $tag_group;
            } else {
                fdump_api(['添加标签失败：'.__LINE__,$add_corp_tag],'qyweixin/add_corp_tag_log',1);
                return false;
            }
        } elseif ($this->replace_code == $token_info['errcode'] && !$is_replace) {
            $token_info = $this->get_qy_access_token($corpid,$property_id,0,true);
            if (!$token_info['errcode'] && $token_info['access_token']) {
                $this->add_corp_tag($acid,$house_village_data_config,$property_id, $group_id, $group_name, $order, $tag,true,$contact_way_user);
            }
        }
        fdump_api(['获取token失败：'.__LINE__,$token_info,$acid,$group_id,$group_name,$tag,$house_village_data_config],'qyweixin/contact_way_access_token_err_log',1);
        return false;
    }

    /**
     * Notes: 编辑标签组-只能更改名称和排序
     * @param $group_id
     * @param string $group_name
     * @param int $order
     * @param bool $is_replace
     * @return bool
     * @author: wanzy
     * @date_time: 2021/2/1 16:17
     */
    public function edit_corp_tag($group_id, $group_name='', $order=-1, $is_replace=false) {
        if ($this->property_id) {
            $where['bind_id'] = $this->property_id;
        } else {
            $property = session('property');
            if ($property && $property['id']) {
                $where['bind_id'] = $property['id'];
            }
        }
        $property_id = $where['bind_id'];
        $corpid = M('House_enterprise_wx_bind')->where($where)->getField('corpid');
        if (!$corpid) {
            return false;
        }
        if (!$group_id || (!$group_id && $order<0)) {
            return false;
        }
        $where_data_config = [
            'corp_tag_group_id' =>$group_id
        ];
        $house_village_data_config = M('House_village_data_config')->where($where_data_config)->find();
        $house_corp_tag_group = M('House_corp_tag_group');
        if (!$house_village_data_config) {
            $where_tag_group['group_id'] = $group_id;
            $corp_tag_group = $house_corp_tag_group->where($where_tag_group)->find();
            if ($group_name==$corp_tag_group['group_name']) {
                // 没有变动直接置空
                $group_name='';
            }
            if ($order==$corp_tag_group['order']) {
                // 没有变动直接置空
                $order=-1;
            }
        } else {
            if ($group_name==$house_village_data_config['group_name']) {
                // 没有变动直接置空
                $group_name='';
            }
            if ($order==$house_village_data_config['sort']) {
                // 没有变动直接置空
                $order=-1;
            }
        }
        if (!$group_id || (!$group_id && $order<0)) {
            return false;
        }
        $token_info = $this->get_qy_access_token($corpid,$property_id);
        fdump_api(['获取token失败：'.__LINE__,$token_info,$house_village_data_config,$corp_tag_group],'qyweixin/edit_corp_tag_log',1);

        if (!$token_info['errcode'] && $token_info['access_token']) {
            $access_token = $token_info['access_token'];
            $edit_corp_tag_url = "https://qyapi.weixin.qq.com/cgi-bin/externalcontact/edit_corp_tag?access_token={$access_token}";
            $tag_group = [];
            $tag_group['group_id'] = $group_id;
            if ($group_name) {
                $tag_group['group_name'] = $group_name;
            }
            if ($order>0) {
                $tag_group['order'] = $order;
            }
            $edit_corp_tag = $this->curlPost($edit_corp_tag_url, json_encode($tag_group,JSON_UNESCAPED_UNICODE));
            fdump_api(['编辑标签：'.__LINE__,$edit_corp_tag_url,$tag_group,$edit_corp_tag],'qyweixin/edit_corp_tag_log',1);
            return true;
        } elseif ($this->replace_code == $token_info['errcode'] && !$is_replace) {
            $token_info = $this->get_qy_access_token($corpid,$property_id,0,true);
            if (!$token_info['errcode'] && $token_info['access_token']) {
                $this->edit_corp_tag($group_id, $group_name, $order, true);
            }
        }
        fdump_api(['编辑标签：'.__LINE__,$token_info,$group_id,$group_name,$house_village_data_config,$corp_tag_group],'qyweixin/contact_way_access_token_err_log',1);
        return false;
    }

    /**
     * Notes:
     * @param array $corp_tag_id 本地记载标签id组
     * @param array $tag_group_id 本地记载标签组id组
     * @param array $tag_id 企业微信标签id组
     * @param array $group_id 企业微信标签组id组
     * @param bool $is_replace
     * @return bool
     * @author: wanzy
     * @date_time: 2021/2/1 16:21
     */
    public function del_corp_tag($corp_tag_id=[],$tag_group_id=[],$tag_id=[],$group_id=[], $is_replace=false,$property_id=0) {
        if (empty($tag_id) && $corp_tag_id) {
            $tag_id = M('House_corp_tag')->where(['corp_tag_id'=>['in', $corp_tag_id]])->getField('tag_id',true);
        }
        if (empty($group_id) && $tag_group_id) {
            $group_id = M('House_corp_tag_group')->where(['tag_group_id'=>['in', $tag_group_id]])->getField('group_id',true);
        }
        if (empty($tag_id) && empty($group_id)) {
            return false;
        }
        if (!$property_id && $this->property_id) {
            $property_id = $this->property_id;
        }

        $token_info = $this->get_qy_access_token('',$property_id);
        fdump_api(['删除标签：'.__LINE__,$token_info,$corp_tag_id,$tag_group_id,$tag_id,$group_id],'qyweixin/del_corp_tag_log',1);

        if (!$token_info['errcode'] && $token_info['access_token']) {
            $access_token = $token_info['access_token'];
            $del_corp_tag_url = "https://qyapi.weixin.qq.com/cgi-bin/externalcontact/del_corp_tag?access_token={$access_token}";
            $tag_group = [];
            if ($tag_id) {
                $tag_group['tag_id'] = $tag_id;
            }
            if ($group_id) {
                $tag_group['group_id'] = $group_id;
            }
            $del_corp_tag = $this->curlPost($del_corp_tag_url, json_encode($tag_group,JSON_UNESCAPED_UNICODE));
            fdump_api(['删除标签请求：'.__LINE__,$del_corp_tag_url,$tag_group,$del_corp_tag],'qyweixin/del_corp_tag_log',1);
            if (!is_array($del_corp_tag)) {
                $del_corp_tag = json_decode($del_corp_tag,true);
            }
            if (!$del_corp_tag['errcode']) {
                if ($tag_id) {
                    M('House_corp_tag')->where(['tag_id'=>['in', $tag_id]])->delete();
                }
                if ($group_id) {
                    M('House_corp_tag_group')->where(['group_id'=>['in', $group_id]])->delete();
                    $set = [
                        'corp_tag_group_id' => 0,
                        'corp_tag_status' => 2,
                        'syn_time' => time(),
                    ];
                    M('House_village_data_config')->where(['corp_tag_group_id'=> ['in', $group_id]])->data($set)->save();
                }
                return true;
            } else {
                fdump_api(['删除标签失败：'.__LINE__,$del_corp_tag],'qyweixin/del_corp_tag_err_log',1);
                return false;
            }
        } elseif ($this->replace_code == $token_info['errcode'] && !$is_replace) {
            $token_info = $this->get_qy_access_token('',$property_id);
            if (!$token_info['errcode'] && $token_info['access_token']) {
                $this->del_corp_tag($corp_tag_id,$tag_group_id,$tag_id,$group_id,true,$property_id);
            }
        }
        fdump_api(['获取token失败：'.__LINE__,$token_info,$corp_tag_id,$tag_group_id,$tag_id,$group_id],'qyweixin/contact_way_access_token_err_log',1);
        return false;
    }

    /**
     * Notes: 给客户打标签
     * @param $customer_id
     * @param array $contact_way_user_data
     * @param array $add_tag
     * @param array $remove_tag
     * @return bool
     * @author: wanzy
     * @date_time: 2021/2/1 18:57
     */
    public function mark_tag($customer_id, $contact_way_user_data=[],$add_tag=[],$remove_tag=[],$is_replace=false,$property_id=0) {
        fdump_api(['给客户打标签--line:'.__LINE__,$customer_id, $contact_way_user_data,$add_tag,$remove_tag,$is_replace,$property_id],'qyweixin/mark_tag_log',true);
        $db_house_contact_way_user = M('House_contact_way_user');
        if (!$contact_way_user_data) {
            $where = [
                'customer_id' => $customer_id,
            ];
            $contact_way_user_data = $db_house_contact_way_user->where($where)->find();
        }
        if ($contact_way_user_data['UserID']) {
            $userid = $contact_way_user_data['UserID'];
        } elseif ($contact_way_user_data['userid']) {
            $userid = $contact_way_user_data['userid'];
        }
        if ($contact_way_user_data['ExternalUserID']) {
            $external_userid = $contact_way_user_data['ExternalUserID'];
        } elseif ($contact_way_user_data['external_userid']) {
            $external_userid = $contact_way_user_data['external_userid'];
        }
        if ((!$userid || !$external_userid) && !$add_tag && !$remove_tag) {
            return false;
        }
        if (!$property_id && $contact_way_user_data['property_id']) {
            $property_id = $contact_way_user_data['property_id'];
        } elseif (!$property_id && $this->property_id) {
            $property_id = $this->property_id;
        }

        $token_info = $this->get_qy_access_token('',$property_id);
        fdump_api(['给客户打标签--line:'.__LINE__,$token_info, $property_id,$userid,$external_userid,$add_tag,$remove_tag],'qyweixin/mark_tag_log',true);

        if (!$token_info['errcode'] && $token_info['access_token']) {
            $access_token = $token_info['access_token'];
            $mark_tag_url = "https://qyapi.weixin.qq.com/cgi-bin/externalcontact/mark_tag?access_token={$access_token}";
            $tag_group = [];
            $tag_group['userid'] = $userid;
            $tag_group['external_userid'] = $external_userid;
            if ($add_tag) {
                $tag_group['add_tag'] = $add_tag;
                if (!$remove_tag &&  $contact_way_user_data['tag_data']) {
                    $tag_data = $contact_way_user_data['tag_data'];
                    $tag_data = unserialize($tag_data);
                    $remove_tag = [];
                    if ($tag_data) {
                        foreach ($tag_data as $val) {
                            if (!in_array($val,$add_tag)) {
                                $remove_tag[] = $val;
                            }
                        }
                    }
                }
            }
            if ($remove_tag) {
                $tag_group['remove_tag'] = $remove_tag;
            }
            $mark_tag = $this->curlPost($mark_tag_url, json_encode($tag_group,JSON_UNESCAPED_UNICODE));
            fdump($mark_tag_url,'api/log/'.date('Ymd').'/qyweixin/mark_tag_log',true);
            fdump($tag_group,'api/log/'.date('Ymd').'/qyweixin/mark_tag_log',true);
            fdump($mark_tag,'api/log/'.date('Ymd').'/qyweixin/mark_tag_log',true);
            if (!is_array($mark_tag)) {
                $mark_tag = json_decode($mark_tag,true);
            }
            if (!$mark_tag['errcode']) {
                $set = [
                    'tag_data' => serialize($add_tag),
                    'tag_time' =>time()
                ];

                $where = [
                    'customer_id'=>$contact_way_user_data['customer_id']
                ];
                $db_house_contact_way_user->where($where)->data($set)->save();
                return true;
            } else {
                fdump('打标签失败>>>'.__LINE__,'api/log/'.date('Ymd').'/qyweixin/mark_tag_err_log',true);
                fdump($mark_tag,'api/log/'.date('Ymd').'/qyweixin/mark_tag_err_log',true);
                return false;
            }
        } elseif ($this->replace_code == $token_info['errcode'] && !$is_replace) {
            $token_info = $this->get_qy_access_token('',$property_id);
            if (!$token_info['errcode'] && $token_info['access_token']) {
                $this->mark_tag($customer_id, $contact_way_user_data,$add_tag,$remove_tag,true);
            }
        }
        fdump('获取token失败>>>'.__LINE__,'api/log/'.date('Ymd').'/qyweixin/contact_way_access_token_err_log',true);
        fdump('方法：'.__FUNCTION__,'api/log/'.date('Ymd').'/qyweixin/contact_way_access_token_err_log',true);
        fdump($token_info,'api/log/'.date('Ymd').'/qyweixin/contact_way_access_token_err_log',true);
        fdump($customer_id,'api/log/'.date('Ymd').'/qyweixin/contact_way_access_token_err_log',true);
        fdump($contact_way_user_data,'api/log/'.date('Ymd').'/qyweixin/contact_way_access_token_err_log',true);
        fdump($add_tag,'api/log/'.date('Ymd').'/qyweixin/contact_way_access_token_err_log',true);
        fdump($remove_tag,'api/log/'.date('Ymd').'/qyweixin/contact_way_access_token_err_log',true);
        return false;
    }

    /**
     * Notes: 删除标签
     * @param $data_config
     * @return bool
     * @author: wanzy
     * @date_time: 2021/2/4 17:41
     */
    public function del_village_data_config_qy($data_config) {
        // 同步删除标签
        if (1==$data_config['corp_tag_status'] && $data_config['corp_tag_group_id']) {
            $house_corp_tag_group = M('House_corp_tag_group');
            $house_corp_tag = M('House_corp_tag');
            $where_tag_group['group_id'] = $data_config['corp_tag_group_id'];
            $corp_tag_group = $house_corp_tag_group->where($where_tag_group)->find();
            $corp_tag_id = [];
            $tag_group_id = [];
            $tag_group_id[] = $corp_tag_group['tag_group_id'];
            $where_tag['group_id'] = $data_config['corp_tag_group_id'];
            $tag_id = $house_corp_tag->where($where_tag)->getField('tag_id');
            if (!$tag_id) {
                $tag_id = [];
            }
            $group_id = [];
            $group_id[] = $data_config['corp_tag_group_id'];
            $property_id = $corp_tag_group['property_id'] ? $corp_tag_group['property_id'] : 0;
            $village_id = $house_corp_tag['village_id'] ? $house_corp_tag['village_id'] : 0;
            $param = [
                '$property_id' => $property_id,
                'village_id' => $village_id,
            ];
            D('Property_group')->del_corp_tag($corp_tag_id, $tag_group_id, $tag_id, $group_id,$param);
        } else {
            $house_corp_tag_group = M('House_corp_tag_group');
            $where_tag_group = [];
            $where_tag_group['group_name'] = $data_config['title'];
            $where_tag_group['group_id'] = ['neq', ''];
            $where_tag_group['property_id'] = $data_config['property_id'];
            $corp_tag_group = $house_corp_tag_group->where($where_tag_group)->find();
            if ($corp_tag_group && $corp_tag_group['group_id']) {
                $house_corp_tag = M('House_corp_tag');
                $where_tag['group_id'] = $corp_tag_group['group_id'];
                $tag_id = $house_corp_tag->where($where_tag)->getField('tag_id');
                $group_id = [];
                $group_id[] = $corp_tag_group['group_id'];
                $corp_tag_id = [];
                $tag_group_id = [];
                $tag_group_id[] = $corp_tag_group['tag_group_id'];
                $property_id = $corp_tag_group['property_id'] ? $corp_tag_group['property_id'] : 0;
                $village_id = $house_corp_tag['village_id'] ? $house_corp_tag['village_id'] : 0;
                $param = [
                    '$property_id' => $property_id,
                    'village_id' => $village_id,
                ];
                D('Property_group')->del_corp_tag($corp_tag_id, $tag_group_id, $tag_id, $group_id,$param);
            }
        }
        return true;
    }

    public function curlGet($url){
        $ch = curl_init();
        $header = "Accept-Charset: utf-8";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $temp = curl_exec($ch);
        return $temp;
    }

    public function curlPost($url,$data,$timeout=15, $type = null){
        $ch = curl_init();
        $headers[] = "Accept-Charset: utf-8";//"Content-Type: multipart/form-data; boundary=" .  uniqid('------------------');
        if($type == 'json'){
            $headers[] = "Content-type: application/json;charset=utf-8";
        }
// 		$header = "Accept-Charset: utf-8";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.80 Safari/537.36');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch ,CURLOPT_TIMEOUT ,$timeout);
        $result = curl_exec($ch);

        //关闭curl
        curl_close($ch);
        // echo $result;exit;
        $result = json_decode($result, true);
        return $result;
    }

    /**
     * Notes: 迁移业主属性 从小区至对应物业
     * @return array
     * @author: wanzy
     * @date_time: 2021/1/30 16:01
     */
    public function property_data_config() {
        $db_house_village_data_config = M('House_village_data_config');

        $where_repeat = [
            'property_id' => ['gt', 0],
        ];
        $property_arr = $db_house_village_data_config->where($where_repeat)->group('property_id')->getField('property_id',true);

        $where_data_config = [
            'c.status' => 1,
            'b.status' => 1,
            'a.property_id' => 0,
        ];
        if ($property_arr) {
            $where_data_config['b.property_id'] = ['not in', $property_arr];
        }
        $order = 'is_system DESC, is_close DESC, is_display DESC, is_must DESC';
        $field = 'a.*,b.property_id';
        $info = $db_house_village_data_config->alias('a')
            ->JOIN('left join '. C('DB_PREFIX') . 'house_village as b on a.village_id=b.village_id')
            ->JOIN('left join '. C('DB_PREFIX') . 'house_property as c on c.id=b.property_id')
            ->where($where_data_config)
            ->group('a.title,b.property_id')
            ->order($order)
            ->field($field)
            ->select();
        $data = [];
        foreach ($info as $key=>$val) {
            if ($val['property_id']) {
                unset($val['acid']);
                $val['village_id'] = 0;
                foreach ($val as $k => $item) {
                    if ($item==NUll) {
                        unset($val[$k]);
                    }
                }
                $db_house_village_data_config->add($val);
                $data[] = $val;
            }
        }
        // 隐藏小区的业主资料
        $where_menus = [
            'name' => '业主资料管理'
        ];
        $set = [
            'show' => 0,
            'status' => 0,
        ];
        M('House_menu_new')->where($where_menus)->save($set);
        return $data;
    }

    /**
     * Notes: 计划任务调用打标签
     * @param $bind_id
     * @param int $customer_id
     * @param int $property_id
     * @return bool
     * @author: wanzy
     * @date_time: 2021/2/5 11:00
     */
    public function mark_user($bind_id,$customer_id=0,$property_id=0) {
        $db_house_village_user_bind = M('House_village_user_bind');
        $field = 'pigcms_id, uid, village_id, type, single_id, floor_id, layer_id, vacancy_id,address,authentication_field';
        $where_bind['pigcms_id'] = $bind_id;
        $user_bind = $db_house_village_user_bind->where($where_bind)->field($field)->group('vacancy_id')->order('status ASC,pigcms_id ASC')->find();
        if (empty($user_bind)) {
            return false;
        }
        $db_house_contact_way_user = M('House_contact_way_user');
        $where = [];
        $where['customer_id'] = $customer_id;
        $contact_way_user = $db_house_contact_way_user->where($where)->order('customer_id ASC')->find();
        if (empty($contact_way_user)) {
            return false;
        }
        $db_house_services_housekeeper = M('House_services_housekeeper');
        $where_housekeeper = [
            'housekeeper_id' => $contact_way_user['housekeeper_id']
        ];
        $housekeeper_info = $db_house_services_housekeeper->where($where_housekeeper)->find();
        if (empty($housekeeper_info)) {
            return false;
        }
        $info = $this->mark_contact_way_tag($user_bind, $contact_way_user,$housekeeper_info);
        return $info;
    }

    /**
     * Notes: 用户打标签生成计划任务
     * @param $bind_id
     * @param int $village_id
     * @param int $property_id
     * @param array $user_bind
     * @return bool
     * @author: wanzy
     * @date_time: 2021/2/5 10:58
     */
    public function add_mark_user_plan($bind_id,$village_id=0,$property_id=0,$user_bind=[]) {
        if(!$property_id) {
            if (!$village_id && $user_bind['village_id']) {
                $village_id = intval($user_bind['village_id']);
            } elseif (!$village_id) {
                $village_id = M('House_village_user_bind')->where(['pigcms_id' => $bind_id])->getField('village_id');
            }
            $property_id = M('House_village')->where(['village_id' => $village_id])->getField('property_id');
        }
        if (!$bind_id || !$property_id) {
            return false;
        }
        $where = [];
        $where['bind_id'] = $property_id;
        $corpid = M('House_enterprise_wx_bind')->where($where)->getField('corpid');
        if (!$corpid) {
            return false;
        }
        $db_house_contact_way_user = M('House_contact_way_user');
        $where = [
            'bind_id' => $bind_id
        ];
        $customer_id = $db_house_contact_way_user->where($where)->getField('customer_id');
        if (!$customer_id) {
            return false;
        }
        $param = [
            'bind_id' => $bind_id,
            'type' => 'mark_user',
            'property_id' => $property_id,
            'customer_id' => $customer_id,
            'unique_id' =>  'mark_user_'.$bind_id.'_'.$property_id.'_'.$customer_id,
            'plan_time' => -10,
        ];
        $this->add_plan($param);
    }

    public function add_plan($param){
        $now_time = time();
        $arr= array();
        if (!isset($param['type']) || !$param['type']) {
            $param['type'] = 'mark_user';
        }
        if ($param['unique_id']) {
            $unique_id = $param['unique_id'];
            unset($param['unique_id']);
        } else {
            $unique_id = 'house_village_'.$param['property_id'];
        }
        if ($param['time_type']) {
            $time_type = $param['time_type'];
            unset($param['time_type']);
        } else {
            $time_type = 1;
        }
        if ($param['plan_time']) {
            $plan_time = $param['plan_time'];
            unset($param['plan_time']);
        } else {
            $plan_time = 0;
        }
        if ($param['space_time']) {
            $space_time = $param['space_time'];
            unset($param['space_time']);
        } else {
            $space_time = 0;
        }
        $arr['param'] = serialize($param);
        $arr['plan_time'] = $plan_time;
        $arr['space_time'] = $space_time;
        $arr['add_time'] = $now_time;
        $arr['file'] = 'sub_house_village';
        $arr['time_type'] = $time_type;
        $arr['unique_id'] = $unique_id;
        $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
        fdump_api(['给客户打标签--line:'.__LINE__,$arr],'qyweixin/add_plan_log',true);
        $task_id = M('Process_sub_plan')->data($arr)->add();
        fdump_api(['给客户打标签--line:'.__LINE__,$task_id],'qyweixin/add_plan_log',true);
        return array('task_id'=>$task_id);
    }

    // 获取客户信息
    public function externalContactUser($external_userid,$property_id,$village_id, $is_replace=false) {
        if (!$external_userid) {
            return false;
        }
        $token_info = $this->get_qy_access_token('',$property_id,$village_id);
        fdump_api(['$token_info' => $token_info], 'qyweixin/contact_way_receive_log',true);
        if (!$token_info['errcode'] && $token_info['access_token'] && $external_userid) {
            $access_token = $token_info['access_token'];
            $external_contact_user_url = "https://qyapi.weixin.qq.com/cgi-bin/externalcontact/get?access_token={$access_token}&external_userid={$external_userid}";
            $user_msg = $this->curlGet($external_contact_user_url);
            fdump_api(['$external_contact_user_url' => $external_contact_user_url, '$user_msg' => $user_msg], 'qyweixin/contact_way_receive_log',true);
            if (!is_array($user_msg)) {
                $user_msg = json_decode($user_msg,true);
            }
            if (!$user_msg['errcode']) {
                $external_contact = $user_msg['external_contact'];
                return $external_contact;
            } else {
                fdump('获取失败>>>'.__LINE__,'api/log/'.date('Ymd').'/qyweixin/externalContactUserErrLog',true);
                fdump($external_userid,'api/log/'.date('Ymd').'/qyweixin/externalContactUserErrLog',true);
                fdump($user_msg,'api/log/'.date('Ymd').'/qyweixin/externalContactUserErrLog',true);
                return false;
            }
        } elseif ($this->replace_code == $token_info['errcode'] && !$is_replace) {
            $token_info = $this->get_qy_access_token('',$property_id,$village_id,true);
            if (!$token_info['errcode'] && $token_info['access_token']) {
                return $this->externalContactUser($external_userid,$property_id,$village_id, true);
            }
        }
        fdump('获取token失败>>>'.__LINE__,'api/log/'.date('Ymd').'/qyweixin/externalContactUserErrLog',true);
        fdump('方法：'.__FUNCTION__,'api/log/'.date('Ymd').'/qyweixin/externalContactUserErrLog',true);
        fdump($token_info,'api/log/'.date('Ymd').'/qyweixin/externalContactUserErrLog',true);
        fdump($external_userid,'api/log/'.date('Ymd').'/qyweixin/externalContactUserErrLog',true);
        return false;
    }


    /**
     * Notes: 获取企业微信临时图片素材id
     * @param $imgUrl
     * @param bool $is_replace
     * @return array|mixed
     * @author: wanzy
     * @date_time: 2021/3/25 17:00
     */
    public function uploadQywxMedia($imgUrl,$property_id,$village_id, $is_replace=false){
        $imgUrl = replace_file_domain($imgUrl);
        $file_handle = new file_handle();
        if($file_handle->check_open_oss()) {
            $file_handle->download($imgUrl);
            $file_path = $file_handle->get_path($imgUrl);
            $imgUrl = $_SERVER['DOCUMENT_ROOT'].$file_path;
        } else{
            $imgUrl = $_SERVER['DOCUMENT_ROOT'].str_replace(C('config.site_url'),'',$imgUrl);
        }
        $token_info = $this->get_qy_access_token('',$property_id,$village_id);
        if (!$token_info['errcode'] && $token_info['access_token'] && $imgUrl) {
            $file  = $imgUrl;
            if (class_exists('\CURLFile')) {
                $data['media'] = new \CURLFile(realpath($file));
            } else {
                $data['media'] = '@'.realpath($file);
            }
            $return = $this->curlPost('https://qyapi.weixin.qq.com/cgi-bin/media/upload?type=image&access_token='.$token_info['access_token'],$data);
            fdump_api(['图片上传：'.__LINE__,$token_info,$data,$return,$imgUrl,$property_id,$village_id, $is_replace],'qyweixin/uploadQywxMediaLog',1);
            if (!is_array($return)) {
                $return = json_decode($return,true);
            }
            return $return['media_id'];
        } elseif ($this->replace_code == $token_info['errcode'] && !$is_replace) {
            $token_info = $this->get_qy_access_token('',$property_id,$village_id,true);
            if (!$token_info['errcode'] && $token_info['access_token']) {
                $this->uploadQywxMedia($imgUrl, $property_id,$village_id,true);
            }
        }
        return false;
    }


    public $nameStrReplaceSearch = [':','*','?','"','<','>','｜'];
    public function checkStr($name) {
        $name = trim($name);
        $name = str_replace($this->nameStrReplaceSearch, '', $name);
        return $name;
    }
}
?>