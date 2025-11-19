<?php
/**
 * 业主资料
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/5/8 11:58
 */

namespace app\community\model\db;


use think\Model;
use think\facade\Db;
class HouseVillageDataConfig extends Model{

    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 获取业主资料信息
     * @author: wanziyang
     * @date_time: 2020/5/8 13:11
     * @param array $where
     * @param bool|string $field
     * @param string $order
     * @return \think\Collection
     */
    public function getList($where,$field =true,$order='sort DESC, acid ASC') {
        $list = $this->field($field)->where($where)->order($order)->select();
        return $list;
    }
}