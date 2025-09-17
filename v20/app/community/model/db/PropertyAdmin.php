<?php
/**
 * 物业管理员
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/23 17:20
 */

namespace app\community\model\db;


use think\Model;
use think\facade\Db;

class PropertyAdmin extends Model{

    /**
     * 获取单个物业管理数据信息
     * @author: wanziyang
     * @date_time: 2020/4/23 15:44
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */

    public function get_one($where,$field =true,$orderby='status asc,id desc'){
        $info = $this->field($field)->where($where)->order($orderby)->find();
        return $info;
    }

    /**
     * 修改信息
     * @author: wanziyang
     * @date_time: 2020/4/24 14:39
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


    //删除
    public function delPropertyAdmin($where=array()){
        if(empty($where)){
            return false;
        }
        return $this->where($where)->delete();
    }

    public function getList($where,$field=true,$order='id DESC',$page=0,$limit=10)
    {
        if ($page){
            $data = $this->where($where)->field($field)->page($page,$limit)->order($order)->select();
        }else{
            $data = $this->where($where)->field($field)->order($order)->select();
        }

        return $data;
    }

    public function getCount($where) {
        $count =$this->where($where)->count();
        return $count;
    }
}