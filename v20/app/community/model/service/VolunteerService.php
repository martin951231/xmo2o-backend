<?php
/**
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/5/29 13:08
 */

namespace app\community\model\service;


use app\common\model\service\send_message\SmsService;
use app\common\model\service\weixin\TemplateNewsService;
use app\community\model\db\AreaStreet;
use app\community\model\db\HouseVenueNewsRecord;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\User;
use app\community\model\db\VolunteerActivity;
use app\community\model\db\VolunteerActivityJoin;
use think\facade\Db;

class VolunteerService
{

    public $volunteer_activity_status = [
        1 => '开启',
        0 => '关闭'
    ];
    public $volunteer_activity_join_status = [
        1 => '开启',
        2 => '关闭'
    ];

    public $volunteer_activity_join_examine_status = [
        0 => '待审核',
        1 => '审核通过',
        2 => '审核拒绝'
    ];
    
    /**
     * 获取志愿者活动列表
     * @author: wanziyang
     * @date_time: 2020/5/29 16:33
     * @param array $where
     * @param int $page
     * @param bool|string $field
     * @param string $order
     * @param int $page_size
     * @param int $bind_type
     * @return array|null|\think\Model
     */
    public function getLimitVolunteerActivityList($where,$page=0,$field =true,$order='sort DESC,activity_id ASC',$page_size=10,$bind_type=1) {

        $db_volunteer_activity = new VolunteerActivity();
        $list = $db_volunteer_activity->getLimitVolunteerActivityList($where,$page,$field, $order, $page_size);
        $count = $db_volunteer_activity->getLimitVolunteerActivityCount($where);
        $nowtime=time();
        if (!$list || $list->isEmpty()) {
            $list = [];
        } else {
            foreach($list as $key=>&$val) {
                if($bind_type == 1){
                    $list[$key]['label'] = '社区活动';
                }else{
                    $list[$key]['label'] = '街道活动';
                }
                unset($list[$key]['richText']);
                $val['status_txt'] = $this->volunteer_activity_status[$val['status']];
                $val['add_time_txt'] = date('Y-m-d',$val['add_time']);
                if ($val['max_num']<=0) {
                    $val['num_txt'] = '不限';
                } else {
                    $val['num_txt'] = $val['max_num'].'/'.(intval($val['max_num'])-intval($val['join_num']));
                }
                $val['start_end_time_txt'] = date('Y-m-d H:i',$val['start_time']) .'~' .date('Y-m-d H:i',$val['end_time']);
                $val['img'] = unserialize($val['img']);
                $img = $val['img'];
                if(!empty($val['img'])){
                    foreach ($img as $k=>$v){
                        $img[$k] = replace_file_domain($v);
                    }
                    $val['img'] = $img[0];
                }
                if(($val['end_time']>100 && $val['end_time']<$nowtime) || ($val['close_time']>100 && $val['close_time']<$nowtime)){
                    $val['status']=0;
                }
            }
        }
        $out = [
            'list' => $list,
            'count' => $count ? $count : 0
        ];
        return $out;
    }

