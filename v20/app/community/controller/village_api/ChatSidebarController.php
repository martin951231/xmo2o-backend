<?php
/**
 *======================================================
 * Created by : PhpStorm
 * User: zhanghan
 * Date: 2021/10/26
 * Time: 11:30
 *======================================================
 */

namespace app\community\controller\village_api;

use app\community\controller\CommunityBaseController;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\service\ChatSidebarService;
use app\community\model\service\HouseNewRepairService;
use app\community\model\service\HouseUserTrajectoryLogService;
use app\community\model\service\HouseVillageLabelService;
use app\community\model\service\HouseVillagePayOrderService;
use app\community\model\service\HouseVillageUserLabelBindService;
use app\community\model\service\NewBuildingService;
use app\community\model\service\SessionFileService;
use app\traits\CommonLogTraits;
use app\traits\house\AdminLoginTraits;
use file_handle\FileHandle;
use think\App;
use think\Exception;

class ChatSidebarController extends CommunityBaseController
{
    use CommonLogTraits;
    use AdminLoginTraits;
    /**
     * 小区用户标签列表
     * @return \json
     */
    public function getHouseVillageLabel(){
        $page = $this->request->post('page');
        $page = max(1,$page);
        $village_id = $this->adminUser['village_id'];
        $limit = 15;
        try {
            // 获取标签列表
            $house_village_label = new HouseVillageLabelService();
            $data = $house_village_label->getHouseVillageLabel($village_id,'id,label_type,label_name,status,create_at',$page,$limit);
        }catch (\Exception $e){
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 编辑标签
     * @return \json
     */
    public function changeUserLabel(){
        $id = $this->request->post('id');
        $type = $this->request->post('type');
        $label_type = $this->request->post('label_type');
        $label_name = $this->request->post('label_name');
        $status = $this->request->post('status');
        $village_id = $this->adminUser['village_id'];
        if($type != 'add' && empty($id)){
            return api_output_error(1003, '缺少关键参数');
        }
        $where = [];
        $where[] = ['id','=',$id];

        $data = [];
        if($type == 'del'){
            // 删除
            $data['is_delete'] = 1;
            $data['update_at'] = time();
        }elseif($type == 'update'){
            // 编辑
            if(!empty($label_type)){
                $data['label_type'] = $label_type;
            }
            if(!empty($label_name)){
                $data['label_name'] = $label_name;
            }
            $data['status'] = $status;
            $data['update_at'] = time();
        }else{
            // 新增
            if(!empty($label_type)){
                $data['label_type'] = $label_type;
            }
            if(!empty($label_name)){
                $data['label_name'] = $label_name;
            }
            $data['status'] = $status;
            $data['village_id'] = $village_id;
            $data['create_at'] = time();
            $data['update_at'] = time();
        }
        try {
            // 获取标签列表
            $house_village_label = new HouseVillageLabelService();
            $data = $house_village_label->changeHouseVillageLabel($type,$where,$data);
        }catch (\Exception $e){
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 获取标签类型
     * @return \json
     */
    public function getLabelType(){
        $data = ['政治面貌','特殊人群','重点人群','关怀对象'];
        return api_output(0,$data);
    }

    /**
     * 获取标签详情
     * @return \json
     */
    public function getUserLabelInfo(){
        $id = $this->request->post('id');
        if(empty($id)){
            return api_output_error(1003, '缺少关键参数');
        }
        $where = [];
        $where[] = ['id','=',$id];
        $where[] = ['is_delete','=',0];
        try {
            // 获取标签信息
            $house_village_label = new HouseVillageLabelService();
            $data = $house_village_label->getHouseVillageLabelInfo($where,'id,status,label_type,label_name');
        }catch (\Exception $e){
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 获取业主相关企微信息
     * @return array|\json
     */
    public function getCommunityUserInfo(){
        $bind_id = $this->request->post('pingcms_id');
        $room_id = $this->request->post('room_id');
        if(empty($bind_id)&&empty($room_id)){
            return api_output_error(1003, '缺少关键参数');
        }
        try {
            $db_chat_sidebar = new ChatSidebarService();
            if (!empty($bind_id)){
                $where = [];
                $where[] = ['pigcms_id','=',$bind_id];
                $field = 'vub.address,v.village_name,u.nickname';
                $area_street_data = $db_chat_sidebar->getUserQWInfo($where,$field);
                if (!empty($room_id)){
                    $area_street_data['tab_list'] = [
                        'payableOrderList' => '缴费记录',
                        'trackInformation' => '轨迹信息',
                        'ownerInformation' => '业主信息',
                        'auditInformation' => '家属/租客',
                        'workOrder' => '业主工单',
                        'materialManagement' => '在线文件管理',
                        'expressManagement' => '快递管理',
                        'receivableOrderList' => '应收明细'
                    ];
                }
            }else{
                $area_street_data['tab_list'] = [
                    'payableOrderList' => '缴费记录',
                    'receivableOrderList' => '应收明细'
                ];
            }

        }catch (\Exception $e){
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0,$area_street_data);
    }

    /**
     * 缴费记录
     * @return \json
     */
    public function getLivingPaymentLog(){
        $village_id = $this->request->post('village_id',0);
        $uid = $this->request->post('uid',0);
        $page = $this->request->post('page',1,'int');
        $db_house_village_pay_order = new HouseVillagePayOrderService();
        try {
            $data = $db_house_village_pay_order->getLivingPaymentLog($uid,$village_id,$page,5);
        }catch (\Exception $e){
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 获取用户行为轨迹
     * @author: liukezhu
     * @date : 2021/11/12
     * @return \json
     */
    public function getActionTrail(){
        $page = $this->request->post('page',1,'int');
        $village_id = $this->adminUser['village_id'];
        $pigcms_id = $this->request->post('pigcms_id',0,'int');
        if (!$village_id)
            return api_output_error(1001, '缺少必要参数');
        if(!$pigcms_id){
            return api_output(0,['list'=>[],'count'=>0]);
        }
        try {
            $where[] = ['business_type', 'in', ["village"]];
            $where[] = ['business_id', '=', $village_id];
            $where[] = ['role_id', '=', $pigcms_id];
            $field='create_at,content';
            $order='id DESC';
            $limit = 10;
            $data = (new HouseUserTrajectoryLogService())->getUserLog($where,1,$field,$order,$page,$limit);
        }catch (\Exception $exception){
            return api_output_error(1003, $exception->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 获取用户信息
     * @return \json
     */
    public function getUserInfo(){
        $village_id = $this->adminUser['village_id'];
        $bind_id = $this->request->post('pigcms_id');
        try {
            $db_chat_sidebar = new ChatSidebarService();
            $data = $db_chat_sidebar->getUserInfo($village_id,$bind_id,'bind_number,village_id,name,phone,id_card,ic_card,address,housesize,memo,authentication_field,single_id,layer_id,floor_id,vacancy_id');
        }catch (\Exception $e){
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 编辑业主信息
     * @return \json
     */
    public function editUserInfo(){
        $pigcms_id = $this->request->post('pigcms_id');
        $village_id = $this->adminUser['village_id'];
        $post = $this->request->post('post'); // 业主基本信息
        $authentication_field = $this->request->post('authentication_field');   // 业主资料
        $lebel_ids = $this->request->post('lebel_ids',''); // 标签
        $user_label_arr = $this->request->post('user_label_arr',''); // 新标签
        $mark_list_arr = $this->request->post('mark_list_arr',''); // 政治面貌数据
        if(empty($pigcms_id)){
            return api_output_error(1003, '缺少关键参数！');
        }
        $newData=array('pigcms_id'=>$pigcms_id,'post'=>$post,'authentication_field'=>$authentication_field,'lebel_ids'=>$lebel_ids);
        $newData['user_label_arr']=$user_label_arr;
        $newData['mark_list_arr']=$mark_list_arr;
        
        $db_house_village = new HouseVillage();
        $where_village = [];
        $where_village[] = ['village_id','=',$village_id];
        // 判断身份证号码是否必填
        $set_id_code = $db_house_village->getInfo($where_village,'set_id_code');
        if($set_id_code['set_id_code'] == 1){
            if(!$post['id_card']){
                return api_output_error(1003, '身份证号码不能为空！');
            }
            if(is_idcard($post['id_card']) == false){
                return api_output_error(1003, '请填写正确的身份证号码');
            }
        }

        // 处理系列化数据
        foreach($authentication_field as $k => &$v){
            if(is_array($v) && $v['is_must'] == 1 && empty($v['value'])){
                return api_output_error(1003, $v['title'].'为必填项！');
            }
            if($v['key'] == 'native_place'){
                $v['value'] = $v['province_idss'].'#'.$v['city_idss'];
                unset($authentication_field[$k]['province_name']);
                unset($authentication_field[$k]['city_name']);
                unset($authentication_field[$k]['province_idss']);
                unset($authentication_field[$k]['city_idss']);
            }
        }
        $post['authentication_field'] = serialize($authentication_field);

        $condition = [];
        $condition[] = ['pigcms_id','=',$pigcms_id];
        $condition[] = ['village_id','=',$village_id];
        $db_house_village_user_bind = new HouseVillageUserBind();
        // 业主信息
        $user_info = $db_house_village_user_bind->getOne($condition);
        // 手机号不能与当前房屋中待审核和审核通过的手机号一样
        if ($post['phone']) {
            $bind_condition = [];
            $bind_condition[] = ['pigcms_id','<>', $pigcms_id];
            $bind_condition[] = ['vacancy_id','=',$user_info['vacancy_id']];
            $bind_condition[] = ['phone','=',$post['phone']];
            $bind_condition[] = ['type','in', [0,1,2,3]];
            $bind_condition[] = ['status','in' , [1,2]];
            $other_bind_info = $db_house_village_user_bind->getOne($bind_condition);
            if($other_bind_info){
                return api_output_error(1003, '该手机号已绑定或已申请绑定此房间');
            }
        }
        // 姓名不能与当前房屋中待审核和审核通过的姓名一样
        if ($post['name']) {
            $bind_condition = [];
            $bind_condition[] = ['pigcms_id','<>', $user_info['pigcms_id']];
            $bind_condition[] = ['vacancy_id','=',$user_info['vacancy_id']];
            $bind_condition[] = ['name','=',$post['name']];
            $bind_condition[] = ['type','in', [0,1,2,3]];
            $bind_condition[] = ['status','in' , [1,2]];
            $other_bind_info = $db_house_village_user_bind->getOne($bind_condition);
            if($other_bind_info){
                return api_output_error(1003, "{$post['name']}已绑定或已申请绑定此房间");
            }
        }

        // 检查下是否当前ic卡已经被其他人使用 状态处于正常的
        if (trim($post['ic_card'])) {
            $where_repeat = [];
            $where_repeat[] = ['ic_card','=',trim($post['ic_card'])];
            $where_repeat[] = ['status','=',1];
            $where_repeat[] = ['pigcms_id','<>',$user_info['pigcms_id']];
            $repeat = $db_house_village_user_bind->getOne($where_repeat,'pigcms_id,village_id,name');
            if (!empty($repeat)) {
                if ($repeat['village_id'] == $village_id) {
                    // 同一个小区提示具体重复人员
                    return api_output_error(1003, "当前用户【".$repeat['name']."】已经使用了该卡号");
                } else {
                    $village_name = M('House_village')->where($where_village)->getField('village_name');
                    // 不同小区提示小区
                    return api_output_error(1003, "当前【".$village_name."】小区用户已经使用了该卡号");
                }
            }
        }

        $update_vacancy = true;
        if ($user_info['uid']==0 &&  $user_info['phone']=='') { // 虚拟业主
            if (!$post['phone']) { // 没有修改手机号，不更新房屋状态；修改则更新
                $update_vacancy = false;
            }
        }

        // 房间信息
        $vacancy_where = [];
        $vacancy_where[] = ['status','in' , '1,2,3'];
        $vacancy_where[] = ['pigcms_id','=',$user_info['vacancy_id']];
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        $vacancy_info = $db_house_village_user_vacancy->getOne($vacancy_where);
        if (!isset($vacancy_info)) {
            return api_output_error(1003, '该房间信息不存在！');
        }
        $post['message_time'] = time();
        $result = $db_house_village_user_bind->saveOne($condition,$post);
        if ($result) {
            $houseUserTrajectoryLogService=new HouseUserTrajectoryLogService();
            $houseUserTrajectoryLogService->addLog(4,$village_id,0,$user_info['uid'],$user_info['pigcms_id'],2,'更新了人员数据','后台更新了人员数据');
            if ($update_vacancy) {
                //修改房间表 信息
                $condition_vacancy = [];
                $condition_vacancy[] = ['usernum','=',$vacancy_info['usernum']];
                $condition_vacancy[] = ['village_id','=',$village_id];

                $data_vacancy['status'] = 3;
                $data_vacancy['uid'] = $user_info['uid'];
                $data_vacancy['name'] = $post['name'];
                $data_vacancy['phone'] = $post['phone'];
                $data_vacancy['memo'] = $post['memo'];
                // $data_vacancy['housesize'] = $_POST['housesize'];
                $data_vacancy['park_flag'] = $user_info['park_flag'];
                $data_vacancy['type'] = 0;
                $db_house_village_user_vacancy->saveOne($condition_vacancy,$data_vacancy);
            }
            if($lebel_ids!==''){
                // 保存标签
                $house_village_label = new HouseVillageUserLabelBindService();
                $house_village_label->addUserLabelBind(implode(',',$lebel_ids),$pigcms_id,$village_id);
            }

            if($user_label_arr!==''){
                $param=array(
                    'village_id'=>$village_id,
                    'pigcms_id'=>$pigcms_id,
                    'user_label_groups'=>$user_label_arr
                );
                $list = (new NewBuildingService())->subBindUserLabel($param);
            }
            if($mark_list_arr!=='') {
                $param = array(
                    'village_id' => $village_id,
                    'pigcms_id' => $pigcms_id,
                    'user_political_affiliation' => $mark_list_arr['user_political_affiliation'],
                    'user_party_id' => $mark_list_arr['user_party_id'],
                    'user_special_groups' => $mark_list_arr['user_special_groups'],
                    'user_focus_groups' => $mark_list_arr['user_focus_groups'],
                    'user_vulnerable_groups' =>  $mark_list_arr['user_vulnerable_groups'],
                );
                $list = (new NewBuildingService())->subStreetPartyBindUser($param);
            }
            $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
            $queuData = [
                'logData' => [
                    'tbname' => '人员管理-业主列表',
                    'table'  => 'house_village_user_bind',
                    'client' => '小区后台',
                    'trigger_path' => '业主列表->业主详情',
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
            return api_output(0,$post,'修改成功');
        }else{
            return api_output_error(1003,'修改异常');
        }
    }

    /**
     * 获取省市
     * @return \json
     */
    public function getProvinceCity(){
        $type = $this->request->post('type');
        $id = $this->request->post('id');
        if($type == 'province'){
            $condition_area['area_type'] = 1;
            $condition_area['is_open'] = 1;
        }else{
            $condition_area['area_pid'] = intval($id);
            $condition_area['is_open'] = 1;
        }
        try {
            $db_area = new ChatSidebarService();
            $data = $db_area->getProvinceCity($condition_area,'`area_id` `id`,`area_name` `name`');
        }catch (\Exception $e){
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0,$data);

    }

    /**
     * 业主工单列表
     * @return \json
     */
    public function getWorkOrderList(){
        $pigcms_id = $this->request->post('pigcms_id'); // 业主绑定小区ID
        $page = $this->request->post('page',0,'int');
        if(empty($pigcms_id)){
            return api_output_error(1003, '缺少关键参数');
        }
        $where = [];
        $where[] = ['bind_id','=',$pigcms_id];
        $where[] = ['order_type','=',0];
        $limit = 10;
        $field = 'o.*';
        $order='o.order_id DESC';
        $service_house_new_repair = new HouseNewRepairService();
        try{
            $data = $service_house_new_repair->getOrderList($where,$field,$page,$limit,$order);
            $count = $service_house_new_repair->getOrderCount($where);
            $res['list'] = $data;
            $res['total'] = $count;
            $res['total_limit'] = $limit;
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * 工单详情
     * @return \json
     */
    public function getWorkOrderInfo(){
        $order_id = $this->request->post('order_id'); // 工单ID
        if(empty($order_id)){
            return api_output_error(1003, '缺少关键参数');
        }
        $field = '*';
        try {
            $db_chat_sidebar = new ChatSidebarService();
            $data = $db_chat_sidebar->getRepairInfo($order_id,$field);
        }catch (\Exception $e){
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 装修申请单
     * @author: liukezhu
     * @date : 2021/11/12
     * @return \json
     */
    public function getDecorationOrderList(){
        $page = $this->request->post('page',1,'int');
        $uid = $this->request->post('uid',1,'int');
        $limit = 10;
        if (!$uid)
            return api_output(0,[]);
        try {
            $where[] = ['uid', '=', $uid];
            $where[] = ['diy_tatus', 'in', '0,1,3'];
            $village_id = $this->adminUser['village_id'];
            if($village_id>0){
                $where[] = ['from', '=', 'house'];
                $where[] = ['from_id', '=', $village_id];
            }
            $field='id,title,add_time,diy_tatus,from_id';
            $order='id DESC';
            $data = (new ChatSidebarService())->getMaterialList($where,$field,$order,$page,$limit);
        }catch (\Exception $exception){
            return api_output_error(1003, $exception->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 获取模板填写附件列表
     * @return \json
     */
    public function getWriteFileList() {
        $page = $this->request->post('page',1,'int');
        $page_size = 10;
        $diy_id = $this->request->post('diy_id','','intval');
        if(empty($diy_id)){
            return api_output_error(1003, '缺少参数');
        }
        try {
            $chat_sidebar = new ChatSidebarService();
            $data = $chat_sidebar->getWriteFileList($diy_id, $page, $page_size);
        }catch (\Exception $e){
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 删除装饰申请单附件
     * @return \json
     */
    public function delWriteFileList(){
        $file_id = $this->request->post('file_id','','int');
        if(empty($file_id)){
            return api_output_error(1003, '缺少关键参数');
        }
        try {
            $chat_sidebar = new ChatSidebarService();
            $data = $chat_sidebar->delWriteFileList($file_id);
        }catch (\Exception $e){
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * Notes: 上传图片
     * @return \json
     */
    public function uploadFile()
    {
        $file = $this->request->file('file');
        $path = $this->request->post('path');
        if(!$file){
            return api_output_error(1001,'请上传图片');
        }
        try {
            validate(['imgFile' => [
                'fileSize' => 1024 * 1024 * 10,
                'fileExt' => 'jpg,png,jpeg',
            ]])->check(['imgFile' => $file]);

            $imgName = $file->getOriginalName();
            $savename = \think\facade\Filesystem::disk('public_upload')->putFile( $path,$file);
            if(strpos($savename,"\\") !== false){
                $savename = str_replace('\\','/',$savename);
            }
            $data['url'] = '/upload/'.$savename;
            $params = ['savepath' => $data['url']];
            $file_handle = new FileHandle();
            if($file_handle->check_open_oss()) {
                $result = invoke_cms_model('Image/oss_upload_image',$params);
            }
            $data['result'] = $result['retval'];
            $data['name'] = $imgName;
            $data['path'] = replace_file_domain($data['url']);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data, "成功");
    }

    /**
     * 添加附件
     * @return \json
     */
    public function addWriteFile(){
        $diy_id = $this->request->post('diy_id');
        $file_remark = $this->request->post('file_remark');
        $file_type = $this->request->post('file_type');
        $file_url = $this->request->post('file_url');
        if(empty($diy_id) || empty($file_remark) || empty($file_type) || empty($file_url)){
            return api_output_error(1003, '缺少关键参数');
        }
        try {
            $param = [
                'diy_id' => $diy_id,
                'ip' => $_SERVER['REMOTE_ADDR'],
                'file_name' => explode('/',$file_url)[count(explode('/',$file_url))-1],
                'file_remark' => $file_remark,
                'file_type' => $file_type,
                'file_suffix' => explode('.',$file_url)[1],
                'file_url' => $file_url,
                'add_time' => time(),
                'from' => "village",
                'from_id' => $this->adminUser['village_id'],
                'from_type' => "小区",
                'from_name' => $this->adminUser['village_name'],
                'account' => $this->adminUser['account'],
            ];
            $chat_sidebar = new ChatSidebarService();
            $data = $chat_sidebar->addWriteFile($param);
        }catch (\Exception $e){
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 装饰申请单备注列表
     * @return \json
     */
    public function getRemarkList(){
        $page = $this->request->post('page',1,'int');
        $page_size = 10;
        $diy_id = $this->request->post('diy_id','','intval');
        if(empty($diy_id)){
            return api_output_error(1003, '缺少参数');
        }
        try {
            $chat_sidebar = new ChatSidebarService();
            $data = $chat_sidebar->getRemarkList($diy_id, $page, $page_size);
            if(isset($data['msg'])){
                return api_output_error(1003, $data['msg']);
            }
        }catch (\Exception $e){
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 编辑及新增
     * @return \json
     */
    public function addRemark(){
        $diy_id = $this->request->post('diy_id','0','intval');
        $remark_value = $this->request->post('remark_value');
        $remark_id = $this->request->post('remark_id');
        $type = $this->request->post('type');
        $account = $this->adminUser['account'];
        if($type == 'add'){
            if(empty($diy_id)){
                return api_output_error(1003, '缺少参数');
            }elseif (empty($remark_value)){
                return api_output_error(1003, '请填写备注内容！');
            }

        }else if(empty($remark_id)){
            return api_output_error(1003, '缺少参数');
        }
        $param = [];
        $param['diy_id'] = $diy_id;
        $param['remark_value'] = $remark_value;
        $param['account'] = $account;
        $param['remark_id'] = $remark_id;
        try {
            $chat_sidebar = new ChatSidebarService();
            $data = $chat_sidebar->addRemark($param, $type);
            if(isset($data['msg'])){
                return api_output_error(1003, $data['msg']);
            }
        }catch (\Exception $e){
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 删除备注
     * @return \json
     * @throws \Exception
     */
    public function delRemark(){
        $remark_id = $this->request->post('remark_id');
        if(empty($remark_id)){
            return api_output_error(1003,'缺少关键参数');
        }
        try {
            $chat_sidebar = new ChatSidebarService();
            $data = $chat_sidebar->delRemark($remark_id);
            if(!$data){
                return api_output_error(1003, '删除失败');
            }
        }catch (Exception $e){
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 获取快递列表
     * @return \json
     */
    public function getExpressList(){
        $type = $this->request->post('type');  // send 代发   collection 代收
        $pigcms_id = $this->request->post('pigcms_id');
        $uid = $this->request->post('uid',0);
        $page = $this->request->post('page',1,'int');
        $village_id = $this->adminUser['village_id'];
        if(empty($pigcms_id) || empty($uid)){
            return api_output(0,[]);
        }
        try {
            $limit = 10;
            $db_chat_sidebar = new ChatSidebarService();
            if($type === 'send'){
                // 快递代发
                $data = $db_chat_sidebar->getExpressSend($uid,$pigcms_id,$page,$limit,$village_id);
            }else{
                // 快递代收
                $data = $db_chat_sidebar->getExpressCollection($uid,$pigcms_id,$page,$limit,$village_id);
            }
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 获取快递详情
     * @return \json
     */
    public function getExpressInfo(){
        $id = $this->request->post('id');
        if(empty($id)){
            return api_output_error(1003,'缺少关键参数');
        }
        try {
            $chat_sidebar = new ChatSidebarService();
            $data = $chat_sidebar->getExpressInfo($id);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 删除快递
     * @return \json
     */
    public function delExpress(){
        $id = $this->request->post('id');
        if(empty($id)){
            return api_output_error(1003,'缺少关键参数');
        }
        try {
            $where['id'] = $id;
            $data = invoke_cms_model('House_village_express/village_express_del',[$where])['retval'];
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 聊天左侧员工或者群列表
     * @return \json
     */
    public function getChatLeftList(){
        $uid = $this->request->post('uid',0,'int');
        $type = $this->request->post('type','');
        $village_id = $this->adminUser['village_id'];
        if(empty($village_id)){
            return api_output_error('1001','未获取到小区相关参数，请重新登录');
        }
        if(empty($uid)){
            return api_output(0,[]);
        }
        if(empty($type)){
            return api_output_error('1001','聊天类型必传');
        }
        try {
            $chat_sidebar = new ChatSidebarService();
            $data = $chat_sidebar->getChatLeftList($uid,$type,$village_id);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 聊天记录
     * @return \json
     */
    public function chatSessionlog(){
        $property_id = $this->adminUser['property_id'];
        $from_id = $this->request->post('from_id'); //发送者id
        $to_id = $this->request->post('to_id'); //接受者 id
        $msg_type = $this->request->post('msg_type');  // 聊天内容类型
        $type = $this->request->post('type'); //0员工 1客户 2员工群 3客户群
        $page = $this->request->post('page',1,'int');
        $search_name = $this->request->post('search_name');
        $start_date = $this->request->post('start_date');
        $end_date = $this->request->post('end_date');
        $chat_id = $this->request->post('chat_id'); // 聊天群ID
        $chat_from_id = $this->request->post('chat_from_id'); // 群聊的发送者ID
        if($type == 0 || $type == 1)
        {
            if (!$from_id || !$to_id) {
                return api_output_error(1001, '请先注册工作人员账号或选择聊天对象');
            }
        }
        if(!in_array($type,[0,1,2,3])){
            return api_output_error(1001,'类型参数错误');
        }
        if(($type == 2 || $type == 3) && !$chat_id){
            return api_output_error(1001,'缺少聊天群参数信息');
        }
        if($start_date) {
            $start_date = strtotime($start_date)*1000;
        }
        if($end_date) {
            $end_date = strtotime($end_date.' 23:59:59')*1000;
        }
        $param['from_id'] = $from_id;
        $param['to_id'] = $to_id;
        $param['msg_type'] = $msg_type;
        $param['property_id'] = $property_id;
        $param['chat_id'] = $chat_id;
        $param['type'] = $type;
        $param['chat_from_id'] = $chat_from_id;
        if($search_name){
            $param['search_name'] = $search_name;
        }
        if($start_date){
            $param['start_date'] = $start_date;
        }
        if($end_date){
            $param['end_date'] = $end_date;
        }
        $limit = 20;
        $serviceSessionFile = new SessionFileService();
        try{
            $list = $serviceSessionFile->getMsgList($param,$page,$limit,'msgtime asc');
            $next_page = false;
            if(count($list) == $limit){
                $next_page = true;
            }
            if ($list && !$list->isEmpty()){
                $list = $list->toArray();
                foreach ($list as $k => $v){
                    if(empty($v['info'])){
                        unset($list[$k]);
                    }
                }
                $list = array_values($list);
            }else{
                $list = [];
            }
            $data['next_page'] = $next_page;
            $data['list'] = array_values($list);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$data);
    }
}