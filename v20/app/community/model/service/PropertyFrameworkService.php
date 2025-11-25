<?php
/**
 * @author : liukezhu
 * @date : 2022/2/18
 */

namespace app\community\model\service;

use app\community\model\db\HouseMaintenanWorker;
use app\community\model\db\HouseNewCharge;
use app\community\model\db\HouseNewChargeProject;
use app\community\model\db\HouseNewMeterDirector;
use app\community\model\db\HouseNewRepairCate;
use app\community\model\db\HouseNewRepairDirectorScheduling;
use app\community\model\db\HouseNewRepairWorksOrder;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageCheckauthSet;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseWorker;
use app\community\model\db\PropertyGroup;
use app\community\model\service\workweixin\DepartmentUserService;
use app\community\model\service\workweixin\WorkWeiXinSuiteService;
use customization\customization;
use think\facade\Db;
use app\community\model\db\HouseEnterpriseWxBind;
use app\community\model\db\HouseAdmin;
use app\community\model\db\PropertyAdmin;
use app\community\model\db\ProcessSubPlan;
use app\community\model\service\HouseVillageCheckauthSetService;
use app\community\model\db\HousePropertyVillageWorkerRelation;
use app\traits\WorkWeiXinToJobTraits;

class PropertyFrameworkService
{
    use WorkWeiXinToJobTraits;
    use customization;
    protected $PropertyGroup;
    protected $HouseWorkerService;
    protected $HouseVillage;
    protected $HouseWorker;
    protected $HouseVillageUserBind;
    protected $HouseEnterpriseWxBind;
    protected $HouseProgrammeService;
    protected $HouseVillageLabelService;

    public function __construct()
    {
        $this->PropertyGroup = new PropertyGroup();
        $this->HouseWorkerService=new HouseWorkerService();
        $this->HouseVillage=new HouseVillage();
        $this->HouseWorker =new HouseWorker();
        $this->HouseVillageUserBind = new HouseVillageUserBind();
        $this->HouseEnterpriseWxBind = new HouseEnterpriseWxBind();
        $this->HouseProgrammeService = new HouseProgrammeService();
        $this->HouseVillageLabelService = new HouseVillageLabelService();
    }

    //===================== 共用方法 ==============

    //todo 返回部门类型数据
    public function getDepartmentType(){
        $department_type=[];
        if(!empty($this->HouseWorkerService->worker_name)){
            foreach ($this->HouseWorkerService->worker_name as $k=>$v){
                $department_type[]=[
                    'key'=>$k,
                    'value'=>$v
                ];
            }
        }
        return $department_type;
    }

    //todo 校验组织数据
    public function checkOrganization($param){
        $village_info=[];
        if($param['village_id'] > 0){
            $village_info=$this->HouseVillage->getInfo(['village_id'=>$param['village_id']],'village_id,village_name');
        }
        $data=array();
        if(isset($param['sort'])){
            $data['sort']=$param['sort'] ? intval($param['sort']):0;
        }
        if(in_array($param['group_type'],[1,2])){
            if(!isset($this->HouseWorkerService->worker_name[$param['department_type']])){
                throw new \think\Exception("请选择部门类型");
            }
            if(empty($param['name'])){
                throw new \think\Exception("部门名称不为空");
            }
            $data['name'] = trim($param['name']);
        }
        if (isset($param['qy_id']) && $param['qy_id'] && $param['qy_id'] != '暂无') {
            $wherePropertyGroup = [];
            $wherePropertyGroup[] = ['status', '=', 1];
            $wherePropertyGroup[] = ['is_del', '=', 0];
            $wherePropertyGroup[] = ['create_id', '=', $param['qy_id']];
            $wherePropertyGroup[] = ['property_id', '=', $param['property_id']];
            
            $where = [];
            $where[] = ['bind_type', '=', 0];
            $where[] = ['bind_id', '=', $param['property_id']];
            $dbHouseEnterpriseWxBind = new HouseEnterpriseWxBind();
            $bindInfo = $dbHouseEnterpriseWxBind->getOne($where, 'corpid','pigcms_id DESC');
            if ($bindInfo && !is_array($bindInfo)) {
                $bindInfo = $bindInfo->toArray();
            }
            // todo 如果授权企业微信和服务商相同 可以使用 企业微信自带的 通讯录获取
            if (isset($bindInfo['corpid']) && $bindInfo['corpid']) {
                $wherePropertyGroup[] = ['qy_corpid', '=', $bindInfo['corpid']];
            }
            if (isset($param['id']) && $param['id']) {
                $wherePropertyGroup[] = ['id', '<>', $param['id']];
            }
            $otherInfo = $this->PropertyGroup->getOne($wherePropertyGroup, 'id');
            if (isset($otherInfo['id']) && $otherInfo['id']) {
                throw new \think\Exception("所填写【企微同步部门ID】已经绑定了其他部门");
            }
            
            $params = [
                'property_id' => $param['property_id'],
            ];
            $result = (new WorkWeiXinSuiteService())->getDepartmentSimpleList($params);
            $departmentIds = [];
            if (isset($result['department_id']) && $result['department_id']) {
                foreach ($result['department_id'] as $item) {
                    $departmentIds[] = $item['id'];
                }
            }
            if (! in_array($param['qy_id'], $departmentIds)) {
                throw new \think\Exception("所填写【企微同步部门ID】对应企微不存在");
            }
        }
        switch ($param['group_type']) {
            case 0: //小区
                if(empty($village_info)){
                    throw new \think\Exception("该小区不存在");
                }
                $now_village= $this->PropertyGroup->getOne([
                    ['property_id','=',$param['property_id']],
                    ['village_id','=',$param['village_id']],
                    ['is_del','=',0],
                    ['type','=',0]
                ],'village_id');
                if((!isset($param['property_edit_village'])|| $param['property_edit_village']!=1)&& $now_village && !$now_village->isEmpty()){
                    throw new \think\Exception("该小区已被添加过，不可重复");
                }
                $data['name'] = $village_info['village_name'];
                $data['section_type'] = '';
                break;
            case 1: //小区部门
                $data['section_type'] = $this->HouseWorkerService->worker_name[$param['department_type']];
                $data['section_status'] = $param['department_type'];
                if($param['fid']>0 && (empty($village_info) || isset($village_info['village_id']) || empty($village_info['village_id']))){
                    $whereGroup=array(['id','=',$param['fid']]);
                    if(isset($param['property_id']) && $param['property_id']>0){
                        $whereGroup[]=array('property_id','=',$param['property_id']);
                    }
                    $propertyGroup = $this->PropertyGroup->getOne($whereGroup, '*');
                    if($propertyGroup && !$propertyGroup->isEmpty()){
                        $data['village_id']=$propertyGroup['village_id'] ? $propertyGroup['village_id']:0;
                    }
                }
                break;
            case 2: //物业部门
                $data['village_id'] = '';
                $data['section_type'] = $this->HouseWorkerService->worker_name[$param['department_type']];
                $data['section_status'] = $param['department_type'];
                break;
            default:
                throw new \think\Exception("类型不存在");
        }
        return $data;
    }

    //todo 递归组织
    public function recursionOrganization($fid){
        static $child = [];
        $list=$this->PropertyGroup->getColumn([['fid','=',$fid], ['is_del','=',0]],'id');
        if($list){
            foreach ($list as $v){
                if($v){
                    $child[] = $v;
                    $this->recursionOrganization($v);
                }
            }
        }
        return $child;
    }

    //todo 校验工作人员数据
    public function checkWorkParam($login_type,&$param,$wid=0){
        if(empty($param['account'])){
            return api_output(1001,[],'请输入账号！');
        }
        /*
        if(empty($param['id_card'])){
            throw new \think\Exception('请输入身份证号!');
        }
        */
        if(!empty($param['id_card']) && is_idcard($param['id_card']) == false){
            throw new \think\Exception('请填写正确的身份证号码!');
        }
        $property_group=$this->getPropertyGroup($param['property_id'],$param['department_id'],'village_id,type,section_status');
        $where=array();
        $where[] = ['phone','=',$param['phone']];
        $where[] = ['is_del','=',0];
        if($wid > 0){
            $where[] = ['wid','<>',$wid];
        }
        if($login_type == 1){ //物业部门添加
            $where[] = ['property_id','=',$param['property_id']];
            $where[] = ['village_id','=',0];
        }
        elseif($login_type == 2){ //小区部门添加
            $where[] = ['village_id','=',(empty($property_group['village_id']) ? $param['village_id'] : $property_group['village_id'])];
        }
        $house_worker=$this->HouseWorker->get_one($where,'*');
        if($house_worker ){
           // throw new \think\Exception('该手机号【'.$param['phone'].'】已存在');
        }
        if (isset($param['qy_id']) && $param['qy_id'] && $param['qy_id'] != '暂无') {

            $whereWork = [];
            $whereWork[] = ['status', '=', 1];
            $whereWork[] = ['is_del', '=', 0];
            $whereWork[] = ['qy_id', '=', $param['qy_id']];
            $whereWork[] = ['property_id', '=', $param['property_id']];

            $where = [];
            $where[] = ['bind_type', '=', 0];
            $where[] = ['bind_id', '=', $param['property_id']];
            $dbHouseEnterpriseWxBind = new HouseEnterpriseWxBind();
            $bindInfo = $dbHouseEnterpriseWxBind->getOne($where, 'corpid','pigcms_id DESC');
            if ($bindInfo && !is_array($bindInfo)) {
                $bindInfo = $bindInfo->toArray();
            }
            // todo 如果授权企业微信和服务商相同 可以使用 企业微信自带的 通讯录获取
            if (isset($bindInfo['corpid']) && $bindInfo['corpid']) {
                $whereWork[] = ['qy_corpid', '=', $bindInfo['corpid']];
            }
            if ($wid > 0) {
                $whereWork[] = ['wid', '<>', $wid];
            }
            $otherInfo = $this->HouseWorker->getOne($whereWork, 'wid');
            if (isset($otherInfo['id']) && $otherInfo['id']) {
                throw new \think\Exception("所填写【企微用户账号】已经绑定了其他工作人员");
            }
            $params = [
                'property_id' => $param['property_id'],
            ];
            $result = (new WorkWeiXinSuiteService())->toOpenuserid([$param['qy_id']], $params);
            if (!isset($result['open_userid_list']) || empty($result['open_userid_list'])) {

                //throw new \think\Exception('对应企微不存在此【用户账号】或者【用户账户】不在第三方应用可见范围内');
            } else {
                foreach ($result['open_userid_list'] as $open_userids) {
                    if ($open_userids['userid'] == $param['qy_id']) {
                        $param['qy_open_userid'] = $open_userids['open_userid'];
                    }
                }
            }
        } elseif (isset($param['qy_id']) && $param['qy_id'] == '暂无') {
            $param['qy_id'] = '';
        }
        return $property_group;
    }

