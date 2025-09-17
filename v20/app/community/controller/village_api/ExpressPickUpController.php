<?php


namespace app\community\controller\village_api;


use app\common\model\service\config\ConfigCustomizationService;
use app\community\model\service\ExpressPickupService;
use app\community\controller\CommunityBaseController;
use app\community\model\db\HouseVillage;

class ExpressPickUpController extends CommunityBaseController
{
    /**
     * 初始化
     * @author
     * @date_time 2021/07/09
     * @return \json
     */
    public function getHouseVillageList()
    {
        $House_village	= new HouseVillage();
        //	查询社区列表
        $aHouseVillageList	=	$House_village->getList(['status' => 1]);
        var_dump($aHouseVillageList);die;
    }
    /**
     * 抄表列表
     * @author lijie
     * @date_time 2021/07/09
     * @return \json
     */
    public function getExpressPickupLocationList()
    {
        $village_id = $this->adminUser['village_id'];
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',10);
        $service_express_pick_up = new ExpressPickupService();
        $chargeType = ['water','electric','gas'];
        $jz_wsxdsj_customized_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        if($jz_wsxdsj_customized_open){
            array_push($chargeType,'new_nuanqifei');
        }
        $where[] = ['p.village_id','=',$village_id];
        $where[] = ['p.status','=',1];
//        $where[] = ['c.charge_type','in',$chargeType];
        try{
            $field = 'p.name as project_name,c.charge_number_name as subject_name,charge_type,p.id as project_id,p.subject_id,p.mday';
            if($jz_wsxdsj_customized_open){
                $field .= ',p.meter_use_v,p.reset_btn_show,p.sign_btn_show';
            }
            $data = $service_express_pick_up->getExpressPickupLocationList($where,$field,$page,$limit,'p.id DESC')->toArray();

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

}