<?php
/**
 * @author : liukezhu
 * @date : 2022/2/25
 */

namespace app\community\controller\property_api;
use app\community\controller\CommunityBaseController;
use app\community\model\service\HousePropertyVillageWorkerService;
use app\community\model\service\KefuService;
use app\community\model\service\PropertyAdminService;
use app\community\model\service\AdminLoginService;
use app\traits\CommonLogTraits;
use app\traits\house\AdminLoginTraits;
class PropertyWorkerController extends CommunityBaseController
{
    use CommonLogTraits;
    use AdminLoginTraits;
    public function savePropertyWorkerInfo()
    {
        $property_id = $this->adminUser['property_id'];
        if ($property_id<1) {
            return api_output(1002, [], '登录超时！');
        }
        $account = $this->request->post('account', '', 'trim');
        $account = htmlspecialchars($account, ENT_QUOTES);
        $pwd = $this->request->post('pwd', '', 'trim');
        $pwd = $pwd ? strval($pwd):'';
        $pwd=trim($pwd);
        $id_card = $this->request->post('id_card', '', 'trim');
        $id_card = htmlspecialchars($id_card, ENT_QUOTES);
        $job_create_time = $this->request->post('job_create_time', '', 'trim');
        $job_create_time = htmlspecialchars($job_create_time, ENT_QUOTES);
        $job_number = $this->request->post('job_number', '', 'trim');
        $job_number = htmlspecialchars($job_number, ENT_QUOTES);
        $name = $this->request->post('name', '', 'trim');
        $name = htmlspecialchars($name, ENT_QUOTES);
        $phone = $this->request->post('phone', '', 'trim');
        $phone = htmlspecialchars($phone, ENT_QUOTES);
        $remarks = $this->request->post('remarks', '', 'trim');
        $remarks = htmlspecialchars($remarks, ENT_QUOTES);
        $hw_id = $this->request->param('hw_id', 0, 'int');
        $gender = $this->request->param('gender', 0, 'int');   //1男2女 0未知
        
        try {
            if($hw_id<1 && empty($pwd)){
                $pwd='123abc';
            }
            if(empty($account)){
                return api_output_error(1001, '登录账号不能为空！');
            }
            if(empty($phone)){
                return api_output_error(1001, '手机号不能为空！');
            }
            if(empty($name)){
                return api_output_error(1001, '姓名不能为空！');
            }
            $wdata=array('account'=>$account,'name'=>$name,'phone'=>$phone,'job_number'=>$job_number,'id_card'=>$id_card,'remarks'=>$remarks,'gender'=>$gender);
            if($job_create_time){
                $job_create_time=strtotime($job_create_time);
                $job_create_time=$job_create_time> 0 ? $job_create_time:0;
                $wdata['job_create_time']=$job_create_time;
            }
            $housePropertyVillageWorkerService=new  HousePropertyVillageWorkerService();
            if($hw_id>0){
                if(!empty($pwd)){
                    $wdata['pwd']= md5($pwd);
                }
                $housePropertyVillageWorkerService->editPropertyVillageWorker($wdata,$hw_id,$property_id);
                
                $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
                $queuData = [
                    'logData' => [
                        'tbname' => '物业设置-人员管理',
                        'table'  => 'house_property_village_worker',
                        'client' => '物业后台',
                        'trigger_path' => '人员管理->编辑',
                        'trigger_type' => $this->getUpdateNmae(),
                        'addtime'      => time(),
                        'village_id'   => 0,
                        'property_id' =>$property_id,
                        'op_id'   => $parseInfo['op_id'],
                        'op_type'   => $parseInfo['op_type'],
                        'op_name'   => $parseInfo['op_name'],
                    ],
                    'newData' => $wdata,
                    'oldData' => ''
                ];
                $this->laterLogInQueue($queuData);
            }else{
                $wdata['pwd']=$pwd ? md5($pwd):'';
                $hw_id=$housePropertyVillageWorkerService->addPropertyVillageWorker($wdata,$property_id);
                $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
                $queuData = [
                    'logData' => [
                        'tbname' => '物业设置-人员管理',
                        'table'  => 'house_property_village_worker',
                        'client' => '小区后台',
                        'trigger_path' => '人员管理->添加',
                        'trigger_type' => $this->getAddLogName(),
                        'addtime'      => time(),
                        'village_id'   => 0,
                        'property_id' =>$property_id,
                        'op_id'   => $parseInfo['op_id'],
                        'op_type'   => $parseInfo['op_type'],
                        'op_name'   => $parseInfo['op_name'],
                    ],
                    'newData' => $wdata,
                    'oldData' => ''
                ];
                $this->laterLogInQueue($queuData);
            }
            return api_output(0, ['hw_id'=>$hw_id,'msg'=>'操作成功！']);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    public function getOnePropertyWorkerInfo(){
        $hw_id = $this->request->param('hw_id', 0, 'intval');
        $property_id=$this->adminUser['property_id'];
        if ($hw_id<1) {
            return api_output(1000, [], '缺少必要参数！');
        }
        try {
            $where=array(['hw_id','=',$hw_id]);
            $where[] = ['property_id', '=', $property_id];
            $where[] = ['del_time','<',1];
            $housePropertyVillageWorkerService=new  HousePropertyVillageWorkerService();
            $field = 'hw_id,account,gender,id_card,property_id,phone,name,status,job_number,job_create_time,remarks,openid,nickname';
            $res = $housePropertyVillageWorkerService->getOnePropertyVillageWorker($where,$field);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res);
    }
    public function getPropertyWorkerList()
    {
        $page = $this->request->param('page', 0, 'int');
        $phone = $this->request->post('phone', '', 'trim');
        $xname = $this->request->post('xname', '', 'trim');
        $status_v = $this->request->post('status_v', '', 'trim');
        $group_id = $this->request->post('group_id', 0, 'trim');  //property_group 表id
        $property_id=$this->adminUser['property_id'];
        if ($property_id<1) {
            return api_output(1002, [], '登录超时！');
        }
        try {
            $where=array(['property_id', '=', $this->adminUser['property_id']]);
            $where[] = ['del_time','<',1];
            if($phone){
                $where[] = array('phone' ,'like','%'.$phone.'%' );
            }
            if($xname){
                $where[] = array('name' ,'like', '%'.$xname.'%');
            }
            if($status_v!==''){
                $where[] = array('status' ,'=', $status_v);
            }
            $housePropertyVillageWorkerService=new  HousePropertyVillageWorkerService();
            $field = '*';
            $page_size=10;
            $res = $housePropertyVillageWorkerService->getPropertyWorkerList($where, $field, $page, $page_size);
            
            $res['propertyGroup']='';
            $res['property_name']=isset($this->adminUser['property_name']) ? $this->adminUser['property_name']:'';
            $res['village_name']=isset($this->adminUser['village_name']) ? $this->adminUser['village_name']:'';
            if($group_id && $group_id>0){
               $propertyGroup=$housePropertyVillageWorkerService->getPropertyGroupInfo($group_id,$property_id);
               $res['propertyGroup']=$propertyGroup;
            }
            $res['login_role']=$this->login_role;
            $propertyAdminService= new PropertyAdminService();
            $role_add=1;
            $role_edit=1;
            $role_disable=1;
            $role_del=1;
            $adminLoginService = new AdminLoginService();
            if($this->login_role==$adminLoginService::PROPERTY_USER_LOGIN){
                $role_add=$propertyAdminService->checkPermissionMenu('property_2_4_1', $this->adminUser);
                $role_edit=$propertyAdminService->checkPermissionMenu('property_2_4_2', $this->adminUser);
                $role_disable=$propertyAdminService->checkPermissionMenu('property_2_4_3', $this->adminUser);
                $role_del=$propertyAdminService->checkPermissionMenu('property_2_4_4', $this->adminUser);
            }
            $res['role_add']=$role_add;
            $res['role_edit']=$role_edit;
            $res['role_disable']=$role_disable;
            $res['role_del']=$role_del;
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res);
    }
    public function disableWorkerAccount(){
        $hw_id = $this->request->param('hw_id', 0, 'intval');
        if ($hw_id<1) {
            return api_output(1000, [], '缺少必要参数！');
        }
        $property_id=$this->adminUser['property_id'];
        if ($property_id<1) {
            return api_output(1002, [], '登录超时！');
        }
        try {
            $housePropertyVillageWorkerService=new  HousePropertyVillageWorkerService();
            $res = $housePropertyVillageWorkerService->disableWorkerAccount($hw_id,$property_id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res);
    }
    /**
     * 删除员工
     */
    public function delPropertyWorker()
    {
        $hw_id = $this->request->param('hw_id', 0, 'intval');
        if ($hw_id<1) {
            return api_output(1000, [], '缺少必要参数！');
        }
        $property_id=$this->adminUser['property_id'];
        if ($property_id<1) {
            return api_output(1002, [], '登录超时！');
        }
        try {
            $housePropertyVillageWorkerService=new  HousePropertyVillageWorkerService();
            $res = $housePropertyVillageWorkerService->delPropertyWorker($hw_id,$property_id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res);
    }

    public function getPropertySingleKeepers()
    {
        $village_ids = $this->request->param('village_ids', ''); //小区ids
        $property_id = $this->adminUser['property_id'];
        if ($property_id < 1) {
            return api_output(1002, [], '登录超时！');
        }
        if (empty($village_ids)) {
            return api_output(0, array('list' => []));
        }
        try {
            $where = array(
                ['village_id', 'in', $village_ids],
                ['single_id', '>', 0],
                ['floor_id', '<', 1],
                ['work_arr', '<>', ''],
            );
            $housekeepers = (new KefuService())->getHousekeepersOfSingle($where);
            return api_output(0, $housekeepers);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
}