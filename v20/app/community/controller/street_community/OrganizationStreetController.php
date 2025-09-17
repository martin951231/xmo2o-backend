<?php
/**
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/5/27 15:12
 */

namespace app\community\controller\street_community;

use app\community\controller\CommunityBaseController;
use app\community\model\service\AreaService;
use app\community\model\service\AreaStreetService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\OrganizationStreetService;
use app\community\model\service\RecognitionService;
use app\community\model\service\StreetCommunityMenuService;

class OrganizationStreetController extends CommunityBaseController
{
    //组织架构
    public function getTissueNav()
    {
        $serviceOrganizationStreet = new OrganizationStreetService();
        $street_id = $this->adminUser['area_id'];
        try{
            $list = $serviceOrganizationStreet->getNav($street_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
    //添加编辑部门
    public function addOrganization()
    {
        $street_id = $this->adminUser['area_id'];
        $name = $this->request->param('name','','trim');
        $sort = $this->request->param('sort','','int');
        $des = $this->request->param('des','','trim');
        $id = $this->request->param('id','','int');
        $fid = $this->request->param('fid','','int');
        if(!$name){
            return api_output_error(1001,'必传参数缺失');
        }
        $serviceOrganizationStreet = new OrganizationStreetService();
        $data = [
            'name'=>$name,
            'sort'=>$sort,
            'des'=>$des,
            'area_id'=>$street_id,
            'area_type'=>0,
            'fid'=>$fid,
        ];
        try{
            $res = $serviceOrganizationStreet->subBranch($data,$id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }
    //部门详情
    public function getBranchInfo()
    {
        $street_id = $this->adminUser['area_id'];
        $serviceOrganizationStreet = new OrganizationStreetService();
        if(!$street_id)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        $id = $this->request->param('id','','intval');
        if(!$id){
            return api_output_error(1001,'必传参数缺失');
        }
        try{
            $data = $serviceOrganizationStreet->getNavInfo($id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$data);
    }
    //删除部门
    public function delBranch()
    {
        $id = $this->request->param('id',0,'int');
        if(!$id){
            return api_output_error(1001,'必传参数缺失');
        }
        $serviceOrganizationStreet = new OrganizationStreetService();
        try{
            $res = $serviceOrganizationStreet->delBranch($id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }
    //----------------------------------------------------------

    /**
     * 工作人员列表
     * @author lijie
     * @date_time 2021/02/26
     * @return \json
     */
    public function getMemberList()
    {
        $serviceOrganizationStreet = new OrganizationStreetService();
        $street_id = $this->adminUser['area_id'];
        $branch_id = $this->request->param('id','','int');
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',15);
        $search = $this->request->post('con','');
        try{
            if($search){
                $service_organization_street = new OrganizationStreetService();
                $info = $service_organization_street->getOrganizationInfo(['name'=>$search,'is_del'=>0],'id');
                $where=array();
                $where[] = ['area_id','=',$street_id];
                $where[] = ['work_status','=',1];
                $where[] = ['work_name|work_phone','=',$search];
                $list = $serviceOrganizationStreet->getMemberList($where,'*','worker_id DESC',$page,$limit,0);
                $count = $serviceOrganizationStreet->getWorkersCount($where,0);
                if($count == 0 && $info['id']){
                    $where=array();
                    $where[] = ['area_id','=',$street_id];
                    $where[] = ['work_status','=',1];
                    $list = $serviceOrganizationStreet->getMemberList($where,'*','worker_id DESC',$page,$limit,$info['id']);
                    $count = $serviceOrganizationStreet->getWorkersCount($where,$info['id']);
                    $res['organization_id'] = $info['id'];
                }
            }else{
                $list = $serviceOrganizationStreet->getMemberList(['area_id'=>$street_id,'work_status'=>1],'*','worker_id DESC',$page,$limit,$branch_id);
                $count = $serviceOrganizationStreet->getWorkersCount(['area_id'=>$street_id,'work_status'=>1],$branch_id);
            }
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        $res['list'] = $list;
        $res['count'] = $count;
        $res['total_limit'] = 15;
        $res['street_id'] = $street_id;
        $res['login_role'] = $this->login_role;
        return api_output(0,$res);
    }

    /**
     * 工作人员信息
     * @author lijie
     * @date_time 2021/02/26
     * @return \json
     */
    public function getMemberInfo()
    {
        $serviceOrganizationStreet = new OrganizationStreetService();
        $worker_id = $this->request->param('worker_id','','int');
        $is_edit = $this->request->param('is_edit',1,'int');   //2查看
        try{
            $data = $serviceOrganizationStreet->getMemberInfo($worker_id);
            $data['info']['work_passwd'] = '';
            if($data['info']['work_head']){
                $data['info']['img'] = $data['info']['work_head'];
                $data['info']['work_head'] = replace_file_domain($data['info']['work_head']);
            }
            if($data['info']['organization_ids']==0) {
                $data['info']['organization_ids'] = '';
            }
            if($data['info']['entry_time']>1) {
                $data['info']['entry_time'] = date('Y-m-d',$data['info']['entry_time']);
            } else {
                $data['info']['entry_time'] = '';
            }
            if($data['info']['join_party_time']>1) {
                $data['info']['join_party_time'] = date('Y-m-d',$data['info']['join_party_time']);
            } else {
                $data['info']['join_party_time'] = '';
            }
            if(isset($data['info']['village_ids']) && !empty($data['info']['village_ids'])){
                $data['info']['village_ids']=explode(',',$data['info']['village_ids']);
            }else{
                $data['info']['village_ids']=array();
            }
            if($is_edit==2){
                if($data['info']['work_phone']){
                    $data['info']['work_phone']=phone_desensitization($data['info']['work_phone']);
                }
                if($data['info']['work_id_card']){
                    $data['info']['work_id_card']=idnum_desensitization($data['info']['work_id_card']);
                }
            }
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 添加工作人员
     * @author lijie
     * @date_time 2021/02/26
     * @return \json
     */
    public function subMemberBranch()
    {
        $street_id = $this->adminUser['area_id'];
        $service_area_street = new AreaStreetService();
        $area_info = $service_area_street->getAreaStreet(['area_id'=>$street_id],'area_type');
        $postParams = $this->request->param();
        $postParams['add_time'] = time();
        $postParams['area_id'] = $street_id;
        $postParams['area_type'] = $area_info['area_type'];
        $postParams['entry_time'] = $postParams['entry_time']?$postParams['entry_time']:1;
        $postParams['organization_ids'] = rtrim($postParams['organization_ids'],',');
        $postParams['work_passwd'] = md5($postParams['work_passwd']);
        $service_organization_street = new OrganizationStreetService();
        if(!empty($postParams['work_account'])){
            $info = $service_organization_street->getMemberDetail(['work_account'=>$postParams['work_account'],'work_status'=>1]);
            if($info)
                return api_output_error(1001,'该账号已存在');
        }
        if(!empty($postParams['work_phone'])){
            $info = $service_organization_street->getMemberDetail(['work_phone'=>$postParams['work_phone'],'work_status'=>1]);
            if($info)
                return api_output_error(1001,'该手机号已存在');
        }
        if(!empty($postParams['work_id_card'])){
            if(!is_idcard($postParams['work_id_card'])){
                return api_output_error(1001,'请输入正确的身份证号');
            }
        }
        $res = $service_organization_street->addStreetWorker($postParams);
        return api_output(0,$res);
    }

    /**
     * 编辑工作人员
     * @author lijie
     * @date_time 2021/02/26
     * @return \json
     */
    public function saveStreetWorker()
    {
        $postParams = $this->request->param();
        $postParams['last_time'] = time();
        $postParams['organization_ids'] = rtrim($postParams['organization_ids'],',');
        if($postParams['work_passwd'])
            $postParams['work_passwd'] = md5($postParams['work_passwd']);
        else
            unset($postParams['work_passwd']);
        $service_organization_street = new OrganizationStreetService();
        if($postParams['work_account']){
            $info = $service_organization_street->getMemberDetail([['work_account','=',$postParams['work_account']],['worker_id','<>',$postParams['worker_id']],['work_status','=',1]]);
            if($info)
                return api_output_error(1001,'该账号已存在');
        }
        if(!empty($postParams['work_phone'])){
            $info = $service_organization_street->getMemberDetail([['work_phone','=',$postParams['work_phone']],['worker_id','<>',$postParams['worker_id']],['work_status','=',1]]);
            if($info)
                return api_output_error(1001,'该手机号已存在');
        }
        if(!empty($postParams['work_id_card'])){
            if(!is_idcard($postParams['work_id_card'])){
                return api_output_error(1001,'请输入正确的身份证号');
            }
        }
        if(is_null($postParams['entry_time'])){
            unset($postParams['entry_time']);
        }
        if(isset($postParams['join_party_time']) && $postParams['join_party_time']){
            $postParams['join_party_time']=strtotime($postParams['join_party_time']);
            $postParams['join_party_time']=$postParams['join_party_time']>0 ? $postParams['join_party_time']:3600;
        }
        $res = $service_organization_street->saveStreetWorker(['worker_id'=>$postParams['worker_id']],$postParams);
        return api_output(0,$res);
    }

    public function saveStreetRolePermission(){
        $worker_id = $this->request->post('worker_id',0);
        if(!$worker_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $xtype = $this->request->post('xtype',0);
        $service_organization_street = new OrganizationStreetService();
        $worker_id=intval($worker_id);
        $street_id = $this->adminUser['area_id'];
        $whereArr=[];
        $whereArr[]=['worker_id','=',$worker_id];
        $whereArr[]=['area_id','=',$street_id];
        $info = $service_organization_street->getMemberDetail($whereArr);
        if(empty($info)){
            return api_output_error(1001,'人员信息不存在！');
        }else if($info['work_status']==4){
            return api_output_error(1001,'人员信息已被删除！');
        }

        if($xtype==1){
            //小区
            $village_ids = $this->request->post('village_ids','');
            if(!empty($village_ids)){
                $village_ids=explode(',',$village_ids);
                $village_ids=array_unique($village_ids);
                $village_ids=implode(',',$village_ids);
            }else{
                $village_ids='';
            }
            $res = $service_organization_street->saveStreetWork(['worker_id'=>$worker_id],['village_ids'=>$village_ids]);
        }else if($xtype==2){
            //菜单
            $menus = $this->request->post('menus','');
            $res = $service_organization_street->saveStreetWork(['worker_id'=>$worker_id],['menus'=>$menus]);
        }else{
            return api_output_error(1001,'操作不合法！');
        }

        return api_output(0,$info);
    }

    /**
     * 删除工作人员
     * @author lijie
     * @date_time 2021/02/26
     * @return bool|\json
     * @throws \Exception
     */
    public function delWorker()
    {
        $worker_id = $this->request->post('worker_id',0);
        if(!$worker_id)
            return api_output_error(1001,'必传参数缺失');
        if(is_array($worker_id)){
            $worker_id_arr = [];
            foreach ($worker_id as $v){
                $worker_id_arr[] = $v['worker_id'];
            }
            $where[] = ['worker_id','in',$worker_id_arr];
        }else{
            $where[] = ['worker_id','=',$worker_id];
        }
        $street_id = $this->adminUser['area_id'];
        $where[]=['area_id','=',$street_id];
        $service_organization_street = new OrganizationStreetService();
        $res = $service_organization_street->delStreetWorker($where);
        return api_output(0,[]);
    }

    public function getTissueNavList()
    {
        $serviceOrganizationStreet = new OrganizationStreetService();
        $street_id = $this->adminUser['area_id'];
        try{
            $list = $serviceOrganizationStreet->getTissueNavList($street_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    //获取省市区信息
    public function getProvinceCityAreas(){
        $pid = $this->request->post('pid', 0, 'int');
        $xtype = $this->request->post('xtype', 0, 'int');   //0 省 1市 2区县
        $areaService=new AreaService();
        $whereArr=array('is_open'=>1);
        if($xtype==0){
            $whereArr['area_type']=1;
        }elseif($xtype==1 && $pid<1){
            return api_output_error(1001, '您选择的省信息错误');
        }elseif($xtype==2 && $pid<1){
            return api_output_error(1001, '您选择的市信息错误');
        }else{
            $whereArr['area_pid']=$pid;
        }
        try {
            $fieldStr='area_id as id,area_name as name';
            $tmpData=$areaService->getAreaList($whereArr,$fieldStr);
            return api_output(0, $tmpData);
        }catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    //获取物业小区
    public function getStreetXillages()
    {

        $limit = $this->request->param('limit', 10, 'int');
        $page = $this->request->param('page', 0, 'int');
        $street_id = $this->request->param('street_id', '0', 'int');
        if(isset($this->adminUser['area_id'])){
            $street_id = $this->adminUser['area_id'];
        }
        $village_name = $this->request->param('keyword', '', 'trim');
        $village_name = htmlspecialchars($village_name, ENT_QUOTES);
        $province_id = $this->request->param('province_id', '', 'int');
        $city_id = $this->request->param('city_id', '', 'int');
        $area_id = $this->request->param('area_id', '', 'int');
        $condition_where = array();
        if (!empty($village_name)) {
            $condition_where[] = array('village_name', 'like', '%' . $village_name . '%');
        }
        if (!empty($province_id)) {
            $condition_where[] = array('province_id', '=', $province_id);
        }
        if (!empty($city_id)) {
            $condition_where[] = array('city_id', '=', $city_id);
        }
        if (!empty($area_id)) {
            $condition_where[] = array('area_id', '=', $area_id);
        }
        $condition_where[] = array('street_id', '=', $street_id);
        $condition_where[] = array('status', '<>', 5);
        try {
            $houseVillageService = new HouseVillageService();
           /* $limit = 10;*/
            $page = $page > 0 ? $page : 1;
            $dataArr = ['list' => array(), 'total_limit' => $limit, 'count' => 0];
            $fieldStr='village_id,property_id,account,area_id,city_id,community_id,property_address,property_name,property_phone,village_address,village_name,village_logo';
            $list = $houseVillageService->getList($condition_where, $fieldStr, $page, $limit, 'village_id DESC',1);
            if ($list && !$list->isEmpty()) {
                $list = $list->toArray();
                foreach ($list as $kk => $vv) {
                    $list[$kk]['key'] = $vv['village_id'];
                }
                $dataArr['list'] = $list;
                $count = $houseVillageService->getVillageNum($condition_where);
                $dataArr['count'] = $count;
                $dataArr['login_role'] = $this->login_role;
                $dataArr['adminUser'] = $this->adminUser;
            }
            return api_output(0, $dataArr);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function getStreetRolePermission()
    {
        $street_id = $this->adminUser['area_id'];
        $worker_id = $this->request->param('worker_id', 0, 'int');
        $worker_id=intval($worker_id);
        try {
            $streetCommunityMenuService = new StreetCommunityMenuService();
            $menus = $streetCommunityMenuService->getStreetRolePermissionData($this->adminUser);
            $role_menus = array();
            if ($worker_id> 0) {
                $service_organization_street = new OrganizationStreetService();
                $street_id = $this->adminUser['area_id'];
                $whereArr=[];
                $whereArr[]=['worker_id','=',$worker_id];
                $whereArr[]=['area_id','=',$street_id];
                $info = $service_organization_street->getMemberDetail($whereArr);
                if (!empty($info) && !empty($info['menus'])) {
                    $role_menus = explode(',', $info['menus']);
                }
            }
            $list = $streetCommunityMenuService->getTrees($menus, $role_menus);
            $returnArr = array();
            $returnArr['role_menus'] = $role_menus;
            $returnArr['menus'] = $list['tree'];
            $returnArr['mckeyArr'] = $list['ckeyArr'];
            return api_output(0, $returnArr);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }


    /**
     * 街道扫码绑定微信公众号
     * @author: liukezhu
     * @date : 2022/8/23
     * @return \json
     */
    public function getRecognition(){
        $qrcode_id =  $this->request->param('qrcode_id',0,'int');
        if($qrcode_id && $qrcode_id<200000000){
            return api_output_error(1000,'二维码场景id错误！');
        }
        try {
            $service_recognition = new RecognitionService();
            $data = $service_recognition->getTmpQrcode($qrcode_id);
            return api_output(0,$data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * 校验工作人员是否绑定微信公众号
     * @author: liukezhu
     * @date : 2022/8/23
     * @return \json
     */
    public function checkWorker(){
        $wid = $this->request->post('wid', '0', 'int');
        if(!$wid){
            return api_output_error(1001, '缺少必要参数！');
        }
        try{
            $res = (new OrganizationStreetService())->checkWorker($this->adminUser['area_id'],$wid);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }


    /**
     * 取消工作人员绑定微信
     * @author: liukezhu
     * @date : 2022/9/1
     * @return \json
     */
    public function cancelWorkerBind(){
        $wid = $this->request->post('wid', '0', 'int');
        if(!$wid){
            return api_output_error(1001, '缺少必要参数！');
        }
        try{
            $res = (new OrganizationStreetService())->cancelWorkerBind($this->adminUser['area_id'],$wid);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }


}