    //todo 统一组装返回提示用户信息
    public function getWorkName($user){
        if(isset($user['realname']) && !empty($user['realname'])){
            $str='姓名：'.$user['realname'];
        }elseif (isset($user['user_name']) && !empty($user['user_name'])){
            $str='真实姓名：'.$user['user_name'];
        }elseif (isset($user['account']) && !empty($user['account'])){
            $str='账号名：'.$user['account'];
        }else{
            $str='手机号：'.$user['phone'];
        }
        return $str;
    }

    //todo 校验删除工作人员  $type 1:删除组织 2：删除工作人员
    public function checkWorkDel($param,$ids=[],$type){
        $is_del=false;
        $adminLoginService = (new AdminLoginService());
        if(in_array($param['login_role'],$adminLoginService->propertyUserArr)){//物业 物业普通管理员
            $is_del=true;
        }elseif (in_array($param['login_role'],$adminLoginService->villageUserArr)){//小区 工作人员
            $is_del=true;
        }
        if($is_del){
            $wid=isset($param['user_admin']['wid']) ? intval($param['user_admin']['wid']) : 0;
            $village_id=isset($param['user_admin']['village_id']) ? ($param['user_admin']['village_id']) : 0;;
            if($wid){
                if($type == 1){
                    $where[] = ['wid','=',$wid];
                    $where[] = ['is_del','=',0];
                    $info=$this->HouseWorker->get_one($where,'department_id');
                    if($info && !$info->isEmpty()){
                        if(!empty($param['group_id_all']) && in_array($info['department_id'],$param['group_id_all'])){
                            throw new \think\Exception('当前组织存在登录【'.(self::getWorkName($param['user_admin'])).'】，不可删除！');
                        }
                    }
                    $this->checkWorkRelation($wid,$village_id,2);
                }else{
                    if(in_array($wid,$ids)){
                        throw new \think\Exception('当前登录【'.(self::getWorkName($param['user_admin'])).'】，不可删除！');
                    }
                }
            }
        }
    }
    
    /**
     * $type 1禁用 2删除
    ***/
    public function checkWorkRelation($wid = 0, $village_id = 0, $type = 1)
    {
        if($village_id<1){
            return true;
        }
        $db_repair_director = new HouseNewRepairDirectorScheduling();
        $whereArr = array();
        $typeopt = '禁用';
        if ($type == 2) {
            $typeopt = '删除';
        }
        $whereArr[] = array('cate_id', '>', 0);
        $whereArr[] = ['director_uid', 'FIND IN SET', $wid];
        $director = $db_repair_director->getOne($whereArr);
        $db_repair_cate = new HouseNewRepairCate();
        if ($director && !$director->isEmpty()) {
            $whereArr = array();
            $whereArr[] = array('village_id', '=', $village_id);
            $whereArr[] = ['id', '=', $director['cate_id']];
            $whereArr[] = ['status', '<>', 4];
            $repair_cate = $db_repair_cate->getOne($whereArr);
            if ($repair_cate && !$repair_cate->isEmpty()) {
                throw new \think\Exception("在工单类目为【" . $repair_cate['cate_name'] . "】有此工作人员的负责人数据，不可以" . $typeopt);
            }
        }
        $whereArr = array();
        $whereArr[] = array('village_id', '=', $village_id);
        $whereArr[] = ['uid', '=', $wid];
        $whereArr[] = ['status', '<>', 4];
        $repair_cate = $db_repair_cate->getOne($whereArr);
        if ($repair_cate && !$repair_cate->isEmpty()) {
            throw new \think\Exception("在工单类目是【" . $repair_cate['cate_name'] . "】有此工作人员的负责人数据，不可以" . $typeopt);
        }

        $houseVillageCheckauthSetService = new HouseVillageCheckauthSetService();
        $orderRefundCheckWhere = array('village_id' => $village_id, 'xtype' => 'order_refund_check');
        $userAuthLevel = $houseVillageCheckauthSetService->getOneCheckauthSet($orderRefundCheckWhere);
        if (!empty($userAuthLevel) && !empty($userAuthLevel['check_level'])) {
            $check_wid = 0;
            foreach ($userAuthLevel['check_level'] as $clv) {
                if ($clv['level_wid'] > 0 && ($clv['level_wid']==$wid)) {
                    $check_wid = $clv['level_wid'];
                    break;
                }
            }
            if ($check_wid > 0) {
                throw new \think\Exception("此工作人员在订单退款（作废）审核设置人员中，不可以" . $typeopt);
            }
        }

        $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
        $whereArr = array();
        $whereArr[] = array('village_id', '=', $village_id);
        $whereArr[] = ['worker_id', '=', $wid];
        $whereArr[] = ['event_status', '>=', 20];
        $whereArr[] = ['event_status', '<', 40];
        $works_order = $db_house_new_repair_works_order->getOne($whereArr);
        if ($works_order && !$works_order->isEmpty()) {
            throw new \think\Exception("此工作人员有未办结的工单再处理，不可以" . $typeopt);
        }
        $db_house_new_meter_director = new HouseNewMeterDirector();
        $whereArr = array();
        $whereArr[] = array('village_id', '=', $village_id);
        $whereArr[] = ['worker_id', '=', $wid];
        $whereArr[] = ['status', '<>', 4];
        $meter_director = $db_house_new_meter_director->getOne($whereArr);
        if ($meter_director && !$meter_director->isEmpty()) {
            $db_house_new_meter_project = new HouseNewChargeProject();
            $whereArr = array();
            $whereArr[] = array('village_id', '=', $village_id);
            $whereArr[] = ['id', '=', $meter_director['project_id']];
            $whereArr[] = ['status', '<>', 4];
            $meter_project = $db_house_new_meter_project->getOne($whereArr);
            if ($meter_project && !$meter_project->isEmpty()) {
                throw new \think\Exception("此工作人员是收费项目为【" . $meter_project['name'] . "】的抄表负责人，不可以" . $typeopt);
            }
        }

        $db_charge = new HouseNewCharge();
        $chargeInfo = $db_charge->get_one(['village_id' => $village_id]);
        if ($chargeInfo && !$chargeInfo->isEmpty()) {
            $chargeSet = $chargeInfo->toArray();
            $wids = !empty($chargeSet['wids']) ? json_decode($chargeSet['wids'],1) : array();
            if ($wids && in_array($wid, $wids)) {
                throw new \think\Exception("此工作人员是收费设置里的缴费通知人员，不可以" . $typeopt);
            }
        }

        $dbHouseWorker = new HouseMaintenanWorker();
        $whereArr = array();
        $whereArr[] = array('village_id', '=', $village_id);
        $whereArr[] = ['wid', '=', $wid];
        $whereArr[] = ['is_del_time', '=', 0];
        $data = $dbHouseWorker->get_one($whereArr);
        if (!empty($data)) {
            throw new \think\Exception("此工作人员是设备预警列表中的通知人员，不可以" . $typeopt);
        }
        return true;
    }
    //===================== db查询方法 ==============

    //todo 返回部门数据
    public function getPropertyGroup($property_id,$group_id,$field=true){
        $where[] = ['id','=',$group_id];
        $where[] = ['property_id','=',$property_id];
        $where[] = ['is_del','=',0];
        $rel=$this->PropertyGroup->getOne($where,$field);
        if(!$rel || $rel->isEmpty()){
            throw new \think\Exception("该组织架构不存在");
        }
        $rel=$rel->toArray();
        return $rel;
    }

    /**
     * 获取物业下未绑定分组小区
     * @author: liukezhu
     * @date : 2022/2/21
     * @param $property_id
     * @return mixed
     */
    public function dbPropertyUnboundVillage($property_id){
        $now_property= $this->PropertyGroup->getColumn([
            ['property_id','=',$property_id],
            ['is_del','=',0],
            ['type','=',0]
        ],'village_id');
        $where[]=['status','=',1];
        $where[]=['property_id','=',$property_id];
        if(!empty($now_property)){
            $where[] = ['village_id','not in',$now_property];
        }
        return $this->HouseVillage->getList($where,'village_id,village_name');
    }

    public function getOnePropertyGroup($whereArr,$field=true){
        $rel=$this->PropertyGroup->getOne($whereArr,$field);
        if ($rel && !$rel->isEmpty()) {
            $rel=$rel->toArray();
        }else{
            $rel=array();
        }
        return $rel;
    }
    public function addOnePropertyGroup($dataArr){
        $rel= $this->PropertyGroup->addFind($dataArr);
        return $rel;
    }
    //===================== 业务方法 ==============

    //todo 获取物业、小区组织架构
    public function businessNav($login_type,$property_id,$village_id,$menus=[],$where=array(),$param = []){
        $where=[];
        $where[] = ['is_del','=',0];
        $where[] = ['property_id','=',$property_id];
        $orderby='sort desc,id desc';
        if($login_type==2){
            $where[]=['village_id', '=', $village_id];
        }else if($login_type==1){
            $orderby='type desc,sort desc,id desc';
        }
        
        $dataList=$this->PropertyGroup->getList($where,'id,fid,name,type,village_id',0,10,$orderby);
        if($dataList && !$dataList->isEmpty()){
            $dataList= $dataList->toArray();
        }
        $is_top=false;
        if($login_type == 2 && $dataList){
            $lists01=$lists02=[];
            foreach ($dataList as $v){
                if($v['type'] == 0 && $v['village_id'] == $village_id){
                    $lists01[]=$v;
                    if(!$is_top){
                        $is_top=true;
                    }
                }else{
                    $lists02[]=$v;
                }
            }
            if($lists01){
                $dataList=array_merge($lists01,$lists02);
            }
        }
        $new_organization = [];
        $type=[0=>0, 1=>1, 2=>2, 99=>3];
        $menus_edit=$menus_del=1;
        if($login_type == 2 && !empty($menus)){
            if(!in_array(295,$menus)){ //编辑组织架构
                $menus_edit=0;
            }
            if(!in_array(296,$menus)){ //编辑组织架构
                $menus_del=0;
            }
        }
        foreach ($dataList as $key=>$val){
            if($is_top){
                if(empty($val['village_id']) && $val['type'] == 99){
                    continue;
                }
            }
            $is_edit=$is_del=0;
            if(($val['type'] == 1 || $val['type'] == 2) && $login_type == 1){// 物业判断
                $is_edit=$is_del=1;
            }elseif ($val['village_id'] == $village_id && $login_type == 2 && $val['type'] != 0){
                $is_edit=$menus_edit;
                $is_del=$menus_del;
            }
            $disabled=false;
            if($val['village_id']>0 && $val['type']==0){
                $village_info=$this->HouseVillage->getInfo(['village_id'=>$val['village_id']],'village_id,status,village_name');
                if($village_info && !$village_info->isEmpty()){
                    $village_info=$village_info->toArray();
                    if($village_info['status'] == 5){
                        continue;
                    }
                    if(!in_array($village_info['status'],array(0,1))){
                        $disabled=true;
                    }
                    if (isset($village_info['village_name']) && $village_info['village_name'] && ($val['name']!=$village_info['village_name'])) {
                        $val['name'] = $village_info['village_name'];
                        $this->PropertyGroup->editFind(array('id'=>$val['id']),array('name'=>$village_info['village_name']));
                    }
                }
            }
            if ($val['type'] == 99 && isset($param['property_name']) && $param['property_name']) {
                $val['name'] = $param['property_name'];
            }
            $new_organization[] = [
                'id'=>$val['id'],
                'fid'=>$val['fid'],
                'title'=> $val['name'],
                'key'=> '1-'.$val['village_id'].'-'.$val['name'].'-'.$val['id'],
                'scopedSlots'=> ['title'=> "edit_out"],
                'is_edit'=>$is_edit,
                'is_del'=>$is_del,
                'type'=>$type[$val['type']],//  0是小区 1 小区部门 2物业部门 3 物业
                'disabled'=>$disabled,
                'village_id'=>$val['village_id'] ? $val['village_id']:0,
            ];
        }
        $new_arr = (new OrganizationStreetService())->getTree($new_organization);
        $key = [];
        if(isset($new_arr[0]['key'])){
            $key=[
                ['key'=>$new_arr[0]['key'], 'assets_id'=> 1],
            ];
        }
        $button['icon']=cfg('site_url').'/tpl/House/default/static/images/qyWeixin/qyweixinIcon1.png';
        if($login_type == 1){
            $button['status']=1;
        }else{
            $button['status']=0;
        }
        $data['button']=$button;
        $data['menu_list'] = $new_arr;
        $data['key'] = $key;
        return $data;

    }

