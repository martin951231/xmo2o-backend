<?php
/**
 * 管理员信息
 * Created by PhpStorm.
 * User:zhubaodi
 * Date Time: 2021/4/8
 */
namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseMeterAdminUser extends Model{

    /**
     * 获取单个管理员信息
     * @author: zhubaodi
     * @date_time: 2021/4/8
     * @param string $username 登录账号
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function getOne($username,$field =true){
        $info = $this->field($field)->where(array('username'=>$username))->find();
        return $info;
    }



    /**
     * 添加登录记录
     * @author: zhubaodi
     * @date_time: 2021/4/8
     * @param array $data 对应修改信息数组
     * @return array|null|\think\Model
     */
    public function addLoginLog($data) {
        $update_info = $this->update(['ip'=>$data['ip'],'last_login_time'=>$data['last_login_time']],['id'=>$data['id']]);
        return $update_info;
    }

    /**
     * 修改信息
     * @author: zhubaodi
     * @date_time: 2021/4/8
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }



    /**
     * 获取管理员列表
     * @author: zhubaodi
     * @date_time: 2021/4/8
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return array|null|Model
     */
    public function getList($where=[],$field=true,$page=1,$limit=20,$order='id DESC') {
        $list = $this->field($field)->where($where);
        $list = $list->order($order)->page($page,$limit)->select();
        return $list;
    }

    /**
     * Notes: 获取数量
     * @param $where
     * @return int
     * @author: zhubaodi
     * @datetime: 2021/4/12 17:20
     */
    public function getCount($where=[])
    {
        $count = $this->where($where)->count();
        return $count;
    }


    /**
     * 添加管理员信息
     * @author:zhubaodi
     * @date_time: 2021/4/8
     */
    public function insertOne($data)
    {
        $res = $this->insertGetId($data);
        return $res;
    }


    /**
     * 获取单个管理员信息
     * @author: zhubaodi
     * @date_time: 2021/4/8
     * @param array $where 登录id
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function getInfo($where,$field=true)
    {
        $info = $this->where($where)->field($field)->find();
        return $info;
    }




}
