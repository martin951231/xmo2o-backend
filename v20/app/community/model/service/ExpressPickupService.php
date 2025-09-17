<?php

namespace app\community\model\service;


use app\community\model\db\ExpressPickUpLocation;
use token\Token;
use app\traits\MeterDirectorNoticeHouseTraits;
use think\Exception;
use app\traits\CommonLogTraits;
use app\traits\house\AdminLoginTraits;
class ExpressPickupService
{
    use MeterDirectorNoticeHouseTraits;
    use CommonLogTraits;
    use AdminLoginTraits;
    /**
     * 抄表项目列表
     * @author
     * @date_time 2021/07/09
     * @param array $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     */
    public function getExpressPickupLocationList($where=[],$field=true,$page=1,$limit=15,$order='p.id DESC')
    {
        $db_express_pick_up_location = new ExpressPickUpLocation();
        $data = $db_express_pick_up_location->getList($where,$field,$order,$page,$limit);
        return $data;
    }

    /**
     * 抄表项目列表数量
     * @author
     * @date_time 2021/07/09
     * @param array $where
     * @return mixed
     */
    public function getExpressPickupLocationCount($where=[])
    {
        $db_house_new_meter_project = new HouseNewChargeProject();
        $count = $db_house_new_meter_project->getCount($where);
        return $count;
    }

    public function getMeterProjectInfo($where=[],$field=true)
    {
        $db_house_new_meter_project = new HouseNewChargeProject();
        $count = $db_house_new_meter_project->getFind($where,$field);
        return $count;
    }

    /**
     * 添加负责人
     * @author
     * @date_time 2021/07/09
     * @param array $data
     * @return int|string
     */
    public function addMeterDirector($data=[])
    {
        $db_house_new_meter_director = new HouseNewMeterDirector();
        $res = $db_house_new_meter_director->addOne($data);
        return $res;
    }

    /**
     * 修改负责人信息
     * @author
     * @date_time 2021/07/09
     * @param array $where
     * @param array $data
     * @return int|string
     */
    public function saveMeterDirector($where=[],$data=[])
    {
        $db_house_new_meter_director = new HouseNewMeterDirector();
        $res = $db_house_new_meter_director->saveOne($where,$data);
        return $res;
    }

    /**
     * 负责人详情
     * @author
     * @date_time 2021/07/10
     * @param array $where
     * @param bool $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMeterDirectorInfo($where=[],$field=true)
    {
        $db_house_new_meter_director = new HouseNewMeterDirector();
        $data = $db_house_new_meter_director->getOne($where,$field);
        return $data;
    }

}