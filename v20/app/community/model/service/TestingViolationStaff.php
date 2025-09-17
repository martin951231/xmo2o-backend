<?php
/**
 * @author : liukezhu
 * @date : 2021/5/10
 */
namespace app\community\model\service;

use app\community\model\db\HouseWorker;
use app\community\model\db\WorkMsgAuditInfoSensitive;
use app\community\model\db\WorkMsgAuditInfoViolationGroup;
use app\community\model\db\WorkMsgAuditInfoViolationStaff;
use app\community\model\db\WorkMsgAuditInfoGroup;
use app\community\model\db\WorkMsgAuditInfo;
use app\community\model\db\HouseEnterpriseWxBind;
use net\Http as Http;

class TestingViolationStaff
{

    public $violation_action=array(
        1=>'图片',
        2=>'链接',
        3=>'小程序',
        4=>'名片',
        5=>'红包',
        6=>'音频',
        7=>'视频',
        8=>'关键词',
    );

    protected $db_http;
    protected $WorkMsgAuditInfoViolationStaff;
    protected $HouseWorker;
    protected $QywxService;
    protected $HouseEnterpriseWxBind;
    protected $WorkMsgAuditInfoSensitive;
    protected $WorkMsgAuditInfo;
    protected $WorkMsgAuditInfoViolationGroup;
    protected $WorkMsgAuditInfoGroup;
    protected $SessionFileService;

    public function __construct()
    {
        $this->db_http =  new Http();
        $this->WorkMsgAuditInfoViolationStaff = new WorkMsgAuditInfoViolationStaff();
        $this->HouseWorker = new HouseWorker();
        $this->QywxService = new QywxService();
        $this->HouseEnterpriseWxBind = new HouseEnterpriseWxBind();
        $this->WorkMsgAuditInfoSensitive = new WorkMsgAuditInfoSensitive();
        $this->WorkMsgAuditInfo = new WorkMsgAuditInfo();
        $this->WorkMsgAuditInfoViolationGroup = new WorkMsgAuditInfoViolationGroup();
        $this->WorkMsgAuditInfoGroup =new WorkMsgAuditInfoGroup();
        $this->SessionFileService=new SessionFileService();


    }

    /**
     * 查询违规员工数据
     * @author: liukezhu
     * @date : 2021/5/11
     * @param $param
     * @param $village_id
     * @param $property_id
     * @return mixed
     */
    public function getStaffList($param,$village_id,$property_id)
    {
        $limit = 20;
        $page = isset($param['page']) && $param['page'] ? $param['page'] : 0;
        $where[]=[ 's.village_id','=',$village_id];
        $where[]=[ 's.del_time','=',0];
        $where[]=[ 'w.is_del','=',0];
        $where[]=[ 'w.status','=',1];
        if(isset($param['staff_id']) && !empty($param['staff_id'])){
            $where[]=[ 's.staff_id','in',$param['staff_id']];
        }
        if(isset($param['send_type']) && $param['send_type'] >= 0){
            $where[]=[ 's.status','=',$param['send_type']];
        }
        $list = $this->WorkMsgAuditInfoViolationStaff->getList($where,'s.id,w.name,w.avatar,( SELECT group_concat( w1.NAME ) FROM pigcms_house_worker AS w1 WHERE FIND_IN_SET( w1.wid, s.remind_uid ) ) AS remind_user,s.sensitive,s.status','s.id desc',$page,$limit);
        if($list){
            foreach ($list as &$v){
                $v['sensitive']=explode(',',$v['sensitive']);
                $v['tags']=explode(',',$v['remind_user']);
                unset($v['remind_user']);
            }
            unset($v);
        }
        $count = $this->WorkMsgAuditInfoViolationStaff->getCount($where);
        $data['list'] = $list;
        $data['total_limit'] = $limit;
        $data['count'] = $count;
        return $data;
    }

