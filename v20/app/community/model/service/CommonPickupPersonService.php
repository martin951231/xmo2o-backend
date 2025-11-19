<?php

namespace app\community\model\service;


use app\community\model\db\CommonPickupPerson;
use token\Token;
use app\traits\MeterDirectorNoticeHouseTraits;
use think\Exception;
use app\traits\CommonLogTraits;
use app\traits\house\AdminLoginTraits;
class CommonPickupPersonService
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
    public function getCommonPickupPersonList($where=[],$field=true,$page=1,$limit=15,$order='p.id DESC')
    {
        $db_express_pick_up_location = new CommonPickupPerson();
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
    public function getCommonPickupPersonCount($where=[],$field=true)
    {
        $db_common_pickup_person = new CommonPickupPerson();
        $count = $db_common_pickup_person->getCount($where,$field);
        return $count;
    }

    public function getCommonPickupPersonInfo($where=[], $field=true, $order='p.id DESC')
    {
        $db_common_pickup_person = new CommonPickupPerson();
        $res = $db_common_pickup_person->getOne($where, $field, $order);
        return $res;
    }

    public function getDefaultRecipientList($where=[],$field=true,$page=1,$limit=100,$order='p.id DESC')
    {
        $db_common_pickup_person = new CommonPickupPerson();
        $res = $db_common_pickup_person->getList($where, $field, $order , false, false);
        return $res;
    }

    /**
     * 添加负责人
     * @author
     * @date_time 2021/07/09
     * @param array $data
     * @return int|string
     */
    public function addPickupLocation($data=[])
    {
        $db_express_pick_up_location = new CommonPickupPerson();
        $res = $db_express_pick_up_location->addOne($data);
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
    public function savePickupLocation($where=[],$data=[])
    {
        $db_express_pick_up_location = new CommonPickupPerson();
        $res = $db_express_pick_up_location->saveOne($where,$data);
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
        $db_express_pick_up_location = new CommonPickupPerson();
        $res = $db_express_pick_up_location->saveOne($where);
        return $res;
    }

}