<?php

namespace app\community\controller\street_api;

use app\community\controller\CommunityBaseController;
use app\community\model\service\AreaStreetService;
use app\community\model\service\HouseVillageService;

class StreetController extends CommunityBaseController
{
    /**
     * 社区列表
     * @author lijie
     * @date_time 2020/09/09 11:00
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function areaStreetLists()
    {
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',20);
        $street_id = $this->request->post('area_street_id',0);
        if(!$street_id)
            return api_output_error(1001,'缺少必传参数');
        $area_street_service = new AreaStreetService();
        $field = 'area_name,phone,address,detail_address,area_id,logo';
        $order = 'area_sort DESC';
        $where['area_type'] = 1;
        $where['area_pid'] = $street_id;
        $data = $area_street_service->getStreetLists($where,$field,$page,$limit,$order);
        foreach ($data as &$val){
            if(empty($val['logo'])) {
                $val['logo'] = cfg('site_url') . '/v20/public/static/community/images/community.png';
            }else{
                $val['logo'] = replace_file_domain($val['logo']);
            }
        }
        return api_output(0,$data);
    }

    /**
     * 社区下小区列表
     * @author lijie
     * @date_time 2020/09/09 11:05
     * @return \json
     */
    public function houseVillageLists()
    {
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',20);
        $street_id = $this->request->post('area_street_id',0);
        if(!$street_id)
            return api_output_error(1001,'缺少必传参数');
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
        $house_village_service = new HouseVillageService();
        if($bind_type)
            $data = $house_village_service->getList(['status'=>1,'community_id'=>$street_id],'village_name,village_address,property_phone,village_logo',$page,$limit,'village_id DESC',1);
        else
            $data = $house_village_service->getList(['status'=>1,'street_id'=>$street_id],'village_name,village_address,property_phone,village_logo',$page,$limit,'village_id DESC',1);
        foreach ($data as &$val){
            if(empty($val['village_logo'])){
                $val['village_logo'] = cfg('site_url') . '/v20/public/static/community/images/village.png';
            }else{
                $val['village_logo'] = replace_file_domain($val['village_logo']);
            }
        }
        return api_output(0,$data);
    }
}