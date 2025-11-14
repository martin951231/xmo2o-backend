<?php
/*
 * 城市区域管理
 *
 */

class AreaAction extends BaseAction
{

    private function allArea($areaId)
    {
        static $allAreas;
        if ($allAreas) {

        } else {
            $condition_area['is_open'] = array('neq', 2);
            $areaList = D('Area')->field(true)->where($condition_area)->select();
            $tmpMap = array();
            foreach ($areaList as $area) {
                $tmpMap[$area['area_id']] = $area['area_pid'];
            }
            return array_reverse($this->ids($tmpMap, $areaId));//[父ID，子ID, 孙ID, ..., $sortId（指定的ID）]
        }
    }

    private function ids($idsArray, $areaId, $ids = array())
    {
        $ids[] = $areaId;
        if (isset($idsArray[$areaId]) && $idsArray[$areaId]) {
            return $this->ids($idsArray, $idsArray[$areaId], $ids);
        } else {
            return $ids;
        }
    }

    public function index()
    {
        if ($this->system_session['street_id']) {
            $this->redirect('Area/area_street', ['type' => 0, 'pid' => $this->system_session['area_id']]);
        }

        $database_area = D('Area');
        if (!isset($_GET['type'])) {
            if (!$this->config['many_city']) {
                if ($_GET['type'] != 4) $_GET['type'] = 3;
                $_GET['pid'] = !empty($_GET['pid']) ? $_GET['pid'] : $this->config['now_city'];
            } else {
                if ($this->config['now_province']) {
                    if (empty($_GET['type'])) $_GET['type'] = 2;
                    $_GET['pid'] = !empty($_GET['pid']) ? $_GET['pid'] : $this->config['now_province'];
                } else {
                    if (empty($_GET['type'])) $_GET['type'] = 1;
                    $_GET['pid'] = !empty($_GET['pid']) ? $_GET['pid'] : 0;
                }
            }
        }

        if ($this->system_session['area_id']) {
            $allAreaIds = $this->allArea($_GET['pid']);
            if (!in_array($this->system_session['area_id'], $allAreaIds)) {
                $tempArea = $database_area->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
//				$_GET['pid'] = $this->system_session['area_id'];
                $_GET['pid'] = $tempArea['area_pid'];
                $_GET['type'] = $tempArea['area_type'];
            }
        }

        $condition_area['area_pid'] = $_GET['pid'];
        $tempArea['area_id'] && $condition_area['area_id'] = $tempArea['area_id'];
        $condition_area['area_type'] = $_GET['type'];
        $condition_area['is_open'] = array('neq', 2);

        if ($this->system_session['area_id'] == $_GET['pid'] || $this->system_session['area_id'] == '0') {
            $this->assign('is_system', true);
        }

        $now_area = $database_area->field(true)->where(array('area_id' => $_GET['pid']))->find();
        $this->assign('now_area', $now_area);

        if ($_GET['type'] == 4) {
            $order = '`area_sort` DESC,`is_open` DESC,`first_pinyin` ASC';
        } else {
            $order = '`area_sort` DESC,`is_open` DESC,`area_id` ASC';
        }
        $area_list = $database_area->field(true)->where($condition_area)->order($order)->select();

        $this->assign('area_list', $area_list);
        switch ($_GET['type']) {
            case 1:
                $now_type_str = '省份';
                break;
            case 2:
                $now_type_str = '城市';
                break;
            case 3:
                $now_type_str = '区域';
                break;
            default:
                $now_type_str = '商圈';
        }
        $this->assign('now_type_str', $now_type_str);
        if ($_GET['type'] == 4) {
            $circle_category = D('Circle_category')->getField('id,name');
            $this->assign('circle_category', $circle_category);
        }
        if (0 == $this->config['is_open_merchant_foodshop_discount']) {
            $this->assign('merchant_close', '1');
        }

        $this->display();
    }
    public function cityRegion(){

        if ($this->system_session['street_id']) {
            $this->redirect('Area/area_street', ['type' => 0, 'pid' => $this->system_session['area_id']]);
        }
        $database_area = D('Area');
        $area_type=isset($_GET['type']) ? $_GET['type']:0;
        $tempArea=array();
        $login_area_type=0;
        if ($this->system_session['area_id']) {
            $tempArea = $database_area->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if ($tempArea['area_type']==3) {
                $this->redirect('Area/area_city_street', ['type' => 0, 'pid' => $this->system_session['area_id']]);
                exit();
            }
            if(!isset($_GET['pid']) || empty($_GET['pid'])){
                $_GET['pid'] = $this->system_session['area_id'];
                $_GET['type'] = $tempArea['area_type'];
                $area_type=$tempArea['area_type'];
                $login_area_type=$area_type;
            }
        }
        $area_type=$area_type+1;
        $condition_area=array();
        $condition_area['area_pid'] = $_GET['pid'];
        $condition_area['area_type'] = $area_type;
        $condition_area['is_open'] = array('neq', 2);
        $now_area = $database_area->field(true)->where(array('area_id' => $_GET['pid']))->find();
        $this->assign('now_area', $now_area);
        $order = '`area_sort` DESC,`is_open` DESC,`area_id` ASC';
        $area_list = $database_area->field(true)->where($condition_area)->order($order)->select();
        $this->assign('area_list', $area_list);
        switch ($area_type) {
            case 1:
                $now_type_str = '省份';
                break;
            case 2:
                $now_type_str = '城市';
                break;
            case 3:
                $now_type_str = '区县';
                break;
            default:
                $now_type_str = '区县';
        }
        
        $this->assign('login_area_type',$login_area_type);
        $this->assign('now_type_str', $now_type_str);
        $this->display();
    }

    public function area_city_street()
    {

        $database_area_street = D('Area_street');
        $area_id = !empty($_GET['pid']) ? $_GET['pid'] : 0;
        $type = !empty($_GET['type']) ? $_GET['type'] : 0;
        $street_where = array('area_pid' => $area_id);
        if (0 == $type) {
            $now_area = M('Area')->field(true)->where(array('area_id' => $area_id))->find();
            $this->assign('now_area', $now_area);
            $street_where['area_type'] = 0;
        } else {
            $now_area = $database_area_street->field(true)->where(array('area_id' => $area_id))->find();
            $this->assign('now_area', $now_area);
            $street_where['area_type'] = 1;
        }
        if ($this->system_session['street_id']) {
            $street_where['area_id'] = $this->system_session['street_id'];
        }
        $login_area_type=0;
        $database_area = D('Area');
        if ($this->system_session['area_id']) {
            $tempArea = $database_area->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            $login_area_type=$tempArea['area_type'];
        }
        $area_street = $database_area_street->order('is_open desc,area_sort desc')->where($street_where)->select();
        switch ($type) {
            case 0:
                $now_type_str = '街道/乡镇';
                break;
            case 1:
                $now_type_str = '社区/村';
                break;
        }
        $this->assign('now_type_str', $now_type_str);
        $this->assign('login_area_type', $login_area_type); 
        $this->assign('area_list', $area_street);
        $this->assign('area_id', $area_id);

        $area_login_url = $this->config['site_url'] . '/v20/public/platform/#/user/community/login';
        $this->assign('area_login_url', $area_login_url);
        $nowUrl = $_SERVER['REQUEST_URI'];
        $this->assign('nowUrl', $nowUrl);

        $this->display();

    }
    public function allAreaStreet() {
        $_GET['from'] = 'house';
        $this->area_street();
    }

