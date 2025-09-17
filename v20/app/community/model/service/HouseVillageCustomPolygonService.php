<?php


namespace app\community\model\service;
use app\community\model\db\HouseVillageCustomPolygon;

class HouseVillageCustomPolygonService
{
    public $model = '';

    public function __construct()
    {
        $this->model = new HouseVillageCustomPolygon();
    }

    /**
     * 获取网格员列表
     * @author lijie
     * @date_time 2020/12/19
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGridCustomList($where,$field=true,$page=1,$limit=15,$order='id DESC')
    {
        $data = $this->model->getList($where,$field,$page,$limit,$order);
        $count = $this->model->getCount($where);
        $res['list'] = $data;
        $res['count'] = $count;
        return $res;
    }

    /**
     * 添加网格员
     * @author lijie
     * @date_time 2020/12/19
     * @param $data
     * @return int|string
     */
    public function addGirdCustom($data)
    {
        $res = $this->model->addOne($data);
        return $res;
    }

    /**
     * 更新网格员
     * @author lijie
     * @date_time 2020/12/19
     * @param $where
     * @param $data
     * @return bool
     */
    public function saveGirdCustom($where,$data)
    {
        $res = $this->model->saveOne($where,$data);
        return $res;
    }

    /**
     * 获取网格员详情
     * @author lijie
     * @date_time 2020/12/19
     * @param $where
     * @param bool $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGridCustomDetail($where,$field=true)
    {
        $data = $this->model->getOne($where,$field);
        return $data;
    }
}