<?php


namespace app\community\model\service;

use app\common\model\service\plan\PlanService;
use app\community\model\db\VillageQywxMessage;
use app\community\model\db\VillageQywxCodeLabelGroup;
use app\community\model\db\VillageQywxCodeLabel;
use app\community\model\db\HouseWorker;
use app\community\model\db\VillageQywxMessageDetail;
use app\community\model\db\VillageQywxMessageRecord;
use app\community\model\db\HouseVillageDataConfig;
use app\community\model\db\Country;
use app\community\model\db\HouseContactWayUser;
use app\community\model\db\HouseEnterpriseWxBind;
use app\community\model\service\workweixin\WorkWeiXinNewService;
use app\community\model\service\workweixin\WorkWeiXinRequestService;
use app\traits\WorkWeiXinToJobTraits;
use file_handle\FileHandle;
use net\Http as Http;

class VillageQywxMessageService
{
    use WorkWeiXinToJobTraits;
    protected $db_village_qywx_message = '';
    protected $db_village_qywx_code_label_group = '';
    protected $db_village_qywx_code_label = '';
    protected $db_house_worker = '';
    protected $db_village_qywx_message_detail = '';
    protected $db_house_contact_way_user = '';
    protected $db_village_qywx_message_record = '';
    protected $db_house_village_data_config = '';
    protected $db_country = '';
    protected $db_house_enterprise_wx_bind = '';

    public function __construct()
    {
        $this->db_village_qywx_message = new VillageQywxMessage();
        $this->db_village_qywx_code_label = new VillageQywxCodeLabel();
        $this->db_village_qywx_code_label_group = new VillageQywxCodeLabelGroup();
        $this->db_house_worker = new HouseWorker();
        $this->db_village_qywx_message_detail = new VillageQywxMessageDetail();
        $this->db_house_contact_way_user = new HouseContactWayUser();
        $this->db_village_qywx_message_record = new VillageQywxMessageRecord();
        $this->db_house_village_data_config = new HouseVillageDataConfig();
        $this->db_country = new Country();
        $this->db_house_enterprise_wx_bind = new HouseEnterpriseWxBind();
    }

    /**
     * 企业微信群发详情
     * @author lijie
     * @date_time 2021/03/12
     * @param $where
     * @param bool $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getQywxMessageInfo($where,$field=true)
    {
        $data = $this->db_village_qywx_message->getOne($where,$field);
        return $data;
    }

    /**
     * 成员确认
     * @author lijie
     * @date_time 2021/03/22
     * @param array $where
     * @param $message_id
     * @param int $page
     * @param int $limit
     * @param bool $field
     * @return array
     */
    public function getStaffConfirm($where=[],$message_id,$page=1,$limit=10,$field=true)
    {
        $sender_arr = $this->db_village_qywx_message_record->getListByUserId($where,$page,$limit,$field,'user_id');
        $staff_arr = [];
        if($sender_arr){
            foreach ($sender_arr as $k=>$v){
                $worker_info = $this->db_house_worker->get_one(['qy_id'=>$v['user_id']],'avatar,name');
                $staff_arr[$k]['avatar'] = $worker_info['avatar'];
                $staff_arr[$k]['name'] = $worker_info['name'];
                $staff_arr[$k]['send_reality_count'] = $this->db_village_qywx_message_record->getCount(['user_id'=>$v['user_id'],'status'=>1,'message_id'=>$message_id]);
                $staff_arr[$k]['send_total_count'] = $this->db_village_qywx_message_record->getCount(['user_id'=>$v['user_id'],'message_id'=>$message_id]);
                if($staff_arr[$k]['send_total_count']==$staff_arr[$k]['send_reality_count'])
                    $staff_arr[$k]['status'] = '已发送';
                else
                    $staff_arr[$k]['status'] = '发送中';
                $info = $this->db_village_qywx_message_record->getOne(['user_id'=>$v['user_id'],'message_id'=>$message_id,'status'=>1]);
                if($info)
                    $staff_arr[$k]['send_time'] = date('Y-m-d H:i:s',$info['send_time']);
                else
                    $staff_arr[$k]['status'] = '未发送';
            }
        }
        return $staff_arr;
    }