    //	列表
    public function area_street()
    {
        $database_area_street = D('Area_street');
        $area_id = !empty($_GET['pid']) ? $_GET['pid'] : 0;
        $type = !empty($_GET['type']) ? $_GET['type'] : 0;
        $from = !empty($_GET['from']) ? $_GET['from'] : 'system';
        $street_where = [];
        $address = '';
        $areaInfos = [];
        if ('system' == $from) {
            $street_where = array('area_pid' => $area_id);
            if (0==$type) {
                $now_area = M('Area')->field(true)->where(array('area_id' => $area_id))->find();
                $this->assign('now_area', $now_area);
                $street_where['area_type'] = 0;
            } else {
                $now_area = $database_area_street->field(true)->where(array('area_id' => $area_id))->find();
                $this->assign('now_area', $now_area);
                $street_where['area_type'] = 1;
            }
            if ($this->system_session['street_id']) {
                $street_where['area_id'] = $this->system_session['street_id'];
            }
        } elseif ('house' == $from) {
            if ($area_id) {
                $street_where = array('area_pid' => $area_id);
                $street_where['area_type'] = 1;
                $now_area = $database_area_street->field(true)->where(array('area_id' => $area_id))->find();
                $this->assign('now_area', $now_area);
                $address = $now_area['area_name'];
                // 街道上级
                if ($now_area['area_pid']) {
                    $addressPid = $this->getAllAreaAddress($now_area['area_pid']);
                    $address = $addressPid.' '.$address;
                }
            } else {
                $street_where = array('area_type' => 0);
                $dbArea = M('Area');
                $areaInfos = $dbArea->getField('area_id, area_pid,area_name,area_type');
                foreach($areaInfos as $kk=>$vv){
                    if($vv['area_pid']>0 && $vv['area_type']==3){
                        foreach($areaInfos as $k1k=>$v1v){
                            if($vv['area_pid']==$v1v['area_id']){
                                if($v1v['area_pid']>0){
                                    $areaInfos[$kk]['c_area_id']=$v1v['area_id'];
                                    $areaInfos[$kk]['p_area_id']=$v1v['area_pid'];
                                }else{
                                    $areaInfos[$kk]['p_area_id']=$v1v['area_pid'];
                                }
                                break;
                            }
                        }
                    }elseif($vv['area_pid']>0 && $vv['area_type']==2){
                        $areaInfos[$kk]['p_area_id']=$vv['area_pid'];
                    }
                }
                if($this->system_session['area_id']>0 && empty($area_id)){
                    if($this->system_session['area_type']==1){
                        $area_pid_arr=array($this->system_session['area_id']);
                        foreach ($areaInfos as $akk=>$avv){
                            if($avv['area_pid']==$this->system_session['area_id']){
                                $area_pid_arr[]=$avv['area_id'];
                            }else if(isset($avv['p_area_id']) && ($avv['p_area_id']==$this->system_session['area_id'])){
                                $area_pid_arr[]=$avv['area_id'];
                            }
                        }
                        $street_where['area_pid'] = array('in',$area_pid_arr);
                    }else if($this->system_session['area_type']==2){
                        $area_pid_arr=array($this->system_session['area_id']);
                        foreach ($areaInfos as $akk=>$avv){
                            if($avv['area_pid']==$this->system_session['area_id']){
                                $area_pid_arr[]=$avv['area_id'];
                            }
                        }
                        $street_where['area_pid'] = array('in',$area_pid_arr);
                    }else if($this->system_session['area_type']==3){
                        $street_where['area_pid'] = $this->system_session['area_id'];
                    }
                    
                }

            }
        }

        $area_street = $database_area_street->order('is_open desc,area_sort desc')->where($street_where)->select();
        if ('house' == $from && $area_street) {
            foreach ($area_street as &$item) {
                if($item['is_default']==1 && $item['area_type']==1){
                    $item['area_name']=$item['area_name'].'（默认社区）';
                }
                if ($area_id && $address) {
                    $item['addressTxt'] = $address;
                } elseif ($areaInfos) {
                    $item['addressTxt'] = $this->getAllAreaAddress($item['area_pid'],'',$areaInfos);
                }
            }
        }
        switch ($type) {
            case 0:
                $now_type_str = '街道/乡镇';
                break;
            case 1:
                $now_type_str = '社区/村';
                break;
        }
        $this->assign('now_type_str', $now_type_str);

        $this->assign('area_list', $area_street);
        $this->assign('area_id', $area_id);

        // 可复制该链接进入街道/社区后台：xxxxx
        $area_login_url = $this->config['site_url'].'/v20/public/platform/#/user/community/login';
        $this->assign('area_login_url', $area_login_url);

        $nowUrl =  $_SERVER['REQUEST_URI'];
        $this->assign('nowUrl', $nowUrl);

        $this->display('area_street');
    }

    public function getAllAreaAddress($area_id, $address='', $areaInfos=[]) {
        $dbArea = M('Area');
        if ($area_id) {
            if (isset($areaInfos[$area_id])&&$areaInfos[$area_id]) {
                $areaPidInfo = $areaInfos[$area_id];
            } else {
                $areaPidInfo = $dbArea->where(['area_id'=> $area_id])->field('area_pid, area_name')->find();
            }
            if ($areaPidInfo && $areaPidInfo['area_name']) {
                $address = $areaPidInfo['area_name'].' '.$address;
            }
            if ($areaPidInfo && isset($areaPidInfo['area_pid']) && $areaPidInfo['area_pid']) {
                return $this->getAllAreaAddress($areaPidInfo['area_pid'], $address);
            }
        }
        return $address;
    }


