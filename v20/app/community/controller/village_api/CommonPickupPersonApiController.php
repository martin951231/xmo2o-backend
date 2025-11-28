<?php


namespace app\community\controller\village_api;


use app\common\model\service\config\ConfigCustomizationService;
use app\community\model\service\CommonPickupPersonService;
use app\community\controller\CommunityBaseController;
use app\community\model\db\HouseVillage;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseVillageUserBindService;
use app\community\model\service\HouseVillageUserVacancyService;

class CommonPickupPersonApiController extends CommunityBaseController
{

    /**
     * 获取用户信息
     * @return \json
     * @author mashuaifei
     * @date_time 2025/09/23
     */
    public function getUserinfo($pigcms_id)
    {
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
        $bind_info = $service_house_village_user_bind->getBindInfo(['pigcms_id'=>$pigcms_id],'vacancy_id,village_id,type');
        $vacancy_id = isset($bind_info['vacancy_id']) && intval($bind_info['vacancy_id']) > 0 ? intval($bind_info['vacancy_id']) : -1;
        if ($vacancy_id > 0) {
            $user_info = $service_house_village_user_vacancy->getUserVacancyInfo(['pigcms_id'=>$vacancy_id],'usernum,name,phone,single_id,floor_id,layer_id,pigcms_id,village_id');
        }
        $single_id = $user_info['single_id'];
        $floor_id = $user_info['floor_id'];
        $layer_id = $user_info['layer_id'];
        $room_id = $user_info['pigcms_id'];
        return [
            'single_id' => $single_id,
            'floor_id' => $floor_id,
            'layer_id' => $layer_id,
            'room_id' => $room_id,
            'room_name' => $user_info['usernum'],
        ];
    }
    /**
     * 获取默认收件人信息
     * @return \json
     * @author mashuaifei
     * @date_time 2025/09/24
     */
    public function getDefaultRecipientInfo($pigcms_id)
    {
        $pigcms_id = $this->request->post('pigcms_id');
        if(!$pigcms_id)
        {
            return api_output_error(1002,'登录信息错误,请重新登录!');
        }
        $village_id = $this->request->post('village_id');
        $user_id = $this->request->post('uid');
//        $service_house_village_user_bind = new HouseVillageUserBindService();
//        $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
//        $bind_info = $service_house_village_user_bind->getBindInfo(['pigcms_id'=>$pigcms_id],'vacancy_id,village_id,type');
//        $vacancy_id = isset($bind_info['vacancy_id']) && intval($bind_info['vacancy_id']) > 0 ? intval($bind_info['vacancy_id']) : -1;
//        $user_info = [];
//        if ($vacancy_id > 0) {
//            $user_info = $service_house_village_user_vacancy->getUserVacancyInfo(['pigcms_id'=>$vacancy_id],'usernum,name,phone,single_id,floor_id,layer_id,pigcms_id,village_id');
//        }
//        $single_id = $user_info['single_id'];
//        $floor_id = $user_info['floor_id'];
//        $layer_id = $user_info['layer_id'];
//        $room_id = $user_info['pigcms_id'];
        $service_express_pick_up = new CommonPickupPersonService();
        $where[] = ['p.village_id','=',$village_id];
        $where[] = ['p.status','=',1];
        $where[] = ['p.is_default','=',1];
        $where[] = ['p.user_id','=',$user_id];
        $field = 'p.*,p.id as recipient_id, e.location';
        $res = $service_express_pick_up->getCommonPickupPersonInfo( $where, $field, 'p.id DESC' );
        $result['item'] = $res;
        return api_output(0,$result);
    }
    /**
     * 获取默认收件人列表
     * @return \json
     * @author mashuaifei
     * @date_time 2025/09/24
     */
    public function getDefaultRecipientList($pigcms_id)
    {
        $pigcms_id = $this->request->post('pigcms_id');
        if(!$pigcms_id)
        {
            return api_output_error(1002,'登录信息错误,请重新登录!');
        }
        $village_id = $this->request->post('village_id');
        $user_id = $this->request->post('uid');
        $service_express_pick_up = new CommonPickupPersonService();
        $where[] = ['p.village_id','=',$village_id];
        $where[] = ['p.status','=',1];
        $where[] = ['p.user_id','=',$user_id];
        $field = 'p.*,p.id as recipient_id, e.location';
        $res = $service_express_pick_up->getDefaultRecipientList( $where, $field, 'p.id DESC' );
        $result['item'] = $res;
        return api_output(0,$result);
    }
    /**
     * 常用取件人列表
     * @return \json
     * @author mashuaifei
     * @date_time 2025/09/18
 */
    public function getExpressPickupPersonList()
    {
        $pigcms_id = $this->request->post('pigcms_id');
        if(!$pigcms_id)
        {
            return api_output_error(1002,'登录信息错误,请重新登录!');
        }
        $village_id = $this->request->post('village_id');
        $location_id = $this->request->post('location_id');
        $user_id = $this->request->post('uid');
        $recipient_name = $this->request->post('recipient_name');
        $recipient_phone = $this->request->post('recipient_phone');
        $page = $this->request->post('pageIndex',1);
        $limit = $this->request->post('pageSize',10);
        $userInfo = $this->getUserinfo($pigcms_id);
        $service_express_pick_up = new CommonPickupPersonService();

        $where[] = ['p.village_id','=',$village_id];
        $where[] = ['p.status','=',1];
        $location_id && $where[] = ['p.location_id', '=', $location_id];

        $userInfo['single_id'] && $where[] = ['p.single_id', '=', $userInfo['single_id']];
        $userInfo['floor_id'] && $where[] = ['p.floor_id', '=', $userInfo['floor_id']];
        $userInfo['layer_id'] && $where[] = ['p.layer_id', '=', $userInfo['layer_id']];
        $userInfo['room_id'] && $where[] = ['p.room_id', '=', $userInfo['room_id']];
        $user_id && $where[] = ['p.user_id', '=', $user_id];

        $recipient_name && $where[] = ['p.recipient_name', 'like', '%'.$recipient_name.'%'];
        $recipient_phone && $where[] = ['p.recipient_phone', 'like', '%'.$recipient_phone.'%'];
        $field = 'p.*,e.location';

        try{
            $data = $service_express_pick_up->getCommonPickupPersonList($where,$field,$page,$limit,'p.id DESC')->toArray();

            $count = $service_express_pick_up->getCommonPickupPersonCount($where,$field);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        $res=array();
        // 总页数 = 向上取整(总条数 / 每页条数)
        $totalPage = (int)ceil($count / $limit);

        // 是否有下一页：当前页 < 总页数
        $hasNextPage = $page < $totalPage;
        $res['total_limit'] = $limit;
        $res['list'] = $data;
        $res['count'] = $count;
        $res['hasMore'] = $hasNextPage;
        return api_output(0,$res);
    }
    /**
     * 添加常用取件人
     * @return \json
     * @author mashuaifei
     * @date_time 2025/09/18
     */
    public function saveCommonPickupPerson()
    {
        $pigcms_id = $this->request->post('pigcms_id');
        if(!$pigcms_id)
        {
            return api_output_error(1002,'登录信息错误,请重新登录!');
        }
        if( $this->request->post('location_id') == '' ){
            return api_output_error(1001,'请选择取件地址!');
        }
        if( $this->request->post('recipient_name') == '' ){
            return api_output_error(1001,'请填写收件人姓名!');
        }
        if( $this->request->post('recipient_phone') == '' ){
            return api_output_error(1001,'请填写收件人电话!');
        }
        $current_action = $this->request->post('current_action');
        $pickup_person_id = $this->request->post('id');
        $village_id = $this->request->post('village_id');

        $data = [];
        $data['status'] = 1;
        if( $current_action == 'edit' ){
            $data['update_by'] = $this->adminUser['user_name'];
            $data['update_time'] = date('Y-m-d H:i:s');
        }else{
            $data['create_by'] = $this->adminUser['user_name'];
            $data['create_time'] = date('Y-m-d H:i:s');
        }
        $data['village_id'] = $village_id;
        $data['recipient_name'] = $this->request->post()['recipient_name'];
        $data['recipient_phone'] = $this->request->post()['recipient_phone'];
        $data['location_id'] = $this->request->post()['location_id'];
        $data['remark'] = $this->request->post()['remark'];
        $data['is_default'] = $this->request->post()['is_default'];
        $data['user_id'] = $this->request->post()['uid'];
        $userInfo = $this->getUserinfo($pigcms_id);
        $data['single_id'] = $userInfo['single_id'];
        $data['floor_id'] = $userInfo['floor_id'];
        $data['layer_id'] = $userInfo['layer_id'];
        $data['room_id'] = $userInfo['room_id'];
        $data['room_name'] = $userInfo['room_name'];

        $service_express_pick_up = new CommonPickupPersonService();
        $service_common_pickup_person = new CommonPickupPersonService();
        try{
            if( $current_action == 'edit' ){
                if( $this->request->post()['is_default'] == 1 ){
                    //先把所有默认取件人改为0
                    $service_common_pickup_person->savePickupLocation([
                        'village_id' => $village_id,
                        'user_id' => $this->request->post()['uid'],
                        'is_default' => 1,
                    ],['is_default' => 0]);
                }
                $res = $service_express_pick_up->savePickupLocation([ 'id' => $pickup_person_id ],$data);
            }else{
                $res = $service_express_pick_up->addPickupLocation($data);
            }
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        if($res)
            return api_output(0,[],'保存成功');
        return api_output_error(1001,'服务异常');
    }

    /**
     * 删除常用取件人
     * @return \json
     * @author mashuaifei
     * @date_time 2025/09/18
     */
    public function delCommonPickupPerson()
    {
        $pigcms_id = $this->request->post('pigcms_id');
        if(!$pigcms_id)
        {
            return api_output_error(1002,'登录信息错误,请重新登录!');
        }
        $pickup_person_id = $this->request->post('id');
        $service_express_pick_up = new CommonPickupPersonService();
        if( !$pickup_person_id ){
            return api_output_error(1001,'请选择要删除的取件人!');
        }
        try{
            $res = $service_express_pick_up->savePickupLocation([ 'id' => $pickup_person_id ],['status' => 0 ]);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        if($res)
            return api_output(0,[],'删除成功');
        return api_output_error(1001,'服务异常');
    }


}