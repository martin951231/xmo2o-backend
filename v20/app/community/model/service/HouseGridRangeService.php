<?php
/**
 * @author : 合肥快鲸科技有限公司
 * @date : 2022/11/14
 */

namespace app\community\model\service;


use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageGridRange;

class HouseGridRangeService
{

    protected $HouseVillage;
    protected $HouseVillageGridRange;
    protected $HouseVillageGridService;

    public function __construct()
    {
        $this->HouseVillage = new HouseVillage();
        $this->HouseVillageGridRange = new HouseVillageGridRange();
        $this->HouseVillageGridService = new HouseVillageGridService();
    }

    /**
     * 获取小区地图位置
     * @date : 2022/11/14
     * @param $village_id
     * @return array|\think\Model|null
     * @throws \think\Exception
     */
    public function getHouseInfo($village_id){
        $house=$this->HouseVillage->getOne($village_id,'village_id,village_name,community_id, street_id,area_id,long,lat');
        if (!$house || $house->isEmpty()){
            throw new \think\Exception("该小区不存在");
        }
        $house=$house->toArray();

        return $house;
    }

    /**
     * 获取网格列表
     * @date : 2022/11/14
     * @param $village_id
     * @return mixed
     */
    public function getHouseGridRangeList($village_id){
        $whereor=[
            [
                ['type','=',3],
                ['bind_id','=',   ],
            ],
            [
                ['type','=',4],
                ['f_village_id','=',$village_id],
            ]
        ];
        $grid_range_list=$this->HouseVillageGridRange->getWhereOrList($whereor,'id,type,bind_id,grid_member_id,f_street_id,manage_range_polygon,polygon_name');
        if ($grid_range_list && !$grid_range_list->isEmpty()){
            foreach ($grid_range_list as &$v){
                $v['manage_range_polygon'] = explode('|',$v['manage_range_polygon']);
                $info = $this->HouseVillageGridService->getGridCenter(['bind_id'=>$v['f_street_id'],'type'=>$v['type']],'zoom');
                $v['zoom'] = $info['zoom'];
            }
        }
        return ['data'=>$grid_range_list];
    }

}