<?php
/**
 * 小区解绑申请业主家属租客信息表
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/25 15:14
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;
class HouseVillageUserUnbind extends Model{

    /**
     * 查询对应条件下申请解绑数量
     * @author: wanziyang
     * @date_time: 2020/4/25 15:01
     * @param array $where
     * @return int
     */
    public function getVillageUnbindUserNum($where) {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * 修改信息
     * @author: wanziyang
     * @date_time: 2020/5/13 11:39
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    /**
     * 获取单个信息
     * @author: wanziyang
     * @date_time: 2020/5/13 13:09
     * @param array $where 查询条件
     * @param bool|string $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function getOne($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }

    /**
     * 获取解绑列表
     * @author:wanziyang
     * @date_time: 2020/5/12 19:35
     * @param array $where 查询条件
     * @param int|string $page 分页
     * @param string|bool $field 分页
     * @param string $order 排序
     * @param int $page_size 每页数量
     * @return array|null|\think\Model
     */
    public function getLimitList($where,$page=0,$field ='a.*',$order='a.pigcms_id DESC',$page_size=10) {
        $db_list = $this->alias('a')
            ->leftJoin('house_village_user_bind ub', 'ub.pigcms_id=a.bind_id')
            ->leftJoin('house_village_user_vacancy b', 'b.pigcms_id=a.room_id')
            ->leftJoin('house_village_floor c', 'b.floor_id=c.floor_id')
            ->leftJoin('house_village_single d', 'b.single_id=d.id')
            ->leftJoin('user u','a.uid = u.uid')
            ->field($field)
            ->order($order);
        if ($page>0) {
            $db_list->page(intval($page), intval($page_size));
        }
        $list = $db_list->where($where)->select();
        return $list;
    }

}