    /**
     * 获取活动详情
     * @author: wanziyang
     * @date_time: 2020/5/29 15:47
     * @param array $where
     * @param bool|string $field
     * @return array|null|\think\Model
     */
    public function getVolunteerActivity($where,$field =true) {
        $db_volunteer_activity = new VolunteerActivity();
        $info = $db_volunteer_activity->getOne($where, $field);
        if (!$info || $info->isEmpty()) {
            $info = [];
        } else {
            $db_volunteer_activity_join = new VolunteerActivityJoin();
            $where = [
                'activity_id' => $info['activity_id'],
                'join_status' => 1,
                'join_examine' => 1,
            ];
            $count = $db_volunteer_activity_join->getLimitVolunteerActivityJoinCount($where);
            $info['join_num'] = $count;
            $max_num = isset($info['max_num']) && $info['max_num'] > 0 ? intval($info['max_num']) : 0;
            $info['apply_limit_num'] = $max_num;
            if ($max_num > $count) {
                $activity_apply_remain_sum = $max_num - $count;
            } else {
                $activity_apply_remain_sum = 0;
            }
            $info['activity_apply_remain_sum'] = $activity_apply_remain_sum;
            //$info['apply_fee'] = 0;
            $info['status_txt'] = $this->volunteer_activity_status[$info['status']];
            $info['add_time_txt'] = date('Y-m-d H:i:s',$info['add_time']);
            $info['richText'] = htmlspecialchars_decode($info['richText']);
            if ($info['max_num']<=0) {
                $info['num_txt'] = '不限';
            } else {
                $info['num_txt'] = $info['max_num'].'/'.(intval($info['max_num'])-intval($info['join_num']));
            }
            if($info['close_time'] < time()){
                $info['is_allow'] = 0;
                $info['allow_txt']='报名已截止';
            }else{
                $info['is_allow'] = 1;
                $info['allow_txt']='我要报名';
            }
            $info['start_time'] = date('Y-m-d H:i',$info['start_time']);
            $info['end_time'] = date('Y-m-d H:i',$info['end_time']);
            $info['close_time'] = date('Y-m-d H:i',$info['close_time']);
            $imgList = [];
            if($info['img']) {
                $img = unserialize($info['img']);
                if($img) {
                    foreach ($img as $key => $val) {
                        $imgList[] = [
                            'response'=>replace_file_domain($val),
                            'uid' => $key + 1,
                            'name' => 'image.jpg',
                            'status' => 'done',
                            'url' => replace_file_domain($val),
                            'url_path' => $val,
                        ];
                    }
                }
            }
            $info['imgList'] = $imgList;

        }
        return $info;
    }

    /**
     * 删除志愿者活动
     * @author: wanziyang
     * @date_time: 2020/5/29 16:17
     * @param array $where
     * @return bool
     */
    public function delVolunteerActivity($where) {
        $db_volunteer_activity = new VolunteerActivity();
        $del = $db_volunteer_activity->delOne($where);
        return $del;
    }

    /**
     * 添加编辑志愿者活动
     * @author: wanziyang
     * @date_time: 2020/5/ ${TIME}
     * @param $data
     * @return bool
     * @throws \think\Exception
     */
    public function addAndEditVolunteer($data) {
        $db_volunteer_activity = new VolunteerActivity();
        if ($data && isset($data['activity_id']) && $data['activity_id']>0) {
            $where_activity = [];
            $where_activity[] = ['activity_id', '=', $data['activity_id']];
            $info = $db_volunteer_activity->getOne($where_activity);
            if(!$info || $info->isEmpty()) {
                throw new \think\Exception("编辑的活动不存在");
            }
            unset($data['activity_id']);
            $data['last_time'] = time();
            $set = $db_volunteer_activity->saveOne($where_activity,$data);
        } else {
            $data['add_time'] = time();
            $data['last_time'] = time();
            $set = $db_volunteer_activity->addOne($data);
        }
        return $set;
    }


