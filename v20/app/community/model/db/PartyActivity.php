<?php
/**
 * Created by PhpStorm.
 * User: 郭先森
 * Date: 2020/5/30
 * Time: 16:38
 */

namespace app\community\model\db;

use think\Model;

class PartyActivity extends Model
{
    protected $name = 'party_activity';

    public static function getPartyActivityModel($where,$page=0,$field =true,$order='party_activity_id desc',$page_size=10){

        $db_list = PartyActivity::field($field)
                  ->order($order);
                    if ($page) {
                        $db_list->page($page,$page_size);
                    }
        $list = $db_list->where($where)->select();
        return $list;

    }


    public function addPartyActivityDb($data)
    {
        $id = $this->insertGetId($data);
        return $id;
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
     * @param mixed|null $where
     * @return array|null|Model
     */
    public function find($where)
    {
        $info = $this->find($where);
        return $info;
    }

    /**
     * Notes: 获取数量
     * @param $where
     * @return int
     * @author: weili
     * @datetime: 2020/9/22 11:33
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * Notes:获取一条数据
     * @param $where
     * @param $field
     * @param $order
     * @return array|Model|null
     * @author: weili
     * @datetime: 2020/9/22 11:51
     */
    public function getFind($where,$field=true,$order='party_activity_id desc')
    {
        $info = $this->where($where)->field($field)->order($order)->find();
        return $info;
    }

    /**
     * 报名人数+1
     * @param $where
     * @return mixed
     */
    public function setOne($where)
    {
        $res = $this->where($where)->Inc('sign_up_num')->update();
        return $res;
    }

    public function getGroupList($where,$field='*',$order='a.party_activity_id desc',$group,$page=0,$limit=20)
    {
        $list = $this->alias('a')->leftJoin('area_street b','b.area_id=a.street_id')->where($where)->field($field)->order($order)->group($group)->page($page,$limit)->select();
        return $list;
    }

    public function getGroupCount($where,$group)
    {
        $list = $this->alias('a')->leftJoin('area_street b','b.area_id=a.street_id')->where($where)->group($group)->count();
        return $list;
    }
}