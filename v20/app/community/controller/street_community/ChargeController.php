<?php
/**
 * @author : liukezhu
 * @date : 2021/6/10
 */
namespace app\community\controller\village_api;

use app\community\controller\CommunityBaseController;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\service\HouseNewChargePrepaidService;
use app\community\model\service\HouseNewChargeProjectService;
use app\community\model\service\HouseNewChargeRuleService;
use app\community\model\service\HouseNewParkingService;
use app\community\model\service\HouseNewPorpertyService;
use app\community\model\service\HouseVillageParkingService;
use app\community\model\service\HouseVillageSingleService;
use app\community\model\service\HouseVillageUserVacancyService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HousePropertyDigitService;
use app\job\CommonLogSysJob;
use think\facade\Queue;
use app\common\model\service\config\ConfigCustomizationService;
use app\community\model\service\HouseNewChargePrepaidDiscountService;
use app\traits\MassChargeStandardBindTraits;
class ChargeController extends CommunityBaseController{

    use MassChargeStandardBindTraits;
    /**
     *收费项目列表
     * @author: liukezhu
     * @date : 2021/6/10
     */
    public function ChargeProjectList(){
        $page = $this->request->param('page',0,'int');
        $limit = $this->request->param('limit',0,'int');
        $keyword= $this->request->param('keyword');
        $village_id = $this->adminUser['village_id'];
        $property_id =  $this->adminUser['property_id'];
        $type= $this->request->param('type','','trim');
        $charge_type= $this->request->param('charge_type','','trim');
        $project_type= $this->request->param('project_type','','trim'); // cycle周期性的
        $login_role = $this->login_role;
        if (in_array($login_role,$this->propertyRole)) {
            $from_type = 2;//物业
            $from_id = $property_id;
        }
        else {
            $from_type = 1;//小区
            $from_id = $village_id;
        }
        if(!$from_id)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        $HouseNewChargeProjectService = new HouseNewChargeProjectService();
        $param=[
            'from_type'=>$from_type,
            'from_id'=>$from_id,
            'page'=>$page,
            'limit'=>$limit,
            'keyword'=>$keyword,
            'property_id'=>$property_id,
            'type'=>$type,
            'charge_type'=>$charge_type,
            'project_type'=>$project_type,
        ];
        try{
            $list = $HouseNewChargeProjectService->getList($param);
            $houseVillageService=new HouseVillageService();
            $list['role_additem']=$houseVillageService->checkPermissionMenu(112089,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $list['role_delitem']=$houseVillageService->checkPermissionMenu(112092,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $list['role_edititem']=$houseVillageService->checkPermissionMenu(112091,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $list['role_manageitem']=$houseVillageService->checkPermissionMenu(112090,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $list['func_kaidalaicn_special']=0;
            $configCustomizationService= new ConfigCustomizationService();
            $isfuncKaidalaicnSpecial=$configCustomizationService->getfuncKaidalaicnSpecial();
            if($isfuncKaidalaicnSpecial){
                $list['func_kaidalaicn_special']=1;
            }
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 收费项目详情
     * @author lijie
     * @date_time 2022/02/15
     * @return \json
     */
    public function getProjectInfo()
    {
        $id = $this->request->post('id',0);
        if(!$id){
            return api_output_error(1001,'缺少必传参数');
        }
        $HouseNewChargeProjectService = new HouseNewChargeProjectService();
        $where['id'] = $id;
        try{
            $data = $HouseNewChargeProjectService->getProjectInfo($where);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 收费项目科目
     * @author: liukezhu
     * @date : 2021/6/11
     */
    public function getChargeSubject(){
        $HouseNewChargeProjectService = new HouseNewChargeProjectService();
        try{
            $list = $HouseNewChargeProjectService->getSubject($this->adminUser['property_id']);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 添加收费项目
     * @author: liukezhu
     * @date : 2021/6/11
     */
    public function ChargeProjectAdd(){
        $subject_id = $this->request->param('subject_id');
        $name = $this->request->param('name');
        $img = $this->request->param('img');
        $type = $this->request->param('type');
        $refund_period = $this->request->param('refund_period',0);
        $status = $this->request->param('status');
        if (empty($subject_id)){
            return api_output(1001,[],'请选择收费科目！');
        }
        if (empty($name)){
            return api_output(1001,[],'请输入收费项目名称！');
        }
        if (empty($img)){
            return api_output(1001,[],'请上传收费项目图标！');
        }
        $HouseNewChargeProjectService = new HouseNewChargeProjectService();
        $param=array(
            'village_id' => $this->adminUser['village_id'],
            'name'=>$name,
            'subject_id'=>$subject_id,
            'img'=>$img,
            'refund_period'=>$refund_period,
            'status'=>$status
        );
        if(intval($type)){
            $param['type']=$type;
        }
        try{
            $id=$HouseNewChargeProjectService->add($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($id<1){
            return api_output_error(1001,'添加失败');
        }else{
            return api_output(0,$id);
        }
    }

    public function ChargeProjectIcon(){
        $type = $this->request->param('type');
        if (empty($type)){
            return api_output(1001,[],'请选择收费科目！');
        }
        $HouseNewChargeProjectService = new HouseNewChargeProjectService();
        try{
            $list = $HouseNewChargeProjectService->getIcon($type);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 收费项目编辑回显
     * @author: liukezhu
     * @date : 2021/6/11
     */
    public function ChargeProjectEdit(){
        $id = $this->request->param('id','','intval');
        if (empty($id)){
            return api_output(1001,[],'id不能为空！');
        }
        $HouseNewChargeProjectService = new HouseNewChargeProjectService();
        try{
            $param=array(
                'village_id' => $this->adminUser['village_id'],
                'id'=>$id
            );
            $id=$HouseNewChargeProjectService->edit($param);
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
     * 删除收费项目
     * @author: zhubaodi
     * @date : 2021/11/22
     */
    public function delChargeProject(){
        $id = $this->request->param('id','','intval');
        if (empty($id)){
            return api_output(1001,[],'id不能为空！');
        }
        $HouseNewChargeProjectService = new HouseNewChargeProjectService();
        try{
            $param=array(
                'village_id' => $this->adminUser['village_id'],
                'id'=>$id
            );
            $id=$HouseNewChargeProjectService->del($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if(!$id){
            return api_output_error(1001,'删除失败');
        }else{
            return api_output(0,['res'=>''],'删除成功');
        }
    }
    /**
     *收费项目编辑提交
     * @author: liukezhu
     * @date : 2021/6/11
     */
    public function ChargeProjectSub(){
        $id = $this->request->param('id');
        $name = $this->request->param('name');
        $img = $this->request->param('img');
        $status = $this->request->param('status');
        $refund_period = $this->request->param('refund_period',0);
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数！');
        }
        if (empty($name)){
            return api_output(1001,[],'请输入收费项目名称！');
        }
        if (empty($img)){
            return api_output(1001,[],'请上传收费项目图标！');
        }
        if (empty($status)){
            return api_output(1001,[],'请选择状态！');
        }
        $HouseNewChargeProjectService = new HouseNewChargeProjectService();
        $param=array(
            'id'=>$id,
            'village_id' => $this->adminUser['village_id'],
            'name'=>$name,
            'img'=>$img,
            'refund_period'=>$refund_period,
            'status'=>$status
        );
        try{
            $ids=$HouseNewChargeProjectService->edit($param,$id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($ids<1){
            return api_output_error(1001,'添加失败');
        }else{
            return api_output(0,$ids);
        }
    }


    /**
     * 收费规则列表
     * @author: liukezhu
     * @date : 2021/6/11
     */
    public function ChargeRuleList(){
        $charge_project_id = $this->request->param('charge_project_id',0);
        $subjectId = $this->request->param('subjectId',0);
        $page = $this->request->param('page','1','int');
        $limit = $this->request->param('limit','10','int');
        $keyword= $this->request->param('keyword');
        $type = $this->request->param('type','');
        $village_id = $this->adminUser['village_id'];
        $property_id =  $this->adminUser['property_id'];
        $login_role = $this->login_role;
        if (in_array($login_role,$this->propertyRole)) {
            $from_type = 2;//物业
            $from_id = $property_id;
        }
        else {
            $from_type = 1;//小区
            $from_id = $village_id;
        }
        if(!$from_id)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        $HouseNewChargeRuleService = new HouseNewChargeRuleService();
        $param=[
            'from_type'=>$from_type,
            'from_id'=>$from_id,
            'page'=>$page,
            'limit'=>$limit,
            'keyword'=>$keyword,
            'charge_project_id'=>$charge_project_id,
            'subjectId'=>$subjectId,
            'type' => $type
        ];
        try{
            $list = $HouseNewChargeRuleService->getList($param);
            $houseVillageService=new HouseVillageService();
            $list['role_addrule']=$houseVillageService->checkPermissionMenu(112093,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $list['role_delrule']=$houseVillageService->checkPermissionMenu(112095,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $list['role_editrule']=$houseVillageService->checkPermissionMenu(112094,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $list['role_bindrule']=$houseVillageService->checkPermissionMenu(112096,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage().$e->getLine());
        }
        return api_output(0,$list);
    }

    /**
     * 校验收费标准返格式
     * @author: liukezhu
     * @date : 2021/6/12
     */
    public function checkChargeRule(){
        $charge_project_id= $this->request->param('charge_project_id','','intval');
        if (empty($charge_project_id)){
            return api_output(1001,[],'缺少必要参数！');
        }
        $HouseNewChargeRuleService = new HouseNewChargeRuleService();
        try{
            $param=array(
                'village_id' => $this->adminUser['village_id'],
                'charge_project_id'=>$charge_project_id
            );
            $list = $HouseNewChargeRuleService->ruleParam($param);
            $configCustomizationService=new ConfigCustomizationService();
            $is_grapefruit_prepaid=$configCustomizationService->getHzhouGrapefruitOrderJudge();
            $isWsxCustomizedOpen=$configCustomizationService->getWSXCustomizedOpen();
            $list['is_grapefruit_prepaid']=$is_grapefruit_prepaid;
            $list['have_order_discount']=$isWsxCustomizedOpen ? 1:0;
            $func_kaidalaicn_special=$configCustomizationService->getfuncKaidalaicnSpecial();
            $list['func_kaidalaicn_special']=0;
            if($func_kaidalaicn_special){
                $list['have_order_discount']=1;
                $list['func_kaidalaicn_special']=1;
            }
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 收费规则账单类型数据
     * @author: liukezhu
     * @date : 2021/6/15
     */
    public function ChargeRuleBillParam(){
        $HouseNewChargeRuleService = new HouseNewChargeRuleService();
        try{
            $village_id = $this->adminUser['village_id'];
            $list = $HouseNewChargeRuleService->ruleBillParam($village_id);
            $service_house_village = new HouseVillageService();
            $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$village_id],'property_id,property_name,village_name');
            $digit_type=1;  //1四舍五入 2全舍
            if($village_info && !$village_info->isEmpty()){
                $village_info = $village_info->toArray();
                $db_house_property_digit_service = new HousePropertyDigitService();
                $digit_info =$db_house_property_digit_service->get_one_digit(['property_id'=>$village_info['property_id']]);
                if(!empty($digit_info) && $digit_info['type']>0){
                     $digit_type=$digit_info['type'];
                }
            }
            $list['digit_type_txt']='四舍五入';
            $list['digit_type']=$digit_type;
            if($digit_type==2){
                $list['digit_type_txt']='全舍';
            }
            $configCustomizationService=new ConfigCustomizationService();
            $is_grapefruit_prepaid=$configCustomizationService->getHzhouGrapefruitOrderJudge();
            $isWsxCustomizedOpen=$configCustomizationService->getWSXCustomizedOpen();
            $list['is_grapefruit_prepaid']=$is_grapefruit_prepaid;
            $list['have_order_discount']=$isWsxCustomizedOpen ? 1:0;
            $func_kaidalaicn_special=$configCustomizationService->getfuncKaidalaicnSpecial();
            $list['func_kaidalaicn_special']=0;
            if($func_kaidalaicn_special){
                $list['have_order_discount']=1;
                $list['func_kaidalaicn_special']=1;
            }
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     *添加收费标准     todo 收费标准 添加和 编辑  未生效前 添加和编辑 根据 时间类型 年月日  去重 可随意添加 未生效时间段
                         注意当前 2021年6月10  号 按月只能添加 7月  按日只能添加11日   按年 只能加 2022年
     * @author: liukezhu
     * @date : 2021/6/15
     */
    public function ChargeRuleAdd(){
        set_time_limit(0);
        $post = $this->request->param('post');
        if(empty($post)) return api_output(1001,[],'缺少必要参数！');
        $post['village_id']=$this->adminUser['village_id'];
        $HouseNewChargeRuleService = new HouseNewChargeRuleService();
        try{
            $id=$HouseNewChargeRuleService->add($post);
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
     *回显收费标准
     * @author: liukezhu
     * @date : 2021/6/15
     */
    public function ChargeRuleEdit(){
        $id = $this->request->param('id','','intval');
        $type = $this->request->param('type','');
        if (empty($id)){
            return api_output(1001,[],'id不能为空！');
        }
        $HouseNewChargeRuleService = new HouseNewChargeRuleService();
        try{
            $param=array(
                'village_id' => $this->adminUser['village_id'],
                'id'=>$id,
                'type'=>$type
            );
            $id=$HouseNewChargeRuleService->edit($param);
            $configCustomizationService=new ConfigCustomizationService();
            $is_grapefruit_prepaid=$configCustomizationService->getHzhouGrapefruitOrderJudge();
            $isWsxCustomizedOpen=$configCustomizationService->getWSXCustomizedOpen();
            if(is_array($id) && isset($id['edit_data'])){
                $id['is_grapefruit_prepaid']=$is_grapefruit_prepaid;
                $id['have_order_discount']=$isWsxCustomizedOpen ? 1:0;
                $func_kaidalaicn_special=$configCustomizationService->getfuncKaidalaicnSpecial();
                $list['func_kaidalaicn_special']=0;
                if($func_kaidalaicn_special){
                    $list['have_order_discount']=1;
                    $list['func_kaidalaicn_special']=1;
                }
            }
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
     * 提交收费标准
     * @author: liukezhu
     * @date : 2021/6/15
     * @return \json
     */
    public function ChargeRuleSub(){
        $post = $this->request->param('post');
        if(empty($post)) return api_output(1001,[],'缺少必要参数！');
        $post['village_id']=$this->adminUser['village_id'];
        $HouseNewChargeRuleService = new HouseNewChargeRuleService();
        try{
            $id=$HouseNewChargeRuleService->edit($post,true);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($id<1){
            return api_output_error(1001,'提交失败');
        }else{
            return api_output(0,$id);
        }
    }


    /**
     * 删除收费规则
     * @author: liukezhu
     * @date : 2021/6/15
     * @return \json
     */
    public function ChargeRuleDel(){
        $id = $this->request->param('id','','intval');
        if (empty($id)){
            return api_output(1001,[],'id不能为空！');
        }
        $HouseNewChargeRuleService = new HouseNewChargeRuleService();
        try{
            $param=array(
                'village_id' => $this->adminUser['village_id'],
                'id'=>$id
            );
            $id=$HouseNewChargeRuleService->del($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if(!$id){
            return api_output_error(1001,'删除失败');
        }else{
            return api_output(0,$id);
        }
    }

    /**
     *预缴周期列表
     * @author: liukezhu
     * @date : 2021/6/11
     */
    public function ChargePrepaidList(){
        $charge_rule_id = $this->request->param('charge_rule_id');
        $page = $this->request->param('page','1','int');
        $limit = $this->request->param('limit','10','int');
        $type = $this->request->param('type','');
        $village_id = $this->adminUser['village_id'];
        $property_id =  $this->adminUser['property_id'];
        $login_role = $this->login_role;
        if (in_array($login_role,$this->propertyRole)) {
            $from_type = 2;//物业
            $from_id = $property_id;
        }
        else {
            $from_type = 1;//小区
            $from_id = $village_id;
        }
        if(!$from_id)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        $HouseNewChargePrepaidService = new HouseNewChargePrepaidService();
        $param=[
            'from_type'=>$from_type,
            'from_id'=>$from_id,
            'page'=>$page,
            'limit'=>$limit,
            'type'=>$type,
            'charge_rule_id'=>intval($charge_rule_id)
        ];
        try{
            $list = $HouseNewChargePrepaidService->getList($param);
            $configCustomizationService=new ConfigCustomizationService();
            $is_grapefruit_prepaid=$configCustomizationService->getHzhouGrapefruitOrderJudge();
            $list['is_grapefruit_prepaid']=$is_grapefruit_prepaid;
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     *获取预缴周期参数
     * @author: liukezhu
     * @date : 2021/6/11
     */
    public function getPrepaidCycle(){
        $charge_rule_id = $this->request->param('charge_rule_id','0', 'intval');  //收费标准id
        $HouseNewChargePrepaidService = new HouseNewChargePrepaidService();
        try{
            $list=$HouseNewChargePrepaidService->getPrepaidCycle($charge_rule_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     *添加预缴周期
     * @author: liukezhu
     * @date : 2021/6/11
     */
    public function ChargePrepaidAdd(){
        $cycle = $this->request->param('cycle');                                    //预缴周期
        $cycle_param = $this->request->param('cycle_param', '0', 'intval');          //自定义预缴周期值
        $type = $this->request->param('type');                                        //优惠模式
        $rate = $this->request->param('rate');                                        //折扣率
        $give_cycle_param = $this->request->param('give_cycle_param');                //赠送周期参数
        $give_cycle_txt = $this->request->param('give_cycle_txt', '0', 'intval');    //自定义赠送周期值
        $custom_txt = $this->request->param('custom_txt');                            //自定义文本
        $charge_rule_id = $this->request->param('charge_rule_id');                    //收费标准id
        $status = $this->request->param('status');
        if (empty($cycle)){
            return api_output(1001,[],'请选择预缴周期！');
        }
        if (empty($type)){
            return api_output(1001,[],'请选择优惠模式！');
        }
        $HouseNewChargePrepaidService = new HouseNewChargePrepaidService();
        $param=array(
            'charge_rule_id'=>intval($charge_rule_id),
            'village_id' => $this->adminUser['village_id'],
            'cycle'=>intval($cycle),
            'type'=>intval($type),
            'custom_txt'=>'',
            'rate'=>0,
            'give_cycle_param'=>0,
            'give_cycle_txt'=>$give_cycle_txt,
            'cycle_param'=>$cycle_param,
            'status'=>$status
        );
        if($type == 1){
            //折扣
            if(empty($rate)){
                return api_output(1001,[],'请选择折扣率');
            }
            $param['rate']=intval($rate);
        }elseif ($type == 2){
            //赠送周期
            if(empty($give_cycle_param)){
                return api_output(1001,[],'请选择赠送周期');
            }
            $param['give_cycle_param']=$give_cycle_param;
        }elseif ($type == 3){
            //自定义文本
            if(empty($custom_txt)){
                return api_output(1001,[],'请输入自定义文本');
            }
            $param['custom_txt']=$custom_txt;
        }
        try{
            $id=$HouseNewChargePrepaidService->add($param);
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
     * 编辑预缴周期
     * @author: liukezhu
     * @date : 2021/6/11
     */
    public function ChargePrepaidEdit(){
        $id = $this->request->param('id','','intval');
        if (empty($id)){
            return api_output(1001,[],'id不能为空！');
        }
        $HouseNewChargePrepaidService = new HouseNewChargePrepaidService();
        try{
            $param=array(
                'village_id' => $this->adminUser['village_id'],
                'id'=>$id
            );
            $id=$HouseNewChargePrepaidService->edit($param);
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
     * 提交预缴周期
     * @author: liukezhu
     * @date : 2021/6/11
     */
    public function ChargePrepaidSub(){
        $id = $this->request->param('id');
        $cycle = $this->request->param('cycle');                                    //预缴周期
        $cycle_param = $this->request->param('cycle_param', '0', 'intval');          //自定义预缴周期值
        $type = $this->request->param('type');                                        //优惠模式
        $rate = $this->request->param('rate');                                        //折扣率
        $give_cycle_param = $this->request->param('give_cycle_param');                //赠送周期参数
        $give_cycle_txt = $this->request->param('give_cycle_txt', '0', 'intval');    //自定义赠送周期值
        $custom_txt = $this->request->param('custom_txt');                            //自定义文本
        $status = $this->request->param('status');
        if (empty($type)){
            return api_output(1001,[],'请选择优惠模式！');
        }
        if (empty($status)){
            return api_output(1001,[],'请选择状态！');
        }
        $HouseNewChargePrepaidService = new HouseNewChargePrepaidService();
        $param=array(
            'id'=>$id,
            'village_id' => $this->adminUser['village_id'],
            'cycle'=>intval($cycle),
            'type'=>intval($type),
            'rate'=>0,
            'give_cycle_param'=>0,
            'cycle_param'=>$cycle_param,
            'give_cycle_txt'=>$give_cycle_txt,
            'custom_txt'=>'',
            'status'=>$status
        );
        if($type == 1){
            //折扣
            if(empty($rate)){
                return api_output(1001,[],'请选择折扣率');
            }
            $param['rate']=intval($rate);
        }elseif ($type == 2){
            //赠送周期
            if(empty($give_cycle_param)){
                return api_output(1001,[],'请选择赠送周期');
            }
            $param['give_cycle_param']=$give_cycle_param;
        }elseif ($type == 3){
            //自定义文本
            if(empty($custom_txt)){
                return api_output(1001,[],'请输入自定义文本');
            }
            $param['custom_txt']=$custom_txt;
        }
        try{
            $id=$HouseNewChargePrepaidService->edit($param,$id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($id<1){
            return api_output_error(1001,'提交失败');
        }else{
            return api_output(0,$id);
        }
    }

    /**
     * 删除预缴周期
     * @author: liukezhu
     * @date : 2021/6/11
     * @return \json
     */
    public function ChargePrepaidDel(){
        $id = $this->request->param('id','','intval');
        if (empty($id)){
            return api_output(1001,[],'id不能为空！');
        }
        $HouseNewChargePrepaidService = new HouseNewChargePrepaidService();
        try{
            $param=array(
                'village_id' => $this->adminUser['village_id'],
                'id'=>$id
            );
            $id=$HouseNewChargePrepaidService->del($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if(!$id){
            return api_output_error(1001,'删除失败');
        }else{
            return api_output(0,$id);
        }
    }

    /**
     * 编辑收费设置
     * @author:zhubaodi
     * @date_time: 2021/6/16 14:27
     */
    public function editChargeSet()
    {
        // 获取登录信息
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $data['refund_term'] = $this->request->param('refund_term', 0, 'intval');
        $data['call_date'] = $this->request->param('call_date', 0, 'intval');
        $data['call_type'] = $this->request->param('call_type', 0, 'intval');
        $data['is_combine'] = $this->request->param('is_combine', 0, 'intval');
        $data['can_modify_zero'] = $this->request->param('can_modify_zero', 0, 'intval');
        $data['order_type_cutday'] = $this->request->param('order_type_cutday', 0, 'intval');
        $selectedWorker= $this->request->param('wids','');
        $wids='';
        if(!empty($selectedWorker) && is_array($selectedWorker)){
            $wids=json_encode($selectedWorker,JSON_UNESCAPED_UNICODE);
        }
        $data['wids']=$wids;
        $serviceChargeProject = new HouseNewChargeProjectService();
        try {
            $set_id = $serviceChargeProject->editChargeSet($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if ($set_id) {
            return api_output(0, ['set_id' => $set_id], '编辑成功');
        } else {
            return api_output(1003, [], '编辑失败！');
        }
    }

    /**
     * 查询新版收费设置
     * @author:zhubaodi
     * @date_time: 2021/6/16 14:27
     */
    public function chargeSetInfo()
    {
        // 获取登录信息
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $serviceChargeProject = new HouseNewChargeProjectService();

        try {
            $chargeSetInfo = $serviceChargeProject->getChargeSetInfo($village_id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $chargeSetInfo);
    }


    /**
     * 查询收费标准绑定列表
     * @author:zhubaodi
     * @date_time: 2021/6/18 10:27
     */
    public function standardBindList()
    {
        // 获取登录信息
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output(1002, [], '请先登录到小区后台！');
        }

        $data['rule_id'] = $this->request->param('rule_id', 0, 'intval');
        if (empty($data['rule_id'] )){
            return api_output(1001, [], '收费标准不能为空！');
        }
        $data['bind_type'] = $this->request->param('bind_type', 0, 'intval');
        if (empty($data['bind_type'] )){
            return api_output(1001, [], '收费标准绑定类型不能为空！');
        }
        if ($data['bind_type']==1){
            $data['vecancy_id'] = $this->request->param('vacancy', '', 'trim');
        }else{
            $data['garage_id'] = $this->request->param('garage_id', 0, 'intval');
            $data['position_num'] = $this->request->param('position_num', '', 'trim');
        }

        $data['page'] = $this->request->param('page', 0, 'intval');
        $data['limit']=$this->request->param('limit', 10, 'intval');
        $serviceChargeProject = new HouseNewChargeRuleService();

       // print_r($data);exit;
        try {
            $chargeSetInfo = $serviceChargeProject->getStandardBindList($data);
            $configCustomizationService=new ConfigCustomizationService();
            $isWSXCustomizedOpen=$configCustomizationService->getWSXCustomizedOpen();
            $is_aihorse_customized=$configCustomizationService->getAihorseCustomizedJudge();
            $is_standard_bind_import_btn=0;
            if($isWSXCustomizedOpen || $is_aihorse_customized){
                $is_standard_bind_import_btn=1;
            }
            $chargeSetInfo['standard_bind_import_btn']=0;
            if($is_standard_bind_import_btn && isset($chargeSetInfo['rule_info']) && in_array($chargeSetInfo['rule_info']['fees_type'],array(1,2))){
                $chargeSetInfo['standard_bind_import_btn']=1;
            }
            
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $chargeSetInfo);
    }


    /**
     * 添加收费标准绑定
     * @author:zhubaodi
     * @date_time: 2021/6/18 10:27
     */
    public function addStandardBind()
    {
        set_time_limit(0);
        // 获取登录信息
        $data=array();
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $data['bind_type'] = $this->request->param('bind_type', 0, 'intval');
        if (empty($data['bind_type'])){
            return api_output(1000, ['status'=>1000,'msg'=>'绑定类型不能为空'], '绑定类型不能为空');
        }
        $data['rule_id'] = $this->request->param('rule_id', 0, 'intval');
        if (empty($data['rule_id'])){
            return api_output(1000, ['status'=>1000,'msg'=>'收费标准id不能为空'], '收费标准id不能为空');
        }
        /**** 
         *
        $configCustomizationService=new ConfigCustomizationService();
        $is_grapefruit_prepaid=$configCustomizationService->getHzhouGrapefruitOrderJudge();
        if($is_grapefruit_prepaid==1){
            $data['per_one_order'] = $this->request->param('per_one_order', 0, 'intval'); //1时 将生成多笔按一个月或一日计费的账单
        }
        */
        $data['per_one_order'] = $this->request->param('per_one_order', 0, 'intval'); //1时 将生成多笔按一个月或一日计费的账单
        $serviceChargeProject = new HouseNewChargeRuleService();
        $data['custom_value'] = $this->request->param('custom_value', '', 'trim');
        if (!empty($data['custom_value'])){
            if(!is_numeric($data['custom_value'])||$data['custom_value']<0){
                return api_output(1000, ['status'=>1000,'msg'=>'请正确输入自定义字段值'], '请正确输入自定义字段值');
            }
        }
        $data['order_add_time'] = $this->request->param('order_add_time', '', 'trim');
        $data['cycle'] = $this->request->param('cycle', 0, 'int');
        if (!empty($data['cycle'])){
            if(floor($data['cycle'])!= $data['cycle']||$data['cycle']<1){
                return api_output(1001, [], '请正确输入收费周期');
            }
        }
        if ($data['bind_type']==1){
            $data['pigcms_id'] = $this->request->param('pigcms_id', '', 'trim');
            $data['position_id'] = $this->request->param('position_id', '', 'trim');
            if(empty($data['pigcms_id'])&&empty($data['position_id'])){
                return api_output(1000, ['status'=>1000,'msg'=>'请选择需要绑定的房间或车位'], '请选择需要绑定的房间或车位');
            }
        }else{
            $data['pigcms_arr'] = $this->request->param('pigcms_arr', '');
            if(empty($data['pigcms_arr'])){
                return api_output(1000, ['status'=>1000,'msg'=>'请选择需要绑定的房间'], '请选择需要绑定的房间!');
            }
            $is_break=false;
            $mc=0;
            foreach ($data['pigcms_arr'] as $kk=>$sv) {
                if (!isset($sv['single_id']) || ($sv['single_id']<=0)) {
                    $mc=$kk+1;
                    $is_break = true;
                    break;
                }
            }
            if ($is_break) {
                return api_output(1000, ['status' => 1000, 'msg' => '第' . $mc . '项未选择楼栋信息！'], '第' . $mc . '项未选择楼栋信息！');
            }
            try {
                $toJobret = $this->houseMassChargeStandardBindToJob($data);
                fdump_api(['toJobFail','toJobret'=>$toJobret],'00houseMassChargeStandardBind',1);
                if ($toJobret) {
                    $ret = array('status' => 0, 'msg' => '操作成功，正在处理批量绑定数据，请稍等！');
                    return api_output(0, $ret);
                }
            } catch (\Exception $e) {
                fdump_api(['toJobException','msg'=>$e->getMessage()],'00houseMassChargeStandardBind',1);
            }
            $data['pigcms_id'] = $serviceChargeProject->getVacancyList($data['pigcms_arr'], $this->adminUser['village_id']);
            if (isset($data['pigcms_id']['code']) && $data['pigcms_id']['code'] == 1003) {
                return api_output(1000, ['status' => 1000, 'msg' => $data['pigcms_id']['msg']], $data['pigcms_id']['msg']);
            }
        }
        try {
            $chargeSetInfo = $serviceChargeProject->addAllStandardBind($data);
        } catch (\Exception $e) {
            return api_output(1000, ['status'=>1000,'msg'=>$e->getMessage()], $e->getMessage());
        }
        return api_output(0, $chargeSetInfo);
    }
    //手动批量给标准绑定的房间生成账单
    public function standardCreateManyOrderByRuleId(){
        set_time_limit(0);
        //$single_data = $this->request->param('single_data', '');
        $rule_id = $this->request->param('rule_id', 0, 'intval');
        $charge_project_id = $this->request->param('charge_project_id', 0, 'intval');
        $service_start_time = $this->request->param('service_start_time', '', 'trim');
        $service_end_time = $this->request->param('service_end_time', '', 'trim');
        $xtype = $this->request->param('xtype', 0, 'intval');
        if($rule_id<1){
            return api_output(1000, ['status'=>1000,'msg'=>'收费标准id不能为空'], '收费标准id不能为空');
        }
        /*
        if(empty($single_data) || !is_array($single_data)){
            return api_output(1000, ['status'=>1000,'msg'=>'请选择需要操作的楼栋数据'], '请选择需要操作的楼栋数据!');
        }
        */
        $village_id = $this->adminUser['village_id'];
        try {
            $datas=array('village_id'=>$village_id,'single_data'=>array(),'rule_id'=>$rule_id,'login_role'=>$this->login_role,'role_id'=>0);
            if($xtype==1){
                $service_start_time_tmp=0;
                if($service_start_time){
                    $service_start_time_tmp=strtotime($service_start_time);
                }
                $service_start_time_tmp=$service_start_time_tmp && $service_start_time_tmp>100 ? $service_start_time_tmp:0;

                $service_end_time_tmp=0;
                if($service_end_time){
                    $service_end_time_tmp=strtotime($service_end_time);
                }
                $service_end_time_tmp=$service_end_time_tmp && $service_end_time_tmp>100 ? $service_end_time_tmp:0;
                if($service_end_time_tmp<$service_start_time_tmp){
                    $service_end_time_tmp=$service_start_time_tmp;
                }
            }
            $datas['service_start_time']=$service_start_time_tmp;
            $datas['service_end_time']=$service_end_time_tmp;
            $datas['xtype']=$xtype;
            $datas['charge_project_id']=$charge_project_id;
            if(in_array($this->login_role,$this->villageOrderCheckRole)){
                $datas['role_id']=$this->_uid;
            }
            $serviceChargeProject = new HouseNewChargeRuleService();
            $chargeSetInfo = $serviceChargeProject->createManyOrderByRuleId($datas);
        } catch (\Exception $e) {
            return api_output(1000, ['status'=>1000,'msg'=>$e->getMessage()], $e->getMessage());
        }
        return api_output(0, $chargeSetInfo);
    }
    /**
     * 批量绑定车场
     * @author:zhubaodi
     * @date_time: 2022/3/7 18:07
     */
    public function addBindAllPosition(){
        // 获取登录信息
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $data['rule_id'] = $this->request->param('rule_id', 0, 'intval');
        if (empty($data['rule_id'])){
            return api_output(1001, [], '收费标准id不能为空');
        }
        $serviceChargeProject = new HouseNewChargeRuleService();
        $data['garage_id'] = $this->request->param('garage_id', '', 'trim');
        if(empty($data['garage_id'])){
            return api_output(1001, [], '请选择需要绑定的车场');
        }
        try {
            $chargeSetInfo = $serviceChargeProject->addBindAllPosition($data);
        } catch (\Exception $e) {

            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $chargeSetInfo);
    }

    /**
     * 查询收费标准详情
     * @author:zhubaodi
     * @date_time: 2021/6/23 19:27
     */
    public function getRuleInfo()
    {
        // 获取登录信息
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $data['rule_id'] = $this->request->param('rule_id', 0, 'intval');
        if (empty($data['rule_id'])){
            return api_output(1001, [], '收费标准id不能为空');
        }

        try {
            $serviceChargeRule = new HouseNewChargeRuleService();
            $ruleInfo= $serviceChargeRule->getRuleInfo($data['rule_id']);
            if ($ruleInfo['charge_type']==2){
                $ruleInfo['is_show'] = 1;
            }else {
                if (empty($ruleInfo['fees_type']) || ($ruleInfo['bill_type'] == 1 && empty($ruleInfo['unit_gage'])) || (!in_array($ruleInfo['charge_type'], [2,5]) && empty($ruleInfo['unit_gage']))) {
                    $ruleInfo['is_show'] = 2;
                } else {
                    $ruleInfo['is_show'] = 1;
                }
            }
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $ruleInfo);
    }


    /**
     * 解除收费标准绑定
     * @author:zhubaodi
     * @date_time: 2021/6/18 10:27
     */
    public function delStandardBind()
    {
        // 获取登录信息
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $data['bind_id'] = $this->request->param('bind_id', 0, 'intval');
        if (empty($data['bind_id'])){
            return api_output(1001, [], '绑定id不能为空！');
        }
        $serviceChargeProject = new HouseNewChargeRuleService();

        try {
            $chargeSetInfo = $serviceChargeProject->delStandardBind($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $chargeSetInfo);
    }


    /**
     * 查询添加绑定列表
     * @author:zhubaodi
     * @date_time: 2021/6/18 10:27
     */
    public function abbBindList()
    {
         // 获取登录信息
        $data['village_id'] = $this->adminUser['village_id'];
        if (empty($data['village_id'])){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $data['page'] = $this->request->param('page', 0, 'intval');
        $data['page']=empty($data['page'])?1:$data['page'];
        $data['limit']= $this->request->param('limit', 10, 'intval');
        $data['bind_type'] = $this->request->param('bind_type', 0, 'intval');
        $data['rule_id'] = $this->request->param('rule_id', 0, 'intval');
        $is_user_bind = $this->request->param('is_user_bind', 0, 'intval');  //0全部 1有绑定住户 2无绑定住户
        if (empty($data['rule_id'] )){
            return api_output(1001, [], '收费标准不能为空！');
        }

        try {
            $serviceChargeRule = new HouseNewChargeRuleService();
            $ruleInfo= $serviceChargeRule->getRuleInfo($data['rule_id']);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }


        if (empty($data['bind_type'] )){
            return api_output(1001, [], '收费标准绑定类型不能为空！');
        }
        // 房产
        if ($data['bind_type']==1){
            $data['vacancy_id'] = $this->request->param('vacancy', '', 'trim');
            $serviceChargeProject = new HouseVillageSingleService();
            $where=[];
            $xwhere=array();
            $where[]=['a.village_id','=',$data['village_id']];
            $xwhere['village_id']=$data['village_id'];
            $where[]=['a.is_del','=',0];
            if (!empty($data['vacancy_id'])){
              //   $where[]=['a.pigcms_id','=',$data['vacancy_id'][3]];
                if (isset($data['vacancy_id'][3])){
                    $where[]=['a.pigcms_id','=',$data['vacancy_id'][3]];
                    $xwhere['vacancy_id']=$data['vacancy_id'][3];
                }elseif (isset($data['vacancy_id'][2])){
                    $where[]=['a.layer_id','=',$data['vacancy_id'][2]];
                    $xwhere['layer_id']=$data['vacancy_id'][2];
                }elseif (isset($data['vacancy_id'][1])){
                    $where[]=['a.floor_id','=',$data['vacancy_id'][1]];
                    $xwhere['floor_id']=$data['vacancy_id'][1];
                } else{
                    $where[]=['a.single_id','=',$data['vacancy_id'][0]];
                    $xwhere['single_id']=$data['vacancy_id'][0];
                }
            }
            $fieldStr='a.*,b.single_name,c.floor_name,d.layer_name';
            try {
                $extra_data=array('nobinded'=>true,'is_user_bind'=>$is_user_bind,'xwhere'=>$xwhere);
                $abbBindList = $serviceChargeProject->getVacancyList($data['rule_id'],$where,$fieldStr,$data['page'],$data['limit'],$extra_data);
            } catch (\Exception $e) {
                return api_output_error(-1, $e->getMessage());
            }
        }else{
            // 车位
            $data['garage_id'] = $this->request->param('garage_id', 0, 'intval');
            $data['position_num'] = $this->request->param('position_num', '', 'trim');
            $serviceChargeProject = new HouseVillageParkingService();
            $serviceHouseNewParking = new HouseNewParkingService();
            $park_config=$serviceHouseNewParking->getParkConfigInfo(['village_id'=>$data['village_id']]);


            $where=[];
            $where[]=['pp.village_id','=',$data['village_id']];
            if (!empty($park_config)&&$park_config['children_position_type']==1){
                $where[]=['pp.children_type','=',1];
            }
            if (!empty($data['garage_id'])){
                $where[]=['pp.garage_id','=',$data['garage_id']];
            }
            if (!empty($data['position_num'])){
                $where[]=['pp.position_num','=',$data['position_num']];
            }

            try {
                $abbBindList = $serviceChargeProject->getParkingPositionList($data['rule_id'],$where,'pp.*,pg.garage_num',$data['page'],$data['limit'],'pp.position_id DESC',true);
            } catch (\Exception $e) {
                return api_output_error(-1, $e->getMessage());
            }
        }

        $abbBindList['ruleInfo']= $ruleInfo;
        return api_output(0, $abbBindList);
    }


    public function getGarageList(){
        // 获取登录信息
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $service_garage=new HouseVillageParkingService();
        $garageList=$service_garage->getParkingGarageLists(['village_id'=>$village_id,'status'=>1],'*',0,0);
        return api_output(0, $garageList);

    }

    /**
     * 删除收费项目
     * 1、如果收费项目是空的，则可以直接删除
     * 2、如果是收费项目非空需要终止，然后需要提醒先删除收费标准，再删除。
     * @return \json
     * @author cc
     */
    public function deleteProjectIdDel()
    {
        // 获取登录信息
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }

        $projectId = $this->request->param('id', 0, 'intval');
        $HouseNewChargeProjectService = new HouseNewChargeProjectService();
        $where['village_id'] = $village_id;
        $where['id'] = $projectId;
        $field = 'id,subject_id,village_id';
        try{
            $projectInfo = $HouseNewChargeProjectService->getOneChargeProject($where,$field);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }

        if (empty($projectInfo)){
            return api_output(1001, [], '该收费项已不存在！！');
        }

        $HouseNewChargeRuleService = new HouseNewChargeRuleService();
        $param=[
            'village_id'=>$projectInfo['village_id'],
            'charge_project_id'=>$projectInfo['id']
        ];

        $projectIdRuleCount = $HouseNewChargeRuleService->getChargeCountByProjectId($param);

        if ($projectIdRuleCount >= 1){
            return api_output(1001, [], '该收费项目现在已关联（'.$projectIdRuleCount.'）条收费标准，请到【收费标准管理】里先删除收费标准”！！');
        }

        try {
            $delWhere['id'] = $projectInfo['id'];
            $delWhere['village_id'] = $projectInfo['village_id'];
            $delStatu = $HouseNewChargeProjectService->deleteChargeProject($delWhere);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if ($delStatu) {
            return api_output(0, '删除成功');
        } else {
            return api_output(1003, [], '删除失败，请刷新页面再试！');
        }
    }

    /**
     * 获取收费科目下的收费项目
     * User: zhanghan
     * Date: 2022/1/11
     * Time: 14:00
     * @return \json
     */
    public function getChargeProject(){
        $village_id = $this->adminUser['village_id'];
        $subject_id =  $this->request->post('subject_id');
        if(empty($subject_id)){
            return api_output_error(1003, '请选择科目');
        }

        $HouseNewChargeProjectService = new HouseNewChargeProjectService();
        $where = [];
        $where[] = ['p.village_id','=',$village_id];
        $where[] = ['p.subject_id','=',$subject_id];
        $where[] = ['p.status','=',1];
        try{
            $list = $HouseNewChargeProjectService->getProjectList($where,'p.id,p.name,p.type');
        }catch (\Exception $e){
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0,$list);
    }

    public function getMonthParkRuleList(){
        // 获取登录信息
        $data['village_id'] = $this->adminUser['village_id'];
       //$data['village_id']=50;
        if (empty($data['village_id'])){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $data['page'] = $this->request->param('page', 0, 'intval');
        $data['page']=empty($data['page'])?1:$data['page'];
        $data['limit']=10;
        $data['garage_id'] = $this->request->param('garage_id', 0, 'intval');
        if (empty($data['garage_id'] )){
            return api_output(1001, [], '车库id不能为空！');
        }
        try {
            $serviceChargeRule = new HouseNewChargeRuleService();
            $ruleInfo= $serviceChargeRule->getMonthParkRuleList($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$ruleInfo);
        
    }

    /**
     * 查询临时车收费标准
     * @author:zhubaodi
     * @date_time: 2022/11/18 11:19
     */
    public function getTempParkRuleList(){
        // 获取登录信息
        $data['village_id'] = $this->adminUser['village_id'];
        //$data['village_id']=50;
        if (empty($data['village_id'])){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $data['page'] = $this->request->param('page', 0, 'intval');
        $data['page']=empty($data['page'])?1:$data['page'];
        $data['limit']=10;
        $data['garage_id'] = $this->request->param('garage_id', 0, 'intval');
        if (empty($data['garage_id'] )){
            return api_output(1001, [], '车库id不能为空！');
        }
        try {
            $serviceChargeRule = new HouseNewChargeRuleService();
            $ruleInfo= $serviceChargeRule->getTempParkRuleList($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$ruleInfo);
    }
    
    
    public function checkTakeEffectTime(){
        $property_id = $this->adminUser['property_id'];
        if(!$property_id){
            return api_output_error(1001,'缺少必传参数');
        }
        try{
            $result=(new HouseNewPorpertyService())->checkTakeEffectTime($property_id);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$result);
    }
    
    public function savePrepaidDiscount(){
        
        $village_id = $this->adminUser['village_id'];
        $fid =  $this->request->post('id',0,'intval'); //id
        $expire_time =  $this->request->post('expire_time','','trim'); //到期时间
        $discount_type =  $this->request->post('discount_type',0,'intval'); //1按月2按季度3按年4日5周
        $charge_rule_id =  $this->request->post('charge_rule_id',0,'intval'); //标准id
        $charge_project_id =  $this->request->post('charge_project_id',0,'intval'); //收费项目id
        $bill_date_set =  $this->request->post('bill_date_set',0,'intval'); //标准是按什么的1日2月3年
        $prepayment =  $this->request->post('prepayment','');
        $order_discount_type =  $this->request->post('order_discount_type',1,'intval'); //1账单月数优惠2账单提前缴费优惠3欠费缴费优惠
        $con_type =  $this->request->post('con_type',0,'intval'); //1月数2个数3天数
        $con_num =  $this->request->post('con_num',0,'intval');  //满足的条件月数或个数
        $project_cycle_type =  $this->request->post('project_cycle_type',0,'intval'); //项目的收费模式 1:一次性费用 2：周期性费用
        $msg_err='预缴时长';
        $expire_err='请正确选择预缴优惠到期时间！';
        if($order_discount_type==2){
            $expire_err='请正确选择提前缴费优惠到期时间';
            $msg_err='提前缴费';
        }elseif ($order_discount_type==3){
            $msg_err='欠费时长优惠';
            $con_type=3;  //天数
            $con_num=0;
        }
        if(empty($prepayment)){
            return api_output_error(1001,$msg_err.'设置错误，请检查设置！');
        }
        if($order_discount_type<2 && !in_array($discount_type,[1,2,3])){
            return api_output_error(1001,'缴费类型选择错误！');
        }
        if($order_discount_type<3 && $bill_date_set==3 && $discount_type!=3){
            return api_output_error(1001,'缴费类型选择错误！');
        }
        $expire_time=$expire_time ? strtotime($expire_time):0;
        if(empty($expire_time) && $order_discount_type<2){
            
            return api_output_error(1001,$expire_err);
        }
        $expire_time=$expire_time>0 ? $expire_time+(24*3600-1):0; //到今天的 23:59:59

        try{
            $prepaidDiscountService=new HouseNewChargePrepaidDiscountService();
            $dataArr=array();
            $dataArr['discount_type']=$discount_type;
            $dataArr['expire_time']=$expire_time;
            $dataArr['charge_rule_id']=$charge_rule_id;
            $dataArr['bill_create_set']=$bill_date_set;
            $dataArr['charge_project_id']=$charge_project_id;
            $dataArr['type']=1; //1折扣 
            $dataArr['status']=1; //1开启
            $nowtime=time();
            $dataArr['add_time']=$nowtime;
            $dataArr['update_time']=$nowtime;
            $dataArr['order_discount_type']=$order_discount_type;
            $dataArr['con_type']=$con_type;
            $dataArr['con_num']=$con_num;
            $ret=array('id'=>$fid);
            if($fid>0){
                $result=$prepaidDiscountService->saveAllDatas($fid,$village_id,$dataArr,$prepayment);
            }else{
                $dataArr['village_id']=$village_id;
                $result=$prepaidDiscountService->addAllDatas($dataArr,$prepayment);
                $ret['id']=$result;
            }
            return api_output(0,$ret);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
    }
    public function chargePrepaidDiscountList(){
        $charge_project_id = $this->request->param('charge_project_id');
        $charge_project_id= intval($charge_project_id);
        $charge_rule_id = $this->request->param('charge_rule_id');
        $charge_rule_id= intval($charge_rule_id);
        $village_id = $this->adminUser['village_id'];
        $bill_date_set = $this->request->param('bill_date_set');
        $bill_date_set= intval($bill_date_set);
        try{
            $prepaidDiscountService=new HouseNewChargePrepaidDiscountService();
            $whereArr=[];
            $whereArr[]=['fid','=',0];
            $whereArr[]=['charge_rule_id','=',$charge_rule_id];
            $whereArr[]=['charge_project_id','=',$charge_project_id];
            $whereArr[]=['village_id','=',$village_id];
            //$whereArr[]=['bill_create_set','=',$bill_date_set];
            $whereArr[]=['status','=',1];
            $result=$prepaidDiscountService->getAllList($whereArr);
            $configCustomizationService=new ConfigCustomizationService();
            $func_kaidalaicn_special=$configCustomizationService->getfuncKaidalaicnSpecial();
            $result['func_kaidalaicn_special']=0;
            if($func_kaidalaicn_special){
                $result['func_kaidalaicn_special']=1;
            }
            return api_output(0,$result);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
    }
    public function  getchargePrepaidDiscountEdit(){
        $charge_rule_id = $this->request->param('charge_rule_id');
        $charge_rule_id= intval($charge_rule_id);
        $village_id = $this->adminUser['village_id'];
        $bill_create_set = $this->request->param('bill_date_set');
        $charge_project_id = $this->request->param('charge_project_id');
        $charge_project_id= intval($charge_project_id);
        $fid =  $this->request->post('id',0,'int'); //id
        try{
            $prepaidDiscountService=new HouseNewChargePrepaidDiscountService();
            $whereArr=[];
            $whereArr[]=['id','=',$fid];
            $whereArr[]=['charge_rule_id','=',$charge_rule_id];
            $whereArr[]=['charge_project_id','=',$charge_project_id];
            $whereArr[]=['village_id','=',$village_id];
            $whereArr[]=['status','=',1];
            $result=$prepaidDiscountService->getEditData($whereArr);
            return api_output(0,$result);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
    }
    public function delChargePrepaidDiscount(){
        $fid =  $this->request->post('id',0,'int'); //id
        $village_id = $this->adminUser['village_id'];
        if($fid<1){
            return api_output_error(1001,'参数ID出错！');
        }
        try{
            $prepaidDiscountService=new HouseNewChargePrepaidDiscountService();
            $result=$prepaidDiscountService->delChargePrepaidDiscount($fid,$village_id);
            return api_output(0,$result);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
    }
    
    public function getChargeOtherConfigInfo(){
        $village_id = $this->adminUser['village_id'];
        try {
            $serviceChargeRule = new HouseNewChargeRuleService();
            $configInfo= $serviceChargeRule->getChargeOtherConfigInfo($village_id);
            $configCustomizationService=new ConfigCustomizationService();
            $is_aihorse_customized=$configCustomizationService->getAihorseCustomizedJudge();
            $configInfo['is_aihorse_customized']=$is_aihorse_customized ? 1:0;
            $configInfo['show_advance_order_day']=$is_aihorse_customized ? 1:0;
            $func_kaidalaicn_special=$configCustomizationService->getfuncKaidalaicnSpecial();
            if($func_kaidalaicn_special){
                $configInfo['show_advance_order_day']=1;
            }
            $jz_wsxdsj_net_customized_open=$configCustomizationService->getWSXCustomizedOpen();
            $configInfo['since_order_service_end_time']=$jz_wsxdsj_net_customized_open ? 1:0;
            return api_output(0,$configInfo);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * 获取折扣率小数位数
     */
    public function getPrecisionNum()
    {
        $precision = 0;
        $getSetDiscountPrecision = (new ConfigCustomizationService())->getSetDiscountPrecision();
        if($getSetDiscountPrecision){
            $precision = 2;
        }
        return api_output(0,['precision'=>$precision]);
    }

    /***获取红冲和补交收费项目标准信息*****/
    public function getSpecialChargeList(){
        $village_id = $this->adminUser['village_id'];
        try {
            $returnArr=array('red_reversal'=>array('projectList'=>array(),'ruleList'=>array()),'append_money'=>array('projectList'=>array(),'ruleList'=>array()));
            $houseNewChargeProjectService = new HouseNewChargeProjectService();
            $whereArr=array(['village_id','=',0],['subject_id','=',0],['status','=',5],['from_type','in',['red_reversal','append_money']]);
            $ruleList=$houseNewChargeProjectService->getChargeRuleList($whereArr,'id,village_id,charge_project_id,from_type,charge_name,unit_price,fees_type,status','id asc');
            $projectIds=array();
            if($ruleList){
                foreach ($ruleList as $ritem){
                    $oneCharge=array();
                    if(!isset($projectIds[$ritem['charge_project_id']])){
                        $whereArrTmp=array(['id','=',$ritem['charge_project_id']],['village_id','=',0],['subject_id','=',0],['type','=',1],['status','=',5]);
                        $oneChargeObj=$houseNewChargeProjectService->getOneChargeProject($whereArrTmp,'id,village_id,name,type,status');
                        if($oneChargeObj && !$oneChargeObj->isEmpty()){
                            $oneCharge=$oneChargeObj->toArray();
                            $projectIds[$oneCharge['id']]=$oneCharge;
                        }
                    }
                    if($ritem['from_type']=='red_reversal'){
                        $returnArr['red_reversal']['ruleList'][]=$ritem;
                        if(isset($projectIds[$ritem['charge_project_id']])){
                            $returnArr['red_reversal']['projectList'][$ritem['charge_project_id']]=$projectIds[$ritem['charge_project_id']];
                        }
                    }else if($ritem['from_type']=='append_money'){
                        $returnArr['append_money']['ruleList'][]=$ritem;
                        if(isset($projectIds[$ritem['charge_project_id']])){
                            $returnArr['append_money']['projectList'][$ritem['charge_project_id']]=$projectIds[$ritem['charge_project_id']];
                        }
                    }
                }
            }
            $returnArr['red_reversal']['projectList']=array_values($returnArr['red_reversal']['projectList']);
            $returnArr['append_money']['projectList']=array_values($returnArr['append_money']['projectList']);
            return api_output(0,$returnArr);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
}