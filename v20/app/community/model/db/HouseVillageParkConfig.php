<?php
/**
 * 智慧停车场配置
 * @author weili
 * @datetime 2020/8/27
 */

namespace app\community\model\db;

use app\community\model\service\Car\CarHandleGetInfoService;
use think\Model;
class HouseVillageParkConfig extends Model
{
    /**
     * Notes: 获取一条
     * @param $where
     * @param $field
     * @return mixed
     * @author: weili
     * @datetime: 2020/8/27 13:37
     */
    public function getFind($where,$field=true)
    {
         $info = $this->where($where)->field($field)->find();
         return $info;
    }
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }
    /**
     * 查询列表
     * @author: liukezhu
     * @date : 2022/1/12
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     */
    public function get_list($where,$field=true,$page=0,$limit=15,$order='id DESC')
    {
        if ($page){
            $data = $this->where($where)->field($field)->page($page,$limit)->order($order)->select();
        }else{
            $data = $this->where($where)->field($field)->order($order)->select();
        }
        return $data;
    }

    /**
     * 查询列表
     * @author: liukezhu
     * @date : 2022/1/12
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     */
    public function getVillagelist($where, $field = true, $page = 0, $limit = 15, $order = 'c.id DESC')
    {
        $list = $this->alias('c')
            ->leftJoin('house_village b', 'b.village_id = c.village_id')
            ->where($where)
            ->field($field);
        if ($page) {
            $list = $list->order($order)->page($page, $limit)->select();
        } else {
            $list = $list->select();
        }
        return $list;
    }

    /**
     *编辑数据
     * @author: liukezhu
     * @date : 2022/1/12
     * @param $where
     * @param $save
     * @return mixed
     */
    public function save_one($where, $save) {
        $set_info = $this->where($where)->save($save);
        $village_id = isset($data['village_id']) ? intval($data['village_id']) : 0;
        if (!$village_id) {
            $info = $this->where($where)->field('village_id')->find();
            $village_id = isset($info['village_id']) ? intval($info['village_id']) : 0;
        }
        if ($village_id > 0) {
            (new CarHandleGetInfoService())->clearParkConfig($village_id);
        }
        return $set_info;
    }


    /**
     * 添加数据
     * @author lijie
     * @date_time 2020/07/16 16:08
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        $res = $this->insertGetId($data);
        $village_id = isset($data['village_id']) ? intval($data['village_id']) : 0;
        if ($village_id > 0) {
            (new CarHandleGetInfoService())->clearParkConfig($village_id);
        }
        return $res;
    }

}