    /**
     * 获取志愿者活动加入列表
     * @author: wanziyang
     * @date_time: 2020/5/29 16:33
     * @param array $where
     * @param int $page
     * @param bool|string $field
     * @param string $order
     * @param int $page_size
     * @return array|null|\think\Model
     */
    public function getLimitVolunteerActivityJoinList($where,$page=0,$field =true,$order='join_id ASC',$page_size=10) {

        $db_volunteer_activity_join = new VolunteerActivityJoin();
        $db_volunteer_activity = new VolunteerActivity();
        $list = $db_volunteer_activity_join->getLimitVolunteerActivityJoinList($where,$page,$field, $order, $page_size);
        $count = $db_volunteer_activity_join->getLimitVolunteerActivityJoinCount($where);
        if (!$list || $list->isEmpty()) {
            $list = [];
        } else {
            foreach($list as $key=>&$val) {
                $map = [];
                $map[] = ['activity_id','=',$val['activity_id']];
                $activity = $db_volunteer_activity->getOne($map,'active_name,is_need');
                $val['join_status_txt'] = $this->volunteer_activity_join_status[$val['join_status']];
                $val['join_examine_txt']=$this->volunteer_activity_join_examine_status[$val['join_examine']];
                $val['join_add_time_txt'] = date('Y-m-d H:i:s',$val['join_add_time']);
                $val['active_name'] = $activity['active_name'];
                $val['active_is_need'] = $activity['is_need'];
                if(isset($val['join_phone']) && !empty($val['join_phone'])){
                    $val['join_phone']=phone_desensitization($val['join_phone']);
                }
                if(isset($val['join_id_card']) && !empty($val['join_id_card'])){
                    $val['join_id_card']=idnum_desensitization($val['join_id_card']);
                }
            }
        }
        $out = [
            'list' => $list,
            'count' => $count ? $count : 0
        ];
        return $out;
    }


    /**
     * 获取活动加入详情
     * @author: wanziyang
     * @date_time: 2020/5/29 17:01
     * @param array $where
     * @param bool|string $field
     * @return array|null|\think\Model
     */
    public function getVolunteerActivityJoin($where,$field =true) {
        $db_volunteer_activity_join = new VolunteerActivityJoin();
        $info = $db_volunteer_activity_join->getOne($where, $field);
        if (!$info || $info->isEmpty()) {
            $info = [];
        } else {
            if (isset($info['status'])) {
                $info['status_txt'] = $this->volunteer_activity_status[$info['status']];
            }
            if (isset($info['add_time'])) {
                $info['add_time_txt'] = date('Y-m-d H:i:s',$info['add_time']);
            }
            if (isset($info['richText'])) {
                $info['richText'] = htmlspecialchars_decode($info['richText']);
            }
            if (isset($info['max_num'])) {
                if ($info['max_num']<=0) {
                    $info['num_txt'] = '不限';
                } else {
                    $info['num_txt'] = $info['max_num'].'/'.(intval($info['max_num'])-intval($info['join_num']));
                }
            }
            if (isset($info['start_time'])) {
                $info['start_time'] = date('Y-m-d H:i:s',$info['start_time']);
            }
            if (isset($info['end_time'])) {
                $info['end_time'] = date('Y-m-d H:i:s',$info['end_time']);
            }
        }
        return $info;
    }

    /**
     * 志愿者活动报名
     * @author lijie
     * @date_time 2020/09/09
     * @param $data
     * @return int|string
     */
    public function addVolunteerActivityJoin($data)
    {
        $db_volunteer_activity_join = new VolunteerActivityJoin();
        $res = $db_volunteer_activity_join->addOne($data);
        return $res;
    }

    /**
     * 报名人数+1
     * @param $where
     * @return mixed
     */
    public function setOne($where)
    {
        $db_volunteer_activity = new VolunteerActivity();
        $res = $db_volunteer_activity->setOne($where);
        return $res;
    }

    /**
     * Notes: 修改
     * @param $join_id
     * @param $data
     * @return bool
     */
    public function editActivityJoin($join_id,$data)
    {
        $db_volunteer_activity = new VolunteerActivity();
        $where[] = ['join_id','=',$join_id];
        $db_volunteer_activity_join = new VolunteerActivityJoin();
        $info = $db_volunteer_activity_join->getOne($where,'activity_id,join_examine,join_uid,pigcms_id,join_name,join_phone');
        $map[] = ['activity_id','=',$info['activity_id']];
        $activity_info = $db_volunteer_activity->getOne($map,'is_need,bind_id,bind_type,active_name,start_time,end_time');
        if($activity_info['is_need'] == 1 && !$data['join_id_card']){
            throw new \think\Exception("请填写身份证号");
        }
        if ($info && !$info->isEmpty()){
            $info=$info->toArray();
        }
        if($data['join_name'] != $info['join_name']){
            $info['join_name']=$data['join_name'];
        }
        if($data['join_phone'] != $info['join_phone']){
            $info['join_phone']=$data['join_phone'];
        }
        $info['bind_type']=$activity_info['bind_type'];
        $info['bind_id']=$activity_info['bind_id'];
        $info['active_name']=$activity_info['active_name'];
        $info['start_time']=$activity_info['start_time'];
        $info['end_time']=$activity_info['end_time'];
        $this->examineActivity($data['join_examine'],$info);
        $res = $db_volunteer_activity_join->saveOne($where,$data);
        return $res;
    }

