<?php


namespace app\community\controller\village_api;

use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseVillageUserService;
use app\community\model\service\VillageQywxMessageService;
use app\community\model\service\HouseWorkerService;
use app\community\model\service\HouseVillageService;
use app\common\model\service\plan\PlanService;
use think\helper\Arr;

class SendQywxMessageController extends CommunityBaseController
{
    /**
     * 企业微信群发列表
     * @author lijie
     * @date_time 2021/03/12
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSendMessageList()
    {
        $village_id = $this->adminUser['village_id'];
        $property_id = $this->adminUser['property_id'];
        $login_role = $this->login_role;
        if (in_array($login_role,$this->propertyRole)) {
            $type = 2;//物业
            $param_id = $property_id;
        } else {
            $type = 1;//小区
            $param_id = $village_id;
        }
        $page = $this->request->post('page',1);
        $limit = 10;
        $send_type = $this->request->post('send_type',0);
        $send_status = $this->request->post('send_status',0);
        $message_name = $this->request->post('message_name','');
        $date = $this->request->post('date','');
        $service_village_qywx_message = new VillageQywxMessageService();
        $where[] = ['is_del','=',0];
        $where[] = ['add_type','=',$type];
        if($send_status){
            $where[] = ['send_status','=',$send_status];
        }
        if($send_type) {
            $where[] = ['send_type','=',$send_type];
        }
        if($message_name) {
            $where[] = ['message_name','like','%'.$message_name.'%'];
        }
        if($date && $date[0]) {
            $where[] = ['send_time','between',[$date[0],$date[1]]];
        }
        if($type == 1) {
            $where[] = ['village_id','=',$param_id];
        }
        if($type == 2) {
            $where[] = ['property_id','=',$param_id];
        }
        $data = $service_village_qywx_message->getQywxMessageList($where,true,$page,$limit,$order='id DESC');
        $count = $service_village_qywx_message->getQywxMessageCount($where);
        $res['list'] = $data;
        $res['count'] = $count;
        $res['total_limit'] = $limit;
        (new VillageQywxMessageService())->sendMessage();
        return api_output(0,$res);
    }

    /**
     * 删除企业微信群发
     * @author lijie
     * @date_time 2021/03/12
     * @return \json
     * @throws \Exception
     */
    public function delSendMessage()
    {
        $id = $this->request->post('id',0);
        $service_village_qywx_message = new VillageQywxMessageService();
        $res = $service_village_qywx_message->saveQywxMessage(['id'=>$id],['is_del'=>1]);
        return api_output(0,[]);
    }

