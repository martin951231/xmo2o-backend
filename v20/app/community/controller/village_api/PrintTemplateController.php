<?php
/**
 *======================================================
 * Created by : PhpStorm
 * User: zhanghan
 * Date: 2022/1/28
 * Time: 16:41
 *======================================================
 */

namespace app\community\controller\village_api;


use app\common\model\service\config\ConfigCustomizationService;
use app\community\controller\CommunityBaseController;
use app\community\model\db\HouseVillagePrintCustom;
use app\community\model\service\HouseVillageService;
use app\community\model\service\PrintTemplateService;

class PrintTemplateController extends CommunityBaseController
{
    /**
     * 获取模板列表
     * User: zhanghan
     * Date: 2022/1/28
     * Time: 17:08
     * @return \json
     */
    public function getPrintTemplateList(){
        $village_id = $this->adminUser['village_id'];
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',10);
        $keyword = $this->request->post('keyword',1);

        $field = 'template_id,title,desc,top_title,col_num,type';
        $where = [];
        $where[] = ['village_id','=',$village_id];
        $where[] = ['is_new','=',1];
        if(!empty($keyword)){
            $where[] = ['title','like','%'.$keyword.'%'];
        }

        $printTemplate = new PrintTemplateService();
        try {
            $data = $printTemplate->getPrintTemplateList($where,$field,$page,$limit);
            $houseVillageService=new HouseVillageService();
            $data['role_addtemp']=$houseVillageService->checkPermissionMenu(112114,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $data['role_tempset']=$houseVillageService->checkPermissionMenu(112115,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $data['role_edittemp']=$houseVillageService->checkPermissionMenu(112116,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $data['role_deltemp']=$houseVillageService->checkPermissionMenu(112117,$this->adminUser,$this->login_role,$this->dismissPermissionRole);
            $isAihorseCustomizedJudge = (new ConfigCustomizationService())->getAihorseCustomizedJudge();
            $data['is_aihorse_customized']=0;
            $data['is_digital_seal']=0;
            if($isAihorseCustomizedJudge>0){
                $data['is_aihorse_customized'] = 1;
                $data['is_digital_seal']=1;
            }
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output(0,$data,'模板列表');
    }

    /**
     * 获取模板详情
     * User: zhanghan
     * Date: 2022/1/29
     * Time: 9:38
     * @return \json
     */
    public function getTemplateDetail(){
        $village_id = $this->adminUser['village_id'];
        $template_id = $this->request->post('template_id','');
        if(empty($template_id)){
            return api_output_error('1001','缺少模板参数');
        }
        if(empty($village_id)){
            return api_output_error('1002','请重新登录');
        }
        $where = [];
        $where[] = ['village_id','=',$village_id];
        $where[] = ['template_id','=',$template_id];

        $printTemplate = new PrintTemplateService();
        try {
            $data = $printTemplate->getTemplateDetail($where);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output(0,$data,'模板详情');
    }

    /**
     * 新增、编辑保存模板
     * User: zhanghan
     * Date: 2022/1/29
     * Time: 10:09
     * @return \json
     */
    public function templateAdd(){
        $template_id = $this->request->post('template_id','');
        $title = $this->request->post('title','');
        $type = $this->request->post('type',1);
        $col_num = $this->request->post('col_num',3);
        $top_title = $this->request->post('top_title','');
        $desc = $this->request->post('desc','');
        $bak_content = $this->request->post('bak_content','');
        $seal_img = $this->request->post('seal_img','','trim');
        $bak_content = trim($bak_content);
        if(empty($title)){
           return api_output_error('1001','模板名称必填');
        }
        $village_id = $this->adminUser['village_id'];

        $param = [];
        $param['title'] = $title;
        $param['type'] = $type;
        $param['col_num'] = $col_num;
        $param['top_title'] = $top_title;
        $param['desc'] = $desc;
        $param['village_id'] = $village_id;
        $param['bak_content'] = $bak_content;
        $param['seal_img'] = $seal_img;
        
        $printTemplate = new PrintTemplateService();
        try {
            $data = $printTemplate->templateAdd($template_id,$param);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output(0,$data,'模板保存成功');
    }

    /**
     * 删除模板
     * User: zhanghan
     * Date: 2022/1/29
     * Time: 11:04
     * @return \json
     */
    public function delTemplate(){
        $template_id = $this->request->post('template_id','');
        $village_id = $this->adminUser['village_id'];
        if(empty($template_id) || empty($village_id)){
            return api_output_error('1001','模板ID或小区ID不能为空');
        }

        $printTemplate = new PrintTemplateService();
        try {
            $data = $printTemplate->delTemplate($template_id,$village_id);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output(0,$data,'打印模板删除成功');
    }

    /**
     * 获取打印模板配置
     * User: zhanghan
     * Date: 2022/1/29
     * Time: 13:38
     * @return \json
     */
    public function getTemplateSet(){
        $template_id = $this->request->post('template_id','');
        $village_id = $this->adminUser['village_id'];
        if(empty($template_id) || empty($village_id)){
            return api_output_error('1001','模板ID或小区ID不能为空');
        }
        $printTemplate = new PrintTemplateService();
        try {
            $data = $printTemplate->getTemplateSet($template_id,$village_id);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output(0,$data,'打印模板删除成功');
    }

    /**
     * 获取配置区选择字段列表
     * User: zhanghan
     * Date: 2022/2/7
     * Time: 13:05
     * @return \json
     */
    public function getPrintCustomConfigureList(){
        $type = $this->request->post('type',1);
        $template_id = $this->request->post('template_id',1);

        if(empty($type)){
            return api_output_error('1001','缺少字段所属位置参数');
        }

        $field = 'configure_id,title,type,is_new';
        $printTemplate = new PrintTemplateService();
        try {
            $data = $printTemplate->getPrintCustomConfigureList($template_id,$type,$field);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output(0,$data,'模板字段列表');
    }

    /**
     * 保存模板配置详情
     * User: zhanghan
     * Date: 2022/2/7
     * Time: 14:35
     * @return \json
     */
    public function addPrintTemplateCustom(){
        $village_id = $this->adminUser['village_id'];
        $ids = $this->request->post('ids',1);
        $template_id = $this->request->post('template_id',1);
        $font_set = $this->request->post('font_set','');
        $blankline = $this->request->post('blankline',0);   //模板三暴露的空白行 数
        if(empty($village_id)){
            return api_output_error('1002','请重新登录');
        }
        if(empty($template_id)){
            return api_output_error('1001','缺少模板参数');
        }

        $printTemplate = new PrintTemplateService();
        try {
            $extra_data=array();
            $extra_data['blankline']=intval($blankline);
            $data = $printTemplate->addPrintTemplateCustom($village_id,$template_id,$ids,$font_set,$extra_data);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output(0,$data,'保存模板详情');
    }

    /**
     * 打印模板后，更改开票状态接口
     * User: zhanghan
     * Date: 2022/2/9
     * Time: 13:41
     * @return \json
     */
    public function printRecordUrl(){
        $order_id = $this->request->post('order_id',0);
        $pigcms_id = $this->request->post('pigcms_id',0);
        $choice_ids = $this->request->post('choice_ids',[]);
        if (empty($order_id) && empty($choice_ids)){
            return api_output_error(1001,'缺少必传参数');
        }
        $printTempLateType = $this->request->post('printTempLateType','','trim');

        $printTemplate = new PrintTemplateService();
        try{
            $data = $printTemplate->printRecordUrl($order_id,$pigcms_id,$choice_ids,$printTempLateType);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$data);
    }
}