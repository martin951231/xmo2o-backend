<?php
/**
 *======================================================
 * Created by : PhpStorm
 * User: zhanghan
 * Date: 2021/10/26
 * Time: 15:40
 *======================================================
 */

namespace app\community\model\service;

use app\community\model\db\AccessTokenCommonExpires;
use app\community\model\db\Area;
use app\community\model\db\HouseContactWayUser;
use app\community\model\db\HouseEnterpriseWxBind;
use app\community\model\db\HouseNewRepairCate;
use app\community\model\db\HouseNewRepairCateCustom;
use app\community\model\db\HouseNewRepairSubject;
use app\community\model\db\HouseNewRepairWorksOrder;
use app\community\model\db\HouseNewRepairWorksOrderLog;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageBindCar;
use app\community\model\db\HouseVillageBindPosition;
use app\community\model\db\HouseVillageDataConfig;
use app\community\model\db\HouseVillageExpress;
use app\community\model\db\HouseVillageExpressSend;
use app\community\model\db\HouseVillageParkingCar;
use app\community\model\db\HouseVillageParkingGarage;
use app\community\model\db\HouseVillageParkingPosition;
use app\community\model\db\HouseVillagePayOrder;
use app\community\model\db\HouseVillageRepairCate;
use app\community\model\db\HouseVillageRepairList;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\HouseWorker;
use app\community\model\db\PluginMaterialDiyFile;
use app\community\model\db\PluginMaterialDiyRemark;
use app\community\model\db\PluginMaterialDiyValue;
use app\community\model\db\HouseVillageUserLabelBind;
use app\community\model\db\VillageQywxAgent;
use app\community\model\db\WorkMsgAuditInfo;
use app\community\model\db\WorkMsgAuditInfoGroup;
use app\community\model\db\HouseNewPayOrder;
use app\community\model\service\NewBuildingService;
use app\community\model\service\workweixin\WorkWeiXinNewService;
use net\Http as Http;
use think\facade\Db;
use token\Ticket;