    //	添加街道和社区
    public function add_area_street()
    {
        $database_area_street = D('Area_street');
        $is_func_yougejianet_special=D('Config_customization')->getfuncYougejianetSpecial();
        if (IS_POST) {
            $_POST['account'] = trim($_POST['account']);
            if (!$_POST['self_area_id'] && $_POST['account'] && !$_POST['pwd']) {
                $this->frame_error_tips('填写了账号就必须填写密码~');
            }
            if ($_POST['account']) {
                $area_street = $database_area_street->where(array('account'=>$_POST['account']))->find();
                if ($area_street && $_POST['self_area_id']!=$area_street['area_id']) {
                    $this->frame_error_tips('该账号已经被使用请使用其他账号~');
                }
            }

            if($_POST['pwd']) {
                $_POST['pwd'] = md5($_POST['pwd']);
            } else {
                unset($_POST['pwd']);
            }
            if ($_POST['long_lat']) {
                $long = explode(",", $_POST['long_lat']);
                $_POST['long'] = $long[0];
                $_POST['lat'] = $long[1];
                unset($_POST['long_lat']);
            }
            if (isset($_POST['area_id'])&&$_POST['area_id']) {
                $_POST['area_pid'] = $_POST['area_id'];
            }
            if (isset($_POST['self_area_id'])&&$_POST['self_area_id']) {
                $_POST['area_id'] = $_POST['self_area_id'];
            } else {
                $_POST['area_id'] = 0;
            }
            if (isset($_POST['login_phone'])) {
                $login_phone = trim($_POST['login_phone']);
                unset($_POST['login_phone']);
            }
            if ($_POST['area_id']) {
                unset($_POST['area_type']);
                unset($_POST['area_pid']);
                if(isset($_POST['is_default']) && $_POST['is_default']==1){
                     $database_area_street->where(array('area_type'=>1))->data(array('is_default'=>'0'))->save();
                }
                $_POST['last_time'] = time();
                $set = $database_area_street->data($_POST)->save();
                if ($set) {
                    $database_property = D('House_property');
                    $whereWorkWeiXinPhone = [
                        'type'        => $database_property::WORK_WEI_XIN_PHONE_LOGIN_TYPE,
                        'from_table'  => $database_property::WORK_WEI_XIN_PHONE_FROM_AREA_STREET,
                        'from_id'     => $_POST['area_id'],
                    ];
                    $db_work_weixin_phone = M('Work_weixin_phone');
                    $workWeiXinPhoneInfo = $db_work_weixin_phone->where($whereWorkWeiXinPhone)->find();
                    // 处理添加登录凭依手机号
                    if (isset($login_phone) && $login_phone && !$workWeiXinPhoneInfo) {
                        $dataParam = [
                            'type'        => $database_property::WORK_WEI_XIN_PHONE_LOGIN_TYPE,
                            'from_table'  => $database_property::WORK_WEI_XIN_PHONE_FROM_AREA_STREET,
                            'from_id'     => $_POST['area_id'],
                            'phone'       => $login_phone,
                            'add_time'    => time(),
                        ];
                        $db_work_weixin_phone->data($dataParam)->add();
                    } elseif (isset($login_phone) && $workWeiXinPhoneInfo) {
                        $dataParam = [
                            'phone'          => $login_phone,
                            'update_time'    => time(),
                        ];
                        $db_work_weixin_phone->where($whereWorkWeiXinPhone)->save($dataParam);
                    }
                    
                    $this->frame_submit_tips(1, '修改成功！');
                } else {
                    $this->frame_error_tips('修改失败！请重试~');
                }
            } else {
                $_POST['add_time'] = time();
                if (!$_POST['area_pid']) {
                    $this->frame_error_tips('添加失败，缺少父级！请重试~');
                }
                $add = $database_area_street->add($_POST);
                if ($add) {
                    $database_property = D('House_property');
                    $db_work_weixin_phone = M('Work_weixin_phone');
                    if (isset($login_phone) && $login_phone) {
                        $dataParam = [
                            'type'        => $database_property::WORK_WEI_XIN_PHONE_LOGIN_TYPE,
                            'from_table'  => $database_property::WORK_WEI_XIN_PHONE_FROM_AREA_STREET,
                            'from_id'     => $add,
                            'phone'       => $login_phone,
                            'add_time'    => time(),
                        ];
                        $db_work_weixin_phone->data($dataParam)->add();
                    }
                    $this->frame_submit_tips(1, '添加成功！');
                } else {
                    $this->frame_error_tips( '添加失败！请重试~');
                }
            }
        } else {
            $area_id = !empty($_GET['area_id']) ? $_GET['area_id'] : 0;
            $area_type = !empty($_GET['area_type']) ? $_GET['area_type'] : 0;
            switch ($area_type) {
                case 0:
                    $now_type_str = '街道/乡镇';
                    break;
                case 1:
                    $now_type_str = '社区/村';
                    break;
            }
            $this->assign('now_type_str', $now_type_str);
            $this->assign('bg_color', '#F3F3F3');
            if ($area_id) {
                $area_street = $database_area_street->where(array('area_id' => $area_id))->find();
                if (floatval($area_street['long'])==0 && floatval($area_street['lat']==0)) {
                    $area_street['long'] = '';
                    $area_street['lat'] = '';
                } else {
                    $area_street['long_lat'] = $area_street['long'].','.$area_street['lat'];
                }
                $this->assign('area_street', $area_street);
                if ($area_street['area_type']==0) {
                    $addressTxt = $this->getAllAreaAddress($area_street['area_pid']);
                    $addressTxt = $addressTxt.' '.$area_street['area_name'];
                } else {
                    $area_parent = $database_area_street->where(array('area_id' => $area_street['area_pid']))->find();
                    $addressTxt = $this->getAllAreaAddress($area_parent['area_pid']);
                    $addressTxt = $addressTxt.' '.$area_parent['area_name'].' '.$area_street['area_name'];
                }
                $this->assign('addressTxt', $addressTxt);
            } elseif ( $_GET['area_pid']) {
                if ($area_type==0) {
                    $addressTxt = $this->getAllAreaAddress($_GET['area_pid']);
                } else {
                    $area_parent = $database_area_street->where(array('area_id' => $_GET['area_pid']))->find();
                    $addressTxt = $this->getAllAreaAddress($area_parent['area_pid']);
                    $addressTxt = $addressTxt.' '.$area_parent['area_name'];
                }
                $this->assign('addressTxt', $addressTxt);
            }
            $default_city = M('Area')->where(array('area_id'=>$this->config['now_city']))->find();
            $this->assign('default_city', $default_city);


            $database_property = D('House_property');
            $whereWorkWeiXinPhone = [
                'type'        => $database_property::WORK_WEI_XIN_PHONE_LOGIN_TYPE,
                'from_table'  => $database_property::WORK_WEI_XIN_PHONE_FROM_AREA_STREET,
                'from_id'     => $area_id,
            ];
            $db_work_weixin_phone = M('Work_weixin_phone');
            $workWeiXinPhoneInfo = $db_work_weixin_phone->where($whereWorkWeiXinPhone)->find();

            if (!$workWeiXinPhoneInfo) {
                $workWeiXinPhoneInfo = [];
            }
            $this->assign('workWeiXinPhoneInfo', $workWeiXinPhoneInfo);
            $this->assign('is_func_yougejianet_special', $is_func_yougejianet_special);
            
            $this->display();
        }
    }

    //	删除商场
    public function del_area_street()
    {
        $del = M('Area_street')->where(array('area_id' => $_POST['area_id']))->delete();
        if ($del) {
            $this->success('删除成功！');
        } else {
            $this->error('删除失败！请重试~');
        }
    }

    //	商场列表
    public function area_market()
    {
        $database_area = D('Area_market');
        $area_id = !empty($_GET['pid']) ? $_GET['pid'] : 0;
        $now_area = M('Area')->field(true)->where(array('area_id' => $area_id))->find();

        $area_market = $database_area->order('market_sort desc')->where(array('area_id' => $area_id))->select();
        if ($area_market) {
            foreach ($area_market as $k => $v) {
                $area_market[$k]['img'] = $this->config['site_url'] . $v['img'];
            }
        }
        $this->assign('now_area', $now_area);
        $this->assign('area_list', $area_market);
        $this->assign('area_id', $area_id);
        $this->display();
    }

    //	添加商场
    public function add_market()
    {
        if (IS_POST) {
            $image = D('Image')->handle($_POST['area_id'], 'market', 0, array('size' => 10), false);
            if (!$image['error']) {
                $_POST = array_merge($_POST, $image['url']);
            } else {
                $this->frame_submit_tips(0, $image['msg']);
            }
            $farea = M('Area')->field(array('area_pid'))->where(array('area_id' => $_POST['area_id']))->find();
            $area = M('Area')->field(array('area_pid'))->where(array('area_id' => $farea['area_pid']))->find();
            $_POST['city_id'] = $area['area_pid'];
            $long = explode(",", $_POST['long_lat']);
            $_POST['long'] = $long[0];
            $_POST['lat'] = $long[1];
            unset($_POST['long_lat']);
            $add = M('Area_market')->add($_POST);
            if ($add) {
                $this->frame_submit_tips(1, '添加成功！');
            } else {
                $this->frame_submit_tips(0, '添加失败！请重试~');
            }
        } else {
            $this->assign('bg_color', '#F3F3F3');
            $this->display();
        }
    }

    //	修改商场
    public function edit_market()
    {
        if (IS_POST) {
            $image = D('Image')->handle($_POST['area_id'], 'market', 0, array('size' => 10), false);
            if (!$image['error']) {
                $_POST = array_merge($_POST, $image['url']);
            }
            $long = explode(",", $_POST['long_lat']);
            $_POST['long'] = $long[0];
            $_POST['lat'] = $long[1];
            unset($_POST['long_lat']);
            $save = M('Area_market')->where(array('market_id' => $_GET['market_id']))->data($_POST)->save();
            if ($save) {
                $this->frame_submit_tips(1, '添加成功！');
            } else {
                $this->frame_submit_tips(0, '添加失败！请重试~');
            }
        } else {
            $market = M('Area_market')->where(array('market_id' => $_GET['market_id']))->find();
            $this->assign('bg_color', '#F3F3F3');
            $this->assign('market', $market);
            $this->display();
        }
    }

    //	删除商场
    public function del_market()
    {
        $del = M('Area_market')->where(array('market_id' => $_POST['market_id']))->delete();
        if ($del) {
            $this->success('删除成功！');
        } else {
            $this->error('删除失败！请重试~');
        }
    }