    /**
     * 获取志愿者活动报名数量
     * @author lijie
     * @date_time 2020/12/05
     * @param $where
     * @return array|\think\Model|null
     */
    public function getVolunteerJoinCount($where)
    {
        $db_volunteer_activity_join = new VolunteerActivityJoin();
        $count = $db_volunteer_activity_join->getLimitVolunteerActivityJoinCount($where);
        return $count;
    }


    //todo 删除报名数据
    public function delActivityJoin($join_id,$data)
    {
        $db_volunteer_activity = new VolunteerActivity();
        $where[] = ['join_id','=',$join_id];
        $db_volunteer_activity_join = new VolunteerActivityJoin();
        $info = $db_volunteer_activity_join->getOne($where,'activity_id');
        if(empty($info)){
            throw new \think\Exception("该报名信息不存在");
        }
        $map[] = ['activity_id','=',$info['activity_id']];
        $activity_info = $db_volunteer_activity->getOne($map,'is_need,join_num');
        if(!$activity_info){
            throw new \think\Exception("该数据不存在");
        }
        Db::startTrans();
        try {
            $db_volunteer_activity_join->saveOne($where,$data);
            if(intval($activity_info['join_num']) > 0){
                $db_volunteer_activity->decNum($map);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }


    /**
     * 获取报名信息
     * @author: liukezhu
     * @date : 2022/6/24
     * @param $join_id
     * @param $activity_id
     * @return mixed
     * @throws \think\Exception
     */
    public function getActiveJoinInfo($join_id, $activity_id){
        $where[] = ['a.join_id','=',$join_id];
        $where[] = ['a.activity_id','=',$activity_id];
        $info = (new VolunteerActivityJoin())->getOnes($where,'a.join_id,a.activity_id,a.join_name,a.join_phone,a.join_id_card,b.active_name,a.join_remark,a.join_status,a.join_examine');
        if(empty($info)){
            throw new \think\Exception("该报名信息不存在");
        }
        if((int)$info['join_examine'] > 0){
            $info['is_examine']=true;
        }else{
            $info['is_examine']=false;
        }
        return $info;
    }
    
    //校验添加报名
    public function checkAddActivity($activity_id,$is_repeat,$pigcms_id){
        if(intval($is_repeat)){
            return ['error'=>true,'msg'=>'ok'];
        }
        $where=[
            ['activity_id','=',$activity_id],
            ['pigcms_id','=',$pigcms_id],
            ['join_status','in',[1,2]],
            ['join_examine','in',[0,1]]
        ];
        $info = (new VolunteerActivityJoin())->getOne($where,'join_id,join_examine');
        if($info && !$info->isEmpty()){
            if($info['join_examine'] == 1){
                return ['error'=>false,'msg'=>'您已报名过'];
            }else{
                return ['error'=>false,'msg'=>'您已报名(审核中)'];
            }
        }else{
            return ['error'=>true,'msg'=>'ok'];
        }
    }
    
    //审核报名
    public function examineActivity($join_examine,$info){
        $db_volunteer_activity = new VolunteerActivity();
        $HouseVillageUserBind=new HouseVillageUserBind();
        $area_street=new AreaStreet();
        if (!$info){
            return  true;
        }
        if($info['join_examine'] > 0){
            return  true;
        }
        if($join_examine <= 0){
            return true;
        }
        $param=[];
        if($info['pigcms_id']){
            $user_bind= $HouseVillageUserBind->getOne([
                ['pigcms_id','=',$info['pigcms_id']]
            ],'village_id');
            if($user_bind){
                $street_info=$area_street->getOne([
                    ['area_id','=',$info['bind_id']]
                ],'area_name,area_type');
                if($street_info){
                    $param=[
                        'uid'=>$info['join_uid'],
                        'village_id'=>$user_bind['village_id'],
                        'name'=>$info['join_name'],
                        'phone'=>$info['join_phone'],
                        'street_name'=>$street_info['area_name'],
                        'activity_name'=>$info['active_name'],
                    ];
                }
            }
        }
        if($join_examine ==1){
            //报名数量加一
            $db_volunteer_activity->setOne(['activity_id'=>$info['activity_id']]);
            if($param){
                $param['examine_txt']='审核通过';
            }
        }
        elseif ($join_examine ==2){
            if($param){
                $param['examine_txt']='审核拒绝';
            }
        }
        //写入消息记录
        $info['status']=$join_examine;
        $this->addActivityNews($info);
        //发送短信提醒
        if($param && $param['phone']){
            $this->sendExamineSms($param);
        }
        //发送微信模板通知
        if($param){
            $wx_param=[
                'village_id'=>$param['village_id'],
                'uid'=>$param['uid'],
                'pigcms_id'=>$info['pigcms_id'],
                'title'=>$info['active_name'],
                'examine_txt'=>'审核结果：'.($join_examine == 1 ? '通过' : '拒绝'),
            ];
            $this->sendExamineWx($wx_param);
        }
        return true;
    }
    
    //写入消息记录
    public function addActivityNews($param){
        if(!$param['pigcms_id']){
            return true;
        }
        if($param['status'] == 1){
            $title='您报名的【'.$param['active_name'].'】审核通过';
        }else{
            $title='您报名的【'.$param['active_name'].'】审核拒绝';
        }
        $data=[
            'pigcms_id'=>$param['pigcms_id'],
            'uid'=>$param['join_uid'],
            'type'=>3,
            'title'=>$title,
            'content'=>'时间：'.(date('Y-m-d',$param['start_time'])).'至'.(date('Y-m-d',$param['end_time'])),
            'status'=>$param['status'],
            'add_time'=>time()
        ];
        return (new HouseVenueNewsRecord())->add($data);
    }
    
    //短信发送通知
    public function sendExamineSms($param){
        $data = array('type' => 'fee_notice');
        $data['uid'] = $param['uid'];
        $data['village_id'] = $param['village_id'];
        $data['mobile'] = $param['phone'];
        $data['sendto'] = 'user';
        $data['mer_id'] = 0;
        $data['store_id'] = 0;
        $data['content'] = L_('尊敬的x1您好，您在x2预约的x3，x4', array(
            'x1' => $param['name'],
            'x2' => $param['street_name'],
            'x3' => $param['activity_name'],
            'x4' => $param['examine_txt'],
        ));
        $result = (new SmsService())->sendSms($data);
        return true;
    }

    //微信模板通知
    public function sendExamineWx($param){
        $user = (new User())->getOne(['uid' => $param['uid']],'openid');
        if (!$user || $user->isEmpty()){
            return  true;
        }
        if (mb_strlen($param['title'])>15) {
            $param['title']=msubstr($param['title'],0,15);
        }
        $href=get_base_url('pages/village/materialDiy/myMsg?village_id='.$param['village_id'].'&pigcms_id='.$param['pigcms_id'].'&source_type=3&url_type=village');
        $data=[
            'href' => $href,
            'wecha_id' => $user['openid'],
            'first' => $param['examine_txt'],
            'keyword1' => $param['title'],
            'keyword2' => '已发送',
            'keyword3' => date('H时i分', $_SERVER['REQUEST_TIME']),
            'remark' => '请点击查看详细信息！'
        ];
        return (new TemplateNewsService())->sendTempMsg('OPENTM400166399', $data);
    }
    
}