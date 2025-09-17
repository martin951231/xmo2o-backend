<?php
/**
 * 加入志愿者活动
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/5/29 16:34
 */

namespace app\community\model\db;


use think\Model;
use think\facade\Db;
class VolunteerActivityJoin extends Model
{
    /**
     * 获取列表信息
     * @author:wanziyang
     * @date_time: 2020/5/29 16:35
     * @param array $where 查询条件
     * @param int|string $page 分页
     * @param string|bool $field 分页
     * @param string $order 排序
     * @param int $page_size 每页数量
     * @return array|null|\think\Model
     */
    public function getLimitVolunteerActivityJoinList($where,$page=0,$field =true,$order='join_id ASC',$page_size=10) {
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
     * @date_time: 2020/5/29 16:36
     * @param array $where 查询条件
     * @return array|null|\think\Model
     */
    public function getLimitVolunteerActivityJoinCount($where) {
        $count = $this->where($where)->count();
        return $count;
    }
    /**
     * 获取单个数据信息
     * @author: wanziyang
     * @date_time: 2020/5/29 17:02
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
     * @date_time: 2020/5/29 17:02
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    /**
     * 志愿者活动报名
     * @author lijie
     * @date_time 2020/09/09
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        $res = $this->insert($data);
        return $res;
    }


    public function getOnes($where,$field=true){
        $data = $this->alias('a')
            ->leftJoin('volunteer_activity b','b.activity_id = a.activity_id')
            ->where($where)
            ->field($field)
            ->find();
        return $data;
    }

}