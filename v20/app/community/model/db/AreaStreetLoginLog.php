<?php
/**
 * 街道社区登录记录
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/23 17:20
 */

namespace app\community\model\db;


use think\Model;
use think\facade\Db;

class AreaStreetLoginLog extends Model{
    /**
     * 添加信息
     * @author: wanziyang
     * @date_time: 2020/4/24 14:55
     * @param array $data 添加内容
     * @return bool
     */
    public function addOne($data) {
        $area_id = $this->insertGetId($data);
        return $area_id;
    }
}