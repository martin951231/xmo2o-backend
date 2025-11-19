<?php
/**
 * liuruofei
 * 2021/08/24
 * 套餐管理
 */
namespace app\new_marketing\model\db;

use think\Model;

class NewMarketingPackageRegion extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    //保存数据
    public function editData($where, $param) {
        $res = $this->where($where)->strict(false)->update($param);
        return $res;
    }

    //保存数据
    public function addData($param) {
        $res = $this->strict(false)->insertGetId($param);
        return $res;
    }

    //获取数据
    public function getOneData($param) {
        $res = $this->where($param)->find();
        return $res;
    }

    //获取数据
    public function setStatus($where, $status) {
        $res = $this->where($where)->update(['status' => $status]);
        return $res;
    }

    //删除
    public function del($id) {
        $res = $this->where("id", $id)->update(['is_del' => 1]);
        return $res;
    }

    //获取商家后台套餐列表
    public function getMerSearchList($where, $field) {
        $prefix = config('database.connections.mysql.prefix');
        $res = $this->alias('a')
            ->leftJoin($prefix.'new_marketing_package b','b.id = a.package_id')
            ->where($where)
            ->field($field)
            ->select()
            ->toArray();
        return $res;
    }

    //根据条件查出数据的所有ID
    public function getIdsByWhere($where)
    {
        $ids = $this->where($where)->column('id');
        return $ids;
    }

    //获取联表数据
    public function getWhereData($where, $field) {
        $prefix = config('database.connections.mysql.prefix');
        $res = $this->alias('a')
            ->leftJoin($prefix.'new_marketing_package b','b.id = a.package_id')
            ->where($where)
            ->field($field)
            ->find();
        return $res;
    }

}