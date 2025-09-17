<?php
/**
 * 三会一课相关service
 * @author weili
 * @date 2020/9/18
 */

namespace app\community\model\service;

use app\community\model\db\AreaStreetMeetingLessonCategory;
use app\community\model\db\AreaStreetMeetingLesson;
use app\community\model\db\AreaStreetMeetingLessonReply;
use app\community\model\db\AreaStreetConfig;
use app\community\model\db\HouseVillageUserLabel;
use app\community\model\db\User;
use app\community\model\db\AreaStreet;

use app\common\model\service\weixin\TemplateNewsService;

class MeetingLessonService
{
    public $PartyBranchType=[
        ['key'=>1,'value'=>'支部党员大会','color'=>'RGBA(140, 211, 246, 1)'],
        ['key'=>2,'value'=>'支部委员会','color'=>'RGBA(21, 240, 225, 1)'],
        ['key'=>3,'value'=>'党小组会','color'=>'RGBA(229, 215, 84, 1)'],
        ['key'=>4,'value'=>'党课','color'=>'RGBA(68, 103, 227, .5)'],
    ];

    public $PartyBranchTypeSing=[
        1=>'支部党员大会',
        2=>'支部委员会',
        3=>'党小组会',
        4=>'党课',
    ];


    /**
     * Notes: 获取了列表
     * @param $page
     * @param $limit
     * @return mixed
     * @author: weili
     * @datetime: 2020/9/18 11:55
     */
    public function getLessonClass($area_id,$name,$page,$limit)
    {
        $dbAreaStreetMeetingLessonCategory = new AreaStreetMeetingLessonCategory();
        $where[] = ['area_id','=',$area_id];
        $where[] = ['cat_status','<>',-1];
        if($name){
            $where[] = ['cat_name','like','%'.$name.'%'];
        }
        $count = $dbAreaStreetMeetingLessonCategory->getCount($where);
        $list = $dbAreaStreetMeetingLessonCategory->getLists($where,true,$order='cat_sort DESC',$page,$limit);

        if($list){
            foreach ($list as $key=>&$val){
                $val['type']=!empty($val['type']) ? $this->PartyBranchTypeSing[$val['type']] : '--';
            }
        }


        $data['count'] = $count;
        $data['list'] = $list;
        return $data;
    }

    /**
     * Notes: 获取详情
     * @param $id
     * @return array|\think\Model|null
     * @author: weili
     * @datetime: 2020/9/18 13:25
     */
    public function getClassInfo($id)
    {
        $dbAreaStreetMeetingLessonCategory = new AreaStreetMeetingLessonCategory();
        $where[] = ['cat_id','=',$id];
        $info = $dbAreaStreetMeetingLessonCategory->getFind($where);
        $data['info'] =$info;
        return $data;
    }

    /**
     * Notes: 添加/编辑三会一课分类
     * @param $data
     * @param $id
     * @return AreaStreetMeetingLessonCategory|int|string
     * @author: weili
     * @datetime: 2020/9/18 13:42
     */
    public function saveClass($data,$id)
    {
        $dbAreaStreetMeetingLessonCategory = new AreaStreetMeetingLessonCategory();
        $where[] = ['cat_id','=',$id];
        $info = $dbAreaStreetMeetingLessonCategory->getFind($where);
        if(!$id)
        {
            if($info['cat_name'] == $data['cat_name'])
            {
                throw new \think\Exception("分类名已存在");
            }else{
                $res = $dbAreaStreetMeetingLessonCategory->addData($data);
            }
        }else{
            $res = $dbAreaStreetMeetingLessonCategory->editData($where,$data);
        }
        return $res;
    }

