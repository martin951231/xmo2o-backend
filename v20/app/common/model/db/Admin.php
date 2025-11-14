<?php
/**
 * 系统后台用户model
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/7 09:01
 */

namespace app\common\model\db;
use think\Model;
// use think\facade\Db;
class Admin extends Model {

	//账号等级（0:普通号，1：区域账号，2：超级号
	const LEVEL_NORMAL = 0;
	const LEVEL_AREA = 1;
	const LEVEL_SUPER = 2;
	
	//账号状态（0:禁用，1：正常）
	const STATUS_NORMAL = 1;
    /**
     * 根据用户名获取后端用户表的数据
     * @param $account
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserByAccount($account) {
        if(empty($account)) {
            return false;
        }

        $where = [
            "account" => trim($account),
        ];

        $result = $this->where($where)->find();
        return $result;
    }

    /**
     * 根据用户名获取后端用户表的数据
     * @param $account
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserById($id) {
       if(empty($id)) {
            return false;
        }

        $where = [
            "id" => trim($id),
        ];

        $result = $this->where($where)->find();
        return $result;
    }

    /**
     * 根据主键ID更新数据表中的数据
     * @param $id
     * @param $data
     * @return bool
     */
    public function updateById($id,$data) {
        $id = intval($id);
        if(empty($id) || empty($data) || !is_array($data)) {
            return false;
        }

        $where = [
            "id" => $id,
        ];

        return $this->where($where)->save($data);
    }

    /**
     * 根据条件返回一条数据
     * @param $where array 条件
     * @return array|bool|Model|null
     */
    public function getOne($where,$field='*') {
        if(!$where){
            return null;
        }

        $result = $this->where($where)->field($field)->find();
        return $result;
    }

    /**
     * 根据条件返回数据列表
     * @param $where array 条件
     * @return array|bool|Model|null
     */
    public function getList($where,$field='*') {
        if(!$where){
            return null;
        }

        $result = $this->where($where)->field($field)->select();
        return $result;
    }
}