    //todo 获取组织架构下面的人员或账号
    public function businessTissueUser($param){
        $property_group=$this->getPropertyGroup($param['property_id'],$param['group_id'],'type,village_id');
        $button=[
            'is_org'=>true,
            'is_user'=>true,
            'is_del'=>true,
            'data_see'=>true,
            'data_edit'=>true,
            'data_del'=>true,
        ];
        if($param['role_type'] == 2){ //小区判断
           if($property_group['village_id'] != $param['village_id']){
               $button=[
                   'is_org'=>false,
                   'is_user'=>false,
                   'is_del'=>false,
                   'data_see'=>true,
                   'data_edit'=>false,
                   'data_del'=>false,
               ];
           }
            if(!empty($param['menus'])){
                if(!in_array(292,$param['menus'])){ //添加子组织
                    $button['is_org']=false;
                }
                if(!in_array(293,$param['menus'])){ //添加人员
                    $button['is_user']=false;
                }
                if(!in_array(294,$param['menus'])){ //查看人员
                    $button['data_see']=false;
                }
                if(!in_array(297,$param['menus'])){ //编辑子组织
                    $button['data_edit']=false;
                }
                if(!in_array(296,$param['menus'])){ //删除人员
                    $button['is_del']=false;
                    $button['data_del']=false;
                }
            }
        }
        $houseVillageDb=$this->HouseVillage;
        $where=[];
        $where[]=['w.is_del','=',0];
        $where[]=['w.status','<>',4];
        $where[]=['w.property_id','=',$param['property_id']];
        if($param['role_type'] == 2){
            $where[]=['w.village_id','=',$param['village_id']];
        }
        if(!empty($param['keywords'])){
            $where[] = ['w.name|w.phone|w.job_number', 'like', '%' . $param['keywords'] . '%'];
        }else{
            $where[]=['w.department_id','=',$param['group_id']];
        }
        if($param['role_type'] == 1 && !empty($param['keywords']) && ($param['property_id']>0)){
            $whereArr=array();
            $whereArr[]=array('property_id','=',$param['property_id']);
            $whereArr[]=array('status','not in',array(0,1));
            $villageObj=$this->HouseVillage->getList($whereArr,'village_id',0);
            if($villageObj && !$villageObj->isEmpty()){
                $villages= $villageObj->toArray();
                $village_ids=array();
                foreach ($villages as $vvv){
                    $village_ids[]=$vvv['village_id'];
                }
                $where[]=['w.village_id','not in',$village_ids];
            }
        }
        $order='w.wid desc';
        $field='w.wid,w.job_number,w.name,w.phone,w.gender,w.open_door,w.openid,w.nickname,w.department_id,w.avatar,g.name as group_name,w.group_id,w.village_id,w.xtype,w.relation_id,w.hw_id';
        $res=$this->HouseWorkerService->getWorkerList($where,$field,$order,$param['page'],10);
        if(!empty($res['list'])){
            $houseAdminDb = new HouseAdmin();
            foreach ($res['list'] as &$v){
                $label='';
                $xtype=[];
                $v['is_property_worker']='否';
                if($v['hw_id']>0){
                    $v['is_property_worker']='是';
                }
               
                if(!empty($v['village_id'])){
                    $label= $this->HouseProgrammeService->getProgrammeWid($v['village_id'],$v['wid'],$v['group_id'],1,3);
                }
                if($param['role_type']==2){
                    $v['phone']=$houseVillageDb->phone_desensitization_v($v['phone'],$v['village_id']);
                }else{
                    $v['phone']=phone_desensitization($v['phone']);
                }
                if($property_group['village_id']){
                    if($v['xtype'] == 2 && $v['relation_id']>0){
                        $xtype=[
                            ['value'=>'工','color'=>'red','title'=>'工作人员账号登录'],
                        ];
                        $whereArr=array(['village_id','=',$v['village_id']]);
                        $whereArr[]=['wid','=',$v['wid']];
                        $whereArr[]=['id','=',$v['relation_id']];
                        $houseAdmin = $houseAdminDb->getOne($whereArr);
                        if($houseAdmin && !$houseAdmin->isEmpty()){
                            $houseAdmin=$houseAdmin->toArray();
                            if($houseAdmin['status']==1){
                                $xtype[]= ['value'=>'管','color'=>'#1890FF','title'=>'小区管理员账号登录'];
                            }
                        }

                    }else{
                        $xtype=[
                            ['value'=>'工','color'=>'red','title'=>'工作人员账号登录'],
                        ];
                    }
                }
                $v['label']=$label;
                $v['name_type']=$xtype;
            }
            unset($v);
        }
        return array_merge($res,['button'=>$button]);
    }

    //todo 获取类型参数
    public function businessGroupParam($login_type,$property_id,$group_id,$type=0){
        $property_group=$this->getPropertyGroup($property_id,$group_id,'type,village_id');
        $data=[
            'department_type'=>self::getDepartmentType(),
        ];
        $group_type=[];
        if($type == 0){
            if((intval($property_group['village_id']) > 0) || $login_type == 2){
                $group_type=[
                    ['key'=>1, 'value'=>'小区部门']
                ];
                $data['is_show']=true;
                $data['key']=1;
            }
            else{
                $group_type=[
                    ['key'=>2, 'value'=>'物业部门'],
                    ['key'=>0, 'value'=>'小区'],
                ];
                $data['is_show']=false;
            }
        }
        else{
            if($property_group['type'] == 0){
                $group_type[]=['key'=>0, 'value'=>'小区'];
            }
            elseif ($property_group['type'] == 1){
                $group_type[]=['key'=>1, 'value'=>'小区部门'];
            }
            elseif ($property_group['type'] == 2){
                $group_type[]=['key'=>2, 'value'=>'物业部门'];
            }
            $data['key']=$property_group['type'];
            $data['is_show']=true;
        }
        $data['group_type']=$group_type;
        return $data;
    }

    //todo 添加子组织
    public function businessOrganizationAdd($param)
    {
        $data = [
            'fid'         => $param['fid'],
            'sort'        => $param['sort'],
            'status'      => 1,
            'type'        => $param['group_type'],
            'property_id' => $param['property_id'],
            'village_id'  => $param['village_id']
        ];
        $data = array_merge($data, $this->checkOrganization($param));
        $rel = $this->PropertyGroup->addFind($data);
        if($rel){
            $workParam = [
                'id'          => $rel,
                'village_id'  => isset($data['village_id'])  ? $data['village_id']  : 0,
                'property_id' => isset($data['property_id']) ? $data['property_id'] : 0,
            ];
            $info = (new DepartmentUserService())->syncSingleGroupToWorkWeiXin($workParam);
            fdump_api([$info, $workParam], 'syncSingleGroupToWorkWeiXin');
            return ['error' => true, 'msg' => '添加成功'];
        } else {
            return ['error' => false, 'msg' => '添加失败'];
        }
    }

    //todo 返回子组织数据
    public function businessOrganizationQuery($param){
        $property_group = $this->getPropertyGroup($param['property_id'], $param['id']);
        $group_type = [];
        if ($property_group['type'] == 0) {
            $group_type[] = ['key' => 0, 'value' => '小区'];
        } elseif ($property_group['type'] == 1) {
            $group_type[] = ['key' => 1, 'value' => '小区部门'];
        } elseif ($property_group['type'] == 2) {
            $group_type[] = ['key' => 2, 'value' => '物业部门'];
        }
        $wx_bind_id = $this->HouseEnterpriseWxBind->getOne(['bind_id' => $param['property_id']], 'pigcms_id');
        $qy_txt     = '';
        $qy_time    = '';
        $qy_reasons = '';
        $qy_id = '';
        if ($wx_bind_id) {
            $qy_txt = '未同步';
            $qy_time = '暂无';
            $qy_id = isset($property_group['create_id']) && trim($property_group['create_id']) ? $property_group['create_id'] : '暂无';
            if (isset($property_group['update_time']) && $property_group['update_time']) {
                $qy_time = date('Y-m-d H:i:s', $property_group['update_time']);
            } elseif (isset($property_group['create_time']) && $property_group['create_time']) {
                $qy_time = date('Y-m-d H:i:s', $property_group['create_time']);
            } elseif (isset($property_group['del_time']) && $property_group['del_time']) {
                $qy_time = date('Y-m-d H:i:s', $property_group['del_time']);
            }
            if (!in_array($property_group['create_status'], [1, 4]) && trim($property_group['qy_reasons'])) {
                $qy_txt = '【同步失败】';
                $qy_reasons = $property_group['qy_reasons'];
            } elseif (1 == $property_group['create_status']) {
                if (trim($property_group['qy_reasons'])) {
                    $qy_txt = '【同步成功】';
                } else {
                    $qy_txt = '【同步成功】';
                }
            } elseif (4 == $property_group['create_status']) {
                if (trim($property_group['qy_reasons'])) {
                    $qy_txt = '【同步删除】';
                } else {
                    $qy_txt = '【同步删除】';
                }
                if (isset($property_group['del_time']) && $property_group['del_time']) {
                    $qy_time = date('Y-m-d H:i:s', $property_group['del_time']);
                }
            }
        }
        $data = [
            'data' => [
                'id'              => $property_group['id'],
                'fid'             => $property_group['fid'],
                'name'            => $property_group['name'],
                'group_type'      => $property_group['type'],
                'department_type' => $property_group['section_status'],
                'sort'            => $property_group['sort'],
                'qy_txt'          => $qy_txt,
                'qy_time'         => $qy_time,
                'qy_reasons'      => $qy_reasons,
                'qy_id'           => $qy_id,
                'editQy'          => true,
                'village_id'      => $property_group['village_id'] ? intval($property_group['village_id']):0,
            ],
        ];
        return $data;
    }