    //	介绍类型列表
    //public function add_market_type(){
//    	if(IS_POST){
//			$image = D('Image')->handle($_POST['area_id'], 'market', 0, array('size' => 10), false);
//    		if(!$image['error']) {
//				$_POST = array_merge($_POST,$image['url']);
//			}
//			$add	=	M('Area_market_type')->add($_POST);
//			if($add){
//				$this->success('添加成功！');
//			}else{
//				$this->error('添加失败！请重试~');
//			}
//    	}else{
//			$typeimg	=	M('Area_market_type')->field(array('pigcms_id','type_name','type_img'))->select();
//			if($typeimg){
//				foreach($typeimg as $k=>$v){
//					$typeimg[$k]['type_img']	=	$this->config['site_url'].$v['type_img'];
//				}
//			}
//			$this->assign('typeimg',$typeimg);
//			$this->display();
//    	}
//    }

    public function add()
    {
        $database_area = D('Area');
        $now_city = $database_area->where(array('area_id' => $this->config['now_city']))->find();
        import('ORG.Net.Http');
        $http = new Http();
        $url = "http://api.map.baidu.com/place/v2/search?query={$now_city['area_name']}&region={$now_city['area_name']}&output=json&ak=" . $this->config['baidu_map_ak'];
        $result = $http->curlGet($url);
        $result = json_decode($result, true);
        $now_area['lng'] = $result['results'][0]['location']['lng'];
        $now_area['lat'] = $result['results'][0]['location']['lat'];
        $this->assign('now_area', $now_area);
        $this->assign('cat_list', $this->circle_category());

        $current_timezone = C('DEFAULT_TIMEZONE');//当前时区
        $timezone_arr = explode('/', $current_timezone);
        $continent = M("area_timezone")->group('continent')->field('continent')->select();
        $this->assign('continent', $continent);//所有时区分类
        $this->assign('current_continent', $timezone_arr[0]);//当前时区分类
        $timezone = M("area_timezone")->where('continent="' . $timezone_arr[0] . '"')->field('timezone')->order('timezone asc')->select();
        $this->assign('timezone', $timezone);//当前时区分类

        $this->assign('bg_color', '#F3F3F3');
        $this->display();
    }

    //根据分类获取时区
    public function get_timezone()
    {
        $continent = $_POST['continent'];
        if (empty($continent)) {
            $return = array('error' => 1, 'data' => array());
            exit(json_encode($return));
        }
        $timezone = M("area_timezone")->where('continent="' . $continent . '"')->field('timezone')->order('timezone asc')->select();
        $return = array('error' => 0, 'data' => $timezone);
        exit(json_encode($return));
    }

    public function circle_category_list()
    {
        $circle_category_list = M('Circle_category')->select();
        $this->assign('circle_category_list', $circle_category_list);
        $this->display();
    }

    public function circle_category_add()
    {
        if (IS_POST) {
            $category_id = $_POST['category_id'];
            if ($category_id) {
                $result = M('Circle_category')->where(array('id' => $category_id))->save($_POST);
            } else {
                $result = M('Circle_category')->add($_POST);
            }
            if ($result) {
                $this->success('操作成功！');
            } else {
                $this->error('操作失败！');
            }
        } else {
            $category_id = $_GET['category_id'];
            $res = M('Circle_category')->where(array('id' => $category_id))->find();
            $this->assign('res', $res);
            $this->display();
        }
    }

    public function circle_category_del()
    {
        if (IS_POST) {
            $return = M('Circle_category')->where(array('id' => $_POST['id']))->delete();
            import('ORG.Util.Dir');
            Dir::delDirnotself('./runtime');
            $this->success('删除成功！');
        } else {
            $this->error('非法提交,请重新提交~');
        }
    }

