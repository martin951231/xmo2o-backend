<?php
/**
 *智慧停车场配置
 */

namespace app\community\model\db;
use app\community\model\service\Car\CarHandleGetInfoService;
use think\Model;

class HouseVillageParkGarageConfig extends Model
{
    public function getSum($where, $field)
    {
        $data = $this->where($where)->sum($field);
        return $data;
    }

    public function getListData($where, $field = true, $orderby = 'id DESC', $page = 0, $page_size = 10,$groupby='')
    {
        $data_sql = $this->field($field)->where($where)->order($orderby);
        if ($page > 0) {
            $data_sql->page($page, $page_size);
        }
        if($groupby){
            $data_sql->group($groupby);
        }
        $data = $data_sql->select();
        return $data;
    }

    public function getCount($where = [])
    {
        $result = $this->where($where)->count();
        return $result;
    }

    public function getOneData($where, $field = '*', $orderby = 'id DESC')
    {
        $data = $this->field($field)->where($where)->order($orderby)->find();
        return $data;
    }

    public function saveOneData($where, $data)
    {
        $res = $this->where($where)->save($data);
        $info = $this->where($where)->field('village_id')->find();
        if (isset($info['village_id']) && intval($info['village_id']) > 0) {
            (new CarHandleGetInfoService())->clearGarageConfig(intval($info['village_id']));
        }
        return $res;
    }

    public function updateOneData($where, $data)
    {
        $result = $this->where($where)->update($data);
        $info = $this->where($where)->field('village_id')->find();
        if (isset($info['village_id']) && intval($info['village_id']) > 0) {
            (new CarHandleGetInfoService())->clearGarageConfig(intval($info['village_id']));
        }
        return $result;
    }

    public function addOneData($data)
    {
        $result = $this->insertGetId($data);
        if (isset($data['village_id']) && intval($data['village_id']) > 0) {
            (new CarHandleGetInfoService())->clearGarageConfig(intval($data['village_id']));
        }
        return $result;
    }

    public function getColumn($where,$field, string $key = '')
    {
        $info = $this->where($where)->column($field, $key);
        return $info;
    }

    public function delParkGarageConfig($where)
    {
        $ret = $this->where($where)->delete();
        return $ret;
    }
}