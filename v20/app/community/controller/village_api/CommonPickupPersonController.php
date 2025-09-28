<?php


namespace app\community\controller\village_api;


use app\common\model\service\config\ConfigCustomizationService;
use app\community\model\service\ExpressPickupService;
use app\community\controller\CommunityBaseController;
use app\community\model\db\HouseVillage;

class ExpressPickUpController extends CommunityBaseController
{
    /**
     * 代取地址列表
     * @return \json
     * @author mashuaifei
     * * @date_time 2025/09/17
 */
    public function getExpressPickupLocationList()
    {
        $village_id = $this->adminUser['village_id'];
        $location = $this->request->post('location');
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',10);
        $service_express_pick_up = new ExpressPickupService();
        $chargeType = ['water','electric','gas'];
        $jz_wsxdsj_customized_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        if($jz_wsxdsj_customized_open){
            array_push($chargeType,'new_nuanqifei');
        }
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
    public function addExpressPickupLocationDetail()
    {
        $village_id = $this->adminUser['village_id'];
        $this->saveExpressPickupLocation($village_id);
    }
    public function editExpressPickupLocationDetail()
    {
        $village_id = $this->request->post('village_id');
        $this->saveExpressPickupLocation($village_id);

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
        $data['update_by'] = $this->adminUser['user_name'];
        $data['update_time'] = date('Y-m-d H:i:s');
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
}