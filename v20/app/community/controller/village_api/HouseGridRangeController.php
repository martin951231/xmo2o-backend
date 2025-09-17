<?php
/**
 * @author : 合肥快鲸科技有限公司
 * @date : 2022/11/14
 */

namespace app\community\controller\village_api;


use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseGridRangeService;

class HouseGridRangeController extends CommunityBaseController
{

    /**
     * 获取小区地图信息
     * @date : 2022/11/14
     * @return \json
     */
    public function getHouseMap(){
        $village_id=$this->adminUser['village_id'];
        try{
            $list = (new HouseGridRangeService())->getHouseInfo($village_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 获取网格集合
     * @date : 2022/11/14
     * @return \json
     */
    public function getHouseGridRange(){
        $village_id=$this->adminUser['village_id'];
        try{
            $list = (new HouseGridRangeService())->getHouseGridRangeList($village_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

}