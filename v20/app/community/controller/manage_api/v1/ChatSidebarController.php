<?php
/**
 *======================================================
 * Created by : PhpStorm
 * User: zhanghan
 * Date: 2021/10/26
 * Time: 11:30
 *======================================================
 */

namespace app\community\controller\manage_api\v1;

use app\community\controller\manage_api\BaseController;
use \app\community\model\service\ChatSidebarService;
use app\community\model\service\EnterpriseWeChatService;
use app\community\model\service\HouseContactWayUserService;
use app\community\model\service\HouseNewRepairService;
use app\community\model\service\HouseUserTrajectoryLogService;
use app\community\model\service\HouseVillageDepositService;
use app\community\model\service\HouseVillageLabelService;
use app\community\model\service\HouseVillageOrderService;
use app\community\model\service\HouseVillagePayOrderService;
use app\community\model\service\HouseVillageUserBindService;
use app\community\model\service\HouseVillageUserLabelBindService;
use app\community\model\service\HouseWorkerService;
use app\community\model\service\SessionFileService;
use app\community\model\service\WorkChatService;
use think\App;

class ChatSidebarController extends BaseController
{
    public $userInfo='';

    public function __construct(App $app)
    {
        parent::__construct($app);
        $notUserId = ['wx_config','getactiontrail','getchatgroupinfo','chatsessionlog'];
        $action = strtolower(request()->action());
        // 外部联系人ID
        $userId = $this->request->post('userId');
        if(!in_array($action,$notUserId) ||(!empty($userId))){
            $db_house_contact_way_user_service = new HouseContactWayUserService();
            $chat_sidebar = new ChatSidebarService();
            $chat_id = $this->request->post('group_chat_id'); // 聊天群ID
            $bind_id = $this->request->post('pigcms_id',0);
            if(empty($userId)){
                if ($chat_id) {
                    $this->returnCode(1003, array(), '目前不支持聊天群使用！', '');
                }
                $this->returnCode(1003, array(), '请允许获相关信息', '');
            }
            $agentid = $this->request->post('agentid');
            $from_type = $this->request->post('from_type');
            $from_id = $this->request->post('from_id');
            $phone = $this->request->post('phone');
            $otherParam = [
                'agentid' => $agentid,
                'from_type' => $from_type,
                'from_id' => $from_id,
                'phone' => $phone,
            ];
            fdump_api([$userId, $otherParam], '$otherParam');
            // 获取用户基本信息
            try {
                $user_info = $db_house_contact_way_user_service->gethouseContactWayUser($userId,'customer_id,uid,phone,property_id,UserID,ExternalUserID', $otherParam);
            }catch (\Exception $e){
                fdump_api(['msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' =>$e->getFile(), 'code' => $e->getCode()], '$eMessage');
                $this->returnCode(1012,['tip' => '为了协助准确定位用户身份请确认对方身份后填写对应手机号码（注意一旦填写并查询到住户身份就会直接进行关联）'],$e->getMessage(),'');
            }
            $userUid = isset($user_info['uid']) && intval($user_info['uid']) > 0 ? intval($user_info['uid']) : 0;
            $userBindId = isset($user_info['bind_id']) && intval($user_info['bind_id']) > 0 ? intval($user_info['bind_id']) : 0;
            if(!$userBindId){
                if ($phone) {
                    $this->returnCode(1012,['tip' => '为了协助准确定位用户身份请确认对方身份后填写对应手机号码（注意一旦填写并查询到住户身份就会直接进行关联）'],'未查询到对应手机号住户信息,请确保填写手机号已在对应物业下有身份','');
                }
                $this->returnCode(1012,['tip' => '为了协助准确定位用户身份请确认对方身份后填写对应手机号码（注意一旦填写并查询到住户身份就会直接进行关联）'],'未查询到外部联系人相关信息,请确保外部联系人已在用户端登录注册','');
            }

            $this->userInfo = $user_info;
            $this->_uid = $userUid;
            // 选择房间后
            if(!empty($bind_id)){
                // 验证 用户与传值pigcms_id 的关系是否正确
                $where = [];
                $where[] = ['pigcms_id','=',$bind_id];
                $data = $chat_sidebar->saveVillageRoomInfo($where);
                if(empty($data)){
                    $this->returnCode(1003, $user_info, '缺少住户身份', '');
                }else{
                    $this->userInfo['bind_id'] = $data['pigcms_id'];
                    $this->userInfo['village_id'] = $data['village_id'];
                }
            }

            // 企微员工ID
            if(isset($user_info['UserID']) && $user_info['UserID']){
                $db_house_worker = new HouseWorkerService();
                $whereWork = [];
                $whereWork[] = ['qy_id', '=', $user_info['UserID']];
                isset($user_info['property_id']) && $user_info['property_id'] && $whereWork[] = ['property_id', '=', $user_info['property_id']];
                $house_worker_data = $db_house_worker->getOneWorker($whereWork,'wid,name');
                if($house_worker_data && isset($house_worker_data['wid'])){
                    $this->userInfo['work_id'] = $house_worker_data['wid'];
                    $this->userInfo['work_name'] = $house_worker_data['name'];
                }
            }
        }
    }

