<?php
/**
 * 社区小区物业费积分设置表
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/23 15:44
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseNewPileUserMoneyLog extends Model{



    /**
     * 获取单个信息
     * @author:zhubaodi
     * @date_time: 2022/9/23 10:40
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array
     */
    public function getOne($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }

    /**
     * 修改信息
     * @author:zhubaodi
     * @date_time: 2022/9/23 10:40
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }
    /**
     * 插入数据并获取插入id
     * @author:zhubaodi
     * @date_time: 2022/9/23 10:40
     * @param array $data
     * @return int
     **/
    public function addOne($data)
    {
        $insert = $this->insertGetId($data);
        return $insert;
    }

    /**
     * 查询列表
     * @author: zhubaodi
     * @date_time: 2022/9/23 10:40
     * @param $where
     * @param bool $field
     * @param string $order
     * @param int $page
     * @param int $page_size
     * @return mixed
     */
    public function getList($where,$field =true,$page=0,$page_size=10,$order='id ASC') {
        $db_list = $this->field($field)->order($order);
        if ($page) {
            $db_list->page($page,$page_size);
        }
        $list = $db_list->where($where)->select();
        return $list;
    }

    /**
     *统计数量
     * @author: zhubaodi
     * @date_time: 2022/9/23 10:40
     * @param $where
     * @param $group
     * @return mixed
     */
    public function getCount($where) {
        $list = $this->where($where)->count();
        return $list;
    }
}