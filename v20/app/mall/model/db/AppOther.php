<?php
/**
 * AppOther.php
 * 其他IOSAPP列表model
 * Create on 2020/10/21 10:02
 * Created by zhumengqun
 */

namespace app\mall\model\db;

use \think\Model;

class AppOther extends Model
{
    /**
     * 获取所有的iosapp列表
     * @return array
     */
    public function getAll()
    {
        $arr = $this->field(true)->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }
}