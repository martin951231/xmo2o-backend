<?php
/**
 * 智慧停车场 出入通道  （一个通道一个设备）
 * @author weili
 * @datetime 2020/8/3
 */

namespace app\community\model\db;

use think\Model;
class ParkPassageType extends Model
{
    /**
     * Notes: 获取数量
     * @param $where
     * @author: zhubaodi
     * @datetime: 2022/12/13 13:37
     * @return integer
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }


    /**
     * Notes: 获取一条
     * @param $where
     * @param $field
     * @return mixed
     * @author: zhubaodi
     * @datetime: 2022/12/13 13:37
     */
    public function getFind($where,$field=true)
    {
        $info = $this->where($where)->field($field)->find();
        return $info;
    }
    /**
     * Notes: 获取一条
     * @param $where
     * @param $field
     * @return mixed
     * @author: zhubaodi
     * @datetime: 2022/12/13 13:37
     */
    public function getColumn($where,$field)
    {
        $info = $this->where($where)->column($field);
        return $info;
    }

    /**
     * @author: zhubaodi
     * @datetime: 2022/12/13 13:37
     * @param array $where 查询条件
     * @param int $page 分页
     * @param string $field 需要查询的字段 默认查询所有
     * @param string $order 排序
     * @param integer $page_size 排序
     * @return array|null|Model
     */
    public function getList($where,$field =true) {

        $list = $this->where($where)->field($field)->select();
        return $list;
    }


    /**
     * 根据条件获取列表
     * @author: zhubaodi
     * @datetime: 2022/12/13 13:37
     * @param $where
     * @param $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLists($where,$field=true,$page=0,$limit=15,$order='id DESC')
    {
        if ($page){
            $data = $this->where($where)->field($field)->page($page,$limit)->order($order)->select();
        }else{
            $data = $this->where($where)->field($field)->order($order)->select();
        }

        return $data;
    }


    /**
     * 修改信息
     * @author: zhubaodi
     * @datetime: 2022/12/13 13:37
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function save_one($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }
    /**
     * 插入数据并获取插入id
     * @author: zhubaodi
     * @datetime: 2022/12/13 13:37
     * @paramarray $data
     **/
    public function addOne($data)
    {
        $insert = $this->insertGetId($data);
        return $insert;
    }


    /**
     * 删除
     * @author: zhubaodi
     * @datetime: 2022/12/13 13:37
     * @param array $where 删除数据条件
     * @return array|null|Model
     */
    public function del_one($where) {
        $del = $this->where($where)->delete();
        return $del;
    }
}