    /**
     * 获取物业下用户所有小区及房间列表
     * @return \json
     */
    public function getVillageList(){
        $uid = $this->_uid;
        $property_id = $this->userInfo['property_id'];
        $page = $this->request->post('page',1);
        if(empty($uid) || empty($property_id)){
            return api_output_error(1001,'缺少参数');
        }
        $chat_sidebar = new ChatSidebarService();
        $limit = 10;
        $field = 'v.village_id,v.village_name,v.village_address,v.village_logo,b.pigcms_id';
        try{
            $data = $chat_sidebar->getVillageBindList($uid,$property_id,$field,$page,$limit);
        }catch (\Exception $e){
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 获取微信js配置信息
     * @return \json
     */
    public function wx_config(){
        $from_id = $this->request->param('from_id','','int');
        $wx_api_type = $this->request->param('wx_api_type','','int');
        $timestamp = $this->request->param('timestamp','');
        $nonceStr = $this->request->param('nonceStr','');
        $url = $this->request->param('url','');
        if(!$from_id){
            return api_output_error(1001,'必传参数缺失');
        }
        if(!in_array($wx_api_type,[1,2])){
            return api_output_error(1001,'必传参数异常');
        }
        $chat_sidebar = new ChatSidebarService();
        try{
            $data = $chat_sidebar->getWxConfig($from_id,$wx_api_type,$timestamp,$nonceStr,$url);
        }catch (\Exception $e){
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 获取聊天侧边栏客户信息
     * @return \json
     */
    public function getChatSidebarUserInfo(){
        // 外部联系人ID
        $userId = $this->request->post('userId');
        $group_chat_id = $this->request->post('group_chat_id');
        $agentid = $this->request->post('agentid');
        $from_type = $this->request->post('from_type');
        $from_id = $this->request->post('from_id');

        try {
            $db_house_contact_way_user_service = new HouseContactWayUserService();
            $db_work_chat_service = new WorkChatService();
            $db_house_village_order_service = new HouseVillageOrderService();
            $db_house_new_repair_works_order_service = new HouseNewRepairService();
            $dbHouseVillageLabelService = new HouseVillageLabelService();
            $externalUserID=isset($this->userInfo['ExternalUserID']) ? $this->userInfo['ExternalUserID']:'';
            // 获取用户基本信息
            $user_info = $db_house_contact_way_user_service->getUserBindInfo($externalUserID,$this->userInfo['bind_id'],'customer_id,uid,phone,property_id,village_id,bind_id,name,avatar,gender');
            fdump_api([$user_info,'thisuserInfo'=>$this->userInfo,'group_chat_id'=>$group_chat_id], '00getChatSidebarUserInfo');

            // 所在群
            $where_group = [];
            $where_group[] = ['group_id','=',$group_chat_id];
            $field = 'roomname';
            $work_chat_data = $db_work_chat_service->getWorkChatInfo($where_group,$field);
            $village_id=isset($user_info['village_id']) ? $user_info['village_id']:0;
            $room_id=isset($user_info['vacancy_id']) ? $user_info['vacancy_id']:0;
            // 合计费用
            $total_money = (new ChatSidebarService())->getSumMoney($user_info['pigcms_id'], $user_info['uid'],$room_id,$village_id);

            // 投诉建议 
            $where_order = [];
            //$where_order[] = ['uid', '=', $user_info['uid']];
            $where_order[] = ['bind_id', '=', $this->userInfo['bind_id']];
            $where_order[] = ['order_type', '=', 0];
            $order_count = $db_house_new_repair_works_order_service->getOrderCount($where_order);

            // 标签
            $db_user_label_bind = $dbHouseVillageLabelService->handleUserLabel($this->userInfo['bind_id']);
            
            $user_data = [
                [
                    'name' => '小区名称',
                    'field' => 'village_name',
                    'value' => $user_info['village_name'],
                ],
                [
                    'name' => '家庭住址',
                    'field' => 'address',
                    'value' => $user_info['address'],
                ],
                [
                    'name' => '手机号',
                    'field' => 'phone',
                    'value' => $user_info['phone'],
                ],
                [
                    'name' => '昵称',
                    'field' => 'nickname',
                    'value' => $user_info['nickname'],
                ],
                [
                    'name' => '联系TA',
                    'field' => 'phone',
                    'value' => $user_info['phone'],
                ],
            ];
            $data = [];
            $data['user_info'] = $user_info;
            $data['user_data'] = $user_data;
            $data['chat_name'] = [
                'title' => '所在群',
                'value' => $work_chat_data
            ];
            $data['total_money'] = [
                'title' => '合计费用',
                'value' => $total_money
            ];
            $data['order_count'] = [
                'title' => '投诉建议',
                'value' => $order_count
            ];
            $data['label_list'] = [
                'title' => '标签',
                'list' => $db_user_label_bind
            ];
            $data['tab_list'] = [
                ['name' => '缴费记录'],
                ['name' => '轨迹信息'],
                ['name' => '业主信息'],
                ['name' => '业主工单'],
                ['name' => '聊天记录'],
                ['name' => '装修申请单'],
                ['name' => '快递管理'],
                ['name' => '押金管理']
            ];
            if (trim($group_chat_id)) {
                $data['bottom_btn'] = [
                    [
                        'title' => '返回',
                        'color' => '#FFFFFF',
                        'field_color' => '#333333'
                    ],
                    [
                        'title' => '业主转交',
                        'color' => '#2681F3',
                        'field_color' => '#FFFFFF'
                    ]
                ];
            }
            fdump_api(['data'=>$data], '00getChatSidebarUserInfo',1);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 小区用户标签列表
     * @return \think\response\Json
     */
    public function getHouseVillageLabel()
    {
        $village_id = $this->userInfo['village_id'];
        $bind_id = $this->userInfo['bind_id'];
        $field = 'id,label_type,label_name,create_at';
        try {
            // 获取标签列表
            $house_village_label = new HouseVillageLabelService();
            $data = $house_village_label->getHouseVillageUserLabel($village_id, $bind_id, $field);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 绑定小区用户标签关系
     * @return \think\response\Json
     */
    public function changeHouseVillageUserLabelBind(){
        $label_ids = $this->request->post('label_ids');   // 标签ID字符串
        try {
            $house_village_user_label = new HouseVillageUserLabelBindService();
            $data = $house_village_user_label->addUserLabelBind($label_ids,$this->userInfo['bind_id'],$this->userInfo['village_id']);
        }catch (\Exception $e){
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 缴费记录
     * @return \json
     */
    public function getLivingPaymentLog(){
        $page = $this->request->post('page',1,'int');
        $db_house_village_pay_order = new HouseVillagePayOrderService();
        try {
            $data = $db_house_village_pay_order->getLivingPaymentLog($this->userInfo['uid'],$this->userInfo['village_id'],$page);
        }catch (\Exception $e){
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     *押金列表
     * @author: liukezhu
     * @date : 2021/11/10
     * @return \json
     */
    public function getDepositList(){
        $page = $this->request->post('page',1,'int');
        $limit = $this->request->post('limit',10,'int');
        $pigcms_id = $this->request->post('pigcms_id',0,'int');
        if (!$pigcms_id) {
            return api_output_error(1001, '缺少必要参数');
        }
        try {
            $houseVillageUserBindService = new HouseVillageUserBindService();
            $where_user_bind = [];
            $where_user_bind[] = ['pigcms_id', '=', $pigcms_id];
            $userBind = $houseVillageUserBindService->getBindInfo($where_user_bind, 'vacancy_id,uid');
            $where[] = ['pigcms_id', '=', $userBind['vacancy_id']];
            $where[] = ['is_del', '=', 1];
            $field='deposit_id,deposit_name,actual_money,pay_time';
            $order='deposit_id DESC';
            $data = (new HouseVillageDepositService())->getDepositList($where,$field,$order,$page,$limit);
        }catch (\Exception $exception){
            return api_output_error(1003, $exception->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 押金详情
     * @author: liukezhu
     * @date : 2021/11/10
     * @return \json
     */
    public function getDepositInfo(){
        $deposit_id = $this->request->post('deposit_id',0,'int');
        $pigcms_id = $this->request->post('pigcms_id',0,'int');
        if (!$deposit_id || !$pigcms_id)
            return api_output_error(1001, '缺少必要参数');
        try {
            $where[] = ['hvd.pigcms_id', '=', $pigcms_id];
            $where[] = ['hvd.deposit_id', '=', $deposit_id];
            $where[] = ['hvd.is_del', '=', 1];
            $field='hvd.deposit_id,hvub.name,hvd.deposit_name,hvd.pay_type,hvd.payment_money,hvd.actual_money,hvd.deposit_note';
            $data = (new HouseVillageDepositService())->getDepositInfo($where,$field);
        }catch (\Exception $exception){
            return api_output_error(1003, $exception->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 快递代收
     * @author: liukezhu
     * @date : 2021/11/11
     * @return \json
     */
    public function getExpressCollection(){
        $page = $this->request->post('page',1,'int');
        $limit = $this->request->post('limit',10,'int');
        $pigcms_id = $this->request->post('pigcms_id',0,'int');
        $village_id = $this->userInfo && isset($this->userInfo['village_id']) ? $this->userInfo['village_id']:0;
        $uid = $this->_uid;
        if (!$uid && !$pigcms_id) {
            return api_output(0,[]);
        }
        try {
            $data = (new ChatSidebarService())->getExpressCollection($uid,$pigcms_id,$page,$limit, $village_id);
        }catch (\Exception $exception){
            return api_output_error(1003, $exception->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 快递代发
     * @author: liukezhu
     * @date : 2021/11/11
     * @return \json
     */
    public function getExpressSend(){
        $page = $this->request->post('page',1,'int');
        $limit = $this->request->post('limit',10,'int');
        $pigcms_id = $this->request->post('pigcms_id',0,'int');
        $village_id = $this->userInfo && isset($this->userInfo['village_id']) ? $this->userInfo['village_id']:0;
        $uid = $this->_uid;
        if (!$uid && !$pigcms_id) {
            return api_output(0,[]);
        }
        try {
            $data = (new ChatSidebarService())->getExpressSend($uid,$pigcms_id,$page,$limit,$village_id);
        }catch (\Exception $exception){
            return api_output_error(1003, $exception->getMessage());
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
        $limit = $this->request->post('limit',10,'int');
        $village_id= $this->request->post('village_id',0,'int');
        $pigcms_id = $this->request->post('pigcms_id',0,'int');
        if (!$pigcms_id || !$village_id)
            return api_output_error(1001, '缺少必要参数');
        try {
            $where[] = ['business_type', 'in', ["village"]];
            $where[] = ['business_id', '=', $village_id];
            $where[] = ['role_id', '=', $pigcms_id];
            $field='create_at,content';
            $order='id DESC';
            $data = (new HouseUserTrajectoryLogService())->getUserLog($where,1,$field,$order,$page,$limit);
        }catch (\Exception $exception){
            return api_output_error(1003, $exception->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 装修申请单
     * @author: liukezhu
     * @date : 2021/11/12
     * @return \json
     */
    public function getMaterialList(){
        $village_id = $this->request->post('village_id',0,'int');
        $page = $this->request->post('page',1,'int');
        $limit = $this->request->post('limit',10,'int');
        $uid = $this->_uid;
        if (!$uid) {
            return api_output(0,[]);
        }
        try {
            $where[] = ['uid', '=', $uid];
            $where[] = ['diy_tatus', 'in', '0,1,3'];
            if ($village_id) {
                $where[] = ['from', '=', 'house'];
                $where[] = ['from_id', '=', $village_id];
            }
            $field='id,title,add_time,diy_tatus,from_id';
            $order='id DESC';
            $data = (new ChatSidebarService())->getMaterialList($where,$field,$order,$page,$limit,$uid);
        }catch (\Exception $exception){
            return api_output_error(1003, $exception->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 聊天记录
     * @return \json
     */
    public function chatSessionlog(){
        $property_id = 0;
        $from_id = 0; //发送者id
        $to_id = 0; //接受者 id
        $msg_type = $this->request->post('msg_type');  // 聊天内容类型
        $type = $this->request->post('type'); //0员工 1客户 2员工群 3客户群
        $page = $this->request->post('page',1,'int');
        $search_name = $this->request->post('search_name');
        $start_date = $this->request->post('start_date');
        $end_date = $this->request->post('end_date');
        $chat_id = $this->request->post('chat_id'); // 聊天群ID
        $chat_from_id =0; // 群聊的发送者ID
        $param=array();
        $param['msg_type'] = $msg_type;
        $param['chat_id'] = $chat_id;
        $param['type'] = $type;
        if($type == 0 || $type == 1)
        {
            $property_id = $this->userInfo['property_id'];
            $from_id = $this->userInfo['UserID']; //发送者id
            $to_id = $this->userInfo['ExternalUserID']; //接受者 id
            $chat_from_id = $this->userInfo['work_id']; // 群聊的发送者ID
            if (!$from_id || !$to_id) {
                return api_output_error(1001, '请先注册工作人员账号或选择聊天对象!');
            }
            $param['from_id'] = 'audit_'.$from_id;
            $param['to_id'] = 'audit_'.$to_id;
            $param['property_id'] = $property_id;
            $param['chat_from_id'] = $chat_from_id;
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
            if ($list && !is_array($list)) {
                $list = $list->toArray();
            }
            $next_page = false;
            if(count($list) == $limit){
                $next_page = true;
            }
            if (! empty($list)){
                foreach ($list as $k => $v){
                    if(empty($v['info'])){
                        unset($list[$k]);
                    }
                }
                $list = array_values($list);
            }else{
                $list = [];
            }
            if (isset($this->userInfo['work_name']) && $this->userInfo['work_name']) {
                $data['tips'] = "与成员 {$this->userInfo['work_name']} 的对话";
            } elseif (isset($this->userInfo['UserID']) && $this->userInfo['UserID']) {
                $data['tips'] = "与成员 {$this->userInfo['UserID']} 的对话";
            }
            $data['next_page'] = $next_page;
            $data['list'] = array_values($list);
            fdump_api($data, '$data1111');
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 企微群信息或管理人员
     * @return \json
     */
    public function getChatGroupInfo(){
        $chat_id = $this->request->post('chat_id'); // 聊天群ID
        $type = $this->request->post('type','info'); // info表示企微群信息  worker 表示查询管理人员
        $property_id = 0;
        if(!empty($this->userInfo) && isset($this->userInfo['property_id'])){
            $property_id=$this->userInfo['property_id'];
        }
        if(empty($chat_id)){
            return api_output_error(1003,'缺少关键参数');
        }
        $chat_sibedar = new ChatSidebarService();
        try {
            $data = $chat_sibedar->getChatGroupInfo($chat_id,$property_id,$type);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 业主转交
     * @return \json
     */
    public function transferCustomer(){
        $takeover_userid = $this->request->post('takeover_userid'); // 接替成员的userid
        $handover_userid = $this->userInfo['UserID']; // 原跟进成员的userid
        $external_userid = $this->userInfo['ExternalUserID']; // 客户的external_userid列表，每次最多分配100个客户
        $property_id = $this->userInfo['property_id'];
        if(empty($handover_userid) || empty($takeover_userid) || empty($external_userid)){
            return api_output_error(1003,'缺少关键参数');
        }
        $chat_sibedar = new ChatSidebarService();
        try {
            $data = $chat_sibedar->transferCustomer($property_id,$handover_userid,$takeover_userid,$external_userid);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 工单列表
     * @return \json
     */
    public function workersOrderList(){
        $bind_id = $this->userInfo['bind_id'];
        $page = $this->request->post('page',0,'int');
        if(empty($bind_id)){
            return api_output_error(1003,'缺少必要参数');
        }
        $order = 'o.order_id DESC';
        $field = 'o.order_id,o.phone,o.event_status,o.name,o.order_content,o.address_txt,o.add_time as order_add_time,s.subject_name';
        $limit = 10;
        try {
            $chat_sidebar = new ChatSidebarService();
            $data = $chat_sidebar->workersOrderList($bind_id,$field,$page,$limit,$order);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 返回代码
     * @param int $status
     * @param array $data
     * @param string $msg
     * @param string $refresh_ticket
     */
    public function returnCode($status=0,$data=array(),$msg='',$refresh_ticket=''){
        if(!$data){
            $data = [];
        }
        $output = [
            'status' => $status  == '0' ? 1000 : $status,
            'msg' 	 => $msg,
            'data' 	 => $data
        ];

        if(cfg('staff_ticket')){
            // 店员端每次要返回新的ticket,防止过期
            $output['staff_ticket'] = cfg('staff_ticket');
        }
        if ($refresh_ticket) {
            // 传递了刷新
            $output['refresh_ticket'] = $refresh_ticket;
        } else {
            // 空值返回
            $output['refresh_ticket'] = '';
        }
        echo json_encode($output);
        exit();
    }

}