    /**
     * 获取敏感词
     * @author: liukezhu
     * @date : 2021/5/11
     */
    public function getSensitives($param,$where=false){
        if($where){
            $list = $this->WorkMsgAuditInfoSensitive->getList($where,0,'id,name');
        }else{
            $list = $this->WorkMsgAuditInfoSensitive->getList($param,0,'id,name');
        }
        if(!empty($list)){
            $list=$list ->toarray();
        }
        return $list;
    }

    /**
     * 判断是否配置同一个违规用户
     * @author: liukezhu
     * @date : 2021/5/11
     * @param $param
     * @return WorkMsgAuditInfoViolationStaff
     */
    public function checkViolationStaff($param){
        $where[]=[ 's.staff_id','in',$param['staff_id']];
        $where[]=[ 's.village_id','=',$param['village_id']];
        $where[]=[ 's.del_time','=',0];
        $list=$this->WorkMsgAuditInfoViolationStaff->getOne($where);
        if(!empty($list)){
            $list=$list ->toarray();
        }
        return $list;
    }

    public function checkStaff($param){
        $where[]=[ 'w.wid','in',$param['staff_id']];
        $where[]=[ 'w.village_id','=',$param['village_id']];
        $where[]=[ 'w.status','=',1];
        $where[]=[ 'w.qy_id','>',0];
        $where[]=[ 'w.is_del','=',0];
        $where[]=[ 'g.is_del','=',0];
        $where[]=[ 'w2.status','=',1];
        $where[]=[ 'w2.is_del','=',0];
        $where[]=[ 'w2.qy_id','>',0];
        $list=$this->HouseWorker ->checkStaff($where);
        return (empty($list) ? [] : $list->toarray());
    }

    /**
     * 添加违规员工
     * @author: liukezhu
     * @date : 2021/5/11
     * @param $param
     */
    public function addViolationStaff($param){
        $name = array_column($param['sensitives_info'],'name');
        $id = array_column($param['sensitives_info'],'id');

        $time=time();
        $sensitives_info=[];
        foreach ($param['sensitives_info'] as $v){
            $sensitives_info[$v['id']]=$v['name'];
        }
        $rel=false;
        if($param['choice_remind'] == 1){
            //对应部门负责人
            foreach ($param['staff_id'] as $v){
                $wid=[];
                foreach ($param['rr'] as $v2){
                    if($v == $v2['staff_id']){
                        $wid[]=$v2['wid'];
                    }
                }
                if(!empty($wid)){
                    $data=array(
                        'property_id'=>$param['property_id'],
                        'village_id'=>$param['village_id'],
                        'staff_id'=>$v,
                        'sensitive'=>implode(',',$name),
                        'sensitive_id'=>implode(',',$id),
                        'sensitive_info'=>serialize($sensitives_info),
                        'remind_type'=>$param['choice_remind'],
                        'remind_uid'=>implode(',',$wid),
                        'status'=>1,
                        'add_time'=>$time
                    );
                    $rel = $this->WorkMsgAuditInfoViolationStaff->addFind($data);
                }
            }
        }else{
            //指定成员
            $remind_uid=implode(',',$param['appoint_staff_id']);
            foreach ($param['staff_id'] as $v){
                $data=array(
                    'property_id'=>$param['property_id'],
                    'village_id'=>$param['village_id'],
                    'staff_id'=>$v,
                    'sensitive'=>implode(',',$name),
                    'sensitive_id'=>implode(',',$id),
                    'sensitive_info'=>serialize($sensitives_info),
                    'remind_type'=>$param['choice_remind'],
                    'remind_uid'=>$remind_uid,
                    'status'=>1,
                    'add_time'=>$time
                );
                $rel = $this->WorkMsgAuditInfoViolationStaff->addFind($data);
            }
        }
        return $rel;
    }