    /**
     * 获取数量
     * @param $where
     * @param int $page
     * @param int $limit
     * @param bool $field
     * @return int
     */
    public function getStaffConfirmCount($where=[],$page=0,$limit=10,$field=true)
    {
        $sender_arr = $this->db_village_qywx_message_record->getListByUserId($where,$page,$limit,$field,'user_id');
        return count($sender_arr);
    }

    /**
     * 企业微信群发列表
     * @author lijie
     * @date_time 2021/03/12
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getQywxMessageList($where,$field=true,$page=1,$limit=10,$order='id DESC')
    {
        $data = $this->db_village_qywx_message->getList($where,$field,$page,$limit,$order);
        if($data){
            $nowTime = time();
            foreach ($data as $k=>$v){
                $data[$k]['worker'] = $this->db_house_worker->getAll([['qy_id','in',$v['sender']]],'name');
                $data[$k]['send_time_txt'] = $v['send_time'] == 1 ? date('Y-m-d H:i:s', $nowTime) : date('Y-m-d H:i:s', $v['send_time']);
                switch ($v['send_type']){
                    case 1:
                        $data[$k]['send_type_txt'] = '业主';
                        break;
                    case 2:
                        $data[$k]['send_type_txt'] = '业主群';
                        break;
                    case 3:
                        $data[$k]['send_type_txt'] = '企业成员';
                        break;
                    default:
                        $data[$k]['send_type_txt'] = '业主';
                }
                switch ($v['send_status']){
                    case 1:
                        $data[$k]['send_status_txt'] = '发送成功';
                        break;
                    case 2:
                        $data[$k]['send_status_txt'] = '发送中';
                        break;
                    case 3:
                        $send_status_txt = '未发送';
                        if (isset($v['send_time']) && intval($v['send_time']) > 100) {
                            $send_status_txt .= '(定时在'.date('Y-m-d H:i:s',$v['send_time']).')';
                        }
                        $data[$k]['send_status_txt'] = $send_status_txt;
                        $data[$k]['send_time_txt'] = '--';
                        break;
                    case 4:
                        $data[$k]['send_status_txt'] = '发送失败';
                        break;
                    default:
                        $data[$k]['send_status_txt'] = '发送成功';
                }
            }
        }
        return $data;
    }

    /**
     * 修改企业微信群发
     * @author lijie
     * @date_time 2021/03/12
     * @param $where
     * @param $data
     * @return bool
     */
    public function saveQywxMessage($where,$data)
    {
        $res = $this->db_village_qywx_message->saveOne($where,$data);
        return $res;
    }

    /**
     * 添加企业微信群发
     * @author lijie
     * @date_time 2021/03/12
     * @param $data
     * @return int|string
     */
    public function addQywxMessage($data)
    {
        $id = $this->db_village_qywx_message->addOne($data);
        return $id;
    }

    /**
     * 删除企业微信群发
     * @author lijie
     * @date_time 2021/03/12
     * @param $where
     * @return bool
     * @throws \Exception
     */
    public function delQywxMessage($where)
    {
        $res = $this->db_village_qywx_message->delOne($where);
        return $res;
    }

    /**
     * 企业微信群发数量
     * @author lijie
     * @date_time 2021/03/12
     * @param $where
     * @return int
     */
    public function getQywxMessageCount($where)
    {
        $count = $this->db_village_qywx_message->getCount($where);
        return $count;
    }

