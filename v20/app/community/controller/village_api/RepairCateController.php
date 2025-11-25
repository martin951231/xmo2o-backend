<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/8/12 15:26
 */
namespace app\community\controller\village_api;

use app\common\model\service\CacheSqlService;
use app\common\model\service\config\ConfigCustomizationService;
use app\community\controller\CommunityBaseController;
use app\community\controller\manage_api\BaseController;
use app\community\model\db\HouseWorker;
use app\community\model\service\HouseNewRepairService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseVillageUserBindService;
use app\community\model\service\HouseVillageUserVacancyService;
use app\community\model\service\HouseWorkerService;
use app\community\model\service\RepairCateService;
use app\community\model\service\HouseProgrammeService;
use app\community\model\service\workorder\HouseCommunityAboutService;
use app\community\model\service\workorder\HouseCommunityQualityService;
use app\traits\CommonLogTraits;
use app\traits\house\AdminLoginTraits;
class RepairCateController extends CommunityBaseController
{
    use CommonLogTraits;
    use AdminLoginTraits;
    /**
     * 查询类目列表
     * @author:zhubaodi
     * @date_time: 2021/8/12 18:40
     */
    public function getSubjectList(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['property_id']=$this->adminUser['property_id'];
        $data['page'] = $this->request->param('page', 0, 'intval');
        $data['limit'] = 20;
        $serviceRepairCate = new RepairCateService();
        try{
            $res = $serviceRepairCate->getSubjectList($data);
            $res['total_limit'] = $data['limit'];
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);

    }



