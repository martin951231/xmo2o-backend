<?php
/**
 * CustomerService.php
 * 客服
 * Create on 2020/11/12 20:11
 * Created by zhumengqun
 */

namespace app\mall\model\db;

use think\Model;

class CustomerService extends Model
{
    /**
     * @param $where
     * @return array
     * 根据条件获取
     */
    public function getSome($where)
    {
        $arr = $this->field(true)->where($where)->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }
}