<?php
/**
 * 志愿者活动
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/5/29 13:13
 */

namespace app\community\model\db;


use think\Model;
use think\facade\Db;
class VolunteerActivity extends Model
{
    /**
     * 获取列表信息
     * @author:wanziyang
     * @date_time: 2020/5/29 13:14
     * @param array $where 查询条件
     * @param int|string $page 分页
     * @param string|bool $field 分页
     * @param string $order 排序
     * @param int $page_size 每页数量
     * @return array|null|\think\Model
     */
    public function getLimitVolunteerActivityList($where,$page=0,$field =true,$order='activity_id ASC',$page_size=10) {
        $db_list = $this
            ->field($field)
            ->order($order);
        if ($page) {
            $db_list->page($page,$page_size);
        }
        $list = $db_list->where($where)->select();
        return $list;
    }

    /**
     * 获取列表信息数量
     * @author:wanziyang
     * @date_time: 2020/5/29 13:14
     * @param array $where 查询条件
     * @return array|null|\think\Model
     */
    public function getLimitVolunteerActivityCount($where) {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * 获取单个数据信息
     * @author: wanziyang
     * @date_time: 2020/5/29 14:53
     * @param array $where 查询条件
     * @param bool|string $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function getOne($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }
    /**
     * 修改信息
     * @author: wanziyang
     * @date_time: 2020/5/29 15:01
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }
    /**
     * 添加信息
     * @author: wanziyang
     * @date_time: 2020/5/29 15:02
     * @param array $data 添加内容
     * @return bool
     */
    public function addOne($data) {
        $area_id = $this->insertGetId($data);
        return $area_id;
    }

    /**
     * 删除信息
     * @author: wanziyang
     * @date_time: 2020/5/29 16:16
     * @param array $where 改写内容的条件
     * @return bool
     */
    public function delOne($where) {
        $del = $this->where($where)->delete();
        return $del;
    }

    /**
     * 报名人数+1
     * @param $where
     * @return mixed
     */
    public function setOne($where)
    {
        $res = $this->where($where)->Inc('join_num')->update();
        return $res;
    }

    /**
     *  报名人数-1
     * @author: liukezhu
     * @date : 2022/2/10
     * @param $where
     * @return mixed
     */
    public function decNum($where) {
        try{
            $res = $this->where($where)->dec('join_num')->update();
            return $res;
        }catch (\Exception $e){
            return  0;
        }
    }
}