    /**
     * Notes: 删除分类
     * @param $id
     * @return AreaStreetMeetingLessonCategory
     * @author: weili
     * @datetime: 2020/9/18 16:51
     */
    public function delClass($id)
    {
        $where[] = ['cat_id','=',$id];
        $dbAreaStreetMeetingLessonCategory = new AreaStreetMeetingLessonCategory();
        $dbAreaStreetMeetingLesson = new AreaStreetMeetingLesson();
        $dbAreaStreetMeetingLesson->editData($where,['status'=>'-1']);
        $res = $dbAreaStreetMeetingLessonCategory->editData($where,['cat_status'=>'-1']);
        return $res;
    }
    /**
     * Notes: 获取会议列表
     * @param $cat_id
     * @param $street_id
     * @param $page
     * @param $limit
     * @return mixed
     * @author: weili
     * @datetime: 2020/9/18 14:45
     */
    public function getMeetingList($cat_id,$street_id,$title,$date,$page,$limit)
    {
        $dbAreaStreetMeetingLesson = new AreaStreetMeetingLesson();
        $where[] = ['cat_id','=',$cat_id];
        $where[] = ['status','<>',-1];
        $where[] = ['area_id','=',$street_id];
        if($title)
        {
            $where[] = ['title','like','%'.$title.'%'];
        }
        if($date)
        {
            $start_time = strtotime($date[0]);
            $end_time = strtotime($date[1]);
            $where[] = ['add_time','>=',$start_time];
            $where[] = ['add_time','<=',$end_time];
        }
        $count = $dbAreaStreetMeetingLesson->getCount($where);
        $list = $dbAreaStreetMeetingLesson->getLists($where,true,$page,$limit);
        if($list && count($list)>0) {
            foreach ($list as &$val) {
                $val['add_time'] = date('Y-m-d H:i:s', $val['add_time']);
                if($val['is_notice'] == 1)
                {
                    $val['is_notice_size'] = '是';
                }else{
                    $val['is_notice_size'] = '否';
                }
                if (isset($val['status']) && 0==$val['status']) {
                    $val['status'] = 2;
                }
            }
        }
        $data['count'] = $count;
        $data['list'] = $list;
        return $data;
    }

    /**
     * Notes: 获取会议详情
     * @param $meeting_id
     * @return mixed
     * @author: weili
     * @datetime: 2020/9/18 14:52
     */
    public function getMeetingInfo($meeting_id)
    {
        $dbAreaStreetMeetingLesson = new AreaStreetMeetingLesson();
        $where[] = ['meeting_id','=',$meeting_id];
        $info = $dbAreaStreetMeetingLesson->getOne($where,true);
        if($info)
        {
            $info['add_time'] = date('Y-m-d H:i:s',$info['add_time']);
            $info['title_img'] = replace_file_domain($info['title_img']);
        }
        $data['info'] = $info;
        return $data;
    }

    /**
     * Notes: 添加、编辑数据
     * @param $data
     * @param $meeting_id
     * @return AreaStreetMeetingLesson|int|string
     * @author: weili
     * @datetime: 2020/9/18 15:11
     */
    public function saveMeeting($data,$meeting_id)
    {
        $dbAreaStreetMeetingLesson = new AreaStreetMeetingLesson();
        if($meeting_id)
        {
            $where[] = ['meeting_id','=',$meeting_id];
            $res = $dbAreaStreetMeetingLesson->editData($where,$data);
        }else{
            $data['add_time'] = time();
            $res =  $dbAreaStreetMeetingLesson->addData($data);
        }
        return $res;
    }

    /**
     * Notes: 删除会议
     * @param $id
     * @return AreaStreetMeetingLesson
     * @author: weili
     * @datetime: 2020/9/18 16:56
     */
    public function delMeeting($id)
    {
        $where[] = ['meeting_id','=',$id];
        $dbAreaStreetMeetingLesson = new AreaStreetMeetingLesson();
        $res = $dbAreaStreetMeetingLesson->editData($where,['status'=>'-1']);
        return $res;
    }

