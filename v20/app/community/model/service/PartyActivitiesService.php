<?php
/**
 * 智慧党建之党内活动管理相关
 * @author weili
 * @date 2020/9/22
 */

namespace app\community\model\service;

use app\community\model\db\PartyActivity;
use app\community\model\db\PartyActivityJoin;
class PartyActivitiesService
{
    /**
     * Notes: 获取党内活动列表
     * @param $street_id
     * @param $name
     * @param $page
     * @param $limit
     * @param $from
     * @return mixed
     * @author: weili
     * @datetime: 2020/9/22 11:40
     */
    public function PartyActivityList($street_id,$name,$page,$limit,$from='end')
    {
        $dbPartyActivity = new PartyActivity();
        $where[] = ['street_id','=',$street_id];
        if($from == 'front')
            $where[] = ['status','=',1];
        else
            $where[] = ['status','<>',-1];
        if($name)
        {
            $where[] = ['name','like','%'.$name.'%'];
        }
        $count = $dbPartyActivity->getCount($where);
        $order = 'sort desc,party_activity_id desc';
        $list = $dbPartyActivity->getPartyActivityModel($where,$page,true,$order,$limit);
        if ($list) {
            $list = $list->toArray();
            $now_time = intval(time());
            foreach ($list as $key=>&$val)
            {
                $list[$key]['label'] = '党建活动';
                if($val['add_time'])
                {
                    $val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
                }
                if($val['last_time'])
                {
                    $val['last_time'] = date('Y-m-d H:i:s',$val['last_time']);
                }
                if($val['start_time'])
                {
                    $val['start_time'] = date('Y-m-d',$val['start_time']);
                }
                if($val['end_time'])
                {
                    $val['end_time'] = date('Y-m-d',$val['end_time']);
                }
                if ($val['close_time'] && intval($val['close_time'])<$now_time) {
                    $val['is_join'] = false;
                } else {
                    $val['is_join'] = true;
                }
                if($val['close_time'])
                {
                    $val['close_time'] = date('Y-m-d',$val['close_time']);
                }
                if($val['img'])
                {
                    $val['aimg'] = explode(',',$val['img']);
                    if ($val['aimg']) {
                        foreach ($val['aimg'] as &$vs) {
                            if ($vs) {
                                $vs = dispose_url($vs);
                            }
                        }
                    }
                    if ( $val['aimg']) {
                        $val['img_title'] = reset($val['aimg']);
                    }
                }
                if($val['max_num'] == 0){
                    $val['surplus'] = 0;
                }else{
                    $val['surplus'] = $val['max_num']-$val['sign_up_num'];
                }
                $val['num'] = $val['max_num'].'/'.$val['surplus'];
            }
        } else {
            $list = [];
        }
        $data['count'] = $count;
        $data['list'] = $list;
        return $data;
    }

    /**
     * Notes:获取信息
     * @param $id
     * @return mixed
     * @author: weili
     * @datetime: 2020/9/22 16:19
     */
    public function getActivityInfo($id, $get_join=true)
    {
        $dbPartyActivity = new PartyActivity();
        $where[] = ['party_activity_id','=',$id];
        $info = $dbPartyActivity->getFind($where);
        $info['no_join_msg']='';
        $now_time = intval(time());
        $info['is_join'] = true;
        $img_url = [];
        if($info['img'])
        {
            $img = explode(',',$info['img']);
            foreach ($img as $key=>&$val)
            {
                $img_url[] = [
                    'uid'=>$key+1,
                    'name'=>'image.jpg',
                    'status'=>'done',
                    'url'=>dispose_url($val),
                    'url_path'=>$val,
                ];
            }
        }
        $info['img']=$img_url;
        $dbPartyActivityJoin = new PartyActivityJoin();
        $where_count = [];
        $where_count[] = ['party_activity_id', '=', $id];
        $where_count[] = ['status', '=', 1];
        $count = $dbPartyActivityJoin->getCount($where_count);
        $data['join_count'] = $count>0 ? $count:0;
        $info['join_count'] = $count>0 ? $count:0;
        $info['sign_up_num'] = $count>0 ? $count:0;
        $max_num = isset($info['max_num']) && $info['max_num'] > 0 ? intval($info['max_num']) : 0;
        $info['apply_limit_num'] = $max_num;
        if ($max_num > $count) {
            $activity_apply_remain_sum = $max_num - $count;
        } else {
            $activity_apply_remain_sum = 0;
        }
        $info['activity_apply_remain_sum'] = $activity_apply_remain_sum;
        $info['apply_fee'] = 0;
        if(intval($info['max_num']) > 0 && $info['join_count']>=$info['max_num']){
            //人数已满 不可报名
            $info['is_join'] = false;
            $info['no_join_msg']='人数已满';
        }
        if (isset($info['close_time']) && intval($info['close_time'])<$now_time) {
            $info['is_join'] = false;
            $info['no_join_msg']='报名已截止';
        }
        if (isset($info['start_time']) && $info['start_time']) {
            $info['start_time'] = date('Y-m-d',$info['start_time']);
        }
        if (isset($info['end_time']) && $info['end_time']) {
            $info['end_time'] = date('Y-m-d',$info['end_time']);
        }
        if (isset($info['close_time']) && $info['close_time']) {
            $info['close_time'] = date('Y-m-d',$info['close_time']);
        }
        $data['info'] = $info;
        return $data;
    }