    public function modify()
    {
        if (IS_POST) {
            $database_area = D('Area');
//            $condition_area['area_url'] = $_POST['area_url'];
//            if ($database_area->where($condition_area)->find()) {
//                $this->error('数据库中已存在相同的网址标识，请更换。');
//            }
            $long_lat = explode(',', $_POST['long_lat']);
            $_POST['area_lng'] = $long_lat[0]??'';
            $_POST['area_lat'] = $long_lat[1]??'';

            if ($_POST['area_lng'] && $_POST['area_lat'] && $_POST['coordinate_type'] == 'wgs84') {
                list($_POST['area_lng'], $_POST['area_lat']) = Coordinate::wgs84ToBd09($_POST['area_lng'], $_POST['area_lat']);
            }

            unset($_POST['long_lat']);
            unset($_POST['area_url']);
            if(isset($_POST['pwd'])){
                $pwd=trim($_POST['pwd']);
                unset($_POST['pwd']);
                if($pwd){
                    $_POST['pwd']=md5($pwd);
                }
            }
            if(isset($_POST['account']) && !empty($_POST['account'])){
                $account= trim($_POST['account']);
                $whereArr=array('area_id' => array('gt',0),'account'=>$account);
                $tmpArea=$database_area->where($whereArr)->field('area_id,area_name,account')->find();
                if(!empty($tmpArea)){
                    $this->error('账号：'.$account.' 已经存在了，请修改账号！');
                    exit();
                }
                $_POST['account']=$account;
            }
            if($_POST['area_lng'] && $_POST['area_lat']){
                $mapInfo=$database_area->getBaiduMapInfo($_POST['area_lng'],$_POST['area_lat']);
                fdump_api($mapInfo,'00mapinfo',1);
                if($mapInfo && isset($mapInfo['addressComponent']) && isset($mapInfo['addressComponent']['adcode'])){
                    $adcode=$mapInfo['addressComponent']['adcode'];
                    if($adcode && $_POST['area_type']==1){
                        $adcode=substr($this->currentCode,0,2).'0000';
                    }elseif($adcode && $_POST['area_type']==2){
                        $adcode=substr($this->currentCode,0,4).'00';
                    }
                    $_POST['adcode']=$adcode;
                    if(in_array($_POST['area_type'],array(1,2,3))){
                        import('ORG.Net.Http');
                        $http = new Http();
                        /***
                         * https://datav.aliyun.com/portal/school/atlas/area_selector
                         ****/
                        // $url = 'https://geo.datav.aliyun.com/areas_v3/bound/geojson?code=340100_full';
                        $url = 'https://geo.datav.aliyun.com/areas_v3/bound/' . $adcode . '_full.json';
                        if($_POST['area_type']==3){
                            $url = 'https://geo.datav.aliyun.com/areas_v3/bound/' . $adcode . '.json';
                        }
                        $dotresult = $http->curlGet($url, 20);
                        fdump_api(['adcode'=>$adcode,'url'=>$url,'result'=>$dotresult],'00area_selector',1);
                        if ($dotresult) {
                            $area_frame_dot = json_decode($dotresult, true);
                            $_POST['area_frame_dot'] = $area_frame_dot ? json_encode($area_frame_dot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
                        }
                    }
                }
            }
            if ($area_id = $database_area->data($_POST)->add()) {
                $database_area->where(array('area_id'=>$area_id))->setField('area_url', $area_id);
                import('ORG.Util.Dir');
                Dir::delDirnotself('./runtime');
                $this->success('添加成功！');
            } else {
                $this->error('添加失败！请重试~');
            }
        } else {
            $this->error('非法提交,请重新提交~');
        }
    }

    public function edit()
    {
        $database_area = D('Area');
        $this->assign('bg_color', '#F3F3F3');
        $condition_area['area_id'] = $_GET['area_id'];
        $now_area = $database_area->field(true)->where($condition_area)->find();
        $tmpArea = floatval($now_area['area_lng']);
        if (empty($tmpArea) && $_GET['type'] == 4) {
            $now_area_ = $database_area->where(array('area_id' => $now_area['area_pid']))->find();
            $now_city = $database_area->where(array('area_id' => $now_area_['area_pid']))->find();
            import('ORG.Net.Http');
            $http = new Http();
            $url = "http://api.map.baidu.com/place/v2/search?query={$now_city['area_name']}&region={$now_city['area_name']}&output=json&ak=" . $this->config['baidu_map_ak'];
            $result = $http->curlGet($url);
            $result = json_decode($result, true);
            $now_area['area_lng'] = $result['results'][0]['location']['lng'];
            $now_area['area_lat'] = $result['results'][0]['location']['lat'];
            $now_area['notice'] = '您还没有定位，当前商圈定位为该城市中心，请重新定位';
        }

        $current_timezone = C('DEFAULT_TIMEZONE');//当前时区
        $now_area_timezone = $now_area['timezone'];
        $timezone_arr = explode('/', $current_timezone);
        $now_area_timezone_arr = explode('/', $now_area_timezone);

        $continent = M("area_timezone")->group('continent')->field('continent')->select();
        $this->assign('continent', $continent);//所有时区分类
        $sel_timezone = $now_area_timezone_arr[0] ? $now_area_timezone_arr[0] : $timezone_arr[0];
        $this->assign('current_continent', $sel_timezone);//当前时区分类
        $timezone = M("area_timezone")->where('continent="' . $sel_timezone . '"')->field('timezone')->order('timezone asc')->select();
        $this->assign('timezone', $timezone);//当前时区分类

        if (empty($now_area)) {
            $this->frame_error_tips('数据库中没有查询到该信息！');
        }
	    $now_area['edit_commission'] = true;
		if($this->system_session['level'] == 1 && $this->system_session['area_id'] == $_GET['area_id']) {//区域管理员不可修改自己的佣金比例
			$now_area['edit_commission'] = false;
		}
        $this->assign('now_area', $now_area);
        $this->assign('cat_list', $this->circle_category());
        $this->display();
    }

    public function circle_category()
    {
        $circle_category_list = M('Circle_category')->select();
        return $circle_category_list;
    }

    public function amend()
    {
        if (IS_POST) {
            $database_area = D('Area');
            $condition_area['area_url'] = $_POST['area_url'];
            $long_lat = explode(',', $_POST['long_lat']);
            $_POST['area_lng'] = $long_lat[0];
            $_POST['area_lat'] = $long_lat[1];
            unset($_POST['long_lat']);
            unset($_POST['area_url']);
            $area_type = $database_area->where(array('area_id' => $_POST['area_id']))->field('area_type')->find();
            if(isset($_POST['pwd'])){
                $pwd=trim($_POST['pwd']);
                unset($_POST['pwd']);
                if($pwd){
                    $_POST['pwd']=md5($pwd);
                }
            }
            if(isset($_POST['account']) && !empty($_POST['account'])){
                $account= trim($_POST['account']);
                $whereArr=array('area_id' => array('neq',$_POST['area_id']),'account'=>$account);
                $tmpArea=$database_area->where($whereArr)->field('area_id,area_name,account')->find();
                if(!empty($tmpArea)){
                    $this->error('账号：'.$account.' 已经存在了，请修改账号！');
                    exit();
                }
                $_POST['account']=$account;
            }
            if ($_POST['area_lng'] && $_POST['area_lat']) {
                $mapInfo = $database_area->getBaiduMapInfo($_POST['area_lng'], $_POST['area_lat']);
                fdump_api($mapInfo, '00mapinfo', 1);
                if ($mapInfo && isset($mapInfo['addressComponent']) && isset($mapInfo['addressComponent']['adcode'])) {
                    $adcode = $mapInfo['addressComponent']['adcode'];
                    if ($adcode && $area_type['area_type'] == 1) {
                        $adcode = substr($adcode, 0, 2) . '0000';
                    } elseif ($adcode && $area_type['area_type'] == 2) {
                        $adcode = substr($adcode, 0, 4) . '00';
                    }
                    $_POST['adcode'] = $adcode;
                    if(in_array($area_type['area_type'],array(1,2,3))){
                        import('ORG.Net.Http');
                        $http = new Http();
                        /***
                         * https://datav.aliyun.com/portal/school/atlas/area_selector
                         ****/
                        // $url = 'https://geo.datav.aliyun.com/areas_v3/bound/geojson?code=340100_full';
                        $url = 'https://geo.datav.aliyun.com/areas_v3/bound/' . $adcode . '_full.json';
                        if($area_type['area_type']==3){
                            $url = 'https://geo.datav.aliyun.com/areas_v3/bound/' . $adcode . '.json';
                        }
                        $dotresult = $http->curlGet($url, 20);
                        fdump_api(['adcode'=>$adcode,'url'=>$url,'result'=>$dotresult],'00area_selector',1);
                        if ($dotresult) {
                            $area_frame_dot = json_decode($dotresult, true);
                            $_POST['area_frame_dot'] = $area_frame_dot ? json_encode($area_frame_dot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
                        }
                    }

                }
            }
	        if($this->system_session['level'] == 1 && $this->system_session['area_id'] == $_POST['area_id']) {//区域管理员不可修改自己的佣金比例
		        unset($_POST['commission_rate']);
	        }
            if ($database_area->data($_POST)->save() !== FALSE) {
                //import('ORG.Util.Dir');
                //Dir::delDirnotself('./runtime');
                if ($area_type['area_type'] > 3) {
                    $circles = D('group_store')->where(array('circle_id' => $_POST['area_id']))->field('group_id')->select();
                    if (!empty($circles)) {
                        $tmp = '';
                        foreach ($circles as $v) {
                            $tmp .= $v['group_id'] . ',';
                        }
                        $tmp = substr($tmp, 0, -1);
                        $where['group_id'] = array('in', $tmp);
                        D('group')->where($where)->save(array('prefix_title' => $_POST['area_name']));

                    }
                }
                $this->success('修改成功！');
            } else {
                $this->error('修改失败！请重试~');
            }
        } else {
            $this->error('非法提交,请重新提交~');
        }
    }

    public function del()
    {
        if (IS_POST) {
            $return = $this->recursive_del($_POST['area_id']);
            // import('ORG.Util.Dir');
            // Dir::delDirnotself('./runtime');
            $this->success('删除成功！');
        } else {
            $this->error('非法提交,请重新提交~');
        }
    }

    /* 递归删除分类下的子分类且删除自己 */
    protected function recursive_del($area_id)
    {
        $database_area = D('Area');
        $condition_area['area_pid'] = $area_id;
        $now_area = $database_area->field('`area_id`')->where($condition_area)->select();
        if (is_array($now_area)) {
            foreach ($now_area as $key => $value) {
                $this->recursive_del($value['area_id']);
            }
        }

        $condition_del_area['area_id'] = $area_id;
        $database_area->where($condition_del_area)->setField('is_open', 2);
        M()->execute('Update ' . C('DB_PREFIX') . "area set area_url=concat(area_url,'~no_use') where area_id=" . $area_id);
    }

    public function ajax_province()
    {
        $database_area = D('Area');
        $condition_area['area_type'] = 1;
        $condition_area['is_open'] = 1;
        //$this->system


        $province_id = 0;
        if ($this->system_session['area_id']) {
//            if (C('customization.open_view_multi_city') == 1) {
//                $admin_area_list = $this->system_session['area_ids'] ? explode(',', $this->system_session['area_ids']) : [];
//                $now_area_list = D('Area')->field('area_pid')->where(array('area_id' => array('in', $admin_area_list)))->group('area_pid')->select();
//                $now_area_pids = [];
//                foreach ($now_area_list as $item) {
//                    $now_area_pids[] = $item['area_pid'];
//                }
//                $now_province_list = D('Area')->field('area_pid')->where(array('area_id' => array('in', $now_area_pids)))->group('area_pid')->select();
//                $now_province_pids = [];
//                foreach ($now_province_list as $item) {
//                    $now_province_pids[] = $item['area_pid'];
//                }
//                $now_province_pids = array_merge($now_province_pids, $now_area_pids);
//                $condition_area['area_id'] = ['in', $now_province_pids];
//            } else {
                $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
                if ($now_area['area_type'] == 3) {
                    $now_area = D('Area')->field(true)->where(array('area_id' => $now_area['area_pid']))->find();
// 		        $now_area = D('Area')->field(true)->where(array('area_id' => $now_area['area_pid']))->find();
                    $province_id = $now_area['area_pid'];
                } elseif ($now_area['area_type'] == 2) {
// 		        $now_area = D('Area')->field(true)->where(array('area_id' => $now_area['area_pid']))->find();
                    $province_id = $now_area['area_pid'];
                } elseif ($now_area['area_type'] == 1) {
                    $province_id = $this->system_session['area_id'];
                }
//            }
        }
        
        if ($province_id) {
            $condition_area['area_id'] = $province_id;
            $province_list = $database_area->field('`area_id` `id`,`area_name` `name`')->where($condition_area)->order('`area_sort` DESC,`area_id` ASC')->select();
        } else {
            $province_list = $database_area->field('`area_id` `id`,`area_name` `name`')->where($condition_area)->order('`area_sort` DESC,`area_id` ASC')->select();
        }
        if ($this->system_session['area_type'] >= 1 && C('customization.open_view_multi_city') != 1) {
            $return['error'] = 2;
            $return['id'] = $province_list[0]['id'];
            $return['name'] = $province_list[0]['name'];
        } else if (!empty($province_list)) {
            $return['error'] = 0;
            $return['list'] = $province_list;
        } else {
            $return['error'] = 1;
            $return['info'] = '没有开启了的省份！请先开启。';
        }

        exit(json_encode($return));
        
    }

    public function ajax_city()
    {
        $database_area = D('Area');
        if ($this->system_session['area_type'] == 1) {
            $_POST['id'] = $this->system_session['area_id'];
        }

        $condition_area['area_pid'] = intval($_POST['id']);
        if ($_POST['id'] == 0) {
            $return['error'] = 0;
            $return['list'] = array();
            exit(json_encode($return));
        }
        $condition_area['is_open'] = 1;

        $city_id = 0;
        if ($this->system_session['area_id']) {
//            if(C('customization.open_view_multi_city')==1){
//                $admin_area_list = $this->system_session['area_ids']?explode(',',$this->system_session['area_ids']):[];
//                $now_area_list = D('Area')->field('area_pid')->where(array('area_id' => array('in',$admin_area_list)))->group('area_pid')->select();
//                $now_area_pids = [];
//                foreach ($now_area_list as $item){
//                    $now_area_pids[] = $item['area_pid'];
//                }
//                $now_area_pids = array_merge($now_area_pids,$admin_area_list);
//                $condition_area['area_id'] = ['in',$now_area_pids];
//            }else{
                $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
                if ($now_area['area_type'] == 3) {
                    $city_id = $now_area['area_pid'];
                } elseif ($now_area['area_type'] == 2) {
                    $city_id = $this->system_session['area_id'];
                }
//            }
        }

        if ($city_id && $this->system_session['area_id'] != $_POST['id']) {
            $condition_area['area_id'] = $city_id;
            $city_list = $database_area->field('`area_id` `id`,`area_name` `name`,`area_lng` `lng`,`area_lat` `lat`,`shop_main_page_show_ad`,`shop_main_page_center_type`')->where($condition_area)->order('`area_sort` DESC,`area_id` ASC')->select();
        } else {
            $city_list = $database_area->field('`area_id` `id`,`area_name` `name`,`area_lng` `lng`,`area_lat` `lat`,`shop_main_page_show_ad`,`shop_main_page_center_type`')->where($condition_area)->order('`area_sort` DESC,`area_id` ASC')->select();
        }
        //城市缺少经纬度，调用接口补上
        foreach ($city_list as $k => $c) {
            if (empty($c['lat'])) {
                $result = $database_area->address_info($c['name']);
                $lng = $result['result']['location']['lng'];
                $lat = $result['result']['location']['lat'];
                $database_area->where(array('area_id' => $c['id']))->data(array('area_lng' => $lng, 'area_lat' => $lat))->save();
                $city_list[$k]['lat'] = $lat;
                $city_list[$k]['lng'] = $lng;
            }
        }
        //城市缺少经纬度，调用接口补上
        foreach ($city_list as $k => $c) {
            if (empty($c['lat'])) {
                $result = $database_area->address_info($c['name']);
                $lng = $result['result']['location']['lng'];
                $lat = $result['result']['location']['lat'];
                $database_area->where(array('area_id' => $c['id']))->data(array('area_lng' => $lng, 'area_lat' => $lat))->save();
                $city_list[$k]['lat'] = $lat;
                $city_list[$k]['lng'] = $lng;
            }
        }
        if (!$_POST['type'] && $this->system_session['area_type'] > 1) {
            $return['error'] = 2;
            $return['id'] = $city_list[0]['id'];
            $return['name'] = $city_list[0]['name'];
            $return['shop_main_page_show_ad'] = $city_list[0]['shop_main_page_show_ad'];
            $return['shop_main_page_center_type'] = $city_list[0]['shop_main_page_center_type'];
        } else if (!empty($city_list)) {
            $return['error'] = 0;
            $return['list'] = $city_list;
        } else {
            $return['error'] = 1;
            $return['info'] = '［ ' . $_POST['name'] . ' ］ 省份下没有已开启的城市！请先开启城市或删除此省份';
        }
        exit(json_encode($return));
    }

    public function ajax_area()
    {
        $database_area = D('Area');
        if ($this->system_session['area_type'] == 2) {
            $_POST['id'] = $this->system_session['area_id'];
        } elseif ($this->system_session['area_type'] == 3) {
            $area = $database_area->field(true)->where(array('area_id' => $this->system_session['area_id']))->order('`area_sort` DESC,`area_id` ASC')->find();
            $return['error'] = 0;
            $return['list']=array();
            $return['id'] = $area['area_id'];
            $return['name'] = $area['area_name'];
            $return['list'][]=array('id'=> $area['area_id'],'name'=>$area['area_name'],'lat'=>$area['area_lat'],'lng'=>$area['area_lng']);
            exit(json_encode($return));
        }
        $condition_area['area_pid'] = intval($_POST['id']);


        if ($_POST['id'] == 0) {
            $return['error'] = 0;
            $return['list'] = array();
            exit(json_encode($return));
        }
        $condition_area['is_open'] = 1;
        $area_list = $database_area->field('`area_id` `id`,`area_name` `name`,`area_lng` AS lng,`area_lat` AS lat')->where($condition_area)->order('`area_sort` DESC,`area_id` ASC')->select();
        if (!empty($area_list)) {
            $return['error'] = 0;
            $return['list'] = $area_list;
        } else {
            $return['error'] = 1;
            $return['info'] = '［ ' . $_POST['name'] . ' ］ 城市下没有已开启的区域！请先开启区域或删除此城市';
        }
        exit(json_encode($return));
    }

    // 获取 街道/乡镇
    public function ajax_street()
    {
        $database_area = D('Area_street');
        $condition_area['area_pid'] = intval($_POST['id']);

        if ($_POST['id'] == 0) {
            $return['error'] = 0;
            $return['list'] = array();
            exit(json_encode($return));
        }
        $condition_area['is_open'] = 1;
        $condition_area['area_type'] = 0;
        $area_list = $database_area->field('`area_id` `id`,`area_name` `name`,`long` AS lng,lat')->where($condition_area)->order('`area_sort` DESC,`area_id` ASC')->select();
        if (!empty($area_list)) {
            $return['error'] = 0;
            $return['list'] = $area_list;
        } else {
            $return['error'] = 1;
            $return['info'] = '［ ' . $_POST['name'] . ' ］ 城市区域下没有已开启的街道/乡镇！请先开启或添加街道/乡镇或删除此城市区域';
        }
        exit(json_encode($return));
    }

    public function ajax_community(){
        $database_area = D('Area_street');
        $condition_area['area_pid'] = intval($_POST['id']);

        if ($_POST['id'] == 0) {
            $return['error'] = 0;
            $return['list'] = array();
            exit(json_encode($return));
        }
        $condition_area['is_open'] = 1;
        $condition_area['area_type'] = 1;
        $area_list = $database_area->field('`area_id` `id`,`area_name` `name`')->where($condition_area)->order('`area_sort` DESC,`area_id` ASC')->select();
        if (!empty($area_list)) {
            $return['error'] = 0;
            $return['list'] = $area_list;
        } else {
            $return['error'] = 1;
            $return['info'] = '［ ' . $_POST['name'] . ' ］街道/乡镇下没有已开启的社区/村！请先开启或添加社区/村或删除此街道/乡镇';
        }
        exit(json_encode($return));
    }


    public function ajax_house_village(){
        $database_house_village = D('House_village');
        $condition_house['community_id'] = intval($_POST['id']);

        if ($_POST['id'] == 0) {
            $return['error'] = 0;
            $return['list'] = array();
            exit(json_encode($return));
        }
        $condition_house['status'] = 1;
        $area_list = $database_house_village->field('`village_id` `id`,`village_name` `name`')->where($condition_house)->order('`village_id` ASC')->select();
        if (!empty($area_list)) {
            $return['error'] = 0;
            $return['list'] = $area_list;
        } else {
            $return['error'] = 1;
            $return['info'] = '［ ' . $_POST['name'] . ' ］社区/村下没有对应关联小区！清先关联或者添加对应小区';
        }
        exit(json_encode($return));
    }

    public function ajax_all_house_village(){
        $database_house_village = D('House_village');
        $condition_house['village_id'] = array('gt',0);

        $condition_house['status'] = 1;
        $list = $database_house_village->field('village_id,village_name,village_address')->where($condition_house)->order('`village_id` desc')->select();
        if($list){
            exit(json_encode(array('status'=>1,'cat_list'=>$list),JSON_UNESCAPED_UNICODE));
        }else{
            exit(json_encode(array('status'=>0,'cat_list'=>[]),JSON_UNESCAPED_UNICODE));
        }
    }
    public function ajax_get_single(){
        $village_id = isset($_POST['village_id']) ? intval($_POST['village_id']):0;
        $database_house_village_single = D('House_village_single');
        $where['status'] = 1;
        $where['village_id'] = $village_id;

        $cat_list = $database_house_village_single->field('id,single_name')->where($where)->select();
        if($cat_list){
            exit(json_encode(array('status'=>1,'cat_list'=>$cat_list),JSON_UNESCAPED_UNICODE));
        }else{
            exit(json_encode(array('status'=>0,'cat_list'=>[]),JSON_UNESCAPED_UNICODE));
        }
    }

    public function ajax_get_floor(){
        $single_id = trim($_POST['cat_id']);
        $village_id = isset($_POST['village_id']) ? intval($_POST['village_id']):0;
        if(!$single_id || $village_id<1){
            exit(json_encode(array('status'=>0,'cat_list'=>[])));
        }

        $database_house_village_floor= D('House_village_floor');
        $where['single_id'] = $single_id;
        if($_POST['type'] != 1){
            $where['status'] = ['in', [1,3]];
        } else {
            $where['status'] = 1;
        }
        $where['village_id'] =$village_id;

        $cat_list = $database_house_village_floor->field('floor_id,floor_name')->where($where)->order('sort DESC,floor_id asc')->select();
        if($cat_list){
            exit(json_encode(array('status'=>1,'cat_list'=>$cat_list),JSON_UNESCAPED_UNICODE));
        }else{
            exit(json_encode(array('status'=>0,'cat_list'=>[]),JSON_UNESCAPED_UNICODE));
        }
    }
    public function ajax_circle()
    {
        $database_area = D('Area');
        $condition_area['area_pid'] = intval($_POST['id']);
        if ($_POST['id'] == 0) {
            $return['error'] = 0;
            $return['list'] = array();
            exit(json_encode($return));
        }
        $condition_area['is_open'] = 1;
        $condition_area['area_type'] = 4;
        $circle_list = $database_area->field('`area_id` `id`,`area_name` `name`,`first_pinyin`')->where($condition_area)->order('`area_sort` DESC,`first_pinyin` ASC')->select();
        if (!empty($circle_list)) {
            $tmp_list = array();
            foreach ($circle_list as $key => $value) {
                if (empty($tmp_list[$value['first_pinyin']])) {
                    $circle_list[$key]['name'] = $value['first_pinyin'] . '. ' . $value['name'];
                    $tmp_list[$value['first_pinyin']] = true;
                } else {
                    $circle_list[$key]['name'] = '&nbsp;&nbsp;&nbsp;&nbsp;' . $value['name'];
                }
            }
            $return['error'] = 0;
            $return['list'] = $circle_list;
        } else {
            $return['error'] = 0;
            $return['list'] = [];
            //$return['error'] = 1;
            //$return['info'] = '［ ' . $_POST['name'] . ' ］ 区域下没有已开启的商圈！请先开启商圈或删除此区域';
        }
        exit(json_encode($return));
    }

    public function ajax_market()
    {
        $database_area = D('Area_market');
        $condition_area['area_id'] = intval($_POST['id']);
        $condition_area['is_open'] = 1;
        $circle_list = $database_area->field('`market_id` `id`,`market_name` `name`')->where($condition_area)->order('`market_sort` DESC')->select();
        if (!empty($circle_list)) {
            //$tmp_list = array();
//			foreach($circle_list as $key=>$value){
//				if(empty($tmp_list[$value['first_pinyin']])){
//					$circle_list[$key]['name'] = $value['first_pinyin'].'. '.$value['name'];
//					$tmp_list[$value['first_pinyin']] = true;
//				}else{
//					$circle_list[$key]['name'] = '&nbsp;&nbsp;&nbsp;&nbsp;'.$value['name'];
//				}
//			}
            $return['error'] = 0;
            $return['list'] = $circle_list;
        } else {
            $return['error'] = 1;
            $return['info'] = '［ <b>' . $_POST['name'] . '</b> ］ 商圈下没有已开启的商场！请先开启商场或删除此区域';
        }
        exit(json_encode($return));
    }

    public function admin()
    {
        $area_id = isset($_GET['area_id']) ? intval($_GET['area_id']) : 0;
        $area = D('Area')->field(true)->where(array('area_id' => $area_id, 'is_open' => 1))->find();
        if (empty($area)) {
            $this->error('不存在的区域或该区域没有被开通,请查证后重新操作~');
        }
        if ($area['area_type'] == 2) {
            $this->assign('title', '城市');
        } elseif ($area['area_type'] == 3) {
            $this->assign('title', '区域');
        }
        $admin = D('Admin')->field(true)->where(array('area_id' => $area_id))->select();

        $this->assign('admin', $admin);
        $this->display();
    }

    public function addadmin()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $admin = D('Admin')->field(true)->where(array('id' => $id))->find();
        $this->assign('admin', $admin);
        $this->assign('bg_color', '#F3F3F3');
        $this->display();
    }

    public function saveAdmin()
    {
        if (IS_POST) {
            $database_area = D('Admin');
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $account = htmlspecialchars($_POST['account']);
            if ($database_area->where("`id`<>'{$id}' AND `account`='{$account}'")->find()) {
                $this->error('此账号已存在，请更换。');
            }
            unset($_POST['id']);

            $area_id = isset($_POST['area_id']) ? intval($_POST['area_id']) : 0;
            $area = D('Area')->field(true)->where(array('area_id' => $area_id, 'is_open' => 1))->find();
            if (empty($area)) {
                $this->error('不存在的区域或该区域没有被开通,请查证后重新操作~');
            }
            if ($area['area_type'] == 2) {
                $_POST['level'] = 3;
            } elseif ($area['area_type'] == 3) {
                $_POST['level'] = 1;
            }
            if ($id) {
                if ($_POST['pwd']) {
                    $_POST['pwd'] = md5($_POST['pwd']);
                } else {
                    unset($_POST['pwd']);
                }
                $database_area->where(array('id' => $id))->data($_POST)->save();
                $this->success('修改成功！');
            } else {
                if (empty($_POST['pwd'])) {
                    $this->error('密码不能为空~');
                }
                $_POST['pwd'] = md5($_POST['pwd']);
                $_POST['menus'] = 9999;//区域管理员默认能使用概况
                if ($database_area->data($_POST)->add()) {
                    $this->success('添加成功！');
                } else {
                    $this->error('添加失败！请重试~');
                }
            }
        } else {
            $this->error('非法提交,请重新提交~');
        }

    }

    public function deliver()
    {
        $area_id = isset($_GET['area_id']) ? intval($_GET['area_id']) : 0;
        $now_area = D('Area')->field(true)->where(array('area_id' => $area_id))->find();
        if (empty($now_area)) {
            $this->frame_error_tips('数据库中没有查询到该信息！');
        }

        $deliverSet = D('Deliver_set')->field(true)->where(array('area_id' => $area_id))->find();
        if (empty($deliverSet)) {
            $deliverSet = array('delivertime_start' => '08:00', 'delivertime_stop' => '20:00', 'delivertime_start2' => '20:00', 'delivertime_stop2' => '23:30');
        } else {
            $deliverSet['delivertime_start'] = substr($deliverSet['delivertime_start'], 0, -3);
            $deliverSet['delivertime_stop'] = substr($deliverSet['delivertime_stop'], 0, -3);
            $deliverSet['delivertime_start2'] = substr($deliverSet['delivertime_start2'], 0, -3);
            $deliverSet['delivertime_stop2'] = substr($deliverSet['delivertime_stop2'], 0, -3);
        }
        $this->assign('deliverSet', $deliverSet);
        $this->assign('area_id', $area_id);
        $this->assign('bg_color', '#F3F3F3');
        $this->display();
    }

    public function deliverAmend()
    {
        if (IS_POST) {
            $area_id = isset($_POST['area_id']) ? intval($_POST['area_id']) : 0;
            $where = array('area_id' => $area_id);
            $now_area = D('Area')->field(true)->where($where)->find();
            if (empty($now_area)) {
                $this->error('提交数据错误！');
            }
            $deliverSetDB = D('Deliver_set');

            if ($deliver = $deliverSetDB->field(true)->where($where)->find()) {
                unset($_POST['area_id']);
                if ($deliverSetDB->where($where)->save($_POST)) {
                    D('Area')->where($where)->save(array('is_deliver' => intval($_POST['status'])));
                    $this->success('修改成功！');
                } else {
                    $this->error('修改失败！请重试~');
                }
            } else {
                if ($deliverSetDB->add($_POST)) {
                    D('Area')->where($where)->save(array('is_deliver' => intval($_POST['status'])));
                    $this->success('设置成功！');
                } else {
                    $this->error('设置失败！请重试~');
                }
            }
        } else {
            $this->error('非法提交,请重新提交~');
        }

    }

    // 城市区域 跑腿配置获取信息
    public function deliver_service()
    {
        $area_id = isset($_GET['area_id']) ? intval($_GET['area_id']) : 0;
        $now_area = M('Area')->field(true)->where(array('area_id' => $area_id))->find();
        if (empty($now_area)) {
            $this->frame_error_tips('数据库中没有查询到该信息！');
        }

        $deliver_service_set = M('Deliver_service_set')->field(true)->where(array('area_id' => $area_id))->find();
        if (empty($deliver_service_set)) {
            $deliver_service_set = array(
                'buy_time1_start' => '08:00',
                'buy_time1_end' => '20:00',
                'buy_time2_start' => '20:00',
                'buy_time2_end' => '23:30',
                'give_time1_start' => '08:00',
                'give_time1_end' => '20:00',
                'give_time2_start' => '20:00',
                'give_time2_end' => '23:30',
            );
        } else {
            $buy_time1 = explode('-', $deliver_service_set['buy_deliver_time1']);
            $buy_time2 = explode('-', $deliver_service_set['buy_deliver_time2']);
            $give_time1 = explode('-', $deliver_service_set['give_deliver_time1']);
            $give_time2 = explode('-', $deliver_service_set['give_deliver_time2']);
            $deliver_service_set['buy_time1_start'] = $buy_time1[0];
            $deliver_service_set['buy_time1_end'] = $buy_time1[1];
            $deliver_service_set['buy_time2_start'] = $buy_time2[0];
            $deliver_service_set['buy_time2_end'] = $buy_time2[1];
            $deliver_service_set['give_time1_start'] = $give_time1[0];
            $deliver_service_set['give_time1_end'] = $give_time1[1];
            $deliver_service_set['give_time2_start'] = $give_time2[0];
            $deliver_service_set['give_time2_end'] = $give_time2[1];

        }
        $this->assign('deliverSet', $deliver_service_set);
        $this->assign('area_id', $area_id);
        $this->assign('bg_color', '#F3F3F3');
        $this->display();
    }

    // 城市区域 跑腿配置添加/编辑信息
    public function deliver_service_amend()
    {
        if (IS_POST) {
            $area_id = isset($_POST['area_id']) ? intval($_POST['area_id']) : 0;
            $where = array('area_id' => $area_id);
            $now_area = M('Area')->field(true)->where($where)->find();
            if (empty($now_area)) {
                $this->error('提交数据错误！');
            }
            $deliverServiceSetDB = M('Deliver_service_set');

            if (intval($_POST['buy_status']) == 1 && intval($_POST['give_status']) == 1) {
                $is_deliver_server = 3;
            } elseif (intval($_POST['buy_status']) == 1 && intval($_POST['give_status']) == 0) {
                $is_deliver_server = 1;
            } elseif (intval($_POST['buy_status']) == 0 && intval($_POST['give_status']) == 1) {
                $is_deliver_server = 2;
            } else {
                $is_deliver_server = 0;
            }

            // 处理时间信息
            $_POST['buy_deliver_time1'] = $_POST['buy_time1_start'] . '-' . $_POST['buy_time1_end'];
            $_POST['buy_deliver_time2'] = $_POST['buy_time2_start'] . '-' . $_POST['buy_time2_end'];
            $_POST['give_deliver_time1'] = $_POST['give_time1_start'] . '-' . $_POST['give_time1_end'];
            $_POST['give_deliver_time2'] = $_POST['give_time2_start'] . '-' . $_POST['give_time2_end'];

            if ($deliver = $deliverServiceSetDB->field(true)->where($where)->find()) {
                unset($_POST['area_id']);
                $_POST['set_time'] = time();
                if ($deliverServiceSetDB->where($where)->save($_POST)) {
                    D('Area')->where($where)->save(array('is_deliver_server' => $is_deliver_server));
                    $this->success('修改成功！');
                } else {
                    $this->error('修改失败！请重试~');
                }
            } else {
                $_POST['add_time'] = time();
                if ($deliverServiceSetDB->add($_POST)) {
                    D('Area')->where($where)->save(array('is_deliver_server' => $is_deliver_server));
                    $this->success('设置成功！');
                } else {
                    $this->error('设置失败！请重试~');
                }
            }
        } else {
            $this->error('非法提交,请重新提交~');
        }
    }

    public function ajax_area_center()
    {
        $area_id = $_POST['area_id'];


        $now_city = M('Area')->where(array('area_id' => $area_id))->find();
        $url = 'http://api.map.baidu.com/place/v2/search?query=' . $now_city['area_name'] . '&region=' . $now_city['area_name'] . '&city_limit=true&output=json&ak=' . $this->config['baidu_map_ak'];
        import('ORG.Net.Http');
        $http = new Http();
        $result = $http->curlGet($url);
        $result = json_decode($result, true);

//		return $result['results'][0]['location'];

        echo json_encode(array('errcode' => 0, 'lng' => $result['results'][0]['location']['lng'], 'lat' => $result['results'][0]['location']['lat']));

    }
}
