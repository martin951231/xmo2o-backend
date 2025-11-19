<?php

namespace app\community\model\service;


use app\community\model\db\ExpressPickUpLocation;
use app\community\model\db\ExpressApplication;
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
     * 获取代取地址列表
     * @author
     * @date_time 2025-09-24
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
     * 获取代取地址列表数量
     * @author
     * @date_time 2025-09-24
     * @param array $where
     * @return mixed
     */
    public function getExpressPickupLocationCount($where=[])
    {
        $db_house_new_meter_project = new ExpressPickUpLocation();
        $count = $db_house_new_meter_project->getCount($where);
        return $count;
    }

    /**
     * 添加代取地址
     * @author
     * @date_time 2025-09-24
     * @param array $data
     * @return int|string
     */
    public function addPickupLocation($data=[])
    {
        $db_express_pick_up_location = new ExpressPickUpLocation();
        $res = $db_express_pick_up_location->addOne($data);
        return $res;
    }

    /**
     * 修改地址信息
     * @author
     * @date_time 2025-09-24
     * @param array $where
     * @param array $data
     * @return int|string
     */
    public function savePickupLocation($where=[],$data=[])
    {
        $db_express_pick_up_location = new ExpressPickUpLocation();
        $res = $db_express_pick_up_location->saveOne($where,$data);
        return $res;
    }

    /**
     * 获取快递取件申请
     * @author
     * @date_time 2025-09-24
     * @param array $data
     * @return int|string
     */
    public function getExpressApplicationList( $where=[],$field=true,$page=1,$limit=10,$order='p.id DESC' )
    {
        $db_express_pick_up_location = new ExpressApplication();
        $data = $db_express_pick_up_location->getList($where,$field,$order,$page,$limit);
        return $data;
    }

    /**
     * 获取快递取件申请总数
     * @author
     * @date_time 2025-09-24
     * @param array $data
     * @return int|string
     */
    public function getExpressApplicationCount($where = [])
    {
        $db_express_pick_up_application = new ExpressApplication();
        $count = $db_express_pick_up_application->getCount($where);
        return $count;
    }

    /**
     * 获取快递取件申请详情
     * @author
     * @date_time 2025-09-24
     * @param array $data
     * @return int|string
     */
    public function getExpressApplicationDetail($where = [],$field=true)
    {
        $db_express_pick_up_application = new ExpressApplication();
        $count = $db_express_pick_up_application->getOne($where,$field);
        return $count;
    }

    /**
     * 添加快递取件申请
     * @author
     * @date_time 2025-09-24
     * @param array $data
     * @return int|string
     */
    public function addPickupApplication($data=[])
    {
        $db_express_pick_up_location = new ExpressApplication();
        $res = $db_express_pick_up_location->addOne($data);
        return $res;
    }

    /**
     * 保存快递取件申请
     * @author
     * @date_time 2025-09-24
     * @param array $data
     * @return int|string
     */
    public function savePickupApplication($where=[],$data=[])
    {
        $db_express_pick_up_location = new ExpressApplication();
        $res = $db_express_pick_up_location->saveOne($where,$data);
        return $res;
    }

    /**
     * 修改快递取件申请
     * @author
     * @date_time 2025-09-24
     * @param array $data
     * @return int|string
     */
    public function updatePickupApplication($where=[],$data=[])
    {
        $db_express_pick_up_location = new ExpressApplication();
        $res = $db_express_pick_up_location->updateAll($where,$data);
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
    public function delPickupLocation($where=[],$field=true)
    {
        $db_express_pick_up_location = new ExpressPickUpLocation();
        $res = $db_express_pick_up_location->saveOne($where);
        return $res;
    }

    /**
     * 作废快递申请
     * @author
     * @date_time 2025-10-21
     * @param array $where
     * @param array $data
     * @return int|string
     */
    public function ExpressPickupCancel($where=[],$data=[])
    {
        $db_express_pick_up_ = new ExpressApplication();
        $res = $db_express_pick_up_->saveOne($where,$data);
        return $res;
    }

}