<?php
/**
 * 社区管理端App
 * 工单处理挪至物业移动管理端，物业人员可统一进行登录管理
 * @author weili
 * @date 2020/10/20
 */

namespace app\community\controller\manage_api\v1;


use app\community\controller\manage_api\BaseController;
use app\community\model\service\HouseContactWayUserService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\PropertyManagementService;
use function Qiniu\explodeUpToken;

class PropertyManagementController extends BaseController
{
    //首页
    public function getRepairOrderIndex()
    {
//        $property_id = $this->request->param('property_id','','intval');
        $info = $this->getLoginInfo();
        if (isset($info['status']) && $info['status']!=1000) {
            return api_output($info['status'],[],$info['msg']);
        }
//        if(!$property_id){
//            return api_output_error(1001,'必传参数缺失');
//        }
        $worker_id = $this->_uid;
        if (6==$this->login_role) {
            $wid = $this->_uid;
            $property_id = $this->login_info['property_id'];
        } elseif (4==$this->login_role || 5==$this->login_role) {
            $wid = 0;
            if (isset($this->login_info) && isset($this->login_info['property_id'])) {
                $property_id = $info['user']['property_id'];
            } else {
                $property_id = 0;
            }
        }
        if ($wid<1 && $property_id<1) {
            $msg = [
                'status' => 1002,
                'msg' => '登录失效，请重新登录！'
            ];
            return $msg;
        }
        if (isset($this->login_info) && isset($this->login_info['login_name'])) {
            $login_name = $this->login_info['login_name'];
        }
        $app_type = $this->request->post('app_type','');
        $servicePropertyManagement = new PropertyManagementService();
        try {
            $list = $servicePropertyManagement->getRepair($wid, $property_id, $login_name,$this->login_role,$app_type,$worker_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        // 获取客服联系路径
        $kf_phone = cfg('site_phone');
        $list['kf_phone'] = trim($kf_phone) ? trim($kf_phone): '';
        return api_output(0, $list, "成功");
    }
    /**
     * Notes: 工单列表
     * @return \json
     * @author: weili
     * @datetime: 2020/10/22 11:19
     */
    public function getWorkOrder()
    {
        // 外部联系人ID 兼容企微聊天侧边栏调取用户详情
        $userId = $this->request->post('userId');
        // 是否忽略登录
        $ignoreLogin = false;
        if(!empty($userId)){
            // 获取用户基本信息
            $db_house_contact_way_user_service = new HouseContactWayUserService();
            $user_info = $db_house_contact_way_user_service->gethouseContactWayUser($userId,'customer_id,uid,phone,property_id,village_id,bind_id');
            if(!empty($user_info)){
                $ignoreLogin = true;
            }
        }

        if(!$ignoreLogin){
            $info = $this->getLoginInfo();
            if (isset($info['status']) && $info['status']!=1000) {
                return api_output($info['status'],[],$info['msg']);
            }
            $wid = $this->_uid;
            if(!$wid){
                return api_output_error(1001,'必传参数缺失');
            }
        }else{
            $pigcms_id = $this->request->post('pigcms_id');
            if(!$pigcms_id){
                return api_output_error(1001,'必传参数缺失');
            }
        }
        $village_id=$this->request->param('village_id',0,'intval');
        if($this->login_info && isset($this->login_info['village_id']) && ($this->login_info['village_id']>0) ){
            $village_id=$this->login_info['village_id'];
        }
        $page = $this->request->param('page','0','int');
        $search = $this->request->param('search','','trim');
        $type = $this->request->param('type',0,'int'); //0表示全部 1已指派 2已受理 3已处理
//        $property_id = $this->request->param('property_id','','intval');
        $handle_type=0;  //0:分配，1：工作人员抢单
        $where=array();
        if($village_id>0){
            $where[]=['r.village_id','=',$village_id];
            $serviceHouseVillage = new HouseVillageService();
            $whereArr=array('village_id'=>$village_id);
            $villageInfo=$serviceHouseVillage->getHouseVillageInfo($whereArr,'village_id,handle_type');
            if($villageInfo && !$villageInfo->isEmpty()){
                $villageInfo=$villageInfo->toArray();
                $handle_type=$villageInfo['handle_type']>0 ? $villageInfo['handle_type']:0;
            }
        }
        if(!empty($type)){
            if(!in_array($type,[1,2,3])){
                return api_output_error(1001,'传参异常');
            }
            if($handle_type==1){
                if(!$ignoreLogin){
                    $where[] = ['wid','in',array(0,$wid)];
                }else{
                    $where[] = ['bind_id','in',array(0,$pigcms_id)];
                }
                if($type == 3){
                    $where[] = ['r.status','in',[3,4]];
                }elseif($type == 1) {
                    $where[] = ['r.status', '<', 2];
                }else{
                    $where[] = ['r.status', '=', $type];
                }
                $where[] = ['r.type','in',[1,3]];
            }else{
                if(!$ignoreLogin){
                    $where[] = ['wid','=',$wid];
                }else{
                    $where[] = ['bind_id','=',$pigcms_id];
                }
                if($type == 3){
                    $where[] = ['r.status','in',[3,4]];
                }else {
                    $where[] = ['r.status', '=', $type];
                }
                $where[] = ['r.type','in',[1,2,3]];
            }
        }else{
            if($handle_type==1){
                if(!$ignoreLogin){
                    $where[] = ['wid','in',array(0,$wid)];
                }else{
                    $where[] = ['bind_id','in',array(0,$pigcms_id)];
                }
                $where[] = ['r.type','in',[1,3]];
            }else{
                if(!$ignoreLogin){
                    $where[] = ['wid','=',$wid];
                }else{
                    $where[] = ['bind_id','=',$pigcms_id];
                }
                $where[] = ['r.type','in',[1,2,3]];
            }
        }
        if($search){
            $where[] = ['r.phone|r.user_name|u.name|u.phone','like','%'.$search.'%'];
        }

        $servicePropertyManagement = new PropertyManagementService();
        $limit = 10;//分页 每页展示对应条数
        if($page)
        {
            $page = ($page-1)*$limit;
        }
        $order = 'r.time desc,r.pigcms_id desc';
        $field='r.pigcms_id,r.village_id,r.type,r.status,r.reply_time,r.user_name,r.time,r.wid,r.reply_content,r.reply_time,r.cate_id,r.cate_fid,r.single_id,r.floor_id,r.layer_id,r.vacancy_id,u.single_id as u_single_id,u.floor_id as u_floor_id,u.layer_id as u_layer_id,u.vacancy_id as u_vacancy_id,r.comment,r.comment_time,r.status,r.content,r.phone,u.name,u.address,u.phone as uphone';
        try {
            $list = $servicePropertyManagement->getWorkOrderList($where, $page, $limit, $order, $field);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
    /**
     * Notes: 工单详情
     * @return \json
     * @author: weili
     * @datetime: 2020/10/22 11:18
     */
    public function getDetails()
    {
        $pigcmsId = $this->request->param('pigcms_id','','int');
        if(empty($pigcmsId)){
            return api_output_error(1001,'必传参数缺失');
        }
        $app_type = $this->request->param('app_type');
        $servicePropertyManagement = new PropertyManagementService();
        $where[] = ['r.pigcms_id','=',$pigcmsId];
        //$field = 'pigcms_id,village_id,uid,bind_id,content,type,time,status,pic,wid,repair_type,repair_time,user_name,cate_id,cate_fid,single_id,floor_id,layer_id,vacancy_id,reply_time,reply_pic,reply_content,msg,comment,comment_pic,comment_time,is_read,score';
        $field = 'r.*,u.name,u.address,u.phone as uphone,u.single_id as u_single_id,u.floor_id as u_floor_id,u.layer_id as u_layer_id,u.vacancy_id as u_vacancy_id';
        try {
            $info = $servicePropertyManagement->getRepairFind($where, $field,$app_type);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$info);
    }
    /**
     * Notes: 操作工单 （接单/拒绝 以及处理意见 工单）
     * @return \json
     * @author: weili
     * @datetime: 2020/10/22 11:18
     */
    public function operationRepair()
    {
        $info = $this->getLoginInfo();
        $pigcmsId = $this->request->param('pigcms_id','','intval');
//        $wid = $this->request->param('wid','','intval');
        $wid = $this->_uid;
        $message = $this->request->param('message','','trim');
        $status = $this->request->param('status','','intval');//status=2 接受  1拒绝
        $type = $this->request->param('type',0,'intval');//type=1 处理意见及上传图片 0接单/拒绝
        $app_type = $this->request->param('app_type');
        $reply_content = $this->request->param('reply_content','','trim');
        $reply_pic = $this->request->param('reply_pic');
        if(!is_array($reply_pic) && !empty($reply_pic)){
            $reply_pic = explode(';',$reply_pic);
        }
        if($type){//处理意见
//            $reply_pic = [
//                '61/000/001/131/20151029153949.jpg','61/000/001/131/20151029153949.jpg','61/000/001/131/20151029153949.jpg'
//            ];
            if(!$pigcmsId || !$wid || !$reply_content ){
                return api_output_error(1001, '必传参数缺失');
            }
            if($app_type == 'packapp' || (is_array($reply_pic) && !empty($reply_pic))) {
                if ($reply_pic || count($reply_pic) > 0) {
                    $reply_pic = implode('|', $reply_pic);
                }
            }else{
                if (is_array($reply_pic) && !empty($reply_pic)) {
                    $reply_pic = implode('|', $reply_pic);
                }
            }
            if($reply_pic){
                $data = [
                    'pigcms_id'=>$pigcmsId,
                    'wid'=>$wid,
                    'reply_content'=>$reply_content,
                    'reply_pic'=>$reply_pic,
                ];
            }else{
                $data = [
                    'pigcms_id'=>$pigcmsId,
                    'wid'=>$wid,
                    'reply_content'=>$reply_content,
                    'reply_pic'=>'',
                ];
            }
        }else {//接单/拒接
            if(!in_array($status,[1,2])){
                return api_output_error(1001, '必传参数缺失');
            }
            if (empty($pigcmsId) || !$wid || !$message) {
                return api_output_error(1001, '必传参数缺失');
            }
            $data = [
                'pigcms_id'=>$pigcmsId,
                'wid'=>$wid,
                'message'=>$message,
                'status'=>$status,
                'reply_pic'=>'',
            ];
        }
        $field = 'r.pigcms_id,r.village_id,r.uid,r.bind_id,r.content,r.type,r.time,r.status,r.pic,r.wid,r.repair_type';
        $servicePropertyManagement = new PropertyManagementService();
        try {
            $res = $servicePropertyManagement->operationRepair($data,$field,$type);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($res){
            if($app_type == 'packapp' || $app_type == 'wxapp'){
                return api_output(0,$res,'操作成功');
            }else{
                return api_output(0,['info'=>true],'操作成功');
            }
        }else{
            return api_output(1003,[],'操作失败！');
        }
    }

    /**
     * Notes:更近内容
     * @return \json
     * @author: weili
     * @datetime: 2020/10/22 11:16
     */
    public function disposeRepair()
    {
        $info = $this->getLoginInfo();
        $pigcms_id = $this->request->param('pigcms_id','','intval');
        $wid = $this->_uid;
        $content = $this->request->param('content','','trim');
        $village_id = $this->request->param('village_id','','intval');
        if(!$pigcms_id || !$wid || !$content || !$village_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $data = [
            'pigcms_id'=>$pigcms_id,
            'wid'=>$wid,
            'content'=>$content,
            'village_id'=>$village_id,
        ];
        $servicePropertyManagement = new PropertyManagementService();
        try{
            $res = $servicePropertyManagement->disposeRepair($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($res){
            return api_output(0,$res,'操作成功');
        }else{
            return api_output(1003,[],'操作失败！');
        }
    }

    /**
     * Notes: 工单处理意见上传图片
     * @return \json
     * @author: weili
     * @datetime: 2020/10/22 11:17
     */
    public function upload(){
        $file = $this->request->file('img');
        $imgFile = $this->request->file('imgFile');
        $village_id = $this->request->param('village_id','','intval');
//        if(!$village_id){
//            return api_output_error(1001,'必传参数缺失');
//        }
        if(!$file && !$imgFile){
            return api_output_error(1001,'必传参数缺失');
        }
        if(!$file){
            $file = $imgFile;
        }
        $servicePropertyManagement = new PropertyManagementService();
        try {
            $data = $servicePropertyManagement->uploads($file,$village_id);
            return api_output(0, $data, "成功");
        }catch (\think\exception\ValidateException $e){
            return api_output_error(-1, $e->getMessage());
        }
    }
}