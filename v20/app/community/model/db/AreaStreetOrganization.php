<?php
namespace app\community\model\db;

use think\Model;

class AreaStreetOrganization extends Model
{
    /**
     * Notes: 获取列表
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @datetime: 2021/2/22 15:56
     * @return \think\Collection
     */
    public function getSelectList($where,$field=true,$order='id DESC',$page=0,$limit=20)
    {
        if($page) {
            $res = $this->where($where)->field($field)->order($order)->page($page, $limit)->select();
            return $res;
        }else{
            $res = $this->where($where)->field($field)->order($order)->select();
            return $res;
        }
    }

    /**
     * Notes: 获取单条信息
     * @param $where
     * @param bool $field
     * @return array|Model|null
     * @datetime: 2021/2/22 15:57
     */
    public function getOne($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }

    /**
     * Notes: 添加数据
     * @param $data
     * @return int|string
     * @datetime: 2021/2/22 15:58
     */
    public function addOne($data) {
        $area_id = $this->insertGetId($data);
        return $area_id;
    }

    /**
     * Notes: 修改数据
     * @param $where
     * @param $save
     * @return bool
     * @datetime: 2021/2/22 15:58
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    /**
     * Notes: 硬删除
     * @param $where
     * @return bool
     * @throws \Exception
     * @datetime: 2021/2/23 9:29
     */
    public function delFind($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }
}
