<?php


namespace app\community\controller\street_api;


use app\community\model\service\HouseVillageUserLabelService;
use app\community\model\service\PartyActivityJoinService;
use app\community\controller\CommunityBaseController;
use app\community\model\service\PartyActivitiesService;
use app\community\model\service\PartyActivityService;

class PartyActivityController extends CommunityBaseController
{
    /**
     * 党内活动管理
     * @author lijie
     * @date_time 2020/10/14
     * @return \json
     */
    public function getActivityLists()
    {
        $street_id = $this->request->post('area_street_id',0); // 街道id
        if(!$street_id)
            return api_output_error(1001,'缺少必传参数');
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',20);
        $service_activity = new PartyActivitiesService();
        $data = $service_activity->PartyActivityList($street_id,'',$page,$limit,'front');
        return api_output(0,$data);
    }

    /**
     * 党内活动详情
     * @author lijie
     * @date_time 2020/10/14
     * @return \json
     */
    public function activityDetail()
    {
        $party_activity_id = $this->request->post('party_activity_id',0);
        if(!$party_activity_id){
            return api_output_error(1001,'缺少必传参数');
        }
        $user_id = intval($this->request->log_uid);
        if (!$user_id) {
            return api_output_error(1002, "没有登录");
        }
        // 查询下当前登录者是否具备党员身份
        $service_house_village_user_label = new HouseVillageUserLabelService();
        $where_member = [];
        $where_member[] = ['b.status','=',1];
        $where_member[] = ['b.uid','=',$user_id];
        $where_member[] = ['l.user_political_affiliation','=',1];
        $member_count = $service_house_village_user_label->getCount($where_member);
        if(!$member_count || $member_count<=0) {
            return api_output(1001,  [],'您不是党员无法浏览当前页面');
        }
        $service_activity = new PartyActivitiesService();
        $data = $service_activity->getActivityInfo($party_activity_id);
        
        return api_output(0,$data);
    }

    /**
     * 党内活动报名
     * @author lijie
     * @date_time 2020/10/14
     * @return \json
     */
    public function activityJoin()
    {
        $user_name = $this->request->post('user_name','');
        $user_phone = $this->request->post('user_phone','');
        $id_card = $this->request->post('id_card','');
        $desc = $this->request->post('desc','');
        $party_activity_id = $this->request->post('party_activity_id',0);
        if(!$user_name) {
            return api_output_error(1001,'请填写姓名');
        }
        if(!$user_phone) {
            return api_output_error(1001,'请填写手机号码');
        }
        if(!cfg('international_phone') && !preg_match('/^[0-9]{11}$/',$user_phone)){
            return api_output_error(1001, '请输入有效的手机号');
        }
        if(!$party_activity_id) {
            return api_output_error(1001,'缺少参与活动对象');
        }
        $user_id = intval($this->request->log_uid);
        if (!$user_id) {
            return api_output_error(1002, "没有登录");
        }
        $service_activity = new PartyActivitiesService();
        $msg = $service_activity->getActivityInfo($party_activity_id,true);
        if (!$id_card && $msg && isset($data['id_card_status']) && $msg['id_card_status']==1) {
            return api_output_error(1001,'请填写身份证');
        }
        if ($id_card && is_idcard($id_card) == false) {
            return api_output_error(1001,'请填写有效身份证');
        }
        if ($msg && isset($msg['info']) && (!isset($msg['info']['is_join']) || !$msg['info']['is_join'])) {
            return api_output_error(1001,'当前不能参加');
        }
        $max_num=$msg['info']['max_num'];
        $join_count=$msg['join_count'];
        if ($max_num>0 && $join_count>=$max_num) {
            return api_output_error(1001,'当前报名人数已满');
        }
        $data = [];
        $data['uid'] = $user_id;
        $data['user_name'] = $user_name;
        $data['user_phone'] = $user_phone;
        $data['id_card'] = $id_card;
        $data['desc'] = $desc;
        $data['add_time'] = time();
        $data['party_activity_id'] = $party_activity_id;
        $service_party_activity_join = new PartyActivityJoinService();
        $res = $service_party_activity_join->addPartyActivityJoin($data);
        if($res){
            $service_party_activity = new PartyActivityService();
            $service_party_activity->setOne(['party_activity_id'=>$party_activity_id]);
            return api_output(0,'','报名成功');
        } else{
            return api_output_error('1001','服务异常');
        }
    }
}