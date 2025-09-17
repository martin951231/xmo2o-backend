<?php


namespace app\community\controller\street_api;


use app\community\controller\CommunityBaseController;
use app\community\model\service\AreaStreetService;
use app\community\model\service\VolunteerService;

class VolunteerController extends CommunityBaseController
{
    /**
     * 志愿者活动列表
     * @author lijie
     * @date_time 2020/09/12
     * @return \json
     */
    public function getVolunteerLists()
    {
        $street_id = $this->request->post('area_street_id',0);
        $service_area_street = new AreaStreetService();
        $where_street = [];
        $where_street[] = ['area_id', '=', $street_id];
        $street_info = $service_area_street->getAreaStreet($where_street);
        if ($street_info && $street_info['area_type']==1) {
            $data['title'] = '社区';
            $bind_type = 1;
        } elseif($street_info) {
            $data['title'] = '街道';
            $bind_type = 0;
        } else {
            return api_output_error(1001,'对应街道/社区不存在');
        }
        if(!$street_id)
            return api_output_error(1001,'缺少必传参数');
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',20);
        $service_volunteer = new VolunteerService();
        $where['bind_id'] = $street_id;
        $where['bind_type'] = $bind_type;
        $where['status'] = 1;
        $field = 'status,richText,img,active_name,start_time,end_time,add_time,activity_id,is_need,close_time';
        $data = $service_volunteer->getLimitVolunteerActivityList($where,$page,$field,$order='sort DESC,activity_id ASC',$limit,$bind_type);
        $data['share_info']=[
            'share_switch'=>intval(cfg('share_switch')),
            'share_img'=> cfg('site_url') . '/static/wxapp/fenxiang/default.png',
            'share_wx'=>cfg('pay_wxapp_important') && cfg('pay_wxapp_username') ? 'wxapp' : 'h5',
            'userName'=>cfg('pay_wxapp_username'),
            'title'=>cfg('area_street_active_alias'),
            'info'=>cfg('area_street_active_alias').'，进入可查看详情。'
        ];
        return api_output(0,$data);
    }

    /**
     * 志愿者活动详情
     * @author lijie
     * @date_time 2020/09/12
     * @return \json
     */
    public function getVolunteerDetail()
    {
        $activity_id = $this->request->post('activity_id',0);
        if(!$activity_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        $pigcms_id = $this->request->param('pigcms_id',0,'int');
        if(!$pigcms_id){
            return api_output_error(1001,'当前无住户身份，请重新选择');
        }
        $service_volunteer = new VolunteerService();
        $where['activity_id'] = $activity_id;
        $field = 'activity_id,max_num,status,richText,img,active_name,start_time,end_time,add_time,join_num,is_need,close_time,is_repeat';
        $data = $service_volunteer->getVolunteerActivity($where,$field);
        $data['share_info']=[
            'share_switch'=>intval(cfg('share_switch')),
            'share_img'=>isset($data['imgList'][0]['url']) ? $data['imgList'][0]['url'] : cfg('site_url') . '/static/wxapp/fenxiang/default.png',
            'share_wx'=>cfg('pay_wxapp_important') && cfg('pay_wxapp_username') ? 'wxapp' : 'h5',
            'userName'=>cfg('pay_wxapp_username'),
            'title'=>$data['active_name'],
            'info'=>stringText($data['richText'])
        ];
        if($data['richText'] && preg_match( '/src=[\'|\"]\/upload\/ueditor\/images/i', $data['richText'], $matches ) ){
            $data['richText']=str_replace('/upload/ueditor/images',cfg('site_url').'/upload/ueditor/images',$data['richText']);
        }
        $result=$service_volunteer->checkAddActivity($activity_id,$data['is_repeat'],$pigcms_id);
        if(!$result['error']){
            $data['is_allow']=0;
            $data['allow_txt']=$result['msg'];
        }
        return api_output(0,$data);
    }

    /**
     * 报名
     * @author lijie
     * @date_time 2020/09/12
     * @return \json
     */
    public function joinVolunteerActivity()
    {
        $join_phone = $this->request->post('join_phone','');
        $join_name = $this->request->post('join_name','');
        $join_id_card = $this->request->post('join_id_card','');
        $activity_id = $this->request->post('activity_id',0);
        $join_remark = $this->request->post('join_remark','');
        $pigcms_id = $this->request->param('pigcms_id',0,'int');
        if(!$pigcms_id){
            return api_output_error(1001,'当前无住户身份，请重新选择');
        }
        $service_volunteer = new VolunteerService();
        $res = $service_volunteer->getVolunteerActivity(['activity_id'=>$activity_id]);
        if(strtotime($res['close_time']) < time()){
            return api_output_error(1001,'活动报名已截止');
        }
        $result=$service_volunteer->checkAddActivity($activity_id,$res['is_repeat'],$pigcms_id);
        if(!$result['error']){
            return api_output_error(1001,$result['msg']);
        }
        if($res['is_need'] == 1){
            if(!$join_id_card)
                return api_output_error(1001,'缺少身份证');
        }
        if(!$join_name || !$join_phone || !$activity_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        if(!cfg('international_phone') && !preg_match('/^[0-9]{11}$/',$join_phone)) {
            return api_output_error(1001,'手机号格式错误');
        }
        $join_uid = intval($this->request->log_uid);
        if (!$join_uid) {
            return api_output_error(1002, "没有登录");
        }
        if($res['max_num'] > 0)
        {
            $count = $service_volunteer->getVolunteerJoinCount(['activity_id'=>$activity_id,'join_status'=>1,'join_examine'=>1]);
            if($res['max_num'] <= $count)
                return api_output_error(1001,'当前报名人数已满');
        }
        $params['join_phone'] = $join_phone;
        $params['join_name'] = $join_name;
        $params['join_id_card'] = $join_id_card;
        $params['activity_id'] = $activity_id;
        $params['join_uid'] = $join_uid;
        $params['pigcms_id'] = $pigcms_id;
        $params['join_remark'] = $join_remark;
        $params['join_add_time'] = time();
        $res = $service_volunteer->addVolunteerActivityJoin($params);
        if($res){
            //$service_volunteer->setOne(['activity_id'=>$activity_id]);
            return api_output(0,['msg'=>'报名成功,等待审核'],'报名成功,等待审核');
        } else{
            return api_output_error(1001,'服务异常');
        }
    }
}