    /**
     * 标签组列表
     * @author lijie
     * @date_time 2021/03/15
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getQywxLabelList()
    {
        $village_id = $this->adminUser['village_id'];
        $property_id = $this->adminUser['property_id'];
        $login_role = $this->login_role;
        if (in_array($login_role,$this->propertyRole)) {
            $type = 2;//物业
            $param_id = $property_id;
        } else {
            $type = 1;//小区
            $param_id = $village_id;
        }
        $service_village_qywx_message = new VillageQywxMessageService();
        $service_house_village = new HouseVillageService();
        if($type == 1){
            $service_house_village = new HouseVillageService();
            $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$param_id],'property_id');
            //$where[] = ['add_type','=',1];
            //$where[] = ['village_id','=',$param_id];
            $property_id = $village_info['property_id'];
            $where = "status = 1 and (village_id=$param_id or property_id =$property_id)";
            $villageArr = array();
            $dataConfig = $service_village_qywx_message->getDataConfigLabel(['village_id'=>$param_id,'type'=>2],'title as label_group_name,key,use_field');
            if (!empty($dataConfig)) {
                $dataConfig = $dataConfig->toArray();
            } else {
                $dataConfig = [];
            }
        } else{
            $where[] = ['status','=',1];
            $where[] = ['add_type','=',2];
            $where[] = ['property_id','=',$param_id];
            $villageList = $service_house_village->getList(['property_id'=>$param_id,'status'=>1],'village_id as label,village_name as label_name');
            if (!empty($villageList)) {
                $villageList = $villageList->toArray();
            } else {
                $villageList = [];
            }
            $villageArr[0]['label_group_name'] = '小区';
            $villageArr[0]['label_lists'] = $villageList;
            $villageArr[0]['type'] = 0;
            $dataConfig = $service_village_qywx_message->getDataConfigLabel(['property_id'=>$param_id,'type'=>2],'title as label_group_name,key,use_field');
            if (!empty($dataConfig)) {
                $dataConfig = $dataConfig->toArray();
            } else {
                $dataConfig = [];
            }
        }
        $data = $service_village_qywx_message->getQywxCodeLabel($where,'label_group_id,label_group_name',0);
        if (!empty($data)) {
            $data = $data->toArray();
        } else {
            $data = [];
        }
        $userTypeArr[0]['label_group_name'] = '业主类型';
        $userTypeArr[0]['type'] = 0;
        $userTypeArr[0]['label_lists'] = [['label_id'=>1,'label_name'=>'房主'],['label_id'=>2,'label_name'=>'家属'],['label_id'=>3,'label_name'=>'租客']];
        $data = array_merge($data,$userTypeArr,$villageArr,$dataConfig);
        return api_output(0,$data);
    }

    /**
     * 组织架构
     * @author lijie
     * @date_time 2021/03/19
     * @return \json
     */
    public function getTissueNav()
    {
        $village_id = $this->adminUser['village_id'];
        $property_id = $this->adminUser['property_id'];
        $login_role = $this->login_role;
        $type_ = $this->request->post('type_');
        if(!$type_){
            $type_=false;
        }

        if (in_array($login_role,$this->propertyRole)) {
            $type = 2;//物业
            $param_id = $property_id;
        } else {
            $type = 1;//小区
            $param_id = $village_id;
        }
        $service_house_worker = new HouseWorkerService();
        if($type == 1) {
            $where[] = ['village_id','=',$param_id];
        } else {
            $where[] = ['property_id','=',$param_id];
        }
        $where[] = ['status','=',1];
        $where[] = ['is_del','=',0];
        try{
            $list = $service_house_worker->getNav($where,$type_);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    public function getWorker()
    {
        $name = $this->request->post('name','');
        if(empty($name)) {
            return api_output_error(1001,'缺少必传参数');
        }
        $service_house_worker = new HouseWorkerService();
        $data = $service_house_worker->getOneWorker([['qy_id','<>',''],['name','=',$name],['status','=',1],['is_del','=',0]],'qy_id,name');
        if($data) {
            $info = $data['qy_id'].'-'.$data['name'];
        } else {
            $info = '';
        }
        return api_output(0,$info);
    }

    /**
     * 获取标签组详情
     * @author lijie
     * @date_time 2021/03/18
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLabelGroupInfo()
    {
        $label_group_id = $this->request->post('label_group_id',0);
        if(!$label_group_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        $service_send_qywx_massage = new VillageQywxMessageService();
        $data = $service_send_qywx_massage->getLabelGroupInfo(['label_group_id'=>$label_group_id],true);
        return api_output(0,$data);
    }

    /**
     * 添加标签组
     * @author lijie
     * @date_time 2021/03/18
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addLabelGroup()
    {
        $village_id = $this->adminUser['village_id'];
        $property_id = $this->adminUser['property_id'];
        $login_role = $this->login_role;
        $label_group_name = $this->request->post('label_group_name','');
        $add_time = time();
        if (mb_strlen($label_group_name)>15) {
            return api_output_error(1001,'标签组名称最多15个字符');
        }
        if (in_array($login_role,$this->propertyRole)) {
            $type = 2;//物业
            $param_id = $property_id;
            $add_type = 2;
            $where['property_id'] = $param_id;
            $postDatas['property_id'] = $param_id;
        } else {
            $type = 1;//小区
            $param_id = $village_id;
            $add_type = 1;
            $where['village_id'] = $param_id;
            $postDatas['village_id'] = $param_id;
            $service_house_village = new HouseVillageService();
            $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$param_id],'property_id');
            $postDatas['property_id'] = $village_info['property_id'];
        }
        $where['label_group_name'] = $label_group_name;
        $service_send_qywx_massage = new VillageQywxMessageService();
        $info = $service_send_qywx_massage->getLabelGroupInfo($where);
        if($info) {
            return api_output_error(1001,'该小区下已存在次标签组');
        }
        $postDatas['label_group_name'] = $label_group_name;
        $postDatas['add_type'] = $add_type;
        $postDatas['add_time'] = $add_time;
        try{
            $res = $service_send_qywx_massage->addLabelGroup($postDatas);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,[]);
    }

    /**
     * 添加标签
     * @author lijie
     * @date_time 2021/03/18
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addLabel()
    {
        $village_id = $this->adminUser['village_id'];
        $property_id = $this->adminUser['property_id'];
        $login_role = $this->login_role;
        if (in_array($login_role,$this->propertyRole)) {
            $type = 2;//物业
            $add_type = 2;
            $param_id = $property_id;
            $where['property_id'] = $param_id;
        } else {
            $type = 1;//小区
            $add_type = 1;
            $param_id = $village_id;
            $where['village_id'] = $param_id;
            $service_house_village = new HouseVillageService();
            $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$param_id],'property_id');
        }
        $label_name = $this->request->post('label_name','');
        $label_group_id = $this->request->post('label_group_id',0);
        if(empty($label_group_id) || empty($label_name)) {
            return api_output_error(1001,'缺少必传参数');
        }
        if(mb_strlen($label_name)>15) {
            return api_output_error(1001,'标签名不能大于15个字符');
        }
        $where['label_name'] = $label_name;
        $label_name_arr = explode(' ',$label_name);
        $add_time = time();
        $service_send_qywx_massage = new VillageQywxMessageService();
        foreach ($label_name_arr as $v){
            $info = $service_send_qywx_massage->getLabelInfo($where);
            if($info) {
                continue;
            }
            $postDatas['label_name'] = $v;
            if (mb_strlen($v)>15) {
                return api_output_error(1001,'标签名称最多15个字符');
                continue;
            }
            $postDatas['label_group_id'] = $label_group_id;
            $postDatas['add_type'] = $add_type;
            $postDatas['add_time'] = $add_time;
            if($type == 1){
                $postDatas['village_id'] = $param_id;
                $postDatas['property_id'] = $village_info['property_id'];
            }else{
                $postDatas['property_id'] = $param_id;
            }
            $res = $service_send_qywx_massage->addLabel($postDatas);
        }
        return api_output(0,[]);
    }

    /**
     * 群发消息内容
     * @author lijie
     * @date_time 2021/03/18
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMessageContent()
    {
        $message_id = $this->request->post('message_id',0);
        if(!$message_id) {
            return api_output_error(1001,'必传参数缺失');
        }
        $service_send_qywx_massage = new VillageQywxMessageService();
        try{
            $data = $service_send_qywx_massage->getMessageContent(['message_id'=>$message_id]);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 客户列表
     * @author lijie
     * @date_time 2021/03/19
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getQywxContactUser()
    {
        $village_id = $this->adminUser['village_id'];
        $property_id = $this->adminUser['property_id'];
        $login_role = $this->login_role;
        $where = [];
        if (in_array($login_role,$this->propertyRole)) {
            $param_id = $property_id;
            $where[] = ['u.property_id','=',$param_id];
        } else {
            $param_id = $village_id;
            $where[] = ['u.village_id','=',$param_id];
        }
        $wid = $this->request->post('wid','');
        $tags_name = $this->request->post('tags','');
        $custom_owner = $this->request->post('custom_owner',1);
        $where[] = ['u.status','=',1];
        if($custom_owner == 2) {
            $where[] = ['u.UserID','in',$wid];
        }
        if($tags_name) {
            $where[] = ['l.name','in',$tags_name];
        }
        $service_send_qywx_massage = new VillageQywxMessageService();
        $data = $service_send_qywx_massage->getContactUserList($where,'u.ExternalUserID',0);
        return api_output(0,$data);
    }

    /**
     * 添加企业微信群发
     * @author lijie
     * @date_time 2021/03/20
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addQywxMessage()
    {
        $village_id = $this->adminUser['village_id'];
        $property_id = $this->adminUser['property_id'];
        $login_role = $this->login_role;
        if (in_array($login_role,$this->propertyRole)) {
            $type = 2;//物业
            $param_id = $property_id;
        } else {
            $type = 1;//小区
            $param_id = $village_id;
            $service_house_village = new HouseVillageService();
            $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$param_id],'property_id');
        }
        /*$service_house_village = new HouseVillageService();
        $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$village_id],'property_id');*/
        $message_name = $this->request->post('message_name','');
        $send_type = $this->request->post('send_type',1);
        $external_userid = $this->request->post('external_userid',[]);
        $sender = $this->request->post('sender',[]);
        $send_status = $this->request->post('send_status',3);
        $send_time = $this->request->post('send_time',1);
        $message_send_time = $this->request->post('message_send_time',1);
        $custom_owner = $this->request->post('custom_owner',1);
        $content_txt = $this->request->post('content_txt','');
        $file_url = $this->request->post('file_url','');
        $pic_link = $this->request->post('pic_link','');
        $message_type = $this->request->post('message_type');
        if($custom_owner == 1 && $send_type !=3){
            $service_house_worker = new HouseWorkerService();
            if($type == 1) {
                $where[] = ['village_id','=',$param_id];
            } else {
                $where[] = ['property_id','=',$param_id];
            }
            $where[] = ['status','=',1];
            $where[] = ['qy_id','<>',''];
            $where[] = ['is_del','=',0];
            $sender = $service_house_worker->getQyWorker($where,'qy_id');
            if(empty($sender)) {
                return api_output_error(1001,'没有所属客户');
            }
        }else{
            if(empty($sender)) {
                return api_output_error(1001,'没有所属客户');
            } else {
                $sender = implode(',',$sender);
            }
        }
        $nowTime = time();
        if($message_send_time != 1) {
            $message_send_time = strtotime($message_send_time);
        } else {
            $message_send_time = $nowTime;
        }
        if($send_time == 2 && ($message_send_time == 1 || $message_send_time <= $nowTime)){
            return api_output_error(1001,'请选择群发时间且群发时间不能小于当前时间');
        }
        if($send_type == 1 && empty($external_userid)) {
            return api_output_error(1001,'没有群发成员');
        }
        if(!empty($external_userid)) {
            $external_userid = implode(',',$external_userid);
        }
        if(empty($message_name)) {
            return api_output_error(1001,'缺少消息名称');
        }
        $service_send_qywx_massage = new VillageQywxMessageService();
        $message_info = $service_send_qywx_massage->getQywxMessageInfo(['message_name'=>$message_name,'is_del'=>0]);
        if($message_info) {
            return api_output_error(1001,'消息名称不可重复');
        }
        $postDatas['message_name'] = $message_name;
        if($type == 1) {
            $postDatas['property_id'] = $village_info['property_id'];
            $postDatas['village_id'] = $param_id;
        }else{
            $postDatas['property_id'] = $param_id;
        }
        $postDatas['send_type'] = $send_type;
        $postDatas['sender'] = $sender;
        $postDatas['external_userid'] = $external_userid?$external_userid:'';
        $postDatas['send_status'] = $send_status;
        $postDatas['send_time'] = $message_send_time;
        $postDatas['create_time'] = time();
        $postDatas['add_type'] = $type;
        try{
            $message_id = $service_send_qywx_massage->addQywxMessage($postDatas);
            if($send_type == 3){
                if($message_type == 1){
                    $service_send_qywx_massage->addMessageDetail(['message_id'=>$message_id,'type'=>1,'content'=>$content_txt,'add_time'=>time()]);
                }elseif($message_type == 2){
                    $service_send_qywx_massage->addMessageDetail(['message_id'=>$message_id,'type'=>2,'content'=>$file_url,'add_time'=>time()]);
                }elseif ($message_type == 3){
                    $service_send_qywx_massage->addMessageDetail(['message_id'=>$message_id,'type'=>4,'content'=>$file_url,'add_time'=>time()]);
                }else{
                    $service_send_qywx_massage->addMessageDetail(['message_id'=>$message_id,'type'=>5,'content'=>$file_url,'add_time'=>time()]);
                }
            }else{
                if(!empty($content_txt)) {
                    $service_send_qywx_massage->addMessageDetail(['message_id'=>$message_id,'type'=>1,'content'=>$content_txt,'add_time'=>time()]);
                }
                if(!empty($file_url)) {
                    $service_send_qywx_massage->addMessageDetail(['message_id'=>$message_id,'type'=>2,'content'=>$file_url,'add_time'=>time()]);
                }
                if(!empty($pic_link)) {
                    $service_send_qywx_massage->addMessageDetail(['message_id'=>$message_id,'type'=>3,'content'=>$pic_link['title'],'share_img'=>$pic_link['share_img'],'share_url'=>$pic_link['content'],'add_time'=>time()]);
                }
             }
            $service_send_qywx_massage->sendMessage();
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,[]);
    }

    /**
     * 用户群发记录
     * @author lijie
     * @date_time 2021/03/22
     * @return \json
     */
    public function getMessageRecord()
    {
        $village_id = $this->adminUser['village_id'];
        $page = $this->request->post('page',1);
        $enterprise_staff = $this->request->post('enterprise_staff',[]);
        $name = $this->request->post('name','');
        if(!empty($enterprise_staff)){
            $enterprise_staff = array_filter($enterprise_staff);
            if(!empty($enterprise_staff)){
                $where[] = ['r.user_id','in',$enterprise_staff];
                $where2[] = ['user_id','in',$enterprise_staff];
            }
        }
        $message_id = $this->request->post('message_id',0);
        if(!$message_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        $limit = 10;
        $service_send_qywx_massage = new VillageQywxMessageService();
        if($name){
            $where[] = ['w.name','=',$name];
            $where2[] = ['name','=',$name];
        }
        $where[] = ['r.message_id','=',$message_id];
        $where2[] = ['message_id','=',$message_id];
        $field = 'r.send_time,r.status,w.name,u.avatar';
        $data = $service_send_qywx_massage->getMessageRecord($where,$field,$page,$limit,'id DESC');
        $count = $service_send_qywx_massage->getMessageRecordCount($where2);
        $arrived = $service_send_qywx_massage->getMessageRecordCount(['message_id'=>$message_id,'status'=>1]);
        $no_arrived = $service_send_qywx_massage->getMessageRecordCount(['message_id'=>$message_id,'status'=>0]);
        $not_friend = $service_send_qywx_massage->getMessageRecordCount(['message_id'=>$message_id,'status'=>2]);
        $unknow_reason = $service_send_qywx_massage->getMessageRecordCount(['message_id'=>$message_id,'status'=>3]);
        $res['list'] = $data;
        $res['count'] = $count;
        $res['total_limit'] = $limit;
        $res['arrived'] = $arrived;
        $res['no_arrived'] = $no_arrived;
        $res['not_friend'] = $not_friend;
        $res['unknow_reason'] = $unknow_reason;
        return api_output(0,$res);
    }

    /**
     * 成员确认
     * @author lijie
     * @date_time 2021/03/22
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getStaffConfirm()
    {
        $village_id = $this->adminUser['village_id'];
        $property_id = $this->adminUser['property_id'];
        $login_role = $this->login_role;
        if (in_array($login_role,$this->propertyRole)) {
            $type = 2;//物业
            $param_id = $property_id;
        } else {
            $type = 1;//小区
            $param_id = $village_id;
            $service_house_village = new HouseVillageService();
            $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$param_id],'property_id');
        }
        $message_id = $this->request->post('message_id',0);
        $page = $this->request->post('page',1);
        $name = $this->request->post('name','');
        $limit = 10;
        if(!$message_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        $service_send_qywx_massage = new VillageQywxMessageService();
        if($name){
            if($type == 1) {
                $where = [['name','=',$name],['is_del','=',0],['village_id','=',$param_id],['property_id','=',$village_info['property_id']],['qy_id','<>','']];
            } else {
                $where = [['name','=',$name],['is_del','=',0],['property_id','=',$param_id],['qy_id','<>','']];
            }
            $service_house_worker = new HouseWorkerService();
            $worker_info = $service_house_worker->getOneWorker($where,'qy_id');
            if($worker_info) {
                $where_record['user_id'] = $worker_info['qy_id'];
            } else {
                $where_record['user_id'] = '';
            }
        }
        $where_record['message_id'] = $message_id;
        $data = $service_send_qywx_massage->getStaffConfirm($where_record,$message_id,$page,$limit,true);
        $count = $service_send_qywx_massage->getStaffConfirmCount($where_record);
        $res['list'] = $data;
        $res['count'] = $count;
        return api_output(0,$res);
    }

    public function sendMessage()
    {
        $service_send_qywx_massage = new VillageQywxMessageService();
        $where[] = ['send_status','in','2,3,4'];
        $where[] = ['is_del','=','0'];
        $data = $service_send_qywx_massage->sendMessage($where,true,0,0,'id DESC');
    }
}