    /**
     * 违规员工显示
     * @author: liukezhu
     * @date : 2021/5/11
     * @param $param
     */
    public function editViolationStaff($param,$type=false){
        $list= $this->WorkMsgAuditInfoViolationStaff->getOne([
            's.id'=>$param['id'],
            's.village_id'=>$param['village_id']
        ]);
        if(!$list){
            return false;
        }

        if($type){
            $this->WorkMsgAuditInfoViolationStaff->editFind(['id'=>$param['id'],'village_id'=>$param['village_id']],['status'=>$param['status']]);
            return true;
        }else{
            $list['staff_name']=$this->HouseWorker ->getColumn(['wid'=>$list['staff_id']],'name')[0];
            $where[]=[ 'village_id','=',$param['village_id']];
            $where[]=[ 'status','=',1];
            $where[]=[ 'id','in',explode(',',$list['sensitive_id'])];
            $list['sensitive_info'] = $this->WorkMsgAuditInfoSensitive->getColumn($where,'id');
        }
        return $list;
    }

    /**
     * 删除
     * @author: liukezhu
     * @date : 2021/5/11
     * @param $param
     * @return \app\community\model\db\WorkMsgAuditInfo
     */
    public function delViolationStaff($param){
        return $this->WorkMsgAuditInfoViolationStaff->editFind([
            'id'=>$param['id'],
            'village_id'=>$param['village_id']
        ],[
            'del_time'=>time()
        ]);
    }


    /**
     * 获取群聊列表
     * @author: liukezhu
     * @date : 2021/5/12
     * @param $param
     * @param $village_id
     * @return mixed
     */
    public function getViolationGroupList($param,$village_id){
        $limit = 20;
        $page = isset($param['page']) && $param['page'] ? $param['page'] : 0;
        $where[]=[ 'g.village_id','=',$village_id];
        $where[]=[ 'g.del_time','=',0];
        if(isset($param['title'])){
            $where[] = ['g.rule_name','like','%'.$param['title'].'%'];
        }
        if(isset($param['group_id']) && !empty($param['group_id'])){
            $where[]=[ 'g.group_id','in',$param['group_id']];
        }
        $list = $this->WorkMsgAuditInfoViolationGroup->getList($where,'g.id,g.sensitive,g.rule_name,g.action,g.remind_type,g.remind_info,g.status,g.add_time,( SELECT group_concat( g1.roomname ) FROM pigcms_work_msg_audit_info_group AS g1 WHERE FIND_IN_SET( g1.id, g.group_id ) ) AS groups','g.id desc',$page,$limit);
        if($list){
            foreach ($list as &$v){
                $remind_info=unserialize($v['remind_info']);
                $action=explode(',',$v['action']);
                $action_tags=[];
                if($action){
                    sort($action);
                    foreach ($action as $v2){
                        $action_tags[]=$this->violation_action[$v2];
                    }
                }
                $v['groups_tags']=explode(',',ltrim($v['groups'], ","));
                $v['action_tags']=$action_tags;
                $v['sensitive_tags']=empty($v['sensitive']) ? [] : '关键词：'.str_replace(',','，',$v['sensitive']);
                $v['remind_info']=$remind_info['name'];
                $v['add_time']=date('Y-m-d',$v['add_time']);
                unset($v['groups'],$v['action'],$v['sensitive']);
            }
            unset($v);
        }
        $count = $this->WorkMsgAuditInfoViolationGroup->getCount($where);
        $data['list'] = $list;
        $data['total_limit'] = $limit;
        $data['count'] = $count;
        return $data;
    }