    /**
     * 获取标签列表
     * @author lijie
     * @date_time 2021/03/15
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getQywxCodeLabel($where,$field=true,$page=1,$limit=10,$order='add_time ASC')
    {
        $data = $this->db_village_qywx_code_label_group->getList($where,$field,$page,$limit,$order);
        if($data){
            foreach ($data as $k=>$v){
                $data[$k]['type'] = 1;
                $data[$k]['label_lists'] = $this->db_village_qywx_code_label->getList(['label_group_id'=>$v['label_group_id']],'label_id,label_name',0)->toArray();
                /*$res = $data[$k]['label_lists'];
                $res[] = ['label_id'=>0,'label_name'=>'＋ 新建标签'];
                $data[$k]['label_lists'] = $res;*/
            }
        }
        return $data;
    }

    /**
     * 获取标签组详情
     * @author lijie
     * @date_time 2021/03/18
     * @param $where
     * @param bool $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLabelGroupInfo($where,$field=true)
    {
        $data = $this->db_village_qywx_code_label_group->getOne($where,$field);
        return $data;
    }

    /**
     * 标签信息
     * @author lijie
     * @date_time 2021/03/23
     * @param array $where
     * @param bool $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLabelInfo($where=[],$field=true)
    {
        $data = $this->db_village_qywx_code_label->getOne($where,$field);
        return $data;
    }

    /**
     * 添加标签组
     * @author lijie
     * @date_time 2021/03/18
     * @param $data
     * @return int|string
     */
    public function addLabelGroup($data)
    {
        $res = $this->db_village_qywx_code_label_group->addOne($data);
        return $res;
    }

    /**
     * 添加标签
     * @author lijie
     * @date_time 2021/03/18
     * @param $data
     * @return int|string
     */
    public function addLabel($data)
    {
        $res = $this->db_village_qywx_code_label->addOne($data);
        return $res;
    }

    /**
     * 群发消息内容
     * @param $where
     * @param bool $field
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMessageContent($where,$field=true)
    {
        $data = $this->db_village_qywx_message_detail->getList($where,$field);
        return $data;
    }

    /**
     * 企业用户微信列表
     * @author lijie
     * @date_time 2021/03/19
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getContactUserList($where,$field=true,$page=1,$limit=10,$order='u.customer_id DESC')
    {
        $data = $this->db_house_contact_way_user->getList($where,$field,$page,$limit,$order);
        return $data;
    }

    /**
     * 企业用户数量
     * @author lijie
     * @date_time 2021/03/19
     * @param $where
     * @return int
     */
    public function getContactUserCount($where)
    {
        $count = $this->db_house_contact_way_user->getCount($where);
        return $count;
    }

    /**
     * 用户接受消息列表
     * @author lijie
     * @date_time 2021/03/22
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     */
    public function getMessageRecord($where,$field=true,$page=1,$limit=10,$order='r.id DESC')
    {
        $data = $this->db_village_qywx_message_record->getList($where,$field,$page,$limit,$order);
        if($data){
            foreach ($data as $k=>$v){
                /*$userInfo = $this->db_house_contact_way_user->getFind([['ExternalUserID','=',$v['external_user_id']],['status','in','1,3']]);
                if($userInfo)
                    $data[$k]['avatar'] = $userInfo['avatar'];
                $worker_info = $this->db_house_worker->get_one(['qy_id'=>$v['user_id']]);
                if($worker_info)
                    $data[$k]['name'] = $userInfo['name'];*/
                switch ($v['status']){
                    case 0:
                        $data[$k]['status'] = '未发送';
                        $data[$k]['send_time'] = '--';
                        break;
                    case 1:
                        $data[$k]['status'] = '已发送';
                        $data[$k]['send_time'] = date('Y-m-d H:i:s',$v['send_time']);
                        break;
                    case 2:
                        $data[$k]['status'] = '因客户不是好友导致发送失败';
                        $data[$k]['send_time'] = '--';
                        break;
                    case 3:
                        $data[$k]['status'] = '因客户已经收到其他群发消息导致发送失败';
                        $data[$k]['send_time'] = '--';
                        break;
                }
            }
        }
        return $data;
    }

    public function getMessageRecordCount($where)
    {
        $count = $this->db_village_qywx_message_record->getCount($where);
        return $count;
    }

    public function getMessageRecordJoinCount($where)
    {
        $count = $this->db_village_qywx_message_record->getJoinCount($where);
        return $count;
    }

    /**
     * 添加发送消息内容
     * @author lijie
     * @date_time 2021/03/22
     * @param $data
     * @return int|string
     */
    public function addMessageDetail($data)
    {
        $res = $this->db_village_qywx_message_detail->addOne($data);
        return $res;
    }

    /**
     * 自定义标签
     * @author lijie
     * @date_time 2021/03/23
     * @param $where
     * @param bool $field
     * @param string $order
     * @return \think\Collection
     */
    public function getDataConfigLabel($where,$field =true,$order='sort DESC, acid ASC')
    {
        $data = $this->db_house_village_data_config->getList($where,'title as label_group_name,key,use_field');
        if($data){
            foreach ($data as $k=>$v){
                $data[$k]['type'] = 0;
                $system_value = $this->get_system_value($v['key']);
                if($system_value){
                    $sys_value_arr = [];
                    foreach ($system_value as $k1=>$v1){
                        $sys_value_arr[$k1]['label_name'] = $v1;
                    }
                    $data[$k]['label_lists'] = $sys_value_arr;
                }else{
                    $use_field = explode(',',$v['use_field']);
                    if(empty($use_field)){
                        continue;
                    }else{
                        $use_field_arr = [];
                        foreach ($use_field as $k2=>$v2){
                            $use_field_arr[$k2]['label_name'] = $v2;
                        }
                        $data[$k]['label_lists'] = $use_field_arr;
                    }
                }
            }
        }
        return $data;
    }

    public  function get_system_value($key)
    {
        $arr = array();
        if(empty($key)){
            return $arr;
        }
        if($key == 'sex'){
            $arr = array('男','女');
        }elseif($key == 'nation'){
            $arr = array('汉族','满族','蒙古族','回族','藏族','维吾尔族','苗族','彝族','壮族','布依族','侗族','瑶族','白族','土家族','哈尼族','哈萨克族','傣族','黎族','傈僳族','佤族','畲族','高山族','拉祜族','水族','东乡族','纳西族','景颇族','柯尔克孜族','土族','达斡尔族','仫佬族','羌族','布朗族','撒拉族','毛南族','仡佬族','锡伯族','阿昌族','普米族','朝鲜族','塔吉克族','怒族','乌孜别克族','俄罗斯族','鄂温克族','德昂族','保安族','裕固族','京族','塔塔尔族','独龙族','鄂伦春族','赫哲族','门巴族','珞巴族','基诺族');
        }elseif($key == 'nationality'){
            $country = $this->db_country->getList([]);
            foreach($country as $k=>$v){
                $arr[] = $v['name_zh'];
            }
        }elseif($key == 'marriage_status'){
            $arr = array('未婚','已婚','离婚','丧偶');//未婚、已婚、离婚、丧偶
        }elseif($key == 'edcation'){
            $arr = array('无','初中及以下','高中或中专','大专','本科','硕士','博士','其他');
        }elseif($key == 'unit_nature'){
            $arr = array('国有企业','民营企业','外商独资','中外合资','港澳台企业','政府机关','事业单位','非营利性组织','自主创业','其他');
        }

        return $arr;
    }

    /**
     * 群发消息-直接走队列.
     */
    public function sendMessage($where=[],$field=true,$page=1,$limit=10,$order='id DESC')
    {
        $queueData = [];
        $queueData['jobType'] = 'workWeiXinGroupMsg';
        $queueData['type'] = 'get_group_msg_send_result';
        try{
            $this->traitCommonWorkWeiXin($queueData, 5);
        }catch (\Exception $e){
            fdump_api(['title' => '群发队列错误', 'err' => $e->getMessage()], 'qiYeWeiXin/sendQyMessageErrLog',true);
        }
        $queueData = [];
        $queueData['jobType'] = 'workWeiXinGroupMsg';
        $queueData['type'] = 'send_group_message';
        try{
            $this->traitCommonWorkWeiXin($queueData, 5);
        }catch (\Exception $e){
            fdump_api(['title' => '群发队列错误', 'err' => $e->getMessage()], 'qiYeWeiXin/sendQyMessageErrLog',true);
        }
        return true;
    }

    /**
     * 群发消息-（弃用）
     * @author lijie
     * @date_time 2021/03/19
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function sendMessageDisuse($where=[],$field=true,$page=1,$limit=10,$order='id DESC')
    {
        $whereArr=array();
        if(!empty($where)){
            foreach ($where as $kk=>$vv){
                if(is_array($vv)){
                    $whereArr[]=$vv;
                }else{
                    $whereArr[]=[$kk,'=',$vv];
                }
            }
            $whereArr[]=['is_del','=',0];
            //$whereArr[]=['send_status','in',array(0,2,3)];
        }else{
            $whereArr=[];
            $whereArr[]=['is_del','=',0];
            $whereArr[]=['send_status','in',array(0,2,3)];
        }
        $httpObj = new Http();
        $data = $this->db_village_qywx_message->getList($whereArr,$field,$page,$limit,$order);
        $service_qywx = new QywxService();
        if($data && !$data->isEmpty()){
            $data=$data->toArray();
            fdump_api(['获取$data=='.__LINE__,'data'=>$data],'qyweixin/sendQywxMessage',1);
            foreach ($data as $k=>$v){
                $access_token = $service_qywx->getQywxAccessToken($v['property_id']);
                fdump_api(['获取$access_token=='.__LINE__,'access_token'=>$access_token,'v'=>$v],'qyweixin/sendQywxMessage',1);
                if($v['msgid']  != ''){
                    $msg_result_url = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/get_group_msg_result?access_token='.$access_token;
                    if($v['send_type'] == 1){
                        $sendData=array();
                        $sendData['msgid'] = $v['msgid'];
                        $msg_result = $httpObj->curlQyWxPost($msg_result_url,json_encode($sendData,JSON_UNESCAPED_UNICODE));
                        if (!is_array($msg_result)) {
                            $msg_result = json_decode($msg_result,true);
                        }
                        fdump_api(['发送接口===='.__LINE__,'sendData'=>$sendData,'msg_result'=>$msg_result],'qyweixin/sendQywxMessage',1);
                        if($msg_result['errcode'] == 0 && $msg_result['check_status'] == 1){
                            foreach ($msg_result['detail_list'] as $value){
                                if (!in_array($value['userid'], explode(',',$v['sender']))){
                                    continue;
                                }
                                if(isset($value['external_userid'])){
                                    $record_info = $this->db_village_qywx_message_record->getOne(['message_id'=>$v['id'],'external_user_id'=>$value['external_userid'],'user_id'=>$value['userid']]);
                                    if($record_info){
                                        $this->db_village_qywx_message_record->saveOne(['id'=>$record_info['id']],['status'=>$value['status'],'send_time'=>isset($value['send_time'])?$value['send_time']:1]);
                                    }else{
                                        $contact_user_info = $this->db_house_contact_way_user->getOne([['ExternalUserID','=',$value['external_userid']],['UserID','=',$value['userid']],['status','in','1,3']],'customer_id');
                                        $this->db_village_qywx_message_record->addOne(['message_id'=>$v['id'],'external_user_id'=>$value['external_userid'],'user_id'=>$value['userid'],'status'=>$value['status'],'send_time'=>isset($value['send_time'])?$value['send_time']:1,'contact_user_id'=>$contact_user_info?$contact_user_info['customer_id']:0]);
                                    }
                                }
                            }
                            $service_plan = new PlanService();
                            $param['plan_time'] = time();
                            $param['space_time'] = 0;
                            $param['add_time'] = time();
                            $param['file'] = 'sub_village_qywx_send_message';
                            $param['time_type'] = 1;
                            $param['unique_id'] = 'qywx_message';
                            $service_plan->addTask($param,1);
                        }else{
                            $this->db_village_qywx_message->saveOne(['id'=>$v['id']],['send_status'=>1]);
                        }
                    }
                    else{
                        //群发
                        $msg_id_arr = explode(',',$v['msgid']);
                        $total_num = 0;
                        $actual_num = 0;
                        foreach ($msg_id_arr as $v2){
                            if(empty($v2)){
                                continue;
                            }
                            $sendData = [];
                            $sendData['msgid'] = $v2;
                            $msg_result = $httpObj->curlQyWxPost($msg_result_url,json_encode($sendData,JSON_UNESCAPED_UNICODE));
                            fdump_api(['群发发送接口===='.__LINE__,'sendData'=>$sendData,'msg_result'=>$msg_result],'qyweixin/sendQywxMessage',1);
                            if (!is_array($msg_result)) {
                                $msg_result = json_decode($msg_result,true);
                            }
                            if($msg_result['errcode'] == 0){
                                $total_num+=count($msg_result['detail_list']);
                                foreach ($msg_result['detail_list'] as $v3){
                                    if(isset($v3['chat_id'])){
                                        if($v3['status'] == 1){
                                            $actual_num+=1;
                                        }
                                        $record_info = $this->db_village_qywx_message_record->getOne(['message_id'=>$v['id'],'chat_id'=>$v3['chat_id']]);
                                        if($record_info){
                                            $this->db_village_qywx_message_record->saveOne(['id'=>$record_info['id']],['status'=>$v3['status'],'send_time'=>isset($v3['send_time'])?$v3['send_time']:1]);
                                        }else{
                                            $this->db_village_qywx_message_record->addOne(['message_id'=>$v['id'],'chat_id'=>$v3['chat_id'],'user_id'=>$v3['userid'],'status'=>$v3['status'],'send_time'=>isset($v3['send_time'])?$v3['send_time']:1]);
                                        }
                                    }
                                }
                            }
                        }
                        $this->db_village_qywx_message->saveOne(['id'=>$v['id']],['send_res'=>'预计发送'.$total_num.'个群，实际发送'.$actual_num.'个群','success_send'=>$actual_num,'fail_send'=>$total_num-$actual_num]);
                    }
                }else{
                    if($v['send_time'] <= time()){
                        if($v['send_type'] == 1 || $v['send_type'] == 2){
                            $postParams = [];
                            $postParams['chat_type'] = $v['send_type'] == 1?'single':'group';
                            if($v['send_type'] == 1)
                                $postParams['external_userid'] = explode(',',$v['external_userid']);
                            else
                                $sender = explode(',',$v['sender']);
                            $content_list = $this->db_village_qywx_message_detail->getList(['message_id'=>$v['id']],true);
                            if(empty($content_list)){
                                continue;
                            }else{
                                foreach ($content_list as $val){
                                    if($val['type'] == 1){
                                        $postParams['text'] = ['content'=>$val['content']];
                                    }elseif ($val['type'] == 2){
                                        //需要将图片上传到临时素材返回MEDIA_ID
                                        $media_id = $this->uploadQywxMedia($val['content'],'image',$v['property_id'],false,$v['message_name']);
                                        $postParams['attachments'][0]['msgtype'] = 'image';
                                        $postParams['attachments'][0]['image']['media_id'] = $media_id;
                                        $postParams['attachments'][0]['image']['pic_url'] = dispose_url($val['content']);
                                    }elseif ($val['type'] == 3){
                                        $postParams['attachments'][0]['msgtype'] = 'link';
                                        $postParams['attachments'][0]['link']['title'] = $val['content'];
                                        $postParams['attachments'][0]['link']['picurl'] = $val['share_img'];
                                        $postParams['attachments'][0]['link']['url'] = $val['share_url'];
                                    }
                                }
                            }
                            if(!$access_token)
                                continue;
                            $url = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/add_msg_template?access_token='.$access_token;
                            if(!isset($sender)){
                                $result = $httpObj->curlQyWxPost($url,json_encode($postParams,JSON_UNESCAPED_UNICODE));
                                fdump_api(['msgid为空=='.__LINE__,'postParams'=>$postParams,'msg_result'=>$result],'qyweixin/sendQywxMessage',1);
                                if (!is_array($result)) {
                                    $result = json_decode($result,true);
                                }
                                if($result['errcode'] == 0){
                                    $err_count = count($result['fail_list']);
                                    $this->db_village_qywx_message->saveOne(['id'=>$v['id']],['msgid'=>$result['msgid'],'send_status'=>2,'send_res'=>'预计发送'.count($postParams['external_userid']).'个客户，实际发送'.(count($postParams['external_userid'])-$err_count).'个客户','success_send'=>count($postParams['external_userid'])-$err_count,'fail_send'=>$err_count]);
                                }
                            }else{
                                $msgid_list = '';
                                foreach ($sender as $v1){
                                    $postParams['sender'] = $v1;
                                    $result = $httpObj->curlQyWxPost($url,json_encode($postParams,JSON_UNESCAPED_UNICODE));
                                    fdump_api(['postParams'=>$postParams,'msg_result'=>$result],'qyweixin/sendQywxMessage',1);
                                    if (!is_array($result)) {
                                        $result = json_decode($result,true);
                                    }
                                    $msgid_list .= $result['msgid'].',';
                                }
                                $msgid_list = rtrim($msgid_list,',');
                                $this->db_village_qywx_message->saveOne(['id'=>$v['id']],['msgid'=>$msgid_list,'send_status'=>2]);
                            }
                        }
                        else{
                            //企业成员群发消息
                            $content_info = $this->db_village_qywx_message_detail->getOne(['message_id'=>$v['id']],true);
                            if(empty($content_info))
                                continue;
                            $access_token = $service_qywx->getQywxAccessToken($v['property_id'],'enterprise_wx_provider');
                            fdump_api(['657_access_token'=>$access_token,'property_id'=>$v['property_id']],'qyweixin/sendQywxMessage',1);
                            if(!$access_token)
                                continue;
                            $info = $this->db_house_enterprise_wx_bind->getOne(['bind_id'=>$v['property_id'],'bind_type'=>0],'agentid');
                            $agentid = $info['agentid'];
                            $send_message_url = 'https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token='.$access_token;
                            if($content_info['type'] == 1){
                                $postParams = [];
                                $postParams['touser'] = str_replace(',','|',$v['sender']);
                                $postParams['msgtype'] = 'text';
                                $postParams['agentid'] = $agentid;
                                $postParams['text']['content'] = $content_info['content'];
                            }elseif ($content_info['type'] == 2){
                                //需要将图片/文件/视频上传到临时素材返回MEDIA_ID
                                $media_id = $this->uploadQywxMedia($content_info['content'],'image',$v['property_id'],false,$v['message_name']);
                                $postParams = [];
                                $postParams['touser'] = str_replace(',','|',$v['sender']);
                                $postParams['msgtype'] = 'image';
                                $postParams['agentid'] = $agentid;
                                $postParams['image'] = ['media_id'=>$media_id];
                            }elseif ($content_info['type'] == 4){
                                //需要将图片/文件/视频上传到临时素材返回MEDIA_ID
                                $media_id = $this->uploadQywxMedia($content_info['content'],'video',$v['property_id'],false,$v['message_name']);
                                $postParams = [];
                                $postParams['touser'] = str_replace(',','|',$v['sender']);
                                $postParams['msgtype'] = 'video';
                                $postParams['agentid'] = $agentid;
                                $postParams['video'] = ['media_id'=>$media_id];
                            }elseif ($content_info['type'] == 5){
                                //需要将图片/文件/视频上传到临时素材返回MEDIA_ID
                                $media_id = $this->uploadQywxMedia($content_info['content'],'file',$v['property_id'],false,$v['message_name']);
                                $postParams = [];
                                $postParams['touser'] = str_replace(',','|',$v['sender']);
                                $postParams['msgtype'] = 'file';
                                $postParams['agentid'] = $agentid;
                                $postParams['file'] = ['media_id'=>$media_id];
                            }
                            $getopenuseridurl='https://qyapi.weixin.qq.com/cgi-bin/batch/userid_to_openuserid?access_token='.$access_token;
                            $userid_list=explode(',',$v['sender']);
                            $postuserid=array('userid_list'=>$userid_list);
                            $postuserid=json_encode($postuserid,JSON_UNESCAPED_UNICODE);
                            $openuserid=$httpObj->curlQyWxPost($getopenuseridurl,$postuserid,30,'json');
                            fdump_api([$getopenuseridurl,'postuserid'=>$postuserid,'openuserid'=>$openuserid],'qyweixin/sendQywxMessage',1);
                            $touserArr=array();
                            if (!is_array($openuserid)) {
                                $openuserid = json_decode($openuserid,true);
                            }
                            if($openuserid && isset($openuserid['open_userid_list']) && !empty($openuserid['open_userid_list'])){
                                foreach ($openuserid['open_userid_list'] as $nvv){
                                    $touserArr[]=$nvv['open_userid'];
                                }
                                $postParams['touser']=implode('|',$touserArr);
                            }
                            $bodaydata=json_encode($postParams,JSON_UNESCAPED_UNICODE);
                            $res = $httpObj->curlQyWxPost($send_message_url,$bodaydata,30,'json');
                            fdump_api(['696_postParams'=>$bodaydata,'res'=>$res],'qyweixin/sendQywxMessage',1);
                            if (!is_array($res)) {
                                $res = json_decode($res,true);
                            }

                            if(empty($res['invaliduser'])){
                                $invaliduser = 0;
                            } else{
                                if(!is_array($res['invaliduser']) && strpos($res['invaliduser'],'|')){
                                    $res['invaliduser']=explode('|',$res['invaliduser']);
                                }
                                $invaliduser = count($res['invaliduser']);
                            }
                            $send_res='预计发送'.count(explode(',',$v['sender'])).'个客户，实际发送'.(count(explode(',',$v['sender']))-$invaliduser).'个客户';
                            $send_status=1;
                            if(empty($res) || $res['errcode']>0){
                                $send_status=4;
                                if(!empty($res) && $res['errmsg']){
                                    $send_res=$res['errmsg'];
                                }

                            }
                            $this->db_village_qywx_message->saveOne(['id'=>$v['id']],['send_status'=>$send_status,'send_res'=>$send_res,'success_send'=>count(explode(',',$v['sender']))-$invaliduser,'fail_send'=>$invaliduser]);
                        }
                    }
                }
            }
        }
        return true;
    }

    /**
     * 企业微信上传临时素材
     * @author lijie
     * @date_time 2021/03/29
     * @param $imgUrl
     * @param string $type
     * @param int $property_id
     * @param bool $is_replace
     * @return bool|mixed
     */
    public function uploadQywxMedia($imgUrl, $type='image',$property_id=0,$is_replace=false,$basename='')
    {
        if(!$property_id)
            return false;
        if($type != 'file'){
            $imgUrl = dispose_url($imgUrl);
        }
        if(cfg('static_oss_switch')==1 && $type != 'file') {
            $file_handle = new FileHandle();
            $file_handle->download($imgUrl);
            $imgUrl = $_SERVER['DOCUMENT_ROOT'].str_replace($_SERVER['REQUEST_SCHEME'] . '://' . cfg('static_oss_access_domain_names'), '', $imgUrl);
        } else{
            $imgUrl = $_SERVER['DOCUMENT_ROOT'].str_replace(cfg('site_url'),'',$imgUrl);
        }
        $service_qywx = new QywxService();
        $access_token = $service_qywx->getQywxAccessToken($property_id);
        if(!$access_token && !$is_replace){
            $this->uploadQywxMedia($imgUrl,$type, $property_id,true,$basename);
        }
        if($access_token && $imgUrl){
            $file  = $imgUrl;
            $fileInfo = pathinfo($file);
            if (!$basename  &&  $fileInfo['basename']) {
                $basename = $fileInfo['basename'];
            }
            if ($fileInfo['extension']) {
                $extension = $fileInfo['extension'];
            } else {
                $extension = '';
            }
            if (class_exists('\CURLFile')) {
                $data['media'] = new \CURLFile(realpath($file),$extension,$basename);
            } else {
                $data['media'] = '@'.realpath($file);
            }
            $httpObj = new Http();
            $return = $httpObj->curlQyWxPost('https://qyapi.weixin.qq.com/cgi-bin/media/upload?access_token='.$access_token.'&type='.$type,$data);
            if (!is_array($return)) {
                $return = json_decode($return,true);
            }
            return $return['media_id'];
        }
        return false;
    }
}