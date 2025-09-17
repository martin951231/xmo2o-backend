<?php
/**
 * 小区管理员
 * Created by PhpStorm.
 * Author: win7
 * Date Time: 2020/4/23 17:33
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseAdmin extends Model{

    /**
     * 获取单个小区管理员数据信息
     * @author: wanziyang
     * @date_time: 2020/4/23 15:44
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function getOne($where,$field =true,$orderBy='wid desc'){
        $info = $this->field($field)->where($where)->order($orderBy)->find();
        return $info;
    }


    /**
     * 修改信息
     * @author: wanziyang
     * @date_time: 2020/4/24 17:56
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function save_one($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }
    //添加数据
    public function addData($addData=array()){
        if(empty($addData)){
            return false;
        }
        $idd=$this->insertGetId($addData);
        return $idd;
    }
    /**
     * 获取小区管理员关联小区信息数据信息
     * @author: wanziyang
     * @date_time: 2020/5/7 11:41
     * @param array $where 查询条件
     * @param bool|string $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function get_admin_village($where,$field ='a.*') {
        $list = $this->alias('a')
            ->leftjoin('house_village hv', 'a.village_id=hv.village_id')
            ->where($where)
            ->field($field)
            ->group('a.id')
            ->select();
        return $list;
    }
    public function get_one_normal_house_admin($where,$field ='a.*',$orderBy='a.status ASC') {
        $oneFind = $this->alias('a')
            ->leftjoin('house_village hv', 'a.village_id=hv.village_id')
            ->where($where)
            ->field($field)
            ->order($orderBy)
            ->find();
        return $oneFind;
    }
    //获取列表数据
    public function getHouseAdminLists($where = [] ,$field = true,$order=true,$page=0,$limit=20){
        $listSql = $this->field($field)->where($where)->order($order);
        if($page)
        {
            $listSql->page($page,$limit);
        }
        $result = $listSql->select();
        if (!$result || $result->isEmpty()) {
            $result = [];
        }else{
            $result=$result->toArray();
        }
        return $result;
    }
    public function getHouseAdminCount($where=array())
    {
        $count = $this->where($where)->count();
        $count=$count ? $count:0;
        return $count;
    }
    //删除数据
    public function delHouseAdmin($where=array()){
        if(empty($where)){
            return false;
        }
        return $this->where($where)->delete();
    }


}