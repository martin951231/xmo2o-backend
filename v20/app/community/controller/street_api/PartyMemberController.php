<?php


namespace app\community\controller\street_api;

use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseVillageUserLabelService;
use app\community\model\service\AreaStreetService;
use app\community\model\service\AreaStreetPartyBuildService;
use app\community\model\service\AreaStreetMeetingLessonService;
use app\community\model\service\PartyActivitiesService;
use app\community\model\service\PartyBranchService;
use app\community\model\service\PartyMemberService;


class PartyMemberController extends CommunityBaseController
{

    /**
     * Notes: 党员首页
     * @return \json
     * @author: wanzy
     * @date_time: 2020/11/25 14:32
     */
    public function index()
    {
        $street_id = $this->request->post('area_street_id',0);
        if(!$street_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        $user_id = intval($this->request->log_uid);
        if (!$user_id) {
            return api_output_error(1002, "没有登录");
        }
        $service_area_street = new AreaStreetService();
        $service_house_village_user_label = new HouseVillageUserLabelService();
        // 查询下当前登录者是否具备党员身份
        $where_member = [];
        $where_member[] = ['b.status','=',1];
        $where_member[] = ['b.uid','=',$user_id];
        $where_member[] = ['l.user_political_affiliation','=',1];
        $member_count = $service_house_village_user_label->getCount($where_member);
        $data = [];
        if(!$member_count || $member_count<=0) {
            return api_output(0,['member_count' => 0, 'msg' => '您不是党员无法浏览当前页面']);
        }
        $data['member_count'] = $member_count;


        //-----------------------------------首页党务信息----------------------------------------
        $where_street = [];
        $where_street[] = ['area_id', '=', $street_id];
        $street_info = $service_area_street->getAreaStreet($where_street);
        if ($street_info && $street_info['area_type']==1) {
            $data['title'] = '社区';
        } elseif($street_info) {
            $data['title'] = '街道';
        } else {
            return api_output_error(1001,'对应街道/社区不存在');
        }

        //------------------必读党课---------------
        $area_street_meeting_lesson_service = new AreaStreetMeetingLessonService();
        $where = [];
        $where[] = ['area_id','=',$street_id];
        $where[] = ['status','=',1];
        $field = 'title,add_time,title_img,meeting_id';
        $meet_list = $area_street_meeting_lesson_service->getMeetingLessonLists($where,$field,1,4,'is_hot,meeting_id DESC');
        $data['meeting_list'] =  $meet_list;

        //-----------------党员活动----------------

        $service_activity = new PartyActivitiesService();
        $active_list = $service_activity->PartyActivityList($street_id,'',1,1,'front');
        if ($active_list['list']) {
            $active_list['list'] = reset($active_list['list']);
        }
        $data['active_list'] =  $active_list;

        //-----------------------------------新闻----------------------------------------
        $area_street_party_build_service = new AreaStreetPartyBuildService();
        $where = [];
        $where[] = ['area_id','=',$street_id];
        $where[] = ['cat_status','=',1];
        $field = 'cat_id,cat_name';
        $news_category_lists = $area_street_party_build_service->getPartyBuildCategoryLists($where,$field,'cat_sort DESC');
        if ($news_category_lists) {
            $news_category_lists = $news_category_lists->toArray();
            array_unshift($news_category_lists,['cat_id'=>0,'cat_name'=>'全部']);
            $data['news_list'] = [];
            $data['news_list']['news_tab'] = $news_category_lists;
        }


        //-----------------------------------优秀党员---------------------------------------------
        $where = [];
        if ($street_info && $street_info['area_type']==1) {
            $where[] = ['v.community_id', '=', $street_id];
        } else {
            $where[] = ['v.street_id', '=', $street_id];
        }
        $where[] = ['l.user_political_affiliation', '=', 1];
        $where[] = ['l.is_excellent', '=', 1];
        $where[] = ['b.status','<>',4];
        $part_member_lists = $service_house_village_user_label->getPartyMemberLists($where,'l.logo,b.name,l.part_time',0,0,'l.id DESC');
        $data['partyMember'] = $part_member_lists;
        $data['top_title'] = $street_info['area_name'].'党建';

        $data['share_info']=[
            'share_switch'=>intval(cfg('share_switch')),
            'share_img'=>cfg('site_url') . '/static/wxapp/fenxiang/default.png',
            'share_wx'=>cfg('pay_wxapp_important') && cfg('pay_wxapp_username') ? 'wxapp' : 'h5',
            'userName'=>cfg('pay_wxapp_username'),
            'title'=>'党建首页',
            'info'=>'党建首页，进入可查看详情。'
        ];
        return api_output(0,$data);
    }


    /**
     * 获取党员列表
     * @author lijie
     * @date_time 2020/09/12
     * @return \json
     */
    public function getPartyMemberLists()
    {
        $street_id = $this->request->post('area_street_id',0); // 街道id
        if(!$street_id){
            return api_output_error(1001,'缺少必传参数');
        }

        $service_area_street = new AreaStreetService();
        $where_street = [];
        $where_street[] = ['area_id', '=', $street_id];
        $tmp_street_id=$street_id;
        $street_info = $service_area_street->getAreaStreet($where_street);
        $where = [];
        if ($street_info && $street_info['area_type']==1) {
            $where[] = ['v.community_id','=',$street_id];
            $data['title'] = '社区';
            $tmp_street_id=$street_info['area_pid'];
        } elseif($street_info) {
            $where[] = ['v.street_id','=',$street_id];
            $data['title'] = '街道';
        } else {
            return api_output_error(1001,'对应街道/社区不存在');
        }
        $partyBranchService=new PartyBranchService();
        $partyBranch=$partyBranchService->getPartyBranchAll($tmp_street_id);
        $party_ids=array();
        if($partyBranch && !$partyBranch->isEmpty()){
            foreach ($partyBranch as $pvv){
                $party_ids[]=$pvv['id'];
            }
            $where[] = ['pbu.party_id','in',$party_ids];
        }
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',20);
        $service_house_village_user_label = new HouseVillageUserLabelService();
        $where[] = ['l.user_political_affiliation','=',1];
        $where[] =['pbu.party_status','in',[1]];
        $where[]=['b.status', '<>', 4];
        $is_good_party=0;
        if($this->request->post('is_excellent',0)) {
            // 查询优秀党员
            $where[] = ['pbu.is_good_party','=',1];
            $is_good_party=1;
        }
        $field = 'l.logo,b.name,l.part_time,p.name as party_branch_name';
        $order = 'l.id DESC';
        $count=$service_house_village_user_label->getCount($where);
        $count=$count>0 ? $count:0;
        $partyMemberService= new PartyMemberService();

        $countWorker=$partyMemberService->getWorkerMemberListCount($tmp_street_id);
        $countX=$count+$countWorker;
        $param=array('countMember'=>$count,'street_id'=>$tmp_street_id);
        if($is_good_party==1){
            $param=array();
        }
        $part_member_lists = $service_house_village_user_label->getPartyMemberLists($where,$field,$page,$limit,$order,$param);

        return api_output(0,['list'=>$part_member_lists,'count'=>$countX,'share_info'=>[
            'share_switch'=>intval(cfg('share_switch')),
            'share_img'=> cfg('site_url') . '/static/wxapp/fenxiang/default.png',
            'share_wx'=>cfg('pay_wxapp_important') && cfg('pay_wxapp_username') ? 'wxapp' : 'h5',
            'userName'=>cfg('pay_wxapp_username'),
            'title'=>'党员列表',
            'info'=>'党员列表，进入可查看详情。'
        ]]);
    }


    /**
     * 党员详情
     * @author: liukezhu
     * @date : 2022/10/31
     * @return \json
     * @throws \think\Exception
     */
    public function getPartyMemberDetails(){
        $village_id = $this->request->post('village_id',0,'intval');
        $pigcms_id = $this->request->post('pigcms_id',0,'intval');
        if(!$village_id){
            return api_output_error(1001,'缺少必传参数');
        }
        if(!$pigcms_id){
            return api_output_error(1001,'未获取到您的住户身份！');
        }
        $data = (new HouseVillageUserLabelService())->getPartyMemberInfo($village_id,$pigcms_id);
        return api_output(0,['data'=>$data]);
    }

}