<?php
/**
 *
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/9/19 11:36
 */

namespace app\mall\model\db;
use think\Model;
class MallRobotList extends Model {

    public function getRobotCount($where){
        $result = $this->where($where)->count();
        return $result;
    }

    /**
     * @param $limit
     * @return mixed
     * 取随机robot
     */
    public function getRobotList($limit,$where){
        $result = $this->where($where)->orderRaw('rand()')->limit($limit)->select()->toArray();
        return $result;
    }
}