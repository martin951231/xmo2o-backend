<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/7/29 16:47
 */


namespace app\community\model\db;

use think\Model;
class VillageQywxGuideAbout extends Model
{
    /**
     * 添加
     * @author:zhubaodi
     * @date_time: 2021/7/29 16:50
     * @param array $data 要进行添加的数据
     * @return mixed
     */
    public function addOne($data) {
        $pigcms_id = $this->insertGetId($data);
        return $pigcms_id;
    }


    /**
     * 获取单个数据
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     * @author:zhubaodi
     * @date_time: 2021/7/29 16:50
     */
    public function getOne($where, $field = true)
    {
        $info = $this->field($field)->where($where)->find();
        return $info;
    }


    /**
     * 修改信息
     * @author:zhubaodi
     * @date_time: 2021/7/29 16:50
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    /**
     * 获取列表
     * @author:zhubaodi
     * @date_time: 2021/7/29 16:50
     */
    public function getList($where, $field = true, $page = 0, $limit = 20, $order = 'id DESC', $type = 0)
    {
        $list = $this->field($field)->where($where);
        if ($page)
            $list = $list->order($order)->page($page, $limit)->select();
        else
            $list = $list->select();
        return $list;
    }


}