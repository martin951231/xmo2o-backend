<?php


namespace app\community\model\service;

use app\community\model\db\VillageQywxActionLog;
use app\community\model\db\VillageQywxDataCenter;

class VillageQywxDataCenterService
{
    protected $db_village_qywx_action_log = '';
    protected $db_village_qywx_data_center = '';

    public function __construct()
    {
        $this->db_village_qywx_action_log = new VillageQywxActionLog();
        $this->db_village_qywx_data_center = new VillageQywxDataCenter();
    }

    /**
     * 添加日志
     * @author lijie
     * @date_time 2021/03/25
     * @param $data
     * @return int|string
     */
    public function addActionLog($data)
    {
        $id = $this->db_village_qywx_action_log->addOne($data);
        return $id;
    }

    /**
     * 添加数据
     * @author lijie
     * @date_time 2021/03/25
     * @param $data
     * @return int|string
     */
    public function addData($data)
    {
        $id = $this->db_village_qywx_data_center->addOne($data);
        return $id;
    }

    /**
     * 数据统计
     * @author lijie
     * @date_time 2021/03/25
     * @param array $where
     * @param bool $field
     * @return float
     */
    public function getSum($where=[],$field=true)
    {
        $sum = $this->db_village_qywx_data_center->getSum($where,$field);
        return $sum;
    }

    /**
     * 获取列表
     * @author lijie
     * @date_time 2021/03/25
     * @param array $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDataList($where=[],$field=true,$page=1,$limit=10,$order='id DESC')
    {
        $data = $this->db_village_qywx_data_center->getList($where,$field,$page,$limit,$order);
        return $data;
    }
}