<?php

/**
 * 优惠券店铺
 * @author hengtingmei
 * @datetime 2021/03/30
 */

namespace app\community\model\db;

use think\Model;

class ParkCoupons extends Model
{
    /**
     * Notes: 获取数量
     * @param $where
     * @author: weili
     * @datetime: 2020/8/3 16:48
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
     * @datetime: 2021/11/3 13:37
     */
    public function getFind($where,$field=true)
    {
        $info = $this->where($where)->field($field)->find();
        return $info;
    }

    /**
     * @author: wanziyang
     * @date_time: 2020/5/14 13:59
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
     * @datetime: 2021/11/3 13:37
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
    public function getLists($where,$field=true,$page=0,$limit=15,$order='c_id DESC')
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
     * @author:zhubaodi
     * @date_time: 2021/6/10 17:16
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
     * @author:zhubaodi
     * @date_time: 2021/6/10 17:16
     * @paramarray $data
     **/
    public function addOne($data)
    {
        $insert = $this->insertGetId($data);
        return $insert;
    }


    /**
     * 删除
     * @author: wanziyang
     * @date_time: 2020/4/29 17:17
     * @param array $where 删除数据条件
     * @return array|null|Model
     */
    public function del_one($where) {
        $del = $this->where($where)->delete();
        return $del;
    }

    public function getColumn($where,$field=true){
        $list = $this->where($where)->column($field);
        return $list;
    }
}
