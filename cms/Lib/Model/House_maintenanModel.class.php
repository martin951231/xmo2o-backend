<?php

class House_maintenanModel extends Model{

    public $next_key=[
        0=>30,
        1=>60,
        2=>120,
        3=>180
    ];

    //todo 查询列表
    public function getlist(){
        $where = array(
            'status'=>['in', [0,1]]
        );
        import('@.ORG.system_page');
        $count = $this->where($where)->count();
        $p = new Page($count, 20);
        $field=true;
        $list = $this->where($where)->field($field)->order('add_time DESC')->limit($p->firstRow, $p->listRows)->select();
//        if ($list) {
//            foreach ($list as &$val) {
//            }
//        }
        $pagebar = $count > 0 ? $p->show() : '';
        return ['list'=>$list,'pagebar'=>$pagebar];
    }

    //todo 查询单条数据
    public function get_one($where,$field=true){
        return $this->field($field)->where($where)->find();
    }

    //todo 写入数据
    public function maintenan_add($data){
       return $this->data($data)->add();
    }

    //todo 编辑数据
    public function maintenan_edit($where,$data){
        return $this->where($where)->data($data)->save();
    }

    public function getlistlog($id){
        $where = array(
            '_string' => 'FIND_IN_SET(' . $id . ',`maintenan_id`)'
        );
//        import('@.ORG.system_page');
        import('@.ORG.merchant_page');
        $count = D('House_maintenan_log')->where($where)->count();
        $p = new Page($count, 10);
        $field=true;
        $list = D('House_maintenan_log')->where($where)->field($field)->order('add_time DESC')->limit($p->firstRow, $p->listRows)->select();
        $pagebar = $count > 0 ? $p->show() : '';
        return ['list'=>$list,'pagebar'=>$pagebar];
    }



    //todo 获取最新故障数据
    public function get_maintenan_log($type,$device_id){
        $list=D('House_maintenan_log')->where(['device_type'=>$type,'device_id'=>$device_id])->field('id,next_key,next_time')->order('id desc')->limit(0,2)->select();
        $data=[];
        if(!empty($list)){
            $data=$list[0];
        }
        return $data;
    }

    //todo 获取下一个参数值
    public function get_next_key($value){
        $data=0;
        $next_key=$this->next_key;
        foreach ($next_key as $k=>$v){
            if($v == $value){
                if(isset($next_key[$k+1])){
                    $data=$next_key[$k+1];
                    break;
                }
            }
        }
        return $data;
    }


    //todo 获取设备地址
    public function get_address($face_device){
        $House_village=D('House_village');
        $single_name=$floor_name='';
        if ($face_device['floor_id'] != "-1") {
            /**
             * 单元机
             */
            $floor_info = M('House_village_floor')
                ->join(' as f LEFT JOIN '.C('DB_PREFIX').'house_village_single as s on s.id=f.single_id')
                ->where(array('f.floor_id' => $face_device['floor_id']))->field('s.single_name,f.floor_name')->find();
            $address = $floor_info['single_name'].(D('House_village')->getHouseAlias('single_name')?:'栋').$floor_info['floor_name'].(D('House_village')->getHouseAlias('floor_name')?:'单元');
            $device_type=1;
            $single_name=$floor_info['single_name'];
            $floor_name=$floor_info['floor_name'];
        }
        else{
            /**
             * 围墙机
             */
            $address = D('House_village_public_area')->where(['public_area_id'=>$face_device['public_area_id']])->getField('public_area_name');
            $device_type=2;
        }
        $village_name=$House_village->where(['village_id'=>$face_device['village_id']])->getField('village_name');
        $data=[
            'village_name'      =>  $village_name ? $village_name : '',
            'single_name'       =>  $single_name,
            'floor_name'        =>  $floor_name,
            'device_address'    =>  $address,
            'device_type'       =>  $device_type
        ];
        return $data;
    }