    /**
     * Notes:发送微信通知（未完成 注：目前只是修改状态，暂未接入微信群发通知 2020/9/21）
     * @param $id
     * @return AreaStreetMeetingLesson
     * @author: weili
     * @datetime: 2020/9/18 17:45
     */
    public function sendNotice($id,$street_id)
    {
        $dbAreaStreetMeetingLesson = new AreaStreetMeetingLesson();
        $dbHouseVillageUserLabel =new HouseVillageUserLabel();
        //获取所有党员 start
        $map[] = ['l.user_political_affiliation','=',1];
        $map[] = ['v.street_id','=',$street_id];
        $field='l.id,b.pigcms_id,b.authentication_field,b.village_id,b.uid,b.name,b.phone,b.id_card,b.single_id,b.floor_id,b.layer_id,b.vacancy_id,p.name as party_name,pbu.party_status';
        $count = $dbHouseVillageUserLabel->getCount($map);
        $list = $dbHouseVillageUserLabel->getPartyMemberLists($map,$field,1,$count);
        $where = [];
        $where[] = ['meeting_id','=',$id];
        $info = $dbAreaStreetMeetingLesson->getOne($where,true);
        if (empty($info)) {
            throw new \think\Exception("发送信息不存在");
        }
        //获取所有党员 end
        if(!empty($list)) {
            $list = $list->toArray();
            if (!empty($list)) {
                $user = new User();
                $db_area_street = new AreaStreet();
                $where_area = [];
                $where_area[] = ['area_id','=',$street_id];
                $area_street = $db_area_street->getOne($where_area);
                $base_url = cfg('site_url');
                $keyword1 = (isset($area_street['area_type']) && 1==$area_street['area_type']) ? '社区三会一课':'街道三会一课';
                $templateNewsService = new TemplateNewsService();
                $is_send = false;
                foreach ($list as $item) {
                    if ($item['uid']) {
                        $openid = $user->getOne(['uid' => $item['uid']]);
                        if (!empty($openid)) {
                            $href = $base_url."/packapp/village/pages/street/threeParty/oneClassDetails?news_id={$id}&area_street_id={$street_id}";
                            $datamsg = [
                                'tempKey' => 'OPENTM405462911',
                                'dataArr' => [
                                    'href' => $href,
                                    'wecha_id' => $openid['openid'],
                                    'first' => '您好，' . $area_street['area_name'] . '发布了一条新的消息【'.$info['title'].'】\n',
                                    'keyword1' =>(isset($item['party_name']) && $item['party_name'])?$item['party_name']:$keyword1,
                                    'keyword2' => '已发送',
                                    'keyword3' => date('H:i'),
                                    'keyword4' => isset($item['name'])?$item['name']:'',
                                    'remark' => '\n请点击查看详细信息！'
                                ]
                            ];
                            //调用微信模板消息
                            $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
                            $is_send = true;
                        }
                    }
                }
                if ($is_send) {
                    $where = [];
                    $where[] = ['meeting_id', '=', $id];
                    $res = $dbAreaStreetMeetingLesson->editData($where, ['is_notice' => '1']);
                } else {
                    throw new \think\Exception("沒有符合发送条件的党员");
                }
            } else {
                throw new \think\Exception("该街道下没有党员");
            }
            return $res;
        }else{
            throw new \think\Exception("该街道下没有党员");
        }
    }
    /**
     * Notes:三会一课评论列表
     * @param $street_id
     * @param $meeting_id
     * @param $page
     * @param $limit
     * @return mixed
     * @author: weili
     * @datetime: 2020/9/21 14:37
     */
    public function meetingReplyList($street_id,$meeting_id,$page,$limit)
    {
        $dbAreaStreetConfig = new AreaStreetConfig();
        $dbAreaStreetMeetingLessonReply = new AreaStreetMeetingLessonReply();
        $where[] = ['r.status','<>',-1];
        $where[] = ['r.area_id','=',$street_id];
        $where[] = ['r.meeting_id','=',$meeting_id];
        $count = $dbAreaStreetMeetingLessonReply->getCount($where);
        $field = 'r.*,u.nickname';
        $order = 'is_read asc,pigcms_id desc';
        $list = $dbAreaStreetMeetingLessonReply->getLists($where,$field,$page,$limit,$order);
        foreach ($list as &$val){
            if($val['add_time'])
            {
                $val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
            }
        }
        $map[] = ['area_id','=',$street_id];
        $info = $dbAreaStreetConfig->getFind($map,'lessons_meeting_witch');
        if(!$info)
        {
            $info['lessons_meeting_witch'] = false;//默认关闭
        }
        $data['count'] = $count;
        $data['list'] = $list;
        $data['info'] = $info;
        return $data;
    }
    /**
     * Notes:三会一课操作（删除 已读 前台是否显示）
     * @param $id
     * @param $data
     * @return AreaStreetMeetingLessonReply
     * @throws \think\Exception
     * @author: weili
     * @datetime: 2020/9/21 14:38
     */
    public function meetingAction($id,$data)
    {
        if($data && count($data)>0) {
            $dbAreaStreetMeetingLessonReply = new AreaStreetMeetingLessonReply();
            $where[] = ['pigcms_id', '=', $id];
            $res = $dbAreaStreetMeetingLessonReply->editData($where, $data);
            return $res;
        }else{
            throw new \think\Exception("要修改的值不能为空");
        }
    }
    /**
     * Notes:三会一课评论开关
     * @param $street_id
     * @param $status
     * @return AreaStreetConfig|int|string
     * @author: weili
     * @datetime: 2020/9/21 14:38
     */
    public function replySwitch($street_id,$status)
    {
        $dbAreaStreetConfig = new AreaStreetConfig();
        $where[] = ['area_id','=',$street_id];
        $info = $dbAreaStreetConfig->getFind($where);
        $data = ['lessons_meeting_witch'=>$status];
        if($info)
        {
            $res = $dbAreaStreetConfig->saveData($where,$data);
        }else{
            $data['area_id'] = $street_id;
            $res = $dbAreaStreetConfig->addData($data);
        }
        return $res;
    }
}