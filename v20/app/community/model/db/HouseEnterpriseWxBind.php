<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/3/9 16:26
 */
namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseEnterpriseWxBind extends Model{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 修改信息
     * @author: zhubaodi
     * @date_time: 2021/8/6 14:39
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function save_one($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    /**
     * 获取单个数据
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     * @author:zhubaodi
     * @date_time: 2021/7/29 16:50
     */
    public function getOne($where, $field = true, $order = [])
    {
        $info = $this->field($field)->where($where)->order($order)->find();
        return $info;
    }

    /**
     * 添加
     * @author:zhubaodi
     * @date_time: 2021/7/29 16:50
     * @param array $data 要进行添加的数据
     * @return mixed
     */
    public function add($data) {
        $pigcms_id = $this->insertGetId($data);
        return $pigcms_id;
    }
}