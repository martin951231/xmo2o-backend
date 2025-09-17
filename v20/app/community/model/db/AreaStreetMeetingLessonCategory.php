<?php


namespace app\community\model\db;

use think\Model;

class AreaStreetMeetingLessonCategory extends Model
{
    /**
     * 三会一课分类
     * @author lijie
     * @date_time 2020/09/17
     * @param $where
     * @param bool $field
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLists($where,$field=true,$order='cat_sort DESC',$page=0,$limit=0)
    {
//        $data = $this->where($where)->field($field)->order($order)->select();
//        return $data;
        $sql = $this->where($where)->field($field)->order($order);
        if($page){
            $data = $sql->page($page,$limit)->select();
        }else{
            $data = $sql->select();
        }
        return $data;
    }

    /**
     * Notes: 获取数量
     * @param $where
     * @return int
     * @author: weili
     * @datetime: 2020/9/18 11:52
     */
    public function getCount($where){
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * Notes: 查询一条
     * @param $where
     * @param bool $field
     * @param string $order
     * @return array|Model|null
     * @author: weili
     * @datetime: 2020/9/18 13:21
     */
    public function getFind($where,$field=true,$order='cat_sort desc,cat_id desc')
    {
        $data = $this->where($where)->field($field)->order($order)->find();
        return $data;
    }

    /**
     * Notes: 插入一条
     * @param $data
     * @return int|string
     * @author: weili
     * @datetime: 2020/9/18 13:37
     */
    public function addData($data)
    {
        $id = $this->insertGetId($data);
        return $id;
    }

    /**
     * Notes: 修改数据
     * @param $where
     * @param $data
     * @return AreaStreetMeetingLessonCategory
     * @author: weili
     * @datetime: 2020/9/18 13:38
     */
    public function editData($where,$data){
        $res = $this->where($where)->update($data);
        return $res;
    }

    /**
     * 统计类型
     * @author: liukezhu
     * @date : 2022/5/6
     * @param $where
     * @param string $group
     * @param bool $field
     * @return mixed
     */
    public function getListByGroup($where,$group='c.type',$field=true)
    {
        return $this->alias('c')->rightJoin('area_street_meeting_lesson t','t.cat_id=c.cat_id')->where($where)->field($field)->group($group)->select();
    }

}