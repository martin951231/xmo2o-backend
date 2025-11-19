<?php


namespace app\community\model\db;

use think\Model;

class AreaStreetMatterCategory extends Model
{
    /**
     * 街道事项分类列表
     * @author lijie
     * @date_time 2020/09/21
     * @param $where
     * @param $field
     * @param $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLists($where,$field,$order,$page=0,$limit=0)
    {
//        $data = $this->where($where)->field($field)->order($order)->select();
//        return $data;
        $sql = $this->where($where)->field($field)->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }

    /**
     * Notes: 获取详情
     * @param $where
     * @param bool $field
     * @param string $order
     * @return array|Model|null
     * @author: weili
     * @datetime: 2020/11/2 14:37
     */
    public function getFind($where,$field=true,$order='cat_id desc')
    {
        $info = $this->where($where)->field($field)->order($order)->find();
        return $info;
    }

    /**
     * Notes: 添加数据
     * @param $data
     * @return int|string
     * @author: weili
     * @datetime: 2020/11/2 14:43
     */
    public function addData($data)
    {
        $res = $this->insert($data);
        return $res;
    }

    /**
     * Notes: 编辑数据
     * @param $where
     * @param $data
     * @return AreaStreetMatterCategory
     * @author: weili
     * @datetime: 2020/11/2 14:43
     */
    public function editData($where,$data)
    {
        $res = $this->where($where)->update($data);
        return $res;
    }
}