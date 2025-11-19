<?php
/**
 *======================================================
 * Created by : PhpStorm
 * User: zhanghan
 * Date: 2021/11/30
 * Time: 13:57
 *======================================================
 */

namespace app\community\controller\village_api;


use app\community\controller\CommunityBaseController;
use app\community\model\service\CloudIntercomService;
use app\community\model\service\HouseVillageService;

class CloudIntercomController extends CommunityBaseController
{
    /**
     * 获取小区三方对接配置项
     * @return \json
     */
    public function getCloudIntercomConfig(){
        $village_id = $this->adminUser['village_id'];
        if(empty($village_id)){
            $village_id = $this->request->param('village_id');
        }
        if(empty($village_id)){
            return api_output_error(1001,'缺少关键参数');
        }
        $cloud_intercom = new CloudIntercomService();
        try {
            $data = $cloud_intercom->getCloudIntercomConfig($village_id);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 保存小区三方对接配置项
     * @return \json
     */
    public function saveCloudIntercomConfig(){
        $status = $this->request->post('isOpen');   // 是否启用
        $clientId = $this->request->post('clientId');
        $clientSecret = $this->request->post('clientSecret');
        $villageCode = $this->request->post('villageCode'); // 小区编码
        $village_id = $this->adminUser['village_id'];
        if(empty($village_id)){
            $village_id = $this->request->param('village_id');
        }
        if(empty($village_id) || empty($clientId) || empty($clientSecret)){
            return api_output_error(1001,'clientId和clientSecret必填');
        }
        $param = [];
        $param['status'] = $status;
        $param['clientId'] = $clientId;
        $param['clientSecret'] = $clientSecret;
        $param['village_num'] = $villageCode;
        $cloud_intercom = new CloudIntercomService();
        try {
            $data = $cloud_intercom->saveCloudIntercomConfig($village_id,$param);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 接收对讲平台推送信息数据
     * @return \json
     */
    public function receiveUserInfo(){
        header("Content-type: application/json");
        $data = $this->request->post();
        $village_id = intval($_GET['village_id'])>0 ? intval($_GET['village_id']):0;
        fdump_api(['$data' => $data,'$village_id' => $village_id],'dopu/receiveUserInfo',1);
        $cloud_intercom = new CloudIntercomService();
        try {
            $res = $cloud_intercom->receiveUserInfo($village_id,$data);
            return $this->returnCode($res['code'],$res['message']);
        }catch (\Exception $e){
            return $this->returnCode(1,$e->getMessage());
        }
    }

    /**
     * 获取三方人脸信息列表
     * @return \json
     */
    public function getFaceDataList(){
        $keyword = $this->request->post('keyword','');
        $check_status = $this->request->post('check_status',0,'int');
        $device_status = $this->request->post('device_status',0,'int');
        $page = $this->request->post('page',1,'int');
        $village_id = $this->adminUser['village_id'];
        if(empty($village_id)){
            $village_id = $this->request->param('village_id');
        }
        if(empty($village_id)){
            return api_output_error(1001,'缺少关键参数');
        }
        $where = [];
        $where[] = ['village_id','=',$village_id];
        $where[] = ['third_type','=','dopu_cloudintercom'];
        // 姓名
        if(!empty($keyword)){
            $where[] = ['third_xm','like','%'.$keyword.'%'];
        }
        // 审核状态
        if(!empty($check_status)&&intval($check_status)>0){
            $where[] = ['check_status','=',intval($check_status)-1];
        }
        // 下发状态
        if(!empty($device_status)){
            $where[] = ['device_status','=',$device_status-1];
        }
        $limit = 10;
        $field = 'village_id,third_user_id,third_ryid,third_xm,third_xbdm,third_zjhm,third_jzd_dzxz,img_url,check_status,device_status,phone';
        $cloud_intercom = new CloudIntercomService();
        try {
            $data = $cloud_intercom->getFaceDataList($where,$field,$page,$limit);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 获取三方人脸信息详情 并匹配小区已有业主信息
     * @return \json
     */
    public function getThirdUserInfo(){
        $third_user_id = $this->request->post('third_user_id',0,'int'); // 三方用户记录自增id
        if(empty($third_user_id)){
            return api_output_error(1001,'缺少关键参数');
        }
        $where = [];
        $where[] = ['third_user_id','=',$third_user_id];
        $field = 'third_user_id,village_id,third_ryid,third_xm,third_xbdm,third_zjhm,third_jzd_dzxz,img_url,check_status,device_status,from,address,check_reason,phone';
        $cloud_intercom = new CloudIntercomService();
        try {
            $data = $cloud_intercom->getFaceDataInfo($where,$field);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 审核三方人脸信息并反馈到平台
     * @return \json
     */
    public function editThirdUserInfo(){
        $third_user_id = $this->request->post('third_user_id',0,'int'); // 三方用户记录自增id
        $check_status = $this->request->post('check_status',1,'int'); // 审核状态  1是通过  2是拒绝
        $check_reason = $this->request->post('check_reason',''); // 拒绝理由
        $singleName = $this->request->post('singleName'); // 楼栋
        $floorName = $this->request->post('floorName'); // 单元
        $layerName = $this->request->post('layerName'); // 楼层
        $roomName = $this->request->post('roomName'); // 房间号
        $type = $this->request->post('type',0,'int'); // 类型  0房主  1，家人  2租客 4工作人员
        $relatives_type = $this->request->post('relatives_type',0,'int'); // type类型为1时生效
        $memo = $this->request->post('memo',''); // 备注
        $isLockAddress = $this->request->post('isLockAddress',''); // 是否锁死住房地址
        $isMatching = $this->request->post('isMatching',''); // 匹配到用户
        $matchingUid = $this->request->post('matching_uid',0); // 匹配到的用户ID
        $village_id = $this->adminUser['village_id'];
        if(empty($village_id)){
            $village_id = $this->request->param('village_id');
        }
        if(empty($third_user_id)){
            return api_output_error('1001','缺少关键参数');
        }
        if($check_status == 2 && empty($check_reason)){
            return api_output_error('1001','请填写拒绝理由');
        }
        if($check_status == 1 && !$isLockAddress && (empty($singleName) || empty($floorName) || empty($layerName) || empty($roomName))){
            return api_output_error('1001','请选择房间');
        }
        $param = [];
        $param['village_id'] = $village_id;
        $param['third_user_id'] = $third_user_id;
        $param['check_status'] = $check_status;
        $param['check_reason'] = $check_reason;
        $param['singleName'] = $singleName;
        $param['floorName'] = $floorName;
        $param['layerName'] = $layerName;
        $param['roomName'] = $roomName;
        $param['type'] = $type;
        $param['relatives_type'] = $relatives_type;
        $param['memo'] = $memo;
        $param['isLockAddress'] = $isLockAddress;
        $param['isMatching'] = $isMatching;
        $param['matchingUid'] = $matchingUid;
        $cloud_intercom = new CloudIntercomService();
        try {
            $data = $cloud_intercom->editThirdUserInfo($param);
            if($data['code'] > 0){
                return api_output_error(1003,$data['msg']);
            }
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 接收平台下发设备信息
     * @return \json
     */
    public function receiveDeviceInfo(){
        header("Content-type: application/json");
        $param = $this->request->post();
        $village_id = intval($_GET['village_id'])>0 ? intval($_GET['village_id']):0;
        fdump_api(['$param' => $param,'$village_id' => $village_id],'dopu/receiveDeviceInfo',1);
        if(empty($village_id) || empty($param)){
            return $this->returnCode(1,'参数不全');
        }
        $cloud_intercom = new CloudIntercomService();
        try {
            $data = $cloud_intercom->receiveDeviceInfo($village_id,$param);
            return $this->returnCode($data['code'],$data['message']);
        }catch (\Exception $e){
            return $this->returnCode(1,$e->getMessage());
        }
    }

    /**
     * 获取设备列表
     * @return \json
     */
    public function getDeviceDataList(){
        $village_id = $this->adminUser['village_id'];
        $keyword = $this->request->post('keyword','');
        $deviceStatus = $this->request->post('deviceStatus',0);
        $page = $this->request->post('page',0,'int');
        if(empty($village_id)){
            $village_id = $this->request->param('village_id');
        }
        if(empty($village_id)){
            return api_output_error(1001,'缺少关键参数');
        }
        $limit = 10;
        $order = 'd.device_id DESC';
        $field = 'd.device_sn,d.device_name,d.device_direction,d.add_time,d.device_status,d.floor_id,d.layer_id,d.public_area_id,d.notify_time,d.thirdDeviceTypeStr,d.village_id,v.village_name';
        $where = [];
        $where[] = ['d.village_id','=',$village_id];
        $where[] = ['d.device_type','=',29];
        $where[] = ['d.is_del','=',0];
        if(!empty($keyword)){
            $where[] = ['d.device_sn','like','%'.$keyword.'%'];
        }
        if($deviceStatus > 0){
            $where[] = ['d.device_status','=',$deviceStatus];
        }
        $cloud_intercom = new CloudIntercomService();
        try {
            $data = $cloud_intercom->getDeviceDataList($where,$page,$limit,$order,$field);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 接收平台下发设备心跳
     * @return \json
     */
    public function receiveDeviceHeartbeat(){
        header("Content-type: application/json");
        $param = $this->request->post();
        $village_id = intval($_GET['village_id'])>0 ? intval($_GET['village_id']):0;
        fdump_api(['$param' => $param,'$village_id' => $village_id],'dopu/receiveDeviceHeartbeat',1);
        if(empty($village_id) || empty($param)){
            return $this->returnCode(1,'参数不全');
        }
        $cloud_intercom = new CloudIntercomService();
        try {
            $data = $cloud_intercom->receiveDeviceHeartbeat($village_id,$param);
            return $this->returnCode($data['code'],$data['message']);
        }catch (\Exception $e){
            return $this->returnCode(1,$e->getMessage());
        }
    }

    /**
     * 获取公共区域和楼栋列表
     * @return \json
     */
    public function getVillageSinglePublic(){
        $village_id = $this->adminUser['village_id'];
        $service_house_village = new HouseVillageService();
        if(empty($village_id)){
            $village_id = $this->request->param('village_id');
        }
        if(empty($village_id)){
            return api_output_error(1001,'缺少关键参数');
        }
        $list = [];
        $where_public = [];
        $where_public[] = ['village_id', '=', $village_id];
        $where_public[] = ['status', '=', 1];
        $public_area = $service_house_village->getHouseVillagePublicAreaList($where_public,'public_area_id as single_id,public_area_name as single_name');
        foreach($public_area as &$val) {
            $val['is_public'] = 1;
            $val['single_id'] = '1-'.$val['single_id'];
            $list[] = $val;
        }
        $where = [];
        $where[] = ['village_id', '=', $village_id];
        $where[] = ['status', '=', 1];
        $list_single = $service_house_village->getSingleList($where,'id as single_id,single_name','sort desc, id asc');
        foreach($list_single as &$value) {
            $value['is_public'] = 0;
            $value['single_id'] = '0-'.$value['single_id'];
            $list[] = $value;
        }
        $out = [];
        $out['list'] = $list;
        return api_output(0,$out);
    }

    /**
     * 保存设备信息
     * @return \json
     */
    public function editDeviceInfo(){
        $deviceSn = $this->request->post('deviceSn');
        $thirdDeviceTypeStr = $this->request->post('thirdDeviceTypeStr',1);
        $device_name = $this->request->post('device_name');
        $device_direction = $this->request->post('device_direction',0);
        $single_id = $this->request->post('single_id');
        $floor_id = $this->request->post('floor_id');
        if(empty($device_name) || empty($single_id)){
            return api_output_error(1001,'缺少关键参数');
        }
        $singleArr = explode('-',$single_id);
        if($singleArr[0] == 0 && empty($floor_id)){
            return api_output_error(1001,'缺少楼栋单元参数');
        }
        $param = [];
        $param['deviceSn'] = $deviceSn;
        $param['thirdDeviceTypeStr'] = $thirdDeviceTypeStr;
        $param['device_name'] = $device_name;
        $param['device_direction'] = $device_direction;
        $param['single_id'] = $single_id;
        $param['floor_id'] = $floor_id;
        $cloud_intercom = new CloudIntercomService();
        try {
            $data = $cloud_intercom->editDeviceInfo($param);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 下发人员到设备
     * @return \json
     */
    public function sendThirdUserToDevice(){
        $deviceSn = $this->request->post('device_sn',0);
        $thirdUser = $this->request->post('third_user'); // 需要被操作的人员数组
        $operation = $this->request->post('operation','add'); // add 下发操作 del 移除操作
        $village_id = $this->adminUser['village_id'];
        if(empty($thirdUser)){
            return api_output_error(1001,'请选择下发人员');
        }
        if(empty($village_id)){
            $village_id = $this->request->param('village_id');
        }
        if(empty($village_id)){
            return api_output_error(1001,'缺少关键参数');
        }
        $cloud_intercom = new CloudIntercomService();
        try {
            $data = $cloud_intercom->sendThirdUserToDevice($village_id,$deviceSn,$thirdUser,$operation);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output($data['code'],$data,$data['msg']);
    }

    /**
     * 权限接口信息下发
     * @return \json|\think\response\Json|void
     */
    public function receiveDeviceAuth(){
        header("Content-type: application/json");
        $param = $this->request->post();
        $village_id = intval($_GET['village_id'])>0 ? intval($_GET['village_id']):0;
        fdump_api(['$param' => $param,'$village_id' => $village_id],'dopu/receiveDeviceAuth',1);
        if(empty($village_id) || empty($param)){
            return $this->returnCode(1,'参数不全');
        }
        $cloud_intercom = new CloudIntercomService();
        try {
            $data = $cloud_intercom->receiveDeviceAuth($village_id,$param);
            return $this->returnCode($data['code'],$data['message']);
        }catch (\Exception $e){
            fdump_api(['$param' => $param,'$village_id' => $village_id,'err' => $e->getMessage()],'dopu/errReceiveDeviceAuthLog',1);
            return $this->returnCode(1,$e->getMessage());
        }
    }

    /**
     * 接收流水数据下发
     * @return \json|\think\response\Json|void
     */
    public function receiveFlowData(){
        header("Content-type: application/json");
        $param = $this->request->post();
        $village_id = intval($_GET['village_id'])>0 ? intval($_GET['village_id']):0;
        $dataParam = $param;
        if (isset($dataParam['face'])) {
            unset($dataParam['face']);
        }
        fdump_api(['$param' => $dataParam,'$village_id' => $village_id],'dopu/receiveFlowData',1);
        if(empty($village_id) || empty($param)){
            return $this->returnCode(1,'参数不全');
        }
        $cloud_intercom = new CloudIntercomService();
        try {
            $data = $cloud_intercom->receiveFlowData($village_id,$param);
            return $this->returnCode($data['code'],$data['message']);
        }catch (\Exception $e){
            return $this->returnCode(1,$e->getMessage());
        }
    }

    /**
     * 获取非机动车卡号列表
     * @return \json
     */
    public function getNmvCardList(){
        $village_id = $this->adminUser['village_id'];
        $page = $this->request->post('page');
        $keyword = $this->request->post('keyword','','trim');
        $nmvCard = $this->request->post('nmvCard','','trim');
        $surplusDays = $this->request->post('surplusDays');
        $status = $this->request->post('status');
        $cfromtype = $this->request->post('cfromtype','','trim');
        
        if(empty($village_id)){
            $village_id = $this->request->param('village_id');
        }
        if(empty($village_id)){
            return api_output_error(1001,'缺少关键参数');
        }
        $where = [];
        $where[] = ['b.village_id','=',$village_id];
        if($keyword){ // 获取非机动车卡绑定的姓名
            $where[] = ['b.name','like','%'.$keyword.'%'];
        }
        if($nmvCard){ // 非机动车卡
            $where[] = ['c.nmv_card','like','%'.$nmvCard.'%'];
        }
        if($surplusDays != ''){ // 在查询时，查输入的剩余天数及以下天数
            // 转到期时间
            $expiration_time = strtotime(date('Y-m-d 23:59:59')."+ $surplusDays day");
            $where[] = ['c.expiration_time','<=',$expiration_time];
        }
        if($status){ // 1已到期  2未到期 3未缴费
            if($status == 1){
                $where[] = ['c.expiration_time','<',time()];
            }elseif ($status == 3){
                $where[] = ['c.expiration_time','=',0];
            }else{
                $where[] = ['c.expiration_time','>=',time()];
            }
        }
        $limit = 10;
        $field = 'c.id,c.nmv_card,c.expiration_time,b.name,b.pigcms_id,b.uid';
        $order = 'c.id DESC';
        if($cfromtype=='search'){
            $page=0;
            $limit=100;
            //查能正常用的
            $where[] = ['b.status','=',1];
        }
        $cloud_intercom = new CloudIntercomService();
        try {
            $data = $cloud_intercom->getNmvCardList($where,$page,$limit,$field,$order,$cfromtype);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 发送消息模板
     * @return \json
     */
    public function sendNmvMessage(){
        $send = $this->request->post('send');
        if(empty($send)){
            return api_output_error(1001,'缺少关键参数');
        }
        $property_name = $this->adminUser['property_name'];
        $cloud_intercom = new CloudIntercomService();
        try {
            $data = $cloud_intercom->sendNmvMessage($send,$property_name);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 非机动车卡号的缴费记录
     * @return \json
     */
    public function getNmvChargeOrderList(){
        $id = $this->request->post('id');
        $page = $this->request->post('page',0);
        if(empty($id)){
            return api_output_error(1001,'缺少关键参数');
        }
        $fieldOrder = 'rule_id,pay_money,pay_time,pay_type,is_paid,order_id,prepaid_cycle,service_month_num,extra_data';
        $limit = 10;
        $cloud_intercom = new CloudIntercomService();
        try {
            $data = $cloud_intercom->getNmvChargeOrderList($id,$fieldOrder,$page,$limit);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output(0,$data);
    }



    /**
     * 获取非机动车缴费规则列表
     * @return \json
     */
    public function getNmvChargeList(){
        $page = $this->request->post('page',0,'int');
        $keyword = $this->request->post('keyword');
        $type = $this->request->post('type');
        $status = $this->request->post('status');
        $village_id = $this->adminUser['village_id'];
        if(empty($village_id)){
            $village_id = $this->request->param('village_id');
        }
        if (empty($village_id)){
            return api_output_error(1001,'缺少关键参数');
        }
        $where = [];
        $where[] = ['village_id','=',$village_id];
        $where[] = ['is_del','=',0];
        if(!empty($keyword)){
            $where[] = ['nmv_charge_name','like','%'.$keyword.'%'];
        }
        if(!empty($type)){
            $where[] = ['type','=',$type];
        }
        if($status > -1){
            $where[] = ['status','=',$status];
        }

        $limit = 10;
        $field = 'id,nmv_charge_name,type,price,status';

        $cloud_intercom = new CloudIntercomService();
        try {
            $data = $cloud_intercom->getNmvChargeList($where,$page,$limit,$field);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 编辑非机动车信息
     * @return \json
     */
    public function editNmvChargeInfo(){
        $type = $this->request->post('type');
        $id = $this->request->post('id');
        $info = $this->request->post('info');
        $village_id = $this->adminUser['village_id'];
        if(empty($village_id)){
            $village_id = $this->request->param('village_id');
        }
        if (empty($type) || (($type == 'edit' || $type == 'del') && !$id)){
            return api_output_error(1001,'缺少关键参数');
        }

        $cloud_intercom = new CloudIntercomService();
        try {
            $data = $cloud_intercom->editNmvChargeInfo($type,$id,$info,$village_id,$this->adminUser['account']);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output(0,$data,$data['message']);
    }

    /**
     * 平台下发后响应数据格式
     * @param $code
     * @param $msg
     * @return \think\response\Json|void
     */
    public function returnCode($code,$msg){
        $output = [
            'code' => $code,
            'msg' => $msg
        ];
        return json($output);
    }

    /**
     * 获取房间下的非机动车IC卡列表
     * @return \json
     */
    public function getUserCardList(){
        $pigcms_id = $this->request->param('pigcms_id','','intval');
        if(empty($pigcms_id)){
            return api_output(1001,[],'缺少关键参数');
        }

        $page = $this->request->param('page',1,'intval');
        $type = $this->request->param('type','select');
        $limit = 10;
        $field = 'pigcms_id,name,nmv_card,type';

        $where = [];
        $where[] = ['pigcms_id','=',$pigcms_id];
        $where[] = ['status','=',1];
        if($type == 'add'){
            $where[] = ['nmv_card','=',''];
        }else{
            $where[] = ['nmv_card','<>',''];
        }
        $whereOr = [];
        $whereOr[] = ['parent_id','=',$pigcms_id];
        $whereOr[] = ['status','=',1];
        if($type == 'add'){
            $whereOr[] = ['nmv_card','=',''];
        }else{
            $whereOr[] = ['nmv_card','<>',''];
        }
        $cloud_intercom = new CloudIntercomService();
        try {
            $data = $cloud_intercom->getUserCardList([],$page,$limit,[$where,$whereOr],$field);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 保存卡号
     * @return \json
     */
    public function saveUsernmvCard(){
        $pigcms_id = $this->request->param('pigcms_id','','intval');
        if(empty($pigcms_id)){
            return api_output(1001,[],'缺少关键参数');
        }

        $nmv_card = $this->request->param('nmv_card');

        if(!preg_match("/^[a-z\d]*$/i",$nmv_card))
        {
            return api_output_error('1001','非机动车卡号只能是数组和英文');
        }
        if(strlen($nmv_card) > 10){
            return api_output_error('1001','非机动车卡号最多10位');
        }

        $cloud_intercom = new CloudIntercomService();
        try {
            $data = $cloud_intercom->saveUsernmvCard($pigcms_id,$nmv_card);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output($data['code'],[],$data['msg']);
    }

    /**
     * 获取非机动车收费规则详情
     * @return \json
     */
    public function getNMVChargeInfo(){
        $pigcms_id = $this->request->param('pigcms_id',0,'intval');
        if(empty($pigcms_id)){
            return api_output(1001,[],'缺少关键参数');
        }
        $cloud_intercom = new CloudIntercomService();
        try {
            $data = $cloud_intercom->getNMVChargeInfo($pigcms_id);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output($data['code'],$data['data'],$data['msg']);
    }

    /**
     * 非机动车去支付
     * @return \json
     */
    public function nmvGoPay(){
        $pigcms_id = $this->request->param('pigcms_id',0,'intval');
        $rule_id = $this->request->param('rule_id',0,'intval');
        $app_type = $this->request->post('app_type','');
        if(empty($pigcms_id)){
            return api_output(1001,[],'缺少关键参数');
        }
        $cloud_intercom = new CloudIntercomService();
        try {
            $data = $cloud_intercom->nmvGoPay($pigcms_id,$rule_id,$app_type);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output($data['code'],$data['data'],$data['msg']);
    }

    /**
     * 非机动车房间下的缴费记录
     * @return \json
     */
    public function nmvPaymentRecord(){
        $pigcms_id = $this->request->param('pigcms_id',0,'intval');
        $page = $this->request->param('page',1,'intval');
        if(empty($pigcms_id)){
            return api_output(1001,[],'缺少关键参数');
        }
        $cloud_intercom = new CloudIntercomService();
        $fieldOrder = 'order_id,pigcms_id,pay_type,pay_money,pay_time,is_paid,rule_id,position_id';
        $limit = 10;
        try {
            $data = $cloud_intercom->nmvPaymentRecord($pigcms_id,$fieldOrder,$page,$limit);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output($data['code'],$data['data'],$data['msg']);
    }



    /**
     * 远程开门
     * @return \json
     */
    public function openDoor(){
        $code = $this->request->param('code');
        $village_id = $this->request->param('village_id');
        if(empty($code) || empty($village_id)){
            return api_output(1001,[],'缺少关键参数');
        }
        $cloud_intercom = new CloudIntercomService();
        try {
            $data = $cloud_intercom->openDoor($village_id,$code);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output($data['code'],$data,$data['msg']);
    }
    
    public function getNmvChargePayInfo(){
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output_error(1001,'缺少关键参数');
        }
        $cloud_intercom = new CloudIntercomService();
        try {
            $ret = $cloud_intercom->getNmvChargePayInfo($village_id);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output(0,$ret);
    }
    /***
     *PC后台线下支付
    **/
    public function nmvPcOfflinePay(){
        $village_id = $this->adminUser['village_id'];
        $pigcms_id = $this->request->param('pigcms_id',0,'intval');
        $rule_id = $this->request->param('rule_id',0,'intval');
        $card_no = $this->request->post('card_no','','trim');
        $cycle_num = $this->request->post('cycle_num',1,'intval');
        $offline_pay_type = $this->request->post('offline_pay_type',0,'intval');
        $pay_momey = $this->request->post('pay_momey',0,'trim');
        $searched_arr = $this->request->post('searched_arr');
        
        if($pigcms_id<1){
            return api_output(1001,[],'用户数据错误，请重新操作。');
        }
        if(empty($searched_arr) || $pigcms_id!=$searched_arr['pigcms_id']){
            return api_output(1001,[],'用户数据错误，请重新操作！');
        } 
        if(empty($card_no)){
            return api_output(1001,[],'非机动车卡号错误，请重新操作！');
        }
        if($rule_id<1){
            return api_output(1001,[],'请选择一种电动车收费规则！');
        }
        if($offline_pay_type<1){
            return api_output(1001,[],'请选择一种线下支付方式！');
        }
        $cycle_num=$cycle_num>0 ?$cycle_num:1;
        $pdata=array();
        $pdata['pigcms_id']=$pigcms_id;
        $pdata['rule_id']=$rule_id;
        $pdata['card_no']=$card_no;
        $pdata['offline_pay_type']=$offline_pay_type;
        $pdata['cycle_num']=$cycle_num;
        $pdata['village_id']=$village_id;
        $pdata['searched_arr']=$searched_arr;
        $pdata['role_id']=$this->_uid;
        $cloud_intercom = new CloudIntercomService();
        try {
            $data = $cloud_intercom->nmvPcOfflinePay($pdata);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output(0,$data);
    }
}