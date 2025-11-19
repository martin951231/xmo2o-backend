<?php
/**
 * @author : liukezhu
 * @date : 2021/5/10
 */
namespace app\community\controller\village_api;


use app\community\controller\CommunityBaseController;
use app\community\model\db\WorkMsgAuditInfoSensitive;
use app\community\model\service\ViolationRemindService;
use app\community\model\db\HouseWorker;


class ViolationRemindController extends CommunityBaseController{

    /**
     * 获取员工、群聊违规列表
     * @author: liukezhu
     * @date : 2021/5/10
     * @return \json
     */
    public function getViolationRemindList(){
        $type= $this->request->param('type','','int');
        $send_type= $this->request->param('send_type','','int');
        $title= $this->request->param('title');
        $staff_id= $this->request->param('staff_id');
        $group_id= $this->request->param('group_id');
        $page = $this->request->param('page','1','int');
        $village_id = $this->adminUser['village_id'];
        $property_id =  $this->adminUser['property_id'];
        $serviceViolationRemindService = new ViolationRemindService();
        $login_role = $this->login_role;
        if (in_array($login_role,$this->propertyRole)) {
            $from_type = 2;//物业
            $from_id = $property_id;
        } else {
            $from_type = 1;//小区
            $from_id = $village_id;
        }
        if(!$from_id)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        try{
            $param['send_type'] = $send_type;
            $param['from_id'] = $from_id;
            $param['from_type'] = $from_type;
            $param['page'] = $page;
            $param['uid']  = intval($this->_uid);
            if($type == 0){
                $param['staff_id'] = $staff_id;
                $list = $serviceViolationRemindService->getStaffList($param,$village_id,$property_id);
            }else{
                if(!empty($title)){
                    $param['title']=trim($title);
                }
                $param['group_id'] = $group_id;
                $list = $serviceViolationRemindService->getViolationGroupList($param,$village_id);
            }
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }


    /**
     * 获取敏感词
     * @author: liukezhu
     * @date : 2021/5/11
     * @return \json
     */
    public function choiceSensitive(){
        $serviceViolationRemindService = new ViolationRemindService();
        try{
            $where[]=[ 'village_id','=',$this->adminUser['village_id']];
            $where[]=[ 'status','=',1];
            $list = $serviceViolationRemindService->getSensitives($where);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 添加违规员工
     * @author: liukezhu
     * @date : 2021/5/11
     * @return \json
     */
    public function addViolationStaff(){
        $staff_txt = $this->request->param('staff_id');
        $choice_remind = $this->request->param('choice_remind');
        $sensitive_id = $this->request->param('sensitive_id');
        $appoint_staff_id = $this->request->param('appoint_staff_id');
        if (empty($staff_txt)){
            return api_output(1001,[],'请选择员工！');
        }
        if (empty($choice_remind)){
            return api_output(1001,[],'请选择提醒通知成员！');
        }
        if (empty($sensitive_id)){
            return api_output(1001,[],'请选择敏感词！');
        }
        $serviceViolationRemindService = new ViolationRemindService();
        $staff_id=$staff_info=[];
        foreach ($staff_txt as $v){
            $str=explode('-',$v);
            $staff_id[]=$str[0];
            $staff_info[$str[0]]=$str[1];
        }
        if(empty($staff_id)){
            return api_output(1001,[],'缺少必要参数！');
        }
        $param=array(
            'property_id'=>$this->adminUser['property_id'],
            'village_id' => $this->adminUser['village_id'],
            'staff_id'=>$staff_id,
            'choice_remind'=>$choice_remind,
            'sensitive_id'=>$sensitive_id,
        );
        if($choice_remind == 2){
            if (empty($appoint_staff_id)){
                return api_output(1001,[],'请选择指定成员提醒！');
            }
            $param['appoint_staff_id']=$appoint_staff_id;
        }
        try{
            if(count($param['sensitive_id']) > 5){
                return api_output(1001,[],'最多能选择五个敏感词！');
            }
            $rr=$serviceViolationRemindService->checkViolationStaff(['village_id'=>$param['village_id'],'staff_id'=>$staff_id]);
            if($rr){
                return api_output(1001,[],'该【'.$rr['staff_name'].'】已添加过敏感词！');
            }
            $where[]=[ 'village_id','=',$param['village_id']];
            $where[]=[ 'status','=',1];
            $where[]=[ 'id','in',$param['sensitive_id']];
            $serviceSensitives = $serviceViolationRemindService->getSensitives([],$where);
            if(empty($serviceSensitives)){
                return api_output(1001,[],'敏感词不存在！');
            }
            if($choice_remind == 1){
                //对应部门负责人
                $rr=$serviceViolationRemindService->checkStaff(['staff_id'=>$staff_id,'village_id' => $this->adminUser['village_id']]);
                if(empty($rr)){
                    return api_output(1001,[],'所选部门成员暂无对应部门负责人！');
                }
                $staff_id_=array_column($rr,'staff_id');
                foreach ($staff_info as $k=>$v){
                    if(!in_array($k,$staff_id_)){
                        return api_output(1001,[],'该【'.$v.'】暂无对应部门负责人！');
                    }
                }
                $param['rr']=$rr;
            }
            $param['sensitives_info']=$serviceSensitives;
            $id=$serviceViolationRemindService->addViolationStaff($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($id<1){
            return api_output_error(1001,'添加失败');
        }else{
            return api_output(0,$id);
        }
    }


    /**
     * 显示编辑违规员工
     * @author: liukezhu
     * @date : 2021/5/11
     */
    public function editViolationStaff(){
        $id = $this->request->param('id','','intval');
        if (empty($id)){
            return api_output(1001,[],'id不能为空！');
        }
        $serviceViolationMonitor = new ViolationRemindService();
        try{
            $param=array(
                'village_id' => $this->adminUser['village_id'],
                'id'=>$id
            );
            $id=$serviceViolationMonitor->editViolationStaff($param);

        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if(!$id){
            return api_output_error(1001,'数据不存在');
        }else{
            return api_output(0,$id);
        }
    }

    /**
     * 提交编辑违规员工
     * @author: liukezhu
     * @date : 2021/5/12
     * @return \json
     */
    public function subViolationStaff(){
        $id= $this->request->param('id');
        $status= $this->request->param('status');
        if (!isset($id)){
            return api_output(1001,[],'缺少必要参数！');
        }
        if (!isset($status)){
            return api_output(1001,[],'请选择状态！');
        }
        $serviceViolationRemindService = new ViolationRemindService();
        try{
            $param=array(
                'village_id' => $this->adminUser['village_id'],
                'id'=>$id,
                'status'=>$status,
            );
            $id=$serviceViolationRemindService->editViolationStaff($param,$id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($id<1){
            return api_output_error(1001,'编辑失败');
        }else{
            return api_output(0,$id);
        }
    }

    //删除违规员工
    public function delViolationStaff(){
        $id = $this->request->param('id','','trim');
        if (empty($id)){
            return api_output(1001,[],'id不能为空！');
        }
        $serviceViolationMonitor = new ViolationRemindService();
        try{
            $param=array(
                'village_id' => $this->adminUser['village_id'],
                'id'=>$id
            );
            $id=$serviceViolationMonitor->delViolationStaff($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($id<1){
            return api_output_error(1001,'删除失败');
        }else{
            return api_output(0,$id);
        }
    }


    /**
     * 群聊行为
     * @author: liukezhu
     * @date : 2021/5/12
     * @return \json
     */
    public function getGroupAction(){
        $serviceViolationMonitor = new ViolationRemindService();
        $data=$serviceViolationMonitor->violation_action;
        $list=[];
        foreach ($data as $k=>$v){
            $list[]=array(
                'key'=>$k,
                'value'=>$v
            );
        }
        return api_output(0,$list);
    }

    /**
     * 新增群聊配置
     * @author: liukezhu
     * @date : 2021/5/12
     */
    public function addViolationGroup(){
        $rule_name= $this->request->param('rule_name', '', 'trim');
        $group_id= $this->request->param('group_id');
        $groupAction= $this->request->param('action');
        $sensitive_id= $this->request->param('sensitive_id');
        $staff_type= $this->request->param('staff_type');
        $appoint_staff_id= $this->request->param('appoint_staff_id');
        if (empty($rule_name)){
            return api_output(1001,[],'请输入规则名称！');
        }
        if (empty($group_id)){
            return api_output(1001,[],'请选择适用业主群！');
        }
        if (empty($groupAction)){
            return api_output(1001,[],'请选择提醒行为！');
        }
        if (empty($sensitive_id) && in_array(8,$groupAction)){
            return api_output(1001,[],'请选择敏感词！');
        }
        if(count($staff_type) > 1){
            if(empty($appoint_staff_id)){
                return api_output(1001,[],'请选择成员！');
            }
        }
        $serviceViolationRemindService = new ViolationRemindService();
        $param=array(
            'property_id'=>$this->adminUser['property_id'],
            'village_id' => $this->adminUser['village_id'],
            'rule_name'=>trim($rule_name),
            'group_id'=>$group_id,
            'groupAction'=>$groupAction,
            'sensitive_id'=>$sensitive_id,
            'staff_type'=>$staff_type,
            'appoint_staff_id'=>$appoint_staff_id
        );
        try{
            $serviceSensitives=[];
            if(!empty($param['sensitive_id'])){
                if(count($param['sensitive_id']) > 5){
                    return api_output(1001,[],'最多能选择五个敏感词！');
                }
                $where[]=[ 'village_id','=',$param['village_id']];
                $where[]=[ 'status','=',1];
                $where[]=[ 'id','in',$param['sensitive_id']];
                $serviceSensitives = $serviceViolationRemindService->getSensitives([],$where);
                if(empty($serviceSensitives)){
                    return api_output(1001,[],'敏感词不存在！');
                }
            }
            $param['sensitives_info']=$serviceSensitives;
            $id=$serviceViolationRemindService->addViolationGroup($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($id<1){
            return api_output_error(1001,'添加失败');
        }else{
            return api_output(0,$id);
        }
    }


    /**
     * 回显群聊数据
     * @author: liukezhu
     * @date : 2021/5/12
     */
    public function editViolationGroup(){
        $id = $this->request->param('id','','intval');
        if (empty($id)){
            return api_output(1001,[],'id不能为空！');
        }
        $serviceViolationMonitor = new ViolationRemindService();
        try{
            $param=array(
                'village_id' => $this->adminUser['village_id'],
                'id'=>$id
            );
            $id=$serviceViolationMonitor->editViolationGroup($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if(!$id){
            return api_output_error(1001,'数据不存在');
        }else{
            return api_output(0,$id);
        }
    }

    /**
     * 提交编辑群聊数据
     * @author: liukezhu
     * @date : 2021/5/12
     * @return \json
     */
    public function subViolationGroup(){
        $id= $this->request->param('id');
        $groupAction= $this->request->param('action');
        $sensitive_id= $this->request->param('sensitive_id');
        $status= $this->request->param('status');
        if (!isset($id)){
            return api_output(1001,[],'缺少必要参数！');
        }
        if (!isset($status)){
            return api_output(1001,[],'请选择状态！');
        }
        if (empty($groupAction)){
            return api_output(1001,[],'请选择提醒行为！');
        }
        if (empty($sensitive_id) && in_array(8,$groupAction)){
            return api_output(1001,[],'请选择敏感词！');
        }
        $serviceViolationRemindService = new ViolationRemindService();
        try{
            $param=array(
                'village_id' => $this->adminUser['village_id'],
                'id'=>$id,
                'status'=>$status,
                'action'=>$groupAction,
                'sensitive_id'=>$sensitive_id
            );
            $serviceSensitives=[];
            if(!empty($param['sensitive_id'])){
                if(count($param['sensitive_id']) > 5){
                    return api_output(1001,[],'最多能选择五个敏感词！');
                }
                $where[]=[ 'village_id','=',$param['village_id']];
                $where[]=[ 'status','=',1];
                $where[]=[ 'id','in',$param['sensitive_id']];
                $serviceSensitives = $serviceViolationRemindService->getSensitives([],$where);
                if(empty($serviceSensitives)){
                    return api_output(1001,[],'敏感词不存在！');
                }
            }
            $param['sensitives_info']=$serviceSensitives;
            $id=$serviceViolationRemindService->editViolationGroup($param,$id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($id<1){
            return api_output_error(1001,'编辑失败');
        }else{
            return api_output(0,$id);
        }
    }

    /**
     * 删除群聊配置
     * @author: liukezhu
     * @date : 2021/5/12
     * @return \json
     */
    public function delViolationGroup(){
        $id = $this->request->param('id','','trim');
        if (empty($id)){
            return api_output(1001,[],'id不能为空！');
        }
        $serviceViolationMonitor = new ViolationRemindService();
        try{
            $param=array(
                'village_id' => $this->adminUser['village_id'],
                'id'=>$id
            );
            $id=$serviceViolationMonitor->delViolationGroup($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($id<1){
            return api_output_error(1001,'删除失败');
        }else{
            return api_output(0,$id);
        }
    }

    public function test(){
        $serviceViolationRemindService = new ViolationRemindService();
        $serviceViolationRemindService->staffRemind();
    }
}