    /**
     * 新增群聊配置
     * @author: liukezhu
     * @date : 2021/5/12
     * @param $param
     */
    public function addViolationGroup($param){
        $sensitives_info=$sensitive_id=$sensitive_name=[];
        if(!empty($param['sensitives_info'])){
            $sensitive_name = array_column($param['sensitives_info'],'name');
            $sensitive_id = array_column($param['sensitives_info'],'id');
            foreach ($param['sensitives_info'] as $v){
                $sensitives_info[$v['id']]=$v['name'];
            }
        }
        $group_id=[];
        foreach ($param['group_id'] as $v){
            $str=explode('-',$v);
            $group_id[]=$str[0];
        }

        $remind_info=array(
            'inside_group'=>[],     //内部群
            'contact_group'=>[],    //外部群
            'work_id'=>[],          //选择成员
            'name'=>[]
        );
        /**
         * todo 内部群-群id
         */
        $group_where[]=[ 'g.id','in',$group_id];
        $group_where[]=[ 'g.owner_type','=',1];
        $group_where[]=[ 'w.is_del','=',0];
        $inside_group=$this->WorkMsgAuditInfoGroup->getWorkerGroup($group_where,'w.wid,w.name');
        if($inside_group){
            $qun_id=$qun_name=[];
            foreach ($inside_group as $v){
                $qun_id[]=$v['wid'];
                $qun_name[]=$v['name'];
            }
            $remind_info['inside_group']=array_values(array_unique($qun_id));
            $remind_info['name'][]='群主：'.array_values(array_unique($qun_name))[0];
        }

        /**
         * todo 外部群-群id
         */
        $contact_where[]=[ 'g.id','in',$group_id];
        $contact_where[]=[ 'g.owner_type','=',2];
        $contact_group = $this->WorkMsgAuditInfoGroup->getContactWayUserGroup($contact_where,'w.customer_id as wid');

        if($contact_group){
            $qun_id=[];
            foreach ($contact_group as $v){
                $qun_id[]=$v['wid'];
            }
            $remind_info['contact_group']=array_values(array_unique($qun_id));
            $remind_info['name'][]='群主：--';
        }

        $remind_type=1;
        if(in_array(2,$param['staff_type'])){
            $remind_type=2;
            if(empty($param['appoint_staff_id'])){
                return false;
            }
            $where[]=[ 'wid','in',implode(',',$param['appoint_staff_id'])];
            $where[]=[ 'is_del','=',0];
            $worker=$this->HouseWorker ->getColumn($where,'wid');
            if(empty($worker)){
                return false;
            }
            $remind_info['work_id']=$worker;
            $remind_info['name'][]='其它：选择成员';
        }
        $data=array(
            'property_id'=>$param['property_id'],
            'village_id'=>$param['village_id'],
            'rule_name'=>$param['rule_name'],
            'group_id'=>implode(',',$group_id),
            'action'=>implode(',',$param['groupAction']),
            'remind_type'=>$remind_type,
            'remind_info'=>serialize($remind_info),
            'add_time'=>time()
        );
        if(!empty($param['sensitives_info'])){
            $data['sensitive_id']=implode(',',$sensitive_id);
            $data['sensitive']=implode(',',$sensitive_name);
            $data['sensitive_info']=serialize($sensitives_info);
        }else{
            $data['sensitive_id']=$data['sensitive']=$data['sensitive_info']=null;
        }
        return $this->WorkMsgAuditInfoViolationGroup->addFind($data);
    }


    private function stringInt($items){
        $numbers = array();
        foreach ($items as $number) {
            $numbers[] = intval(trim($number), 10); //因为可能数字两侧有空格，所以trim一下
        }
        return $numbers;
    }


    /**
     * 编辑群聊配置
     * @author: liukezhu
     * @date : 2021/5/12
     * @param $param
     * @param bool $type
     * @return \app\community\model\db\WorkMsgAuditInfo|bool
     */
    public function editViolationGroup($param,$type=false){
        $list= $this->WorkMsgAuditInfoViolationGroup->getOne([
            'g.id'=>$param['id'],
            'g.village_id'=>$param['village_id'],
            'g.del_time'=>0
        ]);
        if(!$list){
            return false;
        }
        $list=$list->toarray();
        if($type){
            $sensitives_info=$sensitive_id=$sensitive_name=[];
            if(!empty($param['sensitives_info'])){
                $sensitive_name = array_column($param['sensitives_info'],'name');
                $sensitive_id = array_column($param['sensitives_info'],'id');
                foreach ($param['sensitives_info'] as $v){
                    $sensitives_info[$v['id']]=$v['name'];
                }
            }
            $data=array(
                'status'=>$param['status'],
                'action'=>(is_array($param['action']) ? implode(',',$param['action']) : $param['action']),
            );
            if(!empty($param['sensitives_info'])){
                $data['sensitive_id']=implode(',',$sensitive_id);
                $data['sensitive']=implode(',',$sensitive_name);
                $data['sensitive_info']=serialize($sensitives_info);
            }else{
                $data['sensitive_id']=$data['sensitive']=$data['sensitive_info']=null;
            }
            $this->WorkMsgAuditInfoViolationGroup->editFind(['id'=>$param['id'],'village_id'=>$param['village_id']],$data);
            return true;
        }else{
            $list['action']=self::stringInt(explode(',',$list['action']));
            if(!empty($list['sensitive_id'])){
                $list['sensitive_id']=self::stringInt(explode(',',$list['sensitive_id']));
            }else if(in_array(8,$list['action'])){
                $list['sensitive_id']=[8];
            }else{
                $list['sensitive_id']=[];
            }
            $list['groups']=explode(',',ltrim($list['groups'], ","));
        }
        return $list;
    }

