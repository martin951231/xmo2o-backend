<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/5/7 11:30
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseOpenDoorAdver extends Model{

    /**
     * 获取广告列表
     * @author:zhubaodi
     * @date_time: 2021/4/26 19:52
     */
    public function getList($where,$field=true,$page=0,$limit=20,$order='id DESC') {
        $list = $this->field($field)->where($where);
        if($page)
            $list = $list->page($page,$limit)->select();
        else
            $list = $list->select();
        return $list;
    }
}