    /**
     * todo 故障通知   人脸门禁类型：A3/A4/A5/A6/A7 D3/D4
     * @author: liukezhu
     * @date : 2021/11/2
     * @param $type       1：人脸门禁 2：充电桩
     * @param $device_id  对应表主键id   house_face_device  house_village_pile_equipment
     * @param $reason     故障原因
     * @param $notice     true:发送通知 false：不发送通知
     * @return bool
     *$d=[
        'type' => 1,
        'device_id' => 86,
        'reason'=>'测试啊啊啊'
        ];
        $rr= invoke_cms_model('House_maintenan/faultLog',$d, true);
     */
    public function faultLog($type,$device_id,$reason,$notice=true){
        fdump_api(['参数进来了',__LINE__,$type,$device_id,$reason],'maintenan/faultLog',1);
        $data=[];
        $time=time();
        $adds='';
        $tempKey='TM0001801';
        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
        //todo 最新故障数据
        $log=$this->get_maintenan_log($type,$device_id);
        // print_r($log['next_time']);die;
        if($log && intval($log['next_key']) > 0){
            if($log['next_time'] > $time){
                return false;
            }
            $key=$this->get_next_key($log['next_key']);
            if(intval($key) == 0){
                //todo 不在阶段性参数内 不处理
                return false;
            }
            $next_key=$key;
            $next_time=$time + ($key * 60);
        }
        else{
            $next_key=$this->next_key[0];
            $next_time=$time + ($this->next_key[0] * 60);
        }
        //todo 运维人员
        $maintenan=$this->where([
            'status'=>1,
            'openid'=>array('neq','')
        ])->field('id,name,phone,openid,nickname')->order('add_time desc,id desc')->select();
        if(empty($maintenan)){
            fdump_api([__LINE__,'--暂无运维人员'],'maintenan/faultLog',1);
        }
        $title = '';
        $remark = '';
        $device_name='';
        $village_id=0;
        switch ($type) {
            case 1:
                //todo 人脸门禁
                $where_device = [
                    'device_id' => $device_id,
                    'is_del'=>0
                ];
                $device = D('House_face_device')->where($where_device)->find();
                if(!$device){
                    return false;
                }
                $address=$this->get_address($device);
                $village_id=$device['village_id'];
                $data=[
                    'village_id'=>$device['village_id'],
                    'village_name'=>$address['village_name'],
                    'device_id'=>$device['device_id'],
                    'device_type'=>$type,
                    'address'=>$address['device_address'],
                    'device_name'=>$device['device_name'],
                    'reason'=>$reason,
                    'add_time'=>$time
                ];
                $adds=$address['village_name'].' '.$address['device_address'];
                $title = $address['village_name'];
                $device_name=$device['device_name'];
                $remark = $data['device_name'];
                break;
            case 2:
                //todo 充电桩
                $where_device = [
                    'id' => $device_id,
                    'is_del'=>1];
                $device = D('House_village_pile_equipment')->where($where_device)->find();
                if(!$device){
                    return false;
                }
                $village_id=$device['village_id'];
                $village_name=D('house_village')->where(['village_id'=>$device['village_id']])->getField('village_name');
                $data=[
                    'village_id'=>$device['village_id'],
                    'village_name'=>$village_name,
                    'device_id'=>$device['id'],
                    'device_type'=>$type,
                    'address'=>'--',
                    'device_name'=>$device['equipment_name'],
                    'reason'=>$reason,
                    'add_time'=>$time
                ];
                $adds=$village_name;
                $title = $village_name;
                $device_name=$device['equipment_name'];
                $remark = $data['device_name'];
                break;
        }
        if(empty($data)){
            fdump_api([__LINE__,'--$data数据为空'],'maintenan/faultLog',1);
            return false;
        }
        $data['next_key']=$next_key;
        $data['next_time']=$next_time;
        $data['maintenan_id']=empty($maintenan) ? '' : implode(',',array_column($maintenan, 'id'));
        D('House_maintenan_log')->data($data)->add();
        if(intval(C('config.is_maintenan_send')) < 1){
            fdump_api([__LINE__,'--未开启配置开关'],'maintenan/faultLog',1);
            return false;
        }
        if(!empty($maintenan)&&$notice==true){

            foreach ($maintenan as $v){
                $send= $model->sendTempMsg($tempKey,
                    array(
                        'href' => '',
                        'wecha_id' => $v['openid'],
                        'first' => '报警信息',
                        'keyword1' => $title,
                        'keyword2' => $reason,
                        'keyword3' => date('H时i分', $_SERVER['REQUEST_TIME']),
                        'remark' => $remark
                    )
                );
                fdump_api([__LINE__,$send,[
                    'href' => '',
                    'wecha_id' => $v['openid'],
                    'first' => '报警信息',
                    'keyword1' => $title,
                    'keyword2' => $reason,
                    'keyword3' => date('H时i分', $_SERVER['REQUEST_TIME']),
                    'remark' => $remark
                ]],'maintenan/faultLog',1);
            }
        }
        if($village_id>0){
            $nowtime=time();
            $village_info_extend = D('House_village_info')->getVillageInfo(['village_id'=>$village_id],'urge_notice_type');
            $urge_notice_type=3;
            if($village_info_extend && isset($village_info_extend['urge_notice_type'])){
                //1短信通知2微信模板通知3短信和微信模板通知
                $urge_notice_type=$village_info_extend['urge_notice_type'];
            }
            //发短信
            $workers=array();
            if(in_array($urge_notice_type,array(1,3))){
                $whereArr=array('mw.village_id'=>$village_id,'mw.is_del_time'=>0,'hw.status'=>1,'hw.is_del'=>0);
                $workers=M('House_maintenan_worker')->join(' as mw LEFT JOIN '.C('DB_PREFIX').'house_worker as hw on mw.wid=hw.wid')->where($whereArr)->field('hw.wid,hw.name,hw.village_id,hw.nickname,hw.phone,hw.openid')->select();
                if(!empty($workers)){
                    foreach ($workers as $vv){
                        if(!empty($vv['phone']) && strlen($vv['phone'])==11){
                            $text='您所管理的'.$title.'，有'.$device_name.'智能设备于'.date('Y-m-d H:i:s').' 离线，请您尽快处理。';
                            $sms_data = array('mer_id' => 0, 'store_id' => 0, 'sendto'=>'worker','content' => $text, 'mobile' => $vv['phone'], 'uid' => 0, 'type' => 'house_worker','village_id'=>$village_id);
                            Sms::sendSms(($sms_data));
                        }
                    }
                }
            }
            //发模板消息
            if(in_array($urge_notice_type,array(2,3))){
                $tempKey='TM0001801';
                if(empty($workers)){
                    $whereArr=array('mw.village_id'=>$village_id,'mw.is_del_time'=>0,'hw.status'=>1,'hw.is_del'=>0);
                    $workers=M('House_maintenan_worker')->join(' as mw LEFT JOIN '.C('DB_PREFIX').'house_worker as hw on mw.wid=hw.wid')->where($whereArr)->field('hw.wid,hw.name,hw.village_id,hw.nickname,hw.phone,hw.openid')->select();
                }
                if(!empty($workers)){
                    foreach ($workers as $vv){
                        if(!empty($vv['openid'])){
                            $send= $model->sendTempMsg($tempKey,
                                array(
                                    'href' => '',
                                    'wecha_id' => $vv['openid'],
                                    'first' => '报警信息',
                                    'keyword1' => $title,
                                    'keyword2' => $reason,
                                    'keyword3' => date('H时i分',$nowtime ),
                                    'remark' => $remark
                                )
                            );
                        }
                    }
                }

            }
        }
        return true;

    }

    /**
     * todo 故障解除
     * @author: liukezhu
     * @date : 2021/11/3
     * @param $type
     * @param $device_id
     * @return bool
     */
    public function normalLog($type,$device_id){
        $log=$this->get_maintenan_log($type,$device_id);
        if($log && intval($log['next_key']) > 0){
            D('House_maintenan_log')->where(['id'=>$log['id']])->data(['next_key'=>0])->save();
        }
        return true;
    }
}