    /**
     * 删除群聊配置
     * @author: liukezhu
     * @date : 2021/5/12
     * @param $param
     * @return \app\community\model\db\WorkMsgAuditInfo
     */
    public function delViolationGroup($param){
        return $this->WorkMsgAuditInfoViolationGroup->editFind([
            'id'=>$param['id'],
            'village_id'=>$param['village_id']
        ],[
            'del_time'=>time()
        ]);
    }

    public function test($property_id){
        $info =$this->HouseEnterpriseWxBind->getOne(['bind_id'=>$property_id,'bind_type'=>0],'agentid');
        if(!$info){
            return false;
        }
        $agentid = $info['agentid'];
        $access_token = $this->QywxService->getQywxAccessToken($property_id,'enterprise_wx_provider');
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token='.$access_token;


        $content = "";

        $content .= "<div>关键词一</div>";

        $content .= "<div>状态:提醒</div>";

        $content .= "<div>时间:".date('Y-m-d H:i:s',time())."</div>";

        $content .= "<div>类型:违规</div>";

        $sender='worker_326';
        $postParams = [];
        $postParams['touser'] = str_replace(',','|',$sender);
        $postParams['msgtype'] = 'text';
        $postParams['agentid'] = $agentid;
        $postParams['text']['content'] = $content;
        $result = $this->db_http->curlQyWxPost($url,json_encode($postParams,JSON_UNESCAPED_UNICODE));


    }

    public function send($sender,$agentid,$access_token,$data){
        $content = "";
        $content .= "<div>{$data['title']}</div>";
        if ($data['type']) {
            $content .= "<div>类型：{$data['type']}</div>";
        }
        if (isset($data['staff'])) {
            $content .= "<div>违规人：{$data['staff']}</div>";
        }
        if (isset($data['name'])) {
            $content .= "<div>规则名称：{$data['name']}</div>";
        }
        if (isset($data['group'])) {
            $content .= "<div>规则群聊：{$data['group']}</div>";
        }
        if (isset($data['sensitive'])) {
            $content .= "<div>敏感词：{$data['sensitive']}</div>";
        }
        if (isset($data['content'])) {
            $content .= "<div>内容：{$data['content']}</div>";
        }
        $content .= "<div>时 间：".date('Y-m-d H:i:s',time())."</div>";
        $postParams = [];
        $postParams['touser'] = implode('|',$sender);
        $postParams['msgtype'] = 'text';
        $postParams['agentid'] = $agentid;
        $postParams['text']['content'] = $content;
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token='.$access_token;
        return $this->db_http->curlQyWxPost($url,json_encode($postParams,JSON_UNESCAPED_UNICODE));
    }