class ChatSidebarService
{
    // 填写模板对应状态
    public $diy_tatus_txt = [
        0 => '待审核',
        1 => '已通过',
        3 => '已拒绝',
        4 => '已删除',
    ];
    // 图片文件的后缀
    public $image_exts = array(
        'jpg', 'jpeg', 'png', 'gif'
    );
    // 图片文件的所有类型
    public $image_type = array(
        'image/png', 'image/x-png', 'image/jpg', 'image/jpeg',
        'image/pjpeg', 'image/gif', 'image/x-icon'
    );
    public $attachment_type_config = [
        5 => [
            'name' => '图片',
            'accept' => '.jpg,.jpeg,.png,.gif,.bmp',
            'allow_exts' => ['jpg', 'jpeg', 'png', 'gif', 'bmp'],
        ],
        6 => [
            'name' => 'PDF',
            'accept' => '.pdf',
            'allow_exts' => ['pdf'],
        ],
        7 => [
            'name' => '附件',
            'accept' => '.jpg,.jpeg,.png,.gif,.bmp,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar',
            'allow_exts' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'rar'],
        ],
    ];
    public $express_goods_type = array('0'=>'文件','1'=>'数码产品','2'=>'生活用品','3'=>'服饰','4'=>'食品','5'=>'其他');
    public $express_status = array('0'=>'未送','1'=>'业主确认取件','2'=>'物业确认取件');
    // 工作人员工单状态对应中文
    public $order_work_status_txt = [
        '10'=> '待指派',
        '11'=> '待指派(驳回)',// 工作人员 驳回给处理中心
        '14'=> '待指派(拒绝)',// 工作人员 拒绝

        '20' => '已指派',
        '21' => '已指派',// 物业管理员指派
        '22' => '已指派',// 物业工作人员指派
        '23' => '已指派',// 小区管理员指派
        '24' => '转单',// 工作人员转单指派
        '25' => '已指派',// 工作人员拒绝转单指派

        '30' => '处理中',
        '34' => '处理中', // 工作人员拒绝

        '40' => '已办结',// 工作人员办结
        '41' => '已办结',// 物业管理员回复 办结
        '42' => '已办结',// 物业工作人员回复 办结
        '43' => '已办结',// 小区管理员回复 办结

        '50' => '已撤回',
        '60' => '已关闭',
        '70' => '已评价'
    ];
    // 工单状态对应颜色
    public $order_status_color = [
        '10'=>'#FE3950',
        '11'=>'#FE3950',
        '14'=>'#FE3950',

        '20' => '#00CC00',
        '21' => '#00CC00',
        '22' => '#00CC00',
        '23' => '#00CC00',
        '24' => '#00CC00',
        '25' => '#00CC00',

        '30' => '#26A6FF',
        '34' => '#26A6FF',

        '40' => '#787ADF',
        '41' => '#787ADF',
        '42' => '#787ADF',
        '43' => '#787ADF',

        '50' => '#06C1AE',
        '60' => '#CCCCCC',
        '70' => '#FFA112'
    ];

    public function getTicket($uid,$deviceId='packapp'){
        $ticket = Ticket::create($uid, $deviceId, true);
        return $ticket;
    }

    //todo 获取用户所在小区id
    public function getVillageId($pigcms_id){
        $where[] =['pigcms_id', '=', $pigcms_id];
        $user=(new HouseVillageUserBind())->getOne($where,'village_id');
        if($user){
            $user=$user->toArray();
        }
        if(empty($user)){
            throw new \think\Exception("该用户不存在");
        }
        return $user['village_id'];
    }

    /**
     * 获取自建应用的配置信息
     * @param $from_id // 物业ID
     * @param $wx_api_type // 1企业  2应用
     * @param $timestamp
     * @param $nonceStr
     * @param $url
     * @return array
     * @throws \Exception
     */
    public function getWxConfig($from_id,$wx_api_type,$timestamp,$nonceStr,$url){
        $dbQywxService = new QywxService();
        $enterpriseWeChatService = new EnterpriseWeChatService();

        $property_id = $from_id;
        //$access_token = $dbQywxService->getQywxAccessToken($property_id,'enterprise_wx_provider'); // 企业的
        try {
            $access_token_info = (new WorkWeiXinNewService())->getCgiBinServiceGetCorpToken($property_id, true);
        }catch (\Exception $e){
            fdump_api(['line' => $e->getLine(),'$property_id' => $e->getMessage(),'code' => $e->getCode()], '$getWxConfigChat', true);
        }
        $access_token = isset($access_token_info['access_token']) ? $access_token_info['access_token'] : '';
        if(empty($access_token)){
            return [ 'data' => $access_token ];
        }
        $res = $enterpriseWeChatService->disposeTicket($access_token,$wx_api_type,$property_id);
        fdump_api(['$access_token' => $access_token, '$res' => $res], '$disposeTicket', true);
        if($res['errcode'] == 0){
            $jsapi_ticket = $res['jsapi_ticket'];
        }else{
            throw new \Exception($res['errmsg']);
        }

        $db_house_enterprise_wx_bind = new HouseEnterpriseWxBind();
        $data = $db_house_enterprise_wx_bind->getOne(['bind_id'=>$property_id],'corpid, agentid');
        if (!$timestamp) {
            $timestamp = (string)time();
        }
        if (!$nonceStr) {
            $nonceStr  = (string)rand(100000,999999);
        }
        $content_url = $url;
        if (!$content_url) {
            $content_url = cfg('site_url') .'/packapp/workweixin/DevelopmentStation.html?from_id='.$from_id.'&from_type=2';
        }
        $signature_url = 'jsapi_ticket='.$jsapi_ticket.'&noncestr='.$nonceStr.'&timestamp='.$timestamp.'&url='.$content_url;
        $signature = sha1($signature_url);
        $return['appId'] = $data['corpid'];//企业微信的corpID
        $return['noncestr'] = $nonceStr;//生成签名的随机串
        $return['timestamp'] = $timestamp;//生成签名的时间戳
        $return['signature'] = $signature; //签名
        fdump_api(['$return' => $return, '$data' => $data], '$return', true);
        if($wx_api_type == 2){//应用的id
            $serviceQywx = new QywxService();
            $agent_info = $serviceQywx->getAgentByProperty($property_id);
            if($agent_info && $agent_info['agentid']){
                $return['agentid'] =$agent_info['agentid'];
            }else{
                $return['agentid'] = $data['agentid'];
            }
        }
        return $return;
    }

    /**
     * 快递代收
     * @author: liukezhu
     * @date : 2021/11/12
     * @param $uid
     * @param $pigcms_id
     * @param $page
     * @param $limit
     * @return array
     * @throws \think\Exception
     */
    public function getExpressCollection($uid,$pigcms_id,$page,$limit,$village_id=0){
        $count=0;
        $dataList=[];
        $arr1 = array('未取件', '已取件（业主)', '已取件（小区）');
        $paidDesc = array('未支付', '已支付');
        $HouseVillageExpress=new HouseVillageExpress();
        // 用户小区信息
        $houseVillageUserBind = new HouseVillageUserBind();
        if ($pigcms_id) {
            $where_user_bind = [];
            $where_user_bind[] = ['pigcms_id','=',$pigcms_id];
            $userBind = $houseVillageUserBind->getOne($where_user_bind,'address,uid');
            !$uid && $uid = isset($userBind['uid']) && $userBind['uid'] ? $userBind['uid'] : 0;
            $address = isset($userBind['address']) && $userBind['address'] ? $userBind['address'] : '';
        } else {
            $address = '';
        }
        // 列表
        $where[] = ['p.uid', '=', $uid];
        $where[] = ['v.has_express_service', '=', 1];
        if ($village_id) {
            $where[] = ['p.village_id', '=', $village_id];
        } else {
            $village_id=self::getVillageId($pigcms_id);
            $where[] = ['p.village_id', '=', $village_id];
        }
        $field='p.*,e.name as express_name,o.paid,o.send_time,v.village_name';
        $order='p.id desc';
        $list = $HouseVillageExpress->getList($where,$field,$order,$page,$limit);
        if($list){
            $houseVillageDb=new HouseVillage();
            $village_info=$houseVillageDb->getInfo(array('village_id'=>$village_id),'*');
            $list=$list->toArray();
            foreach ($list as $k => $v) {
                $phone_tmp=$houseVillageDb->phone_desensitization_v($v['phone'],$village_id,$village_info);
                $dataList[] = [
                    'id'            =>  $v['id'],
                    'express_no'    =>  $v['express_no'],
                    'express_status'=>  $v['status'],
                    'express_msg'   =>  $arr1[$v['status']],
                    'pay_status'    =>  empty($v['paid']) ? 0 : $v['paid'],
                    'pay_msg'       =>  empty($v['paid']) ? $paidDesc[0] : $paidDesc[$v['paid']],
                    'fetch_code'    =>  $v['fetch_code'],
                    'express_name'  =>  $v['express_name'],
                    'money'         =>  $v['money'] . cfg('Currency_txt'),
                    'remarks'       =>  $v['memo'],
                    'add_time'      =>  date('Y-m-d H:i:s', $v['add_time']),
                    'collect_info'       =>  $v['express_name'].PHP_EOL.$v['express_no'],
                    'collect_address'       =>  $v['village_name'].$address,
                    'phone'       =>  $phone_tmp,
                    'send_time'       =>  !empty($v['send_time']) ? date("Y-m-d H:i:s",$v['send_time']) : ''
                ];
            }
            $count= $HouseVillageExpress->getCount($where);
        }
        return ['list'=>$dataList,'count'=>$count];
    }

    /**
     * 快递代发
     * @author: liukezhu
     * @date : 2021/11/12
     * @param $uid
     * @param $pigcms_id
     * @param $page
     * @param $limit
     * @return array
     * @throws \think\Exception
     */
    public function getExpressSend($uid,$pigcms_id,$page,$limit,$village_id=0){
        $houseVillageUserBind = new HouseVillageUserBind();
        if ($pigcms_id) {
            $where_user_bind = [];
            $where_user_bind[] = ['pigcms_id','=',$pigcms_id];
            $userBind = $houseVillageUserBind->getOne($where_user_bind,'address,uid');
            !$uid && $uid = isset($userBind['uid']) && $userBind['uid'] ? $userBind['uid'] : 0;
        }
        $count=0;
        $dataList=[];
        $HouseVillageExpressSend=new HouseVillageExpressSend();
        $where[] = ['s.uid', '=', $uid];
        $where[] = ['v.has_express_send', '=', 1];
        if ($village_id) {
            $where[] = ['s.village_id', '=', $village_id];
        } else {
            $village_id=self::getVillageId($pigcms_id);
            $where[] = ['s.village_id', '=', $village_id];
        }
        $field='s.send_id,s.send_uname,s.send_phone,s.send_city,s.send_adress,s.collect_uname,s.collect_phone,s.collect_city,s.collect_adress,s.express,s.weight,s.goods_type,s.remarks,s.send_price,s.add_time,s.export_time,e.name as express_name';
        $order='s.send_id desc';
        $list = $HouseVillageExpressSend->getList($where,$field,$order,$page,$limit);
        if($list){
            $houseVillageDb=new HouseVillage();
            $village_info=$houseVillageDb->getInfo(array('village_id'=>$village_id),'*');
            
            $list=$list->toArray();
            foreach ($list as $v){
                $phone_tmp=$houseVillageDb->phone_desensitization_v($v['send_phone'],$village_id,$village_info);
                $dataList[] = [
                    'send_id'                =>  $v['send_id'],
                    'express'           =>  $v['express'],
                    'expressDesc'       =>  $v['express_name'],
                    'add_time'          =>  date('Y-m-d H:i:s', $v['add_time']),
                    'export_time'          =>  !empty($v['export_time']) ? date('Y-m-d H:i:s', $v['export_time']) : '',
                    'goods_type_text'        =>  $this->express_goods_type[$v['goods_type']],
                    'send_uname'        =>  $v['send_uname'],
                    'send_phone'        =>  $phone_tmp,
                    'send_adress'       =>  $v['send_city'] . $v['send_adress'],
                    'weightDesc'        =>  $v['weight'] . '(kg)',
                    'send_price'        =>  $v['send_price'] . cfg('Currency_txt'),
                    'collect_uname'     =>  $v['collect_uname'],
                    'collect_phone'     =>  $v['collect_phone'],
                    'collect_adress'    =>  $v['collect_city'] . $v['collect_adress'],
                    'remarks'           =>  $v['remarks']
                ];
            }
            $count= $HouseVillageExpressSend->getCount($where);
        }
        return ['list'=>$dataList,'count'=>$count];
    }

    /**
     * 装修申请单
     * @author: liukezhu
     * @date : 2021/11/12
     * @param $where
     * @param $field
     * @param $order
     * @param $page
     * @param $limit
     * @return array
     */
    public function getMaterialList($where,$field,$order,$page,$limit,$uid=0){
        $model=new PluginMaterialDiyValue();
        $base_url = (new HouseVillageService())->base_url;
        $list =$model->getList($where,$field,$order,$page,$limit);
        $count=0;
        if($list){
            $list=$list->toArray();
            foreach ($list as &$v){
                $v['add_time']=date('Y-m-d H:i:s',$v['add_time']);
                $v['url'] = cfg('site_url') . $base_url."pages/village/materialDiy/materialDetails?diy_id={$v['id']}&from_id={$v['from_id']}";
                $v['edit_url'] = cfg('site_url') . "/packapp/material_diy/index.html#/template_write?diy_id={$v['id']}&paths=write_list&t=".time().rand(1000,9999);
                $v['diy_tatus_txt'] = $this->diy_tatus_txt[$v['diy_tatus']];
            }
            unset($v);
            $count= $model->getCount($where);
        }
        $ticket='';
        if($uid > 0){
            $ticket=self::getTicket($uid)['ticket'];
        }
        return ['list'=>$list,'count'=>$count,'total_limit' => $limit,'ticket'=>$ticket,'uid'=>$uid];
    }

    /**
     * 获取用户相关企微信息
     * @param $where
     * @param bool $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserQWInfo($where,$field = true){
        $db_house_village_user_bind  = new HouseVillageUserBind();
        $field .= ',vub.pigcms_id,vub.name,vub.phone,vub.authentication_field,vub.village_id,vub.uid,vub.single_id,vub.layer_id,vub.floor_id,vub.vacancy_id,u.phone as u_phone,u.sex as u_sex,u.avatar as u_avatar,s.area_name';
        // 获取社区业主的相关信息
        $house_village_user_bind_data = $db_house_village_user_bind->getStreetUserInfo($where,$field);
        $data = [];
        if(!empty($house_village_user_bind_data)){
            $authentication_field = unserialize($house_village_user_bind_data['authentication_field']);
            $house_village_user_bind_data['sex'] = isset($authentication_field['sex']) ? $authentication_field['sex']['value'] : $house_village_user_bind_data['u_sex'];
            $house_village_user_bind_data['phone'] = !empty($house_village_user_bind_data['phone']) ? $house_village_user_bind_data['phone'] : $house_village_user_bind_data['u_phone'];
            $house_village_user_bind_data['is_wx'] = false;
            $house_village_user_bind_data['avatar'] = '';
            unset($house_village_user_bind_data['authentication_field']);
            // 获取业主的标签
            $where_label = [];
            $where_label[] = ['ulb.bind_id','=',$house_village_user_bind_data['pigcms_id']];
            $where_label[] = ['ulb.is_delete','=',0];
            $where_label[] = ['hvl.is_delete','=',0];
            $where_label[] = ['hvl.status','=',0];
            $field_label = 'hvl.label_name';
            $user_label = new HouseVillageUserLabelBind();
            // 获取用户标签列表
            $user_label_list = $user_label->getUserLabelList($where_label,$field_label);
            $user_label_arr = array_column($user_label_list,'label_name');
            $house_village_user_bind_data['user_label'] = implode(',',$user_label_arr);
            // 获取业主的名称及是否已关联微信
            $where_way = [];
            $where_way[] = ['bind_id','=',$house_village_user_bind_data['pigcms_id']];
            $where_way[] = ['village_id','=',$house_village_user_bind_data['village_id']];
            $field_way = 'customer_id,name,avatar';
            $user_way = new HouseContactWayUser();
            // 外部用户信息
            $user_way_data = $user_way->getOne($where_way,$field_way);
            if($user_way_data && !$user_way_data->isEmpty()){
                $user_way_data = $user_way_data->toArray();
                $user_way_data['name'] = !empty($user_way_data['name']) ? $user_way_data['name'] : $house_village_user_bind_data['nickname'];
                $house_village_user_bind_data['name'] = !empty($house_village_user_bind_data['name']) ? $house_village_user_bind_data['name'] : $user_way_data['name'];
                if(!empty($user_way_data)){
                    $house_village_user_bind_data['is_wx'] = true;
                    // 头像
                    $house_village_user_bind_data['avatar'] = $user_way_data['avatar'] ? $user_way_data['avatar'] : $user_way_data['u_avatar'];
                }
            }
            if(empty($house_village_user_bind_data['avatar'])){
                $house_village_user_bind_data['avatar'] = '/static/images/user_avatar.jpg';
            }
            // 地址
            $houseVillageService = new HouseVillageService();
            $address = $houseVillageService->getSingleFloorRoom($house_village_user_bind_data['single_id'],$house_village_user_bind_data['floor_id'],$house_village_user_bind_data['layer_id'],$house_village_user_bind_data['vacancy_id'],$house_village_user_bind_data['village_id']);
            $house_village_user_bind_data['address'] = !empty($address) ? $address : $house_village_user_bind_data['address'];
            $village_id = $house_village_user_bind_data['village_id'] ?? 0;
            $houseName = (new HouseVillageService())->getHouseAlias('house_name',$village_id)?:'小区';
            $user_data = [
                [
                    'name' => '社区名称',
                    'field' => 'village_name',
                    'value' => $house_village_user_bind_data['area_name'],
                ],
                [
                    'name' => $houseName.'名称',
                    'field' => 'village_name',
                    'value' => $house_village_user_bind_data['village_name'],
                ],
                [
                    'name' => '家庭住址',
                    'field' => 'address',
                    'value' => $house_village_user_bind_data['address'],
                ],
                [
                    'name' => '手机号',
                    'field' => 'phone',
                    'value' => $house_village_user_bind_data['phone'],
                ],
                [
                    'name' => '标签',
                    'field' => 'label',
                    'type' => 'edit',
                    'value' => $house_village_user_bind_data['user_label'],
                ],
                [
                    'name' => '昵称',
                    'field' => 'nickname',
                    'value' => $house_village_user_bind_data['nickname'],
                ]
            ];
            $data['userInfo'] = $house_village_user_bind_data;
            $data['userData'] = $user_data;
            // 合计费用
            $where_money = [];
            $where_money[] = ['village_id' , '=', $village_id];
            $where_money[] = ['pay_bind_id' , '=', $house_village_user_bind_data['pigcms_id']];
            $where_money[] = ['room_id' , '=', $house_village_user_bind_data['vacancy_id']];
            $where_money[] = ['is_paid' , '=', 1];

            $house_new_pay_order = new HouseNewPayOrder();
            $sum_money=$house_new_pay_order->getSum($where_money,'pay_money');
            $sum_money=formatNumber($sum_money,2,1);
             //$house_village_pay_order = new HouseVillagePayOrder();
            //$sum_money = $house_village_pay_order->sumMoney($where_money);
            // 投诉与建议
            $where_order = [];
            $where_order[] = ['bind_id', '=', $house_village_user_bind_data['pigcms_id']];
            $where_order[] = ['order_type', '=', 0];
            $houseNewRepairWorksOrder = new HouseNewRepairWorksOrder();
            $order_num = $houseNewRepairWorksOrder->getCount($where_order);
            // 所在群
            $work_chat_data = '无';
            if(isset($user_way_data['customer_id']) && !empty($user_way_data['customer_id'])){
                $customer_id = $user_way_data['customer_id'];
                $whereChat = "(FIND_IN_SET('".$customer_id."',tolist) or user_id=$customer_id or external_id=$customer_id ) and roomid !='' and chat_id!=''";
                $dbWorkMsgAuditInfo = new WorkMsgAuditInfo();
                $fieldChat = 'chat_id';
                // 获取业主会话消息汇总列表
                $chat_list = $dbWorkMsgAuditInfo->getList($whereChat, $fieldChat, 'msgtime asc');
                if($chat_list && !$chat_list->isEmpty()){
                    $chat_list = $chat_list->toArray();
                    $chat_list_arr = array_unique(array_column($chat_list,'chat_id'));
                    $dbWorkMsgAuditInfoGroup = new WorkMsgAuditInfoGroup();
                    $group_where[] = ['id','in',$chat_list_arr];
                    // 获取业主群聊列表
                    $group_data = $dbWorkMsgAuditInfoGroup->getList($group_where,'roomname');
                    if($group_data && !$group_data->isEmpty()){
                        $group_data = $group_data->toArray();
                        $group_data_arr = array_unique(array_column($group_data,'roomname'));
                        $work_chat_data = implode(',',$group_data_arr);
                    }
                }
            }

            $data['chat_name'] = [
                'title' => '所在群',
                'value' => $work_chat_data
            ];
            $data['total_money'] = [
                'title' => '合计费用',
                'value' => $sum_money
            ];
            $data['order_count'] = [
                'title' => '投诉建议',
                'value' => $order_num
            ];
            $data['tab_list'] = [
                'paymentRecord' => '缴费记录',
                'trackInformation' => '轨迹信息',
                'ownerInformation' => '业主信息',
                'workOrder' => '业主工单',
                'chatRecord' => '聊天记录',
                'decorationOrder' => '装修申请单',
                'expressManagement' => '快递管理',
                'depositManagement' => '预存管理'
            ];
        }
        return $data;
    }

    /**
     * 获取用户信息
     * @param $village_id
     * @param $pigcms_id
     * @param $field
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserInfo($village_id,$pigcms_id,$field){
        $data = [];
        $where_village = [];
        $where_village[] = ['village_id','=',$village_id];
        $db_house_village = new HouseVillageService();
        // 获取小区物业ID
        $house_village_property = $db_house_village->getHouseVillageInfo($where_village,'property_id,street_id');
        $where_data_config = [
            ['village_id','=',0],
            ['property_id','=',$house_village_property['property_id']],
            ['is_display','=',1],
            ['is_close','=',0]
        ];
        $db_house_village_data_config = new HouseVillageDataConfig();
        // 获取用户基本资料字段
        $dataList = $db_house_village_data_config->getList($where_data_config);
        if($dataList && !$dataList->isEmpty()){
            $dataList = $dataList->toArray();
            foreach($dataList as $k=>&$v){
                if($v['type'] == 2){
                    if($v['is_system'] == 1){
                        $use_field = $db_house_village->get_system_value($v['key']);
                        $v['use_field'] = !empty($use_field)? array_unique($use_field):array();
                    }else{
                        $v['use_field'] = explode(',',$v['use_field']);
                    }
                }elseif($v['type'] == 3){
                    $v['use_field'] = [];
                }else{
                    $v['use_field'] = [];
                }
            }
            $data['dataList'] = $dataList;
        }else{
            $data['dataList'] = [];
        }

        if ($pigcms_id) {
            $bind_condition[] = ['pigcms_id', '=', $pigcms_id];
            $db_house_village_user_bind = new HouseVillageUserBind();
            // 获取小区用户信息
            $info = $db_house_village_user_bind->getOne($bind_condition,$field);
            if($info && !$info->isEmpty()){
                $info = $info->toArray();
            }else{
                return '未查询到相应信息';
            }
            // 业主资料
            if(!empty($info['authentication_field'])){
                $info['authentication_field'] = unserialize($info['authentication_field']);
            }else{
                $info['authentication_field'] = $data['dataList'];
            }

            // 房间类型
            if ($info['vacancy_id']) {
                $where_vacancy = [];
                $where_vacancy[] = ['pigcms_id','=',$info['vacancy_id']];
                $db_house_village_user_vacancy = new HouseVillageUserVacancy();
                $house_type = $db_house_village_user_vacancy->getOne($where_vacancy,'house_type');
                $info['house_type'] = $house_type['house_type'];
            }
            if (!$info['house_type']) {
                $info['house_type'] = 1;
            }
            if($info['house_type'] == 1){
                $info['house_type_name'] = '住宅';
            }elseif ($info['house_type'] == 2){
                $info['house_type_name'] = '商铺';
            }else{
                $info['house_type_name'] = '办公';
            }

            $authentication_field = [];
            if(!empty($info['authentication_field'])){
                foreach($info['authentication_field'] as $k => &$v){
                    if ('object'==gettype($v) || !isset($v['type'])) {
                        continue;
                    }
                    // 籍贯特殊处理 获取省市
                    if($v['type'] == 3){
                        if (isset($v['value']) && $v['value']) {
                            $v['value'] = explode('#',$v['value']);
                            $v['province_idss'] = $v['value'][0];
                            $v['city_idss'] = $v['value'][1];
                            $where_area_province = [];
                            $where_area_province[] = ['area_id','=',$v['province_idss']];
                            $area = new Area();
                            $area_data = $area->getOne($where_area_province,'area_name');
                            $v['province_name'] = $area_data['area_name'];
                            $where_area_city = [];
                            $where_area_city[] = ['area_id','=',$v['city_idss']];
                            $area = new Area();
                            $area_data = $area->getOne($where_area_city,'area_name');
                            $v['city_name'] = $area_data['area_name'];
                        } else {
                            $v['value'] = '';
                            $v['province_idss'] = 0;
                            $v['city_idss'] = 0;
                        }
                    }else{
                        if (!isset($v['value']) || empty($v['value'])) {
                            $v['value'] = '';
                            // 出生日期 特殊处理
                            if($v['type'] == 4){
                                $v['value'] = date("Y-m-d",time());
                            }
                        }
                    }
                    if(!isset($v['key'])){
                        $v['key'] = $k;
                    }
                    $authentication_field[$v['key']] = $v;
                }
            }
            if($data['dataList'] && is_array($data['dataList'])){
                foreach ($data['dataList'] as $dvv){
                    if(!isset($authentication_field[$dvv['key']])){
                        $authentication_field[$dvv['key']]=array('value'=>'','key'=>$dvv['key'],'title'=>$dvv['title']);
                    }
                }
            }
            $info['authentication_field'] = $authentication_field;
            // 地址
            $houseVillageService = new HouseVillageService();
            $address = $houseVillageService->getSingleFloorRoom($info['single_id'],$info['floor_id'],$info['layer_id'],$info['vacancy_id'],$info['village_id']);
            $info['address'] = !empty($address) ? $address : $info['address'];
            $data['info'] = $info;
            // 用户标签列表
            $newBuildingService=new NewBuildingService();
            $paramData=array(
                'property_id'=>$house_village_property['property_id'],
                'street_id'=>$house_village_property['street_id'],
                'village_id'=>$village_id,
                'vacancy_id'=>$info['vacancy_id'],
                'pigcms_id'=>$pigcms_id
            );;
            $roomBindUserData=$newBuildingService->getRoomBindUserData($paramData);
            $data['user_label_data']=$roomBindUserData;
            //车位信息
            // 绑定信息
            $whereBindPosition = [];
            $whereBindPosition[] = ['user_id',    '=', $pigcms_id];
            $whereBindPosition[] = ['village_id', '=', $village_id];
            $position_id_arr = (new HouseVillageBindPosition())->getColumn($whereBindPosition, 'position_id');
            // 车位信息
            $wherePosition = [];
            $wherePosition[] = ['village_id',  '=', $village_id];
            $wherePosition[] = ['position_id', 'in', $position_id_arr];
            $position_list = (new HouseVillageParkingPosition())->getList($wherePosition, 'position_id as bind_id, garage_id, position_num, position_area, position_note', 0);
            if ($position_list && !is_array($position_list)) {
                $position_list = $position_list->toArray();
            }
            $garage_id_arr = [];
            foreach ($position_list as $position1) {
                $garage_id_arr[] = $position1['garage_id'];
            }
            if (!empty($garage_id_arr)) {
                // 车库信息
                $whereGarage = [];
                $whereGarage[] = ['village_id', '=', $village_id];
                $whereGarage[] = ['garage_id',  'in', $garage_id_arr];
                $garage_arr = (new HouseVillageParkingGarage())->getColumn($whereGarage, 'garage_num', 'garage_id');
            }
            foreach ($position_list as &$position) {
                $garage_id = $position['garage_id'];
                $position['garage_num'] = isset($garage_arr) && isset($garage_arr[$garage_id]) ? $garage_arr[$garage_id] : '';
            }
            $data['position_list'] = $position_list;
            //车辆信息
            // 获得车辆绑定
            $whereBindCar = [];
            if (isset($info['uid']) && $info['uid'] > 0) {
                $whereBindCar[] = [['user_id', '=', $pigcms_id], ['uid','=',$info['uid']], 'or'];
            } else {
                $whereBindCar[] = ['user_id', '=', $pigcms_id];
            }
            $whereBindCar[] = ['village_id', '=', $village_id];
            $car_id_arr = (new HouseVillageBindCar())->getColumn($whereBindCar, 'car_id');
            // 车辆信息
            $car_position_id_arr = [];
            if (!empty($car_id_arr)) {
                $whereCar = [];
                $whereCar[] = ['village_id', '=', $village_id];
                $whereCar[] = ['car_id', 'in', $car_id_arr];
                $fieldCar = "car_id as id, concat(`province`,'',`car_number`) as province, car_stop_num, car_user_name, car_user_phone, car_position_id";
                $car_list = (new HouseVillageParkingCar())->getHouseVillageParkingCarLists($whereCar, $fieldCar, 0);
                if ($car_list && !is_array($car_list)) {
                    $car_list = $car_list->toArray();
                }
                foreach ($car_list as $car1) {
                    if ($car1['car_position_id']) {
                        $car_position_id_arr[] = $car1['car_position_id'];
                    }
                }
                
            }
            // 车位信息
            if ($car_position_id_arr) {
                $whereCarPosition = [];
                $whereCarPosition[] = ['village_id',  '=', $village_id];
                $whereCarPosition[] = ['position_id', 'in', $car_position_id_arr];
                $position_info_id_arr = (new HouseVillageParkingPosition())->getColumn($whereCarPosition, 'position_num', 'position_id');
                if ($position_info_id_arr && !is_array($position_info_id_arr)) {
                    $position_info_id_arr = $position_info_id_arr->toArray();
                }
            }
            if (isset($car_list) && !empty($car_list)) {
                foreach ($car_list as &$car) {
                    $car_position_id = $car['car_position_id'];
                    $car['position_num'] = isset($position_info_id_arr) && isset($position_info_id_arr[$car_position_id]) ? $position_info_id_arr[$car_position_id] : '';
                }
                $data['car_list'] = $car_list;
            } else {
                $data['car_list'] = [];
            }
        }
        return $data;
    }

    /**
     * 获取省市
     * @param $where
     * @param $field
     * @return array
     */
    public function getProvinceCity($where,$field){
        $area = new Area();
        $area_data = $area->getList($where,$field);
        if(!$area_data->isEmpty()){
            $area_data = $area_data->toArray();
            return $area_data;
        }
        return [];
    }

    public function getSumMoney($pigcms_id, $uid = 0, $room_id = 0,$village_id=0)
    {
        // 合计费用
        $where_money = [];
        if($village_id>0){
            $where_money[] = ['village_id', '=', $village_id];
        }
        if($room_id>0){
            $where_money[] = ['room_id', '=', $room_id];
        }
        if($pigcms_id>0){
            $where_money[] = ['pay_bind_id', '=', $pigcms_id];
        }
        $where_money[] = ['is_paid', '=', 1];

        $house_new_pay_order = new HouseNewPayOrder();
        $sum_money = $house_new_pay_order->getSum($where_money, 'pay_money');
        $sum_money=$sum_money>0 ? round($sum_money,2):0;
        return $sum_money;
    }

    /**
     * 工单详情
     * @param $village_id
     * @param $pigcms_id
     * @param $field
     * @return array|array[]
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRepairInfo($order_id,$field){
        $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
        $db_house_new_repair_cate = new HouseNewRepairCate();
        $db_house_new_repair_subject = new HouseNewRepairSubject();

        // 报修工单列表
        $where_list = [];
        $where_list[] = ['order_id','=',$order_id];
        $repairList = $db_house_new_repair_works_order->getOne($where_list,$field);
        $data = [];
        if(!$repairList->isEmpty()){
            $repairList = $repairList->toArray();
            $repairList['status_str'] = $repairList['event_status']?$this->order_work_status_txt[$repairList['event_status']]:'';
            $repairList['date_time'] = date("Y-m-d H:i:s",$repairList['add_time']);
            // 工单类目
            $subject_name = '';
            if(isset($repairList['category_id'])){
                $subject_info = $db_house_new_repair_subject->getOne(['id'=>$repairList['category_id']],'subject_name');
                if($subject_info){
                    $subject_name = $subject_info['subject_name'];
                }
            }
            // 上报分类
            $cate_name = '';
            $fcate_name='';
            if($repairList['cat_fid']){
                $cate_info = $db_house_new_repair_cate->getOne(['id'=>$repairList['cat_fid'],'status'=>1],'cate_name');
                $fcate_name = $cate_info['cate_name'];
            }
            if($repairList['cat_id']){
                $cate_info = $db_house_new_repair_cate->getOne(['id'=>$repairList['cat_id'],'status'=>1],'cate_name');
                $cate_name = $cate_info['cate_name'];
                if(!empty($fcate_name)){
                   // $cate_name = $fcate_name.'/'.$cate_info['cate_name'];
                }
            }
            $repairList['cate_name'] = $cate_name;
            // 自定义字段
            $tag_list = [];
            $db_house_new_repair_cate_custom = new HouseNewRepairCateCustom();
            if($repairList['label_txt']){
                $tag_list = $db_house_new_repair_cate_custom->getList([['id','in',$repairList['label_txt']]],'name')->toArray();
            }
            $repairList['tags'] = implode(';',array_column($tag_list,'name'));
            // 处理人员
            $db_house_worker = new HouseWorker();
            $where_worker = [];
            $where_worker[] = ['wid','=',$repairList['worker_id']];
            $worker_info = $db_house_worker->get_one($where_worker,'name');
            if(empty($worker_info)){
                $worker_info['name'] = '';
            }
            $data = [
                ['title' => '上报人员', 'value' => $repairList['name'], 'type' => 'text'],
                ['title' => '联系号码', 'value' => $repairList['phone'], 'type' => 'text'],
                ['title' => '上报时间', 'value' => $repairList['date_time'], 'type' => 'text'],
                ['title' => '上报位置', 'value' => $repairList['address_txt'], 'type' => 'text'],
                ['title' => '工单类目', 'value' => $fcate_name, 'type' => 'text'],
                ['title' => '上报分类', 'value' => $repairList['cate_name'], 'type' => 'text'],
                ["title" => "自定义字段", "value"=> (!empty($repairList['tags']) ? $repairList['tags'] : '无'),'type' => 'text'],
                ['title' => '补充说明', 'value' => (!empty($repairList['order_content']) ? $repairList['order_content'] : '无'), 'type' => 'text'],
                ['title' => '状态', 'value' => $repairList['status_str'], 'type' => 'text'],
                ['title' => '处理人员', 'value' => $worker_info['name'], 'type' => 'text']
            ];
        }
        return $data;
    }

    /**
     * 装修申请单附件列表
     * @param $where
     * @param $page
     * @param $page_size
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getWriteFileList($diy_id, $page, $page_size){
        $where_diy = [];
        $where_diy[] = ['id','=',$diy_id];
        $d_plugin_material_diy_value = new PluginMaterialDiyValue();
        $diy_value_info = $d_plugin_material_diy_value->getOne($where_diy);

        $d_plugin_material_diy_file = new PluginMaterialDiyFile();
        $where_diy_file = [];
        $where_diy_file[] = ['diy_id','=',$diy_id];
        $where_diy_file[] = ['file_status','<>',4];
        // 总条数
        $count = $d_plugin_material_diy_file->getCount($where_diy_file);
        // 列表
        $list = $d_plugin_material_diy_file->getList($where_diy_file, $page, $page_size);

        // 图片文件的后缀
        $image_exts = $this->image_exts;
        // 图片文件的所有类型
        $image_type = $this->image_type;
        if (empty($list)) {
            $list = array();
        } else {
            foreach ($list as &$val) {
                $val['is_image'] = false;
                if ($val['add_time']>0) {
                    $val['add_time_txt'] = date('Y-m-d H:i:s',$val['add_time']);
                }
                if ($val['last_time']>0) {
                    $val['last_time_txt'] = date('Y-m-d H:i:s',$val['last_time']);
                }
                if ($val['file_url']) {
                    $val['file_url_path'] = replace_file_domain($val['file_url']);
                }
                if (in_array($val['file_type'], $image_type)) {
                    $val['is_image'] = true;
                }
                if (in_array($val['file_suffix'], $image_exts)) {
                    $val['is_image'] = true;
                }
                if ($diy_value_info && $diy_value_info['title']) {
                    $val['title'] = $diy_value_info['title'];
                }
            }
        }

        $data = array();
        $data['count'] = $count;
        $data['total_limit'] = $page_size;
        $data['list'] = $list;
        return $data;
    }

    public function getUserAttachmentList($village_id, $pigcms_id)
    {
        $user = (new HouseVillageUserBind())->getOne([
            ['village_id', '=', $village_id],
            ['pigcms_id', '=', $pigcms_id],
        ], 'pigcms_id,authentication_field');
        if (!$user || $user->isEmpty()) {
            throw new \think\Exception('用户不存在');
        }
        $config_list = $this->getAttachmentConfigListByVillage($village_id);
        $authentication_field = $this->parseAuthenticationField(isset($user['authentication_field']) ? $user['authentication_field'] : '');
        return [
            'bind_id' => intval($user['pigcms_id']),
            'list' => $this->formatAttachmentFieldList($config_list, $authentication_field),
        ];
    }

    public function getRoomAttachmentList($village_id, $vacancy_id)
    {
        $user = $this->getRoomOwnerBind($village_id, $vacancy_id);
        $config_list = $this->getAttachmentConfigListByVillage($village_id);
        $authentication_field = $user ? $this->parseAuthenticationField(isset($user['authentication_field']) ? $user['authentication_field'] : '') : [];
        return [
            'bind_id' => $user ? intval($user['pigcms_id']) : 0,
            'list' => $this->formatAttachmentFieldList($config_list, $authentication_field),
        ];
    }

    public function getDepositAttachmentList($village_id, $vacancy_id)
    {
        $user = $this->getRoomOwnerBind($village_id, $vacancy_id);
        if (!$user) {
            throw new \think\Exception('room owner not found');
        }
        $field = $this->getDepositAttachmentField($village_id, true);
        $authentication_field = $this->parseAuthenticationField(isset($user['authentication_field']) ? $user['authentication_field'] : '');
        return [
            'bind_id' => intval($user['pigcms_id']),
            'group' => $this->formatDepositAttachmentGroup($field, $authentication_field),
        ];
    }

    /**
     * 批量获取房间是否已上传押金附件。
     *
     * @param int $village_id
     * @param array $vacancy_ids
     * @return array<int, int>
     */
    public function getDepositAttachmentStatusMap($village_id, $vacancy_ids)
    {
        $vacancy_ids = array_values(array_unique(array_filter(array_map('intval', (array)$vacancy_ids))));
        if (!$vacancy_ids) {
            return [];
        }
        $status_map = array_fill_keys($vacancy_ids, 0);
        $bind_list = (new HouseVillageUserBind())->getList([
            ['village_id', '=', intval($village_id)],
            ['vacancy_id', 'in', $vacancy_ids],
            ['type', 'in', [0, 3]],
            ['status', '<>', 4],
        ], 'pigcms_id,vacancy_id,authentication_field', 'pigcms_id desc');
        if (!$bind_list || $bind_list->isEmpty()) {
            return $status_map;
        }
        $handled_room_ids = [];
        foreach ($bind_list->toArray() as $bind) {
            $room_id = intval($bind['vacancy_id'] ?? 0);
            if ($room_id <= 0 || isset($handled_room_ids[$room_id])) {
                continue;
            }
            $handled_room_ids[$room_id] = true;
            $authentication_field = $this->parseAuthenticationField($bind['authentication_field'] ?? '');
            $field_value = isset($authentication_field['deposit_attachment']) && is_array($authentication_field['deposit_attachment'])
                ? $authentication_field['deposit_attachment']
                : [];
            $file_list = !empty($field_value['file_list']) && is_array($field_value['file_list'])
                ? $field_value['file_list']
                : (!empty($field_value['files']) && is_array($field_value['files']) ? $field_value['files'] : []);
            $status_map[$room_id] = $this->formatAttachmentFiles($file_list) ? 1 : 0;
        }
        return $status_map;
    }

    public function saveDepositAttachmentList($village_id, $vacancy_id, $files)
    {
        $user = $this->getRoomOwnerBind($village_id, $vacancy_id);
        if (!$user) {
            throw new \think\Exception('room owner not found');
        }
        if (is_string($files)) {
            $files = json_decode($files, true);
        }
        if (!is_array($files)) {
            $files = [];
        }
        $field = $this->getDepositAttachmentField($village_id, true);
        $save_files = $this->formatAttachmentFiles($files);
        $allow_exts = isset($this->attachment_type_config[intval($field['type'])]['allow_exts']) ? $this->attachment_type_config[intval($field['type'])]['allow_exts'] : [];
        foreach ($save_files as $save_file) {
            if ($allow_exts && !in_array(strtolower($save_file['suffix']), $allow_exts)) {
                throw new \think\Exception('attachment type error');
            }
        }
        $authentication_field = $this->parseAuthenticationField(isset($user['authentication_field']) ? $user['authentication_field'] : '');
        $file_name_list = [];
        foreach ($save_files as $file_item) {
            $file_name_list[] = $file_item['original_name'];
        }
        if ($save_files) {
            $authentication_field['deposit_attachment'] = [
                'type' => intval($field['type']),
                'is_must' => isset($field['is_must']) ? intval($field['is_must']) : 0,
                'key' => 'deposit_attachment',
                'title' => isset($field['title']) ? $field['title'] : '押金附件',
                'value' => implode(', ', $file_name_list),
                'file_list' => $save_files,
            ];
        } else {
            unset($authentication_field['deposit_attachment']);
        }
        (new HouseVillageUserBind())->saveOne(['pigcms_id' => $user['pigcms_id'], 'village_id' => $village_id], [
            'authentication_field' => serialize($authentication_field),
        ]);
        return [
            'bind_id' => intval($user['pigcms_id']),
            'group' => $this->formatDepositAttachmentGroup($field, $authentication_field),
        ];
    }
    public function saveRoomAttachmentList($village_id, $vacancy_id, $files)
    {
        $user = $this->getRoomOwnerBind($village_id, $vacancy_id);
        if (!$user) {
            throw new \think\Exception('room owner not found');
        }
        $groups = $files;
        if (is_string($groups)) {
            $groups = json_decode($groups, true);
        }
        if (!is_array($groups)) {
            $groups = [];
        }
        $group_map = [];
        foreach ($groups as $group) {
            if (!is_array($group) || empty($group['key'])) {
                continue;
            }
            $group_map[trim($group['key'])] = isset($group['files']) && is_array($group['files']) ? $group['files'] : [];
        }
        $config_list = $this->getAttachmentConfigListByVillage($village_id);
        $authentication_field = $this->parseAuthenticationField(isset($user['authentication_field']) ? $user['authentication_field'] : '');
        foreach ($config_list as $field) {
            $field_key = isset($field['key']) ? trim($field['key']) : '';
            if (!$field_key || !array_key_exists($field_key, $group_map)) {
                continue;
            }
            $save_files = $this->formatAttachmentFiles($group_map[$field_key]);
            $allow_exts = isset($this->attachment_type_config[intval($field['type'])]['allow_exts']) ? $this->attachment_type_config[intval($field['type'])]['allow_exts'] : [];
            if ($allow_exts && $save_files) {
                foreach ($save_files as $save_file) {
                    if (!in_array(strtolower($save_file['suffix']), $allow_exts)) {
                        throw new \think\Exception('attachment type error');
                    }
                }
            }
            if (!$save_files) {
                unset($authentication_field[$field_key]);
                continue;
            }
            $file_name_list = [];
            foreach ($save_files as $file_item) {
                $file_name_list[] = $file_item['original_name'];
            }
            $authentication_field[$field_key] = [
                'type' => isset($field['type']) ? intval($field['type']) : 0,
                'is_must' => isset($field['is_must']) ? intval($field['is_must']) : 0,
                'key' => $field_key,
                'title' => isset($field['title']) ? $field['title'] : '',
                'value' => implode(', ', $file_name_list),
                'file_list' => $save_files,
            ];
        }
        (new HouseVillageUserBind())->saveOne(['pigcms_id' => $user['pigcms_id'], 'village_id' => $village_id], [
            'authentication_field' => serialize($authentication_field),
        ]);
        return [
            'bind_id' => intval($user['pigcms_id']),
            'list' => $this->formatAttachmentFieldList($config_list, $authentication_field),
        ];
    }

    private function getDepositAttachmentField($village_id, $create = false)
    {
        $village = (new HouseVillageService())->getHouseVillageInfo([
            ['village_id', '=', $village_id],
        ], 'property_id');
        $property_id = isset($village['property_id']) ? intval($village['property_id']) : 0;
        if ($property_id <= 0) {
            throw new \think\Exception('property not found');
        }
        $where = [
            ['village_id', '=', 0],
            ['property_id', '=', $property_id],
            ['key', '=', 'deposit_attachment'],
        ];
        $field = Db::name('house_village_data_config')->where($where)->find();
        if ($field && (intval($field['type']) !== 7 || intval($field['is_close']) !== 0)) {
            Db::name('house_village_data_config')->where($where)->update([
                'title' => '押金附件',
                'type' => 7,
                'is_close' => 0,
            ]);
            $field['title'] = '押金附件';
            $field['type'] = 7;
            $field['is_close'] = 0;
        }
        if (!$field && $create) {
            $data = [
                'village_id' => 0,
                'property_id' => $property_id,
                'title' => '押金附件',
                'type' => 7,
                'is_must' => 0,
                'is_system' => 0,
                'key' => 'deposit_attachment',
                'use_field' => '',
                'is_close' => 0,
                'sort' => 0,
            ];
            $id = Db::name('house_village_data_config')->insertGetId($data);
            $field = Db::name('house_village_data_config')->where('acid', $id)->find();
            if (!$field) {
                $field = $data;
            }
        }
        if (!$field) {
            $field = [
                'key' => 'deposit_attachment',
                'title' => '押金附件',
                'type' => 7,
                'is_must' => 0,
            ];
        }
        return $field;
    }

    private function formatDepositAttachmentGroup($field, $authentication_field)
    {
        $type = isset($field['type']) ? intval($field['type']) : 7;
        $field_value = isset($authentication_field['deposit_attachment']) && is_array($authentication_field['deposit_attachment']) ? $authentication_field['deposit_attachment'] : [];
        $file_list = [];
        if (!empty($field_value['file_list']) && is_array($field_value['file_list'])) {
            $file_list = $field_value['file_list'];
        } elseif (!empty($field_value['files']) && is_array($field_value['files'])) {
            $file_list = $field_value['files'];
        }
        if (empty($this->attachment_type_config[$type])) {
            $type = 7;
        }
        return [
            'key' => 'deposit_attachment',
            'title' => isset($field['title']) ? $field['title'] : '押金附件',
            'type' => $type,
            'type_name' => $this->attachment_type_config[$type]['name'],
            'accept' => isset($this->attachment_type_config[$type]['accept']) ? $this->attachment_type_config[$type]['accept'] : '',
            'allow_exts' => $this->attachment_type_config[$type]['allow_exts'],
            'allow_exts_text' => implode(',', $this->attachment_type_config[$type]['allow_exts']),
            'files' => $this->formatAttachmentFiles($file_list),
        ];
    }
    private function getRoomOwnerBind($village_id, $vacancy_id)
    {
        $room = (new HouseVillageUserVacancy())->getOne([
            ['village_id', '=', $village_id],
            ['pigcms_id', '=', $vacancy_id],
            ['is_del', '=', 0],
        ], 'pigcms_id');
        if (!$room || $room->isEmpty()) {
            throw new \think\Exception('room not found');
        }
        $user = (new HouseVillageUserBind())->getOne([
            ['village_id', '=', $village_id],
            ['vacancy_id', '=', $vacancy_id],
            ['type', 'in', [0, 3]],
            ['status', '<>', 4],
        ], 'pigcms_id,authentication_field', 'pigcms_id desc');
        if (!$user || $user->isEmpty()) {
            return [];
        }
        return $user->toArray();
    }

    private function getAttachmentConfigListByVillage($village_id)
    {
        $village = (new HouseVillageService())->getHouseVillageInfo([
            ['village_id', '=', $village_id],
        ], 'property_id');
        $property_id = isset($village['property_id']) ? intval($village['property_id']) : 0;
        if ($property_id <= 0) {
            return [];
        }
        $config_list = (new HouseVillageDataConfig())->getList([
            ['village_id', '=', 0],
            ['property_id', '=', $property_id],
            ['is_close', '=', 0],
            ['type', 'in', array_keys($this->attachment_type_config)],
        ], true, 'sort desc');
        return ($config_list && !$config_list->isEmpty()) ? $config_list->toArray() : [];
    }

    private function parseAuthenticationField($authentication_field)
    {
        if (!$authentication_field) {
            return [];
        }
        if (is_array($authentication_field)) {
            return $authentication_field;
        }
        return @unserialize($authentication_field) ? unserialize($authentication_field) : [];
    }

    private function formatAttachmentFieldList($config_list, $authentication_field = [])
    {
        $list = [];
        if (!is_array($config_list)) {
            return $list;
        }
        if (!is_array($authentication_field)) {
            $authentication_field = [];
        }
        foreach ($config_list as $field) {
            $key = isset($field['key']) ? trim($field['key']) : '';
            $type = isset($field['type']) ? intval($field['type']) : 0;
            if (!$key || empty($this->attachment_type_config[$type])) {
                continue;
            }
            $field_value = isset($authentication_field[$key]) && is_array($authentication_field[$key]) ? $authentication_field[$key] : [];
            $file_list = [];
            if (!empty($field_value['file_list']) && is_array($field_value['file_list'])) {
                $file_list = $field_value['file_list'];
            } elseif (!empty($field_value['files']) && is_array($field_value['files'])) {
                $file_list = $field_value['files'];
            }
            $list[] = [
                'key' => $key,
                'title' => isset($field['title']) ? $field['title'] : '',
                'type' => $type,
                'type_name' => $this->attachment_type_config[$type]['name'],
                'accept' => isset($this->attachment_type_config[$type]['accept']) ? $this->attachment_type_config[$type]['accept'] : '',
                'allow_exts' => $this->attachment_type_config[$type]['allow_exts'],
                'allow_exts_text' => implode(',', $this->attachment_type_config[$type]['allow_exts']),
                'files' => $this->formatAttachmentFiles($file_list),
            ];
        }
        return $list;
    }

    public function formatRoomAttachmentFiles($room_imgs)
    {
        if (!$room_imgs) {
            return [];
        }
        $files = is_array($room_imgs) ? $room_imgs : json_decode($room_imgs, true);
        if (!is_array($files)) {
            return [];
        }
        $file_list = [];
        foreach ($files as $file) {
            if (is_string($file)) {
                $file_list[] = ['file_url' => $file];
            } elseif (is_array($file)) {
                $file_list[] = $file;
            }
        }
        return $this->formatAttachmentFiles($file_list);
    }

    public function formatAttachmentFiles($file_list)
    {
        $format_files = [];
        if (!is_array($file_list)) {
            return $format_files;
        }
        foreach ($file_list as $file_item) {
            if (!is_array($file_item)) {
                continue;
            }
            $file_url = isset($file_item['file_url']) ? trim($file_item['file_url']) : (isset($file_item['url']) ? trim($file_item['url']) : '');
            if (!$file_url) {
                continue;
            }
            $file_url = $this->normalizeAttachmentPath($file_url);
            $original_name = isset($file_item['original_name']) && $file_item['original_name'] ? $file_item['original_name'] : (isset($file_item['name']) && $file_item['name'] ? $file_item['name'] : basename($file_url));
            $suffix = isset($file_item['suffix']) && $file_item['suffix'] ? strtolower($file_item['suffix']) : strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
            $format_files[] = [
                'uid' => md5($file_url),
                'file_url' => $file_url,
                'file_url_full' => replace_file_domain($file_url),
                'url' => replace_file_domain($file_url),
                'original_name' => $original_name,
                'name' => $original_name,
                'file_name' => isset($file_item['file_name']) && $file_item['file_name'] ? $file_item['file_name'] : basename($file_url),
                'size' => isset($file_item['size']) ? intval($file_item['size']) : 0,
                'suffix' => $suffix,
                'is_image' => in_array($suffix, ['jpg', 'jpeg', 'png', 'gif', 'bmp']),
                'add_time' => isset($file_item['add_time']) ? intval($file_item['add_time']) : 0,
                'status' => 'done',
            ];
        }
        return $format_files;
    }

    private function normalizeAttachmentPath($file_url)
    {
        $file_url = trim($file_url);
        $site_url = rtrim(cfg('site_url'), '/');
        if ($site_url && strpos($file_url, $site_url) === 0) {
            $file_url = substr($file_url, strlen($site_url));
        }
        $upload_pos = strpos($file_url, '/upload/');
        if ($upload_pos !== false) {
            $file_url = substr($file_url, $upload_pos);
        }
        return $file_url;
    }

    /**
     * 修改数据 软删除
     * @param $file_id
     */
    public function delWriteFileList($file_id){
        $d_plugin_material_diy_file = new PluginMaterialDiyFile();
        $where_diy_file = [];
        $where_diy_file[] = ['file_id','=',$file_id];
        $data = [
            'file_status' => 4,
            'last_time' => time()
        ];
        return $d_plugin_material_diy_file->updatePluginMaterialDiyFile($where_diy_file,$data);
    }

    /**
     * 插入数据
     * @param $data
     * @return int|string
     */
    public function addWriteFile($data){
        $d_plugin_material_diy_file = new PluginMaterialDiyFile();
        return $d_plugin_material_diy_file->addPluginMaterialDiyFile($data);
    }

    /**
     * 装饰申请单备注列表
     * @param $diy_id
     * @param $page
     * @param $page_size
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRemarkList($diy_id, $page, $page_size){
        $where_diy = [];
        $where_diy[] = ['id','=',$diy_id];
        // 对应信息，查看是否存在
        $d_plugin_material_diy_value = new PluginMaterialDiyValue();
        $info = $d_plugin_material_diy_value->getOne($where_diy);
        if (empty($info)) {
            return ['msg' => '参数错误'];
        }
        $whereRemark = [];
        $whereRemark[] = ['diy_id','=',$diy_id];
        $plugin_material_diy_remark = new PluginMaterialDiyRemark();
        // 总数
        $count = $plugin_material_diy_remark->getRemarkCount($whereRemark);
        // 列表
        $list = $plugin_material_diy_remark->getRemarkList($whereRemark, $page, $page_size);
        foreach ($list as &$value){
            $value['add_time_text'] = date('Y-m-d H:i:s',$value['add_time']);
        }
        $data = [
            'list' => $list,
            'count' => $count,
            'total_limit' => $page_size
        ];
        return $data;
    }

    /**
     * 编辑及新增
     * @param $param
     * @param string $type
     * @return bool|int|string|string[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addRemark($param, $type = 'add'){
        $where_diy = [];
        $where_diy[] = ['id','=',$param['diy_id']];
        // 对应信息，查看是否存在
        $d_plugin_material_diy_value = new PluginMaterialDiyValue();
        $info = $d_plugin_material_diy_value->getOne($where_diy);
        if (empty($info)) {
            return ['msg' => '参数错误'];
        }
        $plugin_material_diy_remark = new PluginMaterialDiyRemark();
        $data = [
            'remark' => $param['remark_value']
        ];
        if($type == 'add'){
            // 插入
            $data['template_id'] = $info['template_id'];
            $data['diy_id'] = $param['diy_id'];
            $data['account'] = $param['account'];
            $data['add_time'] = time();
            return $plugin_material_diy_remark->addRemark($data);
        }else{
            // 编辑保存
            $where = [];
            $where[] = ['remark_id','=',$param['remark_id']];
            return $plugin_material_diy_remark->saveRemark($where,$data);
        }
    }

    /**
     * 删除备注
     * @param $remark_id
     * @return bool
     * @throws \Exception
     */
    public function delRemark($remark_id){
        if(empty($remark_id)){
            return false;
        }
        $plugin_material_diy_remark = new PluginMaterialDiyRemark();
        return $plugin_material_diy_remark->delRemark($remark_id);
    }

    /**
     * 获取快递详情
     * @param $id
     * @return array[]
     */
    public function getExpressInfo($id){
        $houseVillageExpress = new HouseVillageExpress();
        $user_bind = new HouseVillageUserBind();

        $where = [];
        $where[] = ['p.id','=',$id];
        $field = 'p.*,e.name as express_name,o.send_time,o.status as o_status';
        // 获取快递信息
        $info = $houseVillageExpress->getList($where,$field);

        $where_user = [];
        $where_user[] = ['uid','=',$info[0]['uid']];
        $where_user[] = ['village_id','=',$info[0]['village_id']];
        if(!empty($info[0]['floor_id'])){
            $where_user[] = ['floor_id','=',$info[0]['floor_id']];
        }
        // 小区用户绑定信息
        $user_bind_info = $user_bind->getOne($where_user,'address');

        $village_id = $info[0]['village_id'] ?? 0;
        $houseName = (new HouseVillageService())->getHouseAlias('floor_name',$village_id)?:'单元';
        $data = [
            ['title' => '快递类型', 'value' => $info[0]['express_name']],
            ['title' => '快递单号', 'value' => $info[0]['express_no']],
            ['title' => '收件人手机号', 'value' => $info[0]['phone']],
            ['title' => '状态', 'value' => $this->express_goods_type[$info[0]['status']]],
            ['title' => '业主'.$houseName, 'value' => $user_bind_info['address']],
            ['title' => '取件码', 'value' => $info[0]['fetch_code']],
            ['title' => '预约代送时间', 'value' => !empty($info[0]['send_time']) ? date("Y-m-d H:i:s",$info[0]['send_time']) : ''],
            ['title' => '取件时间', 'value' => !empty($info[0]['delivery_time']) ? date("Y-m-d H:i:s",$info[0]['delivery_time']) : ''],
            ['title' => '代送费用', 'value' => $info[0]['collect_money']],
            ['title' => '代送状态', 'value' => $this->express_status[$info[0]['o_status']]],
            ['title' => '备注', 'value' => $info[0]['memo']],
        ];
        return $data;
    }

    /**
     * 获取群信息
     * @param $chat_id
     * @param $property_id
     * @return array|bool|mixed|string
     */
    public function getChatGroupInfo($chat_id,$property_id,$type){
        // 内外部群资料信息
        $audit_info_group = new WorkMsgAuditInfoGroup();
        $where_group = [];
        $where_group[] = ['group_id','=',$chat_id];
        $field = 'roomname as name,room_create_time as create_time,notice,member_userid,member_external_id,creator,admin_list';
        $audit_info = $audit_info_group->getFind($where_group,$field);
        $data = [];
        if(!empty($audit_info)){
            if($type == 'info'){
                $enterpriseWeChat = new EnterpriseWeChatService();
                // 获取企微群信息
                $data = $enterpriseWeChat->groupChat($property_id,$chat_id,2);
                if(empty($data) && !$audit_info->isEmpty()){
                    $data = $audit_info->toArray();
                }
                $data['create_time'] = date("Y-m-d H:i:s",$data['create_time']);
                // 统计信息
                $member_userid_arr = explode(',',$audit_info['member_userid']);
                $member_external_arr = explode(',',$audit_info['member_external_id']);
                $data['statistics'] = [
                    ['title' => '当前群成员', 'value' => (int)(count($member_userid_arr) + count($member_external_arr))],
                    ['title' => '今日入群', 'value' => 3],
                    ['title' => '今日退群', 'value' => 0],
                    ['title' => '今日活跃', 'value' => (int)(count($member_userid_arr) + count($member_external_arr))],
                ];
            }else{
                // 管理人员ID
                $worker_arr = array_merge([$audit_info['creator']],explode(',',$audit_info['admin_list']));
                $dbHouseWorker = new HouseWorker();
                $map[] = ['qy_id','in',$worker_arr];
                $user_work = $dbHouseWorker->getAll($map,'wid,name as nickname,qy_id');
                if($user_work && !$user_work->isEmpty()){
                    $data = $user_work->toArray();
                }
            }
        }
        return $data;
    }

    /**
     * 业主转交  分配在职成员的客户
     * @param $handover_userid
     * @param $takeover_userid
     * @param $external_userid
     */
    public function transferCustomer($property_id,$handover_userid,$takeover_userid,$external_userid){
        $enterpriseWeChat = new EnterpriseWeChatService();
        $data = [];
        $data['handover_userid'] = $handover_userid;
        $data['takeover_userid'] = $takeover_userid;
        $data['external_userid'][] = $external_userid;
        // 分配在职成员的客户
        return $enterpriseWeChat->transferCustomer($property_id,$data);
    }

    /**
     * 通过cropid 和 agentID获取物业ID
     * @param $cropId
     * @param $agentId
     * @return int|mixed
     */
    public function getPropertyInfo($cropId,$agentId){
        $whereEnterpriseWxBind = [];
        $whereEnterpriseWxBind[] = ['corpid','=',$cropId];
        $house_enterprise_wx = new HouseEnterpriseWxBind();
        // 通过企业授权信息 获取物业ID
        $qywxBind = $house_enterprise_wx->getOne($whereEnterpriseWxBind,'bind_id');

        $dbVillageQywxAgent = new VillageQywxAgent();
        $where = [];
        $where[] = ['type', '=', 0];
        $where[] = ['agentid', '=', $agentId];
        $where[] = ['secret', '<>', ''];
        $where[] = ['is_close', '=', 0];
        // 通过agentid获取 物业ID
        $agent_list = $dbVillageQywxAgent->getOne($where,'property_id');
        if($qywxBind['bind_id'] != $agent_list['property_id']){
            return 0;
        }else{
            return $qywxBind['bind_id'];
        }
    }

    /**
     * 工单列表
     * @param $bind_id
     * @param bool $field
     * @param $page
     * @param $limit
     * @param $order
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function workersOrderList($bind_id,$field = true,$page,$limit,$order){
        $data = [];
        $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
        $db_house_new_repair_works_order_log = new HouseNewRepairWorksOrderLog();
        $where = [];
        $where[] = ['bind_id','=',$bind_id];
        // 工单列表
        $dataArr = [];
        $listCount = $db_house_new_repair_works_order->getCount($where);
        $work_list = $db_house_new_repair_works_order->getList($where,$field,$page,$limit,$order);
        if (!$work_list->isEmpty()) {
            $work_list = $work_list->toArray();
            foreach ($work_list as $key => &$val) {

                // 获取工单对应处理记录 按照时间顺序倒
                $where_log = [];
                $where_log[] = ['order_id','=',$val['order_id']];
                $log_new = $db_house_new_repair_works_order_log->getOne($where_log, 'log_content,add_time');
                $val['add_time'] = $log_new['add_time'];
                $val['log_content'] = !empty($log_new['log_content']) ? $log_new['log_content'] : '无';

                $val['add_time_txt'] = date('Y-m-d H:i:s',$val['add_time']);
                $val['order_add_time_txt'] = date('Y-m-d H:i:s',$val['order_add_time']);
                //工单 状态
                $status_msg = $this->order_work_status_txt[$val['event_status']];
                $status_color = $this->order_status_color[$val['event_status']];

                if(empty($val['order_content'])){
                    $val['order_content'] = '无';
                }

                $dataArr[$key]['order_id'] = $val['order_id'];
                $dataArr[$key]['time'] = $val['order_add_time_txt'];
                $dataArr[$key]['status_msg'] = $status_msg;
                $dataArr[$key]['status'] = $val['event_status'];
                $dataArr[$key]['status_color'] = $status_color;
                $dataArr[$key]['phone'] = $val['phone'];
                $dataArr[$key]['list'] = [
                    ['title'=>'报修人员','type'=>1,'content'=>$val['name']],
                    ['title'=>'报修内容','type'=>1,'content'=>$val['order_content']],
                    ['title'=>'报修地址','type'=>1,'content'=>$val['address_txt']],
                ];
                if(($val['event_status'] >= 20 && $val['event_status'] < 50) || ($val['event_status'] >= 60 && $val['event_status'] < 70)) {
                    $dataArr[$key]['list'][] = ['title' => '处理意见', 'type' => 1, 'content' => $val['log_content']];
                    $dataArr[$key]['list'][] = ['title' => '处理时间', 'type' => 1, 'content' => $val['add_time_txt']];
                }
                if($val['event_status'] >= 70){
                    $dataArr[$key]['list'][] = ['title' => '评价内容','type'=>1, 'content' => $val['log_content']];
                    $dataArr[$key]['list'][] = ['title' => '评价时间','type'=>1, 'content' => $val['add_time_txt']];
                }
            }
        } else {
            $dataArr = [];
        }
        $data['count'] = $listCount;
        $data['list'] = $dataArr;
        return $data;
    }

    /**
     * 获取物业下用户小区及房间列表
     * @param $uid
     * @param $property_id
     * @param $field
     * @param $page
     * @param $limit
     * @return array
     */
    public function getVillageBindList($uid,$property_id,$field,$page,$limit){
        $user_bind = new HouseVillageUserBind();
        $village_list = new HouseVillage();

        $single = true;

        $where = [];
        $where[] = ['v.property_id','=',$property_id];
        $where[] = ['b.uid','=',$uid];
        $list = $village_list->getVillageBindList($where,$field,$page,$limit,'v.village_id DESC');
        if(!empty($list['list'])){
            if($list['count'] > 1){
                $single = false;
            }
            foreach ($list['list'] as $key => &$value){
                // 查询小区下的房间
                $where = [];
                $where[] = ['h.village_id','=',$value['village_id']];
                $where[] = ['h.uid','=',$uid];
                $user_bind_list = $user_bind->getPageList($where,'h.pigcms_id,a.room,b.single_name,c.floor_name',0,'h.pigcms_id DESC');
                if($user_bind_list && !$user_bind_list->isEmpty()){
                    if(count($user_bind_list->toArray()) > 1){
                        $single = false;
                    }
                    $value['room_list'] = $user_bind_list->toArray();
                }else{
                    // 用户在小区没有房子 防止出现查询错误bug，导致查询到没有房间的小区则删除
                    unset($list['list'][$key]);
                }
            }
            $list['list'] = array_values($list['list']);
            $list['single'] = $single;
        }
        return $list;
    }

    /**
     * 获取用户信息
     * @param $where
     * @return array
     */
    public function saveVillageRoomInfo($where){
        $user_bind = new HouseVillageUserBind();

        // 查询用户绑定小区信息是否正确
        $info = $user_bind->getOne($where,'pigcms_id,village_id');
        if($info && !$info->isEmpty()){
            return $info->toArray();
        }else{
            return [];
        }
    }

    /**
     * 聊天左侧员工或者群列表
     * @param $uid
     * @param $type
     * @param $village_id
     * @return array|string[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getChatLeftList($uid,$type,$village_id){
        if(empty($type) || empty($uid) || empty($village_id)){
            return ['msg' => '缺少参数'];
        }
        $house_contact_way_user = new HouseContactWayUser();
        $where = [];
        $where[] = ['g.uid','=',$uid];
        $where[] = ['g.status','=',1];
        $where[] = ['w.village_id','=',$village_id];
        // 查询用户在小区下的列表
        $userList = $house_contact_way_user->getAllVillageContact($where,'UserID,customer_id');
        $UserIdArr = [];
        foreach ($userList as $v){
            if(!isset($UserIdArr[$v['UserID']])){
                $UserIdArr[$v['UserID']] = $v['customer_id'];
            }
        }
        $data = [];
        if(!empty($userList)){
            // 单聊
            if($type == 'single'){
                // 获取聊天的员工列表
                $workUserIdArr = array_unique(array_column($userList,'UserID'));
                $house_worker = new HouseWorker();
                $where = [];
                $where[] = ['qy_id','in',$workUserIdArr];
                $where[] = ['village_id','=',$village_id];
                // 获取员工列表
                $list = $house_worker->getAll($where,'wid as id,name,avatar,qy_id');
                if($list && !$list->isEmpty()){
                    foreach ($list->toArray() as $value){
                        $value['customer_id'] = $UserIdArr[$value['qy_id']];
                        unset($value['qy_id']);
                        $data[] = $value;
                    }
                }
            }else{
                // 群聊
                $UserIdArr = array_unique(array_column($userList,'customer_id'));
                foreach ($UserIdArr as $va){
                    $work_group = new WorkMsgAuditInfoGroup();
                    // 获取用户加入的群聊
                    $where = [];
                    $where[] = ['','exp',Db::raw("FIND_IN_SET($va,member_external_id)")];
                    $list = $work_group->getList($where,'id,roomname,group_type,group_id');
                    if($list && !$list->isEmpty()){
                        $list = $list->toArray();
                        foreach ($list as $val){
                            // 去重
                            $data[$val['group_id']] = [
                                'id' => $val['id'],
                                'name' => $val['roomname'],
                                'avatar' => $val['group_type'],
                                'customer_id' => $va
                            ];
                        }
                    }
                }
            }
        }
        $return = [];
        $return['list'] = array_values($data);
        return $return;
    }

}
