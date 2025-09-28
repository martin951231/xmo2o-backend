<?php


namespace app\community\controller\village_api;


use app\common\model\service\config\ConfigCustomizationService;
use app\community\model\service\CommonPickupPersonService;
use app\community\controller\CommunityBaseController;
use app\community\model\db\HouseVillage;

class CommonPickupPersonController extends CommunityBaseController
{
    /**
     * 常用取件人列表
     * @return \json
     * @author mashuaifei
     * @date_time 2025/09/18
 */
    public function getExpressPickupPersonList()
    {
        $village_id = $this->adminUser['village_id'];
        $location_id = $this->request->post('location_id');
        $single_id = $this->request->post('single_id');
        $floor_id = $this->request->post('floor_id');
        $layer_id = $this->request->post('layer_id');
        $room_id = $this->request->post('room_id');
        $user_id = $this->request->post('user_id');
        $recipient_name = $this->request->post('recipient_name');
        $recipient_phone = $this->request->post('recipient_phone');
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',10);
        $service_express_pick_up = new CommonPickupPersonService();
        $where[] = ['p.village_id','=',$village_id];
        $where[] = ['p.status','=',1];
        $location_id && $where[] = ['p.location_id', '=', $location_id];
        $single_id && $where[] = ['p.single_id', '=', $single_id];
        $floor_id && $where[] = ['p.floor_id', '=', $floor_id];
        $layer_id && $where[] = ['p.layer_id', '=', $layer_id];
        $room_id && $where[] = ['p.room_id', '=', $room_id];
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
        $res['total_limit'] = $limit;
        $res['list'] = $data;
        $res['count'] = $count;
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
        if( $this->request->post('location_id') == '' ){
            return api_output_error(1001,'请选择取件地址!');
        }
        if( $this->request->post('recipient_name') == '' ){
            return api_output_error(1001,'请填写收件人姓名!');
        }
        if( $this->request->post('recipient_phone') == '' ){
            return api_output_error(1001,'请填写收件人电话!');
        }
        if( $this->request->post('room_name') == '' || $this->request->post('room_id') == '' ){
            return api_output_error(1001,'请选择房间!');
        }
        $current_action = $this->request->post('current_action');
        $pickup_person_id = $this->request->post('id');
        $village_id = $current_action == 'edit' ? $this->request->post('village_id') : $this->adminUser['village_id'] ;

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
        $data['single_id'] = $this->request->post()['single_id'];
        $data['floor_id'] = $this->request->post()['floor_id'];
        $data['layer_id'] = $this->request->post()['layer_id'];
        $data['room_id'] = $this->request->post()['room_id'];
        $data['room_name'] = $this->request->post()['room_name'];

        $service_express_pick_up = new CommonPickupPersonService();

        try{
            if( $current_action == 'edit' ){
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