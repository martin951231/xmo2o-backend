<?php
/**
 * Created by PhpStorm.
 * User: gxs
 * Date: 2020/6/1
 * Time: 16:57
 */
namespace app\community\model\db;

use think\Model;

class PartyActivityJoin extends Model
{
    protected $name = 'party_activity_join';

    public function getList($where,$page=0,$field =true,$order='id ASC',$page_size=10)
    {
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
     * @param $where
     * @param bool $field
     * @return array|null|Model
     */
    public function getOne($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }

    /**
     * @param $where
     * @return bool
     * @throws \Exception
     */
    public function delOne($where) {
        $del = $this->where($where)->delete();
        return $del;
    }

    /**
     * @param $where
     * @param $save
     * @return bool
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    /**
     * Notes: 获取数量
     * @param $where
     * @return int
     * @author: weili
     * @datetime: 2020/9/22 17:20
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * 添加活动报名
     * @author lijie
     * @date_time 2020/10/14
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        $res = $this->insert($data);
        return $res;
    }
}