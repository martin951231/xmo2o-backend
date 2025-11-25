<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2022/1/8 16:52
 */
namespace app\community\model\db;

use think\Model;
use think\facade\Db;
class FaceD6RequestRecord extends Model{

    /**
     * 查询单条数据
     * @param $where
     * @param $field
     * @return mixed
     * @author:zhubaodi
     * @date_time: 2022/1/8 16:57
     */
    public function getOne($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }


    /**
     * 修改单条数据
     * @param $where
     * @param $save
     * @return mixed
     * @author:zhubaodi
     * @date_time: 2022/1/8 16:57
     */
    public function save_one($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }


    /**
     * Notes: 添加数据获取id
     * @param $where
     * @param $data
     * @return int|string
     * @author:zhubaodi
     * @date_time: 2022/1/8 16:57
     */
    public function addFind($data)
    {
        $id = $this->insertGetId($data);
        return $id;
    }


    /**
     * Notes: 删除
     * @param $where
     * @return bool
     * @throws \Exception
     * @author:zhubaodi
     * @date_time: 2022/1/8 16:57
     */
    public function del($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }

    /**
     * Notes: 获取数量
     * @param $where
     * @return int
     * @author:zhubaodi
     * @date_time: 2022/1/8 16:57
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * Notes:
     * @param array $where
     * @param bool $field
     * @param string $order
     * @return \think\Collection
     * @author:zhubaodi
     * @date_time: 2022/1/8 16:57
     */
    public function getList($where=[],$field=true,$order='id desc',$page=0,$limit=0)
    {
        $sql = $this->field($field)->where($where)->order($order);
        if($limit)
        {
            $sql->limit($page,$limit);
        }
        $list = $sql->select();
        return $list;
    }
}