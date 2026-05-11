<?php

/*
 * 小区业主
 *
 */

class UserAction extends BaseAction
{
    use customization;

    protected $village_id;
    protected $village;
    protected $huizhisq;

    public function _initialize()
    {
        $this->thirdHouseIntercept = [
            'User#user_add','User#user_import','User#edit','User#user_delete',// 业主用户 添加 导入 编辑 删除
            'User#bind_other','User#bind_edit','User#bind_delete', // 家属、租客 添加 编辑  删除
        ];
        parent::_initialize();
        $this->village_id = $this->house_session['village_id'];
        $this->village = D('House_village')->where(array('village_id' => $this->village_id))->find();
        if (empty($this->village)) {
            $this->error('该'.C('config.house_name').'不存在！');
        }
        if ($this->village['status'] == 0) {
            $this->assign('jumpUrl', U('Index/config'));
            $this->error('您需要先完善信息才能继续操作');
        }
        $this->assign('now_village',$this->village);
        C('customization', D('Config_customization')->get_config());
        // 营山域名限制
        $this->huizhisq = false;
        if ($this->hasCloudIntercom()){
            $this->huizhisq = true;
        }
        $this->assign('huizhisq',$this->huizhisq);
    }

    protected function get_department($worker = array())
    {
        if (empty($worker) || ($worker['department_id'] < 1)) {
            return array();
        }
        $departmentArr = array();
        $subinfo=array();
        $fieldStr = 'id,fid,name,type,status';
        $property_group = M('Property_group')->where(array('id' => $worker['department_id'], 'is_del' => 0))->field($fieldStr)->find();
        if (!empty($property_group)) {
            if ($property_group['fid'] > 0) {
                $worker['department_id'] = $property_group['fid'];
                $departmentTree=array();
                $subinfo =$this->get_department_tree($property_group,$departmentTree);
            }

        }
        if(!empty($subinfo)){
            $departmentArr=$subinfo;
        }else{
            $departmentArr[]=$property_group;
        }
        return $departmentArr;
    }

    protected function get_department_tree($_group=array(),&$departmentTree = array(),$is_deep=0)
    {
        $departmentTree[]=$_group;
        if (empty($_group) || $_group['fid']<1) {
            return $departmentTree;
        }
        $fieldStr = 'id,fid,name,type,status';
        $property_group_sub = M('Property_group')->where(array('id' => $_group['fid'], 'is_del' => 0))->field($fieldStr)->find();
        if (!empty($property_group_sub)) {
            if ($property_group_sub['fid'] > 0) {
                if($is_deep==1){
                    return $this->get_department_tree($property_group_sub,$departmentTree,1);
                }else{
                    $this->get_department_tree($property_group_sub,$departmentTree,1);
                }
            }else{
                $departmentTree[]=$property_group_sub;
                return $departmentTree;
            }
        }
        return $departmentTree;
    }

    //设置物业服务时间审核人员
    public function property_service_time_set()
    {
        $village_id = $this->village_id;
        $property_id=$this->village['property_id'];
        if(IS_POST){
            $order_check_open=isset($_POST['order_check_open']) ? $_POST['order_check_open']:0 ;
            $order_check_open=intval($order_check_open);
            $check_level=isset($_POST['check_level']) ? $_POST['check_level']:'';
            $xtype=isset($_POST['xtype']) ? $_POST['xtype']:'service_time_check';
            $xtype=trim($xtype);
            $xtype=htmlspecialchars($xtype,ENT_QUOTES);
            $savedata=array('village_id'=>$village_id,'property_id'=>$property_id);
            $savedata['xtype']=$xtype;
            if($xtype=='service_time_check'){
                $savedata['tname']='物业服务时间审核设置';
            }
            $set_datas=array();
            if($order_check_open==1 && empty($check_level)){
                $this->error('请至少选择设置一项审批人！');
            }
            foreach ($check_level as $vv){
                $set_datas[$vv['level_v']]=$vv;
            }
            ksort($set_datas);
            $savedata['is_open']=$order_check_open;
            $savedata['set_datas']=json_encode($set_datas,JSON_UNESCAPED_UNICODE);
            D('House_village_checkauth_set')->save_set($savedata);
            $this->success('设置成功！');
        }
    }

    //物业服务时间审核
    public function property_service_time_audit()
    {
        $village_id = $this->village_id;
        $d_house_village = D('House_village');
        $village_info = $d_house_village->where(array('village_id'=>$village_id))->find();
        $whereArr=array('village_id'=>$village_id,'property_id'=>$village_info['property_id'],'xtype'=>'service_time_check');
        $order_refund_check=D('House_village_checkauth_set')->get_one_set($whereArr);
        $service_time_check_open=0;
        $order_check_level=array();
        $check_level0_wid=0;
        if(!empty($order_refund_check)){
            $service_time_check_open=$order_refund_check['is_open'];
            if(!empty($order_refund_check['set_datas'])){
                $order_check_level=json_decode($order_refund_check['set_datas'],1);
                $check_level0_wid=$order_check_level['0']['level_wid'];
                unset($order_check_level['0']);
            }
        }
        $fieldStr='wid,village_id,phone,name,department_id,people_type';
        $workerList = M('House_worker')->where(array('property_id'=>$village_info['property_id'],'department_id'=>array('gt',0),'is_del'=>0,'village_id'=>$village_id,'status'=>1))->field($fieldStr)->order('department_id ASC,wid desc')->select();
        if (!empty($workerList)){
            foreach ($workerList as $kk=>$vv){
                $workerList[$kk]['department']=array();
                $workerList[$kk]['department_str']='';
                $department=$this->get_department($vv);
                if(!empty($department)){
                    $department_str='';
                    foreach ($department as $dvv){
                        $department_str =$dvv['name'].' > '.$department_str;
                    }
                    $workerList[$kk]['department_str']=$department_str;
                    $workerList[$kk]['department']=$department;
                }
            }
        }else{
            $workerList=array();
        }
        $whereArr=array('village_id'=>$village_id,'property_id'=>$village_info['property_id'],'xtype'=>'service_time_check','status'=>array('in',array('0','1')));
        $applyhas=M('House_village_checkauth_apply')->where($whereArr)->order('id desc')->find();
        $msg='';
        if($applyhas){
            $msg='由'.$applyhas['apply_name'].'发起的物业服务时间修改，用户ID为'.$applyhas['pigcms_id'];
            $applyhas['msg']=$msg;
        }
        $this->assign('applyhas',$applyhas);
        $this->assign('check_level0_wid',$check_level0_wid);
        $this->assign('order_check_level',$order_check_level);
        $order_check_level_num=count(empty($order_check_level)?$order_check_level:$order_check_level[1]);
        $this->assign('order_check_level_num',$order_check_level_num);
        $this->assign('service_time_check_open',$service_time_check_open);
        $this->assign('worker_list',$workerList);
        $this->assign('worker_list_json',json_encode($workerList,JSON_UNESCAPED_UNICODE));
        $this->display();
    }

    //判断登录人员有无审核物业服务时间权限
    public function is_audit()
    {
        $house = session('house');
        if(!$house['role_id']){
            $this->error('您无权限审核');
        }
        $village_id = $this->village_id;
        $property_id=$this->village['property_id'];
        $whereArr=array('village_id'=>$village_id,'property_id'=>$property_id,'xtype'=>'service_time_check');
        $service_time_check=D('House_village_checkauth_set')->get_one_set($whereArr);
        $wid = 0;
        if(empty($service_time_check)){
            $this->error('您无权限审核',U('User/index'));
        }else{
            $admin_info = M('House_admin')->where(['id'=>$house['role_id']])->find();
            $rule_list = json_decode($service_time_check['set_datas'],true);
            if(empty($rule_list)){
                $this->error('您无权限审核',U('User/index'));
            }else{
                foreach ($rule_list as $v){
                    if($v['level_wid'] == $admin_info['wid']){
                        $wid = $admin_info['wid'];
                    }
                }
                if(!$wid){
                    $this->error('您无权限审核',U('User/index'));
                }
            }
        }
        $this->success('权限正常');
    }

    //审核物业服务时间
    public function audit_service()
    {
        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
        $house = session('house');
        if(!$house['role_id']){
            $this->error('您无权限审核',U('User/index'));
        }
        $village_id = $this->village_id;
        $property_id=$this->village['property_id'];
        $whereArr=array('village_id'=>$village_id,'property_id'=>$property_id,'xtype'=>'service_time_check');
        $service_time_check=D('House_village_checkauth_set')->get_one_set($whereArr);
        $wid = 0;
        $level = 0;
        $wid_count = 0;
        if(empty($service_time_check)){
            $this->error('您无权限审核',U('User/index'));
        }else{
            $admin_info = M('House_admin')->where(['id'=>$house['role_id']])->find();
            $rule_list = json_decode($service_time_check['set_datas'],true);
            if(empty($rule_list)){
                $this->error('您无权限审核',U('User/index'));
            }else{
                foreach ($rule_list as $v){
                    $wid_count+=1;
                    if($v['level_wid'] == $admin_info['wid']){
                        $wid = $admin_info['wid'];
                        $level = $v['level_v'];
                    }
                }
                if(!$wid){
                    $this->error('您无权限审核',U('User/index'));
                }
            }
        }
        if(IS_POST && IS_AJAX){
            if(!$_POST['pigcms_id']){
                $this->error('参数异常');
            }
            $bind_info = M('House_village_user_bind')->where(['pigcms_id'=>$_POST['pigcms_id']])->find();
            if($_POST['status'] == 3 && empty($_POST['bak'])){
                $this->error('请填写不通过原因');
            }else{
                $where['pigcms_id'] = $_POST['pigcms_id'];
                $where['status'] = array('in','0,1');
                $reply_info = M('House_village_checkauth_apply')->where($where)->find();
                if(!$reply_info){
                    $this->error('该业主没有正在审核的物业服务时间');
                }else{
                    $con['apply_id'] = $reply_info['id'];
                    $con['wid'] = $wid;
                    $con['status'] = array('in','1,2');
                    if(M('House_village_checkauth_detail')->where($con)->find()){
                        $this->error('您已经审核过了，无法再次审核');
                    }
                    M('House_village_checkauth_detail')->where(['apply_id'=>$reply_info['id'],'wid'=>$wid,'status'=>0])->delete();
                    $reply_detail_info  = M('House_village_checkauth_detail')->where(['apply_id'=>$reply_info['id']])->find();
                    if($wid_count == 1){
                        $res = M('House_village_checkauth_apply')->where(['id'=>$reply_info['id']])->data([
                            'status'=>$_POST['status'],
                            'apply_time'=>time()
                        ])->save();
                        if($_POST['status'] == 2){
                            //todo 更新house_new_order_log物业服务时间
                            $this->update_order_log($_POST['pigcms_id'],$reply_info['service_start_time'],$reply_info['service_end_time']);
                        }
                    }else{
                        if($rule_list[0]['level_wid'] != $wid){
                            if(!M('House_village_checkauth_detail')->where(['apply_id'=>$reply_info['id'],'wid'=>$rule_list[0]['level_wid']])->find()){
                                $this->error('需要上级先审核');
                            }
                        }
                        if(empty($reply_detail_info)){
                            if($_POST['status'] == 3){
                                $res = M('House_village_checkauth_apply')->where(['id'=>$reply_info['id']])->data([
                                    'status'=>3,
                                    'apply_time'=>time()
                                ])->save();
                            }else{
                                $res = M('House_village_checkauth_apply')->where(['id'=>$reply_info['id']])->data([
                                    'status'=>1,
                                    'apply_time'=>time()
                                ])->save();
                            }
                        }else{
                            if($reply_detail_info['status'] == 2){
                                $res = M('House_village_checkauth_apply')->where(['id'=>$reply_info['id']])->data([
                                    'status'=>3,
                                    'apply_time'=>time()
                                ])->save();
                            }else{
                                $res = M('House_village_checkauth_apply')->where(['id'=>$reply_info['id']])->data([
                                    'status'=>$_POST['status'],
                                    'apply_time'=>time()
                                ])->save();
                                if($_POST['status'] == 2){
                                    //todo 更新house_new_order_log物业服务时间
                                    $this->update_order_log($_POST['pigcms_id'],$reply_info['service_start_time'],$reply_info['service_end_time']);
                                }
                            }
                        }
                    }
                    if($res){
                        $worker_info = M('House_worker')->where(['wid'=>$wid])->find();
                        $res = M('House_village_checkauth_detail')->data([
                            'apply_id'=>$reply_info['id'],
                            'property_id'=>$property_id,
                            'village_id'=>$village_id,
                            'wid'=>$wid,
                            'level'=>$level,
                            'status'=>$_POST['status']-1,
                            'bak'=>$_POST['bak'],
                            'apply_time'=>time(),
                            'add_time'=>time(),
                            'extra_data'=>json_encode([
                                'wid'=>$wid,
                                'village_id'=>$worker_info['village_id'],
                                'phone'=>$worker_info['phone'],
                                'name'=>$worker_info['name'],
                                'openid'=>$worker_info['openid'],
                                'status'=>$worker_info['status'],
                                'type'=>$worker_info['type'],
                                'property_id'=>$worker_info['property_id'],
                                'people_type'=>$worker_info['people_type'],
                                'department_id'=>$worker_info['department_id'],
                                'account'=>$worker_info['account']
                            ])
                        ])->add();
                        $address = D('House_village')->get_single_floor_room($bind_info['single_id'],$bind_info['floor_id'],$bind_info['layer_id'],$bind_info['vacancy_id'],$bind_info['village_id']);
                        if(!empty($worker_info['openid']) && $_POST['status']-1 == 0){
                            //审核抄送
                            $href = C('config.site_url').'/packapp/community/pages/Community/reviewRefund/myApproval';
                            $model->sendTempMsg('OPENTM417740354',//todo 类目模板OPENTM417740354
                                array(
                                    'href' => $href,
                                    'wecha_id' => $worker_info['openid'],
                                    'first' => '审核提醒',
                                    'keyword1' => '修改物业服务时间申请',
                                    'keyword2' =>$bind_info['name'],
                                    'keyword3' => date('Y-m-d H:i:s',time()),
                                    'remark' => '您有一个新的申请消息需要审核！',
                                    'new_info' => [//新版本发送需要的信息
                                        'tempKey'=>'43258',//新模板号
                                        'thing2'=>'修改物业服务时间申请',//流程名称
                                        'thing9'=>$bind_info['name'],//发起人
                                        'time10'=>date('Y-m-d H:i:s',time()),//发起时间
                                        'thing7'=>$address,//房源名称
                                    ],
                                ),0,$this->village['property_id'],1
                            );
                        }
                        if($res){
                            if($wid_count == 2 && $_POST['status'] == 2){
                                if(empty($reply_detail_info)){
                                    $worker_info = M('House_worker')->where(['wid'=>$rule_list[1]['level_wid']])->find();
                                    M('House_village_checkauth_detail')->data([
                                        'apply_id'=>$reply_info['id'],
                                        'property_id'=>$property_id,
                                        'village_id'=>$village_id,
                                        'wid'=>$rule_list[1]['level_wid'],
                                        'level'=>$rule_list[1]['level_v'],
                                        'status'=>0,
                                        'bak'=>'',
                                        'apply_time'=>time(),
                                        'add_time'=>time(),
                                        'extra_data'=>json_encode([
                                            'wid'=>$rule_list[1]['level_wid'],
                                            'village_id'=>$worker_info['village_id'],
                                            'phone'=>$worker_info['phone'],
                                            'name'=>$worker_info['name'],
                                            'openid'=>$worker_info['openid'],
                                            'status'=>$worker_info['status'],
                                            'type'=>$worker_info['type'],
                                            'property_id'=>$worker_info['property_id'],
                                            'people_type'=>$worker_info['people_type'],
                                            'department_id'=>$worker_info['department_id'],
                                            'account'=>$worker_info['account']
                                        ])
                                    ])->add();
                                    if(!empty($worker_info['openid'])){
                                        //审核抄送
                                        $href = C('config.site_url').'/packapp/community/pages/Community/reviewRefund/myApproval';
                                        $model->sendTempMsg('OPENTM417740354',//todo 类目模板OPENTM417740354
                                            array(
                                                'href' => $href,
                                                'wecha_id' => $worker_info['openid'],
                                                'first' => '审核提醒',
                                                'keyword1' => '修改物业服务时间申请',
                                                'keyword2' =>$bind_info['name'],
                                                'keyword3' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']),
                                                'remark' => '您有一个新的申请消息需要审核！',
                                                'new_info' => [//新版本发送需要的信息
                                                    'tempKey'=>'43258',//新模板号
                                                    'thing2'=>'修改物业服务时间申请',//流程名称
                                                    'thing9'=>$bind_info['name'],//发起人
                                                    'time10'=>date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']),//发起时间
                                                    'thing7'=>$address,//房源名称
                                                ],
                                            ),0,$this->village['property_id'],1
                                        );
                                    }
                                }
                            }
                            $this->success('申请成功');
                        }else{
                            $this->error('服务异常');
                        }
                    }
                }
            }
        }else{
            $this->assign('pigcms_id',$_GET['pigcms_id']);
            $this->display();
        }
    }

    //修改物业服务时间
    public function update_service_time()
    {
        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
        $house = session('house');
        if(IS_POST && IS_AJAX){
            if(empty($_POST['service_start_time']) || empty($_POST['service_end_time']) || empty($_POST['reply_reason']) || !$_POST['pigcms_id']){
                $this->error('参数异常');
            }else{
                $service_start_time = strtotime($_POST['service_start_time']);
                $service_end_time = strtotime($_POST['service_end_time']);
                if($service_end_time>0){
                    $service_end_time +=86399;
                }
                if($service_start_time > $service_end_time){
                    $this->error('物业服务开始时间不能大于物业服务结束时间');
                }else{
                    $village_id = $this->village_id;
                    $property_id=$this->village['property_id'];
                    $where['pigcms_id'] = $_POST['pigcms_id'];
                    $where['status'] = array('in','0,1');
                    $bind_info = M('House_village_user_bind')->where(['pigcms_id'=>$_POST['pigcms_id']])->find();
                    $time_info=D('House_village')->getContractTime($bind_info['vacancy_id']);
                   //  $time_info=D('House_village_user_bind')->getUserEndTime($_POST['pigcms_id']);
                    fdump_api([$time_info,$service_start_time,$service_end_time],'getUserEndTime_0623',1);
                    if (!empty($time_info)){
                        if ($service_start_time<$time_info['contract_time_start']&&$time_info['contract_time_start']>0){
                            $this->error('物业服务开始时间不能小于合同开始时间');
                        }
                        if ($service_end_time>$time_info['contract_time_end']&&$time_info['contract_time_end']>0){
                            $this->error('物业服务结束时间不能大于合同结束时间');
                        }
                    }
                    if(M('House_village_checkauth_apply')->where($where)->find()){
                        $this->error('您有申请正在审核，无法再次申请');
                    }else{
                        $whereArr=array('village_id'=>$village_id,'property_id'=>$property_id,'xtype'=>'service_time_check');
                        $order_refund_check=D('House_village_checkauth_set')->get_one_set($whereArr);
                        if ($house['role_id']){
                            $apply_name = $house['user_name'];
                            $apply_login_role = 5;
                        }elseif ($house['is_system'] == 1){
                            $apply_name = $house['user_name'];
                            $apply_login_role = 6;
                        }elseif ($house['is_system'] == 2){
                            $apply_name = $house['user_name'];
                            $apply_login_role = 7;
                        }else{
                            $apply_name = $house['user_name'];
                            $apply_login_role = 12;
                        }
                        $is_auto_level = 0;//是否自动过审
                        $level = 0; //自动过审人员等级
                        if(empty($order_refund_check) || !$order_refund_check['is_open']){
                            $status = 2;
                            //todo 更新house_new_order_log物业服务时间
                            $this->update_order_log($_POST['pigcms_id'],$service_start_time,$service_end_time);
                        }else{
                            if($apply_login_role == 5){
                                $rule_list = json_decode($order_refund_check['set_datas'],true);
                                $admin_info = M('House_admin')->where(['id'=>$house['role_id']])->find();
                                foreach ($rule_list as $k=>$v){
                                    if($v['level_wid'] == $admin_info['wid']){
                                        $is_auto_level = 1;
                                        $level = $k+1;
                                    }
                                }
                            }
                            $status = 0;
                        }
                        $res = M('House_village_checkauth_apply')->data([
                            'village_id'=>$village_id,
                            'property_id'=>$property_id,
                            'xtype'=>'service_time_check',
                            'pigcms_id'=>$_POST['pigcms_id'],
                            'service_start_time'=>$service_start_time,
                            'service_end_time'=>$service_end_time,
                            'apply_reason'=>$_POST['reply_reason'],
                            'add_time'=>time(),
                            'status'=>$status,
                            'apply_login_role'=>$apply_login_role,
                            'apply_name'=>$apply_name
                        ])->add();
                        if($res){
                            if($status == 0 && !empty($order_refund_check) && $order_refund_check['is_open']){
                                $rule_list = json_decode($order_refund_check['set_datas'],true);
                                $address = D('House_village')->get_single_floor_room($bind_info['single_id'],$bind_info['floor_id'],$bind_info['layer_id'],$bind_info['vacancy_id'],$bind_info['village_id']);
                                if($is_auto_level){
                                    foreach ($rule_list as $key=>$value){
                                        $worker_info = M('House_worker')->where(['wid'=>$value['level_wid']])->find();
                                        if($level > $key){
                                            $bak = '自动过审';
                                            $status = 1;
                                        }else{
                                            $bak = '';
                                            $status = 0;
                                        }
                                        M('House_village_checkauth_detail')->data([
                                            'apply_id'=>$res,
                                            'property_id'=>$property_id,
                                            'village_id'=>$village_id,
                                            'wid'=>$value['level_wid'],
                                            'level'=>$value['level_v'],
                                            'status'=>$status,
                                            'bak'=>$bak,
                                            'apply_time'=>time(),
                                            'add_time'=>time(),
                                            'extra_data'=>json_encode([
                                                'wid'=>$value['level_wid'],
                                                'village_id'=>$worker_info['village_id'],
                                                'phone'=>$worker_info['phone'],
                                                'name'=>$worker_info['name'],
                                                'openid'=>$worker_info['openid'],
                                                'status'=>$worker_info['status'],
                                                'type'=>$worker_info['type'],
                                                'property_id'=>$worker_info['property_id'],
                                                'people_type'=>$worker_info['people_type'],
                                                'department_id'=>$worker_info['department_id'],
                                                'account'=>$worker_info['account']
                                            ],JSON_UNESCAPED_UNICODE)
                                        ])->add();
                                        if($status<1){
                                            $dataJpush=array('village_id'=>$village_id,'wid'=>$value['level_wid']);
                                            D('House_app_jpush_log')->handleJpushInfoSend($dataJpush,'checkauth_apply_tips'); 
                                        }
                                        if(!$status && !empty($worker_info['openid'])){
                                            //审核抄送
                                            $href = C('config.site_url').'/packapp/community/pages/Community/reviewRefund/myApproval';
                                            $model->sendTempMsg('OPENTM417740354',//todo 类目模板OPENTM417740354
                                                array(
                                                    'href' => $href,
                                                    'wecha_id' => $worker_info['openid'],
                                                    'first' => '审核提醒',
                                                    'keyword1' => '修改物业服务时间申请',
                                                    'keyword2' =>$bind_info['name'],
                                                    'keyword3' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']),
                                                    'remark' => '您有一个新的申请消息需要审核！',
                                                    'new_info' => [//新版本发送需要的信息
                                                        'tempKey'=>'43258',//新模板号
                                                        'thing2'=>'修改物业服务时间申请',//流程名称
                                                        'thing9'=>$bind_info['name'],//发起人
                                                        'time10'=>date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']),//发起时间
                                                        'thing7'=>$address,//房源名称
                                                    ],
                                                ),0,$this->village['property_id'],1
                                            );
                                        }
                                    }
                                    if(count($rule_list) == 2 && $level == 1){
                                        M('House_village_checkauth_apply')->where(['id'=>$res])->data(['status'=>1])->save();
                                    }else{
                                        M('House_village_checkauth_apply')->where(['id'=>$res])->data(['status'=>2])->save();
                                        //todo 更新house_new_order_log物业服务时间
                                        $this->update_order_log($_POST['pigcms_id'],$service_start_time,$service_end_time);
                                    }
                                }else{
                                    $worker_info = M('House_worker')->where(['wid'=>$rule_list[0]['level_wid']])->find();
                                    M('House_village_checkauth_detail')->data([
                                        'apply_id'=>$res,
                                        'property_id'=>$property_id,
                                        'village_id'=>$village_id,
                                        'wid'=>$rule_list[0]['level_wid'],
                                        'level'=>$rule_list[0]['level_v'],
                                        'status'=>0,
                                        'bak'=>'',
                                        'apply_time'=>time(),
                                        'add_time'=>time(),
                                        'extra_data'=>json_encode([
                                            'wid'=>$rule_list[0]['level_wid'],
                                            'village_id'=>$worker_info['village_id'],
                                            'phone'=>$worker_info['phone'],
                                            'name'=>$worker_info['name'],
                                            'openid'=>$worker_info['openid'],
                                            'status'=>$worker_info['status'],
                                            'type'=>$worker_info['type'],
                                            'property_id'=>$worker_info['property_id'],
                                            'people_type'=>$worker_info['people_type'],
                                            'department_id'=>$worker_info['department_id'],
                                            'account'=>$worker_info['account']
                                        ],JSON_UNESCAPED_UNICODE)
                                    ])->add();
                                    $dataJpush=array('village_id'=>$village_id,'wid'=>$rule_list[0]['level_wid']);
                                    D('House_app_jpush_log')->handleJpushInfoSend($dataJpush,'checkauth_apply_tips');
                                    if(!empty($worker_info['openid'])){
                                        //审核抄送
                                        $href = C('config.site_url').'/packapp/community/pages/Community/reviewRefund/myApproval';
                                        $model->sendTempMsg('OPENTM417740354',//todo 类目模板OPENTM417740354
                                            array(
                                                'href' => $href,
                                                'wecha_id' => $worker_info['openid'],
                                                'first' => '审核提醒',
                                                'keyword1' => '修改物业服务时间申请',
                                                'keyword2' =>$bind_info['name'],
                                                'keyword3' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']),
                                                'remark' => '您有一个新的申请消息需要审核！',
                                                'new_info' => [//新版本发送需要的信息
                                                    'tempKey'=>'43258',//新模板号
                                                    'thing2'=>'修改物业服务时间申请',//流程名称
                                                    'thing9'=>$bind_info['name'],//发起人
                                                    'time10'=>date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']),//发起时间
                                                    'thing7'=>$address,//房源名称
                                                ],
                                            ),0,$this->village['property_id'],1
                                        );
                                    }
                                }
                            }
                            $this->success('申请成功');
                        }else{
                            $this->error('服务异常');
                        }
                    }
                }
            }
        }else{
            $this->assign('pigcms_id',$_GET['pigcms_id']);
            $this->display();
        }
    }

    //更新house_new_order_log表物业服务时间
    public function update_order_log($pigcms_id=0,$service_start_time=0,$service_end_time=0)
    {
        if(!$pigcms_id || !$service_end_time || !$service_start_time){
            return false;
        }
        $village_id = $this->village_id;
        $property_id=$this->village['property_id'];
        $vacancy_id = M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->getField('vacancy_id');
        $res = M('House_new_order_log')->data([
            'order_type'=>'property',
            'order_name'=>'物业费',
            'room_id'=>$vacancy_id,
            'village_id'=>$village_id,
            'property_id'=>$property_id,
            'desc'=>'业主列表修改物业服务时间',
            'service_start_time'=>$service_start_time,
            'service_end_time'=>$service_end_time,
            'add_time'=>time()
        ])->add();
        $order_log= M('House_new_order_log')->where(['room_id'=>$vacancy_id,'order_type'=>'property'])->order('id ASC')->find();
        if (!empty($order_log)){
            M('House_new_order_log')->where(['room_id'=>$vacancy_id,'order_type'=>'property','id'=>$order_log['id']])->data(['service_start_time'=>$service_start_time])->save();
        }
        if($res){
            //如果更改了服务时间
            D('House_face_img')->house_user_face_device_info(0,intval($pigcms_id));
            return true;
        }else{
            return false;
        }
    }

    // 所有业主列表
    public function index()
    {
        //业主-添加 权限
        if (0&&!in_array(90, $this->house_session['menus']) && !in_array(91, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $find_type = $_GET['find_type'];
        $find_value = $_GET['find_value'];
        $is_platform = $_GET['is_platform'] + 0;
        $village_id = $this->village_id;
        $whereRoom = [
            'village_id' => $village_id,
            'is_del' => 0,
            'status' => ['neq', 3]
        ];
        if ($find_value) {
            if ($find_type == 1) {
                $room_arr = explode('-',$find_value);
                if(false && strpos($find_value,'-') !== false && count($room_arr)>=3){
                    // 延华定制
                    $floor_name = ''; // 单元名称
                    $layer_name = ''; // 楼层名称
                    $single_name = $room_arr[0]; // 楼栋名称
                    $room_name = $room_arr[count($room_arr)-1]; // 房间名称
                    if (count($room_arr) == 3){
                        $floor_name = $room_arr[1];
                    }elseif (count($room_arr) == 4){
                        $floor_name = $room_arr[1];
                        $layer_name = $room_arr[2];
                    }
                    // 查询楼栋ID
                    $single_id = D('House_village_single')->where(array('single_name' => $single_name,'village_id' => $village_id))->getField('id',true);
                    if($single_id){
                        $where['single_id'] = array('in',$single_id);
                        $whereRoom['single_id'] = array('in',$single_id);
                    }
                    // 查询单元ID
                    if(!empty($floor_name)){
                        $floor_id = D('House_village_floor')->where(array('single_id' => array('in',$single_id),'floor_name' => $floor_name,'village_id' => $village_id))->getField('floor_id',true);
                        $where['floor_id'] = array('in',$floor_id);
                        $whereRoom['floor_id'] = array('in',$floor_id);
                    }
                    // 查询楼层ID
                    if(!empty($layer_name)){
                        $layer_id = D('House_village_layer')->where(array('single_id' => array('in',$single_id),'layer_name' => $layer_name,'village_id' => $village_id))->getField('id',true);
                        $where['layer_id'] = array('in',$layer_id);
                        $whereRoom['layer_id'] = array('in',$layer_id);
                    }
                    // 查询房间ID
                    if(!empty($room_name)){
                        $room_id = D('House_village_user_vacancy')->where(array('single_id' => array('in',$single_id),'room' => $room_name,'village_id' => $village_id,'is_del'=>0))->getField('pigcms_id',true);
                        $where['vacancy_id'] = array('in',$room_id);
                        $whereRoom['pigcms_id'] = array('in',$room_id);
                    }
                    $where['_string'] = "ub.usernum LIKE '%{$find_value}%' OR ub.bind_number LIKE '%{$find_value}%'";
                }else{
                    $where['_string'] = "ub.usernum LIKE '%{$find_value}%' OR ub.bind_number LIKE '%{$find_value}%'";
                }

            } else if ($find_type == 2) {
                $where['name'] = array('like', '%' .str_replace(' ','',$find_value) . '%');
            } else if ($find_type == 3) {
                $where['phone'] = array('like', '%' . $find_value . '%');
            } else if ($find_type == 4) {
                $where['address'] = array('like', '%' . $find_value . '%');
            } else if ($find_type == 5) {
                $where['card_no'] = array('like', '%' . $find_value . '%');
            } else if ($find_type == 6) {
                $where['ic_card'] = array('like', '%' . $find_value . '%');
            }
        }
        if (isset($_GET['is_real']) && $_GET['is_real']>0) {
            if (1==$_GET['is_real']) {
                if ($where['_string']) {
                    $where['_string'] .= " AND (ub.uid not in (0,'') OR ub.phone not in (0,'') OR ub.name not in (0,''))";
                } else {
                    $where['_string'] = "(ub.uid not in (0,'') OR ub.phone not in (0,'') OR ub.name not in (0,''))";
                }
            } else {
                if ($where['_string']) {
                    $where['_string'] .= " AND (ub.uid in (0,'') AND ub.phone in (0,'') AND ub.name in (0,''))";
                } else {
                    $where['_string'] = "(ub.uid in (0,'') AND ub.phone in (0,'') AND ub.name in (0,''))";
                }
            }
        }

        if($_GET['single_id'] !=0){
            $where['single_id'] = $_GET['single_id'];
            $whereRoom['single_id'] = $_GET['single_id'];
        }
        if($_GET['floor_id'] !=0){
            $where['floor_id'] = $_GET['floor_id'];
            $whereRoom['floor_id'] = $_GET['floor_id'];
        }
        if($_GET['vacancy_id'] !=0){
            $where['vacancy_id'] = $_GET['vacancy_id'];
            $whereRoom['vacancy_id'] = $_GET['vacancy_id'];
        }
        if($_GET['layer_id'] !=0){
            $where['layer_id'] = $_GET['layer_id'];
            $whereRoom['layer_id'] = $_GET['layer_id'];
        }

        $property_endtime_start = $_GET['property_endtime_start'];
        $property_endtime_end = $_GET['property_endtime_end'];
        $this->assign('property_endtime_start', $property_endtime_start);
        $this->assign('property_endtime_end', $property_endtime_end);
        /*
        if ($property_endtime_start && $property_endtime_end) {
            $start_time = strtotime($property_endtime_start);
            $end_time = strtotime($property_endtime_end.'23:59:59');
            $where['property_endtime'] = array('between',array($start_time,$end_time));
        }else if($property_endtime_start){
            $property_endtime_start = strtotime($property_endtime_start);
            $where['property_endtime'] = array('egt',$property_endtime_start);
        }else if($property_endtime_end){
            $property_endtime_end = strtotime($property_endtime_end.'23:59:59');
            $where['property_endtime'] = array('elt',$property_endtime_end);
        }
        */
        $is_new_charge=D('House_village')->checkNewCharge($this->house_session['property_id']);
        $this->assign('new_charge_time', $is_new_charge);
        if($is_platform == 1){
            $where['uid'] = array('neq',0);
        }elseif ($is_platform == 2) {
            $where['uid'] = array('eq',0);
        }
        //数据来源
        if(isset($_GET['third_type']) && $_GET['third_type']=='house'){//社区注册
            $where['third_type'] = array('eq','');
        }elseif(isset($_GET['third_type']) && $_GET['third_type']=='ezc_chinapopin'){//平安小区接口
            $where['third_type'] = array('eq',$_GET['third_type']);
        }elseif(isset($_GET['third_type']) && $_GET['third_type']=='dopu_cloudintercom'){//朵普接口
            $where['third_type'] = array('eq',$_GET['third_type']);
        }
		
		//人脸状态
		if($_GET['face_status'] == '1'){
			$where['face_img_status'] = '1';
            $where['add_face_time'] =array('gt',100);
		}else if($_GET['face_status'] == '2'){
			$where['face_img_status'] = '2';
		}else if($_GET['face_status'] == '-1'){
            $where['face_img_status'] = 0;
        }
        /**是否绑定微信*is_bind_weixin 1是 2否****/
		$is_bind_weixin=isset($_GET['is_bind_weixin']) ? intval($_GET['is_bind_weixin']):0;
		if($is_bind_weixin>0){
            $where['is_bind_weixin'] = $is_bind_weixin;
        }
        /**是否绑定微信*source_comefrom 1公众号 2小程序3小区自动注册4其他****/
        $source_comefrom=isset($_GET['source_comefrom']) ? intval($_GET['source_comefrom']):0;
        if($source_comefrom>0){
            $where['source_comefrom'] = $source_comefrom;
        }
        $house = session('house');
        $admin_info = M('House_admin')->where(['id'=>$house['role_id'],'wid'=>array('egt',0)])->find();
        $whereArr=array('village_id'=>$village_id,'property_id'=>$this->house_session['property_id'],'xtype'=>'service_time_check');
        $service_time_check=D('House_village_checkauth_set')->get_one_set($whereArr);
        $service_time_check_open=0;
        $service_time_check_wid=0;
        $service_time_check_level=0;
        $service_time_check_pigcms_ids=array();
        if(!empty($service_time_check)){
            $service_time_check_open=$service_time_check['is_open'];
            $rule_list = json_decode($service_time_check['set_datas'],true);
            if($rule_list && $admin_info){
                foreach ($rule_list as $v){
                    if($v['level_wid'] == $admin_info['wid']){
                        $service_time_check_wid = $admin_info['wid'];
                        $service_time_check_level = $v['level_v'];
                    }
                }
            }
            if($service_time_check_wid>0){
                $where_check=array();
                $where_check['caud.wid'] = $service_time_check_wid;
                $where_check['caud.status'] =0;
                $where_check['caa.status'] = array('in','0,1');
                $where_check['caa.xtype'] = 'service_time_check';
                $where_check['caa.village_id'] = $village_id;
                $where_check['caud.village_id'] = $village_id;
                $checkauth_apply=M('House_village_checkauth_detail caud')->join('LEFT JOIN '.C('DB_PREFIX') .'house_village_checkauth_apply as caa on caud.	apply_id=caa.id')->where($where_check)->field('caa.id,caa.pigcms_id')->order('caa.id desc')->select();
                if(!empty($checkauth_apply)){
                        foreach ($checkauth_apply as $cvv){
                            $service_time_check_pigcms_ids[]=$cvv['pigcms_id'];
                        }
                    $where['orderby']='FIELD(ub.pigcms_id,'.implode(',',$service_time_check_pigcms_ids).') desc';
                }
            }
        }
        $where['status'] = 1;
        if (empty($where)) {
            $user_list = D('House_village_user_bind')->get_limit_list_page($village_id, 20);
        } else {
            $where['getChild'] = false;
            $user_list = D('House_village_user_bind')->get_limit_list_page($village_id, 20, $where);
        }

        $database_house_village_user_vacancy = M('House_village_user_vacancy');
        if (isset($user_list['roomIdArrs']) && $user_list['roomIdArrs']) {
            $whereRoom = [
                'pigcms_id' => ['in', $user_list['roomIdArrs']],
                'village_id' => $village_id,
                'is_del' => 0,
                'status' => ['neq', 3],
            ];
        }
        $d_house_village=D('House_village');
        $otherStatusRooms = $database_house_village_user_vacancy->where($whereRoom)->getField('pigcms_id,status',true);
        $village_info = $d_house_village->field('*')->where(array('village_id'=>$village_id))->find();
 
        $d_house_face_img = M('House_face_img');
        $d_house_village_user_bind = D('House_village_user_bind');
        $d_house_village_bind_position = M('House_village_bind_position');
        $d_house_village_property_paylist = M('House_village_property_paylist');
        $chinapopinOpen  = D('Config_customization')->getChinapopinOpen();
        foreach ($user_list['user_list'] as $k => $v) {
            if($chinapopinOpen && $user_list['user_list'][$k]['third_id'] && !$user_list['user_list'][$k]['third_type']){
                //兼容朵普之前未记录的老字段
                if($user_list['user_list'][$k]['third_id']){
                    $user_list['user_list'][$k]['third_type'] = M('House_village_third_user_info')->where([
                        'third_ryid'=>$user_list['user_list'][$k]['third_id'],
                        'village_id'=>$village_id
                    ])->getField('third_type');
                }
                if($user_list['user_list'][$k]['third_type']){
                    M('House_village_user_bind')->where(['pigcms_id'=>$user_list['user_list'][$k]['pigcms_id']])->save(['third_type'=>$user_list['user_list'][$k]['third_type']]);
                }
            }
            if($chinapopinOpen){
                $user_list['user_list'][$k]['third_type_color'] = D('House_village_third_user_info')->getThirdTypeColor($user_list['user_list'][$k]['third_type']);
                $user_list['user_list'][$k]['third_type'] = D('House_village_third_user_info')->getThirdType($user_list['user_list'][$k]['third_type']);
            }
            if($v['apply_time']){
                $user_list['user_list'][$k]['apply_time_txt'] = date('Y-m-d H:i:s',$v['apply_time']);
            }else{
                $user_list['user_list'][$k]['apply_time_txt'] = '—';
            }
            if($v['adopt_time']){
                $user_list['user_list'][$k]['adopt_time_txt'] = date('Y-m-d H:i:s',$v['adopt_time']);
            }else{
                $user_list['user_list'][$k]['adopt_time_txt'] = '—';
            }
            if (isset($otherStatusRooms[$v['vacancy_id']]) && $otherStatusRooms[$v['vacancy_id']]!=3 && in_array($v['type'],[0,3]) && $v['status']==1) {
                $whereArr=array('pigcms_id'=>$v['vacancy_id'],'status'=>1);
                $database_house_village_user_vacancy->where($whereArr)->data(array('status'=>3))->save();
            }
            if ($is_new_charge == 1) {
//                $userTimes =  $d_house_village_user_bind->getUserEndTime($v['pigcms_id'],false);
                $userTimes =  $d_house_village_user_bind->getPropertyTime($v['pigcms_id'],[
                    'property_starttime'=>$v['property_starttime'],
                    'property_endtime'=>$v['property_endtime']
                ]);
                if (isset($userTimes['propertyEndTime'])) {
                    $user_list['user_list'][$k]['property_endtime'] = intval($userTimes['propertyEndTime']);
                } else {
                    $user_list['user_list'][$k]['property_endtime'] = 0;
                }
                if (isset($userTimes['propertyStartTime'])) {
                    $user_list['user_list'][$k]['property_starttime'] = intval($userTimes['propertyStartTime']);
                } else {
                    $user_list['user_list'][$k]['property_endtime'] = 0;
                }
            }else{
                if (!$v['property_endtime']) {
                    $now_pay_info = $d_house_village_property_paylist->where(array('bind_id' => $v['pigcms_id']))->order('add_time desc')->field('end_time')->find();
                    $user_list['user_list'][$k]['property_endtime'] = $now_pay_info['end_time'];
                }

                if (!$v['property_starttime']) {
                    $now_pay_info = $d_house_village_property_paylist->where(array('bind_id' => $v['pigcms_id']))->order('add_time asc')->field('start_time')->find();
                    $user_list['user_list'][$k]['property_starttime'] = $now_pay_info['start_time'];
                }
            }

            if ($village_info['write_iccard'] && isset($v['door_control_str']) && $v['door_control_str']) {
                $door_control_str = $v['door_control_str'];
            } elseif($village_info['write_iccard']) {
                $door_control = D('House_village_floor')->where(array('floor_id'=>$v['floor_id']))->getField('door_control');
                if (empty($door_control)) {
                    $door_control_str = $v['door_control'];
                }elseif ($v['door_control'] == null) {
                    $door_control_str = $door_control;
                }else{
                    $door_control_str = $v['door_control'].','.$door_control;
                }
            }

            // 门禁卡到期时间
            if($this->house_session['owe_property_open_door'] == 1){
                if ($this->house_session['owe_property_open_door_day']) {
                    $time = $user_list['user_list'][$k]['property_endtime']+$this->house_session['owe_property_open_door_day']*86400;
                }else{
                    $time = $user_list['user_list'][$k]['property_endtime']+1576800000;
                }
            }else{
                $time = $user_list['user_list'][$k]['property_endtime'];
            }
            if ($village_info['write_iccard'] && $door_control_str) {
                $user_list['user_list'][$k]['door_str'] = $this->convert($time,$door_control_str);
            }

            $v['bind_unverify_num'] = $d_house_village_user_bind->where(array('parent_id'=>$v['pigcms_id'],'status'=>2))->count();

            //获取微信 openid
            if ($v['uid']) {
                $openidArr = D('User')->where(['uid' => $v['uid']])->field('openid,wxapp_openid')->find();
                $user_list['user_list'][$k]['openid'] = '';
                if($openidArr){
                    $user_list['user_list'][$k]['openid'] = $openidArr['openid'] ? $openidArr['openid']:$openidArr['wxapp_openid'];
                }
            }

            // 查看有无车位
            $user_list['user_list'][$k]['bind_position'] = $d_house_village_bind_position->where(array('user_id'=>$v['pigcms_id'],'village_id'=>$this->village_id))->count();
            // 加密查询更换
            $is_bind_face = false;
            if ($v['uid']>0) {
                $face_num = $user_list['user_list'][$k]['face_num'] = $d_house_face_img->where(array('uid' => $v['uid'], 'status'=>['in',[0,3]], 'img_url' => array('neq', '')))->count();
                if ($face_num > 0) {
                    $is_bind_face = true;
                    $user_list['user_list'][$k]['is_bind_face'] = true;
                } else {
                    $is_bind_face = false;
                    $user_list['user_list'][$k]['is_bind_face'] = false;
                }
            } else {
                $is_bind_face = false;
                $user_list['user_list'][$k]['is_bind_face'] = false;
            }
            // 处理下人脸同步状态 face_img_reason
            $faceTxt = '';
            $faceReason = '';
            if ($is_bind_face) {
                // 同步人脸状态 0 同步中（未操作） 1 同步成功 2 同步失败  5人员同步中 6人脸同步中 7人员人脸同步中
                if (isset($v['face_img_status'])&&$v['face_img_status']==1) {
                    $faceTxt = '已上传(同步成功)';
                } elseif (isset($v['face_img_status'])&&in_array($v['face_img_status'],[0,5,6,7])) {
                    $faceTxt = '已上传(同步中)';
                } elseif (isset($v['face_img_status'])&&$v['face_img_status']==2) {
                    $faceTxt = '已上传(同步失败)';
                    $faceReason = isset($v['face_img_reason'])?$v['face_img_reason']:'';
                } else {
                    $faceTxt = '已上传';
                }
            } else {
                // 同步人脸状态 0 同步中（未操作） 1 同步成功 2 同步失败  5人员同步中 6人脸同步中 7人员人脸同步中
                if (isset($v['face_img_status'])&&$v['face_img_status']==1) {
                    $faceTxt = '未上传(同步成功)';
                } elseif (isset($v['face_img_status'])&&$v['face_img_status']==0) {
                    $faceTxt = '未上传(未操作)';
                } elseif (isset($v['face_img_status'])&&in_array($v['face_img_status'],[5,6,7])) {
                    $faceTxt = '未上传(同步中)';
                } elseif (isset($v['face_img_status'])&&$v['face_img_status']==2) {
                    $faceTxt = '未上传(同步失败)';
                    $faceReason = isset($v['face_img_reason'])?$v['face_img_reason']:'';
                } else {
                    $faceTxt = '已上传';
                }
            }
            $user_list['user_list'][$k]['faceTxt'] = $faceTxt;
            $user_list['user_list'][$k]['faceReason'] = $faceReason;
            $user_list['user_list'][$k]['is_service_time_check']=0;
            if($service_time_check_open==1 && $service_time_check_wid>0){
                $where_check=array();
                $where_check['pigcms_id'] = $v['pigcms_id'];
                $where_check['xtype'] = 'service_time_check';
                $where_check['village_id'] = $village_id;
                $where_check['status'] = array('in','0,1');
                $reply_info = M('House_village_checkauth_apply')->where($where_check)->find();
                if($reply_info){
                    $where_con=array('apply_id'=>$reply_info['id'],'wid'=>$service_time_check_wid,'status'=>0);
                    $is_checkauth_detail=M('House_village_checkauth_detail')->where($where_con)->find();
                    if(!empty($is_checkauth_detail)){
                        $user_list['user_list'][$k]['is_service_time_check']=1;
                    }
                }
            }
            if(isset($v['phone']) && !empty($v['phone'])){
                $user_list['user_list'][$k]['phone']=$d_house_village->phone_desensitization_v($v['phone'],$village_id,$village_info);
            }
            if(isset($v['id_card']) && !empty($v['id_card'])){
                $user_list['user_list'][$k]['id_card']= $d_house_village->idnum_desensitization_v($v['id_card'],$village_id,$village_info);
            }
        }
        $village_info['long'] = floatval($village_info['long']);
        $village_info['lat'] = floatval($village_info['lat']);


        // 查询下是否存在人脸识别门禁设备A2
        $device = M('House_face_device')->where(array('village_id' => $village_id,'device_type'=>2))->find();
        if ($device) {
            $is_device = true;
        } else {
            $is_device = false;
        }
        $this->assign('is_device',$is_device);
        //楼栋
        $single_list = M('House_village_single')->where(array('village_id'=>$this->village_id,'status'=>1))->order('sort DESC,id desc')->select();
        if($single_list){
            $this->assign('single_list',$single_list);
        }else{
            $this->assign('single_list',[]);
        }
        
        $fingerprintJudgeConfig = D('House_face_device')->judgeFingerprintConfig($village_id);
        $this->assign('fingerprintJudgeConfig', $fingerprintJudgeConfig);

        
        $show_sky_cockpit_entrance=C('config.show_sky_cockpit_entrance');
        $show_sky_cockpit_entrance=$show_sky_cockpit_entrance ? intval($show_sky_cockpit_entrance):0;
        $this->assign('show_sky_cockpit_entrance',$show_sky_cockpit_entrance);
        fdump_api(['msg' => '处理完成','time' => microtime(true)],'$index',1);
        $hik_info_vision_i_community_app_key=C('config.hik_info_vision_i_community_app_key');
        $hik_info_vision_i_community_app_secret=C('config.hik_info_vision_i_community_app_secret');
        if($hik_info_vision_i_community_app_key && $hik_info_vision_i_community_app_secret){
            $this->assign('hik_info_vision_i_community',1);
        }
        $haveXiHanFaceDoor=0;
        $whereFace=array('village_id'=>$village_id,'device_type'=>31,'is_del'=>array('lt',4));
        $xihan_face_device=M('House_face_device')->where($whereFace)->field('device_id,device_name,device_type')->find();
        if($xihan_face_device && $xihan_face_device['device_id']){
            $haveXiHanFaceDoor=1;
        }
        $this->assign('haveXiHanFaceDoor', $haveXiHanFaceDoor);
        $this->assign('door_pwd',$this->pwd_convert($this->village['door_pwd']));
        $this->assign('door_sector',$this->village['door_sector']);
        $this->assign('village_info',$village_info);
        $this->assign('find_value', $find_value);
        $this->assign('find_type', $find_type);
        $this->assign('user_list', $user_list);
		
		//人脸识别截图尺寸
	    $this->assign('imageSize',['width'=>480,'height'=>640]);
        
	    $this->assign('village_id',$village_id);
		//是否展示数据来源
	    $this->assign('chinapopinOpen',$chinapopinOpen);

        $is_func_cloudmrsqfw_special=D('Config_customization')->getFuncCloudmrsqfwSpecial();
        $lilin_third_dev_api2=0;
        if($is_func_cloudmrsqfw_special){
            $whereArr=array('village_id'=>$village_id);
            $village_data = M('House_village')->where($whereArr)->find();
            if($village_data && !empty($village_data['lilin_pf_client_id']) && !empty($village_data['lilin_pf_client_secret']) && !empty($village_data['lilin_village_no'])){
                $lilin_third_dev_api2=1;
            }
        }
        $this->assign('lilin_third_dev_api2',$lilin_third_dev_api2);

        $this->display();
    }
    public function hikCommunityUserSyc(){
        $village_id=$this->house_session['village_id'];
        if( $village_id>0){
            $models = '\app\community\model\service\Device\HikInfoVisionICommunityService/sycBindUserToHikCommunity';
            $param=['village_id'=>$village_id,'bind_pigcms_id'=>0];
            $result = invoke_v20_service($models, $param);
            $result = $result['data']['retval'];
            $array = array(
                'errorCode' => 0,
                'result' => $result
            );
            exit(json_encode(array('errorCode'=>0,'errorMsg'=>'操作成功,','result'=>$result)));
        }
        exit(json_encode(array('errorCode'=>0,'errorMsg'=>'操作成功！')));
    }
    //批量修改物业时间
    public function property_time_edit(){
        //业主-批量修改物业编号
        if (0&&!in_array(276, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $this->idList = $_GET['idList'];
        if (IS_POST) {
            $idList = $_POST['idList'];

            $_POST['property_starttime']&&$data['property_starttime'] = strtotime($_POST['property_starttime'].' 00:00:00');
            $_POST['property_endtime']&&$data['property_endtime'] = strtotime($_POST['property_endtime'].' 23:59:59');
            if (!$idList) {
                $this->error('未选择业主');
            }
            if (!$_POST['property_starttime']&&!$_POST['property_endtime']) {
                $this->error('请填写物业时间！');
            }
            if ($_POST['property_starttime'] && $_POST['property_endtime'] && $data['property_starttime']>$data['property_endtime']) {
                $this->error('请填写正确的物业时间！');
            }
            $res = D('House_village_user_bind')->where(array('pigcms_id'=>array('in',explode(',',$idList)),'village_id'=>$this->village_id))->data($data)->save();
            if ($res!==false) {
                $this->success('修改成功');exit;
            }else{
                $this->error('修改失败');exit;
            }
        }
        $this->display();
    }

    //时间转换
    public function convert($time,$door_control_str){

        $twoy = decbin(date('y',$time));
        $twom = decbin(date('m',$time));
        $twod = decbin(date('d',$time));

        $len_nian = strlen($twoy);
        if($len_nian == 7){
            $str_nian = $twoy;
        }else{
            $str_nian = $this->str_prefix($twoy,intval( 7-$len_nian),'0');
        }

        $len_yue = strlen($twom);
        if($len_yue == 4){
            $str_yue = $twom;
        }else{
            $str_yue = $this->str_prefix($twom,intval( 4-$len_yue),'0');
        }

        $len_ri = strlen($twod);
        if($len_ri == 5){
            $str_ri = $twod;
        }else{
            $str_ri = $this->str_prefix($twod,intval( 5-$len_ri),'0');
        }

        // '二进制组合，日的第一部分放在最前+年+日的第二部分+月 -- 结果：';
        $total_str = substr($str_ri, 0,1).$str_nian.substr($str_ri, 1,4).$str_yue;
        // '组合之后转换成十六进制：';
        $ymdshiliu = dechex(bindec($total_str));
        //数据组合

        $data = '5100'.$ymdshiliu.'000000'.'000000000000000000'.$this->door_control_convert($door_control_str);
        // $data = '5000'.$ymdshiliu.'000000'.'000000000000000000'.'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF';
        return $data;

    }


    //门禁机转换
    public function door_control_convert($door_control_str){

        if(strlen($door_control_str) > 0){
            $door_array = explode(',', $door_control_str);
        }
        $door_two_str = '';
        for ($i=0; $i <= 127; $i++) {
            if(in_array($i, $door_array)){
                $door_two_str .= '1';
            }else{
                $door_two_str .= '0';
            }
        }

        $start_position = 0;
        $total_door_str = '';
        for ($c=0; $c < 16; $c++) {
            $byte = substr($door_two_str, $start_position,8);
            $byte_sixteen = dechex(bindec($byte));
            if(strlen($byte_sixteen) < 2){
                $byte_sixteen = $this->str_prefix($byte_sixteen,intval( 2-strlen($byte_sixteen)),'0');
            }
            $total_door_str .= $byte_sixteen;
            $start_position += 8;
        }

        return $total_door_str;
    }



    //密码转换
    public function pwd_convert($door_pwd){
        $door_pwd = explode(',', $door_pwd);

        $pwd1 = $door_pwd[0];
        $pwd2 = $door_pwd[1];
        $pwd3 = $door_pwd[2];
        $pwd4 = $door_pwd[3];
        $pwd5 = $door_pwd[4];
        $pwd6 = $door_pwd[5];

        $pwd_16_1 = dechex($pwd1);
        if(strlen($pwd_16_1) == 2){
            $pwd_16_1 = $pwd_16_1;
        }else{
            $pwd_16_1 = $this->str_prefix($pwd_16_1,intval( 2-strlen($pwd_16_1)),'0');
        }

        $pwd_16_2 = dechex($pwd2);
        if(strlen($pwd_16_2) == 2){
            $pwd_16_2 = $pwd_16_2;
        }else{
            $pwd_16_2 = $this->str_prefix($pwd_16_2,intval( 2-strlen($pwd_16_2)),'0');
        }

        $pwd_16_3 = dechex($pwd3);
        if(strlen($pwd_16_3) == 2){
            $pwd_16_3 = $pwd_16_3;
        }else{
            $pwd_16_3 = $this->str_prefix($pwd_16_3,intval( 2-strlen($pwd_16_3)),'0');
        }

        $pwd_16_4 = dechex($pwd4);
        if(strlen($pwd_16_4) == 2){
            $pwd_16_4 = $pwd_16_4;
        }else{
            $pwd_16_4 = $this->str_prefix($pwd_16_4,intval( 2-strlen($pwd_16_4)),'0');
        }

        $pwd_16_5 = dechex($pwd5);
        if(strlen($pwd_16_5) == 2){
            $pwd_16_5 = $pwd_16_5;
        }else{
            $pwd_16_5 = $this->str_prefix($pwd_16_5,intval( 2-strlen($pwd_16_5)),'0');
        }

        $pwd_16_6 = dechex($pwd6);
        if(strlen($pwd_16_6) == 2){
            $pwd_16_6 = $pwd_16_6;
        }else{
            $pwd_16_6 = $this->str_prefix($pwd_16_6,intval( 2-strlen($pwd_16_6)),'0');
        }

        $pwd_total = $pwd_16_1.$pwd_16_2.$pwd_16_3.$pwd_16_4.$pwd_16_5.$pwd_16_6;

        return $pwd_total;

    }

    public function str_prefix($str, $n=1, $char="0"){
        for ($x=0;$x<$n;$x++){$str = $char.$str;}
        return $str;
    }

    public function str_suffix($str, $n=1, $char="0"){
        for ($x=0;$x<$n;$x++){$str = $str.$char;}
        return $str;
    }

    public function card_no_add(){
        $res =  D('House_village_user_bind')->where(array('pigcms_id'=>intval($_POST['pigcms_id'])))->data(array('card_no'=>$_POST['card_no']))->save();
        if($res){
            exit(json_encode(array('error'=>1,'msg'=>'更新门禁卡号成功')));
        }else{
            exit(json_encode(array('error'=>2,'msg'=>'更新门禁卡号失败','sql'=>D('House_village_user_bind')->getlastsql())));
        }
    }

    // 家属绑定列表
    public function bind_list(){
        //家属租客-查看 权限
        
        $pigcms_id = $_GET['pigcms_id'] + 0;
        $where['parent_id'] = $pigcms_id;
        $where['status'] = ['not in', [3,4]];
        $user_list = D('House_village_user_bind')->field(true)->where($where)->order('`pigcms_id` DESC')->select();
        $parent_info = D('House_village_user_bind')->where(array('pigcms_id'=>$pigcms_id))->find();
        $parent_info['openid'] = D('User')->where(array('uid'=>$parent_info['uid']))->getField('openid');


        $door_control = D('House_village_floor')->where(array('floor_id'=>$parent_info['floor_id']))->getField('door_control');
        if (empty($door_control)) {
            $door_control_str = $parent_info['door_control'];
        }elseif ($parent_info['door_control'] == null) {
            $door_control_str = $door_control;
        }else{
            $door_control_str = $parent_info['door_control'].','.$door_control;
        }

        // 门禁卡到期时间
        if($this->house_session['owe_property_open_door'] == 1){
            if ($this->house_session['owe_property_open_door_day']) {
                $time = $parent_info['property_endtime']+$this->house_session['owe_property_open_door_day']*86400;
            }else{
                $time = $parent_info['property_endtime']+1576800000;
            }
        }else{
            $time = $parent_info['property_endtime'];
        }
        $d_house_village=D('House_village');
        $village_info = $d_house_village->field('*')->where(array('village_id'=>$this->village_id))->find();
        $parent_info['door_str'] = $this->convert($time,$door_control_str);
        // 业主属性
        $d_house_face_img = M('House_face_img');
        foreach ($user_list as &$row) {
            $is_bind_face = false;
            if ($row['uid']>0) {
                $row['face_num'] = $d_house_face_img->where(array('uid' => $row['uid'], 'status'=>['in',[0,3]], 'img_url' => array('neq', '')))->count();
                if ($row['face_num'] > 0) {
                    $is_bind_face = true;
                }
            }
            $row['is_bind_face'] = $is_bind_face;

            // 处理下人脸同步状态 face_img_reason
            $faceReason = '';
            if ($is_bind_face) {
                // 同步人脸状态 0 同步中（未操作） 1 同步成功 2 同步失败  5人员同步中 6人脸同步中 7人员人脸同步中
                if (isset($row['face_img_status'])&&$row['face_img_status']==1) {
                    $faceTxt = '已上传(同步成功)';
                } elseif (isset($row['face_img_status'])&&in_array($row['face_img_status'],[0,5,6,7])) {
                    $faceTxt = '已上传(同步中)';
                } elseif (isset($row['face_img_status'])&&$row['face_img_status']==2) {
                    $faceTxt = '已上传(同步失败)';
                    $faceReason = isset($row['face_img_reason'])?$row['face_img_reason']:'';
                } else {
                    $faceTxt = '已上传';
                }
            } else {
                // 同步人脸状态 0 同步中（未操作） 1 同步成功 2 同步失败  5人员同步中 6人脸同步中 7人员人脸同步中
                if (isset($row['face_img_status'])&&$row['face_img_status']==1) {
                    $faceTxt = '未上传(同步成功)';
                } elseif (isset($row['face_img_status'])&&$row['face_img_status']==0) {
                    $faceTxt = '未上传(未操作)';
                } elseif (isset($row['face_img_status'])&&in_array($row['face_img_status'],[5,6,7])) {
                    $faceTxt = '未上传(同步中)';
                } elseif (isset($row['face_img_status'])&&$row['face_img_status']==2) {
                    $faceTxt = '未上传(同步失败)';
                    $faceReason = isset($row['face_img_reason'])?$row['face_img_reason']:'';
                } else {
                    $faceTxt = '未上传';
                }
            }
            $row['faceTxt'] = $faceTxt;
            $row['faceReason'] = $faceReason;
            if(isset($row['phone']) && !empty($row['phone'])){
                $row['phone']=$d_house_village->phone_desensitization_v($row['phone'],$this->village_id,$village_info);
            }
            if(isset($row['id_card']) && !empty($row['id_card'])){
                $row['id_card']= $d_house_village->idnum_desensitization_v($row['id_card'],$this->village_id,$village_info);
            }
        }

        $this->assign('door_pwd',$this->pwd_convert($this->village['door_pwd']));
        $this->assign('door_sector',$this->village['door_sector']);
        $this->assign('user_list' , $user_list);
        $this->assign('parent_info' , $parent_info);
        $this->display();
    }

    // 添加编辑家属信息
    public function bind_other(){
        //权限
        if (0 && !in_array(116, $this->house_session['menus']) && !in_array(104, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if(IS_POST){
			$_POST['name'] = trim($_POST['name']);
			$_POST['phone'] = trim($_POST['phone']);
			$_POST['id_card'] = trim($_POST['id_card']);

			if(empty($_POST['name'])){
				$this->error('对不起，姓名必填');
			}

			$nmv_card = trim($_POST['nmv_card']);
			unset($_POST['nmv_card']);
			
            $data_post = $_POST;
            unset($data_post['pigcms_id']);
            unset($data_post['edit']);
            unset($data_post['name']);
            unset($data_post['phone']);
            unset($data_post['id_card']);
            unset($data_post['ic_card']);
            unset($data_post['type']);
            unset($data_post['relatives_type']);
            unset($data_post['attribute']);
            unset($data_post['memo']);
            unset($data_post['city_idss']);
            unset($data_post['province_idss']);
//            echo '<pre>';
//            var_dump($data_post);die;
            $_POST['name'] = trim($_POST['name']);
            $_POST['phone'] = trim($_POST['phone']);
            if($data_post['user_political_affiliation'] == 1 && $data_post['party_type'] < 1){
                $this->error('请选择党员属性！');
            }
            if($data_post['user_political_affiliation'] == 1 && $data_post['party_type'] == 1 && $data_post['user_party_id'] < 1){
                $this->error('请选择党支部！');
            }
            if($data_post['user_political_affiliation'] != 1 || $data_post['party_type'] != 1){
                $_POST['user_party_id'] = 0;
            }
            foreach($data_post as $k=>$v){
                if(is_array($v) && $v['is_must'] == 1 && empty($v['value'])){
                    $this->error($v['title'].'为必填项！');
                }
            }
            foreach($data_post as $k=>&$v){
                if($v['key'] == 'native_place'){
                    $v['value'] = $_POST['province_idss'].'#'.$_POST['city_idss'];
                }
            }
            $set_id_code = M('House_village')->where(['village_id'=>$this->village_id])->getField('set_id_code');
            if($set_id_code == 1){
                if(!$_POST['id_card']){
                    $this->error('身份证号码不能为空！');
                }
                if(is_idcard($_POST['id_card']) == false){
                    $this->error('请填写正确的身份证号码');
                }
            }
            unset($_POST['province_idss']);
            unset($_POST['city_idss']);
            $data_post_list['authentication_field'] = serialize($data_post);

            $user_label_arr=[
                'user_political_affiliation'=>isset($_POST['user_political_affiliation'])? $_POST['user_political_affiliation'] : 0,
                'user_party_id'=>isset($_POST['user_party_id'])? $_POST['user_party_id'] : 0,
                'user_special_groups'=>isset($_POST['user_special_groups']) ? $_POST['user_special_groups'] : [],
                'user_focus_groups'=>isset($_POST['user_focus_groups']) ? $_POST['user_focus_groups'] : [],
                'user_vulnerable_groups'=>isset($_POST['user_vulnerable_groups']) ? $_POST['user_vulnerable_groups'] : [],
                'user_label_groups'=>isset($_POST['user_label_groups']) ? $_POST['user_label_groups'] : [],
                'party_type'=>$data_post['party_type'],
            ];
            unset($_POST['user_political_affiliation'],$_POST['user_party_id'],$_POST['user_special_groups'],$_POST['user_focus_groups'],$_POST['user_vulnerable_groups'],$_POST['user_label_groups']);

            if ($_POST['edit'] == 1) { // 编辑家属信息
                //家属租客-编辑 权限
                if (0&&!in_array(468, $this->house_session['menus']) && !in_array(469, $this->house_session['menus'])) {
                    $this->error('对不起，您没有权限执行此操作');
                }

                $child_info =  D('House_village_user_bind')->get_one_by_bindId($_POST['pigcms_id']);
                if(!$child_info){
                    $this->error((C('config.open_house_user_name')?'亲友':'家属').'信息不存在');
                }
                if ($child_info && $child_info['uid']>0) {
                    $uid = $child_info['uid'];
                }
                $bind_info =  D('House_village_user_bind')->get_one_by_bindId($child_info['parent_id']);
                // 手机号不能与当前房屋中待审核和审核通过的手机号一样
                if ($_POST['phone']) {
                    $bind_condition = [];
                    $bind_condition['pigcms_id'] = ['neq', $child_info['pigcms_id']];
                    $bind_condition['vacancy_id'] = $child_info['vacancy_id'];
                    $bind_condition['phone'] = $_POST['phone'];
                    $bind_condition['type'] = ['in', [0,1,2,3]];
                    $bind_condition['status'] = array('in' , [1,2]);
                    $other_bind_info = D('House_village_user_bind')->where($bind_condition)->find();
                    if($other_bind_info){
                        $this->error('该手机号已绑定或已申请绑定此房间');
                    }
                }
                // 姓名不能与当前房屋中待审核和审核通过的姓名一样
                if ($_POST['name'] && $_POST['phone']) {
                    $bind_condition = [];
                    $bind_condition['pigcms_id'] = ['neq', $child_info['pigcms_id']];
                    $bind_condition['vacancy_id'] = $child_info['vacancy_id'];
                    $bind_condition['name'] = $_POST['name'];
                    $bind_condition['phone'] = $_POST['phone'];
                    if ($_POST['phone']) {
                        $bind_condition['phone'] = $_POST['phone'];
                    }
                    $bind_condition['type'] = ['in', [0,1,2,3]];
                    $bind_condition['status'] = ['in' , [1,2]];
                    $other_bind_info = D('House_village_user_bind')->where($bind_condition)->find();
                    if($other_bind_info){
                        $this->error("{$_POST['name']}已绑定或已申请绑定此房间");
                    }
                }
                if (trim($_POST['ic_card'])) {
                    // 检查下是否当前ic卡已经被其他人使用 状态处于正常的
                    $where_repeat = [
                        'ic_card' => trim($_POST['ic_card']),
                        'status' => 1,
                        'pigcms_id' => ['neq', $child_info['pigcms_id']]
                    ];
                    $repeat = D('House_village_user_bind')->where($where_repeat)->field('pigcms_id,village_id,name')->find();
                    if (!empty($repeat)) {
                        if ($repeat['village_id']==$this->village_id) {
                            // 同一个小区提示具体重复人员
                            $this->error("当前用户【".$repeat['name']."】已经使用了该卡号");
                        } else {
                            $where_village = [
                                'village_id' => $repeat['village_id']
                            ];
                            $village_name = M('House_village')->where($where_village)->getField('village_name');
                            // 不同小区提示小区
                            $this->error("当前【".$village_name."】小区用户已经使用了该卡号");
                        }
                    }
                }
                if (trim($_POST['pile_card_no'])) {
                    // 检查下是否当前ic卡已经被其他人使用 状态处于正常的
                    $where_repeat = [
                        'pile_card_no' => trim($_POST['pile_card_no']),
                        'status' => 1,
                        'pigcms_id' => ['neq', $child_info['pigcms_id']]
                    ];
                    $repeat = D('House_village_user_bind')->where($where_repeat)->field('pigcms_id,village_id,name')->find();
                    if (!empty($repeat)) {
                        if ($repeat['village_id']==$this->village_id) {
                            // 同一个小区提示具体重复人员
                            $this->error("当前用户【".$repeat['name']."】已经使用了该卡号");
                        } else {
                            $where_village = [
                                'village_id' => $repeat['village_id']
                            ];
                            $village_name = M('House_village')->where($where_village)->getField('village_name');
                            // 不同小区提示小区
                            $this->error("当前【".$village_name."】小区用户已经使用了该卡号");
                        }
                    }
                }

                // 非机动车卡号
                if ($nmv_card) {
                    // 检查下是否当前ic卡已经被其他人使用 状态处于正常的
                    $where_nmv['nmv_card'] = $nmv_card;
                    $where_nmv['pigcms_id'] = ['neq', $child_info['pigcms_id']];
                    $nmv = D('House_village_user_bind')->field('pigcms_id,village_id,name')->where($where_nmv)->find();
                    if (!empty($nmv)) {
                        if ($nmv['village_id']==$this->village_id) {
                            // 同一个小区提示具体重复人员
                            $this->error("当前用户【".$nmv['name']."】已经使用了该卡号");
                        } else {
                            $where_village = [
                                'village_id' => $nmv['village_id']
                            ];
                            $village_name = M('House_village')->where($where_village)->getField('village_name');
                            // 不同小区提示小区
                            $this->error("当前【".$village_name."】小区用户已经使用了该卡号");
                        }
                    }
                }
            }
            else {
                //家属租客-添加 权限
                if (0&&!in_array(110, $this->house_session['menus'])) {
                    $this->error('对不起，您没有权限执行此操作');
                }

                $bind_info =  D('House_village_user_bind')->get_one_by_bindId($_POST['pigcms_id']);
                // 手机号不能与当前房屋中待审核和审核通过的手机号一样
                if ($_POST['phone']) {
                    $bind_condition = [];
                    $bind_condition['vacancy_id'] = $bind_info['vacancy_id'];
                    $bind_condition['phone'] = $_POST['phone'];
                    $bind_condition['type'] = ['in', [0,1,2,3]];
                    $bind_condition['status'] = ['in' , [1,2]];
                    $other_bind_info = D('House_village_user_bind')->where($bind_condition)->find();
                    if($other_bind_info){
                        $this->error('该手机号已绑定或已申请绑定此房间');
                    }
                }
                // 姓名不能与当前房屋中待审核和审核通过的姓名一样
                if ($_POST['name'] && $_POST['phone']) {
                    $bind_condition = [];
                    $bind_condition['vacancy_id'] = $bind_info['vacancy_id'];
                    $bind_condition['name'] = $_POST['name'];
                    $bind_condition['phone'] = $_POST['phone'];
                    $bind_condition['type'] = ['in', [0,1,2,3]];
                    $bind_condition['status'] = ['in' , [1,2]];
                    $other_bind_info = D('House_village_user_bind')->where($bind_condition)->find();
                    if($other_bind_info){
                        $this->error("{$_POST['name']}已绑定或已申请绑定此房间");
                    }
                }
                $uid = 0;
                if (trim($_POST['ic_card'])) {
                    // 检查下是否当前ic卡已经被其他人使用 状态处于正常的
                    $where_repeat = [
                        'ic_card' => trim($_POST['ic_card']),
                        'status' => 1,
                    ];
                    $repeat = D('House_village_user_bind')->where($where_repeat)->field('pigcms_id,village_id,name')->find();
                    if (!empty($repeat)) {
                        if ($repeat['village_id']==$this->village_id) {
                            // 同一个小区提示具体重复人员
                            $this->error("当前用户【".$repeat['name']."】已经使用了该卡号");
                        } else {
                            $where_village = [
                                'village_id' => $repeat['village_id']
                            ];
                            $village_name = M('House_village')->where($where_village)->getField('village_name');
                            // 不同小区提示小区
                            $this->error("当前【".$village_name."】小区用户已经使用了该卡号");
                        }
                    }
                }

                if (trim($_POST['pile_card_no'])) {
                    // 检查下是否当前ic卡已经被其他人使用 状态处于正常的
                    $where_repeat = [
                        'pile_card_no' => trim($_POST['pile_card_no']),
                        'status' => 1,
                    ];
                    $repeat = D('House_village_user_bind')->where($where_repeat)->field('pigcms_id,village_id,name')->find();
                    if (!empty($repeat)) {
                        if ($repeat['village_id']==$this->village_id) {
                            // 同一个小区提示具体重复人员
                            $this->error("当前用户【".$repeat['name']."】已经使用了该卡号");
                        } else {
                            $where_village = [
                                'village_id' => $repeat['village_id']
                            ];
                            $village_name = M('House_village')->where($where_village)->getField('village_name');
                            // 不同小区提示小区
                            $this->error("当前【".$village_name."】小区用户已经使用了该卡号");
                        }
                    }
                }
                // 非机动车卡号
                if ($nmv_card) {
                    // 检查下是否当前ic卡已经被其他人使用 状态处于正常的
                    $where_nmv['nmv_card'] = $nmv_card;
                    $nmv = D('House_village_user_bind')->field('pigcms_id,village_id,name')->where($where_nmv)->find();
                    if (!empty($nmv)) {
                        if ($nmv['village_id']==$this->village_id) {
                            // 同一个小区提示具体重复人员
                            $this->error("当前用户【".$nmv['name']."】已经使用了该卡号");
                        } else {
                            $where_village = [
                                'village_id' => $nmv['village_id']
                            ];
                            $village_name = M('House_village')->where($where_village)->getField('village_name');
                            // 不同小区提示小区
                            $this->error("当前【".$village_name."】小区用户已经使用了该卡号");
                        }
                    }
                }
            }
            if(!$bind_info){
                $this->error('业主信息不存在');
            }

            if($_POST['phone']==$bind_info['phone'] && !empty($bind_info['phone'])){
                $this->error('手机号码不能跟业主的手机号码相同');
            }
            fdump_api(['单个编辑家属租客'.__LINE__,$_GET,$_POST,$this->house_session],'house/user/bind_other_log',1);

            /*if(empty($_POST['phone'])){
                $this->error('手机号码不能为空');
            }*/

            $bind_data = $bind_info;
            unset($bind_data['keyId']);
            unset($bind_data['pigcms_id']);

            if($_POST['phone'] && !$uid){
                $user_info = D('User')->where(array('phone'=>$_POST['phone']))->find();
                if ($user_info['uid']) {
                    $uid = $user_info['uid'];
                }
            }elseif ($_POST['phone'] && $uid){
                $user_msg = D('User')->where(array('uid'=>$uid))->field('uid, phone')->find();
                $user_phone_msg = D('User')->where(array('phone'=>$_POST['phone']))->field('uid, phone')->find();
                if (empty($user_phone_msg) && $user_msg && !$user_msg['phone']) {
                    D('User')->where(['uid' => $uid])->data(['phone'=>$_POST['phone']])->save();
                } elseif($user_phone_msg) {
                    // 变更了 手机号  自动更正对应所属uid 如果查不到信息 自动更改uid为0
                    if ($user_phone_msg['uid']) {
                        $uid = $user_phone_msg['uid'];
                    } else {
                        $uid = 0;
                    }
                }
            } elseif (!$_POST['phone'] && $child_info && $child_info['phone'] && $child_info['uid']==$uid) {
                $uid = 0;
            }
            if(empty($user_info) && !$uid){
                $user_add = [];
				
				if($_POST['phone'] && $_POST['name']){
					$data_user = array(
						'nickname' 	=> $_POST['name'],
						'phone' 	=> $_POST['phone'],
						'source' 	=> 'houseautoreg',
					);
					$reg_result = D('User')->autoreg($data_user);
					if($reg_result['error_code']){
						$this->error($reg_result['error_code']);
					}
					$uid = $reg_result['uid'];
				}
            }
            /*if(empty($user_info)){
                $this->error('提交失败，用户不是平台用户，请先注册平台会员。');
            }*/
            set_time_limit(0);
            ini_set('memory_limit', '-1');
            if($_POST['edit'] == 1 && $child_info){
                $edit_data = array();
                $where = array();
                $where['pigcms_id'] = $_POST['pigcms_id'];
                $edit_data['name'] = $_POST['name'] ? $_POST['name'] : '';
                $edit_data['phone'] =  $_POST['phone'] ? $_POST['phone'] : '';
                $edit_data['type'] = $_POST['type'] ? $_POST['type'] : 1;
                $edit_data['memo'] = $_POST['memo'] ? $_POST['memo'] : '';
                $edit_data['attribute'] = isset($_POST['attribute'])? $_POST['attribute']:0;
                $edit_data['id_card'] = $_POST['id_card']?$_POST['id_card']:'';
                $edit_data['ic_card'] = trim($_POST['ic_card'])?trim($_POST['ic_card']):'';
                $edit_data['pile_card_no'] = trim($_POST['pile_card_no'])?trim($_POST['pile_card_no']):'';
                $edit_data['authentication_field'] = $data_post_list['authentication_field']?$data_post_list['authentication_field'] :'';
                if (isset($_POST['relatives_type'])) {
                    $edit_data['relatives_type'] = $_POST['relatives_type'];
                }
                if ($child_info['uid']!=$uid) {
                    $edit_data['uid'] = $uid;
                }
                if(isset($_POST['open_door_day'])){
                    $edit_data['open_door_day'] = $_POST['open_door_day'];
                }
                $user_label_arr['pigcms_id']=$where['pigcms_id'];
                $insert_id = D('House_village_user_bind')->where($where)->save($edit_data);
                if (intval($_POST['pigcms_id'])>0) {
                    $insert_id = intval($_POST['pigcms_id']);
                    $change_param = [];
                    $change_param['pigcms_id'] = intval($_POST['pigcms_id']);
                    D('House_village_user_vacancy')->change_user_bind_number($change_param);
                    $arr = array();
                    $arr['param'] = serialize(array('bind_id' =>$_POST['pigcms_id'],'type'=>'user'));
                    $arr['plan_time'] = time()+60;
                    $arr['space_time'] = 0;
                    $arr['add_time'] = time();
                    $arr['file'] = 'sub_house_village_third';
                    $arr['time_type'] = 1;
                    $arr['unique_id'] = 'sub_house_village_third_user_'.$_POST['pigcms_id'];
                    $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
                    M('process_sub_plan')->data($arr)->add();
                }
                //如果填写了IC卡   向a5门禁 发卡
                D('House_face_img')->house_user_face_device_info($uid,intval($_POST['pigcms_id']));
            }else{
                $bind_data['uid'] = $uid;
                $bind_data['name'] = $_POST['name'] ? $_POST['name'] : '';
                $bind_data['phone'] =  $_POST['phone'] ? $_POST['phone'] : '';
                $bind_data['type'] = $_POST['type'] ? $_POST['type'] : 1;
                $bind_data['status'] = 1;
                $bind_data['usernum'] = rand(0,99999) . '-' . time();
                $bind_data['add_time'] = time();
                $bind_data['parent_id'] = $_POST['pigcms_id'];
                $bind_data['attribute'] = isset($_POST['attribute'])? $_POST['attribute']:0;
                $bind_data['id_card'] = $_POST['id_card']?$_POST['id_card']:'';
                $bind_data['ic_card'] = $_POST['ic_card']?$_POST['ic_card']:'';
                $bind_data['pile_card_no'] = $_POST['pile_card_no']?$_POST['pile_card_no']:'';
                $bind_data['memo'] = $_POST['memo'] ? $_POST['memo'] : '';
                $bind_data['authentication_field'] = $data_post_list['authentication_field']?$data_post_list['authentication_field'] :'';
                if (isset($_POST['relatives_type'])) {
                    $bind_data['relatives_type'] = $_POST['relatives_type'];
                }
                if(isset($_POST['open_door_day'])){
                    $bind_data['open_door_day'] = $_POST['open_door_day'];
                }
                // 检查下是否存在 房屋编号  楼栋编号和楼层编号
                if (($bind_data['single_id'] || $bind_data['layer_id']) && $bind_data['vacancy_id']) {
                    // 查询下房屋编号 更新下楼栋和楼层
                    $vacancy_info = D('House_village_user_vacancy')->where(['pigcms_id'=>$bind_data['vacancy_id']])->field('single_id,layer_id')->find();
                    if ($vacancy_info && $vacancy_info['single_id']) {
                        $bind_data['single_id'] = $vacancy_info['single_id'];
                    }
                    if ($vacancy_info && $vacancy_info['layer_id']) {
                        $bind_data['layer_id'] = $vacancy_info['layer_id'];
                    }
                }

                $insert_id = D('House_village_user_bind')->data($bind_data)->add();
                $user_label_arr['pigcms_id']=$insert_id;
                if (intval($insert_id)>0) {
                    $arr = array();
                    $arr['param'] = serialize(array('bind_id' =>$insert_id,'type'=>'user'));
                    $arr['plan_time'] = time()+60;
                    $arr['space_time'] = 0;
                    $arr['add_time'] = time();
                    $arr['file'] = 'sub_house_village_third';
                    $arr['time_type'] = 1;
                    $arr['unique_id'] = 'sub_house_village_third_user_'.$insert_id;
                    $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
                    M('process_sub_plan')->data($arr)->add();
                    $change_param = [];
                    $change_param['pigcms_id'] = $insert_id;
                    D('House_village_user_vacancy')->change_user_bind_number($change_param);
                }
                D('House_face_img')->house_user_face_device_info($uid,$insert_id);
            }
            $user_label_arr['uid']=$uid;
            if($insert_id !== false){

                $bind_id = ($_POST['edit'] == 1) ? $_POST['pigcms_id'] : $insert_id;
                // 合并标签
                $label_arr = array_merge([$_POST['political_outlook']],(array)$_POST['special_population'],(array)$_POST['key_population'],(array)$_POST['care_object']);
                $label_ids = implode(',', $label_arr);
                $param = [$label_ids,$bind_id,$this->village_id];
                // 保存标签
                //invoke_v20_service('\app\community\model\service\HouseVillageUserLabelBindService/addUserLabelBind',$param);
                //todo 信息标注编辑-添加or编辑
                D('House_village_user_label')->saveUserLabelMethod($user_label_arr);

                //todo 轨迹信息 用户绑定/编辑家属或租客
                $str = '业主'.(($_POST['edit'] == 1)?'编辑':'绑定').(($_POST['type'] == 1)?'家人':'租客').$_POST['name'];
                D('House_user_trajectory_log')->addLog(4,$this->village_id,0,$bind_info['uid'],$bind_info['pigcms_id'],(($_POST['edit'] == 1)?2:1),$str,'后台更新了人员数据');

                
                invoke_v20_service('\app\community\model\service\CloudIntercomService/saveUsernmvCard',[$insert_id,$nmv_card]);
                
                if($this->huizhisq && $_POST['ic_card']){ // 门禁卡
                    $res = invoke_v20_service('\app\community\model\service\CloudIntercomService/sendIcCardToDevice',[$_POST['ic_card'],1]);
                    fdump_api(['$res' => $res],'dopu/ic_card',1);
                }
                $hik_info_vision_i_community_app_key=C('config.hik_info_vision_i_community_app_key');
                $hik_info_vision_i_community_app_secret=C('config.hik_info_vision_i_community_app_secret');
                if($hik_info_vision_i_community_app_key && $hik_info_vision_i_community_app_secret){
                    $hikRet=invoke_v20_service('\app\community\model\service\Device\HikInfoVisionICommunityService/updateBindUserToHikCommunity', ['village_id'=>$this->village_id,'pigcms_id'=>$bind_id]);
                }
                $this->success('提交成功');
            }else{
                $this->error('提交失败');
            }
        }else{
            //家属租客-查看 权限
            if (0&& !in_array(115, $this->house_session['menus']) && !in_array(103, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }
            $where_village['village_id'] = $this->village_id;
            $house_village = D('House_village')->where($where_village)->field('property_id,street_id,province_id,city_id,area_id,owe_property_open_door')->find();
            $property_id = $house_village['property_id'];
            $where_data_config = array(
                'village_id'=>0,
                'property_id'=>$property_id,
                'is_display'=>1,
                'is_close'=>0
            );
            $dataList = D('House_village_data_config')->where($where_data_config)->order('sort desc')->select();

            foreach($dataList as $k=>&$v){
                if($v['type'] == 2){
                    if($v['is_system'] == 1){
                        $v['use_field'] = D('House_village')->get_system_value($v['key']);
                    }else{
                        $v['use_field'] = explode(',',$v['use_field']);
                    }
                }elseif($v['type'] == 3){

                    $v['use_field'] = [];
                }else{
                    $v['use_field'] = [];
                }
            }

            $this->assign('dataList',$dataList);
            $bind_info =  D('House_village_user_bind')->get_one_by_bindId($_GET['pigcms_id']);
            if($bind_info['authentication_field']){
                $bind_info['authentication_field'] = unserialize($bind_info['authentication_field']);
            }
            $authentication_field=array();
            if(!in_array($bind_info['type'],[1,2])){
                $authentication_field['nationality']=array();
                $authentication_field['nationality']['value']='中国';
                $authentication_field['nation']=array();
                $authentication_field['nation']['value']='汉族';
                if($house_village['province_id']>0){
                    $authentication_field['native_place']=array();
                    $authentication_field['native_place']['type']=3;
                    $authentication_field['native_place']['province_idss'] =$house_village['province_id'];
                    $authentication_field['native_place']['value']=$house_village['province_id'].'#';
                    if($house_village['city_id']>0){
                        $authentication_field['native_place']['city_idss'] =$house_village['city_id'];
                        $authentication_field['native_place']['value']=$house_village['province_id'].'#'.$house_village['city_id'];
                    }
                }
            }
            $house_type = M('House_village_user_vacancy')->where(['pigcms_id'=>$bind_info['vacancy_id']])->getField('house_type');
            foreach($bind_info['authentication_field'] as $k=>&$v){
                if($v['type'] == 3){
                    $v['value'] = explode('#',$v['value']);
                    if(is_array($v['value'])){
                        $v['province_idss'] = $v['value'][0] ? $v['value'][0]:'';
                        $v['city_idss'] = $v['value'][1]? $v['value'][1]:'';
                    }
                }
            }
            if (in_array($bind_info['type'],[1,2])) {
                $this->assign('bind_info',$bind_info);
            }else if($authentication_field){
                $bind_info_tmp=array();
                $bind_info_tmp['authentication_field']=$authentication_field;
                $this->assign('bind_info',$bind_info_tmp);
            }
            if($house_type != 1){
                $relatives_type_info = array(
                    0 => array(
                        'relatives_type' => 1,
                        'name' => '配偶'
                    ),
                    1 => array(
                        'relatives_type' => 2,
                        'name' => '父母'
                    ),
                    2 => array(
                        'relatives_type' => 3,
                        'name' => '子女'
                    ),
                    3 => array(
                        'relatives_type' => 4,
                        'name' => '亲朋好友'
                    ),
                    4 => array(
                        'relatives_type' => 5,
                        'name' => '老板'
                    ),
                    5 => array(
                        'relatives_type' => 6,
                        'name' => '人事'
                    ),
                    6 => array(
                        'relatives_type' => 7,
                        'name' => '财务'
                    ),
                );
            }else{
                $relatives_type_info = D('House_village_user_bind')->relatives_type_info;
                if (empty($relatives_type_info)) {
                    $relatives_type_info = array(
                        0 => array(
                            'relatives_type' => 1,
                            'name' => '配偶'
                        ),
                        1 => array(
                            'relatives_type' => 2,
                            'name' => '父母'
                        ),
                        2 => array(
                            'relatives_type' => 3,
                            'name' => '子女'
                        ),
                        3 => array(
                            'relatives_type' => 4,
                            'name' => '亲朋好友'
                        ),
                    );
                }
            }

            // 用户标签列表
            $param = [
                $this->village_id,($_GET['edit'] == 1) ? $_GET['pigcms_id'] : 0,'*',array(array('status','=','0'),'oderby'=>'id asc')
            ];

            if($_GET['edit'] == 1){ //编辑家属
                $user_bind_uid=$bind_info['uid'];
                $user_bind_id=$bind_info['pigcms_id'];
            }else{ //添加家属
                $user_bind_uid=0;
                $user_bind_id=0;
            }
            //信息标注
            $user_tag=D('House_village_user_label')->getUserLabelMethod($house_village['street_id'],$this->village_id,$user_bind_uid,$user_bind_id);
            $this->assign('user_tag',$user_tag);


            $this->assign('relatives_type_info',$relatives_type_info);
            $this->assign('house_type',$house_type);

            $third_import_data_switch_judge = D('Config_customization')->getThirdImportDataSwitch();
            if ($third_import_data_switch_judge) {
                $info = D('Third_import_data')->getThirdImportUserInfo($this->village_id, $_GET['pigcms_id']);
//                if (!$info) {
//                    $params = ['village_id'=>$this->village_id,'bind_id'=>$_GET['pigcms_id']];
//                    $result = invoke_v20_service('\app\community\model\service\third\ImportExcelService/getThirdImportUserInfo',$params);
//                    $info = $result['data']['retval'];
//                }
                if (isset($info['showArr']) && $info['showArr']) {
                    $showArr = $info['showArr'];
                } else {
                    $showArr = [];
                }
                $this->assign('showArr', $showArr);
            }
            $userOpenDoorDay=D('Config_customization')->getCustomizedUserOpenDoorDay();
            $userOpenDoorDayTmp=0;
            if($house_village['owe_property_open_door']>0 && $userOpenDoorDay){
                $userOpenDoorDayTmp=1;
            }
            $this->assign('userOpenDoorDay', $userOpenDoorDayTmp);
            $this->display();
        }
    }

    // 删除家属信息
    public function bind_delete(){
        //家属租客-删除 权限
        if (0&&!in_array(470, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $pigcms_id=intval($_GET['pigcms_id']);
        $bind_info = D('House_village_user_bind')->get_one_by_bindId($pigcms_id,$this->village_id);
        $data['village_id'] = $bind_info['village_id'];
        $data['vacancy_id'] = $bind_info['vacancy_id'];
        $data['floor_id'] = $bind_info['floor_id'];
        $data['uid'] = $bind_info['uid'];
        $data['name'] = $bind_info['name'];
        $data['phone'] = $bind_info['phone'];
        $data['type'] = $bind_info['type'];
        $data['check_in_time'] = $bind_info['add_time'];
        $data['user_bind_id'] = $bind_info['pigcms_id'];
        $data['add_time'] = time();
        M('House_village_user_record')->data($data)->add();
        $info = D('House_village_user_bind')->where(array('pigcms_id'=>$pigcms_id))->find();

        fdump('>>>>>>'.__LINE__.';function:'.__FUNCTION__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
        fdump($info,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);

        /**
         * todo 软删除 修改类型 usernum拼上时间戳
         */
        $delRet=D('House_village_user_bind')->where(array('pigcms_id'=>$pigcms_id))->data(['usernum'=>$bind_info['usernum'].'-'.createRandomStr(11,true),'status'=>4])->save();
        D('House_village_user_bind')->delLiLinPfHousehold($pigcms_id,$bind_info['vacancy_id'],$this->village_id);
        if($delRet){
            $hik_info_vision_i_community_app_key=C('config.hik_info_vision_i_community_app_key');
            $hik_info_vision_i_community_app_secret=C('config.hik_info_vision_i_community_app_secret');
            if($hik_info_vision_i_community_app_key && $hik_info_vision_i_community_app_secret){
                $hikRet=invoke_v20_service('\app\community\model\service\Device\HikInfoVisionICommunityService/deleteBindUserOfHikCommunity', ['village_id'=>$bind_info['village_id'],'pigcms_id'=>$pigcms_id]);
            }
            D('House_village_face_door')->del_a3_user_bind($pigcms_id, $info['village_id'], $info);
            if (M('House_village_user_bind')->where(array('parent_id'=>$bind_info['parent_id'],'type'=>array('in','1,2,3')))->count()==0) {
                // 删除家属，查询是否还存在家属，如果没有，则删除虚拟业主
                $parent_info = M('House_village_user_bind')->where(array('pigcms_id'=>$bind_info['parent_id']))->find();
                if (!$parent_info['phone']&&!$parent_info['name']&&!$parent_info['uid']) {
                    fdump('>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                    fdump($_SERVER,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                    fdump('houseapi--'.__FUNCTION__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                    $del_info = M('House_village_user_bind')->where(array('pigcms_id'=>$bind_info['parent_id']))->select();
                    fdump($del_info,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
//                    M('House_village_user_bind')->where(array('pigcms_id'=>$bind_info['parent_id']))->delete();

                    /**
                     * todo 软删除 修改类型 usernum拼上时间戳
                     */
                    M('House_village_user_bind')->where(array('pigcms_id'=>$bind_info['parent_id']))->data(['usernum'=>time().'-'.createRandomStr(11,true),'status'=>4])->save();

                }
            }

            //todo 轨迹信息 业主删除家属信息
            $parent_user = M('house_village_user_bind')->where(['pigcms_id'=>$bind_info['parent_id']])->field('uid,pigcms_id')->find();
            $str = '业主删除'.(($bind_info['type'] == 1)?'家人':'租客').$bind_info['name'];
            D('House_user_trajectory_log')->addLog(4,$bind_info['village_id'],0,$parent_user['uid'],$parent_user['pigcms_id'],3,$str,'后台更新了人员数据');

            $this->success('删除成功');
        }else{
            fdump(D('House_village_user_bind')->_sql(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
            fdump(D('House_village_user_bind')->getDbError(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
            $this->error('删除失败');
        }
    }

    // 删除家属信息 批量删除
    public function ajax_del_bind(){
        //审核业主列表-删除 权限
        if (0&&!in_array(106, $this->house_session['menus'])) {
            exit(json_encode(array('status'=>0,'msg'=>'对不起，您没有权限执行此操作')));
        }

        $arr_pigcms_id = $_POST['arr_pigcms_id'];

        if (!$arr_pigcms_id) {
            exit(json_encode(array('status'=>0,'msg'=>'参数传递错误')));
        }
        $where['pigcms_id'] = array('in',$arr_pigcms_id);
        $where['type'] = array('in',array(1,2));
        $where['village_id'] = $this->village_id;
        $delete_list = D('House_village_user_bind')->where($where)->select();
        fdump_api(['arr_pigcms_id'=>$arr_pigcms_id,'village_id'=>$this->village_id,'delete_list'=>$delete_list],'house_user_bind/user_bind_delete_log',true);

        $res = D('House_village_user_bind')->where($where)->data(['status'=>4])->save();

        if ($delete_list) {
            foreach ($delete_list as $key => $bind_info) {
                D('House_village_face_door')->delLiLinPfHousehold($bind_info['pigcms_id'],$bind_info['vacancy_id'],$bind_info['village_id']);

                D('House_village_face_door')->del_a3_user_bind($bind_info['pigcms_id'], $bind_info['village_id'], $bind_info);
                if (M('House_village_user_bind')->where(array('parent_id'=>$bind_info['parent_id'],'type'=>array('in','1,2,3')))->count()==0) {
                    // 删除家属，查询是否还存在家属，如果没有，则删除虚拟业主
                    $parent_info = M('House_village_user_bind')->where(array('pigcms_id'=>$bind_info['parent_id']))->find();
                    if (!$parent_info['phone']&&!$parent_info['name']&&!$parent_info['uid']) {
                       // $del_info = M('House_village_user_bind')->where(array('pigcms_id'=>$bind_info['parent_id']))->select();
                        /**
                         * todo 软删除 修改类型 usernum拼上时间戳
                         */
                        //修改当前数据
                        D('House_village_user_bind')->where(array('pigcms_id'=>$bind_info['pigcms_id']))->data(['usernum'=>$bind_info['usernum'].'-'.createRandomStr(11,true),'status'=>4])->save();

                        //修改父级数据
                        $agent = D('House_village_user_bind')->where(array('pigcms_id'=>$bind_info['parent_id']))->find();
                        if($agent){
                            M('House_village_user_bind')->where(array('pigcms_id'=>$agent['pigcms_id']))->data(['usernum'=>$agent['usernum'].'-'.createRandomStr(11,true),'status'=>4])->save();
                        }
                    }
                }
                usleep (200000);
            }
            exit(json_encode(array('status'=>1,'msg'=>'删除成功')));
        }else{
            exit(json_encode(array('status'=>1,'msg'=>'删除失败')));
        }
    }

    //家属审核列表
    public function bind_audit_list(){
        //家属审核列表-查看 权限
        if (0&&!in_array(103, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $village_id = $this->village_id;
        $where['ub.village_id'] = $village_id;

        $find_type = $_GET['find_type'];
        $find_value = $_GET['find_value'];
        $find_status = $_GET['status'];

        $begin_time = $_GET['begin_time'];
        $end_time = $_GET['end_time'];

        //数据来源
        if(isset($_GET['third_type']) && $_GET['third_type']=='house'){//社区注册
            $where['third_type'] = array('eq','');
        }elseif(isset($_GET['third_type']) && $_GET['third_type']=='ezc_chinapopin'){//平安小区接口
            $where['third_type'] = array('eq',$_GET['third_type']);
        }elseif(isset($_GET['third_type']) && $_GET['third_type']=='dopu_cloudintercom'){//朵普接口
            $where['third_type'] = array('eq',$_GET['third_type']);
        }
        if ($find_value) {
            switch ($find_type) {
                case 1: // 家属姓名
                    $where['ub.name'] = array('like', '%' . str_replace(' ','',$find_value) . '%');
                    // $where['parent_id'] = array('gt',0);
                    $where['ub.type'] = array('in',array(1,2,3));
                    break;
                case 2: // 家属手机号
                    $where['ub.phone'] = array('like', '%' . $find_value . '%');
                    // $where['parent_id'] = array('gt',0);
                    $where['ub.type'] = array('in',array(1,2,3));
                    break;
                case 3: // 业主姓名
                    // 查询业主信息
                    $parent_where = $where;
                    $parent_where['ub.name'] = array('like', '%' . $find_value . '%');
                    $parent_where['ub.parent_id'] = 0;
                    $parent_where['ub.type'] = 0;
                    $parent_list = D('House_village_user_bind')->alias('ub')->where($parent_where)->field('pigcms_id')->select();
                    if ($parent_list) {
                        $parent_ids = array(); // 业主id
                        foreach ($parent_list as $key => $value) {
                            $parent_ids[] = $value['pigcms_id'];
                        }
                        $where['ub.parent_id'] = array('in' , $parent_ids);
                    } else {
                        $where['ub.parent_id'] = -1;
                    }
                    break;
                case 4: // 业主手机号
                    // 查询业主信息
                    $parent_where = $where;
                    $parent_where['ub.phone'] = array('like', '%' . $find_value . '%');
                    $parent_where['ub.parent_id'] = 0;
                    $parent_where['ub.type'] = 0;
                    $parent_list = D('House_village_user_bind')->alias('ub')->where($parent_where)->field('pigcms_id')->select();
                    if ($parent_list) {
                        $parent_ids = array(); // 业主id
                        foreach ($parent_list as $key => $value) {
                            $parent_ids[] = $value['pigcms_id'];
                        }
                        $where['ub.parent_id'] = array('in' , $parent_ids);
                    } else {
                        $where['ub.parent_id'] = -1;
                    }
                    break;
				case 6:
					$where['ub.ic_card'] = $find_value;
                    $where['ub.type'] = array('in',array(1,2,3));
                    break;
            }
        } else {
            // $where['parent_id'] = array('gt',0);
            $where['ub.type'] = array('in',array(1,3));
        }

        // 状态
        if($find_status){
            $where['ub.status'] = $find_status;
        }

        if ($begin_time && !$end_time) {
            $where['ub.add_time'] = array('gt' , strtotime($begin_time));
        }
        if (!$begin_time && $end_time) {
            $where['ub.add_time'] = array('lt' , strtotime(date('Y-m-d 23:59:59',strtotime($end_time))));
        }
        if ($begin_time && $end_time) {
            $where['ub.add_time'] = array('between' , array(strtotime($begin_time),strtotime(date('Y-m-d 23:59:59',strtotime($end_time)))));
        }
		
		
		//人脸状态
		if($_GET['face_status'] == '1'){
			$where['ub.face_img_status'] = '1';
		}else if($_GET['face_status'] == '2'){
			$where['ub.face_img_status'] = '2';
		}else if($_GET['face_status'] == '-1'){
            $where['ub.face_img_status'] = 0;
        }

        /**是否绑定微信*is_bind_weixin 1是 2否****/
        $is_bind_weixin=isset($_GET['is_bind_weixin']) ? intval($_GET['is_bind_weixin']):0;
        if($is_bind_weixin>0){
            $where['is_bind_weixin'] = $is_bind_weixin;
        }
        /**是否绑定微信*source_comefrom 1公众号 2小程序3小区自动注册4其他****/
        $source_comefrom=isset($_GET['source_comefrom']) ? intval($_GET['source_comefrom']):0;
        if($source_comefrom>0){
            $where['source_comefrom'] = $source_comefrom;
        }
        $where['ub.type'] = 1;
        $result = D('House_village_user_bind')->get_all_child_list($where,true,'ub.status desc,ub.pigcms_id desc');
        $d_house_village=D('House_village');
        $village_info = $d_house_village->field('*')->where(array('village_id'=>$village_id))->find();
        $d_house_face_img = M('House_face_img');
        $chinapopinOpen  = D('Config_customization')->getChinapopinOpen();
        if ($result['result'] && $result['result']['list']) {
            foreach ($result['result']['list'] as &$row) {
                if($chinapopinOpen && $row['third_id'] && !$row['third_type']){
                    //兼容朵普之前未记录的老字段
                    if($row['third_id']){
                        $row['third_type'] = M('House_village_third_user_info')->where([
                            'third_ryid'=>$row['third_id'],
                            'village_id'=>$village_id
                        ])->getField('third_type');
                    }
                    if($row['third_type']){
                        M('House_village_user_bind')->where(['pigcms_id'=>$row['pigcms_id']])->save(['third_type'=>$row['third_type']]);
                    }
                }
                if($chinapopinOpen){
                    $row['third_type_color'] = D('House_village_third_user_info')->getThirdTypeColor($row['third_type']);
                    $row['third_type'] = D('House_village_third_user_info')->getThirdType($row['third_type']);
                }
                if ($row['uid']>0) {
                    $row['face_num'] = $d_house_face_img->where(array('uid' => $row['uid'], 'status'=>['in',[0,3]], 'img_url' => array('neq', '')))->count();
                    if ($row['face_num'] > 0) {
                        $row['is_bind_face'] = true;
                        $is_bind_face = true;
                    } else {
                        $row['is_bind_face'] = false;
                        $is_bind_face = false;
                    }
                } else {
                    $row['is_bind_face'] = false;
                    $is_bind_face = false;
                }
                // 处理下人脸同步状态 face_img_reason
                $faceReason = '';
                if ($is_bind_face) {
                    // 同步人脸状态 0 同步中（未操作） 1 同步成功 2 同步失败  5人员同步中 6人脸同步中 7人员人脸同步中
                    if (isset($row['face_img_status'])&&$row['face_img_status']==1) {
                        $faceTxt = '已上传(同步成功)';
                    } elseif (isset($row['face_img_status'])&&in_array($row['face_img_status'],[0,5,6,7])) {
                        $faceTxt = '已上传(同步中)';
                    } elseif (isset($row['face_img_status'])&&$row['face_img_status']==2) {
                        $faceTxt = '已上传(同步失败)';
                        $faceReason = isset($row['face_img_reason'])?$row['face_img_reason']:'';
                    } else {
                        $faceTxt = '已上传';
                    }
                } else {
                    // 同步人脸状态 0 同步中（未操作） 1 同步成功 2 同步失败  5人员同步中 6人脸同步中 7人员人脸同步中
                    if (isset($row['face_img_status'])&&$row['face_img_status']==1) {
                        $faceTxt = '未上传(同步成功)';
                    } elseif (isset($row['face_img_status'])&&$row['face_img_status']==0) {
                        $faceTxt = '未上传(未操作)';
                    } elseif (isset($row['face_img_status'])&&in_array($row['face_img_status'],[5,6,7])) {
                        $faceTxt = '未上传(同步中)';
                    } elseif (isset($row['face_img_status'])&&$row['face_img_status']==2) {
                        $faceTxt = '未上传(同步失败)';
                        $faceReason = isset($row['face_img_reason'])?$row['face_img_reason']:'';
                    } else {
                        $faceTxt = '未上传';
                    }
                }
                if(isset($row['parent_phone']) && !empty($row['parent_phone'])){
                    $row['parent_phone']=$d_house_village->phone_desensitization_v($row['parent_phone'],$village_id,$village_info);
                }
                if(isset($row['parent_id_card']) && !empty($row['parent_id_card'])){
                    $row['parent_id_card']= $d_house_village->idnum_desensitization_v($row['parent_id_card'],$village_id,$village_info);
                }
                if(isset($row['phone']) && !empty($row['phone'])){
                    $row['phone']=$d_house_village->phone_desensitization_v($row['phone'],$village_id,$village_info);
                }
                if(isset($row['id_card']) && !empty($row['id_card'])){
                    $row['id_card']= $d_house_village->idnum_desensitization_v($row['id_card'],$village_id,$village_info);
                }
                $row['faceTxt'] = $faceTxt;
                $row['faceReason'] = $faceReason;
            }
        }
        if($find_type){
            $this->assign('find_type',$find_type);
        }
        if($find_value){
            $this->assign('find_value',$find_value);
        }
        if($find_status){
            $this->assign('find_status',$find_status);
        }
        $this->assign('user_list',$result['result']);

        $fingerprintJudgeConfig = D('House_face_device')->judgeFingerprintConfig($village_id);
        $this->assign('fingerprintJudgeConfig', $fingerprintJudgeConfig);
        $haveXiHanFaceDoor=0;
        $whereFace=array('village_id'=>$village_id,'device_type'=>31,'is_del'=>array('lt',4));
        $xihan_face_device=M('House_face_device')->where($whereFace)->field('device_id,device_name,device_type')->find();
        if($xihan_face_device && $xihan_face_device['device_id']){
            $haveXiHanFaceDoor=1;
        }
        $this->assign('haveXiHanFaceDoor', $haveXiHanFaceDoor);

	    //人脸识别截图尺寸
	    $this->assign('imageSize',['width'=>480,'height'=>640]);
        //是否展示数据来源
        $this->assign('chinapopinOpen',$chinapopinOpen);

        $this->display();
    }

    public function bind_audit_export(){
        //家属租客-导出 权限

        $village_id = $this->village_id;
        $where['ub.village_id'] = $village_id;

        $find_type = $_GET['find_type'];
        $find_value = $_GET['find_value'];
        $find_status = $_GET['status'];

        $begin_time = $_GET['begin_time'];
        $end_time = $_GET['end_time'];

        if ($find_value) {
            switch ($find_type) {
                case 1: // 住户姓名
                    $where['ub.name'] = array('like', '%' . str_replace(' ','',$find_value) . '%');
                    break;
                case 2: // 家属手机号
                    $where['ub.phone'] = array('like', '%' . $find_value . '%');
                    break;
                case 3: // 业主姓名
                    // 查询业主信息
                    $parent_where = $where;
                    $parent_where['ub.name'] = array('like', '%' . $find_value . '%');
                    $parent_where['ub.parent_id'] = 0;
                    $parent_where['ub.type'] = 0;
                    $parent_list = D('House_village_user_bind')->where($parent_where)->field('pigcms_id')->select();
                    if ($parent_list) {
                        $parent_ids = array(); // 业主id
                        foreach ($parent_list as $key => $value) {
                            $parent_ids[] = $value['pigcms_id'];
                        }
                        $where['ub.parent_id'] = array('in' , $parent_ids);
                    } else {
                        $where['ub.parent_id'] = -1;
                    }
                    break;
                case 4: // 业主手机号
                    // 查询业主信息
                    $parent_where = $where;
                    $parent_where['ub.phone'] = array('like', '%' . $find_value . '%');
                    $parent_where['ub.parent_id'] = 0;
                    $parent_where['ub.type'] = 0;
                    $parent_list = D('House_village_user_bind')->where($parent_where)->field('pigcms_id')->select();
                    if ($parent_list) {
                        $parent_ids = array(); // 业主id
                        foreach ($parent_list as $key => $value) {
                            $parent_ids[] = $value['pigcms_id'];
                        }
                        $where['ub.parent_id'] = array('in' , $parent_ids);
                    } else {
                        $where['ub.parent_id'] = -1;
                    }
                    break;
                case 6:
                    $where['ub.ic_card'] = $find_value;
                    $where['ub.type'] = array('in',array(1,2,3));
                    break;
            }
        } else {
            $where['ub.type'] = array('in',array(1,3));
        }

        // 状态
        if($find_status){
            $where['ub.status'] = $find_status;
        }

        if ($begin_time && !$end_time) {
            $where['ub.add_time'] = array('gt' , strtotime($begin_time));
        }
        if (!$begin_time && $end_time) {
            $where['ub.add_time'] = array('lt' , strtotime(date('Y-m-d 23:59:59',strtotime($end_time))));
        }
        if ($begin_time && $end_time) {
            $where['ub.add_time'] = array('between' , array(strtotime($begin_time),strtotime(date('Y-m-d 23:59:59',strtotime($end_time)))));
        }


        //人脸状态
        if($_GET['face_status'] == '1'){
            $where['ub.face_img_status'] = '1';
        }else if($_GET['face_status'] == '2'){
            $where['ub.face_img_status'] = '2';
        }else if($_GET['face_status'] == '-1'){
            $where['ub.face_img_status'] = 0;
        }

        if (isset($_GET['type'])) {
            $where['ub.type'] = intval($_GET['type']);
        } else {
            $where['ub.type'] = 1;
        }
        /**是否绑定微信*is_bind_weixin 1是 2否****/
        $is_bind_weixin=isset($_GET['is_bind_weixin']) ? intval($_GET['is_bind_weixin']):0;
        if($is_bind_weixin>0){
            $where['is_bind_weixin'] = $is_bind_weixin;
        }
        /**是否绑定微信*source_comefrom 1公众号 2小程序3小区自动注册4其他****/
        $source_comefrom=isset($_GET['source_comefrom']) ? intval($_GET['source_comefrom']):0;
        if($source_comefrom>0){
            $where['source_comefrom'] = $source_comefrom;
        }
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $result = D('House_village_user_bind')->get_all_child_list($where,true,'status desc,pigcms_id desc',-1);
        $user_list = $result['result']['list'];
        foreach ($user_list as &$v){
            // 查看有无车位
            if ($v['authentication_field']) {
                $v['authentication_field_arr'] = unserialize($v['authentication_field']);
            } else {
                $v['authentication_field_arr'] = [];
            }
        }
        if(count($user_list) <= 0 ){
            $this->error('无数据导出！');
        }

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';

        if (2==$where['ub.type']) {
            $title = $this->village['village_name'] . '社区-租客列表';
        } else {
            $title = $this->village['village_name'] . '社区-家属列表';
        }

        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $length = ceil(count($user_list)/1000);

        $pCoordinate_arr = [
            'A','B','C','D','E','F','G',
            'H','I','J','K','L','M','N',
            'O','P','Q','R','S','T',
            'U','V','W','X','Y','Z',
            'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM',
            'AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ',
            'BA','BB','BC','BD','BE','BF','BG','BH','BI','BJ','BK','BL','BM',
            'BN','BO','BP','BQ','BR','BS','BT','BU','BV','BW','BX','BY','BZ',
            'CA','CB','CC','CD','CE','CF','CG','CH','CI','CJ','CK','CL','CM',
            'CN','CO','CP','CQ','CR','CS','CT','CU','CV','CW','CX','CY','CZ',
            'DA','DB','DC','DD','DE','DF','DG','DH','DI','DJ','DK','DL','DM',
            'DN','DO','DP','DQ','DR','DS','DT','DU','DV','DW','DX','DY','DZ'
        ];
        $pValue_arr = [
            '业主姓名',
            '业主手机号',
            '住户姓名',
            '住户手机号',
            '与业主关系',
            '身份证号',
            'IC卡号',
            '住址',
            '物业服务开始时间',
            '物业服务结束时间',
        ];
        $pValue_arr[] = '是否绑定微信';
        $pValue_arr[] = '注册来源';
        $where_village['village_id'] = $this->village_id;
        $property_id = D('House_village')->where($where_village)->getField('property_id');
        $where_data_config = array(
            'village_id'=>0,
            'property_id'=>$property_id,
            'is_display'=>1,
            'is_close'=>0
        );
        $dataList = D('House_village_data_config')->where($where_data_config)->order('sort desc')->field('title,key')->select();
        if (!empty($dataList)) {
            foreach ($dataList as $dataKey) {
                $pValue_arr[] = $dataKey['title'];
            }
        }

        $key_length = count($pValue_arr) - 1;

        $cycle_type = array('Y'=>'year', 'M'=>'month', 'D'=>'day');
        $area_arr = M('Area')->where([])->getField('area_id,area_id,area_name',true);
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle('共' . count($user_list) . '个用户');
            $objActSheet = $objExcel->getActiveSheet();

            foreach ($pValue_arr as $k1=>$v1) {
                $objActSheet->setCellValue($pCoordinate_arr[$k1].'1', $v1);
            }

            if (!empty($user_list)) {
                $index = 2;
                $cell_list = range($pCoordinate_arr[0],$pCoordinate_arr[$key_length]);
                foreach ($cell_list as $cell) {
                    $objActSheet->getColumnDimension($cell)->setWidth(25);
                }
                $user_type = D('House_village_user_bind')->user_type;
                $relatives_type_info = D('House_village_user_bind')->relatives_type_info;
                $tmp_relatives_type_info=array();
                foreach ($relatives_type_info as $rvv){
                    $tmp_relatives_type_info[$rvv['relatives_type']]=$rvv;
                }
                $relatives_type_info=$tmp_relatives_type_info;
                foreach ($user_list as $value) {
                    $pCoordinateKey = 0;
                    $parent_name = $value['parent_name']?$value['parent_name']:'-';
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, $parent_name);
                    $pCoordinateKey = $pCoordinateKey+1;
                    $parent_phone = $value['parent_phone']?$value['parent_phone']:'-';
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, $parent_phone);
                    $pCoordinateKey = $pCoordinateKey+1;
                    $name = $value['name']?$value['name']:'-';
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, $name);
                    $pCoordinateKey = $pCoordinateKey+1;
                    $phone = $value['phone']?$value['phone']:'-';
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, $phone);
                    $pCoordinateKey = $pCoordinateKey+1;
                    if (1==$value['type'] && $value['relatives_type']) {
                        $type_name = isset($relatives_type_info[$value['relatives_type']])?$relatives_type_info[$value['relatives_type']]['name']:'家属';
                    } else {
                        $type_name = isset($user_type[$value['type']])?$user_type[$value['type']]['name']:'-';
                    }
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, $type_name);
                    $pCoordinateKey = $pCoordinateKey+1;
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, $value['id_card']?$value['id_card']:'无');
                    $pCoordinateKey = $pCoordinateKey+1;
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, $value['ic_card']?$value['ic_card']:'无');

                    $pCoordinateKey = $pCoordinateKey+1;
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, $value['address']);


                    if ($value['parent_starttime'] && intval($value['parent_starttime'])>0) {
                        $property_starttime = date('Y-m-d',$value['parent_starttime']);
                    } else {
                        $property_starttime = '无';
                    }
                    if ($value['parent_endtime'] && intval($value['parent_endtime'])>0) {
                        $property_endtime = date('Y-m-d',$value['parent_endtime']);
                    } else {
                        $property_endtime = '无';
                    }
                    $pCoordinateKey = $pCoordinateKey+1;
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, $property_starttime);
                    $pCoordinateKey = $pCoordinateKey+1;
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, $property_endtime);
                    $is_bind_weixin='';
                    if(isset($value['is_bind_weixin'])){
                        $is_bind_weixin=$value['is_bind_weixin'];
                    }
                    $pCoordinateKey = $pCoordinateKey+1;
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, $is_bind_weixin);
                    $source_comefrom='';
                    if(isset($value['source_comefrom'])){
                        $source_comefrom=$value['source_comefrom'];
                    }
                    $pCoordinateKey = $pCoordinateKey+1;
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, $source_comefrom);
                    $indexs = $pCoordinateKey;
                    if (!empty($dataList)) {
                        $ks1 = 1;
                        foreach ($dataList as $dataKey1) {
                            $index1 = $ks1+$indexs;
                            if (!empty($value['authentication_field_arr'])) {
                                if (isset($value['authentication_field_arr'][$dataKey1['key']]) && $value['authentication_field_arr'][$dataKey1['key']]['value']) {
                                    $keyVal = $value['authentication_field_arr'][$dataKey1['key']]['value'];
                                    if ('sex'==$dataKey1['key'] && !$keyVal) {
                                        $keyVal = '-';
                                    } elseif ($dataKey1['key'] == 'native_place'){
                                        $native_place = explode('#',$keyVal);
                                        $province = $native_place[0];
                                        $city = $native_place[1];
                                        if (!$province && !$city) {
                                            $keyVal = '-';
                                        } else {
                                            $keyVal = '';
                                            if ($province && $area_arr && isset($area_arr[$province])) {
                                                $keyVal .= $area_arr[$province]['area_name'] . ' ';
                                            }
                                            if ($city && $area_arr && isset($area_arr[$city])) {
                                                $keyVal .= $area_arr[$city]['area_name'] . ' ';
                                            }
                                            if (!$keyVal) {
                                                $keyVal = '未知';
                                            }
                                        }
                                    }
                                } else {
                                    $keyVal = '-';
                                }
                            } else {
                                $keyVal = '-';
                            }
                            $objActSheet->setCellValueExplicit($pCoordinate_arr[$index1] . $index, $keyVal);
                            $ks1++;
                        }
                    }
                    $index++;
                }
            }
            sleep(2);
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
            header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
            header("Content-Transfer-Encoding:binary");
            $objWriter->save('php://output');
            exit();
        }
    }

    public function bind_audit_tenant_export() {
        $_GET['type'] = 2;
        $this->bind_audit_export();
    }

    //家属审核列表
    public function bind_audit_list_tenant(){
        //家属审核列表-查看 权限
        if (0&&!in_array(103, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $village_id = $this->village_id;
        $where['ub.village_id'] = $village_id;

        $find_type = $_GET['find_type'];
        $find_value = $_GET['find_value'];
        $find_status = $_GET['status'];

        $begin_time = $_GET['begin_time'];
        $end_time = $_GET['end_time'];

        //数据来源
        if(isset($_GET['third_type']) && $_GET['third_type']=='house'){//社区注册
            $where['third_type'] = array('eq','');
        }elseif(isset($_GET['third_type']) && $_GET['third_type']=='ezc_chinapopin'){//平安小区接口
            $where['third_type'] = array('eq',$_GET['third_type']);
        }elseif(isset($_GET['third_type']) && $_GET['third_type']=='dopu_cloudintercom'){//朵普接口
            $where['third_type'] = array('eq',$_GET['third_type']);
        }
        if ($find_value) {
            switch ($find_type) {
                case 1: // 家属姓名
                    $where['ub.name'] = array('like', '%' . str_replace(' ','',$find_value) . '%');
                    // $where['parent_id'] = array('gt',0);
                    $where['ub.type'] = array('in',array(1,2,3));
                    break;
                case 2: // 家属手机号
                    $where['ub.phone'] = array('like', '%' . $find_value . '%');
                    // $where['parent_id'] = array('gt',0);
                    $where['ub.type'] = array('in',array(1,2,3));
                    break;
                case 3: // 业主姓名
                    // 查询业主信息
                    $parent_where = $where;
                    $parent_where['ub.name'] = array('like', '%' . $find_value . '%');
                    $parent_where['ub.parent_id'] = 0;
                    $parent_where['ub.type'] = 0;
                    $parent_list = D('House_village_user_bind')->alias('ub')->where($parent_where)->field('pigcms_id')->select();
                    if ($parent_list) {
                        $parent_ids = array(); // 业主id
                        foreach ($parent_list as $key => $value) {
                            $parent_ids[] = $value['pigcms_id'];
                        }
                        $where['ub.parent_id'] = array('in' , $parent_ids);
                    } else {
                        $where['ub.parent_id'] = -1;
                    }
                    break;
                case 4: // 业主手机号
                    // 查询业主信息
                    $parent_where = $where;
                    $parent_where['ub.phone'] = array('like', '%' . $find_value . '%');
                    $parent_where['ub.parent_id'] = 0;
                    $parent_where['ub.type'] = 0;
                    $parent_list = D('House_village_user_bind')->alias('ub')->where($parent_where)->field('pigcms_id')->select();
                    if ($parent_list) {
                        $parent_ids = array(); // 业主id
                        foreach ($parent_list as $key => $value) {
                            $parent_ids[] = $value['pigcms_id'];
                        }
                        $where['ub.parent_id'] = array('in' , $parent_ids);
                    } else {
                        $where['ub.parent_id'] = -1;
                    }
                    break;
				case 6:
					$where['ub.ic_card'] = $find_value;
                    $where['ub.type'] = array('in',array(1,2,3));
                    break;
            }
            $where['ub.type'] = 2;
        } else {
            // $where['parent_id'] = array('gt',0);
            $where['ub.type'] = 2;
        }

        // 状态
        if($find_status){
            $where['ub.status'] = $find_status;
        }

        if ($begin_time && !$end_time) {
            $where['ub.add_time'] = array('gt' , strtotime($begin_time));
        }
        if (!$begin_time && $end_time) {
            $where['ub.add_time'] = array('lt' , strtotime(date('Y-m-d 23:59:59',strtotime($end_time))));
        }
        if ($begin_time && $end_time) {
            $where['ub.add_time'] = array('between' , array(strtotime($begin_time),strtotime(date('Y-m-d 23:59:59',strtotime($end_time)))));
        }
		
		//人脸状态
		if($_GET['face_status'] == '1'){
			$where['ub.face_img_status'] = '1';
		}else if($_GET['face_status'] == '2'){
			$where['ub.face_img_status'] = '2';
		}else if($_GET['face_status'] == '-1'){
            $where['ub.face_img_status'] = 0;
        }
        /**是否绑定微信*is_bind_weixin 1是 2否****/
        $is_bind_weixin=isset($_GET['is_bind_weixin']) ? intval($_GET['is_bind_weixin']):0;
        if($is_bind_weixin>0){
            $where['is_bind_weixin'] = $is_bind_weixin;
        }
        /**是否绑定微信*source_comefrom 1公众号 2小程序3小区自动注册4其他****/
        $source_comefrom=isset($_GET['source_comefrom']) ? intval($_GET['source_comefrom']):0;
        if($source_comefrom>0){
            $where['source_comefrom'] = $source_comefrom;
        }
        $result = D('House_village_user_bind')->get_all_child_list($where,true,'ub.status desc,ub.pigcms_id desc');
        $d_house_village=D('House_village');
        $village_info = $d_house_village->field('*')->where(array('village_id'=>$village_id))->find();
        $d_house_face_img = M('House_face_img');
        $chinapopinOpen  = D('Config_customization')->getChinapopinOpen();
        if ($result['result'] && $result['result']['list']) {
            foreach ($result['result']['list'] as &$row) {
                if($chinapopinOpen && $row['third_id'] && !$row['third_type']){
                    //兼容朵普之前未记录的老字段
                    if($row['third_id']){
                        $row['third_type'] = M('House_village_third_user_info')->where([
                            'third_ryid'=>$row['third_id'],
                            'village_id'=>$village_id
                        ])->getField('third_type');
                    }
                    if($row['third_type']){
                        M('House_village_user_bind')->where(['pigcms_id'=>$row['pigcms_id']])->save(['third_type'=>$row['third_type']]);
                    }
                }
                if($chinapopinOpen){
                    $row['third_type_color'] = D('House_village_third_user_info')->getThirdTypeColor($row['third_type']);
                    $row['third_type'] = D('House_village_third_user_info')->getThirdType($row['third_type']);
                }
                if ($row['uid']>0) {
                    $row['face_num'] = $d_house_face_img->where(array('uid' => $row['uid'], 'status'=>['in',[0,3]], 'img_url' => array('neq', '')))->count();
                    if ($row['face_num'] > 0) {
                        $row['is_bind_face'] = true;
                        $is_bind_face = true;
                    } else {
                        $row['is_bind_face'] = false;
                        $is_bind_face = false;
                    }
                } else {
                    $row['is_bind_face'] = false;
                    $is_bind_face = false;
                }
                // 处理下人脸同步状态 face_img_reason
                $faceReason = '';
                if ($is_bind_face) {
                    // 同步人脸状态 0 同步中（未操作） 1 同步成功 2 同步失败  5人员同步中 6人脸同步中 7人员人脸同步中
                    if (isset($row['face_img_status'])&&$row['face_img_status']==1) {
                        $faceTxt = '已上传(同步成功)';
                    } elseif (isset($row['face_img_status'])&&in_array($row['face_img_status'],[0,5,6,7])) {
                        $faceTxt = '已上传(同步中)';
                    } elseif (isset($row['face_img_status'])&&$row['face_img_status']==2) {
                        $faceTxt = '已上传(同步失败)';
                        $faceReason = isset($row['face_img_reason'])?$row['face_img_reason']:'';
                    } else {
                        $faceTxt = '已上传';
                    }
                } else {
                    // 同步人脸状态 0 同步中（未操作） 1 同步成功 2 同步失败  5人员同步中 6人脸同步中 7人员人脸同步中
                    if (isset($row['face_img_status'])&&$row['face_img_status']==1) {
                        $faceTxt = '未上传(同步成功)';
                    } elseif (isset($row['face_img_status'])&&$row['face_img_status']==0) {
                        $faceTxt = '未上传(未操作)';
                    } elseif (isset($row['face_img_status'])&&in_array($row['face_img_status'],[5,6,7])) {
                        $faceTxt = '未上传(同步中)';
                    } elseif (isset($row['face_img_status'])&&$row['face_img_status']==2) {
                        $faceTxt = '未上传(同步失败)';
                        $faceReason = isset($row['face_img_reason'])?$row['face_img_reason']:'';
                    } else {
                        $faceTxt = '未上传';
                    }
                }
                $row['faceTxt'] = $faceTxt;
                $row['faceReason'] = $faceReason;
                if(isset($row['phone']) && !empty($row['phone'])){
                    $row['phone']=$d_house_village->phone_desensitization_v($row['phone'],$this->village_id,$village_info);
                }
                if(isset($row['id_card']) && !empty($row['id_card'])){
                    $row['id_card']= $d_house_village->idnum_desensitization_v($row['id_card'],$this->village_id,$village_info);
                }
                if(isset($row['parent_phone']) && !empty($row['parent_phone'])){
                    $row['parent_phone']=$d_house_village->phone_desensitization_v($row['parent_phone'],$this->village_id,$village_info);
                }
                if(isset($row['parent_id_card']) && !empty($row['parent_id_card'])){
                    $row['parent_id_card']= $d_house_village->idnum_desensitization_v($row['parent_id_card'],$this->village_id,$village_info);
                }
            }
        }

        if($find_type){
            $this->assign('find_type',$find_type);
        }
        if($find_value){
            $this->assign('find_value',$find_value);
        }
        if($find_status){
            $this->assign('find_status',$find_status);
        }
        $this->assign('user_list',$result['result']);
        $fingerprintJudgeConfig = D('House_face_device')->judgeFingerprintConfig($village_id);
        $this->assign('fingerprintJudgeConfig', $fingerprintJudgeConfig);
        $haveXiHanFaceDoor=0;
        $whereFace=array('village_id'=>$village_id,'device_type'=>31,'is_del'=>array('lt',4));
        $xihan_face_device=M('House_face_device')->where($whereFace)->field('device_id,device_name,device_type')->find();
        if($xihan_face_device && $xihan_face_device['device_id']){
            $haveXiHanFaceDoor=1;
        }
        $this->assign('haveXiHanFaceDoor', $haveXiHanFaceDoor);
	    //人脸识别截图尺寸
	    $this->assign('imageSize',['width'=>480,'height'=>640]);
        //是否展示数据来源
        $this->assign('chinapopinOpen',$chinapopinOpen);

	    $this->display();
    }


    public function test(){
        require_once APP_PATH . 'Lib/ORG/plan_house_notice.class.php';
        $objExcel = new plan_house_notice();
        $objExcel->runTask();exit();
    }



    public function payment_list(){
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title = '模板-业主每月账单明细';
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $objExcel->setActiveSheetIndex(0);
        $objActSheet = $objExcel->getActiveSheet();
        $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
        $objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(40);
        $objExcel->getActiveSheet()->getColumnDimension('H')->setWidth(40);


        $objActSheet->setCellValue('A1', '业主编号');
        $objActSheet->setCellValue('B1', '业主名');
        $objActSheet->setCellValue('C1', '手机号');
        $objActSheet->setCellValue('D1', '用水(立方米)');
        $objActSheet->setCellValue('E1', '用电(千瓦时[度])');
        $objActSheet->setCellValue('F1', '燃气(立方米)');
        // $objActSheet->setCellValue('G1', '是否需要缴物业费（1需要，0不需要）');
        $objActSheet->setCellValue('G1', '是否需要缴停车费（1需要，0不需要）');

        //筛选数据
        $where['status'] = 1;
        $where['village_id'] = $this->village_id;
        $where['parent_id'] = 0;
        $where['type'] =0 ;
        $bind_user = M('House_village_user_bind')->field('usernum,name,phone')->where($where)->select();
        $index = 2;
        foreach ($bind_user as $value) {
            $objActSheet->setCellValueExplicit('A' . $index, $value['usernum']);
            $objActSheet->setCellValueExplicit('B' . $index, $value['name']);
            $objActSheet->setCellValueExplicit('C' . $index, $value['phone']);
            $objActSheet->setCellValueExplicit('D' . $index, 0);
            $objActSheet->setCellValueExplicit('E' . $index,0);
            $objActSheet->setCellValueExplicit('F' . $index, 0);
            $objActSheet->setCellValueExplicit('G' . $index, 0);
            // $objActSheet->setCellValueExplicit('H' . $index,0);
            $index++;
        }

        if (!$bind_user) {
            $objActSheet->setCellValueExplicit('A' . $index, '');
            $objActSheet->setCellValueExplicit('B' . $index, '');
            $objActSheet->setCellValueExplicit('C' . $index, '');
            $objActSheet->setCellValueExplicit('D' . $index, '');
            $objActSheet->setCellValueExplicit('E' . $index,'');
            $objActSheet->setCellValueExplicit('F' . $index, '');
            $objActSheet->setCellValueExplicit('G' . $index, '');
            // $objActSheet->setCellValueExplicit('H' . $index,'');
        }

        $objWriter = new PHPExcel_Writer_Excel5($objExcel);
        ob_end_clean();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();
    }

    // 审核业主列表
    public function audit_index(){
        //审核业主列表-查看 权限
        if (0&&!in_array(101, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $find_type = $_GET['find_type'];
        $find_value = $_GET['find_value'];
        $find_status = $_GET['status'];
        if($find_status){
            $find_status_arr=[
                1=>1,
                2=>2,
                3=>0,
                4=>10
            ];
            if(isset($find_status_arr[$find_status])){
                $status=$find_status_arr[$find_status];
                $where['status'] = $status;
                if(in_array($status,[0,10])){
                    if($status == 0){ //未通过
                        $where['refuse_time'] = array('gt', 0);
                        unset($where['status']);
                    }else{ //替换业主
                        $where['refuse_time'] = 0;
                        $where['status']=0;
                    }
                }
            }else{
                $where['status'] = array('in',array(2,0)); // 查询待审核和审核拒绝用户
            }
        }else{
            $where['status'] = array('in',array(2,0)); // 查询待审核和审核拒绝用户
//            $where['_sting'] = " and (status = 0 OR status=2)"; // 排除空置房间
//            $where['reason'] = array('neq','');
        }
        //数据来源
        if(isset($_GET['third_type']) && $_GET['third_type']=='house'){//社区注册
            $where['third_type'] = array('eq','');
        }elseif(isset($_GET['third_type']) && $_GET['third_type']=='ezc_chinapopin'){//平安小区接口
            $where['third_type'] = array('eq',$_GET['third_type']);
        }elseif(isset($_GET['third_type']) && $_GET['third_type']=='dopu_cloudintercom'){//朵普接口
            $where['third_type'] = array('eq',$_GET['third_type']);
        }
        if ($find_value) {
            if ($find_type == 1) {
                $where['_string'] = "usernum LIKE '%{$find_value}%' OR bind_number LIKE '%{$find_value}%'";
            } else if ($find_type == 2) {
                $where['name'] = array('like', '%' . str_replace(' ','',$find_value) . '%');
            } else if ($find_type == 3) {
                $where['phone'] = array('like', '%' . $find_value . '%');
            }
        }

        $village_id = $this->village_id;
        $database_house_village_user_bind = D('House_village_user_bind');
        $where['village_id'] = $village_id;

        $where['is_del'] = 0;
        if(!$where['phone']){
            //$where['phone'] = array('neq' , "");
        }

        $begin_time = $_GET['begin_time'];
        $end_time = $_GET['end_time'];
        if ($begin_time && !$end_time) {
            $where['add_time'] = array('gt' , strtotime($begin_time));
        }
        if (!$begin_time && $end_time) {
            $where['add_time'] = array('lt' , strtotime(date('Y-m-d 23:59:59',strtotime($end_time))));
        }
        if ($begin_time && $end_time) {
            $where['add_time'] = array('between' , array(strtotime($begin_time),strtotime(date('Y-m-d 23:59:59',strtotime($end_time)))));
        }
        $where['type'] = array('in','0,3');
        $result = $database_house_village_user_bind->get_all_child_list($where);
        
        $ownerList = isset($result['result']['list']) && $result['result']['list'] ? $result['result']['list'] : [];
        $vacancyIds = array_unique(array_column($ownerList, 'vacancy_id'));
        $whereVacancy = [
            'village_id' => ['in', $village_id], 'vacancy_id' => ['in', $vacancyIds], 'type' => ['in', [0, 3]], 'status' => 1,
        ];
        $vacancyOwnerArr = $database_house_village_user_bind->where($whereVacancy)->getField('vacancy_id, pigcms_id, village_id, name, phone', true);
        
        foreach ($result['result']['list'] as &$resultItem) {
            $vacancy_id = isset($resultItem['vacancy_id']) ? intval($resultItem['vacancy_id']) : 0;
            $vacancyOwner = isset($vacancyOwnerArr[$vacancy_id]) ? $vacancyOwnerArr[$vacancy_id] : [];
            $ownerText = '';
            if (!empty($vacancyOwner)) {
                $name = isset($vacancyOwner['name']) ? trim($vacancyOwner['name']) : '';
                $phone = isset($vacancyOwner['phone']) ? trim($vacancyOwner['phone']) : '';
                $name && $ownerText = "已有业主【姓名：{$name}】";
                !$name && $phone && $ownerText = "已有业主【手机号：{$phone}】";
                $name && $phone && $ownerText = "已有业主【姓名：{$name}；手机号：{$phone}】";
            }
            $resultItem['ownerText'] = $ownerText;
        }
        
        fdump_api(['where' => $where, 'vacancyOwnerArr' => $vacancyOwnerArr, 'list' => $result['result']['list']], '$get_all_child_list');

        if(!$result){
            $this->error('数据处理有误！');
        }
        $d_house_village=D('House_village');
        $village_info = $d_house_village->field('*')->where(array('village_id'=>$village_id))->find();

        $chinapopinOpen  = D('Config_customization')->getChinapopinOpen();
        if ($result['result'] && $result['result']['list']) {
            foreach ($result['result']['list'] as &$row) {
                if($chinapopinOpen && $row['third_id'] && !$row['third_type']){
                    //兼容朵普之前未记录的老字段
                    if($row['third_id']){
                        $row['third_type'] = M('House_village_third_user_info')->where([
                            'third_ryid'=>$row['third_id'],
                            'village_id'=>$village_id
                        ])->getField('third_type');
                    }
                    if($row['third_type']){
                        M('House_village_user_bind')->where(['pigcms_id'=>$row['pigcms_id']])->save(['third_type'=>$row['third_type']]);
                    }
                }
                if($chinapopinOpen){
                    $row['third_type_color'] = D('House_village_third_user_info')->getThirdTypeColor($row['third_type']);
                    $row['third_type'] = D('House_village_third_user_info')->getThirdType($row['third_type']);
                }
                
                if(isset($row['phone']) && !empty($row['phone'])){
                    $row['phone']=$d_house_village->phone_desensitization_v($row['phone'],$this->village_id,$village_info);
                }
                if(isset($row['id_card']) && !empty($row['id_card'])){
                    $row['id_card']= $d_house_village->idnum_desensitization_v($row['id_card'],$this->village_id,$village_info);
                }
                if(isset($row['parent_phone']) && !empty($row['parent_phone'])){
                    $row['parent_phone']=$d_house_village->phone_desensitization_v($row['parent_phone'],$this->village_id,$village_info);
                }
                if(isset($row['parent_id_card']) && !empty($row['parent_id_card'])){
                    $row['parent_id_card']= $d_house_village->idnum_desensitization_v($row['parent_id_card'],$this->village_id,$village_info);
                }
            }
        }
        if($find_type){
            $this->assign('find_type',$find_type);
        }
        if($find_value){
            $this->assign('find_value',$find_value);
        }
        if($find_status){
            $this->assign('find_status',$find_status);
        }
        $this->assign('user_list',$result['result']);
        //是否展示数据来源
        $this->assign('chinapopinOpen',$chinapopinOpen);
        $this->display();
    }

    // 审核业主列表 删除
    public function ajax_del_audit(){
        //审核业主列表-查看 权限
        if (0&&!in_array(268, $this->house_session['menus'])) {
            exit(json_encode(array('status'=>0,'msg'=>'对不起，您没有权限执行此操作')));
        }

        $arr_pigcms_id = $_POST['arr_pigcms_id'];

        if (!$arr_pigcms_id) {
            exit(json_encode(array('status'=>0,'msg'=>'参数传递错误')));
        }
        $bind_condition['pigcms_id'] = array('in',$arr_pigcms_id);
        $bind_condition['village_id'] = $this->village_id;
        $bind_condition['is_del'] = 0;
        $database_house_village_user_vacancy = D('House_village_user_vacancy');
        $database_house_village_user_bind = D('House_village_user_bind');
        $del_list = $database_house_village_user_vacancy->where($bind_condition)->select();

        if (!$del_list) {
            exit(json_encode(array('status'=>0,'msg'=>'审核信息不存在')));
        }

        $data['name'] = '';
        $data['phone'] = '';
        $data['uid'] = 0;
        $data['status'] = 1;
        foreach ($del_list as $key => $value) {
            if ($value['status'] != 0) { // 审核不通过的才可以删除
                continue;
            }

            //更新房屋信息
            $modify = $database_house_village_user_vacancy->where($bind_condition)->data($data)->save();

            //删除绑定业主信息
            if ($modify) {
                $where = array(
                    'village_id'=>$this->village_id,
                    'vacancy_id'=>$value['pigcms_id'],
                    'type'=>0,
                );
                $user_bind = $database_house_village_user_bind->where($where)->select();
                if ($user_bind) { //存在业主信息
                    // 查询有无家属信息
                    $bind_info = $database_house_village_user_bind->where(array('type'=>array('in',array(1,2)),'vacancy_id'=>$value['pigcms_id']))->count();
                    if ($bind_info) { // 更新为虚拟房主
                        $edit_data = array();
                        $edit_data['uid'] = 0;
                        $edit_data['name'] = '';
                        $edit_data['phone'] = '';
                        $edit_data['status'] = 1;
                        $save_bind = $database_house_village_user_bind->where($where)->data($edit_data)->save();
                    }else{
                        // 删除房主信息
                        $delete_list = D('House_village_user_bind')->where($where)->select();
                        fdump('>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                        fdump($_SERVER,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                        fdump('houseapi--'.__FUNCTION__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                        fdump($delete_list,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
//                        $save_bind = $database_house_village_user_bind->where($where)->delete();

                        /**
                         * todo 软删除 修改类型 usernum拼上时间戳
                         */
                        $database_house_village_user_bind->where($where)->data(['usernum'=>time().'-'.createRandomStr(11,true),'status'=>4])->save();

                        fdump($database_house_village_user_bind->_sql(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                        fdump($database_house_village_user_bind->getDbError(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                    }
                }
            }
        }
        if ($modify) {
            exit(json_encode(array('status'=>1,'msg'=>'删除成功')));
        }else{
            exit(json_encode(array('status'=>1,'msg'=>'删除失败')));
        }
    }

    // 审核业主列表 删除
    public function audit_delete(){
        //审核业主列表-删除 权限
        if (0&&!in_array(268, $this->house_session['menus']) && !in_array(94, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $pigcms_id = $_GET['pigcms_id'] + 0;

        if (!$pigcms_id) {
            $this->error('参数传递错误');
        }

        $bind_condition['pigcms_id'] = $pigcms_id;
        $bind_condition['village_id'] = $this->village_id;
        $database_house_village_user_bind = D('House_village_user_bind');
        $info = $database_house_village_user_bind->where($bind_condition)->find();

        if (!$info) {
            $this->error('审核信息不存在');
        }

        if ($info['status']==1) {
            $this->error('该信息不能删除');
        }
//        $del = $database_house_village_user_bind->where($bind_condition)->delete();

        /**
         * todo 软删除 修改类型 usernum拼上时间戳
         */
        $nowTime = time();
        $del = $database_house_village_user_bind->where($bind_condition)->data(['usernum'=>$info['usernum'].'_'.$pigcms_id.'del'.$nowTime,'status'=>4])->save();

        if ($del) {
            if ($info['vacancy_id']) {
                $where = array(
                    'village_id'=>$this->village_id,
                    'vacancy_id'=>$info['vacancy_id'],
                    'type'=>['in', [0,3]],
                    'status'=>['in', [1,2]],
                );
                $user_bind = D('House_village_user_bind')->where($where)->select();
                if (!$user_bind) {
                    // 房屋下没有业主  清空房屋数据更改状态
                    $data = [];
                    $data['name'] = '';
                    $data['phone'] = '';
                    $data['uid'] = 0;
                    $data['status'] = 1;
                    $bind_vacancy = [
                        'village_id'=>$this->village_id,
                        'pigcms_id'=>$info['vacancy_id'],
                    ];
                    $database_house_village_user_vacancy = D('House_village_user_vacancy');
                    $database_house_village_user_vacancy->where($bind_vacancy)->data($data)->save();
                }

                // 删除  同步进行删除
                try {
                    D('House_village_face_door')->del_a3_user_bind($pigcms_id, $info['village_id'],$info);
                }catch (Exception $e) {
                    fdump_api(['删除时同步设备出错'.__LINE__,$pigcms_id,$info,$e->getMessage()],'house_user_bind/del_a3_user_bind_log_err',1);
                }
                
            }
            fdump_api([$_POST, $info, $info],'house_user_bind/user_bind_delete_log',1);

            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }

    // 审核业主列表 编辑
    public function audit_edit(){
        //审核业主列表-查看 权限
        if (0&&!in_array(101, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $pigcms_id = $_GET['pigcms_id'] + 0;
        $usernum = $_GET['usernum'];

        $database_house_village_user_bind = D('House_village_user_bind');
        $database_house_village_user_vacancy = D('House_village_user_vacancy');
        $info = $database_house_village_user_bind->get_one_by_bindId($pigcms_id);

        $bind_condition['pigcms_id'] = $info['vacancy_id'];
        if(!$info){
            $this->error('数据处理有误！');
        }
        $chinapopinOpen  = D('Config_customization')->getChinapopinOpen();
        if(IS_POST) {
            $room_new = [];
            if($chinapopinOpen && ($info['single_id'] != $_POST['single_id'] || $info['layer_id'] != $_POST['layer_id']
                || $info['floor_id'] != $_POST['floor_id'] || $info['vacancy_id'] != $_POST['vacancy_id'])){//楼栋单元楼层房间重置
                $bind_condition['pigcms_id'] = $_POST['vacancy_id'];
                $info['single_id'] = $_POST['single_id'];
                $info['layer_id'] = $_POST['layer_id'];
                $info['floor_id'] = $_POST['floor_id'];
                $info['vacancy_id'] = $_POST['vacancy_id'];
                $info['housesize'] = 0;
                $info['address'] = '';
                //检查楼栋单元楼层房间信息
                $vacancy_info = M('House_village_user_vacancy')->where([
                    'village_id'=>$info['village_id'],
                    'single_id'=>$info['single_id'],
                    'layer_id'=>$info['layer_id'],
                    'floor_id'=>$info['floor_id'],
                    'pigcms_id'=>$info['vacancy_id']
                ])->field('housesize,room')->find();
                if(!$vacancy_info){
                    $this->error('住址选择有误，请重新选择！');
                }
                $info['housesize'] = $vacancy_info['housesize'];
                $sing_name = M('House_village_single')->where(['id'=>$info['single_id']])->getField('single_name');
                $floor_name = M('House_village_floor')->where(['floor_id'=>$info['floor_id']])->getField('floor_name');
                $word_data['floor_name'] = $floor_name;
                $word_data['layer'] = $vacancy_info['layer'];
                $word_data['room'] = $vacancy_info['room'];
                $word_data['single_name'] = $sing_name;
                $info['address'] = D('House_village')->word_replce_msg($word_data,$this->village_id);
                $room_new = [
                    'single_id'=>$_POST['single_id'],
                    'layer_id'=>$_POST['layer_id'],
                    'floor_id'=>$_POST['floor_id'],
                    'vacancy_id'=>$_POST['vacancy_id'],
                    'housesize'=>$vacancy_info['housesize'],
                    'address'=>$info['address']
                ];
            }
            $type_new = $_POST['type_new'] ?? $_POST['type'];
            if($chinapopinOpen && in_array($type_new,[1,2]) && $_POST['status'] == 1){//家属,租户。同意申请
                //查询房屋业主信息
                $parent = $database_house_village_user_bind->where([
                    'status'=>1,
                    'type'=>['in',[0,3]],
                    'vacancy_id'=>$info['vacancy_id']
                ])->field('pigcms_id')->find();
                $type_update = [
                    'type'=>$type_new,
                    'name'=>$_POST['user_name'],//姓名
                    'phone'=>$_POST['phone'],//联系方式
                ];
                if($parent && $parent['pigcms_id']){
                    $type_update['parent_id'] = $parent['pigcms_id'];
                }
                if($type_new == 1 && $_POST['relatives_type']){
                    $type_update['relatives_type'] = $_POST['relatives_type'];
                }
                $type_update = array_merge($room_new,$type_update);
                //修改用户类型以及用户名称、手机号、房屋面积、上级业主id
                $database_house_village_user_bind->where(['pigcms_id'=>$pigcms_id])->data($type_update)->save();

                $vacancy = $database_house_village_user_vacancy->where($bind_condition)->field('housesize')->find();
                if (floatval($_POST['housesize'])>0 && $vacancy && floatval($_POST['housesize']) != floatval($vacancy['housesize'])) {
                    //修改房屋面积
                    $data_room['housesize'] = floatval($_POST['housesize']);
                    $database_house_village_user_vacancy->where($bind_condition)->data(['housesize'=>$_POST['housesize']])->save();
                    //修改房间用户的房屋面积
                    $database_house_village_user_bind->where(['vacancy_id'=>$info['vacancy_id']])->data(['housesize'=>floatval($_POST['housesize'])])->save();
                }
                $_GET['redirect_url'] = U('audit_index');
                if($_SERVER['original_post']['referer']){
                    $_GET['redirect_url'] =$_SERVER['original_post']['referer'];
                }
                $this->bind_edit();
                die;
            }
            //审核业主列表-编辑 权限
            if (0&&!in_array(102, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }

            if(empty($_POST['usernum'])){
                $this->error('业主编号不能为空！');
            }

            if(empty($_POST['user_name'])){
                $this->error('业主名不能为空！');
            }

            /*if(empty($_POST['phone'])){
                $this->error('手机号不能为空！');
            }*/
            if (!empty(trim($_POST['phone']))) {
                $phone = trim($_POST['phone']);
            } else {
                $phone = '';
            }
            if($phone && !$pigcms_id){
                if($info['uid']){
                    $uInfo = D('User')->where(['uid'=>$info['uid']])->field('phone')->find();
                    if(empty($uInfo['phone'])){
                        D('User')->where(['uid'=>$info['uid']])->save(['phone'=>$phone]);
                    }
                }
            }
            $status = $_POST['status'];
            if (-1==$status || '-1'==$status) {
                $status = 0;
            } elseif ($status!=1 && $status!=-1 && $status!='1' && $status!='-1') {
                $this->error('请选择审核状态！');
            }

            $data['usernum'] = $_POST['usernum'];
            $database_house_village_floor = D('House_village_floor');
            $database_house_village_user_bind = D('House_village_user_bind');
            $database_user = D('User');


            if (intval($_POST['floor_id'])>0) {
                $where['floor_id'] = intval($_POST['floor_id']);
//            $where['status'] = $status;
                $where['village_id'] = $this->village_id;
                $house_village_floor_info = $database_house_village_floor->where($where)->find();
                if (!isset($house_village_floor_info)) {
                    $this->error('单元不存在，请查看社区中心，单元管理-单元列表！');
                }
            }
            fdump_api(['单个编辑业主'.__LINE__,$_GET,$_POST,$status,$this->house_session],'house/user/audit_edit_log',1);

            if ($phone) {
                // 查询状态正常的平台用户
                $where_user = [
                    'phone' => $phone,
                    'status' => 1
                ];
                $now_user = $database_user->where($where_user)->find();
            } elseif ($info['uid']>0 && !$info['phone']) {
                // 原来存在uid 且手机号也是空的  否则需要空置uid
                $now_user = $database_user->get_user($info['uid'], 'uid');
            } else {
                $now_user = [];
            }
            if($status == 1){
                // if ($database_house_village_user_bind->field('`usernum`')->where($Map)->find()) {
                // $this->error('业主名为已存在！物业编号重复。');
                // }
                if ($now_user) {
                    $data['uid'] = $now_user['uid'];
                }
                if (trim($_POST['layer_num'])) {
                    $layer_num = trim($_POST['layer_num']);
                    $data['layer_num'] = $layer_num;
                }
                if (trim($_POST['room_num'])) {
                    $room_num = trim($_POST['room_num']);
                    $data['room_addrss'] =  $room_num;
                }
                if (trim($_POST['user_name'])) {
                    $data['name'] = trim($_POST['user_name']);
                }
                if ($phone) {
                    $data['phone'] = $phone;
                }
                if (intval($_POST['floor_id'])>0) {
                    $data['floor_id'] = intval($_POST['floor_id']);
                }

                // 同一个房间下不能存在多个相同手机号
                if ($data['phone']) {
                    $bind_condition2['vacancy_id'] = $info['vacancy_id'];
                    $bind_condition2['pigcms_id'] = ['neq', $info['pigcms_id']];
                    $bind_condition2['phone'] = $data['phone'];
                    $bind_condition2['type'] = array('in' , '0,1,2,3');
                    $bind_condition2['status'] = array('in' , '1,2');
                    $family_bind_info = $database_house_village_user_bind->where($bind_condition2)->find();
                    if($family_bind_info){
                        $this->error('该手机号已绑定或已申请绑定此房间');
                    }
                }

                if($memo = htmlspecialchars(trim($_POST['memo']))){
                    $data['memo'] = $memo;
                }
                if (trim($_POST['address'])) {
                    $data['address'] = $_POST['address'];
                }
                $data['village_id'] = $this->village_id;
                // 传过来的面积不同就改值
                // 房屋面积处理
                if (isset($_POST['housesize']) && floatval($_POST['housesize'])>0) {
                    $housesize = trim($_POST['housesize']);
                    if (floatval($housesize) != floatval($info['housesize'])) {
                        $data['housesize'] = $housesize;
                    } else {
                        $data['housesize'] = $info['housesize'];
                    }
                }
                $data['park_flag'] = $info['park_flag'];
                $data['add_time'] = time();
                $data['vacancy_id'] = $info['vacancy_id'];
                $data['type'] = $info['type'];
                $data['attribute'] = $info['attribute'];
                $data['parent_id'] = 0;

                if($info){
                    $data['status'] = 1;
                    $data['adopt_time'] = time();
                    if ($info['single_id']) {
                        $data['single_id'] = $info['single_id'];
                    }
                    if ($info['layer_id']) {
                        $data['layer_id'] = $info['layer_id'];
                    }
                    // 如果是业主  将原来所有与房屋相同物业编号改为 编号+'-y' 然后当前审核通过的业主改为
                    $roomMsg = $database_house_village_user_vacancy->where($bind_condition)->field('usernum,housesize')->find();
                    $room_usernum = $roomMsg['usernum'];
                    if (!$data['housesize'] && floatval($roomMsg['housesize'])>0) {
                        $data['housesize'] = $roomMsg['housesize'];
                        $housesize = $roomMsg['housesize'];
                    }
                    // 查询下是否存在虚拟业主  存在直接替换 然后删除现在的申请数据
                    if (0==$info['type'] || 3==$info['type']) {
                        $where_virtual = [
                            'vacancy_id' => $info['vacancy_id'],
                            'usernum' => $room_usernum,
                            'pigcms_id' => ['neq', $pigcms_id],
                            'type' => ['in',[0,3]],
                            'status' => 1,
                        ];
                        $virtual_owner = $database_house_village_user_bind->where($where_virtual)->find();
                        if ($virtual_owner && $virtual_owner['uid']==0 && !$virtual_owner['name'] && !$virtual_owner['phone']) {
                            fdump('虚拟业主>>>'.__LINE__,'api/log/'.date('Ymd').'/house/virtual_owner_log',true);
                            fdump($virtual_owner,'api/log/'.date('Ymd').'/house/virtual_owner_log',true);
                            // 存在虚拟业主
                            // 记录原来用户id
                            $y_pigcms_id = $pigcms_id;
                            $pigcms_id = $virtual_owner['pigcms_id'];
                            $data['add_time'] = $info['add_time'];
                            $data['id_card'] = $info['id_card'];
                            $data['floor_id'] = $info['floor_id'];
                            $data['address'] = $info['address'] ? $info['address'] : '';
                            $data['authentication_field'] = $info['authentication_field'] ? $info['authentication_field'] : '';
                        }
                        if ($room_usernum) {
                            // 直接将原有的物业编号给改为其他物业编号
                            $where_other = [
                                'usernum' => $room_usernum,
                                'pigcms_id' => ['neq', $pigcms_id]
                            ];
                            $set = [
                                'usernum' => $room_usernum.'-y'.time()
                            ];
                            $a = $database_house_village_user_bind->where($where_other)->save($set);
                            fdump('>>>'.__LINE__,'api/log/'.date('Ymd').'/house/audit_edit_log',true);
                            fdump($where_other,'api/log/'.date('Ymd').'/house/audit_edit_log',true);
                            fdump($room_usernum.'-y','api/log/'.date('Ymd').'/house/audit_edit_log',true);
                            if ($a!==false) {
                                $data['usernum'] = $room_usernum;
                                // 同样其他业主禁用
                                $where_user_bind = [
                                    'vacancy_id' => $info['vacancy_id'],
                                    'type' => ['in', [0,3]],
                                    'pigcms_id' => ['neq', $pigcms_id],
                                    'status' => 1
                                ];
                                $b = $database_house_village_user_bind->where($where_user_bind)->data(['status'=>0])->save();
                            } else {
                                fdump('>>>'.__LINE__,'api/log/'.date('Ymd').'/house/audit_edit_err_log',true);
                                fdump($a,'api/log/'.date('Ymd').'/house/audit_edit_err_log',true);
                                fdump($pigcms_id,'api/log/'.date('Ymd').'/house/audit_edit_err_log',true);
                                fdump($y_pigcms_id,'api/log/'.date('Ymd').'/house/audit_edit_err_log',true);
                                fdump($where_other,'api/log/'.date('Ymd').'/house/audit_edit_err_log',true);
                                fdump($set,'api/log/'.date('Ymd').'/house/audit_edit_err_log',true);
                                fdump($database_house_village_user_bind->getDbError(),'api/log/'.date('Ymd').'/house/audit_edit_err_log',true);
                                fdump($database_house_village_user_bind->_sql(),'api/log/'.date('Ymd').'/house/audit_edit_err_log',true);
                                $this->error('添加失败！');
                            }
                        }
                    }
                    $where_bind = [
                        'pigcms_id' => $pigcms_id
                    ];
                    if (!$data['single_id'] || !$data['layer_id']) {
                        fdump('审核参数有误 参数有误>>'.__LINE__,'api/log/'.date('Ymd').'/user/admin_check_apply_err_log',true);
                        fdump($_POST,'api/log/'.date('Ymd').'/user/admin_check_apply_err_log',true);
                        fdump($info,'api/log/'.date('Ymd').'/user/admin_check_apply_err_log',true);
                        fdump($where_bind,'api/log/'.date('Ymd').'/user/admin_check_apply_err_log',true);
                        fdump($data,'api/log/'.date('Ymd').'/user/admin_check_apply_err_log',true);
                    }
                    $insert_id = $database_house_village_user_bind->where($where_bind)->data($data)->save();
                    if ($insert_id!==false) {
                        // 如果存在原数据id 删除申请数据
                        if ($y_pigcms_id) {
                            $where_bind_del = [
                                'pigcms_id' => $y_pigcms_id
                            ];
//                            $insert_id = $database_house_village_user_bind->where($where_bind_del)->delete();

                            /**
                             * todo 软删除 修改类型 usernum拼上时间戳
                             */
                            $insert_id = $database_house_village_user_bind->where($where_bind_del)->data(['usernum'=>$info['usernum'].'-'.createRandomStr(11,true),'status'=>4])->save();


                            fdump('如果存在原数据id 删除申请数据>>'.__LINE__,'api/log/'.date('Ymd').'/house/check_delete_log',true);
                            fdump($info,'api/log/'.date('Ymd').'/house/check_delete_log',true);
                            fdump($where_bind_del,'api/log/'.date('Ymd').'/house/check_delete_log',true);
                            fdump($data,'api/log/'.date('Ymd').'/house/check_delete_log',true);
                        }
                    } else {
                        fdump('>>>'.__LINE__,'api/log/'.date('Ymd').'/house/audit_edit_err_log',true);
                        fdump($database_house_village_user_bind->getDbError(),'api/log/'.date('Ymd').'/house/audit_edit_err_log',true);
                        fdump($database_house_village_user_bind->_sql(),'api/log/'.date('Ymd').'/house/audit_edit_err_log',true);
                        $this->error('添加失败！');
                    }
                    $face_pigcms_id = $pigcms_id;
                }else{
                    $this->error('审核对象不存在！');
                    $insert_id = $database_house_village_user_bind->data($data)->add();
                    $face_pigcms_id = $insert_id;
                }
                if (!$insert_id) {
                    fdump('审核通过添加失败>>'.__LINE__,'api/log/'.date('Ymd').'/house/add_err_log',true);
                    fdump($insert_id,'api/log/'.date('Ymd').'/house/add_err_log',true);
                    fdump($data,'api/log/'.date('Ymd').'/house/add_err_log',true);
                    fdump($database_house_village_user_bind->getDbError(),'api/log/'.date('Ymd').'/house/add_err_log',true);
                    $this->error('添加失败！');
                }

                //2020/10/9 start 更换业主 原业主积分清零
                //小区积分开关 （1开启 0关闭）
                $house_score_switch = D('Config')->where(array('name'=>'house_score_switch'))->getField('value');
                //小区积分开关开启则执行
                if($house_score_switch == 1) {
                    $where['uid'] = $info['uid'];
                    $where['status'] = ['eq', 1];
//                    $where['parent_id'] = ['eq', 0];//查询业主
                    $where['type'] = ['eq', [0,3]];//查询业主
                    $house_user_owner = D('House_village_user_bind')->where($where)
                        ->field('pigcms_id,uid,name,phone,parent_id,type,add_time')
                        ->order('add_time asc')->find();
                    if (!$house_user_owner) {
                        $user_info = D('User')->where(['uid' => $info['uid']])->field('house_integral,score_count')->find();
                        $user_score['house_integral'] = 0;
                        $user_score['score_count'] = 0;
                        D('User')->where(['uid' => $info['uid']])->data($user_score)->save();
                    }
                }
                //2020/10/9 end

                if($insert_id){
                    $data_room['status'] = 3;
                    if ($data['uid']) {
                        $data_room['uid'] = $data['uid'];
                    } elseif($info['uid']) {
                        $data_room['uid'] = $info['uid'];
                    }
                    if ($data['name']) {
                        $data_room['name'] = $data['name'];
                    } elseif($info['name']) {
                        $data_room['name'] = $info['name'];
                    }
                    if ($data['phone']) {
                        $data_room['phone'] = $data['phone'];
                    } elseif($info['phone']) {
                        $data_room['phone'] = $info['phone'];
                    }
                    $data_room['memo'] = $memo ? $memo : "";
                    // 传过来的面积不同就改值
                    if (floatval($housesize)>0 && floatval($housesize) != floatval($info['housesize'])) {
                        $data_room['housesize'] = $housesize;
                    }
                    $database_house_village_user_vacancy->where($bind_condition)->data($data_room)->save();

                    if (intval($face_pigcms_id)>0) {
                        $change_param = [];
                        $change_param['pigcms_id'] = $face_pigcms_id;
                        D('House_village_user_vacancy')->change_user_bind_number($change_param);
                    }
                    
                    if (3==$info['type']) {
                        $where_con['pigcms_id'] = array('neq',$face_pigcms_id);
                        $where_con['vacancy_id'] = $info['vacancy_id'];
                        // 将现有的家属租客等改为待审核状态
                        D("House_village_user_bind")->where($where_con)->data(['status'=>0,'reason' => '业主已替换成功（原业主更改状态），替换时间：'.date('Y-m-d H:i:s',time())])->save();
                        // 同步更新下当前房屋家属和租客的父级为0的为当前父级
                        $where_other_user_bind = [
                            'vacancy_id' => $info['vacancy_id'],
                            'type' => ['in', [1,2]],
                            'parent_id' => 0,
                        ];
                        $set = [
                            'parent_id' => $pigcms_id
                        ];
                        $database_house_village_user_bind->where($where_other_user_bind)->data($set)->save();
                    } else {
                        // 同步更新下当前房屋家属和租客的父级为0的为当前父级
                        $where_other_user_bind = [
                            'vacancy_id' => $info['vacancy_id'],
                            'type' => ['in', [1,2]],
                            'parent_id' => 0,
                        ];
                        $set = [
                            'parent_id' => $pigcms_id
                        ];
                        $database_house_village_user_bind->where($where_other_user_bind)->data($set)->save();
                    }
                    $href = C('config.site_url').'/wap.php?g=Wap&c=House&a=my_village_list&village_id='.$this->village_id;
                    //todo app 极光推送
                    $this->AppPush($face_pigcms_id,[
                        'title'=>'业主入住申请',
                        'msg'=>'您在【'.$this->village['village_name'].'】的业主入住申请审核已通过',
                        'url'=>$href
                    ]);

                    //发送模板消息
                    if ($now_user['openid']) {
                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        $model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $now_user['openid'],'first' => '您在【'.$this->village['village_name'].'】的业主入住申请审核已通过\n', 'keyword1' => '业主入住申请',  'keyword2' => '已发送', 'keyword3' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']), 'keyword4' => $now_user['nickname'], 'remark' => '\n请点击查看详细信息！'),0,$this->village['property_id'],1);
                    }
                    // 审核通过，进行同步
//                    D('House_village_face_door')->modify_user($insert_id);
                    D('House_face_img')->house_user_face_device_info($data_room['uid'],$face_pigcms_id);

                    if ($face_pigcms_id) {
                        $where_bind['pigcms_id'] = $face_pigcms_id;
                        $ywlsh = D("House_village_user_bind")->where($where_bind)->getField('third_xqryid');
                        if ($ywlsh) {
                            D('House_village_third_user_info')->apply_access_callback($this->village_id,$face_pigcms_id, 1,'', $ywlsh);
                        }
                    }
                    D('House_village_user_bind')->checkUserPass($face_pigcms_id, $now_user['uid'], $this->village_id, $this->village['property_id']);
                    if($info && !empty($info['ExternalUserID'])){
                        $db_house_contact_way_user = M('House_contact_way_user');
                        $db_house_worker = M('House_worker');
                        $db_village_qywx_data_center = M('Village_qywx_data_center');
                        $db_village_qywx_action_log = M('Village_qywx_action_log');
                        $db_house_village = M('House_village');
                        $contact_info = $db_house_contact_way_user->where(['ExternalUserID'=>$info['ExternalUserID'],'status'=>1])->find();
                        if($contact_info){
                            $worker_info = $db_house_worker->where(['is_del'=>0,'qy_id'=>$contact_info['UserId']])->find();
                            if($worker_info){
                                $year = date('Y',time());
                                $month = date('Y-m',time());
                                $day = date('Y-m-d',time());
                                $data_center_info = $db_village_qywx_data_center->where(['wid'=>$worker_info['wid'],'Y'=>$year,'M'=>$month,'D'=>$day])->find();
                                if($data_center_info){
                                    $db_village_qywx_data_center->where(['wid'=>$worker_info['wid'],'Y'=>$year,'M'=>$month,'D'=>$day])->setInc('new_house_holds_num');
                                    $db_village_qywx_action_log->add([
                                        'center_id'=>$data_center_info['id'],
                                        'add_type'=>$data_center_info['add_type'],
                                        'wid'=>$data_center_info['wid'],
                                        'user_id'=>$contact_info['UserId'],
                                        'external_user_id'=>$info['ExternalUserID'],
                                        'action_name'=>'NewHouseholds',
                                        'property_id'=>!empty($data_center_info['property_id'])?$data_center_info['property_id']:0,
                                        'village_id'=>!empty($data_center_info['village_id'])?$data_center_info['village_id']:0,
                                        'create_time'=>time()
                                    ]);
                                }else{
                                    $property_id = 0;
                                    if(!empty($worker_info['village_id'])){
                                        $property_id = $db_house_village->where(['village_id'=>$worker_info['village_id']])->getField('property_id');
                                    }
                                    $id = $db_village_qywx_data_center->add([
                                        'add_type'=>!empty($worker_info['village_id'])?1:2,
                                        'wid'=>$worker_info['wid'],
                                        'user_id'=>$contact_info['UserId'],
                                        'Y'=>$year,
                                        'M'=>$month,
                                        'D'=>$day,
                                        'new_house_holds_num'=>1,
                                        'property_id'=>!empty($worker_info['property_id'])?$worker_info['property_id']:$property_id,
                                        'village_id'=>!empty($worker_info['village_id'])?$worker_info['village_id']:0
                                    ]);
                                    $db_village_qywx_action_log->add([
                                        'center_id'=>$id,
                                        'add_type'=>!empty($worker_info['village_id'])?1:2,
                                        'wid'=>$worker_info['wid'],
                                        'user_id'=>$contact_info['UserId'],
                                        'external_user_id'=>$info['ExternalUserID'],
                                        'action_name'=>'NewHouseholds',
                                        'property_id'=>!empty($worker_info['property_id'])?$worker_info['property_id']:$property_id,
                                        'village_id'=>!empty($worker_info['village_id'])?$worker_info['village_id']:0,
                                        'create_time'=>time()
                                    ]);
                                }
                            }
                        }
                    }
                    $arr = array();
                    $arr['param'] = serialize(array('bind_id' =>$pigcms_id,'type'=>'user'));
                    $arr['plan_time'] = time()+60;
                    $arr['space_time'] = 0;
                    $arr['add_time'] = time();
                    $arr['file'] = 'sub_house_village_third';
                    $arr['time_type'] = 1;
                    $arr['unique_id'] = 'sub_house_village_third_user_'.$pigcms_id;
                    $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
                    M('process_sub_plan')->data($arr)->add();
                    if($_SERVER['original_post']['referer']){
                        $this->success('添加成功！', $_SERVER['original_post']['referer']);
                    }else{
                        $this->success('添加成功！', U('audit_index'));
                    }

                }else{
                    $this->error('添加失败！');
                }
            }else{ // 审核不通过
                if($info){
                    $where = array();
                    $where['type']=array('in',array(0,3));
                    $where['vacancy_id'] = $info['vacancy_id'];
                    $where['status'] = 1;
                    $where['pigcms_id'] = array('neq',$pigcms_id);
                    $res = $database_house_village_user_bind->where($where)->find();
                    if($res && ($res['uid'] || $res['phone'] || $res['name'])){
                        $save_bind = $database_house_village_user_bind->where(array('pigcms_id'=>$_POST['pigcms_id']))->save(['status'=>0,'refuse_time'=>time(),'reason'=>trim($_POST['reason']) ? htmlspecialchars(trim($_POST['reason'])) : '无']);
                    }else{
                        $check_data = $database_house_village_user_bind->where(['pigcms_id' => $pigcms_id])->find();
                        $bind_info = $database_house_village_user_bind->where(array('type'=>array('in',array(1,2)),'parent_id'=>$pigcms_id))->count();
                        if ($bind_info && $res) { // 存在家属且不存在虚拟房主就更新为虚拟房主
                            $edit_data = array();
                            $edit_data['uid'] = 0;
                            $edit_data['phone'] = '';
                            $edit_data['name'] = '';
                            $edit_data['status'] = 1;
                            $edit_data['reason'] = trim($_POST['reason']) ? htmlspecialchars(trim($_POST['reason'])) : '无';
                            $edit_data['refuse_time'] = time();
                            $insert_id = $database_house_village_user_vacancy->where($bind_condition)->data($edit_data)->save();
                            $save_bind = $database_house_village_user_bind->where(array('pigcms_id'=>$pigcms_id))->data($edit_data)->save();
                            if (!$save_bind) {
                                fdump('用户申请业主入住审核拒绝更改失败>>'.__LINE__,'api/log/'.date('Ymd').'/user/check_apply_log',true);
                                fdump($edit_data,'api/log/'.date('Ymd').'/user/check_apply_log',true);
                                fdump($database_house_village_user_bind->getDbError(),'api/log/'.date('Ymd').'/user/check_apply_log',true);
                            } elseif ($check_data) {
                                // 重新将数据添加为审核拒绝
                                unset($check_data['pigcms_id']);
                                $check_data['status'] = 0;
                                $check_data['reason'] = $edit_data['reason'];
                                $database_house_village_user_bind->add($check_data);
                            }
                        }else{
                            $edit_data = array();
                            $edit_data['uid'] = 0;
                            $edit_data['status'] = 1;
                            $edit_data['phone'] = '';
                            $edit_data['name'] = '';
                            $edit_data['reason'] = trim($_POST['reason']) ? htmlspecialchars(trim($_POST['reason'])) : '无';
                            $insert_id = $database_house_village_user_vacancy->where($bind_condition)->data($edit_data)->save();
                            $save_bind = $database_house_village_user_bind->where(array('pigcms_id'=>$_POST['pigcms_id']))->save(['status'=>0,'refuse_time'=>time(),'reason'=>trim($_POST['reason']) ? htmlspecialchars(trim($_POST['reason'])) : '无']);
                        }
                    }
                    if($save_bind){

                        $reason = trim($_POST['reason']) ? htmlspecialchars(trim($_POST['reason'])) : '无';
                        $href = C('config.site_url').'/wap.php?g=Wap&c=House&a=my_village_list&village_id='.$this->village_id;
                        //todo app 极光推送
                        $this->AppPush($pigcms_id,[
                            'title'=>'业主入住申请',
                            'msg'=>'您在【'.$this->village['village_name'].'】的业主入住申请审核未通过。原因：'.$reason,
                            'url'=>$href
                        ]);

                        //发送模板消息
                        if ($now_user['openid']) {
                            $reason = trim($_POST['reason']) ? htmlspecialchars(trim($_POST['reason'])) : '无';
                            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                            $model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $now_user['openid'],'first' => '您在【'.$this->village['village_name'].'】的业主入住申请审核未通过。原因：'.$reason.'\n', 'keyword1' => '业主入住申请', 'keyword2' => '已发送', 'keyword3' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']), 'keyword4' => $now_user['nickname'], 'remark' => '\n请点击查看详细信息！'),0,$this->village['property_id'],1);
                        }
                        if ($_POST['pigcms_id']) {
                            $where_bind['pigcms_id'] = $_POST['pigcms_id'];
                            $ywlsh = D("House_village_user_bind")->where($where_bind)->getField('third_xqryid');
                            if ($ywlsh) {
                                D('House_village_third_user_info')->apply_access_callback($this->village_id,$_POST['pigcms_id'], 0,'小区审核拒绝入住', $ywlsh);
                            }
                        }
                        $this->success('修改成功！');
                    }else{
                        $this->error('修改失败！');
                    }
                }
            }
            exit;
        }else if ($pigcms_id && $usernum) {
            $info['old_user_name'] = '';//原业主名称
            $info['old_user_phone'] = '';//原业主名称
            $info['third_user_type_text'] = '';//智慧平安小区用户人员类型
            $info['third_type_text'] = '';
            // 当前房间已经有业主。如果满足条件，需要获取即将被替换掉的业主姓名
            $old_user = M('House_village_user_bind')->where(['vacancy_id'=>$info['vacancy_id'],'type'=>['in',[0,3]],'status'=>1])->field('name, phone')->find();
            if($old_user && trim($old_user['name'])){
                $info['old_user_name'] = trim($old_user['name']);
            }
            if($old_user && trim($old_user['phone'])){
                $info['old_user_phone'] = trim($old_user['phone']);
            }
            if (isset($info['third_type']) && $info['third_type']) {
                $info['third_type_text'] = D('House_village_third_user_info')->getThirdType($info['third_type']);
            }
            if($chinapopinOpen && $info['third_xqryid']){
                //查询是否是智慧平安小区的用户，并且当前房间已经有业主。如果满足条件，需要获取即将被替换掉的业主姓名
                $third_user_type = M('House_village_third_user_info')->where(['third_xqryid'=>$info['third_xqryid']])->getField('type');
                $third_user_type = trim($third_user_type);
                // 人员类型，01 住户，02 租户，03 物业人员，04 服务人员， 05 来访人员
                $thirdUserTypeArr = [
                    '01' => '住户', '1' => '住户', '02' => '租户', '2' => '租户', '03' => '物业人员', '3' => '物业人员',
                    '04' => '服务人员', '4' => '服务人员', '05' => '来访人员', '5' => '来访人员',
                ];
                $info['third_user_type_text'] = isset($thirdUserTypeArr[$third_user_type]) ? $thirdUserTypeArr[$third_user_type] : '';
                //楼栋
                $single_list = M('House_village_single')->where(array('village_id'=>$this->village_id,'status'=>1))->order('sort DESC,id desc')->select();
                if($single_list){
                    $this->assign('single_list',$single_list);
                }else{
                    $this->assign('single_list',[]);
                }
            }
            $relatives_type_info = D('House_village_user_bind')->relatives_type_info;
            $this->assign('relatives_type_info', $relatives_type_info);
            $this->assign('info', $info);
        }
        $this->assign('chinapopinOpen', $chinapopinOpen);
        $this->display();
    }

    /**
     * 业主绑定审核换极光推送
     * @author: liukezhu
     * @date : 2021/6/30
     * @param $pigcms_id
     * @param array $arr
     * @return bool|void
     */
    private function AppPush($pigcms_id,$arr=[]){
        $bind_user = D('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->find();
        if(empty($bind_user)){
            return false;
        }
        return D('User')->AppPush($bind_user,$arr);
    }


    public function audit_del(){
        $pigcms_id = $_GET['pigcms_id'] + 0;
        if(!$pigcms_id){
            $this->error('传递参数有误！');
        }

        $where['pigcms_id'] = $pigcms_id;

        $database_house_village_user_vacancy = D('House_village_user_vacancy');

        $data['is_del'] = 1;
        $data['del_time'] = time();
        $insert_id = $database_house_village_user_vacancy->where($where)->data($data)->save();

        if($insert_id){
            // 删除  同步进行删除
            D('House_village_face_door')->delete_user($insert_id);
            $this->success('删除成功！');
        }else{
            $this->error('删除失败！');
        }
    }

    # update 2017-03-21 - wangdong
    // 家属租客-绑定解绑
    public function bind_edit(){
        //家属租客-绑定解绑 权限
        if (0&&!in_array(468, $this->house_session['menus']) && !in_array(471, $this->house_session['menus'])) {
            if ($_GET['data_type'] == 'json' ) {
                exit(json_encode(array('status'=>1,'msg'=>'对不起，您没有权限执行此操作')));
            }
            $this->error('对不起，您没有权限执行此操作');
        }

        $pigcms_id = $_GET['pigcms_id'] + 0;
        if(!$pigcms_id){
            if ($_GET['data_type'] == 'json' ) {
                exit(json_encode(array('status'=>1,'msg'=>'传递参数有误！')));
            }
            $this->error('传递参数有误！~~~');
        }

        $database_house_village_user_bind = D('House_village_user_bind');
        $now_user = $database_house_village_user_bind->get_one_by_bindId($pigcms_id);
        if(!$now_user){
            if ($_GET['data_type'] == 'json' ) {
                exit(json_encode(array('status'=>1,'msg'=>'信息暂时不存在！')));
            }
            $this->error('信息暂时不存在！');
        }

        if(!$_GET['no_bind']) {
            if ($now_user['status'] == 1) {
                if ($_GET['data_type'] == 'json' ) {
                    exit(json_encode(array('status'=>1,'msg'=>'已通过审核！')));
                }
                $this->error('已通过审核！');
            }
        }
        fdump_api(['单个编辑审核家属租客'.__LINE__,$_GET,$_POST,$this->house_session],'house/user/bind_edit_log',1);

        // 平台用户
        $user_info = D('User')->get_user($now_user['phone'], 'phone');

        if ($pigcms_id) {
            $where_bind['pigcms_id'] = $pigcms_id;
            $ywlsh = D("House_village_user_bind")->where($where_bind)->getField('third_xqryid');
        }
        $typename=C('config.open_house_user_name') ? '亲友':'家属';
        if($now_user['type']==2){
            $typename='租客';
        }
        if($_GET['no_bind']){
            $reason = trim($_GET['reason']) ? htmlspecialchars(trim($_GET['reason'])) : '无';
            $insert_id = $database_house_village_user_bind->where(array('pigcms_id'=>$pigcms_id))->data(array('pass_time'=>time(),'status'=>0,'reason'=>$reason))->save();

            if($now_user['type'] != 3){
                if ($now_user['status'] == 1 ) {
                    $first = '您在【'.$this->village['village_name'].'】的'.$typename.'入住申请已解绑。原因：'.$reason.'\n';
                    $keyword1 = ''.$typename.'入住申请';
                }else{
                    $first = '您在【'.$this->village['village_name'].'】的'.$typename.'入住申请审核未通过。原因：'.$reason.'\n';
                    $keyword1 = ''.$typename.'入住申请';
                }
            }else{
                $first = '您在【'.$this->village['village_name'].'】的替换房主申请审核未通过。原因：'.$reason.'\n';
                $keyword1 = '替换房主申请';
            }
        }else{
            if($now_user['type'] != 3){
                // 房屋信息
                $database_house_village_user_vacancy = D('House_village_user_vacancy');
                $vacancy_condition['pigcms_id'] = $now_user['vacancy_id'];
                $vacancy_condition['status'] = ['in', [1,2]];
                $vacancy_condition['phone'] = $now_user['phone'];
                $bind_info = $database_house_village_user_vacancy->where($vacancy_condition)->find();
                if($bind_info['phone'] && $now_user['phone'] == $bind_info['phone']){
                    if ($bind_info['status']==2) {
                        if ($_GET['data_type'] == 'json' ) {
                            exit(json_encode(array('status'=>1,'msg'=>'该手机号已提交申请')));
                        }
                        $this->error('该手机号已提交申请');
                    }else{
                        if ($_GET['data_type'] == 'json' ) {
                            exit(json_encode(array('status'=>1,'msg'=>'手机号不能与房屋其他人一致')));
                        }
                        $this->error('手机号不能与房屋其他人一致');
                    }
                }
                $where_owner = [
                    'type' =>['in', [0,3]],
                    'status' =>1,
                    'vacancy_id' => $now_user['vacancy_id'],
                ];
                $owner_pigcms_id = D("House_village_user_bind")->where($where_owner)->getField('pigcms_id');
                $set = array('pass_time'=>time(),'status'=>1);
                // 家属的父级业主id 更正为最新的
                if ($owner_pigcms_id && $now_user['parent_id']!=$owner_pigcms_id) {
                    $set['parent_id'] = $owner_pigcms_id;
                }
                $insert_id = $database_house_village_user_bind->where(array('pigcms_id'=>$pigcms_id))->data($set)->save();
                $face_pigcms_id = $pigcms_id;
                $arr = array();
                $arr['param'] = serialize(array('bind_id' =>$pigcms_id,'type'=>'user'));
                $arr['plan_time'] = time()+60;
                $arr['space_time'] = 0;
                $arr['add_time'] = time();
                $arr['file'] = 'sub_house_village_third';
                $arr['time_type'] = 1;
                $arr['unique_id'] = 'sub_house_village_third_user_'.$pigcms_id;
                $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
                M('process_sub_plan')->data($arr)->add();
                $first = '您在【'.$this->village['village_name'].'】的'.$typename.'入住申请审核已通过\n';
                $keyword1 = ''.$typename.'入住申请';
            }else{
                // 更新原房主信息
                $user_data['uid'] = $now_user['uid'];
                $user_data['name'] = $now_user['name'];
                $user_data['phone'] = $now_user['phone'];
                if (isset($now_user['third_id']) && $now_user['third_id']) {
                    $user_data['third_id'] = $now_user['third_id'];
                }
                if (isset($now_user['third_xqryid']) && $now_user['third_xqryid']) {
                    $user_data['third_xqryid'] = $now_user['third_xqryid'];
                }
                $user_data['pass_time'] = time();
                $user_data['parent_id'] = 0;
                $insert_id = $database_house_village_user_bind->where(array('pigcms_id'=>$now_user['parent_id']))->data($user_data)->save();
                $face_pigcms_id = $now_user['parent_id'];

                #要修改 房间house_village_user_vacancy 表信息
                $vacancy_data['uid'] = $now_user['uid'];
                $vacancy_data['name'] = $now_user['name'];
                $vacancy_data['phone'] = $now_user['phone'];
                $vacancy_data['status'] = 3;

                $vacancy_where['pigcms_id'] = $now_user['vacancy_id'];
                $vacancy_where['floor_id']  = $now_user['floor_id'];
                $vacancy_where['village_id'] = $now_user['village_id'];
                $database_house_village_user_vacancy = D('House_village_user_vacancy');
                $database_house_village_user_vacancy->where($vacancy_where)->data($vacancy_data)->save();

                // 删除申请记录
                fdump('>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                fdump($_SERVER,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                fdump('houseapi--'.__FUNCTION__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                $user_del_info = $database_house_village_user_bind->where(array('pigcms_id'=>$pigcms_id))->select();
                fdump($user_del_info,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
//                $database_house_village_user_bind->where(array('pigcms_id'=>$pigcms_id))->delete();

                /**
                 * todo 软删除 修改类型 usernum拼上时间戳
                 */
                $database_house_village_user_bind->where(array('pigcms_id'=>$pigcms_id))->data(['usernum'=>$now_user['usernum'].'-'.createRandomStr(11,true),'status'=>4])->save();

                $arr = array();
                $arr['param'] = serialize(array('bind_id' =>$pigcms_id,'type'=>'user'));
                $arr['plan_time'] = time()+60;
                $arr['space_time'] = 0;
                $arr['add_time'] = time();
                $arr['file'] = 'sub_house_village_third';
                $arr['time_type'] = 1;
                $arr['unique_id'] = 'sub_house_village_third_user_'.$pigcms_id;
                $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
                M('process_sub_plan')->data($arr)->add();
                fdump($database_house_village_user_bind->_sql(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                fdump($database_house_village_user_bind->getDbError(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);

                #禁止之前房主ID
                // $database_house_village_user_bind->where(array('pigcms_id'=>$now_user['parent_id']))->data(array('status'=>0))->save();
                #修改之前房主下面的绑定亲属/租客 替换到现在房主下面
                // $database_house_village_user_bind->where(array('parent_id'=>$now_user['parent_id']))->data(array('parent_id'=>$now_user['pigcms_id']))->save();
                $reason = '';
                $first = '您在【'.$this->village['village_name'].'】的替换房主申请审核已通过'.$reason.'\n';
                $keyword1 = '替换房主申请';
            }
        }

        //发送模板消息
        if ($user_info['openid']) {
            $href = C('config.site_url').'/wap.php?g=Wap&c=House&a=my_village_list&village_id='.$this->village_id;
            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
            $model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $user_info['openid'],'first' => $first, 'keyword1' =>  $keyword1,  'keyword2' => '已发送', 'keyword3' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']), 'keyword4' => $user_info['nickname'], 'remark' => '\n请点击查看详细信息！'),0,$this->village['property_id'],1);
        }

        if($insert_id){
            // 修改信息，进行同步
            if (!$_GET['no_bind']) {
                if (intval($face_pigcms_id)>0) {
                    $change_param = [];
                    $change_param['pigcms_id'] = $face_pigcms_id;
                    D('House_village_user_vacancy')->change_user_bind_number($change_param);
                }
                D('House_face_img')->house_user_face_device_info($now_user['uid'],$face_pigcms_id);
                if ($ywlsh) {
                    // 通过
                    D('House_village_third_user_info')->apply_access_callback($this->village_id,$pigcms_id, 1,'', $ywlsh);
                }
                D('House_village_user_bind')->checkUserPass($face_pigcms_id, $now_user['uid'], $this->village_id, $this->village['property_id']);
            } else {
                if ($ywlsh) {
                    // 拒绝
                    D('House_village_third_user_info')->apply_access_callback($this->village_id,$_POST['pigcms_id'], 0,'小区审核拒绝入住', $ywlsh);
                }
                // 解绑了 走下删除逻辑 以防遗留
                try {
                    D('House_village_face_door')->del_a3_user_bind($pigcms_id, $this->village_id);

                    //todo 轨迹信息 用户绑定/编辑家属或租客
                    $parent_user = M('house_village_user_bind')->where(['pigcms_id'=>$now_user['parent_id']])->field('uid,pigcms_id')->find();
                    $str = '业主解绑'.(($now_user['type'] == 1)?'家人':'租客').$now_user['name'];
                    D('House_user_trajectory_log')->addLog(4,$this->village_id,0,$parent_user['uid'],$parent_user['pigcms_id'],3,$str,'后台更新了人员数据');

                }catch (Exception $e) {
                    fdump_api(['家属租客解绑'.__LINE__,$pigcms_id,$this->village_id,$e->getMessage()],'face/user_del_err_log',1);
                }
            }
            if($now_user && !empty($now_user['ExternalUserID'])){
                $db_house_contact_way_user = M('House_contact_way_user');
                $db_house_worker = M('House_worker');
                $db_village_qywx_data_center = M('Village_qywx_data_center');
                $db_village_qywx_action_log = M('Village_qywx_action_log');
                $db_house_village = M('House_village');
                $contact_info = $db_house_contact_way_user->where(['ExternalUserID'=>$now_user['ExternalUserID'],'status'=>1])->find();
                if($contact_info){
                    $worker_info = $db_house_worker->where(['is_del'=>0,'qy_id'=>$contact_info['UserId']])->find();
                    if($worker_info){
                        $year = date('Y',time());
                        $month = date('Y-m',time());
                        $day = date('Y-m-d',time());
                        $data_center_info = $db_village_qywx_data_center->where(['wid'=>$worker_info['wid'],'Y'=>$year,'M'=>$month,'D'=>$day])->find();
                        if($data_center_info){
                            $db_village_qywx_data_center->where(['wid'=>$worker_info['wid'],'Y'=>$year,'M'=>$month,'D'=>$day])->setInc('new_house_holds_num');
                            $db_village_qywx_action_log->add([
                                'center_id'=>$data_center_info['id'],
                                'add_type'=>$data_center_info['add_type'],
                                'wid'=>$data_center_info['wid'],
                                'user_id'=>$contact_info['UserId'],
                                'external_user_id'=>$now_user['ExternalUserID'],
                                'action_name'=>'NewHouseholds',
                                'property_id'=>!empty($data_center_info['property_id'])?$data_center_info['property_id']:0,
                                'village_id'=>!empty($data_center_info['village_id'])?$data_center_info['village_id']:0,
                                'create_time'=>time()
                            ]);
                        }else{
                            $property_id = 0;
                            if(!empty($worker_info['village_id'])){
                                $property_id = $db_house_village->where(['village_id'=>$worker_info['village_id']])->getField('property_id');
                            }
                            $id = $db_village_qywx_data_center->add([
                                'add_type'=>!empty($worker_info['village_id'])?1:2,
                                'wid'=>$worker_info['wid'],
                                'user_id'=>$contact_info['UserId'],
                                'Y'=>$year,
                                'M'=>$month,
                                'D'=>$day,
                                'new_house_holds_num'=>1,
                                'property_id'=>!empty($worker_info['property_id'])?$worker_info['property_id']:$property_id,
                                'village_id'=>!empty($worker_info['village_id'])?$worker_info['village_id']:0
                            ]);
                            $db_village_qywx_action_log->add([
                                'center_id'=>$id,
                                'add_type'=>!empty($worker_info['village_id'])?1:2,
                                'wid'=>$worker_info['wid'],
                                'user_id'=>$contact_info['UserId'],
                                'external_user_id'=>$now_user['ExternalUserID'],
                                'action_name'=>'NewHouseholds',
                                'property_id'=>!empty($worker_info['property_id'])?$worker_info['property_id']:$property_id,
                                'village_id'=>!empty($worker_info['village_id'])?$worker_info['village_id']:0,
                                'create_time'=>time()
                            ]);
                        }
                    }
                }
            }
            if ($_GET['data_type'] == 'json' ) {
                exit(json_encode(array('status'=>0,'msg'=>'修改成功！')));
            }
            if(isset($_GET['redirect_url']) && $_GET['redirect_url']){
                $this->success('修改成功！',$_GET['redirect_url']);
            }else{
                $this->success('修改成功！');
            }
        }else{
            if ($_GET['data_type'] == 'json' ) {
                exit(json_encode(array('status'=>1,'msg'=>'修改失败！')));
            }
            $this->error('修改失败！');
        }
    }




    // 编辑业主
    public function edit()
    {
        $is_new_charge=D('House_village')->checkNewCharge($this->house_session['property_id']);
        $this->assign('new_charge_time', $is_new_charge);
        $house_village = M('House_village')->where(['village_id'=>$this->village_id])->field('property_id,set_id_code,street_id,community_id,owe_property_open_door')->find();
        $set_id_code=intval($house_village['set_id_code']);
        $user_bind_uid=0;
        if (IS_POST) {
            //业主-编辑 权限
            if (0&&!in_array(93, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }

            $newData=$_POST;
            $nmv_card = trim($_POST['nmv_card']);
            unset($_POST['nmv_card']);
            $attachment_auto_save = intval($_POST['attachment_auto_save']);
            
            $data_post = $_POST;
            unset($data_post['pigcms_id']);
            unset($data_post['usernum']);
            unset($data_post['single_id']);
            unset($data_post['page']);
            unset($data_post['name']);
            unset($data_post['phone']);
            unset($data_post['id_card']);
            unset($data_post['ic_card']);
            unset($data_post['attribute']);
            unset($data_post['floor_id']);
            unset($data_post['memo']);
            unset($data_post['water_price']);
            unset($data_post['electric_price']);
            unset($data_post['gas_price']);
            unset($data_post['park_price']);
            unset($data_post['property_starttime']);
            unset($data_post['property_endtime']);
            unset($data_post['city_idss']);
            unset($data_post['province_idss']);
            unset($data_post['door_control']);
            unset($data_post['attachment_auto_save']);
            $attachment_post = [
                'existing_url' => isset($_POST['attachment_existing_url']) && is_array($_POST['attachment_existing_url']) ? $_POST['attachment_existing_url'] : [],
                'existing_name' => isset($_POST['attachment_existing_name']) && is_array($_POST['attachment_existing_name']) ? $_POST['attachment_existing_name'] : [],
                'existing_size' => isset($_POST['attachment_existing_size']) && is_array($_POST['attachment_existing_size']) ? $_POST['attachment_existing_size'] : [],
                'existing_suffix' => isset($_POST['attachment_existing_suffix']) && is_array($_POST['attachment_existing_suffix']) ? $_POST['attachment_existing_suffix'] : [],
                'existing_time' => isset($_POST['attachment_existing_time']) && is_array($_POST['attachment_existing_time']) ? $_POST['attachment_existing_time'] : [],
            ];
            $attachment_files = [];
            if (!empty($_FILES) && is_array($_FILES)) {
                foreach ($_FILES as $file_key => $file_value) {
                    if (strpos($file_key, 'attachment_files_') === 0) {
                        $attachment_files[substr($file_key, strlen('attachment_files_'))] = $file_value;
                    }
                }
            }
            unset($data_post['attachment_existing_url'], $data_post['attachment_existing_name'], $data_post['attachment_existing_size'], $data_post['attachment_existing_suffix'], $data_post['attachment_existing_time']);
            unset($_POST['attachment_existing_url'], $_POST['attachment_existing_name'], $_POST['attachment_existing_size'], $_POST['attachment_existing_suffix'], $_POST['attachment_existing_time']);
            $database_house_village_user_bind = D('House_village_user_bind');
            $database_house_village_floor = D('House_village_floor');
            $database_house_village_user_vacancy = D('house_village_user_vacancy');
            $condition['pigcms_id'] = $_POST['pigcms_id'];
            $condition['village_id'] = $this->village_id;
            // 业主信息
            $user_info = $database_house_village_user_bind->where($condition)->find();
            if(!$user_info){
                $this->error('数据不存在！');
            }
            $origin_authentication_field = $user_info['authentication_field'] ? @unserialize($user_info['authentication_field']) : [];
            if (!is_array($origin_authentication_field)) {
                $origin_authentication_field = [];
            }
            $attachment_field_list = $this->getUserAttachmentFieldList($house_village['property_id']);
            if($data_post['user_political_affiliation'] == 1 && $data_post['party_type'] < 1){
                $this->error('请选择党员属性！');
            }
            if($data_post['user_political_affiliation'] == 1 && $data_post['party_type'] == 1 && $data_post['user_party_id'] < 1){
                $this->error('请选择党支部！');
            }
            if($data_post['user_political_affiliation'] != 1 || $data_post['party_type'] != 1){
                $_POST['user_party_id'] = 0;
            }
            $user_label_arr=[
                'pigcms_id'=>$_POST['pigcms_id'],
                'uid'=>$user_info['uid'],
                'user_political_affiliation'=>isset($_POST['user_political_affiliation'])? $_POST['user_political_affiliation'] : 0,
                'user_party_id'=>isset($_POST['user_party_id'])? $_POST['user_party_id'] : 0,
                'user_special_groups'=>isset($_POST['user_special_groups']) ? $_POST['user_special_groups'] : [],
                'user_focus_groups'=>isset($_POST['user_focus_groups']) ? $_POST['user_focus_groups'] : [],
                'user_vulnerable_groups'=>isset($_POST['user_vulnerable_groups']) ? $_POST['user_vulnerable_groups'] : [],
                'user_label_groups'=>isset($_POST['user_label_groups']) ? $_POST['user_label_groups'] : [],
                'party_type'=>$data_post['party_type'],
            ];
            unset($_POST['user_political_affiliation'],$_POST['user_party_id'],$_POST['user_special_groups'],$_POST['user_focus_groups'],$_POST['user_vulnerable_groups'],$_POST['user_label_groups']);
            $_POST['name'] = trim($_POST['name']);
            $_POST['phone'] = trim($_POST['phone']);
            if($set_id_code == 1 || !empty($_POST['id_card'])){
                if(!$_POST['id_card']){
                    $this->error('身份证号码不能为空！');
                }
                if(!strpos($_POST['id_card'],'*') && !preg_match('#(^\d{15}$)|(^\d{17}(\d|X)$)#', $_POST['id_card'])){
                    $this->error('请填写正确的身份证号码');
                }
            }
            $idCard=idCardAnalysis($_POST['id_card']);
            foreach($data_post as $k=>&$v){
                if(in_array($k,['birthday','sex'])){
                    if($idCard != false){
                        $v['value']=$idCard[$k];
                    }
                }
                if($v['key'] == 'native_place'){
                    $v['value'] = $_POST['province_idss'].'#'.$_POST['city_idss'];
                }
                if(is_array($v) && $v['is_must'] == 1 && empty($v['value'])){
                    $this->error($v['title'].'为必填项！');
                }
            }
            unset($v);
            unset($_POST['province_idss']);
            unset($_POST['city_idss']);
            $data_post = $this->mergeUserAttachmentFieldData($data_post, $_POST['pigcms_id'], $attachment_post, $attachment_files, $attachment_field_list, $origin_authentication_field);
            $data_post_list['authentication_field'] = serialize($data_post);
            $page = $_POST['page'];
            unset($_POST['page']);
            unset($_POST['attachment_auto_save']);
            if($_POST['property_starttime'] > $_POST['property_endtime']){
                $this->error('请填写正确的物业时间！');
            }
            !empty($_POST['property_starttime']) && $_POST['property_starttime'] = strtotime($_POST['property_starttime'].'00:00:00');
            !empty($_POST['property_endtime']) && $_POST['property_endtime'] = strtotime($_POST['property_endtime'].'23:59:59');

            $_POST['add_time'] = $_SERVER['REQUEST_TIME'];

            if (isset($_POST['usernum']) && $_POST['usernum'] && $_POST['usernum']!=$user_info['usernum']) {
                $_POST['usernum'] = trim($_POST['usernum']);
                $other_pigcms_id = $database_house_village_user_bind->where(['pigcms_id'=>['neq', $_POST['pigcms_id']],'status'=>1,'usernum' => $_POST['usernum']])->getField('pigcms_id');
                if ($other_pigcms_id) {
                    $this->error('该物业编号已经被使用！');
                }
                $other_vacancy_id = $database_house_village_user_vacancy->where(['pigcms_id'=>['neq', $user_info['vacancy_id']],'usernum' => $_POST['usernum']])->getField('pigcms_id');
                if ($other_vacancy_id) {
                    $this->error('该物业编号已经被使用！');
                }
            } else {
                unset($_POST['usernum']);
            }

            // 手机号不能与当前房屋中待审核和审核通过的手机号一样
            if ($_POST['phone']) {
                $bind_condition = [];
                $bind_condition['pigcms_id'] = ['neq', $user_info['pigcms_id']];
                $bind_condition['vacancy_id'] = $user_info['vacancy_id'];
                $bind_condition['phone'] = $_POST['phone'];
                $bind_condition['type'] = ['in', [0,1,2,3]];
                $bind_condition['status'] = array('in' , [1,2]);
                $other_bind_info = D('House_village_user_bind')->where($bind_condition)->find();
                if($other_bind_info){
                    $this->error('该手机号已绑定或已申请绑定此房间');
                }
            }
            // 姓名不能与当前房屋中待审核和审核通过的姓名一样
            if ($_POST['name'] && $_POST['phone']) {
                $bind_condition = [];
                $bind_condition['pigcms_id'] = ['neq', $user_info['pigcms_id']];
                $bind_condition['vacancy_id'] = $user_info['vacancy_id'];
                $bind_condition['name'] = $_POST['name'];
                $bind_condition['phone'] = $_POST['phone'];
                $bind_condition['type'] = ['in', [0,1,2,3]];
                $bind_condition['status'] = ['in' , [1,2]];
                $other_bind_info = D('House_village_user_bind')->where($bind_condition)->find();
                if($other_bind_info){
                    $this->error("{$_POST['name']}已绑定或已申请绑定此房间");
                }
            }

            if (trim($_POST['ic_card'])) {
                // 检查下是否当前ic卡已经被其他人使用 状态处于正常的
                $where_repeat = [
                    'ic_card' => trim($_POST['ic_card']),
                    'status' => 1,
                    'pigcms_id' => ['neq', $user_info['pigcms_id']]
                ];
                $repeat = $database_house_village_user_bind->where($where_repeat)->field('pigcms_id,village_id,name')->find();
                if (!empty($repeat)) {
                    if ($repeat['village_id']==$this->village_id) {
                        // 同一个小区提示具体重复人员
                        $this->error("当前用户【".$repeat['name']."】已经使用了该卡号");
                    } else {
                        $where_village = [
                            'village_id' => $repeat['village_id']
                        ];
                        $village_name = M('House_village')->where($where_village)->getField('village_name');
                        // 不同小区提示小区
                        $this->error("当前【".$village_name."】小区用户已经使用了该卡号");
                    }
                }
            }

            //汽车充电卡
            if (trim($_POST['pile_card_no'])) {
                // 检查下是否当前ic卡已经被其他人使用 状态处于正常的
                $where_repeat = [
                    'pile_card_no' => trim($_POST['pile_card_no']),
                    'status' => 1,
                    'pigcms_id' => ['neq', $user_info['pigcms_id']]
                ];
                $repeat = $database_house_village_user_bind->where($where_repeat)->field('pigcms_id,village_id,name')->find();
                if (!empty($repeat)) {
                    if ($repeat['village_id']==$this->village_id) {
                        // 同一个小区提示具体重复人员
                        $this->error("当前用户【".$repeat['name']."】已经使用了该卡号");
                    } else {
                        $where_village = [
                            'village_id' => $repeat['village_id']
                        ];
                        $village_name = M('House_village')->where($where_village)->getField('village_name');
                        // 不同小区提示小区
                        $this->error("当前【".$village_name."】小区用户已经使用了该卡号");
                    }
                }
            }
            // 非机动车卡号
            if ($nmv_card) {
                // 检查下是否当前ic卡已经被其他人使用 状态处于正常的
                $where_nmv['nmv_card'] = $nmv_card;
                $where_nmv['status'] = ['lt', 4];
                $where_nmv['pigcms_id'] = ['neq', $user_info['pigcms_id']];
                $nmv = $database_house_village_user_bind->field('pigcms_id,village_id,name')->where($where_nmv)->find();
                if (!empty($nmv)) {
                    if ($nmv['village_id']==$this->village_id) {
                        // 同一个小区提示具体重复人员
                        $this->error("当前用户【".$nmv['name']."】已经使用了该非机动卡号");
                    } else {
                        $where_village = [
                            'village_id' => $nmv['village_id']
                        ];
                        $village_name = M('House_village')->where($where_village)->getField('village_name');
                        // 不同小区提示小区
                        $this->error("当前【".$village_name."】小区用户已经使用了该非机动卡号");
                    }
                }
            }
            $_POST['nmv_card']= $nmv_card ;

            // if($_POST['phone'] && !preg_match('/^[0-9]{11}$/',$_POST['phone'])){
            //     $this->error('请输入正确的手机号！');
            // }
            // 验证是否更新房屋信息
            $update_vacancy = true;
            if ($user_info['uid']==0 &&  $user_info['phone']=='') { // 虚拟业主
                if (!$_POST['phone']) { // 没有修改手机号，不更新房屋状态；修改则更新
                    $update_vacancy = false;
                }
            }

            if ($_POST['phone']) {
                $uid = D('User')->get_user_by_phone($_POST['phone']);
            }
            $_POST['uid'] = $uid ? $uid : 0;

            $_POST['floor_id'] = $_POST['floor_id'] + 0;
            if (intval($_POST['floor_id'])>0) {
                $where['floor_id'] = $_POST['floor_id'];
                $where['status'] = 1;
                $house_village_floor_info = $database_house_village_floor->where($where)->find();

                if (!isset($house_village_floor_info)) {
                    $this->error('单元不存在，请查看社区中心，单元管理-单元列表！');
                }
            } else {
                unset($_POST['floor_id']);
            }
            $vacancy_where['status'] = array('in' , '1,2,3');
            $vacancy_where['pigcms_id'] = $user_info['vacancy_id'];
            $vacancy_info = $database_house_village_user_vacancy->where($vacancy_where)->find();
            if (!isset($vacancy_info)) {
                $this->error('该房间信息不存在！');
            }
            //处理未缴费订单==>郭先森
            $res = $database_house_village_user_bind->where($condition)->find();
            if($res){
                $park['type']= $gas['type']= $electric['type']= $water['type']=$data['type'] = 1;
                $park['village_id']= $gas['village_id']= $electric['village_id']= $water['village_id']=$data['village_id'] = $this->village_id;
                $park['uid']= $gas['uid']= $electric['uid']= $water['uid']=$data['uid'] = $_POST['uid'];
                $park['usernum']= $gas['usernum']= $electric['usernum']= $water['usernum']=$data['usernum'] = $res['usernum'];
                $park['name']= $gas['name']= $electric['name']= $water['name']=$data['name'] = $res['name'];
                $park['phone']= $gas['phone']= $electric['phone']= $water['phone']=$data['phone'] = $res['phone'];
                $park['bind_id']= $gas['bind_id']= $electric['bind_id']= $water['bind_id']=$data['bind_id'] = $res['pigcms_id'];
                $park['add_time']= $gas['add_time']= $electric['add_time']= $water['add_time']=$data['add_time'] = time();

//                $data
                // 水费
                /*if ($_POST['water_price'] >$res['water_price']) {
                    $water['type'] = 1;
                    $water['water_price'] = $_POST['water_price']-$res['water_price'];
                    M('House_village_user_paylist')->data($water)->add();
                }elseif($_POST['water_price'] <$res['water_price']){
                    $water['type'] = 1;
                    $water['water_price'] = $_POST['water_price']-$res['water_price'];
                    M('House_village_user_paylist')->data($water)->add();
                }*/
                // 电费
                /*if ($_POST['electric_price'] >$res['electric_price']) {
                    $electric['type'] = 1;
                    $electric['electric_price'] = $_POST['electric_price']-$res['electric_price'];
                    M('House_village_user_paylist')->data($electric)->add();

                }elseif($_POST['electric_price'] <$res['electric_price']){
                    $electric['type'] = 1;
                    $electric['electric_price'] = $_POST['electric_price']-$res['electric_price'];
                    M('House_village_user_paylist')->data($electric)->add();
                }*/
                // 燃气
                /*if ($_POST['gas_price'] >$res['gas_price']) {
                    $gas['type'] = 1;
                    $gas['gas_price'] = $_POST['gas_price']-$res['gas_price'];
                    M('House_village_user_paylist')->data($gas)->add();
                }elseif($_POST['gas_price'] <$res['gas_price']){
                    $gas['type'] = 1;
                    $gas['gas_price'] = $_POST['gas_price']-$res['gas_price'];
                    M('House_village_user_paylist')->data($gas)->add();
                }*/
                // 停车费
                /*if ($_POST['park_price'] >$res['park_price']) {
                    $park['type'] = 1;
                    $park['park_price'] = $_POST['park_price']-$res['park_price'];
                    M('House_village_user_paylist')->data($park)->add();
                }elseif($_POST['park_price'] <$res['park_price']){
                    $park['type'] = 1;
                    $park['park_price'] = $_POST['park_price']-$res['park_price'];
                    M('House_village_user_paylist')->data($park)->add();
                }*/
            }
            
            // 合并标签
            $label_arr = array_merge([$_POST['political_outlook']],(array)$_POST['special_population'],(array)$_POST['key_population'],(array)$_POST['care_object']);
            $label_ids = implode(',', $label_arr);
            $param = [$label_ids,$_POST['pigcms_id'],$this->village_id];
            // 保存标签
            //invoke_v20_service('\app\community\model\service\HouseVillageUserLabelBindService/addUserLabelBind',$param);

            // $Map['usernum'] =  $vacancy_info['usernum'];

            // if ($database_house_village_user_bind->field('`usernum`')->where($Map)->find()) {
            //$this->error('业主名为' . $_POST['user_name'] . ' 已存在！物业编号重复');
            //}
            //$_POST['usernum'] =  $vacancy_info['usernum'];
            //todo 信息标注编辑-添加
            D('House_village_user_label')->saveUserLabelMethod($user_label_arr);
            //没有修改房间表 house_village_user_vacancy
            
            if ($database_house_village_user_bind->where($condition)->data($_POST)->save()) {
                //处理自定义资料
                $database_house_village_user_bind->where($condition)->data($data_post_list)->save();
                if ($update_vacancy) {
                    //修改房间表 信息
                    $condition_vacancy['usernum']    = $vacancy_info['usernum'];
                    $condition_vacancy['village_id'] = $this->village_id;

                    $data_vacancy['status'] = 3;
                    $data_vacancy['uid'] = $_POST['uid'];
                    $data_vacancy['name'] = $_POST['name'];
                    $data_vacancy['phone'] = $_POST['phone'];
                    $data_vacancy['memo'] = $_POST['memo'];
                    // $data_vacancy['housesize'] = $_POST['housesize'];
                    $data_vacancy['park_flag'] = $_POST['park_flag'];
                    $data_vacancy['type'] = 0;
                    $database_house_village_user_vacancy->where($condition_vacancy)->data($data_vacancy)->save();
                }
                if (!$user_info['uid'] && $_POST['uid']) {
                    $user_info['uid'] = $_POST['uid'];
                }
                if (intval($_POST['pigcms_id'])>0) {
                    $change_param = [];
                    $change_param['pigcms_id'] = $_POST['pigcms_id'];
                    D('House_village_user_vacancy')->change_user_bind_number($change_param);
                }
                set_time_limit(0);
                ini_set('memory_limit', '2048M');
                D('House_face_img')->house_user_face_device_info($user_info['uid'],$_POST['pigcms_id']);
                $device5Count = D('House_face_device')->where(['device_type'=>['in',[5,6],'is_del'=> 0]])->count();
                if ($device5Count && intval($_POST['property_endtime'])<intval(time())) {
                    // 如果物业截止时间小于当前  单独同步下
                    D('Face_door_a5_service')->synDevice($_POST['pigcms_id']);
                }
                // 存在物业时间和家属同步下家属信息
                if ($_POST['property_endtime']) {
                    // 存在物业时间和家属同步下家属信息
                    $childrenList = $database_house_village_user_bind->where(array('parent_id'=>$_POST['pigcms_id'],'status'=>1))->field('pigcms_id,uid')->select();
                    if (!empty($childrenList)) {
                        foreach ($childrenList as $items) {
                            D('House_face_img')->house_user_face_device_info($items['uid'],$items['pigcms_id']);
                        }
                    }
                }
                $arr = array();
                $arr['param'] = serialize(array('bind_id' =>$_POST['pigcms_id'],'type'=>'user'));
                $arr['plan_time'] = time()+60;
                $arr['space_time'] = 0;
                $arr['add_time'] = time();
                $arr['file'] = 'sub_house_village_third';
                $arr['time_type'] = 1;
                $arr['unique_id'] = 'sub_house_village_third_user_'.$_POST['pigcms_id'];
                $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
                M('process_sub_plan')->data($arr)->add();

                //todo 轨迹信息 用户编辑
               D('House_user_trajectory_log')->addLog(4,$this->village_id,0,$res['uid'],$res['pigcms_id'],2,'更新了人员数据','后台更新了人员数据');
                //todo 用户同步写入属性表记录
                invoke_v20_service('\app\community\model\service\TaskReleaseService/userAttributeRecord', ['pigcms_id'=>$_POST['pigcms_id'],'field'=>$data_post]);

                $hik_info_vision_i_community_app_key=C('config.hik_info_vision_i_community_app_key');
                $hik_info_vision_i_community_app_secret=C('config.hik_info_vision_i_community_app_secret');
                if($hik_info_vision_i_community_app_key && $hik_info_vision_i_community_app_secret){
                    $hikRet=invoke_v20_service('\app\community\model\service\Device\HikInfoVisionICommunityService/updateBindUserToHikCommunity', ['village_id'=>$this->village_id,'pigcms_id'=>$_POST['pigcms_id']]);
                }
                if(!empty($_POST['iframe'])) {
                    $this->success('修改成功');
                }
                // 绑定车辆信息
                if($_POST['car_id']){
                    // 获取已绑定信息
                    $carList = D('House_village_bind_car')
                        ->where(array('village_id' => $this->village_id, 'user_id' => $_POST['pigcms_id']))
                        ->field('car_id')
                        ->select();
                    $carIdArr = array_column($carList,'car_id');
                    $carId = explode(',',$_POST['car_id']);
                    $add_data = [];
                    foreach ($carId as $kk => $vv){
                        if(!empty($vv) && !in_array($vv,$carIdArr)){
                            $add_data[] = [
                                'village_id' => $this->village_id,
                                'user_id' => $_POST['pigcms_id'],
                                'car_id' => $vv,
                                'uid' => intval($_POST['uid']),
                            ];
                        }
                    }
                    if(!empty($add_data)){
                        D('House_village_bind_car')->addAll($add_data);
                    }
                }

                if($nmv_card){
                    invoke_v20_service('\app\community\model\service\CloudIntercomService/saveUsernmvCard',[$_POST['pigcms_id'],$nmv_card]);
                }
                if($this->huizhisq && $_POST['ic_card']){ // 门禁卡
                    try {
                        invoke_v20_service('\app\community\model\service\CloudIntercomService/sendIcCardToDevice',[$_POST['ic_card'],1]);
                    }catch (\Exception $ee){
                        fdump_api(['执行错误' => $ee->getMessage(), 'line' => __LINE__, 'param' => $param, 'errline' => $ee->getLine(), 'file' => $ee->getFile()], 'edit_sendIcCardToDevice');
                    }
                }
                $models = '\app\common\model\service\CommonSystemLogService/addLaterLogInQueue';
                $logData=array('tbname'=>'人车房管理-人员管理','table'=>'house_village_user_bind','client'=>'小区后台','trigger_path'=>'业主列表->编辑','trigger_type'=>'update','village_id'=>$this->village_id,'property_id'=>0);
                $logData['login_uid']=$this->login_uid;
                $logData['login_role']=$this->login_role;
                $logData['newData']=$newData;
                $logData['oldData']=$user_info;
                $param=array('logData'=>$logData);
                $result = invoke_v20_service($models, $param);

                if($attachment_auto_save){
                    $this->success('修改成功', U('User/edit',array('pigcms_id'=>$_POST['pigcms_id'],'usernum'=>$newData['usernum'])).'#attachments');
                }else{
                    $this->success('修改成功', U('User/index',array('page'=>$page)));
                }
                exit;
            }

            $this->error('保存失败');
            exit;
        }
        else {
            //业主-查看 权限
            if (0&&!in_array(90, $this->house_session['menus']) && !in_array(91, $this->house_session['menus']) && !in_array(93, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }
            //$house_village_config['_string'] = "( village_id=".$this->village_id.") OR (village_id = 0)";
            $attachment_field_list = [];
            $legacy_attachment_list = [];

            $where_village['village_id'] = $this->village_id;
            $property_id = D('House_village')->where($where_village)->getField('property_id');
            $where_data_config = array(
                'village_id'=>0,
                'property_id'=>$property_id,
                'is_display'=>1,
                'is_close'=>0
            );
            $dataList = D('House_village_data_config')->where($where_data_config)->order('sort desc')->select();

            $pigcms_id = $_GET['pigcms_id'];
            $usernum = $_GET['usernum'];
            if ($pigcms_id && $usernum) {
                $reply_info = M('House_village_checkauth_apply')->where(['pigcms_id'=>$pigcms_id,'xtype'=>'service_time_check'])->order('id DESC')->find();
                if(!empty($reply_info)){
                    $reply_info['add_time_txt'] = date('Y-m-d H:i:s',$reply_info['add_time']);
                    $reply_info['service_time'] = date('Y-m-d',$reply_info['service_start_time']).'--'.date('Y-m-d',$reply_info['service_end_time']);
                    $reply_detail_info = M('House_village_checkauth_detail')->where(['apply_id'=>$reply_info['id']])->order('id DESC')->select();
                    if($reply_detail_info){
                        foreach ($reply_detail_info as &$v){
                            $v['apply_time_txt'] = date('Y-m-d H:i:s',$v['apply_time']);
                            if($v['status'] == 1){
                                $v['status_txt'] = '审核通过';
                            }elseif($v['status'] == 2){
                                $v['status_txt'] = '审核未通过';
                            }else{
                                $v['status_txt'] = '未审核';
                                $v['apply_time_txt'] = '-';
                            }
                            $v['name'] = json_decode($v['extra_data'],true)['name'];
                        }
                    }
                }else{
                    $reply_detail_info = [];
                }
                $this->assign('reply_info',$reply_info);
                $this->assign('reply_detail_info',$reply_detail_info);
                $bind_condition['pigcms_id'] = $pigcms_id;
                $info = D('House_village_user_bind')->where($bind_condition)->find();
                $user_bind_uid=$info['uid'];
                $database_house_village_user_vacancy = D('House_village_user_vacancy');
                $info['min_service_start_time'] ='';
                $info['max_service_end_time'] ='';
                if ($is_new_charge == 1) {
                    //$userTimes =  D('House_village_user_bind')->getUserEndTime($bind_condition['pigcms_id'],false);
                    $userTimes =  D('House_village_user_bind')->getPropertyTime($bind_condition['pigcms_id'],[
                        'property_starttime'=>$bind_condition['property_starttime'],
                        'property_endtime'=>$bind_condition['property_endtime']
                    ]);
                    if (isset($userTimes['propertyEndTime']) && $userTimes['propertyEndTime']>100) {
                        $info['property_endtime'] = intval($userTimes['propertyEndTime']);
                        $info['max_service_end_time']= date('Y-m-d',$info['property_endtime']);
                    } else {
                        $info['property_endtime'] = 0;
                    }
                    if (isset($userTimes['propertyStartTime'])) {
                        $info['property_starttime'] = intval($userTimes['propertyStartTime']);
                        $info['min_service_end_time']=date('Y-m-d',$info['property_starttime']);
                    } else {
                        $info['property_starttime'] = 0;
                    }
                    
                    $serviceTimeInfo = $database_house_village_user_vacancy->getRoomEndTime($info['vacancy_id'],$this->village_id,$property_id);
                    if ($serviceTimeInfo['propertyStartTime']>100){
                        $info['min_service_start_time'] = date('Y-m-d',$serviceTimeInfo['propertyStartTime']);
                    }
                    if ($serviceTimeInfo['propertyEndTime']>100){
                        $info['max_service_end_time'] = date('Y-m-d',$serviceTimeInfo['propertyEndTime']);
                    }
                    
                }else{
                    if (!$info['property_endtime']) {
                        $now_pay_info = D('House_village_property_paylist')->where(array('bind_id' => $info['pigcms_id']))->order('add_time desc')->field('end_time')->find();
                        $info['property_endtime'] = $now_pay_info['end_time'];
                    }
                    if (!$info['property_starttime']) {
                        $now_pay_info = D('House_village_property_paylist')->where(array('bind_id' => $info['pigcms_id']))->order('add_time asc')->field('start_time')->find();
                        $info['property_starttime'] = $now_pay_info['start_time'];
                    }
                }
                $database_house_village_floor = D('House_village_floor');
                if($info['floor_id']){
                    $floor_info = $database_house_village_floor->where(array('floor_id'=>$info['floor_id']))->field('floor_type, floor_name, floor_layer','single_id')->find();
                    $floor_type = $floor_info['floor_type'];
                    $info['floor_type_name'] = D('House_village_floor_type')->where(array('id'=>$floor_type))->getField('name');
                }
                if ($info['vacancy_id']) {
                    $info['house_type'] = D('House_village_user_vacancy')->where(['pigcms_id' => $info['vacancy_id']])->getField('house_type');
                }
                if (!$info['house_type']) {
                    $info['house_type']=1;
                }

                if($info['authentication_field']){
                    $info['authentication_field'] = @unserialize($info['authentication_field']);
                }
                if (!is_array($info['authentication_field'])) {
                    $info['authentication_field'] = [];
                }
                if (!empty($info['authentication_field']['user_attachment_list'])) {
                    $legacy_attachment_list = $this->formatUserAttachmentList($info['authentication_field']['user_attachment_list']);
                    unset($info['authentication_field']['user_attachment_list']);
                }

                if ($floor_info && $floor_info['single_id']>0 && !$info['single_id']) {
                    $info['single_id'] = $floor_info['single_id'];
                }
                if($info['layer_id']) {
                    $layer_id = $info['layer_id'];
                } elseif ($info['vacancy_id']) {
                    $layer_id = M('House_village_user_vacancy')->where(['pigcms_id' => $info['vacancy_id']])->getField('layer_id');
                }
                if ($layer_id) {
                    $layer_num = M('House_village_layer')->where(['id' => $layer_id])->getField('layer_name');
                } else {
                    $layer_num = $info['layer_num'];
                }

                $authentication_field = [];
                foreach($info['authentication_field'] as $k=>&$v){
                    if ('object'==gettype($v) || !isset($v['type'])) {
                        continue;
                    }
                    if($v['type'] == 3){
                        if (isset($v['value']) && $v['value']) {
                            $v['value'] = explode('#',$v['value']);
                            $v['province_idss'] = $v['value'][0];
                            $v['city_idss'] = $v['value'][1];
                        } else {
                            $v['value'] = '';
                            $v['province_idss'] = 0;
                            $v['city_idss'] = 0;
                        }
                    }
                    $authentication_field[$k] = $v;
                }
                $info['authentication_field'] = $authentication_field;
//
                $word_data = array();
                if ($info['single_id']>0) {
                    $sing_name = M('House_village_single')->where(array('id' => $info['single_id']))->getField('single_name');
                } else {
                    $sing_name = '';
                }
                if (!empty($floor_info)) {
                    $word_data['floor_name'] = $floor_info['floor_name'];
                    if (!$sing_name) {
                        $sing_name = $floor_info['floor_layer'];
                    }
                }
                $word_data['single_name'] = $sing_name;

                $word_data['layer'] = $layer_num;

                $word_data['room'] = $info['room_addrss'];
                $room_diy_name = D('House_village')->word_replce_msg($word_data,$this->village_id);
                $this->assign('room_diy_name', $room_diy_name);

                $this->assign('info', $info);

                //车位信息
                $position_list = D('House_village_bind_position')->get_user_position_bind_list(array('pigcms_id'=>$pigcms_id,'village_id'=>$this->village_id));
                //车辆信息
                $car_list = D('House_village_bind_car')->get_user_car_bind_list(array('pigcms_id'=>$pigcms_id));

                // 自定义缴费项
                $payment_list = D('')->table(array(C('DB_PREFIX').'house_village_payment_standard_bind'=>'psb', C('DB_PREFIX').'house_village_payment_standard'=>'ps', C('DB_PREFIX').'house_village_payment'=>'p'))->where("psb.pigcms_id= '".$pigcms_id."' AND p.payment_id = psb.payment_id AND ps.standard_id = psb.standard_id AND psb.village_id=".$this->village_id)->select();
                $payment_list = $payment_list ? $payment_list : array();

                // 自定义缴费项（车位）
                $position_payment_list = D('House_village_bind_position')->get_user_position_payment_list(array('pigcms_id'=>$pigcms_id));
                $payment_list = array_merge($payment_list, $position_payment_list);

                // 计算结束时间
                $cycle_type = array('Y'=>'year', 'M'=>'month', 'D'=>'day');
                foreach ($payment_list as &$value) {
                    $value['end_time'] = strtotime(date('Y-m-d',$value['start_time']).'+'.$value['cycle_sum']*$value['pay_cycle'].$cycle_type[$value['cycle_type']]);
                }

                $cycle_type = array(
                    'Y'=>'年',
                    'M'=>'月',
                    'D'=>'日',
                );
                $this->assign('cycle_type',$cycle_type);
                $this->assign('position_list',$position_list);
                $this->assign('car_list',$car_list);
                $this->assign('payment_list',$payment_list);
            }
            $is_edit_attribute=D('House_village_user_bind')->check_id_card((empty($info) ? [] : $info));
            foreach($dataList as $k=>&$v){
                if($v['type'] == 2){
                    if($v['is_system'] == 1){
                        $v['use_field'] = D('House_village')->get_system_value($v['key']);
                    }else{
                        $v['use_field'] = explode(',',$v['use_field']);
                    }
                }elseif($v['type'] == 3){

                    $v['use_field'] = [];
                }else{
                    $v['use_field'] = [];
                }
                if($v['key'] == 'is_sign_agreement' && empty($v['use_field'])){
                    $v['use_field'] = ['是','否'];
                }
                $is_edit='';
                if($set_id_code == 1 && $is_edit_attribute && in_array($v['key'],['birthday','sex'])){
                    //$is_edit='disabled="disabled"';
                    $is_edit='readonly="readonly"';
                    
                }
                $v['is_edit']=$is_edit;

            }
            $attachment_config_list = $this->getUserAttachmentFieldList($property_id);
            if (!empty($info['authentication_field'])) {
                $attachment_field_list = $this->formatUserAttachmentFieldList($attachment_config_list, $info['authentication_field']);
            } else {
                $attachment_field_list = $this->formatUserAttachmentFieldList($attachment_config_list, []);
            }
            $this->assign('dataList',$dataList);
            // 用户标签列表
            $param = [
                $this->village_id,$pigcms_id,'*',array(array('status','=','0'),array('cat_id','=','0'),'oderby'=>'id asc')
            ];

            $third_import_data_switch_judge = D('Config_customization')->getThirdImportDataSwitch();
            if ($third_import_data_switch_judge) {
                $info = D('Third_import_data')->getThirdImportUserInfo($this->village_id, $_GET['pigcms_id']);
                fdump_api([ $_GET['pigcms_id'], $info], '$info1');
//                if (!$info) {
//                    $params = ['village_id'=>$this->village_id,'bind_id'=>$pigcms_id];
//                    $result = invoke_v20_service('\app\community\model\service\third\ImportExcelService/getThirdImportUserInfo',$params);
//                    $info = $result['data']['retval'];
//                }
                if (isset($info['showArr']) && $info['showArr']) {
                    $showArr = $info['showArr'];
                } else {
                    $showArr = [];
                }
                $this->assign('showArr', $showArr);
            }
            //信息标注
            $user_tag=D('House_village_user_label')->getUserLabelMethod($house_village['street_id'],$this->village_id,$user_bind_uid,$pigcms_id);
            $this->assign('user_tag',$user_tag);
            $userOpenDoorDay=D('Config_customization')->getCustomizedUserOpenDoorDay();
            $userOpenDoorDayTmp=0;
            if($house_village['owe_property_open_door']>0 && $userOpenDoorDay){
                $userOpenDoorDayTmp=1;
            }
            $this->assign('userOpenDoorDay', $userOpenDoorDayTmp);
            $this->assign('readCard_denake', C('customization.readCard_denake'));
            $this->assign('attachment_field_list', $attachment_field_list);
            $this->assign('legacy_attachment_list', $legacy_attachment_list);
            $this->display();
        }
    }

    private function getUserAttachmentTypeConfig($type = 0)
    {
        $config = [
            5 => [
                'name' => '图片',
                'accept' => '.jpg,.jpeg,.png,.gif,.bmp',
                'allow_exts' => ['jpg', 'jpeg', 'png', 'gif', 'bmp'],
            ],
            6 => [
                'name' => 'PDF',
                'accept' => '.pdf',
                'allow_exts' => ['pdf'],
            ],
        ];
        if ($type) {
            $type = intval($type);
            return isset($config[$type]) ? $config[$type] : [];
        }
        return $config;
    }

    private function getUserAttachmentFieldList($property_id)
    {
        $property_id = intval($property_id);
        if ($property_id <= 0) {
            return [];
        }
        $type_config = $this->getUserAttachmentTypeConfig();
        $where = [
            'village_id' => 0,
            'property_id' => $property_id,
            'is_close' => 0,
            'type' => ['in', array_keys($type_config)],
        ];
        return D('House_village_data_config')->where($where)->order('sort desc')->select();
    }

    private function mergeUserAttachmentFieldData($data_post, $pigcms_id, $attachment_post = [], $attachment_files = [], $attachment_field_list = [], $origin_authentication_field = [])
    {
        if (!is_array($data_post)) {
            $data_post = [];
        }
        if (!is_array($origin_authentication_field)) {
            $origin_authentication_field = [];
        }
        $type_config = $this->getUserAttachmentTypeConfig();
        foreach ($origin_authentication_field as $origin_key => $origin_value) {
            if ($origin_key === 'user_attachment_list') {
                $data_post[$origin_key] = $origin_value;
                continue;
            }
            if (is_array($origin_value) && isset($origin_value['type']) && isset($type_config[intval($origin_value['type'])])) {
                $data_post[$origin_key] = $origin_value;
            }
        }
        foreach ($attachment_field_list as $field) {
            $field_key = isset($field['key']) ? trim($field['key']) : '';
            if (!$field_key) {
                continue;
            }
            $current_files = $this->buildUserAttachmentCurrentFiles($attachment_post, $field_key);
            $current_type_config = $this->getUserAttachmentTypeConfig(isset($field['type']) ? $field['type'] : 0);
            if (isset($attachment_files[$field_key]) && !$this->isEmptyUserAttachmentUpload($attachment_files[$field_key])) {
                $upload_result = $this->uploadUserAttachmentFiles($attachment_files[$field_key], $pigcms_id, $current_type_config ? $current_type_config['allow_exts'] : []);
                if ($upload_result['error']) {
                    $field_title = isset($field['title']) && $field['title'] ? $field['title'] : 'attachment';
                    $this->error($field_title . ': ' . $upload_result['msg']);
                }
                $current_files = array_merge($current_files, $upload_result['files']);
            }
            if (empty($current_files)) {
                unset($data_post[$field_key]);
                continue;
            }
            $file_name_list = [];
            foreach ($current_files as $file_item) {
                if (!empty($file_item['original_name'])) {
                    $file_name_list[] = $file_item['original_name'];
                }
            }
            $data_post[$field_key] = [
                'type' => isset($field['type']) ? intval($field['type']) : 0,
                'is_must' => isset($field['is_must']) ? intval($field['is_must']) : 0,
                'key' => $field_key,
                'title' => isset($field['title']) ? $field['title'] : '',
                'value' => implode(', ', $file_name_list),
                'file_list' => array_values($current_files),
            ];
        }
        return $data_post;
    }

    private function buildUserAttachmentCurrentFiles($attachment_post = [], $field_key = '')
    {
        $field_key = trim($field_key);
        if ($field_key === '') {
            return [];
        }
        $existing_url_list = isset($attachment_post['existing_url']) && is_array($attachment_post['existing_url']) ? $attachment_post['existing_url'] : [];
        $existing_name_list = isset($attachment_post['existing_name']) && is_array($attachment_post['existing_name']) ? $attachment_post['existing_name'] : [];
        $existing_size_list = isset($attachment_post['existing_size']) && is_array($attachment_post['existing_size']) ? $attachment_post['existing_size'] : [];
        $existing_suffix_list = isset($attachment_post['existing_suffix']) && is_array($attachment_post['existing_suffix']) ? $attachment_post['existing_suffix'] : [];
        $existing_time_list = isset($attachment_post['existing_time']) && is_array($attachment_post['existing_time']) ? $attachment_post['existing_time'] : [];
        $current_urls = isset($existing_url_list[$field_key]) && is_array($existing_url_list[$field_key]) ? $existing_url_list[$field_key] : [];
        $current_names = isset($existing_name_list[$field_key]) && is_array($existing_name_list[$field_key]) ? $existing_name_list[$field_key] : [];
        $current_sizes = isset($existing_size_list[$field_key]) && is_array($existing_size_list[$field_key]) ? $existing_size_list[$field_key] : [];
        $current_suffixes = isset($existing_suffix_list[$field_key]) && is_array($existing_suffix_list[$field_key]) ? $existing_suffix_list[$field_key] : [];
        $current_times = isset($existing_time_list[$field_key]) && is_array($existing_time_list[$field_key]) ? $existing_time_list[$field_key] : [];
        $current_count = max(count($current_urls), count($current_names), count($current_sizes), count($current_suffixes), count($current_times));
        $files = [];
        for ($i = 0; $i < $current_count; $i++) {
            $file_url = isset($current_urls[$i]) ? trim($current_urls[$i]) : '';
            if (!$file_url) {
                continue;
            }
            $original_name = isset($current_names[$i]) && $current_names[$i] ? $current_names[$i] : basename($file_url);
            $suffix = isset($current_suffixes[$i]) && $current_suffixes[$i] ? strtolower($current_suffixes[$i]) : strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
            $files[] = [
                'file_url' => $file_url,
                'original_name' => $original_name,
                'file_name' => basename($file_url),
                'size' => isset($current_sizes[$i]) ? intval($current_sizes[$i]) : 0,
                'suffix' => $suffix,
                'add_time' => isset($current_times[$i]) ? intval($current_times[$i]) : 0,
            ];
        }
        return $files;
    }

    private function formatUserAttachmentFieldList($data_list, $authentication_field = [])
    {
        $format_list = [];
        if (!is_array($data_list)) {
            return $format_list;
        }
        if (!is_array($authentication_field)) {
            $authentication_field = [];
        }
        foreach ($data_list as $field) {
            $field_key = isset($field['key']) ? trim($field['key']) : '';
            $type_config = $this->getUserAttachmentTypeConfig(isset($field['type']) ? $field['type'] : 0);
            if (!$field_key || empty($type_config)) {
                continue;
            }
            $field_value = isset($authentication_field[$field_key]) && is_array($authentication_field[$field_key]) ? $authentication_field[$field_key] : [];
            $file_list = [];
            if (!empty($field_value['file_list']) && is_array($field_value['file_list'])) {
                $file_list = $field_value['file_list'];
            } elseif (!empty($field_value['files']) && is_array($field_value['files'])) {
                $file_list = $field_value['files'];
            }
            $format_list[] = [
                'key' => $field_key,
                'title' => isset($field['title']) ? $field['title'] : '',
                'type' => isset($field['type']) ? intval($field['type']) : 0,
                'type_name' => $type_config['name'],
                'accept' => $type_config['accept'],
                'allow_exts_text' => implode(',', $type_config['allow_exts']),
                'files' => $this->formatUserAttachmentFiles($file_list),
            ];
        }
        return $format_list;
    }

    private function buildUserAttachmentList($pigcms_id, $attachment_post = [], $attachment_files = [])
    {
        $title_list = isset($attachment_post['title']) && is_array($attachment_post['title']) ? $attachment_post['title'] : [];
        $existing_url_list = isset($attachment_post['existing_url']) && is_array($attachment_post['existing_url']) ? $attachment_post['existing_url'] : [];
        $existing_name_list = isset($attachment_post['existing_name']) && is_array($attachment_post['existing_name']) ? $attachment_post['existing_name'] : [];
        $existing_size_list = isset($attachment_post['existing_size']) && is_array($attachment_post['existing_size']) ? $attachment_post['existing_size'] : [];
        $existing_suffix_list = isset($attachment_post['existing_suffix']) && is_array($attachment_post['existing_suffix']) ? $attachment_post['existing_suffix'] : [];
        $existing_time_list = isset($attachment_post['existing_time']) && is_array($attachment_post['existing_time']) ? $attachment_post['existing_time'] : [];
        $attachment_indexes = array_unique(array_merge(array_keys($title_list), array_keys($existing_url_list), array_keys($attachment_files)));
        sort($attachment_indexes);
        $attachment_list = [];
        foreach ($attachment_indexes as $attachment_index) {
            $title = isset($title_list[$attachment_index]) ? trim($title_list[$attachment_index]) : '';
            $files = [];
            $current_urls = isset($existing_url_list[$attachment_index]) && is_array($existing_url_list[$attachment_index]) ? $existing_url_list[$attachment_index] : [];
            $current_names = isset($existing_name_list[$attachment_index]) && is_array($existing_name_list[$attachment_index]) ? $existing_name_list[$attachment_index] : [];
            $current_sizes = isset($existing_size_list[$attachment_index]) && is_array($existing_size_list[$attachment_index]) ? $existing_size_list[$attachment_index] : [];
            $current_suffixes = isset($existing_suffix_list[$attachment_index]) && is_array($existing_suffix_list[$attachment_index]) ? $existing_suffix_list[$attachment_index] : [];
            $current_times = isset($existing_time_list[$attachment_index]) && is_array($existing_time_list[$attachment_index]) ? $existing_time_list[$attachment_index] : [];
            $current_count = max(count($current_urls), count($current_names), count($current_sizes), count($current_suffixes), count($current_times));
            for ($i = 0; $i < $current_count; $i++) {
                $file_url = isset($current_urls[$i]) ? trim($current_urls[$i]) : '';
                if (!$file_url) {
                    continue;
                }
                $original_name = isset($current_names[$i]) && $current_names[$i] ? $current_names[$i] : basename($file_url);
                $suffix = isset($current_suffixes[$i]) && $current_suffixes[$i] ? strtolower($current_suffixes[$i]) : strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
                $files[] = [
                    'file_url' => $file_url,
                    'original_name' => $original_name,
                    'file_name' => basename($file_url),
                    'size' => isset($current_sizes[$i]) ? intval($current_sizes[$i]) : 0,
                    'suffix' => $suffix,
                    'add_time' => isset($current_times[$i]) ? intval($current_times[$i]) : 0,
                ];
            }
            if (isset($attachment_files[$attachment_index]) && !$this->isEmptyUserAttachmentUpload($attachment_files[$attachment_index])) {
                if ($title === '') {
                    $this->error('请填写附件标题！');
                }
                $upload_result = $this->uploadUserAttachmentFiles($attachment_files[$attachment_index], $pigcms_id);
                if ($upload_result['error']) {
                    $this->error($upload_result['msg']);
                }
                $files = array_merge($files, $upload_result['files']);
            }
            if (empty($files)) {
                continue;
            }
            if ($title === '') {
                $this->error('请填写附件标题！');
            }
            $attachment_list[] = [
                'title' => $title,
                'files' => $files,
            ];
        }
        return $attachment_list;
    }

    private function isEmptyUserAttachmentUpload($file)
    {
        if (empty($file) || !isset($file['error'])) {
            return true;
        }
        if (is_array($file['error'])) {
            foreach ($file['error'] as $error) {
                if (intval($error) !== 4) {
                    return false;
                }
            }
            return true;
        }
        return intval($file['error']) === 4;
    }

    private function uploadUserAttachmentFiles($file, $pigcms_id, $allow_exts = [])
    {
        $pigcms_id = intval($pigcms_id);
        if ($pigcms_id <= 0) {
            return ['error' => 1, 'msg' => '用户信息不存在！'];
        }
        $attachment_id = sprintf("%09d", $pigcms_id);
        $rand_num = mt_rand(10, 99) . '/' . substr($attachment_id, 0, 3) . '/' . substr($attachment_id, 3, 3) . '/' . substr($attachment_id, 6, 3);
        $upload_dir = './upload/house/user_attachment/' . $rand_num . '/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        import('ORG.Net.UploadFile');
        $upload = new UploadFile();
        $upload->maxSize = 20 * 1024 * 1024;
        if (empty($allow_exts)) {
            foreach ($this->getUserAttachmentTypeConfig() as $attachment_type) {
                if (!empty($attachment_type['allow_exts'])) {
                    $allow_exts = array_merge($allow_exts, $attachment_type['allow_exts']);
                }
            }
        }
        $upload->allowExts = array_values(array_unique(array_map('strtolower', $allow_exts)));
        $upload->allowTypes = [];
        $upload->savePath = $upload_dir;
        $upload->saveRule = 'uniqid';
        $origin_files = $_FILES;
        $_FILES = ['attachment_file' => $file];
        $upload_status = $upload->upload($upload_dir);
        $_FILES = $origin_files;
        if (!$upload_status) {
            return ['error' => 1, 'msg' => $upload->getErrorMsg()];
        }
        $upload_list = $upload->getUploadFileInfo();
        $file_list = [];
        foreach ($upload_list as $upload_item) {
            $file_url = preg_replace('#^\./#', '', $upload_item['savepath'] . $upload_item['savename']);
            $file_list[] = [
                'file_url' => $file_url,
                'original_name' => $upload_item['name'],
                'file_name' => $upload_item['savename'],
                'size' => intval($upload_item['size']),
                'suffix' => strtolower($upload_item['extension']),
                'add_time' => time(),
            ];
        }
        return ['error' => 0, 'files' => $file_list];
    }

    private function formatUserAttachmentFiles($file_list)
    {
        $format_files = [];
        if (!is_array($file_list)) {
            return $format_files;
        }
        foreach ($file_list as $file_item) {
            $file_url = isset($file_item['file_url']) ? trim($file_item['file_url']) : '';
            if (!$file_url) {
                continue;
            }
            $original_name = isset($file_item['original_name']) && $file_item['original_name'] ? $file_item['original_name'] : basename($file_url);
            $suffix = isset($file_item['suffix']) && $file_item['suffix'] ? strtolower($file_item['suffix']) : strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
            $format_files[] = [
                'file_url' => $file_url,
                'file_url_full' => getAttachmentUrl($file_url),
                'original_name' => $original_name,
                'file_name' => isset($file_item['file_name']) && $file_item['file_name'] ? $file_item['file_name'] : basename($file_url),
                'size' => isset($file_item['size']) ? intval($file_item['size']) : 0,
                'suffix' => $suffix,
                'add_time' => isset($file_item['add_time']) ? intval($file_item['add_time']) : 0,
            ];
        }
        return $format_files;
    }

    private function formatUserAttachmentList($attachment_list)
    {
        $format_list = [];
        if (!is_array($attachment_list)) {
            return $format_list;
        }
        foreach ($attachment_list as $attachment_item) {
            $title = isset($attachment_item['title']) ? trim($attachment_item['title']) : '';
            $files = $this->formatUserAttachmentFiles(isset($attachment_item['files']) ? $attachment_item['files'] : []);
            if (empty($files)) {
                continue;
            }
            $format_list[] = [
                'title' => $title,
                'files' => $files,
            ];
        }
        return $format_list;
    }

    // 删除缴费项目
    public function payment_del(){
        if (intval($_POST['pigcms_id'])) {
            //业主-删除缴费项 权限
            if (0&&!in_array(114, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }
            $res = D('House_village_payment_standard_bind')->where(array('bind_id'=>intval($_POST['bind_id']),'village_id'=>$this->village_id,'pigcms_id'=>intval($_POST['pigcms_id'])))->delete();
        }elseif (intval($_POST['position_id'])) {
            // 车位-删除缴费项 权限
            if (0&&!in_array(262, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }
            $res = D('House_village_payment_standard_bind')->where(array('bind_id'=>intval($_POST['bind_id']),'village_id'=>$this->village_id,'position_id'=>intval($_POST['position_id'])))->delete();
        }

        if($res){
            exit(json_encode(array('error'=>1,'msg'=>'删除成功！')));
        }else{
            exit(json_encode(array('error'=>2,'msg'=>'删除失败！')));
        }
    }

    //添加缴费项
    public function payment_add(){
        //业主-添加缴费项 权限
        if (0&&!in_array(97, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $payment_list = D('House_village_payment')->where(array('village_id'=>$this->village_id))->select();
        $this->assign('payment_list',$payment_list);
        $this->display();
    }

    // 选择缴费标准
    public function payment_standard_choice(){
        // header('Content-Type: application/json; charset=utf-8');
        if (!$_POST['payment_id']) {
            exit(json_encode(array('error'=>1,'msg'=>'参数传递错误','data'=>array())));
        }
        $where = 'payment_id='.$_POST['payment_id'];
        if ($_POST['bind_type']==2) { // 绑定车位
            $where .= ' AND (pay_type=1 OR  metering_mode_type=3)';
        }else{ //绑定业主
            $where .= ' AND  metering_mode_type<>3';
        }
        $list = D('House_village_payment_standard')->where($where)->select();
        $html = '<option value="">——选择收费标准——</option>';

        $cycle_type = array(
            'Y'=>'年',
            'M'=>'月',
            'D'=>'日',
        );

        foreach ($list as $key => $value) {
            $val = '';
            if($value['pay_type'] == 1){
                $val .= '缴费模式: 固定费用;';
            }else{
                $val .= '缴费模式: 按金额*数量;';
                $val .= '计量方式: '.$value['metering_mode'].';';
            }
            $val .= '缴费金额: '.$value['pay_money'].'; ';
            $val .= '周期类型: '.$cycle_type[$value['cycle_type']].'; ';
            $val .= '缴费周期: '.$value['pay_cycle'].'('.$cycle_type[$value['cycle_type']].') / 周期; ';
            $val .= '周期上限: '.$value['max_cycle'].' (周期) ; ';
            $html.= '<option data-max_cycle="'.$value['max_cycle'].'" data-pay_type="'.$value['pay_type'].'" data-metering_mode="'.$value['metering_mode'].'" data-metering_mode_type="'.$value['metering_mode_type'].'" value="'.$value['standard_id'].'">'.$val.'</option>';
        }
        exit(json_encode(array('error'=>0,'data'=>$html)));
    }

    // 用户签订缴费项合同
    public function user_payment_add(){
        //业主-添加缴费项 权限
        if (0&&!in_array(97, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $payment_info = D('House_village_payment')->where(array('village_id'=>$this->village_id,'payment_id'=>$_POST['payment_id']))->find();
        $standard_info = D('House_village_payment_standard')->where(array('payment_id'=>$_POST['payment_id'],'standard_id'=>$_POST['standard_id']))->find();

        $cycle_type = array('Y'=>'year', 'M'=>'month', 'D'=>'day', );
        $data['payment_id'] = $_POST['payment_id'];
        $data['standard_id'] = $_POST['standard_id'];

        $data['village_id'] = $this->village_id;
        if($_POST['start_time'] == ''){
            $_POST['start_time'] = date('Y-m-d',time());
        }

        if($_POST['cycle_sum'] == ''){
            $_POST['cycle_sum'] = $standard_info['max_cycle'];
        }
        $data['start_time'] = strtotime($_POST['start_time']);
        $data['end_time'] = strtotime($_POST['start_time'].'+'.$_POST['cycle_sum']*$standard_info['pay_cycle'].$cycle_type[$standard_info['cycle_type']]);
        $data['cycle_sum'] = $_POST['cycle_sum'];
        $data['pay_cycle'] = 0;
        $data['remarks'] = $_POST['remarks'];

        $database_house_village_user_bind = D('House_village_user_bind');

        if(empty($_POST['uid'])){
            $data['pigcms_id'] = $_POST['pigcms_id'];
            if($_POST['metering_mode_type'] == 1){ //房屋面积
                $housesize = D('House_village_user_bind')->where(array('pigcms_id'=>$_POST['pigcms_id']))->getField('housesize');
                $data['metering_mode_val'] = $housesize;
            } elseif($_POST['metering_mode_type'] == 3){ //车位面积
                $condition_table  = array(C('DB_PREFIX').'house_village_bind_position'=>'b',C('DB_PREFIX').'house_village_parking_position'=>'p');
                $condition_where = " `p`.`position_id` = `b`.`position_id` AND `b`.`user_id` =".$_POST['pigcms_id'];
                $condition_field = '`p`.`position_area`';
                $position_list = D()->field($condition_field)->table($condition_table)->where($condition_where)->select();
                if ($position_list) {
                    $position_area = 0;
                    foreach ($position_list as $key => $value) {
                        $position_area += $value['position_area'];
                    }
                    $data['metering_mode_val'] = $position_area;
                }else{
                    $data['metering_mode_val'] = 0;
                }
            }else{
                $data['metering_mode_val'] = $_POST['metering_mode_val'];
            }
            if($standard_info['pay_type'] == 1){
                $data['metering_mode_val'] = '';
            }
            $res = D('House_village_payment_standard_bind')->data($data)->add();

            // 发送微信通知
            if ($data['start_time']<time()) {
                $bind_condition['pigcms_id'] = $data['pigcms_id'];
                $bind_condition['village_id'] = $this->village_id;
                $user_bind_list = $database_house_village_user_bind->get_cashier_unpaid_list($bind_condition,0,0);
                if($user_bind_list['list']){
                    $database_house_village_user_bind->send_weixin_pay($this->village_id,$user_bind_list['list']);
                }
            }
        }else{
            $pigcms_id=explode(',',$_POST['uid']);
            foreach ($pigcms_id as $key => $value) {
                if($value > 0){
                    $data['pigcms_id'] = $value;
                    if($_POST['metering_mode_type'] == 1){

                        $housesize = D('House_village_user_bind')->where(array('pigcms_id'=>$value))->getField('housesize');
                        $data['metering_mode_val'] = $housesize;
                    } elseif($_POST['metering_mode_type'] == 3){ //车位面积
                        $condition_table  = array(C('DB_PREFIX').'house_village_bind_position'=>'b',C('DB_PREFIX').'house_village_parking_position'=>'p');
                        $condition_where = " `p`.`position_id` = `b`.`position_id` AND `b`.`user_id` =".$_POST['pigcms_id'];
                        $condition_field = '`p`.`position_area`';
                        $position_list = D()->field($condition_field)->table($condition_table)->where($condition_where)->select();
                        if ($position_list) {
                            $position_area = 0;
                            foreach ($position_list as $key => $value) {
                                $position_area += $value['position_area'];
                            }
                            $data['metering_mode_val'] = $position_area;
                        }else{
                            $data['metering_mode_val'] = 0;
                        }
                    }else{
                        $data['metering_mode_val'] = $_POST['metering_mode_val'];
                    }
                    if($standard_info['pay_type'] == 1){
                        $data['metering_mode_val'] = '';
                    }
                    $res = D('House_village_payment_standard_bind')->data($data)->add();
                }
            }
            // 发送微信通知
            if ($_POST['start_time']<time()) {
                $bind_condition['pigcms_id'] = trim($_POST['uid'],',');
                $bind_condition['village_id'] = $this->village_id;
                $user_bind_list = $database_house_village_user_bind->get_cashier_unpaid_list($bind_condition,0,0);
                if($user_bind_list['list']){
                    $database_house_village_user_bind->send_weixin_pay($this->village_id,$user_bind_list['list']);
                }
            }
        }

        if($res){
            $this->success('保存成功！');
        }else{
            $this->error('保存失败！');
        }
    }

    // 添加业主
    public function user_add(){
        //业主-添加 权限
        if (0&&!in_array(92, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $database_house_village_user_vacancy = D('House_village_user_vacancy');
        $database_house_village_floor = D('House_village_floor');

        //先判断单元
        $floor_num = $database_house_village_floor->where(array('village_id'=>$this->village_id,'status'=>1))->count();
        if($floor_num <= 0){
            $this->error('请先添加单元',U('Unit/index'));
        }
        $vacancy_where = [];
        $vacancy_where['status'] = ['neq', 4];
        $vacancy_where['is_del'] = 0;
        $vacancy_where['village_id'] = $this->village_id;
        $countVacancy = $database_house_village_user_vacancy->where($vacancy_where)->count();

        if(!$countVacancy){
            $this->error('请先导入房间',U('Unit/import_village'));
        }
        $house_village = M('House_village')->where(['village_id'=>$this->village_id])->field('set_id_code,street_id,community_id')->find();
        $set_id_code=intval($house_village['set_id_code']);
        $this->assign('set_id_code' , $set_id_code);
        $is_new_charge=D('House_village')->checkNewCharge($this->house_session['property_id']);
        $this->assign('new_charge_time', $is_new_charge);
        $this->assign('readCard_denake', C('customization.readCard_denake'));
        if (IS_POST) {

            $nmv_card = trim($_POST['nmv_card']);
            unset($_POST['nmv_card']);

            $data_post = $_POST;
            unset($data_post['user_name']);
            unset($data_post['phone']);
            unset($data_post['id_card']);
            unset($data_post['ic_card']);
            unset($data_post['single_id']);
            unset($data_post['attribute']);
            unset($data_post['floor_id']);
            unset($data_post['layer_id']);
            unset($data_post['layer_room']);
            unset($data_post['layer_num']);
            unset($data_post['room_num']);
            unset($data_post['vacancy_id']);
            unset($data_post['housesize']);
            unset($data_post['water_price']);
            unset($data_post['electric_price']);
            unset($data_post['gas_price']);
            unset($data_post['park_price']);
            unset($data_post['memo']);
            unset($data_post['property_starttime']);
            unset($data_post['property_endtime']);
            unset($data_post['province_idss']);
            unset($data_post['city_idss']);
            $_POST['user_name'] = trim($_POST['user_name']);
            $_POST['phone'] = trim($_POST['phone']);

            foreach ($data_post as $k => $v) {
                if ($v['is_must'] == 1 && empty($v['value'])) {
//                    $this->error($v['title'].'为必填项！');
                }
            }
            foreach($data_post as $k=>&$v){
                if($v['key'] == 'native_place'){
                    $v['value'] = $_POST['province_idss'].'#'.$_POST['city_idss'];
                }
            }
            unset($_POST['province_idss']);
            unset($_POST['city_idss']);

            if($set_id_code == 1){
                if(!$_POST['id_card']){
                    $this->error('身份证号码不能为空！');
                }
                if(!strpos($_POST['id_card'],'*') && !preg_match('#(^\d{15}$)|(^\d{17}(\d|X)$)#', $_POST['id_card'])){
                    $this->error('请填写正确的身份证号码');
                }
            }
            $data['authentication_field'] = serialize($data_post);

            if(empty($_POST['user_name'])){
                $this->error('业主名不能为空【注意回到添加页面选择房间重新选择下】！');
            }

            if(empty($_POST['floor_id'])){
                $this->error('单元名称不能为空【注意回到添加页面选择房间重新选择下】！');
            }
            if(empty($_POST['layer_id'])){
                $this->error('楼层不能为空【注意回到添加页面选择房间重新选择下】！');
            }

            if($_POST['property_starttime'] > $_POST['property_endtime']){
                $this->error('请填写正确的物业时间【注意回到添加页面选择房间重新选择下】！');
            }

            $_POST['property_starttime'] && $_POST['property_starttime'] = strtotime($_POST['property_starttime'].'00:00:00');
            $_POST['property_endtime'] &&  $_POST['property_endtime'] = strtotime($_POST['property_endtime'].'23:59:59');


            $user_label_arr=[
                'user_political_affiliation'=>isset($_POST['user_political_affiliation'])? $_POST['user_political_affiliation'] : 0,
                'user_party_id'=>isset($_POST['user_party_id'])? $_POST['user_party_id'] : 0,
                'user_special_groups'=>isset($_POST['user_special_groups']) ? $_POST['user_special_groups'] : [],
                'user_focus_groups'=>isset($_POST['user_focus_groups']) ? $_POST['user_focus_groups'] : [],
                'user_vulnerable_groups'=>isset($_POST['user_vulnerable_groups']) ? $_POST['user_vulnerable_groups'] : [],
                'user_label_groups'=>isset($_POST['user_label_groups']) ? $_POST['user_label_groups'] : [],
            ];
            unset($_POST['user_political_affiliation'],$_POST['user_party_id'],$_POST['user_special_groups'],$_POST['user_focus_groups'],$_POST['user_vulnerable_groups'],$_POST['user_label_groups']);


            $floor_name = $_POST['floor_name'];
            $floor_layer = $_POST['floor_layer'];
            $now_vacancy = $database_house_village_user_vacancy->where(array('pigcms_id'=>$_POST['vacancy_id']))->find();
            if (!isset($now_vacancy)) {
                $this->error('该房间信息不存在【注意回到添加页面选择房间重新选择下】！');
            }

            //增加楼栋判断
            $layer_num = $now_vacancy['layer'];
			
            //增加楼栋时判断
            $room_num = $now_vacancy['room'];

            $database_house_village_floor = D('House_village_floor');
            $database_house_village_user_bind = D('House_village_user_bind');
            $database_house_village_user_vacancy = D('House_village_user_vacancy');
            $database_user = D('User');

            //增加楼栋判断
            $single_where['id'] = $_POST['single_id'] + 0;
            $single_where['status'] = 1;
            $single_where['village_id'] = $this->village_id;
            $single = M('House_village_single')->where($single_where)->find();
            if(!isset($single)){
                $this->error('楼栋不存在或者状态没开启，请查看社区中心，楼栋管理-楼栋列表【注意回到添加页面选择房间重新选择下】！');
            }

            $where['floor_id'] = $_POST['floor_id'] + 0;
            $where['status'] = 1;
            $where['village_id'] = $this->village_id;

            $house_village_floor_info = $database_house_village_floor->where($where)->find();
            if (!isset($house_village_floor_info)) {
                $this->error('单元不存在，请查看小区管理后台数据中心中楼栋管理-楼栋列表中的单元列表【注意回到添加页面选择房间重新选择下】！');
            }

            // 增加楼层判断
            $where_layer = [];
            $where_layer['id'] = $_POST['layer_id'] + 0;
            $where_layer['status'] = 1;
            $where_layer['village_id'] = $this->village_id;
            $house_village_layer = M('House_village_layer')->where($where_layer)->find();
            if (!isset($house_village_layer)) {
                $this->error('楼层不存在，请查看小区管理后台数据中心中楼栋管理-楼栋列表-单元列表中的楼层列表【注意回到添加页面选择房间重新选择下】！');
            }

            $vacancy_where['status'] = array('in',array(1,3));
            $vacancy_where['pigcms_id'] = $_POST['vacancy_id'] + 0;//新加楼栋处理房间
            $vacancy_info = $database_house_village_user_vacancy->where($vacancy_where)->find();

            if (!isset($vacancy_info)) {
                $this->error('该房间信息不存在【注意回到添加页面选择房间重新选择下】！');
            }
            $tmpWhereArr=array(
                'vacancy_id'=>$vacancy_where['pigcms_id'],
                'type'=>array('in',array(0,3)),
                'status' => ['neq', 4]
            );
            $userBindTmp = $database_house_village_user_bind->where($tmpWhereArr)->field('pigcms_id,village_id,name')->find();
            if(!empty($userBindTmp)){
                $this->error('该房间已经存在业主信息了【注意回到添加页面选择房间重新选择下】！');
            }
            $data['ic_card'] = trim($_POST['ic_card']) ? trim($_POST['ic_card']) : '';
            if ($data['ic_card']) {
                // 检查下是否当前ic卡已经被使用 状态处于正常的
                $where_repeat = [
                    'ic_card' => $data['ic_card'],
                    'status' => 1
                ];
                $repeat = $database_house_village_user_bind->where($where_repeat)->field('pigcms_id,village_id,name')->find();
                if (!empty($repeat)) {
                    if ($repeat['village_id']==$this->village_id) {
                        // 同一个小区提示具体重复人员
                        $this->error("当前用户【".$repeat['name']."】已经使用了该卡号");
                    } else {
                        $where_village = [
                            'village_id' => $repeat['village_id']
                        ];
                        $village_name = M('House_village')->where($where_village)->getField('village_name');
                        // 不同小区提示小区
                        $this->error("当前【".$village_name."】小区用户已经使用了该卡号");
                    }
                }
            }
            $data['pile_card_no'] = trim($_POST['pile_card_no']) ? trim($_POST['pile_card_no']) : '';
            if ($data['pile_card_no']) {
                // 检查下是否当前ic卡已经被使用 状态处于正常的
                $where_repeat = [
                    'pile_card_no' => $data['pile_card_no'],
                    'status' => 1
                ];
                $repeat = $database_house_village_user_bind->where($where_repeat)->field('pigcms_id,village_id,name')->find();
                if (!empty($repeat)) {
                    if ($repeat['village_id']==$this->village_id) {
                        // 同一个小区提示具体重复人员
                        $this->error("当前用户【".$repeat['name']."】已经使用了该卡号");
                    } else {
                        $where_village = [
                            'village_id' => $repeat['village_id']
                        ];
                        $village_name = M('House_village')->where($where_village)->getField('village_name');
                        // 不同小区提示小区
                        $this->error("当前【".$village_name."】小区用户已经使用了该卡号");
                    }
                }
            }

            // 非机动车卡号
            if ($nmv_card) {
                // 检查下是否当前ic卡已经被其他人使用 状态处于正常的
                $where_nmv = [
                    'nmv_card' => $nmv_card
                ];
                $nmv = $database_house_village_user_bind->where($where_nmv)->field('pigcms_id,village_id,name')->find();
                if (!empty($nmv)) {
                    if ($nmv['village_id']==$this->village_id) {
                        // 同一个小区提示具体重复人员
                        $this->error("当前用户【".$nmv['name']."】已经使用了该卡号");
                    } else {
                        $where_village = [
                            'village_id' => $nmv['village_id']
                        ];
                        $village_name = M('House_village')->where($where_village)->getField('village_name');
                        // 不同小区提示小区
                        $this->error("当前【".$village_name."】小区用户已经使用了该卡号");
                    }
                }
            }

            if($_POST['phone']){
                $now_user = D('User')->where(['phone'=>$_POST['phone']])->find();
            }else{
                $now_user = array();
            }
			
			//注册新用户
            if(empty($now_user)){
				$data_user = array(
					'nickname' 	=> $_POST['user_name'],
					'phone' 	=> $_POST['phone'],
					'source' 	=> 'houseautoreg',
				);
				$reg_result = D('User')->autoreg($data_user);
				if($reg_result['error_code']){
					$this->error($reg_result['error_code']);
				}
				$now_user['uid'] = $reg_result['uid'];
            }
            $data['uid'] = $now_user['uid'] ? intval($now_user['uid']) : 0;
			

            //检测用户是否已存在
            $Map['usernum'] =  $this->village_id .'_'. $vacancy_info['usernum'];
            $info = $database_house_village_user_bind->field('`pigcms_id`,`uid`,`name`,`phone`,`usernum`')->where($Map)->find();
            if ($info && ($info['uid'] || $info['name'] || $info['phone'])) {
                $this->error('业主名为' . $info['name'] . ' 已存在该物业编号！物业编号重复【注意回到添加页面选择房间重新选择下】');
            }


            $data['name'] = $_POST['user_name'];
            $data['phone'] = $_POST['phone'];
            $data['floor_id'] = $_POST['floor_id'];
            $data['layer_id'] = $_POST['layer_id']; 	//2020/8/6 新增
            $data['id_card'] = $_POST['id_card'] ? $_POST['id_card'] : '';
            $data['water_price'] = $_POST['water_price'] ? trim($_POST['water_price']):0;
            $data['electric_price'] = $_POST['electric_price'] ? trim($_POST['electric_price']):0;
            $data['gas_price'] = $_POST['gas_price'] ? trim($_POST['gas_price']):0;
            $data['park_flag'] = $_POST['park_flag'] + 0;
            $data['room_addrss'] = $room_num;
            $data['usernum'] = $this->village_id .'_'.$vacancy_info['usernum'];
            $data['vacancy_id'] = $_POST['vacancy_id'] + 0;
            $data['attribute'] = $_POST['attribute'] + 0;
            $data['apply_time'] = time();
            $data['adopt_time'] = time();

            // 手机号不能与当前房屋中待审核和审核通过的手机号一样
            if ($data['phone']) {
                $bind_condition = [];
                $bind_condition['vacancy_id'] = $data['vacancy_id'];
                $bind_condition['phone'] = $data['phone'];
                $bind_condition['type'] = ['in', [0,1,2,3]];
                $bind_condition['status'] = array('in' , [1,2]);
                $other_bind_info = $database_house_village_user_bind->where($bind_condition)->find();
                if($other_bind_info){
                    $this->error('该手机号已绑定或已申请绑定此房间');
                }
            }
            // 姓名不能与当前房屋中待审核和审核通过的姓名一样
            if ($data['name'] && $data['phone']) {
                $bind_condition = [];
                $bind_condition['vacancy_id'] = $data['vacancy_id'];
                $bind_condition['name'] = $data['name'];
                $bind_condition['phone'] = $data['phone'];
                $bind_condition['type'] = ['in', [0,1,2,3]];
                $bind_condition['status'] = array('in' , [1,2]);
                $other_bind_info = $database_house_village_user_bind->where($bind_condition)->find();
                if($other_bind_info){
                    $this->error("{$data['name']}已绑定或已申请绑定此房间");
                }
            }

            if($memo = htmlspecialchars(trim($_POST['memo']))){
                $data['memo'] = $memo;
            }

            if(isset($_POST['park_flag'])){
                $data['park_price'] = $_POST['park_price'];
            }

            $data['housesize'] = $vacancy_info['housesize'];
            $data['layer_num'] = $layer_num;
            //$data['address'] = $house_village_floor_info['floor_layer'] . $house_village_floor_info['floor_name'] . $layer_num . '#' . $room_num;
            //修改楼栋信息
            $data['address'] = $single['single_name'] . $house_village_floor_info['floor_name'] . $layer_num . '#' . $room_num;
            $data['village_id'] = $this->village_id;
            $data['add_time'] = time();

            $data['property_starttime'] = $_POST['property_starttime'];
            $data['property_endtime'] = $_POST['property_endtime'];

            //进入数据库楼栋新数据
            $data['single_id'] = $single['id'];
            $data['layer_id'] = $house_village_layer['id'];
            if ($info['pigcms_id']) {
                $set_id = $database_house_village_user_bind->where(['pigcms_id' => $info['pigcms_id']])->data($data)->save();
                if ($set_id) {
                    $insert_id = $info['pigcms_id'];
                }
            } else {
                $insert_id = $database_house_village_user_bind->data($data)->add();
            }
            if($insert_id){

                //更改房间房主信息  uid,name,phone,type=0,status=3,park_flag,add_time,
                $data_info['uid'] = $data['uid'];
                $data_info['name'] = $_POST['user_name'];
                $data_info['phone'] = $_POST['phone'];
                $data_info['type'] = 0;
                $data_info['status'] = 3;
                $data_info['park_flag'] = $_POST['park_flag'];
                $data_info['add_time'] = time();

                $where_info['pigcms_id'] = $_POST['vacancy_id'];
                $where_info['village_id'] = $this->village_id;


                $database_house_village_user_vacancy->data($data_info)->where($where_info)->save();

                if (intval($insert_id)) {
                    $change_param = [];
                    $change_param['pigcms_id'] = $insert_id;
                    D('House_village_user_vacancy')->change_user_bind_number($change_param);
                }
                $arr = array();
                $arr['param'] = serialize(array('bind_id' =>$insert_id,'type'=>'user'));
                $arr['plan_time'] = time()+60;
                $arr['space_time'] = 0;
                $arr['add_time'] = time();
                $arr['file'] = 'sub_house_village_third';
                $arr['time_type'] = 1;
                $arr['unique_id'] = 'sub_house_village_third_user_'.$insert_id;
                $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
                M('process_sub_plan')->data($arr)->add();
                // 添加业主，进行同步
//                D('House_village_face_door')->modify_user($insert_id);
                D('House_face_img')->house_user_face_device_info($data['uid'],$insert_id);

                // 合并标签
                $label_arr = array_merge([$_POST['political_outlook']],(array)$_POST['special_population'],(array)$_POST['key_population'],(array)$_POST['care_object']);
                $label_ids = implode(',', $label_arr);
                $param = [$label_ids,$insert_id,$this->village_id];
                // 保存标签
                //invoke_v20_service('\app\community\model\service\HouseVillageUserLabelBindService/addUserLabelBind',$param);

                $user_label_arr['uid']=$data['uid'];
                $user_label_arr['pigcms_id']=$insert_id;
                //todo 信息标注编辑-编辑
                D('House_village_user_label')->saveUserLabelMethod($user_label_arr);

                //todo 轨迹信息 用户添加
                D('House_user_trajectory_log')->addLog(4,$this->village_id,0,$data['uid'],$insert_id,2,'注册用户','后台新增了一条用户数据');

                //todo 用户同步写入属性表记录
                invoke_v20_service('\app\community\model\service\TaskReleaseService/userAttributeRecord', ['pigcms_id'=>$insert_id,'field'=>$data_post]);

                if($nmv_card){
                    invoke_v20_service('\app\community\model\service\CloudIntercomService/saveUsernmvCard',[$insert_id,$nmv_card]);
                }
                if($this->huizhisq && $data['ic_card']){ // 门禁卡
                    invoke_v20_service('\app\community\model\service\CloudIntercomService/sendIcCardToDevice',[$data['ic_card'],1]);
                }

                $hik_info_vision_i_community_app_key=C('config.hik_info_vision_i_community_app_key');
                $hik_info_vision_i_community_app_secret=C('config.hik_info_vision_i_community_app_secret');
                if($hik_info_vision_i_community_app_key && $hik_info_vision_i_community_app_secret){
                    $hikRet=invoke_v20_service('\app\community\model\service\Device\HikInfoVisionICommunityService/updateBindUserToHikCommunity', ['village_id'=>$this->village_id,'pigcms_id'=>$insert_id]);
                }
                // 绑定车辆信息
                if($_POST['car_id']){
                    $carId = explode(',',$_POST['car_id']);
                    $add_data = [];
                    foreach ($carId as $kk => $vv){
                        if(!empty($vv)){
                            $add_data[] = [
                                'village_id' => $this->village_id,
                                'user_id' => $insert_id,
                                'car_id' => $vv,
                                'uid' => intval($data['uid']),
                            ];
                        }
                    }
                    if(!empty($add_data)){
                        D('House_village_bind_car')->addAll($add_data);
                    }
                }
				
			if($_GET['from_type']==1){
				$this->success('添加成功！');
			}else{
				$this->success('添加成功！',U('index'));
			}
                
            }else{
                $this->error('添加失败【注意回到添加页面选择房间重新选择下】！');
            }
        } else {
            $where_village['village_id'] = $this->village_id;
            $property_id = D('House_village')->where($where_village)->getField('property_id');
            $where_data_config = array(
                'village_id'=>0,
                'property_id'=>$property_id,
                'is_display'=>1,
                'is_close'=>0
            );
            $dataList = D('House_village_data_config')->where($where_data_config)->order('sort desc')->select();
//            $dataList = D('House_village_data_config')->where(array('village_id'=>$this->village_id,'is_display'=>1))->select();
            foreach($dataList as $k=>&$v){
                if($v['type'] == 2){
                    if($v['is_system'] == 1){
                        $v['use_field'] = D('House_village')->get_system_value($v['key']);
                    }else{
                        $v['use_field'] = explode(',',$v['use_field']);
                    }
                }elseif($v['type'] == 3){

                    $v['use_field'] = [];
                }else{
                    $v['use_field'] = [];
                }
            }
            //var_dump($dataList);
            $this->assign('dataList',$dataList);

            // 用户标签列表
            $param = [
                $this->village_id,0,'*',array(array('status','=','0'),'oderby'=>'id asc')
            ];

            //楼栋
            $single_list = M('House_village_single')->where(array('village_id'=>$this->village_id,'status'=>1))->select();
            if($single_list){
                $this->assign('single_list',$single_list);
            }else{
                $this->assign('single_list',[]);
            }
            $database_house_village_floor = D('House_village_floor');
            $condition = [];
            $condition['village_id'] = $this->village_id;
            $condition['status'] = 1;
            $countFloor = $database_house_village_floor->where($condition)->count();

            //信息标注
            $user_tag=D('House_village_user_label')->getUserLabelMethod($house_village['street_id'],$this->village_id);
            $this->assign('user_tag',$user_tag);

            if(!$countFloor){
                $this->error('单元不存在，请先添加！');
            }
            $this->assign('village_id',$this->village_id);

            $this->display();

        }
    }

    public function ajax_get_floor(){
        $single_id = $_POST['cat_id'];

        if(!$single_id){
            exit(json_encode(array('status'=>0,'cat_list'=>[])));
        }

        $database_house_village_floor= D('House_village_floor');
        $where['single_id'] = $single_id;
        $where['status'] = 1;
        $where['village_id'] = $this->house_session['village_id'];

        $cat_list = $database_house_village_floor->field('floor_id,floor_name')->where($where)->select();
//        var_dump(D('House_village_floor')->_sql());
        if($cat_list){
            exit(json_encode(array('status'=>1,'cat_list'=>$cat_list)));
        }else{
            exit(json_encode(array('status'=>0,'cat_list'=>[])));
        }
    }



    public function ajax_get_layer(){
        if(IS_AJAX){
            $floor_id = $_POST['floor_id'] + 0;
            $database_house_village_user_vacancy = D('House_village_user_vacancy');

            $where['floor_id'] = $floor_id;
            $where['status'] = 1;
            $result = $database_house_village_user_vacancy->house_village_user_vacancy_page_list($where ,true ,'pigcms_id desc' , 999999999);

            if(!$result){
                exit(json_encode(array('status'=>0,'msg'=>'数据处理有误！')));
            }else{
                if(!$result['status']){
                    exit(json_encode(array('status'=>0,'msg'=>$result['msg'])));
                }else{
                    exit(json_encode(array('status'=>1,'list'=>$result['result']['list'])));
                }
            }
        }else{
            $this->error('访问页面有误！~~~~');
        }
    }


    public function ajax_user_bind(){
        //检测用户是否已存在
        $Map['usernum'] =  $this->village_id . '-' . $_POST['usernum'];
        $database_house_village_user_bind = D('House_village_user_bind');
        if ($database_house_village_user_bind->field('`usernum`')->where($Map)->find()) {
            exit(json_encode(array('status'=>1)));
        }else{
            exit(json_encode(array('status'=>0)));
        }
    }


    // 导入业主
    public function user_import()
    {
        //业主-添加 权限
        /*
        if (0&&!in_array(92, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        */

        if (IS_POST) {
            if ($_FILES['file']['error'] != 4) {
                set_time_limit(0);
                ini_set('memory_limit', '2048M');

                import('ORG.Util.Dir');
                Dir::delDirnotself('./upload/excel/villageuser');

                $upload_dir = './upload/excel/villageuser/' . date('Ymd') . '/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
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

                $is_new_charge=D('House_village')->checkNewCharge($this->house_session['property_id']);


                $where_village['village_id'] = $this->village_id;
                $property_id = D('House_village')->where($where_village)->getField('property_id');
                $where_data_config = array(
                    'village_id'=>0,
                    'property_id'=>$property_id,
                    'is_display'=>1,
                    'is_close'=>0
                );
                $dataList = D('House_village_data_config')->where($where_data_config)->order('sort desc')->select();
                $is_func_cloudmrsqfw_special=D('Config_customization')->getFuncCloudmrsqfwSpecial();
                $house_village_user_label_serve= D('House_village_user_label');
                if ($is_new_charge) {
                    $errKey = 'O';
                    if ($upload->upload()) {
                        $uploadList = $upload->getUploadFileInfo();
                        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel/IOFactory.php';
                        $path = $uploadList['0']['savepath'] . $uploadList['0']['savename'];
                        $fileType = PHPExcel_IOFactory::identify($path); //文件名自动判断文件类型

                        $objReader = PHPExcel_IOFactory::createReader($fileType);
                        $excelObj = $objReader->load($path);
                        $result = $excelObj->getActiveSheet()->toArray(null, true, true, true);
                        unlink($path);    //删除物业上传的excel表格
                        $database_user = D('User');
                        $error_arr = array();
                        $A1Tips='';
                        if (!empty($result) && is_array($result) && count($result)>1) {
                            $headerArr=$result[1];
                            unset($result[1]);
                            $err_index = 2;
                            if($is_func_cloudmrsqfw_special){
                                $errKey = 'AI';
                                $A1Tips=$headerArr['A'];
                                $headerArr=$result[2];
                                unset($result[2]);
                                $err_index = 3;
                            }else{
                                $A1Tips='';
                            }
                            $last_user_id = 0;
                            $err_msg = '';
                            $house_village_user_bind_serve=D('House_village_user_bind');
                            $errArrKey = 0;// 错误数据键值
                            foreach ($result as $kk => $vv) {
                                if (array_sum($vv) == 0 ||(strpos($vv['A'],'业主名')!==false && strpos($vv['B'],'手机号')!==false && strpos($vv['C'],'所属楼栋')!==false && strpos($vv['D'],'所属单元')!==false)) {
                                    continue;
                                }
                                if ($vv['A'] === null && $vv['B'] === null && $vv['C'] === null && $vv['D'] === null && $vv['E'] === null && $vv['F'] === null && $vv['G'] === null) continue;
                                //2020/8/6 优化  start

                                $tmpdata = array();
                                $name = trim($vv['A']);
                                if (empty($name)) {
                                    $vv[$errKey] = '请填写业主名！';
                                    $error_arr[$errArrKey] = $vv;
                                    $errArrKey++;
                                    continue;
                                }
                                $phone = trim($vv['B']);
                                if (!preg_match('/^[0-9]{11}$/', $phone)) {
                                    $vv[$errKey] = '请填写正确的手机号！';
                                    $error_arr[$errArrKey] = $vv;
                                    $errArrKey++;
                                    continue;
                                }
                                $single_name = trim($vv['C']);
                                if (empty($vv['C'])) {
                                    $vv[$errKey] = '请填写楼号！';
                                    $error_arr[$errArrKey] = $vv;
                                    $errArrKey++;
                                    continue;
                                }
                                $floor_name = htmlspecialchars(trim($vv['D']), ENT_QUOTES);
                                if (empty($floor_name)) {
                                    $vv[$errKey] = '请填写单元名称！';
                                    $error_arr[$errArrKey] = $vv;
                                    $errArrKey++;
                                    continue;
                                }
                                $layer_name = htmlspecialchars(trim($vv['E']), ENT_QUOTES);
                                if (empty($layer_name)) {
                                    $vv[$errKey] = '请填写层号！';
                                    $error_arr[$errArrKey] = $vv;
                                    $errArrKey++;
                                    continue;
                                }
                                $room = htmlspecialchars(trim($vv['F']), ENT_QUOTES);
                                if (empty($room)) {
                                    $vv[$errKey] = '请填写房屋号！';
                                    $error_arr[$errArrKey] = $vv;
                                    $errArrKey++;
                                    continue;
                                }
                                $property_starttime= htmlspecialchars(trim($vv['M']), ENT_QUOTES);  //缴费开始时间

                                $property_endtime = htmlspecialchars(trim($vv['N']), ENT_QUOTES);  //缴费结束时间
       
                                $village_id = $this->village_id;
                                $single_data = [];
                                $single_data['single_name'] = $single_name;
                                $single_data['village_id'] = $village_id;
                                $single_data['status'] = 1;
                                $single = M('House_village_single')->where($single_data)->find();
                                if (!$single) {
                                    $vv[$errKey] = '楼栋不存在！';
                                    $error_arr[$errArrKey] = $vv;
                                    $errArrKey++;
                                    continue;
                                }
                                $where = [];
                                $where['floor_name'] = $floor_name;
                                $where['status'] = 1;
                                $where['village_id'] = $village_id;
                                $where['single_id'] = $single['id'];
                                $database_house_village_floor = D('House_village_floor');
                                $house_village_floor_info = $database_house_village_floor->where($where)->find();
                                if (!$house_village_floor_info) {
                                    $vv[$errKey] = '单元不存在，请查看社区中心，单元管理-单元列表!';
                                    $error_arr[$errArrKey] = $vv;
                                    $errArrKey++;
                                    continue;
                                }
                                $where_layer = array(
                                    'village_id' => $village_id,
                                    'layer_name' => $layer_name,
                                    'single_id' => $single['id'],
                                    'floor_id' => $house_village_floor_info['floor_id'],
                                    'status'=>['neq', 4]
                                );
                                $layer = M('House_village_layer')->where($where_layer)->find();
                                if ($layer) {
                                    $tmpdata['layer_id'] = $layer['id'];
                                } else {
                                    $err_msg = '楼层不存在';
                                    $vv[$errKey] = $err_msg;
                                    $error_arr[$errArrKey] = $vv;
                                    $errArrKey++;
                                    continue;
                                }

                                //检查房间是否存在或已绑定
                                $vwhere=[];
                                $vwhere['village_id'] = $village_id;
                                $vwhere['floor_id'] = $house_village_floor_info['floor_id'];
                                $vwhere['layer_id'] = $layer['id'];
                                $vwhere['room'] = $room;
                                $vwhere['type'] = 0;
                                $vwhere['is_del'] = 0;
                                $vwhere['status'] = ['neq', 4];
                                $room_info = D('House_village_user_vacancy')->where($vwhere)->find();
                                if (!$room_info) {
                                    $vv[$errKey] = '房间不存在!';
                                    $error_arr[$errArrKey] = $vv;
                                    $errArrKey++;
                                    continue;
                                }elseif ($room_info['is_del'] == 1) {
                                    $vv[$errKey] = '房间已被删除!';
                                    $error_arr[$errArrKey] = $vv;
                                    $errArrKey++;
                                    continue;
                                }

                                $id_card = trim($vv['G'])?trim($vv['G']):'';
                                if($id_card && !is_idcard($id_card)){
                                    $vv[$errKey] = '身份证数据错误，不是正确的身份证号';
                                    $error_arr[$errArrKey] = $vv;
                                    $errArrKey++;
                                }
                                $ic_card = trim($vv['H'])?trim($vv['H']):'';
                                $idCard=idCardAnalysis($id_card);
                                $authenticationArr=array();
                                if($idCard && isset($idCard['birthday'])){
                                    $authenticationArr['birthday']=$idCard['birthday'];
                                }
                                if($idCard && isset($idCard['sex'])){
                                    $authenticationArr['sex']=$idCard['sex'];
                                }
                                /*
                                $id_card_data=D('House_village_user_bind')->id_card_data($id_card,$dataList);
                                if($id_card_data['error']){
                                    $tmpdata['authentication_field']=$id_card_data['data'];
                                }
                                */
                                $tmpdata['name'] = htmlspecialchars(str_replace(array(' ', PHP_EOL, "\t"), '', $name), ENT_QUOTES);
                                $tmpdata['phone'] = htmlspecialchars(trim($phone), ENT_QUOTES);
                                $tmpdata['id_card'] = $id_card;
                                $tmpdata['ic_card'] = $ic_card;
                                $memo = htmlspecialchars(trim($vv['L']), ENT_QUOTES);
                                $tmpdata['memo'] = $memo;
                                $user = array();
                                if ($tmpdata['phone']) {
                                    $user = D('User')->where(array('phone' => $tmpdata['phone']))->find();
                                }
                                if (empty($user) && $tmpdata['phone']) {
                                    $user_add = [];
                                    $user_add['phone'] = $tmpdata['phone'];
                                    if ($tmpdata['name']) {
                                        $user_add['nickname'] = $tmpdata['name'];
                                    }
                                    $user_add['source'] = 'houseautoreg';
                                    $reg_result = D('User')->autoreg($user_add);
                                    $uid = $reg_result['uid'];
                                    $user['uid'] = $uid;

                                }
                                $tmpdata['uid'] = $user['uid'] ? intval($user['uid']) : 0;
                                
                                $user_tag_arr=array('user_political_str'=>'','party_type_str'=>'','user_party_str'=>'','user_special_groups'=>'','user_focus_groups'=>'','user_vulnerable_groups'=>'','uid'=>$tmpdata['uid']);
                                if($is_func_cloudmrsqfw_special){
                                    $user_tag_arr['user_political_str'] = htmlspecialchars(trim($vv['O']), ENT_QUOTES);
                                    $user_tag_arr['party_type_str'] = htmlspecialchars(trim($vv['P']), ENT_QUOTES);
                                    $user_tag_arr['user_party_str'] = htmlspecialchars(trim($vv['Q']), ENT_QUOTES);
                                    $user_tag_arr['user_special_groups'] = htmlspecialchars(trim($vv['R']), ENT_QUOTES);
                                    $user_tag_arr['user_focus_groups'] = htmlspecialchars(trim($vv['S']), ENT_QUOTES);
                                    $user_tag_arr['user_vulnerable_groups'] = htmlspecialchars(trim($vv['T']), ENT_QUOTES);
                                    $systemConfigAttr=array('sex'=>'','birthday'=>'','nation'=>'','native_place'=>'','nationality'=>'','household_register'=>'','marriage_status'=>'','edcation'=>'','emergent_person'=>'','person_phone'=>'','job'=>'','unit_nature'=>'','job_address'=>'');
                                    $systemConfigAttr['sex'] = htmlspecialchars(trim($vv['U']), ENT_QUOTES);
                                    $systemConfigAttr['birthday'] = htmlspecialchars(trim($vv['V']), ENT_QUOTES);
                                    $systemConfigAttr['nation'] = htmlspecialchars(trim($vv['W']), ENT_QUOTES);
                                    $systemConfigAttr['native_place'] = htmlspecialchars(trim($vv['X']), ENT_QUOTES);
                                    $systemConfigAttr['nationality'] = htmlspecialchars(trim($vv['Y']), ENT_QUOTES);
                                    $systemConfigAttr['household_register'] = htmlspecialchars(trim($vv['Z']), ENT_QUOTES);
                                    $systemConfigAttr['marriage_status'] = htmlspecialchars(trim($vv['AA']), ENT_QUOTES);
                                    $systemConfigAttr['edcation'] = htmlspecialchars(trim($vv['AB']), ENT_QUOTES);
                                    $systemConfigAttr['emergent_person'] = htmlspecialchars(trim($vv['AC']), ENT_QUOTES);
                                    $systemConfigAttr['person_phone'] = htmlspecialchars(trim($vv['AD']), ENT_QUOTES);
                                    $systemConfigAttr['job'] = htmlspecialchars(trim($vv['AE']), ENT_QUOTES);
                                    $systemConfigAttr['unit_nature'] = htmlspecialchars(trim($vv['AF']), ENT_QUOTES);
                                    $systemConfigAttr['job_address'] = htmlspecialchars(trim($vv['AG']), ENT_QUOTES);
                                    $authenticationArr=array_merge($systemConfigAttr,$authenticationArr);
                                }
                                $updateUserArr=array('name'=>$name,'id_card'=>$id_card,'room_id'=>$room_info['pigcms_id'],'ic_card'=>$ic_card,'memo'=>$memo,'property_id'=>$this->house_session['property_id'],'authentication_field'=>'','authenticationArr'=>$authenticationArr,'user_tag_arr'=>$user_tag_arr,'systemConfigAttr'=>$dataList);
                                $whereRoomOwner = [
                                    'status' => 1,
                                    'vacancy_id' => $room_info['pigcms_id'],
                                    'type' => ['in', [0,3]],
                                ];
                                $bind_pigcms_id=0;
                                $user_bind = D('House_village_user_bind')->where($whereRoomOwner)->field('pigcms_id,authentication_field')->find();
                                if ($user_bind && $user_bind['pigcms_id']) {
                                    $updateUserArr['authentication_field']=$user_bind['authentication_field'] ? unserialize($user_bind['authentication_field']):'';
                                    $errMsg='房间已绑定业主！'.$user_bind['pigcms_id'];
                                    if($is_func_cloudmrsqfw_special) {
                                        $house_village_user_bind_serve->updateUserBindInfo($user_bind['pigcms_id'], $updateUserArr, $this->village_id);
                                        $errMsg='房间已绑定业主,数据已更新！'.$user_bind['pigcms_id'];
                                    }
                                    $bind_pigcms_id=$user_bind['pigcms_id'];
                                    $vv[$errKey] =$errMsg;
                                    $error_arr[$errArrKey] = $vv;
                                    $errArrKey++;
                                    continue;
                                }
                                if($authenticationArr){
                                    $authentication_field_arr=$house_village_user_bind_serve->get_authentication_field($authenticationArr,$dataList);
                                    $tmpdata['authentication_field']=$authentication_field_arr ? serialize($authentication_field_arr):'';
                                }
                                $tmpdata['usernum'] = $room_info['usernum'];
                                $replace_info = D('House_village_user_bind')->field('pigcms_id,status,usernum')->where(array('usernum' => $tmpdata['usernum'],'village_id'=>$village_id))->find();
                                if ($replace_info && 0 == $replace_info['status']) {
                                    // 查询到的业主处于禁止状态 改变改业主物业编号为其他的
                                    $usernum = $tmpdata['usernum'] . 'y' . time();
                                    $set_data = [];
                                    $set_data['usernum'] = $usernum;
                                    D('House_village_user_bind')->where(['pigcms_id' => $replace_info['pigcms_id']])->data($set_data)->save();
                                } elseif (false && $replace_info && in_array($replace_info['status'], [1, 2])) {
                                    $err_msg = '业主名为' . $name . ' 已存在！物业编号重复';
                                    $vv[$errKey] = $err_msg;
                                    $error_arr[$errArrKey] = $vv;
                                    $errArrKey++;
                                    continue;
                                }
                                
                                $tmpdata['layer_num'] = $layer_name;
                                $tmpdata['apply_time'] = time();
                                $tmpdata['adopt_time'] = time();
                                $room_addrss = $room;
                                $tmpdata['room_addrss'] = $room_addrss;
                                $tmpdata['address'] = $single_name . $floor_name . '#' . $room_addrss;
                                $tmpdata['floor_id'] = $house_village_floor_info['floor_id'];
                                $tmpdata['single_id'] = $single['id'];
                                $tmpdata['single_id'] = $house_village_floor_info['single_id'] ? $house_village_floor_info['single_id'] : $single['id'];
                                $tmpdata['village_id'] = $village_id;
                                
                                $cars = trim($vv['I'])?trim($vv['I']):'';
                                $carInfos = [];
                                $carsNumArr=array();
                                if($cars){
                                    $where_car = [
                                        'village_id' => $village_id,
                                    ];
                                    $carsArr = explode(',',$cars);
                                    if (empty($carsArr)) {
                                        // 以防万一 中文逗号也过滤下
                                        $carsArr = explode('，',$cars);
                                    }
                                    if ($carsArr) {
                                        foreach ($carsArr as $k=>$v) {
                                            $carsArr[$k] = "'{$v}'";
                                            $carsNumArr[]=$v;
                                        }
                                        $carsStr = implode(',',$carsArr);
                                        $where_car['_string'] = "concat(`province`,'',`car_number`) in ({$carsStr})";
                                        $carInfos = D('House_village_parking_car')->where($where_car)->field('car_id,village_id,province,car_number')->select();
                                        /*
                                        if (empty($carInfos)) {
                                            if (!isset($vv[$errKey]) || !$vv[$errKey]) {
                                                $vv[$errKey] = '对应车辆不存在（注意，实际数据是录入了只是车辆没有绑定）！';
                                            } else {
                                                $vv[$errKey] .= '；对应车辆不存在（注意，实际数据是录入了只是车辆没有绑定）！';
                                            }
                                            $error_arr[$errArrKey] = $vv;
                                            $carInfos = [];
                                        }
                                        */
                                    }
                                }
                                $carGarage = trim($vv['J'])?trim($vv['J']):'';
                                $carPosition = trim($vv['K'])?trim($vv['K']):'';
                                $carPositions = [];
                                $garage_id=0;
                                if($carPosition && $carGarage){
                                    $whereCarGarage = [
                                        'village_id' => $village_id,
                                        'garage_num' => $carGarage,
                                        'status' => 1,
                                    ];
                                    $carGarageInfo = M('House_village_parking_garage')->where($whereCarGarage)->find();
                                    if (!$carGarageInfo || !$carGarageInfo['garage_id']) {
                                        if (!isset($vv[$errKey]) || !$vv[$errKey]) {
                                            $vv[$errKey] = '对应车库不存在（注意，实际业主数据是录入了只是车位没有绑定）！';
                                        } else {
                                            $vv[$errKey] .= '；对应车库不存在（注意，实际业主数据是录入了只是车位没有绑定）！';
                                        }
                                        $error_arr[$errArrKey] = $vv;
                                        $carPositions = [];
                                    } else {
                                        $where_position = [
                                            'village_id' => $village_id,
                                            'garage_id' => $carGarageInfo['garage_id'],
                                        ];
                                        $garage_id=$carGarageInfo['garage_id'];
                                        $carPositionArr = explode(',',$carPosition);
                                        if (empty($carPositionArr)) {
                                            // 以防万一 中文逗号也过滤下
                                            $carPositionArr = explode('，',$carPosition);
                                        }
                                        if ($carPositionArr && $carGarageInfo) {
                                            foreach ($carPositionArr as $k1 => $v1) {
                                                $carPositionArr[$k1] = "'{$v1}'";
                                            }
                                            $carPositionStr = implode(',',$carPositionArr);
                                            $where_position['_string'] = "position_num in ({$carPositionStr})";
                                            $carPositions = D('house_village_parking_position')->where($where_position)->field('position_id,position_num,village_id')->select();
                                            if (empty($carPositions)) {
                                                if (!isset($vv[$errKey]) || !$vv[$errKey]) {
                                                    $vv[$errKey] = '对应车位不存在（注意，实际业主数据是录入了只是车位没有绑定）！';
                                                } else {
                                                    $vv[$errKey] .= '；对应车位不存在（注意，实际业主数据是录入了只是车位没有绑定）！';
                                                }
                                                $error_arr[$errArrKey] = $vv;
                                                $carPositions = [];
                                            }
                                        }
                                    }
                                }
                                $tmpdata['add_time'] = time();
                                $tmpdata['vacancy_id'] = $room_info['pigcms_id'];
                                if ($room_info['housesize'] > 0.00) {
                                    $tmpdata['housesize'] = round($room_info['housesize'], 2);
                                }
                                $property_starttime_c=0;
                                if(!empty($property_starttime)){
                                    $property_starttime_c=strtotime($property_starttime);
                                    if($property_starttime_c>0){
                                        $tmpdata['property_starttime'] =$property_starttime_c;
                                    } else if (preg_match('/[\x7f-\xff]/', $property_starttime)) {
                                        $arr = date_parse_from_format('Y年m月d日', $property_starttime);
                                        $tmpdata['property_starttime'] = mktime(0, 0, 0, $arr['month'], $arr['day'], $arr['year']);
                                        $property_starttime_c = $tmpdata['property_starttime'];
                                    }
                                }
                                if(!empty($property_endtime)){
                                    $property_endtime_c=strtotime($property_endtime);
                                    if($property_endtime_c<1 && preg_match('/[\x7f-\xff]/', $property_endtime)){
                                        $arr = date_parse_from_format('Y年m月d日', $property_endtime);
                                        $property_endtime_c = mktime(0, 0, 0, $arr['month'], $arr['day'], $arr['year']);
                                    } 
                                    if($property_starttime_c > $property_endtime_c){
                                        $tmpdata['property_endtime'] =0;
                                    }else{
                                        $tmpdata['property_endtime'] =$property_endtime_c+86399;
                                    }
                                }
                                $last_user_id = D('House_village_user_bind')->data($tmpdata)->add();
                                if (!$last_user_id) {
                                    $err_msg = '业主名为' . $vv['A'] . ' 导入失败！';
                                    if (!isset($vv[$errKey]) || !$vv[$errKey]) {
                                        $vv[$errKey] = $err_msg;
                                    } else {
                                        $vv[$errKey] .= '；'.$err_msg;
                                    }
                                    $error_arr[$errArrKey] = $vv;
                                } else {
                                    $bind_pigcms_id=$last_user_id;
                                    if($is_func_cloudmrsqfw_special) {
                                        $house_village_user_label_serve->updateUserTagAttr($bind_pigcms_id, $user_tag_arr, $this->village_id);
                                    }
                                    $arr = array();
                                    $arr['param'] = serialize(array('bind_id' =>$last_user_id,'type'=>'user'));
                                    $arr['plan_time'] = time()+60;
                                    $arr['space_time'] = 0;
                                    $arr['add_time'] = time();
                                    $arr['file'] = 'sub_house_village_third';
                                    $arr['time_type'] = 1;
                                    $arr['unique_id'] = 'sub_house_village_third_user_'.$last_user_id;
                                    $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
                                    M('process_sub_plan')->data($arr)->add();
                                    //导入成功 绑定此房间
                                    $data_vacancy = array();
                                    $data_vacancy['uid'] = $tmpdata['uid'] ? $tmpdata['uid'] : 0;
                                    $data_vacancy['status'] = 3;
                                    $data_vacancy['name'] = $tmpdata['name'];
                                    $data_vacancy['phone'] = $tmpdata['phone'];
                                    D('House_village_user_vacancy')->where($vwhere)->data($data_vacancy)->save();
                                    if (intval($last_user_id) > 0) {
                                        $change_param = [];
                                        $change_param['pigcms_id'] = $last_user_id;
                                        D('House_village_user_vacancy')->change_user_bind_number($change_param);
                                    }
                                    // 导出成功处理车辆车位信息
                                    if (!empty($carInfos)) {
                                        $db_house_village_bind_car = D('House_village_bind_car');
                                        foreach ($carInfos as $car) {
                                            $bind_car = $db_house_village_bind_car->where(array('village_id'=>$village_id,'car_id'=>$car['car_id']))->find();
                                            if(!empty($bind_car)){
                                                if (!isset($vv[$errKey]) || !$vv[$errKey]) {
                                                    $vv[$errKey] = "车辆{$car['province']}{$car['car_number']}已被其他人绑定（注意，实际业主数据是录入了只是车辆没有绑定）！";
                                                } else {
                                                    $vv[$errKey] .= "；车辆{$car['province']}{$car['car_number']}已被其他人绑定（注意，实际业主数据是录入了只是车辆没有绑定）！";
                                                }
                                                $error_arr[$errArrKey] = $vv;
                                            } else {
                                                $car_bind = [
                                                    'car_id' => $car['car_id'],
                                                    'user_id' => $last_user_id,
                                                    'village_id' => $village_id,
                                                    'uid' => intval($tmpdata['uid']),
                                                ];
                                                D('House_village_bind_car')->add($car_bind);
                                            }
                                        }
                                    }else if(empty($carInfos) && !empty($carsNumArr) && $garage_id>0 && !empty($carPositions)){
                                        $car_position_id=$carPositions['0']['position_id'];
                                        foreach ($carsNumArr as $cnkk=>$cnvv){
                                            $car_number_tmp=trim($cnvv);
                                            $province=preg_replace('/\w/i','',$car_number_tmp);
                                            $parking_car_tmp=array('village_id'=>$village_id,'car_position_id'=>$car_position_id,'car_type'=>0);
                                            if(isset($carPositions[$cnkk]) && !empty($carPositions[$cnkk])){
                                                $parking_car_tmp['car_position_id']=$carPositions[$cnkk]['position_id'];
                                            }
                                            $parking_car_tmp['province']=trim($province);
                                            $car_number_tmp=str_replace($province,'',$car_number_tmp);
                                            $parking_car_tmp['car_number']=$car_number_tmp;
                                            $parking_car_tmp['car_number']=$car_number_tmp;
                                            $parking_car_tmp['car_user_name']=$tmpdata['name'];
                                            $parking_car_tmp['car_user_phone']=$tmpdata['phone'];
                                            $parking_car_tmp['room_id']=$room_info['pigcms_id'];
                                            $parking_car_tmp['car_addtime']=time();
                                            $parking_car_tmp['examine_time']=time();
                                            $parking_car_tmp['examine_status']=1;
                                            $parking_car_tmp['garage_id']=$garage_id;
                                            $car_id_tmp=D('House_village_parking_car')->data($parking_car_tmp)->add();
                                            if($car_id_tmp>0){
                                                $car_bind = [
                                                    'car_id' => $car_id_tmp,
                                                    'user_id' => $last_user_id,
                                                    'village_id' => $village_id,
                                                    'uid' => intval($tmpdata['uid']),
                                                ];
                                                D('House_village_bind_car')->add($car_bind);
                                            }
                                        }
                                    }
                                    if (!empty($carPositions)) {
                                        $db_house_village_bind_position = D('House_village_bind_position');
                                        foreach ($carPositions as $carPosition) {
                                            $bind_car = $db_house_village_bind_position->where(array('village_id'=>$village_id,'position_id'=>$carPosition['position_id']))->find();
                                            if(!empty($bind_car)){
                                                if (!isset($vv[$errKey]) || !$vv[$errKey]) {
                                                    $vv[$errKey] = "车位{$carPosition['position_num']}已被其他人绑定（注意，实际业主数据是录入了只是车位没有绑定）！";
                                                } else {
                                                    $vv[$errKey] .= "；车位{$carPosition['position_num']}已被其他人绑定（注意，实际业主数据是录入了只是车位没有绑定）！";
                                                }
                                                $error_arr[$errArrKey] = $vv;
                                            } else {
                                                $position_bind = [
                                                    'position_id' => $carPosition['position_id'],
                                                    'user_id' => $last_user_id,
                                                    'village_id' => $village_id,
                                                ];
                                                D('House_village_bind_position')->add($position_bind);
                                            }
                                        }
                                    }
                                }
                                if (isset($vv[$errKey]) && $vv[$errKey]) {
                                    $errArrKey++;
                                }
                            }
                            if (empty($error_arr)) {
                                $this->success('导入成功。');
                                exit;
                            } else {
                                //导出失败信息
                                require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
                                error_reporting(E_ALL);
                                date_default_timezone_set('Europe/London');
                                $objExcel = new PHPExcel();

                                $title = '导入失败列表';
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
                                $objExcel->getActiveSheet()->setTitle($title);
                                $objActSheet = $objExcel->getActiveSheet();
                                $cell_list = range('A', 'T');
                                if($is_func_cloudmrsqfw_special){
                                    $cell_list = range('A', 'Z');
                                    $cell_list=array_merge($cell_list,['AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK']);
                                }
                                foreach ($cell_list as $cell) {
                                    $objActSheet->getColumnDimension($cell)->setWidth(30);
                                }
                                $objActSheet->getDefaultRowDimension()->setRowHeight(22); //设置默认行高
                                if($A1Tips){
                                    $objActSheet->setCellValue('A1',$A1Tips );
                                    $columnCell='A1:S1';
                                    if($is_func_cloudmrsqfw_special){
                                        $columnCell='A1:AK1';
                                    }
                                    $objActSheet->mergeCells($columnCell);
                                    $objActSheet->getStyle($columnCell)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                                    $objActSheet->getRowDimension(1)->setRowHeight(30); // 固定高度
                                }
                                $header_index=$err_index-1;
                                $header_index=$header_index<1 ? 1:$header_index;
                                foreach ($headerArr as $kk=>$vv){
                                    $objActSheet->setCellValue($kk.$header_index, $vv);
                                }
                                $objActSheet->setCellValue($errKey.$header_index, '失败信息');
                               
                                $error_arr = array_values($error_arr);
                                foreach ($error_arr as $value) {
                                    foreach ($headerArr as $kk=>$vv){
                                        $objActSheet->setCellValueExplicit($kk . $err_index, $value[$kk]);
                                    }
                                    $objActSheet->setCellValueExplicit($errKey . $err_index, $value[$errKey]);
                                    $err_index++;
                                }

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
                        }else{
                            $this->error('导入失败！请检查表格数据。');
                            exit;
                        }
                    } else {
                        $this->error($upload->getErrorMsg());
                        exit;
                    }
                } else {
                    if ($upload->upload()) {
                        $uploadList = $upload->getUploadFileInfo();
                        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel/IOFactory.php';
                        $path = $uploadList['0']['savepath'] . $uploadList['0']['savename'];
                        $fileType = PHPExcel_IOFactory::identify($path); //文件名自动判断文件类型

                    $objReader = PHPExcel_IOFactory::createReader($fileType);
                    $excelObj = $objReader->load($path);
                    $result = $excelObj->getActiveSheet()->toArray(null, true, true, true);
                    unlink($path);	//删除物业上传的excel表格
                    $database_user = D('User');
                    $error_arr = array();
                    if (!empty($result) && is_array($result)) {
                        unset($result[1]);
                        $last_user_id = 0;
                        $err_msg = '';
                        $errKey = 'T';
                        $errArrKey = 0;// 错误数据键值
                        foreach ($result as $kk => $vv) {
                            if (array_sum($vv) == 0) {
                                continue;
                            }
                            if ($vv['A'] === null && $vv['B'] === null && $vv['C'] === null && $vv['D'] === null && $vv['E'] === null && $vv['F'] === null && $vv['G'] === null && $vv['H'] === null && $vv['I'] === null && $vv['J'] === null && $vv['K'] === null && $vv['L'] === null && $vv['M'] === null) continue;
                            //2020/8/6 优化  start

                            $tmpdata = array();
                            $name = trim($vv['A']);
                            if (empty($name)) {
                                $vv[$errKey] = '请填写业主名！';
                                $error_arr[$errArrKey] = $vv;
                                $errArrKey++;
                                continue;
                            }
                            $phone = trim($vv['B']);
                            if(!preg_match('/^[0-9]{11}$/',$phone)){
                                $vv[$errKey] = '请填写正确的手机号！';
                                $error_arr[$errArrKey] = $vv;
                                $errArrKey++;
                                continue;
                            }
                            $single_name = trim($vv['H']);
                            if (empty($vv['H'])) {
                                $vv[$errKey] = '请填写楼号！';
                                $error_arr[$errArrKey] = $vv;
                                $errArrKey++;
                                continue;
                            }
                            $floor_name = htmlspecialchars(trim($vv['I']), ENT_QUOTES);
                            if (empty($floor_name)) {
                                $vv[$errKey] = '请填写单元名称！';
                                $error_arr[$errArrKey] = $vv;
                                $errArrKey++;
                                continue;
                            }
                            $layer_name = htmlspecialchars(trim($vv['J']), ENT_QUOTES);
                            if (empty($layer_name)) {
                                $vv[$errKey] = '请填写层号！';
                                $error_arr[$errArrKey] = $vv;
                                $errArrKey++;
                                continue;
                            }
                            $room = htmlspecialchars(trim($vv['K']), ENT_QUOTES);
                            if (empty($room)) {
                                $vv[$errKey] = '请填写房屋号！';
                                $error_arr[$errArrKey] = $vv;
                                $errArrKey++;
                                continue;
                            }
                            $village_id = $this->village_id;
                            $single_data = [];
                            $single_data['single_name'] = $single_name;
                            $single_data['village_id'] = $village_id;
                            $single_data['status'] = 1;
                            $single = M('House_village_single')->where($single_data)->find();
                            if(!$single){
                                $vv[$errKey] = '楼栋不存在！';
                                $error_arr[$errArrKey] = $vv;
                                $errArrKey++;
                                continue;
                            }
                            $where = [];
                            $where['floor_name'] = $floor_name;
                            $where['status'] = 1;
                            $where['village_id'] = $village_id;
                            $where['single_id'] = $single['id'];
                            $database_house_village_floor = D('House_village_floor');
                            $house_village_floor_info = $database_house_village_floor->where($where)->find();
                            if (!$house_village_floor_info) {
                                $vv[$errKey] = '单元不存在，请查看社区中心，单元管理-单元列表!';
                                $error_arr[$errArrKey] = $vv;
                                $errArrKey++;
                                continue;
                            }
                            $where_layer = array(
                                'village_id'=>$village_id,
                                'layer_name'=>$layer_name,
                                'single_id'=>$single['id'],
                                'status'=>1,
                                'floor_id'=>$house_village_floor_info['floor_id']
                            );
                            $layer = M('House_village_layer')->where($where_layer)->find();
                            if($layer){
                                $tmpdata['layer_id'] = $layer['id'];
                            }else{
                                $err_msg = '楼层不存在';
                                $vv[$errKey] = $err_msg;
                                $error_arr[$errArrKey] = $vv;
                                $errArrKey++;
                                continue;
                            }

                            //检查房间是否存在或已绑定
                            $vwhere=[];
                            $vwhere['village_id'] = $village_id;
                            $vwhere['floor_id'] =  $house_village_floor_info['floor_id'];
                            $vwhere['layer_id'] = $layer['id'];
                            $vwhere['room'] = $room;
                            $vwhere['type'] = 0;
                            $vwhere['is_del'] = 0;
                            $vwhere['status'] = ['neq', 4];
                            $room_info = D('House_village_user_vacancy')->where($vwhere)->find();
                            if(!$room_info){
                                $vv[$errKey] = '房间不存在!';
                                $error_arr[$errArrKey] = $vv;
                                $errArrKey++;
                                continue;
                            }elseif($room_info['status']!=1 || $room_info['is_del']==1 || ($room_info['name']!="" && $room_info['phone']!="")){
                                $vv[$errKey] = '房间已被删除或已绑定业主!';
                                $error_arr[$errArrKey] = $vv;
                                $errArrKey++;
                                continue;
                            }
                            if ($vv['L']) {
                                if(strtotime(trim($vv['L']))){
                                    $tmpdata['property_starttime'] = strtotime(trim($vv['L']));
                                }
                                /*else{
                                    $tmpdata['property_starttime'] = PHPExcel_Shared_Date::ExcelToPHP(trim($vv['L']));
                                    if($tmpdata['property_starttime'] < 0){
                                        //格林威治时间
                                        $tmpdata['property_starttime']=strtotime(date('Y-m-d',PHPExcel_Shared_Date::ExcelToPHP($excelObj->getActiveSheet()->getCell("L" . $kk)->getValue())));
                                    }
                                }*/
                                if(!$tmpdata['property_starttime']){
                                    if(preg_match('/[\x7f-\xff]/',$vv['L'])){
                                        $arr = date_parse_from_format('Y年m月d日', $vv['L']);
                                        $tmpdata['property_starttime'] = mktime(0, 0, 0, $arr['month'], $arr['day'], $arr['year']);
                                    }else{
                                        $arr= explode("-",$vv['L']);
                                        $tmpdata['property_starttime'] = mktime(0,0,0,$arr[0],$arr[1],$arr[2]);
                                    }
                                }

                            }
                            if ($vv['M']) {
                                $tmpdata['property_endtime'] = strtotime(trim($vv['M']));
                                if(!$tmpdata['property_endtime']){
                                    if(preg_match('/[\x7f-\xff]/',$vv['M'])){
                                        $arr = date_parse_from_format('Y年m月d日', $vv['M']);
                                        $tmpdata['property_endtime'] = mktime(0, 0, 0, $arr['month'], $arr['day'], $arr['year']);
                                    }else{
                                        $arr= explode("-",$vv['M']);
                                        $tmpdata['property_endtime'] = mktime(0,0,0,$arr[0],$arr[1],$arr[2]);
                                        $tmpdata['property_endtime'] +=86399;
                                    }
                                }
                            }


                            $tmpdata['usernum'] = $room_info['usernum'];
                            $replace_info = D('House_village_user_bind')->field('pigcms_id,status,usernum')->where(array('usernum' => $tmpdata['usernum']))->find();
                            if ($replace_info && 0==$replace_info['status']) {
                                // 查询到的业主处于禁止状态 改变改业主物业编号为其他的
                                $usernum = $tmpdata['usernum'] . 'y'.time();
                                $set_data = [];
                                $set_data['usernum'] = $usernum;
                                D('House_village_user_bind')->where(['pigcms_id'=>$replace_info['pigcms_id']])->data($set_data)->save();
                            } elseif ($replace_info && in_array($replace_info['status'],[1,2])) {
                                $err_msg = '业主名为' . $name . ' 已存在！物业编号重复';
                                $vv[$errKey] = $err_msg;
                                $error_arr[$errArrKey] = $vv;
                                $errArrKey++;
                                continue;
                            }

                            $id_card = trim($vv['N'])?trim($vv['N']):'';
                            if($id_card && !is_idcard($id_card)){
                                $vv[$errKey] = '身份证错误（注意，实际业主数据是录入了只是身份证没有录入）！';
                                $id_card = '';
                                $error_arr[$errArrKey] = $vv;
                            }
                            $ic_card = trim($vv['O'])?trim($vv['O']):'';

                            $id_card_data=D('House_village_user_bind')->id_card_data($id_card,$dataList);
                            if($id_card_data['error']){
                                $tmpdata['authentication_field']=$id_card_data['data'];
                            }

                            $tmpdata['id_card'] = $id_card;
                            $tmpdata['ic_card'] = $ic_card;

                            $cars = trim($vv['P'])?trim($vv['P']):'';
                            $carInfos =[];
                            $carsNumArr=array();
                            $garage_id=0;
                            if($cars){
                                $where_car = [
                                    'village_id' => $village_id,
                                ];
                                $carsArr = explode(',',$cars);
                                if (empty($carsArr)) {
                                    // 以防万一 中文逗号也过滤下
                                    $carsArr = explode('，',$cars);
                                }
                                if ($carsArr) {
                                    foreach ($carsArr as $k=>$v) {
                                        $carsArr[$k] = "'{$v}'";
                                        $carsNumArr[]=$v;
                                    }
                                    $carsStr = implode(',',$carsArr);
                                    $where_car['_string'] = "concat(`province`,'',`car_number`) in ({$carsStr})";
                                    $carInfos = D('House_village_parking_car')->where($where_car)->field('car_id,village_id,province,car_number')->select();
                                    /*
                                    if (empty($carInfos)) {
                                        if (!isset($vv[$errKey]) || !$vv[$errKey]) {
                                            $vv[$errKey] = '对应车辆不存在（注意，实际数据是录入了只是车辆没有绑定）！';
                                        } else {
                                            $vv[$errKey] .= '；对应车辆不存在（注意，实际数据是录入了只是车辆没有绑定）！';
                                        }
                                        $error_arr[$errArrKey] = $vv;
                                        $carInfos = [];
                                    }
                                    */
                                }
                            }
                            $carGarage = trim($vv['Q'])?trim($vv['Q']):'';
                            $carPosition = trim($vv['R'])?trim($vv['R']):'';
                            $carPositions = [];
                            if($carPosition && $carGarage){
                                $whereCarGarage = [
                                    'village_id' => $village_id,
                                    'garage_num' => $carGarage,
                                    'status' => 1,
                                ];
                                $carGarageInfo = M('House_village_parking_garage')->where($whereCarGarage)->find();
                                if (!$carGarageInfo || !$carGarageInfo['garage_id']) {
                                    if (!isset($vv[$errKey]) || !$vv[$errKey]) {
                                        $vv[$errKey] = '对应车库不存在（注意，实际业主数据是录入了只是车位没有绑定）！';
                                    } else {
                                        $vv[$errKey] .= '；对应车库不存在（注意，实际业主数据是录入了只是车位没有绑定）！';
                                    }
                                    $error_arr[$errArrKey] = $vv;
                                    $carPositions = [];
                                } else {
                                    $garage_id=$carGarageInfo['garage_id'];
                                    $where_position = [
                                        'village_id' => $village_id,
                                        'garage_id' => $carGarageInfo['garage_id'],
                                    ];
                                    $carPositionArr = explode(',', $carPosition);
                                    if (empty($carPositionArr)) {
                                        // 以防万一 中文逗号也过滤下
                                        $carPositionArr = explode('，', $carPosition);
                                    }
                                    if ($carPositionArr) {
                                        foreach ($carPositionArr as $k1 => $v1) {
                                            $carPositionArr[$k1] = "'{$v1}'";
                                        }
                                        $carPositionStr = implode(',', $carPositionArr);
                                        $where_position['_string'] = "position_num in ({$carPositionStr})";
                                        $carPositions = D('house_village_parking_position')->where($where_position)->field('position_id,position_num,village_id')->select();
                                        if (empty($carPositions)) {
                                            if (!isset($vv[$errKey]) || !$vv[$errKey]) {
                                                $vv[$errKey] = '对应车位不存在（注意，实际业主数据是录入了只是车位没有绑定）！';
                                            } else {
                                                $vv[$errKey] .= '；对应车位不存在（注意，实际业主数据是录入了只是车位没有绑定）！';
                                            }
                                            $error_arr[$errArrKey] = $vv;
                                            $carPositions = [];
                                        }
                                    }
                                }
                            }
                            $tmpdata['name'] = htmlspecialchars(str_replace(array(' ',PHP_EOL,"\t"),'',$name), ENT_QUOTES);
                            $tmpdata['phone'] = htmlspecialchars(trim($phone), ENT_QUOTES);
                            $tmpdata['water_price'] = htmlspecialchars(trim($vv['C']), ENT_QUOTES);
                            $tmpdata['electric_price'] = htmlspecialchars(trim($vv['D']), ENT_QUOTES);
                            $tmpdata['gas_price'] = htmlspecialchars(trim($vv['E']), ENT_QUOTES);
                            $tmpdata['park_flag'] = htmlspecialchars(trim($vv['F']), ENT_QUOTES);
                            $tmpdata['park_price'] = htmlspecialchars(trim($vv['G']), ENT_QUOTES);
                            $tmpdata['layer_num'] = $layer_name;
                            $room_addrss = $room;
                            $tmpdata['room_addrss'] = $room_addrss;
                            $tmpdata['address'] = $single_name . $floor_name  . '#' . $room_addrss;
                            $tmpdata['floor_id'] = $house_village_floor_info['floor_id'];
                            $tmpdata['single_id'] = $single['id'];

                            $tmpdata['single_id'] = $house_village_floor_info['single_id'] ? $house_village_floor_info['single_id'] : $single['id'];


                            $tmpdata['village_id'] = $village_id;
                            if($memo = htmlspecialchars(trim($vv['S']), ENT_QUOTES)){
                                $tmpdata['memo'] = $memo;
                            }

                            if($tmpdata['phone']){
                                $user = D('User')->where(array('phone'=>$tmpdata['phone']))->find();
                            }else{
                                $user = array();
                            }
                            if(empty($user)){
                                $user_add = [];
                                if ($tmpdata['phone']) {
                                    $user_add['phone'] = $tmpdata['phone'];
                                }
                                if ($tmpdata['name']) {
                                    $user_add['nickname'] = $tmpdata['name'];
                                }
                                if ($user_add) {
                                    $user_add['source'] = 'houseautoreg';
                                    $reg_result = D('User')->autoreg($user_add);
                                    $uid = $reg_result['uid'];
                                    $user['uid'] = $uid;
                                }
                            }

                            $tmpdata['uid'] = $user['uid'] ? intval($user['uid']) : 0;

                            $tmpdata['add_time'] = time();

                            if(intval($tmpdata['property_starttime']) > intval($tmpdata['property_endtime'])){
                                if (!isset($vv[$errKey]) || !$vv[$errKey]) {
                                    $vv[$errKey] = '请填写正确的物业时间！';
                                } else {
                                    $vv[$errKey] .= '；请填写正确的物业时间！';
                                }
                                $error_arr[$errArrKey] = $vv;
                            }
                            $tmpdata['vacancy_id'] = $room_info['pigcms_id'];
                            if($room_info['housesize'] > 0.00){
                                $tmpdata['housesize'] = round($room_info['housesize'],2);
                            }


                            $last_user_id = D('House_village_user_bind')->data($tmpdata)->add();
                            if (!$last_user_id) {
                                $err_msg = '业主名为' . $vv['A'] . ' 导入失败！';
                                if (!isset($vv[$errKey]) || !$vv[$errKey]) {
                                    $vv[$errKey] = $err_msg;
                                } else {
                                    $vv[$errKey] .= '；'.$err_msg;
                                }
                                $error_arr[$errArrKey] = $vv;
                            }else{
                                //导入成功 绑定此房间
                                $data_vacancy = array();
                                $data_vacancy['uid'] = $tmpdata['uid'] ? $tmpdata['uid'] : 0;
                                $data_vacancy['status'] = 3;
                                $data_vacancy['name'] = $tmpdata['name'];
                                $data_vacancy['phone'] = $tmpdata['phone'];
                                $data_vacancy['park_flag'] = $tmpdata['park_flag'];
                                D('House_village_user_vacancy')->where($vwhere)->data($data_vacancy)->save();
                                if (intval($last_user_id)>0) {
                                    $change_param = [];
                                    $change_param['pigcms_id'] = $last_user_id;
                                    D('House_village_user_vacancy')->change_user_bind_number($change_param);
                                }
                                // 导出成功处理车辆车位信息
                                if (!empty($carInfos)) {
                                    $db_house_village_bind_car = D('House_village_bind_car');
                                    foreach ($carInfos as $car) {
                                        $bind_car = $db_house_village_bind_car->where(array('village_id'=>$village_id,'car_id'=>$car['car_id']))->find();
                                        if(!empty($bind_car)){
                                            if (!isset($vv[$errKey]) || !$vv[$errKey]) {
                                                $vv[$errKey] = "车辆{$car['province']}{$car['car_number']}已被其他人绑定（注意，实际业主数据是录入了只是车辆没有绑定）！";
                                            } else {
                                                $vv[$errKey] .= "；车辆{$car['province']}{$car['car_number']}已被其他人绑定（注意，实际业主数据是录入了只是车辆没有绑定）！";
                                            }
                                            $error_arr[$errArrKey] = $vv;
                                        } else {
                                            $car_bind = [
                                                'car_id' => $car['car_id'],
                                                'user_id' => $last_user_id,
                                                'village_id' => $village_id,
                                                'uid' => intval($tmpdata['uid']),
                                            ];
                                            D('House_village_bind_car')->add($car_bind);
                                        }
                                    }
                                }else if(empty($carInfos) && !empty($carsNumArr) && $garage_id>0 && !empty($carPositions)){
                                    $car_position_id=$carPositions['0']['position_id'];
                                    foreach ($carsNumArr as $cnkk=>$cnvv){
                                        $car_number_tmp=trim($cnvv);
                                        $province=preg_replace('/\w/i','',$car_number_tmp);
                                        $parking_car_tmp=array('village_id'=>$village_id,'car_position_id'=>$car_position_id,'car_type'=>0);
                                        if(isset($carPositions[$cnkk]) && !empty($carPositions[$cnkk])){
                                            $parking_car_tmp['car_position_id']=$carPositions[$cnkk]['position_id'];
                                        }
                                        $parking_car_tmp['province']=trim($province);
                                        $car_number_tmp=str_replace($province,'',$car_number_tmp);
                                        $parking_car_tmp['car_number']=$car_number_tmp;
                                        $parking_car_tmp['car_number']=$car_number_tmp;
                                        $parking_car_tmp['car_user_name']=$tmpdata['name'];
                                        $parking_car_tmp['car_user_phone']=$tmpdata['phone'];
                                        $parking_car_tmp['room_id']=$room_info['pigcms_id'];
                                        $parking_car_tmp['car_addtime']=time();
                                        $parking_car_tmp['examine_time']=time();
                                        $parking_car_tmp['examine_status']=1;
                                        $parking_car_tmp['garage_id']=$garage_id;
                                        $car_id_tmp=D('House_village_parking_car')->data($parking_car_tmp)->add();
                                        if($car_id_tmp>0){
                                            $car_bind = [
                                                'car_id' => $car_id_tmp,
                                                'user_id' => $last_user_id,
                                                'village_id' => $village_id,
                                                'uid' => intval($tmpdata['uid']),
                                            ];
                                            D('House_village_bind_car')->add($car_bind);
                                        }
                                    }
                                }
                                if (!empty($carPositions)) {
                                    $db_house_village_bind_position = D('House_village_bind_position');
                                    foreach ($carPositions as $carPosition) {
                                        $bind_car = $db_house_village_bind_position->where(array('village_id'=>$village_id,'position_id'=>$carPosition['position_id']))->find();
                                        if(!empty($bind_car)){
                                            if (!isset($vv[$errKey]) || !$vv[$errKey]) {
                                                $vv[$errKey] = "车位{$carPosition['position_num']}已被其他人绑定（注意，实际业主数据是录入了只是车位没有绑定）！";
                                            } else {
                                                $vv[$errKey] .= "；车位{$carPosition['position_num']}已被其他人绑定（注意，实际业主数据是录入了只是车位没有绑定）！";
                                            }
                                            $error_arr[$errArrKey] = $vv;
                                        } else {
                                            $position_bind = [
                                                'position_id' => $carPosition['position_id'],
                                                'user_id' => $last_user_id,
                                                'village_id' => $village_id,
                                            ];
                                            D('House_village_bind_position')->add($position_bind);
                                        }
                                    }
                                }
                            }
                            if (isset($vv[$errKey]) && $vv[$errKey]) {
                                $errArrKey++;
                            }
                        }
                        if (empty($error_arr)) {
                            $this->success('导入成功!');
                            exit;
                        } else {
                            //导出失败信息
                            require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
                            error_reporting(E_ALL);
                            date_default_timezone_set('Europe/London');
                            $objExcel = new PHPExcel();

                            $title = '导入失败列表';
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
                            $objExcel->getActiveSheet()->setTitle($title);
                            $objActSheet = $objExcel->getActiveSheet();
                            $objActSheet->setCellValue('A1', '业主名');
                            $objActSheet->setCellValue('B1', '手机号');
                            $objActSheet->setCellValue('C1', '水费总欠费');
                            $objActSheet->setCellValue('D1', '电费总欠费');
                            $objActSheet->setCellValue('E1', '燃气费总欠费');
                            $objActSheet->setCellValue('F1', '是否有停车位（1有0无）');
                            $objActSheet->setCellValue('G1', '停车费总欠费');
                            //2020/8/6 优化  start
                            $objActSheet->setCellValue('H1', '所属楼栋');
                            $objActSheet->setCellValue('I1', '所属单元');
                            $objActSheet->setCellValue('J1', '所属楼层');
                            $objActSheet->setCellValue('K1', '房间号');
                            $objActSheet->setCellValue('L1', '物业开始时间');
                            $objActSheet->setCellValue('M1', '物业结束时间');
                            $objActSheet->setCellValue('N1', '身份证号');
                            $objActSheet->setCellValue('O1', 'IC卡号');
                            $objActSheet->setCellValue('P1', '车辆');
                            $objActSheet->setCellValue('Q1', '车库');
                            $objActSheet->setCellValue('R1', '车位');

                            $objActSheet->setCellValue('S1', '备注');
                            $objActSheet->setCellValue($errKey.'1', '失败信息');
                            //2020/8/6 优化 end

                            $index = 2;
                            $error_arr = array_values($error_arr);
                            foreach ($error_arr as  $value) {
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
                                $objActSheet->setCellValueExplicit('O' . $index, $value['O']);
                                $objActSheet->setCellValueExplicit('P' . $index, $value['P']);
                                $objActSheet->setCellValueExplicit('Q' . $index, $value['Q']);
                                $objActSheet->setCellValueExplicit('R' . $index, $value['R']);
                                $objActSheet->setCellValueExplicit('S' . $index, $value['S']);
                                $objActSheet->setCellValueExplicit($errKey . $index, $value[$errKey]);
                                $index++;
                            }


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
                            header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:s", time()) . '.xls"');
                            header("Content-Transfer-Encoding:binary");
                            $objWriter->save('php://output');
                            exit();
                        }
                    }
                } else {
                    $this->error($upload->getErrorMsg());
                    exit;
                }
            }
            $this->error('文件上传失败');
            exit;
        }
        } else {
            $this->display();
        }
    }

    // 导入业主
    public function user_import_test()
    {
        //业主-添加 权限
        if (0&&!in_array(92, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        if (IS_POST) {
            if ($_FILES['file']['error'] != 4) {
                set_time_limit(0);
                ini_set('memory_limit', '2048M');

                import('ORG.Util.Dir');
                Dir::delDirnotself('./upload/excel/villageuser');

                $upload_dir = './upload/excel/villageuser/' . date('Ymd') . '/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
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
                    unlink($path);	//删除物业上传的excel表格
                    $database_user = D('User');
                    $error_arr = array();
//                    echo '<pre>';
//                    var_dump($result);die;
                    if (!empty($result) && is_array($result)) {
                        unset($result[1]);
                        $last_user_id = 0;
                        $err_msg = '';
                        foreach ($result as $kk => $vv) {
                            if (array_sum($vv) == 0) {
                                continue;
                            }
                            if ($vv['A'] === null && $vv['B'] === null && $vv['C'] === null && $vv['D'] === null && $vv['E'] === null && $vv['F'] === null && $vv['G'] === null && $vv['H'] === null && $vv['I'] === null && $vv['J'] === null && $vv['K'] === null && $vv['L'] === null && $vv['M'] === null && $vv['N'] === null) continue;

                            // $vv['N'] = floatval($vv['N']);
                            if (empty($vv['A'])) {
                                $vv['R'] = '请填写业主编号！';
                                $error_arr[] = $vv;
                                // $err_msg = '请填写业主编号！';
                                continue;
                            }
                            if (empty($vv['B'])) {
                                $vv['R'] = '请填写业主名！';
                                $error_arr[] = $vv;
                                // $err_msg = '请填写业主名！';
                                continue;
                            }
                            if (empty($vv['C'])) {
                                $vv['R'] = '请填写手机号！';
                                $error_arr[] = $vv;
                                // $err_msg = '请填写手机号！';
                                continue;
                            }

                            if(!preg_match('/^[0-9]{11}$/',$vv['C'])){
                                $vv['R'] = '请填写正确的手机号！';
                                $error_arr[] = $vv;
                            }

                            if (empty($vv['I'])) {
                                $vv['R'] = '请填写单元名称！';
                                $error_arr[] = $vv;
                                // $err_msg = '请填写单元名称！';
                                continue;
                            }

                            if (empty($vv['J'])) {
                                $vv['R'] = '请填写楼号！';
                                $error_arr[] = $vv;
                                // $err_msg = '请填写楼号！';
                                continue;
                            }


                            if (empty($vv['K'])) {
                                $vv['R'] = '请填写层号！';
                                $error_arr[] = $vv;
                                // $err_msg = '请填写层号！';
                                continue;
                            }


                            if (empty($vv['L'])) {
                                $vv['R'] = '请填写门牌号！';
                                $error_arr[] = $vv;
                                // $err_msg = '请填写门牌号！';
                                continue;
                            }

                            // if (empty($vv['M'])) {
                            //     $err_msg = '请填写房子平方！';
                            //     continue;
                            // }

                            $floor_name = htmlspecialchars(trim($vv['I']), ENT_QUOTES);
                            $floor_layer = htmlspecialchars(trim($vv['J']), ENT_QUOTES);
                            $where['floor_name'] = $floor_name;
                            $where['floor_layer'] = $floor_layer;
                            $where['status'] = 1;
                            $where['village_id'] = $this->village_id;
                            $database_house_village_floor = D('House_village_floor');
                            $house_village_floor_info = $database_house_village_floor->where($where)->find();
                            if (!$house_village_floor_info) {
                                $vv['R'] = '单元不存在，请查看社区中心，单元管理-单元列表!';
                                $error_arr[] = $vv;
                                // $err_msg = '单元不存在，请查看社区中心，单元管理-单元列表！';
                                continue;
                            }

                            //检查房间是否存在或已绑定
                            $vwhere['village_id'] = $this->village_id;
                            $vwhere['floor_id'] =  $house_village_floor_info['floor_id'];
                            $vwhere['layer'] = htmlspecialchars(trim($vv['K']), ENT_QUOTES);
                            $vwhere['room'] = htmlspecialchars(trim($vv['L']), ENT_QUOTES);
                            $vwhere['type'] = 0;
                            $room_info = D('House_village_user_vacancy')->where($vwhere)->find();

                            if(!$room_info){
                                $err_msg = htmlspecialchars(trim($vv['I']), ENT_QUOTES).htmlspecialchars(trim($vv['J']), ENT_QUOTES). htmlspecialchars(trim($vv['K']), ENT_QUOTES).htmlspecialchars(trim($vv['L']), ENT_QUOTES)."房间不存在";
                                $vv['R'] = $err_msg;
                                $error_arr[] = $vv;
                                continue;
                            }elseif($room_info['status']!=1 || $room_info['is_del']==1 || ($room_info['name']!="" && $room_info['phone']!="")){
                                $err_msg = htmlspecialchars(trim($vv['J']), ENT_QUOTES).htmlspecialchars(trim($vv['I']), ENT_QUOTES). htmlspecialchars(trim($vv['K']), ENT_QUOTES).'层'.htmlspecialchars(trim($vv['L']), ENT_QUOTES)."可能被绑定/禁用/删除";
                                $vv['R'] = $err_msg;
                                $error_arr[] = $vv;
                                continue;
                            }


                            $tmpdata = array();
                            $tmpdata['usernum'] =$this->village_id . '-' . htmlspecialchars(trim($vv['A']), ENT_QUOTES);
                            //检测用户是否已存在
                            if (D('House_village_user_bind')->field('`usernum`')->where(array('usernum' => $tmpdata['usernum']))->find()) {
                                $err_msg = '业主名为' . $vv['B'] . ' 已存在！物业编号重复';
                                $vv['R'] = $err_msg;
                                $error_arr[] = $vv;
                                continue;
                            }

                            // 属性
                            $attribute_name = htmlspecialchars(trim($vv['Q']), ENT_QUOTES);
                            if ($attribute_name) {
                                $attribute = M('House_village_user_attribute')->where(array('village_id'=>$this->village_id,'name'=>$attribute_name))->find();
                                if ($attribute) {
                                    $tmpdata['attribute'] = $attribute['pigcms_id'];
                                }
                            }
                            $tmpdata['name'] = htmlspecialchars(str_replace(array(' ',PHP_EOL,"\t"),'',$vv['B']), ENT_QUOTES);
                            $tmpdata['phone'] = htmlspecialchars(trim($vv['C']), ENT_QUOTES);
                            $tmpdata['water_price'] = htmlspecialchars(trim($vv['D']), ENT_QUOTES);
                            $tmpdata['electric_price'] = htmlspecialchars(trim($vv['E']), ENT_QUOTES);
                            $tmpdata['gas_price'] = htmlspecialchars(trim($vv['F']), ENT_QUOTES);
                            $tmpdata['park_flag'] = htmlspecialchars(trim($vv['G']), ENT_QUOTES);
                            $tmpdata['park_price'] = htmlspecialchars(trim($vv['H']), ENT_QUOTES);
                            $tmpdata['layer_num'] = $vv['K'];
                            $room_addrss = htmlspecialchars(trim($vv['L']), ENT_QUOTES);
                            $tmpdata['room_addrss'] = $room_addrss;
                            $tmpdata['address'] = $floor_layer . $floor_name . htmlspecialchars(trim($vv['K']), ENT_QUOTES)  . '#' . $room_addrss;
                            $tmpdata['floor_id'] = $house_village_floor_info['floor_id'];



                            //$van = M('House_village_user_vacancy')->where(array('floor_id'=>$tmpdata['floor_id'],'room'=>$room_addrss,'village_id'=>$this->village_id))->find();
                            //$tmpdata['vacancy_id'] = $van['pigcms_id']?$van['pigcms_id']:0;
                            $tmpdata['village_id'] = $this->village_id;
                            if($memo = htmlspecialchars(trim($vv['M']), ENT_QUOTES)){
                                $tmpdata['memo'] = $memo;
                            }


                            $user = $database_user->get_user($tmpdata['phone'], 'phone');
                            if ($user) {
                                $tmpdata['uid'] = $user['uid'];
                            }

                            $tmpdata['add_time'] = time();

                            if($_POST['property_starttime'] > $_POST['property_endtime']){
                                $error_arr[] = $vv;
                                $vv['R'] = '请填写正确的物业时间！';
                                // $this->error('请填写正确的物业时间！');
                            }
                            $tmpdata['property_starttime'] = strtotime(trim($vv['N']));
                            $tmpdata['property_endtime'] = strtotime(trim($vv['O']));
                            $tmpdata['vacancy_id'] = $room_info['pigcms_id'];
                            if(floatval($room_info['housesize']) > 0){
                                $tmpdata['housesize'] = round($room_info['housesize'],2);
                            }else{
                                $tmpdata['housesize'] = round(htmlspecialchars(trim($vv['P']), ENT_QUOTES),2);
                            }


                            $last_user_id = D('House_village_user_bind')->data($tmpdata)->add();
                            if (!$last_user_id) {
                                $err_msg = '业主名为' . $vv['B'] . ' 导入失败！';
                                $error_arr[] = $vv;
                                $vv['R'] = $err_msg ;
                            }else{
                                //导入成功 绑定此房间
                                $data_vacancy = array();
                                $data_vacancy['uid'] = $tmpdata['uid'] ? $tmpdata['uid'] : 0;
                                $data_vacancy['status'] = 3;
                                $data_vacancy['name'] = $tmpdata['name'];
                                $data_vacancy['phone'] = $tmpdata['phone'];
                                $data_vacancy['housesize'] = $tmpdata['housesize'];
                                $data_vacancy['park_flag'] = $tmpdata['park_flag'];
                                D('House_village_user_vacancy')->where($vwhere)->data($data_vacancy)->save();
                                if (intval($last_user_id)>0) {
                                    $change_param = [];
                                    $change_param['pigcms_id'] = $last_user_id;
                                    D('House_village_user_vacancy')->change_user_bind_number($change_param);
                                }
                            }
                        }
                        if (empty($error_arr)) {
                            $this->success('导入成功');
                            exit;
                        } else {
                            $num = count($error_arr);
                            // echo "<script language='JavaScript' type='text/javascript'>alert('失败{$num}条，点击下载！')</script>";
                            //导出失败信息
                            require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
                            error_reporting(E_ALL);
                            date_default_timezone_set('Europe/London');
                            $objExcel = new PHPExcel();

                            $title = '导入失败列表';
                            // $title = $this->village['village_name'] . '社区-导入失败列表';
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
                            $objExcel->getActiveSheet()->setTitle($title);
                            $objActSheet = $objExcel->getActiveSheet();
                            $objActSheet->setCellValue('A1', '业主编号');
                            $objActSheet->setCellValue('B1', '业主名');
                            $objActSheet->setCellValue('C1', '手机号');
                            $objActSheet->setCellValue('D1', '水费总欠费');
                            $objActSheet->setCellValue('E1', '电费总欠费');
                            $objActSheet->setCellValue('F1', '燃气费总欠费');
                            $objActSheet->setCellValue('G1', '是否有停车位（1有0无）');
                            $objActSheet->setCellValue('H1', '停车费总欠费');
                            $objActSheet->setCellValue('I1', '单元名称');
                            $objActSheet->setCellValue('J1', '楼号');
                            $objActSheet->setCellValue('K1', '层号');
                            $objActSheet->setCellValue('L1', '门牌号');
                            $objActSheet->setCellValue('M1', '备注');
                            $objActSheet->setCellValue('N1', '物业开始时间');
                            $objActSheet->setCellValue('O1', '物业结束时间');
                            $objActSheet->setCellValue('P1', '房子平方(计算物业费使用)');
                            $objActSheet->setCellValue('Q1', '属性');
                            $objActSheet->setCellValue('R1', '失败信息');
                            $index = 2;
                            foreach ($error_arr as  $value) {
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
                                $objActSheet->setCellValueExplicit('O' . $index, $value['O']);
                                $objActSheet->setCellValueExplicit('P' . $index, number_format($value['P'],2));
                                $objActSheet->setCellValueExplicit('Q' . $index, $value['Q']);
                                $objActSheet->setCellValueExplicit('R' . $index, $value['R']);
                                $index++;
                            }

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
                            header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:s", time()) . '.xls"');
                            header("Content-Transfer-Encoding:binary");
                            $objWriter->save('php://output');
                            exit();
                        }

                        // if (!empty($last_user_id)) {
                        //     $this->success('导入成功');
                        //     exit;
                        // } else {
                        //     $this->error('导入失败！原因：' . $err_msg);
                        //     exit;
                        // }
                    }
                } else {
                    $this->error($upload->getErrorMsg());
                    exit;
                }
            }
            $this->error('文件上传失败');
            exit;
        } else {
            $this->display();
        }
    }
    /***
    ***导入家属
     *****/
    public function user_import_relatives(){
        $this->display();
    }
    /***
     ***导入租客
     *****/
    public function user_import_tenant(){
        $this->display();
    }

    public function user_import_excel_file()
    {
        $xtype = $_GET['xtype'];
        $xtype = $xtype ? trim($xtype) : 'user_relatives';
        if (!in_array($xtype, ['user_relatives', 'user_tenant'])) {
            $this->error('非法请求！');
            exit();
        }
        fdump_api(array('file' => $_FILES, 'xtype' => $xtype, 'village_id' => $this->village_id), 'user_import_excel_file', true);
        if ($_FILES['file']['error'] > 0) {
            $this->error('导入文件上传失败！');
            exit();
        }
        set_time_limit(0);
        ini_set('memory_limit', '2048M');

        import('ORG.Util.Dir');
        Dir::delDirnotself('./upload/excel/villageuser');

        $upload_dir = './upload/excel/villageuser/' . date('Ymd') . '/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
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
        $where_village=array();
        $where_village['village_id'] = $this->village_id;
        $property_id = D('House_village')->where($where_village)->getField('property_id');
        $where_data_config = array(
            'village_id' => 0,
            'property_id' => $property_id,
            'is_display' => 1,
            'is_close' => 0
        );
        $dataList = D('House_village_data_config')->where($where_data_config)->order('sort desc')->select();
        if ($upload->upload()) {
            $uploadList = $upload->getUploadFileInfo();
            require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel/IOFactory.php';
            $path = $uploadList['0']['savepath'] . $uploadList['0']['savename'];
            $fileType = PHPExcel_IOFactory::identify($path); //文件名自动判断文件类型

            $objReader = PHPExcel_IOFactory::createReader($fileType);
            $excelObj = $objReader->load($path);
            $result = $excelObj->getActiveSheet()->toArray(null, true, true, true);
            unlink($path);    //删除物业上传的excel表格
            $database_user = D('User');
            $village_id = $this->village_id;
            $is_func_cloudmrsqfw_special=D('Config_customization')->getFuncCloudmrsqfwSpecial();
            $house_village_user_label_serve= D('House_village_user_label');
            $house_village_user_bind_serve=D('House_village_user_bind');
            $error_arr = array();
            $bind_type= 1;
            $last_user_id = 0;
            $err_msg = '';
            $errKey = $is_func_cloudmrsqfw_special ? 'AC':'J';
            $errArrKey = 0;// 错误数据键值
            $xtitle = '导入亲属';
            if($xtype == 'user_tenant'){
                $bind_type=2;
                $errKey= $is_func_cloudmrsqfw_special ? 'AB':'J';
                $xtitle = '导入租客';
            }
            $A1Tips='';
            if (!empty($result) && is_array($result) && count($result) > 2) {
                $A1Tips=$result[1]['A'];
                unset($result[1]);
                $headerArr=$result[2];
                unset($result[2]);
                foreach ($result as $kk => $vv) {
                    if (array_sum($vv) == 0) {
                        continue;
                    }
                    if ($vv['A'] === null && $vv['B'] === null && $vv['C'] === null && $vv['D'] === null && $vv['E'] === null && $vv['F'] === null) {
                        continue;
                    }

                    $tmpdata = array('type'=>$bind_type);
                    $name = trim($vv['A']);
                    if (empty($name)) {
                        $vv[$errKey] = '请填写姓名！';
                        $error_arr[$errArrKey] = $vv;
                        $errArrKey++;
                        continue;
                    }
                    $phone = trim($vv['B']);
                    if (empty($phone) || !preg_match('/^1[0-9]{10}$/', $phone)) {
                        $vv[$errKey] = '请填写正确的手机号！';
                        $error_arr[$errArrKey] = $vv;
                        $errArrKey++;
                        continue;
                    }
                    $single_name = htmlspecialchars(trim($vv['C']), ENT_QUOTES);
                    if (empty($vv['C'])) {
                        $vv[$errKey] = '请填写楼栋名称！';
                        $error_arr[$errArrKey] = $vv;
                        $errArrKey++;
                        continue;
                    }
                    $floor_name = htmlspecialchars(trim($vv['D']), ENT_QUOTES);
                    if (empty($floor_name)) {
                        $vv[$errKey] = '请填写单元名称！';
                        $error_arr[$errArrKey] = $vv;
                        $errArrKey++;
                        continue;
                    }
                    $layer_name = htmlspecialchars(trim($vv['E']), ENT_QUOTES);
                    if (empty($layer_name)) {
                        $vv[$errKey] = '请填写楼层名称！';
                        $error_arr[$errArrKey] = $vv;
                        $errArrKey++;
                        continue;
                    }
                    $room = htmlspecialchars(trim($vv['F']), ENT_QUOTES);
                    if (empty($room)) {
                        $vv[$errKey] = '请填写房间号！';
                        $error_arr[$errArrKey] = $vv;
                        $errArrKey++;
                        continue;
                    }
                    
                    $single_data = [];
                    $single_data['single_name'] = $single_name;
                    $single_data['village_id'] = $village_id;
                    $single_data['status'] = 1;
                    $single = M('House_village_single')->where($single_data)->find();
                    if (!$single) {
                        $vv[$errKey] = '楼栋不存在！';
                        $error_arr[$errArrKey] = $vv;
                        $errArrKey++;
                        continue;
                    }
                    $where = [];
                    $where['floor_name'] = $floor_name;
                    $where['status'] = 1;
                    $where['village_id'] = $village_id;
                    $where['single_id'] = $single['id'];
                    $database_house_village_floor = D('House_village_floor');
                    $house_village_floor_info = $database_house_village_floor->where($where)->find();
                    if (!$house_village_floor_info) {
                        $vv[$errKey] = '单元不存在，请查看社区中心，单元管理-单元列表!';
                        $error_arr[$errArrKey] = $vv;
                        $errArrKey++;
                        continue;
                    }
                    $where_layer = array(
                        'village_id' => $village_id,
                        'layer_name' => $layer_name,
                        'single_id' => $single['id'],
                        'floor_id' => $house_village_floor_info['floor_id'],
                        'status' => ['neq', 4]
                    );
                    $layer = M('House_village_layer')->where($where_layer)->find();
                    if (empty($layer)) {
                        $err_msg = '楼层不存在';
                        $vv[$errKey] = $err_msg;
                        $error_arr[$errArrKey] = $vv;
                        $errArrKey++;
                        continue;
                    }
                    $tmpdata['layer_id'] = $layer['id'];
                    //检查房间是否存在或已绑定
                    $vwhere = [];
                    $vwhere['village_id'] = $village_id;
                    $vwhere['floor_id'] = $house_village_floor_info['floor_id'];
                    $vwhere['layer_id'] = $layer['id'];
                    $vwhere['room'] = $room;
                    $vwhere['type'] = 0;
                    $vwhere['is_del'] = 0;
                    $vwhere['status'] = ['neq', 4];
                    $room_info = D('House_village_user_vacancy')->where($vwhere)->find();
                    if (!$room_info) {
                        $vv[$errKey] = '房间不存在!';
                        $error_arr[$errArrKey] = $vv;
                        $errArrKey++;
                        continue;
                    } elseif ($room_info['is_del'] == 1) {
                        $vv[$errKey] = '此房间已被删除!';
                        $error_arr[$errArrKey] = $vv;
                        $errArrKey++;
                        continue;
                    }
                    $whereRoomOwner = [
                        'status' => 1,
                        'vacancy_id' => $room_info['pigcms_id'],
                        'type' => ['in', [0, 3]],
                    ];
                    $roomOwnerUser = D('House_village_user_bind')->where($whereRoomOwner)->field('*')->find();
                    if (empty($roomOwnerUser)) {
                        $vv[$errKey] = '此房间没有绑定业主!';
                        $error_arr[$errArrKey] = $vv;
                        $errArrKey++;
                        continue;
                    }
                    $tmpdata['parent_id']=$roomOwnerUser['pigcms_id'];
                    $tmpdata['usernum'] = $room_info['pigcms_id'] . '_' . time() . '_' . rand(1000, 9999);

                    $tmpdata['name'] = htmlspecialchars(str_replace(array(' ', PHP_EOL, "\t"), '', $name), ENT_QUOTES);
                    $tmpdata['phone'] = htmlspecialchars($phone, ENT_QUOTES);
                    $tmpdata['layer_num'] = $layer_name;
                    $tmpdata['apply_time'] = time();
                    $tmpdata['adopt_time'] = time();
                    $room_addrss = $room;
                    $tmpdata['room_addrss'] = $room_addrss;
                    $tmpdata['address'] = $single_name . $floor_name . '#' . $room_addrss;
                    $tmpdata['floor_id'] = $house_village_floor_info['floor_id'];
                    $tmpdata['single_id'] = $roomOwnerUser['single_id'];
                    $tmpdata['village_id'] = $village_id;


                    $id_card = trim($vv['G']) ? trim($vv['G']) : '';
                    if ($id_card && !is_idcard($id_card)) {
                        $vv[$errKey] = '身份证数据错误，不是正确的身份证号';
                        $error_arr[$errArrKey] = $vv;
                        $errArrKey++;
                    }
                    $idCard=idCardAnalysis($id_card);
                    $authenticationArr=array();
                    if($idCard && isset($idCard['birthday'])){
                        $authenticationArr['birthday']=$idCard['birthday'];
                    }
                    if($idCard && isset($idCard['sex'])){
                        $authenticationArr['sex']=$idCard['sex'];
                    }
                    $tmpdata['id_card'] = $id_card;
                    
                    $ic_card = trim($vv['H']) ? trim($vv['H']) : '';
                    $tmpdata['ic_card'] = $ic_card;
                    
                    $relatives_type_str='';
                    $letter_next='';
                    $bind_type=1;
                    $bind_type_str='家属';
                    if ($xtype == 'user_relatives') {
                        $relatives_type_str = trim($vv['I']) ? trim($vv['I']) : '';   //亲属关系  配偶，父母，子女，亲朋好友
                        $party_name = trim($vv['J']) ? trim($vv['J']) : '';  //政治面貌 党员，团员，群众
                        $letter_next='K';
                    }else{
                        $bind_type=2;  //租客
                        $bind_type_str='租客';
                        $party_name = trim($vv['I']) ? trim($vv['I']) : '';  //政治面貌 党员，团员，群众
                        $letter_next='J';
                    }
                    switch ($relatives_type_str){
                        case '配偶':
                            $tmpdata['relatives_type']=1;
                            break;
                        case '父母':
                            $tmpdata['relatives_type']=2;
                            break;
                        case '配偶':
                            $tmpdata['relatives_type']=3;
                            break;
                        case '子女':
                            $tmpdata['relatives_type']=4;
                            break;
                        default:
                            $tmpdata['relatives_type']=4;
                            break;
                    }

                    $user = array();
                    if ($tmpdata['phone']) {
                        $user = D('User')->where(array('phone' => $tmpdata['phone']))->order('uid desc')->find();
                    }
                    if (empty($user) && $tmpdata['phone']) {
                        $user_add = [];
                        $user_add['phone'] = $tmpdata['phone'];
                        if ($tmpdata['name']) {
                            $user_add['nickname'] = $tmpdata['name'];
                        }
                        $user_add['source'] = 'houseautoreg';
                        $reg_result = D('User')->autoreg($user_add);
                        $uid = $reg_result['uid'];
                        $user['uid'] = $uid;
                    }
                    $tmpdata['uid'] = $user['uid'] ? intval($user['uid']) : 0;
                    $user_tag_arr=array('user_political_str'=>$party_name,'party_type_str'=>'','user_party_str'=>'','user_special_groups'=>'','user_focus_groups'=>'','user_vulnerable_groups'=>'','uid'=>$tmpdata['uid']);
                    if($is_func_cloudmrsqfw_special){
                        $user_tag_arr['party_type_str'] = htmlspecialchars(trim($vv[$letter_next]), ENT_QUOTES);
                        $letter_next++;
                        $user_tag_arr['user_party_str'] = htmlspecialchars(trim($vv[$letter_next]), ENT_QUOTES);
                        $letter_next++;
                        $user_tag_arr['user_special_groups'] = htmlspecialchars(trim($vv[$letter_next]), ENT_QUOTES);
                        $letter_next++;
                        $user_tag_arr['user_focus_groups'] = htmlspecialchars(trim($vv[$letter_next]), ENT_QUOTES);
                        $letter_next++;
                        $user_tag_arr['user_vulnerable_groups'] = htmlspecialchars(trim($vv[$letter_next]), ENT_QUOTES);
                        $systemConfigAttr=array('sex'=>'','birthday'=>'','nation'=>'','native_place'=>'','nationality'=>'','household_register'=>'','marriage_status'=>'','edcation'=>'','emergent_person'=>'','person_phone'=>'','job'=>'','unit_nature'=>'','job_address'=>'');
                        $letter_next++;
                        $systemConfigAttr['sex'] = htmlspecialchars(trim($vv[$letter_next]), ENT_QUOTES);
                        $letter_next++;
                        $systemConfigAttr['birthday'] = htmlspecialchars(trim($vv[$letter_next]), ENT_QUOTES);
                        $letter_next++;
                        $systemConfigAttr['nation'] = htmlspecialchars(trim($vv[$letter_next]), ENT_QUOTES);
                        $letter_next++;
                        $systemConfigAttr['native_place'] = htmlspecialchars(trim($vv[$letter_next]), ENT_QUOTES);
                        $letter_next++;
                        $systemConfigAttr['nationality'] = htmlspecialchars(trim($vv[$letter_next]), ENT_QUOTES);
                        $letter_next++;
                        $systemConfigAttr['household_register'] = htmlspecialchars(trim($vv[$letter_next]), ENT_QUOTES);
                        $letter_next++;
                        $systemConfigAttr['marriage_status'] = htmlspecialchars(trim($vv[$letter_next]), ENT_QUOTES);
                        $letter_next++;
                        $systemConfigAttr['edcation'] = htmlspecialchars(trim($vv[$letter_next]), ENT_QUOTES);
                        $letter_next++;
                        $systemConfigAttr['emergent_person'] = htmlspecialchars(trim($vv[$letter_next]), ENT_QUOTES);
                        $letter_next++;
                        $systemConfigAttr['person_phone'] = htmlspecialchars(trim($vv[$letter_next]), ENT_QUOTES);
                        $letter_next++;
                        $systemConfigAttr['job'] = htmlspecialchars(trim($vv[$letter_next]), ENT_QUOTES);
                        $letter_next++;
                        $systemConfigAttr['unit_nature'] = htmlspecialchars(trim($vv[$letter_next]), ENT_QUOTES);
                        $letter_next++;
                        $systemConfigAttr['job_address'] = htmlspecialchars(trim($vv[$letter_next]), ENT_QUOTES);
                        $authenticationArr=array_merge($systemConfigAttr,$authenticationArr);
                    }
                    $updateUserArr=array('name'=>$tmpdata['name'],'id_card'=>$id_card,'room_id'=>$room_info['pigcms_id'],'ic_card'=>$ic_card,'property_id'=>$this->house_session['property_id'],'authentication_field'=>'','authenticationArr'=>$authenticationArr,'user_tag_arr'=>$user_tag_arr,'systemConfigAttr'=>$dataList);
                    $whereRoomBind = [
                        'status' => array('in',[1,2]),
                        'vacancy_id' => $room_info['pigcms_id'],
                        'phone'=>$phone,
                        'type' => $bind_type,
                    ];
                    $bind_pigcms_id=0;
                    $user_bind = D('House_village_user_bind')->where($whereRoomBind)->field('pigcms_id,authentication_field')->find();
                    if ($user_bind && $user_bind['pigcms_id']) {
                        $updateUserArr['authentication_field']=$user_bind['authentication_field'] ? unserialize($user_bind['authentication_field']):'';
                        $errMsg='此房间已绑定了手机号是'.$phone.'的'.$bind_type_str.'!'.$user_bind['pigcms_id'];
                        if($is_func_cloudmrsqfw_special) {
                            $house_village_user_bind_serve->updateUserBindInfo($user_bind['pigcms_id'], $updateUserArr, $this->village_id);
                            $errMsg='此房间已绑定了手机号是'.$phone.'的'.$bind_type_str.',数据已更新！'.$user_bind['pigcms_id'];
                        }
                        $bind_pigcms_id=$user_bind['pigcms_id'];
                        $vv[$errKey] = $errMsg;
                        $error_arr[$errArrKey] = $vv;
                        $errArrKey++;
                        continue;
                    }

                    $tmpdata['add_time'] = time();

                    $tmpdata['vacancy_id'] = $room_info['pigcms_id'];
                    if ($room_info['housesize'] > 0.00) {
                        $tmpdata['housesize'] = round($room_info['housesize'], 2);
                    }
                    if($authenticationArr){
                        $authentication_field_arr=$house_village_user_bind_serve->get_authentication_field($authenticationArr,$dataList);
                        $tmpdata['authentication_field']=$authentication_field_arr ? serialize($authentication_field_arr):'';
                    }
                    $last_user_id = D('House_village_user_bind')->data($tmpdata)->add();
                    if (!$last_user_id) {
                        $err_msg = '姓名为' . $vv['A'] . ' 导入失败！';
                        if (!isset($vv[$errKey]) || !$vv[$errKey]) {
                            $vv[$errKey] = $err_msg;
                        } else {
                            $vv[$errKey] .= '；' . $err_msg;
                        }
                        $error_arr[$errArrKey] = $vv;
                    } else {
                        if($is_func_cloudmrsqfw_special){
                            $house_village_user_label_serve->updateUserTagAttr($last_user_id, $user_tag_arr, $this->village_id);
                        }else{
                            $user_political_affiliation=0;
                            switch ($party_name){
                                case '党员':
                                    $user_political_affiliation=1;
                                    break;
                                case '团员':
                                    $user_political_affiliation=2;
                                    break;
                                case '群众':
                                    $user_political_affiliation=3;
                                    break;
                                default:
                                    $user_political_affiliation=0;
                                    break;
                            }
                            if($user_political_affiliation>0){
                                $user_label_arr=[
                                    'user_political_affiliation'=>$user_political_affiliation,
                                    'party_type'=>2,
                                    'pigcms_id'=>$last_user_id,
                                    'uid'=> $tmpdata['uid'],
                                ];
                                $house_village_user_label_serve->saveUserLabelMethod($user_label_arr);
                            }
                        }

                        /**
                         * $arr = array();
                         * $arr['param'] = serialize(array('bind_id' =>$last_user_id,'type'=>'user'));
                         * $arr['plan_time'] = time()+60;
                         * $arr['space_time'] = 0;
                         * $arr['add_time'] = time();
                         * $arr['file'] = 'sub_house_village_third';
                         * $arr['time_type'] = 1;
                         * $arr['unique_id'] = 'sub_house_village_third_user_'.$last_user_id;
                         * $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
                         * M('process_sub_plan')->data($arr)->add();
                         * //导入成功 绑定此房间
                         * $data_vacancy = array();
                         * $data_vacancy['uid'] = $tmpdata['uid'] ? $tmpdata['uid'] : 0;
                         * $data_vacancy['status'] = 3;
                         * $data_vacancy['name'] = $tmpdata['name'];
                         * $data_vacancy['phone'] = $tmpdata['phone'];
                         * D('House_village_user_vacancy')->where($vwhere)->data($data_vacancy)->save();
                         **/
                    }
                    if (isset($vv[$errKey]) && $vv[$errKey]) {
                        $errArrKey++;
                    }
                }
                if (empty($error_arr)) {
                    $this->success('导入成功。');
                    exit;
                } else {
                    //导出失败信息
                    require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
                    error_reporting(E_ALL);
                    date_default_timezone_set('Europe/London');
                    $objExcel = new PHPExcel();

                    $title = $xtitle.'失败列表';
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
                    $objExcel->getActiveSheet()->setTitle($title);
                    $objActSheet = $objExcel->getActiveSheet();
                    
                    foreach ($headerArr as $cell=>$vv) {
                        $objActSheet->getColumnDimension($cell)->setWidth(30);
                    }
                    $objActSheet->getDefaultRowDimension()->setRowHeight(22); //设置默认行高
                    if($A1Tips){
                        $objActSheet->setCellValue('A1',$A1Tips );
                        $columnCell='A1:K1';
                        if($is_func_cloudmrsqfw_special){
                            $columnCell='A1:AC1';
                        }
                        $objActSheet->mergeCells($columnCell);
                        $objActSheet->getStyle($columnCell)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                        $objActSheet->getRowDimension(1)->setRowHeight(30); // 固定高度
                    }
                    foreach ($headerArr as $kk=>$vv){
                        $objActSheet->setCellValue($kk.'2', $vv);
                    }
                    $objActSheet->setCellValue($errKey . '2', '失败信息');
                    $index = 3;
                    $error_arr = array_values($error_arr);
                    foreach ($error_arr as $value) {
                        foreach ($headerArr as $kk=>$vv){
                            $objActSheet->setCellValueExplicit($kk . $index, $value[$kk]);
                        }
                        $objActSheet->setCellValueExplicit($errKey . $index, $value[$errKey]);
                        $index++;
                    }
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
            } else {
                $this->error('导入失败！请检查表格数据。');
                exit;
            }
        } else {
            $this->error($upload->getErrorMsg());
            exit;
        }

    }

    public function user_import_excel_demo()
    {
        $xtype = $_GET['xtype'];
        $xtype = $xtype ? trim($xtype) : 'user_relatives';
        if (!in_array($xtype, ['user_relatives', 'user_tenant'])) {
            $this->error('非法请求！');
        }
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $user_list = array();

        $xtitle = '导入亲属模板';
        $tips='所有单元格请设置成文本格式后填写内容。政治面貌填写（党员，团员，群众），亲属关系填写（配偶，父母，子女，亲朋好友）';
        if ($xtype == 'user_tenant') {
            $xtitle = '导入租客模板';
            $user_list[] = array(
                '端木华菲', '18965000156', '5栋', '2', '8层', '2',
                '342417191008176337', '123457', '群众'
            );
            $tips='所有单元格请设置成文本格式后填写内容。政治面貌填写（党员，团员，群众）';
        } else {
            $user_list[] = array(
                '慕容嫣紫', '19765201867', '7栋', '2', '17层', '1701',
                '342417190008156539', '123456', '配偶', '党员'
            );
        }
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($xtitle);
        $objProps->setTitle($xtitle);
        $objProps->setSubject($xtitle);
        $objProps->setDescription($xtitle);


        $objExcel->createSheet();
        $objExcel->setActiveSheetIndex(1);

        $objExcel->getActiveSheet()->setTitle($xtitle);
        $objActSheet = $objExcel->getActiveSheet();
        $objActSheet->getDefaultRowDimension()->setRowHeight(22); //设置默认行高
        $is_func_cloudmrsqfw_special=D('Config_customization')->getFuncCloudmrsqfwSpecial();
        $columnCell='A1:K1';
        if($is_func_cloudmrsqfw_special){
            $tips='所有单元格请设置成文本格式后填写内容。政治面貌填写（党员,团员,群众）,社区党员组织关系是当党员是社区党员时填写才有效。特殊人群请填写（刑满释放,社区矫正,重点青少年,吸毒,艾滋病,精神病）多个值用,分隔，重点人群请填写（服刑人员,非法组织,上访人员）多个值用,分隔，关怀群体请填写（低保,残疾人,空巢,失独老人,孤寡老人,退役军人,留守儿童）多个值用,分隔。性别请填写（男,女），籍贯请填写从网站后台系统设置=》城市区域里面的省市名称例如(安徽,合肥)中间用,分隔，婚姻状况请填写（未婚,已婚,离婚,丧偶），教育水平请填写（无,初中及以下,高中或中专,大专,本科,硕士,博士,其他），单位性质请填写（国有企业,民营企业,外商独资,中外合资,港澳台企业,政府机关,事业单位,非营利性组织,自主创业,其他），国籍默认请不填或者填中国，其他值从业主编辑=》业主资料里找，民族请填写（汉族,满族,蒙古族,回族,藏族,维吾尔族,苗族,彝族,壮族,布依族,侗族,瑶族,白族,土家族,哈尼族,哈萨克族,傣族,黎族,傈僳族,佤族,畲族,高山族,拉祜族,水族,东乡族,纳西族,景颇族,柯尔克孜族,土族,达斡尔族,仫佬族,羌族,布朗族,撒拉族,毛南族,仡佬族,锡伯族,阿昌族,普米族,朝鲜族,塔吉克族,怒族,乌孜别克族,俄罗斯族,鄂温克族,德昂族,保安族,裕固族,京族,塔塔尔族,独龙族,鄂伦春族,赫哲族,门巴族,珞巴族,基诺族）';
            $columnCell='A1:AD1';
        }
        $objActSheet->setCellValue('A1',$tips );
        $objActSheet->mergeCells($columnCell);
        $objActSheet->getStyle($columnCell)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objActSheet->getRowDimension(1)->setRowHeight(30); // 固定高度

        $objActSheet->setCellValue('A2', '姓名(必填)');
        $objActSheet->setCellValue('B2', '手机号(必填)');
        $objActSheet->setCellValue('C2', '楼栋名称(必填)');
        $objActSheet->setCellValue('D2', '单元名称(必填)');
        $objActSheet->setCellValue('E2', '楼层名称(必填)');
        $objActSheet->setCellValue('F2', '房间号(必填)');
        $objActSheet->setCellValue('G2', '身份证号(选填)');
        $objActSheet->setCellValue('H2', 'IC卡号(选填)');
        $nextletter='';
        if ($xtype == 'user_relatives') {
            $objActSheet->setCellValue('I2', '亲属关系(必填)');
            $objActSheet->setCellValue('J2', '政治面貌');
            $nextletter='K';
        }else{
            $objActSheet->setCellValue('I2', '政治面貌');
            $nextletter='J';
        }
        $cell_list = range('A', 'K');
        if($is_func_cloudmrsqfw_special){
            $cell_list = range('A', 'Z');
            $cell_list=array_merge($cell_list,['AA','AB','AC','AD']);
        }
        foreach ($cell_list as $cell) {
            $objActSheet->getColumnDimension($cell)->setWidth(30);
        }
        if($is_func_cloudmrsqfw_special){
            $objActSheet->setCellValue($nextletter.'2', '社区党员（是或否）');
            $user_list[0][]='否';
            $nextletter++;
            $objActSheet->setCellValue($nextletter.'2', '社区党员组织关系');
            $user_list[0][]='';
            $nextletter++;
            $objActSheet->setCellValue($nextletter.'2', '特殊人群');
            $user_list[0][]='社区矫正,重点青少年';
            $nextletter++;
            $objActSheet->setCellValue($nextletter.'2', '重点人群');
            $user_list[0][]='非法组织';
            $nextletter++;
            $objActSheet->setCellValue($nextletter.'2', '关怀群体');
            $user_list[0][]='残疾人,空巢,孤寡老人,退役军人,留守儿童';
            $objActSheet->getColumnDimension($nextletter)->setWidth(50);
            $property_id=$this->house_session['property_id'];
            $dataList = M('House_village_data_config')->where(array('property_id'=>$property_id,'is_system'=>1))->order('sort desc')->select();
            if($dataList){
                $systemConfigAttr=array('sex'=>'男','birthday'=>'1992-03-21','nation'=>'满族','native_place'=>'安徽,滁州','nationality'=>'中国','household_register'=>'南谯区乌衣镇康泰路与大同路交叉口','marriage_status'=>'离婚','edcation'=>'本科','emergent_person'=>'皇甫涛','person_phone'=>'13212345593','job'=>'','unit_nature'=>'其他','job_address'=>'');
                $nextletter++;
                foreach ($dataList as $kk=>$vv){
                    $kkey=trim($vv['key']);
                    if(!isset($systemConfigAttr[$kkey])){
                        continue;
                    }
                    if(in_array($kkey,['birthday','sex','nation','marriage_status','edcation','nationality'])){
                        $objActSheet->getColumnDimension($nextletter)->setWidth(20);
                    }elseif (in_array($kkey,['household_register'])){
                        $objActSheet->getColumnDimension($nextletter)->setWidth(40);
                    }
                    $user_list[0][]=$systemConfigAttr[$kkey];
                    $title=trim($vv['title']);
                    $objActSheet->setCellValue($nextletter.'2', $title);
                    $nextletter++;
                }
            }
        }
        
        if (!empty($user_list)) {
            $index = 3;
            foreach ($user_list as $value) {
                $letter='A';
                foreach ($value as $vStr) {
                    $objActSheet->setCellValueExplicit($letter. $index, $vStr);
                    $letter++;
                }
                /*
                $objActSheet->setCellValueExplicit('A' . $index, $value[0]);
                $objActSheet->setCellValueExplicit('B' . $index, $value[1]);
                $objActSheet->setCellValueExplicit('C' . $index, $value[2]);
                $objActSheet->setCellValueExplicit('D' . $index, $value[3]);
                $objActSheet->setCellValueExplicit('E' . $index, $value[4]);
                $objActSheet->setCellValueExplicit('F' . $index, $value[5]);
                $objActSheet->setCellValueExplicit('G' . $index, $value[6]);
                $objActSheet->setCellValueExplicit('H' . $index, $value[7]);
                if ($xtype == 'user_relatives') {
                    $objActSheet->setCellValueExplicit('I' . $index, $value[8]);
                    $objActSheet->setCellValueExplicit('J' . $index, $value[9]);
                }else{
                    $objActSheet->setCellValueExplicit('I' . $index, $value[8]);
                }
                */
                $index++;
            }
        }
        sleep(2);
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
        header('Content-Disposition:attachment;filename="' . $xtitle . '_' . date("Y-m-d h:i:sa", time()) . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();

    }

    // 欠费明细
    public function pay_detail()
    {
        //业主-查看欠费明细 权限
        if (0&&!in_array(118, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $village_id = $this->village_id;
        $usernum = $_GET['usernum'];

        if ($village_id && $usernum) {
            $list = D('House_village_user_paylist')->get_limit_list_page($usernum, $village_id);

            $this->assign('user_list', $list);
        }

        $this->display();
    }

    public function pay_one_del(){
        //业主-删除欠费明细 权限
        if (0&&!in_array(119, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $village_id = $this->village_id;
        $pigcms_id = $_GET['pigcms_id'];

        $pay_condition['village_id'] = $village_id;
        $pay_condition['pigcms_id'] = $pigcms_id;
        $database_house_village_user_paylist = D('House_village_user_paylist');
        $insert_id = $database_house_village_user_paylist->where($pay_condition)->delete();

        if($insert_id){
            $this->success('删除成功！');
        }else{
            $this->error('删除失败！');
        }
        echo $pigcms_id;
    }

    //导入账单重新改===>郭杰
    public function detail_import_test()
    {
        //业主管理-导入账单明细 权限
        if (0&&!in_array(117, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        if (IS_POST) {
            if (!$_POST['paytime']) {
                $this->error('请选择时间');
                exit;
            }
            $yearArray = explode('年', $_POST['paytime']);
            $year = $yearArray[0];
            $m = str_replace('月', '', $yearArray[1]);

            unset($_POST['paytime']);
            $_POST['ydate'] = $year;
            $_POST['mdate'] = intval($m);

            if ($_FILES['file']['error'] != 4) {
                $upload_dir = './upload/house/excel/paydetail/' . date('Ymd') . '/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
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

                    $old_end_user_id = D('House_village_user_paylist')->field('pigcms_id')->order('pigcms_id DESC')->find();

                    if (!empty($result) && is_array($result)) {
                        unset($result[1]);
                        $last_user_id = 0;
                        $err_msg = '';
                        foreach ($result as $kk => $vv) {
                            if ($vv['A'] === null && $vv['B'] === null && $vv['C'] === null && $vv['D'] === null && $vv['E'] === null && $vv['F'] === null && $vv['G'] === null && $vv['H'] === null) continue;

                            if (empty($vv['A'])) {
                                $err_msg = '请填写业主编号！';
                                continue;
                            }
                            // if (empty($vv['B'])) {
                            //     $err_msg = '请填写业主名！';
                            //     continue;
                            // }
                            // if (empty($vv['C'])) {
                            //     $err_msg = '请填写手机号！';
                            //     continue;
                            // }
                            //if (empty($vv['I'])) {
                            //    $err_msg = '请填写住址！';
                            //    continue;
                            //}

                            $tmpdata = array();
                            $tmpdata['mdate'] = $_POST['mdate'];
                            $tmpdata['ydate'] = $_POST['ydate'];
                            $tmpdata['village_id'] = $this->village_id;
                            $tmpdata['usernum'] = htmlspecialchars(trim($vv['A']), ENT_QUOTES);
                            //检测业主是否已经导入
                            $condition = array('usernum' => $tmpdata['usernum'], 'ydate' => $tmpdata['ydate'], 'mdate' => $tmpdata['mdate']);
                            $pay_list = D('House_village_user_paylist')->field('`usernum`')->where($condition)->find();
                            if ($pay_list) {
                                $err_msg = '业主 ' . $vv['B'] . ' 当月帐单已导入';
                                continue;
                            }

                            $conditionBind = array('village_id' => $this->village_id, 'usernum' => $tmpdata['usernum']);
                            $bindInfo = D('House_village_user_bind')->where($conditionBind)->find();
                            if (!$bindInfo) {
                                $err_msg = '通过业主编号没找到 ' . $vv['B'];
                                continue;
                            }

                            //if($bindInfo['address'] != htmlspecialchars(trim($vv['I']), ENT_QUOTES)){
                            //    $err_msg = '地址填写不一致！ ';
                            //    continue;
                            //}

                            $tmpdata['name'] = htmlspecialchars(trim($vv['B']), ENT_QUOTES);
                            $tmpdata['phone'] = htmlspecialchars(trim($vv['C']), ENT_QUOTES);
                            $tmpdata['use_water'] = floatval(htmlspecialchars(trim($vv['D']), ENT_QUOTES));
                            $tmpdata['use_electric'] = floatval(htmlspecialchars(trim($vv['E']), ENT_QUOTES));
                            $tmpdata['use_gas'] = floatval(htmlspecialchars(trim($vv['F']), ENT_QUOTES));
                            $tmpdata['use_property'] = intval(htmlspecialchars(trim($vv['G']), ENT_QUOTES));
                            $tmpdata['use_park'] = intval(htmlspecialchars(trim($vv['H']), ENT_QUOTES));
                            // $tmpdata['address'] = htmlspecialchars(trim($vv['I']), ENT_QUOTES);
                            $tmpdata['bind_id'] = $bindInfo['pigcms_id'];
                            $tmpdata['uid'] = $bindInfo['uid'];
                            $tmpdata['add_time'] = $_SERVER['REQUEST_TIME'];

                            //print_r($tmpdata);
                            //exit;
                            $floor_where['floor_id'] = $bindInfo['floor_id'];
                            $floor_where['status'] = 1;
                            $database_house_village_floor = D('House_village_floor');
                            $floor_info = $database_house_village_floor->house_village_floor_detail($floor_where);
                            $floor_info = $floor_info['detail'];

                            if ($tmpdata['use_water']) {
                                if(($floor_info['water_fee'] == '0.00') || (!isset($floor_info['water_fee']))){
                                    $water_price = $this->village['water_price'];
                                }else{
                                    $water_price = $floor_info['water_fee'];
                                }
                                $tmpdata['water_price'] = $tmpdata['use_water'] * $water_price;
                                D('House_village_user_bind')->where($conditionBind)->setInc('water_price', $tmpdata['water_price']);
                            }
                            if ($tmpdata['use_electric']) {
                                if(($floor_info['electric_fee'] == '0.00') || (!isset($floor_info['electric_fee']))){
                                    $electric_price = $this->village['electric_price'];
                                }else{
                                    $electric_price = $floor_info['electric_fee'];
                                }
                                $tmpdata['electric_price'] = $tmpdata['use_electric'] * $electric_price;
                                D('House_village_user_bind')->where($conditionBind)->setInc('electric_price', $tmpdata['electric_price']);
                            }
                            if ($tmpdata['use_gas']) {
                                if(($floor_info['gas_fee'] == '0.00') || (!isset($floor_info['gas_fee']))){
                                    $gas_price = $this->village['gas_price'];
                                }else{
                                    $gas_price = $floor_info['gas_fee'];
                                }
                                $tmpdata['gas_price'] = $tmpdata['use_gas'] * $gas_price;
                                D('House_village_user_bind')->where($conditionBind)->setInc('gas_price', $tmpdata['gas_price']);
                            }
                            if ($tmpdata['use_property']) {
                                if(($floor_info['property_fee'] == '0.00') || (!isset($floor_info['property_fee']))){
                                    $property_fee = $this->village['property_price'];
                                }else{
                                    $property_fee = $floor_info['property_fee'];
                                }
                                $tmpdata['property_price'] = $property_fee * $bindInfo['housesize'];
                                D('House_village_user_bind')->where($conditionBind)->setInc('property_price', $tmpdata['property_price']);
                            }
                            if ($tmpdata['use_park']) {
                                $tmpdata['park_price'] = $this->village['park_price'];
                                D('House_village_user_bind')->where($conditionBind)->setInc('park_price', $this->village['park_price']);
                            }

                            $last_user_id = D('House_village_user_paylist')->data($tmpdata)->add();
                            if (!$last_user_id) {
                                $err_msg = $vv['B'] . ' 帐单导入失败';
                            }
                        }
                        if (!empty($last_user_id)) {
                            // 模板消息
                            $this->send($old_end_user_id['pigcms_id'], $last_user_id);

                        } else {
                            $this->error('导入失败！原因：' . $err_msg);
                            exit;
                        }
                    }
                } else {
                    $this->error($upload->getErrorMsg());
                    exit;
                }
            }
            $this->error('文件上传失败');
            exit;
        } else {
            $this->display();
        }
    }

    // 业主管理-导入账单明细
    public function detail_import()
    {
        //业主管理-导入账单明细 权限
        if (0&&!in_array(117, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        if (IS_POST) {
            if (!$_POST['paytime']) {
                $this->error('请选择时间');
                exit;
            }
            $yearArray = explode('年', $_POST['paytime']);
            $year = $yearArray[0];
            $m = str_replace('月', '', $yearArray[1]);

            unset($_POST['paytime']);
            $_POST['ydate'] = $year;
            $_POST['mdate'] = intval($m);

            if ($_FILES['file']['error'] != 4) {
                $upload_dir = './upload/house/excel/paydetail/' . date('Ymd') . '/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
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

                    $old_end_user_id = D('House_village_user_paylist')->field('pigcms_id')->order('pigcms_id DESC')->find();

                    if (!empty($result) && is_array($result)) {
                        unset($result[1]);
                        $last_user_id = 0;
                        $err_msg = '';
                        foreach ($result as $kk => $vv) {
                            if ($vv['A'] === null && $vv['B'] === null && $vv['C'] === null && $vv['D'] === null && $vv['E'] === null && $vv['F'] === null && $vv['G'] === null && $vv['H'] === null) continue;

                            if (empty($vv['A'])) {
                                $err_msg = '请填写业主编号！';
                                continue;
                            }
                            // if (empty($vv['B'])) {
                            //     $err_msg = '请填写业主名！';
                            //     continue;
                            // }
                            // if (empty($vv['C'])) {
                            //     $err_msg = '请填写手机号！';
                            //     continue;
                            // }
                            //if (empty($vv['I'])) {
                            //    $err_msg = '请填写住址！';
                            //    continue;
                            //}

                            $tmpdata = array();
                            $data_park['mdate'] = $data_gas['mdate'] = $data_electric['mdate'] = $data_water['mdate'] = $tmpdata['mdate'] = $_POST['mdate'];
                            $data_park['ydate'] = $data_gas['ydate'] = $data_electric['ydate'] = $data_water['ydate'] = $tmpdata['ydate'] = $_POST['ydate'];
                            $data_park['village_id'] = $data_gas['village_id'] = $data_electric['village_id'] = $data_water['village_id'] = $tmpdata['village_id'] = $this->village_id;
                            $data_park['usernum'] = $data_gas['usernum'] = $data_electric['usernum'] = $data_water['usernum'] = $tmpdata['usernum'] = htmlspecialchars(trim($vv['A']), ENT_QUOTES);
                            //检测业主是否已经导入

                            $condition['_complex'] = array(
                                '_logic' => 'or',
                                array('usernum' => $tmpdata['usernum'], 'ydate' => $tmpdata['ydate'], 'mdate' => $tmpdata['mdate']),
                                array('bind_number' => $tmpdata['usernum'], 'ydate' => $tmpdata['ydate'], 'mdate' => $tmpdata['mdate']),
                            );
                            $pay_list = D('House_village_user_paylist')->field('`usernum`')->where($condition)->find();
                            if ($pay_list) {
                                $err_msg = '业主 ' . $vv['B'] . ' 当月帐单已导入';
                                continue;
                            }

                            //$conditionBind = array('village_id' => $this->village_id, 'usernum' => $tmpdata['usernum']);
                            $conditionBind['_complex'] = array(
                                '_logic' => 'or',
                                array('village_id' => $this->village_id,'usernum'=>$tmpdata['usernum']),
                                array('village_id' => $this->village_id,'bind_number'=>$tmpdata['usernum']),

                            );
                            $bindInfo = D('House_village_user_bind')->where($conditionBind)->find();
                            if (!$bindInfo) {
                                $err_msg = '通过业主编号没找到 ' . $vv['B'];
                                continue;
                            }

                            //if($bindInfo['address'] != htmlspecialchars(trim($vv['I']), ENT_QUOTES)){
                            //    $err_msg = '地址填写不一致！ ';
                            //    continue;
                            //}

                            $data_park['name'] = $data_gas['name'] = $data_electric['name'] = $data_water['name'] = $tmpdata['name'] = htmlspecialchars(trim($vv['B']), ENT_QUOTES);
                            $data_park['phone'] = $data_gas['phone'] = $data_electric['phone'] = $data_water['phone'] = $tmpdata['phone'] = htmlspecialchars(trim($vv['C']), ENT_QUOTES);
                            $data_water['use_water'] = $tmpdata['use_water'] = floatval(htmlspecialchars(trim($vv['D']), ENT_QUOTES));
                            $data_electric['use_electric'] = $tmpdata['use_electric'] = floatval(htmlspecialchars(trim($vv['E']), ENT_QUOTES));
                            $data_gas['use_gas'] = $tmpdata['use_gas'] = floatval(htmlspecialchars(trim($vv['F']), ENT_QUOTES));
                            $data_park['use_park'] = $tmpdata['use_park'] = intval(htmlspecialchars(trim($vv['G']), ENT_QUOTES));
                            //$tmpdata['use_park'] = intval(htmlspecialchars(trim($vv['H']), ENT_QUOTES));
                            // $tmpdata['address'] = htmlspecialchars(trim($vv['I']), ENT_QUOTES);
                            $data_park['bind_id'] = $data_gas['bind_id'] = $data_electric['bind_id'] = $data_water['bind_id'] = $tmpdata['bind_id'] = $bindInfo['pigcms_id'];
                            $data_park['uid'] = $data_gas['uid'] = $data_electric['uid'] = $data_water['uid'] = $tmpdata['uid'] = $bindInfo['uid'];
                            $data_park['add_time'] = $data_gas['add_time'] = $data_electric['add_time'] = $data_water['add_time'] = $tmpdata['uid'] = $tmpdata['add_time'] = $_SERVER['REQUEST_TIME'];

                            //print_r($tmpdata);
                            //exit;
                            $floor_where['floor_id'] = $bindInfo['floor_id'];
                            $floor_where['status'] = 1;
                            $database_house_village_floor = D('House_village_floor');
                            $floor_info = $database_house_village_floor->house_village_floor_detail($floor_where);
                            $floor_info = $floor_info['detail'];

                            if ($tmpdata['use_water']) {
                                if(($floor_info['water_fee'] == '0.00') || (!isset($floor_info['water_fee']))){
                                    $water_price = $this->village['water_price'];
                                }else{
                                    $water_price = $floor_info['water_fee'];
                                }
                                $data_water['water_price'] = $tmpdata['water_price'] = $tmpdata['use_water'] * $water_price;
                                D('House_village_user_bind')->where($conditionBind)->setInc('water_price', $tmpdata['water_price']);
                                M('House_village_user_edit_price_record')->add(['pigcms_id'=>$bindInfo['pigcms_id'],'money'=>$tmpdata['water_price'],'detail'=>'增加'.$tmpdata['water_price'].'元','action'=>'导入业主账单-增加','add_time'=>time(),'remark'=>'','type'=>1]);
                            }
                            if ($tmpdata['use_electric']) {
                                if(($floor_info['electric_fee'] == '0.00') || (!isset($floor_info['electric_fee']))){
                                    $electric_price = $this->village['electric_price'];
                                }else{
                                    $electric_price = $floor_info['electric_fee'];
                                }
                                $data_electric['electric_price'] = $tmpdata['electric_price'] = $tmpdata['use_electric'] * $electric_price;
                                D('House_village_user_bind')->where($conditionBind)->setInc('electric_price', $tmpdata['electric_price']);
                                M('House_village_user_edit_price_record')->add(['pigcms_id'=>$bindInfo['pigcms_id'],'money'=>$tmpdata['electric_price'],'detail'=>'增加'.$tmpdata['electric_price'].'元','action'=>'导入业主账单-增加','add_time'=>time(),'remark'=>'','type'=>2]);
                            }
                            if ($tmpdata['use_gas']) {
                                if(($floor_info['gas_fee'] == '0.00') || (!isset($floor_info['gas_fee']))){
                                    $gas_price = $this->village['gas_price'];
                                }else{
                                    $gas_price = $floor_info['gas_fee'];
                                }
                                $data_gas['gas_price'] = $tmpdata['gas_price'] = $tmpdata['use_gas'] * $gas_price;
                                D('House_village_user_bind')->where($conditionBind)->setInc('gas_price', $tmpdata['gas_price']);
                                M('House_village_user_edit_price_record')->add(['pigcms_id'=>$bindInfo['pigcms_id'],'money'=>$tmpdata['gas_price'],'detail'=>'增加'.$tmpdata['gas_price'].'元','action'=>'导入业主账单-增加','add_time'=>time(),'remark'=>'','type'=>3]);
                            }
                            if ($tmpdata['use_property']) {
                                if(($floor_info['property_fee'] == '0.00') || (!isset($floor_info['property_fee']))){
                                    $property_fee = $this->village['property_price'];
                                }else{
                                    $property_fee = $floor_info['property_fee'];
                                }
                                $tmpdata['property_price'] = $property_fee * $bindInfo['housesize'];
                                D('House_village_user_bind')->where($conditionBind)->setInc('property_price', $tmpdata['property_price']);
                            }
                            if ($tmpdata['use_park']) {
                                $data_park['park_price'] = $tmpdata['park_price'] = $this->village['park_price'];
                                D('House_village_user_bind')->where($conditionBind)->setInc('park_price', $this->village['park_price']);
                                M('House_village_user_edit_price_record')->add(['pigcms_id'=>$bindInfo['pigcms_id'],'money'=>$this->village['park_price'],'detail'=>'增加'.$this->village['park_price'].'元','action'=>'导入业主账单-增加','add_time'=>time(),'remark'=>'','type'=>4]);
                            }

                            $last_user_park_id = D('House_village_user_paylist')->data($data_park)->add();
                            $last_user_gas_id = D('House_village_user_paylist')->data($data_gas)->add();
                            $last_user_electric_id = D('House_village_user_paylist')->data($data_electric)->add();
                            $last_user_water = D('House_village_user_paylist')->data($data_water)->add();
//                            $last_user_id = D('House_village_user_paylist')->data($tmpdata)->add();
                            if (!$last_user_park_id || !$last_user_gas_id || !$last_user_electric_id || !$last_user_water) {
                                $err_msg = $vv['B'] . ' 帐单导入失败';
                            }
                        }
                        if (!empty($last_user_park_id) || !empty($last_user_gas_id) || !empty($last_user_electric_id) || !empty($last_user_water)) {
                            // 模板消息
                            $this->send($old_end_user_id['pigcms_id'], $last_user_id);

                        } else {
                            $this->error('导入失败！原因：' . $err_msg);
                            exit;
                        }
                    }
                } else {
                    $this->error($upload->getErrorMsg());
                    exit;
                }
            }
            $this->error('文件上传失败');
            exit;
        } else {
            $this->display();
        }
    }

    // 缴费明细
    public function orders()
    {
        //业主-查看缴费明细 权限
        if (0&&!in_array(120, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        //$uid = $_GET['uid'];
        $bind_id = $_GET['bind_id'] + 0;
        if ($bind_id) {
            //$condtion['uid'] = $uid;
            $condition['bind_id'] = $bind_id;
            $condition['village_id'] = $this->village_id;
            $condition['paid'] = 1;

            import('@.ORG.merchant_page');
            $count_order = D('House_village_pay_order')->where($condition)->count();
            $p = new Page($count_order, 20, 'page');
            $order_list = D('House_village_pay_order')->where($condition)->order('order_id desc')->limit($p->firstRow . ',' . $p->listRows)->select();

            $database_house_village_property_paylist = D('House_village_property_paylist');
            $pay_list = $database_house_village_property_paylist->where(array('village_id'=>$this->village_id))->select();

            // 自定义缴费周期时间
            $custom_paylist = D('House_village_payment_paylist')->where(array('village_id'=>$this->village_id))->select();

            $totalmoney = 0;
            if(!empty($pay_list) || $custom_paylist){
                foreach($order_list as $Key=>$order){
                    if($order['cashier_id']){
                        $cashier_info = D('House_village_pay_cashier_order')->get_one($order['cashier_id']);
                        $cashier_order_list = M('House_village_pay_order')->where(['cashier_id'=>$order['cashier_id']])->select();
                        $order_all_money = 0;
                        foreach ($cashier_order_list as $v){
                            $order_all_money += $v['money'];
                        }
                        $order_list[$Key]['money'] = sprintf("%.2f",($order['money']/$order_all_money)* $cashier_info['money']);
                    }else{
                        $order_list[$Key]['money'] = $order['money'];
                    }
                    if($order['order_type'] == 'park'){
                        if(!$order['month_end_time']){
                            $order_list[$Key]['park_time_str'] = '暂无';
                        }else{
                            $order_list[$Key]['park_time_str'] = date('Y-m-d',time()) . '至' . date('Y-m-d',$order['month_end_time']);
                        }
                    }
                    if($order['order_status'] == 1)
                        $totalmoney += $order['money'];
                    foreach($pay_list as $pay_info){
                        if($order['order_id'] ==  $pay_info['order_id']){
                            $order_list[$Key]['property_time_str'] = date('Y-m-d',$pay_info['start_time']) . '至' . date('Y-m-d',$pay_info['end_time']);
                        }
                    }
                    foreach($custom_paylist as $custom_info){
                        if($order['order_id'] ==  $custom_info['order_id']){
                            $order_list[$Key]['custom_time_str'] = date('Y-m-d',$custom_info['start_time']) . '至' . date('Y-m-d',$custom_info['end_time']);
                        }
                    }
                }
            }

            $result['order_list'] = $order_list;
            $result['pagebar'] = $p->show();
            $this->assign('totalmoney', $totalmoney);
            //打印模板
            $database_house_village_print_template = D('House_village_print_template');
            $print_template = $database_house_village_print_template->where(array('village_id'=>$this->village_id,'is_new' => 0))->select();
            $this->assign('print_template',$print_template);
            $this->assign('order_list', $result);

        }
        $this->display();
    }

    // 打印
    public function print_start(){
        //业主-查看缴费明细 打印 权限
        if (0&&!in_array(120, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $template_id = $_GET['template_id'] + 0;
        $ids = trim($_GET['ids'],',');

        $village_id = $this->village_id;

        $where = array(
            'order_id' => $ids,
            'village_id' => $village_id,
        );
        //获取订单详情
        $print_detail = D('House_village_pay_order')->get_print_detail($where);
        //获取模板详情
        $database_house_village_print_template = D('House_village_print_template');
        $print_template = $database_house_village_print_template->get_one_new($template_id);
        if(!empty($print_template['top_title'])){
            $print_template['title'] = $print_template['top_title'];
        }
        // 宽度
        $col_num = $print_template['col_num'];
        $with = floor(100/$col_num).'%';
        $this->assign('with',$with);
        // print_detail
        $print_detail['desc'] = $print_template['desc'];
        $this->assign('print_detail',$print_detail);
        $this->assign('print_template',$print_template);
        // exit(json_encode($this->fetch()));
        // return $this->fetch();
        $this->display();
    }


    public function village_order()
    {
        $village_id = $this->village_id;
        if ($village_id) {
            $condition['village_id'] = $this->village_id;
            $condition['paid'] = 1;

            $result = D('House_village_pay_order')->get_limit_list_page($condition);

            $this->assign('order_list', $result);
        }

        $this->display();
    }

    public function change()
    {
        $village_id = $this->village_id;
        $strids = isset($_POST['strids']) ? htmlspecialchars($_POST['strids']) : '';
        if ($strids) {
            $array = explode(',', $strids);
            $usernums = $orderids = array();
            foreach ($array as $val) {
                $t = explode('_', $val);
                if ($t[1]) {
                    $orderids[] = $t[1];
                }
            }

            $orderids && D('House_village_pay_order')->where(array('village_id' => $village_id, 'order_id' => array('in', $orderids)))->save(array('is_pay_bill' => 1));
        }
        exit(json_encode(array('error_code' => 0)));
    }

    public function send($old_end_user_id, $last_user_id)
    {
        $users = D('House_village_user_bind')->get_pay_list_open($this->village_id, 20, $last_user_id, $old_end_user_id);

        if ($users) {
            $page = $_GET['page'] ? intval($_GET['page']) : 1;

            if ($page > $users['totalPage']) {
                $this->success('导入成功！');
                exit;
            } else {
                // 模板消息
                $village_info = M('House_village')->where(['village_id'=>$this->village_id])->field('property_id')->find();
                $property_name = M('House_property')->where(['id'=>$village_info['property_id']])->getField('property_name');
                $getIsAihorseCustomized = D('Config_customization')->getIsAihorseCustomized();
                $third_pay_url = '';
                if($getIsAihorseCustomized){
                    $village_info_arr= M('House_village_info')->where(['village_id'=>$this->village_id])->field('third_pay_url,third_ccb_pay_url')->find();
                    $third_pay_url = isset($village_info_arr['third_pay_url']) && $village_info_arr['third_pay_url'] ? $village_info_arr['third_pay_url'] : $third_pay_url;
                    $third_pay_url = isset($village_info_arr['third_ccb_pay_url']) && $village_info_arr['third_ccb_pay_url'] ? $village_info_arr['third_ccb_pay_url'] : $third_pay_url;
                }
                foreach ($users['user_list'] as $userInfo) {
                    $href = C('config.site_url') . '/wap.php?g=Wap&c=House&a=village_my_paylists&village_id=' . $this->village_id.'&pigcms_id='.$userInfo['bind_id'];
                    $href = $third_pay_url ?: $href;
                    $keynote1 = $this->village['village_name'];
                    $keynote2 = $userInfo['address'];
                    $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                    $model->sendTempMsg('TM01008', array('href' => $href, 'wecha_id' => $userInfo['openid'], 'first' => '您好，社区发布了一条账单信息', 'keynote2' => $userInfo['address'], 'keynote1' => $property_name, 'remark' => '请点击查看详细信息！'),0,$village_info['property_id'],1);
                }

                $this->success('发送微信消息完毕，正在跳转下一页', U('User/detail_import', array('page' => $page + 1)));
                exit;
            }
        } else {
            $this->success('导入成功。',U('index'));
            exit;
        }
    }

    // 发送微信通知
    public function send_property(){
        if(IS_AJAX){
            $database_house_village_user_bind = D('House_village_user_bind');
            $database_house_village_pay_order = D('House_village_pay_order');
            $database_user =D('User');
            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
            $getIsAihorseCustomized = D('Config_customization')->getIsAihorseCustomized();
            $third_pay_url = '';
            if($getIsAihorseCustomized){
                $village_info_arr= M('House_village_info')->where(['village_id'=>$this->village_id])->field('third_pay_url,third_ccb_pay_url')->find();
                $third_pay_url = isset($village_info_arr['third_pay_url']) && $village_info_arr['third_pay_url'] ? $village_info_arr['third_pay_url'] : $third_pay_url;
                $third_pay_url = isset($village_info_arr['third_ccb_pay_url']) && $village_info_arr['third_ccb_pay_url'] ? $village_info_arr['third_ccb_pay_url'] : $third_pay_url;
            }
            if($_POST['is_collective']){
                //业主-群发微信消息 权限
                if (0&&!in_array(100, $this->house_session['menus'])) {
                    exit(json_encode(array('msg'=>'对不起，您没有权限执行此操作','status'=>0)));
                }

                $bind_condition['village_id'] = $this->village_id;
                $bind_condition['parent_id'] = 0;
                $user_bind_list = $database_house_village_user_bind->where($bind_condition)->select();

                $href = C('config.site_url') . '/wap.php?g=Wap&c=House&a=village_my_pay&village_id='.$this->village_id;
                $href = $third_pay_url ?: $href;
                foreach($user_bind_list as $Key => $User){
                    $now_user = $database_user->get_user($User['uid']);
                    //添加一个统计物业总时间
//                  $wy_num = 0;
//                  $wy_num_buy = $database_house_village_pay_order->where(array('bind_id'=>$house_village_user_bind_info['pigcms_id'],'uid'=>$house_village_user_bind_info['uid'],'order_type'=>'property','paid'=>1))->Sum('property_month_num');
//                  $wy_num_sbuy = $database_house_village_pay_order->where(array('bind_id'=>$house_village_user_bind_info['pigcms_id'],'uid'=>$house_village_user_bind_info['uid'],'order_type'=>'property','paid'=>1))->Sum('presented_property_month_num');
//                  $wy_num = $wy_num_buy + $wy_num_sbuy + 0;

                    if(!empty($now_user['openid'])){
                        if($User['add_time'] > 0){
                            $model->sendTempMsg('TM01008', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' => ' 尊敬的业主，您有新的账单！', 'keynote2' => $User['address'], 'keynote1' => $this->village['property_name'], 'remark' => '请点击查看详细信息！'),0,$this->village['property_id'],1);
                        }else{
                            $model->sendTempMsg('TM01008', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' => ' 尊敬的业主，您有新的账单！', 'keynote2' => $User['address'], 'keynote1' => $this->village['property_name'], 'remark' => '请点击查看详细信息！'),0,$this->village['property_id'],1);
                        }
                    }
                }
            }else{
                //业主-发送微信通知 权限
                if (0&&!in_array(96, $this->house_session['menus'])) {
                    exit(json_encode(array('msg'=>'对不起，您没有权限执行此操作','status'=>0)));
                }

                $pigcms_id = $_POST['pigcms_id'] + 0;
                $usernum = $_POST['usernum'];

                if(empty($pigcms_id) || empty($usernum)){
                    exit(json_encode(array('msg'=>'传递参数有误！','status'=>0)));
                }

                $bind_condition['pigcms_id'] = $pigcms_id;
                // $bind_condition['usernum'] = $usernum;

                $house_village_user_bind_info = $database_house_village_user_bind->where($bind_condition)->find();
                if(!$house_village_user_bind_info){
                    exit(json_encode(array('msg'=>'该用户不存在！','status'=>0)));
                }

                $now_user = $database_user->get_user($house_village_user_bind_info['uid']);

                //添加一个统计物业总时间
                $wy_num_buy = $database_house_village_pay_order->where(array('bind_id'=>$house_village_user_bind_info['pigcms_id'],'uid'=>$house_village_user_bind_info['uid'],'order_type'=>'property','paid'=>1))->Sum('property_month_num');
                $wy_num_sbuy = $database_house_village_pay_order->where(array('bind_id'=>$house_village_user_bind_info['pigcms_id'],'uid'=>$house_village_user_bind_info['uid'],'order_type'=>'property','paid'=>1))->Sum('presented_property_month_num');
                $wy_num = $wy_num_buy + $wy_num_sbuy + 0;

                if(!empty($now_user['openid'])){
                    $href = C('config.site_url') . '/wap.php?g=Wap&c=House&a=village_my_pay&village_id='.$this->village_id;
                    $href = $third_pay_url ?: $href;
                    $model->sendTempMsg('TM01008', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' =>   '尊敬的业主，您有新的账单！', 'keynote1' =>$this->village['property_name'], 'keynote2' =>$house_village_user_bind_info['address'], 'remark' => '请点击查看详细信息！'),0,$this->village['property_id'],1);
                }
            }
            exit(json_encode(array('msg'=>'发送微信消息完毕！','status'=>1)));
        }else{
            $this->error('访问页面有误！~~~');
        }
    }

    // 发送微信通知 新 未缴账单
    public function send_weixin_notice(){
        // 全部发送 群发 单个
        // 业主 家属
        if(IS_AJAX){
            $database_house_village_user_bind = D('House_village_user_bind');
            $database_user =D('User');
            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));

            $bind_condition['village_id'] = $this->village_id;
            if ($_POST['is_all']) {
                //未缴-全部发微信消息 权限
                if (0&&!in_array(264, $this->house_session['menus'])) {
                    exit(json_encode(array('msg'=>'对不起，您没有权限执行此操作','status'=>0)));
                }

                $bind_condition['parent_id'] = 0;
                $user_bind_list = $database_house_village_user_bind->get_cashier_unpaid_list($bind_condition,0,0);
            } elseif($_POST['ids']) {
                //未缴-群发微信消息 权限
                if (0&&!in_array(265, $this->house_session['menus'])) {
                    exit(json_encode(array('msg'=>'对不起，您没有权限执行此操作','status'=>0)));
                }
                $bind_condition['pigcms_id'] = trim($_POST['ids'],',');
                $user_bind_list = $database_house_village_user_bind->get_cashier_unpaid_list($bind_condition,0,0);

            }else{
                //未缴-发送微信通知 权限
                if (0&&!in_array(266, $this->house_session['menus'])) {
                    exit(json_encode(array('msg'=>'对不起，您没有权限执行此操作','status'=>0)));
                }

                $pigcms_id = $_POST['pigcms_id'] + 0;

                if(empty($pigcms_id)){
                    exit(json_encode(array('msg'=>'传递参数有误！','status'=>0)));
                }

                $bind_condition['pigcms_id'] = $pigcms_id;
                $user_bind_list = $database_house_village_user_bind->get_cashier_unpaid_list($bind_condition,0,0);
                if(!$user_bind_list['list']){
                    exit(json_encode(array('msg'=>'该用户不存在！','status'=>0)));
                }
            }

            $href = C('config.site_url') . '/wap.php?g=Wap&c=House&a=village_my_pay&village_id='.$this->village_id;

            if(!$user_bind_list['list']){
                exit(json_encode(array('msg'=>'发送失败！','status'=>0)));
            }

            //判断发送业主 或 家属
            $send_user_type = $_POST['send_user_type'] ? $_POST['send_user_type'] : 1;
            // 发送
            $database_house_village_user_bind->send_weixin_pay($this->village_id,$user_bind_list['list'],$send_user_type);

            exit(json_encode(array('msg'=>'发送微信消息完毕！','status'=>1)));
        }else{
            $this->error('访问页面有误！~~~');
        }
    }

    // 删除业主
    public function user_delete(){
        set_time_limit(0);
        //业主-删除 权限
        if (0&&!in_array(94, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $pigcms_id = $_GET['pigcms_id'] + 0;

        $result=D('House_village_user_bind')->user_bind_del($pigcms_id);
        if(!$result['error']){
            $this->error($result['msg']);
        }else{
            $this->success($result['msg']);
        }
        exit();

        $usernum = $_GET['usernum'];
        $database_house_village_user_bind = D('House_village_user_bind');
        $bind_condition['pigcms_id'] = $pigcms_id;
        //$bind_condition['usernum'] = $usernum;
        $bind_condition['type'] = ['in', [0,3]];
        $now_bind_info = $database_house_village_user_bind->where($bind_condition)->find();
        if(!$now_bind_info){
            $this->error('该信息不存在！');
        }

        fdump('>>>>>>'.__LINE__.';function:'.__FUNCTION__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
        fdump($now_bind_info,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
//        $insert_id = $database_house_village_user_bind->where($bind_condition)->delete();
        /**
         * todo 软删除 修改类型 usernum拼上时间戳
         */
        $insert_id = $database_house_village_user_bind->where($bind_condition)->data(['usernum'=>$now_bind_info['usernum'].'-'.createRandomStr(11,true),'status'=>4])->save();

        $m_house_face_device = M('House_face_device');
        $condition_face_device['village_id'] = $now_bind_info['village_id'];
//        $condition_face_device['device_type'] = 1;
        $condition_face_device['is_del'] = 0;
        $aDoor_count = $m_house_face_device->field(true)->where($condition_face_device)->count();
        fdump_api(['删除时查询设备'.__LINE__,$aDoor_count],'house_user_bind/user_bind_delete_log',1);

        $third_robot_recognition_open = C('config.third_robot_recognition_open');
        if (intval($aDoor_count) > 0 || $third_robot_recognition_open) {
            // 删除  同步进行删除
            try {
                D('House_village_face_door')->del_a3_user_bind($pigcms_id, $now_bind_info['village_id'],$now_bind_info);
            }catch (Exception $e) {
                fdump_api(['删除时同步设备出错'.__LINE__,$pigcms_id,$now_bind_info,$e->getMessage()],'house_user_bind/del_a3_user_bind_log_err',1);
            }
            $where = array('village_id' => $now_bind_info['village_id'],'status'=>1, 'vacancy_id' => $now_bind_info['vacancy_id'], 'type' => array('in', '0, 3'));
            // 查询一下是否存在其他状态的业主信息，全部删除
            $other = $database_house_village_user_bind->where($where)->select();
            if (!empty($other)) {
                foreach ($other as $val) {
                    fdump('>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                    fdump($_SERVER,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                    fdump('houseapi--'.__FUNCTION__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                    fdump($val,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
//                    $database_house_village_user_bind->where(array('pigcms_id' => $val['pigcms_id']))->delete();
                    /**
                     * todo 软删除 修改类型 usernum拼上时间戳
                     */
                    $database_house_village_user_bind->where(array('pigcms_id' => $val['pigcms_id']))->data(['usernum'=>$val['usernum'].'-'.createRandomStr(11,true),'status'=>4])->save();

                    fdump($database_house_village_user_bind->_sql(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                    fdump($database_house_village_user_bind->getDbError(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                    try {
                        D('House_village_face_door')->del_a3_user_bind($val['pigcms_id'], $val['village_id'], $val);
                    }catch (Exception $e) {
                        fdump_api(['删除时同步设备出错'.__LINE__,$val,$e->getMessage()],'house_user_bind/user_bind_delete_log_err',1);
                    }
                }
            }
        }
        if($insert_id){
            $family_condition['village_id'] = $now_bind_info['village_id'];
            $family_condition['parent_id'] = $now_bind_info['pigcms_id'];
            $family_condition['status'] = ['neq', 4];
            $user_del =  $database_house_village_user_bind->where($family_condition)->select();
            if (!empty($user_del)) {
                fdump_api(['删除家属'.__LINE__,$user_del],'house_user_bind/user_bind_delete_log',1);
                   /**
                    * todo 软删除 修改类型 usernum拼上时间戳
                    */
                $database_house_village_user_bind->where($family_condition)->data(['usernum'=>$now_bind_info['usernum'].'-'.createRandomStr(11,true),'status'=>4])->save();
                foreach ($user_del as $val1) {
                    try {
                        D('House_village_face_door')->del_a3_user_bind($val1['pigcms_id'], $val1['village_id'], $val1);
                    }catch (Exception $e) {
                        fdump_api(['删除时同步设备出错'.__LINE__,$val1,$e->getMessage()],'house_user_bind/user_bind_delete_log_err',1);
                    }
                }
            }

            // 同步通过 解绑申请
            $database_house_village_user_unbind = D('House_village_user_unbind');
            $info_where = [];
            $info_where['bind_id'] = $pigcms_id;
            $info_where['status'] = ['neq', 3];
            $info_where['type'] = ['in', [0,3]];
            $info_where['village_id'] = $this->village_id;
            $set_data = [];
            $set_data['status'] = 3;
            $set_data['edittime'] = time();
            $set_data['reason'] = '对应业主删除自动同步对应解绑';
            $database_house_village_user_unbind->where($info_where)->save($set_data);

            //清空房间
            $database_house_village_user_vacancy = D('House_village_user_vacancy');
            $data['uid'] = 0;
            $data['status'] = 1;
            $data['name'] = "";
            $data['phone'] = "";
            $data['type'] = 0;
            $data['park_flag'] = 0;
            $database_house_village_user_vacancy->where(array('pigcms_id'=>$now_bind_info['vacancy_id'],'village_id'=>$now_bind_info['village_id']))->data($data)->save();

            fdump_api(['删除家属'.__LINE__,$now_bind_info],'house_user_bind/shequ_user_delete_log',1);
//            D('Face_door_service')->sync_delete_face_per($pigcms_id, $now_bind_info);
            $this->success('删除成功！');
        }else{
            $this->error('删除失败！');
        }
    }

    // 业主 数据统计
    public function user_data(){
        //业主-数据统计 权限
        if (0&&!in_array(99, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $database_house_village_user_bind = D('House_village_user_bind');
        $where['village_id'] = $this->village_id;
        $where['status'] = 1;
        $where['type'] = ['in', [0,3]];
        //业主分析表start
        $user_list = $database_house_village_user_bind->where($where)->group('uid')->field('*,count(*) as house_sum')->select();

        $uidArr = array();
        foreach($user_list as $user){
            $uidArr[] = $user['uid'];
        }

        $database_user = D('User');
        $user_condition['uid'] = array('in',$uidArr);
        $user_condition['open_id'] != '';
        $wx_user_list = $database_user->where($user_condition)->select();

        $wx_sum = 0;
        foreach($user_list as $Key => $user){
            foreach($wx_user_list as $wx_key => $wx_user){
                if(($user['uid'] == $wx_user['uid']) && (!empty($wx_user['openid']) || !empty($wx_user['wxapp_openid']))){
                    $wx_sum += $user['house_sum'];
                }
            }
        }

        $count_user = 0;
        foreach($user_list as $user){
            $count_user += $user['house_sum'];
        }
        // 房屋总数
        $room_count =  D('House_village_user_vacancy')->where(array('is_del'=>'0','village_id'=>$this->village_id))->count();
        $this->assign('room_count' , $room_count);
        $this->assign('count_user' , $count_user);
        $this->assign('wx_user_count' , $wx_sum);
        //业主分析表end

        //停车位start
        $part_count = $database_house_village_user_bind->where(array('park_flag'=>1,'village_id'=>$this->village_id))->count();
        $this->assign('part_count' , $part_count);
        //停车位end


        $this->display();
    }


    // 导出业主
    public function user_export(){
        //业主-导出 权限
        if (0&&!in_array(98, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        set_time_limit(0);
        
        ini_set('memory_limit','-1');
        $params = [
            'village_id'  => $this->village_id,
            'village_name' => $this->village['village_name'],
            'property_id'  => $this->house_session['property_id'],
            'data' => $_GET
        ];
        $curlTimeOutArr = [
            'connectionout' => 600 ,
            'timeout'=>600
        ];
        $result = invoke_v20_service('\app\community\model\service\HouseVillageUserBindService/exportUserData',$params,$curlTimeOutArr);
        exit(json_encode($result['data']['retval']));
        
        $find_type = $_GET['find_type'];
        $find_value = $_GET['find_value'];
        $is_platform = $_GET['is_platform'] + 0;
        if ($find_value) {
            if ($find_type == 1) {
//                $where['usernum'] = array('like', '%' . $find_value . '%');
                $where['_string'] = "ub.usernum LIKE '%{$find_value}%' OR ub.bind_number LIKE '%{$find_value}%'";
            } else if ($find_type == 2) {
                $where['name'] = array('like', '%' . str_replace(' ','',$find_value) . '%');
            } else if ($find_type == 3) {
                $where['phone'] = array('like', '%' . $find_value . '%');
            } else if ($find_type == 4) {
                $where['address'] = array('like', '%' . $find_value . '%');
            }
        }

        if($is_platform == 1){
            $where['uid'] = array('neq',0);
        }elseif ($is_platform == 2) {
            $where['uid'] = array('eq',0);
        }

        $property_endtime_start = $_GET['property_endtime_start'];
        $property_endtime_end = $_GET['property_endtime_end'];

        if($property_endtime_start && $property_endtime_end){
            $start_time = strtotime($property_endtime_start);
            $end_time = strtotime($property_endtime_end.'23:59:59');
            $where['property_endtime'] = array('between',array($start_time,$end_time));
        }else if($property_endtime_start){
            $property_endtime_start = strtotime($property_endtime_start);
            $where['property_endtime'] = array('egt',$property_endtime_start);
        }else if($property_endtime_end){
            $property_endtime_end = strtotime($property_endtime_end.'23:59:59');
            $where['property_endtime'] = array('lt',$property_endtime_end);
        }
        if (isset($_GET['is_real']) && $_GET['is_real']>0) {
            if (1==$_GET['is_real']) {
                if ($where['_string']) {
                    $where['_string'] .= " AND (ub.uid not in (0,'') OR ub.phone not in (0,'') OR ub.name not in (0,''))";
                } else {
                    $where['_string'] = "(ub.uid not in (0,'') OR ub.phone not in (0,'') OR ub.name not in (0,''))";
                }
            } else {
                if ($where['_string']) {
                    $where['_string'] .= " AND (ub.uid in (0,'') AND ub.phone in (0,'') AND ub.name in (0,''))";
                } else {
                    $where['_string'] = "(ub.uid in (0,'') AND ub.phone in (0,'') AND ub.name in (0,''))";
                }
            }
        }

        $db_house_village_user_bind = D('House_village_user_bind');
        $village_id = $this->village_id;

        if(isset($_GET['single_id']) && !empty($_GET['single_id'])){
            $where['single_id'] = $_GET['single_id'];
        }
        if(isset($_GET['floor_id']) && !empty($_GET['floor_id'])){
            $where['floor_id'] = $_GET['floor_id'];
        }
        if(isset($_GET['vacancy_id']) && !empty($_GET['vacancy_id'])){
            $where['vacancy_id'] = $_GET['vacancy_id'];
        }
        if(isset($_GET['layer_id']) && !empty($_GET['layer_id'])){
            $where['layer_id'] = $_GET['layer_id'];
        }
        /**是否绑定微信*is_bind_weixin 1是 2否****/
        $is_bind_weixin=isset($_GET['is_bind_weixin']) ? intval($_GET['is_bind_weixin']):0;
        if($is_bind_weixin>0){
            $where['is_bind_weixin'] = $is_bind_weixin;
        }
        /**是否绑定微信*source_comefrom 1公众号 2小程序3小区自动注册4其他****/
        $source_comefrom=isset($_GET['source_comefrom']) ? intval($_GET['source_comefrom']):0;
        if($source_comefrom>0){
            $where['source_comefrom'] = $source_comefrom;
        }

//        $where['status'] = ['in', [0,1]];
        $where['status'] = 1;
        ini_set('memory_limit', '1024M');
        $is_new_charge=D('House_village')->checkNewCharge($this->house_session['property_id']);
        $user_info_list = $db_house_village_user_bind->get_limit_list_page_new($village_id, 99999999, $where,'',false);
        $user_list = $user_info_list['user_list'];
        foreach ($user_list as &$v) {
            if ($is_new_charge == 1) {
                $userTimes =  D('House_village_user_bind')->getUserEndTime($v['pigcms_id'],false);
                if (isset($userTimes['propertyEndTime'])) {
                    $v['property_endtime'] = intval($userTimes['propertyEndTime']);
                } else {
                    $v['property_endtime'] = 0;
                }
                if (isset($userTimes['propertyStartTime'])) {
                    $v['property_starttime'] = intval($userTimes['propertyStartTime']);
                } else {
                    $v['property_endtime'] = 0;
                }

            }else{
                if (!$v['property_endtime']) {
                    $now_pay_info = D('House_village_property_paylist')->where(array('bind_id' => $v['pigcms_id']))->order('add_time desc')->field('end_time')->find();
                    $v['property_endtime'] = $now_pay_info['end_time'];
                }

                if (!$v['property_starttime']) {
                    $now_pay_info = D('House_village_property_paylist')->where(array('bind_id' => $v['pigcms_id']))->order('add_time asc')->field('start_time')->find();
                    $v['property_starttime'] = $now_pay_info['start_time'];
                }
            }
            // 查看有无车位
            $v['bind_position'] = D('House_village_bind_position')->where(array('user_id' => $v['pigcms_id'], 'village_id' => $this->village_id))->count();
            if ($v['authentication_field']) {
                $v['authentication_field_arr'] = unserialize($v['authentication_field']);
            } else {
                $v['authentication_field_arr'] = [];
            }
	}
        if (count($user_list) <= 0) {
            $this->error('无数据导出！');
        }

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';

        $title = $this->village['village_name'] . '社区-业主列表';

        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $length = ceil(count($user_list)/1000);

        $pCoordinate_arr = [
            'A','B','C','D','E','F','G',
            'H','I','J','K','L','M','N',
            'O','P','Q','R','S','T',
            'U','V','W','X','Y','Z',
            'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM',
            'AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ',
            'BA','BB','BC','BD','BE','BF','BG','BH','BI','BJ','BK','BL','BM',
            'BN','BO','BP','BQ','BR','BS','BT','BU','BV','BW','BX','BY','BZ',
            'CA','CB','CC','CD','CE','CF','CG','CH','CI','CJ','CK','CL','CM',
            'CN','CO','CP','CQ','CR','CS','CT','CU','CV','CW','CX','CY','CZ',
            'DA','DB','DC','DD','DE','DF','DG','DH','DI','DJ','DK','DL','DM',
            'DN','DO','DP','DQ','DR','DS','DT','DU','DV','DW','DX','DY','DZ'
        ];

        if ($is_new_charge) {
            $pValue_arr = [
                '业主编号',
                '姓名',
                '手机号',
                '身份证号',
                'IC卡号',
                '房屋类型',
                '住址',
                '物业服务开始时间',
                '物业服务结束时间',
            ];
            $pValue_arr[] = '停车位';
            $pValue_arr[] = '房子大小';
            $pValue_arr[] = '车辆信息';
            $pValue_arr[] = '车位信息';
            $pValue_arr[] = '是否绑定微信';
            $pValue_arr[] = '注册来源';
        } else {
            $pValue_arr = [
	            '业主编号',
	            '姓名',
	            '手机号',
	            '身份证号',
	            'IC卡号',
	            '房屋类型',
	            '住址',
	            '物业服务开始时间',
	            '物业服务结束时间',
	            '水费',
	            '电费',
	            '燃气费',
	            '停车费'
            ];
            $pValue_arr[] = '停车位';
            $pValue_arr[] = '房子大小';
            $pValue_arr[] = '车辆信息';
            $pValue_arr[] = '车位信息';
            $pValue_arr[] = '是否绑定微信';
            $pValue_arr[] = '注册来源';
            if (isset($user_info_list['cunstom_arr']) && $user_info_list['cunstom_arr']) {
                foreach ($user_info_list['cunstom_arr'] as $vs) {
                    $pValue_arr[] = $vs['payment_name'];
                }
            } else {
                $pValue_arr[] = '自定义项费';
            }
        }

        $where_village['village_id'] = $this->village_id;
        $property_id = D('House_village')->where($where_village)->getField('property_id');
        $where_data_config = array(
            'village_id'=>0,
            'property_id'=>$property_id,
            'is_display'=>1,
            'is_close'=>0
        );
        $dataList = D('House_village_data_config')->where($where_data_config)->order('sort desc')->field('title,key')->select();
        if (!empty($dataList)) {
            foreach ($dataList as $dataKey) {
                $pValue_arr[] = $dataKey['title'];
            }
        }

        $key_length = count($pValue_arr) - 1;

        $cycle_type = array('Y'=>'year', 'M'=>'month', 'D'=>'day');
        $area_arr = M('Area')->where([])->getField('area_id,area_id,area_name',true);
       // for ($i = 0; $i < $length; $i++) {
           // $i && $objExcel->createSheet();
            //$objExcel->setActiveSheetIndex($i);
            $objExcel->setActiveSheetIndex(0);
            $objExcel->getActiveSheet()->setTitle('共' . count($user_list) . '个用户');
            $objActSheet = $objExcel->getActiveSheet();

            foreach ($pValue_arr as $k1=>$v1) {
                $objActSheet->setCellValue($pCoordinate_arr[$k1].'1', $v1);
            }

            if (!empty($user_list)) {
                $index = 2;

                $cell_list = range($pCoordinate_arr[0],$pCoordinate_arr[$key_length]);
                foreach ($cell_list as $cell) {
                    $objActSheet->getColumnDimension($cell)->setWidth(25);
                }

                foreach ($user_list as $value) {
                    $pCoordinateKey = 0;
                    $bind_number = $value['bind_number']?$value['bind_number']:$value['usernum'];
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, $bind_number);
                    $pCoordinateKey = $pCoordinateKey+1;
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, $value['name']);
                    $pCoordinateKey = $pCoordinateKey+1;
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, $value['phone']);
                    $pCoordinateKey = $pCoordinateKey+1;
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, $value['id_card']?$value['id_card']:'无');
                    $pCoordinateKey = $pCoordinateKey+1;
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, $value['ic_card']?$value['ic_card']:'无');

                    if($value['floor_type_name']){
                        $pCoordinateKey = $pCoordinateKey+1;
                        $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, $value['floor_type_name']);
                    }else{
                        $pCoordinateKey = $pCoordinateKey+1;
                        $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, '暂无');
                    }

                    $pCoordinateKey = $pCoordinateKey+1;
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, $value['address']);


                    if ($value['property_starttime'] && intval($value['property_starttime'])>0) {
                        $property_starttime = date('Y-m-d',$value['property_starttime']);
                    } else {
                        $property_starttime = '无';
                    }
                    if ($value['property_endtime'] && intval($value['property_endtime'])>0) {
                        $property_endtime = date('Y-m-d',$value['property_endtime']);
                    } else {
                        $property_endtime = '无';
                    }
                    $pCoordinateKey = $pCoordinateKey+1;
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, $property_starttime);
                    $pCoordinateKey = $pCoordinateKey+1;
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, $property_endtime);


                    if ($is_new_charge) {
	                    // 自定义缴费
                        $indexs = $pCoordinateKey;
                    } else {
                        // 自定义缴费
                        $water_price = floatval($value['water_price']);
                        $electric_price = floatval($value['electric_price']);
                        $gas_price = floatval($value['gas_price']);
                        $park_price = floatval($value['park_price']);

                        $pCoordinateKey = $pCoordinateKey+1;
                        $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, $water_price);
                        $pCoordinateKey = $pCoordinateKey+1;
                        $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, $electric_price);
                        $pCoordinateKey = $pCoordinateKey+1;
                        $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, $gas_price);
                        $pCoordinateKey = $pCoordinateKey+1;
                        $objActSheet->setCellValueExplicit($pCoordinate_arr[$pCoordinateKey] . $index, $park_price);

                        $cunstom_pay_arr = $value['cunstom_pay_arr'];
                        if (isset($user_info_list['cunstom_arr']) && $user_info_list['cunstom_arr']) {
                            $ks0 = 1;
                            foreach ($user_info_list['cunstom_arr'] as $ks1 => $vs1) {
                                $indexs = $ks0+$pCoordinateKey;
                                $objActSheet->getStyle($pCoordinate_arr[$indexs] . $index)->getAlignment()->setWrapText(true);
                                $total1 = $cunstom_pay_arr[$ks1] ? $cunstom_pay_arr[$ks1]['total_money'] : '-';
                                $objActSheet->setCellValueExplicit($pCoordinate_arr[$indexs] . $index, $total1);
                                $ks0++;
                            }
                        } else {
                            $indexs = $pCoordinateKey+1;
                            $objActSheet->getStyle($pCoordinate_arr[$indexs] . $index)->getAlignment()->setWrapText(true);
                            $objActSheet->setCellValueExplicit($pCoordinate_arr[$indexs] . $index, $value['cunstom_money']);
                        }
                    }

                    $indexs = $indexs + 1;
                    if($value['bind_position']){
                        $objActSheet->setCellValueExplicit($pCoordinate_arr[$indexs] . $index, '有');
                    }else{
                        $objActSheet->setCellValueExplicit($pCoordinate_arr[$indexs] . $index, '无');
                    }
                    $indexs = $indexs + 1;
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[$indexs] . $index, $value['housesize']);

                    // 车辆信息
                    $car_list = D('House_village_bind_car')->get_user_cars(array('pigcms_id'=>$value['pigcms_id']));
                    if ($car_list['car_str']) {
                        $car_str = $car_list['car_str'];
                    } else {
                        $car_str = '';
                    }
                    $indexs = $indexs + 1;
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[$indexs] . $index, $car_str);
                    // 车位信息
                    $position_list = D('House_village_bind_position')->get_user_position_bind_list(array('pigcms_id'=>$value['pigcms_id'],'village_id'=>$this->village_id));
                    if ($position_list['position_str']) {
                        $position_str = $position_list['position_str'];
                    } else {
                        $position_str = '';
                    }
                    $indexs = $indexs + 1;
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[$indexs] . $index, $position_str);
                    $indexs = $indexs + 1;
                    $is_bind_weixin='';
                    if(isset($value['is_bind_weixin'])){
                        $is_bind_weixin=$value['is_bind_weixin'];
                    }
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[$indexs] . $index, $is_bind_weixin);
                    $source_comefrom='';
                    if(isset($value['source_comefrom'])){
                        $source_comefrom=$value['source_comefrom'];
                    }
                    $indexs = $indexs + 1;
                    $objActSheet->setCellValueExplicit($pCoordinate_arr[$indexs] . $index, $source_comefrom);
                    if (!empty($dataList)) {
                        $ks1 = 1;
                        foreach ($dataList as $dataKey1) {
                            $index1 = $ks1+$indexs;
                            if (!empty($value['authentication_field_arr'])) {
                                if (isset($value['authentication_field_arr'][$dataKey1['key']]) && $value['authentication_field_arr'][$dataKey1['key']]['value']) {
                                    $keyVal = $value['authentication_field_arr'][$dataKey1['key']]['value'];
                                    if ('sex'==$dataKey1['key'] && !$keyVal) {
                                        $keyVal = '-';
                                    } elseif ($dataKey1['key'] == 'native_place'){
                                        $native_place = explode('#',$keyVal);
                                        $province = $native_place[0];
                                        $city = $native_place[1];
                                        if (!$province && !$city) {
                                            $keyVal = '-';
                                        } else {
                                            $keyVal = '';
                                            if ($province && $area_arr && isset($area_arr[$province])) {
                                                $keyVal .= $area_arr[$province]['area_name'] . ' ';
                                            }
                                            if ($city && $area_arr && isset($area_arr[$city])) {
                                                $keyVal .= $area_arr[$city]['area_name'] . ' ';
                                            }
                                            if (!$keyVal) {
                                                $keyVal = '未知';
                                            }
                                        }
                                    }
                                } else {
                                    $keyVal = '-';
                                }
                            } else {
                                $keyVal = '-';
                            }
                            $objActSheet->setCellValueExplicit($pCoordinate_arr[$index1] . $index, $keyVal);
                            $ks1++;
                        }
                        $indexs = $index1;
                    }

                    $index++;
                }
            }
            sleep(2);
            //输出
            $objWriter = new PHPExcel_Writer_Excel5($objExcel);
            ob_end_clean();
            header("Pragma: public");
            header("Expires: 0");
            header("Content-type:text/html;charset=utf-8");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename='.urlencode($title).'_' . date("Y-m-d h:i:sa", time()) . '.xls');
            header("Content-Transfer-Encoding:binary");
            $objWriter->save('php://output');
            exit();
       // }
    }
    
    public function exportUserDataForLouYu()
    {
        if (0&&!in_array(107, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        set_time_limit(0);
        ini_set('memory_limit','-1');;
        $params = [
            'village_id'  => $this->village_id,
            'village_name' => $this->village['village_name']
        ];
        $curlTimeOutArr = [
            'connectionout' => 600 ,
            'timeout'=>600
        ];
        $result = invoke_v20_service('\app\community\model\service\HouseVillageUserBindService/exportUserDataForLouYu',$params,$curlTimeOutArr);
        exit(json_encode($result['data']['retval']));
    }    
    
    public function exportUserJsDataForLouYu()
    {
        if (0&&!in_array(107, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        /**
         * type
         * 业主 = 0,3
         * 家人 = 1
         * 租客 = 2
         */
        $type = (int)$_GET['type'];
        $params = [
            'village_id'  => $this->village_id,
            'village_name' => $this->village['village_name'],
            'type' => $type
        ];
        $curlTimeOutArr = [
            'connectionout' => 600 ,
            'timeout'=>600
        ];
        $result = invoke_v20_service('\app\community\model\service\HouseVillageUserBindService/exportUserJsDataForLouYu',$params,$curlTimeOutArr);
        exit(json_encode($result['data']['retval']));
    }
    
    #申请解绑审核 - wangdong
    public function audit_unbind(){
        //申请解绑列表-查看 权限
        if (0&&!in_array(107, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $database_house_village_user_unbind = D('House_village_user_unbind');

        $database_house_village_floor = D('House_village_floor');

        $database_house_village_user_vacancy = D('House_village_user_vacancy');

        $where['village_id'] = $this->village_id;
        $find_type = $_GET['find_type'];
        $find_value = $_GET['find_value'];
        $find_status = $_GET['status'];
        if ($find_value) {
            if ($find_type == 2) {
                $where['name'] = array('like', '%' . str_replace(' ','',$find_value) . '%');
            } else if ($find_type == 3) {
                $where['phone'] = array('like', '%' . $find_value . '%');
            }
        }
        if($find_status){
            $where['status'] = $find_status;
        }

        $begin_time = $_GET['begin_time'];
        $end_time = $_GET['end_time'];
        if ($begin_time && !$end_time) {
            $where['addtime'] = array('gt' , strtotime($begin_time));
        }
        if (!$begin_time && $end_time) {
            $where['addtime'] = array('lt' , strtotime(date('Y-m-d 23:59:59',strtotime($end_time))));
        }
        if ($begin_time && $end_time) {
            $where['addtime'] = array('between' , array(strtotime($begin_time),strtotime(date('Y-m-d 23:59:59',strtotime($end_time)))));
        }
        $d_house_village=D('House_village');
        $village_info = $d_house_village->field('*')->where(array('village_id'=>$this->village_id))->find();

        $lists = $database_house_village_user_unbind->get_limit_list_page($this->village_id, 20, $where);
        if($lists && $lists['list']){
            foreach($lists['list'] as $k=>&$vv){
                if(isset($vv['phone']) && !empty($vv['phone'])){
                    $vv['phone']=$d_house_village->phone_desensitization_v($vv['phone'],$this->village_id,$village_info);
                }
                if(isset($vv['id_card']) && !empty($vv['id_card'])){
                    $vv['id_card']= $d_house_village->idnum_desensitization_v($vv['id_card'],$this->village_id,$village_info);
                }
            }
        }
        // $lists = $database_house_village_user_unbind->where($condition)->order('itemid DESC')->select();


        // foreach($lists as $k=>&$v){

        // 	$floor_info = $database_house_village_floor->where(array('floor_id'=>$v['floor_id']))->find();

        // 	$room_info = $database_house_village_user_vacancy->where(array('pigcms_id'=>$v['room_id']))->find();

        // 	$v['address'] = $floor_info['floor_layer']." - ".$floor_info['floor_name']." - ".$room_info['layer']."#".$room_info['room'];

        // }

        $this->assign("lists" , $lists);

        $this->display();

    }

    #修改/审核用户申请解绑信息 - wangdong

    public function audit_unbind_edit(){

        $database_house_village_user_unbind = D('House_village_user_unbind');

        $database_house_village_floor = D('House_village_floor');

        $database_house_village_user_bind = D('House_village_user_bind');

        $database_house_village_user_vacancy = D('House_village_user_vacancy');

        if(IS_POST){
            //申请解绑列表-编辑 权限
            if (0&&!in_array(108, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }

            $itemid = $_POST['itemid'] + 0;

            $status = $_POST['status'] + 0;

            if(!$itemid)  $this->error('传递参数错误！~~~');

            $where['itemid'] = $itemid;

            $data['status']   = $status;
            $data['edittime'] = time();
            $data['reason'] = trim($_POST['reason']) ? htmlspecialchars(trim($_POST['reason'])) : '无';
            $save_id = $database_house_village_user_unbind -> where($where) -> data($data)->save();

            $info_where['itemid']     = $itemid;
            $info_where['village_id'] = $this->village_id;
            $info = $database_house_village_user_unbind->where($info_where)->find();
            // 平台用户
            $user_info = D('User')->get_user($info['phone'], 'phone');
            #审核不通过/审核中 不删除绑定信息
            if($status==1 || $status==2){
                if ($status==2) {
                    //发送模板消息
                    if ($user_info['openid']) {
                        $param = [
                            'pageName' => 'myVillage',
                            'isAll' => true,
                            'urlParam' => '',
                        ];
                        $myVillageUrl = D('House_village')->villagePagePath('','',$param);
                        $href = $myVillageUrl;
                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        if ($info['type'] == 0 || $info['type'] == 3) {


                            //todo app 极光推送
                            $ar=[
                                'title'=>'业主解绑申请',
                                'msg'=> '您在【'.$this->village['village_name'].'】的业主解绑申请审核未通过。原因：'.$data['reason'],
                                'url'=>$href
                            ];
                            $info['pigcms_id']=$info['itemid'];
                            D('User')->AppPush($info,$ar);
                            unset($info['pigcms_id']);

                            $model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $user_info['openid'],'first' => '您在【'.$this->village['village_name'].'】的业主解绑申请审核未通过。原因：'.$data['reason'].'\n', 'keyword1' => '业主解绑申请', 'keyword2' => '已发送', 'keyword3' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']), 'keyword4' => $user_info['nickname'], 'remark' => '\n请点击查看详细信息！'),0,$this->village['property_id'],1);
                        }else{
                            //todo app 极光推送
                            $ar=[
                                'title'=>''.(C('config.open_house_user_name')?'亲友':'家属').'解绑申请',
                                'msg'=> '您在【'.$this->village['village_name'].'】的'.(C('config.open_house_user_name')?'亲友':'家属').'解绑申请审核未通过。原因：'.$data['reason'],
                                'url'=>$href
                            ];
                            $info['pigcms_id']=$info['itemid'];
                            D('User')->AppPush($info,$ar);
                            unset($info['pigcms_id']);

                            $model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $user_info['openid'],'first' => '您在【'.$this->village['village_name'].'】的'.(C('config.open_house_user_name')?'亲友':'家属').'解绑申请审核未通过。原因：'.$data['reason'].'\n', 'keyword1' => ''.(C('config.open_house_user_name')?'亲友':'家属').'解绑申请', 'keyword2' => '已发送', 'keyword3' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']), 'keyword4' => $user_info['nickname'], 'remark' => '\n请点击查看详细信息！'),0,$this->village['property_id'],1);
                        }

                    }
                }

                if($save_id) $this->success('操作成功！',U('User/audit_unbind')); else $this->error('操作失败！');

                # 审核通过 删除绑定（要判断是否是管理员 如果是 那么删除房间绑定信息和管理员绑定信息 以及 该房间绑定的亲属/租客）
            }elseif($status==3){
                //发送模板消息

                if($save_id){

                    #如果是管理员/替换管理员 先清空房间绑定信息 vacancy 在删除绑定信息/亲属/租客 bind
                    if($info['type']==0 || $info['type']==3){
                        //发送模板消息
                        if ($user_info['openid']) {
//                            $href = C('config.site_url').'/wap.php?g=Wap&c=House&a=my_village_list';

                            $param = [
                                'pageName' => 'myVillage',
                                'isAll' => true,
                                'urlParam' => '',
                            ];
                            $myVillageUrl = D('House_village')->villagePagePath('','',$param);
                            $href = $myVillageUrl;
                            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));

                            //todo app 极光推送
                            $ar=[
                                'title'=>'业主解绑申请',
                                'msg'=> '您在【'.$this->village['village_name'].'】的业主解绑申请审核已通过',
                                'url'=>$href
                            ];
                            $info['pigcms_id']=$info['itemid'];
                            D('User')->AppPush($info,$ar);
                            unset($info['pigcms_id']);

                            $model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $user_info['openid'],'first' => '您在【'.$this->village['village_name'].'】的业主解绑申请审核已通过\n', 'keyword1' => '业主解绑申请', 'keyword2' => '已发送', 'keyword3' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']), 'keyword4' => $user_info['nickname'], 'remark' => '\n请点击查看详细信息！'),0,$this->village['property_id'],1);
                        }
                        //2020/10/9 start 解绑业主，该用户没有其他房屋则扣除业主房间下所有未使用积分
                        //小区积分开关 （1开启 0关闭）
                        $house_score_switch = D('Config')->where(array('name'=>'house_score_switch'))->getField('value');
                        //小区积分开关开启则执行
                        if($house_score_switch == 1) {
                            $map['uid'] = $info['uid'];
                            $map['status'] = ['eq', 1];
                            //$map['parent_id'] = ['eq', 0];//查询业主
                            $map['type'] = ['in', [0,3]];//查询业主
                            $user_bind_count = D('House_village_user_bind')->where($map)->count();
                            $user_bind = D('House_village_user_bind')->where($map)->find();
                            //该用户没有其他业主身份则清除名下小区所得所有未使用积分
                            if ($user_bind_count == 1) {
//                                $user_score['score_count'] = $user_info['score_count'] - $user_info['house_integral'];
                                $user_score['score_count'] = 0;
                                $user_score['house_integral'] = 0;
                                D('User')->where(['uid' => $info['uid']])->data($user_score)->save();
                                $arr = [
                                    'uid'=>$info['uid'],
                                    'type'=>2,
                                    'score'=>$user_info['score_count'],
                                    'time'=>time(),
                                    'ip'=>get_client_ip(0),
                                    'desc'=>'申请解绑房屋成功',
                                    'clean_time'=>time(),
                                    'clean_status'=>1,
                                ];
                                fdump('>>>>1>>>>','api/User_score_list',true);
                                fdump($arr,'api/User_score_list',true);
                                $res = D('User_score_list')->data($arr)->add();
                                fdump('>>>>2>>>>','api/User_score_list',true);
                                fdump($res,'api/User_score_list',true);
                                fdump('>>>>3>>>>','api/User_score_list',true);
                            }
                        }
                        //2020/10/9 end
                        $room['status'] = 1;
                        $room['uid'] = $room['type'] = $room['park_flag']= 0;
                        $room['name'] = $room['phone'] = $room['memo'] = "";

                        $room_where['pigcms_id'] = $info['room_id'];
                        $room_where['village_id'] = $info['village_id'];
                        $reset_id = $database_house_village_user_vacancy->where($room_where)->data($room)->save();

                        #房间清除完成 删除绑定信息
                        if(1||$reset_id){

                            #先删除房主信息

                            $bind_info['uid']        = $info['uid'];
                            $bind_info['phone']      = $info['phone'];
                            $bind_info['vacancy_id'] = $info['room_id'];
                            $bind_info['village_id'] = $info['village_id'];
                            $bind_info['type']       = $info['type'];
                            $bind_info['pigcms_id']  = $info['bind_id'];

                            //增加记录
                            $user_bind = $database_house_village_user_bind->where($bind_info)->find();

                            $data['village_id'] = $user_bind['village_id'];
                            $data['vacancy_id'] = $user_bind['vacancy_id'];
                            $data['floor_id'] = $user_bind['floor_id'];
                            $data['uid'] = $user_bind['uid'];
                            $data['name'] = $user_bind['name'];
                            $data['phone'] = $user_bind['phone'];
                            $data['type'] = $user_bind['type'];
                            $data['check_in_time'] = $user_bind['add_time'];
                            $data['user_bind_id'] = $user_bind['bind_id'];
                            $data['add_time'] = time();
                            M('House_village_user_record')->data($data)->add();

                            fdump(__FUNCTION__.'>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                            fdump($_POST,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                            fdump($info,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                            fdump($user_bind,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
//                            $del_0_id = $database_house_village_user_bind->where($bind_info)->delete();

                            /**
                             * todo 软删除 修改类型 usernum拼上时间戳
                             */
                            $del_0_id= $database_house_village_user_bind->where($bind_info)->data(['usernum'=>$user_bind['usernum'].'-'.createRandomStr(11,true),'status'=>4])->save();

                            fdump($database_house_village_user_bind->getDbError(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);

                            #再删除亲属/租客信息
                            if($del_0_id){
                                // 同步删除钥匙
                                D('House_village_face_door')->del_a3_user_bind($bind_info['pigcms_id'], $bind_info['village_id'], $user_bind);
                                $bind_info_1['vacancy_id'] = $info['room_id'];
                                $bind_info_1['type']      = array('in' , array(1,2,6));
                                $del_info = $database_house_village_user_bind->where($bind_info_1)->select();
                                fdump('>>同步删除家属>>>>'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                                fdump($del_info,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
//                                $del_1_id = $database_house_village_user_bind->where($bind_info_1)->delete();

                                /**
                                 * todo 软删除 修改类型 usernum拼上时间戳
                                 */
                                $database_house_village_user_bind->where($bind_info_1)->data(['usernum'=>time().'-'.createRandomStr(11,true),'status'=>4])->save();
                                fdump($database_house_village_user_bind->getDbError(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                                if ($del_info) {
                                    foreach ($del_info as $valss) {
                                        // 同步删除钥匙
                                        D('House_village_face_door')->del_a3_user_bind($valss['pigcms_id'], $valss['village_id'], $valss);
                                    }
                                }
                            }

                            $this->success('操作成功！',U('User/audit_unbind'));
                        }else{

                            $this->error('操作失败！');
                        }

                        #如果是亲属/租客	删除绑定信息 bind
                    }elseif($info['type']==1 || $info['type']==2){
                        //发送模板消息
                        if ($user_info['openid']) {
//                            $href = C('config.site_url').'/wap.php?g=Wap&c=House&a=my_village_list';
                            $param = [
                                'pageName' => 'myVillage',
                                'isAll' => true,
                                'urlParam' => '',
                            ];
                            $myVillageUrl = D('House_village')->villagePagePath('','',$param);
                            $href = $myVillageUrl;
                            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));


                            //todo app 极光推送
                            $ar=[
                                'title'=>''.(C('config.open_house_user_name')?'亲友':'家属').'解绑申请',
                                'msg'=> '您在【'.$this->village['village_name'].'】的'.(C('config.open_house_user_name')?'亲友':'家属').'解绑申请审核已通过',
                                'url'=>$href
                            ];
                            $info['pigcms_id']=$info['itemid'];
                            D('User')->AppPush($info,$ar);
                            unset($info['pigcms_id']);


                            $model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $user_info['openid'],'first' => '您在【'.$this->village['village_name'].'】的'.(C('config.open_house_user_name')?'亲友':'家属').'解绑申请审核已通过\n', 'keyword1' => ''.(C('config.open_house_user_name')?'亲友':'家属').'解绑申请', 'keyword2' => '已发送', 'keyword3' => date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']), 'keyword4' => $user_info['nickname'], 'remark' => '\n请点击查看详细信息！'),0,$this->village['property_id'],1);
                        }

                        $bind_info['uid']        = $info['uid'];
                        $bind_info['phone']      = $info['phone'];
                        $bind_info['vacancy_id'] = $info['room_id'];
                        $bind_info['village_id'] = $info['village_id'];
                        $bind_info['type']       = $info['type'];
                        $bind_info['pigcms_id']  = $info['bind_id'];

                        //增加记录
                        $user_bind = $database_house_village_user_bind->where($bind_info)->find();
                        $data['village_id'] = $user_bind['village_id'];
                        $data['vacancy_id'] = $user_bind['vacancy_id'];
                        $data['floor_id'] = $user_bind['floor_id'];
                        $data['uid'] = $user_bind['uid'];
                        $data['name'] = $user_bind['name'];
                        $data['phone'] = $user_bind['phone'];
                        $data['type'] = $user_bind['type'];
                        $data['check_in_time'] = $user_bind['add_time'];
                        $data['user_bind_id'] = $user_bind['bind_id'];
                        $data['add_time'] = time();
                        M('House_village_user_record')->data($data)->add();

                        fdump('>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                        fdump($bind_info,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                        fdump($_SERVER,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                        fdump('houseapi--'.__FUNCTION__,'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
//                        $del_0_id = $database_house_village_user_bind->where($bind_info)->delete();

                        /**
                         * todo 软删除 修改类型 usernum拼上时间戳
                         */
                        $del_0_id= $database_house_village_user_bind->where($bind_info)->data(['usernum'=>$user_bind['usernum'].'-'.createRandomStr(11,true),'status'=>4])->save();

                        fdump($database_house_village_user_bind->getDbError(),'api/log/'.date('Ymd').'/house_user_bind/user_bind_delete_log',true);
                        if($del_0_id) {
                            D('House_village_face_door')->del_a3_user_bind($bind_info['pigcms_id'], $bind_info['village_id'], $user_bind);
                            $this->success('操作成功！',U('User/audit_unbind'));
                        } else {
                            $this->error('操作失败！');
                        }
                    }

                }else{
                    $this->error('操作失败！');
                }

            }

        }
        else{
            //申请解绑列表-查看 权限
            if (0&&!in_array(107, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }

            $itemid = $_GET['itemid']+0;

            if(!$itemid)  $this->error('传递参数错误！~~~');

            $condition['itemid'] = $itemid;

            $edit = $database_house_village_user_unbind->where($condition)->find();

            $floor_info = $database_house_village_floor->where(array('floor_id'=>$edit['floor_id']))->find();

            $room_info = $database_house_village_user_vacancy->where(array('pigcms_id'=>$edit['room_id']))->find();

//            $edit['address'] = $floor_info['floor_layer']." - ".$floor_info['floor_name']." - ".$room_info['layer']."#".$room_info['room'];

            $single = M('House_village_single')->where(array('id'=>$floor_info['single_id']))->find();
            $word_data = array();
            $sing_name = $single['single_name'] ? $single['single_name'] : $floor_info['floor_layer'];
            $word_data['floor_name'] = $floor_info['floor_name'];
            $word_data['layer'] = $room_info['layer'];
            $word_data['room'] = $room_info['room'];
            $word_data['single_name'] = $sing_name;
            $room_diy_name = D('House_village')->word_replce_msg($word_data,$this->village_id);
            $edit['address'] = $room_diy_name;

            $this->assign('edit' , $edit);

            $this->display();
        }

    }

    #删除 解绑信息 - wangdong
    public function audit_unbind_del(){
        //申请解绑列表-删除 权限
        if (0&&!in_array(109, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $itemid = $_GET['itemid'] + 0;

        $village_id = $this->village_id;

        if(!$itemid || !$village_id)  $this->error('传递参数错误！~~~');

        $condition['itemid']     = $itemid;
        $condition['village_id'] = $village_id;

        $database_house_village_user_unbind = D('House_village_user_unbind');
        $delete_id = $database_house_village_user_unbind->where($condition)->delete();

        if($delete_id) $this->success('删除成功！',U('User/audit_unbind')); else $this->error('删除失败！');


    }

    #检测是否存在用户
    public function ajax_empty_user_info(){

        if(IS_AJAX){

            $phone = trim($_POST['phone']);
            if(!$phone) $this->error('参数传递错误！~~~~');

            $batabase_user = D('User');
            $info = $batabase_user->where(array('phone'=>$phone,'status'=>1))->count();
            if($info>0){
                exit(json_encode(array('status'=>0,'msg'=>'')));
            }else{
                exit(json_encode(array('status'=>1,'msg'=>'此手机号暂时不是平台用户')));
            }
        }

    }

    #批量删除业主
    public function ajaxDelete(){
        if(IS_AJAX){
            //业主-删除 权限
            if (0&&!in_array(269, $this->house_session['menus'])) {
                exit(json_encode(array('status'=>0,'msg'=>"对不起，您没有权限执行此操作")));
            }

            $database_house_village_user_bind = D('House_village_user_bind');
            $database_house_village_user_vacancy = D('House_village_user_vacancy');

            $village_id = $_POST['village_id']+0;
            $arr_pigcms_id = $_POST['arr_pigcms_id'];
            if(($this->village_id != $village_id) || empty($arr_pigcms_id)){
                exit(json_encode(array('status'=>1,'msg'=>'参数错误')));
            }
            $arr_pigcms_ids = explode(",",$arr_pigcms_id);
            fdump_api(['批量删除'.__LINE__,$arr_pigcms_ids,$_SERVER],'house_user_bind/user_bind_delete_log',1);
            for($i=0;$i<count($arr_pigcms_ids);$i++){
                //先删除亲属/租客 然后解绑房间  最后删除绑定数据
                $bind_info = $database_house_village_user_bind->where(array('pigcms_id'=>$arr_pigcms_ids[$i],'village_id'=>$village_id))->find();
                fdump_api(['单个对象'.__LINE__,$bind_info],'house_user_bind/user_bind_delete_log',1);
                if($bind_info){
                    //删除亲属/ 租客
                    $where = array();
                    $where['parent_id'] = $bind_info['pigcms_id'];
                    $where['type']      = array('in','1,2,5,6');
                    $where['village_id']=$village_id;
                    $msg = $database_house_village_user_bind->where($where)->select();

//                    $database_house_village_user_bind->where($where)->delete();
                    /**
                     * todo 软删除 修改类型 usernum拼上时间戳
                     */
                    $database_house_village_user_bind->where($where)->data(['usernum'=>$bind_info['usernum'].'-'.createRandomStr(11,true),'status'=>4])->save();
                    $database_house_village_user_bind->delLiLinPfHousehold($bind_info['pigcms_id'],$bind_info['vacancy_id'],$this->village_id);
                    fdump_api(['单个对象下属'.__LINE__,$database_house_village_user_bind->_sql(),$database_house_village_user_bind->getDbError()],'house_user_bind/user_bind_delete_log',1);
                    if ($msg) {
                        foreach ($msg as $v1) {
                            D('House_village_face_door')->del_a3_user_bind($v1['pigcms_id'], $v1['village_id'], $v1);
                            $database_house_village_user_bind->delLiLinPfHousehold($v1['pigcms_id'],$v1['vacancy_id'],$this->village_id);
                        }
                    }

                    //清空房间
                    $data = array();
                    $data['status'] = 1;
                    $data['uid'] = 0;
                    $data['name'] = "";
                    $data['phone'] = "";
                    $data['type'] = 0;
                    $data['mome'] = "";
                    $data['is_del'] = 0;
                    $data['del_time'] = 0;
                    $data['park_flag'] = 0;
                    $database_house_village_user_vacancy->where(array('pigcms_id'=>$bind_info['vacancy_id']))->data($data)->save();
                    //做到清空房间了
//                    $user_dels = $database_house_village_user_bind->where(array('village_id'=>$village_id,'pigcms_id'=>$arr_pigcms_ids[$i]))->select();

                    fdump_api(['更改房屋'.__LINE__,$bind_info['vacancy_id'],$database_house_village_user_vacancy->_sql(),$database_house_village_user_vacancy->getDbError()],'house_user_bind/user_bind_delete_log',1);

//                    $database_house_village_user_bind->where(array('village_id'=>$village_id,'pigcms_id'=>$arr_pigcms_ids[$i]))->delete();

                    /**
                     * todo 软删除 修改类型 usernum拼上时间戳
                     */
                    $database_house_village_user_bind->where(array('village_id'=>$village_id,'pigcms_id'=>$bind_info['pigcms_id']))->data(['usernum'=>$bind_info['usernum'].'-'.createRandomStr(11,true),'status'=>4])->save();

                    fdump_api(['软删除'.__LINE__,$village_id,$bind_info['pigcms_id'],$database_house_village_user_bind->_sql(),$database_house_village_user_bind->getDbError()],'house_user_bind/user_bind_delete_log',1);
                    // 同步删除钥匙
                    D('House_village_face_door')->del_a3_user_bind($bind_info['pigcms_id'], $bind_info['village_id'], $bind_info);
                }
            }
            exit(json_encode(array('status'=>1,'msg'=>"业主删除完成")));


        }
    }

    public function house_village_owner_info()
    {

        $village_id = $this->village_id;
        //$data['_string'] = "( village_id=".$village_id.") OR (village_id = 0)";
        $dataList = M('House_village_data_config')->where(array('village_id'=>$village_id))->order('sort desc')->select();
        $this->assign('dataList',$dataList);
        $this->display();
    }

    public function house_village_owner_info_add()
    {
        $village_id = $this->village_id;
        if(IS_POST){
            if(empty($_POST['title'])){
                $this->error('名称不能为空!');
            }

            $_POST['key'] = uniqid() . mt_rand(10000,99999);
            if($_POST['type'] == 3){
                if(empty($_POST['title'])){
                    $this->error('单选内容不能为空!');
                }
            }
            $data['title'] = $_POST['title'];
            $data['sort'] = $_POST['sort'];
            $data['type'] = $_POST['type'];
            $data['key'] = $_POST['key'];
            $data['is_display'] = $_POST['is_display'];
            $data['is_must'] = $_POST['is_must'];
            $data['add_time'] = time();
            $data['village_id'] = $village_id;

            //是否是系统自带

            if($_POST['use_field']){
                $data['use_field'] = implode(',',explode(PHP_EOL, $_POST['use_field']));
            }
            $inset_id = M('House_village_data_config')->data($data)->add();
            if($inset_id){
                $this->success('添加成功！',U('house_village_owner_info'));
            }else{
                $this->error('添加失败');
            }
        }else{
            $this->display();
        }

    }

    public function house_village_owner_info_edit()
    {
        $village_id = $this->village_id;
        if(IS_POST){
            $id = $_POST['id'];
            if(empty($_POST['title'])){
                $this->error('名称不能为空!');
            }
            if($_POST['type'] == 2){
                if(empty($_POST['title'])){
                    $this->error('单选内容不能为空!');
                }
            }
            if($_POST['is_system'] == 0){
                $data['type'] = $_POST['type'];
                $data['title'] = $_POST['title'];
                if($_POST['type'] == 2){
                    if($_POST['use_field']){
                        $data['use_field'] = implode(',',explode(PHP_EOL, $_POST['use_field']));
                    }
                }else{
                    $data['use_field'] = '';
                }
            }
            $data['sort'] = $_POST['sort'];
            $data['is_display'] = $_POST['is_display'];
            $data['is_must'] = $_POST['is_must'];
            $data['is_close'] = $_POST['is_close'];



            $inset_id = M('House_village_data_config')->where(array('acid'=>$id))->data($data)->save();
            if($inset_id){
                $this->success('修改成功！',U('house_village_owner_info'));
            }else{
                $this->error('修改失败,请检查是否进行了修改!');
            }
        }else{
            $id = $_GET['id'];
            $cate = D('House_village_data_config')->where(array('acid'=>$id))->find();
            if($cate['use_field']){
                $cate['use_field'] = implode(PHP_EOL,explode(',',$cate['use_field']));
            }

            $this->assign('cate',$cate);
            $this->display();
        }
    }

    public function house_village_owner_info_del()
    {
        $id =  M('House_village_data_config')->where(array('acid'=>$_GET['id']))->delete();
        if($id){
            $this->success('删除成功！');
        }else{
            $this->error('删除失败！');
        }

    }

    public function user_download(){


        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $user_list[] = array('12345235','小黑','13212345679','11120.1','1120.1','1120.1','1','1120.1','三单元	','30栋','12','1102','家中有狗','2017-07-24','2018-07-24','100','职工');

        $test = array('R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP');
        $title = '导入业主模板';

        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        $length = 1;
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle('导入业主模板');
            $objActSheet = $objExcel->getActiveSheet();
            $objActSheet->setCellValue('A1', '业主编号');
            $objActSheet->setCellValue('B1', '业主名');
            $objActSheet->setCellValue('C1', '手机号');
            $objActSheet->setCellValue('D1', '水费总欠费');
            $objActSheet->setCellValue('E1', '电费总欠费');
            $objActSheet->setCellValue('F1', '燃气费总欠费');
            $objActSheet->setCellValue('G1', '是否有停车位（1有0无）');
            $objActSheet->setCellValue('H1', '停车费总欠费');
            $objActSheet->setCellValue('I1', '单元名称');
            $objActSheet->setCellValue('J1', '楼号');
            $objActSheet->setCellValue('K1', '层号');
            $objActSheet->setCellValue('L1', '门牌号');
            $objActSheet->setCellValue('M1', '备注');
            $objActSheet->setCellValue('N1', '物业开始时间');
            $objActSheet->setCellValue('O1', '物业结束时间');
            $objActSheet->setCellValue('P1', '房子平方(计算物业费使用)');
            $objActSheet->setCellValue('Q1', '属性');
            $ordA = ord('A'); //65
            $key2 = ord("@"); //64
            $where_village['village_id'] = $this->village_id;
            $property_id = D('House_village')->where($where_village)->getField('property_id');
            $where_data_config = array(
                'village_id'=>0,
                'property_id'=>$property_id,
                'is_display'=>1,
            );
            $village_data_config = D('House_village_data_config')->where($where_data_config)->order('sort desc')->select();
//            $village_data_config = D('House_village_data_config')->where(array('village_id'=>$this->village_id,'is_display'=>1))->select();
            if($village_data_config >0){
                foreach($village_data_config as $kk=>$vv){
                    if($vv['title']){
//                        if($ordA > ord("Z"))
//                        {
//                            $colum = chr(ord("A")).chr(++$key2);//超过26个字母 AA1,AB1,AC1,AD1...BA1,BB1...
//                            $objActSheet->setCellValue($colum.'1', $vv['title']);
//                        }
//                        else
//                        {
//                            $colum = chr($ordA++);
//                            $objActSheet->setCellValue($colum.'1', $vv['title']);
//                        }

                        $objActSheet->setCellValue($test[$kk].'1', $vv['title']);

                    }
                }
            }
            if (!empty($user_list)) {
                $index = 2;
                $cell_list = range('A','Q');
                foreach ($cell_list as $cell) {
                    $objActSheet->getColumnDimension($cell)->setWidth(30);
                }

                foreach ($user_list as $value) {

                    $objActSheet->setCellValueExplicit('A' . $index, $value[0]);
                    $objActSheet->setCellValueExplicit('B' . $index, $value[1]);
                    $objActSheet->setCellValueExplicit('C' . $index, $value[2]);
                    $objActSheet->setCellValueExplicit('D' . $index, $value[3]);
                    $objActSheet->setCellValueExplicit('E' . $index, $value[4]);
                    $objActSheet->setCellValueExplicit('F' . $index, $value[5]);
                    $objActSheet->setCellValueExplicit('G' . $index, $value[6]);
                    $objActSheet->setCellValueExplicit('H' . $index, $value[7]);
                    $objActSheet->setCellValueExplicit('I' . $index, $value[8]);
                    $objActSheet->setCellValueExplicit('J' . $index, $value[9]);
                    $objActSheet->setCellValueExplicit('K' . $index, $value[10]);
                    $objActSheet->setCellValueExplicit('L' . $index, $value[11]);
                    $objActSheet->setCellValueExplicit('M' . $index, $value[12]);
                    $objActSheet->setCellValueExplicit('N' . $index, $value[13]);
                    $objActSheet->setCellValueExplicit('O' . $index, $value[14]);
                    $objActSheet->setCellValueExplicit('P' . $index, number_format($value[15],2));
                    $objActSheet->setCellValueExplicit('Q' . $index, $value[16]);
                    $index++;
                }
            }
            sleep(2);
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
            header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
            header("Content-Transfer-Encoding:binary");
            $objWriter->save('php://output');
            exit();
        }
    }


    // 获取用户同步人脸设备图片信息
    public function device_img_info() {
        $pigcms_id = I('pigcms_id','', 'intval');
        if ($pigcms_id) {
            $msg = D('Face_door_service')->get_bind_user_device_img($pigcms_id);
        } else {
            $msg = [];
        }
        exit(json_encode(array('status'=>1,'msg'=>$msg)));
    }
    
    // 同步
    public function syn_device_img($tmp_pigcms_id=0) {
        $pigcms_id = I('pigcms_id','', 'intval');
        if($tmp_pigcms_id>0){
            $pigcms_id=$tmp_pigcms_id;
            $_GET['pigcms_id']=$tmp_pigcms_id;
        }
        if(empty($pigcms_id)){
            exit(json_encode(array('errorCode'=>1001,'errorMsg'=>'参数错误！')));
        }
        $d_house_village_user_bind = M('House_village_user_bind');
        $where = array(
            'pigcms_id' => $pigcms_id,
            'village_id'=>$this->village_id
        );
        $village_info = $d_house_village_user_bind->where($where)->find();
        if (empty($village_info['uid'])) {
			if(!empty($village_info['phone'])){
				$village_info['uid'] = M('User')->where(['phone' => $village_info['phone']])->getField('uid');
				
				if(empty($village_info['uid'])){
					$data_user = array(
						'nickname' 	=> $village_info['name'],
						'phone' 	=> $village_info['phone'],
						'source' 	=> 'houseautoreg',
					);
					$reg_result = D('User')->autoreg($data_user);
					if($reg_result['error_code']){
						exit(json_encode(array('errorCode'=>1, 'msg'=>$reg_result['error_code'])));
					}
					$village_info['uid'] = $reg_result['uid'];
				}
				
				if(!empty($village_info['uid'])){
					$d_house_village_user_bind->where($where)->data(['uid' => $village_info['uid']])->save();
				}else{
					exit(json_encode(array('errorCode'=>1001,'errorMsg'=>'请先绑定为平台用户！')));
				}
			}else{
				exit(json_encode(array('errorCode'=>1001,'errorMsg'=>'请先绑定为平台用户！')));
			}
        }
        if (isset($village_info['face_img_status']) && $village_info['face_img_status'] != 2) {
            $data_bind = [
                'face_img_status' => '0',
                'face_img_reason' => '',
                'add_face_time' => time(),
            ];
            $d_house_village_user_bind->where($where)->data($data_bind)->save();
        }
        
        $info = D('House_face_img')->house_user_face_device_info($village_info['uid'],$pigcms_id);
        
        $hik_info_vision_i_community_app_key = C('config.hik_info_vision_i_community_app_key');
        $hik_info_vision_i_community_app_secret = C('config.hik_info_vision_i_community_app_secret');
        if ($hik_info_vision_i_community_app_key && $hik_info_vision_i_community_app_secret) {
            $hikRet = invoke_v20_service('\app\community\model\service\Device\HikInfoVisionICommunityService/sycBindUserToHikCommunity', ['village_id' => $village_info['village_id'], 'pigcms_id' => $village_info['pigcms_id']]);
        }
        if($info && (!isset($info['err_msg']) || !$info['err_msg'])){
            // 存在物业时间和家属同步下家属信息
            if (in_array($village_info['type'],[0,3])) {
                // 存在物业时间和家属同步下家属信息
                $childrenList = $d_house_village_user_bind->where(array('parent_id'=>$pigcms_id,'status'=>1))->field('pigcms_id,uid')->select();
                if (!empty($childrenList)) {
                    foreach ($childrenList as $items) {
                        D('House_face_img')->house_user_face_device_info($items['uid'],$items['pigcms_id']);
                    }
                }
            }

            //todo 轨迹信息 用户同步家属或租客的人脸照片至设备中
            if($village_info['type'] != 0){
                $parent_user = M('house_village_user_bind')->where(['pigcms_id'=>$village_info['parent_id']])->field('uid,pigcms_id')->find();
            }else{
                $parent_user = $village_info;
            }
            $str = '业主将'.(($village_info['type'] == 1)?'家人':'租客').$village_info['name'].'的人脸照片同步至门禁设备中';
            D('House_user_trajectory_log')->addLog(4,$village_info['village_id'],0,$parent_user['uid'],$parent_user['pigcms_id'],1,$str,'后台更新了人员数据');

            exit(json_encode(array('errorCode'=>0,'errorMsg'=>'操作成功！')));
        } elseif (isset($info['err_msg']) && $info['err_msg']) {
            exit(json_encode(array('errorCode'=>1001,'errorMsg'=>$info['err_msg'])));
        }else{
            exit(json_encode(array('errorCode'=>1001,'errorMsg'=>'没有可同步设备！')));
        }
    }
    public function add_face_img()
    {
        if(IS_POST){
            if(!isset($_POST['pigcms_id'])){
                exit(json_encode(array('error'=>1,'msg'=>'缺少必传参数！')));
            }
            $bind_pigcms_id=trim($_POST['pigcms_id']);
            $bind_pigcms_id=intval($bind_pigcms_id);
            $user = M('house_village_user_bind')->where(['pigcms_id'=>$bind_pigcms_id])->field('pigcms_id,uid,type,phone,name,village_id,parent_id')->find();
            $user['phone'] = trim($user['phone']);
            if(empty($user['uid'])){
                if(empty($user['phone']) && $user['name']){
                    $data_user = array(
                        'nickname' 	=> $user['name'],
                        'source' 	=> 'houseautoreg',
                    );
                    $reg_result = D('User')->autoreg($data_user);
                    if($reg_result['error_code']){
                        exit(json_encode(array('error'=>1, 'msg'=>$reg_result['error_code'])));
                    }
                    $user['uid'] = $reg_result['uid'];
                }elseif ($user['phone']){
                    $where['phone'] = $user['phone'];
                    $where['status'] = array('in','0,1');
                    $res = D('User')->where($where)->field('uid')->find();
                    if(empty($res['uid'])){
                        $data_user = array(
							'nickname' 	=> $user['name'],
							'phone' 	=> $user['phone'],
							'source' 	=> 'houseautoreg',
						);
						$reg_result = D('User')->autoreg($data_user);
						if($reg_result['error_code']){
							exit(json_encode(array('error'=>1, 'msg'=>$reg_result['error_code'])));
						}
                        $user['uid'] = $reg_result['uid'];
                    }else{
                        $user['uid'] = $res['uid'];
                    }
                }else if(empty($user['name'])){
					exit(json_encode(array('error'=>1,'msg'=>'请给该人员录入姓名！')));
				}
                if ($user['uid']) {
                    D('house_village_user_bind')->where(['pigcms_id'=>$_POST['pigcms_id']])->save(['uid'=>$user['uid']]);
                }
            }
            if($user['type'] != 0){
                $parent_user = M('house_village_user_bind')->where(['pigcms_id'=>$user['parent_id']])->field('uid,pigcms_id')->find();
            }else{
                $parent_user = $user;
            }
            //base64上传
            if(isset($_POST['imageBase64']) || isset($_POST['base'])){
	            $base64Data = I('imageBase64');
                $imgbase64=isset($_POST['base']) ? $_POST['base']:'';
                if(empty($base64Data) && !empty($imgbase64)){
                    $base64Data=$imgbase64;
                }
	            if (empty($base64Data)) {
		            exit(json_encode(array('error'=>1,'msg'=>'未收到数据，上传失败，请重试！')));
	            }

	            $img_mer_id = sprintf("%09d", 0);
	            $rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
	            $up_dir = "./upload/house/".$rand_num.'/';
	            if (!is_dir($up_dir)) {
		            mkdir($up_dir, 0777, true);
	            }
                try {
                    $new_file = $up_dir.date('YmdHis_').uniqid().'.jpg';//统一默认转 jpg
                    empty($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] = dirname($_SERVER['SCRIPT_FILENAME']);
                    //$savePath = rtrim(WEB_PATH,'/'). ltrim($new_file,'.');
                    //$savePath = $_SERVER['DOCUMENT_ROOT']. ltrim($new_file,'.');
                    
                    $url = $this->base64_to_img(str_replace('data:image/png;base64,', '', $base64Data),0);
                    $new_file=$url;
                    $savePath = $_SERVER['DOCUMENT_ROOT']. ltrim($url,'.');
                    
                    $arr = getimagesize($base64Data);
                    fdump_api(['base64Data'=>$base64Data,'savePath'=>$savePath,'new_file'=>$new_file,'arr'=>$arr,'WEB_PATH'=>WEB_PATH],'00imageBase64',1);
                    
                    if (isset($arr[0]) && $arr[0] && isset($arr[1]) && $arr[1]) {
                        $models = '\app\common\model\service\image\ImageService/encodePngToJpg';
                        $param = [
                            'params'     => [
                                //'imgPath'      => $base64Data,
                                'imgPath'      => $savePath,
                                'savePath'      => $savePath,
                                'format'       => 'jpg',  //统一默认转 jpg
                                'heiht'        => $arr[1] ,
                                'with'         => $arr[0]
                            ]
                        ];
                        invoke_v20_service($models,$param);
                    }
                }catch (Exception $e) {
                    fdump_api([$e->getMessage(), '图片压缩失败', $base64Data], 'houseApi/encodeImgToDataUrl');
                }

	            //判断是否需要上传至云存储
	            $file_handle = new file_handle();
	            $file_handle->upload($new_file);
	            $img_path = trim($new_file,'.');
	            $imageUrl = replace_file_domain($img_path);


	            if($file_handle->check_open_oss()){
		            @unlink($savePath);
	            }

	            $id = $this->add_face($user['uid'],$imageUrl,$bind_pigcms_id);
 
                //todo 轨迹信息 用户上传家属或租客的人脸照片
                $str = '业主上传了'.(($user['type'] == 1)?'家人':'租客').$user['name'].'的人脸照片';
                D('House_user_trajectory_log')->addLog(4,$user['village_id'],0,$parent_user['uid'],$parent_user['pigcms_id'],1,$str,'后台更新了人员数据');

                exit(json_encode(array('error'=>2,'msg'=>'上传成功','data'=>['url'=>$imageUrl,'id'=>$id])));
            }
            //上传图片
            $image = D('Image')->handle($_POST['pigcms_id'], 'houseface', 0, array('size' => 10), false);
            if (!$image['error']) {
                try {
                    empty($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] = dirname($_SERVER['SCRIPT_FILENAME']);
                    $savePath = $_SERVER['DOCUMENT_ROOT']. $image['url']['file'];
                    $arr = getimagesize('.'.$image['url']['file']);
                    if (isset($arr[0]) && $arr[0] && isset($arr[1]) && $arr[1]) {
                        $models = '\app\common\model\service\image\ImageService/encodePngToJpg';
                        $param = [
                            'params'     => [
                                'imgPath'      => $savePath,
                                'savePath'     => $savePath,
                                'format'       => 'jpg',  //统一默认转 jpg
                                'heiht'        => $arr[1] ,
                                'with'         => $arr[0]
                            ]
                        ];
                        $res = invoke_v20_service($models,$param);
                    }
                }catch (Exception $e) {
                    fdump_api([$e->getMessage(), '图片压缩失败', $image], 'houseApi/encodeImgToDataUrl');
                }
                if (!isset($image['extension']) || (!in_array($image['extension'], ['jpg', 'jpeg']) && !in_array($image['type'], ['image/jpg', 'image/jpeg']))) {
                    $imgUrl = D('Image')->transform_image($image['url']['file']);
                }
                if(!isset($imgUrl) || !$imgUrl) {
                    $imgUrl = $image['url']['file'];
                }
                $id = $this->add_face($user['uid'],$imgUrl,$bind_pigcms_id);
                fdump_api([$image, $imgUrl, $id],'$add_face_img12');
                //todo 轨迹信息 用户上传家属或租客的人脸照片
                $str = '业主上传了'.(($user['type'] == 1)?'家人':'租客').$user['name'].'的人脸照片';
                $result = D('House_user_trajectory_log')->addLog(4,$user['village_id'],0,$parent_user['uid'],$parent_user['pigcms_id'],1,$str,'后台更新了人员数据');

                exit(json_encode(array('error'=>2,'msg'=>'上传成功','data'=>['url'=>$imgUrl,'id'=>$id,'pigcms_id'=>$_POST['pigcms_id'],'$result' => $result])));
            } else {
                exit(json_encode(array('error'=>1,'msg'=>'上传失败，请重试！')));
            }
        }else{
            exit(json_encode(array('error'=>1,'msg'=>'上传失败，请重试！')));
        }
    }

    public function  add_face($uid,$img_url,$bind_pigcms_id=0)
    {
        $face_img = D('House_face_img');
        $data['uid'] = $uid;
        if ($img_url) {
            import('@.ORG.face.word_encryption');
            $word_encryption = new word_encryption();
            $img_url = $word_encryption->text_encryption($img_url);
            $data['status'] = 3;
        } else {
            $data['status'] = 0;
        }
        $whereFaceNum = [];
        $whereFaceNum['uid'] = $uid;
        $whereFaceNum['status'] = ['in', [0,3]];
        $face_num = $face_img->where($whereFaceNum)->count();
        $data['img_url'] = $img_url;
        $data['add_time'] = time();
        if($bind_pigcms_id>0){
            $data['bind_pigcms_id'] = $bind_pigcms_id;
        }
        $id = $face_img->add_face_img($data);
        $judgeNum = $face_img->userMaxFaceImgNum;
        if (!$judgeNum) {
            $judgeNum = 5;
        }
        $judgeNum = $judgeNum-1;
        if ($id && $face_num>$judgeNum) {
            $saveDel = [
                'status' => 1,
                'del_time' => time(),
            ];
            $whereFaceDel = [
                'uid' => $uid,
                'status' => ['in', [0,3]]
            ];
            $limit = $face_num - $judgeNum;
            $face_img->where($whereFaceDel)->order('id ASC')->limit($limit)->save($saveDel);
        }
        return $id;
    }

    /**
     * 删除图片
     */
    public function house_face_img_del()
    {
        $id = I('id',0);
        if(empty($id))
            exit(json_encode(array('error'=>1,'msg'=>'缺少必传参数！')));
        $res = D('house_face_img')->where(['id'=>$id])->save(['status'=>1]);
        if($res){
            $hik_info_vision_i_community_app_key=C('config.hik_info_vision_i_community_app_key');
            $hik_info_vision_i_community_app_secret=C('config.hik_info_vision_i_community_app_secret');
            if($hik_info_vision_i_community_app_key && $hik_info_vision_i_community_app_secret){
                $hikRet=invoke_v20_service('\app\community\model\service\Device\HikInfoVisionICommunityService/delHouseFaceImg', ['village_id'=>$this->village_id,'id'=>$id]);
            }
            exit(json_encode(array('error'=>2,'msg'=>'删除成功')));
        }
        else{
            exit(json_encode(array('error'=>1,'msg'=>'系统异常！')));
        }
    }

    private function base64_to_img($base_img, $id) {
        $img_mer_id = sprintf("%09d", $id);
        $rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
        $up_dir = './upload/houseface/'.$rand_num.'/';
        if (!is_dir($up_dir)) {
            mkdir($up_dir, 0777, true);
        }
        if ($base_img) {
            $type = 'jpg';
            $new_file = $up_dir.date('YmdHis_').uniqid().'.'.$type;
            if(file_put_contents($new_file, base64_decode($base_img))){
                //判断是否需要上传至云存储
                $file_handle = new file_handle();
                $file_handle->upload($new_file);
                $img_path = $new_file;
                return $img_path;
            }else{
                return false;
            }
        } else {
            return false;
        }
    }
    public function faces()
    {
        $pigcmsId = I('id','', 'intval');
        if ($pigcmsId) {
            $msg = D('Face_door_service')->get_bind_user_device_img($pigcmsId);
            $info = D('House_village_user_bind')->where(['pigcms_id'=>$pigcmsId])->field('*')->find();
        } else {
            $msg = [];
            $info = [];
        }
        $this->assign('info',$info);
        $this->assign('dataImg',$msg['img_info']);
        $this->assign('pigcms_id',$pigcmsId);
        //第三方录入照片时，禁止上传照片
        $face_door_set = D('House_village_info')->where(['village_id' => $this->village_id])->field('face_door_set')->find();
        $this->assign('face_door_set', $face_door_set['face_door_set']);
		//第三方录入照片时，禁止上传照片
		$third_config = D('House_village_third_config')->where(['village_id' => $this->village_id])->find();
		if($third_config && $third_config['type'] == 'chinapopin_sichuan' && $third_config['status'] == '1'){
			$this->assign('no_upload_face', true);
		}

        $whereSyncLog = ['pigcms_id' => $pigcmsId];
        $faceSyncLogList = M('House_face_sync_log')->where($whereSyncLog)->order('last_time desc,log_time desc,log_id DESC')->select();
        $faceSyncs = [];
        if (!empty($faceSyncLogList)) {
            $deviceIds = array_column($faceSyncLogList, 'device_id');
            $whereDevice = ['device_id' => ['in', $deviceIds], 'is_del' => 0];
            $deviceInfoArr = M('House_face_device')->where($whereDevice)->getField('device_id,device_name,device_sn');
            // 下发状态 0 正在上传  1 成功  2失败
            $statusArr = [0 => '正在上传', 1 => '同步成功', 2 => '同步失败'];
            $syncDevices = [];
            foreach ($faceSyncLogList as $k => $v) {
                $device_id = isset($v['device_id']) && $v['device_id'] ? intval($v['device_id']) : 0;
                $deviceInfo = isset($deviceInfoArr[$device_id]) ? $deviceInfoArr[$device_id] : [];
                if (empty($deviceInfo)) {
                    continue;
                }
                if (isset($syncDevices[$device_id])) {
                    continue;
                }
                $syncDevices[$device_id] = $device_id;
                $device_name = isset($deviceInfo['device_name']) && $deviceInfo['device_name'] ? $deviceInfo['device_name'] : '';
                $device_sn = isset($deviceInfo['device_sn']) && $deviceInfo['device_sn'] ? $deviceInfo['device_sn'] : '';
                !$device_sn && $device_sn = isset($v['device_sn']) && $v['device_sn'] ? $v['device_sn'] : '';
                $msg = isset($v['msg']) && $v['msg'] ? $v['msg'] : '';
                $last_time = isset($v['last_time']) && $v['last_time'] > 100 ? date('Y-m-d H:i:s', $v['last_time']) : '';
                $log_time = isset($v['log_time']) && $v['log_time'] > 100 ? date('Y-m-d H:i:s', $v['log_time']) : '';
                $status = isset($v['status']) && $v['status'] ? intval($v['status']) : 0;
                $statusText = isset($statusArr[$status]) ? $statusArr[$status] : '';
                $sync_delete_time = isset($v['sync_delete_time']) && $v['sync_delete_time'] > 100 ? date('Y-m-d H:i:s', $v['sync_delete_time']) : '';
                $sync_delete_msg = isset($v['sync_delete_msg']) && $v['sync_delete_msg'] ? $v['sync_delete_msg'] : '';
                $faceSyncs[] = [
                    'device_id' => $device_id, 'device_sn' => $device_sn, 'device_name' => $device_name,
                    'statusText' => $statusText, 'msg' => $msg, 'sync_delete_time' => $sync_delete_time,
                    'sync_delete_msg' => $sync_delete_msg, 'sync_time' => $last_time ? $last_time : $log_time
                ];
            }
        }
       // fdump_api($faceSyncs, '$faceSyncs');
        $this->assign('faceSyncs', $faceSyncs);
		
        $this->display();
    }
    public function cap()
    {
        $pigcmsId = I('pigcms_id','', 'intval');
        $this->assign('pigcms_id',$pigcmsId);
        $this->display();
    }

    /**
     * 编辑用户水电燃费用
     */
    public function edit_fee()
    {
        if(IS_POST){
            $pigcms_id = $_POST['pigcms_id'];
            $user_info = M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->find();
            $edit_price = $_POST['edit_price'];
            $type = $_POST['type'];
            $kind = $_POST['kind'];
            $reason = $_POST['reason'];
            $gas['type']= $electric['type']= $water['type'] = 1;
            $gas['village_id']= $electric['village_id']= $water['village_id'] = $user_info['village_id'];
            $gas['uid']= $electric['uid']= $water['uid'] = $user_info['uid'];
            $gas['usernum']= $electric['usernum']= $water['usernum'] = $user_info['usernum'];
            $gas['name']= $electric['name']= $water['name'] = $user_info['name'];
            $gas['phone']= $electric['phone']= $water['phone'] = $user_info['phone'];
            $gas['bind_id']= $electric['bind_id']= $water['bind_id'] = $pigcms_id;
            $gas['add_time']= $electric['add_time']= $water['add_time'] = time();
            switch ($type){
                case 1:
                    if($kind == 1){
                        $res = M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->setInc('water_price',$edit_price);
                        M('House_village_user_edit_price_record')->add(['pigcms_id'=>$pigcms_id,'money'=>$edit_price,'detail'=>'增加'.$edit_price.'元','action'=>'手动修改-增加','add_time'=>time(),'remark'=>$reason,'type'=>$type]);
                        $water['water_price'] = $edit_price;
                        M('House_village_user_paylist')->data($water)->add();
                    } else{
                        $res = M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->setDec('water_price',$edit_price);
                        M('House_village_user_edit_price_record')->add(['pigcms_id'=>$pigcms_id,'money'=>$edit_price,'detail'=>'减少'.$edit_price.'元','action'=>'手动修改-减少','add_time'=>time(),'remark'=>$reason,'type'=>$type]);
                        $water['water_price'] = $edit_price*-1;
                        M('House_village_user_paylist')->data($water)->add();
                    }
                    break;
                case 2:
                    if($kind == 1){
                        $res = M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->setInc('electric_price',$edit_price);
                        M('House_village_user_edit_price_record')->add(['pigcms_id'=>$pigcms_id,'money'=>$edit_price,'detail'=>'增加'.$edit_price.'元','action'=>'手动修改-增加','add_time'=>time(),'remark'=>$reason,'type'=>$type]);
                        $electric['electric_price'] = $edit_price;
                        M('House_village_user_paylist')->data($electric)->add();
                    } else{
                        $res = M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->setDec('electric_price',$edit_price);
                        M('House_village_user_edit_price_record')->add(['pigcms_id'=>$pigcms_id,'money'=>$edit_price,'detail'=>'减少'.$edit_price.'元','action'=>'手动修改-减少','add_time'=>time(),'remark'=>$reason,'type'=>$type]);
                        $electric['electric_price'] = $edit_price*-1;
                        M('House_village_user_paylist')->data($electric)->add();
                    }
                    break;
                case 3:
                    if($kind == 1){
                        $res = M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->setInc('gas_price',$edit_price);
                        M('House_village_user_edit_price_record')->add(['pigcms_id'=>$pigcms_id,'money'=>$edit_price,'detail'=>'增加'.$edit_price.'元','action'=>'手动修改-增加','add_time'=>time(),'remark'=>$reason,'type'=>$type]);
                        $gas['gas_price'] = $edit_price;
                        M('House_village_user_paylist')->data($gas)->add();
                    } else{
                        $res = M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->setDec('gas_price',$edit_price);
                        M('House_village_user_edit_price_record')->add(['pigcms_id'=>$pigcms_id,'money'=>$edit_price,'detail'=>'减少'.$edit_price.'元','action'=>'手动修改-减少','add_time'=>time(),'remark'=>$reason,'type'=>$type]);
                        $gas['gas_price'] = $edit_price*-1;
                        M('House_village_user_paylist')->data($gas)->add();
                    }
                    break;
                case 4:
                    if($kind == 1){
                        $res = M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->setInc('park_price',$edit_price);
                        M('House_village_user_edit_price_record')->add(['pigcms_id'=>$pigcms_id,'money'=>$edit_price,'detail'=>'增加'.$edit_price.'元','action'=>'手动修改-增加','add_time'=>time(),'remark'=>$reason,'type'=>$type]);
                    } else{
                        $res = M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->setDec('park_price',$edit_price);
                        M('House_village_user_edit_price_record')->add(['pigcms_id'=>$pigcms_id,'money'=>$edit_price,'detail'=>'减少'.$edit_price.'元','action'=>'手动修改-减少','add_time'=>time(),'remark'=>$reason,'type'=>$type]);
                    }
                    break;
                default :
                    if($kind == 1)
                        $res = M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->setInc('gas_price',$edit_price);
                    else
                        $res = M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->setDec('gas_price',$edit_price);
            }
            if($res)
                exit(json_encode(array('status'=>1,'msg'=>'成功')));
            else
                exit(json_encode(array('status'=>0,'msg'=>'失败')));
        }
        $village_id = $this->village_id;
        $pigcms_id = $_GET['pigcms_id'];
        $usernum = $_GET['usernum'];
        $type = $_GET['type'];
        $user_info = D('House_village_user_bind')->get_one($village_id,$pigcms_id,'pigcms_id',0);
        switch ($type){
            case 1:
                $this->assign('price',$user_info['water_price']);
                $this->assign('type_name','水费总金额');
                $this->assign('type',$type);
                break;
            case 2:
                $this->assign('price',$user_info['electric_price']);
                $this->assign('type_name','电费总金额');
                $this->assign('type',$type);
                break;
            case 3:
                $this->assign('price',$user_info['gas_price']);
                $this->assign('type_name','燃气费总金额');
                $this->assign('type',$type);
                break;
            case 4:
                $this->assign('price',$user_info['park_price']);
                $this->assign('type_name','停车费总金额');
                $this->assign('type',$type);
                break;
            default :
                $this->assign('price',$user_info['water_price']);
                $this->assign('type_name','水费总金额');
        }
        $this->assign('pigcms_id',$pigcms_id);
        $this->assign('usernum',$usernum);
        $this->display();
    }

    /**
     * 更改用户水电燃费用记录
     */
    public function price_edit_record()
    {
        $pigcms_id = $_GET['pigcms_id'];
        $type = $_GET['type'];
        $record_list = M('House_village_user_edit_price_record')->where(['pigcms_id'=>$pigcms_id,'type'=>$type])->select();
        foreach ($record_list as $k=>$v){
            $record_list[$k]['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
        }
        $this->assign('record_list',$record_list);
        $this->display();
    }


    public function readCard(){
        $brand_list=D('HardwareBrand')->getBrand(6);
        $this->assign('brand_list',$brand_list);
        $this->display();
    }

    public function getType()
    {
        $brand_id = $_POST['brand_id'];
        $type = 1;
        $type_list = D('HardwareBrand')->getType($brand_id, $type);
        if (!empty($type_list)) {
            foreach ($type_list as $k => $value) {
                if ($value['sub_series_type'] != 1) {
                    unset($type_list[$k]);
                }
                if (!isset($value['hardware_config']['isSupportCard'])) {
                    $type_list[$k]['hardware_config']['isSupportCard'] = 0;
                }
            }
        }
        $type_list = array_values($type_list);
        exit(json_encode(array('status' => 1, 'msg' => 'Ok！', 'list' => $type_list)));
    }

    public function add_ic_card(){
            $data['device_brand']=$_POST['brand_list'];
            $data['device_type']=$_POST['brand_type'];
            $data['ic_card']=$_POST['ic_card'];
            $data['vacancy_id']=$_POST['vacancy_id'];
            if (empty($data['vacancy_id'])){
                exit(json_encode(array('status'=>0,'msg'=>'房间信息不能为空！')));
            }
            if (empty($data['ic_card'])){
                exit(json_encode(array('status'=>0,'msg'=>'卡号不能为空！')));
            }
            if (empty($data['device_type'])){
                exit(json_encode(array('status'=>0,'msg'=>'设备类型不能为空！')));
            }
            if (empty($data['device_brand'])){
                exit(json_encode(array('status'=>0,'msg'=>'设备品牌不能为空！')));
            }
            $data['add_time']=time();
            $data['village_id']=$this->village_id;
            $id=D('House_village_vacancy_iccard')->add($data);
            if ($id>0){
                $user_info=D('House_village_user_bind')->where(['village_id'=>$data['village_id'],'vacancy_id'=>$data['vacancy_id'],'type'=>0,'status'=>1])->field('single_id,floor_id,layer_id,vacancy_id,type,parent_id,pigcms_id,uid,phone,status,property_starttime,property_endtime')->find();
                $device_list=D('House_face_device')->where(['village_id'=>$data['village_id'],'is_del'=>0])->field('device_type,device_id,device_name,device_sn,floor_id')->select();
                if (!empty($device_list)&&!empty($user_info)){
                    foreach ($device_list as $kk => &$vv) {
                        if (in_array($vv['device_type'],[26])){
                            D('Face_d6_request_record')->add_create_card_record($data['ic_card'],$vv['device_id'],$user_info['pigcms_id'],$data['village_id']);
                        }
                    }
                }
                exit(json_encode(array('status'=>1,'msg'=>'添加成功')));
            }else{
                exit(json_encode(array('status'=>0,'msg'=>'添加失败')));
            }
    }

    //身份证号解析
    public function getIdCardAnalysis(){
        $id_card=idCardAnalysis(trim($_POST['id_card']));
        if($id_card == false){
            exit(json_encode(['status'=>0,'msg'=>'身份证号不合法','data'=>[]]));
        }else{
            exit(json_encode(['status'=>1,'msg'=>'身份证号解析成功','data'=>$id_card]));
        }
    }
    
    //协议设置
    public function bind_protocol_set(){
        $house_village_protocolDb=D('House_village_protocol');
        if(IS_POST && isset($_POST['ptitle'])){
            $whereArr=array('village_id'=>$this->village_id,'cfromtype'=>'user_bind_vacancy');
            if(isset($_POST['idd']) && ($_POST['idd']>0)){
                $whereArr['id']=$_POST['idd'];
            }
            $saveArr=array();
            $saveArr['ptitle']=trim($_POST['ptitle']);
            if(empty($saveArr['ptitle'])){
                $this->error('协议标题不能为空！');
            }
            $saveArr['ptitle']=htmlspecialchars($saveArr['ptitle'],ENT_QUOTES );
            $saveArr['pcontent']=trim($_POST['pcontent']);
            if(empty($saveArr['pcontent'])){
                $this->error('协议内容不能为空！');
            }
            $saveArr['pcontent']=htmlspecialchars($saveArr['pcontent'],ENT_QUOTES );
            $saveArr['status']=intval($_POST['status']);
            $protocol=$house_village_protocolDb->where($whereArr)->order('id desc')->find();
            if(!empty($protocol)){
                $house_village_protocolDb->where(array('id'=>$protocol['id']))->data($saveArr)->save();
                $this->success('保存成功！');
            }else{
                $saveArr['village_id']=$this->village_id;
                $saveArr['cfromtype']='user_bind_vacancy';
                $saveArr['add_time']=time();
                $house_village_protocolDb->data($saveArr)->add();
                $this->success('保存成功！');
            }
        }else{
            $whereArr=array('village_id'=>$this->village_id,'cfromtype'=>'user_bind_vacancy');
            $protocol=$house_village_protocolDb->where($whereArr)->order('id desc')->find();
            if(empty($protocol)){
                $protocol=array();
            }else{
                $protocol['pcontent']=htmlspecialchars_decode($protocol['pcontent'],ENT_QUOTES);
            }
            $this->assign('protocol',$protocol);
            $this->display();
        }
        
    }
    public function user_bind_iccard(){
        $bind_pigcms_id=$_GET['bind_pigcms_id'] ? trim($_GET['bind_pigcms_id']):0;
        $house_village_user_bind_iccard=M('House_village_user_bind_iccard');
        $whereArr=array('village_id'=>$this->village_id,'bind_id'=>$bind_pigcms_id,'del_time'=>array('lt',10));
        $user_iccards =$house_village_user_bind_iccard->where($whereArr)->order('type ASC,end_time DESC')->select();
        $card_count=0;
        if($user_iccards){
            $typeArr=array('1'=>'普通卡','2'=>'胁迫卡');
            foreach ($user_iccards as $kk=>$vv){
                $card_count++;
                $user_iccards[$kk]['type_str']=isset($typeArr[$vv['type']]) ? $typeArr[$vv['type']]:'';
                $user_iccards[$kk]['effective_time']='';
                $user_iccards[$kk]['card_expire_time_type_str']='跟随物业费';
                if($vv['card_expire_time_type']==1){
                    $user_iccards[$kk]['card_expire_time_type_str']='自定义';
                }
                if($vv['start_time']>0 && $vv['end_time']>0){
                    $user_iccards[$kk]['effective_time']=date('Y-m-d H:i',$vv['start_time']).' 至  '.date('Y-m-d H:i',$vv['end_time']);
                }
            }
        }
        $imgFace = D('Face_door_service')->get_bind_user_device_img($bind_pigcms_id);
        $img_faces=$imgFace && isset($imgFace['img_info']) ? $imgFace['img_info']:array();
        $this->assign('img_faces',$img_faces);
        $this->assign('card_count',$card_count);
        $this->assign('user_iccards',$user_iccards);
        $this->assign('bind_pigcms_id',$bind_pigcms_id);
        $this->display();
    }
    public function add_user_iccard(){
        $bind_pigcms_id=$_GET['bind_pigcms_id'] ? intval($_GET['bind_pigcms_id']):0;
        $idd=$_GET['idd'] ? intval($_GET['idd']):0;
        $village_about_info = M('House_village_info')->where(array('village_id'=>$this->village_id))->find();
        $card_expire_time_type=0;
        if($village_about_info && isset($village_about_info['card_expire_time_type'])){
            $card_expire_time_type=$village_about_info['card_expire_time_type'];
        }
        $house_village_user_bind_iccard=M('House_village_user_bind_iccard');
        $user_iccard=array('id'=>0);
        if($idd>0){
            $whereArr=array('id'=>$idd,'village_id'=>$this->village_id,'bind_id'=>$bind_pigcms_id);
            $user_iccard=$house_village_user_bind_iccard->where($whereArr)->find();
            if($user_iccard){
                $user_iccard['start_time_str']=$user_iccard['start_time']>100 ? date('Y-m-d',$user_iccard['start_time']):'';
                $user_iccard['end_time_str']=$user_iccard['end_time']>100 ? date('Y-m-d',$user_iccard['end_time']):'';
            }
        }
        $this->assign('user_iccard',$user_iccard);
        $this->assign('bind_pigcms_id',$bind_pigcms_id);
        $this->assign('card_expire_time_type',$card_expire_time_type);
        $this->display();
    }
    public function add_user_iccard_action(){
        $bind_pigcms_id=$_POST['bind_pigcms_id'] ? intval($_POST['bind_pigcms_id']):0;
        $idd=$_POST['idd'] ? intval($_POST['idd']):0;
        $card_type=isset($_POST['card_type']) ? intval($_POST['card_type']):1;
        $ic_card_no=$_POST['ic_card_no'] ? trim($_POST['ic_card_no']):'';
        $start_time_str=$_POST['start_time_str'] ? trim($_POST['start_time_str']):'';
        $end_time_str=$_POST['end_time_str'] ? trim($_POST['end_time_str']):'';
        if(empty($ic_card_no)){
            $this->error('卡号不能为空！');
        }
        $house_village_user_bind_iccard=M('House_village_user_bind_iccard');
        $user_bind_iccard='';
        if($idd>0){
            $whereArr=array('id'=>$idd,'village_id'=>$this->village_id,'bind_id'=>$bind_pigcms_id);
            $user_bind_iccard=$house_village_user_bind_iccard->where($whereArr)->find();
        }
        if($user_bind_iccard){
            $whereTmpArr=array('village_id'=>$this->village_id,'ic_card_no'=>$ic_card_no,'id'=>array('neq',$user_bind_iccard['id']),'del_time'=>array('lt',10));
        }else{
            $whereTmpArr=array('village_id'=>$this->village_id,'ic_card_no'=>$ic_card_no,'del_time'=>array('lt',10));
        }
        $user_bind_iccard_tmp=$house_village_user_bind_iccard->where($whereTmpArr)->find();
        if($user_bind_iccard_tmp){
            $this->error('此卡号已被添加了，请换一个卡号！');
        }
        
        $card_data=array('village_id'=>$this->village_id,'bind_id'=>$bind_pigcms_id);
        $card_data['type']=$card_type;
        $card_data['last_time']=time();
        if($start_time_str){
            $card_data['start_time']=strtotime($start_time_str);
        }
        if($end_time_str){
            $card_data['end_time']=strtotime($end_time_str);
            $card_data['end_time']=$card_data['end_time']+24*3600-10;
        }
        $village_about_info = M('House_village_info')->where(array('village_id'=>$this->village_id))->find();
        $card_expire_time_type=0;  //0跟随物业费1自定义
        if($village_about_info && isset($village_about_info['card_expire_time_type'])){
            $card_expire_time_type=$village_about_info['card_expire_time_type'];
        }
        $card_data['card_expire_time_type']=$card_expire_time_type;
        if($card_expire_time_type<1){
            $card_data['start_time']=0;
            $card_data['end_time']=0;
        }
        if($user_bind_iccard){
            $house_village_user_bind_iccard->where(array('id'=>$user_bind_iccard['id']))->data($card_data)->save();
        }else{
            $card_data['ic_card_no']=$ic_card_no;
            $house_village_user_bind_iccard->data($card_data)->add();
        }
        $this->success('保存成功！');
    }
    public function del_user_iccard(){
        $bind_pigcms_id=$_POST['bind_pigcms_id'] ? intval($_POST['bind_pigcms_id']):0;
        $idd=$_POST['idd'] ? intval($_POST['idd']):0;
        $ic_card_no=$_POST['ic_card_no'] ? trim($_POST['ic_card_no']):'';
        $house_village_user_bind_iccard=M('House_village_user_bind_iccard');
        $whereArr=array('id'=>$idd,'village_id'=>$this->village_id,'bind_id'=>$bind_pigcms_id);
        $nowtime=time();
        $save_data=array('del_time'=>$nowtime,'last_time'=>$nowtime);
        $house_village_user_bind_iccard->where($whereArr)->data($save_data)->save();
        D('Face_door_xihan_service')->delxiHanFaceDoorCard($ic_card_no,$this->village_id,$bind_pigcms_id);
        $this->success('操作成功！');
    }
    public function syn_large_device_img(){ 
        set_time_limit(0);
        $pigcms_id_arr=array();
        foreach ($pigcms_id_arr as $idd){
            $this->syn_device_img($idd);
            usleep(200000);  //0.2秒
        }
    }

    public function getLilinPfUserData(){
        $ptype=$_GET['ptype'] ? intval($_GET['ptype']):1;  //1业主 2租客
        $whereArr=array('village_id'=>$this->village_id);
        $village_data = M('House_village')->where($whereArr)->find();
        if($village_data && !empty($village_data['lilin_pf_client_id']) && !empty($village_data['lilin_pf_client_secret']) && !empty($village_data['lilin_village_no'])){
            $models = '\app\community\model\service\Device\LiLinDevAccessApiService/getLilinPfUserDatas';
            $param=['village_id'=>$this->village_id,'ptype'=>$ptype];
            $result = invoke_v20_service($models, $param);
            exit(json_encode(array('errorCode'=>0,'errorMsg'=>'操作成功！','result'=>$result)));
        }
        exit(json_encode(array('errorCode'=>0,'errorMsg'=>'操作成功！')));
    }
}
