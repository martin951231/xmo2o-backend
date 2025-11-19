<?php
/**
 * liuruofei
 * 2021/08/24
 * 套餐管理
 */
namespace app\new_marketing\model\db;

use think\Model;

class NewMarketingPackage extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    //套餐管理列表
    public function getSearchList($where, $limit) {
        $list = $this->where($where)->order('sort desc')->paginate($limit);
        return $list;
    }

    //保存数据
    public function editData($id, $param) {
        $res = $this->where("id", $id)->strict(false)->update($param);//关闭字段严格检查
        return $res;
    }

    //保存数据
    public function addData($param) {
        $res = $this->strict(false)->insertGetId($param);//关闭字段严格检查
        return $res;
    }

    //获取数据
    public function getOneData($param) {
        $res = $this->where($param)->find();
        return $res;
    }

    //获取数据
    public function setStatus($id, $status) {
        $res = $this->where("id", $id)->update(['status' => $status]);
        return $res;
    }

    //删除
    public function del($id) {
        $res = $this->where("id", $id)->update(['is_del' => 1,'update_time' => time()]);
        return $res;
    }

}