    /**
     * 查询类目详情
     * @author:zhubaodi
     * @date_time: 2021/8/12 18:40
     */
    public function getSubjectInfo(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['property_id']=$this->adminUser['property_id'];
        $data['id'] = $this->request->param('id', 0, 'intval');
        $serviceRepairCate = new RepairCateService();
        try{
            $res = $serviceRepairCate->getSubjectInfo($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);

    }

    /**
     * 添加类目
     * @author:zhubaodi
     * @date_time: 2021/8/12 18:40
     */
    public function addSubject(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['property_id']=$this->adminUser['property_id'];
        $data['subject_name'] = $this->request->param('name', '', 'trim');
        $data['color'] = $this->request->param('color', '', 'trim');
        $data['status'] = $this->request->param('status', 0, 'intval');
        if (empty($data['subject_name'])) {
            return api_output_error(1001, '类目名称不能为空');
        }
        if (empty($data['color'])) {
            return api_output_error(1001, '背景色不能为空');
        }
        if (empty($data['status'])) {
            return api_output_error(1001, '状态不能为空');
        }
        $serviceRepairCate = new RepairCateService();
        try{
            $id = $serviceRepairCate->addSubject($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if ($id){
            return api_output(0,['id' => $id],'添加成功');
        }else{
            return api_output(1003,[],'添加失败！');
        }

    }

    /**
     * 编辑类目
     * @author:zhubaodi
     * @date_time: 2021/8/12 18:40
     */
    public function editSubject(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['property_id']=$this->adminUser['property_id'];
        $data['subject_name'] = $this->request->param('name', '', 'trim');
        $data['color'] = $this->request->param('color', '', 'trim');
        $data['status'] = $this->request->param('status', 0, 'intval');
        $data['id'] = $this->request->param('id', 0, 'intval');
        if (empty($data['subject_name'])) {
            return api_output_error(1001, '类目名称不能为空');
        }
        if (empty($data['color'])) {
            return api_output_error(1001, '背景色不能为空');
        }
        if (empty($data['status'])) {
            return api_output_error(1001, '状态不能为空');
        }
        if (empty($data['id'])) {
            return api_output_error(1001, 'id不能为空');
        }
        $serviceRepairCate = new RepairCateService();
        try{
            $res = $serviceRepairCate->editSubject($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if ($res){
            return api_output(0,['res' => $res],'编辑成功');
        }else{
            return api_output(1003,[],'编辑失败！');
        }

    }



    /**
     * 查询类别列表
     * @author:zhubaodi
     * @date_time: 2021/8/12 18:40
     */
    public function getCategoryList(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['property_id']=$this->adminUser['property_id'];
        $data['parent_id'] = $this->request->param('subject_id', 0, 'intval');
        $data['page'] = $this->request->param('page', 0, 'intval');
        $data['limit'] = 20;
        $serviceRepairCate = new RepairCateService();
        try{
            $res = $serviceRepairCate->getCategoryList($data);
            $res['total_limit'] = $data['limit'];
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);

    }



    /**
     * 查询类别详情
     * @author:zhubaodi
     * @date_time: 2021/8/12 18:40
     */
    public function getCategoryInfo(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['property_id']=$this->adminUser['property_id'];
        $data['id'] = $this->request->param('id', 0, 'intval');
        if (empty($data['id'])) {
            return api_output_error(1001, '必填参数缺失');
        }
        $serviceRepairCate = new RepairCateService();
        try{
            $res = $serviceRepairCate->getSubjectInfo($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);

    }

    /**
     * 添加类别
     * @author:zhubaodi
     * @date_time: 2021/8/12 18:40
     */
    public function addCategory(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['property_id']=$this->adminUser['property_id'];
        $data['subject_name'] = $this->request->param('name', '', 'trim');
        $data['status'] = $this->request->param('status', 0, 'intval');
        $data['parent_id'] = $this->request->param('subject_id', '', 'intval');
        if (empty($data['subject_name'])) {
            return api_output_error(1001, '类别名称不能为空');
        }
        if (empty($data['status'])) {
            return api_output_error(1001, '状态不能为空');
        }
        if (empty($data['parent_id'])) {
            return api_output_error(1001, '父级id不能为空');
        }
        $serviceRepairCate = new RepairCateService();
        try{
            $id = $serviceRepairCate->addCategory($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if ($id){
            return api_output(0,['id' => $id],'添加成功');
        }else{
            return api_output(1003,[],'添加失败！');
        }


    }

    /**
     * 编辑类别
     * @author:zhubaodi
     * @date_time: 2021/8/12 18:40
     */
    public function editCategory(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['property_id']=$this->adminUser['property_id'];
        $data['subject_name'] = $this->request->param('name', '', 'trim');
        $data['parent_id'] = $this->request->param('subject_id', '', 'intval');
        $data['status'] = $this->request->param('status', 0, 'intval');
        $data['id'] = $this->request->param('id', 0, 'intval');
        if (empty($data['subject_name'])) {
            return api_output_error(1001, '类别名称不能为空');
        }
        if (empty($data['status'])) {
            return api_output_error(1001, '状态不能为空');
        }
        if (empty($data['parent_id'])) {
            return api_output_error(1001, '父级id不能为空');
        }
        if (empty($data['id'])) {
            return api_output_error(1001, 'id不能为空');
        }
        $serviceRepairCate = new RepairCateService();
        try{
            $res = $serviceRepairCate->editCategory($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if ($res){
            return api_output(0,['res' => $res],'编辑成功');
        }else{
            return api_output(1003,[],'编辑失败！');
        }
    }


    /**
     * 删除类别
     * @author:zhubaodi
     * @date_time: 2021/8/12 18:40
     */
    public function delCategory(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['property_id']=$this->adminUser['property_id'];
        $data['id'] = $this->request->param('id', 0, 'intval');
        if (empty($data['id'])) {
            return api_output_error(1001, '必填参数缺失');
        }
        $serviceRepairCate = new RepairCateService();
        try{
            $res = $serviceRepairCate->delCategory($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if ($res){
            return api_output(0,['res' => $res],'删除成功');
        }else{
            return api_output(1003,[],'删除失败！');
        }

    }





    //============================= todo 工单优化

    //todo 查询分类列表
    public function getCateList(){
        $parent_id = $this->request->param('parent_id', 0, 'intval');
        $data['page'] = $this->request->param('page', 0, 'intval');
        $data['limit'] = 10;
        $where[] = ['village_id','=',$this->adminUser['village_id']];
        $where[] = ['parent_id','=',$parent_id];
        $where[] = ['status','in',[1,2]];
        $serviceRepairCate = new RepairCateService();
        try{
            $serviceRepairCate->checkRepairCate($this->adminUser['property_id'],$this->adminUser['village_id']);
            $res = $serviceRepairCate->getCateList($where,$data);
            $res['total_limit'] = $data['limit'];
            $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
            $addCategory = 1;
            $editCategory = 1;
            $categoryManagement = 1;
            $deleteCategory = 1;
            $addCategory2 = 1;
            $editCategory2 = 1;
            $deleteCategory2 = 1;
            $lookLabel2 = 1;
            $addLabel2 = 1;
            $editLabel2 = 1;
            $deleteLabel2 = 1;
            if ($jz_open) {
                $authMenus = isset($this->adminUser['menus']) ? $this->adminUser['menus'] : [];
                if ($authMenus && !is_array($authMenus)) {
                    $authMenus = explode(',', $authMenus);
                }
                if (!empty($authMenus) && !in_array(113001, $authMenus)) {
                    $addCategory = 0;
                }
                if (!empty($authMenus) && !in_array(113002, $authMenus)) {
                    $editCategory = 0;
                }
                if (!empty($authMenus) && !in_array(113003, $authMenus)) {
                    $categoryManagement = 0;
                }
                if (!empty($authMenus) && !in_array(113004, $authMenus)) {
                    $deleteCategory = 0;
                }
                if (!empty($authMenus) && !in_array(113005, $authMenus)) {
                    $addCategory2 = 0;
                }
                if (!empty($authMenus) && !in_array(113006, $authMenus)) {
                    $lookLabel2 = 0;
                }
                if (!empty($authMenus) && !in_array(113007, $authMenus)) {
                    $addLabel2 = 0;
                }
                if (!empty($authMenus) && !in_array(113008, $authMenus)) {
                    $editLabel2 = 0;
                }
                if (!empty($authMenus) && !in_array(113009, $authMenus)) {
                    $deleteLabel2 = 0;
                }
                if (!empty($authMenus) && !in_array(113016, $authMenus)) {
                    $editCategory2 = 0;
                }
                if (!empty($authMenus) && !in_array(113017, $authMenus)) {
                    $deleteCategory2 = 0;
                }
            }
            $auth = [];
            $auth['addCategory'] = $addCategory;
            $auth['editCategory'] = $editCategory;
            $auth['categoryManagement'] = $categoryManagement;
            $auth['deleteCategory'] = $deleteCategory;
            $auth['addCategory2'] = $addCategory2;
            $auth['editCategory2'] = $editCategory2;
            $auth['deleteCategory2'] = $deleteCategory2;
            $auth['lookLabel2'] = $lookLabel2;
            $auth['addLabel2'] = $addLabel2;
            $auth['editLabel2'] = $editLabel2;
            $auth['deleteLabel2'] = $deleteLabel2;
            $res['auth'] = $auth;
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);

    }

    public function deleteHouseNewRepairCate()
    {
        $cate_id = $this->request->param('cate_id', 0, 'intval');
        $result = [];
        try {
            $whereNewRepairCate = [];
            $whereNewRepairCate[] = ['id', '=', $cate_id];
            $data = ['status' => 4, 'update_time' => time()];
            $serviceRepairCate = new RepairCateService();
            $queryRepairCate = $serviceRepairCate->queryRepairCate($whereNewRepairCate, 'id,parent_id,status');
            $result['info'] = $serviceRepairCate->editRepairCate($whereNewRepairCate, $data);
            if (isset($queryRepairCate['parent_id']) && intval($queryRepairCate['parent_id']) <= 0) {
                $whereParentNewRepairCate = [];
                $whereParentNewRepairCate[] = ['parent_id', '=', $cate_id];
                $result['parentInfo'] = $serviceRepairCate->editRepairCate($whereParentNewRepairCate, $data);
            }
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }

    //todo 一级分类添加
    public function newRepairCateAdd(){
        $name = $this->request->param('name', '', 'trim');
        $color = $this->request->param('color', '', 'trim');
        $status = $this->request->param('status', 1, 'intval');
        $parent_id = $this->request->param('parent_id', 0, 'intval');
        $group_id_all = $this->request->param('group_id_all', []);
        if(empty($name)){
            return api_output_error(1001, '请输入类目名称');
        }
        if(empty($color)){
            return api_output_error(1001, '请选择背景色');
        }
        $data=[
            'cate_name'=>$name,
            'parent_id'=>$parent_id,
            'status'=>$status,
            'village_id'=>$this->adminUser['village_id'],
            'property_id'=>$this->adminUser['property_id'],
            'color'=>$color
        ];
        $serviceRepairCate = new RepairCateService();
        try{
            $serviceRepairCate->checkRepairCateOnly($this->adminUser['village_id'],$name);
            $id = $serviceRepairCate->addRepairCate($data);
            $serviceRepairCate->addCateGroup(1,$this->adminUser['village_id'],$id,$group_id_all);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if ($id){
            (new CacheSqlService())->clearCache();
            return api_output(0,['res' => $id],'添加成功');
        }else{
            return api_output(1003,[],'添加失败！');
        }
    }

    //todo 一级分类编辑
    public function newRepairCateEdit(){
        $serviceRepairCate = new RepairCateService();
        $type = $this->request->param('type', 0, 'intval');
        $id = $this->request->param('id', 0, 'intval');
        $name = $this->request->param('name', '', 'trim');
        $color = $this->request->param('color', '', 'trim');
        $status = $this->request->param('status', 1, 'intval');
        $group_id_all = $this->request->param('group_id_all', []);
        $where[] = ['village_id','=',$this->adminUser['village_id']];
        $where[] = ['id','=',$id];
        $where[] = ["status",'<>',4];
        $cate = $serviceRepairCate->queryRepairCate($where,'id,cate_name,status,color');
        if(!$cate){
            return api_output_error(1001, '数据不存在');
        }
        if($type == 1){
            $cate['group_id_all']=$serviceRepairCate->getCateGroup($this->adminUser['village_id'],$id);
            return api_output(0,$cate);
        }
        else{
            if(empty($name)){
                return api_output_error(1001, '请输入类目名称');
            }
            if(empty($color)){
                return api_output_error(1001, '请选择背景色');
            }
            $data=[
                'cate_name'=>$name,
                'status'=>$status,
                'color'=>$color
            ];
            $where=[];
            $where[] = ['id','=',$id];
            try{
                $serviceRepairCate->checkRepairCateOnly($this->adminUser['village_id'],$name,$id);
                $serviceRepairCate->addCateGroup(2,$this->adminUser['village_id'],$id,$group_id_all);
                $id = $serviceRepairCate->editRepairCate($where,$data);
                return api_output(0,['res' => $id],'编辑成功');
            }catch (\Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
        }
    }


    /**
     * 查询分类详情
     * @author:zhubaodi
     * @date_time: 2021/8/12 18:40
     */
    public function getCateInfo(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['property_id']=$this->adminUser['property_id'];
        $data['id'] = $this->request->param('id', 0, 'intval');
        if (empty($data['id']) ) {
            return api_output_error(1001, '必填参数缺失');
        }
        $serviceRepairCate = new RepairCateService();
        try{
            $res = $serviceRepairCate->getCateInfo($data);
            $configCustomizationService=new ConfigCustomizationService();
            $jz_wsxdsj_customized_open = $configCustomizationService->getWSXCustomizedOpen();
            if ($jz_wsxdsj_customized_open) {
                $params = [
                    'village_id' => $this->adminUser['village_id'],
                    'property_id' => $this->adminUser['property_id'],
                    'repair_cate_id' => $data['id'],
                ];
                $bindInfo = (new HouseCommunityQualityService())->getRepairCateQuality($params);
                if (!empty($bindInfo)) {
                    $res['single_id'] = isset($bindInfo['single_id']) ? intval($bindInfo['single_id']) : 0;
                    $res['quality_id'] = isset($bindInfo['quality_id']) ? intval($bindInfo['quality_id']) : 0;
                    $res['not_timely_report_switch'] = isset($bindInfo['not_timely_report_switch']) ? intval($bindInfo['not_timely_report_switch']) : 0;
                } else {
                    $res['single_id'] = 0;
                    $res['quality_id'] = 0;
                    $res['not_timely_report_switch'] = 0;
                }
            }
            if (isset($res['other_village_cate_fid']) && intval($res['other_village_cate_fid']) <= 0) {
                $res['other_village_cate_fid'] = null;
            }
            if (isset($res['other_village_cate_id']) && intval($res['other_village_cate_id']) <= 0) {
                $res['other_village_cate_id'] = null;
            }
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);

    }


    /**
     * 添加分类
     * @author:zhubaodi
     * @date_time: 2021/8/16 10:48
     */
    public function addCate(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['property_id']=$this->adminUser['property_id'];
        $data['cate_name'] = $this->request->param('name', 0, 'trim');
        $data['parent_id'] = $this->request->param('parent_id', 0, 'intval');
        $data['sort'] = $this->request->param('sort', 0, 'intval');
        $data['status'] = $this->request->param('status', 0, 'intval');
        $data['type'] = $this->request->param('type', 0, 'intval');
        $data['timely_time'] = $this->request->param('timely_time', '');
        if (empty($data['cate_name'])) {
            return api_output_error(1001, '分类名称不能为空');
        }
        if (empty($data['status'])) {
            return api_output_error(1001, '状态不能为空');
        }
        if ($data['sort']<0){
            return api_output_error(1001, '排序值不能小于0');
        }
        if ($data['type']==1){
            $data['uid']=$this->request->param('uid', 0, 'intval');
        }
        if ($data['type']==2){
            $data['director_id']=$this->request->param('director_id', 0, 'trim');
            $data['scheduling']=$this->request->param('scheduling', 0, 'trim');
        }

        $configCustomizationService = new ConfigCustomizationService();
        $jz_open = $configCustomizationService->getWSXCustomizedOpen();
        // 是否转给供暖站 0-否 1-是
        $to_supply_station = $this->request->param('to_supply_station', 0, 'intval');
        $other_village_id = $this->request->param('other_village_id', 0, 'intval');
        $other_village_cate_id = $this->request->param('other_village_cate_id', 0, 'intval');
        $other_village_cate_fid = $this->request->param('other_village_cate_fid', 0, 'intval');

        $from_user = ['type' => 'village_admin', 'admin_id' => $this->adminUser['village_id'], 'account' => $this->adminUser['village_name']];
        if ($this->adminUser['user_name']) {
            $from_user['name'] = $this->adminUser['user_name'];
        } elseif ($this->adminUser['adminName']) {
            $from_user['name'] = $this->adminUser['adminName'];
        }
        if (isset($this->adminUser['phone']) && $this->adminUser['phone']) {
            $from_user['phone'] = $this->adminUser['phone'];
        }
        if ($this->_uid) {
            $from_user['_userId'] = $this->_uid;
        }
        if ($this->login_userId) {
            $from_user['login_userId'] = $this->login_userId;
        }
        if ($this->login_role) {
            $from_user['login_role'] = $this->login_role;
        }
        $from_user = json_encode($from_user, JSON_UNESCAPED_UNICODE);
        $nowTime = time();
        if ($jz_open) {
            if ($to_supply_station == 1) {
                $data['to_supply_station'] = $to_supply_station;
                $data['other_village_id'] = $other_village_id;
                $data['other_village_cate_id'] = $other_village_cate_id;
                $data['other_village_cate_fid'] = $other_village_cate_fid;
                $data['other_village_text'] = $from_user;
                $data['other_village_time'] = $nowTime;
                if (!$other_village_cate_id || !$other_village_cate_fid){
                    return api_output_error(1001, '请选择绑定的对应完整【供暖站分类】！');
                }
            } else {
                $data['to_supply_station'] = $to_supply_station;
                $data['other_village_id'] = 0;
                $data['other_village_cate_id'] = 0;
                $data['other_village_cate_fid'] = 0;
                $data['other_village_text'] = $from_user;
                $data['other_village_time'] = 0;
            }
        }
        $serviceRepairCate = new RepairCateService();
        try{
            $id = $serviceRepairCate->addCate($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if ($id){
            if ($jz_open) {
                // 判断是否有绑定质保 走质保
                $single_id = $this->request->param('single_id', 0, 'intval');
                $quality_id = $this->request->param('quality_id', 0, 'intval');
                $not_timely_report_switch = $this->request->param('not_timely_report_switch', 0, 'intval');
                $params = [
                    'village_id' => $this->adminUser['village_id'],
                    'property_id' => $this->adminUser['property_id'],
                    'single_id' => $single_id,
                    'quality_id' => $quality_id,
                    'repair_cate_id' => $id,
                    'repair_cate_fid' => $data['parent_id'],
                    'not_timely_report_switch' => $not_timely_report_switch
                ];
                $params['from_user'] = $from_user;
                (new HouseCommunityQualityService())->repairCateBindSingleQuality($params);
            }
            return api_output(0,['res' => $id],'添加成功');
        }else{
            return api_output(1003,[],'添加失败！');
        }
    }

    /**
     * 修改分类
     * @author:zhubaodi
     * @date_time: 2021/8/16 10:48
     */
    public function editCate(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['property_id']=$this->adminUser['property_id'];
        $data['cate_name'] = $this->request->param('name', 0, 'trim');
        $data['id'] = $this->request->param('id', 0, 'intval');
        $data['parent_id'] = $this->request->param('parent_id', 0, 'intval');
        $data['sort'] = $this->request->param('sort', 0, 'intval');
        $data['status'] = $this->request->param('status', 0, 'intval');
        $data['type'] = $this->request->param('type', 0, 'intval');
        $data['timely_time'] = $this->request->param('timely_time', '');
        if (empty($data['cate_name'])) {
            return api_output_error(1001, '分类名称不能为空');
        }
        if (empty($data['status'])) {
            return api_output_error(1001, '状态不能为空');
        }
        if (empty($data['cate_name'])||empty($data['status'])||empty($data['type'])||empty($data['id'])) {
            return api_output_error(1001, '必填参数缺失');
        }
        if ($data['sort']<0){
            return api_output_error(1001, '排序值不能小于0');
        }
        if ($data['type']==1){
            $data['uid']=$this->request->param('uid', 0, 'intval');
        }
        if ($data['type']==2){
            $data['director_id']=$this->request->param('director_id');
            $data['scheduling']=$this->request->param('scheduling');
        }

        $configCustomizationService = new ConfigCustomizationService();
        $jz_open = $configCustomizationService->getWSXCustomizedOpen();
        // 是否转给供暖站 0-否 1-是
        $to_supply_station = $this->request->param('to_supply_station', 0, 'intval');
        $other_village_id = $this->request->param('other_village_id', 0, 'intval');
        $other_village_cate_id = $this->request->param('other_village_cate_id', 0, 'intval');
        $other_village_cate_fid = $this->request->param('other_village_cate_fid', 0, 'intval');
        $from_user = ['type' => 'village_admin', 'admin_id' => $this->adminUser['village_id'], 'account' => $this->adminUser['village_name']];
        if (isset($this->adminUser['user_name']) && $this->adminUser['user_name']) {
            $from_user['name'] = $this->adminUser['user_name'];
        } elseif (isset($this->adminUser['adminName']) && $this->adminUser['adminName']) {
            $from_user['name'] = $this->adminUser['adminName'];
        }
        if (isset($this->adminUser['phone']) && $this->adminUser['phone']) {
            $from_user['phone'] = $this->adminUser['phone'];
        }
        if ($this->_uid) {
            $from_user['_userId'] = $this->_uid;
        }
        if ($this->login_userId) {
            $from_user['login_userId'] = $this->login_userId;
        }
        if ($this->login_role) {
            $from_user['login_role'] = $this->login_role;
        }
        $from_user = json_encode($from_user, JSON_UNESCAPED_UNICODE);
        $nowTime = time();
        
        if ($jz_open) {
            if ($to_supply_station == 1) {
                $data['to_supply_station'] = $to_supply_station;
                $data['other_village_id'] = $other_village_id;
                $data['other_village_cate_id'] = $other_village_cate_id;
                $data['other_village_cate_fid'] = $other_village_cate_fid;
                $data['other_village_text'] = $from_user;
                $data['other_village_time'] = $nowTime;
                if (!$other_village_cate_id || !$other_village_cate_fid){
                    return api_output_error(1001, '请选择绑定的对应完整【供暖站分类】！');
                }
            } else {
                $data['to_supply_station'] = $to_supply_station;
                $data['other_village_id'] = 0;
                $data['other_village_cate_id'] = 0;
                $data['other_village_cate_fid'] = 0;
                $data['other_village_text'] = $from_user;
                $data['other_village_time'] = 0;
            }
        }
        try{
            $serviceRepairCate = new RepairCateService();
            $id = $serviceRepairCate->editCate($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if ($id){
            if ($jz_open) {
                // 判断是否有绑定质保 走质保
                $single_id = $this->request->param('single_id', 0, 'intval');
                $data['single_id']=$single_id;
                $quality_id = $this->request->param('quality_id', 0, 'intval');
                $data['quality_id']=$quality_id;
                $not_timely_report_switch = $this->request->param('not_timely_report_switch', 0, 'intval');
                $data['not_timely_report_switch']=$not_timely_report_switch;
                $params = [
                    'village_id' => $this->adminUser['village_id'],
                    'property_id' => $this->adminUser['property_id'],
                    'single_id' => $single_id,
                    'quality_id' => $quality_id,
                    'repair_cate_id' => $data['id'],
                    'repair_cate_fid' => $data['parent_id'],
                    'not_timely_report_switch' => $not_timely_report_switch
                ];
                $params['from_user'] = $from_user;
                (new HouseCommunityQualityService())->repairCateBindSingleQuality($params);
            }
            $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
            $queuData = [
                'logData' => [
                    'tbname' => '工单类目-管理-编辑',
                    'table'  => 'house_new_repair_cate',
                    'client' => '小区后台',
                    'trigger_path' => '工单设置->工单类目',
                    'trigger_type' => $this->getUpdateNmae(),
                    'addtime'      => time(),
                    'village_id'   => $this->adminUser['village_id'],
                    'property_id' =>0,
                    'op_id'   => $parseInfo['op_id'],
                    'op_type'   => $parseInfo['op_type'],
                    'op_name'   => $parseInfo['op_name'],
                ],
                'newData' => $data,
                'oldData' =>''
            ];
            $this->laterLogInQueue($queuData);
            return api_output(0,['res' => $id],'编辑成功');
        }else{
            return api_output(1003,[],'编辑 失败！');
        }
    }


    /**
     * 删除分类
     * @author:zhubaodi
     * @date_time: 2021/8/12 18:40
     */
    public function delCate(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['property_id']=$this->adminUser['property_id'];
        $data['id'] = $this->request->param('id', 0, 'intval');
        if (empty($data['id'])) {
            return api_output_error(1001, '必填参数缺失');
        }
        $serviceRepairCate = new RepairCateService();
        try{
            $res = $serviceRepairCate->delCate($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if ($res){
            return api_output(0,['res' => $res],'删除成功');
        }else{
            return api_output(1003,[],'删除失败！');
        }

    }


    /**
     * 查询负责人列表
     * @author:zhubaodi
     * @date_time: 2021/8/12 18:40
     */
    public function getDirectorList(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['property_id']=$this->adminUser['property_id'];
        $data['cate_id'] = $this->request->param('cate_id', 0, 'intval');
        if (empty($data['cate_id']) ) {
            return api_output_error(1001, '必填参数缺失');
        }
        $serviceRepairCate = new RepairCateService();
        try{
            $res = $serviceRepairCate->getDirectorList($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);

    }

    /**
     * 根据id查询负责人列表
     * @author:zhubaodi
     * @date_time: 2021/8/12 18:40
     */
    public function getDirectorLists(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['property_id']=$this->adminUser['property_id'];
        $data['id'] = $this->request->param('id');
        if (empty($data['id'])) {
            return api_output_error(1001, '必填参数缺失');
        }
        $serviceRepairCate = new RepairCateService();
        try{
            $res = $serviceRepairCate->getDirectorLists($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);

    }

    /**
     * 查询指定日期的负责人列表
     * @author:zhubaodi
     * @date_time: 2021/9/25 13:47
     */
    public function getScheduling(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['property_id']=$this->adminUser['property_id'];
        $data['cate_id'] = $this->request->param('cate_id', '', 'intval');
        $data['date_type'] = $this->request->param('key', '', 'intval');
        $data['id'] = $this->request->param('director_id');
       /* if (empty($data['cate_id'])) {
            return api_output_error(1001, '分类id不能为空');
        }*/
        if (empty($data['date_type'])) {
            return api_output_error(1001, '日期类型不能为空');
        }
        $serviceRepairCate = new RepairCateService();
        try{
            $res = $serviceRepairCate->getScheduling($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);

    }

    /**
     * 查询负责人详情
     * @author:zhubaodi
     * @date_time: 2021/8/12 18:40
     */
    public function getDirectorInfo(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['property_id']=$this->adminUser['property_id'];
        $data['cate_id'] = $this->request->param('cate_id', 0, 'intval');
        $data['date_type'] = $this->request->param('date_type', 0, 'intval');
        if (empty($data['cate_id'])||empty( $data['date_type']) ) {
            return api_output_error(1001, '必填参数缺失');
        }
        $serviceRepairCate = new RepairCateService();
        try{
            $res = $serviceRepairCate->getDirectorInfo($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);

    }


    /**
     * 添加负责人纪录
     * @author:zhubaodi
     * @date_time: 2021/8/16 10:48
     */
    public function addDirector(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['property_id']=$this->adminUser['property_id'];
        $data['date_type'] = $this->request->param('date_type', '', 'trim');
        $data['item'] = $this->request->param('item', '', 'trim');
        $data['defult'] = $this->request->param('defult', '', 'trim');
        $allitem=array();
        if ((!isset($data['defult']['id']) || ($data['defult']['id']<1)) && empty($data['defult']['uid'])){
           // return api_output_error(1001, '24小时负责人不能为空');
        }else{
            $allitem[]=$data['defult'];
            //$data['item'][]= $data['defult'];
        }
        if (empty($data['item'])||empty($data['date_type'])) {
            //return api_output_error(1001, '必填参数缺失');
        }
        if(!empty($data['item']) && is_array($data['item'])){
            foreach ($data['item'] as $item){
                $allitem[]=$item;
            }
        }
        $data['item']=$allitem;
        // print_r($data);exit;
        $serviceRepairCate = new RepairCateService();
        try{
            $id = $serviceRepairCate->addDirector($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,['res' => $id],'操作成功');
        /*
        if ($id){
            return api_output(0,['res' => $id],'添加成功');
        }else{
            return api_output(1003,[],'添加失败！');
        }*/
    }

    /**
     * 修改负责人纪录
     * @author:zhubaodi
     * @date_time: 2021/8/16 10:48
     */
    public function editDirector(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['property_id']=$this->adminUser['property_id'];
        $data['cate_name'] = $this->request->param('name', 0, 'trim');
        $data['id'] = $this->request->param('id', 0, 'intval');
        $data['parent_id'] = $this->request->param('parent_id', 0, 'intval');
        $data['subject_id'] = $this->request->param('subject_id', 0, 'intval');
        $data['sort'] = $this->request->param('sort', 0, 'intval');
        $data['status'] = $this->request->param('status', 0, 'intval');
        $data['type'] = $this->request->param('type', 0, 'intval');
        if (empty($data['cate_name'])||empty($data['subject_id'])||empty($data['sort'])||empty($data['status'])||empty($data['type'])||empty($data['id'])) {
            return api_output_error(1001, '必填参数缺失');
        }
        if ($data['type']==1){
            $data['uid']=$this->request->param('uid', 0, 'intval');
        }
        if ($data['type']==2){
            $data['director_id']=$this->request->param('director_id', 0, 'trim');
        }
        $serviceRepairCate = new RepairCateService();
        try{
            $id = $serviceRepairCate->editCate($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if ($id){
            return api_output(0,['res' => $id],'编辑成功');
        }else{
            return api_output(1003,[],'编辑 失败！');
        }
    }



    /**
     * 查询自定义字段列表
     * @author:zhubaodi
     * @date_time: 2021/8/12 18:40
     */
    public function getCateCustomList(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['property_id']=$this->adminUser['property_id'];
        $data['cate_id'] = $this->request->param('cate_id', 0, 'intval');
        $data['page'] = $this->request->param('page', 0, 'intval');
        $data['limit'] = 20;
        if (empty($data['cate_id'])) {
            return api_output_error(1001, '必填参数缺失');
        }
        $serviceRepairCate = new RepairCateService();
        try{
            $res = $serviceRepairCate->getCateCustomList($data);
            $res['total_limit'] = $data['limit'];
            $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
            $addLabel2 = 1;
            $editLabel2 = 1;
            $deleteLabel2 = 1;
            if ($jz_open) {
                $authMenus = isset($this->adminUser['menus']) ? $this->adminUser['menus'] : [];
                if ($authMenus && !is_array($authMenus)) {
                    $authMenus = explode(',', $authMenus);
                }
                if (!empty($authMenus) && !in_array(113007, $authMenus)) {
                    $addLabel2 = 0;
                }
                if (!empty($authMenus) && !in_array(113008, $authMenus)) {
                    $editLabel2 = 0;
                }
                if (!empty($authMenus) && !in_array(113009, $authMenus)) {
                    $deleteLabel2 = 0;
                }
            }
            $auth = [];
            $auth['addLabel2'] = $addLabel2;
            $auth['editLabel2'] = $editLabel2;
            $auth['deleteLabel2'] = $deleteLabel2;
            $res['auth'] = $auth;
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);

    }



    /**
     * 查询自定义字段详情
     * @author:zhubaodi
     * @date_time: 2021/8/12 18:40
     */
    public function getCateCustomInfo(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['property_id']=$this->adminUser['property_id'];
        $data['id'] = $this->request->param('id', 0, 'intval');
        $data['cate_id'] = $this->request->param('cate_id', 0, 'intval');
        if (empty($data['id']) ) {
            return api_output_error(1001, '必填参数缺失');
        }
        $serviceRepairCate = new RepairCateService();
        try{
            $res = $serviceRepairCate->getCateCustomInfo($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);

    }


    /**
     * 添加自定义字段
     * @author:zhubaodi
     * @date_time: 2021/8/16 10:48
     */
    public function addCateCustom(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['property_id']=$this->adminUser['property_id'];
        $data['name'] = $this->request->param('name', 0, 'trim');
        $data['cate_id'] = $this->request->param('cate_id', 0, 'intval');
        $data['sort'] = $this->request->param('sort', 0, 'intval');
        $data['status'] = $this->request->param('status', 0, 'intval');
        $data['id'] = $this->request->param('id', 0, 'intval');
        if (empty($data['name'])||empty($data['cate_id'])||empty($data['status'])) {
            return api_output_error(1001, '必填参数缺失');
        }
        if ($data['sort']<0){
            return api_output_error(1001, '排序值不能小于0');
        }
        $serviceRepairCate = new RepairCateService();
        try{
            $id = $serviceRepairCate->addCateCustom($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if ($id){
            return api_output(0,['res' => $id],'添加成功');
        }else{
            return api_output(1003,[],'添加失败！');
        }
    }



    /**
     * 删除自定义字段
     * @author:zhubaodi
     * @date_time: 2021/8/12 18:40
     */
    public function delCateCustom(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['property_id']=$this->adminUser['property_id'];
        $data['id'] = $this->request->param('id', 0, 'intval');
        $data['cate_id'] = $this->request->param('cate_id', 0, 'intval');
        if (empty($data['id'])) {
            return api_output_error(1001, '必填参数缺失');
        }
        $serviceRepairCate = new RepairCateService();
        try{
            $res = $serviceRepairCate->delCateCustom($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if ($res){
            return api_output(0,['res' => $res],'删除成功');
        }else{
            return api_output(1003,[],'删除失败！');
        }

    }

    public function getDirectortree(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['property_name'] = $this->adminUser['property_name'];
        $data['property_id']=$this->adminUser['property_id'];
        $serviceRepairCate = new RepairCateService();
        try{
            $res = $serviceRepairCate->getDirectortree($this->adminUser);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if ($res){
            return api_output(0,['res' => $res]);
        }else{
            return api_output(1003,[],'暂无数据！');
        }
    }

    /**
     * 配置类别的收费设置
     * @author:zhubaodi
     * @date_time: 2022/3/28 11:14
     */
    public function setRepairCharge(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['property_id']=$this->adminUser['property_id'];
        $data['charge_type'] = $this->request->param('charge_type', 0, 'intval');
        $data['subject_id'] = $this->request->param('subject_id', '', 'trim');
        /*if (empty($data['subject_id'])) {
            return api_output_error(1001, '类别名称不能为空');
        }*/
        $serviceRepairCate = new RepairCateService();
        try{
            $res = $serviceRepairCate->setRepairCharge($data);
            $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
            $queuData = [
                'logData' => [
                    'tbname' => '工单管理-工单设置',
                    'table'  => 'house_new_repair_cate',
                    'client' => '小区后台',
                    'trigger_path' => '工单设置->收费设置',
                    'trigger_type' => $this->getUpdateNmae(),
                    'addtime'      => time(),
                    'village_id'   => $this->adminUser['village_id'],
                    'property_id' =>0,
                    'op_id'   => $parseInfo['op_id'],
                    'op_type'   => $parseInfo['op_type'],
                    'op_name'   => $parseInfo['op_name'],
                ],
                'newData' => $data,
                'oldData' =>''
            ];
            $this->laterLogInQueue($queuData);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if ($res['code']){
            return api_output(0,['res' => $res],'设置成功');
        }else{
            return api_output(1003,[],$res['msg']);
        }

    }

    /**
     * 查询类别的收费设置
     * @author:zhubaodi
     * @date_time: 2022/3/28 11:14
     */
    public function getRepairCharge(){
        $data['village_id'] = $this->adminUser['village_id'];
        $data['property_id']=$this->adminUser['property_id'];
      //  $data['village_id']=50;
        $serviceRepairCate = new RepairCateService();
        try{
            $res = $serviceRepairCate->getRepairCharge($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if ($res){
            return api_output(0, $res,'设置成功');
        }else{
            return api_output(1003,[],'设置失败！');
        }
    }


    /**
     * Notes: 提交工单
     * @author: wanzy
     * @date_time: 2021/8/17 10:27
     */
    public function repairOrderAdd() {
        $village_id = $this->adminUser['village_id'];
        if(!$village_id) {
            return api_output_error(1001,'必传参数缺失');
        }
        $cat_fid = $this->request->post('cat_fid',0,'intval');
        $cat_id = $this->request->post('cat_id',0,'intval');
        if(!$cat_fid) {
            return api_output_error(1001,'请选择工单类目');
        }
        if(!$cat_id) {
            return api_output_error(1001,'请选择工单分类');
        }
        $address_type = $this->request->post('address_type','');

        $address_id = $this->request->post('address_id',0);
        if($address_type){
            if(!in_array($address_type,['public', 'room'])) {
                return api_output_error(1001,'请选择对应位置');
            }
            if(!$address_id) {
                return api_output_error(1001,'请选择对应位置');
            }
        }
        $label_txt = $this->request->post('label_txt');
        $order_imgs = $this->request->post('order_imgs');
        $videos = $this->request->post('videos');
        $order_content = $this->request->post('order_content');
        $go_time = $this->request->post('go_time','','trim');
        $order_content = htmlspecialchars(trim($order_content));
        if (empty($order_imgs) && !$order_content && empty($label_txt)) {
            return api_output_error(1001,'缺少必传参数');
        }
        $complete_time = $this->request->post('complete_time','','trim');
        $is_complete = $this->request->post('is_complete',0,'intval');
        $u_idd = $this->request->post('u_idd','','trim');  //上报人 b_ 是住户人员 w_是工作人员
        $assign_wid = $this->request->post('wid',0,'intval'); // 指派的工作人员id
        $report_pigcms_id=0;
        $report_wid=0;
        if($u_idd && strpos($u_idd,'_')) {
            $u_idds=explode('_',$u_idd);
            if($u_idds['0']=='b'){
                $report_pigcms_id=$u_idds['1'];
            }else if($u_idds['0']=='w'){
                $report_wid=$u_idds['1'];
            }
        }
        $repair_cate_service = new RepairCateService();
        $worker_id = $this->_uid;
        $login_role = $this->login_role;
        $data = [
            'village_id' => $village_id,
            'category_id' => 0,
            'type_id' => 0,
            'cat_fid' => $cat_fid?$cat_fid:0,
            'cat_id' => $cat_id,
            'address_type' => $address_type,
            'address_id' => $address_id,
            'label_txt' => $label_txt?$label_txt:'',
            'order_imgs' => $order_imgs?$order_imgs:'',
            'videos' => $videos?$videos:'',
            'order_content' => $order_content?$order_content:'',
            'worker_id' => $worker_id,
            'login_role' => $login_role,
            'event_status' => 10,
            'go_time' => $go_time,
            'assign_wid'=>$assign_wid,
            'report_pigcms_id'=>$report_pigcms_id,
            'report_wid'=>$report_wid,
        ];
        if($is_complete==1){
            $data['is_complete']=1;
            $complete_time=$complete_time ? $complete_time:date('Y-m-d H:i');
            $data['event_status']=60;
            $complete_time=strtotime($complete_time);
            $data['complete_time']=$complete_time;
        }
        try{
            $info = $repair_cate_service->addWorksOrder($data);
            $arr = [];
            $arr['info'] = $info;
            return api_output(0, $arr);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }

    //todo 校验新版工单及时率
    public function checkIsTimely()
    {
        $village_id = $this->adminUser['village_id'];
        try {
            $rr = (new HouseVillageService())->checkVillageField($village_id, 'is_timely');
            $result = ['is_timely' => $rr];
            $configCustomizationService = new ConfigCustomizationService();
            $jz_wsxdsj_customized_open = $configCustomizationService->getWSXCustomizedOpen();
            $result['cate_bind_quality'] = $jz_wsxdsj_customized_open ? 1 : 0;
            $sHouseCommunityAboutService = new HouseCommunityAboutService();
            $aboutConfig = $sHouseCommunityAboutService->getVillageFacilitiesInfo($village_id);
            if ($aboutConfig && !is_array($aboutConfig)) {
                $aboutConfig = $aboutConfig->toArray();
            }
            $is_supply_station = empty($aboutConfig) ? 0 : 1;
            if ($is_supply_station == 0) {
                $developerConfig = $sHouseCommunityAboutService->getVillageFacilitiesInfo($village_id, 'developer');
                if ($developerConfig && !is_array($developerConfig)) {
                    $developerConfig = $developerConfig->toArray();
                }
                $is_supply_station = empty($developerConfig) ? 0 : 1;
            }
            $result['is_supply_station'] = $is_supply_station;
            $where = ['village_id' => $village_id, 'bind_facilities_type' => 'supply_station'];
            $field = 'id, property_id, village_id, bind_facilities_type, bind_village_id, bind_time';
            $params = ['getVillageName' => 1];
            $communityInfo = $sHouseCommunityAboutService->getHouseCommunityAboutBind($where, $field, $params);
            if (!empty($communityInfo)) {
                $communityInfo = reset($communityInfo);
            }
            $result['communityInfo'] = $communityInfo;
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }
    
    public function getBindVillageRepairCate()
    {
        $village_id = $this->adminUser['village_id'];
        $bind_village_id = $this->request->post('bind_village_id', 0, 'intval');
        $parent_id = $this->request->param('parent_id', 0, 'intval');
        $result = [];
        try {
            $sHouseCommunityAboutService = new HouseCommunityAboutService();
            $aboutConfig = $sHouseCommunityAboutService->getVillageFacilitiesInfo($village_id);
            if ($aboutConfig && !is_array($aboutConfig)) {
                $aboutConfig = $aboutConfig->toArray();
            }
            $is_supply_station = empty($aboutConfig) ? 0 : 1;
            $developerConfig = $sHouseCommunityAboutService->getVillageFacilitiesInfo($village_id, 'developer');
            if ($developerConfig && !is_array($developerConfig)) {
                $developerConfig = $developerConfig->toArray();
            }
            $is_developer = empty($developerConfig) ? 0 : 1;
            if (empty($bind_village_id)) {
                $where = ['village_id' => $village_id, 'bind_facilities_type' => 'supply_station'];
                $field = 'id, property_id, village_id, bind_facilities_type, bind_village_id, bind_time';
                $params = ['getVillageName' => 1];
                $communityInfo = $sHouseCommunityAboutService->getHouseCommunityAboutBind($where, $field, $params);
                if (!empty($communityInfo)) {
                    $communityInfo = reset($communityInfo);
                }
                $bind_village_id = isset($communityInfo['bind_village_id']) ? intval($communityInfo['bind_village_id']) : 0;
            }
            if ($is_supply_station == 1) {
                return api_output_error(-1, '供暖站不支持绑定其他小区分类');
            }
            if ($is_developer == 1) {
                return api_output_error(-1, '开发商不支持绑定其他小区分类');
            }

            $where = [];
            $where[] = ['village_id', '=', $bind_village_id];
            $where[] = ['parent_id', '=', $parent_id];
            $where[] = ['status', 'in', [1, 2]];
            $serviceRepairCate = new RepairCateService();

            $data = [];
            $data['page'] = 0;
            $data['limit'] = 0;
            fdump_api(['where' => $where, 'data' => $data], '$data');
            $result = $serviceRepairCate->getCateList($where, $data);
            $selectList = [];
            foreach ($result['list'] as $item) {
                $cate_name = isset($item['cate_name']) ? trim($item['cate_name']) : '';
                $id = isset($item['id']) ? intval($item['id']) : 0;
                $village_id = isset($item['village_id']) ? intval($item['village_id']) : 0;
                $selectList[] = [
                    'label' => $cate_name, 'value' => $id, 'village_id' => $village_id, 'cate_name' => $cate_name, 'id' => $id,
                ];
            }
            $result['list'] = $selectList;
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }

    /**
     * 查询公共区域和楼栋列表
     * @author:zhubaodi
     * @date_time: 2021/4/25 13:27
     */
    public function getHousePosition()
    {
        $village_id = $this->adminUser['village_id'];
        if(!$village_id) {
            return api_output_error(1001,'必传参数缺失');
        }
        // 查询楼栋
        $village_service = new HouseVillageService();
        try {
            $list = $village_service->getHousePosition($village_id);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0,$list);
    }
    /**
     * Notes: 获取对应子集信息
     * @return array|\json
     * @author: wanzy
     * @date_time: 2021/8/13 17:01
     */
    public function getHousePositionChidren() {
        $village_id = $this->adminUser['village_id'];
        if(!$village_id) {
            return api_output_error(1001,'必传参数缺失');
        }
        $id = $this->request->post('id',0);
        if(!$id) {
            return api_output_error(1001,'必传参数缺失');
        }
        $type = $this->request->post('type',0);
        if(!$type) {
            return api_output_error(1001,'必传参数缺失');
        }
        // 查询楼栋
        $village_service = new HouseVillageService();
        try {
            $list = $village_service->getHousePositionChildren($village_id,$id,$type);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        $arr = [];
        $arr['list'] = $list;
        return api_output(0,$list);
    }

    /**
     * 工单类目-类别列表
     * @author wanzy
     * @date_time 2021/08/13 17:07
     * @return \json
     */
    public function getSubject()
    {
        $village_id = $this->adminUser['village_id'];
        if(!$village_id) {
            return api_output_error(1001,'必传参数缺失');
        }
        $parent_id = $this->request->post('subject_id',0);
        if (!$parent_id) {
            $parent_id = 0;
        }
        $where[] = ['village_id','=',$village_id];
        $where[] = ['status','=',1];
        $where[] = ['parent_id','=',$parent_id];
        $field = 'subject_name,id as category_id,color';
        $field = 'cate_name as subject_name,id as category_id,color';
        try{

            $data = (new RepairCateService())->getNewRepairCate($where,$field,0,0,'id DESC');
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * Notes:分类 一级分类-二级分类
     * @return \json
     * @author: wanzy
     * @date_time: 2021/8/13 17:15
     */
    public function getRepairCate()
    {
        $cat_fid = $this->request->post('cat_fid',0);
        $village_id = $this->adminUser['village_id'];
        if(!$village_id) {
            return api_output_error(1001,'必传参数缺失');
        }
        $subject_id = $this->request->post('subject_id',0);
        if(!$subject_id) {
            return api_output_error(1001,'必传参数缺失');
        }
        $appType = $this->request->post('app_type','');
        if (!$cat_fid) {
            $cat_fid = 0;
        }
        $where = [];
        // H5 一次性将一级二级分类拼好返给前端
        if($appType != 'packapp'){
//            $where[] = ['parent_id','=',$cat_fid];
        }
        $where[] = ['parent_id','=',$subject_id];
        $where[] = ['village_id','=',$village_id];
//      $where[] = ['subject_id','=',$subject_id];

        $where[] = ['status','=',1];
        $field='id as cat_id,cate_name';
        $service_house_new_repair = new HouseNewRepairService();
        try{
            $data = $service_house_new_repair->getFidCateList($where,$field,'sort DESC,id desc',$appType);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 获取部门数据
     * @author: liukezhu
     * @date : 2022/4/6
     * @return \json
     */
    public function getTissueNav(){
        $village_id = $this->adminUser['village_id'];
        try{
            $status=(new HouseVillageService())->checkVillageField($village_id,'is_grab_order');
            if($status){
                $data = (new HouseProgrammeService())->businessNav($village_id,0);
            }else{
                $data=[];
            }
            $list=['status'=>$status,'list'=>$data];
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    public function getVillageRepairConfig()
    {
        $village_id = $this->adminUser['village_id'];
        try {
            $houseVillageService = new HouseVillageService();
            $whereArr = array();
            $whereArr[] = array('village_id', '=', $village_id);
            $fieldStr = 'village_id,property_id,is_timely,works_order_switch,grab_order_time,is_grab_order,work_order_extra';
            $villageInfoExtend = $houseVillageService->getHouseVillageInfoExtend($whereArr, $fieldStr);
            $bindWorkerUrl = cfg('site_url') . '/shequ.php?g=House&c=Index&a=new_repair_bind_worker&cfromfun=v20';
            $bindWorkerUrl = str_replace('//shequ.php', '/shequ.php', $bindWorkerUrl);
            if (empty($villageInfoExtend)) {
                $villageInfoExtend = [];
            } else {
                $villageInfoExtend['day_works_order_integral'] = 0;
                $villageInfoExtend['per_works_order_integral'] = 0;
                $villageInfoExtend['auto_evaluate'] = array("is_open" => false, "stime" => 0, "stime_type" => "hour", "star" => "5");
                $work_order_extra = !empty($villageInfoExtend['work_order_extra']) ? json_decode($villageInfoExtend['work_order_extra'], 1) : array();
                if ($work_order_extra) {
                    $villageInfoExtend = array_merge($villageInfoExtend, $work_order_extra);
                }
            }
            $is_comment_point = cfg('works_order_comment_get_point');
            $is_comment_point = $is_comment_point > 0 ? 1 : 0;
            $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
            $is_jz_customized_open = $jz_open ? 1 : 0;
            $data = ['bindWorkerUrl' => $bindWorkerUrl, 'is_comment_point' => $is_comment_point];
            $is_supply_station = 0;
            $is_developer = 0;
            $selects = [];
            $selects[] = [
                'value' => 0,
                'label' => '未选择',
                'type_name' => '',
            ];
            if ($is_jz_customized_open) {
                $configInfo = (new HouseCommunityAboutService())->getHouseCommunityConfig($village_id);
                if (!empty($configInfo)) {
                    $villageInfoExtend = array_merge($villageInfoExtend, $configInfo);
                }
                // 查询下是否归属供暖站
                $supplyInfo = (new HouseCommunityAboutService())->getVillageFacilitiesInfo($village_id);
                if (isset($supplyInfo['is_virtual']) && intval($supplyInfo['is_virtual']) == 1) {
                    $is_supply_station = 1;
                    $villageInfoExtend['is_contact_available'] = isset($supplyInfo['is_contact_available']) ? intval($supplyInfo['is_contact_available']) : 0;
                }
                $developer = (new HouseCommunityAboutService())->getVillageFacilitiesInfo($village_id, 'developer');
                if (isset($developer['is_virtual']) && intval($developer['is_virtual']) == 1) {
                    $is_developer = 1;
                    $villageInfoExtend['is_contact_available'] = isset($developer['is_contact_available']) ? intval($developer['is_contact_available']) : 0;
                }
                $whereWork = array(['village_id', '=', $village_id], ['is_del', '=', 0]);
                $whereWork[] = ['status', '=', 1];
                $whereWork[] = ['department_id', '>', 0];
                $fieldStr = 'wid,type,phone,name,openid,nickname';
                $workList = (new HouseWorkerService())->get_all_worker_list($whereWork, $fieldStr, 0);
                if (isset($workList['list']) && $workList['list'] && !is_array($workList['list'])) {
                    $workList = $workList['list']->toArray();
                } else {
                    $workList = isset($workList['list']) ? $workList['list'] : [];
                }
                foreach ($workList as $work) {
                    $selects[] = [
                        'value' => $work['wid'],
                        'label' => $work['name'],
                        'type_name' => isset($work['type_name']) ? trim($work['type_name']) : '',
                    ];
                }
                $selectOperates = [
                    ['value' => '0', 'label' => '关闭'], ['value' => '1', 'label' => '同意'],
                ];
                $data['selectOperates'] = $selectOperates;
            }
            $data['selects'] = $selects;
            $data['selectWorks'] = $selects;
            $data['is_supply_station'] = $is_supply_station;
            $data['is_developer'] = $is_developer;
            $data['is_jz_customized_open'] = $is_jz_customized_open;
            if (isset($villageInfoExtend['not_receive_work_id']) && !$villageInfoExtend['not_receive_work_id']) {
                $villageInfoExtend['not_receive_work_id'] = 0;
            }
            if (isset($villageInfoExtend['order_recipient_work_id']) && !$villageInfoExtend['order_recipient_work_id']) {
                $villageInfoExtend['order_recipient_work_id'] = null;
            }
            $data['repairConfig'] = $villageInfoExtend;
            $configTableList = [];

            $baseSetAuth = 1;
            $chargeSetAuth = 1;
            $commentPointAuth = 1;
            $remarkSetAuth = 1;
            $orderReviewSetAuth = 1;
            $supervisionAuth = 1;
            if ($jz_open) {
                $authMenus = isset($this->adminUser['menus']) ? $this->adminUser['menus'] : [];
                if ($authMenus && !is_array($authMenus)) {
                    $authMenus = explode(',', $authMenus);
                }
                if (!empty($authMenus) && !in_array(113010, $authMenus)) {
                    $baseSetAuth = 0;
                }
                if (!empty($authMenus) && !in_array(113011, $authMenus)) {
                    $chargeSetAuth = 0;
                }
                if (!empty($authMenus) && !in_array(113012, $authMenus)) {
                    $commentPointAuth = 0;
                }
                if (!empty($authMenus) && !in_array(113013, $authMenus)) {
                    $remarkSetAuth = 0;
                }
                if (!empty($authMenus) && !in_array(113014, $authMenus)) {
                    $orderReviewSetAuth = 0;
                }
                if (!empty($authMenus) && !in_array(113015, $authMenus)) {
                    $supervisionAuth = 0;
                }
            }
            $auth = [];
            $auth['baseSetAuth'] = $baseSetAuth;
            $auth['chargeSetAuth'] = $chargeSetAuth;
            $auth['commentPointAuth'] = $commentPointAuth;
            $auth['remarkSettAuth'] = $remarkSetAuth;
            $auth['orderReviewSetAuth'] = $orderReviewSetAuth;
            $auth['supervisionAuth'] = $supervisionAuth;
            $data['auth'] = $auth;
            $configTableList = [];
            if ($baseSetAuth == 1) {
                $configTableList[] = ['name' => '基本设置', 'key' => 'baseSet'];
            }
            if ($chargeSetAuth == 1) {
                $configTableList[] = ['name' => '收费设置', 'key' => 'chargeSet'];
            }
            if ($remarkSetAuth == 1) {
                $configTableList[] = ['name' => '评价设置', 'key' => 'remarkSet'];
            }
            if ($is_jz_customized_open == 1 && $is_supply_station != 1 && $is_developer != 1) {
                if ($is_comment_point == 1) {
                    $configTableList = [];
                    if ($baseSetAuth == 1) {
                        $configTableList[] = ['name' => '基本设置', 'key' => 'baseSet'];
                    }
                    if ($chargeSetAuth == 1) {
                        $configTableList[] = ['name' => '收费设置', 'key' => 'chargeSet'];
                    }
                    if ($commentPointAuth == 1) {
                        $configTableList[] = ['name' => '评价积分设置', 'key' => 'commentPoint'];
                    }
                    if ($remarkSetAuth == 1) {
                        $configTableList[] = ['name' => '评价设置', 'key' => 'remarkSet'];
                    }
                    if ($orderReviewSetAuth == 1) {
                        $configTableList[] = ['name' => '转单审批设置', 'key' => 'orderReviewSet'];
                    }
                    if ($supervisionAuth == 1) {
                        $configTableList[] = ['name' => '推送督查室设置', 'key' => 'supervision'];
                    }
                } else {
                    $configTableList = [];
                    if ($baseSetAuth == 1) {
                        $configTableList[] = ['name' => '基本设置', 'key' => 'baseSet'];
                    }
                    if ($chargeSetAuth == 1) {
                        $configTableList[] = ['name' => '收费设置', 'key' => 'chargeSet'];
                    }
                    if ($remarkSetAuth == 1) {
                        $configTableList[] = ['name' => '评价设置', 'key' => 'remarkSet'];
                    }
                    if ($orderReviewSetAuth == 1) {
                        $configTableList[] = ['name' => '转单审批设置', 'key' => 'orderReviewSet'];
                    }
                    if ($supervisionAuth == 1) {
                        $configTableList[] = ['name' => '推送督查室设置', 'key' => 'supervision'];
                    }
                }
            } elseif ($is_jz_customized_open == 1) {
                if ($is_comment_point == 1) {
                    $configTableList = [];
                    if ($baseSetAuth == 1) {
                        $configTableList[] = ['name' => '基本设置', 'key' => 'baseSet'];
                    }
                    if ($chargeSetAuth == 1) {
                        $configTableList[] = ['name' => '收费设置', 'key' => 'chargeSet'];
                    }
                    if ($commentPointAuth == 1) {
                        $configTableList[] = ['name' => '评价积分设置', 'key' => 'commentPoint'];
                    }
                    if ($remarkSetAuth == 1) {
                        $configTableList[] = ['name' => '评价设置', 'key' => 'remarkSet'];
                    }
                    if ($orderReviewSetAuth == 1) {
                        $configTableList[] = ['name' => '转单审批设置', 'key' => 'orderReviewSet'];
                    }
                    if ($supervisionAuth == 1) {
                        $configTableList[] = ['name' => '推送督查室设置', 'key' => 'supervision'];
                    }
                } else {
                    $configTableList = [];
                    if ($baseSetAuth == 1) {
                        $configTableList[] = ['name' => '基本设置', 'key' => 'baseSet'];
                    }
                    if ($chargeSetAuth == 1) {
                        $configTableList[] = ['name' => '收费设置', 'key' => 'chargeSet'];
                    }
                    if ($remarkSetAuth == 1) {
                        $configTableList[] = ['name' => '评价设置', 'key' => 'remarkSet'];
                    }
                    if ($orderReviewSetAuth == 1) {
                        $configTableList[] = ['name' => '转单审批设置', 'key' => 'orderReviewSet'];
                    }
                    if ($supervisionAuth == 1) {
                        $configTableList[] = ['name' => '推送督查室设置', 'key' => 'supervision'];
                    }
                }
            } elseif ($is_comment_point == 1) {
                $configTableList = [];
                if ($baseSetAuth == 1) {
                    $configTableList[] = ['name' => '基本设置', 'key' => 'baseSet'];
                }
                if ($chargeSetAuth == 1) {
                    $configTableList[] = ['name' => '收费设置', 'key' => 'chargeSet'];
                }
                if ($commentPointAuth == 1) {
                    $configTableList[] = ['name' => '评价积分设置', 'key' => 'commentPoint'];
                }
                if ($remarkSetAuth == 1) {
                    $configTableList[] = ['name' => '评价设置', 'key' => 'remarkSet'];
                }
            }
            $data['configTableList'] = $configTableList;
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function saveVillageRepairConfig(){
        $village_id = $this->adminUser['village_id'];
        $property_id=$this->adminUser['property_id'];
        $xtype=$this->request->post('xtype','','trim');
        $is_timely=$this->request->post('is_timely',0,'intval');
        $grab_order_time=$this->request->post('grab_order_time',0,'intval');
        $is_grab_order=$this->request->post('is_grab_order',0,'intval');
        $day_works_order_integral=$this->request->post('day_works_order_integral',0,'intval');
        $per_works_order_integral=$this->request->post('per_works_order_integral',0,'intval');
        $works_order_switch=$this->request->post('works_order_switch',0,'intval');
        $saveArr=array('is_timely'=>$is_timely,'grab_order_time'=>$grab_order_time);
        $saveArr['is_grab_order']=$is_grab_order;
        $saveArr['works_order_switch']=$works_order_switch;
        $auto_evaluate=$this->request->post('auto_evaluate');
        $work_order_extra=array();
        if($xtype=='other_set'){
            $work_order_extra['day_works_order_integral']=$day_works_order_integral;
            $work_order_extra['per_works_order_integral']=$per_works_order_integral;
        }else if($xtype=='auto_evaluate_set'){
            $work_order_extra['auto_evaluate']=$auto_evaluate;
        }
        $newData=array_merge($saveArr,$work_order_extra);
        try{
            $houseVillageService=new HouseVillageService();
            $whereArr=array('village_id'=>$village_id,'property_id'=>$property_id);
            if($xtype=='other_set' || $xtype=='auto_evaluate_set'){
                $tmpSaveArr=array();
                if($xtype=='auto_evaluate_set'){
                    $tmpSaveArr['is_auto_work_order_evaluate']=!empty($auto_evaluate['is_open']) ? 1:0;
                    $tmpSaveArr['work_order_auto_evaluate_time']=!empty($auto_evaluate['stime']) ? intval($auto_evaluate['stime']):0;
                }
                $newData=array_merge($newData,$tmpSaveArr);
                $houseVillageService->saveHouseVillageInfoExtendWorkOrderExtra($whereArr,$work_order_extra,$tmpSaveArr);
            }else{
                $houseVillageService->saveHouseVillageInfoExtend($whereArr,$saveArr);
            }
            $jz_wsxdsj_net_customized_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
            if ($jz_wsxdsj_net_customized_open) {
                $params = $this->request->param();
                $from_user = ['type' => 'village_admin', 'admin_id' => $this->adminUser['village_id'], 'account' => $this->adminUser['village_name']];
                if (isset($this->adminUser['user_name']) && $this->adminUser['user_name']) {
                    $from_user['name'] = $this->adminUser['user_name'];
                } elseif (isset($this->adminUser['adminName']) && $this->adminUser['adminName']) {
                    $from_user['name'] = $this->adminUser['adminName'];
                }
                if (isset($this->adminUser['phone']) && $this->adminUser['phone']) {
                    $from_user['phone'] = $this->adminUser['phone'];
                }
                if ($this->_uid) {
                    $from_user['_userId'] = $this->_uid;
                }
                if ($this->login_userId) {
                    $from_user['login_userId'] = $this->login_userId;
                }
                if ($this->login_role) {
                    $from_user['login_role'] = $this->login_role;
                }
                $from_user = json_encode($from_user,JSON_UNESCAPED_UNICODE);
                $params['from'] = 'village_admin';
                $params['from_user'] = $from_user;
                $params['village_id'] = $village_id;
                $params['property_id'] = $property_id;
                (new HouseCommunityAboutService())->addHouseCommunityConfig($params);
            }

            $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
            $queuData = [
                'logData' => [
                    'tbname' => '工单管理-工单设置',
                    'table'  => 'house_village_info',
                    'client' => '小区后台',
                    'trigger_path' => '工单设置->基本设置',
                    'trigger_type' => $this->getUpdateNmae(),
                    'addtime'      => time(),
                    'village_id'   => $village_id,
                    'property_id' =>0,
                    'op_id'   => $parseInfo['op_id'],
                    'op_type'   => $parseInfo['op_type'],
                    'op_name'   => $parseInfo['op_name'],
                ],
                'newData' => $newData,
                'oldData' =>'' 
            ];
            $this->laterLogInQueue($queuData);
            return api_output(0,'操作成功！');
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }
}