    //todo 子组织数据
    public function businessOrganizationSub($param){
        $property_group=$this->getPropertyGroup($param['property_id'],$param['id']);
        $data=[
            'sort'=>$param['sort'],
        ];
        $data1=array_merge($data,$this->checkOrganization($param));
        $this->PropertyGroup->editFind([['property_id','=',$param['property_id']],['id','=',$property_group['id']]],$data1);
        if($property_group && isset($data1['section_status']) && !empty($data1['section_status'])){
            $this->HouseWorker->editData([
                ['property_id','=',$property_group['property_id']],
                ['village_id','=',$property_group['village_id']],
                ['department_id','=',$property_group['id']]
            ],['type'=>$data1['section_status']]);
        }
        if ($property_group) {
            $workParam = [
                'id'          => $property_group['id'],
                'village_id'  => $property_group['village_id'],
                'property_id' => $property_group['property_id'],
            ];
            (new DepartmentUserService())->syncSingleGroupToWorkWeiXin($workParam);
        }
        return ['error'=>true,'msg'=>'编辑成功'];
    }

    //todo 删除子组织
    public function businessOrganizationDel($param){
        $group_id=$this->recursionOrganization($param['id']);
        if($group_id){
            $group_id=array_merge([$param['id']],$group_id);
        }else{
            $group_id=[$param['id']];
        }
        if(empty($group_id)){
            throw new \think\Exception('数据为空，请刷新数据');
        }
        Db::startTrans();
        try {
            //删除上下级组织
            $this->checkWorkDel([
                'property_id'  => $param['property_id'],
                'login_role'   => $param['login_role'],
                'role_type'    => $param['role_type'],
                'user_admin'   => $param['user_admin'],
                'group_id_all' => $group_id
            ],[],1);
            $this->PropertyGroup->editFind([['property_id','=',$param['property_id']],['id','in',$group_id]],['is_del'=>1]);
            $where[] = ['department_id','in',$group_id];
            $where[] = ['property_id','=',$param['property_id']];
            $where[] = ['is_del','=',0];
            $this->businessWorkerDel($param['property_id'],$where);
            foreach ($group_id as $id) {
                $workParam = [
                    'id'          => $id,
                    'village_id'  => isset($param['village_id'])  ? $param['village_id']  : 0,
                    'property_id' => isset($param['property_id']) ? $param['property_id'] : 0,
                ];
                $info = (new DepartmentUserService())->syncSingleGroupToWorkWeiXin($workParam);
                fdump_api([$info, $workParam], 'delSyncSingleGroupToWorkWeiXin',1);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        return ['error'=>true,'msg'=>'删除成功'];
    }

    //todo 返回工作人员数据
    public function businessWorkerQuery($param){
        $where[] = ['wid','=',$param['wid']];
//        $where[] = ['department_id','=',$param['group_id']];
        $where[] = ['is_del','=',0];
        $where[] = ['property_id','=',$param['property_id']];
        $info = $this->HouseWorker->get_one($where);
        if ($info && !is_array($info)) {
            $info = $info->toArray();
        }
        if(empty($info)){
            throw new \think\Exception('该数据不存在');
        }
        $is_true=false;
        $adminLoginService = (new AdminLoginService())->showTabRoleArr;
        if(in_array($param['login_role'],$adminLoginService)){
            $is_true=true;
        }
        $wx_bind_id=$this->HouseEnterpriseWxBind->getOne(['bind_id'=>$param['property_id']],'pigcms_id');
        $qy_txt     = '未同步';
        $qy_time    = '暂无';
        $qy_reasons = '';
        $is_worker_admin=0;
        $houseAdminDb = new HouseAdmin();
        if($info['xtype']==2 && $info['relation_id']>0){
            $whereArr=array(['village_id','=',$info['village_id']]);
            $whereArr[]=['wid','=',$param['wid']];
            $whereArr[]=['id','=',$info['relation_id']];
            $houseAdmin = $houseAdminDb->getOne($whereArr);
            if($houseAdmin && !$houseAdmin->isEmpty()){
                $houseAdmin=$houseAdmin->toArray();
                if($houseAdmin['status']==1){
                    $is_worker_admin=1;
                }
            }
        }
        $depart_name='';
        if($info['department_id']>0){
            $pWhereArr=array('id'=>$info['department_id']);
            $onePropertyGroup=$this->getOnePropertyGroup($pWhereArr,'id,fid,name');
            if($onePropertyGroup){
                $depart_name=$onePropertyGroup['name'];
            }
        }
        $qy_id = '';
        if($wx_bind_id){
            if (isset($info['update_time']) && $info['update_time']) {
                $qy_time = date('Y-m-d H:i:s', $info['update_time']);
            } elseif(isset($info['qy_time']) && $info['qy_time']) {
                $qy_time = date('Y-m-d H:i:s', $info['qy_time']);
            } elseif (isset($info['del_time']) && $info['del_time']) {
                $qy_time = date('Y-m-d H:i:s', $info['del_time']);
            }
            $qy_id = isset($info['qy_id']) && trim($info['qy_id']) ? trim($info['qy_id']) : '暂无';
            if (!in_array($info['qy_status'],[1,4]) && trim($info['qy_reasons'])) {
                $qy_txt     = '【同步失败】';
                $qy_reasons = trim($info['qy_reasons']);
            } elseif (1==$info['qy_status']) {
                if (trim($info['qy_reasons'])) {
                    $qy_txt = '【同步成功】';
                } else {
                    $qy_txt = '【同步成功】';
                }
            } elseif (4==$info['qy_status']) {
                if (trim($info['qy_reasons'])) {
                    $qy_txt = '【同步删除】';
                } else {
                    $qy_txt = '【同步删除】';
                }
                if ($info['del_time']) {
                    $qy_time = date('Y-m-d H:i:s', $info['del_time']);
                }
            }
        }
        $houseVillageDb=new HouseVillage();
        $data=[];
        $info['job_create_time']=!empty($info['job_create_time']) ? date('Y-m-d',$info['job_create_time']) : '';
        if($param['source_type'] == 1){
            if (cfg('householdIdShow')==1) {
                if(isset($info['village_id']) && isset($info['phone']) && $info['phone'] && $info['village_id']){
                    $whereBind = [];
                    $whereBind[] = ['phone','=',$info['phone']];// 原手机号
                    $whereBind[] = ['village_id','=',$info['village_id']];
                    $whereBind[] = ['type','=',4];
                    $whereBind[] = ['status','<>',4];
                    $bind_user=$this->HouseVillageUserBind->getOne($whereBind,'pigcms_id');
                    if ($bind_user && isset($bind_user['pigcms_id'])) {
                        $data[]= ['name'=>'ID','value'=>$bind_user['pigcms_id'],'type'=>0];
                    }
                }
            }
            $data[]= ['name'=>'编号','value'=>$info['job_number'],'type'=>0];
            $data[]= ['name'=>'姓名','value'=>$info['name'],'type'=>0];
            $data[]= ['name'=>'性别','value'=>($info['gender'] == 1 ? '男' : '女'),'type'=>0];
            if($param['login_type']==2 && $info['village_id']){
                $tmp_phone=$houseVillageDb->phone_desensitization_v($info['phone'],$info['village_id']);
                $data[]= ['name'=>'手机号码','value'=>$tmp_phone,'type'=>0];
            }else{
                $data[]= ['name'=>'手机号码','value'=>phone_desensitization($info['phone']),'type'=>0];
            }
            if($is_true){
                if($param['login_type']==2 && $info['village_id']){
                    $tmp_phone=$houseVillageDb->idnum_desensitization_v($info['id_card'],$info['village_id']);
                    $data[]= ['name'=>'身份证号','value'=>$tmp_phone,'type'=>0];
                }else{
                    $data[]= ['name'=>'身份证号','value'=>idnum_desensitization($info['id_card']),'type'=>0];
                }
                $data[]= ['name'=>'IC卡','value'=>$info['ic_card'],'type'=>0];
            }
            $data[]= ['name'=>'微信号','value'=>$info['weixin_num'],'type'=>0];
            if($is_true){
                $data[]= ['name'=>'入职时间','value'=>$info['job_create_time'],'type'=>0];
            }
            $data[]= ['name'=>'是否可以开门','value'=>($info['open_door'] == 1 ? '是' :'否'),'type'=>0];
            $data[]= ['name'=>'备注','value'=>$info['note'],'type'=>0];
            $data[] = ['name' => '企微同步状态', 'value' => $qy_txt, 'type' => 0];
            if ($qy_reasons) {
                $data[] = ['name' => '同步失败原因', 'value' => $qy_reasons, 'type' => 0];
            }
            $data[] = ['name' => '企微同步时间', 'value' => $qy_time, 'type' => 0];
            if ($qy_id) {
                $data[] = ['name' => '企微账号', 'value' => $qy_id, 'type' => 0];
            }
            $label='';
            if(!empty($info['village_id'])){
                $label= $this->HouseProgrammeService->getProgrammeWid($info['village_id'],$param['wid'],$info['group_id']);
            }
            $data[]= ['name'=>'标签','value'=>$label,'type'=>1];
            $village_id = $info['village_id'] ?? 0;
            $houseName = (new HouseVillageService())->getHouseAlias('house_name',$village_id)?:'小区';
            if($is_worker_admin>0){
                $data[]= ['name'=>$houseName.'管理人员身份','value'=>'是','type'=>0];
            }else{
                $data[]= ['name'=>$houseName.'管理人员身份','value'=>'否','type'=>0];
            }
            if(isset($info['is_sentry']) && $info['is_sentry']==1){
                $data[]= ['name'=>$houseName.'可以管理岗亭','value'=>'是','type'=>0];
            }elseif(isset($info['is_sentry']) ){
                $data[]= ['name'=>$houseName.'可以管理岗亭','value'=>'否','type'=>0];
            }
        }
        else{
            $upload_face=[
                'status'=>0,
                'url'=>''
            ];
            if($param['login_type'] == 2 && $param['village_id'] == $info['village_id']){
                $where=[];
                $where[] = ['phone','=',$info['phone']];// 原手机号
                $where[] = ['village_id','=',$info['village_id']];
                $where[] = ['type','=',4];
                $where[] = ['status','<>',4];
                $bind_user=$this->HouseVillageUserBind->getOne($where);
                if($bind_user && !$bind_user->isEmpty()){
                    $upload_face=[
                        'status'=>1,
                        'url'=>cfg('site_url').'/shequ.php?g=House&c=User&a=faces&id='.$bind_user['pigcms_id']
                    ];
                }
            }
            if ($qy_reasons) {
                $qy_txt = $qy_reasons . $qy_txt;
            }
            $data = [
                'wid' => $info['wid'], 'village_id' => $info['village_id'], 'department_id' => $info['department_id'],
                'depart_name' => $depart_name, 'job_number' => $info['job_number'], 'work_name' => $info['name'],
                'gender' => $info['gender'], 'phone' => $info['phone'], 'id_card' => $info['id_card'],
                'ic_card' => $info['ic_card'], 'job_create_time' => $info['job_create_time'],
                'open_door' => $info['open_door'], 'remarks' => $info['note'], 'account' => $info['account'],
                'qy_txt' => $qy_txt, 'qy_time' => $qy_time, 'upload_face' => $upload_face, 'is_worker_admin' => $is_worker_admin,
                'qy_id' => $qy_id, 'editQy' => true, 'is_constructor' => isset($info['is_constructor']) ? intval($info['is_constructor']) : 0,
                'hw_id' => isset($info['hw_id']) ? $info['hw_id'] : 0,
                'is_sentry'=>$info['is_sentry'],
            ];
            if(isset($info['is_sentry'])){
                $data['is_sentry']=$info['is_sentry'];
            }
        }
        return $data;
    }

    //工作人员对应的用户更新
    protected function worker_user_amend($wid,$phone, $param = []){
        $time=time();
        $worker=$this->HouseWorker->get_one(['wid'=>$wid]);
        if(!$worker || $worker->isEmpty()){
            fdump_api(['工作人员数据不存在=='.__LINE__,$wid],'framework/worker_user_amend',1);
           return false;
        }
        if(empty($worker['village_id'])){
            fdump_api(['工作人员village_id为空=='.__LINE__,$wid],'framework/worker_user_amend',1);
            return false;
        }
        //查询输入的手机号变更uid
        $now_user=invoke_cms_model('User/get_user', [
            'field_value'=>$worker['phone'],
            'field'=>'phone',
        ]);
        $uid = isset($now_user['retval']['uid']) ? $now_user['retval']['uid'] : 0;
        $data['village_id'] = $worker['village_id'];
        $data['type'] = 4 ;//工作人员
        $data['memo'] ='工作人员自动绑定小区';
        $data['status'] = 1 ;
        $data['pass_time'] = $time;
        $data['phone']=$worker['phone'];
        $data['name']=$worker['name'];
        $where=array();
        $where[] = ['phone','=',$phone];// 原手机号
        $where[] = ['village_id','=',$worker['village_id']];
        $where[] = ['type','=',4];
        //$where[] = ['status','in',[1,2]];
        $bind_info=$this->HouseVillageUserBind->getOne($where);
        if($bind_info && !$bind_info->isEmpty()){
            $bind_info=$bind_info->toArray();
        }
        if(empty($bind_info) && $uid>0){
            $where=array();
            $where[] = ['uid','=',$uid];// 原手机号
            $where[] = ['village_id','=',$worker['village_id']];
            $where[] = ['type','=',4];
            //$where[] = ['status','in',[1,2]];
            $bind_info=$this->HouseVillageUserBind->getOne($where);
            if($bind_info && !$bind_info->isEmpty()){
                $bind_info=$bind_info->toArray();
            }
        }
        /*
        if($worker['phone'] != $phone){
            $this->HouseVillageUserBind->saveOne([
                ['phone','=',$phone],
                ['village_id','=',$worker['village_id']],
                ['type','=',4],
                ['status','in',[1,2]]
            ],['status'=>0]);
        }
        */
        $data['uid'] = $uid;
        $data['ic_card'] = $worker['ic_card'] ? $worker['ic_card'] : '';
        if ($data['ic_card'] && isset($param['ic_card']) && $param['ic_card']) {
            $data['ic_card'] = $param['ic_card'];
        }
        $data['id_card'] = $worker['id_card'] ? $worker['id_card'] : '';
        if ($data['id_card'] && isset($param['id_card']) && $param['id_card']) {
            $data['id_card'] = $param['id_card'];
        }
        $data['uid'] = isset($now_user['retval']['uid']) ? $now_user['retval']['uid'] : 0;
        if ($bind_info) { // 已有业主信息 更新
            $set =$this->HouseVillageUserBind->saveOne($where,$data);
            if (!$set) {
                fdump_api(['物业编辑工作人员错误11=='.__LINE__,$set,$where,$data],'framework/worker_user_amend',1);
            }
            $pigcms_id = $bind_info['pigcms_id'];
            if(!isset($data['uid'])){
                $data['uid']=$bind_info['uid'];
            }
        }else{
            $data['usernum'] = rand(0,99999) . '-' . time();
            $data['add_time'] = $time ;
            $data['housesize']='';
            $data['water_price']='';
            $data['electric_price']='';
            $data['gas_price']='';
            $data['park_price']='';
            $data['property_price']='';
            $data['address']='';
            $data['property_starttime']='';
            $data['card_no']='';
            $data['door_control']='';
            $data['reason']='';
            $data['attribute']='';
            $data['v_id']='';
            $data['u_id']='';
            $data['door_code']='';
            $data['door_name']='';
            $data['keyId']='';
            $data['add_face_time']='';
            $data['face_img_status']='';
            $data['face_img_reason']='';
            $data['a2_face_door_keyId']='';
            $data['is_all_notice']='';
            $data['nmv_card']='';
            $pigcms_id=$this->HouseVillageUserBind->addOne($data);
            if (!$pigcms_id) {
                fdump_api(['物业添加工作人员错误22=='.__LINE__,$pigcms_id,$where,$data],'framework/worker_user_amend',1);
            }
        }
        if ($pigcms_id>0 && isset($data['uid'])) {
            invoke_cms_model('House_face_img/house_user_face_device_info', [
                'uid'=>$data['uid'],
                'pigcms_id'=>$pigcms_id,
            ]);
        }
    }

    //todo 添加工作人员
    public function businessWorkerAdd($login_type,$param){
        //$login_type 1是物业 2是小区 3街道
        $property_group=$this->checkWorkParam($login_type,$param);
        if($property_group['section_status']!==0 && $property_group['section_status'] == null ){
            $param['type'] = '-1';
        }else{
            $param['type'] = intval($property_group['section_status'])>=0 ? intval($property_group['section_status']) : '-1';
        }
        if(!empty($param['password'])){
            $param['password']=md5($param['password']);
        }
        $param['status']=1;
        $param['people_type']=$property_group['type'];
        $xtype='';
        if($login_type==1){
            $xtype='property';
            if(!empty($property_group['village_id'])){
                $param['village_id']=$property_group['village_id'];
                $xtype='village';
                $login_type=2;
            }
        }elseif($login_type==2){
            $xtype='village';
        }
        if(!empty($xtype)){
            $tmpRet=$this->saveWorkerData($param,$xtype);
            if(!$tmpRet['error']){
                if (isset($tmpRet['wid']) && $tmpRet['wid']) {
                    $info = (new DepartmentUserService())->syncSingleWorkToWorkWeiXin(['wid' => $tmpRet['wid']]);
                    fdump_api([$info, $tmpRet], 'syncSingleWorkToWorkWeiXin',1);
                }
                return $tmpRet;
            }else{
                $rel=$tmpRet['wid'];
                $this->worker_user_amend($rel,$param['phone'], $param);
                $info = (new DepartmentUserService())->syncSingleWorkToWorkWeiXin(['wid' => $rel]);
                fdump_api([$info, $rel], 'syncSingleWorkToWorkWeiXin',1);
                return $tmpRet;
            }
        }else{
            if(isset($param['is_worker_admin'])){
                unset($param['is_worker_admin']);
            }
            $rel=$this->HouseWorker->addFind($param);
            if($rel){
                $this->worker_user_amend($rel,$param['phone'], $param);
                $info = (new DepartmentUserService())->syncSingleWorkToWorkWeiXin(['wid' => $rel]);
                fdump_api([$info, $rel], 'syncSingleWorkToWorkWeiXin',1);
                return ['error'=>true,'msg'=>'添加成功'];
            }else{
                return ['error'=>false,'msg'=>'添加失败'];
            }
        }
    }

    //todo 编辑工作人员
    public function businessWorkerSub($login_type,$wid,$param){
        $house_worker=$this->HouseWorker->get_one(['wid'=>$wid],'property_id,village_id,phone,password,department_id');
        if (!$house_worker || $house_worker->isEmpty()){
            throw new \think\Exception('该数据不存在');
        }
        $house_worker=$house_worker->toArray();
        $property_group=$this->checkWorkParam($login_type,$param,$wid);
        if($property_group['section_status']!==0 && $property_group['section_status'] == null ){
            $param['type'] = '-1';
        }else{
            $param['type'] = intval($property_group['section_status'])>=0 ? intval($property_group['section_status']) : '-1';
        }
        $where=[];
        $where[] = ['is_del','=',0];
        $where[] = ['wid','=',$wid];
        if(empty($param['account'])){
            unset($param['account']);
        }
        if(empty($param['password'])){
            unset($param['password']);
        }else{
            $param['password']=md5($param['password']);
        }
        //unset($param['department_id']);
        $xtype='';
        if($login_type==1 && $house_worker['village_id']<1){
            $xtype='property';
        }elseif($login_type==2){
            $xtype='village';
        }
        if($house_worker['village_id']>0){
            $xtype='village';
        }
        $_login=$param['_login'];
        unset($param['_login']);
        //针对物业端操作小区工作人员 操作跨小区绑定部门 执行先删除后插入的逻辑
        if($login_type == 1){
            $property_group=$this->getPropertyGroup($house_worker['property_id'],$param['department_id'],'id,type,village_id,section_status');
            if($property_group['village_id'] != $house_worker['village_id']){ //选择部门对应的小区和当前用户所在小区不一致
                $check_param=[
                    '_login'=>$_login,
                    'wid'=>$wid,
                    'house_worker'=>$house_worker,
                    'property_group'=>$property_group
                ];
                //组装需要传递的参数
                $param['_param']=$check_param;
                //重置为0 走插入操作
                $wid=0;
                //替换组织小区id
                $house_worker['village_id']=$property_group['village_id'];
                //替换提交的手机号
                $house_worker['phone']=$param['phone'];
                $param['status']=1;
                if($property_group['section_status']!==0 && $property_group['section_status'] == null ){
                    $param['type'] = '-1';
                }else{
                    $param['type'] = intval($property_group['section_status'])>=0 ? intval($property_group['section_status']) : '-1';
                }
                if(!isset($param['password'])){
                    $param['password']=$house_worker['password'];
                }
                if(empty($property_group['village_id']) && $property_group['type'] == 2){ //物业操作 给小区创建工作人员
                    $xtype='property';
                }else{
                    $xtype='village';
                }
            }
        }
        $param['village_id']=$house_worker['village_id'];
        if(!empty($xtype)){
            $tmpRet=$this->saveWorkerData($param,$xtype,$wid);
            if(!$tmpRet['error']){
                (new DepartmentUserService())->syncSingleWorkToWorkWeiXin(['wid' => $wid]);
                return $tmpRet;
            }else{
                if(isset($param['_param'])){
                    $wid=$tmpRet['wid'];
                }
                $this->worker_user_amend($wid,$house_worker['phone'], $param);
                (new DepartmentUserService())->syncSingleWorkToWorkWeiXin(['wid' => $wid]);
                return $tmpRet;
            }
        }else{
            if(isset($param['is_worker_admin'])){
                unset($param['is_worker_admin']);
            }
            unset($param['property_id'],$param['village_id']);
            $this->HouseWorker->editData($where,$param);
            $this->worker_user_amend($wid,$house_worker['phone'], $param);
            (new DepartmentUserService())->syncSingleWorkToWorkWeiXin(['wid' => $wid]);
            return ['error'=>true,'msg'=>'编辑成功'];
        }
    }

    //todo 删除工作人员
    public function businessWorkerDel($property_id,$where,$type=0){
        $house_worker_list=$this->HouseWorker->getAll($where,'wid,village_id,property_id,phone,qy_id,qy_status,xtype,relation_id,hw_id');
        $village_id=0;
        $qy_id='';
        $plan_data=[];
        $time=time();
        if($house_worker_list && !$house_worker_list->isEmpty()){
            $house_worker_list=$house_worker_list->toArray();
            //删除工作人员
            $this->HouseWorker->editData($where,['is_del'=>1,'status'=>4,'del_time'=>time()]);
            $housePropertyVillageWorkerRelationDb= new HousePropertyVillageWorkerRelation();
            $houseAdminDb = new HouseAdmin();
            $propertyAdminDb = new PropertyAdmin();
            foreach ($house_worker_list as $v){
                if($v['hw_id']>0){
                    $whereTmpArr=array('hw_id'=>$v['hw_id'],'wid'=>$v['wid']);
                    $housePropertyVillageWorkerRelationDb->saveOneData($whereTmpArr,['del_time'=>$time]);
                }
                if(!empty($v['qy_id']) && ($v['qy_id']==1)){
                    $qy_id=$v['qy_id'];
                }
                if($v['xtype']==1 && $v['relation_id']>0){
                    //物业关联账号
                    $whereTmp=array('id'=>$v['relation_id'],'property_id'=>$v['property_id']);
                    $propertyAdminDb->delPropertyAdmin($whereTmp);
                }elseif ($v['xtype']==2 && $v['relation_id']>0){
                    //小区关联账号
                    $whereTmp=array('id'=>$v['relation_id'],'village_id'=>$v['village_id']);
                    $village_id=$v['village_id'];
                    //$houseAdminDb->delHouseAdmin($whereTmp);
                    $houseAdminDb->save_one($whereTmp,array('status'=>4,'remarks'=>'账号在'.date('Y-m-d H:i:s').'被删除了'));
                    //同步删除权限方案工作人员标签
                    $this->HouseProgrammeService->programmeRelationDel([
                        ['village_id','=',$v['village_id']],
                        ['wid','=',$v['wid']]
                    ]);
                }
                if($v['phone'] && !empty($v['village_id'])){
                    $bind_user=$this->HouseVillageUserBind->getOne([
                        ['phone','=',$v['phone']],
                        ['village_id','=',$v['village_id']],
                        ['type','=',4],
                    ]);
                    if($bind_user && !$bind_user->isEmpty()){
                        $bind_user=$bind_user->toArray();
                        $this->HouseVillageUserBind->saveOne(['pigcms_id'=>$bind_user['pigcms_id']],['status'=>4]);
                        $plan_data[]=[
                            'param'=>serialize([
                                'type'=>'org_del_sys_face_door',
                                'param'=>[
                                    'pigcms_id'=>$bind_user['pigcms_id'],
                                    'village_id'=>$v['village_id'],
                                    'bind_user'=>$bind_user
                                ]
                            ]),
                            'plan_time'     =>  -110,
                            'space_time'    =>  0,
                            'add_time'      =>  $time,
                            'file'          =>  'sub_d5_park',
                            'time_type'     =>  1,
                            'unique_id'     =>  'org_del_1_'.$bind_user['pigcms_id'].'_'.$time.'_'.uniqid(),
                            'rand_number'   =>  mt_rand(1, max(cfg('sub_process_num'), 3)),
                        ];
                    }
                }
                (new DepartmentUserService())->syncSingleWorkToWorkWeiXin(['wid' => $v['wid']]);
            }
            $num=count($house_worker_list);
//            if($property_id && !empty($qy_id)){
//                $plan_data[]=[
//                    'param'=>serialize([
//                        'type'=>'org_del_sys_wechat',
//                        'param'=>[
//                            'property_id'=>$property_id,
//                            'village_id'=>$village_id
//                        ]
//                    ]),
//                    'plan_time'     =>  -110,
//                    'space_time'    =>  0,
//                    'add_time'      =>  $time,
//                    'file'          =>  'sub_d5_park',
//                    'time_type'     =>  1,
//                    'unique_id'     =>  'org_del_2_'.$village_id.'_'.$time.'_'.uniqid(),
//                    'rand_number'   =>  mt_rand(1, max(cfg('sub_process_num'), 3)),
//                ];
//            }
            if($plan_data){
                (new ProcessSubPlan())->addAll($plan_data);
            }
            return ['error'=>true,'msg'=>'删除成功，已处理【'.$num.'】条数据'];
        }else{
            if($type == 1){
                throw new \think\Exception('选中的数据不存在');
            }
            return ['error'=>false,'msg'=>'选中的数据不存在'];
        }
    }

    //todo 异步处理用户关系数据
    public function synHandleUserRelation($type,$param){
        fdump_api(['异步处理用户关系数据=='.__LINE__,$type,$param],'framework/synHandleUserRelation',1);
        if($type == 1){
            //todo 同步删除设备数据
            invoke_cms_model('House_village_face_door/del_work_user_bind',$param);
        }elseif ($type == 2){
            //todo 删除完成考虑到可能删除数据过多 直接同步整体同步一次
            invoke_cms_model('Property_group/sync_to_enterprise_wechat', $param);
        }
        return true;
    }

    //todo 物业后台处理小区工作人员跨小区绑定部门 集中处理
    public function handleWorkerSwitchVillage($param){
        //校验是否删除
        $this->checkCurrentLogin($param['_login']['login_role'],$param['_login']['user_admin'],$param['wid']);
        $where_del=[
            ['wid','=',$param['wid']],
            ['is_del','=',0]
        ];
        //删除旧用户数据
        $this->businessWorkerDel($param['house_worker']['property_id'],$where_del);
        //解绑旧用户数据
        $this->unBindWorkerAuthSet($param['house_worker']['village_id'],$param['wid']);
        return true;
    }

    //todo 物业后台处理小区工作人员跨小区绑定部门 校验当前登录账号
    public function checkCurrentLogin($login_role,$user_admin,$del_wid){
        $adminLoginService = (new AdminLoginService());
        $wid=isset($user_admin['wid']) ? intval($user_admin['wid']) : 0;
        if($wid && in_array($login_role,$adminLoginService->propertyUserArr)){//物业 物业普通管理员
            if((int)$wid == (int)$del_wid){
                throw new \think\Exception('当前登录【'.(self::getWorkName($user_admin)).'】，不可操作切换部门！');
            }
        }
        return true;
    }


    //todo 物业后台处理小区工作人员跨小区绑定部门 解绑订单退款（作废）审核设置人员
    public function unBindWorkerAuthSet($village_id,$wid){
        $db_villageCheckauthSet = new HouseVillageCheckauthSet();
        $where=array('village_id'=>$village_id,'xtype'=>'order_refund_check');
        $auth_set = $db_villageCheckauthSet->getOne($where,'id,set_datas');
        if (!$auth_set || !empty($info['set_datas'])){
            return true;
        }
        $set_datas = json_decode($auth_set['set_datas'],1);
        if(!$set_datas){
            return true;
        }
        $is_true=false;
        foreach ($set_datas as $k=>&$v){
            if((int)$v['level_wid'] == (int)$wid){
                $is_true=true;
                unset($set_datas[$k]);
            }
            if($is_true){
                $v['level_v']=$v['level_v']-1;
            }
        }
        if(!$is_true){
            return true;
        }
        $db_villageCheckauthSet->saveOne(['id'=>$auth_set['id']],['set_datas'=>json_encode($set_datas,JSON_UNESCAPED_UNICODE)]);
        return true;
    }

    //todo 同步信息至企业微信
    public function businessSynQw($property_id){
        $wx_bind_id=$this->HouseEnterpriseWxBind->getOne(['bind_id'=>$property_id],'access_token, expires_in, corpid, permanent_code');
        if (!$wx_bind_id['corpid'] || !$wx_bind_id['permanent_code']) {
            throw new \think\Exception('请先进行企业微信授权');
        }
//        $rr = invoke_cms_model('Property_group/sync_to_enterprise_wechat', ['property_id'=>$property_id]);

        fdump_api(['msg' => '企业微信同步', 'line' => __LINE__, 'property_id' => $property_id], 'qiYeWeiXin/businessSynQwLog');
        $synInfo = (new DepartmentUserService())->getDepartment($property_id);
        fdump_api(['msg' => '企业微信同步', 'line' => __LINE__, 'property_id' => $property_id, 'synInfo' => $synInfo], 'qiYeWeiXin/businessSynQwLog', 1);
        $queueData = [
            'property_id'       => $property_id,
            'jobType'           => 'handleUserToExternalUserId',
        ];
        // $job_id = $this->traitCommonWorkWeiXin($queueData);
        if(isset($synInfo['message']) && $synInfo['message']){
            return ['error'=>true,'msg'=>$synInfo['message']];
        }else{
            return ['error'=>false,'msg'=>'同步失败'];
        }
    }

    //todo 校验用户IC卡号的唯一性
    public function checkUserBindIc($param){
        if(!isset($param['ic_card']) || empty($param['ic_card'])){
            return ['error'=>true,'msg'=>'ok'];
        }
        if(!isset($param['phone']) || empty($param['phone'])){
            return ['error'=>true,'msg'=>'ok'];
        }
        if(!isset($param['village_id']) || empty($param['village_id'])){
            return ['error'=>true,'msg'=>'ok'];
        }
        $where[] = ['a.ic_card','=',$param['ic_card']];
        $where[] = ['a.status','in',[1,2]];
        $where[] = ['a.phone','<>',$param['phone']];// 手机号
        $bind_info=$this->HouseVillageUserBind->getVillageUserBind($where,'a.village_id,a.name,v.village_name');
        if($bind_info){
            if($bind_info['village_id'] == $param['village_id']){
                return ['error'=>false,'msg'=>'当前用户【'.$bind_info['name'].'】已经使用了该卡号'];
            }else{
                return ['error'=>false,'msg'=>'当前【'.$bind_info['village_name'].'】小区【'.$bind_info['name'].'】用户已经使用了该卡号'];
            }
        }
        return ['error'=>true,'msg'=>'ok'];
    }

    //保存和更新工作人员数据
    public function saveWorkerData($datas=array(),$xtype='',$wid=0){
        if(empty($datas) || empty($xtype)){
            return ['error'=>false,'msg'=>'保存数据不能为空！'];
        }
        if(isset($datas['account'])){
            $datas['account']=trim($datas['account']);
        }
        $datas['remarks']=isset($datas['note']) ? $datas['note'] : '';
        $village_id=0;
        $is_param=false;
        if(isset($datas['village_id'])){
            $village_id=$datas['village_id'];
            unset($datas['village_id']);
        }
        $property_id=0;
        if(isset($datas['property_id'])){
            $property_id=$datas['property_id'];
            unset($datas['property_id']);
        }
        $check_ic=$this->checkUserBindIc([
            'ic_card'=>$datas['ic_card'],
            'phone'=>$datas['phone'],
            'village_id'=>$village_id,
        ]);
        if(!$check_ic['error']){
            return $check_ic;
        }
        if($xtype=='village' && $village_id<=0){
            return ['error'=>false,'msg'=>'小区id数据错误！'];
        }
        if($xtype=='property' && $property_id<=0){
            return ['error'=>false,'msg'=>'物业id数据错误！'];
        }
        $is_worker_admin=0;
        if(isset($datas['is_worker_admin'])){
            $is_worker_admin=$datas['is_worker_admin'];
            unset($datas['is_worker_admin']);
        }
        $whereWork=array();
        $whereWork[]=array('account','=',$datas['account']);
        $whereWork[]=array('is_del','=',0);
        $whereWork[]=array('status','<>',4);
        $whereWork[]=array('hw_id','<',1);
        if($xtype=='village'){
            $whereWork[]=array('xtype','=',2);
        }else if($xtype=='property'){
            $whereWork[]=array('xtype','=',1);
        }
        if($wid>0){
            $whereWork[]=array('wid','<>',$wid);
        }
        if(isset($datas['_param']['wid']) && $datas['_param']['wid']){
            $whereWork[]=array('wid','<>',$datas['_param']['wid']);
        }
        $housePropertyVillageWorkerRelationDb=new HousePropertyVillageWorkerRelation();
        $worker=$this->HouseWorker->get_one($whereWork);
        if ($worker && !$worker->isEmpty()) {
            if ($xtype == 'village') {
                // 已经删除或者审核拒绝的小区对应工作人员也改为已经删除状态
                $whereVillageNot = [];
                $whereVillageNot[] = ['status', 'in', [4, 5]];
                $villageIds = $this->HouseVillage->getColumn($whereVillageNot, 'village_id');
                $whereWorkEdit = $whereWork;
                $whereWorkEdit[] = ['village_id', 'in', $villageIds];
                $this->HouseWorker->editData($whereWorkEdit, ['is_del' => 1]);
                $otherWorker = $this->HouseWorker->get_one($whereWork);
                if (isset($otherWorker['wid']) && intval($otherWorker['wid']) > 0) {
                    return ['error'=>false,'msg'=>'您添加的【'.$datas['account'].'】账号已存在！'];
                }
            } else {
                return ['error'=>false,'msg'=>'您添加的【'.$datas['account'].'】账号已存在！'];
            }
        }
        if(isset($datas['_param'])){
            $is_param=true;
            $this->handleWorkerSwitchVillage($datas['_param']);
            unset($datas['_param']);
        }
        if($wid>0){
            $whereArr=array('wid'=>$wid);
            $worker=$this->HouseWorker->get_one($whereArr);
            if (!$worker || $worker->isEmpty()) {
                return ['error'=>false,'msg'=>'未找到要修改的数据！'];
            }else{
                $worker=$worker->toArray();
            }
            //更新
            $ret=$this->HouseWorker->editData($whereArr,$datas);
            $worker=$this->HouseWorker->get_one($whereArr);
            $worker=$worker->toArray();
            $worker=array_merge($worker,$datas);
            if($worker['xtype']==1 && ($worker['relation_id']>0)){
                //物业
                $relation_id=$worker['relation_id'];
                $property_admin_arr=array();
                $property_admin_arr['account']=$worker['account'];
                $property_admin_arr['pwd']=$worker['password'];
                $property_admin_arr['realname']=$worker['name'];
                $property_admin_arr['phone']=$worker['phone'];
                $property_admin_arr['wid']=$wid;
                $property_admin_arr['remarks']=$worker['remarks'];

                $propertyAdminDb = new PropertyAdmin();
                if($relation_id>0){
                    $houseAdminWhere=array('id'=>$relation_id);
                    $upres = $propertyAdminDb->save_one($houseAdminWhere,$property_admin_arr);
                }
                return ['error'=>true,'msg'=>'修改成功！','wid'=>$wid];
            }elseif($worker['xtype']==0 && $worker['relation_id']==0 && $xtype=='property' && $worker['property_id']>0 && $worker['village_id']==0){
                $propertyAdminDb = new PropertyAdmin();
                $whereArr=array(['property_id','=',$worker['property_id']]);
                $whereArr[]=['wid','=',$wid];
                $propertyObj = $propertyAdminDb->get_one($whereArr);
                if((!$propertyObj || $propertyObj->isEmpty()) && !empty($worker['account'])){
                    $whereArr=array(['account','=',$worker['account']],['property_id','=',$worker['property_id']],['status','=',1]);
                    $whereArr[]=['wid','<',1];
                    $propertyObj = $propertyAdminDb->get_one($whereArr);
                    if((!$propertyObj || $propertyObj->isEmpty()) && !empty($worker['phone'])){
                        $whereArr=array(['phone','=',$worker['phone']],['property_id','=',$worker['property_id']],['status','=',1]);
                        $whereArr[]=['wid','<',1];
                        $propertyObj = $propertyAdminDb->get_one($whereArr);
                    }
                }
                if($propertyObj && !$propertyObj->isEmpty()){
                    $propertyAdmin=$propertyObj->toArray();
                    $relation_id=$propertyAdmin['id'];
                    $this->HouseWorker->editData(array('wid'=>$wid),array('xtype'=>1,'relation_id'=>$relation_id));

                    $property_admin_arr=array();
                    $property_admin_arr['account']=$worker['account'];
                    $property_admin_arr['pwd']=$worker['password'];
                    $property_admin_arr['realname']=$worker['name'];
                    $property_admin_arr['phone']=$worker['phone'];
                    $property_admin_arr['remarks']=$worker['remarks'];
                    $property_admin_arr['wid']=$wid;
                    if($worker['status']==1 || $worker['status']==0){
                        $property_admin_arr['status']=1;
                    }else{
                        $property_admin_arr['status']=2;
                    }
                    $propertyAdminWhere=array('id'=>$relation_id);
                    $upres = $propertyAdminDb->save_one($propertyAdminWhere,$property_admin_arr);
                }else{
                    $property_admin_arr=array();
                    $property_admin_arr['property_id']=$worker['property_id'];
                    $property_admin_arr['account']=$worker['account'];
                    $property_admin_arr['pwd']=$worker['password'];
                    $property_admin_arr['realname']=$worker['name'];
                    $property_admin_arr['phone']=$worker['phone'];
                    $property_admin_arr['remarks']=$worker['remarks'];
                    $property_admin_arr['wid']=$wid;
                    $property_admin_arr['time']=time();
                    if($worker['status']==1 || $worker['status']==0){
                        $property_admin_arr['status']=1;
                    }else{
                        $property_admin_arr['status']=2;
                    }
                    $relation_id = $propertyAdminDb->addData($property_admin_arr);
                    if($relation_id>0){
                        $this->HouseWorker->editData(array('wid'=>$wid),array('xtype'=>1,'relation_id'=>$relation_id));
                    }
                }

            } elseif($worker['xtype']==2){
                //小区
                $relation_id=$worker['relation_id'];
                $house_admin_arr=array();
                $house_admin_arr['account']=$worker['account'];
                $house_admin_arr['pwd']=$worker['password'];
                $house_admin_arr['realname']=$worker['name'];
                $house_admin_arr['phone']=$worker['phone'];
                if(isset($datas['menus'])){
                    $house_admin_arr['menus']=$worker['menus'];
                }
                if(isset($datas['group_id'])){
                    $house_admin_arr['group_id']=$worker['group_id'];
                }
                $house_admin_arr['remarks']=$worker['remarks'];
                $house_admin_arr['wid']=$wid;
                if($is_worker_admin<1){
                    $house_admin_arr['status']=2;
                }else{
                    $house_admin_arr['status']=1;
                }
                $houseAdminDb = new HouseAdmin();

                if($relation_id<1){
                    $houseAdmin = $houseAdminDb->getOne(array('wid'=>$wid));
                    if($houseAdmin && !$houseAdmin->isEmpty()){
                        $houseAdmin=$houseAdmin->toArray();
                        $relation_id=$houseAdmin['id'];
                        $this->HouseWorker->editData(array('wid'=>$wid),array('xtype'=>2,'relation_id'=>$relation_id));
                    }
                }
                if($relation_id>0){
                    $houseAdminWhere=array('id'=>$relation_id);
                    $upres = $houseAdminDb->save_one($houseAdminWhere,$house_admin_arr);
                    if(isset($worker['hw_id']) && $worker['hw_id']>0){
                        $whereRArr=array(['hw_id','=',$worker['hw_id']],['village_id','=',$worker['village_id']]);
                        $whereRArr[]=array('wid','=',$wid);
                        $whereRArr[]=array('xtype','=',2);
                        $housePropertyVillageWorkerRelationDb->saveOneData($whereRArr,['had_id'=>$relation_id]);
                    }
                }
            }else if($worker['xtype']==0 && $worker['relation_id']==0 && $xtype=='village' && $worker['village_id']>0){
                $houseAdminDb = new HouseAdmin();
                $whereArr=array(['village_id','=',$worker['village_id']]);
                $whereArr[]=['wid','=',$wid];
                $houseAdmin = $houseAdminDb->getOne($whereArr);
                if((!$houseAdmin || $houseAdmin->isEmpty()) && !empty($worker['account'])){
                    $whereArr=array(['account','=',$worker['account']],['village_id','=',$worker['village_id']],['status','=',1]);
                    $whereArr[]=['wid','<',1];
                    $houseAdmin = $houseAdminDb->getOne($whereArr);
                    if((!$houseAdmin || $houseAdmin->isEmpty()) && !empty($worker['phone'])){
                        $whereArr=array(['phone','=',$worker['phone']],['village_id','=',$worker['village_id']],['status','=',1]);
                        $whereArr[]=['wid','<',1];
                        $houseAdmin = $houseAdminDb->getOne($whereArr);
                    }
                }
                if($houseAdmin && !$houseAdmin->isEmpty()){
                    $houseAdmin=$houseAdmin->toArray();
                    $relation_id=$houseAdmin['id'];
                    $this->HouseWorker->editData(array('wid'=>$wid),array('xtype'=>2,'relation_id'=>$relation_id));

                    $house_admin_arr=array();
                    $house_admin_arr['account']=$worker['account'];
                    $house_admin_arr['pwd']=$worker['password'];
                    $house_admin_arr['realname']=$worker['name'];
                    $house_admin_arr['phone']=$worker['phone'];
                    $house_admin_arr['menus']=!empty($worker['menus']) ? $worker['menus']:'';
                    $house_admin_arr['group_id']=$worker['group_id'];
                    $house_admin_arr['remarks']=$worker['remarks'];
                    $house_admin_arr['wid']=$wid;
                    if($is_worker_admin<1){
                        $house_admin_arr['status']=2;
                    }else{
                        $house_admin_arr['status']=1;
                    }
                    $houseAdminWhere=array('id'=>$relation_id);
                    $upres = $houseAdminDb->save_one($houseAdminWhere,$house_admin_arr);
                    if(isset($worker['hw_id']) && $worker['hw_id']>0){
                        $whereRArr=array(['hw_id','=',$worker['hw_id']],['village_id','=',$worker['village_id']]);
                        $whereRArr[]=array('wid','=',$wid);
                        $whereRArr[]=array('xtype','=',2);
                        $housePropertyVillageWorkerRelationDb->saveOneData($whereRArr,['had_id'=>$relation_id]);
                    }
                }else{
                    $house_admin_arr=array();
                    $house_admin_arr['village_id']=$worker['village_id'];
                    $house_admin_arr['account']=$worker['account'];
                    $house_admin_arr['pwd']=$worker['password'];
                    $house_admin_arr['realname']=$worker['name'];
                    $house_admin_arr['phone']=$worker['phone'];
                    $house_admin_arr['menus']=!empty($worker['menus']) ? $worker['menus']:'';
                    $house_admin_arr['group_id']=$worker['group_id'];
                    $house_admin_arr['remarks']=$worker['remarks'];
                    $house_admin_arr['wid']=$wid;
                    $house_admin_arr['time']=time();
                    if($is_worker_admin<1){
                        $house_admin_arr['status']=2;
                    }else{
                        $house_admin_arr['status']=1;
                    }
                    $relation_id = $houseAdminDb->addData($house_admin_arr);
                    if($relation_id>0){
                        $this->HouseWorker->editData(array('wid'=>$wid),array('xtype'=>2,'relation_id'=>$relation_id));
                        if(isset($worker['hw_id']) && $worker['hw_id']>0){
                            $whereRArr=array(['hw_id','=',$worker['hw_id']],['village_id','=',$worker['village_id']]);
                            $whereRArr[]=array('wid','=',$wid);
                            $whereRArr[]=array('xtype','=',2);
                            $housePropertyVillageWorkerRelationDb->saveOneData($whereRArr,['had_id'=>$relation_id]);
                        }
                    }
                }
            }
            return ['error'=>true,'msg'=>'修改成功！','wid'=>$wid];
        }
        else{
            //添加
            $datas['village_id']=$village_id;
            $datas['property_id']=$property_id;
            $datas['openid']='';
            $datas['nickname']='';
            $datas['avatar']='';
            $datas['create_time']=time();
            $datas['reply_count']='';
            $datas['score_all']='';
            $datas['score_mean']='';
            $datas['num']='';
            $datas['login_time']=0;
            if($xtype=='village'){
                $datas['xtype']=2;
            }else if($xtype=='property'){
                $datas['xtype']=1;
            }
            if($xtype=='property'){
                //物业
                $propertyAdminDb = new PropertyAdmin();
                $propertyWhere=array('account'=>$datas['account'],'status'=>1);
                $propertyArr=$propertyAdminDb->get_one($propertyWhere);
                if ($propertyArr && !$propertyArr->isEmpty()) {
                    return ['error'=>false,'msg'=>'该人员【'.$datas['account'].'】账号已存在！请换个账号。'];
                }
                $wid=$this->HouseWorker->addData($datas);
                if(empty($wid) || $wid<1){
                    return ['error'=>false,'msg'=>'组织架构数据保存失败了！'];
                }
                $property_admin_arr=array();
                $property_admin_arr['property_id']=$datas['property_id'];
                $property_admin_arr['account']=$datas['account'];
                $property_admin_arr['pwd']=$datas['password'];
                $property_admin_arr['realname']=$datas['name'];
                $property_admin_arr['phone']=$datas['phone'];
                $property_admin_arr['wid']=$wid;
                $property_admin_arr['remarks']=$datas['remarks'];
                $property_admin_arr['time']=time();
                $property_admin_arr['wid']=$wid;

                $relation_id = $propertyAdminDb->addData($property_admin_arr);
                if($relation_id>0){
                    $this->HouseWorker->editData(array('wid'=>$wid),array('relation_id'=>$relation_id));
                }
            }
            elseif($xtype=='village'){
                //小区
                $houseAdminDb = new HouseAdmin();
                $houseAdminWhere=array('account'=>$datas['account']);
                $houseAdminArr=$houseAdminDb->getOne($houseAdminWhere);
                if ($houseAdminArr && !$houseAdminArr->isEmpty()) {
                    $houseAdminArr=$houseAdminArr->toArray();
                    if(($houseAdminArr['status']==1 || $houseAdminArr['status']==2) && ($houseAdminArr['wid']>0)){
                        return ['error'=>false,'msg'=>'该人员【'.$datas['account'].'】账号已存在！请换个账号。'];
                    }
                }
                $wid=$this->HouseWorker->addData($datas);
                if(empty($wid) || $wid<1){
                    return ['error'=>false,'msg'=>'组织架构数据保存失败了！'];
                }
                $house_admin_arr=array();
                $house_admin_arr['village_id']=$datas['village_id'];
                $house_admin_arr['account']=$datas['account'];
                $house_admin_arr['pwd']=$datas['password'];
                $house_admin_arr['realname']=$datas['name'];
                if(isset($datas['menus'])){
                    $house_admin_arr['menus']=$datas['menus'];
                }else{
                    $house_admin_arr['menus']='';
                }
                if(isset($datas['group_id'])){
                    $house_admin_arr['group_id']=$datas['group_id'];
                }else{
                    $house_admin_arr['group_id']='';
                }
                $house_admin_arr['phone']=$datas['phone'];
                $house_admin_arr['remarks']=$datas['remarks'];
                $house_admin_arr['time']=time();
                $house_admin_arr['wid']=$wid;
                $house_admin_arr['email']='';
                $house_admin_arr['last_ip']='';
                $house_admin_arr['last_time']='';
                $house_admin_arr['login_count']='';
                $house_admin_arr['device_id']='';
                if($is_worker_admin<1){
                    $house_admin_arr['status']=2;
                }else{
                    $house_admin_arr['status']=1;
                }
                $houseAdminDb = new HouseAdmin();
                $relation_id = $houseAdminDb->addData($house_admin_arr);
                if($relation_id>0){
                    $this->HouseWorker->editData(array('wid'=>$wid),array('relation_id'=>$relation_id));
                }
            }
            if($is_param){
                return ['error'=>true,'msg'=>'操作成功！','wid'=>$wid];
            }else{
                return ['error'=>true,'msg'=>'添加成功！','wid'=>$wid];
            }
        }

    }

    //保存和更新工作人员数据
    public function synAdminToWorker($village_id=0,$admin_id=0,$department_id=0,$property_id=0){
        $houseAdminDb = new HouseAdmin();
        $houseAdminWhere=array('id'=>$admin_id,'village_id'=>$village_id);
        $houseAdminArr=$houseAdminDb->getOne($houseAdminWhere);
        if ($houseAdminArr && !$houseAdminArr->isEmpty()) {
            $houseAdminArr=$houseAdminArr->toArray();
            $whereArr=array('account'=>$houseAdminArr['account'],'is_del'=>0);
            $worker=$this->HouseWorker->get_one($whereArr);
            if(!empty($worker) && !$worker->isEmpty()){
                throw new \think\Exception('要同步的数据账号已经存在了！');
            }else{
                $propertyGroupWhere=array('id'=>$department_id,'property_id'=>$property_id);
                $departmentObj=$this->PropertyGroup->getOne($propertyGroupWhere);
                $type=-1;
                if($departmentObj && !$departmentObj->isEmpty()){
                    $department=$departmentObj->toArray();
                    if($department['section_status']!==0 && $department['section_status'] == null ){
                        $type=-1;
                    }else{
                        $type = intval($department['section_status'])>=0 ? intval($department['section_status']) : -1;
                    }
                }
                $datas=array();
                $datas['village_id']=$village_id;
                $datas['phone']=$houseAdminArr['phone'];
                $datas['name']=$houseAdminArr['realname'];
                $datas['status']=1;
                $datas['create_time']=time();
                $datas['type']=$type;
                $datas['num']=0;
                $datas['gender']=0;
                $datas['property_id']=$property_id;
                $datas['people_type']=1;
                $datas['department_id']=$department_id;
                $datas['account']=$houseAdminArr['account'];
                $datas['password']=$houseAdminArr['pwd'];
                $datas['remarks']=$houseAdminArr['remarks'];
                $datas['menus']=!empty($houseAdminArr['menus']) ? $houseAdminArr['menus']:'';
                $datas['group_id']= $houseAdminArr['group_id'];
                $datas['xtype']= 2;
                $datas['relation_id']= $admin_id;
                $wid=$this->HouseWorker->addData($datas);
                if(empty($wid) || $wid<1){
                    throw new \think\Exception('同步组织架构数据保存失败了！');
                }
                $upres = $houseAdminDb->save_one(['id'=>$admin_id],['wid'=>$wid]);
                return ['wid'=>$wid];
            }
        }else{
            throw new \think\Exception('要同步的数据不存在！');
        }

    }

	/**
	 * 获取物业的企业微信绑定配置信息
	 * @param      $where
	 * @param bool $filed
	 *
	 * @return array|\think\Model|null
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\DbException
	 * @throws \think\db\exception\ModelNotFoundException
	 */
	public function getEnterpriseWxBind($where,$filed = true)
	{
		return $this->HouseEnterpriseWxBind->where($where)->field($filed)->find();
	}
}