    /**
     * 违规员工发送
     * @author: liukezhu
     * @date : 2021/5/13
     */
    public function staffRemind(){
        $where[]=[ 'del_time','=',0];
        $where[]=[ 'status','=',1];
        $sensitiveList = $this->WorkMsgAuditInfoViolationStaff->getAll($where,'property_id,sensitive,sensitive_info');
        unset($where);
        if(empty($sensitiveList)){
            return false;
        }
        foreach ($sensitiveList as $v){
            $sensitive=explode(',',$v['sensitive']);
            $sensitive_info=unserialize($v['sensitive_info']);
            $info =$this->HouseEnterpriseWxBind->getOne(['bind_id'=>$v['property_id'],'bind_type'=>0],'agentid');
            if(!$info || empty($info['agentid'])){
                continue;
            }
            $access_token = $this->QywxService->getQywxAccessToken($v['property_id'],'enterprise_wx_provider');
            foreach ($sensitive as $v2){
                $worke_where[]=[ 's.del_time','=',0];
                $worke_where[]=[ 's.status','=',1];
                $worke_where[] = [['m.content', 'like', '%' . $v2 . '%']];
                $worke_where[]=[ 'm.msgtype','=','text'];
                $worke_where[]=[ 'm.is_send','=',0];
                $work=$this->WorkMsgAuditInfoViolationStaff->getViolationWork($worke_where,'s.id,s.staff_id,s.property_id,s.remind_uid,m.id as msg_id,m.content,"'.$v2.'" as msg');
                unset($worke_where);
                if(empty($work) || empty($work['remind_uid'])){
                    continue;
                }
                $sensitive_id = array_search($v2, $sensitive_info);
                $staff=$this->HouseWorker ->get_one(['wid'=>$work['staff_id']],'name');
                $remind_uid=explode(',',$work['remind_uid']);
                $remind_uid=array_values(array_unique($remind_uid));
                if(empty($remind_uid)){
                    continue;
                }
                $condition1[] = ['wid', 'in',$remind_uid];
                $sender=$this->HouseWorker ->getColumn($condition1,'qy_id');
                unset($condition1);
                $data=array(
                    'title'=>'违规提醒',
                    'type'=>'员工违规',
                    'staff'=>$staff['name'],
                    'sensitive'=>$work['msg'],
                    'content'=>$work['content']
                );
                $send=$this->send($sender,$info['agentid'],$access_token,$data);
                if(isset($send['errcode']) && ($send['errcode'] == 0)){
                    //发送成功
                    $this->WorkMsgAuditInfo->editFind(['id'=>$work['msg_id'],'is_send'=>0],['is_send'=>1]);
                    //员工触发的敏感词次数
                    $this->WorkMsgAuditInfoSensitive->where(['id'=>$sensitive_id])->setInc('trigger_worker_num',1);
                }
                fdump_api('员工关键词触发'.__LINE__,$send,'village/staffRemind_err_log',true);
            }
        }
    }