    /**
     * Notes: 添加或者编辑数据
     * @param $data
     * @param $id
     * @param $street_id
     * @return bool|int|string
     * @author: weili
     * @datetime: 2020/9/22 16:19
     */
    public function subPartyActivity($data,$id,$street_id)
    {
        $dbPartyActivity = new PartyActivity();
        $img = '';
        $start_time = '';
        $end_time = '';
        if($data['activity_time'] && count($data['activity_time'])>0)
        {
            $start_time = strtotime($data['activity_time'][0]);
            $end_time = strtotime($data['activity_time'][1]);
        }
        if($data['img_arr'] && count($data['img_arr'])>0)
        {
            $img = implode(',',$data['img_arr']);
        }
        if($data['close_time']){
            $data['close_time'] = strtotime($data['close_time']);
            $close_time=date('Y-m-d',$data['close_time']).' 23:59:59';
            $data['close_time'] = strtotime($close_time);
        }
        unset($data['activity_time']);
        unset($data['img_arr']);
        $data['img'] = $img;
        $data['start_time'] = $start_time;
        $end_time=date('Y-m-d',$end_time).' 23:59:59';
        $end_time = strtotime($end_time);
        $data['end_time'] = $end_time;
        if($id)
        {
            $data['last_time'] = time();
            $where[] = ['party_activity_id','=',$id];
            $res = $dbPartyActivity->saveOne($where,$data);
        }else{
            $data['street_id'] = $street_id;
            $data['add_time'] = time();
            $res = $dbPartyActivity->addPartyActivityDb($data);
        }
        return $res;
    }

    /**
     * Notes: 删除数据（软删除）
     * @param $id
     * @return bool
     * @author: weili
     * @datetime: 2020/9/22 16:20
     */
    public function delActivity($id)
    {
        $dbPartyActivity = new PartyActivity();
        $where[] = ['party_activity_id','=',$id];
        $dbPartyActivityJoin = new PartyActivityJoin();
        $dbPartyActivityJoin->saveOne($where,['status'=>'-1']);
        $res=$dbPartyActivity->saveOne($where,['status'=>-1]);
        return $res;
    }

    /**
     * Notes: 获取报名列表
     * @param $id
     * @param $data
     * @param $page
     * @param $limit
     * @return array
     * @author: weili
     * @datetime: 2020/9/22 17:15
     */
    public function getActivityApplyList($id,$data,$page,$limit)
    {
        $dbPartyActivityJoin = new PartyActivityJoin();
        $where[] = ['party_activity_id','=',$id];
        $where[] = ['status','<>',-1];
        if($data['name'])
        {
            $where[] = ['user_name','like','%'.$data['name'].'%'];
        }
        if($data['phone'])
        {
            $where[] = ['user_phone','like','%'.$data['phone'].'%'];
        }
        $count = $dbPartyActivityJoin->getCount($where);
        $order = 'id desc';
        $list = $dbPartyActivityJoin->getList($where,$page,true,$order,$limit);
        foreach ($list as &$val)
        {
            $val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
            if($page>0){
                if(isset($val['user_phone']) && !empty($val['user_phone'])){
                    $val['user_phone']=phone_desensitization($val['user_phone']);
                }
                if(isset($val['id_card']) && !empty($val['id_card'])){
                    $val['id_card']=idnum_desensitization($val['id_card']);
                }
            }
        }
        $data = [];
        $data['count'] = $count;
        $data['list'] = $list;
        return $data;
    }

    /**
     * Notes: 获取报名列表
     * @param $id
     * @return mixed
     * @author: weili
     * @datetime: 2020/9/22 18:08
     */
    public function getActivityApplyInfo($id)
    {
        $dbPartyActivityJoin = new PartyActivityJoin();
        $dbPartyActivity = new PartyActivity();
        $where[] = ['id','=',$id];
        $info = $dbPartyActivityJoin->getOne($where);
        $map[] = ['party_activity_id','=',$info['party_activity_id']];
        $field = 'name';
        $activity = $dbPartyActivity->getFind($map,$field);
        $info['activity_name'] = $activity['name'];
        $info['add_time'] = date('Y-m-d H:i:s',$info['add_time']);
        $data['info'] = $info;
        return $data;
    }

    /**
     * Notes: 修改报名信息
     * @param $data
     * @param $id
     * @return bool
     * @author: weili
     * @datetime: 2020/9/22 18:08
     */
    public function editApply($data,$id)
    {
        $dbPartyActivityJoin = new PartyActivityJoin();
        $where=array();
        $where[] = ['id','=',$id];
        $res = $dbPartyActivityJoin->saveOne($where,$data);

        return $res;
    }

    /**
     * Notes:软删除
     * @param $id
     * @return bool
     * @author: weili
     * @datetime: 2020/9/22 18:08
     */
    public function delApply($id)
    {
        $dbPartyActivityJoin = new PartyActivityJoin();
        $where=array();
        $where[] = ['id','=',$id];
        $activityJoin=$dbPartyActivityJoin->getOne($where);
        $res = $dbPartyActivityJoin->saveOne($where,['status'=>-1]);
        if($activityJoin && !$activityJoin->isEmpty()){
            $party_activity_id=$activityJoin['party_activity_id'];
            $whereArr=array();
            $whereArr[]=array('party_activity_id','=',$party_activity_id);
            $whereArr[]=array('status','>=',0);
            $signNum=$dbPartyActivityJoin->getCount($whereArr);
            $signNum=$signNum>0 ?$signNum:0;
            $dbPartyActivity = new PartyActivity();
            $dbPartyActivity->saveOne(['party_activity_id'=>$party_activity_id],['sign_up_num'=>$signNum]);
        }
        
        return $res;
    }
}