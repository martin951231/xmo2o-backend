<?php
/**
 * 小区权限分组
 * Created by PhpStorm.
 * Author: win7
 * Date Time: 2020/4/23 17:33
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;


class HouseAdminGroup extends Model{

    protected $pk = 'group_id';
    /**
     * 获取单个社区管理员权限分组表数据信息
     * @author: wanziyang
     * @date_time: 2020/4/23 15:44
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function get_one($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }

    public function getGroupList($where,$field =true){
        $listTmp=$this->field($field)->where($where)->order('group_id desc')->select();
        if (!$listTmp || $listTmp->isEmpty()) {
            $listTmp = [];
        }else{
            $listTmp=$listTmp->toArray();
        }
        return $listTmp;
    }

    public function getHouseAdminGroupLists($where = [] ,$field = true,$order='group_id desc',$page=0,$limit=20){
        $listSql=$this->field($field)->where($where)->order($order);
        if($page)
        {
            $listSql->page($page,$limit);
        }
        $listTmp=$listSql->select();
        if (!$listTmp || $listTmp->isEmpty()) {
            $listTmp = [];
        }else{
            $listTmp=$listTmp->toArray();
        }
        return $listTmp;
    }

    public function getHouseAdminGroupCount($where=array())
    {
        $count = $this->where($where)->count();
        $count=$count ? $count:0;
        return $count;
    }

    public function addGroup($addData=array()){
        if(empty($addData)){
            return false;
        }
        $idd=$this->insertGetId($addData);
        return $idd;
    }

    //更新数据
    public function updateGroup($where=array(),$updateData=array()){
        if(empty($where) || empty($updateData)){
            return false;
        }
        $ret=$this->where($where)->update($updateData);
        //echo $this->getLastSql();
        return $ret;
    }

    //删除
    public function deleteGroup($where=array()){
        if(empty($where)){
            return false;
        }
        return $this->where($where)->delete();
    }

    public function getColumn($where,$field)
    {
        $column = $this->where($where)->column($field);
        return $column;
    }
}