    /**
     * 违规群聊发送
     * @author: liukezhu
     * @date : 2021/5/13
     */
    public function groupRemind(){
        $actionArr=array(
            1=>'image',
            2=>'link',
            3=>'weapp',
            4=>'card',
            5=>'redpacket',
            6=>'voice',
            7=>'video',
            8=>'text',
        );
        $where[]=[ 'del_time','=',0];
        $where[]=[ 'status','=',1];
        $ViolationGroup = $this->WorkMsgAuditInfoViolationGroup->getAll($where,'id,group_id,property_id,remind_info,rule_name,sensitive,action,sensitive_info');
        if(empty($ViolationGroup)) return false;
        foreach ($ViolationGroup as $v){
            $group_id=explode(',',$v['group_id']);
            $sensitive_info=empty($v['sensitive_info']) ? [] : unserialize($v['sensitive_info']);
            $sensitive=empty($v['sensitive']) ? [] : array_values(array_unique(explode(',',$v['sensitive'])));
            $remind_info=unserialize($v['remind_info']);
            $info =$this->HouseEnterpriseWxBind->getOne(['bind_id'=>$v['property_id'],'bind_type'=>0],'agentid');
            if(!$info || empty($info['agentid'])){
                continue;
            }
            $access_token = $this->QywxService->getQywxAccessToken($v['property_id'],'enterprise_wx_provider');
            $action=[];
            if(!empty($v['action'])){
                foreach (explode(',',$v['action']) as $v3){
                    if(isset($actionArr[$v3])){
                        $action[]=$actionArr[$v3];
                    }
                }
            }
            if(!empty($sensitive)){
                foreach ($sensitive as $v2){
                    $group_where[]=[ 'is_send','=',0];
                    $group_where[] = [['content', 'like', '%' . $v2 . '%']];
                    //$group_where[] = ['content','like',$sensitive,'or'];
                    if(!empty($action)){
                        $group_where[]=[ 'msgtype','in',$action];
                    }
                    if(!empty($group_id)){
                        $group_where[]=[ 'roomid','in',$group_id];
                    }
                    $work= $this->WorkMsgAuditInfo->getFind($group_where, 'id as msg_id,roomid,msgtype,content,"'.$v2.'" as msg', 'id desc');
                    unset($group_where);
                    if(empty($work)){
                        continue;
                    }
                    $sensitive_id = array_search($v2, $sensitive_info);
                    $users = array_merge($remind_info['inside_group'], $remind_info['contact_group'], $remind_info['work_id']);
                    $remind_uid=array_values(array_unique($users));
                    if(empty($remind_uid)){
                        continue;
                    }
                    $roomname=$this->WorkMsgAuditInfoGroup->getFind(['id'=>$work['roomid']],'roomname');
                    $condition1[] = ['wid', 'in',$remind_uid];
                    $sender=$this->HouseWorker->getColumn($condition1,'qy_id');
//                    $c=$dbSessionFileService->actionMsg($work['msgtype'],$work['msg_id'],[])['local_path'];
                    $data=array(
                        'title'=>'违规提醒',
                        'type'=>'群聊违规'.'+'.$work['msgtype'],
                        'name'=>$v['rule_name'],
                        'group'=>$roomname['roomname'],
                        'sensitive'=>$work['msg'],
                        'content'=>$work['content'],
                    );
                    $send=$this->send($sender,$info['agentid'],$access_token,$data);
                    if(isset($send['errcode']) && ($send['errcode'] == 0)){
                        //发送成功
                        $this->WorkMsgAuditInfo->editFind(['id'=>$work['msg_id'],'is_send'=>0],['is_send'=>1]);
                        //员工触发的敏感词次数
                        $this->WorkMsgAuditInfoSensitive->where(['id'=>$sensitive_id])->setInc('trigger_user_num',1);
                    }
                    fdump_api('群聊关键词'.__LINE__,$send,'village/groupRemind_err_log',true);
                }
            }
            else{
                $group_where[]=[ 'is_send','=',0];
                if(!empty($action)){
                    $group_where[]=[ 'msgtype','in',$action];
                }
                if(!empty($group_id)){
                    $group_where[]=[ 'roomid','in',$group_id];
                }
                $work= $this->WorkMsgAuditInfo->getFind($group_where, 'id as msg_id,roomid,msgtype,content', 'id desc');
                unset($group_where);
                if(empty($work)){
                    continue;
                }
                $roomname=$this->WorkMsgAuditInfoGroup->getFind(['id'=>$work['roomid']],'roomname');
                $users = array_merge($remind_info['inside_group'], $remind_info['contact_group'], $remind_info['work_id']);
                $remind_uid=array_values(array_unique($users));
                if(empty($remind_uid)){
                    continue;
                }
                $condition1[] = ['wid', 'in',$remind_uid];
                $sender=$this->HouseWorker->getColumn($condition1,'qy_id');
//                    $c=$dbSessionFileService->actionMsg($work['msgtype'],$work['msg_id'],[])['local_path'];
                $data=array(
                    'title'=>'违规提醒',
                    'type'=>'群聊违规'.'+'.$work['msgtype'],
                    'name'=>$v['rule_name'],
                    'group'=>$roomname['roomname'],
                    'content'=>$work['content'],
                );
                $send=$this->send($sender,$info['agentid'],$access_token,$data);
                if(isset($send['errcode']) && ($send['errcode'] == 0)){
                    //发送成功
                    $this->WorkMsgAuditInfo->editFind(['id'=>$work['msg_id'],'is_send'=>0],['is_send'=>1]);
                }
                fdump_api('群聊行为',__LINE__,$send,'village/groupRemind_err_log',true);
            }
        }

    }



}