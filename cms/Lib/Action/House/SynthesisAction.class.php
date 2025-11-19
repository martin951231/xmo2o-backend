<?php
/**
 * 综治政务
 * Class SynthesisServiceAction
 * @author weili
 * @datetime 2020/8/5
 */

class SynthesisAction extends BaseAction
{
    public function index()
    {
        $village_id = $this->house_session['village_id'];
        $database_area_street = D('Area_street');//街道
        $villageInfo = D('House_village')->where(['village_id'=>$village_id])->field('street_id,community_id')->find();
        if($villageInfo['street_id'])
        {
            $streetInfo = $database_area_street->where(['area_id'=>$villageInfo['street_id']])->field('area_name,phone,detail_address')->find();
            if(!$streetInfo['phone'])
            {
                $streetInfo['phone']='暂无';
            }
            if(!$streetInfo['detail_address'])
            {
                $streetInfo['detail_address']='暂无';
            }
        }else{
            $streetInfo = '';
        }
        if($villageInfo['community_id']){
            $communityInfo = $database_area_street->where(['area_id'=>$villageInfo['community_id']])->field('area_name,phone,detail_address')->find();
            if(!$communityInfo['phone'])
            {
                $communityInfo['phone']='暂无';
            }
            if(!$communityInfo['detail_address'])
            {
                $communityInfo['detail_address']='暂无';
            }
        }else{
            $communityInfo='';
        }
        $newArr = [
            [
                'img'=>'/tpl/House/default/static/images/service/business.png',
                'title'=>'业主管理',
                'content'=>'街道/社区可线上管理业主，并可对特殊业主进行标注，方便后期实时跟踪记录'
            ],
            [
                'img'=>'/tpl/House/default/static/images/service/news.png',
                'title'=>'新闻管理',
                'content'=>'街道/社区可线上发布重要公告、通知等，有效提高新闻传达率'
            ],
            [
                'img'=>'/tpl/House/default/static/images/service/grid.png',
                'title'=>'网格化管理',
                'content'=>'各级可独立管理网格区域，上级可查看下级的区域及对应网格员，方便直接确认区域负责人'
            ],
//            [
//                'img'=>'/tpl/House/default/static/images/service/volunteer.png',
//                'title'=>'志愿活动管理',
//                'content'=>'街道/社区可面向所属区域下的业主招募志愿者，能够有效提高志愿者招募效率及增加招募渠道'
//            ],
//            [
//                'img'=>'/tpl/House/default/static/images/service/vote.png',
//                'title'=>'投票管理',
//                'content'=>'线上发布投票，业主线上填写提交，助力街道/社区快速推进业主相关投票工作'
//            ],
//            [
//                'img'=>'/tpl/House/default/static/images/service/take.png',
//                'title'=>'随手拍',
//                'content'=>'业主可线上提交相关投诉建议，街道/社区可线上进行回复处理，有效提高街道/社区文明建设'
//            ],
//            [
//                'img'=>'/tpl/House/default/static/images/service/gisnet.png',
//                'title'=>'gis网格化管理',
//                'content'=>'网格化管理升级版，助力街道、社区、小区建设更完整的网格化管理模式'
//            ],
//            [
//                'img'=>'/tpl/House/default/static/images/service/party.png',
//                'title'=>'党务管理',
//                'content'=>'街道/社区可线上管理党组织、党员等，方便信息统计及人员管理'
//            ],[
//                'img'=>'/tpl/House/default/static/images/service/partyactivity.png',
//                'title'=>'党内活动管理',
//                'content'=>'专属于党员的活动管理，线上活动添加、党员线上报名更方便、便捷'
//            ],
//            [
//                'img'=>'/tpl/House/default/static/images/service/financial.png',
//                'title'=>'网上政务管理',
//                'content'=>'街道/社区可线上管理政务相关的事项办理材料，方便业主线上实时查询'
//            ],
        ];
        $this->assign('list',$newArr);
        $this->assign('streetInfo',$streetInfo);
        $this->assign('communityInfo',$communityInfo);
        $this->display();
    }
}