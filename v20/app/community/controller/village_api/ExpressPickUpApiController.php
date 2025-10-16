<?php


namespace app\community\controller\village_api;


use app\common\model\service\config\ConfigCustomizationService;
use app\community\model\service\CommonPickupPersonService;
use app\community\model\service\ExpressPickupService;
use app\community\controller\CommunityBaseController;
use app\community\model\db\HouseVillage;
use app\community\model\service\HouseVillageUserBindService;
use app\community\model\service\HouseVillageUserVacancyService;

class ExpressPickUpApiController extends CommunityBaseController
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
     * 代取地址列表
     * @return \json
     * @author mashuaifei
     * * @date_time 2025/09/17
 */
    public function getExpressPickupLocationList()
    {
        $pigcms_id = $this->request->post('pigcms_id');
        if(!$pigcms_id)
        {
            return api_output_error(1002,'登录信息错误,请重新登录!');
        }
        $village_id = $this->request->post('village_id');
        $location = $this->request->post('location');
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',10);
        $service_express_pick_up = new ExpressPickupService();
        $chargeType = ['water','electric','gas'];
//        $jz_wsxdsj_customized_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
//        if($jz_wsxdsj_customized_open){
//            array_push($chargeType,'new_nuanqifei');
//        }
        $where[] = ['village_id','=',$village_id];
        $where[] = ['status','=',1];
        try{
            if( $location != '' ){
                $where[] = ['location','like','%'.$location.'%'];
            }
            $data = $service_express_pick_up->getExpressPickupLocationList($where,true,$page,$limit,'p.id DESC')->toArray();

            $count = $service_express_pick_up->getExpressPickupLocationCount($where);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        $res=array();
        $res['total_limit'] = $limit;
        $res['list'] = $data;
        $res['count'] = $count;

//        $houseVillageService=new HouseVillageService();
//        $res['role_addmeter']=$houseVillageService->checkPermissionMenu(112104,$this->adminUser,$this->login_role,$this->dismissPermissionRole);


        return api_output(0,$res);
    }
    /**
     * 添加代取地址
     * @return \json
     * @author mashuaifei
     * * @date_time 2025/09/17
     */
    public function saveExpressPickupLocation()
    {
        $current_action = $this->request->post('current_action');
        $village_id = $current_action == 'edit' ? $this->request->post('village_id') : $this->adminUser['village_id'] ;
        $location = $this->request->post('location');
        $pickup_point_id = $this->request->post('id');
        if( !$location ){
            return api_output_error(1001,'请填写代取地址!');
        }
        if( !$village_id ){
            return api_output_error(1002,'用户身份信息获取错误,请重新登录!');
        }
        $data = [];
        $data['status'] = 1;
        $data['location'] = $location;
        if( $current_action == 'edit' ){
            $data['update_by'] = $this->adminUser['user_name'];
            $data['update_time'] = date('Y-m-d H:i:s');
        }else{
            $data['create_by'] = $this->adminUser['user_name'];
            $data['create_time'] = date('Y-m-d H:i:s');
        }

        $data['village_id'] = $village_id;
        $service_express_pick_up = new ExpressPickupService();

        $exists = $service_express_pick_up->getExpressPickupLocationCount([
            'status' => 1,
            'village_id' => $data['village_id'],
            'location'=>$data['location']
        ]);

        if( $exists > 0 ){
            return api_output_error(1001,'代取地址已存在!');
        }

        try{
            if( $current_action == 'edit' ){
                $res = $service_express_pick_up->savePickupLocation([ 'id' => $pickup_point_id ],$data);
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
    public function delExpressPickupLocation()
    {
        $pickup_point_id = $this->request->post('id');
        $service_express_pick_up = new ExpressPickupService();
        if( !$pickup_point_id ){
            return api_output_error(1001,'请选择要删除的地址!');
        }
        try{
            $res = $service_express_pick_up->savePickupLocation([ 'id' => $pickup_point_id ],['status' => 0 ]);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        if($res)
            return api_output(0,[],'删除成功');
        return api_output_error(1001,'服务异常');
    }

    /**
     * 常用取件人列表
     * @return \json
     * @author mashuaifei
     * @date_time 2025/09/18
     */
    public function getExpressApplicationList()
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
        $search = $this->request->post('search');
        $page = $this->request->post('pageIndex',1);
        $limit = $this->request->post('pageSize',10);
        $userInfo = $this->getUserinfo($pigcms_id);
        $service_express_pick_up = new ExpressPickupService();

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

        if( $search !== '' ){
            $where[] = ['p.track_num|p.pickup_code|p.recipient_phone|p.recipient_name|e.location', 'like', '%'.$search.'%'];
        }

        try{
            $data = $service_express_pick_up->getExpressApplicationList($where,$field,$page,$limit,'p.id DESC')->toArray();

            $count = $service_express_pick_up->getExpressApplicationCount($where);
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
     * 添加申请
     * @return \json
     * @author mashuaifei
     * @date_time 2025/09/24
     */
    public function saveExpressApplication()
    {
        $pigcms_id = $this->request->post('pigcms_id');
        if(!$pigcms_id)
        {
            return api_output_error(1002,'登录信息错误,请重新登录!');
        }
        if( $this->request->post('pickup_code') == '' ){
            return api_output_error(1001,'请填写取件码!');
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
        $pickup_id = $this->request->post('id');
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
        $data['remark'] = $this->request->post()['remark'];
        $data['user_id'] = $this->request->post()['uid'];
        $data['pickup_code'] = $this->request->post()['pickup_code'];
        if( $this->request->post()['pickup_code'] ){
            $data['track_num'] = $this->request->post()['track_num'];
        }

        $data['location_id'] = $this->request->post()['location_id'];
        //查看当前收件人是否为默认收件人, 如果不存在, 则添加默认收件人, 如果存在但不是默认, 则更新为默认, 否则不做处理
        $service_common_pickup_person = new CommonPickupPersonService();
        $recipient_info_where = [
            'p.recipient_name' => $this->request->post()['recipient_name'],
            'p.recipient_phone' => $this->request->post()['recipient_phone'],
            'p.village_id' => $village_id,
            'p.user_id' => $this->request->post()['uid'],
        ];
        $field = 'p.*,e.location';
        $recipient_info = $this->request->post()['recipient_id'] ? $service_common_pickup_person->getCommonPickupPersonInfo($recipient_info_where,$field) : [];
        $data['location_id'] = $this->request->post()['location_id'];

        $userInfo = $this->getUserinfo($pigcms_id);

        $data['single_id'] = $userInfo['single_id'];
        $data['floor_id'] = $userInfo['floor_id'];
        $data['layer_id'] = $userInfo['layer_id'];
        $data['room_id'] = $userInfo['room_id'];
        $data['room_name'] = $userInfo['room_name'];
        $data['create_by'] = $userInfo['name'];
        $data['recipient_name'] = $this->request->post()['recipient_name'];
        $data['recipient_phone'] = $this->request->post()['recipient_phone'];
        if( !$recipient_info ){
            //如果当前收件人不存在, 则添加为默认收件人,返回收件人id, 并且把其余收件人设置为非默认
            //先把其他收件人设置为非默认
            $service_common_pickup_person->savePickupLocation([
                'village_id' => $village_id,
                'user_id' => $this->request->post()['uid'],
                'is_default' => 1,
                ],['is_default' => 0]);
            $defalut_data = [
                'village_id' => $village_id,
                'user_id' => $this->request->post()['uid'],
                'recipient_name' => $this->request->post()['recipient_name'],
                'recipient_phone' => $this->request->post()['recipient_phone'],
                'location_id' => $this->request->post()['location_id'],
                'is_default' => 1,
                'create_by' => $this->adminUser['user_name'],
                'create_time' => date('Y-m-d H:i:s'),
                'single_id' => $userInfo['single_id'],
                'floor_id' => $userInfo['floor_id'],
                'layer_id' => $userInfo['layer_id'],
                'room_id' => $userInfo['room_id'],
                'room_name' => $userInfo['room_name'],
            ];
            $data['recipient_id'] = $service_common_pickup_person->addPickupLocation($defalut_data);
        }else{
            $data['recipient_id'] = $this->request->post()['recipient_id'];
            if( $recipient_info['is_default'] !== 1 ){
                //先把其他收件人设置为非默认
                $service_common_pickup_person->savePickupLocation([
                    'village_id' => $village_id,
                    'user_id' => $this->request->post()['uid'],
                    'is_default' => 1,
                ],['is_default' => 0]);
                //把当前这条设置为默认
                $service_common_pickup_person->savePickupLocation([
                    'village_id' => $village_id,
                    'recipient_name' => $recipient_info['recipient_name'],
                    'recipient_phone' => $recipient_info['recipient_phone'],
                    'user_id' => $this->request->post()['uid'],
                    'is_default' => 0,
                ],['is_default' => 1]);
            }
        }

        $service_express_pick_up = new ExpressPickupService();

        try{
            if( $current_action == 'edit' ){
                $res = $service_express_pick_up->savePickupApplication([ 'id' => $pickup_id ],$data);
            }else{
                $res = $service_express_pick_up->addPickupApplication($data);
            }
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        if($res)
            return api_output(0,[],'保存成功');
        return api_output_error(1001,'服务异常');
    }
    /**
     * 查看收件人是否为默认收件人
     * @return \json
     * @author mashuaifei
     * @date_time 2025/09/18
     */
    public function checkDefaultRecipient( $userInfo )
    {
        $recipient_id = $this->request->post('recipient_id');
        $service_express_pick_up = new ExpressPickupService();
        $res = $service_express_pick_up->checkDefaultRecipient($recipient_id);

    }
    /**
     * 作废快递
     * @return \json
     * @author mashuaifei
     * @date_time 2025/09/18
     */
    public function delExpressApplication()
    {
        $pigcms_id = $this->request->post('pigcms_id');
        if(!$pigcms_id)
        {
            return api_output_error(1002,'登录信息错误,请重新登录!');
        }
        $pickup_app_id = $this->request->post('id');
        $service_express_pick_up = new ExpressPickupService();
        if( !$pickup_app_id ){
            return api_output_error(1001,'请选择要作废的快递!');
        }
        try{
            $res = $service_express_pick_up->savePickupApplication([ 'id' => $pickup_app_id ],['status' => 0 ]);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        if($res)
            return api_output(0,[],'作废成功');
        return api_output_error(1001,'服务异常');
    }
}