<?php
/**
 * 企业微信渠道活码
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/3/12 15:00
 */
namespace app\community\controller\village_api;
use app\community\controller\CommunityBaseController;

use app\community\model\service\ChannelCodeService;
use app\community\model\service\QywxService;
use app\community\model\service\VillageQywxMessageService;

class ChannelCodeController extends CommunityBaseController
{

    /**
     * Notes: 获取分组列表
     * @return \json
     * @author: wanzy
     * @date_time: 2021/3/12 19:21
     */
    public function channelMenuList() {
        $village_id = $this->adminUser['village_id'];
        $property_id =  $this->adminUser['property_id'];
        $login_role = $this->login_role;
        $gid = $this->request->param('id',0,'int');
        if (in_array($login_role,$this->propertyRole)) {
            $type = 2;//物业
        } else {
            $type = 1;//小区
        }
        if(!$village_id && !$property_id)
        {
            return api_output_error(1002,'必传参数缺失');
        }
        $serviceChannelCode = new ChannelCodeService();
        try{
            $list = $serviceChannelCode->getGroupMenu($type, $property_id, $village_id,false,$gid);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * Notes: 添加编辑分组
     * @return int|string
     * @author: wanzy
     * @date_time: 2021/3/12 19:20
     */
    public function subCodeGroup() {
        $village_id = $this->adminUser['village_id'];
        $property_id =  $this->adminUser['property_id'];
        $name = $this->request->param('name','','trim');
        $id = $this->request->param('id','','int');
        $pid = $this->request->param('pid','0','int');
        $login_role = $this->login_role;
        if(!$name){
            return api_output_error(1001,'请输入分组名称');
        }
        if (in_array($login_role,$this->propertyRole)) {
            $type = 2;//物业
            $param_id = $property_id;
        } else {
            $type = 1;//小区
            $param_id = $village_id;
        }
        $serviceChannelCode = new ChannelCodeService();
        $data = [
            'name'=>$name,
            'pid'=>$pid,
            'type'=>$type,
        ];
        if ($id) {
            $info = $serviceChannelCode->getGroupInfo($id);
            if (empty($info) || empty($info['info']) ) {
                return api_output_error(1001,'当前编辑对象不存在或者已经被删除');
            }
            $info = $info['info'];
            $login_role = $this->login_role;
            $village_id = $this->adminUser['village_id'];
            $property_id =  $this->adminUser['property_id'];
            if (in_array($login_role,$this->propertyRole)) {
                //物业
                if ($property_id!=$info['property_id']) {
                    return api_output_error(1001,'您没有权限编辑当前对象');
                }
            } else {
                //小区
                if ($village_id!=$info['village_id']) {
                    return api_output_error(1001,'您没有权限编辑当前对象');
                }
            }
        }
        try{
            $res = $serviceChannelCode->subGroup($id,$data,$param_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }


    /**
     * Notes:获取分组详情
     * @return \json
     * @author: wanzy
     * @date_time: 2021/3/12 19:20
     */
    public function getGroupInfo()
    {
        $id = $this->request->param('id','','int');
        if(!$id){
            return api_output_error(1001,'请输入分组名称');
        }
        $serviceChannelCode = new ChannelCodeService();
        try{
            $data = $serviceChannelCode->getGroupInfo($id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * Notes: 删除分组
     * @return \json
     * @author: wanzy
     * @date_time: 2021/3/13 11:18
     */
    public function delGroup()
    {
        $id = $this->request->param('id','','int');
        if(!$id){
            return api_output_error(1001,'缺少删除对象');
        }
        $serviceChannelCode = new ChannelCodeService();
        $info = $serviceChannelCode->getGroupInfo($id);
        if (empty($info) || empty($info['info']) ) {
            return api_output_error(1001,'当前删除对象不存在或者已经被删除');
        }
        $info = $info['info'];
        $login_role = $this->login_role;
        $village_id = $this->adminUser['village_id'];
        $property_id =  $this->adminUser['property_id'];
        if (in_array($login_role,$this->propertyRole)) {
            //物业
            if ($property_id!=$info['property_id']) {
                return api_output_error(1001,'您没有权限删除当前对象');
            }
        } else {
            //小区
            if ($village_id!=$info['village_id']) {
                return api_output_error(1001,'您没有权限删除当前对象');
            }
        }
        try{
            $data = $serviceChannelCode->delGroup($id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * Notes: 获取活码
     * @return \json
     * @author: wanzy
     * @date_time: 2021/3/15 14:36
     */
    public function channelCodeList() {
        $id = $this->request->param('id','0','intval');
        $work_id = $this->request->param('work_id','0','intval');
        $code_name = $this->request->param('code_name','','strval');
        $page = $this->request->param('page','1','intval');
        $serviceChannelCode = new ChannelCodeService();
        try{
            $login_role = $this->login_role;
            if (in_array($login_role,$this->propertyRole)) {
                //物业
                $type = 2;
                $village_id = 0;
                $property_id =  $this->adminUser['property_id'];
            } else {
                //小区
                $type = 1;
                $village_id = $this->adminUser['village_id'];
                $property_id =  $this->adminUser['property_id'];
            }
            $where = [];
            $where['code_group_id'] = $id;
            $where['work_id'] = $work_id;
            $where['type'] = $type;
            $where['village_id'] = $village_id;
            $where['property_id'] = $property_id;
            $where['code_name'] = $code_name;
            $where['page'] = $page;
            $list = $serviceChannelCode->getChannelCodeList($where);
            $work_field = 'wid,name';
            $work_list = $serviceChannelCode->getWorkList($village_id,$property_id,$work_field);
            $list['work_list'] = $work_list;
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * Notes: 获取添加或者编辑信息
     * @return \json
     * @author: wanzy
     * @date_time: 2021/3/18 14:36
     */
    public function addCodeInfo() {
        $code_id = $this->request->param('code_id','0','intval');
        $serviceChannelCode = new ChannelCodeService();
        try{
            $where = [];
            $where['code_id'] = $code_id;
            $login_role = $this->login_role;
            $where_label = [];
            if (in_array($login_role,$this->propertyRole)) {
                //物业
                $where['type'] = 2;
                $village_id = 0;
                $property_id =  $this->adminUser['property_id'];
                $where_label[] = ['property_id','=',$property_id];
            } else {
                //小区
                $where['type'] = 1;
                $village_id = $this->adminUser['village_id'];
                $property_id =  $this->adminUser['property_id'];
                $where_label[] = ['add_type','=',1];
                $where_label[] = ['village_id','=',$village_id];
            }
            $where['village_id'] = $village_id;
            $where['property_id'] = $property_id;
            $list = $serviceChannelCode->getChannelCodeDetail($where);
            $service_village_qywx_message = new VillageQywxMessageService();
            $where_label[] = ['status','=',1];
            $label_data = $service_village_qywx_message->getQywxCodeLabel($where_label,'label_group_id,label_group_name',0);
            $service_qywx = new QywxService();
            $qywxBind = $service_qywx->getEnterpriseWxBind($property_id,'corp_name');
            $list['qywx_bind'] = $qywxBind;
            $list['label_data'] = $label_data;
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    public function uploadCode() {
        $code_id_arr = $this->request->param('code_id_arr');
        if(!$code_id_arr){
            return api_output_error(1001,'请选择下载对象');
        }
        $code_group_id = $this->request->param('code_group_id');
        $serviceChannelCode = new ChannelCodeService();
        $arr = [];
        try{
            $where = [];
            $where[] = ['code_id', 'in', $code_id_arr];
            $upload = $serviceChannelCode->uploadCode($where, $code_group_id);
            $arr['code_id_arr'] = $code_id_arr;
            $arr['down_url'] = $upload;
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$arr);
    }

    /**
     * Notes: 删除单条渠道活码数据
     * @return \json
     * @author: wanzy
     * @date_time: 2021/3/23 16:12
     */
    public function delCode() {
        $code_id = $this->request->param('code_id','0','intval');
        if(!$code_id){
            return api_output_error(1001,'请选择删除对象');
        }
        $serviceChannelCode = new ChannelCodeService();
        $arr = [];
        try{
            $where = [];
            $where[] = ['code_id', '=', $code_id];
            $village_id = $this->adminUser['village_id'];
            $property_id =  $this->adminUser['property_id'];
            $login_role = $this->login_role;
            if (in_array($login_role,$this->propertyRole)) {
                $type = 2;//物业
                $param_id = $property_id;
            } else {
                $type = 1;//小区
                $param_id = $village_id;
            }
            $del = $serviceChannelCode->delCode($where, $type, $param_id);
            $arr['code_id'] = $code_id;
            $arr['del'] = $del;
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$arr);
    }
    // 批量删除
    public function batchDelCode() {
        $code_id_arr = $this->request->param('code_id_arr');
        if(!$code_id_arr){
            return api_output_error(1001,'请选择删除对象');
        }
        $serviceChannelCode = new ChannelCodeService();
        $arr = [];
        try{
            $where = [];
            $where[] = ['code_id', 'in', $code_id_arr];
            $del = $serviceChannelCode->delCode($where);
            $arr['code_id_arr'] = $code_id_arr;
            $arr['del'] = $del;
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$arr);
    }


    /**
     * Notes: 单独获取标签信息
     * @return \json
     * @author: wanzy
     * @date_time: 2021/3/18 17:16
     */
    public function getLabelData() {
        $service_village_qywx_message = new VillageQywxMessageService();
        $list = [];
        try{
            $login_role = $this->login_role;
            $where_label = [];
            if (in_array($login_role,$this->propertyRole)) {
                //物业
                $property_id =  $this->adminUser['property_id'];
                $where_label[] = ['property_id','=',$property_id];
            } else {
                //小区
                $village_id = $this->adminUser['village_id'];
                $where_label[] = ['add_type','=',1];
                $where_label[] = ['village_id','=',$village_id];
            }
            $where_label[] = ['status','=',1];
            $label_data = $service_village_qywx_message->getQywxCodeLabel($where_label,'label_group_id,label_group_name',0);
            $list['label_data'] = $label_data;
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * Notes: 获取工作人员列表
     * @return \json
     * @author: wanzy
     * @date_time: 2021/3/18 18:44
     */
    public function getWorkList() {
        $serviceChannelCode = new ChannelCodeService();
        try{
            $list = [];
            $login_role = $this->login_role;
            if (in_array($login_role,$this->propertyRole)) {
                //物业
                $village_id = 0;
                $property_id =  $this->adminUser['property_id'];
            } else {
                //小区
                $village_id = $this->adminUser['village_id'];
                $property_id =  $this->adminUser['property_id'];
            }
            $work_field = 'wid,name';
            $work_list = $serviceChannelCode->getWorkList($village_id,$property_id, $work_field);
            $list['work_list'] = $work_list;
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * Notes: 添加编辑渠道活码
     * @return \json
     * @author: wanzy
     * @date_time: 2021/3/23 13:55
     */
    public function addCode() {
        $code_id = $this->request->param('code_id','0','intval');
        $code_group_id = $this->request->param('code_group_id','0','intval');
        $code_name = $this->request->param('code_name','','trim');
        $work_arr = $this->request->param('work_arr');
        $is_send = $this->request->param('is_send','1','intval');
        $skip_verify = $this->request->param('skip_verify','1','intval');
        $engine_content_id = $this->request->param('engine_content_id','0','intval');
        $welcome_tip = $this->request->param('welcome_tip');
        $welcome_img = $this->request->param('welcome_img','','trim');
        $welcome_url = $this->request->param('welcome_url','','trim');
        $tags = $this->request->param('tags');
        $serviceChannelCode = new ChannelCodeService();
        $arr = [];
        try{
            $data = [];
            $data['code_id'] = $code_id ? $code_id : 0;
            $data['code_group_id'] = $code_group_id;
            $data['code_name'] = $code_name;
            $data['work_arr'] = $work_arr;
            $data['is_send'] = $is_send ? $is_send : 1;
            $data['skip_verify'] = $skip_verify ? $skip_verify : 1;
            $data['engine_content_id'] = $engine_content_id ? $engine_content_id : 0;
            $data['welcome_tip'] = $welcome_tip ? $welcome_tip : '';
            $data['welcome_img'] = $welcome_img ? $welcome_img : '';
            $data['welcome_url'] = $welcome_url ? $welcome_url : '';
            $data['tags'] = !empty($tags) ? $tags : [];
            $login_role = $this->login_role;
            if (in_array($login_role,$this->propertyRole)) {
                //物业
                $data['add_type'] = 2;
                $property_id =  $this->adminUser['property_id'];
                $data['property_id'] = $property_id;
            } else {
                //小区
                $data['add_type'] = 1;
                $village_id = $this->adminUser['village_id'];
                $property_id =  $this->adminUser['property_id'];
                $data['village_id'] = $village_id;
                $data['property_id'] = $property_id;
            }
            $set_code_id = $serviceChannelCode->addCode($data);
            $arr['code_id'] = $set_code_id;
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$arr);
    }
}